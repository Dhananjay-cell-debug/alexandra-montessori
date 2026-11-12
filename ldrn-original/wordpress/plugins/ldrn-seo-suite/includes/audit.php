<?php
/**
 * The crawl audit.
 *
 * Every address in the sitemap is read twice. The server's answer is fetched
 * the way a crawler fetches it (no login, no cookies): status, headers, time
 * to first byte, and the <head> every crawler reads. Then - because this site
 * draws its pages in the browser - the screen opens the page in a real browser
 * frame, lets it render, and hands the rendered document over: headings,
 * words, images and links are judged on what Google's renderer sees, not on
 * the empty shell. The last rendering of each page is kept, so the weekly run
 * (which has no browser) still judges real content. Nothing is estimated from
 * settings: each finding is something the site actually answered or showed.
 *
 * Severity follows the Ahrefs Site Audit convention - Errors stop a page
 * being found or understood; Warnings cost rankings or clicks; Notices are
 * worth knowing - and the health score is Ahrefs' too: the share of pages
 * with no Errors.
 *
 * It runs a page at a time from the screen (so a slow page never times out
 * the request) and once a week on its own, keeping a history of the score.
 */

defined('ABSPATH') || exit;

const LDRNSEO_UA = 'Mozilla/5.0 (compatible; LDRN-SEO-Audit/1.0)';

/* -------------------------------------------------------------------------
 * What can be wrong, why it matters, and what to do.
 * ---------------------------------------------------------------------- */

