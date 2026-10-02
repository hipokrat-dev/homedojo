<?php
declare(strict_types=1);
require __DIR__.'/../app/store.php';
$store=new Store(['environment'=>'development','driver'=>'sqlite','sqlite_path'=>':memory:']);$store->initialize();$store->initialize();
if(count($store->read()['users'])!==4)throw new RuntimeException('Initialization is not idempotent');
$store->update(function(&$s){mutate($s,'selectGoal',['userId'=>'u1','rewardId'=>'r1']);spin($s,'u1','daily','store-draw-12345678');});
$assignment=$store->read()['assignments'][0];
try{$store->update(function(&$s){$s['users'][0]['name']='Must roll back';throw new AppError('Expected');});}catch(AppError){}
if($store->read()['users'][0]['name']!=='Oyuncu 1')throw new RuntimeException('Rollback failed');
$store->update(function(&$s)use($assignment){mutate($s,'complete',['userId'=>'u1','assignmentId'=>$assignment['id']]);});
try{$store->update(function(&$s)use($assignment){mutate($s,'complete',['userId'=>'u1','assignmentId'=>$assignment['id']]);});throw new RuntimeException('Duplicate accepted');}catch(AppError $e){if($e->status!==409)throw $e;}
if(balance($store->read(),'u1')!==$assignment['points'])throw new RuntimeException('Duplicate write credited twice');
for($i=0;$i<15;$i++)$store->attemptLogin('test-ip');
try{$store->attemptLogin('test-ip');throw new RuntimeException('Rate limit failed');}catch(AppError $e){if($e->status!==429)throw $e;}
$store->clearLogin('test-ip');$store->attemptLogin('test-ip');
echo "✓ Store initialization, saved assignment, rollback, duplicate protection and login throttling passed.\n";
