<?php
/**
 * Little Rainbows Day Nursery operations platform.
 *
 * This must-use plugin owns durable form ingestion, storage, workflow,
 * notifications, private-file metadata and the submissions command centre.
 */

if (!defined('ABSPATH')) {
	exit;
}

define('LDRN_OPS_VERSION', '0.2.2');
define('LDRN_OPS_SCHEMA_VERSION', '3');
define('LDRN_OPS_PLUGIN_FILE', __FILE__);
define('LDRN_OPS_PLUGIN_DIR', __DIR__);
define('LDRN_OPS_ACTIVE', true);
define(
	'LDRN_OPS_REPLACES_LEGACY',
	(bool) get_option('ldrn_ops_replacement_enabled', false)
);

if (LDRN_OPS_REPLACES_LEGACY && !defined('LDRN_SUBMISSIONS_MENU_SLUG')) {
	define('LDRN_SUBMISSIONS_MENU_SLUG', 'ldrn_submissions');
}

require_once LDRN_OPS_PLUGIN_DIR . '/includes/class-ldrn-ops-tables.php';
require_once LDRN_OPS_PLUGIN_DIR . '/includes/class-ldrn-ops-schema.php';
require_once LDRN_OPS_PLUGIN_DIR . '/includes/class-ldrn-ops-capabilities.php';
require_once LDRN_OPS_PLUGIN_DIR . '/includes/class-ldrn-ops-repository.php';
require_once LDRN_OPS_PLUGIN_DIR . '/includes/class-ldrn-ops-migration.php';
require_once LDRN_OPS_PLUGIN_DIR . '/includes/class-ldrn-ops-settings.php';
require_once LDRN_OPS_PLUGIN_DIR . '/includes/class-ldrn-ops-security.php';
require_once LDRN_OPS_PLUGIN_DIR . '/includes/class-ldrn-ops-queue.php';
require_once LDRN_OPS_PLUGIN_DIR . '/includes/class-ldrn-ops-files.php';
require_once LDRN_OPS_PLUGIN_DIR . '/includes/class-ldrn-ops-ingestion.php';
require_once LDRN_OPS_PLUGIN_DIR . '/includes/class-ldrn-ops-admin.php';
require_once LDRN_OPS_PLUGIN_DIR . '/includes/class-ldrn-ops-password-lockdown.php';
require_once LDRN_OPS_PLUGIN_DIR . '/includes/class-ldrn-ops-admin-floor.php';
require_once LDRN_OPS_PLUGIN_DIR . '/includes/class-ldrn-ops-user-provisioning.php';

/**
 * Boots components that are safe before the legacy theme integration is
 * switched over. Public ingestion and admin replacement are enabled in later
 * verified phases.
 */
final class LDRN_Ops_Plugin {
	/**
	 * Register the initial schema and permissions layer.
	 *
	 * @return void
	 */
	public static function boot() {
		add_action('init', array('LDRN_Ops_Schema', 'maybe_install'), 1);
		add_action('init', array('LDRN_Ops_Capabilities', 'maybe_install'), 2);
		LDRN_Ops_Queue::register();
		LDRN_Ops_Files::register();
		LDRN_Ops_Password_Lockdown::register();
		LDRN_Ops_Admin_Floor::register();
		LDRN_Ops_User_Provisioning::register();
		if (LDRN_OPS_REPLACES_LEGACY) {
			LDRN_Ops_Ingestion::register();
			LDRN_Ops_Admin::register();
		}
	}
}

LDRN_Ops_Plugin::boot();

if (LDRN_OPS_REPLACES_LEGACY && !function_exists('ldrn_submission_unhandled_total')) {
	/**
	 * Compatibility for the existing restricted-role dashboard card.
	 *
	 * @return int
	 */
	function ldrn_submission_unhandled_total() {
		$metrics = (new LDRN_Ops_Repository())->overview_metrics();

		return (int) ($metrics['new_count'] ?? 0);
	}
}
