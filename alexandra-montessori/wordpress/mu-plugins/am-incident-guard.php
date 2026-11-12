<?php
/**
 * Plugin Name: Alexandra Incident Access Guard
 * Description: Require usable administrator 2FA and reserve plugin activation changes for SSH maintenance.
 */
if (!defined('ABSPATH')) {
    exit;
}

add_filter('authenticate', function ($user) {
    if (!($user instanceof WP_User) || !in_array('administrator', (array) $user->roles, true)) {
        return $user;
    }
    if (function_exists('am_2fa_ensure_email')) {
        am_2fa_ensure_email($user->ID);
    }
    if (!class_exists('Two_Factor_Core') || !Two_Factor_Core::is_user_using_two_factor($user)) {
        return new WP_Error('am_two_factor_required', 'Administrator two-factor authentication is unavailable. Contact the site maintainer.');
    }
    return $user;
}, 25);

add_filter('user_has_cap', function ($caps) {
    if (!(defined('WP_CLI') && WP_CLI)) {
        $caps['activate_plugins'] = false;
    }
    return $caps;
}, 100);

// CMB2 2.13.4 offers an explicit opt-in for core-equivalent user-field privacy.
add_filter('cmb2_rest_enforce_user_read_permissions', '__return_true');

add_filter('upload_mimes', function ($mimes) {
    foreach (['svg', 'svgz', 'html', 'htm', 'js', 'php', 'phtml', 'phar'] as $extension) {
        unset($mimes[$extension]);
    }
    return $mimes;
});

add_filter('wp_handle_upload_prefilter', function ($file) {
    if (preg_match('/\.(?:php\d*|phtml|phar)(?:[^a-z0-9]|$)/i', (string)($file['name'] ?? ''))) {
        $file['error'] = 'Executable file names are not permitted.';
    }
    return $file;
});

/** Record security events outside the web root. Never record passwords or tokens. */
function am_security_audit_event($event, $subject_id = 0, $details = []) {
    $directory = dirname(rtrim(ABSPATH, '/\\')) . '/private-security-logs';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        error_log('Alexandra security audit directory unavailable.');
        return;
    }
    $record = [
        'time_utc' => gmdate('c'),
        'event' => $event,
        'actor_id' => get_current_user_id(),
        'subject_id' => (int)$subject_id,
        'ip' => filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: null,
        'source' => defined('WP_CLI') && WP_CLI ? 'cli' : 'web',
        'details' => $details,
    ];
    $path = $directory . '/security-' . gmdate('Y-m-d') . '.jsonl';
    if (false === file_put_contents($path, wp_json_encode($record) . "\n", FILE_APPEND | LOCK_EX)) {
        error_log('Alexandra security audit write failed.');
        return;
    }
    chmod($path, 0600);
}

add_action('wp_login', function ($login, $user) {
    am_security_audit_event('primary_credentials_accepted', $user->ID);
}, 5, 2);
add_action('two_factor_user_authenticated', function ($user, $provider) {
    am_security_audit_event('two_factor_authenticated', $user->ID, ['provider' => get_class($provider)]);
}, 10, 2);
add_action('wp_set_password', function ($unused_password, $user_id) {
    am_security_audit_event('password_changed', $user_id);
}, 10, 2);
add_action('user_register', function ($user_id) {
    am_security_audit_event('user_created', $user_id);
});
add_action('set_user_role', function ($user_id, $role) {
    am_security_audit_event('role_changed', $user_id, ['role' => sanitize_key($role)]);
}, 10, 2);
foreach (['activated_plugin', 'deactivated_plugin'] as $event) {
    add_action($event, function ($plugin) use ($event) {
        am_security_audit_event($event, 0, ['plugin' => sanitize_text_field($plugin)]);
    });
}
