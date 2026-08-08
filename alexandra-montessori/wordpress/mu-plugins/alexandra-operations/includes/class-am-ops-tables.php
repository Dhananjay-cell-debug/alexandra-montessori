<?php
/**
 * Central table-name registry.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_Ops_Tables {
	const SUBMISSIONS = 'am_submissions';
	const EVENTS      = 'am_submission_events';
	const MESSAGES    = 'am_submission_messages';
	const FILES       = 'am_submission_files';
	const JOBS        = 'am_ops_jobs';
	const RATE_LIMITS = 'am_rate_limits';

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
