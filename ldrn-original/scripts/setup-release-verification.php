<?php
if(!defined('WP_CLI') || !WP_CLI || parse_url(home_url(),PHP_URL_HOST)!=='lrdn.local')throw new RuntimeException('Local verification only.');
$folder=getenv('LDRN_TEST_RELEASE_ROOT');$name=getenv('LDRN_TEST_DATABASE');
if(!preg_match('/^ldrn_release_verify_[a-f0-9]{8}$/D',$name ?: '') || !$folder || !str_contains(str_replace('\\','/',$folder),'/private-releases/verification-'))throw new RuntimeException('Invalid isolated verification target.');
global $wpdb;
if($wpdb->query('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')===false)throw new RuntimeException('Cannot create isolated verification database.');
$config=array('site_url'=>'https://ldrn.krildigital.com','database'=>array('host'=>'127.0.0.1','port'=>10029,'name'=>$name,'user'=>DB_USER,'password'=>DB_PASSWORD,'dedicated_empty_database'=>true),
    'administrator'=>array('login'=>'release_verifier','email'=>'verification@example.test','password'=>wp_generate_password(32,true,true)));
file_put_contents($folder.'/database-config.json',wp_json_encode($config));
echo 'Created isolated release-verification database; the local LDRN database is unchanged.'.PHP_EOL;
