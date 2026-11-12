<?php
/**
 * Pages - the site as a lookbook.
 *
 * Every address - each page, the nursery, each article, event and vacancy -
 * is shown as its own plate (its share picture), with its SEO and readability scores, its keyphrase and how it
 * reads in Google. Opening a frame gives the editor: the Google result and the
 * WhatsApp link preview as they will look, and the analysis of the page's
 * real text in Rank Math's groups.
 */

defined('ABSPATH') || exit;

/** Everything the analyser needs about one page. */
function ldrnseo_page_payload($route, array $page, $audit, $content, $stats, $built) {
    $a = is_array($audit) && isset($audit['pages'][$route]) ? $audit['pages'][$route] : array();
    $c = $content[$route] ?? array();
    $share = ldrnseo_share_image($route);
    $crawl = ldrnseo_last_crawl();
    $google = null;
    foreach (array('Googlebot', 'Googlebot-Image', 'Google Inspection') as $bot) {
        $t = $crawl[$route][$bot] ?? null;
        if ($t && (!$google || $t > $google)) {
            $google = $t;
        }
    }
    return array(
        'route'     => $route,
        'url'       => ldrnseo_abs($route),
        'label'     => wp_strip_all_tags($page['label']),
        'kind'      => '/' === $route ? 'home' : ('/contact' === $route ? 'contact' : (in_array($route, array('/privacy', '/careers/apply'), true) ? 'legal' : $page['kind'])),
        'title'     => $page['title'],
        'desc'      => $page['description'],
        'builtTitle'=> $built[$route]['title'] ?? ($a['title'] ?? ''),
        'builtDesc' => $built[$route]['description'] ?? ($a['desc'] ?? ''),
        'keyphrase' => $page['keyphrase'],
        'noindex'   => $page['noindex'],
        'imageId'   => $page['image_id'],
        'share'     => $share['url'],
        'photo'     => ldrnseo_page_photo($route),
        'text'      => $c['text'] ?? '',
        'paras'     => $c['paras'] ?? array(),
        'headings'  => $c['headings'] ?? array(),
        'images'    => $c['images'] ?? array(),
        'inlinks'   => $c['inlinks'] ?? array(),
        'outlinks'  => isset($a['links_internal']) ? count($a['links_internal']) : null,
        'words'     => $a['words'] ?? null,
        'schema'    => $a['schema_types'] ?? array(),
        'googlebot' => $google ? ldrnseo_ago($google) : null,
        'audited'   => !empty($a),
        'views'     => (int) ($stats[$route]['views'] ?? 0),
        'organic'   => (int) ($stats[$route]['organic'] ?? 0),
    );
}

