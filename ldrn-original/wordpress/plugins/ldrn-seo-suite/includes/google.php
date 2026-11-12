<?php
/**
 * Google: Search Console and PageSpeed Insights.
 *
 * Search Console is the only place that knows what people typed before they
 * saw the site, how often it appeared, where it ranked and whether they
 * clicked. It is read with a Google Cloud service account - a robot user the
 * owner adds to the Search Console property as a "Restricted" user - so no
 * one's personal Google login is stored here, and access can be withdrawn in
 * Search Console at any time. The key is kept server-side and never printed
 * back into a page.
 *
 * PageSpeed Insights runs Google's Lighthouse against the public address and
 * returns real-user Chrome data (CrUX) when Google has enough of it. Google's
 * shared keyless quota is exhausted most days, so it takes a free API key.
 */

defined('ABSPATH') || exit;

/* -------------------------------------------------------------------------
 * Search Console
 * ---------------------------------------------------------------------- */

function ldrnseo_gsc_key() {
    $json = (string) ldrnseo_setting('gsc_key');
    $key  = $json ? json_decode($json, true) : null;
    return is_array($key) && !empty($key['client_email']) && !empty($key['private_key']) ? $key : null;
}

/** An OAuth access token for the service account (JWT bearer grant), cached ~55 minutes. */
function ldrnseo_gsc_token() {
    $key = ldrnseo_gsc_key();
    if (!$key) {
        return new WP_Error('ldrnseo_gsc', 'No service-account key saved.');
    }
    $cached = get_transient('ldrnseo_gsc_token');
    if ($cached && is_array($cached) && $cached['email'] === $key['client_email']) {
        return $cached['token'];
    }
    if (!function_exists('openssl_sign')) {
        return new WP_Error('ldrnseo_gsc', 'This server\'s PHP has no OpenSSL, which signing the request needs.');
    }
    $b64 = function ($s) { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); };
    $now = time();
    $jwt = $b64(wp_json_encode(array('alg' => 'RS256', 'typ' => 'JWT'))) . '.' . $b64(wp_json_encode(array(
        'iss' => $key['client_email'], 'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
        'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600,
    )));
    $pk = openssl_pkey_get_private($key['private_key']);
    if (!$pk || !openssl_sign($jwt, $sig, $pk, 'sha256WithRSAEncryption')) {
        return new WP_Error('ldrnseo_gsc', 'The private key in the JSON file could not be read. Download a fresh key.');
    }
    $res = wp_remote_post('https://oauth2.googleapis.com/token', array('timeout' => 20, 'body' => array(
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt . '.' . $b64($sig),
    )));
    if (is_wp_error($res)) {
        return $res;
    }
    $body = json_decode((string) wp_remote_retrieve_body($res), true);
    if (empty($body['access_token'])) {
        return new WP_Error('ldrnseo_gsc', 'Google refused the key: ' . ($body['error_description'] ?? $body['error'] ?? 'no reason given') . '.');
    }
    set_transient('ldrnseo_gsc_token', array('email' => $key['client_email'], 'token' => $body['access_token']), 55 * MINUTE_IN_SECONDS);
    return $body['access_token'];
}

function ldrnseo_gsc_call($method, $path, $body = null) {
    $token = ldrnseo_gsc_token();
    if (is_wp_error($token)) {
        return $token;
    }
    $args = array('method' => $method, 'timeout' => 30, 'headers' => array('Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'));
    if (null !== $body) {
        $args['body'] = wp_json_encode($body);
    }
    $base = 0 === strpos($path, 'https://') ? '' : 'https://www.googleapis.com/webmasters/v3';
    $res  = wp_remote_request($base . $path, $args);
    if (is_wp_error($res)) {
        return $res;
    }
    $data = json_decode((string) wp_remote_retrieve_body($res), true);
    $code = (int) wp_remote_retrieve_response_code($res);
    if ($code >= 400) {
        $msg = $data['error']['message'] ?? ('HTTP ' . $code);
        if (403 === $code) {
            $msg .= ' - add ' . (ldrnseo_gsc_key()['client_email'] ?? 'the service account') . ' as a user of this property in Search Console (Settings > Users and permissions).';
        }
        return new WP_Error('ldrnseo_gsc', $msg);
    }
    return is_array($data) ? $data : array();
}

