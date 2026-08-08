<?php
/** Do custom free layers - including shapes - now keep their schema properties? */

define('ABSPATH', __DIR__ . '/');
function sanitize_key($k) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $k)); }
function sanitize_text_field($v) { return trim(strip_tags((string) $v)); }
function sanitize_textarea_field($v) { return trim(strip_tags((string) $v)); }
function sanitize_hex_color($c) { return preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', (string) $c) ? (string) $c : ''; }
function esc_url_raw($u, $p = null) {
	$u = trim((string) $u);
	if ('' === $u) { return ''; }
	$s = strtolower((string) parse_url($u, PHP_URL_SCHEME));
	if ('' === $s) { return 0 === strpos($u, '/') ? $u : ''; }
	return in_array($s, $p ?: array('http', 'https', 'mailto', 'tel'), true) ? $u : '';
}

// The sanitiser normalises media now, so stub just enough of that surface.
function trailingslashit($v) { return rtrim((string) $v, '/\\') . '/'; }
function wp_parse_url($u, $c = -1) { return parse_url($u, $c); }
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
require_once $dir . '/class-am-vb-home-design.php';

$pass = 0; $fail = 0;
function check($n, $ok, $d = '') {
	global $pass, $fail;
	if ($ok) { $pass++; echo "  PASS  $n\n"; } else { $fail++; echo "  FAIL  $n  $d\n"; }
}

$styled = array(
	'shadow' => 'medium', 'hover' => 'zoom', 'animate' => 'fade',
	'opacity' => 70, 'borderWidth' => 2, 'borderColor' => '#345b40', 'borderStyle' => 'dotted',
	'desktop' => array('rotate' => 15, 'padding' => 24),
);

$m = new ReflectionMethod('AM_VB_Home_Design', 'sanitize');
$m->setAccessible(true);

$input = array(
	'customSections' => array(array(
		'id' => 'band1', 'name' => 'My band', 'after' => 'home-trust',
		'items' => array(
			array('id' => 'sh1', 'type' => 'shape', 'name' => 'Blob'),
			array('id' => 'tx1', 'type' => 'text', 'name' => 'Headline'),
			array('id' => 'im1', 'type' => 'image', 'name' => 'Photo'),
		),
	)),
	'elements' => array(
		'custom-band1-sh1' => array_merge($styled, array('type' => 'shape')),
		'custom-band1-tx1' => array_merge($styled, array('type' => 'textarea', 'value' => 'Hello')),
		'custom-band1-im1' => array_merge($styled, array('type' => 'image', 'maskShape' => 'circle', 'brightness' => 120)),
	),
);

$out = $m->invoke(null, $input);

foreach (array('sh1' => 'shape', 'tx1' => 'text', 'im1' => 'image') as $id => $label) {
	$rec = $out['elements']['custom-band1-' . $id] ?? array();
	echo "-- $label layer (custom-band1-$id)\n";
	echo '   keys: ' . implode(', ', array_keys($rec)) . "\n";
	check("$label keeps shadow", ($rec['shadow'] ?? null) === 'medium');
	check("$label keeps hover", ($rec['hover'] ?? null) === 'zoom');
	check("$label keeps animate", ($rec['animate'] ?? null) === 'fade');
	check("$label keeps opacity", ($rec['opacity'] ?? null) === 70);
	check("$label keeps border colour", ($rec['borderColor'] ?? null) === '#345b40');
	check("$label keeps device rotate", ($rec['desktop']['rotate'] ?? null) === 15);
}

$img = $out['elements']['custom-band1-im1'];
check('image layer keeps mask', ($img['maskShape'] ?? null) === 'circle');
check('image layer keeps brightness', ($img['brightness'] ?? null) === 120);

$txt = $out['elements']['custom-band1-tx1'];
check('text layer has NO media-only mask control stored', !isset($txt['maskShape']));
check('text layer keeps its typed value', ($txt['value'] ?? null) === 'Hello');

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
