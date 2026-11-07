<?php
declare(strict_types=1);
require_once __DIR__.'/domain.php';
final class Store {
    public PDO $db;
    private bool $mysql;
    private string $familyCode='';
    public function __construct(array $config) {
        $this->mysql=($config['driver']??'mysql')==='mysql';
        if($this->mysql){
            $this->db=new PDO('mysql:host='.$config['host'].';port='.($config['port']??3306).';dbname='.$config['database'].';charset=utf8mb4',$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
        }else{
            if(($config['environment']??'production')!=='development')throw new RuntimeException('SQLite is development only.');
            $path=$config['sqlite_path']??__DIR__.'/../data/homedojo.sqlite';if($path!==':memory:'&&!is_dir(dirname($path)))mkdir(dirname($path),0700,true);
            $this->db=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$this->db->exec('PRAGMA busy_timeout=5000; PRAGMA journal_mode=WAL');
        }
    }
    public function initialize(): void {
        $this->db->exec('CREATE TABLE IF NOT EXISTS homedojo_state (id INT PRIMARY KEY, payload '.($this->mysql?'LONGTEXT':'TEXT').' NOT NULL)'.($this->mysql?' ENGINE=InnoDB':''));
        $sql=($this->mysql?'INSERT IGNORE':'INSERT OR IGNORE').' INTO homedojo_state (id,payload) VALUES (1,?)';$this->db->prepare($sql)->execute([json_encode(initial_state(),JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
        $this->db->exec('CREATE TABLE IF NOT EXISTS homedojo_login_limits (ip_hash VARCHAR(64) PRIMARY KEY, attempts INT NOT NULL, reset_at BIGINT NOT NULL)'.($this->mysql?' ENGINE=InnoDB':''));
    }
    public function ensureFamilies(): void {
        $this->db->exec('CREATE TABLE IF NOT EXISTS homedojo_families (code VARCHAR(32) PRIMARY KEY, payload '.($this->mysql?'LONGTEXT':'TEXT').' NOT NULL)'.($this->mysql?' ENGINE=InnoDB':''));
    }
    public function selectFamily(mixed $code): void {
        $code=self::familyCode($code);
        if($code!=='')$this->ensureFamilies();
        $this->familyCode=$code;
    }
    public static function familyCode(mixed $code): string {
        if(!is_string($code))throw new AppError('Aile kodu geçerli değil.');
        $code=strtolower(trim($code));
        if($code===''||$code==='bizim-ev')return '';
        if(!preg_match('/^[a-z0-9][a-z0-9-]{2,31}$/D',$code))throw new AppError('Aile kodu 3–32 İngilizce harf, rakam veya tire olmalı.');
        return $code;
    }
    public function registerFamily(array $state): void {
        $this->ensureFamilies();$code=self::familyCode($state['family']['code']??null);
        if($code==='')throw new AppError('Bu aile kodu kullanılıyor.',409);
        try{$this->db->prepare('INSERT INTO homedojo_families (code,payload) VALUES (?,?)')->execute([$code,json_encode($state,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);}
        catch(PDOException $e){if($e->getCode()==='23000')throw new AppError('Bu aile kodu kullanılıyor. Başka bir kod seç.',409);throw $e;}
        $this->familyCode=$code;
    }
    public function familyCodes(): array {
        $this->ensureFamilies();
        return $this->db->query('SELECT code FROM homedojo_families ORDER BY code')->fetchAll(PDO::FETCH_COLUMN);
    }
    private function payload(bool $lock=false): string {
        $sql=$this->familyCode===''?'SELECT payload FROM homedojo_state WHERE id=1':'SELECT payload FROM homedojo_families WHERE code=?';
        $q=$this->db->prepare($sql.($lock&&$this->mysql?' FOR UPDATE':''));
        $q->execute($this->familyCode===''?[]:[$this->familyCode]);$row=$q->fetchColumn();
        if($row===false)throw new AppError('Aile kodunu ve giriş bilgilerini kontrol et.',401);
        return $row;
    }
    public function read(): array {
        $state=json_decode($this->payload(),true,512,JSON_THROW_ON_ERROR);
        if(($state['version']??0)<9)return $this->update(function(array &$s): void {});
        return upgrade_state($state);
    }
    public function update(callable $fn): array {
        if($this->mysql)$this->db->beginTransaction();else $this->db->exec('BEGIN IMMEDIATE');
        try{
            $state=upgrade_state(json_decode($this->payload(true),true,512,JSON_THROW_ON_ERROR));
            $fn($state);$json=json_encode($state,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
            $sql=$this->familyCode===''?'UPDATE homedojo_state SET payload=? WHERE id=1':'UPDATE homedojo_families SET payload=? WHERE code=?';
            $this->db->prepare($sql)->execute($this->familyCode===''?[$json]:[$json,$this->familyCode]);
            if($this->mysql)$this->db->commit();else $this->db->exec('COMMIT');return $state;
        }catch(Throwable $e){if($this->mysql)$this->db->rollBack();else $this->db->exec('ROLLBACK');throw $e;}
    }
    public function attemptLogin(string $ip): void {
        $hash=hash('sha256',$ip);$now=time();$this->db->prepare('DELETE FROM homedojo_login_limits WHERE reset_at < ?')->execute([$now]);
        $sql=$this->mysql?'INSERT INTO homedojo_login_limits VALUES (?,1,?) ON DUPLICATE KEY UPDATE attempts=attempts+1':'INSERT INTO homedojo_login_limits VALUES (?,1,?) ON CONFLICT(ip_hash) DO UPDATE SET attempts=attempts+1';
        $this->db->prepare($sql)->execute([$hash,$now+900]);$q=$this->db->prepare('SELECT attempts FROM homedojo_login_limits WHERE ip_hash=?');$q->execute([$hash]);if((int)$q->fetchColumn()>15)throw new AppError('Çok fazla giriş denemesi. 15 dakika sonra yeniden dene.',429);
    }
    public function clearLogin(string $ip): void { $this->db->prepare('DELETE FROM homedojo_login_limits WHERE ip_hash=?')->execute([hash('sha256',$ip)]); }
}
