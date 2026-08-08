<?php
/**
 * Public ingestion verification without switching Local's active REST routes.
 *
 * Tests Contact, Visit and Availability directly against the new handler,
 * including idempotency, duplicate detection, validation and atomic limits.
 * Prefix-locked synthetic rows are removed at the end.
 *
 * Usage:
 * php scripts/verify-am-ops-ingestion.php "C:\path\to\wordpress"
 */

if (PHP_SAPI !== 'cli') {
	exit(1);
}

define('AM_OPS_RL_BURST', 100);
define('AM_OPS_RL_HOURLY', 100);
define('AM_OPS_RL_FORM_HOURLY', 100);

$wordpress_root = isset($argv[1]) ? rtrim((string) $argv[1], '/\\') : '';
if ('' === $wordpress_root || !is_file($wordpress_root . '/wp-load.php')) {
	fwrite(STDERR, "Pass a valid WordPress root containing wp-load.php.\n");
	exit(2);
}

$_SERVER['REMOTE_ADDR']    = '192.0.2.200';
$_SERVER['HTTP_USER_AGENT']= 'Alexandra Operations CLI Verifier';

require_once $wordpress_root . '/wp-load.php';

global $wpdb;

$passed = 0;
$failed = 0;
$created_ids = array();
$token = 'amops-' . bin2hex(random_bytes(8));
$email = $token . '@example.com';
$settings_before = get_option(AM_Ops_Settings::OPTION, null);

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

$request = static function ($params, $idempotency_key) {
	$request = new WP_REST_Request('POST', '/am/v1/enquiry');
	$request->set_body_params($params);
	$request->set_header('X-AM-Idempotency-Key', $idempotency_key);
	$_SERVER['CONTENT_LENGTH'] = strlen(wp_json_encode($params));

	return $request;
};

$response_data = static function ($response) {
	return $response instanceof WP_REST_Response ? $response->get_data() : array();
};

$legacy_before = array(
	'posts' => (int) $wpdb->get_var(
		"SELECT COUNT(ID) FROM {$wpdb->posts}
		WHERE post_type IN ('am_enquiry','am_availability','am_application')"
	),
	'meta' => (int) $wpdb->get_var(
		"SELECT COUNT(pm.meta_id)
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE p.post_type IN ('am_enquiry','am_availability','am_application')"
	),
);

