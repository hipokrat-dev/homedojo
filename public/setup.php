<?php
declare(strict_types=1);
header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');header('X-Frame-Options: DENY');
require_once __DIR__.'/../app/bootstrap.php';
session_start_safe();
$configPath=__DIR__.'/../app/config.local.php';$keyPath=__DIR__.'/../app/setup-key.php';
if(is_file($configPath)){http_response_code(403);exit('Kurulum tamamlandı. Ana sayfadan giriş yapabilirsiniz.');}
if(!is_file($keyPath)){http_response_code(403);exit('Kurulum kilitli. Site sahibi app/setup-key.php dosyasını oluşturmalı.');}
$expected=require $keyPath;
if(isset($_GET['key'])&&is_string($_GET['key'])&&is_string($expected)&&strlen($expected)>=32&&hash_equals($expected,$_GET['key'])){$_SESSION['setup_authorized']=hash('sha256',$expected);$_SESSION['setup_expires']=time()+1800;header('Location: setup.php');exit;}
if(($_SESSION['setup_expires']??0)<time()||!isset($_SESSION['setup_authorized'])||!hash_equals(hash('sha256',$expected),$_SESSION['setup_authorized'])){http_response_code(403);exit('Kurulum bağlantısı geçersiz.');}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
try{
    if(!hash_equals($_SESSION['csrf'],$_POST['csrf']??''))throw new AppError('Oturum geçersiz. Sayfayı yenileyin.');
    $password=valid_text($_POST['household_password']??'','Ev şifresi',72);if(mb_strlen($password)<12)throw new AppError('Ev şifresi en az 12 karakter olmalı.');
    if($password!==($_POST['household_confirm']??''))throw new AppError('Ev şifreleri eşleşmiyor.');
    $url=rtrim(valid_text($_POST['public_url']??'','Site adresi',200),'/');$parts=parse_url($url);
    if(!filter_var($url,FILTER_VALIDATE_URL)||($parts['scheme']??'')!=='https'||isset($parts['user'])||isset($parts['pass'])||!empty($parts['path'])||isset($parts['query'])||isset($parts['fragment']))throw new AppError('Site adresi https:// ile başlamalı; yol içermemeli.');
    $config=['environment'=>'production','driver'=>'mysql','host'=>valid_text($_POST['host']??'','Sunucu',120),'port'=>3306,'database'=>valid_text($_POST['database']??'','Veritabanı',100),'username'=>valid_text($_POST['username']??'','Kullanıcı',100),'password'=>valid_text($_POST['db_password']??'','Veritabanı şifresi',1024),'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'public_url'=>$url];
    // A fresh connection verifies credentials before any config is saved.
    $store=new Store($config);$store->initialize();
    $handle=fopen($configPath,'x');if(!$handle)throw new AppError('Kurulum başka bir oturumda tamamlandı.',409);
    $configText="<?php\nreturn ".var_export($config,true).";\n";
    if(fwrite($handle,$configText)!==strlen($configText)){fclose($handle);unlink($configPath);throw new RuntimeException('Configuration write failed.');}fclose($handle);chmod($configPath,0600);
    unlink($keyPath);$_SESSION=[];session_regenerate_id(true);header('Location: ./');exit;
}catch(AppError $e){$error=$e->getMessage();}catch(Throwable $e){error_log('HomeDojo setup: '.$e->getMessage());$error='Veritabanına bağlanılamadı veya yapılandırma kaydedilemedi. Bilgileri kontrol edin.';}
}
function h(string $v): string{return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>HomeDojo · İlk kurulum</title><link rel="stylesheet" href="style.css"></head><body><main class="login"><form method="post" class="login-box" style="max-width:550px;margin:30px auto"><div class="brand"><span class="brand-icon">⌂</span><div>home<span>dojo</span></div></div><h1>Evini hazırlayalım.</h1><p>Hostinger MySQL bilgilerini ve evin ortak giriş şifresini gir. Bilgiler yalnızca kendi sunucuna kaydedilir. Kurulum tamamlanınca bu ekran kilitlenir.</p><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><label>Site adresi<input name="public_url" type="url" value="https://olivedrab-barracuda-526213.hostingersite.com" required></label><label>MySQL sunucusu<input name="host" value="localhost" required></label><label>MySQL veritabanı adı<input name="database" placeholder="u123456789_homedojo" required></label><label>MySQL kullanıcı adı<input name="username" placeholder="u123456789_homedojo" required></label><label>MySQL şifresi<input name="db_password" type="password" autocomplete="off" required></label><label>Evin ortak şifresi (en az 12 karakter)<input name="household_password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required></label><label>Ev şifresini tekrar gir<input name="household_confirm" type="password" minlength="12" maxlength="72" autocomplete="new-password" required></label><div class="form-error" role="alert"><?=h($error)?></div><button class="primary" type="submit">Kurulumu tamamla →</button></form></main></body></html>
