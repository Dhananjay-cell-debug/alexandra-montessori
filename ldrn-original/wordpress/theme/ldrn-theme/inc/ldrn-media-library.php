<?php
/**
 * Editorial Media Library organization without changing attachment URLs.
 */

if (!defined('ABSPATH')) exit;

const LDRN_MEDIA_TAXONOMY = 'ldrn_media_collection';

function ldrn_register_media_collection_taxonomy() {
  register_taxonomy(LDRN_MEDIA_TAXONOMY, ['attachment'], [
    'labels' => [
      'name'          => 'Media Collections',
      'singular_name' => 'Media Collection',
      'menu_name'     => 'Collections',
      'search_items'  => 'Search collections',
      'all_items'     => 'All collections',
      'edit_item'     => 'Edit collection',
      'update_item'   => 'Update collection',
      'add_new_item'  => 'Add collection',
      'new_item_name' => 'New collection name',
      'parent_item'   => 'Parent collection',
    ],
    'public'                => false,
    // Collections are a fixed information architecture. Editors assign them
    // in Media; nobody should rename/delete the schema from a taxonomy screen.
    'show_ui'               => false,
    'show_admin_column'     => false,
    'show_in_rest'          => true,
    'show_in_nav_menus'     => false,
    'hierarchical'          => true,
    'query_var'             => true,
    'rewrite'               => false,
    'update_count_callback' => '_update_generic_term_count',
    'capabilities'          => [
      'manage_terms' => 'manage_options',
      'edit_terms'   => 'manage_options',
      'delete_terms' => 'manage_options',
      'assign_terms' => 'upload_files',
    ],
  ]);
}
add_action('init', 'ldrn_register_media_collection_taxonomy', 5);

function ldrn_ensure_media_collection($name, $slug, $parent = 0) {
  $existing = get_term_by('slug', $slug, LDRN_MEDIA_TAXONOMY);
  if ($existing) return (int) $existing->term_id;

  $created = wp_insert_term($name, LDRN_MEDIA_TAXONOMY, [
    'slug'   => $slug,
    'parent' => absint($parent),
  ]);
  return is_wp_error($created) ? 0 : (int) $created['term_id'];
}

function ldrn_seed_media_collections() {
  if ((int) get_option('ldrn_media_collection_schema', 0) >= 1) return;

  ldrn_ensure_media_collection('Inbox / Needs Filing', 'needs-filing');
  $website = ldrn_ensure_media_collection('Website Content', 'website-content');
  ldrn_ensure_media_collection('Global & Brand', 'global-brand', $website);
  // One sub-collection per published setting rather than a fixed list, so
  // adding or closing a setting in wp-admin does not need a code change.
  $settings_folder = ldrn_ensure_media_collection('Our Nursery', 'nurseries', $website);
  foreach (ldrn_setting_media_slugs() as $slug => $label) {
    ldrn_ensure_media_collection($label, $slug, $settings_folder);
  }
  ldrn_ensure_media_collection('Events', 'events', $website);
  ldrn_ensure_media_collection('Blog', 'blog', $website);
  $documents = ldrn_ensure_media_collection('Documents', 'documents');
  ldrn_ensure_media_collection('Fee Sheets', 'fee-sheets', $documents);
  ldrn_ensure_media_collection('Policies & Reports', 'policies-reports', $documents);

  update_option('ldrn_media_collection_schema', 1, false);
}
add_action('init', 'ldrn_seed_media_collections', 20);

/**
 * Published settings as slug => title, for media filing.
 *
 * Falls back to Hounslow before any record exists so a fresh install still
 * has somewhere sensible to file the current nursery's photographs.
 */
function ldrn_setting_media_slugs() {
  $posts = get_posts([
    'post_type'      => 'ldrn_setting',
    'post_status'    => ['publish', 'draft'],
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
  ]);

  $slugs = [];
  foreach ($posts as $post_id) {
    $post = get_post($post_id);
    if (!$post || !$post->post_name) continue;
    $slugs[$post->post_name] = get_the_title($post) ?: ucfirst($post->post_name);
  }

  return $slugs ?: ['hounslow' => 'Hounslow'];
}

function ldrn_media_collection_term_id($slug) {
  $term = get_term_by('slug', $slug, LDRN_MEDIA_TAXONOMY);
  return $term ? (int) $term->term_id : 0;
}

