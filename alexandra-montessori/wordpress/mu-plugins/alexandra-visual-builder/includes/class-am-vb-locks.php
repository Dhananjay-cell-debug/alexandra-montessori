<?php
/**
 * Short-lived collaborative edit locks.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_VB_Locks {
	const META_KEY = '_am_vb_edit_lock';
	const TTL      = 120;

	/**
	 * @param int    $post_id    Page ID.
	 * @param string $session_id Editor session ID.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function acquire($post_id, $session_id) {
		if (!AM_VB_Features::enabled('locking')) {
			return self::empty_status();
		}
		$session_id = self::clean_session($session_id);
		if (!$session_id) {
			return new WP_Error('am_vb_invalid_session', 'A valid editor session is required.', array('status' => 400));
		}
		$current = self::raw($post_id);
		if (self::active($current)
			&& ($current['session'] ?? '') !== $session_id) {
			return new WP_Error(
				'am_vb_locked',
				'This page is currently being edited in another session.',
				array_merge(array('status' => 409), self::status($post_id, $session_id))
			);
		}
		$lock = array(
			'user'    => get_current_user_id(),
			'session' => $session_id,
			'time'    => time(),
		);
		update_post_meta($post_id, self::META_KEY, $lock);

		return self::status($post_id, $session_id);
	}

	/**
	 * @param int    $post_id    Page ID.
	 * @param string $session_id Editor session ID.
	 * @return true|WP_Error
	 */
	public static function assert_owned($post_id, $session_id) {
		if (!AM_VB_Features::enabled('locking')) {
			return true;
		}
		$current = self::raw($post_id);
		if (!self::active($current)
			|| (int) ($current['user'] ?? 0) !== get_current_user_id()
			|| ($current['session'] ?? '') !== self::clean_session($session_id)) {
			return new WP_Error(
				'am_vb_lock_required',
				'Your editing lock expired or belongs to another session. Reload before saving.',
				array_merge(array('status' => 409), self::status($post_id, $session_id))
			);
		}

		return true;
	}

	/**
	 * @param int    $post_id    Page ID.
	 * @param string $session_id Editor session ID.
	 * @return array<string,mixed>
	 */
	public static function release($post_id, $session_id) {
		$current = self::raw($post_id);
		if (($current['session'] ?? '') === self::clean_session($session_id)
			&& (int) ($current['user'] ?? 0) === get_current_user_id()) {
			delete_post_meta($post_id, self::META_KEY);
		}

		return self::status($post_id, $session_id);
	}

	/**
	 * @param int    $post_id    Page ID.
	 * @param string $session_id Current editor session.
	 * @return array<string,mixed>
	 */
	public static function status($post_id, $session_id = '') {
		$current = self::raw($post_id);
		if (!self::active($current)) {
			if ($current) {
				delete_post_meta($post_id, self::META_KEY);
			}
			return self::empty_status();
		}
		$user = get_userdata((int) ($current['user'] ?? 0));

		return array(
			'locked'    => true,
			'owned'     => ($current['session'] ?? '') === self::clean_session($session_id)
				&& (int) ($current['user'] ?? 0) === get_current_user_id(),
			'userId'    => (int) ($current['user'] ?? 0),
			'userName'  => $user ? $user->display_name : 'Another editor',
			'expiresIn' => max(0, self::TTL - (time() - (int) ($current['time'] ?? 0))),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function empty_status() {
		return array(
			'locked'    => false,
			'owned'     => !AM_VB_Features::enabled('locking'),
			'userId'    => 0,
			'userName'  => '',
			'expiresIn' => 0,
		);
	}

	/**
	 * @param int $post_id Page ID.
	 * @return array<string,mixed>
	 */
	private static function raw($post_id) {
		$value = get_post_meta($post_id, self::META_KEY, true);

		return is_array($value) ? $value : array();
	}

	/**
	 * @param array<string,mixed> $lock Lock.
	 * @return bool
	 */
	private static function active($lock) {
		return !empty($lock['time']) && (time() - (int) $lock['time']) < self::TTL;
	}

	/**
	 * @param string $session Session ID.
	 * @return string
	 */
	private static function clean_session($session) {
		$session = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $session);

		return strlen($session) >= 8 ? substr($session, 0, 80) : '';
	}
}
