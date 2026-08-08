<?php
/**
 * One-time, idempotent production activation for Alexandra Operations.
 *
 * Run through `wp eval-file` after the coordinated theme/MU-plugin release.
 * No email is sent by this script.
 */

if (!defined('ABSPATH')) {
	fwrite(STDERR, "WordPress did not bootstrap.\n");
	exit(1);
}

if (
	!class_exists('AM_Ops_Schema')
	|| !class_exists('AM_Ops_Migration')
	|| !class_exists('AM_Ops_Settings')
) {
	fwrite(STDERR, "Alexandra Operations did not load.\n");
	exit(1);
}

if (!AM_Ops_Schema::maybe_install()) {
	fwrite(STDERR, "Operations schema installation failed.\n");
	exit(1);
}

AM_Ops_Capabilities::maybe_install();

$nursery_defaults = array(
	'hounslow' => array(
		'_am_nursery_address'    => 'Ved Court, Alexandra Road, Hounslow',
		'_am_nursery_age_range'  => '6 months to 5 years',
		'_am_nursery_short'      => 'Montessori-inspired practice with a caring, family feel at the heart of Hounslow.',
		'_am_nursery_welcome'    => 'Our Hounslow nursery blends Montessori-inspired practice with a caring, family feel. Natural materials and consistent key-person care help children grow in a safe, well-prepared setting.',
		'_am_nursery_hero_image' => home_url('/wp-content/themes/alexandra-theme/dist/assets/organisation/classroom-main.webp'),
	),
	'heston' => array(
		'_am_nursery_address'    => '36 Springwell Road, Hounslow',
		'_am_nursery_age_range'  => '6 months to 5 years',
		'_am_nursery_short'      => 'A warm, welcoming home for early learners, with spacious studios and a secure garden.',
		'_am_nursery_welcome'    => 'Our Heston nursery is a warm, welcoming home for early learners. Thoughtfully prepared environments, home-cooked meals and a secure garden help every child build confidence and independence at their own pace.',
		'_am_nursery_hero_image' => home_url('/wp-content/themes/alexandra-theme/dist/assets/organisation/friends-two.webp'),
	),
	'hammersmith' => array(
		'_am_nursery_age_range' => '12 months to 5 years',
		'_am_nursery_short'     => 'Montessori-inspired care moments from Ravenscourt Park, with bright, natural-light studios.',
		'_am_nursery_welcome'   => 'Just a short walk from Ravenscourt Park, our Hammersmith nursery offers calm, prepared Montessori environments and natural resources that support independence, confidence and purposeful early learning.',
	),
);

$central_email    = '';
$branch_recipients = array();
$published_count  = 0;

foreach (get_posts(array(
	'post_type'      => 'am_nursery',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
)) as $nursery_post) {
	$published_count++;
	$slug = sanitize_title((string) $nursery_post->post_name);

	foreach ((array) ($nursery_defaults[$slug] ?? array()) as $meta_key => $default_value) {
		if ('' === trim((string) get_post_meta($nursery_post->ID, $meta_key, true))) {
			update_post_meta($nursery_post->ID, $meta_key, $default_value);
		}
	}

	$email = strtolower(sanitize_email((string) get_post_meta(
		$nursery_post->ID,
		'_am_nursery_email',
		true
	)));
	if ($slug && is_email($email)) {
		$branch_recipients[$slug] = $email;
	}

	if ('hounslow' === $slug && is_email($email)) {
		$central_email = $email;
	}
}

if (!$central_email && function_exists('am_get_settings')) {
	$site_settings = am_get_settings();
	$candidate     = strtolower(sanitize_email((string) ($site_settings['email'] ?? '')));

	if (is_email($candidate)) {
		$central_email = $candidate;
	}
}

if (!is_email($central_email)) {
	fwrite(STDERR, "No approved central client recipient could be resolved.\n");
	exit(1);
}

$site_settings = get_option('am_settings', array());
if (!is_array($site_settings)) {
	$site_settings = array();
}
$site_settings['email'] = $central_email;
update_option('am_settings', $site_settings, false);

update_option(
	AM_Ops_Settings::OPTION,
	array(
		'notification_mode'     => 'digest',
		'digest_minutes'        => 30,
		'default_recipient'     => $central_email,
		'application_recipient' => $central_email,
		'branch_recipients'     => $branch_recipients,
		'retention_days'        => 0,
	),
	false
);

$ready_count = function_exists('am_get_nurseries') ? count(am_get_nurseries()) : 0;
if ($published_count < 3 || $ready_count < 3 || count($branch_recipients) < 3) {
	fwrite(STDERR, "The three production nursery records are not frontend/routing ready.\n");
	exit(1);
}

$migration = (new AM_Ops_Migration())->run(250);
if (is_wp_error($migration)) {
	fwrite(STDERR, $migration->get_error_code() . ": " . $migration->get_error_message() . "\n");
	exit(1);
}

$reconciliation = (array) ($migration['reconciliation'] ?? array());
if (
	'verified' !== ($migration['state'] ?? '')
	|| empty($reconciliation['healthy'])
	|| !empty($migration['errors'])
) {
	fwrite(STDERR, "Legacy submission reconciliation was not healthy.\n");
	exit(1);
}

update_option('am_ops_replacement_enabled', true, false);

$schema   = AM_Ops_Schema::health();
$settings = AM_Ops_Settings::get();
$result   = array(
	'schema_healthy'          => !empty($schema['healthy']),
	'schema_version'          => (string) get_option(AM_Ops_Schema::VERSION_OPTION, ''),
	'migration_state'         => (string) ($migration['state'] ?? ''),
	'source_total'            => (int) ($migration['source_total'] ?? 0),
	'processed'               => (int) ($migration['processed'] ?? 0),
	'created'                 => (int) ($migration['created'] ?? 0),
	'already_migrated'        => (int) ($migration['already_migrated'] ?? 0),
	'destination_total'       => (int) ($reconciliation['destination_total'] ?? 0),
	'reconciliation_healthy'  => !empty($reconciliation['healthy']),
	'replacement_enabled'     => (bool) get_option('am_ops_replacement_enabled', false),
	'notification_mode'       => (string) ($settings['notification_mode'] ?? ''),
	'central_recipient_ready' => false !== is_email($settings['default_recipient'] ?? ''),
	'branch_recipients_ready' => count($branch_recipients),
	'nurseries_frontend_ready'=> $ready_count,
);

echo wp_json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
