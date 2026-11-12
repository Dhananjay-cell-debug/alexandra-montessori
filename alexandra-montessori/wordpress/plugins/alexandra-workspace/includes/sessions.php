<?php
defined('ABSPATH') || exit;

function am_ws_audit($action, $detail = '') {
    wp_insert_post(array('post_type'=>'am_ws_audit','post_status'=>'private','post_title'=>sanitize_text_field($action),'post_content'=>sanitize_text_field($detail),'post_author'=>get_current_user_id()));
}
add_action('init', function() {
    register_post_type('am_ws_audit', array('public'=>false,'show_ui'=>false,'supports'=>array('title','editor','author')));
    register_post_type('am_ws_session', array('public'=>false,'show_ui'=>false,'supports'=>array('title','editor','author')));
});
function am_ws_capture_session($name, $draft = false) {
    $id = wp_generate_password(12, false, false);
    $manifest = get_template_directory().'/dist/.vite/manifest.json';
    $state = array('id'=>$id, 'time'=>time(), 'user'=>wp_get_current_user()->display_name, 'name'=>$name ?: 'Published site', 'kind'=>$draft ? 'draft' : 'publication', 'doc'=>$draft ? am_ws_studio_draft() : am_ws_studio_live(), 'pages'=>$draft ? am_ws_pages() : get_option('am_ws_published_pages',am_ws_default_pages()), 'templates'=>am_ws_templates(), 'build'=>is_readable($manifest) ? hash_file('sha256', $manifest) : '');
    $pending=$draft ? get_option('am_ws_pending_site',null) : null;
    $state['site']=$pending['site'] ?? array('data'=>am_ws_snapshot_data(),'records'=>am_ws_snapshot_records(),'options'=>am_ws_snapshot_options());
    $build=$pending['site']['build'] ?? am_ws_store_build($state);
    if(is_wp_error($build) || !am_ws_build_valid($build))return false;
    $state['site']['build']=$build;
    $state['build']=$build['hash'];
    $previous=get_posts(array('post_type'=>'am_ws_session','post_status'=>'private','posts_per_page'=>1,'orderby'=>'ID','order'=>'DESC'));
    $before=$previous ? json_decode($previous[0]->post_content,true) : null;
    $changes=array();
    foreach(array('doc'=>'Design or text','pages'=>'Pages or navigation','templates'=>'Reusable sections','build'=>'Website build','site'=>'Nursery content or media') as $key=>$label)if(wp_json_encode($state[$key] ?? null)!==wp_json_encode($before[$key] ?? null))$changes[]=$label;
    $state['changes']=$changes ?: array('No changes since previous save');
    $state['detail']=am_ws_session_describe($state,$before);
    $post_id = wp_insert_post(array('post_type'=>'am_ws_session','post_status'=>'private','post_name'=>$id,'post_title'=>$state['name'],'post_content'=>wp_slash(wp_json_encode($state)),'post_author'=>get_current_user_id()));
    if (!$post_id || is_wp_error($post_id)) return false;
    am_ws_audit('session_saved', $id . ': ' . $state['name']);
    return $id;
}
function am_ws_session($id) {
    $posts = get_posts(array('post_type'=>'am_ws_session','post_status'=>'private','name'=>$id,'posts_per_page'=>1));
    if (!$posts) return null;
    $data = json_decode($posts[0]->post_content, true);
    return is_array($data) ? $data : null;
}
add_action('am_ws_studio_published', function($name) {
    update_option('am_ws_published_pages', am_ws_pages(), false);
    if(!am_ws_capture_session($name))throw new RuntimeException('The complete saved version could not be created. Publication was rolled back.');
    am_ws_audit('site_published');
});
add_action('admin_menu', function() {
    add_submenu_page('am_content', 'Saved sessions', 'Saved sessions', 'manage_options', 'am-sessions', 'am_ws_render_sessions');
    add_submenu_page('am_content', 'Activity history', 'Activity history', 'manage_options', 'am-activity', 'am_ws_render_activity');
});
function am_ws_sessions_icon($name) {
    $paths = array(
        'back'    => '<path d="M10 3.5L5.5 8l4.5 4.5"/>',
        'history' => '<path d="M5 6.5H10a3 3 0 010 6H7"/><path d="M7 3.5l-3 3 3 3"/>',
        'plus'    => '<path d="M8 3v10M3 8h10"/>',
        'refresh' => '<path d="M13 8a5 5 0 11-1.5-3.5"/><path d="M13 2.5v3h-3"/>',
        'cal'     => '<rect x="2" y="3" width="12" height="11" rx="1.5"/><path d="M2 6.5h12M5 1.5v3M11 1.5v3"/>',
        'search'  => '<circle cx="7" cy="7" r="4.5"/><path d="M10.5 10.5L14 14"/>',
    );
    return '<svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">' . ($paths[$name] ?? '') . '</svg>';
}

