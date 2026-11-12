<?php
/**
 * Plugin Name: Little Rainbows Workspace
 * Description: LDRN visual site editor, nursery section library, saved sessions and activity history.
 * Version: 1.0.0
 * Requires PHP: 8.0
 */
defined('ABSPATH') || exit;
define('LDRN_WS_VERSION', '1.0.0');
define('LDRN_WS_DIR', plugin_dir_path(__FILE__));
define('LDRN_WS_URL', plugin_dir_url(__FILE__));
define('LDRN_WS_MENU', 'ldrn_content');
require_once LDRN_WS_DIR . 'includes/pages.php';
require_once LDRN_WS_DIR . 'includes/access.php';
require_once LDRN_WS_DIR . 'includes/studio.php';
require_once LDRN_WS_DIR . 'includes/sessions.php';
require_once LDRN_WS_DIR . 'includes/site-snapshots.php';
require_once LDRN_WS_DIR . 'includes/templates.php';

function ldrn_ws_palette() {
    return array('navy'=>'#213472', 'sky'=>'#bbe8f6', 'sage'=>'#a3bc9a', 'forest'=>'#3a5446', 'white'=>'#ffffff', 'ink'=>'#213472', 'orange'=>'#f97338', 'red'=>'#e43944', 'yellow'=>'#feda30', 'green'=>'#279459', 'blue'=>'#2c6ed5', 'violet'=>'#6422af');
}
function ldrn_ws_colour_swatches() { return array_flip(ldrn_ws_palette()); }
function ldrn_ws_site_font_faces() {
    $base = get_template_directory_uri() . '/dist/fonts/';
    return '@font-face{font-family:Fraunces;src:url("'.esc_url($base.'fraunces-latin-var.woff2').'");font-weight:100 900;font-display:swap}'
        .'@font-face{font-family:"Nunito Sans";src:url("'.esc_url($base.'nunito-sans-latin-var.woff2').'");font-weight:200 1000;font-display:swap}';
}
function ldrn_ws_images() {
    $root = get_template_directory() . '/dist/assets/';
    if (!is_dir($root)) return array();
    $out = array();
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if (!$file->isFile() || !preg_match('/\.(webp|png|jpe?g|svg)$/i', $file->getFilename())) continue;
        $path = str_replace('\\', '/', substr($file->getPathname(), strlen($root)));
        if (preg_match('/(?:-\d{2,4}w|favicon|icon-)/', $path)) continue;
        $out[] = array('url'=>get_template_directory_uri().'/dist/assets/'.$path, 'name'=>str_replace('-', ' ', pathinfo($path, PATHINFO_FILENAME)), 'group'=>ucfirst(explode('/', $path)[0]));
    }
    usort($out, function($a,$b) { return strcmp($a['group'].$a['name'],$b['group'].$b['name']); });
    return $out;
}
function ldrn_ws_public_pages() {
    $state=function_exists('ldrn_ws_snapshot_request') ? ldrn_ws_snapshot_request() : null;
    if($state)return $state['pages'];
    return get_option('ldrn_ws_published_pages', ldrn_ws_default_pages());
}
function ldrn_ws_published_custom_pages() {
    return array_values(array_filter(ldrn_ws_public_pages(), function($p) { return !empty($p['visible']) && !ldrn_ws_studio_builtin_route($p['route']) && ldrn_ws_route_has_content($p['route']); }));
}
function ldrn_ws_route_has_content($route, $doc = null) {
    $state=function_exists('ldrn_ws_snapshot_request') ? ldrn_ws_snapshot_request() : null;
    if($doc===null && $state)$doc=$state['doc'];
    $doc = $doc ?? ldrn_ws_studio_live();
    foreach (($doc['blocks'] ?? array()) as $block) {
        $at = $block['at'] ?? '';
        if ($at === 'm:'.$route || str_starts_with($at, 'm:'.$route.'>')) return true;
    }
    return false;
}
add_filter('ldrn_seo_static_routes', function($routes) {
    foreach (ldrn_ws_public_pages() as $page) {
        $route = $page['route'];
        if (empty($page['visible'])) { unset($routes[$route]); continue; }
        if (!ldrn_ws_studio_builtin_route($route) && ldrn_ws_route_has_content($route)) {
            $routes[$route] = array('title'=>$page['label'].' | Little Rainbows Day Nursery', 'description'=>$page['lede'] ?: $page['label']);
        }
    }
    return $routes;
});
add_filter('ldrn_seo_route_meta', function($meta, $path) {
    foreach (ldrn_ws_public_pages() as $page) {
        if ($page['route'] === $path && empty($page['visible']) && !ldrn_ws_studio_is_canvas_request()) {
            $meta = ldrn_seo_meta_defaults($path);
        }
    }
    return $meta;
}, 10, 2);
add_action('save_post', function($id,$post) {
    if (wp_is_post_revision($id) || !in_array($post->post_type,array('post','ldrn_event','ldrn_setting','ldrn_testimonial','ldrn_job'),true)) return;
    ldrn_ws_audit('content_saved', $post->post_type.' #'.$id);
}, 100, 2);
add_action('update_option_'.LDRN_WS_PAGES_OPTION, function() { ldrn_ws_audit('page_directory_changed'); });

