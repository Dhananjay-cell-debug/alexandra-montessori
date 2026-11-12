<?php
/** Plugin Name: Little Rainbows Theme Editor Access
 * Restores only theme editing for existing administrators.
 */
defined('ABSPATH') || exit;
add_filter('map_meta_cap', function ($caps, $cap, $user_id, $args) {
    if ($cap !== 'edit_themes' || is_multisite()) return $caps;
    $user = get_userdata($user_id);
    if (!$user || !in_array('administrator', (array) $user->roles, true) || empty($user->allcaps['edit_themes'])) return $caps;
    return array('edit_themes');
}, PHP_INT_MAX, 4);