function ldrnseo_issue_catalog() {
    $e = 'error'; $w = 'warning'; $n = 'notice';
    return array(
        // Indexability
        'http_error'      => array($e, 'Indexability', 'Page does not answer 200', 'A page in the sitemap that fails cannot be indexed, and a sitemap full of failures makes Google trust the sitemap less.', 'Fix the page, or hide it in the page list of the Site Editor (a hidden page leaves the sitemap on its own).'),
        'noindex'         => array($w, 'Indexability', 'Page is set to noindex', 'Google is told not to list it. Right for a thank-you page; wrong for anything you want found.', 'Untick "Keep out of search" on the Pages tab if it should be found.'),
        'canonical_miss'  => array($e, 'Indexability', 'No canonical address', 'Without one, the same page reachable at two addresses (with and without a query string) splits its ranking signals.', 'The SEO plugin writes it; this means the page is not passing through WordPress.'),
        'canonical_conflict' => array($e, 'Indexability', 'Two different canonicals once the page renders', 'The server names one canonical address and the React build adds a second, different one in the browser. Google renders the page, sees both, and may ignore both - or index the other domain.', 'In the build, make <Seo /> use the WordPress address (window.ldrnData) instead of a fixed SITE_URL, or drop its canonical tag.'),
        'sitemap_bad'     => array($w, 'Indexability', 'Sitemap lists addresses that are not pages', 'Every address in the sitemap should answer 200 itself. Redirects and errors in it waste crawls and make Google trust the sitemap less.', 'Remove them from ldrn_seo_sitemap_entries() in ldrn-seo.php.'),
        'canonical_other' => array($w, 'Indexability', 'Canonical points elsewhere', 'The page asks Google to index a different address instead of itself.', 'Expected only for duplicates. Otherwise check the route table in ldrn-seo.php and the redirects.'),
        'soft_404'        => array($e, 'Indexability', 'Missing pages answer 200', 'A made-up address should answer 404. Answering 200 ("soft 404") fills the index with empty pages.', 'Check the theme\'s not-found handling.'),
        'robots_block'    => array($e, 'Indexability', 'robots.txt blocks the whole site', 'Every crawler is turned away before it reads a page.', 'Replace the robots.txt file in the web root, or untick "Discourage search engines" in Settings > Reading (Site Settings > "Let Google find this website"). Remove any robots.txt file left in the web root.'),
        'wp_private'      => array($e, 'Indexability', 'WordPress discourages search engines', 'Settings > Reading has "Discourage search engines from indexing this site" ticked.', 'Untick it at launch.'),
        'sitemap_missing' => array($e, 'Indexability', 'Sitemap does not load', 'The sitemap is how Google learns every address, including service pages no menu links to.', 'Check /sitemap.xml (written by ldrn-seo.php).'),
        // Titles & descriptions
        'title_missing'   => array($e, 'Titles & descriptions', 'Title missing', 'The title is the blue link in the result. Without one Google invents it.', 'Write one on the Pages tab.'),
        'title_long'      => array($w, 'Titles & descriptions', 'Title will be cut off', 'Google shows about 600 pixels (roughly 60 characters). The end - often the brand - disappears.', 'Shorten it; put the words people search for first.'),
        'title_short'     => array($n, 'Titles & descriptions', 'Title is short', 'Under 30 characters leaves room unused that could say what the page offers and where.', 'Add the service and the place (e.g. "in Hounslow").'),
        'title_dup'       => array($w, 'Titles & descriptions', 'Same title on several pages', 'Google cannot tell which page to show, and may rewrite both.', 'Give each page its own title.'),
        'desc_missing'    => array($w, 'Titles & descriptions', 'Description missing', 'Google will pick a random sentence from the page as the snippet.', 'Write one on the Pages tab.'),
        'desc_long'       => array($n, 'Titles & descriptions', 'Description will be cut off', 'Google shows about 155-160 characters on desktop, fewer on phones.', 'Shorten it; keep the reason to click in the first 120 characters.'),
        'desc_short'      => array($n, 'Titles & descriptions', 'Description is short', 'Under 70 characters rarely persuades; Google often replaces short descriptions.', 'Say what the page offers parents, and where.'),
        'desc_dup'        => array($w, 'Titles & descriptions', 'Same description on several pages', 'Duplicate snippets make results look interchangeable.', 'Write one per page.'),
        // Headings & content
        'h1_missing'      => array($e, 'Headings & content', 'No H1 heading', 'The H1 tells search engines and screen readers what the page is about.', 'Add one main heading to the page.'),
        'h1_multi'        => array($w, 'Headings & content', 'More than one H1', 'Two main headings blur what the page is about.', 'Keep one H1; make the others H2.'),
        'heading_skip'    => array($n, 'Headings & content', 'Heading levels skip', 'An H2 followed by an H4 breaks the outline assistive technology and crawlers read.', 'Use levels in order.'),
        'thin'            => array($w, 'Headings & content', 'Little text on a page meant to rank', 'A nursery, article or event page competes for searches like "nursery in Hounslow". Under 150 words gives Google little to rank it on.', 'Add what parents ask: ages, sessions, a typical day, the rooms and garden, how to visit (Website Content screens).'),
        'js_only'         => array($e, 'Headings & content', 'Page is empty without JavaScript', 'Crawlers that do not run JavaScript - link previews, most AI assistants, older bots - receive an empty page.', 'The SEO plugin writes a plain reading of each page into the HTML (theme hook wp_body_open); this means the page is not passing through WordPress.'),
        'hash_links'      => array($e, 'Links', 'Links to /#/ addresses', 'Search engines ignore everything after a #, so a link to /#/about is a link to the home page. Each page needs its real address.', 'Link to /about, /settings/hounslow ... - the site\'s own menu already does.'),
        'no_viewport'     => array($e, 'Headings & content', 'Not set up for phones', 'Without a viewport tag phones show the desktop layout shrunk; Google indexes the phone version first.', 'Add <meta name="viewport" content="width=device-width, initial-scale=1">.'),
        'no_favicon'      => array($n, 'Sharing', 'No favicon', 'Google shows the site icon next to every result on phones; without one it shows a grey globe.', 'Add a square icon, at least 48 x 48 px.'),
        'lang_missing'    => array($w, 'Headings & content', 'Page language not declared', 'Without <html lang> search engines guess the language and region.', 'Settings > General > Site Language: English (UK).'),
        // Links
        'broken_internal' => array($e, 'Links', 'Links to a page that does not exist', 'A dead link wastes a visitor and a crawl.', 'Fix the link or redirect the old address (Settings > Redirects).'),
        'redirect_link'   => array($n, 'Links', 'Internal link goes through a redirect', 'An extra hop slows the visitor and dilutes the link.', 'Link to the final address directly.'),
        'orphan'          => array($w, 'Links', 'No other page links here', 'A page only the sitemap knows about is treated as unimportant.', 'Link to it from a related page (a nursery from Home and Our Nursery).'),
        'few_inlinks'     => array($n, 'Links', 'Only one page links here', 'Internal links are how a site tells Google which pages matter most.', 'Link to it from two or three relevant pages.'),
        'empty_anchor'    => array($n, 'Links', 'Link without words', 'A link with no text and no aria-label says nothing to Google or a screen reader.', 'Give it visible text or an aria-label.'),
        'blank_noopener'  => array($n, 'Links', 'New-tab link without rel="noopener"', 'The opened page can control this tab. Modern browsers guard against it, older ones do not.', 'Add rel="noopener".'),
        // Images
        'img_alt'         => array($w, 'Images', 'Images without alt text', 'Alt text is how Google Images and screen readers understand a picture.', 'Describe each meaningful image; leave alt="" only for decoration.'),
        'img_heavy'       => array($w, 'Images', 'Heavy image (over 400 KB)', 'Large pictures are the usual cause of a slow Largest Contentful Paint, especially on mobile data.', 'Export at the size shown, as WebP or AVIF.'),
        'img_dims'        => array($n, 'Images', 'Images without width and height', 'The browser cannot reserve space, so the layout jumps as they load (CLS).', 'Set width and height attributes.'),
        'img_legacy'      => array($n, 'Images', 'JPEG/PNG where WebP would do', 'WebP is typically 25-35% smaller at the same quality.', 'Convert to WebP.'),
        // Performance
        'slow'            => array($w, 'Performance', 'Slow server answer', 'Measured from the server: over 800 ms before the first byte is "needs improvement" in Google\'s own TTFB guidance.', 'Page caching on the host; fewer plugins in the request.'),
        'heavy_page'      => array($w, 'Performance', 'Heavy page (over 3 MB)', 'Everything the page loads up front. On a 4G phone, every MB is roughly a second.', 'Lazy-load below-the-fold media; compress video posters and images.'),
        'html_big'        => array($n, 'Performance', 'Large HTML (over 200 KB)', 'Big HTML delays the first paint and is parsed on the main thread.', 'Move inline data and SVG out of the page.'),
        'no_compress'     => array($w, 'Performance', 'Text not compressed', 'gzip or brotli cuts HTML, CSS and JS by about 70%.', 'Turn on compression on the host.'),
        'blocking'        => array($n, 'Performance', 'Scripts that block rendering', 'A script in the <head> without defer/async stops the page painting until it downloads.', 'Add defer.'),
        'no_store'        => array($n, 'Performance', 'Pages sent with "no-store"', 'Stops the browser keeping the page for Back/Forward - every Back press reloads everything.', 'The SEO plugin sends "no-cache" to visitors instead.'),
        'cwv_poor'        => array($e, 'Performance', 'Fails Core Web Vitals for real visitors', 'Measured in visitors\' browsers: at the 75th percentile this page is "poor" on LCP, INP or CLS - the page-experience signal Google uses.', 'See Performance > Core Web Vitals for which metric and on which device.'),
        // Sharing
        'og_broken'       => array($e, 'Sharing', 'Share picture does not load', 'The page names a share picture that answers an error, so WhatsApp, Facebook and LinkedIn show the link bare.', 'Choose a share image on the Pages tab, or fix the file the route table points at.'),
        'logo_broken'     => array($w, 'Structured data', 'Logo in the structured data does not load', 'Google takes the logo for results and the knowledge panel from the structured data; a broken file is ignored.', 'Choose a logo on the Business tab, or fix the file ldrn-seo.php names.'),
        'title_dup_head'  => array($n, 'Titles & descriptions', 'Title and description written twice', 'Once the page renders there are two title and two description tags - one from the server, one from the React build. Harmless while they agree; when one changes and the other does not, Google picks either.', 'Let one layer own them: the server, with <Seo /> reading the same words.'),
        'lang_not_gb'     => array($n, 'Headings & content', 'Language is not UK English', 'The page says it is US English. For a nursery in London, en-GB tells Google which country the page belongs to.', 'Settings > General > Site Language: English (UK).'),
        'og_missing'      => array($w, 'Sharing', 'No share picture', 'A link shared on WhatsApp, LinkedIn or Instagram DMs shows as bare text.', 'Choose a share image on the Pages tab.'),
        'twitter_missing' => array($n, 'Sharing', 'No X/Twitter card', 'Links on X show small or plain.', 'Written by the SEO plugin; this means the page is not passing through WordPress.'),
        // Structured data
        'schema_missing'  => array($w, 'Structured data', 'No structured data', 'Without JSON-LD search engines and AI assistants have to guess what the business is, where, and what it sells.', 'Written by the SEO plugin from the Business tab.'),
        'schema_invalid'  => array($e, 'Structured data', 'Structured data cannot be read', 'Broken JSON-LD is ignored entirely.', 'Check the Business tab for odd characters.'),
        // Security
        'not_https'       => array($w, 'Security', 'Site is not on HTTPS', 'Chrome marks HTTP pages "Not secure" and HTTPS is a ranking signal.', 'The nursery domain must be served over HTTPS with a redirect from HTTP.'),
        'mixed'           => array($e, 'Security', 'HTTPS page loads HTTP files', 'Browsers block or warn on insecure files inside a secure page.', 'Load every file over HTTPS.'),
        'no_hsts'         => array($n, 'Security', 'No HSTS header', 'Without it a first visit can be downgraded to HTTP.', 'Send Strict-Transport-Security on the live domain.'),
        // Trust & local
        'nap_mismatch'    => array($w, 'Trust & local', 'Contact details disagree', 'Google cross-checks name, address and phone (NAP) across the site and listings. Disagreement weakens local rankings and trust.', 'Use one spelling everywhere - site, Ofsted, Google Business Profile, daynurseries.co.uk and the rest.'),
        'business_gaps'   => array($n, 'Trust & local', 'Business profile incomplete', 'Opening hours and a Google Business Profile link are what make a business eligible for the local pack and the knowledge panel.', 'Fill them in on the Business tab.'),
        'no_gbp'          => array($w, 'Trust & local', 'No Google Business Profile linked', 'For "nursery near me" searches, the map pack is decided by the Business Profile, not the website.', 'Claim or link it on the Business tab.'),
        // AI search
        'ai_blocked'      => array($w, 'AI search', 'AI answer engines are blocked', 'ChatGPT search, Perplexity and Claude cannot read the site, so they cannot cite or link to it.', 'Settings > Crawling: allow answer engines.'),
        'no_llms'         => array($n, 'AI search', 'No llms.txt', 'A plain summary for AI assistants of what the business is and which pages matter.', 'Turn it on in Settings > Crawling.'),
        // Connections
        'no_gsc'          => array($w, 'Measurement', 'Search Console not connected', 'Only Search Console knows which searches show the site, how often, and at what position.', 'Settings > Connections.'),
        'no_verify'       => array($n, 'Measurement', 'Bing Webmaster Tools not verified', 'Bing also feeds ChatGPT search and Copilot.', 'Paste the Bing code in Settings > Connections.'),
    );
}

