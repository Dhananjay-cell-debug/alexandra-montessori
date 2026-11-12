<?php
/** Plugin Name: Alexandra Content List Columns
 * Keeps the existing editor screens; removes the requested list-only columns.
 */
defined('ABSPATH') || exit;
add_filter('manage_post_posts_columns', function ($columns) {
    foreach (array_keys($columns) as $key) if (strpos($key, 'wpseo-') === 0) unset($columns[$key]);
    return $columns;
}, 1000);
foreach (array('am_event', 'am_testimonial') as $type) {
    add_filter('manage_' . $type . '_posts_columns', function ($columns) {
        unset($columns['am_website_result']);
        return $columns;
    }, 1000);
}
