<?php
/**
 * Scale-ready public form REST ingestion.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class LDRN_Ops_Ingestion {
	/**
	 * Register the three existing public contracts. Contact and visit share the
	 * enquiry route, preserving the current React API surface.
	 *
	 * @return void
	 */
	public static function register() {
		add_action('rest_api_init', array(__CLASS__, 'routes'), 20);
	}

	/**
	 * @return void
	 */
	public static function routes() {
        foreach (array('quick-application' => 'application', 'application' => 'full_application') as $route => $callback) {
            register_rest_route('ldrn/v1', '/' . $route, array(
                'methods' => WP_REST_Server::CREATABLE, 'callback' => array(__CLASS__, $callback),
                'permission_callback' => '__return_true',
            ));
        }
		register_rest_route(
			'ldrn/v1',
			'/enquiry',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array(__CLASS__, 'enquiry'),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'ldrn/v1',
			'/availability',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array(__CLASS__, 'availability'),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'ldrn/v1',
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
		$guard = LDRN_Ops_Security::guard($request, 'enquiry', 65536);
		if (is_wp_error($guard) || $guard instanceof WP_REST_Response) {
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
			return self::invalid('ldrn_ops_name_required', 'Your first name is required.');
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

		$branch = self::branch($request->get_param('branch') ?: $request->get_param('setting'), true);
		if (is_wp_error($branch)) {
			return $branch;
		}

        if (!self::accepted($request->get_param('contactConsent')) || (!$is_visit && '' === $message)) {
            return self::invalid('ldrn_ops_contact_required', 'Please enter your message and confirm the privacy acknowledgement.');
        }
		$type = $is_visit
			? LDRN_Ops_Repository::TYPE_VISIT
			: LDRN_Ops_Repository::TYPE_CONTACT;
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
		$guard = LDRN_Ops_Security::guard($request, 'availability', 65536);
		if (is_wp_error($guard) || $guard instanceof WP_REST_Response) {
			return $guard;
		}

		$name       = self::text($request->get_param('parentName') ?: $request->get_param('name'), 200);
		$email      = self::email($request->get_param('email'));
		$phone      = self::phone($request->get_param('phone'), true);
		$child_name = self::text($request->get_param('childName'), 200);
		$child_age  = self::text($request->get_param('childAge'), 100);
		if ('' === $name) {
			return self::invalid('ldrn_ops_name_required', 'Parent name is required.');
		}
		if (is_wp_error($email)) {
			return $email;
		}
		if (is_wp_error($phone)) {
			return $phone;
		}
		if ('' === $child_name) {
			return self::invalid('ldrn_ops_child_name_required', 'Child name is required.');
		}
		if ('' === $child_age) {
			return self::invalid('ldrn_ops_child_age_required', 'Child age is required.');
		}

		$branch = self::branch($request->get_param('branch') ?: $request->get_param('setting'), true);
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
			return self::invalid('ldrn_ops_date_order', 'The end date cannot be before the start date.');
		}

        if (!self::accepted($request->get_param('availabilityConsent'))) {
            return self::invalid('ldrn_ops_consent_required', 'Please confirm the privacy acknowledgement.');
        }
        if ($start_date < current_time('Y-m-d')) {
            return self::invalid('ldrn_ops_start_date_past', 'Please choose a start date today or later.');
        }
        $start_time = self::time($request->get_param('startTime'));
        $end_time = self::time($request->get_param('endTime'));
        if ($start_time && $end_time && $end_time <= $start_time) {
            return self::invalid('ldrn_ops_time_order', 'The end time must be after the start time.');
        }
		$payload = array(
			'child_name'         => $child_name,
			'child_age'          => $child_age,
			'desired_start_date' => $start_date,
			'end_date'           => $end_date,
			'days'               => implode(', ', array_intersect(array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), (array) $request->get_param('days'))),
			'session_type'       => self::text($request->get_param('sessionType'), 100),
            'other_session' => self::text($request->get_param('otherSession'), 500),
			'start_time'         => self::time($request->get_param('startTime')),
			'end_time'           => self::time($request->get_param('endTime')),
			'sessions'           => self::text($request->get_param('sessions'), 500),
			'message'            => self::textarea($request->get_param('message'), 5000),
		);
		$mapped = self::common(
			$request,
			LDRN_Ops_Repository::TYPE_AVAILABILITY,
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
		$guard = LDRN_Ops_Security::guard(
			$request,
			'application',
			LDRN_Ops_Files::MAX_BYTES + 1024 * 1024
		);
		if (is_wp_error($guard) || $guard instanceof WP_REST_Response) {
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
			return self::invalid('ldrn_ops_name_required', 'First and last name are required.');
		}
		if (is_wp_error($email)) {
			return $email;
		}
		if (is_wp_error($phone)) {
			return $phone;
		}
		if ('' === $qualification || '' === $position) {
			return self::invalid('ldrn_ops_application_fields', 'Qualification and position are required.');
		}

        if (!self::accepted($request->get_param('careerConsent'))) {
            return self::invalid('ldrn_ops_consent_required', 'Please confirm the privacy acknowledgement.');
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
			LDRN_Ops_Repository::TYPE_APPLICATION,
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

    private static function accepted($value) {
        return in_array($value, array(true, 1, '1', 'on', 'true', 'yes'), true);
    }

    /** Full paper application: preserve all approved answers in the private record. */
    public static function full_application($request) {
        $guard = LDRN_Ops_Security::guard($request, 'application', LDRN_Ops_Files::MAX_BYTES + 1024 * 1024);
        if (is_wp_error($guard) || $guard instanceof WP_REST_Response) return $guard;
        $payload = array();
        foreach (array('address', 'age', 'barredList', 'closingDate', 'convictions', 'currentDuties', 'currentEmployer', 'currentJobTitle', 'currentReasonLeaving', 'currentStartDate', 'currentStudyCourse', 'currentStudyFrom', 'currentStudyGrade', 'currentStudyInstitution', 'currentStudyTo', 'currentUntilDate', 'dateOfBirth', 'declarationAccepted', 'declarationDate', 'declarationName', 'disabled', 'drivingLicence', 'email', 'employeeRelationshipDetails', 'employment1Employer', 'employment1From', 'employment1Post', 'employment1ReasonLeaving', 'employment1To', 'employment2Employer', 'employment2From', 'employment2Post', 'employment2ReasonLeaving', 'employment2To', 'employment3Employer', 'employment3From', 'employment3Post', 'employment3ReasonLeaving', 'employment3To', 'employment4Employer', 'employment4From', 'employment4Post', 'employment4ReasonLeaving', 'employment4To', 'employmentGaps', 'equalOpportunitiesConsent', 'ethnicity', 'forename', 'gender', 'homePhone', 'interestsAndVoluntaryWork', 'interviewAdjustments', 'jobReference', 'mayCallAtWork', 'mobilePhone', 'monitoringName', 'nationalInsurance', 'noticePeriod', 'otherTraining', 'position', 'professionalQualifications', 'publicTransport', 'qualification1Course', 'qualification1From', 'qualification1Grade', 'qualification1Institution', 'qualification1To', 'qualification2Course', 'qualification2From', 'qualification2Grade', 'qualification2Institution', 'qualification2To', 'qualification3Course', 'qualification3From', 'qualification3Grade', 'qualification3Institution', 'qualification3To', 'referee1Address', 'referee1ContactBeforeInterview', 'referee1Email', 'referee1Name', 'referee1Organisation', 'referee1Phone', 'referee1Relationship', 'referee2Address', 'referee2ContactBeforeInterview', 'referee2Email', 'referee2Name', 'referee2Organisation', 'referee2Phone', 'referee2Relationship', 'relatedToEmployee', 'safeguardingDetails', 'salary', 'supportingStatement', 'surname', 'vacancySource', 'vehicleAccess', 'workPermit', 'workPhone') as $field) {
            $value = $request->get_param($field);
            if (is_array($value) || is_object($value)) return self::invalid('ldrn_ops_field_invalid', 'Please check your application answers.');
            $payload[$field] = self::textarea($value, 10000);
        }
        foreach (array('position', 'forename', 'surname', 'address', 'employmentGaps', 'supportingStatement', 'declarationName') as $field) {
            if (empty($payload[$field])) return self::invalid('ldrn_ops_required', 'Please complete all required application fields.');
        }
        foreach (array('convictions', 'barredList', 'workPermit', 'referee1ContactBeforeInterview', 'referee2ContactBeforeInterview') as $field) {
            if (!in_array(strtolower($payload[$field] ?? ''), array('yes', 'no'), true)) return self::invalid('ldrn_ops_required', 'Please answer all required Yes / No questions.');
        }
        foreach (array('mayCallAtWork', 'disabled', 'drivingLicence', 'vehicleAccess', 'publicTransport', 'relatedToEmployee') as $field) {
            if (!empty($payload[$field]) && !in_array(strtolower($payload[$field]), array('yes', 'no'), true)) return self::invalid('ldrn_ops_answer', 'Please check the Yes / No answers.');
        }
        foreach (array(1, 2) as $number) {
            foreach (array('Name', 'Relationship', 'Organisation', 'Phone', 'Address') as $suffix) {
                if (empty($payload['referee' . $number . $suffix])) return self::invalid('ldrn_ops_referee_required', 'Please complete both referee details.');
            }
            $email = self::email($payload['referee' . $number . 'Email'] ?? '');
            if (is_wp_error($email)) return self::invalid('ldrn_ops_referee_email', 'Please provide valid email addresses for both referees.');
            $payload['referee' . $number . 'Email'] = $email;
        }
        if (!self::accepted($request->get_param('declarationAccepted'))) return self::invalid('ldrn_ops_declaration', 'Please confirm the application declaration.');
        foreach (array('declarationDate', 'dateOfBirth', 'closingDate', 'currentStartDate', 'currentUntilDate') as $field) {
            $date = self::date($payload[$field] ?? '', 'declarationDate' === $field);
            if (is_wp_error($date)) return $date;
            if (in_array($field, array('declarationDate', 'dateOfBirth', 'currentStartDate'), true) && $date > current_time('Y-m-d')) return self::invalid('ldrn_ops_future_date', 'Please check the dates in your application.');
        }
        if (!empty($payload['age']) && (!ctype_digit($payload['age']) || (int)$payload['age'] < 16 || (int)$payload['age'] > 100)) return self::invalid('ldrn_ops_age', 'Please check the age entered.');
        foreach ($payload as $key => $value) {
            if (preg_match('/^(qualification[1-3]|currentStudy|employment[1-4])(From|To)$/', $key) && $value && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) return self::invalid('ldrn_ops_month', 'Please check your employment and qualification dates.');
        }
        $payload['declarationAccepted'] = 'Yes';
        $payload['equalOpportunitiesConsent'] = self::accepted($request->get_param('equalOpportunitiesConsent')) ? 'Yes' : 'No';
        if ('No' === $payload['equalOpportunitiesConsent']) {
            foreach (array('monitoringName', 'gender', 'ethnicity', 'disabled') as $field) unset($payload[$field]);
        }
        $email = self::email($request->get_param('email'));
        $phone = self::phone($request->get_param('mobilePhone'), false);
        if (is_wp_error($email)) return $email;
        if (is_wp_error($phone)) return $phone;
        $branch = self::branch('', false);
        $mapped = self::common($request, LDRN_Ops_Repository::TYPE_APPLICATION, 'full_application_form', trim($payload['forename'].' '.$payload['surname']), $email, $phone, $branch, 'Staff application — '.$payload['position'], $payload);
        return self::persist_application($request, $mapped);
    }

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
			'ip_hash'               => LDRN_Ops_Security::ip_hash(),
			'user_agent_hash'       => LDRN_Ops_Security::user_agent_hash(),
			'idempotency_key'       => LDRN_Ops_Security::idempotency_key($request),
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
		$repository = new LDRN_Ops_Repository();
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
		$repository = new LDRN_Ops_Repository();
		$files      = new LDRN_Ops_Files();
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

		$prepared = $files->prepare_upload($_FILES['resume'] ?? $_FILES['cv'] ?? array());
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
					'ldrn_ops_application_file_incomplete',
					__('Your application was saved, but the CV could not be attached. Please retry with the same form.', 'ldrn-operations'),
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
		$queued = (new LDRN_Ops_Queue())->enqueue_submission_notification($submission);
		if (is_wp_error($queued)) {
			(new LDRN_Ops_Repository())->set_notification_state(
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
				'email' => $submission['customer_email'],
            'message' => 'Your submission has been saved. Please keep your reference number.',
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
			return self::invalid('ldrn_ops_branch_required', 'Please choose a nursery.');
		}

		$nurseries = function_exists('ldrn_get_settings') ? ldrn_get_settings() : array();
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
			'ldrn_ops_branch_invalid',
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

		$jobs = function_exists('ldrn_get_jobs') ? ldrn_get_jobs() : array();
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
			'ldrn_ops_job_invalid',
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
			return self::invalid('ldrn_ops_email_invalid', 'A valid email address is required.');
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
				'ldrn_ops_phone_invalid',
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
			return self::invalid('ldrn_ops_date_invalid', 'Please provide a valid date.');
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
			__($message, 'ldrn-operations'),
			array('status' => 400)
		);
	}
}
