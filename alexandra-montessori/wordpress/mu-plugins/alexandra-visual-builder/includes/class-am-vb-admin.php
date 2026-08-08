<?php
/**
 * Admin workspace and isolated preview.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_VB_Admin {
	const PAGE_SLUG = 'am_visual_builder';

	/**
	 * @return void
	 */
	public static function register_menu() {
		// An Editor with no allocated pages has nothing to open, so the entry
		// point is withheld rather than shown and then refused.
		if (!AM_VB_Access::can_use_builder()) {
			return;
		}
		$parent = defined('AM_CONTENT_MENU_SLUG') ? AM_CONTENT_MENU_SLUG : 'am_content';
		add_submenu_page(
			$parent,
			__('Visual Site Builder', 'alexandra-visual-builder'),
			__('Visual Builder', 'alexandra-visual-builder'),
			'edit_am_visual_pages',
			self::PAGE_SLUG,
			array(__CLASS__, 'render_page')
		);
	}

	/**
	 * @param string $hook Admin page hook.
	 * @return void
	 */
	public static function enqueue_assets($hook) {
		if ('website-content_page_' . self::PAGE_SLUG !== $hook
			&& false === strpos($hook, self::PAGE_SLUG)) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style('wp-components');
		wp_enqueue_style(
			'am-vb-canvas',
			AM_VB_PLUGIN_URL . 'assets/canvas.css',
			array(),
			AM_VB_VERSION
		);
		wp_enqueue_style(
			'am-vb-editor',
			AM_VB_PLUGIN_URL . 'assets/editor.css',
			array('wp-components', 'am-vb-canvas'),
			AM_VB_VERSION
		);
		wp_enqueue_script(
			'am-vb-inspector',
			AM_VB_PLUGIN_URL . 'assets/inspector.js',
			array(),
			AM_VB_VERSION,
			true
		);
		wp_enqueue_script(
			'am-vb-editor',
			AM_VB_PLUGIN_URL . 'assets/editor.js',
			array('wp-element', 'wp-api-fetch', 'wp-components', 'wp-i18n', 'am-vb-inspector'),
			AM_VB_VERSION,
			true
		);
		wp_add_inline_script(
			'am-vb-editor',
			'window.amVisualBuilder = ' . wp_json_encode(
				array(
					'root'       => esc_url_raw(rest_url('am-visual-builder/v1/')),
					'nonce'      => wp_create_nonce('wp_rest'),
					'initialSlug'=> self::requested_editor_slug(),
					'openSavedSessions' => !empty($_GET['am_vb_sessions']),
					'restoreSavedSessionId' => isset($_GET['am_vb_restore_session']) ? absint(wp_unslash($_GET['am_vb_restore_session'])) : 0,
					'previewBase'=> add_query_arg('am_visual_preview', '__AM_VB_SLUG__', home_url('/')),
					'liveCanvasUrl' => add_query_arg('am_visual_canvas', 'home', home_url('/')),
					'exactRoutes' => self::allowed_editor_registry(),
					'styleSchema' => AM_VB_Style_Schema::payload(),
					'liveHomeUrl' => home_url('/'),
					'contentUrl' => admin_url('admin.php?page=' . (defined('AM_CONTENT_MENU_SLUG') ? AM_CONTENT_MENU_SLUG : 'am_content')),
					'aboutUrl'   => admin_url('admin.php?page=am_about'),
					'nurseriesUrl' => admin_url('edit.php?post_type=am_nursery'),
					'testimonialsUrl' => admin_url('edit.php?post_type=am_testimonial'),
					'settingsUrl' => admin_url('admin.php?page=am_settings'),
					'brandName'  => get_bloginfo('name'),
					'canPublish' => current_user_can('publish_am_visual_pages'),
					'canManageTemplates' => current_user_can('manage_am_visual_templates'),
					'canViewAudit' => current_user_can('view_am_visual_audit'),
					'canOrganizePages' => AM_VB_Access::is_unrestricted(),
					'allowedPages' => array_values(AM_VB_Access::allowed_pages()),
					'user'       => wp_get_current_user()->display_name,
					'userRole'   => AM_VB_Access::role_label(),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * @return void
	 */
	public static function render_page() {
		if (!current_user_can('edit_am_visual_pages') || !AM_VB_Access::can_use_builder()) {
			wp_die(esc_html__('Access denied.', 'alexandra-visual-builder'));
		}

		echo '<div id="am-visual-builder-root">';
		echo '<div class="am-vb-loading-screen"><span class="spinner is-active"></span><p>Opening visual builder…</p></div>';
		echo '</div>';
	}

	/**
	 * Add an obvious entry point to the existing Website Content card grid
	 * without coupling the proof of concept to the active theme's PHP file.
	 *
	 * @return void
	 */
	public static function render_workspace_entry() {
		if (!current_user_can('edit_am_visual_pages') || !AM_VB_Access::can_use_builder()) {
			return;
		}
		$url = admin_url('admin.php?page=' . self::PAGE_SLUG);
		?>
		<script>
		(function () {
			var grid = document.querySelector('.am-dashboard-grid');
			if (!grid || grid.querySelector('[data-am-vb-entry]')) return;
			var card = document.createElement('a');
			card.className = 'am-dashboard-card';
			card.href = <?php echo wp_json_encode($url); ?>;
			card.setAttribute('data-am-vb-entry', 'true');
			card.innerHTML = '<span class="dashicons dashicons-art" style="font-size:28px;width:34px;height:34px"></span>'
				+ '<strong>Visual Site Builder</strong>'
				+ '<span>Design the live website page by page, exactly as visitors see it.</span>';
			grid.insertBefore(card, grid.firstChild);
		})();
		</script>
		<?php
	}

	/**
	 * Validate the separate, capability-protected React preview URL. The normal
	 * theme request continues after this check, allowing the existing React
	 * entry point to render the visual-builder document.
	 *
	 * @return void
	 */
	public static function maybe_render_preview() {
		if (!empty($_GET['am_visual_canvas'])) {
			if (!is_user_logged_in() || !current_user_can('edit_am_visual_pages')) {
				auth_redirect();
			}
			// The canvas is the editable surface, so it is allocated per page
			// exactly like the editor itself.
			$canvas_slug = sanitize_title(wp_unslash($_GET['am_visual_canvas']));
			if (!AM_VB_Access::can_edit_page($canvas_slug)) {
				wp_die(
					esc_html__('This account is not allocated to that page.', 'alexandra-visual-builder'),
					'',
					array('response' => 403)
				);
			}
			show_admin_bar(false);
			nocache_headers();

			return;
		}
		if (empty($_GET['am_visual_preview'])) {
			return;
		}
		if (!is_user_logged_in() || !current_user_can('edit_am_visual_pages')) {
			auth_redirect();
		}
		$slug = sanitize_title(wp_unslash($_GET['am_visual_preview']));
		if ($slug && !AM_VB_Access::can_edit_page($slug)) {
			wp_die(
				esc_html__('This account is not allocated to that page.', 'alexandra-visual-builder'),
				'',
				array('response' => 403)
			);
		}
		if (!$slug || !AM_VB_Document::find($slug)) {
			status_header(404);
			wp_die(esc_html__('Visual preview not found.', 'alexandra-visual-builder'), '', array('response' => 404));
		}

		show_admin_bar(false);
		nocache_headers();
	}

	/**
	 * Inject the saved block tree into the real React entry point only for the
	 * protected prototype preview request.
	 *
	 * @return void
	 */
	public static function enqueue_preview_assets() {
		if (empty($_GET['am_visual_preview']) || !current_user_can('edit_am_visual_pages')) {
			return;
		}
		$slug = sanitize_title(wp_unslash($_GET['am_visual_preview']));
		if (!$slug || !AM_VB_Document::find($slug) || !wp_script_is('alexandra-app', 'enqueued')) {
			return;
		}
		$document = AM_VB_Document::get($slug);

		wp_enqueue_style(
			'am-vb-react-preview',
			AM_VB_PLUGIN_URL . 'assets/canvas.css',
			array(),
			AM_VB_VERSION
		);
		wp_add_inline_style('am-vb-react-preview', self::responsive_rules($document));
		wp_localize_script(
			'alexandra-app',
			'amVisualBuilderPreview',
			array(
				'document' => $document,
				'editUrl'  => add_query_arg('am_vb_page', $slug, admin_url('admin.php?page=' . self::PAGE_SLUG)),
				'label'    => __('Protected draft preview', 'alexandra-visual-builder'),
				'status'   => AM_VB_Document::status(AM_VB_Document::find($slug)->ID),
			)
		);
	}

	/**
	 * Resolve the requested visual page without trusting arbitrary query data.
	 *
	 * @return string
	 */
	private static function requested_editor_slug() {
		$requested = isset($_GET['am_vb_page']) ? sanitize_title(wp_unslash($_GET['am_vb_page'])) : '';
		if ($requested && AM_VB_Document::find($requested) && AM_VB_Access::can_edit_page($requested)) {
			return $requested;
		}
		// Fall back to the first page this account is actually allowed to open,
		// never to Home, which an Editor may well not be allocated.
		$allowed = AM_VB_Access::allowed_pages();
		foreach (AM_VB_Document::all_pages() as $page) {
			if (!empty($page['slug']) && in_array($page['slug'], $allowed, true)) {
				return $page['slug'];
			}
		}

		return !empty($allowed[0]) ? $allowed[0] : AM_VB_Document::HOME_SLUG;
	}

	/**
	 * The exact-route registry trimmed to this account's allocation, so an
	 * Editor's page rail lists only pages they can genuinely open.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function allowed_editor_registry() {
		$registry = AM_VB_Site_Design::editor_registry();
		if (AM_VB_Access::is_unrestricted()) {
			return $registry;
		}
		$allowed = array_flip(AM_VB_Access::allowed_pages());

		return array_intersect_key($registry, $allowed);
	}

	/**
	 * @param array<string,mixed> $document Document.
	 * @return string
	 */
	private static function render_sections($document) {
		$html = '';
		foreach ((array) ($document['sections'] ?? array()) as $section) {
			$id       = sanitize_html_class($section['id'] ?? '');
			$settings = (array) ($section['settings'] ?? array());
			$layout   = sanitize_html_class($settings['layout'] ?? 'stack');
			$html    .= '<section class="am-vb-section am-vb-section--' . esc_attr($id) . ' am-vb-layout--' . esc_attr($layout) . '">';
			foreach ((array) ($section['elements'] ?? array()) as $element) {
				$html .= self::render_element($element);
			}
			$html .= '</section>';
		}

		return $html;
	}

	/**
	 * @param array<string,mixed> $element Element.
	 * @return string
	 */
	private static function render_element($element) {
		$id    = sanitize_html_class($element['id'] ?? '');
		$type  = sanitize_key($element['type'] ?? 'text');
		$class = 'am-vb-node am-vb-node--' . $id . ' am-vb-node-type--' . $type;

		if ('text' === $type) {
			$tag = in_array(($element['tag'] ?? ''), array('h1', 'h2', 'h3', 'h4', 'p', 'div'), true)
				? $element['tag']
				: 'p';
			return '<' . $tag . ' class="' . esc_attr($class) . '">' . wp_kses_post($element['content'] ?? '') . '</' . $tag . '>';
		}
		if ('button' === $type) {
			$target = !empty($element['newWindow']) ? ' target="_blank" rel="noopener"' : '';
			return '<a class="' . esc_attr($class) . '" href="' . esc_url($element['href'] ?? '#') . '"' . $target . '>'
				. esc_html($element['label'] ?? 'Button') . '</a>';
		}
		if ('image' === $type) {
			if (empty($element['src'])) {
				return '<div class="' . esc_attr($class . ' am-vb-image-placeholder') . '"><span>No image selected</span></div>';
			}
			return '<img class="' . esc_attr($class) . '" src="' . esc_url($element['src']) . '" alt="' . esc_attr($element['alt'] ?? '') . '">';
		}
		if ('shape' === $type) {
			return '<span aria-hidden="true" class="' . esc_attr($class . ' am-vb-shape--' . sanitize_html_class($element['shape'] ?? 'rectangle')) . '"></span>';
		}

		return '';
	}

	/**
	 * Generate scoped responsive CSS from the same style model used by the
	 * editor canvas.
	 *
	 * @param array<string,mixed> $document Document.
	 * @return string
	 */
	private static function responsive_css($document) {
		return '<style id="am-vb-responsive-css">' . self::responsive_rules($document) . '</style>';
	}

	/**
	 * @param array<string,mixed> $document Document.
	 * @return string
	 */
	private static function responsive_rules($document) {
		$desktop = '';
		$tablet  = '';
		$mobile  = '';
		foreach ((array) ($document['sections'] ?? array()) as $section) {
			$id       = sanitize_html_class($section['id'] ?? '');
			$selector = '.am-vb-section--' . $id;
			$settings = (array) ($section['settings'] ?? array());
			$desktop .= self::rule($selector, (array) ($settings['desktop'] ?? array()), true);
			$tablet  .= self::rule($selector, (array) ($settings['tablet'] ?? array()), true);
			$mobile  .= self::rule($selector, (array) ($settings['mobile'] ?? array()), true);
			foreach ((array) ($section['elements'] ?? array()) as $element) {
				$element_selector = '.am-vb-node--' . sanitize_html_class($element['id'] ?? '');
				$styles           = (array) ($element['styles'] ?? array());
				$desktop         .= self::rule($element_selector, (array) ($styles['desktop'] ?? array()), false);
				$tablet          .= self::rule($element_selector, (array) ($styles['tablet'] ?? array()), false);
				$mobile          .= self::rule($element_selector, (array) ($styles['mobile'] ?? array()), false);
			}
		}

		return $desktop
			. '@media(max-width:900px){' . $tablet . '}'
			. '@media(max-width:600px){' . $mobile . '}'
			;
	}

	/**
	 * @param string              $selector Selector.
	 * @param array<string,mixed> $styles   Style values.
	 * @param bool                $section  Whether this is a section rule.
	 * @return string
	 */
	private static function rule($selector, $styles, $section) {
		$map = array(
			'backgroundcolor' => 'background-color',
			'backgroundimage' => 'background-image',
			'backgroundsize'  => 'background-size',
			'backgroundposition' => 'background-position',
			'color'           => 'color',
			'fontsize'        => 'font-size',
			'fontweight'      => 'font-weight',
			'lineheight'      => 'line-height',
			'letterspacing'   => 'letter-spacing',
			'textalign'       => 'text-align',
			'textdecoration'  => 'text-decoration',
			'width'           => 'width',
			'height'          => 'height',
			'minheight'       => 'min-height',
			'padding'         => 'padding',
			'margin'          => 'margin',
			'gap'             => 'gap',
			'borderadius'     => 'border-radius',
			'bordercolor'     => 'border-color',
			'borderwidth'     => 'border-width',
			'borderstyle'     => 'border-style',
			'boxshadow'       => 'box-shadow',
			'opacity'         => 'opacity',
			'objectfit'       => 'object-fit',
			'objectposition'  => 'object-position',
			'overflow'        => 'overflow',
			'zindex'          => 'z-index',
		);
		$declarations = '';
		foreach ($styles as $key => $value) {
			$key = strtolower((string) $key);
			if (isset($map[$key]) && '' !== (string) $value) {
				$css_value = self::css_value((string) $value);
				if ('backgroundimage' === $key && 'none' !== $css_value && false === strpos($css_value, 'url(')) {
					$css_value = 'url("' . self::css_value(esc_url_raw((string) $value), true) . '")';
				}
				if ('' !== $css_value) {
					$declarations .= $map[$key] . ':' . $css_value . ';';
				}
			}
		}
		if (!$section && isset($styles['positionX'])) {
			$declarations .= 'left:' . self::css_value((string) $styles['positionX']) . ';';
		}
		if (!$section && isset($styles['positionY'])) {
			$declarations .= 'top:' . self::css_value((string) $styles['positionY']) . ';';
		}
		if (!$section && isset($styles['rotate'])) {
			$declarations .= 'transform:rotate(' . self::css_value((string) $styles['rotate']) . ');';
		}

		return $declarations ? $selector . '{' . $declarations . '}' : '';
	}

	/**
	 * Allow the small CSS value grammar supported by the prototype while
	 * preventing a stored value from escaping its declaration or style tag.
	 *
	 * @param string $value     Raw CSS value.
	 * @param bool   $allow_url Whether URL-safe colon/query characters may remain.
	 * @return string
	 */
	private static function css_value($value, $allow_url = false) {
		$value = wp_strip_all_tags($value);
		if (preg_match('/(?:expression\s*\(|javascript\s*:|data\s*:\s*text\/html|@import|behavior\s*:)/i', $value)) {
			return '';
		}
		$value = str_replace(array('{', '}', ';', '<', '>', '\\'), '', $value);
		if (!$allow_url) {
			$value = str_replace(array('"', "'"), '', $value);
		}

		return trim($value);
	}
}
