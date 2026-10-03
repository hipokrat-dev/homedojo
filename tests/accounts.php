<?php
require __DIR__.'/../app/domain.php';require __DIR__.'/../app/accounts.php';
$count=0;
function check($condition,$label){global $count;if(!$condition)throw new RuntimeException($label);$count++;}
function denied($fn,$status){try{$fn();}catch(AppError $e){check($e->status===$status,'Expected '.$status.' got '.$e->status);return;}throw new RuntimeException('Expected rejection');}
$s=initial_state();$in=['adminId'=>'u1','accounts'=>[]];
foreach($s['users'] as $u)$in['accounts'][]=['id'=>$u['id'],'name'=>$u['name'],'username'=>'user'.$u['id'],'password'=>'fixture-password-123'];
setup_accounts($s,$in);check($s['users'][0]['name']==='Baba'&&$s['users'][0]['role']==='admin','Baba is admin');
check(count(array_filter($s['users'],fn($u)=>$u['role']==='admin'))===1,'Exactly one admin');
denied(function()use(&$s,$in){setup_accounts($s,$in);},409);
$session=['userId'=>'u1','authVersion'=>1,'expires'=>time()+600];check(actor_for($s,$session)!==null,'Valid actor');
check(actor_for($s,['authenticated'=>true,'expires'=>time()+600])===null,'Legacy session cannot impersonate');
$input=['userId'=>'u2'];denied(function()use($s,&$input){authorize_action($s,$s['users'][0],'spin',$input);},403);
foreach(['saveTask','saveReward','saveUser','saveAccount','createCompetition','claimCompetition'] as $a){$input=[];denied(function()use($s,$a,&$input){authorize_action($s,$s['users'][1],$a,$input);},403);}
$t=new DateTimeImmutable('2026-10-03T12:00:00+03:00');mutate($s,'selectGoal',['userId'=>'u1','rewardId'=>'r1'],$t);$a=spin($s,'u1','daily',uid(),$t)['assignment'];
denied(function()use(&$s,$a,$t){mutate($s,'complete',['userId'=>'u1','assignmentId'=>$a['id'],'reviewerId'=>'u1'],$t);},403);
mutate($s,'complete',['userId'=>'u1','assignmentId'=>$a['id'],'reviewerId'=>'u2'],$t);
check(balance($s,'u1')===0,'Submission awards no points');check(assignment_status($s['assignments'][0],$t->modify('+3 days'))==='pending','Timely submission survives review delay');
check(!in_array($a['taskId'],array_column(available_tasks($s,'u1','all',$t->modify('+3 days')),'id')),'Pending task not redrawn');
$v=member_snapshot($s,$s['users'][2]);check(count($v['assignments'])===0,'Uninvolved member cannot inspect assignment');check(!str_contains(json_encode($v),'passwordHash'),'No password hash leaves state API');
check(count(member_snapshot($s,$s['users'][1])['assignments'])===1,'Selected reviewer sees request');
denied(function()use(&$s,$a,$t){mutate($s,'reviewAssignment',['actorId'=>'u3','assignmentId'=>$a['id'],'decision'=>'approve'],$t);},403);
mutate($s,'reviewAssignment',['actorId'=>'u2','assignmentId'=>$a['id'],'decision'=>'approve'],$t->modify('+3 days'));
check(balance($s,'u1')===$a['points'],'Approval after deadline awards frozen points');check($s['completions'][0]['at']===$a['assignedAt'],'Uses submission timestamp');
denied(function()use(&$s,$a,$t){mutate($s,'reviewAssignment',['actorId'=>'u2','assignmentId'=>$a['id'],'decision'=>'approve'],$t);},409);
$b=spin($s,'u1','daily',uid(),$t)['assignment'];mutate($s,'complete',['userId'=>'u1','assignmentId'=>$b['id'],'reviewerId'=>'u3'],$t);mutate($s,'reviewAssignment',['actorId'=>'u3','assignmentId'=>$b['id'],'decision'=>'reject'],$t);
check(assignment_status($s['assignments'][1],$t)==='active','Rejected task reopens');
mutate($s,'complete',['userId'=>'u1','assignmentId'=>$b['id'],'reviewerId'=>'u4'],$t);mutate($s,'reviewAssignment',['actorId'=>'u4','assignmentId'=>$b['id'],'decision'=>'reject'],$t->modify('+3 days'));
check(assignment_status($s['assignments'][1],$t->modify('+3 days'))==='expired','Rejection after deadline cannot extend time');
save_account($s,['id'=>'u1','username'=>'baba','name'=>'Baba','avatar'=>'🌻','password'=>'fixture-new-password']);check(actor_for($s,$session)===null,'Password change revokes existing sessions');
echo "✓ $count account, privacy, role and approval checks passed.\n";

check(account_password('1234')==='1234','Four-digit player password accepted');
check(account_password('şğüı')==='şğüı','Four Turkish characters accepted');
try{account_password('123');throw new RuntimeException('Three-character password accepted');}catch(AppError $e){check($e->status===400,'Too short password rejected');}
