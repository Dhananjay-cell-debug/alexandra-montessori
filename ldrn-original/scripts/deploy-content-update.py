"""Backed-up code deployment of the existing Website Content work to LDRN."""
from pathlib import Path
import base64, hashlib, importlib.util, io, json, secrets, sqlite3, sys, tarfile, time, urllib.request

PROJECT = Path(__file__).resolve().parent.parent
PRIVATE = Path(r'C:\Users\Dhananjay\Local Sites\lrdn\private-releases')
spec = importlib.util.spec_from_file_location('deploy', PROJECT/'scripts/deploy-ldrn.py')
deploy = importlib.util.module_from_spec(spec); spec.loader.exec_module(deploy)

def files():
    result = {}
    for source, target in [(PROJECT/'wordpress/theme/ldrn-theme','wp-content/themes/ldrn-theme'), (PROJECT/'wordpress/plugins/ldrn-workspace','wp-content/plugins/ldrn-workspace'), (PROJECT/'wordpress/mu-plugins','wp-content/mu-plugins')]:
        for path in source.rglob('*'):
            rel = path.relative_to(source)
            if not path.is_file() or any(p in {'tests','ldrn-visual-builder','.git','node_modules'} for p in rel.parts) or path.name == 'ldrn-visual-builder.php': continue
            if path.suffix.lower() not in {'.php','.js','.css','.json','.png','.jpg','.jpeg','.webp','.svg','.gif','.ico','.pdf','.docx','.woff','.woff2','.html','.txt'}: continue
            result[target+'/'+rel.as_posix()] = path
    return result

def call(ftp, action):
    token=secrets.token_urlsafe(48); name='ldrn-content-release-'+secrets.token_hex(20)+'.php'
    code="""<?php
header('Content-Type: application/json'); header('Cache-Control: no-store');
if(time()>EXPIRES || ($_SERVER['HTTP_HOST']??'')!=='ldrn.krildigital.com' || !hash_equals('AUTHHASH',hash('sha256',$_SERVER['HTTP_AUTHORIZATION']??''))){http_response_code(404);exit;}
unlink(__FILE__);
try { ACTION } catch(Throwable $e){http_response_code(500);echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);}
""".replace('EXPIRES',str(int(time.time())+900)).replace('AUTHHASH',hashlib.sha256(('Bearer '+token).encode()).hexdigest()).replace('ACTION',action)
    ftp.storbinary('STOR '+deploy.LIVE+'/'+name,io.BytesIO(code.encode()))
    try:
        req=urllib.request.Request(deploy.SITE+'/'+name,headers={'Authorization':'Bearer '+token})
        try:
            with urllib.request.urlopen(req,timeout=180) as response: return json.load(response)
        except urllib.error.HTTPError as error:
            raise RuntimeError(error.read().decode()[:2000]) from None
    finally:
        try: ftp.delete(deploy.LIVE+'/'+name)
        except Exception: pass

def encoded(value): return base64.b64encode(json.dumps(value).encode()).decode()

def inspect(ftp, paths):
    return call(ftp,"""require __DIR__.'/wp-load.php';
$paths=json_decode(base64_decode('PATHS'),true);$hashes=[];
foreach($paths as $p)$hashes[$p]=is_file(__DIR__.'/'.$p)?hash_file('sha256',__DIR__.'/'.$p):null;
if(!ldrn_sqlite_backup())throw new RuntimeException('Private database backup failed.');
$backups=glob(dirname(FQDB).'/backups/site-*.sqlite');rsort($backups,SORT_STRING);
echo wp_json_encode(['ok'=>true,'hashes'=>$hashes,'database_backup'=>$backups[0],
'live'=>hash('sha256',serialize(ldrn_ws_studio_live())),'draft'=>hash('sha256',serialize(ldrn_ws_studio_draft())),'revision'=>ldrn_ws_studio_rev(),
'active_build'=>get_option('ldrn_ws_active_build')['hash']??null,
'counts'=>['nurseries'=>(int)wp_count_posts('ldrn_setting')->publish,'testimonials'=>(int)wp_count_posts('ldrn_testimonial')->publish,'media'=>(int)wp_count_posts('attachment')->inherit]]);
""".replace('PATHS',encoded(list(paths))))

