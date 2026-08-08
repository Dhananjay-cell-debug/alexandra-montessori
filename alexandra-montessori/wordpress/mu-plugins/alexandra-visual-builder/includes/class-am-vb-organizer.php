<?php
/**
 * Persistent page-panel organisation without changing public route ownership.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_VB_Organizer {
	const OPTION = 'am_vb_page_organizer';

	/** @return array<string,mixed> */
	public static function get() {
		$stored = get_option(self::OPTION, array());
		return self::sanitize(is_array($stored) ? $stored : array());
	}

	/** @return array<string,mixed> */
	public static function save($input) {
		$value = self::sanitize(is_array($input) ? $input : array());
		update_option(self::OPTION, $value, false);
		return $value;
	}

	/** @return void */
	public static function register_routes() {
		register_rest_route(
			AM_VB_REST::NAMESPACE,
			'/organizer',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array(__CLASS__, 'rest_get'),
					'permission_callback' => array(__CLASS__, 'can_edit'),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array(__CLASS__, 'rest_save'),
					'permission_callback' => array(__CLASS__, 'can_edit'),
					'args'                => array('organizer' => array('required' => true, 'type' => 'object')),
				),
			)
		);
	}

	/** @return bool */
	public static function can_edit() {
		return current_user_can('edit_am_visual_pages');
	}

	/** @return WP_REST_Response */
	public static function rest_get() {
		return rest_ensure_response(array('organizer' => self::get()));
	}

	/** @return WP_REST_Response */
	public static function rest_save($request) {
		return rest_ensure_response(array('organizer' => self::save((array) $request->get_param('organizer'))));
	}

	/** @return array<string,mixed> */
	private static function sanitize($input) {
		$known = array();
		foreach (AM_VB_Document::all_pages() as $page) {
			$known[$page['slug']] = $page;
		}

		$order = array();
		foreach ((array) ($input['order'] ?? array()) as $slug) {
			$slug = sanitize_key($slug);
			if ($slug && isset($known[$slug]) && !in_array($slug, $order, true)) {
				$order[] = $slug;
			}
		}
		foreach (array_keys($known) as $slug) {
			if (!in_array($slug, $order, true)) {
				$order[] = $slug;
			}
		}

		$labels = array();
		foreach ((array) ($input['labels'] ?? array()) as $slug => $label) {
			$slug  = sanitize_key($slug);
			$label = sanitize_text_field($label);
			if (isset($known[$slug]) && $label && $label !== $known[$slug]['title']) {
				$labels[$slug] = function_exists('mb_substr') ? mb_substr($label, 0, 80) : substr($label, 0, 80);
			}
		}

		$buckets = array();
		foreach ((array) ($input['buckets'] ?? array()) as $slug => $bucket) {
			$slug   = sanitize_key($slug);
			$bucket = sanitize_key($bucket);
			if (isset($known[$slug]) && in_array($bucket, array('navbar', 'menu'), true)) {
				$buckets[$slug] = $bucket;
			}
		}

		$hidden = array();
		foreach ((array) ($input['hidden'] ?? array()) as $slug) {
			$slug = sanitize_key($slug);
			if ($slug && isset($known[$slug]) && !in_array($slug, $hidden, true)) {
				$hidden[] = $slug;
			}
		}

		return array(
			'version' => 1,
			'order'   => $order,
			'labels'  => $labels,
			'buckets' => $buckets,
			'hidden'  => $hidden,
		);
	}
}
