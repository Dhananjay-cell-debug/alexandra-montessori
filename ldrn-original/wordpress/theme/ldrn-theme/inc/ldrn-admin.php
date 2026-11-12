<?php
/**
 * Client-safe WordPress admin and editorial workflow.
 */

if (!defined('ABSPATH')) exit;

const LDRN_CONTENT_MANAGER_ROLE = 'ldrn_content_manager';
const AM_SETTINGS_CAPABILITY = 'manage_ldrn_site_settings';
const LDRN_CONTENT_MENU_SLUG = 'ldrn_content';

function ldrn_is_content_manager($user = null) {
  $user = $user ?: wp_get_current_user();
  return $user && in_array(LDRN_CONTENT_MANAGER_ROLE, (array) $user->roles, true);
}

/**
 * Any restricted client user - an Editor (content manager) or a read-only
 * Viewer - but never an Administrator. Used to give them the same tidy,
 * client-safe admin shell. Per-Editor section allocation and the Viewer role
 * live in inc/ldrn-roles.php.
 */
function ldrn_is_client_user($user = null) {
  $user = $user ?: wp_get_current_user();
  if (!$user) return false;
  $roles = (array) $user->roles;
  return in_array(LDRN_CONTENT_MANAGER_ROLE, $roles, true)
    || in_array('ldrn_viewer', $roles, true);
}

function ldrn_sync_content_manager_role() {
  if ((int) get_option('ldrn_content_manager_role_schema', 0) >= 1) return;

  $capabilities = [
    'read'                   => true,
    'upload_files'           => true,
    'edit_posts'             => true,
    'edit_others_posts'      => true,
    'edit_published_posts'   => true,
    'edit_private_posts'     => true,
    'read_private_posts'     => true,
    'publish_posts'          => true,
    'delete_posts'           => true,
    'delete_others_posts'    => true,
    'delete_published_posts' => true,
    'manage_categories'      => true,
    AM_SETTINGS_CAPABILITY   => true,
  ];

  $role = get_role(LDRN_CONTENT_MANAGER_ROLE);
  if (!$role) {
    add_role(LDRN_CONTENT_MANAGER_ROLE, 'Little Rainbows Content Manager', $capabilities);
    $role = get_role(LDRN_CONTENT_MANAGER_ROLE);
  }
  if ($role) {
    foreach (array_keys($role->capabilities) as $cap) {
      $role->remove_cap($cap);
    }
    foreach ($capabilities as $cap => $granted) {
      $role->add_cap($cap, $granted);
    }
  }

  $administrator = get_role('administrator');
  if ($administrator) {
    $administrator->add_cap(AM_SETTINGS_CAPABILITY);
  }

  update_option('ldrn_content_manager_role_schema', 1, false);
}
add_action('init', 'ldrn_sync_content_manager_role', 1);

function ldrn_relabel_core_content_types() {
  $blog = get_post_type_object('post');
  if ($blog) {
    $blog->show_in_menu = LDRN_CONTENT_MENU_SLUG;
    $blog->label = 'Blog';
    $blog->labels->name = 'Blog';
    $blog->labels->singular_name = 'Blog Article';
    $blog->labels->menu_name = 'Blog';
    $blog->labels->name_admin_bar = 'Blog Article';
    $blog->labels->all_items = 'All Articles';
    $blog->labels->add_new = 'Add Article';
    $blog->labels->add_new_item = 'Add Blog Article';
    $blog->labels->edit_item = 'Edit Blog Article';
    $blog->labels->new_item = 'New Blog Article';
    $blog->labels->view_item = 'View Blog Article';
    $blog->labels->search_items = 'Search Blog';
    $blog->labels->not_found = 'No blog articles found.';
    $blog->labels->not_found_in_trash = 'No blog articles found in Trash.';
  }

  $pages = get_post_type_object('page');
  if ($pages) {
    $pages->show_in_menu = false;
    $pages->label = 'System Pages';
    $pages->labels->name = 'System Pages';
    $pages->labels->menu_name = 'System Pages';
    $pages->labels->singular_name = 'System Page';
  }

  remove_post_type_support('post', 'comments');
  remove_post_type_support('post', 'trackbacks');
  remove_post_type_support('post', 'post-formats');
  remove_post_type_support('page', 'comments');
}
add_action('init', 'ldrn_relabel_core_content_types', 30);

