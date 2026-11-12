<?php
/**
 * Plugin Name: Little Rainbows SEO Integration
 * Description: Route-aware SEO, structured data, redirects and sitemap support for the Little Rainbows React/WordPress site.
 * Version: 1.0.4
 * Author: Little Rainbows build
 */

if (!defined('ABSPATH')) {
	exit;
}

define('AM_SEO_VERSION', '1.0.3');

/**
 * The SPA deliberately uses extensionless URLs without a trailing slash.
 */
function ldrn_seo_request_path() {
	$uri  = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
	$path = (string) wp_parse_url($uri, PHP_URL_PATH);
	$path = '/' . ltrim($path, '/');
	$path = preg_replace('#/+#', '/', $path);
	$path = rawurldecode($path);

	if ('/' !== $path) {
		$path = untrailingslashit($path);
	}

	return $path ?: '/';
}

function ldrn_seo_is_public_request() {
	if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
		return false;
	}
	if (defined('REST_REQUEST') && REST_REQUEST) {
		return false;
	}

	$path = ldrn_seo_request_path();
	return !preg_match('#^/(?:wp-admin|wp-login\.php|wp-json)(?:/|$)#', $path);
}

function ldrn_seo_site_url($path = '/') {
	$base = untrailingslashit(home_url('/'));
	return '/' === $path ? $base . '/' : $base . '/' . ltrim($path, '/');
}

function ldrn_seo_theme_image($relative = 'assets/organisation/teacher-hug.webp') {
	return trailingslashit(get_template_directory_uri()) . 'dist/' . ltrim($relative, '/');
}

function ldrn_seo_clean_text($value, $length = 160) {
	$value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, get_bloginfo('charset'));
	$value = wp_strip_all_tags(strip_shortcodes($value), true);
	$value = preg_replace('/\s+/u', ' ', $value);
	$value = trim($value);

	if ($length > 0 && function_exists('mb_strlen') && mb_strlen($value) > $length) {
		$value = rtrim(mb_substr($value, 0, $length - 1));
		$value = preg_replace('/\s+\S*$/u', '', $value);
		$value .= '…';
	} elseif ($length > 0 && strlen($value) > $length) {
		$value = wp_html_excerpt($value, $length - 1, '…');
	}

	return $value;
}

function ldrn_seo_post_by_slug($slug, $post_type) {
	static $cache = array();
	$key = $post_type . ':' . $slug;

	if (!array_key_exists($key, $cache)) {
		$post = get_page_by_path(sanitize_title($slug), OBJECT, $post_type);
		$cache[$key] = $post instanceof WP_Post && 'publish' === $post->post_status ? $post : null;
	}

	return $cache[$key];
}

function ldrn_seo_post_is_ready($post) {
	if (!$post instanceof WP_Post) {
		return false;
	}

	$callbacks = array(
		'post'        => 'ldrn_blog_is_frontend_ready',
		'ldrn_event'    => 'ldrn_event_is_frontend_ready',
		'ldrn_setting'  => 'ldrn_setting_is_frontend_ready',
		'ldrn_job'      => 'ldrn_job_is_frontend_ready',
	);
	$callback = isset($callbacks[$post->post_type]) ? $callbacks[$post->post_type] : '';

	if ($callback && function_exists($callback) && !$callback($post->ID)) {
		return false;
	}

	if ('ldrn_job' === $post->post_type) {
		$status = (string) get_post_meta($post->ID, '_ldrn_job_status', true);
		if ($status && 'open' !== $status) {
			return false;
		}
	}

	return true;
}

/**
 * Server-rendered metadata for every public LDRN route.
 *
 * This table is the server half of the SEO contract; the React `<Seo />`
 * component holds the same values in `src/data/site.js`. A crawler that never
 * runs JavaScript sees this, so the two must be changed together.
 *
 * Only Hounslow is currently an operating setting. Nothing here may claim a
 * location, age range, fee or scheme the client has not confirmed.
 */
