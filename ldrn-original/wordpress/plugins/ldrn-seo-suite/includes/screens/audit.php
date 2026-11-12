<?php
/**
 * Audit - what the last crawl found: the dial, the twelve categories as
 * tiles (each a filter), every issue with the pages it touches, every page
 * with its answer time and its weight split by kind. Lighthouse is the Speed tab.
 */

defined('ABSPATH') || exit;

function ldrnseo_category_icons() {
    return array('Indexability' => 'eye', 'Titles & descriptions' => 'type', 'Headings & content' => 'file-text', 'Links' => 'link',
                 'Images' => 'image', 'Performance' => 'zap', 'Sharing' => 'share-2', 'Structured data' => 'code-xml',
                 'Security' => 'lock', 'Trust & local' => 'map-pin');
}

function ldrnseo_screen_audit() {
    $audit   = get_option('ldrnseo_audit');
    $history = (array) get_option('ldrnseo_audit_history', array());
    $cat     = ldrnseo_issue_catalog();

    echo '<div class="is-grid-21" style="grid-template-columns:330px minmax(0,1fr)">';
    echo '<section class="is-panel" style="display:grid;justify-items:center;align-content:start">' . ldrnseo_dial(is_array($audit) ? $audit['score'] : null, 'Health score');
    echo '<div class="is-hero-meta">' . (is_array($audit) ? '<span>' . count($audit['pages']) . ' pages &middot; ' . (int) $audit['files'] . ' checks &middot; ' . (int) $audit['took'] . 's</span>' : '<span></span>')
       . '<button type="button" class="is-btn is-btn-primary" data-audit-run>' . ldrnseo_icon('scan-search') . (is_array($audit) ? 'Re-scan' : 'Scan site') . '</button></div>';
    echo '<div class="is-progress" data-audit-progress hidden><div class="is-progress-track"><i></i></div><span></span></div>';
    if (count($history) > 1) {
        echo '<div class="is-history-spark" style="width:100%;margin-top:16px"><span class="is-kpi-head" style="justify-content:space-between"><span>Score history</span><span>' . count($history) . ' scans</span></span>'
           . ldrnseo_spark(array_column($history, 'score')) . '</div>';
    }
    echo '</section>';

    $by = array();
    foreach (array_keys(ldrnseo_category_icons()) as $c) {
        $by[$c] = array('error' => 0, 'warning' => 0, 'notice' => 0);
    }
    if (is_array($audit)) {
        foreach ($audit['issues'] as $id => $rows) {
            $by[$cat[$id][1]][$cat[$id][0]] += count($rows);
        }
    }
    echo '<section class="is-section" id="issues"><header class="is-section-head"><h2>' . ldrnseo_icon('layout-grid') . 'Categories</h2>'
       . '<button type="button" class="is-link" data-cat="" aria-pressed="true">Show all</button></header><div class="is-cats">';
    foreach (ldrnseo_category_icons() as $c => $ico) {
        $n = array_sum($by[$c]);
        $bar = '';
        foreach (array('error' => 'e', 'warning' => 'w', 'notice' => 'n') as $sev => $k) {
            if ($by[$c][$sev]) {
                $bar .= '<i class="' . $k . '" style="flex:' . $by[$c][$sev] . '"></i>';
            }
        }
        echo '<button type="button" class="is-cat' . ($n ? '' : ' is-clean') . '" data-cat="' . esc_attr($c) . '" aria-pressed="false">'
           . '<span class="is-cat-top">' . ldrnseo_icon($ico) . esc_html($c) . '</span><span class="is-cat-bar">' . $bar . '</span>'
           . '<span class="is-cat-n">' . ($n ? $n . ' issue' . (1 === $n ? '' : 's') : 'clean') . '</span></button>';
    }
    echo '</div></section></div>';

    if (!is_array($audit)) {
        return;
    }

    echo ldrnseo_section('Issues', '<span>' . count($audit['issues']) . ' kinds</span>', '', 'scan-search');
    if (!$audit['issues']) {
        echo ldrnseo_void('check', 'Every page passed every check.');
    }
    $rank = array('error' => 0, 'warning' => 1, 'notice' => 2);
    $ids  = array_keys($audit['issues']);
    usort($ids, function ($a, $b) use ($rank, $cat, $audit) {
        return array($rank[$cat[$a][0]], -count($audit['issues'][$a])) <=> array($rank[$cat[$b][0]], -count($audit['issues'][$b]));
    });
    echo '<div class="is-issues" style="margin-top:0">';
    foreach ($ids as $id) {
        $rows = $audit['issues'][$id];
        $sev  = $cat[$id][0];
        $site = '*' === $rows[0]['route'];
        echo '<details class="is-issue" id="issue-' . esc_attr($id) . '" data-cat="' . esc_attr($cat[$id][1]) . '"><summary>'
           . '<span class="is-fix-ico ' . $sev[0] . '">' . ldrnseo_icon(array('error' => 'x', 'warning' => 'alert-triangle', 'notice' => 'info')[$sev]) . '</span>'
           . '<span><span class="is-issue-title">' . esc_html($cat[$id][2]) . '</span><span class="is-issue-cat">' . esc_html($cat[$id][1]) . '</span></span>'
           . ldrnseo_sev($sev) . '<span class="is-issue-n">' . ($site ? ldrnseo_icon('globe') : count(array_unique(array_column($rows, 'route'))) . '<small>pages</small>') . '</span></summary>'
           . '<div class="is-issue-body"><p class="is-issue-fix" title="' . esc_attr($cat[$id][3]) . '">' . ldrnseo_icon('zap') . esc_html($cat[$id][4]) . '</p><ul class="is-affected">';
        foreach ($rows as $row) {
            echo '<li>' . ('*' === $row['route'] ? ldrnseo_icon('globe') : '<a href="' . esc_url(ldrnseo_url('pages', array('route' => $row['route']))) . '">' . esc_html(ldrnseo_route_name($row['route'])) . '</a>')
               . ($row['detail'] ? ' <small>' . esc_html($row['detail']) . '</small>' : '') . '</li>';
        }
        echo '</ul></div></details>';
    }
    echo '</div></section>';

    $per = array();
    foreach ($audit['issues'] as $id => $rows) {
        foreach ($rows as $r) {
            $per[$r['route']][$cat[$id][0]] = ($per[$r['route']][$cat[$id][0]] ?? 0) + 1;
        }
    }
    $maxw = max(1, max(array_map(function ($p) { return isset($p['weight']) ? array_sum($p['weight']) : 0; }, $audit['pages'])));
    echo ldrnseo_section('Pages', '<span class="is-wt-key"><span><i style="background:#2B1E13"></i>HTML</span><span><i style="background:#8C6444"></i>CSS</span><span><i style="background:#C9A97B"></i>JS</span><span><i style="background:#6F4A2E"></i>Images</span><span><i style="background:#BFB183"></i>Fonts</span></span>', '', 'file-text');
    echo '<div class="is-strip"><div class="is-strip-row is-strip-head"><span>Page</span><span>Response</span><span>Weight</span><span>Words</span><span style="text-align:right">Issues</span></div>';
    foreach ($audit['pages'] as $route => $p) {
        $w = $p['weight'] ?? array('html' => 0, 'css' => 0, 'js' => 0, 'img' => 0, 'font' => 0);
        $total = array_sum($w);
        $ms = (int) $p['ms'];
        $iss = $per[$route] ?? array();
        echo '<div class="is-strip-row"><a href="' . esc_url(ldrnseo_url('pages', array('route' => $route))) . '">' . esc_html(ldrnseo_route_name($route)) . '</a>'
           . '<span class="is-ms ' . ($ms < 300 ? 'fast' : ($ms < 800 ? 'mid' : 'slow')) . '">' . $ms . ' ms</span>'
           . '<span style="display:grid;grid-template-columns:1fr 64px;gap:10px;align-items:center"><span class="is-wt" style="width:' . max(4, round(100 * $total / $maxw)) . '%" title="' . esc_attr(size_format($total, 1)) . '">'
           . '<i class="h" style="flex:' . (int) $w['html'] . '"></i><i class="c" style="flex:' . (int) $w['css'] . '"></i><i class="j" style="flex:' . (int) $w['js'] . '"></i><i class="m" style="flex:' . (int) $w['img'] . '"></i><i class="f" style="flex:' . (int) $w['font'] . '"></i></span>'
           . '<span class="is-muted" style="text-align:right">' . esc_html(size_format($total, 0)) . '</span></span>'
           . '<span>' . esc_html(ldrnseo_num($p['words'] ?? null)) . '</span><span class="is-counts3">'
           . (empty($iss['error']) ? '' : '<b class="e">' . $iss['error'] . '</b>') . (empty($iss['warning']) ? '' : '<b class="w">' . $iss['warning'] . '</b>') . (empty($iss['notice']) ? '' : '<b class="n">' . $iss['notice'] . '</b>')
           . ($iss ? '' : '<b class="ok">' . ldrnseo_icon('check') . '</b>') . '</span></div>';
    }
    echo '</div></section>';

}
