<?php
defined('ABSPATH') || exit;

/** The reference session screen, backed by LDRN's own complete snapshots. */
add_filter('admin_body_class', function($classes) {
    return ($_GET['page'] ?? '') === 'ldrn-sessions' ? $classes.' ldrn-sessions-screen' : $classes;
});
add_action('admin_enqueue_scripts', function() {
    if(($_GET['page'] ?? '') !== 'ldrn-sessions')return;
    wp_enqueue_style('ldrn-sessions-fonts','https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap',array(),null);
    wp_enqueue_style('ldrn-sessions',LDRN_WS_URL.'assets/sessions.css',array('ldrn-sessions-fonts'),filemtime(LDRN_WS_DIR.'assets/sessions.css'));
});

function ldrn_ws_session_describe($state,$before) {
    if(!$before)return array('pages'=>array(),'summary'=>'The site as it stood when saved sessions began.');
    $parts=array();$routes=array();$changed=0;
    foreach(array('nodes','blocks') as $group) {
        $current=$state['doc'][$group] ?? array();$old=$before['doc'][$group] ?? array();
        foreach(array_unique(array_merge(array_keys($current),array_keys($old))) as $key) {
            if(wp_json_encode($current[$key] ?? null)===wp_json_encode($old[$key] ?? null))continue;
            $changed++;
            if(preg_match('#^[me]:(/[^>~]*)#',$key,$m))$routes[$m[1]]=true;
            if($group==='blocks') {
                $at=$current[$key]['at'] ?? $old[$key]['at'] ?? '';
                if(preg_match('#^[me]:(/[^>~]*)#',$at,$m))$routes[$m[1]]=true;
            }
        }
    }
    if($changed)$parts[]='Restyled or rewrote '.$changed.' element'.($changed===1 ? '' : 's');
    foreach(array('pages'=>'Changed pages or navigation','templates'=>'Changed reusable sections') as $key=>$label)
        if(wp_json_encode($state[$key] ?? null)!==wp_json_encode($before[$key] ?? null))$parts[]=$label;
    if(wp_json_encode($state['site']['records'] ?? null)!==wp_json_encode($before['site']['records'] ?? null))$parts[]='Changed nursery content';
    if(wp_json_encode($state['site']['options'] ?? null)!==wp_json_encode($before['site']['options'] ?? null))$parts[]='Changed website settings';
    if(($state['build'] ?? '')!==($before['build'] ?? ''))$parts[]='New website build or media';
    $pages=array();
    foreach(array_keys($routes) as $route) {
        $label=$route;
        foreach($state['pages'] as $page)if($page['route']===$route){$label=$page['label'].' · '.$route;break;}
        $pages[]=$label;
    }
    return array('pages'=>$pages,'summary'=>$parts ? implode('. ',$parts).'.' : 'No changes since the previous save.');
}
function ldrn_ws_session_post($id) {
    $posts=get_posts(array('post_type'=>'ldrn_ws_session','post_status'=>'private','name'=>sanitize_title($id),'numberposts'=>1));
    return $posts ? $posts[0] : null;
}
function ldrn_ws_session_kind($kind) {
    return array('publication'=>'Published','draft'=>'Draft save','save'=>'Named save','backup'=>'Automatic backup','restore'=>'Restored','build'=>'New site build','baseline'=>'Starting point')[$kind] ?? 'Saved session';
}
function ldrn_ws_session_mark($id,$kind,$summary='') {
    $post=ldrn_ws_session_post($id);
    if(!$post)return false;
    $state=json_decode($post->post_content,true);$state['kind']=$kind;
    if($summary!=='')$state['detail']['summary']=$summary;
    return !is_wp_error(wp_update_post(array('ID'=>$post->ID,'post_content'=>wp_slash(wp_json_encode($state))),true));
}
function ldrn_ws_sessions_rows() {
    $rows=array();
    foreach(get_posts(array('post_type'=>'ldrn_ws_session','post_status'=>'private','numberposts'=>-1,'orderby'=>'ID','order'=>'DESC')) as $post) {
        $state=json_decode($post->post_content,true);if(!$state)continue;
        $user=get_userdata($post->post_author);
        $rows[]=array('id'=>$state['id'],'title'=>$post->post_title,'time'=>$state['time'],'when'=>wp_date('j M Y, g:i a',$state['time']),
            'user'=>$state['user'],'role'=>$user ? ucfirst($user->roles[0] ?? '') : '',
            'kind'=>empty($state['site']) ? 'Legacy design save' : ldrn_ws_session_kind($state['kind']),
            'pages'=>$state['detail']['pages'] ?? array(),
            'summary'=>$state['detail']['summary'] ?? implode(', ',$state['changes'] ?? array('Design save; uses current website content and build.')),
            'open'=>add_query_arg('ldrn_session',$state['id'],home_url('/')),'latest'=>!$rows);
    }
    return $rows;
}
function ldrn_ws_sessions_guard($revision=false) {
    if(!current_user_can('manage_options'))wp_send_json_error(array('message'=>'You are not allowed to manage saved sessions.'),403);
    if(!check_ajax_referer('ldrn_ws_sessions','nonce',false))wp_send_json_error(array('message'=>'This page expired. Refresh and try again.'),403);
    if($revision && !hash_equals(ldrn_ws_studio_rev(),sanitize_text_field(wp_unslash($_POST['rev'] ?? ''))))
        wp_send_json_error(array('message'=>'The editor changed since this page opened. Refresh before restoring.','conflict'=>true),409);
}
add_action('wp_ajax_ldrn_ws_session_save',function() {
    ldrn_ws_sessions_guard();
    $name=mb_substr(sanitize_text_field(wp_unslash($_POST['name'] ?? '')),0,120) ?: 'Saved site';
    $id=ldrn_ws_capture_session($name,false);
    if(!$id)wp_send_json_error(array('message'=>'The complete website could not be saved. Nothing was changed.'),500);
    ldrn_ws_session_mark($id,'save');
    wp_send_json_success(array('id'=>$id));
});
add_action('wp_ajax_ldrn_ws_session_delete',function() {
    ldrn_ws_sessions_guard();$post=ldrn_ws_session_post(wp_unslash($_POST['id'] ?? ''));
    if(!$post)wp_send_json_error(array('message'=>'Saved session not found.'),404);
    if(!wp_delete_post($post->ID,true))wp_send_json_error(array('message'=>'The session could not be deleted.'),500);
    // Content-addressed files may still serve the live website or another save.
    ldrn_ws_audit('session_deleted',$post->post_name);
    wp_send_json_success();
});
add_action('wp_ajax_ldrn_ws_session_rename',function() {
    ldrn_ws_sessions_guard();$post=ldrn_ws_session_post(wp_unslash($_POST['id'] ?? ''));
    $name=mb_substr(sanitize_text_field(wp_unslash($_POST['name'] ?? '')),0,120);
    if(!$post || $name==='')wp_send_json_error(array('message'=>'A saved session and a name are required.'),400);
    $state=json_decode($post->post_content,true);$state['name']=$name;
    $result=wp_update_post(array('ID'=>$post->ID,'post_title'=>$name,'post_content'=>wp_slash(wp_json_encode($state))),true);
    if(is_wp_error($result))wp_send_json_error(array('message'=>$result->get_error_message()),500);
    ldrn_ws_audit('session_renamed',$post->post_name);
    wp_send_json_success();
});

