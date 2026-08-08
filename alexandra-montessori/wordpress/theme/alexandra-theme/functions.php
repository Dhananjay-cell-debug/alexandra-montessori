<?php
/**
 * Alexandra Montessori — theme functions.
 *
 * Integrates the React/Vite SPA (built into /dist) into WordPress:
 *  - enqueues the hashed entry JS (as an ES module) + CSS read from .vite/manifest.json,
 *    so the correct files are used after every rebuild without hardcoding hashes;
 *  - loads the Poppins web font the design uses;
 *  - serves the SPA shell for every front-end URL with a 200 status so client-side
 *    routes (/about, /nurseries/hammersmith, …) work on refresh and deep-link.
 */

if (!defined('ABSPATH')) {
  exit;
}

function alexandra_montessori_theme_setup() {
  add_theme_support('title-tag');
  // Enables the Featured Image (thumbnail) upload box on Blog posts and Events
  // so staff can choose the exact image shown on the public site. Without this
  // the editor never offers a thumbnail field and the public cards fall back to
  // a generic image.
  add_theme_support('post-thumbnails');
  // A right-sized candidate for the circular Home image. Future JPEG uploads
  // are emitted as WebP below, so Visual Builder/CMS srcsets stay efficient.
  add_image_size('alexandra-home-portrait', 480, 640, false);
}
add_action('after_setup_theme', 'alexandra_montessori_theme_setup');

/** Generate modern responsive sub-sizes for future photographic uploads. */
function alexandra_montessori_image_output_formats($formats) {
  $formats['image/jpeg'] = 'image/webp';
  return $formats;
}
add_filter('image_editor_output_format', 'alexandra_montessori_image_output_formats');

/** A balanced quality level for responsive JPEG/WebP derivatives. */
function alexandra_montessori_image_quality($quality, $mime_type) {
  return in_array($mime_type, array('image/jpeg', 'image/webp'), true) ? 76 : $quality;
}
add_filter('wp_editor_set_quality', 'alexandra_montessori_image_quality', 10, 2);
function alexandra_montessori_jpeg_quality() {
  return 76;
}
add_filter('jpeg_quality', 'alexandra_montessori_jpeg_quality');

/**
 * Filesystem path / URL of the built SPA in the theme's /dist directory.
 */
function alexandra_dist_path() {
  return get_template_directory() . '/dist';
}
function alexandra_dist_uri() {
  return get_template_directory_uri() . '/dist';
}

/** Whether the current public request is the SPA home route. */
function alexandra_montessori_is_home_request() {
  $request_path = isset($_SERVER['REQUEST_URI'])
    ? (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH)
    : '/';
  $home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
  return trim($request_path, '/') === trim($home_path, '/');
}

/**
 * Resolve the saved Visual Builder hero poster before React starts. This lets
 * the browser discover the real LCP image in the initial HTML rather than
 * waiting for the JavaScript bundle to mount the Home page.
 *
 * @return array{src:string,mobile:string}
 */
function alexandra_montessori_hero_poster() {
  static $poster = null;
  if (is_array($poster)) return $poster;

  $default = alexandra_dist_uri() . '/assets/videos/alexandra-promo-poster.webp';
  $mobile_default = alexandra_dist_uri() . '/assets/videos/alexandra-promo-poster-768-3f8765e4.webp';
  $src = $default;
  $mobile = is_readable(alexandra_dist_path() . '/assets/videos/alexandra-promo-poster-768-3f8765e4.webp')
    ? $mobile_default
    : $default;
  $attachment_id = 0;

  if (class_exists('AM_VB_Home_Design')) {
    $design = AM_VB_Home_Design::get();
    $element = isset($design['elements']['hero-poster']) && is_array($design['elements']['hero-poster'])
      ? $design['elements']['hero-poster']
      : array();
    $attachment_id = isset($element['srcId']) ? absint($element['srcId']) : 0;
    $saved_src = isset($element['src']) ? trim((string) $element['src']) : '';
    if ($saved_src !== '') {
      $resolved = class_exists('AM_VB_Media')
        ? AM_VB_Media::resolve($saved_src, $attachment_id)
        : $saved_src;
      if ($resolved !== '') {
        $src = $resolved;
        $mobile = $resolved;
      }
    }
  }

  if ($attachment_id) {
    $responsive = wp_get_attachment_image_url($attachment_id, 'medium_large');
    if (is_string($responsive) && $responsive !== '') $mobile = $responsive;
  }

  $poster = array('src' => esc_url_raw($src), 'mobile' => esc_url_raw($mobile));
  return $poster;
}

/**
 * Read and decode dist/.vite/manifest.json once per request.
 * Returns an associative array, or an empty array if no build is present.
 */
function alexandra_vite_manifest() {
  static $manifest = null;
  if ($manifest !== null) {
    return $manifest;
  }
  $file = alexandra_dist_path() . '/.vite/manifest.json';
  if (!is_readable($file)) {
    $manifest = array();
    return $manifest;
  }
  $decoded = json_decode(file_get_contents($file), true);
  $manifest = is_array($decoded) ? $decoded : array();
  return $manifest;
}

/**
 * Enqueue the built SPA assets (CSS + hashed ES-module entry) plus the web font.
 */
function alexandra_montessori_enqueue_assets() {
  $manifest = alexandra_vite_manifest();
  if (empty($manifest['index.html'])) {
    return; // No build present — nothing more to enqueue.
  }
  $entry = $manifest['index.html'];
  $dist  = alexandra_dist_uri();

  // Bundled CSS (content-hashed → version null, no ?ver= cache-buster needed).
  if (!empty($entry['css'])) {
    foreach ($entry['css'] as $i => $css) {
      wp_enqueue_style(
        'alexandra-app' . ($i ? '-' . $i : ''),
        $dist . '/' . $css,
        array(),
        null
      );
    }
  }

  // Hashed entry JS, loaded in the footer as an ES module (see filter below).
  if (!empty($entry['file'])) {
    wp_enqueue_script(
      'alexandra-app',
      $dist . '/' . $entry['file'],
      array(),
      null,
      true
    );
  }
}
add_action('wp_enqueue_scripts', 'alexandra_montessori_enqueue_assets');

/**
 * The public site is a complete React SPA, so WordPress's block/editor styles
 * do not style any visible node. Remove those payloads and fold the Visual
 * Builder's small stylesheet into the already-required hashed app CSS.
 */
function alexandra_montessori_optimize_frontend_assets() {
  if (is_admin()) return;

  foreach (array(
    'wp-img-auto-sizes-contain',
    'wp-emoji-styles',
    'wp-block-library',
    'wp-block-library-theme',
    'classic-theme-styles',
    'global-styles',
  ) as $handle) {
    wp_dequeue_style($handle);
  }

  if (wp_style_is('am-vb-style-vars', 'enqueued') && wp_style_is('alexandra-app', 'enqueued')) {
    $style_file = defined('AM_VB_PLUGIN_DIR') ? AM_VB_PLUGIN_DIR . '/assets/style-vars.css' : '';
    if ($style_file && is_readable($style_file)) {
      wp_add_inline_style('alexandra-app', file_get_contents($style_file));
      wp_dequeue_style('am-vb-style-vars');
    }
  }
}
add_action('wp_enqueue_scripts', 'alexandra_montessori_optimize_frontend_assets', 100);

// The SPA does not render WordPress post content, embeds or emoji helpers.
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_enqueue_scripts', 'wp_common_block_scripts_and_styles');
remove_action('wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles');
remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
remove_action('wp_head', 'wp_enqueue_img_auto_sizes_contain_css_fix', 0);
remove_action('wp_head', 'wp_print_auto_sizes_contain_css_fix', 1);
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'wp_oembed_add_host_js');
remove_action('wp_head', 'wp_shortlink_wp_head', 10);
remove_action('wp_head', 'rest_output_link_wp_head', 10);
remove_action('wp_head', 'wp_generator');

/**
 * Vite output is ESM — mark the entry <script> as type="module" (+ crossorigin),
 * matching how Vite itself injects the tag.
 */
function alexandra_montessori_script_type($tag, $handle, $src) {
  if ($handle === 'alexandra-app') {
    return '<script type="module" crossorigin src="' . esc_url($src) . '"></script>' . "\n";
  }
  if (in_array($handle, array('am-vb-style-apply', 'am-vb-site-runtime'), true)) {
    $id = preg_quote($handle . '-js', '/');
    return preg_replace('/<script(?=[^>]*\bid=["\']' . $id . '["\'])/', '<script defer', $tag, 1);
  }
  return $tag;
}
add_filter('script_loader_tag', 'alexandra_montessori_script_type', 10, 3);

/**
 * Preconnect to the Google Fonts hosts and modulepreload the entry's static
 * import chunks, mirroring Vite's own <head> hints for faster startup.
 */
