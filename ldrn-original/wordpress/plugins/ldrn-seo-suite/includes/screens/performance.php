<?php
/**
 * Performance - what search and the site actually brought in.
 *
 *   Google Search    Search Console: clicks, impressions, CTR, position, the
 *                    searches, and the ones within reach of page one
 *   Visitors         first-party, consent-given visits by channel and source,
 *                    and the enquiries each brought
 *   Enquiries        the inbox itself: what people asked for, from where
 *   Core Web Vitals  real visitors' speed, as dials, phone and desktop
 *   Crawlers         Google, Bing and the AI crawlers, verified where possible
 */

defined('ABSPATH') || exit;

function ldrnseo_screen_performance() {
    $days = ldrnseo_days();
    $p    = ldrnseo_period($days);
    echo '<div class="is-toolbar">' . ldrnseo_period_switch('performance', $days)
       . '<span>' . esc_html(wp_date('j M', strtotime($p['from']))) . ' – ' . esc_html(wp_date('j M Y', strtotime($p['to']))) . ' &middot; vs previous ' . $days . ' days</span></div>';
    $s       = ldrnseo_settings();
    $gsc     = $s['gsc_key'] && $s['gsc_property'];
    $visits  = ldrnseo_totals($p['from'], $p['to'])['visits'] > 0;
    $v       = ldrnseo_vitals($p['from'], $p['to']);
    $vitals  = $v['lcp']['n'] + $v['inp']['n'] + $v['cls']['n'] > 0;
    $bots    = (bool) ldrnseo_bot_report($p['from'], $p['to']);
    $enq     = ldrnseo_inbox_breakdown($p['from'], $p['to']);
    $enq     = $enq && $enq['total'] ? $enq : null;
    if (!$gsc && !$visits && !$vitals && !$bots && !$enq) {
        echo '<section class="is-section"><div class="is-waiting">'
           . '<a href="' . esc_url(ldrnseo_url('settings', array('#' => 'gsc'))) . '">' . ldrnseo_brand('Search Console', 26) . '<span><b>Google Search</b><small>' . (ldrnseo_is_local() ? 'connects on the nursery\'s domain' : 'connect Search Console') . '</small></span></a>'
           . '<a href="' . esc_url(ldrnseo_url('settings', array('#' => 'measurement'))) . '">' . ldrnseo_icon('users') . '<span><b>Visitors &amp; Core Web Vitals</b><small>' . ($s['measure'] ? (ldrnseo_is_local() ? 'waits for a cookie panel on the site' : 'no consented visit in this period') : 'measurement is off') . '</small></span></a>'
           . '<a href="' . esc_url(admin_url('admin.php?page=ldrn_submissions')) . '">' . ldrnseo_icon('mail') . '<span><b>Enquiries</b><small>' . ((defined('LDRN_SUBMISSIONS_ENABLED') && LDRN_SUBMISSIONS_ENABLED) ? 'none in this period' : 'forms are switched off - nothing is stored yet') . '</small></span></a>'
           . '<a href="' . esc_url(ldrnseo_url('settings', array('#' => 'indexing'))) . '">' . ldrnseo_icon('bot') . '<span><b>Crawlers</b><small>' . (ldrnseo_is_local() ? 'logged once search engines can reach the site' : 'no crawler visit in this period') . '</small></span></a>'
           . '</div></section>';
        return;
    }
    if ($gsc) {
        ldrnseo_perf_search($days);
    }
    if ($enq) {
        ldrnseo_perf_enquiries($enq, $p);
    }
    if ($visits) {
        ldrnseo_perf_visitors($p);
    }
    if ($vitals || $bots) {
        echo '<div class="' . ($vitals && $bots ? 'is-grid-2' : '') . '">';
        if ($vitals) {
            ldrnseo_perf_vitals($p);
        }
        if ($bots) {
            ldrnseo_perf_crawlers($p);
        }
        echo '</div>';
    }
}

/* ---------------------------------------------------------------- Google */

