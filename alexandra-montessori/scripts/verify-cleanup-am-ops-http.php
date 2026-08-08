<?php
/**
 * Verify and clean an explicitly tokenized real-HTTP smoke fixture.
 *
 * Usage:
 * php scripts/verify-cleanup-am-ops-http.php "C:\path\to\wordpress" http-abc123
 */

if (PHP_SAPI !== 'cli') {
	exit(1);
}

$wordpress_root = isset($argv[1]) ? rtrim((string) $argv[1], '/\\') : '';
$token          = trim((string) ($argv[2] ?? ''));
if ('' === $wordpress_root || !is_file($wordpress_root . '/wp-load.php')) {
	fwrite(STDERR, "Pass a valid WordPress root containing wp-load.php.\n");
	exit(2);
}
if (!preg_match('/^http-[a-f0-9]{12}$/', $token)) {
	fwrite(STDERR, "Refused: token must match the HTTP smoke prefix exactly.\n");
	exit(2);
}

require_once $wordpress_root . '/wp-load.php';

global $wpdb;

$passed = 0;
$failed = 0;
$email  = $token . '@example.com';

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

$submissions_table = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
$files_table       = AM_Ops_Tables::name(AM_Ops_Tables::FILES);
$events_table      = AM_Ops_Tables::name(AM_Ops_Tables::EVENTS);
$messages_table    = AM_Ops_Tables::name(AM_Ops_Tables::MESSAGES);
$jobs_table        = AM_Ops_Tables::name(AM_Ops_Tables::JOBS);

$rows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$submissions_table}
		WHERE customer_email = %s AND legacy_post_id IS NULL
		ORDER BY id ASC",
		$email
	),
	ARRAY_A
);

$safe_fixture = 3 === count($rows);
foreach ($rows as $row) {
	$payload = json_decode((string) $row['payload'], true);
	$safe_fixture = $safe_fixture
		&& 'website' !== $row['source']
		&& false !== strpos(wp_json_encode($payload), $token);
}
$check($safe_fixture, 'Exactly three prefix-locked real-HTTP records are present');

$types = array_column($rows, 'type');
sort($types);
$expected_types = array('application', 'availability', 'contact');
sort($expected_types);
$check($expected_types === $types, 'HTTP boundary stored Contact, Availability and Application types');

$check(
	1 === count(array_keys($types, 'contact', true)),
	'Idempotent HTTP retry created no second Contact row'
);

$states = array_unique(array_column($rows, 'notification_state'));
$check(
	array('suppressed') === array_values($states),
	'Dashboard-only policy remains explicit for every accepted HTTP record'
);

$ids = array_map('intval', array_column($rows, 'id'));
$application = null;
foreach ($rows as $row) {
	if ('application' === $row['type']) {
		$application = $row;
		break;
	}
}

$file = $application
	? $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$files_table} WHERE submission_id = %d LIMIT 1",
			(int) $application['id']
		),
		ARRAY_A
	)
	: null;
$file_path = $file ? (new AM_Ops_Files())->path((string) $file['storage_key']) : '';
$check((bool) $file, 'Multipart Application created one private-file metadata row');
$check($file_path && is_readable($file_path), 'Multipart CV bytes are readable from private storage');
$check(
	$file_path && 0 !== strpos(realpath($file_path), realpath(ABSPATH)),
	'Multipart CV remains outside the public WordPress web root'
);
$check(
	$file_path && hash_file('sha256', $file_path) === ($file['sha256'] ?? ''),
	'Stored CV checksum matches the uploaded bytes'
);

$creation_events = $ids
	? (int) $wpdb->get_var(
		"SELECT COUNT(id) FROM {$events_table}
		WHERE submission_id IN (" . implode(',', $ids) . ")
			AND event_type = 'submission_created'"
	)
	: 0;
$check(3 === $creation_events, 'Every accepted HTTP record has one creation audit event');

if (!$safe_fixture) {
	echo "[CLEANUP] Refused because the prefix-lock ownership proof failed.\n";
	echo "Passed: {$passed}; Failed: " . ($failed + 1) . "\n";
	exit(1);
}

if ($file_path && is_file($file_path)) {
	@unlink($file_path);
}

$id_list = implode(',', $ids);
$wpdb->query('START TRANSACTION');
$wpdb->query("DELETE FROM {$jobs_table} WHERE submission_id IN ({$id_list})");
$wpdb->query("DELETE FROM {$messages_table} WHERE submission_id IN ({$id_list})");
$wpdb->query("DELETE FROM {$files_table} WHERE submission_id IN ({$id_list})");
$wpdb->query("DELETE FROM {$events_table} WHERE submission_id IN ({$id_list})");
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$submissions_table}
		WHERE id IN ({$id_list}) AND customer_email = %s AND legacy_post_id IS NULL",
		$email
	)
);
$wpdb->query('COMMIT');
delete_transient('am_ops_overview_metrics');

$remaining = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(id) FROM {$submissions_table} WHERE customer_email = %s AND legacy_post_id IS NULL",
		$email
	)
);
$check(0 === $remaining, 'Prefix-locked real-HTTP fixture was completely removed');
echo '[CLEANUP] Removed real-HTTP fixture IDs: ' . implode(', ', $ids) . "\n";
echo "Passed: {$passed}; Failed: {$failed}\n";

exit($failed > 0 ? 1 : 0);
