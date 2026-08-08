<?php
/**
 * Reusable section templates.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_VB_Template {
	const POST_TYPE = 'am_vb_template';

	/**
	 * @return void
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array('name' => 'Visual builder templates'),
				'public'              => false,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'supports'            => array('title', 'editor', 'author'),
			)
		);
	}

	/**
	 * @param string              $name    Template name.
	 * @param array<string,mixed> $section Section data.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function create($name, $section) {
		$wrapped = AM_VB_Document::sanitize_document(
			array(
				'version'  => AM_VB_Document::SCHEMA_VERSION,
				'title'    => 'Template validation',
				'sections' => array($section),
			)
		);
		if (empty($wrapped['sections'][0])) {
			return new WP_Error('am_vb_invalid_template', 'A valid section is required.', array('status' => 400));
		}
		$name    = sanitize_text_field($name ?: ($wrapped['sections'][0]['name'] ?? 'Section template'));
		$content = array(
			'version' => 1,
			'type'    => 'section',
			'name'    => $name,
			'section' => $wrapped['sections'][0],
		);
		$id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => $name,
				'post_content' => wp_json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
				'post_author'  => get_current_user_id(),
			),
			true
		);
		if (is_wp_error($id)) {
			return $id;
		}

		return self::one(get_post($id));
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function all() {
		return array_map(
			array(__CLASS__, 'one'),
			get_posts(
				array(
					'post_type'      => self::POST_TYPE,
					'post_status'    => 'publish',
					'posts_per_page' => 100,
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			)
		);
	}

	/**
	 * @param WP_Post $post Template post.
	 * @return array<string,mixed>
	 */
	public static function one($post) {
		$data = json_decode((string) $post->post_content, true);

		return array(
			'id'      => (int) $post->ID,
			'name'    => $post->post_title,
			'section' => is_array($data) ? ($data['section'] ?? array()) : array(),
			'author'  => get_the_author_meta('display_name', $post->post_author),
			'date'    => mysql2date('c', $post->post_date_gmt, false),
		);
	}
}
