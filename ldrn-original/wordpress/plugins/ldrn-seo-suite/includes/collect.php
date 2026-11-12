<?php
/**
 * First-party measurement - receiving it, and reading it back as reports.
 *
 * Privacy, the way the site's privacy page promises it:
 *   - nothing arrives unless the visitor allowed Analytics (track.js waits);
 *   - no cookie; no IP address or user agent is stored;
 *   - a visitor is a hash of (today's random salt + address + browser), and
 *     the salt is thrown away every night, so yesterday's visitors can never
 *     be recognised or linked to today's;
 *   - records older than 13 months are deleted.
 *
 * What it can answer that matters to a business: how many parents came, from
 * where (Google, ChatGPT, the Ofsted report ...), which page they landed on, how long
 * they stayed, how fast the page really was for them - and which visits ended
 * in an enquiry or a tap on the phone number.
 */

defined('ABSPATH') || exit;

add_action('rest_api_init', function () {
    register_rest_route('ldrnseo/v1', '/m', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'ldrnseo_receive',
    ));
});

function ldrnseo_receive(WP_REST_Request $req) {
    $none = new WP_REST_Response(null, 204);
    if (!ldrnseo_setting('measure')) {
        return $none;
    }
    $raw = $req->get_body();
    if (strlen($raw) > 4000) {
        return $none;
    }
    $d = json_decode($raw, true);
    if (!is_array($d) || empty($d['t']) || empty($d['id']) || !is_string($d['id']) || !preg_match('/^[a-z0-9]{6,16}$/', $d['id'])) {
        return $none;
    }
    $ua = (string) $req->get_header('user_agent');
    if (ldrnseo_is_robotic($ua) || '' !== ldrnseo_which_bot($ua) || is_user_logged_in()) {
        return $none;
    }
    // Only this site's own pages send beacons.
    $origin = (string) ($req->get_header('origin') ?: $req->get_header('referer'));
    if ($origin && wp_parse_url($origin, PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST)) {
        return $none;
    }

    global $wpdb;
    $table = ldrnseo_table('hits');
    $pv    = $d['id'];

    if ('pv' === $d['t']) {
        $path = strtolower('/' . trim((string) wp_parse_url((string) ($d['p'] ?? '/'), PHP_URL_PATH), '/'));
        if (!in_array($path, ldrnseo_routes(), true)) {
            return $none;
        }
        $visitor = ldrnseo_visitor_id($ua);
        // A burst from one visitor (a reload loop, a script) is not traffic.
        $key = 'ldrnseo_rl_' . $visitor;
        $n   = (int) get_transient($key);
        if ($n > 60) {
            return $none;
        }
        set_transient($key, $n + 1, MINUTE_IN_SECONDS);

        parse_str(ltrim((string) ($d['q'] ?? ''), '?'), $q);
        $ref  = (string) ($d['r'] ?? '');
        $src  = ldrnseo_classify($ref, $q);
        $w    = (int) ($d['w'] ?? 0);
        $device = preg_match('/iPad|Tablet/i', $ua) || ($w >= 600 && $w < 1024 && preg_match('/Mobile|Android/i', $ua)) ? 'tablet'
                : (preg_match('/Mobi|Android|iPhone/i', $ua) || ($w && $w < 600) ? 'mobile' : 'desktop');

        $wpdb->insert($table, array(
            'day' => wp_date('Y-m-d'), 'ts' => current_time('mysql'), 'pv' => $pv, 'visitor' => $visitor,
            'path' => substr($path, 0, 190), 'entry' => $src['internal'] ? 0 : 1,
            'channel' => $src['channel'], 'source' => substr($src['source'], 0, 40), 'ref_host' => substr($src['host'], 0, 120),
            'utm_source' => substr(sanitize_text_field((string) ($q['utm_source'] ?? '')), 0, 60),
            'utm_medium' => substr(sanitize_text_field((string) ($q['utm_medium'] ?? '')), 0, 60),
            'utm_campaign' => substr(sanitize_text_field((string) ($q['utm_campaign'] ?? '')), 0, 60),
            'device' => $device,
        ));
        return $none;
    }

    $since = wp_date('Y-m-d', time() - DAY_IN_SECONDS);

    if ('end' === $d['t']) {
        $num = function ($k, $max) use ($d) {
            return isset($d[$k]) && is_numeric($d[$k]) && $d[$k] >= 0 && $d[$k] <= $max ? (int) $d[$k] : null;
        };
        $set = array('engaged = GREATEST(COALESCE(engaged, 0), %d)');
        $args = array(min(6 * HOUR_IN_SECONDS * 1000, (int) ($num('e', PHP_INT_MAX) ?? 0)));
        foreach (array('lcp' => 120000, 'inp' => 60000, 'cls' => 100000, 'fcp' => 120000, 'ttfb' => 120000) as $k => $max) {
            $v = $num($k, $max);
            if (null !== $v) {
                $set[]  = "$k = %d";
                $args[] = $v;
            }
        }
        $args[] = $pv;
        $args[] = $since;
        $wpdb->query($wpdb->prepare("UPDATE $table SET " . implode(', ', $set) . ' WHERE pv = %s AND day >= %s', $args));
        return $none;
    }

    if ('lead' === $d['t']) {
        $k = (string) ($d['k'] ?? '');
        if ('enquiry' === $k) {
            $wpdb->query($wpdb->prepare("UPDATE $table SET enquiry = 1 WHERE pv = %s AND day >= %s", $pv, $since));
        } elseif (in_array($k, array('call', 'email', 'whatsapp', 'map'), true)) {
            $wpdb->query($wpdb->prepare("UPDATE $table SET contact = %s WHERE pv = %s AND day >= %s AND contact = ''", $k, $pv, $since));
        }
    }
    return $none;
}

