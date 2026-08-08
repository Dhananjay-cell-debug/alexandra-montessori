<?php
/**
 * Phase 1 REST API for visual pages, workflow and reusable assets.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_VB_REST {
	const NAMESPACE = 'am-visual-builder/v1';

	/**
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/pages',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array(__CLASS__, 'get_pages'),
					'permission_callback' => array(__CLASS__, 'can_edit'),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array(__CLASS__, 'create_page'),
					'permission_callback' => array(__CLASS__, 'can_edit'),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/page/(?P<slug>[a-z0-9-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array(__CLASS__, 'get_page'),
					'permission_callback' => array(__CLASS__, 'can_edit'),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array(__CLASS__, 'save_page'),
					'permission_callback' => array(__CLASS__, 'can_edit'),
					'args'                => array(
						'document' => array('required' => true, 'type' => 'object'),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array(__CLASS__, 'delete_page'),
					'permission_callback' => array(__CLASS__, 'can_edit'),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/page/(?P<slug>[a-z0-9-]+)/lock',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array(__CLASS__, 'lock_page'),
					'permission_callback' => array(__CLASS__, 'can_edit'),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array(__CLASS__, 'unlock_page'),
					'permission_callback' => array(__CLASS__, 'can_edit'),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/page/(?P<slug>[a-z0-9-]+)/status',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array(__CLASS__, 'transition_page'),
				'permission_callback' => array(__CLASS__, 'can_edit'),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/page/(?P<slug>[a-z0-9-]+)/revisions',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array(__CLASS__, 'get_revisions'),
				'permission_callback' => array(__CLASS__, 'can_edit'),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/page/(?P<slug>[a-z0-9-]+)/revisions/(?P<revision_id>\d+)/restore',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array(__CLASS__, 'restore_revision'),
				'permission_callback' => array(__CLASS__, 'can_edit'),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/page/(?P<slug>[a-z0-9-]+)/audit',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array(__CLASS__, 'get_audit'),
				'permission_callback' => array(__CLASS__, 'can_view_audit'),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/templates',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array(__CLASS__, 'get_templates'),
					'permission_callback' => array(__CLASS__, 'can_edit'),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array(__CLASS__, 'create_template'),
					'permission_callback' => array(__CLASS__, 'can_manage_templates'),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/templates/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array(__CLASS__, 'delete_template'),
				'permission_callback' => array(__CLASS__, 'can_manage_templates'),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/media',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array(__CLASS__, 'get_media'),
				'permission_callback' => array(__CLASS__, 'can_edit'),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/features',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array(__CLASS__, 'get_features'),
				'permission_callback' => array(__CLASS__, 'can_edit'),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/public/page/(?P<slug>[a-z0-9-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array(__CLASS__, 'get_public_page'),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * @return bool
	 */
	public static function can_edit() {
		return current_user_can('edit_am_visual_pages');
	}

	/**
	 * @return bool
	 */
	public static function can_publish() {
		return current_user_can('publish_am_visual_pages');
	}

	/**
	 * @return bool
	 */
	public static function can_manage_templates() {
		return current_user_can('manage_am_visual_templates');
	}

	/**
	 * @return bool
	 */
	public static function can_view_audit() {
		return current_user_can('view_am_visual_audit');
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function get_pages() {
		return rest_ensure_response(array('pages' => AM_VB_Document::all_pages()));
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_page($request) {
		$post = AM_VB_Document::create(
			$request->get_param('title'),
			$request->get_param('slug'),
			(array) $request->get_param('document')
		);
		if (is_wp_error($post)) {
			return $post;
		}

		return new WP_REST_Response(self::page_payload($post, $request->get_param('sessionId')), 201);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_page($request) {
		$post = self::page($request['slug']);
		if (is_wp_error($post)) {
			return $post;
		}

		return rest_ensure_response(self::page_payload($post, $request->get_param('sessionId')));
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function save_page($request) {
		$post = self::page($request['slug']);
		if (is_wp_error($post)) {
			return $post;
		}
		$session_id = (string) $request->get_param('sessionId');
		$owned      = AM_VB_Locks::assert_owned($post->ID, $session_id);
		if (is_wp_error($owned)) {
			return $owned;
		}
		$mode  = 'autosave' === $request->get_param('mode') ? 'autosave' : 'manual';
		$saved = AM_VB_Document::save(
			$request['slug'],
			$request->get_param('document'),
			$mode,
			(string) $request->get_param('baseHash')
		);
		if (is_wp_error($saved)) {
			return $saved;
		}
		AM_VB_Locks::acquire($post->ID, $session_id);

		return rest_ensure_response(self::page_payload($saved, $session_id));
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function delete_page($request) {
		$post = self::page($request['slug']);
		if (is_wp_error($post)) {
			return $post;
		}
		$owned = AM_VB_Locks::assert_owned($post->ID, (string) $request->get_param('sessionId'));
		if (is_wp_error($owned)) {
			return $owned;
		}
		$deleted = AM_VB_Document::trash($post->ID);
		if (is_wp_error($deleted)) {
			return $deleted;
		}

		return rest_ensure_response(array('deleted' => (bool) $deleted, 'pages' => AM_VB_Document::all_pages()));
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function lock_page($request) {
		$post = self::page($request['slug']);
		if (is_wp_error($post)) {
			return $post;
		}
		$status = AM_VB_Locks::acquire($post->ID, (string) $request->get_param('sessionId'));

		return is_wp_error($status) ? $status : rest_ensure_response(array('lock' => $status));
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function unlock_page($request) {
		$post = self::page($request['slug']);
		if (is_wp_error($post)) {
			return $post;
		}

		return rest_ensure_response(
			array('lock' => AM_VB_Locks::release($post->ID, (string) $request->get_param('sessionId')))
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function transition_page($request) {
		$post = self::page($request['slug']);
		if (is_wp_error($post)) {
			return $post;
		}
		$owned = AM_VB_Locks::assert_owned($post->ID, (string) $request->get_param('sessionId'));
		if (is_wp_error($owned)) {
			return $owned;
		}
		$action = sanitize_key($request->get_param('action'));
		if ('publish' === $action && !self::can_publish()) {
			return new WP_Error('rest_forbidden', 'You cannot publish visual pages.', array('status' => 403));
		}
		$result = AM_VB_Document::transition($post, $action);

		return is_wp_error($result)
			? $result
			: rest_ensure_response(array_merge($result, self::page_payload(get_post($post->ID), $request->get_param('sessionId'))));
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_revisions($request) {
		$post = self::page($request['slug']);

		return is_wp_error($post)
			? $post
			: rest_ensure_response(array('revisions' => AM_VB_Document::revisions($post->ID)));
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function restore_revision($request) {
		$post = self::page($request['slug']);
		if (is_wp_error($post)) {
			return $post;
		}
		$owned = AM_VB_Locks::assert_owned($post->ID, (string) $request->get_param('sessionId'));
		if (is_wp_error($owned)) {
			return $owned;
		}
		$revision = wp_get_post_revision(absint($request['revision_id']));
		if (!$revision || (int) $revision->post_parent !== (int) $post->ID) {
			return new WP_Error('am_vb_invalid_revision', 'That revision is not available.', array('status' => 404));
		}
		$document = AM_VB_Document::restore($revision->ID);
		if (is_wp_error($document)) {
			return $document;
		}

		return rest_ensure_response(self::page_payload(get_post($post->ID), $request->get_param('sessionId')));
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_audit($request) {
		$post = self::page($request['slug']);

		return is_wp_error($post)
			? $post
			: rest_ensure_response(array('audit' => AM_VB_Audit::recent($post->ID, 50)));
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function get_templates() {
		return rest_ensure_response(array('templates' => AM_VB_Template::all()));
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_template($request) {
		if (!AM_VB_Features::enabled('templates')) {
			return new WP_Error('am_vb_feature_disabled', 'Templates are disabled.', array('status' => 404));
		}
		$template = AM_VB_Template::create($request->get_param('name'), (array) $request->get_param('section'));

		return is_wp_error($template)
			? $template
			: new WP_REST_Response(array('template' => $template, 'templates' => AM_VB_Template::all()), 201);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function delete_template($request) {
		$post = get_post(absint($request['id']));
		if (!$post || AM_VB_Template::POST_TYPE !== $post->post_type) {
			return new WP_Error('am_vb_template_not_found', 'Template not found.', array('status' => 404));
		}
		wp_trash_post($post->ID);

		return rest_ensure_response(array('deleted' => true, 'templates' => AM_VB_Template::all()));
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function get_media() {
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_status'    => 'inherit',
				'posts_per_page' => 80,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		$items = array_map(
			static function ($attachment) {
				$thumbnail = wp_get_attachment_image_url($attachment->ID, 'thumbnail');
				$large     = wp_get_attachment_image_url($attachment->ID, 'large');

				return array(
					'id'        => (int) $attachment->ID,
					'title'     => $attachment->post_title,
					'alt'       => (string) get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
					'thumbnail' => (string) ($thumbnail ?: $large),
					'url'       => (string) ($large ?: wp_get_attachment_url($attachment->ID)),
				);
			},
			$attachments
		);

		return rest_ensure_response(array('media' => $items));
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function get_features() {
		return rest_ensure_response(
			array(
				'features' => AM_VB_Features::all(),
				'schema'   => AM_VB_Document::SCHEMA_VERSION,
			)
		);
	}

	/**
	 * Public, immutable snapshot endpoint. Draft data is never returned.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_public_page($request) {
		if (!AM_VB_Features::enabled('public_ast')) {
			return new WP_Error('am_vb_feature_disabled', 'Published visual pages are unavailable.', array('status' => 404));
		}
		$snapshot = AM_VB_Document::published($request['slug']);
		if (!$snapshot) {
			return new WP_Error('am_vb_public_not_found', 'Published visual page not found.', array('status' => 404));
		}
		$response = rest_ensure_response($snapshot);
		$response->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=300');
		$response->header('ETag', '"' . ($snapshot['hash'] ?? '') . '"');

		return $response;
	}

	/**
	 * @param string $slug Page slug.
	 * @return WP_Post|WP_Error
	 */
	private static function page($slug) {
		$post = AM_VB_Document::find($slug);

		return $post ?: new WP_Error('am_vb_not_found', 'Visual page not found.', array('status' => 404));
	}

	/**
	 * @param WP_Post $post       Visual page.
	 * @param string  $session_id Editor session.
	 * @return array<string,mixed>
	 */
	private static function page_payload($post, $session_id = '') {
		$document = AM_VB_Document::get($post->post_name);
		$hash     = AM_VB_Document::hash($document);
		$snapshot = AM_VB_Document::published($post->post_name);

		return array(
			'id'              => (int) $post->ID,
			'slug'            => $post->post_name,
			'document'        => $document,
			'hash'            => $hash,
			'schemaVersion'   => AM_VB_Document::SCHEMA_VERSION,
			'status'          => AM_VB_Document::status($post->ID),
			'readinessIssues' => AM_VB_Document::readiness_issues($document),
			'modified'        => mysql2date('c', $post->post_modified_gmt, false),
			'modifiedLabel'   => mysql2date('j M Y, g:i a', $post->post_modified, false),
			'revisions'       => AM_VB_Document::revisions($post->ID),
			'audit'           => AM_VB_Audit::recent($post->ID, 12),
			'lock'            => AM_VB_Locks::status($post->ID, $session_id),
			'publication'     => array(
				'hasSnapshot'          => (bool) $snapshot,
				'publishedAt'          => $snapshot['publishedAt'] ?? '',
				'publishedHash'        => $snapshot['hash'] ?? '',
				'hasUnpublishedChanges'=> (bool) $snapshot && !hash_equals((string) ($snapshot['hash'] ?? ''), $hash),
			),
		);
	}
}
