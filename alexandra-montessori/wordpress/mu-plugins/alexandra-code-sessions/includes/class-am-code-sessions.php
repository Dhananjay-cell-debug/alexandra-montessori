<?php
/**
 * Code editor session storage, capture and restore.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_Code_Sessions {
	const POST_TYPE    = 'am_code_session';
	const PAGE_SLUG    = 'am_code_sessions';
	const META_FILE    = '_am_cs_file';
	const META_THEME   = '_am_cs_theme';
	const META_CONTENT = '_am_cs_content';
	const META_EVENT   = '_am_cs_event';
	const META_HASH    = '_am_cs_hash';
	const CAPABILITY   = 'edit_themes';

	/**
	 * @return void
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name'          => __('Code editor sessions', 'alexandra-code-sessions'),
					'singular_name' => __('Code session', 'alexandra-code-sessions'),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array('title', 'author'),
			)
		);
	}

	/**
	 * Fired on load-theme-editor.php, before core writes the submitted file
	 * to disk. Snapshots whatever is currently on disk (the "before" state)
	 * so it can be restored later.
	 *
	 * @return void
	 */
	public static function capture_before_save() {
		if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '')) {
			return;
		}
		if (!isset($_POST['newcontent'], $_POST['file'])) {
			return;
		}
		if (!current_user_can(self::CAPABILITY)) {
			return;
		}

		$theme = isset($_POST['theme']) ? sanitize_text_field(wp_unslash($_POST['theme'])) : get_stylesheet();
		$file  = sanitize_text_field(wp_unslash($_POST['file']));

		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'edit-theme_' . $theme . '_' . $file)) return;
        if ($theme !== get_stylesheet()) return;
        $path = self::resolve_path($theme, $file);
        if (!$path || !is_readable($path)) return;
        $snapshot = self::snapshot(
            $theme,
            $file,
            'edit',
			sprintf(
				/* translators: %s: file name being edited */
				__('Edited %s via Theme File Editor', 'alexandra-code-sessions'),
				$file
			)
        );
        if (!$snapshot) {
            if (wp_doing_ajax()) wp_send_json_error(array('message' => 'The recovery snapshot could not be saved. The file was not changed.'));
            wp_die('The recovery snapshot could not be saved. The file was not changed.');
        }
    }

    /**
     * Read the current on-disk content of a theme file and store it as a
	 * named session.
	 *
	 * @param string $theme Theme stylesheet slug.
	 * @param string $file  File path relative to the theme root.
	 * @param string $event edit | restore | restore-backup.
	 * @param string $label Human-readable title for this session.
	 * @return int|null Post ID of the new session, or null on failure.
	 */
	private static function snapshot($theme, $file, $event, $label) {
		$path = self::resolve_path($theme, $file);
		if (!$path || !is_readable($path)) {
			return null;
		}
		$content = file_get_contents($path);
		if (false === $content) {
			return null;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => wp_html_excerpt(sanitize_text_field($label), 200, ''),
				'post_author' => get_current_user_id(),
			),
			true
		);
		if (is_wp_error($post_id)) {
			return null;
		}

		update_post_meta($post_id, self::META_FILE, $file);
		update_post_meta($post_id, self::META_THEME, $theme);
		update_post_meta($post_id, self::META_CONTENT, wp_slash($content));
		update_post_meta($post_id, self::META_EVENT, $event);
		update_post_meta($post_id, self::META_HASH, hash('sha256', $content));

		return $post_id;
	}

	/**
	 * Resolve a theme-relative file path to a real on-disk path, refusing
	 * anything that would escape the theme directory. Mirrors the checks
	 * WordPress core itself applies in wp-admin/theme-editor.php.
	 *
	 * @param string $theme Theme stylesheet slug.
	 * @param string $file  File path relative to the theme root.
	 * @return string|null
	 */
	private static function resolve_path($theme, $file) {
		$theme = sanitize_text_field((string) $theme);
		$file  = str_replace('\\', '/', (string) $file);

		if (0 !== validate_file($file)) {
			return null;
		}

		$theme_root = get_theme_root($theme);
		if (!$theme_root) {
			return null;
		}

		$base      = trailingslashit($theme_root . '/' . $theme);
		$path      = $base . ltrim($file, '/');
		$real_base = realpath($base);
		$real_path = realpath($path);

		if (!$real_base || !$real_path || 0 !== strpos($real_path, rtrim($real_base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
			return null;
		}

		return $real_path;
	}

	/**
	 * @return void
	 */
	public static function register_menu() {
		if (!current_user_can(self::CAPABILITY)) {
			return;
		}
		add_submenu_page(
			'themes.php',
			__('Code Sessions', 'alexandra-code-sessions'),
			__('Code Sessions', 'alexandra-code-sessions'),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array(__CLASS__, 'render_page')
		);
	}

	/**
	 * @return void
	 */
	public static function render_page() {
		if (!current_user_can(self::CAPABILITY)) {
			wp_die(esc_html__('You do not have permission to access this page.', 'alexandra-code-sessions'));
		}

		$view = isset($_GET['view']) ? absint($_GET['view']) : 0;
		if ($view) {
			self::render_detail($view);
			return;
		}

		$notice = self::consume_notice();

		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => array('date' => 'DESC', 'ID' => 'DESC'),
				'no_found_rows'  => true,
			)
		);
		$editor_url = admin_url('theme-editor.php');
		?>
		<div class="wrap">
			<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
				<div>
					<p style="text-transform:uppercase;letter-spacing:.06em;font-size:11px;font-weight:600;color:#787c82;margin:0 0 4px;"><?php esc_html_e('Version history', 'alexandra-code-sessions'); ?></p>
					<h1 style="margin:0 0 6px;"><?php esc_html_e('Code Sessions', 'alexandra-code-sessions'); ?></h1>
					<p style="margin:0;color:#50575e;"><?php esc_html_e('Every save made through Appearance → Theme File Editor is snapshotted here automatically, so you can open an older version or restore it.', 'alexandra-code-sessions'); ?></p>
				</div>
				<div style="display:flex;gap:8px;flex-shrink:0;">
					<a class="button" href="<?php echo esc_url(admin_url('themes.php?page=' . self::PAGE_SLUG)); ?>"><?php esc_html_e('Refresh', 'alexandra-code-sessions'); ?></a>
					<a class="button button-primary" href="<?php echo esc_url($editor_url); ?>">&larr; <?php esc_html_e('Back to editor', 'alexandra-code-sessions'); ?></a>
				</div>
			</div>

			<?php if ($notice) : ?>
				<div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible"><p><?php echo esc_html($notice['message']); ?></p></div>
			<?php endif; ?>

			<div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:16px 0;">
				<input type="search" id="am-cs-search" placeholder="<?php esc_attr_e('Search file, event or changed by…', 'alexandra-code-sessions'); ?>" style="flex:1 1 320px;max-width:480px;padding:6px 10px;">
				<label for="am-cs-date" style="font-size:12px;color:#50575e;">
					<?php esc_html_e('Nearest date', 'alexandra-code-sessions'); ?>
					<input type="date" id="am-cs-date" style="margin-left:6px;">
				</label>
				<span id="am-cs-count" style="color:#787c82;font-size:12px;"></span>
			</div>

			<table class="wp-list-table widefat fixed striped" id="am-cs-table">
				<thead>
					<tr>
						<th style="width:180px;"><?php esc_html_e('Time', 'alexandra-code-sessions'); ?></th>
						<th><?php esc_html_e('File', 'alexandra-code-sessions'); ?></th>
						<th style="width:120px;"><?php esc_html_e('Event', 'alexandra-code-sessions'); ?></th>
						<th style="width:220px;"><?php esc_html_e('Changed by', 'alexandra-code-sessions'); ?></th>
						<th style="width:220px;"><?php esc_html_e('Actions', 'alexandra-code-sessions'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if (!$posts) : ?>
						<tr class="am-cs-row"><td colspan="5"><?php esc_html_e('No saves recorded yet. Edit and save a file in Theme File Editor to create the first session.', 'alexandra-code-sessions'); ?></td></tr>
					<?php else : foreach ($posts as $post) :
						$file     = get_post_meta($post->ID, self::META_FILE, true);
						$event    = get_post_meta($post->ID, self::META_EVENT, true);
						$author   = get_userdata($post->post_author);
						$author_name = $author ? $author->display_name : __('Unknown', 'alexandra-code-sessions');
						$open_url = add_query_arg(array('page' => self::PAGE_SLUG, 'view' => $post->ID), admin_url('themes.php'));
						$search_blob = strtolower($file . ' ' . $event . ' ' . $author_name . ' ' . $post->post_title);
						?>
						<tr class="am-cs-row" data-search="<?php echo esc_attr($search_blob); ?>" data-date="<?php echo esc_attr(mysql2date('Y-m-d', $post->post_date, false)); ?>">
							<td><?php echo esc_html(mysql2date('j M Y, g:i a', $post->post_date, false)); ?></td>
							<td><code><?php echo esc_html($file); ?></code></td>
							<td><?php echo esc_html(ucfirst(str_replace('-', ' ', (string) $event))); ?></td>
							<td>
								<?php echo esc_html($author_name); ?>
								<br><small style="color:#666;"><?php echo esc_html($post->post_title); ?></small>
							</td>
							<td>
								<a class="button button-small" href="<?php echo esc_url($open_url); ?>"><?php esc_html_e('Open', 'alexandra-code-sessions'); ?></a>
								<?php echo self::restore_button($post->ID); ?>
								<?php echo self::delete_button($post->ID); ?>
							</td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
			<p id="am-cs-empty" style="display:none;color:#787c82;"><?php esc_html_e('No sessions match that search.', 'alexandra-code-sessions'); ?></p>
		</div>
		<script>
		(function () {
			var search = document.getElementById('am-cs-search');
			var date   = document.getElementById('am-cs-date');
			var count  = document.getElementById('am-cs-count');
			var empty  = document.getElementById('am-cs-empty');
			var rows   = Array.prototype.slice.call(document.querySelectorAll('#am-cs-table .am-cs-row[data-search]'));

			function apply() {
				var q = (search.value || '').toLowerCase().trim();
				var d = date.value || '';
				var visible = 0;
				rows.forEach(function (row) {
					var matchesQ = !q || row.getAttribute('data-search').indexOf(q) !== -1;
					var matchesD = !d || row.getAttribute('data-date') === d;
					var show = matchesQ && matchesD;
					row.style.display = show ? '' : 'none';
					if (show) { visible++; }
				});
				count.textContent = visible + ' / ' + rows.length;
				empty.style.display = (visible === 0 && rows.length > 0) ? '' : 'none';
			}
			if (rows.length) {
				search.addEventListener('input', apply);
				date.addEventListener('input', apply);
				apply();
			}
		})();
		</script>
		<?php
	}

	/**
	 * @param int $id Session post ID.
	 * @return void
	 */
	private static function render_detail($id) {
		$post = self::get_session($id);
		if (!$post) {
			wp_die(esc_html__('Session not found.', 'alexandra-code-sessions'));
		}
		$file    = get_post_meta($post->ID, self::META_FILE, true);
		$content = get_post_meta($post->ID, self::META_CONTENT, true);
		$back    = admin_url('themes.php?page=' . self::PAGE_SLUG);
		?>
		<div class="wrap">
			<h1>
				<?php echo esc_html($file); ?>
				<small style="font-weight:400;color:#666;font-size:14px;"> — <?php echo esc_html(mysql2date('j M Y, g:i a', $post->post_date, false)); ?></small>
			</h1>
			<p><a href="<?php echo esc_url($back); ?>">&larr; <?php esc_html_e('Back to Code Sessions', 'alexandra-code-sessions'); ?></a></p>
			<p><em><?php echo esc_html($post->post_title); ?></em></p>
			<textarea readonly style="width:100%;height:70vh;font-family:Consolas,Menlo,monospace;font-size:13px;white-space:pre;tab-size:2;"><?php echo esc_textarea((string) $content); ?></textarea>
			<p><?php echo self::restore_button($post->ID); ?></p>
		</div>
		<?php
	}

	/**
	 * @param int $id Session post ID.
	 * @return string
	 */
	private static function restore_button($id) {
		ob_start();
		?>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin-right:4px;" onsubmit="return confirm('<?php echo esc_js(__('Restore this version? The current file will be backed up automatically first.', 'alexandra-code-sessions')); ?>');">
			<input type="hidden" name="action" value="am_cs_restore">
			<input type="hidden" name="session_id" value="<?php echo esc_attr($id); ?>">
			<?php wp_nonce_field('am_cs_restore_' . $id); ?>
			<button type="submit" class="button button-small"><?php esc_html_e('Restore', 'alexandra-code-sessions'); ?></button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @param int $id Session post ID.
	 * @return string
	 */
	private static function delete_button($id) {
		ob_start();
		?>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;" onsubmit="return confirm('<?php echo esc_js(__('Remove this saved session? (It moves to Trash, not permanently deleted.)', 'alexandra-code-sessions')); ?>');">
			<input type="hidden" name="action" value="am_cs_delete">
			<input type="hidden" name="session_id" value="<?php echo esc_attr($id); ?>">
			<?php wp_nonce_field('am_cs_delete_' . $id); ?>
			<button type="submit" class="button button-small button-link-delete"><?php esc_html_e('Delete', 'alexandra-code-sessions'); ?></button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @return void
	 */
	public static function handle_restore() {
		if (!current_user_can(self::CAPABILITY)) {
			wp_die(esc_html__('You do not have permission to do this.', 'alexandra-code-sessions'));
		}
		$id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
		check_admin_referer('am_cs_restore_' . $id);

		$post = self::get_session($id);
		if (!$post) {
			wp_die(esc_html__('Session not found.', 'alexandra-code-sessions'));
		}

		$file    = get_post_meta($post->ID, self::META_FILE, true);
		$theme   = get_post_meta($post->ID, self::META_THEME, true);
		$theme   = $theme ? $theme : get_stylesheet();
		$content = get_post_meta($post->ID, self::META_CONTENT, true);

		$path = self::resolve_path($theme, $file);
		if (!$path) {
			self::redirect_with_notice('error', __('That file could not be located safely.', 'alexandra-code-sessions'));
		}

		// Safety backup of what is currently live, before overwriting it —
		// same principle as the Visual Builder's restore flow.
		$backup = self::snapshot(
			$theme,
			$file,
			'restore-backup',
			sprintf(
				/* translators: %s: file name */
				__('Automatic backup of %s before restoring an earlier version', 'alexandra-code-sessions'),
				$file
			)
		);

        if (!$backup) self::redirect_with_notice('error', 'The current file could not be backed up. Nothing was restored.');
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $written = wp_edit_theme_plugin_file(array(
            'theme' => $theme, 'file' => $file, 'newcontent' => (string) $content,
            'nonce' => wp_create_nonce('edit-theme_' . $theme . '_' . $file),
        ));
        if (is_wp_error($written)) self::redirect_with_notice('error', 'Restore failed: ' . $written->get_error_message());
        if (function_exists('opcache_invalidate')) opcache_invalidate($path, true);

		self::snapshot(
			$theme,
			$file,
			'restored',
			sprintf(
				/* translators: 1: file name, 2: original save date */
				__('Restored %1$s to the version from %2$s', 'alexandra-code-sessions'),
				$file,
				mysql2date('j M Y, g:i a', $post->post_date, false)
			)
		);

		self::redirect_with_notice(
			'success',
			sprintf(
				/* translators: %s: file name */
				__('%s restored.', 'alexandra-code-sessions'),
				$file
			)
		);
	}

	/**
	 * @return void
	 */
	public static function handle_delete() {
		if (!current_user_can(self::CAPABILITY)) {
			wp_die(esc_html__('You do not have permission to do this.', 'alexandra-code-sessions'));
		}
		$id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
		check_admin_referer('am_cs_delete_' . $id);

		$post = self::get_session($id);
		if ($post) {
			wp_trash_post($post->ID);
		}

		self::redirect_with_notice('success', __('Session removed.', 'alexandra-code-sessions'));
	}

	/**
	 * @param string $type    success | error.
	 * @param string $message Notice text.
	 * @return void
	 */
	private static function redirect_with_notice($type, $message) {
		set_transient(
			'am_cs_notice_' . get_current_user_id(),
			array('type' => $type, 'message' => $message),
			60
		);
		wp_safe_redirect(admin_url('themes.php?page=' . self::PAGE_SLUG));
		exit;
	}

	/**
	 * @return array{type:string,message:string}|null
	 */
	private static function consume_notice() {
		$key    = 'am_cs_notice_' . get_current_user_id();
		$notice = get_transient($key);
		if ($notice) {
			delete_transient($key);
		}
		return $notice ? $notice : null;
	}

	/**
	 * @param int $id Session post ID.
	 * @return WP_Post|null
	 */
	private static function get_session($id) {
		$post = get_post($id);
		if (!$post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status) {
			return null;
		}
		return $post;
	}
}
