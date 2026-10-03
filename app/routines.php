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
        $r['status']=$now<$start?'upcoming':'active';
        foreach($s['assignments'] as $a)if($a['userId']===$u['id']&&($a['routineId']??null)===$r['id']&&($a['routineDay']??null)===$day){
            // Keep already submitted task content and deadlines stable if the admin edits the schedule.
            foreach(['title','icon','points','dueAt'] as $key)$r[$key]=$a[$key];
            $r['time']=$a['routineTime']??$r['time'];$r['status']=assignment_status($a,$now);$r['assignmentId']=$a['id'];break;
        }
        $r['late']=$now>=new DateTimeImmutable($r['dueAt']);
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
    $s['assignments'][$i]=array_merge($s['assignments'][$i],['status'=>'pending','submittedAt'=>$at,'submittedLate'=>$now>=new DateTimeImmutable($r['dueAt']),'reviewerId'=>$guardian['id']]);
}

function parent_daily_routines(array $s,array $actor,?DateTimeImmutable $now=null): array {
    if(($actor['memberType']??'')!=='parent')return [];
    $out=[];foreach($s['users'] as $child)if(empty($child['archivedAt'])&&has_daily_program($child)){
        $out[]=['id'=>$child['id'],'name'=>$child['name'],'avatar'=>$child['avatar'],'photo'=>$child['photo']??null,'tasks'=>daily_routines($s,$child,$now)];
    }
    return $out;
}
function parent_approve_routine(array &$s,array $actor,array $in,?DateTimeImmutable $now=null): void {
    $parent=$s['users'][active_user_index($s,$actor['id'])];
    if(($parent['memberType']??'')!=='parent')throw new AppError('Bu işlem yalnızca ebeveyn hesabına açık.',403);
    $child=$s['users'][active_user_index($s,$in['childId']??null,'Çocuk')];
    if(!has_daily_program($child)||$child['id']===$parent['id'])throw new AppError('Bir çocuk görevi seç.',403);
    $now=now_tr($now);if(($in['day']??null)!==$now->format('Y-m-d'))throw new AppError('Gün değişti. Sayfayı yenile.',409);
    $tasks=daily_routines($s,$child,$now);$task=$tasks[find_index($tasks,$in['routineId']??null,'Günlük görev')];
    if($task['status']==='completed')return;
    if(!in_array($task['status'],['active','pending'],true))throw new AppError('Bu görev henüz onaylanamaz.',409);
    complete_routine($s,$child,['day'=>$in['day'],'routineId'=>$task['id']],$now);
    foreach($s['assignments'] as $i=>$a)if($a['userId']===$child['id']&&($a['routineDay']??null)===$in['day']&&($a['routineId']??null)===$task['id']){
        $s['assignments'][$i]['observedBy']=$parent['id'];$s['assignments'][$i]['observedAt']=$now->format(DateTimeInterface::ATOM);
        $s['assignments'][$i]['originalReviewerId'] ??= $a['reviewerId'];$s['assignments'][$i]['reviewerId']=$parent['id'];
        mutate($s,'reviewAssignment',['assignmentId'=>$a['id'],'actorId'=>$parent['id'],'decision'=>'approve'],$now);return;
    }
}

function parent_approve_assignment(array &$s,array $actor,array $in,?DateTimeImmutable $now=null): void {
    $p=$s['users'][active_user_index($s,$actor['id'])];if(($p['memberType']??'')!=='parent')throw new AppError('Yalnızca ebeveyn onaylayabilir.',403);
    $i=find_index($s['assignments'],$in['assignmentId']??null,'Görev');$a=$s['assignments'][$i];
    $child=$s['users'][active_user_index($s,$a['userId'],'Çocuk')];if(!has_daily_program($child))throw new AppError('Bir çocuk görevi seç.',403);
    if(isset($a['routineId']))throw new AppError('Günlük görev panelini kullan.',400);
    if($a['status']==='completed')return;
    if(assignment_status($a,$now)==='active')mutate($s,'complete',['assignmentId'=>$a['id'],'userId'=>$child['id'],'reviewerId'=>$p['id']],$now);
    elseif($a['status']!=='pending')throw new AppError('Bu görev onaylanamaz.',409);
    $s['assignments'][$i]['originalReviewerId'] ??= $s['assignments'][$i]['reviewerId'];
    $s['assignments'][$i]['reviewerId']=$p['id'];$s['assignments'][$i]['observedBy']=$p['id'];$s['assignments'][$i]['observedAt']=now_tr($now)->format(DateTimeInterface::ATOM);
    mutate($s,'reviewAssignment',['assignmentId'=>$a['id'],'actorId'=>$p['id'],'decision'=>'approve'],$now);
}
