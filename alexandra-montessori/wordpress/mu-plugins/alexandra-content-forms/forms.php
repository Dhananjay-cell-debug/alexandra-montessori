<?php
/**
 * Content Forms - one simple form per content type and per settings page,
 * replacing the WordPress editor / CMB2 screens.
 *
 * The forms keep the same URLs (so every existing link and the Editor/Viewer
 * access guard keep working) and save into the exact fields the website
 * already reads: post fields, post meta (CMB2-compatible url + _id pairs,
 * file_list arrays) and option arrays. A site loader passes the config; this
 * file is identical on every site.
 *
 * Store keys: title | excerpt | content | menu_order | thumbnail | category |
 * meta:KEY | opt:KEY | blog_public
 */

if (!defined('ABSPATH')) exit;
if (class_exists('Content_Forms')) return;

final class Content_Forms {
  private static $c = [];
  private static $url = '';
  const VERSION = '1.1.0';

  public static function boot(array $config, $base_file) {
    self::$c = $config;
    self::$url = plugin_dir_url($base_file);
    add_action('load-post-new.php', [__CLASS__, 'take_over_post']);
    add_action('load-post.php', [__CLASS__, 'take_over_post']);
    add_action('admin_menu', [__CLASS__, 'hook_pages'], 9999);
    add_action('admin_post_content_forms_save', [__CLASS__, 'save_post']);
    add_action('admin_post_content_forms_save_page', [__CLASS__, 'save_page']);
  }

  private static function type($post_type) { return self::$c['types'][$post_type] ?? null; }
  private static function page($slug) { return self::$c['pages'][$slug] ?? null; }

  /* ------------------------------------------------------------- screens */

  private static function prepare_screen($screen_id, $title, $parent, $submenu) {
    set_current_screen($screen_id);
    $GLOBALS['parent_file'] = $parent;
    $GLOBALS['submenu_file'] = $submenu;
    $GLOBALS['title'] = $title;
    add_action('admin_enqueue_scripts', function () {
      wp_enqueue_media();
      wp_enqueue_style('content-forms', self::$url . 'assets/forms.css', [], self::VERSION);
      $b = self::$c['brand'] ?? [];
      wp_add_inline_style('content-forms', ':root{--cf-primary:' . ($b['primary'] ?? '#2c5aa0') . ';--cf-primary-dark:' . ($b['primary_dark'] ?? '#1d3c6b') . ';--cf-soft:' . ($b['soft'] ?? '#f5f8fb') . ';}');
      wp_enqueue_script('content-forms', self::$url . 'assets/forms.js', ['jquery'], self::VERSION, true);
    });
    add_filter('admin_body_class', function ($c) { return $c . ' content-forms'; });
    remove_all_actions('admin_notices');
    remove_all_actions('all_admin_notices');
  }

  public static function take_over_post() {
    global $pagenow;
    if ($pagenow === 'post.php') {
      if (($_GET['action'] ?? '') !== 'edit') return;
      $post = get_post(absint($_GET['post'] ?? 0));
      if (!$post || !self::type($post->post_type)) return;
      if (!current_user_can('edit_post', $post->ID)) return; // core shows its own error
      $type = $post->post_type;
    } else {
      $type = sanitize_key($_GET['post_type'] ?? 'post');
      if (!self::type($type)) return;
      $obj = get_post_type_object($type);
      if (!$obj || !current_user_can($obj->cap->create_posts)) return;
      $post = null;
    }

    $cfg = self::type($type);
    $obj = get_post_type_object($type);
    self::prepare_screen(
      'content-forms-' . $type,
      ($post ? 'Edit ' : 'New ') . strtolower($cfg['singular']),
      is_string($obj->show_in_menu) ? $obj->show_in_menu : 'edit.php?post_type=' . $type,
      'edit.php?post_type=' . $type
    );
    require_once ABSPATH . 'wp-admin/admin-header.php';
    self::render_post($type, $cfg, $post);
    require ABSPATH . 'wp-admin/admin-footer.php';
    exit;
  }

