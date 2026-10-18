<?php
require __DIR__.'/../app/domain.php';require __DIR__.'/../app/accounts.php';
function expect($value){if(!$value)throw new RuntimeException('Assertion failed');}
function rejects($fn){try{$fn();}catch(AppError){return;}throw new RuntimeException('Expected rejection');}
$s=initial_state();$s['users'][0]['memberType']='parent';$s['users'][2]['memberType']='child';$a=$s['users'][2];$s['users'][3]['memberType']='child';$b=$s['users'][3];
personal_plan($s,$a,'savePlan',['kind'=>'lesson','title'=>'Matematik','day'=>'1','time'=>'09:00']);
personal_plan($s,$a,'savePlan',['kind'=>'todo','title'=>'Kitap oku','day'=>'2026-10-18','time'=>'']);
$id=$s['users'][2]['personalPlan'][1]['id'];
rejects(fn()=>personal_plan($s,$b,'deletePlan',['id'=>$id]));
rejects(fn()=>personal_plan($s,$a,'savePlan',['kind'=>'todo','title'=>'Geçersiz','day'=>'2026-02-30']));
rejects(fn()=>personal_plan($s,$a,'savePlan',['kind'=>'lesson','title'=>'Geçersiz','day'=>'8']));
rejects(fn()=>personal_plan($s,$s['users'][0],'savePlan',['kind'=>'lesson','title'=>'Ders','day'=>'1']));
$before=balance($s,$a['id']);personal_plan($s,$a,'togglePlan',['id'=>$id]);expect($s['users'][2]['personalPlan'][1]['done']);expect(balance($s,$a['id'])===$before);
personal_plan($s,$a,'savePlan',['id'=>$id,'kind'=>'todo','title'=>'Yeni başlık','day'=>'2026-10-19','time'=>'17:00']);expect($s['users'][2]['personalPlan'][1]['done']);
foreach($s['users'] as &$u)$u['role']='member';unset($u);$s['users'][2]['guardianId']='u1';$s['users'][3]['guardianId']='u1';$a=$s['users'][2];$b=$s['users'][3];
$v=member_snapshot($s,$a);expect(count($v['personalPlan'])===2);foreach($v['users'] as $u)expect(!isset($u['personalPlan']));expect(member_snapshot($s,$b)['personalPlan']===[]);
personal_plan($s,$a,'deletePlan',['id'=>$id]);expect(count($s['users'][2]['personalPlan'])===1);
echo "Planner ownership, validation, privacy and no-points tests passed.\n";
