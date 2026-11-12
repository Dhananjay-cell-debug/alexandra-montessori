<?php
/**
 * Unified operational inbox for all website form submissions.
 */

if (!defined('ABSPATH')) exit;

const LDRN_SUBMISSIONS_MENU_SLUG = 'ldrn_submissions';

function ldrn_submission_config($post_type = '') {
  $all = [
    'ldrn_enquiry' => [
      'label' => 'Contact & Visits',
      'singular' => 'Contact / visit request',
      'status_key' => '_ldrn_enq_status',
      'owner_key' => '_ldrn_enq_owner',
      'email_key' => '_ldrn_enq_email',
      'branch_key' => '_ldrn_enq_branch',
      'statuses' => [
        'new' => 'New',
        'in_progress' => 'In progress',
        'replied' => 'Replied',
        'closed' => 'Closed',
      ],
      'resolved' => ['replied', 'closed'],
    ],
    'ldrn_availability' => [
      'label' => 'Availability',
      'singular' => 'Availability request',
      'status_key' => '_ldrn_avl_status',
      'owner_key' => '_ldrn_avl_owner',
      'email_key' => '_ldrn_avl_email',
      'branch_key' => '_ldrn_avl_branch',
      'statuses' => [
        'new' => 'New',
        'reviewing' => 'Reviewing',
        'contacted' => 'Contacted',
        'waitlisted' => 'Waitlisted',
        'placed' => 'Placed',
        'closed' => 'Closed',
      ],
      'resolved' => ['placed', 'closed'],
    ],
    'ldrn_application' => [
      'label' => 'Careers',
      'singular' => 'Career application',
      'status_key' => '_ldrn_app_status',
      'owner_key' => '_ldrn_app_owner',
      'email_key' => '_ldrn_app_email',
      'branch_key' => '',
      'statuses' => [
        'new' => 'New',
        'reviewed' => 'Reviewed',
        'contacted' => 'Contacted',
        'interview' => 'Interview',
        'offered' => 'Offer made',
        'hired' => 'Hired',
        'rejected' => 'Not selected',
        'closed' => 'Closed',
      ],
      'resolved' => ['hired', 'rejected', 'closed'],
    ],
  ];
  return $post_type === '' ? $all : ($all[$post_type] ?? null);
}

function ldrn_submission_owner_options() {
  $options = [0 => 'Unassigned'];
  $users = get_users([
    'role__in' => ['administrator', LDRN_CONTENT_MANAGER_ROLE],
    'orderby' => 'display_name',
    'order' => 'ASC',
  ]);
  foreach ($users as $user) {
    $options[$user->ID] = $user->display_name;
  }
  return $options;
}

function ldrn_submission_status_counts($post_type) {
  global $wpdb;
  $config = ldrn_submission_config($post_type);
  if (!$config) return [];

  $sql = $wpdb->prepare(
    "SELECT COALESCE(NULLIF(pm.meta_value, ''), 'new') AS workflow_status, COUNT(DISTINCT p.ID) AS total
     FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->postmeta} pm
       ON pm.post_id = p.ID AND pm.meta_key = %s
     WHERE p.post_type = %s AND p.post_status = 'private'
     GROUP BY workflow_status",
    $config['status_key'],
    $post_type
  );
  $counts = array_fill_keys(array_keys($config['statuses']), 0);
  foreach ($wpdb->get_results($sql) as $row) {
    $counts[$row->workflow_status] = (int) $row->total;
  }
  return $counts;
}

function ldrn_submission_unhandled_total() {
  $total = 0;
  foreach (array_keys(ldrn_submission_config()) as $post_type) {
    $counts = ldrn_submission_status_counts($post_type);
    $total += (int) ($counts['new'] ?? 0);
  }
  return $total;
}

