<?php
/**
 * What the SEO suite keeps, and where.
 *
 *   ldrnseo_settings   connections, verification codes, crawler policy, switches
 *   ldrnseo_business   the facts about the nursery that structured data publishes
 *   ldrnseo_pages      per address: title, description, focus keyphrase,
 *                      "keep out of search", share image
 *   ldrn_site_settings (the theme's) GA4 Measurement ID and the Search Console
 *                      verification code - read and written there, so Site
 *                      Settings and this screen can never disagree
 *
 * Four small tables hold what happens on the live site: visits, crawler visits,
 * addresses that answered 404, and redirects.
 */

defined('ABSPATH') || exit;

/* -------------------------------------------------------------------------
 * Settings
 * ---------------------------------------------------------------------- */

function ldrnseo_settings_defaults() {
    return array(
        // Where the public reaches the site while WordPress runs somewhere else
        // (a local copy). The client's own domain replaces it at go-live.
        'launch_url'       => 'https://ldrn.krildigital.com',
        'measure'          => 1,           // first-party visits + vitals, consent-gated
        'ga4_id'           => '',
        'gtm_id'           => '',
        'verify_google'    => '',
        'verify_bing'      => '',
        'verify_yandex'    => '',
        'verify_pinterest' => '',
        'verify_facebook'  => '',
        'ai_policy'        => 'search',    // all | search | none - the starting point
        'ai_block'         => null,        // the AI crawlers refused, by name; null = as ai_policy says
        'robots_extra'     => '',
        'llms'             => 1,
        'psi_key'          => '',
        'gsc_key'          => '',          // service-account JSON (never shown again)
        'gsc_property'     => '',
    );
}

function ldrnseo_settings() {
    $stored = get_option('ldrnseo_settings', array());
    $s = wp_parse_args(is_array($stored) ? $stored : array(), ldrnseo_settings_defaults());
    // The theme's Site Settings own these two; this screen edits the same values.
    $site = get_option('ldrn_site_settings');
    $site = is_array($site) ? $site : array();
    $s['ga4_id'] = strtoupper(trim((string) ($site['ga4_id'] ?? '')));
    $gsc = trim((string) ($site['gsc_verification'] ?? ''));
    if (preg_match('/content=["\']([^"\']+)["\']/', $gsc, $m)) {
        $gsc = trim($m[1]);
    }
    $s['verify_google'] = $gsc;
    return $s;
}

/** The AI crawlers robots.txt refuses: chosen bot by bot, or as the policy preset says. */
function ldrnseo_ai_blocked() {
    $s = ldrnseo_settings();
    if (is_array($s['ai_block'])) {
        return array_values(array_intersect($s['ai_block'], ldrnseo_ai_bot_names()));
    }
    $ai = ldrnseo_ai_bots();
    if ('none' === $s['ai_policy']) {
        return array_merge(array_keys($ai['training']), array_keys($ai['answers']));
    }
    return 'search' === $s['ai_policy'] ? array_keys($ai['training']) : array();
}

function ldrnseo_ai_bot_names() {
    $ai = ldrnseo_ai_bots();
    return array_merge(array_keys($ai['answers']), array_keys($ai['training']));
}

function ldrnseo_setting($key) {
    $s = ldrnseo_settings();
    return $s[$key] ?? '';
}

function ldrnseo_update_settings(array $changes) {
    // GA4 and the Google verification code live in the theme's Site Settings.
    $site = get_option('ldrn_site_settings');
    $site = is_array($site) ? $site : array();
    $touch = false;
    if (array_key_exists('ga4_id', $changes)) {
        $site['ga4_id'] = (string) $changes['ga4_id'];
        $touch = true;
    }
    if (array_key_exists('verify_google', $changes)) {
        $site['gsc_verification'] = (string) $changes['verify_google'];
        $touch = true;
    }
    if ($touch) {
        update_option('ldrn_site_settings', $site);
    }
    unset($changes['ga4_id'], $changes['verify_google']);
    $stored = get_option('ldrnseo_settings', array());
    $s = array_merge(wp_parse_args(is_array($stored) ? $stored : array(), ldrnseo_settings_defaults()), $changes);
    unset($s['ga4_id'], $s['verify_google']);
    update_option('ldrnseo_settings', $s, false);
    return ldrnseo_settings();
}

