<?php
/**
 * The SEO screen (wp-admin > SEO), in seven tabs:
 *
 *   Overview     the few numbers that matter, what to fix first, launch readiness
 *   Pages        each page as Google and a shared link show it; keyphrase and
 *                readability analysis on the page's real text
 *   Audit        the crawl audit: errors, warnings, notices, per page; PageSpeed
 *   Performance  Search Console, visitors and enquiries by source, real-user
 *                Core Web Vitals, crawler visits
 *   Business     the facts structured data publishes; listings off the site
 *   Redirects    redirects, and every address that answered 404
 *   Settings     connections, crawler policy, robots.txt, llms.txt, measurement
 *
 * It sits in the menu directly below the Site Editor
 * (admin.php?page=ldrn-seo). Administrators edit; anyone else who can open
 * it (none today) would see it read-only.
 */

defined('ABSPATH') || exit;

require_once LDRNSEO_DIR . 'includes/ui.php';
foreach (array('overview', 'pages', 'audit', 'speed', 'performance', 'business', 'settings') as $screen) {
    require_once LDRNSEO_DIR . 'includes/screens/' . $screen . '.php';
}

function ldrnseo_tabs() {
    return array('overview' => 'Overview', 'pages' => 'Pages', 'audit' => 'Audit', 'speed' => 'Speed', 'performance' => 'Traffic',
                 'business' => 'Business', 'settings' => 'Settings');
}

function ldrnseo_tab() {
    $t = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'overview';
    // Redirects and the 404 log live in Settings now; old links still land there.
    $t = 'redirects' === $t ? 'settings' : $t;
    return isset(ldrnseo_tabs()[$t]) ? $t : 'overview';
}

function ldrnseo_url($tab = 'overview', array $args = array()) {
    $frag = isset($args['#']) ? '#' . $args['#'] : '';
    unset($args['#']);
    return add_query_arg(array_merge(array('page' => 'ldrn-seo', 'tab' => $tab), $args), admin_url('admin.php')) . $frag;
}

/** Who may change what the screen saves. */
function ldrnseo_can_edit() {
    return current_user_can('manage_options');
}

/* -------------------------------------------------------------------------
 * The menu entry: directly below the Site Editor (position 2.9)
 * ---------------------------------------------------------------------- */

add_action('admin_menu', function () {
    add_menu_page('SEO', 'SEO', 'manage_options', 'ldrn-seo', 'ldrnseo_render', 'dashicons-search', '2.95');
}, 10);

add_filter('admin_body_class', function ($classes) {
    return isset($_GET['page']) && 'ldrn-seo' === $_GET['page'] ? $classes . ' ldrnseo-screen' : $classes;
});

add_action('admin_enqueue_scripts', function () {
    if ('ldrn-seo' !== (isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '')) {
        return;
    }
    wp_enqueue_style('ldrnseo', LDRNSEO_URL . 'assets/seo.css', array(), filemtime(LDRNSEO_DIR . 'assets/seo.css'));
    // The site's own two faces, self-hosted by the theme build.
    $fonts = get_template_directory_uri() . '/dist/fonts/';
    wp_add_inline_style('ldrnseo', '@font-face{font-family:Fraunces;src:url("' . esc_url($fonts . 'fraunces-latin-var.woff2') . '") format("woff2");font-weight:100 900;font-display:swap}'
        . '@font-face{font-family:"Nunito Sans";src:url("' . esc_url($fonts . 'nunito-sans-latin-var.woff2') . '") format("woff2");font-weight:200 1000;font-display:swap}');
    wp_enqueue_script('ldrnseo', LDRNSEO_URL . 'assets/seo.js', array(), filemtime(LDRNSEO_DIR . 'assets/seo.js'), true);
    if (in_array(ldrnseo_tab(), array('pages', 'business'), true)) {
        wp_enqueue_media();
    }
    wp_localize_script('ldrnseo', 'ldrnSeo', array(
        'ajax'  => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ldrnseo'),
        'site'  => ldrnseo_base(),
        'host'  => (string) wp_parse_url(home_url(), PHP_URL_HOST),
    ));
}, 20);

