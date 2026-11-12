<?php
/**
 * Plugin Name: Alexandra Montessori Workspace
 * Description: Alexandra Montessori visual site editor, nursery section library, saved sessions and activity history. Ported from the Little Rainbows (LDRN) workspace.
 * Version: 1.0.0
 * Requires PHP: 8.0
 */
defined('ABSPATH') || exit;
define('AM_WS_VERSION', '1.0.0');
define('AM_WS_DIR', plugin_dir_path(__FILE__));
define('AM_WS_URL', plugin_dir_url(__FILE__));
define('AM_WS_MENU', 'am_content');
require_once AM_WS_DIR . 'includes/pages.php';
require_once AM_WS_DIR . 'includes/access.php';
require_once AM_WS_DIR . 'includes/studio.php';
require_once AM_WS_DIR . 'includes/sessions.php';
require_once AM_WS_DIR . 'includes/site-snapshots.php';
require_once AM_WS_DIR . 'includes/templates.php';
require_once AM_WS_DIR . 'includes/visual-builder-sessions.php';

function am_ws_palette() {
    // The site's own Tailwind palette (alexandra-montessori/tailwind.config.js).
    return array('sage'=>'#a3bc9a', 'sage-50'=>'#f7faf5', 'sage-100'=>'#eff5eb', 'sage-200'=>'#dcead7', 'sage-600'=>'#667c61', 'sage-700'=>'#506457', 'sage-800'=>'#405246', 'sage-900'=>'#34443a', 'cream'=>'#f5f1ef', 'sand'=>'#e9f1e6', 'olive'=>'#8f9b84', 'ink'=>'#36463d', 'white'=>'#ffffff');
}
function am_ws_colour_swatches() { return array_flip(am_ws_palette()); }
function am_ws_site_font_faces() {
    $base = get_template_directory_uri() . '/dist/assets/fonts/';
    $out = '';
    foreach (array(400, 500, 600, 700, 800) as $weight) {
        $out .= '@font-face{font-family:Poppins;src:url("'.esc_url($base.'poppins-latin-'.$weight.'.woff2').'");font-weight:'.$weight.';font-display:swap}';
    }
    // The editor's own interface keeps the Little Rainbows editor face.
    $out .= '@font-face{font-family:"Nunito Sans";src:url("'.esc_url(AM_WS_URL.'assets/fonts/nunito-sans-latin-var.woff2').'");font-weight:200 1000;font-display:swap}';
    return $out;
}
function am_ws_images() {
    $root = get_template_directory() . '/dist/assets/';
    if (!is_dir($root)) return array();
    $out = array();
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if (!$file->isFile() || !preg_match('/\.(webp|png|jpe?g|svg)$/i', $file->getFilename())) continue;
        $path = str_replace('\\', '/', substr($file->getPathname(), strlen($root)));
        // Responsive variants (name-768-3f8765e4.webp), build-hashed root files and icons.
        if (!str_contains($path, '/') || preg_match('/(?:-\d{2,4}-[a-f0-9]{8}\.|-\d{2,4}w|favicon|icon-)/', $path)) continue;
        // A PNG kept beside its WebP is the same picture; list it once.
        if (preg_match('/\.png$/i', $path) && is_file($root.preg_replace('/\.png$/i', '.webp', $path))) continue;
        $out[] = array('url'=>get_template_directory_uri().'/dist/assets/'.$path, 'name'=>str_replace('-', ' ', pathinfo($path, PATHINFO_FILENAME)), 'group'=>ucfirst(explode('/', $path)[0]));
    }
    usort($out, function($a,$b) { return strcmp($a['group'].$a['name'],$b['group'].$b['name']); });
    return $out;
}
function am_ws_public_pages() {
    $state=function_exists('am_ws_snapshot_request') ? am_ws_snapshot_request() : null;
    if($state)return $state['pages'];
    return get_option('am_ws_published_pages', am_ws_default_pages());
}
function am_ws_published_custom_pages() {
    return array_values(array_filter(am_ws_public_pages(), function($p) { return !empty($p['visible']) && !am_ws_studio_builtin_route($p['route']) && am_ws_route_has_content($p['route']); }));
}
function am_ws_route_has_content($route, $doc = null) {
    $state=function_exists('am_ws_snapshot_request') ? am_ws_snapshot_request() : null;
    if($doc===null && $state)$doc=$state['doc'];
    $doc = $doc ?? am_ws_studio_live();
    foreach (($doc['blocks'] ?? array()) as $block) {
        $at = $block['at'] ?? '';
        if ($at === 'm:'.$route || str_starts_with($at, 'm:'.$route.'>')) return true;
    }
    return false;
}
add_filter('am_seo_static_routes', function($routes) {
    foreach (am_ws_public_pages() as $page) {
        $route = $page['route'];
        if (empty($page['visible'])) { unset($routes[$route]); continue; }
        if (!am_ws_studio_builtin_route($route) && am_ws_route_has_content($route)) {
            $routes[$route] = array('title'=>$page['label'].' | Alexandra Montessori', 'description'=>$page['lede'] ?: $page['label']);
        }
    }
    return $routes;
});
add_filter('am_seo_route_meta', function($meta, $path) {
    foreach (am_ws_public_pages() as $page) {
        if ($page['route'] === $path && empty($page['visible']) && !am_ws_studio_is_canvas_request()) {
            $meta = am_seo_meta_defaults($path);
        }
    }
    return $meta;
}, 10, 2);
add_action('save_post', function($id,$post) {
    if (wp_is_post_revision($id) || !in_array($post->post_type,array('post','am_event','am_nursery','am_testimonial','am_job'),true)) return;
    am_ws_audit('content_saved', $post->post_type.' #'.$id);
}, 100, 2);
add_action('update_option_'.AM_WS_PAGES_OPTION, function() { am_ws_audit('page_directory_changed'); });