function ldrn_submission_source_label($post_id) {
  $post_type = get_post_type($post_id);
  if ($post_type === 'ldrn_enquiry') {
    return get_post_meta($post_id, '_ldrn_enq_kind', true) === 'booking'
      ? 'Book a visit'
      : 'Contact form';
  }
  if ($post_type === 'ldrn_availability') return 'Check availability';
  if ($post_type === 'ldrn_application') return 'Careers form';
  return 'Website form';
}

function ldrn_submission_owner_label($post_id, $owner_key) {
  $owner_id = absint(get_post_meta($post_id, $owner_key, true));
  $owner = $owner_id ? get_userdata($owner_id) : null;
  return $owner ? $owner->display_name : 'Unassigned';
}

function ldrn_gmail_compose_url($email, $subject = '', $body = '') {
  $args = [
    'view' => 'cm',
    'fs' => '1',
    'to' => sanitize_email($email),
  ];
  if ($subject !== '') $args['su'] = $subject;
  if ($body !== '') $args['body'] = $body;
  return add_query_arg($args, 'https://mail.google.com/mail/');
}

function ldrn_register_submissions_menu() {
  add_menu_page(
    'Submissions',
    'Submissions',
    'edit_private_posts',
    LDRN_SUBMISSIONS_MENU_SLUG,
    'ldrn_render_submissions_overview',
    'dashicons-clipboard',
    24
  );
  add_submenu_page(
    null,
    'Reply to customer',
    'Reply to customer',
    'edit_private_posts',
    'ldrn_reply_submission',
    'ldrn_render_submission_reply_page'
  );
}
add_action('admin_menu', 'ldrn_register_submissions_menu', 8);

function ldrn_add_submissions_menu_badge() {
  global $menu;
  $count = ldrn_submission_unhandled_total();
  if (!$count) return;
  foreach ($menu as &$item) {
    if (($item[2] ?? '') === LDRN_SUBMISSIONS_MENU_SLUG) {
      $item[0] .= ' <span class="awaiting-mod count-' . (int) $count . '"><span class="pending-count">' . (int) $count . '</span></span>';
      break;
    }
  }
}
add_action('admin_menu', 'ldrn_add_submissions_menu_badge', 999);

function ldrn_submission_reply_subject($post_id) {
  return 'Re: ' . ldrn_submission_source_label($post_id) . ' - Little Rainbows Day Nursery';
}

function ldrn_submission_customer_email($post_id) {
  $config = ldrn_submission_config(get_post_type($post_id));
  return $config ? sanitize_email(get_post_meta($post_id, $config['email_key'], true)) : '';
}

function ldrn_mark_submission_opened() {
  // Opening a record must be a pure read for the section-allocated Viewer.
  if (function_exists('ldrn_is_viewer') && ldrn_is_viewer()) return;

  $post_id = absint($_GET['post'] ?? 0);
  if (!$post_id || sanitize_key($_GET['action'] ?? '') !== 'edit') return;
  $post = get_post($post_id);
  $config = $post ? ldrn_submission_config($post->post_type) : null;
  if (!$post || !$config || !current_user_can('edit_post', $post_id)) return;

  $status = get_post_meta($post_id, $config['status_key'], true) ?: 'new';
  if ($status !== 'new') return;
  $next_status = [
    'ldrn_enquiry' => 'in_progress',
    'ldrn_availability' => 'reviewing',
    'ldrn_application' => 'reviewed',
  ][$post->post_type];

  update_post_meta($post_id, $config['status_key'], $next_status);
  if (!absint(get_post_meta($post_id, $config['owner_key'], true))) {
    update_post_meta($post_id, $config['owner_key'], get_current_user_id());
  }
  $GLOBALS['ldrn_writing_communication'] = true;
  update_post_meta($post_id, '_ldrn_first_viewed_at', current_time('c'));
  update_post_meta($post_id, '_ldrn_first_viewed_by', get_current_user_id());
  unset($GLOBALS['ldrn_writing_communication']);
}
add_action('load-post.php', 'ldrn_mark_submission_opened');

