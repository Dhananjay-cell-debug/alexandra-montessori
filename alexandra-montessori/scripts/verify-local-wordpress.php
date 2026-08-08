<?php
/**
 * Read-only architecture regression checks for the Local WordPress site.
 *
 * Usage:
 * php verify-local-wordpress.php "C:\path\to\wordpress\public"
 */

$wp_root = isset($argv[1]) ? rtrim($argv[1], "\\/") : '';
if (!$wp_root || !is_file($wp_root . DIRECTORY_SEPARATOR . 'wp-load.php')) {
  fwrite(STDERR, "Pass the Local WordPress public directory as argument 1.\n");
  exit(2);
}

require $wp_root . DIRECTORY_SEPARATOR . 'wp-load.php';

$host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
if (!str_ends_with($host, '.local')) {
  fwrite(STDERR, "Refusing to audit a non-Local site: {$host}\n");
  exit(2);
}

$failures = [];
$passes = 0;

function am_audit_check($condition, $label, $detail = '') {
  global $failures, $passes;
  if ($condition) {
    $passes++;
    echo "[PASS] {$label}\n";
    return;
  }
  $failures[] = $label . ($detail !== '' ? ": {$detail}" : '');
  echo "[FAIL] {$label}" . ($detail !== '' ? " - {$detail}" : '') . "\n";
}

$ops_replaces_legacy = defined('AM_OPS_REPLACES_LEGACY') && AM_OPS_REPLACES_LEGACY;
am_audit_check(
  $ops_replaces_legacy
    ? class_exists('AM_Ops_Repository')
    : function_exists('am_submission_config'),
  'Submission architecture is loaded'
);
am_audit_check(function_exists('am_validate_e164_phone'), 'Server phone validation is loaded');
am_audit_check(
  $ops_replaces_legacy
    ? class_exists('AM_Ops_Files')
    : function_exists('am_application_private_file_path'),
  'Private CV storage is loaded'
);

$settings = get_option('am_settings', []);
am_audit_check(!empty($settings['phone']), 'Global phone is configured');
am_audit_check(is_email($settings['email'] ?? ''), 'Global email is valid');
am_audit_check(!empty($settings['hours']), 'Global opening hours are configured');
$output_formats = apply_filters('image_editor_output_format', [], 'audit.jpg', 'image/jpeg');
am_audit_check(
  ($output_formats['image/jpeg'] ?? '') === 'image/webp',
  'Future JPEG responsive sizes are generated as WebP'
);
am_audit_check(
  apply_filters('wp_editor_set_quality', 82, 'image/webp', ['width' => 480, 'height' => 640]) === 76,
  'Future responsive image quality is bounded'
);

$expected_menus = [
  'post' => AM_CONTENT_MENU_SLUG,
  'am_event' => AM_CONTENT_MENU_SLUG,
  'am_testimonial' => AM_CONTENT_MENU_SLUG,
  'am_nursery' => AM_CONTENT_MENU_SLUG,
  'am_job' => AM_CONTENT_MENU_SLUG,
];
if (!$ops_replaces_legacy) {
  $expected_menus['am_enquiry'] = AM_SUBMISSIONS_MENU_SLUG;
  $expected_menus['am_availability'] = AM_SUBMISSIONS_MENU_SLUG;
  $expected_menus['am_application'] = AM_SUBMISSIONS_MENU_SLUG;
}
foreach ($expected_menus as $post_type => $menu) {
  $object = get_post_type_object($post_type);
  am_audit_check($object && $object->show_in_menu === $menu, "{$post_type} belongs to {$menu}");
}
am_audit_check(get_post_type_object('page')->show_in_menu === false, 'System Pages are hidden');
$media_taxonomy = get_taxonomy(AM_MEDIA_TAXONOMY);
am_audit_check($media_taxonomy && $media_taxonomy->show_ui === false, 'Media collection schema management is hidden');

