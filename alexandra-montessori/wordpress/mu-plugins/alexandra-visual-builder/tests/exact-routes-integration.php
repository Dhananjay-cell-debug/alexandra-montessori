<?php
/**
 * Exact-route registry, ownership and sanitisation checks.
 *
 * Run with:
 * wp eval-file wp-content/mu-plugins/alexandra-visual-builder/tests/exact-routes-integration.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

wp_set_current_user(1);

$results = array();
$record = static function ($name, $passed, $detail = '') use (&$results) {
	$results[$name] = array('passed' => (bool) $passed, 'detail' => (string) $detail);
};

try {
	$registry = AM_VB_Site_Design::registry();
	$required = array(
		'home-poc', 'nurseries', 'nurseries-hounslow', 'nurseries-heston', 'nurseries-hammersmith',
		'curriculum', 'careers', 'careers-vacancies', 'careers-vacancy-template', 'careers-apply',
		'events', 'event-detail-template', 'contact', 'contact-hounslow', 'contact-heston',
		'contact-hammersmith', 'check-availability', 'fees', 'fee-calculator', 'funded-childcare',
		'blogs', 'blog-detail-template', 'food-hygiene-rating',
	);
	$record('all_required_routes_registered', !array_diff($required, array_keys($registry)), wp_json_encode(array_diff($required, array_keys($registry))));
	$record('registry_has_full_site_depth', count($registry) >= count($required), 'registered: ' . count($registry));

	$region_ids_are_unique = true;
	$documents_exist = true;
	$global_not_duplicated = true;
	foreach ($registry as $slug => $page) {
		$ids = array_map(static function ($region) { return $region['id']; }, $page['regions']);
		if (count($ids) !== count(array_unique($ids))) {
			$region_ids_are_unique = false;
		}
		if (!AM_VB_Document::find($slug)) {
			$documents_exist = false;
		}
		if ('home-poc' !== $slug) {
			$defaults = AM_VB_Site_Design::defaults($slug);
			if (isset($defaults['sections']['global-header']) || isset($defaults['sections']['global-footer'])) {
				$global_not_duplicated = false;
			}
		}
	}
	$record('region_ids_unique_per_page', $region_ids_are_unique);
	$record('every_exact_route_has_workflow_document', $documents_exist);
	$record('global_design_not_duplicated_into_pages', $global_not_duplicated);

	$curriculum = AM_VB_Site_Design::defaults('curriculum');
	$record(
		'curriculum_matches_planned_sections',
		4 === count($curriculum['sections'])
			&& isset($curriculum['sections']['curriculum-philosophy'])
			&& isset($curriculum['sections']['curriculum-together'])
	);
	$availability = AM_VB_Site_Design::defaults('check-availability');
	$record('availability_has_thirteen_planned_regions', 13 === count($availability['sections']));
	$record(
		'navigation_categories_are_explicit',
		'menu' === $registry['fees']['navigation']
			&& 'menu' === $registry['blogs']['navigation']
			&& 'navbar' === $registry['careers']['navigation']
			&& 'navbar' === $registry['check-availability']['navigation']
	);

	$organizer_reflection = new ReflectionMethod('AM_VB_Organizer', 'sanitize');
	$organizer_reflection->setAccessible(true);
	$organized = $organizer_reflection->invoke(
		null,
		array(
			'order' => array('fees', 'fees', 'invented-page', 'home-poc'),
			'labels' => array('fees' => '<b>Parent fees</b>', 'invented-page' => 'Unsafe'),
			'buckets' => array('fees' => 'navbar', 'careers' => 'unknown'),
			'hidden' => array('food-hygiene-rating', 'invented-page'),
		)
	);
	$record(
		'organizer_is_persistent_and_bounded',
		array_slice($organized['order'], 0, 2) === array('fees', 'home-poc')
			&& 'Parent fees' === $organized['labels']['fees']
			&& 'navbar' === $organized['buckets']['fees']
			&& !isset($organized['buckets']['careers'])
			&& array('food-hygiene-rating') === $organized['hidden']
	);

	$reflection = new ReflectionMethod('AM_VB_Site_Design', 'sanitize');
	$reflection->setAccessible(true);
	$sanitised = $reflection->invoke(
		null,
		'curriculum',
		array(
			'sections' => array(
				'curriculum-philosophy' => array(
					'backgroundColor' => '#123456',
					'desktop' => array('paddingY' => 9999, 'gap' => -9, 'contentWidth' => 9999),
				),
				'invented-section' => array('visible' => false),
			),
			'elements' => array(
				'safe-copy' => array('type' => 'text', 'value' => '<script>bad()</script>Hello', 'href' => 'javascript:bad()'),
				'unsafe-type' => array('type' => 'iframe', 'src' => 'https://example.com'),
			),
		)
	);
	$record(
		'section_values_are_bounded',
		200 === $sanitised['sections']['curriculum-philosophy']['desktop']['paddingY']
			&& 0 === $sanitised['sections']['curriculum-philosophy']['desktop']['gap']
			&& 1600 === $sanitised['sections']['curriculum-philosophy']['desktop']['contentWidth']
			&& !isset($sanitised['sections']['invented-section'])
	);
	$record(
		'element_payload_is_safe',
		'' === $sanitised['elements']['safe-copy']['href']
			&& false === strpos($sanitised['elements']['safe-copy']['value'], '<script>')
			&& !isset($sanitised['elements']['unsafe-type'])
	);

	$resolved = AM_VB_Site_Design::editor_registry();
	$record(
		'editor_receives_capability_protected_canvas_urls',
		!empty($resolved['fees']['canvasUrl'])
			&& false !== strpos($resolved['fees']['canvasUrl'], '/fees')
			&& false !== strpos($resolved['fees']['canvasUrl'], 'am_visual_canvas=fees')
	);
} catch (Throwable $error) {
	$record('uncaught_exception', false, $error->getMessage());
}

$failed = array_filter($results, static function ($result) { return empty($result['passed']); });
WP_CLI::line(wp_json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
if ($failed) {
	WP_CLI::error('Exact-route integration checks failed: ' . implode(', ', array_keys($failed)));
}
WP_CLI::success('All exact-route integration checks passed.');
