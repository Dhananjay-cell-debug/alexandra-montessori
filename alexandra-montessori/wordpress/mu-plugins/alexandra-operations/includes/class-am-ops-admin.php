<?php
/**
 * High-volume WordPress operations command centre.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_Ops_Admin {
	const MENU_SLUG     = 'am_submissions';
	const INBOX_SLUG    = 'am_submissions_inbox';
	const FOLLOWUP_SLUG = 'am_submissions_follow_up';
	const ISSUES_SLUG   = 'am_submissions_issues';
	const SETTINGS_SLUG = 'am_submissions_settings';
	const DETAIL_SLUG   = 'am_submission';

	/**
	 * @return void
	 */
	public static function register() {
		add_action('admin_menu', array(__CLASS__, 'menu'), 8);
		add_action('admin_menu', array(__CLASS__, 'badge'), 999);
		add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
		add_action('admin_post_am_ops_update_submission', array(__CLASS__, 'handle_update'));
		add_action('admin_post_am_ops_bulk_update', array(__CLASS__, 'handle_bulk'));
		add_action('admin_post_am_ops_queue_reply', array(__CLASS__, 'handle_reply'));
		add_action('admin_post_am_ops_retry_job', array(__CLASS__, 'handle_retry_job'));
		add_action('admin_post_am_ops_export', array(__CLASS__, 'handle_export'));
	}

	/**
	 * @return void
	 */
	public static function menu() {
		add_menu_page(
			__('Submissions', 'alexandra-operations'),
			__('Submissions', 'alexandra-operations'),
			'manage_am_submissions',
			self::MENU_SLUG,
			array(__CLASS__, 'inbox'),
			'dashicons-clipboard',
			24
		);
		add_submenu_page(
			self::MENU_SLUG,
			__('Submissions inbox', 'alexandra-operations'),
			__('Inbox', 'alexandra-operations'),
			'manage_am_submissions',
			self::MENU_SLUG,
			array(__CLASS__, 'inbox')
		);
		add_submenu_page(
			null,
			__('Unified inbox', 'alexandra-operations'),
			__('Unified inbox', 'alexandra-operations'),
			'manage_am_submissions',
			self::INBOX_SLUG,
			array(__CLASS__, 'inbox')
		);
		add_submenu_page(
			null,
			__('Follow-up queue', 'alexandra-operations'),
			__('Follow-up due', 'alexandra-operations'),
			'manage_am_submissions',
			self::FOLLOWUP_SLUG,
			array(__CLASS__, 'follow_up')
		);
		add_submenu_page(
			null,
			__('Failures and quarantine', 'alexandra-operations'),
			__('Failures & spam', 'alexandra-operations'),
			'manage_am_submissions',
			self::ISSUES_SLUG,
			array(__CLASS__, 'issues')
		);
		add_submenu_page(
			null,
			__('Submission detail', 'alexandra-operations'),
			__('Submission detail', 'alexandra-operations'),
			'manage_am_submissions',
			self::DETAIL_SLUG,
			array(__CLASS__, 'detail')
		);
	}

	/**
	 * @return void
	 */
	public static function badge() {
		global $menu;

		$metrics = (new AM_Ops_Repository())->overview_metrics();
		$count   = (int) ($metrics['new_count'] ?? 0);
		if (!$count) {
			return;
		}
		foreach ($menu as &$item) {
			if (($item[2] ?? '') === self::MENU_SLUG) {
				$item[0] .= ' <span class="awaiting-mod count-' . $count . '"><span class="pending-count">'
					. $count
					. '</span></span>';
				break;
			}
		}
		unset($item);
	}

	/**
	 * @param string $hook Admin page hook.
	 * @return void
	 */
	public static function assets($hook) {
		if (false === strpos($hook, 'am_submission')) {
			return;
		}

		$base = content_url('mu-plugins/alexandra-operations/assets/');
		wp_enqueue_style('am-ops-admin', $base . 'admin.css', array(), AM_OPS_VERSION);
		wp_enqueue_script('am-ops-admin', $base . 'admin.js', array(), AM_OPS_VERSION, true);
	}

	/**
	 * @return void
	 */
	public static function overview() {
		self::require_capability('manage_am_submissions');

		$repository = new AM_Ops_Repository();
		$metrics    = $repository->overview_metrics();
		$recent     = $repository->query(array('limit' => 10));
		$settings   = AM_Ops_Settings::get();
		$health     = self::queue_health();

		self::start_page(
			'Operations overview',
			'Live workload, follow-ups and delivery health. Every number links to the records behind it.'
		);

		echo '<section class="am-ops-kpis" aria-label="Submission workload">';
		self::metric('New', $metrics['new_count'], 'Needs first review', self::inbox_url(array('quick' => 'new')), 'new');
		self::metric('Unassigned', $metrics['unassigned'], 'No staff owner', self::inbox_url(array('quick' => 'unassigned')), 'unassigned');
		self::metric('Needs attention', $metrics['needs_attention_count'], 'Priority, due or delivery issue', self::inbox_url(array('quick' => 'attention')), 'attention');
		self::metric('Follow-up due', $metrics['follow_up_due'], 'Due now or overdue', self::inbox_url(array('quick' => 'follow_up')), 'followup');
		self::metric('High / urgent', $metrics['high_priority'], 'Active priority records', self::inbox_url(array('quick' => 'priority')), 'priority');
		self::metric('Delivery issues', $metrics['notification_issues'], 'Failed, retrying or unconfigured', self::inbox_url(array('quick' => 'failures')), 'failure');
		self::metric('Quarantined', $metrics['quarantined'], 'Suspected or quarantined', self::inbox_url(array('quick' => 'quarantine')), 'spam');
		self::metric('Active total', $metrics['active'], 'Across every form', self::inbox_url(), 'active');
		echo '</section>';

		if ('dashboard_only' !== $settings['notification_mode'] && !AM_Ops_Settings::recipient_for(AM_Ops_Repository::TYPE_CONTACT)) {
			echo '<div class="notice notice-warning inline am-ops-notice"><p><strong>Notifications need configuration.</strong> '
				. 'Records are safe in the Inbox, but email alerts are paused. Contact the site administrator.</p></div>';
		}

		echo '<div class="am-ops-overview-grid">';
		echo '<section class="am-ops-panel"><div class="am-ops-panel-head"><div><h2>Most recent</h2><p>Newest records across every form.</p></div>'
			. '<a class="button" href="' . esc_url(self::inbox_url()) . '">Open inbox</a></div>';
		self::render_table($recent['items'], false);
		echo '</section>';

		echo '<section class="am-ops-panel"><div class="am-ops-panel-head"><div><h2>Notification worker</h2><p>Durable work remains visible until sent or resolved.</p></div></div>';
		echo '<dl class="am-ops-health">';
		self::health_row('Pending jobs', $health['pending']);
		self::health_row('Retrying jobs', $health['retrying']);
		self::health_row('Permanently failed', $health['failed']);
		self::health_row('Oldest pending', $health['oldest_pending_label']);
		self::health_row('Last worker run', $health['last_worker_label']);
		self::health_row('Fallback cron', $health['cron_label']);
		echo '</dl><p><a href="' . esc_url(admin_url('admin.php?page=' . self::ISSUES_SLUG)) . '">Open failures and quarantine</a></p></section>';
		echo '</div>';

		echo '<div class="am-ops-three-grid">';
		self::breakdown_panel('By form type', $metrics['by_type'], 'type');
		self::breakdown_panel('By nursery', $metrics['by_branch'], 'branch');
		self::owner_panel($metrics['by_owner']);
		echo '</div>';

		echo '<section class="am-ops-panel am-ops-today"><h2>Today</h2><div><span><strong>'
			. (int) $metrics['received_today']
			. '</strong> received</span><span><strong>'
			. (int) $metrics['resolved_today']
			. '</strong> resolved</span></div></section>';

		self::end_page();
	}

	/**
	 * @return void
	 */
	public static function inbox() {
		self::require_capability('manage_am_submissions');
		self::render_inbox_page('', 'Submissions inbox', 'Search, filter and manage every website form in one place.');
	}

	/**
	 * @return void
	 */
	public static function follow_up() {
		self::require_capability('manage_am_submissions');
		self::render_inbox_page('follow_up', 'Follow-up due', 'Only active records whose follow-up is due now or overdue.');
	}

	/**
	 * @return void
	 */
	public static function detail() {
		self::require_capability('manage_am_submissions');

		$repository = new AM_Ops_Repository();
		$id         = absint($_GET['id'] ?? 0);
		$submission = $id ? $repository->get($id) : null;
		if (!$submission && !empty($_GET['ref'])) {
			$submission = $repository->get_by_public_reference(sanitize_text_field(wp_unslash($_GET['ref'])));
		}
		if (!$submission) {
			wp_die('Submission not found.', '', array('response' => 404));
		}

		if (!self::is_view_only()) {
			$opened = $repository->record_first_open(
				(int) $submission['id'],
				get_current_user_id(),
				true
			);
			if (!is_wp_error($opened)) {
				$submission = $opened;
			}
		}

		$file     = (new AM_Ops_Files())->for_submission((int) $submission['id']);
		$messages = $repository->messages((int) $submission['id']);
		$events   = $repository->events((int) $submission['id'], 500);

		self::start_page(
			$submission['public_ref'],
			self::type_label($submission['type']) . ' · Received ' . self::date_label($submission['submitted_at'])
		);
		echo '<p class="am-ops-back"><a href="' . esc_url(self::inbox_url()) . '">&larr; Back to unified inbox</a></p>';
		self::notice_from_query();

		echo '<div class="am-ops-detail-grid">';
		echo '<main>';
		echo '<section class="am-ops-panel"><div class="am-ops-panel-head"><div><h2>Customer-submitted details</h2><p>Immutable evidence from the website form.</p></div>'
			. self::status_badge($submission['status'])
			. '</div>';
		echo '<dl class="am-ops-fields">';
		self::field(
			AM_Ops_Repository::TYPE_AVAILABILITY === $submission['type'] ? 'Parent name' : 'Name',
			$submission['customer_name']
		);
		self::field('Email', $submission['customer_email'], 'mailto:' . $submission['customer_email']);
		self::field('Phone', $submission['customer_phone'], $submission['customer_phone'] ? 'tel:' . $submission['customer_phone'] : '');
		self::field('Nursery', $submission['branch_label']);
		self::field('Source', self::type_label($submission['type']));
		self::field('Received', self::date_label($submission['submitted_at']));
		self::field('Public reference', $submission['public_ref']);
		if ($submission['legacy_post_id']) {
			self::field('Legacy archive ID', '#' . (int) $submission['legacy_post_id']);
		}
		echo '</dl>';
		self::payload_fields($submission['payload']);
		echo '</section>';

		if ($file) {
			echo '<section class="am-ops-panel"><h2>Private CV / resume</h2><div class="am-ops-file">'
				. '<div><strong>' . esc_html($file['original_filename']) . '</strong><span>'
				. esc_html(size_format((int) $file['byte_size'], 1))
				. ' · ' . esc_html($file['mime_type'])
				. ' · ' . esc_html($file['validation_state'])
				. '</span></div>';
			if (current_user_can('view_am_private_files')) {
				echo '<a class="button button-primary" href="' . esc_url(AM_Ops_Files::download_url((int) $file['id'])) . '">Download securely</a>';
			}
			echo '</div><p class="description">The file is outside the public web root; every download is audited.</p></section>';
		}

		echo '<section class="am-ops-panel"><div class="am-ops-panel-head"><div><h2>Customer communication</h2><p>Replies are locked to '
			. esc_html($submission['customer_email'])
			. ' and sent by the durable worker.</p></div></div>';
		if (current_user_can('reply_am_submissions') && is_email($submission['customer_email'])) {
			echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="am-ops-reply-form">';
			wp_nonce_field('am_ops_queue_reply_' . (int) $submission['id']);
			echo '<input type="hidden" name="action" value="am_ops_queue_reply"><input type="hidden" name="submission_id" value="' . (int) $submission['id'] . '">';
			echo '<label>Private recipient<input type="email" value="' . esc_attr($submission['customer_email']) . '" readonly></label>';
			echo '<label>Subject<input type="text" name="subject" required maxlength="255" value="' . esc_attr('Re: ' . self::type_label($submission['type']) . ' — Alexandra Montessori') . '"></label>';
			echo '<label>Message<textarea name="message" rows="8" required maxlength="10000"></textarea></label>';
			echo '<button class="button button-primary button-hero">Queue private email</button></form>';
		}
		self::messages($messages);
		echo '</section>';

		echo '<section class="am-ops-panel"><h2>Audit timeline</h2>';
		self::timeline($events);
		echo '</section>';
		echo '</main>';

		echo '<aside><section class="am-ops-panel am-ops-sticky"><h2>Workflow</h2>';
		if (self::is_view_only()) {
			$owner = $submission['owner_user_id'] ? get_userdata((int) $submission['owner_user_id']) : null;
			echo '<dl class="am-ops-health">';
			self::health_row('Status', self::humanize($submission['status']));
			self::health_row('Priority', self::humanize($submission['priority']));
			self::health_row('Owner', $owner ? $owner->display_name : 'Unassigned');
			self::health_row('Follow-up', $submission['follow_up_at'] ? self::date_label($submission['follow_up_at']) : 'Not set');
			self::health_row('Spam review', self::humanize($submission['spam_state']));
			self::health_row('Archived', $submission['archived_at'] ? 'Yes' : 'No');
			echo '</dl>';
			if ($submission['internal_notes']) {
				echo '<h3>Internal notes</h3><p>' . nl2br(esc_html($submission['internal_notes'])) . '</p>';
			}
		} else {
			echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="am-ops-workflow-form">';
			wp_nonce_field('am_ops_update_submission_' . (int) $submission['id']);
			echo '<input type="hidden" name="action" value="am_ops_update_submission">'
				. '<input type="hidden" name="submission_id" value="' . (int) $submission['id'] . '">'
				. '<input type="hidden" name="version" value="' . (int) $submission['version'] . '">';
			self::workflow_select('Status', 'status', self::status_options(), $submission['status']);
			self::workflow_select('Priority', 'priority', self::priority_options(), $submission['priority']);
			self::workflow_select('Owner', 'owner_user_id', self::owner_options(), $submission['owner_user_id'] ?: '0');
			echo '<label>Follow-up (site time)<input type="datetime-local" name="follow_up_at" value="'
				. esc_attr(self::datetime_local($submission['follow_up_at']))
				. '"></label>';
			self::workflow_select('Spam review', 'spam_state', self::spam_options(), $submission['spam_state']);
			echo '<label>Internal notes<textarea name="internal_notes" rows="8" maxlength="20000">'
				. esc_textarea($submission['internal_notes'])
				. '</textarea></label>';
			echo '<label class="am-ops-check"><input type="checkbox" name="archived" value="1" '
				. checked((bool) $submission['archived_at'], true, false)
				. '> Archived</label>';
			echo '<button class="button button-primary button-hero">Save workflow</button></form>';
		}
		echo '<dl class="am-ops-health am-ops-detail-health">';
		self::health_row('Notification', self::humanize($submission['notification_state']));
		self::health_row('Destination', $submission['notification_destination'] ?: 'None');
		self::health_row('First opened', $submission['first_opened_at'] ? self::date_label($submission['first_opened_at']) : 'Not yet');
		self::health_row('Record version', (int) $submission['version']);
		echo '</dl></section></aside></div>';

		self::end_page();
	}

	/**
	 * @return void
	 */
	public static function issues() {
		global $wpdb;

		self::require_capability('manage_am_submissions');
		$jobs_table = AM_Ops_Tables::name(AM_Ops_Tables::JOBS);
		$jobs = $wpdb->get_results(
			"SELECT * FROM {$jobs_table}
			WHERE state IN ('failed','pending','processing')
			ORDER BY
				CASE state WHEN 'failed' THEN 0 WHEN 'processing' THEN 1 ELSE 2 END,
				next_attempt_at ASC, id ASC
			LIMIT 100",
			ARRAY_A
		);
		$quarantine = (new AM_Ops_Repository())->query(
			array('quick' => 'quarantine', 'limit' => 50)
		);

		self::start_page(
			'Failures & spam',
			'Nothing disappears silently: delayed mail, terminal failures and quarantined records stay actionable.'
		);
		self::notice_from_query();

		echo '<section class="am-ops-panel"><div class="am-ops-panel-head"><div><h2>Notification jobs</h2><p>Pending, processing and permanently failed work.</p></div></div>';
		if (!$jobs) {
			echo '<div class="am-ops-empty"><strong>No delivery work needs attention.</strong></div>';
		} else {
			echo '<div class="am-ops-table-scroll"><table class="widefat fixed striped am-ops-table"><thead><tr>'
				. '<th>Job</th><th>Submission</th><th>Recipient</th><th>State</th><th>Attempts</th><th>Next / finished</th><th>Error</th><th></th>'
				. '</tr></thead><tbody>';
			foreach ($jobs as $job) {
				echo '<tr><td>' . esc_html(self::humanize($job['job_type'])) . '<br><small>#' . (int) $job['id'] . '</small></td>';
				echo '<td>' . ((int) $job['submission_id'] ? '<a href="' . esc_url(self::detail_url((int) $job['submission_id'])) . '">#' . (int) $job['submission_id'] . '</a>' : '—') . '</td>';
				echo '<td>' . esc_html($job['recipient'] ?: '—') . '</td><td>' . self::status_badge($job['state']) . '</td>';
				echo '<td>' . (int) $job['attempts'] . ' / ' . (int) $job['max_attempts'] . '</td>';
				echo '<td>' . esc_html(self::date_label($job['finished_at'] ?: $job['next_attempt_at'])) . '</td>';
				echo '<td class="am-ops-error-cell">' . esc_html($job['last_error'] ?: '—') . '</td><td>';
				if ('failed' === $job['state'] && !self::is_view_only()) {
					echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
					wp_nonce_field('am_ops_retry_job_' . (int) $job['id']);
					echo '<input type="hidden" name="action" value="am_ops_retry_job"><input type="hidden" name="job_id" value="' . (int) $job['id'] . '">'
						. '<button class="button button-small">Retry</button></form>';
				}
				echo '</td></tr>';
			}
			echo '</tbody></table></div>';
		}
		echo '</section>';

		echo '<section class="am-ops-panel"><div class="am-ops-panel-head"><div><h2>Suspected / quarantined</h2><p>Review without deleting customer evidence.</p></div>'
			. '<a class="button" href="' . esc_url(self::inbox_url(array('quick' => 'quarantine'))) . '">Open filtered inbox</a></div>';
		self::render_table($quarantine['items'], false);
		echo '</section>';

		self::end_page();
	}

	/**
	 * @return void
	 */
	public static function settings() {
		self::require_capability('manage_am_ops_settings');

		$settings = AM_Ops_Settings::get();
		$health   = self::queue_health();
		$schema   = AM_Ops_Schema::health();
		$branches = function_exists('am_get_nurseries') ? am_get_nurseries() : array();

		self::start_page(
			'Settings & health',
			'Delivery behavior is explicit. No developer inbox or guessed client address is used.'
		);
		self::notice_from_query();
		echo '<div class="am-ops-settings-grid"><section class="am-ops-panel"><h2>Notification policy</h2>';
		echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="am-ops-settings-form">';
		wp_nonce_field('am_ops_save_settings');
		echo '<input type="hidden" name="action" value="am_ops_save_settings">';
		self::workflow_select(
			'New-submission alerts',
			'notification_mode',
			array(
				'immediate'      => 'Immediate — one alert per submission',
				'digest'         => 'Digest — grouped alerts (recommended at volume)',
				'dashboard_only' => 'Dashboard only — no new-submission email',
			),
			$settings['notification_mode']
		);
		echo '<label>Digest interval (minutes)<input type="number" name="digest_minutes" min="5" max="1440" value="' . (int) $settings['digest_minutes'] . '"></label>';
		echo '<label>Default approved recipient<input type="email" name="default_recipient" value="' . esc_attr($settings['default_recipient']) . '" placeholder="Client confirmation required"></label>';
		echo '<label>Careers approved recipient<input type="email" name="application_recipient" value="' . esc_attr($settings['application_recipient']) . '" placeholder="Falls back to approved default"></label>';
		if ($branches) {
			echo '<fieldset><legend>Nursery recipients</legend>';
			foreach ($branches as $branch) {
				$slug = sanitize_title((string) ($branch['id'] ?? ''));
				if (!$slug) {
					continue;
				}
				echo '<label>' . esc_html($branch['name'] ?? $slug)
					. '<input type="email" name="branch_recipients[' . esc_attr($slug) . ']" value="'
					. esc_attr($settings['branch_recipients'][$slug] ?? '')
					. '" placeholder="Falls back to approved default"></label>';
			}
			echo '</fieldset>';
		}
		echo '<div class="am-ops-policy-note"><strong>Retention:</strong> automatic deletion is off. A written client retention policy is required before enabling any purge.</div>';
		echo '<button class="button button-primary button-hero">Save operations settings</button></form></section>';

		echo '<section class="am-ops-panel"><h2>System health</h2><dl class="am-ops-health">';
		self::health_row('Schema', $schema['healthy'] ? 'Healthy · version ' . AM_OPS_SCHEMA_VERSION : 'Missing tables');
		self::health_row('Notification mode', self::humanize($settings['notification_mode']));
		self::health_row('Pending jobs', $health['pending']);
		self::health_row('Retrying jobs', $health['retrying']);
		self::health_row('Failed jobs', $health['failed']);
		self::health_row('Oldest pending', $health['oldest_pending_label']);
		self::health_row('Last worker run', $health['last_worker_label']);
		self::health_row('WP-Cron fallback', $health['cron_label']);
		echo '</dl><div class="am-ops-policy-note"><strong>Production cron:</strong><code>wp cron event run '
			. esc_html(AM_Ops_Queue::CRON_HOOK)
			. '</code> every minute. Host command/path must be verified before deployment.</div>'
			. '<p><a href="' . esc_url(admin_url('admin.php?page=' . self::ISSUES_SLUG)) . '">Review advanced delivery queue</a></p>'
			. '</section></div>';

		self::end_page();
	}

	/**
	 * @return void
	 */
	public static function handle_update() {
		self::require_capability('manage_am_submissions');
		$id = absint($_POST['submission_id'] ?? 0);
		check_admin_referer('am_ops_update_submission_' . $id);

		$follow_up = self::input_to_utc(wp_unslash($_POST['follow_up_at'] ?? ''));
		$changes = array(
			'status'         => wp_unslash($_POST['status'] ?? ''),
			'priority'       => wp_unslash($_POST['priority'] ?? ''),
			'owner_user_id'  => absint($_POST['owner_user_id'] ?? 0),
			'follow_up_at'   => $follow_up,
			'spam_state'     => wp_unslash($_POST['spam_state'] ?? ''),
			'internal_notes' => wp_unslash($_POST['internal_notes'] ?? ''),
			'archived'       => !empty($_POST['archived']),
		);
		$result = (new AM_Ops_Repository())->update_operational(
			$id,
			$changes,
			get_current_user_id(),
			absint($_POST['version'] ?? 0)
		);
		self::redirect_detail($id, $result, 'Workflow saved.');
	}

	/**
	 * @return void
	 */
	public static function handle_bulk() {
		self::require_capability('manage_am_submissions');
		check_admin_referer('am_ops_bulk_update');

		$ids    = array_map('absint', (array) ($_POST['submission_ids'] ?? array()));
		$action = sanitize_key($_POST['bulk_action'] ?? '');
		$changes = array();
		if ('assign_me' === $action) {
			$changes['owner_user_id'] = get_current_user_id();
		} elseif ('unassign' === $action) {
			$changes['owner_user_id'] = 0;
		} elseif (0 === strpos($action, 'status_')) {
			$changes['status'] = substr($action, 7);
		} elseif (0 === strpos($action, 'priority_')) {
			$changes['priority'] = substr($action, 9);
		} elseif ('archive' === $action) {
			$changes['archived'] = true;
		} elseif ('restore' === $action) {
			$changes['archived'] = false;
		} elseif ('quarantine' === $action) {
			$changes['spam_state'] = 'quarantined';
		} elseif ('clean' === $action) {
			$changes['spam_state'] = 'clean';
		} elseif ('follow_up' === $action) {
			$changes['follow_up_at'] = self::input_to_utc(wp_unslash($_POST['bulk_follow_up_at'] ?? ''));
		}

		if (!$ids || !$changes) {
			self::redirect_with_notice(self::inbox_url(), 'error', 'Choose records and a valid bulk action.');
		}

		$result = (new AM_Ops_Repository())->bulk_update($ids, $changes, get_current_user_id());
		$message = sprintf('%d submission(s) updated.', (int) $result['updated']);
		if ($result['errors']) {
			$message .= ' ' . count($result['errors']) . ' could not be changed.';
		}
		self::redirect_with_notice(
			wp_get_referer() ?: self::inbox_url(),
			$result['errors'] ? 'warning' : 'success',
			$message
		);
	}

	/**
	 * @return void
	 */
	public static function handle_reply() {
		self::require_capability('reply_am_submissions');
		$id = absint($_POST['submission_id'] ?? 0);
		check_admin_referer('am_ops_queue_reply_' . $id);

		$result = (new AM_Ops_Queue())->enqueue_reply(
			$id,
			wp_unslash($_POST['subject'] ?? ''),
			wp_unslash($_POST['message'] ?? ''),
			get_current_user_id()
		);
		self::redirect_detail($id, $result, 'Private reply queued.');
	}

	/**
	 * @return void
	 */
	public static function handle_retry_job() {
		global $wpdb;

		self::require_capability('manage_am_submissions');
		$id = absint($_POST['job_id'] ?? 0);
		check_admin_referer('am_ops_retry_job_' . $id);

		$updated = $wpdb->update(
			AM_Ops_Tables::name(AM_Ops_Tables::JOBS),
			array(
				'state'           => 'pending',
				'attempts'        => 0,
				'next_attempt_at' => gmdate('Y-m-d H:i:s'),
				'locked_at'       => null,
				'lock_token'      => null,
				'last_error'      => '',
				'updated_at'      => gmdate('Y-m-d H:i:s'),
				'finished_at'     => null,
			),
			array('id' => $id, 'state' => 'failed')
		);
		self::redirect_with_notice(
			admin_url('admin.php?page=' . self::ISSUES_SLUG),
			$updated ? 'success' : 'error',
			$updated ? 'Job queued for retry.' : 'Only a failed job can be retried.'
		);
	}

	/**
	 * @return void
	 */
	public static function handle_settings() {
		self::require_capability('manage_am_ops_settings');
		check_admin_referer('am_ops_save_settings');

		$mode = sanitize_key($_POST['notification_mode'] ?? 'dashboard_only');
		if (!in_array($mode, array('immediate', 'digest', 'dashboard_only'), true)) {
			$mode = 'dashboard_only';
		}
		$branches = array();
		foreach ((array) ($_POST['branch_recipients'] ?? array()) as $slug => $email) {
			$slug  = sanitize_title($slug);
			$email = strtolower(sanitize_email(wp_unslash($email)));
			if ($slug && is_email($email)) {
				$branches[$slug] = $email;
			}
		}
		update_option(
			AM_Ops_Settings::OPTION,
			array(
				'notification_mode'     => $mode,
				'digest_minutes'        => min(1440, max(5, absint($_POST['digest_minutes'] ?? 30))),
				'default_recipient'     => strtolower(sanitize_email(wp_unslash($_POST['default_recipient'] ?? ''))),
				'application_recipient' => strtolower(sanitize_email(wp_unslash($_POST['application_recipient'] ?? ''))),
				'branch_recipients'     => $branches,
				'retention_days'        => 0,
			),
			false
		);
		self::redirect_with_notice(
			admin_url('admin.php?page=' . self::SETTINGS_SLUG),
			'success',
			'Operations settings saved.'
		);
	}

	/**
	 * Stream a bounded, filtered and formula-safe CSV.
	 *
	 * @return void
	 */
	public static function handle_export() {
		self::require_capability('export_am_submissions');
		check_admin_referer('am_ops_export');

		$filters   = self::filters_from_request($_GET);
		$date_from = trim((string) ($filters['date_from'] ?? ''));
		$date_to   = trim((string) ($filters['date_to'] ?? ''));
		$from      = DateTimeImmutable::createFromFormat('!Y-m-d', $date_from);
		$to        = DateTimeImmutable::createFromFormat('!Y-m-d', $date_to);
		if (!$from || !$to || $to < $from || $from->diff($to)->days > 366) {
			wp_die('Exports require a valid date range of 366 days or less.', '', array('response' => 400));
		}

		$filename = 'alexandra-submissions-' . $date_from . '-to-' . $date_to . '.csv';
		nocache_headers();
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('X-Content-Type-Options: nosniff');
		$output = fopen('php://output', 'w');
		fwrite($output, "\xEF\xBB\xBF");
		fputcsv(
			$output,
			array(
				'Reference',
				'Received UTC',
				'Type',
				'Status',
				'Priority',
				'Owner',
				'Branch',
				'Name',
				'Email',
				'Phone',
				'Subject',
				'Submitted details (JSON)',
				'Internal notes',
				'Follow-up UTC',
				'Notification',
				'Archived UTC',
			)
		);

		$repository = new AM_Ops_Repository();
		$filters['limit'] = 100;
		unset($filters['cursor'], $filters['direction']);
		$count = 0;
		do {
			$page = $repository->query($filters);
			foreach ($page['items'] as $row) {
				$owner = $row['owner_user_id'] ? get_userdata((int) $row['owner_user_id']) : null;
				$values = array(
					$row['public_ref'],
					$row['submitted_at'],
					$row['type'],
					$row['status'],
					$row['priority'],
					$owner ? $owner->display_name : 'Unassigned',
					$row['branch_label'],
					$row['customer_name'],
					$row['customer_email'],
					$row['customer_phone'],
					$row['subject'],
					wp_json_encode($row['payload'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
					$row['internal_notes'],
					$row['follow_up_at'],
					$row['notification_state'],
					$row['archived_at'],
				);
				fputcsv($output, array_map(array(__CLASS__, 'csv_cell'), $values));
				$count++;
			}
			$filters['cursor']    = $page['older_cursor'];
			$filters['direction'] = 'older';
		} while ($page['older_cursor']);

		$repository->add_event(
			0,
			'export_performed',
			array(
				'count'     => $count,
				'date_from' => $date_from,
				'date_to'   => $date_to,
				'filters'   => array_intersect_key(
					$filters,
					array_flip(array('type', 'status', 'priority', 'branch', 'owner', 'quick'))
				),
			),
			get_current_user_id()
		);
		fclose($output);
		exit;
	}

	/**
	 * @param string $quick Default quick filter.
	 * @param string $title Page title.
	 * @param string $description Page description.
	 * @return void
	 */
	private static function render_inbox_page($quick, $title, $description) {
		$filters = self::filters_from_request($_GET);
		if ($quick && empty($filters['quick'])) {
			$filters['quick'] = $quick;
		}
		$page = (new AM_Ops_Repository())->query($filters);

		self::start_page($title, $description);
		self::notice_from_query();
		self::email_routing_guide();
		self::filter_form($filters);
		echo '<section class="am-ops-panel am-ops-inbox-panel">';
		if (self::is_view_only()) {
			self::render_table($page['items'], false);
		} else {
			echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="am-ops-bulk-form">';
			wp_nonce_field('am_ops_bulk_update');
			echo '<input type="hidden" name="action" value="am_ops_bulk_update">';
			self::bulk_toolbar();
			self::render_table($page['items'], true);
			echo '</form>';
		}
		self::pagination($filters, $page);
		echo '</section>';
		self::end_page();
	}

	/**
	 * Explain the client-editable recipient sources without exposing technical
	 * queue controls that could accidentally pause production notifications.
	 *
	 * @return void
	 */
	private static function email_routing_guide() {
		$settings = AM_Ops_Settings::get();
		$branches = function_exists('am_get_nurseries') ? am_get_nurseries() : array();
		$mode_messages = array(
			'immediate'      => 'New alerts are queued immediately.',
			'digest'         => 'New alerts are currently grouped into digest emails.',
			'dashboard_only' => 'Email alerts are paused; submissions still remain safely in this inbox.',
		);
		$mode_message = $mode_messages[$settings['notification_mode']] ?? 'Notification mode is unavailable.';

		echo '<div class="notice notice-info inline am-ops-notice"><p><strong>Email delivery &amp; routing:</strong> '
			. esc_html($mode_message)
			. ' Selected-nursery enquiries use the Email saved on that Nursery; enquiries without a nursery use Site Settings &gt; Email.</p>';
		echo '<details><summary>View current recipients and where to edit them</summary><ul>';
		foreach ($branches as $branch) {
			$slug = sanitize_title((string) ($branch['id'] ?? ''));
			if (!$slug) {
				continue;
			}
			$recipient = AM_Ops_Settings::recipient_for(AM_Ops_Repository::TYPE_AVAILABILITY, $slug);
			echo '<li><strong>' . esc_html((string) ($branch['name'] ?? $slug)) . ':</strong> <code>'
				. esc_html($recipient ?: 'Not configured')
				. '</code></li>';
		}
		$general_recipient = AM_Ops_Settings::recipient_for(AM_Ops_Repository::TYPE_AVAILABILITY);
		echo '<li><strong>General / no nursery selected:</strong> <code>'
			. esc_html($general_recipient ?: 'Not configured')
			. '</code></li></ul>';
		echo '<p><a class="button" href="' . esc_url(admin_url('edit.php?post_type=am_nursery'))
			. '">Manage nursery emails</a> <a class="button" href="'
			. esc_url(admin_url('admin.php?page=am_settings'))
			. '">Manage main email</a></p></details>';
		echo '<p><small><strong>Delivery status:</strong> Sent means WordPress handed the message to its mail transport; inbox placement can still be delayed or spam-filtered.</small></p></div>';
	}

	/**
	 * @param array<string,mixed> $filters Current filters.
	 * @return void
	 */
	private static function filter_form($filters) {
		$owners   = self::owner_options();
		unset($owners['0']);
		$branches = function_exists('am_get_nurseries') ? am_get_nurseries() : array();
		$advanced_open = false;
		foreach (array('owner', 'priority', 'branch', 'date_from', 'date_to') as $advanced_key) {
			if ('' !== (string) ($filters[$advanced_key] ?? '')) {
				$advanced_open = true;
				break;
			}
		}
		if ('active' !== (string) ($filters['archived'] ?? 'active')) {
			$advanced_open = true;
		}
		echo '<form method="get" action="' . esc_url(admin_url('admin.php')) . '" class="am-ops-filters">';
		echo '<input type="hidden" name="page" value="' . esc_attr(sanitize_key($_GET['page'] ?? self::MENU_SLUG)) . '">';
		echo '<div class="am-ops-filter-primary">';
		echo '<label class="am-ops-search"><span>Search</span><input type="search" name="search" value="' . esc_attr($filters['search'] ?? '') . '" placeholder="Reference, exact email/phone, or name prefix"></label>';
		self::filter_select('Type', 'type', array('' => 'All forms') + self::type_options(), $filters['type'] ?? '');
		self::filter_select('Status', 'status', array('' => 'All statuses') + self::status_options(), $filters['status'] ?? '');
		self::filter_select(
			'Action queue',
			'quick',
			array(
				''           => 'Everything',
				'new'        => 'New',
				'unassigned' => 'Unassigned',
				'attention'  => 'Needs attention',
				'follow_up'  => 'Follow-up due',
				'priority'   => 'High / urgent',
				'failures'   => 'Delivery issues',
				'quarantine' => 'Suspected / quarantined',
			),
			$filters['quick'] ?? ''
		);
		echo '</div>';
		echo '<details class="am-ops-advanced-filters"' . ($advanced_open ? ' open' : '') . '><summary>More filters &amp; export</summary><div class="am-ops-filter-advanced">';
		self::filter_select('Owner', 'owner', array('' => 'All owners', 'unassigned' => 'Unassigned') + $owners, $filters['owner'] ?? '');
		self::filter_select('Priority', 'priority', array('' => 'All priorities') + self::priority_options(), $filters['priority'] ?? '');
		$branch_options = array('' => 'All nurseries');
		foreach ($branches as $branch) {
			$slug = sanitize_title((string) ($branch['id'] ?? ''));
			if ($slug) {
				$branch_options[$slug] = $branch['name'] ?? $slug;
			}
		}
		self::filter_select('Nursery', 'branch', $branch_options, $filters['branch'] ?? '');
		self::filter_select(
			'Archive',
			'archived',
			array('active' => 'Active only', 'archived' => 'Archived only', 'all' => 'Active + archived'),
			$filters['archived'] ?? 'active'
		);
		echo '<label><span>From</span><input type="date" name="date_from" value="' . esc_attr($filters['date_from'] ?? '') . '"></label>';
		echo '<label><span>To</span><input type="date" name="date_to" value="' . esc_attr($filters['date_to'] ?? '') . '"></label>';
		echo '</div></details>';
		echo '<div class="am-ops-filter-actions"><button class="button button-primary">Apply filters</button><a class="button" href="' . esc_url(self::inbox_url()) . '">Clear</a>';
		if (!empty($filters['date_from']) && !empty($filters['date_to']) && current_user_can('export_am_submissions')) {
			$export_args = array_merge(
				array('action' => 'am_ops_export'),
				array_intersect_key(
					$filters,
					array_flip(array('search', 'type', 'status', 'owner', 'priority', 'branch', 'quick', 'archived', 'date_from', 'date_to'))
				)
			);
			$export_url = wp_nonce_url(
				add_query_arg($export_args, admin_url('admin-post.php')),
				'am_ops_export'
			);
			echo '<a class="button" href="' . esc_url($export_url) . '">Export filtered CSV</a>';
		} elseif (current_user_can('export_am_submissions')) {
			echo '<span class="am-ops-export-hint">Set From and To dates to export.</span>';
		}
		echo '</div></form>';
	}

	/**
	 * @param array<int,array<string,mixed>> $items Rows.
	 * @param bool                           $checkboxes Show bulk selectors.
	 * @return void
	 */
	private static function render_table($items, $checkboxes) {
		if (!$items) {
			echo '<div class="am-ops-empty"><strong>No submissions match this view.</strong><span>Change the filters or return to the newest records.</span></div>';
			return;
		}

		echo '<div class="am-ops-table-scroll"><table class="widefat fixed striped am-ops-table"><thead><tr>';
		if ($checkboxes) {
			echo '<td class="check-column"><input type="checkbox" class="am-ops-select-all" aria-label="Select all on this page"></td>';
		}
		echo '<th>Received</th><th>Reference / form</th><th>Person</th><th>Nursery</th><th>Owner</th><th>Priority</th><th>Status</th><th>Follow-up</th><th>Delivery</th></tr></thead><tbody>';
		foreach ($items as $row) {
			$owner = $row['owner_user_id'] ? get_userdata((int) $row['owner_user_id']) : null;
			echo '<tr class="' . esc_attr($row['archived_at'] ? 'is-archived' : '') . '">';
			if ($checkboxes) {
				echo '<th class="check-column"><input type="checkbox" name="submission_ids[]" value="' . (int) $row['id'] . '" aria-label="Select ' . esc_attr($row['public_ref']) . '"></th>';
			}
			echo '<td><span class="am-ops-date">' . esc_html(self::date_label($row['submitted_at'])) . '</span></td>';
			echo '<td><a class="am-ops-ref" href="' . esc_url(self::detail_url((int) $row['id'])) . '">' . esc_html($row['public_ref']) . '</a><small>'
				. esc_html(self::type_label($row['type']))
				. '</small></td>';
			echo '<td><strong>' . esc_html($row['customer_name'] ?: 'Unnamed') . '</strong><a href="mailto:' . esc_attr($row['customer_email']) . '">' . esc_html($row['customer_email']) . '</a></td>';
			echo '<td>' . esc_html($row['branch_label'] ?: '—') . '</td>';
			echo '<td>' . esc_html($owner ? $owner->display_name : 'Unassigned') . '</td>';
			echo '<td>' . self::priority_badge($row['priority']) . '</td>';
			echo '<td>' . self::status_badge($row['status']) . '</td>';
			echo '<td>' . ($row['follow_up_at'] ? esc_html(self::date_label($row['follow_up_at'])) : '—') . '</td>';
			echo '<td>' . self::status_badge($row['notification_state']) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	/**
	 * @return void
	 */
	private static function bulk_toolbar() {
		echo '<div class="am-ops-bulk-toolbar"><select name="bulk_action" aria-label="Bulk action">'
			. '<option value="">Bulk action</option>'
			. '<option value="assign_me">Assign to me</option><option value="unassign">Unassign</option>'
			. '<optgroup label="Status">';
		foreach (self::status_options() as $value => $label) {
			echo '<option value="status_' . esc_attr($value) . '">' . esc_html($label) . '</option>';
		}
		echo '</optgroup><optgroup label="Priority">';
		foreach (self::priority_options() as $value => $label) {
			echo '<option value="priority_' . esc_attr($value) . '">' . esc_html($label) . '</option>';
		}
		echo '</optgroup><option value="follow_up">Set follow-up</option><option value="quarantine">Quarantine</option><option value="clean">Mark clean</option>'
			. '<option value="archive">Archive resolved/closed</option><option value="restore">Restore archived</option></select>'
			. '<input type="datetime-local" name="bulk_follow_up_at" aria-label="Bulk follow-up date">'
			. '<button class="button">Apply to selected</button><span>Maximum 100 records per operation.</span></div>';
	}

	/**
	 * @param array<string,mixed> $filters Filters.
	 * @param array<string,mixed> $page Query page.
	 * @return void
	 */
	private static function pagination($filters, $page) {
		echo '<nav class="am-ops-pagination" aria-label="Inbox pagination">';
		$base = array_filter(
			array_intersect_key(
				$filters,
				array_flip(array('search', 'type', 'status', 'owner', 'priority', 'branch', 'quick', 'archived', 'date_from', 'date_to'))
			),
			static function ($value) {
				return '' !== (string) $value;
			}
		);
		$base['page'] = sanitize_key($_GET['page'] ?? self::MENU_SLUG);
		if ($page['newer_cursor']) {
			echo '<a class="button" href="' . esc_url(add_query_arg(array_merge($base, array('cursor' => $page['newer_cursor'], 'direction' => 'newer')), admin_url('admin.php'))) . '">&larr; Newer</a>';
		} else {
			echo '<span></span>';
		}
		echo '<a href="' . esc_url(add_query_arg(array('page' => $base['page']), admin_url('admin.php'))) . '">Newest records</a>';
		if ($page['older_cursor']) {
			echo '<a class="button" href="' . esc_url(add_query_arg(array_merge($base, array('cursor' => $page['older_cursor'], 'direction' => 'older')), admin_url('admin.php'))) . '">Older &rarr;</a>';
		} else {
			echo '<span></span>';
		}
		echo '</nav>';
	}

	/**
	 * @param array<string,mixed> $request Request array.
	 * @return array<string,mixed>
	 */
	private static function filters_from_request($request) {
		$keys = array(
			'search',
			'type',
			'status',
			'owner',
			'priority',
			'branch',
			'quick',
			'archived',
			'date_from',
			'date_to',
			'cursor',
			'direction',
		);
		$filters = array();
		foreach ($keys as $key) {
			if (isset($request[$key]) && !is_array($request[$key])) {
				$filters[$key] = sanitize_text_field(wp_unslash($request[$key]));
			}
		}
		$filters['limit'] = 50;

		return $filters;
	}

	/**
	 * @param array<string,mixed> $payload Stored payload.
	 * @return void
	 */
	private static function payload_fields($payload) {
		$labels = array(
			'first_name'           => 'First name',
			'last_name'            => 'Last name',
			'message'              => 'Message',
			'preferred_visit_date' => 'Preferred visit date',
			'child_name'           => 'Child name',
			'child_age'            => 'Child age',
			'desired_start_date'   => 'Desired start date',
			'end_date'             => 'End date',
			'days'                 => 'Days',
			'session_type'         => 'Session type',
			'start_time'           => 'Start time',
			'end_time'             => 'End time',
			'sessions'             => 'Session summary',
			'qualification'        => 'Qualification',
			'position'             => 'Position',
			'job_slug'             => 'Job reference',
			'job_title'            => 'Job title',
			'legacy_cv_name'       => 'Original CV name',
		);
		$visible = array_intersect_key($payload, $labels);
		if (!$visible) {
			return;
		}
		echo '<h3>Form-specific answers</h3><dl class="am-ops-fields">';
		foreach ($visible as $key => $value) {
			if ('' === (string) $value) {
				continue;
			}
			self::field($labels[$key], is_scalar($value) ? (string) $value : wp_json_encode($value));
		}
		echo '</dl>';
	}

	/**
	 * @param array<int,array<string,mixed>> $messages Messages.
	 * @return void
	 */
	private static function messages($messages) {
		if (!$messages) {
			echo '<div class="am-ops-empty am-ops-empty-small"><span>No outbound replies recorded.</span></div>';
			return;
		}
		echo '<div class="am-ops-messages">';
		foreach ($messages as $message) {
			$author = $message['actor_user_id'] ? get_userdata((int) $message['actor_user_id']) : null;
			echo '<article><header><div><strong>' . esc_html($message['subject']) . '</strong><span>'
				. esc_html(self::date_label($message['created_at']))
				. ' · ' . esc_html($author ? $author->display_name : 'System')
				. '</span></div>' . self::status_badge($message['status']) . '</header>'
				. '<p>' . nl2br(esc_html($message['message_body'])) . '</p>';
			if ($message['last_error']) {
				echo '<div class="am-ops-message-error">' . esc_html($message['last_error']) . '</div>';
			}
			echo '</article>';
		}
		echo '</div>';
	}

	/**
	 * @param array<int,array<string,mixed>> $events Events.
	 * @return void
	 */
	private static function timeline($events) {
		if (!$events) {
			echo '<div class="am-ops-empty am-ops-empty-small"><span>No events recorded.</span></div>';
			return;
		}
		echo '<ol class="am-ops-timeline">';
		foreach (array_reverse($events) as $event) {
			$actor = $event['actor_user_id'] ? get_userdata((int) $event['actor_user_id']) : null;
			echo '<li><span class="am-ops-timeline-dot"></span><div><strong>'
				. esc_html(self::humanize($event['event_type']))
				. '</strong><span>' . esc_html(self::date_label($event['created_at']))
				. ' · ' . esc_html($actor ? $actor->display_name : 'System')
				. '</span>';
			if ($event['details']) {
				echo '<small>' . esc_html(self::event_summary($event['details'])) . '</small>';
			}
			echo '</div></li>';
		}
		echo '</ol>';
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function queue_health() {
		global $wpdb;

		$table = AM_Ops_Tables::name(AM_Ops_Tables::JOBS);
		$row   = $wpdb->get_row(
			"SELECT
				SUM(state = 'pending') AS pending,
				SUM(state = 'pending' AND attempts > 0) AS retrying,
				SUM(state = 'failed') AS failed,
				MIN(CASE WHEN state = 'pending' THEN created_at ELSE NULL END) AS oldest_pending
			FROM {$table}",
			ARRAY_A
		);
		$last = get_option(AM_Ops_Queue::LAST_WORKER_OPTION, array());
		$next = wp_next_scheduled(AM_Ops_Queue::CRON_HOOK);

		return array(
			'pending'              => (int) ($row['pending'] ?? 0),
			'retrying'             => (int) ($row['retrying'] ?? 0),
			'failed'               => (int) ($row['failed'] ?? 0),
			'oldest_pending_label' => !empty($row['oldest_pending']) ? self::date_label($row['oldest_pending']) : 'None',
			'last_worker_label'    => !empty($last['finished_at']) ? self::date_label(gmdate('Y-m-d H:i:s', strtotime($last['finished_at']))) : 'Not recorded',
			'cron_label'           => $next ? 'Scheduled · ' . wp_date('d M H:i', $next) : 'Not scheduled',
		);
	}

	/**
	 * @param string $title Title.
	 * @param string $description Description.
	 * @return void
	 */
	private static function start_page($title, $description) {
		echo '<div class="wrap am-ops-wrap"><div class="am-ops-title"><div><h1>'
			. esc_html($title)
			. '</h1><p>' . esc_html($description) . '</p></div><span class="am-ops-live">Operations</span></div>';
	}

	/**
	 * @return void
	 */
	private static function end_page() {
		echo '</div>';
	}

	/**
	 * @param string $label Label.
	 * @param int    $value Value.
	 * @param string $description Description.
	 * @param string $url Link.
	 * @param string $tone Tone.
	 * @return void
	 */
	private static function metric($label, $value, $description, $url, $tone) {
		echo '<a class="am-ops-kpi tone-' . esc_attr($tone) . '" href="' . esc_url($url) . '"><span>'
			. esc_html($label)
			. '</span><strong>' . number_format_i18n((int) $value) . '</strong><small>'
			. esc_html($description)
			. '</small></a>';
	}

	/**
	 * @param string $title Panel title.
	 * @param array  $rows Rows.
	 * @param string $kind type or branch.
	 * @return void
	 */
	private static function breakdown_panel($title, $rows, $kind) {
		echo '<section class="am-ops-panel"><h2>' . esc_html($title) . '</h2><div class="am-ops-breakdown">';
		if (!$rows) {
			echo '<p>No records yet.</p>';
		}
		foreach ($rows as $row) {
			$key   = 'type' === $kind ? $row['type'] : $row['branch_slug'];
			$label = 'type' === $kind ? self::type_label($row['type']) : ($row['branch_label'] ?: $row['branch_slug']);
			echo '<a href="' . esc_url(self::inbox_url(array($kind => $key))) . '"><span><strong>'
				. esc_html($label)
				. '</strong><small>' . (int) ($row['new_count'] ?? 0) . ' new</small></span><b>'
				. (int) ($row['active'] ?? 0)
				. '</b></a>';
		}
		echo '</div></section>';
	}

	/**
	 * @param array $rows Owner metrics.
	 * @return void
	 */
	private static function owner_panel($rows) {
		echo '<section class="am-ops-panel"><h2>Owner workload</h2><div class="am-ops-breakdown">';
		foreach ($rows as $row) {
			$user  = $row['owner_user_id'] ? get_userdata((int) $row['owner_user_id']) : null;
			$label = $user ? $user->display_name : 'Unassigned';
			$owner = $user ? (string) $user->ID : 'unassigned';
			echo '<a href="' . esc_url(self::inbox_url(array('owner' => $owner))) . '"><span><strong>'
				. esc_html($label)
				. '</strong><small>' . (int) $row['due'] . ' due</small></span><b>'
				. (int) $row['active']
				. '</b></a>';
		}
		echo '</div></section>';
	}

	/**
	 * @param string $label Label.
	 * @param mixed  $value Value.
	 * @return void
	 */
	private static function health_row($label, $value) {
		echo '<div><dt>' . esc_html($label) . '</dt><dd>' . esc_html((string) $value) . '</dd></div>';
	}

	/**
	 * @param string $label Label.
	 * @param string $value Value.
	 * @param string $href Optional href.
	 * @return void
	 */
	private static function field($label, $value, $href = '') {
		echo '<div><dt>' . esc_html($label) . '</dt><dd>';
		if ($href) {
			echo '<a href="' . esc_url($href) . '">' . nl2br(esc_html((string) ($value ?: '—'))) . '</a>';
		} else {
			echo nl2br(esc_html((string) ($value ?: '—')));
		}
		echo '</dd></div>';
	}

	/**
	 * @param string $label Label.
	 * @param string $name Field name.
	 * @param array  $options Options.
	 * @param mixed  $selected Selected.
	 * @return void
	 */
	private static function workflow_select($label, $name, $options, $selected) {
		echo '<label>' . esc_html($label) . '<select name="' . esc_attr($name) . '">';
		foreach ($options as $value => $option_label) {
			echo '<option value="' . esc_attr($value) . '" '
				. selected((string) $selected, (string) $value, false)
				. '>' . esc_html($option_label) . '</option>';
		}
		echo '</select></label>';
	}

	/**
	 * @param string $label Label.
	 * @param string $name Name.
	 * @param array  $options Options.
	 * @param mixed  $selected Selected.
	 * @return void
	 */
	private static function filter_select($label, $name, $options, $selected) {
		echo '<label><span>' . esc_html($label) . '</span><select name="' . esc_attr($name) . '">';
		foreach ($options as $value => $option_label) {
			echo '<option value="' . esc_attr($value) . '" '
				. selected((string) $selected, (string) $value, false)
				. '>' . esc_html($option_label) . '</option>';
		}
		echo '</select></label>';
	}

	/**
	 * @return array<string,string>
	 */
	private static function type_options() {
		return array(
			'contact'      => 'Contact',
			'visit'        => 'Visit',
			'availability' => 'Availability',
			'application'  => 'Careers',
		);
	}

	/**
	 * @return array<string,string>
	 */
	private static function status_options() {
		return array(
			'new'         => 'New',
			'in_progress' => 'In progress',
			'waiting'     => 'Waiting',
			'replied'     => 'Replied',
			'resolved'    => 'Resolved',
			'closed'      => 'Closed',
		);
	}

	/**
	 * @return array<string,string>
	 */
	private static function priority_options() {
		return array('low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent');
	}

	/**
	 * @return array<string,string>
	 */
	private static function spam_options() {
		return array('clean' => 'Clean', 'suspected' => 'Suspected', 'quarantined' => 'Quarantined');
	}

	/**
	 * @return array<string,string>
	 */
	private static function owner_options() {
		$options = array('0' => 'Unassigned');
		$users = get_users(
			array(
				'role__in' => array('administrator', 'am_content_manager'),
				'orderby'  => 'display_name',
				'order'    => 'ASC',
			)
		);
		foreach ($users as $user) {
			$options[(string) $user->ID] = $user->display_name;
		}

		return $options;
	}

	/**
	 * @param string $type Type.
	 * @return string
	 */
	private static function type_label($type) {
		return self::type_options()[$type] ?? self::humanize($type);
	}

	/**
	 * @param string $value Value.
	 * @return string
	 */
	private static function humanize($value) {
		return ucwords(str_replace('_', ' ', (string) $value));
	}

	/**
	 * @param string $status Status.
	 * @return string
	 */
	private static function status_badge($status) {
		return '<span class="am-ops-badge status-' . esc_attr(sanitize_html_class($status)) . '">'
			. esc_html(self::humanize($status))
			. '</span>';
	}

	/**
	 * @param string $priority Priority.
	 * @return string
	 */
	private static function priority_badge($priority) {
		return '<span class="am-ops-priority priority-' . esc_attr(sanitize_html_class($priority)) . '">'
			. esc_html(self::humanize($priority))
			. '</span>';
	}

	/**
	 * @param string|null $utc UTC datetime.
	 * @return string
	 */
	private static function date_label($utc) {
		if (!$utc) {
			return '—';
		}

		return get_date_from_gmt((string) $utc, 'd M Y, H:i');
	}

	/**
	 * @param string|null $utc UTC datetime.
	 * @return string
	 */
	private static function datetime_local($utc) {
		return $utc ? get_date_from_gmt((string) $utc, 'Y-m-d\TH:i') : '';
	}

	/**
	 * @param string $input Site-time datetime-local.
	 * @return string|null
	 */
	private static function input_to_utc($input) {
		$input = trim((string) $input);
		if ('' === $input) {
			return null;
		}
		if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $input)) {
			return null;
		}

		return get_gmt_from_date(str_replace('T', ' ', $input) . ':00', 'Y-m-d H:i:s');
	}

	/**
	 * @param array<string,mixed> $details Details.
	 * @return string
	 */
	private static function event_summary($details) {
		$parts = array();
		foreach ($details as $key => $value) {
			if (is_scalar($value) && '' !== (string) $value && 'destination' !== $key) {
				$parts[] = self::humanize($key) . ': ' . (string) $value;
			}
		}

		return implode(' · ', array_slice($parts, 0, 4));
	}

	/**
	 * @param array<string,mixed> $args Query args.
	 * @return string
	 */
	private static function inbox_url($args = array()) {
		return add_query_arg(
			array_merge(array('page' => self::MENU_SLUG), $args),
			admin_url('admin.php')
		);
	}

	/**
	 * @param int $id Submission ID.
	 * @return string
	 */
	private static function detail_url($id) {
		return add_query_arg(
			array('page' => self::DETAIL_SLUG, 'id' => (int) $id),
			admin_url('admin.php')
		);
	}

	/**
	 * @param string $capability Capability.
	 * @return void
	 */
	private static function require_capability($capability) {
		if (!current_user_can($capability)) {
			wp_die('Access denied.', '', array('response' => 403));
		}
	}

	/**
	 * Whether the current role is the section-allocated, read-only Viewer.
	 *
	 * Kept behind a function_exists check so the operations plugin remains
	 * independent when the theme access layer is unavailable.
	 *
	 * @return bool
	 */
	private static function is_view_only() {
		return function_exists('am_is_viewer') && am_is_viewer();
	}

	/**
	 * @param int            $id Submission.
	 * @param mixed          $result Result or error.
	 * @param string         $success Success message.
	 * @return void
	 */
	private static function redirect_detail($id, $result, $success) {
		$type    = is_wp_error($result) ? 'error' : 'success';
		$message = is_wp_error($result) ? $result->get_error_message() : $success;
		self::redirect_with_notice(self::detail_url($id), $type, $message);
	}

	/**
	 * @param string $url URL.
	 * @param string $type Notice type.
	 * @param string $message Message.
	 * @return void
	 */
	private static function redirect_with_notice($url, $type, $message) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'am_ops_notice'      => sanitize_key($type),
					'am_ops_notice_text' => rawurlencode($message),
				),
				$url
			)
		);
		exit;
	}

	/**
	 * @return void
	 */
	private static function notice_from_query() {
		$type = sanitize_key($_GET['am_ops_notice'] ?? '');
		$text = isset($_GET['am_ops_notice_text'])
			? rawurldecode(sanitize_text_field(wp_unslash($_GET['am_ops_notice_text'])))
			: '';
		if (!$text || !in_array($type, array('success', 'error', 'warning'), true)) {
			return;
		}
		echo '<div class="notice notice-' . esc_attr($type) . ' inline am-ops-notice"><p>'
			. esc_html($text)
			. '</p></div>';
	}

	/**
	 * Neutralize spreadsheet formulas and control characters.
	 *
	 * @param mixed $value CSV cell.
	 * @return string
	 */
	public static function csv_cell($value) {
		$value = str_replace(array("\0", "\r"), array('', ''), (string) $value);
		if (preg_match('/^[\s]*[=+\-@]/', $value)) {
			$value = "'" . $value;
		}

		return $value;
	}
}
