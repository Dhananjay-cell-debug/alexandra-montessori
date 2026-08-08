<?php
/**
 * Dedicated permissions for the operations platform.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_Ops_Capabilities {
	const VERSION_OPTION = 'am_ops_capabilities_version';
	const VERSION        = '1';

	/**
	 * Capabilities intentionally exclude permanent record deletion.
	 *
	 * @return string[]
	 */
	public static function all() {
		return array(
			'manage_am_submissions',
			'reply_am_submissions',
			'export_am_submissions',
			'view_am_private_files',
			'manage_am_ops_settings',
		);
	}

	/**
	 * Add operations capabilities to the two intended staff roles.
	 *
	 * @return void
	 */
	public static function maybe_install() {
		if (self::VERSION === (string) get_option(self::VERSION_OPTION, '')) {
			return;
		}

		$roles = array('administrator', 'am_content_manager');
		foreach ($roles as $role_name) {
			$role = get_role($role_name);
			if (!$role) {
				continue;
			}

			foreach (self::all() as $capability) {
				$role->add_cap($capability);
			}
		}

		update_option(self::VERSION_OPTION, self::VERSION, false);
	}
}