/* -------------------------------------------------------------------------
 * The nursery, as search engines should know it.
 *
 * Every default below is a fact the site already publishes (the Hounslow
 * setting record, the footer, Contact, the Ofsted page) or a public record:
 *   - Ofsted URN EY545805, "Good", inspected 27 June 2025
 *     (reports.ofsted.gov.uk/provider/16/EY545805)
 *   - LRDN Limited, company 10610476 (Companies House), the company the
 *     footer names
 * Nothing is invented: what neither says (map pin, fees, a Google Business
 * Profile owner, social profiles, the manager's name) starts empty and is simply
 * left out of the structured data until the nursery confirms it.
 * ---------------------------------------------------------------------- */

function ldrnseo_business_defaults() {
    return array(
        'name'          => 'Little Rainbows Day Nursery',
        'legal_name'    => 'LRDN Limited',
        'alt_name'      => 'Little Rainbows',
        'type'          => 'ChildCare',
        'gbp_category'  => '',
        'slogan'        => 'Learning Through Play',
        'description'   => 'Little Rainbows Day Nursery is a day nursery at 64 Pears Road, Hounslow, rated Good by Ofsted (June 2025). It cares for children aged 6 months to 5 years, Monday to Friday, 8am to 6pm, with learning through play at the heart of every day.',
        'email'         => 'littlerainbowsdaynursery@yahoo.co.uk',
        'phones'        => '020 4619 6231',
        'street'        => '64 Pears Road',
        'locality'      => 'Hounslow',
        'region'        => 'Greater London',
        'postcode'      => 'TW3 1SR',
        'country'       => 'GB',
        // The pin of the Google Maps place "Little Rainbows Day Nursery"
        // (cid 9670622344649555139), found 4 Oct 2026.
        'lat'           => '51.4693041',
        'lng'           => '-0.3545101',
        'maps_url'      => 'https://maps.google.com/?cid=9670622344649555139',
        'areas'         => "Hounslow",
        'hours'         => 'Mo-Fr 08:00-18:00',
        'founded'       => '2005',
        'price_range'   => '',
        'ages'          => '6 months – 5 years',
        'places'        => '40',
        'ofsted_urn'    => 'EY545805',
        'ofsted_grade'  => 'Good',
        'ofsted_date'   => '2025-06-27',
        'company_no'    => '10610476',
        'founder'       => '',
        'founder_role'  => '',
        'founder_url'   => '',
        'profiles'      => '',
        'logo_id'       => 0,
        'share_id'      => 0,
    );
}

function ldrnseo_business() {
    $stored = get_option('ldrnseo_business', array());
    return wp_parse_args(is_array($stored) ? $stored : array(), ldrnseo_business_defaults());
}

/** One per line, trimmed, empties dropped. */
function ldrnseo_lines($text) {
    return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $text)), 'strlen'));
}

/* -------------------------------------------------------------------------
 * The site's pages - every address the site answers with its own content.
 * ---------------------------------------------------------------------- */

/** Paths ldrn-seo.php lists as routes but answers with a redirect. */
function ldrnseo_legacy_routes() {
    return array('/book-a-visit');
}

/** The published CMS records that are pages of their own: path prefix => post type, kind. */
function ldrnseo_record_types() {
    return array(
        'ldrn_setting' => array('/our-nursery/', 'setting', 'Our nursery'),
        'post'         => array('/blogs/', 'article', 'Blog'),
        'ldrn_event'   => array('/events/', 'event', 'Events'),
        'ldrn_job'     => array('/careers/vacancies/', 'job', 'Vacancies'),
    );
}