/* -------------------------------------------------------------------------
 * Running it
 * ---------------------------------------------------------------------- */

function ldrnseo_audit_start() {
    $routes = ldrnseo_routes();
    // One request to the site per step: on a host whose PHP backends take turns,
    // a step that fetched twice could reach the very process running it.
    $queue = array_merge($routes, array('@robots', '@sitemap', '@missing'));
    $run = array('started' => time(), 'queue' => $queue, 'total' => count($queue), 'pages' => array(), 'cache' => array(), 'site' => array());
    update_option('ldrnseo_audit_run', $run, false);
    return $run;
}

/**
 * One page (or a site-wide check, or the finish when the queue is empty).
 * $rendered: the page's document as the screen's browser frame rendered it,
 * when the step is for the page the screen just rendered.
 */
function ldrnseo_audit_step($rendered = null) {
    $run = get_option('ldrnseo_audit_run');
    if (!is_array($run)) {
        return array('done' => true);
    }
    if ($run['queue']) {
        $route = array_shift($run['queue']);
        $cache = $run['cache'];
        if ('@' === $route[0]) {
            $run['site'][$route] = ldrnseo_audit_site_check($route);
        } else {
            $run['pages'][$route] = ldrnseo_audit_page($route, $cache, $rendered);
        }
        $run['cache'] = $cache;
        update_option('ldrnseo_audit_run', $run, false);
        return array('done' => false, 'did' => $route, 'left' => count($run['queue']), 'total' => $run['total']);
    }
    $result = ldrnseo_audit_finish($run);
    delete_option('ldrnseo_audit_run');
    return array('done' => true, 'score' => $result['score']);
}

/** The next address the audit will read, so the screen can render it first. */
function ldrnseo_audit_next() {
    $run = get_option('ldrnseo_audit_run');
    return is_array($run) && $run['queue'] ? (string) $run['queue'][0] : '';
}

/** Where the last rendering of each page is kept. */
function ldrnseo_render_dir() {
    $up = wp_upload_dir(null, false);
    return trailingslashit($up['basedir']) . 'ldrnseo/render';
}

function ldrnseo_render_file($route) {
    return ldrnseo_render_dir() . '/' . ('/' === $route ? 'home' : sanitize_file_name(trim(str_replace('/', '--', $route), '-'))) . '.html';
}

