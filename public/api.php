<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
require_once __DIR__.'/../app/bootstrap.php';
try{
    $config=configuration();session_start_safe();$action=$_GET['action']??'state';$method=$_SERVER['REQUEST_METHOD'];
    $development=($config['environment']??'production')==='development';
    if(!$development&&empty($config['password_hash']))throw new RuntimeException('Password is required in production.');
    $authenticated=$development&&!$config['password_hash']||(!empty($_SESSION['authenticated'])&&($_SESSION['expires']??0)>time());
    $writes=['login','logout','spin','saveTask','deleteTask','saveReward','deleteReward','saveUser','complete','redeem'];
    if(in_array($action,$writes,true)){
        if($method!=='POST')throw new AppError('Bu işlem POST gerektirir.',405);
        if(($_SERVER['HTTP_SEC_FETCH_SITE']??'')==='cross-site')throw new AppError('Bu kaynaktan işlem yapılamaz.',403);
        $origin=$_SERVER['HTTP_ORIGIN']??'';$expected=rtrim($config['public_url'],'/');
        if($origin!==''&&$origin!==$expected)throw new AppError('İstek kaynağı geçersiz.',403);
        if(!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??''))throw new AppError('Oturum yenilendi. Sayfayı yenileyip tekrar dene.',403);
        if(!str_starts_with($_SERVER['CONTENT_TYPE']??'','application/json'))throw new AppError('JSON içerik gerekli.',415);
        if((int)($_SERVER['CONTENT_LENGTH']??0)>16384)throw new AppError('İstek çok büyük.',413);
        $raw=file_get_contents('php://input',false,null,0,16385);if(strlen($raw)>16384)throw new AppError('İstek çok büyük.',413);
        try{$input=json_decode($raw,true,64,JSON_THROW_ON_ERROR);}catch(JsonException){throw new AppError('Geçersiz istek.');}
        if(!is_array($input))throw new AppError('Geçersiz istek.');
    }elseif($method!=='GET')throw new AppError('Yöntem desteklenmiyor.',405);
    if($action==='session')json_response(['authenticated'=>$authenticated,'csrf'=>$_SESSION['csrf']]);
    if($action==='logout'){$_SESSION=[];session_destroy();setcookie(session_name(),'', ['expires'=>1,'path'=>'/','secure'=>!$development,'httponly'=>true,'samesite'=>'Strict']);json_response(['ok'=>true]);}
    if($action!=='login'&&!$authenticated)throw new AppError('Devam etmek için ev şifrenle giriş yap.',401);
    $store=new Store($config);if($development)$store->initialize();
    if($action==='login'){
        $ip=$_SERVER['REMOTE_ADDR']??'unknown';$store->attemptLogin($ip);
        $password=$input['password']??'';
        if(!is_string($password)||strlen($password)>1024||!password_verify($password,$config['password_hash']))throw new AppError('Ev şifresi doğru değil.',422);
        $store->clearLogin($ip);session_regenerate_id(true);$_SESSION['authenticated']=true;$_SESSION['expires']=time()+86400;$_SESSION['csrf']=bin2hex(random_bytes(32));json_response(['csrf'=>$_SESSION['csrf']]);
    }
    $csrf=$_SESSION['csrf'];session_write_close();
    if($action==='state')json_response(['state'=>snapshot($store->read()),'csrf'=>$csrf]);
    if($action==='spin'){if(!is_string($input['userId']??null))throw new AppError('Kullanıcı geçersiz.');json_response(spin($store->read(),$input['userId']));}
    $state=$store->update(function(array &$state)use($action,$input){mutate($state,$action,$input);});json_response(['state'=>snapshot($state)]);
}catch(AppError $e){json_response(['error'=>$e->getMessage()],$e->status);}catch(Throwable $e){error_log('HomeDojo: '.$e->getMessage());json_response(['error'=>'Sunucu bağlantısı tamamlanamadı. Lütfen daha sonra tekrar dene.'],500);}
