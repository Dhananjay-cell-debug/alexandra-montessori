<?php
/**
 * Formal account notices: sent when a new user is created, and again
 * whenever an administrator resets someone's password. Both paths always
 * deliver actual credentials by email (never a self-set link), consistent
 * with this site's administrator-only password policy, and both replace
 * WordPress's default "click here to choose your own password" flow, which
 * individuals here are not permitted to use. This module also trims the
 * Users list screen down to the columns/actions that policy still supports.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_Ops_User_Provisioning {
	/**
	 * @return void
	 */
	public static function register() {
		add_action('edit_user_created_user', array(__CLASS__, 'force_user_notification'), 10, 2);
		add_filter('wp_new_user_notification_email', array(__CLASS__, 'formal_account_notice'), 10, 3);
		add_action('admin_init', array(__CLASS__, 'handle_admin_password_reset'), 5);
		add_action('admin_notices', array(__CLASS__, 'reset_confirmation_notice'));
		add_filter('manage_users_columns', array(__CLASS__, 'remove_redundant_columns'), 999);
		add_filter('user_row_actions', array(__CLASS__, 'trim_row_actions'), 999, 2);
		add_action('admin_head', array(__CLASS__, 'hide_duplicate_table_footer'));
	}

	/**
	 * WordPress repeats the column headers and bulk-actions bar in a <tfoot>
	 * below every core list table (Users, Posts, every custom post type,
	 * Media, Plugins, etc). Requested removed everywhere; the top copy still
	 * has full Bulk actions/Apply/Change role controls.
	 *
	 * @return void
	 */
	public static function hide_duplicate_table_footer() {
		echo '<style>.wp-list-table tfoot{display:none}</style>';
	}

	/**
	 * The notification checkbox on Add User only ever controlled whether the
	 * admin copy also went out; the account notice to the individual is not
	 * optional here. If the checkbox was left unchecked (core passes
	 * $notify = 'admin'), send the user notice anyway.
	 *
	 * @param int    $user_id Newly created user.
	 * @param string $notify Notification type core decided on.
	 * @return void
	 */
	public static function force_user_notification($user_id, $notify) {
		if ('admin' === $notify) {
			wp_send_new_user_notifications($user_id, 'user');
		}
	}

	/**
	 * Replaces the default password-set-link email with a brief formal
	 * notice containing the username, password, role and login link. Only
	 * intervenes when the raw password is available from the current
	 * request (the Add User submission); any other path that generates this
	 * notification (e.g. open self-registration, if ever enabled) keeps
	 * core's default reset-link email.
	 *
	 * @param array   $email Used to build wp_mail(): to, subject, message, headers.
	 * @param WP_User $user Newly created user.
	 * @param string  $blogname Site title.
	 * @return array
	 */
	public static function formal_account_notice($email, $user, $blogname) {
		$password = isset($_POST['pass1']) ? trim(wp_unslash($_POST['pass1'])) : '';
		if ('' === $password) {
			return $email;
		}

		$email['subject'] = __('[%s] Your account has been created', 'alexandra-operations');
		$email['message'] = self::notice_body($user, $password, $blogname);

		return $email;
	}

	/**
	 * Replaces core's "Send password reset" action (which would otherwise
	 * email the individual a link to choose their own password, something
	 * this site's policy doesn't allow) with an admin-only regenerate-and-
	 * notify flow: a new password is generated immediately and emailed via
	 * the same formal notice used at account creation. Intercepts and fully
	 * replaces core's own resetpassword handling in wp-admin/users.php,
	 * which runs later in the same request.
	 *
	 * @return void
	 */
	public static function handle_admin_password_reset() {
		if ('users.php' !== ($GLOBALS['pagenow'] ?? '') || 'resetpassword' !== ($_REQUEST['action'] ?? '')) {
			return;
		}

		check_admin_referer('bulk-users');

		if (!current_user_can('edit_users') || empty($_REQUEST['users'])) {
			return;
		}

		$blogname    = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$current_id  = get_current_user_id();
		$reset_count = 0;

		foreach (array_map('intval', (array) $_REQUEST['users']) as $user_id) {
			if ($user_id === $current_id || !current_user_can('edit_user', $user_id)) {
				continue;
			}

			$user = get_userdata($user_id);
			if (!$user) {
				continue;
			}

			$password = wp_generate_password(20, true, true);
			wp_update_user(array('ID' => $user_id, 'user_pass' => $password));

			wp_mail(
				$user->user_email,
				wp_specialchars_decode(sprintf(__('[%s] Your password has been reset', 'alexandra-operations'), $blogname)),
				self::notice_body($user, $password, $blogname)
			);

			++$reset_count;
		}

		wp_redirect(add_query_arg(
			array(
				'reset_count' => $reset_count,
				'update'      => 'am_password_reset',
			),
			admin_url('users.php')
		));
		exit;
	}

	/**
	 * @return void
	 */
	public static function reset_confirmation_notice() {
		if ('users.php' !== ($GLOBALS['pagenow'] ?? '') || 'am_password_reset' !== ($_GET['update'] ?? '')) {
			return;
		}

		$count = isset($_GET['reset_count']) ? (int) $_GET['reset_count'] : 0;
		printf(
			'<div id="message" class="updated notice is-dismissible"><p>%s</p></div>',
			esc_html(sprintf(
				/* translators: %d: number of users. */
				_n('New password generated and emailed to %d user.', 'New password generated and emailed to %d users.', $count, 'alexandra-operations'),
				$count
			))
		);
	}

	/**
	 * The "already set" Password column duplicates what the administrator-
	 * only policy already makes unambiguous, and invites confusion. Hidden
	 * regardless of which plugin/theme code added it.
	 *
	 * @param array $columns Column headers.
	 * @return array
	 */
	public static function remove_redundant_columns($columns) {
		unset($columns['am_password']);
		return $columns;
	}

	/**
	 * Keeps only Edit / Delete / Send password reset on the Users list;
	 * "View" (author archive) and "Copy setup link" both encourage flows
	 * this site's password policy doesn't support.
	 *
	 * @param string[] $actions Row actions.
	 * @param WP_User  $user_object Row's user.
	 * @return string[]
	 */
	public static function trim_row_actions($actions, $user_object) {
		unset($actions['view'], $actions['am_copy_setup_link']);
		return $actions;
	}

	/**
	 * @param WP_User $user Account the notice is about.
	 * @param string  $password Plaintext password to disclose.
	 * @param string  $blogname Site title.
	 * @return string
	 */
	private static function notice_body($user, $password, $blogname) {
		$roles     = (array) $user->roles;
		$role_name = self::role_label($roles ? $roles[0] : '');
		$name      = trim((string) $user->display_name) ? $user->display_name : $user->user_login;

		return
			sprintf(__('Dear %s,', 'alexandra-operations'), $name) . "\r\n\r\n" .
			sprintf(__('This is a notice regarding your account on %s.', 'alexandra-operations'), $blogname) . "\r\n\r\n" .
			sprintf(__('Username: %s', 'alexandra-operations'), $user->user_login) . "\r\n" .
			sprintf(__('Password: %s', 'alexandra-operations'), $password) . "\r\n" .
			sprintf(__('Role: %s', 'alexandra-operations'), $role_name) . "\r\n" .
			sprintf(__('Login link: %s', 'alexandra-operations'), wp_login_url()) . "\r\n\r\n" .
			__('Please keep these details confidential. For any access issues, contact the site administrator.', 'alexandra-operations') . "\r\n\r\n" .
			sprintf(__('Regards,%1$s%2$s Administration', 'alexandra-operations'), "\r\n", $blogname);
	}

	/**
	 * @param string $role_slug Role slug.
	 * @return string Human-readable role name.
	 */
	private static function role_label($role_slug) {
		if ('' === $role_slug) {
			return '';
		}

		$role_names = wp_roles()->role_names;

		return isset($role_names[$role_slug])
			? translate_user_role($role_names[$role_slug])
			: ucwords(str_replace(array('_', '-'), ' ', $role_slug));
	}
}
