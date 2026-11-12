<?php
// Explicitly requested local administrator recovery. Never runs on production.
if(parse_url(home_url(),PHP_URL_HOST)!=='alexandra-montessori.local') throw new RuntimeException('Local Alexandra only.');
$file=getenv('KRIL_ALEXANDRA_LOCAL_RECOVERY');
if(!$file || !str_starts_with(str_replace('\\','/',$file),'C:/Users/Dhananjay/.config/kril/')) throw new RuntimeException('Private local recovery configuration required.');
$config=json_decode(preg_replace('/^\xEF\xBB\xBF/','',file_get_contents($file)),true,512,JSON_THROW_ON_ERROR);
$owner=get_user_by('login',$config['login']);
if(!$owner || $owner->ID!==1 || !in_array('administrator',$owner->roles,true) || strlen($config['password'])<24) throw new RuntimeException('Expected the existing local owner.');
$backup=dirname($file).'/alexandra-before-local-account-recovery.json';
if(!is_file($backup))file_put_contents($backup,wp_json_encode(array('ID'=>$owner->ID,'hash'=>$owner->user_pass,'activation_key'=>$owner->user_activation_key,'sessions'=>get_user_meta($owner->ID,'session_tokens',true))));
wp_set_password($config['password'],$owner->ID);
$fresh=get_user_by('id',$owner->ID);
if(!wp_check_password($config['password'],$fresh->user_pass,$fresh->ID))throw new RuntimeException('Local password verification failed.');
echo "Existing local Alexandra administrator recovered; website content and plugins preserved. Credentials retained privately.\n";