/** Every record that is a page today: route => WP_Post. */
function ldrnseo_records() {
    static $out = null;
    if (null !== $out) {
        return $out;
    }
    $out = array();
    foreach (ldrnseo_record_types() as $type => $def) {
        foreach (get_posts(array('post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => 200, 'orderby' => 'menu_order date', 'order' => 'ASC')) as $post) {
            if (function_exists('ldrn_seo_post_is_ready') && !ldrn_seo_post_is_ready($post)) {
                continue;
            }
            $out[$def[0] . $post->post_name] = $post;
        }
    }
    return $out;
}

/** The record an address is for, or null. */
function ldrnseo_record_for($route) {
    $all = ldrnseo_records();
    return $all[$route] ?? null;
}

/** The static route table as ldrn-seo.php ships it, before this plugin's own words. */
function ldrnseo_static_routes_raw() {
    if (!function_exists('ldrn_seo_static_routes')) {
        return array('/' => array());
    }
    $GLOBALS['ldrnseo_raw'] = true;
    $routes = ldrn_seo_static_routes();
    $GLOBALS['ldrnseo_raw'] = false;
    return $routes;
}

/** Every public address: the route table (less legacy redirects), then each record. */
function ldrnseo_routes() {
    static $routes = null;
    if (null !== $routes) {
        return $routes;
    }
    $routes = array_values(array_diff(array_keys(ldrnseo_static_routes_raw()), ldrnseo_legacy_routes()));
    foreach (array_keys(ldrnseo_records()) as $route) {
        $routes[] = $route;
    }
    return $routes = array_values(array_unique($routes));
}

/** What the SEO suite knows about each address, in sitemap order. */
function ldrnseo_pages_list() {
    $out = array();
    foreach (ldrnseo_routes() as $route) {
        $out[$route] = ldrnseo_page($route);
    }
    return $out;
}

/** The page directory's name for a route ("Our Curriculum"), when it has one. */
function ldrnseo_route_label($route) {
    static $labels = null;
    if (null === $labels) {
        $labels = array();
        if (function_exists('ldrn_ws_public_pages')) {
            foreach ((array) ldrn_ws_public_pages() as $p) {
                if (!empty($p['route'])) {
                    $labels[$p['route']] = (string) $p['label'];
                }
            }
        }
    }
    return $labels[$route] ?? '';
}

function ldrnseo_page($route) {
    $all   = get_option('ldrnseo_pages', array());
    $own   = is_array($all) && isset($all[$route]) ? (array) $all[$route] : array();
    $raw   = ldrnseo_static_routes_raw();
    $page  = array(
        'route'       => $route,
        'label'       => $route,
        'kind'        => 'page',
        'title'       => (string) ($own['title'] ?? ''),
        'description' => (string) ($own['description'] ?? ''),
        'keyphrase'   => (string) ($own['keyphrase'] ?? ''),
        // A page the route table keeps out of search (Apply, Privacy) starts hidden.
        'noindex'     => array_key_exists('noindex', $own) ? !empty($own['noindex']) : (isset($raw[$route]['index']) && !$raw[$route]['index']),
        'image_id'    => (int) ($own['image_id'] ?? 0),
        'post_id'     => 0,
    );
    if ($post = ldrnseo_record_for($route)) {
        $types = ldrnseo_record_types();
        $page['label']   = wp_strip_all_tags(get_the_title($post));
        $page['kind']    = $types[$post->post_type][1];
        $page['post_id'] = $post->ID;
    } else {
        $page['label'] = ldrnseo_route_label($route) ?: ('/' === $route ? 'Home' : ucwords(str_replace(array('/', '-'), array(' · ', ' '), trim($route, '/'))));
    }
    return $page;
}

/** The title and description written for a page on this screen ('' = what the site ships). */
function ldrnseo_page_words($route) {
    $page = ldrnseo_page($route);
    return array('title' => $page['title'], 'description' => $page['description']);
}

/**
 * What each address carries when nobody has written its words here: the
 * route table's own title and description (the same words the React build
 * holds), or what a record's own fields say. Shown as the placeholder in
 * the Pages editor.
 */
function ldrnseo_built_words($route) {
    $raw = ldrnseo_static_routes_raw();
    if (isset($raw[$route])) {
        return array('title' => (string) ($raw[$route]['title'] ?? ''), 'description' => (string) ($raw[$route]['description'] ?? ''));
    }
    $post = ldrnseo_record_for($route);
    if (!$post || !function_exists('ldrn_seo_clean_text')) {
        return array('title' => '', 'description' => '');
    }
    $name = ldrn_seo_clean_text(get_the_title($post), 90);
    switch ($post->post_type) {
        case 'ldrn_setting':
            return array('title' => ldrn_seo_clean_text(get_the_title($post), 80) . ' Nursery | Little Rainbows Day Nursery',
                         'description' => ldrn_seo_clean_text(get_post_meta($post->ID, '_ldrn_setting_short', true) ?: 'Learning through play at the Little Rainbows ' . $name . ' setting.', 160));
        case 'ldrn_job':
            return array('title' => ldrn_seo_clean_text(get_the_title($post), 80) . ' - Careers | Little Rainbows Day Nursery',
                         'description' => ldrn_seo_clean_text(get_post_meta($post->ID, '_ldrn_job_short', true) ?: 'Apply for this early-years role at Little Rainbows Day Nursery.', 160));
        default:
            return array('title' => $name . ' | Little Rainbows Day Nursery', 'description' => ldrn_seo_clean_text(get_the_excerpt($post) ?: $post->post_content, 160));
    }
}

function ldrnseo_page_facts_cache() {
    $out = array();
    foreach (ldrnseo_routes() as $route) {
        $out[$route] = ldrnseo_built_words($route);
    }
    return $out;
}

/** Save one address. */
function ldrnseo_page_save($route, array $data) {
    if (!in_array($route, ldrnseo_routes(), true)) {
        return false;
    }
    $desc = trim(preg_replace('/\s+/', ' ', sanitize_textarea_field($data['description'] ?? '')));
    $all  = get_option('ldrnseo_pages', array());
    $all  = is_array($all) ? $all : array();
    $all[$route] = array(
        'title'       => sanitize_text_field($data['title'] ?? ''),
        'description' => $desc,
        'keyphrase'   => sanitize_text_field($data['keyphrase'] ?? ''),
        'noindex'     => !empty($data['noindex']) ? 1 : 0,
        'image_id'    => absint($data['image_id'] ?? 0),
    );
    update_option('ldrnseo_pages', $all, false);
    return true;
}

/* -------------------------------------------------------------------------
 * Addresses
 * ---------------------------------------------------------------------- */

function ldrnseo_base() {
    return untrailingslashit(home_url());
}

function ldrnseo_abs($route) {
    return ldrnseo_base() . ('/' === $route ? '/' : $route);
}

/** Is WordPress running at an address only this computer can reach? */
function ldrnseo_is_local() {
    $host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
    return (bool) preg_match('/(^localhost$|\.local$|\.test$|\.localhost$|^127\.|^10\.|^192\.168\.)/i', $host);
}

/** The address the world reaches: the live site, or the launch address set in Settings while this is a local copy. */
function ldrnseo_public_url() {
    if (!ldrnseo_is_local()) {
        return trailingslashit(home_url());
    }
    $launch = (string) ldrnseo_setting('launch_url');
    return trailingslashit($launch ?: 'https://ldrn.krildigital.com');
}

/* -------------------------------------------------------------------------
 * Tables
 * ---------------------------------------------------------------------- */

function ldrnseo_table($name) {
    global $wpdb;
    return $wpdb->prefix . 'ldrnseo_' . $name;
}

function ldrnseo_install() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $c = $wpdb->get_charset_collate();

    dbDelta('CREATE TABLE ' . ldrnseo_table('hits') . " (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  day date NOT NULL,
  ts datetime NOT NULL,
  pv char(16) NOT NULL DEFAULT '',
  visitor char(16) NOT NULL DEFAULT '',
  path varchar(190) NOT NULL DEFAULT '',
  entry tinyint(1) NOT NULL DEFAULT 0,
  channel varchar(12) NOT NULL DEFAULT '',
  source varchar(40) NOT NULL DEFAULT '',
  ref_host varchar(120) NOT NULL DEFAULT '',
  utm_source varchar(60) NOT NULL DEFAULT '',
  utm_medium varchar(60) NOT NULL DEFAULT '',
  utm_campaign varchar(60) NOT NULL DEFAULT '',
  device varchar(8) NOT NULL DEFAULT '',
  engaged int(10) unsigned DEFAULT NULL,
  enquiry tinyint(1) NOT NULL DEFAULT 0,
  contact varchar(10) NOT NULL DEFAULT '',
  lcp int(10) unsigned DEFAULT NULL,
  inp int(10) unsigned DEFAULT NULL,
  cls int(10) unsigned DEFAULT NULL,
  fcp int(10) unsigned DEFAULT NULL,
  ttfb int(10) unsigned DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY day (day),
  KEY pv (pv)
) $c;");

    dbDelta('CREATE TABLE ' . ldrnseo_table('bots') . " (
  day date NOT NULL,
  bot varchar(32) NOT NULL,
  path varchar(190) NOT NULL,
  hits int(10) unsigned NOT NULL DEFAULT 0,
  verified tinyint(1) NOT NULL DEFAULT 0,
  last_seen datetime NOT NULL,
  PRIMARY KEY  (day,bot,path)
) $c;");

    dbDelta('CREATE TABLE ' . ldrnseo_table('notfound') . " (
  path varchar(190) NOT NULL,
  hits int(10) unsigned NOT NULL DEFAULT 0,
  first_seen datetime NOT NULL,
  last_seen datetime NOT NULL,
  referrer varchar(190) NOT NULL DEFAULT '',
  ignored tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (path)
) $c;");

    dbDelta('CREATE TABLE ' . ldrnseo_table('redirects') . " (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  source varchar(190) NOT NULL,
  target varchar(500) NOT NULL,
  code smallint(5) unsigned NOT NULL DEFAULT 301,
  hits int(10) unsigned NOT NULL DEFAULT 0,
  last_hit datetime DEFAULT NULL,
  created datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY source (source)
) $c;");

    update_option('ldrnseo_db', LDRNSEO_DB, false);

    if (!wp_next_scheduled('ldrnseo_daily')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'ldrnseo_daily');
    }
    if (!wp_next_scheduled('ldrnseo_weekly_audit')) {
        wp_schedule_event(time() + DAY_IN_SECONDS, 'weekly', 'ldrnseo_weekly_audit');
    }
}