function ldrn_submission_communications($post_id) {
  $history = get_post_meta($post_id, '_ldrn_submission_communications', true);
  return is_array($history) ? $history : [];
}

function ldrn_register_submission_reply_box() {
  foreach (array_keys(ldrn_submission_config()) as $post_type) {
    $title = $post_type === 'ldrn_application' ? 'Reply to applicant' : 'Reply to family';
    add_meta_box(
      'ldrn_customer_reply',
      $title,
      'ldrn_render_submission_reply_box',
      $post_type,
      'side',
      'high'
    );
  }
}
add_action('add_meta_boxes', 'ldrn_register_submission_reply_box');

function ldrn_render_submission_reply_box($post) {
  $email = ldrn_submission_customer_email($post->ID);
  $history = ldrn_submission_communications($post->ID);
  $reply_url = add_query_arg([
    'page' => 'ldrn_reply_submission',
    'post_id' => $post->ID,
  ], admin_url('admin.php'));
  echo '<p>Replies go only to the immutable customer address:</p>';
  echo '<p><strong>' . esc_html($email ?: 'No valid email saved') . '</strong></p>';
  if ($email) {
    $button = $post->post_type === 'ldrn_application' ? 'Reply to this applicant' : 'Reply to this family';
    echo '<p><a class="button button-primary" href="' . esc_url($reply_url) . '">' . esc_html($button) . '</a></p>';
    echo '<p><a href="' . esc_url(ldrn_gmail_compose_url($email, ldrn_submission_reply_subject($post->ID))) . '" target="_blank" rel="noopener noreferrer">Open this recipient in Gmail</a></p>';
  }
  if ($history) {
    $last = end($history);
    echo '<hr><p><strong>Last outbound:</strong><br>' . esc_html($last['sent_at'] ?? '') . '<br>' . esc_html(ucfirst($last['result'] ?? 'recorded')) . '</p>';
  } else {
    echo '<hr><p><em>No reply has been sent from WordPress.</em></p>';
  }
  $viewed_at = get_post_meta($post->ID, '_ldrn_first_viewed_at', true);
  $viewed_by = get_userdata(absint(get_post_meta($post->ID, '_ldrn_first_viewed_by', true)));
  if ($viewed_at) {
    echo '<hr><p class="description">First opened ' . esc_html($viewed_at) . ($viewed_by ? ' by ' . esc_html($viewed_by->display_name) : '') . '.</p>';
  }
}

