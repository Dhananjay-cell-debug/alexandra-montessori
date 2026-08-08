<?php
/**
 * Immutable builder audit events.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_VB_Audit {
	const POST_TYPE = 'am_vb_audit';

	/**
	 * @return void
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array('name' => 'Visual builder audit'),
				'public'              => false,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'supports'            => array('title', 'editor', 'author'),
			)
		);
	}

	/**
	 * @param string              $action  Stable action key.
	 * @param int                 $page_id Visual page ID.
	 * @param array<string,mixed> $context Safe event context.
	 * @return int
	 */
	public static function log($action, $page_id, $context = array()) {
		if (!AM_VB_Features::enabled('audit')) {
			return 0;
		}
		$page = get_post($page_id);
		if (!$page) {
			return 0;
		}
		$event_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => sanitize_key($action),
				'post_content' => wp_json_encode(self::sanitize_context($context)),
				'post_author'  => get_current_user_id(),
			),
			true
		);
		if (is_wp_error($event_id)) {
			return 0;
		}
		update_post_meta($event_id, '_am_vb_page_id', (int) $page_id);
		update_post_meta($event_id, '_am_vb_page_slug', $page->post_name);

		return (int) $event_id;
	}

	/**
	 * @param int $page_id Page ID.
	 * @param int $limit   Maximum events.
	 * @return array<int,array<string,mixed>>
	 */
	public static function recent($page_id, $limit = 20) {
		$events = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => min(50, max(1, (int) $limit)),
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_key'       => '_am_vb_page_id',
				'meta_value'     => (int) $page_id,
			)
		);

		return array_map(
			static function ($event) {
				$author  = get_userdata($event->post_author);
				$context = json_decode((string) $event->post_content, true);

				return array(
					'id'        => (int) $event->ID,
					'action'    => $event->post_title,
					'date'      => mysql2date('c', $event->post_date_gmt, false),
					'dateLabel' => mysql2date('j M Y, g:i a', $event->post_date, false),
					'author'    => $author ? $author->display_name : 'System',
					'context'   => is_array($context) ? $context : array(),
				);
			},
			$events
		);
	}

	/**
	 * @param array<string,mixed> $context Context.
	 * @return array<string,mixed>
	 */
	private static function sanitize_context($context) {
		$clean = array();
		foreach (array_slice($context, 0, 20, true) as $key => $value) {
			$key = sanitize_key($key);
			if (is_bool($value) || is_int($value) || is_float($value)) {
				$clean[$key] = $value;
			} else {
				$clean[$key] = sanitize_text_field((string) $value);
			}
		}

		return $clean;
	}
}
