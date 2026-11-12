<?php
/** One-use, authenticated FTP-only activation. Data/configuration stay private. */
declare(strict_types=1);
header('Content-Type: application/json');header('Cache-Control: no-store');ini_set('display_errors','0');
function result(array $value,int $code=200): never {http_response_code($code);echo json_encode($value);exit;}
$release='__RELEASE__';$base='__BASE__';
foreach([$release,$base] as $id)if(!preg_match('/^[0-9]{8}T[0-9]{6}Z-[a-f0-9]{8}$/D',$id))result(['ok'=>false],404);
$domain=dirname(__DIR__,2);$private=$domain.'/ldrn-releases/'.$release;
$config=json_decode((string)@file_get_contents($private.'/sqlite-config.json'),true);
if(!$config || ($_SERVER['REQUEST_METHOD'] ?? '')!=='POST' || !hash_equals($config['authorization_sha256'] ?? '',hash('sha256',$_SERVER['HTTP_AUTHORIZATION'] ?? '')) || time()>($config['expires'] ?? 0))result(['ok'=>false],404);
$mode=json_decode((string)file_get_contents('php://input'),true)['mode'] ?? '';
$status=$private.'/sqlite-status.json';$live=$domain.'/public_html/ldrn';$staged=$private.'/public';$backup=$domain.'/ldrn-backups/'.$release;
if($mode==='status')result(json_decode((string)@file_get_contents($status),true) ?: ['ok'=>true,'state'=>'uploaded']);
$lock=fopen($private.'/sqlite-install.lock','c');if(!$lock || !flock($lock,LOCK_EX|LOCK_NB))result(['ok'=>false,'message'=>'Deployment running.'],409);
function state(string $name): void {global $status;file_put_contents($status,json_encode(['ok'=>true,'state'=>$name]),LOCK_EX);}
function copy_site(string $source,string $target): void {
    if(is_link($source))throw new RuntimeException('Symlink in private source.');
    if(!is_dir($target) && !mkdir($target,0755,true))throw new RuntimeException('Cannot create staged directory.');
    foreach(scandir($source) as $name)if($name!=='.' && $name!=='..') {
        if(is_link($source.'/'.$name))throw new RuntimeException('Symlink in release.');
        if(is_dir($source.'/'.$name))copy_site($source.'/'.$name,$target.'/'.$name);
        elseif(!copy($source.'/'.$name,$target.'/'.$name))throw new RuntimeException('Cannot copy private release.');
    }
}
try {
    set_time_limit(240);ini_set('memory_limit','512M');
    if(realpath(__DIR__)!==realpath($live) || is_file($live.'/wp-config.php'))throw new RuntimeException('Expected the current static LDRN target.');
    if(!extension_loaded('pdo_sqlite') || version_compare((string)(new PDO('sqlite::memory:'))->query('SELECT sqlite_version()')->fetchColumn(),'3.37.0','<'))throw new RuntimeException('SQLite runtime unavailable.');
    if($mode==='prepare') {
        if(is_file($staged.'/wp-config.php'))throw new RuntimeException('This release was already prepared.');
        if(!hash_equals($config['overlay_sha256'],hash_file('sha256',$private.'/overlay.tar.gz')))throw new RuntimeException('Private overlay hash mismatch.');
        copy_site($domain.'/ldrn-releases/'.$base.'/public',$staged);
        $archive=new PharData($private.'/overlay.tar.gz');$archive->extractTo($private,null,true);
        $manifest=json_decode(file_get_contents($private.'/sqlite-manifest.json'),true);
        if($manifest['release']!==$release || $manifest['site_url']!=='https://ldrn.krildigital.com')throw new RuntimeException('Release identity mismatch.');
        $root=realpath($staged);
        foreach($manifest['files'] as $rel=>$hash) {
            $file=realpath($staged.'/'.$rel);
            if(!$file || !str_starts_with($file,$root.DIRECTORY_SEPARATOR) || !hash_equals($hash,hash_file('sha256',$file)))throw new RuntimeException('Public file verification failed: '.$rel);
        }
        if(!hash_equals($manifest['database_sha256'],hash_file('sha256',$private.'/database/site.sqlite')))throw new RuntimeException('Database verification failed.');
        $db=new PDO('sqlite:'.$private.'/database/site.sqlite');
        if($db->query('PRAGMA integrity_check')->fetchColumn()!=='ok')throw new RuntimeException('Database integrity check failed.');
        $db=null;
        $wp="<?php\n";
        foreach(['DB_ENGINE'=>'sqlite','DB_NAME'=>'ldrn_sqlite','DB_USER'=>'','DB_PASSWORD'=>'','DB_HOST'=>'localhost','DB_CHARSET'=>'utf8mb4','DB_COLLATE'=>'','DB_DIR'=>$private.'/database/','DB_FILE'=>'site.sqlite','WP_HOME'=>'https://ldrn.krildigital.com','WP_SITEURL'=>'https://ldrn.krildigital.com'] as $key=>$value)$wp.='define('.var_export($key,true).','.var_export($value,true).");\n";
        foreach(['AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'] as $key)$wp.='define('.var_export($key,true).','.var_export(bin2hex(random_bytes(48)),true).");\n";
        $wp.="define('FORCE_SSL_ADMIN',true);define('DISALLOW_FILE_EDIT',true);define('WP_DEBUG',false);define('WP_DEBUG_DISPLAY',false);\n";
        $wp.='$table_prefix='.var_export($manifest['prefix'],true).";\nif(!defined('ABSPATH'))define('ABSPATH',__DIR__.'/');require_once ABSPATH.'wp-settings.php';\n";
        file_put_contents($staged.'/wp-config.php',$wp,LOCK_EX);chmod($staged.'/wp-config.php',0600);chmod($private.'/database/site.sqlite',0600);
        state('prepared');result(['ok'=>true,'state'=>'prepared','files'=>count($manifest['files'])]);
    }
    if($mode!=='apply' || (json_decode((string)file_get_contents($status),true)['state'] ?? '')!=='prepared')throw new RuntimeException('Prepare this release before activation.');
    require $staged.'/wp-load.php';require_once ABSPATH.'wp-admin/includes/plugin.php';
    $admin=$config['administrator'];$owner=get_user_by('login',$admin['login']);
    if(!$owner || !in_array('administrator',$owner->roles,true) || !wp_check_password($admin['password'],$owner->user_pass,$owner->ID))throw new RuntimeException('Production administrator verification failed.');
    if(get_option('template')!=='ldrn-theme' || !is_plugin_active('ldrn-workspace/ldrn-workspace.php') || !is_array(get_option(LDRN_WS_STUDIO_LIVE)) || count(ldrn_get_testimonials())<3)throw new RuntimeException('Content/editor contract failed.');
    wp_set_current_user($owner->ID);
    if(!ldrn_sqlite_backup())throw new RuntimeException('Private consistent backup failed.');
    if(!is_dir(dirname($backup)))mkdir(dirname($backup),0700,true);
    if(file_exists($backup))throw new RuntimeException('Backup already exists.');
    state('activating');chdir($domain);
    if(!rename($live,$backup))throw new RuntimeException('Cannot preserve original site.');
    if(!rename($staged,$live)){rename($backup,$live);throw new RuntimeException('Activation failed; original restored.');}
    @unlink($backup.'/'.basename(__FILE__));state('complete');
    result(['ok'=>true,'state'=>'complete','site'=>'https://ldrn.krildigital.com','engine'=>'sqlite','release'=>$release]);
}catch(Throwable $error) {
    file_put_contents($private.'/sqlite-failure.json',json_encode(['type'=>get_class($error),'message'=>$error->getMessage()]),LOCK_EX);
    result(['ok'=>false,'message'=>'Deployment stopped. Inspect private sqlite-failure.json.'],500);
}
