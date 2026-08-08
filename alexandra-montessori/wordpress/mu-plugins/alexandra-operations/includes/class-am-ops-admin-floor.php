<?php
/**
 * Keeps at least two administrator accounts on the site at all times, so a
 * mistaken bulk delete or role change can never lock everyone out.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_Ops_Admin_Floor {
	const MINIMUM_ADMINISTRATORS = 2;

	/**
	 * @return void
	 */
	public static function register() {
		add_filter('map_meta_cap', array(__CLASS__, 'protect_administrator_floor'), 10, 4);
	}

	/**
	 * Denies deleting or changing the role of an administrator when doing so
	 * would drop the site below the minimum administrator count.
	 *
	 * @param string[] $caps Required primitive capabilities.
	 * @param string   $cap Requested meta capability.
	 * @param int      $user_id Acting user (unused; the rule applies to everyone, including other admins).
	 * @param array    $args Meta cap args; args[0] is the target user ID.
	 * @return string[]
	 */
	public static function protect_administrator_floor($caps, $cap, $user_id, $args) {
		if (!in_array($cap, array('delete_user', 'promote_user'), true) || empty($args[0])) {
			return $caps;
		}

		$target_id = (int) $args[0];
		if (!user_can($target_id, 'administrator')) {
			return $caps;
		}

		if (self::administrator_count() - 1 < self::MINIMUM_ADMINISTRATORS) {
			return array('do_not_allow');
		}

		return $caps;
	}

	/**
	 * @return int Current number of accounts holding the administrator role.
	 */
	private static function administrator_count() {
		return count(get_users(array(
			'role'   => 'administrator',
			'fields' => 'ID',
		)));
	}
}
