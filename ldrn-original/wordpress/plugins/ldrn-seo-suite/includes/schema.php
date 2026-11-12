<?php
/**
 * Structured data - one JSON-LD graph per page.
 *
 * Search engines, maps and AI assistants read this to know, without guessing,
 * that Little Rainbows is an Ofsted-registered day nursery at 64 Pears Road,
 * Hounslow, which company runs it, which ages it takes and when it is open,
 * which page is which, and how the pages sit under one another. Every node is
 * built from what the site already says (the Business tab, the setting record,
 * the article, event and vacancy records, the questions on the page), so the
 * markup can never claim something the page does not show - the rule Google
 * enforces.
 *
 * The page's own node (a nursery setting, an article, an event, a vacancy)
 * comes from ldrn-seo.php's ldrn_seo_entity_schema(), so both layers describe
 * a record the same way. Nodes reference each other by @id.
 */

defined('ABSPATH') || exit;

function ldrnseo_id($frag) {
    return ldrnseo_base() . '/#' . $frag;
}

/** A theme or upload URL back to its file on this server, or ''. */
function ldrnseo_local_file($url) {
    $url = (string) $url;
    foreach (array(array(get_template_directory_uri(), get_template_directory()), array(content_url(), WP_CONTENT_DIR), array(home_url(), untrailingslashit(ABSPATH))) as $pair) {
        if ('' !== $url && 0 === strpos($url, $pair[0])) {
            $path = $pair[1] . substr((string) strtok($url, '?'), strlen($pair[0]));
            return is_readable($path) ? $path : '';
        }
    }
    return '';
}

/** The logo search engines show: chosen in the Business tab, or the rainbow mark from the build. */
function ldrnseo_logo() {
    $b = ldrnseo_business();
    if ($b['logo_id'] && ($src = wp_get_attachment_image_src((int) $b['logo_id'], 'full'))) {
        return array('url' => $src[0], 'width' => (int) $src[1], 'height' => (int) $src[2]);
    }
    foreach (array('assets/logo/logo-rainbow-arc-mark.jpg', 'assets/logo/logo-primary-navy-wordmark.png') as $file) {
        $path = get_template_directory() . '/dist/' . $file;
        if (is_readable($path) && ($size = @getimagesize($path))) {
            return array('url' => get_template_directory_uri() . '/dist/' . $file, 'width' => (int) $size[0], 'height' => (int) $size[1]);
        }
    }
    return array('url' => LDRNSEO_URL . 'assets/brand/logo-square.png', 'width' => 512, 'height' => 512);
}

/**
 * The share picture for an address: the page's own choice on the Pages tab,
 * then the picture the route table or the record gives it (when that file
 * really exists), then the nursery's default, then the home photograph.
 */
function ldrnseo_share_image($route, $from_meta = '') {
    $page = ldrnseo_page($route);
    $b    = ldrnseo_business();
    if ($page['image_id'] && ($src = wp_get_attachment_image_src($page['image_id'], 'full'))) {
        return array('url' => $src[0], 'width' => (int) $src[1], 'height' => (int) $src[2],
                     'alt' => (string) get_post_meta($page['image_id'], '_wp_attachment_image_alt', true));
    }
    if ('' === $from_meta) {
        $raw = ldrnseo_static_routes_raw();
        $from_meta = (string) ($raw[$route]['image'] ?? '');
        if ('' === $from_meta && ($post = ldrnseo_record_for($route))) {
            $from_meta = 'ldrn_setting' === $post->post_type ? (string) get_post_meta($post->ID, '_ldrn_setting_hero_image', true) : (string) get_the_post_thumbnail_url($post->ID, 'full');
        }
    }
    $candidates = array($from_meta);
    if ($b['share_id'] && ($src = wp_get_attachment_image_src((int) $b['share_id'], 'full'))) {
        $candidates[] = $src[0];
    }
    $candidates[] = get_template_directory_uri() . '/dist/assets/photography/hero-practitioner-and-child-playdough.png';
    foreach ($candidates as $url) {
        if ('' === (string) $url) {
            continue;
        }
        $file = ldrnseo_local_file($url);
        if ($file || !preg_match('#^' . preg_quote(home_url(), '#') . '#', $url)) {
            $size = $file ? @getimagesize($file) : false;
            return array('url' => $url, 'width' => $size ? (int) $size[0] : 0, 'height' => $size ? (int) $size[1] : 0, 'alt' => '');
        }
    }
    return array('url' => (string) $from_meta, 'width' => 0, 'height' => 0, 'alt' => '');
}

