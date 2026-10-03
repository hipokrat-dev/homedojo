<?php
declare(strict_types=1);
require_once __DIR__.'/domain.php';
final class Store {
    public PDO $db;
    private bool $mysql;
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
    public function read(): array { $row=$this->db->query('SELECT payload FROM homedojo_state WHERE id=1')->fetchColumn(); if(!$row)throw new RuntimeException('Database is not initialized.');$state=json_decode($row,true,512,JSON_THROW_ON_ERROR);if(($state['version']??0)<8)return $this->update(function(array &$s): void {});return upgrade_state($state); }
    public function update(callable $fn): array {
        if($this->mysql)$this->db->beginTransaction();else $this->db->exec('BEGIN IMMEDIATE');
        try{$state=json_decode($this->db->query('SELECT payload FROM homedojo_state WHERE id=1'.($this->mysql?' FOR UPDATE':''))->fetchColumn(),true,512,JSON_THROW_ON_ERROR);$state=upgrade_state($state);$fn($state);$this->db->prepare('UPDATE homedojo_state SET payload=? WHERE id=1')->execute([json_encode($state,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);if($this->mysql)$this->db->commit();else $this->db->exec('COMMIT');return $state;}
        catch(Throwable $e){if($this->mysql)$this->db->rollBack();else $this->db->exec('ROLLBACK');throw $e;}
    }
    public function attemptLogin(string $ip): void {
        $hash=hash('sha256',$ip);$now=time();$this->db->prepare('DELETE FROM homedojo_login_limits WHERE reset_at < ?')->execute([$now]);
        $sql=$this->mysql?'INSERT INTO homedojo_login_limits VALUES (?,1,?) ON DUPLICATE KEY UPDATE attempts=attempts+1':'INSERT INTO homedojo_login_limits VALUES (?,1,?) ON CONFLICT(ip_hash) DO UPDATE SET attempts=attempts+1';
        $this->db->prepare($sql)->execute([$hash,$now+900]);$q=$this->db->prepare('SELECT attempts FROM homedojo_login_limits WHERE ip_hash=?');$q->execute([$hash]);if((int)$q->fetchColumn()>15)throw new AppError('Çok fazla giriş denemesi. 15 dakika sonra yeniden dene.',429);
    }
    public function clearLogin(string $ip): void { $this->db->prepare('DELETE FROM homedojo_login_limits WHERE ip_hash=?')->execute([hash('sha256',$ip)]); }
}
