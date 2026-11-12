<?php
/**
 * Lighthouse reports - what PageSpeed Insights shows, kept per page.
 *
 * Two sources, one reducer:
 *
 *   PageSpeed Insights API   Google's servers run Lighthouse against the
 *                            public address and add real-user Chrome data
 *                            (CrUX) when Google has enough of it. Needs a
 *                            free API key (Settings).
 *   tools/lighthouse-run.mjs the same engine on a computer, for a local copy
 *                            or when there is no key; imported with
 *                            ldrnseo_lh_import_dir().
 *
 * Each report is reduced to what the Speed tab draws - category scores,
 * metrics with their weights, the screenshot and filmstrip, and every audit
 * sorted into failed / to check by hand / passed / not applicable, with its
 * evidence table - and written to uploads/ldrnseo/lighthouse/<page>.<device>.json.
 * A lean index of scores and metrics (option ldrnseo_lighthouse) feeds the
 * whole-site view and the other tabs without reading every file.
 */

defined('ABSPATH') || exit;

function ldrnseo_lh_dir() {
    $dir = trailingslashit(wp_upload_dir(null, false)['basedir']) . 'ldrnseo/lighthouse';
    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
        @file_put_contents($dir . '/index.php', "<?php\n// Silence.\n");
    }
    return $dir;
}

function ldrnseo_lh_slug($route) {
    return '/' === $route ? 'home' : trim(preg_replace('/[^a-z0-9]+/i', '-', $route), '-');
}

/** The full reduced report for a page on a device, or null. */
function ldrnseo_lh_report($route, $device) {
    $file = ldrnseo_lh_dir() . '/' . ldrnseo_lh_slug($route) . '.' . ('desktop' === $device ? 'desktop' : 'mobile') . '.json';
    if (!is_readable($file)) {
        return null;
    }
    $r = json_decode((string) file_get_contents($file), true);
    return is_array($r) ? $r : null;
}

/** Lighthouse's pass line and its three ratings. */
function ldrnseo_lh_rating($score, $mode = 'numeric') {
    if ('informative' === $mode || 'error' === $mode) {
        return $mode;
    }
    if ('notApplicable' === $mode || 'manual' === $mode) {
        return 'na';
    }
    if (null === $score) {
        return 'informative';
    }
    return $score >= 0.9 ? 'pass' : ($score >= 0.5 ? 'average' : 'fail');
}

/** Markdown from Lighthouse ("[text](url)", "`code`") to plain text; the "Learn more" links go. */
function ldrnseo_lh_text($md) {
    $md = preg_replace('/\s*\[(Learn (more|how)[^\]]*)\]\([^)]*\)\.?/i', '', (string) $md);
    $md = preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $md);
    return trim(wp_strip_all_tags(str_replace('`', '', $md)));
}

/** One value of a details table, formatted the way the Lighthouse report does. */
function ldrnseo_lh_cell($v, $type, $origin) {
    if (is_array($v)) {
        $t = $v['type'] ?? '';
        if ('node' === $t) {
            return array('node', (string) ($v['nodeLabel'] ?? ''), (string) ($v['snippet'] ?? ''));
        }
        if ('source-location' === $t) {
            return array('url', ldrnseo_lh_short_url((string) ($v['url'] ?? ''), $origin) . (isset($v['line']) ? ':' . ((int) $v['line'] + 1) : ''));
        }
        if ('url' === $t || 'link' === $t) {
            return array('url', ldrnseo_lh_short_url((string) ($v['value'] ?? $v['url'] ?? $v['text'] ?? ''), $origin));
        }
        if ('code' === $t || 'text' === $t) {
            return array('text', (string) ($v['value'] ?? ''));
        }
        if ('numeric' === $t) {
            return array('num', number_format_i18n((float) ($v['value'] ?? 0)));
        }
        return null;
    }
    if (null === $v || '' === $v) {
        return array('text', '');
    }
    switch ($type) {
        case 'url':
            return array('url', ldrnseo_lh_short_url((string) $v, $origin));
        case 'bytes':
            return array('num', number_format_i18n((float) $v / 1024, 1) . ' KiB');
        case 'ms':
        case 'timespanMs':
            return array('num', number_format_i18n((float) $v) . ' ms');
        case 'numeric':
            return array('num', is_float($v) ? number_format_i18n($v, $v < 1 ? 3 : 0) : number_format_i18n((float) $v));
        case 'thumbnail':
            return null;
        default:
            return array('text', mb_substr(is_scalar($v) ? (string) $v : '', 0, 160));
    }
}

