<?php
/**
 * What visitors and crawlers receive - on top of ldrn-seo.php.
 *
 * mu-plugins/ldrn-seo.php already gives every address its server-side title,
 * description, canonical, robots directives, share tags, JSON-LD and its place
 * in /sitemap.xml, and answers 404 for anything that is not a page. This file
 * never writes a second copy of any of that. It feeds that layer through its
 * own filters:
 *
 *   ldrn_seo_static_routes   the words, share image and "keep out of search"
 *                            chosen on the Pages tab, for the fixed pages
 *   ldrn_seo_route_meta      the same for each nursery, article, event, vacancy
 *
 * and adds what it does not have: verification codes for Bing and the rest,
 * the nursery's facts in the structured data, a plain reading of each page
 * for crawlers that never run JavaScript, an AI-crawler policy in robots.txt,
 * /llms.txt, redirects managed from the screen, and two server-side logs that
 * need no consent because they record no person: addresses that answered 404,
 * and visits from search and AI crawlers. Measurement of real visitors loads
 * only after the visitor allows it.
 */

defined('ABSPATH') || exit;

/** Share the authoritative SEO output with React, including SPA navigation. */
add_filter('ldrn_cms_data', function ($data) {
    if (!function_exists('ldrn_seo_meta_for_path')) return $data;
    $data['seo'] = array();
    $business = ldrnseo_business();
    foreach (ldrnseo_routes() as $path) {
        $meta = ldrn_seo_meta_for_path($path);
        if (empty($meta['found'])) continue;
        $share = ldrnseo_share_image($path, $meta['image']);
        $data['seo'][$path] = array(
            'title' => $meta['title'], 'description' => $meta['description'],
            'canonical' => $meta['canonical'], 'image' => $share['url'],
            'index' => !empty($meta['index']) && (bool) get_option('blog_public', 1),
            'type' => $meta['type'], 'siteName' => $business['name'],
            'schema' => ldrnseo_graph($meta),
        );
    }
    return $data;
}, 100);

