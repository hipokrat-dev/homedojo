<?php
declare(strict_types=1);
function progress_counts(array $occurrences): array {
    $counts=['total'=>count($occurrences),'completed'=>0,'pending'=>0,'active'=>0,'expired'=>0,'cancelled'=>0];
    foreach($occurrences as $o)if(isset($counts[$o['status']]))$counts[$o['status']]++;
    $counts['rate']=$counts['total']?round($counts['completed']/$counts['total']*100,1):null;return $counts;
}
function routine_program_on(array $u,string $day): array {
    $schedule=[];foreach($u['routineHistory']??[] as $revision)if($revision['day']<=$day)$schedule=$revision['schedule'];
    return $schedule;
}
function progress_date(mixed $date): DateTimeImmutable {
    if(!is_string($date)||!preg_match('/^(20[0-9]{2}|2100)-[0-9]{2}-[0-9]{2}$/',$date))throw new AppError('Geçerli bir tarih seç.');
    $d=DateTimeImmutable::createFromFormat('!Y-m-d',$date,new DateTimeZone('Europe/Istanbul'));
    if(!$d||$d->format('Y-m-d')!==$date)throw new AppError('Geçerli bir tarih seç.');return $d;
}
function child_progress(array $s,array $actor,array $in,?DateTimeImmutable $now=null): array {
    if(($actor['memberType']??'')!=='parent'&&($actor['role']??'')!=='admin')throw new AppError('Bu panel yalnızca ebeveynlere açık.',403);
    $now=now_tr($now);$mode=$in['mode']??'day';$source=$in['source']??'daily';$childId=$in['childId']??'all';
    if(!in_array($mode,['day','week','range'],true)||!in_array($source,['daily','wheel'],true)||!is_string($childId))throw new AppError('Rapor seçimi geçersiz.');
    $selected=progress_date($in['date']??$now->format('Y-m-d'));$start=$mode==='week'?$selected->modify('-'.((int)$selected->format('N')-1).' days'):$selected;$end=$start->modify($mode==='week'?'+6 days':'+0 days');
    if($mode==='range'){$end=progress_date($in['endDate']??null);if($end<$start||$start->diff($end)->days>365)throw new AppError('En fazla bir yıllık geçerli tarih aralığı seç.');}
    $children=array_values(array_filter($s['users'],fn($u)=>has_daily_program($u)));
    if($childId!=='all'&&!in_array($childId,array_column($children,'id'),true))throw new AppError('Çocuk profili bulunamadı.',404);
    $choices=array_map(fn($u)=>array_intersect_key($u,array_flip(['id','name','avatar','photo','memberType','archivedAt'])),$children);
    $rows=[];$all=[];$daily=[];
    for($d=$start;$d<=$end;$d=$d->modify('+1 day'))$daily[$d->format('Y-m-d')]=[];
    foreach($children as $u){
        if($childId!=='all'&&$u['id']!==$childId)continue;$occurrences=[];
        $assignments=array_filter($s['assignments'],fn($a)=>$a['userId']===$u['id']);
        if($source==='daily'){
            foreach(array_keys($daily) as $day){
                if($day>$now->format('Y-m-d'))continue;
                foreach(routine_program_on($u,$day) as $r){
                    $starts=new DateTimeImmutable($day.'T'.$r['time'].':00+03:00');$due=new DateTimeImmutable($day.'T'.$r['until'].':00+03:00');
                    if($starts>$now||isset($u['routineTrackingAt'])&&$due<=new DateTimeImmutable($u['routineTrackingAt']))continue;
                    $occurrences[$day.':'.$r['id']]=['id'=>$r['id'],'day'=>$day,'title'=>$r['title'],'icon'=>$r['icon'],'time'=>$r['time'],'status'=>$day<$now->format('Y-m-d')?'expired':'active'];
                }
            }
            // Frozen submissions remain reportable even if a template is edited or removed.
            foreach($assignments as $a){
                if(!isset($a['routineDay'],$a['routineId']))continue;$day=$a['routineDay'];
                if(!isset($daily[$day])||new DateTimeImmutable($a['assignedAt'])>$now)continue;
                $occurrences[$day.':'.$a['routineId']]=['id'=>$a['routineId'],'day'=>$day,'title'=>$a['title'],'icon'=>$a['icon'],'time'=>$a['routineTime']??$occurrences[$day.':'.$a['routineId']]['time']??'','status'=>assignment_status($a,$now)];
            }
        }else{
            foreach($assignments as $a){
                if(isset($a['routineId']))continue;$at=now_tr(new DateTimeImmutable($a['assignedAt']));$day=$at->format('Y-m-d');
                if(!isset($daily[$day])||$at>$now)continue;
                $occurrences[$a['id']]=['id'=>$a['taskId'],'day'=>$day,'title'=>$a['title'],'icon'=>$a['icon'],'time'=>'','status'=>assignment_status($a,$now)];
            }
        }
        $groups=[];foreach($occurrences as $o){$groups[$o['id']][]=$o;$daily[$o['day']][]=$o;$all[]=$o;}
        $tasks=[];foreach($groups as $id=>$items){$last=end($items);$days=[];foreach(array_keys($daily) as $day)$days[]=['day'=>$day]+progress_counts(array_values(array_filter($items,fn($o)=>$o['day']===$day)));$tasks[]=['id'=>$id,'title'=>$last['title'],'icon'=>$last['icon'],'time'=>$last['time'],'days'=>$days]+progress_counts($items);}
        $rows[]=['userId'=>$u['id'],'name'=>$u['name'],'avatar'=>$u['avatar'],'photo'=>$u['photo']??null,'memberType'=>$u['memberType'],'archived'=>!empty($u['archivedAt']),'trackingSince'=>$u['routineTrackingSince']??null,'tasks'=>$tasks]+progress_counts(array_values($occurrences));
    }
    return ['mode'=>$mode,'source'=>$source,'start'=>$start->format('Y-m-d'),'end'=>$end->format('Y-m-d'),'today'=>$now->format('Y-m-d'),'children'=>$choices,'rows'=>$rows,'summary'=>progress_counts($all),'days'=>array_map(fn($day,$items)=>['day'=>$day]+progress_counts($items),array_keys($daily),array_values($daily)),'serverNow'=>$now->format(DateTimeInterface::ATOM)];
}