  /** Settings pages (CMB2 options pages) are replaced on their own load hook. */
  public static function hook_pages() {
    foreach ((self::$c['pages'] ?? []) as $slug => $cfg) {
      $hook = get_plugin_page_hookname($slug, $cfg['parent'] ?? '');
      if (!$hook) continue;
      add_action('load-' . $hook, function () use ($slug, $cfg) {
        if (!current_user_can($cfg['capability'] ?? 'manage_options')) return;
        self::prepare_screen('content-forms-' . $slug, $cfg['title'], $cfg['parent'] ?? '', $slug);
        require_once ABSPATH . 'wp-admin/admin-header.php';
        self::render_page($slug, $cfg);
        require ABSPATH . 'wp-admin/admin-footer.php';
        exit;
      });
    }
  }

  private static function pop_notice() {
    $key = 'content_forms_notice_' . get_current_user_id();
    $notice = get_transient($key);
    if ($notice) delete_transient($key);
    if (!$notice) return;
    echo '<div class="cf-notice cf-notice--' . esc_attr($notice['kind']) . '"><p>' . esc_html($notice['text']);
    if (!empty($notice['link'])) echo ' <a href="' . esc_url($notice['link']) . '" target="_blank" rel="noopener">View on website</a>';
    echo '</p></div>';
  }

  private static function render_post($type, $cfg, $post) {
    $list = admin_url($type === 'post' ? 'edit.php' : 'edit.php?post_type=' . $type);
    $published = $post && $post->post_status === 'publish';

    echo '<div class="wrap cf-wrap">';
    echo '<a class="cf-back" href="' . esc_url($list) . '">&larr; All ' . esc_html(strtolower($cfg['plural'])) . '</a>';
    echo '<div class="cf-head"><h1>' . esc_html(($post ? 'Edit ' : 'New ') . strtolower($cfg['singular'])) . '</h1>';
    if ($post) echo '<span class="cf-status cf-status--' . ($published ? 'live' : 'draft') . '">' . ($published ? 'Published' : 'Draft') . '</span>';
    echo '</div>';
    self::pop_notice();

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="cf-form" novalidate>';
    echo '<input type="hidden" name="action" value="content_forms_save">';
    echo '<input type="hidden" name="cf_type" value="' . esc_attr($type) . '">';
    echo '<input type="hidden" name="cf_post" value="' . ($post ? (int) $post->ID : 0) . '">';
    wp_nonce_field('content_forms_save', 'cf_nonce');
    foreach ($cfg['fields'] as $key => $field) self::field($key, $field, self::read_post($post, $field));

    echo '<div class="cf-actions">';
    if ($post) echo '<a class="cf-delete" href="' . esc_url(get_delete_post_link($post->ID)) . '">Move to bin</a>';
    if ($published) {
      echo '<button type="submit" name="cf_do" value="draft" class="button cf-btn">Unpublish</button>';
      echo '<button type="submit" name="cf_do" value="publish" class="button button-primary cf-btn cf-btn--primary">Save changes</button>';
    } else {
      echo '<button type="submit" name="cf_do" value="draft" class="button cf-btn">Save draft</button>';
      echo '<button type="submit" name="cf_do" value="publish" class="button button-primary cf-btn cf-btn--primary">Publish</button>';
    }
    echo '</div></form></div>';
  }

  private static function render_page($slug, $cfg) {
    $option = get_option($cfg['option'], []);
    if (!is_array($option)) $option = [];

    echo '<div class="wrap cf-wrap">';
    if (!empty(self::$c['hub'])) echo '<a class="cf-back" href="' . esc_url(admin_url(self::$c['hub']['url'])) . '">&larr; ' . esc_html(self::$c['hub']['label']) . '</a>';
    echo '<div class="cf-head"><h1>' . esc_html($cfg['title']) . '</h1></div>';
    if (!empty($cfg['intro'])) echo '<p class="cf-intro">' . esc_html($cfg['intro']) . '</p>';
    self::pop_notice();

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="cf-form" novalidate>';
    echo '<input type="hidden" name="action" value="content_forms_save_page">';
    echo '<input type="hidden" name="cf_page" value="' . esc_attr($slug) . '">';
    wp_nonce_field('content_forms_save_page', 'cf_nonce');
    foreach ($cfg['fields'] as $key => $field) self::field($key, $field, self::read_option($option, $field));
    echo '<div class="cf-actions"><button type="submit" class="button button-primary cf-btn cf-btn--primary">Save changes</button></div>';
    echo '</form></div>';
  }

  /* ---------------------------------------------------------------- read */

