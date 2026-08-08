<?php
/**
 * Reversible Local verification for future Nursery and Testimonial records.
 *
 * Creates prefix-locked synthetic WordPress posts, proves collection,
 * pagination, routing and readiness behaviour, then permanently removes only
 * the records carrying this run's unique verifier token.
 *
 * Usage:
 * php scripts/verify-cms-collections.php "C:\path\to\wordpress\public"
 */

if (PHP_SAPI !== 'cli') {
	exit(1);
}

$wordpress_root = isset($argv[1]) ? rtrim((string) $argv[1], '/\\') : '';
if ('' === $wordpress_root || !is_file($wordpress_root . '/wp-load.php')) {
	fwrite(STDERR, "Pass a valid WordPress root containing wp-load.php.\n");
	exit(2);
}

require_once $wordpress_root . '/wp-load.php';

$host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
if (!str_ends_with($host, '.local')) {
	fwrite(STDERR, "Refusing to create verifier records on a non-Local site: {$host}\n");
	exit(2);
}

global $wpdb;

$passed = 0;
$failed = 0;
$created_ids = [];
$token = 'am-cms-' . bin2hex(random_bytes(8));
$short_token = substr(str_replace('am-cms-', '', $token), 0, 8);

$check = static function ($condition, $label, $details = '') use (&$passed, &$failed) {
	if ($condition) {
		$passed++;
		echo "[PASS] {$label}\n";
		return;
	}

	$failed++;
	echo "[FAIL] {$label}";
	if ('' !== $details) echo ": {$details}";
	echo "\n";
};

$create_post = static function ($post_type, $title, $menu_order = 0) use (&$created_ids, $token) {
	$post_id = wp_insert_post(
		[
			'post_type'   => $post_type,
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_name'   => sanitize_title($title),
			'menu_order'  => (int) $menu_order,
		],
		true
	);
	if (is_wp_error($post_id)) {
		throw new RuntimeException($post_id->get_error_message());
	}
	$post_id = (int) $post_id;
	update_post_meta($post_id, '_am_verifier_token', $token);
	$created_ids[] = $post_id;
	return $post_id;
};

$baseline_nurseries = function_exists('am_get_nurseries') ? am_get_nurseries() : [];
$baseline_nursery_slugs = array_column($baseline_nurseries, 'id');
$baseline_testimonial_total = function_exists('am_testimonial_query')
	? (int) am_testimonial_query(1, 24)['total']
	: -1;
$ops_settings_before = class_exists('AM_Ops_Settings')
	? get_option(AM_Ops_Settings::OPTION, null)
	: null;

