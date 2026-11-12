<?php
/**
 * Overview - only what is measured.
 *
 *   site health (the crawl audit's dial and its counts)
 *   speed of the whole site, phone and desktop (Lighthouse medians)
 *   every page against the on-page checks Google reads, as one grid
 *   traffic, search and Core Web Vitals - each block appears once it has data
 *   what to fix first - only while there is something to fix
 */

defined('ABSPATH') || exit;

function ldrnseo_screen_overview() {
    $audit = get_option('ldrnseo_audit');
    $cat   = ldrnseo_issue_catalog();
    $s     = ldrnseo_settings();
    $counts = array('error' => 0, 'warning' => 0, 'notice' => 0);
    if (is_array($audit)) {
        foreach ($audit['issues'] as $id => $rows) {
            $counts[$cat[$id][0]] += count($rows);
        }
    }

    echo '<div class="is-hero is-hero-2">';

    // Site health.
    echo '<section class="is-panel" style="display:grid;justify-items:center">';
    echo ldrnseo_dial(is_array($audit) ? $audit['score'] : null);
    if (array_sum($counts)) {
        echo '<div class="is-sevs">';
        foreach (array('error' => array('e', 'x', 'Errors'), 'warning' => array('w', 'alert-triangle', 'Warnings'), 'notice' => array('n', 'info', 'Notices')) as $sev => $d) {
            echo '<a class="' . $d[0] . '" href="' . esc_url(ldrnseo_url('audit', array('#' => 'issues'))) . '"><b>' . $counts[$sev] . '</b><span>' . ldrnseo_icon($d[1]) . $d[2] . '</span></a>';
        }
        echo '</div>';
    } elseif (is_array($audit)) {
        echo '<p class="is-allclear">' . ldrnseo_icon('check') . 'No issues on ' . count($audit['pages']) . ' pages</p>';
    }
    echo '<div class="is-hero-meta"><span>' . (is_array($audit) ? ldrnseo_icon('clock') . ' ' . esc_html(ldrnseo_ago($audit['at'])) : '') . '</span>'
       . '<button type="button" class="is-btn is-btn-primary" data-audit-run>' . ldrnseo_icon('scan-search') . (is_array($audit) ? 'Re-scan' : 'Scan site') . '</button></div>';
    echo '<div class="is-progress" data-audit-progress hidden><div class="is-progress-track"><i></i></div><span></span></div>';
    echo '</section>';

    // Speed, whole site.
    ldrnseo_overview_speed();
    echo '</div>';

    // Every page against the on-page checks.
    if (is_array($audit)) {
        ldrnseo_overview_grid($audit);
    }

    // Traffic and search - only once there is any.
    $p    = ldrnseo_period(28);
    $now  = ldrnseo_totals($p['from'], $p['to']);
    $gsc  = $s['gsc_key'] && $s['gsc_property'] ? ldrnseo_gsc_overview(28) : null;
    $gsc  = is_wp_error($gsc) ? null : $gsc;
    $tiles = array();
    if ($now['visits'] > 0) {
        $then  = ldrnseo_totals($p['pfrom'], $p['pto']);
        $daily = ldrnseo_daily($p['from'], $p['to']);
        $tiles[] = ldrnseo_kpi('users', 'Visitors', ldrnseo_num($now['visitors']), ldrnseo_delta(ldrnseo_change($now['visitors'], $then['visitors'])), '28 days', array_column($daily, 'visitors'), ldrnseo_url('performance'));
        if ($now['organic'] > 0) {
            $tiles[] = ldrnseo_kpi('search', 'From search', ldrnseo_num($now['organic']), ldrnseo_delta(ldrnseo_change($now['organic'], $then['organic'])), round(100 * $now['organic'] / $now['visits']) . '% of visits', array_column($daily, 'organic'), ldrnseo_url('performance'));
        }
    }
    $inbox = ldrnseo_inbox_count($p['from'], $p['to']);
    if ($inbox) {
        $tiles[] = ldrnseo_kpi('mail', 'Enquiries', ldrnseo_num($inbox), ldrnseo_delta(ldrnseo_change($inbox, ldrnseo_inbox_count($p['pfrom'], $p['pto']))), (int) $now['enquiries'] . ' traced to a source', array(), admin_url('admin.php?page=ldrn_submissions'));
    }
    if ($gsc && $gsc['now']['impressions'] > 0) {
        $tiles[] = ldrnseo_kpi('mouse-pointer-click', 'Google clicks', ldrnseo_num($gsc['now']['clicks']), ldrnseo_delta($gsc['before'] ? ldrnseo_change($gsc['now']['clicks'], $gsc['before']['clicks']) : null),
                               ldrnseo_num($gsc['now']['impressions']) . ' impressions', array_map(function ($r) { return $r['clicks']; }, $gsc['daily']), ldrnseo_url('performance'));
    }
    $v = ldrnseo_vitals($p['from'], $p['to']);
    $has_vitals = $v['lcp']['n'] + $v['inp']['n'] + $v['cls']['n'] > 0;
    if ($tiles || $has_vitals) {
        echo '<div class="' . ($tiles && $has_vitals ? 'is-grid-21' : '') . '">';
        if ($tiles) {
            echo '<div class="is-kpis is-kpis-' . min(4, max(2, count($tiles))) . '">' . implode('', $tiles) . '</div>';
        }
        if ($has_vitals) {
            echo '<a class="is-panel" href="' . esc_url(ldrnseo_url('performance', array('#' => 'vitals'))) . '" style="display:block"><header class="is-section-head" style="margin-bottom:6px"><h2>' . ldrnseo_icon('gauge') . 'Core Web Vitals</h2><span class="is-section-aside">real visitors &middot; p75</span></header>'
               . '<div class="is-vgs">' . ldrnseo_vital_gauge('lcp', $v['lcp']['p75'], $v['lcp']['n']) . ldrnseo_vital_gauge('inp', $v['inp']['p75'], $v['inp']['n']) . ldrnseo_vital_gauge('cls', $v['cls']['p75'], $v['cls']['n']) . '</div></a>';
        }
        echo '</div>';
    }

    // Fix first - only while there is something to fix.
    if (is_array($audit) && $audit['issues']) {
        echo ldrnseo_section('Fix first', '<a class="is-link" href="' . esc_url(ldrnseo_url('audit')) . '">Full audit ' . ldrnseo_icon('arrow-up-right') . '</a>', '', 'zap');
        $rank = array('error' => 0, 'warning' => 1, 'notice' => 2);
        $ids  = array_keys($audit['issues']);
        usort($ids, function ($a, $b) use ($rank, $cat, $audit) {
            return array($rank[$cat[$a][0]], -count($audit['issues'][$a])) <=> array($rank[$cat[$b][0]], -count($audit['issues'][$b]));
        });
        echo '<ol class="is-fixes is-fixes-2">';
        foreach (array_slice($ids, 0, 6) as $id) {
            $rows  = $audit['issues'][$id];
            $sev   = $cat[$id][0];
            $on    = array_values(array_unique(array_column($rows, 'route')));
            $where = '*' === $rows[0]['route'] ? 'site-wide' : (1 === count($on) ? esc_html(ldrnseo_route_name($on[0])) : count($on) . ' pages');
            echo '<li><a href="' . esc_url(ldrnseo_url('audit', array('#' => 'issue-' . $id))) . '" title="' . esc_attr($cat[$id][3]) . '">'
               . '<span class="is-fix-ico ' . $sev[0] . '">' . ldrnseo_icon(array('error' => 'x', 'warning' => 'alert-triangle', 'notice' => 'info')[$sev]) . '</span>'
               . '<span><span class="is-fix-title">' . esc_html($cat[$id][2]) . '</span><span class="is-fix-cat">' . esc_html($cat[$id][1]) . '</span></span>'
               . '<span class="is-fix-where">' . $where . '</span>' . ldrnseo_icon('arrow-up-right', 'is-go') . '</a></li>';
        }
        echo '</ol></section>';
    }
}

