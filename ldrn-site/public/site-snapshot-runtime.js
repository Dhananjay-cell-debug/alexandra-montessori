/* Generated from studio-runtime.js by ldrn_ws_studio_live_runtime() - edit that file, not this one. */
/**
 * Site Editor runtime - the half that lives inside the site.
 *
 * The theme prints this into every INU page, before the page's own scripts
 * run, with the published design document already in `window.__LDRN_WORKSPACE__.studio`.
 * It does two things for every visitor, and a third only inside the Site
 * Editor:
 *
 *   1. Styles. Every layer's style edits become one generated stylesheet -
 *      Desktop as the base, Tablet and Phone as media queries - so they sit
 *      in the cascade the ordinary way, after the build's own CSS.
 *   2. Content. Text, pictures, alt text and links can only be changed in
 *      the DOM, so those are applied as the page is parsed and re-applied
 *      whenever the page's own scripts redraw something.
 *   3. (editor only) Hover, select, double-click-to-type, the layer tree and
 *      the numbers the right-hand panel shows - all called directly by the
 *      editor screen, which shares this origin.
 *
 * How a layer is addressed
 * ------------------------
 * An element the source already names with `data-ldrn-ws` keeps that name
 * (`w:home.intro.title`). Everything else is addressed by where it sits,
 * counted from the nearest named ancestor, from the page's <main id="main">,
 * or from the site chrome (`.site-shell`, which holds header, main, footer):
 *
 *   w:home.intro>2.1      2nd child, then its 1st child, of a named element
 *   m:/about>3.1          inside the About page's <main> - About only
 *   t>1.2                 site chrome (header, footer) - every page
 *
 * The same key is both the storage key and, deterministically, the CSS
 * selector - so the server only has to check its shape, and there is no
 * separate selector string anyone could smuggle anything into.
 */
