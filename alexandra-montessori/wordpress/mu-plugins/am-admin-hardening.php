<?php
/**
 * Plugin Name: Alexandra Admin Hardening
 * Description: Security + admin cleanup for the Alexandra Montessori site (must-use, cannot be deactivated from the dashboard).
 * Author: Alexandra build
 */

if (!defined('ABSPATH')) { exit; }

// ---------------------------------------------------------------------------
// Security: disable XML-RPC — a common brute-force / amplification vector and
// unused by this site (no remote publishing / Jetpack).
// ---------------------------------------------------------------------------
add_filter('xmlrpc_enabled', '__return_false');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');

// ---------------------------------------------------------------------------
// Admin cleanup: hide menus this site never uses (no blog Posts, no Comments)
// so Editors see a clean, unconfusing dashboard.
// ---------------------------------------------------------------------------
add_action('admin_menu', function () {
    remove_menu_page('edit.php');          // Posts
    remove_menu_page('edit-comments.php'); // Comments
}, 999);

add_action('admin_bar_menu', function ($bar) {
    $bar->remove_node('new-post');
    $bar->remove_node('comments');
}, 999);