function alexandra_montessori_head_hints() {
  if (alexandra_montessori_is_home_request()) {
    $poster = alexandra_montessori_hero_poster();
    if ($poster['mobile'] !== $poster['src']) {
      echo '<link rel="preload" as="image" href="' . esc_url($poster['mobile']) . '" media="(max-width: 1023px)" fetchpriority="high">' . "\n";
      echo '<link rel="preload" as="image" href="' . esc_url($poster['src']) . '" media="(min-width: 1024px)" fetchpriority="high">' . "\n";
    } else {
      echo '<link rel="preload" as="image" href="' . esc_url($poster['src']) . '" fetchpriority="high">' . "\n";
    }
  }

  $manifest = alexandra_vite_manifest();
  if (empty($manifest['index.html']['imports'])) {
    return;
  }
  $dist = alexandra_dist_uri();
  foreach ($manifest['index.html']['imports'] as $key) {
    if (!empty($manifest[$key]['file'])) {
      echo '<link rel="modulepreload" crossorigin href="'
        . esc_url($dist . '/' . $manifest[$key]['file']) . '">' . "\n";
    }
  }
}
add_action('wp_head', 'alexandra_montessori_head_hints', 1);

/**
 * The validated GA4 Measurement ID from Site Settings, or '' if unset/invalid.
 * Shared by the <head> tag output and the window.amData contract so the SPA
 * page_view tracker and the gtag loader always agree.
 */
function am_ga4_id() {
  $opts = get_option('am_settings');
  $id = is_array($opts) && isset($opts['ga4_id']) ? trim((string) $opts['ga4_id']) : '';
  return preg_match('/^G-[A-Z0-9]+$/i', $id) ? $id : '';
}

/** Site Kit is the authoritative Analytics connection in production. */
function am_analytics_tag_id() {
  $site_kit = get_option('googlesitekit_analytics-4_settings');
  if (is_array($site_kit)) {
    foreach (array('measurementID', 'googleTagID') as $key) {
      $id = isset($site_kit[$key]) ? trim((string) $site_kit[$key]) : '';
      if (preg_match('/^(?:G|GT)-[A-Z0-9]+$/i', $id)) return $id;
    }
  }
  return am_ga4_id();
}

// Keep Site Kit reporting connected, but let the consent-aware React loader
// decide if/when the public Google tag is downloaded.
add_filter('googlesitekit_analytics-4_tag_blocked', '__return_true', PHP_INT_MAX);

/**
 * Output the Search Console verification meta tag and the GA4 gtag.js loader.
 * Both are driven by Site Settings, so nothing is emitted until the client
 * pastes their own codes. Automatic page_view is disabled (send_page_view:false)
 * because the React app fires page_view on every route change (see Analytics.jsx).
 */
function am_output_head_marketing_tags() {
  $opts = get_option('am_settings');
  if (!is_array($opts)) return;

  // Google Search Console — accept the full <meta> tag or just the code token.
  $gsc = isset($opts['gsc_verification']) ? trim((string) $opts['gsc_verification']) : '';
  if ($gsc !== '') {
    if (preg_match('/content=["\']([^"\']+)["\']/', $gsc, $m)) {
      $gsc = trim($m[1]);
    }
    if ($gsc !== '') {
      echo '<meta name="google-site-verification" content="' . esc_attr($gsc) . '">' . "\n";
    }
  }

}
add_action('wp_head', 'am_output_head_marketing_tags', 2);

/**
 * SPA routing: the theme has a single template (index.php), which WordPress also
 * uses as the 404 fallback, so every unmatched front-end URL already renders the
 * React shell. Normalise the HTTP status to 200 on those routes so deep links and
 * refreshes (e.g. /about, /nurseries/hammersmith) are not served as 404s.
 * Admin, REST (wp-json), wp-login and real files are never is_404(), so untouched.
 */
function alexandra_montessori_spa_status() {
  if (!is_admin() && is_404()) {
    status_header(200);
    nocache_headers();
  }
}
add_action('template_redirect', 'alexandra_montessori_spa_status');

function alexandra_montessori_preserve_blog_routes($redirect_url, $requested_url) {
  $path = untrailingslashit((string) wp_parse_url($requested_url, PHP_URL_PATH));
  if ($path === '/blogs' || str_starts_with($path, '/blogs/')) return false;
  return $redirect_url;
}
add_filter('redirect_canonical', 'alexandra_montessori_preserve_blog_routes', 10, 2);

function am_public_security_headers() {
  if (is_admin()) return;
  header_remove('X-Powered-By');
  header('X-Content-Type-Options: nosniff');
  header('X-Frame-Options: SAMEORIGIN');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
}
add_action('send_headers', 'am_public_security_headers');

/**
 * Keep the WP admin bar from overlapping the SPA's fixed navbar on the front end.
 * (Reversible — remove this line to restore the admin bar for logged-in users.)
 */
add_filter('show_admin_bar', '__return_false');

// ===================================================================
// Phase 1 CMS — Custom Post Types: Events + Testimonials
// ===================================================================

function am_register_cpts() {
  register_post_type('am_event', [
    'labels' => [
      'name'          => 'Events',
      'singular_name' => 'Event',
      'add_new_item'  => 'Add New Event',
      'edit_item'     => 'Edit Event',
    ],
    'public'       => false,
    'show_ui'      => true,
    'show_in_menu' => AM_CONTENT_MENU_SLUG,
    'supports'     => ['title', 'editor', 'excerpt', 'thumbnail'],
    'menu_icon'    => 'dashicons-calendar-alt',
    'rewrite'      => false,
  ]);

  register_post_type('am_testimonial', [
    'labels' => [
      'name'          => 'Testimonials',
      'singular_name' => 'Testimonial',
      'add_new_item'  => 'Add New Testimonial',
      'edit_item'     => 'Edit Testimonial',
    ],
    'public'       => false,
    'show_ui'      => true,
    'show_in_menu' => AM_CONTENT_MENU_SLUG,
    'supports'     => ['title', 'page-attributes'],
    'menu_icon'    => 'dashicons-format-quote',
    'rewrite'      => false,
  ]);

  register_post_type('am_nursery', [
    'labels' => [
      'name'          => 'Nurseries',
      'singular_name' => 'Nursery',
      'add_new_item'  => 'Add New Nursery',
      'edit_item'     => 'Edit Nursery',
    ],
    'public'       => false,
    'show_ui'      => true,
    'show_in_menu' => AM_CONTENT_MENU_SLUG,
    'supports'     => ['title', 'page-attributes'],
    'menu_icon'    => 'dashicons-location',
    'rewrite'      => false,
  ]);

  register_post_type('am_job', [
    'labels' => [
      'name'          => 'Jobs',
      'singular_name' => 'Job',
      'add_new_item'  => 'Add New Job',
      'edit_item'     => 'Edit Job',
    ],
    'public'       => false,
    'show_ui'      => true,
    'show_in_menu' => AM_CONTENT_MENU_SLUG,
    'supports'     => ['title', 'page-attributes'],
    'menu_icon'    => 'dashicons-id-alt',
    'rewrite'      => false,
  ]);
}
add_action('init', 'am_register_cpts');

// ----------------------------------------------------------------
// Meta boxes — Events (date / time / location)
// ----------------------------------------------------------------

function am_event_meta_boxes() {
  add_meta_box('am_event_details', 'Event Details', 'am_event_meta_callback', 'am_event', 'normal', 'high');
}
add_action('add_meta_boxes', 'am_event_meta_boxes');

function am_event_meta_callback($post) {
  wp_nonce_field('am_event_save', 'am_event_nonce');
  $date     = get_post_meta($post->ID, '_am_event_date', true);
  $time     = get_post_meta($post->ID, '_am_event_time', true);
  $location = get_post_meta($post->ID, '_am_event_location', true);
  $booking  = get_post_meta($post->ID, '_am_event_booking_info', true);
  echo '<p><label><strong>Date (YYYY-MM-DD)</strong><br>';
  echo '<input type="date" name="am_event_date" value="' . esc_attr($date) . '" style="width:100%;margin-top:4px;padding:4px;" /></label></p>';
  echo '<p><label><strong>Time</strong><br>';
  echo '<input type="text" name="am_event_time" value="' . esc_attr($time) . '" placeholder="e.g. 9:30am – 11:30am" style="width:100%;margin-top:4px;padding:4px;" /></label></p>';
  echo '<p><label><strong>Location</strong><br>';
  echo '<input type="text" name="am_event_location" value="' . esc_attr($location) . '" placeholder="e.g. Hammersmith · Ravenscourt" style="width:100%;margin-top:4px;padding:4px;" /></label></p>';
  echo '<p><label><strong>Booking information</strong><br>';
  echo '<textarea name="am_event_booking_info" rows="3" placeholder="How to book, cost, what to bring…" style="width:100%;margin-top:4px;padding:4px;">' . esc_textarea($booking) . '</textarea></label></p>';
  echo '<p style="color:#666;font-size:12px;margin-top:6px;">The main editor above is the full event description shown on the event details page. The Excerpt is the short card preview.</p>';
}

