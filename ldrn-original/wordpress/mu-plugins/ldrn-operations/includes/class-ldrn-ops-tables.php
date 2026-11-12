<?php
/**
 * Central table-name registry.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class LDRN_Ops_Tables {
	const SUBMISSIONS = 'ldrn_submissions';
	const EVENTS      = 'ldrn_submission_events';
	const MESSAGES    = 'ldrn_submission_messages';
	const FILES       = 'ldrn_submission_files';
	const JOBS        = 'ldrn_ops_jobs';
	const RATE_LIMITS = 'ldrn_rate_limits';

	/**
	 * Return a fully prefixed operations table name.
	 *
	 * @param string $key One of this class's table constants.
	 * @return string
	 */
	public static function name($key) {
		global $wpdb;

		return $wpdb->prefix . $key;
	}

	/**
	 * Return every logical and fully prefixed table name.
	 *
	 * @return array<string,string>
	 */
	public static function all() {
		return array(
			self::SUBMISSIONS => self::name(self::SUBMISSIONS),
			self::EVENTS      => self::name(self::EVENTS),
			self::MESSAGES    => self::name(self::MESSAGES),
			self::FILES       => self::name(self::FILES),
			self::JOBS        => self::name(self::JOBS),
			self::RATE_LIMITS => self::name(self::RATE_LIMITS),
		);
	}
}
