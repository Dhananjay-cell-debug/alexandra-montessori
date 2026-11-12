<?php
/** One-use LDRN installer. Configuration, SQL, lock and status stay outside public_html. */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
ini_set('display_errors','0');
function reply(array $body,int $status=200): never {http_response_code($status);echo json_encode($body);exit;}
$release='__LDRN_RELEASE_ID__';
if(!preg_match('/^[0-9]{8}T[0-9]{6}Z-[a-f0-9]{8}$/',$release))reply(['ok'=>false,'message'=>'Installer not configured.'],404);
$cli=PHP_SAPI==='cli';
// SSH runs the installer from the private release, outside the directory swap.
$installerLive=$cli && isset($argv[2]) ? realpath($argv[2]) : realpath(__DIR__);
if(!$installerLive || basename($installerLive)!=='ldrn' || basename(dirname($installerLive))!=='public_html')reply(['ok'=>false,'message'=>'Invalid installation target.'],404);
$domain=dirname($installerLive,2);
$private=$domain.'/ldrn-releases/'.$release;
$config=json_decode((string)@file_get_contents($private.'/remote-config.json'),true);
$authorization=$cli ? 'Bearer '.trim((string)stream_get_contents(STDIN)) : ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if(!$config || (!$cli && ($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') || !preg_match('/^Bearer ([A-Za-z0-9_-]+)$/D',$authorization,$token)
    || !hash_equals($config['token_sha256'] ?? '',hash('sha256',$token[1])))reply(['ok'=>false,'message'=>'Not found.'],404);
$request=$cli ? ['mode'=>$argv[1] ?? 'status'] : json_decode((string)file_get_contents('php://input'),true);
$statusFile=$private.'/status.json';
function status_write(string $state,string $message=''): void {
    global $statusFile;
    $GLOBALS['ldrn_install_phase']=$state;
    $body=['ok'=>$state==='complete','state'=>$state,'message'=>$message,'updated'=>gmdate('c')];
    $temp=$statusFile.'.tmp';
    if(file_put_contents($temp,json_encode($body),LOCK_EX)===false || !rename($temp,$statusFile))throw new RuntimeException('Cannot persist deployment status.');
}
if(($request['mode'] ?? '')==='status')reply(json_decode((string)@file_get_contents($statusFile),true) ?: ['ok'=>true,'state'=>'staged']);
if(($request['mode'] ?? '')!=='apply')reply(['ok'=>false,'message'=>'Unknown operation.'],400);
$lock=fopen($private.'/apply.lock','c');
if(!$lock || !flock($lock,LOCK_EX|LOCK_NB))reply(['ok'=>false,'message'=>'Deployment is already running. Inspect its status.'],409);
if(is_file($statusFile))reply(['ok'=>false,'message'=>'This release was already attempted. Inspect its status; do not repeat the database import.'],409);
$live=$domain.'/public_html/ldrn';$staged=$private.'/public';$backup=$domain.'/ldrn-backups/'.$release;
$swapped=false;
try {
    // File verification/import can exceed shared hosting's 30 second default.
    // A timed-out attempt remains locked and never blindly repeats an import.
    set_time_limit(240);
    status_write('checking');
    if(PHP_VERSION_ID<80200 || !extension_loaded('pdo_mysql') || !extension_loaded('zlib'))throw new RuntimeException('PHP 8.2+, PDO MySQL and zlib are required.');
    if($installerLive!==realpath($live) || is_file($live.'/wp-config.php') || is_dir($live.'/wp-admin'))throw new RuntimeException('The existing site is not the expected static LDRN target.');
    if(($config['site_url'] ?? '')!=='https://ldrn.krildigital.com' || ($config['database']['dedicated_empty_database'] ?? false)!==true)throw new RuntimeException('A dedicated empty LDRN database is required.');
    $manifest=json_decode((string)file_get_contents($private.'/manifest.json'),true);
    if(!$manifest || $manifest['release']!==$release || $manifest['site_url']!==$config['site_url'])throw new RuntimeException('Release identity mismatch.');
    $root=realpath($staged);
    foreach($manifest['files'] as $rel=>$hash) {
        $path=realpath($staged.'/'.$rel);
        if(!$path || !str_starts_with($path,$root.DIRECTORY_SEPARATOR) || !is_file($path) || !hash_equals($hash,hash_file('sha256',$path)))throw new RuntimeException('The staged release is incomplete.');
    }
    if(!hash_equals($manifest['sql_sha256'],hash_file('sha256',$private.'/database.sql.gz')))throw new RuntimeException('The database export does not match this release.');
    $db=$config['database'];
    $admin=$config['administrator'];
    if(strlen($admin['password'] ?? '')<20 || !filter_var($admin['email'] ?? '',FILTER_VALIDATE_EMAIL) || !preg_match('/^[A-Za-z0-9_.@-]+$/D',$admin['login'] ?? ''))throw new RuntimeException('Administrator settings are invalid.');
    if(!preg_match('/^[A-Za-z0-9_.-]+$/D',$db['host']) || !preg_match('/^[A-Za-z0-9_]+$/D',$db['name']))throw new RuntimeException('Invalid database connection settings.');
    $port=(int)($db['port'] ?? 3306);
    if($port<1 || $port>65535)throw new RuntimeException('Invalid database port.');
    $pdo=new PDO('mysql:host='.$db['host'].';port='.$port.';dbname='.$db['name'].';charset=utf8mb4',$db['user'],$db['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("SET SESSION SQL_MODE='NO_AUTO_VALUE_ON_ZERO'");
    if($pdo->query('SHOW TABLES')->fetchColumn()!==false)throw new RuntimeException('The target database is not empty. No existing database was overwritten.');
    $prefix=$manifest['database']['prefix'];
    if(!preg_match('/^[A-Za-z0-9_]+$/D',$prefix))throw new RuntimeException('Invalid WordPress table prefix.');
    status_write('importing');
    // Exported INSERT values escape newlines; CREATE TABLE spans multiple lines.
    // Split only semicolons outside SQL strings/backticks, never shell-import SQL.
    $stream=gzopen($private.'/database.sql.gz','rb');
    if(!$stream)throw new RuntimeException('Cannot read the database export.');
    $statement='';$quote=null;$escaped=false;
    while(!gzeof($stream)) {
        $chunk=gzread($stream,1048576);
        if($chunk===false)throw new RuntimeException('Cannot decompress the database export.');
        for($i=0,$length=strlen($chunk);$i<$length;$i++) {
            $char=$chunk[$i];$statement.=$char;
            if($quote!==null) {
                if($escaped){$escaped=false;continue;}
                if($char==='\\'){$escaped=true;continue;}
                if($char===$quote)$quote=null;
            }elseif($char==="'" || $char==='"' || $char==='`')$quote=$char;
            elseif($char===';'){$pdo->exec($statement);$statement='';}
        }
    }
    gzclose($stream);
    if(trim($statement)!=='' || $quote!==null)throw new RuntimeException('The database export ended unexpectedly.');
    if((int)$pdo->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchColumn()!==$manifest['database']['tables'])throw new RuntimeException('The database import is incomplete.');
    $wpConfig="<?php\n";
    foreach(['DB_NAME'=>$db['name'],'DB_USER'=>$db['user'],'DB_PASSWORD'=>$db['password'],'DB_HOST'=>$db['host'].($port!==3306 ? ':'.$port : ''),'DB_CHARSET'=>'utf8mb4','DB_COLLATE'=>'',
        'WP_HOME'=>$config['site_url'],'WP_SITEURL'=>$config['site_url']] as $key=>$value)$wpConfig.='define('.var_export($key,true).','.var_export($value,true).");\n";
    foreach(['AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'] as $key)$wpConfig.='define('.var_export($key,true).','.var_export(bin2hex(random_bytes(48)),true).");\n";
    $wpConfig.="define('FORCE_SSL_ADMIN',true);\ndefine('DISALLOW_FILE_EDIT',true);\ndefine('WP_DEBUG',false);\ndefine('WP_DEBUG_DISPLAY',false);\n";
    $wpConfig.='$table_prefix='.var_export($prefix,true).";\n";
    $wpConfig.="if(!defined('ABSPATH'))define('ABSPATH',__DIR__.'/');\nrequire_once ABSPATH.'wp-settings.php';\n";
    if(file_put_contents($staged.'/wp-config.php',$wpConfig,LOCK_EX)===false)throw new RuntimeException('Cannot write WordPress configuration.');
    chmod($staged.'/wp-config.php',0600);
    require $staged.'/wp-load.php';
    require_once ABSPATH.'wp-admin/includes/plugin.php';
    $admin=$config['administrator'];
    if(strlen($admin['password'])<20 || !is_email($admin['email']) || sanitize_user($admin['login'],true)!==$admin['login'])throw new RuntimeException('Administrator settings are invalid.');
    $existing=get_user_by('login',$admin['login']);
    if($existing && !in_array('administrator',$existing->roles,true))throw new RuntimeException('Configured administrator login belongs to another role.');
    $id=$existing ? $existing->ID : wp_create_user($admin['login'],$admin['password'],$admin['email']);
    if(is_wp_error($id))throw new RuntimeException('Cannot configure the administrator.');
    $account=new WP_User($id);$account->set_role('administrator');
    wp_update_user(['ID'=>$id,'user_email'=>$admin['email']]);wp_set_password($admin['password'],$id);
    // Local admin credentials and sessions must not become production access.
    foreach(get_users(['role'=>'administrator']) as $user)if($user->ID!=$id)wp_set_password(bin2hex(random_bytes(32)),$user->ID);
    delete_metadata('user',0,'session_tokens','',true);delete_metadata('user',0,'_application_passwords','',true);
    update_option('home',$config['site_url']);update_option('siteurl',$config['site_url']);
    update_option('rewrite_rules',false);
    if(get_option('template')!=='ldrn-theme' || !is_plugin_active('ldrn-workspace/ldrn-workspace.php'))throw new RuntimeException('The release is not connected to the LDRN theme and editor.');
    if(!is_dir(dirname($backup)) && !mkdir(dirname($backup),0700,true))throw new RuntimeException('Cannot create the recovery directory.');
    if(file_exists($backup))throw new RuntimeException('Recovery directory already exists.');
    status_write('activating');
    // Release the request directory on Windows too; production uses Linux.
    if(!chdir($domain))throw new RuntimeException('Cannot enter the deployment parent directory.');
    if(!rename($live,$backup))throw new RuntimeException('Cannot preserve the existing live site.');
    if(!rename($staged,$live)) {
        rename($backup,$live);throw new RuntimeException('Activation failed. The existing website was restored.');
    }
    $swapped=true;
    status_write('complete','LDRN WordPress and the editor are active. The previous static site is retained outside the web root.');
    @unlink($backup.'/'.basename(__FILE__));
    reply(['ok'=>true,'state'=>'complete','site'=>$config['site_url'],'release'=>$release]);
}catch(Throwable $error) {
    if($swapped) {
        if(rename($live,$private.'/failed-public'))rename($backup,$live);
    }
    // Do not return driver errors, paths or secrets to the HTTP response.
    $detail=$error->getMessage();
    foreach([$config['database']['password'] ?? '',$config['administrator']['password'] ?? '',$token[1]] as $secret)if($secret!=='')$detail=str_replace($secret,'[redacted]',$detail);
    file_put_contents($private.'/failure-detail.json',json_encode(['phase'=>$GLOBALS['ldrn_install_phase'] ?? 'checking','type'=>get_class($error),'message'=>$detail]),LOCK_EX);
    status_write('failed','Deployment stopped. Review the private release and database before retrying.');
    reply(['ok'=>false,'state'=>'failed','message'=>'Deployment stopped. The previous site was retained. Inspect the private release status.'],500);
}