function ldrn_seo_static_routes() {
	return apply_filters('ldrn_seo_static_routes', array(
		'/' => array(
			'title'       => 'Little Rainbows Day Nursery | Day Care in West London',
			'description' => 'A day nursery in Hounslow, rated Good by Ofsted. Learning through play for children aged 6 months–5 years. Check availability.',
			'image'       => ldrn_seo_theme_image('assets/photography/hero-practitioner-and-child-playdough.png'),
		),
		'/our-nursery' => array(
			'title'       => 'Our Nursery | Little Rainbows Day Nursery',
			'description' => 'Explore Little Rainbows Day Nursery in Hounslow, with current details and clear availability notes.',
			'image'       => ldrn_seo_theme_image('assets/photography/children-at-craft-table.jpg'),
		),
		'/about' => array(
			'title'       => 'About Us | Little Rainbows Day Nursery',
			'description' => 'Children learn best through play. Our philosophy, our approach to health and safety, and what a day at Little Rainbows looks like.',
			'image'       => ldrn_seo_theme_image('assets/photography/child-waving-from-play-window.jpg'),
		),
		'/about/ofsted' => array(
			'title'       => 'Ofsted Reports | Little Rainbows Day Nursery',
			'description' => 'View the published Ofsted inspection report for Little Rainbows Day Nursery in Hounslow.',
		),
		'/curriculum' => array(
			'title'       => 'Curriculum | Little Rainbows Day Nursery',
			'description' => 'Art, music, movement, language, mathematics and outside play — the Early Years Foundation Stage at Little Rainbows.',
			'image'       => ldrn_seo_theme_image('assets/photography/child-pointing-shapes-and-numbers-wall.png'),
		),
		'/admissions' => array(
			'title'       => 'Admissions | Little Rainbows Day Nursery',
			'description' => 'How to apply for a place at Little Rainbows. Funded places available for children aged 9 months and older.',
		),
		'/fees' => array(
			'title'       => 'Admissions & Fees | Little Rainbows Day Nursery',
			'description' => 'Contact your preferred Little Rainbows setting for current nursery fees and sessions, or estimate funded weekly hours online.',
		),
		'/fee-calculator' => array(
			'title'       => 'Funding Estimate | Little Rainbows Day Nursery',
			'description' => 'Estimate weekly childcare hours remaining after funded hours. Exact fees and funding eligibility are confirmed by Little Rainbows.',
		),
		'/funded-childcare' => array(
			'title'       => 'Funded Childcare | Little Rainbows Day Nursery',
			'description' => 'Read Little Rainbows funded childcare information and contact your preferred nursery setting for eligibility details.',
		),
		'/blogs' => array(
			'title'       => 'Blog | Little Rainbows Day Nursery',
			'description' => 'News and updates from Little Rainbows Day Nursery.',
			'type'        => 'website',
		),
		'/food-hygiene-rating' => array(
			'title'       => 'Food Hygiene Rating | Little Rainbows Day Nursery',
			'description' => 'View the current Food Standards Agency hygiene rating for Little Rainbows Day Nursery in Hounslow.',
		),
		'/events' => array(
			'title'       => 'News & Events | Little Rainbows Day Nursery',
			'description' => 'View upcoming and past events from Little Rainbows Day Nursery, or contact the nursery team to register your interest.',
		),
		'/testimonials' => array(
			'title'       => 'Parent Testimonials | Little Rainbows Day Nursery',
			'description' => 'Read what parents and carers say about their experience with Little Rainbows Day Nursery.',
		),
		'/careers' => array(
			'title'       => 'Careers | Little Rainbows Day Nursery',
			'description' => 'Join our team. A rewarding career in childcare with a nursery that believes children learn best through play.',
		),
		'/careers/vacancies' => array(
			'title'       => 'Current Vacancies | Little Rainbows Day Nursery',
			'description' => 'View current vacancies or send a general childcare application to Little Rainbows Day Nursery.',
		),
		'/careers/apply' => array(
			// A personal-data form should not be a search landing page.
			'title'       => 'Apply | Little Rainbows Day Nursery',
			'description' => 'Complete the Little Rainbows Day Nursery staff application form.',
			'index'       => false,
		),
		'/check-availability' => array(
			'title'       => 'Check Availability | Little Rainbows Day Nursery',
			'description' => 'Tell Little Rainbows your preferred setting, start date, days and session pattern so the nursery team can check current availability.',
			'image'       => ldrn_seo_theme_image('assets/photography/children-playing-together-at-table.jpg'),
		),
		'/book-a-visit' => array(
			// Legacy path for the same page; canonicalised to /check-availability.
			'title'       => 'Check Availability | Little Rainbows Day Nursery',
			'description' => 'Tell Little Rainbows your preferred setting, start date, days and session pattern so the nursery team can check current availability.',
			'index'       => false,
		),
		'/contact' => array(
			'title'       => 'Contact | Little Rainbows Day Nursery',
			'description' => 'Contact Little Rainbows Day Nursery in Hounslow by phone or email and view our published details.',
		),
		'/privacy' => array(
			// Not in force until the data controller and retention periods
			// are confirmed, so it stays out of the index.
			'title'       => 'Privacy Policy | Little Rainbows Day Nursery',
			'description' => 'How Little Rainbows Day Nursery collects, uses and protects your personal information.',
			'index'       => false,
		),
	));
}

function ldrn_seo_meta_defaults($path) {
	return array(
		'path'        => $path,
		'canonical'   => ldrn_seo_site_url($path),
		'title'       => 'Page Not Found | Little Rainbows Day Nursery',
		'description' => 'The requested page could not be found.',
		'image'       => ldrn_seo_theme_image(),
		'type'        => 'website',
		'index'       => false,
		'found'       => false,
		'post'        => null,
		'entity'      => '',
	);
}

function ldrn_seo_meta_for_request() {
	return ldrn_seo_meta_for_path(ldrn_seo_request_path());
}