/** The photograph a page leads with (the editor's header). */
function ldrnseo_page_photo($route) {
    return ldrnseo_share_image($route)['url'];
}

function ldrnseo_org_node() {
    $b    = ldrnseo_business();
    $logo = ldrnseo_logo();
    $node = array(
        '@type'       => array_values(array_unique(array('Organization', $b['type'] ?: 'ChildCare'))),
        '@id'         => ldrnseo_id('organization'),
        'name'        => $b['name'],
        'url'         => ldrnseo_base() . '/',
        'logo'        => array('@type' => 'ImageObject', '@id' => ldrnseo_id('logo'), 'url' => $logo['url'],
                               'contentUrl' => $logo['url'], 'width' => $logo['width'], 'height' => $logo['height'], 'caption' => $b['name']),
        'image'       => array('@id' => ldrnseo_id('logo')),
    );
    if ($b['legal_name'])  { $node['legalName'] = $b['legal_name']; }
    if ($b['alt_name'])    { $node['alternateName'] = $b['alt_name']; }
    if ($b['slogan'])      { $node['slogan'] = $b['slogan']; }
    if ($b['description']) { $node['description'] = $b['description']; }
    if ($b['email'])       { $node['email'] = $b['email']; }
    $phones = ldrnseo_lines($b['phones']);
    if ($phones) {
        $node['telephone'] = ldrnseo_e164($phones[0]);
        $node['contactPoint'] = array_map(function ($p) use ($b) {
            $cp = array('@type' => 'ContactPoint', 'telephone' => ldrnseo_e164($p), 'contactType' => 'admissions',
                        'areaServed' => 'GB', 'availableLanguage' => array('English'));
            if ($b['email']) { $cp['email'] = $b['email']; }
            return $cp;
        }, $phones);
    }
    if ($b['street'] || $b['locality']) {
        $node['address'] = array_filter(array(
            '@type' => 'PostalAddress', 'streetAddress' => $b['street'], 'addressLocality' => $b['locality'],
            'addressRegion' => $b['region'], 'postalCode' => $b['postcode'], 'addressCountry' => $b['country'],
        ));
    }
    if (is_numeric($b['lat']) && is_numeric($b['lng'])) {
        $node['geo'] = array('@type' => 'GeoCoordinates', 'latitude' => (float) $b['lat'], 'longitude' => (float) $b['lng']);
    }
    if ($b['maps_url']) { $node['hasMap'] = $b['maps_url']; }
    $areas = ldrnseo_lines($b['areas']);
    if ($areas) {
        $node['areaServed'] = array_map(function ($a) { return array('@type' => 'Place', 'name' => $a); }, $areas);
    }
    if ($b['hours'])       { $node['openingHours'] = ldrnseo_lines(str_replace(',', "\n", $b['hours'])); }
    if ($b['founded'])     { $node['foundingDate'] = $b['founded']; }
    if ($b['price_range']) { $node['priceRange'] = $b['price_range']; }
    if ($b['places'] && ctype_digit((string) $b['places'])) {
        $node['maximumAttendeeCapacity'] = (int) $b['places'];
    }
    // The registration parents and Google check: the Ofsted URN, and the company.
    $ids = array();
    if ($b['ofsted_urn']) {
        $ids[] = array('@type' => 'PropertyValue', 'propertyID' => 'Ofsted URN', 'value' => $b['ofsted_urn']);
    }
    if ($b['company_no']) {
        $ids[] = array('@type' => 'PropertyValue', 'propertyID' => 'Companies House number', 'value' => $b['company_no']);
    }
    if ($ids) { $node['identifier'] = $ids; }
    if ($b['ofsted_urn'] && $b['ofsted_grade']) {
        $node['hasCredential'] = array_filter(array(
            '@type' => 'EducationalOccupationalCredential', 'name' => 'Ofsted inspection: ' . $b['ofsted_grade'],
            'credentialCategory' => 'Ofsted early years inspection', 'dateCreated' => $b['ofsted_date'] ?: null,
            'recognizedBy' => array('@type' => 'GovernmentOrganization', 'name' => 'Ofsted', 'url' => 'https://www.gov.uk/government/organisations/ofsted'),
            'url' => 'https://reports.ofsted.gov.uk/provider/16/' . rawurlencode($b['ofsted_urn']),
        ));
    }
    if ($b['founder']) {
        $node['founder'] = array('@id' => ldrnseo_id('founder'));
    }
    $same = ldrnseo_same_as();
    if ($same) { $node['sameAs'] = $same; }
    $node['knowsAbout'] = array('Early Years Foundation Stage', 'Learning through play', 'Childcare', 'Funded childcare');
    return $node;
}

