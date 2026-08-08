<?php
/**
 * Plugin Name: Alexandra SEO Integration
 * Description: Route-aware SEO, structured data, redirects and sitemap support for the Alexandra React/WordPress site.
 * Version: 1.0.4
 * Author: Alexandra build
 */

if (!defined('ABSPATH')) {
	exit;
}

define('AM_SEO_VERSION', '1.0.3');

/**
 * The SPA deliberately uses extensionless URLs without a trailing slash.
 */
function am_seo_request_path() {
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

function am_seo_is_public_request() {
	if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
		return false;
	}
	if (defined('REST_REQUEST') && REST_REQUEST) {
		return false;
	}

	$path = am_seo_request_path();
	return !preg_match('#^/(?:wp-admin|wp-login\.php|wp-json)(?:/|$)#', $path);
}

function am_seo_site_url($path = '/') {
	$base = untrailingslashit(home_url('/'));
	return '/' === $path ? $base . '/' : $base . '/' . ltrim($path, '/');
}

function am_seo_theme_image($relative = 'assets/organisation/teacher-hug.webp') {
	return trailingslashit(get_template_directory_uri()) . 'dist/' . ltrim($relative, '/');
}

function am_seo_clean_text($value, $length = 160) {
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

function am_seo_post_by_slug($slug, $post_type) {
	static $cache = array();
	$key = $post_type . ':' . $slug;

	if (!array_key_exists($key, $cache)) {
		$post = get_page_by_path(sanitize_title($slug), OBJECT, $post_type);
		$cache[$key] = $post instanceof WP_Post && 'publish' === $post->post_status ? $post : null;
	}

	return $cache[$key];
}

function am_seo_post_is_ready($post) {
	if (!$post instanceof WP_Post) {
		return false;
	}

	$callbacks = array(
		'post'        => 'am_blog_is_frontend_ready',
		'am_event'    => 'am_event_is_frontend_ready',
		'am_nursery'  => 'am_nursery_is_frontend_ready',
		'am_job'      => 'am_job_is_frontend_ready',
	);
	$callback = isset($callbacks[$post->post_type]) ? $callbacks[$post->post_type] : '';

	if ($callback && function_exists($callback) && !$callback($post->ID)) {
		return false;
	}

	if ('am_job' === $post->post_type) {
		$status = (string) get_post_meta($post->ID, '_am_job_status', true);
		if ($status && 'open' !== $status) {
			return false;
		}
	}

	return true;
}

function am_seo_static_routes() {
	return array(
		'/' => array(
			'title'       => 'Montessori Nurseries in London | Alexandra Montessori',
			'description' => 'Montessori childcare for babies to 5 years in welcoming Hounslow, Heston and Hammersmith nursery settings.',
			'image'       => am_seo_theme_image('assets/organisation/classroom-main.webp'),
		),
		'/nurseries' => array(
			'title'       => 'Our Montessori Nurseries in London | Alexandra Montessori',
			'description' => 'Explore Alexandra Montessori nurseries in Hounslow, Heston and Hammersmith, offering thoughtful, child-centred early years care.',
		),
		'/about' => array(
			'title'       => 'About Our Montessori Nurseries | Alexandra Montessori',
			'description' => 'Discover our Montessori approach, dedicated early-years team and the warm, purposeful nursery environments we create for every child.',
			'image'       => am_seo_theme_image('assets/organisation/classroom-calm.webp'),
		),
		'/curriculum' => array(
			'title'       => 'Montessori Curriculum & EYFS | Alexandra Montessori',
			'description' => 'See how Montessori-inspired practice and the EYFS framework support confident, independent and creative early learning.',
		),
		'/fees' => array(
			'title'       => 'Nursery Fees & Admissions | Alexandra Montessori',
			'description' => 'Download nursery fee sheets for Hounslow, Heston and Hammersmith and find guidance on admissions and funded childcare.',
			'image'       => am_seo_theme_image('assets/organisation/materials-shelf.webp'),
		),
		'/fee-calculator' => array(
			'title'       => 'Funded Childcare Hours Calculator | Alexandra Montessori',
			'description' => 'Estimate weekly chargeable nursery hours after funded childcare and download the correct Alexandra Montessori branch fee sheet.',
		),
		'/funded-childcare' => array(
			'title'       => 'Funded Childcare & Free Hours | Alexandra Montessori',
			'description' => 'Understand 15 and 30 funded childcare hours, support from 9 months and two-year-old funding, including eligibility and how to claim.',
		),
		'/blogs' => array(
			'title'       => 'Montessori Parenting Blogs & Resources | Alexandra Montessori',
			'description' => 'Helpful reads for parents about Montessori learning, settling in, nursery nutrition, child development and funded childcare.',
			'type'        => 'website',
		),
		'/food-hygiene-rating' => array(
			'title'       => 'Food Hygiene Ratings | Alexandra Montessori',
			'description' => 'View food hygiene rating information and official public records for Alexandra Montessori nursery locations.',
		),
		'/events' => array(
			'title'       => 'Nursery Events & Open Mornings | Alexandra Montessori',
			'description' => 'Discover open mornings, taster sessions and family events at Alexandra Montessori nurseries across London.',
		),
		'/testimonials' => array(
			'title'       => 'Parent Testimonials | Alexandra Montessori',
			'description' => 'Read what parents and carers say about the care, learning and nursery experience at Alexandra Montessori.',
		),
		'/careers' => array(
			'title'       => 'Montessori Nursery Careers | Alexandra Montessori',
			'description' => 'Join a supportive Montessori nursery team with genuine training, development and career pathways across our London settings.',
		),
		'/careers/vacancies' => array(
			'title'       => 'Current Nursery Vacancies | Alexandra Montessori',
			'description' => 'Browse current early-years and Montessori nursery vacancies at Alexandra Montessori locations across London.',
		),
		'/careers/apply' => array(
			'title'       => 'Apply for a Nursery Role | Alexandra Montessori',
			'description' => 'Apply to join the Alexandra Montessori early-years team.',
			'index'       => false,
		),
		'/check-availability' => array(
			'title'       => 'Check Nursery Availability | Alexandra Montessori',
			'description' => 'Check nursery place availability at Alexandra Montessori in Hounslow, Heston and Hammersmith.',
			'image'       => am_seo_theme_image('assets/organisation/friends-two.webp'),
		),
		'/contact' => array(
			'title'       => 'Contact Our London Nurseries | Alexandra Montessori',
			'description' => 'Book a show-around or ask about places and funded hours at Alexandra Montessori in Hammersmith, Heston or Hounslow.',
		),
		'/privacy' => array(
			'title'       => 'Privacy Policy | Alexandra Montessori',
			'description' => 'Read the Alexandra Montessori website privacy policy.',
			'index'       => false,
		),
	);
}

function am_seo_meta_defaults($path) {
	return array(
		'path'        => $path,
		'canonical'   => am_seo_site_url($path),
		'title'       => 'Page Not Found | Alexandra Montessori',
		'description' => 'The requested page could not be found.',
		'image'       => am_seo_theme_image(),
		'type'        => 'website',
		'index'       => false,
		'found'       => false,
		'post'        => null,
		'entity'      => '',
	);
}

function am_seo_meta_for_request() {
	static $meta = null;
	if (null !== $meta) {
		return $meta;
	}

	$path = am_seo_request_path();
	$meta = am_seo_meta_defaults($path);
	$routes = am_seo_static_routes();

	if (isset($routes[$path])) {
		$meta = array_merge($meta, $routes[$path]);
		$meta['found'] = true;
		$meta['index'] = array_key_exists('index', $routes[$path]) ? (bool) $routes[$path]['index'] : true;
	}

	if (preg_match('#^/nurseries/([a-z0-9-]+)$#', $path, $matches)) {
		$post = am_seo_post_by_slug($matches[1], 'am_nursery');
		if (am_seo_post_is_ready($post)) {
			$name  = am_seo_clean_text(get_the_title($post), 80);
			$short = get_post_meta($post->ID, '_am_nursery_short', true);
			$image = get_post_meta($post->ID, '_am_nursery_hero_image', true);
			$meta = array_merge($meta, array(
				'title'       => $name . ' Montessori Nursery | Alexandra Montessori',
				'description' => am_seo_clean_text($short ?: 'Discover Alexandra Montessori childcare at our ' . $name . ' nursery.', 160),
				'image'       => $image ?: $meta['image'],
				'index'       => true,
				'found'       => true,
				'post'        => $post,
				'entity'      => 'nursery',
			));
		}
	}

	if (preg_match('#^/contact/([a-z0-9-]+)$#', $path, $matches)) {
		$post = am_seo_post_by_slug($matches[1], 'am_nursery');
		if (am_seo_post_is_ready($post)) {
			$name     = am_seo_clean_text(get_the_title($post), 80);
			$address  = trim((string) get_post_meta($post->ID, '_am_nursery_address', true));
			$postcode = trim((string) get_post_meta($post->ID, '_am_nursery_postcode', true));
			$phone    = trim((string) get_post_meta($post->ID, '_am_nursery_phone', true));
			$image    = get_post_meta($post->ID, '_am_nursery_hero_image', true);
			$meta = array_merge($meta, array(
				'title'       => 'Contact ' . $name . ' Nursery | Alexandra Montessori',
				'description' => am_seo_clean_text('Contact Alexandra Montessori ' . $name . ' at ' . trim($address . ', ' . $postcode, ' ,') . ($phone ? '. Call ' . $phone : '') . ' or send an enquiry to book a visit.', 160),
				'image'       => $image ?: $meta['image'],
				'index'       => true,
				'found'       => true,
				'post'        => $post,
				'entity'      => 'nursery',
			));
		}
	}

	if (preg_match('#^/blogs/([a-z0-9-]+)$#', $path, $matches)) {
		$post = am_seo_post_by_slug($matches[1], 'post');
		if (am_seo_post_is_ready($post)) {
			$description = get_the_excerpt($post);
			if (!$description) {
				$description = $post->post_content;
			}
			$meta = array_merge($meta, array(
				'title'       => am_seo_clean_text(get_the_title($post), 90) . ' | Alexandra Montessori',
				'description' => am_seo_clean_text($description, 160),
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
		$post = am_seo_post_by_slug($matches[1], 'am_event');
		if (am_seo_post_is_ready($post)) {
			$description = get_the_excerpt($post) ?: $post->post_content;
			$meta = array_merge($meta, array(
				'title'       => am_seo_clean_text(get_the_title($post), 90) . ' | Alexandra Montessori',
				'description' => am_seo_clean_text($description, 160),
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
		$post = am_seo_post_by_slug($matches[1], 'am_job');
		if (am_seo_post_is_ready($post)) {
			$summary = get_post_meta($post->ID, '_am_job_short', true);
			$meta = array_merge($meta, array(
				'title'       => am_seo_clean_text(get_the_title($post), 80) . ' - Careers | Alexandra Montessori',
				'description' => am_seo_clean_text($summary ?: 'Apply for this early-years role at Alexandra Montessori.', 160),
				'index'       => true,
				'found'       => true,
				'post'        => $post,
				'entity'      => 'job',
			));
		}
	}

	if (isset($_GET['s']) || isset($_GET['am_visual_preview']) || isset($_GET['am_vb_saved_session'])) {
		$meta['index'] = false;
	}

	$meta['title']       = am_seo_clean_text($meta['title'], 120);
	$meta['description'] = am_seo_clean_text($meta['description'], 160);
	$meta['canonical']   = am_seo_site_url($meta['path']);

	return $meta;
}

/**
 * Redirect URLs retained by the old site and WordPress-native post archives.
 */
function am_seo_redirect_legacy_routes() {
	if (!am_seo_is_public_request() || headers_sent()) {
		return;
	}

	$path = am_seo_request_path();
	$redirects = array(
		'/category/blogs'            => '/blogs',
		'/ravenscourt'              => '/nurseries/hammersmith',
		'/hammersmith'              => '/nurseries/hammersmith',
		'/heston'                   => '/nurseries/heston',
		'/hounslow'                 => '/nurseries/hounslow',
		'/our-nurseries'            => '/nurseries',
		'/about-us'                 => '/about',
		'/parent-information'       => '/funded-childcare',
		'/contact-us'               => '/contact',
		'/contact-us-hammersmith'   => '/contact/hammersmith',
		'/contact-us-ravenscourt'   => '/contact/hammersmith',
		'/contact-us-heston'        => '/contact/heston',
		'/contact-us-hounslow'      => '/contact/hounslow',
		'/blog'                     => '/blogs',
		'/news'                     => '/events',
		'/sample-page'              => '/',
	);

	// Retire obsolete numeric permalinks retained in Google's historical crawl.
	if (isset($_GET['p']) && is_scalar($_GET['p'])) {
		$post_id = absint(wp_unslash($_GET['p']));
		if ($post_id > 0) {
			$post = get_post($post_id);
			if ($post instanceof WP_Post && 'post' === $post->post_type && am_seo_post_is_ready($post)) {
				wp_safe_redirect(am_seo_site_url('/blogs/' . $post->post_name), 301, 'Alexandra SEO');
			} else {
				wp_safe_redirect(am_seo_site_url('/blogs'), 301, 'Alexandra SEO');
			}
			exit;
		}
	}

	// Old dated WordPress feeds are not public landing pages; consolidate them
	// into the corresponding route-aware article when the post still exists.
	if (preg_match('#^/[0-9]{4}/[0-9]{2}/[0-9]{2}/([a-z0-9-]+)/feed$#', $path, $matches)) {
		$post = am_seo_post_by_slug($matches[1], 'post');
		$destination = ($post instanceof WP_Post && am_seo_post_is_ready($post))
			? '/blogs/' . $post->post_name
			: '/blogs';
		wp_safe_redirect(am_seo_site_url($destination), 301, 'Alexandra SEO');
		exit;
	}

	if (isset($redirects[$path])) {
		wp_safe_redirect(am_seo_site_url($redirects[$path]), 301, 'Alexandra SEO');
		exit;
	}

	if (is_singular('post')) {
		$post = get_queried_object();
		if ($post instanceof WP_Post && am_seo_post_is_ready($post)) {
			wp_safe_redirect(am_seo_site_url('/blogs/' . $post->post_name), 301, 'Alexandra SEO');
			exit;
		}
	}

	if (is_category() || is_tag() || is_date() || is_author()) {
		wp_safe_redirect(am_seo_site_url('/blogs'), 301, 'Alexandra SEO');
		exit;
	}
}
add_action('template_redirect', 'am_seo_redirect_legacy_routes', -20);

/**
 * A few React blog slugs also exist as WordPress attachment slugs. Prevent
 * Yoast and WordPress from redirecting valid SPA routes to an uploaded file or
 * a native permalink; the integration owns those routes and their canonicals.
 */
function am_seo_prevent_attachment_redirect($url, $attachment) {
	if (am_seo_is_public_request() && am_seo_meta_for_request()['found']) {
		return '';
	}
	return $url;
}
add_filter('wpseo_attachment_redirect_url', 'am_seo_prevent_attachment_redirect', PHP_INT_MAX, 2);

function am_seo_prevent_core_canonical_redirect($redirect_url, $requested_url) {
	if (am_seo_is_public_request() && am_seo_meta_for_request()['found']) {
		return false;
	}
	return $redirect_url;
}
add_filter('redirect_canonical', 'am_seo_prevent_core_canonical_redirect', PHP_INT_MAX, 2);

/**
 * Replace the SPA's blanket 200 response with a real 404 for unknown routes.
 */
function am_seo_enforce_route_status() {
	if (!am_seo_is_public_request()) {
		return;
	}

	$path = am_seo_request_path();
	if (in_array($path, array('/sitemap.xml', '/sitemap_index.xml', '/wp-sitemap.xml', '/robots.txt'), true)) {
		return;
	}

	$meta = am_seo_meta_for_request();
	if (!$meta['found']) {
		status_header(404);
		nocache_headers();
	}
}
add_action('template_redirect', 'am_seo_enforce_route_status', 999);

/**
 * WordPress/Yoast title, canonical, robots and social metadata integration.
 */
function am_seo_document_title($title) {
	if (!am_seo_is_public_request()) {
		return $title;
	}
	return am_seo_meta_for_request()['title'];
}
add_filter('pre_get_document_title', 'am_seo_document_title', 99);
add_filter('wpseo_title', 'am_seo_document_title', 99);

function am_seo_description($description) {
	if (!am_seo_is_public_request()) {
		return $description;
	}
	return am_seo_meta_for_request()['description'];
}
add_filter('wpseo_metadesc', 'am_seo_description', 99);
add_filter('wpseo_opengraph_desc', 'am_seo_description', 99);
add_filter('wpseo_twitter_description', 'am_seo_description', 99);

function am_seo_canonical($canonical) {
	if (!am_seo_is_public_request()) {
		return $canonical;
	}
	$meta = am_seo_meta_for_request();
	return $meta['found'] ? $meta['canonical'] : false;
}
add_filter('wpseo_opengraph_url', 'am_seo_canonical', 99);

/**
 * Yoast does not present a canonical for SPA routes that WordPress initially
 * resolves as a 404. Own the canonical output so every valid React route gets
 * exactly one canonical while genuine 404s get none.
 */
function am_seo_disable_yoast_canonical($canonical) {
	return am_seo_is_public_request() ? false : $canonical;
}
add_filter('wpseo_canonical', 'am_seo_disable_yoast_canonical', PHP_INT_MAX);

function am_seo_output_canonical() {
	if (!am_seo_is_public_request()) {
		return;
	}

	$meta = am_seo_meta_for_request();
	if ($meta['found']) {
		echo '<link rel="canonical" href="' . esc_url($meta['canonical']) . '">' . "\n";
	}
}
add_action('wp_head', 'am_seo_output_canonical', 3);

/**
 * Yoast omits og:url when a valid SPA route begins as a WordPress 404. Fill
 * only that gap; native WordPress requests keep Yoast's own presenter.
 */
function am_seo_output_spa_opengraph_url() {
	if (!am_seo_is_public_request() || !is_404()) {
		return;
	}

	$meta = am_seo_meta_for_request();
	if ($meta['found']) {
		echo '<meta property="og:url" content="' . esc_url($meta['canonical']) . '">' . "\n";
	}
}
add_action('wp_head', 'am_seo_output_spa_opengraph_url', 3);

function am_seo_social_title($title) {
	return am_seo_is_public_request() ? am_seo_meta_for_request()['title'] : $title;
}
add_filter('wpseo_opengraph_title', 'am_seo_social_title', 99);
add_filter('wpseo_twitter_title', 'am_seo_social_title', 99);

function am_seo_social_image($image) {
	return am_seo_is_public_request() ? am_seo_meta_for_request()['image'] : $image;
}
add_filter('wpseo_opengraph_image', 'am_seo_social_image', 99);
add_filter('wpseo_twitter_image', 'am_seo_social_image', 99);

function am_seo_opengraph_type($type) {
	return am_seo_is_public_request() ? am_seo_meta_for_request()['type'] : $type;
}
add_filter('wpseo_opengraph_type', 'am_seo_opengraph_type', 99);

function am_seo_yoast_robots($robots) {
	if (!am_seo_is_public_request()) {
		return $robots;
	}
	return am_seo_meta_for_request()['index']
		? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'
		: 'noindex, follow';
}
add_filter('wpseo_robots', 'am_seo_yoast_robots', 99);

function am_seo_wordpress_robots($robots) {
	if (!am_seo_is_public_request()) {
		return $robots;
	}

	if (am_seo_meta_for_request()['index']) {
		unset($robots['noindex'], $robots['nofollow']);
		$robots['max-image-preview'] = 'large';
	} else {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset($robots['nofollow']);
	}

	return $robots;
}
add_filter('wp_robots', 'am_seo_wordpress_robots', 99);

function am_seo_yoast_active() {
	return defined('WPSEO_VERSION') || class_exists('WPSEO_Options');
}

/**
 * Fallback output keeps the site correct if Yoast is temporarily inactive.
 */
function am_seo_output_fallback_meta() {
	if (!am_seo_is_public_request() || am_seo_yoast_active()) {
		return;
	}

	$meta = am_seo_meta_for_request();
	echo '<meta name="description" content="' . esc_attr($meta['description']) . '">' . "\n";
	echo '<meta property="og:locale" content="en_GB">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr($meta['type']) . '">' . "\n";
	echo '<meta property="og:site_name" content="Alexandra Montessori">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr($meta['title']) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr($meta['description']) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url($meta['canonical']) . '">' . "\n";
	echo '<meta property="og:image" content="' . esc_url($meta['image']) . '">' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr($meta['title']) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr($meta['description']) . '">' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url($meta['image']) . '">' . "\n";
	echo '<script type="application/ld+json">' . wp_json_encode(am_seo_schema_document($meta), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}
add_action('wp_head', 'am_seo_output_fallback_meta', 3);

function am_seo_remove_core_canonical() {
	remove_action('wp_head', 'rel_canonical');
}
add_action('after_setup_theme', 'am_seo_remove_core_canonical', 99);

function am_seo_nursery_schema($meta) {
	$post = $meta['post'];
	if (!$post instanceof WP_Post) {
		return array();
	}

	$name     = am_seo_clean_text(get_the_title($post), 80);
	$address  = trim((string) get_post_meta($post->ID, '_am_nursery_address', true));
	$postcode = trim((string) get_post_meta($post->ID, '_am_nursery_postcode', true));
	$area     = trim((string) get_post_meta($post->ID, '_am_nursery_area', true));

	return array(
		'@type'              => 'ChildCare',
		'@id'                => am_seo_site_url('/nurseries/' . $post->post_name) . '#childcare',
		'name'               => 'Alexandra Montessori ' . $name,
		'url'                => am_seo_site_url('/nurseries/' . $post->post_name),
		'description'        => am_seo_clean_text(get_post_meta($post->ID, '_am_nursery_short', true), 240),
		'image'              => get_post_meta($post->ID, '_am_nursery_hero_image', true) ?: $meta['image'],
		'telephone'          => (string) get_post_meta($post->ID, '_am_nursery_phone', true),
		'email'              => (string) get_post_meta($post->ID, '_am_nursery_email', true),
		'priceRange'         => '££',
		'address'            => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $address,
			'addressLocality' => $area ?: $name,
			'postalCode'      => $postcode,
			'addressCountry'  => 'GB',
		),
		'parentOrganization' => array('@id' => am_seo_site_url('/') . '#organization'),
	);
}

/**
 * Find the published nursery record represented by a human-facing location.
 *
 * Job and event editors select locations by label (for example, Hammersmith),
 * while the nursery address lives on the am_nursery post. Keeping the lookup
 * here means Google receives the same verified address that staff maintain in
 * WordPress instead of a second hard-coded copy.
 */
function am_seo_nursery_for_location($location) {
	$key = sanitize_title(am_seo_clean_text($location, 80));
	if ('' === $key || 'all-nurseries' === $key) {
		return null;
	}

	$nursery = get_page_by_path($key, OBJECT, 'am_nursery');
	if ($nursery instanceof WP_Post && 'publish' === $nursery->post_status) {
		return $nursery;
	}

	$nurseries = get_posts(array(
		'post_type'      => 'am_nursery',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	));

	foreach ($nurseries as $candidate) {
		$candidate_keys = array_filter(array_unique(array(
			sanitize_title((string) $candidate->post_name),
			sanitize_title(am_seo_clean_text(get_the_title($candidate), 80)),
			sanitize_title(am_seo_clean_text(get_post_meta($candidate->ID, '_am_nursery_area', true), 80)),
		)));

		foreach ($candidate_keys as $candidate_key) {
			if ($key === $candidate_key || str_contains($key, $candidate_key) || str_contains($candidate_key, $key)) {
				return $candidate;
			}
		}
	}

	return null;
}

function am_seo_postal_address_for_nursery($nursery, $fallback_locality = 'London') {
	if (!$nursery instanceof WP_Post) {
		return array(
			'@type'           => 'PostalAddress',
			'addressLocality' => $fallback_locality ?: 'London',
			'addressRegion'   => 'Greater London',
			'addressCountry'  => 'GB',
		);
	}

	$street   = trim((string) get_post_meta($nursery->ID, '_am_nursery_address', true));
	$postcode = trim((string) get_post_meta($nursery->ID, '_am_nursery_postcode', true));
	$parts    = array_values(array_filter(array_map('trim', explode(',', $street))));
	$locality = $parts ? (string) end($parts) : '';

	if ('' === $locality) {
		$locality = trim((string) get_post_meta($nursery->ID, '_am_nursery_area', true));
	}
	if ('' === $locality) {
		$locality = $fallback_locality ?: am_seo_clean_text(get_the_title($nursery), 80);
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
function am_seo_job_salary_schema($salary_text) {
	$salary_text = am_seo_clean_text($salary_text, 120);
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

function am_seo_entity_schema($meta) {
	$post = $meta['post'];
	if ('nursery' === $meta['entity']) {
		return am_seo_nursery_schema($meta);
	}
	if (!$post instanceof WP_Post) {
		return array();
	}

	$common = array(
		'@id'         => $meta['canonical'] . '#primary',
		'url'         => $meta['canonical'],
		'name'        => am_seo_clean_text(get_the_title($post), 120),
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
			'publisher'        => array('@id' => am_seo_site_url('/') . '#organization'),
		));
	}

	if ('event' === $meta['entity']) {
		$location = (string) get_post_meta($post->ID, '_am_event_location', true);
		$nursery  = am_seo_nursery_for_location($location);
		$place    = array(
			'@type' => 'Place',
			'name'  => $nursery instanceof WP_Post ? 'Alexandra Montessori ' . am_seo_clean_text(get_the_title($nursery), 80) : ($location ?: 'Alexandra Montessori'),
		);
		if ($nursery instanceof WP_Post) {
			$place['address'] = am_seo_postal_address_for_nursery($nursery, $location);
		}

		return array_merge($common, array(
			'@type'               => 'Event',
			'startDate'           => (string) get_post_meta($post->ID, '_am_event_date', true),
			'eventStatus'         => 'https://schema.org/EventScheduled',
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
			'location'            => $place,
			'organizer'           => array('@id' => am_seo_site_url('/') . '#organization'),
		));
	}

	if ('job' === $meta['entity']) {
		$location   = (string) get_post_meta($post->ID, '_am_job_location', true);
		$nursery    = am_seo_nursery_for_location($location);
		$job_schema = array_merge($common, array(
			'@type'              => 'JobPosting',
			'title'              => $common['name'],
			'datePosted'         => get_post_time('Y-m-d', true, $post),
			'employmentType'     => (string) get_post_meta($post->ID, '_am_job_type', true),
			'hiringOrganization' => array('@id' => am_seo_site_url('/') . '#organization'),
			'jobLocation'        => array(
				'@type'   => 'Place',
				'name'    => $nursery instanceof WP_Post ? 'Alexandra Montessori ' . am_seo_clean_text(get_the_title($nursery), 80) : ($location ?: 'Alexandra Montessori, London'),
				'address' => am_seo_postal_address_for_nursery($nursery, $location ?: 'London'),
			),
		));

		$salary = am_seo_job_salary_schema(get_post_meta($post->ID, '_am_job_salary', true));
		if ($salary) {
			$job_schema['baseSalary'] = $salary;
		}

		$valid_through = trim((string) get_post_meta($post->ID, '_am_job_valid_through', true));
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valid_through)) {
			$job_schema['validThrough'] = $valid_through . 'T23:59:59+00:00';
		}

		return $job_schema;
	}

	return array();
}

function am_seo_schema_document($meta) {
	$home = am_seo_site_url('/');
	$graph = array(
		array(
			'@type' => 'Organization',
			'@id'   => $home . '#organization',
			'name'  => 'Alexandra Montessori',
			'url'   => $home,
			'logo'  => array('@type' => 'ImageObject', 'url' => am_seo_theme_image('assets/logo-badge.webp')),
		),
		array(
			'@type'     => 'WebSite',
			'@id'       => $home . '#website',
			'url'       => $home,
			'name'      => 'Alexandra Montessori',
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
	$entity = am_seo_entity_schema($meta);
	if ($entity) {
		$graph[] = $entity;
	}

	return array('@context' => 'https://schema.org', '@graph' => $graph);
}

/**
 * Correct Yoast's graph, whose queried object is otherwise the one SPA shell.
 */
function am_seo_yoast_schema_graph($graph) {
	if (!am_seo_is_public_request() || !is_array($graph)) {
		return $graph;
	}

	$meta = am_seo_meta_for_request();
	$home = am_seo_site_url('/');
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
			$piece['name'] = 'Alexandra Montessori';
		}
		if (in_array('Organization', $types, true)) {
			$piece['@id']  = $home . '#organization';
			$piece['url']  = $home;
			$piece['name'] = 'Alexandra Montessori';
		}
		$clean[] = $piece;
	}

	$entity = am_seo_entity_schema($meta);
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
	$fallback = am_seo_schema_document($meta);
	foreach ($fallback['@graph'] as $piece) {
		$types = isset($piece['@type']) ? (array) $piece['@type'] : array();
		if (array_intersect($types, $required) && !array_intersect($types, $present)) {
			$clean[] = $piece;
			$present = array_merge($present, $types);
		}
	}
	return $clean;
}
add_filter('wpseo_schema_graph', 'am_seo_yoast_schema_graph', 99);

/**
 * A single authoritative sitemap containing the actual React routes.
 */
function am_seo_sitemap_entries() {
	$entries = array();
	foreach (am_seo_static_routes() as $path => $route) {
		if (isset($route['index']) && !$route['index']) {
			continue;
		}
		$entries[] = array('loc' => am_seo_site_url($path));
	}

	$nurseries = get_posts(array(
		'post_type'      => 'am_nursery',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	));
	foreach ($nurseries as $post) {
		if (!am_seo_post_is_ready($post)) {
			continue;
		}
		$lastmod = get_post_modified_time('c', true, $post);
		$image   = get_post_meta($post->ID, '_am_nursery_hero_image', true);
		$entries[] = array('loc' => am_seo_site_url('/nurseries/' . $post->post_name), 'lastmod' => $lastmod, 'image' => $image);
		$entries[] = array('loc' => am_seo_site_url('/contact/' . $post->post_name), 'lastmod' => $lastmod, 'image' => $image);
	}

	$collections = array(
		'post' => array('prefix' => '/blogs/', 'ready' => true),
		'am_event' => array('prefix' => '/events/', 'ready' => true),
		'am_job' => array('prefix' => '/careers/vacancies/', 'ready' => true),
	);
	foreach ($collections as $post_type => $config) {
		$posts = get_posts(array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		));
		foreach ($posts as $post) {
			if (!am_seo_post_is_ready($post)) {
				continue;
			}
			$entries[] = array(
				'loc'     => am_seo_site_url($config['prefix'] . $post->post_name),
				'lastmod' => get_post_modified_time('c', true, $post),
				'image'   => get_the_post_thumbnail_url($post->ID, 'full') ?: '',
			);
		}
	}

	$unique = array();
	foreach ($entries as $entry) {
		$unique[$entry['loc']] = $entry;
	}
	ksort($unique, SORT_NATURAL | SORT_FLAG_CASE);
	return array_values($unique);
}

function am_seo_redirect_deprecated_sitemaps() {
	if (!am_seo_is_public_request()) {
		return;
	}

	if (in_array(am_seo_request_path(), array('/sitemap_index.xml', '/wp-sitemap.xml'), true)) {
		wp_safe_redirect(am_seo_site_url('/sitemap.xml'), 301, 'Alexandra SEO');
		exit;
	}
}
add_action('init', 'am_seo_redirect_deprecated_sitemaps', -1000);

function am_seo_render_sitemap() {
	if (!am_seo_is_public_request()) {
		return;
	}

	$path = am_seo_request_path();
	if (in_array($path, array('/sitemap_index.xml', '/wp-sitemap.xml'), true)) {
		wp_safe_redirect(am_seo_site_url('/sitemap.xml'), 301, 'Alexandra SEO');
		exit;
	}
	if ('/sitemap.xml' !== $path) {
		return;
	}

	status_header(200);
	header('Content-Type: application/xml; charset=UTF-8');
	header('Cache-Control: public, max-age=3600');
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
	foreach (am_seo_sitemap_entries() as $entry) {
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
add_action('template_redirect', 'am_seo_render_sitemap', -100);

add_filter('wp_sitemaps_enabled', '__return_false', 99);
add_filter('wpseo_sitemap_enabled', '__return_false', 99);

function am_seo_robots_txt($output, $public) {
	if (!$public) {
		return "User-agent: *\nDisallow: /\n";
	}

	return "User-agent: *\n"
		. "Disallow: /wp-admin/\n"
		. "Allow: /wp-admin/admin-ajax.php\n"
		. "Disallow: /wp-content/uploads/am-private/\n\n"
		. 'Sitemap: ' . am_seo_site_url('/sitemap.xml') . "\n";
}
add_filter('robots_txt', 'am_seo_robots_txt', PHP_INT_MAX, 2);
