"""Targeted, backed-up Site Editor allocation and Two Factor deployment."""
from pathlib import Path
import importlib.util,json,tarfile,time,subprocess
P=Path(__file__).resolve().parent.parent
spec=importlib.util.spec_from_file_location('m',P/'scripts/deploy-content-update.py');m=importlib.util.module_from_spec(spec);spec.loader.exec_module(m)
PHP=Path(r'C:\OUT_OF_ONEDRIVE\Local\resources\extraResources\lightning-services\php-8.2.29+0\bin\win64\php.exe')
relative=['wordpress/plugins/ldrn-workspace/ldrn-workspace.php','wordpress/plugins/ldrn-workspace/includes/access.php','wordpress/plugins/ldrn-workspace/includes/studio.php','wordpress/plugins/ldrn-workspace/assets/studio.js','wordpress/plugins/ldrn-workspace/assets/studio-runtime.js','wordpress/theme/ldrn-theme/inc/ldrn-roles.php','wordpress/mu-plugins/ldrn-2fa-enforce.php']
sources={('wp-content/'+r.removeprefix('wordpress/')).replace('wp-content/theme/','wp-content/themes/'):P/r for r in relative}
for f in (P/'wordpress/plugins/two-factor').rglob('*'):
 if f.is_file():sources['wp-content/plugins/two-factor/'+f.relative_to(P/'wordpress/plugins/two-factor').as_posix()]=f
for f in sources.values():
 if f.suffix=='.php':
  r=subprocess.run([str(PHP),'-l',str(f)],capture_output=True,text=True)
  if r.returncode:raise RuntimeError(r.stdout+r.stderr)
ftp=m.deploy.connect(json.loads((m.PRIVATE/'hosting-connection.json').read_text()))
stamp=time.strftime('%Y%m%dT%H%M%SZ',time.gmtime())+'-user-access';backup=m.PRIVATE/stamp;backup.mkdir()
try:
 before=m.inspect(ftp,sources);(backup/'before.json').write_text(json.dumps(before,indent=2))
 hashes={r:m.deploy.digest(f) for r,f in sources.items() if m.deploy.digest(f)!=before['hashes'][r]}
 archive=backup/'code.tar.gz'
 with tarfile.open(archive,'w:gz',format=tarfile.USTAR_FORMAT) as tar:
  for rel in hashes:tar.add(sources[rel],arcname=rel,recursive=False)
 remote='domains/krildigital.com/ldrn-releases/'+stamp
 m.deploy.upload(ftp,archive,remote+'/code.tar.gz')
 print('PHP lint passed; private database backup complete; staging '+str(len(hashes))+' changed files.',flush=True)
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
 $options=['active_plugins'=>get_option('active_plugins'),'ldrn_force_2fa'=>get_option('ldrn_force_2fa',null)];
 $admins=get_users(['role'=>'administrator']);$userMeta=[];
 foreach($admins as$admin)foreach(['_two_factor_enabled_providers','_two_factor_provider']as$key)$userMeta[$admin->ID][$key]=get_user_meta($admin->ID,$key,false);
 file_put_contents($private.'/auth-before.json',wp_json_encode(['options'=>$options,'user_meta'=>$userMeta]));chmod($private.'/auth-before.json',0600);
 $activation=activate_plugin('two-factor/two-factor.php','',false,false);if(is_wp_error($activation))throw new RuntimeException($activation->get_error_message());
 if(class_exists('Two_Factor_Core'))Two_Factor_Core::get_providers();
 if(!class_exists('Two_Factor_Core') || !class_exists('Two_Factor_Email'))throw new RuntimeException('Two Factor did not initialize.');
 // Check the actual private SMTP transport without sending a message.
 require_once ABSPATH.WPINC.'/PHPMailer/PHPMailer.php';require_once ABSPATH.WPINC.'/PHPMailer/SMTP.php';require_once ABSPATH.WPINC.'/PHPMailer/Exception.php';
 $mail=new PHPMailer\PHPMailer\PHPMailer(true);do_action('phpmailer_init',$mail);
 if($mail->Mailer!=='smtp' || !$mail->smtpConnect())throw new RuntimeException('Authenticated email transport is unavailable. 2FA was not enforced.');
 $mail->smtpClose();
 update_option('ldrn_force_2fa',true,false);
 foreach($admins as$admin)ldrn_2fa_ensure_email($admin->ID);
 $secured=[];foreach($admins as$admin)$secured[]=Two_Factor_Core::is_user_using_two_factor($admin);
 if(in_array(false,$secured,true))throw new RuntimeException('An administrator has no available second factor.');
 $result=['ok'=>true,'files'=>count($files),'two_factor_version'=>TWO_FACTOR_VERSION,'smtp_authenticated'=>true,'admin_2fa_enabled'=>count($secured),'force_2fa'=>ldrn_2fa_enforced()];
 file_put_contents($private.'/receipt.json',wp_json_encode($result));echo wp_json_encode($result);
}catch(Throwable$e){
 if($options!==null)foreach($options as$key=>$value){if($value===null)delete_option($key);else update_option($key,$value,false);}
 if(!empty($userMeta))foreach($userMeta as$id=>$values)foreach($values as$key=>$items){delete_user_meta($id,$key);foreach($items as$value)add_user_meta($id,$key,$value);}
 foreach(array_reverse($done)as$rel){$old=$private.'/before/'.$rel;$target=__DIR__.'/'.$rel;if(is_file($old))copy($old,$target);elseif(is_file($target))unlink($target);if(function_exists('opcache_invalidate'))opcache_invalidate($target,true);}throw$e;
}
""".replace('STAMP',stamp).replace('FILES',m.encoded(hashes)).replace('BASELINE',m.encoded(before['hashes'])).replace('ARCHIVE',m.deploy.digest(archive))
 result=m.call(ftp,action);(backup/'receipt.json').write_text(json.dumps(result,indent=2))
 after=m.inspect(ftp,sources);(backup/'after.json').write_text(json.dumps(after,indent=2))
 assert all(after['hashes'][r]==m.deploy.digest(f) for r,f in sources.items()),'File parity failed'
 assert all(before[k]==after[k] for k in ['live','draft','revision','active_build','counts']),'Public/editor state changed'
 print(json.dumps({'backup':str(backup),'result':result,'source_files_verified':len(sources),'public_and_editor_state_preserved':True},indent=2))
finally:ftp.quit()
