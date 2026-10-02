<?php
// Production: save as ../homedojo-private/config.local.php outside public_html. Never commit credentials.
return [
    'environment'=>'production', 'driver'=>'mysql',
    'host'=>'localhost', 'port'=>3306,
    'database'=>'u123456789_homedojo', 'username'=>'u123456789_homedojo',
    'password'=>'YOUR_DATABASE_PASSWORD',
    // Generate with password_hash($householdPassword, PASSWORD_DEFAULT).
    'password_hash'=>'YOUR_HASH',
    'public_url'=>'https://olivedrab-barracuda-526213.hostingersite.com',
];