// Feed the canvas before React mounts. The runtime observes subsequent renders.
add_action('wp_head', function() {
    $payload = am_ws_studio_payload();
    if (isset($_GET['am_session']) && current_user_can('manage_options')) {
        $session = am_ws_session(sanitize_key(wp_unslash($_GET['am_session'])));
        if ($session) { $payload['doc'] = $session['doc']; $payload['pages'] = array_values(array_filter($session['pages'], function($p) { return !am_ws_studio_builtin_route($p['route']); })); }
    }
    $build=am_ws_request_build();
    if($build)$payload['doc']=am_ws_snapshot_asset_values($payload['doc'],$build);
    echo '<script>window.__AM_WORKSPACE__='.wp_json_encode(array('studio'=>$payload), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';</script>';
    echo am_ws_studio_head_markup();
    if (am_ws_studio_is_canvas_request()) {
        echo '<style>html{scroll-behavior:auto!important}body{overflow-x:hidden}.reveal{opacity:1!important;transform:none!important}.am-workspace-collection{min-width:0}</style>';
    }
}, 99);
add_action('wp_enqueue_scripts', function() {
    $is_canvas = am_ws_studio_is_canvas_request();
    $pages = $is_canvas ? am_ws_pages() : am_ws_public_pages();
    $custom = $is_canvas ? am_ws_studio_custom_pages(false) : am_ws_published_custom_pages();
    if (isset($_GET['am_session']) && current_user_can('manage_options')) {
        $session = am_ws_session(sanitize_key(wp_unslash($_GET['am_session'])));
        if ($session) {
            $pages = $session['pages'];
            $custom = array_values(array_filter($pages, function($p) use ($session) { return !empty($p['visible']) && !am_ws_studio_builtin_route($p['route']) && am_ws_route_has_content($p['route'], $session['doc']); }));
        }
    }
    wp_add_inline_script('alexandra-app', 'window.amWorkspace='.wp_json_encode(array('pages'=>$pages, 'customPages'=>$custom), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';', 'before');
}, 30);
add_action('template_redirect', function() {
    $route = '/' . trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    foreach (am_ws_public_pages() as $p) {
        if ($p['route'] !== $route) continue;
        $canvas = am_ws_studio_is_canvas_request();
        // Built-in record routes keep the CMS readiness status from the SEO resolver.
        if (am_ws_studio_builtin_route($route) && !empty($p['visible'])) continue;
        if ((!empty($p['visible']) && am_ws_route_has_content($route)) || $canvas) {
            status_header(200); global $wp_query; $wp_query->is_404 = false;
        } else {
            status_header(404); global $wp_query; $wp_query->is_404 = true;
        }
    }
}, 1001);
register_activation_hook(__FILE__, function() {
    am_ws_seed_pages();
    if (!get_option(AM_WS_VB_MIGRATED_OPTION)) am_ws_migrate_vb_sessions();
});

/** Directory visibility applies to individual CMS pages and their archive cards. */
function am_ws_route_visible($route) {
    if (am_ws_studio_is_canvas_request()) return true;
    foreach (am_ws_public_pages() as $page) {
        if ($page['route'] === $route) return !empty($page['visible']);
    }
    return true;
}
add_filter('rest_post_dispatch', function($response) {
    if (am_ws_studio_is_canvas_request() && $response instanceof WP_REST_Response) {
        $response->header('Cache-Control','private, no-store');
    }
    return $response;
});

function am_ws_native_page($id) {
    $post = get_post($id);
    $types = array('am_event'=>array('/events/','am_event_is_frontend_ready'), 'post'=>array('/blogs/','am_blog_is_frontend_ready'), 'am_nursery'=>array('/nurseries/','am_nursery_is_frontend_ready'), 'am_job'=>array('/careers/vacancies/','am_job_is_frontend_ready'));
    if (!$post || 'publish' !== $post->post_status || !isset($types[$post->post_type])) return null;
    list($prefix,$ready) = $types[$post->post_type];
    if (function_exists($ready) && !$ready($id)) return null;
    return array('id'=>'record-'.$id, 'label'=>html_entity_decode($post->post_title, ENT_QUOTES, 'UTF-8'), 'route'=>$prefix.$post->post_name, 'menu'=>false, 'visible'=>true, 'custom'=>false, 'recordId'=>$id, 'editUrl'=>get_edit_post_link($id, 'raw'));
}

// Native CMS records retain the same directory controls as static pages.
add_filter('am_ws_editor_pages', function($pages) {
    foreach (array('am_event'=>'/events/', 'post'=>'/blogs/', 'am_nursery'=>'/nurseries/', 'am_job'=>'/careers/vacancies/') as $type=>$prefix) {
        foreach (get_posts(array('post_type'=>$type, 'post_status'=>'publish', 'posts_per_page'=>200)) as $post) {
            $native = am_ws_native_page($post->ID);
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
