<?php
require __DIR__.'/../app/domain.php';require __DIR__.'/../app/accounts.php';
$count=0;
function check($ok,$label){global $count;if(!$ok)throw new RuntimeException($label);$count++;}
function denied($fn,$status){try{$fn();}catch(AppError $e){check($e->status===$status,'Expected '.$status.', got '.$e->status);return;}throw new RuntimeException('Expected rejection');}
$s=initial_state();$rows=[];foreach($s['users'] as $u)$rows[]=['id'=>$u['id'],'name'=>$u['name'],'username'=>'user'.$u['id'],'password'=>'fixture-password-123'];setup_accounts($s,['adminId'=>'u1','accounts'=>$rows]);$admin=$s['users'][0];
check($admin['memberType']==='parent','Admin defaults to parent');
foreach(['createAccount','archiveAccount','restoreAccount','saveRoutines'] as $action){$in=[];denied(function()use($s,$action,&$in){authorize_action($s,$s['users'][1],$action,$in);},403);}
$input=['name'=>'Minik','username'=>'minik','password'=>'fixture-password-123','avatar'=>'🐣','memberType'=>'young_child','guardianId'=>'u1','role'=>'admin'];create_account($s,$input);$kid=end($s['users']);$id=$kid['id'];
check(count($s['users'])===5,'Fifth member created');check(count($kid['routineSchedule'])===8,'Eight default routines');check($kid['role']==='member','Type does not grant admin');
check(count(array_filter($s['tasks'],fn($t)=>task_visible_to($t,$id)))===0,'New member does not inherit private tasks');
denied(function()use(&$s,$input){create_account($s,$input);},400);
foreach(['spin','complete','reviewAssignment','redeem','selectGoal'] as $action){$in=[];denied(function()use($s,$kid,$action,&$in){authorize_action($s,$kid,$action,$in);},403);}
$at=new DateTimeImmutable('2026-10-03T08:00:00+03:00');$plan=daily_routines($s,$kid,$at);check($plan[0]['status']==='active'&&$plan[1]['status']==='active'&&$plan[2]['status']==='upcoming','Morning availability');
check(daily_routines($s,$kid,new DateTimeImmutable('2026-10-03T05:00:00Z'))===$plan,'Istanbul timezone');
$in=['routineId'=>'bed','day'=>'2026-10-03'];denied(function()use(&$s,$kid,$in,$at){complete_routine($s,$kid,$in+['userId'=>'u2'],$at);},403);
denied(function()use(&$s,$kid,$at){complete_routine($s,$kid,['routineId'=>'sleep','day'=>'2026-10-03'],$at);},409);
$lateState=$s;complete_routine($lateState,$kid,$in,new DateTimeImmutable('2026-10-03T12:00:00+03:00'));$late=end($lateState['assignments']);check($late['submittedLate']===true,'Late routine submission remains available');mutate($lateState,'reviewAssignment',['actorId'=>'u1','assignmentId'=>$late['id'],'decision'=>'approve'],new DateTimeImmutable('2026-10-05T09:00:00+03:00'));check(balance($lateState,$id)===10,'Late daily routine can be approved days later');
denied(function()use(&$s,$kid,$at){complete_routine($s,$kid,['routineId'=>'bed','day'=>'2026-10-02'],$at);},409);
complete_routine($s,$kid,$in+['points'=>99999,'reviewerId'=>'u2'],$at);complete_routine($s,$kid,$in,$at);
check(count($s['assignments'])===1,'Double tap creates one assignment');$a=$s['assignments'][0];check($a['points']===10&&$a['reviewerId']==='u1','Server owns points and guardian');check(balance($s,$id)===0,'Submission gives no points');
check(daily_routines($s,$kid,$at)[0]['status']==='pending','Pending visible to child');
mutate($s,'reviewAssignment',['actorId'=>'u1','assignmentId'=>$a['id'],'decision'=>'reject'],$at);check(daily_routines($s,$kid,$at)[0]['status']==='active','Rejected task can be corrected');
complete_routine($s,$kid,$in,$at->modify('+1 minute'));check(count($s['assignments'])===1,'Resubmission reuses assignment');
mutate($s,'reviewAssignment',['actorId'=>'u1','assignmentId'=>$a['id'],'decision'=>'approve'],$at->modify('+2 days'));
check(balance($s,$id)===10,'Timely work approved later earns points');denied(function()use(&$s,$a,$at){mutate($s,'reviewAssignment',['actorId'=>'u1','assignmentId'=>$a['id'],'decision'=>'approve'],$at);},409);
check(daily_routines($s,$kid,$at)[0]['status']==='completed','Approved card stays complete');check(daily_routines($s,$kid,$at->modify('+1 day'))[0]['status']==='active','Fresh task next day');
$program=$kid['routineSchedule'];$program[1]['points']=40;save_routines($s,['id'=>$id,'routines'=>$program]);$kid=$s['users'][find_index($s['users'],$id,'K')];
complete_routine($s,$kid,['routineId'=>'breakfast','day'=>'2026-10-03'],$at);$program[1]['points']=99;$program[1]['until']='09:00';save_routines($s,['id'=>$id,'routines'=>$program]);$kid=$s['users'][find_index($s['users'],$id,'K')];$frozen=daily_routines($s,$kid,$at)[1];check($frozen['points']===40&&str_contains($frozen['dueAt'],'12:00'),'Editing preserves submitted points and deadlines');
$bad=$program;$bad[0]['time']='25:00';denied(function()use(&$s,$id,$bad){save_routines($s,['id'=>$id,'routines'=>$bad]);},400);
$bad=$program;$bad[0]['until']=$bad[0]['time'];denied(function()use(&$s,$id,$bad){save_routines($s,['id'=>$id,'routines'=>$bad]);},400);
$bad=$program;$bad[1]['id']=$bad[0]['id'];denied(function()use(&$s,$id,$bad){save_routines($s,['id'=>$id,'routines'=>$bad]);},400);
$before=$s['completions'];$session=['userId'=>$id,'authVersion'=>$kid['authVersion'],'expires'=>time()+100];check(actor_for($s,$session)!==null,'Child session active');
archive_account($s,$admin,['id'=>$id]);check(actor_for($s,$session)===null,'Archive revokes login');check($s['completions']===$before,'Archive preserves history');check($s['assignments'][1]['status']==='cancelled','Archive cancels pending own tasks');
$v=member_snapshot($s,$admin);check(count($v['users'])===4&&count($v['archivedUsers'])===1,'Dashboard excludes archived member');check(!str_contains(json_encode($v),'passwordHash'),'No hashes leak');
$v=member_snapshot($s,$s['users'][1]);check($v['archivedUsers']===[],'Nonadmin cannot inspect archived accounts');
restore_account($s,['id'=>$id]);check(actor_for($s,$session)===null,'Restore does not revive old sessions');check(balance($s,$id)===10,'Restore preserves score');
denied(function()use(&$s,$admin){archive_account($s,$admin,['id'=>'u1']);},409);
$parent=$s['users'][1];save_account($s,['id'=>$parent['id'],'username'=>$parent['username'],'name'=>$parent['name'],'avatar'=>$parent['avatar'],'memberType'=>'parent']);
$kid=$s['users'][find_index($s['users'],$id,'K')];save_account($s,['id'=>$id,'username'=>$kid['username'],'name'=>$kid['name'],'avatar'=>$kid['avatar'],'memberType'=>'young_child','guardianId'=>'u2']);
$kid=$s['users'][find_index($s['users'],$id,'K')];complete_routine($s,$kid,['routineId'=>'teeth-am','day'=>'2026-10-03'],$at->modify('+1 hour'));
check(end($s['assignments'])['reviewerId']==='u2','Configured parent receives review');archive_account($s,$admin,['id'=>'u2']);
check(end($s['assignments'])['reviewerId']==='u1','Parent archive reroutes pending review');check($s['users'][find_index($s['users'],$id,'K')]['guardianId']==='u1','Dependent child gets admin guardian');
$session=['userId'=>'u2','authVersion'=>1,'expires'=>time()+100];check(actor_for($s,$session)===null,'Archived parent session rejected');
check(count(array_filter(daily_routines($s,$kid,new DateTimeImmutable('2026-10-03T23:59:00+03:00')),fn($r)=>$r['status']==='active'))>0,'Routines remain available past evening cutoff');
denied(function()use(&$s){restore_account($s,['id'=>'u1']);},409);
$adminEdit=['id'=>'u1','username'=>$admin['username'],'name'=>'Baba','avatar'=>'🌻','memberType'=>'child'];denied(function()use(&$s,$adminEdit){save_account($s,$adminEdit);},400);
$scenario=$s;restore_account($scenario,['id'=>'u2']);$t=new DateTimeImmutable('2026-10-03T09:00:00+03:00');
mutate($scenario,'selectGoal',['userId'=>'u1','rewardId'=>'r1'],$t);$work=spin($scenario,'u1','daily',uid(),$t)['assignment'];mutate($scenario,'complete',['userId'=>'u1','assignmentId'=>$work['id'],'reviewerId'=>'u2'],$t);
$p=$scenario['users'][1];save_account($scenario,['id'=>'u2','name'=>$p['name'],'username'=>$p['username'],'avatar'=>$p['avatar'],'memberType'=>'young_child','guardianId'=>'u1']);
$work=$scenario['assignments'][find_index($scenario['assignments'],$work['id'],'A')];check($work['reviewerId']!=='u1'&&$work['reviewerId']!=='u2','Type change cannot route a review back to its owner');
$view=member_snapshot($scenario,$scenario['users'][2]);check(!isset($view['users'][1]['routineSchedule']),'Other members cannot inspect child schedules');
echo "✓ $count membership, daily schedule, approval and archive checks passed.\n";

