(function (wp, config) {
	'use strict';

	if (!wp || !config) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useCallback = wp.element.useCallback;
	var useEffect = wp.element.useEffect;
	var useMemo = wp.element.useMemo;
	var useRef = wp.element.useRef;
	var useState = wp.element.useState;
	var apiFetch = wp.apiFetch;

	apiFetch.use(apiFetch.createNonceMiddleware(config.nonce));

	function clone(value) {
		return JSON.parse(JSON.stringify(value));
	}

	/**
	 * Coerce a keyed map to a plain object.
	 *
	 * PHP encodes an empty associative array as JSON `[]`, so a page whose
	 * design has never been saved arrives with `elements` and `collections` as
	 * JavaScript **arrays**. Writing `design.elements['hero-title'] = {...}`
	 * then stores a named property on an array - and `JSON.stringify` throws
	 * named properties on an array away. Because `clone()` is a JSON round
	 * trip, the very next edit silently destroyed every earlier edit, and the
	 * save payload went out empty. That is the whole "I move one slider and the
	 * one before it drops to zero" bug.
	 *
	 * @param {*} value Candidate map.
	 * @returns {object} Plain object, preserving any keys already set.
	 */
	function toMap(value) {
		if (value && 'object' === typeof value && !Array.isArray(value)) {
			return value;
		}
		var map = {};
		if (Array.isArray(value)) {
			Object.keys(value).forEach(function (key) {
				map[key] = value[key];
			});
		}

		return map;
	}

	/**
	 * Normalise a design so every keyed map really is an object. This is the
	 * only guard against the array/object trap above, so it runs at every point
	 * a design enters the model - on load, before every clone, and on save.
	 *
	 * @param {*} design Raw design.
	 * @returns {object} Shallow copy with sound container types.
	 */
	function normaliseDesign(design) {
		var source = design && 'object' === typeof design ? design : {};
		var model = {};
		var key;
		for (key in source) {
			if (Object.prototype.hasOwnProperty.call(source, key)) {
				model[key] = source[key];
			}
		}
		model.sections = toMap(model.sections);
		model.elements = toMap(model.elements);
		model.collections = toMap(model.collections);
		model.customSections = Array.isArray(model.customSections) ? model.customSections : [];

		return model;
	}

	function uid(prefix) {
		return prefix + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
	}

	function sessionId() {
		var key = 'am-vb-session';
		var value = window.sessionStorage.getItem(key);
		if (!value) {
			value = uid('session') + '-' + Math.random().toString(36).slice(2, 10);
			window.sessionStorage.setItem(key, value);
		}
		return value;
	}

	function calendarDayNumber(value) {
		var date = new Date(value);
		if (Number.isNaN(date.getTime())) {
			return Number.MAX_SAFE_INTEGER;
		}
		return Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()) / 86400000;
	}

	function selectedDayNumber(value) {
		var match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(String(value || '').trim());
		if (!match) {
			return null;
		}
		var day = Number(match[1]);
		var month = Number(match[2]);
		var year = Number(match[3]);
		var timestamp = Date.UTC(year, month - 1, day);
		var date = new Date(timestamp);
		return date.getUTCFullYear() === year && date.getUTCMonth() === month - 1 && date.getUTCDate() === day
			? timestamp / 86400000
			: null;
	}

	function formatDayMonthYearInput(value) {
		var digits = String(value || '').replace(/\D/g, '').slice(0, 8);
		if (digits.length <= 2) {
			return digits;
		}
		if (digits.length <= 4) {
			return digits.slice(0, 2) + '/' + digits.slice(2);
		}
		return digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
	}

	function dayMonthYearPickerValue(value) {
		var match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(String(value || '').trim());
		return match && null !== selectedDayNumber(value)
			? match[3] + '-' + match[2] + '-' + match[1]
			: '';
	}

	function dayMonthYearFromPicker(value) {
		var match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || '').trim());
		return match ? match[3] + '/' + match[2] + '/' + match[1] : '';
	}

	function readableSelectedDate(value) {
		var day = selectedDayNumber(value);
		return null === day
			? ''
			: new Date(day * 86400000).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' });
	}

	function normalizeDocument(value) {
		var next = clone(value || { version: 2, title: 'Untitled page', sections: [] });
		next.version = 2;
		next.sections = Array.isArray(next.sections) ? next.sections : [];
		next.sections.forEach(function (section) {
			section.visible = false !== section.visible;
			section.locked = !!section.locked;
			section.settings = section.settings || { layout: 'stack' };
			section.groups = Array.isArray(section.groups) ? section.groups : [];
			section.elements = Array.isArray(section.elements) ? section.elements : [];
			section.elements.forEach(function (element) {
				element.visible = false !== element.visible;
				element.locked = !!element.locked;
				element.groupId = element.groupId || '';
			});
		});
		return next;
	}

	function mergeResponsive(styles, device) {
		var source = styles || {};
		var merged = Object.assign({}, source.desktop || {});
		if ('tablet' === device) {
			Object.assign(merged, source.tablet || {});
		}
		if ('mobile' === device) {
			Object.assign(merged, source.tablet || {}, source.mobile || {});
		}
		return merged;
	}

	function cssStyle(raw, layered) {
		var source = raw || {};
		var style = {};
		[
			'backgroundColor', 'backgroundImage', 'backgroundSize', 'backgroundPosition',
			'color', 'fontSize', 'fontWeight', 'lineHeight', 'letterSpacing',
			'textAlign', 'textDecoration', 'width', 'height', 'minHeight', 'padding',
			'margin', 'gap', 'borderRadius', 'borderColor', 'borderWidth', 'borderStyle',
			'boxShadow', 'opacity', 'objectFit', 'objectPosition', 'overflow', 'zIndex'
		].forEach(function (key) {
			if (undefined !== source[key] && '' !== source[key]) {
				style[key] = source[key];
			}
		});
		if (source.backgroundImage && 'none' !== source.backgroundImage && 0 !== source.backgroundImage.indexOf('url(')) {
			style.backgroundImage = 'url("' + String(source.backgroundImage).replace(/"/g, '') + '")';
		}
		if (layered) {
			style.position = 'absolute';
			if (undefined !== source.positionX) {
				style.left = source.positionX;
			}
			if (undefined !== source.positionY) {
				style.top = source.positionY;
			}
		}
		if (source.rotate) {
			style.transform = 'rotate(' + source.rotate + ')';
		}
		return style;
	}

	function iconFor(type) {
		return { text: 'T', button: '▣', image: '▧', shape: '●', unknown: '?' }[type] || '•';
	}

	function button(className, label, onClick, props) {
		return el('button', Object.assign({
			type: 'button',
			className: className,
			onClick: onClick
		}, props || {}), label);
	}

	function Field(props) {
		if ('checkbox' === props.type) {
			return el('label', { className: 'am-vb-field am-vb-field--checkbox' },
				el('input', {
					type: 'checkbox',
					checked: !!props.value,
					disabled: !!props.disabled,
					onChange: function (event) { props.onChange(event.target.checked); }
				}),
				el('span', null, props.label)
			);
		}
		var inputProps = {
			value: undefined === props.value || null === props.value ? '' : props.value,
			onChange: function (event) { props.onChange(event.target.value); },
			placeholder: props.placeholder || '',
			type: props.type || 'text',
			disabled: !!props.disabled
		};
		if ('select' === props.kind) {
			return el('label', { className: 'am-vb-field' },
				el('span', null, props.label),
				el('select', inputProps, (props.options || []).map(function (option) {
					return el('option', { key: option.value, value: option.value }, option.label);
				}))
			);
		}
		if ('textarea' === props.kind) {
			return el('label', { className: 'am-vb-field' },
				el('span', null, props.label),
				el('textarea', inputProps)
			);
		}
		return el('label', { className: 'am-vb-field' },
			el('span', null, props.label),
			el('input', inputProps)
		);
	}

	function LinkField(props) {
		var _open = useState(false);
		var open = _open[0];
		var setOpen = _open[1];
		var rawQuery = String(undefined === props.value || null === props.value ? '' : props.value);
		var query = rawQuery.trim().toLowerCase().replace(/^\//, '');
		var matches = (props.suggestions || []).filter(function (suggestion) {
			if (!query) {
				return true;
			}
			return String(suggestion.label || '').toLowerCase().indexOf(query) !== -1
				|| String(suggestion.href || '').toLowerCase().indexOf(rawQuery.trim().toLowerCase()) !== -1;
		}).slice(0, 8);
		var listId = 'am-vb-link-suggestions-' + String(props.fieldId || props.label || 'link').toLowerCase().replace(/[^a-z0-9]+/g, '-');
		return el('div', { className: 'am-vb-field am-vb-link-field' },
			el('label', { htmlFor: listId }, props.label),
			el('input', {
				id: listId,
				type: 'text',
				value: rawQuery,
				disabled: !!props.disabled,
				placeholder: props.placeholder || '/page or https://example.com',
				autoComplete: 'off',
				'aria-expanded': open && !!matches.length,
				'aria-controls': listId + '-list',
				onFocus: function () { setOpen(true); },
				onBlur: function () { window.setTimeout(function () { setOpen(false); }, 120); },
				onChange: function (event) {
					props.onChange(event.target.value);
					setOpen(true);
				}
			}),
			open && matches.length
				? el('div', { id: listId + '-list', className: 'am-vb-link-suggestions', role: 'listbox' }, matches.map(function (suggestion) {
					return button('am-vb-link-suggestions__item', el(Fragment, null,
						el('strong', null, suggestion.label),
						el('span', null, suggestion.href)
					), function () {
						props.onChange(suggestion.href);
						setOpen(false);
					}, {
						key: suggestion.href,
						role: 'option',
						onMouseDown: function (event) { event.preventDefault(); }
					});
				}))
				: null
		);
	}

	function RangeField(props) {
		var value = Number(props.value);
		if (!Number.isFinite(value)) {
			value = Number(props.min) || 0;
		}
		// Some schema properties treat zero as "inherit whatever the design
		// already does". Showing "0px" there reads as a broken value, so the
		// definition can supply a word to display instead.
		var minimum = undefined === props.min ? 0 : Number(props.min);
		var maximum = undefined === props.max ? 100 : Number(props.max);
		var hint = (0 === value && props.zeroLabel) ? props.zeroLabel : (props.unit || '');

		// The slider is for feel, the number box is for precision. The slider
		// keeps its coarse step; the box accepts any typed value including two
		// decimal places, because "roughly right" is not always good enough.
		function commit(next) {
			if (!Number.isFinite(next)) {
				return;
			}
			props.onChange(Math.max(minimum, Math.min(maximum, Math.round(next * 100) / 100)));
		}

		return el('div', { className: 'am-vb-range-field' },
			el('span', { className: 'am-vb-range-field__heading' },
				el('span', { className: 'am-vb-range-field__label' }, props.label),
				el('span', { className: 'am-vb-range-field__value' },
					el('input', {
						type: 'number',
						className: 'am-vb-range-field__number',
						value: value,
						min: props.min,
						max: props.max,
						step: 'any',
						inputMode: 'decimal',
						disabled: !!props.disabled,
						'aria-label': props.label + ' exact value',
						onChange: function (event) {
							if ('' === event.target.value) {
								return;
							}
							commit(Number(event.target.value));
						}
					}),
					hint ? el('span', { className: 'am-vb-range-field__unit' }, hint) : null
				)
			),
			el('input', {
				type: 'range',
				min: props.min,
				max: props.max,
				step: props.step || 1,
				value: value,
				disabled: !!props.disabled,
				'aria-label': props.label,
				onChange: function (event) { commit(Number(event.target.value)); }
			})
		);
	}

	function titleCase(value) {
		return String(value || '').replace(/_/g, ' ').replace(/\b\w/g, function (letter) {
			return letter.toUpperCase();
		});
	}

	// The inspector renders whatever the PHP schema declares. Injecting the
	// primitives keeps that file free of any WordPress or editor coupling.
	var inspectorApi = window.AMVBInspector
		? window.AMVBInspector.create({
			el: el,
			Fragment: Fragment,
			useState: useState,
			Field: Field,
			RangeField: RangeField,
			button: button,
			titleCase: titleCase
		})
		: null;
	var StylePanel = inspectorApi ? inspectorApi.StylePanel : null;

	var LIVE_HOME_REGIONS = [
		{
			id: 'global-header',
			label: 'Header & navigation',
			kind: 'Global component',
			detail: 'Logo, desktop navigation, nursery menu, availability action and mobile menus.',
			editUrlKey: 'settingsUrl'
		},
		{
			id: 'home-hero',
			label: 'Video hero',
			kind: 'Home section',
			detail: 'Responsive promotional video, poster image, dither texture, tint overlay and accessible page heading.'
		},
		{
			id: 'home-feature-links',
			label: 'Nursery feature links',
			kind: 'Home section · 3 cards',
			detail: 'Three headings, three clipped nursery images, three calls to action and three supporting icons.',
			editUrlKey: 'nurseriesUrl'
		},
		{
			id: 'home-benefits',
			label: 'Montessori benefits',
			kind: 'Home section · 3 benefits',
			detail: 'Three icon-and-copy benefits with their own responsive grid and spacing.'
		},
		{
			id: 'home-about',
			label: 'About us journey',
			kind: 'Home section',
			detail: 'Heading, story paragraphs, milestone chips, optional upcoming location and founders image.',
			editUrlKey: 'aboutUrl'
		},
		{
			id: 'home-testimonials',
			label: 'Parent testimonials',
			kind: 'Home section · up to 3 cards',
			detail: 'Photo background, colour overlay, heading, testimonial cards and archive call to action.',
			editUrlKey: 'testimonialsUrl'
		},
		{
			id: 'home-trust',
			label: 'Trust & accreditations',
			kind: 'Home section · 4 items',
			detail: 'Four accreditation marks with responsive icon-and-label layouts.'
		},
		{
			id: 'social-sidebar',
			label: 'Social links rail',
			kind: 'Global component',
			detail: 'Fixed, platform-coloured social links shown from tablet widths upward.',
			editUrlKey: 'settingsUrl'
		},
		{
			id: 'global-footer',
			label: 'Footer',
			kind: 'Global component',
			detail: 'Brand, contact details, hours, Ofsted mark and the expandable quick-links and nursery-contact area.',
			editUrlKey: 'settingsUrl'
		},
		{
			id: 'cookie-control',
			label: 'Cookie control',
			kind: 'Global system component',
			detail: 'Consent banner and preferences dialog. It may be hidden when consent is already stored.'
		}
	];

	var CORE_LINK_SUGGESTIONS = [
		{ label: 'Home', href: '/' },
		{ label: 'Our Nurseries', href: '/nurseries' },
		{ label: 'Hounslow nursery', href: '/nurseries/hounslow' },
		{ label: 'Heston nursery', href: '/nurseries/heston' },
		{ label: 'Hammersmith nursery', href: '/nurseries/hammersmith' },
		{ label: 'About us', href: '/about' },
		{ label: 'Our Curriculum', href: '/curriculum' },
		{ label: 'Fees', href: '/fees' },
		{ label: 'Fee Calculator', href: '/fee-calculator' },
		{ label: 'Funded Childcare', href: '/funded-childcare' },
		{ label: 'Blog', href: '/blogs' },
		{ label: 'Food Hygiene Rating', href: '/food-hygiene-rating' },
		{ label: 'Events', href: '/events' },
		{ label: 'Testimonials', href: '/testimonials' },
		{ label: 'Careers', href: '/careers' },
		{ label: 'Current Vacancies', href: '/careers/vacancies' },
		{ label: 'Apply for a role', href: '/careers/apply' },
		{ label: 'Check Availability', href: '/check-availability' },
		{ label: 'Contact', href: '/contact' },
		{ label: 'Hounslow contact', href: '/contact/hounslow' },
		{ label: 'Heston contact', href: '/contact/heston' },
		{ label: 'Hammersmith contact', href: '/contact/hammersmith' },
		{ label: 'Privacy Policy', href: '/privacy' }
	];

	function exactRoute(pageSlug) {
		return config.exactRoutes && config.exactRoutes[pageSlug]
			? config.exactRoutes[pageSlug]
			: null;
	}

	var HOME_BLOCK_TEMPLATES = [
		{
			id: 'blank',
			category: 'Basics',
			name: 'Blank design canvas',
			description: 'Start with an empty responsive section and add any layers you need.',
			height: { desktop: 420, tablet: 420, mobile: 520 },
			items: []
		},
		{
			id: 'image-text',
			category: 'Story',
			name: 'Photo + story',
			description: 'A large photo with an editable heading and paragraph.',
			height: { desktop: 520, tablet: 620, mobile: 780 },
			items: [
				{ type: 'image', name: 'Story photo', desktop: { x: 70, y: 70, width: 520, height: 380 }, tablet: { x: 44, y: 52, width: 680, height: 300 }, mobile: { x: 24, y: 32, width: 342, height: 280 }, borderRadius: 32 },
				{ type: 'text', name: 'Story heading', value: 'A thoughtful new story', desktop: { x: 660, y: 105, width: 620, height: 100, fontSize: 48 }, tablet: { x: 44, y: 390, width: 680, height: 80, fontSize: 38 }, mobile: { x: 24, y: 350, width: 342, height: 100, fontSize: 34 } },
				{ type: 'text', name: 'Story copy', value: 'Click here and type your own words. Move or stretch this text box to shape the layout.', desktop: { x: 660, y: 235, width: 560, height: 150, fontSize: 18 }, tablet: { x: 44, y: 485, width: 680, height: 100, fontSize: 18 }, mobile: { x: 24, y: 475, width: 342, height: 190, fontSize: 18 } }
			]
		},
		{
			id: 'four-cards',
			category: 'Cards',
			name: 'Four visual cards',
			description: 'A ready-made four-item row with photos and editable labels.',
			height: { desktop: 520, tablet: 760, mobile: 1360 },
			items: [
				{ type: 'text', name: 'Section heading', value: 'Explore more', desktop: { x: 70, y: 38, width: 600, height: 70, fontSize: 42 }, tablet: { x: 44, y: 32, width: 620, height: 65, fontSize: 36 }, mobile: { x: 24, y: 24, width: 342, height: 70, fontSize: 34 } },
				{ type: 'image', name: 'Card 1 photo', desktop: { x: 70, y: 130, width: 285, height: 250 }, tablet: { x: 44, y: 120, width: 320, height: 230 }, mobile: { x: 24, y: 120, width: 342, height: 220 }, borderRadius: 26 },
				{ type: 'text', name: 'Card 1 label', value: 'First card', desktop: { x: 70, y: 395, width: 285, height: 50, fontSize: 22 }, tablet: { x: 44, y: 365, width: 320, height: 48, fontSize: 21 }, mobile: { x: 24, y: 355, width: 342, height: 50, fontSize: 22 } },
				{ type: 'image', name: 'Card 2 photo', desktop: { x: 405, y: 130, width: 285, height: 250 }, tablet: { x: 404, y: 120, width: 320, height: 230 }, mobile: { x: 24, y: 425, width: 342, height: 220 }, borderRadius: 26 },
				{ type: 'text', name: 'Card 2 label', value: 'Second card', desktop: { x: 405, y: 395, width: 285, height: 50, fontSize: 22 }, tablet: { x: 404, y: 365, width: 320, height: 48, fontSize: 21 }, mobile: { x: 24, y: 660, width: 342, height: 50, fontSize: 22 } },
				{ type: 'image', name: 'Card 3 photo', desktop: { x: 740, y: 130, width: 285, height: 250 }, tablet: { x: 44, y: 445, width: 320, height: 230 }, mobile: { x: 24, y: 730, width: 342, height: 220 }, borderRadius: 26 },
				{ type: 'text', name: 'Card 3 label', value: 'Third card', desktop: { x: 740, y: 395, width: 285, height: 50, fontSize: 22 }, tablet: { x: 44, y: 690, width: 320, height: 48, fontSize: 21 }, mobile: { x: 24, y: 965, width: 342, height: 50, fontSize: 22 } },
				{ type: 'image', name: 'Card 4 photo', desktop: { x: 1075, y: 130, width: 285, height: 250 }, tablet: { x: 404, y: 445, width: 320, height: 230 }, mobile: { x: 24, y: 1035, width: 342, height: 220 }, borderRadius: 26 },
				{ type: 'text', name: 'Card 4 label', value: 'Fourth card', desktop: { x: 1075, y: 395, width: 285, height: 50, fontSize: 22 }, tablet: { x: 404, y: 690, width: 320, height: 48, fontSize: 21 }, mobile: { x: 24, y: 1270, width: 342, height: 50, fontSize: 22 } }
			]
		},
		{
			id: 'logo-strip',
			category: 'Trust',
			name: 'Logo / accreditation strip',
			description: 'A heading and four independently replaceable, resizable logos.',
			height: { desktop: 330, tablet: 520, mobile: 760 },
			items: [
				{ type: 'text', name: 'Logo strip heading', value: 'Trusted by families and partners', desktop: { x: 70, y: 40, width: 900, height: 70, fontSize: 38 }, tablet: { x: 44, y: 32, width: 680, height: 70, fontSize: 34 }, mobile: { x: 24, y: 24, width: 342, height: 90, fontSize: 32 } },
				{ type: 'logo', name: 'Logo 1', desktop: { x: 100, y: 145, width: 220, height: 110 }, tablet: { x: 90, y: 140, width: 250, height: 120 }, mobile: { x: 70, y: 135, width: 250, height: 110 }, borderRadius: 12 },
				{ type: 'logo', name: 'Logo 2', desktop: { x: 430, y: 145, width: 220, height: 110 }, tablet: { x: 430, y: 140, width: 250, height: 120 }, mobile: { x: 70, y: 275, width: 250, height: 110 }, borderRadius: 12 },
				{ type: 'logo', name: 'Logo 3', desktop: { x: 760, y: 145, width: 220, height: 110 }, tablet: { x: 90, y: 320, width: 250, height: 120 }, mobile: { x: 70, y: 415, width: 250, height: 110 }, borderRadius: 12 },
				{ type: 'logo', name: 'Logo 4', desktop: { x: 1090, y: 145, width: 220, height: 110 }, tablet: { x: 430, y: 320, width: 250, height: 120 }, mobile: { x: 70, y: 555, width: 250, height: 110 }, borderRadius: 12 }
			]
		},
		{
			id: 'cta-tabs',
			category: 'Actions',
			name: 'Tabs + call to action',
			description: 'Editable pill tabs, supporting copy and a linked button.',
			height: { desktop: 420, tablet: 470, mobile: 620 },
			items: [
				{ type: 'text', name: 'Section heading', value: 'Find what matters to you', desktop: { x: 70, y: 55, width: 900, height: 80, fontSize: 44 }, tablet: { x: 44, y: 48, width: 680, height: 75, fontSize: 38 }, mobile: { x: 24, y: 38, width: 342, height: 100, fontSize: 34 } },
				{ type: 'tabs', name: 'Tabs', value: 'Overview\nLearning\nCare', desktop: { x: 70, y: 155, width: 760, height: 80, fontSize: 16 }, tablet: { x: 44, y: 150, width: 680, height: 90, fontSize: 16 }, mobile: { x: 24, y: 165, width: 342, height: 130, fontSize: 15 } },
				{ type: 'text', name: 'Supporting copy', value: 'Use these tabs as an aesthetic navigation row or change the words for your own categories.', desktop: { x: 70, y: 255, width: 760, height: 90, fontSize: 19 }, tablet: { x: 44, y: 270, width: 680, height: 90, fontSize: 18 }, mobile: { x: 24, y: 325, width: 342, height: 150, fontSize: 18 } },
				{ type: 'button', name: 'Action button', value: 'Learn more', href: '#', desktop: { x: 980, y: 170, width: 250, height: 58, fontSize: 16 }, tablet: { x: 44, y: 375, width: 240, height: 56, fontSize: 16 }, mobile: { x: 24, y: 505, width: 240, height: 56, fontSize: 16 }, backgroundColor: '#345b40', color: '#ffffff', borderRadius: 999 }
			]
		},
		{
			id: 'heading-copy',
			category: 'Basics',
			name: 'Heading + rich copy',
			description: 'A calm editorial heading, supporting text and action button.',
			height: { desktop: 380, tablet: 430, mobile: 560 },
			items: [
				{ type: 'text', name: 'Eyebrow', value: 'OUR APPROACH', desktop: { x: 90, y: 58, width: 360, height: 35, fontSize: 14 }, tablet: { x: 48, y: 48, width: 320, height: 35, fontSize: 14 }, mobile: { x: 24, y: 38, width: 300, height: 35, fontSize: 13 } },
				{ type: 'text', name: 'Heading', value: 'A place to grow with confidence', desktop: { x: 90, y: 105, width: 560, height: 150, fontSize: 52 }, tablet: { x: 48, y: 95, width: 650, height: 110, fontSize: 42 }, mobile: { x: 24, y: 90, width: 342, height: 150, fontSize: 36 } },
				{ type: 'text', name: 'Body copy', value: 'Add the story, detail or reassurance families need here.', desktop: { x: 760, y: 118, width: 560, height: 110, fontSize: 20 }, tablet: { x: 48, y: 245, width: 650, height: 90, fontSize: 19 }, mobile: { x: 24, y: 275, width: 342, height: 125, fontSize: 18 } },
				{ type: 'button', name: 'Action', value: 'Discover more', href: '#', desktop: { x: 760, y: 250, width: 210, height: 56, fontSize: 16 }, tablet: { x: 48, y: 350, width: 210, height: 56, fontSize: 16 }, mobile: { x: 24, y: 430, width: 210, height: 56, fontSize: 16 }, backgroundColor: '#345b40', color: '#ffffff', borderRadius: 999 }
			]
		},
		{
			id: 'three-feature-cards',
			category: 'Cards',
			name: 'Three feature cards',
			description: 'Three shaped cards with editable headings, copy and colours.',
			height: { desktop: 560, tablet: 900, mobile: 1280 },
			items: [
				{ type: 'text', name: 'Heading', value: 'What makes us different', desktop: { x: 70, y: 38, width: 700, height: 70, fontSize: 42 }, tablet: { x: 44, y: 32, width: 650, height: 65, fontSize: 38 }, mobile: { x: 24, y: 25, width: 342, height: 85, fontSize: 34 } },
				{ type: 'shape', name: 'Card 1', backgroundColor: '#e7f0e3', borderRadius: 34, desktop: { x: 70, y: 135, width: 390, height: 340 }, tablet: { x: 44, y: 130, width: 320, height: 320 }, mobile: { x: 24, y: 130, width: 342, height: 315 } },
				{ type: 'text', name: 'Card 1 title', value: 'Individual care', desktop: { x: 105, y: 185, width: 310, height: 60, fontSize: 28 }, tablet: { x: 74, y: 175, width: 260, height: 55, fontSize: 25 }, mobile: { x: 52, y: 175, width: 285, height: 55, fontSize: 25 } },
				{ type: 'text', name: 'Card 1 copy', value: 'Tell families what this benefit means in everyday life.', desktop: { x: 105, y: 270, width: 310, height: 130, fontSize: 18 }, tablet: { x: 74, y: 250, width: 260, height: 125, fontSize: 17 }, mobile: { x: 52, y: 250, width: 285, height: 125, fontSize: 17 } },
				{ type: 'shape', name: 'Card 2', backgroundColor: '#f6dfcc', borderRadius: 34, desktop: { x: 525, y: 135, width: 390, height: 340 }, tablet: { x: 404, y: 130, width: 320, height: 320 }, mobile: { x: 24, y: 475, width: 342, height: 315 } },
				{ type: 'text', name: 'Card 2 title', value: 'Purposeful play', desktop: { x: 560, y: 185, width: 310, height: 60, fontSize: 28 }, tablet: { x: 434, y: 175, width: 260, height: 55, fontSize: 25 }, mobile: { x: 52, y: 520, width: 285, height: 55, fontSize: 25 } },
				{ type: 'text', name: 'Card 2 copy', value: 'Use this card for learning, spaces, food or your team.', desktop: { x: 560, y: 270, width: 310, height: 130, fontSize: 18 }, tablet: { x: 434, y: 250, width: 260, height: 125, fontSize: 17 }, mobile: { x: 52, y: 595, width: 285, height: 125, fontSize: 17 } },
				{ type: 'shape', name: 'Card 3', backgroundColor: '#eee4f2', borderRadius: 34, desktop: { x: 980, y: 135, width: 390, height: 340 }, tablet: { x: 224, y: 500, width: 320, height: 320 }, mobile: { x: 24, y: 820, width: 342, height: 315 } },
				{ type: 'text', name: 'Card 3 title', value: 'A warm community', desktop: { x: 1015, y: 185, width: 310, height: 60, fontSize: 28 }, tablet: { x: 254, y: 545, width: 260, height: 55, fontSize: 25 }, mobile: { x: 52, y: 865, width: 285, height: 55, fontSize: 25 } },
				{ type: 'text', name: 'Card 3 copy', value: 'Finish the row with another clear reason to choose you.', desktop: { x: 1015, y: 270, width: 310, height: 130, fontSize: 18 }, tablet: { x: 254, y: 620, width: 260, height: 125, fontSize: 17 }, mobile: { x: 52, y: 940, width: 285, height: 125, fontSize: 17 } }
			]
		},
		{
			id: 'photo-gallery',
			category: 'Gallery',
			name: 'Photo gallery',
			description: 'One large photo with three smaller supporting images.',
			height: { desktop: 650, tablet: 760, mobile: 1060 },
			items: [
				{ type: 'text', name: 'Gallery heading', value: 'A glimpse into nursery life', desktop: { x: 70, y: 35, width: 800, height: 75, fontSize: 44 }, tablet: { x: 44, y: 30, width: 680, height: 70, fontSize: 39 }, mobile: { x: 24, y: 24, width: 342, height: 100, fontSize: 34 } },
				{ type: 'image', name: 'Main photo', desktop: { x: 70, y: 130, width: 760, height: 440 }, tablet: { x: 44, y: 120, width: 680, height: 360 }, mobile: { x: 24, y: 135, width: 342, height: 310 }, borderRadius: 34 },
				{ type: 'image', name: 'Photo 2', desktop: { x: 870, y: 130, width: 235, height: 205 }, tablet: { x: 44, y: 515, width: 210, height: 180 }, mobile: { x: 24, y: 475, width: 342, height: 170 }, borderRadius: 26 },
				{ type: 'image', name: 'Photo 3', desktop: { x: 1135, y: 130, width: 235, height: 205 }, tablet: { x: 279, y: 515, width: 210, height: 180 }, mobile: { x: 24, y: 670, width: 342, height: 170 }, borderRadius: 26 },
				{ type: 'image', name: 'Photo 4', desktop: { x: 870, y: 365, width: 500, height: 205 }, tablet: { x: 514, y: 515, width: 210, height: 180 }, mobile: { x: 24, y: 865, width: 342, height: 170 }, borderRadius: 26 }
			]
		},
		{
			id: 'stats-strip',
			category: 'Trust',
			name: 'Numbers / stats strip',
			description: 'Four prominent facts with editable labels and colour blocks.',
			height: { desktop: 330, tablet: 500, mobile: 790 },
			items: [
				{ type: 'shape', name: 'Background', backgroundColor: '#345b40', borderRadius: 32, desktop: { x: 55, y: 45, width: 1330, height: 235 }, tablet: { x: 36, y: 35, width: 696, height: 420 }, mobile: { x: 18, y: 24, width: 354, height: 720 } },
				{ type: 'text', name: 'Stat 1', value: '20+', color: '#ffffff', desktop: { x: 105, y: 95, width: 240, height: 80, fontSize: 48 }, tablet: { x: 75, y: 75, width: 280, height: 75, fontSize: 44 }, mobile: { x: 50, y: 60, width: 290, height: 70, fontSize: 42 } },
				{ type: 'text', name: 'Stat 1 label', value: 'Years of experience', color: '#ffffff', desktop: { x: 105, y: 185, width: 240, height: 40, fontSize: 17 }, tablet: { x: 75, y: 155, width: 280, height: 40, fontSize: 17 }, mobile: { x: 50, y: 135, width: 290, height: 40, fontSize: 17 } },
				{ type: 'text', name: 'Stat 2', value: '3', color: '#ffffff', desktop: { x: 430, y: 95, width: 240, height: 80, fontSize: 48 }, tablet: { x: 415, y: 75, width: 280, height: 75, fontSize: 44 }, mobile: { x: 50, y: 230, width: 290, height: 70, fontSize: 42 } },
				{ type: 'text', name: 'Stat 2 label', value: 'Welcoming nurseries', color: '#ffffff', desktop: { x: 430, y: 185, width: 240, height: 40, fontSize: 17 }, tablet: { x: 415, y: 155, width: 280, height: 40, fontSize: 17 }, mobile: { x: 50, y: 305, width: 290, height: 40, fontSize: 17 } },
				{ type: 'text', name: 'Stat 3', value: '100%', color: '#ffffff', desktop: { x: 755, y: 95, width: 240, height: 80, fontSize: 48 }, tablet: { x: 75, y: 260, width: 280, height: 75, fontSize: 44 }, mobile: { x: 50, y: 400, width: 290, height: 70, fontSize: 42 } },
				{ type: 'text', name: 'Stat 3 label', value: 'Child-centred care', color: '#ffffff', desktop: { x: 755, y: 185, width: 240, height: 40, fontSize: 17 }, tablet: { x: 75, y: 340, width: 280, height: 40, fontSize: 17 }, mobile: { x: 50, y: 475, width: 290, height: 40, fontSize: 17 } },
				{ type: 'text', name: 'Stat 4', value: '5★', color: '#ffffff', desktop: { x: 1080, y: 95, width: 240, height: 80, fontSize: 48 }, tablet: { x: 415, y: 260, width: 280, height: 75, fontSize: 44 }, mobile: { x: 50, y: 570, width: 290, height: 70, fontSize: 42 } },
				{ type: 'text', name: 'Stat 4 label', value: 'Family feedback', color: '#ffffff', desktop: { x: 1080, y: 185, width: 240, height: 40, fontSize: 17 }, tablet: { x: 415, y: 340, width: 280, height: 40, fontSize: 17 }, mobile: { x: 50, y: 645, width: 290, height: 40, fontSize: 17 } }
			]
		},
		{
			id: 'testimonial-spotlight',
			category: 'Story',
			name: 'Testimonial spotlight',
			description: 'A parent quote beside a shaped family photo.',
			height: { desktop: 520, tablet: 650, mobile: 840 },
			items: [
				{ type: 'shape', name: 'Quote panel', backgroundColor: '#eee4f2', borderRadius: 44, desktop: { x: 70, y: 70, width: 760, height: 380 }, tablet: { x: 44, y: 330, width: 680, height: 270 }, mobile: { x: 24, y: 380, width: 342, height: 390 } },
				{ type: 'text', name: 'Quote', value: '“The warmth, care and confidence our child found here has been wonderful.”', desktop: { x: 125, y: 135, width: 650, height: 165, fontSize: 32 }, tablet: { x: 88, y: 375, width: 590, height: 120, fontSize: 27 }, mobile: { x: 55, y: 430, width: 280, height: 205, fontSize: 25 } },
				{ type: 'text', name: 'Parent name', value: '— A happy parent', desktop: { x: 125, y: 340, width: 400, height: 40, fontSize: 17 }, tablet: { x: 88, y: 525, width: 400, height: 40, fontSize: 17 }, mobile: { x: 55, y: 675, width: 280, height: 45, fontSize: 17 } },
				{ type: 'image', name: 'Family photo', desktop: { x: 900, y: 70, width: 470, height: 380 }, tablet: { x: 134, y: 40, width: 500, height: 250 }, mobile: { x: 24, y: 34, width: 342, height: 310 }, borderRadius: 160 }
			]
		},
		{
			id: 'milestone-timeline',
			category: 'Story',
			name: 'Milestone timeline',
			description: 'A flexible four-stage journey for history or a child pathway.',
			height: { desktop: 480, tablet: 700, mobile: 1020 },
			items: [
				{ type: 'text', name: 'Timeline heading', value: 'Our journey', desktop: { x: 70, y: 40, width: 650, height: 70, fontSize: 44 }, tablet: { x: 44, y: 35, width: 650, height: 70, fontSize: 39 }, mobile: { x: 24, y: 25, width: 342, height: 80, fontSize: 34 } },
				{ type: 'shape', name: 'Timeline line', backgroundColor: '#a9c6a2', borderRadius: 999, desktop: { x: 135, y: 200, width: 1170, height: 8 }, tablet: { x: 120, y: 125, width: 8, height: 500 }, mobile: { x: 55, y: 125, width: 8, height: 780 } },
				{ type: 'text', name: 'Milestone 1', value: '2005\nThe beginning', desktop: { x: 70, y: 150, width: 250, height: 150, fontSize: 23 }, tablet: { x: 165, y: 115, width: 480, height: 100, fontSize: 22 }, mobile: { x: 90, y: 120, width: 265, height: 125, fontSize: 21 } },
				{ type: 'text', name: 'Milestone 2', value: '2012\nGrowing together', desktop: { x: 405, y: 150, width: 250, height: 150, fontSize: 23 }, tablet: { x: 165, y: 245, width: 480, height: 100, fontSize: 22 }, mobile: { x: 90, y: 315, width: 265, height: 125, fontSize: 21 } },
				{ type: 'text', name: 'Milestone 3', value: '2019\nA wider community', desktop: { x: 740, y: 150, width: 250, height: 150, fontSize: 23 }, tablet: { x: 165, y: 375, width: 480, height: 100, fontSize: 22 }, mobile: { x: 90, y: 510, width: 265, height: 125, fontSize: 21 } },
				{ type: 'text', name: 'Milestone 4', value: 'Today\nStill learning', desktop: { x: 1075, y: 150, width: 250, height: 150, fontSize: 23 }, tablet: { x: 165, y: 505, width: 480, height: 100, fontSize: 22 }, mobile: { x: 90, y: 705, width: 265, height: 125, fontSize: 21 } }
			]
		},
		{
			id: 'centred-cta',
			category: 'Actions',
			name: 'Centred call to action',
			description: 'A bold colour panel with heading, copy and two buttons.',
			height: { desktop: 440, tablet: 490, mobile: 650 },
			items: [
				{ type: 'shape', name: 'CTA background', backgroundColor: '#f6dfcc', borderRadius: 48, desktop: { x: 70, y: 45, width: 1300, height: 350 }, tablet: { x: 36, y: 36, width: 696, height: 410 }, mobile: { x: 18, y: 25, width: 354, height: 575 } },
				{ type: 'text', name: 'CTA heading', value: 'Come and see us in action', desktop: { x: 270, y: 105, width: 900, height: 90, fontSize: 50 }, tablet: { x: 100, y: 90, width: 568, height: 100, fontSize: 42 }, mobile: { x: 45, y: 75, width: 300, height: 140, fontSize: 37 } },
				{ type: 'text', name: 'CTA copy', value: 'Book a visit, ask a question or check current availability.', desktop: { x: 350, y: 210, width: 740, height: 70, fontSize: 20 }, tablet: { x: 105, y: 220, width: 558, height: 75, fontSize: 19 }, mobile: { x: 50, y: 245, width: 290, height: 110, fontSize: 18 } },
				{ type: 'button', name: 'Primary action', value: 'Book a visit', href: '#', desktop: { x: 470, y: 305, width: 220, height: 56, fontSize: 16 }, tablet: { x: 145, y: 330, width: 220, height: 56, fontSize: 16 }, mobile: { x: 75, y: 400, width: 240, height: 58, fontSize: 16 }, backgroundColor: '#345b40', color: '#ffffff', borderRadius: 999 },
				{ type: 'button', name: 'Secondary action', value: 'Contact us', href: '#', desktop: { x: 750, y: 305, width: 220, height: 56, fontSize: 16 }, tablet: { x: 405, y: 330, width: 220, height: 56, fontSize: 16 }, mobile: { x: 75, y: 485, width: 240, height: 58, fontSize: 16 }, backgroundColor: '#ffffff', color: '#345b40', borderRadius: 999 }
			]
		},
		{
			id: 'faq-teaser',
			category: 'Actions',
			name: 'FAQ / information rows',
			description: 'A heading and four clean editable information rows.',
			height: { desktop: 560, tablet: 650, mobile: 820 },
			items: [
				{ type: 'text', name: 'FAQ heading', value: 'Questions families ask', desktop: { x: 70, y: 45, width: 600, height: 75, fontSize: 44 }, tablet: { x: 44, y: 35, width: 650, height: 70, fontSize: 39 }, mobile: { x: 24, y: 28, width: 342, height: 100, fontSize: 34 } },
				{ type: 'text', name: 'FAQ intro', value: 'Add your most helpful answer topics here.', desktop: { x: 780, y: 60, width: 520, height: 60, fontSize: 19 }, tablet: { x: 44, y: 120, width: 650, height: 60, fontSize: 18 }, mobile: { x: 24, y: 140, width: 342, height: 70, fontSize: 18 } },
				{ type: 'shape', name: 'Row 1', backgroundColor: '#eef5ec', borderRadius: 18, desktop: { x: 70, y: 165, width: 1300, height: 70 }, tablet: { x: 44, y: 210, width: 680, height: 75 }, mobile: { x: 24, y: 235, width: 342, height: 90 } },
				{ type: 'text', name: 'Question 1', value: 'What ages do you welcome?', desktop: { x: 105, y: 185, width: 1150, height: 38, fontSize: 20 }, tablet: { x: 75, y: 230, width: 580, height: 40, fontSize: 19 }, mobile: { x: 50, y: 260, width: 285, height: 45, fontSize: 18 } },
				{ type: 'shape', name: 'Row 2', backgroundColor: '#f8eee5', borderRadius: 18, desktop: { x: 70, y: 250, width: 1300, height: 70 }, tablet: { x: 44, y: 305, width: 680, height: 75 }, mobile: { x: 24, y: 345, width: 342, height: 90 } },
				{ type: 'text', name: 'Question 2', value: 'How do visits and settling-in work?', desktop: { x: 105, y: 270, width: 1150, height: 38, fontSize: 20 }, tablet: { x: 75, y: 325, width: 580, height: 40, fontSize: 19 }, mobile: { x: 50, y: 370, width: 285, height: 45, fontSize: 18 } },
				{ type: 'shape', name: 'Row 3', backgroundColor: '#f2ebf5', borderRadius: 18, desktop: { x: 70, y: 335, width: 1300, height: 70 }, tablet: { x: 44, y: 400, width: 680, height: 75 }, mobile: { x: 24, y: 455, width: 342, height: 90 } },
				{ type: 'text', name: 'Question 3', value: 'What does a typical day look like?', desktop: { x: 105, y: 355, width: 1150, height: 38, fontSize: 20 }, tablet: { x: 75, y: 420, width: 580, height: 40, fontSize: 19 }, mobile: { x: 50, y: 480, width: 285, height: 45, fontSize: 18 } },
				{ type: 'shape', name: 'Row 4', backgroundColor: '#eef5ec', borderRadius: 18, desktop: { x: 70, y: 420, width: 1300, height: 70 }, tablet: { x: 44, y: 495, width: 680, height: 75 }, mobile: { x: 24, y: 565, width: 342, height: 90 } },
				{ type: 'text', name: 'Question 4', value: 'How can I check availability?', desktop: { x: 105, y: 440, width: 1150, height: 38, fontSize: 20 }, tablet: { x: 75, y: 515, width: 580, height: 40, fontSize: 19 }, mobile: { x: 50, y: 590, width: 285, height: 45, fontSize: 18 } }
			]
		}
	];

	function duplicateSection(source) {
		var section = clone(source);
		var idMap = {};
		section.id = uid('section');
		section.name = (section.name || 'Section') + ' copy';
		(section.elements || []).forEach(function (element) {
			var oldId = element.id;
			element.id = uid(element.type || 'element');
			idMap[oldId] = element.id;
		});
		(section.groups || []).forEach(function (group) {
			var oldGroupId = group.id;
			var newGroupId = uid('group');
			group.id = newGroupId;
			group.elementIds = (group.elementIds || []).map(function (id) { return idMap[id]; }).filter(Boolean);
			(section.elements || []).forEach(function (element) {
				if (element.groupId === oldGroupId) {
					element.groupId = newGroupId;
				}
			});
		});
		return section;
	}

	function App() {
		var _loading = useState(true);
		var loading = _loading[0];
		var setLoading = _loading[1];
		var _error = useState('');
		var error = _error[0];
		var setError = _error[1];
		var _pages = useState([]);
		var pages = _pages[0];
		var setPages = _pages[1];
		var _pageView = useState('all');
		var pageView = _pageView[0];
		var setPageView = _pageView[1];
		var _organizer = useState({ version: 1, order: [], labels: {}, buckets: {}, hidden: [] });
		var organizer = _organizer[0];
		var setOrganizer = _organizer[1];
		var _pageMenu = useState(null);
		var pageMenu = _pageMenu[0];
		var setPageMenu = _pageMenu[1];
		var _sidebarOrders = useState(function () {
			try {
				return JSON.parse(window.localStorage.getItem('am-vb-sidebar-orders-v1') || '{}') || {};
			} catch (ignore) {
				return {};
			}
		});
		var sidebarOrders = _sidebarOrders[0];
		var setSidebarOrders = _sidebarOrders[1];
		var _slug = useState(config.initialSlug || '');
		var slug = _slug[0];
		var setSlug = _slug[1];
		var _document = useState(null);
		var documentState = _document[0];
		var setDocumentState = _document[1];
		var _selection = useState({ sectionId: null, elementIds: [] });
		var selection = _selection[0];
		var setSelection = _selection[1];
		var _device = useState('desktop');
		var device = _device[0];
		var setDevice = _device[1];
		var _responsiveScope = useState('all');
		var responsiveScope = _responsiveScope[0];
		var setResponsiveScope = _responsiveScope[1];
		var _leftTab = useState('structure');
		var leftTab = _leftTab[0];
		var setLeftTab = _leftTab[1];
		var _dirty = useState(false);
		var dirty = _dirty[0];
		var setDirty = _dirty[1];
		var _saving = useState(false);
		var saving = _saving[0];
		var setSaving = _saving[1];
		var _statusText = useState('Loading…');
		var statusText = _statusText[0];
		var setStatusText = _statusText[1];
		var _workflow = useState('draft');
		var workflow = _workflow[0];
		var setWorkflow = _workflow[1];
		var _revisions = useState([]);
		var revisions = _revisions[0];
		var setRevisions = _revisions[1];
		var _audit = useState([]);
		var audit = _audit[0];
		var setAudit = _audit[1];
		var _templates = useState([]);
		var templates = _templates[0];
		var setTemplates = _templates[1];
		var _media = useState([]);
		var media = _media[0];
		var setMedia = _media[1];
		var _features = useState({});
		var features = _features[0];
		var setFeatures = _features[1];
		var _lock = useState({ locked: false, owned: false });
		var lock = _lock[0];
		var setLock = _lock[1];
		var _readOnly = useState(false);
		var readOnly = _readOnly[0];
		var setReadOnly = _readOnly[1];
		var _readiness = useState([]);
		var readiness = _readiness[0];
		var setReadiness = _readiness[1];
		var _publication = useState({ hasSnapshot: false, hasUnpublishedChanges: false });
		var publication = _publication[0];
		var setPublication = _publication[1];
		var _recovery = useState(null);
		var recovery = _recovery[0];
		var setRecovery = _recovery[1];
		var _toast = useState('');
		var toast = _toast[0];
		var setToast = _toast[1];
		var _liveRegion = useState('home-hero');
		var liveRegion = _liveRegion[0];
		var setLiveRegion = _liveRegion[1];
		var _liveElement = useState(null);
		var liveElement = _liveElement[0];
		var setLiveElement = _liveElement[1];
		var _liveZoom = useState(0.6);
		var liveZoom = _liveZoom[0];
		var setLiveZoom = _liveZoom[1];
		var _liveFocus = useState(false);
		var liveFocus = _liveFocus[0];
		var setLiveFocus = _liveFocus[1];
		var _canvasMode = useState('edit');
		var canvasMode = _canvasMode[0];
		var setCanvasMode = _canvasMode[1];
		var _liveCanvasLoading = useState(true);
		var liveCanvasLoading = _liveCanvasLoading[0];
		var setLiveCanvasLoading = _liveCanvasLoading[1];
		var _liveCanvasError = useState('');
		var liveCanvasError = _liveCanvasError[0];
		var setLiveCanvasError = _liveCanvasError[1];
		var _liveBrowsePath = useState('');
		var liveBrowsePath = _liveBrowsePath[0];
		var setLiveBrowsePath = _liveBrowsePath[1];
		var _homeTemplateCategory = useState('All');
		var homeTemplateCategory = _homeTemplateCategory[0];
		var setHomeTemplateCategory = _homeTemplateCategory[1];
		var _homeDesign = useState({ version: 3, sections: {}, elements: {}, collections: {}, customSections: [] });
		var homeDesign = _homeDesign[0];
		var setHomeDesign = _homeDesign[1];
		var _homeDesignDefaults = useState({ version: 3, sections: {}, elements: {}, collections: {}, customSections: [] });
		var homeDesignDefaults = _homeDesignDefaults[0];
		var setHomeDesignDefaults = _homeDesignDefaults[1];
		var _homeDesignDirty = useState(false);
		var homeDesignDirty = _homeDesignDirty[0];
		var setHomeDesignDirty = _homeDesignDirty[1];
		var _homeDesignSaving = useState(false);
		var homeDesignSaving = _homeDesignSaving[0];
		var setHomeDesignSaving = _homeDesignSaving[1];
		var _homeDesignStatus = useState('Saved');
		var homeDesignStatus = _homeDesignStatus[0];
		var setHomeDesignStatus = _homeDesignStatus[1];
		var _savedSessionsOpen = useState(!!config.openSavedSessions);
		var savedSessionsOpen = _savedSessionsOpen[0];
		var setSavedSessionsOpen = _savedSessionsOpen[1];
		var _savedSessions = useState([]);
		var savedSessions = _savedSessions[0];
		var setSavedSessions = _savedSessions[1];
		var _savedSessionsLoading = useState(false);
		var savedSessionsLoading = _savedSessionsLoading[0];
		var setSavedSessionsLoading = _savedSessionsLoading[1];
		var _savedSessionsError = useState('');
		var savedSessionsError = _savedSessionsError[0];
		var setSavedSessionsError = _savedSessionsError[1];
		var _savedSessionQuery = useState('');
		var savedSessionQuery = _savedSessionQuery[0];
		var setSavedSessionQuery = _savedSessionQuery[1];
		var _savedSessionDate = useState('');
		var savedSessionDate = _savedSessionDate[0];
		var setSavedSessionDate = _savedSessionDate[1];
		var savedSessionDatePickerRef = useRef(null);
		var _saveSessionDialog = useState(false);
		var saveSessionDialog = _saveSessionDialog[0];
		var setSaveSessionDialog = _saveSessionDialog[1];
		var _saveSessionTitle = useState('');
		var saveSessionTitle = _saveSessionTitle[0];
		var setSaveSessionTitle = _saveSessionTitle[1];
		var _saveSessionError = useState('');
		var saveSessionError = _saveSessionError[0];
		var setSaveSessionError = _saveSessionError[1];
		var _saveSessionBusy = useState(false);
		var saveSessionBusy = _saveSessionBusy[0];
		var setSaveSessionBusy = _saveSessionBusy[1];
		var _restoreCandidate = useState(null);
		var restoreCandidate = _restoreCandidate[0];
		var setRestoreCandidate = _restoreCandidate[1];
		var _restoreSessionBusy = useState(false);
		var restoreSessionBusy = _restoreSessionBusy[0];
		var setRestoreSessionBusy = _restoreSessionBusy[1];

		var historyRef = useRef([]);
		var futureRef = useRef([]);
		var docRef = useRef(null);
		var slugRef = useRef(slug);
		var hashRef = useRef('');
		var dirtyRef = useRef(false);
		var savingRef = useRef(false);
		var sessionRef = useRef(sessionId());
		var editableRef = useRef(null);
		var initialTextRef = useRef('');
		var liveFrameRef = useRef(null);
		var homeDesignRef = useRef(homeDesign);
		var deviceRef = useRef(device);
		var responsiveScopeRef = useRef(responsiveScope);
		var liveFocusRef = useRef(liveFocus);
		var draggedPageRef = useRef('');
		var pageKeyboardSelectionRef = useRef(slug);
		var sidebarOrdersRef = useRef(sidebarOrders);
		var restorePromptRequestedRef = useRef(false);
		// Ctrl+C stores here rather than on the system clipboard: the payload is
		// a design record, not text, and the system clipboard has to stay free
		// for the client copying actual words in and out of the canvas.
		var clipboardRef = useRef(null);

		var activeExactRoute = exactRoute(slug);
		var liveParity = !!activeExactRoute;
		var availableLinks = useMemo(function () {
			var seen = {};
			return CORE_LINK_SUGGESTIONS.concat(pages.map(function (page) {
				return {
					label: 'home-poc' === page.slug ? 'Home' : page.title,
					href: 'home-poc' === page.slug ? '/' : '/' + page.slug
				};
			})).filter(function (suggestion) {
				if (!suggestion.href || seen[suggestion.href]) {
					return false;
				}
				seen[suggestion.href] = true;
				return true;
			});
		}, [pages]);
		var visibleSavedSessions = useMemo(function () {
			var query = String(savedSessionQuery || '').trim().toLowerCase();
			var targetDay = selectedDayNumber(savedSessionDate);
			return savedSessions.filter(function (item) {
				if (!query) {
					return true;
				}
				return [item.title, item.summary, item.pageTitle, item.route, item.author, item.authorRole]
					.join(' ')
					.toLowerCase()
					.indexOf(query) !== -1;
			}).sort(function (left, right) {
				if (null !== targetDay) {
					var leftDistance = Math.abs(calendarDayNumber(left.created) - targetDay);
					var rightDistance = Math.abs(calendarDayNumber(right.created) - targetDay);
					if (leftDistance !== rightDistance) {
						return leftDistance - rightDistance;
					}
				}
				return new Date(right.created).getTime() - new Date(left.created).getTime();
			});
		}, [savedSessions, savedSessionQuery, savedSessionDate]);

		function openLinkedPage(href) {
			var destination = String(href || '').trim();
			if (!destination || '#' === destination) {
				setToast('Choose a page first');
				return;
			}
			try {
				destination = new URL(destination, config.liveHomeUrl).href;
			} catch (ignore) {
				setToast('Enter a valid page link');
				return;
			}
			window.open(destination, '_blank', 'noopener');
		}

		function recoveryKey(pageSlug) {
			return 'am-vb-recovery-' + pageSlug;
		}

		function applyPayload(payload) {
			var next = normalizeDocument(payload.document);
			setDocumentState(next);
			docRef.current = next;
			hashRef.current = payload.hash || '';
			setWorkflow(payload.status || 'draft');
			setRevisions(payload.revisions || []);
			setAudit(payload.audit || []);
			setReadiness(payload.readinessIssues || []);
			setPublication(payload.publication || { hasSnapshot: false, hasUnpublishedChanges: false });
			setLock(payload.lock || { locked: false, owned: false });
			setDirty(false);
			dirtyRef.current = false;
			setStatusText('Saved ' + (payload.modifiedLabel || ''));
			historyRef.current = [];
			futureRef.current = [];
			if (next.sections[0]) {
				setSelection({ sectionId: next.sections[0].id, elementIds: [] });
			} else {
				setSelection({ sectionId: null, elementIds: [] });
			}
			var stored = null;
			try {
				stored = JSON.parse(window.localStorage.getItem(recoveryKey(payload.slug)) || 'null');
			} catch (ignore) {
				stored = null;
			}
			setRecovery(stored && stored.document && stored.baseHash !== payload.hash ? stored : null);
		}

		function acquireLock(pageSlug) {
			return apiFetch({
				url: config.root + 'page/' + pageSlug + '/lock',
				method: 'POST',
				data: { sessionId: sessionRef.current }
			}).then(function (payload) {
				setLock(payload.lock);
				setReadOnly(false);
				return payload.lock;
			}).catch(function (lockError) {
				var lockData = lockError && lockError.data ? lockError.data : {};
				setLock(lockData);
				setReadOnly(true);
				setStatusText(lockError.message || 'Read-only: page locked');
				return lockData;
			});
		}

		function designEndpoint(pageSlug) {
			return 'home-poc' === pageSlug ? 'home-design' : 'page-design/' + pageSlug;
		}

		function applyDesignPayload(payload, pageSlug) {
			if (!payload) {
				return;
			}
			var empty = { version: 'home-poc' === pageSlug ? 3 : 4, sections: {}, elements: {}, collections: {}, customSections: [] };
			var design = normaliseDesign(payload.design || empty);
			setHomeDesign(design);
			homeDesignRef.current = design;
			setHomeDesignDefaults(normaliseDesign(payload.defaults || empty));
			setHomeDesignDirty(false);
			setHomeDesignStatus('Saved');
			var regions = ('home-poc' === pageSlug ? LIVE_HOME_REGIONS : ((exactRoute(pageSlug) || {}).regions || [])).filter(function (region) {
				return region.editable !== false;
			});
			if ('home-poc' === pageSlug) {
				setLiveRegion('home-hero');
			} else if (regions[0]) {
				setLiveRegion(regions[0].id);
			}
			setLiveElement(null);
		}

		function loadPage(pageSlug) {
			if (!pageSlug) {
				setError('No visual page is available.');
				setLoading(false);
				return Promise.resolve();
			}
			setLoading(true);
			setError('');
			setSlug(pageSlug);
			slugRef.current = pageSlug;
			var designRequest = exactRoute(pageSlug)
				? apiFetch({ url: config.root + designEndpoint(pageSlug) })
				: Promise.resolve(null);
			return Promise.all([
				apiFetch({ url: config.root + 'page/' + pageSlug + '?sessionId=' + encodeURIComponent(sessionRef.current) }),
				designRequest
			]).then(function (results) {
				applyPayload(results[0]);
				applyDesignPayload(results[1], pageSlug);
				return acquireLock(pageSlug);
			}).catch(function (fetchError) {
				setError(fetchError.message || 'The visual page could not be opened.');
			}).finally(function () {
				setLoading(false);
			});
		}

		function loadSavedSessions(silent) {
			if (!silent) {
				setSavedSessionsLoading(true);
			}
			setSavedSessionsError('');
			return apiFetch({ url: config.root + 'saved-sessions' }).then(function (payload) {
				setSavedSessions(payload.sessions || []);
				return payload.sessions || [];
			}).catch(function (sessionsError) {
				setSavedSessionsError(sessionsError.message || 'Saved sessions could not be loaded');
				throw sessionsError;
			}).finally(function () {
				setSavedSessionsLoading(false);
			});
		}

		function openSavedSessions() {
			setSavedSessionsOpen(true);
			loadSavedSessions().catch(function () {});
		}

		function closeSavedSessions() {
			setSavedSessionsOpen(false);
		}

		function openSavedSessionPreview(item) {
			if (!item || !item.previewUrl) {
				setToast('This saved session has no preview route');
				return;
			}
			window.open(item.previewUrl, '_blank', 'noopener');
		}

		function deleteSavedSession(item) {
			if (!item || !window.confirm('Remove the saved session “' + item.title + '”? It will be moved to WordPress Trash.')) {
				return;
			}
			apiFetch({
				url: config.root + 'saved-sessions/' + item.id,
				method: 'DELETE'
			}).then(function () {
				setSavedSessions(function (current) {
					return current.filter(function (candidate) { return candidate.id !== item.id; });
				});
				setToast('Saved session removed from history');
			}).catch(function (deleteError) {
				setToast(deleteError.message || 'Saved session could not be removed');
			});
		}

		function clearRestoreQuery() {
			try {
				var url = new URL(window.location.href);
				url.searchParams.delete('am_vb_restore_session');
				window.history.replaceState({}, '', url.href);
			} catch (ignore) {
				// The restore is still safe if an older browser cannot clean the URL.
			}
		}

		function dismissSavedSessionRestore() {
			setRestoreCandidate(null);
			clearRestoreQuery();
		}

		function restoreSavedSession() {
			if (!restoreCandidate || restoreSessionBusy) {
				return;
			}
			setRestoreSessionBusy(true);
			return apiFetch({
				url: config.root + 'saved-sessions/' + restoreCandidate.id + '/restore',
				method: 'POST',
				data: { editorSessionId: sessionRef.current }
			}).then(function (payload) {
				setRestoreCandidate(null);
				clearRestoreQuery();
				return loadPage(payload.slug || slugRef.current).then(function () {
					setToast('Restored “' + restoreCandidate.title + '” — the replaced version was backed up');
					loadSavedSessions(true).catch(function () {});
				});
			}).catch(function (restoreError) {
				setToast(restoreError.message || 'Saved session could not be restored');
			}).finally(function () {
				setRestoreSessionBusy(false);
			});
		}

		useEffect(function () {
			Promise.all([
				apiFetch({ url: config.root + 'pages' }),
				apiFetch({ url: config.root + 'templates' }),
				apiFetch({ url: config.root + 'media' }),
				apiFetch({ url: config.root + 'features' }),
				apiFetch({ url: config.root + 'organizer' })
			]).then(function (results) {
				var nextPages = results[0].pages || [];
				setPages(nextPages);
				setTemplates(results[1].templates || []);
				setMedia(results[2].media || []);
				setFeatures(results[3].features || {});
				setOrganizer(results[4].organizer || { version: 1, order: [], labels: {}, buckets: {}, hidden: [] });
				var requested = config.initialSlug;
				if (!nextPages.some(function (page) { return page.slug === requested; })) {
					requested = nextPages[0] ? nextPages[0].slug : '';
				}
				return loadPage(requested);
			}).catch(function (bootstrapError) {
				setError(bootstrapError.message || 'The visual builder could not be opened.');
				setLoading(false);
			});
		}, []);

		useEffect(function () {
			if (config.openSavedSessions) {
				loadSavedSessions().catch(function () {});
			}
		}, []);

		useEffect(function () {
			if (loading || !config.restoreSavedSessionId || restorePromptRequestedRef.current) {
				return;
			}
			restorePromptRequestedRef.current = true;
			apiFetch({ url: config.root + 'saved-sessions/' + config.restoreSavedSessionId }).then(function (payload) {
				if (!payload.session || payload.session.slug !== slugRef.current) {
					throw new Error('Open the matching page before restoring this session');
				}
				setRestoreCandidate(payload.session);
			}).catch(function (restoreLookupError) {
				clearRestoreQuery();
				setToast(restoreLookupError.message || 'Saved session could not be opened');
			});
		}, [loading]);

		useEffect(function () {
			docRef.current = documentState;
		}, [documentState]);

		useEffect(function () {
			homeDesignRef.current = homeDesign;
		}, [homeDesign]);

		useEffect(function () {
			liveFocusRef.current = liveFocus;
			applyLiveCanvasFocus();
		}, [liveFocus, liveRegion]);

		useEffect(function () {
			deviceRef.current = device;
			var frame = liveFrameRef.current;
			if (frame && frame.contentDocument) {
				frame.contentDocument.documentElement.setAttribute('data-am-vb-device', device);
				applyHomeDesignToFrame(homeDesignRef.current);
			}
		}, [device]);

		useEffect(function () {
			responsiveScopeRef.current = responsiveScope;
		}, [responsiveScope]);

		useEffect(function () {
			dirtyRef.current = dirty;
		}, [dirty]);

		useEffect(function () {
			if (!toast) {
				return undefined;
			}
			var timer = window.setTimeout(function () { setToast(''); }, 2800);
			return function () { window.clearTimeout(timer); };
		}, [toast]);

		useEffect(function () {
			if (!liveParity) {
				return undefined;
			}
			function fitLiveCanvas() {
				var viewportWidth = 'desktop' === device ? 1440 : ('tablet' === device ? 768 : 390);
				var chromeWidth = 'view' === canvasMode ? 56 : ('templates' === leftTab ? 870 : 650);
				var available = Math.max(300, window.innerWidth - chromeWidth);
				setLiveZoom(Math.max(0.35, Math.min(1, available / viewportWidth)));
			}
			fitLiveCanvas();
			window.addEventListener('resize', fitLiveCanvas);

			return function () { window.removeEventListener('resize', fitLiveCanvas); };
		}, [liveParity, device, leftTab, canvasMode]);

		useEffect(function () {
			if (!features.locking || !slug) {
				return undefined;
			}
			var timer = window.setInterval(function () {
				if (!readOnly) {
					acquireLock(slugRef.current);
				}
			}, 45000);
			return function () { window.clearInterval(timer); };
		}, [features.locking, slug, readOnly]);

		useEffect(function () {
			function release() {
				if (!slugRef.current || readOnly) {
					return;
				}
				window.fetch(config.root + 'page/' + slugRef.current + '/lock', {
					method: 'DELETE',
					keepalive: true,
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce },
					body: JSON.stringify({ sessionId: sessionRef.current })
				});
			}
			window.addEventListener('beforeunload', release);
			return function () { window.removeEventListener('beforeunload', release); };
		}, [readOnly]);

		var flushActiveText = useCallback(function () {
			if (!editableRef.current || !editableRef.current.isConnected) {
				return;
			}
			editableRef.current.blur();
		}, []);

		var save = useCallback(function (mode) {
			if (!docRef.current || savingRef.current || readOnly) {
				return Promise.resolve();
			}
			flushActiveText();
			savingRef.current = true;
			setSaving(true);
			setStatusText('autosave' === mode ? 'Autosaving…' : 'Saving…');
			return apiFetch({
				url: config.root + 'page/' + slugRef.current,
				method: 'POST',
				data: {
					document: docRef.current,
					sessionId: sessionRef.current,
					baseHash: hashRef.current,
					mode: mode || 'manual'
				}
			}).then(function (payload) {
				var next = normalizeDocument(payload.document);
				setDocumentState(next);
				docRef.current = next;
				hashRef.current = payload.hash;
				setWorkflow(payload.status);
				setRevisions(payload.revisions || []);
				setAudit(payload.audit || []);
				setReadiness(payload.readinessIssues || []);
				setPublication(payload.publication || { hasSnapshot: false, hasUnpublishedChanges: false });
				setPages(function (current) {
					return current.map(function (page) {
						return page.slug === slugRef.current
							? Object.assign({}, page, { title: next.title, status: payload.status, modifiedLabel: payload.modifiedLabel })
							: page;
					});
				});
				setDirty(false);
				dirtyRef.current = false;
				window.localStorage.removeItem(recoveryKey(slugRef.current));
				setRecovery(null);
				setStatusText('Saved ' + (payload.modifiedLabel || 'just now'));
				if ('autosave' !== mode) {
					setToast('Changes saved');
				}
			}).catch(function (saveError) {
				setStatusText('Save failed');
				if (409 === (saveError && saveError.data && saveError.data.status)) {
					setReadOnly(true);
					setToast('Save conflict: your recovery copy is preserved');
				} else {
					setToast(saveError.message || 'Could not save changes');
				}
				throw saveError;
			}).finally(function () {
				savingRef.current = false;
				setSaving(false);
			});
		}, [flushActiveText, readOnly]);

		useEffect(function () {
			if (!dirty || !documentState || readOnly) {
				return undefined;
			}
			var recoveryTimer = window.setTimeout(function () {
				window.localStorage.setItem(recoveryKey(slugRef.current), JSON.stringify({
					savedAt: new Date().toISOString(),
					baseHash: hashRef.current,
					document: docRef.current
				}));
			}, 350);
			var autosaveTimer = window.setTimeout(function () {
				if (features.autosave) {
					save('autosave').catch(function () {});
				}
			}, 2200);
			return function () {
				window.clearTimeout(recoveryTimer);
				window.clearTimeout(autosaveTimer);
			};
		}, [documentState, dirty, readOnly, features.autosave, save]);

		var commit = useCallback(function (updater, message) {
			if (readOnly) {
				setToast('This page is read-only while another editing session holds the lock');
				return;
			}
			setDocumentState(function (current) {
				if (!current) {
					return current;
				}
				var before = clone(current);
				var next = clone(current);
				updater(next);
				historyRef.current = historyRef.current.concat([before]).slice(-100);
				futureRef.current = [];
				docRef.current = next;
				return next;
			});
			setDirty(true);
			dirtyRef.current = true;
			setStatusText(message || 'Unsaved changes');
		}, [readOnly]);

		function undo() {
			if (!historyRef.current.length || !docRef.current || readOnly) {
				return;
			}
			var previous = historyRef.current[historyRef.current.length - 1];
			historyRef.current = historyRef.current.slice(0, -1);
			futureRef.current = [clone(docRef.current)].concat(futureRef.current).slice(0, 100);
			setDocumentState(previous);
			docRef.current = previous;
			setDirty(true);
			dirtyRef.current = true;
			setStatusText('Undid last change');
		}

		function redo() {
			if (!futureRef.current.length || !docRef.current || readOnly) {
				return;
			}
			var next = futureRef.current[0];
			futureRef.current = futureRef.current.slice(1);
			historyRef.current = historyRef.current.concat([clone(docRef.current)]).slice(-100);
			setDocumentState(next);
			docRef.current = next;
			setDirty(true);
			dirtyRef.current = true;
			setStatusText('Redid change');
		}

		useEffect(function () {
			function onKeyDown(event) {
				var target = event.target;
				var editingField = target && target.closest && target.closest('input,textarea,select,[contenteditable="true"]');
				if (!editingField && !event.ctrlKey && !event.metaKey && !event.altKey && 'pages' === leftTab && ('ArrowUp' === event.key || 'ArrowDown' === event.key)) {
					var selectedPageSlug = pageKeyboardSelectionRef.current || slugRef.current;
					var selectedPage = pages.find(function (page) { return page.slug === selectedPageSlug; });
					if (selectedPage) {
						event.preventDefault();
						event.stopPropagation();
						movePageStep(selectedPage, 'ArrowUp' === event.key ? -1 : 1);
						return;
					}
				}
				if (!(event.ctrlKey || event.metaKey)) {
					return;
				}
				var key = event.key.toLowerCase();
				if ('s' === key) {
					event.preventDefault();
					if (liveParity) {
						requestNamedSave();
					} else {
						save('manual').catch(function () {});
					}
				} else if ('z' === key && !event.shiftKey) {
					event.preventDefault();
					undo();
				} else if ('z' === key && event.shiftKey || 'y' === key) {
					event.preventDefault();
					redo();
				} else if ('c' === key && liveParity && !readOnly) {
					// Only claim the keystroke when there is something to copy and
					// the client is not selecting words - otherwise ordinary text
					// copying inside the canvas would stop working.
					var documentSelection = (target && target.ownerDocument ? target.ownerDocument : document).getSelection();
					var selectingWords = documentSelection && !documentSelection.isCollapsed;
					if (liveElement && liveElement.key && !editingField && !selectingWords) {
						event.preventDefault();
						copySelectedElement();
					}
				} else if ('v' === key && liveParity && !readOnly && !editingField) {
					event.preventDefault();
					if (event.shiftKey) {
						pasteClipboardAppearance();
					} else {
						pasteClipboardElement();
					}
				} else if ('d' === key && liveParity && !readOnly && !editingField && liveElement && liveElement.key) {
					event.preventDefault();
					duplicateSelectedElement();
				}
			}

			// The canvas is a same-origin iframe with its own key events, so a
			// shortcut pressed while the client is looking at the page they just
			// clicked would otherwise go nowhere.
			var frame = liveFrameRef.current;
			var frameDocument = frame && frame.contentDocument;
			window.addEventListener('keydown', onKeyDown);
			if (frameDocument) {
				frameDocument.addEventListener('keydown', onKeyDown);
			}
			return function () {
				window.removeEventListener('keydown', onKeyDown);
				if (frameDocument) {
					frameDocument.removeEventListener('keydown', onKeyDown);
				}
			};
		});

		var selected = useMemo(function () {
			var section = documentState && documentState.sections.find(function (item) {
				return item.id === selection.sectionId;
			});
			var elements = section ? section.elements.filter(function (item) {
				return selection.elementIds.indexOf(item.id) !== -1;
			}) : [];
			return { section: section, elements: elements, element: 1 === elements.length ? elements[0] : null };
		}, [documentState, selection]);

		function liveRegionData(regionId) {
			var regions = homeRegionList();
			return regions.find(function (region) { return region.id === regionId; }) || regions[0] || LIVE_HOME_REGIONS[0];
		}

		function homeRegionList(source) {
			var model = source || homeDesignRef.current || homeDesign;
			var custom = model && Array.isArray(model.customSections) ? model.customSections : [];
			var baseRegions = 'home-poc' === slug
				? LIVE_HOME_REGIONS
				: ((exactRoute(slug) || {}).regions || []);
			var output = [];
			baseRegions.forEach(function (region) {
				output.push(region);
				custom.filter(function (section) { return section.after === region.id; }).forEach(function (section) {
					output.push({
						id: 'custom-' + section.id,
						label: section.name || 'Custom section',
						kind: 'Custom design section · ' + (section.items || []).length + ' layers',
						detail: 'A reusable free-design canvas. Every layer can be moved and resized independently on Desktop, Tablet and Mobile.',
						custom: true,
						sectionId: section.id
					});
				});
			});
			return output;
		}

		function homeSectionData(regionId, source) {
			var model = source || homeDesign;
			return model && model.sections && model.sections[regionId]
				? model.sections[regionId]
				: {};
		}

		function homeElementData(elementKey, source) {
			var model = source || homeDesignRef.current || homeDesign;
			return model && model.elements && model.elements[elementKey]
				? model.elements[elementKey]
				: {};
		}

		function homeCustomSection(sectionId, source) {
			var model = source || homeDesignRef.current || homeDesign;
			return model && Array.isArray(model.customSections)
				? model.customSections.find(function (section) { return section.id === sectionId; })
				: null;
		}

		function homeCustomItem(sectionId, itemId, source) {
			var section = homeCustomSection(sectionId, source);
			return section && Array.isArray(section.items)
				? section.items.find(function (item) { return item.id === itemId; })
				: null;
		}

		function editableNodes(frameDocument, elementKey) {
			if (!frameDocument || !elementKey) {
				return [];
			}
			return Array.prototype.slice.call(frameDocument.querySelectorAll('[data-am-vb-editable="' + elementKey + '"]'));
		}

		function applyHomeElementToFrame(frameDocument, elementKey, item) {
			var nodes = editableNodes(frameDocument, elementKey);
			if (!nodes.length) {
				return;
			}
			nodes.forEach(function (node) {
				var domType = node.getAttribute('data-am-vb-edit-type') || item.type || 'text';
				var hasTransform = false;
				['desktop', 'tablet', 'mobile'].forEach(function (breakpoint) {
					var transformValues = item[breakpoint] || {};
					hasTransform = hasTransform || Number(transformValues.scale || 100) !== 100 || Number(transformValues.offsetX || 0) !== 0 || Number(transformValues.offsetY || 0) !== 0;
					node.style.setProperty('--am-element-scale-' + breakpoint, Number(transformValues.scale || 100) / 100);
					node.style.setProperty('--am-element-offset-x-' + breakpoint, Number(transformValues.offsetX || 0) + 'px');
					node.style.setProperty('--am-element-offset-y-' + breakpoint, Number(transformValues.offsetY || 0) + 'px');
				});
				node.toggleAttribute('data-am-vb-has-transform', hasTransform);
				// Everything beyond the three legacy transform values is applied by
				// the shared schema renderer, so the canvas and the real site can
				// never disagree about what a saved design looks like.
				var sharedStyle = frameDocument.defaultView && frameDocument.defaultView.AMVBStyleApply;
				if (sharedStyle) {
					sharedStyle.applyToNode(node, domType, item);
				}
				if (['text', 'textarea', 'button', 'tabs'].indexOf(domType) !== -1) {
					if ((undefined !== item.value && '' !== item.value) || node.hasAttribute('data-am-vb-custom-element')) {
						node.textContent = item.value;
						node.setAttribute('data-am-vb-has-value', 'true');
					}
					var textLink = node.matches('a') ? node : node.closest('a');
					if (item.href && textLink) {
						textLink.setAttribute('href', item.href);
					}
					if (item.href) {
						node.setAttribute('data-am-vb-link-href', item.href);
					} else {
						node.removeAttribute('data-am-vb-link-href');
					}
					if (item.linkDescription) {
						node.setAttribute('title', item.linkDescription);
					} else {
						node.removeAttribute('title');
					}
					return;
				}
				if ('shape' === domType || 'frame' === domType) {
					return;
				}
				var mediaLink = node.matches('a') ? node : node.closest('a');
				if (item.href && mediaLink) {
					mediaLink.setAttribute('href', item.href);
				}
				if (item.linkDescription) {
					node.setAttribute('title', item.linkDescription);
					node.setAttribute('aria-label', item.linkDescription);
				}

				node.style.setProperty('--am-media-position-x', Number(item.positionX || 50) + '%');
				node.style.setProperty('--am-media-position-y', Number(item.positionY || 50) + '%');
				['desktop', 'tablet', 'mobile'].forEach(function (breakpoint) {
					var values = item[breakpoint] || {};
					node.style.setProperty('--am-media-scale-' + breakpoint, Number(values.scale || 100) / 100);
					node.style.setProperty('--am-media-offset-x-' + breakpoint, Number(values.offsetX || 0) + 'px');
					node.style.setProperty('--am-media-offset-y-' + breakpoint, Number(values.offsetY || 0) + 'px');
				});

				if (!item.src) {
					return;
				}
				if ('video' === domType && 'VIDEO' === node.tagName) {
					var source = node.querySelector('source');
					if (source && source.getAttribute('src') !== item.src) {
						source.setAttribute('src', item.src);
						node.load();
						node.play().catch(function () {});
					}
					return;
				}
				var image = 'IMG' === node.tagName ? node : node.querySelector('img');
				if (!image) {
					image = frameDocument.createElement('img');
					image.className = 'h-full w-full object-contain';
					node.insertBefore(image, node.firstChild || null);
				}
				Array.prototype.forEach.call(node.querySelectorAll('svg'), function (icon) {
					icon.style.display = 'none';
				});
				Array.prototype.forEach.call(node.querySelectorAll('[data-am-vb-fallback-icon]'), function (icon) {
					icon.style.display = 'none';
				});
				Array.prototype.forEach.call(node.querySelectorAll('.am-home-custom-placeholder'), function (placeholder) {
					placeholder.style.display = 'none';
				});
				image.setAttribute('src', item.src);
				image.setAttribute('alt', item.alt || '');
			});
		}

		function setCustomItemStyles(node, item) {
			['desktop', 'tablet', 'mobile'].forEach(function (breakpoint) {
				var values = item[breakpoint] || {};
				['x', 'y', 'width', 'height', 'fontSize'].forEach(function (key) {
					if (undefined !== values[key]) {
						node.style.setProperty('--am-custom-' + key + '-' + breakpoint, Number(values[key]) + 'px');
					}
				});
			});
			if (item.color) {
				node.style.color = item.color;
			}
			if (item.backgroundColor && ['shape', 'button'].indexOf(item.type) !== -1) {
				node.style.backgroundColor = item.backgroundColor;
			}
			node.style.borderRadius = Number(item.borderRadius || 0) + 'px';
		}

		function prepareEditableNode(node, key, type, label, fallback) {
			node.setAttribute('data-am-vb-editable', key);
			node.setAttribute('data-am-vb-edit-type', type);
			node.setAttribute('data-am-vb-label', label || titleCase(type));
			node.setAttribute('data-am-vb-fallback', fallback || '');
			return node;
		}

		function renderCollectionExtras(frameDocument, design) {
			Array.prototype.forEach.call(frameDocument.querySelectorAll('[data-am-vb-collection-extra="true"]'), function (node) { node.remove(); });
			var collections = design.collections || {};
			var elements = design.elements || {};

			function textNode(tag, key, type, label, fallback, className) {
				var node = prepareEditableNode(frameDocument.createElement(tag), key, type, label, fallback);
				node.className = className || '';
				node.textContent = elements[key] && elements[key].value ? elements[key].value : fallback;
				return node;
			}

			var featureGrid = frameDocument.querySelector('[data-am-vb-region="home-feature-links"] .am-home-variable-grid, [data-am-vb-region="home-feature-links"] .am-home-grid');
			(collections.features || []).forEach(function (item, index) {
				if (!featureGrid) { return; }
				var prefix = 'feature-extra-' + item.id;
				var card = frameDocument.createElement('div');
				card.className = 'flex flex-col items-center text-center';
				card.setAttribute('data-am-vb-collection-extra', 'true');
				card.appendChild(textNode('h2', prefix + '-title', 'text', 'Extra feature heading', 'New feature', 'font-heading text-2xl font-medium text-sage-800'));
				var media = prepareEditableNode(frameDocument.createElement('span'), prefix + '-image', 'image', 'Extra feature photo', '');
				media.className = 'am-vb-editable-media am-home-feature-media hexagon mx-auto mt-4 block aspect-[1.1547/1] w-52 overflow-hidden bg-sage-50';
				media.setAttribute('data-am-vb-media-mode', 'crop');
				var mediaItem = elements[prefix + '-image'] || {};
				var image = frameDocument.createElement('img');
				image.className = 'h-full w-full object-cover';
				image.src = mediaItem.src || '/wp-content/themes/alexandra-theme/dist/assets/organisation/classroom-main.webp';
				image.alt = mediaItem.alt || 'Extra feature';
				media.appendChild(image);
				card.appendChild(media);
				var link = textNode('a', prefix + '-cta', 'text', 'Extra feature link', 'View more', 'mt-4 font-body text-[1.05rem] font-medium text-sage-700');
				link.href = elements[prefix + '-cta'] && elements[prefix + '-cta'].href ? elements[prefix + '-cta'].href : '#';
				card.appendChild(link);
				var icon = prepareEditableNode(frameDocument.createElement('span'), prefix + '-icon', 'logo', 'Extra feature icon', '');
				icon.className = 'am-vb-editable-media mt-3 flex h-12 w-12 items-center justify-center text-sage-700';
				icon.setAttribute('data-am-vb-media-mode', 'move');
				icon.setAttribute('data-am-vb-fallback-icon', 'true');
				icon.textContent = '◇';
				card.appendChild(icon);
				featureGrid.appendChild(card);
			});
			if (featureGrid) { featureGrid.style.setProperty('--am-home-columns', Math.min(featureGrid.children.length, 4)); }

			var benefitGrid = frameDocument.querySelector('[data-am-vb-region="home-benefits"] .am-home-variable-grid, [data-am-vb-region="home-benefits"] .am-home-grid');
			(collections.benefits || []).forEach(function (item) {
				if (!benefitGrid) { return; }
				var key = 'benefit-extra-' + item.id + '-text';
				var benefit = frameDocument.createElement('div');
				benefit.className = 'flex flex-col items-center text-center';
				benefit.setAttribute('data-am-vb-collection-extra', 'true');
				var icon = prepareEditableNode(frameDocument.createElement('span'), 'benefit-extra-' + item.id + '-icon', 'logo', 'Extra benefit icon', '');
				icon.className = 'am-vb-editable-media flex h-12 w-12 items-center justify-center text-sage-700';
				icon.setAttribute('data-am-vb-media-mode', 'move');
				icon.setAttribute('data-am-vb-fallback-icon', 'true');
				icon.textContent = '♡';
				benefit.appendChild(icon);
				benefit.appendChild(textNode('p', key, 'textarea', 'Extra benefit text', 'Add your benefit text', 'am-home-benefit-copy mt-4 max-w-[15rem] font-body text-lg font-medium leading-snug text-sage-800'));
				benefitGrid.appendChild(benefit);
			});
			if (benefitGrid) { benefitGrid.style.setProperty('--am-home-columns', Math.min(benefitGrid.children.length, 4)); }

			var trustGrid = frameDocument.querySelector('[data-am-vb-region="home-trust"] .am-home-trust-grid');
			(collections.trust || []).forEach(function (item) {
				if (!trustGrid) { return; }
				var prefix = 'trust-extra-' + item.id;
				var trust = frameDocument.createElement('div');
				trust.className = 'grid grid-cols-[2.25rem_1fr] items-center gap-4 text-left text-sage-800 sm:grid-cols-1 sm:justify-items-center sm:text-center';
				trust.setAttribute('data-am-vb-collection-extra', 'true');
				var logo = prepareEditableNode(frameDocument.createElement('span'), prefix + '-logo', 'logo', 'Extra trust logo', '');
				logo.className = 'am-vb-editable-media am-home-trust-icon-wrap flex h-8 w-8 items-center justify-center';
				logo.setAttribute('data-am-vb-media-mode', 'move');
				var logoItem = elements[prefix + '-logo'] || {};
				if (logoItem.src) {
					var logoImage = frameDocument.createElement('img');
					logoImage.src = logoItem.src;
					logoImage.alt = logoItem.alt || '';
					logoImage.className = 'h-full w-full object-contain';
					logo.appendChild(logoImage);
				} else {
					var shield = frameDocument.createElement('span');
					shield.setAttribute('data-am-vb-fallback-icon', 'true');
					shield.textContent = '♢';
					logo.appendChild(shield);
				}
				trust.appendChild(logo);
				trust.appendChild(textNode('span', prefix + '-label', 'text', 'Extra trust label', 'Accreditation', 'font-body text-sm font-medium uppercase leading-snug tracking-wide'));
				trustGrid.appendChild(trust);
			});
			if (trustGrid) { trustGrid.style.setProperty('--am-home-columns', Math.min(trustGrid.children.length, 5)); }
		}

		function renderCustomSections(frameDocument, design) {
			Array.prototype.forEach.call(frameDocument.querySelectorAll('section[data-am-vb-custom-section-id]'), function (node) { node.remove(); });
			var insertionPoints = {};
			(design.customSections || []).forEach(function (section) {
				var anchor = insertionPoints[section.after] || frameDocument.querySelector('[data-am-vb-region="' + section.after + '"]');
				if (!anchor || !anchor.parentNode) { return; }
				var node = frameDocument.createElement('section');
				node.className = 'am-home-design-region am-home-custom-section';
				node.setAttribute('data-am-vb-region', 'custom-' + section.id);
				node.setAttribute('data-am-vb-custom-section-id', section.id);
				node.style.backgroundColor = section.backgroundColor || '#f7faf5';
				node.style.display = false === section.visible ? 'none' : '';
				['desktop', 'tablet', 'mobile'].forEach(function (breakpoint) {
					node.style.setProperty('--am-custom-height-' + breakpoint, Number((section[breakpoint] || {}).height || 420) + 'px');
				});
				var stage = frameDocument.createElement('div');
				stage.className = 'am-home-custom-stage';
				(section.items || []).forEach(function (item) {
					var content = (design.elements || {})[item.key] || {};
					var itemNode;
					if ('button' === item.type) {
						itemNode = frameDocument.createElement('a');
						itemNode.href = content.href || '#';
						itemNode.textContent = content.value || 'Learn more';
					} else if ('image' === item.type || 'logo' === item.type) {
						itemNode = frameDocument.createElement('div');
						itemNode.setAttribute('data-am-vb-media-mode', 'move');
						if (content.src) {
							var image = frameDocument.createElement('img');
							image.src = content.src;
							image.alt = content.alt || '';
							itemNode.appendChild(image);
						} else {
							var placeholder = frameDocument.createElement('span');
							placeholder.className = 'am-home-custom-placeholder';
							placeholder.textContent = 'logo' === item.type ? 'Add logo' : 'Add photo';
							itemNode.appendChild(placeholder);
						}
					} else if ('shape' === item.type) {
						itemNode = frameDocument.createElement('span');
					} else {
						itemNode = frameDocument.createElement('div');
						itemNode.textContent = content.value || ('tabs' === item.type ? 'Overview | Learning | Care' : 'Edit this text');
					}
					prepareEditableNode(itemNode, item.key, item.type, item.name, content.value || '');
					itemNode.setAttribute('data-am-vb-custom-element', item.id);
					itemNode.setAttribute('data-am-vb-custom-owner', section.id);
					itemNode.className += ' am-home-custom-item am-home-custom-' + item.type;
					if ('logo' === item.type) { itemNode.className += ' am-vb-editable-media am-home-custom-media is-logo'; }
					if ('image' === item.type) { itemNode.className += ' am-vb-editable-media am-home-custom-media'; }
					if ('shape' === item.type) { itemNode.className += ' is-' + (item.shape || 'rectangle'); }
					setCustomItemStyles(itemNode, item);
					stage.appendChild(itemNode);
				});
				node.appendChild(stage);
				anchor.parentNode.insertBefore(node, anchor.nextSibling);
				insertionPoints[section.after] = node;
			});
		}

		function renderHomeStructureToFrame(frameDocument, design) {
			var structureHash = JSON.stringify({ collections: design.collections || {}, customSections: design.customSections || [] });
			if (frameDocument.__amVbStructureHash === structureHash) {
				return false;
			}
			renderCollectionExtras(frameDocument, design);
			renderCustomSections(frameDocument, design);
			frameDocument.__amVbStructureHash = structureHash;
			return true;
		}

		function applyHomeDesignToFrame(design) {
			var frame = liveFrameRef.current;
			var frameDocument;
			var variableNames = {
				height: 'height',
				paddingY: 'padding-y',
				gap: 'gap',
				contentWidth: 'content-width',
				mediaSize: 'media-size',
				textSize: 'text-size',
				imageSize: 'image-size',
				iconSize: 'icon-size'
			};
			try {
				frameDocument = frame && frame.contentDocument;
			} catch (ignore) {
				frameDocument = null;
			}
			if (!frameDocument || !design || !design.sections) {
				return;
			}
			frameDocument.documentElement.setAttribute('data-am-vb-device', deviceRef.current);
			Object.keys(design.sections).forEach(function (regionId) {
				var section = design.sections[regionId] || {};
				var nodes = frameDocument.querySelectorAll('[data-am-vb-region="' + regionId + '"]');
				if (!nodes.length) {
					return;
				}
				Array.prototype.forEach.call(nodes, function (node) {
					var gapNode = node.getAttribute('data-am-vb-layout-gap-for') === regionId
						? node
						: (node.querySelector('[data-am-vb-layout-gap-for="' + regionId + '"]') || node);
					var widthNode = node.getAttribute('data-am-vb-layout-content-width-for') === regionId
						? node
						: (node.querySelector('[data-am-vb-layout-content-width-for="' + regionId + '"]') || node);
					node.style.display = false === section.visible ? 'none' : '';
					if (section.backgroundColor) {
						node.style.setProperty('--am-home-background', section.backgroundColor);
						node.style.setProperty('--am-vb-page-background', section.backgroundColor);
						if ('home-poc' !== slugRef.current) {
							node.style.backgroundColor = section.backgroundColor;
						}
					}
					['tint', 'texture', 'overlay'].forEach(function (key) {
						if (undefined !== section[key]) {
							node.style.setProperty('--am-home-' + key, Number(section[key]));
						}
					});
					['desktop', 'tablet', 'mobile'].forEach(function (breakpoint) {
						var values = section[breakpoint] || {};
						if ('home-poc' !== slugRef.current) {
							[node, gapNode, widthNode].forEach(function (target) {
								['padding', 'gap', 'content-width'].forEach(function (marker) {
									target.removeAttribute('data-am-vb-' + marker + '-' + breakpoint);
								});
							});
							if (Number(values.paddingY) > 0) {
								node.style.setProperty('--am-vb-page-padding-y-' + breakpoint, Number(values.paddingY) + 'px');
								node.setAttribute('data-am-vb-padding-' + breakpoint, 'true');
							} else {
								node.style.removeProperty('--am-vb-page-padding-y-' + breakpoint);
							}
							if (Number(values.gap) > 0) {
								gapNode.style.setProperty('--am-vb-page-gap-' + breakpoint, Number(values.gap) + 'px');
								gapNode.setAttribute('data-am-vb-gap-' + breakpoint, 'true');
							} else {
								gapNode.style.removeProperty('--am-vb-page-gap-' + breakpoint);
							}
							if (Number(values.contentWidth) > 0) {
								widthNode.style.setProperty('--am-vb-page-content-width-' + breakpoint, Number(values.contentWidth) + 'px');
								widthNode.setAttribute('data-am-vb-content-width-' + breakpoint, 'true');
							} else {
								widthNode.style.removeProperty('--am-vb-page-content-width-' + breakpoint);
							}
						}
						Object.keys(variableNames).forEach(function (key) {
							if (undefined !== values[key]) {
								node.style.setProperty(
									'--am-home-' + variableNames[key] + '-' + breakpoint,
									Number(values[key]) + 'px'
								);
							}
						});
					});
					// The layout values above target inner nodes and the historical
					// --am-home-* names, so they stay here. Everything the schema
					// owns outright - radius, border, shadow, hover, animation,
					// opacity - is written by the shared applier, which means a
					// section renders identically on the canvas and on the real page.
					var sharedStyle = frameDocument.defaultView && frameDocument.defaultView.AMVBStyleApply;
					if (sharedStyle) {
						sharedStyle.applyToNode(node, 'section', section);
					}
				});
			});
			var structureChanged = renderHomeStructureToFrame(frameDocument, design);
			Object.keys(design.elements || {}).forEach(function (elementKey) {
				applyHomeElementToFrame(frameDocument, elementKey, design.elements[elementKey] || {});
			});
			var heroVideo = frameDocument.querySelector('[data-am-vb-editable="hero-video"]');
			var heroPoster = design.elements && design.elements['hero-poster'];
			if (heroVideo && heroPoster && heroPoster.src) {
				heroVideo.setAttribute('poster', heroPoster.src);
			}
			if (structureChanged && liveElement && liveElement.key) {
				window.setTimeout(function () { highlightLiveElement(liveElement.key); }, 0);
			}
			applyLiveCanvasFocus();
		}

		function updateHomeDesign(updater, message, skipFrame) {
			setHomeDesign(function (current) {
				// normaliseDesign must run *before* clone: cloning an array that
				// carries named properties is exactly what silently discarded them.
				var next = clone(normaliseDesign(current || { version: 3, sections: {}, elements: {}, collections: {}, customSections: [] }));
				updater(next);
				if (!skipFrame) {
					applyHomeDesignToFrame(next);
				}
				homeDesignRef.current = next;
				return next;
			});
			setHomeDesignDirty(true);
			setHomeDesignStatus(message || 'Unsaved changes');
		}

		function updateHomeElementValue(elementKey, key, value, skipFrame) {
			updateHomeDesign(function (next) {
				next.elements = next.elements || {};
				next.elements[elementKey] = next.elements[elementKey] || {};
				next.elements[elementKey][key] = value;
			}, 'Unsaved Home content changes', skipFrame);
		}

		function responsiveDevices(targetDevice) {
			return 'all' === responsiveScopeRef.current
				? ['desktop', 'tablet', 'mobile']
				: [targetDevice || deviceRef.current || 'desktop'];
		}

		function updateHomeElementDeviceValue(elementKey, key, value, targetDevice) {
			var activeDevice = targetDevice || device;
			updateHomeDesign(function (next) {
				next.elements = next.elements || {};
				next.elements[elementKey] = next.elements[elementKey] || {};
				responsiveDevices(activeDevice).forEach(function (breakpoint) {
					next.elements[elementKey][breakpoint] = next.elements[elementKey][breakpoint] || {};
					next.elements[elementKey][breakpoint][key] = value;
				});
			}, 'all' === responsiveScopeRef.current ? 'Unsaved all-device changes' : 'Unsaved ' + titleCase(activeDevice) + ' changes');
		}

		function resetHomeElement(elementKey) {
			var baseline = homeElementData(elementKey, homeDesignDefaults);
			var selectedSnapshot = liveElement && liveElement.key === elementKey ? liveElement : null;
			updateHomeDesign(function (next) {
				next.elements = next.elements || {};
				next.elements[elementKey] = clone(baseline);
				if ('hero-video' === elementKey && homeDesignDefaults.elements && homeDesignDefaults.elements['hero-poster']) {
					next.elements['hero-poster'] = clone(homeDesignDefaults.elements['hero-poster']);
				}
			}, 'Element reset - save to keep it');
			var frame = liveFrameRef.current;
			var frameDocument = frame && frame.contentDocument;
			if (frameDocument && selectedSnapshot) {
				editableNodes(frameDocument, elementKey).forEach(function (node) {
					if ('text' === selectedSnapshot.type || 'textarea' === selectedSnapshot.type) {
						node.textContent = selectedSnapshot.fallbackValue || '';
						return;
					}
					var fallbackSrc = selectedSnapshot.fallbackSrc || '';
					if ('video' === selectedSnapshot.type) {
						var source = node.querySelector('source');
						if (source && fallbackSrc) {
							source.setAttribute('src', fallbackSrc);
							node.load();
							node.play().catch(function () {});
						}
						return;
					}
					var image = 'IMG' === node.tagName ? node : node.querySelector('img');
					if (fallbackSrc && image) {
						image.setAttribute('src', fallbackSrc);
						image.setAttribute('alt', selectedSnapshot.fallbackAlt || '');
					} else if (!fallbackSrc && image && 'logo' === selectedSnapshot.type) {
						image.remove();
						Array.prototype.forEach.call(node.querySelectorAll('svg'), function (icon) { icon.style.display = ''; });
					}
				});
			}
			setLiveElement(function (current) {
				return current && current.key === elementKey
					? Object.assign({}, current, {
						value: current.fallbackValue || '',
						src: current.fallbackSrc || '',
						alt: current.fallbackAlt || '',
						href: '',
						linkDescription: ''
					})
					: current;
			});
		}

		function chooseHomeMedia(elementKey, mediaType) {
			var isVideo = 'video' === mediaType;
			var frame = wp.media({
				title: isVideo ? 'Choose a Home video' : 'Choose a Home image or logo',
				button: { text: isVideo ? 'Use this video' : 'Use this image' },
				library: { type: isVideo ? 'video' : 'image' },
				multiple: false
			});
			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				var src = !isVideo && attachment.sizes && attachment.sizes.large
					? attachment.sizes.large.url
					: attachment.url;
				updateHomeDesign(function (next) {
					next.elements = next.elements || {};
					next.elements[elementKey] = next.elements[elementKey] || {};
					next.elements[elementKey].src = src;
					// Keep the attachment ID beside the URL. The server renders
					// from the ID, which is what lets a design keep its pictures
					// when it moves between local, staging and production.
					next.elements[elementKey].srcId = attachment.id || 0;
					if (!isVideo) {
						next.elements[elementKey].alt = attachment.alt || attachment.title || '';
					}
				}, 'Unsaved media replacement');
				setLiveElement(function (current) {
					return current && current.key === elementKey
						? Object.assign({}, current, { src: src, alt: attachment.alt || attachment.title || '' })
						: current;
				});
				window.setTimeout(function () { highlightLiveElement(elementKey); }, 30);
			});
			frame.open();
		}

		function updateHomeSectionValue(regionId, key, value) {
			updateHomeDesign(function (next) {
				next.sections = next.sections || {};
				next.sections[regionId] = next.sections[regionId] || {};
				next.sections[regionId][key] = value;
			}, 'Unsaved Localhost changes');
		}

		function updateHomeDeviceValue(regionId, key, value) {
			updateHomeDesign(function (next) {
				next.sections = next.sections || {};
				next.sections[regionId] = next.sections[regionId] || {};
				responsiveDevices(device).forEach(function (breakpoint) {
					next.sections[regionId][breakpoint] = next.sections[regionId][breakpoint] || {};
					next.sections[regionId][breakpoint][key] = value;
				});
			}, 'all' === responsiveScopeRef.current ? 'Unsaved all-device changes' : 'Unsaved ' + titleCase(device) + ' changes');
		}

		function homeMediaRecord(label, type) {
			return {
				type: type,
				label: label,
				src: '',
				alt: '',
				positionX: 50,
				positionY: 50,
				desktop: { scale: 100, offsetX: 0, offsetY: 0 },
				tablet: { scale: 100, offsetX: 0, offsetY: 0 },
				mobile: { scale: 100, offsetX: 0, offsetY: 0 }
			};
		}

		function addHomeCollectionItem(collectionName) {
			var id = uid('item');
			updateHomeDesign(function (next) {
				next.collections = next.collections || {};
				next.collections[collectionName] = next.collections[collectionName] || [];
				next.elements = next.elements || {};
				next.collections[collectionName].push({
					id: id,
					icon: 'benefits' === collectionName ? 'Sprout' : ('trust' === collectionName ? 'ShieldCheck' : 'Image')
				});
				if ('features' === collectionName) {
					next.elements['feature-extra-' + id + '-title'] = { type: 'text', label: 'Extra feature heading', value: 'New feature' };
					next.elements['feature-extra-' + id + '-cta'] = { type: 'text', label: 'Extra feature link', value: 'View more', href: '#' };
					next.elements['feature-extra-' + id + '-image'] = homeMediaRecord('Extra feature photo', 'image');
					next.elements['feature-extra-' + id + '-icon'] = homeMediaRecord('Extra feature icon', 'logo');
				} else if ('benefits' === collectionName) {
					next.elements['benefit-extra-' + id + '-text'] = { type: 'textarea', label: 'Extra benefit text', value: 'Add your benefit text' };
					next.elements['benefit-extra-' + id + '-icon'] = homeMediaRecord('Extra benefit icon', 'logo');
				} else {
					next.elements['trust-extra-' + id + '-label'] = { type: 'text', label: 'Extra trust label', value: 'Accreditation' };
					next.elements['trust-extra-' + id + '-logo'] = homeMediaRecord('Extra trust logo', 'logo');
				}
			}, 'New Home item added - edit it in the canvas');
			setLeftTab('structure');
			window.setTimeout(function () {
				selectLiveRegion('features' === collectionName ? 'home-feature-links' : ('benefits' === collectionName ? 'home-benefits' : 'home-trust'), false);
			}, 0);
		}

		function collectionInfoFromElementKey(key) {
			var match = String(key || '').match(/^(feature|benefit|trust)-extra-(item-[a-z0-9-]+)-(?:title|cta|image|icon|text|label|logo)$/);
			return match ? {
				collection: 'feature' === match[1] ? 'features' : ('benefit' === match[1] ? 'benefits' : 'trust'),
				id: match[2]
			} : null;
		}

		function removeHomeCollectionItem(collectionName, itemId) {
			if (!window.confirm('Remove this added Home item?')) { return; }
			updateHomeDesign(function (next) {
				next.collections[collectionName] = (next.collections[collectionName] || []).filter(function (item) { return item.id !== itemId; });
				var prefix = ('features' === collectionName ? 'feature' : ('benefits' === collectionName ? 'benefit' : 'trust')) + '-extra-' + itemId + '-';
				Object.keys(next.elements || {}).forEach(function (key) {
					if (0 === key.indexOf(prefix)) { delete next.elements[key]; }
				});
			}, 'Added Home item removed');
			setLiveElement(null);
		}

		function moveHomeCollectionItem(collectionName, itemId, direction) {
			updateHomeDesign(function (next) {
				var items = (next.collections && next.collections[collectionName]) || [];
				var index = items.findIndex(function (item) { return item.id === itemId; });
				var targetIndex = index + direction;
				if (index < 0 || targetIndex < 0 || targetIndex >= items.length) { return; }
				var moved = items.splice(index, 1)[0];
				items.splice(targetIndex, 0, moved);
			}, 'Added item moved ' + (direction < 0 ? 'up' : 'down') + ' one place');
		}

		function customItemRecord(type, sectionId, seed, index) {
			var source = seed || {};
			var id = uid(type);
			var key = 'custom-' + sectionId + '-' + id;
			var defaultWidth = ['image', 'logo'].indexOf(type) !== -1 ? 260 : ('shape' === type ? 180 : 360);
			var defaultHeight = 'image' === type ? 240 : ('logo' === type ? 120 : ('shape' === type ? 120 : ('button' === type ? 56 : 100)));
			var baseX = 40 + ((index || 0) % 4) * 34;
			var baseY = 40 + ((index || 0) % 5) * 34;
			var item = {
				id: id,
				key: key,
				type: type,
				name: source.name || titleCase(type),
				shape: source.shape || 'rectangle',
				backgroundColor: source.backgroundColor || ('button' === type ? '#345b40' : '#a9c6a2'),
				color: source.color || ('button' === type ? '#ffffff' : '#20372a'),
				borderRadius: undefined !== source.borderRadius ? source.borderRadius : (['image', 'logo'].indexOf(type) !== -1 ? 20 : 0),
				desktop: Object.assign({ x: baseX, y: baseY, width: defaultWidth, height: defaultHeight, fontSize: 'text' === type ? 28 : 16 }, source.desktop || {}),
				tablet: Object.assign({ x: 36, y: baseY, width: Math.min(defaultWidth, 680), height: defaultHeight, fontSize: 'text' === type ? 26 : 16 }, source.tablet || {}),
				mobile: Object.assign({ x: 24, y: baseY, width: Math.min(defaultWidth, 342), height: defaultHeight, fontSize: 'text' === type ? 24 : 16 }, source.mobile || {})
			};
			var element;
			if (['image', 'logo'].indexOf(type) !== -1) {
				element = homeMediaRecord(item.name, type);
				if (source.src) { element.src = source.src; }
				// Carry the attachment ID with the URL, or a duplicated layer
				// loses the portable half of its media reference.
				if (source.srcId) { element.srcId = source.srcId; }
				if (source.alt) { element.alt = source.alt; }
			} else if ('shape' === type) {
				element = { type: 'shape', label: item.name, value: '' };
			} else {
				element = {
					type: ['text', 'tabs'].indexOf(type) !== -1 ? 'textarea' : 'text',
					label: item.name,
					value: undefined !== source.value ? source.value : ('tabs' === type ? 'Overview\nLearning\nCare' : ('button' === type ? 'Learn more' : 'Edit this text'))
				};
				if ('button' === type) { element.href = source.href || '#'; }
			}
			return { item: item, element: element };
		}

		function insertHomeTemplate(template) {
			var sectionId = uid('section');
			var selectedRegion = liveRegionData(liveRegion);
			var editableRegions = homeRegionList().filter(function (region) {
				return !region.custom && region.editable !== false;
			});
			var fallbackAnchor = editableRegions.length ? editableRegions[editableRegions.length - 1].id : '';
			var after = selectedRegion.custom
				? (homeCustomSection(selectedRegion.sectionId) || {}).after
				: (selectedRegion.editable !== false ? liveRegion : fallbackAnchor);
			var createdItems = [];
			var createdElements = {};
			(template.items || []).forEach(function (source, index) {
				var created = customItemRecord(source.type || 'text', sectionId, source, index);
				createdItems.push(created.item);
				createdElements[created.item.key] = created.element;
			});
			updateHomeDesign(function (next) {
				next.customSections = next.customSections || [];
				next.elements = next.elements || {};
				next.customSections.push({
					id: sectionId,
					name: template.name || 'Custom section',
					visible: true,
					after: after,
					backgroundColor: template.backgroundColor || '#f7faf5',
					desktop: { height: Number((template.height || {}).desktop || 420) },
					tablet: { height: Number((template.height || {}).tablet || 420) },
					mobile: { height: Number((template.height || {}).mobile || 520) },
					items: createdItems
				});
				Object.keys(createdElements).forEach(function (key) { next.elements[key] = createdElements[key]; });
			}, (template.name || 'Template') + ' added to ' + ((exactRoute(slug) || {}).title || 'this page'));
			setLeftTab('structure');
			setLiveElement(null);
			setLiveRegion('custom-' + sectionId);
			window.setTimeout(function () { highlightLiveRegion('custom-' + sectionId, true); }, 40);

			// Callers that need to keep working on what they just inserted -
			// duplicate and paste both do - would otherwise have to guess the
			// generated keys.
			return { sectionId: sectionId, items: createdItems };
		}

		function insertSavedTemplateOnHome(template) {
			var section = template && template.section ? template.section : {};
			var genericItems = (section.elements || []).map(function (element, index) {
				var type = ['text', 'image', 'button', 'shape'].indexOf(element.type) !== -1 ? element.type : 'text';
				var styles = element.styles && element.styles.desktop ? element.styles.desktop : {};
				function pixels(value, fallback) {
					var parsed = parseInt(value, 10);
					return Number.isFinite(parsed) ? parsed : fallback;
				}
				return {
					type: type,
					name: element.name || titleCase(type),
					value: 'button' === type ? element.label : (element.content || 'Edit this text'),
					href: element.href || '#',
					src: element.src || '',
					alt: element.alt || '',
					shape: element.shape || 'rectangle',
					backgroundColor: styles.backgroundColor,
					color: styles.color,
					borderRadius: pixels(styles.borderRadius, 16),
					desktop: { x: pixels(styles.positionX, 60 + index * 30), y: pixels(styles.positionY, 60 + index * 30), width: pixels(styles.width, 360), height: pixels(styles.height, 'image' === type ? 240 : 100), fontSize: pixels(styles.fontSize, 20) }
				};
			});
			insertHomeTemplate({
				name: template.name || section.name || 'Saved template',
				description: 'Converted from a saved page section',
				height: { desktop: 520, tablet: 620, mobile: Math.max(620, 180 + genericItems.length * 120) },
				items: genericItems
			});
		}

		function addCustomItem(type, sectionId) {
			var section = homeCustomSection(sectionId);
			if (!section) { return; }
			var created = customItemRecord(type, sectionId, {}, (section.items || []).length);
			updateHomeDesign(function (next) {
				var target = next.customSections.find(function (candidate) { return candidate.id === sectionId; });
				target.items = target.items || [];
				target.items.push(created.item);
				next.elements = next.elements || {};
				next.elements[created.item.key] = created.element;
			}, titleCase(type) + ' layer added');
			window.setTimeout(function () {
				var frame = liveFrameRef.current;
				var node = frame && frame.contentDocument ? frame.contentDocument.querySelector('[data-am-vb-editable="' + created.item.key + '"]') : null;
				if (node) { selectLiveElement(node, 'text' === type); }
			}, 40);
		}

		function updateCustomSection(sectionId, field, value) {
			updateHomeDesign(function (next) {
				var section = next.customSections.find(function (candidate) { return candidate.id === sectionId; });
				if (section) { section[field] = value; }
			}, 'Custom section updated');
		}

		function updateCustomSectionDevice(sectionId, field, value) {
			updateHomeDesign(function (next) {
				var section = next.customSections.find(function (candidate) { return candidate.id === sectionId; });
				if (section) {
					responsiveDevices(device).forEach(function (breakpoint) {
						section[breakpoint] = section[breakpoint] || {};
						section[breakpoint][field] = value;
					});
				}
			}, 'Custom section layout updated');
		}

		function updateCustomItem(sectionId, itemId, field, value, targetDevice) {
			updateHomeDesign(function (next) {
				var section = next.customSections.find(function (candidate) { return candidate.id === sectionId; });
				var item = section && section.items ? section.items.find(function (candidate) { return candidate.id === itemId; }) : null;
				if (!item) { return; }
				if (targetDevice) {
					responsiveDevices(targetDevice).forEach(function (breakpoint) {
						item[breakpoint] = item[breakpoint] || {};
						item[breakpoint][field] = value;
					});
				} else {
					item[field] = value;
				}
			}, 'Custom layer updated');
		}

		function deleteCustomItem(sectionId, itemId) {
			var item = homeCustomItem(sectionId, itemId);
			if (!item) { return; }
			updateHomeDesign(function (next) {
				var section = next.customSections.find(function (candidate) { return candidate.id === sectionId; });
				section.items = section.items.filter(function (candidate) { return candidate.id !== itemId; });
				delete next.elements[item.key];
			}, 'Layer deleted');
			setLiveElement(null);
		}

		function duplicateCustomItem(sectionId, itemId) {
			var sourceItem = homeCustomItem(sectionId, itemId);
			if (!sourceItem) { return; }
			var sourceElement = homeElementData(sourceItem.key);
			var copy = customItemRecord(sourceItem.type, sectionId, sourceItem, (homeCustomSection(sectionId).items || []).length);
			copy.item.name = (sourceItem.name || titleCase(sourceItem.type)) + ' copy';
			copy.item.desktop.x = Math.min(1400, Number(sourceItem.desktop.x || 0) + 24);
			copy.item.desktop.y = Math.min(1100, Number(sourceItem.desktop.y || 0) + 24);
			copy.element = clone(sourceElement);
			copy.element.label = copy.item.name;
			updateHomeDesign(function (next) {
				var section = next.customSections.find(function (candidate) { return candidate.id === sectionId; });
				section.items.push(copy.item);
				next.elements[copy.item.key] = copy.element;
			}, 'Layer duplicated');
			selectElementByKey(copy.item.key);

			return copy.item.key;
		}

		/**
		 * Select whatever the builder just created, so a duplicate or a paste
		 * leaves the client looking at the new thing rather than the old one.
		 *
		 * @param {string} elementKey Design key of the new element.
		 */
		function selectElementByKey(elementKey) {
			window.setTimeout(function () {
				var frame = liveFrameRef.current;
				var frameDocument = frame && frame.contentDocument;
				var node = frameDocument
					? frameDocument.querySelector('[data-am-vb-editable="' + elementKey + '"]')
					: null;
				if (node) {
					selectLiveElement(node, false);
				}
			}, 60);
		}

		/**
		 * Every property name the style schema owns, flattened across the base
		 * toolkit and every type pack.
		 *
		 * @returns {object} Property definitions keyed by name.
		 */
		function schemaProperties() {
			var schema = config.styleSchema || {};
			var names = {};
			Object.keys(schema.base || {}).forEach(function (name) {
				names[name] = schema.base[name];
			});
			Object.keys(schema.packs || {}).forEach(function (pack) {
				Object.keys(schema.packs[pack] || {}).forEach(function (name) {
					names[name] = schema.packs[pack][name];
				});
			});

			return names;
		}

		/**
		 * The appearance half of an element record - everything the schema owns,
		 * and nothing that is content. Copying a styled heading onto a photo must
		 * carry the shadow and the corner radius, never the words.
		 *
		 * @param {object} record Element design record.
		 * @returns {object} Schema-owned properties only.
		 */
		function designOnly(record) {
			var names = schemaProperties();
			var source = record || {};
			var picked = {};
			Object.keys(names).forEach(function (name) {
				if (names[name].device || undefined === source[name]) {
					return;
				}
				picked[name] = source[name];
			});
			['desktop', 'tablet', 'mobile'].forEach(function (breakpoint) {
				var bucket = source[breakpoint];
				if (!bucket) {
					return;
				}
				Object.keys(names).forEach(function (name) {
					if (!names[name].device || undefined === bucket[name]) {
						return;
					}
					picked[breakpoint] = picked[breakpoint] || {};
					picked[breakpoint][name] = bucket[name];
				});
			});

			return picked;
		}

		/**
		 * Merge an appearance onto an existing element without touching its
		 * content or the device buckets it already has values in.
		 *
		 * @param {string} elementKey Target element.
		 * @param {object} design     Output of designOnly().
		 * @param {string} message    Status line.
		 */
		function applyDesignToElement(elementKey, design, message) {
			updateHomeDesign(function (next) {
				next.elements = next.elements || {};
				next.elements[elementKey] = next.elements[elementKey] || {};
				var target = next.elements[elementKey];
				Object.keys(design || {}).forEach(function (name) {
					if (['desktop', 'tablet', 'mobile'].indexOf(name) !== -1) {
						target[name] = Object.assign({}, target[name] || {}, design[name]);

						return;
					}
					target[name] = design[name];
				});
			}, message || 'Appearance pasted');
		}

		/**
		 * A free-layer seed describing a fixed page element.
		 *
		 * Free layers are the only structure the runtime can create on an
		 * arbitrary page, so a copy of a form label or a page heading becomes a
		 * free layer. That keeps "duplicate" honest: the copy is a real thing
		 * that renders on the public page, not a builder-only ghost.
		 *
		 * @param {object} record  Element design record.
		 * @param {object} details Live element metadata (label, type, fallbacks).
		 * @returns {object} Seed accepted by customItemRecord().
		 */
		function layerSeed(record, details) {
			var data = record || {};
			var info = details || {};
			var type = data.type || info.type || 'text';
			var seedType = ['image', 'logo'].indexOf(type) !== -1
				? type
				: (['frame', 'shape'].indexOf(type) !== -1 ? 'shape' : ('button' === type ? 'button' : 'text'));

			return {
				type: seedType,
				name: (info.label || data.label || titleCase(seedType)) + ' copy',
				value: undefined !== data.value && '' !== data.value ? data.value : info.value,
				href: data.href || info.href,
				src: data.src || info.src,
				alt: data.alt || info.alt
			};
		}

		/**
		 * Copy a fixed page element into a free layer.
		 *
		 * Repeated copies from the same band land in the same host section
		 * rather than spawning a new band each time, which would bury the page
		 * under one-layer strips.
		 *
		 * @param {string} elementKey Source element.
		 * @param {object} details    Live element metadata.
		 * @returns {string} Key of the created layer, or ''.
		 */
		function copyElementIntoLayer(elementKey, details) {
			var record = homeElementData(elementKey);
			var seed = layerSeed(record, details);
			var design = designOnly(record);
			var region = liveRegionData(liveRegion);
			var anchor = region.custom ? (homeCustomSection(region.sectionId) || {}).after : liveRegion;
			var hostName = 'Copied items';
			var host = (homeDesignRef.current || homeDesign).customSections.find(function (section) {
				return section.name === hostName && section.after === anchor;
			});

			if (!host) {
				var inserted = insertHomeTemplate({
					name: hostName,
					height: { desktop: 320, tablet: 320, mobile: 380 },
					items: [seed]
				});
				if (!inserted || !inserted.items.length) {
					return '';
				}
				var freshKey = inserted.items[0].key;
				if (Object.keys(design).length) {
					applyDesignToElement(freshKey, design, 'Element duplicated');
				}
				selectElementByKey(freshKey);

				return freshKey;
			}

			var created = customItemRecord(seed.type, host.id, seed, (host.items || []).length);
			created.element.label = seed.name;
			updateHomeDesign(function (next) {
				var section = next.customSections.find(function (candidate) { return candidate.id === host.id; });
				section.items = section.items || [];
				section.items.push(created.item);
				next.elements = next.elements || {};
				next.elements[created.item.key] = Object.assign({}, created.element, design);
			}, 'Element duplicated');
			setLiveRegion('custom-' + host.id);
			selectElementByKey(created.item.key);

			return created.item.key;
		}

		/**
		 * Duplicate whatever is selected, choosing the mechanism that actually
		 * exists for that kind of thing.
		 */
		function duplicateSelectedElement() {
			if (!liveElement || !liveElement.key) {
				return;
			}
			if (liveElement.custom) {
				duplicateCustomItem(liveElement.customSectionId, liveElement.customItemId);
				setToast('Layer duplicated');

				return;
			}
			var collectionInfo = collectionInfoFromElementKey(liveElement.key);
			if (collectionInfo) {
				duplicateHomeCollectionItem(collectionInfo.collection, collectionInfo.id);

				return;
			}
			if (copyElementIntoLayer(liveElement.key, liveElement)) {
				setToast('Copied into a free layer you can drag anywhere');
			}
		}

		/**
		 * Duplicate an added Home collection card, content and appearance.
		 *
		 * @param {string} collectionName features | benefits | trust
		 * @param {string} itemId         Source item id.
		 */
		function duplicateHomeCollectionItem(collectionName, itemId) {
			var model = homeDesignRef.current || homeDesign;
			var source = ((model.collections || {})[collectionName] || []).find(function (item) {
				return item.id === itemId;
			});
			if (!source) {
				return;
			}
			var prefix = ('features' === collectionName ? 'feature' : ('benefits' === collectionName ? 'benefit' : 'trust')) + '-extra-';
			var newId = uid('item');
			updateHomeDesign(function (next) {
				next.collections[collectionName] = next.collections[collectionName] || [];
				next.collections[collectionName].push(Object.assign({}, clone(source), { id: newId }));
				Object.keys(next.elements || {}).forEach(function (key) {
					if (0 !== key.indexOf(prefix + itemId + '-')) {
						return;
					}
					next.elements[key.replace(prefix + itemId + '-', prefix + newId + '-')] = clone(next.elements[key]);
				});
			}, 'Item duplicated');
			setToast('Item duplicated');
		}

		/**
		 * "Remove" for a fixed page element.
		 *
		 * The markup belongs to the React page, so the design model cannot
		 * delete the node - but `hidden` takes it off the public page on every
		 * breakpoint, which is what removing means to a visitor. It is written
		 * to all three devices deliberately: a client pressing Remove does not
		 * mean "only on the size I happen to be previewing".
		 *
		 * @param {string} elementKey Target element.
		 * @param {boolean} hidden    Desired state.
		 */
		function setElementHidden(elementKey, hidden) {
			updateHomeDesign(function (next) {
				next.elements = next.elements || {};
				next.elements[elementKey] = next.elements[elementKey] || {};
				['desktop', 'tablet', 'mobile'].forEach(function (breakpoint) {
					next.elements[elementKey][breakpoint] = next.elements[elementKey][breakpoint] || {};
					next.elements[elementKey][breakpoint].hidden = !!hidden;
				});
			}, hidden ? 'Item removed from the page' : 'Item restored to the page');
			setToast(hidden ? 'Removed from the page - press Restore to bring it back' : 'Restored to the page');
		}

		function elementIsHidden(record) {
			var data = record || {};

			return ['desktop', 'tablet', 'mobile'].every(function (breakpoint) {
				return !!(data[breakpoint] || {}).hidden;
			});
		}

		/**
		 * Ctrl+C - put the selected element on the builder clipboard.
		 *
		 * Both halves are kept: the content so a paste can reproduce the thing,
		 * and the appearance separately so Ctrl+Shift+V can carry the styling
		 * onto something else without disturbing its words.
		 *
		 * @returns {boolean} Whether anything was copied.
		 */
		function copySelectedElement() {
			if (!liveElement || !liveElement.key) {
				return false;
			}
			var record = homeElementData(liveElement.key);
			clipboardRef.current = {
				label: liveElement.label || record.label || 'Element',
				seed: layerSeed(record, liveElement),
				design: designOnly(record)
			};
			setToast('Copied “' + clipboardRef.current.label + '” · Ctrl+V to paste a copy, Ctrl+Shift+V for its look only');

			return true;
		}

		/**
		 * Ctrl+V - paste the clipboard as a new free layer.
		 *
		 * @returns {boolean} Whether anything was pasted.
		 */
		function pasteClipboardElement() {
			var entry = clipboardRef.current;
			if (!entry) {
				setToast('Nothing copied yet - select an element and press Ctrl+C');

				return false;
			}
			var region = liveRegionData(liveRegion);
			var anchor = region.custom ? (homeCustomSection(region.sectionId) || {}).after : liveRegion;
			var hostName = 'Copied items';
			var host = (homeDesignRef.current || homeDesign).customSections.find(function (section) {
				return section.name === hostName && section.after === anchor;
			});
			var created;
			if (!host) {
				var inserted = insertHomeTemplate({
					name: hostName,
					height: { desktop: 320, tablet: 320, mobile: 380 },
					items: [entry.seed]
				});
				if (!inserted || !inserted.items.length) {
					return false;
				}
				if (Object.keys(entry.design).length) {
					applyDesignToElement(inserted.items[0].key, entry.design, 'Pasted');
				}
				selectElementByKey(inserted.items[0].key);
				setToast('Pasted “' + entry.label + '”');

				return true;
			}
			created = customItemRecord(entry.seed.type, host.id, entry.seed, (host.items || []).length);
			created.element.label = entry.seed.name;
			updateHomeDesign(function (next) {
				var section = next.customSections.find(function (candidate) { return candidate.id === host.id; });
				section.items = section.items || [];
				section.items.push(created.item);
				next.elements = next.elements || {};
				next.elements[created.item.key] = Object.assign({}, created.element, entry.design);
			}, 'Pasted');
			setLiveRegion('custom-' + host.id);
			selectElementByKey(created.item.key);
			setToast('Pasted “' + entry.label + '”');

			return true;
		}

		/**
		 * Ctrl+Shift+V - carry the copied appearance onto the selected element
		 * and leave its content alone.
		 *
		 * @returns {boolean} Whether anything was applied.
		 */
		function pasteClipboardAppearance() {
			var entry = clipboardRef.current;
			if (!entry || !liveElement || !liveElement.key) {
				setToast(entry ? 'Select something to paste the look onto' : 'Nothing copied yet');

				return false;
			}
			if (liveElement.hardProtected) {
				setToast('This global component is edited once for the whole site');

				return false;
			}
			applyDesignToElement(liveElement.key, entry.design, 'Appearance pasted');
			setToast('Look of “' + entry.label + '” applied');

			return true;
		}

		/**
		 * Remove whatever is selected, again choosing the real mechanism.
		 */
		function removeSelectedElement() {
			if (!liveElement || !liveElement.key) {
				return;
			}
			if (liveElement.custom) {
				deleteCustomItem(liveElement.customSectionId, liveElement.customItemId);

				return;
			}
			var collectionInfo = collectionInfoFromElementKey(liveElement.key);
			if (collectionInfo) {
				removeHomeCollectionItem(collectionInfo.collection, collectionInfo.id);

				return;
			}
			setElementHidden(liveElement.key, true);
		}

		function moveCustomItem(sectionId, itemId, direction) {
			updateHomeDesign(function (next) {
				var section = next.customSections.find(function (candidate) { return candidate.id === sectionId; });
				var index = section.items.findIndex(function (candidate) { return candidate.id === itemId; });
				var target = Math.max(0, Math.min(section.items.length - 1, index + direction));
				var item = section.items.splice(index, 1)[0];
				section.items.splice(target, 0, item);
			}, direction > 0 ? 'Layer brought forward' : 'Layer sent backward');
		}

		function duplicateCustomSection(sectionId) {
			var source = homeCustomSection(sectionId);
			if (!source) { return; }
			var template = { name: (source.name || 'Custom section') + ' copy', backgroundColor: source.backgroundColor, height: {}, items: [] };
			['desktop', 'tablet', 'mobile'].forEach(function (breakpoint) { template.height[breakpoint] = (source[breakpoint] || {}).height; });
			template.items = (source.items || []).map(function (item) {
				var content = homeElementData(item.key);
				return Object.assign({}, clone(item), {
					value: content.value,
					href: content.href,
					src: content.src,
					alt: content.alt
				});
			});
			insertHomeTemplate(template);
		}

		function deleteCustomSection(sectionId) {
			var section = homeCustomSection(sectionId);
			if (!section || !window.confirm('Delete "' + (section.name || 'Custom section') + '" and all of its layers?')) { return; }
			updateHomeDesign(function (next) {
				next.customSections = next.customSections.filter(function (candidate) { return candidate.id !== sectionId; });
				(section.items || []).forEach(function (item) { delete next.elements[item.key]; });
			}, 'Custom section deleted');
			setLiveElement(null);
			setLiveRegion(section.after || 'home-trust');
		}

		function resetHomeSection(regionId) {
			var baseline = homeSectionData(regionId, homeDesignDefaults);
			updateHomeDesign(function (next) {
				next.sections = next.sections || {};
				next.sections[regionId] = clone(baseline);
			}, 'Section reset — save to keep it');
		}

		function saveHomeDesign(sessionTitle) {
			if (!homeDesignDirty || homeDesignSaving) {
				return Promise.resolve();
			}
			setHomeDesignSaving(true);
			setHomeDesignStatus('Saving…');
			// The ref, not the render closure: a save fired from a keyboard
			// shortcut or a timer would otherwise post whatever the model looked
			// like when that handler was created.
			var outgoing = normaliseDesign(homeDesignRef.current || homeDesign);
			return apiFetch({
				url: config.root + designEndpoint(slugRef.current),
				method: 'POST',
				data: { design: outgoing, sessionTitle: String(sessionTitle || '').trim() }
			}).then(function (payload) {
				var saved = normaliseDesign(payload.design || outgoing);
				setHomeDesign(saved);
				homeDesignRef.current = saved;
				setHomeDesignDefaults(normaliseDesign(payload.defaults || homeDesignDefaults));
				setHomeDesignDirty(false);
				setHomeDesignStatus('Saved');
				if (payload.savedSession) {
					setSavedSessions(function (current) {
						return [payload.savedSession].concat(current.filter(function (item) {
							return item.id !== payload.savedSession.id;
						}));
					});
					setToast('Saved session “' + payload.savedSession.title + '”');
				} else {
					setToast('Saved');
				}
				var frame = liveFrameRef.current;
				if (frame && frame.contentWindow) {
					frame.contentWindow.location.reload();
				}
				return payload;
			}).catch(function (designError) {
				setHomeDesignStatus('Save failed');
				setToast(designError.message || 'Page design could not be saved');
				throw designError;
			}).finally(function () {
				setHomeDesignSaving(false);
			});
		}

		function requestNamedSave() {
			if (!liveParity) {
				save('manual').catch(function () {});
				return;
			}
			if (!homeDesignDirty) {
				setToast('Everything is already saved');
				return;
			}
			setSaveSessionTitle('');
			setSaveSessionError('');
			setSaveSessionDialog(true);
		}

		function confirmNamedSave() {
			var title = String(saveSessionTitle || '').trim();
			if (!title) {
				setSaveSessionError('Name this saved session before continuing');
				return;
			}
			if (saveSessionBusy) {
				return;
			}
			setSaveSessionBusy(true);
			setSaveSessionError('');
			saveHomeDesign(title).then(function () {
				setSaveSessionDialog(false);
				setSaveSessionTitle('');
			}).catch(function (saveError) {
				setSaveSessionError(saveError.message || 'This saved session could not be created');
			}).finally(function () {
				setSaveSessionBusy(false);
			});
		}

		function applyLiveCanvasFocus() {
			var frame = liveFrameRef.current;
			var frameDocument;
			try {
				frameDocument = frame && frame.contentDocument;
			} catch (ignore) {
				frameDocument = null;
			}
			if (!frameDocument) {
				return;
			}
			Array.prototype.forEach.call(frameDocument.querySelectorAll('[data-am-vb-focus-hidden]'), function (node) {
				node.removeAttribute('data-am-vb-focus-hidden');
			});
			if (!liveFocusRef.current) {
				return;
			}
			Array.prototype.forEach.call(frameDocument.querySelectorAll('[data-am-vb-region]'), function (node) {
				if (node.getAttribute('data-am-vb-region') !== liveRegion) {
					node.setAttribute('data-am-vb-focus-hidden', 'true');
				}
			});
			var selectedRegion = frameDocument.querySelector('[data-am-vb-region="' + liveRegion + '"]');
			if (selectedRegion) {
				selectedRegion.scrollIntoView({ block: 'start' });
			}
		}

		function highlightLiveRegion(regionId, shouldScroll) {
			var frame = liveFrameRef.current;
			var frameDocument;
			try {
				frameDocument = frame && frame.contentDocument;
			} catch (ignore) {
				frameDocument = null;
			}
			if (!frameDocument) {
				return;
			}
			Array.prototype.forEach.call(frameDocument.querySelectorAll('[data-am-vb-selected]'), function (node) {
				node.removeAttribute('data-am-vb-selected');
			});
			var region = frameDocument.querySelector('[data-am-vb-region="' + regionId + '"]');
			if (!region) {
				if ('cookie-control' === regionId) {
					setToast('Cookie control is hidden because consent has already been saved in this browser');
				}
				return;
			}
			region.setAttribute('data-am-vb-selected', 'true');
			if (shouldScroll) {
				region.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		}

		function clearLiveElementHighlights(frameDocument) {
			Array.prototype.forEach.call(frameDocument.querySelectorAll('[data-am-vb-edit-selected]'), function (node) {
				node.removeAttribute('data-am-vb-edit-selected');
			});
			Array.prototype.forEach.call(frameDocument.querySelectorAll('.am-vb-media-resize-handle'), function (node) {
				node.remove();
			});
			Array.prototype.forEach.call(frameDocument.querySelectorAll('.am-vb-media-move-handle'), function (node) {
				node.remove();
			});
			Array.prototype.forEach.call(frameDocument.querySelectorAll('[contenteditable="true"][data-am-vb-editable]'), function (node) {
				node.removeAttribute('contenteditable');
			});
		}

		function highlightLiveElement(elementKey, preferredNode) {
			var frame = liveFrameRef.current;
			var frameDocument;
			try {
				frameDocument = frame && frame.contentDocument;
			} catch (ignore) {
				frameDocument = null;
			}
			if (!frameDocument) {
				return;
			}
			clearLiveElementHighlights(frameDocument);
			var nodes = editableNodes(frameDocument, elementKey);
			nodes.forEach(function (node) { node.setAttribute('data-am-vb-edit-selected', 'true'); });
			var node = preferredNode && preferredNode.isConnected ? preferredNode : nodes.find(function (candidate) {
				return candidate.getClientRects().length > 0;
			});
			if (!node) {
				return;
			}
			var type = node.getAttribute('data-am-vb-edit-type');
			if ('video' !== type && ['IMG', 'INPUT', 'BR', 'HR'].indexOf(node.tagName) === -1) {
				// Corners scale proportionally, edges stretch a single axis, and
				// the stalk above rotates. Every handle keeps the original class
				// so existing hit-detection and teardown still find them.
				var titles = {
					rotate: 'Drag to rotate',
					e: 'Drag to change width', w: 'Drag to change width',
					n: 'Drag to change height', s: 'Drag to change height'
				};
				// Text has no height property - a forced height only parks empty
				// space under the words - so it gets no vertical handles. A
				// handle that cannot change anything is worse than no handle.
				var isTextRun = ['text', 'textarea'].indexOf(type) !== -1;
				var directions = isTextRun
					? ['nw', 'ne', 'e', 'se', 'sw', 'w', 'rotate']
					: ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w', 'rotate'];
				directions.forEach(function (direction) {
					var handle = frameDocument.createElement('span');
					handle.className = 'am-vb-media-resize-handle am-vb-handle--' + direction;
					handle.setAttribute('data-am-vb-handle', direction);
					handle.setAttribute('aria-hidden', 'true');
					handle.setAttribute('contenteditable', 'false');
					handle.title = titles[direction] || 'Drag to resize';
					node.appendChild(handle);
				});
			}
		}

		function selectedElementSnapshot(node) {
			var key = node.getAttribute('data-am-vb-editable');
			var type = node.getAttribute('data-am-vb-edit-type') || 'text';
			var item = homeElementData(key);
			var image = 'IMG' === node.tagName ? node : node.querySelector('img');
			var region = node.closest('[data-am-vb-region]');
			var linkNode = node.matches('a') ? node : node.closest('a');
			return {
				key: key,
				type: type,
				label: node.getAttribute('data-am-vb-label') || item.label || titleCase(key),
				regionId: region ? region.getAttribute('data-am-vb-region') : liveRegion,
				value: item.value || node.innerText || node.textContent || '',
				href: item.href || node.getAttribute('data-am-vb-link-href') || (linkNode ? linkNode.getAttribute('href') : ''),
				src: item.src || ('video' === type ? (node.querySelector('source') || {}).src : (image ? image.src : '')),
				alt: item.alt || (image ? image.alt : ''),
				linkDescription: item.linkDescription || node.getAttribute('title') || (linkNode ? (linkNode.getAttribute('title') || linkNode.getAttribute('aria-label')) : '') || '',
				fallbackValue: node.getAttribute('data-am-vb-fallback') || '',
				fallbackSrc: node.getAttribute('data-am-vb-fallback-src') || '',
				fallbackAlt: node.getAttribute('data-am-vb-fallback-alt') || '',
				customSectionId: node.getAttribute('data-am-vb-custom-owner') || '',
				customItemId: node.getAttribute('data-am-vb-custom-element') || '',
				custom: node.hasAttribute('data-am-vb-custom-element'),
				protected: node.hasAttribute('data-am-vb-protected'),
				hardProtected: node.hasAttribute('data-am-vb-hard-protected'),
				sourceLabel: node.getAttribute('data-am-vb-source-label') || '',
				node: node
			};
		}

		function placeCaretAtEnd(frameDocument, node) {
			var selection = frameDocument.getSelection();
			var range = frameDocument.createRange();
			range.selectNodeContents(node);
			range.collapse(false);
			selection.removeAllRanges();
			selection.addRange(range);
		}

		function selectLiveElement(node, startTyping) {
			var snapshot = selectedElementSnapshot(node);
			setLiveRegion(snapshot.regionId);
			setLiveElement(snapshot);
			highlightLiveRegion(snapshot.regionId, false);
			highlightLiveElement(snapshot.key, node);
			if (startTyping && !snapshot.protected && ('text' === snapshot.type || 'textarea' === snapshot.type)) {
				node.setAttribute('contenteditable', 'true');
				node.setAttribute('spellcheck', 'true');
				node.focus({ preventScroll: true });
				placeCaretAtEnd(node.ownerDocument, node);
			}
		}

		function selectLiveRegion(regionId, shouldScroll) {
			setLiveElement(null);
			setLiveRegion(regionId);
			window.setTimeout(function () {
				var frame = liveFrameRef.current;
				if (frame && frame.contentDocument) {
					clearLiveElementHighlights(frame.contentDocument);
				}
				highlightLiveRegion(regionId, false !== shouldScroll);
			}, 0);
		}

		function disconnectLiveCanvas(frameDocument) {
			if (!frameDocument) {
				return;
			}
			if ('function' === typeof frameDocument.__amVbActiveDragCancel) {
				frameDocument.__amVbActiveDragCancel({});
			}
			[
				['click', '__amVbRegionClick'],
				['click', '__amVbViewLinkClick'],
				['dblclick', '__amVbEditableDoubleClick'],
				['input', '__amVbEditableInput'],
				['pointerdown', '__amVbMediaPointer'],
				['am-vb-runtime-ready', '__amVbRuntimeReady']
			].forEach(function (binding) {
				var handler = frameDocument[binding[1]];
				if (handler) {
					frameDocument.removeEventListener(binding[0], handler, true);
					try { delete frameDocument[binding[1]]; } catch (ignore) { frameDocument[binding[1]] = null; }
				}
			});
			clearLiveElementHighlights(frameDocument);
			Array.prototype.forEach.call(frameDocument.querySelectorAll('[data-am-vb-selected],[data-am-vb-focus-hidden]'), function (node) {
				node.removeAttribute('data-am-vb-selected');
				node.removeAttribute('data-am-vb-focus-hidden');
			});
			var bridgeStyle = frameDocument.getElementById('am-vb-live-canvas-bridge');
			if (bridgeStyle) {
				bridgeStyle.remove();
			}
		}

		function connectViewCanvas(frame, frameDocument) {
			disconnectLiveCanvas(frameDocument);
			frameDocument.documentElement.setAttribute('data-am-vb-canvas-mode', 'view');
			try {
				setLiveBrowsePath(frame.contentWindow.location.pathname || '/');
			} catch (ignore) {
				setLiveBrowsePath('');
			}
			frameDocument.__amVbViewLinkClick = function (clickEvent) {
				if (clickEvent.defaultPrevented || clickEvent.button > 0 || clickEvent.metaKey || clickEvent.ctrlKey || clickEvent.shiftKey || clickEvent.altKey) {
					return;
				}
				var link = clickEvent.target && clickEvent.target.closest ? clickEvent.target.closest('a[href]') : null;
				if (!link || link.hasAttribute('download') || ('_blank' === link.getAttribute('target'))) {
					return;
				}
				var destination;
				try {
					destination = new URL(link.href, frame.contentWindow.location.href);
				} catch (ignore) {
					return;
				}
				if (destination.origin !== frame.contentWindow.location.origin || ['http:', 'https:'].indexOf(destination.protocol) === -1) {
					return;
				}
				var current = frame.contentWindow.location;
				if (destination.pathname === current.pathname && destination.search === current.search && destination.hash) {
					return;
				}
				clickEvent.preventDefault();
				clickEvent.stopPropagation();
				destination.searchParams.set('am_visual_canvas', slugRef.current || 'view');
				destination.searchParams.set('am_visual_mode', 'view');
				frame.contentWindow.location.assign(destination.href);
			};
			frameDocument.addEventListener('click', frameDocument.__amVbViewLinkClick, true);
		}

		function verifyLiveCanvas(frame, frameDocument) {
			setLiveCanvasLoading(true);
			setLiveCanvasError('');
			window.setTimeout(function () {
				if (liveFrameRef.current !== frame || !frameDocument) {
					return;
				}
				var rendered = frameDocument.querySelector('#root > *, [data-am-vb-region]');
				setLiveCanvasLoading(false);
				if (!rendered) {
					setLiveCanvasError('The live website did not finish loading. Reload this canvas; if it continues, rebuild and sync the complete theme bundle.');
				}
			}, 1400);
		}

		function handleLiveCanvasLoad(event) {
			var frame = event.currentTarget;
			var frameDocument;
			liveFrameRef.current = frame;
			try {
				frameDocument = frame.contentDocument;
			} catch (ignore) {
				frameDocument = null;
			}
			if (!frameDocument) {
				setLiveCanvasLoading(false);
				setLiveCanvasError('The live website canvas could not be connected.');
				return;
			}
			if ('view' === canvasMode) {
				connectViewCanvas(frame, frameDocument);
			} else {
				setLiveBrowsePath('');
				connectLiveCanvas(event);
			}
			verifyLiveCanvas(frame, frameDocument);
		}

		function switchCanvasMode(nextMode) {
			if (nextMode === canvasMode || ['view', 'edit'].indexOf(nextMode) === -1) {
				return;
			}
			var frame = liveFrameRef.current;
			if (frame && frame.contentDocument) {
				disconnectLiveCanvas(frame.contentDocument);
			}
			setLiveCanvasLoading(true);
			setLiveCanvasError('');
			setLiveFocus(false);
			setLiveElement(null);
			setPageMenu(null);
			if ('view' === nextMode) {
				setLeftTab('pages');
			}
			setCanvasMode(nextMode);
			if ('edit' === nextMode && liveBrowsePath) {
				var normalizedPath = '/' + String(liveBrowsePath).replace(/^\/+|\/+$/g, '');
				if ('/' !== normalizedPath) {
					normalizedPath = normalizedPath.replace(/\/$/, '');
				}
				var browsedSlug = Object.keys(config.exactRoutes || {}).find(function (candidateSlug) {
					var route = String((config.exactRoutes[candidateSlug] || {}).route || '/');
					route = '/' + route.replace(/^\/+|\/+$/g, '');
					if ('/' !== route) {
						route = route.replace(/\/$/, '');
					}
					return route === normalizedPath;
				});
				if (browsedSlug && browsedSlug !== slugRef.current) {
					window.setTimeout(function () { switchPage(browsedSlug); }, 0);
				}
			}
			setToast('view' === nextMode
				? 'View mode: use the page exactly like the live website'
				: 'Edit mode: select, type, move and resize in the canvas');
		}

		function connectLiveCanvas(event) {
			var frame = event.currentTarget;
			var frameDocument;
			liveFrameRef.current = frame;
			try {
				frameDocument = frame.contentDocument;
			} catch (ignore) {
				frameDocument = null;
			}
			if (!frameDocument) {
				setToast('The exact page canvas could not be connected');
				return;
			}
			if (!frameDocument.__amVbRuntimeReady) {
				frameDocument.__amVbRuntimeReady = function () {
					if ('edit' !== frameDocument.documentElement.getAttribute('data-am-vb-canvas-mode')) {
						return;
					}
					// Exact-route annotations can arrive after the iframe load event.
					// Reapply the editor's current (possibly unsaved/recovered) model once
					// those nodes exist; the iframe runtime must never win this race with
					// its older localized config.design snapshot.
					applyHomeDesignToFrame(homeDesignRef.current);
					applyLiveCanvasFocus();
				};
				frameDocument.addEventListener('am-vb-runtime-ready', frameDocument.__amVbRuntimeReady, true);
			}
			frameDocument.documentElement.setAttribute('data-am-vb-canvas-mode', 'edit');
			if (!frameDocument.getElementById('am-vb-live-canvas-bridge')) {
				var bridgeStyle = frameDocument.createElement('style');
				bridgeStyle.id = 'am-vb-live-canvas-bridge';
				bridgeStyle.textContent = [
					'[data-am-vb-region]{outline:2px solid transparent;outline-offset:-2px;transition:outline-color .15s,box-shadow .15s;}',
					'[data-am-vb-region]:hover{outline-color:rgba(47,110,229,.45);}',
					'[data-am-vb-selected]{box-shadow:inset 0 0 0 2px rgba(47,110,229,.7)!important;outline-color:#fff!important;}',
					'[data-am-vb-editable]{outline:2px dashed transparent;outline-offset:4px;transition:outline-color .12s,box-shadow .12s;cursor:pointer;}',
					'[data-am-vb-editable][data-am-vb-free-move="true"]{cursor:grab;}',
					'[data-am-vb-editable]:hover{outline-color:#ff8a34!important;box-shadow:0 0 0 4px rgba(255,138,52,.16);}',
					'[data-am-vb-edit-selected]{outline:3px solid #ff8a34!important;box-shadow:0 0 0 5px rgba(255,255,255,.8),0 0 0 8px rgba(255,138,52,.3)!important;cursor:move!important;touch-action:none!important;}',
					'[data-am-vb-dragging="true"]{cursor:grabbing!important;user-select:none!important;}',
					'[data-am-vb-editable][contenteditable="true"]{cursor:text;caret-color:#123d2a;min-width:1em;}',
					'.am-vb-media-resize-handle{position:absolute!important;right:-10px!important;bottom:-10px!important;z-index:2147483647!important;width:22px!important;height:22px!important;border:3px solid #fff!important;border-radius:50%!important;background:#ff8a34!important;box-shadow:0 2px 9px rgba(0,0,0,.35)!important;cursor:nwse-resize!important;clip-path:none!important;}',
					// Corner, edge and rotate handles. Squares stretch one axis,
					// circles scale proportionally, the green stalk rotates.
					'.am-vb-media-resize-handle[data-am-vb-handle]{width:13px!important;height:13px!important;border-width:2px!important;border-radius:3px!important;box-shadow:0 1px 5px rgba(0,0,0,.3)!important;}',
					'.am-vb-handle--nw{top:-8px!important;left:-8px!important;right:auto!important;bottom:auto!important;border-radius:50%!important;cursor:nwse-resize!important;}',
					'.am-vb-handle--ne{top:-8px!important;right:-8px!important;left:auto!important;bottom:auto!important;border-radius:50%!important;cursor:nesw-resize!important;}',
					'.am-vb-handle--sw{bottom:-8px!important;left:-8px!important;right:auto!important;top:auto!important;border-radius:50%!important;cursor:nesw-resize!important;}',
					'.am-vb-handle--se{bottom:-8px!important;right:-8px!important;left:auto!important;top:auto!important;border-radius:50%!important;cursor:nwse-resize!important;}',
					'.am-vb-handle--n{top:-7px!important;left:50%!important;right:auto!important;bottom:auto!important;margin-left:-7px!important;cursor:ns-resize!important;}',
					'.am-vb-handle--s{bottom:-7px!important;left:50%!important;right:auto!important;top:auto!important;margin-left:-7px!important;cursor:ns-resize!important;}',
					'.am-vb-handle--w{left:-7px!important;top:50%!important;right:auto!important;bottom:auto!important;margin-top:-7px!important;cursor:ew-resize!important;}',
					'.am-vb-handle--e{right:-7px!important;top:50%!important;left:auto!important;bottom:auto!important;margin-top:-7px!important;cursor:ew-resize!important;}',
					'.am-vb-handle--rotate{top:-30px!important;left:50%!important;right:auto!important;bottom:auto!important;margin-left:-8px!important;width:17px!important;height:17px!important;border-radius:50%!important;background:#345b40!important;cursor:grab!important;}',
					'.am-vb-handle--rotate:active{cursor:grabbing!important;}',
					'[data-am-vb-frame-handle]{align-items:center!important;background:#ff8a34!important;border:2px solid #fff!important;border-radius:999px!important;box-shadow:0 2px 8px rgba(0,0,0,.25)!important;color:#fff!important;cursor:move!important;display:flex!important;font:700 12px/1 sans-serif!important;height:26px!important;justify-content:center!important;letter-spacing:-2px!important;position:absolute!important;right:12px!important;top:12px!important;width:34px!important;z-index:2147483646!important;}',
					'[data-am-vb-custom-element]{overflow:visible!important;touch-action:none;}',
					'[data-am-vb-editable]{touch-action:none;}',
					'[data-am-vb-focus-hidden]{display:none!important;}'
				].join('');
				frameDocument.head.appendChild(bridgeStyle);
			}
			if (!frameDocument.__amVbRegionClick) {
				frameDocument.__amVbRegionClick = function (clickEvent) {
					var editableTarget = clickEvent.target && clickEvent.target.closest
						? clickEvent.target.closest('[data-am-vb-editable]')
						: null;
					var allowedAction = clickEvent.target && clickEvent.target.closest
						? clickEvent.target.closest('[data-am-vb-allow-action="true"]')
						: null;
					var frameHandle = clickEvent.target && clickEvent.target.closest
						? clickEvent.target.closest('[data-am-vb-frame-handle]')
						: null;
					if (allowedAction && !frameHandle) {
						return;
					}
					if (editableTarget) {
						var alreadyTyping = 'true' === editableTarget.getAttribute('contenteditable');
						clickEvent.preventDefault();
						if (!alreadyTyping) {
							clickEvent.stopPropagation();
							selectLiveElement(editableTarget, false);
						}
						return;
					}
					var target = clickEvent.target && clickEvent.target.closest
						? clickEvent.target.closest('[data-am-vb-region]')
						: null;
					if (!target) {
						return;
					}
					clickEvent.preventDefault();
					clickEvent.stopPropagation();
					selectLiveRegion(target.getAttribute('data-am-vb-region'), false);
				};
				frameDocument.addEventListener('click', frameDocument.__amVbRegionClick, true);
			}
			if (!frameDocument.__amVbEditableDoubleClick) {
				frameDocument.__amVbEditableDoubleClick = function (doubleClickEvent) {
					var node = doubleClickEvent.target && doubleClickEvent.target.closest
						? doubleClickEvent.target.closest('[data-am-vb-editable]')
						: null;
					if (!node || ['text', 'textarea'].indexOf(node.getAttribute('data-am-vb-edit-type')) === -1) {
						return;
					}
					doubleClickEvent.preventDefault();
					doubleClickEvent.stopPropagation();
					selectLiveElement(node, true);
				};
				frameDocument.addEventListener('dblclick', frameDocument.__amVbEditableDoubleClick, true);
			}
			if (!frameDocument.__amVbEditableInput) {
				frameDocument.__amVbEditableInput = function (inputEvent) {
					var node = inputEvent.target && inputEvent.target.closest
						? inputEvent.target.closest('[contenteditable="true"][data-am-vb-editable]')
						: null;
					if (!node) {
						return;
					}
					var key = node.getAttribute('data-am-vb-editable');
					var valueNode = node.cloneNode(true);
					Array.prototype.forEach.call(valueNode.querySelectorAll('.am-vb-media-resize-handle,.am-vb-media-move-handle'), function (handle) { handle.remove(); });
					var value = valueNode.innerText.replace(/\u00a0/g, ' ');
					updateHomeElementValue(key, 'value', value, true);
					setLiveElement(function (current) {
						return current && current.key === key ? Object.assign({}, current, { value: value }) : current;
					});
				};
				frameDocument.addEventListener('input', frameDocument.__amVbEditableInput, true);
			}
			if (!frameDocument.__amVbMediaPointer) {
				frameDocument.__amVbMediaPointer = function (pointerEvent) {
					var resizeHandle = pointerEvent.target && pointerEvent.target.closest ? pointerEvent.target.closest('.am-vb-media-resize-handle') : null;
					var node = resizeHandle ? resizeHandle.parentElement : (pointerEvent.target && pointerEvent.target.closest ? pointerEvent.target.closest('[data-am-vb-editable]') : null);
					if (!node || 0 !== pointerEvent.button) {
						return;
					}
					if (node.hasAttribute('data-am-vb-hard-protected')) {
						pointerEvent.preventDefault();
						pointerEvent.stopPropagation();
						selectLiveElement(node, false);
						return;
					}
					var allowedAction = pointerEvent.target && pointerEvent.target.closest ? pointerEvent.target.closest('[data-am-vb-allow-action="true"]') : null;
					var frameHandle = pointerEvent.target && pointerEvent.target.closest ? pointerEvent.target.closest('[data-am-vb-frame-handle]') : null;
					if (allowedAction && !frameHandle) {
						return;
					}
					if ('true' === node.getAttribute('contenteditable') && !resizeHandle) {
						return;
					}
					if ('function' === typeof frameDocument.__amVbActiveDragCancel) {
						frameDocument.__amVbActiveDragCancel({});
					}
					var customSectionId = node.getAttribute('data-am-vb-custom-owner');
					var customItemId = node.getAttribute('data-am-vb-custom-element');
					pointerEvent.preventDefault();
					pointerEvent.stopPropagation();
					selectLiveElement(node, false);
					var key = node.getAttribute('data-am-vb-editable');
					var item = homeElementData(key);
					var activeDevice = frameDocument.documentElement.getAttribute('data-am-vb-device') || 'desktop';
					var settings = item[activeDevice] || {};
					var customItem = customItemId ? homeCustomItem(customSectionId, customItemId) : null;
					var customSettings = customItem && customItem[activeDevice] ? customItem[activeDevice] : {};
					var pointerId = pointerEvent.pointerId;
					var mediaNode = node.classList.contains('am-vb-editable-media');
					function inlineNumber(property, fallback) {
						var raw = node.style.getPropertyValue(property);
						var parsed = '' === String(raw || '').trim() ? NaN : parseFloat(raw);
						return Number.isFinite(parsed) ? parsed : Number(fallback || 0);
					}
					var startX = pointerEvent.clientX;
					var startY = pointerEvent.clientY;
					// Read the coordinates actually rendered in the iframe. This avoids a
					// first-move jump when an older localized runtime snapshot and a newer
					// unsaved editor model briefly disagree.
					var startScale = inlineNumber((mediaNode ? '--am-media-scale-' : '--am-element-scale-') + activeDevice, Number(settings.scale || 100) / 100) * 100;
					var startOffsetX = inlineNumber((mediaNode ? '--am-media-offset-x-' : '--am-element-offset-x-') + activeDevice, settings.offsetX || 0);
					var startOffsetY = inlineNumber((mediaNode ? '--am-media-offset-y-' : '--am-element-offset-y-') + activeDevice, settings.offsetY || 0);
					var startCustomX = Number(customSettings.x || 0);
					var startCustomY = Number(customSettings.y || 0);
					var startCustomWidth = Number(customSettings.width || 100);
					var startCustomHeight = Number(customSettings.height || 100);
					// Which handle was grabbed decides what the gesture means.
					var handleDirection = resizeHandle ? (resizeHandle.getAttribute('data-am-vb-handle') || 'se') : '';
					var startBox = node.getBoundingClientRect();
					var startWidth = inlineNumber('--am-vb-width-' + activeDevice, Math.round(startBox.width));
					var startHeight = inlineNumber('--am-vb-height-' + activeDevice, Math.round(startBox.height));
					var startRotate = inlineNumber('--am-vb-rotate-' + activeDevice, Number(settings.rotate || 0));
					var moved = false;
					var finished = false;
					var latest = {};
					function clamp(value, min, max) { return Math.max(min, Math.min(max, value)); }
					function samePointer(event) {
						return undefined === pointerId || undefined === event.pointerId || pointerId === event.pointerId;
					}
					function onMove(moveEvent) {
						if (!samePointer(moveEvent) || finished) {
							return;
						}
						// Pointer capture is the primary guarantee. This mouse-only guard is
						// the fallback for older engines: never keep an orphaned gesture alive
						// after the physical button was released outside the iframe.
						if ('mouse' === moveEvent.pointerType && 0 === moveEvent.buttons) {
							if (moved) {
								onUp(moveEvent);
							} else {
								cancelDrag(moveEvent);
							}
							return;
						}
						var dx = moveEvent.clientX - startX;
						var dy = moveEvent.clientY - startY;
						if (!moved && Math.sqrt(dx * dx + dy * dy) <= 5) {
							return;
						}
						moved = true;
						node.setAttribute('data-am-vb-dragging', 'true');
						moveEvent.preventDefault();
						moveEvent.stopPropagation();
						if (customItem && resizeHandle) {
							latest.width = Math.round(clamp(startCustomWidth + dx, 30, 1400));
							latest.height = Math.round(clamp(startCustomHeight + dy, 20, 1000));
							node.style.setProperty('--am-custom-width-' + activeDevice, latest.width + 'px');
							node.style.setProperty('--am-custom-height-' + activeDevice, latest.height + 'px');
						} else if (customItem) {
							latest.x = Math.round(clamp(startCustomX + dx, -1200, 2400));
							latest.y = Math.round(clamp(startCustomY + dy, -1200, 1800));
							node.style.setProperty('--am-custom-x-' + activeDevice, latest.x + 'px');
							node.style.setProperty('--am-custom-y-' + activeDevice, latest.y + 'px');
						} else if (resizeHandle && 'rotate' === handleDirection) {
							var box = node.getBoundingClientRect();
							var radians = Math.atan2(
								moveEvent.clientY - (box.top + box.height / 2),
								moveEvent.clientX - (box.left + box.width / 2)
							);
							// The stalk sits above the element, so straight up is zero.
							var degrees = radians * 180 / Math.PI + 90;
							if (degrees > 180) {
								degrees -= 360;
							}
							// Shift snaps to 15 degree steps for deliberate angles.
							if (moveEvent.shiftKey) {
								degrees = Math.round(degrees / 15) * 15;
							}
							latest.rotate = Math.round(clamp(degrees, -180, 180));
							node.style.setProperty('--am-vb-rotate-' + activeDevice, latest.rotate + 'deg');
							node.setAttribute('data-am-vb-styled', 'true');
						} else if (resizeHandle && ('e' === handleDirection || 'w' === handleDirection)) {
							// One axis only. Text rewraps onto the next line, media
							// stretches sideways - the element decides, not the handle.
							latest.width = Math.round(clamp(startWidth + ('w' === handleDirection ? -dx : dx), 24, 1600));
							node.style.setProperty('--am-vb-width-' + activeDevice, latest.width + 'px');
							node.setAttribute('data-am-vb-styled', 'true');
						} else if (resizeHandle && ('n' === handleDirection || 's' === handleDirection)) {
							latest.height = Math.round(clamp(startHeight + ('n' === handleDirection ? -dy : dy), 16, 1600));
							node.style.setProperty('--am-vb-height-' + activeDevice, latest.height + 'px');
							node.setAttribute('data-am-vb-styled', 'true');
						} else if (resizeHandle) {
							latest.scale = Math.round(clamp(startScale + dx * 0.8, 40, 240));
							node.style.setProperty('--am-media-scale-' + activeDevice, latest.scale / 100);
							node.style.setProperty('--am-element-scale-' + activeDevice, latest.scale / 100);
						} else {
							latest.offsetX = Math.round(clamp(startOffsetX + dx, -1200, 1200));
							latest.offsetY = Math.round(clamp(startOffsetY + dy, -1200, 1200));
							node.style.setProperty('--am-media-offset-x-' + activeDevice, latest.offsetX + 'px');
							node.style.setProperty('--am-media-offset-y-' + activeDevice, latest.offsetY + 'px');
							node.style.setProperty('--am-element-offset-x-' + activeDevice, latest.offsetX + 'px');
							node.style.setProperty('--am-element-offset-y-' + activeDevice, latest.offsetY + 'px');
						}
					}
					function cleanup() {
						if (finished) {
							return false;
						}
						finished = true;
						frameDocument.removeEventListener('pointermove', onMove, true);
						frameDocument.removeEventListener('pointerup', onUp, true);
						frameDocument.removeEventListener('pointercancel', onCancel, true);
						node.removeEventListener('lostpointercapture', onLostCapture, true);
						if (frameDocument.defaultView) {
							frameDocument.defaultView.removeEventListener('blur', onCancel, true);
						}
						if (frameDocument.__amVbActiveDragCancel === cancelDrag) {
							frameDocument.__amVbActiveDragCancel = null;
						}
						node.removeAttribute('data-am-vb-dragging');
						try {
							if (undefined !== pointerId && node.hasPointerCapture && node.hasPointerCapture(pointerId)) {
								node.releasePointerCapture(pointerId);
							}
						} catch (ignore) {}
						return true;
					}
					function onUp(upEvent) {
						if (!samePointer(upEvent) || !cleanup()) {
							return;
						}
						if (!moved) {
							return;
						}
						if (customItem) {
							updateHomeDesign(function (next) {
								var section = next.customSections.find(function (candidate) { return candidate.id === customSectionId; });
								var target = section && section.items ? section.items.find(function (candidate) { return candidate.id === customItemId; }) : null;
								if (!target) { return; }
								responsiveDevices(activeDevice).forEach(function (breakpoint) {
									target[breakpoint] = target[breakpoint] || {};
									Object.keys(latest).forEach(function (field) { target[breakpoint][field] = latest[field]; });
								});
							}, 'all' === responsiveScopeRef.current ? 'Layer moved or resized on all devices' : 'Layer moved or resized on ' + titleCase(activeDevice));
							return;
						}
						// Commit the complete gesture atomically. Queuing X and Y as separate
						// React updates could let the second update reapply a model that still
						// contained the old X value, so horizontal movement appeared to snap
						// back even though the pointer drag itself was working.
						updateHomeDesign(function (next) {
							next.elements = next.elements || {};
							next.elements[key] = next.elements[key] || {};
							responsiveDevices(activeDevice).forEach(function (breakpoint) {
								next.elements[key][breakpoint] = next.elements[key][breakpoint] || {};
								Object.keys(latest).forEach(function (field) {
									next.elements[key][breakpoint][field] = latest[field];
								});
							});
						}, 'all' === responsiveScopeRef.current ? 'Element moved or resized on all devices' : 'Element moved or resized on ' + titleCase(activeDevice));
					}
					function cancelDrag(cancelEvent) {
						if (!samePointer(cancelEvent) || !cleanup()) {
							return;
						}
						// A cancelled gesture must leave no unsaved visual residue.
						applyHomeElementToFrame(frameDocument, key, homeElementData(key));
					}
					function onCancel(cancelEvent) { cancelDrag(cancelEvent); }
					function onLostCapture(lostEvent) { cancelDrag(lostEvent); }
					frameDocument.addEventListener('pointermove', onMove, true);
					frameDocument.addEventListener('pointerup', onUp, true);
					frameDocument.addEventListener('pointercancel', onCancel, true);
					node.addEventListener('lostpointercapture', onLostCapture, true);
					if (frameDocument.defaultView) {
						frameDocument.defaultView.addEventListener('blur', onCancel, true);
					}
					frameDocument.__amVbActiveDragCancel = cancelDrag;
					try {
						if (undefined !== pointerId && node.setPointerCapture) {
							node.setPointerCapture(pointerId);
						}
					} catch (ignore) {}
				};
				frameDocument.addEventListener('pointerdown', frameDocument.__amVbMediaPointer, true);
			}
			applyHomeDesignToFrame(homeDesign);
			applyLiveCanvasFocus();
			highlightLiveRegion(liveRegion, false);
			if (liveElement && liveElement.key) {
				highlightLiveElement(liveElement.key);
			}
		}

		function sectionLocked(section) {
			return readOnly || !section || !!section.locked;
		}

		function elementLocked(section, element) {
			if (sectionLocked(section) || !element) {
				return true;
			}
			if (element.locked) {
				return true;
			}
			var group = (section.groups || []).find(function (item) { return item.id === element.groupId; });
			return !!(group && group.locked);
		}

		function selectSection(sectionId) {
			setSelection({ sectionId: sectionId, elementIds: [] });
		}

		function selectElement(sectionId, elementId, event) {
			var multiple = !!(event && (event.shiftKey || event.ctrlKey || event.metaKey));
			setSelection(function (current) {
				if (!multiple || current.sectionId !== sectionId) {
					return { sectionId: sectionId, elementIds: [elementId] };
				}
				var ids = current.elementIds.slice();
				var index = ids.indexOf(elementId);
				if (-1 === index) {
					ids.push(elementId);
				} else {
					ids.splice(index, 1);
				}
				return { sectionId: sectionId, elementIds: ids };
			});
		}

		function moveDocumentSectionStep(sectionId, direction) {
			if (readOnly || !documentState) { return; }
			commit(function (next) {
				var index = next.sections.findIndex(function (section) { return section.id === sectionId; });
				var targetIndex = index + direction;
				if (index < 0 || targetIndex < 0 || targetIndex >= next.sections.length) { return; }
				var moved = next.sections.splice(index, 1)[0];
				next.sections.splice(targetIndex, 0, moved);
			}, 'Section moved ' + (direction < 0 ? 'up' : 'down') + ' one place');
			setSelection({ sectionId: sectionId, elementIds: [] });
		}

		function moveDocumentElementStep(sectionId, elementId, direction) {
			if (readOnly || !documentState) { return; }
			commit(function (next) {
				var section = next.sections.find(function (candidate) { return candidate.id === sectionId; });
				if (!section) { return; }
				var index = section.elements.findIndex(function (element) { return element.id === elementId; });
				var targetIndex = index + direction;
				if (index < 0 || targetIndex < 0 || targetIndex >= section.elements.length) { return; }
				var moved = section.elements.splice(index, 1)[0];
				section.elements.splice(targetIndex, 0, moved);
			}, 'Layer moved ' + (direction < 0 ? 'up' : 'down') + ' one place');
			setSelection({ sectionId: sectionId, elementIds: [elementId] });
		}

		function updateSectionField(field, value) {
			if (!selected.section || (selected.section.locked && 'locked' !== field)) {
				return;
			}
			commit(function (next) {
				var section = next.sections.find(function (item) { return item.id === selected.section.id; });
				section[field] = value;
			});
		}

		function updateSectionStyle(field, value) {
			if (sectionLocked(selected.section)) {
				return;
			}
			commit(function (next) {
				var section = next.sections.find(function (item) { return item.id === selected.section.id; });
				section.settings = section.settings || {};
				section.settings[device] = section.settings[device] || {};
				section.settings[device][field] = value;
			});
		}

		function updateElementField(field, value) {
			if (!selected.element || (selected.element.locked && 'locked' !== field)) {
				return;
			}
			commit(function (next) {
				var section = next.sections.find(function (item) { return item.id === selected.section.id; });
				var element = section.elements.find(function (item) { return item.id === selected.element.id; });
				element[field] = value;
			});
		}

		function updateElementStyle(field, value) {
			if (!selected.element || elementLocked(selected.section, selected.element)) {
				return;
			}
			commit(function (next) {
				var section = next.sections.find(function (item) { return item.id === selected.section.id; });
				var element = section.elements.find(function (item) { return item.id === selected.element.id; });
				element.styles = element.styles || {};
				element.styles[device] = element.styles[device] || {};
				element.styles[device][field] = value;
			});
		}

		function newElement(type, mediaItem) {
			var defaults = {
				id: uid(type),
				type: type,
				name: titleCase(type),
				visible: true,
				locked: false,
				groupId: '',
				styles: { desktop: {}, tablet: {}, mobile: {} }
			};
			if ('text' === type) {
				Object.assign(defaults, { tag: 'p', content: 'Edit this text' });
			} else if ('button' === type) {
				Object.assign(defaults, { label: 'Button', href: '#', newWindow: false });
			} else if ('image' === type) {
				Object.assign(defaults, {
					mediaId: mediaItem ? mediaItem.id : 0,
					src: mediaItem ? mediaItem.url : '',
					alt: mediaItem ? (mediaItem.alt || mediaItem.title) : ''
				});
			} else if ('shape' === type) {
				Object.assign(defaults, { shape: 'rectangle' });
				defaults.styles.desktop = { width: '180px', height: '100px', backgroundColor: '#a9c6a2' };
			}
			return defaults;
		}

		function addSection(sectionTemplate) {
			var section = sectionTemplate ? duplicateSection(sectionTemplate) : {
				id: uid('section'),
				name: 'New section',
				visible: true,
				locked: false,
				settings: {
					layout: 'stack',
					desktop: { minHeight: '360px', padding: '64px', gap: '20px', backgroundColor: '#ffffff' },
					tablet: {},
					mobile: { minHeight: '300px', padding: '32px 22px' }
				},
				groups: [],
				elements: []
			};
			commit(function (next) { next.sections.push(section); }, 'Section added');
			setSelection({ sectionId: section.id, elementIds: [] });
			setLeftTab('structure');
		}

		function addElement(type, mediaItem) {
			var target = selected.section || (documentState.sections && documentState.sections[0]);
			if (!target || sectionLocked(target)) {
				if (!target) {
					setToast('Add a section first');
				}
				return;
			}
			var element = newElement(type, mediaItem);
			commit(function (next) {
				var section = next.sections.find(function (item) { return item.id === target.id; });
				section.elements.push(element);
			}, titleCase(type) + ' added');
			setSelection({ sectionId: target.id, elementIds: [element.id] });
		}

		function deleteSelection() {
			if (!selected.section) {
				return;
			}
			if (selected.elements.length) {
				var deletable = selected.elements.filter(function (element) {
					return !elementLocked(selected.section, element);
				}).map(function (element) { return element.id; });
				if (!deletable.length) {
					return;
				}
				commit(function (next) {
					var section = next.sections.find(function (item) { return item.id === selected.section.id; });
					section.elements = section.elements.filter(function (item) { return deletable.indexOf(item.id) === -1; });
					section.groups = (section.groups || []).map(function (group) {
						group.elementIds = (group.elementIds || []).filter(function (id) { return deletable.indexOf(id) === -1; });
						return group;
					}).filter(function (group) { return group.elementIds.length; });
				}, 'Selection deleted');
				setSelection({ sectionId: selected.section.id, elementIds: [] });
			} else if (!sectionLocked(selected.section) && window.confirm('Delete this entire section?')) {
				commit(function (next) {
					next.sections = next.sections.filter(function (item) { return item.id !== selected.section.id; });
				}, 'Section deleted');
				setSelection({ sectionId: null, elementIds: [] });
			}
		}

		function duplicateSelection() {
			if (!selected.section || sectionLocked(selected.section)) {
				return;
			}
			if (!selected.elements.length) {
				var copy = duplicateSection(selected.section);
				commit(function (next) {
					var index = next.sections.findIndex(function (item) { return item.id === selected.section.id; });
					next.sections.splice(index + 1, 0, copy);
				}, 'Section duplicated');
				setSelection({ sectionId: copy.id, elementIds: [] });
				return;
			}
			var copies = selected.elements.filter(function (element) {
				return !elementLocked(selected.section, element);
			}).map(function (element) {
				var copy = clone(element);
				copy.id = uid(element.type || 'element');
				copy.name = (copy.name || titleCase(copy.type)) + ' copy';
				copy.groupId = '';
				return copy;
			});
			commit(function (next) {
				var section = next.sections.find(function (item) { return item.id === selected.section.id; });
				Array.prototype.push.apply(section.elements, copies);
			}, 'Selection duplicated');
			setSelection({ sectionId: selected.section.id, elementIds: copies.map(function (item) { return item.id; }) });
		}

		function moveSelection(direction) {
			if (!selected.section || selected.elements.length > 1 || sectionLocked(selected.section)) {
				return;
			}
			commit(function (next) {
				if (!selected.element) {
					var sectionIndex = next.sections.findIndex(function (item) { return item.id === selected.section.id; });
					var nextSectionIndex = Math.max(0, Math.min(next.sections.length - 1, sectionIndex + direction));
					var section = next.sections.splice(sectionIndex, 1)[0];
					next.sections.splice(nextSectionIndex, 0, section);
				} else {
					var sectionItem = next.sections.find(function (item) { return item.id === selected.section.id; });
					var index = sectionItem.elements.findIndex(function (item) { return item.id === selected.element.id; });
					var nextIndex = Math.max(0, Math.min(sectionItem.elements.length - 1, index + direction));
					var element = sectionItem.elements.splice(index, 1)[0];
					sectionItem.elements.splice(nextIndex, 0, element);
				}
			}, 'Layer reordered');
		}

		function groupSelection() {
			if (!selected.section || selected.elements.length < 2 || sectionLocked(selected.section)) {
				return;
			}
			var groupId = uid('group');
			var ids = selected.elements.map(function (item) { return item.id; });
			commit(function (next) {
				var section = next.sections.find(function (item) { return item.id === selected.section.id; });
				section.groups = section.groups || [];
				section.groups.push({ id: groupId, name: 'Group', elementIds: ids, visible: true, locked: false });
				section.elements.forEach(function (item) {
					if (ids.indexOf(item.id) !== -1) {
						item.groupId = groupId;
					}
				});
			}, 'Elements grouped');
		}

		function ungroupSelection() {
			if (!selected.section || !selected.elements.length || sectionLocked(selected.section)) {
				return;
			}
			var groupIds = selected.elements.map(function (item) { return item.groupId; }).filter(Boolean);
			if (!groupIds.length) {
				return;
			}
			commit(function (next) {
				var section = next.sections.find(function (item) { return item.id === selected.section.id; });
				section.groups = (section.groups || []).filter(function (group) { return groupIds.indexOf(group.id) === -1; });
				section.elements.forEach(function (item) {
					if (groupIds.indexOf(item.groupId) !== -1) {
						item.groupId = '';
					}
				});
			}, 'Elements ungrouped');
		}

		function toggleGroup(group, field) {
			if (!selected.section || sectionLocked(selected.section)) {
				return;
			}
			commit(function (next) {
				var section = next.sections.find(function (item) { return item.id === selected.section.id; });
				var target = section.groups.find(function (item) { return item.id === group.id; });
				target[field] = !target[field];
			}, 'Group updated');
		}

		function chooseImage() {
			if (!selected.element || 'image' !== selected.element.type || elementLocked(selected.section, selected.element)) {
				return;
			}
			var frame = wp.media({
				title: 'Choose an image',
				button: { text: 'Use this image' },
				library: { type: 'image' },
				multiple: false
			});
			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				commit(function (next) {
					var section = next.sections.find(function (item) { return item.id === selected.section.id; });
					var image = section.elements.find(function (item) { return item.id === selected.element.id; });
					image.mediaId = attachment.id;
					image.src = attachment.sizes && attachment.sizes.large ? attachment.sizes.large.url : attachment.url;
					image.alt = attachment.alt || attachment.title || '';
				}, 'Image replaced');
			});
			frame.open();
		}

		function finishTextEdit(elementId, html) {
			if (html === initialTextRef.current) {
				return;
			}
			commit(function (next) {
				next.sections.forEach(function (section) {
					var text = section.elements.find(function (item) { return item.id === elementId; });
					if (text) {
						text.content = html;
					}
				});
			}, 'Text updated');
		}

		function formatText(command) {
			if (!editableRef.current) {
				setToast('Click directly into a text layer first');
				return;
			}
			editableRef.current.focus();
			window.document.execCommand(command, false, null);
			finishTextEdit(editableRef.current.dataset.elementId, editableRef.current.innerHTML);
		}

		function openPreview() {
			if (dirty) {
				save('manual').then(function () {
					window.open(config.previewBase.replace('__AM_VB_SLUG__', encodeURIComponent(slugRef.current)), '_blank', 'noopener');
				}).catch(function () {});
				return;
			}
			window.open(config.previewBase.replace('__AM_VB_SLUG__', encodeURIComponent(slugRef.current)), '_blank', 'noopener');
		}

		function switchPage(nextSlug) {
			if (nextSlug === slugRef.current) {
				return;
			}
			if (liveParity && homeDesignDirty && !window.confirm('This exact page still has unsaved changes. Switch pages without saving them yet?')) {
				return;
			}
			if (!liveParity && dirtyRef.current && !window.confirm('This page still has unsaved changes. Switch pages and keep its browser recovery copy?')) {
				return;
			}
			if (!readOnly) {
				apiFetch({
					url: config.root + 'page/' + slugRef.current + '/lock',
					method: 'DELETE',
					data: { sessionId: sessionRef.current }
				}).catch(function () {});
			}
			pageKeyboardSelectionRef.current = nextSlug;
			loadPage(nextSlug);
		}

		function createPage() {
			var title = window.prompt('New page name');
			if (!title) {
				return;
			}
			var requestedSlug = window.prompt('URL slug (letters, numbers and hyphens)', title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''));
			if (!requestedSlug) {
				return;
			}
			apiFetch({
				url: config.root + 'pages',
				method: 'POST',
				data: { title: title, slug: requestedSlug, sessionId: sessionRef.current }
			}).then(function (payload) {
				setPages(function (current) {
					return current.concat([{ id: payload.id, slug: payload.slug, title: payload.document.title, status: payload.status }]);
				});
				setToast('Page created');
				return switchPage(payload.slug);
			}).catch(function (createError) {
				setToast(createError.message || 'Could not create page');
			});
		}

		function deletePage(page) {
			if (pages.length < 2) {
				setToast('Keep at least one visual page');
				return;
			}
			if (page.slug !== slugRef.current) {
				switchPage(page.slug);
				setToast('Open the page before moving it to Trash');
				return;
			}
			if (!window.confirm('Move "' + page.title + '" to WordPress Trash?')) {
				return;
			}
			apiFetch({
				url: config.root + 'page/' + page.slug,
				method: 'DELETE',
				data: { sessionId: sessionRef.current }
			}).then(function (payload) {
				setPages(payload.pages || []);
				var next = payload.pages && payload.pages[0];
				if (next) {
					loadPage(next.slug);
				}
				setToast('Page moved to Trash');
			}).catch(function (deleteError) {
				setToast(deleteError.message || 'Could not delete page');
			});
		}

		function pageBucket(page) {
			return (organizer.buckets || {})[page.slug] || page.navigation || ('Parent Information' === page.family || 'Blog' === page.family ? 'menu' : 'navbar');
		}

		function pageLabel(page) {
			var value = (organizer.labels || {})[page.slug] || page.title;
			var decoder = window.document.createElement('textarea');
			decoder.innerHTML = String(value || 'Untitled page');
			return decoder.value;
		}

		function orderedPages(includeHidden) {
			var ranks = {};
			(organizer.order || []).forEach(function (pageSlug, index) { ranks[pageSlug] = index; });
			var hidden = organizer.hidden || [];
			return pages.slice().sort(function (left, right) {
				var leftRank = undefined === ranks[left.slug] ? 10000 : ranks[left.slug];
				var rightRank = undefined === ranks[right.slug] ? 10000 : ranks[right.slug];
				return leftRank - rightRank || pageLabel(left).localeCompare(pageLabel(right));
			}).filter(function (page) {
				return includeHidden || hidden.indexOf(page.slug) === -1;
			});
		}

		function pagesForView(view) {
			return orderedPages(false).filter(function (page) {
				return 'all' === view || pageBucket(page) === view;
			});
		}

		function persistOrganizer(next, successMessage) {
			setOrganizer(next);
			return apiFetch({
				url: config.root + 'organizer',
				method: 'POST',
				data: { organizer: next }
			}).then(function (payload) {
				setOrganizer(payload.organizer || next);
				if (successMessage) {
					setToast(successMessage);
				}
			}).catch(function (organizerError) {
				setToast(organizerError.message || 'Could not save the page arrangement');
			});
		}

		function reorderPage(sourceSlug, targetSlug, placeAfter) {
			if (!sourceSlug || !targetSlug || sourceSlug === targetSlug) {
				return;
			}
			var fullOrder = orderedPages(true).map(function (page) { return page.slug; });
			var sourceIndex = fullOrder.indexOf(sourceSlug);
			var targetIndex = fullOrder.indexOf(targetSlug);
			if (-1 === sourceIndex || -1 === targetIndex) {
				return;
			}
			fullOrder.splice(sourceIndex, 1);
			targetIndex = fullOrder.indexOf(targetSlug);
			fullOrder.splice(targetIndex + (placeAfter ? 1 : 0), 0, sourceSlug);
			persistOrganizer(Object.assign({}, organizer, { order: fullOrder }), 'Page order saved');
		}

		function movePageStep(page, direction) {
			var visible = pagesForView(pageView);
			var index = visible.findIndex(function (item) { return item.slug === page.slug; });
			var target = visible[index + direction];
			if (target) {
				reorderPage(page.slug, target.slug, direction > 0);
			}
			setPageMenu(null);
		}

		function orderedSidebarItems(scope, items, orderState) {
			var sourceOrders = orderState || sidebarOrders;
			var order = sourceOrders[scope] || [];
			var ranks = {};
			order.forEach(function (key, index) { ranks[key] = index; });
			return (items || []).map(function (item, originalIndex) {
				return { item: item, originalIndex: originalIndex };
			}).sort(function (left, right) {
				var leftKey = String(left.item.id || left.item.key || '');
				var rightKey = String(right.item.id || right.item.key || '');
				var leftRank = undefined === ranks[leftKey] ? 10000 + left.originalIndex : ranks[leftKey];
				var rightRank = undefined === ranks[rightKey] ? 10000 + right.originalIndex : ranks[rightKey];
				return leftRank - rightRank;
			}).map(function (entry) { return entry.item; });
		}

		function moveSidebarItemStep(scope, items, item, direction, noun, visibleItems) {
			var currentOrders = sidebarOrdersRef.current || sidebarOrders;
			var ordered = orderedSidebarItems(scope, items, currentOrders);
			var visible = visibleItems ? orderedSidebarItems(scope, visibleItems, currentOrders) : ordered;
			var key = String(item.id || item.key || '');
			var index = visible.findIndex(function (candidate) { return String(candidate.id || candidate.key || '') === key; });
			var targetIndex = index + direction;
			if (!key || index < 0 || targetIndex < 0 || targetIndex >= visible.length) {
				return false;
			}
			var targetKey = String(visible[targetIndex].id || visible[targetIndex].key || '');
			var keys = ordered.map(function (candidate) { return String(candidate.id || candidate.key || ''); });
			var fullIndex = keys.indexOf(key);
			var fullTargetIndex = keys.indexOf(targetKey);
			if (fullIndex < 0 || fullTargetIndex < 0) {
				return false;
			}
			keys[fullIndex] = targetKey;
			keys[fullTargetIndex] = key;
			var next = Object.assign({}, currentOrders);
			next[scope] = keys;
			sidebarOrdersRef.current = next;
			setSidebarOrders(next);
			try {
				window.localStorage.setItem('am-vb-sidebar-orders-v1', JSON.stringify(next));
			} catch (ignore) {
				// The ordering still works for this open session when storage is unavailable.
			}
			setToast((noun || 'Item') + ' moved ' + (direction < 0 ? 'up' : 'down') + ' one place');
			return true;
		}

		function handleSidebarArrow(event, moveUp, moveDown) {
			if ('ArrowUp' !== event.key && 'ArrowDown' !== event.key) {
				return;
			}
			event.preventDefault();
			event.stopPropagation();
			if ('ArrowUp' === event.key) {
				moveUp();
			} else {
				moveDown();
			}
		}

		function movePageBucket(page, bucket) {
			var buckets = Object.assign({}, organizer.buckets || {});
			buckets[page.slug] = bucket;
			setPageMenu(null);
			persistOrganizer(Object.assign({}, organizer, { buckets: buckets }), 'Moved to ' + ('menu' === bucket ? 'Menu' : 'Navbar'));
		}

		function renameOrganizedPage(page) {
			var label = window.prompt('Page name in this organizer', pageLabel(page));
			if (!label || !label.trim()) {
				return;
			}
			var labels = Object.assign({}, organizer.labels || {});
			labels[page.slug] = label.trim().slice(0, 80);
			setPageMenu(null);
			persistOrganizer(Object.assign({}, organizer, { labels: labels }), 'Page renamed in the organizer');
		}

		function removeOrganizedPage(page) {
			setPageMenu(null);
			if (!page.exactRoute) {
				deletePage(page);
				return;
			}
			if (!window.confirm('Remove "' + pageLabel(page) + '" from this Pages list? The live route and its saved design stay safe.')) {
				return;
			}
			var hidden = (organizer.hidden || []).filter(function (item) { return item !== page.slug; });
			hidden.push(page.slug);
			persistOrganizer(Object.assign({}, organizer, { hidden: hidden }), 'Page removed from the organizer');
		}

		function restoreOrganizedPages() {
			persistOrganizer(Object.assign({}, organizer, { hidden: [] }), 'Removed pages restored');
		}

		function workflowAction(action) {
			var run = dirty ? save('manual') : Promise.resolve();
			run.then(function () {
				return apiFetch({
					url: config.root + 'page/' + slugRef.current + '/status',
					method: 'POST',
					data: { action: action, sessionId: sessionRef.current }
				});
			}).then(function (payload) {
				applyPayload(payload);
				setPages(function (current) {
					return current.map(function (page) {
						return page.slug === slugRef.current ? Object.assign({}, page, { status: payload.status }) : page;
					});
				});
				setToast('Workflow updated: ' + titleCase(payload.status));
			}).catch(function (workflowError) {
				var issues = workflowError && workflowError.data && workflowError.data.issues;
				setToast(issues && issues.length ? issues[0] : (workflowError.message || 'Workflow update failed'));
			});
		}

		function restoreRevision(revisionId) {
			if (!window.confirm('Restore this saved version? The current version remains in history.')) {
				return;
			}
			apiFetch({
				url: config.root + 'page/' + slugRef.current + '/revisions/' + revisionId + '/restore',
				method: 'POST',
				data: { sessionId: sessionRef.current }
			}).then(function (payload) {
				applyPayload(payload);
				setToast('Revision restored');
			}).catch(function (restoreError) {
				setToast(restoreError.message || 'Could not restore revision');
			});
		}

		function saveTemplate() {
			if (!selected.section || !config.canManageTemplates) {
				return;
			}
			var name = window.prompt('Reusable section name', selected.section.name || 'Section template');
			if (!name) {
				return;
			}
			apiFetch({
				url: config.root + 'templates',
				method: 'POST',
				data: { name: name, section: selected.section }
			}).then(function (payload) {
				setTemplates(payload.templates || []);
				setToast('Reusable section saved');
			}).catch(function (templateError) {
				setToast(templateError.message || 'Could not save template');
			});
		}

		function deleteTemplate(template) {
			if (!window.confirm('Delete reusable section "' + template.name + '"?')) {
				return;
			}
			apiFetch({
				url: config.root + 'templates/' + template.id,
				method: 'DELETE'
			}).then(function (payload) {
				setTemplates(payload.templates || []);
				setToast('Reusable section deleted');
			}).catch(function (templateError) {
				setToast(templateError.message || 'Could not delete template');
			});
		}

		function restoreRecovery() {
			if (!recovery || !recovery.document) {
				return;
			}
			var recovered = normalizeDocument(recovery.document);
			setDocumentState(recovered);
			docRef.current = recovered;
			setDirty(true);
			dirtyRef.current = true;
			setRecovery(null);
			setStatusText('Recovered browser copy — save to keep it');
			setToast('Recovery copy restored');
		}

		function discardRecovery() {
			window.localStorage.removeItem(recoveryKey(slugRef.current));
			setRecovery(null);
			setToast('Recovery copy discarded');
		}

		function renderTopbar() {
			var currentPageTitle = (activeExactRoute && activeExactRoute.title) || (documentState ? documentState.title : 'Visual Site Builder');
			var currentPublicUrl = activeExactRoute && activeExactRoute.route
				? new URL(activeExactRoute.route, config.liveHomeUrl).href
				: config.liveHomeUrl;
			if (savedSessionsOpen) {
				return el('header', { className: 'am-vb-topbar am-vb-topbar--sessions' },
					el('div', { className: 'am-vb-topbar__group' },
						el('a', { className: 'am-vb-back', href: config.contentUrl }, '← Website Content'),
						el('div', { className: 'am-vb-title' },
							el('strong', null, 'All saved sessions'),
							el('span', null, savedSessions.length + ' named version' + (1 === savedSessions.length ? '' : 's'))
						)
					),
					el('div', null),
					el('div', { className: 'am-vb-topbar__group am-vb-topbar__group--right' },
						button('am-vb-button', 'Back to editor', closeSavedSessions)
					)
				);
			}
			return el('header', { className: 'am-vb-topbar' },
				el('div', { className: 'am-vb-topbar__group' },
					el('a', { className: 'am-vb-back', href: config.contentUrl }, '← Website Content'),
					el('div', { className: 'am-vb-title' },
						el('strong', null, liveParity ? currentPageTitle : (documentState ? documentState.title : 'Visual Site Builder')),
						el('span', null, liveParity ? homeDesignStatus : (titleCase(workflow) + ' · ' + statusText))
					)
				),
				el('div', { className: 'am-vb-device-switcher', 'aria-label': 'Responsive preview' },
					button(device === 'desktop' ? 'is-active' : '', '▰ Desktop', function () { setDevice('desktop'); }),
					button(device === 'tablet' ? 'is-active' : '', '▯ Tablet', function () { setDevice('tablet'); }),
					button(device === 'mobile' ? 'is-active' : '', '▯ Mobile', function () { setDevice('mobile'); })
				),
				el('div', { className: 'am-vb-topbar__group am-vb-topbar__group--right' },
					liveParity
						? el(Fragment, null,
							el('span', { className: 'am-vb-parity-badge' }, homeDesignDirty ? 'Unsaved' : 'Saved'),
							button('am-vb-button am-vb-button--primary', homeDesignSaving ? 'Saving…' : 'Save', function () {
								requestNamedSave();
							}, { disabled: 'view' === canvasMode || homeDesignSaving || !homeDesignDirty }),
							el('a', {
								className: 'am-vb-button',
								href: currentPublicUrl,
								target: '_blank',
								rel: 'noopener'
							}, 'Open full page')
						)
						: el(Fragment, null,
							button('am-vb-icon-button', '↶', undo, { disabled: readOnly || !historyRef.current.length, title: 'Undo' }),
							button('am-vb-icon-button', '↷', redo, { disabled: readOnly || !futureRef.current.length, title: 'Redo' }),
							button('am-vb-button', 'Preview', openPreview),
							'draft' === workflow
								? button('am-vb-button', 'Submit review', function () { workflowAction('submit_review'); }, { disabled: readOnly })
								: button('am-vb-button', 'Return to draft', function () { workflowAction('revert_draft'); }, { disabled: readOnly }),
							config.canPublish && 'published' !== workflow
								? button('am-vb-button am-vb-button--primary', 'Publish', function () { workflowAction('publish'); }, { disabled: readOnly || saving })
								: null,
							button('am-vb-button am-vb-button--primary', saving ? 'Saving…' : 'Save', function () { save('manual').catch(function () {}); }, {
								disabled: readOnly || saving || !dirty
							})
						)
				)
			);
		}

		function renderSavedSessionsPage() {
			var selectedSessionDay = selectedDayNumber(savedSessionDate);
			var hasCompleteInvalidDate = 10 === savedSessionDate.length && null === selectedSessionDay;
			var nearestLabel = null !== selectedSessionDay
				? 'Closest to ' + readableSelectedDate(savedSessionDate)
				: (savedSessionDate ? (hasCompleteInvalidDate ? 'Enter a real date in DD/MM/YYYY' : 'Finish the date in DD/MM/YYYY') : 'Newest changes first');
			return el('main', { className: 'am-vb-sessions-page' },
				el('div', { className: 'am-vb-sessions-page__inner' },
					el('div', { className: 'am-vb-sessions-hero' },
						el('div', null,
							el('span', { className: 'am-vb-sessions-eyebrow' }, 'Version history'),
							el('h1', null, 'All saved sessions'),
							el('p', null, 'Open any named save as the real website, then restore it only when you are ready.')
						),
						button('am-vb-button', 'Refresh', function () { loadSavedSessions().catch(function () {}); }, { disabled: savedSessionsLoading })
					),
					el('div', { className: 'am-vb-session-filters' },
						el('label', { className: 'am-vb-session-search' },
							el('span', { className: 'screen-reader-text' }, 'Search saved sessions'),
							el('input', {
								type: 'search',
								value: savedSessionQuery,
								placeholder: 'Search title, page or changes',
								onChange: function (event) { setSavedSessionQuery(event.target.value); }
							})
						),
						el('div', { className: 'am-vb-session-date' + (hasCompleteInvalidDate ? ' is-invalid' : '') },
							el('label', { htmlFor: 'am-vb-nearest-date' }, 'Nearest date · DD/MM/YYYY'),
							el('div', { className: 'am-vb-session-date__control' },
								el('input', {
									id: 'am-vb-nearest-date',
									type: 'text',
									inputMode: 'numeric',
									pattern: '[0-9/]*',
									placeholder: 'DD/MM/YYYY',
									maxLength: 10,
									autoComplete: 'off',
									'aria-label': 'Nearest date in DD/MM/YYYY',
									'aria-invalid': hasCompleteInvalidDate ? 'true' : 'false',
									value: savedSessionDate,
									onChange: function (event) { setSavedSessionDate(formatDayMonthYearInput(event.target.value)); }
								}),
								el('button', {
									type: 'button',
									className: 'am-vb-session-date__picker-button',
									'aria-label': 'Choose nearest date from calendar',
									title: 'Choose date from calendar',
									onClick: function () {
										var picker = savedSessionDatePickerRef.current;
										if (!picker) {
											return;
										}
										if ('function' === typeof picker.showPicker) {
											try {
												picker.showPicker();
												return;
											} catch (ignore) {}
										}
										picker.focus();
										picker.click();
									}
								},
									el('svg', { viewBox: '0 0 24 24', 'aria-hidden': 'true', focusable: 'false' },
										el('rect', { x: '3.5', y: '5.5', width: '17', height: '15', rx: '2', fill: 'none', stroke: 'currentColor', strokeWidth: '1.7' }),
										el('path', { d: 'M8 3.5v4M16 3.5v4M3.5 10h17', fill: 'none', stroke: 'currentColor', strokeWidth: '1.7', strokeLinecap: 'round' })
									)
								),
								el('input', {
									ref: savedSessionDatePickerRef,
									className: 'am-vb-session-date__native-picker',
									type: 'date',
									tabIndex: -1,
									'aria-hidden': 'true',
									value: dayMonthYearPickerValue(savedSessionDate),
									onChange: function (event) { setSavedSessionDate(dayMonthYearFromPicker(event.target.value)); }
								})
							)
						),
						savedSessionQuery || savedSessionDate
							? button('am-vb-session-clear', 'Clear', function () {
								setSavedSessionQuery('');
								setSavedSessionDate('');
							})
							: null
					),
					el('div', { className: 'am-vb-session-results-meta' },
						el('strong', null, nearestLabel),
						el('span', null, visibleSavedSessions.length + ' result' + (1 === visibleSavedSessions.length ? '' : 's'))
					),
					savedSessionsLoading
						? el('div', { className: 'am-vb-session-state', role: 'status' },
							el('span', { className: 'spinner is-active' }),
							el('p', null, 'Loading saved sessions…')
						)
						: savedSessionsError
							? el('div', { className: 'am-vb-session-state am-vb-session-state--error', role: 'alert' },
								el('strong', null, 'Saved sessions could not be opened'),
								el('p', null, savedSessionsError),
								button('am-vb-button', 'Try again', function () { loadSavedSessions().catch(function () {}); })
							)
							: visibleSavedSessions.length
								? el('div', { className: 'am-vb-session-table-wrap' },
									el('table', { className: 'am-vb-session-table' },
										el('thead', null,
											el('tr', null,
												el('th', { scope: 'col' }, 'Time'),
												el('th', { scope: 'col' }, 'Recent changes'),
												el('th', { scope: 'col' }, 'What changed'),
												el('th', { scope: 'col' }, 'Open'),
												el('th', { scope: 'col' }, 'Delete')
											)
										),
										el('tbody', null, visibleSavedSessions.map(function (item) {
											return el('tr', { key: item.id },
												el('td', { className: 'am-vb-session-time' },
													el('time', { dateTime: item.created }, item.createdLabel),
													el('small', null, item.author || 'System'),
													item.authorRole
														? el('small', { className: 'am-vb-session-role' }, item.authorRole)
														: null
												),
												el('td', { className: 'am-vb-session-title-cell' },
													el('strong', null, item.title),
													el('span', null, item.pageTitle + ' · ' + item.route)
												),
												el('td', { className: 'am-vb-session-summary' }, item.summary || 'Named saved version'),
												el('td', null,
													button('am-vb-session-action am-vb-session-action--open', 'Open', function () {
														openSavedSessionPreview(item);
													})
												),
												el('td', null,
													button('am-vb-session-action am-vb-session-action--delete', 'Delete', function () {
														deleteSavedSession(item);
													})
												)
											);
										}))
									)
								)
								: el('div', { className: 'am-vb-session-state' },
									el('strong', null, savedSessions.length ? 'No matching sessions' : 'No saved sessions yet'),
									el('p', null, savedSessions.length
										? 'Try another title, page or date.'
										: 'The next named Save will appear here automatically.')
								)
				)
			);
		}

		function renderSaveSessionDialog() {
			if (!saveSessionDialog) {
				return null;
			}
			var currentPageTitle = (activeExactRoute && activeExactRoute.title) || 'Current page';
			return el('div', {
				className: 'am-vb-session-modal-backdrop',
				onMouseDown: function (event) {
					if (event.target === event.currentTarget && !saveSessionBusy) {
						setSaveSessionDialog(false);
					}
				}
			},
				el('div', {
					className: 'am-vb-session-modal',
					role: 'dialog',
					'aria-modal': 'true',
					'aria-labelledby': 'am-vb-save-session-title'
				},
					el('span', { className: 'am-vb-sessions-eyebrow' }, 'Create a restore point'),
					el('h2', { id: 'am-vb-save-session-title' }, 'Name this saved session'),
					el('p', null, 'This title will identify the current ' + currentPageTitle + ' version in All saved sessions.'),
					el('label', { className: 'am-vb-session-modal__field' },
						el('span', null, 'Session title'),
						el('input', {
							type: 'text',
							value: saveSessionTitle,
							maxLength: 120,
							autoFocus: true,
							placeholder: 'For example: Homepage hero approved',
							disabled: saveSessionBusy,
							onChange: function (event) {
								setSaveSessionTitle(event.target.value);
								if (saveSessionError) {
									setSaveSessionError('');
								}
							},
							onKeyDown: function (event) {
								if ('Enter' === event.key) {
									event.preventDefault();
									confirmNamedSave();
								} else if ('Escape' === event.key && !saveSessionBusy) {
									setSaveSessionDialog(false);
								}
							}
						})
					),
					saveSessionError ? el('p', { className: 'am-vb-session-modal__error', role: 'alert' }, saveSessionError) : null,
					el('div', { className: 'am-vb-session-modal__actions' },
						button('am-vb-button', 'Cancel', function () { setSaveSessionDialog(false); }, { disabled: saveSessionBusy }),
						button('am-vb-button am-vb-button--primary', saveSessionBusy ? 'Saving…' : 'Save session', confirmNamedSave, {
							disabled: saveSessionBusy
						})
					)
				)
			);
		}

		function renderRestoreSessionDialog() {
			if (!restoreCandidate) {
				return null;
			}
			return el('div', { className: 'am-vb-session-modal-backdrop' },
				el('div', {
					className: 'am-vb-session-modal',
					role: 'dialog',
					'aria-modal': 'true',
					'aria-labelledby': 'am-vb-restore-session-title'
				},
					el('span', { className: 'am-vb-sessions-eyebrow' }, 'Safe restore'),
					el('h2', { id: 'am-vb-restore-session-title' }, 'Restore “' + restoreCandidate.title + '”?'),
					el('p', null, restoreCandidate.pageTitle + ' · ' + restoreCandidate.createdLabel),
					el('div', { className: 'am-vb-session-restore-summary' }, restoreCandidate.summary),
					el('p', { className: 'am-vb-session-safety-note' }, 'Your current page will be saved automatically first, so you can move back and forth safely.'),
					el('div', { className: 'am-vb-session-modal__actions' },
						button('am-vb-button', 'Cancel', dismissSavedSessionRestore, { disabled: restoreSessionBusy }),
						button('am-vb-button am-vb-button--primary', restoreSessionBusy ? 'Restoring…' : 'Restore this version', restoreSavedSession, {
							disabled: restoreSessionBusy
						})
					)
				)
			);
		}

		function renderPagePanel() {
			var visiblePages = pagesForView(pageView);
			var hiddenCount = (organizer.hidden || []).filter(function (pageSlug) {
				return pages.some(function (page) { return page.slug === pageSlug; });
			}).length;
			var counts = { navbar: pagesForView('navbar').length, menu: pagesForView('menu').length, all: orderedPages(false).length };
			var menuPage = pageMenu && pages.find(function (page) { return page.slug === pageMenu.slug; });
			var menuStyle = pageMenu ? {
				left: Math.max(10, Math.min(pageMenu.x, window.innerWidth - 238)) + 'px',
				top: Math.max(10, Math.min(pageMenu.y, window.innerHeight - 330)) + 'px'
			} : {};
			return el(Fragment, null,
				el('div', { className: 'am-vb-panel-title' },
					el('div', null, el('strong', null, 'Visual pages'), el('span', null, counts.all + ' visible' + (hiddenCount ? ' · ' + hiddenCount + ' removed' : '')))
				),
				button('am-vb-button am-vb-panel-create', '+ Create page', createPage, { disabled: readOnly && !liveParity }),
				el('div', { className: 'am-vb-page-filter', role: 'tablist', 'aria-label': 'Page categories' },
					[['navbar', 'Navbar'], ['menu', 'Menu'], ['all', 'All']].map(function (item) {
						return button('am-vb-page-filter__tab' + (pageView === item[0] ? ' is-active' : ''), el(Fragment, null,
							el('span', null, item[1]), el('small', null, counts[item[0]])
						), function () { setPageView(item[0]); setPageMenu(null); }, { role: 'tab', 'aria-selected': pageView === item[0] });
					})
				),
				hiddenCount ? button('am-vb-page-restore', 'Restore ' + hiddenCount + ' removed page' + (1 === hiddenCount ? '' : 's'), restoreOrganizedPages) : null,
				el('p', { className: 'am-vb-page-list__hint' }, 'Select a page card and press the keyboard ↑ or ↓ key to move it one place. You can also hold and drag, right-click, or use the three dots.'),
				el('div', { className: 'am-vb-page-list' }, visiblePages.length ? visiblePages.map(function (page) {
					var protectedRoute = !!page.exactRoute;
					return el('div', {
						className: 'am-vb-page-list__item' + (page.slug === slug ? ' is-active' : ''),
						key: page.id,
						draggable: true,
						tabIndex: 0,
						'data-page-slug': page.slug,
						'aria-label': pageLabel(page) + '. Draggable page. Press keyboard Arrow Up or Arrow Down to reorder.',
						onFocus: function () { pageKeyboardSelectionRef.current = page.slug; },
						onPointerDown: function () { pageKeyboardSelectionRef.current = page.slug; },
						onDragStart: function (event) {
							draggedPageRef.current = page.slug;
							event.currentTarget.classList.add('is-dragging');
							if (event.dataTransfer) {
								event.dataTransfer.effectAllowed = 'move';
								event.dataTransfer.setData('text/plain', page.slug);
							}
						},
						onDragOver: function (event) {
							event.preventDefault();
							event.currentTarget.classList.add('is-drag-target');
							if (event.dataTransfer) { event.dataTransfer.dropEffect = 'move'; }
						},
						onDragLeave: function (event) { event.currentTarget.classList.remove('is-drag-target'); },
						onDrop: function (event) {
							event.preventDefault();
							event.currentTarget.classList.remove('is-drag-target');
							reorderPage(draggedPageRef.current || (event.dataTransfer ? event.dataTransfer.getData('text/plain') : ''), page.slug);
							draggedPageRef.current = '';
						},
						onDragEnd: function (event) {
							event.currentTarget.classList.remove('is-dragging');
							Array.prototype.forEach.call(window.document.querySelectorAll('.am-vb-page-list__item.is-drag-target'), function (item) { item.classList.remove('is-drag-target'); });
							draggedPageRef.current = '';
						},
						onContextMenu: function (event) {
							event.preventDefault();
							setPageMenu({ slug: page.slug, x: event.clientX, y: event.clientY });
						},
						onKeyDown: function (event) {
							if ('ArrowUp' === event.key || 'ArrowDown' === event.key) {
								handleSidebarArrow(event, function () { movePageStep(page, -1); }, function () { movePageStep(page, 1); });
							} else if ('ContextMenu' === event.key || ('F10' === event.key && event.shiftKey)) {
								event.preventDefault();
								var rect = event.currentTarget.getBoundingClientRect();
								setPageMenu({ slug: page.slug, x: rect.right - 210, y: rect.bottom + 6 });
							}
						}
					},
						el('span', { className: 'am-vb-page-list__grip', title: 'Hold and drag to reorder', 'aria-hidden': 'true' }, '⋮⋮'),
						button('am-vb-page-list__open', el(Fragment, null,
							el('strong', null, pageLabel(page)),
							el('span', null, el('em', null, page.family || 'Custom pages'), ' · ', protectedRoute ? (page.route || '/') : ('/' + page.slug))
						), function () { switchPage(page.slug); }),
						button('am-vb-page-list__more', '⋮', function (event) {
							event.stopPropagation();
							var rect = event.currentTarget.getBoundingClientRect();
							setPageMenu({ slug: page.slug, x: rect.right - 210, y: rect.bottom + 6 });
						}, { title: 'Page actions', 'aria-label': 'Actions for ' + pageLabel(page) })
					);
				}) : el('div', { className: 'am-vb-page-list__empty' }, 'No pages in this category. Use a page action in All to move it here.')),
				pageMenu ? el('button', { className: 'am-vb-page-menu-backdrop', 'aria-label': 'Close page actions', onClick: function () { setPageMenu(null); } }) : null,
				menuPage ? el('div', { className: 'am-vb-page-menu', style: menuStyle, role: 'menu' },
					el('div', { className: 'am-vb-page-menu__title' }, el('strong', null, pageLabel(menuPage)), el('span', null, menuPage.route || '/' + menuPage.slug)),
					button('am-vb-page-menu__action', 'Move up', function () { movePageStep(menuPage, -1); }, { role: 'menuitem' }),
					button('am-vb-page-menu__action', 'Move down', function () { movePageStep(menuPage, 1); }, { role: 'menuitem' }),
					button('am-vb-page-menu__action', 'Move to Navbar', function () { movePageBucket(menuPage, 'navbar'); }, { role: 'menuitem', disabled: 'navbar' === pageBucket(menuPage) }),
					button('am-vb-page-menu__action', 'Move to Menu', function () { movePageBucket(menuPage, 'menu'); }, { role: 'menuitem', disabled: 'menu' === pageBucket(menuPage) }),
					el('hr', null),
					button('am-vb-page-menu__action', 'Rename in organizer', function () { renameOrganizedPage(menuPage); }, { role: 'menuitem' }),
					button('am-vb-page-menu__action is-danger', menuPage.exactRoute ? 'Remove from Pages list' : 'Move page to Trash', function () { removeOrganizedPage(menuPage); }, { role: 'menuitem' })
				) : null
			);
		}

		function renderLiveOutline() {
			var pageName = (activeExactRoute && activeExactRoute.title) || 'Home page';
			var coreRegions = homeRegionList().filter(function (region) { return !region.custom && region.editable !== false; });
			var regionScope = 'regions:' + slug;
			var outlineRegions = orderedSidebarItems(regionScope, homeRegionList());
			return el(Fragment, null,
				el('div', { className: 'am-vb-panel-title' },
					el('div', null,
						el('strong', null, pageName + ' regions'),
						el('span', null, coreRegions.length + ' planned regions + ' + ((homeDesign.customSections || []).length) + ' custom sections')
					)
				),
				button('am-vb-button am-vb-panel-create', '+ Add a section', function () { setLeftTab('templates'); }),
				el('p', { className: 'am-vb-keyboard-reorder-hint' }, 'Select a region or added layer, then press the keyboard ↑ or ↓ key to move it one place.'),
				el('div', { className: 'am-vb-live-tree' }, outlineRegions.map(function (region) {
					var regionButton = button(
						'am-vb-live-tree__item' + (liveRegion === region.id ? ' is-active' : ''),
						el(Fragment, null,
							el('span', { className: 'am-vb-live-tree__icon', 'aria-hidden': 'true' },
								0 === region.id.indexOf('global-') ? '◇' : (0 === region.id.indexOf('home-') ? '§' : '◌')
							),
							el('span', { className: 'am-vb-live-tree__copy' },
								el('strong', null, region.label),
								el('small', null, region.kind)
							)
						),
						function () { selectLiveRegion(region.id, true); },
						{
							onKeyDown: function (event) {
								handleSidebarArrow(event,
									function () { moveSidebarItemStep(regionScope, outlineRegions, region, -1, 'Region'); },
									function () { moveSidebarItemStep(regionScope, outlineRegions, region, 1, 'Region'); }
								);
							}
						}
					);
					var regionControl = regionButton;
					if (!region.custom) {
						var collectionName = 'home-feature-links' === region.id ? 'features' : ('home-benefits' === region.id ? 'benefits' : ('home-trust' === region.id ? 'trust' : ''));
						var extraItems = collectionName && homeDesign.collections ? (homeDesign.collections[collectionName] || []) : [];
						if (!extraItems.length) {
							return el(Fragment, { key: region.id }, regionControl);
						}
						return el('div', { className: 'am-vb-live-tree__custom-group', key: region.id },
							regionControl,
							el('div', { className: 'am-vb-live-tree__layers' }, extraItems.map(function (item, index) {
								var key = 'features' === collectionName
									? 'feature-extra-' + item.id + '-title'
									: ('benefits' === collectionName ? 'benefit-extra-' + item.id + '-text' : 'trust-extra-' + item.id + '-label');
								var data = homeElementData(key);
								return button(
									'am-vb-live-tree__layer' + (liveElement && liveElement.key === key ? ' is-active' : ''),
									el(Fragment, null, el('span', null, '+'), el('strong', null, data.value || ('Added item ' + (index + 1)))),
									function () {
										var frame = liveFrameRef.current;
										var node = frame && frame.contentDocument ? frame.contentDocument.querySelector('[data-am-vb-editable="' + key + '"]') : null;
										if (node) { selectLiveElement(node, false); }
									},
									{
										key: item.id,
										onKeyDown: function (event) {
											handleSidebarArrow(event,
												function () { if (index > 0) { moveHomeCollectionItem(collectionName, item.id, -1); } },
												function () { if (index < extraItems.length - 1) { moveHomeCollectionItem(collectionName, item.id, 1); } }
											);
										}
									}
								);
							}))
						);
					}
					var section = homeCustomSection(region.sectionId);
					return el('div', { className: 'am-vb-live-tree__custom-group', key: region.id },
						regionControl,
						el('div', { className: 'am-vb-live-tree__layers' }, (section && section.items || []).map(function (item, itemIndex) {
							return button(
								'am-vb-live-tree__layer' + (liveElement && liveElement.customItemId === item.id ? ' is-active' : ''),
								el(Fragment, null, el('span', null, iconFor(item.type)), el('strong', null, item.name || titleCase(item.type))),
								function () {
									var frame = liveFrameRef.current;
									var node = frame && frame.contentDocument ? frame.contentDocument.querySelector('[data-am-vb-custom-owner="' + region.sectionId + '"][data-am-vb-custom-element="' + item.id + '"]') : null;
									if (node) { selectLiveElement(node, false); }
								},
								{
									key: item.id,
									onKeyDown: function (event) {
										handleSidebarArrow(event,
											function () { if (itemIndex > 0) { moveCustomItem(region.sectionId, item.id, -1); } },
											function () { if (itemIndex < (section.items || []).length - 1) { moveCustomItem(region.sectionId, item.id, 1); } }
										);
									}
								}
							);
						})),
						el('div', { className: 'am-vb-live-tree__custom-actions' },
							button('am-vb-section-action', 'Duplicate section', function () { duplicateCustomSection(region.sectionId); }),
							button('am-vb-section-action am-vb-section-action--danger', 'Delete section', function () { deleteCustomSection(region.sectionId); })
						)
					);
				})),
				el('div', { className: 'am-vb-live-tree__note' },
					el('strong', null, 'Exact-page navigator'),
					el('p', null, 'The outline follows the planning workstream while the centre remains the real responsive React route. Bound facts and functional values stay connected to their source records.')
				)
			);
		}

		function renderOutlineRow(section, sectionIndex) {
			var active = selection.sectionId === section.id && !selection.elementIds.length;
			return el('li', { className: 'am-vb-outline-section', key: section.id },
				el('div', {
					className: 'am-vb-outline-row' + (active ? ' is-active' : '') + (!section.visible ? ' is-hidden' : ''),
					tabIndex: 0,
					onClick: function () { selectSection(section.id); },
					onKeyDown: function (event) {
						handleSidebarArrow(event,
							function () { if (sectionIndex > 0) { moveDocumentSectionStep(section.id, -1); } },
							function () { if (sectionIndex < documentState.sections.length - 1) { moveDocumentSectionStep(section.id, 1); } }
						);
					}
				},
					el('span', { className: 'am-vb-outline-icon' }, '§'),
					el('span', { className: 'am-vb-outline-label' }, section.name),
					el('span', { className: 'am-vb-outline-actions' },
						button('am-vb-mini-action', section.visible ? '◉' : '○', function (event) {
							event.stopPropagation();
							var prior = selected.section;
							setSelection({ sectionId: section.id, elementIds: [] });
							commit(function (next) {
								var item = next.sections.find(function (candidate) { return candidate.id === section.id; });
								item.visible = !item.visible;
							}, 'Section visibility updated');
							if (prior && prior.id !== section.id) {
								setSelection({ sectionId: section.id, elementIds: [] });
							}
						}, { title: 'Toggle visibility', disabled: readOnly }),
						button('am-vb-mini-action', section.locked ? '🔒' : '🔓', function (event) {
							event.stopPropagation();
							commit(function (next) {
								var item = next.sections.find(function (candidate) { return candidate.id === section.id; });
								item.locked = !item.locked;
							}, 'Section lock updated');
						}, { title: 'Toggle edit lock', disabled: readOnly })
					)
				),
				(section.groups || []).map(function (group) {
					return el('div', { className: 'am-vb-group-row', key: group.id },
						el('span', null, '⌘ ' + group.name + ' (' + (group.elementIds || []).length + ')'),
						button('am-vb-mini-action', group.visible ? '◉' : '○', function () { toggleGroup(group, 'visible'); }),
						button('am-vb-mini-action', group.locked ? '🔒' : '🔓', function () { toggleGroup(group, 'locked'); })
					);
				}),
				el('ul', { className: 'am-vb-outline' }, section.elements.map(function (element, elementIndex) {
					var selectedElement = selection.sectionId === section.id && selection.elementIds.indexOf(element.id) !== -1;
					return el('li', {
						className: 'am-vb-outline-row am-vb-outline-row--element' + (selectedElement ? ' is-active' : '') + (!element.visible ? ' is-hidden' : ''),
						key: element.id,
						tabIndex: 0,
						onClick: function (event) { selectElement(section.id, element.id, event); },
						onKeyDown: function (event) {
							handleSidebarArrow(event,
								function () { if (elementIndex > 0) { moveDocumentElementStep(section.id, element.id, -1); } },
								function () { if (elementIndex < section.elements.length - 1) { moveDocumentElementStep(section.id, element.id, 1); } }
							);
						}
					},
						el('span', { className: 'am-vb-outline-icon' }, iconFor(element.type)),
						el('span', { className: 'am-vb-outline-label' }, element.name),
						element.groupId ? el('small', { title: 'Grouped' }, '⌘') : null,
						el('span', { className: 'am-vb-outline-actions' },
							button('am-vb-mini-action', element.visible ? '◉' : '○', function (event) {
								event.stopPropagation();
								commit(function (next) {
									var itemSection = next.sections.find(function (candidate) { return candidate.id === section.id; });
									var item = itemSection.elements.find(function (candidate) { return candidate.id === element.id; });
									item.visible = !item.visible;
								}, 'Layer visibility updated');
							}, { disabled: readOnly || section.locked }),
							button('am-vb-mini-action', element.locked ? '🔒' : '🔓', function (event) {
								event.stopPropagation();
								commit(function (next) {
									var itemSection = next.sections.find(function (candidate) { return candidate.id === section.id; });
									var item = itemSection.elements.find(function (candidate) { return candidate.id === element.id; });
									item.locked = !item.locked;
								}, 'Layer lock updated');
							}, { disabled: readOnly || section.locked })
						)
					);
				}))
			);
		}

		function renderAddPanel() {
			return el(Fragment, null,
				el('div', { className: 'am-vb-panel-title' },
					el('div', null, el('strong', null, 'Add content'), el('span', null, 'Adds to the selected section'))
				),
				el('div', { className: 'am-vb-add-grid' },
					['text', 'button', 'image', 'shape'].map(function (type) {
						return button('am-vb-add-card', el(Fragment, null,
							el('span', null, iconFor(type)),
							el('strong', null, titleCase(type))
						), function () { addElement(type); }, { key: type, disabled: readOnly });
					})
				),
				button('am-vb-add-section', '+ Add blank section', function () { addSection(); }, { disabled: readOnly })
			);
		}

		function renderMediaPanel() {
			return el(Fragment, null,
				el('div', { className: 'am-vb-panel-title' },
					el('div', null, el('strong', null, 'Media library'), el('span', null, 'Click to add or replace an image'))
				),
				el('div', { className: 'am-vb-media-grid' }, media.map(function (item) {
					return button('am-vb-media-card', el(Fragment, null,
						el('img', { src: item.thumbnail || item.url, alt: item.alt || '' }),
						el('span', null, item.title || 'Image')
					), function () {
						if (selected.element && 'image' === selected.element.type) {
							updateElementField('mediaId', item.id);
							updateElementField('src', item.url);
							updateElementField('alt', item.alt || item.title);
						} else {
							addElement('image', item);
						}
					}, { key: item.id, disabled: readOnly });
				})),
				button('am-vb-add-section', 'Open WordPress uploader', function () {
					if (selected.element && 'image' === selected.element.type) {
						chooseImage();
					} else {
						setToast('Add or select an image layer first');
					}
				})
			);
		}

		function renderTemplatesPanel() {
			var savedTemplateScope = 'templates:saved';
			var orderedTemplates = orderedSidebarItems(savedTemplateScope, templates);
			return el(Fragment, null,
				el('div', { className: 'am-vb-panel-title' },
					el('div', null, el('strong', null, 'Reusable sections'), el('span', null, templates.length + ' saved templates'))
				),
				config.canManageTemplates
					? button('am-vb-button am-vb-panel-create', 'Save selected section as template', saveTemplate, { disabled: !selected.section || readOnly })
					: null,
				el('p', { className: 'am-vb-keyboard-reorder-hint' }, 'Select a template, then press the keyboard ↑ or ↓ key to move it one place.'),
				templates.length
					? el('div', { className: 'am-vb-template-list' }, orderedTemplates.map(function (template, templateIndex) {
						return el('article', {
							className: 'am-vb-template-card',
							key: template.id,
							tabIndex: 0,
							'aria-label': template.name + '. Press keyboard Arrow Up or Arrow Down to reorder.',
							onClick: function (event) { event.currentTarget.focus(); },
							onKeyDown: function (event) {
								handleSidebarArrow(event,
									function () { if (templateIndex > 0) { moveSidebarItemStep(savedTemplateScope, orderedTemplates, template, -1, 'Template'); } },
									function () { if (templateIndex < orderedTemplates.length - 1) { moveSidebarItemStep(savedTemplateScope, orderedTemplates, template, 1, 'Template'); } }
								);
							}
						},
							el('div', null, el('strong', null, template.name), el('span', null, 'By ' + (template.author || 'Unknown'))),
							el('div', { className: 'am-vb-action-row' },
								button('am-vb-button', 'Insert', function () { addSection(template.section); }, { disabled: readOnly }),
								config.canManageTemplates
									? button('am-vb-button am-vb-button--danger', 'Delete', function () { deleteTemplate(template); })
									: null
							)
						);
					}))
					: el('p', { className: 'am-vb-empty-copy' }, 'Select a section and save it here to reuse it on other pages.')
			);
		}

		function renderHomeTemplateThumbnail(template) {
			var canvasHeight = Number((template.height || {}).desktop || 420);
			var layers = (template.items || []).map(function (item, index) {
				var values = item.desktop || {};
				var type = item.type || 'text';
				var style = {
					left: Math.max(0, Math.min(96, Number(values.x || 0) / 1440 * 100)) + '%',
					top: Math.max(0, Math.min(94, Number(values.y || 0) / canvasHeight * 100)) + '%',
					width: Math.max(2, Math.min(100, Number(values.width || 140) / 1440 * 100)) + '%',
					height: Math.max(3, Math.min(100, Number(values.height || 35) / canvasHeight * 100)) + '%',
					borderRadius: Math.max(1, Math.min(16, Number(item.borderRadius || 5) / 12)) + 'px'
				};
				if ('shape' === type || 'button' === type) {
					style.backgroundColor = item.backgroundColor || ('button' === type ? '#345b40' : '#a9c6a2');
				}
				return el('span', {
					className: 'am-vb-template-layer is-' + type,
					key: template.id + '-layer-' + index,
					style: style,
					title: item.name || titleCase(type)
				}, ['text', 'tabs'].indexOf(type) !== -1 ? el('i', null) : null);
			});
			return el('div', { className: 'am-vb-home-template-card__preview is-' + template.id },
				layers,
				el('span', { className: 'am-vb-home-template-card__count' }, template.items.length ? template.items.length + ' layers' : 'Blank')
			);
		}

		function renderLiveTemplatesPanel() {
			var quickCollection = 'home-poc' !== slug ? '' : ('home-feature-links' === liveRegion
				? 'features'
				: ('home-benefits' === liveRegion ? 'benefits' : ('home-trust' === liveRegion ? 'trust' : '')));
			var quickLabel = 'features' === quickCollection
				? 'Add another feature card'
				: ('benefits' === quickCollection ? 'Add another benefit' : 'Add another logo / trust item');
			var categories = ['All'].concat(HOME_BLOCK_TEMPLATES.map(function (template) { return template.category || 'Other'; }).filter(function (category, index, list) { return list.indexOf(category) === index; }));
			var responsiveTemplateScope = 'templates:responsive';
			var allResponsiveTemplates = orderedSidebarItems(responsiveTemplateScope, HOME_BLOCK_TEMPLATES);
			var visibleTemplates = allResponsiveTemplates.filter(function (template) {
				return 'All' === homeTemplateCategory || template.category === homeTemplateCategory;
			});
			var savedTemplateScope = 'templates:saved';
			var orderedSavedTemplates = orderedSidebarItems(savedTemplateScope, templates);
			return el(Fragment, null,
				quickCollection
					? el('div', { className: 'am-vb-home-quick-add' },
						el('strong', null, 'Add inside ' + liveRegionData(liveRegion).label),
						el('p', null, 'This keeps the same responsive design and simply adds the next item.'),
						button('am-vb-button am-vb-button--primary am-vb-button--wide', '+ ' + quickLabel, function () { addHomeCollectionItem(quickCollection); })
					)
					: null,
				el('div', { className: 'am-vb-panel-title' },
					el('div', null,
						el('strong', null, 'Responsive section library'),
						el('span', null, visibleTemplates.length + ' responsive designs · added after ' + liveRegionData(liveRegion).label)
					)
				),
				el('div', { className: 'am-vb-template-filters', role: 'group', 'aria-label': 'Filter templates' }, categories.map(function (category) {
					return button('am-vb-template-filter' + (homeTemplateCategory === category ? ' is-active' : ''), category, function () { setHomeTemplateCategory(category); }, { key: category });
				})),
				el('p', { className: 'am-vb-keyboard-reorder-hint' }, 'Select a template, then press the keyboard ↑ or ↓ key to move it one place.'),
				el('div', { className: 'am-vb-home-template-grid' }, visibleTemplates.map(function (template) {
					var visibleTemplateIndex = visibleTemplates.findIndex(function (candidate) { return candidate.id === template.id; });
					return el('article', {
						className: 'am-vb-home-template-card',
						key: template.id,
						tabIndex: 0,
						'aria-label': template.name + '. Press keyboard Arrow Up or Arrow Down to reorder.',
						onClick: function (event) { event.currentTarget.focus(); },
						onKeyDown: function (event) {
							handleSidebarArrow(event,
								function () { if (visibleTemplateIndex > 0) { moveSidebarItemStep(responsiveTemplateScope, allResponsiveTemplates, template, -1, 'Template', visibleTemplates); } },
								function () { if (visibleTemplateIndex < visibleTemplates.length - 1) { moveSidebarItemStep(responsiveTemplateScope, allResponsiveTemplates, template, 1, 'Template', visibleTemplates); } }
							);
						}
					},
						renderHomeTemplateThumbnail(template),
						el('div', { className: 'am-vb-home-template-card__title' }, el('strong', null, template.name), el('span', null, template.category || 'Section')),
						el('p', null, template.description),
						button('am-vb-button am-vb-button--primary am-vb-button--wide am-vb-template-insert', '+ Add section', function () { insertHomeTemplate(template); })
					);
				})),
				templates.length
					? el(Fragment, null,
						el('div', { className: 'am-vb-panel-title' }, el('div', null, el('strong', null, 'Saved section templates'), el('span', null, 'Reusable across Home and visual pages'))),
						el('div', { className: 'am-vb-template-list' }, orderedSavedTemplates.map(function (template, templateIndex) {
							return el('article', {
								className: 'am-vb-template-card',
								key: template.id,
								tabIndex: 0,
								'aria-label': template.name + '. Press keyboard Arrow Up or Arrow Down to reorder.',
								onClick: function (event) { event.currentTarget.focus(); },
								onKeyDown: function (event) {
									handleSidebarArrow(event,
										function () { if (templateIndex > 0) { moveSidebarItemStep(savedTemplateScope, orderedSavedTemplates, template, -1, 'Template'); } },
										function () { if (templateIndex < orderedSavedTemplates.length - 1) { moveSidebarItemStep(savedTemplateScope, orderedSavedTemplates, template, 1, 'Template'); } }
									);
								}
							},
								el('div', null, el('strong', null, template.name), el('span', null, 'By ' + (template.author || 'Unknown'))),
								button('am-vb-button am-vb-button--wide', 'Add to page', function () { insertSavedTemplateOnHome(template); })
							);
						}))
					)
					: null,
				el('div', { className: 'am-vb-live-tree__note' },
					el('strong', null, 'Responsive by default'),
					el('p', null, 'Every new section gets separate Desktop, Tablet and Mobile positions. Select any new layer in the canvas to move, stretch, duplicate or delete it.')
				)
			);
		}

		function renderLeftbar() {
			var viewOnly = liveParity && 'view' === canvasMode;
			var tabs = viewOnly
				? [
					['pages', 'Pages']
				]
				: liveParity
				? [
					['pages', 'Pages'],
					['structure', 'Regions'],
					['templates', 'Templates']
				]
				: [
					['pages', 'Pages'],
					['structure', 'Layers'],
					['add', 'Add'],
					['media', 'Media'],
					['templates', 'Templates']
				];
			var content = viewOnly || 'pages' === leftTab
				? renderPagePanel()
				: 'structure' === leftTab
					? (liveParity ? renderLiveOutline() : el(Fragment, null,
						el('div', { className: 'am-vb-panel-title' },
							el('div', null, el('strong', null, 'Layers'), el('span', null, 'Shift-click for multi-select'))
						),
						el('p', { className: 'am-vb-keyboard-reorder-hint' }, 'Select a section or layer, then press the keyboard ↑ or ↓ key to move it one place.'),
						el('ul', { className: 'am-vb-outline' }, documentState.sections.map(renderOutlineRow)),
						el('div', { className: 'am-vb-action-row' },
							button('am-vb-button', 'Group', groupSelection, { disabled: selection.elementIds.length < 2 || readOnly }),
							button('am-vb-button', 'Ungroup', ungroupSelection, { disabled: !selection.elementIds.length || readOnly })
						)
					))
					: 'add' === leftTab
						? renderAddPanel()
						: 'media' === leftTab
							? renderMediaPanel()
							: (liveParity ? renderLiveTemplatesPanel() : renderTemplatesPanel());
			return el('aside', { className: 'am-vb-leftbar' },
				el('div', { className: 'am-vb-tabs am-vb-tabs--scroll' }, tabs.map(function (tab) {
					return button(leftTab === tab[0] ? 'is-active' : '', tab[1], function () { setLeftTab(tab[0]); }, { key: tab[0] });
				})),
				el('div', { className: 'am-vb-leftbar__content' }, content)
			);
		}

		function moveOrResizeGenericNode(section, element, pointerEvent) {
			if ('layered' !== section.settings.layout || elementLocked(section, element) || 0 !== pointerEvent.button) {
				return;
			}
			var node = pointerEvent.currentTarget;
			var parent = node.parentElement;
			if (!node || !parent) { return; }
			var rect = node.getBoundingClientRect();
			var parentRect = parent.getBoundingClientRect();
			var resizeGesture = rect.right - pointerEvent.clientX < 20 && rect.bottom - pointerEvent.clientY < 20;
			var startX = pointerEvent.clientX;
			var startY = pointerEvent.clientY;
			var startLeft = rect.left - parentRect.left;
			var startTop = rect.top - parentRect.top;
			var startWidth = rect.width;
			var startHeight = rect.height;
			var moved = false;
			function onMove(event) {
				var dx = event.clientX - startX;
				var dy = event.clientY - startY;
				if (!moved && Math.abs(dx) + Math.abs(dy) < 4) { return; }
				moved = true;
				event.preventDefault();
				if (resizeGesture) {
					node.style.width = Math.max(30, startWidth + dx) + 'px';
					node.style.height = Math.max(20, startHeight + dy) + 'px';
				} else {
					node.style.left = Math.max(0, startLeft + dx) + 'px';
					node.style.top = Math.max(0, startTop + dy) + 'px';
				}
			}
			function onUp() {
				window.document.removeEventListener('pointermove', onMove, true);
				window.document.removeEventListener('pointerup', onUp, true);
				if (!moved) { return; }
				var finalRect = node.getBoundingClientRect();
				var finalParentRect = parent.getBoundingClientRect();
				commit(function (next) {
					var targetSection = next.sections.find(function (candidate) { return candidate.id === section.id; });
					var target = targetSection.elements.find(function (candidate) { return candidate.id === element.id; });
					target.styles = target.styles || {};
					target.styles[device] = target.styles[device] || {};
					if (resizeGesture) {
						target.styles[device].width = Math.round(finalRect.width) + 'px';
						target.styles[device].height = Math.round(finalRect.height) + 'px';
					} else {
						target.styles[device].positionX = Math.round(finalRect.left - finalParentRect.left) + 'px';
						target.styles[device].positionY = Math.round(finalRect.top - finalParentRect.top) + 'px';
					}
				}, resizeGesture ? 'Layer resized' : 'Layer moved');
			}
			window.document.addEventListener('pointermove', onMove, true);
			window.document.addEventListener('pointerup', onUp, true);
		}

		function renderCanvasElement(section, element) {
			var style = cssStyle(mergeResponsive(element.styles, device), 'layered' === section.settings.layout);
			var active = selection.sectionId === section.id && selection.elementIds.indexOf(element.id) !== -1;
			var group = (section.groups || []).find(function (item) { return item.id === element.groupId; });
			var hidden = false === element.visible || (group && false === group.visible);
			var lockedItem = elementLocked(section, element);
			var className = 'am-vb-node am-vb-node-type--' + element.type
				+ (active ? ' is-selected' : '')
				+ (active && 'layered' === section.settings.layout ? ' am-vb-can-resize' : '')
				+ (lockedItem ? ' is-locked' : '')
				+ (hidden ? ' is-builder-hidden' : '');
			var common = {
				className: className,
				style: style,
				onClick: function (event) {
					event.stopPropagation();
					selectElement(section.id, element.id, event);
				},
				onPointerDown: function (event) { moveOrResizeGenericNode(section, element, event); },
				key: element.id
			};
			if ('text' === element.type) {
				var tag = ['h1', 'h2', 'h3', 'h4', 'p', 'div'].indexOf(element.tag) !== -1 ? element.tag : 'p';
				return el(tag, Object.assign({}, common, {
					contentEditable: !lockedItem,
					suppressContentEditableWarning: true,
					'data-element-id': element.id,
					onFocus: function (event) {
						editableRef.current = event.currentTarget;
						initialTextRef.current = event.currentTarget.innerHTML;
					},
					onBlur: function (event) {
						finishTextEdit(element.id, event.currentTarget.innerHTML);
						editableRef.current = null;
					},
					dangerouslySetInnerHTML: { __html: element.content || '' }
				}));
			}
			if ('button' === element.type) {
				return el('a', Object.assign({}, common, { href: '#', onClick: common.onClick }), element.label || 'Button');
			}
			if ('image' === element.type) {
				return element.src
					? el('img', Object.assign({}, common, { src: element.src, alt: element.alt || '' }))
					: el('div', Object.assign({}, common, { className: common.className + ' am-vb-image-placeholder' }), 'Choose an image');
			}
			if ('shape' === element.type) {
				return el('span', Object.assign({}, common, {
					className: common.className + ' am-vb-shape--' + (element.shape || 'rectangle')
				}));
			}
			return el('div', Object.assign({}, common, { className: common.className + ' am-vb-unknown-node' }),
				el('strong', null, 'Unsupported layer'),
				el('span', null, element.originalType || 'unknown')
			);
		}

		function renderLiveCanvas() {
			var viewportWidth = 'desktop' === device ? 1440 : ('tablet' === device ? 768 : 390);
			var viewportHeight = 'mobile' === device ? 844 : 900;
			var scaledWidth = Math.round(viewportWidth * liveZoom);
			var scaledHeight = Math.round(viewportHeight * liveZoom);
			var region = liveRegionData(liveRegion);
			var pageName = (activeExactRoute && activeExactRoute.title) || 'Home';
			var canvasUrl = (activeExactRoute && activeExactRoute.canvasUrl) || config.liveCanvasUrl;
			var modeCanvasUrl = new URL(canvasUrl, config.liveHomeUrl);
			modeCanvasUrl.searchParams.set('am_visual_mode', canvasMode);

			return el('main', { className: 'am-vb-canvas-shell am-vb-canvas-shell--live am-vb-canvas-shell--' + canvasMode },
				el('div', { className: 'am-vb-live-status' },
					el('div', { className: 'am-vb-live-status__history' },
						button('am-vb-sessions-link', 'All saved sessions', openSavedSessions, {
							title: 'Open named versions from every page'
						})
					),
					el('div', { className: 'am-vb-canvas-mode', role: 'group', 'aria-label': 'Canvas mode' },
						button('am-vb-canvas-mode__button' + ('view' === canvasMode ? ' is-active' : ''), 'View', function () { switchCanvasMode('view'); }, {
							'aria-pressed': 'view' === canvasMode,
							title: 'Browse the real website without editing'
						}),
						button('am-vb-canvas-mode__button' + ('edit' === canvasMode ? ' is-active' : ''), 'Edit', function () { switchCanvasMode('edit'); }, {
							'aria-pressed': 'edit' === canvasMode,
							title: 'Select, type, drag and resize website content'
						})
					),
					el('div', { className: 'am-vb-live-status__selection' },
						el('div', null,
							el('span', null, 'view' === canvasMode ? 'Browsing' : 'Selected'),
							el('strong', null, 'view' === canvasMode ? (liveBrowsePath || 'Links and menus are active') : (liveElement ? liveElement.label : region.label))
						),
						'edit' === canvasMode
							? button('am-vb-focus-toggle', liveFocus ? 'Show whole page' : 'Focus section', function () { setLiveFocus(!liveFocus); })
							: null
					)
				),
				el('div', { className: 'am-vb-live-workbench' },
					el('div', {
						className: 'am-vb-live-viewport-frame',
						style: { width: scaledWidth + 'px', height: scaledHeight + 'px' }
					},
						el('div', {
							className: 'am-vb-live-viewport',
							style: {
								width: viewportWidth + 'px',
								height: viewportHeight + 'px',
								transform: 'scale(' + liveZoom + ')'
							}
						},
							el('iframe', {
								ref: liveFrameRef,
								key: slug + '-' + canvasMode,
								className: 'am-vb-live-iframe',
								src: modeCanvasUrl.href,
								title: 'Exact Alexandra Montessori ' + pageName + ' page',
								onLoad: handleLiveCanvasLoad
							})
						)
					),
					liveCanvasLoading
						? el('div', { className: 'am-vb-live-canvas-notice', role: 'status' }, el('span', { className: 'spinner is-active' }), el('strong', null, 'Loading the live website...'))
						: liveCanvasError
							? el('div', { className: 'am-vb-live-canvas-notice am-vb-live-canvas-notice--error', role: 'alert' },
								el('strong', null, 'The website canvas is unavailable'),
								el('p', null, liveCanvasError),
								button('am-vb-button am-vb-button--primary', 'Reload canvas', function () {
									setLiveCanvasLoading(true);
									setLiveCanvasError('');
									if (liveFrameRef.current && liveFrameRef.current.contentWindow) {
										liveFrameRef.current.contentWindow.location.reload();
									}
								})
							)
							: null
				)
			);
		}

		function renderCanvas() {
			if (liveParity) {
				return renderLiveCanvas();
			}
			var width = 'desktop' === device ? '100%' : ('tablet' === device ? '820px' : '390px');
			return el('main', { className: 'am-vb-canvas-shell' },
				readOnly
					? el('div', { className: 'am-vb-lock-banner' }, 'Read-only: ' + (lock.userName || 'another session') + ' is editing this page. Lock refreshes automatically.')
					: null,
				recovery
					? el('div', { className: 'am-vb-recovery-banner' },
						el('span', null, 'A newer browser recovery copy is available from ' + new Date(recovery.savedAt).toLocaleString() + '.'),
						button('am-vb-button am-vb-button--primary', 'Restore', restoreRecovery),
						button('am-vb-button', 'Discard', discardRecovery)
					)
					: null,
				el('div', { className: 'am-vb-stage', style: { width: width } },
					documentState.sections.length
						? el('div', { className: 'am-vb-page am-vb-page-card' }, documentState.sections.map(function (section) {
							var sectionStyle = cssStyle(mergeResponsive(section.settings, device), false);
							return el('section', {
								className: 'am-vb-section am-vb-layout--' + (section.settings.layout || 'stack')
									+ (selection.sectionId === section.id && !selection.elementIds.length ? ' is-selected' : '')
									+ (false === section.visible ? ' is-builder-hidden' : '')
									+ (section.locked ? ' is-locked' : ''),
								key: section.id,
								style: sectionStyle,
								onClick: function () { selectSection(section.id); }
							}, section.elements.map(function (element) {
								return renderCanvasElement(section, element);
							}));
						}))
						: el('div', { className: 'am-vb-empty-canvas' },
							el('strong', null, 'This page has no sections yet'),
							button('am-vb-button am-vb-button--primary', 'Add first section', function () { addSection(); }, { disabled: readOnly })
						)
				)
			);
		}

		function styleValue(styles, key) {
			return (styles && styles[device] && undefined !== styles[device][key]) ? styles[device][key] : '';
		}

		function renderActions() {
			return el('div', { className: 'am-vb-action-row' },
				button('am-vb-button', 'Move up', function () { moveSelection(-1); }, { disabled: readOnly }),
				button('am-vb-button', 'Move down', function () { moveSelection(1); }, { disabled: readOnly }),
				button('am-vb-button', 'Duplicate', duplicateSelection, { disabled: readOnly }),
				button('am-vb-button am-vb-button--danger', 'Delete', deleteSelection, { disabled: readOnly })
			);
		}

		function renderRichTools() {
			return el('div', { className: 'am-vb-style-tools' },
				button('am-vb-tool', 'B', function () { formatText('bold'); }, { title: 'Bold' }),
				button('am-vb-tool', 'I', function () { formatText('italic'); }, { title: 'Italic' }),
				button('am-vb-tool', 'U', function () { formatText('underline'); }, { title: 'Underline' }),
				button('am-vb-tool', '• List', function () { formatText('insertUnorderedList'); }),
				button('am-vb-tool', '1. List', function () { formatText('insertOrderedList'); })
			);
		}

		function renderSectionInspector(section) {
			var disabled = sectionLocked(section);
			return el(Fragment, null,
				el('div', { className: 'am-vb-inspector-header' },
					el('div', null, el('strong', null, section.name), el('span', null, 'Section settings'))
				),
				el('div', { className: 'am-vb-inspector-section' },
					el('h3', null, 'Section'),
					el(Field, { label: 'Name', value: section.name, disabled: disabled, onChange: function (value) { updateSectionField('name', value); } }),
					el(Field, { label: 'Visible', type: 'checkbox', value: section.visible, disabled: readOnly || section.locked, onChange: function (value) { updateSectionField('visible', value); } }),
					el(Field, { label: 'Lock section', type: 'checkbox', value: section.locked, disabled: readOnly, onChange: function (value) { updateSectionField('locked', value); } }),
					el(Field, {
						label: 'Layout', kind: 'select', value: section.settings.layout || 'stack', disabled: disabled,
						options: [
							{ value: 'stack', label: 'Stack' },
							{ value: 'grid', label: 'Two-column grid' },
							{ value: 'layered', label: 'Free layered canvas' }
						],
						onChange: function (value) {
							commit(function (next) {
								var item = next.sections.find(function (candidate) { return candidate.id === section.id; });
								item.settings.layout = value;
							}, 'Layout updated');
						}
					})
				),
				el('div', { className: 'am-vb-inspector-section' },
					el('h3', null, titleCase(device) + ' appearance'),
					el(Field, { label: 'Background colour', type: 'color', value: styleValue(section.settings, 'backgroundColor') || '#ffffff', disabled: disabled, onChange: function (value) { updateSectionStyle('backgroundColor', value); } }),
					el(Field, { label: 'Background image URL', value: styleValue(section.settings, 'backgroundImage'), disabled: disabled, onChange: function (value) { updateSectionStyle('backgroundImage', value); } }),
					el(Field, { label: 'Minimum height', value: styleValue(section.settings, 'minHeight'), placeholder: '520px', disabled: disabled, onChange: function (value) { updateSectionStyle('minHeight', value); } }),
					el(Field, { label: 'Padding', value: styleValue(section.settings, 'padding'), placeholder: '64px 8%', disabled: disabled, onChange: function (value) { updateSectionStyle('padding', value); } }),
					el(Field, { label: 'Gap', value: styleValue(section.settings, 'gap'), placeholder: '24px', disabled: disabled, onChange: function (value) { updateSectionStyle('gap', value); } }),
					el(Field, { label: 'Text alignment', kind: 'select', value: styleValue(section.settings, 'textAlign'), disabled: disabled, options: [
						{ value: '', label: 'Inherit' }, { value: 'left', label: 'Left' }, { value: 'center', label: 'Centre' }, { value: 'right', label: 'Right' }
					], onChange: function (value) { updateSectionStyle('textAlign', value); } }),
					el(Field, { label: 'Overflow', kind: 'select', value: styleValue(section.settings, 'overflow'), disabled: disabled, options: [
						{ value: '', label: 'Default' }, { value: 'hidden', label: 'Hidden' }, { value: 'visible', label: 'Visible' }
					], onChange: function (value) { updateSectionStyle('overflow', value); } })
				),
				config.canManageTemplates
					? el('div', { className: 'am-vb-inspector-section' }, button('am-vb-button', 'Save as reusable section', saveTemplate, { disabled: disabled }))
					: null,
				renderActions()
			);
		}

		function renderElementInspector(element) {
			var disabled = elementLocked(selected.section, element);
			var fields = [];
			if ('text' === element.type) {
				fields.push(el(Field, {
					key: 'tag', label: 'HTML style', kind: 'select', value: element.tag || 'p', disabled: disabled,
					options: [
						{ value: 'h1', label: 'Heading 1' }, { value: 'h2', label: 'Heading 2' },
						{ value: 'h3', label: 'Heading 3' }, { value: 'h4', label: 'Heading 4' },
						{ value: 'p', label: 'Paragraph' }, { value: 'div', label: 'Rich text block' }
					],
					onChange: function (value) { updateElementField('tag', value); }
				}));
				fields.push(renderRichTools());
				fields.push(el(Field, { key: 'content', label: 'HTML content', kind: 'textarea', value: element.content, disabled: disabled, onChange: function (value) { updateElementField('content', value); } }));
			} else if ('button' === element.type) {
				fields.push(el(Field, { key: 'label', label: 'Button label', value: element.label, disabled: disabled, onChange: function (value) { updateElementField('label', value); } }));
				fields.push(el(LinkField, { key: 'href', fieldId: 'visual-button-link', label: 'Destination page', value: element.href, suggestions: availableLinks, disabled: disabled, onChange: function (value) { updateElementField('href', value); } }));
				if (element.href) {
					fields.push(button('am-vb-button am-vb-button--wide', 'Open linked page', function () { openLinkedPage(element.href); }, { key: 'open-href' }));
				}
				fields.push(el(Field, { key: 'new', label: 'Open in new window', type: 'checkbox', value: element.newWindow, disabled: disabled, onChange: function (value) { updateElementField('newWindow', value); } }));
			} else if ('image' === element.type) {
				fields.push(button('am-vb-button', 'Replace from WordPress media', chooseImage, { key: 'replace', disabled: disabled }));
				fields.push(el(Field, { key: 'alt', label: 'Alternative text (required)', value: element.alt, disabled: disabled, onChange: function (value) { updateElementField('alt', value); } }));
				fields.push(el(Field, { key: 'src', label: 'Image URL', value: element.src, disabled: disabled, onChange: function (value) { updateElementField('src', value); } }));
			} else if ('shape' === element.type) {
				fields.push(el(Field, {
					key: 'shape', label: 'Shape', kind: 'select', value: element.shape || 'rectangle', disabled: disabled,
					options: ['rectangle', 'circle', 'pill', 'line', 'triangle'].map(function (shape) {
						return { value: shape, label: titleCase(shape) };
					}),
					onChange: function (value) { updateElementField('shape', value); }
				}));
			} else {
				fields.push(el('div', { className: 'am-vb-warning', key: 'unknown' },
					el('strong', null, 'Unsupported layer'),
					el('p', null, 'This layer is preserved so no data is silently lost. Replace it before publishing.')
				));
			}
			return el(Fragment, null,
				el('div', { className: 'am-vb-inspector-header' },
					el('div', null, el('strong', null, element.name), el('span', null, titleCase(element.type) + ' layer'))
				),
				el('div', { className: 'am-vb-inspector-section' },
					el('h3', null, 'Content and layer'),
					el(Field, { label: 'Layer name', value: element.name, disabled: disabled, onChange: function (value) { updateElementField('name', value); } }),
					el(Field, { label: 'Visible', type: 'checkbox', value: element.visible, disabled: readOnly || selected.section.locked || element.locked, onChange: function (value) { updateElementField('visible', value); } }),
					el(Field, { label: 'Lock layer', type: 'checkbox', value: element.locked, disabled: readOnly || selected.section.locked, onChange: function (value) { updateElementField('locked', value); } }),
					fields
				),
				el('div', { className: 'am-vb-inspector-section' },
					el('h3', null, titleCase(device) + ' typography and size'),
					el('div', { className: 'am-vb-field-row' },
						el(Field, { label: 'Width', value: styleValue(element.styles, 'width'), placeholder: '100%', disabled: disabled, onChange: function (value) { updateElementStyle('width', value); } }),
						el(Field, { label: 'Height', value: styleValue(element.styles, 'height'), placeholder: 'auto', disabled: disabled, onChange: function (value) { updateElementStyle('height', value); } })
					),
					el('div', { className: 'am-vb-field-row' },
						el(Field, { label: 'Font size', value: styleValue(element.styles, 'fontSize'), placeholder: '18px', disabled: disabled, onChange: function (value) { updateElementStyle('fontSize', value); } }),
						el(Field, { label: 'Line height', value: styleValue(element.styles, 'lineHeight'), placeholder: '1.5', disabled: disabled, onChange: function (value) { updateElementStyle('lineHeight', value); } })
					),
					el('div', { className: 'am-vb-field-row' },
						el(Field, { label: 'Text colour', type: 'color', value: styleValue(element.styles, 'color') || '#20372a', disabled: disabled, onChange: function (value) { updateElementStyle('color', value); } }),
						el(Field, { label: 'Fill colour', type: 'color', value: styleValue(element.styles, 'backgroundColor') || '#ffffff', disabled: disabled, onChange: function (value) { updateElementStyle('backgroundColor', value); } })
					),
					el(Field, { label: 'Font weight', kind: 'select', value: styleValue(element.styles, 'fontWeight'), disabled: disabled, options: [
						{ value: '', label: 'Default' }, { value: '400', label: 'Regular' }, { value: '500', label: 'Medium' },
						{ value: '600', label: 'Semi-bold' }, { value: '700', label: 'Bold' }, { value: '800', label: 'Extra bold' }
					], onChange: function (value) { updateElementStyle('fontWeight', value); } }),
					el(Field, { label: 'Text alignment', kind: 'select', value: styleValue(element.styles, 'textAlign'), disabled: disabled, options: [
						{ value: '', label: 'Inherit' }, { value: 'left', label: 'Left' }, { value: 'center', label: 'Centre' }, { value: 'right', label: 'Right' }
					], onChange: function (value) { updateElementStyle('textAlign', value); } }),
					el(Field, { label: 'Padding', value: styleValue(element.styles, 'padding'), placeholder: '12px 20px', disabled: disabled, onChange: function (value) { updateElementStyle('padding', value); } }),
					el(Field, { label: 'Margin', value: styleValue(element.styles, 'margin'), placeholder: '0 auto', disabled: disabled, onChange: function (value) { updateElementStyle('margin', value); } }),
					el(Field, { label: 'Corner radius', value: styleValue(element.styles, 'borderRadius'), placeholder: '12px', disabled: disabled, onChange: function (value) { updateElementStyle('borderRadius', value); } }),
					el(Field, { label: 'Shadow', value: styleValue(element.styles, 'boxShadow'), placeholder: '0 12px 30px rgba(0,0,0,.15)', disabled: disabled, onChange: function (value) { updateElementStyle('boxShadow', value); } }),
					el(Field, { label: 'Opacity', value: styleValue(element.styles, 'opacity'), placeholder: '1', disabled: disabled, onChange: function (value) { updateElementStyle('opacity', value); } }),
					'layered' === selected.section.settings.layout
						? el(Fragment, null,
							el('div', { className: 'am-vb-field-row' },
								el(Field, { label: 'X position', value: styleValue(element.styles, 'positionX'), placeholder: '10%', disabled: disabled, onChange: function (value) { updateElementStyle('positionX', value); } }),
								el(Field, { label: 'Y position', value: styleValue(element.styles, 'positionY'), placeholder: '20%', disabled: disabled, onChange: function (value) { updateElementStyle('positionY', value); } })
							),
							el('div', { className: 'am-vb-field-row' },
								el(Field, { label: 'Rotation', value: styleValue(element.styles, 'rotate'), placeholder: '0deg', disabled: disabled, onChange: function (value) { updateElementStyle('rotate', value); } }),
								el(Field, { label: 'Layer order', value: styleValue(element.styles, 'zIndex'), placeholder: '1', disabled: disabled, onChange: function (value) { updateElementStyle('zIndex', value); } })
							)
						)
						: null
				),
				renderActions()
			);
		}

		function renderMultiInspector() {
			return el(Fragment, null,
				el('div', { className: 'am-vb-inspector-header' },
					el('div', null, el('strong', null, selection.elementIds.length + ' layers selected'), el('span', null, 'Multi-selection tools'))
				),
				el('div', { className: 'am-vb-inspector-section' },
					el('p', null, 'Group related layers so visibility and edit locks can be managed together.'),
					el('div', { className: 'am-vb-action-row' },
						button('am-vb-button am-vb-button--primary', 'Group selected', groupSelection, { disabled: selection.elementIds.length < 2 || readOnly }),
						button('am-vb-button', 'Ungroup', ungroupSelection, { disabled: readOnly })
					)
				),
				renderActions()
			);
		}

		function renderHistory() {
			return el(Fragment, null,
				el('div', { className: 'am-vb-inspector-section' },
					el('h3', null, 'Page'),
					el(Field, {
						label: 'Page title',
						value: documentState.title,
						disabled: readOnly,
						onChange: function (value) {
							commit(function (next) { next.title = value; }, 'Page title updated');
						}
					}),
					el('p', { className: 'am-vb-device-note' },
						'Workflow: ' + titleCase(workflow) + ' · Schema v' + (documentState.version || 2)
						+ (publication.hasSnapshot
							? (publication.hasUnpublishedChanges ? ' · Published version is safely unchanged' : ' · Matches published version')
							: ' · No public snapshot yet')
					)
				),
				el('div', { className: 'am-vb-inspector-section' },
					el('h3', null, 'Publishing readiness'),
					readiness.length
						? el('ul', { className: 'am-vb-readiness' }, readiness.map(function (issue, index) {
							return el('li', { key: index }, issue);
						}))
						: el('p', { className: 'am-vb-ready' }, 'Ready to publish.')
				),
				el('div', { className: 'am-vb-inspector-section' },
					el('h3', null, 'Revision history'),
					revisions.length
						? el('div', { className: 'am-vb-revisions' }, revisions.slice(0, 8).map(function (revision) {
							return el('div', { key: revision.id },
								el('span', { className: 'am-vb-revision-copy' }, revision.dateLabel + ' · ' + revision.author),
								button('am-vb-mini-action', 'Restore', function () { restoreRevision(revision.id); }, { disabled: readOnly })
							);
						}))
						: el('p', null, 'No earlier saves yet.')
				),
				config.canViewAudit
					? el('div', { className: 'am-vb-inspector-section' },
						el('h3', null, 'Recent activity'),
						audit.length
							? el('div', { className: 'am-vb-audit-list' }, audit.slice(0, 10).map(function (event) {
								return el('div', { key: event.id },
									el('strong', null, titleCase(event.action)),
									el('span', null, event.author + ' · ' + event.dateLabel)
								);
							}))
							: el('p', null, 'No activity recorded yet.')
					)
					: null
			);
		}

		function updateSelectedElement(field, value) {
			if (!liveElement) {
				return;
			}
			updateHomeElementValue(liveElement.key, field, value);
			setLiveElement(Object.assign({}, liveElement, (function () {
				var patch = {};
				patch[field] = value;
				return patch;
			})()));
		}

		/**
		 * Render the schema toolkit for the selected element. Every element type
		 * gets the same base controls here, so there are no dead ends - only the
		 * type packs differ, and those are declared in PHP.
		 */
		function renderSchemaStyles(options) {
			var settings = options || {};
			if (!StylePanel || !config.styleSchema || !liveElement) {
				return null;
			}

			return el(StylePanel, {
				schema: config.styleSchema,
				type: settings.type || liveElement.type || 'text',
				record: settings.record || {},
				device: device,
				readOnly: !!settings.readOnly,
				allowStructural: false !== settings.allowStructural,
				skip: settings.skip || {},
				handlers: {
					onFlatChange: function (name, value) { updateHomeElementValue(liveElement.key, name, value); },
					onDeviceChange: function (name, value) { updateHomeElementDeviceValue(liveElement.key, name, value); }
				}
			});
		}

		/**
		 * Copy / Paste / Duplicate / Remove for whatever is selected.
		 *
		 * One row, rendered for every kind of selection, because a client who
		 * can duplicate on Home but not on Careers has been given half a tool.
		 * Each button routes to the mechanism that genuinely exists for that
		 * kind of element rather than pretending they are all the same thing.
		 *
		 * @returns {object|null} React element.
		 */
		function renderElementActions() {
			if (!liveElement || !liveElement.key || readOnly) {
				return null;
			}
			var record = homeElementData(liveElement.key);
			var hidden = !liveElement.custom && elementIsHidden(record);
			var hasClipboard = !!clipboardRef.current;
			var actions = [
				button('am-vb-button', 'Copy', copySelectedElement, { key: 'copy', title: 'Ctrl+C' }),
				button('am-vb-button', 'Paste', pasteClipboardElement, { key: 'paste', title: 'Ctrl+V', disabled: !hasClipboard }),
				button('am-vb-button', 'Duplicate', duplicateSelectedElement, { key: 'duplicate', title: 'Ctrl+D' })
			];

			// A global header or footer component deliberately has no per-page
			// override, so it gets no Remove. Copy and Duplicate still work:
			// they create a new independent layer rather than forking the global.
			if (!liveElement.hardProtected) {
				actions.push(hidden
					? button('am-vb-button', 'Restore', function () { setElementHidden(liveElement.key, false); }, { key: 'restore' })
					: button('am-vb-button am-vb-button--danger', 'Remove', removeSelectedElement, { key: 'remove' }));
			}

			return el('div', { className: 'am-vb-inspector-section' },
				el('div', { className: 'am-vb-action-row am-vb-action-row--wrap' }, actions),
				el('p', { className: 'am-vb-device-note' },
					hidden
						? 'This item is currently removed from the public page.'
						: (hasClipboard
							? 'Ctrl+Shift+V pastes only the copied look onto this item.'
							: 'Copy an item, then paste it here or on any other page.'))
			);
		}

		function renderResponsiveScopeControl() {
			return el('div', { className: 'am-vb-responsive-scope', role: 'group', 'aria-label': 'Responsive edit scope' },
				button('am-vb-responsive-scope__button' + ('all' === responsiveScope ? ' is-active' : ''), 'All devices', function () { setResponsiveScope('all'); }),
				button('am-vb-responsive-scope__button' + ('device' === responsiveScope ? ' is-active' : ''), 'This device', function () { setResponsiveScope('device'); })
			);
		}

		function renderCustomHomeElementFields() {
			var item = liveElement ? homeCustomItem(liveElement.customSectionId, liveElement.customItemId) : null;
			if (!item) { return null; }
			var content = homeElementData(item.key);
			var values = item[device] || {};
			var section = homeCustomSection(liveElement.customSectionId);
			var sectionHeight = Number((section && section[device] || {}).height || 520);
			var viewportWidth = 'desktop' === device ? 1440 : ('tablet' === device ? 768 : 390);
			var isText = ['text', 'button', 'tabs'].indexOf(item.type) !== -1;
			var controls = [];

			controls.push(el(Field, {
				key: 'name', label: 'Layer name', value: item.name || titleCase(item.type),
				onChange: function (value) { updateCustomItem(liveElement.customSectionId, item.id, 'name', value); }
			}));
			if (isText) {
				controls.push(el(Field, {
					key: 'value', label: 'Text', kind: 'textarea', value: content.value || '',
					onChange: function (value) { updateHomeElementValue(item.key, 'value', value); }
				}));
				controls.push(el(LinkField, {
					key: 'href', label: 'Click link (optional)', value: content.href || '', placeholder: '/page or https://example.com',
					suggestions: availableLinks, fieldId: item.key + '-link',
					onChange: function (value) { updateHomeElementValue(item.key, 'href', value); }
				}));
				if (content.href) {
					controls.push(button('am-vb-button am-vb-button--wide', 'Open linked page', function () { openLinkedPage(content.href); }, { key: 'open-href' }));
				}
				controls.push(el(Field, {
					key: 'link-description', label: 'Link description (optional)', value: content.linkDescription || '', placeholder: 'What this link opens',
					onChange: function (value) { updateHomeElementValue(item.key, 'linkDescription', value); }
				}));
			} else if (['image', 'logo'].indexOf(item.type) !== -1) {
				controls.push(el('div', { key: 'media', className: 'am-vb-home-media-card' },
					content.src ? el('img', { src: content.src, alt: '', className: 'am-vb-home-media-card__preview' }) : el('div', { className: 'am-vb-home-media-card__placeholder' }, 'logo' === item.type ? 'Logo layer' : 'Photo layer'),
					button('am-vb-button am-vb-button--primary', 'logo' === item.type ? 'Upload / replace logo' : 'Upload / replace photo', function () { chooseHomeMedia(item.key, 'image'); })
				));
				controls.push(el(Field, {
					key: 'alt', label: 'Accessible description', value: content.alt || '',
					onChange: function (value) { updateHomeElementValue(item.key, 'alt', value); }
				}));
			} else if ('shape' === item.type) {
				controls.push(el(Field, {
					key: 'shape', label: 'Shape', kind: 'select', value: item.shape || 'rectangle',
					options: ['rectangle', 'circle', 'pill', 'line'].map(function (shape) { return { value: shape, label: titleCase(shape) }; }),
					onChange: function (value) { updateCustomItem(liveElement.customSectionId, item.id, 'shape', value); }
				}));
			}

			if (['shape', 'button'].indexOf(item.type) !== -1) {
				controls.push(el(Field, {
					key: 'fill', label: 'Fill colour', type: 'color', value: item.backgroundColor || '#a9c6a2',
					onChange: function (value) { updateCustomItem(liveElement.customSectionId, item.id, 'backgroundColor', value); }
				}));
			}
			if (isText) {
				controls.push(el(Field, {
					key: 'color', label: 'Text colour', type: 'color', value: item.color || '#20372a',
					onChange: function (value) { updateCustomItem(liveElement.customSectionId, item.id, 'color', value); }
				}));
			}
			controls.push(el(RangeField, { key: 'x', label: 'Left position', value: values.x || 0, min: 0, max: Math.max(0, viewportWidth - 30), unit: ' px', onChange: function (value) { updateCustomItem(liveElement.customSectionId, item.id, 'x', value, device); } }));
			controls.push(el(RangeField, { key: 'y', label: 'Top position', value: values.y || 0, min: 0, max: Math.max(0, sectionHeight - 20), unit: ' px', onChange: function (value) { updateCustomItem(liveElement.customSectionId, item.id, 'y', value, device); } }));
			controls.push(el(RangeField, { key: 'width', label: 'Width / stretch', value: values.width || 100, min: 30, max: viewportWidth, unit: ' px', onChange: function (value) { updateCustomItem(liveElement.customSectionId, item.id, 'width', value, device); } }));
			controls.push(el(RangeField, { key: 'height', label: 'Height / stretch', value: values.height || 100, min: 20, max: Math.max(200, sectionHeight), unit: ' px', onChange: function (value) { updateCustomItem(liveElement.customSectionId, item.id, 'height', value, device); } }));
			if (isText) {
				controls.push(el(RangeField, { key: 'font', label: 'Text size', value: values.fontSize || 16, min: 10, max: 120, unit: ' px', onChange: function (value) { updateCustomItem(liveElement.customSectionId, item.id, 'fontSize', value, device); } }));
			}
			controls.push(el(RangeField, { key: 'radius', label: 'Corner roundness', value: item.borderRadius || 0, min: 0, max: 200, unit: ' px', onChange: function (value) { updateCustomItem(liveElement.customSectionId, item.id, 'borderRadius', value); } }));

			return el(Fragment, null,
				el('div', { className: 'am-vb-inspector-section am-vb-home-element-controls' },
					el('div', { className: 'am-vb-live-controls__title' }, el('h3', null, titleCase(item.type) + ' layer'), el('span', null, titleCase(device))),
					renderResponsiveScopeControl(),
					controls,
					// A free layer is still an element, so it gets the same schema
					// toolkit as everything else rather than only the short hardcoded
					// list above. The skipped properties are the ones a free layer
					// already owns through `updateCustomItem`: it is absolutely
					// placed, so x/y and width/height are its position, and its
					// colour and corner roundness are written as direct inline styles
					// that would beat anything the schema set. Offering either twice
					// would give the client two controls, one of which silently loses.
					renderSchemaStyles({
						record: content,
						type: content.type || item.type,
						skip: {
							offsetX: true,
							offsetY: true,
							scale: true,
							width: true,
							height: true,
							fontSize: true,
							radius: true,
							background: true,
							color: true
						}
					})
				),
				renderElementActions(),
				el('div', { className: 'am-vb-inspector-section' },
					el('div', { className: 'am-vb-action-row am-vb-action-row--wrap' },
						button('am-vb-button', 'Backward', function () { moveCustomItem(liveElement.customSectionId, item.id, -1); }),
						button('am-vb-button', 'Forward', function () { moveCustomItem(liveElement.customSectionId, item.id, 1); })
					),
					button('am-vb-button am-vb-button--primary am-vb-button--wide', homeDesignSaving ? 'Saving…' : 'Save', requestNamedSave, { disabled: homeDesignSaving || !homeDesignDirty })
				)
			);
		}

		function renderHomeElementFields() {
			if (!liveElement) {
				return null;
			}
			if (liveElement.custom) {
				return renderCustomHomeElementFields();
			}
			var item = homeElementData(liveElement.key);
			if (liveElement.protected) {
				var protectedValues = item[device] || {};
				var presentationControls = liveElement.hardProtected
					? el('div', { className: 'am-vb-inspector-section' }, el('p', { className: 'am-vb-live-detail' }, 'Open Home or the connected global settings area to change this once for the whole website. Page-specific overrides are intentionally disabled.'))
					: el('div', { className: 'am-vb-inspector-section am-vb-home-element-controls' },
						el('div', { className: 'am-vb-live-controls__title' },
							el('h3', null, 'Appearance'),
							el('span', null, titleCase(device))
						),
						el('p', { className: 'am-vb-device-note' }, 'The connected content is protected, but how it looks is entirely yours to change.'),
						renderResponsiveScopeControl(),
						renderSchemaStyles({ record: item, allowStructural: false })
					);
				return el(Fragment, null,
					el('div', { className: 'am-vb-inspector-section' },
						el('div', { className: 'am-vb-parity-card am-vb-parity-card--bound' },
							el('span', { className: 'am-vb-parity-card__check', 'aria-hidden': 'true' }, '↔'),
							el('div', null,
								el('strong', null, liveElement.sourceLabel || 'Connected source value'),
								el('p', null, 'This value comes from a Nursery, Job, Event, Article or protected form rule. Its content cannot be accidentally forked into page-design JSON.')
							)
						)
					),
					presentationControls,
					renderElementActions(),
					liveElement.hardProtected ? null : el('div', { className: 'am-vb-inspector-section' },
						button('am-vb-button am-vb-button--primary am-vb-button--wide', homeDesignSaving ? 'Saving…' : 'Save presentation', requestNamedSave, { disabled: homeDesignSaving || !homeDesignDirty })
					)
				);
			}
			var isText = 'text' === liveElement.type || 'textarea' === liveElement.type;
			var isFrame = 'frame' === liveElement.type;
			var mediaValues = item[device] || {};
			var controls = [];

			if (isText) {
				controls.push(el(Field, {
					key: 'copy',
					label: 'Text',
					kind: 'textarea',
					value: item.value || liveElement.value || '',
					onChange: function (value) { updateSelectedElement('value', value); }
				}));
				controls.push(el(LinkField, {
					key: 'href',
					label: 'Click link (optional)',
					value: item.href || liveElement.href || '',
					placeholder: '/page or https://example.com',
					suggestions: availableLinks,
					fieldId: liveElement.key + '-link',
					onChange: function (value) { updateSelectedElement('href', value); }
				}));
				if (item.href || liveElement.href) {
					controls.push(button('am-vb-button am-vb-button--wide', 'Open linked page', function () { openLinkedPage(item.href || liveElement.href); }, { key: 'open-href' }));
				}
				controls.push(el(Field, {
					key: 'link-description',
					label: 'Link description (optional)',
					value: item.linkDescription || liveElement.linkDescription || '',
					placeholder: 'What this link opens',
					onChange: function (value) { updateSelectedElement('linkDescription', value); }
				}));
			} else if (isFrame) {
				var footerButtonTextKey = 'footer-view-more-button' === liveElement.key
					? 'footer-view-more'
					: ('footer-view-less-button' === liveElement.key ? 'footer-view-less' : '');
				if (footerButtonTextKey) {
					var footerButtonText = homeElementData(footerButtonTextKey);
					controls.push(el(Field, {
						key: 'button-text',
						label: 'Button text',
						kind: 'textarea',
						value: footerButtonText.value || ('footer-view-more' === footerButtonTextKey ? 'View more' : 'View less'),
						onChange: function (value) { updateHomeElementValue(footerButtonTextKey, 'value', value); }
					}));
				}
			} else {
				controls.push(el('div', { key: 'replace', className: 'am-vb-home-media-card' },
					liveElement.src && 'video' !== liveElement.type
						? el('img', { src: item.src || liveElement.src, alt: '', className: 'am-vb-home-media-card__preview' })
						: el('div', { className: 'am-vb-home-media-card__placeholder' }, 'video' === liveElement.type ? 'Home video' : 'Current site artwork'),
					button('am-vb-button am-vb-button--primary', 'video' === liveElement.type ? 'Replace video' : ('logo' === liveElement.type ? 'Upload / replace logo' : 'Replace photo'), function () {
						chooseHomeMedia(liveElement.key, 'video' === liveElement.type ? 'video' : 'image');
					})
				));
				if ('video' === liveElement.type) {
					controls.push(button('am-vb-button am-vb-button--wide', 'Replace fallback image', function () {
						chooseHomeMedia('hero-poster', 'image');
					}, { key: 'poster' }));
				}
				if ('video' !== liveElement.type) {
					controls.push(el(Field, {
						key: 'alt',
						label: 'Accessible description',
						value: item.alt || liveElement.alt || '',
						onChange: function (value) { updateSelectedElement('alt', value); }
					}));
				}
				if (Object.prototype.hasOwnProperty.call(item, 'href')) {
					controls.push(el(LinkField, {
						key: 'media-href', label: 'Click link', value: item.href || liveElement.href || '', placeholder: '/page or https://example.com',
						suggestions: availableLinks, fieldId: liveElement.key + '-media-link',
						onChange: function (value) { updateSelectedElement('href', value); }
					}));
					if (item.href || liveElement.href) {
						controls.push(button('am-vb-button am-vb-button--wide', 'Open linked page', function () { openLinkedPage(item.href || liveElement.href); }, { key: 'open-media-href' }));
					}
					controls.push(el(Field, {
						key: 'media-link-description', label: 'Link description', value: item.linkDescription || liveElement.linkDescription || '', placeholder: 'What this link opens',
						onChange: function (value) { updateSelectedElement('linkDescription', value); }
					}));
				}
				if ('logo' !== liveElement.type && 'video' !== liveElement.type) {
					controls.push(el(RangeField, {
						key: 'position-x', label: 'Photo focus left / right', value: item.positionX || 50, min: 0, max: 100, unit: '%',
						onChange: function (value) { updateHomeElementValue(liveElement.key, 'positionX', value); }
					}));
					controls.push(el(RangeField, {
						key: 'position-y', label: 'Photo focus up / down', value: item.positionY || 50, min: 0, max: 100, unit: '%',
						onChange: function (value) { updateHomeElementValue(liveElement.key, 'positionY', value); }
					}));
				}
			}

			return el(Fragment, null,
				el('div', { className: 'am-vb-inspector-section am-vb-home-element-controls' },
					el('div', { className: 'am-vb-live-controls__title' },
						el('h3', null, isText ? 'Edit text' : (isFrame ? 'Edit card' : 'Edit media')),
						el('span', null, titleCase(device))
					),
					renderResponsiveScopeControl(),
					controls,
					renderSchemaStyles({ record: item })
				),
				renderElementActions(),
				el('div', { className: 'am-vb-inspector-section' },
					el('div', { className: 'am-vb-live-save-row' },
						button('am-vb-button', 'Reset item', function () { resetHomeElement(liveElement.key); }),
						button('am-vb-button am-vb-button--primary', homeDesignSaving ? 'Saving…' : 'Save', requestNamedSave, { disabled: homeDesignSaving || !homeDesignDirty })
					)
				)
			);
		}

		/**
		 * Section panel.
		 *
		 * This used to be a chain of `if (region.id === 'home-hero')` blocks: six
		 * bespoke lists that gave Home controls nobody had written for the other
		 * 22 surfaces, which all fell through to the same three-slider fallback.
		 * The controls, their ranges and which band owns them are now declared in
		 * class-am-vb-style-schema.php, and this renders whatever it is handed -
		 * so every page gets the same panel, and adding a section control is one
		 * entry in PHP.
		 */
		function renderHomeDesignFields(region) {
			var section = homeSectionData(region.id);
			var genericExactRoute = 'home-poc' !== slug;
			var canHideRegion = !genericExactRoute || !/protected|bound|conditional|global|facts|binding/i.test(region.kind || '');

			return el(Fragment, null,
				el('div', { className: 'am-vb-inspector-section am-vb-live-controls' },
					el('div', { className: 'am-vb-live-controls__title' },
						el('h3', null, titleCase(device) + ' design'),
						el('span', null, 'Live preview')
					),
					renderResponsiveScopeControl(),
					canHideRegion
						? null
						: el('p', { className: 'am-vb-device-note', key: 'required-visible' }, 'This connected or functional section stays visible. Its layout can be moved and styled without hiding required data or controls.'),
					StylePanel && config.styleSchema
						? el(StylePanel, {
							schema: config.styleSchema,
							type: 'section',
							sectionId: region.id,
							record: section,
							device: device,
							allowStructural: canHideRegion,
							handlers: {
								onFlatChange: function (name, value) { updateHomeSectionValue(region.id, name, value); },
								onDeviceChange: function (name, value) { updateHomeDeviceValue(region.id, name, value); }
							}
						})
						: null
				),
				el('div', { className: 'am-vb-inspector-section' },
					el('div', { className: 'am-vb-live-save-row' },
						button('am-vb-button', 'Reset section', function () { resetHomeSection(region.id); }),
						button('am-vb-button am-vb-button--primary', homeDesignSaving ? 'Saving…' : 'Save', requestNamedSave, { disabled: homeDesignSaving || !homeDesignDirty })
					)
				)
			);
		}

		function renderCustomSectionFields(region) {
			var section = homeCustomSection(region.sectionId);
			if (!section) { return null; }
			var values = section[device] || {};
			var placementOptions = homeRegionList().filter(function (candidate) {
				return !candidate.custom && candidate.editable !== false;
			}).map(function (candidate) { return { value: candidate.id, label: 'After ' + candidate.label }; });
			var defaultPlacement = placementOptions.length ? placementOptions[placementOptions.length - 1].value : '';
			return el(Fragment, null,
				el('div', { className: 'am-vb-inspector-section am-vb-live-controls' },
					el('div', { className: 'am-vb-live-controls__title' }, el('h3', null, 'Custom section'), el('span', null, titleCase(device))),
					renderResponsiveScopeControl(),
					el(Field, { label: 'Section name', value: section.name || 'Custom section', onChange: function (value) { updateCustomSection(section.id, 'name', value); } }),
					el(Field, { label: 'Show this section', type: 'checkbox', value: false !== section.visible, onChange: function (value) { updateCustomSection(section.id, 'visible', value); } }),
					el(Field, { label: 'Background colour', type: 'color', value: section.backgroundColor || '#f7faf5', onChange: function (value) { updateCustomSection(section.id, 'backgroundColor', value); } }),
					el(Field, { label: 'Placement on page', kind: 'select', value: section.after || defaultPlacement, options: placementOptions, onChange: function (value) { updateCustomSection(section.id, 'after', value); } }),
					el(RangeField, { label: 'Section height', value: values.height || 420, min: 180, max: 'mobile' === device ? 1200 : 1000, step: 10, unit: ' px', onChange: function (value) { updateCustomSectionDevice(section.id, 'height', value); } })
				),
				el('div', { className: 'am-vb-inspector-section' },
					el('h3', null, 'Add a layer'),
					el('div', { className: 'am-vb-add-grid am-vb-add-grid--compact' }, ['text', 'image', 'logo', 'button', 'shape', 'tabs'].map(function (type) {
						return button('am-vb-add-card', el(Fragment, null, el('span', null, iconFor(type)), el('strong', null, titleCase(type))), function () { addCustomItem(type, section.id); }, { key: type });
					}))
				),
				el('div', { className: 'am-vb-inspector-section' },
					el('div', { className: 'am-vb-action-row am-vb-action-row--wrap' },
						button('am-vb-button', 'Duplicate section', function () { duplicateCustomSection(section.id); }),
						button('am-vb-button am-vb-button--danger', 'Delete section', function () { deleteCustomSection(section.id); })
					),
					button('am-vb-button am-vb-button--primary am-vb-button--wide', homeDesignSaving ? 'Saving…' : 'Save', requestNamedSave, { disabled: homeDesignSaving || !homeDesignDirty })
				)
			);
		}

		function renderLiveInspector() {
			if ('view' === canvasMode) {
				return el('aside', { className: 'am-vb-inspector am-vb-inspector--live am-vb-inspector--view' },
					el('div', { className: 'am-vb-inspector-header' },
						el('div', null,
							el('strong', null, 'Website view'),
							el('span', null, titleCase(device) + ' preview')
						)
					),
					el('div', { className: 'am-vb-inspector-section' },
						el('div', { className: 'am-vb-view-card' },
							el('span', { className: 'am-vb-view-card__icon', 'aria-hidden': 'true' }, '\u2197'),
							el('div', null,
								el('strong', null, 'Browse the real site'),
								el('p', null, 'Menus, links, accordions and page navigation work normally. Nothing in the page can be selected, moved or rewritten while View is active.')
							)
						)
					),
					el('div', { className: 'am-vb-inspector-section' },
						button('am-vb-button am-vb-button--primary am-vb-button--wide', 'Switch to Edit', function () { switchCanvasMode('edit'); })
					)
				);
			}
			var region = liveRegionData(liveRegion);
			var editUrl = region.editUrlKey ? config[region.editUrlKey] : '';
			var editablePageRegion = !!(homeDesign && homeDesign.sections && homeDesign.sections[region.id]);
			return el('aside', { className: 'am-vb-inspector am-vb-inspector--live' },
				el('div', { className: 'am-vb-inspector-header' },
					el('div', null,
						el('strong', null, liveElement ? liveElement.label : region.label),
						el('span', null, liveElement ? region.label + ' · ' + titleCase(device) : region.kind)
					)
				),
				liveElement
					? renderHomeElementFields()
					: region.custom
						? renderCustomSectionFields(region)
					: editablePageRegion
						? renderHomeDesignFields(region)
					: el('div', { className: 'am-vb-inspector-section' },
						el('div', { className: 'am-vb-parity-card' },
							el('span', { className: 'am-vb-parity-card__check', 'aria-hidden': 'true' }, '✓'),
							el('div', null,
								el('strong', null, 'global-header' === region.id ? 'Click the logo or menu text' : 'Connected site component'),
								el('p', null, 'global-header' === region.id
									? 'Select the logo to upload a replacement, resize it or drag it into position. Select menu text to edit it.'
									: 'Use the connected settings button for this site-wide area.')
							)
						)
					),
				el('div', { className: 'am-vb-inspector-section am-vb-inspector-section--unrelated' },
					el('h3', null, 'Content'),
					el('p', { className: 'am-vb-live-detail' }, region.detail),
					liveElement
						? el('p', { className: 'am-vb-device-note' }, 'The selected item is editable directly above.')
						: editUrl
						? el('a', {
							className: 'am-vb-button am-vb-button--primary am-vb-live-edit-link',
							href: editUrl
						}, 'Edit connected content')
						: editablePageRegion || region.custom
							? el('p', { className: 'am-vb-device-note' }, 'Click text, a photo or a logo in the centre canvas to edit it. Click empty space for layout controls.')
							: el('p', { className: 'am-vb-device-note' }, 'This global component keeps the approved site structure.')
				),
				el('div', { className: 'am-vb-inspector-section am-vb-inspector-section--unrelated' },
					el('h3', null, 'Localhost behavior'),
					el('p', { className: 'am-vb-live-detail' }, liveElement || editablePageRegion || region.custom
						? 'Only the Localhost site is updated. The production website remains untouched.'
						: 'Use the connected content screen for safe text, media and link changes.')
				)
			);
		}

		function renderInspector() {
			if (liveParity) {
				return renderLiveInspector();
			}
			var content = selection.elementIds.length > 1
				? renderMultiInspector()
				: selected.element
					? renderElementInspector(selected.element)
					: selected.section
						? renderSectionInspector(selected.section)
						: el(Fragment, null,
							el('div', { className: 'am-vb-inspector-header' },
								el('div', null, el('strong', null, 'Page settings'), el('span', null, 'Select a section or layer to edit it'))
							),
							el('div', { className: 'am-vb-inspector-section' },
								el(Field, {
									label: 'Page title',
									value: documentState.title,
									disabled: readOnly,
									onChange: function (value) {
										commit(function (next) { next.title = value; }, 'Page title updated');
									}
								})
							)
						);
			return el('aside', { className: 'am-vb-inspector' }, content, renderHistory());
		}

		if (loading) {
			return el('div', { className: 'am-vb-loading-screen' }, el('span', { className: 'spinner is-active' }), el('p', null, 'Opening visual page…'));
		}
		if (error || !documentState) {
			return el('div', { className: 'am-vb-error-screen' },
				el('strong', null, 'Visual builder could not open'),
				el('p', null, error || 'The document is unavailable.'),
				button('am-vb-button', 'Reload', function () { window.location.reload(); })
			);
		}

		return el(Fragment, null,
			el('div', { className: 'am-vb-app' + (savedSessionsOpen ? ' am-vb-app--sessions' : '') + (!savedSessionsOpen && liveParity && 'templates' === leftTab ? ' am-vb-app--library' : '') + (!savedSessionsOpen && liveParity && 'view' === canvasMode ? ' am-vb-app--view' : '') },
				renderTopbar(),
				savedSessionsOpen
					? renderSavedSessionsPage()
					: el(Fragment, null,
						liveParity && 'view' === canvasMode ? null : renderLeftbar(),
						renderCanvas(),
						liveParity && 'view' === canvasMode ? null : renderInspector()
					)
			),
			renderSaveSessionDialog(),
			renderRestoreSessionDialog(),
			toast ? el('div', { className: 'am-vb-toast', role: 'status' }, toast) : null
		);
	}

	var root = window.document.getElementById('am-visual-builder-root');
	if (root) {
		if (wp.element.createRoot) {
			wp.element.createRoot(root).render(el(App));
		} else {
			wp.element.render(el(App), root);
		}
	}
})(window.wp, window.amVisualBuilder);
