<?php
declare(strict_types=1);
// The HTML always points to the exact assets from this deployment.
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-LiteSpeed-Cache-Control: no-cache');
$template=file_get_contents(__DIR__.'/index.html');
foreach(['app.js','style.css','pwa.js'] as $asset){
    $version=substr(hash_file('sha256',__DIR__.'/'.$asset),0,16);
    $template=str_replace('"'.$asset.'"','"'.$asset.'?v='.$version.'"',$template);
}
echo $template;
