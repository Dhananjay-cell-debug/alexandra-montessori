<?php
/** Export approved Local public content for the production-only importer. */

$wp_root = 'C:/Users/Dhananjay/Local Sites/alexandra-montessori/app/public';
require $wp_root . '/wp-load.php';

$host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
if (!str_ends_with($host, '.local')) {
  fwrite(STDERR, "Refusing to export from a non-Local site: {$host}\n");
  exit(2);
}

$specs = [
  'am_event' => [
    'meta' => ['_am_event_date', '_am_event_time', '_am_event_location', '_am_event_booking_info'],
    'items' => [
      'open-morning-hammersmith' => ['organisation/painting-close.webp', 'event-hammersmith-open-morning.webp'],
      'stay-play-heston' => ['organisation/materials-shelf.webp', 'event-heston-stay-and-play.webp'],
      'summer-celebration-garden-party' => ['organisation/movement-play.webp', 'event-summer-garden-party.webp'],
      'settling-in-week-new-starters' => ['organisation/friends-two.webp', 'event-settling-in-week.webp'],
    ],
    'collection' => 'events',
  ],
  'am_testimonial' => [
    'meta' => ['_am_testimonial_quote', '_am_testimonial_name', '_am_testimonial_location'],
    'items' => [
      'an-outstanding-nursery' => null,
      'highly-recommend' => null,
      'a-calm-confident-start' => null,
      'the-team-feels-like-family' => null,
    ],
  ],
  'post' => [
    'meta' => ['_am_official_source_url', '_am_blog_featured'],
    'items' => [
      'independence-through-montessori-how-we-guide-our-toddlers-2' => ['blogs/independence-hounslow.png', 'blog-independence-hounslow.png'],
      'independence-through-montessori-how-we-guide-our-toddlers' => ['blogs/independence-toddlers.jpg', 'blog-independence-toddlers.jpg'],
      'why-montessori-is-powerful-for-2-year-olds-the-alexandra-approach' => ['blogs/montessori-two-year-olds.jpg', 'blog-montessori-two-year-olds.jpg'],
      'growing-minds-at-alexandra-montessori-hounslow' => ['blogs/growing-minds-hounslow.jpg', 'blog-growing-minds-hounslow.jpg'],
    ],
    'collection' => 'blog',
  ],
];

$records = [];
foreach ($specs as $post_type => $spec) {
  foreach ($spec['items'] as $slug => $image_spec) {
    $post = get_page_by_path($slug, OBJECT, $post_type);
    if (!$post || $post->post_status !== 'publish') {
      fwrite(STDERR, "Missing approved {$post_type} record: {$slug}\n");
      exit(1);
    }

    $meta = [];
    foreach ($spec['meta'] as $meta_key) {
      $value = get_post_meta($post->ID, $meta_key, true);
      if ($value !== '') $meta[$meta_key] = $value;
    }
    $record = [
      'postType' => $post_type,
      'slug' => $post->post_name,
      'title' => $post->post_title,
      'excerpt' => $post->post_excerpt,
      'content' => $post->post_content,
      'date' => $post->post_date,
      'dateGmt' => $post->post_date_gmt,
      'menuOrder' => (int) $post->menu_order,
      'meta' => $meta,
    ];
    if ($post_type === 'post') {
      $record['categories'] = wp_get_post_categories($post->ID, ['fields' => 'names']);
    }
    if ($image_spec) {
      $thumbnail_id = get_post_thumbnail_id($post->ID);
      $record['image'] = [
        'asset' => $image_spec[0],
        'filename' => $image_spec[1],
        'title' => $post->post_title,
        'alt' => $thumbnail_id ? get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) : $post->post_title,
        'seedKey' => ($post_type === 'post' ? 'blog-' : 'event-') . $slug,
        'collection' => $spec['collection'],
      ];
    }
    $records[] = $record;
  }
}

$payload = [
  'schemaVersion' => 1,
  'sourceHost' => $host,
  'generatedAt' => gmdate('c'),
  'records' => $records,
];
$output = __DIR__ . '/production-public-content.json';
if (file_put_contents($output, wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) === false) {
  fwrite(STDERR, "Could not write {$output}\n");
  exit(1);
}
echo "Exported " . count($records) . " records to {$output}\n";