if ($ops_replaces_legacy) {
  $schema_health = AM_Ops_Schema::health();
  am_audit_check(!empty($schema_health['healthy']), 'Operations custom-table schema is healthy');
  foreach (['am_enquiry', 'am_availability', 'am_application'] as $legacy_post_type) {
    am_audit_check(!get_post_type_object($legacy_post_type), "{$legacy_post_type} legacy editor is not registered");
  }
} else {
  foreach (array_keys(am_submission_config()) as $post_type) {
    am_audit_check(get_all_post_type_supports($post_type) === [], "{$post_type} has no misleading content editor");
  }
}

$role = get_role(AM_CONTENT_MANAGER_ROLE);
am_audit_check((bool) $role, 'Content Manager role exists');
if ($role) {
  foreach (['read', 'upload_files', 'edit_posts', 'edit_private_posts', AM_SETTINGS_CAPABILITY] as $capability) {
    am_audit_check(!empty($role->capabilities[$capability]), "Content Manager has {$capability}");
  }
  foreach (['activate_plugins', 'switch_themes', 'edit_users', 'edit_pages'] as $capability) {
    am_audit_check(empty($role->capabilities[$capability]), "Content Manager lacks {$capability}");
  }
}

$ready_nurseries = am_get_nurseries();
am_audit_check(count($ready_nurseries) >= 3, 'Ready nursery collection feeds the site');
am_audit_check(count(am_get_events()) >= 4, 'Event content feeds the site');
am_audit_check(count(am_get_blogs()) >= 4, 'Blog content feeds the site');
$testimonial_archive = function_exists('am_testimonial_query') ? am_testimonial_query(1, 24) : [];
am_audit_check(
  (int) ($testimonial_archive['total'] ?? 0) >= 1,
  'Ready Testimonials feed the public archive'
);
am_audit_check(
  count(am_get_testimonials()) === min(3, (int) ($testimonial_archive['total'] ?? 0)),
  'Homepage Testimonial preview is capped at three'
);
am_audit_check(!get_page_by_path('sample-page'), 'Sample Page is absent');

$ready_blog_posts = function_exists('am_ready_blog_posts') ? am_ready_blog_posts() : [];
$blog_archive = function_exists('am_get_blog_archive') ? am_get_blog_archive(0, 0, 1, 9) : [];
$featured_count = !empty($blog_archive['featured']) ? 1 : 0;
$archive_total = isset($blog_archive['total']) ? (int) $blog_archive['total'] : -1;
am_audit_check(
  count($ready_blog_posts) === $featured_count + $archive_total,
  'Blog ready count equals main article plus archive total',
  'ready: ' . count($ready_blog_posts) . ", main: {$featured_count}, archive: {$archive_total}"
);

$ready_featured_choices = array_values(array_filter($ready_blog_posts, function ($post) {
  return am_blog_is_featured_choice($post->ID);
}));
am_audit_check(
  count($ready_featured_choices) <= 1,
  'At most one website-ready Blog is chosen as the main article',
  'chosen ready posts: ' . wp_json_encode(array_map(function ($post) {
    return (int) $post->ID;
  }, $ready_featured_choices))
);
$main_blog_id = am_featured_blog_post_id($ready_blog_posts);
$main_blog = $main_blog_id ? get_post($main_blog_id) : null;
am_audit_check(
  (!$ready_blog_posts && empty($blog_archive['featured']))
    || ($main_blog && ($blog_archive['featured']['slug'] ?? '') === $main_blog->post_name),
  'The selected or automatic main Blog matches the large public article',
  'main post ID: ' . $main_blog_id . ', API slug: ' . ($blog_archive['featured']['slug'] ?? '')
);

$archive_per_page = isset($blog_archive['perPage']) ? (int) $blog_archive['perPage'] : 0;
$first_page_items = isset($blog_archive['items']) && is_array($blog_archive['items'])
  ? $blog_archive['items']
  : [];
am_audit_check(
  $archive_per_page === 9 && count($first_page_items) <= 9,
  'Blog archive is capped at nine cards per page',
  "perPage: {$archive_per_page}, returned: " . count($first_page_items)
);

