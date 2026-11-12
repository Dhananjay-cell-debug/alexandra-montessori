<?php
/**
 * Little Rainbows Day Nursery — Theme File Editor code sessions.
 *
 * Snapshots every file saved through Appearance -> Theme File Editor before
 * it is overwritten, and provides a small admin screen (Appearance -> Code
 * Sessions) to browse and restore those snapshots. Mirrors the Visual
 * Builder's "All saved sessions" pattern (see
 * ldrn-visual-builder/includes/class-ldrn-vb-saved-sessions.php) — same
 * "auto-backup the current state, then restore, then record the restore as
 * its own session" flow — but for raw theme files instead of page designs.
 */

if (!defined('ABSPATH')) {
	exit;
}

define('LDRN_CS_VERSION', '1.0.0');
define('LDRN_CS_PLUGIN_DIR', __DIR__);

require_once LDRN_CS_PLUGIN_DIR . '/includes/class-ldrn-code-sessions.php';

final class LDRN_Code_Sessions_Plugin {
	/**
	 * @return void
	 */
	public static function boot() {
		add_action('init', array('LDRN_Code_Sessions', 'register_post_type'), 8);
		add_action('load-theme-editor.php', array('LDRN_Code_Sessions', 'capture_before_save'), 1);
        add_action('wp_ajax_edit-theme-plugin-file', array('LDRN_Code_Sessions', 'capture_before_save'), 1);
		add_action('admin_menu', array('LDRN_Code_Sessions', 'register_menu'), 21);
		add_action('admin_post_ldrn_cs_restore', array('LDRN_Code_Sessions', 'handle_restore'));
		add_action('admin_post_ldrn_cs_delete', array('LDRN_Code_Sessions', 'handle_delete'));
	}
}

LDRN_Code_Sessions_Plugin::boot();
