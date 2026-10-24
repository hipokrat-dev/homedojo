<?php
declare(strict_types=1);
// CLI-only: no public scheduler URL, token or account information is exposed.
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}

function due_plan_reminders(array $state,int $now): array {
    $due=[];$zone=new DateTimeZone('Europe/Istanbul');
    foreach($state['users']??[] as $user){
        if(!empty($user['archivedAt'])||!in_array($user['memberType']??'',['parent','child'],true))continue;
        foreach($user['personalPlan']??[] as $plan){
            if(($plan['kind']??'')!=='todo'||!empty($plan['done'])||empty($plan['reminder'])||empty($plan['time']))continue;
            $value=($plan['day']??'').' '.$plan['time'];
            $when=DateTimeImmutable::createFromFormat('!Y-m-d H:i',$value,$zone);
            if(!$when||$when->format('Y-m-d H:i')!==$value)continue;
            $at=$when->getTimestamp();
            // Retry briefly after transient failures; never send yesterday's reminders.
            if($at>$now||$at<$now-300)continue;
            $due[]=['user'=>$user,'event'=>'plan:'.$plan['id'].':'.$value];
        }
    }
    return $due;
}

if(realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){
    // Match the web deployment's private configuration directory when run by cron.
    if(!getenv('HOMEDOJO_CONFIG_DIR'))$_SERVER['DOCUMENT_ROOT']=dirname(__DIR__);
    require_once __DIR__.'/bootstrap.php';require_once __DIR__.'/push.php';
    $lock=fopen(sys_get_temp_dir().'/homedojo-reminders-'.sha1(__DIR__).'.lock','c');
    if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit(0);
    try{
        $store=new Store(configuration());$push=new TaskPush($store->db);$sent=0;$failed=0;$start=time();
        foreach(due_plan_reminders($store->read(),time()) as $item){
            if(time()-$start>45)break;
            $result=$push->deliver($item['user'],$item['event'],null,300);
            $sent+=$result['sent'];$failed+=$result['failed'];
        }
        echo gmdate('c')." reminders sent=$sent failed=$failed\n";
        exit($failed?1:0);
    }catch(Throwable $e){error_log('HomeDojo reminder job failed');exit(1);}
}
