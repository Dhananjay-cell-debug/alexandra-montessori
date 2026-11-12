<?php
/**
 * Little Rainbows Day Nursery — theme functions.
 *
 * Integrates the React/Vite SPA (built into /dist) into WordPress:
 *  - enqueues the hashed entry JS (as an ES module) + CSS read from .vite/manifest.json,
 *    so the correct files are used after every rebuild without hardcoding hashes;
 *  - preloads the self-hosted Fraunces and Nunito Sans variable fonts;
 *  - serves the SPA shell for every front-end URL with a 200 status so client-side
 *    routes (/about, /our-nursery/hounslow, …) work on refresh and deep-link.
 */

if (!defined('ABSPATH')) {
  exit;
}

function ldrn_theme_theme_setup() {
  add_theme_support('title-tag');
  // Enables the Featured Image (thumbnail) upload box on Blog posts and Events
  // so staff can choose the exact image shown on the public site. Without this
  // the editor never offers a thumbnail field and the public cards fall back to
  // a generic image.
  add_theme_support('post-thumbnails');
  // A right-sized candidate for the circular Home image. Future JPEG uploads
  // are emitted as WebP below, so Visual Builder/CMS srcsets stay efficient.
  add_image_size('ldrn-home-portrait', 480, 640, false);
}
add_action('after_setup_theme', 'ldrn_theme_theme_setup');

/** Generate modern responsive sub-sizes for future photographic uploads. */
function ldrn_theme_image_output_formats($formats) {
  $formats['image/jpeg'] = 'image/webp';
  return $formats;
}
add_filter('image_editor_output_format', 'ldrn_theme_image_output_formats');

/** A balanced quality level for responsive JPEG/WebP derivatives. */
function ldrn_theme_image_quality($quality, $mime_type) {
  return in_array($mime_type, array('image/jpeg', 'image/webp'), true) ? 76 : $quality;
}
add_filter('wp_editor_set_quality', 'ldrn_theme_image_quality', 10, 2);
function ldrn_theme_jpeg_quality() {
  return 76;
}
add_filter('jpeg_quality', 'ldrn_theme_jpeg_quality');

/**
 * Filesystem path / URL of the built SPA in the theme's /dist directory.
 */
function ldrn_dist_path() {
  return apply_filters('ldrn_dist_path',get_template_directory() . '/dist');
}
function ldrn_dist_uri() {
  return apply_filters('ldrn_dist_uri',get_template_directory_uri() . '/dist');
}

/** Whether the current public request is the SPA home route. */
function ldrn_theme_is_home_request() {
  $request_path = isset($_SERVER['REQUEST_URI'])
    ? (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH)
    : '/';
  $home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
  return trim($request_path, '/') === trim($home_path, '/');
}

/**
 * Resolve the home hero image before React starts, so the browser can
 * discover the real LCP image in the initial HTML rather than waiting for the
 * JavaScript bundle to mount the Home page.
 *
 * LDRN's hero is a still photograph, not a video poster. The default matches
 * the image the Home component renders; a Visual Builder override wins when
 * an editor has set one.
 *
 * @return array{src:string,mobile:string}
 */
