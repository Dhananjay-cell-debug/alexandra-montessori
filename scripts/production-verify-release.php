<?php
if (!defined('ABSPATH')) {
	exit(1);
}

global $wpdb;

$settings  = AM_Ops_Settings::get();
$schema    = AM_Ops_Schema::health();
$migration = get_option(AM_Ops_Migration::STATUS_OPTION, array());
$tables    = AM_Ops_Tables::all();
$counts    = array();

foreach ($tables as $logical_name => $table_name) {
	// Table names come only from the plugin's internal registry.
	$counts[$logical_name] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
}

$branch_routes = array();
foreach (get_posts(array(
	'post_type'      => 'am_nursery',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
)) as $nursery) {
	$slug     = sanitize_title((string) $nursery->post_name);
	$expected = strtolower(sanitize_email((string) get_post_meta(
		$nursery->ID,
		'_am_nursery_email',
		true
	)));
	$resolved = AM_Ops_Settings::recipient_for(AM_Ops_Repository::TYPE_CONTACT, $slug);

	$branch_routes[$slug] = (
		is_email($expected)
		&& is_email($resolved)
		&& hash_equals($expected, $resolved)
	);
}

$bcc_ready = false;
if (defined('AM_OPS_MONITOR_BCC') && is_email(AM_OPS_MONITOR_BCC)) {
	$headers_method = new ReflectionMethod('AM_Ops_Queue', 'notification_headers');
	$headers_method->setAccessible(true);
	$headers = (array) $headers_method->invoke(
		new AM_Ops_Queue(),
		(string) ($settings['default_recipient'] ?? '')
	);

	foreach ($headers as $header) {
		if (0 === stripos((string) $header, 'Bcc:')) {
			$bcc_ready = true;
			break;
		}
	}
}

$manifest_path = get_stylesheet_directory() . '/dist/.vite/manifest.json';
$manifest      = json_decode((string) file_get_contents($manifest_path), true);
$entry         = (string) ($manifest['index.html']['file'] ?? '');

$result = array(
	'theme_entry'             => basename($entry),
	'ops_version'             => defined('AM_OPS_VERSION') ? AM_OPS_VERSION : '',
	'schema_healthy'          => !empty($schema['healthy']),
	'schema_version'          => (string) get_option(AM_Ops_Schema::VERSION_OPTION, ''),
	'replacement_enabled'     => (bool) get_option('am_ops_replacement_enabled', false),
	'migration_state'         => (string) ($migration['state'] ?? ''),
	'reconciliation_healthy'  => !empty($migration['reconciliation']['healthy']),
	'legacy_source_total'     => (int) ($migration['source_total'] ?? 0),
	'table_counts'            => $counts,
	'notification_mode'       => (string) ($settings['notification_mode'] ?? ''),
	'digest_minutes'          => (int) ($settings['digest_minutes'] ?? 0),
	'central_recipient_ready' => false !== is_email($settings['default_recipient'] ?? ''),
	'branch_routes'           => $branch_routes,
	'monitor_bcc_ready'       => $bcc_ready,
	'test_override_empty'     => !defined('AM_TEST_NOTIFY_EMAIL') || '' === AM_TEST_NOTIFY_EMAIL,
	'nurseries_frontend_ready'=> function_exists('am_get_nurseries')
		? count(am_get_nurseries())
		: 0,
	'cron_scheduled'          => (bool) wp_next_scheduled(AM_Ops_Queue::CRON_HOOK),
	'maintenance_mode'        => file_exists(ABSPATH . '.maintenance'),
);

echo wp_json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