/** The whole site's Lighthouse medians, phone and desktop, and the slowest page on each. */
function ldrnseo_overview_speed() {
    $sites = array('mobile' => ldrnseo_lh_site('mobile'), 'desktop' => ldrnseo_lh_site('desktop'));
    if (!$sites['mobile'] && !$sites['desktop']) {
        echo '<section class="is-panel">' . ldrnseo_void('gauge', 'No speed test yet. <a class="is-link" href="' . esc_url(ldrnseo_url('speed')) . '">Test it on the Speed tab</a>') . '</section>';
        return;
    }
    $any = $sites['mobile'] ?: $sites['desktop'];
    echo '<section class="is-panel is-ov-site"><header class="is-section-head"><h2>' . ldrnseo_icon('gauge') . 'Speed &middot; whole site</h2>'
       . '<div class="is-section-aside">' . ldrnseo_lh_source_chip($any['sources']) . '<span>' . count($any['pages']) . ' pages &middot; median</span></div></header>';
    echo '<div class="is-ov-row is-ov-head"><span></span><span class="is-ov-rings">';
    foreach ($any['cats'] as $c) {
        $short = array('Performance' => 'Perf.', 'Accessibility' => 'Access.', 'Best Practices' => 'Best pr.', 'Agentic Browsing' => 'Agentic')[$c['title']] ?? $c['title'];
        echo '<span><b class="is-long">' . esc_html($c['title']) . '</b><b class="is-short">' . esc_html($short) . '</b></span>';
    }
    echo '</span><span></span></div>';
    foreach (array('mobile' => array('smartphone', 'Phone'), 'desktop' => array('monitor', 'Desktop')) as $dev => $d) {
        $site = $sites[$dev];
        if (!$site) {
            continue;
        }
        echo '<a class="is-ov-row" href="' . esc_url(ldrnseo_url('speed', array('device' => $dev))) . '"><span class="is-ov-dev">' . ldrnseo_icon($d[0]) . $d[1] . '</span><span class="is-ov-rings">';
        foreach ($site['cats'] as $cid => $c) {
            echo ldrnseo_ring($c['score'], 58, '', 90);
        }
        echo '</span>';
        // The page that pulls Performance down most.
        $worst = null;
        foreach ($site['pages'] as $r => $pg) {
            $sc = $pg['cats']['performance']['score'] ?? null;
            if (null !== $sc && (null === $worst || $sc < $worst[1])) {
                $worst = array($r, $sc);
            }
        }
        echo '<span class="is-ov-worst">' . ($worst ? '<small>slowest</small><b>' . esc_html(ldrnseo_route_name($worst[0])) . '</b><em class="is-' . ldrnseo_lh_band($worst[1]) . '">' . (int) $worst[1] . '</em>' : '') . '</span></a>';
    }
    // What, fixed once in the theme, lifts every page.
    $common = array_slice(array_filter((array) ($sites['mobile']['common'] ?? array()), function ($c) { return count($c['routes']) > 1 && !ldrnseo_lh_https_only($c['id']); }), 0, 4);
    if ($common) {
        echo '<div class="is-ov-fixes"><h3 class="is-sub">' . ldrnseo_icon('zap') . 'Fix once, every page gains</h3><ul>';
        foreach ($common as $c) {
            echo '<li><a href="' . esc_url(ldrnseo_url('speed', array('device' => 'mobile', '#' => 'common'))) . '"><i class="is-lhr is-lhr-' . esc_attr($c['rating']) . '"></i><span>' . esc_html($c['title']) . '</span><b>' . count($c['routes']) . ' pages</b></a></li>';
        }
        echo '</ul></div>';
    }
    echo '</section>';
}

