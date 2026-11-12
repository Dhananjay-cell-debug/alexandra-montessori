<?php
/**
 * Speed - PageSpeed Insights for every page, the whole site first.
 *
 *   Whole site   each category's median, every page against every category,
 *                what fails on many pages (fix once in the theme), Largest
 *                Contentful Paint page by page, phone against desktop
 *   One page     the PageSpeed report: real-user data when Google has it, the
 *                five scores, the screenshot, metrics with their weights, the
 *                filmstrip, insights and diagnostics (filterable by metric),
 *                then for every category what fails, what to check by hand,
 *                what passed and what does not apply - each with its evidence
 *
 * Reports come from includes/lighthouse.php.
 */

defined('ABSPATH') || exit;

/** Lighthouse's metric thresholds (good, poor) per device, for colouring a value. */
function ldrnseo_lh_metric_defs($device = 'mobile') {
    $m = 'mobile' === $device;
    return array(
        'FCP' => array('First Contentful Paint', $m ? 1800 : 934, $m ? 3000 : 1600),
        'LCP' => array('Largest Contentful Paint', $m ? 2500 : 1200, $m ? 4000 : 2400),
        'TBT' => array('Total Blocking Time', $m ? 200 : 150, $m ? 600 : 350),
        'CLS' => array('Cumulative Layout Shift', 0.1, 0.25),
        'SI'  => array('Speed Index', $m ? 3387 : 1311, $m ? 5800 : 2300),
    );
}

function ldrnseo_screen_speed() {
    $routes = ldrnseo_routes();
    $device = isset($_GET['device']) && 'desktop' === $_GET['device'] ? 'desktop' : 'mobile';
    $route  = isset($_GET['route']) ? sanitize_text_field(wp_unslash($_GET['route'])) : '';
    if ('' !== $route && !in_array($route, $routes, true)) {
        $route = '';
    }
    $idx = get_option('ldrnseo_lighthouse');
    $runs = is_array($idx) ? (array) ($idx['runs'] ?? array()) : array();

    // Toolbar.
    $sel = '<select data-nav aria-label="Page"><option value="' . esc_url(ldrnseo_url('speed', array('device' => $device))) . '"' . selected('', $route, false) . '>Whole site</option>';
    foreach ($routes as $r) {
        $sc = $runs[$r][$device]['cats']['performance']['score'] ?? null;
        $sel .= '<option value="' . esc_url(ldrnseo_url('speed', array('route' => $r, 'device' => $device))) . '"' . selected($r, $route, false) . '>' . esc_html(ldrnseo_route_name($r)) . (null === $sc ? ' · not tested' : ' · ' . (int) $sc) . '</option>';
    }
    $sel .= '</select>';
    $args = '' === $route ? array() : array('route' => $route);
    echo '<div class="is-toolbar"><div class="is-seg" role="group" aria-label="Device">'
       . '<a href="' . esc_url(ldrnseo_url('speed', $args + array('device' => 'mobile'))) . '"' . ('mobile' === $device ? ' aria-current="true"' : '') . '>' . ldrnseo_icon('smartphone') . ' Mobile</a>'
       . '<a href="' . esc_url(ldrnseo_url('speed', $args + array('device' => 'desktop'))) . '"' . ('desktop' === $device ? ' aria-current="true"' : '') . '>' . ldrnseo_icon('monitor') . ' Desktop</a></div>'
       . '<div class="is-speed-pick">' . $sel . '</div><span style="margin-left:auto"></span>';
    if (ldrnseo_setting('psi_key')) {
        echo '<button type="button" class="is-btn" data-psi-site="' . esc_attr(wp_json_encode($routes)) . '">' . ldrnseo_icon('layout-grid') . 'Test whole site</button>';
        if ('' !== $route) {
            echo '<button type="button" class="is-btn is-btn-primary" data-psi="' . esc_attr($device) . '" data-route="' . esc_attr($route) . '">' . ldrnseo_icon('gauge') . 'Test this page</button>';
        }
    } else {
        echo '<a class="is-btn" href="' . esc_url(ldrnseo_url('settings', array('#' => 'psi'))) . '">' . ldrnseo_icon('key-round') . 'Add the PageSpeed key to test from Google</a>';
    }
    echo '</div><div class="is-progress" data-psi-progress hidden><div class="is-progress-track"><i></i></div><span></span></div>';

    if ('' === $route) {
        ldrnseo_speed_site($device);
    } else {
        ldrnseo_speed_page($route, $device);
    }
}

