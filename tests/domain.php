<?php
declare(strict_types=1);
require __DIR__.'/../app/domain.php';
$passed=0;
function ok(bool $condition,string $name):void{global $passed;if(!$condition)throw new RuntimeException('FAIL: '.$name);$passed++;echo "✓ $name\n";}
function rejects(callable $fn,int $status,string $name):void{try{$fn();}catch(AppError $e){ok($e->status===$status,$name);return;}throw new RuntimeException('FAIL: '.$name);}
$s=initial_state();$friday=new DateTimeImmutable('2026-10-02T12:00:00+03:00');
ok(count($s['users'])===4,'Exactly four profiles');
mutate($s,'complete',['userId'=>'u1','taskId'=>'t1','points'=>999999],$friday);
ok(balance($s,'u1')===20,'Server owns task points, ignores client points');
ok(balance($s,'u2')===0,'User balances are independent');
rejects(function()use(&$s,$friday){mutate($s,'complete',['userId'=>'u1','taskId'=>'t1'],$friday);},409,'Duplicate completion rejected');
mutate($s,'complete',['userId'=>'u1','taskId'=>'t1'],new DateTimeImmutable('2026-10-02T21:00:01Z'));
ok(balance($s,'u1')===40,'Daily task resets at Istanbul midnight');
mutate($s,'complete',['userId'=>'u1','taskId'=>'t4'],$friday);
rejects(function()use(&$s){mutate($s,'complete',['userId'=>'u1','taskId'=>'t4'],new DateTimeImmutable('2026-10-04T23:59:59+03:00'));},409,'Weekly completion stays locked Sunday');
mutate($s,'complete',['userId'=>'u1','taskId'=>'t4'],new DateTimeImmutable('2026-10-05T00:00:00+03:00'));
ok(balance($s,'u1')===160,'Weekly task resets Monday');
mutate($s,'complete',['userId'=>'u1','taskId'=>'t6'],$friday);
rejects(function()use(&$s){mutate($s,'complete',['userId'=>'u1','taskId'=>'t6'],new DateTimeImmutable('2026-10-31T12:00:00+03:00'));},409,'Monthly completion stays locked in same month');
mutate($s,'complete',['userId'=>'u1','taskId'=>'t6'],new DateTimeImmutable('2026-11-01T00:00:00+03:00'));
ok(balance($s,'u1')===460,'Monthly task resets on first day');
$before=earned($s,'u1');mutate($s,'redeem',['userId'=>'u1','rewardId'=>'r1','requestId'=>'request-1234567890']);
ok(balance($s,'u1')===360&&earned($s,'u1')===$before,'Redemption spends balance but preserves lifetime score');
rejects(function()use(&$s){mutate($s,'redeem',['userId'=>'u1','rewardId'=>'r1','requestId'=>'request-1234567890']);},409,'Duplicate reward request rejected');
rejects(function()use(&$s){mutate($s,'redeem',['userId'=>'u2','rewardId'=>'r1','requestId'=>'request-1234567891']);},409,'Insufficient balance rejected');
$original=$s['tasks'][0];mutate($s,'saveTask',array_merge($original,['points'=>500]));
ok(earned($s,'u1')===$before,'Editing task points preserves historical awards');
mutate($s,'deleteTask',['id'=>'t1']);ok(earned($s,'u1')===$before,'Deleting task preserves history');
mutate($s,'deleteReward',['id'=>'r1']);ok(balance($s,'u1')===360,'Deleting reward preserves spent points');
foreach([-5,0,1.5,'20',100001]as $v)rejects(function()use(&$s,$original,$v){mutate($s,'saveTask',array_merge($original,['id'=>'t2','points'=>$v]));},400,'Invalid points rejected: '.json_encode($v));
rejects(function()use(&$s){mutate($s,'saveUser',['id'=>'intruder','name'=>'X','avatar'=>'X']);},404,'Cannot create a fifth profile');
ok(period_start('weekly',new DateTimeImmutable('2027-01-01T12:00:00+03:00'))==='2026-12-28','Week boundary works across year');
$t=initial_state();foreach($t['tasks']as $task)mutate($t,'complete',['userId'=>'u1','taskId'=>$task['id']],$friday);
rejects(fn()=>spin($t,'u1',$friday),409,'Wheel handles all tasks completed');
$spin=spin($t,'u2',$friday);ok(count($spin['candidates'])===6,'Wheel candidates belong to selected profile');
$empty=initial_state();$empty['tasks']=[];rejects(fn()=>spin($empty,'u1'),409,'Wheel handles no tasks');
echo "$passed domain checks passed.\n";
