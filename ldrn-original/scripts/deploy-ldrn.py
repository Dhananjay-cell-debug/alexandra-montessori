"""Dedicated LDRN install: private staging, verified files, empty DB import, atomic swap.

prepare never changes production. stage transfers outside public_html. apply
requires the dedicated database and a complete release; status never retries an
interrupted import. rollback restores the previous static site, retaining the
new WordPress database for investigation. Future WP updates need a separate
code-only release; this installer refuses a nonempty target database.
"""
import argparse
import datetime as dt
import ftplib
import gzip
import hashlib
import json
import os
from pathlib import Path
import re
import secrets
import shutil
import ssl
import subprocess
import sys
import urllib.request

PROJECT = Path(__file__).resolve().parent.parent
LOCAL = Path(r'C:\Users\Dhananjay\Local Sites\lrdn\app\public')
PRIVATE = LOCAL.parent.parent / 'private-releases'
SITE = 'https://ldrn.krildigital.com'
DOMAIN_ROOT = 'domains/krildigital.com'
LIVE = DOMAIN_ROOT + '/public_html/ldrn'
STATIC_TYPES = {'.png','.jpg','.jpeg','.webp','.svg','.gif','.ico','.pdf','.docx','.mp4','.webm','.mp3','.ogg','.woff','.woff2','.js','.css','.json','.html','.txt'}

def digest(path):
    with path.open('rb') as source:
        return hashlib.file_digest(source, 'sha256').hexdigest()

def config(path):
    path=Path(path).resolve()
    if path.is_relative_to(PROJECT) or path.is_relative_to(LOCAL):
        raise ValueError('Keep the private deployment configuration outside source and the public website.')
    data=json.loads(path.read_text(encoding='utf-8-sig'))
    if data.get('site_url')!=SITE:
        raise ValueError('This installer is bound to the LDRN hostname.')
    for group,fields in {'ftp':['host','user','password'],'database':['host','name','user','password'],'administrator':['login','email','password']}.items():
        if any(not data.get(group,{}).get(field) for field in fields):
            raise ValueError(f'Private {group} settings are incomplete.')
    if data['database'].get('dedicated_empty_database') is not True:
        raise ValueError('A dedicated empty LDRN database is required.')
    if len(data['administrator']['password'])<20:
        raise ValueError('Use a unique administrator password of at least 20 characters.')
    if data['ftp'].get('protocol','ftps')!='ftps':
        raise ValueError('Use an FTPS hostname from the hosting panel; credentials are never sent over plain FTP.')
    return data

def tree(source,target):
    if not source.is_dir(): raise ValueError(f'Required source directory missing: {source.name}')
    shutil.copytree(source,target,dirs_exist_ok=True,ignore=shutil.ignore_patterns('.git','.env','debug.log','*.sql','*.sql.gz','*.bak','*.tmp','*.log'))

def prepare():
    subprocess.run(['powershell','-NoProfile','-File',str(PROJECT/'scripts/sync-local.ps1')],check=True)
    stamp=dt.datetime.now(dt.timezone.utc).strftime('%Y%m%dT%H%M%SZ')+'-'+secrets.token_hex(4)
    release=PRIVATE/stamp; public=release/'public';public.mkdir(parents=True)
    for path in LOCAL.iterdir():
        if path.is_file() and path.name not in {'wp-config.php','.htaccess','debug.log'} and (path.suffix=='.php' or path.name in {'license.txt','readme.html'}):
            shutil.copy2(path,public/path.name)
    for folder in ['wp-admin','wp-includes','assets','fonts']:
        tree(LOCAL/folder,public/folder)
    for folder in ['themes/ldrn-theme','plugins/ldrn-workspace','plugins/cmb2','mu-plugins']:
        tree(LOCAL/'wp-content'/folder,public/'wp-content'/folder)
    for path in (LOCAL/'wp-content/uploads').rglob('*'):
        if path.is_file() and path.suffix.lower() in STATIC_TYPES and not any(part.lower() in {'private','ldrn-private','.building','node_modules'} or part.startswith('.building-') for part in path.parts):
            target=public/path.relative_to(LOCAL);target.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(path,target)
    (public/'.htaccess').write_text('''Options -Indexes
<FilesMatch "(?i)^(wp-config\\.php|\\.env.*|.*\\.(sql|sql\\.gz|log|bak))$">
Require all denied
</FilesMatch>
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule (^|/)\\.(git|env)(/|$) - [F,L]
RewriteRule ^index\\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
''',encoding='utf-8')
    uploads=public/'wp-content/uploads';uploads.mkdir(parents=True,exist_ok=True)
    (uploads/'.htaccess').write_text('Options -Indexes\n<FilesMatch "(?i)\\.(php[0-9]?|phtml|phar)$">\nRequire all denied\n</FilesMatch>\n',encoding='utf-8')
    env=os.environ.copy();env['LDRN_RELEASE_SQL']=str(release/'database.sql');env['LDRN_RELEASE_URL']=SITE
    subprocess.run(['powershell','-NoProfile','-File',str(PROJECT/'scripts/wp-local.ps1'),'eval-file',str(PROJECT/'scripts/export-production.php')],env=env,check=True)
    with (release/'database.sql').open('rb') as src,gzip.open(release/'database.sql.gz','wb') as out:shutil.copyfileobj(src,out)
    (release/'database.sql').unlink()
    files={p.relative_to(public).as_posix():digest(p) for p in sorted(public.rglob('*')) if p.is_file()}
    metadata=json.loads((release/'database-metadata.json').read_text())
    manifest={'release':stamp,'site_url':SITE,'files':files,'sql_sha256':digest(release/'database.sql.gz'),'database':metadata}
    (release/'manifest.json').write_text(json.dumps(manifest,indent=2),encoding='utf-8')
    print(f'Prepared {len(files)} files in the private release: {release}')
    print('Production is unchanged. Dedicated database configuration is required for stage/apply.')

