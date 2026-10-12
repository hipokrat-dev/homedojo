<?php
require_once __DIR__.'/../app/store.php';require_once __DIR__.'/../app/push.php';
function ensure($ok,$message){if(!$ok)throw new RuntimeException($message);}
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$push=new TaskPush($db);
$keys=$push->keys();ensure($keys===$push->keys(),'Keys persist');
$auth=$push->authorization();preg_match('/t=([^,]+), k=(.+)/',$auth,$m);[$h,$p,$s]=explode('.',$m[1]);$decode=fn($v)=>base64_decode(strtr($v,'-_','+/'));$claims=json_decode($decode($p),true);
ensure($claims['aud']==='https://fcm.googleapis.com'&&$claims['exp']>time(),'VAPID audience and expiry');$raw=$decode($s);ensure(strlen($raw)===64,'JOSE signature size');
$der='';foreach(str_split($raw,32) as $integer){$integer=ltrim($integer,"\0");if(ord($integer[0])>127)$integer="\0".$integer;$der.="\x02".chr(strlen($integer)).$integer;}$der="\x30".chr(strlen($der)).$der;
$public=openssl_pkey_get_details(openssl_pkey_get_private($keys['private_key']))['key'];ensure(openssl_verify($h.'.'.$p,$der,$public,OPENSSL_ALGO_SHA256)===1,'Signature verifies');
foreach(['http://fcm.googleapis.com/wp/test','https://localhost/wp/test','https://fcm.googleapis.com.evil.test/wp/test','https://fcm.googleapis.com/wp/test?x=1','https://fcm.googleapis.com:444/wp/test'] as $url){try{TaskPush::endpoint($url);throw new RuntimeException('Unsafe URL accepted');}catch(AppError $e){ensure($e->status===422,'Endpoint rejected');}}
$child=['id'=>'u3','authVersion'=>1,'memberType'=>'child'];$other=['id'=>'u4','authVersion'=>1,'memberType'=>'young_child'];
$ep='https://fcm.googleapis.com/wp/test-device';$push->subscribe($child,['endpoint'=>$ep]);
$count=0;$ok=function($url,$header)use(&$count){$count++;return 201;};
ensure($push->deliver($other,'a',$ok)['sent']===0,'Other child isolated');
ensure($push->deliver($child,'a',$ok)['sent']===1,'Delivered');$push->deliver($child,'a',$ok);ensure($count===1,'Duplicate assignment deduplicated');
ensure($push->deliver(array_replace($child,['authVersion'=>2]),'b',$ok)['devices']===0,'Revoked credentials isolated');
ensure($push->deliver($child,'b',fn()=>503)['failed']===1,'Failure recorded');ensure($push->deliver($child,'b',$ok)['sent']===1,'Failed delivery retry possible');
$push->subscribe($other,['endpoint'=>$ep]);ensure($push->deliver($child,'c',$ok)['devices']===0,'Shared tablet rebound to current account');
$push->deliver($other,'c',fn()=>410);ensure($push->deliver($other,'d',$ok)['devices']===0,'Expired subscriptions cleaned');
try{$push->subscribe(['id'=>'u1','memberType'=>'parent'],['endpoint'=>$ep]);throw new RuntimeException('Parent subscribed');}catch(AppError $e){ensure($e->status===403,'Parent rejected');}
echo "Push keys, VAPID signature, endpoint restrictions, device ownership, revocation, deduplication, retries and expiry passed.\n";
