<?php
declare(strict_types=1);
// Android Chrome Web Push: a payload-free signal keeps task details off the lock screen.
final class TaskPush {
    public function __construct(private PDO $db) {
        $db->exec('CREATE TABLE IF NOT EXISTS homedojo_push_keys (id INT PRIMARY KEY, private_key TEXT NOT NULL, public_key TEXT NOT NULL)');
        $db->exec('CREATE TABLE IF NOT EXISTS homedojo_push_devices (id VARCHAR(64) PRIMARY KEY, user_id VARCHAR(100) NOT NULL, auth_version INT NOT NULL, endpoint TEXT NOT NULL, updated_at BIGINT NOT NULL)');
        $db->exec('CREATE TABLE IF NOT EXISTS homedojo_push_sent (id VARCHAR(64) PRIMARY KEY, created_at BIGINT NOT NULL)');
    }
    public static function b64(string $value): string {return rtrim(strtr(base64_encode($value),'+/','-_'),'=');}
    public static function endpoint(mixed $value): string {
        if(!is_string($value)||strlen($value)>2048||!preg_match('~^https://fcm\.googleapis\.com/(?:wp|fcm/send)/[A-Za-z0-9_:\-]+$~D',$value))throw new AppError('Bu cihaz için Android Chrome bildirimleri destekleniyor.',422);
        return $value;
    }
    public function keys(): array {
        $row=$this->db->query('SELECT * FROM homedojo_push_keys WHERE id=1')->fetch(PDO::FETCH_ASSOC);if($row)return $row;
        $key=openssl_pkey_new(['private_key_type'=>OPENSSL_KEYTYPE_EC,'curve_name'=>'prime256v1']);if(!$key||!openssl_pkey_export($key,$private))throw new RuntimeException('Push key generation failed');
        $details=openssl_pkey_get_details($key);$public=self::b64("\x04".$details['ec']['x'].$details['ec']['y']);
        try{$this->db->prepare('INSERT INTO homedojo_push_keys VALUES (1,?,?)')->execute([$private,$public]);}catch(PDOException $e){if(!$this->db->query('SELECT id FROM homedojo_push_keys WHERE id=1')->fetchColumn())throw $e;}
        return $this->db->query('SELECT * FROM homedojo_push_keys WHERE id=1')->fetch(PDO::FETCH_ASSOC);
    }
    public function subscribe(array $actor,array $input): void {
        if(!in_array($actor['memberType']??'', ['child','young_child','parent'],true))throw new AppError('Bu hesap bildirim kullanamaz.',403);
        $endpoint=self::endpoint($input['endpoint']??null);$id=hash('sha256',$endpoint);
        $count=$this->db->prepare('SELECT COUNT(*) FROM homedojo_push_devices WHERE user_id=?');$count->execute([$actor['id']]);
        $existing=$this->db->prepare('SELECT id FROM homedojo_push_devices WHERE id=?');$existing->execute([$id]);
        if(!$existing->fetchColumn()&&(int)$count->fetchColumn()>=10)throw new AppError('En fazla 10 cihazda bildirim açılabilir.',422);
        $mysql=$this->db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql';
        $sql='INSERT INTO homedojo_push_devices (id,user_id,auth_version,endpoint,updated_at) VALUES (?,?,?,?,?)'.($mysql?' ON DUPLICATE KEY UPDATE user_id=VALUES(user_id),auth_version=VALUES(auth_version),updated_at=VALUES(updated_at)':' ON CONFLICT(id) DO UPDATE SET user_id=excluded.user_id,auth_version=excluded.auth_version,updated_at=excluded.updated_at');
        $this->db->prepare($sql)->execute([$id,$actor['id'],$actor['authVersion'],$endpoint,time()]);
        $_SESSION['pushDevice']=$id;
    }
    public function remove(string $id,string $user): void {$this->db->prepare('DELETE FROM homedojo_push_devices WHERE id=? AND user_id=?')->execute([$id,$user]);}
    public static function signature(string $der): string {
        // OpenSSL DER ECDSA integers -> fixed-width JOSE R || S (RFC 7515).
        $pos=2;if(ord($der[1])&128)$pos+=ord($der[1])&127;
        $out='';for($i=0;$i<2;$i++){if(ord($der[$pos++])!==2)throw new RuntimeException('Invalid signature');$len=ord($der[$pos++]);$n=ltrim(substr($der,$pos,$len),"\0");$pos+=$len;if(strlen($n)>32)throw new RuntimeException('Invalid signature length');$out.=str_pad($n,32,"\0",STR_PAD_LEFT);}return $out;
    }
    public function authorization(): string {
        $keys=$this->keys();$token=self::b64('{"typ":"JWT","alg":"ES256"}').'.'.self::b64(json_encode(['aud'=>'https://fcm.googleapis.com','exp'=>time()+3600,'sub'=>'https://yapeglen.com/'],JSON_THROW_ON_ERROR));
        if(!openssl_sign($token,$signature,$keys['private_key'],OPENSSL_ALGO_SHA256))throw new RuntimeException('Push signing failed');
        return 'vapid t='.$token.'.'.self::b64(self::signature($signature)).', k='.$keys['public_key'];
    }
    public function deliver(array $child,string $event,?callable $transport=null,int $ttl=86400): array {
        $q=$this->db->prepare('SELECT * FROM homedojo_push_devices WHERE user_id=? AND auth_version=?');$q->execute([$child['id'],$child['authVersion']]);$devices=$q->fetchAll(PDO::FETCH_ASSOC);$sent=0;$failed=0;
        foreach($devices as $device){
            $id=hash('sha256',$event.':'.$device['id']);
            try{$this->db->prepare('INSERT INTO homedojo_push_sent VALUES (?,?)')->execute([$id,time()]);}catch(PDOException $e){if($e->getCode()==='23000')continue;throw $e;}
            try{
                $endpoint=self::endpoint($device['endpoint']);
                if($transport)$status=$transport($endpoint,$this->authorization());
                else{$ch=curl_init($endpoint);curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>'',CURLOPT_HTTPHEADER=>['Authorization: '.$this->authorization(),'TTL: '.max(0,min(86400,$ttl)),'Urgency: high','Content-Length: 0'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>5,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS]);curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);}
                if($status>=200&&$status<300)$sent++;
                elseif(in_array($status,[404,410],true))$this->remove($device['id'],$child['id']);
                else{$failed++;$this->db->prepare('DELETE FROM homedojo_push_sent WHERE id=?')->execute([$id]);}
            }catch(Throwable $e){$failed++;$this->db->prepare('DELETE FROM homedojo_push_sent WHERE id=?')->execute([$id]);error_log('HomeDojo push delivery failed');}
        }
        $this->db->prepare('DELETE FROM homedojo_push_sent WHERE created_at<?')->execute([time()-7*86400]);
        return ['devices'=>count($devices),'sent'=>$sent,'failed'=>$failed];
    }
}
