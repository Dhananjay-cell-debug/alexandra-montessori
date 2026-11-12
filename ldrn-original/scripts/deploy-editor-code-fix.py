"""Deploy code only over verified FTPS; retain all visitor edits and drafts."""
from pathlib import Path
import hashlib, importlib.util, io, json, secrets, time, urllib.request, tarfile

PROJECT=Path(__file__).resolve().parent.parent
PRIVATE=Path(r'C:\Users\Dhananjay\Local Sites\lrdn\private-releases')
spec=importlib.util.spec_from_file_location('deploy',PROJECT/'scripts/deploy-ldrn.py')
deploy=importlib.util.module_from_spec(spec);spec.loader.exec_module(deploy)
stamp=time.strftime('%Y%m%dT%H%M%SZ',time.gmtime())+'-editor-code-fix'
backup=PRIVATE/stamp;backup.mkdir()
theme=PROJECT/'wordpress/theme/ldrn-theme/dist'
manifest=json.loads((theme/'.vite/manifest.json').read_text())
paths={r['file'] for r in manifest.values() if 'file' in r}
paths.update(f for r in manifest.values() for f in r.get('css',[]))
files=[(theme/p,'wp-content/themes/ldrn-theme/dist/'+p) for p in sorted(paths)]
files.extend((PROJECT/'wordpress/plugins/ldrn-workspace'/p,'wp-content/plugins/ldrn-workspace/'+p) for p in ['assets/studio-runtime.js','assets/studio.js','assets/studio-library.js','includes/studio.php','includes/site-snapshots.php','ldrn-workspace.php'])
files.append((PROJECT/'wordpress/theme/ldrn-theme/functions.php','wp-content/themes/ldrn-theme/functions.php'))
files.append((theme/'.vite/manifest.json','wp-content/themes/ldrn-theme/dist/.vite/manifest.json'))
hashes={relative:deploy.digest(source) for source,relative in files}
archive=backup/'code.tar.gz'
with tarfile.open(archive,'w:gz',format=tarfile.USTAR_FORMAT) as package:
    for source,relative in files:package.add(source,arcname=relative,recursive=False)
remote_private='domains/krildigital.com/ldrn-releases/'+stamp
ftp=deploy.connect(json.loads((PRIVATE/'hosting-connection.json').read_text()))
token=secrets.token_urlsafe(48);name='ldrn-code-fix-'+secrets.token_hex(20)+'.php'
code='''<?php
header('Content-Type: application/json');header('Cache-Control: no-store');
if(time()>__EXPIRES__ || ($_SERVER['HTTP_HOST']??'')!=='ldrn.krildigital.com' || !hash_equals('__HASH__',hash('sha256',$_SERVER['HTTP_AUTHORIZATION']??''))){http_response_code(404);exit;}
$private=dirname(__DIR__,2).'/ldrn-releases/__STAMP__';$changed=[];
try {
    $hashes=json_decode('__FILES__',true);
    if(!hash_equals('__ARCHIVE_HASH__',hash_file('sha256',$private.'/code.tar.gz')))throw new RuntimeException('Code package hash mismatch.');
    (new PharData($private.'/code.tar.gz'))->extractTo($private.'/staging');
    foreach($hashes as $relative=>$expected){
        if(str_contains($relative,'..') || !hash_equals($expected,hash_file('sha256',$private.'/staging/'.$relative)))throw new RuntimeException('Staged file validation failed.');
    }
    foreach($hashes as $relative=>$expected){
        $target=__DIR__.'/'.$relative;$old=$private.'/before/'.$relative;
        if(is_file($target)){if(!is_dir(dirname($old)))mkdir(dirname($old),0700,true);if(!copy($target,$old))throw new RuntimeException('Code recovery copy failed.');}
        if(!is_dir(dirname($target)))mkdir(dirname($target),0755,true);
        if(!copy($private.'/staging/'.$relative,$target))throw new RuntimeException('Code transfer failed.');
        $changed[]=$relative;
        if(!hash_equals($expected,hash_file('sha256',$target)))throw new RuntimeException('Installed code validation failed.');
    }
    require __DIR__.'/wp-load.php';
    __ACTION__
} catch(Throwable $error){
    foreach(array_reverse($changed) as $relative){$old=$private.'/before/'.$relative;if(is_file($old))copy($old,__DIR__.'/'.$relative);}
    http_response_code(500);echo json_encode(['ok'=>false,'message'=>$error->getMessage()]);
}
'''.replace('__EXPIRES__',str(int(time.time())+900)).replace('__HASH__',hashlib.sha256(('Bearer '+token).encode()).hexdigest()).replace('__STAMP__',stamp).replace('__FILES__',json.dumps(hashes,separators=(',',':'))).replace('__ARCHIVE_HASH__',deploy.digest(archive)).replace('__ACTION__',(PROJECT/'scripts/activate-editor-code.php').read_text().removeprefix('<?php'))
try:
    deploy.upload(ftp,archive,remote_private+'/code.tar.gz')
    print('One verified code package uploaded privately; activating without changing editor documents.',flush=True)
    ftp.storbinary('STOR '+deploy.LIVE+'/'+name,io.BytesIO(code.encode()))
    try:
        request=urllib.request.Request(deploy.SITE+'/'+name,headers={'Authorization':'Bearer '+token})
        with urllib.request.urlopen(request,timeout=180) as response:result=json.load(response)
        if not result.get('ok'):raise RuntimeError('Code activation did not pass.')
        (backup/'receipt.json').write_text(json.dumps({'files':hashes,'result':result},indent=2))
        print(json.dumps({'verified_files':len(files),'result':result,'private_backup':str(backup)},indent=2))
    finally:ftp.delete(deploy.LIVE+'/'+name)
finally:ftp.quit()
