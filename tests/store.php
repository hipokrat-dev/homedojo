<?php
declare(strict_types=1);
require __DIR__.'/../app/store.php';
$store=new Store(['environment'=>'development','driver'=>'sqlite','sqlite_path'=>':memory:']);$store->initialize();$store->initialize();
if(count($store->read()['users'])!==4)throw new RuntimeException('Initialization is not idempotent');
$store->update(function(&$s){mutate($s,'complete',['userId'=>'u1','taskId'=>'t1']);});
try{$store->update(function(&$s){$s['users'][0]['name']='Must roll back';throw new AppError('Expected');});}catch(AppError){}
if($store->read()['users'][0]['name']!=='Oyuncu 1')throw new RuntimeException('Rollback failed');
try{$store->update(function(&$s){mutate($s,'complete',['userId'=>'u1','taskId'=>'t1']);});}catch(AppError $e){if($e->status!==409)throw $e;}
if(balance($store->read(),'u1')!==20)throw new RuntimeException('Duplicate write credited twice');
for($i=0;$i<15;$i++)$store->attemptLogin('test-ip');
try{$store->attemptLogin('test-ip');throw new RuntimeException('Rate limit failed');}catch(AppError $e){if($e->status!==429)throw $e;}
$store->clearLogin('test-ip');$store->attemptLogin('test-ip');
echo "✓ Store initialization, rollback, duplicate protection and login throttling passed.\n";
