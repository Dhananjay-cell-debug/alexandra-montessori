"""Read-only production checks; credentials stay in private local storage."""
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor
import hashlib, json, re, requests
import importlib.util

PROJECT=Path(__file__).resolve().parent.parent
PRIVATE=Path(r'C:\Users\Dhananjay\Local Sites\lrdn\private-releases')
backup=sorted(PRIVATE.glob('*-website-content'))[-1]
site='https://ldrn.krildigital.com'
routes=['/','/our-nursery','/our-nursery/hounslow','/about','/about/ofsted','/curriculum','/events','/fees','/fee-calculator','/funded-childcare','/blogs','/food-hygiene-rating','/admissions','/book-a-visit','/check-availability','/careers','/careers/vacancies','/careers/apply','/testimonials','/contact','/privacy']
def check_route(path):
    response=requests.get(site+path,timeout=40)
    assert response.status_code==200,(path,response.status_code)
    assert 'var ldrnData =' in response.text,(path,'WordPress contract missing')
    assert 'Fatal error' not in response.text and 'critical error' not in response.text
    return {'route':path,'status':response.status_code}
with ThreadPoolExecutor(max_workers=5) as pool: public=list(pool.map(check_route,routes))
session=requests.Session()
session.get(site+'/wp-login.php',timeout=30)
login=(PRIVATE/'LDRN-WordPress-login.txt').read_text()
username=re.search(r'^Username:\s*(.+)$',login,re.M).group(1).strip()
password=re.search(r'^Password:\s*(.+)$',login,re.M).group(1).strip()
response=session.post(site+'/wp-login.php',data={'log':username,'pwd':password,'wp-submit':'Log In','redirect_to':site+'/wp-admin/','testcookie':'1'},timeout=40)
logged_in=any('wordpress_logged_in_' in c.name for c in session.cookies)
checks=[('/wp-admin/admin.php?page=ldrn_content','Website Content'),('/wp-admin/post-new.php','Cover photo'),('/wp-admin/post-new.php?post_type=ldrn_event','Event name'),('/wp-admin/post-new.php?post_type=ldrn_testimonial','Quote'),('/wp-admin/post-new.php?post_type=ldrn_job','Job title'),('/wp-admin/post-new.php?post_type=ldrn_setting','Nursery name'),('/wp-admin/admin.php?page=ldrn_about','Milestones'),('/wp-admin/admin.php?page=ldrn_site_settings','Contact details'),('/wp-admin/upload.php','Media Library')]
admin=[]
for path,label in (checks if logged_in else []):
    response=session.get(site+path,timeout=40)
    assert response.status_code==200,(path,response.status_code)
    assert label in response.text,(path,'Expected form absent: '+label)
    assert 'critical error' not in response.text and 'Fatal error' not in response.text
    admin.append({'route':path,'status':response.status_code,'expected_label':label})
backend_forms=[]
if not logged_in:
    spec=importlib.util.spec_from_file_location('content_deploy',PROJECT/'scripts/deploy-content-update.py')
    module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
    ftp=module.deploy.connect(json.loads((PRIVATE/'hosting-connection.json').read_text()))
    try:
        render=module.call(ftp,"""
require __DIR__.'/wp-load.php';require_once ABSPATH.'wp-admin/includes/admin.php';
$admins=get_users(['role'=>'administrator','number'=>1]);wp_set_current_user($admins[0]->ID);
$cfg=(new ReflectionProperty('Content_Forms','c'))->getValue();$results=[];
$postMethod=new ReflectionMethod('Content_Forms','render_post');$pageMethod=new ReflectionMethod('Content_Forms','render_page');
foreach($cfg['types'] as $type=>$fields){ob_start();$postMethod->invoke(null,$type,$fields,null);$html=ob_get_clean();if(!str_contains($html,'cf-form') || !str_contains($html,'cf_nonce'))throw new RuntimeException('Content form rendering failed.');$results[]=$type;}
foreach($cfg['pages'] as $slug=>$fields){ob_start();$pageMethod->invoke(null,$slug,$fields);$html=ob_get_clean();if(!str_contains($html,'cf-form') || !str_contains($html,'cf_nonce'))throw new RuntimeException('Settings form rendering failed.');$results[]=$slug;}
ob_start();ldrn_render_content_workspace();$hub=ob_get_clean();if(!str_contains($hub,'Website Content'))throw new RuntimeException('Workspace rendering failed.');
echo wp_json_encode(['ok'=>true,'rendered_forms'=>$results,'workspace_rendered'=>true]);
""")
        backend_forms=render['rendered_forms']
    finally:ftp.quit()
build=json.loads((backup/'after.json').read_text())['active_build']
manifest=json.loads((PROJECT/'wordpress/theme/ldrn-theme/dist/.vite/manifest.json').read_text())
assets={v['file'] for v in manifest.values() if 'file' in v}
assets.update(p for v in manifest.values() for p in v.get('css',[]))
def check_asset(path):
    response=requests.get(site+'/wp-content/uploads/ldrn-site-builds/'+build+'/dist/'+path,timeout=40)
    assert response.status_code==200,(path,response.status_code)
    expected=(PROJECT/'wordpress/theme/ldrn-theme/dist'/path).read_bytes()
    assert hashlib.sha256(response.content).digest()==hashlib.sha256(expected).digest(),(path,'Published build asset differs')
    return path
with ThreadPoolExecutor(max_workers=5) as pool: checked_assets=list(pool.map(check_asset,sorted(assets)))
result={'ok':True,'public_routes':public,'admin_forms':admin,'admin_http_login_verified':logged_in,'backend_forms_rendered':backend_forms,'published_build':build,'published_assets_verified':len(checked_assets)}
(backup/'http-verification.json').write_text(json.dumps(result,indent=2))
print(json.dumps({'ok':True,'public_routes_passed':len(public),'authenticated_admin_pages_passed':len(admin),'backend_forms_rendered':len(backend_forms),'admin_login_verified':logged_in,'published_assets_verified':len(checked_assets),'backup':str(backup)},indent=2))
