<?php
/**
 * Private storage and authenticated downloads for applicant CVs.
 */

if (!defined('ABSPATH')) exit;

function am_private_application_dir() {
  if (defined('AM_PRIVATE_UPLOAD_DIR') && AM_PRIVATE_UPLOAD_DIR) {
    return trailingslashit(AM_PRIVATE_UPLOAD_DIR) . 'applications';
  }

  // This project's WordPress install lives two levels below the domain/site
  // root (site/app/public locally; domains/.../public_html/alexandra live).
  // Keeping files above both web roots makes direct HTTP access impossible.
  return trailingslashit(dirname(dirname(untrailingslashit(ABSPATH))))
    . 'private-uploads/alexandra-applications';
}

function am_prepare_private_application_dir() {
  $dir = am_private_application_dir();
  if (!wp_mkdir_p($dir)) {
    return new WP_Error('private_dir_failed', 'The private application storage directory is unavailable.');
  }

  $guards = [
    trailingslashit(dirname($dir)) . '.htaccess' => "Options -Indexes\n<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n",
    trailingslashit(dirname($dir)) . 'index.php'  => "<?php\nhttp_response_code(404);\nexit;\n",
    trailingslashit($dir) . 'index.php'           => "<?php\nhttp_response_code(404);\nexit;\n",
    trailingslashit(dirname($dir)) . 'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><security><authorization><remove users=\"*\" roles=\"\" verbs=\"\"/><add accessType=\"Deny\" users=\"*\"/></authorization></security></system.webServer></configuration>\n",
  ];

  foreach ($guards as $path => $contents) {
    if (!file_exists($path)) {
      @file_put_contents($path, $contents, LOCK_EX);
    }
  }

  return $dir;
}

function am_private_application_signature_is_valid($path, $ext) {
  $head = @file_get_contents($path, false, null, 0, 1024);
  if ($head === false) return false;

  if ($ext === 'pdf') {
    return strpos($head, '%PDF-') !== false;
  }
  if ($ext === 'doc') {
    return str_starts_with($head, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1");
  }
  if ($ext === 'docx') {
    if (!str_starts_with($head, "PK\x03\x04")) return false;
    if (!class_exists('ZipArchive')) return true;
    $archive = new ZipArchive();
    if ($archive->open($path) !== true) return false;
    $valid = $archive->locateName('[Content_Types].xml') !== false
      && $archive->locateName('word/document.xml') !== false;
    $archive->close();
    return $valid;
  }
  return false;
}

/**
 * Move a validated browser upload into private storage.
 *
 * @return array|WP_Error Stored file metadata, never a public URL.
 */
function am_store_private_application_file($file, $application_id) {
  if (empty($file['name']) || empty($file['tmp_name'])) {
    return new WP_Error('missing_resume', 'Please attach your CV / resume.', ['status' => 400]);
  }
  if (!empty($file['error'])) {
    return new WP_Error('resume_upload_failed', 'The CV / resume upload did not complete.', ['status' => 400]);
  }
  if ((int) ($file['size'] ?? 0) > 5 * MB_IN_BYTES) {
    return new WP_Error('resume_too_large', 'CV / resume files must be 5 MB or smaller.', ['status' => 400]);
  }

  $allowed = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  ];
  $checked = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed);
  $ext = strtolower((string) ($checked['ext'] ?? ''));
  if (!isset($allowed[$ext])) {
    return new WP_Error('resume_type_invalid', 'Please upload a PDF, DOC, or DOCX CV / resume.', ['status' => 400]);
  }
  if (!am_private_application_signature_is_valid($file['tmp_name'], $ext)) {
    return new WP_Error('resume_content_invalid', 'The CV / resume contents do not match the selected file type.', ['status' => 400]);
  }

  $dir = am_prepare_private_application_dir();
  if (is_wp_error($dir)) {
    return $dir;
  }

  $stored_name = absint($application_id) . '-' . wp_generate_password(32, false, false) . '.' . $ext;
  $destination = trailingslashit($dir) . $stored_name;
  if (!@move_uploaded_file($file['tmp_name'], $destination)) {
    return new WP_Error('resume_move_failed', 'The CV / resume could not be stored securely.', ['status' => 500]);
  }

  @chmod($destination, 0640);

  return [
    'stored_name'   => $stored_name,
    'original_name' => sanitize_file_name($file['name']),
    'mime'          => $allowed[$ext],
    'size'          => filesize($destination),
    'path'          => $destination,
  ];
}

