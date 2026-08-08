<?php
/**
 * Repeatable custom-table schema installer and health checks.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_Ops_Schema {
	const VERSION_OPTION = 'am_ops_schema_version';
	const ERROR_OPTION   = 'am_ops_schema_error';
	const LOCK_OPTION    = 'am_ops_schema_install_lock';

	/**
	 * Install only when the recorded version or physical tables require it.
	 *
	 * @return bool
	 */
	public static function maybe_install() {
		$installed_version = (string) get_option(self::VERSION_OPTION, '');

		if (
			AM_OPS_SCHEMA_VERSION === $installed_version
			&& self::tables_exist()
		) {
			return true;
		}

		return self::install();
	}

	/**
	 * Create or update all operations tables using WordPress's safe dbDelta.
	 *
	 * An option lock prevents a traffic burst from running multiple schema
	 * updates concurrently. A stale lock expires after five minutes.
	 *
	 * @return bool
	 */
	public static function install() {
		$now      = time();
		$lock_age = $now - (int) get_option(self::LOCK_OPTION, 0);

		if ($lock_age < 300 && !add_option(self::LOCK_OPTION, $now, '', 'no')) {
			return false;
		}

		update_option(self::LOCK_OPTION, $now, false);

		try {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			foreach (self::statements() as $statement) {
				dbDelta($statement);
			}

			$health = self::health();
			if (!$health['healthy']) {
				update_option(
					self::ERROR_OPTION,
					array(
						'checked_at' => gmdate('c'),
						'missing'    => $health['missing'],
					),
					false
				);
				return false;
			}

			update_option(self::VERSION_OPTION, AM_OPS_SCHEMA_VERSION, false);
			delete_option(self::ERROR_OPTION);

			return true;
		} catch (Throwable $error) {
			update_option(
				self::ERROR_OPTION,
				array(
					'checked_at' => gmdate('c'),
					'message'    => $error->getMessage(),
				),
				false
			);

			return false;
		} finally {
			delete_option(self::LOCK_OPTION);
		}
	}

	/**
	 * Return CREATE TABLE statements compatible with MySQL and MariaDB versions
	 * supported by current WordPress releases.
	 *
	 * Datetimes are UTC. Foreign keys are intentionally omitted because many
	 * WordPress hosts and dbDelta workflows do not manage them safely.
	 *
	 * @return string[]
	 */
	private static function statements() {
		global $wpdb;

		$tables  = AM_Ops_Tables::all();
		$collate = $wpdb->get_charset_collate();

		$submissions = "CREATE TABLE {$tables[AM_Ops_Tables::SUBMISSIONS]} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_ref varchar(32) NOT NULL,
			idempotency_key char(64) DEFAULT NULL,
			type varchar(32) NOT NULL,
			source varchar(32) NOT NULL DEFAULT 'website',
			status varchar(32) NOT NULL DEFAULT 'new',
			priority varchar(16) NOT NULL DEFAULT 'normal',
			owner_user_id bigint(20) unsigned DEFAULT NULL,
			branch_slug varchar(100) NOT NULL DEFAULT '',
			branch_label varchar(255) NOT NULL DEFAULT '',
			customer_name varchar(255) NOT NULL DEFAULT '',
			customer_email varchar(254) NOT NULL DEFAULT '',
			customer_email_hash char(64) NOT NULL DEFAULT '',
			customer_phone varchar(32) NOT NULL DEFAULT '',
			subject varchar(255) NOT NULL DEFAULT '',
			payload longtext NOT NULL,
			internal_notes longtext NOT NULL,
			submitted_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			first_opened_at datetime DEFAULT NULL,
			first_opened_by bigint(20) unsigned DEFAULT NULL,
			follow_up_at datetime DEFAULT NULL,
			resolved_at datetime DEFAULT NULL,
			archived_at datetime DEFAULT NULL,
			spam_state varchar(24) NOT NULL DEFAULT 'clean',
			spam_score decimal(8,3) NOT NULL DEFAULT 0,
			notification_state varchar(24) NOT NULL DEFAULT 'not_queued',
			notification_destination varchar(254) NOT NULL DEFAULT '',
			duplicate_fingerprint char(64) NOT NULL DEFAULT '',
			ip_hash char(64) NOT NULL DEFAULT '',
			user_agent_hash char(64) NOT NULL DEFAULT '',
			legacy_post_id bigint(20) unsigned DEFAULT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			UNIQUE KEY public_ref (public_ref),
			UNIQUE KEY idempotency_key (idempotency_key),
			UNIQUE KEY legacy_post_id (legacy_post_id),
			KEY latest (submitted_at,id),
			KEY type_status_submitted (type,status,submitted_at,id),
			KEY status_submitted (status,submitted_at,id),
			KEY owner_status_followup (owner_user_id,status,follow_up_at,id),
			KEY owner_status_submitted (owner_user_id,status,submitted_at,id),
			KEY followup_status (follow_up_at,status,id),
			KEY branch_status_submitted (branch_slug,status,submitted_at,id),
			KEY priority_status_submitted (priority,status,submitted_at,id),
			KEY spam_submitted (spam_state,submitted_at,id),
			KEY notification_submitted (notification_state,submitted_at,id),
			KEY email_submitted (customer_email_hash,submitted_at,id),
			KEY phone_submitted (customer_phone,submitted_at,id),
			KEY duplicate_submitted (duplicate_fingerprint,submitted_at,id),
			KEY archive_id (archived_at,id),
			KEY name_submitted (customer_name(64),submitted_at,id)
		) {$collate};";

		$events = "CREATE TABLE {$tables[AM_Ops_Tables::EVENTS]} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			submission_id bigint(20) unsigned NOT NULL,
			actor_user_id bigint(20) unsigned DEFAULT NULL,
			event_type varchar(64) NOT NULL,
			details longtext NOT NULL,
			ip_hash char(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY submission_created (submission_id,created_at,id),
			KEY event_created (event_type,created_at,id),
			KEY actor_created (actor_user_id,created_at,id)
		) {$collate};";

		$messages = "CREATE TABLE {$tables[AM_Ops_Tables::MESSAGES]} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			submission_id bigint(20) unsigned NOT NULL,
			recipient varchar(254) NOT NULL,
			recipient_hash char(64) NOT NULL,
			subject varchar(255) NOT NULL,
			message_body longtext NOT NULL,
			actor_user_id bigint(20) unsigned DEFAULT NULL,
			channel varchar(24) NOT NULL DEFAULT 'email',
			transport varchar(64) NOT NULL DEFAULT '',
			status varchar(24) NOT NULL DEFAULT 'queued',
			provider_id varchar(191) NOT NULL DEFAULT '',
			last_error text NOT NULL,
			legacy_key char(64) DEFAULT NULL,
			created_at datetime NOT NULL,
			attempted_at datetime DEFAULT NULL,
			sent_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY legacy_key (legacy_key),
			KEY submission_created (submission_id,created_at,id),
			KEY status_created (status,created_at,id),
			KEY recipient_created (recipient_hash,created_at,id)
		) {$collate};";

		$files = "CREATE TABLE {$tables[AM_Ops_Tables::FILES]} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			submission_id bigint(20) unsigned NOT NULL,
			storage_key varchar(191) NOT NULL,
			original_filename varchar(255) NOT NULL,
			mime_type varchar(100) NOT NULL,
			byte_size bigint(20) unsigned NOT NULL DEFAULT 0,
			sha256 char(64) NOT NULL,
			validation_state varchar(24) NOT NULL DEFAULT 'validated',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY storage_key (storage_key),
			KEY submission_created (submission_id,created_at,id),
			KEY sha256 (sha256)
		) {$collate};";

		$jobs = "CREATE TABLE {$tables[AM_Ops_Tables::JOBS]} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			job_type varchar(48) NOT NULL,
			submission_id bigint(20) unsigned DEFAULT NULL,
			message_id bigint(20) unsigned DEFAULT NULL,
			recipient varchar(254) NOT NULL DEFAULT '',
			recipient_hash char(64) NOT NULL DEFAULT '',
			payload longtext NOT NULL,
			state varchar(24) NOT NULL DEFAULT 'pending',
			attempts int(10) unsigned NOT NULL DEFAULT 0,
			max_attempts smallint(5) unsigned NOT NULL DEFAULT 5,
			next_attempt_at datetime NOT NULL,
			locked_at datetime DEFAULT NULL,
			lock_token char(64) DEFAULT NULL,
			last_error text NOT NULL,
			unique_key char(64) NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			finished_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY unique_key (unique_key),
			KEY claimable (state,next_attempt_at,id),
			KEY locked (state,locked_at,id),
			KEY submission_created (submission_id,created_at,id),
			KEY message_created (message_id,created_at,id),
			KEY recipient_state (recipient_hash,state,created_at,id)
		) {$collate};";

		$rate_limits = "CREATE TABLE {$tables[AM_Ops_Tables::RATE_LIMITS]} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			key_hash char(64) NOT NULL,
			bucket varchar(64) NOT NULL,
			window_start datetime NOT NULL,
			window_seconds int(10) unsigned NOT NULL,
			hits bigint(20) unsigned NOT NULL DEFAULT 0,
			expires_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY rate_window (key_hash,bucket,window_start),
			KEY expires (expires_at,id),
			KEY bucket_expires (bucket,expires_at,id)
		) {$collate};";

		return array(
			$submissions,
			$events,
			$messages,
			$files,
			$jobs,
			$rate_limits,
		);
	}

	/**
	 * Check whether every required physical table exists.
	 *
	 * @return bool
	 */
	public static function tables_exist() {
		return self::health()['healthy'];
	}

	/**
	 * Return schema health without mutating the database.
	 *
	 * @return array{healthy:bool,missing:string[]}
	 */
	public static function health() {
		global $wpdb;

		$missing = array();

		foreach (AM_Ops_Tables::all() as $table) {
			$found = $wpdb->get_var(
				$wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))
			);

			if ($found !== $table) {
				$missing[] = $table;
			}
		}

		return array(
			'healthy' => empty($missing),
			'missing' => $missing,
		);
	}
}