/** The route a request is for, the way the route table spells it ("/about"). */
function ldrnseo_request_path() {
    $path = (string) wp_parse_url(isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/', PHP_URL_PATH);
    $path = strtolower('/' . trim(rawurldecode($path), '/'));
    return '/index.php' === $path ? '/' : $path;
}

function ldrnseo_is_canvas() {
    if (function_exists('ldrn_ws_studio_is_canvas_request')) {
        return ldrn_ws_studio_is_canvas_request();
    }
    return isset($_GET['ldrn_ws_studio']);
}

/* -------------------------------------------------------------------------
 * The words chosen on the Pages tab, handed to ldrn-seo.php
 * ---------------------------------------------------------------------- */

add_filter('ldrn_seo_static_routes', function ($routes) {
    if (!empty($GLOBALS['ldrnseo_raw']) || !is_array($routes)) {
        return $routes;
    }
    $own = get_option('ldrnseo_pages', array());
    if (!is_array($own)) {
        return $routes;
    }
    foreach ($own as $route => $o) {
        if (!isset($routes[$route])) {
            continue;
        }
        if ('' !== (string) ($o['title'] ?? '')) {
            $routes[$route]['title'] = $o['title'];
        }
        if ('' !== (string) ($o['description'] ?? '')) {
            $routes[$route]['description'] = $o['description'];
        }
        if (array_key_exists('noindex', $o)) {
            // ldrn-seo.php leaves an index:false route out of the sitemap too.
            $routes[$route]['index'] = empty($o['noindex']);
        }
        if (!empty($o['image_id']) && ($src = wp_get_attachment_image_url((int) $o['image_id'], 'full'))) {
            $routes[$route]['image'] = $src;
        }
    }
    return $routes;
}, 20);

add_filter('ldrn_seo_route_meta', function ($meta, $path) {
    if (!is_array($meta) || empty($meta['found']) || !ldrnseo_record_for($path)) {
        return $meta;
    }
    $own = get_option('ldrnseo_pages', array());
    $o   = is_array($own) && isset($own[$path]) ? (array) $own[$path] : array();
    if ('' !== (string) ($o['title'] ?? '')) {
        $meta['title'] = $o['title'];
    }
    if ('' !== (string) ($o['description'] ?? '')) {
        $meta['description'] = $o['description'];
    }
    if (!empty($o['noindex'])) {
        $meta['index'] = false;
    }
    if (!empty($o['image_id']) && ($src = wp_get_attachment_image_url((int) $o['image_id'], 'full'))) {
        $meta['image'] = $src;
    }
    return $meta;
}, 20, 2);

/* -------------------------------------------------------------------------
 * The head: what ldrn-seo.php does not write
 * ---------------------------------------------------------------------- */

add_action('wp_head', function () {
    if (is_admin() || !function_exists('ldrn_seo_is_public_request') || !ldrn_seo_is_public_request()) {
        return;
    }
    $s = ldrnseo_settings();
    // Google's code is printed by the theme from Site Settings (the same value).
    $verify = array('verify_bing' => 'msvalidate.01', 'verify_yandex' => 'yandex-verification',
                    'verify_pinterest' => 'p:domain_verify', 'verify_facebook' => 'facebook-domain-verification');
    foreach ($verify as $key => $name) {
        if ('' !== (string) $s[$key]) {
            echo '<meta name="' . esc_attr($name) . '" content="' . esc_attr($s[$key]) . '">' . "\n";
        }
    }
    // Measurement: never inside the Site Editor, never for the team.
    if (!ldrnseo_is_canvas() && !is_user_logged_in()) {
        echo ldrnseo_measure_tag(ldrnseo_request_path());
    }
    echo ldrnseo_words_script();
}, 4);

/**
 * The React build sets each page's title and description again in the
 * browser, from its own copy of the route table. Words chosen on the Pages
 * tab are put back over it, on the first page and after every move to another
 * page without a reload, so a visitor, Google's renderer and the server all
 * see the same words.
 */
function ldrnseo_words_script() {
    $map = array();
    foreach (ldrnseo_routes() as $route) {
        $w = ldrnseo_page_words($route);
        if ('' !== $w['title'] || '' !== $w['description']) {
            $map[$route] = array('t' => $w['title'], 'd' => $w['description']);
        }
    }
    if (!$map) {
        return '';
    }
    $json = str_replace('</', '<\/', wp_json_encode((object) $map, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    return '<script id="ldrnseo-words">(function(m){function p(){var r=location.pathname.replace(/\/+$/,"")||"/";return m[r];}'
         . 'function a(){var w=p();if(!w)return;if(w.t){document.querySelectorAll("head title").forEach(function(t){if(t.textContent!==w.t)t.textContent=w.t;});}'
         . 'if(w.d){document.querySelectorAll(\'head meta[name="description"],head meta[property="og:description"]\').forEach(function(d){if(d.content!==w.d)d.content=w.d;});}}'
         . 'new MutationObserver(a).observe(document.head,{childList:true,subtree:true,characterData:true});addEventListener("popstate",a);a();})(' . $json . ');</script>' . "\n";
}

/**
 * The share tags and structured data when Yoast is not running (ldrn-seo.php
 * prints them itself in that case). Same tags, plus the share picture's size
 * and words, and the nursery's facts from the Business tab in the graph.
 */
remove_action('wp_head', 'ldrn_seo_output_fallback_meta', 3);
add_action('wp_head', function () {
    if (!function_exists('ldrn_seo_is_public_request') || !ldrn_seo_is_public_request() || (function_exists('ldrn_seo_yoast_active') && ldrn_seo_yoast_active())) {
        return;
    }
    $meta  = ldrn_seo_meta_for_request();
    $b     = ldrnseo_business();
    $share = ldrnseo_share_image($meta['path'], $meta['image']);
    $alt   = $share['alt'] ?: $meta['title'];
    $tags  = array(
        '<meta name="description" content="' . esc_attr($meta['description']) . '">',
        '<meta property="og:locale" content="en_GB">',
        '<meta property="og:type" content="' . esc_attr($meta['type']) . '">',
        '<meta property="og:site_name" content="' . esc_attr($b['name']) . '">',
        '<meta property="og:title" content="' . esc_attr($meta['title']) . '">',
        '<meta property="og:description" content="' . esc_attr($meta['description']) . '">',
        '<meta property="og:url" content="' . esc_url($meta['canonical']) . '">',
        '<meta property="og:image" content="' . esc_url($share['url']) . '">',
    );
    if ($share['width'] && $share['height']) {
        $tags[] = '<meta property="og:image:width" content="' . (int) $share['width'] . '">';
        $tags[] = '<meta property="og:image:height" content="' . (int) $share['height'] . '">';
    }
    $tags[] = '<meta property="og:image:alt" content="' . esc_attr($alt) . '">';
    $tags[] = '<meta name="twitter:card" content="summary_large_image">';
    $tags[] = '<meta name="twitter:title" content="' . esc_attr($meta['title']) . '">';
    $tags[] = '<meta name="twitter:description" content="' . esc_attr($meta['description']) . '">';
    $tags[] = '<meta name="twitter:image" content="' . esc_url($share['url']) . '">';
    $tags[] = '<meta name="twitter:image:alt" content="' . esc_attr($alt) . '">';
    $graph  = $meta['found'] ? ldrnseo_graph($meta) : ldrn_seo_schema_document($meta);
    $tags[] = '<script type="application/ld+json" id="ldrnseo-schema">' . str_replace('</', '<\/', wp_json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</script>';
    echo implode("\n", $tags) . "\n";
}, 3);

/**
 * What a page shows that only the rendered page knows - its main heading,
 * the questions it answers, its paragraphs - as the last audit read it in a
 * real browser. Empty until the first audit; the reading then falls back to
 * the page's name.
 */
function ldrnseo_facts($route) {
    $all  = get_option('ldrnseo_rendered', array());
    $f    = is_array($all) && isset($all[$route]) ? (array) $all[$route] : array();
    $page = ldrnseo_page($route);
    return array(
        'h1'    => (string) ($f['h1'] ?? ($page['label'] ?: '')),
        'faqs'  => (array) ($f['faqs'] ?? array()),
        'paras' => (array) ($f['paras'] ?? array()),
    );
}

/* -------------------------------------------------------------------------
 * The reading for crawlers that do not run JavaScript
 *
 * The theme serves one empty #root for every address; React draws the page.
 * Link previews, most AI assistants and older bots never run it. This writes
 * the page's heading, its words, the menu and the nursery's details into a
 * <noscript> block at the top of <body>. Browsers that run JavaScript never
 * show it.
 * ---------------------------------------------------------------------- */

add_action('wp_body_open', function () {
    if (is_admin() || ldrnseo_is_canvas() || !function_exists('ldrn_seo_meta_for_request')) {
        return;
    }
    $meta = ldrn_seo_meta_for_request();
    if (empty($meta['found'])) {
        return;
    }
    echo ldrnseo_reading($meta['path'], $meta);
}, 1);

function ldrnseo_reading($route, array $meta) {
    $b     = ldrnseo_business();
    $facts = ldrnseo_facts($route);
    $e     = 'esc_html';
    $link  = function ($r, $label) { return '<a href="' . esc_url(ldrnseo_abs($r)) . '">' . esc_html($label) . '</a>'; };

    $nav = array();
    if (function_exists('ldrn_ws_public_pages')) {
        foreach ((array) ldrn_ws_public_pages() as $p) {
            if (!empty($p['visible']) && !empty($p['menu']) && in_array($p['route'], ldrnseo_routes(), true)) {
                $nav[] = '<li>' . $link($p['route'], $p['label']) . '</li>';
            }
        }
    }
    if (!$nav) {
        foreach (array('/' => 'Home', '/our-nursery' => 'Our Nursery', '/curriculum' => 'Our Curriculum', '/careers' => 'Careers', '/events' => 'Events', '/contact' => 'Contact Us') as $r => $label) {
            $nav[] = '<li>' . $link($r, $label) . '</li>';
        }
    }

    $main = '<h1>' . $e($facts['h1'] ?: $meta['title']) . '</h1>';
    if ($meta['description']) {
        $main .= '<p>' . $e($meta['description']) . '</p>';
    }
    $post = $meta['post'] ?? null;
    if ($post instanceof WP_Post && 'ldrn_setting' !== $post->post_type) {
        $main .= wpautop(esc_html(wp_strip_all_tags($post->post_content)));
    } else {
        foreach (array_slice($facts['paras'], 0, 10) as $para) {
            $main .= '<p>' . $e($para) . '</p>';
        }
    }
    if ('/our-nursery' === $route || '/' === $route) {
        $items = '';
        foreach (ldrnseo_records() as $r => $p) {
            if ('ldrn_setting' === $p->post_type) {
                $items .= '<li>' . $link($r, 'Little Rainbows ' . get_the_title($p)) . '</li>';
            }
        }
        $main .= $items ? '<ul>' . $items . '</ul>' : '';
    }
    if ($facts['faqs']) {
        $main .= '<h2>Questions</h2>';
        foreach ($facts['faqs'] as $f) {
            $main .= '<h3>' . $e($f['question']) . '</h3><p>' . $e($f['answer']) . '</p>';
        }
    }

    $nap = array();
    if ($b['street']) {
        $nap[] = $e(trim($b['street'] . ', ' . $b['locality'] . ($b['postcode'] ? ' ' . $b['postcode'] : ''), ', '));
    }
    foreach (ldrnseo_lines($b['phones']) as $ph) {
        $nap[] = '<a href="tel:' . esc_attr(preg_replace('/[^\d+]/', '', $ph)) . '">' . $e($ph) . '</a>';
    }
    if ($b['email']) {
        $nap[] = '<a href="mailto:' . esc_attr($b['email']) . '">' . $e($b['email']) . '</a>';
    }
    if ($b['ofsted_urn']) {
        $nap[] = 'Ofsted URN ' . $e($b['ofsted_urn']);
    }

    return '<noscript id="ldrnseo-reading"><header><p>' . $link('/', $b['name']) . '</p><nav><ul>' . implode('', $nav) . '</ul></nav></header>'
         . '<main>' . $main . '</main>'
         . '<footer><p>' . implode(' &middot; ', $nap) . '</p></footer></noscript>' . "\n";
}

/** A site path ("/wp-content/...") or an uploaded file's address, made absolute. */
function ldrnseo_abs_asset($src) {
    $src = (string) $src;
    return preg_match('#^https?://#i', $src) ? $src : ldrnseo_base() . '/' . ltrim($src, '/');
}

/**
 * The measuring script and what it may load. Nothing is measured, and no
 * Google tag is fetched, until the visitor allows analytics in a cookie panel
 * on the site (localStorage "ldrn-consent", event "ldrn:consent"). The site
 * has no such panel yet - its privacy page says it sets no cookies - so until
 * one exists this script waits and sends nothing.
 */
function ldrnseo_measure_tag($route) {
    $s = ldrnseo_settings();
    $attrs = array(
        'id'         => 'ldrnseo-measure',
        'src'        => LDRNSEO_URL . 'assets/track.js?v=' . filemtime(LDRNSEO_DIR . 'assets/track.js'),
        'defer'      => true,
        'data-api'   => esc_url_raw(rest_url('ldrnseo/v1/m')),
        'data-route' => $route,
    );
    if (!$s['measure']) {
        $attrs['data-off'] = '1';
    }
    if (preg_match('/^G-[A-Z0-9]{4,}$/', $s['ga4_id'])) {
        $attrs['data-ga4'] = $s['ga4_id'];
    }
    if (preg_match('/^GTM-[A-Z0-9]{4,}$/', $s['gtm_id'])) {
        $attrs['data-gtm'] = $s['gtm_id'];
    }
    if (!empty($attrs['data-off']) && empty($attrs['data-ga4']) && empty($attrs['data-gtm'])) {
        return '';
    }
    $out = '<script';
    foreach ($attrs as $k => $v) {
        $out .= true === $v ? ' ' . $k : ' ' . $k . '="' . esc_attr($v) . '"';
    }
    return $out . '></script>' . "\n";
}

/* -------------------------------------------------------------------------
 * Requests answered before the theme: llms.txt, redirects, the sitemap
 * ---------------------------------------------------------------------- */

add_action('template_redirect', function () {
    if (is_admin()) {
        return;
    }
    $path = ldrnseo_request_path();

    if ('/llms.txt' === $path) {
        if (!ldrnseo_setting('llms')) {
            return;
        }
        status_header(200);
        header('Content-Type: text/plain; charset=UTF-8');
        header('X-Robots-Tag: noindex');
        echo ldrnseo_llms_txt();
        exit;
    }

    ldrnseo_run_redirect($path);
}, -30);

/**
 * /sitemap.xml is ldrn-seo.php's, built from its route table and the ready
 * records. The route table already drops a fixed page hidden on the Pages
 * tab; a nursery, article, event or vacancy hidden there is dropped here,
 * just before ldrn-seo.php would print it.
 */
add_action('template_redirect', function () {
    if (is_admin() || '/sitemap.xml' !== ldrnseo_request_path() || !function_exists('ldrn_seo_sitemap_entries')) {
        return;
    }
    $hidden = array();
    foreach ((array) get_option('ldrnseo_pages', array()) as $route => $o) {
        if (!empty($o['noindex'])) {
            $hidden[ldrnseo_abs($route)] = true;
        }
    }
    if (!$hidden) {
        return;
    }
    status_header(200);
    header('Content-Type: application/xml; charset=UTF-8');
    header('Cache-Control: public, max-age=3600');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
    foreach (ldrn_seo_sitemap_entries() as $entry) {
        if (isset($hidden[$entry['loc']])) {
            continue;
        }
        echo "  <url>\n    <loc>" . esc_xml($entry['loc']) . "</loc>\n";
        if (!empty($entry['lastmod'])) {
            echo '    <lastmod>' . esc_xml($entry['lastmod']) . "</lastmod>\n";
        }
        if (!empty($entry['image'])) {
            echo '    <image:image><image:loc>' . esc_xml($entry['image']) . "</image:loc></image:image>\n";
        }
        echo "  </url>\n";
    }
    echo "</urlset>\n";
    exit;
}, -101);

/** Redirects: exact addresses, or a prefix ending in * that carries the rest along. */
function ldrnseo_run_redirect($path) {
    global $wpdb;
    $table = ldrnseo_table('redirects');
    $raw   = (string) wp_parse_url(isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/', PHP_URL_PATH);
    $raw   = '/' . ltrim(rawurldecode($raw), '/');
    $cands = array_unique(array($path, rtrim($raw, '/') ?: '/', $raw));
    $hit   = null;
    $suppress = $wpdb->suppress_errors(true);
    foreach ($cands as $c) {
        $hit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE source = %s", $c));
        if ($hit) {
            break;
        }
    }
    $tail = '';
    if (!$hit) {
        $rows = $wpdb->get_results("SELECT * FROM $table WHERE source LIKE '%*'");
        foreach ((array) $rows as $r) {
            $prefix = rtrim(substr($r->source, 0, -1), '/');
            if ('' !== $prefix && (0 === strpos($path, $prefix . '/') || $path === $prefix)) {
                $hit  = $r;
                $tail = substr($path, strlen($prefix));
                break;
            }
        }
    }
    $wpdb->suppress_errors($suppress);
    if (!$hit) {
        return;
    }
    $wpdb->query($wpdb->prepare("UPDATE $table SET hits = hits + 1, last_hit = %s WHERE id = %d", current_time('mysql'), $hit->id));
    $code = (int) $hit->code;
    if (410 === $code) {
        status_header(410);
        nocache_headers();
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Robots-Tag: noindex');
        echo '<!doctype html><meta charset="utf-8"><meta name="robots" content="noindex"><title>Gone - Little Rainbows Day Nursery</title>'
           . '<p style="font:16px/1.6 \'Nunito Sans\',system-ui;color:#213472;background:#f4f9fb;margin:0;padding:20vh 24px;text-align:center">This page has been removed. '
           . '<a style="color:#3a5446" href="' . esc_url(home_url('/')) . '">Little Rainbows Day Nursery</a></p>';
        exit;
    }
    $target = $hit->target;
    if ('' !== $tail && '/*' === substr($target, -2)) {
        $target = substr($target, 0, -2) . $tail;
    }
    if (0 === strpos($target, '/')) {
        $target = home_url($target);
    }
    // The query the visitor sent, from the address itself: a server rewrite can add its own
    // parameters to QUERY_STRING, which are not the visitor's.
    $qs = (string) wp_parse_url(isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '', PHP_URL_QUERY);
    if ($qs && false === strpos($target, '?')) {
        $target .= '?' . $qs;
    }
    header('X-Redirect-By: Little Rainbows SEO');
    wp_redirect($target, in_array($code, array(301, 302, 307, 308), true) ? $code : 301);
    exit;
}

/**
 * robots.txt - ldrn-seo.php writes the rules and the sitemap line; this adds
 * the AI-crawler policy and any extra lines from Settings. When WordPress's
 * "discourage search engines" switch is on, its Disallow stands untouched.
 */
add_filter('robots_txt', function ($output, $public) {
    if (!$public) {
        return $output;
    }
    $add   = '';
    $block = ldrnseo_ai_blocked();
    if ($block) {
        // Model-training crawlers only; the assistants that answer a person's
        // question with a link back may still read the site.
        $add .= "\n# Model-training crawlers. Assistants that answer with a link back may read the site.\n";
        foreach ($block as $bot) {
            $add .= 'User-agent: ' . $bot . "\n";
        }
        $add .= "Disallow: /\n";
    }
    $extra = trim((string) ldrnseo_setting('robots_extra'));
    if ('' !== $extra) {
        $add .= "\n" . $extra . "\n";
    }
    if ('' === $add) {
        return $output;
    }
    // Keep the Sitemap line last.
    if (preg_match('/^Sitemap:.*$/mi', $output, $m)) {
        return rtrim(str_replace($m[0], '', $output)) . "\n" . $add . "\n" . $m[0] . "\n";
    }
    return rtrim($output) . "\n" . $add;
}, PHP_INT_MAX, 2);

function ldrnseo_ai_bots() {
    return array(
        // Collect pages to train models. Blocking them does not affect search.
        'training' => array('GPTBot' => 'OpenAI training', 'ClaudeBot' => 'Anthropic training', 'Google-Extended' => 'Gemini training',
                            'Applebot-Extended' => 'Apple Intelligence training', 'CCBot' => 'Common Crawl', 'meta-externalagent' => 'Meta AI training',
                            'Bytespider' => 'ByteDance', 'Amazonbot' => 'Amazon'),
        // Fetch pages to answer a person's question, with a link back.
        'answers'  => array('OAI-SearchBot' => 'ChatGPT search', 'ChatGPT-User' => 'ChatGPT browsing', 'PerplexityBot' => 'Perplexity',
                            'Perplexity-User' => 'Perplexity browsing', 'Claude-SearchBot' => 'Claude search', 'Claude-User' => 'Claude browsing'),
    );
}

/**
 * llms.txt - the nursery in plain Markdown, for AI assistants that read it
 * (llmstxt.org). Built from the pages' own titles and descriptions and the
 * Business tab, so it never says anything the site does not.
 */
function ldrnseo_llms_txt() {
    $b     = ldrnseo_business();
    $pages = ldrnseo_pages_list();
    $out   = '# ' . $b['name'] . "\n\n> " . $b['description'] . "\n\n";
    $line  = array();
    if ($b['street'])     { $line[] = 'Nursery: ' . trim($b['street'] . ', ' . $b['locality'] . ($b['postcode'] ? ' ' . $b['postcode'] : ''), ', ') . ', United Kingdom.'; }
    if ($b['ages'])       { $line[] = 'Ages: ' . $b['ages'] . '.'; }
    if ($b['hours'])      { $line[] = 'Open: ' . ldrnseo_hours_words($b['hours']) . '.'; }
    if ($b['ofsted_urn']) { $line[] = 'Ofsted URN ' . $b['ofsted_urn'] . ($b['ofsted_grade'] ? ', rated ' . $b['ofsted_grade'] . ($b['ofsted_date'] ? ' (' . wp_date('j F Y', strtotime($b['ofsted_date'])) . ')' : '') : '') . '.'; }
    if ($b['email'])      { $line[] = 'Email: ' . $b['email'] . '.'; }
    $phones = ldrnseo_lines($b['phones']);
    if ($phones)          { $line[] = 'Phone: ' . implode(', ', $phones) . '.'; }
    $out .= implode(' ', $line) . "\n\n## Pages\n\n";
    $groups = array();
    foreach ($pages as $route => $p) {
        if ($p['noindex']) {
            continue;
        }
        $w    = ldrnseo_page_words($route);
        $bw   = ldrnseo_built_words($route);
        $desc = $w['description'] ?: $bw['description'];
        $row  = '- [' . wp_strip_all_tags($p['label'] ?: $route) . '](' . rtrim(ldrnseo_public_url(), '/') . ('/' === $route ? '/' : $route) . ')' . ($desc ? ': ' . $desc : '') . "\n";
        if ('page' === $p['kind']) {
            $out .= $row;
        } else {
            $groups[$p['kind']][] = $row;
        }
    }
    $names = array('setting' => 'Our nursery', 'article' => 'Blog', 'event' => 'Events', 'job' => 'Vacancies');
    foreach ($groups as $kind => $rows) {
        $out .= "\n## " . ($names[$kind] ?? ucfirst($kind)) . "\n\n" . implode('', $rows);
    }
    $out .= "\n## Optional\n\n- [Sitemap](" . rtrim(ldrnseo_public_url(), '/') . "/sitemap.xml)\n";
    return $out;
}

/** "Mo-Fr 08:00-18:00" in words: "Monday to Friday, 08:00-18:00". */
function ldrnseo_hours_words($hours) {
    $days = array('Mo' => 'Monday', 'Tu' => 'Tuesday', 'We' => 'Wednesday', 'Th' => 'Thursday', 'Fr' => 'Friday', 'Sa' => 'Saturday', 'Su' => 'Sunday');
    return preg_replace_callback('/\b(Mo|Tu|We|Th|Fr|Sa|Su)(?:-(Mo|Tu|We|Th|Fr|Sa|Su))?\s+/', function ($m) use ($days) {
        return $days[$m[1]] . (!empty($m[2]) ? ' to ' . $days[$m[2]] : '') . ', ';
    }, (string) $hours);
}

/** When an address last changed: a record's own date, or the last edit on this screen. */
function ldrnseo_lastmod($route) {
    $times = array();
    if ($post = ldrnseo_record_for($route)) {
        $times[] = (int) get_post_modified_time('U', true, $post);
    }
    $last = (int) get_option('ldrnseo_changed', 0);
    if ($last) {
        $times[] = $last;
    }
    return $times ? max($times) : 0;
}

// Anything that changes what a page says moves its date on.
foreach (array('ldrnseo_pages', 'ldrnseo_business', 'ldrn_site_settings') as $opt) {
    add_action('update_option_' . $opt, function () {
        update_option('ldrnseo_changed', time(), false);
    });
}

/* -------------------------------------------------------------------------
 * Server-side logs: 404s and crawlers. No person is recorded in either.
 * ---------------------------------------------------------------------- */

/** Known crawlers, by what their user agent says. Order matters (specific first). */
function ldrnseo_bot_list() {
    return array(
        'Googlebot-Image'   => array('/Googlebot-Image/i', 'search', 'googlebot.com|google.com'),
        'Google Inspection' => array('/Google-InspectionTool/i', 'search', 'googlebot.com|google.com'),
        'Googlebot'         => array('/Googlebot/i', 'search', 'googlebot.com|google.com'),
        'Bingbot'           => array('/bingbot/i', 'search', 'search.msn.com'),
        'Applebot'          => array('/Applebot/i', 'search', 'applebot.apple.com'),
        'DuckDuckBot'       => array('/DuckDuckBot/i', 'search', ''),
        'YandexBot'         => array('/YandexBot/i', 'search', 'yandex.ru|yandex.net|yandex.com'),
        'Baiduspider'       => array('/Baiduspider/i', 'search', ''),
        'OAI-SearchBot'     => array('/OAI-SearchBot/i', 'ai', ''),
        'ChatGPT-User'      => array('/ChatGPT-User/i', 'ai', ''),
        'GPTBot'            => array('/GPTBot/i', 'ai', ''),
        'Claude-SearchBot'  => array('/Claude-SearchBot/i', 'ai', ''),
        'Claude-User'       => array('/Claude-User/i', 'ai', ''),
        'ClaudeBot'         => array('/ClaudeBot/i', 'ai', ''),
        'PerplexityBot'     => array('/Perplexity(Bot|-User)/i', 'ai', ''),
        'CCBot'             => array('/CCBot/i', 'ai', ''),
        'Bytespider'        => array('/Bytespider/i', 'ai', ''),
        'Meta AI'           => array('/meta-externalagent/i', 'ai', ''),
        'Amazonbot'         => array('/Amazonbot/i', 'ai', ''),
        'Pinterest'         => array('/Pinterestbot/i', 'preview', ''),
        'Facebook preview'  => array('/facebookexternalhit|facebookcatalog/i', 'preview', ''),
        'WhatsApp preview'  => array('/WhatsApp/i', 'preview', ''),
        'LinkedIn preview'  => array('/LinkedInBot/i', 'preview', ''),
        'X preview'         => array('/Twitterbot/i', 'preview', ''),
        'Slack preview'     => array('/Slackbot/i', 'preview', ''),
        'Telegram preview'  => array('/TelegramBot/i', 'preview', ''),
        'Discord preview'   => array('/Discordbot/i', 'preview', ''),
        'Ahrefs'            => array('/AhrefsBot/i', 'tool', ''),
        'Semrush'           => array('/SemrushBot/i', 'tool', ''),
        'Majestic'          => array('/MJ12bot/i', 'tool', ''),
    );
}

function ldrnseo_which_bot($ua) {
    foreach (ldrnseo_bot_list() as $name => $def) {
        if (preg_match($def[0], $ua)) {
            return $name;
        }
    }
    return '';
}

/** Generic automation that is not a named crawler - never counted as a person. */
function ldrnseo_is_robotic($ua) {
    return '' === trim($ua) || (bool) preg_match('/bot|crawl|spider|slurp|fetch|preview|headless|lighthouse|pagespeed|curl|wget|python|httpclient|java\/|go-http|axios|node-fetch|monitor|uptime|scan/i', $ua);
}

add_action('template_redirect', function () {
    if (is_admin()) {
        return;
    }
    $ua  = isset($_SERVER['HTTP_USER_AGENT']) ? (string) wp_unslash($_SERVER['HTTP_USER_AGENT']) : '';
    $bot = ldrnseo_which_bot($ua);
    if ('' === $bot) {
        return;
    }
    $path = ldrnseo_request_path();
    if (is_robots()) {
        $path = '/robots.txt';
    }
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    // A crawler never reaches the site from this computer or a private network:
    // such a request is a test that borrowed a crawler's name.
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return;
    }
    add_action('shutdown', function () use ($bot, $path, $ip) {
        ldrnseo_log_bot($bot, $path, $ip);
    }, 99);
}, -200);

function ldrnseo_log_bot($bot, $path, $ip) {
    global $wpdb;
    // The answer goes to the crawler first; the bookkeeping happens after.
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    $def      = ldrnseo_bot_list()[$bot] ?? null;
    $verified = 0;
    if ($def && '' !== $def[2] && filter_var($ip, FILTER_VALIDATE_IP)) {
        $verified = ldrnseo_verify_crawler($ip, $def[2]) ? 1 : 0;
    }
    $path = substr($path, 0, 190);
    $now  = current_time('mysql');
    $wpdb->query($wpdb->prepare(
        'INSERT INTO ' . ldrnseo_table('bots') . ' (day, bot, path, hits, verified, last_seen) VALUES (%s, %s, %s, 1, %d, %s)
         ON DUPLICATE KEY UPDATE hits = hits + 1, verified = GREATEST(verified, VALUES(verified)), last_seen = VALUES(last_seen)',
        wp_date('Y-m-d'), $bot, $path, $verified, $now
    ));
}

/**
 * Is this really Google (or Bing, or Apple)? A user agent is only a claim;
 * the reverse-and-forward DNS check is the method each of them documents.
 * Remembered for a day per address.
 */
function ldrnseo_verify_crawler($ip, $domains) {
    $key = 'ldrnseo_dns_' . md5($ip);
    $got = get_transient($key);
    if (false !== $got) {
        return '1' === $got;
    }
    $ok   = false;
    $host = @gethostbyaddr($ip);
    if ($host && $host !== $ip && preg_match('/\.(' . str_replace('.', '\.', $domains) . ')$/i', $host)) {
        $ok = in_array($ip, (array) @gethostbynamel($host), true);
    }
    set_transient($key, $ok ? '1' : '0', DAY_IN_SECONDS);
    return $ok;
}

/** Every address that answered 404 to someone - a broken link to fix, or an old URL to redirect. */
add_filter('status_header', function ($header, $code) {
    if (404 === (int) $code && !is_admin() && !wp_doing_ajax() && !(defined('REST_REQUEST') && REST_REQUEST)) {
        $raw = (string) wp_parse_url(isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/', PHP_URL_PATH);
        $ref = isset($_SERVER['HTTP_REFERER']) ? (string) wp_unslash($_SERVER['HTTP_REFERER']) : '';
        $ua  = isset($_SERVER['HTTP_USER_AGENT']) ? (string) wp_unslash($_SERVER['HTTP_USER_AGENT']) : '';
        // WordPress says 404 for /about before the theme serves it with 200;
        // only the status the response finally carries counts.
        static $queued = false;
        if (!$queued && false === strpos($ua, 'LDRN-SEO-Audit')) {
            $queued = true;
            add_action('shutdown', function () use ($raw, $ref, $ua) {
                if (404 === (int) http_response_code()) {
                    ldrnseo_log_404($raw, $ref, $ua);
                }
            }, 99);
        }
    }
    return $header;
}, 10, 2);

function ldrnseo_log_404($raw, $ref, $ua) {
    global $wpdb;
    $path = substr('/' . ltrim(rawurldecode($raw), '/'), 0, 190);
    if ('' === trim($path, '/') || preg_match('#^/(wp-admin|wp-login|wp-json)#', $path)) {
        return;
    }
    // Keep a referrer only when it is a page (ours or another site's), never a query string.
    $refp = '';
    if ($ref && ($h = wp_parse_url($ref, PHP_URL_HOST))) {
        $refp = substr($h . (string) wp_parse_url($ref, PHP_URL_PATH), 0, 190);
    }
    $table = ldrnseo_table('notfound');
    $now   = current_time('mysql');
    $wpdb->query($wpdb->prepare(
        "INSERT INTO $table (path, hits, first_seen, last_seen, referrer) VALUES (%s, 1, %s, %s, %s)
         ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen = VALUES(last_seen), referrer = IF(VALUES(referrer) = '', referrer, VALUES(referrer))",
        $path, $now, $now, $refp
    ));
    // A scanner can invent endless addresses; keep the 2,000 most recent.
    if (1 === wp_rand(1, 50)) {
        $wpdb->query("DELETE FROM $table WHERE path NOT IN (SELECT path FROM (SELECT path FROM $table ORDER BY last_seen DESC LIMIT 2000) keep)");
    }
}

/** Addresses only an attack scanner asks for. Shown apart, so real broken links stand out. */
function ldrnseo_is_probe($path) {
    return (bool) preg_match('#(\.(php\d?|asp|aspx|jsp|cgi|env|ini|sql|bak|old|log|yml|yaml|git|ds_store)$)|/\.|wp-config|xmlrpc|phpmyadmin|/vendor/|/cgi-bin|/admin\b|/\.well-known/(?!security\.txt)|/wp-(content|includes)/#i', $path);
}
