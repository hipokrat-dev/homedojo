<?php
declare(strict_types=1);
function active_user_index(array $s,mixed $id,string $label='Kullanıcı'): int {
    $i=find_index($s['users'],$id,$label);if(!empty($s['users'][$i]['archivedAt']))throw new AppError($label.' artık aktif değil.',409);return $i;
}
function has_daily_program(array $u): bool { return in_array($u['memberType']??'', ['child','young_child'],true); }
function record_routine_program(array &$u,?DateTimeImmutable $now=null): void {
    $day=now_tr($now)->format('Y-m-d');
    $u['routineTrackingSince'] ??= $day;
    $u['routineTrackingAt'] ??= now_tr($now)->format(DateTimeInterface::ATOM);
    $u['routineHistory'] ??= [];
    $schedule=has_daily_program($u)&&empty($u['archivedAt'])?($u['routineSchedule']??default_routines()):[];
    $entry=['day'=>$day,'schedule'=>$schedule];
    $n=count($u['routineHistory']);
    if($n&&$u['routineHistory'][$n-1]['day']===$day)$u['routineHistory'][$n-1]=$entry;else $u['routineHistory'][]=$entry;
}
function default_routines(): array {
    $rows=[['bed','Yatağını topla','🛏️','07:00','12:00'],['breakfast','Kahvaltını yap','🥣','08:00','12:00'],['teeth-am','Dişlerini fırçala','🪥','08:30','12:00'],['play','Oyun zamanı','🧸','12:00','18:00'],['toys','Oyuncaklarını topla','🧺','18:00','23:00'],['pajamas','Pijamalarını giy','🌙','19:30','23:00'],['teeth-pm','Dişlerini fırçala','🪥','20:00','23:00'],['sleep','Yatağa gir','🛌','20:30','23:00']];
    return array_map(fn($r)=>['id'=>$r[0],'title'=>$r[1],'icon'=>$r[2],'time'=>$r[3],'until'=>$r[4],'points'=>10],$rows);
}
function routine_guardian(array $s,array $u): array {
    foreach($s['users'] as $p)if($p['id']===($u['guardianId']??'')&&empty($p['archivedAt'])&&($p['memberType']??'')==='parent'&&$p['id']!==$u['id'])return $p;
    foreach($s['users'] as $p)if(($p['role']??'')==='admin'&&empty($p['archivedAt'])&&$p['id']!==$u['id'])return $p;
    throw new AppError('Admin panelinden bir onaycı ebeveyn seçin.',409);
}
function daily_routines(array $s,array $u,?DateTimeImmutable $now=null): array {
    $now=now_tr($now);$day=$now->format('Y-m-d');$rows=[];
    foreach($u['routineSchedule']??default_routines() as $r){
        $start=new DateTimeImmutable($day.'T'.$r['time'].':00+03:00');$end=new DateTimeImmutable($day.'T'.$r['until'].':00+03:00');
        $r['startsAt']=$start->format(DateTimeInterface::ATOM);$r['dueAt']=$end->format(DateTimeInterface::ATOM);
        $r['status']=$now<$start?'upcoming':($now>=$end?'expired':'active');
        foreach($s['assignments'] as $a)if($a['userId']===$u['id']&&($a['routineId']??null)===$r['id']&&($a['routineDay']??null)===$day){
            // Keep already submitted task content and deadlines stable if the admin edits the schedule.
            foreach(['title','icon','points','dueAt'] as $key)$r[$key]=$a[$key];
            $r['time']=$a['routineTime']??$r['time'];$r['status']=assignment_status($a,$now);$r['assignmentId']=$a['id'];break;
        }
        $rows[]=$r;
    }
    usort($rows,fn($a,$b)=>strcmp($a['time'],$b['time']));return $rows;
}
function save_routines(array &$s,array $in,?DateTimeImmutable $now=null): void {
    $i=active_user_index($s,$in['id']??null);if(!has_daily_program($s['users'][$i]))throw new AppError('Günlük program çocuk profilleri içindir.');
    $rows=$in['routines']??null;if(!is_array($rows)||!array_is_list($rows)||count($rows)>20)throw new AppError('En fazla 20 günlük görev ekleyebilirsin.');
    $out=[];$ids=[];
    foreach($rows as $r){
        if(!is_array($r))throw new AppError('Görev bilgisi geçersiz.');
        $id=$r['id']??uid();if(!is_string($id)||!preg_match('/^[a-zA-Z0-9-]{1,64}$/',$id)||isset($ids[$id]))throw new AppError('Görev kimliği geçersiz.');$ids[$id]=true;
        foreach(['time','until'] as $k)if(!is_string($r[$k]??null)||!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/',$r[$k]))throw new AppError('Saat HH:MM biçiminde olmalı.');
        if($r['time']>=$r['until'])throw new AppError('Bitiş saati başlangıçtan sonra olmalı.');
        $out[]=['id'=>$id,'title'=>valid_text($r['title']??null,'Görev',80),'icon'=>valid_text($r['icon']??'✨','Simge',12),'time'=>$r['time'],'until'=>$r['until'],'points'=>valid_points($r['points']??null)];
    }
    $s['users'][$i]['routineSchedule']=$out;record_routine_program($s['users'][$i],$now);
}
function complete_routine(array &$s,array $actor,array $in,?DateTimeImmutable $now=null): void {
    if(!has_daily_program($actor))throw new AppError('Bu ekran çocuk hesabı içindir.',403);
    if(isset($in['userId'])&&$in['userId']!==$actor['id'])throw new AppError('Yalnızca kendi görevini tamamlayabilirsin.',403);
    $u=$s['users'][active_user_index($s,$actor['id'])];$now=now_tr($now);$day=$now->format('Y-m-d');
    if(($in['day']??null)!==$day)throw new AppError('Yeni bir güne geçtik. Günlük görevlerini yenile.',409);
    $rows=daily_routines($s,$u,$now);$r=$rows[find_index($rows,$in['routineId']??null,'Günlük görev')];
    if(in_array($r['status'],['pending','completed'],true))return; // Idempotent double taps and retries.
    if($r['status']!=='active')throw new AppError('Bu görevin saati şu anda uygun değil.',409);
    $guardian=routine_guardian($s,$u);$at=$now->format(DateTimeInterface::ATOM);
    if(isset($r['assignmentId']))$i=find_index($s['assignments'],$r['assignmentId'],'Görev');
    else{
        $i=count($s['assignments']);$s['assignments'][]=['id'=>uid(),'requestId'=>'routine-'.uid(),'userId'=>$u['id'],'taskId'=>'routine-'.$r['id'],'routineId'=>$r['id'],'routineDay'=>$day,'routineTime'=>$r['time'],'title'=>$r['title'],'icon'=>$r['icon'],'description'=>'Günlük küçük adım · '.$r['time'],'points'=>$r['points'],'frequency'=>'daily','periodStart'=>$day,'assignedAt'=>$at,'dueAt'=>$r['dueAt'],'goalRewardId'=>null,'goalTitle'=>'Günlük küçük adımlar'];
    }
    $s['assignments'][$i]=array_merge($s['assignments'][$i],['status'=>'pending','submittedAt'=>$at,'reviewerId'=>$guardian['id']]);
}
