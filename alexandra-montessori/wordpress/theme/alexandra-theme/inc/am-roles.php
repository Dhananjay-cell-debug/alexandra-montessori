<?php
/**
 * Client access model: Administrator plus section-allocated Editor and Viewer.
 *
 * - Administrator : unchanged WordPress admin. The only role that can assign
 *                   or revoke roles and section allocations at any time.
 * - Editor        : the existing `am_content_manager` role, simply relabelled
 *                   "Editor". Can change only Administrator-selected sections.
 * - Viewer        : can read only Administrator-selected sections. Every
 *                   create/update/publish/delete/upload is blocked server-side.
 *
 * The Editor role definition and the shared client-menu clean-up already live
 * in inc/am-admin.php; this file is required immediately after it.
 */

if (!defined('ABSPATH')) exit;

const AM_VIEWER_ROLE = 'am_viewer';
const AM_USER_SECTIONS_META = '_am_allowed_sections';

/** True when the given/current user is a read-only Viewer. */
function am_is_viewer($user = null) {
  $user = $user ?: wp_get_current_user();
  return $user && in_array(AM_VIEWER_ROLE, (array) $user->roles, true);
}

/** Exact WordPress sections an Administrator may allocate. */
function am_access_section_definitions() {
  return [
    'overview' => 'Overview',
    'nurseries' => 'Nurseries',
    'events' => 'Events',
    'blog' => 'Blog',
    'testimonials' => 'Testimonials',
    'jobs' => 'Jobs',
    'about' => 'About us',
    'media' => 'Media Library',
    'settings' => 'Site Settings',
    'submissions' => 'Submissions',
  ];
}

/** True for either restricted dashboard role. Administrators are never scoped. */
function am_is_allocated_user($user = null) {
  $user = $user ?: wp_get_current_user();
  if (!$user) return false;
  $roles = (array) $user->roles;
  return in_array(AM_CONTENT_MANAGER_ROLE, $roles, true)
    || in_array(AM_VIEWER_ROLE, $roles, true);
}

/**
 * True when a restricted user has an explicit Administrator-saved allocation.
 *
 * Accounts created before section allocation keep their legacy access until an
 * Administrator saves them. New/edited accounts always get explicit metadata.
 */
function am_access_has_explicit_sections($user = null) {
  $user = $user ?: wp_get_current_user();
  return $user
    && am_is_allocated_user($user)
    && metadata_exists('user', $user->ID, AM_USER_SECTIONS_META);
}

/** Return the exact sections assigned to an Editor or Viewer. */
function am_access_allowed_sections($user = null) {
  $user = $user ?: wp_get_current_user();
  if (!$user || !am_is_allocated_user($user)) return [];

  $all = array_keys(am_access_section_definitions());
  if (!am_access_has_explicit_sections($user)) {
    // Backward compatibility: Editors historically had everything, while the
    // old Viewer could read website content but not private Submissions.
    return am_is_viewer($user)
      ? array_values(array_diff($all, ['submissions']))
      : $all;
  }

  $saved = get_user_meta($user->ID, AM_USER_SECTIONS_META, true);
  if (!is_array($saved)) return [];
  return array_values(array_intersect($all, array_map('sanitize_key', $saved)));
}

/** Authoritative section check for the given/current Editor or Viewer. */
function am_access_can_section($section, $user = null) {
  $section = sanitize_key($section);
  return in_array($section, am_access_allowed_sections($user), true);
}

/** Human-readable labels for dashboard notices and audit output. */
function am_access_allowed_section_labels($user = null) {
  $definitions = am_access_section_definitions();
  return array_values(array_intersect_key(
    $definitions,
    array_flip(am_access_allowed_sections($user))
  ));
}

// ---------------------------------------------------------------------------
// Provisioning: relabel Editor + create the Viewer role. Schema-gated so it
// runs once, and re-runs only when this file's schema number is bumped.
// ---------------------------------------------------------------------------
function am_sync_access_roles() {
  if ((int) get_option('am_access_roles_schema', 0) >= 4) return;

  // (a) Relabel the content-manager role's display name to "Editor" without
  //     disturbing the accounts already assigned to it (the slug is unchanged).
  $wp_roles = wp_roles();
  if (isset($wp_roles->roles[AM_CONTENT_MANAGER_ROLE])
      && $wp_roles->roles[AM_CONTENT_MANAGER_ROLE]['name'] !== 'Editor') {
    $wp_roles->roles[AM_CONTENT_MANAGER_ROLE]['name'] = 'Editor';
    $wp_roles->role_names[AM_CONTENT_MANAGER_ROLE] = 'Editor';
    update_option($wp_roles->role_key, $wp_roles->roles);
  }

  // (b) The Viewer's capabilities are only enough to REACH and READ the client
  //     screens - deliberately no publish/delete/manage caps.
  $viewer_caps = [
    'read'                 => true,
    'edit_posts'           => true, // needed to open the content list screens
    'edit_others_posts'    => true,
    'edit_published_posts' => true,
    'edit_private_posts'   => true,
    'read_private_posts'   => true,
    'upload_files'         => true, // needed to browse the Media Library screen
    AM_SETTINGS_CAPABILITY => true, // needed to open the Site Settings screen
    'manage_am_submissions' => true, // read-only ops UI; writes are blocked below
    'view_am_private_files' => true, // read/download only when Submissions selected
  ];
  $viewer = get_role(AM_VIEWER_ROLE);
  if (!$viewer) {
    add_role(AM_VIEWER_ROLE, 'Viewer', $viewer_caps);
    $viewer = get_role(AM_VIEWER_ROLE);
  }
  if ($viewer) {
    foreach (array_keys($viewer->capabilities) as $cap) {
      $viewer->remove_cap($cap);
    }
    foreach ($viewer_caps as $cap => $granted) {
      $viewer->add_cap($cap, $granted);
    }
  }

  // (c) Migrate any account created during the superseded Blog-role prototype
  //     to the single Editor role with Blog + Media explicitly allocated.
  foreach (get_users(['role' => 'am_blog_editor']) as $legacy_blog_editor) {
    $legacy_blog_editor->set_role(AM_CONTENT_MANAGER_ROLE);
    update_user_meta(
      $legacy_blog_editor->ID,
      AM_USER_SECTIONS_META,
      ['blog', 'media']
    );
  }
  remove_role('am_blog_editor');

  update_option('am_access_roles_schema', 4, false);
}
add_action('init', 'am_sync_access_roles', 2); // after am_sync_content_manager_role (1)

