<?php
require __DIR__.'/../app/domain.php';require __DIR__.'/../app/accounts.php';require __DIR__.'/../app/wishes.php';
$n=0;function check($v,$label){global $n;if(!$v)throw new RuntimeException($label);$n++;}
function denied($fn,$status){try{$fn();}catch(AppError $e){check($e->status===$status,'Expected status '.$status);return;}throw new RuntimeException('Expected denial');}
$s=initial_state();$rows=[];foreach($s['users'] as $i=>$u)$rows[]=['id'=>$u['id'],'name'=>$i===1?'Anne':$u['name'],'username'=>'member'.$i,'password'=>'fixture-password-123'];setup_accounts($s,['adminId'=>'u1','accounts'=>$rows]);[$admin,$parent,$child,$sibling]=$s['users'];
$in=['requestId'=>uid(),'title'=>'Birlikte sinema','description'=>'Hafta sonu','icon'=>'🎬','parentId'=>$parent['id'],'childId'=>$sibling['id'],'cost'=>1];
reward_wish($s,$child,'requestReward',$in);$w=$s['rewardWishes'][0];check($w['childId']===$child['id']&&!isset($w['cost']),'Child identity and price cannot be spoofed');
reward_wish($s,$child,'requestReward',$in);check(count($s['rewardWishes'])===1,'Retry does not duplicate request');
check(count(member_snapshot($s,$parent)['rewardWishes'])===1,'Selected nonadmin parent sees request');check(member_snapshot($s,$sibling)['rewardWishes']===[],'Sibling cannot read requests');
denied(fn()=>reward_wish($s,$parent,'requestReward',$in),403);
denied(fn()=>reward_wish($s,$sibling,'reviewRewardRequest',['id'=>$w['id'],'decision'=>'approve','cost'=>100]),403);
denied(fn()=>reward_wish($s,$parent,'reviewRewardRequest',['id'=>$w['id'],'decision'=>'approve','cost'=>0]),400);
$before=balance($s,$child['id']);reward_wish($s,$parent,'reviewRewardRequest',['id'=>$w['id'],'decision'=>'approve','cost'=>200,'note'=>'Başarabilirsin!']);$w=$s['rewardWishes'][0];
$reward=$s['rewards'][find_index($s['rewards'],$w['rewardId'],'Ödül')];check($reward['ownerId']===$child['id']&&$reward['cost']===200,'Parent creates private reward at chosen price');check($s['users'][2]['goalRewardId']===$reward['id'],'Approved reward becomes active goal');check(balance($s,$child['id'])===$before,'Approval does not mint points');
denied(fn()=>reward_wish($s,$parent,'reviewRewardRequest',['id'=>$w['id'],'decision'=>'approve','cost'=>1]),409);
denied(fn()=>mutate($s,'redeem',['userId'=>$child['id'],'rewardId'=>$reward['id'],'requestId'=>uid()]),409);
$in['requestId']=uid();reward_wish($s,$child,'requestReward',$in);$id=end($s['rewardWishes'])['id'];denied(fn()=>reward_wish($s,$sibling,'cancelRewardRequest',['id'=>$id]),403);reward_wish($s,$child,'cancelRewardRequest',['id'=>$id]);denied(fn()=>reward_wish($s,$parent,'reviewRewardRequest',['id'=>$id,'decision'=>'approve','cost'=>100]),409);
$in['requestId']=uid();reward_wish($s,$child,'requestReward',$in);$id=end($s['rewardWishes'])['id'];$count=count($s['rewards']);reward_wish($s,$parent,'reviewRewardRequest',['id'=>$id,'decision'=>'decline','note'=>'Birlikte başka bir ödül seçelim.']);check(count($s['rewards'])===$count&&end($s['rewardWishes'])['status']==='declined','Decline records response without creating reward');
for($i=0;$i<5;$i++){$in['requestId']=uid();reward_wish($s,$child,'requestReward',$in);}$in['requestId']=uid();denied(fn()=>reward_wish($s,$child,'requestReward',$in),409);
echo "✓ $n reward request permission, privacy, pricing, retry, cancellation and goal checks passed.\n";