function ldrn_register_content_workspace() {
  add_menu_page(
    'Website Content',
    'Website Content',
    'edit_posts',
    LDRN_CONTENT_MENU_SLUG,
    'ldrn_render_content_workspace',
    'dashicons-layout',
    20
  );
  add_submenu_page(
    LDRN_CONTENT_MENU_SLUG,
    'Media Library',
    'Media Library',
    'upload_files',
    'upload.php'
  );
}
add_action('admin_menu', 'ldrn_register_content_workspace', 8);

function ldrn_render_content_workspace() {
  if (!current_user_can('edit_posts')) wp_die('Access denied.');
  $definitions = [
    'blog' => ['Blog', ldrn_content_count('post'), 'edit.php', 'Articles, categories and featured images drive the public Blog.'],
    'nurseries' => ['Our Nursery', ldrn_content_count('ldrn_setting'), 'edit.php?post_type=ldrn_setting', 'Every complete published branch gets a directory card, detail page and form routing.'],
    'events' => ['Events', ldrn_content_count('ldrn_event'), 'edit.php?post_type=ldrn_event', 'Published complete events appear as upcoming or past by date.'],
    'testimonials' => ['Testimonials', ldrn_content_count('ldrn_testimonial'), 'edit.php?post_type=ldrn_testimonial', 'Complete published quotes enter the public Testimonials archive; selected stories also appear around the site.'],
    'jobs' => ['Jobs', ldrn_content_count('ldrn_job'), 'edit.php?post_type=ldrn_job', 'Only complete, published roles marked Open appear under Vacancies.'],
    'about' => ['About us', '', 'admin.php?page=ldrn_about', 'The homepage story, owners image and milestones.'],
    'media' => ['Media', ldrn_content_count('attachment', 'inherit'), 'upload.php', 'Public images and documents are filed into controlled collections.'],
    'settings' => ['Site Settings', '', 'admin.php?page=ldrn_site_settings', 'Global phone, email, opening hours and social links feed the public site.'],
  ];
  $cards = [];
  foreach ($definitions as $section => $card) {
    if (!function_exists('ldrn_is_allocated_user')
        || !ldrn_is_allocated_user()
        || !function_exists('ldrn_access_can_section')
        || ldrn_access_can_section($section)) {
      $cards[] = $card;
    }
  }
  echo '<div class="wrap"><h1>Website Content</h1>';
  if (function_exists('ldrn_is_allocated_user')
      && ldrn_is_allocated_user()
      && function_exists('ldrn_access_has_explicit_sections')
      && ldrn_access_has_explicit_sections()) {
    $labels = function_exists('ldrn_access_allowed_section_labels')
      ? implode(', ', ldrn_access_allowed_section_labels())
      : '';
    $role_label = function_exists('ldrn_is_viewer') && ldrn_is_viewer() ? 'Viewer' : 'Editor';
    echo '<p class="description" style="max-width:850px;font-size:14px">This ' . esc_html($role_label) . ' is allocated: <strong>' . esc_html($labels ?: 'No website content sections') . '</strong>. Other sections are protected.</p>';
  } else {
    echo '<p class="description" style="max-width:850px;font-size:14px">This is the complete client-editable website workspace. Publishing here changes the React website through its WordPress data contract; the hidden WordPress Home/System Page is only the application shell and is not content.</p>';
  }
  echo '<p><a class="button button-primary" href="' . esc_url(home_url('/')) . '" target="_blank" rel="noopener">Open website preview</a></p>';
  echo '<div class="ldrn-dashboard-grid" style="margin-top:20px;border:1px solid #dcdcde">';
  foreach ($cards as [$label, $count, $url, $description]) {
    echo '<a class="ldrn-dashboard-card" href="' . esc_url(admin_url($url)) . '">';
    echo $count === '' ? '<span class="dashicons dashicons-admin-settings" style="font-size:28px;width:34px;height:34px"></span>' : '<span class="ldrn-dashboard-count">' . (int) $count . '</span>';
    echo '<strong>' . esc_html($label) . '</strong><span>' . esc_html($description) . '</span></a>';
  }
  echo '</div></div>';
}