function ldrnseo_screen_pages() {
    $list    = ldrnseo_pages_list();
    $audit   = get_option('ldrnseo_audit');
    $content = (array) get_option('ldrnseo_content', array());
    $p       = ldrnseo_period(28);
    $stats   = ldrnseo_page_stats($p['from'], $p['to']);
    $built   = ldrnseo_page_facts_cache();
    $route   = isset($_GET['route']) ? sanitize_text_field(wp_unslash($_GET['route'])) : '';

    $all = array();
    foreach ($list as $r => $page) {
        $all[$r] = ldrnseo_page_payload($r, $page, $audit, $content, $stats, $built);
    }
    $phrases = array();
    foreach ($all as $r => $d) {
        if ('' !== $d['keyphrase']) {
            $phrases[$r] = $d['keyphrase'];
        }
    }
    if ($route && isset($list[$route])) {
        ldrnseo_page_editor($all[$route], $phrases);
        return;
    }

    if (!is_array($audit)) {
        echo '<div class="is-void">' . ldrnseo_icon('scan-search') . '<span>Scores read each page\'s live text.</span><button type="button" class="is-btn is-btn-primary" data-audit-run style="margin-left:auto">Scan site</button></div>'
           . '<div class="is-progress" data-audit-progress hidden><div class="is-progress-track"><i></i></div><span></span></div>';
    }
    echo '<script type="application/json" id="is-pages-data">' . str_replace('</', '<\/', wp_json_encode(array('pages' => array_values($all), 'phrases' => $phrases))) . '</script>';

    $groups = array('Pages' => array('em' => 'the site', 'items' => array()), 'Our nursery' => array('em' => 'each setting', 'items' => array()),
                    'Articles & events' => array('em' => 'the blog and diary', 'items' => array()), 'Vacancies' => array('em' => 'careers', 'items' => array()));
    $where = array('setting' => 'Our nursery', 'article' => 'Articles & events', 'event' => 'Articles & events', 'job' => 'Vacancies');
    foreach ($all as $r => $d) {
        $groups[$where[$d['kind']] ?? 'Pages']['items'][$r] = $d;
    }
    foreach ($groups as $title => $g) {
        if (!$g['items']) {
            continue;
        }
        echo '<section class="is-section"><div class="is-sheet-head"><h3>' . esc_html($title) . '<em>' . esc_html($g['em']) . '</em></h3>'
           . '<span class="is-muted">' . count($g['items']) . ' ' . (1 === count($g['items']) ? 'page' : 'pages') . '</span></div><div class="is-sheet">';
        foreach ($g['items'] as $r => $d) {
            $t = $d['title'] ?: $d['builtTitle'];
            $ds = $d['desc'] ?: $d['builtDesc'];
            echo '<a class="is-frame" data-route="' . esc_attr($r) . '" href="' . esc_url(ldrnseo_url('pages', array('route' => $r))) . '">'
               . '<div class="is-frame-img"><img src="' . esc_url($d['share']) . '" alt="" loading="lazy">'
               . '<span class="is-frame-route">' . esc_html($r) . '</span>'
               . '<span class="is-frame-scores"><span data-ring="seo">' . ldrnseo_ring(null, 40, 'SEO') . '</span><span data-ring="read">' . ldrnseo_ring(null, 40, 'Read') . '</span></span>'
               . '<span class="is-frame-name">' . esc_html($d['label']) . '</span></div>'
               . '<div class="is-frame-body"><div class="is-frame-serp"><b>' . esc_html($t) . '</b><span>' . esc_html($ds) . '</span></div>'
               . '<div class="is-frame-kp">' . ($d['noindex'] ? ldrnseo_sev('na', 'hidden from Google') : ($d['keyphrase'] ? '<span class="is-chip is-chip-kp">' . ldrnseo_icon('key-round') . '<span>' . esc_html($d['keyphrase']) . '</span></span>' : ('legal' === $d['kind'] ? '' : '<span class="is-chip is-chip-dim">+ focus keyphrase</span>'))) . '</div>'
               . '<div class="is-frame-stats"><span title="Words">' . ldrnseo_icon('type') . esc_html(ldrnseo_num($d['words'])) . '</span>'
               . '<span title="Pages linking here">' . ldrnseo_icon('link') . ($d['audited'] ? count($d['inlinks']) : '–') . '</span>'
               . ($d['views'] ? '<span title="Visits, 28 days">' . ldrnseo_icon('users') . esc_html(ldrnseo_num($d['views'])) . '</span>' : '')
               . '</div></div></a>';
        }
        echo '</div></section>';
    }
}