function ldrn_render_submission_reply_page() {
  $post_id = absint($_GET['post_id'] ?? 0);
  $post = get_post($post_id);
  $config = $post ? ldrn_submission_config($post->post_type) : null;
  if (!$post || !$config || !current_user_can('edit_post', $post_id)) wp_die('Submission not found or access denied.');

  $email = ldrn_submission_customer_email($post_id);
  if (!$email) wp_die('This submission does not contain a valid recipient email.');
  $history = array_reverse(ldrn_submission_communications($post_id));
  $subject = ldrn_submission_reply_subject($post_id);
  $legacy_draft = !$history ? trim(wp_strip_all_tags($post->post_content)) : '';
  if ($history && ($history[0]['result'] ?? '') === 'failed') {
    $subject = $history[0]['subject'] ?? $subject;
    $legacy_draft = $history[0]['message'] ?? '';
  }
  $result = sanitize_key($_GET['reply_result'] ?? '');

  echo '<div class="wrap ldrn-reply-wrap"><h1>Reply to customer</h1>';
  echo '<p><a href="' . esc_url(get_edit_post_link($post_id)) . '">&larr; Back to submission</a></p>';
  if ($result === 'sent') echo '<div class="notice notice-success inline"><p>Reply accepted by the configured WordPress mail transport and recorded below.</p></div>';
  if ($result === 'failed') echo '<div class="notice notice-error inline"><p>The mail transport rejected this reply. It was recorded as failed; use Gmail or fix SMTP before retrying.</p></div>';
  echo '<div class="ldrn-reply-recipient"><span>Private recipient</span><strong>' . esc_html($email) . '</strong><small>Locked to the address submitted by this customer.</small></div>';
  echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="ldrn-reply-form">';
  wp_nonce_field('ldrn_send_submission_reply_' . $post_id);
  echo '<input type="hidden" name="action" value="ldrn_send_submission_reply"><input type="hidden" name="post_id" value="' . (int) $post_id . '">';
  echo '<label><strong>Subject</strong><input class="large-text" type="text" name="subject" value="' . esc_attr($subject) . '" required maxlength="180"></label>';
  echo '<label><strong>Message</strong><textarea class="large-text" name="message" rows="12" required>' . esc_textarea($legacy_draft) . '</textarea></label>';
  echo '<p class="description">This sends to the single address above and stores an audit entry on this private submission. Local sends are captured by the Local mail environment; production requires authenticated SMTP for dependable delivery.</p>';
  echo '<p><button type="submit" class="button button-primary button-hero">Send private email</button> <a id="ldrn-gmail-compose" class="button button-hero" href="' . esc_url(ldrn_gmail_compose_url($email, $subject, $legacy_draft)) . '" target="_blank" rel="noopener noreferrer">Compose in Gmail instead</a></p>';
  echo '</form>';
  echo '<script>(function(){const form=document.querySelector(".ldrn-reply-form");const link=document.getElementById("ldrn-gmail-compose");if(!form||!link)return;const sync=function(){const url=new URL("https://mail.google.com/mail/");url.searchParams.set("view","cm");url.searchParams.set("fs","1");url.searchParams.set("to",' . wp_json_encode($email) . ');url.searchParams.set("su",form.elements.subject.value);url.searchParams.set("body",form.elements.message.value);link.href=url.toString();};form.elements.subject.addEventListener("input",sync);form.elements.message.addEventListener("input",sync);sync();}());</script>';

  echo '<h2>Communication history</h2>';
  if (!$history) {
    echo '<p>No outbound replies recorded yet.</p>';
  } else {
    foreach ($history as $entry) {
      $author = get_userdata(absint($entry['sent_by'] ?? 0));
      echo '<article class="ldrn-communication-entry"><header><strong>' . esc_html($entry['subject'] ?? '') . '</strong><span>' . esc_html($entry['sent_at'] ?? '') . ' by ' . esc_html($author ? $author->display_name : 'Unknown user') . '</span><span class="ldrn-status ldrn-status-' . esc_attr(sanitize_html_class($entry['result'] ?? 'recorded')) . '">' . esc_html(ucfirst($entry['result'] ?? 'recorded')) . '</span></header><p>' . nl2br(esc_html($entry['message'] ?? '')) . '</p></article>';
    }
  }
  echo '</div>';
}

