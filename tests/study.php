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
foreach($deck as $c)check_study($c['example']!==''&&$c['explanation']!==''&&in_array($c['answer'],['somut','soyut'],true),'Content complete');
$initial=concept_study_snapshot($a);check_study($initial['completed']===0&&!isset($initial['cards'][0]['answer']),'Unanswered answer hidden');
foreach([0,1] as $i)deny_study(fn()=>answer_concept_card($s,$s['users'][$i],['cardId'=>'k001','choice'=>'somut']),403);
deny_study(fn()=>answer_concept_card($s,$a,['cardId'=>'k001','choice'=>'other']),400);
deny_study(fn()=>answer_concept_card($s,$a,['cardId'=>'k002','choice'=>'somut']),409);
deny_study(fn()=>answer_concept_card($s,$a,['cardId'=>'k001','choice'=>'somut','userId'=>$b['id']]),403);
$balance=balance($s,$a['id']);
answer_concept_card($s,$a,['cardId'=>'k001','choice'=>$deck[0]['answer']]);
answer_concept_card($s,$a,['cardId'=>'k001','choice'=>$deck[0]['answer']==='somut'?'soyut':'somut']);
check_study(count($s['users'][2]['conceptStudyV1'])===1,'Retry does not advance twice');
// Wrong answers end the round, but preserve the explanatory card until restart.
answer_concept_card($s,$a,['cardId'=>'k002','choice'=>'soyut']);
$view=concept_study_snapshot($s['users'][2]);
check_study($view['ended']&&$view['score']===10&&$view['rank']['name']==='Bakır','Wrong answer ends round with score');
deny_study(fn()=>answer_concept_card($s,$a,['cardId'=>'k003','choice'=>'soyut']),409);
deny_study(fn()=>restart_concept_study($s,$b,['round'=>1]),409);
deny_study(fn()=>restart_concept_study($s,$a,['round'=>1,'userId'=>$b['id']]),403);
deny_study(fn()=>restart_concept_study($s,$s['users'][0],['round'=>1]),403);
restart_concept_study($s,$a,['round'=>1]);
$view=concept_study_snapshot($s['users'][2]);
check_study($view['completed']===0&&$view['round']===2&&$view['best']===10&&$view['last']['score']===10,'Restart preserves best and last score');
deny_study(fn()=>answer_concept_card($s,$a,['cardId'=>'k001','choice'=>'soyut','round'=>1]),409);
deny_study(fn()=>answer_concept_card($s,$a,['cardId'=>'k001','choice'=>'soyut']),409);
answer_concept_card($s,$a,['cardId'=>'k001','choice'=>'somut','round'=>2]);
restart_concept_study($s,$a,['round'=>1]);
check_study(count($s['users'][2]['conceptStudyV1'])===1,'Duplicate restart cannot erase new progress');
deny_study(fn()=>restart_concept_study($s,$a,['round'=>2]),409);
for($i=1;$i<100;$i++)answer_concept_card($s,$a,['cardId'=>$deck[$i]['id'],'choice'=>$deck[$i]['answer'],'round'=>2]);
$s=json_decode(json_encode($s),true);$a=$s['users'][2];$view=member_snapshot($s,$a);
check_study($view['conceptStudy']['completed']===100&&$view['conceptStudy']['score']===1000&&$view['conceptStudy']['rank']['id']==='platinum','Persisted full progression and score');
check_study(balance($s,$a['id'])===$balance,'Study does not award family currency');
check_study(member_snapshot($s,$b)['conceptStudy']['completed']===0,'Separate progress per child');
foreach(member_snapshot($s,$b)['users'] as $u)check_study(!isset($u['conceptStudyV1'])&&!isset($u['conceptStudyMeta']),'No raw progress disclosure');
foreach([0=>'copper',390=>'copper',400=>'silver',690=>'silver',700=>'gold',890=>'gold',900=>'platinum',1000=>'platinum'] as $score=>$rank)check_study(concept_rank($score)['id']===$rank,'Rank boundary');
restart_concept_study($s,$a,['round'=>2]);$view=concept_study_snapshot($s['users'][2]);
check_study($view['best']===1000&&$view['bestRank']['id']==='platinum'&&$view['completed']===0,'Complete restart preserves platinum best');
echo "Study: content, roles, privacy, score/rank boundaries, failed/completed rounds, stale requests, idempotent restarts and persistence passed.\n";
