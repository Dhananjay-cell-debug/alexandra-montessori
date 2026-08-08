<?php
/**
 * Pure saved-session summary contract. Does not boot WordPress.
 */

define('ABSPATH', __DIR__);

if (!function_exists('wp_json_encode')) {
	function wp_json_encode($value) {
		return json_encode($value);
	}
}

if (!function_exists('sanitize_key')) {
	function sanitize_key($value) {
		return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value));
	}
}

require_once dirname(__DIR__) . '/includes/class-am-vb-saved-sessions.php';

$failures = array();

$assert = static function ($condition, $label) use (&$failures) {
	if (!$condition) {
		$failures[] = $label;
	}
};

$first = AM_VB_Saved_Sessions::summarize_snapshots(
	array(),
	array(
		'sections' => array('hero' => array('visible' => true)),
		'elements' => array(
			'hero-title' => array('value' => 'Hello'),
			'hero-image' => array('src' => '/hero.jpg'),
		),
	),
	'home'
);
$assert(
	'First named save with 1 section setting and 2 editable elements.' === $first,
	'first_snapshot_summary'
);

$same_snapshot = array(
	'sections'       => array('hero' => array('visible' => true)),
	'elements'       => array('hero-title' => array('value' => 'Hello')),
	'collections'    => array(),
	'customSections' => array(),
);
$same = AM_VB_Saved_Sessions::summarize_snapshots($same_snapshot, $same_snapshot, 'home');
$assert(
	'Named checkpoint; no visual difference from the previous saved session.' === $same,
	'unchanged_checkpoint_summary'
);

$changed = AM_VB_Saved_Sessions::summarize_snapshots(
	array(
		'sections' => array('hero' => array('visible' => true)),
		'elements' => array('hero-title' => array('value' => 'Hello')),
	),
	array(
		'sections' => array(
			'hero'  => array('visible' => true, 'background' => '#ffffff'),
			'trust' => array('visible' => true),
		),
		'elements' => array(
			'hero-title' => array('value' => 'Welcome'),
			'trust-logo' => array('src' => '/trust.svg'),
		),
	),
	'home'
);
$assert(false !== strpos($changed, '2 sections (hero, trust)'), 'changed_sections_are_named');
$assert(false !== strpos($changed, '2 elements (hero-title, trust-logo)'), 'changed_elements_are_named');

$custom = AM_VB_Saved_Sessions::summarize_snapshots(
	array('customSections' => array()),
	array('customSections' => array(array('id' => 'story', 'name' => 'Our story'))),
	'home'
);
$assert(false !== strpos($custom, '1 custom section'), 'custom_section_change');

if ($failures) {
	fwrite(STDERR, 'Saved-session contract failures: ' . implode(', ', $failures) . PHP_EOL);
	exit(1);
}

echo "Saved-session summary contract passed (4/4).\n";