  /** CMB2 'file' fields store the URL under KEY and the attachment under KEY_id. */
  private static function file_value($url, $id) {
    $url = trim((string) $url);
    $id = absint($id);
    if (!$id && $url !== '') $id = (int) attachment_url_to_postid($url);
    if ($id && !$url) $url = (string) wp_get_attachment_url($id);
    return $url === '' && !$id ? '' : ['id' => $id, 'url' => $url];
  }

  private static function read_post($post, $f) {
    if ($f['type'] === 'heading') return '';
    if (!$post) return $f['default'] ?? '';
    $store = $f['store'];
    if ($store === 'title') return $post->post_title;
    if ($store === 'excerpt') return $post->post_excerpt;
    if ($store === 'content') return $post->post_content;
    if ($store === 'menu_order') return (string) $post->menu_order;
    if ($store === 'thumbnail') {
      $id = (int) get_post_thumbnail_id($post);
      return $id ? ['id' => $id, 'url' => (string) wp_get_attachment_url($id)] : '';
    }
    if ($store === 'category') {
      $cats = wp_get_post_categories($post->ID);
      return $cats ? (int) $cats[0] : 0;
    }
    if (strpos($store, 'meta:') === 0) {
      $key = substr($store, 5);
      if (in_array($f['type'], ['image', 'file'], true)) return self::file_value(get_post_meta($post->ID, $key, true), get_post_meta($post->ID, $key . '_id', true));
      return get_post_meta($post->ID, $key, true);
    }
    return '';
  }

  private static function read_option($o, $f) {
    if ($f['type'] === 'heading') return '';
    $store = $f['store'];
    if ($store === 'blog_public') return get_option('blog_public', 1) ? 'on' : '';
    if (strpos($store, 'opt:') !== 0) return '';
    $key = substr($store, 4);
    if (in_array($f['type'], ['image', 'file'], true)) return self::file_value($o[$key] ?? '', $o[$key . '_id'] ?? 0);
    return $o[$key] ?? ($f['default'] ?? '');
  }

  /* -------------------------------------------------------------- fields */

  private static function options($field, $current) {
    $options = $field['options'] ?? [];
    if (!empty($field['options_cb']) && is_callable($field['options_cb'])) $options = call_user_func($field['options_cb']);
    if ($field['type'] === 'category') {
      $options = [];
      foreach (get_categories(['hide_empty' => false]) as $cat) {
        if ($cat->slug !== 'uncategorized') $options[$cat->term_id] = $cat->name;
      }
    }
    // Never silently lose a value saved before this form existed.
    if ($current !== '' && $current !== 0 && !isset($options[$current])) $options[$current] = $current;
    return $options;
  }

  private static function media_token($item) {
    if (!is_array($item)) return '';
    return $item['id'] ? (string) $item['id'] : ($item['url'] ? 'url:' . $item['url'] : '');
  }

