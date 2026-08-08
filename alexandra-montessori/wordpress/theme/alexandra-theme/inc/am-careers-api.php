<?php
/**
 * Alexandra Montessori — Careers Applications backend
 *
 * Registers:
 *   - am_application CPT
 *   - CMB2 meta box (application fields + status workflow)
 *   - Admin list columns
 *   - REST endpoint POST /wp-json/am/v1/apply
 *   - Email notification helper
 */

if (!defined('ABSPATH')) exit;

// ===================================================================
// Applications CPT
// ===================================================================

function am_register_application_cpt() {
  register_post_type('am_application', [
    'labels' => [
      'name'          => 'Applications',
      'singular_name' => 'Application',
      'menu_name'     => 'Careers',
      'edit_item'     => 'Open Career Application',
      'search_items'  => 'Search Career Applications',
    ],
    'public'       => false,
    'show_ui'      => true,
    'show_in_menu' => AM_SUBMISSIONS_MENU_SLUG,
    'supports'     => false,
    'menu_icon'    => 'dashicons-id-alt',
    'rewrite'      => false,
    'capabilities' => ['create_posts' => 'do_not_allow'],
    'map_meta_cap' => true,
  ]);
}
add_action('init', 'am_register_application_cpt');

// ===================================================================
// CMB2 meta box — application fields
// ===================================================================

function am_register_application_fields() {
  if (!function_exists('new_cmb2_box')) return;

  $cmb = new_cmb2_box([
    'id'           => 'am_application_details',
    'title'        => 'Application Details',
    'object_types' => ['am_application'],
    'context'      => 'normal',
    'priority'     => 'high',
  ]);

  // Applicant identity
  $cmb->add_field(['name' => 'First Name',    'id' => '_am_app_first_name',    'type' => 'text']);
  $cmb->add_field(['name' => 'Last Name',     'id' => '_am_app_last_name',     'type' => 'text']);
  $cmb->add_field(['name' => 'Email',         'id' => '_am_app_email',         'type' => 'text']);
  $cmb->add_field(['name' => 'Phone',         'id' => '_am_app_phone',         'type' => 'text']);

  // Role context
  $cmb->add_field(['name' => 'Qualification', 'id' => '_am_app_qualification', 'type' => 'text']);
  $cmb->add_field(['name' => 'Position',      'id' => '_am_app_position',      'type' => 'text']);
  $cmb->add_field(['name' => 'Nursery (applied to)', 'id' => '_am_app_branch', 'type' => 'text']);
  $cmb->add_field(['name' => 'Job Slug',      'id' => '_am_app_job_slug',      'type' => 'text']);
  $cmb->add_field(['name' => 'Job Title',     'id' => '_am_app_job_title',     'type' => 'text']);
  $cmb->add_field(['name' => 'Source',        'id' => '_am_app_source',        'type' => 'text']);

  // Cover message
  $cmb->add_field([
    'name' => 'Message',
    'id'   => '_am_app_message',
    'type' => 'textarea',
  ]);

  // Submission timestamp (auto-set, not editable by admin)
  $cmb->add_field([
    'name'       => 'Submitted At',
    'id'         => '_am_app_submitted_at',
    'type'       => 'text',
    'attributes' => ['readonly' => 'readonly'],
  ]);

  $cmb->add_field([
    'name' => 'Assigned owner',
    'id' => '_am_app_owner',
    'type' => 'select',
    'options_cb' => 'am_submission_owner_options',
  ]);
  $cmb->add_field(['name' => 'Internal notes', 'id' => '_am_app_notes', 'type' => 'textarea']);

  // Status — the primary workflow field
  $cmb->add_field([
    'name'    => 'Status',
    'id'      => '_am_app_status',
    'type'    => 'select',
    'options' => am_submission_config('am_application')['statuses'],
    'default' => 'new',
  ]);
}
add_action('cmb2_admin_init', 'am_register_application_fields');

