"""Live permissions verification, with scoped disposable users and cleanup."""
from pathlib import Path
import importlib.util,json,requests,copy,re,time
P=Path(__file__).resolve().parent.parent
spec=importlib.util.spec_from_file_location('m',P/'scripts/deploy-content-update.py');m=importlib.util.module_from_spec(spec);spec.loader.exec_module(m)
ftp=m.deploy.connect(json.loads((m.PRIVATE/'hosting-connection.json').read_text()));users=[]
report={}
def call(code):return m.call(ftp,"require __DIR__.'/wp-load.php';"+code)
def check(name,condition):
 report[name]=bool(condition)
 if not condition:raise AssertionError(name)
try:
 state=call(r"""
$admins=get_users(['role'=>'administrator','number'=>1]);wp_set_current_user($admins[0]->ID);$out=[];
foreach(['editor'=>LDRN_CONTENT_MANAGER_ROLE,'empty'=>LDRN_CONTENT_MANAGER_ROLE,'viewer'=>LDRN_VIEWER_ROLE]as$name=>$role){
$id=wp_insert_user(['user_login'=>'ldrn_access_test_'.strtolower(wp_generate_password(14,false,false)),'user_pass'=>wp_generate_password(32,true,true),'role'=>$role]);if(is_wp_error($id))throw new RuntimeException($id->get_error_message());
$_POST=['ldrn_ws_page_access_nonce'=>wp_create_nonce('ldrn_ws_save_page_access'),'ldrn_ws_allowed_pages'=>$name==='empty'?[]:['contact','nonexistent']];
ldrn_ws_access_save($id);update_user_meta($id,LDRN_USER_SECTIONS_META,[]);
wp_set_current_user($id);$user=wp_get_current_user();$expiry=time()+900;$token=WP_Session_Tokens::get_instance($id)->create($expiry);$_COOKIE[LOGGED_IN_COOKIE]=wp_generate_auth_cookie($id,$expiry,'logged_in',$token);
$out[$name]=['id'=>$id,'routes'=>ldrn_ws_access_routes(),'can'=>ldrn_ws_access_can_edit(),'cap'=>current_user_can('edit_ldrn_site_pages'),'manage_options'=>current_user_can('manage_options'),'cookies'=>[SECURE_AUTH_COOKIE=>wp_generate_auth_cookie($id,$expiry,'secure_auth',$token),LOGGED_IN_COOKIE=>wp_generate_auth_cookie($id,$expiry,'logged_in',$token)],'nonce'=>wp_create_nonce('ldrn_ws_studio')];
unset($_COOKIE[LOGGED_IN_COOKIE]);wp_set_current_user($admins[0]->ID);
}
$_POST=[];echo wp_json_encode(['users'=>$out,'draft'=>ldrn_ws_studio_draft(),'rev'=>ldrn_ws_studio_rev(),'live'=>hash('sha256',serialize(ldrn_ws_studio_live())),'build'=>get_option('ldrn_ws_active_build')['hash']??null,'blog_public'=>get_option('blog_public')]);
""")
 users=[v['id'] for v in state['users'].values()]
 (m.PRIVATE/'user-access-test-state.json').write_text(json.dumps(state))
 check('editor_exact_page_allocation',state['users']['editor']['routes']==['/contact'])
 check('editor_cap_without_manage_options',state['users']['editor']['cap'] and not state['users']['editor']['manage_options'])
 check('empty_editor_denied',not state['users']['empty']['can'] and not state['users']['empty']['cap'])
 check('viewer_denied_even_with_page_metadata',not state['users']['viewer']['can'] and not state['users']['viewer']['cap'])
 tests=call(r"""
wp_set_current_user(EDITOR);$checks=[];
$base=['nodes'=>['t>1'=>['text'=>'Shared'],'m:/about>1'=>['text'=>'About']],'blocks'=>['zzzz'=>['at'=>'m:/about','pos'=>'append','tree'=>['id'=>'zzzz','tag'=>'div','kids'=>[['id'=>'yyyy','tag'=>'p','text'=>'Protected']]]]]];
$next=$base;$next['nodes']['m:/contact>1']=['text'=>'Allowed'];$checks['assigned_node_edit']=!is_wp_error(ldrn_ws_access_validate_doc($next,$base));
foreach(['m:/about>1','w:about.section-1','t>1','n:yyyy']as$key){$next=$base;$next['nodes'][$key]=['text'=>'Forbidden'];$checks['denied_'.$key]=is_wp_error(ldrn_ws_access_validate_doc($next,$base));}
$next=$base;unset($next['nodes']['m:/about>1']);$checks['protected_deletion']=is_wp_error(ldrn_ws_access_validate_doc($next,$base));
$next=$base;$next['blocks']['zzzz']['at']='m:/contact';$checks['protected_block_move']=is_wp_error(ldrn_ws_access_validate_doc($next,$base));
$next=$base;$next['blocks']['abcd']=['at'=>'m:/contact','pos'=>'append','tree'=>['id'=>'abcd','tag'=>'div','kids'=>[['id'=>'yyyy','tag'=>'p']]]];$checks['duplicate_child_id']=is_wp_error(ldrn_ws_access_validate_doc($next,$base));
$next=$base;$next['blocks']['abcd']=['at'=>'m:/contact','pos'=>'append','tree'=>['id'=>'abcd','tag'=>'div','kids'=>[['id'=>'efgh','tag'=>'p']]]];$next['nodes']['n:efgh']=['text'=>'Allowed'];$checks['assigned_inserted_descendant']=!is_wp_error(ldrn_ws_access_validate_doc($next,$base));
$next=$base;$next['groups']=['m:/contact>1'=>'m:/about>1'];$checks['cross_page_group_denied']=is_wp_error(ldrn_ws_access_validate_doc($next,$base));
$next=$base;$next['layerOrder']=['page'=>['m:/contact>1','m:/about>1']];$checks['cross_page_order_denied']=is_wp_error(ldrn_ws_access_validate_doc($next,$base));
$provider=Two_Factor_Email::get_instance();$token=$provider->generate_token(EDITOR);
$checks['otp_wrong_rejected']=!$provider->validate_token(EDITOR,'invalid');$checks['otp_valid_accepted']=$provider->validate_token(EDITOR,$token);$checks['otp_replay_rejected']=!$provider->validate_token(EDITOR,$token);
$token=$provider->generate_token(EDITOR);update_user_meta(EDITOR,Two_Factor_Email::TOKEN_META_KEY_TIMESTAMP,time()-3600);$checks['otp_expired_rejected']=!$provider->validate_token(EDITOR,$token);$provider->delete_token(EDITOR);
$admins=get_users(['role'=>'administrator']);wp_set_current_user($admins[0]->ID);$checks['noncurrent_viewer_cap_denied']=!user_can(VIEWER,'edit_ldrn_site_pages');
$checks['admin_all_pages']=count(ldrn_ws_access_routes())===count(ldrn_ws_access_catalog());
$checks['admin_2fa_enabled']=ldrn_2fa_enforced()&&Two_Factor_Core::is_user_using_two_factor($admins[0]);$checks['admin_application_password_bypass_disabled']=!wp_is_application_passwords_available_for_user($admins[0]);
echo wp_json_encode($checks);
""".replace('EDITOR',str(state['users']['editor']['id'])).replace('VIEWER',str(state['users']['viewer']['id'])))
 for name,value in tests.items():check(name,value)
 sessions={}
 for name,user in state['users'].items():
  s=requests.Session();s.cookies.update(user['cookies']);sessions[name]=s
  r=s.get(m.deploy.SITE+'/wp-admin/admin.php?page=ldrn-studio',timeout=60)
  if name=='editor':
   check('editor_admin_screen_http_200',r.status_code==200 and 'id="ldrn-studio"' in r.text)
   match=re.search(r'var ldrnStudio = (\{.*?\});',r.text)
   cfg=json.loads(match.group(1))
   check('editor_only_assigned_page_in_ui', [p['route'] for p in cfg['pages']]==['/contact'] and cfg['start']=='/contact' and cfg['scoped'])
  else:check(name+'_admin_screen_http_403',r.status_code==403)
 s=sessions['editor'];nonce=state['users']['editor']['nonce'];rev=state['rev']
 for route,expected in [('/contact',200),('/about',403)]:
  r=s.get(m.deploy.SITE+route+'?ldrn_ws_studio=1',timeout=60)
  check('canvas_'+route,r.status_code==expected)
  if expected==200:check('canvas_scoped_runtime', '"scoped":true' in r.text and 'assets/studio-runtime.js' in r.text)
 for action in ['ldrn_ws_studio_page_add','ldrn_ws_studio_page_update','ldrn_ws_studio_page_delete','ldrn_ws_studio_discard','ldrn_ws_studio_restore','ldrn_ws_template_save']:
  r=s.post(m.deploy.SITE+'/wp-admin/admin-ajax.php',data={'action':action,'nonce':nonce,'rev':rev,'label':'Forbidden'},timeout=60)
  check(action+'_http_403',r.status_code==403)
 for key in ['m:/about>1','t>1']:
  doc=copy.deepcopy(state['draft']);doc['nodes'][key]={'text':'Forbidden verification'}
  r=s.post(m.deploy.SITE+'/wp-admin/admin-ajax.php',data={'action':'ldrn_ws_studio_save','nonce':nonce,'rev':rev,'doc':json.dumps(doc)},timeout=60)
  check('crafted_save_'+key,r.status_code==403 and not r.json()['success'])
 r=s.post(m.deploy.SITE+'/wp-admin/admin-ajax.php',data={'action':'ldrn_ws_studio_save','nonce':nonce,'doc':json.dumps(state['draft'])},timeout=60)
 check('missing_revision_rejected',r.status_code==409)
 r=s.post(m.deploy.SITE+'/wp-admin/admin-ajax.php',data={'action':'ldrn_ws_studio_save','nonce':nonce,'rev':rev,'force':'1','doc':json.dumps(state['draft'])},timeout=60)
 check('forced_overwrite_rejected',r.status_code==409)
 r=s.post(m.deploy.SITE+'/wp-admin/admin-ajax.php',data={'action':'ldrn_ws_studio_save','nonce':nonce,'rev':rev,'doc':json.dumps(state['draft'])},timeout=60)
 check('editor_current_document_save',r.status_code==200 and r.json()['success'])
 for name in ['empty','viewer']:
  r=sessions[name].post(m.deploy.SITE+'/wp-admin/admin-ajax.php',data={'action':'ldrn_ws_studio_save','nonce':state['users'][name]['nonce'],'rev':rev,'doc':json.dumps(state['draft'])},timeout=60)
  check(name+'_save_http_403',r.status_code==403)
 # Actual publish is exercised within a rollback transaction: no public edit is committed.
 publish=call(r"""
wp_set_current_user(EDITOR);global $wpdb;$wpdb->query('START TRANSACTION');$GLOBALS['ldrn_ws_external_transaction']=true;
$next=ldrn_ws_studio_live();$next['nodes']['m:/contact>1']=['text'=>'Permission verification (rolled back)'];
$result=ldrn_ws_publish_document($next,'Permission verification (rolled back)');
$published=!is_wp_error($result) && ldrn_ws_studio_live()['nodes']['m:/contact>1']['text']==='Permission verification (rolled back)';
$wpdb->query('ROLLBACK');unset($GLOBALS['ldrn_ws_external_transaction']);wp_cache_flush();
echo wp_json_encode(['ok'=>!is_wp_error($result),'assigned_change_published_in_transaction'=>$published,'error'=>is_wp_error($result)?$result->get_error_message():null]);
""".replace('EDITOR',str(state['users']['editor']['id'])))
 check('actual_publish_rolled_back',publish['ok'])
 check('assigned_page_publish_applied_in_transaction',publish['assigned_change_published_in_transaction'])
 print(json.dumps({'checks_passed':len(report),'results':report},indent=2))
