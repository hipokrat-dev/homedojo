<?php
declare(strict_types=1);
function reward_wish(array &$s,array $actor,string $action,array $in): void {
    $s['rewardWishes'] ??= [];
    if($action==='requestReward'){
        if(($actor['memberType']??'')!=='child')throw new AppError('Ödül isteğini çocuk hesabından gönder.',403);
        $request=valid_request($in['requestId']??null);
        foreach($s['rewardWishes'] as $w)if($w['childId']===$actor['id']&&$w['requestId']===$request)return;
        $parent=$s['users'][active_user_index($s,$in['parentId']??null,'Ebeveyn')];
        if(($parent['memberType']??'')!=='parent')throw new AppError('İsteğini bir ebeveyne gönder.',403);
        if(count(array_filter($s['rewardWishes'],fn($w)=>$w['childId']===$actor['id']&&$w['status']==='pending'))>=5)throw new AppError('En fazla 5 isteğin onay bekleyebilir.',409);
        $s['rewardWishes'][]=['id'=>uid(),'requestId'=>$request,'childId'=>$actor['id'],'parentId'=>$parent['id'],'title'=>valid_text($in['title']??null,'Hayalindeki ödül',80),'description'=>valid_text($in['description']??'','Not',240,false),'icon'=>valid_text($in['icon']??'🎁','Simge',12),'status'=>'pending','createdAt'=>now_tr()->format(DateTimeInterface::ATOM)];return;
    }
    $i=find_index($s['rewardWishes'],$in['id']??null,'Ödül isteği');$w=$s['rewardWishes'][$i];
    if($action==='cancelRewardRequest'){
        if($w['childId']!==$actor['id'])throw new AppError('Bu istek sana ait değil.',403);
        if($w['status']==='cancelled')return;
        if($w['status']!=='pending')throw new AppError('Yalnızca bekleyen isteği geri çekebilirsin.',409);
        $s['rewardWishes'][$i]['status']='cancelled';$s['rewardWishes'][$i]['resolvedAt']=now_tr()->format(DateTimeInterface::ATOM);return;
    }
    if(($actor['memberType']??'')!=='parent'||($actor['id']!==$w['parentId']&&($actor['role']??'')!=='admin'))throw new AppError('Bu isteği seçilen ebeveyn değerlendirebilir.',403);
    $decision=$in['decision']??'';if(!in_array($decision,['approve','decline'],true))throw new AppError('Geçerli bir karar seç.');
    if($w['status']!=='pending')throw new AppError('Bu istek zaten değerlendirildi.',409);
    $child=active_user_index($s,$w['childId'],'Çocuk');
    if(($s['users'][$child]['memberType']??'')!=='child')throw new AppError('Bu hesap artık çocuk hesabı değil.',409);
    $note=valid_text($in['note']??'','Ebeveyn notu',240,false);$rewardId=null;$cost=null;
    if($decision==='approve'){
        $cost=valid_points($in['cost']??null);
        if(count(array_filter($s['rewards'],fn($r)=>$r['ownerId']===$w['childId']))>=100)throw new AppError('Önce çocuğun ödül listesinden bir ödül kaldırın.',409);
        $rewardId=uid();$s['rewards'][]=['id'=>$rewardId,'ownerId'=>$w['childId'],'title'=>$w['title'],'description'=>$w['description'],'icon'=>$w['icon'],'cost'=>$cost,'wishId'=>$w['id']];
        $s['users'][$child]['goalRewardId']=$rewardId;$s['users'][$child]['goalSelectedAt']=now_tr()->format(DateTimeInterface::ATOM);
    }
    $s['rewardWishes'][$i]=array_merge($w,['status'=>$decision==='approve'?'approved':'declined','cost'=>$cost,'rewardId'=>$rewardId,'note'=>$note,'reviewedBy'=>$actor['id'],'resolvedAt'=>now_tr()->format(DateTimeInterface::ATOM)]);
}
