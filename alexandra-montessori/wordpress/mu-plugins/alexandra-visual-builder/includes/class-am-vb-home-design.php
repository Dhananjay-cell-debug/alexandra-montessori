<?php
/**
 * Local-only design controls for the real React Home page.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_VB_Home_Design {
	const OPTION = 'am_vb_home_design';
	const SCHEMA_VERSION = 3;

	/**
	 * Approved baseline values. These mirror the existing React/Tailwind design,
	 * so enabling the controls never changes the page by itself.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		$model = array(
			'version'  => 3,
			'sections' => array(
				'home-hero' => array(
					'visible' => true,
					'tint'    => 14,
					'texture' => 35,
					'desktop' => array('height' => 540),
					'tablet'  => array('height' => 460),
					'mobile'  => array('height' => 380),
				),
				'home-feature-links' => array(
					'visible'         => true,
					'backgroundColor' => '#e9f1e6',
					'desktop' => array('paddingY' => 56, 'gap' => 32, 'contentWidth' => 1200, 'mediaSize' => 224),
					'tablet'  => array('paddingY' => 56, 'gap' => 32, 'contentWidth' => 1200, 'mediaSize' => 224),
					'mobile'  => array('paddingY' => 48, 'gap' => 32, 'contentWidth' => 1200, 'mediaSize' => 208),
				),
				'home-benefits' => array(
					'visible'         => true,
					'backgroundColor' => '#ffffff',
					'desktop' => array('paddingY' => 64, 'gap' => 40, 'contentWidth' => 1200, 'textSize' => 18),
					'tablet'  => array('paddingY' => 64, 'gap' => 40, 'contentWidth' => 1200, 'textSize' => 18),
					'mobile'  => array('paddingY' => 48, 'gap' => 32, 'contentWidth' => 1200, 'textSize' => 18),
				),
				'home-about' => array(
					'visible'         => true,
					'backgroundColor' => '#f7faf5',
					'desktop' => array('paddingY' => 80, 'gap' => 40, 'contentWidth' => 1200, 'imageSize' => 448),
					'tablet'  => array('paddingY' => 80, 'gap' => 40, 'contentWidth' => 1200, 'imageSize' => 420),
					'mobile'  => array('paddingY' => 56, 'gap' => 40, 'contentWidth' => 1200, 'imageSize' => 360),
				),
				'home-testimonials' => array(
					'visible' => true,
					'overlay' => 72,
					'desktop' => array('paddingY' => 96, 'gap' => 24, 'contentWidth' => 1200),
					'tablet'  => array('paddingY' => 96, 'gap' => 24, 'contentWidth' => 1200),
					'mobile'  => array('paddingY' => 64, 'gap' => 24, 'contentWidth' => 1200),
				),
				'home-trust' => array(
					'visible'         => true,
					'backgroundColor' => '#ffffff',
					'desktop' => array('paddingY' => 64, 'gap' => 20, 'contentWidth' => 896, 'iconSize' => 28),
					'tablet'  => array('paddingY' => 64, 'gap' => 20, 'contentWidth' => 896, 'iconSize' => 28),
					'mobile'  => array('paddingY' => 48, 'gap' => 24, 'contentWidth' => 416, 'iconSize' => 28),
				),
			),
			'elements'       => array(),
			'collections'    => array(
				'features' => array(),
				'benefits' => array(),
				'trust'    => array(),
			),
			'customSections' => array(),
		);

		$text = array(
			'header-nav-home'       => array('Home navigation label', false),
			'header-nav-nurseries'  => array('Our Nurseries navigation label', false),
			'header-nav-curriculum' => array('Our Curriculum navigation label', false),
			'header-nav-careers'    => array('Careers navigation label', false),
			'header-nav-events'     => array('Events navigation label', false),
			'header-nav-contact'    => array('Contact navigation label', false),
			'header-availability'   => array('Availability button', false),
			'benefit-1-text'        => array('Benefit 1 text', true),
			'benefit-2-text'        => array('Benefit 2 text', true),
			'benefit-3-text'        => array('Benefit 3 text', true),
			'about-heading'         => array('About heading', false),
			'about-paragraph-1'     => array('About paragraph 1', true),
			'about-paragraph-2'     => array('About paragraph 2', true),
			'about-paragraph-3'     => array('About paragraph 3', true),
			'about-milestone-1'     => array('Milestone 1', false),
			'about-milestone-2'     => array('Milestone 2', false),
			'about-milestone-3'     => array('Milestone 3', false),
			'about-upcoming'        => array('Upcoming milestone', false),
			'testimonials-heading'  => array('Testimonials heading', false),
			'footer-logo-brand-line-1' => array('Footer brand name line 1', false),
			'footer-logo-brand-line-2' => array('Footer brand name line 2', false),
			'footer-logo-strapline'  => array('Footer brand strapline', false),
			'footer-tagline'         => array('Footer tagline', true),
			'footer-email'           => array('Footer email', false, true),
			'footer-hours'           => array('Footer opening hours', false),
			'footer-view-more'       => array('Footer expand button', false),
			'footer-view-less'       => array('Footer collapse button', false),
			'footer-quick-links-heading' => array('Footer quick links heading', false),
			'footer-nurseries-heading' => array('Footer nursery contacts heading', false),
			'footer-ofsted-heading'  => array('Footer Ofsted heading', false),
			'footer-copyright'       => array('Footer copyright', false),
			'footer-privacy'         => array('Footer privacy link', false, true),
			'footer-contact'         => array('Footer contact link', false, true),
			'cookie-message'         => array('Cookie message', true),
			'cookie-privacy'         => array('Cookie privacy link', false, true),
			'cookie-settings'        => array('Cookie settings button', false),
			'cookie-reject'          => array('Cookie reject button', false),
			'cookie-accept'          => array('Cookie accept button', false),
		);
		for ($index = 1; $index <= 5; $index++) {
			$text['footer-quick-link-' . $index] = array('Footer quick link ' . $index, false, true);
		}
		foreach (array('hounslow', 'heston', 'hammersmith') as $location) {
			$text['footer-' . $location . '-name'] = array('Footer nursery name', false);
			$text['footer-' . $location . '-address'] = array('Footer nursery address', true);
			$text['footer-' . $location . '-phone'] = array('Footer nursery phone', false, true);
			$text['footer-' . $location . '-ofsted-name'] = array('Footer Ofsted nursery name', false);
			$text['footer-' . $location . '-ofsted-label'] = array('Footer Ofsted label', false);
		}

		for ($index = 1; $index <= 3; $index++) {
			$text['feature-' . $index . '-title'] = array('Feature ' . $index . ' heading', false);
			$text['feature-' . $index . '-cta'] = array('Feature ' . $index . ' link', false, true);
			$text['testimonial-' . $index . '-title'] = array('Testimonial ' . $index . ' title', false);
			$text['testimonial-' . $index . '-quote'] = array('Testimonial ' . $index . ' quote', true);
			$text['testimonial-' . $index . '-name'] = array('Testimonial ' . $index . ' name', false);
			$text['testimonial-' . $index . '-location'] = array('Testimonial ' . $index . ' location', false);
			$model['elements']['testimonial-' . $index . '-card'] = self::frame_defaults('Testimonial ' . $index . ' card');
		}
		$text['testimonials-cta'] = array('Testimonials link', false, true);
		for ($index = 1; $index <= 4; $index++) {
			$text['trust-' . $index . '-label'] = array('Trust mark ' . $index . ' label', false);
		}

		foreach ($text as $key => $definition) {
			$model['elements'][$key] = array(
				'type'      => !empty($definition[1]) ? 'textarea' : 'text',
				'label'     => $definition[0],
				'value'     => '',
				'href'      => '',
				'linkDescription' => '',
				'desktop'   => array('scale' => 100, 'offsetX' => 0, 'offsetY' => 0),
				'tablet'    => array('scale' => 100, 'offsetX' => 0, 'offsetY' => 0),
				'mobile'    => array('scale' => 100, 'offsetX' => 0, 'offsetY' => 0),
			);
		}

		$media = array(
			'header-logo'             => array('Header logo', 'logo'),
			'footer-logo'             => array('Footer logo', 'logo'),
			'footer-ofsted'           => array('Footer Ofsted logo', 'logo'),
			'cookie-banner-frame'     => array('Cookie banner', 'frame'),
			'footer-view-more-button' => array('Footer View more button', 'frame'),
			'footer-view-less-button' => array('Footer View less button', 'frame'),
			'hero-video'              => array('Home hero video', 'video'),
			'hero-poster'             => array('Hero fallback image', 'image'),
			'feature-1-image'         => array('Feature 1 photo', 'image'),
			'feature-2-image'         => array('Feature 2 photo', 'image'),
			'feature-3-image'         => array('Feature 3 photo', 'image'),
			'about-image'             => array('About photo', 'image'),
			'testimonials-background' => array('Testimonials background', 'image'),
			'social-1-icon'           => array('Facebook icon', 'logo'),
			'social-2-icon'           => array('Instagram icon', 'logo'),
			'social-3-icon'           => array('X icon', 'logo'),
			'social-4-icon'           => array('LinkedIn icon', 'logo'),
		);
		for ($index = 1; $index <= 4; $index++) {
			$media['trust-' . $index . '-logo'] = array('Trust mark ' . $index, 'logo');
		}
		for ($index = 1; $index <= 3; $index++) {
			$media['feature-' . $index . '-icon'] = array('Feature ' . $index . ' icon', 'logo');
			$media['benefit-' . $index . '-icon'] = array('Benefit ' . $index . ' icon', 'logo');
			$media['testimonial-' . $index . '-icon'] = array('Testimonial ' . $index . ' quote icon', 'logo');
		}

		foreach ($media as $key => $definition) {
			if ('frame' === $definition[1]) {
				$model['elements'][$key] = self::frame_defaults($definition[0]);
				continue;
			}
			$model['elements'][$key] = array(
				'type'      => $definition[1],
				'label'     => $definition[0],
				'src'       => '',
				'alt'       => '',
				'positionX' => 50,
				'positionY' => 50,
				'desktop'   => array('scale' => 100, 'offsetX' => 0, 'offsetY' => 0),
				'tablet'    => array('scale' => 100, 'offsetX' => 0, 'offsetY' => 0),
				'mobile'    => array('scale' => 100, 'offsetX' => 0, 'offsetY' => 0),
			);
			if (0 === strpos($key, 'social-')) {
				$model['elements'][$key]['href'] = '';
				$model['elements'][$key]['linkDescription'] = '';
			}
		}

		return $model;
	}

	/**
	 * Return a complete, defensive design model.
	 *
	 * @return array<string,mixed>
	 */
	public static function get() {
		$stored = get_option(self::OPTION, array());

		return self::sanitize(is_array($stored) ? $stored : array());
	}

	/**
	 * Persist one sanitized Home design.
	 *
	 * @param array<string,mixed> $input Design.
	 * @return array<string,mixed>
	 */
	public static function save($input) {
		$design = self::sanitize(is_array($input) ? $input : array());
		update_option(self::OPTION, $design, false);
		$page = AM_VB_Document::find(AM_VB_Document::HOME_SLUG);
		if ($page) {
			AM_VB_Audit::log('home_design_saved', $page->ID, array('schema' => self::SCHEMA_VERSION));
		}
		return $design;
	}

	/**
	 * Register the authenticated editor endpoint.
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			AM_VB_REST::NAMESPACE,
			'/home-design',
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
					'args'                => array(
						'design' => array('required' => true, 'type' => 'object'),
						'sessionTitle' => array('required' => false, 'type' => 'string'),
					),
				),
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
	 * @return WP_REST_Response
	 */
	public static function rest_get() {
		return rest_ensure_response(self::payload());
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_save($request) {
		$design  = self::save((array) $request->get_param('design'));
		$payload = self::payload($design);
		$title   = trim((string) $request->get_param('sessionTitle'));
		if ($title) {
			$session = AM_VB_Saved_Sessions::capture('home-poc', $title, 'home', $design);
			if (is_wp_error($session)) {
				return $session;
			}
			$payload['savedSession'] = $session;
		}

		return rest_ensure_response($payload);
	}

	/**
	 * Make the saved model available to the same React bundle used in the
	 * builder iframe and on the normal Localhost Home page.
	 *
	 * @return void
	 */
	public static function localize_frontend() {
		if (!wp_script_is('alexandra-app', 'enqueued')) {
			return;
		}
		$preview = AM_VB_Saved_Sessions::frontend_snapshot('home-poc', 'home');
		// Resolve media through the attachment ID at render time, so a design
		// written on another domain still shows its pictures here.
		$design = AM_VB_Media::resolve_design(is_array($preview) ? $preview : self::get());
		wp_localize_script('alexandra-app', 'amHomeDesign', $design);
	}

	/**
	 * @param array<string,mixed>|null $design Design.
	 * @return array<string,mixed>
	 */
	private static function payload($design = null) {
		return array(
			'design'   => is_array($design) ? $design : self::get(),
			'defaults' => self::defaults(),
			'updated'  => current_time('c'),
		);
	}

	/**
	 * Allow only known sections, colours and bounded numeric controls.
	 *
	 * @param array<string,mixed> $input Raw model.
	 * @return array<string,mixed>
	 */
	private static function sanitize($input) {
		$defaults = self::defaults();
		$output   = $defaults;
		$sections = isset($input['sections']) && is_array($input['sections']) ? $input['sections'] : array();
		$ranges   = array(
			'height'       => array(220, 900),
			'paddingY'     => array(0, 180),
			'gap'          => array(0, 96),
			'contentWidth' => array(360, 1440),
			'mediaSize'    => array(120, 360),
			'textSize'     => array(13, 28),
			'imageSize'    => array(220, 640),
			'iconSize'     => array(16, 64),
		);

		foreach ($defaults['sections'] as $section_id => $section_defaults) {
			$source = isset($sections[$section_id]) && is_array($sections[$section_id]) ? $sections[$section_id] : array();
			$output['sections'][$section_id]['visible'] = !isset($source['visible']) || (bool) $source['visible'];

			if (isset($section_defaults['backgroundColor'])) {
				$colour = isset($source['backgroundColor']) ? sanitize_hex_color($source['backgroundColor']) : '';
				$output['sections'][$section_id]['backgroundColor'] = $colour ?: $section_defaults['backgroundColor'];
			}
			foreach (array('tint', 'texture', 'overlay') as $percent_key) {
				if (!isset($section_defaults[$percent_key])) {
					continue;
				}
				$value = isset($source[$percent_key]) ? (int) $source[$percent_key] : (int) $section_defaults[$percent_key];
				$output['sections'][$section_id][$percent_key] = max(0, min('tint' === $percent_key ? 80 : 100, $value));
			}

			foreach (array('desktop', 'tablet', 'mobile') as $device) {
				$device_source = isset($source[$device]) && is_array($source[$device]) ? $source[$device] : array();
				foreach ($section_defaults[$device] as $key => $fallback) {
					if (!isset($ranges[$key])) {
						continue;
					}
					$value = isset($device_source[$key]) ? (int) $device_source[$key] : (int) $fallback;
					$output['sections'][$section_id][$device][$key] = max($ranges[$key][0], min($ranges[$key][1], $value));
				}
				$output['sections'][$section_id][$device] = AM_VB_Style_Schema::sanitize_section_device(
					$section_id,
					$device_source,
					$output['sections'][$section_id][$device]
				);
			}

			// Corner radius, border, shadow, hover and scroll animation are new
			// section surface. The clamps above keep their historical ranges for
			// the established keys; the schema owns everything after that.
			$output['sections'][$section_id] = AM_VB_Style_Schema::sanitize_section(
				$section_id,
				$source,
				$output['sections'][$section_id]
			);
		}

		$output['version'] = 3;
		$element_source    = isset($input['elements']) && is_array($input['elements']) ? $input['elements'] : array();
		$element_defaults  = $defaults['elements'];
		$collections       = isset($input['collections']) && is_array($input['collections']) ? $input['collections'] : array();
		$allowed_icons     = array('Trees', 'GraduationCap', 'CalendarHeart', 'HandHeart', 'Sprout', 'Users', 'ShieldCheck', 'Heart', 'Star', 'Image');

		foreach (array('features', 'benefits', 'trust') as $collection_name) {
			$items = isset($collections[$collection_name]) && is_array($collections[$collection_name])
				? array_slice($collections[$collection_name], 0, 12)
				: array();
			foreach ($items as $item) {
				if (!is_array($item)) {
					continue;
				}
				$id = isset($item['id']) ? sanitize_key($item['id']) : '';
				if (!$id || strlen($id) > 48) {
					continue;
				}
				$icon = isset($item['icon']) && in_array($item['icon'], $allowed_icons, true)
					? $item['icon']
					: ('benefits' === $collection_name ? 'Sprout' : ('trust' === $collection_name ? 'ShieldCheck' : 'Image'));
				$output['collections'][$collection_name][] = array('id' => $id, 'icon' => $icon);
				if ('features' === $collection_name) {
					$element_defaults['feature-extra-' . $id . '-title'] = array('type' => 'text', 'label' => 'Extra feature heading', 'value' => 'New feature', 'href' => '', 'linkDescription' => '');
					$element_defaults['feature-extra-' . $id . '-cta'] = array('type' => 'text', 'label' => 'Extra feature link', 'value' => 'View more', 'href' => '#', 'linkDescription' => '');
					$element_defaults['feature-extra-' . $id . '-image'] = self::media_defaults('Extra feature photo', 'image');
					$element_defaults['feature-extra-' . $id . '-icon'] = self::media_defaults('Extra feature icon', 'logo');
				} elseif ('benefits' === $collection_name) {
					$element_defaults['benefit-extra-' . $id . '-text'] = array('type' => 'textarea', 'label' => 'Extra benefit text', 'value' => 'Add your benefit text', 'href' => '', 'linkDescription' => '');
					$element_defaults['benefit-extra-' . $id . '-icon'] = self::media_defaults('Extra benefit icon', 'logo');
				} else {
					$element_defaults['trust-extra-' . $id . '-label'] = array('type' => 'text', 'label' => 'Extra trust label', 'value' => 'Accreditation', 'href' => '', 'linkDescription' => '');
					$element_defaults['trust-extra-' . $id . '-logo'] = self::media_defaults('Extra trust logo', 'logo');
				}
			}
		}

		$custom_sections = isset($input['customSections']) && is_array($input['customSections'])
			? array_slice($input['customSections'], 0, 20)
			: array();
		$allowed_after    = array_keys($defaults['sections']);
		foreach ($custom_sections as $section) {
			if (!is_array($section)) {
				continue;
			}
			$section_id = isset($section['id']) ? sanitize_key($section['id']) : '';
			if (!$section_id || strlen($section_id) > 48) {
				continue;
			}
			$after = isset($section['after']) && in_array($section['after'], $allowed_after, true)
				? $section['after']
				: 'home-trust';
			$colour = isset($section['backgroundColor']) ? sanitize_hex_color($section['backgroundColor']) : '';
			$clean_section = array(
				'id'              => $section_id,
				'name'            => sanitize_text_field(isset($section['name']) ? $section['name'] : 'Custom section'),
				'visible'         => !isset($section['visible']) || (bool) $section['visible'],
				'after'           => $after,
				'backgroundColor' => $colour ?: '#f7faf5',
				'desktop'         => array('height' => self::bounded($section, 'desktop', 'height', 420, 180, 1000)),
				'tablet'          => array('height' => self::bounded($section, 'tablet', 'height', 420, 180, 1000)),
				'mobile'          => array('height' => self::bounded($section, 'mobile', 'height', 520, 180, 1200)),
				'items'           => array(),
			);
			$items = isset($section['items']) && is_array($section['items']) ? array_slice($section['items'], 0, 30) : array();
			foreach ($items as $item) {
				if (!is_array($item)) {
					continue;
				}
				$item_id = isset($item['id']) ? sanitize_key($item['id']) : '';
				$type    = isset($item['type']) ? sanitize_key($item['type']) : '';
				if (!$item_id || strlen($item_id) > 48 || !in_array($type, array('text', 'image', 'logo', 'button', 'shape', 'tabs'), true)) {
					continue;
				}
				$key = 'custom-' . $section_id . '-' . $item_id;
				$clean_item = array(
					'id'              => $item_id,
					'key'             => $key,
					'type'            => $type,
					'name'            => sanitize_text_field(isset($item['name']) ? $item['name'] : ucfirst($type)),
					'shape'           => in_array(($item['shape'] ?? ''), array('rectangle', 'circle', 'pill', 'line'), true) ? $item['shape'] : 'rectangle',
					'backgroundColor' => sanitize_hex_color($item['backgroundColor'] ?? '') ?: ('button' === $type ? '#345b40' : '#a9c6a2'),
					'color'           => sanitize_hex_color($item['color'] ?? '') ?: ('button' === $type ? '#ffffff' : '#20372a'),
					'borderRadius'    => max(0, min(200, isset($item['borderRadius']) ? (int) $item['borderRadius'] : ('image' === $type || 'logo' === $type ? 20 : 0))),
				);
				foreach (array('desktop', 'tablet', 'mobile') as $device) {
					$fallback_width  = in_array($type, array('image', 'logo'), true) ? 260 : ('shape' === $type ? 180 : 360);
					$fallback_height = 'image' === $type ? 240 : ('logo' === $type ? 120 : ('shape' === $type ? 120 : ('button' === $type ? 56 : 100)));
					$clean_item[$device] = array(
						'x'        => self::bounded($item, $device, 'x', 40, 0, 1400),
						'y'        => self::bounded($item, $device, 'y', 40, 0, 1100),
						'width'    => self::bounded($item, $device, 'width', $fallback_width, 30, 1400),
						'height'   => self::bounded($item, $device, 'height', $fallback_height, 20, 1000),
						'fontSize' => self::bounded($item, $device, 'fontSize', 'text' === $type ? 28 : 16, 10, 120),
					);
				}
				$clean_section['items'][] = $clean_item;
				if (in_array($type, array('image', 'logo'), true)) {
					$element_defaults[$key] = self::media_defaults($clean_item['name'], $type);
				} elseif ('shape' === $type) {
					$element_defaults[$key] = array('type' => 'shape', 'label' => $clean_item['name'], 'value' => '');
				} else {
					$element_defaults[$key] = array(
						'type'  => in_array($type, array('text', 'tabs'), true) ? 'textarea' : 'text',
						'label' => $clean_item['name'],
						'value' => 'tabs' === $type ? "Overview\nLearning\nCare" : ('button' === $type ? 'Learn more' : 'Edit this text'),
						'href'  => 'button' === $type ? '#' : '',
						'linkDescription' => '',
					);
				}
			}
			$output['customSections'][] = $clean_section;
		}

		foreach ($element_defaults as $key => $element_defaults) {
			$source = isset($element_source[$key]) && is_array($element_source[$key]) ? $element_source[$key] : array();
			$output['elements'][$key] = $element_defaults;
			if ('frame' === $element_defaults['type']) {
				foreach (array('desktop', 'tablet', 'mobile') as $device) {
					$device_source = isset($source[$device]) && is_array($source[$device]) ? $source[$device] : array();
					$output['elements'][$key][$device]['scale'] = max(40, min(240, isset($device_source['scale']) ? (int) $device_source['scale'] : 100));
					$output['elements'][$key][$device]['offsetX'] = max(-1200, min(1200, isset($device_source['offsetX']) ? (int) $device_source['offsetX'] : 0));
					$output['elements'][$key][$device]['offsetY'] = max(-1200, min(1200, isset($device_source['offsetY']) ? (int) $device_source['offsetY'] : 0));
					$output['elements'][$key][$device] = AM_VB_Style_Schema::sanitize_device('frame', $device_source, $output['elements'][$key][$device]);
				}
				$output['elements'][$key] = array_merge($output['elements'][$key], AM_VB_Style_Schema::sanitize_flat('frame', $source));
				continue;
			}

			if ('text' === $element_defaults['type'] || 'textarea' === $element_defaults['type']) {
				$value = isset($source['value']) ? (string) $source['value'] : '';
				$output['elements'][$key]['value'] = sanitize_textarea_field($value);
				$output['elements'][$key]['href'] = isset($source['href']) ? self::safe_href((string) $source['href']) : '';
				$output['elements'][$key]['linkDescription'] = isset($source['linkDescription']) ? sanitize_text_field((string) $source['linkDescription']) : '';
				foreach (array('desktop', 'tablet', 'mobile') as $device) {
					$device_source = isset($source[$device]) && is_array($source[$device]) ? $source[$device] : array();
					$output['elements'][$key][$device]['scale'] = max(40, min(240, isset($device_source['scale']) ? (int) $device_source['scale'] : 100));
					$output['elements'][$key][$device]['offsetX'] = max(-1200, min(1200, isset($device_source['offsetX']) ? (int) $device_source['offsetX'] : 0));
					$output['elements'][$key][$device]['offsetY'] = max(-1200, min(1200, isset($device_source['offsetY']) ? (int) $device_source['offsetY'] : 0));
					$output['elements'][$key][$device] = AM_VB_Style_Schema::sanitize_device($element_defaults['type'], $device_source, $output['elements'][$key][$device]);
				}
				$output['elements'][$key] = array_merge($output['elements'][$key], AM_VB_Style_Schema::sanitize_flat($element_defaults['type'], $source));
				continue;
			}

			// A shape layer used to fall straight through, which meant every schema
			// property a client set on one was discarded on save. It carries no
			// text or media of its own, but it is still a box: rotate, border,
			// shadow, hover and the rest all apply.
			if ('shape' === $element_defaults['type']) {
				foreach (array('desktop', 'tablet', 'mobile') as $device) {
					$device_source = isset($source[$device]) && is_array($source[$device]) ? $source[$device] : array();
					$existing = isset($output['elements'][$key][$device]) && is_array($output['elements'][$key][$device])
						? $output['elements'][$key][$device]
						: array();
					$output['elements'][$key][$device] = AM_VB_Style_Schema::sanitize_device('shape', $device_source, $existing);
				}
				$output['elements'][$key] = array_merge($output['elements'][$key], AM_VB_Style_Schema::sanitize_flat('shape', $source));
				continue;
			}

			// Normalise to this site and keep the attachment ID beside the URL,
			// so the picture still resolves if the design is ever moved to
			// another domain. See AM_VB_Media.
			$media = AM_VB_Media::sanitize_pair($source);
			$output['elements'][$key]['src'] = $media['src'];
			$output['elements'][$key]['srcId'] = $media['srcId'];
			$output['elements'][$key]['alt'] = isset($source['alt']) ? sanitize_text_field((string) $source['alt']) : '';
			$output['elements'][$key]['positionX'] = max(0, min(100, isset($source['positionX']) ? (int) $source['positionX'] : 50));
			$output['elements'][$key]['positionY'] = max(0, min(100, isset($source['positionY']) ? (int) $source['positionY'] : 50));
			if (array_key_exists('href', $element_defaults)) {
				$output['elements'][$key]['href'] = isset($source['href']) ? self::safe_href((string) $source['href']) : '';
				$output['elements'][$key]['linkDescription'] = isset($source['linkDescription']) ? sanitize_text_field((string) $source['linkDescription']) : '';
			}
			foreach (array('desktop', 'tablet', 'mobile') as $device) {
				$device_source = isset($source[$device]) && is_array($source[$device]) ? $source[$device] : array();
				$output['elements'][$key][$device]['scale'] = max(50, min(200, isset($device_source['scale']) ? (int) $device_source['scale'] : 100));
				$output['elements'][$key][$device]['offsetX'] = max(-1200, min(1200, isset($device_source['offsetX']) ? (int) $device_source['offsetX'] : 0));
				$output['elements'][$key][$device]['offsetY'] = max(-1200, min(1200, isset($device_source['offsetY']) ? (int) $device_source['offsetY'] : 0));
				$output['elements'][$key][$device] = AM_VB_Style_Schema::sanitize_device($element_defaults['type'], $device_source, $output['elements'][$key][$device]);
			}
			$output['elements'][$key] = array_merge($output['elements'][$key], AM_VB_Style_Schema::sanitize_flat($element_defaults['type'], $source));
		}

		return $output;
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function media_defaults($label, $type) {
		return array(
			'type'      => $type,
			'label'     => $label,
			'src'       => '',
			'alt'       => '',
			'positionX' => 50,
			'positionY' => 50,
			'desktop'   => array('scale' => 100, 'offsetX' => 0, 'offsetY' => 0),
			'tablet'    => array('scale' => 100, 'offsetX' => 0, 'offsetY' => 0),
			'mobile'    => array('scale' => 100, 'offsetX' => 0, 'offsetY' => 0),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function frame_defaults($label) {
		return array(
			'type'    => 'frame',
			'label'   => $label,
			'desktop' => array('scale' => 100, 'offsetX' => 0, 'offsetY' => 0),
			'tablet'  => array('scale' => 100, 'offsetX' => 0, 'offsetY' => 0),
			'mobile'  => array('scale' => 100, 'offsetX' => 0, 'offsetY' => 0),
		);
	}

	/**
	 * @param array<string,mixed> $source Section or item.
	 */
	private static function bounded($source, $device, $key, $fallback, $minimum, $maximum) {
		$value = isset($source[$device]) && is_array($source[$device]) && isset($source[$device][$key])
			? (int) $source[$device][$key]
			: (int) $fallback;

		return max($minimum, min($maximum, $value));
	}

	/**
	 * Restrict a link to the handful of schemes a nursery website has any use
	 * for. Plain esc_url_raw() also permits telnet:, svn:, feed: and friends -
	 * harmless in a browser, but nothing an editor should be able to publish.
	 * Kept identical to AM_VB_Site_Design::safe_href so both models agree.
	 *
	 * @param string $value Raw href.
	 * @return string
	 */
	private static function safe_href($value) {
		$value = trim((string) $value);
		if ('' === $value || '#' === $value) {
			return $value;
		}
		// A site-relative path is ours; strip control characters and keep it.
		if (0 === strpos($value, '/') && 0 !== strpos($value, '//')) {
			return preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
		}

		return esc_url_raw($value, array('http', 'https', 'mailto', 'tel'));
	}
}
