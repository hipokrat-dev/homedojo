<?php
declare(strict_types=1);
require __DIR__.'/../app/domain.php';
$passed=0;
function ok(bool $condition,string $name):void{global $passed;if(!$condition)throw new RuntimeException('FAIL: '.$name);$passed++;echo "✓ $name\n";}
function rejects(callable $fn,int $status,string $name):void{try{$fn();}catch(AppError $e){ok($e->status===$status,$name);return;}throw new RuntimeException('FAIL: '.$name);}
function goal(array &$s,string $user='u1'):void{mutate($s,'selectGoal',['userId'=>$user,'rewardId'=>'r1']);}
$friday=new DateTimeImmutable('2026-10-02T12:00:00+03:00');$s=initial_state();
ok(count($s['users'])===4&&count($s['tasks'])===10,'Four profiles and ten example tasks');
rejects(function()use(&$s,$friday){spin($s,'u1','all',uid(),$friday);},409,'Choose target reward before spinning');
goal($s);$draw=spin($s,'u1','all','draw-request-123456',$friday);$a=$draw['assignment'];
ok(count($draw['candidates'])===10&&count($s['assignments'])===1,'Draw persists an assignment from ten candidates');
ok(snapshot($s,$friday)['users'][0]['goal']['id']==='r1','Selected target is persisted');
$retry=spin($s,'u1','all','draw-request-123456',$friday);
ok($retry['assignment']['id']===$a['id']&&count($s['assignments'])===1,'Retry returns original assignment without another draw');
rejects(function()use(&$s,$friday){spin($s,'u2','all','draw-request-123456',$friday);},409,'Another profile cannot reuse a draw request');
ok(count(available_tasks($s,'u1','all',$friday))===9&&count(available_tasks($s,'u2','all',$friday))===10,'Assigned task is removed only from its owner wheel');
rejects(function()use(&$s,$a,$friday){mutate($s,'complete',['userId'=>'u2','assignmentId'=>$a['id']],$friday);},403,'Cannot complete another profile assignment');
rejects(function()use(&$s,$a,$friday){mutate($s,'complete',['userId'=>'u1','taskId'=>$a['taskId']],$friday);},404,'Cannot bypass wheel with a template id');
$original=$draw['task'];mutate($s,'saveTask',array_merge($original,['points'=>99999,'title'=>'Changed task','frequency'=>'monthly']),$friday);
mutate($s,'deleteTask',['id'=>$a['taskId']],$friday);
mutate($s,'complete',['userId'=>'u1','assignmentId'=>$a['id'],'points'=>999999],$friday);
ok(balance($s,'u1')===$a['points']&&balance($s,'u2')===0,'Frozen points survive template edits and deletion; client points ignored');
ok($s['completions'][0]['title']===$a['title'],'Historical title preserved');
rejects(function()use(&$s,$a,$friday){mutate($s,'complete',['userId'=>'u1','assignmentId'=>$a['id']],$friday);},409,'Duplicate completion cannot award twice');
ok(assignment_status($s['assignments'][0],$friday->modify('+2 months'))==='completed','Completed tasks never become expired');
foreach(['daily'=>'2026-10-03T00:00:00+03:00','weekly'=>'2026-10-05T00:00:00+03:00','monthly'=>'2026-11-01T00:00:00+03:00']as $frequency=>$end){
 $t=initial_state();$t['tasks']=array_values(array_filter($t['tasks'],fn($x)=>$x['frequency']===$frequency));$t['tasks']=[$t['tasks'][0]];goal($t);
 $d=spin($t,'u1',$frequency,uid(),$friday);$a=$d['assignment'];$boundary=new DateTimeImmutable($end);
 ok($a['dueAt']===$end,"$frequency calendar deadline in Istanbul");
 $before=$t;mutate($before,'complete',['userId'=>'u1','assignmentId'=>$a['id']],$boundary->modify('-1 second'));
 ok(balance($before,'u1')===$a['points'],"$frequency allows completion one second before deadline");
 rejects(function()use(&$t,$a,$boundary){mutate($t,'complete',['userId'=>'u1','assignmentId'=>$a['id']],$boundary);},409,"$frequency rejects completion exactly at deadline");
 ok(balance($t,'u1')===0&&snapshot($t,$boundary)['assignments'][0]['status']==='expired',"$frequency expiration is derived even without scheduled jobs");
 $next=spin($t,'u1',$frequency,uid(),$boundary);ok($next['assignment']['id']!==$a['id']&&count($t['assignments'])===2,"$frequency next period starts a new assignment and keeps history");
 rejects(function()use(&$t,$a,$boundary){mutate($t,'complete',['userId'=>'u1','assignmentId'=>$a['id']],$boundary->modify('+1 second'));},409,"$frequency old expired assignment remains blocked after new draw");
 rejects(function()use(&$t,$frequency,$boundary){spin($t,'u1',$frequency,uid(),$boundary);},409,"$frequency cannot draw same task twice in period");
}
ok(deadline('monthly',new DateTimeImmutable('2028-02-29T23:59:59+03:00'))->format('Y-m-d')==='2028-03-01','Leap month deadline');
ok(period_start('weekly',new DateTimeImmutable('2027-01-01T12:00:00+03:00'))==='2026-12-28','Week across year boundary');
ok(deadline('daily',new DateTimeImmutable('2026-10-02T21:00:00Z'))->format('Y-m-d')==='2026-10-04','UTC converted to Istanbul before deadline calculation');
$t=initial_state();goal($t);foreach($t['tasks']as $_){$d=spin($t,'u1','all',uid(),$friday);mutate($t,'complete',['userId'=>'u1','assignmentId'=>$d['assignment']['id']],$friday);}
$earned=earned($t,'u1');ok($earned===array_sum(array_column($t['tasks'],'points')),'All ten tasks award their own points');
rejects(function()use(&$t,$friday){spin($t,'u1','all',uid(),$friday);},409,'Wheel handles exhausted pool');
mutate($t,'redeem',['userId'=>'u1','rewardId'=>'r1','requestId'=>'reward-request-12345'],$friday);
ok(balance($t,'u1')===$earned-100&&earned($t,'u1')===$earned,'Reward spends balance without reducing lifetime score');
ok(snapshot($t,$friday)['users'][0]['goal']===null,'Claimed target clears so a new goal can be chosen');
rejects(function()use(&$t){mutate($t,'redeem',['userId'=>'u1','rewardId'=>'r1','requestId'=>'reward-request-12345']);},409,'Reward retry cannot spend twice');
rejects(function()use(&$t){mutate($t,'redeem',['userId'=>'u2','rewardId'=>'r1','requestId'=>'reward-request-12346']);},409,'Insufficient funds rejected');
mutate($t,'deleteReward',['id'=>'r1']);ok(balance($t,'u1')===$earned-100,'Deleting reward preserves redeemed history');
$legacy=$t;unset($legacy['assignments']);$legacy['version']=1;$upgraded=upgrade_state($legacy);
ok($upgraded['assignments']===[]&&$upgraded['tasks']===$legacy['tasks']&&$upgraded['users']===$legacy['users']&&balance($upgraded,'u1')===balance($legacy,'u1'),'Version 1 migration preserves all existing data and points');
foreach([-5,0,1.5,'20',100001]as $v)rejects(function()use(&$s,$v){mutate($s,'saveTask',['title'=>'Invalid','points'=>$v,'frequency'=>'daily']);},400,'Invalid points: '.json_encode($v));
rejects(function()use(&$s){mutate($s,'saveUser',['id'=>'intruder','name'=>'X','avatar'=>'X']);},404,'Cannot create fifth profile');
$t=initial_state();goal($t);$t['tasks']=[];rejects(function()use(&$t){spin($t,'u1','all',uid());},409,'Empty wheel handled');
echo "$passed domain checks passed.\n";