function ldrnseo_lh_short_url($url, $origin) {
    if ($origin && 0 === strpos($url, $origin)) {
        $url = substr($url, strlen($origin)) ?: '/';
    }
    if (0 === strpos($url, 'data:')) {
        return 'data:' . strtok(substr($url, 5), ';,') . '…';
    }
    return mb_strlen($url) > 90 ? mb_substr($url, 0, 60) . '…' . mb_substr($url, -24) : $url;
}

/** A details object to {head: [labels], rows: [[cells]]} or {checks: [[label, ok]]}, at most 8 rows. */
function ldrnseo_lh_details($d, $origin) {
    if (!is_array($d)) {
        return null;
    }
    if ('list' === ($d['type'] ?? '')) {
        foreach ((array) ($d['items'] ?? array()) as $sub) {
            $got = ldrnseo_lh_details($sub, $origin);
            if ($got) {
                return $got;
            }
        }
        return null;
    }
    if ('checklist' === ($d['type'] ?? '')) {
        $checks = array();
        foreach ((array) ($d['items'] ?? array()) as $c) {
            $checks[] = array((string) ($c['label'] ?? ''), !empty($c['value']));
        }
        return $checks ? array('checks' => $checks) : null;
    }
    if (!in_array($d['type'] ?? '', array('table', 'opportunity'), true) || empty($d['items']) || empty($d['headings'])) {
        return null;
    }
    $cols = array();
    foreach ($d['headings'] as $h) {
        $key = $h['key'] ?? null;
        $type = $h['valueType'] ?? ($h['itemType'] ?? 'text');
        if (null === $key || 'thumbnail' === $type) {
            continue;
        }
        $cols[] = array($key, $type, ldrnseo_lh_text(is_array($h['label'] ?? $h['text'] ?? '') ? '' : ($h['label'] ?? $h['text'] ?? '')));
    }
    $rows = array();
    foreach (array_slice((array) $d['items'], 0, 8) as $item) {
        $row = array();
        foreach ($cols as $c) {
            $row[] = ldrnseo_lh_cell($item[$c[0]] ?? null, $c[1], $origin) ?: array('text', '');
        }
        $rows[] = $row;
    }
    return $cols ? array('head' => array_column($cols, 2), 'rows' => $rows, 'more' => max(0, count($d['items']) - 8)) : null;
}

/**
 * A Lighthouse result (lhr) to the stored report. $field is the PageSpeed
 * response's loadingExperience pair, when Google returned one.
 */
