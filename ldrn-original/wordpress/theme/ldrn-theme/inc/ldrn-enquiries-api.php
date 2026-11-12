<?php
/**
 * Little Rainbows Day Nursery — Contact enquiries + Availability requests backend.
 *
 * Mirrors the careers API pattern (inc/ldrn-careers-api.php):
 *   - ldrn_enquiry CPT       (Contact Us form)        + REST POST /wp-json/ldrn/v1/enquiry
 *   - ldrn_availability CPT  (Check Availability form) + REST POST /wp-json/ldrn/v1/availability
 *   - CMB2 meta boxes, admin list columns, spam guards, wp_mail notifications.
 *
 * Submissions are always stored as posts (visible in wp-admin) even if email
 * delivery is unavailable, so no enquiry is ever lost.
 */

if (!defined('ABSPATH')) exit;

// ===================================================================
// Custom Post Types
// ===================================================================

function ldrn_register_enquiry_cpts() {
  register_post_type('ldrn_enquiry', [
    'labels' => [
      'name'          => 'Contact & Visit Requests',
      'singular_name' => 'Contact / Visit Request',
      'menu_name'     => 'Contact & Visits',
      'edit_item'     => 'Open Contact / Visit Request',
      'search_items'  => 'Search Contact & Visits',
    ],
    'public'       => false,
    'show_ui'      => true,
    'show_in_menu' => LDRN_SUBMISSIONS_MENU_SLUG,
    'supports'     => false,
    'menu_icon'    => 'dashicons-email-alt',
    'rewrite'      => false,
    'capabilities' => ['create_posts' => 'do_not_allow'], // form-only, no manual add
    'map_meta_cap' => true,
  ]);

  register_post_type('ldrn_availability', [
    'labels' => [
      'name'          => 'Availability Requests',
      'singular_name' => 'Availability Request',
      'menu_name'     => 'Availability',
      'edit_item'     => 'Open Availability Request',
      'search_items'  => 'Search Availability Requests',
    ],
    'public'       => false,
    'show_ui'      => true,
    'show_in_menu' => LDRN_SUBMISSIONS_MENU_SLUG,
    'supports'     => false,
    'menu_icon'    => 'dashicons-calendar-alt',
    'rewrite'      => false,
    'capabilities' => ['create_posts' => 'do_not_allow'],
    'map_meta_cap' => true,
  ]);
}
add_action('init', 'ldrn_register_enquiry_cpts');

// ===================================================================
// CMB2 meta boxes (read-only review of submitted data + status)
// ===================================================================

