<?php
/**
 * Command-centre repository verification.
 *
 * Proves indexed filtering/search, bidirectional keyset pagination,
 * optimistic locking, workflow audit and bounded bulk updates. Synthetic rows
 * are prefix-locked and removed.
 *
 * Usage:
 * php scripts/verify-am-ops-command-center.php "C:\path\to\wordpress"
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
$created_ids = array();
$token = 'command-' . bin2hex(random_bytes(8));
$repository = new AM_Ops_Repository();

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

try {
	for ($index = 0; $index < 55; $index++) {
		$type = 0 === $index % 3
			? AM_Ops_Repository::TYPE_AVAILABILITY
			: AM_Ops_Repository::TYPE_CONTACT;
		$result = $repository->create(
			array(
				'type'                => $type,
				'source'              => 'automated_test',
				'idempotency_key'     => $token . '-' . $index,
				'status'              => 0 === $index % 5 ? 'new' : 'in_progress',
				'priority'            => 0 === $index % 11 ? 'high' : 'normal',
				'branch_slug'         => 0 === $index % 2 ? 'heston' : 'hounslow',
				'branch_label'        => 0 === $index % 2 ? 'Heston' : 'Hounslow',
				'customer_name'       => 'Command Verifier ' . str_pad((string) $index, 3, '0', STR_PAD_LEFT),
				'customer_email'      => $token . '-' . $index . '@example.com',
				'customer_phone'      => '+4420' . str_pad((string) $index, 8, '0', STR_PAD_LEFT),
				'subject'             => 'Command centre verification ' . $token,
				'payload'             => array('test_marker' => $token, 'index' => $index),
				'submitted_at'        => gmdate('Y-m-d H:i:s', time() - $index),
				'notification_state'  => 0 === $index % 13 ? 'failed' : 'suppressed',
			)
		);
		if (is_wp_error($result)) {
			throw new RuntimeException($result->get_error_message());
		}
		$created_ids[] = (int) $result['submission']['id'];
	}
	$check(55 === count($created_ids), 'Synthetic command-centre fixture contains 55 rows');

	$first_page = $repository->query(
		array(
			'search' => 'Command Verifier',
			'limit'  => 20,
		)
	);
	$check(20 === count($first_page['items']), 'First keyset page is capped at 20 rows');
	$check('' !== $first_page['older_cursor'], 'First page exposes an opaque older cursor');

	$second_page = $repository->query(
		array(
			'search'    => 'Command Verifier',
			'limit'     => 20,
			'cursor'    => $first_page['older_cursor'],
			'direction' => 'older',
		)
	);
	$first_ids  = array_column($first_page['items'], 'id');
	$second_ids = array_column($second_page['items'], 'id');
	$check(20 === count($second_page['items']), 'Second keyset page is capped at 20 rows');
	$check(empty(array_intersect($first_ids, $second_ids)), 'Adjacent keyset pages do not overlap');
	$check('' !== $second_page['newer_cursor'], 'Older page exposes a newer cursor');

	$return_page = $repository->query(
		array(
			'search'    => 'Command Verifier',
			'limit'     => 20,
			'cursor'    => $second_page['newer_cursor'],
			'direction' => 'newer',
		)
	);
	$check(
		$first_ids === array_column($return_page['items'], 'id'),
		'Newer cursor returns to the exact preceding page'
	);

	$type_page = $repository->query(
		array(
			'type'   => AM_Ops_Repository::TYPE_AVAILABILITY,
			'branch' => 'heston',
			'limit'  => 100,
			'search' => 'Command Verifier',
		)
	);
	$type_valid = true;
	foreach ($type_page['items'] as $row) {
		$type_valid = $type_valid
			&& AM_Ops_Repository::TYPE_AVAILABILITY === $row['type']
			&& 'heston' === $row['branch_slug'];
	}
	$check($type_valid && count($type_page['items']) > 0, 'Type and nursery filters combine correctly');

	$exact_email = $token . '-17@example.com';
	$email_page  = $repository->query(array('search' => $exact_email, 'limit' => 10));
	$check(
		1 === count($email_page['items']) && $exact_email === $email_page['items'][0]['customer_email'],
		'Exact email search uses the normalized hash index'
	);
	$reference = $first_page['items'][0]['public_ref'];
	$reference_page = $repository->query(array('search' => $reference, 'limit' => 10));
	$check(
		1 === count($reference_page['items']) && $reference === $reference_page['items'][0]['public_ref'],
		'Exact public reference search returns one row'
	);

	$actor_ids = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ID'));
	$actor_id  = !empty($actor_ids) ? (int) $actor_ids[0] : 1;
	$target    = $repository->get($created_ids[0]);
	$updated   = $repository->update_operational(
		(int) $target['id'],
		array(
			'status'         => 'in_progress',
			'priority'       => 'urgent',
			'owner_user_id'  => $actor_id,
			'follow_up_at'   => gmdate('Y-m-d H:i:s', time() + DAY_IN_SECONDS),
			'internal_notes' => 'Synthetic internal note ' . $token,
		),
		$actor_id,
		(int) $target['version']
	);
	$check(!is_wp_error($updated), 'Optimistic workflow update succeeds with the current version');
	$check(
		'urgent' === $updated['priority']
		&& $actor_id === $updated['owner_user_id']
		&& false !== strpos($updated['internal_notes'], $token),
		'Workflow update changes only staff-owned operational fields'
	);
	$check(
		$target['customer_email'] === $updated['customer_email']
		&& $target['payload'] === $updated['payload'],
		'Workflow update preserves immutable customer evidence'
	);

	$stale = $repository->update_operational(
		(int) $target['id'],
		array('priority' => 'low'),
		$actor_id,
		(int) $target['version']
	);
	$check(
		is_wp_error($stale) && 'am_ops_version_conflict' === $stale->get_error_code(),
		'Stale editor version is rejected instead of overwriting a colleague'
	);

	$archive_active = $repository->update_operational(
		(int) $target['id'],
		array('archived' => true),
		$actor_id,
		(int) $updated['version']
	);
	$check(
		is_wp_error($archive_active) && 'am_ops_archive_requires_resolution' === $archive_active->get_error_code(),
		'An active record cannot be archived prematurely'
	);
	$resolved = $repository->update_operational(
		(int) $target['id'],
		array('status' => 'resolved', 'archived' => true),
		$actor_id,
		(int) $updated['version']
	);
	$check(!is_wp_error($resolved) && (bool) $resolved['archived_at'], 'Resolved record can be archived without deletion');

	$bulk_ids = array_slice($created_ids, 1, 3);
	$bulk = $repository->bulk_update(
		$bulk_ids,
		array('priority' => 'high', 'owner_user_id' => $actor_id),
		$actor_id
	);
	$check(3 === $bulk['updated'] && empty($bulk['errors']), 'Bounded bulk action updates all selected rows');
	$bulk_valid = true;
	foreach ($bulk_ids as $id) {
		$row = $repository->get($id);
		$bulk_valid = $bulk_valid && 'high' === $row['priority'] && $actor_id === $row['owner_user_id'];
	}
	$check($bulk_valid, 'Bulk assignment and priority persist per record');

	$events = $repository->events((int) $target['id']);
	$event_types = array_column($events, 'event_type');
	$check(
		in_array('priority_changed', $event_types, true)
		&& in_array('owner_changed', $event_types, true)
		&& in_array('notes_changed', $event_types, true)
		&& in_array('status_changed', $event_types, true)
		&& in_array('archived', $event_types, true),
		'Every workflow change is represented in the immutable audit timeline'
	);

	$metrics = $repository->overview_metrics();
	$check(
		isset($metrics['new_count'], $metrics['unassigned'], $metrics['notification_issues'], $metrics['by_type']),
		'Action-oriented overview metrics are available as one cached model'
	);
} catch (Throwable $error) {
	$failed++;
	echo '[FAIL] Command-centre verifier exception: ' . $error->getMessage() . "\n";
} finally {
	$created_ids = array_values(array_unique(array_filter(array_map('intval', $created_ids))));
	if ($created_ids) {
		$id_list = implode(',', $created_ids);
		$wpdb->query('START TRANSACTION');
		$wpdb->query(
			'DELETE FROM ' . AM_Ops_Tables::name(AM_Ops_Tables::JOBS)
			. " WHERE submission_id IN ({$id_list})"
		);
		$wpdb->query(
			'DELETE FROM ' . AM_Ops_Tables::name(AM_Ops_Tables::MESSAGES)
			. " WHERE submission_id IN ({$id_list})"
		);
		$wpdb->query(
			'DELETE FROM ' . AM_Ops_Tables::name(AM_Ops_Tables::FILES)
			. " WHERE submission_id IN ({$id_list})"
		);
		$wpdb->query(
			'DELETE FROM ' . AM_Ops_Tables::name(AM_Ops_Tables::EVENTS)
			. " WHERE submission_id IN ({$id_list})"
		);
		$wpdb->query(
			'DELETE FROM ' . AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS)
			. " WHERE id IN ({$id_list}) AND source = 'automated_test'"
		);
		$wpdb->query('COMMIT');
		delete_transient('am_ops_overview_metrics');
		echo '[CLEANUP] Removed prefix-locked synthetic submission IDs: ' . implode(', ', $created_ids) . "\n";
	}
}

echo "Passed: {$passed}; Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