// ---------------------------------------------------------------------------
// Only Administrator / Editor / Viewer are offered anywhere a role is chosen.
// ---------------------------------------------------------------------------
function am_limit_selectable_roles($roles) {
  // WordPress reverses this array when rendering the dropdown. Keep Viewer
  // last here so the least-privileged role is the safe first/default choice.
  $order = [
    'administrator',
    AM_CONTENT_MANAGER_ROLE,
    AM_VIEWER_ROLE,
  ];
  $limited = [];
  foreach ($order as $role) {
    if (isset($roles[$role])) $limited[$role] = $roles[$role];
  }
  return $limited;
}
add_filter('editable_roles', 'am_limit_selectable_roles');

/** Put the confirmed Role + Sections model beside the Add User form. */
function am_access_role_guide() {
  if (!current_user_can('create_users')) return;
  am_render_access_section_fields();
}
add_action('user_new_form', 'am_access_role_guide');

/** Render allocation checkboxes on Add User and Edit User. */
function am_render_access_section_fields($user = null) {
  if (!current_user_can('promote_users')) return;
  $selected = [];
  if ($user instanceof WP_User) {
    $selected = metadata_exists('user', $user->ID, AM_USER_SECTIONS_META)
      ? (array) get_user_meta($user->ID, AM_USER_SECTIONS_META, true)
      : (am_is_allocated_user($user) ? am_access_allowed_sections($user) : []);
  } elseif (isset($_POST['am_access_sections'])) {
    $selected = array_map('sanitize_key', (array) wp_unslash($_POST['am_access_sections']));
  }
  wp_nonce_field('am_save_access_sections', 'am_access_sections_nonce');
  echo '<div id="am-access-sections-panel">';
  echo '<h2>Sections</h2>';
  echo '<div class="am-access-panel-head"><div>';
  echo '<strong>Choose dashboard sections</strong>';
  echo '<p id="am-access-role-summary">Editor can change the selected sections.</p>';
  echo '</div><div class="am-access-role-key" aria-label="Role behavior">';
  echo '<span><b>Editor</b> can edit</span><span><b>Viewer</b> can view</span>';
  echo '</div></div>';
  echo '<fieldset class="am-access-section-grid">';
  foreach (am_access_section_definitions() as $key => $label) {
    $is_selected = in_array($key, $selected, true);
    echo '<label class="am-access-section-choice' . ($is_selected ? ' is-selected' : '') . '">';
    echo '<input type="checkbox" name="am_access_sections[]" value="' . esc_attr($key) . '" '
      . checked($is_selected, true, false) . '>';
    echo '<span>' . esc_html($label) . '</span></label>';
  }
  echo '</fieldset><div class="am-access-section-actions">';
  echo '<span id="am-access-section-count" aria-live="polite"></span>';
  echo '<div><button type="button" class="button" id="am-access-sections-all">Select all</button> ';
  echo '<button type="button" class="button" id="am-access-sections-none">Clear</button></div></div>';
  echo '<p class="am-access-empty-note">Leaving every section clear revokes all dashboard-section access without deleting the account.</p>';
  echo '</div>';
  ?>
  <style>
    #am-access-sections-panel {
      width: 100%;
      max-width: 980px;
      box-sizing: border-box;
    }
    #am-access-sections-panel .am-access-panel-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 18px;
      margin-bottom: 14px;
    }
    #am-access-sections-panel .am-access-panel-head strong {
      display: block;
      color: #1d2327;
      font-size: 14px;
      line-height: 1.4;
    }
    #am-access-sections-panel .am-access-panel-head p {
      margin: 3px 0 0;
      color: #646970;
    }
    #am-access-sections-panel .am-access-role-key {
      display: flex;
      flex-wrap: wrap;
      justify-content: flex-end;
      gap: 6px;
    }
    #am-access-sections-panel .am-access-role-key span {
      padding: 4px 9px;
      border-radius: 999px;
      background: #f0f0f1;
      color: #50575e;
      font-size: 12px;
      white-space: nowrap;
    }
    #am-access-sections-panel .am-access-section-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
      gap: 9px;
      min-width: 0;
      margin: 0;
      padding: 0;
      border: 0;
    }
    #am-access-sections-panel .am-access-section-choice {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 0;
      min-height: 44px;
      box-sizing: border-box;
      margin: 0;
      padding: 10px 12px;
      border: 1px solid #c3c4c7;
      border-radius: 6px;
      background: #fff;
      color: #2c3338;
      cursor: pointer;
      transition: border-color .12s ease, box-shadow .12s ease, background .12s ease;
    }
    #am-access-sections-panel .am-access-section-choice:hover {
      border-color: #72aee6;
      box-shadow: 0 0 0 1px #72aee6;
    }
    #am-access-sections-panel .am-access-section-choice.is-selected {
      border-color: #2271b1;
      background: #f0f6fc;
      box-shadow: inset 3px 0 0 #2271b1;
      color: #0a4b78;
    }
    #am-access-sections-panel .am-access-section-choice input[type="checkbox"] {
      flex: 0 0 18px;
      width: 18px !important;
      min-width: 18px !important;
      max-width: 18px !important;
      height: 18px !important;
      min-height: 18px !important;
      margin: 0 !important;
      padding: 0 !important;
    }
    #am-access-sections-panel .am-access-section-choice span {
      overflow: hidden;
      font-weight: 500;
      line-height: 1.35;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    #am-access-sections-panel .am-access-section-actions {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      margin-top: 12px;
    }
    #am-access-sections-panel #am-access-section-count {
      color: #50575e;
      font-size: 12px;
      font-weight: 600;
    }
    #am-access-sections-panel .am-access-empty-note {
      margin: 9px 0 0;
      color: #646970;
      font-size: 12px;
    }
    @media (max-width: 782px) {
      #am-access-sections-panel .am-access-panel-head {
        display: block;
      }
      #am-access-sections-panel .am-access-role-key {
        justify-content: flex-start;
        margin-top: 10px;
      }
      #am-access-sections-panel .am-access-section-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }
    @media (max-width: 480px) {
      #am-access-sections-panel .am-access-section-grid {
        grid-template-columns: 1fr;
      }
      #am-access-sections-panel .am-access-section-actions {
        align-items: flex-start;
        flex-direction: column;
      }
    }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var panel = document.getElementById('am-access-sections-panel');
      var role = document.querySelector('select[name="role"]');
      if (!panel || !role) return;
      var boxes = Array.from(panel.querySelectorAll('input[type="checkbox"]'));
      var count = document.getElementById('am-access-section-count');
      var summary = document.getElementById('am-access-role-summary');
      var sectionRow = null;
      var website = document.getElementById('url');
      var websiteRow = website ? website.closest('tr') : null;
      if (websiteRow) websiteRow.style.display = 'none';
      var roleRow = role.closest('tr');
      if (roleRow) {
        sectionRow = document.createElement('tr');
        sectionRow.className = 'form-field am-access-sections-row';
        var sectionHeading = document.createElement('th');
        sectionHeading.scope = 'row';
        sectionHeading.innerHTML = '<label>Sections</label>';
        var sectionCell = document.createElement('td');
        sectionRow.appendChild(sectionHeading);
        sectionRow.appendChild(sectionCell);
        roleRow.parentNode.insertBefore(sectionRow, roleRow.nextSibling);
        var heading = panel.querySelector('h2');
        if (heading) heading.remove();
        sectionCell.appendChild(panel);
        panel.style.margin = '0';
        panel.style.padding = '0';
        panel.style.border = '0';
        panel.style.background = 'transparent';
      }

      var syncChoices = function () {
        var selectedCount = 0;
        boxes.forEach(function (box) {
          var choice = box.closest('.am-access-section-choice');
          if (choice) choice.classList.toggle('is-selected', box.checked);
          if (box.checked) selectedCount += 1;
        });
        if (count) {
          count.textContent = selectedCount === 1
            ? '1 section selected'
            : selectedCount + ' sections selected';
        }
      };
      boxes.forEach(function (box) {
        box.addEventListener('change', syncChoices);
      });

      var toggle = function () {
        var usesSections = role.value === '<?php echo esc_js(AM_CONTENT_MANAGER_ROLE); ?>'
          || role.value === '<?php echo esc_js(AM_VIEWER_ROLE); ?>';
        if (summary) {
          summary.textContent = role.value === '<?php echo esc_js(AM_VIEWER_ROLE); ?>'
            ? 'Viewer can read the selected sections but cannot change anything.'
            : 'Editor can create and change content only in the selected sections.';
        }
        if (sectionRow) {
          sectionRow.style.display = usesSections ? '' : 'none';
          panel.style.display = 'block';
        } else {
          panel.style.display = usesSections ? 'block' : 'none';
        }
      };
      role.addEventListener('change', toggle);
      var selectAll = document.getElementById('am-access-sections-all');
      var clearAll = document.getElementById('am-access-sections-none');
      if (selectAll) selectAll.addEventListener('click', function () {
          boxes.forEach(function (box) { box.checked = true; });
          syncChoices();
        });
      if (clearAll) clearAll.addEventListener('click', function () {
          boxes.forEach(function (box) { box.checked = false; });
          syncChoices();
        });
      syncChoices();
      toggle();
    });
  </script>
  <?php
}
add_action('edit_user_profile', 'am_render_access_section_fields');

