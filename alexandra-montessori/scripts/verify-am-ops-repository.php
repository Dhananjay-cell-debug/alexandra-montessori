<?php
/**
 * Transactional repository smoke test using one prefix-locked synthetic row.
 *
 * Usage:
 * php scripts/verify-am-ops-repository.php "C:\path\to\wordpress"
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
$created_submission_id = 0;

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

$repository = new AM_Ops_Repository();
$test_token = 'am-ops-verifier-' . bin2hex(random_bytes(12));
$payload    = array(
	'test_marker' => $test_token,
	'message'     => 'Synthetic repository verification only.',
);

try {
	$first = $repository->create(
		array(
			'type'                    => AM_Ops_Repository::TYPE_CONTACT,
			'source'                  => 'automated_test',
			'idempotency_key'         => $test_token,
			'branch_slug'             => 'heston',
			'branch_label'            => 'Heston',
			'customer_name'           => 'AM Ops Verifier',
			'customer_email'          => 'am-ops-verifier@example.invalid',
			'customer_phone'          => '+442000000000',
			'subject'                 => 'Repository verification',
			'payload'                 => $payload,
			'duplicate_fingerprint'   => hash('sha256', $test_token),
			'notification_state'      => 'suppressed',
		)
	);

	$check(!is_wp_error($first), 'Repository creates a synthetic submission');
	if (is_wp_error($first)) {
		echo "Repository error: {$first->get_error_code()} {$first->get_error_message()}\n";
	} else {
		$created_submission_id = (int) $first['submission']['id'];
		$check(true === $first['created'], 'First idempotent request is marked created');
		$check(
			(bool) preg_match('/^AM-\d{4}-[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{10}$/', $first['submission']['public_ref']),
			'Public reference is non-sequential and support-friendly'
		);
		$check(
			$payload === $first['submission']['payload'],
			'Structured payload round-trips without loss'
		);

		$second = $repository->create(
			array(
				'type'              => AM_Ops_Repository::TYPE_CONTACT,
				'source'            => 'automated_test',
				'idempotency_key'   => $test_token,
				'customer_name'     => 'A browser retry must not replace the customer',
				'customer_email'    => 'replacement@example.invalid',
				'payload'           => array('replacement' => true),
			)
		);

		$check(!is_wp_error($second), 'Repository accepts an idempotent browser retry');
		if (!is_wp_error($second)) {
			$check(false === $second['created'], 'Browser retry returns created=false');
			$check(
				$created_submission_id === (int) $second['submission']['id'],
				'Browser retry returns the original row'
			);
			$check(
				'AM Ops Verifier' === $second['submission']['customer_name'],
				'Browser retry cannot mutate immutable customer data'
			);
		}

		$duplicate = $repository->find_recent_duplicate(hash('sha256', $test_token), 600);
		$check(
			is_array($duplicate) && $created_submission_id === (int) $duplicate['id'],
			'Indexed short-window duplicate lookup finds the row'
		);

		$administrator = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			)
		);
		$actor_id = !empty($administrator) ? (int) $administrator[0] : 1;

		$opened_once  = $repository->record_first_open($created_submission_id, $actor_id, true);
		$opened_twice = $repository->record_first_open($created_submission_id, $actor_id, true);
		$check(!is_wp_error($opened_once), 'First-open transition succeeds');
		$check(!is_wp_error($opened_twice), 'Repeated open is harmless');
		if (!is_wp_error($opened_once) && !is_wp_error($opened_twice)) {
			$check('in_progress' === $opened_once['status'], 'First open advances New to In progress');
			$check($actor_id === (int) $opened_once['owner_user_id'], 'First open atomically claims an unassigned row');
			$check(
				$opened_once['first_opened_at'] === $opened_twice['first_opened_at'],
				'Repeated open does not overwrite the first-open timestamp'
			);
		}

		$events = $repository->events($created_submission_id);
		$types  = array_column($events, 'event_type');
		$check(1 === count(array_keys($types, 'submission_created', true)), 'Exactly one creation audit event exists');
		$check(1 === count(array_keys($types, 'first_opened', true)), 'Exactly one first-open audit event exists');
	}
} finally {
	if ($created_submission_id > 0) {
		global $wpdb;

		$submission = $repository->get($created_submission_id);
		$is_synthetic = is_array($submission)
			&& 'automated_test' === $submission['source']
			&& $test_token === ($submission['payload']['test_marker'] ?? '');

		if ($is_synthetic) {
			$wpdb->query('START TRANSACTION');
			$wpdb->delete(
				AM_Ops_Tables::name(AM_Ops_Tables::EVENTS),
				array('submission_id' => $created_submission_id),
				array('%d')
			);
			$wpdb->delete(
				AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS),
				array(
					'id'     => $created_submission_id,
					'source' => 'automated_test',
				),
				array('%d', '%s')
			);
			$wpdb->query('COMMIT');
			echo "[CLEANUP] Removed prefix-locked synthetic submission #{$created_submission_id}\n";
		} else {
			$failed++;
			echo "[FAIL] Synthetic cleanup refused because the ownership marker did not match\n";
		}
	}
}

echo "Passed: {$passed}; Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