// A nonadmin parent can observe and approve both child types, even with another guardian.
$direct=initial_state();$rows=[];foreach($direct['users'] as $j=>$u)$rows[]=['id'=>$u['id'],'name'=>$j===1?'Anne':$u['name'],'username'=>'parenttest'.$j,'password'=>'1234'];setup_accounts($direct,['adminId'=>'u1','accounts'=>$rows]);$mother=$direct['users'][1];$direct['users'][3]['memberType']='young_child';$time=new DateTimeImmutable('2026-10-03T14:00:00+03:00');
check(count(parent_daily_routines($direct,$mother,$time))===2,'Mother sees both children daily plans');check(parent_daily_routines($direct,$direct['users'][2],$time)===[],'Child cannot receive sibling daily plans');
$input=['childId'=>'u3','routineId'=>'bed','day'=>'2026-10-03'];denied(function()use(&$direct,$input,$time){parent_approve_routine($direct,$direct['users'][3],$input,$time);},403);
parent_approve_routine($direct,$mother,$input,$time);parent_approve_routine($direct,$mother,$input,$time);check(balance($direct,'u3')===10&&count($direct['completions'])===1,'Direct parent approval awards once after repeated clicks');check($direct['completions'][0]['approvedBy']==='u2','Actual observing parent recorded');
complete_routine($direct,$direct['users'][3],['routineId'=>'bed','day'=>'2026-10-03'],$time);parent_approve_routine($direct,$mother,['childId'=>'u4','routineId'=>'bed','day'=>'2026-10-03'],$time);check(balance($direct,'u4')===10,'Mother approves a young child pending with another guardian');
parent_approve_routine($direct,$mother,['childId'=>'u3','routineId'=>'sleep','day'=>'2026-10-03'],$time);$early=end($direct['completions']);check($early['submittedEarly']&&$early['at']===$time->format(DateTimeInterface::ATOM),'Parent early approval records actual time');parent_approve_routine($direct,$mother,['childId'=>'u3','routineId'=>'sleep','day'=>'2026-10-03'],$time);check(balance($direct,'u3')===20,'Repeated early approval awards once');
denied(function()use(&$direct,$mother,$time){parent_approve_routine($direct,$mother,['childId'=>'u3','routineId'=>'bed','day'=>'2026-10-02'],$time);},409);
echo "✓ Parent daily overview, observation, role boundaries and duplicate award protection passed.\n";