if __name__ == '__main__':
    sources=files(); hashes={rel:deploy.digest(path) for rel,path in sources.items()}
    ftp=deploy.connect(json.loads((PRIVATE/'hosting-connection.json').read_text()))
    try:
        if len(sys.argv)==1 or sys.argv[1]=='inspect':
            state=inspect(ftp,sources)
            changed={rel:hashes[rel] for rel in hashes if hashes[rel]!=state['hashes'][rel]}
            (PRIVATE/'content-update-inspection.json').write_text(json.dumps(state,indent=2))
            print(json.dumps({'changed_files':list(changed),'checked_files':len(hashes),'state':{k:v for k,v in state.items() if k!='hashes'}},indent=2))
            sys.exit()
        if sys.argv[1]=='finalize':
            backup=sorted(PRIVATE.glob('*-website-content'))[-1]
            stamp=backup.name
            result=call(ftp,"""
set_time_limit(180);require __DIR__.'/wp-load.php';
$private=dirname(__DIR__,2).'/ldrn-releases/STAMP';
$liveHash=hash('sha256',serialize(ldrn_ws_studio_live()));$draftHash=hash('sha256',serialize(ldrn_ws_studio_draft()));$rev=ldrn_ws_studio_rev();
add_filter('intermediate_image_sizes_advanced',function(){return [];});
add_filter('big_image_size_threshold','__return_false');
$seed=(static function($file){ob_start();require $file;return json_decode(ob_get_clean(),true);})($private.'/seed-content-parity.php');
if(!is_array($seed) || !empty($seed['missing']))throw new RuntimeException('Content migration incomplete.');
if($liveHash!==hash('sha256',serialize(ldrn_ws_studio_live())) || $draftHash!==hash('sha256',serialize(ldrn_ws_studio_draft())) || $rev!==ldrn_ws_studio_rev())throw new RuntimeException('Editor preservation failed.');
$session=ldrn_ws_capture_session('Website Content ready');
if(!$session || !ldrn_sqlite_backup())throw new RuntimeException('Recovery capture failed.');
$result=['ok'=>true,'seed'=>$seed,'session'=>$session,'draft_preserved'=>true,'live_preserved'=>true,'build'=>get_option('ldrn_ws_active_build')['hash']??null];
file_put_contents($private.'/receipt.json',wp_json_encode($result));echo wp_json_encode($result);
""".replace('STAMP',stamp))
            (backup/'receipt.json').write_text(json.dumps(result,indent=2))
            after=inspect(ftp,sources)
            if any(after['hashes'][rel]!=hashes[rel] for rel in hashes):raise RuntimeError('File parity failed.')
            before=json.loads((backup/'before.json').read_text())
            if any(before[k]!=after[k] for k in ['live','draft','revision']):raise RuntimeError('Editor preservation failed.')
            (backup/'after.json').write_text(json.dumps(after,indent=2))
            print(json.dumps({'backup':str(backup),'result':result,'all_files_verified':len(hashes),'counts':after['counts']},indent=2))
            sys.exit()
        if sys.argv[1]!='apply': raise ValueError('Use inspect, apply or finalize.')
        stamp=time.strftime('%Y%m%dT%H%M%SZ',time.gmtime())+'-website-content'
        backup=PRIVATE/stamp;backup.mkdir()
        state=inspect(ftp,sources)
        (backup/'before.json').write_text(json.dumps(state,indent=2))
        remote_db=state['database_backup']
        marker='/domains/krildigital.com/'
        if marker not in remote_db: raise RuntimeError('Unexpected private database path.')
        remote_db='domains/krildigital.com/'+remote_db.split(marker,1)[1]
        with (backup/'database-before.sqlite').open('wb') as out:ftp.retrbinary('RETR '+remote_db,out.write)
        with sqlite3.connect((backup/'database-before.sqlite').as_uri()+'?mode=ro',uri=True) as db:
            if db.execute('PRAGMA integrity_check').fetchone()[0]!='ok': raise RuntimeError('Database backup integrity failed.')
        changed={rel:hashes[rel] for rel in hashes if hashes[rel]!=state['hashes'][rel]}
        for rel in changed:
            if sources[rel].suffix=='.php':
                target=backup/'before'/rel;target.parent.mkdir(parents=True,exist_ok=True)
                if state['hashes'][rel] is not None:
                    with target.open('wb') as out:ftp.retrbinary('RETR '+deploy.LIVE+'/'+rel,out.write)
        archive=backup/'code.tar.gz'
        with tarfile.open(archive,'w:gz',format=tarfile.USTAR_FORMAT) as package:
            for rel in changed:package.add(sources[rel],arcname=rel,recursive=False)
        remote='domains/krildigital.com/ldrn-releases/'+stamp
        deploy.upload(ftp,archive,remote+'/code.tar.gz')
        deploy.upload(ftp,PROJECT/'scripts/seed-content-parity.php',remote+'/seed-content-parity.php')
        deploy.upload(ftp,PROJECT/'scripts/activate-content-parity.php',remote+'/activate-content-parity.php')
        print(f'Private SQLite backup integrity OK; {len(changed)} changed files staged.',flush=True)
        action="""
$private=dirname(__DIR__,2).'/ldrn-releases/STAMP';$changed=[];
$hashes=json_decode(base64_decode('HASHES'),true);$baseline=json_decode(base64_decode('BASELINE'),true);
if(!hash_equals('ARCHIVEHASH',hash_file('sha256',$private.'/code.tar.gz')))throw new RuntimeException('Package hash mismatch.');
(new PharData($private.'/code.tar.gz'))->extractTo($private.'/staging');
foreach($hashes as $rel=>$hash){
 if(str_contains($rel,'..') || !hash_equals($hash,hash_file('sha256',$private.'/staging/'.$rel)))throw new RuntimeException('Staging validation failed.');
 $actual=is_file(__DIR__.'/'.$rel)?hash_file('sha256',__DIR__.'/'.$rel):null;
 if($actual!==$baseline[$rel])throw new RuntimeException('Live code changed during deployment.');
}
try {
 foreach($hashes as $rel=>$hash){
  $target=__DIR__.'/'.$rel;$old=$private.'/before/'.$rel;
  if(is_file($target)){if(!is_dir(dirname($old)))mkdir(dirname($old),0700,true);if(!copy($target,$old))throw new RuntimeException('Recovery copy failed.');}
  if(!is_dir(dirname($target)))mkdir(dirname($target),0755,true);
  if(!copy($private.'/staging/'.$rel,$target))throw new RuntimeException('Code copy failed.');$changed[]=$rel;
  if(!hash_equals($hash,hash_file('sha256',$target)))throw new RuntimeException('Installed hash mismatch.');
  if(function_exists('opcache_invalidate'))opcache_invalidate($target,true);
 }
 require __DIR__.'/wp-load.php';
$liveHash=hash('sha256',serialize(ldrn_ws_studio_live()));$draftHash=hash('sha256',serialize(ldrn_ws_studio_draft()));$rev=ldrn_ws_studio_rev();
 // The activation script saves recovery before the optional content migration.
 ob_start();require $private.'/activate-content-parity.php';$activation=json_decode(ob_get_clean(),true);
 if(empty($activation['ok']))throw new RuntimeException('Activation failed.');
 add_filter('intermediate_image_sizes_advanced',function(){return [];});
 add_filter('big_image_size_threshold','__return_false');
 $seed=(static function($file){ob_start();require $file;return json_decode(ob_get_clean(),true);})($private.'/seed-content-parity.php');
 if(!is_array($seed) || !empty($seed['missing']))throw new RuntimeException('Content media migration incomplete.');
 if($liveHash!==hash('sha256',serialize(ldrn_ws_studio_live())) || $draftHash!==hash('sha256',serialize(ldrn_ws_studio_draft())) || $rev!==ldrn_ws_studio_rev())throw new RuntimeException('Editor document preservation failed.');
 $session=ldrn_ws_capture_session('Website Content ready');
 if(!$session || !ldrn_sqlite_backup())throw new RuntimeException('Post-deploy recovery failed.');
 $result=['ok'=>true,'activation'=>$activation,'seed'=>$seed,'session'=>$session,'files'=>count($hashes),'draft_preserved'=>true,'live_preserved'=>true];
 file_put_contents($private.'/receipt.json',wp_json_encode($result));echo wp_json_encode($result);
}catch(Throwable $e){
 foreach(array_reverse($changed) as $rel){$old=$private.'/before/'.$rel;$target=__DIR__.'/'.$rel;if(is_file($old))copy($old,$target);elseif(is_file($target))unlink($target);if(function_exists('opcache_invalidate'))opcache_invalidate($target,true);}
 throw $e;
}
""".replace('STAMP',stamp).replace('HASHES',encoded(changed)).replace('BASELINE',encoded({rel:state['hashes'][rel] for rel in changed})).replace('ARCHIVEHASH',deploy.digest(archive))
        result=call(ftp,action)
        (backup/'receipt.json').write_text(json.dumps(result,indent=2))
        if not result.get('ok'):raise RuntimeError('Deployment failed.')
        after=inspect(ftp,sources)
        if any(after['hashes'][rel]!=hashes[rel] for rel in hashes):raise RuntimeError('Post-deploy file verification failed.')
        (backup/'after.json').write_text(json.dumps(after,indent=2))
        print(json.dumps({'backup':str(backup),'result':result,'all_files_verified':len(hashes),'counts':after['counts']},indent=2))
    finally:ftp.quit()
