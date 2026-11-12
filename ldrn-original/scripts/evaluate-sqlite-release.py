"""Evaluate the FTP-only SQLite deployment route without touching local or live WordPress."""
from pathlib import Path
import argparse
import json
import secrets
import os
import shutil
import subprocess

PROJECT=Path(__file__).resolve().parent.parent
PRIVATE=Path(r'C:\Users\Dhananjay\Local Sites\lrdn\private-releases')
PHP=Path(r'C:\OUT_OF_ONEDRIVE\Local\resources\extraResources\lightning-services\php-8.2.29+0\bin\win64\php.exe')
WPCLI=Path(r'C:\OUT_OF_ONEDRIVE\Local\resources\extraResources\bin\wp-cli\wp-cli.phar')
parser=argparse.ArgumentParser();parser.add_argument('--release',required=True);parser.add_argument('--reuse');parser.add_argument('--diagnose',action='store_true');parser.add_argument('--finalize',action='store_true');args=parser.parse_args()
source=Path(args.release).resolve()
if not source.is_relative_to(PRIVATE) or not (source/'manifest.json').is_file():raise ValueError('Expected a private release.')
folder=Path(args.reuse).resolve() if args.reuse else PRIVATE/('sqlite-evaluation-'+secrets.token_hex(4))
if not folder.is_relative_to(PRIVATE) or not folder.name.startswith('sqlite-evaluation-'):raise ValueError('Expected an isolated SQLite evaluation path.')
if not args.reuse:
    folder.mkdir();shutil.copytree(source/'public',folder/'public')
    shutil.copy2(source/'database.sql.gz',folder/'database.sql.gz')
plugin=folder/'public/wp-content/plugins/sqlite-database-integration'
shutil.copytree(PRIVATE/'sqlite-reference/sqlite-database-integration',plugin,dirs_exist_ok=True)
shutil.copy2(plugin/'db.copy',folder/'public/wp-content/db.php')
shutil.copy2(PROJECT/'wordpress/mu-plugins/ldrn-sqlite-backups.php',folder/'public/wp-content/mu-plugins/ldrn-sqlite-backups.php')
database_folder=folder/'private-database';database_folder.mkdir(exist_ok=True)
database=database_folder/'site.sqlite'
cmd=[str(PHP),'-n','-d','extension_dir='+str(PHP.parent/'ext'),'-d','memory_limit=512M']
for extension in ['pdo_sqlite','sqlite3','mysqli','mbstring','openssl','curl','fileinfo','gd','zip','exif','intl']:
    cmd+=['-d','extension=php_'+extension+'.dll']
print('Isolated SQLite evaluation:',folder,flush=True)
if not args.reuse:subprocess.run(cmd+[str(PROJECT/'scripts/import-sqlite-release.php'),str(folder),str(database)],check=True)
metadata=json.loads((source/'database-metadata.json').read_text())
config="""<?php
define('DB_ENGINE','sqlite');define('DB_NAME','ldrn_sqlite');define('DB_USER','');define('DB_PASSWORD','');define('DB_HOST','localhost');define('DB_CHARSET','utf8mb4');define('DB_COLLATE','');
define('DB_DIR',__PRIVATE_DB__);define('DB_FILE','site.sqlite');
define('WP_HOME','http://lrdn.local');define('WP_SITEURL','http://lrdn.local');define('WP_DEBUG',false);define('WP_DEBUG_DISPLAY',false);
$table_prefix=__PREFIX__;
if(!defined('ABSPATH'))define('ABSPATH',__DIR__.'/');require_once ABSPATH.'wp-settings.php';
""".replace('__PRIVATE_DB__',json.dumps(str(database_folder).replace('\\','/'))).replace('__PREFIX__',json.dumps(metadata['prefix']))
(folder/'public/wp-config.php').write_text(config)
subprocess.run(cmd+[str(WPCLI),'--path='+str(folder/'public'),'eval-file',str(PROJECT/'scripts/diagnose-sqlite.php' if args.diagnose else PROJECT/'scripts/verify-workspace.php')],check=True)
if args.diagnose:raise SystemExit(0)
(folder/'evaluation.json').write_text(json.dumps({'passed':True,'source_release':source.name,'database':str(database)}))
print('SQLite editor publication/session/backend evaluation passed; production is unchanged.',flush=True)
if args.finalize:
    (folder/'public/wp-config.php').write_text(config.replace('http://lrdn.local','https://ldrn.krildigital.com'))
    env=dict(os.environ,LDRN_PRIVATE_DEPLOYMENT_CONFIG=str(PRIVATE/'deployment-config.json'))
    subprocess.run(cmd+[str(WPCLI),'--path='+str(folder/'public'),'eval-file',str(PROJECT/'scripts/finalize-sqlite-evaluation.php')],env=env,check=True)