/* ------------------------------------------------------------ whole site */

/** Audits a local HTTP copy fails by nature and the HTTPS live domain passes. */
function ldrnseo_lh_https_only($id) {
    return ldrnseo_is_local() && in_array($id, array('is-on-https', 'redirects-http', 'modern-http-insight'), true);
}

function ldrnseo_speed_site($device) {
    $site = ldrnseo_lh_site($device);
    if (!$site) {
        echo '<section class="is-section">' . ldrnseo_void('gauge', 'No page has been tested on ' . ('mobile' === $device ? 'mobile' : 'desktop') . ' yet.') . '</section>';
        return;
    }
    $order = array('performance', 'accessibility', 'best-practices', 'seo', 'agentic-browsing');

    // Medians.
    echo '<section class="is-section"><header class="is-section-head"><h2>' . ldrnseo_icon('globe') . 'Whole site &middot; ' . ('mobile' === $device ? 'mobile' : 'desktop') . '</h2>'
       . '<div class="is-section-aside">' . ldrnseo_lh_source_chip($site['sources']) . '<span>' . count($site['pages']) . ' pages &middot; median &middot; ' . esc_html(ldrnseo_ago((int) ($site['at'] / 1000))) . '</span></div></header>';
    echo '<div class="is-lh-scores is-lh-site">';
    foreach ($order as $cid) {
        if (isset($site['cats'][$cid])) {
            $c = $site['cats'][$cid];
            echo '<div class="is-lh">' . ldrnseo_ring($c['score'], 96, '', 90) . '<span>' . esc_html($c['title']) . '</span>'
               . ($c['min'] < $c['score'] ? '<small>lowest ' . (int) $c['min'] . '</small>' : '<small>every page</small>') . '</div>';
        }
    }
    echo '</div>' . ldrnseo_lh_legend() . '</section>';

    // Every page against every category, with its three main metrics.
    echo ldrnseo_section('Page by page', '<span>select a page for its full report</span>', 'pages', 'layout-grid');
    echo '<div class="is-mx"><div class="is-mx-row is-mx-head"><span>Page</span>';
    foreach ($order as $cid) {
        echo '<span>' . esc_html(array('performance' => 'Perf.', 'accessibility' => 'Access.', 'best-practices' => 'Best pr.', 'seo' => 'SEO', 'agentic-browsing' => 'Agentic')[$cid]) . '</span>';
    }
    echo '<span>LCP</span><span>TBT</span><span>CLS</span></div>';
    $defs = ldrnseo_lh_metric_defs($device);
    foreach ($site['pages'] as $r => $pg) {
        echo '<a class="is-mx-row" href="' . esc_url(ldrnseo_url('speed', array('route' => $r, 'device' => $device))) . '"><span>' . esc_html(ldrnseo_route_name($r)) . '</span>';
        foreach ($order as $cid) {
            $c = $pg['cats'][$cid] ?? null;
            if (!$c) {
                echo '<span></span>';
                continue;
            }
            if (!empty($c['fraction'])) {
                echo '<span><em class="is-pill is-' . ($c['fraction'][0] === $c['fraction'][1] ? 'good' : 'fair') . '">' . (int) $c['fraction'][0] . '/' . (int) $c['fraction'][1] . '</em></span>';
            } else {
                echo '<span><em class="is-pill is-' . ldrnseo_lh_band($c['score']) . '">' . (null === $c['score'] ? '–' : (int) $c['score']) . '</em></span>';
            }
        }
        foreach (array('LCP', 'TBT', 'CLS') as $k) {
            $m = $pg['metrics'][$k] ?? null;
            echo '<span class="is-mx-m is-' . ($m ? ldrnseo_lh_metric_band($k, $m['value'], $defs) : 'none') . '">' . ($m ? esc_html($m['display']) : '') . '</span>';
        }
        echo '</a>';
    }
    echo '</div></section>';

    // Fix once, every page gains.
    if ($site['common']) {
        $cats = array('performance' => 'Performance', 'accessibility' => 'Accessibility', 'best-practices' => 'Best practices', 'seo' => 'SEO', 'agentic-browsing' => 'Agentic browsing');
        echo ldrnseo_section('Fix once, every page gains', '<span>' . count($site['common']) . ' audits fail somewhere</span>', 'common', 'zap');
        echo '<div class="is-common">';
        foreach ($site['common'] as $c) {
            $n = count($c['routes']);
            echo '<details class="is-lha"><summary><i class="is-lhr is-lhr-' . esc_attr($c['rating']) . '"></i><span class="is-lha-t">' . esc_html($c['title']) . '</span>'
               . (ldrnseo_lh_https_only($c['id'])
                   ? '<span class="is-chip is-chip-na" title="This copy runs on plain HTTP; the live domain is served over HTTPS">passes live</span>'
                   : '<span class="is-chip">' . esc_html($cats[$c['cat']] ?? $c['cat']) . '</span>')
               . '<span class="is-common-bar"><i style="width:' . round(100 * $n / count($site['pages'])) . '%"></i></span><b class="is-common-n">' . $n . '/' . count($site['pages']) . '</b></summary><div class="is-lha-body"><ul class="is-common-pages">';
            foreach ($c['routes'] as $r) {
                echo '<li><a href="' . esc_url(ldrnseo_url('speed', array('route' => $r, 'device' => $device, '#' => 'a-' . $c['id']))) . '">' . esc_html(ldrnseo_route_name($r)) . ldrnseo_icon('arrow-up-right') . '</a></li>';
            }
            echo '</ul></div></details>';
        }
        echo '</div></section>';
    }

    // Largest Contentful Paint, page by page, against Google's lines.
    $rows = array();
    foreach ($site['pages'] as $r => $pg) {
        if (isset($pg['metrics']['LCP'])) {
            $rows[$r] = (float) $pg['metrics']['LCP']['value'];
        }
    }
    if ($rows) {
        arsort($rows);
        $d = $defs['LCP'];
        $span = max(max($rows), $d[2] * 1.15);
        echo '<div class="is-grid-2">';
        echo ldrnseo_section('Largest Contentful Paint', '<span>good under ' . number_format_i18n($d[1] / 1000, 1) . ' s</span>', '', 'timer');
        echo '<div class="is-lcp"><div class="is-lcp-lines"><i style="left:' . round(100 * $d[1] / $span, 1) . '%"></i><i class="p" style="left:' . round(100 * $d[2] / $span, 1) . '%"></i></div>';
        foreach ($rows as $r => $v) {
            echo '<a class="is-lcp-row" href="' . esc_url(ldrnseo_url('speed', array('route' => $r, 'device' => $device))) . '"><span>' . esc_html(ldrnseo_route_name($r)) . '</span>'
               . '<span class="is-lcp-track"><i class="is-' . ldrnseo_lh_metric_band('LCP', $v, $defs) . '" style="width:' . round(100 * $v / $span, 1) . '%"></i></span><b>' . number_format_i18n($v / 1000, 1) . ' s</b></a>';
        }
        echo '</div></section>';

        // Phone against desktop.
        $other = ldrnseo_lh_site('mobile' === $device ? 'desktop' : 'mobile');
        $db = array();
        foreach ($site['pages'] as $r => $pg) {
            $mob = 'mobile' === $device ? $pg : ($other['pages'][$r] ?? null);
            $desk = 'desktop' === $device ? $pg : ($other['pages'][$r] ?? null);
            $db[] = array('label' => ldrnseo_route_name($r), 'a' => $mob['cats']['performance']['score'] ?? null, 'b' => $desk['cats']['performance']['score'] ?? null,
                          'href' => ldrnseo_url('speed', array('route' => $r, 'device' => $device)));
        }
        echo ldrnseo_section('Performance, mobile against desktop', '', '', 'smartphone') . ldrnseo_dumbbell($db, array('Mobile', 'Desktop')) . '</section></div>';
    }
}

