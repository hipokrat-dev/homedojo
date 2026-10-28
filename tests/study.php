<?php
require __DIR__.'/../app/domain.php';require __DIR__.'/../app/accounts.php';
function check_study($ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
function deny_study(callable $fn,int $status): void {try{$fn();}catch(AppError $e){check_study($e->status===$status,'Error status');return;}throw new RuntimeException('Expected rejection');}
$s=initial_state();$s['users'][0]['memberType']='parent';$s['users'][1]['memberType']='young_child';$s['users'][2]['memberType']='child';$s['users'][3]['memberType']='child';
foreach($s['users'] as &$u){$u['role']='member';$u['guardianId']='u1';}unset($u);
$a=$s['users'][2];$b=$s['users'][3];$deck=concept_cards();
check_study(count($deck)===100&&count(array_unique(array_column($deck,'id')))===100,'100 stable unique cards');
check_study(count(array_filter($deck,fn($c)=>$c['answer']==='somut'))===50,'Balanced answers');
check_study(count(array_filter($deck,fn($c)=>$c['context']!==''))===40,'40 contextual questions');
foreach($deck as $c)check_study($c['explanation']!==''&&in_array($c['answer'],['somut','soyut'],true),'Content complete');
$initial=concept_study_snapshot($a);check_study($initial['completed']===0&&!isset($initial['cards'][0]['answer']),'Unanswered answer hidden');
foreach([0,1] as $i)deny_study(fn()=>answer_concept_card($s,$s['users'][$i],['cardId'=>'k001','choice'=>'somut']),403);
deny_study(fn()=>answer_concept_card($s,$a,['cardId'=>'k001','choice'=>'other']),400);
deny_study(fn()=>answer_concept_card($s,$a,['cardId'=>'k002','choice'=>'somut']),409);
deny_study(fn()=>answer_concept_card($s,$a,['cardId'=>'k001','choice'=>'somut','userId'=>$b['id']]),403);
$balance=balance($s,$a['id']);
answer_concept_card($s,$a,['cardId'=>'k001','choice'=>$deck[0]['answer']]);
answer_concept_card($s,$a,['cardId'=>'k001','choice'=>$deck[0]['answer']==='somut'?'soyut':'somut']);
check_study(count($s['users'][2]['conceptStudyV1'])===1,'Retry does not advance twice');
for($i=1;$i<100;$i++)answer_concept_card($s,$a,['cardId'=>$deck[$i]['id'],'choice'=>$i%2?$deck[$i]['answer']:($deck[$i]['answer']==='somut'?'soyut':'somut')]);
// Simulate database serialization and another request/device.
$s=json_decode(json_encode($s),true);$a=$s['users'][2];$view=member_snapshot($s,$a);
check_study($view['conceptStudy']['completed']===100&&$view['conceptStudy']['correct']===51,'Persisted full progression and score');
check_study(balance($s,$a['id'])===$balance,'Study does not award family currency');
check_study(member_snapshot($s,$b)['conceptStudy']['completed']===0,'Separate progress per child');
foreach(member_snapshot($s,$b)['users'] as $u)check_study(!isset($u['conceptStudyV1']),'No raw progress disclosure');
deny_study(fn()=>answer_concept_card($s,$a,['cardId'=>'k101','choice'=>'somut']),409);
answer_concept_card($s,$a,['cardId'=>'k100','choice'=>'somut']);check_study(count($s['users'][2]['conceptStudyV1'])===100,'Completed retries safe');
echo "Study: 100 cards, content, roles, privacy, sequential progress, retries, persistence and scoring passed.\n";
