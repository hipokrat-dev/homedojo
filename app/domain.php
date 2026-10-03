<?php
declare(strict_types=1);
final class AppError extends RuntimeException {
    public function __construct(string $message, public int $status = 400) { parent::__construct($message); }
}
function upgrade_state(array $state): array {
    // Additive migration: never overwrite profiles, scores, tasks or reward history.
    $state['assignments'] ??= [];
    $state['competitions'] ??= [];
    $state['version'] = 3;
    return $state;
}
function initial_state(): array { return upgrade_state(json_decode(file_get_contents(__DIR__.'/seed.json'), true, 512, JSON_THROW_ON_ERROR)); }
function now_tr(?DateTimeImmutable $now = null): DateTimeImmutable { return ($now ?? new DateTimeImmutable())->setTimezone(new DateTimeZone('Europe/Istanbul')); }
function period_start(string $frequency, ?DateTimeImmutable $now = null): string {
    $d = now_tr($now);
    return match ($frequency) { 'monthly' => $d->format('Y-m-01'), 'weekly' => $d->modify('-'.((int)$d->format('N')-1).' days')->format('Y-m-d'), default => $d->format('Y-m-d') };
}
function deadline(string $frequency, ?DateTimeImmutable $now=null): DateTimeImmutable {
    $start=now_tr($now);
    if($frequency!=='monthly')return $start->modify($frequency==='weekly'?'+7 days':'+1 day');
    // Preserve the clock time; clamp dates such as January 31 to February's last day.
    $month=$start->modify('first day of next month');
    return $month->setDate((int)$month->format('Y'),(int)$month->format('m'),min((int)$start->format('d'),(int)$month->format('t')));
}
function assignment_status(array $assignment,?DateTimeImmutable $now=null): string {
    if(in_array($assignment['status']??'active',['completed','cancelled'],true))return $assignment['status'];
    return now_tr($now)>=new DateTimeImmutable($assignment['dueAt'])?'expired':'active';
}
function is_done(array $state, string $userId, array $task, ?DateTimeImmutable $now = null): bool {
    foreach($state['completions'] as $c){
        if($c['userId']!==$userId||$c['taskId']!==$task['id'])continue;
        if(isset($c['dueAt'])){if(now_tr($now)<new DateTimeImmutable($c['dueAt']))return true;}
        elseif($c['day']>=period_start($task['frequency'],$now)&&$c['day']<=now_tr($now)->format('Y-m-d'))return true;
    }
    return false;
}
function available_tasks(array $state,string $userId,string $frequency='all',?DateTimeImmutable $now=null): array {
    return array_values(array_filter($state['tasks'],function($t)use($state,$userId,$frequency,$now){
        if($frequency!=='all'&&$t['frequency']!==$frequency)return false;
        if(is_done($state,$userId,$t,$now))return false;
        foreach($state['assignments']??[] as $a){
            if($a['userId']===$userId&&$a['taskId']===$t['id']&&assignment_status($a,$now)==='active')return false;
        }
        return true;
    }));
}
function competition_snapshot(array $state,array $competition,?DateTimeImmutable $now=null): array {
    $scores=array_fill_keys($competition['participants'],0);$counts=$scores;
    $start=new DateTimeImmutable($competition['startedAt']);$end=new DateTimeImmutable($competition['endsAt']);
    $length=isset($competition['stopIndex'])?max(0,$competition['stopIndex']-$competition['startIndex']):null;
    foreach(array_slice($state['completions'],$competition['startIndex'],$length) as $c){
        $at=new DateTimeImmutable($c['at']);
        if(isset($scores[$c['userId']])&&$at>=$start&&$at<$end&&$at<=now_tr($now)){$scores[$c['userId']]+=$c['points'];$counts[$c['userId']]++;}
    }
    $competition['total']=array_sum($scores);$rows=[];
    foreach($state['users'] as $u)if(isset($scores[$u['id']]))$rows[]=['userId'=>$u['id'],'name'=>$u['name'],'avatar'=>$u['avatar'],'points'=>$scores[$u['id']],'completed'=>$counts[$u['id']]];
    usort($rows,fn($a,$b)=>$b['points']<=>$a['points']);$competition['leaderboard']=$rows;
    $competition['status']=isset($competition['cancelledAt'])?'cancelled':(isset($competition['claimedAt'])?'claimed':($competition['total']>=$competition['target']?'achieved':(now_tr($now)>=$end?'expired':'active')));
    return $competition;
}
function family_goal(array $state,?DateTimeImmutable $now=null): ?array {
    foreach($state['competitions']??[] as $c){
        $view=competition_snapshot($state,$c,$now);
        if(in_array($view['status'],['active','achieved'],true)&&now_tr($now)<new DateTimeImmutable($c['endsAt']))return $view;
    }return null;
}