function ldrn_media_collection_for_attachment($attachment_id) {
  $parent_id = (int) wp_get_post_parent_id($attachment_id);
  $parent = $parent_id ? get_post($parent_id) : null;
  if (!$parent) return 'needs-filing';

  if ($parent->post_type === 'post') return 'blog';
  if ($parent->post_type === 'ldrn_event') return 'events';
  if ($parent->post_type === 'ldrn_setting') {
    // File under the setting's own collection when one exists, otherwise
    // under the parent folder.
    return ldrn_media_collection_term_id($parent->post_name)
      ? $parent->post_name
      : 'nurseries';
  }

  return 'needs-filing';
}

function ldrn_auto_file_new_attachment($attachment_id) {
  $current = wp_get_object_terms($attachment_id, LDRN_MEDIA_TAXONOMY, ['fields' => 'ids']);
  if (!is_wp_error($current) && $current) return;

  $term_id = ldrn_media_collection_term_id(ldrn_media_collection_for_attachment($attachment_id));
  if ($term_id) {
    wp_set_object_terms($attachment_id, [$term_id], LDRN_MEDIA_TAXONOMY, false);
  }
}
add_action('add_attachment', 'ldrn_auto_file_new_attachment');

function ldrn_media_collection_options($selected = 0) {
  $terms = get_terms([
    'taxonomy'   => LDRN_MEDIA_TAXONOMY,
    'hide_empty' => false,
    'orderby'    => 'name',
  ]);
  if (is_wp_error($terms)) return '';

  $html = '';
  foreach ($terms as $term) {
    $depth = count(get_ancestors($term->term_id, LDRN_MEDIA_TAXONOMY, 'taxonomy'));
    $label = str_repeat('— ', $depth) . $term->name;
    $html .= '<option value="' . (int) $term->term_id . '" ' . selected($selected, $term->term_id, false) . '>'
      . esc_html($label) . '</option>';
  }
  return $html;
}

function ldrn_attachment_collection_field($fields, $post) {
  $assigned = wp_get_object_terms($post->ID, LDRN_MEDIA_TAXONOMY, ['fields' => 'ids']);
  $selected = (!is_wp_error($assigned) && $assigned) ? (int) $assigned[0] : ldrn_media_collection_term_id('needs-filing');
  $fields['ldrn_media_collection'] = [
    'label' => 'Media Collection',
    'input' => 'html',
    'html'  => '<select name="attachments[' . (int) $post->ID . '][ldrn_media_collection]" style="width:100%">'
      . ldrn_media_collection_options($selected) . '</select>',
    'helps' => 'Choose one editorial home. This organizes the library but never changes the file URL.',
  ];
  return $fields;
}
add_filter('attachment_fields_to_edit', 'ldrn_attachment_collection_field', 10, 2);

function ldrn_save_attachment_collection($post, $attachment) {
  if (!current_user_can('upload_files')) return $post;
  if (!isset($attachment['ldrn_media_collection'])) return $post;

  $term_id = absint($attachment['ldrn_media_collection']);
  if (!$term_id || !term_exists($term_id, LDRN_MEDIA_TAXONOMY)) {
    $term_id = ldrn_media_collection_term_id('needs-filing');
  }
  if ($term_id) {
    wp_set_object_terms($post['ID'], [$term_id], LDRN_MEDIA_TAXONOMY, false);
  }
  return $post;
}
add_filter('attachment_fields_to_save', 'ldrn_save_attachment_collection', 10, 2);

function ldrn_media_library_collection_filter($post_type) {
  if ($post_type !== 'attachment') return;
  wp_dropdown_categories([
    'show_option_all' => 'All media collections',
    'taxonomy'        => LDRN_MEDIA_TAXONOMY,
    // Do not reuse the taxonomy query-var name here. WordPress would parse the
    // numeric term ID as a slug before our explicit tax_query runs.
    'name'            => 'ldrn_collection_filter',
    'orderby'         => 'name',
    'selected'        => isset($_GET['ldrn_collection_filter']) ? absint($_GET['ldrn_collection_filter']) : 0,
    'hierarchical'    => true,
    'hide_empty'      => false,
    'value_field'     => 'term_id',
  ]);
}
add_action('restrict_manage_posts', 'ldrn_media_library_collection_filter');

function ldrn_filter_media_library_collection($query) {
  if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'attachment') return;
  $term_id = isset($_GET['ldrn_collection_filter']) ? absint($_GET['ldrn_collection_filter']) : 0;
  if (!$term_id) return;
  $query->set('tax_query', [[
    'taxonomy'         => LDRN_MEDIA_TAXONOMY,
    'field'            => 'term_id',
    'terms'            => [$term_id],
    'include_children' => true,
  ]]);
}
add_action('pre_get_posts', 'ldrn_filter_media_library_collection');