function ldrnseo_render() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to view this.', 'ldrn'));
    }
    $tab = ldrnseo_tab();
    $audit = get_option('ldrnseo_audit');
    $errs = 0;
    if (is_array($audit)) {
        $cat = ldrnseo_issue_catalog();
        foreach ($audit['issues'] as $id => $rows) {
            $errs += in_array($cat[$id][0], array('error', 'warning'), true) ? 1 : 0;
        }
    }
    $icons = array('overview' => 'radar', 'pages' => 'layout-grid', 'audit' => 'scan-search', 'speed' => 'gauge', 'performance' => 'activity',
                   'business' => 'building-2', 'settings' => 'settings-2');
    echo '<div class="wrap ldrnseo" data-tab="' . esc_attr($tab) . '">';
    echo '<header class="is-head"><h1 class="is-title">SEO<em>found.</em></h1>';
    echo '<p class="is-where"><i class="is-dotlive' . (ldrnseo_is_local() ? '' : ' is-on') . '"></i>' . esc_html((string) wp_parse_url(home_url(), PHP_URL_HOST))
       . (ldrnseo_is_local() ? ' &middot; local copy' : ' &middot; live') . '</p></header>';
    echo '<nav class="is-tabs" aria-label="SEO sections">';
    foreach (ldrnseo_tabs() as $id => $label) {
        echo '<a href="' . esc_url(ldrnseo_url($id)) . '"' . ($id === $tab ? ' aria-current="page"' : '') . '>' . ldrnseo_icon($icons[$id]) . esc_html($label)
           . ('audit' === $id && $errs ? '<b>' . (int) $errs . '</b>' : '') . '</a>';
    }
    echo '</nav>';

    if (isset($_GET['done'])) {
        $msgs = array('saved' => 'Saved.', 'added' => 'Redirect added.', 'deleted' => 'Removed.', 'connected' => 'Search Console connected.',
                      'disconnected' => 'Search Console disconnected.', 'ignored' => 'Hidden from the list.', 'cleared' => 'List cleared.');
        $done = sanitize_key(wp_unslash($_GET['done']));
        if (isset($msgs[$done])) {
            echo '<p class="is-flash" role="status">' . ldrnseo_icon('check') . esc_html($msgs[$done]) . '</p>';
        }
    }
    if (isset($_GET['error'])) {
        echo '<p class="is-flash is-flash-bad" role="alert">' . ldrnseo_icon('alert-triangle') . esc_html(wp_unslash($_GET['error'])) . '</p>';
    }
    if (!ldrnseo_can_edit()) {
        echo '<p class="is-flash">' . ldrnseo_icon('eye') . 'View only</p>';
    }

    echo '<main class="is-body">';
    call_user_func('ldrnseo_screen_' . $tab);
    echo '</main></div>';
}

/* -------------------------------------------------------------------------
 * Saving
 * ---------------------------------------------------------------------- */

function ldrnseo_guard($action) {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Not allowed.', 'ldrn'), '', array('response' => 403));
    }
    check_admin_referer($action);
}

function ldrnseo_back($tab, array $args = array()) {
    wp_safe_redirect(ldrnseo_url($tab, $args));
    exit;
}

add_action('admin_post_ldrnseo_save_page', function () {
    ldrnseo_guard('ldrnseo_save_page');
    $route = isset($_POST['route']) ? sanitize_text_field(wp_unslash($_POST['route'])) : '';
    $data  = isset($_POST['seo']) ? (array) wp_unslash($_POST['seo']) : array();
    $ok    = ldrnseo_page_save($route, $data);
    ldrnseo_back('pages', $ok ? array('route' => $route, 'done' => 'saved') : array('error' => 'That page is not one of the site\'s pages.'));
});

add_action('admin_post_ldrnseo_save_business', function () {
    ldrnseo_guard('ldrnseo_save_business');
    $in  = isset($_POST['b']) ? (array) wp_unslash($_POST['b']) : array();
    $out = array();
    foreach (ldrnseo_business_defaults() as $k => $default) {
        $v = $in[$k] ?? '';
        if (is_int($default)) {
            $out[$k] = absint($v);
        } elseif (in_array($k, array('phones', 'areas', 'profiles', 'description'), true)) {
            $out[$k] = sanitize_textarea_field($v);
        } elseif (in_array($k, array('founder_url', 'maps_url'), true)) {
            $out[$k] = esc_url_raw($v);
        } elseif ('email' === $k) {
            $out[$k] = sanitize_email($v);
        } else {
            $out[$k] = sanitize_text_field($v);
        }
    }
    if (!in_array($out['type'], array_keys(ldrnseo_business_types()), true)) {
        $out['type'] = 'ChildCare';
    }
    $out['profiles'] = implode("\n", array_filter(array_map('esc_url_raw', ldrnseo_lines($out['profiles']))));
    update_option('ldrnseo_business', $out, false);

    $listings = array();
    foreach (ldrnseo_listing_defs() as $key => $def) {
        $row = isset($_POST['l'][$key]) ? (array) wp_unslash($_POST['l'][$key]) : array();
        $listings[$key] = array('url' => esc_url_raw($row['url'] ?? ''), 'as' => sanitize_text_field($row['as'] ?? ''));
    }
    update_option('ldrnseo_listings', $listings, false);
    ldrnseo_back('business', array('done' => 'saved'));
});

