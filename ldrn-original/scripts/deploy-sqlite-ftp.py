"""Deploy tested WordPress and SQLite using FTPS plus an authenticated PHP installer."""
from pathlib import Path
import argparse
import hashlib
import importlib.util
import io
import json
import secrets
import tarfile
import time
import urllib.request

PROJECT=Path(__file__).resolve().parent.parent
PRIVATE=Path(r'C:\Users\Dhananjay\Local Sites\lrdn\private-releases')
spec=importlib.util.spec_from_file_location('deploy',PROJECT/'scripts/deploy-ldrn.py')
deploy=importlib.util.module_from_spec(spec);spec.loader.exec_module(deploy)

def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('mode',choices=['prepare','apply','status'])
    parser.add_argument('--release',required=True);parser.add_argument('--evaluation');parser.add_argument('--base',default='20261003T175621Z-ce5dd3a4')
    args=parser.parse_args();release,source=deploy.release_path(args.release)
    receipt_file=release/'sqlite-receipt.json'
    remote='domains/krildigital.com/ldrn-releases/'+source['release']
    cfg=json.loads((PRIVATE/'deployment-config.json').read_text())
    ftp=deploy.connect(cfg['ftp'])
    try:
        if args.mode=='prepare':
            evaluation=Path(args.evaluation).resolve()
            if not evaluation.is_relative_to(PRIVATE) or not evaluation.name.startswith('sqlite-evaluation-'):raise ValueError('Expected an isolated tested evaluation.')
            evidence=json.loads((evaluation/'evaluation.json').read_text())
            if evidence.get('passed') is not True or evidence.get('source_release')!=source['release'] or not (evaluation/'deployment.sqlite').is_file():raise ValueError('A finalized tested SQLite export is required.')
            base=json.loads((PRIVATE/args.base/'manifest.json').read_text())
            files={p.relative_to(evaluation/'public').as_posix():deploy.digest(p) for p in sorted((evaluation/'public').rglob('*')) if p.is_file() and p.name!='wp-config.php'}
            manifest={'release':source['release'],'site_url':deploy.SITE,'prefix':source['database']['prefix'],'files':files,'database_sha256':deploy.digest(evaluation/'deployment.sqlite')}
            (release/'sqlite-manifest.json').write_text(json.dumps(manifest,indent=2))
            overlay=release/'sqlite-overlay.tar.gz'
            changed=[rel for rel,h in files.items() if base['files'].get(rel)!=h]
            # PHP PharData reads POSIX USTAR paths reliably; Python's PAX
            # extended path headers are not portable to this extractor.
            with tarfile.open(overlay,'w:gz',format=tarfile.USTAR_FORMAT) as archive:
                for rel in changed:archive.add(evaluation/'public'/rel,arcname='public/'+rel)
                archive.add(evaluation/'deployment.sqlite',arcname='database/site.sqlite')
                archive.add(release/'sqlite-manifest.json',arcname='sqlite-manifest.json')
            token=secrets.token_urlsafe(48)
            receipt={'release':source['release'],'bootstrap':'ldrn-activation-'+secrets.token_hex(20)+'.php','token':token,'remote':remote,'backup':'domains/krildigital.com/ldrn-backups/'+source['release']}
            receipt_file.write_text(json.dumps(receipt))
            private_config={'authorization_sha256':hashlib.sha256(('Bearer '+token).encode()).hexdigest(),'expires':int(time.time())+86400,'overlay_sha256':deploy.digest(overlay),'administrator':cfg['administrator']}
            deploy.mkdirs(ftp,remote)
            ftp.storbinary('STOR '+remote+'/sqlite-config.json',io.BytesIO(json.dumps(private_config).encode()))
            ftp.sendcmd('SITE CHMOD 600 '+remote+'/sqlite-config.json')
            sent=[0];total=overlay.stat().st_size;progress=[0]
            def progress_upload(chunk):
                sent[0]+=len(chunk);percent=int(sent[0]*100/total)
                if percent>=progress[0]+20:progress[0]=percent;print('Private FTP overlay upload: '+str(percent)+'%',flush=True)
            print('Uploading %.1f MiB of changes plus private database (%d changed files).'%(total/1048576,len(changed)),flush=True)
            with overlay.open('rb') as file:ftp.storbinary('STOR '+remote+'/overlay.tar.gz',file,blocksize=262144,callback=progress_upload)
            code=(PROJECT/'scripts/ftp-sqlite-bootstrap.php').read_text().replace('__RELEASE__',source['release']).replace('__BASE__',args.base)
            ftp.storbinary('STOR '+deploy.LIVE+'/'+receipt['bootstrap'],io.BytesIO(code.encode()))
        else:receipt=json.loads(receipt_file.read_text())
        if args.mode=='status' and receipt.get('completed'):
            print('This release completed; its installer was removed.');deploy.health();return
        request=urllib.request.Request(deploy.SITE+'/'+receipt['bootstrap'],data=json.dumps({'mode':args.mode}).encode(),headers={'Authorization':'Bearer '+receipt['token'],'Content-Type':'application/json'},method='POST')
        try:
            with urllib.request.urlopen(request,timeout=240) as response:result=json.load(response)
        except Exception:
            # A private diagnostic contains no passwords and does not alter the live website.
            data=io.BytesIO()
            try:ftp.retrbinary('RETR '+remote+'/sqlite-failure.json',data.write);print(data.getvalue().decode())
            except Exception:pass
            raise
        print(json.dumps(result),flush=True)
        if not result.get('ok'):raise RuntimeError('Installer stopped; inspect private diagnostics.')
        if args.mode=='apply':
            receipt['completed']=True;receipt_file.write_text(json.dumps(receipt))
            (release/'receipt.json').write_text(json.dumps(receipt))
            try:deploy.health()
            except Exception:
                deploy.rollback(release,cfg);raise
    finally:ftp.quit()

if __name__=='__main__':main()