function ldrnseo_lh_reduce(array $lhr, $source, array $field = array()) {
    $audits  = (array) ($lhr['audits'] ?? array());
    $final   = (string) ($lhr['finalDisplayedUrl'] ?? $lhr['finalUrl'] ?? $lhr['requestedUrl'] ?? '');
    $p       = wp_parse_url($final);
    $origin  = isset($p['scheme'], $p['host']) ? $p['scheme'] . '://' . $p['host'] : '';
    $groups  = array();
    foreach ((array) ($lhr['categoryGroups'] ?? array()) as $gid => $g) {
        $groups[$gid] = (string) ($g['title'] ?? '');
    }

    $cats = array();
    $lists = array();
    $metrics = array();
    $acronyms = array('first-contentful-paint' => 'FCP', 'largest-contentful-paint' => 'LCP', 'total-blocking-time' => 'TBT', 'cumulative-layout-shift' => 'CLS', 'speed-index' => 'SI');
    foreach ((array) ($lhr['categories'] ?? array()) as $cid => $c) {
        $lists[$cid] = array('failed' => array(), 'manual' => array(), 'passed' => array(), 'na' => array());
        $scored = 0; $passed_w = 0;
        foreach ((array) ($c['auditRefs'] ?? array()) as $ref) {
            $a = $audits[$ref['id']] ?? null;
            if (!$a) {
                continue;
            }
            $mode  = (string) ($a['scoreDisplayMode'] ?? 'numeric');
            $group = (string) ($ref['group'] ?? '');
            if ('metrics' === $group && isset($acronyms[$ref['id']])) {
                $metrics[$acronyms[$ref['id']]] = array(
                    'title' => (string) $a['title'], 'desc' => ldrnseo_lh_text($a['description'] ?? ''),
                    'value' => (float) ($a['numericValue'] ?? 0), 'display' => str_replace("\u{a0}", ' ', (string) ($a['displayValue'] ?? '')),
                    'score' => isset($a['score']) ? (float) $a['score'] : null, 'weight' => (float) ($ref['weight'] ?? 0),
                );
                continue;
            }
            if ('hidden' === $group) {
                continue;
            }
            if (($ref['weight'] ?? 0) > 0 && 'notApplicable' !== $mode) {
                $scored++;
                $passed_w += isset($a['score']) && $a['score'] >= 0.9 ? 1 : 0;
            }
            $row = array(
                'id'      => (string) $ref['id'],
                'title'   => ldrnseo_lh_text($a['title'] ?? ''),
                'desc'    => ldrnseo_lh_text($a['description'] ?? ''),
                'display' => str_replace("\u{a0}", ' ', (string) ($a['displayValue'] ?? '')),
                'rating'  => ldrnseo_lh_rating($a['score'] ?? null, $mode),
                'group'   => $group,
                'metrics' => array_keys((array) ($a['metricSavings'] ?? array())),
                'ms'      => (int) round($a['details']['overallSavingsMs'] ?? ($a['metricSavings']['LCP'] ?? 0)),
                'bytes'   => (int) round($a['details']['overallSavingsBytes'] ?? 0),
            );
            if ('notApplicable' === $mode) {
                $lists[$cid]['na'][] = $row;
                continue;
            }
            if ('manual' === $mode) {
                $lists[$cid]['manual'][] = $row;
                continue;
            }
            $row['details'] = ldrnseo_lh_details($a['details'] ?? null, $origin);
            if (!empty($a['explanation'])) {
                $row['note'] = ldrnseo_lh_text($a['explanation']);
            }
            if (in_array($row['rating'], array('pass'), true)) {
                unset($row['details']);
                $lists[$cid]['passed'][] = $row;
            } else {
                $lists[$cid]['failed'][] = $row;
            }
        }
        // Worst first, then the biggest saving.
        $rank = array('error' => 0, 'fail' => 1, 'average' => 2, 'informative' => 3);
        usort($lists[$cid]['failed'], function ($x, $y) use ($rank) {
            return array($rank[$x['rating']] ?? 4, -$x['ms'], -$x['bytes']) <=> array($rank[$y['rating']] ?? 4, -$y['ms'], -$y['bytes']);
        });
        $cats[$cid] = array(
            'title' => (string) $c['title'],
            'score' => null === ($c['score'] ?? null) ? null : (int) round($c['score'] * 100),
            // Agentic browsing reads as a fraction in PageSpeed: weighted audits passed / applicable.
            'fraction' => 'agentic-browsing' === $cid ? array($passed_w, $scored) : null,
            'manual' => ldrnseo_lh_text($c['manualDescription'] ?? ''),
        );
    }

    $film = array();
    foreach ((array) ($audits['screenshot-thumbnails']['details']['items'] ?? array()) as $f) {
        $film[] = array('t' => (int) $f['timing'], 'img' => (string) $f['data']);
    }
    $env = (array) ($lhr['configSettings'] ?? array());
    $th  = (array) ($env['throttling'] ?? array());
    $mobile = 'mobile' === ($env['formFactor'] ?? 'mobile');
    return array(
        'at'      => isset($lhr['fetchTime']) ? (int) (strtotime($lhr['fetchTime']) * 1000) : time() * 1000,
        'source'  => $source,
        'url'     => $final,
        'engine'  => 'Lighthouse ' . ($lhr['lighthouseVersion'] ?? ''),
        'device'  => $mobile ? 'mobile' : 'desktop',
        'env'     => array(
            'device'  => $mobile ? 'Emulated Moto G Power' : 'Emulated desktop',
            'network' => $mobile ? 'Slow 4G throttling' : 'Custom throttling',
            'cpu'     => (float) ($th['cpuSlowdownMultiplier'] ?? 1),
            'rtt'     => (int) ($th['rttMs'] ?? 0),
            'kbps'    => (int) ($th['throughputKbps'] ?? 0),
            'bench'   => (int) ($lhr['environment']['benchmarkIndex'] ?? 0),
            'browser' => preg_match('#(HeadlessChrome|Chrome)/([\d.]+)#', (string) ($lhr['environment']['hostUserAgent'] ?? ''), $m) ? 'Chrome ' . $m[2] : '',
        ),
        'spread'  => isset($lhr['inuSpread']) ? array_map('intval', (array) $lhr['inuSpread']) : array(),
        'cats'    => $cats,
        'metrics' => $metrics,
        'audits'  => $lists,
        'groups'  => $groups,
        'shot'    => (string) ($audits['final-screenshot']['details']['data'] ?? ''),
        'film'    => $film,
        'field'   => $field,
    );
}

