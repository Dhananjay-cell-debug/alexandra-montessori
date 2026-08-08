<?php
/**
 * Run with:
 * wp eval-file wp-content/mu-plugins/alexandra-visual-builder/tests/smoke.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$post     = AM_VB_Document::find(AM_VB_Document::HOME_SLUG);
$document = AM_VB_Document::get(AM_VB_Document::HOME_SLUG);
$encoded  = wp_json_encode($document);
$types    = array();
$elements = 0;

foreach ((array) ($document['sections'] ?? array()) as $section) {
	foreach ((array) ($section['elements'] ?? array()) as $element) {
		++$elements;
		$types[] = $element['type'] ?? '';
	}
}

$checks = array(
	'enabled'             => AM_VB_Plugin::enabled(),
	'post_exists'         => $post instanceof WP_Post,
	'private_post_type'   => !is_post_type_viewable(AM_VB_Document::POST_TYPE),
	'revisions_supported' => post_type_supports(AM_VB_Document::POST_TYPE, 'revisions'),
	'section_count'       => count((array) ($document['sections'] ?? array())),
	'element_count'       => $elements,
	'has_text'            => in_array('text', $types, true),
	'has_button'          => in_array('button', $types, true),
	'has_image'           => in_array('image', $types, true),
	'has_shape'           => in_array('shape', $types, true),
	'has_desktop_styles'  => false !== strpos($encoded, '"desktop"'),
	'has_tablet_styles'   => false !== strpos($encoded, '"tablet"'),
	'has_mobile_styles'   => false !== strpos($encoded, '"mobile"'),
	'has_test_marker'     => false !== strpos($encoded, 'revision-check')
		|| false !== strpos($encoded, 'underline-test'),
	'revision_count'      => $post ? count(AM_VB_Document::revisions($post->ID)) : 0,
);

WP_CLI::line(wp_json_encode($checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