function ldrn_clean_admin_menu() {
  remove_menu_page('edit-comments.php');
  remove_menu_page('edit.php?post_type=page');
  remove_menu_page('edit.php');
  remove_menu_page('upload.php');

  if (!ldrn_is_client_user()) return;
  foreach ([
    'themes.php',
    'plugins.php',
    'users.php',
    'tools.php',
    'options-general.php',
  ] as $slug) {
    remove_menu_page($slug);
  }

  if (function_exists('ldrn_is_allocated_user')
      && ldrn_is_allocated_user()
      && function_exists('ldrn_access_can_section')) {
    if (defined('LDRN_SUBMISSIONS_MENU_SLUG')
        && !ldrn_access_can_section('submissions')) {
      remove_menu_page(LDRN_SUBMISSIONS_MENU_SLUG);
    }
    $section_submenus = [
      'overview' => LDRN_CONTENT_MENU_SLUG,
      'blog' => 'edit.php?post_type=post',
      'nurseries' => 'edit.php?post_type=ldrn_setting',
      'events' => 'edit.php?post_type=ldrn_event',
      'testimonials' => 'edit.php?post_type=ldrn_testimonial',
      'jobs' => 'edit.php?post_type=ldrn_job',
      'about' => 'ldrn_about',
      'media' => 'upload.php',
      'settings' => 'ldrn_site_settings',
    ];
    foreach ($section_submenus as $section => $slug) {
      if (!ldrn_access_can_section($section)) {
        remove_submenu_page(LDRN_CONTENT_MENU_SLUG, $slug);
      }
    }
    $website_sections = array_keys($section_submenus);
    if (!array_filter($website_sections, 'ldrn_access_can_section')) {
      remove_menu_page(LDRN_CONTENT_MENU_SLUG);
    }
  }
}
add_action('admin_menu', 'ldrn_clean_admin_menu', 999);

function ldrn_order_workspace_submenus() {
  global $submenu;
  $orders = [
    LDRN_CONTENT_MENU_SLUG => [
      LDRN_CONTENT_MENU_SLUG => ['Overview', 0],
      'edit.php?post_type=ldrn_setting' => ['Our Nursery', 10],
      'edit.php?post_type=ldrn_event' => ['Events', 20],
      'edit.php?post_type=post' => ['Blog', 30],
      'edit.php?post_type=ldrn_testimonial' => ['Testimonials', 40],
      'edit.php?post_type=ldrn_job' => ['Jobs', 50],
      'ldrn_about' => ['About us', 55],
      'upload.php' => ['Media Library', 60],
      'ldrn_site_settings' => ['Site Settings', 80],
    ],
    LDRN_SUBMISSIONS_MENU_SLUG => (
      defined('LDRN_OPS_REPLACES_LEGACY') && LDRN_OPS_REPLACES_LEGACY
        ? [
          LDRN_SUBMISSIONS_MENU_SLUG => ['Inbox', 0],
        ]
        : [
          LDRN_SUBMISSIONS_MENU_SLUG => ['Overview', 0],
          'edit.php?post_type=ldrn_enquiry' => ['Contact & Visits', 10],
          'edit.php?post_type=ldrn_availability' => ['Availability', 20],
          'edit.php?post_type=ldrn_application' => ['Careers', 30],
        ]
    ),
  ];
  foreach ($orders as $parent => $preferred) {
    if (empty($submenu[$parent])) continue;
    foreach ($submenu[$parent] as &$item) {
      if (isset($preferred[$item[2]])) $item[0] = $preferred[$item[2]][0];
    }
    unset($item);
    usort($submenu[$parent], function ($a, $b) use ($preferred) {
      return ($preferred[$a[2]][1] ?? 999) <=> ($preferred[$b[2]][1] ?? 999);
    });
  }
}
add_action('admin_menu', 'ldrn_order_workspace_submenus', 1000);

function ldrn_clean_admin_bar_new_content($admin_bar) {
  $admin_bar->remove_node('new-page');
}
add_action('admin_bar_menu', 'ldrn_clean_admin_bar_new_content', 999);

function ldrn_use_custom_menu_order() {
  return ldrn_is_client_user();
}
add_filter('custom_menu_order', 'ldrn_use_custom_menu_order');

