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

// Existing Anne receives the same admin role as Baba during the v9 migration.
$legacy=initial_state();setup_accounts($legacy,['adminId'=>'u1','accounts'=>array_map(fn($u)=>['id'=>$u['id'],'name'=>$u['id']==='u2'?'Anne':$u['name'],'username'=>'migration'.$u['id'],'password'=>'1234'],$legacy['users'])]);$legacy['version']=8;
$before=$legacy;$up=upgrade_state($legacy);$mother=$up['users'][1];
check($mother['role']==='admin'&&$up['users'][0]['role']==='admin','Both existing parents are admins');
check($mother['passwordHash']===$before['users'][1]['passwordHash']&&$mother['authVersion']===$before['users'][1]['authVersion'],'Credentials and sessions remain valid');
foreach(['saveTask','deleteTask','saveReward','deleteReward','saveUser','saveAccount','createAccount','archiveAccount','restoreAccount','saveRoutines','createCompetition','cancelCompetition','claimCompetition'] as $action){$input=[];authorize_action($up,$mother,$action,$input);check(true,'Mother authorized: '.$action);}
$view=member_snapshot($up,$mother);check($view['viewerRole']==='admin'&&count($view['tasks'])===count($up['tasks'])&&count($view['rewards'])===count($up['rewards']),'Mother receives complete admin catalog and UI role');
check($up['users'][2]['role']==='member'&&$up['users'][3]['role']==='member','Children stay members');
check(upgrade_state($up)===$up,'Migration is idempotent');
$childState=$legacy;$childState['users'][1]['memberType']='child';check(upgrade_state($childState)['users'][1]['role']==='member','Child profile cannot gain admin');
require_once __DIR__.'/../app/store.php';$store=new Store(['environment'=>'development','driver'=>'sqlite','sqlite_path'=>':memory:']);$store->initialize();$store->db->prepare('UPDATE homedojo_state SET payload=? WHERE id=1')->execute([json_encode($legacy)]);$store->read();$saved=json_decode($store->db->query('SELECT payload FROM homedojo_state WHERE id=1')->fetchColumn(),true);check($saved['version']===9&&$saved['users'][1]['role']==='admin','First read durably promotes existing mother');
$session=['userId'=>'u2','authVersion'=>$mother['authVersion'],'expires'=>time()+600];check(actor_for($saved,$session)['role']==='admin','Existing mother session immediately has admin access');
echo "Mother admin migration and permissions passed.\n";