function ldrn_register_enquiry_fields() {
  if (!function_exists('new_cmb2_box')) return;

  // --- Contact enquiry ---
  $c = new_cmb2_box([
    'id'           => 'ldrn_enquiry_details',
    'title'        => 'Enquiry Details',
    'object_types' => ['ldrn_enquiry'],
    'context'      => 'normal',
    'priority'     => 'high',
  ]);
  $c->add_field(['name' => 'First name', 'id' => '_ldrn_enq_first_name', 'type' => 'text']);
  $c->add_field(['name' => 'Last name',  'id' => '_ldrn_enq_last_name',  'type' => 'text']);
  $c->add_field(['name' => 'Email',      'id' => '_ldrn_enq_email',      'type' => 'text']);
  $c->add_field(['name' => 'Phone',      'id' => '_ldrn_enq_phone',      'type' => 'text']);
  $c->add_field(['name' => 'Branch',     'id' => '_ldrn_enq_branch',     'type' => 'text']);
  $c->add_field(['name' => 'Source',     'id' => '_ldrn_enq_kind',       'type' => 'text']);
  $c->add_field(['name' => 'Preferred visit date', 'id' => '_ldrn_enq_preferred_date', 'type' => 'text']);
  $c->add_field(['name' => 'Message',    'id' => '_ldrn_enq_message',    'type' => 'textarea']);
  $c->add_field(['name' => 'Submitted at', 'id' => '_ldrn_enq_submitted_at', 'type' => 'text', 'attributes' => ['readonly' => 'readonly']]);
  $c->add_field([
    'name' => 'Assigned owner', 'id' => '_ldrn_enq_owner', 'type' => 'select',
    'options_cb' => 'ldrn_submission_owner_options',
  ]);
  $c->add_field(['name' => 'Internal notes', 'id' => '_ldrn_enq_notes', 'type' => 'textarea']);
  $c->add_field([
    'name' => 'Status', 'id' => '_ldrn_enq_status', 'type' => 'select',
    'options' => ldrn_submission_config('ldrn_enquiry')['statuses'],
    'default' => 'new',
  ]);

  // --- Availability request ---
  $a = new_cmb2_box([
    'id'           => 'ldrn_availability_details',
    'title'        => 'Availability Request Details',
    'object_types' => ['ldrn_availability'],
    'context'      => 'normal',
    'priority'     => 'high',
  ]);
  $a->add_field(['name' => 'Name',            'id' => '_ldrn_avl_name',       'type' => 'text']);
  $a->add_field(['name' => 'Email',           'id' => '_ldrn_avl_email',      'type' => 'text']);
  $a->add_field(['name' => 'Phone',           'id' => '_ldrn_avl_phone',      'type' => 'text']);
  $a->add_field(['name' => 'Child age',       'id' => '_ldrn_avl_child_age',  'type' => 'text']);
  $a->add_field(['name' => 'Preferred nursery', 'id' => '_ldrn_avl_branch',   'type' => 'text']);
  $a->add_field(['name' => 'Desired start date', 'id' => '_ldrn_avl_start',   'type' => 'text']);
  $a->add_field(['name' => 'Days / sessions', 'id' => '_ldrn_avl_sessions',   'type' => 'text']);
  $a->add_field(['name' => 'Message',         'id' => '_ldrn_avl_message',    'type' => 'textarea']);
  $a->add_field(['name' => 'Submitted at',    'id' => '_ldrn_avl_submitted_at', 'type' => 'text', 'attributes' => ['readonly' => 'readonly']]);
  $a->add_field([
    'name' => 'Assigned owner', 'id' => '_ldrn_avl_owner', 'type' => 'select',
    'options_cb' => 'ldrn_submission_owner_options',
  ]);
  $a->add_field(['name' => 'Internal notes', 'id' => '_ldrn_avl_notes', 'type' => 'textarea']);
  $a->add_field([
    'name' => 'Status', 'id' => '_ldrn_avl_status', 'type' => 'select',
    'options' => ldrn_submission_config('ldrn_availability')['statuses'],
    'default' => 'new',
  ]);
}
add_action('cmb2_admin_init', 'ldrn_register_enquiry_fields');

// ===================================================================
// Admin list columns
// ===================================================================

function ldrn_enquiry_columns($cols) {
  return [
    'cb' => $cols['cb'], 'title' => 'From', 'ldrn_type' => 'Source', 'ldrn_email' => 'Email',
    'ldrn_branch' => 'Branch', 'ldrn_owner' => 'Owner', 'ldrn_status' => 'Status', 'ldrn_submitted' => 'Submitted',
  ];
}
add_filter('manage_ldrn_enquiry_posts_columns', 'ldrn_enquiry_columns');
add_filter('manage_ldrn_availability_posts_columns', 'ldrn_enquiry_columns');