function ldrn_theme_hero_poster() {
  static $poster = null;
  if (is_array($poster)) return $poster;

  $default = ldrn_dist_uri() . '/assets/photography/hero-practitioner-and-child-playdough.png';
  $src = $default;
  $mobile = $default;
  $attachment_id = 0;

  if (class_exists('LDRN_VB_Home_Design')) {
    $design = LDRN_VB_Home_Design::get();
    $element = isset($design['elements']['hero-poster']) && is_array($design['elements']['hero-poster'])
      ? $design['elements']['hero-poster']
      : array();
    $attachment_id = isset($element['srcId']) ? absint($element['srcId']) : 0;
    $saved_src = isset($element['src']) ? trim((string) $element['src']) : '';
    if ($saved_src !== '') {
      $resolved = class_exists('LDRN_VB_Media')
        ? LDRN_VB_Media::resolve($saved_src, $attachment_id)
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
function ldrn_vite_manifest() {
  static $manifest = null;
  if ($manifest !== null) {
    return $manifest;
  }
  $file = ldrn_dist_path() . '/.vite/manifest.json';
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
function ldrn_theme_enqueue_assets() {
  $manifest = ldrn_vite_manifest();
  if (empty($manifest['index.html'])) {
    return; // No build present — nothing more to enqueue.
  }
  $entry = $manifest['index.html'];
  $dist  = ldrn_dist_uri();

  // Bundled CSS (content-hashed → version null, no ?ver= cache-buster needed).
  if (!empty($entry['css'])) {
    foreach ($entry['css'] as $i => $css) {
      wp_enqueue_style(
        'ldrn-app' . ($i ? '-' . $i : ''),
        $dist . '/' . $css,
        array(),
        null
      );
    }
  }

  // Hashed entry JS, loaded in the footer as an ES module (see filter below).
  if (!empty($entry['file'])) {
    wp_enqueue_script(
      'ldrn-app',
      $dist . '/' . $entry['file'],
      array(),
      null,
      true
    );
  }
}
add_action('wp_enqueue_scripts', 'ldrn_theme_enqueue_assets');

/**
 * The public site is a complete React SPA, so WordPress's block/editor styles
 * do not style any visible node. Remove those payloads and fold the Visual
 * Builder's small stylesheet into the already-required hashed app CSS.
 */
function ldrn_theme_optimize_frontend_assets() {
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

  if (wp_style_is('ldrn-vb-style-vars', 'enqueued') && wp_style_is('ldrn-app', 'enqueued')) {
    $style_file = defined('LDRN_VB_PLUGIN_DIR') ? LDRN_VB_PLUGIN_DIR . '/assets/style-vars.css' : '';
    if ($style_file && is_readable($style_file)) {
      wp_add_inline_style('ldrn-app', file_get_contents($style_file));
      wp_dequeue_style('ldrn-vb-style-vars');
    }
  }
}
add_action('wp_enqueue_scripts', 'ldrn_theme_optimize_frontend_assets', 100);

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
function ldrn_theme_script_type($tag, $handle, $src) {
  if ($handle === 'ldrn-app') {
    // WordPress may include before/after inline data in this same tag string.
    // Preserve those declarations; only the actual bundle uses module type.
    return preg_replace_callback('/<script\b[^>]*\bid=["\']ldrn-app-js["\'][^>]*>/i', static function($match) {
      $opening=preg_replace('/\s+type=["\'][^"\']*["\']/i','',$match[0]);
      return substr($opening,0,-1).' type="module" crossorigin>';
    },$tag);
  }
  if (in_array($handle, array('ldrn-vb-style-apply', 'ldrn-vb-site-runtime'), true)) {
    $id = preg_quote($handle . '-js', '/');
    return preg_replace('/<script(?=[^>]*\bid=["\']' . $id . '["\'])/', '<script defer', $tag, 1);
  }
  return $tag;
}
add_filter('script_loader_tag', 'ldrn_theme_script_type', 10, 3);

/**
 * Preconnect to the Google Fonts hosts and modulepreload the entry's static
 * import chunks, mirroring Vite's own <head> hints for faster startup.
 */
function ldrn_theme_head_hints() {
  if (ldrn_theme_is_home_request()) {
    $poster = ldrn_theme_hero_poster();
    if ($poster['mobile'] !== $poster['src']) {
      echo '<link rel="preload" as="image" href="' . esc_url($poster['mobile']) . '" media="(max-width: 1023px)" fetchpriority="high">' . "\n";
      echo '<link rel="preload" as="image" href="' . esc_url($poster['src']) . '" media="(min-width: 1024px)" fetchpriority="high">' . "\n";
    } else {
      echo '<link rel="preload" as="image" href="' . esc_url($poster['src']) . '" fetchpriority="high">' . "\n";
    }
  }

  foreach (array('fraunces-latin-var.woff2', 'nunito-sans-latin-var.woff2') as $font) {
    if (!is_readable(ldrn_dist_path() . '/fonts/' . $font)) {
      continue;
    }
    echo '<link rel="preload" as="font" type="font/woff2" crossorigin href="'
      . esc_url(ldrn_dist_uri() . '/fonts/' . $font) . '">' . "\n";
  }

  $manifest = ldrn_vite_manifest();
  if (empty($manifest['index.html']['imports'])) {
    return;
  }
  $dist = ldrn_dist_uri();
  foreach ($manifest['index.html']['imports'] as $key) {
    if (!empty($manifest[$key]['file'])) {
      echo '<link rel="modulepreload" crossorigin href="'
        . esc_url($dist . '/' . $manifest[$key]['file']) . '">' . "\n";
    }
  }
}
add_action('wp_head', 'ldrn_theme_head_hints', 1);

/**
 * The validated GA4 Measurement ID from Site Settings, or '' if unset/invalid.
 * Shared by the <head> tag output and the window.ldrnData contract so the SPA
 * page_view tracker and the gtag loader always agree.
 */
function ldrn_ga4_id() {
  $opts = get_option('ldrn_site_settings');
  $id = is_array($opts) && isset($opts['ga4_id']) ? trim((string) $opts['ga4_id']) : '';
  return preg_match('/^G-[A-Z0-9]+$/i', $id) ? $id : '';
}

/**
 * The validated Meta (Facebook) Pixel ID from Site Settings, or '' if unset/invalid.
 * Pixel IDs are purely numeric (usually 15-16 digits). Shared with the SPA via
 * window.ldrnData so the React consent-aware loader can fire it after marketing
 * cookies are accepted (see Analytics.jsx).
 */
function ldrn_meta_pixel_id() {
  $opts = get_option('ldrn_site_settings');
  $id = is_array($opts) && isset($opts['meta_pixel_id']) ? trim((string) $opts['meta_pixel_id']) : '';
  return preg_match('/^\d{10,20}$/', $id) ? $id : '';
}

/** Site Kit is the authoritative Analytics connection in production. */
function ldrn_analytics_tag_id() {
  $site_kit = get_option('googlesitekit_analytics-4_settings');
  if (is_array($site_kit)) {
    foreach (array('measurementID', 'googleTagID') as $key) {
      $id = isset($site_kit[$key]) ? trim((string) $site_kit[$key]) : '';
      if (preg_match('/^(?:G|GT)-[A-Z0-9]+$/i', $id)) return $id;
    }
  }
  return ldrn_ga4_id();
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
function ldrn_output_head_marketing_tags() {
  $opts = get_option('ldrn_site_settings');
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
add_action('wp_head', 'ldrn_output_head_marketing_tags', 2);

/**
 * SPA routing: the theme has a single template (index.php), which WordPress also
 * uses as the 404 fallback, so every unmatched front-end URL already renders the
 * React shell. Normalise the HTTP status to 200 on those routes so deep links and
 * refreshes (e.g. /about, /our-nursery/hounslow) are not served as 404s.
 * Admin, REST (wp-json), wp-login and real files are never is_404(), so untouched.
 */
function ldrn_theme_spa_status() {
  if (!is_admin() && is_404()) {
    status_header(200);
    nocache_headers();
  }
}
add_action('template_redirect', 'ldrn_theme_spa_status');

function ldrn_theme_preserve_blog_routes($redirect_url, $requested_url) {
  $path = untrailingslashit((string) wp_parse_url($requested_url, PHP_URL_PATH));
  if ($path === '/blogs' || str_starts_with($path, '/blogs/')) return false;
  return $redirect_url;
}
add_filter('redirect_canonical', 'ldrn_theme_preserve_blog_routes', 10, 2);

function ldrn_public_security_headers() {
  if (is_admin()) return;
  header_remove('X-Powered-By');
  header('X-Content-Type-Options: nosniff');
  header('X-Frame-Options: SAMEORIGIN');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
}
add_action('send_headers', 'ldrn_public_security_headers');

/**
 * Keep the WP admin bar from overlapping the SPA's fixed navbar on the front end.
 * (Reversible — remove this line to restore the admin bar for logged-in users.)
 */
add_filter('show_admin_bar', '__return_false');

// ===================================================================
// Phase 1 CMS — Custom Post Types: Events + Testimonials
// ===================================================================

function ldrn_register_cpts() {
  register_post_type('ldrn_event', [
    'labels' => [
      'name'          => 'Events',
      'singular_name' => 'Event',
      'add_new_item'  => 'Add New Event',
      'edit_item'     => 'Edit Event',
    ],
    'public'       => false,
    'show_ui'      => true,
    'show_in_menu' => LDRN_CONTENT_MENU_SLUG,
    'supports'     => ['title', 'editor', 'excerpt', 'thumbnail'],
    'menu_icon'    => 'dashicons-calendar-alt',
    'rewrite'      => false,
  ]);

  register_post_type('ldrn_testimonial', [
    'labels' => [
      'name'          => 'Testimonials',
      'singular_name' => 'Testimonial',
      'add_new_item'  => 'Add New Testimonial',
      'edit_item'     => 'Edit Testimonial',
    ],
    'public'       => false,
    'show_ui'      => true,
    'show_in_menu' => LDRN_CONTENT_MENU_SLUG,
    'supports'     => ['title', 'page-attributes'],
    'menu_icon'    => 'dashicons-format-quote',
    'rewrite'      => false,
  ]);

  register_post_type('ldrn_setting', [
    'labels' => [
      'name'          => 'Our Nursery',
      'singular_name' => 'Nursery',
      'add_new_item'  => 'Add New Nursery',
      'edit_item'     => 'Edit Nursery',
    ],
    'public'       => false,
    'show_ui'      => true,
    'show_in_menu' => LDRN_CONTENT_MENU_SLUG,
    'supports'     => ['title', 'page-attributes'],
    'menu_icon'    => 'dashicons-location',
    'rewrite'      => false,
  ]);

  register_post_type('ldrn_job', [
    'labels' => [
      'name'          => 'Jobs',
      'singular_name' => 'Job',
      'add_new_item'  => 'Add New Job',
      'edit_item'     => 'Edit Job',
    ],
    'public'       => false,
    'show_ui'      => true,
    'show_in_menu' => LDRN_CONTENT_MENU_SLUG,
    'supports'     => ['title', 'page-attributes'],
    'menu_icon'    => 'dashicons-id-alt',
    'rewrite'      => false,
  ]);
}
add_action('init', 'ldrn_register_cpts');

// ----------------------------------------------------------------
// Meta boxes — Events (date / time / location)
// ----------------------------------------------------------------

function ldrn_event_meta_boxes() {
  add_meta_box('ldrn_event_details', 'Event Details', 'ldrn_event_meta_callback', 'ldrn_event', 'normal', 'high');
}
add_action('add_meta_boxes', 'ldrn_event_meta_boxes');

function ldrn_event_meta_callback($post) {
  wp_nonce_field('ldrn_event_save', 'ldrn_event_nonce');
  $date     = get_post_meta($post->ID, '_ldrn_event_date', true);
  $time     = get_post_meta($post->ID, '_ldrn_event_time', true);
  $location = get_post_meta($post->ID, '_ldrn_event_location', true);
  $booking  = get_post_meta($post->ID, '_ldrn_event_booking_info', true);
  echo '<p><label><strong>Date (YYYY-MM-DD)</strong><br>';
  echo '<input type="date" name="ldrn_event_date" value="' . esc_attr($date) . '" style="width:100%;margin-top:4px;padding:4px;" /></label></p>';
  echo '<p><label><strong>Time</strong><br>';
  echo '<input type="text" name="ldrn_event_time" value="' . esc_attr($time) . '" placeholder="e.g. 9:30am – 11:30am" style="width:100%;margin-top:4px;padding:4px;" /></label></p>';
  echo '<p><label><strong>Location</strong><br>';
  echo '<input type="text" name="ldrn_event_location" value="' . esc_attr($location) . '" placeholder="e.g. Hounslow" style="width:100%;margin-top:4px;padding:4px;" /></label></p>';
  echo '<p><label><strong>Booking information</strong><br>';
  echo '<textarea name="ldrn_event_booking_info" rows="3" placeholder="How to book, cost, what to bring…" style="width:100%;margin-top:4px;padding:4px;">' . esc_textarea($booking) . '</textarea></label></p>';
  echo '<p style="color:#666;font-size:12px;margin-top:6px;">The main editor above is the full event description shown on the event details page. The Excerpt is the short card preview.</p>';
}

function ldrn_event_save_meta($post_id) {
  if (!isset($_POST['ldrn_event_nonce']) || !wp_verify_nonce($_POST['ldrn_event_nonce'], 'ldrn_event_save')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;
  if (isset($_POST['ldrn_event_date']))         update_post_meta($post_id, '_ldrn_event_date',         sanitize_text_field($_POST['ldrn_event_date']));
  if (isset($_POST['ldrn_event_time']))         update_post_meta($post_id, '_ldrn_event_time',         sanitize_text_field($_POST['ldrn_event_time']));
  if (isset($_POST['ldrn_event_location']))     update_post_meta($post_id, '_ldrn_event_location',     sanitize_text_field($_POST['ldrn_event_location']));
  if (isset($_POST['ldrn_event_booking_info'])) update_post_meta($post_id, '_ldrn_event_booking_info', sanitize_textarea_field($_POST['ldrn_event_booking_info']));
}
add_action('save_post_ldrn_event', 'ldrn_event_save_meta');

// ----------------------------------------------------------------
// Meta boxes — Testimonials (quote / parent name / location)
// ----------------------------------------------------------------

function ldrn_testimonial_meta_boxes() {
  add_meta_box('ldrn_testimonial_details', 'Testimonial Details', 'ldrn_testimonial_meta_callback', 'ldrn_testimonial', 'normal', 'high');
}
add_action('add_meta_boxes', 'ldrn_testimonial_meta_boxes');

function ldrn_testimonial_meta_callback($post) {
  wp_nonce_field('ldrn_testimonial_save', 'ldrn_testimonial_nonce');
  $quote    = get_post_meta($post->ID, '_ldrn_testimonial_quote', true);
  $name     = get_post_meta($post->ID, '_ldrn_testimonial_name', true);
  $location = get_post_meta($post->ID, '_ldrn_testimonial_location', true);
  echo '<p><label><strong>Quote</strong><br>';
  echo '<textarea name="ldrn_testimonial_quote" rows="4" style="width:100%;margin-top:4px;padding:4px;">' . esc_textarea($quote) . '</textarea></label></p>';
  echo '<p><label><strong>Parent name / descriptor</strong><br>';
  echo '<input type="text" name="ldrn_testimonial_name" value="' . esc_attr($name) . '" placeholder="e.g. Parent of a 3-year-old" style="width:100%;margin-top:4px;padding:4px;" /></label></p>';
  echo '<p><label><strong>Location</strong><br>';
  echo '<input type="text" name="ldrn_testimonial_location" value="' . esc_attr($location) . '" placeholder="e.g. Hounslow" style="width:100%;margin-top:4px;padding:4px;" /></label></p>';
}

function ldrn_testimonial_save_meta($post_id) {
  if (!isset($_POST['ldrn_testimonial_nonce']) || !wp_verify_nonce($_POST['ldrn_testimonial_nonce'], 'ldrn_testimonial_save')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;
  if (isset($_POST['ldrn_testimonial_quote']))    update_post_meta($post_id, '_ldrn_testimonial_quote',    sanitize_textarea_field($_POST['ldrn_testimonial_quote']));
  if (isset($_POST['ldrn_testimonial_name']))     update_post_meta($post_id, '_ldrn_testimonial_name',     sanitize_text_field($_POST['ldrn_testimonial_name']));
  if (isset($_POST['ldrn_testimonial_location'])) update_post_meta($post_id, '_ldrn_testimonial_location', sanitize_text_field($_POST['ldrn_testimonial_location']));
}
add_action('save_post_ldrn_testimonial', 'ldrn_testimonial_save_meta');

// ===================================================================
// Nurseries CMS — CMB2 meta box for ldrn_setting CPT
// ===================================================================

function ldrn_register_nursery_fields() {
  if (!function_exists('new_cmb2_box')) return;

  $cmb = new_cmb2_box([
    'id'           => 'ldrn_setting_details',
    'title'        => 'Nursery Details',
    'object_types' => ['ldrn_setting'],
    'context'      => 'normal',
    'priority'     => 'high',
  ]);

  $cmb->add_field(['name' => 'Area',              'id' => '_ldrn_setting_area',          'type' => 'text']);
  $cmb->add_field(['name' => 'Hero Tagline',       'id' => '_ldrn_setting_hero_tagline',  'type' => 'text']);
  $cmb->add_field(['name' => 'Subheading',         'id' => '_ldrn_setting_subheading',    'type' => 'text']);
  $cmb->add_field(['name' => 'Address',            'id' => '_ldrn_setting_address',       'type' => 'text']);
  $cmb->add_field(['name' => 'Postcode',           'id' => '_ldrn_setting_postcode',      'type' => 'text']);
  $cmb->add_field(['name' => 'Phone',              'id' => '_ldrn_setting_phone',         'type' => 'text']);
  $cmb->add_field(['name' => 'Email',              'id' => '_ldrn_setting_email',         'type' => 'text_email']);
  $cmb->add_field(['name' => 'Hours',              'id' => '_ldrn_setting_hours',         'type' => 'text', 'attributes' => ['placeholder' => 'Mon – Fri · 8am – 6pm']]);
  $cmb->add_field(['name' => 'Age range',          'id' => '_ldrn_setting_age_range',     'type' => 'text', 'attributes' => ['placeholder' => '6 months to 5 years'], 'desc' => 'Admission age range published for this setting.']);
  $cmb->add_field(['name' => 'Short description',  'id' => '_ldrn_setting_short',         'type' => 'textarea_small']);
  $cmb->add_field(['name' => 'Welcome text',       'id' => '_ldrn_setting_welcome',       'type' => 'textarea']);
  $cmb->add_field(['name' => 'Hero image',         'id' => '_ldrn_setting_hero_image',    'type' => 'file', 'options' => ['url' => false]]);
  $cmb->add_field(['name' => 'Welcome image',      'id' => '_ldrn_setting_welcome_image', 'type' => 'file', 'options' => ['url' => false]]);
  $cmb->add_field(['name' => 'Gallery',            'id' => '_ldrn_setting_gallery',       'type' => 'file_list']);
  $cmb->add_field(['name' => 'Fee sheet PDF',      'id' => '_ldrn_setting_fee_sheet_pdf', 'type' => 'file', 'options' => ['url' => false], 'query_args' => ['type' => 'application/pdf']]);

  // --- Ofsted (shown site-wide + footer; client-requested) ---
  $cmb->add_field([
    'name'    => 'Ofsted rating',
    'id'      => '_ldrn_setting_ofsted_rating',
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
  $cmb->add_field(['name' => 'Ofsted report URL',   'id' => '_ldrn_setting_ofsted_url',   'type' => 'text_url']);
  $cmb->add_field(['name' => 'Ofsted link label',   'id' => '_ldrn_setting_ofsted_label', 'type' => 'text', 'attributes' => ['placeholder' => 'Official Ofsted report']]);

  // --- Food Hygiene Rating (FSA; drives /food-hygiene-rating) ---
  $cmb->add_field(['name' => 'Food hygiene rating',      'id' => '_ldrn_setting_hygiene_rating',    'type' => 'text', 'attributes' => ['placeholder' => '5'], 'desc' => 'FSA rating 0–5, or a note if awaiting listing.']);
  $cmb->add_field(['name' => 'Food hygiene rating date', 'id' => '_ldrn_setting_hygiene_date',      'type' => 'text', 'attributes' => ['placeholder' => '20 January 2025']]);
  $cmb->add_field(['name' => 'Food hygiene authority',   'id' => '_ldrn_setting_hygiene_authority', 'type' => 'text', 'attributes' => ['placeholder' => 'London Borough of Hounslow']]);
  $cmb->add_field(['name' => 'Food hygiene FSA URL',     'id' => '_ldrn_setting_hygiene_url',       'type' => 'text_url']);
}
add_action('cmb2_admin_init', 'ldrn_register_nursery_fields');

// ===================================================================
// Jobs / Careers CMS — CMB2 meta box for ldrn_job CPT
// ===================================================================

function ldrn_setting_location_options() {
  $options = [];
  if (function_exists('ldrn_get_settings')) {
    foreach (ldrn_get_settings() as $nursery) {
      $name = sanitize_text_field((string) ($nursery['name'] ?? ''));
      if ($name !== '') $options[$name] = $name;
    }
  }
  $options['All settings'] = 'All settings';
  return $options;
}

function ldrn_register_job_fields() {
  if (!function_exists('new_cmb2_box')) return;

  $cmb = new_cmb2_box([
    'id'           => 'ldrn_job_details',
    'title'        => 'Job Details',
    'object_types' => ['ldrn_job'],
    'context'      => 'normal',
    'priority'     => 'high',
  ]);

  $cmb->add_field([
    'name'    => 'Status',
    'id'      => '_ldrn_job_status',
    'type'    => 'select',
    'options' => [
      'open'   => 'Open — visible on site',
      'closed' => 'Closed — hidden from site',
    ],
    'default' => 'open',
  ]);
  $cmb->add_field([
    'name'       => 'Location / Nursery',
    'id'         => '_ldrn_job_location',
    'type'       => 'select',
    'options_cb' => 'ldrn_setting_location_options',
  ]);
  $cmb->add_field([
    'name'    => 'Job type',
    'id'      => '_ldrn_job_type',
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
    'id'         => '_ldrn_job_hours',
    'type'       => 'text',
    'attributes' => ['placeholder' => 'e.g. 30 hrs/week'],
  ]);
  $cmb->add_field([
    'name'       => 'Salary',
    'id'         => '_ldrn_job_salary',
    'type'       => 'text',
    'attributes' => ['placeholder' => 'e.g. £24,000 – £28,000 pa'],
  ]);
  $cmb->add_field([
    'name' => 'Job summary',
    'id'   => '_ldrn_job_short',
    'type' => 'textarea_small',
    'desc' => 'Short preview shown on the vacancy card and at the top of the details page.',
  ]);
  $cmb->add_field([
    'name'    => 'Full description',
    'id'      => '_ldrn_job_full',
    'type'    => 'wysiwyg',
    'options' => ['textarea_rows' => 12],
    'desc'    => 'The complete role details — add headed sections for Responsibilities, Required skills, Qualifications and Experience.',
  ]);
  $cmb->add_field([
    'name' => 'Benefits',
    'id'   => '_ldrn_job_benefits',
    'type' => 'textarea_small',
    'desc' => 'One benefit per line — shown as a list on the details page.',
  ]);
  $cmb->add_field([
    'name' => 'Apply URL',
    'id'   => '_ldrn_job_apply_url',
    'type' => 'text_url',
  ]);
  $cmb->add_field([
    'name' => 'Apply email',
    'id'   => '_ldrn_job_apply_email',
    'type' => 'text_email',
  ]);
}
add_action('cmb2_admin_init', 'ldrn_register_job_fields');

// Blog display control: one staff-selected main article.
function ldrn_register_blog_display_fields() {
  if (!function_exists('new_cmb2_box')) return;

  $cmb = new_cmb2_box([
    'id'           => 'ldrn_blog_display',
    'title'        => 'Website placement',
    'object_types' => ['post'],
    'context'      => 'side',
    'priority'     => 'high',
  ]);
  $cmb->add_field([
    'name' => 'Show as the main Blog article',
    'id'   => '_ldrn_blog_featured',
    'type' => 'checkbox',
    'desc' => 'The chosen published article appears large. Every other ready article stays in the three-column archive.',
  ]);
}
add_action('cmb2_admin_init', 'ldrn_register_blog_display_fields');

function ldrn_blog_is_featured_choice($post_id) {
  return !empty(get_post_meta($post_id, '_ldrn_blog_featured', true));
}

function ldrn_enforce_single_featured_blog($post_id) {
  if (get_post_type($post_id) !== 'post'
    || get_post_status($post_id) !== 'publish'
    || !ldrn_blog_is_featured_choice($post_id)
    || (function_exists('ldrn_blog_is_frontend_ready') && !ldrn_blog_is_frontend_ready($post_id))) {
    return;
  }

  $other_choices = get_posts([
    'post_type'      => 'post',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'post__not_in'   => [(int) $post_id],
    'meta_key'       => '_ldrn_blog_featured',
    'meta_compare'   => 'EXISTS',
    'fields'         => 'ids',
  ]);
  foreach ($other_choices as $other_id) {
    delete_post_meta($other_id, '_ldrn_blog_featured');
  }
}

function ldrn_blog_featured_meta_changed($meta_id, $post_id, $meta_key, $meta_value) {
  if ($meta_key === '_ldrn_blog_featured' && !empty($meta_value)) {
    ldrn_enforce_single_featured_blog((int) $post_id);
  }
}
add_action('added_post_meta', 'ldrn_blog_featured_meta_changed', 10, 4);
add_action('updated_post_meta', 'ldrn_blog_featured_meta_changed', 10, 4);

function ldrn_enforce_featured_blog_after_save($post_id) {
  if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
  ldrn_enforce_single_featured_blog((int) $post_id);
}
add_action('save_post_post', 'ldrn_enforce_featured_blog_after_save', 100);

// ----------------------------------------------------------------
// Data getters — shape matches src/data/site.js exports exactly
// ----------------------------------------------------------------

function ldrn_get_events() {
  $posts = get_posts([
    'post_type'      => 'ldrn_event',
    'post_status'    => 'publish',
    'posts_per_page' => 100,
    'orderby'        => 'meta_value',
    'meta_key'       => '_ldrn_event_date',
    'order'          => 'ASC',
  ]);
  $result = [];
  foreach ($posts as $p) {
    if (function_exists('ldrn_event_is_frontend_ready') && !ldrn_event_is_frontend_ready($p->ID)) continue;
    if (function_exists('ldrn_ws_route_visible') && !ldrn_ws_route_visible('/events/'.$p->post_name)) continue;
    $content = trim($p->post_content);
    $result[] = [
      'id'          => $p->post_name,
      'title'       => ldrn_frontend_title($p),
      'date'        => get_post_meta($p->ID, '_ldrn_event_date', true)     ?: '',
      'time'        => get_post_meta($p->ID, '_ldrn_event_time', true)     ?: '',
      'location'    => get_post_meta($p->ID, '_ldrn_event_location', true) ?: '',
      'image'       => get_the_post_thumbnail_url($p->ID, 'full')        ?: '',
      'excerpt'     => get_the_excerpt($p),
      // Full description = the main editor body (rendered, sanitised HTML).
      'description' => $content ? wp_kses_post(wpautop($content)) : '',
      'bookingInfo' => get_post_meta($p->ID, '_ldrn_event_booking_info', true) ?: '',
    ];
  }
  return $result;
}

function ldrn_ready_blog_posts() {
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
    return (!function_exists('ldrn_blog_is_frontend_ready') || ldrn_blog_is_frontend_ready($post->ID))
      && (!function_exists('ldrn_ws_route_visible') || ldrn_ws_route_visible('/blogs/'.$post->post_name));
  }));
  return $ready_posts;
}

function ldrn_featured_blog_post_id($ready_posts = null) {
  $ready_posts = is_array($ready_posts) ? $ready_posts : ldrn_ready_blog_posts();
  foreach ($ready_posts as $post) {
    if (ldrn_blog_is_featured_choice($post->ID)) return (int) $post->ID;
  }
  return $ready_posts ? (int) $ready_posts[0]->ID : 0;
}

function ldrn_prepare_blog_card($post) {
  $cats = get_the_category($post->ID);
  $category = !empty($cats) && $cats[0]->slug !== 'uncategorized'
    ? $cats[0]->name
    : 'Resources';
  $fallback_image = get_template_directory_uri() . '/dist/assets/photography/children-at-craft-table.jpg';
  return [
    'slug'     => $post->post_name,
    'title'    => ldrn_frontend_title($post),
    'date'     => get_the_date('Y-m-d', $post),
    'category' => $category,
    'image'    => get_the_post_thumbnail_url($post->ID, 'full') ?: $fallback_image,
    'excerpt'  => get_the_excerpt($post),
  ];
}

function ldrn_prepare_blog_article($post) {
  return array_merge(ldrn_prepare_blog_card($post), [
    'content' => wp_kses_post(apply_filters('the_content', $post->post_content)),
  ]);
}

function ldrn_get_blog_archive($year = 0, $month = 0, $page = 1, $per_page = 9) {
  $all = ldrn_ready_blog_posts();
  $featured = null;
  $featured_id = ldrn_featured_blog_post_id($all);
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
    'featured' => $featured ? ldrn_prepare_blog_card($featured) : null,
    'items' => array_map('ldrn_prepare_blog_card', $items),
    'total' => $total,
    'page' => $page,
    'perPage' => $per_page,
    'totalPages' => $total_pages,
    'filters' => $filters,
  ];
}

// Retained for backward compatibility with an older cached frontend bundle.
function ldrn_get_blogs() {
  return array_map('ldrn_prepare_blog_article', array_slice(ldrn_ready_blog_posts(), 0, 12));
}

function ldrn_blog_archive_rest($request) {
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

  return rest_ensure_response(ldrn_get_blog_archive(
    $year,
    $month,
    max(1, (int) $request->get_param('page')),
    9
  ));
}

function ldrn_blog_article_rest($request) {
  $slug = sanitize_title((string) $request->get_param('slug'));
  $post = get_page_by_path($slug, OBJECT, 'post');
  if (!$post || $post->post_status !== 'publish' || !ldrn_blog_is_frontend_ready($post->ID)
      || (function_exists('ldrn_ws_route_visible') && !ldrn_ws_route_visible('/blogs/'.$slug))) {
    return new WP_Error('article_not_found', 'Article not found.', ['status' => 404]);
  }

  $related = [];
  foreach (ldrn_ready_blog_posts() as $candidate) {
    if ($candidate->ID === $post->ID) continue;
    $related[] = ldrn_prepare_blog_card($candidate);
    if (count($related) === 3) break;
  }

  return rest_ensure_response([
    'post' => ldrn_prepare_blog_article($post),
    'related' => $related,
  ]);
}

function ldrn_register_blog_rest_routes() {
  register_rest_route('ldrn/v1', '/blogs', [
    'methods' => WP_REST_Server::READABLE,
    'callback' => 'ldrn_blog_archive_rest',
    'permission_callback' => '__return_true',
  ]);
  register_rest_route('ldrn/v1', '/blogs/(?P<slug>[a-z0-9-]+)', [
    'methods' => WP_REST_Server::READABLE,
    'callback' => 'ldrn_blog_article_rest',
    'permission_callback' => '__return_true',
  ]);
}
add_action('rest_api_init', 'ldrn_register_blog_rest_routes');

function ldrn_prepare_testimonial($post) {
  return [
    'id'       => $post->post_name,
    'title'    => ldrn_frontend_title($post),
    'quote'    => get_post_meta($post->ID, '_ldrn_testimonial_quote', true)    ?: '',
    'name'     => get_post_meta($post->ID, '_ldrn_testimonial_name', true)     ?: '',
    'location' => get_post_meta($post->ID, '_ldrn_testimonial_location', true) ?: '',
  ];
}

function ldrn_testimonial_query($page = 1, $per_page = 9, $location = '') {
  global $wpdb;

  $page = max(1, (int) $page);
  $per_page = min(24, max(1, (int) $per_page));
  $location = sanitize_text_field((string) $location);
  $meta_query = [
    'relation' => 'AND',
    [
      'key'     => '_ldrn_testimonial_quote',
      'value'   => '',
      'compare' => '!=',
    ],
    [
      'key'     => '_ldrn_testimonial_name',
      'value'   => '',
      'compare' => '!=',
    ],
  ];
  if ($location !== '') {
    $meta_query[] = [
      'key'     => '_ldrn_testimonial_location',
      'value'   => [$location, 'All settings', 'General'],
      'compare' => 'IN',
    ];
  }

  $title_where = static function ($where) use ($wpdb) {
    return $where . " AND {$wpdb->posts}.post_title <> ''";
  };
  add_filter('posts_where', $title_where);
  $query_args = [
    'post_type'           => 'ldrn_testimonial',
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
    'items'   => array_map('ldrn_prepare_testimonial', $query->posts),
    'page'    => $resolved_page,
    'pages'   => max(1, (int) $query->max_num_pages),
    'total'   => (int) $query->found_posts,
    'perPage' => $per_page,
  ];
}

function ldrn_get_testimonials() {
  return ldrn_testimonial_query(1, 3)['items'];
}

function ldrn_testimonials_rest($request) {
  return rest_ensure_response(ldrn_testimonial_query(
    $request->get_param('page'),
    $request->get_param('per_page'),
    $request->get_param('location')
  ));
}

function ldrn_register_testimonial_rest_route() {
  register_rest_route('ldrn/v1', '/testimonials', [
    'methods'             => WP_REST_Server::READABLE,
    'callback'            => 'ldrn_testimonials_rest',
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
add_action('rest_api_init', 'ldrn_register_testimonial_rest_route');

/**
 * Returns nursery records in the exact shape of the `locations` array in
 * src/data/site.js. `features` is intentionally omitted — it is a shared
 * static icon list that lives in code and must not be overwritten by CMS data.
 * The React merge step will re-attach features by slug.
 */
function ldrn_get_settings() {
  $posts = get_posts([
    'post_type'      => 'ldrn_setting',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC', 'ID' => 'ASC'],
  ]);
  $result = [];
  foreach ($posts as $p) {
    if (function_exists('ldrn_setting_is_frontend_ready') && !ldrn_setting_is_frontend_ready($p->ID)) continue;
    if (function_exists('ldrn_ws_route_visible') && !ldrn_ws_route_visible('/our-nursery/'.$p->post_name)) continue;
    // file_list stores [ attachment_id => url ]; extract URLs only.
    $gallery_raw = get_post_meta($p->ID, '_ldrn_setting_gallery', true);
    $gallery = (is_array($gallery_raw) && !empty($gallery_raw)) ? array_values($gallery_raw) : [];

    $result[] = [
      'id'           => $p->post_name,
      'name'         => ldrn_frontend_title($p),
      'area'         => get_post_meta($p->ID, '_ldrn_setting_area',          true) ?: '',
      'heroTagline'  => get_post_meta($p->ID, '_ldrn_setting_hero_tagline',  true) ?: '',
      'subheading'   => get_post_meta($p->ID, '_ldrn_setting_subheading',    true) ?: '',
      'address'      => get_post_meta($p->ID, '_ldrn_setting_address',       true) ?: '',
      'postcode'     => get_post_meta($p->ID, '_ldrn_setting_postcode',      true) ?: '',
      'phone'        => get_post_meta($p->ID, '_ldrn_setting_phone',         true) ?: '',
      'email'        => get_post_meta($p->ID, '_ldrn_setting_email',         true) ?: '',
      'hours'        => get_post_meta($p->ID, '_ldrn_setting_hours',         true) ?: '',
      'ageRange'     => get_post_meta($p->ID, '_ldrn_setting_age_range',     true) ?: '',
      'image'        => get_post_meta($p->ID, '_ldrn_setting_hero_image',    true) ?: '',
      'welcomeImage' => get_post_meta($p->ID, '_ldrn_setting_welcome_image', true) ?: '',
      'gallery'      => $gallery,
      'short'        => get_post_meta($p->ID, '_ldrn_setting_short',         true) ?: '',
      'welcome'      => get_post_meta($p->ID, '_ldrn_setting_welcome',       true) ?: '',
      'feeSheetPdf'  => get_post_meta($p->ID, '_ldrn_setting_fee_sheet_pdf', true) ?: '',
      // Ofsted (client-requested; shown site-wide + footer)
      'ofstedRating'     => get_post_meta($p->ID, '_ldrn_setting_ofsted_rating',    true) ?: '',
      'ofstedUrl'        => get_post_meta($p->ID, '_ldrn_setting_ofsted_url',       true) ?: '',
      'ofstedLabel'      => get_post_meta($p->ID, '_ldrn_setting_ofsted_label',     true) ?: '',
      // Food Hygiene Rating (FSA; drives /food-hygiene-rating)
      'hygieneRating'    => get_post_meta($p->ID, '_ldrn_setting_hygiene_rating',    true) ?: '',
      'hygieneDate'      => get_post_meta($p->ID, '_ldrn_setting_hygiene_date',      true) ?: '',
      'hygieneAuthority' => get_post_meta($p->ID, '_ldrn_setting_hygiene_authority', true) ?: '',
      'hygieneUrl'       => get_post_meta($p->ID, '_ldrn_setting_hygiene_url',       true) ?: '',
    ];
  }
  return $result;
}

/**
 * Returns open job records for window.ldrnData.jobs.
 * Closed jobs remain editable in WP Admin but are excluded from the frontend.
 * Ordered by menu_order ASC (manual drag-and-drop), then date DESC.
 * fullDescription is sanitized with wp_kses_post to preserve safe WYSIWYG HTML.
 */
function ldrn_get_jobs() {
  $posts = get_posts([
    'post_type'      => 'ldrn_job',
    'post_status'    => 'publish',
    'posts_per_page' => 50,
    'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
    'meta_query'     => [
      'relation' => 'OR',
      [
        'key'     => '_ldrn_job_status',
        'value'   => 'open',
        'compare' => '=',
      ],
      // Include posts where status meta was never saved (defaults to open intent).
      [
        'key'     => '_ldrn_job_status',
        'compare' => 'NOT EXISTS',
      ],
    ],
  ]);
  $result = [];
  foreach ($posts as $p) {
    if (function_exists('ldrn_job_is_frontend_ready') && !ldrn_job_is_frontend_ready($p->ID)) continue;
    $result[] = [
      'id'               => $p->post_name,
      'title'            => ldrn_frontend_title($p),
      'location'         => get_post_meta($p->ID, '_ldrn_job_location',    true) ?: '',
      'jobType'          => get_post_meta($p->ID, '_ldrn_job_type',        true) ?: '',
      'hours'            => get_post_meta($p->ID, '_ldrn_job_hours',       true) ?: '',
      'salary'           => get_post_meta($p->ID, '_ldrn_job_salary',      true) ?: '',
      'shortDescription' => get_post_meta($p->ID, '_ldrn_job_short',       true) ?: '',
      'summary'          => get_post_meta($p->ID, '_ldrn_job_short',       true) ?: '',
      'fullDescription'  => wp_kses_post(get_post_meta($p->ID, '_ldrn_job_full', true) ?: ''),
      'benefits'         => get_post_meta($p->ID, '_ldrn_job_benefits',    true) ?: '',
      'applyUrl'         => get_post_meta($p->ID, '_ldrn_job_apply_url',   true) ?: '',
      'applyEmail'       => get_post_meta($p->ID, '_ldrn_job_apply_email', true) ?: '',
      'status'           => 'open',
    ];
  }
  return $result;
}

// ----------------------------------------------------------------
// Inject CMS data into the React app via window.ldrnData
// Priority 20 ensures 'ldrn-app' is already registered (priority 10).
// ----------------------------------------------------------------

/**
 * Whether the public forms may send anything to the server.
 *
 * Two locks, both of which must be open:
 *
 * 1. LDRN_SUBMISSIONS_ENABLED must be defined true in wp-config.php. This is
 *    a deliberate human action, taken only once the privacy policy naming the
 *    data controller and retention periods is published — until then the site
 *    has no lawful basis to store a parent's or candidate's details.
 * 2. An approved recipient must exist in the operations settings. Without one
 *    a submission would be stored with nobody to answer it.
 *
 * With either lock closed the React forms keep their current behaviour: they
 * check the visitor's answers on the device and send and store nothing.
 *
 * @return array<string,mixed>
 */
function ldrn_submissions_contract() {
  $reason = 'Server-side submissions are switched off for this site.';

  if (!defined('LDRN_SUBMISSIONS_ENABLED') || !LDRN_SUBMISSIONS_ENABLED) {
    return ['enabled' => false, 'reason' => $reason];
  }

  if (!class_exists('LDRN_Ops_Settings')) {
    return [
      'enabled' => false,
      'reason'  => 'The LDRN operations plugin is not active.',
    ];
  }

  $settings = LDRN_Ops_Settings::get();
  $has_recipient = !empty($settings['default_recipient'])
    || !empty($settings['application_recipient'])
    || !empty(array_filter((array) $settings['branch_recipients']));

  if (!$has_recipient) {
    return [
      'enabled' => false,
      'reason'  => 'No approved recipient has been configured yet.',
    ];
  }

  return ['enabled' => true, 'reason' => ''];
}

function ldrn_localize_cms_data() {
  if (!wp_script_is('ldrn-app', 'enqueued')) return;
  $testimonial_preview = ldrn_testimonial_query(1, 3);
  wp_localize_script('ldrn-app', 'ldrnData', apply_filters('ldrn_cms_data', [
    'schemaVersion' => 4,
    'submissions'  => ldrn_submissions_contract(),
    'events'       => ldrn_get_events(),
    'blogArchive'  => ldrn_get_blog_archive(),
    'blogApiUrl'   => rest_url('ldrn/v1/blogs'),
    'testimonials' => $testimonial_preview['items'],
    'testimonialArchive' => [
      'apiUrl' => rest_url('ldrn/v1/testimonials'),
      'total'  => $testimonial_preview['total'],
    ],
    'site'         => ldrn_get_site_settings(),
    'settings'     => ldrn_get_settings(),
    'jobs'         => ldrn_get_jobs(),
    'about'        => ldrn_get_about(),
    'heroPoster'   => ldrn_theme_hero_poster(),
    'analyticsTagId' => ldrn_analytics_tag_id(),
    'ga4Id'        => ldrn_analytics_tag_id(),
    'metaPixelId'  => ldrn_meta_pixel_id(),
  ]));
}
add_action('wp_enqueue_scripts', 'ldrn_localize_cms_data', 20);

// ===================================================================
// Global Site Settings — CMB2 options page (phone / email / hours /
// address / apply URL / social links).
//
// Registered on the cmb2_admin_init hook: if the CMB2 plugin is NOT
// active the hook never fires, so new_cmb2_box() is never called and the
// site is completely unaffected (no fatal). Values are stored in the
// single wp_options row 'ldrn_site_settings' and read back below with plain
// get_option(), so ldrn_get_site_settings() also works if CMB2 is ever disabled.
// ===================================================================

function ldrn_register_settings_page() {
  $cmb = new_cmb2_box([
    'id'           => 'ldrn_site_settings_box',
    'title'        => 'Site Settings',
    'object_types' => ['options-page'],
    'option_key'   => 'ldrn_site_settings',
    'menu_title'   => 'Site Settings',
    'icon_url'     => 'dashicons-admin-generic',
    'position'     => 59,
    'capability'   => 'manage_ldrn_site_settings',
    'parent_slug'  => LDRN_CONTENT_MENU_SLUG,
  ]);

  $cmb->add_field([
    'name'       => 'Phone',
    'id'         => 'phone',
    'type'       => 'text',
    'attributes' => ['placeholder' => '020 4619 6231'],
  ]);
  $cmb->add_field([
    'name'       => 'Email',
    'id'         => 'email',
    'type'       => 'text_email',
    'attributes' => ['placeholder' => 'littlerainbowsdaynursery@yahoo.co.uk'],
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
    'default_cb' => 'ldrn_search_engine_visible_default',
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

  // Meta (Facebook) Pixel - paste the Pixel ID from Meta Events Manager
  // (Data sources -> your pixel -> Settings). It's a numeric ID.
  $cmb->add_field([
    'name'       => 'Meta (Facebook) Pixel ID',
    'id'         => 'meta_pixel_id',
    'type'       => 'text',
    'attributes' => ['placeholder' => '1234567890123456'],
    'desc'       => 'From Meta Events Manager -> Data sources -> your pixel -> Settings. Leave blank to switch the pixel off. Loads only after a visitor accepts marketing cookies.',
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
add_action('cmb2_admin_init', 'ldrn_register_settings_page');

/**
 * Default state for the Search engine visibility toggle: mirror the live
 * WordPress `blog_public` option so the box always reflects the real setting.
 */
function ldrn_search_engine_visible_default() {
  return get_option('blog_public', 1) ? 'on' : '';
}

/**
 * When Site Settings are saved, push the toggle into WordPress's native
 * `blog_public` option (0 = discourage search engines, 1 = allow). We then drop
 * the mirrored copy from the ldrn_site_settings blob so `blog_public` remains the one
 * source of truth and the control can never drift out of sync.
 */
function ldrn_sync_search_engine_visibility($cmb_id, $object_id, $updated, $cmb) {
  if ($cmb_id !== 'ldrn_site_settings_box') {
    return;
  }
  $opts = get_option('ldrn_site_settings');
  $checked = is_array($opts) && !empty($opts['search_engine_visible']);
  update_option('blog_public', $checked ? 1 : 0);
  if (is_array($opts) && array_key_exists('search_engine_visible', $opts)) {
    unset($opts['search_engine_visible']);
    update_option('ldrn_site_settings', $opts);
  }
}
add_action('cmb2_save_options-page_fields', 'ldrn_sync_search_engine_visibility', 10, 4);

/**
 * Global settings getter. Reads the 'ldrn_site_settings' option directly (no CMB2
 * dependency) and returns a shape ready for window.ldrnData.settings.
 */
function ldrn_get_site_settings() {
  $o = get_option('ldrn_site_settings', []);
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
// image / milestones / upcoming nursery). Stored in the single 'ldrn_about'
// option row and read back into window.ldrnData.about. Same fail-safe as
// Site Settings: if CMB2 is inactive the hook never fires, no fatal, and
// ldrn_get_about() still returns clean defaults from plain get_option().
// ===================================================================

function ldrn_register_about_page() {
  $cmb = new_cmb2_box([
    'id'           => 'ldrn_about_box',
    'title'        => 'About us section',
    'object_types' => ['options-page'],
    'option_key'   => 'ldrn_about',
    'menu_title'   => 'About us',
    'icon_url'     => 'dashicons-groups',
    'position'     => 59,
    'capability'   => 'manage_ldrn_site_settings',
    'parent_slug'  => LDRN_CONTENT_MENU_SLUG,
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
add_action('cmb2_admin_init', 'ldrn_register_about_page');

/**
 * About us getter. Reads the 'ldrn_about' option directly (no CMB2 dependency)
 * and returns a shape ready for window.ldrnData.about. Empty fields come back as
 * '' / [] so the React side substitutes its own built-in defaults.
 */
function ldrn_get_about() {
  $o = get_option('ldrn_about', []);
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
    'imageAlt'   => $image_id ? trim((string) get_post_meta($image_id, '_wp_attachment_image_alt', true)) : '',
    'milestones' => $milestones,
    'upcoming'   => isset($o['upcoming']) ? (string) $o['upcoming'] : '',
  ];
}

// ===================================================================
// Notification recipient (shared by all website forms)
//
// ROUTING ACTIVE: this override is intentionally blank, so notifications go to
// each branch's inbox (see ldrn_notify_email + ldrn_branch_label), with the
// developer copied via AM_MONITOR_BCC below. Set this to an address again to
// force every form back to a single test inbox.
// ===================================================================
if (
  (!defined('LDRN_OPS_REPLACES_LEGACY') || !LDRN_OPS_REPLACES_LEGACY)
  && !defined('LDRN_TEST_NOTIFY_EMAIL')
) {
  define('LDRN_TEST_NOTIFY_EMAIL', '');
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
 * While LDRN_TEST_NOTIFY_EMAIL is set, it always wins (testing).
 */
function ldrn_notify_email($preferred = '') {
  if (defined('LDRN_TEST_NOTIFY_EMAIL') && LDRN_TEST_NOTIFY_EMAIL) {
    return LDRN_TEST_NOTIFY_EMAIL;
  }
  if (is_email($preferred)) {
    return $preferred;
  }
  $settings = ldrn_get_site_settings();
  if (!empty($settings['email'])) {
    return $settings['email'];
  }
  // Main enquiries inbox — the safe default so no-branch mail never lands on
  // the developer once the LDRN_TEST_NOTIFY_EMAIL override is removed at go-live.
  return 'info@littlerainbowsdaynursery.com';
}

/**
 * SMTP delivery — routes wp_mail through a real SMTP server so emails actually
 * leave the machine and reach the real inbox (in Local, wp_mail is otherwise
 * caught by the built-in Mailpit trap and never delivered).
 *
 * Activated only when the SMTP constants exist in wp-config.php:
 *   define('LDRN_SMTP_HOST', 'smtp.gmail.com');
 *   define('LDRN_SMTP_PORT', 587);
 *   define('LDRN_SMTP_USER', 'you@gmail.com');
 *   define('LDRN_SMTP_PASS', 'your-16-char-app-password');   // Gmail App Password
 *   define('LDRN_SMTP_FROM', 'you@gmail.com');               // optional
 *   define('LDRN_SMTP_SECURE', 'tls');                       // optional: tls|ssl
 * If they are absent, this is a no-op and Local's Mailpit keeps catching mail.
 */
function ldrn_configure_smtp($phpmailer) {
  if (!defined('LDRN_SMTP_HOST') || !LDRN_SMTP_HOST) {
    return; // no SMTP configured — leave default (Mailpit) transport alone
  }
  $phpmailer->isSMTP();
  $phpmailer->Host       = LDRN_SMTP_HOST;
  $phpmailer->Port       = defined('LDRN_SMTP_PORT') ? LDRN_SMTP_PORT : 587;
  $phpmailer->SMTPAuth   = true;
  $phpmailer->Username   = defined('LDRN_SMTP_USER') ? LDRN_SMTP_USER : '';
  $phpmailer->Password   = defined('LDRN_SMTP_PASS') ? LDRN_SMTP_PASS : '';
  $phpmailer->SMTPSecure = defined('LDRN_SMTP_SECURE') ? LDRN_SMTP_SECURE : 'tls';
  $phpmailer->Timeout    = 10; // seconds — don't let a slow SMTP server hang the form request
  $from = defined('LDRN_SMTP_FROM') ? LDRN_SMTP_FROM : (defined('LDRN_SMTP_USER') ? LDRN_SMTP_USER : '');
  if ($from) {
    $phpmailer->setFrom($from, 'Little Rainbows Day Nursery Website', false);
  }
}
add_action('phpmailer_init', 'ldrn_configure_smtp');

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
function ldrn_send_mail_async($to, $subject, $body, $headers = [], $attachments = []) {
  // Developer monitoring copy (BCC), added centrally so it covers every form.
  // Skipped when it would duplicate the primary recipient (e.g. while the
  // LDRN_TEST_NOTIFY_EMAIL override already routes everything to that address).
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
  set_transient('ldrn_mail_' . $token, [
    'to'          => $to,
    'subject'     => $subject,
    'body'        => $body,
    'headers'     => $headers,
    'attachments' => $attachments,
  ], 5 * MINUTE_IN_SECONDS);

  $dispatched = wp_remote_post(rest_url('ldrn/v1/dispatch-mail'), [
    'blocking'  => false,     // don't wait for the worker to finish
    'timeout'   => 0.01,
    'sslverify' => false,     // local self-signed cert on the loopback host
    'body'      => ['token' => $token],
  ]);

  // Safety net: if the loopback couldn't be initiated at all, send inline so a
  // notification is never silently lost (slower, but reliable).
  if (is_wp_error($dispatched)) {
    delete_transient('ldrn_mail_' . $token);
    wp_mail($to, $subject, $body, $headers, $attachments);
  }
}

/**
 * Background worker for ldrn_send_mail_async(): given a single-use token, look up
 * the stored payload and perform the actual (slow) SMTP send. Safe to expose
 * publicly — it only ever sends a payload the server itself generated and
 * stored; the random token is consumed on first use, so it can't be replayed or
 * used to send arbitrary mail.
 */
if (!defined('LDRN_OPS_REPLACES_LEGACY') || !LDRN_OPS_REPLACES_LEGACY) {
  add_action('rest_api_init', function () {
    register_rest_route('ldrn/v1', '/dispatch-mail', [
      'methods'             => 'POST',
      'callback'            => 'ldrn_dispatch_mail',
      'permission_callback' => '__return_true',
    ]);
  });
}

function ldrn_dispatch_mail(WP_REST_Request $request) {
  $token = sanitize_text_field($request->get_param('token'));
  if (!$token) {
    return rest_ensure_response(['ok' => false]);
  }
  $key     = 'ldrn_mail_' . $token;
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
 * wp-content/uploads/ldrn-mail-log/_FAILED.log so delivery problems are visible.
 */
function ldrn_log_mail_failure($wp_error) {
  $upload = wp_upload_dir();
  $dir    = trailingslashit($upload['basedir']) . 'ldrn-mail-log';
  if (!file_exists($dir)) {
    wp_mkdir_p($dir);
  }
  @file_put_contents(
    "{$dir}/_FAILED.log",
    current_time('c') . ' — ' . $wp_error->get_error_message() . "\n",
    FILE_APPEND
  );
}
if (!defined('LDRN_OPS_REPLACES_LEGACY') || !LDRN_OPS_REPLACES_LEGACY) {
  add_action('wp_mail_failed', 'ldrn_log_mail_failure');
}

/**
 * Mail logger — writes every outgoing wp_mail to
 * wp-content/uploads/ldrn-mail-log/ as a timestamped .html file, so the exact
 * subject/recipient/body can be reviewed even when local SMTP is not delivering.
 * Purely additive; does not affect real delivery.
 */
function ldrn_log_outgoing_mail($args) {
  // Local QA only. Production relies on immutable submission records and the
  // mail provider; duplicating customer data in a public uploads URL is unsafe.
  $host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
  if (!str_ends_with($host, '.local') && wp_get_environment_type() !== 'local') {
    return $args;
  }
  $dir = trailingslashit(dirname(ABSPATH, 2)) . 'private-logs/ldrn-mail';
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
add_filter('wp_mail', 'ldrn_log_outgoing_mail');

// Shared form abuse guard (per-IP rate limiting) — used by all form endpoints
require_once get_template_directory() . '/inc/ldrn-validation.php';
require_once get_template_directory() . '/inc/ldrn-media-library.php';
require_once get_template_directory() . '/inc/ldrn-content-contracts.php';
require_once get_template_directory() . '/inc/ldrn-admin.php';
require_once get_template_directory() . '/inc/ldrn-roles.php';

if (!defined('LDRN_OPS_REPLACES_LEGACY') || !LDRN_OPS_REPLACES_LEGACY) {
  require_once get_template_directory() . '/inc/ldrn-rate-limit.php';
  require_once get_template_directory() . '/inc/ldrn-private-files.php';
  require_once get_template_directory() . '/inc/ldrn-submissions.php';

  // Legacy form storage remains available only until the operations migration
  // is verified and the must-use plugin replacement flag is enabled.
  require_once get_template_directory() . '/inc/ldrn-careers-api.php';
  require_once get_template_directory() . '/inc/ldrn-enquiries-api.php';
}
