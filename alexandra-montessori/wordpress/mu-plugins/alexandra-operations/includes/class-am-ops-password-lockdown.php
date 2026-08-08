<?php
/**
 * Centralises password control with the administrator role. Staff accounts
 * cannot set or reset their own password through any entry point; only an
 * administrator can issue a new one (Users -> Edit -> Set New Password).
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_Ops_Password_Lockdown {
	/**
	 * @return void
	 */
	public static function register() {
		add_action('personal_options_update', array(__CLASS__, 'strip_self_service_password'), 0);
		add_filter('show_password_fields', array(__CLASS__, 'hide_self_service_fields'), 10, 2);
		add_filter('allow_password_reset', array(__CLASS__, 'block_self_service_reset'), 10, 2);
		add_action('validate_password_reset', array(__CLASS__, 'block_reset_completion'), 10, 2);
		add_filter('rest_pre_insert_user', array(__CLASS__, 'block_rest_password_change'), 10, 2);
	}

	/**
	 * Belt-and-suspenders: even if a non-admin somehow posts pass1/pass2 to
	 * their own profile screen, drop the fields before core applies them.
	 *
	 * @param int $user_id Profile owner (always the current user here).
	 * @return void
	 */
	public static function strip_self_service_password($user_id) {
		if (!user_can($user_id, 'administrator')) {
			unset($_POST['pass1'], $_POST['pass2']);
		}
	}

	/**
	 * Hides the "New Password" section on a user's own profile screen.
	 * Administrators editing another user still see and use it normally.
	 *
	 * @param bool    $show Whether to show the fields.
	 * @param WP_User $profileuser Profile being viewed.
	 * @return bool
	 */
	public static function hide_self_service_fields($show, $profileuser) {
		if (get_current_user_id() === $profileuser->ID && !current_user_can('administrator')) {
			return false;
		}

		return $show;
	}

	/**
	 * Blocks the "Lost your password?" self-service request for everyone
	 * except administrators (who may still recover their own account).
	 *
	 * @param bool $allow Whether to allow the reset request.
	 * @param int  $user_id Target user ID.
	 * @return bool|WP_Error
	 */
	public static function block_self_service_reset($allow, $user_id) {
		if (user_can($user_id, 'administrator')) {
			return $allow;
		}

		return new WP_Error(
			'am_ops_password_locked',
			__('Password resets are handled by the administrator. Please contact the office for a new password.', 'alexandra-operations')
		);
	}

	/**
	 * Blocks completion of a password reset (even via a pre-existing valid
	 * link, e.g. a new-account email) for non-administrator accounts.
	 *
	 * @param WP_Error $errors Validation errors, added to by reference.
	 * @param WP_User  $user Account the reset applies to.
	 * @return void
	 */
	public static function block_reset_completion($errors, $user) {
		if ($user instanceof WP_User && !user_can($user, 'administrator')) {
			$errors->add(
				'am_ops_password_locked',
				__('Password resets are handled by the administrator. Please contact the office for a new password.', 'alexandra-operations')
			);
		}
	}

	/**
	 * Blocks password changes submitted through the REST API for anyone
	 * other than an administrator (covers /wp/v2/users/<id> and /users/me).
	 *
	 * @param stdClass        $prepared Prepared user object.
	 * @param WP_REST_Request $request Incoming request.
	 * @return stdClass|WP_Error
	 */
	public static function block_rest_password_change($prepared, $request) {
		if (null !== $request->get_param('password') && !current_user_can('administrator')) {
			return new WP_Error(
				'am_ops_password_locked',
				__('Password changes must be made by an administrator.', 'alexandra-operations'),
				array('status' => 403)
			);
		}

		return $prepared;
	}
}