// Feed the canvas before React mounts. The runtime observes subsequent renders.
add_action('wp_head', function() {
    $payload = ldrn_ws_studio_payload();
    if (isset($_GET['ldrn_session']) && current_user_can('manage_options')) {
        $session = ldrn_ws_session(sanitize_key(wp_unslash($_GET['ldrn_session'])));
        if ($session) { $payload['doc'] = $session['doc']; $payload['pages'] = array_values(array_filter($session['pages'], function($p) { return !ldrn_ws_studio_builtin_route($p['route']); })); }
    }
    $build=ldrn_ws_request_build();
    if($build)$payload['doc']=ldrn_ws_snapshot_asset_values($payload['doc'],$build);
    echo '<script>window.__LDRN_WORKSPACE__='.wp_json_encode(array('studio'=>$payload), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';</script>';
    echo ldrn_ws_studio_head_markup();
    if (ldrn_ws_studio_is_canvas_request()) {
        echo '<style>html{scroll-behavior:auto!important}body{overflow-x:hidden}.reveal{opacity:1!important;transform:none!important}.ldrn-workspace-collection{min-width:0}</style>';
    }
}, 99);
add_action('wp_enqueue_scripts', function() {
    $is_canvas = ldrn_ws_studio_is_canvas_request();
    $pages = $is_canvas ? ldrn_ws_pages() : ldrn_ws_public_pages();
    $custom = $is_canvas ? ldrn_ws_studio_custom_pages(false) : ldrn_ws_published_custom_pages();
    if (isset($_GET['ldrn_session']) && current_user_can('manage_options')) {
        $session = ldrn_ws_session(sanitize_key(wp_unslash($_GET['ldrn_session'])));
        if ($session) {
            $pages = $session['pages'];
            $custom = array_values(array_filter($pages, function($p) use ($session) { return !empty($p['visible']) && !ldrn_ws_studio_builtin_route($p['route']) && ldrn_ws_route_has_content($p['route'], $session['doc']); }));
        }
    }
    wp_add_inline_script('ldrn-app', 'window.ldrnWorkspace='.wp_json_encode(array('pages'=>$pages, 'customPages'=>$custom), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';', 'before');
}, 30);
add_action('template_redirect', function() {
    $route = '/' . trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    foreach (ldrn_ws_public_pages() as $p) {
        if ($p['route'] !== $route) continue;
        $canvas = ldrn_ws_studio_is_canvas_request();
        // Built-in record routes keep the CMS readiness status from the SEO resolver.
        if (ldrn_ws_studio_builtin_route($route) && !empty($p['visible'])) continue;
        if ((!empty($p['visible']) && ldrn_ws_route_has_content($route)) || $canvas) {
            status_header(200); global $wp_query; $wp_query->is_404 = false;
        } else {
            status_header(404); global $wp_query; $wp_query->is_404 = true;
        }
    }
}, 1001);
register_activation_hook(__FILE__, 'ldrn_ws_seed_pages');

/** Directory visibility applies to individual CMS pages and their archive cards. */
function ldrn_ws_route_visible($route) {
    if (ldrn_ws_studio_is_canvas_request()) return true;
    foreach (ldrn_ws_public_pages() as $page) {
        if ($page['route'] === $route) return !empty($page['visible']);
    }
    return true;
}
add_filter('rest_post_dispatch', function($response) {
    if (ldrn_ws_studio_is_canvas_request() && $response instanceof WP_REST_Response) {
        $response->header('Cache-Control','private, no-store');
    }
    return $response;
});

function ldrn_ws_native_page($id) {
    $post = get_post($id);
    $types = array('ldrn_event'=>array('/events/','ldrn_event_is_frontend_ready'), 'post'=>array('/blogs/','ldrn_blog_is_frontend_ready'), 'ldrn_setting'=>array('/our-nursery/','ldrn_setting_is_frontend_ready'));
    if (!$post || 'publish' !== $post->post_status || !isset($types[$post->post_type])) return null;
    list($prefix,$ready) = $types[$post->post_type];
    if (function_exists($ready) && !$ready($id)) return null;
    return array('id'=>'record-'.$id, 'label'=>$post->post_title, 'route'=>$prefix.$post->post_name, 'menu'=>false, 'visible'=>true, 'custom'=>false, 'recordId'=>$id, 'editUrl'=>get_edit_post_link($id, 'raw'));
}

// Native CMS records retain the same directory controls as static pages.
add_filter('ldrn_ws_editor_pages', function($pages) {
    foreach (array('ldrn_event'=>'/events/', 'post'=>'/blogs/', 'ldrn_setting'=>'/our-nursery/') as $type=>$prefix) {
        foreach (get_posts(array('post_type'=>$type, 'post_status'=>'publish', 'posts_per_page'=>200)) as $post) {
            $native = ldrn_ws_native_page($post->ID);
            if (!$native) continue;
            $matched = false;
            foreach ($pages as &$page) {
                if ($page['route'] !== $native['route']) continue;
                $page['recordId']=$native['recordId']; $page['editUrl']=$native['editUrl']; $matched=true;
            }
            unset($page);
            if (!$matched) $pages[] = $native;
        }
    }
    return $pages;
});
