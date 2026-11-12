<?php
/**
 * The visual vocabulary of the SEO screen.
 *
 * Everything is inline SVG drawn on the server, in the nursery's own palette,
 * navy, forest and sky: the health gauge (a protractor - fine
 * graduations, a hairline needle), score rings, sparklines, the donut, the
 * vital gauges. Icons: Lucide (ISC) and Simple Icons (CC0), embedded from
 * assets/icons so nothing is fetched from anywhere at run time.
 */

defined('ABSPATH') || exit;

/* ------------------------------------------------------------------ icons */

function ldrnseo_svg_file($file) {
    static $cache = array();
    if (!isset($cache[$file])) {
        $path = LDRNSEO_DIR . 'assets/icons/' . $file . '.svg';
        $cache[$file] = is_readable($path) ? (string) file_get_contents($path) : '';
    }
    return $cache[$file];
}

/** A Lucide glyph, stroked in currentColor. */
function ldrnseo_icon($name, $class = '') {
    $svg = ldrnseo_svg_file('lu-' . $name);
    if ('' === $svg) {
        return '';
    }
    $svg = preg_replace('#<!--.*?-->#s', '', $svg);
    return preg_replace('#<svg\b[^>]*>#', '<svg class="is-ico ' . esc_attr($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">', $svg, 1);
}

/** Brand marks: name => [simple-icons file, colour]. */
function ldrnseo_brands() {
    return array(
        'Google' => array('google', '#4285F4'), 'Search Console' => array('googlesearchconsole', '#458CF5'), 'Analytics' => array('googleanalytics', '#F9AB00'),
        'Tag Manager' => array('googletagmanager', '#4F8DF5'), 'Maps' => array('googlemaps', '#34A853'), 'Bing' => array('microsoftbing', '#258FFA'),
        'ChatGPT' => array('openai', '#2B1E13'), 'OpenAI' => array('openai', '#2B1E13'), 'Perplexity' => array('perplexity', '#20B8CD'),
        'Claude' => array('claude', '#D97757'), 'Anthropic' => array('anthropic', '#D97757'), 'Gemini' => array('googlegemini', '#8E75FF'),
        'Instagram' => array('instagram', '#E4405F'), 'YouTube' => array('youtube', '#FF0033'), 'Facebook' => array('facebook', '#1877F2'),
        'Apple' => array('apple', '#2B1E13'), 'Behance' => array('behance', '#1769FF'), 'X' => array('x', '#2B1E13'),
        'WhatsApp' => array('whatsapp', '#25D366'), 'Pinterest' => array('pinterest', '#E60023'), 'DuckDuckGo' => array('duckduckgo', '#DE5833'),
        'Yandex' => array('yandex', '#FC3F1D'), 'Threads' => array('threads', '#2B1E13'), 'Reddit' => array('reddit', '#FF4500'),
    );
}

/** A brand mark in its colour; anything unknown becomes a monogram tile. */
function ldrnseo_brand($name, $size = 18) {
    $map = ldrnseo_brands();
    $key = null;
    foreach ($map as $label => $def) {
        if (0 === strcasecmp($label, $name) || (strlen($label) > 3 && false !== stripos($name, $label))) {
            $key = $label;
            break;
        }
    }
    if ($key) {
        $svg = ldrnseo_svg_file('si-' . $map[$key][0]);
        if (preg_match('#<path d="([^"]+)"#', $svg, $m)) {
            return '<svg class="is-brand" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-hidden="true" style="color:' . $map[$key][1] . '"><path fill="currentColor" d="' . esc_attr($m[1]) . '"/></svg>';
        }
    }
    $letter = strtoupper(mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: '?', 0, 1));
    return '<span class="is-mono" style="width:' . $size . 'px;height:' . $size . 'px;font-size:' . round($size * 0.55) . 'px" aria-hidden="true">' . esc_html($letter) . '</span>';
}

/* ---------------------------------------------------------------- numbers */

function ldrnseo_num($n) {
    if (null === $n) {
        return '–';
    }
    return $n >= 10000 ? number_format_i18n($n / 1000, 1) . 'k' : number_format_i18n($n);
}

function ldrnseo_ago($ts) {
    if (!$ts) {
        return 'never';
    }
    $ts = is_numeric($ts) ? (int) $ts : strtotime(get_gmt_from_date($ts) . ' UTC');
    $d  = time() - $ts;
    return $d < 90 ? 'just now' : human_time_diff($ts, time()) . ' ago';
}

function ldrnseo_delta($pct, $better_when_up = true) {
    if (null === $pct) {
        return '';
    }
    if ('new' === $pct) {
        return '<span class="is-delta is-up">' . ldrnseo_icon('trending-up') . 'new</span>';
    }
    if (0 === $pct) {
        return '<span class="is-delta">0%</span>';
    }
    $good = $better_when_up ? $pct > 0 : $pct < 0;
    return '<span class="is-delta ' . ($good ? 'is-up' : 'is-down') . '">' . ldrnseo_icon($pct > 0 ? 'trending-up' : 'trending-down') . abs($pct) . '%</span>';
}

function ldrnseo_band($score) {
    if (null === $score) {
        return 'none';
    }
    return $score >= 91 ? 'excellent' : ($score >= 71 ? 'good' : ($score >= 31 ? 'fair' : 'weak'));
}

/* ------------------------------------------------------------------ dials */

/**
 * The health dial: a protractor. 41 graduations on a half-circle darken to
 * the score, read left (weak) to right (good), with a hairline needle and the
 * number set large and plain.
 */
function ldrnseo_dial($score, $caption = 'Site health') {
    $w = 280; $h = 150; $cx = 140; $cy = 140; $r1 = 112; $r2 = 132;
    $ticks = 41; $from = 180; $to = 360;   // degrees, SVG frame (0 = right, clockwise)
    $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" class="is-dial-svg" aria-hidden="true"><defs>'
         . '<filter id="isGlow" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="2.4" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs>';
    for ($i = 0; $i < $ticks; $i++) {
        $f   = $i / ($ticks - 1);
        $deg = deg2rad($from + ($to - $from) * $f);
        $long = 0 === $i % 10;
        $a = $r1 - ($long ? 8 : 0);
        $x1 = $cx + $a * cos($deg); $y1 = $cy + $a * sin($deg);
        $x2 = $cx + $r2 * cos($deg); $y2 = $cy + $r2 * sin($deg);
        $lit = null !== $score && $f * 100 <= $score;
        $hue = $f < 0.3 ? 'r' : ($f < 0.7 ? 'a' : 'g');
        $svg .= '<line x1="' . round($x1, 1) . '" y1="' . round($y1, 1) . '" x2="' . round($x2, 1) . '" y2="' . round($y2, 1) . '" class="is-tick' . ($lit ? ' is-lit is-' . $hue : '') . ($long ? ' is-long' : '') . '"' . '' . '/>';
    }
    foreach (array(0, 50, 100) as $n) {
        $deg = deg2rad($from + ($to - $from) * $n / 100);
        $svg .= '<text x="' . round($cx + ($r1 - 22) * cos($deg), 1) . '" y="' . round($cy + ($r1 - 22) * sin($deg) + 4, 1) . '" class="is-dial-num" text-anchor="middle">' . $n . '</text>';
    }
    if (null !== $score) {
        $deg = deg2rad($from + ($to - $from) * $score / 100);
        $svg .= '<line x1="' . $cx . '" y1="' . $cy . '" x2="' . round($cx + ($r1 - 34) * cos($deg), 1) . '" y2="' . round($cy + ($r1 - 34) * sin($deg), 1) . '" class="is-needle"/>'
              . '<circle cx="' . $cx . '" cy="' . $cy . '" r="5" class="is-hub"/>';
    }
    $svg .= '</svg>';
    $band = ldrnseo_band($score);
    return '<div class="is-dial is-' . $band . '" role="img" aria-label="' . esc_attr($caption . ': ' . (null === $score ? 'not measured' : $score . ' of 100')) . '">' . $svg
         . '<div class="is-dial-read"><span class="is-dial-score">' . (null === $score ? '–' : (int) $score) . '</span>'
         . '<span class="is-dial-brush">' . esc_html(null === $score ? 'not yet' : $band) . '</span></div>'
         . '<span class="is-dial-cap">' . esc_html($caption) . '</span></div>';
}

/** A compact score ring (page cards, PageSpeed). */
function ldrnseo_ring($score, $size = 44, $label = '', $good = 80) {
    $r = ($size - 6) / 2;
    $c = 2 * M_PI * $r;
    // Lighthouse bands its scores at 90 / 50; page analysis at 80 / 50.
    $band = null === $score ? 'none' : ($score >= $good ? 'good' : ($score >= 50 ? 'fair' : 'weak'));
    return '<span class="is-ring is-' . $band . '" style="width:' . $size . 'px;height:' . $size . 'px"' . ($label ? ' title="' . esc_attr($label) . '"' : '') . '>'
         . '<svg viewBox="0 0 ' . $size . ' ' . $size . '" aria-hidden="true"><circle cx="' . ($size / 2) . '" cy="' . ($size / 2) . '" r="' . $r . '" class="is-ring-track"/>'
         . (null === $score ? '' : '<circle cx="' . ($size / 2) . '" cy="' . ($size / 2) . '" r="' . $r . '" class="is-ring-bar" stroke-dasharray="' . round($c * $score / 100, 2) . ' ' . round($c, 2) . '" transform="rotate(-90 ' . ($size / 2) . ' ' . ($size / 2) . ')"/>')
         . '</svg><b>' . (null === $score ? '–' : (int) $score) . '</b>' . ($label ? '<i>' . esc_html($label) . '</i>' : '') . '</span>';
}

/** A Core Web Vital as a half-dial with Google's three zones and a needle. */
function ldrnseo_vital_gauge($metric, $value, $n) {
    $lim = array('lcp' => array(2500, 4000, 'LCP', 'Loading'), 'inp' => array(200, 500, 'INP', 'Response'), 'cls' => array(100, 250, 'CLS', 'Stability'))[$metric];
    $w = 150; $h = 92; $cx = 75; $cy = 80; $r = 60;
    $arc = function ($a, $b) use ($cx, $cy, $r) {
        $p = function ($t) use ($cx, $cy, $r) { $d = deg2rad(180 + 180 * $t); return round($cx + $r * cos($d), 1) . ' ' . round($cy + $r * sin($d), 1); };
        return 'M ' . $p($a) . ' A ' . $r . ' ' . $r . ' 0 0 1 ' . $p($b);
    };
    $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" aria-hidden="true">'
         . '<path d="' . $arc(0, 0.33) . '" class="is-z is-zg"/><path d="' . $arc(0.345, 0.655) . '" class="is-z is-zn"/><path d="' . $arc(0.67, 1) . '" class="is-z is-zp"/>';
    $rating = '';
    if ($n && null !== $value) {
        $pos = ldrnseo_vital_pos($metric, $value) / 100;
        $d = deg2rad(180 + 180 * $pos);
        $svg .= '<line x1="' . $cx . '" y1="' . $cy . '" x2="' . round($cx + ($r - 12) * cos($d), 1) . '" y2="' . round($cy + ($r - 12) * sin($d), 1) . '" class="is-needle"/><circle cx="' . $cx . '" cy="' . $cy . '" r="4" class="is-hub"/>';
        $rating = ldrnseo_vital_rating($metric, $value);
    }
    $svg .= '</svg>';
    return '<div class="is-vg is-v-' . ($rating ?: 'none') . '" title="' . esc_attr($lim[2] . ': good under ' . ldrnseo_vital_format($metric, $lim[0]) . ', poor over ' . ldrnseo_vital_format($metric, $lim[1])) . '">' . $svg
         . '<b>' . ($n ? esc_html(ldrnseo_vital_format($metric, $value)) : '–') . '</b><span>' . esc_html($lim[2]) . ' <small>' . esc_html($lim[3]) . '</small></span>'
         . '<small class="is-vg-n">' . (int) $n . ' visits</small></div>';
}

/** Where a value sits on a good | needs-work | poor scale drawn in thirds. */
function ldrnseo_vital_pos($m, $v) {
    $lim = array('lcp' => array(2500, 4000), 'inp' => array(200, 500), 'cls' => array(100, 250))[$m];
    if ($v <= $lim[0]) {
        return round(33.3 * $v / $lim[0], 1);
    }
    if ($v <= $lim[1]) {
        return round(33.3 + 33.3 * ($v - $lim[0]) / ($lim[1] - $lim[0]), 1);
    }
    return round(min(98, 66.6 + 33.3 * ($v - $lim[1]) / $lim[1]), 1);
}

/* ---------------------------------------------------------- KPI & charts */

/** Sparkline with a glow and a fading wash. */
function ldrnseo_spark(array $values, $class = 'b') {
    $n = count($values);
    if ($n < 2) {
        return '';
    }
    $w = 200; $h = 46;
    $max = max(1, max($values));
    $pts = array();
    foreach (array_values($values) as $i => $v) {
        $pts[] = round($w * $i / ($n - 1), 1) . ',' . round($h - 4 - ($h - 10) * $v / $max, 1);
    }
    $id = 'sp' . wp_rand(1000, 999999);
    return '<svg class="is-spark is-' . $class . '" viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="none" aria-hidden="true">'
         . '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="currentColor" stop-opacity=".35"/><stop offset="1" stop-color="currentColor" stop-opacity="0"/></linearGradient></defs>'
         . '<polygon points="0,' . $h . ' ' . implode(' ', $pts) . ' ' . $w . ',' . $h . '" fill="url(#' . $id . ')"/>'
         . '<polyline points="' . implode(' ', $pts) . '" fill="none" stroke="currentColor" stroke-width="2" vector-effect="non-scaling-stroke"/></svg>';
}

/** A KPI card: glyph, label, the number, its change, a sparkline. */
function ldrnseo_kpi($icon, $label, $value, $delta = '', $foot = '', array $spark = array(), $href = '', $tip = '') {
    $tag = $href ? 'a href="' . esc_url($href) . '"' : 'div';
    $end = $href ? 'a' : 'div';
    return '<' . $tag . ' class="is-kpi"' . ($tip ? ' title="' . esc_attr($tip) . '"' : '') . '>'
         . '<span class="is-kpi-head">' . ldrnseo_icon($icon) . '<span>' . esc_html($label) . '</span>' . $delta . '</span>'
         . '<span class="is-kpi-value">' . $value . '</span>'
         . ($foot ? '<span class="is-kpi-foot">' . $foot . '</span>' : '')
         . ($spark && array_sum($spark) ? ldrnseo_spark($spark) : '<span class="is-kpi-flat"></span>')
         . '</' . $end . '>';
}

/** Donut: segments [label, value, class]. */
function ldrnseo_donut(array $segments, $center, $sub = '') {
    $total = array_sum(array_column($segments, 1));
    $r = 54; $c = 2 * M_PI * $r; $off = 0;
    $svg = '<svg viewBox="0 0 140 140" aria-hidden="true"><circle cx="70" cy="70" r="' . $r . '" class="is-donut-track"/>';
    foreach ($segments as $s) {
        if (!$total || !$s[1]) {
            continue;
        }
        $len = $c * $s[1] / $total;
        $svg .= '<circle cx="70" cy="70" r="' . $r . '" class="is-seg-' . esc_attr($s[2]) . '" stroke-dasharray="' . round(max(0, $len - 3), 2) . ' ' . round($c, 2) . '" stroke-dashoffset="' . round(-$off, 2) . '" transform="rotate(-90 70 70)"/>';
        $off += $len;
    }
    $svg .= '</svg>';
    $legend = '<ul class="is-donut-legend">';
    foreach ($segments as $s) {
        $legend .= '<li><i class="is-seg-' . esc_attr($s[2]) . '"></i>' . $s[0] . '<b>' . ldrnseo_num($s[1]) . '</b></li>';
    }
    return '<div class="is-donut"><div class="is-donut-ring">' . $svg . '<span><b>' . $center . '</b>' . ($sub ? '<small>' . esc_html($sub) . '</small>' : '') . '</span></div>' . $legend . '</ul></div>';
}

/**
 * Line chart, one or two series over days: glowing lines, a wash, one axis,
 * hairline grid. seo.js adds the crosshair read-out from data-points.
 */
function ldrnseo_line_chart(array $rows, array $series, $label) {
    $w = 900; $h = 236; $pl = 34; $pr = 10; $pt = 22; $pb = 26;
    $n = count($rows);
    if (!$n) {
        return '';
    }
    $max = 1;
    foreach ($rows as $r) {
        foreach ($series as $s) {
            $max = max($max, (float) $r[$s['key']]);
        }
    }
    $raw  = $max / 3;
    $mag  = pow(10, floor(log10(max(1, $raw))));
    $step = max(1, ceil($raw / $mag) * $mag);
    $top  = $step * ceil($max / $step);
    $x = function ($i) use ($n, $w, $pl, $pr) { return $n > 1 ? $pl + ($w - $pl - $pr) * $i / ($n - 1) : ($pl + $w - $pr) / 2; };
    $y = function ($v) use ($top, $h, $pt, $pb) { return $pt + ($h - $pt - $pb) * (1 - $v / $top); };
    $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" class="is-chart-svg" aria-hidden="true"><defs>'
         . '<linearGradient id="isWashB" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3A5446" stop-opacity=".22"/><stop offset="1" stop-color="#3A5446" stop-opacity="0"/></linearGradient>'
         . '<linearGradient id="isWashA" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#213472" stop-opacity=".22"/><stop offset="1" stop-color="#213472" stop-opacity="0"/></linearGradient>'
         . '<filter id="isLineGlow" x="-5%" y="-30%" width="110%" height="160%"><feGaussianBlur stdDeviation="3" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs>';
    for ($t = 0; $t <= $top + 0.001; $t += $step) {
        $yy = round($y($t), 1);
        $svg .= '<line x1="' . $pl . '" x2="' . ($w - $pr) . '" y1="' . $yy . '" y2="' . $yy . '" class="is-grid"/><text x="' . ($pl - 8) . '" y="' . ($yy + 4) . '" class="is-axis" text-anchor="end">' . esc_html(ldrnseo_num($t)) . '</text>';
    }
    foreach (array_unique(array(0, (int) floor(($n - 1) / 2), $n - 1)) as $i) {
        $anchor = 0 === $i ? 'start' : ($n - 1 === $i ? 'end' : 'middle');
        $svg .= '<text x="' . round($x($i), 1) . '" y="' . ($h - 6) . '" class="is-axis" text-anchor="' . $anchor . '">' . esc_html(wp_date('j M', strtotime($rows[$i]['day']))) . '</text>';
    }
    foreach ($series as $s) {
        $pts = array();
        foreach ($rows as $i => $r) {
            $pts[] = round($x($i), 1) . ',' . round($y((float) $r[$s['key']]), 1);
        }
        $svg .= '<polygon fill="url(#isWash' . strtoupper($s['class']) . ')" points="' . $pl . ',' . $y(0) . ' ' . implode(' ', $pts) . ' ' . round($x($n - 1), 1) . ',' . $y(0) . '"/>'
              . '<polyline class="is-line is-' . $s['class'] . '" points="' . implode(' ', $pts) . '"' . '' . '/>';
        list($lx, $ly) = explode(',', end($pts));
        $svg .= '<circle class="is-dot is-' . $s['class'] . '" cx="' . $lx . '" cy="' . $ly . '" r="4"/>';
    }
    $svg .= '<line class="is-cross" x1="0" x2="0" y1="' . $pt . '" y2="' . ($h - $pb) . '" visibility="hidden"/></svg>';
    $points = array();
    foreach ($rows as $i => $r) {
        $p = array('x' => round($x($i) / $w, 4), 'd' => wp_date('D j M', strtotime($r['day'])));
        foreach ($series as $s) {
            $p[$s['key']] = (int) $r[$s['key']];
        }
        $points[] = $p;
    }
    $legend = '';
    if (count($series) > 1) {
        $legend = '<ul class="is-legend">';
        foreach ($series as $s) {
            $legend .= '<li><i class="is-key is-' . $s['class'] . '"></i>' . esc_html($s['label']) . '</li>';
        }
        $legend .= '</ul>';
    }
    $table = '<table class="screen-reader-text"><caption>' . esc_html($label) . '</caption>';
    foreach ($rows as $r) {
        $table .= '<tr><td>' . esc_html($r['day']) . '</td>';
        foreach ($series as $s) {
            $table .= '<td>' . (int) $r[$s['key']] . '</td>';
        }
        $table .= '</tr>';
    }
    $table .= '</table>';
    return '<figure class="is-chart" data-series="' . esc_attr(wp_json_encode(array_map(function ($s) { return array($s['key'], $s['label'], $s['class']); }, $series))) . '" data-points="' . esc_attr(wp_json_encode($points)) . '">'
         . $legend . '<div class="is-chart-plot">' . $svg . '<div class="is-tip" hidden></div></div>' . $table . '</figure>';
}

/** Ranked rows with a brand mark and a glowing bar. */
function ldrnseo_bars(array $rows, $empty = '') {
    if (!$rows) {
        return $empty ? '<p class="is-empty">' . $empty . '</p>' : '';
    }
    $max = max(1, max(array_map(function ($r) { return $r['value']; }, $rows)));
    $out = '<ol class="is-bars">';
    foreach ($rows as $r) {
        $out .= '<li>' . ($r['mark'] ?? '') . '<span class="is-bar-label">' . $r['label'] . '</span>'
              . '<span class="is-bar-track"><i style="width:' . round(100 * $r['value'] / $max, 1) . '%"></i></span>'
              . '<span class="is-bar-value">' . esc_html(ldrnseo_num($r['value'])) . '</span></li>';
    }
    return $out . '</ol>';
}

/* ------------------------------------------------------------ small parts */

/** A status chip: a glyph and a word, coloured by state. */
function ldrnseo_sev($sev, $word = null) {
    $def = array('error' => array('x', 'Error'), 'warning' => array('alert-triangle', 'Warning'), 'notice' => array('info', 'Notice'),
                 'good' => array('check', 'Good'), 'ni' => array('alert-triangle', 'Needs work'), 'poor' => array('x', 'Poor'),
                 'ok' => array('check', 'Done'), 'todo' => array('circle-dot', 'To do'), 'na' => array('clock', 'Later'));
    $d = $def[$sev] ?? array('circle-dot', $sev);
    return '<span class="is-chip is-chip-' . esc_attr($sev) . '">' . ldrnseo_icon($d[0]) . (false === $word ? '' : esc_html($word ?? $d[1])) . '</span>';
}

function ldrnseo_section($title, $aside = '', $id = '', $icon = '') {
    return '<section class="is-section"' . ($id ? ' id="' . esc_attr($id) . '"' : '') . '><header class="is-section-head"><h2>' . ($icon ? ldrnseo_icon($icon) : '') . esc_html($title) . '</h2>'
         . ($aside ? '<div class="is-section-aside">' . $aside . '</div>' : '') . '</header>';
}

function ldrnseo_field($label, $control, $help = '', $for = '') {
    return '<div class="is-field"><label' . ($for ? ' for="' . esc_attr($for) . '"' : '') . '>' . esc_html($label) . '</label>'
         . '<div class="is-control">' . $control . ($help ? '<p class="is-help">' . $help . '</p>' : '') . '</div></div>';
}

function ldrnseo_input($name, $value, array $attrs = array()) {
    $a = '';
    foreach ($attrs as $k => $v) {
        $a .= ' ' . $k . '="' . esc_attr($v) . '"';
    }
    return '<input type="text" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"' . $a . '>';
}

function ldrnseo_period_switch($tab, $days) {
    $out = '<div class="is-seg" role="group" aria-label="Period">';
    foreach (array(7 => '7D', 28 => '28D', 90 => '3M') as $d => $label) {
        $out .= '<a href="' . esc_url(ldrnseo_url($tab, array('days' => $d))) . '"' . ($d === $days ? ' aria-current="true"' : '') . '>' . esc_html($label) . '</a>';
    }
    return $out . '</div>';
}

function ldrnseo_days() {
    $d = isset($_GET['days']) ? (int) $_GET['days'] : 28;
    return in_array($d, array(7, 28, 90), true) ? $d : 28;
}

function ldrnseo_route_name($route) {
    static $list = null;
    if (null === $list) {
        $list = ldrnseo_pages_list();
    }
    return isset($list[$route]) ? wp_strip_all_tags($list[$route]['label']) : $route;
}

/** An empty state: one glyph, one line. */
function ldrnseo_void($icon, $line) {
    return '<div class="is-void">' . ldrnseo_icon($icon) . '<span>' . $line . '</span></div>';
}

/* ----------------------------------------------------- more chart forms */

/**
 * Columns: one value per category on a single y axis, or two side by side
 * (a pair per category, a legend above). Values are labelled on the cap only
 * when there are few columns; the axis carries the rest.
 *
 * @param array $rows   [ ['label' => 'Mon', 'a' => 12, 'b' => 3], ... ]
 * @param array $series [ ['a', 'All visitors', 'a'], ['b', 'From search', 'b'] ]
 */
function ldrnseo_columns(array $rows, array $series, $label, $h = 190, $suffix = '') {
    if (!$rows) {
        return '';
    }
    $w = 640; $pl = 30; $pr = 6; $pt = 18; $pb = 24;
    $n = count($rows); $k = count($series);
    $max = 1;
    foreach ($rows as $r) {
        foreach ($series as $s) {
            $max = max($max, (float) $r[$s[0]]);
        }
    }
    $raw  = $max / 3;
    $mag  = pow(10, floor(log10(max(1, $raw))));
    $step = max(1, ceil($raw / $mag) * $mag);
    $top  = $step * ceil($max / $step);
    $band = ($w - $pl - $pr) / $n;
    $bw   = min(26, ($band * 0.62) / $k);
    $y = function ($v) use ($top, $h, $pt, $pb) { return $pt + ($h - $pt - $pb) * (1 - $v / $top); };
    $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" class="is-chart-svg" aria-hidden="true">';
    for ($t = 0; $t <= $top + 0.001; $t += $step) {
        $yy = round($y($t), 1);
        $svg .= '<line x1="' . $pl . '" x2="' . ($w - $pr) . '" y1="' . $yy . '" y2="' . $yy . '" class="is-grid"/><text x="' . ($pl - 6) . '" y="' . ($yy + 4) . '" class="is-axis" text-anchor="end">' . esc_html(ldrnseo_num($t)) . '</text>';
    }
    foreach ($rows as $i => $r) {
        $cx = $pl + $band * ($i + 0.5);
        foreach ($series as $j => $s) {
            $v = (float) $r[$s[0]];
            $x = $cx - ($bw * $k) / 2 + $bw * $j + 1;
            $yy = $y($v);
            $hh = max(0, $y(0) - $yy);
            // 4px rounded data end, square at the baseline.
            $rr = min(4, $hh, ($bw - 2) / 2);
            $svg .= '<path class="is-col is-' . $s[2] . '" d="M' . round($x, 1) . ',' . round($y(0), 1) . ' V' . round($yy + $rr, 1) . ' Q' . round($x, 1) . ',' . round($yy, 1) . ' ' . round($x + $rr, 1) . ',' . round($yy, 1)
                  . ' H' . round($x + $bw - 2 - $rr, 1) . ' Q' . round($x + $bw - 2, 1) . ',' . round($yy, 1) . ' ' . round($x + $bw - 2, 1) . ',' . round($yy + $rr, 1) . ' V' . round($y(0), 1) . ' Z"><title>' . esc_html($r['label'] . ' · ' . $s[1] . ': ' . ldrnseo_num($v) . $suffix) . '</title></path>';
            if ($n <= 12 && $v > 0) {
                $svg .= '<text x="' . round($x + ($bw - 2) / 2, 1) . '" y="' . round($yy - 5, 1) . '" class="is-cap" text-anchor="middle">' . esc_html(ldrnseo_num($v)) . '</text>';
            }
        }
        $svg .= '<text x="' . round($cx, 1) . '" y="' . ($h - 7) . '" class="is-axis" text-anchor="middle">' . esc_html($r['label']) . '</text>';
    }
    $svg .= '</svg>';
    $legend = '';
    if ($k > 1) {
        $legend = '<ul class="is-legend">';
        foreach ($series as $s) {
            $legend .= '<li><i class="is-key is-sq is-' . $s[2] . '"></i>' . esc_html($s[1]) . '</li>';
        }
        $legend .= '</ul>';
    }
    return '<figure class="is-chart" role="img" aria-label="' . esc_attr($label) . '">' . $legend . $svg . '</figure>';
}

/**
 * Dumbbell: two values per row on one 0-100 scale (phone and desktop
 * Lighthouse scores), joined by a line so the gap reads at a glance.
 * The scale is shaded with Lighthouse's own bands.
 */
function ldrnseo_dumbbell(array $rows, array $names = array('Phone', 'Desktop')) {
    $out = '<div class="is-db"><ul class="is-legend"><li><i class="is-dot-key is-a"></i>' . esc_html($names[0]) . '</li><li><i class="is-dot-key is-b"></i>' . esc_html($names[1]) . '</li></ul>'
         . '<div class="is-db-scale"><span></span><span style="left:0%">0</span><span style="left:50%">50</span><span style="left:90%">90</span><span style="left:100%">100</span></div>';
    foreach ($rows as $r) {
        $a = $r['a']; $b = $r['b'];
        $lo = min($a ?? 0, $b ?? 0); $hi = max($a ?? 0, $b ?? 0);
        $out .= '<div class="is-db-row"' . (!empty($r['href']) ? ' data-href="' . esc_url($r['href']) . '"' : '') . '><span class="is-db-label">' . esc_html($r['label']) . '</span>'
              . '<span class="is-db-track"><i class="is-db-zone"></i><i class="is-db-line" style="left:' . $lo . '%;width:' . ($hi - $lo) . '%"></i>'
              . (null === $a ? '' : '<b class="is-db-dot is-a" style="left:' . $a . '%" title="' . esc_attr($names[0] . ' ' . $a) . '"></b>')
              . (null === $b ? '' : '<b class="is-db-dot is-b" style="left:' . $b . '%" title="' . esc_attr($names[1] . ' ' . $b) . '"></b>') . '</span>'
              . '<span class="is-db-vals"><em class="is-a">' . (null === $a ? '–' : (int) $a) . '</em><em class="is-b">' . (null === $b ? '–' : (int) $b) . '</em></span></div>';
    }
    return $out . '</div>';
}

/** Heatmap: visits by weekday and hour, one hue, light to dark. */
function ldrnseo_heatmap(array $grid) {
    $max = 0;
    foreach ($grid as $row) {
        $max = max($max, max($row));
    }
    $days = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
    $out = '<div class="is-heat" role="img" aria-label="Visits by weekday and hour"><span></span>';
    for ($h = 0; $h < 24; $h++) {
        $out .= '<span class="is-heat-h">' . (0 === $h % 3 ? str_pad((string) $h, 2, '0', STR_PAD_LEFT) : '') . '</span>';
    }
    foreach ($days as $d => $name) {
        $out .= '<span class="is-heat-d">' . $name . '</span>';
        for ($h = 0; $h < 24; $h++) {
            $v = $grid[$d][$h] ?? 0;
            $out .= '<i style="--v:' . ($max ? round($v / $max, 3) : 0) . '" title="' . esc_attr($name . ' ' . str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00 · ' . $v . ' visits') . '"></i>';
        }
    }
    return $out . '</div>';
}

/** The PageSpeed score dial: a big ring with the number, banded 90 / 50. */
function ldrnseo_lh_score($score, $title, $size = 96) {
    return '<div class="is-lh">' . ldrnseo_ring($score, $size, '', 90) . '<span>' . esc_html($title) . '</span></div>';
}
