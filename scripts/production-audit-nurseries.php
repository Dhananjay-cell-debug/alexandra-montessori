<?php
if (!defined('ABSPATH')) {
	exit(1);
}

$rows = array();
foreach (get_posts(array(
	'post_type'      => 'am_nursery',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
)) as $nursery) {
	$email = strtolower(sanitize_email((string) get_post_meta($nursery->ID, '_am_nursery_email', true)));
	$rows[] = array(
		'id'          => (int) $nursery->ID,
		'slug'        => (string) $nursery->post_name,
		'email_ready' => (bool) is_email($email),
		'email'       => $email,
		'issues'      => function_exists('am_nursery_readiness_issues')
			? am_nursery_readiness_issues($nursery->ID)
			: array('readiness checker unavailable'),
	);
}

echo wp_json_encode(array(
	'published' => count($rows),
	'api_ready' => function_exists('am_get_nurseries') ? count(am_get_nurseries()) : null,
	'nurseries' => $rows,
), JSON_UNESCAPED_SLASHES) . PHP_EOL;