function ldrnseo_page_editor(array $d, array $phrases) {
    $edit = ldrnseo_can_edit();
    echo '<script type="application/json" id="is-page-data">' . str_replace('</', '<\/', wp_json_encode(array('page' => $d, 'phrases' => $phrases))) . '</script>';

    echo '<div class="is-editor-hero"><img src="' . esc_url($d['photo'] ?: $d['share']) . '" alt="">'
       . '<div class="is-editor-hero-text"><a class="is-back" href="' . esc_url(ldrnseo_url('pages')) . '">&larr; All pages</a>'
       . '<h2>' . esc_html($d['label']) . '</h2><a class="is-url" href="' . esc_url($d['url']) . '" target="_blank" rel="noopener">' . esc_html(preg_replace('#^https?://#', '', $d['url'])) . ldrnseo_icon('arrow-up-right') . '</a></div>'
       . '<div class="is-editor-hero-scores"><span data-ring="seo">' . ldrnseo_ring(null, 64, 'SEO') . '</span><span data-ring="read">' . ldrnseo_ring(null, 64, 'Readability') . '</span></div></div>';

    echo '<div class="is-editor">';
    echo '<form class="is-editor-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="ldrnseo_save_page"><input type="hidden" name="route" value="' . esc_attr($d['route']) . '">';
    wp_nonce_field('ldrnseo_save_page');

    echo '<section class="is-section"><header class="is-section-head"><h2>' . ldrnseo_icon('search') . 'Google</h2>'
       . '<div class="is-seg" role="group"><button type="button" aria-pressed="true" data-serp="desktop">' . ldrnseo_icon('monitor') . '</button><button type="button" aria-pressed="false" data-serp="mobile">' . ldrnseo_icon('smartphone') . '</button></div></header>';
    echo '<div class="is-serp" data-serp-box="desktop"><div class="is-serp-site"><span class="is-serp-fav"><img src="' . esc_url(get_template_directory_uri() . '/dist/favicon.png') . '" alt=""></span>'
       . '<span><b>' . esc_html(ldrnseo_business()['name']) . '</b><small>' . esc_html(preg_replace('#^https?://#', '', $d['url'])) . '</small></span></div>'
       . '<p class="is-serp-title" data-serp-title></p><p class="is-serp-desc" data-serp-desc></p></div>';
    echo '<div style="height:18px"></div>';
    echo ldrnseo_field('Focus keyphrase', '<input type="text" id="is-f-kp" name="seo[keyphrase]" maxlength="80" value="' . esc_attr($d['keyphrase']) . '" placeholder="' . esc_attr(ldrnseo_keyphrase_hint($d)) . '" data-f="kp"' . ($edit ? '' : ' readonly') . '>', '', 'is-f-kp');
    echo '<div class="is-field"><label for="is-f-title">SEO title <span class="is-count" data-count="title"></span></label><div class="is-control">'
       . '<input type="text" id="is-f-title" name="seo[title]" maxlength="90" value="' . esc_attr($d['title']) . '" placeholder="' . esc_attr($d['builtTitle']) . '" data-f="title"' . ($edit ? '' : ' readonly') . '>'
       . '<div class="is-meter" data-meter="title"><i></i></div></div></div>';
    echo '<div class="is-field"><label for="is-f-desc">Meta description <span class="is-count" data-count="desc"></span></label><div class="is-control">'
       . '<textarea id="is-f-desc" name="seo[description]" rows="3" maxlength="300" placeholder="' . esc_attr($d['builtDesc']) . '" data-f="desc"' . ($edit ? '' : ' readonly') . '>' . esc_textarea($d['desc']) . '</textarea>'
       . '<div class="is-meter" data-meter="desc"><i></i></div></div></div>';
    echo '</section>';

    echo '<section class="is-section" style="margin-top:18px"><header class="is-section-head"><h2>' . ldrnseo_brand('WhatsApp', 16) . 'Shared on WhatsApp</h2>'
       . '<div class="is-section-aside"><input type="hidden" name="seo[image_id]" value="' . (int) $d['imageId'] . '" data-image-id>'
       . ($edit ? '<button type="button" class="is-btn" data-pick-image>' . ldrnseo_icon('image') . 'Change image</button>' : '')
       . ($d['imageId'] && $edit ? '<button type="button" class="is-link" data-clear-image>Reset</button>' : '') . '</div></header>';
    echo '<div class="is-wa"><div class="is-wa-bubble"><div class="is-wa-card"><img src="' . esc_url($d['share']) . '" alt="" data-share-img>'
       . '<div><b data-share-title></b><span data-share-desc></span><small>' . esc_html((string) wp_parse_url(home_url(), PHP_URL_HOST)) . '</small></div></div>'
       . '<div class="is-wa-link">' . esc_html($d['url']) . '</div><span class="is-wa-time">' . esc_html(wp_date('g:i a')) . ' &#10003;&#10003;</span></div></div>';
    echo '</section>';

    echo '<div class="is-form-actions is-sticky" style="margin-top:18px"><label class="is-toggle" style="margin-right:auto"><input type="checkbox" name="seo[noindex]" value="1"' . checked($d['noindex'], true, false) . ($edit ? '' : ' disabled') . '>Hide from Google</label>'
       . ($edit ? '<button type="submit" class="is-btn is-btn-primary">' . ldrnseo_icon('check') . 'Save</button>' : '') . '</div>';
    echo '</form>';

    // Analysis.
    echo '<aside class="is-analysis">';
    echo '<section class="is-section"><header class="is-section-head"><h2>' . ldrnseo_icon('list-checks') . 'Analysis</h2>'
       . '<div class="is-seg" role="tablist"><button type="button" role="tab" aria-selected="true" data-an="seo">SEO</button><button type="button" role="tab" aria-selected="false" data-an="read">Readability</button></div></header>';
    echo '<div class="is-analysis-groups" data-an-list="seo"></div><div class="is-analysis-groups" data-an-list="read" hidden></div></section>';

    $h1 = array_values(array_filter($d['headings'], function ($h) { return 1 === $h[0]; }));
    echo '<section class="is-section" style="margin-top:18px"><header class="is-section-head"><h2>' . ldrnseo_icon('file-text') . 'The page</h2></header><div class="is-facts">';
    echo '<div class="is-fact"><span>' . ldrnseo_icon('type') . 'Words</span><b>' . esc_html(ldrnseo_num($d['words'])) . '</b></div>';
    echo '<div class="is-fact"><span>' . ldrnseo_icon('link') . 'Linked from</span><b>' . ($d['audited'] ? count($d['inlinks']) : '–') . '</b></div>';
    if ($d['views']) {
        echo '<div class="is-fact"><span>' . ldrnseo_icon('users') . 'Visits 28d</span><b>' . esc_html(ldrnseo_num($d['views'])) . '</b></div>';
    }
    echo '<div class="is-fact" style="grid-column:span 2"><span>' . ldrnseo_icon('heading-1') . 'H1</span><b class="is-sm">' . ($h1 ? esc_html($h1[0][1]) : '<span class="is-muted">none</span>') . '</b></div>';
    if ($d['googlebot']) {
        echo '<div class="is-fact"><span>' . ldrnseo_brand('Google', 12) . 'Googlebot</span><b class="is-sm">' . esc_html($d['googlebot']) . '</b></div>';
    }
    // Google's own word on this page, when Search Console is connected.
    $insp = ldrnseo_gsc_inspect($d['route']);
    if (null !== $insp) {
        if (is_wp_error($insp)) {
            echo '<div class="is-fact" style="grid-column:1/-1"><span>' . ldrnseo_brand('Search Console', 12) . 'Google index</span><b class="is-sm is-muted">' . esc_html($insp->get_error_message()) . '</b></div>';
        } else {
            $ok = 'PASS' === $insp['verdict'];
            echo '<div class="is-fact" style="grid-column:1/-1"><span>' . ldrnseo_brand('Search Console', 12) . 'Google index</span><b class="is-sm">'
               . ldrnseo_sev($ok ? 'good' : ('NEUTRAL' === $insp['verdict'] ? 'ni' : 'poor'), $insp['coverage'] ?: ($ok ? 'Indexed' : 'Not indexed'))
               . ($insp['crawled'] ? ' <span class="is-muted">crawled ' . esc_html(ldrnseo_ago(strtotime($insp['crawled']))) . '</span>' : '')
               . ($insp['canonical'] && untrailingslashit($insp['canonical']) !== untrailingslashit($d['url']) ? ' <span class="is-chip is-chip-ni">Google chose ' . esc_html($insp['canonical']) . '</span>' : '')
               . '</b></div>';
        }
    }
    echo '<div class="is-fact" style="grid-column:1/-1"><span>' . ldrnseo_icon('code-xml') . 'Structured data</span><b class="is-sm"><span class="is-schema-types">'
       . implode('', array_map(function ($t) { return '<span class="is-chip">' . esc_html($t) . '</span>'; }, $d['schema'])) . '</span></b></div>';
    echo '</div>';
    if ($d['headings']) {
        echo '<details class="is-outline"><summary>Heading outline &middot; ' . count($d['headings']) . '</summary><ol>';
        foreach ($d['headings'] as $h) {
            echo '<li style="--lv:' . (int) $h[0] . '"><span>H' . (int) $h[0] . '</span>' . esc_html($h[1]) . '</li>';
        }
        echo '</ol></details>';
    }
    echo '</section></aside></div>';
}

/** A suggestion for the empty keyphrase box, from the page itself. */
function ldrnseo_keyphrase_hint(array $d) {
    $b = ldrnseo_business();
    $place = $b['locality'] ?: 'Hounslow';
    if ('setting' === $d['kind']) {
        return strtolower('day nursery in ' . $d['label']);
    }
    $by_route = array('/' => 'day nursery ' . $place, '/our-nursery' => 'nursery ' . $place, '/about' => 'learning through play nursery',
                      '/about/ofsted' => 'little rainbows ofsted report', '/curriculum' => 'eyfs curriculum nursery', '/admissions' => 'nursery admissions ' . $place,
                      '/fees' => 'nursery fees ' . $place, '/fee-calculator' => 'nursery fee calculator', '/funded-childcare' => 'funded childcare ' . $place,
                      '/careers' => 'nursery jobs ' . $place, '/careers/vacancies' => 'nursery practitioner jobs ' . $place,
                      '/check-availability' => 'nursery places ' . $place, '/contact' => 'contact little rainbows day nursery',
                      '/food-hygiene-rating' => 'little rainbows food hygiene rating', '/testimonials' => 'little rainbows nursery reviews');
    return strtolower($by_route[$d['route']] ?? '');
}
