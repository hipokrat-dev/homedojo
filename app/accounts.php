<?php
declare(strict_types=1);
function accounts_ready(array $s): bool { return !empty($s['accountsEnabled']); }
function valid_username(mixed $v): string {
    if(!is_string($v)||!preg_match('/^[a-zA-Z0-9._-]{3,32}$/',$v))throw new AppError('Kullanıcı adı 3–32 harf, rakam, nokta veya alt çizgi olmalı.');
    return strtolower($v);
}
function account_password(mixed $v): string {
    if(!is_string($v)||strlen($v)<12||strlen($v)>72)throw new AppError('Şifre 12–72 karakter olmalı.');return $v;
}
function setup_accounts(array &$s,array $in): void {
    if(accounts_ready($s))throw new AppError('Kişisel hesaplar zaten oluşturuldu.',409);
    $rows=$in['accounts']??null;
    if(!is_array($rows)||count($rows)!==count($s['users']))throw new AppError('Dört hesabın bilgilerini tamamlayın.');
    $admin=$in['adminId']??'';find_index($s['users'],$admin,'Baba profili');$ids=[];$names=[];$updated=[];
    foreach($rows as $row){
        if(!is_array($row))throw new AppError('Hesap bilgileri geçersiz.');
        $i=find_index($s['users'],$row['id']??'','Profil');$id=$s['users'][$i]['id'];$username=valid_username($row['username']??null);
        if(isset($ids[$id])||isset($names[$username]))throw new AppError('Her profil ve kullanıcı adı yalnızca bir kez kullanılabilir.');
        $ids[$id]=true;$names[$username]=true;
        $updated[$i]=array_merge($s['users'][$i],['name'=>$id===$admin?'Baba':valid_text($row['name']??null,'İsim',30),'username'=>$username,'passwordHash'=>password_hash(account_password($row['password']??null),PASSWORD_DEFAULT),'role'=>$id===$admin?'admin':'member','authVersion'=>1]);
    }
    ksort($updated);$s['users']=array_values($updated);$s['accountsEnabled']=true;
}
function actor_for(array $s,array $session): ?array {
    if(!accounts_ready($s)||($session['expires']??0)<=time())return null;
    foreach($s['users'] as $u)if($u['id']===($session['userId']??null)&&($u['authVersion']??1)===($session['authVersion']??0))return $u;
    return null;
}
function authorize_action(array $s,array $actor,string $action,array &$in): void {
    $admin=['saveTask','deleteTask','saveReward','deleteReward','saveUser','createCompetition','cancelCompetition','claimCompetition','saveAccount'];
    if(in_array($action,$admin,true)&&($actor['role']??'member')!=='admin')throw new AppError('Bu işlem yalnızca admin hesabına açık.',403);
    if(in_array($action,['spin','selectGoal','complete','redeem','cancelAssignment'],true)){
        if(isset($in['userId'])&&$in['userId']!==$actor['id'])throw new AppError('Yalnızca kendi hesabında işlem yapabilirsin.',403);
        $in['userId']=$actor['id'];
    }
}
function member_snapshot(array $s,array $actor): array {
    $v=snapshot($s);$v['viewerId']=$actor['id'];$v['viewerRole']=$actor['role'];
    foreach($v['users'] as &$u){unset($u['passwordHash'],$u['authVersion']);if($u['id']!==$actor['id']){unset($u['goalRewardId'],$u['goalSelectedAt'],$u['goal'],$u['doneIds'],$u['eligibleTaskIds']);if($actor['role']!=='admin')unset($u['username']);}}unset($u);
    $v['assignments']=array_values(array_filter($v['assignments'],fn($a)=>$a['userId']===$actor['id']||($a['reviewerId']??null)===$actor['id']));
    foreach(['completions','redemptions'] as $key)$v[$key]=array_values(array_filter($v[$key],fn($a)=>$a['userId']===$actor['id']));
    return $v;
}
function save_account(array &$s,array $in): void {
    $i=find_index($s['users'],$in['id']??null,'Kullanıcı');$username=valid_username($in['username']??null);
    foreach($s['users'] as $j=>$u)if($j!==$i&&$u['username']===$username)throw new AppError('Bu kullanıcı adı kullanılıyor.');
    $s['users'][$i]['username']=$username;
    $s['users'][$i]['name']=valid_text($in['name']??null,'İsim',30);
    $s['users'][$i]['avatar']=valid_text($in['avatar']??null,'Simge',12);
    if(($in['password']??'')!==''){$s['users'][$i]['passwordHash']=password_hash(account_password($in['password']),PASSWORD_DEFAULT);$s['users'][$i]['authVersion']=($s['users'][$i]['authVersion']??1)+1;}
}