/** The same metadata contract for initial requests and client-side navigation. */
function ldrn_seo_meta_for_path($path) {
	static $cache = array();
	if (isset($cache[$path])) return $cache[$path];
	$meta = ldrn_seo_meta_defaults($path);
	$routes = ldrn_seo_static_routes();

	if (isset($routes[$path])) {
		$meta = array_merge($meta, $routes[$path]);
		$meta['found'] = true;
		$meta['index'] = array_key_exists('index', $routes[$path]) ? (bool) $routes[$path]['index'] : true;
	}

	if (preg_match('#^/our-nursery/([a-z0-9-]+)$#', $path, $matches)) {
		$post = ldrn_seo_post_by_slug($matches[1], 'ldrn_setting');
		if (ldrn_seo_post_is_ready($post)) {
			$name  = ldrn_seo_clean_text(get_the_title($post), 80);
			$short = get_post_meta($post->ID, '_ldrn_setting_short', true);
			$image = get_post_meta($post->ID, '_ldrn_setting_hero_image', true);
			$meta = array_merge($meta, array(
				'title'       => $name . ' Nursery | Little Rainbows Day Nursery',
				'description' => ldrn_seo_clean_text($short ?: 'Learning through play at the Little Rainbows ' . $name . ' setting.', 160),
				'image'       => $image ?: $meta['image'],
				'index'       => true,
				'found'       => true,
				'post'        => $post,
				'entity'      => 'setting',
			));
		}
	}

	if (preg_match('#^/blogs/([a-z0-9-]+)$#', $path, $matches)) {
		$post = ldrn_seo_post_by_slug($matches[1], 'post');
		if (ldrn_seo_post_is_ready($post)) {
			$description = get_the_excerpt($post);
			if (!$description) {
				$description = $post->post_content;
			}
			$meta = array_merge($meta, array(
				'title'       => ldrn_seo_clean_text(get_the_title($post), 90) . ' | Little Rainbows Day Nursery',
				'description' => ldrn_seo_clean_text($description, 160),
				'image'       => get_the_post_thumbnail_url($post->ID, 'full') ?: $meta['image'],
				'type'        => 'article',
				'index'       => true,
				'found'       => true,
				'post'        => $post,
				'entity'      => 'article',
			));
		}
	}

	if (preg_match('#^/events/([a-z0-9-]+)$#', $path, $matches)) {
		$post = ldrn_seo_post_by_slug($matches[1], 'ldrn_event');
		if (ldrn_seo_post_is_ready($post)) {
			$description = get_the_excerpt($post) ?: $post->post_content;
			$meta = array_merge($meta, array(
				'title'       => ldrn_seo_clean_text(get_the_title($post), 90) . ' | Little Rainbows Day Nursery',
				'description' => ldrn_seo_clean_text($description, 160),
				'image'       => get_the_post_thumbnail_url($post->ID, 'full') ?: $meta['image'],
				'type'        => 'article',
				'index'       => true,
				'found'       => true,
				'post'        => $post,
				'entity'      => 'event',
			));
		}
	}

	if (preg_match('#^/careers/vacancies/([a-z0-9-]+)$#', $path, $matches)) {
		$post = ldrn_seo_post_by_slug($matches[1], 'ldrn_job');
		if (ldrn_seo_post_is_ready($post)) {
			$summary = get_post_meta($post->ID, '_ldrn_job_short', true);
			$meta = array_merge($meta, array(
				'title'       => ldrn_seo_clean_text(get_the_title($post), 80) . ' - Careers | Little Rainbows Day Nursery',
				'description' => ldrn_seo_clean_text($summary ?: 'Apply for this early-years role at Little Rainbows Day Nursery.', 160),
				'index'       => true,
				'found'       => true,
				'post'        => $post,
				'entity'      => 'job',
			));
		}
	}

	$meta = apply_filters('ldrn_seo_route_meta', $meta, $path);
	if (!(bool) get_option('blog_public', 1)) $meta['index'] = false;
	if (isset($_GET['s']) || isset($_GET['ldrn_visual_preview']) || isset($_GET['ldrn_vb_saved_session']) || isset($_GET['ldrn_ws_studio']) || isset($_GET['ldrn_session'])) {
		$meta['index'] = false;
	}

	$meta['title']       = ldrn_seo_clean_text($meta['title'], 120);
	$meta['description'] = ldrn_seo_clean_text($meta['description'], 160);
	$meta['canonical']   = ldrn_seo_site_url($meta['path']);

	return $cache[$path] = $meta;
}

/**
 * Redirect URLs retained by the old site and WordPress-native post archives.
 */
