<?php
require __DIR__.'/../app/domain.php';require __DIR__.'/../app/accounts.php';
$n=0;function verify_profile($condition,$name){global $n;if(!$condition)throw new RuntimeException($name);$n++;}
function denied_profile($fn,$status){try{$fn();}catch(AppError $e){verify_profile($e->status===$status,'Wrong status');return;}throw new RuntimeException('Expected error');}
$s=initial_state();$rows=[];foreach($s['users'] as $i=>$u)$rows[]=['id'=>$u['id'],'name'=>$u['name'],'username'=>'member'.$i,'password'=>'fixture-password-123'];setup_accounts($s,['adminId'=>'u1','accounts'=>$rows]);$actor=$s['users'][1];$session=['userId'=>'u2','authVersion'=>1,'expires'=>time()+300];
$in=['username'=>'new_member','currentPassword'=>'fixture-password-123','password'=>'new-password-123','passwordConfirm'=>'new-password-123'];
denied_profile(function()use(&$s,$actor,$in){change_credentials($s,$actor,$in+['userId'=>'u1']);},403);
denied_profile(function()use(&$s,$actor,$in){change_credentials($s,$actor,array_merge($in,['currentPassword'=>'wrong']));},422);
denied_profile(function()use(&$s,$actor,$in){change_credentials($s,$actor,array_merge($in,['username'=>'MEMBER0']));},409);
denied_profile(function()use(&$s,$actor,$in){change_credentials($s,$actor,array_merge($in,['password'=>'short','passwordConfirm'=>'short']));},400);
denied_profile(function()use(&$s,$actor,$in){change_credentials($s,$actor,array_merge($in,['passwordConfirm'=>'not-matching']));},400);
change_credentials($s,$actor,$in+['role'=>'admin']);verify_profile($s['users'][1]['role']==='member','Cannot change role');verify_profile(password_verify('new-password-123',$s['users'][1]['passwordHash']),'New password works');verify_profile(!password_verify('fixture-password-123',$s['users'][1]['passwordHash']),'Old password revoked');verify_profile(actor_for($s,$session)===null,'Other sessions revoked');
$hash=$s['users'][1]['passwordHash'];change_credentials($s,$s['users'][1],['username'=>'other_name','currentPassword'=>'new-password-123','password'=>'']);verify_profile($s['users'][1]['passwordHash']===$hash,'Username-only edit preserves password');
$image=imagecreatetruecolor(100,70);imagefill($image,0,0,imagecolorallocate($image,135,90,220));ob_start();imagepng($image);$data='data:image/png;base64,'.base64_encode(ob_get_clean());
save_photo($s,$actor,['photo'=>$data]);$photo=$s['users'][1]['photo'];verify_profile(str_starts_with($photo,'data:image/jpeg;base64,'),'Image re-encoded as JPEG');$info=getimagesizefromstring(base64_decode(explode(',',$photo)[1]));verify_profile($info[0]===256&&$info[1]===256,'Photo normalized to 256 square');verify_profile(!isset($s['users'][0]['photo']),'Other profiles unchanged');
$v=member_snapshot($s,$s['users'][0]);verify_profile($v['users'][1]['photo']===$photo,'Family sees profile picture');verify_profile(!str_contains(json_encode($v),'passwordHash'),'No secret leakage');
denied_profile(function()use(&$s,$actor,$data){save_photo($s,$actor,['photo'=>$data,'id'=>'u1']);},403);
foreach(['data:image/svg+xml;base64,'.base64_encode('<svg/>'),'data:image/jpeg;base64,'.base64_encode('<?php echo 1;'),'https://example.com/photo.png',str_repeat('x',220001),false] as $invalid)denied_profile(function()use(&$s,$actor,$invalid){save_photo($s,$actor,['photo'=>$invalid]);},400);
$large=imagecreatetruecolor(513,1);ob_start();imagepng($large);$largeData='data:image/png;base64,'.base64_encode(ob_get_clean());denied_profile(function()use(&$s,$actor,$largeData){save_photo($s,$actor,['photo'=>$largeData]);},400);
save_photo($s,$actor,['photo'=>$data]);verify_profile(isset($s['users'][1]['photo']),'Photo replacement works');save_photo($s,$actor,['remove'=>true]);verify_profile(!isset($s['users'][1]['photo'])&&$s['users'][1]['avatar']!=='','Removal restores emoji fallback');
echo "✓ $n self-service credentials and photo checks passed.\n";