/**
 * Every page (columns) against the on-page checks (rows): green where the
 * page passes, red where it does not. Read from the last crawl.
 */
function ldrnseo_overview_grid(array $audit) {
    $pages = ldrnseo_pages_list();
    $routes = array_values(array_filter(array_keys($pages), function ($r) use ($audit) { return isset($audit['pages'][$r]); }));
    if (!$routes) {
        return;
    }
    // Each row is the audit's own verdict for that page, so the grid and the Audit tab never disagree.
    $by = array();
    foreach ($audit['issues'] as $id => $rows) {
        foreach ($rows as $row) {
            $by[$id][$row['route']] = true;
        }
    }
    $clean = function (array $ids) use ($by) {
        return function ($r) use ($ids, $by) {
            foreach ($ids as $id) {
                if (isset($by[$id][$r]) || isset($by[$id]['*'])) {
                    return false;
                }
            }
            return true;
        };
    };
    $checks = array(
        'Keyphrase'          => function ($r) use ($pages) { return '' !== $pages[$r]['keyphrase'] || '/privacy' === $r; },
        'Title'              => $clean(array('title_missing', 'title_long', 'title_short', 'title_dup')),
        'Meta description'   => $clean(array('desc_missing', 'desc_long', 'desc_short', 'desc_dup')),
        'One H1'             => $clean(array('h1_missing', 'h1_multi')),
        'Heading order'      => $clean(array('heading_skip')),
        'Enough text'        => $clean(array('thin')),
        'Indexable'          => $clean(array('http_error', 'noindex', 'canonical_miss', 'canonical_other', 'canonical_conflict')),
        'Readable without JS' => $clean(array('js_only', 'hash_links')),
        'Structured data'    => $clean(array('schema_missing', 'schema_invalid', 'logo_broken')),
        'Share card'         => $clean(array('og_missing', 'og_broken', 'twitter_missing')),
        'Image alt text'     => $clean(array('img_alt')),
        'Images sized & light' => $clean(array('img_dims', 'img_heavy', 'img_legacy')),
        'Internal links'     => $clean(array('orphan', 'few_inlinks', 'broken_internal', 'redirect_link', 'empty_anchor')),
    );
    $short = function ($r) {
        $n = ldrnseo_route_name($r);
        return mb_strlen($n) > 18 ? mb_substr($n, 0, 17) . '…' : $n;
    };
    echo ldrnseo_section('Every page, on-page', '<span>' . count($routes) . ' pages &middot; ' . count($checks) . ' checks</span>', 'grid', 'layout-grid');
    echo '<div class="is-og" style="--cols:' . count($routes) . '"><span></span>';
    foreach ($routes as $r) {
        echo '<a class="is-og-h" href="' . esc_url(ldrnseo_url('pages', array('route' => $r))) . '" title="' . esc_attr(ldrnseo_route_name($r)) . '"><span>' . esc_html($short($r)) . '</span></a>';
    }
    echo '<span></span>';
    foreach ($checks as $label => $fn) {
        $ok = 0;
        $cells = '';
        foreach ($routes as $r) {
            $pass = (bool) $fn($r);
            $ok += $pass ? 1 : 0;
            $cells .= '<i class="' . ($pass ? 'y' : 'n') . '" title="' . esc_attr(ldrnseo_route_name($r) . ' · ' . $label . ': ' . ($pass ? 'yes' : 'no')) . '"></i>';
        }
        echo '<span class="is-og-l">' . esc_html($label) . '</span>' . $cells . '<b class="' . ($ok === count($routes) ? 'is-c-ok' : 'is-c-bad') . '">' . $ok . '/' . count($routes) . '</b>';
    }
    echo '</div></section>';
}

