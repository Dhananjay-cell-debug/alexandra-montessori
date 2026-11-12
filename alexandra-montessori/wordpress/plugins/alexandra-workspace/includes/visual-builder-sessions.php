<?php
/**
 * Alexandra only: the retired Visual Builder's saved layout and its history.
 *
 * The Visual Builder editor is gone, but the layout it saved (Home in the
 * `am_vb_home_design` option, every other page in its `am_visual_page`
 * record) is still what visitors see. So:
 *
 *   - every saved session carries that layout (site-snapshots.php adds it to
 *     the snapshot), and restoring a session brings it back;
 *   - the Visual Builder's own named saves are moved into All saved sessions
 *     once, keeping their name, author, time and summary. Opening one shows
 *     the page with that saved layout, and "Restore this version" puts it
 *     back through the same restore flow as every other session.
 */

defined('ABSPATH') || exit;

const AM_WS_VB_MIGRATED_OPTION = 'am_ws_vb_sessions_migrated';

/** The saved layout of one Visual Builder page, inside a saved session. */
function am_ws_vb_design_from_state($state, $slug) {
    if (!is_array($state)) return null;
    if (!empty($state['vb']) && ($state['vb']['slug'] ?? '') === $slug) return $state['vb']['snapshot'];
    if (empty($state['site'])) return null;
    if ('home-poc' === $slug) {
        $home = $state['site']['options']['am_vb_home_design'] ?? null;
        return is_array($home) && $home ? $home : null;
    }
    foreach (($state['site']['records'] ?? array()) as $record) {
        if (($record['post_type'] ?? '') === 'am_visual_page' && ($record['post_name'] ?? '') === $slug) {
            $design = $record['meta']['_am_vb_exact_route_design'][0] ?? null;
            return is_array($design) ? $design : null;
        }
    }
    return null;
}

/** The session the current request is showing, if any. */
function am_ws_vb_request_state() {
    if (isset($_GET['am_session']) && current_user_can('manage_options')) {
        return am_ws_session(sanitize_key(wp_unslash($_GET['am_session'])));
    }
    return am_ws_snapshot_request();
}

// The Visual Builder's renderer asks here before using the current layout.
add_filter('am_vb_frontend_snapshot', function ($design, $slug) {
    $state = am_ws_vb_request_state();
    $saved = $state ? am_ws_vb_design_from_state($state, $slug) : null;
    return is_array($saved) ? $saved : $design;
}, 10, 2);

/** Put a session's Visual Builder layout back. Throws, so a restore rolls back. */
function am_ws_vb_apply($vb) {
    if (empty($vb['slug']) || !is_array($vb['snapshot'] ?? null)) return;
    if ('home' === ($vb['kind'] ?? '')) {
        if (!class_exists('AM_VB_Home_Design')) throw new RuntimeException('The saved layout renderer is missing. Nothing was restored.');
        AM_VB_Home_Design::save($vb['snapshot']);
        return;
    }
    if (!class_exists('AM_VB_Site_Design')) throw new RuntimeException('The saved layout renderer is missing. Nothing was restored.');
    $result = AM_VB_Site_Design::save($vb['slug'], $vb['snapshot']);
    if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
}

/**
 * Move the Visual Builder's named saves into All saved sessions. Safe to run
 * again: a save already moved is skipped, and the originals are left as they
 * were (nothing is deleted).
 */
function am_ws_migrate_vb_sessions() {
    $posts = get_posts(array('post_type'=>'am_vb_saved_session', 'post_status'=>'any', 'numberposts'=>-1, 'orderby'=>'date', 'order'=>'ASC'));
    $moved = 0;
    foreach ($posts as $post) {
        $id = 'vbsession' . $post->ID;
        if (am_ws_session($id)) continue;
        $snapshot = get_post_meta($post->ID, '_am_vb_session_snapshot', true);
        if (!is_array($snapshot)) continue;
        $slug  = (string) get_post_meta($post->ID, '_am_vb_session_slug', true);
        $kind  = 'home' === get_post_meta($post->ID, '_am_vb_session_kind', true) ? 'home' : 'exact';
        $route = (string) get_post_meta($post->ID, '_am_vb_session_route', true) ?: '/';
        $title = (string) get_post_meta($post->ID, '_am_vb_session_page_title', true) ?: $route;
        $user  = get_userdata($post->post_author);
        $state = array(
            'id'        => $id,
            'time'      => (int) get_post_time('U', true, $post),
            'user'      => $user ? $user->display_name : '',
            'name'      => $post->post_title,
            'kind'      => 'vb',
            // The site as it was then: no Site Editor changes yet, the original page list.
            'doc'       => am_ws_studio_empty(),
            'pages'     => am_ws_default_pages(),
            'templates' => array(),
            'build'     => '',
            'changes'   => array('Visual Builder layout'),
            'detail'    => array('pages'=>array($title . ' · ' . $route), 'summary'=>(string) get_post_meta($post->ID, '_am_vb_session_summary', true)),
            'vb'        => array('slug'=>$slug, 'kind'=>$kind, 'route'=>$route, 'snapshot'=>$snapshot, 'source'=>$post->ID),
        );
        $new = wp_insert_post(array('post_type'=>'am_ws_session', 'post_status'=>'private', 'post_name'=>$id, 'post_title'=>$post->post_title,
            'post_content'=>wp_slash(wp_json_encode($state)), 'post_author'=>$post->post_author,
            'post_date'=>$post->post_date, 'post_date_gmt'=>$post->post_date_gmt), true);
        if (is_wp_error($new)) return $new;
        $moved++;
    }
    update_option(AM_WS_VB_MIGRATED_OPTION, array('time'=>time(), 'moved'=>$moved, 'total'=>count($posts)), false);
    am_ws_audit('vb_sessions_migrated', $moved . ' of ' . count($posts));
    return $moved;
}
