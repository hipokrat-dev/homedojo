<?php
declare(strict_types=1);
require_once __DIR__.'/store.php';
function private_config_path(): string {
    $override=getenv('HOMEDOJO_CONFIG_DIR');
    if($override)return rtrim($override,'/').'/config.local.php';
    if(getenv('APP_ENV')!=='development' && !empty($_SERVER['DOCUMENT_ROOT']))return dirname(rtrim($_SERVER['DOCUMENT_ROOT'],'/')).'/homedojo-private/config.local.php';
    return __DIR__.'/config.local.php';
}
function configuration(): array {
    $private=private_config_path();
    if(is_file($private))return require $private;
    if(is_file(__DIR__.'/config.local.php'))return require __DIR__.'/config.local.php';
    if(getenv('APP_ENV')==='development')return ['environment'=>'development','driver'=>'sqlite','sqlite_path'=>getenv('SQLITE_PATH')?:__DIR__.'/../data/homedojo.sqlite','password_hash'=>'','public_url'=>'http://localhost:3000'];
    throw new AppError('İlk kurulum henüz tamamlanmadı. Site sahibi kurulum ekranından bağlantıyı tamamlamalı.',503);
}
function session_start_safe(): void {
    ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');ini_set('session.gc_maxlifetime','86400');
    session_name('homedojo_session');session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>getenv('APP_ENV')!=='development','httponly'=>true,'samesite'=>'Strict']);session_start();
    if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));
}
function json_response(array $body,int $status=200): never { http_response_code($status);echo json_encode($body,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);exit; }