function ldrn_content_manager_menu_order($menu_order) {
  if (!ldrn_is_client_user()) return $menu_order;
  $preferred = [
    'index.php',
    'separator1',
    LDRN_CONTENT_MENU_SLUG,
    LDRN_SUBMISSIONS_MENU_SLUG,
    'separator2',
  ];
  return array_values(array_unique(array_merge($preferred, $menu_order)));
}
add_filter('menu_order', 'ldrn_content_manager_menu_order');

function ldrn_protect_submission_deletion($caps, $cap, $user_id, $args) {
  if ($cap !== 'delete_post' || empty($args[0])) return $caps;
  $user = get_userdata($user_id);
  if (!ldrn_is_content_manager($user)) return $caps;
  $type = get_post_type((int) $args[0]);
  if (in_array($type, ['ldrn_application', 'ldrn_enquiry', 'ldrn_availability'], true)) {
    return ['do_not_allow'];
  }
  return $caps;
}
add_filter('map_meta_cap', 'ldrn_protect_submission_deletion', 10, 4);

function ldrn_protect_submission_metadata($check, $object_id, $meta_key) {
  if (!ldrn_is_content_manager()) return $check;
  if (!empty($GLOBALS['ldrn_writing_communication'])) return $check;
  if (!in_array(get_post_type($object_id), ['ldrn_application', 'ldrn_enquiry', 'ldrn_availability'], true)) {
    return $check;
  }
  $editable = [
    '_ldrn_app_status', '_ldrn_app_owner', '_ldrn_app_notes',
    '_ldrn_enq_status', '_ldrn_enq_owner', '_ldrn_enq_notes',
    '_ldrn_avl_status', '_ldrn_avl_owner', '_ldrn_avl_notes',
  ];
  if (str_starts_with($meta_key, '_ldrn_') && !in_array($meta_key, $editable, true)) {
    return false;
  }
  return $check;
}
add_filter('update_post_metadata', 'ldrn_protect_submission_metadata', 10, 3);
add_filter('delete_post_metadata', 'ldrn_protect_submission_metadata', 10, 3);

function ldrn_protect_submission_post_fields($data, $postarr) {
  if (!ldrn_is_content_manager() || empty($postarr['ID'])) return $data;
  $original = get_post((int) $postarr['ID']);
  if (!$original || !in_array($original->post_type, ['ldrn_application', 'ldrn_enquiry', 'ldrn_availability'], true)) {
    return $data;
  }
  $data['post_title'] = $original->post_title;
  $data['post_content'] = $original->post_content;
  $data['post_excerpt'] = $original->post_excerpt;
  $data['post_status'] = 'private';
  return $data;
}
add_filter('wp_insert_post_data', 'ldrn_protect_submission_post_fields', 10, 2);

function ldrn_readonly_submission_fields($args) {
  $id = (string) ($args['id'] ?? '');
  $status_fields = [
    '_ldrn_app_status', '_ldrn_app_owner', '_ldrn_app_notes',
    '_ldrn_enq_status', '_ldrn_enq_owner', '_ldrn_enq_notes',
    '_ldrn_avl_status', '_ldrn_avl_owner', '_ldrn_avl_notes',
  ];
  $is_submission = str_starts_with($id, '_ldrn_app_')
    || str_starts_with($id, '_ldrn_enq_')
    || str_starts_with($id, '_ldrn_avl_');
  if ($is_submission && !in_array($id, $status_fields, true)) {
    $args['attributes']['readonly'] = 'readonly';
  }
  return $args;
}
add_filter('cmb2_field_arguments', 'ldrn_readonly_submission_fields');

function ldrn_dashboard_setup() {
  if (!function_exists('ldrn_is_allocated_user') || !ldrn_is_allocated_user()) return;
  remove_action('welcome_panel', 'wp_welcome_panel');
  remove_meta_box('dashboard_activity', 'dashboard', 'normal');
  remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
  remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
  remove_meta_box('dashboard_primary', 'dashboard', 'side');
  remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
  wp_add_dashboard_widget('ldrn_content_hub', 'Little Rainbows Content Hub', 'ldrn_render_content_dashboard');
}
add_action('wp_dashboard_setup', 'ldrn_dashboard_setup', 99);