/** "020 4619 6231" as +442046196231. */
function ldrnseo_e164($phone) {
    $d = preg_replace('/[^\d+]/', '', (string) $phone);
    if (0 === strpos($d, '+')) {
        return $d;
    }
    return 0 === strpos($d, '0') ? '+44' . substr($d, 1) : $d;
}

function ldrnseo_founder_node() {
    $b = ldrnseo_business();
    if (!$b['founder']) {
        return null;
    }
    $node = array('@type' => 'Person', '@id' => ldrnseo_id('founder'), 'name' => $b['founder'],
                  'worksFor' => array('@id' => ldrnseo_id('organization')));
    if ($b['founder_role']) { $node['jobTitle'] = $b['founder_role']; }
    if ($b['founder_url'])  { $node['sameAs'] = array($b['founder_url']); }
    return $node;
}

function ldrnseo_website_node() {
    $b = ldrnseo_business();
    return array('@type' => 'WebSite', '@id' => ldrnseo_id('website'), 'url' => ldrnseo_base() . '/',
                 'name' => $b['name'], 'alternateName' => $b['alt_name'] ?: null,
                 'inLanguage' => 'en-GB', 'publisher' => array('@id' => ldrnseo_id('organization')));
}

/**
 * The whole graph for one request.
 *
 * @param array $meta  ldrn-seo.php's metadata for the page: path, title,
 *                     description, canonical, image, entity, post
 */
