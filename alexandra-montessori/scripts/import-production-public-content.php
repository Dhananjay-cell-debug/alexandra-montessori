<?php
/** Import only the approved public-content manifest into production. */

$wp_root = rtrim((string) getenv('AM_WP_ROOT'), '/\\');
$manifest_path = $argv[1] ?? '';
if (!$wp_root || !is_file($wp_root . '/wp-load.php') || !is_file($manifest_path)) {
  fwrite(STDERR, "Set AM_WP_ROOT and pass the manifest path.\n");
  exit(2);
}
require $wp_root . '/wp-load.php';

$host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
if ($host !== 'alexandra.krildigital.com') {
  fwrite(STDERR, "Refusing unexpected production host: {$host}\n");
  exit(2);
}
$manifest = json_decode((string) file_get_contents($manifest_path), true);
if (!is_array($manifest) || ($manifest['schemaVersion'] ?? 0) !== 1 || count($manifest['records'] ?? []) !== 12) {
  fwrite(STDERR, "Invalid production content manifest.\n");
  exit(2);
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

foreach ($manifest['records'] as $record) {
  if (empty($record['image'])) continue;
  $source = get_template_directory() . '/dist/assets/' . ltrim($record['image']['asset'], '/');
  if (!is_readable($source)) {
    fwrite(STDERR, 'Missing staged theme asset: ' . $record['image']['asset'] . "\n");
    exit(1);
  }
}

function am_production_attachment_by_key($key) {
  $ids = get_posts([
    'post_type' => 'attachment',
    'post_status' => 'inherit',
    'posts_per_page' => 1,
    'fields' => 'ids',
    'meta_key' => '_am_seed_asset',
    'meta_value' => $key,
  ]);
  return $ids ? (int) $ids[0] : 0;
}

function am_production_import_image(array $image, $post_id) {
  $attachment_id = am_production_attachment_by_key($image['seedKey']);
  if (!$attachment_id) {
    $source = get_template_directory() . '/dist/assets/' . ltrim($image['asset'], '/');
    if (!is_readable($source)) throw new RuntimeException('Missing theme asset: ' . $image['asset']);
    $tmp = wp_tempnam($image['filename']);
    if (!$tmp || !copy($source, $tmp)) throw new RuntimeException('Could not prepare: ' . $image['filename']);
    $attachment_id = media_handle_sideload([
      'name' => $image['filename'],
      'tmp_name' => $tmp,
    ], $post_id, $image['title']);
    if (is_wp_error($attachment_id)) {
      @unlink($tmp);
      throw new RuntimeException($attachment_id->get_error_message());
    }
    update_post_meta($attachment_id, '_am_seed_asset', $image['seedKey']);
  }
  wp_update_post(['ID' => $attachment_id, 'post_parent' => $post_id, 'post_title' => $image['title']]);
  update_post_meta($attachment_id, '_wp_attachment_image_alt', $image['alt']);
  $term_id = function_exists('am_media_collection_term_id')
    ? am_media_collection_term_id($image['collection'])
    : 0;
  if ($term_id) wp_set_object_terms($attachment_id, [$term_id], AM_MEDIA_TAXONOMY, false);
  set_post_thumbnail($post_id, $attachment_id);
  return (int) $attachment_id;
}

$imported = [];
foreach ($manifest['records'] as $record) {
  $post_type = (string) $record['postType'];
  if (!in_array($post_type, ['post', 'am_event', 'am_testimonial'], true)) {
    throw new RuntimeException('Unexpected post type: ' . $post_type);
  }
  $existing = get_page_by_path($record['slug'], OBJECT, $post_type);
  $postarr = [
    'post_type' => $post_type,
    'post_status' => 'publish',
    'post_name' => $record['slug'],
    'post_title' => $record['title'],
    'post_excerpt' => $record['excerpt'],
    'post_content' => $record['content'],
    'post_date' => $record['date'],
    'post_date_gmt' => $record['dateGmt'],
    'menu_order' => (int) $record['menuOrder'],
  ];
  if ($existing) $postarr['ID'] = $existing->ID;
  $post_id = wp_insert_post(wp_slash($postarr), true);
  if (is_wp_error($post_id)) throw new RuntimeException($post_id->get_error_message());
  foreach ($record['meta'] as $meta_key => $value) update_post_meta($post_id, $meta_key, $value);
  if ($post_type === 'post') {
    $term_ids = [];
    foreach ($record['categories'] ?? [] as $category_name) {
      $term = term_exists($category_name, 'category');
      if (!$term) $term = wp_insert_term($category_name, 'category');
      if (!is_wp_error($term)) $term_ids[] = (int) (is_array($term) ? $term['term_id'] : $term);
    }
    if ($term_ids) wp_set_post_categories($post_id, $term_ids, false);
  }
  if (!empty($record['image'])) am_production_import_image($record['image'], $post_id);
  $imported[] = ['id' => (int) $post_id, 'type' => $post_type, 'slug' => $record['slug']];
}

$trashed = [];
foreach ([
  ['am_event', 'test', 'test'],
  ['am_event', 'test-open-day', 'Test Open Day'],
  ['am_job', 'childcare-assitance', 'childcare assitance'],
] as [$post_type, $slug, $expected_title]) {
  $post = get_page_by_path($slug, OBJECT, $post_type);
  if ($post && $post->post_title === $expected_title && $post->post_status !== 'trash') {
    if (!wp_trash_post($post->ID)) throw new RuntimeException('Could not trash ' . $slug);
    $trashed[] = (int) $post->ID;
  }
}

update_option('am_production_public_seed', [
  'schema_version' => 1,
  'completed_at' => current_time('mysql'),
  'imported' => count($imported),
  'trashed' => $trashed,
], false);
echo wp_json_encode(['imported' => $imported, 'trashed' => $trashed]) . "\n";