/** Do not allow a weak manually-entered password when an account is created. */
function am_require_strong_dashboard_password($errors, $update, $user) {
  if ($update || !current_user_can('create_users')) return;
  $role = sanitize_key($_POST['role'] ?? '');
  if (!in_array($role, ['administrator', AM_CONTENT_MANAGER_ROLE, AM_VIEWER_ROLE], true)) return;

  $password = (string) wp_unslash($_POST['pass1'] ?? '');
  $is_strong = strlen($password) >= 20
    && preg_match('/[a-z]/', $password)
    && preg_match('/[A-Z]/', $password)
    && preg_match('/\d/', $password)
    && preg_match('/[^a-zA-Z0-9]/', $password);
  if (!$is_strong) {
    $errors->add(
      'am_weak_password',
      'Use a password of at least 20 characters containing uppercase, lowercase, a number, and a symbol.'
    );
  }
}
add_action('user_profile_update_errors', 'am_require_strong_dashboard_password', 8, 3);

/** Reject a tampered allocation form; zero selected sections is valid revocation. */
function am_validate_access_sections($errors, $update, $user) {
  if (!current_user_can('promote_users')) return;
  $role = sanitize_key($_POST['role'] ?? '');
  if (!in_array($role, [AM_CONTENT_MANAGER_ROLE, AM_VIEWER_ROLE], true)) return;
  if (!isset($_POST['am_access_sections_nonce'])
      || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['am_access_sections_nonce'])), 'am_save_access_sections')) {
    $errors->add('am_access_sections_nonce', 'Section allocation could not be verified. Please try again.');
  }
}
add_action('user_profile_update_errors', 'am_validate_access_sections', 10, 3);