/**
 * Daily housekeeping: the visitor salt of past days is thrown away (so a
 * visitor id can never be recomputed), and records older than 13 months go.
 */
add_action('ldrnseo_daily', function () {
    global $wpdb;
    $salts = (array) get_option('ldrnseo_salt', array());
    $today = wp_date('Y-m-d');
    update_option('ldrnseo_salt', isset($salts[$today]) ? array($today => $salts[$today]) : array(), false);
    $cut = wp_date('Y-m-d', time() - 400 * DAY_IN_SECONDS);
    $wpdb->query($wpdb->prepare('DELETE FROM ' . ldrnseo_table('hits') . ' WHERE day < %s', $cut));
    $wpdb->query($wpdb->prepare('DELETE FROM ' . ldrnseo_table('bots') . ' WHERE day < %s', $cut));
});

/* -------------------------------------------------------------------------
 * Listings - where the nursery appears off the site.
 *
 * Parents choose a nursery by searching the map, reading the Ofsted report
 * and the childcare directories, and asking other parents. Being listed,
 * identically, in those places is most of off-page SEO for a nursery. Each
 * entry says why it matters, so the list reads as a plan rather than a form.
 *
 *   key => [label, group, why, sameAs (same entity in structured data),
 *           compare name/address with the Business tab]
 * ---------------------------------------------------------------------- */

