"""Deploy the existing local SEO suite and its server/React metadata contract."""
from pathlib import Path
import hashlib,importlib.util,json,tarfile,time
P=Path(__file__).resolve().parent.parent
s=importlib.util.spec_from_file_location('maintenance',P/'scripts/deploy-content-update.py');m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
stamp=time.strftime('%Y%m%dT%H%M%SZ',time.gmtime())+'-seo-suite'
private=m.PRIVATE/stamp;private.mkdir()
sources={}
root=P/'wordpress/plugins/ldrn-seo-suite'
for f in root.rglob('*'):
    if f.is_file() and f.suffix in {'.php','.js','.css','.svg','.png','.md'}:sources['wp-content/plugins/ldrn-seo-suite/'+f.relative_to(root).as_posix()]=f
theme=P/'wordpress/theme/ldrn-theme/dist'
manifest=json.loads((theme/'.vite/manifest.json').read_text())
assets={v['file'] for v in manifest.values() if 'file'in v}
assets.update(f for v in manifest.values() for f in v.get('css',[]))
assets.update(['.vite/manifest.json','index.html'])
for rel in assets:sources['wp-content/themes/ldrn-theme/dist/'+rel]=theme/rel
sources['wp-content/mu-plugins/ldrn-seo.php']=P/'wordpress/mu-plugins/ldrn-seo.php'
hashes={rel:m.deploy.digest(f) for rel,f in sources.items()}
ftp=m.deploy.connect(json.loads((m.PRIVATE/'hosting-connection.json').read_text()))
try:
    before=m.inspect(ftp,sources);(private/'before.json').write_text(json.dumps(before,indent=2))
    archive=private/'code.tar.gz'
    with tarfile.open(archive,'w:gz',format=tarfile.USTAR_FORMAT)as tar:
        for rel,f in sources.items():tar.add(f,arcname=rel,recursive=False)
    remote='domains/krildigital.com/ldrn-releases/'+stamp
    m.deploy.upload(ftp,archive,remote+'/code.tar.gz')
    m.deploy.upload(ftp,m.PRIVATE/'seo-settings-export.json',remote+'/settings.json')
    action=r"""
$private=dirname(__DIR__,2).'/ldrn-releases/STAMP';$done=[];$options=null;
$files=json_decode(base64_decode('FILES'),true);$baseline=json_decode(base64_decode('BASELINE'),true);
if(!hash_equals('ARCHIVE',hash_file('sha256',$private.'/code.tar.gz')))throw new RuntimeException('Package validation failed.');
(new PharData($private.'/code.tar.gz'))->extractTo($private.'/staging');
foreach($files as$rel=>$hash){$old=is_file(__DIR__.'/'.$rel)?hash_file('sha256',__DIR__.'/'.$rel):null;if($old!==$baseline[$rel]||str_contains($rel,'..')||!hash_equals($hash,hash_file('sha256',$private.'/staging/'.$rel)))throw new RuntimeException('Staging/baseline validation failed.');}
try{
 foreach($files as$rel=>$hash){$target=__DIR__.'/'.$rel;$old=$private.'/before/'.$rel;
  if(is_file($target)){if(!is_dir(dirname($old)))mkdir(dirname($old),0700,true);if(!copy($target,$old))throw new RuntimeException('Recovery copy failed.');}
  if(!is_dir(dirname($target)))mkdir(dirname($target),0755,true);
  if(!copy($private.'/staging/'.$rel,$target))throw new RuntimeException('Code copy failed.');$done[]=$rel;
  if(function_exists('opcache_invalidate'))opcache_invalidate($target,true);
 }
 require __DIR__.'/wp-load.php';require_once ABSPATH.'wp-admin/includes/plugin.php';
 $admins=get_users(['role'=>'administrator','number'=>1]);wp_set_current_user($admins[0]->ID);
 $options=[];foreach(['active_plugins','ldrn_ws_active_build','ldrnseo_business','ldrnseo_settings','ldrnseo_pages','ldrnseo_listings','ldrn_site_settings','blog_public']as$key)$options[$key]=get_option($key,null);
 file_put_contents($private.'/options-before.json',wp_json_encode($options));chmod($private.'/options-before.json',0600);
 $live=hash('sha256',serialize(ldrn_ws_studio_live()));$draft=hash('sha256',serialize(ldrn_ws_studio_draft()));$rev=ldrn_ws_studio_rev();
 foreach((array)$options['active_plugins']as$plugin)if(preg_match('~^(wordpress-seo|seo-by-rank-math)/~',$plugin))throw new RuntimeException('Conflicting SEO plugin found.');
 $activation=activate_plugin('ldrn-seo-suite/ldrn-seo-suite.php','',false,false);if(is_wp_error($activation))throw new RuntimeException($activation->get_error_message());
 $input=json_decode(file_get_contents($private.'/settings.json'),true);
 foreach($input['options']as$key=>$value){$existing=get_option($key,[]);$value=array_replace_recursive((array)$value,(array)$existing);$encoded=str_replace(['http://lrdn.local','https://lrdn.local'],home_url(),wp_json_encode($value));update_option($key,json_decode($encoded,true),false);}
 if(!empty($input['shared'])){$site=(array)get_option('ldrn_site_settings',[]);foreach($input['shared']as$key=>$value)if(empty($site[$key])&&!empty($value))$site[$key]=$value;update_option('ldrn_site_settings',$site,false);}
 global $wpdb;$tables=[];foreach(['hits','bots','notfound','redirects']as$name){$table=ldrnseo_table($name);if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table)throw new RuntimeException('SEO table missing: '.$name);$tables[]=$table;}
 $build=ldrn_ws_store_build(['data'=>ldrn_ws_snapshot_data(),'studio'=>ldrn_ws_studio_live()],true);
 if(is_wp_error($build)||!ldrn_ws_build_valid($build))throw new RuntimeException('Frontend build validation failed.');update_option('ldrn_ws_active_build',$build,false);
 if($live!==hash('sha256',serialize(ldrn_ws_studio_live()))||$draft!==hash('sha256',serialize(ldrn_ws_studio_draft()))||$rev!==ldrn_ws_studio_rev())throw new RuntimeException('Editor state preservation failed.');
 if(!ldrn_sqlite_backup())throw new RuntimeException('Private database backup failed.');
 $result=['ok'=>true,'files'=>count($files),'tables'=>count($tables),'build'=>$build['hash'],'blog_public'=>get_option('blog_public'),'live_preserved'=>true,'draft_preserved'=>true];file_put_contents($private.'/receipt.json',wp_json_encode($result));echo wp_json_encode($result);
}catch(Throwable$e){
 if($options!==null)foreach($options as$key=>$value){if($value===null)delete_option($key);else update_option($key,$value,false);}
 foreach(array_reverse($done)as$rel){$old=$private.'/before/'.$rel;$target=__DIR__.'/'.$rel;if(is_file($old))copy($old,$target);elseif(is_file($target))unlink($target);if(function_exists('opcache_invalidate'))opcache_invalidate($target,true);}throw$e;
}
""".replace('STAMP',stamp).replace('FILES',m.encoded(hashes)).replace('BASELINE',m.encoded(before['hashes'])).replace('ARCHIVE',m.deploy.digest(archive))
    print('SEO suite and frontend package staged privately; activating.',flush=True)
    result=m.call(ftp,action);(private/'receipt.json').write_text(json.dumps(result,indent=2))
    if not result.get('ok'):raise RuntimeError('SEO activation failed.')
    after=m.inspect(ftp,sources);(private/'after.json').write_text(json.dumps(after,indent=2))
    if any(after['hashes'][rel]!=hashes[rel] for rel in hashes):raise RuntimeError('File parity failed.')
    if after['counts']!=before['counts']:raise RuntimeError('Content counts changed unexpectedly.')
    print(json.dumps({'result':result,'verified_files':len(hashes),'content_counts_preserved':True,'backup':str(private)},indent=2))
finally:ftp.quit()
