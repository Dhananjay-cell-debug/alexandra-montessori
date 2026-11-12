<?php
/**
 * Brings live LDRN's Website Content sections to parity with Alexandra:
 * approved media into the Media Library (filed into collections), the
 * Hounslow record pointed at those attachments and completed from the
 * approved baseline, About us + Site Settings filled with the copy the site
 * already shows, and WordPress's own starter content moved to Trash.
 *
 * Idempotent: every imported attachment carries _ldrn_source_asset, so a
 * re-run reuses it. Run with: wp eval-file seed-content-parity.php
 * Nothing here invents content - every value comes from frontend/src/data/site.js.
 */
if (!defined('ABSPATH')) exit;
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$admins = get_users(['role' => 'administrator', 'number' => 1]);
if (!$admins) throw new RuntimeException('Administrator missing.');
wp_set_current_user($admins[0]->ID);

$dist = get_template_directory() . '/dist';
$hounslow = get_page_by_path('hounslow', OBJECT, 'ldrn_setting');
if (!$hounslow) throw new RuntimeException('Hounslow record missing.');

$alts = [
  'children-at-craft-table' => 'Children working together at a craft table',
  'child-waving-from-play-window' => 'A child waving from a play window',
  'child-drawing-with-crayons' => 'A child drawing with crayons',
];
$photos = [
  'children-at-craft-table.jpg', 'child-waving-from-play-window.jpg', 'child-drawing-with-crayons.jpg',
  'child-rolling-pin-and-cutter-dough.jpg', 'children-playing-together-at-table.jpg',
  'child-at-story-and-circle-time.jpg', 'children-building-wooden-train-track.jpg',
  'child-exploring-sensory-bag.jpg', 'child-hands-green-playdough.jpg', 'child-hands-on-picture-book-fox.jpg',
  'child-holding-fabric-book-bear-bananas.jpg', 'child-pointing-in-classroom.jpg',
  'child-pointing-shapes-and-numbers-wall.png', 'child-pressing-cutter-into-dough.jpg',
  'child-reading-fabric-book-bear-bee.jpg', 'child-reading-fabric-book-elephant-egg.jpg',
];
$items = [];
foreach ($photos as $f) $items[] = ['assets/photography/' . $f, 'hounslow', $hounslow->ID];
foreach (['hero-practitioner-and-child-playdough.png', 'home-hero-child-discovery-20261002.webp', 'home-testimonials-creative-play-20261002.webp'] as $f) {
  $items[] = ['assets/photography/' . $f, 'website-content', 0];
}
foreach (['logo-primary-navy-wordmark.png', 'logo-rainbow-wordmark.jpg', 'logo-rainbow-arc-mark.jpg', 'alexandra-montessori-badge.webp'] as $f) {
  $items[] = ['assets/logo/' . $f, 'global-brand', 0];
}
$items[] = ['assets/documents/ofsted-reports/ofsted-report-hounslow-2019.pdf', 'policies-reports', $hounslow->ID];
$items[] = ['assets/documents/application-forms/hounslow-nursery-application-form.docx', 'documents', 0];
$items[] = ['assets/documents/application-forms/staff-job-application-form.docx', 'documents', 0];

$report = ['imported' => [], 'reused' => [], 'missing' => []];
$by_name = [];
foreach ($items as [$rel, $collection, $parent]) {
  $source = $dist . '/' . $rel;
  $existing = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids',
    'meta_key' => '_ldrn_source_asset', 'meta_value' => $rel]);
  if ($existing) {
    $id = (int) $existing[0];
    $report['reused'][] = $rel;
  } else {
    if (!is_readable($source)) { $report['missing'][] = $rel; continue; }
    $tmp = wp_tempnam(basename($source));
    copy($source, $tmp);
    $stem = pathinfo($source, PATHINFO_FILENAME);
    $title = $alts[$stem] ?? ucfirst(str_replace('-', ' ', preg_replace('/-\d{8}$/', '', $stem)));
    $id = media_handle_sideload(['name' => basename($source), 'tmp_name' => $tmp], $parent, $title);
    if (is_wp_error($id)) { @unlink($tmp); throw new RuntimeException($rel . ': ' . $id->get_error_message()); }
    update_post_meta($id, '_ldrn_source_asset', $rel);
    if (wp_attachment_is_image($id)) update_post_meta($id, '_wp_attachment_image_alt', $title);
    $report['imported'][] = $rel;
  }
  $term = get_term_by('slug', $collection, LDRN_MEDIA_TAXONOMY);
  if ($term) wp_set_object_terms($id, [(int) $term->term_id], LDRN_MEDIA_TAXONOMY, false);
  $by_name[basename($rel)] = $id;
}

