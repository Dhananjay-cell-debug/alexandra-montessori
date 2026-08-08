<?php
/**
 * Durable notification/reply queue verification with wp_mail short-circuited.
 *
 * No real email leaves the machine. Prefix-locked rows are deleted afterward.
 *
 * Usage:
 * php scripts/verify-am-ops-queue.php "C:\path\to\wordpress"
 */

if (PHP_SAPI !== 'cli') {
	exit(1);
}

$wordpress_root = isset($argv[1]) ? rtrim((string) $argv[1], '/\\') : '';
if ('' === $wordpress_root || !is_file($wordpress_root . '/wp-load.php')) {
	fwrite(STDERR, "Pass a valid WordPress root containing wp-load.php.\n");
	exit(2);
}

if (!defined('AM_OPS_MONITOR_BCC')) {
	define('AM_OPS_MONITOR_BCC', 'ops-monitor@example.com');
}
require_once $wordpress_root . '/wp-load.php';

global $wpdb;

$passed = 0;
$failed = 0;
$submission_ids = array();
$token = 'queue-' . bin2hex(random_bytes(8));
$customer_email = $token . '-parent@example.com';
$nursery_email  = $token . '-nursery@example.com';
$settings_before = get_option(AM_Ops_Settings::OPTION, null);
$mail_calls = array();
add_filter('am_ops_immediate_wake_enabled', '__return_false');

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
$queue      = new AM_Ops_Queue();

$create_submission = static function ($suffix) use ($repository, $token, $customer_email, &$submission_ids) {
	$result = $repository->create(
		array(
			'type'               => AM_Ops_Repository::TYPE_CONTACT,
			'source'             => 'automated_test',
			'idempotency_key'    => $token . '-' . $suffix,
			'customer_name'      => 'Queue Verifier ' . $suffix,
			'customer_email'     => $customer_email,
			'customer_phone'     => '+442000000010',
			'subject'            => 'Queue verification ' . $token . ' ' . $suffix,
			'payload'            => array('test_marker' => $token, 'suffix' => $suffix),
			'notification_state' => 'not_queued',
		)
	);
	if (is_wp_error($result)) {
		return $result;
	}

	$submission_ids[] = (int) $result['submission']['id'];

	return $result['submission'];
};

$jobs_table     = AM_Ops_Tables::name(AM_Ops_Tables::JOBS);
$messages_table = AM_Ops_Tables::name(AM_Ops_Tables::MESSAGES);