/** Persist the allocation after account creation or role/profile changes. */
function am_save_access_sections($user_id) {
  if (!current_user_can('promote_user', $user_id)
      && !current_user_can('create_users')) return;
  if (!isset($_POST['am_access_sections_nonce'])
      || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['am_access_sections_nonce'])), 'am_save_access_sections')) {
    return;
  }
  $user = get_userdata($user_id);
  if (!$user || !am_is_allocated_user($user)) return;
  $selected = array_values(array_intersect(
    array_keys(am_access_section_definitions()),
    array_map('sanitize_key', (array) ($_POST['am_access_sections'] ?? []))
  ));
  update_user_meta($user_id, AM_USER_SECTIONS_META, $selected);
}
add_action('user_register', 'am_save_access_sections');
add_action('profile_update', 'am_save_access_sections');

/** The first page inside the exact dashboard allocation given to a user. */
function am_access_user_landing_url($user) {
  $user = $user instanceof WP_User ? $user : get_userdata((int) $user);
  if (!$user) return admin_url();
  if (in_array('administrator', (array) $user->roles, true)) return admin_url();

  $targets = [
    'overview' => 'admin.php?page=' . AM_CONTENT_MENU_SLUG,
    'nurseries' => 'edit.php?post_type=am_nursery',
    'events' => 'edit.php?post_type=am_event',
    'blog' => 'edit.php?post_type=post',
    'testimonials' => 'edit.php?post_type=am_testimonial',
    'jobs' => 'edit.php?post_type=am_job',
    'about' => 'admin.php?page=am_about',
    'media' => 'upload.php',
    'settings' => 'admin.php?page=am_settings',
    'submissions' => 'admin.php?page=am_submissions',
  ];
  foreach (am_access_allowed_sections($user) as $section) {
    if (isset($targets[$section])) return admin_url($targets[$section]);
  }
  return admin_url('profile.php');
}

/**
 * Give the Administrator a matching audit summary for dashboard-created users.
 *
 * This also only formats WordPress's normal Add User administrator notice; it
 * does not introduce a second send trigger.
 */
function am_access_new_user_admin_email($email, $user, $blogname) {
  if (!($user instanceof WP_User)) return $email;

  $role_slug = sanitize_key($user->roles[0] ?? '');
  $role_names = [
    'administrator' => 'Administrator',
    AM_CONTENT_MANAGER_ROLE => 'Editor',
    AM_VIEWER_ROLE => 'Viewer',
  ];
  if (!isset($role_names[$role_slug])) return $email;

  $role_label = $role_names[$role_slug];
  $labels = $role_slug === 'administrator'
    ? ['All dashboard sections']
    : am_access_allowed_section_labels($user);
  $section_label = $labels ? implode(', ', $labels) : 'No sections';

  $email['subject'] = sprintf('[%s] New dashboard account: %s', $blogname, $user->user_login);
  $email['message'] = "A dashboard account was created from WordPress Users.\n\n"
    . "Username: {$user->user_login}\n"
    . "Email: {$user->user_email}\n"
    . "Role: {$role_label}\n"
    . "Sections: {$section_label}\n"
    . 'Allocated landing page: ' . am_access_user_landing_url($user) . "\n"
    . 'Manage this access: ' . admin_url('user-edit.php?user_id=' . (int) $user->ID) . "\n";
  return $email;
}
add_filter('wp_new_user_notification_email_admin', 'am_access_new_user_admin_email', 10, 3);