function ldrn_enquiry_column_values($col, $post_id, $prefix) {
  switch ($col) {
    case 'ldrn_email':
      $email = get_post_meta($post_id, "_ldrn_{$prefix}_email", true);
      echo '<a href="' . esc_url(ldrn_gmail_compose_url($email)) . '" target="_blank" rel="noopener noreferrer">' . esc_html($email) . '</a>';
      break;
    case 'ldrn_type':
      echo esc_html($prefix === 'enq' ? ldrn_submission_source_label($post_id) : 'Check availability');
      break;
    case 'ldrn_owner':
      echo esc_html(ldrn_submission_owner_label($post_id, "_ldrn_{$prefix}_owner"));
      break;
    case 'ldrn_branch':    echo esc_html(get_post_meta($post_id, "_ldrn_{$prefix}_branch", true) ?: '—'); break;
    case 'ldrn_status':
      $config = ldrn_submission_config(get_post_type($post_id));
      $status = get_post_meta($post_id, "_ldrn_{$prefix}_status", true) ?: 'new';
      echo '<span class="ldrn-status ldrn-status-' . esc_attr(sanitize_html_class($status)) . '">' . esc_html($config['statuses'][$status] ?? ucfirst($status)) . '</span>';
      break;
    case 'ldrn_submitted':
      $raw = get_post_meta($post_id, "_ldrn_{$prefix}_submitted_at", true);
      echo $raw ? esc_html(date_i18n('d M Y, H:i', strtotime($raw))) : '—';
      break;
  }
}
add_action('manage_ldrn_enquiry_posts_custom_column', function ($col, $id) {
  ldrn_enquiry_column_values($col, $id, 'enq');
}, 10, 2);
add_action('manage_ldrn_availability_posts_custom_column', function ($col, $id) {
  ldrn_enquiry_column_values($col, $id, 'avl');
}, 10, 2);

// ===================================================================
// Shared spam guard (honeypot + timing), matching the careers endpoint
// ===================================================================

function ldrn_enquiry_spldrn_check(WP_REST_Request $request) {
  if (!empty($request->get_param('website'))) {
    return new WP_Error('spam', 'Invalid submission.', ['status' => 400]);
  }
  $loaded_at = (int) $request->get_param('formLoadedAt');
  if ($loaded_at > 0 && (time() * 1000 - $loaded_at) < 3000) {
    return new WP_Error('too_fast', 'Please take a moment to fill out the form.', ['status' => 400]);
  }
  // Per-IP flood protection (shared across all form endpoints).
  if (function_exists('ldrn_rate_limit_guard')) {
    $limited = ldrn_rate_limit_guard();
    if (is_wp_error($limited)) return $limited;
  }
  return true;
}

/**
 * Per-setting fallback inbox.
 *
 * Deliberately empty. Little Rainbows has not confirmed a domain mailbox, so
 * there is no address that can be assumed here — guessing one would route a
 * parent's enquiry to an inbox nobody reads. The only source of a recipient
 * is the address an administrator enters against the setting in wp-admin
 * (see ldrn_branch_label). With no address configured, the operations plugin
 * holds the submission in the dashboard rather than sending it anywhere.
 */
function ldrn_branch_fallback_email($key) {
  unset($key);
  return '';
}

// Resolve a setting to its display name + notification email. Accepts either
// a slug id (hounslow — Contact/Availability forms) or a display name
// ("Hounslow" — the careers job "Location" field).
function ldrn_branch_label($branch_id) {
  if (!$branch_id) return ['name' => '', 'email' => ''];
  $nurseries = function_exists('ldrn_get_settings') ? ldrn_get_settings() : [];
  foreach ($nurseries as $n) {
    $matches_id   = (($n['id'] ?? '') === $branch_id);
    $matches_name = (strcasecmp($n['name'] ?? '', $branch_id) === 0);
    if ($matches_id || $matches_name) {
      // Prefer the wp-admin email; fall back to the known branch inbox.
      $email = !empty($n['email']) ? $n['email'] : ldrn_branch_fallback_email($n['id'] ?? $branch_id);
      return ['name' => $n['name'] ?? $branch_id, 'email' => $email];
    }
  }
  // No nursery record matched — still resolve a known branch by slug/name.
  return [
    'name'  => ucfirst($branch_id),
    'email' => ldrn_branch_fallback_email($branch_id),
  ];
}

// ===================================================================
// REST — POST /wp-json/ldrn/v1/enquiry  (Contact Us form)
// ===================================================================

add_action('rest_api_init', function () {
  register_rest_route('ldrn/v1', '/enquiry', [
    'methods'             => 'POST',
    'callback'            => 'ldrn_handle_enquiry',
    'permission_callback' => '__return_true',
  ]);
  register_rest_route('ldrn/v1', '/availability', [
    'methods'             => 'POST',
    'callback'            => 'ldrn_handle_availability',
    'permission_callback' => '__return_true',
  ]);
});

