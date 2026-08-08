<?php
/**
 * Phase 2A schema, rich-text, typography and readiness checks.
 *
 * Run with:
 * wp eval-file wp-content/mu-plugins/alexandra-visual-builder/tests/phase2a-integration.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

wp_set_current_user(1);

$results = array();
$record  = static function ($name, $passed, $detail = '') use (&$results) {
	$results[$name] = array(
		'passed' => (bool) $passed,
		'detail' => (string) $detail,
	);
};

try {
	$record('schema_v3', 3 === AM_VB_Document::SCHEMA_VERSION);

	$version_two = array(
		'version'  => 2,
		'title'    => 'Typography migration',
		'sections' => array(
			array(
				'id'       => 'migration-section',
				'name'     => 'Migration section',
				'settings' => array('layout' => 'stack'),
				'elements' => array(
					array(
						'id'        => 'migration-heading',
						'type'      => 'text',
						'name'      => 'Heading',
						'tag'       => 'h1',
						'content'   => '<strong>Welcome</strong>',
						'customCss' => 'position:fixed',
						'styles'    => array(
							'desktop' => array(
								'fontSize'     => '64px',
								'fontWeight'   => '600',
								'lineHeight'   => '1.04',
								'color'        => '#20372a',
								'width'        => '54%',
								'positionX'    => '8%',
							),
							'tablet'  => array('fontSize' => '48px'),
							'mobile'  => array('fontSize' => '38px'),
						),
					),
				),
			),
		),
	);

	$migrated = AM_VB_Document::sanitize_document($version_two);
	$heading  = $migrated['sections'][0]['elements'][0];
	$record(
		'v2_content_migrates_to_namespace',
		'h1' === ($heading['content']['semantic'] ?? '')
			&& '<strong>Welcome</strong>' === ($heading['content']['html'] ?? '')
	);
	$record(
		'v2_typography_preserves_appearance',
		'64px' === ($heading['responsive']['desktop']['typography']['fontSize'] ?? '')
			&& '48px' === ($heading['responsive']['tablet']['typography']['fontSize'] ?? '')
			&& '38px' === ($heading['responsive']['mobile']['typography']['fontSize'] ?? '')
			&& '54%' === ($heading['styles']['desktop']['width'] ?? '')
			&& '8%' === ($heading['styles']['desktop']['positionX'] ?? '')
			&& !isset($heading['styles']['desktop']['fontSize'])
	);
	$record(
		'unknown_attributes_are_quarantined',
		'position:fixed' === ($heading['migration']['quarantined']['customCss'] ?? '')
	);

	$again = AM_VB_Document::sanitize_document($migrated);
	$record(
		'schema_migration_is_idempotent',
		AM_VB_Document::hash($migrated) === AM_VB_Document::hash($again)
	);

	$unsafe = $version_two;
	$unsafe['sections'][0]['elements'][0]['tag'] = 'div';
	$unsafe['sections'][0]['elements'][0]['content'] = '<!--[if gte mso 9]><xml>bad</xml><![endif]-->'
		. '<p class="MsoNormal" id="word-id" onclick="alert(1)" style="margin:0;color:#5E8773;font-size:99px">'
		. '<span style="font-family:Comic Sans MS;color:rgb(94,135,115);background-color:#F5F1EF" data-docs-id="x">'
		. '<b>Clean</b></span><script>alert(1)</script></p>'
		. '<a href="javascript:alert(1)" target="_blank">Unsafe link</a>';
	$cleaned = AM_VB_Document::sanitize_document($unsafe);
	$html    = $cleaned['sections'][0]['elements'][0]['content']['html'];
	$record(
		'word_and_docs_markup_is_clean',
		false === stripos($html, 'mso')
			&& false === stripos($html, 'class=')
			&& false === stripos($html, 'id=')
			&& false === stripos($html, 'onclick')
			&& false === stripos($html, 'font-family')
			&& false === stripos($html, 'font-size')
			&& false === stripos($html, '<script')
			&& false === stripos($html, 'javascript:')
			&& false !== strpos($html, '<strong>Clean</strong>')
			&& false !== strpos($html, 'color:#5e8773')
			&& false !== strpos($html, 'background-color:#f5f1ef')
	);

	$valid_typography = $version_two;
	$valid_typography['sections'][0]['elements'][0]['responsive'] = array(
		'desktop' => array(
			'typography' => array(
				'fontFamily'       => 'heading',
				'fontSize'         => 'clamp(10px, 80vw, 900px)',
				'fontWeight'       => '950',
				'lineHeight'       => '99',
				'letterSpacing'    => 'expression(alert(1))',
				'textAlign'        => 'sideways',
				'textTransform'    => 'uppercase',
				'textDecoration'   => 'underline',
				'textShadow'       => 'strong',
				'fluidFontSize'    => array(
					'enabled'     => true,
					'minSize'     => 18,
					'maxSize'     => 72,
					'minViewport' => 390,
					'maxViewport' => 1440,
				),
			),
		),
	);
	$bounded = AM_VB_Document::sanitize_document($valid_typography);
	$type    = $bounded['sections'][0]['elements'][0]['responsive']['desktop']['typography'];
	$record(
		'typography_values_are_bounded',
		'heading' === ($type['fontFamily'] ?? '')
			&& !isset($type['fontSize'])
			&& '800' === ($type['fontWeight'] ?? '')
			&& '3' === ($type['lineHeight'] ?? '')
			&& !isset($type['letterSpacing'])
			&& !isset($type['textAlign'])
			&& 'uppercase' === ($type['textTransform'] ?? '')
			&& 'underline' === ($type['textDecoration'] ?? '')
			&& 'strong' === ($type['textShadow'] ?? '')
			&& 18.0 === (float) ($type['fluidFontSize']['minSize'] ?? 0)
			&& 72.0 === (float) ($type['fluidFontSize']['maxSize'] ?? 0)
	);

	$one_h1 = $migrated;
	$record('exactly_one_h1_has_no_heading_warning', array() === AM_VB_Document::readiness_warnings($one_h1));

	$missing_h1 = $migrated;
	$missing_h1['sections'][0]['elements'][0]['content']['semantic'] = 'h2';
	$missing_warnings = AM_VB_Document::readiness_warnings($missing_h1);
	$record(
		'missing_h1_is_actionable',
		(bool) array_filter(
			$missing_warnings,
			static function ($warning) {
				return false !== stripos($warning, 'Heading 1');
			}
		)
	);

	$multiple_h1 = $migrated;
	$multiple_h1['sections'][0]['elements'][] = array(
		'id'         => 'second-h1',
		'type'       => 'text',
		'name'       => 'Second heading',
		'visible'    => true,
		'locked'     => false,
		'groupId'    => '',
		'styles'     => array('desktop' => array(), 'tablet' => array(), 'mobile' => array()),
		'content'    => array('semantic' => 'h1', 'html' => 'Second'),
		'typography' => array('preset' => 'heading'),
		'responsive' => array(
			'desktop' => array('typography' => array()),
			'tablet'  => array('typography' => array()),
			'mobile'  => array('typography' => array()),
		),
	);
	$multiple_warnings = AM_VB_Document::readiness_warnings($multiple_h1);
	$record(
		'multiple_h1_is_actionable',
		(bool) array_filter(
			$multiple_warnings,
			static function ($warning) {
				return false !== stripos($warning, 'one visible Heading 1');
			}
		)
	);

	$skipped = $multiple_h1;
	$skipped['sections'][0]['elements'][1]['content']['semantic'] = 'h3';
	$skipped_warnings = AM_VB_Document::readiness_warnings($skipped);
	$record(
		'skipped_heading_level_is_actionable',
		(bool) array_filter(
			$skipped_warnings,
			static function ($warning) {
				return false !== stripos($warning, 'Heading 2');
			}
		)
	);

	$public = AM_VB_Document::public_document($migrated);
	$public_element = $public['sections'][0]['elements'][0];
	$record(
		'public_ast_omits_quarantine_and_legacy_text_fields',
		!isset($public_element['migration'])
			&& !isset($public_element['tag'])
			&& is_array($public_element['content'] ?? null)
			&& is_array($public_element['responsive'] ?? null)
	);
} catch (Throwable $error) {
	$record('uncaught_exception', false, $error->getMessage());
}

$failed = array_filter(
	$results,
	static function ($result) {
		return empty($result['passed']);
	}
);

WP_CLI::line(wp_json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
if ($failed) {
	WP_CLI::error('Phase 2A integration checks failed: ' . implode(', ', array_keys($failed)));
}
WP_CLI::success('All Phase 2A integration checks passed.');