function ldrn_seo_redirect_legacy_routes() {
	if (!ldrn_seo_is_public_request() || headers_sent()) {
		return;
	}

	$path = ldrn_seo_request_path();
	// Paths retained from the previous Little Rainbows site and from
	// WordPress-native archives. Hounslow is the only setting, so the former
	// per-branch URLs resolve to it rather than 404.
	$redirects = array(
		'/category/blogs'      => '/blogs',
		'/blog'                => '/blogs',
		'/news'                => '/events',
		'/nurseries'           => '/our-nursery',
		'/our-nurseries'       => '/our-nursery',
		'/our-settings'        => '/our-nursery',
		'/nurseries/hounslow'  => '/our-nursery/hounslow',
		'/hounslow'            => '/our-nursery/hounslow',
		'/about-us'            => '/about',
		'/meet-the-team'       => '/about',
		'/about/meet-the-team' => '/about',
		'/ofsted'              => '/about/ofsted',
		'/parent-information'  => '/funded-childcare',
		'/fees-and-funding'    => '/funded-childcare',
		'/contact-us'          => '/contact',
		'/contact/hounslow'    => '/contact',
		'/contact-us-hounslow' => '/contact',
		'/book-a-visit'        => '/check-availability',
		'/sample-page'         => '/',
	);

	// Retire obsolete numeric permalinks retained in Google's historical crawl.
	if (isset($_GET['p']) && is_scalar($_GET['p'])) {
		$post_id = absint(wp_unslash($_GET['p']));
		if ($post_id > 0) {
			$post = get_post($post_id);
			if ($post instanceof WP_Post && 'post' === $post->post_type && ldrn_seo_post_is_ready($post)) {
				wp_safe_redirect(ldrn_seo_site_url('/blogs/' . $post->post_name), 301, 'Little Rainbows SEO');
			} else {
				wp_safe_redirect(ldrn_seo_site_url('/blogs'), 301, 'Little Rainbows SEO');
			}
			exit;
		}
	}

	// Old dated WordPress feeds are not public landing pages; consolidate them
	// into the corresponding route-aware article when the post still exists.
	if (preg_match('#^/[0-9]{4}/[0-9]{2}/[0-9]{2}/([a-z0-9-]+)/feed$#', $path, $matches)) {
		$post = ldrn_seo_post_by_slug($matches[1], 'post');
		$destination = ($post instanceof WP_Post && ldrn_seo_post_is_ready($post))
			? '/blogs/' . $post->post_name
			: '/blogs';
		wp_safe_redirect(ldrn_seo_site_url($destination), 301, 'Little Rainbows SEO');
		exit;
	}

	if (isset($redirects[$path])) {
		wp_safe_redirect(ldrn_seo_site_url($redirects[$path]), 301, 'Little Rainbows SEO');
		exit;
	}

	// "Our Nursery" moved from /settings to /our-nursery; keep old links working.
	if (preg_match('#^/settings(/[a-z0-9-]+)?$#', $path, $matches)) {
		wp_safe_redirect(ldrn_seo_site_url('/our-nursery' . ($matches[1] ?? '')), 301, 'Little Rainbows SEO');
		exit;
	}

	if (is_singular('post')) {
		$post = get_queried_object();
		if ($post instanceof WP_Post && ldrn_seo_post_is_ready($post)) {
			wp_safe_redirect(ldrn_seo_site_url('/blogs/' . $post->post_name), 301, 'Little Rainbows SEO');
			exit;
		}
	}

	if (is_category() || is_tag() || is_date() || is_author()) {
		wp_safe_redirect(ldrn_seo_site_url('/blogs'), 301, 'Little Rainbows SEO');
		exit;
	}
}
add_action('template_redirect', 'ldrn_seo_redirect_legacy_routes', -20);

/**
 * A few React blog slugs also exist as WordPress attachment slugs. Prevent
 * Yoast and WordPress from redirecting valid SPA routes to an uploaded file or
 * a native permalink; the integration owns those routes and their canonicals.
 */
function ldrn_seo_prevent_attachment_redirect($url, $attachment) {
	if (ldrn_seo_is_public_request() && ldrn_seo_meta_for_request()['found']) {
		return '';
	}
	return $url;
}
add_filter('wpseo_attachment_redirect_url', 'ldrn_seo_prevent_attachment_redirect', PHP_INT_MAX, 2);

function ldrn_seo_prevent_core_canonical_redirect($redirect_url, $requested_url) {
	if (ldrn_seo_is_public_request() && ldrn_seo_meta_for_request()['found']) {
		return false;
	}
	return $redirect_url;
}
add_filter('redirect_canonical', 'ldrn_seo_prevent_core_canonical_redirect', PHP_INT_MAX, 2);

/**
 * Replace the SPA's blanket 200 response with a real 404 for unknown routes.
 */
function ldrn_seo_enforce_route_status() {
	if (!ldrn_seo_is_public_request()) {
		return;
	}

	$path = ldrn_seo_request_path();
	if (in_array($path, array('/sitemap.xml', '/sitemap_index.xml', '/wp-sitemap.xml', '/robots.txt'), true)) {
		return;
	}

	$meta = ldrn_seo_meta_for_request();
	if (!$meta['found']) {
		status_header(404);
		nocache_headers();
	}
}
add_action('template_redirect', 'ldrn_seo_enforce_route_status', 999);

/**
 * WordPress/Yoast title, canonical, robots and social metadata integration.
 */
function ldrn_seo_document_title($title) {
	if (!ldrn_seo_is_public_request()) {
		return $title;
	}
	return ldrn_seo_meta_for_request()['title'];
}
add_filter('pre_get_document_title', 'ldrn_seo_document_title', 99);
add_filter('wpseo_title', 'ldrn_seo_document_title', 99);

function ldrn_seo_description($description) {
	if (!ldrn_seo_is_public_request()) {
		return $description;
	}
	return ldrn_seo_meta_for_request()['description'];
}
add_filter('wpseo_metadesc', 'ldrn_seo_description', 99);
add_filter('wpseo_opengraph_desc', 'ldrn_seo_description', 99);
add_filter('wpseo_twitter_description', 'ldrn_seo_description', 99);

function ldrn_seo_canonical($canonical) {
	if (!ldrn_seo_is_public_request()) {
		return $canonical;
	}
	$meta = ldrn_seo_meta_for_request();
	return $meta['found'] ? $meta['canonical'] : false;
}
add_filter('wpseo_opengraph_url', 'ldrn_seo_canonical', 99);

/**
 * Yoast does not present a canonical for SPA routes that WordPress initially
 * resolves as a 404. Own the canonical output so every valid React route gets
 * exactly one canonical while genuine 404s get none.
 */
function ldrn_seo_disable_yoast_canonical($canonical) {
	return ldrn_seo_is_public_request() ? false : $canonical;
}
add_filter('wpseo_canonical', 'ldrn_seo_disable_yoast_canonical', PHP_INT_MAX);