function ldrnseo_listing_defs() {
    return array(
        'google'        => array('Google Business Profile', 'map', 'Decides the map pack ("nursery near me", "nursery in Hounslow") and the panel parents see before they ever open the site - with its reviews.', false, true),
        'bing'          => array('Bing Places', 'map', 'Feeds Bing, Microsoft Copilot and the local answers in ChatGPT search. Imports from the Google profile in minutes.', false, true),
        'apple'         => array('Apple Business Connect', 'map', 'Apple Maps, Siri and Spotlight on every iPhone.', false, true),
        'ofsted'        => array('Ofsted', 'official', 'The page every parent checks. Google treats it as the authority on the nursery\'s name, address and registration.', true, true),
        'companies'     => array('Companies House', 'official', 'The legal entity behind the nursery (its registered office may differ from the nursery\'s own address).', true, false),
        'hounslow_fis'  => array('Hounslow Family Information Service', 'official', 'The council\'s childcare directory, where families look for funded places.', false, true),
        'daynurseries'  => array('daynurseries.co.uk', 'directory', 'The UK\'s largest nursery review site; its pages rank on page one for most "nursery in <town>" searches and carry parents\' reviews.', false, true),
        'childcare'     => array('Childcare.co.uk', 'directory', 'Parents search it by postcode for places and vacancies.', false, true),
        'locrating'     => array('Locrating', 'directory', 'Shows nurseries on a map with their Ofsted grade; used by families choosing where to live.', false, true),
        'goodcareguide' => array('Good Care Guide', 'directory', 'A review directory for childcare; an extra place for parents\' reviews.', false, true),
        'facebook'      => array('Facebook page', 'social', 'Where local parents\' groups recommend nurseries, and the source of Meta\'s link previews.', true, false),
        'instagram'     => array('Instagram', 'social', 'Day-to-day life at the nursery; linked as the same entity in structured data.', true, false),
        'linkedin'      => array('LinkedIn company page', 'social', 'What job applicants check before they apply.', true, false),
        'indeed'        => array('Indeed company page', 'social', 'Where early-years staff look the nursery up as an employer.', false, false),
    );
}