add_action('admin_post_ldrnseo_save_settings', function () {
    ldrnseo_guard('ldrnseo_save_settings');
    $in  = isset($_POST['s']) ? (array) wp_unslash($_POST['s']) : array();
    $s   = ldrnseo_settings();
    // Only what the form shows is changed; anything set elsewhere (robots.txt
    // policy, llms.txt, older verifications) keeps its stored value.
    $out = array(
        'launch_url'       => esc_url_raw(trim((string) ($in['launch_url'] ?? ''))),
        'measure'          => empty($in['measure']) ? 0 : 1,
        'ga4_id'           => strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', (string) ($in['ga4_id'] ?? ''))),
        'gtm_id'           => strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', (string) ($in['gtm_id'] ?? ''))),
        'psi_key'          => '' !== trim((string) ($in['psi_key'] ?? '')) ? sanitize_text_field($in['psi_key']) : $s['psi_key'],
    );
    foreach (array('verify_google', 'verify_bing', 'verify_yandex', 'verify_pinterest', 'verify_facebook') as $k) {
        if (!isset($in[$k])) {
            continue;
        }
        $v = trim((string) $in[$k]);
        // People paste the whole <meta> tag; keep only its content.
        if (preg_match('/content=["\']([^"\']+)["\']/', $v, $m)) {
            $v = $m[1];
        }
        $out[$k] = preg_replace('/[^A-Za-z0-9_\-=.]/', '', $v);
    }
    if (!empty($in['psi_key_clear'])) {
        $out['psi_key'] = '';
    }
    // Which pages search engines may list (and the sitemap carries).
    if (isset($in['idx_present'])) {
        $pages = get_option('ldrnseo_pages', array());
        $pages = is_array($pages) ? $pages : array();
        foreach (ldrnseo_routes() as $route) {
            $pages[$route]['noindex'] = empty($in['idx'][$route]) ? 1 : 0;
        }
        update_option('ldrnseo_pages', $pages, false);
    }
    if (preg_match('/^G-[A-Z0-9]{4,}$/', $out['ga4_id']) === 0) {
        $out['ga4_id'] = '';
    }
    if (preg_match('/^GTM-[A-Z0-9]{4,}$/', $out['gtm_id']) === 0) {
        $out['gtm_id'] = '';
    }
    // The Search Console key, when one is pasted.
    $json = trim((string) ($in['gsc_key'] ?? ''));
    if ('' !== $json) {
        $key = json_decode($json, true);
        if (!is_array($key) || empty($key['client_email']) || empty($key['private_key']) || 'service_account' !== ($key['type'] ?? '')) {
            ldrnseo_back('settings', array('error' => 'That is not a service-account key. In Google Cloud: IAM > Service accounts > Keys > Add key > JSON.'));
        }
        $out['gsc_key'] = wp_json_encode(array_intersect_key($key, array_flip(array('type', 'project_id', 'private_key_id', 'private_key', 'client_email', 'client_id', 'token_uri'))));
        delete_transient('ldrnseo_gsc_token');
    }
    if (isset($in['gsc_property'])) {
        $out['gsc_property'] = sanitize_text_field($in['gsc_property']);
    }
    ldrnseo_update_settings($out);
    ldrnseo_back('settings', array('done' => '' !== $json ? 'connected' : 'saved'));
});

add_action('admin_post_ldrnseo_gsc_disconnect', function () {
    ldrnseo_guard('ldrnseo_gsc_disconnect');
    ldrnseo_update_settings(array('gsc_key' => '', 'gsc_property' => ''));
    delete_transient('ldrnseo_gsc_token');
    ldrnseo_back('settings', array('done' => 'disconnected'));
});