function ldrn_seo_output_canonical() {
	if (!ldrn_seo_is_public_request()) {
		return;
	}

	$meta = ldrn_seo_meta_for_request();
	if ($meta['found']) {
		echo '<link rel="canonical" href="' . esc_url($meta['canonical']) . '">' . "\n";
	}
}
add_action('wp_head', 'ldrn_seo_output_canonical', 3);

/**
 * Yoast omits og:url when a valid SPA route begins as a WordPress 404. Fill
 * only that gap; native WordPress requests keep Yoast's own presenter.
 */
function ldrn_seo_output_spa_opengraph_url() {
	if (!ldrn_seo_is_public_request() || !is_404()) {
		return;
	}

	$meta = ldrn_seo_meta_for_request();
	if ($meta['found']) {
		echo '<meta property="og:url" content="' . esc_url($meta['canonical']) . '">' . "\n";
	}
}
add_action('wp_head', 'ldrn_seo_output_spa_opengraph_url', 3);

function ldrn_seo_social_title($title) {
	return ldrn_seo_is_public_request() ? ldrn_seo_meta_for_request()['title'] : $title;
}
add_filter('wpseo_opengraph_title', 'ldrn_seo_social_title', 99);
add_filter('wpseo_twitter_title', 'ldrn_seo_social_title', 99);

function ldrn_seo_social_image($image) {
	return ldrn_seo_is_public_request() ? ldrn_seo_meta_for_request()['image'] : $image;
}
add_filter('wpseo_opengraph_image', 'ldrn_seo_social_image', 99);
add_filter('wpseo_twitter_image', 'ldrn_seo_social_image', 99);

function ldrn_seo_opengraph_type($type) {
	return ldrn_seo_is_public_request() ? ldrn_seo_meta_for_request()['type'] : $type;
}
add_filter('wpseo_opengraph_type', 'ldrn_seo_opengraph_type', 99);

function ldrn_seo_yoast_robots($robots) {
	if (!ldrn_seo_is_public_request()) {
		return $robots;
	}
	return ldrn_seo_meta_for_request()['index']
		? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'
		: 'noindex, follow';
}
add_filter('wpseo_robots', 'ldrn_seo_yoast_robots', 99);

function ldrn_seo_wordpress_robots($robots) {
	if (!ldrn_seo_is_public_request()) {
		return $robots;
	}

	if (ldrn_seo_meta_for_request()['index']) {
		unset($robots['noindex'], $robots['nofollow']);
		$robots['max-image-preview'] = 'large';
	} else {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset($robots['nofollow']);
	}

	return $robots;
}
add_filter('wp_robots', 'ldrn_seo_wordpress_robots', 99);

function ldrn_seo_yoast_active() {
	return defined('WPSEO_VERSION') || class_exists('WPSEO_Options');
}

/**
 * Fallback output keeps the site correct if Yoast is temporarily inactive.
 */
function ldrn_seo_output_fallback_meta() {
	if (!ldrn_seo_is_public_request() || ldrn_seo_yoast_active()) {
		return;
	}

	$meta = ldrn_seo_meta_for_request();
	echo '<meta name="description" content="' . esc_attr($meta['description']) . '">' . "\n";
	echo '<meta property="og:locale" content="en_GB">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr($meta['type']) . '">' . "\n";
	echo '<meta property="og:site_name" content="Little Rainbows Day Nursery">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr($meta['title']) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr($meta['description']) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url($meta['canonical']) . '">' . "\n";
	echo '<meta property="og:image" content="' . esc_url($meta['image']) . '">' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr($meta['title']) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr($meta['description']) . '">' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url($meta['image']) . '">' . "\n";
	echo '<script type="application/ld+json">' . wp_json_encode(ldrn_seo_schema_document($meta), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}
add_action('wp_head', 'ldrn_seo_output_fallback_meta', 3);

function ldrn_seo_remove_core_canonical() {
	remove_action('wp_head', 'rel_canonical');
}
add_action('after_setup_theme', 'ldrn_seo_remove_core_canonical', 99);

function ldrn_seo_nursery_schema($meta) {
	$post = $meta['post'];
	if (!$post instanceof WP_Post) {
		return array();
	}

	$name     = ldrn_seo_clean_text(get_the_title($post), 80);
	$address  = trim((string) get_post_meta($post->ID, '_ldrn_setting_address', true));
	$postcode = trim((string) get_post_meta($post->ID, '_ldrn_setting_postcode', true));
	$area     = trim((string) get_post_meta($post->ID, '_ldrn_setting_area', true));

	return array(
		'@type'              => 'ChildCare',
		'@id'                => ldrn_seo_site_url('/our-nursery/' . $post->post_name) . '#childcare',
		'name'               => 'Little Rainbows Day Nursery ' . $name,
		'url'                => ldrn_seo_site_url('/our-nursery/' . $post->post_name),
		'description'        => ldrn_seo_clean_text(get_post_meta($post->ID, '_ldrn_setting_short', true), 240),
		'image'              => get_post_meta($post->ID, '_ldrn_setting_hero_image', true) ?: $meta['image'],
		'telephone'          => (string) get_post_meta($post->ID, '_ldrn_setting_phone', true),
		'email'              => (string) get_post_meta($post->ID, '_ldrn_setting_email', true),
		'priceRange'         => '££',
		'address'            => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $address,
			'addressLocality' => $area ?: $name,
			'postalCode'      => $postcode,
			'addressCountry'  => 'GB',
		),
		'parentOrganization' => array('@id' => ldrn_seo_site_url('/') . '#organization'),
	);
}

