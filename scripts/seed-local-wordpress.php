<?php
/**
 * One-off, rerunnable Local content migration for Alexandra Montessori.
 *
 * Run with Local's PHP CLI from the WordPress public directory. This script is
 * deliberately not loaded by the theme and refuses to run outside *.local.
 */

$wp_root = getenv('AM_WP_ROOT') ?: 'C:/Users/Dhananjay/Local Sites/alexandra-montessori/app/public';
require rtrim($wp_root, '/\\') . '/wp-load.php';

if (!str_ends_with((string) wp_parse_url(home_url(), PHP_URL_HOST), '.local')) {
  fwrite(STDERR, "Refusing to seed a non-Local WordPress site.\n");
  exit(1);
}
if (!current_user_can('manage_options') && php_sapi_name() !== 'cli') {
  fwrite(STDERR, "Administrator access is required.\n");
  exit(1);
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$workspace = dirname(__DIR__);
$react_root = $workspace . '/alexandra-montessori';
$changes = [];

function am_seed_log($message) {
  global $changes;
  $changes[] = $message;
  echo $message . "\n";
}

function am_seed_post($post_type, $slug, array $data) {
  $existing = get_page_by_path($slug, OBJECT, $post_type);
  $postarr = array_merge($data, [
    'post_type' => $post_type,
    'post_name' => $slug,
  ]);
  if ($existing) {
    $postarr['ID'] = $existing->ID;
  }
  $post_id = wp_insert_post(wp_slash($postarr), true);
  if (is_wp_error($post_id)) {
    throw new RuntimeException($post_id->get_error_message());
  }
  return (int) $post_id;
}

function am_seed_attachment_by_key($key) {
  $ids = get_posts([
    'post_type'      => 'attachment',
    'post_status'    => 'inherit',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'meta_key'       => '_am_seed_asset',
    'meta_value'     => $key,
  ]);
  return $ids ? (int) $ids[0] : 0;
}

function am_seed_import_local_asset($source, $filename, $title, $alt, $parent_id, $collection_slug, $key) {
  $existing = am_seed_attachment_by_key($key);
  if ($existing && get_post($existing)) {
    wp_update_post(['ID' => $existing, 'post_parent' => $parent_id, 'post_title' => $title]);
    update_post_meta($existing, '_wp_attachment_image_alt', $alt);
    $term_id = am_media_collection_term_id($collection_slug);
    if ($term_id) wp_set_object_terms($existing, [$term_id], AM_MEDIA_TAXONOMY, false);
    return $existing;
  }

  if (!is_readable($source)) {
    throw new RuntimeException('Missing seed asset: ' . $source);
  }
  $tmp = wp_tempnam($filename);
  if (!$tmp || !copy($source, $tmp)) {
    throw new RuntimeException('Could not prepare seed asset: ' . $filename);
  }
  $attachment_id = media_handle_sideload([
    'name'     => $filename,
    'tmp_name' => $tmp,
  ], $parent_id, $title);
  if (is_wp_error($attachment_id)) {
    @unlink($tmp);
    throw new RuntimeException($attachment_id->get_error_message());
  }

  update_post_meta($attachment_id, '_am_seed_asset', $key);
  update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
  $term_id = am_media_collection_term_id($collection_slug);
  if ($term_id) wp_set_object_terms($attachment_id, [$term_id], AM_MEDIA_TAXONOMY, false);
  am_seed_log("Imported media: {$filename}");
  return (int) $attachment_id;
}

function am_seed_import_remote_document($url, $filename, $title, $parent_id, $collection_slug, $key) {
  $existing = am_seed_attachment_by_key($key);
  if ($existing && get_post($existing)) return $existing;

  $tmp = download_url($url, 30);
  if (is_wp_error($tmp)) {
    am_seed_log("Remote document kept as URL (download unavailable): {$filename}");
    return 0;
  }
  $attachment_id = media_handle_sideload([
    'name'     => $filename,
    'tmp_name' => $tmp,
  ], $parent_id, $title);
  if (is_wp_error($attachment_id)) {
    @unlink($tmp);
    am_seed_log("Remote document kept as URL (import failed): {$filename}");
    return 0;
  }
  update_post_meta($attachment_id, '_am_seed_asset', $key);
  $term_id = am_media_collection_term_id($collection_slug);
  if ($term_id) wp_set_object_terms($attachment_id, [$term_id], AM_MEDIA_TAXONOMY, false);
  am_seed_log("Imported document: {$filename}");
  return (int) $attachment_id;
}

function am_seed_set_file_meta($post_id, $meta_key, $attachment_id) {
  if (!$attachment_id) return;
  update_post_meta($post_id, $meta_key . '_id', $attachment_id);
  update_post_meta($post_id, $meta_key, wp_get_attachment_url($attachment_id));
}

// 1. Remove confirmed WordPress defaults and local-only test data.
foreach ([1, 2, 3, 62] as $post_id) {
  if (get_post($post_id)) {
    wp_delete_post($post_id, true);
    am_seed_log("Deleted default/test post #{$post_id}");
  }
}
foreach (['test-open-day', 'xyz', 'test'] as $slug) {
  $post = get_page_by_path($slug, OBJECT, 'am_event');
  if ($post) {
    wp_delete_post($post->ID, true);
    am_seed_log("Deleted test event: {$slug}");
  }
}
foreach (['am_application', 'am_enquiry', 'am_availability'] as $post_type) {
  $ids = get_posts(['post_type' => $post_type, 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids']);
  foreach ($ids as $post_id) {
    foreach (get_children(['post_parent' => $post_id, 'post_type' => 'attachment', 'fields' => 'ids']) as $attachment_id) {
      wp_delete_attachment($attachment_id, true);
    }
    wp_delete_post($post_id, true);
  }
  if ($ids) am_seed_log('Cleared local test records: ' . $post_type . ' (' . count($ids) . ')');
}

// Unapproved placeholder vacancies remain available to rewrite but cannot appear live.
$job_ids = get_posts([
  'post_type' => 'am_job',
  'post_status' => ['publish', 'draft', 'pending', 'private', 'trash'],
  'posts_per_page' => -1,
  'fields' => 'ids',
]);
foreach ($job_ids as $post_id) {
  if (get_post_status($post_id) === 'trash') {
    wp_delete_post($post_id, true);
    continue;
  }
  update_post_meta($post_id, '_am_job_status', 'closed');
  wp_update_post(['ID' => $post_id, 'post_status' => 'draft']);
}
if ($job_ids) am_seed_log('Closed placeholder vacancies pending client-approved descriptions.');

// 2. Seed the three nursery records from the current approved frontend facts.
$nurseries = [
  'hounslow' => [
    'title' => 'Hounslow', 'area' => 'Hounslow',
    'hero_tagline' => 'Quality childcare in Hounslow', 'subheading' => 'Montessori Childcare Hounslow',
    'address' => 'Ved Court, Alexandra Road, Hounslow', 'postcode' => 'TW3 1LS',
    'phone' => '0208 001 5165', 'email' => 'info@alexandramontessori.co.uk',
    'hours' => 'Mon-Fri, 8am-6pm', 'age_range' => '6 months to 5 years',
    'short' => 'Montessori-inspired practice with a caring, family feel at the heart of Hounslow.',
    'welcome' => 'Our Hounslow nursery blends Montessori-inspired practice with a caring, family feel. Natural materials and consistent key-person care help children grow in a safe, well-prepared setting.',
    'fee_url' => 'https://alexandramontessori.co.uk/wp-content/uploads/2026/04/Alexandra_Montessori_Hounslow_Fees.pdf',
    'fee_name' => 'alexandra-montessori-hounslow-fees.pdf',
    'ofsted_rating' => 'Good', 'ofsted_url' => 'https://reports.ofsted.gov.uk/provider/16/2546985', 'ofsted_label' => 'Official Ofsted report',
    'hygiene_rating' => '4', 'hygiene_date' => '6 October 2025', 'hygiene_authority' => 'London Borough of Hounslow',
    'hygiene_url' => 'https://ratings.food.gov.uk/business/1250950/alexandra-montessori-hounslow',
    'images' => [
      'hero' => ['organisation/classroom-main.webp', 'hounslow-hero-classroom.webp', 'Hounslow nursery classroom', 'A prepared Montessori classroom at Alexandra Montessori Hounslow'],
      'welcome' => ['organisation/teacher-hug.webp', 'hounslow-welcome-team.webp', 'Hounslow nursery welcome', 'A caring practitioner welcoming a child at Alexandra Montessori Hounslow'],
      'gallery' => [
        ['organisation/painting-close.webp', 'hounslow-gallery-painting.webp', 'Creative painting at Hounslow', 'A child painting at Alexandra Montessori Hounslow'],
        ['organisation/water-pouring.webp', 'hounslow-gallery-practical-life.webp', 'Practical life activity at Hounslow', 'A child practising a Montessori pouring activity at Hounslow'],
        ['organisation/hounslow-gallery-replacement.png', 'hounslow-gallery-classroom.png', 'Learning at Hounslow', 'Children learning together at Alexandra Montessori Hounslow'],
      ],
    ],
  ],
  'heston' => [
    'title' => 'Heston', 'area' => 'Hounslow',
    'hero_tagline' => 'Quality childcare in Heston', 'subheading' => 'Montessori Childcare Heston',
    'address' => '36 Springwell Road, Hounslow', 'postcode' => 'TW5 9EJ',
    'phone' => '0203 627 6707', 'email' => 'heston@alexandramontessori.co.uk',
    'hours' => 'Mon-Fri, 8am-6pm', 'age_range' => '6 months to 5 years',
    'short' => 'A warm, welcoming home for early learners, with spacious studios and a secure garden.',
    'welcome' => 'Our Heston nursery is a warm, welcoming home for early learners. Thoughtfully prepared environments, home-cooked meals and a secure garden help every child build confidence and independence at their own pace.',
    'fee_url' => 'https://alexandramontessori.co.uk/wp-content/uploads/2026/04/Alexandra_Montessori_Heston_Fees.pdf',
    'fee_name' => 'alexandra-montessori-heston-fees.pdf',
    'ofsted_rating' => 'Pending', 'ofsted_url' => 'https://reports.ofsted.gov.uk/provider/16/2814844', 'ofsted_label' => 'No published report yet',
    'hygiene_rating' => '5', 'hygiene_date' => '20 January 2025', 'hygiene_authority' => 'London Borough of Hounslow',
    'hygiene_url' => 'https://ratings.food.gov.uk/business/548392/alexandra-montessori-heston',
    'images' => [
      'hero' => ['organisation/friends-two.webp', 'heston-hero-children.webp', 'Heston nursery children', 'Children learning together at Alexandra Montessori Heston'],
      'welcome' => ['organisation/heston-welcome-replacement.png', 'heston-welcome-classroom.png', 'Heston nursery welcome', 'A welcoming learning environment at Alexandra Montessori Heston'],
      'gallery' => [
        ['organisation/sensory-box.webp', 'heston-gallery-sensory.webp', 'Sensory learning at Heston', 'A sensory learning activity at Alexandra Montessori Heston'],
        ['organisation/toddler-smile.webp', 'heston-gallery-toddler.webp', 'A happy child at Heston', 'A happy child at Alexandra Montessori Heston'],
        ['organisation/materials-shelf.webp', 'heston-gallery-materials.webp', 'Montessori materials at Heston', 'Prepared Montessori learning materials at Heston'],
      ],
    ],
  ],
  'hammersmith' => [
    'title' => 'Hammersmith', 'area' => 'Ravenscourt',
    'hero_tagline' => 'Quality childcare in Hammersmith', 'subheading' => 'Montessori Childcare Ravenscourt',
    'address' => 'Dalling Road, London', 'postcode' => 'W6 0EU',
    'phone' => '0204 618 3477', 'email' => 'hammersmith@alexandramontessori.co.uk',
    'hours' => 'Mon-Fri, 8am-6pm', 'age_range' => '12 months to 5 years',
    'short' => 'Montessori-inspired care moments from Ravenscourt Park, with bright, natural-light studios.',
    'welcome' => 'Just a short walk from Ravenscourt Park, our Hammersmith nursery offers calm, prepared Montessori environments and natural resources that support independence, confidence and purposeful early learning.',
    'fee_url' => 'https://alexandramontessori.co.uk/wp-content/uploads/2026/05/Hammersmith-AM-Fees-2025.pdf',
    'fee_name' => 'alexandra-montessori-hammersmith-fees.pdf',
    'ofsted_rating' => 'Good', 'ofsted_url' => 'https://reports.ofsted.gov.uk/provider/16/2498843', 'ofsted_label' => 'Official Ofsted report',
    'hygiene_rating' => 'Check current local authority record', 'hygiene_date' => 'Awaiting public FHRS listing',
    'hygiene_authority' => 'London Borough of Hammersmith & Fulham', 'hygiene_url' => 'https://ratings.food.gov.uk/',
    'images' => [
      'hero' => ['organisation/classroom-calm.webp', 'hammersmith-hero-classroom.webp', 'Hammersmith nursery classroom', 'A calm Montessori classroom at Alexandra Montessori Hammersmith'],
      'welcome' => ['organisation/shape-work.webp', 'hammersmith-welcome-learning.webp', 'Hammersmith nursery welcome', 'A child learning with Montessori materials at Hammersmith'],
      'gallery' => [
        ['organisation/tree-work.webp', 'hammersmith-gallery-tree-work.webp', 'Nature learning at Hammersmith', 'A child exploring nature-based learning at Hammersmith'],
        ['organisation/practical-kitchen.webp', 'hammersmith-gallery-practical-life.webp', 'Practical life at Hammersmith', 'A child practising practical life skills at Hammersmith'],
        ['organisation/movement-play.webp', 'hammersmith-gallery-movement.webp', 'Movement play at Hammersmith', 'Children enjoying movement play at Hammersmith'],
      ],
    ],
  ],
];

$nursery_order = ['hounslow', 'heston', 'hammersmith'];
foreach ($nursery_order as $order => $slug) {
  $item = $nurseries[$slug];
  $post_id = am_seed_post('am_nursery', $slug, [
    'post_title' => $item['title'], 'post_status' => 'publish', 'menu_order' => $order,
  ]);
  $meta_map = [
    '_am_nursery_area' => 'area', '_am_nursery_hero_tagline' => 'hero_tagline',
    '_am_nursery_subheading' => 'subheading', '_am_nursery_address' => 'address',
    '_am_nursery_postcode' => 'postcode', '_am_nursery_phone' => 'phone',
    '_am_nursery_email' => 'email', '_am_nursery_hours' => 'hours',
    '_am_nursery_age_range' => 'age_range', '_am_nursery_short' => 'short',
    '_am_nursery_welcome' => 'welcome', '_am_nursery_ofsted_rating' => 'ofsted_rating',
    '_am_nursery_ofsted_url' => 'ofsted_url', '_am_nursery_ofsted_label' => 'ofsted_label',
    '_am_nursery_hygiene_rating' => 'hygiene_rating', '_am_nursery_hygiene_date' => 'hygiene_date',
    '_am_nursery_hygiene_authority' => 'hygiene_authority', '_am_nursery_hygiene_url' => 'hygiene_url',
  ];
  foreach ($meta_map as $meta_key => $source_key) update_post_meta($post_id, $meta_key, $item[$source_key]);

  $existing_hero = (int) get_post_meta($post_id, '_am_nursery_hero_image_id', true);
  if ($slug === 'hammersmith' && $existing_hero && get_post($existing_hero)) {
    $hero_id = $existing_hero;
    update_post_meta($hero_id, '_wp_attachment_image_alt', 'Children at Alexandra Montessori Hammersmith');
    $term_id = am_media_collection_term_id('hammersmith');
    if ($term_id) wp_set_object_terms($hero_id, [$term_id], AM_MEDIA_TAXONOMY, false);
  } else {
    [$source, $filename, $title, $alt] = $item['images']['hero'];
    $hero_id = am_seed_import_local_asset("{$react_root}/public/assets/{$source}", $filename, $title, $alt, $post_id, $slug, "nursery-{$slug}-hero");
    am_seed_set_file_meta($post_id, '_am_nursery_hero_image', $hero_id);
  }

  [$source, $filename, $title, $alt] = $item['images']['welcome'];
  $welcome_id = am_seed_import_local_asset("{$react_root}/public/assets/{$source}", $filename, $title, $alt, $post_id, $slug, "nursery-{$slug}-welcome");
  am_seed_set_file_meta($post_id, '_am_nursery_welcome_image', $welcome_id);

  $gallery = [];
  foreach ($item['images']['gallery'] as $index => [$source, $filename, $title, $alt]) {
    $attachment_id = am_seed_import_local_asset("{$react_root}/public/assets/{$source}", $filename, $title, $alt, $post_id, $slug, "nursery-{$slug}-gallery-{$index}");
    $gallery[$attachment_id] = wp_get_attachment_url($attachment_id);
  }
  update_post_meta($post_id, '_am_nursery_gallery', $gallery);

  $fee_id = am_seed_import_remote_document($item['fee_url'], $item['fee_name'], $item['title'] . ' fee sheet', $post_id, 'fee-sheets', "nursery-{$slug}-fees");
  update_post_meta($post_id, '_am_nursery_fee_sheet_pdf', $fee_id ? wp_get_attachment_url($fee_id) : $item['fee_url']);
  if ($fee_id) update_post_meta($post_id, '_am_nursery_fee_sheet_pdf_id', $fee_id);
  am_seed_log("Seeded nursery: {$item['title']}");
}

// 3. Seed the four current website events.
$events = [
  [
    'slug' => 'open-morning-hammersmith', 'title' => 'Open morning at our Hammersmith nursery',
    'date' => '2026-07-05', 'time' => '9:30am - 11:30am', 'location' => 'Hammersmith, Ravenscourt',
    'excerpt' => 'Come and explore our Ravenscourt studios, meet the team and see the Montessori approach in action. Book your place to secure a spot.',
    'content' => '<p>Come and explore our Ravenscourt studios, meet the team and see the Montessori approach in action.</p><p>This is an opportunity to view the prepared environment and ask the nursery team questions about care, learning and settling in.</p>',
    'booking' => 'Please contact the Hammersmith team to confirm a place before attending.',
    'image' => 'organisation/painting-close.webp', 'filename' => 'event-hammersmith-open-morning.webp',
    'alt' => 'A child painting during a Montessori activity',
  ],
  [
    'slug' => 'stay-play-heston', 'title' => 'Stay & play taster session',
    'date' => '2026-07-12', 'time' => '10:00am - 11:00am', 'location' => 'Heston',
    'excerpt' => 'A relaxed morning for little ones and their grown-ups to play, explore and get a feel for nursery life.',
    'content' => '<p>A relaxed morning for little ones and their grown-ups to play, explore and get a feel for nursery life.</p><p>Families can meet the Heston team and experience a selection of age-appropriate Montessori activities together.</p>',
    'booking' => 'Please contact the Heston team to confirm availability before attending.',
    'image' => 'organisation/materials-shelf.webp', 'filename' => 'event-heston-stay-and-play.webp',
    'alt' => 'Prepared Montessori learning materials',
  ],
  [
    'slug' => 'summer-celebration-garden-party', 'title' => 'Summer celebration & garden party',
    'date' => '2026-07-19', 'time' => '11:00am - 1:00pm', 'location' => 'All nurseries',
    'excerpt' => 'Music, games and outdoor fun as we celebrate a wonderful year of growing, learning and discovery together.',
    'content' => '<p>Music, games and outdoor fun as we celebrate a wonderful year of growing, learning and discovery together.</p><p>Families should check with their nursery team for branch-specific arrangements and attendance details.</p>',
    'booking' => 'Please ask your nursery team to confirm the arrangements for your branch.',
    'image' => 'organisation/movement-play.webp', 'filename' => 'event-summer-garden-party.webp',
    'alt' => 'Children enjoying movement and outdoor play',
  ],
  [
    'slug' => 'settling-in-week-new-starters', 'title' => 'Settling-in week for new starters',
    'date' => '2026-09-01', 'time' => 'By appointment', 'location' => 'All nurseries',
    'excerpt' => 'Gentle, gradual settling sessions to help new children - and their families - feel at home before they start.',
    'content' => '<p>Gentle, gradual settling sessions help new children and their families feel at home before their start date.</p><p>Each nursery arranges settling around the child, so session times are confirmed directly with the branch team.</p>',
    'booking' => 'Settling sessions are by appointment with your nursery team.',
    'image' => 'organisation/friends-two.webp', 'filename' => 'event-settling-in-week.webp',
    'alt' => 'Two children learning together at nursery',
  ],
];
foreach ($events as $event) {
  $post_id = am_seed_post('am_event', $event['slug'], [
    'post_title' => $event['title'], 'post_status' => 'publish',
    'post_excerpt' => $event['excerpt'], 'post_content' => $event['content'],
  ]);
  update_post_meta($post_id, '_am_event_date', $event['date']);
  update_post_meta($post_id, '_am_event_time', $event['time']);
  update_post_meta($post_id, '_am_event_location', $event['location']);
  update_post_meta($post_id, '_am_event_booking_info', $event['booking']);
  $attachment_id = am_seed_import_local_asset(
    "{$react_root}/public/assets/{$event['image']}", $event['filename'], $event['title'],
    $event['alt'], $post_id, 'events', 'event-' . $event['slug']
  );
  set_post_thumbnail($post_id, $attachment_id);
  am_seed_log("Seeded event: {$event['title']}");
}

// 4. Seed the homepage testimonials.
$testimonials = [
  ['an-outstanding-nursery', 'An outstanding nursery', 'My son has been here since last year. He was only going initially for 15 hours a week, but on all the other days he would be asking non-stop to go back. He was so eager that he would even ask me during the school holidays. This just goes to show how good the nursery is and how caring the staff are. The children are really well looked after, and the team is genuinely focused on child development and stimulation.', 'Parent of a 3-year-old', 'Hammersmith'],
  ['highly-recommend', 'Highly recommend', "I can only highly recommend Alexandra Montessori, because I can't be grateful enough for how well my son is being looked after. He has even learned some Mandarin now and can say a few words. The care and attention from the whole team have made such a difference to him.", 'Parent of a 4-year-old', 'Heston'],
  ['a-calm-confident-start', 'A calm, confident start', "You can feel the calm the moment you step inside. The Montessori environment has done wonders for our son's confidence, and the practitioners always know where he is in his learning. He is happy, settled and growing more independent every week.", 'Parent of a 2-year-old', 'Hounslow'],
  ['the-team-feels-like-family', 'The team feels like family', 'Our daughter goes in happily every morning and comes home talking about what she has done. The team knows her well, and you can tell how much they care about every child individually. We feel lucky to have found them.', 'Parent of a 3-year-old', 'Ravenscourt'],
];
foreach ($testimonials as $order => [$slug, $title, $quote, $name, $location]) {
  $post_id = am_seed_post('am_testimonial', $slug, [
    'post_title' => $title, 'post_status' => 'publish', 'menu_order' => $order,
  ]);
  update_post_meta($post_id, '_am_testimonial_quote', $quote);
  update_post_meta($post_id, '_am_testimonial_name', $name);
  update_post_meta($post_id, '_am_testimonial_location', $location);
}
am_seed_log('Seeded testimonials: ' . count($testimonials));

// 5. Seed the generated official blog articles and local featured images.
$generated = file_get_contents($react_root . '/src/data/blogs.generated.js');
if (!preg_match('/export const blogs = (\[.*\]);\s*\n\s*export const blogBySlug/s', $generated, $match)) {
  throw new RuntimeException('Could not parse blogs.generated.js');
}
$blogs = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
$blog_images = [
  'independence-through-montessori-how-we-guide-our-toddlers-2' => ['blogs/independence-hounslow.png', 'blog-independence-hounslow.png'],
  'independence-through-montessori-how-we-guide-our-toddlers' => ['blogs/independence-toddlers.jpg', 'blog-independence-toddlers.jpg'],
  'why-montessori-is-powerful-for-2-year-olds-the-alexandra-approach' => ['blogs/montessori-two-year-olds.jpg', 'blog-montessori-two-year-olds.jpg'],
  'growing-minds-at-alexandra-montessori-hounslow' => ['blogs/growing-minds-hounslow.jpg', 'blog-growing-minds-hounslow.jpg'],
];
foreach ($blogs as $blog) {
  $date = str_replace('T', ' ', substr($blog['date'], 0, 19));
  $post_id = am_seed_post('post', $blog['slug'], [
    'post_title' => $blog['title'], 'post_status' => 'publish', 'post_content' => $blog['content'],
    'post_excerpt' => $blog['excerpt'], 'post_date' => $date, 'post_date_gmt' => get_gmt_from_date($date),
  ]);
  $category = term_exists($blog['category'], 'category');
  if (!$category) $category = wp_insert_term($blog['category'], 'category');
  if (!is_wp_error($category)) {
    $term_id = is_array($category) ? (int) $category['term_id'] : (int) $category;
    wp_set_post_categories($post_id, [$term_id], false);
  }
  [$source, $filename] = $blog_images[$blog['slug']];
  $attachment_id = am_seed_import_local_asset(
    "{$react_root}/public/assets/{$source}", $filename, $blog['title'],
    $blog['title'], $post_id, 'blog', 'blog-' . $blog['slug']
  );
  set_post_thumbnail($post_id, $attachment_id);
  update_post_meta($post_id, '_am_official_source_url', $blog['sourceUrl']);
  am_seed_log("Seeded blog: {$blog['title']}");
}

// 6. Replace the original setup placeholders with the approved site facts.
$settings = [
  'phone' => '0204 618 3477',
  'email' => 'info@alexandramontessori.co.uk',
  'hours' => 'Monday to Friday, 8:00am - 6:00pm',
  'address' => 'Alexandra Montessori nurseries in Hounslow, Heston and Hammersmith, London',
  'apply_url' => 'https://applyalways.com/display/G6o5jN8mK1m1GuxmpRN2',
  'socials' => [
    ['label' => 'Facebook', 'href' => 'https://www.facebook.com/alexandramontessoriltd/'],
    ['label' => 'Instagram', 'href' => 'https://www.instagram.com/alexandra_montessori?igsh=MTltNW8waTQ3aWM0ZQ=='],
    ['label' => 'X', 'href' => 'https://x.com/alexand96462858?s=21'],
    ['label' => 'LinkedIn', 'href' => 'https://www.linkedin.com/in/alexandra-montessori-89b92b320'],
  ],
];
update_option('am_settings', $settings, false);
am_seed_log('Completed global Site Settings.');

// Any remaining unattached legacy media is visible but explicitly queued.
$unfiled = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1, 'fields' => 'ids']);
foreach ($unfiled as $attachment_id) {
  $terms = wp_get_object_terms($attachment_id, AM_MEDIA_TAXONOMY, ['fields' => 'ids']);
  if (!is_wp_error($terms) && !$terms) am_auto_file_new_attachment($attachment_id);
}

update_option('am_local_content_seed', [
  'version' => 1,
  'completed_at' => current_time('mysql'),
  'changes' => count($changes),
], false);
am_seed_log('Local content migration complete.');
