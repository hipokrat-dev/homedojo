<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/accounts.php';
try{
    $config=configuration();session_start_safe();$action=$_GET['action']??'state';$method=$_SERVER['REQUEST_METHOD'];
    $development=($config['environment']??'production')==='development';
    if(!$development&&empty($config['password_hash']))throw new RuntimeException('Password is required in production.');
    $writes=['login','logout','spin','saveTask','deleteTask','saveReward','deleteReward','saveUser','selectGoal','complete','redeem','cancelAssignment','createCompetition','cancelCompetition','claimCompetition','setupAccounts','saveAccount','reviewAssignment'];
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
    $store=new Store($config);if($development)$store->initialize();
    $current=$store->read();$ready=accounts_ready($current);$actor=actor_for($current,$_SESSION);
    if($action==='session')json_response(['authenticated'=>$actor!==null,'needsAccountSetup'=>!$ready,'csrf'=>$_SESSION['csrf']]);
    if($action==='logout'){$_SESSION=[];session_destroy();setcookie(session_name(),'', ['expires'=>1,'path'=>'/','secure'=>!$development,'httponly'=>true,'samesite'=>'Strict']);json_response(['ok'=>true]);}
    if($action==='login'){
        $ip=$_SERVER['REMOTE_ADDR']??'unknown';$store->attemptLogin($ip);$password=$input['password']??'';
        if(!$ready){
            if(!is_string($password)||strlen($password)>1024||!password_verify($password,$config['password_hash']))throw new AppError('Ev şifresi doğru değil.',422);
            $store->clearLogin($ip);session_regenerate_id(true);$_SESSION['setupVerified']=true;$_SESSION['expires']=time()+1800;$_SESSION['csrf']=bin2hex(random_bytes(32));json_response(['csrf'=>$_SESSION['csrf'],'needsAccountSetup'=>true]);
        }
        $username=strtolower(is_string($input['username']??null)?$input['username']:'');$match=null;
        foreach($current['users'] as $u)if(($u['username']??null)===$username)$match=$u;
        $hash=$match['passwordHash']??'$2y$10$Yx43vCh2BlMS9T9oeQrxAeSwRYN0Wo83ZmGvrAMYBYlAyKnlCV6rO';
        if(!is_string($password)||strlen($password)>72||!password_verify($password,$hash)||!$match)throw new AppError('Kullanıcı adı veya şifre doğru değil.',422);
        $store->clearLogin($ip);session_regenerate_id(true);$_SESSION=['userId'=>$match['id'],'authVersion'=>$match['authVersion'],'expires'=>time()+86400,'csrf'=>bin2hex(random_bytes(32))];json_response(['csrf'=>$_SESSION['csrf']]);
    }
    if(!$ready){
        $setupSession=($development&&empty($config['password_hash']))||(!empty($_SESSION['setupVerified'])||!empty($_SESSION['authenticated']))&&($_SESSION['expires']??0)>time();
        if(!$setupSession)throw new AppError('Hesapları oluşturmak için mevcut ev şifrenle giriş yap.',401);
        if($action==='state')json_response(['setupRequired'=>true,'profiles'=>array_map(fn($u)=>['id'=>$u['id'],'name'=>$u['name'],'avatar'=>$u['avatar']],$current['users']),'csrf'=>$_SESSION['csrf']]);
        if($action!=='setupAccounts')throw new AppError('Önce kişisel hesapları oluşturun.',409);
        $current=$store->update(function(array &$s)use($input){setup_accounts($s,$input);});
        $admin=$current['users'][find_index($current['users'],$input['adminId'],'Admin')];session_regenerate_id(true);
        $_SESSION=['userId'=>$admin['id'],'authVersion'=>$admin['authVersion'],'expires'=>time()+86400,'csrf'=>bin2hex(random_bytes(32))];
        json_response(['state'=>member_snapshot($current,$admin),'csrf'=>$_SESSION['csrf']]);
    }
    if($action==='setupAccounts')throw new AppError('Kişisel hesaplar zaten oluşturuldu.',409);
    if(!$actor)throw new AppError('Kendi kullanıcı adın ve şifrenle giriş yap.',401);
    $session=$_SESSION;$csrf=$_SESSION['csrf'];session_write_close();
    if($action==='state')json_response(['state'=>member_snapshot($current,$actor),'csrf'=>$csrf]);
    $result=[];
    $current=$store->update(function(array &$s)use($action,$input,$session,&$result){
        $actor=actor_for($s,$session);if(!$actor)throw new AppError('Oturum sona erdi.',401);
        authorize_action($s,$actor,$action,$input);
        if($action==='spin')$result=spin($s,$actor['id'],valid_text($input['frequency']??'all','Dönem',10),valid_request($input['requestId']??null));
        elseif($action==='saveAccount')save_account($s,$input);
        else{$input['actorId']=$actor['id'];mutate($s,$action,$input);}
    });
    json_response($result+['state'=>member_snapshot($current,$actor)]);
}catch(AppError $e){json_response(['error'=>$e->getMessage()],$e->status);}catch(Throwable $e){error_log('HomeDojo: '.$e->getMessage());json_response(['error'=>'Sunucu bağlantısı tamamlanamadı. Lütfen daha sonra tekrar dene.'],500);}