function ldrn_send_submission_reply() {
  $post_id = absint($_POST['post_id'] ?? 0);
  $post = get_post($post_id);
  $config = $post ? ldrn_submission_config($post->post_type) : null;
  if (!$post || !$config || !current_user_can('edit_post', $post_id)) wp_die('Access denied.');
  check_admin_referer('ldrn_send_submission_reply_' . $post_id);

  $email = ldrn_submission_customer_email($post_id);
  $subject = sanitize_text_field(wp_unslash($_POST['subject'] ?? ''));
  $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
  if (!$email || $subject === '' || $message === '') wp_die('Recipient, subject and message are required.');

  $settings = function_exists('ldrn_get_site_settings') ? ldrn_get_site_settings() : [];
  $from_email = sanitize_email($settings['email'] ?? get_option('admin_email'));
  $headers = ['Content-Type: text/plain; charset=UTF-8'];
  if ($from_email) {
    $headers[] = 'From: Little Rainbows Day Nursery <' . $from_email . '>';
    $headers[] = 'Reply-To: ' . $from_email;
  }

  $failure = '';
  $failure_listener = function ($error) use (&$failure) {
    $failure = $error instanceof WP_Error ? $error->get_error_message() : 'Unknown mail error';
  };
  add_action('wp_mail_failed', $failure_listener);
  $sent = wp_mail($email, $subject, $message, $headers);
  remove_action('wp_mail_failed', $failure_listener);

  $history = ldrn_submission_communications($post_id);
  $history[] = [
    'direction' => 'outbound',
    'sent_at' => current_time('c'),
    'sent_by' => get_current_user_id(),
    'recipient' => $email,
    'subject' => $subject,
    'message' => $message,
    'transport' => 'wordpress_mail',
    'result' => $sent ? 'sent' : 'failed',
    'error' => $sent ? '' : $failure,
  ];
  $GLOBALS['ldrn_writing_communication'] = true;
  update_post_meta($post_id, '_ldrn_submission_communications', $history);
  unset($GLOBALS['ldrn_writing_communication']);

  if ($sent) {
    $next_status = [
      'ldrn_enquiry' => 'replied',
      'ldrn_availability' => 'contacted',
      'ldrn_application' => 'contacted',
    ][$post->post_type];
    update_post_meta($post_id, $config['status_key'], $next_status);
  }

  $redirect = add_query_arg([
    'page' => 'ldrn_reply_submission',
    'post_id' => $post_id,
    'reply_result' => $sent ? 'sent' : 'failed',
  ], admin_url('admin.php'));
  wp_safe_redirect($redirect);
  exit;
}
add_action('admin_post_ldrn_send_submission_reply', 'ldrn_send_submission_reply');

function ldrn_render_submissions_overview() {
  if (!current_user_can('edit_private_posts')) wp_die('Access denied.');

  echo '<div class="wrap ldrn-submissions-wrap"><h1>Submissions</h1>';
  echo '<p class="description">One operational inbox for every website form. Customer-entered details stay read-only; staff update only the owner, internal notes and workflow status.</p>';
  echo '<div class="ldrn-submission-queues">';
  foreach (ldrn_submission_config() as $post_type => $config) {
    $counts = ldrn_submission_status_counts($post_type);
    $total = array_sum($counts);
    $new = (int) ($counts['new'] ?? 0);
    $active = $total;
    foreach ($config['resolved'] as $status) $active -= (int) ($counts[$status] ?? 0);
    $list_url = admin_url('edit.php?post_type=' . $post_type);
    $export_url = wp_nonce_url(
      admin_url('admin-post.php?action=ldrn_export_submissions&post_type=' . $post_type),
      'ldrn_export_submissions_' . $post_type
    );
    echo '<section class="ldrn-submission-queue">';
    echo '<div><span class="ldrn-queue-kicker">' . esc_html($config['label']) . '</span><strong>' . (int) $new . ' new</strong></div>';
    echo '<p>' . (int) $active . ' active / ' . (int) $total . ' total</p>';
    echo '<p class="ldrn-queue-actions"><a class="button button-primary" href="' . esc_url($list_url) . '">Open queue</a> <a class="button" href="' . esc_url($export_url) . '">Export CSV</a></p>';
    echo '</section>';
  }
  echo '</div>';

  $recent = get_posts([
    'post_type' => array_keys(ldrn_submission_config()),
    'post_status' => 'private',
    'numberposts' => 20,
    'orderby' => 'date',
    'order' => 'DESC',
  ]);
  echo '<h2>Most recent</h2>';
  if (!$recent) {
    echo '<div class="notice notice-info inline"><p>No submissions yet. New website forms will appear here immediately, even if an email notification fails.</p></div>';
  } else {
    echo '<table class="widefat striped"><thead><tr><th>Received</th><th>Source</th><th>Person</th><th>Email</th><th>Owner</th><th>Status</th></tr></thead><tbody>';
    foreach ($recent as $post) {
      $config = ldrn_submission_config($post->post_type);
      $status = get_post_meta($post->ID, $config['status_key'], true) ?: 'new';
      $email = get_post_meta($post->ID, $config['email_key'], true);
      echo '<tr>';
      echo '<td>' . esc_html(get_the_date('d M Y, H:i', $post)) . '</td>';
      echo '<td>' . esc_html(ldrn_submission_source_label($post->ID)) . '</td>';
      echo '<td><a href="' . esc_url(get_edit_post_link($post->ID)) . '">' . esc_html($post->post_title) . '</a></td>';
      echo '<td><a href="' . esc_url(ldrn_gmail_compose_url($email)) . '" target="_blank" rel="noopener noreferrer">' . esc_html($email) . '</a></td>';
      echo '<td>' . esc_html(ldrn_submission_owner_label($post->ID, $config['owner_key'])) . '</td>';
      echo '<td><span class="ldrn-status ldrn-status-' . esc_attr(sanitize_html_class($status)) . '">' . esc_html($config['statuses'][$status] ?? ucfirst($status)) . '</span></td>';
      echo '</tr>';
    }
    echo '</tbody></table>';
  }

  echo '<div class="ldrn-submission-policy"><h2>Operating rules</h2><p><strong>Daily:</strong> assign each new record, make contact, add a concise internal note, then change its status. <strong>Privacy:</strong> CSV exports contain personal data and must be stored securely. Applicant CVs remain private and never appear in Media. <strong>Retention:</strong> automatic deletion is intentionally disabled until the client approves a written retention period.</p></div>';
  echo '</div>';
}