$expected_blog_slugs = array_map(function ($post) {
  return (string) $post->post_name;
}, $ready_blog_posts);
$archive_blog_slugs = [];
if (!empty($blog_archive['featured']['slug'])) {
  $archive_blog_slugs[] = (string) $blog_archive['featured']['slug'];
}
$archive_pages = max(1, isset($blog_archive['totalPages']) ? (int) $blog_archive['totalPages'] : 1);
for ($archive_page = 1; $archive_page <= $archive_pages; $archive_page++) {
  $page_result = am_get_blog_archive(0, 0, $archive_page, 9);
  foreach ($page_result['items'] ?? [] as $item) {
    if (!empty($item['slug'])) $archive_blog_slugs[] = (string) $item['slug'];
  }
}
$unique_archive_blog_slugs = array_values(array_unique($archive_blog_slugs));
$sorted_expected_blog_slugs = $expected_blog_slugs;
$sorted_archive_blog_slugs = $unique_archive_blog_slugs;
sort($sorted_expected_blog_slugs, SORT_STRING);
sort($sorted_archive_blog_slugs, SORT_STRING);
am_audit_check(
  count($archive_blog_slugs) === count($unique_archive_blog_slugs)
    && $sorted_expected_blog_slugs === $sorted_archive_blog_slugs,
  'Every title/body-ready Blog appears exactly once across main and archive pages',
  'expected: ' . wp_json_encode($sorted_expected_blog_slugs)
    . ', returned: ' . wp_json_encode($sorted_archive_blog_slugs)
);

$blog_fallback_url = get_template_directory_uri() . '/dist/assets/organisation/classroom-main.webp';
$blog_fallback_path = get_template_directory() . '/dist/assets/organisation/classroom-main.webp';
$missing_image_posts = array_values(array_filter($ready_blog_posts, function ($post) {
  return !has_post_thumbnail($post->ID);
}));
$missing_image_posts_use_fallback = true;
foreach ($missing_image_posts as $post) {
  $card = am_prepare_blog_card($post);
  if (($card['image'] ?? '') !== $blog_fallback_url) {
    $missing_image_posts_use_fallback = false;
    break;
  }
}
am_audit_check(
  is_file($blog_fallback_path) && $missing_image_posts_use_fallback,
  'Ready Blogs without featured images receive the controlled fallback image',
  'missing-image posts: ' . count($missing_image_posts) . ", fallback: {$blog_fallback_path}"
);

$collection_minimums = [
  'website-content' => 23,
  'hounslow' => 5,
  'heston' => 5,
  'hammersmith' => 5,
  'events' => 4,
  'blog' => 4,
  'fee-sheets' => 3,
];
foreach ($collection_minimums as $slug => $minimum) {
  $term = get_term_by('slug', $slug, AM_MEDIA_TAXONOMY);
  $query = $term ? new WP_Query([
    'post_type' => 'attachment',
    'post_status' => 'inherit',
    'posts_per_page' => 1,
    'tax_query' => [[
      'taxonomy' => AM_MEDIA_TAXONOMY,
      'field' => 'term_id',
      'terms' => [$term->term_id],
      'include_children' => true,
    ]],
  ]) : null;
  $count = $query ? (int) $query->found_posts : 0;
  am_audit_check($count >= $minimum, "Media collection {$slug} returns assets", "expected >= {$minimum}, found {$count}");
}

$attachments = get_posts([
  'post_type' => 'attachment',
  'post_status' => 'inherit',
  'numberposts' => -1,
  'fields' => 'ids',
]);
$bad_assignments = 0;
foreach ($attachments as $attachment_id) {
  $terms = wp_get_object_terms($attachment_id, AM_MEDIA_TAXONOMY, ['fields' => 'ids']);
  if (is_wp_error($terms) || count($terms) !== 1) $bad_assignments++;
}
am_audit_check($bad_assignments === 0, 'Every public Media item has exactly one collection', "bad assignments: {$bad_assignments}");

