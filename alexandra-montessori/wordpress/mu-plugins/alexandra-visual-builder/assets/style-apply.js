/**
 * Shared style applier.
 *
 * The editor canvas and the public front end both call this, so a property
 * cannot render in one place and silently vanish in the other. Property
 * definitions come from class-am-vb-style-schema.php - nothing is duplicated
 * here, which is what makes the same toolkit reusable on pages other than Home.
 */
(function (window, document) {
	'use strict';

	var DEVICES = ['desktop', 'tablet', 'mobile'];

	/**
	 * @returns {object|null} Schema payload shipped by PHP.
	 */
	function schema() {
		if (window.amVisualBuilder && window.amVisualBuilder.styleSchema) {
			return window.amVisualBuilder.styleSchema;
		}
		if (window.amVBStyleSchema) {
			return window.amVBStyleSchema;
		}

		return null;
	}

	/**
	 * Base toolkit plus whichever packs the element type opts into.
	 *
	 * @param {string} type Element type.
	 * @returns {object} Map of property name to definition.
	 */
	function propsForType(type) {
		var payload = schema();
		if (!payload) {
			return {};
		}
		var props = {};
		var name;
		for (name in payload.base) {
			if (Object.prototype.hasOwnProperty.call(payload.base, name)) {
				props[name] = payload.base[name];
			}
		}
		var packs = (payload.packsFor && payload.packsFor[type]) || [];
		packs.forEach(function (pack) {
			var group = payload.packs && payload.packs[pack];
			if (!group) {
				return;
			}
			var key;
			for (key in group) {
				if (Object.prototype.hasOwnProperty.call(group, key)) {
					props[key] = group[key];
				}
			}
		});

		return props;
	}

	/**
	 * Media elements keep the historical --am-media-* namespace so previously
	 * saved designs and the existing index.css rules keep working.
	 *
	 * @param {string} type Element type.
	 * @returns {string} 'media' or 'element'.
	 */
	function scopeForType(type) {
		return ['image', 'logo', 'video'].indexOf(type) !== -1 ? 'media' : 'element';
	}

	/**
	 * @param {object} prop  Property definition.
	 * @param {string} scope Variable namespace.
	 * @returns {string} CSS custom property name, or '' when the property is
	 *                   applied through a data attribute instead.
	 */
	function variableFor(prop, scope) {
		if (!prop.var) {
			return '';
		}
		if (typeof prop.var === 'string') {
			return prop.var;
		}

		return prop.var[scope] || prop.var.element || '';
	}

	/**
	 * Turn a stored number into a CSS value. A divisor converts a whole-number
	 * slider into its CSS ratio (scale 140 becomes 1.4); percent units are
	 * dropped because those properties are unitless in CSS.
	 *
	 * @param {object} prop  Property definition.
	 * @param {*}      value Stored value.
	 * @returns {string} CSS value.
	 */
	function cssValue(prop, value) {
		if ('select' === prop.control || 'color' === prop.control) {
			return String(value);
		}
		var numeric = Number(value);
		if (isNaN(numeric)) {
			return '';
		}
		if (prop.divisor) {
			numeric = numeric / Number(prop.divisor);
		}
		var unit = prop.unit || '';
		if ('%' === unit || '' === unit) {
			return String(numeric);
		}

		return String(numeric) + unit;
	}

	/**
	 * @param {object} prop  Property definition.
	 * @param {*}      value Stored value.
	 * @returns {boolean} Whether the value is worth writing to the DOM.
	 */
	function isMeaningful(prop, value) {
		if (undefined === value || null === value || '' === value) {
			return false;
		}
		if ('toggle' === prop.control) {
			return true === value || 'true' === value;
		}

		return String(value) !== String(prop.default);
	}

	/**
	 * Write one element's stored design onto a DOM node.
	 *
	 * @param {HTMLElement} node   Target node.
	 * @param {string}      type   Element type.
	 * @param {object}      record Stored element record (flat keys plus a
	 *                             per-device object for each breakpoint).
	 * @returns {void}
	 */
	function applyToNode(node, type, record) {
		if (!node || !record) {
			return;
		}
		var props = propsForType(type);
		var scope = scopeForType(type);
		var touched = false;
		var name;

		for (name in props) {
			if (!Object.prototype.hasOwnProperty.call(props, name)) {
				continue;
			}
			var prop = props[name];
			var variable = variableFor(prop, scope);

			// Legacy transform variables stay owned by their original code path.
			if (variable && ('--am-element-' === variable.slice(0, 13) || '--am-media-' === variable.slice(0, 11))) {
				continue;
			}

			if (prop.device) {
				DEVICES.forEach(function (device) {
					var bucket = record[device] || {};
					var value = bucket[name];
					var suffix = '-' + device;
					if (!isMeaningful(prop, value)) {
						if (variable) {
							node.style.removeProperty(variable + suffix);
						} else if (prop.attr) {
							node.removeAttribute(prop.attr + suffix);
						}

						return;
					}
					touched = true;
					if (variable) {
						node.style.setProperty(variable + suffix, cssValue(prop, value));
					} else if (prop.attr) {
						node.setAttribute(prop.attr + suffix, 'toggle' === prop.control ? 'true' : String(value));
					}
				});

				continue;
			}

			var flat = record[name];
			if (!isMeaningful(prop, flat)) {
				if (variable) {
					node.style.removeProperty(variable);
				} else if (prop.attr) {
					node.removeAttribute(prop.attr);
				}

				continue;
			}
			touched = true;
			if (variable) {
				node.style.setProperty(variable, cssValue(prop, flat));
			} else if (prop.attr) {
				node.setAttribute(prop.attr, 'toggle' === prop.control ? 'true' : String(flat));
			}
		}

		if (touched) {
			node.setAttribute('data-am-vb-styled', 'true');
			ensureLayoutBox(node);
		} else {
			node.removeAttribute('data-am-vb-styled');
		}

		// While editing, an element waiting to animate in must not sit invisible
		// on the canvas - the client would think they had deleted it.
		var animate = node.getAttribute('data-am-vb-animate');
		if (animate && 'none' !== animate) {
			var root = node.ownerDocument && node.ownerDocument.documentElement;
			if (root && 'edit' === root.getAttribute('data-am-vb-canvas-mode')) {
				node.setAttribute('data-am-vb-inview', 'true');
			} else if (!node.hasAttribute('data-am-vb-inview')) {
				node.setAttribute('data-am-vb-inview', 'false');
			}
		}
	}

	/**
	 * CSS silently ignores width, height, padding, border-radius and rotation on
	 * a non-replaced inline box. Links, spans and inline headings are exactly
	 * that, which made those controls look broken on some elements while working
	 * on others. Promoting only genuinely inline nodes to inline-block keeps the
	 * surrounding layout intact while letting the controls take effect.
	 *
	 * @param {HTMLElement} node Target node.
	 * @returns {void}
	 */
	function ensureLayoutBox(node) {
		var view = node.ownerDocument && node.ownerDocument.defaultView;
		if (!view || !view.getComputedStyle) {
			return;
		}
		if ('inline' === view.getComputedStyle(node).display) {
			node.style.display = 'inline-block';
		}
	}

	/**
	 * Reveal scroll-animated nodes once they enter the viewport. Nodes start
	 * hidden only when an animation is actually configured, so a browser
	 * without IntersectionObserver simply shows everything.
	 *
	 * @param {Document} scope Document to observe.
	 * @returns {void}
	 */
	function observeAnimations(scope) {
		var doc = scope || document;
		if (!window.IntersectionObserver) {
			return;
		}
		var nodes = doc.querySelectorAll('[data-am-vb-animate]:not([data-am-vb-animate="none"])');
		if (!nodes.length) {
			return;
		}
		var observer = new window.IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.setAttribute('data-am-vb-inview', 'true');
					observer.unobserve(entry.target);
				}
			});
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });

		Array.prototype.forEach.call(nodes, function (node) {
			if (!node.hasAttribute('data-am-vb-inview')) {
				node.setAttribute('data-am-vb-inview', 'false');
			}
			observer.observe(node);
		});
	}

	/**
	 * Write a whole saved design onto the page it belongs to.
	 *
	 * The editor canvas has always done this through editor.js. The public page
	 * never did: the React helper `homeVisual.js` emits only the three legacy
	 * transform variables, so every other saved property - rotate, width,
	 * colour, radius, shadow, hover, animation - reached the browser as data and
	 * was never rendered. Reading the same schema here fixes that for every page
	 * at once without restating a single property in JavaScript.
	 *
	 * @param {object}   model Saved design ({ elements: { key: record } }).
	 * @param {Document} root  Document to write into.
	 * @returns {number} How many nodes were touched.
	 */
	function applyDesign(model, root) {
		var doc = root || document;
		if (!model) {
			return 0;
		}
		var count = 0;
		Object.keys(model.elements || {}).forEach(function (key) {
			var record = model.elements[key];
			if (!record) {
				return;
			}
			var nodes = doc.querySelectorAll('[data-am-vb-editable="' + key + '"]');
			Array.prototype.forEach.call(nodes, function (node) {
				applyToNode(node, node.getAttribute('data-am-vb-edit-type') || record.type || 'text', record);
				count += 1;
			});
		});
		// Bands resolve through the same applier as elements, under the type
		// `section`. Their layout values keep their own historical route (the
		// React page writes those); this covers the properties the schema owns.
		Object.keys(model.sections || {}).forEach(function (id) {
			var record = model.sections[id];
			if (!record) {
				return;
			}
			var nodes = doc.querySelectorAll('[data-am-vb-region="' + id + '"]');
			Array.prototype.forEach.call(nodes, function (node) {
				applyToNode(node, 'section', record);
				count += 1;
			});
		});

		return count;
	}

	window.AMVBStyleApply = {
		DEVICES: DEVICES,
		applyDesign: applyDesign,
		applyToNode: applyToNode,
		cssValue: cssValue,
		observeAnimations: observeAnimations,
		propsForType: propsForType,
		schema: schema,
		scopeForType: scopeForType
	};

	/**
	 * Inside the builder canvas the editor owns every node, and it applies the
	 * unsaved working model. Running the saved model here as well would fight it
	 * and make in-progress edits flicker back to their last saved value.
	 *
	 * @returns {boolean} Whether this document is the builder canvas.
	 */
	function isBuilderCanvas() {
		return /[?&]am_visual_canvas=/.test(window.location.search);
	}

	// The React app paints after DOMContentLoaded, so a single early pass would
	// find nothing. Run again on load and once more shortly after; the observer
	// ignores nodes it has already claimed.
	function start() {
		if (!isBuilderCanvas()) {
			// Home localises its design as `amHomeDesign`. Other routes are handled
			// by site-runtime.js, which annotates its nodes first and then calls
			// straight into applyToNode.
			applyDesign(window.amHomeDesign, document);
		}
		observeAnimations(document);
	}
	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
	window.addEventListener('load', function () {
		start();
		window.setTimeout(start, 600);
	});
}(window, document));