(function () {
  'use strict';

  var W = window;
  var D = document;
  var bridge = W.__LDRN_WORKSPACE__ || (W.__LDRN_WORKSPACE__ = {});
  var studio = bridge.studio || {};
  var doc = normalise(studio.doc);
  var EDIT = !!studio.edit && W.parent && W.parent !== W;

  var FONTS = {
    display: 'Fraunces, Georgia, serif',
    mark: 'Fraunces, Georgia, serif',
    sans: '"Nunito Sans", sans-serif',
  };
  var BREAKPOINTS = [
    ['d', ''],
    ['t', '@media (max-width: 1199px)'],
    ['p', '@media (max-width: 809px)'],
  ];
  var KEY_RE = /^(w:[A-Za-z0-9._-]{1,100}|[me]:\/[A-Za-z0-9\/_-]{0,100}|t)(>\d{1,3}(\.\d{1,3}){0,40})?$/;
  var VALUE_RE = /^[A-Za-z0-9#%.,()\s+\-\/]{0,160}$/;
  var PROPS = [
    'color', 'font-family', 'font-size', 'font-weight', 'line-height', 'letter-spacing',
    'fill','stroke','stroke-width',
    'text-align', 'text-transform', 'font-style', 'text-decoration-line',
    'width', 'height', 'min-height', 'max-width', 'display', 'flex-direction',
    'justify-content', 'align-items', 'flex-wrap', 'gap', 'align-self', 'order',
    'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
    'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
    'opacity', 'background-color', 'border-radius', 'border-width', 'border-style',
    'border-color', 'box-shadow', 'overflow', 'object-fit', 'object-position',
    'grid-template-columns', 'grid-column', 'aspect-ratio', 'position',
    'top', 'right', 'bottom', 'left', 'z-index', 'background-image',
    'max-height', 'min-width', 'flex', 'text-wrap', 'justify-self',
    'border-top-width', 'border-right-width', 'border-bottom-width', 'border-left-width',
  ];
  var TAGS = /^(div|section|header|footer|article|aside|figure|figcaption|h[1-6]|p|span|small|strong|em|a|img|ul|ol|li|blockquote|hr|iframe|svg|g|path|circle|ellipse|rect|line|polyline|polygon)$/;
  var SVG_TAGS = /^(svg|g|path|circle|ellipse|rect|line|polyline|polygon)$/;
  function safeSvgAttr(name, value) {
    value=String(value);
    if (/^(viewBox|width|height|x|y|x1|x2|y1|y2|cx|cy|r|rx|ry|stroke-width|stroke-dasharray|opacity)$/.test(name)) return /^[0-9eE.,%\s+\-]{1,200}$/.test(value);
    if (/^(d|points|transform)$/.test(name)) return /^[A-Za-z0-9.,()\s+\-]{1,4000}$/.test(value);
    if (/^(fill|stroke)$/.test(name)) return /^(none|currentColor|transparent|#[a-fA-F0-9]{3,8}|[a-zA-Z]{1,30})$/.test(value);
    return /^(stroke-linecap|stroke-linejoin|fill-rule|clip-rule|aria-hidden)$/.test(name) && /^[a-z]{1,15}$/.test(value);
  }
  var EMBED = /^https:\/\/(www\.google\.com\/maps\/embed\?|maps\.google\.com\/maps\?|www\.youtube-nocookie\.com\/embed\/|www\.youtube\.com\/embed\/)[A-Za-z0-9_\-.%&=?+,\/!:@;]*$/;
  var customPages = Array.isArray(studio.pages) ? studio.pages : [];

  function normalise(value) {
    var d = value && typeof value === 'object' ? value : {};
    if (!d.nodes || typeof d.nodes !== 'object' || Array.isArray(d.nodes)) d.nodes = {};
    if (!d.blocks || typeof d.blocks !== 'object' || Array.isArray(d.blocks)) d.blocks = {};
    if (!d.groups || typeof d.groups !== 'object' || Array.isArray(d.groups)) d.groups = {};
    if (!d.layerOrder || typeof d.layerOrder !== 'object' || Array.isArray(d.layerOrder)) d.layerOrder = {};
    return d;
  }

  /* ------------------------------------------------------------ routing */

  // Every INU page is its own document at a clean address (`/about`), so
  // the route is simply the path. /contact is the same page as /lets-talk
  // and shares its edits.
  var ALIASES = { '/index': '/', '/index.html': '/', '/fees-and-funding': '/funded-childcare' };

  function readRoute() {
    var path = W.location.pathname.replace(/^\/ldrn(?=\/|$)/, '').replace(/\.html$/, '').replace(/\/+$/, '') || '/';
    return ALIASES[path] || path;
  }

  var route = readRoute();
  D.documentElement.setAttribute('data-ldrn-ws-route', route);

  /**
   * Inside the editor the site runs as if the visitor asked for reduced
   * motion - a mode the INU build already supports end to end. Reveals are
   * already revealed, the entry burn and parallax stay still and Lenis does
   * not take over scrolling, so what is on the canvas is exactly where it
   * will rest, and a selection box never chases a moving element.
   * Visitors are untouched: this runs only in the editor's own canvas.
   */
  if (EDIT && W.matchMedia) {
    var realMatch = W.matchMedia.bind(W);
    W.matchMedia = function (query) {
      if (/prefers-reduced-motion\s*:\s*reduce/.test(String(query))) {
        return {
          matches: true, media: String(query), onchange: null,
          addEventListener: function () {}, removeEventListener: function () {},
          addListener: function () {}, removeListener: function () {},
          dispatchEvent: function () { return false; },
        };
      }
      return realMatch(query);
    };
  }

  /* ---------------------------------------------------------- addresses */

  // Inserted blocks sit among the site's own children, so a position is
  // always counted among the site's own elements only - an insertion never
  // shifts the address of anything that was already there.
  var OF = ' of :not([data-ldrn-wsn]):not([data-ldrn-ws-label])';

  function selectorFor(key) {
    // An edit whose element moved is followed there; one whose element is
    // gone is applied nowhere rather than to whatever took its place.
    if (aliasing) {
      if (orphans[key]) return null;
      if (aliases[key]) key = aliases[key];
    }
    var label = key.match(/~label([1-9][0-9]?)$/);
    if (label) {
      var owner = selectorFor(key.slice(0, -label[0].length));
      return owner ? owner + ' > [data-ldrn-ws-label="' + label[1] + '"]' : null;
    }
    if (/^n:[a-z0-9]{4,16}$/.test(key)) return '[data-ldrn-wsn="' + key.slice(2) + '"]';
    if (!KEY_RE.test(key)) return null;
    var parts = key.split('>');
    var anchor = parts[0];
    var sel;
    if (anchor === 't') sel = '#root';
    else if (anchor.indexOf('e:') === 0) sel = 'html[data-ldrn-ws-route="' + anchor.slice(2) + '"] .end-reveal';
    else if (anchor.indexOf('m:') === 0) sel = 'html[data-ldrn-ws-route="' + anchor.slice(2) + '"] main#main';
    else {
      sel = '[data-ldrn-ws="' + anchor.slice(2) + '"]';
      // A shared source component must never turn an individual edit into
      // edits on all its instances. Ambiguous old names are left unapplied;
      // the canvas addresses those instances by their own page position.
      if (D.querySelectorAll(sel).length > 1) return null;
    }
    if (parts[1]) {
      parts[1].split('.').forEach(function (n) { sel += ' > :nth-child(' + parseInt(n, 10) + OF + ')'; });
    }
    return sel;
  }

  function validId(id) { return /^[A-Za-z0-9._-]{1,100}$/.test(id || ''); }

  function uniqueName(el) {
    var id = el.getAttribute('data-ldrn-ws');
    return validId(id) && D.querySelectorAll('[data-ldrn-ws="' + id + '"]').length === 1;
  }

  function indexIn(el) {
    var i = 1;
    var s = el;
    while ((s = s.previousElementSibling)) if (!s.hasAttribute('data-ldrn-wsn') && !s.hasAttribute('data-ldrn-ws-label')) i++;
    return i;
  }

  function isMain(el) { return el.tagName === 'MAIN' && el.id === 'main'; }
  function isTop(el) { return el.id === 'root'; }
  function isEnding(el) { return el.classList && el.classList.contains('end-reveal'); }
  function mainEl() { return D.querySelector('main#main'); }
  function topEl() { return D.querySelector('#root'); }
  function endingEl() { return D.querySelector('.end-reveal'); }

  function keyOf(el) {
    var k = placeOf(el);
    for (var dk in aliases) if (aliases[dk] === k) return dk;
    return k;
  }

  function placeOf(el) {
    if (!el || el.nodeType !== 1) return null;
    if (el.hasAttribute('data-ldrn-ws-label')) {
      var owner=keyOf(el.parentElement);
      return owner ? owner+'~label'+el.getAttribute('data-ldrn-ws-label') : null;
    }
    if (el.hasAttribute('data-ldrn-wsn')) return 'n:' + el.getAttribute('data-ldrn-wsn');
    if (el.hasAttribute('data-ldrn-ws') && uniqueName(el)) return 'w:' + el.getAttribute('data-ldrn-ws');
    if (isMain(el)) return 'm:' + route;
    if (isTop(el)) return 't';
    if (isEnding(el)) return 'e:' + route;
    var path = [];
    var cur = el;
    while (cur && cur !== D.body && cur !== D.documentElement) {
      var parent = cur.parentElement;
      if (!parent) return null;
      path.unshift(indexIn(cur));
      if (path.length > 40) return null;
      if (parent.hasAttribute('data-ldrn-ws') && uniqueName(parent)) return 'w:' + parent.getAttribute('data-ldrn-ws') + '>' + path.join('.');
      if (isMain(parent)) return 'm:' + route + '>' + path.join('.');
      if (isTop(parent)) return 't>' + path.join('.');
      if (isEnding(parent)) return 'e:' + route + '>' + path.join('.');
      cur = parent;
    }
    return null;
  }

  function find(key) {
    var sel = selectorFor(key);
    if (!sel) return null;
    try { return D.querySelector(sel); } catch (e) { return null; }
  }

  /*
   * Fingerprints. An address like m:/>3.2 is a position, and a rebuilt site
   * (a section added above) would put the edit on the wrong element without
   * anyone noticing. So each edit also records what it was made on - its
   * section and itself, by tag and first classes - and the address is
   * checked against that before anything is applied.
   */
  var aliases = {};
  var orphans = {};
  var aliasing = true;
  var resolvedAt = 0;
  var resolvedRoute = null;
  var resolveTimer = null;

  function rawFind(key) {
    aliasing = false;
    try { return find(key); } finally { aliasing = true; }
  }

  function sigOf(el) {
    var cls = String(el.getAttribute('class') || '').split(/\s+/).filter(function (c) {
      return c && !/^(is-|ww-|reveal$|parallax$|motion-)/.test(c);
    }).slice(0, 2).join('.');
    return el.tagName.toLowerCase() + (cls ? '.' + cls : '');
  }

  function rootOf(el) {
    var c = el;
    while (c && c.parentElement && !isMain(c.parentElement) && !isTop(c.parentElement) && !isEnding(c.parentElement)) c = c.parentElement;
    return c;
  }

  /**
   * A section's identity: its tag and classes, and - because many sites
   * leave sections unclassed - its id, or else the first named element
   * (data-ldrn-ws) inside it.
   */
  function rootSigOf(r) {
    var sig = sigOf(r);
    if (r.id && /^[A-Za-z0-9_-]{1,60}$/.test(r.id)) return sig + '#' + r.id;
    var named = r.hasAttribute('data-ldrn-ws') ? r : r.querySelector('[data-ldrn-ws]');
    var name = named ? String(named.getAttribute('data-ldrn-ws')).split('.').slice(0, 2).join('.') : '';
    return name && /^[A-Za-z0-9._-]{1,80}$/.test(name) ? sig + '~' + name : sig;
  }

  function fingerprintOf(el) {
    var r = rootOf(el);
    return (r && r !== el ? rootSigOf(r) : '') + '|' + (r === el ? rootSigOf(el) : sigOf(el));
  }

  function fingerprintable(key) { return /^([me]:\/[^>]*|t)>/.test(key); }

  function relocate(key, f) {
    var parts = key.split('>');
    var path = parts[1].split('.');
    var container = parts[0] === 't' ? topEl() : parts[0].indexOf('e:') === 0 ? endingEl() : mainEl();
    if (!container) return null;
    var rootSig = f.split('|')[0] || f.split('|')[1];
    var roots = Array.prototype.filter.call(container.children, function (c) { return !c.hasAttribute('data-ldrn-wsn') && rootSigOf(c) === rootSig; });
    if (roots.length !== 1) return null;
    var cand = parts[0] + '>' + [indexIn(roots[0])].concat(path.slice(1)).join('.');
    if (cand === key) return null;
    var el = rawFind(cand);
    return el && fingerprintOf(el) === f ? cand : null;
  }

  /** Check every fingerprinted edit on this page; true when anything changed. */
  function resolveKeys() {
    if (!D.body || !(mainEl() || topEl())) return false;
    var next = {};
    var lost = {};
    Object.keys(doc.nodes).forEach(function (key) {
      var node = doc.nodes[key];
      if(key.indexOf('w:')===0 && D.querySelectorAll('[data-ldrn-ws="'+key.split('>')[0].replace(/~label[0-9]+$/,'').slice(2)+'"]').length>1) {
        lost[key]=true;return;
      }
      if (!node || typeof node.f !== 'string' || !fingerprintable(key)) return;
      if (/^[me]:/.test(key) && key.split('>')[0] !== key.slice(0, 2) + route) return;
      var el = rawFind(key);
      if (el && fingerprintOf(el) === node.f) return;
      var alt = relocate(key, node.f);
      if (alt) next[key] = alt;
      else lost[key] = true;
    });
    // Blocks placed after or inside one of the site's elements, likewise.
    Object.keys(doc.blocks).forEach(function (id) {
      var block = doc.blocks[id];
      var at = block && block.at;
      if (!at || typeof block.af !== 'string' || !fingerprintable(at) || next[at] || lost[at]) return;
      if (at.indexOf('m:') === 0 && at.split('>')[0] !== 'm:' + route) return;
      var el = rawFind(at);
      if (el && fingerprintOf(el) === block.af) return;
      var alt = relocate(at, block.af);
      if (alt) next[at] = alt;
      else lost[at] = true;
    });
    var moved = JSON.stringify(next) !== JSON.stringify(aliases) || JSON.stringify(lost) !== JSON.stringify(orphans);
    aliases = next;
    orphans = lost;
    resolvedAt = Date.now();
    resolvedRoute = route;
    return moved;
  }

  /* ------------------------------------------------------------- styles */

  function declarations(styles) {
    var out = '';
    if (!styles) return out;
    PROPS.forEach(function (prop) {
      var value = styles[prop];
      if (typeof value !== 'string' && typeof value !== 'number') return;
      value = String(value).trim();
      if (value === '') return;
      if (prop === 'font-family') {
        value = FONTS[value];
        if (!value) return;
      } else if (!VALUE_RE.test(value) || /url|expression|var\(/i.test(value)) {
        return;
      } else if (prop === 'position' && !/^(static|relative|absolute|sticky)$/.test(value)) {
        return;
      } else if (prop === 'background-image' && value !== 'none' && !/^(linear|radial)-gradient\(/.test(value)) {
        return;
      }
      out += prop + ':' + value + ' !important;';
    });
    return out;
  }

  /**
   * A width set in pixels on a larger screen is capped at the screen on the
   * smaller ones - unless someone set that smaller screen's width or max
   * width themselves. A 634px heading made on Desktop must not run off a
   * 390px phone just because nobody looked at Phone afterwards.
   */
  function guardSmaller(s, bp) {
    var own = s[bp] || {};
    var out = '';
    var from = bp === 'p' ? (s.t && s.t.width ? s.t : s.d) : s.d;
    if (!own.width && !own['max-width'] && from && PX_RE.test(String(from.width || ''))) out += 'max-width:100% !important;';
    // Placed freely on a larger screen: the same spot while it fits its
    // container here, pulled in from the right when it would not.
    if (inherited(s, bp, 'position') === 'absolute' && !own.left && !own.right && !PX_RE.test(inherited(s, bp, 'right'))) {
      var left = inherited(s, bp, 'left');
      var width = inherited(s, bp, 'width');
      if (PX_RE.test(left) && PX_RE.test(width)) {
        out += 'left:min(' + left + ', max(0px, calc(100% - min(' + width + ', 100%)))) !important;';
      }
    }
    return out;
  }
  var PX_RE = /^-?\d+(\.\d+)?px$/;

  /** A style as it reaches this screen: its own, else the next larger one's. */
  function inherited(s, bp, prop) {
    var order = bp === 'p' ? ['p', 't', 'd'] : bp === 't' ? ['t', 'd'] : ['d'];
    for (var i = 0; i < order.length; i++) {
      var v = s[order[i]] && s[order[i]][prop];
      if (v !== undefined && v !== null && String(v).trim() !== '') return String(v).trim();
    }
    return '';
  }

  /**
   * An element the site already places absolutely is moved by its insets -
   * left AND right in pixels, measured on the screen it was moved on. On a
   * smaller screen those would put it anywhere (773px from the left of a
   * 390px phone), so they apply on that screen only; smaller ones keep the
   * site's own position for it until someone moves it there too.
   */
  var INSETS = ['left', 'right', 'top', 'bottom'];
  var ONLY_AT = {
    d: '@media (min-width: 1200px)',
    t: '@media (min-width: 810px) and (max-width: 1199px)',
    p: '@media (max-width: 809px)',
  };
  function pinnedInsets(styles) {
    return !!styles && PX_RE.test(String(styles.left || '').trim()) && PX_RE.test(String(styles.right || '').trim());
  }
  function split(styles, keep) {
    var out = {};
    Object.keys(styles).forEach(function (p) { if ((INSETS.indexOf(p) !== -1) === keep) out[p] = styles[p]; });
    return out;
  }

  function stylesheet(d) {
    var blocks = { d: '', t: '', p: '' };
    var only = { d: '', t: '', p: '' };
    Object.keys(d.nodes).forEach(function (key) {
      var node = d.nodes[key];
      var sel = selectorFor(key);
      if (!sel || !node || !node.s) return;
      BREAKPOINTS.forEach(function (bp) {
        var own = node.s[bp[0]];
        var pinned = pinnedInsets(own);
        var decl = declarations(pinned ? split(own, false) : own);
        if (bp[0] !== 'd') decl += guardSmaller(node.s, bp[0]);
        if (decl) blocks[bp[0]] += sel + '{' + decl + '}\n';
        if (pinned) only[bp[0]] += sel + '{' + declarations(split(own, true)) + '}\n';
      });
    });
    // A page made in the Site Editor is a route the build does not know, so
    // the theme serves it with a "not found" section - which the page's own
    // content replaces.
    var css = 'html[data-ldrn-ws-custom] .ldrn-not-found{display:none!important}\n' + blocks.d;
    BREAKPOINTS.forEach(function (bp) {
      if (bp[1] && blocks[bp[0]]) css += bp[1] + '{\n' + blocks[bp[0]] + '}\n';
    });
    BREAKPOINTS.forEach(function (bp) {
      if (only[bp[0]]) css += ONLY_AT[bp[0]] + '{\n' + only[bp[0]] + '}\n';
    });
    return css;
  }

  var styleEl = null;
  function paintStyles() {
    if (!styleEl) {
      styleEl = D.createElement('style');
      styleEl.id = 'ldrn-studio-css';
    }
    // Last in <head> so it follows the build's own stylesheet.
    if (styleEl.parentNode !== D.head && D.head) D.head.appendChild(styleEl);
    var css = stylesheet(doc);
    if (styleEl.textContent !== css) styleEl.textContent = css;
  }

  /* ------------------------------------------------------------ content */

  function readText(el) {
    var s = '';
    for (var n = el.firstChild; n; n = n.nextSibling) {
      if (n.nodeType === 3) s += n.nodeValue;
      else if (n.nodeName === 'BR') s += '\n';
      else if (n.nodeType === 1) s += readText(n);
    }
    return s;
  }

  function tidy(text) {
    return String(text).split('\n').map(function (line) { return line.replace(/\s+/g, ' ').trim(); }).join('\n').trim();
  }

  /**
   * Replace, never mutate. The site's translator remembers every text node's
   * original English and puts it back on the next redraw - editing a node in
   * place is undone within a second. A fresh node is simply new source text.
   */
  function writeText(el, text) {
    if (tidy(readText(el)) === tidy(text)) return;
    var frag = D.createDocumentFragment();
    String(text).split('\n').forEach(function (line, i) {
      if (i) frag.appendChild(D.createElement('br'));
      frag.appendChild(D.createTextNode(line));
    });
    while (el.firstChild) el.removeChild(el.firstChild);
    el.appendChild(frag);
  }

  /*
   * Rich text: a run of words with inline styling kept. Read from the page
   * as runs - strings, line breaks, and inline elements (tag, classes, the
   * site's own data-ldrn-ws name, their own runs) - and built back from them
   * with createElement and text nodes only, never innerHTML.
   */
  var RICH = /^(EM|STRONG|B|I|U|S|SPAN|SMALL|SUP|SUB|MARK)$/;

  function readRich(el) {
    var out = [];
    function push(t) {
      if (!t) return;
      if (typeof out[out.length - 1] === 'string') out[out.length - 1] += t;
      else out.push(t);
    }
    for (var n = el.firstChild; n; n = n.nextSibling) {
      if (n.nodeType === 3) {
        n.nodeValue.split('\n').forEach(function (part, i) { if (i) out.push({ br: 1 }); push(part.replace(/\s+/g, ' ')); });
      } else if (n.nodeName === 'BR') {
        out.push({ br: 1 });
      } else if (n.nodeType === 1 && RICH.test(n.tagName)) {
        var item = { tag: n.tagName.toLowerCase() };
        var cls = n.getAttribute('class');
        if (cls) item.cls = cls;
        if (n.hasAttribute('data-ldrn-ws')) item.ww = n.getAttribute('data-ldrn-ws');
        item.kids = readRich(n);
        out.push(item);
      } else if (n.nodeType === 1) {
        push(readText(n));
      }
    }
    return out;
  }

  function hasRich(el) {
    return Array.prototype.some.call(el.children || [], function (c) { return RICH.test(c.tagName) && c.textContent.trim() !== ''; });
  }

  function buildRich(runs, parent) {
    (runs || []).forEach(function (r) {
      if (typeof r === 'string') parent.appendChild(D.createTextNode(r));
      else if (r && r.br) parent.appendChild(D.createElement('br'));
      else if (r && typeof r.tag === 'string' && RICH.test(r.tag.toUpperCase())) {
        var e = D.createElement(r.tag);
        if (r.cls) e.setAttribute('class', String(r.cls));
        if (r.ww && validId(r.ww)) e.setAttribute('data-ldrn-ws', r.ww);
        buildRich(r.kids, e);
        parent.appendChild(e);
      }
    });
  }

  function writeRich(el, runs) {
    if (JSON.stringify(readRich(el)) === JSON.stringify(runs)) return;
    while (el.firstChild) el.removeChild(el.firstChild);
    buildRich(runs, el);
  }

  function imageIn(el) {
    if (!el) return null;
    if (el.tagName === 'IMG') return el;
    var imgs = el.querySelectorAll('img');
    return imgs.length === 1 ? imgs[0] : null;
  }

  // A native anchor covers an otherwise unlinked layer without moving React's
  // children. Forms and controls keep their original behaviour and never gain
  // a redirect. The generated anchor is excluded from structural addresses.
  function linkFor(el) {
    return el.tagName === 'A' ? el : el.closest('a') ||
      D.querySelector('a[data-ldrn-link-for="'+keyOf(el)+'"]');
  }
  function canLink(el) {
    return !el.closest('button,input,select,textarea,a,[contenteditable="true"]') &&
      !el.querySelector('a,button,input,select,textarea') &&
      (linkTextish(el) || el.tagName === 'IMG' || el.tagName.toLowerCase() === 'svg');
  }
  function linkTextish(el) {
    if (!el || /^(IMG|PICTURE|VIDEO|SVG|svg|INPUT|TEXTAREA|SELECT|IFRAME|CANVAS)$/.test(el.tagName))return false;
    var text=false;
    for(var child=el.firstChild;child;child=child.nextSibling){
      if(child.nodeType===3 && child.nodeValue.trim())text=true;
      else if(child.nodeType===1){
        if(!/^(BR|SPAN|EM|STRONG|I|B|SMALL|SUP|SUB|MARK|U|ABBR|TIME|Q|S)$/.test(child.tagName) || child.querySelector('img,svg,video'))return false;
        if(child.textContent.trim())text=true;
      }
    }
    return text;
  }
  function positionLink(m) {
    if (!m.createdLink || !m.el.isConnected) return;
    var a=m.createdLink, host=m.linkHost;
    var r=m.el.getBoundingClientRect(), p=host.getBoundingClientRect();
    var values={left:(r.left-p.left+host.scrollLeft-host.clientLeft)+'px',top:(r.top-p.top+host.scrollTop-host.clientTop)+'px',width:r.width+'px',height:r.height+'px'};
    Object.keys(values).forEach(function(k){if(a.style[k]!==values[k])a.style[k]=values[k];});
    var label=m.el.getAttribute('alt') || m.el.getAttribute('aria-label') || tidy(m.el.textContent) || 'Open link';
    if(a.getAttribute('aria-label')!==label)a.setAttribute('aria-label',label);
  }
  function createLink(m) {
    var el=m.el;
    if (!canLink(el)) return null;
    var host=el.parentElement;
    if (host && host.tagName==='PICTURE') host=host.parentElement;
    if (!host) return null;
    var a=D.createElement('a');
    a.setAttribute('data-ldrn-wsn','link-overlay');
    a.setAttribute('data-ldrn-link-for',m.key);
    a.style.cssText='position:absolute;display:block;z-index:2;background:transparent;cursor:pointer;';
    if (W.getComputedStyle(host).position==='static') {
      m.hostPosition=host.style.position;
      host.style.position='relative';
    }
    m.linkHost=host; m.createdLink=a;
    host.appendChild(a);
    m.linkObserver=new ResizeObserver(function(){positionLink(m);});
    m.linkObserver.observe(el); m.linkObserver.observe(host);
    positionLink(m);
    return a;
  }
  function removeCreatedLink(m) {
    if (!m.createdLink) return;
    m.createdLink.remove();
    if(m.linkObserver)m.linkObserver.disconnect();
    if(m.hostPosition!==undefined && !m.linkHost.querySelector('[data-ldrn-link-for]'))m.linkHost.style.position=m.hostPosition;
    delete m.createdLink; delete m.linkObserver; delete m.linkHost; delete m.hostPosition;
  }
  W.addEventListener('resize',function(){originals.forEach(positionLink);});

  function safeUrl(url, forLink) {
    url = String(url || '').trim();
    if (!url) return '';
    var assets=W.ldrnData||{};
    if(!forLink && assets.snapshotAssetBase){
      if(assets.snapshotAssetMap && assets.snapshotAssetMap[url])url=assets.snapshotAssetMap[url];
      else if(url.indexOf('/assets/')===0)url=assets.snapshotAssetBase+url;
      else url=url.replace(/(?:https?:\/\/[^/]+)?\/wp-content\/themes\/ldrn-theme\/dist\//g,assets.snapshotAssetBase+'/');
    }
    if (/^(https?:)?\/\//i.test(url) || url.charAt(0) === '/' || url.charAt(0) === '#') return url;
    if (forLink && /^(mailto|tel):/i.test(url)) return url;
    return '';
  }

  /**
   * What an element said before an edit reached it. The INU pages are plain
   * HTML - nothing redraws them from a source of truth - so when an edit is
   * taken away again (Undo, Discard, restoring a session) the runtime has to
   * put the original back itself. Each field is remembered the first time it
   * is overwritten, and handed back the moment the document stops setting it.
   */
  var originals = [];

  function memoryOf(el, key) {
    for (var i = 0; i < originals.length; i++) if (originals[i].el === el) return originals[i];
    var entry = { el: el, key: key };
    originals.push(entry);
    return entry;
  }

  function attr(el, name) { return el.hasAttribute(name) ? el.getAttribute(name) : null; }
  function setAttr(el, name, value) { if (value === null) el.removeAttribute(name); else el.setAttribute(name, value); }

  function restoreStale() {
    originals = originals.filter(function (m) {
      if (!m.el.isConnected) {removeCreatedLink(m);return false;}
      if (m.el === editingEl) return true;
      var node = doc.nodes[m.key] || {};
      if(m.createdLink && (typeof node.href!=='string' || !node.href))removeCreatedLink(m);
      if (m.text && typeof node.text !== 'string' && !Array.isArray(node.rich)) {
        while (m.el.firstChild) m.el.removeChild(m.el.firstChild);
        m.text.forEach(function (n) { m.el.appendChild(n.cloneNode(true)); });
        delete m.text;
      }
      if (m.img && !node.src) {
        setAttr(m.img.el, 'src', m.img.src);
        setAttr(m.img.el, 'srcset', m.img.srcset);
        setAttr(m.img.el, 'sizes', m.img.sizes);
        m.img.sources.forEach(function (x) { setAttr(x[0], 'srcset', x[1]); });
        delete m.img;
      }
      if (m.alt && typeof node.alt !== 'string') { setAttr(m.alt.el, 'alt', m.alt.value); delete m.alt; }
      if (m.href && typeof node.href !== 'string') { setAttr(m.href.el, 'href', m.href.value); delete m.href; }
      if (m.label && typeof node.label !== 'string') { m.label.forEach(function(item){if(item[0].parentNode===m.el)item[0].nodeValue=item[1];}); delete m.label; }
      if (m.placeholder && typeof node.placeholder !== 'string') {setAttr(m.el,'placeholder',m.placeholder.value);delete m.placeholder;}
      if(m.options && !Array.isArray(node.options)){m.options.forEach(function(item){if(item[0].isConnected)item[0].textContent=item[1];});delete m.options;}
      if (m.linkTarget && typeof node.newTab !== 'boolean') {setAttr(m.linkTarget.el,'target',m.linkTarget.target);setAttr(m.linkTarget.el,'rel',m.linkTarget.rel);delete m.linkTarget;}
      return !!(m.text || m.img || m.alt || m.href || m.label || m.placeholder || m.linkTarget || m.createdLink || m.options);
    });
  }

  function applyContent(key, node, el) {
    // Existing saves may store the old button label on its container. Preserve
    // that edit while applying it to the new child, without destroying icons.
    var labelChild=/^(A|BUTTON)$/.test(el.tagName) ? el.querySelector(':scope > [data-ldrn-ws-label="1"]') : null;
    if(labelChild && (typeof node.text==='string' || typeof node.label==='string' || Array.isArray(node.rich))) {
      var labelNode={};
      if(typeof node.label==='string')labelNode.text=node.label;
      else if(typeof node.text==='string')labelNode.text=node.text;
      if(Array.isArray(node.rich))labelNode.rich=node.rich;
      applyContent(key,labelNode,labelChild);
      node=Object.assign({},node);delete node.text;delete node.label;delete node.rich;
    }
    var memory = memoryOf(el, key);
    if(el.tagName==='SELECT' && Array.isArray(node.options)) {
      if(!memory.options)memory.options=Array.prototype.map.call(el.options,function(option){return [option,option.textContent];});
      Array.prototype.forEach.call(el.options,function(option,index){
        var saved=node.options[index];
        if(saved && saved.value===option.value && option.textContent!==saved.label)option.textContent=saved.label;
      });
    }
    if (typeof node.label === 'string') {
      var labels = Array.prototype.filter.call(el.childNodes,function(child){return child.nodeType===3&&child.nodeValue.trim();});
      if (!labels.length && memory.label) labels=memory.label.map(function(item){return item[0];}).filter(function(child){return child.parentNode===el;});
      if (labels.length && labels.map(function(child){return child.nodeValue;}).join('')!==node.label) {
        if(!memory.label)memory.label=labels.map(function(child){return [child,child.nodeValue];});
        labels.forEach(function(child,index){child.nodeValue=index===0?node.label:'';});
      }
    }
    if(typeof node.placeholder==='string'&&el.getAttribute('placeholder')!==node.placeholder){
      if(!memory.placeholder)memory.placeholder={value:attr(el,'placeholder')};
      el.setAttribute('placeholder',node.placeholder);
    }
    if (Array.isArray(node.rich)) {
      if (!memory.text && JSON.stringify(readRich(el)) !== JSON.stringify(node.rich)) {
        memory.text = Array.prototype.map.call(el.childNodes, function (n) { return n.cloneNode(true); });
      }
      writeRich(el, node.rich);
    } else if (typeof node.text === 'string') {
      if (!memory.text && tidy(readText(el)) !== tidy(node.text)) {
        memory.text = Array.prototype.map.call(el.childNodes, function (n) { return n.cloneNode(true); });
      }
      writeText(el, node.text);
    }
    if (node.src || typeof node.alt === 'string') {
      var img = imageIn(el);
      if (img) {
        var src = safeUrl(node.src);
        if (src && img.getAttribute('src') !== src) {
          if (!memory.img) {
            var holder = img.parentElement && img.parentElement.tagName === 'PICTURE' ? img.parentElement : null;
            memory.img = {
              el: img, src: attr(img, 'src'), srcset: attr(img, 'srcset'), sizes: attr(img, 'sizes'),
              sources: holder ? Array.prototype.map.call(holder.querySelectorAll('source'), function (x) { return [x, attr(x, 'srcset')]; }) : [],
            };
          }
          img.setAttribute('src', src);
          img.removeAttribute('srcset');
          img.removeAttribute('sizes');
          var pic = img.parentElement;
          if (pic && pic.tagName === 'PICTURE') {
            Array.prototype.forEach.call(pic.querySelectorAll('source'), function (s) { s.setAttribute('srcset', src); });
          }
        }
        if (typeof node.alt === 'string' && img.getAttribute('alt') !== node.alt) {
          if (!memory.alt) memory.alt = { el: img, value: attr(img, 'alt') };
          img.setAttribute('alt', node.alt);
        }
      }
    }
    if (typeof node.href === 'string') {
      var a = linkFor(el);
      var href = safeUrl(node.href, true);
      if(!a && href)a=createLink(memory);
      if (a && (href || node.href==='') && a.getAttribute('href') !== (href || null)) {
        if (!memory.href) memory.href = { el: a, value: attr(a, 'href') };
        setAttr(a,'href',href||null);
      }
    }
    if(typeof node.newTab==='boolean') {
      var targetLink=linkFor(el);
      if(targetLink) {
        if(!memory.linkTarget)memory.linkTarget={el:targetLink,target:attr(targetLink,'target'),rel:attr(targetLink,'rel')};
        var target=node.newTab?'_blank':null;
        if(attr(targetLink,'target')!==target)setAttr(targetLink,'target',target);
        if(node.newTab && targetLink.getAttribute('rel')!=='noopener noreferrer')targetLink.setAttribute('rel','noopener noreferrer');
      }
    }
    positionLink(memory);
  }

  /**
   * A named element can also carry an older edit from the page-by-page
   * editor. Where both set the same thing, this editor's value wins, and the
   * older one is withdrawn so the two never take turns rewriting the node.
   */
  function yieldLegacy() {
    var legacy = bridge.content;
    if (!legacy || typeof legacy !== 'object') return;
    Object.keys(doc.nodes).forEach(function (key) {
      if (key.indexOf('w:') !== 0 || key.indexOf('>') !== -1) return;
      var old = legacy[key.slice(2)];
      var node = doc.nodes[key];
      if (!old || !node) return;
      if (typeof node.text === 'string') delete old.text;
      if (node.src) { delete old.src; delete old.attachment; }
      if (typeof node.alt === 'string') delete old.alt;
    });
  }

  /* ------------------------------------------------------------- blocks */

  /**
   * Everything added from the Assets panel. Each block is a small tree the
   * document owns, placed relative to an anchor - after a section, at the end
   * of <main>, inside a container. It is rebuilt only when its tree changes,
   * and put back whenever React redraws the part of the page it lives in.
   */
  var built = {};
  var treeIndex = {};

  function indexTrees() {
    treeIndex = {};
    Object.keys(doc.blocks).forEach(function (id) {
      (function walk(node) {
        if (!node || !node.id) return;
        treeIndex[node.id] = node;
        (node.kids || []).forEach(walk);
      })(doc.blocks[id].tree);
    });
  }

  function renderCollection(el, node) {
    var data = W.ldrnData || {};
    var type = node.collection;
    var records = type === 'articles' ? ((data.blogArchive && data.blogArchive.items) || []).slice() : (data[type] || []).slice();
    if (type === 'articles' && data.blogArchive && data.blogArchive.featured) records.unshift(data.blogArchive.featured);
    if (type === 'gallery') {
      records = [];
      (data.settings || []).forEach(function(setting) {
        (setting.gallery || []).forEach(function(url) {
          var hash=2166136261;
          Array.from(String(url)).forEach(function(char){hash=Math.imul(hash^char.charCodeAt(0),16777619);});
          records.push({id:setting.id+'-'+(hash>>>0).toString(36),image:url,title:'Life at Little Rainbows '+setting.name});
        });
      });
    }
    el.className = 'ldrn-workspace-collection';
    var style = D.createElement('style');
    if (!D.getElementById('ldrn-collection-css')) {
      style.id = 'ldrn-collection-css';
      style.textContent = '.ldrn-workspace-collection{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px}.ldrn-collection-card{min-width:0;background:#fff;border:1px solid #cfecf8;border-radius:24px;overflow:hidden}.ldrn-collection-card img{width:100%;height:240px;object-fit:cover}.ldrn-collection-card h3,.ldrn-collection-card p,.ldrn-collection-card a{margin:20px;display:block}.ldrn-collection-card h3{font-size:24px}.ldrn-collection-card p{font-size:16px;line-height:1.6}@media(max-width:1199px){.ldrn-workspace-collection{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:809px){.ldrn-workspace-collection{grid-template-columns:minmax(0,1fr)}}';
      D.head.appendChild(style);
    }
    if (!records.length) {
      var empty = D.createElement('p'); empty.textContent = 'New '+type+' will appear here when the nursery publishes them.'; el.appendChild(empty); return;
    }
    records.slice(0,12).forEach(function(record,index) {
      var identity = String(record.slug || record.id || index).replace(/[^a-zA-Z0-9._-]/g,'-').slice(0,40);
      var prefix = 'collection.'+node.id+'.'+identity;
      function part(tag,text,suffix) { var item = D.createElement(tag); item.setAttribute('data-ldrn-ws',prefix+'.'+suffix); if(text) item.textContent=text; return item; }
      var card = part('article',null,'card'); card.className='ldrn-collection-card';
      var url = safeUrl(record.image);
      if (url) { var img = part('img',null,'image'); img.src=url; img.alt=record.title || ''; img.loading='lazy'; card.appendChild(img); }
      var title = record.title || record.name || (type==='testimonials' ? 'A parent story' : 'Nursery update');
      card.appendChild(part('h3',title,'title'));
      var body = record.excerpt || record.short || record.quote || record.date || '';
      if (body) card.appendChild(part('p',body,'body'));
      var href = type === 'events' ? '/events/'+identity : type === 'articles' ? '/blogs/'+identity : type === 'jobs' ? '/careers/apply?role='+encodeURIComponent(record.title || '') : '';
      if (href) { var link=part('a',type==='jobs'?'Apply for this role':'Read more','link'); link.href=href; card.appendChild(link); }
      el.appendChild(card);
    });
  }

  function build(node) {
    if (!node || !TAGS.test(node.tag || '') || !/^[a-z0-9]{4,16}$/.test(node.id || '')) return null;
    var svg=SVG_TAGS.test(node.tag);
    var el = svg ? D.createElementNS('http://www.w3.org/2000/svg',node.tag) : D.createElement(node.tag);
    el.setAttribute('data-ldrn-wsn', node.id);
    if (node.cls) el.setAttribute('class',String(node.cls));
    if (svg) Object.keys(node.attrs||{}).forEach(function(name){if(safeSvgAttr(name,node.attrs[name]))el.setAttribute(name,node.attrs[name]);});
    if (node.tag === 'img') {
      var src = safeUrl(node.src);
      if (src) el.setAttribute('src', src);
      el.setAttribute('alt', node.alt || '');
      el.setAttribute('loading', 'lazy');
      el.setAttribute('decoding', 'async');
    } else if (node.tag === 'iframe') {
      if (EMBED.test(node.src || '')) el.setAttribute('src', node.src);
      el.setAttribute('loading', 'lazy');
      el.setAttribute('allowfullscreen', '');
      el.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
      el.setAttribute('title', node.name || 'Embedded content');
    } else if (node.tag !== 'hr') {
      if (node.tag === 'a') {
        var href = safeUrl(node.href, true);
        if (href) el.setAttribute('href', href);
        if (node.newTab===true || (node.newTab===undefined && /^https?:/i.test(href))) { el.setAttribute('target', '_blank'); el.setAttribute('rel', 'noopener noreferrer'); }
      }
      if (typeof node.text === 'string' && node.text !== '') {
        if (node.tag === 'a') {
          var label=D.createElement('span');label.setAttribute('data-ldrn-ws-label','1');label.style.display='inline-block';
          writeText(label,node.text);el.appendChild(label);
        } else writeText(el, node.text);
      }
      (node.kids || []).forEach(function (kid) {
        var child = build(kid);
        if (child) el.appendChild(child);
      });
    }
    if (node.collection) renderCollection(el, node);
    return el;
  }

  function placed(el, anchor, pos) {
    if (pos === 'after') return anchor.nextElementSibling === el;
    if (pos === 'before') return anchor.previousElementSibling === el;
    if (el.parentElement !== anchor) return false;
    // "At the end" means nothing of the site's own comes after it - other
    // inserted blocks chained behind it are fine.
    var s = pos === 'append' ? el.nextElementSibling : el.previousElementSibling;
    while (s) {
      if (!s.hasAttribute('data-ldrn-wsn')) return false;
      s = pos === 'append' ? s.nextElementSibling : s.previousElementSibling;
    }
    return true;
  }

  function placeBlock(el, anchor, pos) {
    if (pos === 'after') anchor.parentNode.insertBefore(el, anchor.nextSibling);
    else if (pos === 'before') anchor.parentNode.insertBefore(el, anchor);
    else if (pos === 'append') anchor.appendChild(el);
    else anchor.insertBefore(el, anchor.firstChild);
  }

  function renderBlocks() {
    var blocks = doc.blocks;
    Object.keys(built).forEach(function (id) {
      if (!blocks[id]) {
        if (built[id].el.parentNode) built[id].el.parentNode.removeChild(built[id].el);
        delete built[id];
      }
    });
    var ids = Object.keys(blocks);
    // Blocks can be anchored to other blocks, so a few passes settle chains.
    for (var pass = 0; pass < 8; pass++) {
      var changed = false;
      ids.forEach(function (id) {
        var b = blocks[id];
        if (!b || !b.tree) return;
        var sig = JSON.stringify(b.tree);
        var entry = built[id];
        if (entry && entry.sig !== sig) {
          var fresh = build(b.tree);
          if (entry.el.parentNode && fresh) entry.el.parentNode.replaceChild(fresh, entry.el);
          entry = built[id] = fresh ? { el: fresh, sig: sig } : null;
          if (!entry) delete built[id];
          changed = true;
        }
        if (!entry) {
          var el = build(b.tree);
          if (!el) return;
          entry = built[id] = { el: el, sig: sig };
        }
        var anchor = find(b.at);
        if (!anchor || entry.el.contains(anchor)) {
          if (entry.el.parentNode) { entry.el.parentNode.removeChild(entry.el); changed = true; }
          return;
        }
        if (!placed(entry.el, anchor, b.pos)) {
          placeBlock(entry.el, anchor, b.pos);
          changed = true;
        }
      });
      if (!changed) break;
    }
  }

  /* ---------------------------------------------------------- new pages */

  function customPage() {
    for (var i = 0; i < customPages.length; i++) if (customPages[i].route === route) return customPages[i];
    return null;
  }

  function pageHasContent() {
    var mine = 'm:' + route;
    return Object.keys(doc.blocks).some(function (id) {
      var at = doc.blocks[id].at || '';
      return at === mine || at.indexOf(mine + '>') === 0;
    });
  }

  function paintPage() {
    var page = customPage();
    // In the editor an empty new page is still a page to build on; for a
    // visitor it only replaces the "not found" once something is published.
    var on = !!page && (EDIT || pageHasContent());
    if (on !== D.documentElement.hasAttribute('data-ldrn-ws-custom')) {
      if (on) D.documentElement.setAttribute('data-ldrn-ws-custom', '');
      else D.documentElement.removeAttribute('data-ldrn-ws-custom');
    }
    if (on) {
      var title = page.label + ' â€” Little Rainbows';
      if (D.title !== title) D.title = title;
    }
    paintSeo();
  }

  /**
   * The SEO screen's copy for this page. The site's own code may set its
   * title after it draws; the head is watched, so this always has the last
   * word.
   */
  function metaTag(attr, name, value) {
    var el = D.head && D.head.querySelector('meta[' + attr + '="' + name + '"]');
    if (!el && D.head) {
      el = D.createElement('meta');
      el.setAttribute(attr, name);
      D.head.appendChild(el);
    }
    if (el && el.getAttribute('content') !== value) el.setAttribute('content', value);
  }

  function paintSeo() {
    var all = bridge.seo;
    var seo = all && typeof all === 'object' ? all[route] : null;
    if (!seo) return;
    if (seo.title) {
      if (D.title !== seo.title) D.title = seo.title;
      metaTag('property', 'og:title', seo.title);
    }
    if (seo.description) {
      metaTag('name', 'description', seo.description);
      metaTag('property', 'og:description', seo.description);
    }
  }

  var editingEl = null;
  function applyAll() {
    restoreStale();
    Object.keys(doc.nodes).forEach(function (key) {
      var node = doc.nodes[key];
      if (!node || (node.text === undefined && node.label === undefined && node.placeholder === undefined && node.options === undefined && node.newTab === undefined && node.rich === undefined && !node.src && node.alt === undefined && node.href === undefined)) return;
      var sel = selectorFor(key);
      if (!sel) return;
      var els;
      try { els = D.querySelectorAll(sel); } catch (e) { return; }
      Array.prototype.forEach.call(els, function (el) {
        if (el === editingEl || (editingEl && editingEl.contains(el))) return;
        applyContent(key, node, el);
      });
    });
  }

  /**
   * Straight from the observer callback: it runs as a microtask, before the
   * browser paints, so React's own copy is never on screen for a frame - and,
   * unlike requestAnimationFrame, it still runs in a tab that is not visible.
   * Our own writes trigger one more callback, which finds nothing to change.
   */
  function schedule() {
    var nextRoute = readRoute();
    if (nextRoute !== route) { route = nextRoute; D.documentElement.setAttribute('data-ldrn-ws-route', route); paintStyles(); if (EDIT) host('route', {route: route}); }
    if (resolvedRoute !== route || Date.now() - resolvedAt > 400) {
      if (resolveKeys()) paintStyles();
    } else if (!resolveTimer) {
      // Checked a moment ago: check again once this burst of changes is
      // over, so no change to the page goes unchecked.
      resolveTimer = setTimeout(function () {
        resolveTimer = null;
        if (resolveKeys()) { paintStyles(); renderBlocks(); applyAll(); }
      }, 450);
    }
    renderBlocks();
    paintPage();
    applyAll();
  }

  yieldLegacy();
  indexTrees();
  paintStyles();

  /**
   * A visitor's page is drawn while it is still arriving, and waiting for
   * DOMContentLoaded - which also waits for the site's deferred scripts -
   * put the old words on screen for a moment. Each element with new words
   * or a new picture gets them as soon as the parser has finished it, while
   * it is still the element the edit was made on. Everything else (and
   * anything this skipped) is done by start() as before.
   */
  var early = null;
  function parsedPast(el) {
    for (var c = el; c && c !== D.documentElement; c = c.parentNode) {
      if (c.nextSibling) return true;
    }
    return false;
  }
  function applyEarly() {
    if (!early || !D.body) return;
    Object.keys(doc.nodes).forEach(function (key) {
      var node = doc.nodes[key];
      if (early.done[key] || !node) return;
      if (node.text === undefined && node.label === undefined && node.placeholder === undefined && node.options === undefined && node.newTab === undefined && node.rich === undefined && !node.src && node.alt === undefined && node.href === undefined) return;
      var el = rawFind(key);
      if (!el || !parsedPast(el)) return;
      early.done[key] = true;
      if (node.f && fingerprintable(key) && fingerprintOf(el) !== node.f) return;
      applyContent(key, node, el);
    });
  }
  if (!EDIT && D.readyState === 'loading' && typeof MutationObserver !== 'undefined') {
    early = { done: {}, observer: new MutationObserver(applyEarly) };
    early.observer.observe(D.documentElement, { childList: true, subtree: true });
  }

  function start() {
    if (early) { early.observer.disconnect(); early = null; }
    resolveKeys();
    paintStyles();
    schedule();
    if (typeof MutationObserver !== 'undefined') {
      var observer = new MutationObserver(schedule);
      observer.observe(D.body, { childList: true, subtree: true, characterData: true });
      // The build sets the page title after it draws; a new page's own title
      // has to win that race.
      observer.observe(D.head, { childList: true, subtree: true, characterData: true });
    }
    if (EDIT) startEditor();
  }

  if (D.readyState === 'loading') D.addEventListener('DOMContentLoaded', start);
  else start();

  })();
