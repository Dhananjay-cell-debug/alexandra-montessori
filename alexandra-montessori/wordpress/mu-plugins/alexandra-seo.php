<?php
/**
 * Plugin Name: Alexandra SEO Integration
 * Description: Route-aware SEO, structured data, redirects and sitemap support for the Alexandra React/WordPress site.
 * Version: 1.2.0
 * Author: Alexandra build
 */

if (!defined('ABSPATH')) {
	exit;
}

define('AM_SEO_VERSION', '1.2.0');

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
	return apply_filters('am_seo_static_routes', array(
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
	));
}

/**
 * Non-coder editable page titles/descriptions (wp-admin → Page SEO).
 *
 * Two independent overrides, applied in this priority order (most specific
 * wins): computed default (from am_seo_static_routes()/entity logic above)
 * < the Page SEO settings screen (keyed by URL path, for any route) < Yoast's
 * own per-post Title/Description box (for routes backed by a real WP post -
 * a nursery, blog article, event or job - since that is the field an editor
 * already sees on that post's edit screen).
 */
define('AM_SEO_OVERRIDES_OPTION', 'am_seo_page_overrides');

function am_seo_get_overrides() {
	$stored = get_option(AM_SEO_OVERRIDES_OPTION, array());
	return is_array($stored) ? $stored : array();
}

