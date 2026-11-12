"""Read-only live checks: no articles, events, jobs, drafts or test records."""
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor
import importlib.util,json,re,requests
from bs4 import BeautifulSoup
P=Path(__file__).resolve().parent.parent
s=importlib.util.spec_from_file_location('m',P/'scripts/deploy-content-update.py');m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
ftp=m.deploy.connect(json.loads((m.PRIVATE/'hosting-connection.json').read_text()))
try:
    result=m.call(ftp,r"""
require __DIR__.'/wp-load.php';$admins=get_users(['role'=>'administrator','number'=>1]);wp_set_current_user($admins[0]->ID);
$cfg=(new ReflectionProperty('Content_Forms','c'))->getValue();$types=[];
foreach(['post','ldrn_event','ldrn_setting','ldrn_testimonial','ldrn_job']as$type){$obj=get_post_type_object($type);$types[$type]=['configured'=>isset($cfg['types'][$type]),'can_create'=>current_user_can($obj->cap->create_posts),'published'=>(int)wp_count_posts($type)->publish];}
$actual=['blog_total'=>ldrn_get_blog_archive()['total'],'events'=>count(ldrn_get_events()),'nurseries'=>count(ldrn_get_settings()),'testimonials'=>ldrn_testimonial_query(1,24)['total'],'jobs'=>count(ldrn_get_jobs())];
$stored=['pages'=>get_option('ldrnseo_pages',null),'business'=>get_option('ldrnseo_business',null),'public'=>get_option('blog_public')];
$posts_before=(int)$GLOBALS['wpdb']->get_var('SELECT COUNT(*) FROM '.$GLOBALS['wpdb']->posts);
// In-request filters exercise configurable SEO without writing any test values.
add_filter('option_ldrnseo_pages',function($pages){$pages=(array)$pages;$pages['/contact']=['title'=>'Read-only SEO contract check','description'=>'Read-only description contract check','noindex'=>1,'image_id'=>0];return$pages;});
add_filter('option_ldrnseo_business',function($b){$b=(array)$b;$b['description']='Read-only business schema contract check';return$b;});
add_filter('option_blog_public','__return_true');
$meta=ldrn_seo_meta_for_path('/contact');$contract=apply_filters('ldrn_cms_data',['schemaVersion'=>4]);$entry=$contract['seo']['/contact'];$org=ldrnseo_org_node();
$sitemap=ldrn_seo_sitemap_entries();$hidden=true;foreach($sitemap as$row)if(str_ends_with($row['loc'],'/contact'))$hidden=false;
$checks=['page_title_connected'=>$meta['title']==='Read-only SEO contract check'&&$entry['title']===$meta['title'],'description_connected'=>$meta['description']==='Read-only description contract check'&&$entry['description']===$meta['description'],'business_schema_connected'=>$org['description']==='Read-only business schema contract check','noindex_connected'=>!$meta['index']&&!$entry['index']&&$hidden,'canonical_live_domain'=>$entry['canonical']===home_url('/contact'),'no_posts_created'=>(int)$GLOBALS['wpdb']->get_var('SELECT COUNT(*) FROM '.$GLOBALS['wpdb']->posts)===$posts_before];
echo wp_json_encode(['ok'=>!in_array(false,$checks,true),'content_types'=>$types,'public_contract_counts'=>$actual,'checks'=>$checks,'stored_blog_public'=>$stored['public'],'seo_routes'=>ldrnseo_routes()]);
""")
finally:ftp.quit()
assert result['ok'],result
site='https://ldrn.krildigital.com'
def page(path):
    response=requests.get(site+path,timeout=40);assert response.status_code==200,(path,response.status_code)
    soup=BeautifulSoup(response.text,'html.parser')
    canonicals=soup.select('link[rel=canonical]');assert len(canonicals)==1 and canonicals[0]['href']==site+path,(path,'canonical mismatch')
    assert len(soup.select('meta[name=description]'))==1
    graph=json.loads(soup.select_one('#ldrnseo-schema').string)
    org=next(n for n in graph['@graph'] if 'Organization' in ([n['@type']]if isinstance(n['@type'],str)else n['@type']))
    assert org['url']==site+'/' and org['address']['streetAddress']=='64 Pears Road'
    assert 'http://lrdn.local' not in response.text
    script=next((e.string for e in soup.find_all('script') if 'var ldrnData = ' in (e.string or '')),None);assert script
    data,_=json.JSONDecoder().raw_decode(script.split('var ldrnData = ',1)[1]);assert data['seo'][path]['canonical']==site+path
    assert data['seo'][path]['title']==soup.title.string
    return {'path':path,'status':response.status_code,'canonical':canonicals[0]['href'],'title':soup.title.string}
paths=['/','/our-nursery','/our-nursery/hounslow','/about','/about/ofsted','/curriculum','/events','/fees','/fee-calculator','/funded-childcare','/blogs','/food-hygiene-rating','/admissions','/check-availability','/careers','/careers/vacancies','/careers/apply','/testimonials','/contact','/privacy']
with ThreadPoolExecutor(max_workers=4)as pool:pages=list(pool.map(page,paths))
robots=requests.get(site+'/robots.txt',timeout=30);sitemap=requests.get(site+'/sitemap.xml',timeout=30)
assert robots.status_code==200 and sitemap.status_code==200
for path in ['/wp-json/ldrn/v1/blogs','/wp-json/ldrn/v1/testimonials']:
    r=requests.get(site+path,timeout=30);assert r.status_code==200 and isinstance(r.json(),dict)
result.update(public_pages=pages,robots=robots.text,sitemap_urls=len(BeautifulSoup(sitemap.text,'xml').find_all('loc')))
target=m.PRIVATE/'seo-live-verification.json';target.write_text(json.dumps(result,indent=2))
print(json.dumps({'ok':True,'pages_verified':len(pages),'checks':result['checks'],'public_contract_counts':result['public_contract_counts'],'blog_public':result['stored_blog_public'],'sitemap_urls':result['sitemap_urls'],'report':str(target)},indent=2))
