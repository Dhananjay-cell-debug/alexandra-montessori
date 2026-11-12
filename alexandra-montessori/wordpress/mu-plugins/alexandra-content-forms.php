<?php
/**
 * Plugin Name: Alexandra Content Forms
 * Description: Simple create/edit forms (the LDRN ones) for Blog, Events, Testimonials, Jobs, Nurseries and About us in place of the WordPress editor. Site Settings keeps its own screen. Saves into the same fields the website reads.
 */

if (!defined('ABSPATH')) exit;
require_once __DIR__ . '/alexandra-content-forms/forms.php';

/** Published nursery names plus "All Nurseries", as select options. */
function am_cf_nursery_options() {
  $options = [];
  foreach (get_posts(['post_type' => 'am_nursery', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC']) as $p) {
    $name = html_entity_decode(get_the_title($p), ENT_QUOTES, 'UTF-8');
    $options[$name] = $name;
  }
  $options['All Nurseries'] = 'All Nurseries';
  return $options;
}

/** Editors only reach the sections allocated to them (inc/am-roles.php). */
function am_cf_access_check($type, $post_id) {
  if (!function_exists('am_access_admin_request_allowed')) return true;
  return $post_id
    ? am_access_admin_request_allowed('post.php', ['post' => $post_id])
    : am_access_admin_request_allowed('post-new.php', ['post_type' => $type]);
}

function am_cf_page_access_check($slug) {
  if (!function_exists('am_access_admin_request_allowed')) return true;
  return am_access_admin_request_allowed('admin.php', ['page' => $slug]);
}

add_action('after_setup_theme', function () {
  Content_Forms::boot([
    'brand' => ['primary' => '#506457', 'primary_dark' => '#34443a', 'soft' => '#f7faf5'],
    'hub' => ['label' => 'Website Content', 'url' => 'admin.php?page=am_content'],
    'access_check' => 'am_cf_access_check',
    'page_access_check' => 'am_cf_page_access_check',
    'pages' => [
      'am_about' => [
        'title' => 'About us', 'parent' => 'am_content', 'option' => 'am_about', 'capability' => 'manage_am_site_settings',
        'intro' => 'The About us section on the homepage. Anything left empty keeps the website\'s built-in text.',
        'fields' => [
          'heading' => ['type' => 'text', 'store' => 'opt:heading', 'label' => 'Heading', 'placeholder' => 'About us'],
          'body' => ['type' => 'textarea', 'store' => 'opt:body', 'label' => 'Text', 'rows' => 10, 'help' => 'Leave an empty line between paragraphs.'],
          'image' => ['type' => 'image', 'store' => 'opt:image', 'label' => 'Photo', 'help' => 'The round photo beside the text. A square photo looks best.'],
          'milestones' => ['type' => 'textarea', 'store' => 'opt:milestones', 'label' => 'Milestones', 'rows' => 4, 'help' => 'One per line, e.g. "2019 - Hounslow". Shown as small labels under the text.'],
          'upcoming' => ['type' => 'text', 'store' => 'opt:upcoming', 'label' => 'Coming soon', 'placeholder' => 'e.g. Coming soon - Ealing', 'help' => 'Shown as a highlighted label. Leave empty to hide it.'],
        ],
      ],
    ],
    'types' => [
      'am_nursery' => [
        'singular' => 'Nursery', 'plural' => 'Nurseries', 'route' => '/nurseries/%slug%',
        'fields' => [
          'h1' => ['type' => 'heading', 'label' => 'About the nursery'],
          'title' => ['type' => 'text', 'store' => 'title', 'label' => 'Nursery name', 'required' => true, 'placeholder' => 'e.g. Hammersmith'],
          'area' => ['type' => 'text', 'store' => 'meta:_am_nursery_area', 'label' => 'Area', 'half' => true],
          'age' => ['type' => 'text', 'store' => 'meta:_am_nursery_age_range', 'label' => 'Age range', 'required' => true, 'half' => true, 'placeholder' => '6 months to 5 years'],
          'tagline' => ['type' => 'text', 'store' => 'meta:_am_nursery_hero_tagline', 'label' => 'Tagline', 'half' => true],
          'subheading' => ['type' => 'text', 'store' => 'meta:_am_nursery_subheading', 'label' => 'Subheading', 'half' => true],
          'short' => ['type' => 'textarea', 'store' => 'meta:_am_nursery_short', 'label' => 'Short description', 'required' => true, 'limit' => 200, 'help' => 'Shown on the nursery card.'],
          'welcome' => ['type' => 'textarea', 'store' => 'meta:_am_nursery_welcome', 'label' => 'Welcome text', 'required' => true, 'rows' => 6, 'help' => 'Shown on the nursery\'s own page.'],
          'h2' => ['type' => 'heading', 'label' => 'Contact'],
          'address' => ['type' => 'text', 'store' => 'meta:_am_nursery_address', 'label' => 'Address', 'required' => true],
          'postcode' => ['type' => 'text', 'store' => 'meta:_am_nursery_postcode', 'label' => 'Postcode', 'half' => true],
          'hours' => ['type' => 'text', 'store' => 'meta:_am_nursery_hours', 'label' => 'Opening hours', 'required' => true, 'half' => true, 'placeholder' => 'Mon – Fri · 8am – 6pm'],
          'phone' => ['type' => 'text', 'store' => 'meta:_am_nursery_phone', 'label' => 'Phone', 'required' => true, 'half' => true],
          'email' => ['type' => 'email', 'store' => 'meta:_am_nursery_email', 'label' => 'Email', 'required' => true, 'half' => true],
          'h3' => ['type' => 'heading', 'label' => 'Photos and documents'],
          'hero' => ['type' => 'image', 'store' => 'meta:_am_nursery_hero_image', 'label' => 'Main photo', 'required' => true, 'half' => true, 'help' => 'Top of the nursery page and on its card.'],
          'welcome_image' => ['type' => 'image', 'store' => 'meta:_am_nursery_welcome_image', 'label' => 'Welcome photo', 'half' => true],
          'gallery' => ['type' => 'gallery', 'store' => 'meta:_am_nursery_gallery', 'label' => 'Gallery'],
          'fees' => ['type' => 'file', 'store' => 'meta:_am_nursery_fee_sheet_pdf', 'label' => 'Fee sheet (PDF)'],
          'h4' => ['type' => 'heading', 'label' => 'Ofsted and food hygiene'],
          'ofsted' => ['type' => 'select', 'store' => 'meta:_am_nursery_ofsted_rating', 'label' => 'Ofsted rating', 'half' => true, 'options' => ['Outstanding' => 'Outstanding', 'Good' => 'Good', 'Requires improvement' => 'Requires improvement', 'Inadequate' => 'Inadequate', 'Pending' => 'Pending - not yet inspected']],
          'ofsted_label' => ['type' => 'text', 'store' => 'meta:_am_nursery_ofsted_label', 'label' => 'Ofsted link text', 'half' => true, 'placeholder' => 'Official Ofsted report'],
          'ofsted_url' => ['type' => 'url', 'store' => 'meta:_am_nursery_ofsted_url', 'label' => 'Ofsted report link'],
          'hygiene' => ['type' => 'text', 'store' => 'meta:_am_nursery_hygiene_rating', 'label' => 'Food hygiene rating', 'half' => true, 'placeholder' => '5'],
          'hygiene_date' => ['type' => 'text', 'store' => 'meta:_am_nursery_hygiene_date', 'label' => 'Rating date', 'half' => true, 'placeholder' => '20 January 2025'],
          'hygiene_authority' => ['type' => 'text', 'store' => 'meta:_am_nursery_hygiene_authority', 'label' => 'Council', 'half' => true],
          'hygiene_url' => ['type' => 'url', 'store' => 'meta:_am_nursery_hygiene_url', 'label' => 'Food hygiene link', 'half' => true],
          'order' => ['type' => 'number', 'store' => 'menu_order', 'label' => 'Position in list', 'help' => 'Lower numbers appear first.'],
        ],
      ],
      'post' => [
        'singular' => 'Blog article', 'plural' => 'Blog articles', 'route' => '/blogs/%slug%',
        'fields' => [
          'title' => ['type' => 'text', 'store' => 'title', 'label' => 'Title', 'required' => true],
          'cover' => ['type' => 'image', 'store' => 'thumbnail', 'label' => 'Cover photo', 'help' => 'Shown on the Blog card and at the top of the article. If empty, a standard nursery photo is used.'],
          'category' => ['type' => 'category', 'store' => 'category', 'label' => 'Category'],
          'summary' => ['type' => 'textarea', 'store' => 'excerpt', 'label' => 'Short summary', 'limit' => 160, 'help' => 'One or two sentences for the Blog card. If empty, the first lines of the article are used.'],
          'body' => ['type' => 'richtext', 'store' => 'content', 'label' => 'Article', 'required' => true, 'photos' => true, 'height' => 380],
          'featured' => ['type' => 'checkbox', 'store' => 'meta:_am_blog_featured', 'label' => 'Main article', 'checkbox_label' => 'Show this as the large main article on the Blog page'],
        ],
      ],
      'am_event' => [
        'singular' => 'Event', 'plural' => 'Events', 'route' => '/events/%slug%',
        'fields' => [
          'title' => ['type' => 'text', 'store' => 'title', 'label' => 'Event name', 'required' => true],
          'cover' => ['type' => 'image', 'store' => 'thumbnail', 'label' => 'Cover photo', 'required' => true],
          'date' => ['type' => 'date', 'store' => 'meta:_am_event_date', 'label' => 'Date', 'required' => true, 'help' => 'Past dates move to Past events automatically.'],
          'time' => ['type' => 'timerange', 'store' => 'meta:_am_event_time', 'label' => 'Time', 'required' => true],
          'location' => ['type' => 'select', 'store' => 'meta:_am_event_location', 'label' => 'Nursery', 'required' => true, 'options_cb' => 'am_cf_nursery_options', 'allow_other' => true],
          'summary' => ['type' => 'textarea', 'store' => 'excerpt', 'label' => 'Short summary', 'required' => true, 'limit' => 160, 'help' => 'Shown on the Events page card.'],
          'body' => ['type' => 'richtext', 'store' => 'content', 'label' => 'Description', 'required' => true, 'photos' => true],
          'booking' => ['type' => 'textarea', 'store' => 'meta:_am_event_booking_info', 'label' => 'Booking information', 'placeholder' => 'How to book, cost, what to bring'],
        ],
      ],
      'am_testimonial' => [
        'singular' => 'Testimonial', 'plural' => 'Testimonials', 'route' => '/testimonials',
        'auto_title' => ['name', 'location'],
        'fields' => [
          'name' => ['type' => 'text', 'store' => 'meta:_am_testimonial_name', 'label' => 'Parent name', 'required' => true, 'placeholder' => 'e.g. Sarah, or Parent of a 3-year-old'],
          'location' => ['type' => 'select', 'store' => 'meta:_am_testimonial_location', 'label' => 'Nursery', 'required' => true, 'options_cb' => 'am_cf_nursery_options', 'allow_other' => true],
          'quote' => ['type' => 'textarea', 'store' => 'meta:_am_testimonial_quote', 'label' => 'Quote', 'required' => true, 'rows' => 7, 'help' => 'The parent\'s words only. Leave a blank line between paragraphs.'],
        ],
      ],
      'am_job' => [
        'singular' => 'Job', 'plural' => 'Jobs', 'route' => '/careers/vacancies/%slug%',
        'fields' => [
          'title' => ['type' => 'text', 'store' => 'title', 'label' => 'Role title', 'required' => true, 'placeholder' => 'e.g. Nursery Practitioner (Level 3)'],
          'status' => ['type' => 'select', 'store' => 'meta:_am_job_status', 'label' => 'Status', 'required' => true, 'default' => 'open', 'options' => ['open' => 'Open - show on website', 'closed' => 'Closed - hide from website']],
          'location' => ['type' => 'select', 'store' => 'meta:_am_job_location', 'label' => 'Nursery', 'required' => true, 'options_cb' => 'am_cf_nursery_options', 'allow_other' => true],
          'jobtype' => ['type' => 'select', 'store' => 'meta:_am_job_type', 'label' => 'Job type', 'required' => true, 'options' => ['Full-time' => 'Full-time', 'Part-time' => 'Part-time', 'Sessional' => 'Sessional', 'Volunteer' => 'Volunteer']],
          'hours' => ['type' => 'text', 'store' => 'meta:_am_job_hours', 'label' => 'Hours', 'placeholder' => 'e.g. 40 hours, Mon-Fri'],
          'salary' => ['type' => 'text', 'store' => 'meta:_am_job_salary', 'label' => 'Salary', 'placeholder' => 'e.g. £26,000 - £28,000'],
          'summary' => ['type' => 'textarea', 'store' => 'meta:_am_job_short', 'label' => 'Short summary', 'required' => true, 'limit' => 200],
          'body' => ['type' => 'richtext', 'store' => 'meta:_am_job_full', 'label' => 'Full description', 'required' => true],
          'benefits' => ['type' => 'textarea', 'store' => 'meta:_am_job_benefits', 'label' => 'Benefits', 'rows' => 3],
          'email' => ['type' => 'email', 'store' => 'meta:_am_job_apply_email', 'label' => 'Apply by email'],
        ],
      ],
    ],
  ], __DIR__ . '/alexandra-content-forms/forms.php');
});