/** Restore the saved site immediately, just as INU does, with rollback and recovery. */
function ldrn_ws_restore_site($id,$rev) {
    global $wpdb;
    if(!current_user_can('manage_options'))return new WP_Error('forbidden','Access denied');
    if(!hash_equals(ldrn_ws_studio_rev(),$rev))return new WP_Error('conflict','The editor changed. Refresh before restoring.');
    $state=ldrn_ws_session($id);
    if(!$state)return new WP_Error('missing','Saved session not found.');
    if(!empty($state['site']) && !ldrn_ws_build_valid($state['site']['build']))return new WP_Error('archive_missing','The saved website archive is incomplete. Nothing was restored.');
    foreach(array($wpdb->posts,$wpdb->postmeta,$wpdb->options,$wpdb->term_relationships) as $table) {
        $engine=$wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$table));
        if(strtolower((string)$engine)!=='innodb')return new WP_Error('transaction','Transactional WordPress tables are required to restore safely.');
    }
    $backup=ldrn_ws_capture_session('Automatic backup before “'.$state['name'].'”');
    if(!$backup)return new WP_Error('recovery','The recovery copy could not be saved. Nothing was restored.');
    ldrn_ws_session_mark($backup,'backup','The site exactly as it was just before “'.$state['name'].'” was restored.');
    // Preserve an unpublished draft as well: whole-site recovery must not lose it.
    if(ldrn_ws_studio_status()['unpublished'] && !ldrn_ws_capture_session('Unpublished draft before “'.$state['name'].'”',true))
        return new WP_Error('recovery','The unpublished draft could not be preserved. Nothing was restored.');
    $outer=!empty($GLOBALS['ldrn_ws_external_transaction']);
    $wpdb->query($outer ? 'SAVEPOINT ldrn_session_restore' : 'START TRANSACTION');
    $GLOBALS['ldrn_ws_external_transaction']=true;
    $GLOBALS['ldrn_ws_restoring_session']=true;
    try {
        ldrn_ws_commit_pages($state['pages']);
        if(isset($state['templates']))update_option('ldrn_ws_templates',$state['templates'],false);
        if(!empty($state['site']))update_option('ldrn_ws_pending_site',$state,false);
        else delete_option('ldrn_ws_pending_site');
        $result=ldrn_ws_publish_document(ldrn_ws_studio_clean_doc($state['doc']),'Restored — '.$state['name']);
        if(is_wp_error($result))throw new RuntimeException($result->get_error_message());
        $latest=get_posts(array('post_type'=>'ldrn_ws_session','post_status'=>'private','numberposts'=>1,'orderby'=>'ID','order'=>'DESC'));
        $restored=json_decode($latest[0]->post_content,true);
        ldrn_ws_session_mark($restored['id'],'restore','Restored the whole site from the saved session “'.$state['name'].'”.');
        ldrn_ws_audit('session_restored',$state['id']);
        $wpdb->query($outer ? 'RELEASE SAVEPOINT ldrn_session_restore' : 'COMMIT');
        return true;
    } catch(Throwable $error) {
        $wpdb->query($outer ? 'ROLLBACK TO SAVEPOINT ldrn_session_restore' : 'ROLLBACK');
        wp_cache_flush();
        return new WP_Error('restore_failed',$error->getMessage());
    } finally {
        $GLOBALS['ldrn_ws_external_transaction']=$outer;
        unset($GLOBALS['ldrn_ws_restoring_session']);
    }
}
add_action('wp_ajax_ldrn_ws_session_restore',function() {
    ldrn_ws_sessions_guard(true);
    $result=ldrn_ws_restore_site(sanitize_key(wp_unslash($_POST['id'] ?? '')),sanitize_text_field(wp_unslash($_POST['rev'] ?? '')));
    if(is_wp_error($result))wp_send_json_error(array('message'=>$result->get_error_message()),409);
    wp_send_json_success(array('redirect'=>admin_url('admin.php?page=ldrn-sessions&restored=1')));
});
