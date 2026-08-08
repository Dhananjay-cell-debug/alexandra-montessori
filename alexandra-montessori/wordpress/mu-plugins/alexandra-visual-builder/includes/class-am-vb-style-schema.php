<?php
/**
 * Single source of truth for every visual property the builder can edit.
 *
 * Four separate layers used to hardcode the same three properties:
 *   1. the inspector panel            (assets/editor.js)
 *   2. the canvas applier             (assets/editor.js)
 *   3. the save whitelist             (class-am-vb-home-design.php)
 *   4. the public front-end applier   (assets/site-runtime.js)
 *
 * Any property missing from any one of those layers is silently discarded, so
 * they are now all generated from the definitions below. Adding a property here
 * makes it editable, saveable and renderable everywhere at once - including on
 * pages other than Home, which consume the same schema.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_VB_Style_Schema {
	const VERSION = 1;

	/**
	 * Panel organisation. The inspector renders at most these three tabs, and
	 * only when the selected element actually owns properties inside them.
	 *
	 * `open` marks the one group per tab that is expanded by default; every
	 * other group stays collapsed behind its summary so the panel reads calm.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function groups() {
		return array(
			'copy'       => array('tab' => 'content', 'label' => 'Text',       'icon' => 'Aa', 'open' => true),
			'media'      => array('tab' => 'content', 'label' => 'Media',      'icon' => '▣',  'open' => true),
			'link'       => array('tab' => 'content', 'label' => 'Link',       'icon' => '↗',  'open' => false),
			'typography' => array('tab' => 'style',   'label' => 'Text style', 'icon' => 'Aa', 'open' => true),
			'fill'       => array('tab' => 'style',   'label' => 'Box',        'icon' => '◻',  'open' => false),
			'effects'    => array('tab' => 'style',   'label' => 'Effects',    'icon' => '✧',  'open' => false),
			'position'   => array('tab' => 'layout',  'label' => 'Position',   'icon' => '⤢',  'open' => true),
			'spacing'    => array('tab' => 'layout',  'label' => 'Spacing',    'icon' => '⇔',  'open' => false),
			'visibility' => array('tab' => 'layout',  'label' => 'Visibility', 'icon' => '👁', 'open' => false),
		);
	}

	/**
	 * Universal base toolkit - present on every element type without exception.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function base_props() {
		return array(
			// -- Position -----------------------------------------------------
			// A section is a full-width band in the page flow. Nudging, scaling or
			// rotating one does not move it "on the page", it tears a hole in the
			// layout above and below - so bands get the box and effect toolkit
			// below but never the free-placement one.
			'offsetX' => array(
				'label' => 'Move left / right', 'group' => 'position', 'control' => 'range',
				'min' => -1200, 'max' => 1200, 'step' => 1, 'default' => 0, 'unit' => 'px',
				'device' => true,
				'notTypes' => array('section'),
				'var' => array('element' => '--am-element-offset-x', 'media' => '--am-media-offset-x'),
			),
			'offsetY' => array(
				'label' => 'Move up / down', 'group' => 'position', 'control' => 'range',
				'min' => -1200, 'max' => 1200, 'step' => 1, 'default' => 0, 'unit' => 'px',
				'device' => true,
				'notTypes' => array('section'),
				'var' => array('element' => '--am-element-offset-y', 'media' => '--am-media-offset-y'),
			),
			'scale' => array(
				'label' => 'Size', 'group' => 'position', 'control' => 'range',
				'min' => 40, 'max' => 240, 'step' => 1, 'default' => 100, 'unit' => '%',
				'device' => true, 'divisor' => 100,
				'notTypes' => array('section'),
				'var' => array('element' => '--am-element-scale', 'media' => '--am-media-scale'),
			),
			'rotate' => array(
				'label' => 'Rotate', 'group' => 'position', 'control' => 'range',
				'min' => -180, 'max' => 180, 'step' => 1, 'default' => 0, 'unit' => 'deg',
				'device' => true,
				'notTypes' => array('section'),
				'var' => '--am-vb-rotate',
			),
			// Width and height drive the side resize handles. Zero means "leave
			// the layout alone", which is why they can be dragged from a single
			// edge without the element jumping to a fixed box first. Text
			// reflows onto the next line when its width shrinks; media stretches
			// on that axis. That difference is CSS behaviour, not a special case.
			'width' => array(
				'label' => 'Width', 'group' => 'position', 'control' => 'range',
				'min' => 0, 'max' => 1600, 'step' => 1, 'default' => 0, 'unit' => 'px',
				'zeroLabel' => 'automatic',
				'device' => true,
				// A band is always full-bleed; what a client means by "how wide" is
				// the content column inside it, which is `contentWidth`.
				'notTypes' => array('section'),
				'var' => '--am-vb-width',
			),
			// Height only means something on a box. Forcing a height onto a run
			// of text just parks empty space under it, so text does not get it -
			// and its vertical resize handles are hidden to match.
			'height' => array(
				'label' => 'Height', 'group' => 'position', 'control' => 'range',
				'min' => 0, 'max' => 1600, 'step' => 1, 'default' => 0, 'unit' => 'px',
				'zeroLabel' => 'automatic',
				'device' => true,
				'notTypes' => array('text', 'textarea', 'section'),
				'var' => '--am-vb-height',
			),

			// -- Spacing ------------------------------------------------------
			// Padding inflates a box. On text that reads as a bug, so text gets
			// line height and letter spacing in this group instead - the spacing
			// that actually means something for words.
			'padding' => array(
				'label' => 'Inner spacing', 'group' => 'spacing', 'control' => 'range',
				'min' => 0, 'max' => 160, 'step' => 2, 'default' => 0, 'unit' => 'px',
				'device' => true,
				// A band's own vertical spacing is `paddingY`, which the page
				// stylesheet already understands; two padding controls on one panel
				// would just compete.
				'notTypes' => array('text', 'textarea', 'section'),
				'var' => '--am-vb-padding',
			),

			// -- Box ----------------------------------------------------------
			'background' => array(
				'label' => 'Background fill', 'group' => 'fill', 'control' => 'color',
				'default' => '', 'device' => false,
				// Bands keep their established `backgroundColor` key instead - it is
				// what the React page and every saved design already read.
				'notTypes' => array('section'),
				'var' => '--am-vb-bg',
			),
			'radius' => array(
				'label' => 'Corner radius', 'group' => 'fill', 'control' => 'range',
				'min' => 0, 'max' => 200, 'step' => 1, 'default' => 0, 'unit' => 'px',
				'device' => false,
				'var' => '--am-vb-radius',
			),
			'borderWidth' => array(
				'label' => 'Border width', 'group' => 'fill', 'control' => 'range',
				'min' => 0, 'max' => 16, 'step' => 1, 'default' => 0, 'unit' => 'px',
				'device' => false,
				'var' => '--am-vb-border-width',
			),
			'borderColor' => array(
				'label' => 'Border colour', 'group' => 'fill', 'control' => 'color',
				'default' => '', 'device' => false,
				'var' => '--am-vb-border-color',
			),
			'borderStyle' => array(
				'label' => 'Border style', 'group' => 'fill', 'control' => 'select',
				'options' => array('solid', 'dashed', 'dotted'), 'default' => 'solid',
				'device' => false,
				'var' => '--am-vb-border-style',
			),

			// -- Effects ------------------------------------------------------
			'opacity' => array(
				'label' => 'Opacity', 'group' => 'effects', 'control' => 'range',
				'min' => 0, 'max' => 100, 'step' => 1, 'default' => 100, 'unit' => '%',
				'device' => false, 'divisor' => 100,
				'var' => '--am-vb-opacity',
			),
			'shadow' => array(
				'label' => 'Shadow', 'group' => 'effects', 'control' => 'select',
				'options' => array('none', 'soft', 'medium', 'strong'), 'default' => 'none',
				'device' => false,
				'attr' => 'data-am-vb-shadow',
			),
			'hover' => array(
				'label' => 'Hover effect', 'group' => 'effects', 'control' => 'select',
				'options' => array('none', 'lift', 'zoom', 'glow', 'underline'), 'default' => 'none',
				'device' => false,
				'attr' => 'data-am-vb-hover',
			),
			'animate' => array(
				'label' => 'Scroll animation', 'group' => 'effects', 'control' => 'select',
				'options' => array('none', 'fade', 'rise', 'zoom', 'slide'), 'default' => 'none',
				'device' => false,
				'attr' => 'data-am-vb-animate',
			),

			// -- Visibility ---------------------------------------------------
			'hidden' => array(
				'label' => 'Hide this item', 'group' => 'visibility', 'control' => 'toggle',
				'default' => false, 'device' => true,
				// Bands hide through `visible`, which is a stored contract the server
				// and the React page both honour. Offering both would be two
				// switches for one outcome.
				'notTypes' => array('section'),
				'attr' => 'data-am-vb-hidden',
				'guard' => 'structural',
			),
		);
	}

	/**
	 * Type-specific packs layered on top of the base toolkit. Every element gets
	 * the base; a text element additionally gets typography, an image gets the
	 * media treatment, and so on. Packs are data only - the inspector reads them
	 * from here rather than branching on element type.
	 *
	 * @return array<string,array<string,array<string,mixed>>>
	 */
	public static function packs() {
		return array(
			'text' => array(
				'fontSize' => array(
					'label' => 'Text size', 'group' => 'typography', 'control' => 'range',
					'min' => 0, 'max' => 96, 'step' => 1, 'default' => 0, 'unit' => 'px',
					'zeroLabel' => 'automatic',
					'device' => true, 'var' => '--am-vb-font-size',
				),
				// An empty default is not one of the options, so the browser used
				// to display "300" while the real value was unset - the control
				// lied about the current state. "automatic" is a real option and
				// is never written, because it equals the default.
				'fontWeight' => array(
					'label' => 'Weight', 'group' => 'typography', 'control' => 'select',
					'options' => array('automatic', '300', '400', '500', '600', '700', '800'),
					'default' => 'automatic',
					'device' => false, 'var' => '--am-vb-font-weight',
				),
				// These live under Spacing rather than Text style: for words, the
				// space between lines and between letters *is* the spacing.
				'lineHeight' => array(
					'label' => 'Space between lines', 'group' => 'spacing', 'control' => 'range',
					'min' => 0, 'max' => 220, 'step' => 5, 'default' => 0, 'unit' => '%',
					'zeroLabel' => 'automatic',
					'device' => false, 'divisor' => 100, 'var' => '--am-vb-line-height',
				),
				'letterSpacing' => array(
					'label' => 'Space between letters', 'group' => 'spacing', 'control' => 'range',
					'min' => -50, 'max' => 200, 'step' => 5, 'default' => 0, 'unit' => 'px',
					'zeroLabel' => 'automatic',
					'device' => false, 'divisor' => 100, 'var' => '--am-vb-letter-spacing',
				),
				'color' => array(
					'label' => 'Text colour', 'group' => 'typography', 'control' => 'color',
					'default' => '', 'device' => false, 'var' => '--am-vb-color',
				),
				'textAlign' => array(
					'label' => 'Alignment', 'group' => 'typography', 'control' => 'select',
					'options' => array('inherit', 'left', 'center', 'right'), 'default' => 'inherit',
					'device' => true, 'var' => '--am-vb-text-align',
				),
				'textTransform' => array(
					'label' => 'Capitalisation', 'group' => 'typography', 'control' => 'select',
					'options' => array('none', 'uppercase', 'capitalize'), 'default' => 'none',
					'device' => false, 'var' => '--am-vb-text-transform',
				),
			),
			// Sections (page bands). Home used to be the only page with real section
			// controls, because `editor.js` carried a chain of
			// `if (region.id === 'home-hero')` blocks. Those six blocks are declared
			// here instead, so Curriculum, Fees, Careers and the rest render the
			// same panel from the same data.
			//
			// `applier => 'legacy'` marks the properties whose CSS variable is
			// written by the existing section applier rather than by the shared
			// applyToNode: several of them target an *inner* node (the content
			// column, the flex container) rather than the band itself, which a
			// single-node applier cannot express. They still live here because this
			// file must stay the one place that decides a section's controls,
			// ranges and defaults.
			'section' => array(
				'visible' => array(
					'label' => 'Show this section', 'group' => 'visibility', 'control' => 'toggle',
					'default' => true, 'device' => false,
					'applier' => 'legacy', 'guard' => 'structural', 'invert' => true,
				),
				'backgroundColor' => array(
					'label' => 'Background colour', 'group' => 'fill', 'control' => 'color',
					'default' => '', 'device' => false,
					'applier' => 'legacy',
				),
				// The hero is a fixed-height banner: index.css forces
				// `padding-block: 0` on `.am-home-hero`, and the markup carries
				// neither `.am-home-content` nor `.am-home-grid`. All three controls
				// would therefore move nothing at all on that one band, which is why
				// the old hardcoded panel quietly swapped them for height/tint/
				// texture. Same outcome, declared instead of branched.
				'paddingY' => array(
					'label' => 'Vertical spacing', 'group' => 'spacing', 'control' => 'range',
					'min' => 0, 'max' => 200, 'step' => 4, 'default' => 0, 'unit' => 'px',
					'zeroLabel' => 'automatic',
					'device' => true, 'applier' => 'legacy',
					'notSections' => array('home-hero'),
				),
				'gap' => array(
					'label' => 'Internal spacing', 'group' => 'spacing', 'control' => 'range',
					'min' => 0, 'max' => 120, 'step' => 2, 'default' => 0, 'unit' => 'px',
					'zeroLabel' => 'automatic',
					'device' => true, 'applier' => 'legacy',
					'notSections' => array('home-hero'),
				),
				'contentWidth' => array(
					'label' => 'Content width', 'group' => 'position', 'control' => 'range',
					'min' => 0, 'max' => 1600, 'step' => 20, 'default' => 0, 'unit' => 'px',
					'zeroLabel' => 'automatic',
					'device' => true, 'applier' => 'legacy',
					'notSections' => array('home-hero'),
				),

				// Region-gated. These drive CSS variables that only exist in the
				// markup of one particular band, so offering them anywhere else
				// would be an inert control - the thing v0.9.5 ruled out.
				'height' => array(
					'label' => 'Section height', 'group' => 'position', 'control' => 'range',
					'min' => 220, 'max' => 900, 'step' => 10, 'default' => 540, 'unit' => 'px',
					'device' => true, 'applier' => 'legacy',
					'sections' => array('home-hero'),
				),
				'tint' => array(
					'label' => 'Green tint', 'group' => 'effects', 'control' => 'range',
					'min' => 0, 'max' => 80, 'step' => 1, 'default' => 14, 'unit' => '%',
					'device' => false, 'applier' => 'legacy',
					'sections' => array('home-hero'),
				),
				'texture' => array(
					'label' => 'Texture strength', 'group' => 'effects', 'control' => 'range',
					'min' => 0, 'max' => 100, 'step' => 1, 'default' => 35, 'unit' => '%',
					'device' => false, 'applier' => 'legacy',
					'sections' => array('home-hero'),
				),
				'overlay' => array(
					'label' => 'Photo overlay', 'group' => 'effects', 'control' => 'range',
					'min' => 30, 'max' => 100, 'step' => 1, 'default' => 72, 'unit' => '%',
					'device' => false, 'applier' => 'legacy',
					'sections' => array('home-testimonials'),
				),
				'mediaSize' => array(
					'label' => 'Image size', 'group' => 'position', 'control' => 'range',
					'min' => 120, 'max' => 360, 'step' => 2, 'default' => 224, 'unit' => 'px',
					'device' => true, 'applier' => 'legacy',
					'sections' => array('home-feature-links'),
				),
				'textSize' => array(
					'label' => 'Copy size', 'group' => 'position', 'control' => 'range',
					'min' => 13, 'max' => 28, 'step' => 1, 'default' => 18, 'unit' => 'px',
					'device' => true, 'applier' => 'legacy',
					'sections' => array('home-benefits'),
				),
				'imageSize' => array(
					'label' => 'Image size', 'group' => 'position', 'control' => 'range',
					'min' => 220, 'max' => 640, 'step' => 4, 'default' => 448, 'unit' => 'px',
					'device' => true, 'applier' => 'legacy',
					'sections' => array('home-about'),
				),
				'iconSize' => array(
					'label' => 'Icon size', 'group' => 'position', 'control' => 'range',
					'min' => 16, 'max' => 64, 'step' => 1, 'default' => 28, 'unit' => 'px',
					'device' => true, 'applier' => 'legacy',
					'sections' => array('home-trust'),
				),
			),
			'media' => array(
				'objectFit' => array(
					'label' => 'Fill or fit', 'group' => 'media', 'control' => 'select',
					'options' => array('cover', 'contain'), 'default' => 'cover',
					'device' => false, 'var' => '--am-vb-object-fit',
				),
				'aspectRatio' => array(
					'label' => 'Crop shape', 'group' => 'media', 'control' => 'select',
					'options' => array('original', '1/1', '4/3', '3/2', '16/9', '3/4'), 'default' => 'original',
					'device' => false, 'var' => '--am-vb-aspect',
				),
				'maskShape' => array(
					'label' => 'Mask', 'group' => 'media', 'control' => 'select',
					'options' => array('none', 'circle', 'arch', 'squircle'), 'default' => 'none',
					'device' => false, 'attr' => 'data-am-vb-mask',
				),
				'brightness' => array(
					'label' => 'Brightness', 'group' => 'effects', 'control' => 'range',
					'min' => 50, 'max' => 150, 'step' => 1, 'default' => 100, 'unit' => '%',
					'device' => false, 'divisor' => 100, 'var' => '--am-vb-brightness',
				),
				'saturate' => array(
					'label' => 'Colour intensity', 'group' => 'effects', 'control' => 'range',
					'min' => 0, 'max' => 200, 'step' => 1, 'default' => 100, 'unit' => '%',
					'device' => false, 'divisor' => 100, 'var' => '--am-vb-saturate',
				),
				'tintColor' => array(
					'label' => 'Tint', 'group' => 'effects', 'control' => 'color',
					'default' => '', 'device' => false, 'var' => '--am-vb-tint',
				),
				'tintOpacity' => array(
					'label' => 'Tint strength', 'group' => 'effects', 'control' => 'range',
					'min' => 0, 'max' => 90, 'step' => 5, 'default' => 0, 'unit' => '%',
					'device' => false, 'divisor' => 100, 'var' => '--am-vb-tint-opacity',
				),
			),
		);
	}

	/**
	 * Which packs apply to a given element type.
	 *
	 * @param string $type Element type recorded on the design record.
	 * @return string[]
	 */
	public static function packs_for_type($type) {
		if (in_array($type, array('text', 'textarea', 'button', 'tabs'), true)) {
			return array('text');
		}
		if (in_array($type, array('image', 'logo', 'video'), true)) {
			return array('media');
		}
		if ('section' === $type) {
			return array('section');
		}

		return array();
	}

	/**
	 * Full property set for one page section.
	 *
	 * Sections resolve through exactly the same path as elements - the base
	 * toolkit plus a pack - so a band gets corner radius, border, shadow, hover
	 * and scroll animation for free, on every page, without one line of
	 * per-page code. The only extra step is the region gate: a handful of
	 * properties drive CSS variables that exist in one band's markup alone.
	 *
	 * @param string $region_id Region identifier, e.g. `home-hero`.
	 * @return array<string,array<string,mixed>>
	 */
	public static function section_props($region_id = '') {
		return self::applicable_to_section(self::props_for_type('section'), $region_id);
	}

	/**
	 * Drop section properties that do not belong to a given region.
	 *
	 * @param array<string,array<string,mixed>> $props     Property definitions.
	 * @param string                            $region_id Region identifier.
	 * @return array<string,array<string,mixed>>
	 */
	public static function applicable_to_section($props, $region_id) {
		$allowed = array();
		foreach ($props as $key => $prop) {
			if (!empty($prop['sections']) && !in_array($region_id, (array) $prop['sections'], true)) {
				continue;
			}
			if (!empty($prop['notSections']) && in_array($region_id, (array) $prop['notSections'], true)) {
				continue;
			}
			$allowed[$key] = $prop;
		}

		return $allowed;
	}

	/**
	 * Clamp one section record against the schema.
	 *
	 * @param string              $region_id Region identifier.
	 * @param array<string,mixed> $source    Untrusted incoming section record.
	 * @param array<string,mixed> $legacy    Values the caller already produced.
	 * @return array<string,mixed>
	 */
	public static function sanitize_section($region_id, $source, $legacy = array()) {
		$clean = is_array($legacy) ? $legacy : array();
		foreach (self::section_props($region_id) as $key => $prop) {
			if (!empty($prop['device'])) {
				continue;
			}
			if (array_key_exists($key, $clean) || !isset($source[$key])) {
				continue;
			}
			$clean[$key] = self::sanitize_value($prop, $source[$key]);
		}

		return $clean;
	}

	/**
	 * Clamp one section's values for one breakpoint.
	 *
	 * @param string              $region_id     Region identifier.
	 * @param array<string,mixed> $device_source Untrusted incoming device record.
	 * @param array<string,mixed> $legacy        Values the caller already produced.
	 * @return array<string,mixed>
	 */
	public static function sanitize_section_device($region_id, $device_source, $legacy = array()) {
		$clean = is_array($legacy) ? $legacy : array();
		foreach (self::section_props($region_id) as $key => $prop) {
			if (empty($prop['device'])) {
				continue;
			}
			if (array_key_exists($key, $clean) || !isset($device_source[$key])) {
				continue;
			}
			$clean[$key] = self::sanitize_value($prop, $device_source[$key]);
		}

		return $clean;
	}

	/**
	 * Full property set for an element type: base toolkit plus its packs.
	 *
	 * @param string $type Element type.
	 * @return array<string,array<string,mixed>>
	 */
	public static function props_for_type($type) {
		$props = self::base_props();
		$packs = self::packs();
		foreach (self::packs_for_type($type) as $pack) {
			if (isset($packs[$pack])) {
				$props = array_merge($props, $packs[$pack]);
			}
		}

		// A control that cannot act on this element must not be offered at all.
		// An inert slider teaches the client that the tool is unreliable, which
		// costs more trust than the missing control ever would.
		return self::applicable($props, $type);
	}

	/**
	 * Drop properties that do not apply to an element type.
	 *
	 * @param array<string,array<string,mixed>> $props Property definitions.
	 * @param string                            $type  Element type.
	 * @return array<string,array<string,mixed>>
	 */
	public static function applicable($props, $type) {
		$allowed = array();
		foreach ($props as $key => $prop) {
			if (!empty($prop['types']) && !in_array($type, (array) $prop['types'], true)) {
				continue;
			}
			if (!empty($prop['notTypes']) && in_array($type, (array) $prop['notTypes'], true)) {
				continue;
			}
			$allowed[$key] = $prop;
		}

		return $allowed;
	}

	/**
	 * Which CSS-variable namespace an element type writes into. Media elements
	 * keep their historical `--am-media-*` names so existing saved designs and
	 * existing stylesheet rules keep working untouched.
	 *
	 * @param string $type Element type.
	 * @return string
	 */
	public static function scope_for_type($type) {
		return in_array($type, array('image', 'logo', 'video'), true) ? 'media' : 'element';
	}

	/**
	 * Clamp and coerce one property value against its definition.
	 *
	 * @param array<string,mixed> $prop  Property definition.
	 * @param mixed               $value Raw incoming value.
	 * @return mixed
	 */
	public static function sanitize_value($prop, $value) {
		$control = isset($prop['control']) ? $prop['control'] : 'range';

		if ('toggle' === $control) {
			return (bool) $value;
		}
		if ('select' === $control) {
			$options = isset($prop['options']) ? (array) $prop['options'] : array();
			$value   = (string) $value;

			return in_array($value, $options, true) ? $value : (string) $prop['default'];
		}
		if ('color' === $control) {
			$value = sanitize_hex_color((string) $value);

			return $value ? $value : '';
		}

		// Values are stored to two decimals so a client can type an exact
		// figure, but collapse back to a plain integer when there is no
		// fractional part - saved designs stay readable and comparable.
		$minimum = isset($prop['min']) ? (float) $prop['min'] : 0.0;
		$maximum = isset($prop['max']) ? (float) $prop['max'] : 0.0;
		$number  = round((float) $value, 2);
		$number  = max($minimum, min($maximum, $number));

		return (floor($number) === $number) ? (int) $number : $number;
	}

	/**
	 * Rebuild the stored non-device values for one element, dropping anything
	 * the schema does not define.
	 *
	 * @param string              $type   Element type.
	 * @param array<string,mixed> $source Untrusted incoming element record.
	 * @return array<string,mixed>
	 */
	public static function sanitize_flat($type, $source) {
		$clean = array();
		foreach (self::props_for_type($type) as $key => $prop) {
			if (!empty($prop['device'])) {
				continue;
			}
			if (!isset($source[$key])) {
				continue;
			}
			$clean[$key] = self::sanitize_value($prop, $source[$key]);
		}

		return $clean;
	}

	/**
	 * Rebuild the stored per-device values for one element and one breakpoint.
	 *
	 * @param string              $type          Element type.
	 * @param array<string,mixed> $device_source Untrusted incoming device record.
	 * @param array<string,mixed> $legacy        Values already produced by the
	 *                                           caller's existing clamps, kept so
	 *                                           historical ranges are preserved.
	 * @return array<string,mixed>
	 */
	public static function sanitize_device($type, $device_source, $legacy = array()) {
		$clean = is_array($legacy) ? $legacy : array();
		foreach (self::props_for_type($type) as $key => $prop) {
			if (empty($prop['device'])) {
				continue;
			}
			if (array_key_exists($key, $clean)) {
				continue;
			}
			if (!isset($device_source[$key])) {
				continue;
			}
			$clean[$key] = self::sanitize_value($prop, $device_source[$key]);
		}

		return $clean;
	}

	/**
	 * Load the renderer on the front end. The editor canvas is an iframe of a
	 * real front-end request, so this single hook covers both the canvas and
	 * the site itself - the two can never drift apart.
	 *
	 * @return void
	 */
	public static function enqueue_frontend() {
		wp_enqueue_style(
			'am-vb-style-vars',
			AM_VB_PLUGIN_URL . 'assets/style-vars.css',
			array(),
			AM_VB_VERSION
		);
		wp_enqueue_script(
			'am-vb-style-apply',
			AM_VB_PLUGIN_URL . 'assets/style-apply.js',
			array(),
			AM_VB_VERSION,
			true
		);
		wp_add_inline_script(
			'am-vb-style-apply',
			'window.amVBStyleSchema = ' . wp_json_encode(self::payload()) . ';',
			'before'
		);
	}

	/**
	 * Everything the editor and the public runtime need to render and apply the
	 * schema without duplicating any of it in JavaScript.
	 *
	 * @return array<string,mixed>
	 */
	public static function payload() {
		return array(
			'version'  => self::VERSION,
			'groups'   => self::groups(),
			'base'     => self::base_props(),
			'packs'    => self::packs(),
			'packsFor' => array(
				'text'     => array('text'),
				'textarea' => array('text'),
				'button'   => array('text'),
				'tabs'     => array('text'),
				'image'    => array('media'),
				'logo'     => array('media'),
				'video'    => array('media'),
				'section'  => array('section'),
			),
			'devices'  => array('desktop', 'tablet', 'mobile'),
		);
	}
}