$submission_totals = [];
if ($ops_replaces_legacy) {
  global $wpdb;
  $rows = $wpdb->get_results(
    'SELECT type, COUNT(*) AS total FROM '
      . AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS)
      . ' GROUP BY type ORDER BY type',
    ARRAY_A
  );
  foreach ($rows as $row) {
    $submission_totals[$row['type']] = (int) $row['total'];
  }
  am_audit_check(
    !str_starts_with(wp_normalize_path(AM_Ops_Files::directory()), wp_normalize_path(ABSPATH)),
    'Private CV directory is outside public web root'
  );
} else {
  foreach (am_submission_config() as $post_type => $config) {
    $posts = get_posts([
      'post_type' => $post_type,
      'post_status' => 'private',
      'numberposts' => -1,
    ]);
    $submission_totals[$post_type] = count($posts);
    foreach ($posts as $post) {
      $email = get_post_meta($post->ID, $config['email_key'], true);
      $status = get_post_meta($post->ID, $config['status_key'], true) ?: 'new';
      am_audit_check(is_email($email), "{$post_type} #{$post->ID} has a valid immutable email");
      am_audit_check(isset($config['statuses'][$status]), "{$post_type} #{$post->ID} has a valid workflow status");

      $phone_key = str_replace('_status', '_phone', $config['status_key']);
      $phone = get_post_meta($post->ID, $phone_key, true);
      if ($phone !== '') {
        am_audit_check(!is_wp_error(am_validate_e164_phone($phone, true)), "{$post_type} #{$post->ID} phone is E.164");
      }

      foreach (am_submission_communications($post->ID) as $entry) {
        am_audit_check(($entry['recipient'] ?? '') === $email, "{$post_type} #{$post->ID} reply recipient is locked");
        am_audit_check(in_array($entry['result'] ?? '', ['sent', 'failed'], true), "{$post_type} #{$post->ID} reply result is auditable");
      }

      if ($post_type === 'am_application') {
        $path = am_application_private_file_path($post->ID);
        am_audit_check($path && is_file($path), "Application #{$post->ID} CV exists");
        am_audit_check($path && !str_starts_with(wp_normalize_path($path), wp_normalize_path(ABSPATH)), "Application #{$post->ID} CV is outside public web root");
        $ext = strtolower(pathinfo($path ?: '', PATHINFO_EXTENSION));
        am_audit_check($path && am_private_application_signature_is_valid($path, $ext), "Application #{$post->ID} CV signature matches its file type");
      }
    }
  }
}

$public_mail_log = trailingslashit(WP_CONTENT_DIR) . 'uploads/am-mail-log';
am_audit_check(!is_dir($public_mail_log), 'No form email logs remain under public uploads');
if (!$ops_replaces_legacy) {
  am_audit_check(!str_starts_with(wp_normalize_path(am_private_application_dir()), wp_normalize_path(ABSPATH)), 'Private CV directory is outside public web root');
}
am_audit_check(defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT, 'WordPress dashboard PHP file editing is disabled');

am_audit_check(!is_wp_error(am_validate_e164_phone('+442046183477', true)), 'UK E.164 phone is accepted');
am_audit_check(!is_wp_error(am_validate_e164_phone('+919876543210', true)), 'India E.164 phone is accepted');
am_audit_check(is_wp_error(am_validate_e164_phone('02046183477', true)), 'National-only phone is rejected by the API');
$csv_formula = $ops_replaces_legacy
  ? AM_Ops_Admin::csv_cell('=HYPERLINK("bad")')
  : am_csv_safe('=HYPERLINK("bad")');
am_audit_check($csv_formula[0] === "'", 'CSV formula injection is neutralized');

echo "\nSubmission totals: " . wp_json_encode($submission_totals) . "\n";
echo "Passed: {$passes}; Failed: " . count($failures) . "\n";
if ($failures) {
  echo "Failures:\n- " . implode("\n- ", $failures) . "\n";
  exit(1);
}
exit(0);