function ldrn_content_count($post_type, $status = 'publish') {
  $counts = wp_count_posts($post_type);
  return isset($counts->{$status}) ? (int) $counts->{$status} : 0;
}

function ldrn_render_content_dashboard() {
  $submission_count = function_exists('ldrn_submission_unhandled_total')
    ? ldrn_submission_unhandled_total()
    : 0;
  $section_counts = [
    'blog' => ldrn_content_count('post'),
    'events' => ldrn_content_count('ldrn_event'),
    'testimonials' => ldrn_content_count('ldrn_testimonial'),
    'nurseries' => ldrn_content_count('ldrn_setting'),
    'jobs' => ldrn_content_count('ldrn_job'),
  ];
  $content_count = 0;
  foreach ($section_counts as $section => $count) {
    if (!function_exists('ldrn_access_can_section')
        || ldrn_access_can_section($section)) {
      $content_count += $count;
    }
  }
  $website_sections = ['overview', 'nurseries', 'events', 'blog', 'testimonials', 'jobs', 'about', 'media', 'settings'];
  $has_website_content = !function_exists('ldrn_access_can_section')
    || (bool) array_filter($website_sections, 'ldrn_access_can_section');
  $cards = [];
  if ($has_website_content) {
    $cards[] = ['Website Content', $content_count, 'admin.php?page=' . LDRN_CONTENT_MENU_SLUG, 'Open only the website sections allocated to this Editor.'];
  }
  if (!function_exists('ldrn_access_can_section')
      || ldrn_access_can_section('submissions')) {
    $cards[] = ['Submissions', $submission_count, 'admin.php?page=' . LDRN_SUBMISSIONS_MENU_SLUG, 'New contact, visit, availability and careers records requiring action.'];
  }

  if (function_exists('ldrn_access_has_explicit_sections') && ldrn_access_has_explicit_sections()) {
    $labels = function_exists('ldrn_access_allowed_section_labels')
      ? implode(', ', ldrn_access_allowed_section_labels())
      : '';
    $role_label = function_exists('ldrn_is_viewer') && ldrn_is_viewer() ? 'Viewer' : 'Editor';
    echo '<div class="ldrn-dashboard-intro"><p><strong>Allocated ' . esc_html($role_label) . ' workspace.</strong> Access: ' . esc_html($labels ?: 'No sections') . '. Other sections are protected.</p></div>';
  } else {
    echo '<div class="ldrn-dashboard-intro"><p><strong>One publishing system.</strong> Edit content here, verify it on the Local website, then publish. Design and system files remain protected.</p></div>';
  }
  echo '<div class="ldrn-dashboard-grid">';
  foreach ($cards as [$label, $count, $url, $description]) {
    echo '<a class="ldrn-dashboard-card" href="' . esc_url(admin_url($url)) . '">';
    echo '<span class="ldrn-dashboard-count">' . (int) $count . '</span>';
    echo '<strong>' . esc_html($label) . '</strong>';
    echo '<span>' . esc_html($description) . '</span>';
    echo '</a>';
  }
  echo '</div>';
  if (!function_exists('ldrn_access_can_section')
      || ldrn_access_can_section('settings')) {
    echo '<p class="ldrn-dashboard-settings"><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=ldrn_site_settings')) . '">Open Site Settings</a></p>';
  }
}