// ===========================================================================
// Users list helper: put the allocated landing page on the row so an
// Administrator can see/share where an account lands after login. Password
// issuance itself is handled entirely by the admin-only provisioning module
// (see wordpress/mu-plugins/alexandra-operations) - individuals never set or
// receive a self-service password link on this site.
// ===========================================================================

/** Add a "Dashboard link" column to Users so an Administrator can see/share it. */
function am_users_list_dashboard_link_column($columns) {
  $columns['am_dashboard_link'] = '<span title="Where this account lands after login. Clicking it opens in YOUR current session (so you\'ll see your own full menu, not theirs) - it is a preview/copy link to send them, not a way to view their account.">Dashboard link</span>';
  return $columns;
}
add_filter('manage_users_columns', 'am_users_list_dashboard_link_column');

function am_users_list_dashboard_link_render($value, $column_name, $user_id) {
  if ($column_name !== 'am_dashboard_link') return $value;
  $user = get_userdata($user_id);
  if (!$user) return $value;
  if (in_array('administrator', (array) $user->roles, true)) return 'Full admin access';
  if (!am_is_allocated_user($user)) return '—';
  // Clickable on purpose (copy/open/verify) - clicking it opens in whoever is
  // CURRENTLY logged in (you, the Administrator), never as this user. That's
  // normal link behavior, not a leak; see the column header tooltip.
  $url = am_access_user_landing_url($user);
  return '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($url) . '</a>';
}
add_filter('manage_users_custom_column', 'am_users_list_dashboard_link_render', 10, 3);

// ===========================================================================
// Editor/Viewer section enforcement (authoritative, server-side, fail-closed).
// ===========================================================================

/** Map posts and custom post types to allocatable sections. */
function am_access_section_for_post($post) {
  $post = is_object($post) ? $post : get_post($post);
  if (!$post) return '';
  if ($post->post_type === 'revision' && $post->post_parent) {
    return am_access_section_for_post($post->post_parent);
  }
  $map = [
    'post' => 'blog',
    'am_nursery' => 'nurseries',
    'am_event' => 'events',
    'am_testimonial' => 'testimonials',
    'am_job' => 'jobs',
    'attachment' => 'media',
    'am_enquiry' => 'submissions',
    'am_availability' => 'submissions',
    'am_application' => 'submissions',
  ];
  return $map[$post->post_type] ?? '';
}

/** Enforce section allocation for post, media, taxonomy, settings and ops caps. */
function am_access_scope_caps($caps, $cap, $user_id, $args) {
  $user = get_userdata($user_id);
  if (!$user || !am_is_allocated_user($user) || !am_access_has_explicit_sections($user)) {
    return $caps;
  }

  $simple_caps = [
    'upload_files' => 'media',
    'manage_categories' => 'blog',
    'manage_post_tags' => 'blog',
    'manage_am_submissions' => 'submissions',
    'reply_am_submissions' => 'submissions',
    'export_am_submissions' => 'submissions',
    'view_am_private_files' => 'submissions',
    'manage_am_ops_settings' => 'submissions',
  ];
  if (isset($simple_caps[$cap])
      && !am_access_can_section($simple_caps[$cap], $user)) {
    return ['do_not_allow'];
  }
  if ($cap === AM_SETTINGS_CAPABILITY
      && !am_access_can_section('about', $user)
      && !am_access_can_section('settings', $user)) {
    return ['do_not_allow'];
  }

  $post_meta_caps = [
    'edit_post', 'delete_post', 'read_post',
    'edit_post_meta', 'delete_post_meta', 'add_post_meta',
    'edit_post_thumbnail',
  ];
  if (in_array($cap, $post_meta_caps, true) && !empty($args[0])) {
    $section = am_access_section_for_post((int) $args[0]);
    if (!$section || !am_access_can_section($section, $user)) {
      return ['do_not_allow'];
    }
  }

  return $caps;
}
add_filter('map_meta_cap', 'am_access_scope_caps', 5, 4);

