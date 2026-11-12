<?php
// Isolated local evaluation only: uses the official WordPress MySQL-on-SQLite driver.
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
$root=realpath($argv[1] ?? '');
$database=$argv[2] ?? '';
if(!$root || !str_contains(str_replace('\\','/',$root),'/private-releases/sqlite-evaluation-') || is_file($database))throw new RuntimeException('An isolated empty evaluation target is required.');
require $root.'/public/wp-content/plugins/sqlite-database-integration/wp-includes/database/load.php';
$pdo=new WP_MySQL_On_SQLite('mysql-on-sqlite:path='.$database.';dbname=ldrn_sqlite');
$stream=gzopen($root.'/database.sql.gz','rb');
$statement='';$quote=null;$escaped=false;$count=0;
$pdo->beginTransaction();
try {
    while(!gzeof($stream)) {
        $chunk=gzread($stream,1048576);
        for($i=0,$length=strlen($chunk);$i<$length;$i++) {
            $char=$chunk[$i];$statement.=$char;
            if($quote!==null) {
                if($escaped){$escaped=false;continue;}
                if($char==='\\'){$escaped=true;continue;}
                if($char===$quote)$quote=null;
            }elseif($char==="'" || $char==='"' || $char==='`')$quote=$char;
            elseif($char===';'){$pdo->exec($statement);$statement='';$count++;}
        }
    }
    if(trim($statement)!=='' || $quote!==null)throw new RuntimeException('Incomplete SQL export.');
    $pdo->commit();
    echo $count." source SQL statements imported using the official SQLite driver.\n";
}catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw $error;}
finally{gzclose($stream);}
