<?php
if(!defined('DB_ENGINE') || DB_ENGINE!=='sqlite' || !str_contains(str_replace('\\','/',ABSPATH),'/private-releases/sqlite-evaluation-'))throw new RuntimeException('Isolated evaluation only.');
global $wpdb;
$raw=$wpdb->get_var($wpdb->prepare('SELECT option_value FROM '.$wpdb->options.' WHERE option_name=%s',LDRN_WS_STUDIO_LIVE));
$warning='';set_error_handler(function($level,$message)use(&$warning){$warning=$message;return true;});$decoded=unserialize($raw);restore_error_handler();
$value=['plain'=>'Test','nested'=>['colour'=>'#123456','label'=>'Testing “text”']];
$created=update_option('ldrn_eval_roundtrip',$value,false);$error=$wpdb->last_error;
$dbvalue=maybe_unserialize($wpdb->get_var($wpdb->prepare('SELECT option_value FROM '.$wpdb->options.' WHERE option_name=%s','ldrn_eval_roundtrip')));
$cached=get_option('ldrn_eval_roundtrip');wp_cache_flush();$reloaded=get_option('ldrn_eval_roundtrip');
echo wp_json_encode(['original_deserialization_warning'=>$warning,'created'=>$created,'write_error'=>$error,'db_roundtrip'=>$dbvalue===$value,'cached_roundtrip'=>$cached===$value,'reload_roundtrip'=>$reloaded===$value,'database_value_type'=>gettype($dbvalue)]).PHP_EOL;
delete_option('ldrn_eval_roundtrip');
