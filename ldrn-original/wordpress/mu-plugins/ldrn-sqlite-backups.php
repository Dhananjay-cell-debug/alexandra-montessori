<?php
/** Consistent private backups for the FTP-only SQLite installation. */
defined('ABSPATH') || exit;
if(!defined('DB_ENGINE') || DB_ENGINE!=='sqlite')return;
function ldrn_sqlite_backup(): bool {
    if(!defined('FQDB') || !is_file(FQDB))return false;
    $folder=dirname(FQDB).'/backups';
    if(!is_dir($folder) && !mkdir($folder,0700,true))return false;
    $lock=fopen($folder.'/backup.lock','c');
    if(!$lock || !flock($lock,LOCK_EX|LOCK_NB))return false;
    try {
        $db=new PDO('sqlite:'.FQDB,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $db->exec('PRAGMA busy_timeout=5000');
        $file=$folder.'/site-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sqlite';
        $db->exec('VACUUM INTO '.$db->quote($file));
        chmod($file,0600);
        $files=glob($folder.'/site-*.sqlite');rsort($files,SORT_STRING);
        foreach(array_slice($files,30) as $old)if(dirname($old)===$folder && preg_match('/^site-[0-9]{8}-[0-9]{6}-[a-f0-9]{8}\.sqlite$/',basename($old)))unlink($old);
        return true;
    }catch(Throwable $error){error_log('LDRN private SQLite backup failed.');return false;}
    finally{flock($lock,LOCK_UN);fclose($lock);}
}
add_action('init',function(){if(!wp_next_scheduled('ldrn_sqlite_daily_backup'))wp_schedule_event(time()+300,'daily','ldrn_sqlite_daily_backup');});
add_action('ldrn_sqlite_daily_backup','ldrn_sqlite_backup');
function ldrn_sqlite_mark_backup(): void {$GLOBALS['ldrn_sqlite_backup_needed']=true;}
add_action('ldrn_ws_studio_published','ldrn_sqlite_mark_backup');
add_action('save_post','ldrn_sqlite_mark_backup');
add_action('shutdown',function(){if(!empty($GLOBALS['ldrn_sqlite_backup_needed']))ldrn_sqlite_backup();},100);
