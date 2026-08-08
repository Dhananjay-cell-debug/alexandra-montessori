/**
 * Schema-driven inspector panel.
 *
 * The old panel hardcoded a different list of controls per element type, which
 * is why Home felt richer than every other page. This renders whatever
 * class-am-vb-style-schema.php declares, so a new property becomes editable
 * everywhere at once and other pages inherit the same toolkit for free.
 *
 * Restraint is the point. At most three tabs; one group open per tab; every
 * other group collapsed behind a summary of its current value. A client should
 * see a calm panel and choose to go deeper, never face a wall of sliders.
 */
(function (window) {
	'use strict';

	var TAB_LABELS = { content: 'Content', style: 'Style', layout: 'Layout' };
	var TAB_ORDER = ['content', 'style', 'layout'];

	/**
	 * @param {object} deps Rendering primitives owned by editor.js.
	 * @returns {object} Public inspector API.
	 */
	function create(deps) {
		var el = deps.el;
		var Fragment = deps.Fragment;
		var useState = deps.useState;
		var Field = deps.Field;
		var RangeField = deps.RangeField;
		var button = deps.button;
		var titleCase = deps.titleCase;

		/**
		 * Base toolkit plus the packs this element type opts into.
		 *
		 * @param {object} schema Schema payload.
		 * @param {string} type   Element type.
		 * @returns {object} Property definitions keyed by name.
		 */
		function propsForType(schema, type, sectionId) {
			var props = {};
			var name;
			for (name in schema.base) {
				if (Object.prototype.hasOwnProperty.call(schema.base, name)) {
					props[name] = schema.base[name];
				}
			}
			((schema.packsFor && schema.packsFor[type]) || []).forEach(function (pack) {
				var group = schema.packs && schema.packs[pack];
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

			// A control that cannot act on this element is not shown. An inert
			// slider costs more trust than the missing control ever would.
			var applicable = {};
			var candidate;
			for (candidate in props) {
				if (!Object.prototype.hasOwnProperty.call(props, candidate)) {
					continue;
				}
				var prop = props[candidate];
				if (prop.types && prop.types.length && prop.types.indexOf(type) === -1) {
					continue;
				}
				if (prop.notTypes && prop.notTypes.indexOf(type) !== -1) {
					continue;
				}
				// The same allow/block idea, one level up: a few section properties
				// drive CSS variables that exist in one band's markup only. This is
				// still a data lookup, not a branch on which page we are editing.
				if (prop.sections && prop.sections.length && prop.sections.indexOf(sectionId) === -1) {
					continue;
				}
				if (prop.notSections && prop.notSections.indexOf(sectionId) !== -1) {
					continue;
				}
				applicable[candidate] = prop;
			}

			return applicable;
		}

		/**
		 * @param {object} prop   Property definition.
		 * @param {object} record Element record.
		 * @param {string} device Active breakpoint.
		 * @returns {*} Current value, falling back to the schema default.
		 */
		function valueOf(prop, name, record, device) {
			if (prop.device) {
				var bucket = (record && record[device]) || {};

				return undefined === bucket[name] ? prop.default : bucket[name];
			}

			return undefined === record[name] || '' === record[name] ? prop.default : record[name];
		}

		/**
		 * A short human summary shown on a collapsed group, so nothing the
		 * client changed is ever hidden without a trace.
		 *
		 * @returns {string} Summary text.
		 */
		function summarise(entries, record, device) {
			var changed = entries.filter(function (entry) {
				var value = valueOf(entry.prop, entry.name, record, device);

				return String(value) !== String(entry.prop.default);
			});
			if (!changed.length) {
				return 'default';
			}
			if (1 === changed.length) {
				var only = changed[0];
				var value = valueOf(only.prop, only.name, record, device);
				if ('select' === only.prop.control) {
					return String(value);
				}
				// A toggle summary must state the state, not repeat the label. "Show
				// this section" on a collapsed group read as though the section were
				// visible at the exact moment it had been switched off.
				if ('toggle' === only.prop.control) {
					return value ? 'on' : 'off';
				}
				if ('color' === only.prop.control) {
					return String(value);
				}

				return String(value) + (only.prop.unit || '');
			}

			return changed.length + ' changed';
		}

		/**
		 * Render one property using the control its definition asks for.
		 *
		 * @returns {object} React element.
		 */
		function renderControl(name, prop, record, device, handlers, readOnly) {
			var value = valueOf(prop, name, record, device);
			var onChange = function (next) {
				if (prop.device) {
					handlers.onDeviceChange(name, next);

					return;
				}
				handlers.onFlatChange(name, next);
			};

			if ('range' === prop.control) {
				return el(RangeField, {
					key: name,
					label: prop.label,
					value: value,
					min: prop.min,
					max: prop.max,
					step: prop.step || 1,
					unit: prop.unit || '',
					zeroLabel: prop.zeroLabel || '',
					disabled: readOnly,
					onChange: onChange
				});
			}
			if ('select' === prop.control) {
				return el(Field, {
					key: name,
					kind: 'select',
					label: prop.label,
					value: value,
					disabled: readOnly,
					options: (prop.options || []).map(function (option) {
						return { value: option, label: titleCase(option) };
					}),
					onChange: onChange
				});
			}
			if ('color' === prop.control) {
				return el(Field, {
					key: name,
					type: 'color',
					label: prop.label,
					value: value || '#ffffff',
					disabled: readOnly,
					onChange: onChange
				});
			}

			return el(Field, {
				key: name,
				type: 'checkbox',
				label: prop.label,
				value: !!value,
				disabled: readOnly,
				onChange: onChange
			});
		}

		/**
		 * The panel itself.
		 *
		 * @param {object} props Panel props.
		 * @returns {object|null} React element.
		 */
		function StylePanel(props) {
			var schema = props.schema;
			var _tab = useState('');
			var activeTab = _tab[0];
			var setActiveTab = _tab[1];
			var _open = useState({});
			var openGroups = _open[0];
			var setOpenGroups = _open[1];

			if (!schema || !schema.base) {
				return null;
			}

			var definitions = propsForType(schema, props.type, props.sectionId);
			var groups = schema.groups || {};
			var record = props.record || {};
			var device = props.device;
			var readOnly = !!props.readOnly;
			var skip = props.skip || {};

			// Bucket properties into their declared groups, dropping anything the
			// caller has already rendered itself or that a guard forbids.
			var byGroup = {};
			var name;
			for (name in definitions) {
				if (!Object.prototype.hasOwnProperty.call(definitions, name)) {
					continue;
				}
				if (skip[name]) {
					continue;
				}
				var prop = definitions[name];
				if ('structural' === prop.guard && false === props.allowStructural) {
					continue;
				}
				var groupName = prop.group || 'style';
				if (!byGroup[groupName]) {
					byGroup[groupName] = [];
				}
				byGroup[groupName].push({ name: name, prop: prop });
			}

			// Only offer a tab that actually has something in it.
			var tabs = [];
			TAB_ORDER.forEach(function (tab) {
				var has = Object.keys(byGroup).some(function (groupName) {
					return (groups[groupName] || {}).tab === tab && byGroup[groupName].length;
				});
				if (has) {
					tabs.push(tab);
				}
			});
			if (!tabs.length) {
				return null;
			}
			var current = tabs.indexOf(activeTab) !== -1 ? activeTab : tabs[0];

			var visibleGroups = Object.keys(byGroup).filter(function (groupName) {
				return (groups[groupName] || {}).tab === current;
			}).sort(function (a, b) {
				return Object.keys(groups).indexOf(a) - Object.keys(groups).indexOf(b);
			});

			return el('div', { className: 'am-vb-style-panel' },
				tabs.length > 1
					? el('div', { className: 'am-vb-style-tabs', role: 'tablist' }, tabs.map(function (tab) {
						return button(
							'am-vb-style-tabs__tab' + (tab === current ? ' is-active' : ''),
							TAB_LABELS[tab] || titleCase(tab),
							function () { setActiveTab(tab); },
							{ key: tab, role: 'tab', 'aria-selected': tab === current ? 'true' : 'false' }
						);
					}))
					: null,
				visibleGroups.map(function (groupName) {
					var group = groups[groupName] || {};
					var entries = byGroup[groupName];
					var isOpen = Object.prototype.hasOwnProperty.call(openGroups, groupName)
						? openGroups[groupName]
						: !!group.open;

					return el('section', { className: 'am-vb-style-group' + (isOpen ? ' is-open' : ''), key: groupName },
						button('am-vb-style-group__toggle', el(Fragment, null,
							el('span', { className: 'am-vb-style-group__icon', 'aria-hidden': 'true' }, group.icon || '•'),
							el('span', { className: 'am-vb-style-group__label' }, group.label || titleCase(groupName)),
							el('span', { className: 'am-vb-style-group__summary' }, isOpen ? '' : summarise(entries, record, device)),
							el('span', { className: 'am-vb-style-group__chevron', 'aria-hidden': 'true' }, isOpen ? '▾' : '▸')
						), function () {
							var next = {};
							var key;
							for (key in openGroups) {
								if (Object.prototype.hasOwnProperty.call(openGroups, key)) {
									next[key] = openGroups[key];
								}
							}
							next[groupName] = !isOpen;
							setOpenGroups(next);
						}, { 'aria-expanded': isOpen ? 'true' : 'false' }),
						isOpen
							? el('div', { className: 'am-vb-style-group__body' },
								entries.map(function (entry) {
									return renderControl(entry.name, entry.prop, record, device, props.handlers, readOnly);
								}),
								readOnly ? null : button('am-vb-style-group__reset', 'Reset ' + String(group.label || groupName).toLowerCase(), function () {
									entries.forEach(function (entry) {
										if (entry.prop.device) {
											props.handlers.onDeviceChange(entry.name, entry.prop.default);

											return;
										}
										props.handlers.onFlatChange(entry.name, entry.prop.default);
									});
								})
							)
							: null
					);
				})
			);
		}

		return { StylePanel: StylePanel, propsForType: propsForType };
	}

	window.AMVBInspector = { create: create };
}(window));