function ldrnseo_listings() {
    $stored = get_option('ldrnseo_listings', null);
    if (!is_array($stored)) {
        // Public listings found on 4 Oct 2026. The "as listed" text is each
        // page's own title, so the consistency check compares like with like.
        // Google Maps has a place for the nursery (whether the nursery has
        // claimed it is not visible from outside). No Facebook or Instagram
        // page was found.
        $stored = array(
            'google'        => array('url' => 'https://maps.google.com/?cid=9670622344649555139', 'as' => ''),
            'ofsted'        => array('url' => 'https://reports.ofsted.gov.uk/provider/16/EY545805', 'as' => 'Little Rainbows Day Nursery, 64 Pears Road, Hounslow TW3 1SR'),
            'companies'     => array('url' => 'https://find-and-update.company-information.service.gov.uk/company/10610476', 'as' => 'LRDN LIMITED, Ved Court, Alexandra Road, Hounslow, Middlesex, TW3 1LS'),
            'daynurseries'  => array('url' => 'https://www.daynurseries.co.uk/daynursery.cfm/searchazref/65432247817', 'as' => 'Little Rainbows Day Nursery, 64 Pears Road, Hounslow, London TW3 1SR'),
            'locrating'     => array('url' => 'https://www.locrating.com/nurseries-Little-Rainbows-Day-Nursery-4nrsyEY545805.aspx', 'as' => ''),
            'goodcareguide' => array('url' => 'http://www.goodcareguide.co.uk/childcare/nurseries/middlesex/little-rainbows-day-nursery-tw31sr', 'as' => ''),
            'indeed'        => array('url' => 'https://uk.indeed.com/cmp/Little-Rainbows-Day-Nursery', 'as' => ''),
        );
    }
    $out = array();
    foreach (ldrnseo_listing_defs() as $key => $def) {
        $out[$key] = array('url' => (string) ($stored[$key]['url'] ?? ''), 'as' => (string) ($stored[$key]['as'] ?? ''));
    }
    return $out;
}

/** Every profile that is the same entity: the Business tab's list plus listings marked sameAs. */
function ldrnseo_same_as() {
    $same = ldrnseo_lines(ldrnseo_business()['profiles']);
    foreach (ldrnseo_listings() as $key => $l) {
        if ($l['url'] && !empty(ldrnseo_listing_defs()[$key][3])) {
            $same[] = $l['url'];
        }
    }
    return array_values(array_unique($same));
}