// Hounslow: same pictures, now real Media Library items (picker shows them).
$point = function ($url) use ($by_name) {
  $id = $by_name[basename((string) parse_url((string) $url, PHP_URL_PATH))] ?? 0;
  return $id ? [$id, wp_get_attachment_url($id)] : [0, (string) $url];
};
foreach (['_ldrn_setting_hero_image', '_ldrn_setting_welcome_image'] as $key) {
  [$id, $url] = $point(get_post_meta($hounslow->ID, $key, true));
  if ($id) { update_post_meta($hounslow->ID, $key, $url); update_post_meta($hounslow->ID, $key . '_id', $id); }
}
$gallery = get_post_meta($hounslow->ID, '_ldrn_setting_gallery', true);
if (is_array($gallery)) {
  $next = [];
  foreach ($gallery as $url) { [$id, $u] = $point($url); if ($id) $next[$id] = $u; }
  if (count($next) === count($gallery)) update_post_meta($hounslow->ID, '_ldrn_setting_gallery', $next);
}
// Approved baseline values the record was missing (site.js openSettings, ofsted, foodHygiene).
$fill = [
  '_ldrn_setting_area' => 'Hounslow',
  '_ldrn_setting_ofsted_url' => 'https://reports.ofsted.gov.uk/provider/16/EY545805',
  '_ldrn_setting_hygiene_rating' => '5',
  '_ldrn_setting_hygiene_date' => '1 April 2025',
  '_ldrn_setting_hygiene_authority' => 'London Borough of Hounslow',
  '_ldrn_setting_hygiene_url' => 'https://ratings.food.gov.uk/business/855345/little-rainbows-day-nursery-hounslow',
];
foreach ($fill as $key => $value) {
  if (trim((string) get_post_meta($hounslow->ID, $key, true)) === '') update_post_meta($hounslow->ID, $key, $value);
}

// Site Settings: exactly what the footer shows today, so nothing moves visually.
$site = get_option('ldrn_site_settings', []);
if (!is_array($site)) $site = [];
$site += [
  'phone' => '020 4619 6231',
  'email' => 'littlerainbowsdaynursery@yahoo.co.uk',
  'hours' => "Hounslow: Monday \u{2013} Friday, 8am \u{2013} 6pm",
];
update_option('ldrn_site_settings', $site);

// About us: the homepage section's current copy, image and pills.
$about = get_option('ldrn_about', []);
if (!is_array($about)) $about = [];
$waving = $by_name['child-waving-from-play-window.jpg'] ?? 0;
$about += [
  'heading' => 'About us',
  'body' => implode("\n\n", [
    'At Little Rainbows Day Nursery, we believe that children learn best when they feel happy, safe, and free to explore through play.',
    'Play is at the heart of everything we do. Through fun, age-appropriate activities and hands-on experiences, children are encouraged to use their imagination, build confidence, solve problems, make friends, and develop important social, emotional, physical, and thinking skills.',
    'We understand that every child is unique and develops at their own pace. Our caring team creates a warm, welcoming, and inclusive environment where children feel supported to explore, discover new interests, and enjoy their learning journey.',
    'Our aim is to help every child grow with confidence, curiosity, and joy while creating happy memories and meaningful friendships along the way.',
  ]),
  'milestones' => "Welcoming families since 2005\nLearning Through Play\nOfsted Good \u{2014} Hounslow",
];
if ($waving && empty($about['image'])) { $about['image'] = wp_get_attachment_url($waving); $about['image_id'] = $waving; }
update_option('ldrn_about', $about);

// WordPress's own starter content (never part of the LDRN site) -> Trash, recoverable.
$protected = [(int) get_option('page_on_front'), (int) get_option('page_for_posts')];
$trashed = [];
foreach ([['hello-world', 'post'], ['sample-page', 'page']] as [$slug, $type]) {
  $p = get_page_by_path($slug, OBJECT, $type);
  if ($p && !in_array((int) $p->ID, $protected, true) && $p->post_status !== 'trash') { wp_trash_post($p->ID); $trashed[] = $p->ID; }
}

if (function_exists('ldrn_sqlite_backup') && defined('DB_ENGINE') && DB_ENGINE === 'sqlite') ldrn_sqlite_backup();
echo wp_json_encode([
  'imported' => count($report['imported']), 'reused' => count($report['reused']), 'missing' => $report['missing'],
  'hounslow_gallery' => array_keys((array) get_post_meta($hounslow->ID, '_ldrn_setting_gallery', true)),
  'trashed' => $trashed, 'front_page' => $protected,
], JSON_PRETTY_PRINT) . "\n";