/** Accept the new comma-separated multi-image field and the old single ID. */
function am_seo_parse_image_ids($value) {
	if (is_string($value)) {
		$value = preg_split('/\s*,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
	}
	if (!is_array($value)) {
		$value = array($value);
	}
	$ids = array_values(array_unique(array_filter(array_map('absint', $value))));
	return $ids;
}

function am_seo_override_image_ids($override) {
	if (!is_array($override)) {
		return array();
	}
	if (array_key_exists('image_ids', $override)) {
		return am_seo_parse_image_ids($override['image_ids']);
	}
	return am_seo_parse_image_ids($override['image_id'] ?? 0);
}

function am_seo_attachment_image_urls($ids, $size = 'large') {
	$urls = array();
	foreach (am_seo_parse_image_ids($ids) as $id) {
		$url = wp_get_attachment_image_url($id, $size);
		if ($url) {
			$urls[] = $url;
		}
	}
	return array_values(array_unique($urls));
}

function am_seo_normalise_meta_images(&$meta) {
	$images = isset($meta['images']) && is_array($meta['images']) ? $meta['images'] : array();
	if (empty($images) && !empty($meta['image'])) {
		$images[] = $meta['image'];
	}
	$images = array_values(array_unique(array_filter(array_map('esc_url_raw', $images))));
	$meta['images'] = $images;
	$meta['image']  = $images[0] ?? '';
}

/**
 * Reduce any typed URL (full link, relative path, with/without trailing
 * slash) to the same canonical path key am_seo_request_path() produces, so a
 * client pasting "https://alexandramontessori.co.uk/fees/" still matches "/fees".
 */
function am_seo_normalise_override_path($path) {
	$path = trim((string) $path);
	if ('' === $path) {
		return '';
	}
	$path = (string) wp_parse_url($path, PHP_URL_PATH);
	$path = '/' . ltrim((string) $path, '/');
	$path = preg_replace('#/+#', '/', $path);
	if ('/' !== $path) {
		$path = untrailingslashit($path);
	}
	return $path ?: '';
}

function am_seo_apply_page_override(&$meta, $path) {
	$overrides = am_seo_get_overrides();
	if (empty($overrides[$path]) || !is_array($overrides[$path])) {
		return;
	}
	$title    = trim((string) ($overrides[$path]['title'] ?? ''));
	$desc     = trim((string) ($overrides[$path]['description'] ?? ''));
	$image_ids = am_seo_override_image_ids($overrides[$path]);

	if ('' !== $title) {
		$meta['title'] = $title;
	}
	if ('' !== $desc) {
		$meta['description'] = $desc;
	}
	if (!empty($image_ids)) {
		$urls = am_seo_attachment_image_urls($image_ids, 'large');
		if (!empty($urls)) {
			$meta['image']  = $urls[0];
			$meta['images'] = $urls;
		}
	}
}

/**
 * Yoast already renders a familiar Title/Meta description box, with its own
 * live Google-style preview, on every nursery/blog/event/job edit screen.
 * Respect it instead of silently discarding it, which is what happened before
 * this function existed (am_seo_document_title()/am_seo_description() always
 * won via wpseo_title/wpseo_metadesc regardless of what an editor typed here).
 */
function am_seo_apply_yoast_post_override(&$meta) {
	$post = $meta['post'];
	if (!$post instanceof WP_Post) {
		return;
	}

	$title = trim((string) get_post_meta($post->ID, '_yoast_wpseo_title', true));
	$desc  = trim((string) get_post_meta($post->ID, '_yoast_wpseo_metadesc', true));

	if ('' !== $title) {
		$meta['title'] = function_exists('wpseo_replace_vars') ? wpseo_replace_vars($title, $post) : $title;
	}
	if ('' !== $desc) {
		$meta['description'] = function_exists('wpseo_replace_vars') ? wpseo_replace_vars($desc, $post) : $desc;
	}
}

function am_seo_meta_defaults($path) {
	return array(
		'path'        => $path,
		'canonical'   => am_seo_site_url($path),
		'title'       => 'Page Not Found | Alexandra Montessori',
		'description' => 'The requested page could not be found.',
		'image'       => am_seo_theme_image(),
		'images'      => array(),
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

	$meta = apply_filters('am_seo_route_meta', $meta, $path);
	if (isset($_GET['s']) || isset($_GET['am_visual_preview']) || isset($_GET['am_vb_saved_session']) || isset($_GET['am_ws_studio']) || isset($_GET['am_session'])) {
		$meta['index'] = false;
	}

	am_seo_apply_page_override($meta, $path);
	am_seo_apply_yoast_post_override($meta);

	$meta['title']       = am_seo_clean_text($meta['title'], 120);
	$meta['description'] = am_seo_clean_text($meta['description'], 160);
	$meta['canonical']   = am_seo_site_url($meta['path']);
	am_seo_normalise_meta_images($meta);

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

/**
 * The React app renders its own <title>/<meta description> on every route
 * (Seo.jsx) and, being client-side, always wins the render race against
 * whatever this file or Yoast already put in the server HTML — a wp-admin
 * "Page SEO" edit would otherwise be visible in "view source" but never in
 * the browser tab or to Google. Expose the fully-resolved (default, or the
 * client's saved override) title/description for every static route so
 * Seo.jsx can use the same value instead of its own hardcoded per-page prop.
 */
function am_seo_resolved_static_routes() {
	$resolved = array();
	foreach (am_seo_static_routes() as $path => $route) {
		$meta = array(
			'title'       => $route['title'] ?? '',
			'description' => $route['description'] ?? '',
			'image'       => $route['image'] ?? am_seo_theme_image(),
			'images'      => array(),
		);
		am_seo_apply_page_override($meta, $path);
		am_seo_normalise_meta_images($meta);
		$resolved[$path] = array(
			'title'       => am_seo_clean_text($meta['title'], 120),
			'description' => am_seo_clean_text($meta['description'], 160),
			'image'       => $meta['image'],
			'images'      => $meta['images'],
		);
	}
	// Also expose the currently requested dynamic route (nursery, article,
	// event, vacancy or custom path) with all overrides already resolved.
	$current = am_seo_meta_for_request();
	if (!empty($current['found'])) {
		$resolved[$current['path']] = array(
			'title'       => $current['title'],
			'description' => $current['description'],
			'image'       => $current['image'],
			'images'      => $current['images'],
		);
	}
	return $resolved;
}

function am_seo_output_route_map() {
	if (!am_seo_is_public_request()) {
		return;
	}
	echo '<script>window.amSeoRoutes = ' . wp_json_encode(am_seo_resolved_static_routes(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';</script>' . "\n";
}
add_action('wp_head', 'am_seo_output_route_map', 2);

/**
 * Keep the live browser head aligned with Page SEO even while an older React
 * bundle is cached. The server tags are already authoritative for crawlers;
 * this small bridge runs after React's initial mount and after SPA navigation
 * so the visible tab title and inspected metadata cannot be overwritten by
 * stale hardcoded component props.
 */
function am_seo_output_browser_sync() {
	if (!am_seo_is_public_request()) {
		return;
	}
	?>
	<script>
	(function () {
		var syncScheduled = false;
		function routeKey() {
			var path = window.location.pathname.replace(/\/+$/, '') || '/';
			return path;
		}
		function setMeta(selector, attrs, content) {
			var el = document.head.querySelector(selector);
			if (!el) {
				el = document.createElement('meta');
				Object.keys(attrs).forEach(function (key) { el.setAttribute(key, attrs[key]); });
				document.head.appendChild(el);
			}
			if (el.getAttribute('content') !== (content || '')) {
				el.setAttribute('content', content || '');
			}
		}
		function applySeo() {
			var route = window.amSeoRoutes && window.amSeoRoutes[routeKey()];
			if (!route) { return; }
			if (route.title && document.title !== route.title) { document.title = route.title; }
			setMeta('meta[name="description"]', { name: 'description' }, route.description);
			setMeta('meta[property="og:title"]', { property: 'og:title' }, route.title);
			setMeta('meta[property="og:description"]', { property: 'og:description' }, route.description);
			var images = Array.isArray(route.images) && route.images.length ? route.images : (route.image ? [route.image] : []);
			var existing = Array.from(document.head.querySelectorAll('meta[property="og:image"]'));
			var existingUrls = existing.map(function (node) { return node.getAttribute('content') || ''; });
			var imagesMatch = existingUrls.length === images.length && existingUrls.every(function (url, index) { return url === images[index]; });
			if (!imagesMatch) {
				// Rewrite the existing tags in place instead of removing them. Some are
				// owned by React (the hoisted <Seo> tags); pulling one out of <head>
				// behind React's back makes React throw when the route changes and it
				// unmounts that tag, which froze the old page on screen while the URL
				// had already moved on.
				images.forEach(function (url, index) {
					var image = existing[index];
					if (!image) {
						image = document.createElement('meta');
						image.setAttribute('property', 'og:image');
						image.setAttribute('data-am-seo-browser-image', '1');
						document.head.appendChild(image);
					}
					if (image.getAttribute('content') !== url) {
						image.setAttribute('content', url);
					}
				});
				// Surplus tags: only ever remove ones this script created itself.
				existing.slice(images.length).forEach(function (node) {
					if (node.hasAttribute('data-am-seo-browser-image')) { node.remove(); }
				});
			}
			if (images[0]) {
				setMeta('meta[name="twitter:image"]', { name: 'twitter:image' }, images[0]);
			}
		}
		function scheduleSeo() {
			if (syncScheduled) { return; }
			syncScheduled = true;
			window.requestAnimationFrame(function () {
				window.requestAnimationFrame(function () {
					applySeo();
					syncScheduled = false;
				});
			});
		}
		['pushState', 'replaceState'].forEach(function (method) {
			var original = window.history[method];
			window.history[method] = function () {
				var result = original.apply(this, arguments);
				scheduleSeo();
				return result;
			};
		});
		window.addEventListener('popstate', scheduleSeo);
		new MutationObserver(scheduleSeo).observe(document.head, { childList: true, subtree: true, attributes: true });
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', scheduleSeo, { once: true });
		} else {
			scheduleSeo();
		}
		window.addEventListener('load', scheduleSeo, { once: true });
	})();
	</script>
	<?php
}
add_action('wp_head', 'am_seo_output_browser_sync', 2);

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
 * Yoast only emits og:image when ITS OWN resolution chain finds something (a
 * featured image, or a sitewide default configured in Yoast's settings) — it
 * does not consult the wpseo_opengraph_image filter at all if it decides
 * there is nothing to output in the first place. Confirmed empirically: the
 * live homepage shipped og:title/description/url correctly but zero og:image
 * tags, silently breaking every WhatsApp/Facebook/LinkedIn/iMessage link
 * preview. Yoast's Twitter card presenter is not affected (it resolves
 * wpseo_twitter_image successfully via the existing filter below), only
 * OpenGraph's image is ever missing, so only that one needs a guaranteed
 * fallback. Output it unconditionally so a working image always exists,
 * independent of whether Yoast's own logic finds one. A duplicate og:image
 * tag is harmless (crawlers use the first); this one is placed early in
 * <head> (priority 3) so it is that first tag.
 */
function am_seo_output_social_image() {
	if (!am_seo_is_public_request()) {
		return;
	}

	$meta = am_seo_meta_for_request();
	if (!$meta['image']) {
		return;
	}
	foreach ($meta['images'] as $image) {
		echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
	}
}
add_action('wp_head', 'am_seo_output_social_image', 3);

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
	foreach ($meta['images'] as $image) {
		echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
	}
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

/* =========================================================================
 * "Page SEO" settings screen — a non-coder editor for the title and
 * description Google shows in search results, for the site's static React
 * routes (Home, About, Fees, Curriculum, ...). Dynamic content (nurseries,
 * blog articles, events, jobs) already gets this via Yoast's own per-post
 * box; see am_seo_apply_yoast_post_override() above.
 *
 * Deliberately its own top-level admin page, not a Visual Builder panel: the
 * builder edits what a page LOOKS like, this edits what Google shows about
 * it, and it must keep working even while the builder is mid-rework.
 * ========================================================================= */

const AM_SEO_CUSTOM_ROWS = 8;

function am_seo_admin_page_slug() {
	return 'am_seo_settings';
}

function am_seo_route_label($path) {
	$labels = array(
		'/'                     => 'Home',
		'/nurseries'            => 'Our Nurseries (index)',
		'/about'                => 'About',
		'/curriculum'           => 'Curriculum',
		'/fees'                 => 'Fees',
		'/fee-calculator'       => 'Fee Calculator',
		'/funded-childcare'     => 'Funded Childcare',
		'/blogs'                => 'Blogs (index)',
		'/food-hygiene-rating'  => 'Food Hygiene Ratings',
		'/events'               => 'Events',
		'/testimonials'         => 'Testimonials',
		'/careers'              => 'Careers',
		'/careers/vacancies'    => 'Careers – Vacancies',
		'/careers/apply'        => 'Careers – Apply',
		'/check-availability'   => 'Check Availability',
		'/contact'              => 'Contact',
		'/privacy'              => 'Privacy Policy',
	);
	if (isset($labels[$path])) {
		return $labels[$path];
	}
	$label = trim(str_replace(array('/', '-'), array(' / ', ' '), $path), ' /');
	return '' !== $label ? $label : $path;
}

function am_seo_register_admin_menu() {
	$hook = add_menu_page(
		__('Page SEO', 'alexandra-seo'),
		__('Page SEO', 'alexandra-seo'),
		'manage_options',
		am_seo_admin_page_slug(),
		'am_seo_render_admin_page',
		'dashicons-search',
		58
	);
	// The thumbnail-image field opens the core Media Library picker.
	add_action("admin_enqueue_scripts", function ($current_hook) use ($hook) {
		if ($current_hook === $hook) {
			wp_enqueue_media();
		}
	});
}
add_action('admin_menu', 'am_seo_register_admin_menu');

function am_seo_handle_admin_save() {
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You do not have permission to do this.', 'alexandra-seo'));
	}
	check_admin_referer('am_seo_save_overrides');

	$routes    = am_seo_static_routes();
	$overrides = array();

	$titles = (isset($_POST['am_seo_title']) && is_array($_POST['am_seo_title'])) ? wp_unslash($_POST['am_seo_title']) : array();
	$descs  = (isset($_POST['am_seo_description']) && is_array($_POST['am_seo_description'])) ? wp_unslash($_POST['am_seo_description']) : array();

	$images = (isset($_POST['am_seo_images']) && is_array($_POST['am_seo_images'])) ? wp_unslash($_POST['am_seo_images']) : array();

	foreach ($routes as $path => $route) {
		$title    = isset($titles[$path]) ? sanitize_text_field($titles[$path]) : '';
		$desc     = isset($descs[$path]) ? sanitize_textarea_field($descs[$path]) : '';
		$image_ids = am_seo_parse_image_ids($images[$path] ?? array());
		if ('' !== $title || '' !== $desc || !empty($image_ids)) {
			$overrides[$path] = array('title' => $title, 'description' => $desc, 'image_ids' => $image_ids);
		}
	}

	$custom_paths  = (isset($_POST['am_seo_custom_path']) && is_array($_POST['am_seo_custom_path'])) ? wp_unslash($_POST['am_seo_custom_path']) : array();
	$custom_titles = (isset($_POST['am_seo_custom_title']) && is_array($_POST['am_seo_custom_title'])) ? wp_unslash($_POST['am_seo_custom_title']) : array();
	$custom_descs  = (isset($_POST['am_seo_custom_description']) && is_array($_POST['am_seo_custom_description'])) ? wp_unslash($_POST['am_seo_custom_description']) : array();
	$custom_images = (isset($_POST['am_seo_custom_images']) && is_array($_POST['am_seo_custom_images'])) ? wp_unslash($_POST['am_seo_custom_images']) : array();

	foreach ($custom_paths as $i => $raw_path) {
		$path = am_seo_normalise_override_path($raw_path);
		if ('' === $path || isset($routes[$path])) {
			continue; // Blank row, or it just duplicates a page already listed above.
		}
		$title    = isset($custom_titles[$i]) ? sanitize_text_field($custom_titles[$i]) : '';
		$desc     = isset($custom_descs[$i]) ? sanitize_textarea_field($custom_descs[$i]) : '';
		$image_ids = am_seo_parse_image_ids($custom_images[$i] ?? array());
		if ('' !== $title || '' !== $desc || !empty($image_ids)) {
			$overrides[$path] = array('title' => $title, 'description' => $desc, 'image_ids' => $image_ids);
		}
	}

	update_option(AM_SEO_OVERRIDES_OPTION, $overrides, false);

	wp_safe_redirect(add_query_arg(
		array('page' => am_seo_admin_page_slug(), 'am_seo_saved' => '1'),
		admin_url('admin.php')
	));
	exit;
}
add_action('admin_post_am_seo_save_overrides', 'am_seo_handle_admin_save');

function am_seo_render_preview_row($path, $default_title, $default_description, $default_image, $value_title, $value_description, $value_image_ids, $value_image_urls, $field_prefix, $show_path_input = false, $row_index = null) {
	$url    = am_seo_site_url($path);
	$domain = wp_parse_url($url, PHP_URL_HOST);
	$name   = $show_path_input ? '[' . esc_attr($row_index) . ']' : '[' . esc_attr($path) . ']';
	$value_image_ids  = am_seo_parse_image_ids($value_image_ids);
	$value_image_urls = is_array($value_image_urls) ? array_values($value_image_urls) : array();
	$shown_image      = $value_image_urls[0] ?? $default_image;
	?>
	<div class="am-seo-card">
		<?php if ($show_path_input) : ?>
			<label class="am-seo-field">
				<span>Page URL (e.g. <code>/new-page</code>)</span>
				<input type="text" name="am_seo_custom_path[<?php echo esc_attr($row_index); ?>]" value="<?php echo esc_attr($path); ?>" placeholder="/example-page" class="am-seo-path-input">
			</label>
		<?php else : ?>
			<h3 class="am-seo-card-title"><?php echo esc_html(am_seo_route_label($path)); ?> <code><?php echo esc_html($path); ?></code></h3>
		<?php endif; ?>

		<label class="am-seo-field">
			<span>SEO title <em>(shown blank = uses the default below)</em></span>
			<input type="text" data-seo-role="title" name="<?php echo esc_attr($field_prefix); ?>_title<?php echo $name; ?>" value="<?php echo esc_attr($value_title); ?>" placeholder="<?php echo esc_attr($default_title); ?>" maxlength="120">
			<span class="am-seo-count" data-seo-count="title">0 / 60 characters (ideal)</span>
		</label>

		<label class="am-seo-field">
			<span>Meta description <em>(shown blank = uses the default below)</em></span>
			<textarea data-seo-role="description" name="<?php echo esc_attr($field_prefix); ?>_description<?php echo $name; ?>" rows="2" placeholder="<?php echo esc_attr($default_description); ?>" maxlength="300"><?php echo esc_textarea($value_description); ?></textarea>
			<span class="am-seo-count" data-seo-count="description">0 / 155 characters (ideal)</span>
		</label>

		<div class="am-seo-field">
			<span>Social images <em>(add as many as you need; the first is the primary link thumbnail)</em></span>
			<div class="am-seo-image-picker">
				<div class="am-seo-image-list" data-seo-role="image-list" data-seo-default-image="<?php echo esc_url($default_image); ?>">
					<?php foreach ($value_image_ids as $image_index => $image_id) :
						$image_url = $value_image_urls[$image_index] ?? wp_get_attachment_image_url($image_id, 'medium');
						if (!$image_url) { continue; }
						?>
						<div class="am-seo-image-item" data-image-id="<?php echo esc_attr($image_id); ?>" data-image-url="<?php echo esc_url($image_url); ?>">
							<img class="am-seo-image-thumb" src="<?php echo esc_url($image_url); ?>" alt="">
							<button type="button" class="button-link-delete am-seo-remove-one-image" aria-label="Remove this image">Remove</button>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="am-seo-image-buttons">
					<button type="button" class="button am-seo-choose-image">Add images</button>
					<button type="button" class="button-link am-seo-remove-all-images" <?php echo empty($value_image_ids) ? 'style="display:none"' : ''; ?>>Remove all (use default)</button>
					<span class="am-seo-image-count" data-seo-role="image-count"></span>
				</div>
				<input type="hidden" data-seo-role="image-ids" name="<?php echo esc_attr($field_prefix); ?>_images<?php echo $name; ?>" value="<?php echo esc_attr(implode(',', $value_image_ids)); ?>">
			</div>
		</div>

		<div class="am-seo-previews">
			<div class="am-seo-preview-block">
				<div class="am-seo-preview-label">Google search preview</div>
				<div class="am-seo-preview">
					<div class="am-seo-preview-url"><?php echo esc_html($url); ?></div>
					<div class="am-seo-preview-title" data-seo-default="<?php echo esc_attr($default_title); ?>"><?php echo esc_html('' !== $value_title ? $value_title : $default_title); ?></div>
					<div class="am-seo-preview-desc" data-seo-default="<?php echo esc_attr($default_description); ?>"><?php echo esc_html('' !== $value_description ? $value_description : $default_description); ?></div>
				</div>
			</div>
			<div class="am-seo-preview-block">
				<div class="am-seo-preview-label">Social share preview</div>
				<div class="am-seo-social-card">
					<img class="am-seo-social-image" data-seo-role="social-image" src="<?php echo esc_url($shown_image); ?>" alt="">
					<div class="am-seo-social-body">
						<div class="am-seo-social-domain"><?php echo esc_html(strtoupper($domain)); ?></div>
						<div class="am-seo-social-title" data-seo-default="<?php echo esc_attr($default_title); ?>"><?php echo esc_html('' !== $value_title ? $value_title : $default_title); ?></div>
						<div class="am-seo-social-desc" data-seo-default="<?php echo esc_attr($default_description); ?>"><?php echo esc_html('' !== $value_description ? $value_description : $default_description); ?></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

function am_seo_render_admin_page() {
	if (!current_user_can('manage_options')) {
		return;
	}

	$routes    = am_seo_static_routes();
	$overrides = am_seo_get_overrides();

	// Existing custom-path overrides (saved on an earlier visit) that are not
	// one of the known static routes above — carry them into the blank rows
	// instead of losing them.
	$custom_existing = array();
	foreach ($overrides as $path => $entry) {
		if (!isset($routes[$path])) {
			$custom_existing[$path] = $entry;
		}
	}
	?>
	<div class="wrap am-seo-settings">
		<style>
			.am-seo-settings { max-width: 900px; }
			.am-seo-settings .am-seo-intro { max-width: 740px; color: #50575e; }
			.am-seo-settings .am-seo-intro strong { color: #1d2327; }
			.am-seo-card { background: #fff; border: 1px solid #dcdcde; border-radius: 8px; padding: 20px 24px; margin: 16px 0; }
			.am-seo-card-title { margin: 0 0 14px; font-size: 15px; }
			.am-seo-card-title code { font-weight: 400; color: #646970; margin-left: 6px; }
			.am-seo-field { display: block; margin-bottom: 14px; }
			.am-seo-field > span { display: block; font-weight: 600; margin-bottom: 4px; font-size: 13px; }
			.am-seo-field em { font-weight: 400; color: #787c82; }
			.am-seo-field input[type=text], .am-seo-field textarea { width: 100%; max-width: 640px; }
			.am-seo-count { display: block; font-size: 12px; color: #787c82; margin-top: 2px; }
			.am-seo-count.is-over { color: #b32d2e; }
			.am-seo-image-picker { display: flex; align-items: flex-start; gap: 14px; flex-wrap: wrap; }
			.am-seo-image-list { display: flex; flex-wrap: wrap; gap: 10px; min-height: 66px; }
			.am-seo-image-list:empty::before { content: 'Using default image'; display: flex; align-items: center; padding: 0 12px; color: #646970; border: 1px dashed #c3c4c7; border-radius: 4px; }
			.am-seo-image-item { position: relative; display: flex; flex-direction: column; gap: 3px; }
			.am-seo-image-thumb { width: 96px; height: 64px; object-fit: cover; border-radius: 4px; border: 1px solid #dcdcde; background: #f0f0f1; }
			.am-seo-image-buttons { display: flex; flex-direction: column; align-items: flex-start; gap: 6px; }
			.am-seo-remove-one-image, .am-seo-remove-all-images { color: #b32d2e; }
			.am-seo-image-count { color: #646970; font-size: 12px; font-weight: 400 !important; }
			.am-seo-previews { display: flex; gap: 20px; margin-top: 14px; flex-wrap: wrap; }
			.am-seo-preview-block { flex: 1 1 320px; min-width: 280px; }
			.am-seo-preview-label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #787c82; margin-bottom: 6px; }
			.am-seo-preview { padding: 12px 16px; background: #f6f7f7; border-radius: 6px; font-family: arial, sans-serif; }
			.am-seo-preview-url { font-size: 13px; color: #202124; }
			.am-seo-preview-title { font-size: 18px; line-height: 1.3; color: #1a0dab; margin-top: 2px; }
			.am-seo-preview-desc { font-size: 13px; line-height: 1.4; color: #4d5156; margin-top: 3px; }
			.am-seo-social-card { border: 1px solid #dadde1; border-radius: 8px; overflow: hidden; background: #fff; font-family: Helvetica, Arial, sans-serif; }
			.am-seo-social-image { display: block; width: 100%; height: 160px; object-fit: cover; background: #f0f0f1; }
			.am-seo-social-body { padding: 10px 12px; background: #f2f3f5; }
			.am-seo-social-domain { font-size: 11px; text-transform: uppercase; letter-spacing: .03em; color: #65676b; margin-bottom: 3px; }
			.am-seo-social-title { font-size: 15px; font-weight: 600; line-height: 1.3; color: #1c1e21; }
			.am-seo-social-desc { font-size: 13px; line-height: 1.35; color: #65676b; margin-top: 3px; }
			.am-seo-custom-note { color: #787c82; font-size: 13px; margin: 24px 0 8px; }
			.am-seo-path-input { font-family: monospace; }
		</style>

		<h1>Page SEO</h1>
		<p class="am-seo-intro">
			This controls the blue title and grey description Google shows for each page — nothing else on the page changes.
			Leave a field blank to keep the current default shown under it.
			<br><br>
			<strong>This does not control Google's extra "sitelinks" (the indented links Google sometimes adds under a big, well-known site) or the photo/reviews card for a business</strong> — Google generates sitelinks itself from site structure and traffic, and the reviews card comes from your separate, free Google Business Profile listing, not from this site.
		</p>

		<?php if (isset($_GET['am_seo_saved'])) : ?>
			<div class="notice notice-success is-dismissible"><p>Saved. Changes are live immediately.</p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<?php wp_nonce_field('am_seo_save_overrides'); ?>
			<input type="hidden" name="action" value="am_seo_save_overrides">

			<?php foreach ($routes as $path => $route) :
				$default_title       = am_seo_clean_text($route['title'] ?? '', 120);
				$default_description = am_seo_clean_text($route['description'] ?? '', 160);
				$default_image       = $route['image'] ?? am_seo_theme_image();
				$value_title         = isset($overrides[$path]['title']) ? $overrides[$path]['title'] : '';
				$value_description   = isset($overrides[$path]['description']) ? $overrides[$path]['description'] : '';
				$value_image_ids     = am_seo_override_image_ids($overrides[$path] ?? array());
				$value_image_urls    = am_seo_attachment_image_urls($value_image_ids, 'medium');
				am_seo_render_preview_row($path, $default_title, $default_description, $default_image, $value_title, $value_description, $value_image_ids, $value_image_urls, 'am_seo');
			endforeach; ?>

			<p class="am-seo-custom-note">
				Nursery pages, blog posts, events and job vacancies each already have their own SEO title/description box on their own edit screen (look for "SEO" near the bottom when editing one).
				Use the rows below only for a page not listed above — for example a brand-new page, or a one-off override.
			</p>

			<?php
			$custom_paths = array_keys($custom_existing);
			for ($i = 0; $i < AM_SEO_CUSTOM_ROWS; $i++) :
				$path            = $custom_paths[$i] ?? '';
				$entry           = '' !== $path ? $custom_existing[$path] : array();
				$value_image_ids  = am_seo_override_image_ids($entry);
				$value_image_urls = am_seo_attachment_image_urls($value_image_ids, 'medium');
				am_seo_render_preview_row(
					$path,
					'',
					'',
					am_seo_theme_image(),
					$entry['title'] ?? '',
					$entry['description'] ?? '',
					$value_image_ids,
					$value_image_urls,
					'am_seo_custom',
					true,
					$i
				);
			endfor;
			?>

			<?php submit_button('Save changes'); ?>
		</form>
	</div>
	<script>
	(function () {
		var form = document.querySelector('.am-seo-settings form');
		if (!form) { return; }

		function updateCard(card) {
			var titleInput = card.querySelector('[data-seo-role="title"]');
			var descInput  = card.querySelector('[data-seo-role="description"]');
			var titleCount = card.querySelector('[data-seo-count="title"]');
			var descCount  = card.querySelector('[data-seo-count="description"]');

			if (titleInput) {
				var t = titleInput.value.trim();
				card.querySelectorAll('.am-seo-preview-title, .am-seo-social-title').forEach(function (el) {
					el.textContent = t !== '' ? t : el.getAttribute('data-seo-default');
				});
				if (titleCount) {
					titleCount.textContent = titleInput.value.length + ' / 60 characters (ideal)';
					titleCount.classList.toggle('is-over', titleInput.value.length > 60);
				}
			}
			if (descInput) {
				var d = descInput.value.trim();
				card.querySelectorAll('.am-seo-preview-desc, .am-seo-social-desc').forEach(function (el) {
					el.textContent = d !== '' ? d : el.getAttribute('data-seo-default');
				});
				if (descCount) {
					descCount.textContent = descInput.value.length + ' / 155 characters (ideal)';
					descCount.classList.toggle('is-over', descInput.value.length > 155);
				}
			}
		}

		function updateImageState(card) {
			var list = card.querySelector('[data-seo-role="image-list"]');
			if (!list) { return; }
			var items = Array.from(list.querySelectorAll('.am-seo-image-item'));
			var ids = items.map(function (item) { return item.getAttribute('data-image-id'); }).filter(Boolean);
			var hidden = card.querySelector('[data-seo-role="image-ids"]');
			if (hidden) { hidden.value = ids.join(','); }
			var removeAll = card.querySelector('.am-seo-remove-all-images');
			if (removeAll) { removeAll.style.display = ids.length ? '' : 'none'; }
			var count = card.querySelector('[data-seo-role="image-count"]');
			if (count) { count.textContent = ids.length ? ids.length + (ids.length === 1 ? ' image' : ' images') : 'Default image'; }
			var first = items[0];
			var previewUrl = first ? first.getAttribute('data-image-url') : list.getAttribute('data-seo-default-image');
			card.querySelectorAll('[data-seo-role="social-image"]').forEach(function (img) { img.src = previewUrl || ''; });
		}

		function addImage(card, attachment) {
			var list = card.querySelector('[data-seo-role="image-list"]');
			if (!list || !attachment || !attachment.id || list.querySelector('[data-image-id="' + attachment.id + '"]')) { return; }
			var url = (attachment.sizes && attachment.sizes.medium) ? attachment.sizes.medium.url : attachment.url;
			var item = document.createElement('div');
			item.className = 'am-seo-image-item';
			item.setAttribute('data-image-id', attachment.id);
			item.setAttribute('data-image-url', url);
			var img = document.createElement('img');
			img.className = 'am-seo-image-thumb';
			img.src = url;
			img.alt = '';
			var remove = document.createElement('button');
			remove.type = 'button';
			remove.className = 'button-link-delete am-seo-remove-one-image';
			remove.setAttribute('aria-label', 'Remove this image');
			remove.textContent = 'Remove';
			item.appendChild(img);
			item.appendChild(remove);
			list.appendChild(item);
		}

		form.addEventListener('input', function (event) {
			var card = event.target.closest('.am-seo-card');
			if (card) { updateCard(card); }
		});

		form.addEventListener('click', function (event) {
			var card = event.target.closest('.am-seo-card');
			if (!card) { return; }

			if (event.target.classList.contains('am-seo-choose-image')) {
				event.preventDefault();
				if (typeof wp === 'undefined' || !wp.media) { return; }
				var frame = wp.media({ title: 'Add social images', button: { text: 'Add selected images' }, multiple: true });
				frame.on('select', function () {
					frame.state().get('selection').each(function (model) { addImage(card, model.toJSON()); });
					updateImageState(card);
				});
				frame.open();
			}

			if (event.target.classList.contains('am-seo-remove-one-image')) {
				event.preventDefault();
				event.target.closest('.am-seo-image-item').remove();
				updateImageState(card);
			}

			if (event.target.classList.contains('am-seo-remove-all-images')) {
				event.preventDefault();
				var list = card.querySelector('[data-seo-role="image-list"]');
				if (list) { list.replaceChildren(); }
				updateImageState(card);
			}
		});

		form.querySelectorAll('.am-seo-card').forEach(function (card) {
			updateCard(card);
			updateImageState(card);
		});
	})();
	</script>
	<?php
}
