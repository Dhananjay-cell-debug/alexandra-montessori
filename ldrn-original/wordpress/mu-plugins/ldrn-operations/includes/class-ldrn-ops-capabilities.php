<?php
/**
 * Dedicated permissions for the operations platform.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class LDRN_Ops_Capabilities {
	const VERSION_OPTION = 'ldrn_ops_capabilities_version';
	const VERSION        = '1';

	/**
	 * Capabilities intentionally exclude permanent record deletion.
	 *
	 * @return string[]
	 */
	public static function all() {
		return array(
			'manage_ldrn_submissions',
			'reply_ldrn_submissions',
			'export_ldrn_submissions',
			'view_ldrn_private_files',
			'manage_ldrn_ops_settings',
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

		$roles = array('administrator', 'ldrn_content_manager');
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