/** Today's visitor id: today's salt + address + browser, hashed. The salt dies tonight. */
function ldrnseo_visitor_id($ua) {
    $today = wp_date('Y-m-d');
    $salts = (array) get_option('ldrnseo_salt', array());
    if (empty($salts[$today])) {
        $salts = array($today => wp_generate_password(32, true, true));
        update_option('ldrnseo_salt', $salts, false);
    }
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return substr(hash('sha256', $salts[$today] . '|' . $ip . '|' . $ua . '|' . home_url()), 0, 16);
}

/* -------------------------------------------------------------------------
 * Where a visit came from
 * ---------------------------------------------------------------------- */

function ldrnseo_source_map() {
    return array(
        // host pattern => [channel, name]. AI before Google: gemini.google.com is not Google search.
        '/(^|\.)(chatgpt\.com|chat\.openai\.com)$/'        => array('ai', 'ChatGPT'),
        '/(^|\.)perplexity\.ai$/'                          => array('ai', 'Perplexity'),
        '/(^|\.)(gemini|bard)\.google\.com$/'              => array('ai', 'Gemini'),
        '/(^|\.)copilot\.microsoft\.com$/'                 => array('ai', 'Copilot'),
        '/(^|\.)claude\.ai$/'                              => array('ai', 'Claude'),
        '/(^|\.)(meta\.ai)$/'                              => array('ai', 'Meta AI'),
        '/(^|\.)(chat\.deepseek\.com)$/'                   => array('ai', 'DeepSeek'),
        '/(^|\.)(grok\.com)$/'                             => array('ai', 'Grok'),
        '/(^|\.)you\.com$/'                                => array('ai', 'You.com'),
        '/(^|\.)google\.[a-z.]+$|googlequicksearchbox/'    => array('organic', 'Google'),
        '/(^|\.)bing\.com$/'                               => array('organic', 'Bing'),
        '/(^|\.)duckduckgo\.com$/'                         => array('organic', 'DuckDuckGo'),
        '/(^|\.)search\.yahoo\.[a-z.]+$|(^|\.)yahoo\.[a-z.]+$/' => array('organic', 'Yahoo'),
        '/(^|\.)yandex\.[a-z.]+$/'                         => array('organic', 'Yandex'),
        '/(^|\.)baidu\.com$/'                              => array('organic', 'Baidu'),
        '/(^|\.)ecosia\.org$/'                             => array('organic', 'Ecosia'),
        '/(^|\.)search\.brave\.com$/'                      => array('organic', 'Brave'),
        '/(^|\.)(startpage\.com|qwant\.com|naver\.com)$/'  => array('organic', 'Other search'),
        '/(^|\.)(instagram\.com)$/'                        => array('social', 'Instagram'),
        '/(^|\.)(facebook\.com|fb\.me|messenger\.com)$/'   => array('social', 'Facebook'),
        '/(^|\.)(linkedin\.com|lnkd\.in)$/'                => array('social', 'LinkedIn'),
        '/(^|\.)(t\.co|x\.com|twitter\.com)$/'             => array('social', 'X'),
        '/(^|\.)(youtube\.com|youtu\.be)$/'                => array('social', 'YouTube'),
        '/(^|\.)(whatsapp\.com|wa\.me)$/'                  => array('social', 'WhatsApp'),
        '/(^|\.)pinterest\.[a-z.]+$|pin\.it$/'             => array('social', 'Pinterest'),
        '/(^|\.)(reddit\.com)$/'                           => array('social', 'Reddit'),
        '/(^|\.)(threads\.net|threads\.com)$/'             => array('social', 'Threads'),
        '/(^|\.)(quora\.com)$/'                            => array('social', 'Quora'),
        '/(^|\.)(daynurseries\.co\.uk)$/'                 => array('referral', 'daynurseries.co.uk'),
        '/(^|\.)(childcare\.co\.uk)$/'                    => array('referral', 'Childcare.co.uk'),
        '/(^|\.)(ofsted\.gov\.uk)$/'                      => array('referral', 'Ofsted'),
        '/(^|\.)(hounslow\.gov\.uk)$/'                    => array('referral', 'Hounslow Council'),
        '/(^|\.)(locrating\.com)$/'                        => array('referral', 'Locrating'),
        '/(^|\.)(goodcareguide\.co\.uk)$/'                => array('referral', 'Good Care Guide'),
        '/(^|\.)(mumsnet\.com)$/'                          => array('social', 'Mumsnet'),
        '/(^|\.)(indeed\.com|indeed\.co\.uk)$/'          => array('referral', 'Indeed'),
        '/(^|\.)(alexandramontessori\.co\.uk)$/'          => array('referral', 'Alexandra Montessori'),
    );
}

