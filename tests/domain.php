<?php
declare(strict_types=1);
require __DIR__.'/../app/domain.php';
function complete_approved(array &$s,string $action,array $in,?DateTimeImmutable $now=null):void {
 $in['reviewerId']=($in['userId']??'')==='u2'?'u1':'u2';mutate($s,$action,$in,$now);
 mutate($s,'reviewAssignment',['assignmentId'=>$in['assignmentId'],'actorId'=>$in['reviewerId'],'decision'=>'approve'],$now);
}
$passed=0;
function ok(bool $condition,string $name):void{global $passed;if(!$condition)throw new RuntimeException('FAIL: '.$name);$passed++;echo "✓ $name\n";}
function rejects(callable $fn,int $status,string $name):void{try{$fn();}catch(AppError $e){ok($e->status===$status,$name);return;}throw new RuntimeException('FAIL: '.$name);}
function goal(array &$s,string $user='u1'):void{mutate($s,'selectGoal',['userId'=>$user,'rewardId'=>array_values(array_filter($s['rewards'],fn($r)=>$r['ownerId']===$user))[0]['id']]);}
$friday=new DateTimeImmutable('2026-10-02T12:00:00+03:00');$s=initial_state();
ok(count($s['users'])===4&&count($s['tasks'])===40,'Four profiles with ten independent example tasks each');
$withoutGoal=$s;$simple=spin($withoutGoal,'u1','all',uid(),$friday,true);ok($simple['assignment']['goalRewardId']===null,'Wheel works without a reward target');ok(assignment_status($simple['assignment'],$friday->modify('+40 days'))==='active','New untimed wheel task stays active');
goal($s);$draw=spin($s,'u1','all','draw-request-123456',$friday);$a=$draw['assignment'];
ok(count($draw['candidates'])===10&&count($s['assignments'])===1,'Draw persists an assignment from ten candidates');
ok(snapshot($s,$friday)['users'][0]['goal']['id']==='r1','Selected target is persisted');
$retry=spin($s,'u1','all','draw-request-123456',$friday);
ok($retry['assignment']['id']===$a['id']&&count($s['assignments'])===1,'Retry returns original assignment without another draw');
rejects(function()use(&$s,$friday){spin($s,'u2','all','draw-request-123456',$friday);},409,'Another profile cannot reuse a draw request');
ok(count(available_tasks($s,'u1','all',$friday))===9&&count(available_tasks($s,'u2','all',$friday))===10,'Assigned task is removed only from its owner wheel');
rejects(function()use(&$s,$a,$friday){complete_approved($s,'complete',['userId'=>'u2','assignmentId'=>$a['id']],$friday);},403,'Cannot complete another profile assignment');
rejects(function()use(&$s,$a,$friday){complete_approved($s,'complete',['userId'=>'u1','taskId'=>$a['taskId']],$friday);},404,'Cannot bypass wheel with a template id');
$original=$draw['task'];mutate($s,'saveTask',array_merge($original,['points'=>99999,'title'=>'Changed task','frequency'=>'monthly']),$friday);
mutate($s,'deleteTask',['id'=>$a['taskId']],$friday);
complete_approved($s,'complete',['userId'=>'u1','assignmentId'=>$a['id'],'points'=>999999],$friday);
ok(balance($s,'u1')===$a['points']&&balance($s,'u2')===0,'Frozen points survive template edits and deletion; client points ignored');
ok($s['completions'][0]['title']===$a['title'],'Historical title preserved');
rejects(function()use(&$s,$a,$friday){complete_approved($s,'complete',['userId'=>'u1','assignmentId'=>$a['id']],$friday);},409,'Duplicate completion cannot award twice');
ok(assignment_status($s['assignments'][0],$friday->modify('+2 months'))==='completed','Completed tasks never become expired');
foreach(['daily'=>'2026-10-03T12:00:00+03:00','weekly'=>'2026-10-09T12:00:00+03:00','monthly'=>'2026-11-02T12:00:00+03:00']as $frequency=>$end){
 $t=initial_state();$t['tasks']=array_values(array_filter($t['tasks'],fn($x)=>$x['frequency']===$frequency));$t['tasks']=[$t['tasks'][0]];goal($t);
 $d=spin($t,'u1',$frequency,uid(),$friday);$a=$d['assignment'];$boundary=new DateTimeImmutable($end);
 ok($a['dueAt']===$end,"$frequency deadline relative to assignment time");
 $before=$t;complete_approved($before,'complete',['userId'=>'u1','assignmentId'=>$a['id']],$boundary->modify('-1 second'));
 ok(balance($before,'u1')===$a['points'],"$frequency allows completion one second before deadline");
 rejects(function()use(&$t,$a,$boundary){complete_approved($t,'complete',['userId'=>'u1','assignmentId'=>$a['id']],$boundary);},409,"$frequency rejects completion exactly at deadline");
 ok(balance($t,'u1')===0&&snapshot($t,$boundary)['assignments'][0]['status']==='expired',"$frequency expiration is derived even without scheduled jobs");
 $next=spin($t,'u1',$frequency,uid(),$boundary);ok($next['assignment']['id']!==$a['id']&&count($t['assignments'])===2,"$frequency next period starts a new assignment and keeps history");
 rejects(function()use(&$t,$a,$boundary){complete_approved($t,'complete',['userId'=>'u1','assignmentId'=>$a['id']],$boundary->modify('+1 second'));},409,"$frequency old expired assignment remains blocked after new draw");
 rejects(function()use(&$t,$frequency,$boundary){spin($t,'u1',$frequency,uid(),$boundary);},409,"$frequency cannot draw same task twice in period");
}
ok(deadline('monthly',new DateTimeImmutable('2028-02-29T23:59:59+03:00'))->format('Y-m-d')==='2028-03-29','One calendar month after leap day');
ok(period_start('weekly',new DateTimeImmutable('2027-01-01T12:00:00+03:00'))==='2026-12-28','Week across year boundary');
ok(deadline('daily',new DateTimeImmutable('2026-10-02T21:00:00Z'))->format('Y-m-d')==='2026-10-04','UTC converted to Istanbul before deadline calculation');
$t=initial_state();goal($t);foreach(array_filter($t['tasks'],fn($x)=>$x['ownerId']==='u1') as $_){$d=spin($t,'u1','all',uid(),$friday);complete_approved($t,'complete',['userId'=>'u1','assignmentId'=>$d['assignment']['id']],$friday);}
$earned=earned($t,'u1');ok($earned===array_sum(array_column(array_filter($t['tasks'],fn($x)=>$x['ownerId']==='u1'),'points')),'All ten tasks award their own points');
rejects(function()use(&$t,$friday){spin($t,'u1','all',uid(),$friday);},409,'Wheel handles exhausted pool');
mutate($t,'redeem',['userId'=>'u1','rewardId'=>'r1','requestId'=>'reward-request-12345'],$friday);
ok(balance($t,'u1')===$earned-100&&earned($t,'u1')===$earned,'Reward spends balance without reducing lifetime score');
ok(snapshot($t,$friday)['users'][0]['goal']===null,'Claimed target clears so a new goal can be chosen');
rejects(function()use(&$t){mutate($t,'redeem',['userId'=>'u1','rewardId'=>'r1','requestId'=>'reward-request-12345']);},409,'Reward retry cannot spend twice');
rejects(function()use(&$t){mutate($t,'redeem',['userId'=>'u2','rewardId'=>array_values(array_filter($t['rewards'],fn($r)=>$r['ownerId']==='u2'))[0]['id'],'requestId'=>'reward-request-12346']);},409,'Insufficient funds rejected');
mutate($t,'deleteReward',['id'=>'r1']);ok(balance($t,'u1')===$earned-100,'Deleting reward preserves redeemed history');
$legacy=$t;unset($legacy['assignments']);$legacy['version']=1;$upgraded=upgrade_state($legacy);
ok($upgraded['assignments']===[]&&$upgraded['tasks']===$legacy['tasks']&&$upgraded['users']===$legacy['users']&&balance($upgraded,'u1')===balance($legacy,'u1'),'Version 1 migration preserves all existing data and points');
foreach([-5,0,1.5,'20',100001]as $v)rejects(function()use(&$s,$v){mutate($s,'saveTask',['ownerId'=>'u1','title'=>'Invalid','points'=>$v,'frequency'=>'daily']);},400,'Invalid points: '.json_encode($v));
rejects(function()use(&$s){mutate($s,'saveUser',['id'=>'intruder','name'=>'X','avatar'=>'X']);},404,'saveUser cannot edit an unknown profile');
$t=initial_state();goal($t);$t['tasks']=[];rejects(function()use(&$t){spin($t,'u1','all',uid());},409,'Empty wheel handled');


