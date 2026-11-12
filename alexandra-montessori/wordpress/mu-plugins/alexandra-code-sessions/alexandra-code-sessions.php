<?php
/**
 * Alexandra Montessori — Theme File Editor code sessions.
 *
 * Snapshots every file saved through Appearance -> Theme File Editor before
 * it is overwritten, and provides a small admin screen (Appearance -> Code
 * Sessions) to browse and restore those snapshots. Mirrors the Visual
 * Builder's "All saved sessions" pattern (see
 * alexandra-visual-builder/includes/class-am-vb-saved-sessions.php) — same
 * "auto-backup the current state, then restore, then record the restore as
 * its own session" flow — but for raw theme files instead of page designs.
 */

if (!defined('ABSPATH')) {
	exit;
}

define('AM_CS_VERSION', '1.0.0');
define('AM_CS_PLUGIN_DIR', __DIR__);

require_once AM_CS_PLUGIN_DIR . '/includes/class-am-code-sessions.php';

final class AM_Code_Sessions_Plugin {
	/**
	 * @return void
	 */
	public static function boot() {
		add_action('init', array('AM_Code_Sessions', 'register_post_type'), 8);
		add_action('load-theme-editor.php', array('AM_Code_Sessions', 'capture_before_save'), 1);
        add_action('wp_ajax_edit-theme-plugin-file', array('AM_Code_Sessions', 'capture_before_save'), 1);
		add_action('admin_menu', array('AM_Code_Sessions', 'register_menu'), 21);
		add_action('admin_post_am_cs_restore', array('AM_Code_Sessions', 'handle_restore'));
		add_action('admin_post_am_cs_delete', array('AM_Code_Sessions', 'handle_delete'));
	}
}

AM_Code_Sessions_Plugin::boot();
