<?php
/**
 * Copy-first, idempotent migration from legacy submission CPTs.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_Ops_Migration {
	const LOCK_OPTION   = 'am_ops_legacy_migration_lock';
	const STATUS_OPTION = 'am_ops_legacy_migration_status';
	const DEFAULT_BATCH = 250;

	/**
	 * Migrate every eligible legacy post without modifying the source records.
	 *
	 * @param int $batch_size Primary-key batch size.
	 * @return array<string,mixed>|WP_Error
	 */
	public function run($batch_size = self::DEFAULT_BATCH) {
		$batch_size = min(1000, max(1, (int) $batch_size));
		$lock_token = wp_generate_uuid4();
		$lock       = get_option(self::LOCK_OPTION, array());
		$lock_age   = time() - (int) ($lock['started'] ?? 0);

		if ($lock_age < 900 && !add_option(
			self::LOCK_OPTION,
			array('token' => $lock_token, 'started' => time()),
			'',
			'no'
		)) {
			return new WP_Error(
				'am_ops_migration_locked',
				__('Another submissions migration is already running.', 'alexandra-operations')
			);
		}

		update_option(
			self::LOCK_OPTION,
			array('token' => $lock_token, 'started' => time()),
			false
		);

		$stats = array(
			'started_at'         => gmdate('c'),
			'source_total'       => $this->source_count(),
			'processed'          => 0,
			'created'            => 0,
			'already_migrated'   => 0,
			'messages_created'   => 0,
			'messages_existing'  => 0,
			'files_created'      => 0,
			'files_existing'     => 0,
			'errors'             => array(),
			'last_legacy_post_id'=> 0,
		);

		try {
			if (!AM_Ops_Schema::maybe_install()) {
				return new WP_Error(
					'am_ops_schema_unavailable',
					__('The operations schema is unavailable.', 'alexandra-operations')
				);
			}

			$repository = new AM_Ops_Repository();
			$after_id   = 0;

			do {
				$posts = $this->source_posts($after_id, $batch_size);
				if (empty($posts)) {
					break;
				}

				$post_ids = array_map('intval', wp_list_pluck($posts, 'ID'));
				update_meta_cache('post', $post_ids);

				foreach ($posts as $post) {
					$legacy_post_id = (int) $post->ID;
					$after_id       = $legacy_post_id;
					$stats['processed']++;
					$stats['last_legacy_post_id'] = $legacy_post_id;

					$mapped = $this->map_post($post);
					if (is_wp_error($mapped)) {
						$this->record_error($stats, $legacy_post_id, $mapped);
						continue;
					}

					$existing = $repository->get_by_legacy_post_id($legacy_post_id);
					if ($existing) {
						$stats['already_migrated']++;
						$submission = $existing;
					} else {
						$result = $repository->create($mapped);
						if (is_wp_error($result)) {
							$this->record_error($stats, $legacy_post_id, $result);
							continue;
						}

						$submission = $result['submission'];
						if ($result['created']) {
							$stats['created']++;
						} else {
							$stats['already_migrated']++;
						}
					}

					$message_stats = $this->migrate_messages(
						$legacy_post_id,
						(int) $submission['id'],
						$repository
					);
					$stats['messages_created']  += $message_stats['created'];
					$stats['messages_existing'] += $message_stats['existing'];
					foreach ($message_stats['errors'] as $error) {
						$this->record_error($stats, $legacy_post_id, $error);
					}

					if ('am_application' === $post->post_type) {
						$file_result = $this->migrate_file(
							$legacy_post_id,
							(int) $submission['id'],
							$repository
						);
						if (is_wp_error($file_result)) {
							$this->record_error($stats, $legacy_post_id, $file_result);
						} elseif ('created' === $file_result) {
							$stats['files_created']++;
						} elseif ('existing' === $file_result) {
							$stats['files_existing']++;
						}
					}
				}

				update_option(
					self::STATUS_OPTION,
					array_merge($stats, array('state' => 'running', 'updated_at' => gmdate('c'))),
					false
				);
			} while (count($posts) === $batch_size);

			$stats['finished_at']    = gmdate('c');
			$stats['reconciliation'] = $this->reconcile($batch_size);
			$stats['state']           = (
				empty($stats['errors'])
				&& $stats['reconciliation']['healthy']
			) ? 'verified' : 'needs_attention';

			update_option(self::STATUS_OPTION, $stats, false);

			return $stats;
		} catch (Throwable $error) {
			$stats['state']       = 'failed';
			$stats['finished_at'] = gmdate('c');
			$stats['errors'][]    = array(
				'legacy_post_id' => (int) $stats['last_legacy_post_id'],
				'code'           => 'exception',
				'message'        => $error->getMessage(),
			);
			update_option(self::STATUS_OPTION, $stats, false);

			return new WP_Error('am_ops_migration_failed', $error->getMessage(), $stats);
		} finally {
			$current_lock = get_option(self::LOCK_OPTION, array());
			if (($current_lock['token'] ?? '') === $lock_token) {
				delete_option(self::LOCK_OPTION);
			}
		}
	}

	/**
	 * Compare current legacy sources to copied rows without changing either.
	 *
	 * @param int $batch_size Primary-key batch size.
	 * @return array<string,mixed>
	 */
	public function reconcile($batch_size = self::DEFAULT_BATCH) {
		global $wpdb;

		$batch_size = min(1000, max(1, (int) $batch_size));
		$repository = new AM_Ops_Repository();
		$report     = array(
			'source_total'         => $this->source_count(),
			'destination_total'    => 0,
			'missing_post_ids'     => array(),
			'checksum_mismatches'  => array(),
			'missing_message_keys' => array(),
			'missing_file_keys'    => array(),
			'orphan_legacy_ids'    => array(),
			'healthy'              => false,
		);

		$submissions_table = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
		$report['destination_total'] = (int) $wpdb->get_var(
			"SELECT COUNT(id) FROM {$submissions_table} WHERE legacy_post_id IS NOT NULL"
		);

		$after_id = 0;
		do {
			$posts = $this->source_posts($after_id, $batch_size);
			if (empty($posts)) {
				break;
			}

			update_meta_cache('post', array_map('intval', wp_list_pluck($posts, 'ID')));

			foreach ($posts as $post) {
				$legacy_post_id = (int) $post->ID;
				$after_id       = $legacy_post_id;
				$mapped         = $this->map_post($post);
				$destination    = $repository->get_by_legacy_post_id($legacy_post_id);

				if (is_wp_error($mapped) || !$destination) {
					$report['missing_post_ids'][] = $legacy_post_id;
					continue;
				}

				$source_checksum      = (string) ($mapped['payload']['legacy']['mapped_checksum'] ?? '');
				$destination_checksum = $this->mapped_checksum_from_destination($destination);
				if (
					'' === $source_checksum
					|| !hash_equals($source_checksum, $destination_checksum)
				) {
					$report['checksum_mismatches'][] = $legacy_post_id;
				}

				$history = get_post_meta($legacy_post_id, '_am_submission_communications', true);
				if (is_array($history)) {
					foreach (array_keys($history) as $sequence) {
						$key = $this->legacy_message_key($legacy_post_id, (int) $sequence);
						if (!$this->message_exists($key)) {
							$report['missing_message_keys'][] = $legacy_post_id . ':' . $sequence;
						}
					}
				}

				if ('am_application' === $post->post_type) {
					$storage_key = (string) get_post_meta($legacy_post_id, '_am_app_cv_file', true);
					if ('' !== $storage_key && !$this->file_exists($storage_key)) {
						$report['missing_file_keys'][] = $storage_key;
					}
				}
			}
		} while (count($posts) === $batch_size);

		$source_types = "'am_enquiry','am_availability','am_application'";
		$orphan_rows  = $wpdb->get_col(
			"SELECT s.legacy_post_id
			FROM {$submissions_table} s
			LEFT JOIN {$wpdb->posts} p
				ON p.ID = s.legacy_post_id
				AND p.post_type IN ({$source_types})
				AND p.post_status NOT IN ('auto-draft','inherit')
			WHERE s.legacy_post_id IS NOT NULL
				AND p.ID IS NULL
			ORDER BY s.legacy_post_id ASC
			LIMIT 100"
		);
		$report['orphan_legacy_ids'] = array_map('intval', $orphan_rows);

		$report['healthy'] = (
			$report['source_total'] === $report['destination_total']
			&& empty($report['missing_post_ids'])
			&& empty($report['checksum_mismatches'])
			&& empty($report['missing_message_keys'])
			&& empty($report['missing_file_keys'])
			&& empty($report['orphan_legacy_ids'])
		);

		return $report;
	}

	/**
	 * Return the number of source records selected by migration.
	 *
	 * @return int
	 */
	public function source_count() {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(ID)
			FROM {$wpdb->posts}
			WHERE post_type IN ('am_enquiry','am_availability','am_application')
				AND post_status NOT IN ('auto-draft','inherit')"
		);
	}

	/**
	 * Fetch a keyset batch of source posts.
	 *
	 * @param int $after_id Last processed post ID.
	 * @param int $limit Batch size.
	 * @return WP_Post[]
	 */
	private function source_posts($after_id, $limit) {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
				FROM {$wpdb->posts}
				WHERE ID > %d
					AND post_type IN ('am_enquiry','am_availability','am_application')
					AND post_status NOT IN ('auto-draft','inherit')
				ORDER BY ID ASC
				LIMIT %d",
				(int) $after_id,
				(int) $limit
			)
		);

		return array_map('get_post', $rows);
	}

	/**
	 * Normalize one legacy post into repository creation fields.
	 *
	 * @param WP_Post $post Legacy post.
	 * @return array<string,mixed>|WP_Error
	 */
	private function map_post($post) {
		$legacy_post_id = (int) $post->ID;
		$submitted_at   = $this->submitted_at($post);
		$first_opened   = $this->nullable_utc(
			(string) get_post_meta($legacy_post_id, '_am_first_viewed_at', true)
		);
		$first_opened_by = absint(get_post_meta($legacy_post_id, '_am_first_viewed_by', true)) ?: null;
		$archived_at     = 'trash' === $post->post_status
			? $this->post_modified_at($post)
			: null;

		if ('am_enquiry' === $post->post_type) {
			$kind        = sanitize_key(get_post_meta($legacy_post_id, '_am_enq_kind', true));
			$is_visit    = 'booking' === $kind;
			$first       = sanitize_text_field(get_post_meta($legacy_post_id, '_am_enq_first_name', true));
			$last        = sanitize_text_field(get_post_meta($legacy_post_id, '_am_enq_last_name', true));
			$email       = strtolower(sanitize_email(get_post_meta($legacy_post_id, '_am_enq_email', true)));
			$phone       = (string) get_post_meta($legacy_post_id, '_am_enq_phone', true);
			$branch      = $this->branch(
				(string) get_post_meta($legacy_post_id, '_am_enq_branch', true)
			);
			$legacy_status = sanitize_key(get_post_meta($legacy_post_id, '_am_enq_status', true)) ?: 'new';
			$mapped = array(
				'type'          => $is_visit ? AM_Ops_Repository::TYPE_VISIT : AM_Ops_Repository::TYPE_CONTACT,
				'source'        => $is_visit ? 'visit_form' : 'contact_form',
				'status'        => $this->map_status($post->post_type, $legacy_status),
				'owner_user_id' => absint(get_post_meta($legacy_post_id, '_am_enq_owner', true)) ?: null,
				'branch_slug'   => $branch['slug'],
				'branch_label'  => $branch['label'],
				'customer_name' => trim($first . ' ' . $last),
				'customer_email'=> $email,
				'customer_phone'=> $phone,
				'subject'       => sanitize_text_field($post->post_title),
				'payload'       => array(
					'first_name'           => $first,
					'last_name'            => $last,
					'message'              => (string) get_post_meta($legacy_post_id, '_am_enq_message', true),
					'preferred_visit_date' => (string) get_post_meta($legacy_post_id, '_am_enq_preferred_date', true),
					'legacy'               => $this->legacy_payload($post, $legacy_status),
				),
				'internal_notes'=> (string) get_post_meta($legacy_post_id, '_am_enq_notes', true),
			);
		} elseif ('am_availability' === $post->post_type) {
			$name          = sanitize_text_field(get_post_meta($legacy_post_id, '_am_avl_name', true));
			$email         = strtolower(sanitize_email(get_post_meta($legacy_post_id, '_am_avl_email', true)));
			$phone         = (string) get_post_meta($legacy_post_id, '_am_avl_phone', true);
			$branch        = $this->branch(
				(string) get_post_meta($legacy_post_id, '_am_avl_branch', true)
			);
			$legacy_status = sanitize_key(get_post_meta($legacy_post_id, '_am_avl_status', true)) ?: 'new';
			$mapped = array(
				'type'          => AM_Ops_Repository::TYPE_AVAILABILITY,
				'source'        => 'availability_form',
				'status'        => $this->map_status($post->post_type, $legacy_status),
				'owner_user_id' => absint(get_post_meta($legacy_post_id, '_am_avl_owner', true)) ?: null,
				'branch_slug'   => $branch['slug'],
				'branch_label'  => $branch['label'],
				'customer_name' => $name,
				'customer_email'=> $email,
				'customer_phone'=> $phone,
				'subject'       => sanitize_text_field($post->post_title),
				'payload'       => array(
					'child_age'         => (string) get_post_meta($legacy_post_id, '_am_avl_child_age', true),
					'desired_start_date'=> (string) get_post_meta($legacy_post_id, '_am_avl_start', true),
					'sessions'          => (string) get_post_meta($legacy_post_id, '_am_avl_sessions', true),
					'message'           => (string) get_post_meta($legacy_post_id, '_am_avl_message', true),
					'legacy'            => $this->legacy_payload($post, $legacy_status),
				),
				'internal_notes'=> (string) get_post_meta($legacy_post_id, '_am_avl_notes', true),
			);
		} elseif ('am_application' === $post->post_type) {
			$first         = sanitize_text_field(get_post_meta($legacy_post_id, '_am_app_first_name', true));
			$last          = sanitize_text_field(get_post_meta($legacy_post_id, '_am_app_last_name', true));
			$email         = strtolower(sanitize_email(get_post_meta($legacy_post_id, '_am_app_email', true)));
			$phone         = (string) get_post_meta($legacy_post_id, '_am_app_phone', true);
			$legacy_status = sanitize_key(get_post_meta($legacy_post_id, '_am_app_status', true)) ?: 'new';
			$mapped = array(
				'type'          => AM_Ops_Repository::TYPE_APPLICATION,
				'source'        => 'careers_form',
				'status'        => $this->map_status($post->post_type, $legacy_status),
				'owner_user_id' => absint(get_post_meta($legacy_post_id, '_am_app_owner', true)) ?: null,
				'branch_slug'   => '',
				'branch_label'  => '',
				'customer_name' => trim($first . ' ' . $last),
				'customer_email'=> $email,
				'customer_phone'=> $phone,
				'subject'       => sanitize_text_field($post->post_title),
				'payload'       => array(
					'first_name'     => $first,
					'last_name'      => $last,
					'qualification'  => (string) get_post_meta($legacy_post_id, '_am_app_qualification', true),
					'position'       => (string) get_post_meta($legacy_post_id, '_am_app_position', true),
					'job_slug'       => (string) get_post_meta($legacy_post_id, '_am_app_job_slug', true),
					'job_title'      => (string) get_post_meta($legacy_post_id, '_am_app_job_title', true),
					'message'        => (string) get_post_meta($legacy_post_id, '_am_app_message', true),
					'legacy_cv_name' => (string) get_post_meta($legacy_post_id, '_am_app_cv_name', true),
					'legacy'         => $this->legacy_payload($post, $legacy_status),
				),
				'internal_notes'=> (string) get_post_meta($legacy_post_id, '_am_app_notes', true),
			);
		} else {
			return new WP_Error('am_ops_unsupported_legacy_type', 'Unsupported legacy post type.');
		}

		$mapped['priority']                 = 'normal';
		$mapped['submitted_at']             = $submitted_at;
		$mapped['updated_at']               = $this->post_modified_at($post);
		$mapped['first_opened_at']          = $first_opened;
		$mapped['first_opened_by']          = $first_opened_by;
		$mapped['resolved_at']              = in_array($mapped['status'], array('resolved', 'closed'), true)
			? $this->post_modified_at($post)
			: null;
		$mapped['archived_at']              = $archived_at;
		$mapped['spam_state']               = 'clean';
		$mapped['notification_state']       = 'legacy_unknown';
		$mapped['notification_destination'] = '';
		$mapped['duplicate_fingerprint']    = hash(
			'sha256',
			implode(
				'|',
				array(
					$mapped['type'],
					strtolower($mapped['customer_email']),
					$mapped['customer_phone'],
					$submitted_at,
				)
			)
		);
		$mapped['legacy_post_id'] = $legacy_post_id;

		$mapped['payload']['legacy']['mapped_checksum'] = $this->mapped_checksum($mapped);

		return $mapped;
	}

	/**
	 * Preserve source identity and a checksum of all Alexandra legacy metadata.
	 *
	 * @param WP_Post $post Source post.
	 * @param string  $workflow_status Original status.
	 * @return array<string,mixed>
	 */
	private function legacy_payload($post, $workflow_status) {
		$all_meta = get_post_meta((int) $post->ID);
		$source   = array(
			'post' => array(
				'ID'                => (int) $post->ID,
				'post_type'         => (string) $post->post_type,
				'post_status'       => (string) $post->post_status,
				'post_title'        => (string) $post->post_title,
				'post_date_gmt'     => (string) $post->post_date_gmt,
				'post_modified_gmt' => (string) $post->post_modified_gmt,
			),
			'meta' => array(),
		);

		foreach ($all_meta as $key => $values) {
			if (0 !== strpos((string) $key, '_am_')) {
				continue;
			}
			$source['meta'][$key] = array_map('maybe_unserialize', $values);
		}

		return array(
			'post_id'          => (int) $post->ID,
			'post_status'      => (string) $post->post_status,
			'workflow_status'  => $workflow_status,
			'source_checksum'  => $this->hash_canonical($source),
		);
	}

	/**
	 * Map legacy workflow vocabularies into the unified state model.
	 *
	 * @param string $post_type Legacy post type.
	 * @param string $status Legacy status.
	 * @return string
	 */
	private function map_status($post_type, $status) {
		$maps = array(
			'am_enquiry' => array(
				'new'         => 'new',
				'in_progress' => 'in_progress',
				'replied'     => 'replied',
				'closed'      => 'closed',
			),
			'am_availability' => array(
				'new'        => 'new',
				'reviewing'  => 'in_progress',
				'contacted'  => 'replied',
				'waitlisted' => 'waiting',
				'placed'     => 'resolved',
				'closed'     => 'closed',
			),
			'am_application' => array(
				'new'       => 'new',
				'reviewed'  => 'in_progress',
				'contacted' => 'replied',
				'interview' => 'in_progress',
				'offered'   => 'waiting',
				'hired'     => 'resolved',
				'rejected'  => 'closed',
				'closed'    => 'closed',
			),
		);

		return $maps[$post_type][$status] ?? 'new';
	}

	/**
	 * Resolve an immutable submitted branch label to a current dynamic slug.
	 *
	 * @param string $label Submitted label.
	 * @return array{slug:string,label:string}
	 */
	private function branch($label) {
		$label = sanitize_text_field($label);
		if ('' === $label || '—' === $label || '-' === $label) {
			return array('slug' => '', 'label' => $label);
		}

		$slug       = sanitize_title($label);
		$nurseries  = function_exists('am_get_nurseries') ? am_get_nurseries() : array();
		foreach ($nurseries as $nursery) {
			$current_slug = sanitize_title((string) ($nursery['id'] ?? ''));
			$current_name = sanitize_text_field((string) ($nursery['name'] ?? ''));
			if (
				$slug === $current_slug
				|| 0 === strcasecmp($label, $current_name)
			) {
				return array(
					'slug'  => $current_slug,
					'label' => $label,
				);
			}
		}

		return array('slug' => substr($slug, 0, 100), 'label' => $label);
	}

	/**
	 * Copy serialized communication history into one row per message.
	 *
	 * @param int                   $legacy_post_id Source post.
	 * @param int                   $submission_id Destination submission.
	 * @param AM_Ops_Repository     $repository Audit writer.
	 * @return array{created:int,existing:int,errors:WP_Error[]}
	 */
	private function migrate_messages($legacy_post_id, $submission_id, $repository) {
		global $wpdb;

		$stats   = array('created' => 0, 'existing' => 0, 'errors' => array());
		$history = get_post_meta($legacy_post_id, '_am_submission_communications', true);
		if (!is_array($history)) {
			return $stats;
		}

		$table = AM_Ops_Tables::name(AM_Ops_Tables::MESSAGES);
		foreach (array_values($history) as $sequence => $entry) {
			if (!is_array($entry)) {
				continue;
			}

			$legacy_key = $this->legacy_message_key($legacy_post_id, $sequence);
			if ($this->message_exists($legacy_key)) {
				$stats['existing']++;
				continue;
			}

			$recipient = strtolower(sanitize_email((string) ($entry['recipient'] ?? '')));
			$result    = sanitize_key($entry['result'] ?? 'recorded');
			$status    = in_array($result, array('sent', 'failed'), true) ? $result : 'recorded';
			$created   = $this->nullable_utc((string) ($entry['sent_at'] ?? '')) ?: gmdate('Y-m-d H:i:s');

			$inserted = $wpdb->insert(
				$table,
				array(
					'submission_id' => $submission_id,
					'recipient'     => $recipient,
					'recipient_hash'=> $recipient ? hash('sha256', $recipient) : '',
					'subject'       => substr(sanitize_text_field((string) ($entry['subject'] ?? '')), 0, 255),
					'message_body'  => (string) ($entry['message'] ?? ''),
					'actor_user_id' => absint($entry['sent_by'] ?? 0) ?: null,
					'channel'       => 'email',
					'transport'     => substr(sanitize_key((string) ($entry['transport'] ?? 'legacy')), 0, 64),
					'status'        => $status,
					'provider_id'   => '',
					'last_error'    => (string) ($entry['error'] ?? ''),
					'legacy_key'    => $legacy_key,
					'created_at'    => $created,
					'attempted_at'  => $created,
					'sent_at'       => 'sent' === $status ? $created : null,
				)
			);

			if (false === $inserted) {
				$stats['errors'][] = new WP_Error(
					'am_ops_legacy_message_failed',
					$wpdb->last_error ?: 'Legacy communication could not be copied.'
				);
				continue;
			}

			$message_id = (int) $wpdb->insert_id;
			$event      = $repository->add_event(
				$submission_id,
				'legacy_reply_migrated',
				array(
					'message_id'      => $message_id,
					'legacy_sequence' => $sequence,
					'status'          => $status,
				),
				absint($entry['sent_by'] ?? 0) ?: null
			);
			if (is_wp_error($event)) {
				$stats['errors'][] = $event;
			}

			$stats['created']++;
		}

		return $stats;
	}

	/**
	 * Register legacy private-file metadata without moving or renaming bytes.
	 *
	 * @param int               $legacy_post_id Source application post.
	 * @param int               $submission_id Destination submission.
	 * @param AM_Ops_Repository $repository Audit writer.
	 * @return string|WP_Error created, existing, or none.
	 */
	private function migrate_file($legacy_post_id, $submission_id, $repository) {
		global $wpdb;

		$storage_key = (string) get_post_meta($legacy_post_id, '_am_app_cv_file', true);
		if ('' === $storage_key) {
			return 'none';
		}
		if (basename($storage_key) !== $storage_key) {
			return new WP_Error('am_ops_invalid_legacy_file_key', 'Legacy private-file key is unsafe.');
		}
		if ($this->file_exists($storage_key)) {
			return 'existing';
		}

		$path = function_exists('am_application_private_file_path')
			? am_application_private_file_path($legacy_post_id)
			: '';
		$readable = $path && is_readable($path);
		$sha256   = $readable ? hash_file('sha256', $path) : '';
		$size     = $readable
			? filesize($path)
			: (int) get_post_meta($legacy_post_id, '_am_app_cv_size', true);

		$inserted = $wpdb->insert(
			AM_Ops_Tables::name(AM_Ops_Tables::FILES),
			array(
				'submission_id'   => $submission_id,
				'storage_key'     => $storage_key,
				'original_filename'=> sanitize_file_name(
					(string) get_post_meta($legacy_post_id, '_am_app_cv_name', true)
				),
				'mime_type'       => sanitize_mime_type(
					(string) get_post_meta($legacy_post_id, '_am_app_cv_mime', true)
				),
				'byte_size'       => max(0, (int) $size),
				'sha256'          => $sha256,
				'validation_state'=> $readable ? 'validated' : 'missing',
				'created_at'      => $this->submitted_at(get_post($legacy_post_id)),
			)
		);

		if (false === $inserted) {
			return new WP_Error(
				'am_ops_legacy_file_failed',
				$wpdb->last_error ?: 'Legacy private-file metadata could not be copied.'
			);
		}

		$repository->add_event(
			$submission_id,
			'legacy_file_registered',
			array(
				'file_id'          => (int) $wpdb->insert_id,
				'validation_state' => $readable ? 'validated' : 'missing',
			)
		);

		return 'created';
	}

	/**
	 * Build a checksum over immutable mapped fields before storage.
	 *
	 * @param array<string,mixed> $mapped Mapped creation data.
	 * @return string
	 */
	private function mapped_checksum($mapped) {
		$payload = $mapped['payload'];
		unset($payload['legacy']['mapped_checksum']);

		return $this->hash_canonical(
			array(
				'type'           => $mapped['type'],
				'source'         => $mapped['source'],
				'branch_slug'    => $mapped['branch_slug'],
				'branch_label'   => $mapped['branch_label'],
				'customer_name'  => $mapped['customer_name'],
				'customer_email' => $mapped['customer_email'],
				'customer_phone' => $mapped['customer_phone'],
				'subject'        => $mapped['subject'],
				'payload'        => $payload,
				'submitted_at'   => $mapped['submitted_at'],
				'legacy_post_id' => (int) $mapped['legacy_post_id'],
			)
		);
	}

	/**
	 * Recreate the immutable checksum from a stored destination row.
	 *
	 * @param array<string,mixed> $destination Hydrated destination.
	 * @return string
	 */
	private function mapped_checksum_from_destination($destination) {
		$payload = $destination['payload'];
		unset($payload['legacy']['mapped_checksum']);

		return $this->hash_canonical(
			array(
				'type'           => $destination['type'],
				'source'         => $destination['source'],
				'branch_slug'    => $destination['branch_slug'],
				'branch_label'   => $destination['branch_label'],
				'customer_name'  => $destination['customer_name'],
				'customer_email' => $destination['customer_email'],
				'customer_phone' => $destination['customer_phone'],
				'subject'        => $destination['subject'],
				'payload'        => $payload,
				'submitted_at'   => $destination['submitted_at'],
				'legacy_post_id' => (int) $destination['legacy_post_id'],
			)
		);
	}

	/**
	 * Stable recursive JSON hash.
	 *
	 * @param mixed $value Value to canonicalize.
	 * @return string
	 */
	private function hash_canonical($value) {
		$canonicalize = static function ($item) use (&$canonicalize) {
			if (!is_array($item)) {
				return $item;
			}

			if (array_keys($item) !== range(0, count($item) - 1)) {
				ksort($item);
			}

			foreach ($item as $key => $child) {
				$item[$key] = $canonicalize($child);
			}

			return $item;
		};

		return hash(
			'sha256',
			wp_json_encode(
				$canonicalize($value),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			)
		);
	}

	/**
	 * @param int $legacy_post_id Source post.
	 * @param int $sequence Zero-based history index.
	 * @return string
	 */
	private function legacy_message_key($legacy_post_id, $sequence) {
		return hash('sha256', 'legacy-message:' . (int) $legacy_post_id . ':' . (int) $sequence);
	}

	/**
	 * @param string $legacy_key Message migration key.
	 * @return bool
	 */
	private function message_exists($legacy_key) {
		global $wpdb;

		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . AM_Ops_Tables::name(AM_Ops_Tables::MESSAGES) . ' WHERE legacy_key = %s LIMIT 1',
				$legacy_key
			)
		);
	}

	/**
	 * @param string $storage_key Opaque private storage key.
	 * @return bool
	 */
	private function file_exists($storage_key) {
		global $wpdb;

		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . AM_Ops_Tables::name(AM_Ops_Tables::FILES) . ' WHERE storage_key = %s LIMIT 1',
				$storage_key
			)
		);
	}

	/**
	 * Use the explicit submitted timestamp, falling back to the post's GMT date.
	 *
	 * @param WP_Post $post Legacy post.
	 * @return string
	 */
	private function submitted_at($post) {
		$keys = array(
			'am_enquiry'     => '_am_enq_submitted_at',
			'am_availability'=> '_am_avl_submitted_at',
			'am_application' => '_am_app_submitted_at',
		);
		$raw = isset($keys[$post->post_type])
			? (string) get_post_meta((int) $post->ID, $keys[$post->post_type], true)
			: '';

		return $this->nullable_utc($raw)
			?: $this->valid_post_gmt($post->post_date_gmt, $post->post_date);
	}

	/**
	 * @param WP_Post $post Legacy post.
	 * @return string
	 */
	private function post_modified_at($post) {
		return $this->valid_post_gmt($post->post_modified_gmt, $post->post_modified);
	}

	/**
	 * @param string $gmt Stored GMT date.
	 * @param string $local Stored local date.
	 * @return string
	 */
	private function valid_post_gmt($gmt, $local) {
		if (is_string($gmt) && '0000-00-00 00:00:00' !== $gmt && '' !== $gmt) {
			return $gmt;
		}

		$converted = get_gmt_from_date((string) $local, 'Y-m-d H:i:s');

		return $converted ?: gmdate('Y-m-d H:i:s');
	}

	/**
	 * @param string $value Date-like string.
	 * @return string|null
	 */
	private function nullable_utc($value) {
		if ('' === trim($value)) {
			return null;
		}

		$timestamp = strtotime($value);

		return false === $timestamp ? null : gmdate('Y-m-d H:i:s', $timestamp);
	}

	/**
	 * Append a bounded, non-sensitive error entry to migration statistics.
	 *
	 * @param array<string,mixed> $stats Statistics by reference.
	 * @param int                 $legacy_post_id Source post.
	 * @param WP_Error            $error Error.
	 * @return void
	 */
	private function record_error(&$stats, $legacy_post_id, $error) {
		if (count($stats['errors']) >= 100) {
			return;
		}

		$stats['errors'][] = array(
			'legacy_post_id' => (int) $legacy_post_id,
			'code'           => $error->get_error_code(),
			'message'        => $error->get_error_message(),
		);
	}
}
