<?php
// Assemble the private configuration without logging any password.
if(!defined('WP_CLI') || !WP_CLI || parse_url(home_url(),PHP_URL_HOST)!=='lrdn.local')throw new RuntimeException('Local configuration preparation only.');
$folder='C:/Users/Dhananjay/Local Sites/lrdn/private-releases';
$target=$folder.'/deployment-config.json';
if(is_file($target)){echo "Existing private deployment configuration retained.\n";return;}
$connection=json_decode(file_get_contents($folder.'/hosting-connection.json'),true);
$admins=get_users(['role'=>'administrator','number'=>1,'orderby'=>'ID','order'=>'ASC']);
if(!$connection || !$admins || !is_email($admins[0]->user_email))throw new RuntimeException('Verified hosting connection and local owner are required.');
$config=['site_url'=>'https://ldrn.krildigital.com','ftp'=>$connection,
    'database'=>['host'=>'127.0.0.1','port'=>3306,'name'=>'','user'=>'','password'=>'','dedicated_empty_database'=>true],
    'administrator'=>['login'=>$admins[0]->user_login,'email'=>$admins[0]->user_email,'password'=>wp_generate_password(40,true,true)]];
file_put_contents($target,wp_json_encode($config,JSON_PRETTY_PRINT));
echo "Private deployment configuration prepared. Only the existing production database connection remains to be supplied.\n";