add_action('admin_post_ldrnseo_redirect', function () {
    global $wpdb;
    ldrnseo_guard('ldrnseo_redirect');
    $do = sanitize_key($_POST['do'] ?? 'add');
    $t  = ldrnseo_table('redirects');
    if ('delete' === $do) {
        $wpdb->delete($t, array('id' => absint($_POST['id'] ?? 0)));
        ldrnseo_back('settings', array('done' => 'deleted', '#' => 'redirects'));
    }
    $from = trim((string) wp_unslash($_POST['from'] ?? ''));
    $to   = trim((string) wp_unslash($_POST['to'] ?? ''));
    $code = (int) ($_POST['code'] ?? 301);
    // Accept a full old URL as well as a path.
    if (preg_match('#^https?://#i', $from)) {
        $from = (string) wp_parse_url($from, PHP_URL_PATH);
    }
    $from = '/' . trim(sanitize_text_field($from), '/');
    if ('/' !== $from && '*' !== substr($from, -1)) {
        $from = strtolower($from);
    }
    $err = '';
    if ('/' === $from) {
        $err = 'The home page cannot be redirected from here.';
    } elseif (in_array($from, ldrnseo_routes(), true)) {
        $err = $from . ' is a live page. Redirecting it would hide it.';
    } elseif (410 !== $code && '' === $to) {
        $err = 'Where should it go?';
    } elseif (410 !== $code && untrailingslashit($to) === untrailingslashit($from)) {
        $err = 'A page cannot redirect to itself.';
    }
    if ($err) {
        ldrnseo_back('settings', array('error' => $err, '#' => 'redirects'));
    }
    if (410 !== $code) {
        $to = 0 === strpos($to, '/') ? '/' . ltrim(sanitize_text_field($to), '/') : esc_url_raw($to);
    }
    $wpdb->replace($t, array('source' => substr($from, 0, 190), 'target' => 410 === $code ? '' : $to,
                             'code' => in_array($code, array(301, 302, 307, 308, 410), true) ? $code : 301, 'hits' => 0, 'created' => current_time('mysql')));
    // Once redirected, it is no longer a broken address.
    $wpdb->delete(ldrnseo_table('notfound'), array('path' => $from));
    ldrnseo_back('settings', array('done' => 'added', '#' => 'redirects'));
});

add_action('admin_post_ldrnseo_404', function () {
    global $wpdb;
    ldrnseo_guard('ldrnseo_404');
    $t  = ldrnseo_table('notfound');
    $do = sanitize_key($_POST['do'] ?? '');
    if ('ignore' === $do) {
        $wpdb->update($t, array('ignored' => 1), array('path' => sanitize_text_field(wp_unslash($_POST['path'] ?? ''))));
        ldrnseo_back('settings', array('done' => 'ignored', '#' => 'notfound'));
    }
    if ('clear' === $do) {
        $wpdb->query("DELETE FROM $t");
        ldrnseo_back('settings', array('done' => 'cleared', '#' => 'notfound'));
    }
    ldrnseo_back('settings', array('#' => 'redirects'));
});

/* -------------------------------------------------------------------------
 * Background work the screen asks for
 * ---------------------------------------------------------------------- */

function ldrnseo_ajax_guard() {
    if (!current_user_can('manage_options') || !check_ajax_referer('ldrnseo', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Your session has expired. Reload the page.'), 403);
    }
}

add_action('wp_ajax_ldrnseo_audit', function () {
    ldrnseo_ajax_guard();
    @set_time_limit(120);
    if ('start' === ($_POST['step'] ?? '')) {
        $run = ldrnseo_audit_start();
        wp_send_json_success(array('done' => false, 'left' => count($run['queue']), 'total' => $run['total'], 'next' => ldrnseo_audit_next()));
    }
    // The page the screen just rendered - accepted only for the address the
    // audit is about to read, so a retried step can never file one page's
    // rendering under the next.
    $html  = null;
    $route = isset($_POST['route']) ? (string) wp_unslash($_POST['route']) : '';
    if ('' !== $route && $route === ldrnseo_audit_next() && isset($_POST['html'])) {
        $html = (string) wp_unslash($_POST['html']);
    }
    $r = ldrnseo_audit_step($html);
    $r['next'] = ldrnseo_audit_next();
    wp_send_json_success($r);
});

add_action('wp_ajax_ldrnseo_psi', function () {
    ldrnseo_ajax_guard();
    @set_time_limit(150);
    $strategy = 'desktop' === ($_POST['strategy'] ?? '') ? 'desktop' : 'mobile';
    $route = sanitize_text_field(wp_unslash($_POST['route'] ?? '/'));
    if (!in_array($route, ldrnseo_routes(), true)) {
        $route = '/';
    }
    $r = ldrnseo_psi($route, $strategy);
    if (is_wp_error($r)) {
        wp_send_json_error(array('message' => $r->get_error_message()));
    }
    wp_send_json_success($r);
});

