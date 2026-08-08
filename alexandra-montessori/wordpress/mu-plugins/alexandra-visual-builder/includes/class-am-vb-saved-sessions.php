<?php
/**
 * Named, immutable save points for the real Visual Builder routes.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_VB_Saved_Sessions {
	const POST_TYPE        = 'am_vb_saved_session';
	const META_SLUG        = '_am_vb_session_slug';
	const META_KIND        = '_am_vb_session_kind';
	const META_PAGE_TITLE  = '_am_vb_session_page_title';
	const META_ROUTE       = '_am_vb_session_route';
	const META_SUMMARY     = '_am_vb_session_summary';
	const META_SNAPSHOT    = '_am_vb_session_snapshot';
	const META_HASH        = '_am_vb_session_hash';
	// Stored at capture time rather than resolved on read: if an Administrator
	// later changes someone's role, the history must still say what they were
	// when they made the change.
	const META_AUTHOR_ROLE = '_am_vb_session_author_role';

	/** @var WP_Post|null|false */
	private static $preview_post = false;

	/**
	 * @return void
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name'          => __('Visual Builder saved sessions', 'alexandra-visual-builder'),
					'singular_name' => __('Saved session', 'alexandra-visual-builder'),
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
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			AM_VB_REST::NAMESPACE,
			'/saved-sessions',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array(__CLASS__, 'rest_list'),
				'permission_callback' => array(__CLASS__, 'can_edit'),
			)
		);
		register_rest_route(
			AM_VB_REST::NAMESPACE,
			'/saved-sessions/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array(__CLASS__, 'rest_get'),
					'permission_callback' => array(__CLASS__, 'can_edit'),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array(__CLASS__, 'rest_delete'),
					'permission_callback' => array(__CLASS__, 'can_edit'),
				),
			)
		);
		register_rest_route(
			AM_VB_REST::NAMESPACE,
			'/saved-sessions/(?P<id>\d+)/restore',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array(__CLASS__, 'rest_restore'),
				'permission_callback' => array(__CLASS__, 'can_edit'),
			)
		);
	}

	/**
	 * @return bool
	 */
	public static function can_edit() {
		return current_user_can('edit_am_visual_pages');
	}

	/**
	 * Capture one named save after the real route has been persisted.
	 *
	 * @param string              $slug             Builder page slug.
	 * @param string              $title            User-entered session title.
	 * @param string              $kind             home or exact.
	 * @param array<string,mixed> $snapshot         Sanitized saved design.
	 * @param string              $summary_override Optional explicit summary.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function capture($slug, $title, $kind, $snapshot, $summary_override = '') {
		$slug  = sanitize_key($slug);
		$kind  = in_array($kind, array('home', 'exact'), true) ? $kind : '';
		$title = trim(sanitize_text_field((string) $title));
		$title = wp_html_excerpt($title, 120, '');

		if (!$slug || !$kind || !$title || !is_array($snapshot)) {
			return new WP_Error(
				'am_vb_invalid_saved_session',
				'A page, title and saved design are required.',
				array('status' => 400)
			);
		}

		$page = AM_VB_Site_Design::page_for_slug($slug);
		if (!$page) {
			return new WP_Error('am_vb_unknown_saved_session_page', 'That visual page is unavailable.', array('status' => 404));
		}

		$previous = self::latest_for_page($slug);
		$before   = $previous ? get_post_meta($previous->ID, self::META_SNAPSHOT, true) : array();
		$summary  = trim(sanitize_text_field((string) $summary_override));
		if (!$summary) {
			$summary = self::summarize_snapshots(is_array($before) ? $before : array(), $snapshot, $kind);
		}
		$summary = wp_html_excerpt($summary, 280, '…');

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_author' => get_current_user_id(),
			),
			true
		);
		if (is_wp_error($post_id)) {
			return $post_id;
		}

		update_post_meta($post_id, self::META_SLUG, $slug);
		update_post_meta($post_id, self::META_KIND, $kind);
		update_post_meta($post_id, self::META_PAGE_TITLE, sanitize_text_field((string) ($page['title'] ?? $slug)));
		update_post_meta($post_id, self::META_ROUTE, self::clean_route((string) ($page['route'] ?? '/')));
		update_post_meta($post_id, self::META_SUMMARY, $summary);
		update_post_meta($post_id, self::META_SNAPSHOT, $snapshot);
		update_post_meta($post_id, self::META_HASH, hash('sha256', (string) wp_json_encode($snapshot)));
		update_post_meta($post_id, self::META_AUTHOR_ROLE, AM_VB_Access::role_label(get_current_user_id()));

		$page_post = AM_VB_Document::find($slug);
		if ($page_post) {
			AM_VB_Audit::log(
				'saved_session_created',
				$page_post->ID,
				array(
					'session_id'    => (int) $post_id,
					'session_title' => $title,
					'page_slug'     => $slug,
				)
			);
		}

		return self::payload(get_post($post_id));
	}

	/**
	 * Produce a concise deterministic description of a visual change.
	 *
	 * @param array<string,mixed> $before Previous snapshot.
	 * @param array<string,mixed> $after  Current snapshot.
	 * @param string              $kind   Session kind.
	 * @return string
	 */
	public static function summarize_snapshots($before, $after, $kind = 'exact') {
		if (!$before) {
			$sections = count((array) ($after['sections'] ?? array()));
			$elements = count((array) ($after['elements'] ?? array()));
			return sprintf(
				'First named save with %1$d section setting%2$s and %3$d editable element%4$s.',
				$sections,
				1 === $sections ? '' : 's',
				$elements,
				1 === $elements ? '' : 's'
			);
		}

		$parts = array();
		foreach (
			array(
				'sections'    => 'section',
				'elements'    => 'element',
				'collections' => 'collection',
			) as $key => $label
		) {
			$changed = self::changed_keys((array) ($before[$key] ?? array()), (array) ($after[$key] ?? array()));
			$count   = count($changed);
			if (!$count) {
				continue;
			}
			$sample = array_slice($changed, 0, 3);
			$parts[] = sprintf(
				'%1$d %2$s%3$s%4$s',
				$count,
				$label,
				1 === $count ? '' : 's',
				$sample ? ' (' . implode(', ', $sample) . (count($changed) > count($sample) ? ', …' : '') . ')' : ''
			);
		}

		$before_custom = self::list_by_id((array) ($before['customSections'] ?? array()));
		$after_custom  = self::list_by_id((array) ($after['customSections'] ?? array()));
		$custom_count  = count(self::changed_keys($before_custom, $after_custom));
		if ($custom_count) {
			$parts[] = $custom_count . ' custom section' . (1 === $custom_count ? '' : 's');
		}

		if (!$parts) {
			return 'Named checkpoint; no visual difference from the previous saved session.';
		}

		return 'Updated ' . self::join_human($parts) . '.';
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_list($request) {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 250,
				'orderby'        => array('date' => 'DESC', 'ID' => 'DESC'),
				'no_found_rows'  => true,
			)
		);

		// History is scoped like the editor itself: an Editor sees the versions
		// of the pages they are allocated, and nothing else.
		if (!AM_VB_Access::is_unrestricted()) {
			$allowed = array_flip(AM_VB_Access::allowed_pages());
			$posts   = array_filter(
				$posts,
				static function ($post) use ($allowed) {
					$slug = sanitize_key((string) get_post_meta($post->ID, self::META_SLUG, true));
					return isset($allowed[$slug]);
				}
			);
		}

		return rest_ensure_response(
			array(
				'sessions' => array_values(array_map(array(__CLASS__, 'payload'), $posts)),
				'total'    => count($posts),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_get($request) {
		$post = self::session_post(absint($request['id']));
		return is_wp_error($post)
			? $post
			: rest_ensure_response(array('session' => self::payload($post)));
	}

	/**
	 * Move a saved session to WordPress Trash. It is deliberately not hard-deleted.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_delete($request) {
		$post = self::session_post(absint($request['id']));
		if (is_wp_error($post)) {
			return $post;
		}
		$slug = (string) get_post_meta($post->ID, self::META_SLUG, true);
		$trashed = wp_trash_post($post->ID);
		if (!$trashed) {
			return new WP_Error('am_vb_saved_session_delete_failed', 'The saved session could not be removed.', array('status' => 500));
		}
		$page_post = AM_VB_Document::find($slug);
		if ($page_post) {
			AM_VB_Audit::log(
				'saved_session_trashed',
				$page_post->ID,
				array('session_id' => (int) $post->ID, 'session_title' => $post->post_title)
			);
		}

		return rest_ensure_response(array('deleted' => true, 'recoverable' => true, 'id' => (int) $post->ID));
	}

	/**
	 * Restore a named session only after preserving the current page as an automatic backup.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_restore($request) {
		$post = self::session_post(absint($request['id']));
		if (is_wp_error($post)) {
			return $post;
		}

		$slug       = (string) get_post_meta($post->ID, self::META_SLUG, true);
		$kind       = (string) get_post_meta($post->ID, self::META_KIND, true);
		$snapshot   = get_post_meta($post->ID, self::META_SNAPSHOT, true);
		$page_post  = AM_VB_Document::find($slug);
		$session_id = (string) $request->get_param('editorSessionId');
		if (!$page_post || !is_array($snapshot)) {
			return new WP_Error('am_vb_saved_session_unavailable', 'That saved session is incomplete.', array('status' => 409));
		}
		$owned = AM_VB_Locks::assert_owned($page_post->ID, $session_id);
		if (is_wp_error($owned)) {
			return $owned;
		}

		$current = 'home' === $kind ? AM_VB_Home_Design::get() : AM_VB_Site_Design::get($slug);
		$backup  = self::capture(
			$slug,
			'Automatic backup before “' . $post->post_title . '”',
			$kind,
			$current,
			'Automatic safety copy created before restoring an earlier saved session.'
		);
		if (is_wp_error($backup)) {
			return new WP_Error(
				'am_vb_restore_backup_failed',
				'Restore stopped because the current page could not be backed up safely.',
				array('status' => 500)
			);
		}

		$restored_design = 'home' === $kind
			? AM_VB_Home_Design::save($snapshot)
			: AM_VB_Site_Design::save($slug, $snapshot);
		if (is_wp_error($restored_design)) {
			return $restored_design;
		}

		$restored = self::capture(
			$slug,
			'Restored — ' . $post->post_title,
			$kind,
			$restored_design,
			'Restored the page from the saved session “' . $post->post_title . '”.'
		);
		if (is_wp_error($restored)) {
			$rollback = 'home' === $kind
				? AM_VB_Home_Design::save($current)
				: AM_VB_Site_Design::save($slug, $current);
			return new WP_Error(
				'am_vb_restore_history_failed',
				is_wp_error($rollback)
					? 'The version was restored, but its history record and automatic rollback both failed. Reload before editing.'
					: 'Restore stopped because the restored version could not be recorded in history. The previous page was put back.',
				array('status' => 500)
			);
		}
		AM_VB_Audit::log(
			'saved_session_restored',
			$page_post->ID,
			array(
				'session_id'     => (int) $post->ID,
				'session_title'  => $post->post_title,
				'backup_session' => (int) ($backup['id'] ?? 0),
			)
		);

		return rest_ensure_response(
			array(
				'restored'        => true,
				'slug'            => $slug,
				'design'          => $restored_design,
				'backupSession'   => $backup,
				'restoredSession' => $restored,
			)
		);
	}

	/**
	 * Require an authenticated editor for historical frontend previews.
	 *
	 * @return void
	 */
	public static function authorize_preview() {
		if (empty($_GET['am_vb_saved_session']) || is_admin()) {
			return;
		}
		if (!is_user_logged_in()) {
			auth_redirect();
		}
		if (!current_user_can('edit_am_visual_pages')) {
			wp_die(esc_html__('You cannot preview Visual Builder saved sessions.', 'alexandra-visual-builder'), '', array('response' => 403));
		}
		$post = self::session_post(absint(wp_unslash($_GET['am_vb_saved_session'])));
		if (is_wp_error($post)) {
			status_header(404);
			wp_die(esc_html__('Saved session not found.', 'alexandra-visual-builder'), '', array('response' => 404));
		}
		self::$preview_post = $post;
		nocache_headers();
		add_filter(
			'wp_robots',
			static function ($robots) {
				$robots['noindex']  = true;
				$robots['nofollow'] = true;
				return $robots;
			}
		);
	}

	/**
	 * Return a historical snapshot when the current request is its matching preview route.
	 *
	 * @param string $slug Page slug.
	 * @param string $kind home or exact.
	 * @return array<string,mixed>|null
	 */
	public static function frontend_snapshot($slug, $kind) {
		$post = self::preview_post();
		if (!$post
			|| sanitize_key((string) get_post_meta($post->ID, self::META_SLUG, true)) !== sanitize_key($slug)
			|| (string) get_post_meta($post->ID, self::META_KIND, true) !== $kind) {
			return null;
		}
		$snapshot = get_post_meta($post->ID, self::META_SNAPSHOT, true);
		return is_array($snapshot) ? $snapshot : null;
	}

	/**
	 * Render a small, unmistakable historical-preview bar on the real page.
	 *
	 * @return void
	 */
	public static function render_preview_banner() {
		$post = self::preview_post();
		if (!$post) {
			return;
		}
		$session      = self::payload($post);
		$builder_base = admin_url('admin.php?page=' . AM_VB_Admin::PAGE_SLUG);
		$sessions_url = add_query_arg(
			array('am_vb_page' => $session['slug'], 'am_vb_sessions' => 1),
			$builder_base
		);
		$restore_url = add_query_arg(
			array('am_vb_page' => $session['slug'], 'am_vb_restore_session' => $session['id']),
			$builder_base
		);
		$current_url = home_url($session['route']);
		?>
		<style>
			.am-vb-history-preview-bar{align-items:center;background:#fff;border:1px solid #d7dfd8;border-radius:14px;bottom:18px;box-shadow:0 12px 42px rgba(27,48,34,.22);display:flex;font-family:Inter,system-ui,sans-serif;gap:12px;left:50%;max-width:min(900px,calc(100vw - 28px));padding:10px 12px;position:fixed;transform:translateX(-50%);width:max-content;z-index:2147483000}
			.am-vb-history-preview-copy{min-width:190px;padding:0 8px}.am-vb-history-preview-copy span,.am-vb-history-preview-copy small{color:#68756d;display:block;font-size:11px}.am-vb-history-preview-copy strong{color:#24332a;display:block;font-size:14px;margin:2px 0}
			.am-vb-history-preview-bar a{background:#fff;border:1px solid #d7dfd8;border-radius:9px;color:#294a34;font-size:12px;font-weight:700;padding:9px 12px;text-decoration:none;white-space:nowrap}.am-vb-history-preview-bar a:hover{background:#f2f6f2}.am-vb-history-preview-bar a.am-vb-history-preview-primary{background:#345b40;border-color:#345b40;color:#fff}
			@media(max-width:700px){.am-vb-history-preview-bar{align-items:stretch;flex-wrap:wrap;width:calc(100vw - 28px)}.am-vb-history-preview-copy{flex:1 0 100%}.am-vb-history-preview-bar a{flex:1;text-align:center}}
		</style>
		<div class="am-vb-history-preview-bar" role="status" aria-label="<?php echo esc_attr__('Saved session preview', 'alexandra-visual-builder'); ?>">
			<div class="am-vb-history-preview-copy">
				<span><?php echo esc_html__('Saved session preview', 'alexandra-visual-builder'); ?></span>
				<strong><?php echo esc_html($session['title']); ?></strong>
				<small><?php echo esc_html($session['pageTitle'] . ' · ' . $session['createdLabel']); ?></small>
				<small><?php echo esc_html($session['author'] . ' · ' . $session['authorRole']); ?></small>
			</div>
			<a href="<?php echo esc_url($current_url); ?>"><?php echo esc_html__('Current site', 'alexandra-visual-builder'); ?></a>
			<a href="<?php echo esc_url($sessions_url); ?>"><?php echo esc_html__('All saved sessions', 'alexandra-visual-builder'); ?></a>
			<a class="am-vb-history-preview-primary" href="<?php echo esc_url($restore_url); ?>"><?php echo esc_html__('Restore this version', 'alexandra-visual-builder'); ?></a>
		</div>
		<?php
	}

	/**
	 * @param WP_Post $post Saved session.
	 * @return array<string,mixed>
	 */
	private static function payload($post) {
		$author = get_userdata($post->post_author);
		$route  = self::clean_route((string) get_post_meta($post->ID, self::META_ROUTE, true));
		return array(
			'id'           => (int) $post->ID,
			'title'        => $post->post_title,
			'summary'      => (string) get_post_meta($post->ID, self::META_SUMMARY, true),
			'slug'         => (string) get_post_meta($post->ID, self::META_SLUG, true),
			'kind'         => (string) get_post_meta($post->ID, self::META_KIND, true),
			'pageTitle'    => (string) get_post_meta($post->ID, self::META_PAGE_TITLE, true),
			'route'        => $route,
			'created'      => mysql2date('c', $post->post_date, false),
			'createdLabel' => mysql2date('j M Y, g:i a', $post->post_date, false),
			'author'       => $author ? $author->display_name : 'System',
			'authorRole'   => self::author_role($post),
			'previewUrl'   => add_query_arg('am_vb_saved_session', (int) $post->ID, home_url($route)),
			'hash'         => (string) get_post_meta($post->ID, self::META_HASH, true),
		);
	}

	/**
	 * The role the author held when this save was made.
	 *
	 * @param WP_Post $post Saved session.
	 * @return string
	 */
	private static function author_role($post) {
		$stored = trim((string) get_post_meta($post->ID, self::META_AUTHOR_ROLE, true));
		if ('' !== $stored) {
			return $stored;
		}
		// Saves made before roles were recorded fall back to the author's
		// current role, which is the best available answer.
		return $post->post_author
			? AM_VB_Access::role_label((int) $post->post_author)
			: __('System', 'alexandra-visual-builder');
	}

	/**
	 * @param int $id Session ID.
	 * @return WP_Post|WP_Error
	 */
	private static function session_post($id) {
		$post = get_post($id);
		if (!$post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status) {
			return new WP_Error('am_vb_saved_session_not_found', 'Saved session not found.', array('status' => 404));
		}
		return $post;
	}

	/**
	 * @return WP_Post|null
	 */
	private static function preview_post() {
		if (false !== self::$preview_post) {
			return self::$preview_post;
		}
		if (empty($_GET['am_vb_saved_session']) || !is_user_logged_in() || !current_user_can('edit_am_visual_pages')) {
			self::$preview_post = null;
			return null;
		}
		$post = self::session_post(absint(wp_unslash($_GET['am_vb_saved_session'])));
		self::$preview_post = is_wp_error($post) ? null : $post;
		return self::$preview_post;
	}

	/**
	 * @param string $slug Page slug.
	 * @return WP_Post|null
	 */
	private static function latest_for_page($slug) {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => array('date' => 'DESC', 'ID' => 'DESC'),
				'meta_key'       => self::META_SLUG,
				'meta_value'     => sanitize_key($slug),
				'no_found_rows'  => true,
			)
		);
		return $posts ? $posts[0] : null;
	}

	/**
	 * @param array<string,mixed> $before Previous map.
	 * @param array<string,mixed> $after  Current map.
	 * @return string[]
	 */
	private static function changed_keys($before, $after) {
		$keys    = array_unique(array_merge(array_keys($before), array_keys($after)));
		$changed = array();
		foreach ($keys as $key) {
			$left  = array_key_exists($key, $before) ? wp_json_encode($before[$key]) : null;
			$right = array_key_exists($key, $after) ? wp_json_encode($after[$key]) : null;
			if ($left !== $right) {
				$changed[] = sanitize_key((string) $key) ?: 'item';
			}
		}
		sort($changed, SORT_NATURAL | SORT_FLAG_CASE);
		return $changed;
	}

	/**
	 * @param array<int,array<string,mixed>> $items List.
	 * @return array<string,array<string,mixed>>
	 */
	private static function list_by_id($items) {
		$map = array();
		foreach ($items as $index => $item) {
			$key       = !empty($item['id']) ? sanitize_key((string) $item['id']) : 'item-' . (int) $index;
			$map[$key] = $item;
		}
		return $map;
	}

	/**
	 * @param string[] $parts Text parts.
	 * @return string
	 */
	private static function join_human($parts) {
		$count = count($parts);
		if ($count < 2) {
			return (string) ($parts[0] ?? '');
		}
		$last = array_pop($parts);
		return implode(', ', $parts) . ' and ' . $last;
	}

	/**
	 * @param string $route Route.
	 * @return string
	 */
	private static function clean_route($route) {
		$route = '/' . ltrim((string) $route, '/');
		return '/' === $route ? '/' : untrailingslashit($route);
	}
}