function ldrnseo_perf_search($days) {
    $s = ldrnseo_settings();
    echo ldrnseo_section('Google Search', '<span>' . ldrnseo_brand('Search Console', 14) . ' Search Console</span>', 'search', 'search');
    if (!$s['gsc_key'] || !$s['gsc_property']) {
        echo '<div class="is-void" style="padding:22px">' . ldrnseo_brand('Search Console', 30) . '<span><b style="color:var(--text);display:block;font-size:14px">Queries, impressions, rankings</b>only Search Console has them</span>'
           . '<a class="is-btn is-btn-primary" style="margin-left:auto" href="' . esc_url(ldrnseo_url('settings', array('#' => 'gsc'))) . '">Connect</a></div></section>';
        return;
    }
    $g = ldrnseo_gsc_overview($days);
    if (is_wp_error($g)) {
        echo '<p class="is-flash is-flash-bad">' . ldrnseo_icon('alert-triangle') . esc_html($g->get_error_message()) . '</p></section>';
        return;
    }
    $n = $g['now'];
    $b = $g['before'];
    $col = function ($k) use ($g) { return array_map(function ($r) use ($k) { return $r[$k]; }, $g['daily']); };
    echo '<div class="is-kpis is-kpis-4" style="margin-bottom:16px">';
    echo ldrnseo_kpi('mouse-pointer-click', 'Clicks', ldrnseo_num($n['clicks']), ldrnseo_delta($b ? ldrnseo_change($n['clicks'], $b['clicks']) : null), '', $col('clicks'));
    echo ldrnseo_kpi('eye', 'Impressions', ldrnseo_num($n['impressions']), ldrnseo_delta($b ? ldrnseo_change($n['impressions'], $b['impressions']) : null), '', $col('impressions'));
    echo ldrnseo_kpi('activity', 'CTR', null === $n['ctr'] ? '–' : number_format_i18n(100 * $n['ctr'], 1) . '<small>%</small>', ldrnseo_delta($b && $b['ctr'] ? (int) round(100 * ($n['ctr'] - $b['ctr']) / $b['ctr']) : null), '', $col('ctr'));
    echo ldrnseo_kpi('trending-up', 'Avg. position', null === $n['position'] ? '–' : number_format_i18n($n['position'], 1), ldrnseo_delta($b && $b['position'] ? (int) round(100 * ($n['position'] - $b['position']) / $b['position']) : null, false));
    echo '</div>';
    $rows = array();
    foreach ($g['daily'] as $r) {
        $rows[] = array('day' => $r['keys'][0], 'clicks' => (int) $r['clicks']);
    }
    if ($rows) {
        echo ldrnseo_line_chart($rows, array(array('key' => 'clicks', 'label' => 'Clicks', 'class' => 'b')), 'Clicks from Google per day');
    }
    $q = ldrnseo_gsc_query($g['start'], $g['end'], array('query', 'page'), 1000);
    if (is_wp_error($q) || !$q) {
        echo ldrnseo_void('search', is_wp_error($q) ? esc_html($q->get_error_message()) : 'No searches in this period yet.') . '</section>';
        return;
    }
    $byq = array();
    foreach ($q as $r) {
        $k = $r['keys'][0];
        $byq[$k] = $byq[$k] ?? array('query' => $k, 'page' => $r['keys'][1], 'clicks' => 0, 'impressions' => 0, 'pw' => 0);
        $byq[$k]['clicks'] += $r['clicks'];
        $byq[$k]['impressions'] += $r['impressions'];
        $byq[$k]['pw'] += $r['position'] * $r['impressions'];
    }
    foreach ($byq as &$r) {
        $r['position'] = $r['impressions'] ? $r['pw'] / $r['impressions'] : 0;
    }
    unset($r);
    $path = function ($url) { return '/' . trim((string) wp_parse_url($url, PHP_URL_PATH), '/'); };
    $top = $byq;
    usort($top, function ($a, $b) { return array($b['clicks'], $b['impressions']) <=> array($a['clicks'], $a['impressions']); });
    $reach = array_filter($byq, function ($r) { return $r['position'] >= 4 && $r['position'] <= 20 && $r['impressions'] >= 5; });
    usort($reach, function ($a, $b) { return $b['impressions'] - $a['impressions']; });
    $table = function ($rows) use ($path) {
        $o = '<table class="is-table"><thead><tr><th>Query</th><th class="num">Clicks</th><th class="num">Impr.</th><th class="num">Pos.</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $pos = $r['position'];
            $o .= '<tr><td>' . esc_html($r['query']) . '<small class="is-under">' . esc_html(ldrnseo_route_name($path($r['page']))) . '</small></td><td class="num">' . (int) $r['clicks'] . '</td><td class="num">' . (int) $r['impressions'] . '</td>'
                . '<td class="num"><span class="is-chip ' . ($pos <= 3 ? 'is-chip-good' : ($pos <= 10 ? 'is-chip-ni' : 'is-chip-na')) . '">' . esc_html(number_format_i18n($pos, 1)) . '</span></td></tr>';
        }
        return $o . '</tbody></table>';
    };
    echo '<div class="is-grid-2" style="margin-top:16px"><div><h3 class="is-sub">' . ldrnseo_icon('search') . 'Top queries</h3>' . $table(array_slice($top, 0, 12)) . '</div>';
    echo '<div><h3 class="is-sub">' . ldrnseo_icon('trending-up') . 'Within reach <small>positions 4–20</small></h3>' . ($reach ? $table(array_slice($reach, 0, 12)) : ldrnseo_void('trending-up', 'None yet.')) . '</div></div>';

    // Every page as Google shows it.
    $gp = ldrnseo_gsc_query($g['start'], $g['end'], array('page'), 100);
    if (!is_wp_error($gp) && $gp) {
        echo '<h3 class="is-sub" style="margin-top:20px">' . ldrnseo_icon('file-text') . 'Pages in Google</h3><table class="is-table"><thead><tr><th>Page</th><th class="num">Clicks</th><th class="num">Impr.</th><th class="num">CTR</th><th class="num">Pos.</th></tr></thead><tbody>';
        foreach (array_slice($gp, 0, 15) as $r) {
            $pp = $path($r['keys'][0]);
            echo '<tr><td><a href="' . esc_url(ldrnseo_url('pages', array('route' => $pp))) . '">' . esc_html(ldrnseo_route_name($pp)) . '</a></td><td class="num">' . (int) $r['clicks'] . '</td><td class="num">' . (int) $r['impressions'] . '</td>'
               . '<td class="num">' . esc_html(number_format_i18n(100 * $r['ctr'], 1)) . '%</td><td class="num">' . esc_html(number_format_i18n($r['position'], 1)) . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    // Where the searches come from, on what, and how high the site ranks for them.
    $countries = ldrnseo_gsc_query($g['start'], $g['end'], array('country'), 250);
    $devices   = ldrnseo_gsc_query($g['start'], $g['end'], array('device'), 10);
    $appear    = ldrnseo_gsc_query($g['start'], $g['end'], array('searchAppearance'), 20);
    $metric    = $n['clicks'] >= 20 ? 'clicks' : 'impressions';
    echo '<div class="is-grid-3" style="margin-top:20px;display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:18px">';
    echo '<div><h3 class="is-sub">' . ldrnseo_icon('globe') . 'Countries <small>by ' . $metric . '</small></h3>';
    if (!is_wp_error($countries) && $countries) {
        usort($countries, function ($a, $b) use ($metric) { return $b[$metric] <=> $a[$metric]; });
        $total = max(1, array_sum(array_column($countries, $metric)));
        $segs = array();
        foreach (array_slice($countries, 0, 5) as $i => $c) {
            $segs[] = array(esc_html(ldrnseo_country_name($c['keys'][0])) . ' <small>' . round(100 * $c[$metric] / $total) . '%</small>', (int) $c[$metric], 'c' . ($i + 1));
        }
        $rest = array_sum(array_column(array_slice($countries, 5), $metric));
        if ($rest) {
            $segs[] = array('Other <small>' . round(100 * $rest / $total) . '%</small>', (int) $rest, 'c6');
        }
        echo ldrnseo_donut($segs, ldrnseo_num($total), $metric);
    } else {
        echo ldrnseo_void('globe', 'No country data yet.');
    }
    echo '</div><div><h3 class="is-sub">' . ldrnseo_icon('smartphone') . 'Devices <small>by ' . $metric . '</small></h3>';
    if (!is_wp_error($devices) && $devices) {
        $names = array('MOBILE' => 'Phone', 'DESKTOP' => 'Desktop', 'TABLET' => 'Tablet');
        $total = max(1, array_sum(array_column($devices, $metric)));
        echo ldrnseo_donut(array_map(function ($d, $i) use ($names, $metric, $total) {
            return array(esc_html($names[$d['keys'][0]] ?? ucfirst(strtolower($d['keys'][0]))) . ' <small>' . round(100 * $d[$metric] / $total) . '%</small>', (int) $d[$metric], 'c' . ($i + 1));
        }, $devices, array_keys($devices)), ldrnseo_num($total), $metric);
    } else {
        echo ldrnseo_void('smartphone', 'No device data yet.');
    }
    // How many of the searches sit where.
    $buckets = array('1–3' => 0, '4–10' => 0, '11–20' => 0, '21–50' => 0, '51+' => 0);
    foreach ($byq as $r) {
        $pos = $r['position'];
        $k = $pos <= 3.5 ? '1–3' : ($pos <= 10.5 ? '4–10' : ($pos <= 20.5 ? '11–20' : ($pos <= 50.5 ? '21–50' : '51+')));
        $buckets[$k]++;
    }
    echo '</div><div><h3 class="is-sub">' . ldrnseo_icon('trending-up') . 'Where the queries rank</h3>'
       . ldrnseo_columns(array_map(function ($k, $v) { return array('label' => $k, 'q' => $v); }, array_keys($buckets), $buckets), array(array('q', 'Queries', 'b')), 'Number of queries by Google position', 170)
       . '</div></div>';
    if (!is_wp_error($appear) && $appear) {
        echo '<h3 class="is-sub" style="margin-top:16px">' . ldrnseo_icon('sparkles') . 'Rich results</h3><div class="is-schema-types">';
        foreach ($appear as $a) {
            echo '<span class="is-chip is-chip-kp">' . esc_html(ucwords(strtolower(str_replace('_', ' ', $a['keys'][0])))) . ' &middot; ' . (int) $a['clicks'] . ' clicks</span>';
        }
        echo '</div>';
    }
    echo '</section>';
}

/* ------------------------------------------------------------- Enquiries */

/** What parents asked in the period - the outcome every page exists to bring. */
function ldrnseo_perf_enquiries(array $e, array $p) {
    $before = ldrnseo_inbox_count($p['pfrom'], $p['pto']);
    echo ldrnseo_section('Enquiries', '<span>' . ldrnseo_delta(ldrnseo_change($e['total'], $before)) . ' <a class="is-link" href="' . esc_url(admin_url('admin.php?page=ldrn_submissions')) . '">Inbox ' . ldrnseo_icon('arrow-up-right') . '</a></span>', 'enquiries', 'mail');
    $bars = function ($rows) {
        $out = array();
        foreach (array_slice($rows, 0, 6, true) as $label => $n) {
            $out[] = array('label' => esc_html($label), 'value' => (int) $n);
        }
        return ldrnseo_bars($out);
    };
    $blocks = array('service' => array('file-text', 'Form'), 'city' => array('map-pin', 'Setting'), 'source' => array('globe', 'Came in through'));
    echo '<div class="is-enq"><div class="is-enq-total"><b>' . (int) $e['total'] . '</b><span>' . (1 === $e['total'] ? 'enquiry' : 'enquiries') . '</span></div><div class="is-enq-grid">';
    foreach ($blocks as $k => $d) {
        if ($e[$k]) {
            echo '<div><h3 class="is-sub">' . ldrnseo_icon($d[0]) . esc_html($d[1]) . '</h3>' . $bars($e[$k]) . '</div>';
        }
    }
    echo '</div></div></section>';
}

/* -------------------------------------------------------------- Visitors */

function ldrnseo_perf_visitors(array $p) {
    $now   = ldrnseo_totals($p['from'], $p['to']);
    $then  = ldrnseo_totals($p['pfrom'], $p['pto']);
    $inbox = ldrnseo_inbox_count($p['from'], $p['to']);
    $daily = ldrnseo_daily($p['from'], $p['to']);
    echo ldrnseo_section('Visitors', '<span>' . ldrnseo_icon('shield-check') . ' consent-only &middot; no cookies</span>', 'visitors', 'users');
    echo '<div class="is-kpis is-kpis-6" style="margin-bottom:16px">';
    echo ldrnseo_kpi('users', 'Visitors', ldrnseo_num($now['visitors']), ldrnseo_delta(ldrnseo_change($now['visitors'], $then['visitors'])), '', array_column($daily, 'visitors'), '', 'Each person counted once per day (no cookies), summed over the period');
    echo ldrnseo_kpi('search', 'From search', ldrnseo_num($now['organic']), ldrnseo_delta(ldrnseo_change($now['organic'], $then['organic'])), '', array_column($daily, 'organic'));
    echo ldrnseo_kpi('file-text', 'Page views', ldrnseo_num($now['views']), ldrnseo_delta(ldrnseo_change($now['views'], $then['views'])), '', array_column($daily, 'views'));
    echo ldrnseo_kpi('timer', 'Avg. time', null === $now['engaged'] ? '–' : esc_html(ldrnseo_duration($now['engaged'])), ldrnseo_delta(ldrnseo_change($now['engaged'], $then['engaged'])));
    echo ldrnseo_kpi('route', 'Single-page visits', null === $now['bounce'] ? '–' : $now['bounce'] . '<small>%</small>', ldrnseo_delta(ldrnseo_change($now['bounce'], $then['bounce']), false));
    echo ldrnseo_kpi('mail', 'Enquiries', ldrnseo_num($now['enquiries']), ldrnseo_delta(ldrnseo_change($now['enquiries'], $then['enquiries'])), (null === $inbox ? '' : $inbox . ' in inbox') . ($now['contacts'] ? ' &middot; ' . $now['contacts'] . ' taps' : ''));
    echo '</div>';

    if (!array_sum(array_column($daily, 'visitors'))) {
        echo ldrnseo_void('activity', ldrnseo_is_local() ? 'Counting starts on the live domain, once a cookie panel lets visitors allow it.' : 'No visits in this period.') . '</section>';
        return;
    }
    echo ldrnseo_line_chart($daily, array(array('key' => 'visitors', 'label' => 'All visitors', 'class' => 'a'), array('key' => 'organic', 'label' => 'From search', 'class' => 'b')), 'Visitors per day');

    $names = ldrnseo_channel_names();
    $chan  = ldrnseo_channels($p['from'], $p['to']);
    $total = array_sum(array_column($chan, 'visits'));
    echo '<div class="is-grid-2" style="margin-top:18px"><div><h3 class="is-sub">' . ldrnseo_icon('radar') . 'Channels</h3>'
       . ldrnseo_donut(array_map(function ($c) use ($names) {
             return array(esc_html($names[$c['channel']] ?? $c['channel']) . ((int) $c['enquiries'] ? ' <small>' . (int) $c['enquiries'] . ' enq.</small>' : ''), (int) $c['visits'], $c['channel']);
         }, $chan), ldrnseo_num($total), 'visits') . '</div>';
    $src = ldrnseo_sources($p['from'], $p['to'], null, 10);
    echo '<div><h3 class="is-sub">' . ldrnseo_icon('globe') . 'Sources</h3>' . ldrnseo_bars(array_map(function ($r) use ($names) {
        return array('mark' => ldrnseo_brand($r['source'], 18), 'label' => esc_html($r['source']) . ' <small>' . esc_html($names[$r['channel']] ?? $r['channel']) . '</small>', 'value' => (int) $r['visits']);
    }, $src), 'Only direct visits so far.') . '</div></div>';

    $pages = ldrnseo_page_stats($p['from'], $p['to']);
    $dev   = ldrnseo_devices($p['from'], $p['to']);
    $dtot  = max(1, array_sum(array_column($dev, 'visitors')));
    echo '<div class="is-grid-21" style="margin-top:18px"><div><h3 class="is-sub">' . ldrnseo_icon('file-text') . 'Pages</h3><table class="is-table"><thead><tr><th>Page</th><th class="num">Views</th><th class="num">Entrances</th><th class="num">Search</th><th class="num">Time</th><th class="num">Enq.</th></tr></thead><tbody>';
    foreach ($pages as $path => $r) {
        echo '<tr><td><a href="' . esc_url(ldrnseo_url('pages', array('route' => $path))) . '">' . esc_html(ldrnseo_route_name($path)) . '</a></td><td class="num">' . (int) $r['views'] . '</td><td class="num">' . (int) $r['landings'] . '</td><td class="num">' . (int) $r['organic'] . '</td>'
           . '<td class="num">' . (null === $r['engaged'] ? '–' : esc_html(ldrnseo_duration((int) round($r['engaged'] / 1000)))) . '</td><td class="num">' . ((int) $r['enquiries'] ? '<span class="is-chip is-chip-good">' . (int) $r['enquiries'] . '</span>' : '0') . '</td></tr>';
    }
    echo '</tbody></table></div><div><h3 class="is-sub">' . ldrnseo_icon('smartphone') . 'Devices</h3>'
       . ldrnseo_donut(array_map(function ($d, $i) use ($dtot) {
             return array(esc_html(ucfirst($d['device'] ?: 'other')) . ' <small>' . round(100 * $d['visitors'] / $dtot) . '%</small>', (int) $d['visitors'], 'c' . ($i + 1));
         }, $dev, array_keys($dev)), ldrnseo_num($dtot), 'visitors') . '</div></div>';
    echo '<h3 class="is-sub" style="margin-top:20px">' . ldrnseo_icon('clock') . 'When people visit <small>' . esc_html(wp_timezone_string()) . '</small></h3>' . ldrnseo_heatmap(ldrnseo_heat($p['from'], $p['to']));

    $camp = ldrnseo_campaigns($p['from'], $p['to']);
    if ($camp) {
        echo '<h3 class="is-sub" style="margin-top:18px">' . ldrnseo_icon('sparkles') . 'Campaigns</h3><table class="is-table"><thead><tr><th>Campaign</th><th class="num">Visits</th><th class="num">Enquiries</th></tr></thead><tbody>';
        foreach ($camp as $c) {
            echo '<tr><td>' . esc_html($c['campaign']) . '<small class="is-under">' . esc_html(trim($c['source'] . ' / ' . $c['medium'], ' /')) . '</small></td><td class="num">' . (int) $c['visits'] . '</td><td class="num">' . (int) $c['enquiries'] . '</td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '</section>';
}

/** ISO 3166 alpha-3 (as Search Console reports countries) to a name, for the countries most likely here. */
function ldrnseo_country_name($code) {
    $names = array('ind' => 'India', 'are' => 'UAE', 'usa' => 'United States', 'gbr' => 'United Kingdom', 'can' => 'Canada', 'aus' => 'Australia',
                   'sgp' => 'Singapore', 'sau' => 'Saudi Arabia', 'qat' => 'Qatar', 'omn' => 'Oman', 'kwt' => 'Kuwait', 'bhr' => 'Bahrain', 'npl' => 'Nepal',
                   'lka' => 'Sri Lanka', 'bgd' => 'Bangladesh', 'pak' => 'Pakistan', 'deu' => 'Germany', 'fra' => 'France', 'nld' => 'Netherlands',
                   'irl' => 'Ireland', 'nzl' => 'New Zealand', 'zaf' => 'South Africa', 'mys' => 'Malaysia', 'idn' => 'Indonesia', 'phl' => 'Philippines',
                   'jpn' => 'Japan', 'chn' => 'China', 'hkg' => 'Hong Kong', 'ita' => 'Italy', 'esp' => 'Spain', 'bra' => 'Brazil', 'zzz' => 'Unknown');
    return $names[strtolower((string) $code)] ?? strtoupper((string) $code);
}

function ldrnseo_duration($s) {
    if (null === $s) {
        return '–';
    }
    return $s >= 60 ? floor($s / 60) . 'm ' . str_pad((string) ($s % 60), 2, '0', STR_PAD_LEFT) . 's' : $s . 's';
}

/* ------------------------------------------------------- Core Web Vitals */

function ldrnseo_perf_vitals(array $p) {
    echo ldrnseo_section('Core Web Vitals', '<span>real visitors &middot; p75</span>', 'vitals', 'gauge');
    echo '<div style="display:grid;gap:12px">';
    foreach (array('mobile' => array('Phone', 'smartphone'), 'desktop' => array('Desktop', 'monitor')) as $dev => $d) {
        $v = ldrnseo_vitals($p['from'], $p['to'], null, $dev);
        echo '<div class="is-vital-dev"><h3 class="is-sub">' . ldrnseo_icon($d[1]) . esc_html($d[0]) . '</h3><div class="is-vgs">'
           . ldrnseo_vital_gauge('lcp', $v['lcp']['p75'], $v['lcp']['n']) . ldrnseo_vital_gauge('inp', $v['inp']['p75'], $v['inp']['n']) . ldrnseo_vital_gauge('cls', $v['cls']['p75'], $v['cls']['n']) . '</div></div>';
    }
    echo '</div></section>';
}

/* -------------------------------------------------------------- Crawlers */

function ldrnseo_perf_crawlers(array $p) {
    $rows = ldrnseo_bot_report($p['from'], $p['to']);
    $defs = ldrnseo_bot_list();
    $kind = array('search' => 'Search', 'ai' => 'AI', 'preview' => 'Link preview', 'tool' => 'SEO tool');
    $marks = array('Googlebot' => 'Google', 'Googlebot-Image' => 'Google', 'Google Inspection' => 'Google', 'Bingbot' => 'Bing', 'Applebot' => 'Apple', 'DuckDuckBot' => 'DuckDuckGo',
                   'YandexBot' => 'Yandex', 'OAI-SearchBot' => 'ChatGPT', 'ChatGPT-User' => 'ChatGPT', 'GPTBot' => 'OpenAI', 'Claude-SearchBot' => 'Claude', 'Claude-User' => 'Claude',
                   'ClaudeBot' => 'Anthropic', 'PerplexityBot' => 'Perplexity', 'Facebook preview' => 'Facebook', 'WhatsApp preview' => 'WhatsApp', 'X preview' => 'X', 'Meta AI' => 'Facebook');
    echo ldrnseo_section('Crawlers', '<span>' . ldrnseo_icon('bot') . ' server log</span>', 'crawlers', 'bot');
    echo '<div class="is-crawl">';
    foreach ($rows as $r) {
        $d = $defs[$r['bot']] ?? array('', '', '');
        echo '<div class="is-crawler"><span class="is-bot-mark">' . ldrnseo_brand($marks[$r['bot']] ?? $r['bot'], 18) . '</span><span><b>' . esc_html($r['bot']) . ($r['verified'] ? ldrnseo_icon('shield-check') : '') . '</b>'
           . '<small>' . esc_html($kind[$d[1]] ?? '') . ' &middot; ' . esc_html(ldrnseo_ago($r['last_seen'])) . ' &middot; ' . (int) $r['pages'] . ' pages</small></span><span class="is-bot-n">' . (int) $r['hits'] . '</span></div>';
    }
    echo '</div></section>';
}