try {
	$check(function_exists('am_get_nurseries'), 'Nursery CMS collection function is loaded');
	$check(function_exists('am_testimonial_query'), 'Testimonial archive query is loaded');
	$check(function_exists('am_content_website_result'), 'Admin Website result contract is loaded');
	$check(function_exists('am_nursery_location_options'), 'Dynamic Job nursery options are loaded');
	if (!$baseline_nurseries) {
		throw new RuntimeException('No existing ready Nursery is available to supply a controlled test image.');
	}

	$hero_image = (string) ($baseline_nurseries[0]['image'] ?? '');
	if ('' === $hero_image) {
		throw new RuntimeException('The existing ready Nursery has no hero image.');
	}

	$incomplete_nursery_id = $create_post(
		'am_nursery',
		"AM CMS verifier incomplete Nursery {$short_token}",
		998
	);
	$incomplete_nursery_slug = get_post_field('post_name', $incomplete_nursery_id);
	$incomplete_nursery_result = am_content_website_result($incomplete_nursery_id);
	$check(
		!am_nursery_is_frontend_ready($incomplete_nursery_id),
		'Incomplete published Nursery fails the frontend readiness contract'
	);
	$check(
		false === $incomplete_nursery_result['visible']
			&& str_starts_with($incomplete_nursery_result['label'], 'Hidden: missing '),
		'Incomplete Nursery receives an exact hidden reason in wp-admin',
		$incomplete_nursery_result['label']
	);
	$check(
		!in_array($incomplete_nursery_slug, array_column(am_get_nurseries(), 'id'), true),
		'Incomplete published Nursery does not leak into the public collection'
	);

	$nursery_name = "Verification Nursery {$short_token}";
	$ready_nursery_id = $create_post('am_nursery', $nursery_name, 999);
	$nursery_meta = [
		'_am_nursery_area'          => 'Verification area',
		'_am_nursery_address'       => '1 Verification Road, London',
		'_am_nursery_postcode'      => 'W1 1AA',
		'_am_nursery_phone'         => '020 7946 0123',
		'_am_nursery_email'         => "verification-{$short_token}@example.com",
		'_am_nursery_hours'         => 'Monday to Friday, 8:00am to 6:00pm',
		'_am_nursery_age_range'     => 'Ages 6 months to 5 years',
		'_am_nursery_short'         => 'A temporary complete Nursery used only for automated Local verification.',
		'_am_nursery_welcome'       => 'This record proves that a complete new Nursery reaches every CMS-driven public destination without a code change.',
		'_am_nursery_hero_image'    => $hero_image,
		'_am_nursery_hero_tagline'  => 'A safe temporary verification branch',
		'_am_nursery_subheading'    => 'Automatically supplied by WordPress',
	];
	foreach ($nursery_meta as $key => $value) {
		update_post_meta($ready_nursery_id, $key, $value);
	}
	clean_post_cache($ready_nursery_id);

	$ready_nursery_slug = get_post_field('post_name', $ready_nursery_id);
	$nurseries_with_fixture = am_get_nurseries();
	$fixture_nurseries = array_values(
		array_filter(
			$nurseries_with_fixture,
			static fn($nursery) => ($nursery['id'] ?? '') === $ready_nursery_slug
		)
	);
	$ready_nursery_result = am_content_website_result($ready_nursery_id);
	$check(am_nursery_is_frontend_ready($ready_nursery_id), 'Complete fourth Nursery passes readiness');
	$check(
		count($nurseries_with_fixture) === count($baseline_nurseries) + 1,
		'Complete fourth Nursery increases the public collection by exactly one'
	);
	$check(
		1 === count($fixture_nurseries)
			&& ($fixture_nurseries[0]['name'] ?? '') === $nursery_name
			&& ($fixture_nurseries[0]['email'] ?? '') === $nursery_meta['_am_nursery_email'],
		'Fourth Nursery public data preserves its unique slug, name and branch email'
	);
	$check(
		true === $ready_nursery_result['visible']
			&& 'Live in Nursery directory and detail page' === $ready_nursery_result['label'],
		'Complete Nursery wp-admin result names its real public destinations',
		$ready_nursery_result['label']
	);

	$job_location_options = am_nursery_location_options();
	$check(
		isset($job_location_options[$nursery_name], $job_location_options['All Nurseries']),
		'New Nursery automatically enters the Job location selector'
	);
	$branch_route = [];
	if (class_exists('AM_Ops_Ingestion')) {
		$branch_method = new ReflectionMethod('AM_Ops_Ingestion', 'branch');
		$branch_method->setAccessible(true);
		$branch_route = $branch_method->invoke(null, $ready_nursery_slug, true);
	}
	$check(
		($branch_route['slug'] ?? '') === $ready_nursery_slug
			&& ($branch_route['label'] ?? '') === $nursery_name,
		'New Nursery automatically enters contact and visit form routing',
		wp_json_encode(
			[
				'slug'     => $ready_nursery_slug,
				'expected' => [
					'slug'  => $ready_nursery_slug,
					'label' => $nursery_name,
				],
				'actual'   => $branch_route,
			]
		)
	);
	if (class_exists('AM_Ops_Settings')) {
		update_option(
			AM_Ops_Settings::OPTION,
			[
				'notification_mode' => 'digest',
				'digest_minutes'    => 30,
			],
			false
		);
	}
	$dynamic_recipient = class_exists('AM_Ops_Settings')
		? AM_Ops_Settings::recipient_for(AM_Ops_Repository::TYPE_CONTACT, $ready_nursery_slug)
		: '';
	$check(
		$dynamic_recipient === $nursery_meta['_am_nursery_email'],
		'New Nursery automatically routes operational alerts to its CMS branch email'
	);

	$incomplete_testimonial_id = $create_post(
		'am_testimonial',
		"AM CMS verifier incomplete Testimonial {$short_token}",
		998
	);
	update_post_meta($incomplete_testimonial_id, '_am_testimonial_quote', 'Incomplete verifier quote.');
	$incomplete_testimonial_result = am_content_website_result($incomplete_testimonial_id);
	$check(
		!am_testimonial_is_frontend_ready($incomplete_testimonial_id),
		'Incomplete published Testimonial fails the frontend readiness contract'
	);
	$check(
		false === $incomplete_testimonial_result['visible']
			&& str_contains($incomplete_testimonial_result['label'], 'parent descriptor')
			&& str_contains($incomplete_testimonial_result['label'], 'nursery location'),
		'Incomplete Testimonial receives precise hidden reasons in wp-admin',
		$incomplete_testimonial_result['label']
	);

	$testimonial_slugs = [];
	for ($index = 1; $index <= 11; $index++) {
		$post_id = $create_post(
			'am_testimonial',
			sprintf('AM CMS verifier Testimonial %s %02d', $short_token, $index),
			1000 + $index
		);
		update_post_meta(
			$post_id,
			'_am_testimonial_quote',
			"Temporary CMS pagination verification quote {$index} for {$token}."
		);
		update_post_meta($post_id, '_am_testimonial_name', "Verifier parent {$index}");
		update_post_meta(
			$post_id,
			'_am_testimonial_location',
			$index <= 8 ? $nursery_name : 'General'
		);
		$testimonial_slugs[] = get_post_field('post_name', $post_id);
	}

	$archive = am_testimonial_query(1, 9);
	$check(
		$archive['total'] === $baseline_testimonial_total + 11
			&& $archive['pages'] >= 2
			&& count($archive['items']) === 9,
		'Testimonial archive exposes every ready record through pagination',
		wp_json_encode(
			[
				'baseline' => $baseline_testimonial_total,
				'total'    => $archive['total'],
				'pages'    => $archive['pages'],
				'items'    => count($archive['items']),
			]
		)
	);

	$returned_fixture_slugs = [];
	for ($page = 1; $page <= $archive['pages']; $page++) {
		$result = am_testimonial_query($page, 9);
		foreach ($result['items'] as $item) {
			if (in_array($item['id'], $testimonial_slugs, true)) {
				$returned_fixture_slugs[] = $item['id'];
			}
		}
	}
	$unique_fixture_slugs = array_values(array_unique($returned_fixture_slugs));
	sort($testimonial_slugs, SORT_STRING);
	sort($unique_fixture_slugs, SORT_STRING);
	$check(
		count($returned_fixture_slugs) === 11 && $unique_fixture_slugs === $testimonial_slugs,
		'All synthetic Testimonials appear exactly once across archive pages'
	);

	$location_page_one = am_testimonial_query(1, 5, $nursery_name);
	$location_items = [];
	for ($page = 1; $page <= $location_page_one['pages']; $page++) {
		$location_result = am_testimonial_query($page, 5, $nursery_name);
		array_push($location_items, ...$location_result['items']);
	}
	$location_fixture_items = array_values(
		array_filter(
			$location_items,
			static fn($item) => in_array($item['id'], $testimonial_slugs, true)
		)
	);
	$location_values = array_unique(array_column($location_fixture_items, 'location'));
	sort($location_values, SORT_STRING);
	$check(
		count($location_fixture_items) === 11
			&& $location_values === ['General', $nursery_name],
		'Nursery testimonial filtering includes matching and General stories without loss',
		wp_json_encode($location_values)
	);

	$out_of_range = am_testimonial_query(9999, 9);
	$check(
		$out_of_range['page'] === $out_of_range['pages']
			&& count($out_of_range['items']) > 0,
		'Out-of-range Testimonial pages resolve to the final populated page',
		wp_json_encode(
			[
				'page'  => $out_of_range['page'],
				'pages' => $out_of_range['pages'],
				'items' => count($out_of_range['items']),
				'total' => $out_of_range['total'],
			]
		)
	);
	$check(
		count(am_get_testimonials()) === 3,
		'Homepage Testimonial preview remains deliberately limited to three'
	);
	$ready_testimonial_result = am_content_website_result($created_ids[count($created_ids) - 1]);
	$check(
		true === $ready_testimonial_result['visible']
			&& 'Live in Testimonials archive' === $ready_testimonial_result['label'],
		'Complete Testimonial wp-admin result names the archive destination',
		$ready_testimonial_result['label']
	);
} catch (Throwable $error) {
	$failed++;
	echo '[FAIL] CMS collection verifier exception: ' . $error->getMessage() . "\n";
} finally {
	foreach (array_reverse(array_values(array_unique(array_map('intval', $created_ids)))) as $post_id) {
		if ($token !== get_post_meta($post_id, '_am_verifier_token', true)) {
			$failed++;
			echo "[FAIL] Cleanup refused unmarked post ID {$post_id}\n";
			continue;
		}
		wp_delete_post($post_id, true);
	}

	$remaining_fixture_count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
			'_am_verifier_token',
			$token
		)
	);
	$check(0 === $remaining_fixture_count, 'All prefix-locked CMS verifier records were removed');

	$restored_nursery_slugs = array_column(am_get_nurseries(), 'id');
	$restored_testimonial_total = (int) am_testimonial_query(1, 24)['total'];
	$check(
		$baseline_nursery_slugs === $restored_nursery_slugs,
		'Nursery collection returned to its exact baseline after cleanup'
	);
	$check(
		$baseline_testimonial_total === $restored_testimonial_total,
		'Testimonial archive returned to its exact baseline after cleanup'
	);
	if (class_exists('AM_Ops_Settings')) {
		if (null === $ops_settings_before) {
			delete_option(AM_Ops_Settings::OPTION);
		} else {
			update_option(AM_Ops_Settings::OPTION, $ops_settings_before, false);
		}
	}
}

echo "Passed: {$passed}; Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
