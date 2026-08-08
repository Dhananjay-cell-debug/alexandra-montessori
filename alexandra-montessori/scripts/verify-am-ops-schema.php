<?php
/**
 * Verify the Alexandra Operations schema against a Local WordPress install.
 *
 * Usage:
 * php scripts/verify-am-ops-schema.php "C:\path\to\wordpress"
 */

if (PHP_SAPI !== 'cli') {
	exit(1);
}

$wordpress_root = isset($argv[1]) ? rtrim((string) $argv[1], '/\\') : '';
if ('' === $wordpress_root || !is_file($wordpress_root . '/wp-load.php')) {
	fwrite(STDERR, "Pass a valid WordPress root containing wp-load.php.\n");
	exit(2);
}

require_once $wordpress_root . '/wp-load.php';

$passed = 0;
$failed = 0;

$check = static function ($condition, $label, $details = '') use (&$passed, &$failed) {
	if ($condition) {
		$passed++;
		echo "[PASS] {$label}\n";
		return;
	}

	$failed++;
	echo "[FAIL] {$label}";
	if ('' !== $details) {
		echo ": {$details}";
	}
	echo "\n";
};

$check(defined('AM_OPS_ACTIVE') && AM_OPS_ACTIVE, 'Operations must-use plugin is active');
$check(class_exists('AM_Ops_Schema'), 'Schema installer is loaded');
$check(class_exists('AM_Ops_Repository'), 'Submission repository is loaded');

if (!class_exists('AM_Ops_Schema') || !class_exists('AM_Ops_Tables')) {
	exit(3);
}

$first_install  = AM_Ops_Schema::install();
$second_install = AM_Ops_Schema::install();
$health         = AM_Ops_Schema::health();

$check($first_install, 'Schema installer succeeds');
$check($second_install, 'Schema installer is repeatable');
$check($health['healthy'], 'Every required operations table exists', implode(', ', $health['missing']));
$check(
	AM_OPS_SCHEMA_VERSION === (string) get_option(AM_Ops_Schema::VERSION_OPTION, ''),
	'Recorded schema version matches plugin'
);

$required_indexes = array(
	AM_Ops_Tables::SUBMISSIONS => array(
		'PRIMARY',
		'public_ref',
		'idempotency_key',
		'legacy_post_id',
		'latest',
		'type_status_submitted',
		'status_submitted',
		'owner_status_followup',
		'owner_status_submitted',
		'followup_status',
		'branch_status_submitted',
		'priority_status_submitted',
		'spam_submitted',
		'notification_submitted',
		'email_submitted',
		'phone_submitted',
		'duplicate_submitted',
		'archive_id',
		'name_submitted',
	),
	AM_Ops_Tables::EVENTS => array(
		'PRIMARY',
		'submission_created',
		'event_created',
		'actor_created',
	),
	AM_Ops_Tables::MESSAGES => array(
		'PRIMARY',
		'legacy_key',
		'submission_created',
		'status_created',
		'recipient_created',
	),
	AM_Ops_Tables::FILES => array(
		'PRIMARY',
		'storage_key',
		'submission_created',
		'sha256',
	),
	AM_Ops_Tables::JOBS => array(
		'PRIMARY',
		'unique_key',
		'claimable',
		'locked',
		'submission_created',
		'message_created',
		'recipient_state',
	),
	AM_Ops_Tables::RATE_LIMITS => array(
		'PRIMARY',
		'rate_window',
		'expires',
		'bucket_expires',
	),
);

global $wpdb;

foreach ($required_indexes as $logical_name => $expected) {
	$table = AM_Ops_Tables::name($logical_name);
	$rows  = $wpdb->get_results("SHOW INDEX FROM `{$table}`", ARRAY_A);
	$found = array_values(
		array_unique(
			array_map(
				static function ($row) {
					return (string) $row['Key_name'];
				},
				$rows
			)
		)
	);
	$missing_indexes = array_values(array_diff($expected, $found));

	$check(
		empty($missing_indexes),
		"Required indexes exist on {$logical_name}",
		implode(', ', $missing_indexes)
	);

	$engine = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s',
			$table
		)
	);
	$check('InnoDB' === $engine, "{$logical_name} uses InnoDB", (string) $engine);
}

foreach (array('administrator', 'am_content_manager') as $role_name) {
	$role = get_role($role_name);
	$check((bool) $role, "{$role_name} role exists");
	if (!$role) {
		continue;
	}

	foreach (AM_Ops_Capabilities::all() as $capability) {
		$check(
			$role->has_cap($capability),
			"{$role_name} has {$capability}"
		);
	}

	$check(
		!$role->has_cap('delete_am_submissions'),
		"{$role_name} has no permanent Alexandra submission-delete capability"
	);
}

$legacy_counts = array();
foreach (array('am_enquiry', 'am_availability', 'am_application') as $post_type) {
	$legacy_counts[$post_type] = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = %s",
			$post_type
		)
	);
}

echo "\nLegacy records remain untouched: " . wp_json_encode($legacy_counts) . "\n";
echo "Passed: {$passed}; Failed: {$failed}\n";

exit($failed > 0 ? 1 : 0);