function ldrn_filter_media_grid_collection($query) {
  // Core deliberately strips unknown attachment query keys before this
  // filter. Read our namespaced UI value from the original request.
  $term_id = isset($_REQUEST['query']['ldrn_collection_filter'])
    ? absint($_REQUEST['query']['ldrn_collection_filter'])
    : 0;
  if ($term_id) {
    $query['tax_query'] = [[
      'taxonomy'         => LDRN_MEDIA_TAXONOMY,
      'field'            => 'term_id',
      'terms'            => [$term_id],
      'include_children' => true,
    ]];
  }
  return $query;
}
add_filter('ajax_query_attachments_args', 'ldrn_filter_media_grid_collection');

function ldrn_media_columns($columns) {
  $result = [];
  foreach ($columns as $key => $label) {
    $result[$key] = $label;
    if ($key === 'title') {
      $result['ldrn_collection'] = 'Collection';
      $result['ldrn_readiness'] = 'Readiness';
    }
  }
  return $result;
}
add_filter('manage_upload_columns', 'ldrn_media_columns');

function ldrn_media_column_value($column, $attachment_id) {
  if ($column === 'ldrn_collection') {
    $terms = wp_get_object_terms($attachment_id, LDRN_MEDIA_TAXONOMY);
    if (is_wp_error($terms) || !$terms) {
      echo '<span style="color:#b32d2e">Needs filing</span>';
      return;
    }
    echo esc_html(implode(', ', wp_list_pluck($terms, 'name')));
  }

  if ($column === 'ldrn_readiness') {
    if (!wp_attachment_is_image($attachment_id)) {
      echo '<span>Document</span>';
      return;
    }
    $alt = trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true));
    echo $alt !== ''
      ? '<span style="color:#237a3b">Alt text ready</span>'
      : '<span style="color:#b32d2e">Add alt text</span>';
  }
}
add_action('manage_media_custom_column', 'ldrn_media_column_value', 10, 2);

function ldrn_media_collection_grid_filter_script() {
  if (!wp_script_is('media-views', 'enqueued')) return;
  $terms = get_terms([
    'taxonomy'   => LDRN_MEDIA_TAXONOMY,
    'hide_empty' => false,
    'orderby'    => 'name',
  ]);
  if (is_wp_error($terms)) return;

  $options = [];
  foreach ($terms as $term) {
    $depth = count(get_ancestors($term->term_id, LDRN_MEDIA_TAXONOMY, 'taxonomy'));
    $options[] = [
      'id'   => (int) $term->term_id,
      'name' => str_repeat('— ', $depth) . $term->name,
    ];
  }
  $json = wp_json_encode($options);
  $script = <<<JS
(function () {
  if (!window.wp?.media?.view?.AttachmentsBrowser || !window.wp?.media?.view?.AttachmentFilters) return;
  const collections = {$json};
  const CollectionFilter = wp.media.view.AttachmentFilters.extend({
    createFilters: function () {
      const filters = {
        all: { text: 'All collections', props: { ldrn_collection_filter: null }, priority: 10 }
      };
      collections.forEach(function (term, index) {
        filters['collection-' + term.id] = {
          text: term.name,
          props: { ldrn_collection_filter: term.id },
          priority: 20 + index
        };
      });
      this.filters = filters;
    }
  });
  const original = wp.media.view.AttachmentsBrowser.prototype.createToolbar;
  wp.media.view.AttachmentsBrowser.prototype.createToolbar = function () {
    original.apply(this, arguments);
    if (!this.options.filters) return;
    this.toolbar.set('ldrn-media-collection', new CollectionFilter({
      controller: this.controller,
      model: this.collection.props,
      priority: -75
    }).render());
  };
}());
JS;
  wp_add_inline_script('media-views', $script, 'after');
}
add_action('admin_enqueue_scripts', 'ldrn_media_collection_grid_filter_script', 30);

function ldrn_media_library_guidance() {
  $screen = get_current_screen();
  if (!$screen || $screen->base !== 'upload') return;
  echo '<div class="notice notice-info"><p><strong>Media filing rule:</strong> website images and public documents belong here; applicant CVs do not. File every upload into one collection, use descriptive lowercase filenames before upload, and add meaningful alt text to content images.</p></div>';
}
add_action('admin_notices', 'ldrn_media_library_guidance');