/** Identify the section required by a classic wp-admin request. */
function am_access_admin_request_section($pagenow, $request = []) {
  $pagenow = strtolower(basename(trim((string) $pagenow)));
  $request = is_array($request) ? $request : [];

  if ($pagenow === 'admin.php') {
    $page = sanitize_key($request['page'] ?? '');
    if ($page === AM_CONTENT_MENU_SLUG) return 'overview';
    if ($page === 'am_about') return 'about';
    if ($page === 'am_settings') return 'settings';
    if (str_starts_with($page, 'am_submission') || str_starts_with($page, 'am_ops')) return 'submissions';
    // The Visual Builder is not one of the ten dashboard sections - it is
    // allocated per page by AM_VB_Access, which guards this screen itself.
    // Returning null means "this guard has no opinion", not "allow anything".
    if ($page === 'am_visual_builder') return null;
    return false;
  }

  if (in_array($pagenow, ['edit.php', 'post-new.php'], true)) {
    $type = sanitize_key($request['post_type'] ?? 'post');
    $map = [
      'post' => 'blog',
      'am_nursery' => 'nurseries',
      'am_event' => 'events',
      'am_testimonial' => 'testimonials',
      'am_job' => 'jobs',
      'am_enquiry' => 'submissions',
      'am_availability' => 'submissions',
      'am_application' => 'submissions',
    ];
    return $map[$type] ?? false;
  }

  if ($pagenow === 'post.php') {
    $post_id = absint($request['post'] ?? ($request['post_ID'] ?? 0));
    return $post_id ? (am_access_section_for_post($post_id) ?: false) : false;
  }

  if (in_array($pagenow, ['edit-tags.php', 'term.php'], true)) return 'blog';
  if (in_array($pagenow, ['upload.php', 'media.php', 'media-new.php', 'async-upload.php'], true)) return 'media';
  if ($pagenow === 'options.php') return 'settings';

  $always_denied = [
    'site-editor.php', 'customize.php', 'themes.php', 'plugins.php',
    'tools.php', 'users.php', 'user-new.php', 'user-edit.php',
  ];
  return in_array($pagenow, $always_denied, true) ? false : null;
}

/** Side-effect-free direct URL decision used by the guard and audit tests. */
function am_access_admin_request_allowed($pagenow, $request = [], $user = null) {
  $user = $user ?: wp_get_current_user();
  if (!$user || !am_is_allocated_user($user) || !am_access_has_explicit_sections($user)) {
    return true;
  }
  $section = am_access_admin_request_section($pagenow, $request);
  if ($section === null) return true;
  if ($section === false) return false;
  if ($section === 'overview') {
    $website = ['overview', 'nurseries', 'events', 'blog', 'testimonials', 'jobs', 'about', 'media', 'settings'];
    return (bool) array_filter($website, fn($item) => am_access_can_section($item, $user));
  }
  return am_access_can_section($section, $user);
}

/** Block typed/bookmarked admin URLs for unallocated sections. */
function am_access_guard_admin_sections() {
  if (!am_is_allocated_user() || !am_access_has_explicit_sections()) return;
  global $pagenow;
  if (am_access_admin_request_allowed($pagenow, $_REQUEST)) return;

  wp_die(
    'This account is not allocated to the requested section.',
    'Section not allocated',
    ['response' => 403, 'back_link' => true]
  );
}
add_action('admin_init', 'am_access_guard_admin_sections', 0);