try {
	$foreign_pending = (int) $wpdb->get_var(
		"SELECT COUNT(id) FROM {$jobs_table} WHERE state IN ('pending','processing')"
	);
	$check(0 === $foreign_pending, 'No unrelated pending Local jobs can be consumed by this verifier');
	if ($foreign_pending) {
		throw new RuntimeException('Queue verifier refused to process unrelated pending jobs.');
	}

	update_option(
		AM_Ops_Settings::OPTION,
		array(
			'notification_mode' => 'immediate',
			'default_recipient' => $nursery_email,
			'digest_minutes'    => 30,
		),
		false
	);

	$success_filter = static function ($preempt, $attributes) use (&$mail_calls) {
		$mail_calls[] = $attributes;
		return true;
	};
	add_filter('pre_wp_mail', $success_filter, 10, 2);

	$immediate = $create_submission('immediate');
	$check(!is_wp_error($immediate), 'Immediate test submission is created');
	$queued = is_wp_error($immediate) ? $immediate : $queue->enqueue_submission_notification($immediate);
	$check(!is_wp_error($queued) && true === $queued['queued'], 'Immediate notification is durably queued');
	$stats = $queue->process(10);
	$check(!is_wp_error($stats) && 1 === $stats['sent'], 'Immediate worker sends one claimed job');
	$immediate_row = is_wp_error($immediate) ? null : $repository->get((int) $immediate['id']);
	$check('sent' === ($immediate_row['notification_state'] ?? ''), 'Immediate success updates submission delivery state');
	$check(1 === count($mail_calls), 'Immediate mode invokes the mail transport once');
	$check(
		$nursery_email === ($mail_calls[0]['to'] ?? ''),
		'Immediate form alert is sent only to the configured nursery recipient'
	);
	$immediate_headers = (array) ($mail_calls[0]['headers'] ?? array());
	$check(
		!in_array('Bcc: ops-monitor@example.com', $immediate_headers, true),
		'Immediate form alert is not copied to the legacy monitoring mailbox'
	);
	$check(
		in_array(
			'From: "Queue Verifier immediate" <' . $customer_email . '>',
			$immediate_headers,
			true
		),
		'Immediate alert uses the literal form submitter as From without via wording'
	);
	$check(
		in_array(
			'Reply-To: "Queue Verifier immediate" <' . $customer_email . '>',
			$immediate_headers,
			true
		),
		'Immediate alert replies to the form submitter'
	);

	remove_filter('pre_wp_mail', $success_filter, 10);
	$mail_calls = array();

	$failure_filter = static function ($preempt, $attributes) use (&$mail_calls) {
		$mail_calls[] = $attributes;
		return false;
	};
	add_filter('pre_wp_mail', $failure_filter, 10, 2);

	$failure_submission = $create_submission('failure');
	$queued_failure = is_wp_error($failure_submission)
		? $failure_submission
		: $queue->enqueue_submission_notification($failure_submission);
	$check(!is_wp_error($queued_failure), 'Failure-path notification is durably queued');
	if (!is_wp_error($failure_submission)) {
		$wpdb->update(
			$jobs_table,
			array('max_attempts' => 2),
			array('submission_id' => (int) $failure_submission['id'])
		);
	}

	$first_failure = $queue->process(10);
	$check(
		!is_wp_error($first_failure) && 1 === $first_failure['retried'],
		'First transport rejection is scheduled for retry'
	);
	if (!is_wp_error($failure_submission)) {
		$wpdb->update(
			$jobs_table,
			array('next_attempt_at' => gmdate('Y-m-d H:i:s')),
			array('submission_id' => (int) $failure_submission['id'])
		);
	}
	$terminal_failure = $queue->process(10);
	$check(
		!is_wp_error($terminal_failure) && 1 === $terminal_failure['failed'],
		'Retry exhaustion becomes a visible terminal failure'
	);
	$failed_row = is_wp_error($failure_submission)
		? null
		: $repository->get((int) $failure_submission['id']);
	$check('failed' === ($failed_row['notification_state'] ?? ''), 'Terminal failure is visible on the submission');
	$failed_job = is_wp_error($failure_submission)
		? null
		: $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$jobs_table} WHERE submission_id = %d LIMIT 1",
				(int) $failure_submission['id']
			),
			ARRAY_A
		);
	$check(
		'failed' === ($failed_job['state'] ?? '') && 2 === (int) ($failed_job['attempts'] ?? 0),
		'Failed job preserves attempts and error state for operations'
	);

	remove_filter('pre_wp_mail', $failure_filter, 10);
	$mail_calls = array();
	add_filter('pre_wp_mail', $success_filter, 10, 2);

	update_option(
		AM_Ops_Settings::OPTION,
		array(
			'notification_mode' => 'digest',
			'default_recipient' => $nursery_email,
			'digest_minutes'    => 30,
		),
		false
	);
	$digest_one = $create_submission('digest-one');
	$digest_two = $create_submission('digest-two');
	$queue->enqueue_submission_notification($digest_one);
	$queue->enqueue_submission_notification($digest_two);
	$digest_ids = array((int) $digest_one['id'], (int) $digest_two['id']);
	$wpdb->query(
		"UPDATE {$jobs_table}
		SET next_attempt_at = UTC_TIMESTAMP()
		WHERE submission_id IN (" . implode(',', $digest_ids) . ")"
	);
	$digest_stats = $queue->process(10);
	$check(
		!is_wp_error($digest_stats) && 2 === $digest_stats['sent'],
		'Digest worker completes both queued items'
	);
	$check(1 === count($mail_calls), 'Two digest items produce one grouped email');
	$digest_headers = (array) ($mail_calls[0]['headers'] ?? array());
	$check(
		!in_array('Bcc: ops-monitor@example.com', $digest_headers, true),
		'Grouped digest is not copied to the legacy monitoring mailbox'
	);
	$check(
		in_array(
			'From: "Alexandra Montessori Website" <website@forms.alexandramontessori.co.uk>',
			$digest_headers,
			true
		),
		'Grouped digest uses the authenticated Alexandra Montessori sender'
	);
	$check(
		0 === count(array_filter($digest_headers, static function ($header) {
			return 0 === stripos((string) $header, 'Reply-To:');
		})),
		'Grouped digest does not pretend to represent one submitter'
	);
	$check(
		'digested' === $repository->get((int) $digest_one['id'])['notification_state']
		&& 'digested' === $repository->get((int) $digest_two['id'])['notification_state'],
		'Every grouped item records a digested state'
	);

	$mail_calls = array();
	$reply_submission = $create_submission('reply');
	$administrators = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ID'));
	$actor_id = !empty($administrators) ? (int) $administrators[0] : 1;
	$message_id = $queue->enqueue_reply(
		(int) $reply_submission['id'],
		'Private reply ' . $token,
		'This is a synthetic recipient-locked reply.',
		$actor_id
	);
	$check(is_int($message_id) && $message_id > 0, 'Recipient-locked reply is inserted and queued transactionally');
	$reply_stats = $queue->process(10);
	$check(!is_wp_error($reply_stats) && 1 === $reply_stats['sent'], 'Reply worker completes the queued message');
	$reply_row = $wpdb->get_row(
		$wpdb->prepare("SELECT * FROM {$messages_table} WHERE id = %d LIMIT 1", (int) $message_id),
		ARRAY_A
	);
	$check('sent' === ($reply_row['status'] ?? ''), 'Reply message stores the final sent state');
	$check($customer_email === ($reply_row['recipient'] ?? ''), 'Reply recipient is locked to the submitted customer email');
	$check(
		1 === count($mail_calls) && $customer_email === ($mail_calls[0]['to'] ?? ''),
		'Mail transport receives only the immutable customer recipient'
	);
	$reply_headers = (array) ($mail_calls[0]['headers'] ?? array());
	$check(
		!in_array('Bcc: ops-monitor@example.com', $reply_headers, true),
		'Private customer replies are never copied to the monitoring address'
	);
	$check(
		in_array(
			'From: Alexandra Montessori <website@forms.alexandramontessori.co.uk>',
			$reply_headers,
			true
		),
		'Customer reply uses the authenticated Alexandra Montessori sender'
	);
	$check(
		in_array('Reply-To: ' . $nursery_email, $reply_headers, true),
		'Customer reply routes follow-up back to the configured nursery inbox'
	);
	$reply_events = $repository->events((int) $reply_submission['id']);
	$reply_types  = array_column($reply_events, 'event_type');
	$check(
		in_array('reply_queued', $reply_types, true) && in_array('reply_sent', $reply_types, true),
		'Reply queue and delivery are both present in the audit timeline'
	);

	remove_filter('pre_wp_mail', $success_filter, 10);
} catch (Throwable $error) {
	$failed++;
	echo '[FAIL] Queue verifier exception: ' . $error->getMessage() . "\n";
} finally {
	remove_all_filters('pre_wp_mail');
	remove_filter('am_ops_immediate_wake_enabled', '__return_false');

	$submission_ids = array_values(array_unique(array_filter(array_map('intval', $submission_ids))));
	if ($submission_ids) {
		$id_list = implode(',', $submission_ids);
		$wpdb->query('START TRANSACTION');
		$wpdb->query("DELETE FROM {$jobs_table} WHERE submission_id IN ({$id_list})");
		$wpdb->query("DELETE FROM {$messages_table} WHERE submission_id IN ({$id_list})");
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
		echo '[CLEANUP] Removed prefix-locked synthetic submission IDs: ' . implode(', ', $submission_ids) . "\n";
	}

	if (null === $settings_before) {
		delete_option(AM_Ops_Settings::OPTION);
	} else {
		update_option(AM_Ops_Settings::OPTION, $settings_before, false);
	}
}

echo "Passed: {$passed}; Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
