(function (config) {
	'use strict';

	if (!config || !config.pageSlug || !config.page || !config.design) {
		return;
	}

	var scanScheduled = false;
	var applying = false;
	var page = config.page;
	var design = config.design;
	var pageRegions = (page.regions || []).filter(function (region) {
		return region.editable !== false && 0 !== String(region.id || '').indexOf('global-') && 'social-sidebar' !== region.id && 'cookie-control' !== region.id;
	});

	function editorOwnsCanvasDesign() {
		return 'edit' === document.documentElement.getAttribute('data-am-vb-canvas-mode');
	}

	function announceRuntimeReady() {
		var event;
		try {
			event = new CustomEvent('am-vb-runtime-ready', {
				detail: { pageSlug: config.pageSlug }
			});
		} catch (ignore) {
			event = document.createEvent('CustomEvent');
			event.initCustomEvent('am-vb-runtime-ready', false, false, { pageSlug: config.pageSlug });
		}
		document.dispatchEvent(event);
	}

	function slug(value) {
		return String(value || '')
			.toLowerCase()
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-+|-+$/g, '')
			.slice(0, 72);
	}

	function text(value) {
		return String(value || '').replace(/\s+/g, ' ').trim();
	}

	function tokens(value) {
		return slug(value).split('-').filter(function (token) {
			return token.length > 2 && ['the', 'and', 'with', 'page', 'section'].indexOf(token) === -1;
		});
	}

	function candidateName(node) {
		var heading = node.querySelector && node.querySelector(':scope > h1, :scope > h2, :scope > h3, :scope > legend, h1, h2, h3, legend');
		return text(heading ? heading.textContent : (node.getAttribute && (node.getAttribute('aria-label') || node.getAttribute('id'))) || '');
	}

	function score(region, node) {
		var expected = tokens((region.label || '') + ' ' + (region.detail || ''));
		var actual = tokens(candidateName(node));
		return expected.reduce(function (total, token) {
			return total + (actual.indexOf(token) !== -1 ? 3 : 0) + (text(node.textContent).toLowerCase().indexOf(token) !== -1 ? 1 : 0);
		}, 0);
	}

	function regionCandidates(main) {
		var top = Array.prototype.slice.call(main.children).filter(function (node) {
			return 'SECTION' === node.tagName;
		});
		var nested = [];
		Array.prototype.forEach.call(main.querySelectorAll('form,fieldset,article,[role="dialog"],h2,h3,legend'), function (node) {
			var candidate = /^(H2|H3|LEGEND)$/.test(node.tagName)
				? node.closest('article,fieldset,form,section,div')
				: node;
			if (!candidate || candidate === main || top.indexOf(candidate) !== -1 || nested.indexOf(candidate) !== -1) {
				return;
			}
			if (candidate.closest('[data-am-vb-region]')) {
				return;
			}
			nested.push(candidate);
		});
		return { top: top, nested: nested };
	}

	function assignRegions(main) {
		var candidates = regionCandidates(main);
		var available = pageRegions.slice();

		// The first public section is consistently the page header when the page
		// uses PageHeader. Nursery/curriculum pages simply fall through to the
		// same ordered workstream mapping.
		candidates.top.forEach(function (node, index) {
			if (node.hasAttribute('data-am-vb-region') || !available.length) {
				return;
			}
			var bestIndex = 0;
			var bestScore = -1;
			available.forEach(function (region, regionIndex) {
				var candidateScore = score(region, node);
				if (candidateScore > bestScore) {
					bestScore = candidateScore;
					bestIndex = regionIndex;
				}
			});
			if (0 === index && /header/i.test(available[0].label || '')) {
				bestIndex = 0;
			}
			var region = available.splice(bestIndex, 1)[0];
			annotateRegion(node, region);
		});

		candidates.nested.forEach(function (node) {
			if (node.hasAttribute('data-am-vb-region') || !available.length) {
				return;
			}
			var bestIndex = -1;
			var bestScore = 0;
			available.forEach(function (region, regionIndex) {
				var candidateScore = score(region, node);
				if (candidateScore > bestScore) {
					bestScore = candidateScore;
					bestIndex = regionIndex;
				}
			});
			if (-1 === bestIndex) {
				return;
			}
			annotateRegion(node, available.splice(bestIndex, 1)[0]);
		});
	}

	function annotateRegion(node, region) {
		node.setAttribute('data-am-vb-region', region.id);
		node.setAttribute('data-am-vb-region-label', region.label || region.id);
		node.setAttribute('data-am-vb-region-kind', region.kind || 'Page section');
		if (/bound|protected/i.test(region.kind || '')) {
			node.setAttribute('data-am-vb-bound-region', 'true');
		}
	}

	function mediaFrame(image) {
		if ('IMG' !== image.tagName) {
			return image;
		}
		var parent = image.parentElement;
		if (parent && ('PICTURE' === parent.tagName || parent.classList.contains('isolate') || parent.classList.contains('overflow-hidden'))) {
			return parent;
		}
		return image;
	}

	function protectedNode(node, region) {
		var kind = String(region.kind || '');
		if (node.closest('[data-am-vb-bound-value="true"]')) {
			return true;
		}
		if (node.matches('input,select,textarea,option')) {
			return true;
		}
		if (node.closest('[data-am-vb-system],script,style,noscript')) {
			return true;
		}
		if (node.matches('dt,dd,time,address') || /^(tel:|mailto:)/i.test(node.getAttribute('href') || '')) {
			return true;
		}
		// Collection/detail content belongs to Jobs, Events, Articles or Nursery
		// records. It may be positioned and styled here, but copying that content
		// into page-design JSON would create a second, stale source of truth.
		if (/bound/i.test(kind) || /protected (nursery|facts|record|content)/i.test(kind) || /nursery (collection|binding|media)/i.test(kind) || /global binding/i.test(kind)) {
			return true;
		}
		if (/mixed bindings/i.test(kind)) {
			return node.matches('a,button,img,video');
		}
		// Functional controls keep their behaviour and submitted values protected;
		// their explanatory headings and labels remain visually editable.
		if (/protected (form|calculator|modal|action)/i.test(kind)) {
			return node.matches('button,a,[role="button"],output,[data-result],[aria-live]');
		}
		return 'template' === page.type && /protected/i.test(kind) && node.matches('h1,h2,h3,p,a,button,img,video');
	}

	function elementLabel(node, type, index) {
		var aria = node.getAttribute('aria-label') || node.getAttribute('title') || '';
		var value = text(aria || node.textContent || '');
		if ('image' === type || 'logo' === type) {
			var image = 'IMG' === node.tagName ? node : node.querySelector('img');
			value = image ? (image.alt || image.getAttribute('title') || '') : value;
		}
		return (value ? value.slice(0, 62) : (type.charAt(0).toUpperCase() + type.slice(1) + ' ' + (index + 1)));
	}

	function regionKeyPrefix(region, instanceIndex) {
		// Keep the first occurrence on the original key forever. Conditional routes
		// can add a second copy later; changing the first copy to "instance-1" at
		// that point would orphan the editor's existing value and transform state.
		var instance = instanceIndex > 0 ? '-instance-' + (instanceIndex + 1) : '';
		return config.pageSlug + '-' + region.id + instance;
	}

	function annotateElements(regionNode, region, instanceIndex, instanceCount) {
		var counts = {};
		// A later async/conditional render must allocate after the keys that already
		// exist. Resetting counters to zero on every scan created duplicate text-1 /
		// image-1 keys and made one drag move multiple unrelated elements.
		Array.prototype.forEach.call(regionNode.querySelectorAll('[data-am-vb-editable]'), function (existing) {
			if (existing.closest('[data-am-vb-region]') !== regionNode) {
				return;
			}
			var existingType = existing.getAttribute('data-am-vb-edit-type') || '';
			if (!existingType || 'frame' === existingType) {
				return;
			}
			var match = String(existing.getAttribute('data-am-vb-editable') || '').match(new RegExp('-' + existingType + '-(\\d+)$'));
			if (match) {
				counts[existingType] = Math.max(counts[existingType] || 0, Number(match[1]) || 0);
			}
		});
		var selector = 'h1,h2,h3,h4,h5,h6,p,li,dt,dd,label,legend,blockquote,figcaption,span,a,button,img,video,svg,input,select,textarea';
		var nodes = Array.prototype.slice.call(regionNode.querySelectorAll(selector));
		if (regionNode.matches(selector)) {
			nodes.unshift(regionNode);
		}
		nodes.forEach(function (rawNode) {
			if (rawNode.closest('[data-am-vb-region]') !== regionNode) {
				return;
			}
			if (rawNode.closest('[data-am-vb-custom-element]') || rawNode.closest('[data-am-vb-editable]')) {
				return;
			}
			// Structured links, buttons and labels must keep their icons and form
			// controls. Their child text slots are annotated independently, while
			// the wrapper is added below as a presentation-only frame.
			if (/^(LABEL|A|BUTTON)$/.test(rawNode.tagName) && rawNode.children.length) {
				return;
			}
			if (/^(P|LI)$/.test(rawNode.tagName) && rawNode.querySelector('h1,h2,h3,h4,p,li,label,a,button')) {
				return;
			}
			if ('SPAN' === rawNode.tagName && (!text(rawNode.textContent) || rawNode.children.length)) {
				return;
			}
			var type = /^(IMG|VIDEO)$/.test(rawNode.tagName)
				? ('VIDEO' === rawNode.tagName ? 'video' : 'image')
				: ('SVG' === rawNode.tagName
					? 'logo'
					: (rawNode.matches('input,select,textarea')
						? 'frame'
						: (rawNode.matches('a,button') ? 'button' : (/^(P|LI|DD|BLOCKQUOTE)$/.test(rawNode.tagName) ? 'textarea' : 'text'))));
			var node = 'image' === type ? mediaFrame(rawNode) : rawNode;
			if (node.hasAttribute('data-am-vb-editable')) {
				return;
			}
			counts[type] = counts[type] || 0;
			var index = ++counts[type];
			var key = slug(regionKeyPrefix(region, instanceIndex) + '-' + type + '-' + index);
			var image = 'image' === type ? ('IMG' === rawNode.tagName ? rawNode : rawNode.querySelector('img')) : null;
			node.setAttribute('data-am-vb-editable', key);
			node.setAttribute('data-am-vb-edit-type', type);
			node.setAttribute('data-am-vb-label', elementLabel(node, type, index));
			node.setAttribute('data-am-vb-fallback', /^(text|textarea|button)$/.test(type) ? text(rawNode.textContent) : '');
			node.setAttribute('data-am-vb-free-move', 'true');
			if (image) {
				node.classList.add('am-vb-editable-media');
				node.setAttribute('data-am-vb-fallback-src', image.currentSrc || image.src || '');
				node.setAttribute('data-am-vb-fallback-alt', image.alt || '');
			}
			if (rawNode.matches('a')) {
				node.setAttribute('data-am-vb-link-href', rawNode.getAttribute('href') || '');
			}
			if ('SVG' === rawNode.tagName) {
				node.classList.add('am-vb-editable-media');
				node.setAttribute('data-am-vb-protected', 'true');
				node.setAttribute('data-am-vb-source-label', 'Site icon');
			} else if (rawNode.matches('input,select,textarea') || protectedNode(rawNode, region)) {
				node.setAttribute('data-am-vb-protected', 'true');
				node.setAttribute('data-am-vb-source-label', /bound/i.test(region.kind || '') ? region.kind : 'Protected functional value');
			}
		});
	}

	function annotateFrames(regionNode, region, instanceIndex, instanceCount) {
		var index = 0;
		Array.prototype.forEach.call(regionNode.querySelectorAll('[data-am-vb-editable][data-am-vb-edit-type="frame"]'), function (existing) {
			if (existing.closest('[data-am-vb-region]') !== regionNode) {
				return;
			}
			var match = String(existing.getAttribute('data-am-vb-editable') || '').match(/-frame-(\d+)$/);
			if (match) {
				index = Math.max(index, Number(match[1]) || 0);
			}
		});
		var selector = 'article,figure,form,fieldset,a,button,label,[role="group"],[class~="card"],[class*="rounded-"][class*="border"]';
		var nodes = Array.prototype.slice.call(regionNode.querySelectorAll(selector));
		if (regionNode.matches(selector)) {
			nodes.unshift(regionNode);
		}
		nodes.forEach(function (node) {
			if (node.closest('[data-am-vb-region]') !== regionNode || node.hasAttribute('data-am-vb-editable') || node.closest('[data-am-vb-custom-element]')) {
				return;
			}
			// Nothing auto-discovered inside a form.
			//
			// The selector below matches `form`, `fieldset`, `label` and every
			// field wrapper, so a single enquiry form produced seventeen boxes -
			// each with a dashed outline, a drag grip and a name like "Layout
			// card 17". None of it was worth anything: the keys are positional,
			// so they move the moment a field is added, and a client does not
			// want to restyle the box around an input, they want the label text
			// and the section. Both of those are still fully editable, because
			// annotateElements and the region itself are untouched.
			if ('FORM' === node.tagName || node.closest('form')) {
				return;
			}
			var key = slug(regionKeyPrefix(region, instanceIndex) + '-frame-' + (++index));
			node.setAttribute('data-am-vb-editable', key);
			node.setAttribute('data-am-vb-edit-type', 'frame');
			node.setAttribute('data-am-vb-label', (node.getAttribute('aria-label') || node.getAttribute('title') || 'Layout card ' + index).slice(0, 62));
			node.setAttribute('data-am-vb-fallback', '');
			node.setAttribute('data-am-vb-free-move', 'true');
			node.setAttribute('data-am-vb-protected', 'true');
			node.setAttribute('data-am-vb-source-label', /protected/i.test(region.kind || '') ? region.kind : 'Layout container');
			// No grip anywhere on a form. The grip exists only so a frame can
			// still be grabbed when the pointer lands on a control that opts out
			// of selection with `data-am-vb-allow-action` - and that attribute is
			// used by the navbar and the footer, never by a form. Forms were
			// therefore wearing a badge per field, seven of them on the contact
			// form alone, that bought nothing: the wrapper is still selected by
			// clicking it and still dragged by its own edges.
			var insideForm = 'FORM' === node.tagName || !!node.closest('form');
			if (!insideForm && !node.querySelector(':scope > [data-am-vb-frame-handle]')) {
				var handle = document.createElement('span');
				handle.setAttribute('data-am-vb-frame-handle', 'true');
				handle.setAttribute('aria-hidden', 'true');
				handle.setAttribute('contenteditable', 'false');
				handle.textContent = '⋮⋮';
				node.appendChild(handle);
			}
		});
	}

	function applyElement(key, item) {
		Array.prototype.forEach.call(document.querySelectorAll('[data-am-vb-editable="' + key + '"]'), function (node) {
			var type = node.getAttribute('data-am-vb-edit-type') || item.type || 'text';
			var hasTransform = false;
			['desktop', 'tablet', 'mobile'].forEach(function (device) {
				var values = item[device] || {};
				hasTransform = hasTransform || Number(values.scale || 100) !== 100 || Number(values.offsetX || 0) !== 0 || Number(values.offsetY || 0) !== 0;
				node.style.setProperty('--am-element-scale-' + device, Number(values.scale || 100) / 100);
				node.style.setProperty('--am-element-offset-x-' + device, Number(values.offsetX || 0) + 'px');
				node.style.setProperty('--am-element-offset-y-' + device, Number(values.offsetY || 0) + 'px');
				node.style.setProperty('--am-media-scale-' + device, Number(values.scale || 100) / 100);
				node.style.setProperty('--am-media-offset-x-' + device, Number(values.offsetX || 0) + 'px');
				node.style.setProperty('--am-media-offset-y-' + device, Number(values.offsetY || 0) + 'px');
				node.style.setProperty('--am-vb-saved-element-scale-' + device, Number(values.scale || 100) / 100);
				node.style.setProperty('--am-vb-saved-element-offset-x-' + device, Number(values.offsetX || 0) + 'px');
				node.style.setProperty('--am-vb-saved-element-offset-y-' + device, Number(values.offsetY || 0) + 'px');
				node.style.setProperty('--am-vb-saved-media-scale-' + device, Number(values.scale || 100) / 100);
				node.style.setProperty('--am-vb-saved-media-offset-x-' + device, Number(values.offsetX || 0) + 'px');
				node.style.setProperty('--am-vb-saved-media-offset-y-' + device, Number(values.offsetY || 0) + 'px');
			});
			node.toggleAttribute('data-am-vb-has-transform', hasTransform);
			node.toggleAttribute('data-am-vb-has-saved-transform', hasTransform);
			// Everything past the legacy transforms is rendered from the shared
			// schema, so this page shows exactly what the canvas showed. Styling a
			// protected element is still allowed - protection covers its content
			// and behaviour, not its appearance.
			if (window.AMVBStyleApply) {
				window.AMVBStyleApply.applyToNode(node, type, item);
			}
			if (node.hasAttribute('data-am-vb-protected')) {
				return;
			}
			if (['text', 'textarea', 'button', 'tabs'].indexOf(type) !== -1 && '' !== String(item.value || '')) {
				node.textContent = item.value;
				node.setAttribute('data-am-vb-has-value', 'true');
			}
			var link = node.matches('a') ? node : (node.closest('a') || node.querySelector('a'));
			if (item.href && link) {
				link.setAttribute('href', item.href);
			}
			if (item.linkDescription) {
				node.setAttribute('title', item.linkDescription);
			}
			if (['image', 'logo', 'video'].indexOf(type) === -1) {
				return;
			}
			node.style.setProperty('--am-media-position-x', Number(item.positionX || 50) + '%');
			node.style.setProperty('--am-media-position-y', Number(item.positionY || 50) + '%');
			node.style.setProperty('--am-vb-saved-media-position-x', Number(item.positionX || 50) + '%');
			node.style.setProperty('--am-vb-saved-media-position-y', Number(item.positionY || 50) + '%');
			node.toggleAttribute('data-am-vb-has-saved-media-position', Number(item.positionX || 50) !== 50 || Number(item.positionY || 50) !== 50);
			if (!item.src) {
				return;
			}
			if ('video' === type) {
				var video = 'VIDEO' === node.tagName ? node : node.querySelector('video');
				var source = video && video.querySelector('source');
				if (source && source.getAttribute('src') !== item.src) {
					source.setAttribute('src', item.src);
					video.load();
				}
				return;
			}
			var image = 'IMG' === node.tagName ? node : node.querySelector('img');
			if (image) {
				image.setAttribute('src', item.src);
				image.setAttribute('alt', item.alt || '');
			}
		});
	}

	function customItemNode(section, item) {
		var content = (design.elements || {})[item.key] || {};
		var node;
		if ('button' === item.type) {
			node = document.createElement('a');
			node.href = content.href || '#';
			node.textContent = content.value || 'Learn more';
		} else if ('image' === item.type || 'logo' === item.type) {
			node = document.createElement('div');
			if (content.src) {
				var image = document.createElement('img');
				image.src = content.src;
				image.alt = content.alt || '';
				node.appendChild(image);
			} else {
				var placeholder = document.createElement('span');
				placeholder.className = 'am-home-custom-placeholder';
				placeholder.textContent = 'logo' === item.type ? 'Add logo' : 'Add photo';
				node.appendChild(placeholder);
			}
		} else if ('shape' === item.type) {
			node = document.createElement('span');
		} else {
			node = document.createElement('div');
			node.textContent = content.value || 'Edit this text';
		}
		node.setAttribute('data-am-vb-editable', item.key);
		node.setAttribute('data-am-vb-edit-type', item.type);
		node.setAttribute('data-am-vb-label', item.name || item.type);
		node.setAttribute('data-am-vb-custom-element', item.id);
		node.setAttribute('data-am-vb-custom-owner', section.id);
		node.className = 'am-home-custom-item am-home-custom-' + item.type + (['image', 'logo'].indexOf(item.type) !== -1 ? ' am-vb-editable-media am-home-custom-media' : '');
		if ('logo' === item.type) {
			node.className += ' is-logo';
		}
		if ('shape' === item.type) {
			node.className += ' is-' + (item.shape || 'rectangle');
		}
		['desktop', 'tablet', 'mobile'].forEach(function (device) {
			var values = item[device] || {};
			['x', 'y', 'width', 'height', 'fontSize'].forEach(function (key) {
				if (undefined !== values[key]) {
					node.style.setProperty('--am-custom-' + key + '-' + device, Number(values[key]) + 'px');
				}
			});
		});
		node.style.color = item.color || '';
		if (['shape', 'button'].indexOf(item.type) !== -1) {
			node.style.backgroundColor = item.backgroundColor || '';
		}
		node.style.borderRadius = Number(item.borderRadius || 0) + 'px';
		return node;
	}

	function renderCustomSections() {
		Array.prototype.forEach.call(document.querySelectorAll('[data-am-vb-runtime-custom="' + config.pageSlug + '"]'), function (node) {
			node.remove();
		});
		var insertions = {};
		(design.customSections || []).forEach(function (section) {
			if (false === section.visible) {
				return;
			}
			var anchor = insertions[section.after] || document.querySelector('[data-am-vb-region="' + section.after + '"]');
			if (!anchor || !anchor.parentNode) {
				return;
			}
			var wrapper = document.createElement('section');
			wrapper.className = 'am-home-design-region am-home-custom-section';
			wrapper.setAttribute('data-am-vb-region', 'custom-' + section.id);
			wrapper.setAttribute('data-am-vb-custom-section-id', section.id);
			wrapper.setAttribute('data-am-vb-runtime-custom', config.pageSlug);
			wrapper.style.setProperty('--am-home-background', section.backgroundColor || '#f7faf5');
			['desktop', 'tablet', 'mobile'].forEach(function (device) {
				wrapper.style.setProperty('--am-custom-height-' + device, Number((section[device] || {}).height || 420) + 'px');
			});
			var stage = document.createElement('div');
			stage.className = 'am-home-custom-stage';
			(section.items || []).forEach(function (item) {
				stage.appendChild(customItemNode(section, item));
			});
			wrapper.appendChild(stage);
			anchor.parentNode.insertBefore(wrapper, anchor.nextSibling);
			insertions[section.after] = wrapper;
		});
	}

	function ownedLayoutCandidates(regionNode) {
		var candidates = [{ node: regionNode, depth: 0 }];
		var queue = [];
		Array.prototype.forEach.call(regionNode.children || [], function (child) {
			queue.push({ node: child, depth: 1 });
		});
		while (queue.length) {
			var entry = queue.shift();
			var candidate = entry.node;
			if (candidate.hasAttribute('data-am-vb-region') && candidate !== regionNode) {
				continue;
			}
			candidates.push(entry);
			if (entry.depth >= 4) {
				continue;
			}
			Array.prototype.forEach.call(candidate.children || [], function (child) {
				queue.push({ node: child, depth: entry.depth + 1 });
			});
		}
		return candidates;
	}

	function classTokens(node) {
		return String(node.getAttribute('class') || '');
	}

	function gapTarget(regionNode) {
		var best = { node: regionNode, score: -1 };
		ownedLayoutCandidates(regionNode).forEach(function (entry) {
			var styles = window.getComputedStyle(entry.node);
			var classes = classTokens(entry.node);
			var score = -1;
			if (/^(grid|inline-grid|flex|inline-flex)$/.test(styles.display)) {
				score = 500 - (entry.depth * 20);
			} else if (/(^|\s)(?:[a-z0-9-]+:)*(?:grid|inline-grid|flex|inline-flex)(?:\s|$)/i.test(classes)) {
				score = 400 - (entry.depth * 20);
			} else if (/(^|\s)(?:[a-z0-9-]+:)*gap-[^\s]+/i.test(classes)) {
				score = 300 - (entry.depth * 20);
			}
			if (score > best.score) {
				best = { node: entry.node, score: score };
			}
		});
		return best.node;
	}

	function contentWidthTarget(regionNode) {
		var best = { node: regionNode, score: -1 };
		ownedLayoutCandidates(regionNode).forEach(function (entry) {
			var classes = classTokens(entry.node);
			var score = -1;
			if (/(^|\s)container-wide(?:\s|$)/.test(classes)) {
				score = 500 - (entry.depth * 20);
			} else if (/(^|\s)(?:[a-z0-9-]+:)*max-w-[^\s]+/i.test(classes)) {
				score = 400 - (entry.depth * 20);
			} else {
				var maxWidth = window.getComputedStyle(entry.node).maxWidth;
				if (maxWidth && 'none' !== maxWidth && '0px' !== maxWidth) {
					score = 300 - (entry.depth * 20);
				}
			}
			if (score > best.score) {
				best = { node: entry.node, score: score };
			}
		});
		if (best.score >= 0) {
			return best.node;
		}
		return regionNode;
	}

	function setRegionLayoutValue(target, region, kind, device, value) {
		var property = 'gap' === kind ? '--am-vb-page-gap-' : '--am-vb-page-content-width-';
		target.style.setProperty(property + device, Number(value) + 'px');
		target.setAttribute('data-am-vb-' + ('gap' === kind ? 'gap-' : 'content-width-') + device, 'true');
		target.setAttribute('data-am-vb-layout-' + kind + '-for', region.id);
	}

	function applyDesign() {
		Object.keys(design.sections || {}).forEach(function (id) {
			var section = design.sections[id] || {};
			Array.prototype.forEach.call(document.querySelectorAll('[data-am-vb-region="' + id + '"]'), function (node) {
				var region = (page.regions || []).find(function (candidate) {
					return candidate.id === id;
				}) || { id: id };
				var gapNode = gapTarget(node);
				var widthNode = contentWidthTarget(node);
				node.style.display = false === section.visible ? 'none' : '';
				if (section.backgroundColor) {
					node.style.backgroundColor = section.backgroundColor;
				}
				['desktop', 'tablet', 'mobile'].forEach(function (device) {
					var values = section[device] || {};
					if (Number(values.paddingY) > 0) {
						node.style.setProperty('--am-vb-page-padding-y-' + device, Number(values.paddingY) + 'px');
						node.setAttribute('data-am-vb-padding-' + device, 'true');
					}
					if (Number(values.gap) > 0) {
						setRegionLayoutValue(gapNode, region, 'gap', device, values.gap);
					}
					if (Number(values.contentWidth) > 0) {
						setRegionLayoutValue(widthNode, region, 'content-width', device, values.contentWidth);
					}
				});
				// Radius, border, shadow, hover and scroll animation on a band come
				// from the shared schema applier, exactly as they do on Home and in
				// the editor canvas.
				if (window.AMVBStyleApply) {
					window.AMVBStyleApply.applyToNode(node, 'section', section);
				}
			});
		});
		Object.keys(design.elements || {}).forEach(function (key) {
			applyElement(key, design.elements[key] || {});
		});
		renderCustomSections();
	}

	function ensureRuntimeStyle() {
		if (document.getElementById('am-vb-exact-route-runtime')) {
			return;
		}
		var style = document.createElement('style');
		style.id = 'am-vb-exact-route-runtime';
		style.textContent = [
			'@media(max-width:600px){[data-am-vb-region][data-am-vb-padding-mobile]{padding-block:var(--am-vb-page-padding-y-mobile);}[data-am-vb-gap-mobile]{gap:var(--am-vb-page-gap-mobile);}[data-am-vb-content-width-mobile]{max-width:var(--am-vb-page-content-width-mobile);}}',
			'@media(min-width:601px) and (max-width:900px){[data-am-vb-region][data-am-vb-padding-tablet]{padding-block:var(--am-vb-page-padding-y-tablet);}[data-am-vb-gap-tablet]{gap:var(--am-vb-page-gap-tablet);}[data-am-vb-content-width-tablet]{max-width:var(--am-vb-page-content-width-tablet);}}',
			'@media(min-width:901px){[data-am-vb-region][data-am-vb-padding-desktop]{padding-block:var(--am-vb-page-padding-y-desktop);}[data-am-vb-gap-desktop]{gap:var(--am-vb-page-gap-desktop);}[data-am-vb-content-width-desktop]{max-width:var(--am-vb-page-content-width-desktop);}}'
		].join('');
		document.head.appendChild(style);
	}

	function scan() {
		scanScheduled = false;
		if (applying) {
			return;
		}
		var main = document.getElementById('main');
		if (!main || !main.querySelector('section')) {
			schedule();
			return;
		}
		applying = true;
		ensureRuntimeStyle();
		assignRegions(main);
		(page.regions || []).forEach(function (region) {
			var regionNodes = document.querySelectorAll('[data-am-vb-region="' + region.id + '"]');
			Array.prototype.forEach.call(regionNodes, function (node, instanceIndex) {
				node.setAttribute('data-am-vb-region-instance', String(instanceIndex + 1));
				if (region.editable !== false) {
					annotateElements(node, region, instanceIndex, regionNodes.length);
					annotateFrames(node, region, instanceIndex, regionNodes.length);
					return;
				}
				Array.prototype.forEach.call(node.querySelectorAll('[data-am-vb-editable]'), function (element) {
					element.setAttribute('data-am-vb-protected', 'true');
					element.setAttribute('data-am-vb-hard-protected', 'true');
					element.setAttribute('data-am-vb-source-label', 'Shared global component');
				});
			});
		});
		// On the public page, this runtime owns the saved design. Inside the
		// Visual Builder's Edit canvas, the parent editor owns the current model
		// (including unsaved/recovered changes). Reapplying config.design here
		// after a selection handle or another DOM mutation used to overwrite a
		// drag while it was in progress and made exact-route elements snap back.
		if (!editorOwnsCanvasDesign()) {
			applyDesign();
		}
		applying = false;
		announceRuntimeReady();
	}

	function schedule() {
		if (scanScheduled) {
			return;
		}
		scanScheduled = true;
		window.setTimeout(scan, 80);
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', schedule, { once: true });
	} else {
		schedule();
	}

	var observer = new MutationObserver(function (mutations) {
		if (applying) {
			return;
		}
		var meaningful = mutations.some(function (mutation) {
			return Array.prototype.some.call(mutation.addedNodes || [], function (node) {
				if (1 !== node.nodeType || node.hasAttribute('data-am-vb-runtime-custom')) {
					return false;
				}
				// Builder-only selection/resize affordances are deliberately added to
				// the iframe DOM. They do not change site content and must not trigger
				// a stale design re-scan during pointer movement.
				return !node.matches('#am-vb-live-canvas-bridge,[data-am-vb-frame-handle],.am-vb-media-resize-handle,.am-vb-media-move-handle');
			});
		});
		if (meaningful) {
			schedule();
		}
	});
	observer.observe(document.documentElement, { childList: true, subtree: true });
})(window.amSiteVisual);