function ldrn_handle_enquiry(WP_REST_Request $request) {
  $spam = ldrn_enquiry_spldrn_check($request);
  if (is_wp_error($spam)) return $spam;

  $first  = sanitize_text_field($request->get_param('firstName'));
  $last   = sanitize_text_field($request->get_param('lastName'));
  $email  = sanitize_email($request->get_param('email'));
  $phone  = ldrn_validate_e164_phone($request->get_param('phone'), false);
  $branch = sanitize_text_field($request->get_param('branch'));
  $msg    = sanitize_textarea_field($request->get_param('message'));
  // Optional: a "Book a visit" submission carries a preferred date.
  $kind      = sanitize_key($request->get_param('kind'));            // '' | 'booking'
  $preferred = sanitize_text_field($request->get_param('preferredDate'));
  $is_booking = ($kind === 'booking');

  if (empty($first)) {
    return new WP_Error('missing_name', 'Your name is required.', ['status' => 400]);
  }
  if (empty($email) || !is_email($email)) {
    return new WP_Error('invalid_email', 'A valid email address is required.', ['status' => 400]);
  }
  if (is_wp_error($phone)) {
    return $phone;
  }
  if ($is_booking && $phone === '') {
    return new WP_Error('missing_phone', 'A phone number with country code is required.', ['status' => 400]);
  }

  $branch_info = ldrn_branch_label($branch);
  $date  = current_time('Y-m-d');
  $name  = trim("{$first} {$last}");
  $bname = $branch_info['name'] ?: '—';
  $label = $is_booking ? 'Visit booking' : 'Enquiry';

  $post_id = wp_insert_post([
    'post_type'   => 'ldrn_enquiry',
    'post_status' => 'private',
    'post_title'  => "{$label}: {$name} \u{2014} {$bname} \u{2014} {$date}",
  ]);
  if (is_wp_error($post_id)) {
    return new WP_Error('save_failed', 'Could not save your message. Please try again.', ['status' => 500]);
  }

  $meta = [
    '_ldrn_enq_first_name'   => $first,
    '_ldrn_enq_last_name'    => $last,
    '_ldrn_enq_email'        => $email,
    '_ldrn_enq_phone'        => $phone,
    '_ldrn_enq_branch'       => $bname,
    '_ldrn_enq_kind'         => $is_booking ? 'booking' : 'contact',
    '_ldrn_enq_preferred_date' => $is_booking ? $preferred : '',
    '_ldrn_enq_message'      => ($is_booking && $preferred)
                               ? "Preferred visit date: {$preferred}\n\n{$msg}"
                               : $msg,
    '_ldrn_enq_owner'        => 0,
    '_ldrn_enq_notes'        => '',
    '_ldrn_enq_status'       => 'new',
    '_ldrn_enq_submitted_at' => current_time('c'),
  ];
  foreach ($meta as $k => $v) update_post_meta($post_id, $k, $v);

  // Notify (branch email preferred in production; test address wins while set).
  $to      = ldrn_notify_email($branch_info['email']);
  $subject = $is_booking
    ? "New Visit Booking \u{2013} {$bname} \u{2013} {$name}"
    : "New Enquiry \u{2013} {$bname} \u{2013} {$name}";
  $rows = [
    ['Name', $name], ['Email', $email], ['Phone', $phone ?: '—'],
    ['Branch', $bname],
  ];
  if ($is_booking) {
    $rows[] = ['Preferred visit date', $preferred ?: '—'];
  }
  $rows[] = ['Message', $msg ?: '—'];
  $rows[] = ['Routed to (production)', $branch_info['email'] ?: '(site email)'];
  $rows[] = ['Submitted', $meta['_ldrn_enq_submitted_at']];

  $intro = $is_booking ? 'A new visit booking' : 'A new website enquiry';
  ldrn_send_mail_async($to, $subject, ldrn_enquiry_email_body($intro, $post_id, $rows), [
    'Content-Type: text/html; charset=UTF-8',
    'Reply-To: ' . $email,
  ]);

  return rest_ensure_response(['success' => true]);
}