function ldrnseo_graph(array $meta) {
    $route = (string) $meta['path'];
    $url   = (string) ($meta['canonical'] ?? ldrnseo_abs($route));
    $share = ldrnseo_share_image($route, (string) ($meta['image'] ?? ''));
    $facts = ldrnseo_facts($route);
    $types = array('/about' => 'AboutPage', '/about/ofsted' => 'AboutPage', '/contact' => 'ContactPage', '/our-nursery' => 'CollectionPage',
                   '/blogs' => 'CollectionPage', '/events' => 'CollectionPage', '/careers/vacancies' => 'CollectionPage', '/testimonials' => 'CollectionPage');
    $graph = array(ldrnseo_org_node(), ldrnseo_website_node());
    if ($f = ldrnseo_founder_node()) {
        $graph[] = $f;
    }

    $webpage = array(
        '@type'      => $types[$route] ?? 'WebPage',
        '@id'        => $url . '#webpage',
        'url'        => $url,
        'name'       => (string) $meta['title'],
        'isPartOf'   => array('@id' => ldrnseo_id('website')),
        'about'      => array('@id' => ldrnseo_id('organization')),
        'inLanguage' => 'en-GB',
        'primaryImageOfPage' => array_filter(array('@type' => 'ImageObject', 'url' => $share['url'], 'width' => $share['width'] ?: null, 'height' => $share['height'] ?: null)),
    );
    if (!empty($meta['description'])) {
        $webpage['description'] = $meta['description'];
    }
    if ($t = ldrnseo_lastmod($route)) {
        $webpage['dateModified'] = gmdate('c', $t);
    }

    // Breadcrumbs: Home > Our Nursery > Hounslow.
    if ('/' !== $route) {
        $crumbs = array(array('Home', ldrnseo_base() . '/'));
        $parts  = explode('/', trim($route, '/'));
        $walk   = '';
        foreach ($parts as $i => $part) {
            $walk .= '/' . $part;
            if ($i === count($parts) - 1) {
                $crumbs[] = array($facts['h1'] && 'page' !== ldrnseo_page($route)['kind'] ? $facts['h1'] : (ldrnseo_route_label($route) ?: ldrnseo_page($route)['label']), $url);
            } elseif (in_array($walk, ldrnseo_routes(), true)) {
                $crumbs[] = array(ldrnseo_route_label($walk) ?: ldrnseo_page($walk)['label'], ldrnseo_abs($walk));
            }
        }
        $webpage['breadcrumb'] = array('@id' => $url . '#breadcrumb');
        $graph[] = array('@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => array_map(function ($c, $i) {
            return array('@type' => 'ListItem', 'position' => $i + 1, 'name' => wp_strip_all_tags($c[0]), 'item' => $c[1]);
        }, $crumbs, array_keys($crumbs)));
    }

    // Lists: the nurseries, articles, events and vacancies each list page shows.
    $lists = array('/our-nursery' => 'ldrn_setting', '/blogs' => 'post', '/events' => 'ldrn_event', '/careers/vacancies' => 'ldrn_job');
    if (isset($lists[$route])) {
        $items = array();
        $i = 0;
        foreach (ldrnseo_records() as $r => $post) {
            if ($lists[$route] === $post->post_type) {
                $items[] = array('@type' => 'ListItem', 'position' => ++$i, 'url' => ldrnseo_abs($r), 'name' => wp_strip_all_tags(get_the_title($post)));
            }
        }
        if ($items) {
            $webpage['mainEntity'] = array('@type' => 'ItemList', 'itemListElement' => $items);
        }
    }

    // The record itself, described the way ldrn-seo.php describes it.
    if (!empty($meta['entity']) && function_exists('ldrn_seo_entity_schema')) {
        $entity = ldrn_seo_entity_schema($meta);
        if ($entity) {
            if ('setting' === $meta['entity']) {
                $entity['parentOrganization'] = array('@id' => ldrnseo_id('organization'));
                $entity['openingHours'] = ldrnseo_lines(str_replace(',', "\n", ldrnseo_business()['hours'])) ?: null;
            }
            $webpage['mainEntity'] = array('@id' => $entity['@id']);
            $graph[] = $entity;
        }
    }

    // The questions answered on the page itself.
    if (!empty($facts['faqs'])) {
        $graph[] = array('@type' => 'FAQPage', '@id' => $url . '#faq', 'isPartOf' => array('@id' => $url . '#webpage'),
            'mainEntity' => array_map(function ($f) {
                return array('@type' => 'Question', 'name' => $f['question'],
                             'acceptedAnswer' => array('@type' => 'Answer', 'text' => $f['answer']));
            }, $facts['faqs']));
    }

    array_splice($graph, 2, 0, array($webpage));
    return array('@context' => 'https://schema.org', '@graph' => ldrnseo_prune($graph));
}

/** Drop nulls and empty strings, so a field left blank is simply not claimed. */
function ldrnseo_prune($v) {
    if (!is_array($v)) {
        return $v;
    }
    $is_list = $v && array_keys($v) === range(0, count($v) - 1);
    $out = array();
    foreach ($v as $k => $x) {
        $x = ldrnseo_prune($x);
        if (null === $x || '' === $x || (is_array($x) && !$x)) {
            continue;
        }
        $out[$k] = $x;
    }
    return $is_list ? array_values($out) : $out;
}
