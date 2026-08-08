<?php
/**
 * Operational settings with conservative, non-guessing defaults.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_Ops_Settings {
	const OPTION = 'am_ops_settings';

	/**
	 * Return validated operational settings.
	 *
	 * New installs intentionally default to dashboard-only notifications until
	 * the client approves actual recipients and delivery behavior.
	 *
	 * @return array<string,mixed>
	 */
	public static function get() {
		$stored = get_option(self::OPTION, array());
		if (!is_array($stored)) {
			$stored = array();
		}

		$mode = defined('AM_OPS_NOTIFICATION_MODE')
			? sanitize_key((string) AM_OPS_NOTIFICATION_MODE)
			: sanitize_key((string) ($stored['notification_mode'] ?? 'dashboard_only'));
		if (!in_array($mode, array('immediate', 'digest', 'dashboard_only'), true)) {
			$mode = 'dashboard_only';
		}

		$branch_recipients = array();
		foreach ((array) ($stored['branch_recipients'] ?? array()) as $slug => $email) {
			$slug  = sanitize_title((string) $slug);
			$email = strtolower(sanitize_email((string) $email));
			if ($slug && is_email($email)) {
				$branch_recipients[$slug] = $email;
			}
		}

		return array(
			'notification_mode'    => $mode,
			'digest_minutes'       => min(1440, max(5, (int) ($stored['digest_minutes'] ?? 30))),
			'default_recipient'    => self::email($stored['default_recipient'] ?? ''),
			'application_recipient'=> self::email($stored['application_recipient'] ?? ''),
			'branch_recipients'    => $branch_recipients,
			'retention_days'       => max(0, (int) ($stored['retention_days'] ?? 0)),
		);
	}

	/**
	 * Resolve a configured notification recipient without falling back to a
	 * developer address or an unapproved WordPress administrator inbox.
	 *
	 * @param string $type Submission type.
	 * @param string $branch_slug Nursery slug.
	 * @return string
	 */
	public static function recipient_for($type, $branch_slug = '') {
		if (defined('AM_OPS_NOTIFY_EMAIL') && is_email(AM_OPS_NOTIFY_EMAIL)) {
			return strtolower((string) AM_OPS_NOTIFY_EMAIL);
		}

		$settings    = self::get();
		$branch_slug = sanitize_title($branch_slug);

		// Nursery records are the client-editable source of truth for both the
		// public branch contact and selected-branch form notifications.
		if ($branch_slug && function_exists('am_get_nurseries')) {
			foreach (am_get_nurseries() as $nursery) {
				$nursery_slug = sanitize_title((string) ($nursery['id'] ?? ''));
				$nursery_email = self::email($nursery['email'] ?? '');
				if ($nursery_slug && hash_equals($nursery_slug, $branch_slug) && $nursery_email) {
					return $nursery_email;
				}
			}
		}
		// Retain the protected operations value only as a legacy fallback when a
		// branch record has no usable email.
		if (
			$branch_slug
			&& isset($settings['branch_recipients'][$branch_slug])
		) {
			return $settings['branch_recipients'][$branch_slug];
		}

		// The main Site Settings email is the client-editable destination for
		// enquiries that do not select a nursery, including Any/All Nurseries.
		if (function_exists('am_get_settings')) {
			$site_settings = am_get_settings();
			$site_email = self::email($site_settings['email'] ?? '');
			if ($site_email) {
				return $site_email;
			}
		}

		if (
			AM_Ops_Repository::TYPE_APPLICATION === $type
			&& is_email($settings['application_recipient'])
		) {
			return $settings['application_recipient'];
		}

		if (is_email($settings['default_recipient'])) {
			return $settings['default_recipient'];
		}

		return '';
	}

	/**
	 * @param mixed $candidate Candidate email.
	 * @return string
	 */
	private static function email($candidate) {
		$email = strtolower(sanitize_email((string) $candidate));

		return is_email($email) ? $email : '';
	}
}
