<?php
/**
 * Create or remove unmistakable Local-only Blog records for pagination tests.
 *
 * Usage:
 * php verify-blog-pagination-volume.php <wp-root> insert
 * php verify-blog-pagination-volume.php <wp-root> delete <comma-separated-ids>
 */

$wp_root = rtrim((string) getenv('AM_WP_ROOT'), "\\/");
foreach (array_slice($argv, 1) as $argument) {
  if ($wp_root) break;
  $candidate = rtrim((string) $argument, "\\/");
  if (is_file($candidate . DIRECTORY_SEPARATOR . 'wp-load.php')) {
    $wp_root = $candidate;
    break;
  }
}
$action = (string) getenv('AM_PAGINATION_ACTION');
if (!in_array($action, ['insert', 'delete'], true)) {
  $action = in_array('insert', $argv, true)
    ? 'insert'
    : (in_array('delete', $argv, true) ? 'delete' : '');
}
if (!$wp_root || !is_file($wp_root . DIRECTORY_SEPARATOR . 'wp-load.php')) {
  fwrite(STDERR, "Pass the Local WordPress public directory as argument 1.\n");
  exit(2);
}

require $wp_root . DIRECTORY_SEPARATOR . 'wp-load.php';

$host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
if (!str_ends_with($host, '.local')) {
  fwrite(STDERR, "Refusing to modify a non-Local site: {$host}\n");
  exit(2);
}

$prefix = 'AM-PHASE1-PAGINATION-20260716-174252';
$existing_test_ids = array_values(array_filter(get_posts([
  'post_type' => 'post',
  'post_status' => 'any',
  'posts_per_page' => -1,
  's' => $prefix,
  'fields' => 'ids',
]), function ($post_id) use ($prefix) {
  $post = get_post($post_id);
  return $post && str_starts_with($post->post_title, $prefix);
}));
if (!$action) $action = $existing_test_ids ? 'delete' : 'insert';

if ($action === 'insert') {
  if ($existing_test_ids) {
    fwrite(STDERR, "Refusing to insert while matching test records already exist.\n");
    exit(1);
  }

  $ids = [];
  for ($index = 1; $index <= 17; $index++) {
    $day = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
    $post_id = wp_insert_post([
      'post_type' => 'post',
      'post_status' => 'publish',
      'post_title' => $prefix . ' ' . $day,
      'post_content' => 'Temporary Local-only pagination verification article ' . $day . '.',
      'post_excerpt' => 'Temporary Local-only pagination verification.',
      'post_date' => '2024-01-' . $day . ' 09:00:00',
      'post_date_gmt' => '2024-01-' . $day . ' 09:00:00',
    ], true);
    if (is_wp_error($post_id)) {
      fwrite(STDERR, $post_id->get_error_message() . "\n");
      exit(1);
    }
    $ids[] = (int) $post_id;
  }
  echo wp_json_encode($ids) . "\n";
  exit(0);
}

if ($action === 'delete') {
  $id_list = (string) getenv('AM_PAGINATION_IDS');
  foreach ($argv as $argument) {
    if (preg_match('/^\d+(?:,\d+)*$/', (string) $argument)) {
      $id_list = (string) $argument;
      break;
    }
  }
  $ids = $id_list
    ? array_values(array_filter(array_map('intval', explode(',', $id_list))))
    : array_map('intval', $existing_test_ids);
  if (!$ids) {
    fwrite(STDERR, "Pass the recorded test IDs as argument 3.\n");
    exit(2);
  }

  $deleted = [];
  foreach ($ids as $post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'post' || !str_starts_with($post->post_title, $prefix)) {
      fwrite(STDERR, "Refusing unexpected record {$post_id}.\n");
      exit(1);
    }
    if (!wp_delete_post($post_id, true)) {
      fwrite(STDERR, "Could not delete test record {$post_id}.\n");
      exit(1);
    }
    $deleted[] = $post_id;
  }
  echo wp_json_encode($deleted) . "\n";
  exit(0);
}

fwrite(STDERR, "Choose insert or delete.\n");
exit(2);
