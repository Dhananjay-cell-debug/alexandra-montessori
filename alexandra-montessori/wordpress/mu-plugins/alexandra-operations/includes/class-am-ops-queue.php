<?php
/**
 * Durable notification and customer-reply job queue.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_Ops_Queue {
	const CRON_HOOK            = 'am_ops_process_jobs';
	const IMMEDIATE_CRON_HOOK  = 'am_ops_process_jobs_immediate';
	const LAST_WORKER_OPTION   = 'am_ops_last_worker_run';
	const LAST_WAKE_OPTION     = 'am_ops_last_worker_wake';
	const DEFAULT_MAIL_FROM    = 'website@forms.alexandramontessori.co.uk';
	const DEFAULT_DKIM_DOMAIN  = 'forms.alexandramontessori.co.uk';
	const DEFAULT_DKIM_SELECTOR = 'website';
	const DEFAULT_DKIM_KEY_FILE = '.am-forms-dkim-private.pem';

	/**
	 * Register the fallback cron worker.
	 *
	 * @return void
	 */
	public static function register() {
		add_filter('cron_schedules', array(__CLASS__, 'cron_schedules'));
		add_action('init', array(__CLASS__, 'schedule_fallback'), 20);
		add_action('rest_api_init', array(__CLASS__, 'register_worker_route'), 6);
		add_action(self::CRON_HOOK, array(__CLASS__, 'process_scheduled'));
		add_action(self::IMMEDIATE_CRON_HOOK, array(__CLASS__, 'process_scheduled'));
	}

	/**
	 * Register a signed loopback-only worker route. A short-lived HMAC prevents
	 * public callers from starting workers while keeping the request stateless.
	 *
	 * @return void
	 */
	public static function register_worker_route() {
		register_rest_route(
			'am/v1',
			'/worker',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array(__CLASS__, 'run_immediate_worker'),
				'permission_callback' => array(__CLASS__, 'authorize_immediate_worker'),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Worker request.
	 * @return true|WP_Error
	 */
	public static function authorize_immediate_worker($request) {
		$timestamp = (int) $request->get_param('timestamp');
		$token = sanitize_text_field((string) $request->get_param('token'));
		if (
			$timestamp < 1
			|| abs(time() - $timestamp) > 90
			|| !hash_equals(self::worker_token($timestamp), $token)
		) {
			return new WP_Error(
				'am_ops_worker_forbidden',
				__('Worker authorization failed.', 'alexandra-operations'),
				array('status' => 403)
			);
		}

		return true;
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public static function run_immediate_worker() {
		$queue = new self();
		$result = $queue->process(100);
		if (is_wp_error($result)) {
			return $result;
		}
		if ($queue->has_due_jobs()) {
			self::wake_immediate_worker();
		}

		$response = rest_ensure_response(array('success' => true, 'processed' => $result));
		$response->header('Cache-Control', 'no-store');

		return $response;
	}

	/**
	 * @param array<string,array<string,mixed>> $schedules Cron schedules.
	 * @return array<string,array<string,mixed>>
	 */
	public static function cron_schedules($schedules) {
		$schedules['am_ops_minute'] = array(
			'interval' => 60,
			'display'  => __('Every minute (Alexandra Operations)', 'alexandra-operations'),
		);

		return $schedules;
	}

	/**
	 * Schedule WP-Cron as a fallback. Production should invoke this hook from a
	 * real server cron so low site traffic cannot delay notifications.
	 *
	 * @return void
	 */
	public static function schedule_fallback() {
		if (!wp_next_scheduled(self::CRON_HOOK)) {
			wp_schedule_event(time() + 60, 'am_ops_minute', self::CRON_HOOK);
		}
	}

	/**
	 * @return void
	 */
	public static function process_scheduled() {
		$queue = new self();
		$result = $queue->process(100);
		if (!is_wp_error($result) && $queue->has_due_jobs()) {
			self::wake_immediate_worker();
		}
	}

	/**
	 * Schedule and non-blockingly spawn a dedicated immediate worker. The
	 * minute cron remains the durable fallback if the loopback cannot start.
	 * Repeated concurrent calls coalesce onto the same single cron event.
	 *
	 * @return array{scheduled:bool,loopback:bool,cron_spawned:bool}
	 */
	public static function wake_immediate_worker() {
		$scheduled = (bool) wp_next_scheduled(self::IMMEDIATE_CRON_HOOK);
		if (!$scheduled) {
			$schedule_result = wp_schedule_single_event(
				time(),
				self::IMMEDIATE_CRON_HOOK,
				array(),
				true
			);
			$scheduled = true === $schedule_result
				|| (bool) wp_next_scheduled(self::IMMEDIATE_CRON_HOOK);
		}

		$timestamp = time();
		$loopback = wp_remote_post(
			rest_url('am/v1/worker'),
			array(
				'timeout'     => 0.01,
				'blocking'    => false,
				'redirection' => 0,
				'body'        => array(
					'timestamp' => $timestamp,
					'token'     => self::worker_token($timestamp),
				),
			)
		);
		$loopback = !is_wp_error($loopback);
		$cron_spawned = !$loopback && function_exists('spawn_cron')
			? (bool) spawn_cron(time())
			: false;
		update_option(
			self::LAST_WAKE_OPTION,
			array(
				'scheduled'   => $scheduled,
				'loopback'    => $loopback,
				'cron_spawned'=> $cron_spawned,
				'created_at'  => gmdate('c'),
			),
			false
		);

		return array(
			'scheduled'    => $scheduled,
			'loopback'     => $loopback,
			'cron_spawned' => $cron_spawned,
		);
	}

	/**
	 * @param int $timestamp Unix timestamp.
	 * @return string
	 */
	private static function worker_token($timestamp) {
		return hash_hmac('sha256', (string) $timestamp, wp_salt('auth'));
	}

	/**
	 * Queue or intentionally suppress a new-submission operational alert.
	 *
	 * @param array<string,mixed> $submission Hydrated submission.
	 * @return array<string,mixed>|WP_Error
	 */
	public function enqueue_submission_notification($submission) {
		global $wpdb;

		$settings   = AM_Ops_Settings::get();
		$mode       = $settings['notification_mode'];
		$recipient  = AM_Ops_Settings::recipient_for(
			$submission['type'],
			$submission['branch_slug']
		);
		$repository = new AM_Ops_Repository();

		if ('dashboard_only' === $mode) {
			$state = $repository->set_notification_state(
				(int) $submission['id'],
				'suppressed',
				'',
				null,
				array('mode' => $mode)
			);

			return is_wp_error($state)
				? $state
				: array('queued' => false, 'state' => 'suppressed', 'mode' => $mode);
		}

		if (!is_email($recipient)) {
			$state = $repository->set_notification_state(
				(int) $submission['id'],
				'needs_configuration',
				'',
				null,
				array('mode' => $mode, 'reason' => 'recipient_missing')
			);

			return is_wp_error($state)
				? $state
				: array('queued' => false, 'state' => 'needs_configuration', 'mode' => $mode);
		}

		$job_type = 'digest' === $mode ? 'submission_digest' : 'submission_notification';
		$delay     = 'digest' === $mode ? (int) $settings['digest_minutes'] * MINUTE_IN_SECONDS : 0;
		$now       = gmdate('Y-m-d H:i:s');
		$unique    = hash(
			'sha256',
			implode(':', array($job_type, (int) $submission['id'], strtolower($recipient)))
		);
		$payload = wp_json_encode(
			array(
				'public_ref'     => $submission['public_ref'],
				'type'           => $submission['type'],
				'customer_name'  => $submission['customer_name'],
				'customer_email' => $submission['customer_email'],
				'customer_phone' => $submission['customer_phone'],
				'branch_label'   => $submission['branch_label'],
				'subject'        => $submission['subject'],
				'submitted_at'   => $submission['submitted_at'],
				'details'        => is_array($submission['payload']) ? $submission['payload'] : array(),
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);

		$wpdb->query('START TRANSACTION');
		$inserted = $wpdb->insert(
			AM_Ops_Tables::name(AM_Ops_Tables::JOBS),
			array(
				'job_type'       => $job_type,
				'submission_id'  => (int) $submission['id'],
				'message_id'     => null,
				'recipient'      => strtolower($recipient),
				'recipient_hash' => hash('sha256', strtolower($recipient)),
				'payload'        => $payload ?: '{}',
				'state'          => 'pending',
				'attempts'       => 0,
				'max_attempts'   => 5,
				'next_attempt_at'=> gmdate('Y-m-d H:i:s', time() + $delay),
				'locked_at'      => null,
				'lock_token'     => null,
				'last_error'     => '',
				'unique_key'     => $unique,
				'created_at'     => $now,
				'updated_at'     => $now,
				'finished_at'    => null,
			)
		);

		if (false === $inserted) {
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT id FROM ' . AM_Ops_Tables::name(AM_Ops_Tables::JOBS) . ' WHERE unique_key = %s LIMIT 1',
					$unique
				)
			);
			if (!$existing) {
				$wpdb->query('ROLLBACK');
				return $this->database_error('am_ops_notification_queue_failed');
			}
		}

		$notification_state = 'digest' === $mode ? 'digest_queued' : 'queued';
		$updated = $wpdb->update(
			AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS),
			array(
				'notification_state'       => $notification_state,
				'notification_destination' => strtolower($recipient),
				'updated_at'               => $now,
			),
			array('id' => (int) $submission['id'])
		);
		if (false === $updated) {
			$wpdb->query('ROLLBACK');
			return $this->database_error('am_ops_notification_state_failed');
		}

		$event = $repository->add_event(
			(int) $submission['id'],
			'notification_queued',
			array('mode' => $mode, 'destination' => strtolower($recipient))
		);
		if (is_wp_error($event)) {
			$wpdb->query('ROLLBACK');
			return $event;
		}

		$wpdb->query('COMMIT');
		if (
			'immediate' === $mode
			&& apply_filters('am_ops_immediate_wake_enabled', true, $submission)
		) {
			self::wake_immediate_worker();
		}

		return array(
			'queued' => true,
			'state'  => $notification_state,
			'mode'   => $mode,
		);
	}

	/**
	 * Queue a recipient-locked customer reply and audit it transactionally.
	 *
	 * @param int    $submission_id Submission ID.
	 * @param string $subject Reply subject.
	 * @param string $message Plain-text message.
	 * @param int    $actor_user_id Staff actor.
	 * @return int|WP_Error Message ID.
	 */
	public function enqueue_reply($submission_id, $subject, $message, $actor_user_id) {
		global $wpdb;

		$repository = new AM_Ops_Repository();
		$submission = $repository->get($submission_id);
		if (!$submission) {
			return new WP_Error('am_ops_submission_not_found', __('Submission not found.', 'alexandra-operations'));
		}

		$recipient = strtolower(sanitize_email($submission['customer_email']));
		$subject   = substr(sanitize_text_field($subject), 0, 255);
		$message   = sanitize_textarea_field($message);
		if (!is_email($recipient) || '' === $subject || '' === $message) {
			return new WP_Error(
				'am_ops_invalid_reply',
				__('Recipient, subject and message are required.', 'alexandra-operations')
			);
		}

		$now = gmdate('Y-m-d H:i:s');
		$wpdb->query('START TRANSACTION');
		$inserted = $wpdb->insert(
			AM_Ops_Tables::name(AM_Ops_Tables::MESSAGES),
			array(
				'submission_id' => (int) $submission_id,
				'recipient'     => $recipient,
				'recipient_hash'=> hash('sha256', $recipient),
				'subject'       => $subject,
				'message_body'  => $message,
				'actor_user_id' => (int) $actor_user_id,
				'channel'       => 'email',
				'transport'     => 'wordpress_mail',
				'status'        => 'queued',
				'provider_id'   => '',
				'last_error'    => '',
				'legacy_key'    => null,
				'created_at'    => $now,
				'attempted_at'  => null,
				'sent_at'       => null,
			)
		);
		if (false === $inserted) {
			$wpdb->query('ROLLBACK');
			return $this->database_error('am_ops_reply_insert_failed');
		}

		$message_id = (int) $wpdb->insert_id;
		$unique     = hash('sha256', 'reply:' . $message_id);
		$payload    = wp_json_encode(
			array('message_id' => $message_id),
			JSON_UNESCAPED_SLASHES
		);
		$job_inserted = $wpdb->insert(
			AM_Ops_Tables::name(AM_Ops_Tables::JOBS),
			array(
				'job_type'       => 'customer_reply',
				'submission_id'  => (int) $submission_id,
				'message_id'     => $message_id,
				'recipient'      => $recipient,
				'recipient_hash' => hash('sha256', $recipient),
				'payload'        => $payload ?: '{}',
				'state'          => 'pending',
				'attempts'       => 0,
				'max_attempts'   => 5,
				'next_attempt_at'=> $now,
				'locked_at'      => null,
				'lock_token'     => null,
				'last_error'     => '',
				'unique_key'     => $unique,
				'created_at'     => $now,
				'updated_at'     => $now,
				'finished_at'    => null,
			)
		);
		if (false === $job_inserted) {
			$wpdb->query('ROLLBACK');
			return $this->database_error('am_ops_reply_queue_failed');
		}

		$event = $repository->add_event(
			$submission_id,
			'reply_queued',
			array('message_id' => $message_id, 'recipient' => $recipient),
			$actor_user_id
		);
		if (is_wp_error($event)) {
			$wpdb->query('ROLLBACK');
			return $event;
		}

		$wpdb->query('COMMIT');

		return $message_id;
	}

	/**
	 * Claim and process due jobs.
	 *
	 * @param int $limit Maximum claimed jobs.
	 * @return array<string,int>|WP_Error
	 */
	public function process($limit = 100) {
		global $wpdb;

		$limit = min(500, max(1, (int) $limit));
		$table = AM_Ops_Tables::name(AM_Ops_Tables::JOBS);
		$now   = gmdate('Y-m-d H:i:s');
		$stale = gmdate('Y-m-d H:i:s', time() - 15 * MINUTE_IN_SECONDS);
		$token = hash('sha256', wp_generate_uuid4() . microtime(true));

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET state = 'pending', lock_token = NULL, locked_at = NULL,
					next_attempt_at = %s, updated_at = %s
				WHERE state = 'processing' AND locked_at < %s",
				$now,
				$now,
				$stale
			)
		);

		$claimed = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET state = 'processing', lock_token = %s, locked_at = %s,
					attempts = attempts + 1, updated_at = %s
				WHERE state = 'pending' AND next_attempt_at <= %s
				ORDER BY id ASC
				LIMIT %d",
				$token,
				$now,
				$now,
				$now,
				$limit
			)
		);
		if (false === $claimed) {
			return $this->database_error('am_ops_job_claim_failed');
		}

		$jobs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE lock_token = %s ORDER BY id ASC",
				$token
			),
			ARRAY_A
		);
		$stats = array(
			'claimed' => count($jobs),
			'sent'    => 0,
			'retried' => 0,
			'failed'  => 0,
		);

		$digest_groups = array();
		foreach ($jobs as $job) {
			if ('submission_digest' === $job['job_type']) {
				$digest_groups[$job['recipient']][] = $job;
				continue;
			}

			$result = $this->deliver_job($job);
			$stats[$result]++;
		}

		foreach ($digest_groups as $recipient => $digest_jobs) {
			$result = $this->deliver_digest($recipient, $digest_jobs);
			$stats[$result] += count($digest_jobs);
		}

		update_option(
			self::LAST_WORKER_OPTION,
			array_merge($stats, array('finished_at' => gmdate('c'))),
			false
		);

		return $stats;
	}

	/**
	 * @return bool Whether another queued job is ready to run now.
	 */
	private function has_due_jobs() {
		global $wpdb;

		$table = AM_Ops_Tables::name(AM_Ops_Tables::JOBS);
		$due = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table}
				WHERE state = 'pending' AND next_attempt_at <= %s
				ORDER BY id ASC LIMIT 1",
				gmdate('Y-m-d H:i:s')
			)
		);

		return null !== $due;
	}

	/**
	 * @param array<string,mixed> $job Claimed job.
	 * @return string sent, retried or failed.
	 */
	private function deliver_job($job) {
		if ('customer_reply' === $job['job_type']) {
			return $this->deliver_reply($job);
		}
		if ('submission_notification' === $job['job_type']) {
			return $this->deliver_notification($job);
		}

		return $this->finish_failure($job, 'Unknown job type.');
	}

	/**
	 * @param array<string,mixed> $job Notification job.
	 * @return string
	 */
	private function deliver_notification($job) {
		$payload = json_decode((string) $job['payload'], true);
		$payload = is_array($payload) ? $payload : array();
		$subject = sprintf(
			'New %s — %s',
			$this->type_label($payload['type'] ?? ''),
			$payload['public_ref'] ?? 'Alexandra submission'
		);
		$body = $this->notification_body($payload);
		$sent = $this->send_mail(
			$job['recipient'],
			$subject,
			$body,
			$this->notification_headers($payload),
			(string) ($payload['customer_email'] ?? ''),
			(string) ($payload['customer_name'] ?? '')
		);

		if (is_wp_error($sent)) {
			return $this->finish_failure($job, $sent->get_error_message());
		}

		$this->finish_success($job, 'sent');

		return 'sent';
	}

	/**
	 * @param array<string,mixed> $job Reply job.
	 * @return string
	 */
	private function deliver_reply($job) {
		global $wpdb;

		$message = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . AM_Ops_Tables::name(AM_Ops_Tables::MESSAGES) . ' WHERE id = %d LIMIT 1',
				(int) $job['message_id']
			),
			ARRAY_A
		);
		if (!$message || !hash_equals((string) $message['recipient'], (string) $job['recipient'])) {
			return $this->finish_failure($job, 'Recipient-locked message is unavailable.');
		}

		$headers = array('Content-Type: text/plain; charset=UTF-8');
		$from    = $this->sender_email();
		if (is_email($from)) {
			$headers[] = 'From: Alexandra Montessori <' . $from . '>';
			// A parent hitting "Reply" must reach the nursery team, not whichever
			// transport happens to deliver the message.
			$headers[] = 'Reply-To: ' . $this->reply_inbox_for((int) $job['submission_id'], $from);
		}

		$sent = $this->send_mail(
			$message['recipient'],
			$message['subject'],
			$message['message_body'],
			$headers
		);
		if (is_wp_error($sent)) {
			return $this->finish_failure($job, $sent->get_error_message());
		}

		$now = gmdate('Y-m-d H:i:s');
		$wpdb->update(
			AM_Ops_Tables::name(AM_Ops_Tables::MESSAGES),
			array(
				'status'       => 'sent',
				'last_error'   => '',
				'attempted_at' => $now,
				'sent_at'      => $now,
			),
			array('id' => (int) $message['id'])
		);
		$this->finish_success($job, 'sent');
		(new AM_Ops_Repository())->add_event(
			(int) $job['submission_id'],
			'reply_sent',
			array('message_id' => (int) $message['id']),
			(int) $message['actor_user_id']
		);

		return 'sent';
	}

	/**
	 * Deliver one digest email for a recipient's claimed rows.
	 *
	 * @param string                         $recipient Email.
	 * @param array<int,array<string,mixed>> $jobs Claimed digest rows.
	 * @return string sent, retried or failed.
	 */
	private function deliver_digest($recipient, $jobs) {
		$items = array();
		foreach ($jobs as $job) {
			$payload = json_decode((string) $job['payload'], true);
			if (is_array($payload)) {
				$items[] = $payload;
			}
		}

		$subject = sprintf(
			'Alexandra Montessori submissions digest — %d new',
			count($items)
		);
		$body = $this->digest_body($items);
		$sent = $this->send_mail(
			$recipient,
			$subject,
			$body,
			$this->notification_headers()
		);

		if (is_wp_error($sent)) {
			$result = 'retried';
			foreach ($jobs as $job) {
				$result = $this->finish_failure($job, $sent->get_error_message());
			}
			return $result;
		}

		foreach ($jobs as $job) {
			$this->finish_success($job, 'digested');
		}

		return 'sent';
	}

	/**
	 * @param array<string,mixed> $job Claimed job.
	 * @param string              $submission_state sent or digested.
	 * @return void
	 */
	private function finish_success($job, $submission_state) {
		global $wpdb;

		$now = gmdate('Y-m-d H:i:s');
		$wpdb->update(
			AM_Ops_Tables::name(AM_Ops_Tables::JOBS),
			array(
				'state'       => 'sent',
				'locked_at'   => null,
				'lock_token'  => null,
				'last_error'  => '',
				'updated_at'  => $now,
				'finished_at' => $now,
			),
			array('id' => (int) $job['id'])
		);

		if ('customer_reply' !== $job['job_type'] && !empty($job['submission_id'])) {
			(new AM_Ops_Repository())->set_notification_state(
				(int) $job['submission_id'],
				$submission_state,
				$job['recipient'],
				null,
				array('job_id' => (int) $job['id'])
			);
		}
	}

	/**
	 * @param array<string,mixed> $job Claimed job.
	 * @param string              $error Transport error.
	 * @return string retried or failed.
	 */
	private function finish_failure($job, $error) {
		global $wpdb;

		$attempts = (int) $job['attempts'];
		$terminal = $attempts >= (int) $job['max_attempts'];
		$state    = $terminal ? 'failed' : 'pending';
		$delay    = min(HOUR_IN_SECONDS, 60 * (2 ** max(0, $attempts - 1)));
		$now      = gmdate('Y-m-d H:i:s');
		$wpdb->update(
			AM_Ops_Tables::name(AM_Ops_Tables::JOBS),
			array(
				'state'          => $state,
				'next_attempt_at'=> gmdate('Y-m-d H:i:s', time() + $delay),
				'locked_at'      => null,
				'lock_token'     => null,
				'last_error'     => substr(wp_strip_all_tags((string) $error), 0, 65535),
				'updated_at'     => $now,
				'finished_at'    => $terminal ? $now : null,
			),
			array('id' => (int) $job['id'])
		);

		if (!empty($job['submission_id'])) {
			$repository = new AM_Ops_Repository();
			if ('customer_reply' === $job['job_type']) {
				$this->update_message_failure(
					(int) $job['message_id'],
					$error,
					$terminal
				);
				$repository->add_event(
					(int) $job['submission_id'],
					$terminal ? 'reply_failed' : 'reply_retrying',
					array(
						'message_id' => (int) $job['message_id'],
						'job_id'     => (int) $job['id'],
						'attempts'   => $attempts,
					)
				);
			} else {
				$repository->set_notification_state(
					(int) $job['submission_id'],
					$terminal ? 'failed' : 'retrying',
					$job['recipient'],
					null,
					array('job_id' => (int) $job['id'], 'attempts' => $attempts)
				);
			}
		}

		return $terminal ? 'failed' : 'retried';
	}

	/**
	 * @param int    $message_id Message row.
	 * @param string $error Transport error.
	 * @param bool   $terminal Whether retries are exhausted.
	 * @return void
	 */
	private function update_message_failure($message_id, $error, $terminal = false) {
		global $wpdb;

		$wpdb->update(
			AM_Ops_Tables::name(AM_Ops_Tables::MESSAGES),
			array(
				'status'       => $terminal ? 'failed' : 'retrying',
				'last_error'   => substr(wp_strip_all_tags((string) $error), 0, 65535),
				'attempted_at' => gmdate('Y-m-d H:i:s'),
			),
			array('id' => (int) $message_id)
		);
	}

	/**
	 * Capture transport errors without writing personal data to public files.
	 *
	 * @param string   $to Recipient.
	 * @param string   $subject Subject.
	 * @param string   $body Body.
	 * @param string[] $headers Headers.
	 * @param string   $message_from      Message-specific From address.
	 * @param string   $message_from_name Message-specific From display name.
	 * @return true|WP_Error
	 */
	private function send_mail($to, $subject, $body, $headers, $message_from = '', $message_from_name = '') {
		$failure = null;
		$mailer_configuration = $this->production_mailer_configuration($message_from, $message_from_name);
		if (is_wp_error($mailer_configuration)) {
			return $mailer_configuration;
		}
		$listener = static function ($error) use (&$failure) {
			$failure = $error instanceof WP_Error
				? $error
				: new WP_Error('am_ops_mail_failed', 'Unknown mail transport error.');
		};
		if (is_callable($mailer_configuration)) {
			add_action('phpmailer_init', $mailer_configuration, PHP_INT_MAX);
		}
		add_action('wp_mail_failed', $listener);
		try {
			$sent = wp_mail($to, $subject, $body, $headers);
		} finally {
			remove_action('wp_mail_failed', $listener);
			if (is_callable($mailer_configuration)) {
				remove_action('phpmailer_init', $mailer_configuration, PHP_INT_MAX);
			}
		}

		if (!$sent) {
			return $failure ?: new WP_Error('am_ops_mail_failed', 'The mail transport rejected the message.');
		}

		return true;
	}

	/**
	 * Keep production operations mail off the legacy personal Gmail transport.
	 *
	 * The legacy site-wide SMTP hook authenticates as a developer Gmail account.
	 * Gmail consequently rewrites every From address back to that personal
	 * account. Operations mail switches only its own PHPMailer instance to the
	 * hosting transport. The current submitter can remain the literal From for
	 * an immediate form alert, while the isolated forms identity signs and owns
	 * the envelope. Other WordPress mail remains untouched.
	 *
	 * @param string $message_from      Message-specific From address.
	 * @param string $message_from_name Message-specific From display name.
	 * @return callable|null|WP_Error
	 */
	private function production_mailer_configuration($message_from = '', $message_from_name = '') {
		$host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
		if (!in_array($host, array('alexandramontessori.co.uk', 'www.alexandramontessori.co.uk'), true)) {
			return null;
		}

		$sender      = $this->sender_email();
		$from        = strtolower(sanitize_email((string) $message_from));
		$from        = is_email($from) ? $from : $sender;
		$from_name   = $this->mail_display_name($message_from_name);
		$dkim_domain = defined('AM_OPS_DKIM_DOMAIN')
			? strtolower(trim((string) AM_OPS_DKIM_DOMAIN))
			: self::DEFAULT_DKIM_DOMAIN;
		$selector    = defined('AM_OPS_DKIM_SELECTOR')
			? strtolower(trim((string) AM_OPS_DKIM_SELECTOR))
			: self::DEFAULT_DKIM_SELECTOR;
		$key_path    = defined('AM_OPS_DKIM_PRIVATE_KEY')
			? (string) AM_OPS_DKIM_PRIVATE_KEY
			: dirname(rtrim(ABSPATH, '/\\'), 3) . '/' . self::DEFAULT_DKIM_KEY_FILE;
		$sender_domain = false !== strpos($sender, '@')
			? strtolower(substr(strrchr($sender, '@'), 1))
			: '';

		if (
			!is_email($sender)
			|| $sender_domain !== $dkim_domain
			|| !preg_match('/^[a-z0-9][a-z0-9._-]*$/', $selector)
			|| !is_readable($key_path)
		) {
			return new WP_Error(
				'am_ops_mail_identity_unavailable',
				'The authenticated Alexandra Montessori mail identity is unavailable.'
			);
		}

		return static function ($phpmailer) use ($sender, $from, $from_name, $dkim_domain, $selector, $key_path) {
			// Run after the legacy theme SMTP hook and change transport only for
			// this operations message; no global mail setting is mutated.
			$phpmailer->isMail();
			$phpmailer->setFrom($from, $from_name, false);
			$phpmailer->Sender          = $sender;
			$phpmailer->DKIM_domain     = $dkim_domain;
			$phpmailer->DKIM_selector   = $selector;
			$phpmailer->DKIM_private    = $key_path;
			$phpmailer->DKIM_passphrase = '';
			$phpmailer->DKIM_identity   = $sender;
		};
	}

	/**
	 * Build headers for a nursery notification without copying submitted personal
	 * data to a developer or monitoring mailbox.
	 *
	 * @param array<string,mixed> $payload Notification payload, when the alert
	 *                            belongs to a single identifiable enquirer.
	 * @return string[]
	 */
	private function notification_headers($payload = array()) {
		return array_merge(
			array('Content-Type: text/html; charset=UTF-8'),
			$this->sender_identity_headers(is_array($payload) ? $payload : array())
		);
	}

	/**
	 * Resolve the branch inbox a customer reply should be answered into.
	 *
	 * @param int    $submission_id Submission ID.
	 * @param string $fallback Address to use when the branch cannot be resolved.
	 * @return string
	 */
	private function reply_inbox_for($submission_id, $fallback) {
		$submission = $submission_id
			? (new AM_Ops_Repository())->get($submission_id)
			: null;
		if (!is_array($submission)) {
			return $fallback;
		}

		$inbox = strtolower(
			sanitize_email(
				(string) AM_Ops_Settings::recipient_for(
					(string) ($submission['type'] ?? ''),
					(string) ($submission['branch_slug'] ?? '')
				)
			)
		);

		return is_email($inbox) ? $inbox : $fallback;
	}

	/**
	 * Present an immediate form alert as coming from the submitted address.
	 *
	 * The selected nursery remains the sole recipient. Reply-To repeats the
	 * submitted address so the nursery's Reply button reaches the same person.
	 *
	 * @param array<string,mixed> $payload Notification payload.
	 * @return string[]
	 */
	private function sender_identity_headers($payload) {
		$email = strtolower(sanitize_email((string) ($payload['customer_email'] ?? '')));
		$name    = wp_strip_all_tags((string) ($payload['customer_name'] ?? ''));
		$headers = array();
		if (is_email($email)) {
			$headers[] = 'Reply-To: ' . $this->mail_address($name, $email);
			$headers[] = 'From: ' . $this->mail_address($name, $email);
		} else {
			$headers[] = 'From: ' . $this->mail_address(
				'Alexandra Montessori Website',
				$this->sender_email()
			);
		}

		return $headers;
	}

	/**
	 * Resolve the non-personal, domain-authenticated operations sender.
	 *
	 * @return string
	 */
	private function sender_email() {
		$configured = defined('AM_OPS_MAIL_FROM')
			? strtolower(sanitize_email((string) AM_OPS_MAIL_FROM))
			: '';

		return is_email($configured) ? $configured : self::DEFAULT_MAIL_FROM;
	}

	/**
	 * Build one RFC 5322 address, keeping header-splitting characters out of the
	 * display name because it is assembled from visitor-supplied text.
	 *
	 * @param string $name Display name.
	 * @param string $email Address.
	 * @return string
	 */
	private function mail_address($name, $email) {
		$name = $this->mail_display_name($name);

		return '' === $name ? $email : '"' . $name . '" <' . $email . '>';
	}

	/**
	 * Normalize a display name before it reaches a mail header or PHPMailer.
	 *
	 * @param string $name Display name.
	 * @return string
	 */
	private function mail_display_name($name) {
		$name = wp_strip_all_tags((string) $name);
		$name = trim(str_replace(array("\r", "\n", "\t", '"', '\\'), ' ', $name));

		return trim(preg_replace('/\s+/', ' ', $name));
	}

	/**
	 * @param array<string,mixed> $payload Notification payload.
	 * @return string
	 */
	private function notification_body($payload) {
		$link = admin_url(
			'admin.php?page=am_submissions&view=submission&id=' . rawurlencode((string) ($payload['public_ref'] ?? ''))
		);
		$type    = (string) ($payload['type'] ?? '');
		$details = is_array($payload['details'] ?? null) ? $payload['details'] : array();
		$rows    = array(
			'Reference' => $payload['public_ref'] ?? '',
			'Type'      => $this->type_label($type),
		);

		if (AM_Ops_Repository::TYPE_AVAILABILITY === $type) {
			$rows += array(
				'Parent name'             => $payload['customer_name'] ?? '',
				'Email'                   => $payload['customer_email'] ?? '',
				'Contact number'          => $payload['customer_phone'] ?? '',
				'Child name'              => $details['child_name'] ?? '',
				'Child age'               => $details['child_age'] ?? '',
				'Preferred nursery'       => $payload['branch_label'] ?? '',
				'Required start date'     => $details['desired_start_date'] ?? '',
				'Required end date'       => $details['end_date'] ?? '',
				'Required days'           => $details['days'] ?? '',
				'Preferred session'       => $details['session_type'] ?? '',
				'Start time'              => $details['start_time'] ?? '',
				'End time'                => $details['end_time'] ?? '',
				'Session summary'         => $details['sessions'] ?? '',
				'Additional requirements' => $details['message'] ?? '',
			);
		} else {
			$rows += array(
				'Person'         => $payload['customer_name'] ?? '',
				'Email'          => $payload['customer_email'] ?? '',
				'Contact number' => $payload['customer_phone'] ?? '',
				'Branch'         => $payload['branch_label'] ?? '',
			);
		}
		$rows['Received'] = $payload['submitted_at'] ?? '';

		$html = '<p>A new website submission is ready in the private operations dashboard.</p><table style="border-collapse:collapse">';
		foreach ($rows as $label => $value) {
			if ('' === trim((string) $value)) {
				continue;
			}
			$html .= '<tr><th style="padding:6px 10px;text-align:left;border:1px solid #ddd">'
				. esc_html($label)
				. '</th><td style="padding:6px 10px;border:1px solid #ddd;white-space:pre-wrap">'
				. esc_html((string) $value)
				. '</td></tr>';
		}
		$html .= '</table><p><a href="' . esc_url($link) . '">Open the private submission</a></p>';

		return $html;
	}

	/**
	 * @param array<int,array<string,mixed>> $items Digest items.
	 * @return string
	 */
	private function digest_body($items) {
		$link = admin_url('admin.php?page=am_submissions');
		$html = '<p>New website submissions are waiting in the private operations dashboard.</p>'
			. '<table style="border-collapse:collapse;width:100%"><thead><tr>'
			. '<th style="padding:6px;border:1px solid #ddd">Reference</th>'
			. '<th style="padding:6px;border:1px solid #ddd">Type</th>'
			. '<th style="padding:6px;border:1px solid #ddd">Person</th>'
			. '<th style="padding:6px;border:1px solid #ddd">Branch</th>'
			. '</tr></thead><tbody>';
		foreach ($items as $item) {
			$html .= '<tr><td style="padding:6px;border:1px solid #ddd">'
				. esc_html((string) ($item['public_ref'] ?? ''))
				. '</td><td style="padding:6px;border:1px solid #ddd">'
				. esc_html($this->type_label($item['type'] ?? ''))
				. '</td><td style="padding:6px;border:1px solid #ddd">'
				. esc_html((string) ($item['customer_name'] ?? ''))
				. '</td><td style="padding:6px;border:1px solid #ddd">'
				. esc_html((string) ($item['branch_label'] ?? ''))
				. '</td></tr>';
		}
		$html .= '</tbody></table><p><a href="' . esc_url($link) . '">Open the submissions dashboard</a></p>';

		return $html;
	}

	/**
	 * @param string $type Submission type.
	 * @return string
	 */
	private function type_label($type) {
		$labels = array(
			AM_Ops_Repository::TYPE_CONTACT      => 'contact enquiry',
			AM_Ops_Repository::TYPE_VISIT        => 'visit request',
			AM_Ops_Repository::TYPE_AVAILABILITY => 'availability request',
			AM_Ops_Repository::TYPE_APPLICATION  => 'career application',
		);

		return $labels[$type] ?? 'website submission';
	}

	/**
	 * @param string $code Stable internal code.
	 * @return WP_Error
	 */
	private function database_error($code) {
		global $wpdb;

		if ($wpdb->last_error) {
			error_log('[Alexandra Operations] ' . $code . ': ' . $wpdb->last_error);
		}

		return new WP_Error(
			$code,
			__('The notification work could not be stored.', 'alexandra-operations')
		);
	}
}
