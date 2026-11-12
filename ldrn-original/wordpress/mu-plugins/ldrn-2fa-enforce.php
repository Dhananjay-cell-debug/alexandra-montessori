<?php
/**
 * Plugin Name: Little Rainbows Administrator Two-Factor Authentication
 * Description: Mandatory administrator email OTP using WordPress Two Factor; existing authenticator choices are preserved.
 */
defined('ABSPATH') || exit;
function ldrn_2fa_enforced() {
    return defined('LDRN_FORCE_2FA') ? (bool)LDRN_FORCE_2FA : (bool)get_option('ldrn_force_2fa', false);
}
function ldrn_2fa_ensure_email($id) {
    if (!ldrn_2fa_enforced()) return;
    $user=get_userdata($id);
    if (!$user || !in_array('administrator',(array)$user->roles,true)) return;
    $enabled=get_user_meta($id,'_two_factor_enabled_providers',true);
    if (empty($enabled) || !is_array($enabled)) {
        update_user_meta($id,'_two_factor_enabled_providers',array('Two_Factor_Email'));
        update_user_meta($id,'_two_factor_provider','Two_Factor_Email');
    }
}
add_action('user_register','ldrn_2fa_ensure_email',20);
add_action('set_user_role','ldrn_2fa_ensure_email',20);
add_action('add_user_role','ldrn_2fa_ensure_email',20);
add_action('init',function(){if(is_user_logged_in())ldrn_2fa_ensure_email(get_current_user_id());});
add_filter('authenticate',function($user){
    if (!ldrn_2fa_enforced() || !($user instanceof WP_User) || !in_array('administrator',(array)$user->roles,true)) return $user;
    ldrn_2fa_ensure_email($user->ID);
    if (!class_exists('Two_Factor_Core') || !Two_Factor_Core::is_user_using_two_factor($user)) return new WP_Error('ldrn_two_factor_unavailable','Administrator two-factor authentication is unavailable. Contact the site maintainer.');
    return $user;
},40);
// Application passwords cannot bypass administrator second-factor login.
add_filter('wp_is_application_passwords_available_for_user',function($available,$user){
    return ldrn_2fa_enforced() && in_array('administrator',(array)$user->roles,true) ? false : $available;
},20,2);
