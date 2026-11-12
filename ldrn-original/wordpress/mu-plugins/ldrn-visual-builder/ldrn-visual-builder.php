<?php
/**
 * Little Rainbows Day Nursery visual site builder.
 *
 * The builder is deliberately isolated from the public React data contract.
 * It is enabled automatically only in local/development environments or when
 * AM_VISUAL_BUILDER_POC_ENABLED is explicitly set to true.
 */

if (!defined('ABSPATH')) {
	exit;
}

define('LDRN_VB_VERSION', '0.9.23');
define('LDRN_VB_PLUGIN_DIR', __DIR__);
define('LDRN_VB_PLUGIN_URL', content_url('mu-plugins/ldrn-visual-builder/'));

require_once LDRN_VB_PLUGIN_DIR . '/includes/class-ldrn-vb-style-schema.php';
require_once LDRN_VB_PLUGIN_DIR . '/includes/class-ldrn-vb-features.php';
require_once LDRN_VB_PLUGIN_DIR . '/includes/class-ldrn-vb-capabilities.php';
require_once LDRN_VB_PLUGIN_DIR . '/includes/class-ldrn-vb-access.php';
require_once LDRN_VB_PLUGIN_DIR . '/includes/class-ldrn-vb-media.php';
require_once LDRN_VB_PLUGIN_DIR . '/includes/class-ldrn-vb-audit.php';
require_once LDRN_VB_PLUGIN_DIR . '/includes/class-ldrn-vb-locks.php';
require_once LDRN_VB_PLUGIN_DIR . '/includes/class-ldrn-vb-document.php';
require_once LDRN_VB_PLUGIN_DIR . '/includes/class-ldrn-vb-saved-sessions.php';
require_once LDRN_VB_PLUGIN_DIR . '/includes/class-ldrn-vb-template.php';
require_once LDRN_VB_PLUGIN_DIR . '/includes/class-ldrn-vb-home-design.php';
require_once LDRN_VB_PLUGIN_DIR . '/includes/class-ldrn-vb-site-design.php';
require_once LDRN_VB_PLUGIN_DIR . '/includes/class-ldrn-vb-organizer.php';
require_once LDRN_VB_PLUGIN_DIR . '/includes/class-ldrn-vb-rest.php';
require_once LDRN_VB_PLUGIN_DIR . '/includes/class-ldrn-vb-admin.php';

final class LDRN_VB_Plugin {
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

		add_action('init', array('LDRN_VB_Capabilities', 'maybe_install'), 3);
		LDRN_VB_Access::boot();
		add_action('init', array('LDRN_VB_Document', 'register_post_type'), 5);
		add_action('init', array('LDRN_VB_Template', 'register_post_type'), 6);
		add_action('init', array('LDRN_VB_Audit', 'register_post_type'), 7);
		add_action('init', array('LDRN_VB_Saved_Sessions', 'register_post_type'), 8);
		add_action('init', array('LDRN_VB_Document', 'maybe_migrate'), 25);
		add_action('init', array('LDRN_VB_Document', 'maybe_seed'), 30);
		add_action('init', array('LDRN_VB_Site_Design', 'maybe_seed_pages'), 31);
		add_action('rest_api_init', array('LDRN_VB_REST', 'register_routes'));
		add_action('rest_api_init', array('LDRN_VB_Home_Design', 'register_routes'));
		add_action('rest_api_init', array('LDRN_VB_Site_Design', 'register_routes'));
		add_action('rest_api_init', array('LDRN_VB_Organizer', 'register_routes'));
		add_action('rest_api_init', array('LDRN_VB_Saved_Sessions', 'register_routes'));
		add_action('admin_menu', array('LDRN_VB_Admin', 'register_menu'), 20);
		add_action('admin_enqueue_scripts', array('LDRN_VB_Admin', 'enqueue_assets'));
		add_action('admin_footer-toplevel_page_ldrn_content', array('LDRN_VB_Admin', 'render_workspace_entry'));
		add_action('template_redirect', array('LDRN_VB_Admin', 'maybe_render_preview'), 0);
		add_action('template_redirect', array('LDRN_VB_Saved_Sessions', 'authorize_preview'), -5);
		add_action('wp_enqueue_scripts', array('LDRN_VB_Style_Schema', 'enqueue_frontend'), 28);
		add_action('wp_enqueue_scripts', array('LDRN_VB_Home_Design', 'localize_frontend'), 29);
		add_action('wp_enqueue_scripts', array('LDRN_VB_Site_Design', 'localize_frontend'), 29);
		add_action('wp_enqueue_scripts', array('LDRN_VB_Admin', 'enqueue_preview_assets'), 30);
		add_action('wp_footer', array('LDRN_VB_Saved_Sessions', 'render_preview_banner'), 100);
	}
}

LDRN_VB_Plugin::boot();