function ldrn_admin_styles() {
  echo '<style>
    #dashboard-widgets .postbox-container { width: 100% !important; }
    #ldrn_content_hub .inside { margin: 0; padding: 0; }
    .ldrn-dashboard-intro { padding: 18px 20px; border-bottom: 1px solid #dcdcde; background: #f6f8f3; }
    .ldrn-dashboard-intro p { margin: 0; font-size: 14px; }
    .ldrn-dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(210px,1fr)); gap: 1px; background: #dcdcde; }
    .ldrn-dashboard-card { display: grid; grid-template-columns: auto 1fr; gap: 4px 12px; min-height: 108px; padding: 18px; background: #fff; color: #1d2327; text-decoration: none; }
    .ldrn-dashboard-card:hover { background: #f6f8f3; color: #315b3a; }
    .ldrn-dashboard-card strong { align-self: center; font-size: 15px; }
    .ldrn-dashboard-card > span:last-child { grid-column: 1 / -1; color: #646970; line-height: 1.45; }
    .ldrn-dashboard-count { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; border-radius: 50%; background: #315b3a; color: #fff; font-weight: 700; }
    .ldrn-dashboard-settings { margin: 0; padding: 16px 20px; border-top: 1px solid #dcdcde; }
    .column-ldrn_readiness, .column-ldrn_website_result { width: 220px; }
    .column-ldrn_collection { width: 170px; }
    .ldrn-ready { color: #237a3b; font-weight: 600; }
    .ldrn-warning { color: #996800; font-weight: 600; }
    .ldrn-needs-work { color: #b32d2e; font-weight: 600; }
  </style>';
}
add_action('admin_head', 'ldrn_admin_styles');

function ldrn_section_guidance() {
  $screen = get_current_screen();
  if (!$screen) return;
  $guidance = [
    'post'            => ['Blog workflow', 'A published article with a title and body appears on the website. Set the main picture using the Featured image box in the right sidebar - that is what shows on the article card and page header. Pictures added inside the text are only extra images within the article. Use Website placement to choose the one large main article; all others appear in the three-column archive, nine per page. If none is chosen, the newest ready article is used.'],
    'ldrn_event'        => ['Events workflow', 'A published event needs a date, time, location, card excerpt, full description and a Featured image. Set the picture using the Featured image box in the right sidebar (not by dropping it into the text) - that is what appears on the event card and page header, and the event stays hidden until it is set. Its date controls Upcoming versus Past automatically.'],
    'ldrn_testimonial'  => ['Testimonials workflow', 'Use the title as an internal summary. A complete published quote appears in the Testimonials archive; the homepage and matching nursery page show a controlled subset.'],
    'ldrn_setting'      => ['Nursery source of truth', 'A complete published branch automatically gets a directory card, detail page, contact and visit routing, plus any supplied fee, Ofsted and food hygiene references. Incomplete branches stay hidden.'],
    'ldrn_job'          => ['Vacancy workflow', 'Keep incomplete roles Closed. Only a published role marked Open should contain an approved summary, full description and application route.'],
    'ldrn_application'  => ['Private applications', 'Applicant data is read-only. Confirm the secure CV indicator, assign an owner, and use Reply to applicant for a tracked private response.'],
    'ldrn_enquiry'      => ['Contact and visit workflow', 'Submitted details are read-only. Assign an owner, add internal notes, and use Reply to family for a tracked private response.'],
    'ldrn_availability' => ['Availability workflow', 'Submitted requirements are read-only. Assign an owner, reply to the family, and move the request through Reviewing, Contacted, Waitlisted, Placed or Closed.'],
  ];
  if (!isset($guidance[$screen->post_type])) return;
  [$title, $copy] = $guidance[$screen->post_type];
  echo '<div class="notice notice-info"><p><strong>' . esc_html($title) . ':</strong> ' . esc_html($copy) . '</p></div>';
}
add_action('admin_notices', 'ldrn_section_guidance');

function ldrn_content_readiness_issues($post_id) {
  $functions = [
    'post' => 'ldrn_blog_readiness_issues',
    'ldrn_event' => 'ldrn_event_readiness_issues',
    'ldrn_testimonial' => 'ldrn_testimonial_readiness_issues',
    'ldrn_job' => 'ldrn_job_readiness_issues',
    'ldrn_setting' => 'ldrn_setting_readiness_issues',
  ];
  $function = $functions[get_post_type($post_id)] ?? '';
  return $function && function_exists($function) ? $function($post_id) : [];
}

function ldrn_content_website_result($post_id) {
  if (get_post_status($post_id) !== 'publish') {
    return ['class' => 'ldrn-needs-work', 'label' => 'Hidden: not published', 'visible' => false];
  }
  if (get_post_type($post_id) === 'ldrn_job' && get_post_meta($post_id, '_ldrn_job_status', true) === 'closed') {
    return ['class' => 'ldrn-needs-work', 'label' => 'Hidden: marked Closed', 'visible' => false];
  }
  $issues = ldrn_content_readiness_issues($post_id);
  if ($issues) {
    return [
      'class' => 'ldrn-needs-work',
      'label' => 'Hidden: missing ' . implode(', ', $issues),
      'visible' => false,
    ];
  }
  if (get_post_type($post_id) === 'post') {
    $is_main = function_exists('ldrn_featured_blog_post_id')
      && ldrn_featured_blog_post_id() === (int) $post_id;
    $placement = $is_main ? 'main article' : 'archive';
    if (!has_post_thumbnail($post_id)) {
      return ['class' => 'ldrn-warning', 'label' => "Live as {$placement} with fallback image", 'visible' => true];
    }
    return ['class' => 'ldrn-ready', 'label' => "Live as {$placement}", 'visible' => true];
  }
  if (get_post_type($post_id) === 'ldrn_setting') {
    return ['class' => 'ldrn-ready', 'label' => 'Live in Nursery directory and detail page', 'visible' => true];
  }
  if (get_post_type($post_id) === 'ldrn_testimonial') {
    return ['class' => 'ldrn-ready', 'label' => 'Live in Testimonials archive', 'visible' => true];
  }
  if (get_post_type($post_id) === 'ldrn_event') {
    return ['class' => 'ldrn-ready', 'label' => 'Live in Events and detail page', 'visible' => true];
  }
  if (get_post_type($post_id) === 'ldrn_job') {
    return ['class' => 'ldrn-ready', 'label' => 'Live in Vacancies and detail page', 'visible' => true];
  }
  return ['class' => 'ldrn-ready', 'label' => 'Live on website', 'visible' => true];
}

function ldrn_render_content_website_result($post_id) {
  $result = ldrn_content_website_result($post_id);
  echo '<span class="' . esc_attr($result['class']) . '">' . esc_html($result['label']) . '</span>';
}

function ldrn_incomplete_content_notice() {
  $screen = get_current_screen();
  if (!$screen || $screen->base !== 'post' || empty($_GET['post'])) return;
  $post_id = (int) $_GET['post'];
  if (!in_array(get_post_type($post_id), ['post', 'ldrn_event', 'ldrn_testimonial', 'ldrn_job', 'ldrn_setting'], true)) return;
  $result = ldrn_content_website_result($post_id);
  if ($result['class'] === 'ldrn-ready') return;
  $explanation = $result['visible']
    ? 'Visitors can see it now, but the stated fallback should be replaced before final approval.'
    : 'Visitors will not see this item until the stated requirement is fixed.';
  echo '<div class="notice notice-warning"><p><strong>Website result:</strong> ' . esc_html($result['label']) . '. ' . esc_html($explanation) . '</p></div>';
}
add_action('admin_notices', 'ldrn_incomplete_content_notice', 20);

function ldrn_content_post_states($states, $post) {
  if (!in_array($post->post_type, ['post', 'ldrn_event', 'ldrn_testimonial', 'ldrn_job', 'ldrn_setting'], true)) return $states;
  $result = ldrn_content_website_result($post->ID);
  if ($result['class'] !== 'ldrn-ready') $states['ldrn_website_hidden'] = $result['label'];
  return $states;
}
add_filter('display_post_states', 'ldrn_content_post_states', 10, 2);

function ldrn_blog_admin_columns($columns) {
  return [
    'cb' => $columns['cb'], 'title' => 'Article', 'categories' => 'Category',
    'ldrn_blog_placement' => 'Placement', 'ldrn_website_result' => 'Website result', 'date' => 'Published',
  ];
}
add_filter('manage_post_posts_columns', 'ldrn_blog_admin_columns');

function ldrn_blog_admin_column($column, $post_id) {
  if ($column === 'ldrn_blog_placement') {
    $is_main = function_exists('ldrn_featured_blog_post_id')
      && ldrn_featured_blog_post_id() === (int) $post_id;
    if ($is_main) {
      echo esc_html(ldrn_blog_is_featured_choice($post_id) ? 'Main article (chosen)' : 'Main article (newest fallback)');
    } else {
      echo esc_html(ldrn_content_website_result($post_id)['visible'] ? '3-column archive' : 'Not on website');
    }
  }
  if ($column === 'ldrn_website_result') ldrn_render_content_website_result($post_id);
}
add_action('manage_post_posts_custom_column', 'ldrn_blog_admin_column', 10, 2);

function ldrn_event_admin_columns($columns) {
  return [
    'cb' => $columns['cb'], 'title' => 'Event', 'ldrn_event_date' => 'Event date',
    'ldrn_event_location' => 'Location', 'ldrn_website_result' => 'Website result', 'date' => 'Published',
  ];
}
add_filter('manage_ldrn_event_posts_columns', 'ldrn_event_admin_columns');

function ldrn_event_admin_column($column, $post_id) {
  if ($column === 'ldrn_event_date') echo esc_html(get_post_meta($post_id, '_ldrn_event_date', true) ?: 'Not set');
  if ($column === 'ldrn_event_location') echo esc_html(get_post_meta($post_id, '_ldrn_event_location', true) ?: 'Not set');
  if ($column === 'ldrn_website_result') ldrn_render_content_website_result($post_id);
}
add_action('manage_ldrn_event_posts_custom_column', 'ldrn_event_admin_column', 10, 2);

function ldrn_setting_admin_columns($columns) {
  return [
    'cb' => $columns['cb'], 'title' => 'Nursery', 'ldrn_area' => 'Area',
    'ldrn_contact' => 'Branch contact', 'ldrn_website_result' => 'Website result', 'date' => 'Updated',
  ];
}
add_filter('manage_ldrn_setting_posts_columns', 'ldrn_setting_admin_columns');

function ldrn_setting_admin_column($column, $post_id) {
  if ($column === 'ldrn_area') echo esc_html(get_post_meta($post_id, '_ldrn_setting_area', true) ?: 'Not set');
  if ($column === 'ldrn_contact') {
    $phone = get_post_meta($post_id, '_ldrn_setting_phone', true);
    $email = get_post_meta($post_id, '_ldrn_setting_email', true);
    echo esc_html(trim($phone . ($phone && $email ? ' / ' : '') . $email) ?: 'Not set');
  }
  if ($column === 'ldrn_website_result') ldrn_render_content_website_result($post_id);
}
add_action('manage_ldrn_setting_posts_custom_column', 'ldrn_setting_admin_column', 10, 2);

function ldrn_job_admin_columns($columns) {
  return [
    'cb' => $columns['cb'], 'title' => 'Role', 'ldrn_job_status' => 'Open / closed',
    'ldrn_job_location' => 'Nursery', 'ldrn_job_type' => 'Type', 'ldrn_website_result' => 'Website result', 'date' => 'Updated',
  ];
}
add_filter('manage_ldrn_job_posts_columns', 'ldrn_job_admin_columns');

function ldrn_job_admin_column($column, $post_id) {
  if ($column === 'ldrn_job_status') echo esc_html(ucfirst(get_post_meta($post_id, '_ldrn_job_status', true) ?: 'open'));
  if ($column === 'ldrn_job_location') echo esc_html(get_post_meta($post_id, '_ldrn_job_location', true) ?: 'Not set');
  if ($column === 'ldrn_job_type') echo esc_html(get_post_meta($post_id, '_ldrn_job_type', true) ?: 'Not set');
  if ($column === 'ldrn_website_result') ldrn_render_content_website_result($post_id);
}
add_action('manage_ldrn_job_posts_custom_column', 'ldrn_job_admin_column', 10, 2);

function ldrn_testimonial_admin_columns($columns) {
  return [
    'cb' => $columns['cb'], 'title' => 'Internal title', 'ldrn_parent' => 'Parent / descriptor',
    'ldrn_location' => 'Nursery', 'ldrn_website_result' => 'Website result', 'date' => 'Updated',
  ];
}
add_filter('manage_ldrn_testimonial_posts_columns', 'ldrn_testimonial_admin_columns');

function ldrn_testimonial_admin_column($column, $post_id) {
  if ($column === 'ldrn_parent') echo esc_html(get_post_meta($post_id, '_ldrn_testimonial_name', true) ?: 'Not set');
  if ($column === 'ldrn_location') echo esc_html(get_post_meta($post_id, '_ldrn_testimonial_location', true) ?: 'Not set');
  if ($column === 'ldrn_website_result') ldrn_render_content_website_result($post_id);
}
add_action('manage_ldrn_testimonial_posts_custom_column', 'ldrn_testimonial_admin_column', 10, 2);