/* -------------------------------------------------------------- one page */

function ldrnseo_speed_page($route, $device) {
    $rep = ldrnseo_lh_report($route, $device);
    if (!$rep) {
        echo '<section class="is-section">' . ldrnseo_void('gauge', esc_html(ldrnseo_route_name($route)) . ' has not been tested on ' . $device . ' yet.') . '</section>';
        return;
    }
    $order = array('performance', 'accessibility', 'best-practices', 'seo', 'agentic-browsing');
    $defs  = ldrnseo_lh_metric_defs($device);

    // Real users: Google's Chrome UX Report, else this site's own consented measurements.
    ldrnseo_speed_field($rep, $route);

    // The five scores.
    echo '<section class="is-section"><header class="is-section-head"><h2>' . ldrnseo_icon('gauge') . esc_html(ldrnseo_route_name($route)) . '</h2>'
       . '<div class="is-section-aside">' . ldrnseo_lh_source_chip(array($rep['source'])) . '<a class="is-link" href="' . esc_url($rep['url']) . '" target="_blank" rel="noopener">' . esc_html(preg_replace('#^https?://#', '', $rep['url'])) . ldrnseo_icon('arrow-up-right') . '</a></div></header>';
    echo '<div class="is-lh-scores">';
    foreach ($order as $cid) {
        if (!isset($rep['cats'][$cid])) {
            continue;
        }
        $c = $rep['cats'][$cid];
        if (!empty($c['fraction'])) {
            echo '<a class="is-lh" href="#lh-' . esc_attr($cid) . '"><span class="is-frac is-' . ($c['fraction'][0] === $c['fraction'][1] ? 'good' : 'fair') . '"><i></i>' . (int) $c['fraction'][0] . '/' . (int) $c['fraction'][1] . '</span><span>' . esc_html($c['title']) . '</span></a>';
        } else {
            echo '<a class="is-lh" href="#lh-' . esc_attr($cid) . '">' . ldrnseo_ring($c['score'], 74, '', 90) . '<span>' . esc_html($c['title']) . '</span></a>';
        }
    }
    echo '</div></section>';

    // Performance.
    if (isset($rep['cats']['performance'])) {
        $perf = $rep['cats']['performance'];
        echo '<section class="is-section" id="lh-performance"><div class="is-lh-perf"><div class="is-lh-perf-score">' . ldrnseo_ring($perf['score'], 128, '', 90) . '<h3>Performance</h3>';
        echo '<div class="is-lh-chips">';
        foreach ($rep['metrics'] as $k => $m) {
            $pts = null === $m['score'] ? 0 : (int) round($m['weight'] * $m['score']);
            echo '<span class="is-' . (null === $m['score'] ? 'none' : ($m['score'] >= 0.9 ? 'good' : ($m['score'] >= 0.5 ? 'fair' : 'weak'))) . '" title="' . esc_attr($m['title'] . ': ' . $pts . ' of ' . (int) $m['weight'] . ' points') . '">' . esc_html($k) . ' <b>+' . $pts . '</b></span>';
        }
        echo '</div>' . ldrnseo_lh_legend() . '</div>';
        if ($rep['shot']) {
            echo '<div class="is-lh-shot is-' . esc_attr($device) . '"><img src="' . esc_attr($rep['shot']) . '" alt="The page as Lighthouse saw it, loaded" width="' . ('desktop' === $device ? 1350 : 412) . '" height="' . ('desktop' === $device ? 940 : 823) . '" decoding="async"></div>';
        }
        echo '</div>';

        // Metrics.
        echo '<details class="is-lh-mx"><summary><span class="is-sub" style="margin:0">' . ldrnseo_icon('timer') . 'Metrics</span><span class="is-link is-expand"><span class="on">Expand view</span><span class="off">Collapse view</span></span></summary></details>';
        echo '<div class="is-lh-metrics">';
        foreach ($rep['metrics'] as $k => $m) {
            $band = ldrnseo_lh_metric_band($k, $m['value'], $defs);
            echo '<div class="is-lh-metric is-' . $band . '"><span class="is-lh-mk"><i class="is-lhr is-lhr-' . array('good' => 'pass', 'fair' => 'average', 'weak' => 'fail', 'none' => 'na')[$band] . '"></i>' . esc_html($m['title']) . '</span>'
               . '<b>' . esc_html($m['display']) . '</b><p class="is-lh-mdesc">' . esc_html($m['desc']) . '</p></div>';
        }
        echo '</div>';

        // Filmstrip.
        if ($rep['film']) {
            echo '<div class="is-film">';
            foreach ($rep['film'] as $f) {
                echo '<figure><img src="' . esc_attr($f['img']) . '" alt="" loading="lazy" width="80" height="' . ('desktop' === $device ? 56 : 160) . '"><figcaption>' . number_format_i18n($f['t'] / 1000, 1) . ' s</figcaption></figure>';
            }
            echo '</div>';
        }

        // Insights and diagnostics, filterable by metric.
        $lists = $rep['audits']['performance'];
        echo '<div class="is-lh-filter" role="group" aria-label="Show audits relevant to"><span>Show audits relevant to:</span><button type="button" aria-pressed="true" data-metric="">All</button>';
        foreach (array('FCP', 'LCP', 'TBT', 'CLS') as $k) {
            echo '<button type="button" aria-pressed="false" data-metric="' . $k . '">' . $k . '</button>';
        }
        echo '</div>';
        foreach (array('insights' => 'Insights', 'diagnostics' => 'Diagnostics') as $g => $title) {
            $items = array_values(array_filter($lists['failed'], function ($a) use ($g) { return $g === $a['group']; }));
            if ($items) {
                echo '<h3 class="is-lh-group">' . esc_html($title) . '</h3>' . implode('', array_map('ldrnseo_lh_audit_row', $items));
            }
        }
        echo ldrnseo_lh_folded('Passed audits', $lists['passed']) . ldrnseo_lh_folded('Not applicable', $lists['na']);
        echo '</section>';
    }

    // Every other category.
    foreach (array('accessibility', 'best-practices', 'seo', 'agentic-browsing') as $cid) {
        if (!isset($rep['cats'][$cid])) {
            continue;
        }
        $c = $rep['cats'][$cid];
        $l = $rep['audits'][$cid];
        echo '<section class="is-section" id="lh-' . esc_attr($cid) . '"><header class="is-section-head"><h2>'
           . (empty($c['fraction']) ? ldrnseo_ring($c['score'], 34, '', 90) : '<span class="is-frac is-sm is-' . ($c['fraction'][0] === $c['fraction'][1] ? 'good' : 'fair') . '"><i></i>' . (int) $c['fraction'][0] . '/' . (int) $c['fraction'][1] . '</span>')
           . esc_html($c['title']) . '</h2><span class="is-section-aside">' . count($l['passed']) . ' passed' . (($fix = count(array_filter($l['failed'], function ($x) { return in_array($x['rating'], array('fail', 'average', 'error'), true); }))) ? ' &middot; ' . $fix . ' to fix' : '') . '</span></header>';
        if ($l['failed']) {
            $by = array();
            foreach ($l['failed'] as $a) {
                $by[$a['group']][] = $a;
            }
            foreach ($by as $g => $items) {
                echo '<h3 class="is-lh-group">' . esc_html($rep['groups'][$g] ?? 'Other') . '</h3>' . implode('', array_map('ldrnseo_lh_audit_row', $items));
            }
        } else {
            echo '<p class="is-allclear" style="margin:0 0 6px">' . ldrnseo_icon('check') . 'Every audit passes</p>';
        }
        echo ldrnseo_lh_folded('Additional items to check by hand', $l['manual']) . ldrnseo_lh_folded('Passed audits', $l['passed']) . ldrnseo_lh_folded('Not applicable', $l['na']);
        echo '</section>';
    }

    // How it was measured.
    $e = $rep['env'];
    echo '<section class="is-section is-lh-env">'
       . '<span>' . ldrnseo_icon('clock') . esc_html(wp_date('M j, Y, g:i A', (int) ($rep['at'] / 1000))) . '</span>'
       . '<span>' . ldrnseo_icon('mobile' === $device ? 'smartphone' : 'monitor') . esc_html($e['device']) . ($e['browser'] ? ' with ' . esc_html($e['browser']) : '') . '</span>'
       . '<span>' . ldrnseo_icon('signal') . esc_html($e['network']) . ($e['rtt'] ? ' (' . (int) $e['rtt'] . ' ms, ' . number_format_i18n($e['kbps'] / 1024, 1) . ' Mbps)' : '') . '</span>'
       . ($e['cpu'] > 1 ? '<span>' . ldrnseo_icon('timer') . 'CPU slowed ' . esc_html((string) $e['cpu']) . '&times;</span>' : '')
       . '<span>' . ldrnseo_icon('gauge') . esc_html($rep['engine']) . '</span>'
       . (!empty($rep['spread']) && count($rep['spread']) > 1 ? '<span>' . ldrnseo_icon('activity') . 'median of ' . count($rep['spread']) . ' runs (' . min($rep['spread']) . '–' . max($rep['spread']) . ')</span>' : '<span>' . ldrnseo_icon('activity') . 'single page session</span>')
       . '</section>';
}

