<?php
/**
 * Public-form abuse controls and privacy-preserving request fingerprints.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_Ops_Security {
	/**
	 * Validate common bot controls and apply atomic global rate limits.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @param string          $form Form bucket.
	 * @param int             $max_bytes Maximum request body size.
	 * @return true|WP_Error
	 */
	public static function guard($request, $form, $max_bytes) {
		$content_length = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
		if ($content_length > $max_bytes) {
			return new WP_Error(
				'am_ops_request_too_large',
				__('This form submission is too large.', 'alexandra-operations'),
				array('status' => 413)
			);
		}

		if ('' !== trim((string) $request->get_param('website'))) {
			return new WP_Error(
				'am_ops_spam',
				__('Invalid submission.', 'alexandra-operations'),
				array('status' => 400)
			);
		}

		$loaded_at = (int) $request->get_param('formLoadedAt');
		if ($loaded_at > 0) {
			$elapsed = (time() * 1000) - $loaded_at;
			if ($elapsed < 3000 || $elapsed > 7 * DAY_IN_SECONDS * 1000) {
				return new WP_Error(
					'am_ops_form_timing',
					__('Please reload the page and take a moment to complete the form.', 'alexandra-operations'),
					array('status' => 400)
				);
			}
		}

		$limits = array(
			array(
				'bucket' => 'all_burst',
				'max'    => defined('AM_OPS_RL_BURST') ? (int) AM_OPS_RL_BURST : 6,
				'window' => defined('AM_OPS_RL_BURST_WINDOW') ? (int) AM_OPS_RL_BURST_WINDOW : 60,
			),
			array(
				'bucket' => 'all_hour',
				'max'    => defined('AM_OPS_RL_HOURLY') ? (int) AM_OPS_RL_HOURLY : 30,
				'window' => HOUR_IN_SECONDS,
			),
			array(
				'bucket' => 'form_' . sanitize_key($form),
				'max'    => defined('AM_OPS_RL_FORM_HOURLY') ? (int) AM_OPS_RL_FORM_HOURLY : 20,
				'window' => HOUR_IN_SECONDS,
			),
		);

		foreach ($limits as $limit) {
			$allowed = self::hit(
				$limit['bucket'],
				max(1, $limit['max']),
				max(1, $limit['window'])
			);
			if (is_wp_error($allowed)) {
				return $allowed;
			}
			if (!$allowed) {
				return new WP_Error(
					'am_ops_rate_limited',
					__('You are submitting a little too quickly. Please wait and try again.', 'alexandra-operations'),
					array(
						'status'      => 429,
						'retry_after' => $limit['window'],
					)
				);
			}
		}

		return true;
	}

	/**
	 * Atomically increment a fixed-window counter.
	 *
	 * @param string $bucket Counter bucket.
	 * @param int    $maximum Maximum hits.
	 * @param int    $window_seconds Fixed window length.
	 * @return bool|WP_Error
	 */
	public static function hit($bucket, $maximum, $window_seconds) {
		global $wpdb;

		if (!AM_Ops_Schema::tables_exist()) {
			return new WP_Error(
				'am_ops_rate_limit_unavailable',
				__('The form protection service is unavailable.', 'alexandra-operations'),
				array('status' => 503)
			);
		}

		$now          = time();
		$window_start = (int) (floor($now / $window_seconds) * $window_seconds);
		$start_mysql  = gmdate('Y-m-d H:i:s', $window_start);
		$expires      = gmdate('Y-m-d H:i:s', $window_start + $window_seconds + 300);
		$updated      = gmdate('Y-m-d H:i:s', $now);
		$key_hash     = self::ip_hash();
		$table        = AM_Ops_Tables::name(AM_Ops_Tables::RATE_LIMITS);

		$sql = $wpdb->prepare(
			"INSERT INTO {$table}
				(key_hash,bucket,window_start,window_seconds,hits,expires_at,updated_at)
			VALUES (%s,%s,%s,%d,1,%s,%s)
			ON DUPLICATE KEY UPDATE
				hits = hits + 1,
				expires_at = VALUES(expires_at),
				updated_at = VALUES(updated_at)",
			$key_hash,
			sanitize_key($bucket),
			$start_mysql,
			$window_seconds,
			$expires,
			$updated
		);

		if (false === $wpdb->query($sql)) {
			return new WP_Error(
				'am_ops_rate_limit_failed',
				__('The form protection service is unavailable.', 'alexandra-operations'),
				array('status' => 503)
			);
		}

		$hits = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT hits FROM {$table}
				WHERE key_hash = %s AND bucket = %s AND window_start = %s
				LIMIT 1",
				$key_hash,
				sanitize_key($bucket),
				$start_mysql
			)
		);

		if (1 === random_int(1, 100)) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE expires_at < %s ORDER BY id ASC LIMIT 500",
					gmdate('Y-m-d H:i:s')
				)
			);
		}

		return $hits <= $maximum;
	}

	/**
	 * The direct peer IP is authoritative by default. Proxy headers are read
	 * only when AM_OPS_TRUSTED_PROXIES explicitly lists the direct proxy IP/CIDR.
	 *
	 * @return string
	 */
	public static function client_ip() {
		$remote = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);
		$remote = $remote ?: '0.0.0.0';

		if (!defined('AM_OPS_TRUSTED_PROXIES') || '' === trim((string) AM_OPS_TRUSTED_PROXIES)) {
			return $remote;
		}

		$trusted = false;
		foreach (explode(',', (string) AM_OPS_TRUSTED_PROXIES) as $cidr) {
			if (self::ip_in_cidr($remote, trim($cidr))) {
				$trusted = true;
				break;
			}
		}
		if (!$trusted) {
			return $remote;
		}

		$candidates = array();
		if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
			$candidates[] = trim((string) $_SERVER['HTTP_CF_CONNECTING_IP']);
		}
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$candidates = array_merge(
				$candidates,
				array_map('trim', explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']))
			);
		}

		foreach ($candidates as $candidate) {
			if (filter_var($candidate, FILTER_VALIDATE_IP)) {
				return $candidate;
			}
		}

		return $remote;
	}

	/**
	 * @return string Stable HMAC; raw IP is never stored.
	 */
	public static function ip_hash() {
		return hash_hmac('sha256', self::client_ip(), wp_salt('auth'));
	}

	/**
	 * @return string Privacy-preserving user-agent fingerprint.
	 */
	public static function user_agent_hash() {
		$user_agent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000);

		return '' === $user_agent
			? ''
			: hash_hmac('sha256', $user_agent, wp_salt('auth'));
	}

	/**
	 * Read and validate the browser's retry token.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return string
	 */
	public static function idempotency_key($request) {
		$key = trim((string) $request->get_header('X-AM-Idempotency-Key'));
		if ('' === $key) {
			$key = trim((string) $request->get_param('idempotencyKey'));
		}

		if (
			strlen($key) < 16
			|| strlen($key) > 128
			|| !preg_match('/^[A-Za-z0-9._:-]+$/', $key)
		) {
			return wp_generate_uuid4();
		}

		return $key;
	}

	/**
	 * IPv4/IPv6 CIDR membership.
	 *
	 * @param string $ip IP address.
	 * @param string $cidr IP or CIDR.
	 * @return bool
	 */
	private static function ip_in_cidr($ip, $cidr) {
		if ('' === $cidr) {
			return false;
		}
		if (false === strpos($cidr, '/')) {
			return hash_equals($cidr, $ip);
		}

		list($network, $prefix) = array_pad(explode('/', $cidr, 2), 2, '');
		$ip_binary      = @inet_pton($ip);
		$network_binary = @inet_pton($network);
		if (false === $ip_binary || false === $network_binary || strlen($ip_binary) !== strlen($network_binary)) {
			return false;
		}

		$prefix    = (int) $prefix;
		$max_bits  = strlen($ip_binary) * 8;
		if ($prefix < 0 || $prefix > $max_bits) {
			return false;
		}

		$full_bytes = intdiv($prefix, 8);
		$remaining  = $prefix % 8;
		if (
			$full_bytes > 0
			&& substr($ip_binary, 0, $full_bytes) !== substr($network_binary, 0, $full_bytes)
		) {
			return false;
		}
		if (0 === $remaining) {
			return true;
		}

		$mask = (0xFF << (8 - $remaining)) & 0xFF;

		return (ord($ip_binary[$full_bytes]) & $mask) === (ord($network_binary[$full_bytes]) & $mask);
	}
}