/**
 * Find the published nursery record represented by a human-facing location.
 *
 * Job and event editors select locations by label (for example, Hounslow),
 * while the nursery address lives on the ldrn_setting post. Keeping the lookup
 * here means Google receives the same verified address that staff maintain in
 * WordPress instead of a second hard-coded copy.
 */
function ldrn_seo_nursery_for_location($location) {
	$key = sanitize_title(ldrn_seo_clean_text($location, 80));
	if ('' === $key || 'all-settings' === $key) {
		return null;
	}

	$nursery = get_page_by_path($key, OBJECT, 'ldrn_setting');
	if ($nursery instanceof WP_Post && 'publish' === $nursery->post_status) {
		return $nursery;
	}

	$settings_posts = get_posts(array(
		'post_type'      => 'ldrn_setting',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	));

	foreach ($settings_posts as $candidate) {
		$candidate_keys = array_filter(array_unique(array(
			sanitize_title((string) $candidate->post_name),
			sanitize_title(ldrn_seo_clean_text(get_the_title($candidate), 80)),
			sanitize_title(ldrn_seo_clean_text(get_post_meta($candidate->ID, '_ldrn_setting_area', true), 80)),
		)));

		foreach ($candidate_keys as $candidate_key) {
			if ($key === $candidate_key || str_contains($key, $candidate_key) || str_contains($candidate_key, $key)) {
				return $candidate;
			}
		}
	}

	return null;
}

function ldrn_seo_postal_address_for_nursery($nursery, $fallback_locality = 'London') {
	if (!$nursery instanceof WP_Post) {
		return array(
			'@type'           => 'PostalAddress',
			'addressLocality' => $fallback_locality ?: 'London',
			'addressRegion'   => 'Greater London',
			'addressCountry'  => 'GB',
		);
	}

	$street   = trim((string) get_post_meta($nursery->ID, '_ldrn_setting_address', true));
	$postcode = trim((string) get_post_meta($nursery->ID, '_ldrn_setting_postcode', true));
	$parts    = array_values(array_filter(array_map('trim', explode(',', $street))));
	$locality = $parts ? (string) end($parts) : '';

	if ('' === $locality) {
		$locality = trim((string) get_post_meta($nursery->ID, '_ldrn_setting_area', true));
	}
	if ('' === $locality) {
		$locality = $fallback_locality ?: ldrn_seo_clean_text(get_the_title($nursery), 80);
	}

	$address = array(
		'@type'           => 'PostalAddress',
		'streetAddress'   => $street,
		'addressLocality' => $locality,
		'addressRegion'   => 'Greater London',
		'postalCode'      => $postcode,
		'addressCountry'  => 'GB',
	);

	return array_filter($address, static function ($value) {
		return '' !== $value;
	});
}

/**
 * Convert an editor-friendly GBP range such as "£26,000 – £30,000 pa" into
 * Google's JobPosting salary structure. No schema is emitted unless the CMS
 * value contains at least one real number and an explicit annual unit.
 */
function ldrn_seo_job_salary_schema($salary_text) {
	$salary_text = ldrn_seo_clean_text($salary_text, 120);
	if ('' === $salary_text || !preg_match('/(?:\bpa\b|per\s+annum|annual|year)/i', $salary_text)) {
		return array();
	}

	preg_match_all('/\d[\d,]*(?:\.\d+)?/', $salary_text, $matches);
	$values = array_values(array_filter(array_map(static function ($value) {
		return (float) str_replace(',', '', $value);
	}, $matches[0]), static function ($value) {
		return $value > 0;
	}));

	if (!$values) {
		return array();
	}

	$quantitative_value = array(
		'@type'    => 'QuantitativeValue',
		'unitText' => 'YEAR',
	);
	if (count($values) > 1) {
		$quantitative_value['minValue'] = min($values);
		$quantitative_value['maxValue'] = max($values);
	} else {
		$quantitative_value['value'] = $values[0];
	}

	return array(
		'@type'    => 'MonetaryAmount',
		'currency' => 'GBP',
		'value'    => $quantitative_value,
	);
}

