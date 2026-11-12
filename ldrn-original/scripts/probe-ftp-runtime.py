"""Inspect only the LDRN PHP runtime via a temporary authenticated FTP-uploaded probe."""
from pathlib import Path
import hashlib
import importlib.util
import io
import json
import secrets
import time
import urllib.request

PROJECT=Path(__file__).resolve().parent.parent
PRIVATE=Path(r'C:\Users\Dhananjay\Local Sites\lrdn\private-releases')
spec=importlib.util.spec_from_file_location('deploy',PROJECT/'scripts/deploy-ldrn.py')
deploy=importlib.util.module_from_spec(spec);spec.loader.exec_module(deploy)
token=secrets.token_urlsafe(48);name='ldrn-runtime-'+secrets.token_hex(20)+'.php'
code='''<?php
declare(strict_types=1);
header('Content-Type: application/json');header('Cache-Control: no-store');
if(time()>__EXPIRES__ || !hash_equals('__HASH__',hash('sha256',$_SERVER['HTTP_AUTHORIZATION'] ?? ''))){http_response_code(404);exit;}
if(($_SERVER['HTTP_HOST'] ?? '')!=='ldrn.krildigital.com'){http_response_code(404);exit;}
$drivers=PDO::getAvailableDrivers();$sqlite=null;
if(in_array('sqlite',$drivers,true)){$db=new PDO('sqlite::memory:');$sqlite=$db->query('SELECT sqlite_version()')->fetchColumn();}
echo json_encode(['php'=>PHP_VERSION,'pdo_drivers'=>$drivers,'sqlite_version'=>$sqlite,'private_parent_writable'=>is_writable(dirname(__DIR__,2)),'lrdn_configuration_files'=>array_values(array_filter(scandir(__DIR__),fn($n)=>preg_match('/^(wp-config\\.php|\\.env|database\\.php|config\\.php)$/',$n)))]);
'''.replace('__EXPIRES__',str(int(time.time())+300)).replace('__HASH__',hashlib.sha256(('Bearer '+token).encode()).hexdigest())
ftp=deploy.connect(json.loads((PRIVATE/'hosting-connection.json').read_text()))
try:
    ftp.storbinary('STOR '+deploy.LIVE+'/'+name,io.BytesIO(code.encode()))
    try:
        request=urllib.request.Request(deploy.SITE+'/'+name,headers={'Authorization':'Bearer '+token})
        with urllib.request.urlopen(request,timeout=30) as response:result=json.load(response)
        (PRIVATE/'ftp-runtime.json').write_text(json.dumps(result,indent=2))
        print(json.dumps(result,indent=2))
    finally:ftp.delete(deploy.LIVE+'/'+name)
finally:ftp.quit()