finally:
 if users:
  cleanup=call(r"""
require_once ABSPATH.'wp-admin/includes/user.php';$ids=json_decode(base64_decode('IDS'),true);$deleted=[];
foreach($ids as$id){$u=get_userdata($id);if(!$u||!str_starts_with($u->user_login,'ldrn_access_test_'))throw new RuntimeException('Refusing to clean an unrelated user.');WP_Session_Tokens::get_instance($id)->destroy_all();$deleted[]=wp_delete_user($id);}
echo wp_json_encode(['temporary_users_deleted'=>$deleted,'draft'=>ldrn_ws_studio_draft(),'rev'=>ldrn_ws_studio_rev(),'live'=>hash('sha256',serialize(ldrn_ws_studio_live())),'build'=>get_option('ldrn_ws_active_build')['hash']??null,'blog_public'=>get_option('blog_public')]);
""".replace('IDS',m.encoded(users)))
  check('temporary_users_cleaned',all(cleanup['temporary_users_deleted']))
  for key in ['draft','rev','live','build','blog_public']:check('preserved_'+key,cleanup[key]==state[key])
  (m.PRIVATE/'user-access-verification.json').write_text(json.dumps({'checks_passed':len(report),'results':report,'cleanup':cleanup},indent=2))
 ftp.quit()