def release_path(value):
    path=Path(value).resolve()
    if not path.is_relative_to(PRIVATE.resolve()) or not (path/'manifest.json').is_file():
        raise ValueError('Expected a prepared private LDRN release.')
    manifest=json.loads((path/'manifest.json').read_text())
    if manifest['site_url']!=SITE or not re.fullmatch(r'\d{8}T\d{6}Z-[a-f0-9]{8}',manifest['release']):raise ValueError('Invalid release identity.')
    for rel,expected in manifest['files'].items():
        file=path/'public'/rel
        if not file.resolve().is_relative_to((path/'public').resolve()) or digest(file)!=expected:raise ValueError('Release files changed after preparation.')
    if digest(path/'database.sql.gz')!=manifest['sql_sha256']:raise ValueError('Release SQL changed after preparation.')
    return path,manifest

def connect(data):
    context=ssl.create_default_context()
    pin=data.get('certificate_sha256')
    if pin and not re.fullmatch('[a-f0-9]{64}',pin):raise ValueError('Invalid FTPS certificate fingerprint.')
    if pin:context.check_hostname=False
    class VerifiedFTP(ftplib.FTP_TLS):
        def auth(self):
            result=super().auth()
            if pin and not secrets.compare_digest(hashlib.sha256(self.sock.getpeercert(binary_form=True)).hexdigest(),pin):
                self.close();raise ValueError('The hosting FTPS certificate changed. Recheck the server identity before authenticating.')
            return result
        def ntransfercmd(self,cmd,rest=None):
            conn,size=super().ntransfercmd(cmd,rest)
            if self._prot_p and pin and not secrets.compare_digest(hashlib.sha256(conn.getpeercert(binary_form=True)).hexdigest(),pin):
                conn.close();raise ValueError('FTPS data-channel certificate mismatch.')
            return conn,size
    ftp=VerifiedFTP(context=context,timeout=45)
    ftp.connect(data['host'],data.get('port',21));ftp.login(data['user'],data['password']);ftp.prot_p()
    return ftp

def mkdirs(ftp,path):
    current=''
    for part in path.split('/'):
        current=current+'/'+part if current else part
        try:ftp.mkd(current)
        except ftplib.error_perm:
            ftp.cwd(current);ftp.cwd('/')

def upload(ftp,source,target):
    mkdirs(ftp,str(Path(target).parent).replace('\\','/'))
    with source.open('rb') as content:ftp.storbinary('STOR '+target,content,blocksize=256*1024)

