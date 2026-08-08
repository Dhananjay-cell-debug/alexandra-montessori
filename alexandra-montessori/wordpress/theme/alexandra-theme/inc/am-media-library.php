<?php
/**
 * Editorial Media Library organization without changing attachment URLs.
 */

if (!defined('ABSPATH')) exit;

const AM_MEDIA_TAXONOMY = 'am_media_collection';

function am_register_media_collection_taxonomy() {
  register_taxonomy(AM_MEDIA_TAXONOMY, ['attachment'], [
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
add_action('init', 'am_register_media_collection_taxonomy', 5);

function am_ensure_media_collection($name, $slug, $parent = 0) {
  $existing = get_term_by('slug', $slug, AM_MEDIA_TAXONOMY);
  if ($existing) return (int) $existing->term_id;

  $created = wp_insert_term($name, AM_MEDIA_TAXONOMY, [
    'slug'   => $slug,
    'parent' => absint($parent),
  ]);
  return is_wp_error($created) ? 0 : (int) $created['term_id'];
}

function am_seed_media_collections() {
  if ((int) get_option('am_media_collection_schema', 0) >= 1) return;

  am_ensure_media_collection('Inbox / Needs Filing', 'needs-filing');
  $website = am_ensure_media_collection('Website Content', 'website-content');
  am_ensure_media_collection('Global & Brand', 'global-brand', $website);
  $nurseries = am_ensure_media_collection('Nurseries', 'nurseries', $website);
  am_ensure_media_collection('Hounslow', 'hounslow', $nurseries);
  am_ensure_media_collection('Heston', 'heston', $nurseries);
  am_ensure_media_collection('Hammersmith', 'hammersmith', $nurseries);
  am_ensure_media_collection('Events', 'events', $website);
  am_ensure_media_collection('Blog', 'blog', $website);
  $documents = am_ensure_media_collection('Documents', 'documents');
  am_ensure_media_collection('Fee Sheets', 'fee-sheets', $documents);
  am_ensure_media_collection('Policies & Reports', 'policies-reports', $documents);

  update_option('am_media_collection_schema', 1, false);
}
add_action('init', 'am_seed_media_collections', 20);

function am_media_collection_term_id($slug) {
  $term = get_term_by('slug', $slug, AM_MEDIA_TAXONOMY);
  return $term ? (int) $term->term_id : 0;
}

function am_media_collection_for_attachment($attachment_id) {
  $parent_id = (int) wp_get_post_parent_id($attachment_id);
  $parent = $parent_id ? get_post($parent_id) : null;
  if (!$parent) return 'needs-filing';

  if ($parent->post_type === 'post') return 'blog';
  if ($parent->post_type === 'am_event') return 'events';
  if ($parent->post_type === 'am_nursery') {
    return in_array($parent->post_name, ['hounslow', 'heston', 'hammersmith'], true)
      ? $parent->post_name
      : 'nurseries';
  }

  return 'needs-filing';
}

function am_auto_file_new_attachment($attachment_id) {
  $current = wp_get_object_terms($attachment_id, AM_MEDIA_TAXONOMY, ['fields' => 'ids']);
  if (!is_wp_error($current) && $current) return;

  $term_id = am_media_collection_term_id(am_media_collection_for_attachment($attachment_id));
  if ($term_id) {
    wp_set_object_terms($attachment_id, [$term_id], AM_MEDIA_TAXONOMY, false);
  }
}
add_action('add_attachment', 'am_auto_file_new_attachment');

function am_media_collection_options($selected = 0) {
  $terms = get_terms([
    'taxonomy'   => AM_MEDIA_TAXONOMY,
    'hide_empty' => false,
    'orderby'    => 'name',
  ]);
  if (is_wp_error($terms)) return '';

  $html = '';
  foreach ($terms as $term) {
    $depth = count(get_ancestors($term->term_id, AM_MEDIA_TAXONOMY, 'taxonomy'));
    $label = str_repeat('— ', $depth) . $term->name;
    $html .= '<option value="' . (int) $term->term_id . '" ' . selected($selected, $term->term_id, false) . '>'
      . esc_html($label) . '</option>';
  }
  return $html;
}

function am_attachment_collection_field($fields, $post) {
  $assigned = wp_get_object_terms($post->ID, AM_MEDIA_TAXONOMY, ['fields' => 'ids']);
  $selected = (!is_wp_error($assigned) && $assigned) ? (int) $assigned[0] : am_media_collection_term_id('needs-filing');
  $fields['am_media_collection'] = [
    'label' => 'Media Collection',
    'input' => 'html',
    'html'  => '<select name="attachments[' . (int) $post->ID . '][am_media_collection]" style="width:100%">'
      . am_media_collection_options($selected) . '</select>',
    'helps' => 'Choose one editorial home. This organizes the library but never changes the file URL.',
  ];
  return $fields;
}
add_filter('attachment_fields_to_edit', 'am_attachment_collection_field', 10, 2);

function am_save_attachment_collection($post, $attachment) {
  if (!current_user_can('upload_files')) return $post;
  if (!isset($attachment['am_media_collection'])) return $post;

  $term_id = absint($attachment['am_media_collection']);
  if (!$term_id || !term_exists($term_id, AM_MEDIA_TAXONOMY)) {
    $term_id = am_media_collection_term_id('needs-filing');
  }
  if ($term_id) {
    wp_set_object_terms($post['ID'], [$term_id], AM_MEDIA_TAXONOMY, false);
  }
  return $post;
}
add_filter('attachment_fields_to_save', 'am_save_attachment_collection', 10, 2);

function am_media_library_collection_filter($post_type) {
  if ($post_type !== 'attachment') return;
  wp_dropdown_categories([
    'show_option_all' => 'All media collections',
    'taxonomy'        => AM_MEDIA_TAXONOMY,
    // Do not reuse the taxonomy query-var name here. WordPress would parse the
    // numeric term ID as a slug before our explicit tax_query runs.
    'name'            => 'am_collection_filter',
    'orderby'         => 'name',
    'selected'        => isset($_GET['am_collection_filter']) ? absint($_GET['am_collection_filter']) : 0,
    'hierarchical'    => true,
    'hide_empty'      => false,
    'value_field'     => 'term_id',
  ]);
}
add_action('restrict_manage_posts', 'am_media_library_collection_filter');

function am_filter_media_library_collection($query) {
  if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'attachment') return;
  $term_id = isset($_GET['am_collection_filter']) ? absint($_GET['am_collection_filter']) : 0;
  if (!$term_id) return;
  $query->set('tax_query', [[
    'taxonomy'         => AM_MEDIA_TAXONOMY,
    'field'            => 'term_id',
    'terms'            => [$term_id],
    'include_children' => true,
  ]]);
}
add_action('pre_get_posts', 'am_filter_media_library_collection');

function am_filter_media_grid_collection($query) {
  // Core deliberately strips unknown attachment query keys before this
  // filter. Read our namespaced UI value from the original request.
  $term_id = isset($_REQUEST['query']['am_collection_filter'])
    ? absint($_REQUEST['query']['am_collection_filter'])
    : 0;
  if ($term_id) {
    $query['tax_query'] = [[
      'taxonomy'         => AM_MEDIA_TAXONOMY,
      'field'            => 'term_id',
      'terms'            => [$term_id],
      'include_children' => true,
    ]];
  }
  return $query;
}
add_filter('ajax_query_attachments_args', 'am_filter_media_grid_collection');

function am_media_columns($columns) {
  $result = [];
  foreach ($columns as $key => $label) {
    $result[$key] = $label;
    if ($key === 'title') {
      $result['am_collection'] = 'Collection';
      $result['am_readiness'] = 'Readiness';
    }
  }
  return $result;
}
add_filter('manage_upload_columns', 'am_media_columns');

function am_media_column_value($column, $attachment_id) {
  if ($column === 'am_collection') {
    $terms = wp_get_object_terms($attachment_id, AM_MEDIA_TAXONOMY);
    if (is_wp_error($terms) || !$terms) {
      echo '<span style="color:#b32d2e">Needs filing</span>';
      return;
    }
    echo esc_html(implode(', ', wp_list_pluck($terms, 'name')));
  }

  if ($column === 'am_readiness') {
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
add_action('manage_media_custom_column', 'am_media_column_value', 10, 2);

function am_media_collection_grid_filter_script() {
  if (!wp_script_is('media-views', 'enqueued')) return;
  $terms = get_terms([
    'taxonomy'   => AM_MEDIA_TAXONOMY,
    'hide_empty' => false,
    'orderby'    => 'name',
  ]);
  if (is_wp_error($terms)) return;

  $options = [];
  foreach ($terms as $term) {
    $depth = count(get_ancestors($term->term_id, AM_MEDIA_TAXONOMY, 'taxonomy'));
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
        all: { text: 'All collections', props: { am_collection_filter: null }, priority: 10 }
      };
      collections.forEach(function (term, index) {
        filters['collection-' + term.id] = {
          text: term.name,
          props: { am_collection_filter: term.id },
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
    this.toolbar.set('am-media-collection', new CollectionFilter({
      controller: this.controller,
      model: this.collection.props,
      priority: -75
    }).render());
  };
}());
JS;
  wp_add_inline_script('media-views', $script, 'after');
}
add_action('admin_enqueue_scripts', 'am_media_collection_grid_filter_script', 30);

function am_media_library_guidance() {
  $screen = get_current_screen();
  if (!$screen || $screen->base !== 'upload') return;
  echo '<div class="notice notice-info"><p><strong>Media filing rule:</strong> website images and public documents belong here; applicant CVs do not. File every upload into one collection, use descriptive lowercase filenames before upload, and add meaningful alt text to content images.</p></div>';
}
add_action('admin_notices', 'am_media_library_guidance');
