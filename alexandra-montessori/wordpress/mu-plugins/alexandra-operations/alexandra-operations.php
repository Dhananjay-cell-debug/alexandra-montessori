<?php
/**
 * Alexandra Montessori operations platform.
 *
 * This must-use plugin owns durable form ingestion, storage, workflow,
 * notifications, private-file metadata and the submissions command centre.
 */

if (!defined('ABSPATH')) {
	exit;
}

define('AM_OPS_VERSION', '0.2.2');
define('AM_OPS_SCHEMA_VERSION', '3');
define('AM_OPS_PLUGIN_FILE', __FILE__);
define('AM_OPS_PLUGIN_DIR', __DIR__);
define('AM_OPS_ACTIVE', true);
define(
	'AM_OPS_REPLACES_LEGACY',
	(bool) get_option('am_ops_replacement_enabled', false)
);

if (AM_OPS_REPLACES_LEGACY && !defined('AM_SUBMISSIONS_MENU_SLUG')) {
	define('AM_SUBMISSIONS_MENU_SLUG', 'am_submissions');
}

require_once AM_OPS_PLUGIN_DIR . '/includes/class-am-ops-tables.php';
require_once AM_OPS_PLUGIN_DIR . '/includes/class-am-ops-schema.php';
require_once AM_OPS_PLUGIN_DIR . '/includes/class-am-ops-capabilities.php';
require_once AM_OPS_PLUGIN_DIR . '/includes/class-am-ops-repository.php';
require_once AM_OPS_PLUGIN_DIR . '/includes/class-am-ops-migration.php';
require_once AM_OPS_PLUGIN_DIR . '/includes/class-am-ops-settings.php';
require_once AM_OPS_PLUGIN_DIR . '/includes/class-am-ops-security.php';
require_once AM_OPS_PLUGIN_DIR . '/includes/class-am-ops-queue.php';
require_once AM_OPS_PLUGIN_DIR . '/includes/class-am-ops-files.php';
require_once AM_OPS_PLUGIN_DIR . '/includes/class-am-ops-ingestion.php';
require_once AM_OPS_PLUGIN_DIR . '/includes/class-am-ops-admin.php';
require_once AM_OPS_PLUGIN_DIR . '/includes/class-am-ops-password-lockdown.php';
require_once AM_OPS_PLUGIN_DIR . '/includes/class-am-ops-admin-floor.php';
require_once AM_OPS_PLUGIN_DIR . '/includes/class-am-ops-user-provisioning.php';

/**
 * Boots components that are safe before the legacy theme integration is
 * switched over. Public ingestion and admin replacement are enabled in later
 * verified phases.
 */
final class AM_Ops_Plugin {
	/**
	 * Register the initial schema and permissions layer.
	 *
	 * @return void
	 */
	public static function boot() {
		add_action('init', array('AM_Ops_Schema', 'maybe_install'), 1);
		add_action('init', array('AM_Ops_Capabilities', 'maybe_install'), 2);
		AM_Ops_Queue::register();
		AM_Ops_Files::register();
		AM_Ops_Password_Lockdown::register();
		AM_Ops_Admin_Floor::register();
		AM_Ops_User_Provisioning::register();
		if (AM_OPS_REPLACES_LEGACY) {
			AM_Ops_Ingestion::register();
			AM_Ops_Admin::register();
		}
	}
}

AM_Ops_Plugin::boot();

if (AM_OPS_REPLACES_LEGACY && !function_exists('am_submission_unhandled_total')) {
	/**
	 * Compatibility for the existing restricted-role dashboard card.
	 *
	 * @return int
	 */
	function am_submission_unhandled_total() {
		$metrics = (new AM_Ops_Repository())->overview_metrics();

		return (int) ($metrics['new_count'] ?? 0);
	}
}
