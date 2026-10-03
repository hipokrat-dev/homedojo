<?php
declare(strict_types=1);
require __DIR__.'/../app/domain.php';require __DIR__.'/../app/accounts.php';
$n=0;
function verify(bool $ok,string $name):void{global $n;if(!$ok)throw new RuntimeException($name);$n++;}
function blocked(callable $fn,int $status):void{try{$fn();}catch(AppError $e){verify($e->status===$status,'Wrong rejection code');return;}throw new RuntimeException('Expected rejection');}
$old=json_decode(file_get_contents(__DIR__.'/../app/seed.json'),true);$old['version']=4;
$old['users'][1]['goalRewardId']='r1';
$old['assignments']=[['id'=>'frozen-assignment','requestId'=>'legacy-request-123456','userId'=>'u2','taskId'=>'t1','title'=>'Preserved task','description'=>'','icon'=>'✨','frequency'=>'daily','assignedAt'=>'2026-10-03T12:00:00+03:00','dueAt'=>'2026-10-04T12:00:00+03:00','status'=>'pending','submittedAt'=>'2026-10-03T13:00:00+03:00','reviewerId'=>'u1','points'=>75,'goalRewardId'=>'r1','goalTitle'=>'Preserved reward']];
$old['completions']=[['id'=>'legacy-complete','userId'=>'u3','taskId'=>'t1','points'=>45,'day'=>'2026-10-03','at'=>'2026-10-03T10:00:00+03:00','dueAt'=>'2026-10-04T10:00:00+03:00']];
$old['redemptions']=[['id'=>'legacy-reward','userId'=>'u3','rewardId'=>'r1','cost'=>20]];
$s=upgrade_state($old);verify(count($s['tasks'])===40&&count($s['rewards'])===16,'Legacy catalogs split once');verify(upgrade_state($s)===$s,'Migration is idempotent');
verify(balance($s,'u3')===25&&earned($s,'u3')===45,'Balances unchanged');
foreach($s['users'] as $u){verify(count(array_filter($s['tasks'],fn($t)=>$t['ownerId']===$u['id']))===10,'Each user has ten independent tasks');verify(count(array_filter($s['rewards'],fn($r)=>$r['ownerId']===$u['id']))===4,'Each user has four independent rewards');}
$task2=$s['tasks'][find_index($s['tasks'],$s['assignments'][0]['taskId'],'Task')];verify($task2['ownerId']==='u2','Existing task assignment remapped to personal template');
$r2=$s['rewards'][find_index($s['rewards'],$s['users'][1]['goalRewardId'],'Reward')];verify($r2['ownerId']==='u2','Selected reward remains owned');verify($s['assignments'][0]['goalRewardId']===$r2['id'],'Assignment reward reference preserved');
verify($s['assignments'][0]['points']===75&&$s['assignments'][0]['dueAt']===$old['assignments'][0]['dueAt']&&$s['assignments'][0]['reviewerId']==='u1','Frozen points, deadline and pending review unchanged');
$t=new DateTimeImmutable('2026-10-03T14:00:00+03:00');verify(!in_array($task2['id'],array_column(available_tasks($s,'u2','all',$t),'id')),'Pending task remains unavailable');
$t3=$s['tasks'][find_index($s['tasks'],$s['completions'][0]['taskId'],'Task')];verify($t3['ownerId']==='u3'&&is_done($s,'u3',$t3,$t),'Completed cooldown remapped');
mutate($s,'saveTask',array_merge($task2,['points'=>900,'title'=>'Only Anne']),$t);
verify($s['tasks'][find_index($s['tasks'],'t1','Task')]['title']!=='Only Anne','Editing member template leaves others intact');
mutate($s,'reviewAssignment',['actorId'=>'u1','assignmentId'=>'frozen-assignment','decision'=>'approve'],$t);verify(balance($s,'u2')===75,'Pre-migration pending task still approves frozen points');
mutate($s,'saveReward',array_merge($r2,['title'=>'Only Anne reward','cost'=>60]),$t);
verify($s['rewards'][find_index($s['rewards'],'r1','Reward')]['cost']===100,'Reward cost independent');
blocked(function()use(&$s,$r2){mutate($s,'selectGoal',['userId'=>'u1','rewardId'=>$r2['id']]);},403);
blocked(function()use(&$s,$r2){mutate($s,'redeem',['userId'=>'u1','rewardId'=>$r2['id'],'requestId'=>'forged-reward-12345']);},403);
blocked(function()use(&$s){mutate($s,'saveTask',['title'=>'Missing owner','points'=>5,'frequency'=>'daily']);},400);
blocked(function()use(&$s){mutate($s,'saveReward',['ownerId'=>'missing','title'=>'Invalid owner','cost'=>5]);},404);
foreach(['u1','u2','u3','u4'] as $id){$v=member_snapshot($s,['id'=>$id,'role'=>'member']);verify(count($v['tasks'])===10&&count($v['rewards'])===4,'Member catalog sizes');verify(count(array_filter($v['tasks'],fn($r)=>$r['ownerId']!==$id))===0,'No other task templates leak');verify(count(array_filter($v['rewards'],fn($r)=>$r['ownerId']!==$id))===0,'No other reward templates leak');}
verify(count(member_snapshot($s,['id'=>'u1','role'=>'admin'])['tasks'])===40,'Admin can manage all catalogs');
// Reassigning a template removes it from the former owner's future choices, not their history.
mutate($s,'saveReward',array_merge($r2,['ownerId'=>'u4']),$t);verify(snapshot($s,$t)['users'][1]['goal']===null,'Moved reward is no longer an eligible selected goal');
blocked(function()use(&$s,$r2){mutate($s,'redeem',['userId'=>'u2','rewardId'=>$r2['id'],'requestId'=>'moved-reward-123456']);},403);
mutate($s,'saveTask',array_merge($task2,['ownerId'=>'u4']),$t);verify(!in_array($task2['id'],array_column(available_tasks($s,'u2','all',$t),'id')),'Moved task unavailable to prior owner');
// An active family prize never grants access to another member's tasks.
mutate($s,'createCompetition',['title'=>'Shared','prize'=>'Picnic','target'=>500,'frequency'=>'weekly'],$t);$draw=spin($s,'u3','daily',uid(),$t);verify(count(array_filter($draw['candidates'],fn($r)=>$r['ownerId']!=='u3'))===0,'Family spin candidates remain personal');
verify($draw['assignment']['userId']==='u3','Draw belongs to actor');
// Shared templates are single records, with per-person assignments and points.
$family=initial_state();mutate($family,'createCompetition',['title'=>'Family','prize'=>'Picnic','target'=>500,'frequency'=>'weekly'],$t);
mutate($family,'saveTask',['scope'=>'shared','participantIds'=>['u1','u2'],'title'=>'Sofrayı hazırla','points'=>35,'frequency'=>'daily'],$t);
$shared=$family['tasks'][array_key_last($family['tasks'])];$family['tasks']=[$shared];
verify(count(upgrade_state($family)['tasks'])===1,'Shared record never cloned by migration');
verify(count(member_snapshot($family,['id'=>'u1','role'=>'member'])['tasks'])===1,'Shared participant can see task');
verify(count(member_snapshot($family,['id'=>'u3','role'=>'member'])['tasks'])===0,'Excluded member cannot see shared task');
blocked(function()use(&$family,$t){spin($family,'u3','daily',uid(),$t);},409);
$a=spin($family,'u1','daily',uid(),$t)['assignment'];$b=spin($family,'u2','daily',uid(),$t)['assignment'];
verify($a['taskId']===$b['taskId']&&$a['id']!==$b['id'],'Shared template, separate assignments');
mutate($family,'complete',['userId'=>'u1','assignmentId'=>$a['id'],'reviewerId'=>'u2'],$t);
mutate($family,'reviewAssignment',['actorId'=>'u2','assignmentId'=>$a['id'],'decision'=>'approve'],$t);
verify(balance($family,'u1')===35&&balance($family,'u2')===0,'One completion never awards other participant');
verify(assignment_status($family['assignments'][1],$t)==='active','Other participant remains active');
mutate($family,'cancelAssignment',['userId'=>'u2','assignmentId'=>$b['id']],$t);
verify(count(available_tasks($family,'u2','daily',$t))===1&&count(available_tasks($family,'u1','daily',$t))===0,'Cooldown and cancellation independent');
mutate($family,'saveTask',array_merge($shared,['participantIds'=>['u3','u4'],'points'=>90]),$t);
verify(count(available_tasks($family,'u2','daily',$t))===0&&count(available_tasks($family,'u3','daily',$t))===1,'Participant change affects future draws');
verify($family['assignments'][0]['points']===35,'Shared edit preserves frozen points');
foreach([[],['u1'],['u1','u1'],['u1','missing'],'u1,u2',[1,'u2']] as $ids){blocked(function()use(&$family,$ids,$t){mutate($family,'saveTask',['scope'=>'shared','participantIds'=>$ids,'title'=>'Invalid','points'=>10,'frequency'=>'daily'],$t);},$ids===['u1','missing']?404:400);}
mutate($family,'saveTask',array_merge($shared,['scope'=>'personal','ownerId'=>'u4']),$t);
verify(!task_visible_to($family['tasks'][0],'u1')&&task_visible_to($family['tasks'][0],'u4'),'Shared task can become personal');
verify($family['tasks'][0]['participantIds']===[],'Personal conversion clears shared recipients');
echo "✓ $n personal catalog, legacy migration and access checks passed.\n";
