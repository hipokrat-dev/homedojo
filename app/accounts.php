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
        $updated[$i]=array_merge($s['users'][$i],['name'=>$id===$admin?'Baba':valid_text($row['name']??null,'İsim',30),'username'=>$username,'passwordHash'=>password_hash(account_password($row['password']??null),PASSWORD_DEFAULT),'role'=>$id===$admin?'admin':'member','authVersion'=>1,'memberType'=>$id===$admin||mb_strtolower(trim($row['name']??''),'UTF-8')==='anne'?'parent':'child']);
    }
    ksort($updated);$s['users']=array_values($updated);$s['accountsEnabled']=true;
}
function actor_for(array $s,array $session): ?array {
    if(!accounts_ready($s)||($session['expires']??0)<=time())return null;
    foreach($s['users'] as $u)if(empty($u['archivedAt'])&&$u['id']===($session['userId']??null)&&($u['authVersion']??1)===($session['authVersion']??0))return $u;
    return null;
}
function authorize_action(array $s,array $actor,string $action,array &$in): void {
    $admin=['saveTask','deleteTask','saveReward','deleteReward','saveUser','createCompetition','cancelCompetition','claimCompetition','saveAccount','createAccount','archiveAccount','restoreAccount','saveRoutines'];
    if(in_array($action,$admin,true)&&($actor['role']??'member')!=='admin')throw new AppError('Bu işlem yalnızca admin hesabına açık.',403);
    if(($actor['memberType']??'')==='young_child'&&in_array($action,['spin','selectGoal','complete','redeem','cancelAssignment','reviewAssignment'],true))throw new AppError('Küçük çocuk hesabında günlük görev ekranını kullan.',403);
    if(in_array($action,['spin','selectGoal','complete','redeem','cancelAssignment'],true)){
        if(isset($in['userId'])&&$in['userId']!==$actor['id'])throw new AppError('Yalnızca kendi hesabında işlem yapabilirsin.',403);
        $in['userId']=$actor['id'];
    }
}
function member_snapshot(array $s,array $actor): array {
    $v=snapshot($s);$v['viewerId']=$actor['id'];$v['viewerRole']=$actor['role'];
    foreach($v['users'] as &$u){unset($u['passwordHash'],$u['authVersion'],$u['routineHistory'],$u['routineTrackingSince'],$u['routineTrackingAt']);if($u['id']!==$actor['id']){unset($u['goalRewardId'],$u['goalSelectedAt'],$u['goal'],$u['doneIds'],$u['eligibleTaskIds']);if($actor['role']!=='admin')unset($u['username']);}}unset($u);
    $v['assignments']=array_values(array_filter($v['assignments'],fn($a)=>$a['userId']===$actor['id']||($a['reviewerId']??null)===$actor['id']));
    foreach(['completions','redemptions'] as $key)$v[$key]=array_values(array_filter($v[$key],fn($a)=>$a['userId']===$actor['id']));
    if($actor['role']!=='admin')foreach(['tasks','rewards'] as $key)$v[$key]=array_values(array_filter($v[$key],fn($r)=>$key==='tasks'?task_visible_to($r,$actor['id']):$r['ownerId']===$actor['id']));
    $v['archivedUsers']=$actor['role']==='admin'?array_values(array_map(fn($u)=>array_intersect_key($u,array_flip(['id','name','username','avatar','memberType','archivedAt'])),array_filter($v['users'],fn($u)=>!empty($u['archivedAt'])))):[];
    $v['users']=array_values(array_filter($v['users'],fn($u)=>empty($u['archivedAt'])));
    foreach($v['users'] as &$u)if($actor['role']!=='admin'&&$u['id']!==$actor['id'])unset($u['routineSchedule'],$u['guardianId']);unset($u);
    if(has_daily_program($actor)){$v['dailyRoutines']=daily_routines($s,$actor);$v['guardianName']=routine_guardian($s,$actor)['name'];}
    $v['canViewProgress']=($actor['memberType']??'')==='parent'||$actor['role']==='admin';
    return $v;
}
function save_account(array &$s,array $in): void {
    $i=active_user_index($s,$in['id']??null);$old=$s['users'][$i];$username=valid_username($in['username']??null);
    foreach($s['users'] as $j=>$u)if($j!==$i&&($u['username']??'')===$username)throw new AppError('Bu kullanıcı adı kullanılıyor.');
    $type=$in['memberType']??$old['memberType']??'child';
    if(!in_array($type,['parent','child','young_child'],true))throw new AppError('Kullanıcı türü geçersiz.');
    if(($old['role']??'')==='admin'&&$type!=='parent')throw new AppError('Admin hesabı ebeveyn olarak kalmalı.');
    if($type!=='parent')foreach($s['users'] as $u)if(empty($u['archivedAt'])&&($u['guardianId']??'')===$old['id'])throw new AppError('Önce bu ebeveyne bağlı çocukların onaycısını değiştir.');
    $updated=array_merge($old,['username'=>$username,'name'=>valid_text($in['name']??null,'İsim',30),'avatar'=>valid_text($in['avatar']??null,'Simge',12),'memberType'=>$type]);
    if(in_array($type,['child','young_child'],true)){
        $guardian=$in['guardianId']??routine_guardian($s,$updated)['id'];$p=$s['users'][active_user_index($s,$guardian,'Ebeveyn')];
        if($p['id']===$old['id']||($p['memberType']??'')!=='parent')throw new AppError('Başka bir ebeveyn seç.');
        $updated['guardianId']=$guardian;$updated['routineSchedule'] ??= default_routines();
    }else unset($updated['guardianId']);
    if(($in['password']??'')!==''){$updated['passwordHash']=password_hash(account_password($in['password']),PASSWORD_DEFAULT);$updated['authVersion']=($old['authVersion']??1)+1;}
    if(($old['memberType']??'')!==$type||!isset($updated['routineHistory']))record_routine_program($updated);
    $s['users'][$i]=$updated;
    // A newly selected guardian receives pending routine approvals too.
    foreach($s['assignments'] as &$a)if($a['status']==='pending'){
        if($a['userId']===$old['id']&&isset($a['routineId'])&&in_array($type,['child','young_child'],true))$a['reviewerId']=$updated['guardianId'];
        elseif(($a['reviewerId']??'')===$old['id']&&$type==='young_child'){
            $replacement=routine_guardian($s,$updated)['id'];
            if($replacement===$a['userId']){$replacement=null;foreach($s['users'] as $p)if(empty($p['archivedAt'])&&$p['id']!==$a['userId']&&$p['memberType']!=='young_child'){$replacement=$p['id'];break;}}
            if($replacement)$a['reviewerId']=$replacement;else{$a['status']='active';unset($a['reviewerId'],$a['submittedAt']);}
        }
    }unset($a);
}
function create_account(array &$s,array $in): void {
    if(count($s['users'])>=100)throw new AppError('En fazla 100 kullanıcı kaydı tutulabilir.');
    $id=uid();$candidate=$s;$candidate['users'][]=['id'=>$id,'name'=>'Yeni üye','avatar'=>'🌟','username'=>'','role'=>'member','authVersion'=>1,'memberType'=>'child'];
    $in['id']=$id;account_password($in['password']??null);save_account($candidate,$in);$s=$candidate;
}
function archive_account(array &$s,array $actor,array $in): void {
    $i=active_user_index($s,$in['id']??null);$u=$s['users'][$i];
    if($u['id']===$actor['id']||($u['role']??'')==='admin')throw new AppError('Admin hesabı çıkarılamaz.',409);
    $at=now_tr()->format(DateTimeInterface::ATOM);$s['users'][$i]['archivedAt']=$at;record_routine_program($s['users'][$i]);$s['users'][$i]['authVersion']=($u['authVersion']??1)+1;
    foreach($s['users'] as &$child)if(($child['guardianId']??'')===$u['id'])$child['guardianId']=$actor['id'];unset($child);
    foreach($s['assignments'] as &$a){
        if($a['userId']===$u['id']&&in_array($a['status'],['active','pending'],true)){$a['status']='cancelled';$a['cancelledAt']=$at;}
        elseif($a['status']==='pending'&&($a['reviewerId']??'')===$u['id']){
            $replacement=null;foreach($s['users'] as $p)if(empty($p['archivedAt'])&&$p['id']!==$a['userId']&&$p['id']!==$u['id']&&($p['memberType']??'')!=='young_child'){$replacement=$p['id'];if(($p['role']??'')==='admin')break;}
            if($replacement)$a['reviewerId']=$replacement;
            else{$a['status']='active';unset($a['reviewerId'],$a['submittedAt']);}
        }
    }unset($a);
}
function restore_account(array &$s,array $in): void {
    $i=find_index($s['users'],$in['id']??null,'Kullanıcı');if(empty($s['users'][$i]['archivedAt']))throw new AppError('Bu kullanıcı zaten aktif.',409);unset($s['users'][$i]['archivedAt']);record_routine_program($s['users'][$i]);$s['users'][$i]['authVersion']=($s['users'][$i]['authVersion']??1)+1;
}

