<?php
/**
 * Revisioned visual-builder document storage and rendering.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_VB_Document {
	const POST_TYPE      = 'am_visual_page';
	const HOME_SLUG      = 'home-poc';
	const SCHEMA_VERSION = 2;
	const SCHEMA_OPTION  = 'am_vb_document_schema_version';
	const STATUS_META    = '_am_vb_workflow_status';
	const PUBLISHED_META = '_am_vb_published_document';

	/**
	 * Register a private, revision-enabled document type.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name'          => __('Visual pages', 'alexandra-visual-builder'),
					'singular_name' => __('Visual page', 'alexandra-visual-builder'),
				),
				'public'              => false,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'supports'            => array('title', 'editor', 'revisions'),
				'map_meta_cap'        => true,
				'capability_type'     => 'post',
			)
		);
	}

	/**
	 * Seed the isolated Home prototype on Local once.
	 *
	 * @return void
	 */
	public static function maybe_seed() {
		if (self::find(self::HOME_SLUG)) {
			return;
		}

		$document = self::default_document();
		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => 'Home page prototype',
				'post_name'    => self::HOME_SLUG,
				'post_content' => wp_json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
			),
			true
		);
		if (!is_wp_error($post_id)) {
			update_post_meta($post_id, self::STATUS_META, 'draft');
			AM_VB_Audit::log('page_created', $post_id, array('source' => 'phase_0_seed'));
		}
	}

	/**
	 * Persist schema migrations once while keeping get() defensive.
	 *
	 * @return void
	 */
	public static function maybe_migrate() {
		if (self::SCHEMA_VERSION === (int) get_option(self::SCHEMA_OPTION, 0)) {
			return;
		}
		$pages = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array('publish', 'draft', 'private'),
				'posts_per_page' => -1,
			)
		);
		foreach ($pages as $page) {
			$decoded  = json_decode((string) $page->post_content, true);
			$migrated = self::migrate_document(is_array($decoded) ? $decoded : array());
			wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_content' => wp_json_encode($migrated, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
				)
			);
			if (!get_post_meta($page->ID, self::STATUS_META, true)) {
				update_post_meta($page->ID, self::STATUS_META, 'draft');
			}
		}
		update_option(self::SCHEMA_OPTION, self::SCHEMA_VERSION, false);
	}

	/**
	 * @param string $slug Document slug.
	 * @return WP_Post|null
	 */
	public static function find($slug) {
		$post = get_page_by_path(sanitize_title($slug), OBJECT, self::POST_TYPE);

		return $post instanceof WP_Post ? $post : null;
	}

	/**
	 * @param string $slug Document slug.
	 * @return array<string,mixed>
	 */
	public static function get($slug) {
		$post = self::find($slug);
		if (!$post) {
			return self::default_document();
		}

		$decoded = json_decode((string) $post->post_content, true);

		return is_array($decoded) ? self::migrate_document($decoded) : self::default_document();
	}

	/**
	 * @param string              $slug     Document slug.
	 * @param array<string,mixed> $document Document data.
	 * @param string              $mode     manual or autosave.
	 * @param string              $base_hash Last server hash known by the client.
	 * @return WP_Post|WP_Error
	 */
	public static function save($slug, $document, $mode = 'manual', $base_hash = '') {
		$post = self::find($slug);
		if (!$post) {
			return new WP_Error('am_vb_missing_document', 'The visual page could not be found.');
		}
		$current = self::get($slug);
		if ($base_hash && !hash_equals(self::hash($current), sanitize_text_field($base_hash))) {
			return new WP_Error(
				'am_vb_content_conflict',
				'The page changed on the server. Reload or restore your local recovery copy.',
				array('status' => 409, 'serverHash' => self::hash($current))
			);
		}

		$document = self::sanitize_document($document);
		$result   = wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_title'   => $document['title'],
				'post_content' => wp_json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
			),
			true
		);
		if (!is_wp_error($result)) {
			update_post_meta($post->ID, '_am_vb_last_editor', get_current_user_id());
			AM_VB_Audit::log(
				'autosave' === $mode ? 'page_autosaved' : 'page_saved',
				$post->ID,
				array('hash' => self::hash($document), 'schema' => self::SCHEMA_VERSION)
			);
		}

		return is_wp_error($result) ? $result : get_post($result);
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function all_pages() {
		$pages = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array('publish', 'draft', 'private'),
				'posts_per_page' => 200,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
			)
		);

		return array_map(
			static function ($page) {
				$document = self::get($page->post_name);
				$exact    = class_exists('AM_VB_Site_Design') ? AM_VB_Site_Design::page_for_slug($page->post_name) : null;

				return array(
					'id'            => (int) $page->ID,
					'slug'          => $page->post_name,
					'title'         => $document['title'] ?? $page->post_title,
					'status'        => self::status($page->ID),
					'modified'      => mysql2date('c', $page->post_modified_gmt, false),
					'modifiedLabel' => mysql2date('j M Y, g:i a', $page->post_modified, false),
					'exactRoute'    => is_array($exact),
					'route'         => is_array($exact) ? $exact['route'] : '/' . $page->post_name,
					'family'        => is_array($exact) ? $exact['family'] : __('Custom pages', 'alexandra-visual-builder'),
					'navigation'    => is_array($exact) ? $exact['navigation'] : 'navbar',
					'pageType'      => is_array($exact) ? $exact['type'] : 'custom',
				);
			},
			$pages
		);
	}

	/**
	 * @param string              $title    Page title.
	 * @param string              $slug     Requested slug.
	 * @param array<string,mixed> $document Optional starting document.
	 * @return WP_Post|WP_Error
	 */
	public static function create($title, $slug = '', $document = array()) {
		$title = sanitize_text_field($title ?: 'Untitled page');
		$slug  = sanitize_title($slug ?: $title);
		if (!$slug) {
			return new WP_Error('am_vb_invalid_slug', 'A valid page name is required.', array('status' => 400));
		}
		if (self::find($slug)) {
			return new WP_Error('am_vb_slug_exists', 'A visual page with that slug already exists.', array('status' => 409));
		}
		if (!$document) {
			$document = array(
				'version'  => self::SCHEMA_VERSION,
				'title'    => $title,
				'sections' => array(),
			);
		}
		$document['title'] = $title;
		$document          = self::sanitize_document($document);
		$post_id           = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => wp_json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
				'post_author'  => get_current_user_id(),
			),
			true
		);
		if (is_wp_error($post_id)) {
			return $post_id;
		}
		update_post_meta($post_id, self::STATUS_META, 'draft');
		AM_VB_Audit::log('page_created', $post_id, array('slug' => $slug));

		return get_post($post_id);
	}

	/**
	 * @param int $post_id Page ID.
	 * @return bool|WP_Error
	 */
	public static function trash($post_id) {
		$post = get_post($post_id);
		if (!$post || self::POST_TYPE !== $post->post_type) {
			return new WP_Error('am_vb_not_found', 'Visual page not found.', array('status' => 404));
		}
		AM_VB_Audit::log('page_trashed', $post_id, array('slug' => $post->post_name));

		return (bool) wp_trash_post($post_id);
	}

	/**
	 * @param int $post_id Page ID.
	 * @return string
	 */
	public static function status($post_id) {
		$status = sanitize_key(get_post_meta($post_id, self::STATUS_META, true));

		return in_array($status, array('draft', 'review', 'published'), true) ? $status : 'draft';
	}

	/**
	 * @param WP_Post $post   Page post.
	 * @param string  $action Workflow action.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function transition($post, $action) {
		$action = sanitize_key($action);
		if ('submit_review' === $action) {
			update_post_meta($post->ID, self::STATUS_META, 'review');
			AM_VB_Audit::log('submitted_for_review', $post->ID);
		} elseif ('publish' === $action) {
			$document = self::get($post->post_name);
			$issues   = self::readiness_issues($document);
			if ($issues) {
				return new WP_Error(
					'am_vb_not_ready',
					'This page is not ready to publish.',
					array('status' => 400, 'issues' => $issues)
				);
			}
			update_post_meta(
				$post->ID,
				self::PUBLISHED_META,
				array(
					'schemaVersion' => self::SCHEMA_VERSION,
					'publishedAt'   => gmdate('c'),
					'publishedBy'   => get_current_user_id(),
					'hash'          => self::hash($document),
					'document'      => $document,
				)
			);
			update_post_meta($post->ID, self::STATUS_META, 'published');
			AM_VB_Audit::log('page_published', $post->ID, array('hash' => self::hash($document)));
		} elseif ('revert_draft' === $action) {
			update_post_meta($post->ID, self::STATUS_META, 'draft');
			AM_VB_Audit::log('reverted_to_draft', $post->ID);
		} else {
			return new WP_Error('am_vb_invalid_transition', 'Unknown workflow action.', array('status' => 400));
		}

		return array('status' => self::status($post->ID));
	}

	/**
	 * @param string $slug Page slug.
	 * @return array<string,mixed>|null
	 */
	public static function published($slug) {
		$post = self::find($slug);
		if (!$post) {
			return null;
		}
		$snapshot = get_post_meta($post->ID, self::PUBLISHED_META, true);

		return is_array($snapshot) ? $snapshot : null;
	}

	/**
	 * @param array<string,mixed> $document Document.
	 * @return string[]
	 */
	public static function readiness_issues($document) {
		$issues = array();
		if (empty($document['sections'])) {
			$issues[] = 'Add at least one section.';
		}
		foreach ((array) ($document['sections'] ?? array()) as $section) {
			foreach ((array) ($section['elements'] ?? array()) as $element) {
				if ('unknown' === ($element['type'] ?? '')) {
					$issues[] = 'Replace the unsupported element "' . ($element['name'] ?? 'Unknown') . '".';
				}
				if ('image' === ($element['type'] ?? '') && empty($element['alt'])) {
					$issues[] = 'Add alternative text to "' . ($element['name'] ?? 'Image') . '".';
				}
			}
		}

		return array_values(array_unique($issues));
	}

	/**
	 * @param array<string,mixed> $document Document.
	 * @return string
	 */
	public static function hash($document) {
		return hash('sha256', wp_json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}

	/**
	 * @param int $revision_id Revision ID.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function restore($revision_id) {
		$revision = wp_get_post_revision($revision_id);
		if (!$revision || self::POST_TYPE !== get_post_type($revision->post_parent)) {
			return new WP_Error('am_vb_invalid_revision', 'That revision is not available.');
		}

		$result = wp_restore_post_revision($revision_id);
		if (!$result) {
			return new WP_Error('am_vb_restore_failed', 'The revision could not be restored.');
		}

		$document = self::get(get_post_field('post_name', $revision->post_parent));
		AM_VB_Audit::log('revision_restored', $revision->post_parent, array('revision_id' => $revision_id));

		return $document;
	}

	/**
	 * @param int $post_id Visual page post ID.
	 * @return array<int,array<string,mixed>>
	 */
	public static function revisions($post_id) {
		$items = array();
		foreach (wp_get_post_revisions($post_id, array('posts_per_page' => 20)) as $revision) {
			$author = get_userdata($revision->post_author);
			$items[] = array(
				'id'        => (int) $revision->ID,
				'date'      => mysql2date('c', $revision->post_modified_gmt, false),
				'dateLabel' => mysql2date('j M Y, g:i a', $revision->post_modified, false),
				'author'    => $author ? $author->display_name : __('Unknown user', 'alexandra-visual-builder'),
			);
		}

		return $items;
	}

	/**
	 * @param array<string,mixed> $document Raw document data.
	 * @return array<string,mixed>
	 */
	public static function sanitize_document($document) {
		$document = self::migrate_document(is_array($document) ? $document : array());
		$clean = array(
			'version'  => self::SCHEMA_VERSION,
			'title'    => sanitize_text_field($document['title'] ?? 'Home page prototype'),
			'sections' => array(),
		);
		$used_ids = array();

		foreach (array_slice((array) ($document['sections'] ?? array()), 0, 30) as $section) {
			if (!is_array($section)) {
				continue;
			}
			$clean_section = array(
				'id'       => self::unique_id($section['id'] ?? '', 'section-', $used_ids),
				'name'     => sanitize_text_field($section['name'] ?? 'Section'),
				'visible'  => !isset($section['visible']) || (bool) $section['visible'],
				'locked'   => !empty($section['locked']),
				'settings' => self::sanitize_config($section['settings'] ?? array()),
				'groups'   => array(),
				'elements' => array(),
			);
			foreach (array_slice((array) ($section['groups'] ?? array()), 0, 30) as $group) {
				if (!is_array($group)) {
					continue;
				}
				$clean_section['groups'][] = array(
					'id'         => self::unique_id($group['id'] ?? '', 'group-', $used_ids),
					'name'       => sanitize_text_field($group['name'] ?? 'Group'),
					'elementIds' => array_values(array_unique(array_map(array(__CLASS__, 'clean_id'), array_slice((array) ($group['elementIds'] ?? array()), 0, 60)))),
					'visible'    => !isset($group['visible']) || (bool) $group['visible'],
					'locked'     => !empty($group['locked']),
				);
			}
			foreach (array_slice((array) ($section['elements'] ?? array()), 0, 60) as $element) {
				if (!is_array($element)) {
					continue;
				}
				$type = sanitize_key($element['type'] ?? 'text');
				if (!in_array($type, array('text', 'button', 'image', 'shape'), true)) {
					$original_type = $type ?: 'missing';
					$type          = 'unknown';
				}
				$clean_element = array(
					'id'      => self::unique_id($element['id'] ?? '', 'element-', $used_ids),
					'type'    => $type,
					'name'    => sanitize_text_field($element['name'] ?? ucfirst($type)),
					'visible' => !isset($element['visible']) || (bool) $element['visible'],
					'locked'  => !empty($element['locked']),
					'groupId' => self::clean_optional_id($element['groupId'] ?? ''),
					'styles'  => self::sanitize_config($element['styles'] ?? array()),
				);
				if ('text' === $type) {
					$clean_element['tag']     = in_array(($element['tag'] ?? ''), array('h1', 'h2', 'h3', 'h4', 'p', 'div'), true)
						? $element['tag']
						: 'p';
					$clean_element['content'] = wp_kses(
						$element['content'] ?? '',
						array(
							'a'      => array('href' => true, 'target' => true, 'rel' => true),
							'strong' => array(),
							'b'      => array(),
							'em'     => array(),
							'i'      => array(),
							'u'      => array(),
							's'      => array(),
							'br'     => array(),
							'ul'     => array(),
							'ol'     => array(),
							'li'     => array(),
							'span'   => array('style' => true),
						)
					);
				} elseif ('button' === $type) {
					$clean_element['label']     = sanitize_text_field($element['label'] ?? 'Button');
					$clean_element['href']      = esc_url_raw($element['href'] ?? '#');
					$clean_element['newWindow'] = !empty($element['newWindow']);
				} elseif ('image' === $type) {
					// Route through the same media contract as the Home and
					// per-route models, so a document cannot be the one place
					// that still stores an unreachable absolute URL.
					$media = AM_VB_Media::sanitize_pair(array(
						'src'   => $element['src'] ?? '',
						'srcId' => $element['mediaId'] ?? 0,
					));
					$clean_element['mediaId'] = $media['srcId'];
					$clean_element['src']     = $media['src'];
					$clean_element['alt']     = sanitize_text_field($element['alt'] ?? '');
				} elseif ('shape' === $type) {
					$shape = sanitize_key($element['shape'] ?? 'rectangle');
					$clean_element['shape'] = in_array($shape, array('rectangle', 'circle', 'pill', 'line', 'triangle'), true)
						? $shape
						: 'rectangle';
				} elseif ('unknown' === $type) {
					$clean_element['originalType'] = sanitize_key($element['originalType'] ?? $original_type);
					$clean_element['raw']          = self::sanitize_config($element['raw'] ?? $element);
				}
				$clean_section['elements'][] = $clean_element;
			}
			$element_ids = wp_list_pluck($clean_section['elements'], 'id');
			$group_ids   = wp_list_pluck($clean_section['groups'], 'id');
			foreach ($clean_section['groups'] as &$clean_group) {
				$clean_group['elementIds'] = array_values(
					array_unique(array_intersect($clean_group['elementIds'], $element_ids))
				);
			}
			unset($clean_group);
			foreach ($clean_section['elements'] as &$clean_element) {
				if (!in_array($clean_element['groupId'], $group_ids, true)) {
					$clean_element['groupId'] = '';
					continue;
				}
				foreach ($clean_section['groups'] as &$clean_group) {
					if ($clean_group['id'] === $clean_element['groupId']
						&& !in_array($clean_element['id'], $clean_group['elementIds'], true)) {
						$clean_group['elementIds'][] = $clean_element['id'];
					}
				}
				unset($clean_group);
			}
			unset($clean_element);
			$clean_section['groups'] = array_values(
				array_filter(
					$clean_section['groups'],
					static function ($group) {
						return !empty($group['elementIds']);
					}
				)
			);
			$clean['sections'][] = $clean_section;
		}

		return $clean;
	}

	/**
	 * Normalize older documents and malformed optional structures.
	 *
	 * @param array<string,mixed> $document Document.
	 * @return array<string,mixed>
	 */
	public static function migrate_document($document) {
		$document = is_array($document) ? $document : array();
		$document['version']  = self::SCHEMA_VERSION;
		$document['title']    = $document['title'] ?? 'Untitled page';
		$document['sections'] = is_array($document['sections'] ?? null) ? $document['sections'] : array();
		foreach ($document['sections'] as &$section) {
			if (!is_array($section)) {
				$section = array();
			}
			$section['id']       = $section['id'] ?? wp_unique_id('section-');
			$section['name']     = $section['name'] ?? 'Section';
			$section['visible']  = !isset($section['visible']) || (bool) $section['visible'];
			$section['locked']   = !empty($section['locked']);
			$section['settings'] = is_array($section['settings'] ?? null) ? $section['settings'] : array('layout' => 'stack');
			$section['groups']   = is_array($section['groups'] ?? null) ? $section['groups'] : array();
			$section['elements'] = is_array($section['elements'] ?? null) ? $section['elements'] : array();
			foreach ($section['elements'] as &$element) {
				if (!is_array($element)) {
					$element = array();
				}
				$element['visible'] = !isset($element['visible']) || (bool) $element['visible'];
				$element['locked']  = !empty($element['locked']);
				$element['groupId'] = $element['groupId'] ?? '';
			}
			unset($element);
		}
		unset($section);

		return $document;
	}

	/**
	 * @param mixed $value Arbitrary settings value.
	 * @param int   $depth Current recursion depth.
	 * @return mixed
	 */
	private static function sanitize_config($value, $depth = 0) {
		if ($depth > 5) {
			return '';
		}
		if (is_array($value)) {
			$clean = array();
			foreach (array_slice($value, 0, 80, true) as $key => $item) {
				$clean_key = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $key);
				if ('' === $clean_key) {
					continue;
				}
				$clean[$clean_key] = self::sanitize_config($item, $depth + 1);
			}
			return $clean;
		}
		if (is_bool($value) || is_int($value) || is_float($value)) {
			return $value;
		}
		if (preg_match('/(?:expression\s*\(|javascript\s*:|data\s*:\s*text\/html|@import|behavior\s*:)/i', (string) $value)) {
			return '';
		}

		return sanitize_text_field((string) $value);
	}

	/**
	 * @param string $id Candidate ID.
	 * @return string
	 */
	private static function clean_id($id) {
		$id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $id);

		return $id ?: wp_unique_id('amvb-');
	}

	/**
	 * Return a page-wide unique DOM identifier.
	 *
	 * @param string              $id       Candidate ID.
	 * @param string              $prefix   Fallback prefix.
	 * @param array<string,bool>  $used_ids IDs already claimed.
	 * @return string
	 */
	private static function unique_id($id, $prefix, &$used_ids) {
		$base      = self::clean_id($id);
		$candidate = $base;
		$suffix    = 2;
		while (isset($used_ids[$candidate])) {
			$candidate = $base . '-' . $suffix;
			++$suffix;
		}
		if (!$candidate) {
			$candidate = wp_unique_id($prefix);
		}
		$used_ids[$candidate] = true;

		return $candidate;
	}

	/**
	 * @param string $id Candidate optional ID.
	 * @return string
	 */
	private static function clean_optional_id($id) {
		return preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $id);
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function default_document() {
		$image = self::starter_image();

		$document = array(
			'version'  => self::SCHEMA_VERSION,
			'title'    => 'Home page prototype',
			'sections' => array(
				array(
					'id'       => 'hero-section',
					'name'     => 'Hero',
					'settings' => array(
						'layout'  => 'layered',
						'desktop' => array(
							'backgroundColor' => '#dfeadd',
							'minHeight'       => '620px',
							'padding'         => '72px',
							'gap'             => '24px',
							'overflow'        => 'hidden',
						),
						'tablet'  => array('minHeight' => '560px', 'padding' => '48px'),
						'mobile'  => array('minHeight' => '620px', 'padding' => '28px'),
					),
					'elements' => array(
						array(
							'id'      => 'hero-accent',
							'type'    => 'shape',
							'name'    => 'Decorative circle',
							'shape'   => 'circle',
							'styles'  => array(
								'desktop' => array(
									'backgroundColor' => '#a9c6a2',
									'width'           => '320px',
									'height'          => '320px',
									'positionX'       => '69%',
									'positionY'       => '11%',
									'opacity'         => '0.72',
									'rotate'          => '-8deg',
									'zIndex'          => '1',
								),
								'tablet' => array('width' => '250px', 'height' => '250px', 'positionX' => '63%'),
								'mobile' => array('width' => '190px', 'height' => '190px', 'positionX' => '45%', 'positionY' => '56%'),
							),
						),
						array(
							'id'      => 'hero-eyebrow',
							'type'    => 'text',
							'name'    => 'Eyebrow',
							'tag'     => 'p',
							'content' => '<strong>LEARNING FOR LIFE</strong>',
							'styles'  => array(
								'desktop' => array(
									'fontSize'     => '14px',
									'letterSpacing' => '0.16em',
									'color'        => '#49634d',
									'width'        => '52%',
									'positionX'    => '8%',
									'positionY'    => '22%',
									'zIndex'       => '3',
								),
								'mobile' => array('width' => '84%', 'positionX' => '8%', 'positionY' => '10%'),
							),
						),
						array(
							'id'      => 'hero-heading',
							'type'    => 'text',
							'name'    => 'Main heading',
							'tag'     => 'h1',
							'content' => 'A calm place to <em>grow</em>, explore and belong.',
							'styles'  => array(
								'desktop' => array(
									'fontSize'   => '64px',
									'lineHeight' => '1.04',
									'fontWeight' => '600',
									'color'      => '#20372a',
									'width'      => '54%',
									'positionX'  => '8%',
									'positionY'  => '29%',
									'zIndex'     => '3',
								),
								'tablet' => array('fontSize' => '48px', 'width' => '60%'),
								'mobile' => array('fontSize' => '38px', 'width' => '84%', 'positionX' => '8%', 'positionY' => '17%'),
							),
						),
						array(
							'id'      => 'hero-copy',
							'type'    => 'text',
							'name'    => 'Introduction',
							'tag'     => 'p',
							'content' => 'Thoughtful Montessori education, warm relationships and carefully prepared environments for every stage of early childhood.',
							'styles'  => array(
								'desktop' => array(
									'fontSize'   => '19px',
									'lineHeight' => '1.6',
									'color'      => '#45594b',
									'width'      => '45%',
									'positionX'  => '8%',
									'positionY'  => '61%',
									'zIndex'     => '3',
								),
								'mobile' => array('fontSize' => '17px', 'width' => '84%', 'positionX' => '8%', 'positionY' => '50%'),
							),
						),
						array(
							'id'        => 'hero-button',
							'type'      => 'button',
							'name'      => 'Primary action',
							'label'     => 'Explore our nurseries',
							'href'      => '#',
							'newWindow' => false,
							'styles'    => array(
								'desktop' => array(
									'fontSize'       => '15px',
									'fontWeight'     => '700',
									'color'          => '#ffffff',
									'backgroundColor'=> '#345b40',
									'padding'        => '16px 24px',
									'borderRadius'   => '999px',
									'positionX'      => '8%',
									'positionY'      => '81%',
									'zIndex'         => '4',
								),
								'mobile' => array('positionX' => '8%', 'positionY' => '77%'),
							),
						),
						array(
							'id'      => 'hero-image',
							'type'    => 'image',
							'name'    => 'Hero image',
							'mediaId' => $image['id'],
							'src'     => $image['url'],
							'alt'     => $image['alt'],
							'styles'  => array(
								'desktop' => array(
									'width'        => '34%',
									'height'       => '70%',
									'positionX'    => '61%',
									'positionY'    => '15%',
									'objectFit'    => 'cover',
									'objectPosition'=> '50% 50%',
									'borderRadius' => '180px 180px 24px 24px',
									'boxShadow'    => '0 24px 70px rgba(35,66,44,.22)',
									'zIndex'       => '2',
								),
								'tablet' => array('width' => '33%', 'height' => '62%', 'positionX' => '64%', 'positionY' => '20%'),
								'mobile' => array('width' => '46%', 'height' => '28%', 'positionX' => '48%', 'positionY' => '66%', 'borderRadius' => '90px 90px 18px 18px'),
							),
						),
					),
				),
				array(
					'id'       => 'welcome-section',
					'name'     => 'Welcome',
					'settings' => array(
						'layout'  => 'stack',
						'desktop' => array(
							'backgroundColor' => '#fffdf8',
							'padding'         => '88px 12%',
							'gap'             => '22px',
							'textAlign'       => 'center',
						),
						'mobile' => array('padding' => '56px 24px'),
					),
					'elements' => array(
						array(
							'id'      => 'welcome-heading',
							'type'    => 'text',
							'name'    => 'Section heading',
							'tag'     => 'h2',
							'content' => 'Built around the child',
							'styles'  => array(
								'desktop' => array('fontSize' => '44px', 'lineHeight' => '1.15', 'color' => '#284331', 'fontWeight' => '600'),
								'mobile'  => array('fontSize' => '34px'),
							),
						),
						array(
							'id'      => 'welcome-copy',
							'type'    => 'text',
							'name'    => 'Section text',
							'tag'     => 'div',
							'content' => 'Use the toolbar to make text <strong>bold</strong>, <em>italic</em> or <u>underlined</u>. You can also create lists:<ul><li>Edit directly on the page</li><li>Style each device independently</li><li>Undo or restore an earlier save</li></ul>',
							'styles'  => array(
								'desktop' => array('fontSize' => '18px', 'lineHeight' => '1.75', 'color' => '#56645a', 'width' => '720px', 'margin' => '0 auto'),
								'mobile'  => array('fontSize' => '16px', 'width' => '100%'),
							),
						),
					),
				),
			),
		);

		return self::migrate_document($document);
	}

	/**
	 * @return array{id:int,url:string,alt:string}
	 */
	private static function starter_image() {
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		if (!$attachments) {
			return array('id' => 0, 'url' => '', 'alt' => 'Montessori learning environment');
		}
		$id = (int) $attachments[0]->ID;

		return array(
			'id'  => $id,
			'url' => (string) wp_get_attachment_image_url($id, 'large'),
			'alt' => (string) (get_post_meta($id, '_wp_attachment_image_alt', true) ?: $attachments[0]->post_title),
		);
	}
}
