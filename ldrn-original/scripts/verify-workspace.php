<?php
// Run only through wp-local.ps1 eval-file. All mutations are rolled back.
if (parse_url(home_url(), PHP_URL_HOST) !== 'lrdn.local') throw new RuntimeException('Local verification only.');
global $wpdb;
$tables = array($wpdb->options, $wpdb->posts, $wpdb->postmeta);
foreach ($tables as $table) {
    $row = $wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS WHERE Name = %s', $table));
    if (!$row || strtolower($row->Engine) !== 'innodb') throw new RuntimeException('Transactional tables required.');
}
$GLOBALS['checks'] = 0;
function verify($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
class WorkspaceJsonStop extends RuntimeException {}
add_filter('wp_die_ajax_handler', function() { return function() { throw new WorkspaceJsonStop(); }; });
add_filter('wp_die_handler', function() { return function() { throw new WorkspaceJsonStop(); }; });
if (!defined('DOING_AJAX')) define('DOING_AJAX', true);
function workspace_action($action, $fields = array()) {
    $_POST = wp_slash(array_merge(array('nonce'=>wp_create_nonce('ldrn_ws_studio'), 'rev'=>ldrn_ws_studio_rev()), $fields));
    $_REQUEST = $_POST;
    ob_start();
    try { do_action('wp_ajax_'.$action); } catch (WorkspaceJsonStop $stop) {}
    $result = json_decode(ob_get_clean(), true);
    if (!is_array($result)) throw new RuntimeException('Action did not return JSON: '.$action);
    return $result;
}
$admins = get_users(array('role'=>'administrator','number'=>1));
if (!$admins) throw new RuntimeException('Local administrator required.');
wp_set_current_user($admins[0]->ID);
$wpdb->query('START TRANSACTION');
$GLOBALS['ldrn_ws_external_transaction']=true;
try {
    $fixtures = json_decode(file_get_contents(__DIR__.'/library-fixtures.json'), true);
    foreach ($fixtures as $fixture) {
        $id = $fixture['tree']['id'];
        $doc = ldrn_ws_studio_clean_doc(array('nodes'=>$fixture['nodes'],'blocks'=>array($id=>array('at'=>'m:/verify-workspace','pos'=>'append','tree'=>$fixture['tree']))));
        verify(isset($doc['blocks'][$id]), $fixture['id'].' root was rejected');
        $count = function($tree) use (&$count) { return 1 + array_sum(array_map($count, $tree['kids'] ?? array())); };
        verify($count($doc['blocks'][$id]['tree']) === $count($fixture['tree']), $fixture['id'].' lost an editable layer');
        foreach ($fixture['nodes'] as $key=>$node) {
            foreach ($node['s'] as $bp=>$styles) foreach ($styles as $prop=>$value) {
                verify(($doc['nodes'][$key]['s'][$bp][$prop] ?? null) === $value, $fixture['id'].' lost '.$prop.' at '.$bp);
            }
        }
    }
    $bad = ldrn_ws_studio_clean_doc(array('nodes'=>array('n:abcd1234'=>array('href'=>'javascript:alert(1)','s'=>array('d'=>array('position'=>'fixed','behavior'=>'url(x)')))), 'blocks'=>array('abcd1234'=>array('at'=>'m:/','pos'=>'append','tree'=>array('id'=>'abcd1234','tag'=>'script','text'=>'alert(1)')))));
    verify(empty($bad['blocks']), 'Script block accepted');
    verify(empty($bad['nodes']['n:abcd1234']['href']), 'Unsafe URL accepted');
    verify(empty($bad['nodes']['n:abcd1234']['s']['d']['behavior']), 'Unsupported CSS accepted');
    $budget=20;
    $icon=ldrn_ws_studio_clean_tree(array('id'=>'icon1234','tag'=>'svg','attrs'=>array('viewBox'=>'0 0 24 24','stroke'=>'currentColor','onload'=>'alert(1)','fill'=>'url(https://example.com/icon)'), 'kids'=>array(
        array('id'=>'path1234','tag'=>'path','attrs'=>array('d'=>'M 3 12 L 21 12','stroke-width'=>'2','onclick'=>'alert(1)')),
        array('id'=>'evil1234','tag'=>'foreignObject','kids'=>array(array('id'=>'img12345','tag'=>'img','src'=>'/image.jpg'))),
        array('id'=>'link1234','tag'=>'a','href'=>'/contact'),
    )),0,$budget);
    verify(($icon['attrs']['viewBox']??'')==='0 0 24 24' && count($icon['kids'])===1,'Copied SVG icon lost its geometry or allowed unsafe children');
    verify(!isset($icon['attrs']['onload'],$icon['attrs']['fill']) && !isset($icon['kids'][0]['attrs']['onclick']),'SVG executable or external attributes accepted');
    verify(($icon['kids'][0]['attrs']['d']??'')==='M 3 12 L 21 12','SVG path geometry lost at save');
    $live_before = ldrn_ws_studio_live();
    $public_before = ldrn_ws_public_pages();
    $add = workspace_action('ldrn_ws_studio_page_add', array('label'=>'Verification page','slug'=>'verify-workspace'));
    verify($add['success'], 'Page creation failed');
    verify(ldrn_ws_public_pages() === $public_before, 'Draft page leaked to live navigation');
    $save = workspace_action('ldrn_ws_studio_save', array('doc'=>wp_json_encode($doc)));
    verify($save['success'], 'Draft save failed');
    verify(ldrn_ws_studio_live() === $live_before, 'Draft design leaked to live');
    $conflict = workspace_action('ldrn_ws_studio_save', array('rev'=>'outdated','doc'=>wp_json_encode(array('nodes'=>array()))));
    verify(!$conflict['success'] && !empty($conflict['data']['conflict']), 'Concurrent draft overwrite was accepted');
    $publish = workspace_action('ldrn_ws_studio_publish', array('name'=>'Verification publication','doc'=>wp_json_encode($doc)));
    verify($publish['success'], 'Publication failed');
    verify(ldrn_ws_route_has_content('/verify-workspace'), 'Published custom route has no content');
    verify(!ldrn_ws_route_has_content('/verify'), 'Route prefix matched a different page');
    verify(isset(ldrn_seo_static_routes()['/verify-workspace']), 'Custom route missing from server metadata');
    $session = ldrn_ws_capture_session('Verification session');
    verify((bool) ldrn_ws_session($session), 'Saved session unreadable');
    $saved=ldrn_ws_session($session);
    verify(ldrn_ws_build_valid($saved['site']['build']), 'Session does not preserve its actual website files');
    verify(isset($saved['site']['data']['allTestimonials']), 'Session missed parent stories');
    verify(!isset($saved['site']['options']['ldrn_ops_settings']), 'Session included delivery configuration');
    $clean=ldrn_ws_studio_clean_doc(array('nodes'=>array('n:abcd1234'=>array('label'=>'Our nursery','placeholder'=>'Your name','href'=>'/contact','newTab'=>true))));
    verify($clean['nodes']['n:abcd1234']['label']==='Our nursery' && $clean['nodes']['n:abcd1234']['placeholder']==='Your name' && $clean['nodes']['n:abcd1234']['newTab']===true,'Button label or destination controls lost at save');
    workspace_action('ldrn_ws_studio_page_update', array('id'=>'home','visible'=>'0'));
    verify(ldrn_ws_studio_status()['unpublished'], 'Directory-only change was not marked unpublished');
    $discard = workspace_action('ldrn_ws_studio_discard');
    verify($discard['success'] && isset($discard['data']['pages']), 'Discard did not restore page list');
    verify(ldrn_ws_pages() === ldrn_ws_public_pages(), 'Discard retained draft navigation');
    workspace_action('ldrn_ws_studio_save', array('doc'=>wp_json_encode(array('nodes'=>array()))));
    $live_at_restore = ldrn_ws_studio_live();
    $restore = ldrn_ws_restore_to_draft($session, ldrn_ws_studio_rev());
    verify($restore === true, 'Session restore failed');
    verify(ldrn_ws_studio_live() === $live_at_restore, 'Restore changed live without publication');
    verify(ldrn_ws_studio_draft() === ldrn_ws_session($session)['doc'], 'Restore lost saved design');
    verify(is_wp_error(ldrn_ws_restore_to_draft($session, 'old')), 'Restore ignored concurrent revision');
    // The following records belong to the current CMS, rather than the saved
    // website deliberately staged by the preceding restore-to-draft checks.
    delete_option('ldrn_ws_pending_site');
    $post = wp_insert_post(array('post_type'=>'post','post_status'=>'publish','post_title'=>'Workspace verification article','post_name'=>'workspace-verification-article','post_content'=>'<p>Verification article content.</p>'));
    verify(!is_wp_error($post), 'Native article could not be created');
    $request = new WP_REST_Request('GET','/ldrn/v1/blogs/workspace-verification-article');
    $response = rest_do_request($request);
    verify($response->get_status() === 200, 'Published article detail unavailable without rebuild');
    $native=ldrn_ws_native_page($post);
    verify($native && $native['recordId']===$post,'Native record missing from page controls');
    $native_update=workspace_action('ldrn_ws_studio_page_update',array('id'=>'record-'.$post,'label'=>'Article display name','visible'=>'0','menu'=>'1'));
    verify($native_update['success'],'Native page controls failed');
    verify(ldrn_ws_page('record-'.$post)['label']==='Article display name','Native page rename did not persist');
    verify(ldrn_ws_route_visible('/blogs/workspace-verification-article'),'Native draft visibility leaked to public site');
    verify(get_post($post)->post_content==='<p>Verification article content.</p>','Native page controls overwrote article content');
    workspace_action('ldrn_ws_studio_publish',array('name'=>'Native page control verification','doc'=>wp_json_encode(ldrn_ws_studio_draft())));
    verify(!ldrn_ws_route_visible('/blogs/workspace-verification-article'),'Native hidden page remains publicly visible after publication');
    verify(rest_do_request($request)->get_status()===404,'Hidden article remains public through REST');
    $_GET['ldrn_ws_studio']='1';
    verify(rest_do_request($request)->get_status()===200,'Archived article cannot be previewed in authenticated editor');
    unset($_GET['ldrn_ws_studio']);
    wp_update_post(array('ID'=>$post,'post_status'=>'draft'));
    verify(rest_do_request($request)->get_status() === 404, 'Unpublished article remains public');
    $setting = get_page_by_path('hounslow', OBJECT, 'ldrn_setting');
    verify((bool)$setting, 'Hounslow CMS record missing');
    update_post_meta($setting->ID, '_ldrn_setting_gallery', array());
    $settings = ldrn_get_settings();
    verify($settings[0]['gallery'] === array(), 'Empty CMS gallery revived baseline images');
    verify(count(ldrn_get_testimonials()) > 0, 'Approved parent stories missing without an optional location');
    $original_title=$setting->post_title;
    wp_update_post(array('ID'=>$setting->ID,'post_title'=>'Changed after saved version'));
    $later=wp_insert_post(array('post_type'=>'post','post_status'=>'publish','post_title'=>'Later article','post_name'=>'later-verification-article','post_content'=>'<p>Later content.</p>'));
    update_option('ldrn_site_settings',array_merge((array)get_option('ldrn_site_settings'),array('private_test_secret'=>'preserve-this')),false);
    verify(ldrn_ws_restore_to_draft($session,ldrn_ws_studio_rev())===true,'Complete session could not load into draft');
    verify(get_post($setting->ID)->post_title==='Changed after saved version','Loading a saved version prematurely changed live nursery content');
    $_GET['ldrn_ws_studio']='1';
    $preview=apply_filters('ldrn_cms_data',array());
    verify($preview['settings'][0]['name']===$saved['site']['data']['settings'][0]['name'],'Canvas preview used current nursery content instead of saved content');
    verify(!$preview['submissions']['enabled'],'Saved-version preview can send enquiries');
    unset($_GET['ldrn_ws_studio']);
    $complete_publish=workspace_action('ldrn_ws_studio_publish',array('doc'=>wp_json_encode(ldrn_ws_studio_draft()),'name'=>'Restore verification'));
    verify($complete_publish['success'],'Complete saved website publication failed');
    verify(get_post($setting->ID)->post_title===$original_title,'Saved nursery content was not restored at publication');
    verify(get_post($later)->post_status==='draft','Content absent from the saved website stayed public');
    verify(get_option('ldrn_site_settings')['private_test_secret']==='preserve-this','Design restore overwrote unrelated settings');
    verify(!get_option('ldrn_ws_pending_site'),'Published restore remained pending');
    $corrupt=ldrn_ws_session($session);$corrupt['site']['build']['files']['missing.js']=str_repeat('0',64);
    update_option('ldrn_ws_pending_site',$corrupt,false);
    $before_failed=ldrn_ws_studio_live();
    $failure=workspace_action('ldrn_ws_studio_publish',array('doc'=>wp_json_encode(array('nodes'=>array()))));
    verify(!$failure['success'],'Incomplete saved website was accepted');
    verify(ldrn_ws_studio_live()===$before_failed,'Failed publication changed the public design');
    delete_option('ldrn_ws_pending_site');
    $split=ldrn_ws_studio_clean_doc(array('nodes'=>array('w:home.hero>2.1~label1'=>array('text'=>'Independent label','s'=>array('d'=>array('color'=>'#213472','font-size'=>'18px'))))));
    verify(isset($split['nodes']['w:home.hero>2.1~label1']),'Independent label address was discarded');
    verify(!ldrn_ws_studio_valid_key('w:home.hero~label0') && !ldrn_ws_studio_valid_key('w:home.hero~label1~label1'),'Malformed label address was accepted');
    $_GET['ldrn_session']=$session;
    $live_directory=get_option('ldrn_ws_published_pages');
    $preview_backup=ldrn_ws_capture_session('Backup while viewing saved site');
    verify(ldrn_ws_session($preview_backup)['pages']===$live_directory,'Viewing a save contaminated the current-site backup');
    unset($_GET['ldrn_session']);
    wp_update_post(array('ID'=>$setting->ID,'post_title'=>'Immediate restore verification'));
    $count_before=count(ldrn_ws_sessions_rows());
    verify(ldrn_ws_restore_site($session,ldrn_ws_studio_rev())===true,'Reference-style immediate restore failed');
    verify(get_post($setting->ID)->post_title===$original_title,'Immediate restore did not change published CMS content');
    verify(ldrn_ws_studio_live()===ldrn_ws_studio_draft(),'Immediate restore left draft and published design out of sync');
    $new_rows=ldrn_ws_sessions_rows();
    verify(count($new_rows)>=$count_before+2 && $new_rows[0]['kind']==='Restored','Restore did not save recovery and restored versions');
    verify(is_wp_error(ldrn_ws_restore_site($session,'stale')),'Immediate restore accepted an outdated editor revision');
    wp_set_current_user(0);
    $forbidden = workspace_action('ldrn_ws_studio_save', array('doc'=>'{}'));
    verify(!$forbidden['success'], 'Anonymous editor mutation accepted');
} finally {
    $wpdb->query('ROLLBACK');
    wp_cache_flush();
}
echo $GLOBALS['checks'].' workspace checks passed; test mutations rolled back.'.PHP_EOL;
