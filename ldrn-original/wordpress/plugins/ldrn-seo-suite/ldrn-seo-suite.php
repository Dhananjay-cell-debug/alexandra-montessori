<?php
/**
 * Plugin Name: Little Rainbows SEO
 * Description: Search for the Little Rainbows Day Nursery site, end to end: what every page says to Google and to a shared link, the nursery's facts that search engines, maps and AI assistants read, an audit of the pages as they really render, first-party traffic and Core Web Vitals from real visitors (with their consent), enquiries, crawler visits, Search Console, PageSpeed and redirects.
 * Version: 1.0.0
 * Author: Kril Digital
 * Requires PHP: 8.0
 *
 * Why a plugin of its own
 * -----------------------
 * The route-aware metadata, canonicals, legacy redirects, structured data and
 * the sitemap are already written server-side by mu-plugins/ldrn-seo.php, and
 * the React <Seo /> component mirrors them in the browser. This plugin is the
 * instrument on top of that layer - the Welcome Woods / INU SEO suite fitted to
 * a nursery - and it changes the pages only through the filters ldrn-seo.php
 * already offers (ldrn_seo_static_routes, ldrn_seo_route_meta), so the two can
 * never write two titles or two canonicals for one page.
 *
 * Where each part lives
 * ---------------------
 *   includes/store.php      settings, nursery facts, per-page fields, tables
 *   includes/front.php      what crawlers get on top of ldrn-seo.php: per-page
 *                           words, verification, robots.txt policy, llms.txt,
 *                           the no-JavaScript reading, redirects, 404 and
 *                           crawler logs, consent-gated measurement
 *   includes/schema.php     the structured-data graph
 *   includes/collect.php    first-party measurement (consent-gated) + reports
 *   includes/audit.php      the audit of the pages as served and as rendered
 *   includes/google.php     Search Console
 *   includes/lighthouse.php PageSpeed Insights / Lighthouse reports
 *   includes/admin.php      the SEO screen and its tabs
 */

defined('ABSPATH') || exit;

define('LDRNSEO_VERSION', '1.0.0');
define('LDRNSEO_DB', 1);
define('LDRNSEO_DIR', plugin_dir_path(__FILE__));
define('LDRNSEO_URL', plugin_dir_url(__FILE__));

require_once LDRNSEO_DIR . 'includes/store.php';
require_once LDRNSEO_DIR . 'includes/schema.php';
require_once LDRNSEO_DIR . 'includes/front.php';
require_once LDRNSEO_DIR . 'includes/collect.php';
require_once LDRNSEO_DIR . 'includes/audit.php';
require_once LDRNSEO_DIR . 'includes/google.php';
require_once LDRNSEO_DIR . 'includes/lighthouse.php';
require_once LDRNSEO_DIR . 'includes/admin.php';

register_activation_hook(__FILE__, 'ldrnseo_install');

/*
 * The audit fetches the site's own pages. Each of those requests (and the
 * audit's own AJAX step) would otherwise spawn WP-Cron, which on a host with
 * few PHP workers takes the worker the next page fetch is waiting for.
 */
if (false !== strpos((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 'LDRN-SEO-Audit')
    || (isset($_POST['action']) && 'ldrnseo_audit' === $_POST['action'])) {
    add_action('init', function () {
        remove_action('init', 'wp_cron');
    }, 0);
}

// A site that already had the plugin when a table changed still needs it.
add_action('plugins_loaded', function () {
    if ((int) get_option('ldrnseo_db', 0) < LDRNSEO_DB) {
        ldrnseo_install();
    }
});

register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('ldrnseo_weekly_audit');
    wp_clear_scheduled_hook('ldrnseo_daily');
});