/** The properties the service account can read. */
function ldrnseo_gsc_sites() {
    $r = ldrnseo_gsc_call('GET', '/sites');
    if (is_wp_error($r)) {
        return $r;
    }
    return array_values(array_map(function ($s) { return array('url' => $s['siteUrl'], 'level' => $s['permissionLevel']); }, (array) ($r['siteEntry'] ?? array())));
}

/**
 * Search Analytics, cached for six hours (Search Console data lags 2-3 days
 * anyway, and the API has a daily quota).
 */
function ldrnseo_gsc_query($start, $end, array $dimensions, $limit = 250, $filters = array()) {
    $prop = (string) ldrnseo_setting('gsc_property');
    if ('' === $prop) {
        return new WP_Error('ldrnseo_gsc', 'Choose a Search Console property in Settings.');
    }
    $body = array('startDate' => $start, 'endDate' => $end, 'dimensions' => $dimensions, 'rowLimit' => $limit, 'dataState' => 'all');
    if ($filters) {
        $body['dimensionFilterGroups'] = array(array('filters' => $filters));
    }
    $key = 'ldrnseo_gsc_' . md5($prop . wp_json_encode($body));
    $hit = get_transient($key);
    if (is_array($hit)) {
        return $hit;
    }
    $r = ldrnseo_gsc_call('POST', '/sites/' . rawurlencode($prop) . '/searchAnalytics/query', $body);
    if (is_wp_error($r)) {
        return $r;
    }
    $rows = (array) ($r['rows'] ?? array());
    set_transient($key, $rows, 6 * HOUR_IN_SECONDS);
    return $rows;
}

/** Totals and the day-by-day line for a period, plus the period before. */
function ldrnseo_gsc_overview($days) {
    $end   = wp_date('Y-m-d', time() - 2 * DAY_IN_SECONDS);   // the last day Google has finished
    $start = wp_date('Y-m-d', strtotime($end . ' -' . ($days - 1) . ' days'));
    $pend  = wp_date('Y-m-d', strtotime($start . ' -1 day'));
    $pstart = wp_date('Y-m-d', strtotime($pend . ' -' . ($days - 1) . ' days'));
    $daily = ldrnseo_gsc_query($start, $end, array('date'), 500);
    if (is_wp_error($daily)) {
        return $daily;
    }
    $prev = ldrnseo_gsc_query($pstart, $pend, array('date'), 500);
    $sum = function ($rows) {
        $t = array('clicks' => 0, 'impressions' => 0, 'pos_w' => 0);
        foreach ((array) $rows as $r) {
            $t['clicks'] += $r['clicks'];
            $t['impressions'] += $r['impressions'];
            $t['pos_w'] += $r['position'] * $r['impressions'];
        }
        $t['ctr'] = $t['impressions'] ? $t['clicks'] / $t['impressions'] : null;
        $t['position'] = $t['impressions'] ? $t['pos_w'] / $t['impressions'] : null;
        return $t;
    };
    return array('start' => $start, 'end' => $end, 'daily' => $daily, 'now' => $sum($daily), 'before' => is_wp_error($prev) ? null : $sum($prev));
}

/**
 * Is this page in Google's index? URL Inspection API (read-only scope),
 * cached for 12 hours per address - Google allows 2,000 inspections a day.
 * Returns verdict, coverage state, last crawl, Google's chosen canonical.
 */
function ldrnseo_gsc_inspect($route) {
    $prop = (string) ldrnseo_setting('gsc_property');
    if (!ldrnseo_gsc_key() || '' === $prop) {
        return null;
    }
    $url = ldrnseo_abs($route);
    $key = 'ldrnseo_insp_' . md5($prop . $url);
    $hit = get_transient($key);
    if (is_array($hit)) {
        return $hit;
    }
    $r = ldrnseo_gsc_call('POST', 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect', array('inspectionUrl' => $url, 'siteUrl' => $prop));
    if (is_wp_error($r)) {
        return $r;
    }
    $i = $r['inspectionResult']['indexStatusResult'] ?? array();
    $out = array(
        'verdict'   => (string) ($i['verdict'] ?? ''),
        'coverage'  => (string) ($i['coverageState'] ?? ''),
        'crawled'   => (string) ($i['lastCrawlTime'] ?? ''),
        'canonical' => (string) ($i['googleCanonical'] ?? ''),
        'robots'    => (string) ($i['robotsTxtState'] ?? ''),
    );
    set_transient($key, $out, 12 * HOUR_IN_SECONDS);
    return $out;
}