// Cancellation and relative-duration boundaries.
$t=initial_state();goal($t);$a=spin($t,'u1','daily',uid(),$friday)['assignment'];
rejects(function()use(&$t,$a,$friday){mutate($t,'cancelAssignment',['userId'=>'u2','assignmentId'=>$a['id']],$friday);},403,'Another profile cannot cancel assignment');
mutate($t,'cancelAssignment',['userId'=>'u1','assignmentId'=>$a['id']],$friday);
ok(assignment_status($t['assignments'][0],$friday->modify('+1 month'))==='cancelled','Cancellation remains in history');
rejects(function()use(&$t,$a,$friday){complete_approved($t,'complete',['userId'=>'u1','assignmentId'=>$a['id']],$friday);},409,'Cancelled task cannot earn points');
ok(in_array($a['taskId'],array_column(available_tasks($t,'u1','all',$friday),'id'))&&balance($t,'u1')===0,'Cancelled task returns to wheel without points');
ok(deadline('monthly',new DateTimeImmutable('2027-01-31T15:42:13+03:00'))->format(DateTimeInterface::ATOM)==='2027-02-28T15:42:13+03:00','Month end clamps day and preserves time');
ok(deadline('monthly',new DateTimeImmutable('2028-01-31T15:42:13+03:00'))->format('Y-m-d')==='2028-02-29','Month end respects leap year');
$t=initial_state();goal($t);$a=spin($t,'u1','daily',uid(),$friday)['assignment'];complete_approved($t,'complete',['userId'=>'u1','assignmentId'=>$a['id']],$friday);
ok(!in_array($a['taskId'],array_column(available_tasks($t,'u1','all',new DateTimeImmutable('2026-10-03T01:00:00+03:00')),'id')),'Midnight does not reset completed rolling-duration task');
// Competitions count only new on-time completions, even with identical second timestamps.
mutate($t,'createCompetition',['title'=>'Family week','prize'=>'Picnic','target'=>1,'frequency'=>'weekly'],$friday);
$c=$t['competitions'][0];ok(competition_snapshot($t,$c,$friday)['total']===0,'Competition excludes points earned before creation in same second');
rejects(function()use(&$t,$friday){mutate($t,'createCompetition',['title'=>'Other','prize'=>'Other','target'=>1,'frequency'=>'daily'],$friday);},409,'Only one ongoing family competition');
$a=spin($t,'u2','daily',uid(),$friday)['assignment'];ok($a['goalTitle']==='Picnic','Shared prize permits spinning without personal goal');
rejects(function()use(&$t,$c,$friday){mutate($t,'claimCompetition',['id'=>$c['id']],$friday);},409,'Shared prize cannot be claimed before target');
complete_approved($t,'complete',['userId'=>'u2','assignmentId'=>$a['id']],$friday);
$view=competition_snapshot($t,$c,$friday);ok($view['total']===$a['points']&&$view['leaderboard'][0]['userId']==='u2'&&$view['status']==='achieved','New completion counts once for family and contributor ranking');
$before=balance($t,'u2');mutate($t,'claimCompetition',['id'=>$c['id']],$friday);
ok(balance($t,'u2')===$before&&competition_snapshot($t,$t['competitions'][0],$friday)['status']==='claimed','Shared prize preserves personal balance');
rejects(function()use(&$t,$c,$friday){mutate($t,'claimCompetition',['id'=>$c['id']],$friday);},409,'Shared reward cannot be claimed twice');
mutate($t,'createCompetition',['title'=>'Tomorrow','prize'=>'Trip','target'=>1000,'frequency'=>'daily'],$friday);$c=$t['competitions'][1];
$a=spin($t,'u3','monthly',uid(),$friday)['assignment'];$end=deadline('daily',$friday);complete_approved($t,'complete',['userId'=>'u3','assignmentId'=>$a['id']],$end);
ok(competition_snapshot($t,$c,$end)['total']===0&&competition_snapshot($t,$c,$end)['status']==='expired','Completion at contest deadline earns personal points but no contest points');
mutate($t,'createCompetition',['title'=>'Cancelled','prize'=>'Trip','target'=>1000,'frequency'=>'weekly'],$end);$c=$t['competitions'][2];mutate($t,'cancelCompetition',['id'=>$c['id']],$end);
ok(competition_snapshot($t,$t['competitions'][2],$end)['status']==='cancelled','Cancelled contest history retained');
rejects(function()use(&$t,$c,$end){mutate($t,'claimCompetition',['id'=>$c['id']],$end);},409,'Cancelled contest cannot award prize');
$old=initial_state();unset($old['competitions']);$old['version']=2;$up=upgrade_state($old);ok($up['competitions']===[]&&$up['users']===$old['users']&&$up['tasks']===$old['tasks'],'Version 3 migration preserves prior records');
echo "$passed domain checks passed.\n";