/**
 * The channel, the named source and the referring host of one page view.
 * Campaign tags win over the referrer (that is what they are for).
 */
function ldrnseo_classify($ref, array $q) {
    $own  = (string) wp_parse_url(home_url(), PHP_URL_HOST);
    $host = $ref ? strtolower((string) wp_parse_url($ref, PHP_URL_HOST)) : '';
    if (0 === strpos($ref, 'android-app://')) {
        $host = strtolower(substr($ref, 14));
    }
    $out  = array('channel' => 'direct', 'source' => '', 'host' => preg_replace('/^www\./', '', $host), 'internal' => false);
    if ($host && $host === $own) {
        $out['internal'] = true;
        $out['channel']  = 'internal';
        return $out;
    }
    $um = strtolower((string) ($q['utm_medium'] ?? ''));
    $us = strtolower((string) ($q['utm_source'] ?? ''));
    if (!empty($q['gclid']) || !empty($q['gbraid']) || !empty($q['wbraid']) || !empty($q['msclkid']) || preg_match('/^(cpc|ppc|paid|paidsearch|paid_social|paidsocial|display|ads?)$/', $um)) {
        return array_merge($out, array('channel' => 'paid', 'source' => $us ?: (!empty($q['msclkid']) ? 'Microsoft Ads' : 'Google Ads')));
    }
    if (preg_match('/^(e-?mail|newsletter)$/', $um)) {
        return array_merge($out, array('channel' => 'email', 'source' => $us ?: 'Email'));
    }
    foreach (ldrnseo_source_map() as $re => $def) {
        if (($host && preg_match($re, $host)) || ($us && preg_match($re, $us))) {
            return array_merge($out, array('channel' => $def[0], 'source' => $def[1]));
        }
    }
    if ('social' === $um) {
        return array_merge($out, array('channel' => 'social', 'source' => $us ?: 'Social'));
    }
    if ($host) {
        return array_merge($out, array('channel' => 'referral', 'source' => $out['host']));
    }
    if ($us) {
        return array_merge($out, array('channel' => 'referral', 'source' => $us));
    }
    return $out;
}

function ldrnseo_channel_names() {
    return array('organic' => 'Organic search', 'ai' => 'AI assistants', 'social' => 'Social', 'referral' => 'Other websites',
                 'direct' => 'Direct', 'email' => 'Email', 'paid' => 'Paid ads');
}

