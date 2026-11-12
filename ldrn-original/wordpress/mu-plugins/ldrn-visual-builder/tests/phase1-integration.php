<?php
/**
 * Phase 1 integration checks.
 *
 * Run with:
 * wp eval-file wp-content/mu-plugins/ldrn-visual-builder/tests/phase1-integration.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

wp_set_current_user(1);

$results     = array();
$test_post   = null;
$template_id = 0;
$record      = static function ($name, $passed, $detail = '') use (&$results) {
	$results[$name] = array(
		'passed' => (bool) $passed,
		'detail' => (string) $detail,
	);
};

try {
	$record('local_only_gate', LDRN_VB_Plugin::enabled(), wp_get_environment_type());
	$record('schema_v2', 2 === LDRN_VB_Document::SCHEMA_VERSION);
	$record('feature_flags', !in_array(false, LDRN_VB_Features::all(), true));
	$record(
		'dedicated_capabilities',
		current_user_can('edit_ldrn_visual_pages')
			&& current_user_can('publish_ldrn_visual_pages')
			&& current_user_can('manage_ldrn_visual_templates')
			&& current_user_can('view_ldrn_visual_audit')
	);

	$malformed = array(
		'version'  => 1,
		'title'    => '<b>Unsafe title</b>',
		'sections' => array(
			array(
				'id'       => 'section-one',
				'name'     => 'Unsafe section',
				'settings' => array(
					'layout'  => 'stack',
					'desktop' => array('backgroundImage' => 'url(javascript:alert(1))'),
				),
				'groups'   => array(
					array('id' => 'group-one', 'name' => 'Group', 'elementIds' => array('duplicate', 'missing')),
				),
				'elements' => array(
					array(
						'id'      => 'duplicate',
						'type'    => 'text',
						'name'    => 'Text',
						'content' => '<script>alert(1)</script><strong>Safe</strong>',
						'groupId' => 'group-one',
						'styles'  => array('desktop' => array('color' => 'expression(alert(1))')),
					),
					array(
						'id'   => 'duplicate',
						'type' => 'future-widget',
						'name' => 'Future widget',
						'raw'  => array('future' => 'preserved'),
					),
				),
			),
		),
	);
	$sanitized = LDRN_VB_Document::sanitize_document($malformed);
	$ids       = array(
		$sanitized['sections'][0]['id'],
		$sanitized['sections'][0]['elements'][0]['id'],
		$sanitized['sections'][0]['elements'][1]['id'],
	);
	$record('migration_normalizes_schema', 2 === $sanitized['version']);
	$record('ids_are_page_unique', count($ids) === count(array_unique($ids)), implode(',', $ids));
	$record(
		'malicious_markup_removed',
		false === strpos($sanitized['sections'][0]['elements'][0]['content'], '<script')
			&& '' === $sanitized['sections'][0]['elements'][0]['styles']['desktop']['color']
			&& '' === $sanitized['sections'][0]['settings']['desktop']['backgroundImage']
	);
	$record(
		'unknown_element_preserved',
		'unknown' === $sanitized['sections'][0]['elements'][1]['type']
			&& 'future-widget' === $sanitized['sections'][0]['elements'][1]['originalType']
	);
	$record(
		'group_references_reconciled',
		1 === count($sanitized['sections'][0]['groups'][0]['elementIds'])
			&& $sanitized['sections'][0]['elements'][0]['id'] === $sanitized['sections'][0]['groups'][0]['elementIds'][0]
	);

	$large_sections = array();
	for ($section_index = 0; $section_index < 35; ++$section_index) {
		$elements = array();
		for ($element_index = 0; $element_index < 65; ++$element_index) {
			$elements[] = array(
				'id'      => 'large-' . $section_index . '-' . $element_index,
				'type'    => 'text',
				'name'    => 'Text',
				'tag'     => 'p',
				'content' => 'Safe text',
				'styles'  => array(),
			);
		}
		$large_sections[] = array(
			'id'       => 'large-section-' . $section_index,
			'name'     => 'Large section',
			'settings' => array('layout' => 'stack'),
			'elements' => $elements,
		);
	}
	$large = LDRN_VB_Document::sanitize_document(
		array('version' => 2, 'title' => 'Large document', 'sections' => $large_sections)
	);
	$record(
		'large_tree_is_bounded',
		30 === count($large['sections']) && 60 === count($large['sections'][0]['elements'])
	);

	$slug      = 'phase1-test-' . strtolower(wp_generate_password(8, false, false));
	$test_post = LDRN_VB_Document::create(
		'Phase 1 integration test',
		$slug,
		array(
			'version'  => 2,
			'title'    => 'Phase 1 integration test',
			'sections' => array(
				array(
					'id'       => 'test-section',
					'name'     => 'Test section',
					'settings' => array('layout' => 'stack', 'desktop' => array('padding' => '20px')),
					'elements' => array(
						array(
							'id'      => 'test-text',
							'type'    => 'text',
							'name'    => 'Test text',
							'tag'     => 'p',
							'content' => 'Version one',
							'styles'  => array('desktop' => array()),
						),
						array(
							'id'      => 'test-image',
							'type'    => 'image',
							'name'    => 'Test image',
							'src'     => 'https://example.com/image.jpg',
							'alt'     => '',
							'styles'  => array('desktop' => array()),
						),
					),
				),
			),
		)
	);
	if (is_wp_error($test_post)) {
		throw new RuntimeException($test_post->get_error_message());
	}
	$record('page_creation', $test_post instanceof WP_Post, $slug);

	$session_a = 'phase1-session-a-' . wp_generate_password(10, false, false);
	$session_b = 'phase1-session-b-' . wp_generate_password(10, false, false);
	$lock_a    = LDRN_VB_Locks::acquire($test_post->ID, $session_a);
	$lock_b    = LDRN_VB_Locks::acquire($test_post->ID, $session_b);
	$record('lock_owner_acquires', !is_wp_error($lock_a) && !empty($lock_a['owned']));
	$record('second_session_conflicts', is_wp_error($lock_b) && 409 === (int) ($lock_b->get_error_data()['status'] ?? 0));
	$record('owner_assertion', true === LDRN_VB_Locks::assert_owned($test_post->ID, $session_a));

	$initial      = LDRN_VB_Document::get($slug);
	$initial_hash = LDRN_VB_Document::hash($initial);
	$initial['sections'][0]['elements'][0]['content'] = 'Version two';
	$save_one = LDRN_VB_Document::save($slug, $initial, 'manual', $initial_hash);
	$record('optimistic_save', $save_one instanceof WP_Post);
	$stale_save = LDRN_VB_Document::save($slug, $initial, 'manual', $initial_hash);
	$record(
		'stale_hash_conflicts',
		is_wp_error($stale_save) && 409 === (int) ($stale_save->get_error_data()['status'] ?? 0)
	);

	$not_ready = LDRN_VB_Document::transition(get_post($test_post->ID), 'publish');
	$record('readiness_blocks_publish', is_wp_error($not_ready) && 'ldrn_vb_not_ready' === $not_ready->get_error_code());

	$ready = LDRN_VB_Document::get($slug);
	$ready['sections'][0]['elements'][1]['alt'] = 'A test nursery classroom';
	$ready_hash = LDRN_VB_Document::hash($ready);
	$save_ready = LDRN_VB_Document::save(
		$slug,
		$ready,
		'manual',
		LDRN_VB_Document::hash(LDRN_VB_Document::get($slug))
	);
	$record('ready_document_saves', $save_ready instanceof WP_Post, $ready_hash);
	$published = LDRN_VB_Document::transition(get_post($test_post->ID), 'publish');
	$snapshot  = LDRN_VB_Document::published($slug);
	$record('publish_creates_snapshot', !is_wp_error($published) && is_array($snapshot) && !empty($snapshot['hash']));

	$draft = LDRN_VB_Document::get($slug);
	$draft['sections'][0]['elements'][0]['content'] = 'Unpublished draft change';
	$draft_saved = LDRN_VB_Document::save($slug, $draft, 'autosave', LDRN_VB_Document::hash(LDRN_VB_Document::get($slug)));
	$snapshot_after_draft = LDRN_VB_Document::published($slug);
	$record(
		'draft_does_not_mutate_snapshot',
		$draft_saved instanceof WP_Post
			&& $snapshot['hash'] === $snapshot_after_draft['hash']
			&& 'Version two' === $snapshot_after_draft['document']['sections'][0]['elements'][0]['content']
	);
	LDRN_VB_Document::transition(get_post($test_post->ID), 'revert_draft');
	$record('public_snapshot_survives_draft_state', is_array(LDRN_VB_Document::published($slug)));
	$record('revision_history_created', count(LDRN_VB_Document::revisions($test_post->ID)) >= 2);

	$template = LDRN_VB_Template::create('Phase 1 test template', LDRN_VB_Document::get($slug)['sections'][0]);
	if (!is_wp_error($template)) {
		$template_id = (int) $template['id'];
	}
	$record('reusable_template', !is_wp_error($template) && !empty($template['section']['id']));
	$record('audit_trail', count(LDRN_VB_Audit::recent($test_post->ID, 50)) >= 5);

	do_action('rest_api_init');
	wp_set_current_user(0);
	$anonymous = rest_do_request(new WP_REST_Request('GET', '/ldrn-visual-builder/v1/pages'));
	$record('admin_api_is_protected', $anonymous->is_error() && in_array($anonymous->get_status(), array(401, 403), true));
	wp_set_current_user(1);
	$public = rest_do_request(new WP_REST_Request('GET', '/ldrn-visual-builder/v1/public/page/' . $slug));
	$record(
		'public_api_returns_snapshot_only',
		200 === $public->get_status()
			&& $snapshot['hash'] === ($public->get_data()['hash'] ?? '')
	);
} catch (Throwable $error) {
	$record('uncaught_exception', false, $error->getMessage());
} finally {
	wp_set_current_user(1);
	if ($template_id) {
		wp_delete_post($template_id, true);
	}
	if ($test_post instanceof WP_Post) {
		LDRN_VB_Locks::release($test_post->ID, isset($session_a) ? $session_a : '');
		foreach (
			get_posts(
				array(
					'post_type'      => LDRN_VB_Audit::POST_TYPE,
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'meta_key'       => '_ldrn_vb_page_id',
					'meta_value'     => $test_post->ID,
					'fields'         => 'ids',
				)
			) as $audit_id
		) {
			wp_delete_post($audit_id, true);
		}
		wp_delete_post($test_post->ID, true);
	}
}

$failed = array_filter(
	$results,
	static function ($result) {
		return empty($result['passed']);
	}
);

WP_CLI::line(wp_json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
if ($failed) {
	WP_CLI::error('Phase 1 integration checks failed: ' . implode(', ', array_keys($failed)));
}
WP_CLI::success('All Phase 1 integration checks passed.');
