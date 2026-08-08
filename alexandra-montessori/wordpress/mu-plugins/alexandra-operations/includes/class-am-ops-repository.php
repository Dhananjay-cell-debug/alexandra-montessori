<?php
/**
 * Durable submission repository.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_Ops_Repository {
	const TYPE_CONTACT      = 'contact';
	const TYPE_VISIT        = 'visit';
	const TYPE_AVAILABILITY = 'availability';
	const TYPE_APPLICATION  = 'application';

	/**
	 * Allowed workflow values are deliberately centralized.
	 *
	 * @return string[]
	 */
	public static function types() {
		return array(
			self::TYPE_CONTACT,
			self::TYPE_VISIT,
			self::TYPE_AVAILABILITY,
			self::TYPE_APPLICATION,
		);
	}

	/**
	 * @return string[]
	 */
	public static function statuses() {
		return array('new', 'in_progress', 'waiting', 'replied', 'resolved', 'closed');
	}

	/**
	 * @return string[]
	 */
	public static function priorities() {
		return array('low', 'normal', 'high', 'urgent');
	}

	/**
	 * @return string[]
	 */
	public static function spam_states() {
		return array('clean', 'suspected', 'quarantined');
	}

	/**
	 * Create a submission and its creation event in one transaction.
	 *
	 * Supplying a previously used idempotency key returns the original record
	 * with `created` false. Customer fields are only written here; later update
	 * methods are restricted to operational fields.
	 *
	 * @param array<string,mixed> $data Normalized submission data.
	 * @return array{created:bool,submission:array<string,mixed>}|WP_Error
	 */
	public function create($data) {
		global $wpdb;

		if (!AM_Ops_Schema::tables_exist()) {
			return new WP_Error(
				'am_ops_schema_unavailable',
				__('The submissions database is not available.', 'alexandra-operations'),
				array('status' => 503)
			);
		}

		$normalized = $this->normalize_create_data($data);
		if (is_wp_error($normalized)) {
			return $normalized;
		}

		if ($normalized['idempotency_key']) {
			$existing = $this->get_by_idempotency_hash($normalized['idempotency_key']);
			if ($existing) {
				return array(
					'created'    => false,
					'submission' => $existing,
				);
			}
		}

		$table        = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
		$events_table = AM_Ops_Tables::name(AM_Ops_Tables::EVENTS);

		for ($attempt = 0; $attempt < 5; $attempt++) {
			$normalized['public_ref'] = self::generate_public_reference();
			$wpdb->query('START TRANSACTION');

			$inserted = $wpdb->insert($table, $normalized);
			if (false === $inserted) {
				$wpdb->query('ROLLBACK');

				if ($normalized['idempotency_key']) {
					$existing = $this->get_by_idempotency_hash($normalized['idempotency_key']);
					if ($existing) {
						return array(
							'created'    => false,
							'submission' => $existing,
						);
					}
				}

				if (false !== stripos((string) $wpdb->last_error, 'public_ref')) {
					continue;
				}

				return $this->database_error('am_ops_submission_insert_failed');
			}

			$submission_id = (int) $wpdb->insert_id;
			$event_details = wp_json_encode(
				array(
					'source'         => $normalized['source'],
					'type'           => $normalized['type'],
					'legacy_post_id' => $normalized['legacy_post_id'],
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			);

			$event_inserted = $wpdb->insert(
				$events_table,
				array(
					'submission_id' => $submission_id,
					'actor_user_id' => $data['actor_user_id'] ?? null,
					'event_type'    => !empty($normalized['legacy_post_id']) ? 'submission_migrated' : 'submission_created',
					'details'       => $event_details ?: '{}',
					'ip_hash'       => $normalized['ip_hash'],
					'created_at'    => $normalized['submitted_at'],
				)
			);

			if (false === $event_inserted) {
				$wpdb->query('ROLLBACK');
				return $this->database_error('am_ops_event_insert_failed');
			}

			$wpdb->query('COMMIT');
			delete_transient('am_ops_overview_metrics');

			return array(
				'created'    => true,
				'submission' => $this->get($submission_id),
			);
		}

		return new WP_Error(
			'am_ops_reference_collision',
			__('A unique submission reference could not be generated.', 'alexandra-operations'),
			array('status' => 500)
		);
	}

	/**
	 * Read one submission by numeric ID.
	 *
	 * @param int $id Submission ID.
	 * @return array<string,mixed>|null
	 */
	public function get($id) {
		global $wpdb;

		$table = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
		$row   = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", (int) $id),
			ARRAY_A
		);

		return $row ? $this->hydrate_row($row) : null;
	}

	/**
	 * Read one submission by its safe public reference.
	 *
	 * @param string $reference Public reference.
	 * @return array<string,mixed>|null
	 */
	public function get_by_public_reference($reference) {
		global $wpdb;

		$table = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE public_ref = %s LIMIT 1",
				strtoupper(trim((string) $reference))
			),
			ARRAY_A
		);

		return $row ? $this->hydrate_row($row) : null;
	}

	/**
	 * Read the record associated with a raw browser retry token.
	 *
	 * @param string $key Raw browser idempotency token.
	 * @return array<string,mixed>|null
	 */
	public function get_by_idempotency_key($key) {
		$hash = self::idempotency_hash($key);

		return $hash ? $this->get_by_idempotency_hash($hash) : null;
	}

	/**
	 * Read a migrated record by its immutable legacy post ID.
	 *
	 * @param int $post_id Legacy WordPress post ID.
	 * @return array<string,mixed>|null
	 */
	public function get_by_legacy_post_id($post_id) {
		global $wpdb;

		$table = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
		$row   = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table} WHERE legacy_post_id = %d LIMIT 1", (int) $post_id),
			ARRAY_A
		);

		return $row ? $this->hydrate_row($row) : null;
	}

	/**
	 * Find a recent identical submission for duplicate handling.
	 *
	 * @param string $fingerprint Precomputed duplicate fingerprint.
	 * @param int    $window_seconds Look-back window.
	 * @return array<string,mixed>|null
	 */
	public function find_recent_duplicate($fingerprint, $window_seconds = 600) {
		global $wpdb;

		$fingerprint = strtolower(trim((string) $fingerprint));
		if (!preg_match('/^[a-f0-9]{64}$/', $fingerprint)) {
			return null;
		}

		$table  = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
		$cutoff = gmdate('Y-m-d H:i:s', time() - max(1, (int) $window_seconds));
		$row    = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE duplicate_fingerprint = %s
				AND submitted_at >= %s
				ORDER BY submitted_at DESC, id DESC
				LIMIT 1",
				$fingerprint,
				$cutoff
			),
			ARRAY_A
		);

		return $row ? $this->hydrate_row($row) : null;
	}

	/**
	 * Atomically record the first staff opening and optionally claim it.
	 *
	 * @param int  $submission_id Submission ID.
	 * @param int  $user_id Staff user ID.
	 * @param bool $claim_unassigned Whether to claim an unassigned record.
	 * @return array<string,mixed>|WP_Error
	 */
	public function record_first_open($submission_id, $user_id, $claim_unassigned = true) {
		global $wpdb;

		$table = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
		$now   = gmdate('Y-m-d H:i:s');
		$sql   = "UPDATE {$table}
			SET first_opened_at = %s,
				first_opened_by = %d,
				owner_user_id = CASE
					WHEN %d = 1 AND owner_user_id IS NULL THEN %d
					ELSE owner_user_id
				END,
				status = CASE WHEN status = 'new' THEN 'in_progress' ELSE status END,
				updated_at = %s,
				version = version + 1
			WHERE id = %d AND first_opened_at IS NULL";

		$wpdb->query('START TRANSACTION');
		$updated = $wpdb->query(
			$wpdb->prepare(
				$sql,
				$now,
				(int) $user_id,
				$claim_unassigned ? 1 : 0,
				(int) $user_id,
				$now,
				(int) $submission_id
			)
		);

		if (false === $updated) {
			$wpdb->query('ROLLBACK');
			return $this->database_error('am_ops_first_open_failed');
		}

		if ($updated) {
			$event = $this->add_event(
				$submission_id,
				'first_opened',
				array('claimed' => (bool) $claim_unassigned),
				$user_id
			);
			if (is_wp_error($event)) {
				$wpdb->query('ROLLBACK');
				return $event;
			}
		}

		$wpdb->query('COMMIT');
		delete_transient('am_ops_overview_metrics');

		$submission = $this->get($submission_id);
		if (!$submission) {
			return new WP_Error(
				'am_ops_submission_not_found',
				__('Submission not found.', 'alexandra-operations'),
				array('status' => 404)
			);
		}

		return $submission;
	}

	/**
	 * Append an immutable audit event.
	 *
	 * @param int                 $submission_id Submission ID.
	 * @param string              $event_type Event type.
	 * @param array<string,mixed> $details Safe event details.
	 * @param int|null            $actor_user_id Staff actor.
	 * @param string              $ip_hash Optional HMAC IP hash.
	 * @return int|WP_Error Inserted event ID.
	 */
	public function add_event($submission_id, $event_type, $details = array(), $actor_user_id = null, $ip_hash = '') {
		global $wpdb;

		$event_type = sanitize_key($event_type);
		if ('' === $event_type) {
			return new WP_Error('am_ops_invalid_event', __('Invalid audit event.', 'alexandra-operations'));
		}

		$encoded = wp_json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$inserted = $wpdb->insert(
			AM_Ops_Tables::name(AM_Ops_Tables::EVENTS),
			array(
				'submission_id' => (int) $submission_id,
				'actor_user_id' => $actor_user_id ? (int) $actor_user_id : null,
				'event_type'    => $event_type,
				'details'       => $encoded ?: '{}',
				'ip_hash'       => self::normalize_hash($ip_hash),
				'created_at'    => gmdate('Y-m-d H:i:s'),
			)
		);

		if (false === $inserted) {
			return $this->database_error('am_ops_event_insert_failed');
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Return an immutable chronological audit timeline.
	 *
	 * @param int $submission_id Submission ID.
	 * @param int $limit Maximum rows.
	 * @return array<int,array<string,mixed>>
	 */
	public function events($submission_id, $limit = 200) {
		global $wpdb;

		$table = AM_Ops_Tables::name(AM_Ops_Tables::EVENTS);
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE submission_id = %d
				ORDER BY created_at ASC, id ASC
				LIMIT %d",
				(int) $submission_id,
				min(1000, max(1, (int) $limit))
			),
			ARRAY_A
		);

		foreach ($rows as &$row) {
			$decoded       = json_decode((string) $row['details'], true);
			$row['details'] = is_array($decoded) ? $decoded : array();
		}
		unset($row);

		return $rows;
	}

	/**
	 * Persist notification delivery state and an audit event atomically.
	 *
	 * @param int      $submission_id Submission ID.
	 * @param string   $state Delivery state.
	 * @param string   $destination Immutable destination.
	 * @param int|null $actor_user_id Optional actor.
	 * @param array    $details Additional safe event details.
	 * @return bool|WP_Error
	 */
	public function set_notification_state($submission_id, $state, $destination = '', $actor_user_id = null, $details = array()) {
		global $wpdb;

		$allowed = array(
			'not_queued',
			'queued',
			'digest_queued',
			'sent',
			'digested',
			'retrying',
			'failed',
			'suppressed',
			'needs_configuration',
			'legacy_unknown',
		);
		$state = sanitize_key($state);
		if (!in_array($state, $allowed, true)) {
			return new WP_Error('am_ops_invalid_notification_state', __('Invalid notification state.', 'alexandra-operations'));
		}

		$table = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
		$wpdb->query('START TRANSACTION');
		$updated = $wpdb->update(
			$table,
			array(
				'notification_state'       => $state,
				'notification_destination' => substr(sanitize_email($destination), 0, 254),
				'updated_at'               => gmdate('Y-m-d H:i:s'),
			),
			array('id' => (int) $submission_id)
		);
		if (false === $updated) {
			$wpdb->query('ROLLBACK');
			return $this->database_error('am_ops_notification_update_failed');
		}

		$event = $this->add_event(
			$submission_id,
			'notification_' . $state,
			array_merge(array('destination' => sanitize_email($destination)), $details),
			$actor_user_id
		);
		if (is_wp_error($event)) {
			$wpdb->query('ROLLBACK');
			return $event;
		}

		$wpdb->query('COMMIT');
		delete_transient('am_ops_overview_metrics');

		return true;
	}

	/**
	 * Query the unified inbox using indexed filters and keyset cursors.
	 *
	 * @param array<string,mixed> $filters Filters.
	 * @return array{items:array<int,array<string,mixed>>,older_cursor:string,newer_cursor:string}
	 */
	public function query($filters = array()) {
		global $wpdb;

		$table      = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
		$conditions = array('1=1');
		$params     = array();
		$limit      = min(100, max(1, (int) ($filters['limit'] ?? 50)));

		$archived = sanitize_key($filters['archived'] ?? 'active');
		if ('archived' === $archived) {
			$conditions[] = 'archived_at IS NOT NULL';
		} elseif ('all' !== $archived) {
			$conditions[] = 'archived_at IS NULL';
		}

		$type = sanitize_key($filters['type'] ?? '');
		if (in_array($type, self::types(), true)) {
			$conditions[] = 'type = %s';
			$params[]     = $type;
		}

		$status = sanitize_key($filters['status'] ?? '');
		if (in_array($status, self::statuses(), true)) {
			$conditions[] = 'status = %s';
			$params[]     = $status;
		}

		$priority = sanitize_key($filters['priority'] ?? '');
		if (in_array($priority, self::priorities(), true)) {
			$conditions[] = 'priority = %s';
			$params[]     = $priority;
		}

		$branch = sanitize_title($filters['branch'] ?? '');
		if ($branch) {
			$conditions[] = 'branch_slug = %s';
			$params[]     = $branch;
		}

		$owner = $filters['owner'] ?? '';
		if ('unassigned' === $owner) {
			$conditions[] = 'owner_user_id IS NULL';
		} elseif ('' !== (string) $owner && ctype_digit((string) $owner)) {
			$conditions[] = 'owner_user_id = %d';
			$params[]     = (int) $owner;
		}

		$spam_state = sanitize_key($filters['spam_state'] ?? '');
		if (in_array($spam_state, self::spam_states(), true)) {
			$conditions[] = 'spam_state = %s';
			$params[]     = $spam_state;
		}

		$notification = sanitize_key($filters['notification_state'] ?? '');
		if ($notification) {
			$conditions[] = 'notification_state = %s';
			$params[]     = $notification;
		}

		$quick = sanitize_key($filters['quick'] ?? '');
		if ('new' === $quick) {
			$conditions[] = "status = 'new'";
		} elseif ('unassigned' === $quick) {
			$conditions[] = 'owner_user_id IS NULL';
			$conditions[] = "status NOT IN ('resolved','closed')";
		} elseif ('follow_up' === $quick) {
			$conditions[] = 'follow_up_at IS NOT NULL';
			$conditions[] = 'follow_up_at <= %s';
			$params[]     = gmdate('Y-m-d H:i:s');
			$conditions[] = "status NOT IN ('resolved','closed')";
		} elseif ('priority' === $quick) {
			$conditions[] = "priority IN ('high','urgent')";
			$conditions[] = "status NOT IN ('resolved','closed')";
		} elseif ('failures' === $quick) {
			$conditions[] = "notification_state IN ('failed','retrying','needs_configuration')";
		} elseif ('quarantine' === $quick) {
			$conditions[] = "spam_state IN ('suspected','quarantined')";
		} elseif ('attention' === $quick) {
			$conditions[] = "(
				priority IN ('high','urgent')
				OR notification_state IN ('failed','retrying','needs_configuration')
				OR (follow_up_at IS NOT NULL AND follow_up_at <= UTC_TIMESTAMP())
			)";
			$conditions[] = "status NOT IN ('resolved','closed')";
		}

		$date_from = self::filter_date($filters['date_from'] ?? '', false);
		if ($date_from) {
			$conditions[] = 'submitted_at >= %s';
			$params[]     = $date_from;
		}
		$date_to = self::filter_date($filters['date_to'] ?? '', true);
		if ($date_to) {
			$conditions[] = 'submitted_at <= %s';
			$params[]     = $date_to;
		}

		$search = trim(sanitize_text_field((string) ($filters['search'] ?? '')));
		if ($search) {
			if (preg_match('/^AM-\d{4}-[A-Z0-9]+$/i', $search)) {
				$conditions[] = 'public_ref = %s';
				$params[]     = strtoupper($search);
			} elseif (false !== strpos($search, '@') && is_email($search)) {
				$conditions[] = 'customer_email_hash = %s';
				$params[]     = hash('sha256', strtolower($search));
			} else {
				$phone = self::normalize_phone($search);
				if (preg_match('/^\+\d{8,15}$/', $phone)) {
					$conditions[] = 'customer_phone = %s';
					$params[]     = $phone;
				} else {
					$conditions[] = 'customer_name LIKE %s';
					$params[]     = $wpdb->esc_like($search) . '%';
				}
			}
		}

		$cursor    = self::decode_cursor((string) ($filters['cursor'] ?? ''));
		$direction = sanitize_key($filters['direction'] ?? 'older');
		$order     = 'DESC';
		if (!$cursor) {
			$direction = 'older';
		}
		if ($cursor) {
			if ('newer' === $direction) {
				$conditions[] = '(submitted_at > %s OR (submitted_at = %s AND id > %d))';
				$order        = 'ASC';
			} else {
				$conditions[] = '(submitted_at < %s OR (submitted_at = %s AND id < %d))';
				$direction    = 'older';
			}
			$params[] = $cursor['submitted_at'];
			$params[] = $cursor['submitted_at'];
			$params[] = $cursor['id'];
		}

		$sql = "SELECT * FROM {$table}
			WHERE " . implode(' AND ', $conditions)
			. " ORDER BY submitted_at {$order}, id {$order} LIMIT %d";
		$params[] = $limit + 1;
		$rows     = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
		$has_more = count($rows) > $limit;
		if ($has_more) {
			array_pop($rows);
		}
		if ('ASC' === $order) {
			$rows = array_reverse($rows);
		}

		$items = array_map(array($this, 'hydrate_row'), $rows);
		$older_cursor = '';
		$newer_cursor = '';
		if ($items) {
			$first = reset($items);
			$last  = end($items);
			if ($has_more || 'newer' === $direction) {
				$older_cursor = self::encode_cursor($last['submitted_at'], (int) $last['id']);
			}
			if ($cursor || 'newer' === $direction) {
				$newer_cursor = self::encode_cursor($first['submitted_at'], (int) $first['id']);
			}
		}

		return array(
			'items'        => $items,
			'older_cursor' => $older_cursor,
			'newer_cursor' => $newer_cursor,
		);
	}

	/**
	 * Action-oriented overview metrics, cached briefly for repeated dashboard
	 * loads. The cache is invalidated on every repository write.
	 *
	 * @return array<string,mixed>
	 */
	public function overview_metrics() {
		global $wpdb;

		$cached = get_transient('am_ops_overview_metrics');
		if (is_array($cached)) {
			return $cached;
		}

		$table = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
		$row   = $wpdb->get_row(
			"SELECT
				COUNT(*) AS total,
				SUM(archived_at IS NULL) AS active_count,
				SUM(archived_at IS NOT NULL) AS archived_count,
				SUM(archived_at IS NULL AND status = 'new') AS new_count,
				SUM(archived_at IS NULL AND owner_user_id IS NULL AND status NOT IN ('resolved','closed')) AS unassigned,
				SUM(archived_at IS NULL AND follow_up_at IS NOT NULL AND follow_up_at <= UTC_TIMESTAMP() AND status NOT IN ('resolved','closed')) AS follow_up_due,
				SUM(archived_at IS NULL AND priority IN ('high','urgent') AND status NOT IN ('resolved','closed')) AS priority_attention_count,
				SUM(archived_at IS NULL AND notification_state IN ('failed','retrying','needs_configuration')) AS notification_issues,
				SUM(
					archived_at IS NULL
					AND status NOT IN ('resolved','closed')
					AND (
						priority IN ('high','urgent')
						OR notification_state IN ('failed','retrying','needs_configuration')
						OR (follow_up_at IS NOT NULL AND follow_up_at <= UTC_TIMESTAMP())
					)
				) AS needs_attention_count,
				SUM(archived_at IS NULL AND spam_state IN ('suspected','quarantined')) AS quarantine_count,
				SUM(submitted_at >= UTC_DATE()) AS received_today,
				SUM(resolved_at >= UTC_DATE()) AS resolved_today
			FROM {$table}",
			ARRAY_A
		);
		if (!is_array($row)) {
			error_log('[Alexandra Operations] Overview metrics query failed: ' . $wpdb->last_error);
			$row = array(
				'total'               => 0,
				'active_count'        => 0,
				'archived_count'      => 0,
				'new_count'           => 0,
				'unassigned'          => 0,
				'follow_up_due'       => 0,
				'priority_attention_count' => 0,
				'notification_issues' => 0,
				'needs_attention_count' => 0,
				'quarantine_count'    => 0,
				'received_today'      => 0,
				'resolved_today'      => 0,
			);
		}
		foreach ($row as $key => $value) {
			$row[$key] = (int) $value;
		}
		$row['active']   = $row['active_count'];
		$row['archived'] = $row['archived_count'];
		$row['high_priority'] = $row['priority_attention_count'];
		$row['quarantined']   = $row['quarantine_count'];
		unset(
			$row['active_count'],
			$row['archived_count'],
			$row['priority_attention_count'],
			$row['quarantine_count']
		);

		$row['by_type'] = $wpdb->get_results(
			"SELECT type, COUNT(*) AS total,
				SUM(status = 'new') AS new_count,
				SUM(status NOT IN ('resolved','closed')) AS active_count
			FROM {$table}
			WHERE archived_at IS NULL
			GROUP BY type
			ORDER BY total DESC",
			ARRAY_A
		);
		$row['by_branch'] = $wpdb->get_results(
			"SELECT branch_slug, MAX(branch_label) AS branch_label,
				COUNT(*) AS total,
				SUM(status = 'new') AS new_count,
				SUM(status NOT IN ('resolved','closed')) AS active_count
			FROM {$table}
			WHERE archived_at IS NULL AND branch_slug <> ''
			GROUP BY branch_slug
			ORDER BY active_count DESC, branch_label ASC
			LIMIT 25",
			ARRAY_A
		);
		$row['by_owner'] = $wpdb->get_results(
			"SELECT owner_user_id, COUNT(*) AS workload_count,
				SUM(follow_up_at IS NOT NULL AND follow_up_at <= UTC_TIMESTAMP()) AS due
			FROM {$table}
			WHERE archived_at IS NULL
				AND status NOT IN ('resolved','closed')
			GROUP BY owner_user_id
			ORDER BY workload_count DESC
			LIMIT 25",
			ARRAY_A
		);
		foreach ($row['by_type'] as &$type_row) {
			$type_row['active'] = (int) $type_row['active_count'];
			unset($type_row['active_count']);
		}
		unset($type_row);
		foreach ($row['by_branch'] as &$branch_row) {
			$branch_row['active'] = (int) $branch_row['active_count'];
			unset($branch_row['active_count']);
		}
		unset($branch_row);
		foreach ($row['by_owner'] as &$owner_row) {
			$owner_row['active'] = (int) $owner_row['workload_count'];
			unset($owner_row['workload_count']);
		}
		unset($owner_row);

		set_transient('am_ops_overview_metrics', $row, 30);

		return $row;
	}

	/**
	 * Read outbound messages for a detail screen.
	 *
	 * @param int $submission_id Submission.
	 * @param int $limit Row cap.
	 * @return array<int,array<string,mixed>>
	 */
	public function messages($submission_id, $limit = 100) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . AM_Ops_Tables::name(AM_Ops_Tables::MESSAGES)
				. ' WHERE submission_id = %d ORDER BY created_at DESC, id DESC LIMIT %d',
				(int) $submission_id,
				min(500, max(1, (int) $limit))
			),
			ARRAY_A
		);
	}

	/**
	 * Update only staff-owned operational fields with optimistic locking.
	 *
	 * @param int                 $submission_id Submission.
	 * @param array<string,mixed> $changes Allowed operational changes.
	 * @param int                 $actor_user_id Staff actor.
	 * @param int|null            $expected_version UI version.
	 * @return array<string,mixed>|WP_Error
	 */
	public function update_operational($submission_id, $changes, $actor_user_id, $expected_version = null) {
		global $wpdb;

		$table = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
		$wpdb->query('START TRANSACTION');
		$current = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table} WHERE id = %d FOR UPDATE", (int) $submission_id),
			ARRAY_A
		);
		if (!$current) {
			$wpdb->query('ROLLBACK');
			return new WP_Error('am_ops_submission_not_found', __('Submission not found.', 'alexandra-operations'));
		}
		if (null !== $expected_version && (int) $current['version'] !== (int) $expected_version) {
			$wpdb->query('ROLLBACK');
			return new WP_Error(
				'am_ops_version_conflict',
				__('This submission was updated by another staff member. Reload it before saving.', 'alexandra-operations')
			);
		}

		$updates = array();
		$events  = array();

		if (array_key_exists('status', $changes)) {
			$status = sanitize_key($changes['status']);
			if (!in_array($status, self::statuses(), true)) {
				$wpdb->query('ROLLBACK');
				return new WP_Error('am_ops_invalid_status', __('Invalid workflow status.', 'alexandra-operations'));
			}
			if ($status !== $current['status']) {
				$updates['status'] = $status;
				$events[] = array('status_changed', array('from' => $current['status'], 'to' => $status));
				if (in_array($status, array('resolved', 'closed'), true)) {
					$updates['resolved_at'] = $current['resolved_at'] ?: gmdate('Y-m-d H:i:s');
				} else {
					$updates['resolved_at'] = null;
				}
			}
		}

		if (array_key_exists('priority', $changes)) {
			$priority = sanitize_key($changes['priority']);
			if (!in_array($priority, self::priorities(), true)) {
				$wpdb->query('ROLLBACK');
				return new WP_Error('am_ops_invalid_priority', __('Invalid priority.', 'alexandra-operations'));
			}
			if ($priority !== $current['priority']) {
				$updates['priority'] = $priority;
				$events[] = array('priority_changed', array('from' => $current['priority'], 'to' => $priority));
			}
		}

		if (array_key_exists('owner_user_id', $changes)) {
			$owner = absint($changes['owner_user_id']) ?: null;
			if ($owner && !get_userdata($owner)) {
				$wpdb->query('ROLLBACK');
				return new WP_Error('am_ops_invalid_owner', __('Invalid assigned owner.', 'alexandra-operations'));
			}
			if ($owner !== (null === $current['owner_user_id'] ? null : (int) $current['owner_user_id'])) {
				$updates['owner_user_id'] = $owner;
				$events[] = array(
					'owner_changed',
					array(
						'from' => null === $current['owner_user_id'] ? null : (int) $current['owner_user_id'],
						'to'   => $owner,
					),
				);
			}
		}

		if (array_key_exists('follow_up_at', $changes)) {
			$follow_up = self::nullable_datetime($changes['follow_up_at']);
			if ($follow_up !== $current['follow_up_at']) {
				$updates['follow_up_at'] = $follow_up;
				$events[] = array('follow_up_changed', array('from' => $current['follow_up_at'], 'to' => $follow_up));
			}
		}

		if (array_key_exists('internal_notes', $changes)) {
			$notes = sanitize_textarea_field((string) $changes['internal_notes']);
			if (!hash_equals((string) $current['internal_notes'], $notes)) {
				$updates['internal_notes'] = $notes;
				$events[] = array('notes_changed', array('length' => strlen($notes)));
			}
		}

		if (array_key_exists('spam_state', $changes)) {
			$spam = sanitize_key($changes['spam_state']);
			if (!in_array($spam, self::spam_states(), true)) {
				$wpdb->query('ROLLBACK');
				return new WP_Error('am_ops_invalid_spam_state', __('Invalid spam state.', 'alexandra-operations'));
			}
			if ($spam !== $current['spam_state']) {
				$updates['spam_state'] = $spam;
				$events[] = array('spam_state_changed', array('from' => $current['spam_state'], 'to' => $spam));
			}
		}

		if (array_key_exists('archived', $changes)) {
			$archive = (bool) $changes['archived'];
			$effective_status = $updates['status'] ?? $current['status'];
			if ($archive && !in_array($effective_status, array('resolved', 'closed'), true)) {
				$wpdb->query('ROLLBACK');
				return new WP_Error(
					'am_ops_archive_requires_resolution',
					__('Resolve or close a submission before archiving it.', 'alexandra-operations')
				);
			}
			$new_archive = $archive ? ($current['archived_at'] ?: gmdate('Y-m-d H:i:s')) : null;
			if ($new_archive !== $current['archived_at']) {
				$updates['archived_at'] = $new_archive;
				$events[] = array($archive ? 'archived' : 'restored', array());
			}
		}

		if (!$updates) {
			$wpdb->query('COMMIT');
			return $this->hydrate_row($current);
		}

		$updates['updated_at'] = gmdate('Y-m-d H:i:s');
		$updates['version']    = (int) $current['version'] + 1;
		$updated = $wpdb->update(
			$table,
			$updates,
			array(
				'id'      => (int) $submission_id,
				'version' => (int) $current['version'],
			)
		);
		if (1 !== $updated) {
			$wpdb->query('ROLLBACK');
			return new WP_Error(
				'am_ops_update_conflict',
				__('The submission could not be updated because it changed concurrently.', 'alexandra-operations')
			);
		}

		foreach ($events as $event) {
			$event_id = $this->add_event(
				$submission_id,
				$event[0],
				$event[1],
				$actor_user_id
			);
			if (is_wp_error($event_id)) {
				$wpdb->query('ROLLBACK');
				return $event_id;
			}
		}

		$wpdb->query('COMMIT');
		delete_transient('am_ops_overview_metrics');

		return $this->get($submission_id);
	}

	/**
	 * Apply one bounded bulk operation, retaining one audit timeline per row.
	 *
	 * @param int[]               $submission_ids IDs.
	 * @param array<string,mixed> $changes Changes.
	 * @param int                 $actor_user_id Actor.
	 * @return array{updated:int,errors:array<int,array<string,string>>}
	 */
	public function bulk_update($submission_ids, $changes, $actor_user_id) {
		$ids = array_slice(
			array_values(array_unique(array_filter(array_map('absint', (array) $submission_ids)))),
			0,
			100
		);
		$result = array('updated' => 0, 'errors' => array());
		foreach ($ids as $id) {
			$updated = $this->update_operational($id, $changes, $actor_user_id);
			if (is_wp_error($updated)) {
				$result['errors'][$id] = array(
					'code'    => $updated->get_error_code(),
					'message' => $updated->get_error_message(),
				);
			} else {
				$result['updated']++;
				$this->add_event($id, 'bulk_operation_applied', array('fields' => array_keys($changes)), $actor_user_id);
			}
		}

		return $result;
	}

	/**
	 * Normalize and validate a creation payload.
	 *
	 * @param array<string,mixed> $data Raw repository input.
	 * @return array<string,mixed>|WP_Error
	 */
	private function normalize_create_data($data) {
		$type = sanitize_key($data['type'] ?? '');
		if (!in_array($type, self::types(), true)) {
			return new WP_Error(
				'am_ops_invalid_submission_type',
				__('Invalid submission type.', 'alexandra-operations'),
				array('status' => 400)
			);
		}

		$status = sanitize_key($data['status'] ?? 'new');
		if (!in_array($status, self::statuses(), true)) {
			return new WP_Error('am_ops_invalid_status', __('Invalid workflow status.', 'alexandra-operations'));
		}

		$priority = sanitize_key($data['priority'] ?? 'normal');
		if (!in_array($priority, self::priorities(), true)) {
			return new WP_Error('am_ops_invalid_priority', __('Invalid priority.', 'alexandra-operations'));
		}

		$spam_state = sanitize_key($data['spam_state'] ?? 'clean');
		if (!in_array($spam_state, self::spam_states(), true)) {
			return new WP_Error('am_ops_invalid_spam_state', __('Invalid spam state.', 'alexandra-operations'));
		}

		$email       = strtolower(sanitize_email((string) ($data['customer_email'] ?? '')));
		$idempotency = self::idempotency_hash((string) ($data['idempotency_key'] ?? ''));
		$payload     = $data['payload'] ?? array();

		if (!is_array($payload)) {
			return new WP_Error('am_ops_invalid_payload', __('Invalid submission payload.', 'alexandra-operations'));
		}

		$payload_json = wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		if (false === $payload_json) {
			return new WP_Error('am_ops_invalid_payload', __('The submission payload cannot be encoded.', 'alexandra-operations'));
		}

		$submitted_at = self::normalize_datetime($data['submitted_at'] ?? '');
		$updated_at   = self::normalize_datetime($data['updated_at'] ?? $submitted_at);

		return array(
			'public_ref'              => '',
			'idempotency_key'         => $idempotency ?: null,
			'type'                    => $type,
			'source'                  => sanitize_key($data['source'] ?? 'website') ?: 'website',
			'status'                  => $status,
			'priority'                => $priority,
			'owner_user_id'           => !empty($data['owner_user_id']) ? (int) $data['owner_user_id'] : null,
			'branch_slug'             => substr(sanitize_title((string) ($data['branch_slug'] ?? '')), 0, 100),
			'branch_label'            => substr(sanitize_text_field((string) ($data['branch_label'] ?? '')), 0, 255),
			'customer_name'           => substr(sanitize_text_field((string) ($data['customer_name'] ?? '')), 0, 255),
			'customer_email'          => substr($email, 0, 254),
			'customer_email_hash'     => $email ? hash('sha256', $email) : '',
			'customer_phone'          => substr(self::normalize_phone((string) ($data['customer_phone'] ?? '')), 0, 32),
			'subject'                 => substr(sanitize_text_field((string) ($data['subject'] ?? '')), 0, 255),
			'payload'                 => $payload_json,
			'internal_notes'          => sanitize_textarea_field((string) ($data['internal_notes'] ?? '')),
			'submitted_at'            => $submitted_at,
			'updated_at'              => $updated_at,
			'first_opened_at'         => self::nullable_datetime($data['first_opened_at'] ?? null),
			'first_opened_by'         => !empty($data['first_opened_by']) ? (int) $data['first_opened_by'] : null,
			'follow_up_at'            => self::nullable_datetime($data['follow_up_at'] ?? null),
			'resolved_at'             => self::nullable_datetime($data['resolved_at'] ?? null),
			'archived_at'             => self::nullable_datetime($data['archived_at'] ?? null),
			'spam_state'              => $spam_state,
			'spam_score'              => (float) ($data['spam_score'] ?? 0),
			'notification_state'      => sanitize_key($data['notification_state'] ?? 'not_queued') ?: 'not_queued',
			'notification_destination'=> substr(sanitize_email((string) ($data['notification_destination'] ?? '')), 0, 254),
			'duplicate_fingerprint'   => self::normalize_hash($data['duplicate_fingerprint'] ?? ''),
			'ip_hash'                 => self::normalize_hash($data['ip_hash'] ?? ''),
			'user_agent_hash'         => self::normalize_hash($data['user_agent_hash'] ?? ''),
			'legacy_post_id'          => !empty($data['legacy_post_id']) ? (int) $data['legacy_post_id'] : null,
			'version'                 => max(1, (int) ($data['version'] ?? 1)),
		);
	}

	/**
	 * Fetch a row using an already normalized idempotency hash.
	 *
	 * @param string $hash SHA-256 hash.
	 * @return array<string,mixed>|null
	 */
	private function get_by_idempotency_hash($hash) {
		global $wpdb;

		$table = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
		$row   = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table} WHERE idempotency_key = %s LIMIT 1", $hash),
			ARRAY_A
		);

		return $row ? $this->hydrate_row($row) : null;
	}

	/**
	 * Convert stored JSON and numeric fields into repository values.
	 *
	 * @param array<string,mixed> $row Database row.
	 * @return array<string,mixed>
	 */
	public function hydrate_row($row) {
		$payload        = json_decode((string) ($row['payload'] ?? ''), true);
		$row['payload'] = is_array($payload) ? $payload : array();

		foreach (
			array(
				'id',
				'owner_user_id',
				'first_opened_by',
				'legacy_post_id',
				'version',
			) as $integer_field
		) {
			$row[$integer_field] = null === $row[$integer_field] ? null : (int) $row[$integer_field];
		}

		$row['spam_score'] = (float) $row['spam_score'];

		return $row;
	}

	/**
	 * @param string $value YYYY-MM-DD.
	 * @param bool   $end_of_day End boundary.
	 * @return string
	 */
	private static function filter_date($value, $end_of_day) {
		$value = trim((string) $value);
		$date  = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
		if (!$date || $date->format('Y-m-d') !== $value) {
			return '';
		}

		return $value . ($end_of_day ? ' 23:59:59' : ' 00:00:00');
	}

	/**
	 * @param string $submitted_at UTC datetime.
	 * @param int    $id Submission ID.
	 * @return string
	 */
	private static function encode_cursor($submitted_at, $id) {
		$json = wp_json_encode(array('t' => $submitted_at, 'i' => (int) $id));

		return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
	}

	/**
	 * @param string $cursor Opaque keyset cursor.
	 * @return array{submitted_at:string,id:int}|null
	 */
	private static function decode_cursor($cursor) {
		if ('' === $cursor || strlen($cursor) > 256) {
			return null;
		}

		$decoded = base64_decode(strtr($cursor, '-_', '+/'), true);
		$data    = $decoded ? json_decode($decoded, true) : null;
		if (
			!is_array($data)
			|| empty($data['t'])
			|| empty($data['i'])
			|| !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $data['t'])
		) {
			return null;
		}

		return array(
			'submitted_at' => (string) $data['t'],
			'id'           => (int) $data['i'],
		);
	}

	/**
	 * Normalize client idempotency tokens before storage.
	 *
	 * @param string $key Client token.
	 * @return string
	 */
	public static function idempotency_hash($key) {
		$key = trim($key);

		return '' === $key ? '' : hash('sha256', $key);
	}

	/**
	 * Generate a non-sequential, support-friendly public reference.
	 *
	 * @return string
	 */
	public static function generate_public_reference() {
		$alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
		$bytes    = random_bytes(10);
		$token    = '';

		for ($index = 0; $index < 10; $index++) {
			$token .= $alphabet[ord($bytes[$index]) % strlen($alphabet)];
		}

		return 'AM-' . gmdate('Y') . '-' . $token;
	}

	/**
	 * Normalize a date as UTC MySQL datetime, defaulting to now.
	 *
	 * @param mixed $value Date-like value.
	 * @return string
	 */
	private static function normalize_datetime($value) {
		if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
			return $value;
		}

		$timestamp = is_string($value) && '' !== $value ? strtotime($value) : false;

		return gmdate('Y-m-d H:i:s', false === $timestamp ? time() : $timestamp);
	}

	/**
	 * @param mixed $value Optional date-like value.
	 * @return string|null
	 */
	private static function nullable_datetime($value) {
		if (null === $value || '' === $value) {
			return null;
		}

		return self::normalize_datetime($value);
	}

	/**
	 * Preserve a leading plus while removing display punctuation.
	 *
	 * @param string $phone Submitted phone.
	 * @return string
	 */
	private static function normalize_phone($phone) {
		$phone = trim($phone);
		$plus  = 0 === strpos($phone, '+') ? '+' : '';

		return $plus . preg_replace('/\D+/', '', $phone);
	}

	/**
	 * @param mixed $hash Candidate SHA-256/HMAC value.
	 * @return string
	 */
	private static function normalize_hash($hash) {
		$hash = strtolower(trim((string) $hash));

		return preg_match('/^[a-f0-9]{64}$/', $hash) ? $hash : '';
	}

	/**
	 * Avoid leaking SQL details to public callers while keeping them in logs.
	 *
	 * @param string $code Stable error code.
	 * @return WP_Error
	 */
	private function database_error($code) {
		global $wpdb;

		if ($wpdb->last_error) {
			error_log('[Alexandra Operations] ' . $code . ': ' . $wpdb->last_error);
		}

		return new WP_Error(
			$code,
			__('The submission could not be stored. Please try again.', 'alexandra-operations'),
			array('status' => 500)
		);
	}
}