function ldrn_handle_availability(WP_REST_Request $request) {
  $spam = ldrn_enquiry_spldrn_check($request);
  if (is_wp_error($spam)) return $spam;

  $name   = sanitize_text_field($request->get_param('name'));
  $email  = sanitize_email($request->get_param('email'));
  $phone  = ldrn_validate_e164_phone($request->get_param('phone'), true);
  $age    = sanitize_text_field($request->get_param('childAge'));
  $branch = sanitize_text_field($request->get_param('branch'));
  $start  = sanitize_text_field($request->get_param('startDate'));
  $sess   = sanitize_text_field($request->get_param('sessions'));
  $msg    = sanitize_textarea_field($request->get_param('message'));

  if (empty($name)) {
    return new WP_Error('missing_name', 'Your name is required.', ['status' => 400]);
  }
  if (empty($email) || !is_email($email)) {
    return new WP_Error('invalid_email', 'A valid email address is required.', ['status' => 400]);
  }
  if (is_wp_error($phone)) {
    return $phone;
  }

  $branch_info = ldrn_branch_label($branch);
  $bname = $branch_info['name'] ?: 'Not sure yet';
  $date  = current_time('Y-m-d');

  $post_id = wp_insert_post([
    'post_type'   => 'ldrn_availability',
    'post_status' => 'private',
    'post_title'  => "{$name} \u{2014} {$bname} \u{2014} {$date}",
  ]);
  if (is_wp_error($post_id)) {
    return new WP_Error('save_failed', 'Could not save your request. Please try again.', ['status' => 500]);
  }

  $meta = [
    '_ldrn_avl_name'         => $name,
    '_ldrn_avl_email'        => $email,
    '_ldrn_avl_phone'        => $phone,
    '_ldrn_avl_child_age'    => $age,
    '_ldrn_avl_branch'       => $bname,
    '_ldrn_avl_start'        => $start,
    '_ldrn_avl_sessions'     => $sess,
    '_ldrn_avl_message'      => $msg,
    '_ldrn_avl_owner'        => 0,
    '_ldrn_avl_notes'        => '',
    '_ldrn_avl_status'       => 'new',
    '_ldrn_avl_submitted_at' => current_time('c'),
  ];
  foreach ($meta as $k => $v) update_post_meta($post_id, $k, $v);

  $to      = ldrn_notify_email($branch_info['email']);
  $subject = "New Availability Request \u{2013} {$bname} \u{2013} {$name}";
  $rows = [
    ['Name', $name], ['Email', $email], ['Phone', $phone ?: '—'],
    ['Child age', $age ?: '—'], ['Preferred nursery', $bname],
    ['Desired start', $start ?: '—'], ['Days / sessions', $sess ?: '—'],
    ['Message', $msg ?: '—'],
    ['Routed to (production)', $branch_info['email'] ?: '(site email)'],
    ['Submitted', $meta['_ldrn_avl_submitted_at']],
  ];
  ldrn_send_mail_async($to, $subject, ldrn_enquiry_email_body('New availability request', $post_id, $rows), [
    'Content-Type: text/html; charset=UTF-8',
    'Reply-To: ' . $email,
  ]);

  return rest_ensure_response(['success' => true]);
}

// ===================================================================
// Shared HTML email body
// ===================================================================

function ldrn_enquiry_email_body($intro, $post_id, $rows) {
  $admin_url = admin_url("post.php?post={$post_id}&action=edit");
  $table = '<table style="border-collapse:collapse;width:100%;font-family:sans-serif;font-size:14px;">';
  foreach ($rows as [$label, $value]) {
    $table .= '<tr>'
      . '<td style="padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:600;white-space:nowrap;vertical-align:top;">' . esc_html($label) . '</td>'
      . '<td style="padding:8px 12px;border:1px solid #ddd;">' . nl2br(esc_html($value)) . '</td>'
      . '</tr>';
  }
  $table .= '</table>';

  return '<p style="font-family:sans-serif;font-size:14px;">' . esc_html($intro)
    . ' has been submitted via the Little Rainbows Day Nursery website.</p>'
    . $table;
}