/* -------------------------------------------------------------------------
 * Reports
 * ---------------------------------------------------------------------- */

/** The period [from, to] ending today, and the one before it. */
function ldrnseo_period($days) {
    $days = max(1, (int) $days);
    $to   = wp_date('Y-m-d');
    $from = wp_date('Y-m-d', strtotime($to . ' -' . ($days - 1) . ' days'));
    $pto  = wp_date('Y-m-d', strtotime($from . ' -1 day'));
    $pfrom = wp_date('Y-m-d', strtotime($pto . ' -' . ($days - 1) . ' days'));
    return array('from' => $from, 'to' => $to, 'pfrom' => $pfrom, 'pto' => $pto, 'days' => $days);
}

function ldrnseo_totals($from, $to) {
    global $wpdb;
    $t = ldrnseo_table('hits');
    $r = $wpdb->get_row($wpdb->prepare(
        "SELECT COUNT(*) views, SUM(entry) visits, SUM(entry AND channel = 'organic') organic, SUM(entry AND channel = 'ai') ai,
                SUM(enquiry) enquiries, SUM(contact <> '') contacts, AVG(engaged) engaged
         FROM $t WHERE day BETWEEN %s AND %s", $from, $to), ARRAY_A);
    $visitors = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM (SELECT day, visitor FROM $t WHERE day BETWEEN %s AND %s GROUP BY day, visitor) x", $from, $to));
    $single = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM (SELECT day, visitor FROM $t WHERE day BETWEEN %s AND %s GROUP BY day, visitor HAVING COUNT(*) = 1) x", $from, $to));
    return array(
        'views'     => (int) $r['views'],
        'visits'    => (int) $r['visits'],
        'visitors'  => $visitors,
        'organic'   => (int) $r['organic'],
        'ai'        => (int) $r['ai'],
        'enquiries' => (int) $r['enquiries'],
        'contacts'  => (int) $r['contacts'],
        'engaged'   => null === $r['engaged'] ? null : (int) round($r['engaged'] / 1000),
        'bounce'    => $visitors ? round(100 * $single / $visitors) : null,
    );
}

/** One row per day: visitors and organic visitors. */
function ldrnseo_daily($from, $to) {
    global $wpdb;
    $t = ldrnseo_table('hits');
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT day, COUNT(DISTINCT visitor) visitors, COUNT(DISTINCT IF(channel = 'organic' AND entry = 1, visitor, NULL)) organic, COUNT(*) views
         FROM $t WHERE day BETWEEN %s AND %s GROUP BY day", $from, $to), ARRAY_A);
    $by = array();
    foreach ($rows as $r) {
        $by[$r['day']] = $r;
    }
    $out = array();
    for ($d = strtotime($from); $d <= strtotime($to); $d += DAY_IN_SECONDS) {
        $k = gmdate('Y-m-d', $d);
        $out[] = array('day' => $k, 'visitors' => (int) ($by[$k]['visitors'] ?? 0), 'organic' => (int) ($by[$k]['organic'] ?? 0), 'views' => (int) ($by[$k]['views'] ?? 0));
    }
    return $out;
}

/** Visits (entries) by channel, with the enquiries each brought. */
function ldrnseo_channels($from, $to) {
    global $wpdb;
    $t = ldrnseo_table('hits');
    // An enquiry belongs to the visit's channel: the entry of that visitor that day.
    return $wpdb->get_results($wpdb->prepare(
        "SELECT e.channel, COUNT(*) visits,
                SUM((SELECT MAX(h.enquiry) FROM $t h WHERE h.day = e.day AND h.visitor = e.visitor AND h.ts >= e.ts)) enquiries,
                SUM((SELECT MAX(h.contact <> '') FROM $t h WHERE h.day = e.day AND h.visitor = e.visitor AND h.ts >= e.ts)) contacts
         FROM $t e WHERE e.entry = 1 AND e.day BETWEEN %s AND %s GROUP BY e.channel ORDER BY visits DESC", $from, $to), ARRAY_A);
}

function ldrnseo_sources($from, $to, $channel = null, $limit = 10) {
    global $wpdb;
    $t = ldrnseo_table('hits');
    $where = $channel ? $wpdb->prepare(' AND channel = %s', $channel) : " AND channel NOT IN ('direct', 'internal')";
    return $wpdb->get_results($wpdb->prepare(
        "SELECT source, channel, COUNT(*) visits FROM $t WHERE entry = 1 AND source <> '' AND day BETWEEN %s AND %s $where
         GROUP BY source, channel ORDER BY visits DESC LIMIT %d", $from, $to, $limit), ARRAY_A);
}

/** Every page: views, visitors, landings, landings from search, time in view, enquiries. */
function ldrnseo_page_stats($from, $to) {
    global $wpdb;
    $t = ldrnseo_table('hits');
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT path, COUNT(*) views, COUNT(DISTINCT day, visitor) visitors, SUM(entry) landings,
                SUM(entry AND channel = 'organic') organic, AVG(engaged) engaged, SUM(enquiry) enquiries
         FROM $t WHERE day BETWEEN %s AND %s GROUP BY path ORDER BY views DESC", $from, $to), ARRAY_A);
    $out = array();
    foreach ($rows as $r) {
        $out[$r['path']] = $r;
    }
    return $out;
}

function ldrnseo_devices($from, $to) {
    global $wpdb;
    $t = ldrnseo_table('hits');
    return $wpdb->get_results($wpdb->prepare(
        "SELECT device, COUNT(DISTINCT day, visitor) visitors FROM $t WHERE day BETWEEN %s AND %s GROUP BY device ORDER BY visitors DESC", $from, $to), ARRAY_A);
}

/** Campaigns run with utm tags (posts, ads, emails, a leaflet's QR code). */
function ldrnseo_campaigns($from, $to) {
    global $wpdb;
    $t = ldrnseo_table('hits');
    return $wpdb->get_results($wpdb->prepare(
        "SELECT utm_campaign campaign, utm_source source, utm_medium medium, COUNT(*) visits, SUM(enquiry) enquiries
         FROM $t WHERE entry = 1 AND utm_campaign <> '' AND day BETWEEN %s AND %s GROUP BY utm_campaign, utm_source, utm_medium
         ORDER BY visits DESC LIMIT 20", $from, $to), ARRAY_A);
}

/**
 * Core Web Vitals at the 75th percentile - the figure Google itself judges a
 * page by - from what real visitors' browsers measured.
 */
function ldrnseo_vitals($from, $to, $path = null, $device = null) {
    global $wpdb;
    $t = ldrnseo_table('hits');
    $w = $wpdb->prepare('day BETWEEN %s AND %s', $from, $to);
    if ($path)   { $w .= $wpdb->prepare(' AND path = %s', $path); }
    if ($device) { $w .= $wpdb->prepare(' AND device = %s', $device); }
    $out = array();
    foreach (array('lcp', 'inp', 'cls', 'fcp', 'ttfb') as $m) {
        $vals = array_map('intval', $wpdb->get_col("SELECT $m FROM $t WHERE $w AND $m IS NOT NULL ORDER BY $m"));
        $n = count($vals);
        $out[$m] = array('n' => $n, 'p75' => $n ? $vals[(int) min($n - 1, ceil(0.75 * $n) - 1)] : null);
    }
    return $out;
}

/** Google's thresholds: good / needs improvement / poor. CLS is stored x1000. */
function ldrnseo_vital_rating($metric, $value) {
    if (null === $value) {
        return '';
    }
    $limits = array('lcp' => array(2500, 4000), 'inp' => array(200, 500), 'cls' => array(100, 250), 'fcp' => array(1800, 3000), 'ttfb' => array(800, 1800));
    if (!isset($limits[$metric])) {
        return '';
    }
    return $value <= $limits[$metric][0] ? 'good' : ($value <= $limits[$metric][1] ? 'ni' : 'poor');
}

function ldrnseo_vital_format($metric, $value) {
    if (null === $value) {
        return '-';
    }
    if ('cls' === $metric) {
        return number_format($value / 1000, 2);
    }
    return $value >= 1000 ? number_format($value / 1000, 1) . ' s' : $value . ' ms';
}

/** Crawlers in the period: who, how often, when last, how many pages, verified or not. */
function ldrnseo_bot_report($from, $to) {
    global $wpdb;
    $t = ldrnseo_table('bots');
    return $wpdb->get_results($wpdb->prepare(
        "SELECT bot, SUM(hits) hits, COUNT(DISTINCT path) pages, MAX(last_seen) last_seen, MAX(verified) verified
         FROM $t WHERE day BETWEEN %s AND %s GROUP BY bot ORDER BY hits DESC", $from, $to), ARRAY_A);
}

/** When each crawler group last fetched each page (any time on record). */
function ldrnseo_last_crawl() {
    global $wpdb;
    $t = ldrnseo_table('bots');
    // Search crawlers can be proven (reverse DNS); only proven visits count as theirs.
    $rows = $wpdb->get_results("SELECT path, bot, MAX(last_seen) last_seen FROM $t WHERE verified = 1 OR bot NOT IN ('Googlebot', 'Googlebot-Image', 'Google Inspection', 'Bingbot', 'Applebot', 'YandexBot') GROUP BY path, bot", ARRAY_A);
    $out = array();
    foreach ($rows as $r) {
        $out[$r['path']][$r['bot']] = $r['last_seen'];
    }
    return $out;
}

/**
 * The submissions table of the operations plugin (every form on the site
 * lands there once submissions are switched on), or ''.
 */
function ldrnseo_inbox_table() {
    global $wpdb;
    if (!class_exists('LDRN_Ops_Tables')) {
        return '';
    }
    $t = LDRN_Ops_Tables::all()[LDRN_Ops_Tables::SUBMISSIONS] ?? '';
    if ('' === $t || $t !== $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t))) {
        return '';
    }
    return $t;
}

/** Enquiries the inbox received in the period - the server's own count, consent or not. Spam is left out. */
function ldrnseo_inbox_count($from, $to) {
    global $wpdb;
    $t = ldrnseo_inbox_table();
    if ('' === $t) {
        return null;
    }
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $t WHERE spldrn_state NOT IN ('suspected', 'quarantined') AND submitted_at BETWEEN %s AND %s",
        $from . ' 00:00:00', $to . ' 23:59:59'));
}

/**
 * What the enquiries in the period were, read from the inbox itself
 * (server-side, so every enquiry counts, consent or not): the form, the
 * setting asked about, and where it came from.
 */
function ldrnseo_inbox_breakdown($from, $to) {
    global $wpdb;
    $t = ldrnseo_inbox_table();
    if ('' === $t) {
        return null;
    }
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT type, branch_label, source FROM $t WHERE spldrn_state NOT IN ('suspected', 'quarantined') AND submitted_at BETWEEN %s AND %s LIMIT 2000",
        $from . ' 00:00:00', $to . ' 23:59:59'), ARRAY_A);
    $names = array('enquiry' => 'Enquiry', 'availability' => 'Check availability', 'career' => 'Job application', 'careers' => 'Job application', 'contact' => 'Contact');
    $out = array('total' => count($rows), 'service' => array(), 'type' => array(), 'city' => array(), 'source' => array());
    foreach ($rows as $r) {
        $form = $names[$r['type']] ?? ucfirst(str_replace('_', ' ', (string) $r['type']));
        $out['service'][$form] = ($out['service'][$form] ?? 0) + 1;
        if ('' !== trim((string) $r['branch_label'])) {
            $out['city'][$r['branch_label']] = ($out['city'][$r['branch_label']] ?? 0) + 1;
        }
        if ('' !== trim((string) $r['source'])) {
            $out['source'][$r['source']] = ($out['source'][$r['source']] ?? 0) + 1;
        }
    }
    foreach (array('service', 'city', 'source') as $k) {
        arsort($out[$k]);
    }
    return $out;
}

function ldrnseo_change($now, $before) {
    if (null === $now || null === $before) {
        return null;
    }
    if (0 == $before) {
        return $now > 0 ? 'new' : null;
    }
    return (int) round(100 * ($now - $before) / $before);
}

/** Visits by weekday (Mon=0) and hour, in the site's time zone (ts is stored in local time). */
function ldrnseo_heat($from, $to) {
    global $wpdb;
    $t = ldrnseo_table('hits');
    $grid = array_fill(0, 7, array_fill(0, 24, 0));
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT WEEKDAY(ts) d, HOUR(ts) h, COUNT(*) n FROM $t WHERE entry = 1 AND day BETWEEN %s AND %s GROUP BY d, h", $from, $to), ARRAY_A);
    foreach ($rows as $r) {
        $grid[(int) $r['d']][(int) $r['h']] = (int) $r['n'];
    }
    return $grid;
}