try {
	update_option(
		AM_Ops_Settings::OPTION,
		array(
			'notification_mode' => 'dashboard_only',
			'digest_minutes'    => 30,
		),
		false
	);

	$nurseries = function_exists('am_get_nurseries') ? am_get_nurseries() : array();
	$branch_slug = sanitize_title((string) ($nurseries[0]['id'] ?? ''));
	$check('' !== $branch_slug, 'A CMS-ready Nursery is available for route validation');

	$application_branch_method = new ReflectionMethod('AM_Ops_Ingestion', 'application_branch');
	$application_branch_method->setAccessible(true);
	$general_application_request = new WP_REST_Request('POST', '/am/v1/apply');
	$general_application_request->set_body_params(array('branch' => $branch_slug));
	$general_application_branch = $application_branch_method->invoke(
		null,
		$general_application_request,
		array('id' => 'general', 'title' => 'General application', 'location' => ''),
		'general'
	);
	$check(
		!is_wp_error($general_application_branch)
			&& $branch_slug === ($general_application_branch['slug'] ?? ''),
		'General application preserves the applicant-selected Nursery route'
	);

	$ready_jobs = function_exists('am_get_jobs') ? am_get_jobs() : array();
	if ($ready_jobs) {
		$job_slug = sanitize_title((string) ($ready_jobs[0]['id'] ?? ''));
		$job_method = new ReflectionMethod('AM_Ops_Ingestion', 'job');
		$job_method->setAccessible(true);
		$validated_job = $job_method->invoke(null, $job_slug);
		$vacancy_application_request = new WP_REST_Request('POST', '/am/v1/apply');
		$vacancy_application_branch = $application_branch_method->invoke(
			null,
			$vacancy_application_request,
			$validated_job,
			$job_slug
		);
		$expected_job_branch = sanitize_title((string) ($ready_jobs[0]['location'] ?? ''));
		$check(
			'all-nurseries' === $expected_job_branch
				? '' === ($vacancy_application_branch['slug'] ?? '')
				: (
					!is_wp_error($vacancy_application_branch)
					&& $expected_job_branch === ($vacancy_application_branch['slug'] ?? '')
				),
			'Vacancy application derives its Nursery route from the validated vacancy'
		);
	}

	$base = array(
		'firstName'    => 'AM Ops',
		'lastName'     => 'Verifier ' . $token,
		'email'        => $email,
		'phone'        => '+442000000001',
		'branch'       => $branch_slug,
		'message'      => 'Synthetic ingestion verification ' . $token,
		'website'      => '',
		'formLoadedAt' => (time() - 5) * 1000,
	);
	$idempotency = 'contact-' . $token;
	$first = AM_Ops_Ingestion::enquiry($request($base, $idempotency));
	$check($first instanceof WP_REST_Response, 'Contact handler accepts a valid submission');
	$first_data = $response_data($first);
	$check(201 === $first->get_status(), 'New Contact returns HTTP 201');
	$check(true === ($first_data['created'] ?? null), 'New Contact reports created=true');
	$check(
		(bool) preg_match('/^AM-\d{4}-[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{10}$/', $first_data['reference'] ?? ''),
		'Contact response includes a support reference'
	);

	$repository = new AM_Ops_Repository();
	$first_row  = $repository->get_by_public_reference($first_data['reference'] ?? '');
	if ($first_row) {
		$created_ids[] = (int) $first_row['id'];
	}
	$check((bool) $first_row, 'Accepted Contact is durable before response');
	$check('suppressed' === ($first_row['notification_state'] ?? ''), 'Dashboard-only mode is explicit and auditable');

	$retry = AM_Ops_Ingestion::enquiry($request($base, $idempotency));
	$retry_data = $response_data($retry);
	$check($retry instanceof WP_REST_Response && 200 === $retry->get_status(), 'Browser retry returns HTTP 200');
	$check(false === ($retry_data['created'] ?? null), 'Browser retry reports created=false');
	$check(
		($first_data['reference'] ?? '') === ($retry_data['reference'] ?? ''),
		'Browser retry returns the original reference'
	);

	$fingerprint_retry = AM_Ops_Ingestion::enquiry(
		$request($base, 'different-' . $token)
	);
	$fingerprint_data = $response_data($fingerprint_retry);
	$check(
		($first_data['reference'] ?? '') === ($fingerprint_data['reference'] ?? ''),
		'Short-window identical payload detection prevents a second row'
	);

	$visit_params = array_merge(
		$base,
		array(
			'kind'          => 'booking',
			'preferredDate' => gmdate('Y-m-d', time() + 3 * DAY_IN_SECONDS),
			'message'       => 'Synthetic visit verification ' . $token,
		)
	);
	$visit = AM_Ops_Ingestion::enquiry(
		$request($visit_params, 'visit-' . $token)
	);
	$visit_data = $response_data($visit);
	$check($visit instanceof WP_REST_Response && 201 === $visit->get_status(), 'Visit handler accepts a valid request');
	$visit_row = $repository->get_by_public_reference($visit_data['reference'] ?? '');
	if ($visit_row) {
		$created_ids[] = (int) $visit_row['id'];
	}
	$check(AM_Ops_Repository::TYPE_VISIT === ($visit_row['type'] ?? ''), 'Visit is normalized as its own indexed type');

	$availability_request = new WP_REST_Request('POST', '/am/v1/availability');
	$availability_request->set_header('X-AM-Idempotency-Key', 'availability-' . $token);
	$availability_request->set_body_params(
		array(
			'name'          => 'Availability Verifier ' . $token,
			'email'         => $email,
			'phone'         => '+442000000002',
			'childAge'      => '2 years',
			'branch'        => '',
			'startDate'     => gmdate('Y-m-d', time() + DAY_IN_SECONDS),
			'endDate'       => '',
			'days'          => 'Monday, Tuesday',
			'sessionType'   => 'Full Day',
			'sessions'      => 'Monday, Tuesday · Full Day',
			'message'       => 'Synthetic availability verification ' . $token,
			'website'       => '',
			'formLoadedAt'  => (time() - 5) * 1000,
		)
	);
	$_SERVER['CONTENT_LENGTH'] = 1000;
	$availability = AM_Ops_Ingestion::availability($availability_request);
	$availability_data = $response_data($availability);
	$check(
		$availability instanceof WP_REST_Response && 201 === $availability->get_status(),
		'Availability handler accepts Not sure yet as an intentional branch choice'
	);
	$availability_row = $repository->get_by_public_reference($availability_data['reference'] ?? '');
	if ($availability_row) {
		$created_ids[] = (int) $availability_row['id'];
	}
	$check(
		AM_Ops_Repository::TYPE_AVAILABILITY === ($availability_row['type'] ?? ''),
		'Availability is normalized as its own indexed type'
	);

	$invalid_branch = array_merge($base, array('branch' => 'invented-branch-' . $token));
	$invalid_result = AM_Ops_Ingestion::enquiry(
		$request($invalid_branch, 'invalid-branch-' . $token)
	);
	$check(
		is_wp_error($invalid_result) && 'am_ops_branch_invalid' === $invalid_result->get_error_code(),
		'An arbitrary branch slug is rejected server-side'
	);

	$invalid_phone = array_merge($visit_params, array('phone' => '020 1234 5678'));
	$invalid_phone_result = AM_Ops_Ingestion::enquiry(
		$request($invalid_phone, 'invalid-phone-' . $token)
	);
	$check(
		is_wp_error($invalid_phone_result) && 'am_ops_phone_invalid' === $invalid_phone_result->get_error_code(),
		'A national-only Visit phone is rejected server-side'
	);

	$atomic_bucket = 'verifier_' . sanitize_key($token);
	$hit_one   = AM_Ops_Security::hit($atomic_bucket, 2, 300);
	$hit_two   = AM_Ops_Security::hit($atomic_bucket, 2, 300);
	$hit_three = AM_Ops_Security::hit($atomic_bucket, 2, 300);
	$check(true === $hit_one && true === $hit_two && false === $hit_three, 'Atomic rate counter blocks exactly after its configured cap');

	$custom_count = (int) $wpdb->get_var(
		$wpdb->prepare(
			'SELECT COUNT(id) FROM ' . AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS)
			. ' WHERE customer_email = %s AND legacy_post_id IS NULL',
			$email
		)
	);
	$check(3 === $custom_count, 'Three valid form intents create exactly three custom-table rows');

	$legacy_after = array(
		'posts' => (int) $wpdb->get_var(
			"SELECT COUNT(ID) FROM {$wpdb->posts}
			WHERE post_type IN ('am_enquiry','am_availability','am_application')"
		),
		'meta' => (int) $wpdb->get_var(
			"SELECT COUNT(pm.meta_id)
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE p.post_type IN ('am_enquiry','am_availability','am_application')"
		),
	);
	$check($legacy_before === $legacy_after, 'New ingestion does not write or alter legacy CPT records');
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
			. " WHERE id IN ({$id_list}) AND customer_email = '" . esc_sql($email) . "'"
		);
		$wpdb->query('COMMIT');
		echo '[CLEANUP] Removed prefix-locked synthetic submission IDs: ' . implode(', ', $created_ids) . "\n";
	}

	$key_hash = AM_Ops_Security::ip_hash();
	$wpdb->delete(
		AM_Ops_Tables::name(AM_Ops_Tables::RATE_LIMITS),
		array('key_hash' => $key_hash),
		array('%s')
	);

	if (null === $settings_before) {
		delete_option(AM_Ops_Settings::OPTION);
	} else {
		update_option(AM_Ops_Settings::OPTION, $settings_before, false);
	}
}

echo "Passed: {$passed}; Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
