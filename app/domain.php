<?php
declare(strict_types=1);
final class AppError extends RuntimeException {
    public function __construct(string $message, public int $status = 400) { parent::__construct($message); }
}
function initial_state(): array { return json_decode(file_get_contents(__DIR__.'/seed.json'), true, 512, JSON_THROW_ON_ERROR); }
function now_tr(?DateTimeImmutable $now = null): DateTimeImmutable { return ($now ?? new DateTimeImmutable())->setTimezone(new DateTimeZone('Europe/Istanbul')); }
function period_start(string $frequency, ?DateTimeImmutable $now = null): string {
    $d = now_tr($now);
    return match ($frequency) { 'monthly' => $d->format('Y-m-01'), 'weekly' => $d->modify('-'.((int)$d->format('N')-1).' days')->format('Y-m-d'), default => $d->format('Y-m-d') };
}
function is_done(array $state, string $userId, array $task, ?DateTimeImmutable $now = null): bool {
    $start=period_start($task['frequency'],$now); $today=now_tr($now)->format('Y-m-d');
    foreach($state['completions'] as $c) if($c['userId']===$userId && $c['taskId']===$task['id'] && $c['day']>=$start && $c['day']<=$today) return true;
    return false;
}
function earned(array $state, string $id): int { return array_sum(array_column(array_filter($state['completions'],fn($c)=>$c['userId']===$id),'points')); }
function balance(array $state, string $id): int { return earned($state,$id)-array_sum(array_column(array_filter($state['redemptions'],fn($c)=>$c['userId']===$id),'cost')); }
function snapshot(array $state, ?DateTimeImmutable $now=null): array {
    $result=$state; $result['today']=now_tr($now)->format('Y-m-d');
    foreach($result['users'] as &$u) { $u['earned']=earned($state,$u['id']); $u['balance']=balance($state,$u['id']); $u['doneIds']=array_values(array_column(array_filter($state['tasks'],fn($t)=>is_done($state,$u['id'],$t,$now)),'id')); } unset($u);
    return $result;
}
function valid_text(mixed $v,string $label,int $max,bool $required=true): string {
    if(!is_string($v)||mb_strlen(trim($v))>$max||($required&&trim($v)==='')) throw new AppError($label.' geçerli değil.'); return trim($v);
}
function valid_points(mixed $v): int { if(!is_int($v)||$v<1||$v>100000) throw new AppError('Puan 1–100.000 arasında tam sayı olmalı.');return $v; }
function find_index(array $items,mixed $id,string $label): int { foreach($items as $i=>$item) if($item['id']===$id)return $i; throw new AppError($label.' bulunamadı.',404); }
function uid(): string { return bin2hex(random_bytes(16)); }
function mutate(array &$state,string $action,array $in,?DateTimeImmutable $now=null): void {
    $at=now_tr($now)->format(DateTimeInterface::ATOM);
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
    case 'complete':
        find_index($state['users'],$in['userId']??null,'Kullanıcı');$task=$state['tasks'][find_index($state['tasks'],$in['taskId']??null,'Görev')];
        if(is_done($state,$in['userId'],$task,$now))throw new AppError('Bu görev bu dönemde zaten tamamlandı.',409);
        $state['completions'][]=['id'=>uid(),'userId'=>$in['userId'],'taskId'=>$task['id'],'title'=>$task['title'],'icon'=>$task['icon'],'points'=>$task['points'],'frequency'=>$task['frequency'],'day'=>now_tr($now)->format('Y-m-d'),'at'=>$at];break;
    case 'redeem':
        find_index($state['users'],$in['userId']??null,'Kullanıcı');$reward=$state['rewards'][find_index($state['rewards'],$in['rewardId']??null,'Ödül')];
        if(!is_string($in['requestId']??null)||!preg_match('/^[a-zA-Z0-9-]{16,64}$/',$in['requestId']))throw new AppError('İşlem kimliği geçersiz.');
        foreach($state['redemptions'] as $r)if($r['requestId']===$in['requestId'])throw new AppError('Bu işlem zaten kaydedildi.',409);
        if(balance($state,$in['userId'])<$reward['cost'])throw new AppError('Bu ödül için henüz yeterli puanın yok.',409);
        $state['redemptions'][]=['id'=>uid(),'requestId'=>$in['requestId'],'userId'=>$in['userId'],'rewardId'=>$reward['id'],'title'=>$reward['title'],'icon'=>$reward['icon'],'cost'=>$reward['cost'],'at'=>$at];break;
    default:throw new AppError('Bilinmeyen işlem.',404);
    }
}
function spin(array $state,string $userId,?DateTimeImmutable $now=null): array {
    find_index($state['users'],$userId,'Kullanıcı');$list=array_values(array_filter($state['tasks'],fn($t)=>!is_done($state,$userId,$t,$now)));
    if(!$list)throw new AppError('Harika! Tüm görevler tamamlandı. Yeni bir görev ekleyebilirsin.',409);
    return ['task'=>$list[random_int(0,count($list)-1)],'candidates'=>$list];
}
