<?php
/**
 * Plugin Name: Little Rainbows Content Forms
 * Description: Simple create/edit forms for Blog, Events, Testimonials, Jobs, Our Nursery, About us and Site Settings in place of the WordPress editor. Saves into the same fields the website reads.
 */

if (!defined('ABSPATH')) exit;
require_once __DIR__ . '/ldrn-content-forms/forms.php';

/** Published nursery names plus "All Nurseries", as select options. */
function ldrn_cf_nursery_options() {
  $options = [];
  foreach (get_posts(['post_type' => 'ldrn_setting', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC']) as $p) {
    $name = html_entity_decode(get_the_title($p), ENT_QUOTES, 'UTF-8');
    $options[$name] = $name;
  }
  $options['All settings'] = 'All settings';
  return $options;
}

/** Editors only reach the sections allocated to them (inc/ldrn-roles.php). */
function ldrn_cf_access_check($type, $post_id) {
  if (!function_exists('ldrn_access_admin_request_allowed')) return true;
  return $post_id
    ? ldrn_access_admin_request_allowed('post.php', ['post' => $post_id])
    : ldrn_access_admin_request_allowed('post-new.php', ['post_type' => $type]);
}

function ldrn_cf_page_access_check($slug) {
  if (!function_exists('ldrn_access_admin_request_allowed')) return true;
  return ldrn_access_admin_request_allowed('admin.php', ['page' => $slug]);
}

add_action('after_setup_theme', function () {
  Content_Forms::boot([
    'brand' => ['primary' => '#213472', 'primary_dark' => '#172552', 'soft' => '#f4f8fc'],
    'hub' => ['label' => 'Website Content', 'url' => 'admin.php?page=ldrn_content'],
    'access_check' => 'ldrn_cf_access_check',
    'page_access_check' => 'ldrn_cf_page_access_check',
    'pages' => [
      'ldrn_about' => [
        'title' => 'About us', 'parent' => 'ldrn_content', 'option' => 'ldrn_about', 'capability' => 'manage_ldrn_site_settings',
        'intro' => 'The About us section on the homepage. Anything left empty keeps the website\'s built-in text.',
        'fields' => [
          'heading' => ['type' => 'text', 'store' => 'opt:heading', 'label' => 'Heading', 'placeholder' => 'About us'],
          'body' => ['type' => 'textarea', 'store' => 'opt:body', 'label' => 'Text', 'rows' => 10, 'help' => 'Leave an empty line between paragraphs.'],
          'image' => ['type' => 'image', 'store' => 'opt:image', 'label' => 'Photo', 'help' => 'The round photo beside the text. A square photo looks best.'],
          'milestones' => ['type' => 'textarea', 'store' => 'opt:milestones', 'label' => 'Milestones', 'rows' => 4, 'help' => 'One per line, e.g. "2019 - Hounslow". Shown as small labels under the text.'],
          'upcoming' => ['type' => 'text', 'store' => 'opt:upcoming', 'label' => 'Coming soon', 'placeholder' => 'e.g. Coming soon - Ealing', 'help' => 'Shown as a highlighted label. Leave empty to hide it.'],
        ],
      ],
      'ldrn_site_settings' => [
        'title' => 'Site Settings', 'parent' => 'ldrn_content', 'option' => 'ldrn_site_settings', 'capability' => 'manage_ldrn_site_settings',
        'intro' => 'Contact details and links used across the whole website.',
        'fields' => [
          'h1' => ['type' => 'heading', 'label' => 'Contact details'],
          'phone' => ['type' => 'text', 'store' => 'opt:phone', 'label' => 'Phone', 'half' => true],
          'email' => ['type' => 'email', 'store' => 'opt:email', 'label' => 'Email', 'half' => true],
          'hours' => ['type' => 'text', 'store' => 'opt:hours', 'label' => 'Opening hours', 'placeholder' => 'Mon – Fri · 8am – 6pm'],
          'address' => ['type' => 'textarea', 'store' => 'opt:address', 'label' => 'Address', 'rows' => 3],
          'apply_url' => ['type' => 'url', 'store' => 'opt:apply_url', 'label' => 'Apply / contact link'],
          'socials' => ['type' => 'repeater', 'store' => 'opt:socials', 'label' => 'Social links', 'add_label' => 'Add social link',
            'columns' => ['label' => ['label' => 'Name', 'placeholder' => 'e.g. Instagram'], 'href' => ['label' => 'Link', 'type' => 'url', 'placeholder' => 'https://']]],
          'h2' => ['type' => 'heading', 'label' => 'Google', 'help' => 'Only change these if you have set up Google Analytics or Search Console.'],
          'visible' => ['type' => 'checkbox', 'store' => 'blog_public', 'label' => 'Search engines', 'checkbox_label' => 'Let Google and other search engines list this website'],
          'ga4_id' => ['type' => 'text', 'store' => 'opt:ga4_id', 'label' => 'Google Analytics ID', 'placeholder' => 'G-XXXXXXXXXX', 'half' => true],
          'meta_pixel_id' => ['type' => 'text', 'store' => 'opt:meta_pixel_id', 'label' => 'Meta (Facebook) Pixel ID', 'half' => true],
          'gsc_verification' => ['type' => 'text', 'store' => 'opt:gsc_verification', 'label' => 'Search Console code', 'half' => true],
        ],
      ],
    ],
    'types' => [
      'ldrn_setting' => [
        'singular' => 'Nursery', 'plural' => 'Nurseries', 'route' => '/our-nursery/%slug%',
        'fields' => [
          'h1' => ['type' => 'heading', 'label' => 'About the nursery'],
          'title' => ['type' => 'text', 'store' => 'title', 'label' => 'Nursery name', 'required' => true, 'placeholder' => 'e.g. Hounslow'],
          'area' => ['type' => 'text', 'store' => 'meta:_ldrn_setting_area', 'label' => 'Area', 'half' => true],
          'age' => ['type' => 'text', 'store' => 'meta:_ldrn_setting_age_range', 'label' => 'Age range', 'required' => true, 'half' => true, 'placeholder' => '6 months to 5 years'],
          'tagline' => ['type' => 'text', 'store' => 'meta:_ldrn_setting_hero_tagline', 'label' => 'Tagline', 'half' => true],
          'subheading' => ['type' => 'text', 'store' => 'meta:_ldrn_setting_subheading', 'label' => 'Subheading', 'half' => true],
          'short' => ['type' => 'textarea', 'store' => 'meta:_ldrn_setting_short', 'label' => 'Short description', 'required' => true, 'limit' => 200, 'help' => 'Shown on the nursery card.'],
          'welcome' => ['type' => 'textarea', 'store' => 'meta:_ldrn_setting_welcome', 'label' => 'Welcome text', 'required' => true, 'rows' => 6, 'help' => 'Shown on the nursery\'s own page.'],
          'h2' => ['type' => 'heading', 'label' => 'Contact'],
          'address' => ['type' => 'text', 'store' => 'meta:_ldrn_setting_address', 'label' => 'Address', 'required' => true],
          'postcode' => ['type' => 'text', 'store' => 'meta:_ldrn_setting_postcode', 'label' => 'Postcode', 'half' => true],
          'hours' => ['type' => 'text', 'store' => 'meta:_ldrn_setting_hours', 'label' => 'Opening hours', 'required' => true, 'half' => true, 'placeholder' => 'Mon – Fri · 8am – 6pm'],
          'phone' => ['type' => 'text', 'store' => 'meta:_ldrn_setting_phone', 'label' => 'Phone', 'required' => true, 'half' => true],
          'email' => ['type' => 'email', 'store' => 'meta:_ldrn_setting_email', 'label' => 'Email', 'required' => true, 'half' => true],
          'h3' => ['type' => 'heading', 'label' => 'Photos and documents'],
          'hero' => ['type' => 'image', 'store' => 'meta:_ldrn_setting_hero_image', 'label' => 'Main photo', 'required' => true, 'half' => true, 'help' => 'Top of the nursery page and on its card.'],
          'welcome_image' => ['type' => 'image', 'store' => 'meta:_ldrn_setting_welcome_image', 'label' => 'Welcome photo', 'half' => true],
          'gallery' => ['type' => 'gallery', 'store' => 'meta:_ldrn_setting_gallery', 'label' => 'Gallery'],
          'fees' => ['type' => 'file', 'store' => 'meta:_ldrn_setting_fee_sheet_pdf', 'label' => 'Fee sheet (PDF)'],
          'h4' => ['type' => 'heading', 'label' => 'Ofsted and food hygiene'],
          'ofsted' => ['type' => 'select', 'store' => 'meta:_ldrn_setting_ofsted_rating', 'label' => 'Ofsted rating', 'half' => true, 'options' => ['Outstanding' => 'Outstanding', 'Good' => 'Good', 'Requires improvement' => 'Requires improvement', 'Inadequate' => 'Inadequate', 'Pending' => 'Pending - not yet inspected']],
          'ofsted_label' => ['type' => 'text', 'store' => 'meta:_ldrn_setting_ofsted_label', 'label' => 'Ofsted link text', 'half' => true, 'placeholder' => 'Official Ofsted report'],
          'ofsted_url' => ['type' => 'url', 'store' => 'meta:_ldrn_setting_ofsted_url', 'label' => 'Ofsted report link'],
          'hygiene' => ['type' => 'text', 'store' => 'meta:_ldrn_setting_hygiene_rating', 'label' => 'Food hygiene rating', 'half' => true, 'placeholder' => '5'],
          'hygiene_date' => ['type' => 'text', 'store' => 'meta:_ldrn_setting_hygiene_date', 'label' => 'Rating date', 'half' => true, 'placeholder' => '20 January 2025'],
          'hygiene_authority' => ['type' => 'text', 'store' => 'meta:_ldrn_setting_hygiene_authority', 'label' => 'Council', 'half' => true],
          'hygiene_url' => ['type' => 'url', 'store' => 'meta:_ldrn_setting_hygiene_url', 'label' => 'Food hygiene link', 'half' => true],
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
          'featured' => ['type' => 'checkbox', 'store' => 'meta:_ldrn_blog_featured', 'label' => 'Main article', 'checkbox_label' => 'Show this as the large main article on the Blog page'],
        ],
      ],
      'ldrn_event' => [
        'singular' => 'Event', 'plural' => 'Events', 'route' => '/events/%slug%',
        'fields' => [
          'title' => ['type' => 'text', 'store' => 'title', 'label' => 'Event name', 'required' => true],
          'cover' => ['type' => 'image', 'store' => 'thumbnail', 'label' => 'Cover photo', 'required' => true],
          'date' => ['type' => 'date', 'store' => 'meta:_ldrn_event_date', 'label' => 'Date', 'required' => true, 'help' => 'Past dates move to Past events automatically.'],
          'time' => ['type' => 'timerange', 'store' => 'meta:_ldrn_event_time', 'label' => 'Time', 'required' => true],
          'location' => ['type' => 'select', 'store' => 'meta:_ldrn_event_location', 'label' => 'Nursery', 'required' => true, 'options_cb' => 'ldrn_cf_nursery_options', 'allow_other' => true],
          'summary' => ['type' => 'textarea', 'store' => 'excerpt', 'label' => 'Short summary', 'required' => true, 'limit' => 160, 'help' => 'Shown on the Events page card.'],
          'body' => ['type' => 'richtext', 'store' => 'content', 'label' => 'Description', 'required' => true, 'photos' => true],
          'booking' => ['type' => 'textarea', 'store' => 'meta:_ldrn_event_booking_info', 'label' => 'Booking information', 'placeholder' => 'How to book, cost, what to bring'],
        ],
      ],
      'ldrn_testimonial' => [
        'singular' => 'Testimonial', 'plural' => 'Testimonials', 'route' => '/testimonials',
        'auto_title' => ['name', 'location'],
        'fields' => [
          'name' => ['type' => 'text', 'store' => 'meta:_ldrn_testimonial_name', 'label' => 'Parent name', 'required' => true, 'placeholder' => 'e.g. Sarah, or Parent of a 3-year-old'],
          'location' => ['type' => 'select', 'store' => 'meta:_ldrn_testimonial_location', 'label' => 'Nursery', 'required' => true, 'options_cb' => 'ldrn_cf_nursery_options', 'allow_other' => true],
          'quote' => ['type' => 'textarea', 'store' => 'meta:_ldrn_testimonial_quote', 'label' => 'Quote', 'required' => true, 'rows' => 7, 'help' => 'The parent\'s words only. Leave a blank line between paragraphs.'],
        ],
      ],
      'ldrn_job' => [
        'singular' => 'Job', 'plural' => 'Jobs', 'route' => '/careers/vacancies',
        'fields' => [
          'title' => ['type' => 'text', 'store' => 'title', 'label' => 'Role title', 'required' => true, 'placeholder' => 'e.g. Nursery Practitioner (Level 3)'],
          'status' => ['type' => 'select', 'store' => 'meta:_ldrn_job_status', 'label' => 'Status', 'required' => true, 'default' => 'open', 'options' => ['open' => 'Open - show on website', 'closed' => 'Closed - hide from website']],
          'location' => ['type' => 'select', 'store' => 'meta:_ldrn_job_location', 'label' => 'Nursery', 'required' => true, 'options_cb' => 'ldrn_cf_nursery_options', 'allow_other' => true],
          'jobtype' => ['type' => 'select', 'store' => 'meta:_ldrn_job_type', 'label' => 'Job type', 'required' => true, 'options' => ['Full-time' => 'Full-time', 'Part-time' => 'Part-time', 'Sessional' => 'Sessional', 'Volunteer' => 'Volunteer']],
          'hours' => ['type' => 'text', 'store' => 'meta:_ldrn_job_hours', 'label' => 'Hours', 'placeholder' => 'e.g. 40 hours, Mon-Fri'],
          'salary' => ['type' => 'text', 'store' => 'meta:_ldrn_job_salary', 'label' => 'Salary', 'placeholder' => 'e.g. £26,000 - £28,000'],
          'summary' => ['type' => 'textarea', 'store' => 'meta:_ldrn_job_short', 'label' => 'Short summary', 'required' => true, 'limit' => 200],
          'body' => ['type' => 'richtext', 'store' => 'meta:_ldrn_job_full', 'label' => 'Full description', 'required' => true],
          'benefits' => ['type' => 'textarea', 'store' => 'meta:_ldrn_job_benefits', 'label' => 'Benefits', 'rows' => 3],
          'email' => ['type' => 'email', 'store' => 'meta:_ldrn_job_apply_email', 'label' => 'Apply by email'],
        ],
      ],
    ],
  ], __DIR__ . '/ldrn-content-forms/forms.php');
});