// ===================================================================
// Admin list columns
// ===================================================================

function am_application_columns($cols) {
  return [
    'cb'              => $cols['cb'],
    'title'           => 'Applicant',
    'am_email'        => 'Email',
    'am_position'     => 'Position',
    'am_job'          => 'Job',
    'am_cv'           => 'CV',
    'am_owner'        => 'Owner',
    'am_status'       => 'Status',
    'am_submitted_at' => 'Submitted',
  ];
}
add_filter('manage_am_application_posts_columns', 'am_application_columns');

function am_application_column_values($col, $post_id) {
  switch ($col) {
    case 'am_email':
      $email = get_post_meta($post_id, '_am_app_email', true);
      echo '<a href="' . esc_url(am_gmail_compose_url($email)) . '" target="_blank" rel="noopener noreferrer">' . esc_html($email) . '</a>';
      break;
    case 'am_position':
      echo esc_html(get_post_meta($post_id, '_am_app_position', true));
      break;
    case 'am_job':
      $title = get_post_meta($post_id, '_am_app_job_title', true);
      $slug  = get_post_meta($post_id, '_am_app_job_slug', true);
      echo esc_html($title ?: ($slug === 'general' ? '— General —' : $slug));
      break;
    case 'am_owner':
      echo esc_html(am_submission_owner_label($post_id, '_am_app_owner'));
      break;
    case 'am_cv':
      $path = am_application_private_file_path($post_id);
      echo $path
        ? '<a class="am-ready" href="' . esc_url(am_application_download_url($post_id)) . '">Attached</a>'
        : '<span class="am-needs-work">Missing</span>';
      break;
    case 'am_status':
      $map = am_submission_config('am_application')['statuses'];
      $val = get_post_meta($post_id, '_am_app_status', true) ?: 'new';
      echo '<span class="am-status am-status-' . esc_attr(sanitize_html_class($val)) . '">' . esc_html($map[$val] ?? ucfirst($val)) . '</span>';
      break;
    case 'am_submitted_at':
      $raw = get_post_meta($post_id, '_am_app_submitted_at', true);
      echo $raw ? esc_html(date_i18n('d M Y, H:i', strtotime($raw))) : '—';
      break;
  }
}
add_action('manage_am_application_posts_custom_column', 'am_application_column_values', 10, 2);

// ===================================================================
// REST API endpoint — POST /wp-json/am/v1/apply
// ===================================================================

add_action('rest_api_init', function() {
  register_rest_route('am/v1', '/apply', [
    'methods'             => 'POST',
    'callback'            => 'am_handle_application',
    'permission_callback' => '__return_true',
  ]);
});