function am_ws_render_sessions() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to view this.', 'am-workspace'));
    }
    $js     = AM_WS_DIR . 'assets/sessions.js';
    $editor = admin_url('admin.php?page=am-studio');

    echo '<div class="wwss" id="wwss">';

    // The same top bar as the Site Editor, with this screen's pill lit.
    echo '<header class="wwss-top">';
    echo '<div class="wwss-top-l">';
    echo '<a class="wwss-icon-btn" href="' . esc_url(admin_url('admin.php?page=' . 'am_content')) . '" title="Back to WordPress">' . am_ws_sessions_icon('back') . '</a>';
    echo '<span class="wwss-brand">Alexandra Montessori</span><span class="wwss-divider"></span>';
    echo '<a class="wwss-pill" href="' . esc_url($editor) . '" title="Back to the Site Editor">Site Editor</a>';
    echo '<span class="wwss-pill is-on">' . am_ws_sessions_icon('history') . '<span>All saved sessions</span></span>';
    echo '</div>';
    echo '<div class="wwss-top-r">';
    echo '<button type="button" class="wwss-icon-btn" title="Refresh" data-refresh>' . am_ws_sessions_icon('refresh') . '</button>';
    echo '<button type="button" class="wwss-btn" data-save>' . am_ws_sessions_icon('plus') . '<span>Save current site</span></button>';
    echo '<a class="wwss-primary" href="' . esc_url($editor) . '">Back to editor</a>';
    echo '</div>';
    echo '</header>';

    echo '<main class="wwss-body"><div class="wwss-inner">';
    if (isset($_GET['restored'])) {
        echo '<div class="wwss-notice">The site has been restored. A backup of how it was a moment ago is saved in the list, in case you want it back.</div>';
    }
    echo '<p class="wwss-eyebrow">Version history</p>';
    echo '<h1 class="wwss-title">All saved sessions</h1>';
    echo '<p class="wwss-lede">Open any save as the real website, then restore it only when you are ready. Every Publish is saved here automatically.</p>';

    echo '<div class="wwss-filters">';
    echo '<label class="wwss-search-box">' . am_ws_sessions_icon('search') . '<input type="search" placeholder="Search title, page or changes" data-search></label>';
    echo '<label class="wwss-date-box" title="Nearest date">'
       . '<input type="text" placeholder="Nearest date · DD/MM/YYYY" inputmode="numeric" maxlength="10" data-date>'
       . '<button type="button" title="Pick a date" data-cal>' . am_ws_sessions_icon('cal') . '</button>'
       . '<input type="date" class="wwss-native-date" tabindex="-1" data-native></label>';
    echo '</div>';
    echo '<div class="wwss-meta"><span data-order>Newest changes first</span><span data-count></span></div>';
    echo '<div class="wwss-table" data-table></div>';
    echo '</div></main>';
    echo '</div>';

    echo '<script>window.amSessions=' . wp_json_encode(array(
        'rows'  => am_ws_sessions_rows(),
        'rev' => am_ws_studio_rev(),
        'ajax'  => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('am_ws_sessions'),
    )) . ';</script>';
    echo '<script src="' . esc_url(AM_WS_URL . 'assets/sessions.js?ver=' . filemtime($js)) . '"></script>';
}

function am_ws_render_activity() {
    if (!current_user_can('manage_options')) wp_die('Access denied');
    echo '<div class="wrap"><h1>Alexandra Montessori · Activity history</h1><table class="widefat striped"><thead><tr><th>Action</th><th>When</th><th>Author</th><th>Details</th></tr></thead><tbody>';
    foreach (get_posts(array('post_type'=>'am_ws_audit','post_status'=>'private','posts_per_page'=>200)) as $post) {
        echo '<tr><td>'.esc_html(str_replace('_',' ',$post->post_title)).'</td><td>'.esc_html($post->post_date).'</td><td>'.esc_html(get_the_author_meta('display_name',$post->post_author)).'</td><td>'.esc_html($post->post_content).'</td></tr>';
    }
    echo '</tbody></table></div>';
}
function am_ws_restore_to_draft($id, $rev) {
    if (!current_user_can('manage_options')) return new WP_Error('forbidden', 'Access denied');
    if (!hash_equals(am_ws_studio_rev(), $rev)) return new WP_Error('conflict', 'The draft changed since this page opened. Refresh Saved sessions before restoring.');
    $state = am_ws_session($id);
    if (!$state) return new WP_Error('missing', 'Session not found');
    if(!empty($state['site']) && !am_ws_build_valid($state['site']['build']))return new WP_Error('archive_missing','The saved website archive is incomplete. Nothing was restored.');
    if (!am_ws_capture_session('Before restoring '.$state['name'], true)) return new WP_Error('recovery', 'Recovery copy could not be saved. Nothing was restored.');
    $doc = am_ws_studio_clean_doc($state['doc']);
    update_option(AM_WS_STUDIO_DRAFT,$doc,false);
    am_ws_commit_pages($state['pages']);
    if (isset($state['templates'])) update_option('am_ws_templates', $state['templates'], false);
    if(!empty($state['site'])) {
        update_option('am_ws_pending_site',$state,false);
    } else delete_option('am_ws_pending_site');
    am_ws_audit('session_restored',$state['id']);
    return true;
}
add_action('admin_post_am_ws_restore_session', function() {
    if (!current_user_can('manage_options')) wp_die('Access denied');
    check_admin_referer('am_ws_restore');
    $result = am_ws_restore_to_draft(sanitize_key(wp_unslash($_POST['session'] ?? '')), sanitize_text_field(wp_unslash($_POST['rev'] ?? '')));
    if (is_wp_error($result)) wp_die(esc_html($result->get_error_message()));
    wp_safe_redirect(admin_url('admin.php?page=am-studio')); exit;
});
add_action('admin_post_am_ws_save_session', function() {
    if (!current_user_can('manage_options')) wp_die('Access denied');
    check_admin_referer('am_ws_save_session');
    $name = mb_substr(sanitize_text_field(wp_unslash($_POST['name'] ?? 'Saved draft')), 0, 80);
    if (!am_ws_capture_session($name, ($_POST['scope'] ?? 'draft') !== 'live')) wp_die('The session could not be saved. Your draft is unchanged.');
    wp_safe_redirect(admin_url('admin.php?page=am-sessions')); exit;
});

require_once __DIR__ . '/session-workspace.php';