function am_application_private_file_path($application_id) {
  $stored_name = (string) get_post_meta($application_id, '_am_app_cv_file', true);
  if (!$stored_name || basename($stored_name) !== $stored_name) {
    return '';
  }

  $base = realpath(am_private_application_dir());
  $path = realpath(trailingslashit(am_private_application_dir()) . $stored_name);
  if (!$base || !$path || !str_starts_with($path, $base . DIRECTORY_SEPARATOR)) {
    return '';
  }

  return $path;
}

function am_application_download_url($application_id) {
  return wp_nonce_url(
    admin_url('admin-post.php?action=am_download_application_file&application=' . absint($application_id)),
    'am_download_application_file_' . absint($application_id)
  );
}

function am_download_application_file() {
  $application_id = isset($_GET['application']) ? absint($_GET['application']) : 0;
  if (!$application_id || get_post_type($application_id) !== 'am_application') {
    wp_die('Application not found.', '', ['response' => 404]);
  }
  if (!current_user_can('edit_post', $application_id)) {
    wp_die('You are not allowed to view this application file.', '', ['response' => 403]);
  }
  check_admin_referer('am_download_application_file_' . $application_id);

  $path = am_application_private_file_path($application_id);
  if (!$path || !is_readable($path)) {
    wp_die('The application file is unavailable.', '', ['response' => 404]);
  }

  $name = get_post_meta($application_id, '_am_app_cv_name', true) ?: basename($path);
  $mime = get_post_meta($application_id, '_am_app_cv_mime', true) ?: 'application/octet-stream';
  nocache_headers();
  header('Content-Type: ' . sanitize_mime_type($mime));
  header('Content-Length: ' . filesize($path));
  header('Content-Disposition: attachment; filename="' . str_replace('"', '', sanitize_file_name($name)) . '"');
  header('X-Content-Type-Options: nosniff');
  readfile($path);
  exit;
}
add_action('admin_post_am_download_application_file', 'am_download_application_file');

function am_application_file_meta_box() {
  add_meta_box(
    'am_application_private_file',
    'CV attached securely',
    'am_render_application_file_meta_box',
    'am_application',
    'side',
    'high'
  );
}
add_action('add_meta_boxes_am_application', 'am_application_file_meta_box');

function am_application_file_notice() {
  $screen = get_current_screen();
  $application_id = absint($_GET['post'] ?? 0);
  if (!$screen || $screen->post_type !== 'am_application' || !$application_id) return;
  $path = am_application_private_file_path($application_id);
  if (!$path || !current_user_can('edit_post', $application_id)) return;
  $name = get_post_meta($application_id, '_am_app_cv_name', true) ?: basename($path);
  $size = size_format((int) get_post_meta($application_id, '_am_app_cv_size', true), 1);
  echo '<div class="notice notice-success"><p><strong>CV attached securely:</strong> ' . esc_html($name) . ' (' . esc_html($size) . '). It is intentionally excluded from public Media. <a class="button button-small" href="' . esc_url(am_application_download_url($application_id)) . '">Download CV</a></p></div>';
}
add_action('admin_notices', 'am_application_file_notice', 20);

function am_render_application_file_meta_box($post) {
  $path = am_application_private_file_path($post->ID);
  if ($path) {
    $name = get_post_meta($post->ID, '_am_app_cv_name', true) ?: basename($path);
    $size = size_format((int) get_post_meta($post->ID, '_am_app_cv_size', true), 1);
    echo '<p><strong>' . esc_html($name) . '</strong><br><span class="description">' . esc_html($size) . '</span></p>';
    echo '<p><a class="button button-primary" href="' . esc_url(am_application_download_url($post->ID)) . '">Download securely</a></p>';
    echo '<p class="description">Private application file. It is not part of the Media Library.</p>';
    return;
  }

  $legacy_url = get_post_meta($post->ID, '_am_app_cv_url', true);
  if ($legacy_url) {
    echo '<p><a class="button" href="' . esc_url($legacy_url) . '">Open legacy CV</a></p>';
    echo '<p class="description">Legacy Media Library file. New applications use private storage.</p>';
    return;
  }

  echo '<p class="description">No CV / resume is attached.</p>';
}

function am_delete_private_application_file($post_id) {
  if (get_post_type($post_id) !== 'am_application') return;
  $path = am_application_private_file_path($post_id);
  if ($path) {
    @unlink($path);
  }
}
add_action('before_delete_post', 'am_delete_private_application_file');