def stage(path,manifest,data):
    remote=DOMAIN_ROOT+'/ldrn-releases/'+manifest['release']
    token=secrets.token_urlsafe(48);receipt={'release':manifest['release'],'bootstrap':'ldrn-release-'+secrets.token_hex(16)+'.php','token':token,'remote':remote,'backup':DOMAIN_ROOT+'/ldrn-backups/'+manifest['release']}
    private_config={key:value for key,value in data.items() if key!='ftp'}
    private_config.update(token_sha256=hashlib.sha256(token.encode()).hexdigest(),receipt={key:value for key,value in receipt.items() if key!='token'})
    (path/'remote-config.json').write_text(json.dumps(private_config),encoding='utf-8')
    (path/'receipt.json').write_text(json.dumps(receipt),encoding='utf-8')
    ftp=connect(data['ftp'])
    try:
        names=[entry[0] for entry in ftp.mlsd(LIVE)]
        if 'wp-config.php' in names or 'wp-admin' in names:raise ValueError('Production already contains WordPress. This initial installer will not replace a live WordPress database.')
        for number,rel in enumerate(manifest['files'],1):
            upload(ftp,path/'public'/rel,remote+'/public/'+rel)
            if number%100==0:print(f'Staged {number}/{len(manifest["files"])} files outside the public website.',flush=True)
        for name in ['database.sql.gz','manifest.json','remote-config.json']:
            upload(ftp,path/name,remote+'/'+name)
        bootstrap=(PROJECT/'scripts/deployment-bootstrap.php').read_text(encoding='utf-8').replace('__LDRN_RELEASE_ID__',manifest['release'])
        (path/'bootstrap.php').write_text(bootstrap,encoding='utf-8')
        upload(ftp,path/'bootstrap.php',LIVE+'/'+receipt['bootstrap'])
        print('Private staging completed; the existing live site is unchanged.')
    finally:ftp.quit()

def request_bootstrap(path,mode):
    receipt=json.loads((path/'receipt.json').read_text())
    if mode=='status' and receipt.get('completed'):
        print('This release completed; its one-use installer was removed.');health();return
    request=urllib.request.Request(SITE+'/'+receipt['bootstrap'],data=json.dumps({'mode':mode}).encode(),headers={'Authorization':'Bearer '+receipt['token'],'Content-Type':'application/json'},method='POST')
    with urllib.request.urlopen(request,timeout=180) as response: result=json.load(response)
    if not result.get('ok'):raise ValueError(result.get('message','Deployment did not complete.'))
    if mode=='apply':
        receipt['completed']=True
        (path/'receipt.json').write_text(json.dumps(receipt),encoding='utf-8')
    print(json.dumps(result,indent=2))

def health():
    with urllib.request.urlopen(SITE+'/wp-json/ldrn/v1/testimonials?per_page=3',timeout=30) as response:
        data=json.load(response)
        if not isinstance(data.get('items'),list) or not data['items']:raise ValueError('Live WordPress testimonial contract is unavailable.')
    for route in ['/','/settings/hounslow','/testimonials','/contact','/wp-login.php']:
        with urllib.request.urlopen(SITE+route,timeout=30) as response:
            text=response.read().decode('utf-8',errors='replace')
            if route!='/wp-login.php' and ('ldrnData' not in text or '/wp-content/' not in text):raise ValueError('The live route is not connected to WordPress: '+route)
    print('Live WordPress routes and the nursery content API passed health checks.')

def rollback(path,data):
    receipt=json.loads((path/'receipt.json').read_text());ftp=connect(data['ftp'])
    try:
        ftp.cwd(receipt['backup']);ftp.cwd('/')
        failed=DOMAIN_ROOT+'/ldrn-releases/'+receipt['release']+'/failed-public'
        ftp.rename(LIVE,failed)
        try:ftp.rename(receipt['backup'],LIVE)
        except Exception:
            ftp.rename(failed,LIVE);raise
        print('The previous website was restored. The dedicated WordPress database and failed release were retained.')
    finally:ftp.quit()

def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('command',choices=['prepare','stage','apply','status','health','rollback'])
    parser.add_argument('--release');parser.add_argument('--config')
    args=parser.parse_args()
    if args.command=='prepare':prepare();return
    if args.command=='health':health();return
    if not args.release:parser.error('--release is required')
    path,manifest=release_path(args.release)
    if args.command=='status':request_bootstrap(path,'status');return
    if args.command=='apply':
        request_bootstrap(path,'apply')
        try:health()
        except Exception:
            if not args.config:raise ValueError('Activation completed but health verification failed. Run rollback with the private configuration.')
            rollback(path,config(args.config));raise ValueError('Health verification failed; the previous website was restored.')
        return
    if not args.config:parser.error('--config must name the private deployment configuration')
    data=config(args.config)
    if args.command=='stage':stage(path,manifest,data)
    elif args.command=='rollback':rollback(path,data)

if __name__=='__main__':
    try:main()
    except Exception as error:
        # FTP/DB credentials and HTTP authorization tokens are never logged.
        print(f'LDRN deployment stopped: {type(error).__name__}: {error}',file=sys.stderr)
        sys.exit(1)