function am_handle_application(WP_REST_Request $request) {

  // 1. Honeypot — bots fill this field; real users never see it
  if (!empty($request->get_param('website'))) {
    return new WP_Error('spam', 'Invalid submission.', ['status' => 400]);
  }

  // 2. Timing — reject submissions faster than 3 seconds (bots)
  $loaded_at = (int)$request->get_param('formLoadedAt');
  if ($loaded_at > 0 && (time() * 1000 - $loaded_at) < 3000) {
    return new WP_Error('too_fast', 'Please take a moment to fill out the form.', ['status' => 400]);
  }

  // 2b. Per-IP flood protection (shared across all form endpoints)
  if (function_exists('am_rate_limit_guard')) {
    $limited = am_rate_limit_guard();
    if (is_wp_error($limited)) return $limited;
  }

  // 3. Sanitize all incoming params
  $first  = sanitize_text_field($request->get_param('firstName'));
  $last   = sanitize_text_field($request->get_param('lastName'));
  $email  = sanitize_email($request->get_param('email'));
  $phone  = am_validate_e164_phone($request->get_param('phone'), true);
  $qual   = sanitize_text_field($request->get_param('qualification'));
  $pos    = sanitize_text_field($request->get_param('position'));
  $slug   = sanitize_key($request->get_param('jobSlug'));
  $title  = sanitize_text_field($request->get_param('jobTitle'));
  $branch = sanitize_text_field($request->get_param('branch')); // applicant's nursery pick (general form)
  $msg    = sanitize_textarea_field($request->get_param('about'));

  // 4. Validate required fields
  if (empty($first) || empty($last)) {
    return new WP_Error('missing_name', 'First and last name are required.', ['status' => 400]);
  }
  if (empty($email) || !is_email($email)) {
    return new WP_Error('invalid_email', 'A valid email address is required.', ['status' => 400]);
  }
  if (is_wp_error($phone)) {
    return $phone;
  }

  // 5. Build human-readable post title and job label
  $date      = current_time('Y-m-d');
  $job_label = !empty($title) ? $title : (!empty($slug) && $slug !== 'general' ? $slug : 'General Application');
  $pos_store = !empty($pos) ? $pos : $job_label;
  // Store the branch as its friendly display name (routing accepts slug or name).
  $branch_name = ($branch && function_exists('am_branch_label')) ? (am_branch_label($branch)['name'] ?: $branch) : $branch;

  $post_id = wp_insert_post([
    'post_type'   => 'am_application',
    'post_status' => 'private',
    'post_title'  => "{$first} {$last} \u{2014} {$job_label} \u{2014} {$date}",
  ]);

  if (is_wp_error($post_id)) {
    return new WP_Error('save_failed', 'Could not save the application. Please try again.', ['status' => 500]);
  }

  // 6. Save all meta
  $meta = [
    '_am_app_first_name'    => $first,
    '_am_app_last_name'     => $last,
    '_am_app_email'         => $email,
    '_am_app_phone'         => $phone,
    '_am_app_qualification' => $qual,
    '_am_app_position'      => $pos_store,
    '_am_app_branch'        => $branch_name,
    '_am_app_job_slug'      => $slug ?: 'general',
    '_am_app_job_title'     => $title,
    '_am_app_source'        => 'Careers form',
    '_am_app_message'       => $msg,
    '_am_app_owner'         => 0,
    '_am_app_notes'         => '',
    '_am_app_status'        => 'new',
    '_am_app_submitted_at'  => current_time('c'),
  ];
  foreach ($meta as $key => $val) {
    update_post_meta($post_id, $key, $val);
  }

  // 6b. Store the CV outside the public editorial Media Library.
  $cv_path = '';
  $stored_cv = am_store_private_application_file($_FILES['resume'] ?? [], $post_id);
  if (is_wp_error($stored_cv)) {
    wp_delete_post($post_id, true);
    return $stored_cv;
  }
  update_post_meta($post_id, '_am_app_cv_file', $stored_cv['stored_name']);
  update_post_meta($post_id, '_am_app_cv_name', $stored_cv['original_name']);
  update_post_meta($post_id, '_am_app_cv_mime', $stored_cv['mime']);
  update_post_meta($post_id, '_am_app_cv_size', $stored_cv['size']);
  $cv_path = $stored_cv['path'];

  // 7. Send notification email (with the CV attached when present)
  am_send_application_email($post_id, $meta, $cv_path);

  return rest_ensure_response(['success' => true]);
}

// ===================================================================
// Email notification helpers
// ===================================================================

/**
 * Work out which branch inbox a job application should be routed to in
 * production. Priority (most specific first):
 *   1. The vacancy's own "Apply email" override (_am_job_apply_email)
 *   2. The vacancy's "Location / Nursery" branch
 *   3. The nursery the applicant picked on the general form (_am_app_branch)
 *   4. '' → the site default (main info@ inbox, via am_notify_email())
 */