function self_user_index(array $s,array $actor,array $in): int {
    foreach(['id','userId'] as $key)if(isset($in[$key])&&$in[$key]!==$actor['id'])throw new AppError('Yalnızca kendi profilini değiştirebilirsin.',403);
    return find_index($s['users'],$actor['id'],'Kullanıcı');
}
function change_credentials(array &$s,array $actor,array $in): void {
    $i=self_user_index($s,$actor,$in);$current=$in['currentPassword']??'';
    if(!is_string($current)||strlen($current)>72||!password_verify($current,$s['users'][$i]['passwordHash']))throw new AppError('Mevcut şifren doğru değil.',422);
    $username=valid_username($in['username']??null);$password=$in['password']??'';
    if(!is_string($password))throw new AppError('Şifre geçersiz.');
    foreach($s['users'] as $j=>$u)if($j!==$i&&$u['username']===$username)throw new AppError('Bu kullanıcı adı kullanılıyor.',409);
    if($password!==''){
        account_password($password);
        if($password!==($in['passwordConfirm']??null))throw new AppError('Yeni şifreler eşleşmiyor.');
        $s['users'][$i]['passwordHash']=password_hash($password,PASSWORD_DEFAULT);
    }
    $s['users'][$i]['username']=$username;
    $s['users'][$i]['authVersion']=($s['users'][$i]['authVersion']??1)+1;
}
function normalized_photo(mixed $value): string {
    if(!is_string($value)||strlen($value)>220000||!preg_match('~^data:image/(jpeg|png);base64,([A-Za-z0-9+/=]+)$~D',$value,$match))throw new AppError('Geçerli bir JPG veya PNG fotoğraf seç.');
    $bytes=base64_decode($match[2],true);$info=$bytes===false?false:@getimagesizefromstring($bytes);
    if(!$info||!in_array($info[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG],true)||$info[0]>512||$info[1]>512||$info[0]<1||$info[1]<1)throw new AppError('Fotoğraf işlenemedi. Başka bir fotoğraf seç.');
    if(!function_exists('imagecreatefromstring'))throw new AppError('Sunucuda fotoğraf işleme özelliği etkin değil.',503);
    $source=@imagecreatefromstring($bytes);if(!$source)throw new AppError('Fotoğraf okunamadı.');
    $dest=imagecreatetruecolor(256,256);imagefill($dest,0,0,imagecolorallocate($dest,245,242,252));$side=min($info[0],$info[1]);
    imagecopyresampled($dest,$source,0,0,(int)(($info[0]-$side)/2),(int)(($info[1]-$side)/2),256,256,$side,$side);
    ob_start();imagejpeg($dest,null,85);$output=ob_get_clean();
    return 'data:image/jpeg;base64,'.base64_encode($output);
}
function save_photo(array &$s,array $actor,array $in): void {
    $i=self_user_index($s,$actor,$in);
    if(($in['remove']??false)===true){unset($s['users'][$i]['photo']);return;}
    $s['users'][$i]['photo']=normalized_photo($in['photo']??null);
}
