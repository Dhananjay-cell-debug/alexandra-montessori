<?php
/**
 * Versioned Phase 1 feature flags.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_VB_Features {
	const OPTION = 'am_vb_feature_flags';

	/**
	 * @return array<string,bool>
	 */
	public static function defaults() {
		return array(
			'editor'       => true,
			'autosave'     => true,
			'locking'      => true,
			'templates'    => true,
			'publishing'   => true,
			'public_ast'   => true,
			'audit'        => true,
			'crash_recovery' => true,
		);
	}

	/**
	 * @return array<string,bool>
	 */
	public static function all() {
		$stored = get_option(self::OPTION, array());
		$flags  = array_merge(self::defaults(), is_array($stored) ? $stored : array());

		foreach ($flags as $key => $enabled) {
			$flags[$key] = (bool) $enabled;
		}

		return apply_filters('am_vb_feature_flags', $flags);
	}

	/**
	 * @param string $feature Feature key.
	 * @return bool
	 */
	public static function enabled($feature) {
		$flags = self::all();

		return !empty($flags[$feature]);
	}
}