function ldrn_seo_entity_schema($meta) {
	$post = $meta['post'];
	if ('setting' === $meta['entity']) {
		return ldrn_seo_nursery_schema($meta);
	}
	if (!$post instanceof WP_Post) {
		return array();
	}

	$common = array(
		'@id'         => $meta['canonical'] . '#primary',
		'url'         => $meta['canonical'],
		'name'        => ldrn_seo_clean_text(get_the_title($post), 120),
		'description' => $meta['description'],
		'image'       => $meta['image'],
	);

	if ('article' === $meta['entity']) {
		return array_merge($common, array(
			'@type'            => 'BlogPosting',
			'headline'         => $common['name'],
			'datePublished'    => get_post_time('c', true, $post),
			'dateModified'     => get_post_modified_time('c', true, $post),
			'mainEntityOfPage' => array('@id' => $meta['canonical'] . '#webpage'),
			'publisher'        => array('@id' => ldrn_seo_site_url('/') . '#organization'),
		));
	}

	if ('event' === $meta['entity']) {
		$location = (string) get_post_meta($post->ID, '_ldrn_event_location', true);
		$nursery  = ldrn_seo_nursery_for_location($location);
		$place    = array(
			'@type' => 'Place',
			'name'  => $nursery instanceof WP_Post ? 'Little Rainbows Day Nursery ' . ldrn_seo_clean_text(get_the_title($nursery), 80) : ($location ?: 'Little Rainbows Day Nursery'),
		);
		if ($nursery instanceof WP_Post) {
			$place['address'] = ldrn_seo_postal_address_for_nursery($nursery, $location);
		}

		return array_merge($common, array(
			'@type'               => 'Event',
			'startDate'           => (string) get_post_meta($post->ID, '_ldrn_event_date', true),
			'eventStatus'         => 'https://schema.org/EventScheduled',
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
			'location'            => $place,
			'organizer'           => array('@id' => ldrn_seo_site_url('/') . '#organization'),
		));
	}

	if ('job' === $meta['entity']) {
		$location   = (string) get_post_meta($post->ID, '_ldrn_job_location', true);
		$nursery    = ldrn_seo_nursery_for_location($location);
		$job_schema = array_merge($common, array(
			'@type'              => 'JobPosting',
			'title'              => $common['name'],
			'datePosted'         => get_post_time('Y-m-d', true, $post),
			'employmentType'     => (string) get_post_meta($post->ID, '_ldrn_job_type', true),
			'hiringOrganization' => array('@id' => ldrn_seo_site_url('/') . '#organization'),
			'jobLocation'        => array(
				'@type'   => 'Place',
				'name'    => $nursery instanceof WP_Post ? 'Little Rainbows Day Nursery ' . ldrn_seo_clean_text(get_the_title($nursery), 80) : ($location ?: 'Little Rainbows Day Nursery, London'),
				'address' => ldrn_seo_postal_address_for_nursery($nursery, $location ?: 'London'),
			),
		));

		$salary = ldrn_seo_job_salary_schema(get_post_meta($post->ID, '_ldrn_job_salary', true));
		if ($salary) {
			$job_schema['baseSalary'] = $salary;
		}

		$valid_through = trim((string) get_post_meta($post->ID, '_ldrn_job_valid_through', true));
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valid_through)) {
			$job_schema['validThrough'] = $valid_through . 'T23:59:59+00:00';
		}

		return $job_schema;
	}

	return array();
}

function ldrn_seo_schema_document($meta) {
	$home = ldrn_seo_site_url('/');
	$graph = array(
		array(
			'@type' => 'Organization',
			'@id'   => $home . '#organization',
			'name'  => 'Little Rainbows Day Nursery',
			'url'   => $home,
			'logo'  => array('@type' => 'ImageObject', 'url' => ldrn_seo_theme_image('assets/logo-badge.webp')),
		),
		array(
			'@type'     => 'WebSite',
			'@id'       => $home . '#website',
			'url'       => $home,
			'name'      => 'Little Rainbows Day Nursery',
			'publisher' => array('@id' => $home . '#organization'),
			'inLanguage'=> 'en-GB',
		),
		array(
			'@type'       => 'WebPage',
			'@id'         => $meta['canonical'] . '#webpage',
			'url'         => $meta['canonical'],
			'name'        => $meta['title'],
			'description' => $meta['description'],
			'isPartOf'    => array('@id' => $home . '#website'),
			'about'       => array('@id' => $home . '#organization'),
			'inLanguage'  => 'en-GB',
		),
	);
	$entity = ldrn_seo_entity_schema($meta);
	if ($entity) {
		$graph[] = $entity;
	}

	return array('@context' => 'https://schema.org', '@graph' => $graph);
}

/**
 * Correct Yoast's graph, whose queried object is otherwise the one SPA shell.
 */
function ldrn_seo_yoast_schema_graph($graph) {
	if (!ldrn_seo_is_public_request() || !is_array($graph)) {
		return $graph;
	}

	$meta = ldrn_seo_meta_for_request();
	$home = ldrn_seo_site_url('/');
	$clean = array();
	foreach ($graph as $piece) {
		if (!is_array($piece)) {
			continue;
		}
		$types = isset($piece['@type']) ? (array) $piece['@type'] : array();
		if (array_intersect($types, array('BreadcrumbList', 'Article', 'BlogPosting', 'Event', 'JobPosting'))) {
			continue;
		}
		if (array_intersect($types, array('WebPage', 'CollectionPage', 'ItemPage', 'AboutPage', 'ContactPage'))) {
			$piece['@id']         = $meta['canonical'] . '#webpage';
			$piece['url']         = $meta['canonical'];
			$piece['name']        = $meta['title'];
			$piece['description'] = $meta['description'];
			$piece['inLanguage']  = 'en-GB';
		}
		if (in_array('WebSite', $types, true)) {
			$piece['@id']  = $home . '#website';
			$piece['url']  = $home;
			$piece['name'] = 'Little Rainbows Day Nursery';
		}
		if (in_array('Organization', $types, true)) {
			$piece['@id']  = $home . '#organization';
			$piece['url']  = $home;
			$piece['name'] = 'Little Rainbows Day Nursery';
		}
		$clean[] = $piece;
	}

	$entity = ldrn_seo_entity_schema($meta);
	if ($entity) {
		$clean[] = $entity;
	}

	// Yoast sees most SPA routes as the WordPress catch-all/404 query. Ensure
	// the core graph pieces still exist even when its own graph is empty.
	$required = array('Organization', 'WebSite', 'WebPage');
	$present  = array();
	foreach ($clean as $piece) {
		if (!empty($piece['@type'])) {
			$present = array_merge($present, (array) $piece['@type']);
		}
	}
	$fallback = ldrn_seo_schema_document($meta);
	foreach ($fallback['@graph'] as $piece) {
		$types = isset($piece['@type']) ? (array) $piece['@type'] : array();
		if (array_intersect($types, $required) && !array_intersect($types, $present)) {
			$clean[] = $piece;
			$present = array_merge($present, $types);
		}
	}
	return $clean;
}
add_filter('wpseo_schema_graph', 'ldrn_seo_yoast_schema_graph', 99);

