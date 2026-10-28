<?php
declare(strict_types=1);
header('X-HomeDojo-Version: 9');header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/accounts.php';
require_once __DIR__.'/../app/progress.php';
require_once __DIR__.'/../app/wishes.php';
require_once __DIR__.'/../app/push.php';
try{
    $config=configuration();session_start_safe();$action=$_GET['action']??'state';$method=$_SERVER['REQUEST_METHOD'];
    $development=($config['environment']??'production')==='development';
    if(!$development&&empty($config['password_hash']))throw new RuntimeException('Password is required in production.');
    $writes=['answerConceptCard','addShoppingProduct','saveShoppingFavorite','saveShoppingItem','setShoppingDone','deleteShoppingItem','saveNote','deleteNote','saveFootballVideo','deleteFootballVideo','savePlan','deletePlan','togglePlan','pushSubscribe','pushUnsubscribe','pushTest','deleteRewardRequest','saveGames','parentAssignTask','parentApproveAssignment','parentApproveRoutine','requestReward','reviewRewardRequest','cancelRewardRequest','login','logout','spin','saveTask','deleteTask','saveReward','deleteReward','saveUser','selectGoal','complete','redeem','cancelAssignment','createCompetition','cancelCompetition','claimCompetition','setupAccounts','saveAccount','reviewAssignment','savePhoto','changeCredentials','createAccount','archiveAccount','restoreAccount','saveRoutines','completeRoutine'];
    if(in_array($action,$writes,true)){
        if($method!=='POST')throw new AppError('Bu işlem POST gerektirir.',405);
        if(($_SERVER['HTTP_SEC_FETCH_SITE']??'')==='cross-site')throw new AppError('Bu kaynaktan işlem yapılamaz.',403);
        $origin=$_SERVER['HTTP_ORIGIN']??'';$expected=rtrim($config['public_url'],'/');
        if($origin!==''&&$origin!==$expected)throw new AppError('İstek kaynağı geçersiz.',403);
        if(!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??''))throw new AppError('Oturum yenilendi. Sayfayı yenileyip tekrar dene.',403);
        if(!str_starts_with($_SERVER['CONTENT_TYPE']??'','application/json'))throw new AppError('JSON içerik gerekli.',415);
        $maxBytes=$action==='savePhoto'?230000:16384;
        if((int)($_SERVER['CONTENT_LENGTH']??0)>$maxBytes)throw new AppError('İstek çok büyük.',413);
        $raw=file_get_contents('php://input',false,null,0,$maxBytes+1);if(strlen($raw)>$maxBytes)throw new AppError('İstek çok büyük.',413);
        try{$input=json_decode($raw,true,64,JSON_THROW_ON_ERROR);}catch(JsonException){throw new AppError('Geçersiz istek.');}
        if(!is_array($input))throw new AppError('Geçersiz istek.');
    }elseif($method!=='GET')throw new AppError('Yöntem desteklenmiyor.',405);
    $store=new Store($config);if($development)$store->initialize();
    $current=$store->read();$ready=accounts_ready($current);$actor=actor_for($current,$_SESSION);
    if($action==='session')json_response(['authenticated'=>$actor!==null,'needsAccountSetup'=>!$ready,'photoUploadEnabled'=>function_exists('imagecreatefromstring'),'csrf'=>$_SESSION['csrf']]);
    if($action==='logout'){if($actor&&!empty($_SESSION['pushDevice'])){try{(new TaskPush($store->db))->remove($_SESSION['pushDevice'],$actor['id']);}catch(Throwable $e){error_log('Push logout cleanup failed');}}$_SESSION=[];session_destroy();setcookie(session_name(),'', ['expires'=>1,'path'=>'/','secure'=>!$development,'httponly'=>true,'samesite'=>'Strict']);json_response(['ok'=>true]);}
    if($action==='login'){
        $ip=$_SERVER['REMOTE_ADDR']??'unknown';$store->attemptLogin($ip);$password=$input['password']??'';
        if(!$ready){
            if(!is_string($password)||strlen($password)>1024||!password_verify($password,$config['password_hash']))throw new AppError('Ev şifresi doğru değil.',422);
            $store->clearLogin($ip);session_regenerate_id(true);$_SESSION['setupVerified']=true;$_SESSION['expires']=time()+1800;$_SESSION['csrf']=bin2hex(random_bytes(32));json_response(['csrf'=>$_SESSION['csrf'],'needsAccountSetup'=>true]);
        }
        $username=strtolower(is_string($input['username']??null)?$input['username']:'');$match=null;
        foreach($current['users'] as $u)if(empty($u['archivedAt'])&&($u['username']??null)===$username)$match=$u;
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
    if($action==='pushKey'){if(!function_exists('curl_init')||!function_exists('openssl_pkey_new'))throw new AppError('Sunucuda bildirim desteği kullanılamıyor.',503);json_response(['publicKey'=>(new TaskPush($store->db))->keys()['public_key']]);}
    if(!$actor)throw new AppError('Kendi kullanıcı adın ve şifrenle giriş yap.',401);
    if(in_array($action,['pushSubscribe','pushUnsubscribe','pushTest'],true)){
        $push=new TaskPush($store->db);
        if($action==='pushSubscribe'){$push->subscribe($actor,$input);json_response(['ok'=>true]);}
        if($action==='pushUnsubscribe'){$push->remove(hash('sha256',TaskPush::endpoint($input['endpoint']??null)),$actor['id']);unset($_SESSION['pushDevice']);json_response(['ok'=>true]);}
        $store->attemptLogin('push-test:'.$actor['id']);json_response(['delivery'=>$push->deliver($actor,'test:'.bin2hex(random_bytes(16)))]);
    }
    if($action==='changeCredentials'){
        $limitKey='credentials:'.$actor['id'].':'.($_SERVER['REMOTE_ADDR']??'unknown');$store->attemptLogin($limitKey);
        $current=$store->update(function(array &$s)use($input){$actor=actor_for($s,$_SESSION);if(!$actor)throw new AppError('Oturum sona erdi.',401);change_credentials($s,$actor,$input);});
        $updated=$current['users'][find_index($current['users'],$actor['id'],'Kullanıcı')];
        $store->clearLogin($limitKey);session_regenerate_id(true);$_SESSION=['userId'=>$updated['id'],'authVersion'=>$updated['authVersion'],'expires'=>time()+86400,'csrf'=>bin2hex(random_bytes(32))];
        json_response(['state'=>member_snapshot($current,$updated),'csrf'=>$_SESSION['csrf']]);
    }
    $session=$_SESSION;$csrf=$_SESSION['csrf'];session_write_close();
    if($action==='state')json_response(['state'=>member_snapshot($current,$actor),'csrf'=>$csrf]);
    if($action==='football'){if(!football_enabled($actor))throw new AppError('Bu alan Aey hesabına ait.',403);json_response(['football'=>(new FootballFeed($store->db))->team(is_string($_GET['team']??null)?$_GET['team']:'bursa')]);}
    if($action==='progress')json_response(['report'=>child_progress($current,$actor,$_GET)]);
    $result=[];
    $current=$store->update(function(array &$s)use($action,$input,$session,&$result){
        $actor=actor_for($s,$session);if(!$actor)throw new AppError('Oturum sona erdi.',401);
        authorize_action($s,$actor,$action,$input);
        if(in_array($action,['addShoppingProduct','saveShoppingFavorite'],true))shopping_quick_add($s,$actor,$action,$input);
        elseif(in_array($action,['saveShoppingItem','setShoppingDone','deleteShoppingItem'],true))shopping_item($s,$actor,$action,$input);
        elseif(in_array($action,['saveNote','deleteNote'],true))personal_note($s,$actor,$action,$input);
        elseif(in_array($action,['saveFootballVideo','deleteFootballVideo'],true))football_video($s,$actor,$action,$input);
        elseif(in_array($action,['savePlan','deletePlan','togglePlan'],true))personal_plan($s,$actor,$action,$input);
        elseif($action==='answerConceptCard')answer_concept_card($s,$actor,$input);
        elseif($action==='spin')$result=spin($s,$actor['id'],'all',valid_request($input['requestId']??null),null,true);
        elseif(in_array($action,['requestReward','reviewRewardRequest','cancelRewardRequest','deleteRewardRequest'],true))reward_wish($s,$actor,$action,$input);
        elseif($action==='saveAccount')save_account($s,$input);
        elseif($action==='createAccount')create_account($s,$input);
        elseif($action==='archiveAccount')archive_account($s,$actor,$input);
        elseif($action==='restoreAccount')restore_account($s,$input);
        elseif($action==='saveRoutines')save_routines($s,$input);
        elseif($action==='saveGames')save_games($s,$actor,$input);
        elseif($action==='parentAssignTask')$result=parent_assign_task($s,$actor,$input);
        elseif($action==='parentApproveAssignment')parent_approve_assignment($s,$actor,$input);
        elseif($action==='parentApproveRoutine')parent_approve_routine($s,$actor,$input);
        elseif($action==='completeRoutine')complete_routine($s,$actor,$input);
        elseif($action==='savePhoto')save_photo($s,$actor,$input);
        else{$input['actorId']=$actor['id'];mutate($s,$action,$input);}
    });
    $updatedActor=$current['users'][find_index($current['users'],$actor['id'],'Kullanıcı')];
    if($action==='parentAssignTask'&&!empty($result['assignment'])){
        try{$child=$current['users'][find_index($current['users'],$result['assignment']['userId'],'Çocuk')];$result['pushDelivery']=(new TaskPush($store->db))->deliver($child,$result['assignment']['id']);}
        catch(Throwable $e){error_log('HomeDojo push unavailable');$result['pushDelivery']=['devices'=>0,'sent'=>0,'failed'=>1];}
    }
    json_response($result+['state'=>member_snapshot($current,$updatedActor)]);
}catch(AppError $e){json_response(['error'=>$e->getMessage()],$e->status);}catch(Throwable $e){error_log('HomeDojo: '.$e->getMessage());json_response(['error'=>'Sunucu bağlantısı tamamlanamadı. Lütfen daha sonra tekrar dene.'],500);}
