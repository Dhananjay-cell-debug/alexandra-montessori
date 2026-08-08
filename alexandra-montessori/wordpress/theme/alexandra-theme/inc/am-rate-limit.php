<?php
/**
 * Alexandra Montessori — form abuse guard (per-IP rate limiting).
 *
 * Layered on TOP of the existing honeypot + timing checks. Those stop naive
 * bots for free; this stops a determined flood (the "what if the site gets
 * bombarded" case) by capping how many submissions one IP can make.
 *
 * Two windows, counted across ALL four forms together (per IP) so a flood
 * can't simply fan out across contact / availability / booking / careers:
 *   - burst:  a few submissions per minute      (stops rapid-fire)
 *   - hourly: a couple dozen per hour            (stops sustained abuse)
 *
 * Uses WordPress transients (DB-backed here; object cache in production), so
 * no external dependency. Tunable via optional wp-config constants:
 *   define('AM_RL_BURST', 4); define('AM_RL_BURST_WINDOW', 60);
 *   define('AM_RL_HOURLY', 20);
 */

if (!defined('ABSPATH')) exit;

/**
 * Best-effort client IP. Prefers Cloudflare's connecting-IP header (trustworthy
 * only when the site is actually proxied through Cloudflare), then the first
 * X-Forwarded-For hop, then REMOTE_ADDR. X-Forwarded-For is spoofable, so this
 * is defence-in-depth — never an authentication boundary.
 */
function am_client_ip() {
  if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
  } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($parts[0]);
  } else {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  }
  $valid = filter_var($ip, FILTER_VALIDATE_IP);
  return $valid ?: '0.0.0.0';
}

/**
 * Register one submission attempt against a named bucket for the current IP.
 * Returns true while under the limit, false once the cap is reached.
 * The window slides from the most recent allowed hit — slightly stricter than a
 * fixed window, which is the safe direction for an abuse guard.
 */
function am_rl_hit($bucket, $max, $window) {
  $key   = 'am_rl_' . $bucket . '_' . md5(am_client_ip());
  $count = get_transient($key);
  if ($count === false) {
    set_transient($key, 1, $window);
    return true;
  }
  if ((int) $count >= $max) {
    return false;
  }
  set_transient($key, (int) $count + 1, $window);
  return true;
}

/**
 * The guard called by every form endpoint. Returns true when the request may
 * proceed, or a WP_Error(429) the endpoint should return as-is.
 */
function am_rate_limit_guard() {
  // On the local dev site every request looks like 127.0.0.1, so a testing
  // session would share one bucket and trip false "blocked" errors. The guard
  // matters only in production (distinct visitor IPs), so skip it locally.
  if (function_exists('wp_get_environment_type') && wp_get_environment_type() === 'local') {
    return true;
  }

  $burst        = defined('AM_RL_BURST')        ? (int) AM_RL_BURST        : 6;
  $burst_window = defined('AM_RL_BURST_WINDOW') ? (int) AM_RL_BURST_WINDOW : 60;
  $hourly       = defined('AM_RL_HOURLY')       ? (int) AM_RL_HOURLY       : 30;

  if (!am_rl_hit('burst', $burst, $burst_window)) {
    return new WP_Error(
      'rate_limited',
      'You are submitting a little too quickly. Please wait a moment and try again.',
      ['status' => 429]
    );
  }
  if (!am_rl_hit('hour', $hourly, HOUR_IN_SECONDS)) {
    return new WP_Error(
      'rate_limited',
      'We have received several submissions from your connection. Please try again later, or call the nursery directly.',
      ['status' => 429]
    );
  }
  return true;
}