function earned(array $state, string $id): int { return array_sum(array_column(array_filter($state['completions'],fn($c)=>$c['userId']===$id),'points')); }
function balance(array $state, string $id): int { return earned($state,$id)-array_sum(array_column(array_filter($state['redemptions'],fn($c)=>$c['userId']===$id),'cost')); }
function snapshot(array $state, ?DateTimeImmutable $now=null): array {
    $result=upgrade_state($state);$result['competitions']=array_map(fn($c)=>competition_snapshot($state,$c,$now),$result['competitions']);$result['familyGoal']=family_goal($state,$now); $result['today']=now_tr($now)->format('Y-m-d');$result['serverNow']=now_tr($now)->format(DateTimeInterface::ATOM);
    foreach($result['assignments'] as &$a)$a['status']=assignment_status($a,$now);unset($a);
    foreach($result['users'] as &$u) {
        $u['earned']=earned($state,$u['id']); $u['balance']=balance($state,$u['id']);
        $u['doneIds']=array_values(array_column(array_filter($state['tasks'],fn($t)=>is_done($state,$u['id'],$t,$now)),'id'));
        $u['eligibleTaskIds']=array_column(available_tasks($result,$u['id'],'all',$now),'id');
        $u['goal']=null;foreach($state['rewards'] as $r)if($r['id']===($u['goalRewardId']??null))$u['goal']=$r;
    }unset($u);
    return $result;
}
function valid_text(mixed $v,string $label,int $max,bool $required=true): string {
    if(!is_string($v)||mb_strlen(trim($v))>$max||($required&&trim($v)==='')) throw new AppError($label.' geçerli değil.'); return trim($v);
}
function valid_points(mixed $v): int { if(!is_int($v)||$v<1||$v>100000) throw new AppError('Puan 1–100.000 arasında tam sayı olmalı.');return $v; }
function valid_request(mixed $id): string {if(!is_string($id)||!preg_match('/^[a-zA-Z0-9-]{16,64}$/',$id))throw new AppError('İşlem kimliği geçersiz.');return $id;}
function find_index(array $items,mixed $id,string $label): int { foreach($items as $i=>$item) if($item['id']===$id)return $i; throw new AppError($label.' bulunamadı.',404); }
function uid(): string { return bin2hex(random_bytes(16)); }
function mutate(array &$state,string $action,array $in,?DateTimeImmutable $now=null): void {
    $state=upgrade_state($state);$at=now_tr($now)->format(DateTimeInterface::ATOM);
    switch($action){
    case 'saveTask': case 'saveReward':
        $task=$action==='saveTask'; $key=$task?'tasks':'rewards'; $points=$task?'points':'cost';
        $value=['title'=>valid_text($in['title']??null,'Ad',80),'description'=>valid_text($in['description']??'','Açıklama',240,false),$points=>valid_points($in[$points]??null),'icon'=>valid_text($in['icon']??($task?'✨':'🎁'),'Simge',12)];
        if($task){if(!in_array($in['frequency']??null,['daily','weekly','monthly'],true))throw new AppError('Görev dönemi geçersiz.');$value['frequency']=$in['frequency'];}
        if(isset($in['id'])){$i=find_index($state[$key],$in['id'],'Kayıt');$state[$key][$i]=array_merge($state[$key][$i],$value);}
        else{if(count($state[$key])>=100)throw new AppError('En fazla 100 aktif kayıt ekleyebilirsiniz.');$state[$key][]=['id'=>uid()]+$value;} break;
    case 'deleteTask': case 'deleteReward':
        $key=$action==='deleteTask'?'tasks':'rewards'; $i=find_index($state[$key],$in['id']??null,'Kayıt');array_splice($state[$key],$i,1);break;
    case 'saveUser':
        $i=find_index($state['users'],$in['id']??null,'Kullanıcı');$state['users'][$i]['name']=valid_text($in['name']??null,'İsim',30);$state['users'][$i]['avatar']=valid_text($in['avatar']??null,'Simge',12);break;
    case 'selectGoal':
        $i=find_index($state['users'],$in['userId']??null,'Kullanıcı');find_index($state['rewards'],$in['rewardId']??null,'Ödül');
        $state['users'][$i]['goalRewardId']=$in['rewardId'];$state['users'][$i]['goalSelectedAt']=$at;break;
    case 'createCompetition':
        if(count($state['competitions'])>=100)throw new AppError('En fazla 100 yarışma kaydı oluşturulabilir.');
        if(family_goal($state,$now))throw new AppError('Önce mevcut aile yarışmasını bitir veya iptal et.',409);
        $frequency=$in['frequency']??'';if(!in_array($frequency,['daily','weekly','monthly'],true))throw new AppError('Yarışma süresi geçersiz.');
        $state['competitions'][]=['id'=>uid(),'title'=>valid_text($in['title']??null,'Yarışma adı',80),'prize'=>valid_text($in['prize']??null,'Ortak ödül',100),'icon'=>valid_text($in['icon']??'🏆','Simge',12),'target'=>valid_points($in['target']??null),'frequency'=>$frequency,'startedAt'=>$at,'endsAt'=>deadline($frequency,$now)->format(DateTimeInterface::ATOM),'participants'=>array_column($state['users'],'id'),'startIndex'=>count($state['completions'])];break;
    case 'cancelCompetition': case 'claimCompetition':
        $i=find_index($state['competitions'],$in['id']??null,'Yarışma');$view=competition_snapshot($state,$state['competitions'][$i],$now);
        if($action==='claimCompetition'&&$view['status']!=='achieved')throw new AppError('Ortak ödül henüz alınamaz veya zaten alındı.',409);
        if($action==='cancelCompetition'&&!in_array($view['status'],['active','achieved'],true))throw new AppError('Bu yarışma artık iptal edilemez.',409);
        $state['competitions'][$i][$action==='claimCompetition'?'claimedAt':'cancelledAt']=$at;
        $state['competitions'][$i]['stopIndex']=count($state['completions']);break;
    case 'cancelAssignment':
        find_index($state['users'],$in['userId']??null,'Kullanıcı');
        $i=find_index($state['assignments'],$in['assignmentId']??null,'Atanmış görev');$a=$state['assignments'][$i];
        if($a['userId']!==$in['userId'])throw new AppError('Bu görev başka bir profile ait.',403);
        if(assignment_status($a,$now)!=='active')throw new AppError('Yalnızca devam eden görev iptal edilebilir.',409);
        $state['assignments'][$i]['status']='cancelled';$state['assignments'][$i]['cancelledAt']=$at;break;
    case 'complete':
        find_index($state['users'],$in['userId']??null,'Kullanıcı');
        $i=find_index($state['assignments'],$in['assignmentId']??null,'Atanmış görev');$a=$state['assignments'][$i];
        if($a['userId']!==$in['userId'])throw new AppError('Bu görev başka bir profile ait.',403);
        $status=assignment_status($a,$now);
        if($status==='cancelled')throw new AppError('İptal edilen görevden puan kazanılamaz.',409);
        if($status==='completed')throw new AppError('Bu görevin puanı zaten verildi.',409);
        if($status==='expired')throw new AppError('Süre doldu. Bu görevden puan kazanılamaz.',409);
        $state['assignments'][$i]['status']='completed';$state['assignments'][$i]['completedAt']=$at;
        $state['completions'][]=['id'=>uid(),'assignmentId'=>$a['id'],'userId'=>$a['userId'],'taskId'=>$a['taskId'],'title'=>$a['title'],'icon'=>$a['icon'],'points'=>$a['points'],'frequency'=>$a['frequency'],'day'=>now_tr($now)->format('Y-m-d'),'at'=>$at,'dueAt'=>$a['dueAt'],'goalTitle'=>$a['goalTitle']];break;
    case 'redeem':
        $i=find_index($state['users'],$in['userId']??null,'Kullanıcı');$reward=$state['rewards'][find_index($state['rewards'],$in['rewardId']??null,'Ödül')];
        valid_request($in['requestId']??null);
        foreach($state['redemptions'] as $r)if($r['requestId']===$in['requestId'])throw new AppError('Bu işlem zaten kaydedildi.',409);
        if(balance($state,$in['userId'])<$reward['cost'])throw new AppError('Bu ödül için henüz yeterli puanın yok.',409);
        $state['redemptions'][]=['id'=>uid(),'requestId'=>$in['requestId'],'userId'=>$in['userId'],'rewardId'=>$reward['id'],'title'=>$reward['title'],'icon'=>$reward['icon'],'cost'=>$reward['cost'],'at'=>$at];
        if(($state['users'][$i]['goalRewardId']??null)===$reward['id'])$state['users'][$i]['goalRewardId']=null;
        break;
    default:throw new AppError('Bilinmeyen işlem.',404);
    }
}
function spin(array &$state,string $userId,string $frequency,string $requestId,?DateTimeImmutable $now=null): array {
    $state=upgrade_state($state);$u=$state['users'][find_index($state['users'],$userId,'Kullanıcı')];
    if(!in_array($frequency,['all','daily','weekly','monthly'],true))throw new AppError('Görev dönemi geçersiz.');valid_request($requestId);
    foreach($state['assignments'] as $a)if($a['requestId']===$requestId){
        if($a['userId']!==$userId)throw new AppError('İşlem başka bir profile ait.',409);
        // Retrying a lost response returns the original assignment, without a second draw.
        $task=['id'=>$a['taskId']]+$a;return ['task'=>$task,'assignment'=>$a,'candidates'=>[$task]];
    }
    $goal=null;foreach($state['rewards'] as $r)if($r['id']===($u['goalRewardId']??null))$goal=$r;
    if(!$goal){$family=family_goal($state,$now);if($family)$goal=['id'=>$family['id'],'title'=>$family['prize']];}
    if(!$goal)throw new AppError('Bir hedef ödül seç veya ortak ödüllü aile yarışması başlat.',409);
    $list=available_tasks($state,$userId,$frequency,$now);
    if(!$list)throw new AppError('Bu dönemde seçilebilecek görev kalmadı. Aktif görevlerini tamamla veya başka bir dönem seç.',409);
    $task=$list[random_int(0,count($list)-1)];
    $a=['id'=>uid(),'requestId'=>$requestId,'userId'=>$userId,'taskId'=>$task['id'],'title'=>$task['title'],'description'=>$task['description'],'icon'=>$task['icon'],'points'=>$task['points'],'frequency'=>$task['frequency'],'periodStart'=>period_start($task['frequency'],$now),'assignedAt'=>now_tr($now)->format(DateTimeInterface::ATOM),'dueAt'=>deadline($task['frequency'],$now)->format(DateTimeInterface::ATOM),'status'=>'active','goalRewardId'=>$goal['id'],'goalTitle'=>$goal['title']];
    $state['assignments'][]=$a;
    return ['task'=>$task,'assignment'=>$a,'candidates'=>$list];
}
