"""Exercise the actual installer on an isolated local database and throwaway site."""
from pathlib import Path
import argparse
import hashlib
import json
import os
import secrets
import shutil
import subprocess
import time
import urllib.request
import urllib.error

PROJECT=Path(__file__).resolve().parent.parent
PRIVATE=Path(r'C:\Users\Dhananjay\Local Sites\lrdn\private-releases')
PHP=Path(r'C:\OUT_OF_ONEDRIVE\Local\resources\extraResources\lightning-services\php-8.2.29+0\bin\win64\php.exe')

parser=argparse.ArgumentParser();parser.add_argument('--release',required=True);args=parser.parse_args()
release=Path(args.release).resolve()
if not release.is_relative_to(PRIVATE) or not (release/'manifest.json').is_file():raise ValueError('Expected a private prepared release.')
manifest=json.loads((release/'manifest.json').read_text())
suffix=secrets.token_hex(4);folder=PRIVATE/('verification-'+suffix);folder.mkdir()
env=dict(os.environ,LDRN_TEST_RELEASE_ROOT=str(folder),LDRN_TEST_DATABASE='ldrn_release_verify_'+suffix)
subprocess.run(['powershell','-NoProfile','-File',str(PROJECT/'scripts/wp-local.ps1'),'eval-file',str(PROJECT/'scripts/setup-release-verification.php')],env=env,check=True)
config=json.loads((folder/'database-config.json').read_text())
remote=folder/'domain/ldrn-releases'/manifest['release'];remote.mkdir(parents=True)
shutil.copytree(release/'public',remote/'public')
for name in ['database.sql.gz','manifest.json']:shutil.copy2(release/name,remote/name)
token=secrets.token_urlsafe(48);config['token_sha256']=hashlib.sha256(token.encode()).hexdigest()
(remote/'remote-config.json').write_text(json.dumps(config))
live=folder/'domain/public_html/ldrn';live.mkdir(parents=True)
(live/'index.html').write_text('Original static LDRN verification site')
bootstrap=(PROJECT/'scripts/deployment-bootstrap.php').read_text().replace('__LDRN_RELEASE_ID__',manifest['release'])
(live/'installer.php').write_text(bootstrap)
extension=PHP.parent/'ext'
cmd=[str(PHP),'-n','-d','extension_dir='+str(extension),'-d','memory_limit=512M','-d','mysqli.default_port=10029']
for name in ['mysqli','pdo_mysql','mbstring','openssl','curl','fileinfo','gd','zip','exif','intl']:
    cmd+=['-d','extension=php_'+name+'.dll']
cmd+=['-S','127.0.0.1:18762','-t',str(folder/'domain')]
log=(folder/'server.log').open('wb')
server=subprocess.Popen(cmd,stdout=log,stderr=log,creationflags=subprocess.CREATE_NO_WINDOW)
def request(mode,authorized=True):
    req=urllib.request.Request('http://127.0.0.1:18762/public_html/ldrn/installer.php',data=json.dumps({'mode':mode}).encode(),headers={'Authorization':'Bearer '+(token if authorized else 'invalid'),'Content-Type':'application/json'},method='POST')
    try:
        with urllib.request.urlopen(req,timeout=180) as response:return response.status,json.load(response)
    except urllib.error.HTTPError as error:return error.code,json.load(error)
try:
    for attempt in range(30):
        try:request('status');break
        except urllib.error.URLError:time.sleep(.2)
    assert request('apply',False)[0]==404,'Unauthenticated installation was accepted'
    assert (live/'index.html').is_file(),'An unauthorized request changed the live fixture'
    print('Unauthenticated installer rejected; previous site preserved.',flush=True)
    # The Windows dev server locks its served directories. SSH deployment uses
    # the same installer through PHP CLI, which permits the atomic directory swap.
    server.terminate();server.wait(timeout=10)
    private_installer=remote/'installer.php';private_installer.write_text(bootstrap)
    (live/'installer.php').unlink()
    process=subprocess.run(cmd[:-4]+[str(private_installer),'apply',str(live)],input=token.encode(),stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=360,cwd=str(folder/'domain'),creationflags=subprocess.CREATE_NO_WINDOW)
    try:result=json.loads(process.stdout);status=200 if result.get('ok') else 500
    except json.JSONDecodeError:raise RuntimeError('The isolated CLI installer did not return JSON. Inspect its private log.')
    if status!=200 or not result.get('ok'):
        print('Private fixture retained for diagnosis:',folder)
        raise RuntimeError('Isolated installation failed; inspect its private status and server log.')
    assert (live/'wp-config.php').is_file() and not (live/'index.html').exists(),'WordPress did not replace the static fixture'
    backup=folder/'domain/ldrn-backups'/manifest['release']
    assert (backup/'index.html').read_text()=='Original static LDRN verification site','Original static site was not retained'
    assert not (backup/'installer.php').exists(),'One-use installer was not removed'
    assert not (live/'database.sql.gz').exists() and not (live/'remote-config.json').exists(),'Private deployment data became public'
    print('Actual installer imported isolated DB, configured WordPress/admin, activated the release and retained the original site.',flush=True)
    print('Private SQL/configuration stayed outside the web root; one-use installer was removed.',flush=True)
    print('Verification fixture retained outside the web root:',folder)
finally:
    if server.poll() is None:server.terminate();server.wait(timeout=10)
    log.close()