// Direct parent assignment and permanent per-child pool completion.
$s=initial_state();$s['users'][0]['memberType']='parent';$parent=$s['users'][0];$child=$s['users'][2];$parent['role']='admin';
mutate($s,'saveTask',['scope'=>'shared','participantIds'=>['u3','u4'],'title'=>'Assign once','description'=>'','points'=>17,'icon'=>'✨','frequency'=>'daily']);$task=end($s['tasks']);
$in=['childId'=>'u3','taskId'=>$task['id'],'requestId'=>'direct-parent-test-12345'];
denied(function()use(&$s,$child,$in){parent_assign_task($s,$child,$in);},403);
denied(function()use(&$s,$parent,$in){parent_assign_task($s,$parent,array_replace($in,['childId'=>'u1']));},403);
$assigned=parent_assign_task($s,$parent,$in,$at)['assignment'];
check($assigned['taskId']===$task['id']&&$assigned['assignedBy']==='u1'&&$assigned['noDeadline'],'Parent selects exact untimed task');
check(parent_assign_task($s,$parent,$in,$at)['assignment']['id']===$assigned['id'],'Assignment retry is idempotent');
denied(function()use(&$s,$parent,$in){parent_assign_task($s,$parent,array_replace($in,['requestId'=>'another-assignment-12345']));},409);
parent_approve_assignment($s,$parent,['assignmentId'=>$assigned['id']],$at);
check(!in_array($task['id'],array_column(available_tasks($s,'u3','all',$at->modify('+2 years')),'id')),'Completed assignment never returns to child wheel');
check(in_array($task['id'],array_column(available_tasks($s,'u4','all',$at->modify('+2 years')),'id')),'Shared task remains available to other child');
$view=member_snapshot($s,$parent);$row=array_values(array_filter($view['taskPool'],fn($t)=>$t['id']===$task['id']))[0];
check($row['childStatuses']['u3']==='completed'&&$row['childStatuses']['u4']==='available','Parent retains completed template with individual child status');
denied(function()use(&$s,$parent,$in,$at){parent_assign_task($s,$parent,array_replace($in,['requestId'=>'completed-again-12345']),$at->modify('+2 years'));},409);
$private=array_values(array_filter($s['tasks'],fn($t)=>($t['ownerId']??null)==='u4'))[0];
denied(function()use(&$s,$parent,$in,$private){parent_assign_task($s,$parent,array_replace($in,['taskId'=>$private['id']]));},403);
$s['users'][2]['archivedAt']=$at->format(DateTimeInterface::ATOM);
denied(function()use(&$s,$parent,$in){parent_assign_task($s,$parent,$in);},409);
echo "Direct assignment and permanent completion checks passed.\n";