/** Enforce allocations before a mutating REST/API callback can run. */
function am_access_guard_rest_writes($result, $server, $request) {
  if (is_wp_error($result)
      || !am_is_allocated_user()
      || !am_access_has_explicit_sections()) return $result;
  if (!in_array($request->get_method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    return $result;
  }

  $route = $request->get_route();

  // The Visual Builder is allocated per PAGE, not per dashboard section, so it
  // is scoped by AM_VB_Access::guard_rest() (rest_pre_dispatch priority 8 -
  // it has already run and returned a WP_Error if the page was not allocated).
  // Without this branch the fail-closed return below would 403 every builder
  // write for an allocated Editor, because no $section can ever match it.
  if (class_exists('AM_VB_Access') && str_starts_with($route, '/am-visual-builder/')) {
    return $result;
  }

  $section = false;
  if (preg_match('#^/wp/v2/(posts|categories|tags|block-renderer)(?:/|$)#', $route)) {
    $section = 'blog';
  } elseif (preg_match('#^/wp/v2/media(?:/|$)#', $route)) {
    $section = 'media';
  } elseif (str_starts_with($route, '/alexandra/')) {
    $section = 'submissions';
  }
  if ($section && am_access_can_section($section)) return $result;

  return new WP_Error(
    'am_section_not_allocated',
    'This account is not allocated to that section.',
    ['status' => 403]
  );
}
add_filter('rest_pre_dispatch', 'am_access_guard_rest_writes', 9, 3);

/** Protect global option stores independently because they share one WP cap. */
function am_access_keep_about_option($value, $old) {
  return am_is_allocated_user()
    && am_access_has_explicit_sections()
    && !am_access_can_section('about')
      ? $old
      : $value;
}
function am_access_keep_settings_option($value, $old) {
  return am_is_allocated_user()
    && am_access_has_explicit_sections()
    && !am_access_can_section('settings')
      ? $old
      : $value;
}
add_filter('pre_update_option_am_about', 'am_access_keep_about_option', 98, 2);
add_filter('pre_update_option_am_settings', 'am_access_keep_settings_option', 98, 2);
add_filter('pre_update_option_blog_public', 'am_access_keep_settings_option', 98, 2);

/** Stop upload handlers even if a crafted request skips the Media screen. */
function am_access_block_unallocated_upload($file) {
  if (am_is_allocated_user()
      && am_access_has_explicit_sections()
      && !am_access_can_section('media')) {
    $file['error'] = 'This account is not allocated to the Media Library.';
  }
  return $file;
}
add_filter('wp_handle_upload_prefilter', 'am_access_block_unallocated_upload', 5);

/** Explain the active allocation inside an Editor or Viewer account. */
function am_access_allocation_notice() {
  if (!am_is_allocated_user() || !am_access_has_explicit_sections()) return;
  $labels = implode(', ', am_access_allowed_section_labels());
  $role_label = am_is_viewer() ? 'Viewer' : 'Editor';
  echo '<div class="notice notice-info"><p><strong>Allocated ' . esc_html($role_label) . ' access:</strong> '
    . esc_html($labels ?: 'No sections') . '. Unallocated sections are protected.</p></div>';
}
add_action('admin_notices', 'am_access_allocation_notice');

/** Remove unusable "+ New" shortcuts for unallocated content types. */
function am_access_admin_bar($bar) {
  if (!am_is_allocated_user() || !am_access_has_explicit_sections()) return;
  $nodes = [
    'new-post' => 'blog',
    'new-am_nursery' => 'nurseries',
    'new-am_event' => 'events',
    'new-am_testimonial' => 'testimonials',
    'new-am_job' => 'jobs',
    'new-media' => 'media',
  ];
  foreach ($nodes as $node => $section) {
    if (!am_access_can_section($section)) $bar->remove_node($node);
  }
}
add_action('admin_bar_menu', 'am_access_admin_bar', 999);

// ===========================================================================
// Viewer read-only enforcement (authoritative, server-side, fail-closed).
// ===========================================================================

/**
 * Layer 1 - capability level. Deny every capability that would let a Viewer
 * change or remove anything. `edit_posts`/`edit_post` stay allowed so a Viewer
 * can still OPEN an item to read it; the save itself is blocked in Layer 2.
 */
function am_viewer_block_write_caps($caps, $cap, $user_id, $args) {
  $user = get_userdata($user_id);
  if (!$user || !am_is_viewer($user)) return $caps;

  $blocked = [
    'publish_posts', 'publish_pages',
    'delete_post', 'delete_posts', 'delete_others_posts',
    'delete_published_posts', 'delete_private_posts',
    'delete_page', 'delete_pages', 'delete_others_pages',
    'manage_categories', 'manage_post_tags', 'edit_theme_options',
    'manage_options', 'create_users', 'edit_users', 'delete_users',
    'promote_users', 'remove_users',
  ];
  if (in_array($cap, $blocked, true)) return ['do_not_allow'];
  return $caps;
}
add_filter('map_meta_cap', 'am_viewer_block_write_caps', 10, 4);

/**
 * Layer 2a - block every write over the REST API (this is how the block editor,
 * and much of modern wp-admin, saves). Reads (GET) are untouched.
 */
function am_viewer_block_rest_writes($result, $server, $request) {
  if (is_wp_error($result) || !am_is_viewer()) return $result;
  if (!in_array($request->get_method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    return $result;
  }
  return new WP_Error(
    'am_view_only',
    'Your account has view-only access and cannot make changes.',
    ['status' => 403]
  );
}
add_filter('rest_pre_dispatch', 'am_viewer_block_rest_writes', 10, 3);

/** Operations actions that a Viewer must never execute. */
function am_viewer_ops_write_actions() {
  return [
    'am_ops_update_submission',
    'am_ops_bulk_update',
    'am_ops_queue_reply',
    'am_ops_retry_job',
    'am_ops_save_settings',
    'am_ops_export',
    'am_send_submission_reply',
    'am_export_submissions',
  ];
}

function am_viewer_is_ops_write_action($action) {
  return in_array(sanitize_key($action), am_viewer_ops_write_actions(), true);
}

/**
 * Layer 2b - block the classic (non-REST) save paths: the classic post editor,
 * Site Settings, taxonomy terms, media uploads, and destructive list actions.
 */
function am_viewer_block_admin_writes() {
  if (!am_is_viewer()) return;
  global $pagenow;

  $is_post = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');

  // Any POST to a content/settings save screen. `profile.php` is intentionally
  // absent so a Viewer can still manage their own account (e.g. password).
  $write_pages = [
    'post.php', 'post-new.php', 'options.php', 'edit-tags.php', 'term.php',
    'media-new.php', 'async-upload.php', 'media.php', 'update.php',
    'link.php', 'link-add.php', 'user-edit.php', 'users.php', 'user-new.php',
  ];
  if ($is_post && in_array($pagenow, $write_pages, true)) {
    am_viewer_die();
  }

  // CMB2 option pages post back to admin.php.
  if ($is_post && $pagenow === 'admin.php'
      && in_array(($_GET['page'] ?? ''), ['am_settings', 'am_about'], true)) {
    am_viewer_die();
  }

  // The operations platform uses one capability for reading and updating its
  // inbox. Viewer receives that capability only to read selected Submissions,
  // so every mutation/export handler is explicitly stopped here.
  $requested_action = sanitize_key($_REQUEST['action'] ?? '');
  if ($pagenow === 'admin-post.php'
      && am_viewer_is_ops_write_action($requested_action)) {
    am_viewer_die();
  }

  // Destructive GET actions on the post list / editor (trash, delete, etc.).
  $action = $_REQUEST['action'] ?? '';
  $action2 = $_REQUEST['action2'] ?? '';
  $bad_actions = ['trash', 'untrash', 'delete', 'editpost', 'edit-comment', 'dodelete'];
  if (array_intersect([$action, $action2], $bad_actions)) {
    am_viewer_die();
  }
}
add_action('admin_init', 'am_viewer_block_admin_writes');

/**
 * Layer 2c - block the write-type admin-ajax actions a Viewer's screen could
 * still fire (quick-edit, bulk-edit, delete, set featured image, etc.). Read
 * actions (e.g. query-attachments for browsing Media) are left alone.
 */
function am_viewer_block_ajax_writes() {
  if (!am_is_viewer()) return;
  $action = $_REQUEST['action'] ?? '';
  $write_ajax = [
    'inline-save', 'inline-save-tax', 'delete-post', 'trash-post',
    'untrash-post', 'delete-tag', 'add-tag', 'editpost', 'autosave',
    'save-attachment', 'save-attachment-compat', 'set-post-thumbnail',
    'delete-post-thumbnail', 'upload-attachment', 'send-attachment-to-editor',
    'heartbeat-autosave', 'wp-remove-post-lock', 'delete-comment',
  ];
  if (in_array($action, $write_ajax, true)) {
    wp_send_json_error('Your account has view-only access.', 403);
  }
}
add_action('admin_init', 'am_viewer_block_ajax_writes', 1);

/** Layer 2d - never let a Viewer's request persist our protected options. */
function am_viewer_keep_option($value, $old) {
  return am_is_viewer() ? $old : $value;
}
add_filter('pre_update_option_am_settings', 'am_viewer_keep_option', 99, 2);
add_filter('pre_update_option_am_about', 'am_viewer_keep_option', 99, 2);
add_filter('pre_update_option_blog_public', 'am_viewer_keep_option', 99, 2);

/** Layer 2e - stop media uploads at the handler as a final backstop. */
function am_viewer_block_upload($file) {
  if (am_is_viewer()) {
    $file['error'] = 'Your account has view-only access and cannot upload files.';
  }
  return $file;
}
add_filter('wp_handle_upload_prefilter', 'am_viewer_block_upload');

/** Shared "read only" stop. */
function am_viewer_die() {
  wp_die(
    'Your account has <strong>view-only</strong> access, so it cannot create, change or delete content. Please ask an Administrator if you need editing access.',
    'View-only access',
    ['response' => 403, 'back_link' => true]
  );
}

// ===========================================================================
// Viewer UI: hide the controls that would only fail, and explain the mode.
// ===========================================================================
function am_viewer_admin_ui() {
  if (!am_is_viewer()) return;
  ?>
  <style>
    /* "Add New" buttons and menu items */
    .wrap .page-title-action,
    #wpbody .page-title-action,
    a.submitduplicate,
    /* Row actions that write */
    .row-actions .trash, .row-actions .untrash, .row-actions .delete,
    .row-actions .inline.hide-if-no-js,
    /* Bulk actions */
    .tablenav .bulkactions,
    /* Classic editor save/publish/trash */
    #submitdiv #minor-publishing-actions,
    #submitdiv #publishing-action,
    #submitdiv #delete-action,
    #post-body .misc-pub-post-status .edit-post-status,
    #edit-slug-buttons .edit-slug,
    /* CMB2 / Site Settings save button */
    .cmb-submit-wrap, p.submit,
    /* Media modal + grid add/edit/delete */
    .media-toolbar-primary .media-button-insert,
    .upload-flash-bypass, .wp-upload-form,
    #wp-admin-bar-new-content {
      display: none !important;
    }
    #am-view-only-note {
      margin: 12px 0; padding: 10px 14px; border-left: 4px solid #a3bc9a;
      background: #f4f7f2; font-size: 13px;
    }
  </style>
  <script>
    // Block editor: hide the Publish/Save controls (their classes are dynamic).
    document.addEventListener('DOMContentLoaded', function () {
      var kill = function () {
        document.querySelectorAll(
          '.editor-post-publish-button,.editor-post-publish-panel__toggle,' +
          '.editor-post-save-draft,.editor-post-trash,.edit-post-header__settings > .components-button.is-primary'
        ).forEach(function (el) { el.style.display = 'none'; });
      };
      kill(); setInterval(kill, 800);
    });
  </script>
  <?php
}
add_action('admin_head', 'am_viewer_admin_ui');

/** A friendly banner so Viewers understand why editing is unavailable. */
function am_viewer_notice() {
  if (!am_is_viewer()) return;
  echo '<div id="am-view-only-note" class="notice"><strong>View-only access.</strong> '
    . 'You can browse only the sections allocated by an Administrator, but every change is turned off. '
    . 'An Administrator can give you editing access.</div>';
}
add_action('admin_notices', 'am_viewer_notice');

/** Remove the admin-bar "+ New" menu for Viewers (nothing there is usable). */
function am_viewer_admin_bar($bar) {
  if (am_is_viewer()) $bar->remove_node('new-content');
}
add_action('admin_bar_menu', 'am_viewer_admin_bar', 999);