/** Lighthouse's three colour bands. */
function ldrnseo_lh_band($score) {
    return null === $score ? 'none' : ($score >= 90 ? 'good' : ($score >= 50 ? 'fair' : 'weak'));
}

/** Where the Lighthouse numbers came from: Google's servers, or a computer. */
function ldrnseo_lh_source_chip(array $sources) {
    if (array('psi') === $sources) {
        return '<span class="is-chip">' . ldrnseo_brand('Google', 12) . 'PageSpeed Insights</span>';
    }
    return '<span class="is-chip" title="Lighthouse run on a computer. Accessibility, Best practices, SEO and Agentic browsing match Google exactly; Performance depends on that computer\'s speed. Add the PageSpeed key in Settings for Google\'s own run.">' . ldrnseo_icon('monitor') . 'Lighthouse &middot; local run</span>';
}

/** Pass / fail for the site, as Search Console words it. */
function ldrnseo_cwv_verdict(array $v) {
    $need = 20;
    if ($v['lcp']['n'] < $need && $v['inp']['n'] < $need && $v['cls']['n'] < $need) {
        return array('word' => 'Waiting', 'note' => max($v['lcp']['n'], $v['cls']['n']) . '/' . $need . ' visits');
    }
    $worst = 'good';
    foreach (array('lcp', 'inp', 'cls') as $m) {
        $r = ldrnseo_vital_rating($m, $v[$m]['p75']);
        if ('poor' === $r) { $worst = 'poor'; }
        elseif ('ni' === $r && 'poor' !== $worst) { $worst = 'ni'; }
    }
    return array('word' => 'good' === $worst ? 'Passed' : ('poor' === $worst ? 'Failed' : 'Needs work'), 'note' => '');
}