function ldrn_submission_filters($post_type) {
  $config = ldrn_submission_config($post_type);
  if (!$config) return;

  $selected_status = sanitize_key($_GET['ldrn_workflow_status'] ?? '');
  echo '<select name="ldrn_workflow_status"><option value="">All workflow statuses</option>';
  foreach ($config['statuses'] as $value => $label) {
    echo '<option value="' . esc_attr($value) . '" ' . selected($selected_status, $value, false) . '>' . esc_html($label) . '</option>';
  }
  echo '</select>';

  if ($post_type === 'ldrn_enquiry') {
    $kind = sanitize_key($_GET['ldrn_submission_kind'] ?? '');
    echo '<select name="ldrn_submission_kind"><option value="">Contact and visit types</option>';
    echo '<option value="contact" ' . selected($kind, 'contact', false) . '>Contact form</option>';
    echo '<option value="booking" ' . selected($kind, 'booking', false) . '>Book a visit</option></select>';
  }

  $owner_id = isset($_GET['ldrn_submission_owner']) && $_GET['ldrn_submission_owner'] !== ''
    ? (string) absint($_GET['ldrn_submission_owner'])
    : '';
  echo '<select name="ldrn_submission_owner"><option value="" ' . selected($owner_id, '', false) . '>All owners</option>';
  foreach (ldrn_submission_owner_options() as $id => $label) {
    echo '<option value="' . (int) $id . '" ' . selected($owner_id, (string) $id, false) . '>' . esc_html($label) . '</option>';
  }
  echo '</select>';
}
add_action('restrict_manage_posts', 'ldrn_submission_filters');

function ldrn_filter_submission_queue($query) {
  if (!is_admin() || !$query->is_main_query()) return;
  $post_type = $query->get('post_type');
  $config = ldrn_submission_config($post_type);
  if (!$config) return;

  $meta_query = (array) $query->get('meta_query');
  $status = sanitize_key($_GET['ldrn_workflow_status'] ?? '');
  if ($status && isset($config['statuses'][$status])) {
    $meta_query[] = ['key' => $config['status_key'], 'value' => $status];
  }
  $kind = sanitize_key($_GET['ldrn_submission_kind'] ?? '');
  if ($post_type === 'ldrn_enquiry' && in_array($kind, ['contact', 'booking'], true)) {
    $meta_query[] = ['key' => '_ldrn_enq_kind', 'value' => $kind];
  }
  if (isset($_GET['ldrn_submission_owner']) && $_GET['ldrn_submission_owner'] !== '') {
    $meta_query[] = ['key' => $config['owner_key'], 'value' => absint($_GET['ldrn_submission_owner'])];
  }
  if ($meta_query) $query->set('meta_query', $meta_query);
}
add_action('pre_get_posts', 'ldrn_filter_submission_queue');