/** The real-user block: CrUX from Google when it has the data, else the site's own consented p75s. */
function ldrnseo_speed_field(array $rep, $route) {
    $f = $rep['field'] ?? array();
    $crux = $f['page'] ?? ($f['origin'] ?? null);
    if ($crux && !empty($crux['metrics'])) {
        $scope = isset($f['page']) ? 'this page' : 'the whole origin';
        $verdict = array('FAST' => array('good', 'Passed'), 'AVERAGE' => array('fair', 'Needs improvement'), 'SLOW' => array('weak', 'Failed'))[$crux['verdict']] ?? array('none', '');
        echo ldrnseo_section('Real users', '<span class="is-chip is-chip-' . ('good' === $verdict[0] ? 'good' : ('fair' === $verdict[0] ? 'warning' : 'error')) . '">Core Web Vitals: ' . esc_html($verdict[1]) . '</span><span>Chrome UX Report &middot; ' . esc_html($scope) . ' &middot; last 28 days</span>', 'field', 'users');
        echo '<div class="is-crux">';
        foreach (array('LCP' => 'Largest Contentful Paint', 'INP' => 'Interaction to Next Paint', 'CLS' => 'Cumulative Layout Shift', 'FCP' => 'First Contentful Paint', 'TTFB' => 'Time to First Byte') as $k => $name) {
            if (empty($crux['metrics'][$k])) {
                continue;
            }
            $m = $crux['metrics'][$k];
            $val = 'CLS' === $k ? number_format_i18n($m['p75'], 2) : ($m['p75'] >= 1000 ? number_format_i18n($m['p75'] / 1000, 1) . ' s' : (int) $m['p75'] . ' ms');
            $band = array('FAST' => 'good', 'AVERAGE' => 'fair', 'SLOW' => 'weak')[$m['cat']] ?? 'none';
            echo '<div class="is-crux-m is-' . $band . '"><span>' . esc_html($name) . '</span><b>' . esc_html($val) . '</b><span class="is-crux-dist">';
            foreach (array_values($m['dist']) as $i => $p) {
                echo '<i class="' . array('g', 'f', 'p')[$i] . '" style="flex:' . max(0.001, $p) . '" title="' . round(100 * $p) . '%"></i>';
            }
            echo '</span></div>';
        }
        echo '</div></section>';
        return;
    }
    // The site's own measurements for this page (consented visitors).
    $p = ldrnseo_period(28);
    if (function_exists('ldrnseo_vitals_for')) {
        $v = ldrnseo_vitals_for($route, $p['from'], $p['to']);
        if ($v && $v['lcp']['n'] + $v['inp']['n'] + $v['cls']['n'] > 0) {
            echo ldrnseo_section('Real users', '<span>this site\'s own measurements &middot; consented visitors &middot; p75</span>', 'field', 'users')
               . '<div class="is-vgs">' . ldrnseo_vital_gauge('lcp', $v['lcp']['p75'], $v['lcp']['n']) . ldrnseo_vital_gauge('inp', $v['inp']['p75'], $v['inp']['n']) . ldrnseo_vital_gauge('cls', $v['cls']['p75'], $v['cls']['n']) . '</div></section>';
        }
    }
}

