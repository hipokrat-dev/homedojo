<?php
declare(strict_types=1);
final class AppError extends RuntimeException {
    public function __construct(string $message, public int $status = 400) { parent::__construct($message); }
}
function upgrade_state(array $state): array {
    // Additive migration: never overwrite profiles, scores, tasks or reward history.
    $state['assignments'] ??= [];
    $state['version'] = 2;
    return $state;
}
function initial_state(): array { return upgrade_state(json_decode(file_get_contents(__DIR__.'/seed.json'), true, 512, JSON_THROW_ON_ERROR)); }
function now_tr(?DateTimeImmutable $now = null): DateTimeImmutable { return ($now ?? new DateTimeImmutable())->setTimezone(new DateTimeZone('Europe/Istanbul')); }
function period_start(string $frequency, ?DateTimeImmutable $now = null): string {
    $d = now_tr($now);
    return match ($frequency) { 'monthly' => $d->format('Y-m-01'), 'weekly' => $d->modify('-'.((int)$d->format('N')-1).' days')->format('Y-m-d'), default => $d->format('Y-m-d') };
}
function deadline(string $frequency, ?DateTimeImmutable $now=null): DateTimeImmutable {
    $start=new DateTimeImmutable(period_start($frequency,$now),new DateTimeZone('Europe/Istanbul'));
    return $start->modify(match($frequency){'weekly'=>'+7 days','monthly'=>'+1 month',default=>'+1 day'});
}
function assignment_status(array $assignment,?DateTimeImmutable $now=null): string {
    if(($assignment['status']??'active')==='completed')return 'completed';
    return now_tr($now)>=new DateTimeImmutable($assignment['dueAt'])?'expired':'active';
}
function is_done(array $state, string $userId, array $task, ?DateTimeImmutable $now = null): bool {
    $start=period_start($task['frequency'],$now); $today=now_tr($now)->format('Y-m-d');
    foreach($state['completions'] as $c) if($c['userId']===$userId && $c['taskId']===$task['id'] && $c['day']>=$start && $c['day']<=$today) return true;
    return false;
}
function available_tasks(array $state,string $userId,string $frequency='all',?DateTimeImmutable $now=null): array {
    return array_values(array_filter($state['tasks'],function($t)use($state,$userId,$frequency,$now){
        if($frequency!=='all'&&$t['frequency']!==$frequency)return false;
        if(is_done($state,$userId,$t,$now))return false;
        foreach($state['assignments']??[] as $a){
            if($a['userId']!==$userId||$a['taskId']!==$t['id'])continue;
            if(assignment_status($a,$now)==='active')return false;
            if($a['frequency']===$t['frequency']&&$a['periodStart']===period_start($t['frequency'],$now))return false;
        }
        return true;
    }));
}
function earned(array $state, string $id): int { return array_sum(array_column(array_filter($state['completions'],fn($c)=>$c['userId']===$id),'points')); }
function balance(array $state, string $id): int { return earned($state,$id)-array_sum(array_column(array_filter($state['redemptions'],fn($c)=>$c['userId']===$id),'cost')); }
function snapshot(array $state, ?DateTimeImmutable $now=null): array {
    $result=upgrade_state($state); $result['today']=now_tr($now)->format('Y-m-d');$result['serverNow']=now_tr($now)->format(DateTimeInterface::ATOM);
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
    case 'complete':
        find_index($state['users'],$in['userId']??null,'Kullanıcı');
        $i=find_index($state['assignments'],$in['assignmentId']??null,'Atanmış görev');$a=$state['assignments'][$i];
        if($a['userId']!==$in['userId'])throw new AppError('Bu görev başka bir profile ait.',403);
        $status=assignment_status($a,$now);
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
    if(empty($u['goalRewardId']))throw new AppError('Önce bir hedef ödül seç, sonra çarkı çevir.',409);
    $goal=$state['rewards'][find_index($state['rewards'],$u['goalRewardId'],'Hedef ödül')];
    $list=available_tasks($state,$userId,$frequency,$now);
    if(!$list)throw new AppError('Bu dönemde seçilebilecek görev kalmadı. Aktif görevlerini tamamla veya başka bir dönem seç.',409);
    $task=$list[random_int(0,count($list)-1)];
    $a=['id'=>uid(),'requestId'=>$requestId,'userId'=>$userId,'taskId'=>$task['id'],'title'=>$task['title'],'description'=>$task['description'],'icon'=>$task['icon'],'points'=>$task['points'],'frequency'=>$task['frequency'],'periodStart'=>period_start($task['frequency'],$now),'assignedAt'=>now_tr($now)->format(DateTimeInterface::ATOM),'dueAt'=>deadline($task['frequency'],$now)->format(DateTimeInterface::ATOM),'status'=>'active','goalRewardId'=>$goal['id'],'goalTitle'=>$goal['title']];
    $state['assignments'][]=$a;
    return ['task'=>$task,'assignment'=>$a,'candidates'=>$list];
}