function ldrn_submission_bulk_actions($actions, $post_type) {
  $config = ldrn_submission_config($post_type);
  if (!$config) return $actions;
  foreach ($config['statuses'] as $status => $label) {
    $actions['ldrn_status_' . $status] = 'Set status: ' . $label;
  }
  return $actions;
}

function ldrn_register_submission_bulk_actions() {
  foreach (array_keys(ldrn_submission_config()) as $post_type) {
    add_filter('bulk_actions-edit-' . $post_type, function ($actions) use ($post_type) {
      return ldrn_submission_bulk_actions($actions, $post_type);
    });
    add_filter('handle_bulk_actions-edit-' . $post_type, function ($redirect, $action, $post_ids) use ($post_type) {
      if (!str_starts_with($action, 'ldrn_status_')) return $redirect;
      $status = substr($action, strlen('ldrn_status_'));
      $config = ldrn_submission_config($post_type);
      if (!isset($config['statuses'][$status])) return $redirect;
      $updated = 0;
      foreach ($post_ids as $post_id) {
        if (get_post_type($post_id) !== $post_type || !current_user_can('edit_post', $post_id)) continue;
        update_post_meta($post_id, $config['status_key'], $status);
        $updated++;
      }
      return add_query_arg('ldrn_workflow_updated', $updated, $redirect);
    }, 10, 3);
  }
}
add_action('admin_init', 'ldrn_register_submission_bulk_actions');

function ldrn_submission_bulk_notice() {
  $updated = absint($_GET['ldrn_workflow_updated'] ?? 0);
  if (!$updated) return;
  echo '<div class="notice notice-success is-dismissible"><p>' . (int) $updated . ' submission(s) updated.</p></div>';
}
add_action('admin_notices', 'ldrn_submission_bulk_notice');

function ldrn_submission_row_actions($actions, $post) {
  if (!ldrn_submission_config($post->post_type)) return $actions;
  unset($actions['inline hide-if-no-js'], $actions['trash'], $actions['delete']);
  if (isset($actions['edit'])) $actions['edit'] = str_replace('Edit', 'Open', $actions['edit']);
  return $actions;
}
add_filter('post_row_actions', 'ldrn_submission_row_actions', 10, 2);

function ldrn_csv_safe($value) {
  $value = (string) $value;
  return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
}

