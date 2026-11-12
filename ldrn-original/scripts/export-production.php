<?php
// WP CLI only. A consistent export; the local database is never rewritten.
if(!defined('WP_CLI') || !WP_CLI || parse_url(home_url(),PHP_URL_HOST)!=='lrdn.local')throw new RuntimeException('Local release export only.');
$destination=getenv('LDRN_RELEASE_SQL');
$url=getenv('LDRN_RELEASE_URL');
if(!$destination || $url!=='https://ldrn.krildigital.com')throw new RuntimeException('Expected the dedicated LDRN release target.');
$parent=realpath(dirname($destination));$public=realpath(ABSPATH);
if(!$parent || str_starts_with(strtolower(str_replace('\\','/',$parent)),strtolower(str_replace('\\','/',$public)).'/'))throw new RuntimeException('SQL must stay outside the public website.');
function ldrn_release_rewrite($value,$from,$to) {
    if(is_array($value)) {
        $out=array();foreach($value as $key=>$item)$out[is_string($key)?str_replace($from,$to,$key):$key]=ldrn_release_rewrite($item,$from,$to);return $out;
    }
    if(is_object($value)) {foreach(get_object_vars($value) as $key=>$item)$value->$key=ldrn_release_rewrite($item,$from,$to);return $value;}
    if(!is_string($value))return $value;
    if(is_serialized($value))return serialize(ldrn_release_rewrite(unserialize($value),$from,$to));
    if(preg_match('/^\s*[\[{]/',$value)) {
        $json=json_decode($value);
        if(json_last_error()===JSON_ERROR_NONE)return wp_json_encode(ldrn_release_rewrite($json,$from,$to));
    }
    return str_replace($from,$to,$value);
}
global $wpdb;
$handle=fopen($destination,'xb');if(!$handle)throw new RuntimeException('Cannot create private SQL export.');
$tables=$wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($wpdb->prefix).'%'));
$wpdb->query('START TRANSACTION WITH CONSISTENT SNAPSHOT');
try {
    // WordPress core uses zero date defaults. Import under its compatible SQL
    // mode rather than the server's strict NO_ZERO_DATE default.
    fwrite($handle,"SET NAMES utf8mb4;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET FOREIGN_KEY_CHECKS=0;\n");
    foreach($tables as $table) {
        if(!preg_match('/^[a-zA-Z0-9_]+$/',$table))throw new RuntimeException('Unexpected table name.');
        $schema=$wpdb->get_row('SHOW CREATE TABLE `'.$table.'`',ARRAY_N);
        fwrite($handle,$schema[1].";\n");
        for($offset=0;;$offset+=500) {
            $rows=$wpdb->get_results('SELECT * FROM `'.$table.'` LIMIT 500 OFFSET '.$offset,ARRAY_A);
            if(!$rows)break;
            foreach($rows as $row) {
                $values=array();
                foreach($row as $key=>$value) {
                    if($value!==null && $key!=='guid')$value=ldrn_release_rewrite($value,home_url(),$url);
                    // _real_escape protects percent signs for wpdb::prepare.
                    // This exporter writes SQL directly, so restore literal %
                    // before writing to preserve serialized string lengths.
                    $values[]=$value===null?'NULL':"'".$wpdb->remove_placeholder_escape($wpdb->_real_escape($value))."'";
                }
                fwrite($handle,'INSERT INTO `'.$table.'` (`'.implode('`,`',array_keys($row)).'`) VALUES ('.implode(',',$values).");\n");
            }
        }
    }
    fwrite($handle,"SET FOREIGN_KEY_CHECKS=1;\n");
    $wpdb->query('COMMIT');
    file_put_contents($parent.'/database-metadata.json',wp_json_encode(array('prefix'=>$wpdb->prefix,'tables'=>count($tables),'wordpress'=>get_bloginfo('version'))));
}finally{fclose($handle);$wpdb->query('ROLLBACK');}
echo count($tables).' WordPress tables exported into the private release.'.PHP_EOL;
