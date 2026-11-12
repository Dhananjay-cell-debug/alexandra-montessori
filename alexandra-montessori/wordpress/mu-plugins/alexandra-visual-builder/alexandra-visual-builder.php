<?php
/**
 * Alexandra Montessori visual site builder.
 *
 * The builder is deliberately isolated from the public React data contract.
 * It is enabled automatically only in local/development environments or when
 * AM_VISUAL_BUILDER_POC_ENABLED is explicitly set to true.
 */

if (!defined('ABSPATH')) {
	exit;
}

define('AM_VB_VERSION', '0.9.23');
define('AM_VB_PLUGIN_DIR', __DIR__);
define('AM_VB_PLUGIN_URL', content_url('mu-plugins/alexandra-visual-builder/'));

require_once AM_VB_PLUGIN_DIR . '/includes/class-am-vb-style-schema.php';
require_once AM_VB_PLUGIN_DIR . '/includes/class-am-vb-features.php';
require_once AM_VB_PLUGIN_DIR . '/includes/class-am-vb-capabilities.php';
require_once AM_VB_PLUGIN_DIR . '/includes/class-am-vb-access.php';
require_once AM_VB_PLUGIN_DIR . '/includes/class-am-vb-media.php';
require_once AM_VB_PLUGIN_DIR . '/includes/class-am-vb-audit.php';
require_once AM_VB_PLUGIN_DIR . '/includes/class-am-vb-locks.php';
require_once AM_VB_PLUGIN_DIR . '/includes/class-am-vb-document.php';
require_once AM_VB_PLUGIN_DIR . '/includes/class-am-vb-saved-sessions.php';
require_once AM_VB_PLUGIN_DIR . '/includes/class-am-vb-template.php';
require_once AM_VB_PLUGIN_DIR . '/includes/class-am-vb-home-design.php';
require_once AM_VB_PLUGIN_DIR . '/includes/class-am-vb-site-design.php';
require_once AM_VB_PLUGIN_DIR . '/includes/class-am-vb-organizer.php';
require_once AM_VB_PLUGIN_DIR . '/includes/class-am-vb-rest.php';
require_once AM_VB_PLUGIN_DIR . '/includes/class-am-vb-admin.php';

final class AM_VB_Plugin {
	/**
	 * @return bool
	 */
	public static function enabled() {
		if (defined('AM_VISUAL_BUILDER_POC_ENABLED')) {
			return (bool) AM_VISUAL_BUILDER_POC_ENABLED;
		}

		$environment = function_exists('wp_get_environment_type')
			? wp_get_environment_type()
			: 'production';
		$host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);

		return in_array($environment, array('local', 'development'), true)
			|| 'localhost' === $host
			|| (bool) preg_match('/\.local$/i', $host);
	}

	/**
	 * @return void
	 */
	public static function boot() {
		if (!self::enabled()) {
			return;
		}

		// 2026-10-17: the Visual Builder editor is retired - the Site Editor
		// (plugins/alexandra-workspace, ported from LDRN) replaces it, and its
		// saved sessions now live in All saved sessions. Only the renderer of
		// the layout it saved stays, so visitors see the site exactly as before.
		// No menu, editor screen, REST routes, preview or user allocation.
		add_action('init', array('AM_VB_Document', 'register_post_type'), 5);
		add_action('init', array('AM_VB_Audit', 'register_post_type'), 7);
		add_action('init', array('AM_VB_Saved_Sessions', 'register_post_type'), 8);
		add_action('wp_enqueue_scripts', array('AM_VB_Style_Schema', 'enqueue_frontend'), 28);
		add_action('wp_enqueue_scripts', array('AM_VB_Home_Design', 'localize_frontend'), 29);
		add_action('wp_enqueue_scripts', array('AM_VB_Site_Design', 'localize_frontend'), 29);
	}
}

AM_VB_Plugin::boot();