function ldrn_export_submissions() {
  $post_type = sanitize_key($_GET['post_type'] ?? '');
  $config = ldrn_submission_config($post_type);
  if (!$config || !current_user_can('edit_private_posts')) wp_die('Access denied.');
  check_admin_referer('ldrn_export_submissions_' . $post_type);

  nocache_headers();
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="ldrn-' . sanitize_file_name($config['label']) . '-' . gmdate('Y-m-d') . '.csv"');
  $output = fopen('php://output', 'w');
  fwrite($output, "\xEF\xBB\xBF");

  $definitions = [
    'ldrn_enquiry' => [
      'headers' => ['Received', 'Type', 'First name', 'Last name', 'Email', 'Phone', 'Branch', 'Preferred visit date', 'Message', 'Owner', 'Status', 'Internal notes'],
      'keys' => ['_ldrn_enq_first_name', '_ldrn_enq_last_name', '_ldrn_enq_email', '_ldrn_enq_phone', '_ldrn_enq_branch', '_ldrn_enq_preferred_date', '_ldrn_enq_message', '_ldrn_enq_notes'],
    ],
    'ldrn_availability' => [
      'headers' => ['Received', 'Name', 'Email', 'Phone', 'Child age', 'Preferred nursery', 'Desired start', 'Days / sessions', 'Message', 'Owner', 'Status', 'Internal notes'],
      'keys' => ['_ldrn_avl_name', '_ldrn_avl_email', '_ldrn_avl_phone', '_ldrn_avl_child_age', '_ldrn_avl_branch', '_ldrn_avl_start', '_ldrn_avl_sessions', '_ldrn_avl_message', '_ldrn_avl_notes'],
    ],
    'ldrn_application' => [
      'headers' => ['Received', 'First name', 'Last name', 'Email', 'Phone', 'Qualification', 'Position', 'Job', 'Message', 'Owner', 'Status', 'Internal notes'],
      'keys' => ['_ldrn_app_first_name', '_ldrn_app_last_name', '_ldrn_app_email', '_ldrn_app_phone', '_ldrn_app_qualification', '_ldrn_app_position', '_ldrn_app_job_title', '_ldrn_app_message', '_ldrn_app_notes'],
    ],
  ];
  $definition = $definitions[$post_type];
  fputcsv($output, $definition['headers']);

  $page = 1;
  do {
    $records = new WP_Query([
      'post_type' => $post_type,
      'post_status' => 'private',
      'posts_per_page' => 500,
      'paged' => $page,
      'orderby' => 'date',
      'order' => 'DESC',
      'fields' => 'ids',
    ]);
    foreach ($records->posts as $post_id) {
      $row = [get_the_date('Y-m-d H:i:s', $post_id)];
      if ($post_type === 'ldrn_enquiry') $row[] = ldrn_submission_source_label($post_id);
      foreach ($definition['keys'] as $key) $row[] = get_post_meta($post_id, $key, true);
      array_splice($row, count($row) - 1, 0, [
        ldrn_submission_owner_label($post_id, $config['owner_key']),
        $config['statuses'][get_post_meta($post_id, $config['status_key'], true) ?: 'new'] ?? 'New',
      ]);
      fputcsv($output, array_map('ldrn_csv_safe', $row));
    }
    $page++;
  } while ($page <= $records->max_num_pages);

  fclose($output);
  exit;
}
add_action('admin_post_ldrn_export_submissions', 'ldrn_export_submissions');

function ldrn_submissions_admin_styles() {
  echo '<style>
    .ldrn-submissions-wrap{max-width:1200px}.ldrn-submissions-wrap>.description{max-width:850px;font-size:14px}
    .ldrn-submission-queues{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;margin:22px 0 28px}
    .ldrn-submission-queue{padding:20px;border:1px solid #dcdcde;border-top:4px solid #315b3a;background:#fff;box-shadow:0 1px 1px rgba(0,0,0,.04)}
    .ldrn-submission-queue>div{display:flex;align-items:center;justify-content:space-between;gap:12px}.ldrn-submission-queue strong{font-size:20px;color:#315b3a}
    .ldrn-queue-kicker{font-size:14px;font-weight:700}.ldrn-queue-actions{margin-bottom:0}.ldrn-submission-policy{max-width:900px;margin-top:28px;padding:18px 20px;border-left:4px solid #dba617;background:#fff}
    .ldrn-status{display:inline-block;padding:3px 8px;border-radius:999px;background:#e9ecef;font-weight:600}.ldrn-status-new{background:#f6d9d7;color:#8a2424}.ldrn-status-in_progress,.ldrn-status-reviewing,.ldrn-status-reviewed,.ldrn-status-contacted,.ldrn-status-interview,.ldrn-status-offered,.ldrn-status-waitlisted{background:#fff2c7;color:#6b5200}.ldrn-status-replied,.ldrn-status-placed,.ldrn-status-hired{background:#dcefdc;color:#245b2b}
  </style>';
}
add_action('admin_head', 'ldrn_submissions_admin_styles');
