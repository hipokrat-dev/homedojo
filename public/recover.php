<?php
// Disabled unless the site owner installs a short-lived key through hosting.
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/accounts.php';
header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');header('X-Frame-Options: DENY');header("Content-Security-Policy: default-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; frame-ancestors 'none'");
session_start_safe();$error='';$enabled=false;
try{
 $config=configuration();$keyPath=__DIR__.'/../app/recovery-key.json';$store=new Store($config);
 if(accounts_ready($store->read()))throw new AppError('Kişisel hesaplar zaten hazır. Kullanıcı adınla giriş yap; şifreyi admin hesabından yenileyebilirsin.',409);
 $key=is_file($keyPath)?json_decode(file_get_contents($keyPath),true):null;
 $enabled=is_array($key)&&is_string($key['hash']??null)&&strlen($key['hash'])===64&&($key['expires']??0)>time()&&$key['expires']<=time()+86400;
 if(!$enabled)throw new AppError('Kurtarma henüz açılmadı. Site sahibi Hostinger üzerinden tek kullanımlık anahtarı etkinleştirmeli.',403);
 if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!hash_equals($_SESSION['csrf'],$_POST['csrf']??''))throw new AppError('Sayfayı yenileyip tekrar dene.',403);
  $origin=$_SERVER['HTTP_ORIGIN']??'';if($origin!==''&&$origin!==rtrim($config['public_url'],'/'))throw new AppError('İstek kaynağı geçersiz.',403);
  $token=$_POST['key']??'';if(!is_string($token)||strlen($token)!==64||!hash_equals($key['hash'],hash('sha256',$token)))throw new AppError('Kurtarma anahtarı geçersiz.',403);
  // Atomic rename consumes the key once, even across simultaneous requests.
  $used=$keyPath.'.used-'.bin2hex(random_bytes(8));if(!rename($keyPath,$used))throw new AppError('Anahtar zaten kullanılmış. Sayfayı yenile.',409);
  unlink($used);session_regenerate_id(true);$_SESSION=['setupVerified'=>true,'expires'=>time()+1800,'csrf'=>bin2hex(random_bytes(32))];
  header('Location: ./');exit;
 }
}catch(AppError $e){$error=$e->getMessage();http_response_code($e->status);}catch(Throwable $e){$error='Kurtarma şu anda açılamıyor.';http_response_code(503);}
function h(string $s): string{return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>HomeDojo · Hesap kurulumunu aç</title><link rel="stylesheet" href="style.css"></head><body><main class="login"><form method="post" class="login-box"><div class="brand"><span class="brand-icon">⌂</span>homedojo</div><h1>Kendi hesabına geç.</h1><p>Bu adım eski ev şifresini istemeden kişisel hesap kurulumunu açar. Mevcut puanların ve görevlerin korunur.</p><div class="form-error" role="alert"><?=h($error)?></div><?php if($enabled): ?><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><label>Tek kullanımlık kurtarma anahtarı<input type="password" name="key" minlength="64" maxlength="64" autocomplete="off" required></label><button class="primary">Hesap kurulumunu aç</button><?php endif; ?><p><a href="./">Giriş ekranına dön</a></p></form></main></body></html>
