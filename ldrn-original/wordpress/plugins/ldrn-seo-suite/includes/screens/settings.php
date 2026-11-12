<?php
/**
 * Settings - where the site's search setup is run from.
 *
 *   Setup            what is in place for the nursery's domain, and what waits for it
 *   Connections      Search Console, PageSpeed Insights, Bing, Google verification
 *   Indexing         which pages search engines may list (and the sitemap carries)
 *   Measurement      first-party analytics, Google Analytics, Tag Manager
 *   Redirects        old addresses sent on; the 404 log when there is one
 *
 * Keys are stored on the server and never printed back.
 */

defined('ABSPATH') || exit;

function ldrnseo_screen_settings() {
    $s     = ldrnseo_settings();
    $edit  = ldrnseo_can_edit();
    $ro    = $edit ? array() : array('readonly' => 'readonly');
    $dis   = $edit ? '' : ' disabled';
    $key   = ldrnseo_gsc_key();
    $audit = get_option('ldrnseo_audit');
    $local = ldrnseo_is_local();
    $on    = function ($yes) { return $yes ? ldrnseo_sev('ok', 'On') : ldrnseo_sev('na', 'Off'); };

    // The setup board: done, waiting for the client's domain, or to do now.
    $gsc_ok  = $key && $s['gsc_property'];
    $https   = 0 === strpos(home_url(), 'https://');
    $listing = ldrnseo_listings()['google']['url'] ?? '';
    // Every page reachable at its own address, readable without JavaScript, as the last audit found it.
    $own_addr = null;
    if (is_array($audit) && !empty($audit['pages'])) {
        $own_addr = !array_filter($audit['pages'], function ($p) { return 200 !== (int) $p['code'] || empty($p['static_words']); })
                 && empty($audit['issues']['hash_links']);
    }
    // One canonical domain: the server's, with nothing in the rendered page naming another.
    $one_domain = is_array($audit) ? empty($audit['issues']['canonical_conflict']) : null;
    $setup = array(
        array($local ? 'later' : ($https ? 'ok' : 'todo'), 'globe', 'Nursery domain on HTTPS', $local ? ($s['launch_url'] ? esc_html(preg_replace('#^https?://#', '', $s['launch_url'])) : 'at launch') : esc_html((string) wp_parse_url(home_url(), PHP_URL_HOST)), '#setup'),
        array(true === $own_addr ? 'ok' : 'todo', 'route', 'Every page at its own address', null === $own_addr ? 'run the audit' : (true === $own_addr ? count($audit['pages']) . ' pages, readable without JavaScript' : 'see the Audit'), null === $own_addr ? admin_url('admin.php?page=ldrn-seo&tab=audit') : admin_url('admin.php?page=ldrn-seo&tab=audit')),
        array(true === $one_domain ? 'ok' : 'todo', 'link', 'One canonical address per page', null === $one_domain ? 'run the audit' : (true === $one_domain ? 'server and build agree' : 'the build names another domain'), admin_url('admin.php?page=ldrn-seo&tab=audit#issue-canonical_conflict')),
        array('todo', 'shield-check', 'Cookie panel on the site', 'none yet - no visits or GA4 until it exists', '#measurement'),
        array((int) get_option('blog_public', 1) ? 'ok' : 'todo', 'eye', 'Open to search engines', '', '#indexing'),
        array(is_array($audit) && 200 === (int) ($audit['site']['sitemap'] ?? 0) ? 'ok' : 'todo', 'layout-grid', 'Sitemap', count(ldrnseo_routes()) . ' pages', '#indexing'),
        array($gsc_ok ? 'ok' : ($local ? 'later' : 'todo'), 'search', 'Search Console', $gsc_ok ? esc_html($s['gsc_property']) : ($local ? 'on the nursery\'s domain' : ''), '#gsc'),
        array($s['verify_bing'] ? 'ok' : ($local ? 'later' : 'todo'), 'search', 'Bing Webmaster Tools', $s['verify_bing'] ? '' : ($local ? 'on the nursery\'s domain' : ''), '#connections'),
        array($listing ? 'todo' : 'later', 'map-pin', 'Google Business Profile', $listing ? 'on Google Maps - claim and verify it' : 'the nursery claims it', admin_url('admin.php?page=ldrn-seo&tab=business#listings')),
        array($s['psi_key'] ? 'ok' : 'todo', 'gauge', 'PageSpeed Insights key', $s['psi_key'] ? '' : 'for Google\'s own speed tests', '#psi'),
        array($s['measure'] ? 'ok' : 'todo', 'activity', 'Visitor measurement', $s['measure'] ? 'ready - waits for consent' : '', '#measurement'),
        array($s['ga4_id'] ? 'ok' : 'later', 'activity', 'Google Analytics 4', $s['ga4_id'] ? esc_html($s['ga4_id']) : 'the nursery\'s own property', '#measurement'),
        array('later', 'route', 'Old addresses redirected', 'after the move', '#redirects'),
    );
    $done = count(array_filter($setup, function ($x) { return 'ok' === $x[0]; }));

    $nav = array('setup' => array('sparkles', 'Setup'), 'connections' => array('plug', 'Connections'), 'indexing' => array('eye', 'Indexing'),
                 'measurement' => array('activity', 'Measurement'), 'redirects' => array('route', 'Redirects'));
    echo '<div class="is-set">';
    echo '<nav class="is-set-nav" aria-label="Settings sections">';
    foreach ($nav as $id => $n) {
        echo '<a href="#' . esc_attr($id) . '">' . ldrnseo_icon($n[0]) . esc_html($n[1]) . ('setup' === $id ? '<b>' . $done . '/' . count($setup) . '</b>' : '') . '</a>';
    }
    echo '</nav><div class="is-set-main">';

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="is-settings">';
    echo '<input type="hidden" name="action" value="ldrnseo_save_settings"><input type="hidden" name="s[idx_present]" value="1">';
    wp_nonce_field('ldrnseo_save_settings');

    /* ---------------------------------------------------------- Setup */
    echo ldrnseo_section('Setup', '<span><b style="color:var(--text)">' . $done . '</b> of ' . count($setup) . ' in place</span>', 'setup', 'sparkles');
    echo '<div class="is-launch-bar">';
    foreach ($setup as $x) {
        echo '<i class="' . ('ok' === $x[0] ? 'on' : ('later' === $x[0] ? 'later' : 'todo')) . '" title="' . esc_attr($x[2]) . '"></i>';
    }
    echo '</div><div class="is-setup">';
    foreach ($setup as $x) {
        $href = 0 === strpos($x[4], '#') ? $x[4] : esc_url($x[4]);
        echo '<a class="is-setup-item is-' . $x[0] . '" href="' . $href . '"><span class="is-setup-ico">' . ldrnseo_icon(array('ok' => 'check', 'later' => 'clock', 'todo' => 'circle-dot')[$x[0]]) . '</span>'
           . '<span><b>' . esc_html($x[2]) . '</b>' . ($x[3] ? '<small>' . $x[3] . '</small>' : '') . '</span></a>';
    }
    echo '</div>';
    echo '<div class="is-field is-field-inline"><label for="s-launch_url">' . ldrnseo_icon('globe') . 'Public address</label>' . ldrnseo_input('s[launch_url]', $s['launch_url'], $ro + array('id' => 's-launch_url', 'placeholder' => 'https://www.littlerainbowsdaynursery.com')) . '</div>';
    echo '</section>';

    /* ---------------------------------------------------- Connections */
    echo ldrnseo_section('Connections', '', 'connections', 'plug');
    echo '<div class="is-conns">';
    echo '<div class="is-conn is-conn-wide' . ($gsc_ok ? ' is-on' : '') . '" id="gsc"><div class="is-conn-top"><span class="is-conn-logo">' . ldrnseo_brand('Search Console', 22) . '</span>'
       . '<span><b>Search Console</b><small>' . ($key ? esc_html($key['client_email']) : 'queries, positions, indexing') . '</small></span>' . $on($gsc_ok) . '</div>';
    if ($key) {
        $sites = ldrnseo_gsc_sites();
        if (is_wp_error($sites)) {
            echo '<p class="is-flash is-flash-bad" style="margin:0">' . ldrnseo_icon('alert-triangle') . esc_html($sites->get_error_message()) . '</p>';
        } elseif (!$sites) {
            echo '<p class="is-flash is-flash-bad" style="margin:0">' . ldrnseo_icon('alert-triangle') . 'Add ' . esc_html($key['client_email']) . ' as a user in Search Console</p>';
        } else {
            echo '<select name="s[gsc_property]"' . $dis . '><option value="">Property&hellip;</option>';
            foreach ($sites as $site) {
                echo '<option value="' . esc_attr($site['url']) . '"' . selected($s['gsc_property'], $site['url'], false) . '>' . esc_html($site['url']) . '</option>';
            }
            echo '</select>';
        }
    } else {
        echo '<ol class="is-steps"><li><a href="https://search.google.com/search-console" target="_blank" rel="noopener">Verify the nursery domain</a></li>'
           . '<li><a href="https://console.cloud.google.com/apis/library/searchconsole.googleapis.com" target="_blank" rel="noopener">Google Cloud</a>: enable the API, create a service account, download its JSON key</li>'
           . '<li>Search Console &rarr; Users: add the key\'s <code>client_email</code> (Restricted)</li></ol>';
    }
    echo '<textarea name="s[gsc_key]" rows="2" spellcheck="false" placeholder="' . ($key ? 'Paste a new JSON key to replace' : 'Paste the JSON key') . '"' . ($edit ? '' : ' readonly') . '></textarea></div>';

    $conn = function ($id, $brand, $title, $sub, $on_now, $field) use ($on) {
        return '<div class="is-conn' . ($on_now ? ' is-on' : '') . '"' . ($id ? ' id="' . esc_attr($id) . '"' : '') . '><div class="is-conn-top"><span class="is-conn-logo">' . ldrnseo_brand($brand, 22) . '</span><span><b>' . esc_html($title) . '</b><small>' . $sub . '</small></span>' . $on($on_now) . '</div>' . $field . '</div>';
    };
    echo $conn('psi', 'Google', 'PageSpeed Insights', 'Speed tab &middot; Google\'s servers + real-user data', '' !== $s['psi_key'],
               '<input type="password" name="s[psi_key]" autocomplete="off" placeholder="' . ($s['psi_key'] ? '•••••••• saved' : 'API key (AIza…)') . '"' . ($edit ? '' : ' readonly') . '>'
               . ($s['psi_key'] ? '' : '<small><a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud &rarr; Credentials</a> &rarr; the PageSpeed key &rarr; Show key</small>'));
    echo $conn('', 'Bing', 'Bing Webmaster Tools', 'also feeds ChatGPT search and Copilot', '' !== $s['verify_bing'], ldrnseo_input('s[verify_bing]', $s['verify_bing'], $ro + array('placeholder' => 'msvalidate.01 code')));
    echo $conn('', 'Google', 'Google site verification', 'for a URL-prefix property', '' !== $s['verify_google'], ldrnseo_input('s[verify_google]', $s['verify_google'], $ro + array('placeholder' => 'google-site-verification code')));
    echo '</div>';
    echo '</section>';

    /* ------------------------------------------------------- Indexing */
    $pages = ldrnseo_pages_list();
    $blog_public = (int) get_option('blog_public', 1);
    echo ldrnseo_section('Indexing', '<div class="is-files"><a href="' . esc_url(get_sitemap_url('index')) . '" target="_blank" rel="noopener">' . ldrnseo_icon('layout-grid') . 'sitemap</a>'
        . '<a href="' . esc_url(home_url('/robots.txt')) . '" target="_blank" rel="noopener">' . ldrnseo_icon('file-text') . 'robots.txt</a></div>', 'indexing', 'eye');
    if (!$blog_public) {
        echo '<p class="is-flash is-flash-bad">' . ldrnseo_icon('alert-triangle') . 'WordPress is set to discourage search engines (Settings &rarr; Reading). Nothing below is indexed until that is off.</p>';
    }
    $listed = count(array_filter($pages, function ($p) { return !$p['noindex']; }));
    echo '<div class="is-idx"><div class="is-idx-row is-idx-head"><span>Page</span><span>Address</span><span>Canonical</span><span>In search &amp; sitemap</span></div>';
    foreach ($pages as $route => $p) {
        $ap = is_array($audit) ? ($audit['pages'][$route] ?? null) : null;
        $canon_ok = $ap && !empty($ap['canonical']) && untrailingslashit((string) wp_parse_url($ap['canonical'], PHP_URL_PATH)) === untrailingslashit('/' === $route ? '' : $route);
        echo '<div class="is-idx-row"><a href="' . esc_url(ldrnseo_url('pages', array('route' => $route))) . '">' . esc_html(ldrnseo_route_name($route)) . '</a>'
           . '<code>' . esc_html($route) . '</code>'
           . '<span>' . ($ap ? ($canon_ok ? ldrnseo_sev('ok', 'self') : ldrnseo_sev('warning', 'other')) : '') . '</span>'
           . '<label class="is-toggle"><input type="checkbox" name="s[idx][' . esc_attr($route) . ']" value="1"' . checked(!$p['noindex'], true, false) . $dis . '><span class="is-idx-word" data-on="Listed" data-off="Hidden"></span></label></div>';
    }
    echo '</div><p class="is-foot">' . ldrnseo_icon('info') . $listed . ' of ' . count($pages) . ' pages listed. A hidden page gets <code>noindex</code> and leaves the sitemap.</p></section>';

    /* ---------------------------------------------------- Measurement */
    echo ldrnseo_section('Measurement', '', 'measurement', 'activity');
    echo '<div class="is-conns">';
    echo '<div class="is-conn' . ($s['measure'] ? ' is-on' : '') . '"><div class="is-conn-top"><span class="is-conn-logo">' . ldrnseo_icon('activity') . '</span><span><b>First-party analytics</b><small>visits, sources, enquiries, Core Web Vitals &middot; only after consent</small></span>'
       . '<span class="is-toggle"><input type="checkbox" name="s[measure]" value="1"' . checked($s['measure'], 1, false) . $dis . ' aria-label="First-party analytics"></span></div></div>';
    echo $conn('', 'Analytics', 'Google Analytics 4', 'loads after consent', '' !== $s['ga4_id'], ldrnseo_input('s[ga4_id]', $s['ga4_id'], $ro + array('placeholder' => 'G-XXXXXXXXXX')));
    echo $conn('', 'Tag Manager', 'Google Tag Manager', 'Consent Mode v2', '' !== $s['gtm_id'], ldrnseo_input('s[gtm_id]', $s['gtm_id'], $ro + array('placeholder' => 'GTM-XXXXXXX')));
    echo '</div></section>';

    if ($edit) {
        echo '<div class="is-form-actions is-sticky">' . ($key ? '<button type="submit" form="is-gsc-off" class="is-link" style="margin-right:auto">' . ldrnseo_icon('x') . 'Disconnect Search Console</button>' : '<span style="margin-right:auto"></span>')
           . '<button type="submit" class="is-btn is-btn-primary">' . ldrnseo_icon('check') . 'Save settings</button></div>';
    }
    echo '</form>';
    if ($key && $edit) {
        echo '<form id="is-gsc-off" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="ldrnseo_gsc_disconnect">' . wp_nonce_field('ldrnseo_gsc_disconnect', '_wpnonce', true, false) . '</form>';
    }

    ldrnseo_settings_redirects($edit);
    echo '</div></div>';
}