/** A rendered document handed over by the screen: kept, if it is plausibly this site's page. */
function ldrnseo_render_keep($route, $html) {
    $html = (string) $html;
    if (strlen($html) < 500 || strlen($html) > 4 * 1024 * 1024 || false === stripos($html, 'id="root"')) {
        return '';
    }
    wp_mkdir_p(ldrnseo_render_dir());
    $dir = ldrnseo_render_dir();
    if (!file_exists($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess', "Require all denied\n");
        @file_put_contents($dir . '/index.php', "<?php // Silence.\n");
    }
    @file_put_contents(ldrnseo_render_file($route), $html);
    return $html;
}

function ldrnseo_audit_run_all() {
    ldrnseo_audit_start();
    for ($i = 0; $i < 200; $i++) {
        $s = ldrnseo_audit_step();
        if (!empty($s['done'])) {
            return $s;
        }
    }
    return array('done' => false);
}

add_action('ldrnseo_weekly_audit', 'ldrnseo_audit_run_all');

/** A site-wide check, fetched in a step of its own. */
function ldrnseo_audit_site_check($what) {
    if ('@robots' === $what) {
        $r = ldrnseo_fetch(home_url('/robots.txt'));
        return array('code' => $r['code'], 'body' => mb_substr($r['body'], 0, 4000));
    }
    if ('@sitemap' === $what) {
        $r = ldrnseo_fetch(home_url('/sitemap.xml'));
        $out = array('code' => $r['code'], 'bad' => array(), 'count' => 0);
        if (200 === $r['code'] && preg_match_all('#<loc>([^<]+)</loc>#', $r['body'], $m)) {
            $locs = array_map(function ($l) { return html_entity_decode(trim($l), ENT_QUOTES | ENT_XML1, 'UTF-8'); }, $m[1]);
            $out['count'] = count($locs);
            $known = array_flip(array_map('ldrnseo_abs', ldrnseo_routes()));
            foreach (array_slice($locs, 0, 60) as $loc) {
                if (isset($known[$loc])) {
                    continue;
                }
                $probe = ldrnseo_fetch($loc);
                if (200 !== $probe['code']) {
                    $out['bad'][$loc] = $probe['code'] . ('' !== (string) ($probe['location'] ?? '') ? ' > ' . $probe['location'] : '');
                }
            }
        }
        return $out;
    }
    $r = ldrnseo_fetch(home_url('/ldrnseo-check-' . wp_generate_password(8, false, false)));
    return array('code' => $r['code']);
}

/** GET (or HEAD) as an anonymous crawler, timed. */
function ldrnseo_fetch($url, $method = 'GET') {
    $t0  = microtime(true);
    $res = wp_remote_request($url, array('method' => $method, 'timeout' => 'HEAD' === $method ? 8 : (empty($GLOBALS['ldrnseo_retry']) ? 5 : 15), 'redirection' => 0, 'user-agent' => LDRNSEO_UA,
                                         'headers' => array('Accept-Encoding' => 'gzip, deflate'), 'sslverify' => false, 'cookies' => array()));
    if (is_wp_error($res) && 'GET' === $method && empty($GLOBALS['ldrnseo_retry'])) {
        // One retry: a page that does not answer in 5s is a busy PHP worker (on a
        // host with two single-process backends the request can land on the one
        // running this audit), not a broken page; the retry reaches the other.
        $GLOBALS['ldrnseo_retry'] = true;
        $again = ldrnseo_fetch($url, $method);
        $GLOBALS['ldrnseo_retry'] = false;
        return $again;
    }
    $ms  = (int) round((microtime(true) - $t0) * 1000);
    if (is_wp_error($res)) {
        return array('code' => 0, 'ms' => $ms, 'error' => $res->get_error_message(), 'body' => '', 'headers' => array(), 'size' => 0);
    }
    $headers = wp_remote_retrieve_headers($res);
    $body    = (string) wp_remote_retrieve_body($res);
    $len     = isset($headers['content-length']) ? (int) $headers['content-length'] : strlen($body);
    return array('code' => (int) wp_remote_retrieve_response_code($res), 'ms' => $ms, 'body' => $body,
                 'headers' => is_object($headers) ? $headers->getAll() : (array) $headers, 'size' => $len,
                 'location' => (string) wp_remote_retrieve_header($res, 'location'));
}

/** The status (and size) of a file or page, fetched once per audit. */
function ldrnseo_probe($url, array &$cache) {
    if (isset($cache[$url])) {
        return $cache[$url];
    }
    // HEAD only for static files (nginx answers them directly). A WordPress
    // page asked with HEAD can hold one of the host's few PHP workers until it
    // times out, so pages are always fetched with GET.
    $static = (bool) preg_match('#\.(webp|avif|jpe?g|png|gif|svg|ico|css|js|woff2?|ttf|mp4|webm|pdf|json|txt|xml)$#i', (string) wp_parse_url($url, PHP_URL_PATH));
    $r = ldrnseo_fetch($url, $static ? 'HEAD' : 'GET');
    if ($static && (405 === $r['code'] || 0 === $r['code'])) {
        $r = ldrnseo_fetch($url, 'GET');
    }
    return $cache[$url] = array('code' => $r['code'], 'size' => $r['size'], 'location' => $r['location'] ?? '',
                                'type' => strtolower((string) ($r['headers']['content-type'] ?? '')));
}

function ldrnseo_absolute($href, $base) {
    $href = trim(html_entity_decode((string) $href, ENT_QUOTES, 'UTF-8'));
    if ('' === $href || '#' === $href[0] || preg_match('#^(mailto|tel|javascript|data|sms|whatsapp):#i', $href)) {
        return '';
    }
    if (preg_match('#^https?://#i', $href)) {
        return $href;
    }
    if (0 === strpos($href, '//')) {
        return (string) wp_parse_url($base, PHP_URL_SCHEME) . ':' . $href;
    }
    $root = preg_replace('#^(https?://[^/]+).*$#i', '$1', $base);
    if ('/' === $href[0]) {
        return $root . $href;
    }
    // Relative to the page's own folder.
    $dir = preg_replace('#/[^/]*$#', '/', (string) wp_parse_url($base, PHP_URL_PATH));
    return $root . $dir . $href;
}

/**
 * An element's text as a reader sees it. textContent glues neighbouring
 * elements together ("<span>Beauty</span><span>is</span>" reads "Beautyis");
 * the site sets headline words in separate spans and line breaks, so each
 * element boundary and <br> counts as a space, as it does on screen.
 */
function ldrnseo_text($node) {
    $walk = function ($n) use (&$walk) {
        if (XML_TEXT_NODE === $n->nodeType || XML_CDATA_SECTION_NODE === $n->nodeType) {
            return $n->nodeValue;
        }
        if (XML_ELEMENT_NODE !== $n->nodeType && XML_DOCUMENT_NODE !== $n->nodeType && XML_HTML_DOCUMENT_NODE !== $n->nodeType) {
            return '';
        }
        $name = XML_ELEMENT_NODE === $n->nodeType ? strtolower($n->nodeName) : '';
        if (in_array($name, array('script', 'style', 'template', 'noscript'), true)) {
            return '';
        }
        if ('br' === $name) {
            return ' ';
        }
        $out = '';
        foreach ($n->childNodes as $c) {
            $out .= $walk($c);
        }
        return ' ' . $out . ' ';
    };
    return trim(preg_replace('/\s+/u', ' ', (string) $walk($node)));
}

/** Read and measure one page: the server's answer, then the page as rendered. */
function ldrnseo_audit_page($route, array &$cache, $rendered = null) {
    $url  = ldrnseo_abs($route);
    $host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
    $r    = ldrnseo_fetch($url);
    // A slow first answer is often a cold server waking up; time it once more and keep the better.
    if (200 === $r['code'] && $r['ms'] > 800) {
        $again = ldrnseo_fetch($url);
        if (200 === $again['code'] && $again['ms'] < $r['ms']) {
            $r['ms'] = $again['ms'];
        }
    }
    $p    = array('route' => $route, 'url' => $url, 'code' => $r['code'], 'ms' => $r['ms'], 'html_bytes' => strlen($r['body']),
                  'encoding' => strtolower((string) ($r['headers']['content-encoding'] ?? '')),
                  'cache_control' => (string) ($r['headers']['cache-control'] ?? ''),
                  'x_robots' => (string) ($r['headers']['x-robots-tag'] ?? ''),
                  'hsts' => !empty($r['headers']['strict-transport-security']));
    if (200 !== $r['code'] || '' === $r['body']) {
        return $p;
    }

    $raw = $r['body'];

    // What a crawler without JavaScript reads: the plain reading in the HTML.
    $p['static_words'] = 0;
    if (preg_match('#<noscript id="ldrnseo-reading">(.*?)</noscript>#s', $raw, $m)) {
        $p['static_words'] = str_word_count(wp_strip_all_tags(str_replace('<', ' <', $m[1])));
    }

    // The body as the browser drew it: handed over now, or the last one kept.
    $html = '';
    if (is_string($rendered) && '' !== $rendered) {
        $html = ldrnseo_render_keep($route, $rendered);
    }
    if ('' === $html && is_readable(ldrnseo_render_file($route))) {
        $html = (string) file_get_contents(ldrnseo_render_file($route));
        $p['render_kept'] = filemtime(ldrnseo_render_file($route));
    }
    $p['rendered'] = '' !== $html;
    if ('' === $html) {
        $html = $raw;
    }
    // The rendered document carries the no-JavaScript reading as text; it is not the page.
    $html = preg_replace('#<noscript\b.*?</noscript>#is', '', $html);

    // For the contact-details check: the email addresses and the digits the page shows.
    preg_match_all('/mailto:([a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,})/i', $html, $mails);
    $p['emails'] = array_values(array_unique(array_map('strtolower', $mails[1])));
    $p['digits'] = substr(preg_replace('/\D+/', '', wp_strip_all_tags($html) . ' ' . $html), 0, 200000);

    // The head every crawler reads comes from the server's answer.
    $hdom = new DOMDocument();
    libxml_use_internal_errors(true);
    $hdom->loadHTML('<?xml encoding="utf-8"?>' . $raw, LIBXML_NOWARNING | LIBXML_NOERROR);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    $hx = new DOMXPath($hdom);
    $x  = new DOMXPath($dom);

    $first = function ($q, $attr = null) use ($hx) {
        $n = $hx->query($q)->item(0);
        return $n ? ($attr ? (string) $n->getAttribute($attr) : ldrnseo_text($n)) : null;
    };

    $p['title']     = (string) $first('//head/title');
    $p['titles']    = $hx->query('//head/title')->length;
    $p['desc']      = (string) $first('//meta[@name="description"]', 'content');
    $p['canonical'] = (string) $first('//link[@rel="canonical"]', 'href');
    $p['robots']    = strtolower((string) $first('//meta[@name="robots"]', 'content'));
    $p['lang']      = (string) $first('//html', 'lang');
    $p['viewport']  = null !== $first('//meta[@name="viewport"]', 'content');
    $p['og_image']  = (string) $first('//meta[@property="og:image"]', 'content');
    $p['og_title']  = (string) $first('//meta[@property="og:title"]', 'content');
    $p['tw_card']   = (string) $first('//meta[@name="twitter:card"]', 'content');
    $p['favicon']   = (string) $first('//link[contains(concat(" ", @rel, " "), " icon ")]', 'href');

    // Headings, in document order.
    $p['headings'] = array();
    foreach ($x->query('//body//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]') as $h) {
        $t = ldrnseo_text($h);
        if ('' !== $t) {
            $p['headings'][] = array((int) substr($h->nodeName, 1), mb_substr($t, 0, 160));
        }
    }
    $p['h1'] = array_values(array_map(function ($h) { return $h[1]; }, array_filter($p['headings'], function ($h) { return 1 === $h[0]; })));

    // Questions the page answers: <details>, or a button that opens its answer.
    $p['faqs'] = array();
    foreach ($x->query('//*[@aria-controls]') as $btn) {
        $q = ldrnseo_text($btn);
        $target = $dom->getElementById($btn->getAttribute('aria-controls'));
        if ($target && '?' === mb_substr($q, -1) && '' !== ($a = ldrnseo_text($target))) {
            $p['faqs'][] = array('question' => $q, 'answer' => $a);
        }
    }
    foreach ($x->query('//details[summary]') as $d) {
        $q = ldrnseo_text($x->query('./summary', $d)->item(0));
        $a = trim(mb_substr(ldrnseo_text($d), mb_strlen($q)));
        if ('?' === mb_substr($q, -1) && '' !== $a) {
            $p['faqs'][] = array('question' => $q, 'answer' => $a);
        }
    }

    // Links that point at a /#/ address (one URL for the whole site, to a crawler).
    $p['hash_links'] = 0;
    foreach ($x->query('//a[@href]') as $a) {
        if (preg_match('~^(/|https?://[^/]+/)?#/~', (string) $a->getAttribute('href'))) {
            $p['hash_links']++;
        }
    }

    // Images.
    $imgs = array();
    foreach ($x->query('//img') as $img) {
        $src = $img->getAttribute('currentSrc') ?: ($img->getAttribute('src') ?: $img->getAttribute('data-src'));
        $abs = ldrnseo_absolute($src, $url);
        if (!$abs) {
            continue;
        }
        $hidden = 'true' === $img->getAttribute('aria-hidden') || 'presentation' === $img->getAttribute('role');
        $one = array('src' => $abs, 'alt' => $img->hasAttribute('alt') ? $img->getAttribute('alt') : null, 'decor' => $hidden,
                     'dims' => $img->hasAttribute('width') && $img->hasAttribute('height'), 'lazy' => 'lazy' === $img->getAttribute('loading'));
        // A picture used twice on a page (a gallery and its lightbox) is one file to fix.
        if (isset($imgs[$abs])) {
            $was = $imgs[$abs];
            $imgs[$abs]['alt']   = null === $was['alt'] || null === $one['alt'] ? null : $was['alt'];
            $imgs[$abs]['decor'] = $was['decor'] && $one['decor'];
            $imgs[$abs]['dims']  = $was['dims'] && $one['dims'];
            $imgs[$abs]['lazy']  = $was['lazy'] && $one['lazy'];
        } else {
            $imgs[$abs] = $one;
        }
    }
    $imgs = array_values($imgs);
    $p['images'] = array();
    $weight = array('html' => $p['html_bytes'], 'css' => 0, 'js' => 0, 'img' => 0, 'font' => 0);
    foreach (array_slice($imgs, 0, 150) as $img) {
        $probe = ldrnseo_probe($img['src'], $cache);
        $img['code'] = $probe['code'];
        $img['size'] = $probe['size'];
        $img['format'] = strtolower(pathinfo((string) wp_parse_url($img['src'], PHP_URL_PATH), PATHINFO_EXTENSION));
        if (!$img['lazy']) {
            $weight['img'] += $probe['size'];
        }
        $p['images'][] = $img;
    }

    // Files the page loads.
    $p['blocking'] = 0;
    $p['mixed'] = array();
    foreach ($hx->query('//link[@rel="stylesheet"]|//link[@rel="preload"]|//script[@src]') as $el) {
        $src = $el->getAttribute('href') ?: $el->getAttribute('src');
        $abs = ldrnseo_absolute($src, $url);
        if (!$abs) {
            continue;
        }
        if (0 === strpos($url, 'https://') && 0 === strpos($abs, 'http://')) {
            $p['mixed'][] = $abs;
        }
        $probe = ldrnseo_probe($abs, $cache);
        if ('script' === $el->nodeName) {
            $weight['js'] += $probe['size'];
            $inHead = 'head' === $el->parentNode->nodeName;
            // The Site Editor runtime is synchronous on purpose: it puts edited words in before first paint.
            if ($inHead && 'ldrn-studio-runtime' !== $el->getAttribute('id') && !$el->hasAttribute('defer') && !$el->hasAttribute('async') && 'module' !== $el->getAttribute('type')) {
                $p['blocking']++;
            }
        } elseif ('stylesheet' === $el->getAttribute('rel')) {
            $weight['css'] += $probe['size'];
        } elseif ('font' === $el->getAttribute('as')) {
            $weight['font'] += $probe['size'];
        }
    }
    $p['weight'] = $weight;

    // Links.
    $p['links_internal'] = array();
    $p['links_external'] = array();
    $p['empty_anchor'] = 0;
    $p['blank_noopener'] = 0;
    foreach ($x->query('//a[@href]') as $a) {
        $abs = ldrnseo_absolute($a->getAttribute('href'), $url);
        if (!$abs) {
            continue;
        }
        $label = ldrnseo_text($a) ?: $a->getAttribute('aria-label') ?: $a->getAttribute('title');
        if ('' === trim((string) $label) && !$x->query('.//img[@alt!=""]', $a)->length) {
            $p['empty_anchor']++;
        }
        if ('_blank' === $a->getAttribute('target') && false === stripos($a->getAttribute('rel'), 'noopener') && false === stripos($a->getAttribute('rel'), 'noreferrer')) {
            $p['blank_noopener']++;
        }
        $abs = preg_replace('/#.*$/', '', $abs);
        if (wp_parse_url($abs, PHP_URL_HOST) === $host) {
            $p['links_internal'][$abs] = mb_substr(trim((string) $label), 0, 80);
        } else {
            $p['links_external'][$abs] = mb_substr(trim((string) $label), 0, 80);
        }
    }
    $p['links_bad'] = array();
    $p['links_redirect'] = array();
    $known = array_flip(array_map('ldrnseo_abs', ldrnseo_routes()));
    foreach (array_keys($p['links_internal']) as $link) {
        if (preg_match('#\.(webp|jpe?g|png|svg|pdf|mp4|zip)$#i', (string) wp_parse_url($link, PHP_URL_PATH))) {
            continue;
        }
        $bare = preg_replace('/\?.*$/', '', $link);
        if (isset($known[$bare]) || isset($known[untrailingslashit($bare)])) {
            continue;
        }
        $probe = ldrnseo_probe($link, $cache);
        if ($probe['code'] >= 400 || 0 === $probe['code']) {
            $p['links_bad'][$link] = $probe['code'];
        } elseif ($probe['code'] >= 300) {
            $p['links_redirect'][$link] = $probe['location'];
        }
    }

    // Structured data.
    $p['schema_types'] = array();
    $p['schema_bad'] = 0;
    foreach ($hx->query('//script[@type="application/ld+json"]') as $s) {
        $data = json_decode($s->textContent, true);
        if (!is_array($data)) {
            $p['schema_bad']++;
            continue;
        }
        foreach ((array) ($data['@graph'] ?? array($data)) as $node) {
            foreach ((array) ($node['@type'] ?? array()) as $t) {
                $p['schema_types'][] = $t;
            }
        }
    }
    $p['schema_types'] = array_values(array_unique($p['schema_types']));

    // The pictures the head and the structured data name: do they load?
    $p['og_code'] = $p['og_image'] ? ldrnseo_probe(ldrnseo_absolute($p['og_image'], $url), $cache)['code'] : 0;
    $p['logo_bad'] = array();
    foreach ($hx->query('//script[@type="application/ld+json"]') as $s) {
        $data = json_decode($s->textContent, true);
        foreach ((array) (is_array($data) ? ($data['@graph'] ?? array($data)) : array()) as $node) {
            $logo = is_array($node['logo'] ?? null) ? (string) ($node['logo']['url'] ?? '') : (string) ($node['logo'] ?? '');
            if ('' !== $logo && 200 !== ($code = ldrnseo_probe(ldrnseo_absolute($logo, $url), $cache)['code'])) {
                $p['logo_bad'][$logo] = $code;
            }
        }
    }

    // The head once the page has rendered: the build adds its own tags to it.
    $p['r_canonicals'] = array();
    $p['r_titles'] = 0;
    $p['r_descs'] = 0;
    if (!empty($p['rendered'])) {
        foreach ($x->query('//link[@rel="canonical"]') as $l) {
            $p['r_canonicals'][] = untrailingslashit((string) $l->getAttribute('href'));
        }
        $p['r_canonicals'] = array_values(array_unique($p['r_canonicals']));
        $p['r_titles'] = $x->query('//head/title')->length;
        $p['r_descs']  = $x->query('//meta[@name="description"]')->length;
    }

    // Words a visitor reads (last: it strips scripts and styles from the tree): the main content, without code, icons or hidden helpers.
    foreach ($x->query('//script|//style|//noscript|//svg|//template') as $junk) {
        $junk->parentNode->removeChild($junk);
    }
    $main  = $x->query('//main')->item(0) ?: $x->query('//body')->item(0);
    $paras = array();
    foreach ($x->query('.//p|.//li|.//blockquote|.//dd', $main) as $el) {
        $t = ldrnseo_text($el);
        if (mb_strlen($t) > 2) {
            $paras[] = mb_substr($t, 0, 1200);
        }
    }
    $p['paras'] = array_slice(array_values(array_unique($paras)), 0, 120);
    $body       = $main ? ldrnseo_text($main) : '';
    $p['text']  = mb_substr($body, 0, 30000);
    $p['words'] = $body ? count(preg_split('/\s+/u', $body)) : 0;

    return $p;
}

/** Cross-page and site-wide checks; the score; the history. */
function ldrnseo_audit_finish(array $run) {
    $pages   = $run['pages'];
    $cache   = $run['cache'];
    $cat     = ldrnseo_issue_catalog();
    $issues  = array();
    $add     = function ($id, $route = '*', $detail = '') use (&$issues) {
        $issues[$id][] = array('route' => $route, 'detail' => $detail);
    };
    $kinds   = array();
    foreach (ldrnseo_pages_list() as $route => $info) {
        $kinds[$route] = $info;
    }

    // Internal links pointing at each page, from every other page.
    $inlinks = array();
    foreach ($pages as $from => $p) {
        foreach (array_keys($p['links_internal'] ?? array()) as $link) {
            $to = '/' . trim((string) wp_parse_url($link, PHP_URL_PATH), '/');
            if ($to !== $from) {
                $inlinks[$to][$from] = true;
            }
        }
    }

    $titles = array();
    $descs  = array();
    $vitals = ldrnseo_period(28);
    foreach ($pages as $route => $p) {
        if (200 !== $p['code']) {
            $add('http_error', $route, 'Answered ' . ($p['code'] ?: 'nothing') . '.');
            continue;
        }
        $kind = $kinds[$route]['kind'] ?? 'page';
        if (false !== strpos($p['robots'], 'noindex') || false !== stripos($p['x_robots'], 'noindex')) {
            $add('noindex', $route);
        }
        if (!$p['canonical']) {
            $add('canonical_miss', $route);
        } elseif (untrailingslashit($p['canonical']) !== untrailingslashit($p['url'])) {
            $add('canonical_other', $route, $p['canonical']);
        }
        $tlen = mb_strlen($p['title']);
        if (!$tlen) {
            $add('title_missing', $route);
        } elseif (ldrnseo_title_px($p['title']) > 600) {
            $add('title_long', $route, $tlen . ' characters, about ' . ldrnseo_title_px($p['title']) . ' px');
        } elseif ($tlen < 30) {
            $add('title_short', $route, $tlen . ' characters');
        }
        $dlen = mb_strlen($p['desc']);
        if (!$dlen) {
            $add('desc_missing', $route);
        } elseif ($dlen > 160) {
            $add('desc_long', $route, $dlen . ' characters');
        } elseif ($dlen < 70) {
            $add('desc_short', $route, $dlen . ' characters');
        }
        if ($p['title']) { $titles[$p['title']][] = $route; }
        if ($p['desc'])  { $descs[$p['desc']][] = $route; }

        $h1 = count($p['h1']);
        if (0 === $h1) {
            $add('h1_missing', $route);
        } elseif ($h1 > 1) {
            $add('h1_multi', $route, implode(' / ', $p['h1']));
        }
        $prev = 0;
        foreach ($p['headings'] as $h) {
            if ($prev && $h[0] > $prev + 1) {
                $add('heading_skip', $route, 'H' . $prev . ' then H' . $h[0] . ' ("' . $h[1] . '")');
                break;
            }
            $prev = $h[0];
        }
        if (in_array($kind, array('setting', 'article', 'event', 'job'), true) && !empty($p['rendered']) && $p['words'] < 150) {
            $add('thin', $route, $p['words'] . ' words');
        }
        if (empty($p['static_words'])) {
            $add('js_only', $route);
        }
        if (!empty($p['hash_links'])) {
            $add('hash_links', $route, $p['hash_links'] . ' link' . (1 === $p['hash_links'] ? '' : 's'));
        }
        if (!$p['lang']) {
            $add('lang_missing', $route);
        } elseif (0 !== stripos($p['lang'], 'en-gb')) {
            $add('lang_not_gb', $route, 'lang="' . $p['lang'] . '"');
        }
        if (count($p['r_canonicals'] ?? array()) > 1) {
            $add('canonical_conflict', $route, implode('  vs  ', $p['r_canonicals']));
        }
        if (($p['r_titles'] ?? 0) > 1 || ($p['r_descs'] ?? 0) > 1) {
            $add('title_dup_head', $route, ($p['r_titles'] ?? 0) . ' titles, ' . ($p['r_descs'] ?? 0) . ' descriptions');
        }
        if ($p['og_image'] && ($p['og_code'] ?? 200) >= 400) {
            $add('og_broken', $route, basename((string) wp_parse_url($p['og_image'], PHP_URL_PATH)) . ' answers ' . $p['og_code']);
        }
        foreach ((array) ($p['logo_bad'] ?? array()) as $logo => $code) {
            $add('logo_broken', $route, basename((string) wp_parse_url($logo, PHP_URL_PATH)) . ' answers ' . ($code ?: 'nothing'));
        }
        if (empty($p['viewport'])) {
            $add('no_viewport', $route);
        }
        if (array_key_exists('favicon', $p) && '' === $p['favicon']) {
            $add('no_favicon', $route);
        }
        foreach ($p['links_bad'] as $link => $code) {
            $add('broken_internal', $route, $link . ' (' . ($code ?: 'no answer') . ')');
        }
        foreach ($p['links_redirect'] as $link => $to) {
            $add('redirect_link', $route, $link . ' > ' . $to);
        }
        if ('/' !== $route && empty($inlinks[$route])) {
            $add('orphan', $route);
        } elseif ('/' !== $route && 1 === count($inlinks[$route] ?? array())) {
            $add('few_inlinks', $route, 'Only from ' . array_key_first($inlinks[$route]));
        }
        if ($p['empty_anchor']) {
            $add('empty_anchor', $route, $p['empty_anchor'] . ' link' . (1 === $p['empty_anchor'] ? '' : 's'));
        }
        if ($p['blank_noopener']) {
            $add('blank_noopener', $route, $p['blank_noopener'] . ' link' . (1 === $p['blank_noopener'] ? '' : 's'));
        }
        $noalt = array_filter($p['images'], function ($i) { return null === $i['alt'] && !$i['decor']; });
        if ($noalt) {
            $add('img_alt', $route, count($noalt) . ' of ' . count($p['images']) . ': ' . implode(', ', array_map(function ($i) { return basename((string) wp_parse_url($i['src'], PHP_URL_PATH)); }, array_slice($noalt, 0, 4))));
        }
        $heavy = array_filter($p['images'], function ($i) { return $i['size'] > 400 * 1024; });
        if ($heavy) {
            $add('img_heavy', $route, implode(', ', array_map(function ($i) { return basename((string) wp_parse_url($i['src'], PHP_URL_PATH)) . ' ' . size_format($i['size']); }, array_values($heavy))));
        }
        $nodims = array_filter($p['images'], function ($i) { return !$i['dims']; });
        if ($nodims) {
            $add('img_dims', $route, count($nodims) . ' image' . (1 === count($nodims) ? '' : 's'));
        }
        $legacy = array_filter($p['images'], function ($i) { return in_array($i['format'], array('jpg', 'jpeg', 'png'), true) && $i['size'] > 60 * 1024; });
        if ($legacy) {
            $add('img_legacy', $route, count($legacy) . ' file' . (1 === count($legacy) ? '' : 's'));
        }
        if ($p['ms'] > 800) {
            $add('slow', $route, $p['ms'] . ' ms');
        }
        $total = array_sum($p['weight']);
        if ($total > 3 * 1024 * 1024) {
            $add('heavy_page', $route, size_format($total, 1));
        }
        if ($p['html_bytes'] > 200 * 1024) {
            $add('html_big', $route, size_format($p['html_bytes']));
        }
        if ('' === $p['encoding'] && $p['html_bytes'] > 1400) {
            $add('no_compress', $route);
        }
        if ($p['blocking']) {
            $add('blocking', $route, $p['blocking'] . ' script' . (1 === $p['blocking'] ? '' : 's'));
        }
        if (false !== stripos($p['cache_control'], 'no-store')) {
            $add('no_store', $route);
        }
        if (!$p['og_image']) {
            $add('og_missing', $route);
        }
        if (!$p['tw_card']) {
            $add('twitter_missing', $route);
        }
        if ($p['schema_bad']) {
            $add('schema_invalid', $route);
        } elseif (!$p['schema_types']) {
            $add('schema_missing', $route);
        }
        if ($p['mixed']) {
            $add('mixed', $route, implode(', ', array_slice($p['mixed'], 0, 3)));
        }
        $v = ldrnseo_vitals($vitals['from'], $vitals['to'], $route);
        foreach (array('lcp', 'inp', 'cls') as $m) {
            if ($v[$m]['n'] >= 5 && 'poor' === ldrnseo_vital_rating($m, $v[$m]['p75'])) {
                $add('cwv_poor', $route, strtoupper($m) . ' ' . ldrnseo_vital_format($m, $v[$m]['p75']) . ' (' . $v[$m]['n'] . ' visits)');
            }
        }
    }
    foreach ($titles as $t => $routes) {
        if (count($routes) > 1) {
            foreach ($routes as $r) { $add('title_dup', $r, 'Shared with ' . implode(', ', array_diff($routes, array($r)))); }
        }
    }
    foreach ($descs as $d => $routes) {
        if (count($routes) > 1) {
            foreach ($routes as $r) { $add('desc_dup', $r, 'Shared with ' . implode(', ', array_diff($routes, array($r)))); }
        }
    }

    // Site-wide.
    $site = array();
    if (!(int) get_option('blog_public', 1)) {
        $add('wp_private');
    }
    $checks = $run['site'] ?? array();
    $robots = $checks['@robots'] ?? array('code' => 0, 'body' => '');
    $site['robots'] = $robots;
    if (ldrnseo_robots_bars_everyone((string) $robots['body'])) {
        $add('robots_block');
    }
    $site['sitemap'] = $checks['@sitemap']['code'] ?? 0;
    $site['sitemap_count'] = $checks['@sitemap']['count'] ?? 0;
    if (200 !== $site['sitemap']) {
        $add('sitemap_missing');
    }
    foreach ((array) ($checks['@sitemap']['bad'] ?? array()) as $loc => $what) {
        $add('sitemap_bad', '*', preg_replace('#^https?://[^/]+#', '', $loc) . ' answers ' . preg_replace('#https?://[^/]+#', '', $what));
    }
    $site['missing_page'] = $checks['@missing']['code'] ?? 0;
    if (200 === $site['missing_page']) {
        $add('soft_404');
    }
    $https = 0 === strpos(home_url(), 'https://');
    $site['https'] = $https;
    // A local copy is plain HTTP by nature; HTTPS is judged on the live domain.
    if (!$https && !ldrnseo_is_local()) {
        $add('not_https');
    } elseif ($https && !array_filter($pages, function ($p) { return !empty($p['hsts']); })) {
        $add('no_hsts');
    }
    // Connections (Search Console, Bing, Business Profile) are setup steps, not
    // faults in the pages: they live in Settings > Setup, not in this list.

    // Contact details: the Business tab against what the pages themselves say.
    $b      = ldrnseo_business();
    $nap    = ldrnseo_nap_check($pages, $b);
    $site['nap'] = $nap;
    foreach ($nap as $finding) {
        $add('nap_mismatch', $finding['route'], $finding['detail']);
    }

    // Score: Ahrefs' method - the share of crawled pages with no Errors.
    $with_errors = array();
    foreach ($issues as $id => $rows) {
        if ('error' !== $cat[$id][0]) {
            continue;
        }
        foreach ($rows as $row) {
            if ('*' !== $row['route']) {
                $with_errors[$row['route']] = true;
            }
        }
    }
    $total = max(1, count($pages));
    $score = (int) round(100 * ($total - count($with_errors)) / $total);
    // A site-wide error (blocked robots, discouraged indexing) caps everything.
    foreach ($issues as $id => $rows) {
        if ('error' === $cat[$id][0] && '*' === $rows[0]['route']) {
            $score = min($score, 30);
        }
    }

    // What only the rendered page knows - the main heading, its questions, its
    // paragraphs - for the head, the structured data and the no-JavaScript reading.
    $rendered = (array) get_option('ldrnseo_rendered', array());
    foreach ($pages as $route => $p) {
        if (!empty($p['rendered']) && 200 === $p['code']) {
            $rendered[$route] = array('h1' => (string) ($p['h1'][0] ?? ''), 'faqs' => array_slice((array) ($p['faqs'] ?? array()), 0, 12),
                                      'paras' => array_slice((array) ($p['paras'] ?? array()), 0, 12), 'at' => time());
        }
    }
    update_option('ldrnseo_rendered', array_intersect_key($rendered, array_flip(ldrnseo_routes())), false);

    // Keep the pages without their raw text in the result; the text lives apart.
    $content = array();
    foreach ($pages as $route => &$p) {
        $content[$route] = array('text' => $p['text'] ?? '', 'paras' => $p['paras'] ?? array(), 'headings' => $p['headings'] ?? array(),
                                 'images' => array_map(function ($i) { return array('src' => $i['src'], 'alt' => $i['alt']); }, $p['images'] ?? array()),
                                 'inlinks' => array_keys($inlinks[$route] ?? array()));
        unset($p['text'], $p['paras'], $p['digits']);
        $p['inlinks'] = count($inlinks[$route] ?? array());
    }
    unset($p);

    $result = array('at' => time(), 'took' => time() - $run['started'], 'score' => $score, 'pages' => $pages, 'issues' => $issues,
                    'site' => $site, 'files' => count($cache));
    update_option('ldrnseo_audit', $result, false);
    update_option('ldrnseo_content', $content, false);

    $history = (array) get_option('ldrnseo_audit_history', array());
    $counts  = array('error' => 0, 'warning' => 0, 'notice' => 0);
    foreach ($issues as $id => $rows) {
        $counts[$cat[$id][0]] += count($rows);
    }
    $history[] = array('at' => time(), 'score' => $score) + $counts;
    update_option('ldrnseo_audit_history', array_slice($history, -30), false);
    return $result;
}

/**
 * Does the site say the same phone, postcode and email the Business tab
 * publishes? Read from the pages a visitor sees - Contact above all.
 */
function ldrnseo_nap_check(array $pages, array $b) {
    $out    = array();
    $digits = function ($s) { return preg_replace('/\D+/', '', (string) $s); };
    $talk   = $pages['/contact'] ?? array();
    $label  = 'the Contact page';
    $shown  = (string) ($talk['digits'] ?? '');
    foreach (ldrnseo_lines($b['phones']) as $phone) {
        $d = substr($digits($phone), -10);
        if ($d && $shown && false === strpos($shown, $d)) {
            $out[] = array('route' => '/contact', 'detail' => 'Phone ' . $phone . ' is not on ' . $label . '.');
        }
    }
    if ($b['postcode'] && $shown && false === strpos($shown, $digits($b['postcode']))) {
        $out[] = array('route' => '/contact', 'detail' => 'Postcode ' . $b['postcode'] . ' is not on ' . $label . '.');
    }
    // Every email address the pages publish should be the business one.
    $seen = array();
    foreach ($pages as $route => $pg) {
        foreach ((array) ($pg['emails'] ?? array()) as $email) {
            $seen[$email][] = $route;
        }
    }
    foreach ($seen as $email => $routes) {
        if ($b['email'] && strtolower($b['email']) !== $email) {
            $out[] = array('route' => $routes[0], 'detail' => 'This page gives ' . $email . '; the business email is ' . $b['email'] . '.');
        }
    }
    // And each listing, as it is written there.
    $defs = ldrnseo_listing_defs();
    foreach (ldrnseo_listings() as $key => $l) {
        $as = trim($l['as']);
        if ('' === $as) {
            continue;
        }
        $name   = trim(strtok($as, ','));
        $label  = $defs[$key][0];
        if (empty($defs[$key][4])) {
            continue;    // a registered office or a profile, not the nursery itself
        }
        // A directory's category word after the brand ("Little Rainbows Day Nursery Hounslow") is the same nursery;
        // a different brand is not.
        $norm = function ($x) { return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower($x))); };
        $names = array_filter(array($norm($b['name']), $norm($b['alt_name'] ?? '')));
        $same  = false;
        foreach ($names as $n) {
            $same = $same || $norm($name) === $n || 0 === strpos($norm($name) . ' ', $n . ' ');
        }
        if (!$same) {
            $out[] = array('route' => '*', 'detail' => $label . ' names the business "' . $name . '"; the site says "' . $b['name'] . '".');
        }
        // The address words that identify the place - unit number, building, area - in any order;
        // generic words ("complex", "road", "near") are written differently everywhere.
        $generic = array('road', 'rd', 'street', 'st', 'avenue', 'ave', 'lane', 'close', 'way', 'drive', 'house', 'court', 'unit', 'floor', 'flat', 'the', 'and', 'london', 'middlesex', 'greater');
        $missing = array();
        foreach (preg_split('/[\s,]+/', strtolower($b['street'])) as $word) {
            $word = trim($word, '.-#/');
            if ('' === $word || in_array($word, $generic, true) || (strlen($word) < 3 && !ctype_digit($word))) {
                continue;
            }
            if (!preg_match('/\b' . preg_quote($word, '/') . '\b/i', $as)) {
                $missing[] = $word;
            }
        }
        if ($missing) {
            $out[] = array('route' => '*', 'detail' => $label . ' writes the address "' . $as . '" (no "' . implode('", "', $missing) . '"); the site says "' . $b['street'] . '".');
        }
        if ($b['postcode'] && false === strpos($as, $b['postcode'])) {
            $out[] = array('route' => '*', 'detail' => $label . ' has no postcode ' . $b['postcode'] . '.');
        }
    }
    return $out;
}

