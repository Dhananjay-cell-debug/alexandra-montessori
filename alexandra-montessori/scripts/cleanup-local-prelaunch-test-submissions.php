<?php
/**
 * Remove the four explicitly identified Local pre-launch form fixtures.
 *
 * This script is intentionally unusable on a non-.local site. It validates the
 * exact public references, identity, form-type set, legacy boundary and payload
 * marker before removing related jobs/messages/files/events and private bytes.
 *
 * Usage:
 * php cleanup-local-prelaunch-test-submissions.php "C:\path\to\wordpress"
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

$host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
if (!str_ends_with($host, '.local')) {
	fwrite(STDERR, "Refused: this fixture cleanup is Local-only.\n");
	exit(2);
}

global $wpdb;

$references = array(
	'AM-2026-7TGT6RUEXJ',
	'AM-2026-TPYJRE3W7J',
	'AM-2026-YCPAUBS6SY',
	'AM-2026-N67Z2YDMFF',
);
$allowed_sources = array(
	'contact_form',
	'visit_form',
	'availability_form',
	'careers_form',
);
$expected_types = array('application', 'availability', 'contact', 'visit');

$submissions = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
$events      = AM_Ops_Tables::name(AM_Ops_Tables::EVENTS);
$messages    = AM_Ops_Tables::name(AM_Ops_Tables::MESSAGES);
$files       = AM_Ops_Tables::name(AM_Ops_Tables::FILES);
$jobs        = AM_Ops_Tables::name(AM_Ops_Tables::JOBS);
$placeholders = implode(',', array_fill(0, count($references), '%s'));
$rows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$submissions} WHERE public_ref IN ({$placeholders}) ORDER BY id ASC",
		$references
	),
	ARRAY_A
);

$safe = count($references) === count($rows);
$types = array();
foreach ($rows as $row) {
	$types[] = (string) $row['type'];
	$payload = strtolower((string) $row['payload']);
	$safe = $safe
		&& in_array((string) $row['public_ref'], $references, true)
		&& in_array((string) $row['source'], $allowed_sources, true)
		&& 'Pre-launch System Test' === (string) $row['customer_name']
		&& 'dhananjaychitmila@gmail.com' === strtolower((string) $row['customer_email'])
		&& null === $row['legacy_post_id']
		&& false !== strpos($payload, 'pre-launch')
		&& false !== strpos($payload, 'system test');
}
sort($types);
$safe = $safe && $expected_types === $types;

if (!$safe) {
	fwrite(STDERR, "Refused: the exact four-record fixture ownership proof failed.\n");
	$diagnostics = array_map(
		static function ($row) {
			return array(
				'id'             => (int) $row['id'],
				'public_ref'     => (string) $row['public_ref'],
				'type'           => (string) $row['type'],
				'source'         => (string) $row['source'],
				'customer_name'  => (string) $row['customer_name'],
				'customer_email' => (string) $row['customer_email'],
				'legacy_post_id' => $row['legacy_post_id'],
				'payload'        => json_decode((string) $row['payload'], true),
			);
		},
		$rows
	);
	fwrite(STDERR, wp_json_encode($diagnostics, JSON_PRETTY_PRINT) . "\n");
	exit(1);
}

$ids     = array_map('intval', array_column($rows, 'id'));
$id_list = implode(',', $ids);
$file_rows = $wpdb->get_results(
	"SELECT * FROM {$files} WHERE submission_id IN ({$id_list})",
	ARRAY_A
);
$file_service = new AM_Ops_Files();
foreach ($file_rows as $file_row) {
	$path = $file_service->path((string) $file_row['storage_key']);
	if ($path && is_file($path) && !unlink($path)) {
		fwrite(STDERR, "Refused: could not remove a private test file.\n");
		exit(1);
	}
}

$wpdb->query('START TRANSACTION');
$wpdb->query("DELETE FROM {$jobs} WHERE submission_id IN ({$id_list})");
$wpdb->query("DELETE FROM {$messages} WHERE submission_id IN ({$id_list})");
$wpdb->query("DELETE FROM {$files} WHERE submission_id IN ({$id_list})");
$wpdb->query("DELETE FROM {$events} WHERE submission_id IN ({$id_list})");
$deleted = $wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$submissions}
		 WHERE id IN ({$id_list})
			AND legacy_post_id IS NULL
			AND customer_email = %s
			AND customer_name = %s",
		'dhananjaychitmila@gmail.com',
		'Pre-launch System Test'
	)
);

if (count($ids) !== $deleted || '' !== $wpdb->last_error) {
	$wpdb->query('ROLLBACK');
	fwrite(STDERR, "Cleanup transaction failed: {$wpdb->last_error}\n");
	exit(1);
}
$wpdb->query('COMMIT');
delete_transient('am_ops_overview_metrics');

$remaining = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM {$submissions} WHERE public_ref IN ({$placeholders})",
		$references
	)
);
if (0 !== $remaining) {
	fwrite(STDERR, "Cleanup verification failed: {$remaining} records remain.\n");
	exit(1);
}

echo '[PASS] Removed four exact Local pre-launch submissions and '
	. count($file_rows) . " private test file(s).\n";
echo "[PASS] Legacy archive records were outside the deletion boundary.\n";
exit(0);
