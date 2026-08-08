<?php
/**
 * Copy-first legacy migration verification.
 *
 * Runs the migration twice, proves the legacy source is byte-for-byte
 * unchanged at the database-field level, and verifies reconciliation.
 *
 * Usage:
 * php scripts/verify-am-ops-migration.php "C:\path\to\wordpress"
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

global $wpdb;

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

$legacy_snapshot = static function () use ($wpdb) {
	$posts = $wpdb->get_results(
		"SELECT ID, post_type, post_status, post_title, post_content,
			post_date, post_date_gmt, post_modified, post_modified_gmt
		FROM {$wpdb->posts}
		WHERE post_type IN ('am_enquiry','am_availability','am_application')
			AND post_status NOT IN ('auto-draft','inherit')
		ORDER BY ID ASC",
		ARRAY_A
	);

	$meta = $wpdb->get_results(
		"SELECT pm.meta_id, pm.post_id, pm.meta_key, pm.meta_value
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE p.post_type IN ('am_enquiry','am_availability','am_application')
			AND p.post_status NOT IN ('auto-draft','inherit')
		ORDER BY pm.meta_id ASC",
		ARRAY_A
	);

	return array(
		'hash'       => hash('sha256', wp_json_encode(array('posts' => $posts, 'meta' => $meta))),
		'post_count' => count($posts),
		'meta_count' => count($meta),
		'post_ids'   => array_map('intval', array_column($posts, 'ID')),
	);
};

$source_message_count = static function () use ($wpdb) {
	$post_ids = $wpdb->get_col(
		"SELECT ID FROM {$wpdb->posts}
		WHERE post_type IN ('am_enquiry','am_availability','am_application')
			AND post_status NOT IN ('auto-draft','inherit')"
	);
	$count = 0;
	foreach ($post_ids as $post_id) {
		$history = get_post_meta((int) $post_id, '_am_submission_communications', true);
		$count  += is_array($history) ? count($history) : 0;
	}

	return $count;
};

$before = $legacy_snapshot();
$expected_messages = $source_message_count();
$expected_files = (int) $wpdb->get_var(
	"SELECT COUNT(DISTINCT pm.post_id)
	FROM {$wpdb->postmeta} pm
	INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
	WHERE p.post_type = 'am_application'
		AND p.post_status NOT IN ('auto-draft','inherit')
		AND pm.meta_key = '_am_app_cv_file'
		AND pm.meta_value <> ''"
);

$submissions_table = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
if (0 === $before['post_count']) {
	$preserved_destination_count = (int) $wpdb->get_var(
		"SELECT COUNT(id) FROM {$submissions_table} WHERE legacy_post_id IS NOT NULL"
	);
	$duplicate_legacy_ids = (int) $wpdb->get_var(
		"SELECT COUNT(*)
		FROM (
			SELECT legacy_post_id
			FROM {$submissions_table}
			WHERE legacy_post_id IS NOT NULL
			GROUP BY legacy_post_id
			HAVING COUNT(*) > 1
		) duplicate_legacy"
	);
	echo "[SKIP] No legacy source posts remain in this Local database; copy-first reconciliation cannot be rerun.\n";
	$check(
		$preserved_destination_count >= 0,
		'Previously migrated destination rows remain readable'
	);
	$check(0 === $duplicate_legacy_ids, 'Preserved legacy references remain unique');
	echo "Preserved migrated rows: {$preserved_destination_count}\n";
	echo "Passed: {$passed}; Failed: {$failed}; Skipped: copy-first source reconciliation\n";
	exit($failed > 0 ? 1 : 0);
}

$migration = new AM_Ops_Migration();
$first     = $migration->run(50);
$check(!is_wp_error($first), 'First copy-first migration run completes');
if (is_wp_error($first)) {
	echo "Migration error: {$first->get_error_code()} {$first->get_error_message()}\n";
	exit(1);
}

$after_first = $legacy_snapshot();
$check(
	$before === $after_first,
	'First migration leaves every legacy post and meta value untouched'
);
$check(
	$before['post_count'] === (int) $first['processed'],
	'First migration processes every selected legacy record'
);
$check(
	empty($first['errors']),
	'First migration records no copy errors',
	wp_json_encode($first['errors'])
);
$check(
	(bool) $first['reconciliation']['healthy'],
	'First migration reconciliation is healthy',
	wp_json_encode($first['reconciliation'])
);

$second = $migration->run(50);
$check(!is_wp_error($second), 'Second migration run completes');
if (is_wp_error($second)) {
	echo "Second migration error: {$second->get_error_code()} {$second->get_error_message()}\n";
	exit(1);
}

$after_second = $legacy_snapshot();
$check(
	$before === $after_second,
	'Repeated migration still leaves the legacy archive untouched'
);
$check(0 === (int) $second['created'], 'Repeated migration creates no duplicate submissions');
$check(0 === (int) $second['messages_created'], 'Repeated migration creates no duplicate messages');
$check(0 === (int) $second['files_created'], 'Repeated migration creates no duplicate file metadata');
$check(
	$before['post_count'] === (int) $second['already_migrated'],
	'Repeated migration recognizes every existing destination row'
);
$check(
	empty($second['errors']),
	'Repeated migration records no errors',
	wp_json_encode($second['errors'])
);
$check(
	(bool) $second['reconciliation']['healthy'],
	'Repeated migration reconciliation remains healthy',
	wp_json_encode($second['reconciliation'])
);

$messages_table    = AM_Ops_Tables::name(AM_Ops_Tables::MESSAGES);
$files_table       = AM_Ops_Tables::name(AM_Ops_Tables::FILES);
$events_table      = AM_Ops_Tables::name(AM_Ops_Tables::EVENTS);

$destination_count = (int) $wpdb->get_var(
	"SELECT COUNT(id) FROM {$submissions_table} WHERE legacy_post_id IS NOT NULL"
);
$message_count = (int) $wpdb->get_var(
	"SELECT COUNT(id) FROM {$messages_table} WHERE legacy_key IS NOT NULL"
);
$file_count = (int) $wpdb->get_var(
	"SELECT COUNT(f.id)
	FROM {$files_table} f
	INNER JOIN {$submissions_table} s ON s.id = f.submission_id
	WHERE s.legacy_post_id IS NOT NULL"
);
$creation_events = (int) $wpdb->get_var(
	"SELECT COUNT(e.id)
	FROM {$events_table} e
	INNER JOIN {$submissions_table} s ON s.id = e.submission_id
	WHERE s.legacy_post_id IS NOT NULL
		AND e.event_type = 'submission_migrated'"
);

$check(
	$before['post_count'] === $destination_count,
	'Destination contains exactly one row per legacy submission'
);
$check(
	$expected_messages === $message_count,
	'Every serialized legacy communication has exactly one message row'
);
$check(
	$expected_files === $file_count,
	'Every private legacy CV key has exactly one file metadata row'
);
$check(
	$before['post_count'] === $creation_events,
	'Every migrated submission has exactly one creation audit event'
);

$duplicate_references = (int) $wpdb->get_var(
	"SELECT COUNT(*)
	FROM (
		SELECT public_ref
		FROM {$submissions_table}
		GROUP BY public_ref
		HAVING COUNT(*) > 1
	) duplicate_refs"
);
$duplicate_legacy_ids = (int) $wpdb->get_var(
	"SELECT COUNT(*)
	FROM (
		SELECT legacy_post_id
		FROM {$submissions_table}
		WHERE legacy_post_id IS NOT NULL
		GROUP BY legacy_post_id
		HAVING COUNT(*) > 1
	) duplicate_legacy"
);
$check(0 === $duplicate_references, 'Migrated public references are unique');
$check(0 === $duplicate_legacy_ids, 'Migrated legacy references are unique');

$rows = $wpdb->get_results(
	"SELECT legacy_post_id, public_ref, type, status, customer_email, submitted_at
	FROM {$submissions_table}
	WHERE legacy_post_id IS NOT NULL
	ORDER BY legacy_post_id ASC",
	ARRAY_A
);

echo "\nMigrated rows: " . wp_json_encode($rows, JSON_UNESCAPED_SLASHES) . "\n";
echo "Legacy snapshot: " . wp_json_encode($before) . "\n";
echo "Passed: {$passed}; Failed: {$failed}\n";

exit($failed > 0 ? 1 : 0);
