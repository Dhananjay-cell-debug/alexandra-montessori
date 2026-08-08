<?php
/**
 * Scale-ready public form REST ingestion.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_Ops_Ingestion {
	/**
	 * Register the three existing public contracts. Contact and visit share the
	 * enquiry route, preserving the current React API surface.
	 *
	 * @return void
	 */
	public static function register() {
		add_action('rest_api_init', array(__CLASS__, 'routes'), 5);
	}

	/**
	 * @return void
	 */
	public static function routes() {
		register_rest_route(
			'am/v1',
			'/enquiry',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array(__CLASS__, 'enquiry'),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'am/v1',
			'/availability',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array(__CLASS__, 'availability'),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'am/v1',
			'/apply',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array(__CLASS__, 'application'),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Contact and visit requests.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function enquiry($request) {
		$guard = AM_Ops_Security::guard($request, 'enquiry', 65536);
		if (is_wp_error($guard)) {
			return $guard;
		}

		$kind     = sanitize_key((string) $request->get_param('kind'));
		$is_visit = 'booking' === $kind;
		$first    = self::text($request->get_param('firstName'), 100);
		$last     = self::text($request->get_param('lastName'), 100);
		$email    = self::email($request->get_param('email'));
		$phone    = self::phone($request->get_param('phone'), $is_visit);
		$message  = self::textarea($request->get_param('message'), 5000);
		$preferred_date = self::date($request->get_param('preferredDate'), false);

		if ('' === $first) {
			return self::invalid('am_ops_name_required', 'Your first name is required.');
		}
		if (is_wp_error($email)) {
			return $email;
		}
		if (is_wp_error($phone)) {
			return $phone;
		}
		if (is_wp_error($preferred_date)) {
			return $preferred_date;
		}

		$branch = self::branch($request->get_param('branch'), true);
		if (is_wp_error($branch)) {
			return $branch;
		}

		$type = $is_visit
			? AM_Ops_Repository::TYPE_VISIT
			: AM_Ops_Repository::TYPE_CONTACT;
		$payload = array(
			'first_name'           => $first,
			'last_name'            => $last,
			'message'              => $message,
			'preferred_visit_date' => $is_visit ? $preferred_date : '',
		);
		$mapped = self::common(
			$request,
			$type,
			$is_visit ? 'visit_form' : 'contact_form',
			trim($first . ' ' . $last),
			$email,
			$phone,
			$branch,
			$is_visit ? 'Visit request' : 'Contact enquiry',
			$payload
		);

		return self::persist($request, $mapped);
	}

	/**
	 * Availability requests.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function availability($request) {
		$guard = AM_Ops_Security::guard($request, 'availability', 65536);
		if (is_wp_error($guard)) {
			return $guard;
		}

		$name       = self::text($request->get_param('name'), 200);
		$email      = self::email($request->get_param('email'));
		$phone      = self::phone($request->get_param('phone'), true);
		$child_name = self::text($request->get_param('childName'), 200);
		$child_age  = self::text($request->get_param('childAge'), 100);
		if ('' === $name) {
			return self::invalid('am_ops_name_required', 'Parent name is required.');
		}
		if (is_wp_error($email)) {
			return $email;
		}
		if (is_wp_error($phone)) {
			return $phone;
		}
		if ('' === $child_name) {
			return self::invalid('am_ops_child_name_required', 'Child name is required.');
		}
		if ('' === $child_age) {
			return self::invalid('am_ops_child_age_required', 'Child age is required.');
		}

		$branch = self::branch($request->get_param('branch'), false);
		if (is_wp_error($branch)) {
			return $branch;
		}
		$start_date = self::date($request->get_param('startDate'), true);
		$end_date   = self::date($request->get_param('endDate'), false);
		if (is_wp_error($start_date)) {
			return $start_date;
		}
		if (is_wp_error($end_date)) {
			return $end_date;
		}
		if ($end_date && $end_date < $start_date) {
			return self::invalid('am_ops_date_order', 'The end date cannot be before the start date.');
		}

		$payload = array(
			'child_name'         => $child_name,
			'child_age'          => $child_age,
			'desired_start_date' => $start_date,
			'end_date'           => $end_date,
			'days'               => self::text($request->get_param('days'), 200),
			'session_type'       => self::text($request->get_param('sessionType'), 100),
			'start_time'         => self::time($request->get_param('startTime')),
			'end_time'           => self::time($request->get_param('endTime')),
			'sessions'           => self::text($request->get_param('sessions'), 500),
			'message'            => self::textarea($request->get_param('message'), 5000),
		);
		$mapped = self::common(
			$request,
			AM_Ops_Repository::TYPE_AVAILABILITY,
			'availability_form',
			$name,
			$email,
			$phone,
			$branch,
			'Availability request',
			$payload
		);

		return self::persist($request, $mapped);
	}

	/**
	 * Career applications with one private CV.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function application($request) {
		$guard = AM_Ops_Security::guard(
			$request,
			'application',
			AM_Ops_Files::MAX_BYTES + 1024 * 1024
		);
		if (is_wp_error($guard)) {
			return $guard;
		}

		$first         = self::text($request->get_param('firstName'), 100);
		$last          = self::text($request->get_param('lastName'), 100);
		$email         = self::email($request->get_param('email'));
		$phone         = self::phone($request->get_param('phone'), true);
		$qualification = self::text($request->get_param('qualification'), 255);
		$position      = self::text($request->get_param('position'), 255);
		$job_slug      = sanitize_title((string) $request->get_param('jobSlug')) ?: 'general';
		$job_title     = self::text($request->get_param('jobTitle'), 255);

		if ('' === $first || '' === $last) {
			return self::invalid('am_ops_name_required', 'First and last name are required.');
		}
		if (is_wp_error($email)) {
			return $email;
		}
		if (is_wp_error($phone)) {
			return $phone;
		}
		if ('' === $qualification || '' === $position) {
			return self::invalid('am_ops_application_fields', 'Qualification and position are required.');
		}

		$job = self::job($job_slug);
		if (is_wp_error($job)) {
			return $job;
		}
		$branch = self::application_branch($request, $job, $job_slug);
		if (is_wp_error($branch)) {
			return $branch;
		}
		if ('general' !== $job_slug) {
			$job_title = $job['title'];
			$position  = $job['title'];
		}
		$job_label = $job_title ?: ('general' === $job_slug ? $position : $job_slug);

		$payload = array(
			'first_name'    => $first,
			'last_name'     => $last,
			'qualification' => $qualification,
			'position'      => $position,
			'job_slug'      => $job_slug,
			'job_title'     => $job_title,
			'branch'        => $branch['slug'],
			'branch_label'  => $branch['label'],
			'message'       => self::textarea($request->get_param('about'), 10000),
		);
		$mapped = self::common(
			$request,
			AM_Ops_Repository::TYPE_APPLICATION,
			'careers_form',
			trim($first . ' ' . $last),
			$email,
			$phone,
			$branch,
			'Career application — ' . $job_label,
			$payload
		);

		return self::persist_application($request, $mapped);
	}

	/**
	 * Build normalized common fields and a duplicate fingerprint.
	 *
	 * @param WP_REST_Request    $request Request.
	 * @param string             $type Type.
	 * @param string             $source Source.
	 * @param string             $name Name.
	 * @param string             $email Email.
	 * @param string             $phone Phone.
	 * @param array<string,string> $branch Branch.
	 * @param string             $subject Subject.
	 * @param array<string,mixed> $payload Payload.
	 * @return array<string,mixed>
	 */
	private static function common($request, $type, $source, $name, $email, $phone, $branch, $subject, $payload) {
		$duplicate = hash(
			'sha256',
			wp_json_encode(
				array(
					'type'    => $type,
					'email'   => strtolower($email),
					'phone'   => $phone,
					'branch'  => $branch['slug'],
					'payload' => $payload,
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			)
		);

		return array(
			'type'                  => $type,
			'source'                => $source,
			'status'                => 'new',
			'priority'              => 'normal',
			'branch_slug'           => $branch['slug'],
			'branch_label'          => $branch['label'],
			'customer_name'         => $name,
			'customer_email'        => $email,
			'customer_phone'        => $phone,
			'subject'               => $subject . ' — ' . $name,
			'payload'               => $payload,
			'notification_state'    => 'not_queued',
			'duplicate_fingerprint' => $duplicate,
			'ip_hash'               => AM_Ops_Security::ip_hash(),
			'user_agent_hash'       => AM_Ops_Security::user_agent_hash(),
			'idempotency_key'       => AM_Ops_Security::idempotency_key($request),
		);
	}

	/**
	 * Persist a non-file submission, then enqueue notification work.
	 *
	 * @param WP_REST_Request    $request Request.
	 * @param array<string,mixed> $mapped Repository data.
	 * @return WP_REST_Response|WP_Error
	 */
	private static function persist($request, $mapped) {
		$repository = new AM_Ops_Repository();
		$existing   = $repository->get_by_idempotency_key($mapped['idempotency_key']);
		if ($existing) {
			return self::success($existing, false, true);
		}

		$duplicate = $repository->find_recent_duplicate($mapped['duplicate_fingerprint'], 600);
		if ($duplicate) {
			return self::success($duplicate, false, true);
		}

		$result = $repository->create($mapped);
		if (is_wp_error($result)) {
			return $result;
		}

		self::queue_notification($result['submission']);

		return self::success($result['submission'], $result['created'], !$result['created']);
	}

	/**
	 * Persist an application and safely converge a retry on exactly one file.
	 *
	 * @param WP_REST_Request    $request Request.
	 * @param array<string,mixed> $mapped Repository data.
	 * @return WP_REST_Response|WP_Error
	 */
	private static function persist_application($request, $mapped) {
		$repository = new AM_Ops_Repository();
		$files      = new AM_Ops_Files();
		$existing   = $repository->get_by_idempotency_key($mapped['idempotency_key']);
		if ($existing && $files->for_submission((int) $existing['id'])) {
			return self::success($existing, false, true);
		}

		if (!$existing) {
			$duplicate = $repository->find_recent_duplicate($mapped['duplicate_fingerprint'], 600);
			if ($duplicate && $files->for_submission((int) $duplicate['id'])) {
				return self::success($duplicate, false, true);
			}
			if ($duplicate) {
				$existing = $duplicate;
			}
		}

		$prepared = $files->prepare_upload($_FILES['resume'] ?? array());
		if (is_wp_error($prepared)) {
			return $prepared;
		}

		if ($existing) {
			$submission = $existing;
			$created    = false;
		} else {
			$result = $repository->create($mapped);
			if (is_wp_error($result)) {
				$files->discard($prepared);
				return $result;
			}
			$submission = $result['submission'];
			$created    = $result['created'];
		}

		$current_file = $files->for_submission((int) $submission['id']);
		if ($current_file) {
			$files->discard($prepared);
		} else {
			$attached = $files->attach((int) $submission['id'], $prepared);
			if (is_wp_error($attached)) {
				$files->discard($prepared);
				$repository->add_event(
					(int) $submission['id'],
					'file_attach_failed',
					array('error_code' => $attached->get_error_code())
				);
				return new WP_Error(
					'am_ops_application_file_incomplete',
					__('Your application was saved, but the CV could not be attached. Please retry with the same form.', 'alexandra-operations'),
					array('status' => 500, 'reference' => $submission['public_ref'])
				);
			}
		}

		if ('not_queued' === $submission['notification_state']) {
			self::queue_notification($submission);
		}

		return self::success($submission, $created, !$created);
	}

	/**
	 * Notification problems are operationally visible but never erase an
	 * already accepted customer submission.
	 *
	 * @param array<string,mixed> $submission Submission.
	 * @return void
	 */
	private static function queue_notification($submission) {
		$queued = (new AM_Ops_Queue())->enqueue_submission_notification($submission);
		if (is_wp_error($queued)) {
			(new AM_Ops_Repository())->set_notification_state(
				(int) $submission['id'],
				'failed',
				'',
				null,
				array('reason' => 'queue_insert_failed', 'error_code' => $queued->get_error_code())
			);
		}
	}

	/**
	 * @param array<string,mixed> $submission Submission.
	 * @param bool                $created Created by this request.
	 * @param bool                $duplicate Idempotent/duplicate response.
	 * @return WP_REST_Response
	 */
	private static function success($submission, $created, $duplicate) {
		$response = rest_ensure_response(
			array(
				'success'   => true,
				'reference' => $submission['public_ref'],
				'created'   => (bool) $created,
				'duplicate' => (bool) $duplicate,
			)
		);
		$response->set_status($created ? 201 : 200);
		$response->header('Cache-Control', 'no-store');

		return $response;
	}

	/**
	 * Resolve a slug only from the current ready Nursery collection.
	 *
	 * @param mixed $candidate Submitted branch slug.
	 * @param bool  $required Whether empty is invalid.
	 * @return array{slug:string,label:string}|WP_Error
	 */
	private static function branch($candidate, $required) {
		$slug = sanitize_title((string) $candidate);
		if ('' === $slug && !$required) {
			return array('slug' => '', 'label' => 'Not sure yet');
		}
		if ('' === $slug) {
			return self::invalid('am_ops_branch_required', 'Please choose a nursery.');
		}

		$nurseries = function_exists('am_get_nurseries') ? am_get_nurseries() : array();
		foreach ($nurseries as $nursery) {
			$current_slug = sanitize_title((string) ($nursery['id'] ?? ''));
			if ($current_slug && hash_equals($current_slug, $slug)) {
				return array(
					'slug'  => $current_slug,
					'label' => self::text($nursery['name'] ?? $current_slug, 255),
				);
			}
		}

		return self::invalid(
			'am_ops_branch_invalid',
			'The selected nursery is no longer available. Please refresh the page and choose again.'
		);
	}

	/**
	 * Resolve a selected vacancy from the current ready Jobs collection.
	 *
	 * @param string $slug Job slug.
	 * @return array<string,mixed>|WP_Error
	 */
	private static function job($slug) {
		if ('general' === $slug) {
			return array('id' => 'general', 'title' => 'General application', 'location' => '');
		}

		$jobs = function_exists('am_get_jobs') ? am_get_jobs() : array();
		foreach ($jobs as $job) {
			$current_slug = sanitize_title((string) ($job['id'] ?? $job['slug'] ?? ''));
			if ($current_slug && hash_equals($current_slug, $slug)) {
				return array(
					'id'       => $current_slug,
					'title'    => self::text($job['title'] ?? $current_slug, 255),
					'location' => self::text($job['location'] ?? '', 255),
				);
			}
		}

		return self::invalid(
			'am_ops_job_invalid',
			'The selected vacancy is no longer available. Please refresh the page or choose a general application.'
		);
	}

	/**
	 * Resolve Careers routing from the vacancy location or the general-form
	 * Nursery selection. "All Nurseries" and an intentionally empty general
	 * selection use the approved central recipient.
	 *
	 * @param WP_REST_Request     $request Request.
	 * @param array<string,mixed> $job Validated job.
	 * @param string              $job_slug Job slug or general.
	 * @return array{slug:string,label:string}|WP_Error
	 */
	private static function application_branch($request, $job, $job_slug) {
		$candidate = 'general' === $job_slug
			? sanitize_title((string) $request->get_param('branch'))
			: sanitize_title((string) ($job['location'] ?? ''));

		if (in_array($candidate, array('', 'all-nurseries', 'general'), true)) {
			return self::branch('', false);
		}

		return self::branch($candidate, false);
	}

	/**
	 * @param mixed $value Candidate email.
	 * @return string|WP_Error
	 */
	private static function email($value) {
		$email = strtolower(sanitize_email((string) $value));
		if (!is_email($email)) {
			return self::invalid('am_ops_email_invalid', 'A valid email address is required.');
		}

		return $email;
	}

	/**
	 * @param mixed $value Candidate E.164 number.
	 * @param bool  $required Required.
	 * @return string|WP_Error
	 */
	private static function phone($value, $required) {
		$value = trim((string) $value);
		if ('' === $value && !$required) {
			return '';
		}
		$phone = '+' . preg_replace('/\D+/', '', ltrim($value, '+'));
		if (!preg_match('/^\+[1-9]\d{7,14}$/', $phone)) {
			return self::invalid(
				'am_ops_phone_invalid',
				'A phone number with country code is required, for example +44.'
			);
		}

		return $phone;
	}

	/**
	 * @param mixed $value Candidate date.
	 * @param bool  $required Required.
	 * @return string|WP_Error
	 */
	private static function date($value, $required) {
		$value = trim((string) $value);
		if ('' === $value && !$required) {
			return '';
		}
		$date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
		$valid = $date && $date->format('Y-m-d') === $value;
		if (!$valid) {
			return self::invalid('am_ops_date_invalid', 'Please provide a valid date.');
		}

		return $value;
	}

	/**
	 * @param mixed $value Candidate 24-hour time.
	 * @return string
	 */
	private static function time($value) {
		$value = trim((string) $value);

		return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : '';
	}

	/**
	 * @param mixed $value Text.
	 * @param int   $limit Character cap.
	 * @return string
	 */
	private static function text($value, $limit) {
		return mb_substr(sanitize_text_field((string) $value), 0, $limit);
	}

	/**
	 * @param mixed $value Multiline text.
	 * @param int   $limit Character cap.
	 * @return string
	 */
	private static function textarea($value, $limit) {
		return mb_substr(sanitize_textarea_field((string) $value), 0, $limit);
	}

	/**
	 * @param string $code Error code.
	 * @param string $message User-safe message.
	 * @return WP_Error
	 */
	private static function invalid($code, $message) {
		return new WP_Error(
			$code,
			__($message, 'alexandra-operations'),
			array('status' => 400)
		);
	}
}
