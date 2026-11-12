<?php
/**
 * Plugin Name: Alexandra Admin Hardening
 * Description: Security + admin cleanup (must-use, cannot be deactivated from the dashboard).
 */
if (!defined('ABSPATH')) { exit; }

/* ---- Existing hardening (kept) ---- */
add_filter('xmlrpc_enabled', '__return_false');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
add_action('admin_menu', function () {
    remove_menu_page('edit.php');
    remove_menu_page('edit-comments.php');
}, 999);
add_action('admin_bar_menu', function ($bar) {
    $bar->remove_node('new-post');
    $bar->remove_node('comments');
}, 999);

/* ---- Added 2026-08-23: stop username enumeration ---- */

// 1) Block the REST users endpoint for anyone who cannot already list users.
//    Logged-in editors/admins keep full access; the public gets a 401.
add_filter('rest_endpoints', function ($endpoints) {
    if (current_user_can('list_users')) {
        return $endpoints;
    }
    foreach (array('/wp/v2/users', '/wp/v2/users/(?P<id>[\d]+)') as $route) {
        if (isset($endpoints[$route])) {
            unset($endpoints[$route]);
        }
    }
    return $endpoints;
});

// 2) Strip the author name/URL leaked by the public oEmbed endpoint.
add_filter('oembed_response_data', function ($data) {
    unset($data['author_name'], $data['author_url']);
    return $data;
}, 10, 1);

// 3) Remove the REST "Link:" discovery header and oEmbed discovery tags
//    (defence in depth; does not affect the site's own /wp-json calls).
remove_action('template_redirect', 'rest_output_link_header', 11);
remove_action('wp_head', 'wp_oembed_add_discovery_links');

// 4) Belt-and-suspenders HSTS at the PHP layer in case a request bypasses .htaccess.
add_action('send_headers', function () {
    if (is_ssl() && !headers_sent()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
});

// 4b) Disable Application Passwords entirely. They authenticate over REST and
//     BYPASS two-factor, so with 2FA now in place they are a back door we do
//     not use. Turn them off site-wide.
add_filter('wp_is_application_passwords_available', '__return_false');

// 4c) Lock REST CORS to our own origin. WordPress core reflects ANY Origin with
//     Allow-Credentials:true; we replace that with a same-origin-only policy.
//     The site's own SPA/admin call REST same-origin (CORS not involved), so
//     cross-origin callers simply receive no ACAO header and the browser blocks
//     them from reading responses.
// WordPress registers rest_send_cors_headers on the rest_api_init hook, so it
// must be removed there (removing it at plugin-load is a no-op — too early).
add_action('rest_api_init', function () {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function ($value) {
        $origin = get_http_origin();
        if ($origin) {
            $home = wp_parse_url(home_url());
            $self = ($home['scheme'] ?? 'https') . '://' . ($home['host'] ?? '')
                . (isset($home['port']) ? ':' . $home['port'] : '');
            if (strtolower($origin) === strtolower($self)) {
                header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
                header('Access-Control-Allow-Credentials: true');
            }
            header('Vary: Origin');
        }
        header('Access-Control-Allow-Methods: OPTIONS, GET, POST');
        header('Access-Control-Allow-Headers: Authorization, X-WP-Nonce, Content-Type, Content-Disposition');
        return $value;
    }, 15);
}, 20);

// 5) Block ?author=N enumeration redirects on the front end for non-editors.
add_action('template_redirect', function () {
    if (is_admin()) {
        return;
    }
    if (isset($_GET['author']) && !current_user_can('edit_posts')) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
});
