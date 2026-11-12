<?php
/**
 * Business - who Little Rainbows Day Nursery is, said once and published everywhere.
 *
 * Left: the facts. Right: how Google's business panel would show them,
 * redrawn as they are typed - a missing field shows as a gap in the panel.
 * Below: the listings off the site, each checked against these facts.
 * These facts become the structured data on every page and llms.txt.
 */

defined('ABSPATH') || exit;

function ldrnseo_business_types() {
    return array('ChildCare' => 'Childcare (day nursery)', 'Preschool' => 'Preschool', 'LocalBusiness' => 'Local business');
}

function ldrnseo_gbp_categories() {
    // Google Business Profile category names that fit a day nursery.
    return array('Day care center', 'Nursery school', 'Preschool', 'Child care agency', 'Kindergarten', 'Playgroup');
}

function ldrnseo_screen_business() {
    $b    = ldrnseo_business();
    $edit = ldrnseo_can_edit();
    $ro   = $edit ? array() : array('readonly' => 'readonly');
    $in   = function ($k, $attrs = array()) use ($b, $ro) { return ldrnseo_input('b[' . $k . ']', (string) $b[$k], $attrs + $ro + array('id' => 'b-' . $k, 'data-b' => $k)); };
    $ta   = function ($k, $rows = 3) use ($b, $edit) { return '<textarea id="b-' . $k . '" data-b="' . $k . '" name="b[' . $k . ']" rows="' . $rows . '"' . ($edit ? '' : ' readonly') . '>' . esc_textarea($b[$k]) . '</textarea>'; };
    $listings = ldrnseo_listings();
    $audit    = get_option('ldrnseo_audit');
    $nap      = is_array($audit) ? ($audit['site']['nap'] ?? array()) : array();

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="is-business">';
    echo '<input type="hidden" name="action" value="ldrnseo_save_business">';
    wp_nonce_field('ldrnseo_save_business');
    echo '<div class="is-biz"><div style="display:grid;gap:18px">';

    echo ldrnseo_section('Identity', '', '', 'building-2');
    echo '<div class="is-field-row">' . ldrnseo_field('Name', $in('name'), '', 'b-name') . ldrnseo_field('Also known as', $in('alt_name'), '', 'b-alt_name') . '</div>';
    $sel = '<select id="b-type" name="b[type]"' . ($edit ? '' : ' disabled') . '>';
    foreach (ldrnseo_business_types() as $k => $label) {
        $sel .= '<option value="' . esc_attr($k) . '"' . selected($b['type'], $k, false) . '>' . esc_html($label) . '</option>';
    }
    $sel .= '</select>';
    $cats = '<datalist id="is-gbp-cats">' . implode('', array_map(function ($c) { return '<option value="' . esc_attr($c) . '">'; }, ldrnseo_gbp_categories())) . '</datalist>';
    echo '<div class="is-field-row">' . ldrnseo_field('Google category', $in('gbp_category', array('list' => 'is-gbp-cats')) . $cats, '', 'b-gbp_category') . ldrnseo_field('Schema type', $sel, '', 'b-type') . '</div>';
    echo '<div class="is-field-row">' . ldrnseo_field('Slogan', $in('slogan'), '', 'b-slogan') . ldrnseo_field('Registered company', $in('legal_name', array('placeholder' => 'As at Companies House')), '', 'b-legal_name') . '</div>';
    echo ldrnseo_field('One-line description', $ta('description', 4), '', 'b-description');
    echo '<div class="is-field-row">';
    echo ldrnseo_field('Logo', '<div class="is-thumbpick"><input type="hidden" name="b[logo_id]" value="' . (int) $b['logo_id'] . '" data-image-id>' . ldrnseo_thumb(ldrnseo_logo()['url'])
         . ($edit ? '<button type="button" class="is-btn" data-pick-image>' . ldrnseo_icon('image') . 'Change</button>' : '') . '</div>');
    echo ldrnseo_field('Default share image', '<div class="is-thumbpick"><input type="hidden" name="b[share_id]" value="' . (int) $b['share_id'] . '" data-image-id>'
         . ldrnseo_thumb($b['share_id'] ? (string) wp_get_attachment_image_url($b['share_id'], 'medium') : ldrnseo_share_image('/')['url'])
         . ($edit ? '<button type="button" class="is-btn" data-pick-image>' . ldrnseo_icon('image') . 'Change</button>' : '') . '</div>');
    echo '</div></section>';

    echo ldrnseo_section('Contact &amp; place', '', '', 'map-pin');
    echo '<div class="is-field-row">' . ldrnseo_field('Email', $in('email', array('type' => 'email')), '', 'b-email') . ldrnseo_field('Phones', $ta('phones', 2), '', 'b-phones') . '</div>';
    echo ldrnseo_field('Street', $in('street'), '', 'b-street');
    echo '<div class="is-field-row">' . ldrnseo_field('City', $in('locality'), '', 'b-locality') . ldrnseo_field('Region', $in('region'), '', 'b-region') . ldrnseo_field('Postcode', $in('postcode'), '', 'b-postcode') . ldrnseo_field('Country', $in('country', array('maxlength' => 2)), '', 'b-country') . '</div>';
    echo '<div class="is-field-row">' . ldrnseo_field('Latitude', $in('lat'), '', 'b-lat') . ldrnseo_field('Longitude', $in('lng'), '', 'b-lng') . ldrnseo_field('Maps link', $in('maps_url'), '', 'b-maps_url') . '</div>';
    echo '<div class="is-field-row">' . ldrnseo_field('Opening hours', $in('hours', array('placeholder' => 'Mo-Fr 08:00-18:00')), '', 'b-hours') . ldrnseo_field('Founded', $in('founded', array('placeholder' => '2005')), '', 'b-founded') . ldrnseo_field('Price range', $in('price_range', array('placeholder' => 'once fees are confirmed')), '', 'b-price_range') . '</div>';
    echo ldrnseo_field('Areas served', $ta('areas', 3), '', 'b-areas');
    echo '</section>';

    echo ldrnseo_section('Registration', '<a class="is-link" href="' . esc_url('https://reports.ofsted.gov.uk/provider/16/' . rawurlencode($b['ofsted_urn'])) . '" target="_blank" rel="noopener">Ofsted report ' . ldrnseo_icon('arrow-up-right') . '</a>', '', 'shield-check');
    echo '<div class="is-field-row">' . ldrnseo_field('Ofsted URN', $in('ofsted_urn'), '', 'b-ofsted_urn') . ldrnseo_field('Latest grade', $in('ofsted_grade'), '', 'b-ofsted_grade') . ldrnseo_field('Inspected', $in('ofsted_date', array('placeholder' => 'YYYY-MM-DD')), '', 'b-ofsted_date') . '</div>';
    echo '<div class="is-field-row">' . ldrnseo_field('Ages', $in('ages'), '', 'b-ages') . ldrnseo_field('Places', $in('places'), '', 'b-places') . ldrnseo_field('Company number', $in('company_no'), '', 'b-company_no') . '</div>';
    echo '</section>';

    echo ldrnseo_section('Manager', '<span>' . ldrnseo_icon('star') . ' E-E-A-T &middot; only once the nursery names them</span>', '', 'users');
    echo '<div class="is-field-row">' . ldrnseo_field('Name', $in('founder'), '', 'b-founder') . ldrnseo_field('Role', $in('founder_role'), '', 'b-founder_role') . ldrnseo_field('Profile', $in('founder_url'), '', 'b-founder_url') . '</div>';
    echo ldrnseo_field('Other profiles of the business', $ta('profiles', 2), '', 'b-profiles');
    echo '</section></div>';

    // The panel.
    $phones = ldrnseo_lines($b['phones']);
    $social = array();
    foreach (array('instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn') as $k => $m) {
        if ($listings[$k]['url']) {
            $social[] = '<span title="' . esc_attr($m) . '">' . ldrnseo_brand($m, 16) . '</span>';
        }
    }
    $pin = '<svg viewBox="0 0 30 40"><path d="M15 0C6.7 0 0 6.6 0 14.8 0 25.9 15 40 15 40s15-14.1 15-25.2C30 6.6 23.3 0 15 0z" fill="#EA4335"/><circle cx="15" cy="14.5" r="5.5" fill="#A50E0E"/></svg>';
    echo '<aside class="is-biz-side"><div class="is-kp"><div class="is-kp-map"><span class="is-kp-pin">' . $pin . '</span></div><div class="is-kp-body">'
       . '<div class="is-kp-head"><span class="is-kp-logo"><img src="' . esc_url(ldrnseo_logo()['url']) . '" alt=""></span><div><div class="is-kp-name" data-kp="name">' . esc_html($b['name']) . '</div>'
       . '<div class="is-kp-type"><span data-kp="gbp_category" data-empty="Day nursery">' . esc_html($b['gbp_category'] ?: 'Day nursery') . '</span> in <span data-kp="locality">' . esc_html($b['locality']) . '</span></div></div></div>'
       . '<div class="is-kp-actions"><span>' . ldrnseo_icon('globe') . 'Website</span><span>' . ldrnseo_icon('map-pin') . 'Directions</span><span>' . ldrnseo_icon('phone') . 'Call</span></div>'
       . '<div class="is-kp-row">' . ldrnseo_icon('map-pin') . '<span><b data-kp="street">' . esc_html($b['street']) . '</b>, <span data-kp="locality">' . esc_html($b['locality']) . '</span> <span data-kp="postcode">' . esc_html($b['postcode']) . '</span></span></div>'
       . '<div class="is-kp-row' . ($b['hours'] ? '' : ' is-missing') . '" data-kp-row="hours">' . ldrnseo_icon('clock') . '<span><b data-kp="hours" data-empty="Add opening hours">' . esc_html($b['hours'] ?: 'Add opening hours') . '</b></span></div>'
       . '<div class="is-kp-row">' . ldrnseo_icon('phone') . '<span><b data-kp="phone">' . esc_html($phones[0] ?? '') . '</b></span></div>'
       . '<div class="is-kp-row">' . ldrnseo_icon('globe') . '<span><b>' . esc_html((string) wp_parse_url(home_url(), PHP_URL_HOST)) . '</b></span></div>'
       . '<div class="is-kp-row">' . ldrnseo_icon('shield-check') . '<span>Ofsted: <b data-kp="ofsted_grade">' . esc_html($b['ofsted_grade']) . '</b> &middot; URN <span data-kp="ofsted_urn">' . esc_html($b['ofsted_urn']) . '</span></span></div>'
       . '<div class="is-kp-row">' . ldrnseo_icon('users') . '<span>Ages <b data-kp="ages">' . esc_html($b['ages']) . '</b></span></div>'
       . ($social ? '<div class="is-kp-social">' . implode('', $social) . '</div>' : '')
       . '</div></div><small>' . ldrnseo_brand('Google', 12) . ' preview of the business panel</small>';
    if ($edit) {
        echo '<button type="submit" class="is-btn is-btn-primary" style="justify-content:center">' . ldrnseo_icon('check') . 'Save business</button>';
    }
    echo '</aside></div>';

    // Listings.
    $marks = array('google' => 'Maps', 'bing' => 'Bing', 'apple' => 'Apple', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn');
    $done = count(array_filter($listings, function ($l) { return '' !== $l['url']; }));
    echo '<div style="height:18px"></div>';
    echo ldrnseo_section('Listings', '<span><b style="color:var(--text);font-size:15px">' . $done . '</b> / ' . count($listings) . ' live</span>', 'listings', 'map-pin');
    $tile = function ($key, $def) use ($listings, $nap, $marks, $ro) {
        $l = $listings[$key];
        $warn = array_values(array_filter($nap, function ($f) use ($def) { return '*' === $f['route'] && 0 === strpos($f['detail'], $def[0] . ' '); }));
        $o = '<div class="is-tile' . ($l['url'] ? ($warn ? ' is-warn' : ' is-on') : '') . '" title="' . esc_attr($def[2]) . '"><div class="is-tile-top">' . ldrnseo_brand($marks[$key] ?? $def[0], 18) . esc_html($def[0]) . '<i class="is-tile-state"></i></div>'
           . ldrnseo_input('l[' . $key . '][url]', $l['url'], $ro + array('placeholder' => 'https://', 'aria-label' => $def[0]))
           . ('social' !== $def[1] ? ldrnseo_input('l[' . $key . '][as]', $l['as'], $ro + array('placeholder' => 'Name, address as listed', 'aria-label' => 'As listed on ' . $def[0])) : '');
        foreach ($warn as $f) {
            $o .= '<span class="is-warn-line">' . ldrnseo_icon('alert-triangle') . esc_html(preg_replace('/^' . preg_quote($def[0], '/') . ' /', '', $f['detail'])) . '</span>';
        }
        return $o . '</div>';
    };
    $live = '';
    $empty = array('map' => '', 'official' => '', 'directory' => '', 'social' => '');
    foreach (ldrnseo_listing_defs() as $key => $def) {
        if ('' !== $listings[$key]['url']) {
            $live .= $tile($key, $def);
        } else {
            $empty[$def[1]] .= $tile($key, $def);
        }
    }
    if ($live) {
        echo '<div class="is-tiles">' . $live . '</div>';
    }
    if (array_filter($empty)) {
        echo '<details class="is-addmore"' . ($live ? '' : ' open') . '><summary>' . ldrnseo_icon('map-pin') . 'Add a listing <b>' . (count($listings) - $done) . '</b></summary>';
        foreach (array('map' => 'Maps', 'official' => 'Official records', 'directory' => 'Childcare directories', 'social' => 'Profiles') as $g => $gtitle) {
            if ($empty[$g]) {
                echo '<h4 class="is-group-label">' . esc_html($gtitle) . '</h4><div class="is-tiles">' . $empty[$g] . '</div>';
            }
        }
        echo '</details>';
    }
    echo '</section>';
    if ($edit) {
        echo '<div class="is-form-actions" style="margin-top:18px;justify-content:flex-end"><button type="submit" class="is-btn is-btn-primary">' . ldrnseo_icon('check') . 'Save</button></div>';
    }
    echo '</form>';

    // What search engines read.
    $graph = ldrnseo_graph(array('path' => '/', 'canonical' => ldrnseo_abs('/'), 'title' => ldrnseo_business()['name'], 'description' => ldrnseo_business()['description'], 'image' => '', 'entity' => '', 'post' => null));
    $types = array();
    foreach ($graph['@graph'] as $n) {
        $types[] = implode(' + ', (array) $n['@type']);
    }
    echo '<div style="height:18px"></div>';
    echo ldrnseo_section('Structured data', '<a class="is-link" href="' . esc_url('https://search.google.com/test/rich-results?url=' . rawurlencode(ldrnseo_public_url())) . '" target="_blank" rel="noopener">' . ldrnseo_brand('Google', 13) . 'Rich Results Test ' . ldrnseo_icon('arrow-up-right') . '</a>'
        . '<a class="is-link" href="https://validator.schema.org/#url=' . esc_attr(rawurlencode(ldrnseo_public_url())) . '" target="_blank" rel="noopener">Schema.org validator ' . ldrnseo_icon('arrow-up-right') . '</a>', '', 'code-xml');
    echo '<div class="is-schema-types">' . implode('', array_map(function ($t) { return '<span class="is-chip is-chip-kp">' . ldrnseo_icon('code-xml') . esc_html($t) . '</span>'; }, $types))
       . '<span class="is-chip">+ ChildCare (each setting) &middot; BlogPosting &middot; Event &middot; JobPosting &middot; BreadcrumbList &middot; ItemList on their pages</span></div>';
    echo '<details class="is-outline"><summary>JSON-LD</summary><pre class="is-code">' . esc_html(wp_json_encode($graph, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</pre></details></section>';
}

function ldrnseo_thumb($url) {
    return $url ? '<img class="is-thumb" src="' . esc_url($url) . '" alt="">' : '';
}
