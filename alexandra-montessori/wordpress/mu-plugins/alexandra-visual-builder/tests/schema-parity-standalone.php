<?php
/**
 * Full section + element round trip through BOTH servers, with hostile input.
 * Proves Home and the other pages now keep the same properties.
 */

define('ABSPATH', __DIR__ . '/');

function sanitize_key($k) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $k)); }
function sanitize_text_field($v) { return trim(strip_tags((string) $v)); }
function sanitize_textarea_field($v) { return trim(strip_tags((string) $v)); }
function sanitize_hex_color($c) { return preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', (string) $c) ? (string) $c : ''; }
function esc_url_raw($u, $p = null) {
	$u = trim((string) $u);
	if ('' === $u) { return ''; }
	$scheme = strtolower((string) parse_url($u, PHP_URL_SCHEME));
	if ('' === $scheme) { return 0 === strpos($u, '/') ? $u : ''; }
	return in_array($scheme, $p ?: array('http', 'https', 'mailto', 'tel'), true) ? $u : '';
}
function untrailingslashit($v) { return rtrim((string) $v, '/\\'); }
function trailingslashit($v) { return rtrim((string) $v, '/\\') . '/'; }
function wp_parse_url($u, $c = -1) { return parse_url($u, $c); }
function wp_json_encode($d, $f = 0) { return json_encode($d, $f); }
// Media normalisation runs inside both sanitisers now, so this harness needs
// just enough of the media surface for the round trip to complete.
function home_url($path = '/') { return 'https://alexandramontessori.co.uk' . $path; }
function is_ssl() { return true; }
function absint($v) { return abs((int) $v); }
function wp_upload_dir() { return array('basedir' => '/var/www/uploads', 'baseurl' => 'https://alexandramontessori.co.uk/wp-content/uploads', 'error' => false); }
function wp_get_attachment_url($id) { return false; }
function get_post_type($id) { return false; }
function attachment_url_to_postid($u) { return 0; }

$dir = __DIR__ . '/../includes';
require_once $dir . '/class-am-vb-style-schema.php';
require_once $dir . '/class-am-vb-media.php';
require_once $dir . '/class-am-vb-site-design.php';
require_once $dir . '/class-am-vb-home-design.php';

$pass = 0; $fail = 0;
function check($name, $ok, $detail = '') {
	global $pass, $fail;
	if ($ok) { $pass++; echo "  PASS  $name\n"; }
	else { $fail++; echo "  FAIL  $name   $detail\n"; }
}

/* A band styled with the new toolkit, plus hostile values. */
$section_payload = array(
	'visible'     => true,
	'radius'      => 99999,
	'borderWidth' => 3,
	'borderColor' => '#8a2be2',
	'borderStyle' => 'dashed',
	'shadow'      => 'url(javascript:alert(1))',
	'hover'       => 'lift',
	'animate'     => 'rise',
	'opacity'     => 85,
	'backgroundColor' => '#e9f1e6',
	'desktop'     => array('paddingY' => 64, 'gap' => 24, 'contentWidth' => 1100),
);

echo "=== SECTIONS: other page (curriculum) ===\n";
$m = new ReflectionMethod('AM_VB_Site_Design', 'sanitize');
$m->setAccessible(true);
$out = $m->invoke(null, 'curriculum', array('sections' => array('curriculum-philosophy' => $section_payload)));
$s = $out['sections']['curriculum-philosophy'];
echo '  kept: ' . implode(', ', array_keys($s)) . "\n";
check('radius clamped 99999 -> 200', 200 === $s['radius'], json_encode($s['radius'] ?? null));
check('hostile shadow -> none', 'none' === $s['shadow'], json_encode($s['shadow'] ?? null));
check('border colour survives', '#8a2be2' === $s['borderColor']);
check('borderStyle survives', 'dashed' === $s['borderStyle']);
check('hover survives', 'lift' === $s['hover']);
check('animate survives', 'rise' === $s['animate']);
check('opacity survives', 85 === $s['opacity']);
check('legacy paddingY still clamped', 64 === $s['desktop']['paddingY']);

echo "\n=== SECTIONS: Home ===\n";
$hm = new ReflectionMethod('AM_VB_Home_Design', 'sanitize');
$hm->setAccessible(true);
$hout = $hm->invoke(null, array('sections' => array('home-about' => $section_payload)));
$hs = $hout['sections']['home-about'];
echo '  kept: ' . implode(', ', array_keys($hs)) . "\n";
check('Home radius clamped', 200 === $hs['radius']);
check('Home hostile shadow -> none', 'none' === $hs['shadow']);
check('Home hover survives', 'lift' === $hs['hover']);

echo "\n=== PARITY: same property set on Home and elsewhere ===\n";
$home_keys = array_keys(AM_VB_Style_Schema::section_props('home-about'));
$other_keys = array_keys(AM_VB_Style_Schema::section_props('curriculum-philosophy'));
$only_home = array_diff($home_keys, $other_keys);
check(
	'other pages differ from Home only by the band-specific extra',
	array('imageSize') === array_values($only_home),
	'diff=' . implode(',', $only_home)
);
check('no property is exclusive to other pages', !array_diff($other_keys, $home_keys));

echo "\n=== REGION GATING enforced at SAVE, not just in the panel ===\n";
$leak = $m->invoke(null, 'curriculum', array('sections' => array(
	'curriculum-philosophy' => array('tint' => 50, 'overlay' => 90, 'imageSize' => 400, 'iconSize' => 40),
)));
$ls = $leak['sections']['curriculum-philosophy'];
check('tint rejected off-region', !isset($ls['tint']));
check('overlay rejected off-region', !isset($ls['overlay']));
check('imageSize rejected off-region', !isset($ls['imageSize']));

$hero = $hm->invoke(null, array('sections' => array('home-hero' => array(
	'tint' => 50, 'texture' => 20, 'overlay' => 90, 'desktop' => array('height' => 600, 'paddingY' => 90),
))));
$hh = $hero['sections']['home-hero'];
check('hero keeps its own tint', 50 === $hh['tint']);
check('hero keeps texture', 20 === $hh['texture']);
check('hero rejects overlay (testimonials only)', !isset($hh['overlay']));
check('hero keeps height', 600 === $hh['desktop']['height']);
check('hero rejects inert paddingY', !isset($hh['desktop']['paddingY']), json_encode($hh['desktop']));

echo "\n=== ELEMENTS: Home vs other page, identical surviving keys ===\n";
$el = array(
	'type' => 'image', 'src' => 'https://example.com/a.jpg',
	'radius' => 40, 'shadow' => 'soft', 'maskShape' => 'circle', 'brightness' => 120,
	'saturate' => 130, 'tintColor' => '#345b40', 'tintOpacity' => 40, 'objectFit' => 'contain',
	'desktop' => array('rotate' => 8, 'width' => 300, 'height' => 200),
);
$so = $m->invoke(null, 'curriculum', array('elements' => array('about-image' => $el)));
$ho = $hm->invoke(null, array('elements' => array('about-image' => $el)));
$sk = array_intersect(array_keys($so['elements']['about-image']), array_keys(AM_VB_Style_Schema::props_for_type('image')));
$hk = array_intersect(array_keys($ho['elements']['about-image']), array_keys(AM_VB_Style_Schema::props_for_type('image')));
sort($sk); sort($hk);
check('image element keeps identical schema keys on both paths', $sk === $hk, 'other=' . implode(',', $sk) . ' | home=' . implode(',', $hk));
check('device rotate survives on other page', 8 === $so['elements']['about-image']['desktop']['rotate']);
check('device width survives on other page', 300 === $so['elements']['about-image']['desktop']['width']);

echo "\n" . str_repeat('=', 60) . "\n";
echo "$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