/** CrUX from a PageSpeed response: page and origin, each metric's p75, its three shares and Google's verdict. */
function ldrnseo_lh_field(array $psi) {
    $map = array('LARGEST_CONTENTFUL_PAINT_MS' => 'LCP', 'INTERACTION_TO_NEXT_PAINT' => 'INP', 'CUMULATIVE_LAYOUT_SHIFT_SCORE' => 'CLS',
                 'FIRST_CONTENTFUL_PAINT_MS' => 'FCP', 'EXPERIMENTAL_TIME_TO_FIRST_BYTE' => 'TTFB');
    $out = array();
    foreach (array('page' => 'loadingExperience', 'origin' => 'originLoadingExperience') as $k => $key) {
        $le = $psi[$key] ?? null;
        if (!is_array($le) || empty($le['metrics'])) {
            continue;
        }
        $row = array('verdict' => (string) ($le['overall_category'] ?? ''), 'metrics' => array());
        foreach ($map as $api => $name) {
            if (empty($le['metrics'][$api])) {
                continue;
            }
            $m = $le['metrics'][$api];
            $p = (float) ($m['percentile'] ?? 0);
            $row['metrics'][$name] = array(
                'p75' => 'CLS' === $name ? $p / 100 : $p,
                'cat' => (string) ($m['category'] ?? ''),
                'dist' => array_map(function ($d) { return (float) ($d['proportion'] ?? 0); }, (array) ($m['distributions'] ?? array())),
            );
        }
        $out[$k] = $row;
    }
    return $out;
}

/** Store a reduced report: its file, and its line in the index. */
function ldrnseo_lh_store($route, array $rep) {
    $dev  = 'desktop' === $rep['device'] ? 'desktop' : 'mobile';
    file_put_contents(ldrnseo_lh_dir() . '/' . ldrnseo_lh_slug($route) . '.' . $dev . '.json', wp_json_encode($rep));
    $idx = get_option('ldrnseo_lighthouse');
    if (!is_array($idx) || !isset($idx['runs'])) {
        $idx = array('runs' => array());
    }
    $fails = array();
    foreach ($rep['audits'] as $cid => $l) {
        foreach ($l['failed'] as $a) {
            if (in_array($a['rating'], array('fail', 'average', 'error'), true)) {
                $fails[] = array($cid, $a['id'], $a['title'], $a['rating']);
            }
        }
    }
    $idx['runs'][$route][$dev] = array(
        'at' => $rep['at'], 'source' => $rep['source'], 'engine' => $rep['engine'], 'cats' => $rep['cats'],
        'metrics' => array_map(function ($m) { return array('value' => $m['value'], 'display' => $m['display'], 'score' => $m['score']); }, $rep['metrics']),
        'fails' => $fails, 'field' => !empty($rep['field']),
    );
    $idx['at'] = max((int) ($idx['at'] ?? 0), (int) $rep['at']);
    $idx['source'] = $rep['source'];
    $idx['site'] = preg_replace('#^(https?://[^/]+).*$#', '$1/', $rep['url']);
    update_option('ldrnseo_lighthouse', $idx, false);
}

/**
 * Import the files tools/lighthouse-run.mjs wrote (<slug>.<device>.json).
 * Returns the number imported. Files are matched to routes by slug.
 */
function ldrnseo_lh_import_dir($dir, $source = 'local') {
    $by_slug = array();
    foreach (ldrnseo_routes() as $r) {
        $by_slug[ldrnseo_lh_slug($r)] = $r;
    }
    $n = 0;
    foreach ((array) glob(rtrim($dir, '/\\') . '/*.json') as $file) {
        if (!preg_match('/^(.+)\.(mobile|desktop)\.json$/', basename($file), $m) || !isset($by_slug[$m[1]])) {
            continue;
        }
        $lhr = json_decode((string) file_get_contents($file), true);
        // A run that never loaded the page (a 403 from a CDN, a timeout) has
        // categories with no scores; it is not a measurement of the page.
        if (!is_array($lhr) || empty($lhr['categories']) || !empty($lhr['runtimeError'])) {
            continue;
        }
        ldrnseo_lh_store($by_slug[$m[1]], ldrnseo_lh_reduce($lhr, $source));
        $n++;
    }
    return $n;
}

