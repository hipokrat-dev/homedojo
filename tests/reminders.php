<?php
require __DIR__.'/../app/reminders.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
$time=new DateTimeImmutable('2026-12-01 09:00:00',new DateTimeZone('Europe/Istanbul'));
$plan=['id'=>'p1','kind'=>'todo','title'=>'Bir iş','day'=>'2026-12-01','time'=>'09:00','reminder'=>true,'done'=>false];
$user=['id'=>'u1','memberType'=>'parent','personalPlan'=>[$plan]];
$s=['users'=>[$user]];
check(count(due_plan_reminders($s,$time->getTimestamp()))===1,'Türkiye timezone due');
check(count(due_plan_reminders($s,$time->getTimestamp()-1))===0,'Not early');
check(count(due_plan_reminders($s,$time->getTimestamp()+300))===1,'Brief retry window');
check(count(due_plan_reminders($s,$time->getTimestamp()+301))===0,'No stale notifications');
foreach([['done'=>true],['reminder'=>false],['time'=>''],['day'=>'2026-02-30'],['kind'=>'lesson']] as $change){$s['users'][0]['personalPlan']=[array_replace($plan,$change)];check(due_plan_reminders($s,$time->getTimestamp())===[],'Skip disabled, invalid or completed plans');}
$s=['users'=>[array_replace($user,['archivedAt'=>'2026-11-30'])]];check(due_plan_reminders($s,$time->getTimestamp())===[],'Archived user excluded');
echo "Agenda reminders: timezone, due time, bounded retry, completion, consent and archived accounts passed.\n";
