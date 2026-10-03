<?php
require __DIR__.'/../app/bootstrap.php';
$private=['public_url'=>'https://old.example','host'=>'fixture-db','password'=>'fixture-only','environment'=>'production'];
$current=apply_site_settings($private);
if($current['public_url']!=='https://yapeglen.com'||$current['host']!==$private['host']||$current['password']!==$private['password'])throw new RuntimeException('Production URL must change without changing database configuration');
$dev=['environment'=>'development','public_url'=>'http://localhost:3000'];if(apply_site_settings($dev)!==$dev)throw new RuntimeException('Local origins must remain unchanged');
echo "✓ Canonical production URL and unchanged local/database configuration checks passed.\n";