/** Redirects, and - when there are any - the addresses that answered 404. */
function ldrnseo_settings_redirects($edit) {
    global $wpdb;
    $rt   = ldrnseo_table('redirects');
    $nt   = ldrnseo_table('notfound');
    $from = isset($_GET['from']) ? sanitize_text_field(wp_unslash($_GET['from'])) : '';
    $rows = $wpdb->get_results("SELECT * FROM $rt ORDER BY created DESC");
    $hits = array_sum(array_map(function ($r) { return (int) $r->hits; }, $rows));

    echo ldrnseo_section('Redirects', $rows ? '<span><b style="color:var(--text)">' . count($rows) . '</b> rules &middot; <b style="color:var(--text)">' . $hits . '</b> visitors sent on</span>' : '', 'redirects', 'route');
    if ($edit) {
        echo '<form class="is-redirect-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="ldrnseo_redirect"><input type="hidden" name="do" value="add">';
        wp_nonce_field('ldrnseo_redirect');
        echo '<input type="text" name="from" required placeholder="Old address, e.g. /old-page" value="' . esc_attr($from) . '" aria-label="From">'
           . '<span class="is-redirect-arrow">' . ldrnseo_icon('arrow-up-right') . '</span>'
           . '<input type="text" name="to" list="is-routes" placeholder="New address, e.g. /projects/aranya-residence" aria-label="To"><datalist id="is-routes">';
        foreach (ldrnseo_routes() as $r) {
            echo '<option value="' . esc_attr($r) . '">' . esc_html(ldrnseo_route_name($r)) . '</option>';
        }
        echo '</datalist><select name="code" aria-label="Kind"><option value="301">301 &middot; moved for good</option><option value="302">302 &middot; temporary</option><option value="410">410 &middot; gone</option></select>'
           . '<button type="submit" class="is-btn is-btn-primary">' . ldrnseo_icon('check') . 'Add</button></form>';
    }
    if ($rows) {
        echo '<div class="is-rlist">';
        foreach ($rows as $r) {
            echo '<div class="is-rrow"><code>' . esc_html($r->source) . '</code>' . ldrnseo_icon('arrow-up-right')
               . (410 === (int) $r->code ? '<span class="is-muted">gone</span>' : '<code>' . esc_html($r->target) . '</code>')
               . '<span class="is-code-badge' . (410 === (int) $r->code ? ' g410' : '') . '">' . (int) $r->code . '</span>'
               . '<span class="is-nf-hits" title="Times used">' . (int) $r->hits . '</span><small>' . esc_html($r->last_hit ? ldrnseo_ago($r->last_hit) : 'unused') . '</small><span>';
            if ($edit) {
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="ldrnseo_redirect"><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="' . (int) $r->id . '">'
                   . wp_nonce_field('ldrnseo_redirect', '_wpnonce', true, false) . '<button type="submit" class="is-link" title="Remove">' . ldrnseo_icon('x') . '</button></form>';
            }
            echo '</span></div>';
        }
        echo '</div>';
    }
    echo '</section>';

    $all   = $wpdb->get_results("SELECT * FROM $nt WHERE ignored = 0 ORDER BY hits DESC, last_seen DESC LIMIT 500");
    $real  = array_values(array_filter($all, function ($r) { return !ldrnseo_is_probe($r->path); }));
    if (!$real) {
        return;
    }
    $probe = count($all) - count($real);
    echo ldrnseo_section('Not found', '<span>' . ($probe ? ldrnseo_icon('shield-check') . ' ' . (int) $probe . ' scanner probes filtered' : '404 log') . '</span>', 'notfound', 'alert-triangle');
    echo '<div class="is-rlist" style="margin-top:0">';
    foreach ($real as $r) {
        echo '<div class="is-rrow" style="grid-template-columns:minmax(0,1.3fr) minmax(0,1fr) 54px 100px auto"><code>' . esc_html($r->path) . '</code>'
           . '<small>' . ($r->referrer ? ldrnseo_icon('link') . ' ' . esc_html($r->referrer) : 'direct') . '</small>'
           . '<span class="is-nf-hits">' . (int) $r->hits . '</span><small>' . esc_html(ldrnseo_ago($r->last_seen)) . '</small><span style="display:flex;gap:10px;justify-content:flex-end">';
        if ($edit) {
            echo '<a class="is-btn" style="height:30px" href="' . esc_url(ldrnseo_url('settings', array('from' => $r->path, '#' => 'redirects'))) . '">' . ldrnseo_icon('route') . 'Redirect</a>'
               . '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="ldrnseo_404"><input type="hidden" name="do" value="ignore"><input type="hidden" name="path" value="' . esc_attr($r->path) . '">'
               . wp_nonce_field('ldrnseo_404', '_wpnonce', true, false) . '<button type="submit" class="is-link" title="Ignore">' . ldrnseo_icon('x') . '</button></form>';
        }
        echo '</span></div>';
    }
    echo '</div></section>';
}