/**
 * Approximate width in pixels of a title as Google sets it (Arial, 20px on
 * desktop). Google truncates at about 600px; characters alone mislead -
 * "WWW" is three times as wide as "iii".
 */
/** Does a robots.txt turn every crawler away from the whole site? */
function ldrnseo_robots_bars_everyone($body) {
    $group = false;
    foreach (preg_split('/\r?\n/', (string) $body) as $line) {
        $line = trim(preg_replace('/#.*$/', '', $line));
        if (preg_match('/^user-agent:\s*(.+)$/i', $line, $m)) {
            $group = '*' === trim($m[1]);
        } elseif ($group && preg_match('/^disallow:\s*\/\s*$/i', $line)) {
            return true;
        }
    }
    return false;
}

function ldrnseo_title_px($s) {
    static $w = null;
    if (null === $w) {
        $w = array();
        $groups = array(
            '5.6' => "ijl|!.,:;'`", '6.7' => 'frtI()[]{} -/', '10' => 'abcdeghknopqsuvxyz0123456789$_?#*', '11.1' => 'JFLTZ', '12.2' => 'ABEKPSVXY&', '13.3' => 'CDHNRUw', '15.6' => 'GOQ', '16.7' => 'Mm', '18.9' => 'W', '20' => '—', '10.1' => '–',
        );
        foreach ($groups as $px => $chars) {
            foreach (preg_split('//u', $chars, -1, PREG_SPLIT_NO_EMPTY) as $c) {
                $w[$c] = (float) $px;
            }
        }
    }
    $total = 0;
    foreach (preg_split('//u', (string) $s, -1, PREG_SPLIT_NO_EMPTY) as $c) {
        $total += $w[$c] ?? 11;
    }
    return (int) round($total);
}