/**
 * A single authoritative sitemap containing the actual React routes.
 */
function ldrn_seo_sitemap_entries() {
	$entries = array();
	foreach (ldrn_seo_static_routes() as $path => $route) {
		if (isset($route['index']) && !$route['index']) {
			continue;
		}
		$entries[] = array('loc' => ldrn_seo_site_url($path));
	}

	$settings_posts = get_posts(array(
		'post_type'      => 'ldrn_setting',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	));
	foreach ($settings_posts as $post) {
		if (!ldrn_seo_post_is_ready($post)) {
			continue;
		}
		$lastmod = get_post_modified_time('c', true, $post);
		$image   = get_post_meta($post->ID, '_ldrn_setting_hero_image', true);
		$entries[] = array('loc' => ldrn_seo_site_url('/our-nursery/' . $post->post_name), 'lastmod' => $lastmod, 'image' => $image);
		$entries[] = array('loc' => ldrn_seo_site_url('/contact/' . $post->post_name), 'lastmod' => $lastmod, 'image' => $image);
	}

	$collections = array(
		'post' => array('prefix' => '/blogs/', 'ready' => true),
		'ldrn_event' => array('prefix' => '/events/', 'ready' => true),
		'ldrn_job' => array('prefix' => '/careers/vacancies/', 'ready' => true),
	);
	foreach ($collections as $post_type => $config) {
		$posts = get_posts(array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		));
		foreach ($posts as $post) {
			if (!ldrn_seo_post_is_ready($post)) {
				continue;
			}
			$entries[] = array(
				'loc'     => ldrn_seo_site_url($config['prefix'] . $post->post_name),
				'lastmod' => get_post_modified_time('c', true, $post),
				'image'   => get_the_post_thumbnail_url($post->ID, 'full') ?: '',
			);
		}
	}

	$unique = array();
	foreach ($entries as $entry) {
		$path = wp_parse_url($entry['loc'], PHP_URL_PATH) ?: '/';
		if (!ldrn_seo_meta_for_path($path)['index']) continue;
		$unique[$entry['loc']] = $entry;
	}
	ksort($unique, SORT_NATURAL | SORT_FLAG_CASE);
	return array_values($unique);
}

function ldrn_seo_redirect_deprecated_sitemaps() {
	if (!ldrn_seo_is_public_request()) {
		return;
	}

	if (in_array(ldrn_seo_request_path(), array('/sitemap_index.xml', '/wp-sitemap.xml'), true)) {
		wp_safe_redirect(ldrn_seo_site_url('/sitemap.xml'), 301, 'Little Rainbows SEO');
		exit;
	}
}
add_action('init', 'ldrn_seo_redirect_deprecated_sitemaps', -1000);

function ldrn_seo_render_sitemap() {
	if (!ldrn_seo_is_public_request()) {
		return;
	}

	$path = ldrn_seo_request_path();
	if (in_array($path, array('/sitemap_index.xml', '/wp-sitemap.xml'), true)) {
		wp_safe_redirect(ldrn_seo_site_url('/sitemap.xml'), 301, 'Little Rainbows SEO');
		exit;
	}
	if ('/sitemap.xml' !== $path) {
		return;
	}

	status_header(200);
	header('Content-Type: application/xml; charset=UTF-8');
	header('Cache-Control: no-cache, max-age=0, must-revalidate');
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
	foreach (ldrn_seo_sitemap_entries() as $entry) {
		echo "  <url>\n";
		echo '    <loc>' . esc_xml($entry['loc']) . "</loc>\n";
		if (!empty($entry['lastmod'])) {
			echo '    <lastmod>' . esc_xml($entry['lastmod']) . "</lastmod>\n";
		}
		if (!empty($entry['image'])) {
			echo '    <image:image><image:loc>' . esc_xml($entry['image']) . "</image:loc></image:image>\n";
		}
		echo "  </url>\n";
	}
	echo "</urlset>\n";
	exit;
}
add_action('template_redirect', 'ldrn_seo_render_sitemap', -100);

add_filter('wp_sitemaps_enabled', '__return_false', 99);
add_filter('wpseo_sitemap_enabled', '__return_false', 99);

function ldrn_seo_robots_txt($output, $public) {
	if (!$public) {
		return "User-agent: *\nDisallow: /\n";
	}

	return "User-agent: *\n"
		. "Disallow: /wp-admin/\n"
		. "Allow: /wp-admin/admin-ajax.php\n"
		. "Disallow: /wp-content/uploads/ldrn-private/\n\n"
		. 'Sitemap: ' . ldrn_seo_site_url('/sitemap.xml') . "\n";
}
add_filter('robots_txt', 'ldrn_seo_robots_txt', PHP_INT_MAX, 2);
