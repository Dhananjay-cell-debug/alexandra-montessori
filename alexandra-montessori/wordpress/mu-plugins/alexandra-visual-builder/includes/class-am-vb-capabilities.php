<?php
/**
 * Dedicated visual-builder permissions.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_VB_Capabilities {
	const VERSION_OPTION = 'am_vb_capabilities_version';
	const VERSION        = '1';

	/**
	 * @return string[]
	 */
	public static function all() {
		return array(
			'edit_am_visual_pages',
			'publish_am_visual_pages',
			'manage_am_visual_templates',
			'view_am_visual_audit',
		);
	}

	/**
	 * @return void
	 */
	public static function maybe_install() {
		foreach (array('administrator', 'am_content_manager') as $role_name) {
			$role = get_role($role_name);
			if (!$role) {
				continue;
			}
			foreach (self::all() as $capability) {
				if (!$role->has_cap($capability)) {
					$role->add_cap($capability);
				}
			}
		}
		if (self::VERSION !== (string) get_option(self::VERSION_OPTION, '')) {
			update_option(self::VERSION_OPTION, self::VERSION, false);
		}
	}
}