/** Run PageSpeed Insights (Google's servers) for one page on one device and store it. */
function ldrnseo_psi($route, $strategy = 'mobile') {
    $url = rtrim(ldrnseo_public_url(), '/') . ('/' === $route ? '/' : $route);
    $key = (string) ldrnseo_setting('psi_key');
    $q   = 'url=' . rawurlencode($url) . '&strategy=' . $strategy;
    foreach (array('performance', 'accessibility', 'best-practices', 'seo', 'agentic-browsing') as $c) {
        $q .= '&category=' . $c;
    }
    if ($key) {
        $q .= '&key=' . rawurlencode($key);
    }
    $res = wp_remote_get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed?' . $q, array('timeout' => 120));
    if (is_wp_error($res)) {
        return $res;
    }
    $d = json_decode((string) wp_remote_retrieve_body($res), true);
    if (!empty($d['error'])) {
        $msg = $d['error']['message'] ?? 'PageSpeed Insights refused the request.';
        if (429 === (int) ($d['error']['code'] ?? 0)) {
            $msg = $key ? 'The PageSpeed key has used its quota for today.' : 'Google\'s shared keyless quota is used up. Add the PageSpeed key in Settings.';
        } elseif (false !== stripos($msg, 'API key not valid')) {
            $msg = 'Google does not accept that PageSpeed key.';
        } elseif (false !== stripos($msg, 'FAILED_DOCUMENT_REQUEST') || false !== stripos($msg, 'Unable to process')) {
            $msg = 'Google could not reach ' . $url . '.';
        }
        return new WP_Error('ldrnseo_psi', $msg);
    }
    $rep = ldrnseo_lh_reduce((array) ($d['lighthouseResult'] ?? array()), 'psi', ldrnseo_lh_field($d));
    ldrnseo_lh_store($route, $rep);
    $c = $rep['cats'];
    return array('route' => $route, 'device' => $strategy, 'scores' => array_map(function ($x) { return $x['score']; }, $c));
}

/**
 * The whole site on one device: each category's median across pages, the
 * pages ranked by Performance, each metric per page, and the audits that
 * fail on the most pages (fix once in the theme, every page improves).
 */
function ldrnseo_lh_site($device) {
    $idx = get_option('ldrnseo_lighthouse');
    $runs = is_array($idx) ? (array) ($idx['runs'] ?? array()) : array();
    $pages = array();
    foreach (ldrnseo_routes() as $r) {
        if (isset($runs[$r][$device]['cats'])) {
            $pages[$r] = $runs[$r][$device];
        }
    }
    if (!$pages) {
        return null;
    }
    $median = function (array $v) {
        $v = array_values(array_filter($v, function ($x) { return null !== $x; }));
        if (!$v) {
            return null;
        }
        sort($v);
        $n = count($v);
        return $n % 2 ? $v[intdiv($n, 2)] : (int) round(($v[$n / 2 - 1] + $v[$n / 2]) / 2);
    };
    $cats = array();
    foreach (array('performance', 'accessibility', 'best-practices', 'seo', 'agentic-browsing') as $cid) {
        $vals = array();
        $title = '';
        foreach ($pages as $p) {
            if (isset($p['cats'][$cid])) {
                $vals[] = $p['cats'][$cid]['score'];
                $title = $p['cats'][$cid]['title'];
                if ('agentic-browsing' === $cid && !empty($p['cats'][$cid]['fraction'])) {
                    $vals[count($vals) - 1] = $p['cats'][$cid]['fraction'][1] ? (int) round(100 * $p['cats'][$cid]['fraction'][0] / $p['cats'][$cid]['fraction'][1]) : null;
                }
            }
        }
        if ($vals) {
            $cats[$cid] = array('title' => $title, 'score' => $median($vals), 'min' => min(array_filter($vals, 'is_int') ?: array(0)), 'pages' => count($vals));
        }
    }
    $common = array();
    foreach ($pages as $r => $p) {
        foreach ((array) ($p['fails'] ?? array()) as $f) {
            $k = $f[0] . '|' . $f[1];
            if (!isset($common[$k])) {
                $common[$k] = array('cat' => $f[0], 'id' => $f[1], 'title' => $f[2], 'rating' => $f[3], 'routes' => array());
            }
            $common[$k]['routes'][] = $r;
            if ('fail' === $f[3]) {
                $common[$k]['rating'] = 'fail';
            }
        }
    }
    usort($common, function ($a, $b) { return array(count($b['routes']), 'fail' === $b['rating']) <=> array(count($a['routes']), 'fail' === $a['rating']); });
    $at = 0; $sources = array();
    foreach ($pages as $p) {
        $at = max($at, (int) $p['at']);
        $sources[$p['source'] ?? 'local'] = true;
    }
    return array('pages' => $pages, 'cats' => $cats, 'common' => $common, 'at' => $at, 'sources' => array_keys($sources));
}