  private static function field($key, $f, $value) {
    if ($f['type'] === 'heading') {
      echo '<h2 class="cf-section">' . esc_html($f['label']) . '</h2>';
      if (!empty($f['help'])) echo '<p class="cf-section-help">' . esc_html($f['help']) . '</p>';
      return;
    }
    $name = 'cf[' . $key . ']';
    $id = 'cf_' . $key;
    $req = !empty($f['required']);
    echo '<div class="cf-field cf-field--' . esc_attr($f['type']) . (!empty($f['half']) ? ' cf-field--half' : '') . '">';
    echo '<label class="cf-label" for="' . esc_attr($id) . '">' . esc_html($f['label']);
    echo $req ? ' <span class="cf-req" aria-hidden="true">*</span>' : ' <span class="cf-opt">optional</span>';
    echo '</label>';

    switch ($f['type']) {
      case 'text':
      case 'email':
      case 'url':
      case 'number':
        echo '<input class="cf-input' . ($key === 'title' ? ' cf-input--title' : '') . ($f['type'] === 'number' ? ' cf-input--tiny' : '') . '" type="' . esc_attr($f['type']) . '" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($f['placeholder'] ?? '') . '">';
        break;
      case 'textarea':
        $limit = (int) ($f['limit'] ?? 0);
        echo '<textarea class="cf-input" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" rows="' . (int) ($f['rows'] ?? 3) . '" placeholder="' . esc_attr($f['placeholder'] ?? '') . '"' . ($limit ? ' data-cf-limit="' . $limit . '"' : '') . '>' . esc_textarea($value) . '</textarea>';
        if ($limit) echo '<span class="cf-count" data-cf-count-for="' . esc_attr($id) . '"></span>';
        break;
      case 'richtext':
        wp_editor((string) $value, $id, [
          'textarea_name' => $name,
          'media_buttons' => !empty($f['photos']),
          'quicktags' => false,
          'editor_height' => (int) ($f['height'] ?? 260),
          'tinymce' => [
            'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink',
            'toolbar2' => '',
            'block_formats' => 'Paragraph=p;Heading=h2;Small heading=h3',
            'statusbar' => false,
          ],
        ]);
        break;
      case 'date':
        echo '<input class="cf-input cf-input--short" type="date" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
        break;
      case 'timerange':
        [$start, $end, $allday, $custom] = self::parse_time((string) $value);
        echo '<div class="cf-time">';
        if ($custom !== '') {
          echo '<input class="cf-input" type="text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '[text]" value="' . esc_attr($custom) . '">';
        } else {
          echo '<input class="cf-input cf-input--short" type="time" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '[start]" value="' . esc_attr($start) . '" aria-label="Start time">';
          echo '<span>to</span><input class="cf-input cf-input--short" type="time" name="' . esc_attr($name) . '[end]" value="' . esc_attr($end) . '" aria-label="End time">';
          echo '<label class="cf-check"><input type="checkbox" name="' . esc_attr($name) . '[allday]" value="1"' . checked($allday, true, false) . '> All day</label>';
        }
        echo '</div>';
        break;
      case 'select':
      case 'category':
        echo '<select class="cf-input cf-input--short" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '"><option value="">Choose…</option>';
        foreach (self::options($f, $value) as $v => $label) {
          echo '<option value="' . esc_attr($v) . '"' . selected((string) $value, (string) $v, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        break;
      case 'checkbox':
        echo '<label class="cf-check"><input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="on"' . checked(!empty($value), true, false) . '> ' . esc_html($f['checkbox_label'] ?? 'Yes') . '</label>';
        break;
      case 'image':
      case 'file':
        $is_image = $f['type'] === 'image';
        $url = is_array($value) ? $value['url'] : '';
        $label = $is_image ? 'photo' : 'file';
        echo '<div class="cf-image" data-cf-media="' . ($is_image ? 'image' : esc_attr($f['mime'] ?? 'application/pdf')) . '">';
        echo '<input type="hidden" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr(self::media_token($value)) . '">';
        if ($is_image) {
          echo '<div class="cf-image__preview"' . ($url ? '' : ' hidden') . '><img src="' . esc_url($url) . '" alt=""></div>';
        } else {
          echo '<p class="cf-file"' . ($url ? '' : ' hidden') . '><a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($url ? wp_basename($url) : '') . '</a></p>';
        }
        echo '<button type="button" class="button cf-btn" data-cf-pick>' . ($url ? 'Change ' . $label : 'Upload ' . $label) . '</button> ';
        echo '<button type="button" class="button-link cf-image__remove" data-cf-remove' . ($url ? '' : ' hidden') . '>Remove</button>';
        echo '</div>';
        break;
      case 'gallery':
        $items = [];
        foreach ((is_array($value) ? $value : []) as $k => $u) {
          $aid = is_numeric($k) && wp_attachment_is_image((int) $k) ? (int) $k : 0;
          $items[] = ['id' => $aid, 'url' => (string) $u];
        }
        echo '<div class="cf-gallery" data-cf-gallery>';
        echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr(wp_json_encode($items)) . '">';
        echo '<ul class="cf-gallery__list"></ul>';
        echo '<button type="button" class="button cf-btn" data-cf-gallery-add>Add photos</button>';
        echo '</div>';
        break;
      case 'repeater':
        $rows = is_array($value) ? array_values($value) : [];
        echo '<div class="cf-repeater" data-cf-repeater data-name="' . esc_attr($name) . '">';
        echo '<template><div class="cf-repeater__row">';
        foreach ($f['columns'] as $col => $c) echo '<input class="cf-input" type="' . esc_attr($c['type'] ?? 'text') . '" data-col="' . esc_attr($col) . '" placeholder="' . esc_attr($c['placeholder'] ?? $c['label']) . '" aria-label="' . esc_attr($c['label']) . '">';
        echo '<button type="button" class="button-link cf-image__remove" data-cf-row-remove>Remove</button></div></template>';
        echo '<div class="cf-repeater__rows">';
        foreach ($rows as $i => $row) {
          echo '<div class="cf-repeater__row">';
          foreach ($f['columns'] as $col => $c) echo '<input class="cf-input" type="' . esc_attr($c['type'] ?? 'text') . '" name="' . esc_attr($name . '[' . $i . '][' . $col . ']') . '" data-col="' . esc_attr($col) . '" value="' . esc_attr($row[$col] ?? '') . '" placeholder="' . esc_attr($c['placeholder'] ?? $c['label']) . '" aria-label="' . esc_attr($c['label']) . '">';
          echo '<button type="button" class="button-link cf-image__remove" data-cf-row-remove>Remove</button></div>';
        }
        echo '</div><button type="button" class="button cf-btn" data-cf-row-add>' . esc_html($f['add_label'] ?? 'Add') . '</button></div>';
        break;
    }
    if (!empty($f['help'])) echo '<p class="cf-help">' . esc_html($f['help']) . '</p>';
    echo '</div>';
  }

  /** "9:30am – 11:30am" -> ['09:30','11:30',false,''] ; unparseable text -> custom. */
  private static function parse_time($text) {
    $text = trim($text);
    if ($text === '') return ['', '', false, ''];
    if (preg_match('/^all\s*day$/i', $text)) return ['', '', true, ''];
    $one = '(\d{1,2})(?:[:.](\d{2}))?\s*(am|pm)?';
    if (preg_match('/^' . $one . '\s*(?:[–—-]\s*' . $one . ')?$/iu', $text, $m)) {
      $end_ap = $m[6] ?? '';
      $to24 = function ($h, $min, $ap) {
        $h = (int) $h; $ap = strtolower((string) $ap);
        if ($ap === 'pm' && $h < 12) $h += 12;
        if ($ap === 'am' && $h === 12) $h = 0;
        return sprintf('%02d:%02d', $h, (int) $min);
      };
      $start = $to24($m[1], $m[2] ?? 0, ($m[3] ?? '') ?: $end_ap);
      $end = !empty($m[4]) ? $to24($m[4], $m[5] ?? 0, $end_ap) : '';
      return [$start, $end, false, ''];
    }
    return ['', '', false, $text];
  }

  private static function format_time($raw) {
    if (!is_array($raw)) return sanitize_text_field((string) $raw);
    if (isset($raw['text'])) return sanitize_text_field($raw['text']);
    if (!empty($raw['allday'])) return 'All day';
    $fmt = function ($v) {
      if (!preg_match('/^(\d{2}):(\d{2})$/', (string) $v, $m)) return '';
      $h = (int) $m[1]; $ap = $h >= 12 ? 'pm' : 'am'; $h = $h % 12 ?: 12;
      return $h . ($m[2] === '00' ? '' : ':' . $m[2]) . $ap;
    };
    $start = $fmt($raw['start'] ?? ''); $end = $fmt($raw['end'] ?? '');
    return $start && $end ? $start . " \u{2013} " . $end : $start;
  }

  /* --------------------------------------------------------------- clean */

  private static function is_empty($v) {
    return $v === '' || $v === 0 || $v === [] || $v === null;
  }

  private static function clean($f, $raw) {
    // Browsers submit textarea line breaks as CRLF; store plain LF like before.
    if (is_string($raw)) $raw = str_replace(["\r\n", "\r"], "\n", $raw);
    switch ($f['type']) {
      case 'richtext': return trim(wp_kses_post((string) $raw));
      case 'textarea': return trim(sanitize_textarea_field((string) $raw));
      case 'email': return sanitize_email((string) $raw);
      case 'url': return esc_url_raw(trim((string) $raw));
      case 'number': return (string) (int) $raw;
      case 'date': return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $raw) ? (string) $raw : '';
      case 'timerange': return self::format_time($raw);
      case 'checkbox': return $raw ? 'on' : '';
      case 'image':
      case 'file':
        $raw = (string) $raw;
        if (strpos($raw, 'url:') === 0) {
          $url = esc_url_raw(substr($raw, 4));
          return $url ? ['id' => 0, 'url' => $url] : '';
        }
        $id = absint($raw);
        if (!$id || get_post_type($id) !== 'attachment') return '';
        if ($f['type'] === 'image' && !wp_attachment_is_image($id)) return '';
        return ['id' => $id, 'url' => (string) wp_get_attachment_url($id)];
      case 'gallery':
        $list = json_decode((string) $raw, true);
        $out = [];
        foreach ((is_array($list) ? $list : []) as $i => $item) {
          $aid = absint($item['id'] ?? 0);
          if ($aid && wp_attachment_is_image($aid)) $out[$aid] = (string) wp_get_attachment_url($aid);
          elseif (!empty($item['url'])) $out['url-' . $i] = esc_url_raw($item['url']);
        }
        return $out;
      case 'repeater':
        $rows = [];
        foreach ((is_array($raw) ? $raw : []) as $row) {
          $clean = [];
          foreach ($f['columns'] as $col => $c) {
            $v = (string) ($row[$col] ?? '');
            $clean[$col] = ($c['type'] ?? 'text') === 'url' ? esc_url_raw(trim($v)) : sanitize_text_field($v);
          }
          if (implode('', $clean) !== '') $rows[] = $clean;
        }
        return $rows;
      case 'category':
        $id = absint($raw);
        return $id && term_exists($id, 'category') ? $id : 0;
      case 'select':
        $raw = sanitize_text_field((string) $raw);
        $options = $f['options'] ?? [];
        if (!empty($f['options_cb']) && is_callable($f['options_cb'])) $options = call_user_func($f['options_cb']);
        return isset($options[$raw]) || ($raw !== '' && !empty($f['allow_other'])) ? $raw : '';
      default: return trim(sanitize_text_field((string) $raw));
    }
  }

  private static function collect($fields) {
    $in = wp_unslash($_POST['cf'] ?? []);
    $values = [];
    $missing = [];
    foreach ($fields as $key => $f) {
      if ($f['type'] === 'heading') continue;
      $values[$key] = self::clean($f, $in[$key] ?? null);
      if (!empty($f['required']) && self::is_empty($values[$key])) $missing[] = strtolower($f['label']);
    }
    return [$values, $missing];
  }

  private static function notice($kind, $text, $link = '') {
    set_transient('content_forms_notice_' . get_current_user_id(), ['kind' => $kind, 'text' => $text, 'link' => $link], 120);
  }

  /* ---------------------------------------------------------------- save */

  public static function save_post() {
    $type = sanitize_key($_POST['cf_type'] ?? '');
    $cfg = self::type($type);
    if (!$cfg || !check_admin_referer('content_forms_save', 'cf_nonce')) wp_die('This form has expired. Go back and try again.');
    $post_id = absint($_POST['cf_post'] ?? 0);
    $obj = get_post_type_object($type);

    if ($post_id) {
      $existing = get_post($post_id);
      if (!$existing || $existing->post_type !== $type || !current_user_can('edit_post', $post_id)) wp_die('You cannot edit this item.');
    } elseif (!current_user_can($obj->cap->create_posts)) {
      wp_die('You cannot create this item.');
    }
    // Same section allocation the rest of wp-admin enforces for Editors.
    if (!empty(self::$c['access_check']) && is_callable(self::$c['access_check'])
        && !call_user_func(self::$c['access_check'], $type, $post_id)) {
      wp_die('This section is not allocated to your account.');
    }

    [$values, $missing] = self::collect($cfg['fields']);
    $want_publish = ($_POST['cf_do'] ?? '') === 'publish';
    $can_publish = current_user_can($obj->cap->publish_posts);
    $status = $want_publish && !$missing && $can_publish ? 'publish' : 'draft';

    $data = ['post_type' => $type, 'post_status' => $status];
    foreach ($cfg['fields'] as $key => $f) {
      if (!isset($f['store'])) continue;
      if ($f['store'] === 'title') $data['post_title'] = $values[$key];
      if ($f['store'] === 'excerpt') $data['post_excerpt'] = $values[$key];
      if ($f['store'] === 'content') $data['post_content'] = $values[$key];
      if ($f['store'] === 'menu_order') $data['menu_order'] = (int) $values[$key];
    }
    $keep_title = $post_id && trim((string) get_post_field('post_title', $post_id)) !== '';
    if (!empty($cfg['auto_title']) && !$keep_title) {
      $parts = array_filter(array_map(function ($k) use ($values) { return $values[$k] ?? ''; }, $cfg['auto_title']));
      $data['post_title'] = implode(' - ', $parts) ?: ('Untitled ' . strtolower($cfg['singular']));
    }
    if (!isset($data['post_title'])) $data['post_title'] = $keep_title ? get_post_field('post_title', $post_id) : '';

    if ($post_id) {
      $data['ID'] = $post_id;
      $result = wp_update_post(wp_slash($data), true);
    } else {
      $result = wp_insert_post(wp_slash($data), true);
    }
    if (is_wp_error($result)) wp_die(esc_html($result->get_error_message()));
    $post_id = (int) $result;

    foreach ($cfg['fields'] as $key => $f) {
      if (!isset($f['store'])) continue;
      $v = $values[$key];
      $store = $f['store'];
      if ($store === 'thumbnail') {
        is_array($v) && $v['id'] ? set_post_thumbnail($post_id, $v['id']) : delete_post_thumbnail($post_id);
      } elseif ($store === 'category') {
        wp_set_post_categories($post_id, $v ? [$v] : []);
      } elseif (strpos($store, 'meta:') === 0) {
        $meta = substr($store, 5);
        if (in_array($f['type'], ['image', 'file'], true)) {
          if (is_array($v)) {
            update_post_meta($post_id, $meta, $v['url']);
            $v['id'] ? update_post_meta($post_id, $meta . '_id', $v['id']) : delete_post_meta($post_id, $meta . '_id');
          } else {
            delete_post_meta($post_id, $meta);
            delete_post_meta($post_id, $meta . '_id');
          }
        } else {
          self::is_empty($v) ? delete_post_meta($post_id, $meta) : update_post_meta($post_id, $meta, $v);
        }
      }
    }

    $label = $cfg['singular'];
    if ($status === 'publish') {
      $link = !empty($cfg['route']) ? home_url(str_replace('%slug%', get_post_field('post_name', $post_id), $cfg['route'])) : '';
      self::notice('ok', $label . ' published.', $link);
    } elseif ($want_publish && $missing) {
      self::notice('warn', 'Saved as a draft. To publish, add: ' . implode(', ', $missing) . '.');
    } elseif ($want_publish) {
      self::notice('warn', 'Saved as a draft. Your account cannot publish - ask an administrator.');
    } else {
      self::notice('ok', 'Draft saved. It is not on the website.');
    }
    wp_safe_redirect(admin_url('post.php?post=' . $post_id . '&action=edit'));
    exit;
  }

  public static function save_page() {
    $slug = sanitize_key($_POST['cf_page'] ?? '');
    $cfg = self::page($slug);
    if (!$cfg || !check_admin_referer('content_forms_save_page', 'cf_nonce')) wp_die('This form has expired. Go back and try again.');
    if (!current_user_can($cfg['capability'] ?? 'manage_options')) wp_die('You cannot change these settings.');
    if (!empty(self::$c['page_access_check']) && is_callable(self::$c['page_access_check'])
        && !call_user_func(self::$c['page_access_check'], $slug)) {
      wp_die('This section is not allocated to your account.');
    }

    [$values, $missing] = self::collect($cfg['fields']);
    $back = admin_url(($cfg['parent_is_admin_page'] ?? true) ? 'admin.php?page=' . $slug : $slug);
    if ($missing) {
      self::notice('warn', 'Not saved. Please fill in: ' . implode(', ', $missing) . '.');
      wp_safe_redirect($back);
      exit;
    }

    // Merge, so keys this form does not manage are never dropped.
    $option = get_option($cfg['option'], []);
    if (!is_array($option)) $option = [];
    foreach ($cfg['fields'] as $key => $f) {
      if (!isset($f['store'])) continue;
      $v = $values[$key];
      if ($f['store'] === 'blog_public') {
        update_option('blog_public', $v ? 1 : 0);
        unset($option['search_engine_visible']);
        continue;
      }
      if (strpos($f['store'], 'opt:') !== 0) continue;
      $k = substr($f['store'], 4);
      if (in_array($f['type'], ['image', 'file'], true)) {
        $option[$k] = is_array($v) ? $v['url'] : '';
        if (is_array($v) && $v['id']) $option[$k . '_id'] = $v['id']; else unset($option[$k . '_id']);
      } else {
        $option[$k] = $v;
      }
    }
    update_option($cfg['option'], $option);
    self::notice('ok', 'Saved. The website now uses these details.', home_url($cfg['route'] ?? '/'));
    wp_safe_redirect($back);
    exit;
  }
}