function am_application_route_email($meta) {
  $slug = $meta['_am_app_job_slug'] ?? '';

  // 1 + 2: a specific vacancy — honour its apply-email, then its Location.
  if ($slug && $slug !== 'general' && function_exists('get_page_by_path')) {
    $job = get_page_by_path($slug, OBJECT, 'am_job');
    if ($job) {
      $apply_email = get_post_meta($job->ID, '_am_job_apply_email', true);
      if (is_email($apply_email)) {
        return $apply_email;
      }
      $location = get_post_meta($job->ID, '_am_job_location', true);
      if ($location && strcasecmp($location, 'All Nurseries') !== 0 && function_exists('am_branch_label')) {
        $info = am_branch_label($location);
        if (!empty($info['email'])) return $info['email'];
      }
    }
  }

  // 3: general application where the applicant chose a nursery.
  $branch = $meta['_am_app_branch'] ?? '';
  if ($branch && function_exists('am_branch_label')) {
    $info = am_branch_label($branch);
    if (!empty($info['email'])) return $info['email'];
  }

  return ''; // → site default (info@)
}

function am_send_application_email($post_id, $meta, $cv_path = '') {
  $route    = am_application_route_email($meta);
  $to       = am_notify_email($route);
  $first    = $meta['_am_app_first_name'];
  $last     = $meta['_am_app_last_name'];
  $job      = $meta['_am_app_job_title'] ?: ($meta['_am_app_job_slug'] === 'general' ? 'General Application' : $meta['_am_app_job_slug']);
  $subject  = "New Application \u{2013} {$job} \u{2013} {$first} {$last}";
  $headers  = [
    'Content-Type: text/html; charset=UTF-8',
    'Reply-To: ' . $meta['_am_app_email'],
  ];
  $attachments = ($cv_path && file_exists($cv_path)) ? [$cv_path] : [];

  am_send_mail_async($to, $subject, am_build_email_body($post_id, $meta, $route), $headers, $attachments);
}

function am_build_email_body($post_id, $meta, $route = '') {
  $admin_url = admin_url("post.php?post={$post_id}&action=edit");
  $routed_to = $route ?: 'info@alexandramontessori.co.uk (general)';

  $rows = [
    ['Name',          $meta['_am_app_first_name'] . ' ' . $meta['_am_app_last_name']],
    ['Email',         '<a href="mailto:' . esc_attr($meta['_am_app_email']) . '">' . esc_html($meta['_am_app_email']) . '</a>'],
    ['Phone',         $meta['_am_app_phone'] ?: '—'],
    ['Qualification', $meta['_am_app_qualification'] ?: '—'],
    ['Position',      $meta['_am_app_position'] ?: '—'],
    ['Job',           $meta['_am_app_job_title'] ?: ($meta['_am_app_job_slug'] === 'general' ? 'General Application' : esc_html($meta['_am_app_job_slug']))],
    ['Nursery',       !empty($meta['_am_app_branch']) ? esc_html($meta['_am_app_branch']) : '—'],
    ['Routed to (production)', esc_html($routed_to)],
    ['Submitted',     $meta['_am_app_submitted_at']],
  ];

  $table = '<table style="border-collapse:collapse;width:100%;font-family:sans-serif;font-size:14px;">';
  foreach ($rows as [$label, $value]) {
    $table .= '<tr>'
      . '<td style="padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:600;white-space:nowrap;">' . esc_html($label) . '</td>'
      . '<td style="padding:8px 12px;border:1px solid #ddd;">' . $value . '</td>'
      . '</tr>';
  }
  if (!empty($meta['_am_app_message'])) {
    $table .= '<tr>'
      . '<td style="padding:8px 12px;border:1px solid #ddd;background:#f9f9f9;font-weight:600;vertical-align:top;">Message</td>'
      . '<td style="padding:8px 12px;border:1px solid #ddd;">' . nl2br(esc_html($meta['_am_app_message'])) . '</td>'
      . '</tr>';
  }
  $table .= '</table>';

  return '<p style="font-family:sans-serif;font-size:14px;">A new job application has been submitted via the Alexandra Montessori website.</p>'
    . $table;
}