/** One audit as PageSpeed lists it: rating mark, title, value; open for what it means and the evidence. */
function ldrnseo_lh_audit_row(array $a) {
    $out = '<details class="is-lha" id="a-' . esc_attr($a['id']) . '" data-metrics="' . esc_attr(implode(' ', $a['metrics'])) . '"><summary><i class="is-lhr is-lhr-' . esc_attr($a['rating']) . '"></i>'
         . '<span class="is-lha-t">' . esc_html($a['title']) . ($a['display'] ? ' <em>' . esc_html($a['display']) . '</em>' : '') . '</span>';
    foreach ($a['metrics'] as $k) {
        $out .= '<span class="is-lha-m">' . esc_html($k) . '</span>';
    }
    $out .= '</summary><div class="is-lha-body">' . ($a['desc'] ? '<p>' . esc_html($a['desc']) . '</p>' : '') . (!empty($a['note']) ? '<p class="is-lha-note">' . esc_html($a['note']) . '</p>' : '');
    $d = $a['details'] ?? null;
    if ($d && isset($d['checks'])) {
        $out .= '<ul class="is-lha-checks">';
        foreach ($d['checks'] as $c) {
            $out .= '<li class="' . ($c[1] ? 'y' : 'n') . '">' . ldrnseo_icon($c[1] ? 'check' : 'x') . esc_html($c[0]) . '</li>';
        }
        $out .= '</ul>';
    } elseif ($d && !empty($d['rows'])) {
        $out .= '<div class="is-lha-table"><table><thead><tr>';
        foreach ($d['head'] as $h) {
            $out .= '<th>' . esc_html($h) . '</th>';
        }
        $out .= '</tr></thead><tbody>';
        foreach ($d['rows'] as $row) {
            $out .= '<tr>';
            foreach ($row as $cell) {
                if ('node' === $cell[0]) {
                    $out .= '<td><span class="is-node">' . esc_html($cell[1]) . '</span><code>' . esc_html(mb_substr($cell[2], 0, 140)) . '</code></td>';
                } elseif ('url' === $cell[0]) {
                    $out .= '<td><code>' . esc_html($cell[1]) . '</code></td>';
                } else {
                    $out .= '<td class="' . ('num' === $cell[0] ? 'n' : '') . '">' . esc_html($cell[1]) . '</td>';
                }
            }
            $out .= '</tr>';
        }
        $out .= '</tbody></table>' . (!empty($d['more']) ? '<small>+ ' . (int) $d['more'] . ' more</small>' : '') . '</div>';
    }
    return $out . '</div></details>';
}

/** A folded list (passed / manual / not applicable), as PageSpeed does it. */
function ldrnseo_lh_folded($title, array $items) {
    if (!$items) {
        return '';
    }
    $out = '<details class="is-lh-fold"><summary>' . esc_html($title) . ' <b>(' . count($items) . ')</b></summary>';
    foreach ($items as $a) {
        $a['details'] = null;
        $out .= ldrnseo_lh_audit_row($a);
    }
    return $out . '</details>';
}

function ldrnseo_lh_legend() {
    return '<div class="is-lh-legend"><span><i class="is-lhr is-lhr-fail"></i>0–49</span><span><i class="is-lhr is-lhr-average"></i>50–89</span><span><i class="is-lhr is-lhr-pass"></i>90–100</span></div>';
}

function ldrnseo_lh_metric_band($k, $v, array $defs) {
    if (!isset($defs[$k])) {
        return 'none';
    }
    return $v <= $defs[$k][1] ? 'good' : ($v <= $defs[$k][2] ? 'fair' : 'weak');
}
