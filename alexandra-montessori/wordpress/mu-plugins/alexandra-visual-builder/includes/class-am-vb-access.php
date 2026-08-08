<?php
/**
 * Per-page Visual Builder allocation for Editors.
 *
 * Administrators are never scoped: they open and save every page. An Editor
 * (`am_content_manager`) sees and saves ONLY the pages an Administrator has
 * ticked on the user screen. The allocation is deliberately fail-closed - an
 * Editor with no saved allocation gets no builder at all, so access is always
 * something an Administrator granted on purpose rather than something inherited.
 *
 * The allocatable list is generated from the same registry + organizer the
 * builder's own left rail renders from, so the checkboxes always match, page
 * for page, what the client actually sees. Never hardcode a page list here.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_VB_Access {
	const USER_META = '_am_vb_allowed_pages';
	const NONCE     = 'am_vb_save_allowed_pages';

	/**
	 * @return void
	 */
	public static function boot() {
		add_action('user_new_form', array(__CLASS__, 'render_fields'));
		add_action('edit_user_profile', array(__CLASS__, 'render_fields'));
		add_action('show_user_profile', array(__CLASS__, 'render_fields'));
		add_action('user_register', array(__CLASS__, 'save_fields'));
		add_action('profile_update', array(__CLASS__, 'save_fields'));
		add_filter('rest_pre_dispatch', array(__CLASS__, 'guard_rest'), 8, 3);
		add_action('admin_init', array(__CLASS__, 'guard_admin_page'), 1);
	}

	/**
	 * Administrators - and anyone who can manage the whole site - are unscoped.
	 *
	 * @param WP_User|null $user User.
	 * @return bool
	 */
	public static function is_unrestricted($user = null) {
		$user = self::resolve_user($user);
		if (!$user || !$user->exists()) {
			return false;
		}
		return in_array('administrator', (array) $user->roles, true)
			|| user_can($user, 'manage_options');
	}

	/**
	 * Every page slug the builder knows about, allocatable or not.
	 *
	 * @return string[]
	 */
	public static function all_page_slugs() {
		if (!class_exists('AM_VB_Site_Design')) {
			return array();
		}
		return array_keys(AM_VB_Site_Design::registry());
	}

	/**
	 * The pages an Administrator may tick, in the builder's own rail order and
	 * under the builder's own labels, minus anything hidden in the organizer.
	 *
	 * @return array<string,string> slug => label
	 */
	public static function allocatable_pages() {
		if (!class_exists('AM_VB_Site_Design')) {
			return array();
		}
		$registry  = AM_VB_Site_Design::registry();
		$organizer = class_exists('AM_VB_Organizer') ? AM_VB_Organizer::get() : array();
		$hidden    = array_flip((array) ($organizer['hidden'] ?? array()));
		$labels    = (array) ($organizer['labels'] ?? array());
		$order     = (array) ($organizer['order'] ?? array());

		$ordered = array();
		foreach ($order as $slug) {
			if (isset($registry[$slug])) {
				$ordered[$slug] = true;
			}
		}
		foreach (array_keys($registry) as $slug) {
			$ordered[$slug] = true;
		}

		$pages = array();
		foreach (array_keys($ordered) as $slug) {
			if (isset($hidden[$slug])) {
				continue;
			}
			$pages[$slug] = isset($labels[$slug]) && '' !== $labels[$slug]
				? (string) $labels[$slug]
				: (string) ($registry[$slug]['title'] ?? $slug);
		}

		return $pages;
	}

	/**
	 * Resolve the exact pages a user may edit.
	 *
	 * @param WP_User|null $user User.
	 * @return string[]
	 */
	public static function allowed_pages($user = null) {
		$user = self::resolve_user($user);
		if (!$user || !$user->exists()) {
			return array();
		}
		if (self::is_unrestricted($user)) {
			return self::all_page_slugs();
		}
		if (!user_can($user, 'edit_am_visual_pages')) {
			return array();
		}

		$saved = get_user_meta($user->ID, self::USER_META, true);
		if (!is_array($saved)) {
			// Fail closed. An Editor reaches the builder only once an
			// Administrator has ticked pages for them on purpose.
			return array();
		}

		$allocatable = self::allocatable_pages();
		$allowed     = array();
		foreach ($saved as $slug) {
			$slug = sanitize_key((string) $slug);
			if ($slug && isset($allocatable[$slug]) && !in_array($slug, $allowed, true)) {
				$allowed[] = $slug;
			}
		}

		return $allowed;
	}

	/**
	 * @param string       $slug Page slug.
	 * @param WP_User|null $user User.
	 * @return bool
	 */
	public static function can_edit_page($slug, $user = null) {
		$slug = sanitize_key((string) $slug);
		if (!$slug) {
			return false;
		}
		return in_array($slug, self::allowed_pages($user), true);
	}

	/**
	 * @param WP_User|null $user User.
	 * @return bool
	 */
	public static function can_use_builder($user = null) {
		$user = self::resolve_user($user);
		if (!$user || !user_can($user, 'edit_am_visual_pages')) {
			return false;
		}
		return (bool) self::allowed_pages($user);
	}

	/**
	 * Human-readable allocation, for notices and the saved-session list.
	 *
	 * @param WP_User|null $user User.
	 * @return string[]
	 */
	public static function allowed_page_labels($user = null) {
		if (self::is_unrestricted($user)) {
			return array(__('All pages', 'alexandra-visual-builder'));
		}
		$allocatable = self::allocatable_pages();
		$labels      = array();
		foreach (self::allowed_pages($user) as $slug) {
			if (isset($allocatable[$slug])) {
				$labels[] = $allocatable[$slug];
			}
		}
		return $labels;
	}

	/**
	 * The display name of a user's role, for audit and history output.
	 *
	 * @param WP_User|int|null $user User.
	 * @return string
	 */
	public static function role_label($user = null) {
		$user = self::resolve_user($user);
		if (!$user || !$user->exists()) {
			return __('System', 'alexandra-visual-builder');
		}
		$roles = (array) $user->roles;
		if (!$roles) {
			return __('No role', 'alexandra-visual-builder');
		}
		$names = wp_roles()->get_names();
		$slug  = (string) reset($roles);

		return isset($names[$slug])
			? translate_user_role($names[$slug])
			: ucwords(str_replace(array('am_', '_'), array('', ' '), $slug));
	}

	// -----------------------------------------------------------------------
	// Administrator UI: the allocation checkboxes.
	// -----------------------------------------------------------------------

	/**
	 * @param WP_User|string $user User being edited, or '' on the Add User form.
	 * @return void
	 */
	public static function render_fields($user = '') {
		if (!current_user_can('promote_users')) {
			return;
		}
		$pages = self::allocatable_pages();
		if (!$pages) {
			return;
		}

		$selected = array();
		if ($user instanceof WP_User) {
			$saved    = get_user_meta($user->ID, self::USER_META, true);
			$selected = is_array($saved) ? array_map('sanitize_key', $saved) : array();
		} elseif (isset($_POST['am_vb_allowed_pages'])) {
			$selected = array_map('sanitize_key', (array) wp_unslash($_POST['am_vb_allowed_pages']));
		}

		$is_admin_user = $user instanceof WP_User && self::is_unrestricted($user);

		wp_nonce_field(self::NONCE, 'am_vb_allowed_pages_nonce');
		?>
		<div id="am-vb-pages-panel" data-am-vb-admin="<?php echo $is_admin_user ? '1' : '0'; ?>">
			<h2><?php esc_html_e('Visual Builder pages', 'alexandra-visual-builder'); ?></h2>
			<div class="am-vb-pages-head">
				<div>
					<strong><?php esc_html_e('Choose the pages this Editor can design', 'alexandra-visual-builder'); ?></strong>
					<p id="am-vb-pages-summary">
						<?php esc_html_e('An Editor can open and save only the pages ticked here. Everything else is hidden from their builder.', 'alexandra-visual-builder'); ?>
					</p>
				</div>
				<div class="am-vb-pages-key">
					<span><b><?php esc_html_e('Administrator', 'alexandra-visual-builder'); ?></b> <?php esc_html_e('always has every page', 'alexandra-visual-builder'); ?></span>
				</div>
			</div>
			<fieldset class="am-vb-pages-grid">
				<?php foreach ($pages as $slug => $label) : ?>
					<?php $is_selected = in_array($slug, $selected, true); ?>
					<label class="am-vb-page-choice<?php echo $is_selected ? ' is-selected' : ''; ?>">
						<input type="checkbox" name="am_vb_allowed_pages[]" value="<?php echo esc_attr($slug); ?>" <?php checked($is_selected); ?>>
						<span><?php echo esc_html($label); ?></span>
					</label>
				<?php endforeach; ?>
			</fieldset>
			<div class="am-vb-pages-actions">
				<span id="am-vb-pages-count" aria-live="polite"></span>
				<div>
					<button type="button" class="button" id="am-vb-pages-all"><?php esc_html_e('Select all', 'alexandra-visual-builder'); ?></button>
					<button type="button" class="button" id="am-vb-pages-none"><?php esc_html_e('Clear', 'alexandra-visual-builder'); ?></button>
				</div>
			</div>
			<p class="am-vb-pages-note">
				<?php esc_html_e('Leaving every page clear removes this account’s access to the Visual Builder without changing anything else.', 'alexandra-visual-builder'); ?>
			</p>
		</div>
		<style>
			#am-vb-pages-panel{box-sizing:border-box;max-width:980px;width:100%}
			#am-vb-pages-panel .am-vb-pages-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:14px}
			#am-vb-pages-panel .am-vb-pages-head strong{color:#1d2327;display:block;font-size:14px;line-height:1.4}
			#am-vb-pages-panel .am-vb-pages-head p{color:#646970;margin:3px 0 0}
			#am-vb-pages-panel .am-vb-pages-key span{background:#f0f0f1;border-radius:999px;color:#50575e;font-size:12px;padding:4px 9px;white-space:nowrap}
			#am-vb-pages-panel .am-vb-pages-grid{border:0;display:grid;gap:9px;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));margin:0;min-width:0;padding:0}
			#am-vb-pages-panel .am-vb-page-choice{align-items:center;background:#fff;border:1px solid #c3c4c7;border-radius:6px;box-sizing:border-box;color:#2c3338;cursor:pointer;display:flex;gap:10px;margin:0;min-height:44px;min-width:0;padding:10px 12px;transition:border-color .12s ease,box-shadow .12s ease,background .12s ease}
			#am-vb-pages-panel .am-vb-page-choice:hover{border-color:#72aee6;box-shadow:0 0 0 1px #72aee6}
			#am-vb-pages-panel .am-vb-page-choice.is-selected{background:#f0f6fc;border-color:#2271b1;box-shadow:inset 3px 0 0 #2271b1;color:#0a4b78}
			#am-vb-pages-panel .am-vb-page-choice input[type=checkbox]{flex:0 0 18px;height:18px!important;margin:0!important;max-width:18px!important;min-height:18px!important;min-width:18px!important;padding:0!important;width:18px!important}
			#am-vb-pages-panel .am-vb-page-choice span{font-weight:500;line-height:1.35;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
			#am-vb-pages-panel .am-vb-pages-actions{align-items:center;display:flex;gap:14px;justify-content:space-between;margin-top:12px}
			#am-vb-pages-panel #am-vb-pages-count{color:#50575e;font-size:12px;font-weight:600}
			#am-vb-pages-panel .am-vb-pages-note{color:#646970;font-size:12px;margin:9px 0 0}
			@media(max-width:782px){#am-vb-pages-panel .am-vb-pages-head{display:block}#am-vb-pages-panel .am-vb-pages-key{justify-content:flex-start;margin-top:10px}#am-vb-pages-panel .am-vb-pages-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
			@media(max-width:480px){#am-vb-pages-panel .am-vb-pages-grid{grid-template-columns:1fr}#am-vb-pages-panel .am-vb-pages-actions{align-items:flex-start;flex-direction:column}}
		</style>
		<script>
		(function () {
			document.addEventListener('DOMContentLoaded', function () {
				var panel = document.getElementById('am-vb-pages-panel');
				if (!panel) { return; }
				var role = document.querySelector('select[name="role"]');
				var boxes = Array.prototype.slice.call(panel.querySelectorAll('input[type="checkbox"]'));
				var count = document.getElementById('am-vb-pages-count');
				var summary = document.getElementById('am-vb-pages-summary');
				var row = null;

				// Sit directly underneath the existing Sections allocation so an
				// Administrator makes both decisions in one place.
				var sectionsRow = document.querySelector('tr.am-access-sections-row');
				if (sectionsRow) {
					row = document.createElement('tr');
					row.className = 'form-field am-vb-pages-row';
					var head = document.createElement('th');
					head.scope = 'row';
					head.innerHTML = '<label>Visual Builder</label>';
					var cell = document.createElement('td');
					row.appendChild(head);
					row.appendChild(cell);
					sectionsRow.parentNode.insertBefore(row, sectionsRow.nextSibling);
					var heading = panel.querySelector('h2');
					if (heading) { heading.remove(); }
					cell.appendChild(panel);
					panel.style.margin = '0';
					panel.style.padding = '0';
					panel.style.border = '0';
					panel.style.background = 'transparent';
				}

				function sync() {
					var selected = 0;
					boxes.forEach(function (box) {
						var choice = box.closest('.am-vb-page-choice');
						if (choice) { choice.classList.toggle('is-selected', box.checked); }
						if (box.checked) { selected += 1; }
					});
					if (count) {
						count.textContent = selected === 1 ? '1 page selected' : selected + ' pages selected';
					}
				}
				boxes.forEach(function (box) { box.addEventListener('change', sync); });

				function toggle() {
					// Administrators are unscoped, so the allocation is meaningless
					// for them - say so instead of showing ticks that do nothing.
					var isAdmin = role
						? role.value === 'administrator'
						: panel.getAttribute('data-am-vb-admin') === '1';
					if (summary) {
						summary.textContent = isAdmin
							? 'Administrators can design every page. This allocation applies to Editors.'
							: 'An Editor can open and save only the pages ticked here. Everything else is hidden from their builder.';
					}
					var grid = panel.querySelector('.am-vb-pages-grid');
					var actions = panel.querySelector('.am-vb-pages-actions');
					var note = panel.querySelector('.am-vb-pages-note');
					[grid, actions, note].forEach(function (el) {
						if (el) { el.style.display = isAdmin ? 'none' : ''; }
					});
					if (row) { row.style.display = ''; }
				}
				if (role) { role.addEventListener('change', toggle); }

				var all = document.getElementById('am-vb-pages-all');
				var none = document.getElementById('am-vb-pages-none');
				if (all) {
					all.addEventListener('click', function () {
						boxes.forEach(function (box) { box.checked = true; });
						sync();
					});
				}
				if (none) {
					none.addEventListener('click', function () {
						boxes.forEach(function (box) { box.checked = false; });
						sync();
					});
				}
				sync();
				toggle();
			});
		})();
		</script>
		<?php
	}

	/**
	 * @param int $user_id User ID.
	 * @return void
	 */
	public static function save_fields($user_id) {
		if (!current_user_can('promote_users')) {
			return;
		}
		if (!isset($_POST['am_vb_allowed_pages_nonce'])
			|| !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['am_vb_allowed_pages_nonce'])), self::NONCE)) {
			return;
		}
		$allocatable = self::allocatable_pages();
		$selected    = array();
		foreach ((array) ($_POST['am_vb_allowed_pages'] ?? array()) as $slug) {
			$slug = sanitize_key((string) wp_unslash($slug));
			if ($slug && isset($allocatable[$slug]) && !in_array($slug, $selected, true)) {
				$selected[] = $slug;
			}
		}
		update_user_meta($user_id, self::USER_META, $selected);
	}

	// -----------------------------------------------------------------------
	// Enforcement. The panel is a convenience; these are the real gates.
	// -----------------------------------------------------------------------

	/**
	 * Resolve the page slug a builder REST route is acting on.
	 *
	 * @param string $route Route.
	 * @return string '' when the route is not page-scoped.
	 */
	public static function route_slug($route) {
		$route = (string) $route;
		$base  = '/' . AM_VB_REST::NAMESPACE;
		if (0 !== strpos($route, $base . '/')) {
			return '';
		}
		$path = substr($route, strlen($base));

		if (preg_match('#^/(?:page|page-design|public/page)/([a-z0-9-]+)#', $path, $matches)) {
			return sanitize_key($matches[1]);
		}
		if (0 === strpos($path, '/home-design')) {
			return AM_VB_Document::HOME_SLUG;
		}

		return '';
	}

	/**
	 * Deny any builder request that touches a page outside the allocation.
	 *
	 * This runs at priority 8 - before the theme's own section guard at 9 -
	 * because the builder namespace is scoped by page here, not by section.
	 *
	 * @param mixed           $result  Pre-dispatch result.
	 * @param WP_REST_Server  $server  Server.
	 * @param WP_REST_Request $request Request.
	 * @return mixed
	 */
	public static function guard_rest($result, $server, $request) {
		if (is_wp_error($result)) {
			return $result;
		}
		$route = (string) $request->get_route();
		if (0 !== strpos($route, '/' . AM_VB_REST::NAMESPACE . '/')) {
			return $result;
		}
		// The public read route serves the live site and is intentionally open.
		if (0 === strpos($route, '/' . AM_VB_REST::NAMESPACE . '/public/')) {
			return $result;
		}
		if (self::is_unrestricted()) {
			return $result;
		}
		if (!self::can_use_builder()) {
			return self::denied(__('This account has not been given any Visual Builder pages.', 'alexandra-visual-builder'));
		}

		$slug = self::route_slug($route);
		if ($slug && !self::can_edit_page($slug)) {
			return self::denied(__('This account is not allocated to that page.', 'alexandra-visual-builder'));
		}

		// Saved sessions carry their page in meta rather than the route.
		if (preg_match('#/saved-sessions/(\d+)#', $route, $matches)) {
			$session_slug = sanitize_key(
				(string) get_post_meta((int) $matches[1], AM_VB_Saved_Sessions::META_SLUG, true)
			);
			if ($session_slug && !self::can_edit_page($session_slug)) {
				return self::denied(__('This account is not allocated to that page.', 'alexandra-visual-builder'));
			}
		}

		// Page arrangement is site-wide, so it stays an Administrator decision.
		if (0 === strpos($route, '/' . AM_VB_REST::NAMESPACE . '/organizer')
			&& in_array($request->get_method(), array('POST', 'PUT', 'PATCH', 'DELETE'), true)) {
			return self::denied(__('Only an Administrator can rearrange the page list.', 'alexandra-visual-builder'));
		}

		return $result;
	}

	/**
	 * Keep an unallocated account out of the builder screen itself.
	 *
	 * @return void
	 */
	public static function guard_admin_page() {
		if (!class_exists('AM_VB_Admin')) {
			return;
		}
		global $pagenow;
		if ('admin.php' !== $pagenow
			|| AM_VB_Admin::PAGE_SLUG !== sanitize_key($_GET['page'] ?? '')) {
			return;
		}
		if (self::is_unrestricted() || self::can_use_builder()) {
			return;
		}
		wp_die(
			esc_html__('This account has not been given any Visual Builder pages. Ask an Administrator to allocate them.', 'alexandra-visual-builder'),
			esc_html__('No pages allocated', 'alexandra-visual-builder'),
			array('response' => 403, 'back_link' => true)
		);
	}

	/**
	 * @param string $message Message.
	 * @return WP_Error
	 */
	private static function denied($message) {
		return new WP_Error('am_vb_page_not_allocated', $message, array('status' => 403));
	}

	/**
	 * @param WP_User|int|null $user User.
	 * @return WP_User|null
	 */
	private static function resolve_user($user = null) {
		if ($user instanceof WP_User) {
			return $user;
		}
		if (is_numeric($user)) {
			$resolved = get_userdata((int) $user);
			return $resolved ?: null;
		}
		return wp_get_current_user();
	}
}
