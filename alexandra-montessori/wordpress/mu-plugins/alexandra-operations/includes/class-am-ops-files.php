<?php
/**
 * Private applicant-file validation, metadata and permission-gated downloads.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_Ops_Files {
	const MAX_BYTES = 5242880;

	/**
	 * @return void
	 */
	public static function register() {
		add_action('admin_post_am_ops_download_file', array(__CLASS__, 'download'));
	}

	/**
	 * Private storage lives outside the public WordPress web root.
	 *
	 * @return string
	 */
	public static function directory() {
		if (defined('AM_PRIVATE_UPLOAD_DIR') && AM_PRIVATE_UPLOAD_DIR) {
			return trailingslashit((string) AM_PRIVATE_UPLOAD_DIR) . 'applications';
		}

		return trailingslashit(dirname(dirname(untrailingslashit(ABSPATH))))
			. 'private-uploads/alexandra-applications';
	}

	/**
	 * Validate and move a PHP upload to an opaque private key.
	 *
	 * The returned metadata is not attached to a submission until attach() is
	 * called. Call discard() if the database transaction cannot complete.
	 *
	 * @param array<string,mixed> $file One $_FILES entry.
	 * @return array<string,mixed>|WP_Error
	 */
	public function prepare_upload($file) {
		if (
			empty($file['name'])
			|| empty($file['tmp_name'])
			|| !isset($file['error'])
		) {
			return new WP_Error(
				'am_ops_resume_missing',
				__('Please attach your CV / resume.', 'alexandra-operations'),
				array('status' => 400)
			);
		}
		if (UPLOAD_ERR_OK !== (int) $file['error']) {
			return new WP_Error(
				'am_ops_resume_upload_failed',
				__('The CV / resume upload did not complete.', 'alexandra-operations'),
				array('status' => 400)
			);
		}

		$size = (int) ($file['size'] ?? 0);
		if ($size < 1 || $size > self::MAX_BYTES) {
			return new WP_Error(
				'am_ops_resume_size',
				__('CV / resume files must be 5 MB or smaller.', 'alexandra-operations'),
				array('status' => 400)
			);
		}

		$allowed = array(
			'pdf'  => 'application/pdf',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		);
		$checked = wp_check_filetype_and_ext(
			(string) $file['tmp_name'],
			(string) $file['name'],
			$allowed
		);
		$extension = strtolower((string) ($checked['ext'] ?? ''));
		if (!isset($allowed[$extension])) {
			return new WP_Error(
				'am_ops_resume_type',
				__('Please upload a PDF, DOC, or DOCX CV / resume.', 'alexandra-operations'),
				array('status' => 400)
			);
		}
		if (!$this->signature_is_valid((string) $file['tmp_name'], $extension)) {
			return new WP_Error(
				'am_ops_resume_signature',
				__('The CV / resume contents do not match its file type.', 'alexandra-operations'),
				array('status' => 400)
			);
		}

		$directory = $this->prepare_directory();
		if (is_wp_error($directory)) {
			return $directory;
		}

		$storage_key = 'app-' . wp_generate_password(40, false, false) . '.' . $extension;
		$destination = trailingslashit($directory) . $storage_key;
		if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
			return new WP_Error(
				'am_ops_resume_move_failed',
				__('The CV / resume could not be stored securely.', 'alexandra-operations'),
				array('status' => 500)
			);
		}

		@chmod($destination, 0640);
		$sha256 = hash_file('sha256', $destination);
		if (!$sha256) {
			@unlink($destination);
			return new WP_Error(
				'am_ops_resume_hash_failed',
				__('The CV / resume could not be verified after storage.', 'alexandra-operations'),
				array('status' => 500)
			);
		}

		return array(
			'storage_key'      => $storage_key,
			'original_filename'=> sanitize_file_name((string) $file['name']),
			'mime_type'        => $allowed[$extension],
			'byte_size'        => (int) filesize($destination),
			'sha256'           => $sha256,
			'validation_state' => 'validated',
			'path'             => $destination,
		);
	}

	/**
	 * Attach prepared metadata to one submission and audit it.
	 *
	 * @param int                 $submission_id Submission ID.
	 * @param array<string,mixed> $prepared Result from prepare_upload().
	 * @return int|WP_Error File ID.
	 */
	public function attach($submission_id, $prepared) {
		global $wpdb;

		$storage_key = (string) ($prepared['storage_key'] ?? '');
		if (
			'' === $storage_key
			|| basename($storage_key) !== $storage_key
			|| empty($prepared['sha256'])
		) {
			return new WP_Error('am_ops_invalid_prepared_file', __('Invalid private file metadata.', 'alexandra-operations'));
		}

		$table = AM_Ops_Tables::name(AM_Ops_Tables::FILES);
		$wpdb->query('START TRANSACTION');
		$inserted = $wpdb->insert(
			$table,
			array(
				'submission_id'    => (int) $submission_id,
				'storage_key'      => $storage_key,
				'original_filename'=> sanitize_file_name((string) ($prepared['original_filename'] ?? 'resume')),
				'mime_type'        => sanitize_mime_type((string) ($prepared['mime_type'] ?? 'application/octet-stream')),
				'byte_size'        => max(0, (int) ($prepared['byte_size'] ?? 0)),
				'sha256'           => (string) $prepared['sha256'],
				'validation_state' => sanitize_key((string) ($prepared['validation_state'] ?? 'validated')),
				'created_at'       => gmdate('Y-m-d H:i:s'),
			)
		);
		if (false === $inserted) {
			$wpdb->query('ROLLBACK');
			return new WP_Error(
				'am_ops_file_metadata_failed',
				__('The private file metadata could not be stored.', 'alexandra-operations')
			);
		}

		$file_id = (int) $wpdb->insert_id;
		$event   = (new AM_Ops_Repository())->add_event(
			(int) $submission_id,
			'file_attached',
			array(
				'file_id'    => $file_id,
				'mime_type'  => sanitize_mime_type((string) $prepared['mime_type']),
				'byte_size'  => max(0, (int) $prepared['byte_size']),
			)
		);
		if (is_wp_error($event)) {
			$wpdb->query('ROLLBACK');
			return $event;
		}

		$wpdb->query('COMMIT');

		return $file_id;
	}

	/**
	 * Delete only a just-prepared, unregistered file.
	 *
	 * @param array<string,mixed> $prepared Prepared file metadata.
	 * @return bool
	 */
	public function discard($prepared) {
		$path = $this->path((string) ($prepared['storage_key'] ?? ''));

		return $path && is_file($path) ? @unlink($path) : false;
	}

	/**
	 * @param int $submission_id Submission ID.
	 * @return array<string,mixed>|null
	 */
	public function for_submission($submission_id) {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . AM_Ops_Tables::name(AM_Ops_Tables::FILES)
				. ' WHERE submission_id = %d ORDER BY id ASC LIMIT 1',
				(int) $submission_id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	/**
	 * Return a private real path only when it remains inside the storage root.
	 *
	 * @param string $storage_key Opaque basename.
	 * @return string
	 */
	public function path($storage_key) {
		if ('' === $storage_key || basename($storage_key) !== $storage_key) {
			return '';
		}

		$base = realpath(self::directory());
		$path = realpath(trailingslashit(self::directory()) . $storage_key);
		if (!$base || !$path || 0 !== strpos($path, $base . DIRECTORY_SEPARATOR)) {
			return '';
		}

		return $path;
	}

	/**
	 * Build a nonce-protected download URL.
	 *
	 * @param int $file_id Private-file row.
	 * @return string
	 */
	public static function download_url($file_id) {
		return wp_nonce_url(
			admin_url('admin-post.php?action=am_ops_download_file&file=' . absint($file_id)),
			'am_ops_download_file_' . absint($file_id)
		);
	}

	/**
	 * Stream an authorized file without exposing its storage key.
	 *
	 * @return void
	 */
	public static function download() {
		global $wpdb;

		$file_id = absint($_GET['file'] ?? 0);
		if (!$file_id) {
			wp_die('File not found.', '', array('response' => 404));
		}
		if (!current_user_can('view_am_private_files')) {
			wp_die('You are not allowed to view this private file.', '', array('response' => 403));
		}
		check_admin_referer('am_ops_download_file_' . $file_id);

		$file = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . AM_Ops_Tables::name(AM_Ops_Tables::FILES) . ' WHERE id = %d LIMIT 1',
				$file_id
			),
			ARRAY_A
		);
		$service = new self();
		$path    = $file ? $service->path((string) $file['storage_key']) : '';
		if (!$file || !$path || !is_readable($path)) {
			wp_die('The private file is unavailable.', '', array('response' => 404));
		}

		(new AM_Ops_Repository())->add_event(
			(int) $file['submission_id'],
			'file_downloaded',
			array('file_id' => $file_id),
			get_current_user_id(),
			AM_Ops_Security::ip_hash()
		);

		nocache_headers();
		header('Content-Type: ' . sanitize_mime_type((string) $file['mime_type']));
		header('Content-Length: ' . filesize($path));
		header(
			'Content-Disposition: attachment; filename="'
			. str_replace('"', '', sanitize_file_name((string) $file['original_filename']))
			. '"'
		);
		header('X-Content-Type-Options: nosniff');
		readfile($path);
		exit;
	}

	/**
	 * @return string|WP_Error
	 */
	private function prepare_directory() {
		$directory = self::directory();
		if (!wp_mkdir_p($directory)) {
			return new WP_Error(
				'am_ops_private_directory_failed',
				__('Private application storage is unavailable.', 'alexandra-operations'),
				array('status' => 503)
			);
		}

		$parent = dirname($directory);
		$guards = array(
			trailingslashit($parent) . '.htaccess' => "Options -Indexes\n<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n",
			trailingslashit($parent) . 'index.php'  => "<?php\nhttp_response_code(404);\nexit;\n",
			trailingslashit($directory) . 'index.php' => "<?php\nhttp_response_code(404);\nexit;\n",
			trailingslashit($parent) . 'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><security><authorization><remove users=\"*\" roles=\"\" verbs=\"\"/><add accessType=\"Deny\" users=\"*\"/></authorization></security></system.webServer></configuration>\n",
		);
		foreach ($guards as $path => $contents) {
			if (!file_exists($path)) {
				@file_put_contents($path, $contents, LOCK_EX);
			}
		}

		return $directory;
	}

	/**
	 * @param string $path Temporary file.
	 * @param string $extension Validated extension.
	 * @return bool
	 */
	private function signature_is_valid($path, $extension) {
		$head = @file_get_contents($path, false, null, 0, 1024);
		if (false === $head) {
			return false;
		}
		if ('pdf' === $extension) {
			return false !== strpos($head, '%PDF-');
		}
		if ('doc' === $extension) {
			return 0 === strpos($head, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1");
		}
		if ('docx' !== $extension || 0 !== strpos($head, "PK\x03\x04")) {
			return false;
		}
		if (!class_exists('ZipArchive')) {
			return true;
		}

		$archive = new ZipArchive();
		if (true !== $archive->open($path)) {
			return false;
		}
		$valid = false !== $archive->locateName('[Content_Types].xml')
			&& false !== $archive->locateName('word/document.xml');
		$archive->close();

		return $valid;
	}
}