function am_event_save_meta($post_id) {
  if (!isset($_POST['am_event_nonce']) || !wp_verify_nonce($_POST['am_event_nonce'], 'am_event_save')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;
  if (isset($_POST['am_event_date']))         update_post_meta($post_id, '_am_event_date',         sanitize_text_field($_POST['am_event_date']));
  if (isset($_POST['am_event_time']))         update_post_meta($post_id, '_am_event_time',         sanitize_text_field($_POST['am_event_time']));
  if (isset($_POST['am_event_location']))     update_post_meta($post_id, '_am_event_location',     sanitize_text_field($_POST['am_event_location']));
  if (isset($_POST['am_event_booking_info'])) update_post_meta($post_id, '_am_event_booking_info', sanitize_textarea_field($_POST['am_event_booking_info']));
}
add_action('save_post_am_event', 'am_event_save_meta');

// ----------------------------------------------------------------
// Meta boxes — Testimonials (quote / parent name / location)
// ----------------------------------------------------------------

function am_testimonial_meta_boxes() {
  add_meta_box('am_testimonial_details', 'Testimonial Details', 'am_testimonial_meta_callback', 'am_testimonial', 'normal', 'high');
}
add_action('add_meta_boxes', 'am_testimonial_meta_boxes');

function am_testimonial_meta_callback($post) {
  wp_nonce_field('am_testimonial_save', 'am_testimonial_nonce');
  $quote    = get_post_meta($post->ID, '_am_testimonial_quote', true);
  $name     = get_post_meta($post->ID, '_am_testimonial_name', true);
  $location = get_post_meta($post->ID, '_am_testimonial_location', true);
  echo '<p><label><strong>Quote</strong><br>';
  echo '<textarea name="am_testimonial_quote" rows="4" style="width:100%;margin-top:4px;padding:4px;">' . esc_textarea($quote) . '</textarea></label></p>';
  echo '<p><label><strong>Parent name / descriptor</strong><br>';
  echo '<input type="text" name="am_testimonial_name" value="' . esc_attr($name) . '" placeholder="e.g. Parent of a 3-year-old" style="width:100%;margin-top:4px;padding:4px;" /></label></p>';
  echo '<p><label><strong>Location</strong><br>';
  echo '<input type="text" name="am_testimonial_location" value="' . esc_attr($location) . '" placeholder="e.g. Hammersmith" style="width:100%;margin-top:4px;padding:4px;" /></label></p>';
}

function am_testimonial_save_meta($post_id) {
  if (!isset($_POST['am_testimonial_nonce']) || !wp_verify_nonce($_POST['am_testimonial_nonce'], 'am_testimonial_save')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;
  if (isset($_POST['am_testimonial_quote']))    update_post_meta($post_id, '_am_testimonial_quote',    sanitize_textarea_field($_POST['am_testimonial_quote']));
  if (isset($_POST['am_testimonial_name']))     update_post_meta($post_id, '_am_testimonial_name',     sanitize_text_field($_POST['am_testimonial_name']));
  if (isset($_POST['am_testimonial_location'])) update_post_meta($post_id, '_am_testimonial_location', sanitize_text_field($_POST['am_testimonial_location']));
}
add_action('save_post_am_testimonial', 'am_testimonial_save_meta');

// ===================================================================
// Nurseries CMS — CMB2 meta box for am_nursery CPT
// ===================================================================

function am_register_nursery_fields() {
  if (!function_exists('new_cmb2_box')) return;

  $cmb = new_cmb2_box([
    'id'           => 'am_nursery_details',
    'title'        => 'Nursery Details',
    'object_types' => ['am_nursery'],
    'context'      => 'normal',
    'priority'     => 'high',
  ]);

  $cmb->add_field(['name' => 'Area',              'id' => '_am_nursery_area',          'type' => 'text']);
  $cmb->add_field(['name' => 'Hero Tagline',       'id' => '_am_nursery_hero_tagline',  'type' => 'text']);
  $cmb->add_field(['name' => 'Subheading',         'id' => '_am_nursery_subheading',    'type' => 'text']);
  $cmb->add_field(['name' => 'Address',            'id' => '_am_nursery_address',       'type' => 'text']);
  $cmb->add_field(['name' => 'Postcode',           'id' => '_am_nursery_postcode',      'type' => 'text']);
  $cmb->add_field(['name' => 'Phone',              'id' => '_am_nursery_phone',         'type' => 'text']);
  $cmb->add_field(['name' => 'Email',              'id' => '_am_nursery_email',         'type' => 'text_email']);
  $cmb->add_field(['name' => 'Hours',              'id' => '_am_nursery_hours',         'type' => 'text', 'attributes' => ['placeholder' => 'Mon – Fri · 8am – 6pm']]);
  $cmb->add_field(['name' => 'Age range',          'id' => '_am_nursery_age_range',     'type' => 'text', 'attributes' => ['placeholder' => '6 months to 5 years'], 'desc' => 'Admission age range for this branch (e.g. Hammersmith differs from the others).']);
  $cmb->add_field(['name' => 'Short description',  'id' => '_am_nursery_short',         'type' => 'textarea_small']);
  $cmb->add_field(['name' => 'Welcome text',       'id' => '_am_nursery_welcome',       'type' => 'textarea']);
  $cmb->add_field(['name' => 'Hero image',         'id' => '_am_nursery_hero_image',    'type' => 'file', 'options' => ['url' => false]]);
  $cmb->add_field(['name' => 'Welcome image',      'id' => '_am_nursery_welcome_image', 'type' => 'file', 'options' => ['url' => false]]);
  $cmb->add_field(['name' => 'Gallery',            'id' => '_am_nursery_gallery',       'type' => 'file_list']);
  $cmb->add_field(['name' => 'Fee sheet PDF',      'id' => '_am_nursery_fee_sheet_pdf', 'type' => 'file', 'options' => ['url' => false], 'query_args' => ['type' => 'application/pdf']]);

  // --- Ofsted (shown site-wide + footer; client-requested) ---
  $cmb->add_field([
    'name'    => 'Ofsted rating',
    'id'      => '_am_nursery_ofsted_rating',
    'type'    => 'select',
    'options' => [
      'Outstanding'          => 'Outstanding',
      'Good'                 => 'Good',
      'Requires improvement' => 'Requires improvement',
      'Inadequate'           => 'Inadequate',
      'Pending'              => 'Pending — not yet inspected',
    ],
    'default' => 'Good',
  ]);
  $cmb->add_field(['name' => 'Ofsted report URL',   'id' => '_am_nursery_ofsted_url',   'type' => 'text_url']);
  $cmb->add_field(['name' => 'Ofsted link label',   'id' => '_am_nursery_ofsted_label', 'type' => 'text', 'attributes' => ['placeholder' => 'Official Ofsted report']]);

  // --- Food Hygiene Rating (FSA; drives /food-hygiene-rating) ---
  $cmb->add_field(['name' => 'Food hygiene rating',      'id' => '_am_nursery_hygiene_rating',    'type' => 'text', 'attributes' => ['placeholder' => '5'], 'desc' => 'FSA rating 0–5, or a note if awaiting listing.']);
  $cmb->add_field(['name' => 'Food hygiene rating date', 'id' => '_am_nursery_hygiene_date',      'type' => 'text', 'attributes' => ['placeholder' => '20 January 2025']]);
  $cmb->add_field(['name' => 'Food hygiene authority',   'id' => '_am_nursery_hygiene_authority', 'type' => 'text', 'attributes' => ['placeholder' => 'London Borough of Hounslow']]);
  $cmb->add_field(['name' => 'Food hygiene FSA URL',     'id' => '_am_nursery_hygiene_url',       'type' => 'text_url']);
}
add_action('cmb2_admin_init', 'am_register_nursery_fields');

// ===================================================================
// Jobs / Careers CMS — CMB2 meta box for am_job CPT
// ===================================================================

function am_nursery_location_options() {
  $options = [];
  if (function_exists('am_get_nurseries')) {
    foreach (am_get_nurseries() as $nursery) {
      $name = sanitize_text_field((string) ($nursery['name'] ?? ''));
      if ($name !== '') $options[$name] = $name;
    }
  }
  $options['All Nurseries'] = 'All Nurseries';
  return $options;
}

function am_register_job_fields() {
  if (!function_exists('new_cmb2_box')) return;

  $cmb = new_cmb2_box([
    'id'           => 'am_job_details',
    'title'        => 'Job Details',
    'object_types' => ['am_job'],
    'context'      => 'normal',
    'priority'     => 'high',
  ]);

  $cmb->add_field([
    'name'    => 'Status',
    'id'      => '_am_job_status',
    'type'    => 'select',
    'options' => [
      'open'   => 'Open — visible on site',
      'closed' => 'Closed — hidden from site',
    ],
    'default' => 'open',
  ]);
  $cmb->add_field([
    'name'       => 'Location / Nursery',
    'id'         => '_am_job_location',
    'type'       => 'select',
    'options_cb' => 'am_nursery_location_options',
  ]);
  $cmb->add_field([
    'name'    => 'Job type',
    'id'      => '_am_job_type',
    'type'    => 'select',
    'options' => [
      'Full-time' => 'Full-time',
      'Part-time' => 'Part-time',
      'Sessional' => 'Sessional',
      'Volunteer' => 'Volunteer',
    ],
  ]);
  $cmb->add_field([
    'name'       => 'Hours',
    'id'         => '_am_job_hours',
    'type'       => 'text',
    'attributes' => ['placeholder' => 'e.g. 30 hrs/week'],
  ]);
  $cmb->add_field([
    'name'       => 'Salary',
    'id'         => '_am_job_salary',
    'type'       => 'text',
    'attributes' => ['placeholder' => 'e.g. £24,000 – £28,000 pa'],
  ]);
  $cmb->add_field([
    'name' => 'Job summary',
    'id'   => '_am_job_short',
    'type' => 'textarea_small',
    'desc' => 'Short preview shown on the vacancy card and at the top of the details page.',
  ]);
  $cmb->add_field([
    'name'    => 'Full description',
    'id'      => '_am_job_full',
    'type'    => 'wysiwyg',
    'options' => ['textarea_rows' => 12],
    'desc'    => 'The complete role details — add headed sections for Responsibilities, Required skills, Qualifications and Experience.',
  ]);
  $cmb->add_field([
    'name' => 'Benefits',
    'id'   => '_am_job_benefits',
    'type' => 'textarea_small',
    'desc' => 'One benefit per line — shown as a list on the details page.',
  ]);
  $cmb->add_field([
    'name' => 'Apply URL',
    'id'   => '_am_job_apply_url',
    'type' => 'text_url',
  ]);
  $cmb->add_field([
    'name' => 'Apply email',
    'id'   => '_am_job_apply_email',
    'type' => 'text_email',
  ]);
}
add_action('cmb2_admin_init', 'am_register_job_fields');

// Blog display control: one staff-selected main article.
function am_register_blog_display_fields() {
  if (!function_exists('new_cmb2_box')) return;

  $cmb = new_cmb2_box([
    'id'           => 'am_blog_display',
    'title'        => 'Website placement',
    'object_types' => ['post'],
    'context'      => 'side',
    'priority'     => 'high',
  ]);
  $cmb->add_field([
    'name' => 'Show as the main Blog article',
    'id'   => '_am_blog_featured',
    'type' => 'checkbox',
    'desc' => 'The chosen published article appears large. Every other ready article stays in the three-column archive.',
  ]);
}
add_action('cmb2_admin_init', 'am_register_blog_display_fields');

function am_blog_is_featured_choice($post_id) {
  return !empty(get_post_meta($post_id, '_am_blog_featured', true));
}

function am_enforce_single_featured_blog($post_id) {
  if (get_post_type($post_id) !== 'post'
    || get_post_status($post_id) !== 'publish'
    || !am_blog_is_featured_choice($post_id)
    || (function_exists('am_blog_is_frontend_ready') && !am_blog_is_frontend_ready($post_id))) {
    return;
  }

  $other_choices = get_posts([
    'post_type'      => 'post',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'post__not_in'   => [(int) $post_id],
    'meta_key'       => '_am_blog_featured',
    'meta_compare'   => 'EXISTS',
    'fields'         => 'ids',
  ]);
  foreach ($other_choices as $other_id) {
    delete_post_meta($other_id, '_am_blog_featured');
  }
}

function am_blog_featured_meta_changed($meta_id, $post_id, $meta_key, $meta_value) {
  if ($meta_key === '_am_blog_featured' && !empty($meta_value)) {
    am_enforce_single_featured_blog((int) $post_id);
  }
}
add_action('added_post_meta', 'am_blog_featured_meta_changed', 10, 4);
add_action('updated_post_meta', 'am_blog_featured_meta_changed', 10, 4);

function am_enforce_featured_blog_after_save($post_id) {
  if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
  am_enforce_single_featured_blog((int) $post_id);
}
add_action('save_post_post', 'am_enforce_featured_blog_after_save', 100);

// ----------------------------------------------------------------
// Data getters — shape matches src/data/site.js exports exactly
// ----------------------------------------------------------------

function am_get_events() {
  $posts = get_posts([
    'post_type'      => 'am_event',
    'post_status'    => 'publish',
    'posts_per_page' => 100,
    'orderby'        => 'meta_value',
    'meta_key'       => '_am_event_date',
    'order'          => 'ASC',
  ]);
  $result = [];
  foreach ($posts as $p) {
    if (function_exists('am_event_is_frontend_ready') && !am_event_is_frontend_ready($p->ID)) continue;
    $content = trim($p->post_content);
    $result[] = [
      'id'          => $p->post_name,
      'title'       => am_frontend_title($p),
      'date'        => get_post_meta($p->ID, '_am_event_date', true)     ?: '',
      'time'        => get_post_meta($p->ID, '_am_event_time', true)     ?: '',
      'location'    => get_post_meta($p->ID, '_am_event_location', true) ?: '',
      'image'       => get_the_post_thumbnail_url($p->ID, 'full')        ?: '',
      'excerpt'     => get_the_excerpt($p),
      // Full description = the main editor body (rendered, sanitised HTML).
      'description' => $content ? wp_kses_post(wpautop($content)) : '',
      'bookingInfo' => get_post_meta($p->ID, '_am_event_booking_info', true) ?: '',
    ];
  }
  return $result;
}

function am_ready_blog_posts() {
  static $ready_posts = null;
  if ($ready_posts !== null) return $ready_posts;

  $posts = get_posts([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
  ]);
  $ready_posts = array_values(array_filter($posts, function ($post) {
    return !function_exists('am_blog_is_frontend_ready') || am_blog_is_frontend_ready($post->ID);
  }));
  return $ready_posts;
}

function am_featured_blog_post_id($ready_posts = null) {
  $ready_posts = is_array($ready_posts) ? $ready_posts : am_ready_blog_posts();
  foreach ($ready_posts as $post) {
    if (am_blog_is_featured_choice($post->ID)) return (int) $post->ID;
  }
  return $ready_posts ? (int) $ready_posts[0]->ID : 0;
}

function am_prepare_blog_card($post) {
  $cats = get_the_category($post->ID);
  $category = !empty($cats) && $cats[0]->slug !== 'uncategorized'
    ? $cats[0]->name
    : 'Resources';
  $fallback_image = get_template_directory_uri() . '/dist/assets/organisation/classroom-main.webp';
  return [
    'slug'     => $post->post_name,
    'title'    => am_frontend_title($post),
    'date'     => get_the_date('Y-m-d', $post),
    'category' => $category,
    'image'    => get_the_post_thumbnail_url($post->ID, 'full') ?: $fallback_image,
    'excerpt'  => get_the_excerpt($post),
  ];
}

function am_prepare_blog_article($post) {
  return array_merge(am_prepare_blog_card($post), [
    'content' => wp_kses_post(apply_filters('the_content', $post->post_content)),
  ]);
}

function am_get_blog_archive($year = 0, $month = 0, $page = 1, $per_page = 9) {
  $all = am_ready_blog_posts();
  $featured = null;
  $featured_id = am_featured_blog_post_id($all);
  foreach ($all as $index => $post) {
    if ((int) $post->ID !== $featured_id) continue;
    $featured = $post;
    unset($all[$index]);
    $all = array_values($all);
    break;
  }
  $facets = [];

  foreach ($all as $post) {
    $post_year = (int) get_the_date('Y', $post);
    $post_month = (int) get_the_date('n', $post);
    if (!isset($facets[$post_year])) $facets[$post_year] = [];
    $facets[$post_year][$post_month] = true;
  }
  krsort($facets, SORT_NUMERIC);
  $filters = [];
  foreach ($facets as $facet_year => $months) {
    $month_values = array_map('intval', array_keys($months));
    rsort($month_values, SORT_NUMERIC);
    $filters[] = ['year' => (int) $facet_year, 'months' => $month_values];
  }

  $filtered = array_values(array_filter($all, function ($post) use ($year, $month) {
    if ($year && (int) get_the_date('Y', $post) !== $year) return false;
    if ($month && (int) get_the_date('n', $post) !== $month) return false;
    return true;
  }));

  $per_page = max(1, min(9, (int) $per_page));
  $total = count($filtered);
  $total_pages = max(1, (int) ceil($total / $per_page));
  $page = max(1, min((int) $page, $total_pages));
  $items = array_slice($filtered, ($page - 1) * $per_page, $per_page);

  return [
    'featured' => $featured ? am_prepare_blog_card($featured) : null,
    'items' => array_map('am_prepare_blog_card', $items),
    'total' => $total,
    'page' => $page,
    'perPage' => $per_page,
    'totalPages' => $total_pages,
    'filters' => $filters,
  ];
}

// Retained for backward compatibility with an older cached frontend bundle.
function am_get_blogs() {
  return array_map('am_prepare_blog_article', array_slice(am_ready_blog_posts(), 0, 12));
}

function am_blog_archive_rest($request) {
  $year = (int) $request->get_param('year');
  $month = (int) $request->get_param('month');
  if ($year && ($year < 2000 || $year > 2100)) {
    return new WP_Error('invalid_year', 'Choose a valid archive year.', ['status' => 400]);
  }
  if ($month && ($month < 1 || $month > 12)) {
    return new WP_Error('invalid_month', 'Choose a valid archive month.', ['status' => 400]);
  }
  if ($month && !$year) {
    return new WP_Error('month_requires_year', 'Choose a year before choosing a month.', ['status' => 400]);
  }

  return rest_ensure_response(am_get_blog_archive(
    $year,
    $month,
    max(1, (int) $request->get_param('page')),
    9
  ));
}

function am_blog_article_rest($request) {
  $slug = sanitize_title((string) $request->get_param('slug'));
  $post = get_page_by_path($slug, OBJECT, 'post');
  if (!$post || $post->post_status !== 'publish' || !am_blog_is_frontend_ready($post->ID)) {
    return new WP_Error('article_not_found', 'Article not found.', ['status' => 404]);
  }

  $related = [];
  foreach (am_ready_blog_posts() as $candidate) {
    if ($candidate->ID === $post->ID) continue;
    $related[] = am_prepare_blog_card($candidate);
    if (count($related) === 3) break;
  }

  return rest_ensure_response([
    'post' => am_prepare_blog_article($post),
    'related' => $related,
  ]);
}

function am_register_blog_rest_routes() {
  register_rest_route('alexandra/v1', '/blogs', [
    'methods' => WP_REST_Server::READABLE,
    'callback' => 'am_blog_archive_rest',
    'permission_callback' => '__return_true',
  ]);
  register_rest_route('alexandra/v1', '/blogs/(?P<slug>[a-z0-9-]+)', [
    'methods' => WP_REST_Server::READABLE,
    'callback' => 'am_blog_article_rest',
    'permission_callback' => '__return_true',
  ]);
}
add_action('rest_api_init', 'am_register_blog_rest_routes');

function am_prepare_testimonial($post) {
  return [
    'id'       => $post->post_name,
    'title'    => am_frontend_title($post),
    'quote'    => get_post_meta($post->ID, '_am_testimonial_quote', true)    ?: '',
    'name'     => get_post_meta($post->ID, '_am_testimonial_name', true)     ?: '',
    'location' => get_post_meta($post->ID, '_am_testimonial_location', true) ?: '',
  ];
}

function am_testimonial_query($page = 1, $per_page = 9, $location = '') {
  global $wpdb;

  $page = max(1, (int) $page);
  $per_page = min(24, max(1, (int) $per_page));
  $location = sanitize_text_field((string) $location);
  $meta_query = [
    'relation' => 'AND',
    [
      'key'     => '_am_testimonial_quote',
      'value'   => '',
      'compare' => '!=',
    ],
    [
      'key'     => '_am_testimonial_name',
      'value'   => '',
      'compare' => '!=',
    ],
  ];
  if ($location !== '') {
    $meta_query[] = [
      'key'     => '_am_testimonial_location',
      'value'   => [$location, 'All Nurseries', 'General'],
      'compare' => 'IN',
    ];
  } else {
    $meta_query[] = [
      'key'     => '_am_testimonial_location',
      'value'   => '',
      'compare' => '!=',
    ];
  }

  $title_where = static function ($where) use ($wpdb) {
    return $where . " AND {$wpdb->posts}.post_title <> ''";
  };
  add_filter('posts_where', $title_where);
  $query_args = [
    'post_type'           => 'am_testimonial',
    'post_status'         => 'publish',
    'posts_per_page'      => $per_page,
    'paged'               => $page,
    'orderby'             => ['menu_order' => 'ASC', 'date' => 'DESC', 'ID' => 'ASC'],
    'meta_query'          => $meta_query,
    'ignore_sticky_posts' => true,
  ];
  $query = new WP_Query($query_args);
  $resolved_page = $page;
  if ($page > 1 && !$query->posts) {
    // Some WordPress/MySQL combinations do not populate max_num_pages when a
    // requested page is far beyond the result set. Query page one once to
    // establish the real final page, then resolve to that populated page.
    $query_args['paged'] = 1;
    $page_one_query = new WP_Query($query_args);
    $resolved_page = max(1, (int) $page_one_query->max_num_pages);
    if ($resolved_page > 1) {
      $query_args['paged'] = $resolved_page;
      $query = new WP_Query($query_args);
    } else {
      $query = $page_one_query;
    }
  } else {
    $resolved_page = min($page, max(1, (int) $query->max_num_pages));
    if ($resolved_page !== $page) {
      $query_args['paged'] = $resolved_page;
      $query = new WP_Query($query_args);
    }
  }
  remove_filter('posts_where', $title_where);

  return [
    'items'   => array_map('am_prepare_testimonial', $query->posts),
    'page'    => $resolved_page,
    'pages'   => max(1, (int) $query->max_num_pages),
    'total'   => (int) $query->found_posts,
    'perPage' => $per_page,
  ];
}

function am_get_testimonials() {
  return am_testimonial_query(1, 3)['items'];
}

function am_testimonials_rest($request) {
  return rest_ensure_response(am_testimonial_query(
    $request->get_param('page'),
    $request->get_param('per_page'),
    $request->get_param('location')
  ));
}

function am_register_testimonial_rest_route() {
  register_rest_route('alexandra/v1', '/testimonials', [
    'methods'             => WP_REST_Server::READABLE,
    'callback'            => 'am_testimonials_rest',
    'permission_callback' => '__return_true',
    'args'                => [
      'page' => [
        'default'           => 1,
        'sanitize_callback' => 'absint',
        'validate_callback' => static fn($value) => (int) $value >= 1,
      ],
      'per_page' => [
        'default'           => 9,
        'sanitize_callback' => 'absint',
        'validate_callback' => static fn($value) => (int) $value >= 1 && (int) $value <= 24,
      ],
      'location' => [
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
      ],
    ],
  ]);
}
add_action('rest_api_init', 'am_register_testimonial_rest_route');

/**
 * Returns nursery records in the exact shape of the `locations` array in
 * src/data/site.js. `features` is intentionally omitted — it is a shared
 * static icon list that lives in code and must not be overwritten by CMS data.
 * The React merge step will re-attach features by slug.
 */
function am_get_nurseries() {
  $posts = get_posts([
    'post_type'      => 'am_nursery',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC', 'ID' => 'ASC'],
  ]);
  $result = [];
  foreach ($posts as $p) {
    if (function_exists('am_nursery_is_frontend_ready') && !am_nursery_is_frontend_ready($p->ID)) continue;
    // file_list stores [ attachment_id => url ]; extract URLs only.
    $gallery_raw = get_post_meta($p->ID, '_am_nursery_gallery', true);
    $gallery = (is_array($gallery_raw) && !empty($gallery_raw)) ? array_values($gallery_raw) : [];

    $result[] = [
      'id'           => $p->post_name,
      'name'         => am_frontend_title($p),
      'area'         => get_post_meta($p->ID, '_am_nursery_area',          true) ?: '',
      'heroTagline'  => get_post_meta($p->ID, '_am_nursery_hero_tagline',  true) ?: '',
      'subheading'   => get_post_meta($p->ID, '_am_nursery_subheading',    true) ?: '',
      'address'      => get_post_meta($p->ID, '_am_nursery_address',       true) ?: '',
      'postcode'     => get_post_meta($p->ID, '_am_nursery_postcode',      true) ?: '',
      'phone'        => get_post_meta($p->ID, '_am_nursery_phone',         true) ?: '',
      'email'        => get_post_meta($p->ID, '_am_nursery_email',         true) ?: '',
      'hours'        => get_post_meta($p->ID, '_am_nursery_hours',         true) ?: '',
      'ageRange'     => get_post_meta($p->ID, '_am_nursery_age_range',     true) ?: '',
      'image'        => get_post_meta($p->ID, '_am_nursery_hero_image',    true) ?: '',
      'welcomeImage' => get_post_meta($p->ID, '_am_nursery_welcome_image', true) ?: '',
      'gallery'      => $gallery,
      'short'        => get_post_meta($p->ID, '_am_nursery_short',         true) ?: '',
      'welcome'      => get_post_meta($p->ID, '_am_nursery_welcome',       true) ?: '',
      'feeSheetPdf'  => get_post_meta($p->ID, '_am_nursery_fee_sheet_pdf', true) ?: '',
      // Ofsted (client-requested; shown site-wide + footer)
      'ofstedRating'     => get_post_meta($p->ID, '_am_nursery_ofsted_rating',    true) ?: '',
      'ofstedUrl'        => get_post_meta($p->ID, '_am_nursery_ofsted_url',       true) ?: '',
      'ofstedLabel'      => get_post_meta($p->ID, '_am_nursery_ofsted_label',     true) ?: '',
      // Food Hygiene Rating (FSA; drives /food-hygiene-rating)
      'hygieneRating'    => get_post_meta($p->ID, '_am_nursery_hygiene_rating',    true) ?: '',
      'hygieneDate'      => get_post_meta($p->ID, '_am_nursery_hygiene_date',      true) ?: '',
      'hygieneAuthority' => get_post_meta($p->ID, '_am_nursery_hygiene_authority', true) ?: '',
      'hygieneUrl'       => get_post_meta($p->ID, '_am_nursery_hygiene_url',       true) ?: '',
    ];
  }
  return $result;
}

/**
 * Returns open job records for window.amData.jobs.
 * Closed jobs remain editable in WP Admin but are excluded from the frontend.
 * Ordered by menu_order ASC (manual drag-and-drop), then date DESC.
 * fullDescription is sanitized with wp_kses_post to preserve safe WYSIWYG HTML.
 */
function am_get_jobs() {
  $posts = get_posts([
    'post_type'      => 'am_job',
    'post_status'    => 'publish',
    'posts_per_page' => 50,
    'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
    'meta_query'     => [
      'relation' => 'OR',
      [
        'key'     => '_am_job_status',
        'value'   => 'open',
        'compare' => '=',
      ],
      // Include posts where status meta was never saved (defaults to open intent).
      [
        'key'     => '_am_job_status',
        'compare' => 'NOT EXISTS',
      ],
    ],
  ]);
  $result = [];
  foreach ($posts as $p) {
    if (function_exists('am_job_is_frontend_ready') && !am_job_is_frontend_ready($p->ID)) continue;
    $result[] = [
      'id'               => $p->post_name,
      'title'            => am_frontend_title($p),
      'location'         => get_post_meta($p->ID, '_am_job_location',    true) ?: '',
      'jobType'          => get_post_meta($p->ID, '_am_job_type',        true) ?: '',
      'hours'            => get_post_meta($p->ID, '_am_job_hours',       true) ?: '',
      'salary'           => get_post_meta($p->ID, '_am_job_salary',      true) ?: '',
      'shortDescription' => get_post_meta($p->ID, '_am_job_short',       true) ?: '',
      'summary'          => get_post_meta($p->ID, '_am_job_short',       true) ?: '',
      'fullDescription'  => wp_kses_post(get_post_meta($p->ID, '_am_job_full', true) ?: ''),
      'benefits'         => get_post_meta($p->ID, '_am_job_benefits',    true) ?: '',
      'applyUrl'         => get_post_meta($p->ID, '_am_job_apply_url',   true) ?: '',
      'applyEmail'       => get_post_meta($p->ID, '_am_job_apply_email', true) ?: '',
      'status'           => 'open',
    ];
  }
  return $result;
}

// ----------------------------------------------------------------
// Inject CMS data into the React app via window.amData
// Priority 20 ensures 'alexandra-app' is already registered (priority 10).
// ----------------------------------------------------------------

function am_localize_cms_data() {
  if (!wp_script_is('alexandra-app', 'enqueued')) return;
  $testimonial_preview = am_testimonial_query(1, 3);
  wp_localize_script('alexandra-app', 'amData', [
    'schemaVersion' => 4,
    'events'       => am_get_events(),
    'blogArchive'  => am_get_blog_archive(),
    'blogApiUrl'   => rest_url('alexandra/v1/blogs'),
    'testimonials' => $testimonial_preview['items'],
    'testimonialArchive' => [
      'apiUrl' => rest_url('alexandra/v1/testimonials'),
      'total'  => $testimonial_preview['total'],
    ],
    'settings'     => am_get_settings(),
    'nurseries'    => am_get_nurseries(),
    'jobs'         => am_get_jobs(),
    'about'        => am_get_about(),
    'heroPoster'   => alexandra_montessori_hero_poster(),
    'analyticsTagId' => am_analytics_tag_id(),
    'ga4Id'        => am_analytics_tag_id(),
  ]);
}
add_action('wp_enqueue_scripts', 'am_localize_cms_data', 20);

// ===================================================================
// Global Site Settings — CMB2 options page (phone / email / hours /
// address / apply URL / social links).
//
// Registered on the cmb2_admin_init hook: if the CMB2 plugin is NOT
// active the hook never fires, so new_cmb2_box() is never called and the
// site is completely unaffected (no fatal). Values are stored in the
// single wp_options row 'am_settings' and read back below with plain
// get_option(), so am_get_settings() also works if CMB2 is ever disabled.
// ===================================================================

function am_register_settings_page() {
  $cmb = new_cmb2_box([
    'id'           => 'am_settings_box',
    'title'        => 'Site Settings',
    'object_types' => ['options-page'],
    'option_key'   => 'am_settings',
    'menu_title'   => 'Site Settings',
    'icon_url'     => 'dashicons-admin-generic',
    'position'     => 59,
    'capability'   => 'manage_am_site_settings',
    'parent_slug'  => AM_CONTENT_MENU_SLUG,
  ]);

  $cmb->add_field([
    'name'       => 'Phone',
    'id'         => 'phone',
    'type'       => 'text',
    'attributes' => ['placeholder' => '0204 618 3477'],
  ]);
  $cmb->add_field([
    'name'       => 'Email',
    'id'         => 'email',
    'type'       => 'text_email',
    'attributes' => ['placeholder' => 'info@alexandramontessori.co.uk'],
  ]);
  $cmb->add_field([
    'name'       => 'Opening hours',
    'id'         => 'hours',
    'type'       => 'text',
    'attributes' => ['placeholder' => 'Mon – Fri · 8am – 6pm'],
  ]);
  $cmb->add_field([
    'name' => 'Address',
    'id'   => 'address',
    'type' => 'textarea_small',
  ]);
  $cmb->add_field([
    'name'       => 'Apply / contact URL',
    'id'         => 'apply_url',
    'type'       => 'text_url',
    'attributes' => ['placeholder' => 'https://applyalways.com/...'],
  ]);

  // Search engine visibility. This is a friendly wrapper around WordPress's
  // built-in "Discourage search engines" setting (the `blog_public` option),
  // so ticking/unticking here is exactly the same switch, just phrased for the
  // client. blog_public stays the single source of truth (see the save hook
  // and default callback below).
  $cmb->add_field([
    'name'       => 'Search engine visibility',
    'id'         => 'search_engine_visible',
    'type'       => 'checkbox',
    'desc'       => 'Let Google and other search engines find and list this website. '
      . 'Untick to ask search engines to keep the site out of their results (handy before launch). '
      . 'This is a request that reputable search engines such as Google and Bing honour — it is not a password or a hard block.',
    'default_cb' => 'am_search_engine_visible_default',
  ]);

  // Google Analytics 4 - paste the Measurement ID from your GA4 property
  // (Admin -> Data streams -> your web stream). Looks like G-XXXXXXXXXX.
  $cmb->add_field([
    'name'       => 'Google Analytics 4 Measurement ID',
    'id'         => 'ga4_id',
    'type'       => 'text',
    'attributes' => ['placeholder' => 'G-XXXXXXXXXX'],
    'desc'       => 'From Google Analytics -> Admin -> Data streams -> Web -> Measurement ID. Leave blank to switch analytics off. Visits are only counted once this is filled in.',
  ]);

  // Google Search Console - paste the verification meta tag (or just its token)
  // from Search Console -> Add property -> HTML tag.
  $cmb->add_field([
    'name'       => 'Google Search Console verification',
    'id'         => 'gsc_verification',
    'type'       => 'text',
    'attributes' => ['placeholder' => '<meta name="google-site-verification" content="..."> or just the code'],
    'desc'       => 'In Search Console choose the "HTML tag" verification method and paste the tag (or only the content code) here, then click Verify in Search Console.',
  ]);

  $socials = $cmb->add_field([
    'id'      => 'socials',
    'type'    => 'group',
    'name'    => 'Social links',
    'options' => [
      'group_title'   => 'Link {#}',
      'add_button'    => 'Add social link',
      'remove_button' => 'Remove link',
      'sortable'      => true,
    ],
  ]);
  $cmb->add_group_field($socials, [
    'name'       => 'Label',
    'id'         => 'label',
    'type'       => 'text',
    'attributes' => ['placeholder' => 'Facebook'],
  ]);
  $cmb->add_group_field($socials, [
    'name' => 'URL',
    'id'   => 'href',
    'type' => 'text_url',
  ]);
}
add_action('cmb2_admin_init', 'am_register_settings_page');

/**
 * Default state for the Search engine visibility toggle: mirror the live
 * WordPress `blog_public` option so the box always reflects the real setting.
 */
function am_search_engine_visible_default() {
  return get_option('blog_public', 1) ? 'on' : '';
}

/**
 * When Site Settings are saved, push the toggle into WordPress's native
 * `blog_public` option (0 = discourage search engines, 1 = allow). We then drop
 * the mirrored copy from the am_settings blob so `blog_public` remains the one
 * source of truth and the control can never drift out of sync.
 */
function am_sync_search_engine_visibility($cmb_id, $object_id, $updated, $cmb) {
  if ($cmb_id !== 'am_settings_box') {
    return;
  }
  $opts = get_option('am_settings');
  $checked = is_array($opts) && !empty($opts['search_engine_visible']);
  update_option('blog_public', $checked ? 1 : 0);
  if (is_array($opts) && array_key_exists('search_engine_visible', $opts)) {
    unset($opts['search_engine_visible']);
    update_option('am_settings', $opts);
  }
}
add_action('cmb2_save_options-page_fields', 'am_sync_search_engine_visibility', 10, 4);

/**
 * Global settings getter. Reads the 'am_settings' option directly (no CMB2
 * dependency) and returns a shape ready for window.amData.settings.
 */
function am_get_settings() {
  $o = get_option('am_settings', []);
  if (!is_array($o)) {
    $o = [];
  }

  $socials = [];
  if (!empty($o['socials']) && is_array($o['socials'])) {
    foreach ($o['socials'] as $s) {
      $label = isset($s['label']) ? $s['label'] : '';
      $href  = isset($s['href'])  ? $s['href']  : '';
      if ($label === '' && $href === '') {
        continue;
      }
      $socials[] = ['label' => $label, 'href' => $href];
    }
  }

  return [
    'phone'    => isset($o['phone'])     ? $o['phone']     : '',
    'email'    => isset($o['email'])     ? $o['email']     : '',
    'hours'    => isset($o['hours'])     ? $o['hours']     : '',
    'address'  => isset($o['address'])   ? $o['address']   : '',
    'applyUrl' => isset($o['apply_url']) ? $o['apply_url'] : '',
    'socials'  => $socials,
  ];
}

// ===================================================================
// "About us" homepage section — CMB2 options page (heading / body /
// image / milestones / upcoming nursery). Stored in the single 'am_about'
// option row and read back into window.amData.about. Same fail-safe as
// Site Settings: if CMB2 is inactive the hook never fires, no fatal, and
// am_get_about() still returns clean defaults from plain get_option().
// ===================================================================

function am_register_about_page() {
  $cmb = new_cmb2_box([
    'id'           => 'am_about_box',
    'title'        => 'About us section',
    'object_types' => ['options-page'],
    'option_key'   => 'am_about',
    'menu_title'   => 'About us',
    'icon_url'     => 'dashicons-groups',
    'position'     => 59,
    'capability'   => 'manage_am_site_settings',
    'parent_slug'  => AM_CONTENT_MENU_SLUG,
  ]);

  $cmb->add_field([
    'name'       => 'Heading',
    'id'         => 'heading',
    'type'       => 'text',
    'attributes' => ['placeholder' => 'About us'],
    'desc'       => 'The title shown above the About us section on the homepage.',
  ]);
  $cmb->add_field([
    'name'       => 'Body',
    'id'         => 'body',
    'type'       => 'textarea',
    'desc'       => 'The About us text. Leave a blank line between paragraphs — each block becomes its own paragraph on the website.',
    'attributes' => ['rows' => 10],
  ]);
  $cmb->add_field([
    'name'    => 'Image',
    'id'      => 'image',
    'type'    => 'file',
    'desc'    => 'The round photo beside the text (e.g. the owners). A square photo looks best. Leave empty to keep the built-in default.',
    'options' => ['url' => false],
    'query_args' => ['type' => ['image/jpeg', 'image/png', 'image/webp']],
    'text'    => ['add_upload_file_text' => 'Choose image'],
  ]);
  $cmb->add_field([
    'name'       => 'Milestones',
    'id'         => 'milestones',
    'type'       => 'textarea_small',
    'desc'       => 'One per line, e.g. "2019 - Hounslow". Shown as small pills under the text.',
    'attributes' => ['rows' => 4],
  ]);
  $cmb->add_field([
    'name'       => 'Upcoming nursery',
    'id'         => 'upcoming',
    'type'       => 'text',
    'attributes' => ['placeholder' => 'Coming soon - Ealing'],
    'desc'       => 'Optional. If filled in, it shows as a highlighted pill under the milestones. Leave blank to hide it.',
  ]);
}
add_action('cmb2_admin_init', 'am_register_about_page');

/**
 * About us getter. Reads the 'am_about' option directly (no CMB2 dependency)
 * and returns a shape ready for window.amData.about. Empty fields come back as
 * '' / [] so the React side substitutes its own built-in defaults.
 */
function am_get_about() {
  $o = get_option('am_about', []);
  if (!is_array($o)) $o = [];

  $image = isset($o['image']) ? trim((string) $o['image']) : '';
  $image_id = isset($o['image_id']) ? absint($o['image_id']) : 0;
  if (!$image_id && $image !== '') $image_id = attachment_url_to_postid($image);
  $image_srcset = $image_id ? wp_get_attachment_image_srcset($image_id, 'large') : false;

  $paragraphs = [];
  if (!empty($o['body'])) {
    foreach (preg_split('/\R{2,}/', (string) $o['body']) as $block) {
      $block = trim($block);
      if ($block !== '') $paragraphs[] = $block;
    }
  }

  $milestones = [];
  if (!empty($o['milestones'])) {
    foreach (preg_split('/\R+/', (string) $o['milestones']) as $line) {
      $line = trim($line);
      if ($line !== '') $milestones[] = $line;
    }
  }

  return [
    'heading'    => isset($o['heading'])  ? (string) $o['heading']  : '',
    'paragraphs' => $paragraphs,
    'image'      => $image,
    'imageSrcSet' => $image_srcset ?: '',
    'imageSizes' => '(min-width: 1024px) 448px, (min-width: 496px) 448px, calc(100vw - 48px)',
    'milestones' => $milestones,
    'upcoming'   => isset($o['upcoming']) ? (string) $o['upcoming'] : '',
  ];
}

// ===================================================================
// Notification recipient (shared by all website forms)
//
// ROUTING ACTIVE: this override is intentionally blank, so notifications go to
// each branch's inbox (see am_notify_email + am_branch_label), with the
// developer copied via AM_MONITOR_BCC below. Set this to an address again to
// force every form back to a single test inbox.
// ===================================================================
if (
  (!defined('AM_OPS_REPLACES_LEGACY') || !AM_OPS_REPLACES_LEGACY)
  && !defined('AM_TEST_NOTIFY_EMAIL')
) {
  define('AM_TEST_NOTIFY_EMAIL', '');
}

// Silent monitoring copy: every form notification is BCC'd here so the developer
// keeps oversight while the branch inboxes get the visible copy. Set to '' (or
// remove) at full hand-off. BCC is invisible to the client and to each other.
if (!defined('AM_MONITOR_BCC')) {
  define('AM_MONITOR_BCC', '');
}

/**
 * The address form notifications are sent to.
 * @param string $preferred A branch/location email to prefer in production.
 * While AM_TEST_NOTIFY_EMAIL is set, it always wins (testing).
 */
function am_notify_email($preferred = '') {
  if (defined('AM_TEST_NOTIFY_EMAIL') && AM_TEST_NOTIFY_EMAIL) {
    return AM_TEST_NOTIFY_EMAIL;
  }
  if (is_email($preferred)) {
    return $preferred;
  }
  $settings = am_get_settings();
  if (!empty($settings['email'])) {
    return $settings['email'];
  }
  // Main enquiries inbox — the safe default so no-branch mail never lands on
  // the developer once the AM_TEST_NOTIFY_EMAIL override is removed at go-live.
  return 'info@alexandramontessori.co.uk';
}

/**
 * SMTP delivery — routes wp_mail through a real SMTP server so emails actually
 * leave the machine and reach the real inbox (in Local, wp_mail is otherwise
 * caught by the built-in Mailpit trap and never delivered).
 *
 * Activated only when the SMTP constants exist in wp-config.php:
 *   define('AM_SMTP_HOST', 'smtp.gmail.com');
 *   define('AM_SMTP_PORT', 587);
 *   define('AM_SMTP_USER', 'you@gmail.com');
 *   define('AM_SMTP_PASS', 'your-16-char-app-password');   // Gmail App Password
 *   define('AM_SMTP_FROM', 'you@gmail.com');               // optional
 *   define('AM_SMTP_SECURE', 'tls');                       // optional: tls|ssl
 * If they are absent, this is a no-op and Local's Mailpit keeps catching mail.
 */
function am_configure_smtp($phpmailer) {
  if (!defined('AM_SMTP_HOST') || !AM_SMTP_HOST) {
    return; // no SMTP configured — leave default (Mailpit) transport alone
  }
  $phpmailer->isSMTP();
  $phpmailer->Host       = AM_SMTP_HOST;
  $phpmailer->Port       = defined('AM_SMTP_PORT') ? AM_SMTP_PORT : 587;
  $phpmailer->SMTPAuth   = true;
  $phpmailer->Username   = defined('AM_SMTP_USER') ? AM_SMTP_USER : '';
  $phpmailer->Password   = defined('AM_SMTP_PASS') ? AM_SMTP_PASS : '';
  $phpmailer->SMTPSecure = defined('AM_SMTP_SECURE') ? AM_SMTP_SECURE : 'tls';
  $phpmailer->Timeout    = 10; // seconds — don't let a slow SMTP server hang the form request
  $from = defined('AM_SMTP_FROM') ? AM_SMTP_FROM : (defined('AM_SMTP_USER') ? AM_SMTP_USER : '');
  if ($from) {
    $phpmailer->setFrom($from, 'Alexandra Montessori Website', false);
  }
}
add_action('phpmailer_init', 'am_configure_smtp');

/**
 * Send an email WITHOUT making the visitor wait for it.
 *
 * SMTP delivery to Gmail takes ~4-5 seconds here (TLS handshake + auth, plus any
 * attachment upload). Doing that inside the form request leaves the visitor on
 * "Sending…" the whole time. This server runs php-cgi/FastCGI (not FPM), so
 * fastcgi_finish_request() isn't available — instead we persist the
 * (server-generated, already-sanitised) payload under a single-use token and
 * fire a NON-BLOCKING loopback request to a worker endpoint that performs the
 * slow send in a separate PHP process. The form request returns in milliseconds.
 */
function am_send_mail_async($to, $subject, $body, $headers = [], $attachments = []) {
  // Developer monitoring copy (BCC), added centrally so it covers every form.
  // Skipped when it would duplicate the primary recipient (e.g. while the
  // AM_TEST_NOTIFY_EMAIL override already routes everything to that address).
  if (defined('AM_MONITOR_BCC') && AM_MONITOR_BCC && strcasecmp(AM_MONITOR_BCC, (string) $to) !== 0) {
    $headers[] = 'Bcc: ' . AM_MONITOR_BCC;
  }

  // On Local the non-blocking loopback is unreliable: constrained php-cgi workers
  // can starve the second (worker) request, so the queued mail is silently
  // dropped. Send inline here — slower for the request but 100% delivered.
  // Production keeps the fast async loopback below (verified delivering there).
  if (function_exists('wp_get_environment_type') && wp_get_environment_type() === 'local') {
    wp_mail($to, $subject, $body, $headers, $attachments);
    return;
  }

  $token = wp_generate_password(32, false);
  set_transient('am_mail_' . $token, [
    'to'          => $to,
    'subject'     => $subject,
    'body'        => $body,
    'headers'     => $headers,
    'attachments' => $attachments,
  ], 5 * MINUTE_IN_SECONDS);

  $dispatched = wp_remote_post(rest_url('am/v1/dispatch-mail'), [
    'blocking'  => false,     // don't wait for the worker to finish
    'timeout'   => 0.01,
    'sslverify' => false,     // local self-signed cert on the loopback host
    'body'      => ['token' => $token],
  ]);

  // Safety net: if the loopback couldn't be initiated at all, send inline so a
  // notification is never silently lost (slower, but reliable).
  if (is_wp_error($dispatched)) {
    delete_transient('am_mail_' . $token);
    wp_mail($to, $subject, $body, $headers, $attachments);
  }
}

/**
 * Background worker for am_send_mail_async(): given a single-use token, look up
 * the stored payload and perform the actual (slow) SMTP send. Safe to expose
 * publicly — it only ever sends a payload the server itself generated and
 * stored; the random token is consumed on first use, so it can't be replayed or
 * used to send arbitrary mail.
 */
if (!defined('AM_OPS_REPLACES_LEGACY') || !AM_OPS_REPLACES_LEGACY) {
  add_action('rest_api_init', function () {
    register_rest_route('am/v1', '/dispatch-mail', [
      'methods'             => 'POST',
      'callback'            => 'am_dispatch_mail',
      'permission_callback' => '__return_true',
    ]);
  });
}

function am_dispatch_mail(WP_REST_Request $request) {
  $token = sanitize_text_field($request->get_param('token'));
  if (!$token) {
    return rest_ensure_response(['ok' => false]);
  }
  $key     = 'am_mail_' . $token;
  $payload = get_transient($key);
  if (!$payload) {
    return rest_ensure_response(['ok' => false]);
  }
  delete_transient($key); // single use
  wp_mail(
    $payload['to'],
    $payload['subject'],
    $payload['body'],
    $payload['headers'],
    $payload['attachments']
  );
  return rest_ensure_response(['ok' => true]);
}

/**
 * Diagnostic: record any wp_mail delivery failure (e.g. SMTP auth error) to
 * wp-content/uploads/am-mail-log/_FAILED.log so delivery problems are visible.
 */
function am_log_mail_failure($wp_error) {
  $upload = wp_upload_dir();
  $dir    = trailingslashit($upload['basedir']) . 'am-mail-log';
  if (!file_exists($dir)) {
    wp_mkdir_p($dir);
  }
  @file_put_contents(
    "{$dir}/_FAILED.log",
    current_time('c') . ' — ' . $wp_error->get_error_message() . "\n",
    FILE_APPEND
  );
}
if (!defined('AM_OPS_REPLACES_LEGACY') || !AM_OPS_REPLACES_LEGACY) {
  add_action('wp_mail_failed', 'am_log_mail_failure');
}

/**
 * Mail logger — writes every outgoing wp_mail to
 * wp-content/uploads/am-mail-log/ as a timestamped .html file, so the exact
 * subject/recipient/body can be reviewed even when local SMTP is not delivering.
 * Purely additive; does not affect real delivery.
 */
function am_log_outgoing_mail($args) {
  // Local QA only. Production relies on immutable submission records and the
  // mail provider; duplicating customer data in a public uploads URL is unsafe.
  $host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
  if (!str_ends_with($host, '.local') && wp_get_environment_type() !== 'local') {
    return $args;
  }
  $dir = trailingslashit(dirname(ABSPATH, 2)) . 'private-logs/alexandra-mail';
  if (!file_exists($dir)) {
    wp_mkdir_p($dir);
  }
  $to      = is_array($args['to']) ? implode(', ', $args['to']) : $args['to'];
  $stamp   = current_time('Y-m-d_His');
  $slug    = sanitize_title(substr((string) $args['subject'], 0, 40));
  $body    = "<p><strong>To:</strong> " . esc_html($to) . "<br>"
           . "<strong>Subject:</strong> " . esc_html($args['subject']) . "<br>"
           . "<strong>Time:</strong> " . current_time('c') . "</p><hr>"
           . (is_array($args['message']) ? '' : $args['message']);
  @file_put_contents("{$dir}/{$stamp}-{$slug}.html", $body);
  return $args;
}
add_filter('wp_mail', 'am_log_outgoing_mail');

// Shared form abuse guard (per-IP rate limiting) — used by all form endpoints
require_once get_template_directory() . '/inc/am-validation.php';
require_once get_template_directory() . '/inc/am-media-library.php';
require_once get_template_directory() . '/inc/am-content-contracts.php';
require_once get_template_directory() . '/inc/am-admin.php';
require_once get_template_directory() . '/inc/am-roles.php';

if (!defined('AM_OPS_REPLACES_LEGACY') || !AM_OPS_REPLACES_LEGACY) {
  require_once get_template_directory() . '/inc/am-rate-limit.php';
  require_once get_template_directory() . '/inc/am-private-files.php';
  require_once get_template_directory() . '/inc/am-submissions.php';

  // Legacy form storage remains available only until the operations migration
  // is verified and the must-use plugin replacement flag is enabled.
  require_once get_template_directory() . '/inc/am-careers-api.php';
  require_once get_template_directory() . '/inc/am-enquiries-api.php';
}
