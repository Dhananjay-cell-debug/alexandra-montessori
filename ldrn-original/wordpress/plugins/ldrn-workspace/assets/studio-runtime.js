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
  var SCOPED = !!studio.scoped;
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
    var path = W.location.pathname.replace(/\.html$/, '').replace(/\/+$/, '') || '/';
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
    if (SCOPED && !(mainEl() && mainEl().contains(el)) && !(endingEl() && endingEl().contains(el))) return null;
    if (el.hasAttribute('data-ldrn-ws-label')) {
      var owner=keyOf(el.parentElement);
      return owner ? owner+'~label'+el.getAttribute('data-ldrn-ws-label') : null;
    }
    if (el.hasAttribute('data-ldrn-wsn')) return 'n:' + el.getAttribute('data-ldrn-wsn');
    if (!SCOPED && el.hasAttribute('data-ldrn-ws') && uniqueName(el)) return 'w:' + el.getAttribute('data-ldrn-ws');
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
      if (!SCOPED && parent.hasAttribute('data-ldrn-ws') && uniqueName(parent)) return 'w:' + parent.getAttribute('data-ldrn-ws') + '>' + path.join('.');
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
      var title = page.label + ' — Little Rainbows';
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

  /* =====================================================================
   * Everything below runs only inside the Site Editor's canvas.
   * ================================================================== */

  var mode = 'select';
  var hoverEl = null;
  var selectedKey = null;
  var ui = null;
  var boxes = {};

  function host(type, payload) {
    try {
      if (W.parent.__ldrnStudioHost) W.parent.__ldrnStudioHost(type, payload || {});
    } catch (e) { /* the editor went away */ }
  }

  function isOwnUi(el) { return ui && el && ui.contains(el); }

  /**
   * The other half of reduced motion. A media query cannot be told the
   * visitor asked for it, so the site's own `prefers-reduced-motion: reduce`
   * rules are copied out and applied unconditionally; and any CSS animation
   * still running is brought to rest - a one-off at its end, a loop (a logo
   * marquee, a glow) at its start. Checked again as the page adds things.
   */
  function reducedRules(rules, out) {
    Array.prototype.forEach.call(rules || [], function (rule) {
      if (rule.type === 4 && /prefers-reduced-motion\s*:\s*reduce/.test(rule.conditionText || (rule.media && rule.media.mediaText) || '')) {
        Array.prototype.forEach.call(rule.cssRules, function (r) { out.push(r.cssText); });
      } else if (rule.cssRules && rule.type !== 1) {
        reducedRules(rule.cssRules, out);
      }
    });
  }

  /**
   * The site's hover effects (a picture that zooms, a card that lifts) would
   * fire under the pointer while someone is selecting and dragging, and the
   * size on the canvas would not be the size being edited. In Select mode
   * every :hover rule is held back; Preview gets them all again. Done by
   * rewriting each selector with a zero-specificity condition, so nothing
   * about the cascade changes.
   */
  var quieted = typeof WeakSet !== 'undefined' ? new WeakSet() : null;
  function quietHover(rules) {
    Array.prototype.forEach.call(rules || [], function (rule) {
      if (rule.cssRules && rule.type !== 1) { quietHover(rule.cssRules); return; }
      if (rule.type !== 1 || !rule.selectorText || rule.selectorText.indexOf(':hover') === -1) return;
      if (quieted && quieted.has(rule)) return;
      if (quieted) quieted.add(rule);
      try {
        rule.selectorText = rule.selectorText.replace(/:hover/g, ':hover:where(html:not([data-ldrn-ws-mode="select"]) *)');
      } catch (e) { /* a selector the browser will not re-parse stays as it was */ }
    });
  }

  var settleEl = null;
  var settledSheets = -1;
  function settleCanvas() {
    if (D.styleSheets.length !== settledSheets) {
      settledSheets = D.styleSheets.length;
      var out = [];
      Array.prototype.forEach.call(D.styleSheets, function (sheet) {
        if (sheet.ownerNode && (sheet.ownerNode.id === 'ldrn-studio-settle' || sheet.ownerNode.id === 'ldrn-studio-css')) return;
        try { reducedRules(sheet.cssRules, out); quietHover(sheet.cssRules); } catch (e) { /* another origin's sheet */ }
      });
      if (!settleEl) {
        settleEl = D.createElement('style');
        settleEl.id = 'ldrn-studio-settle';
      }
      settleEl.textContent = out.join('\n');
      if (!settleEl.parentNode) D.head.insertBefore(settleEl, styleEl && styleEl.parentNode === D.head ? styleEl : null);
    }
    if (D.getAnimations) {
      D.getAnimations().forEach(function (a) {
        if (a.playState === 'finished' || a.playState === 'paused') return;
        var t = a.effect && a.effect.getComputedTiming ? a.effect.getComputedTiming() : null;
        try {
          if (t && t.iterations === Infinity) { a.pause(); a.currentTime = 0; }
          else a.finish();
        } catch (e) { try { a.pause(); } catch (e2) {} }
      });
    }
  }

  function startEditor() {
    settleCanvas();
    W.addEventListener('load', settleCanvas);
    W.setInterval(settleCanvas, 700);
    var css = D.createElement('style');
    css.id = 'ldrn-studio-editor-css';
    css.textContent =
      // The cookie bar, the sound button, film grain and the entry burn belong
      // to visitors; in the editor they only cover what is being edited. The
      // live site keeps all of them.
      '.cookie-bar,.cookie-panel,#sound-toggle,.film-grain,.film-burn,.section-flare,.skip-link{display:none!important}' +
      'html[data-ldrn-ws-mode="select"] a,html[data-ldrn-ws-mode="select"] button{cursor:default!important}' +
      // A transition (even 0.01ms) holds the old value until the next frame,
      // so a nudge or a selection box measured right after a change would
      // read where the layer was. Preview keeps the site's transitions.
      'html[data-ldrn-ws-mode="select"] *,html[data-ldrn-ws-mode="select"] *::before,html[data-ldrn-ws-mode="select"] *::after{transition:none!important}' +
      '[data-ldrn-ws-editing]{outline:none!important;cursor:text!important;caret-color:#0099ff}' +
      // An embed would swallow the click meant to select it.
      'iframe[data-ldrn-wsn]{pointer-events:none}' +
      'html[data-ldrn-ws-mode="select"]:not([data-ldrn-ws-probe]) body *:not(iframe):not(svg):not(svg *){pointer-events:auto!important}' +
      // A stretched link's ::before (laid over a whole card) must not swallow
      // clicks meant for the card's words and pictures. Pseudo-elements are not
      // layers: clicks pass through them to what is really there.
      'html[data-ldrn-ws-mode="select"]:not([data-ldrn-ws-probe]) body *::before,html[data-ldrn-ws-mode="select"]:not([data-ldrn-ws-probe]) body *::after{pointer-events:none!important}' +
      'html[data-ldrn-ws-mode="select"]:not([data-ldrn-ws-probe]) .end-reveal{pointer-events:auto!important}' +
      'html[data-ldrn-ws-mode="select"] .end-reveal__art{position:absolute!important;inset:0!important;z-index:0!important;height:100%!important}' +
      'html[data-ldrn-ws-mode="select"] .end-reveal:before{pointer-events:none!important}' +
      // A drawing (a line across a scene) is picked by its strokes, not its box.
      'html[data-ldrn-ws-mode="select"] body svg{pointer-events:none!important}' +
      'html[data-ldrn-ws-mode="select"] body svg *{pointer-events:visiblePainted!important}' +
      '#ldrn-studio-ui{position:fixed;inset:0;pointer-events:none;z-index:2147483647;font:500 11px/1 Inter,system-ui,sans-serif}' +
      '#ldrn-studio-ui .b{position:fixed;box-sizing:border-box;display:none}' +
      '#ldrn-studio-ui .hov{border:1px solid #0099ff}' +
      '#ldrn-studio-ui .sel{border:1.5px solid #0099ff}' +
      '#ldrn-studio-ui .tag{position:fixed;display:none;background:#0099ff;color:#fff;padding:3px 6px;border-radius:3px;white-space:nowrap;max-width:260px;overflow:hidden;text-overflow:ellipsis}' +
      '#ldrn-studio-ui .dim{position:fixed;display:none;background:#0099ff;color:#fff;padding:3px 6px;border-radius:3px;white-space:nowrap}' +
      '#ldrn-studio-ui .h{position:fixed;display:none;width:8px;height:8px;background:#fff;border:1.5px solid #0099ff;border-radius:1px;box-sizing:border-box;pointer-events:auto}' +
      // A handle is small to look at and generous to grab.
      '#ldrn-studio-ui .h::before{content:"";position:absolute;inset:-6px}' +
      '#ldrn-studio-ui .h[data-h=nw],#ldrn-studio-ui .h[data-h=se]{cursor:nwse-resize}' +
      '#ldrn-studio-ui .h[data-h=ne],#ldrn-studio-ui .h[data-h=sw]{cursor:nesw-resize}' +
      '#ldrn-studio-ui .h[data-h=n],#ldrn-studio-ui .h[data-h=s]{cursor:ns-resize}' +
      '#ldrn-studio-ui .h[data-h=e],#ldrn-studio-ui .h[data-h=w]{cursor:ew-resize}' +
      '#ldrn-studio-ui .mq{position:fixed;display:none;border:1px solid #0099ff;background:rgba(0,153,255,.08)}' +
      '#ldrn-studio-ui .xs{position:fixed;box-sizing:border-box;border:1px solid rgba(0,153,255,.75)}' +
      '#ldrn-studio-ui .hit{position:fixed;box-sizing:border-box;border:1px dashed #0099ff;background:rgba(0,153,255,.06)}' +
      '#ldrn-studio-ui .gl{position:fixed;background:#ff2d87}' +
      'html[data-ldrn-ws-over-sel] body,html[data-ldrn-ws-over-sel] body *{cursor:move!important}' +
      'html[data-ldrn-ws-dragging],html[data-ldrn-ws-dragging] *{user-select:none!important;-webkit-user-select:none!important}' +
      'html[data-ldrn-ws-dragging=move] *{cursor:grabbing!important}' +
      'html[data-ldrn-ws-dragging=marquee] *{cursor:crosshair!important}' +
      'html[data-ldrn-ws-hand],html[data-ldrn-ws-hand] *{cursor:grab!important}';
    D.head.appendChild(css);

    ui = D.createElement('div');
    ui.id = 'ldrn-studio-ui';
    ui.setAttribute('translate', 'no');
    ui.innerHTML = '<div class="b hov"></div><div class="b sel"></div><div class="tag"></div><div class="dim"></div>' +
      '<div class="xs-pool"></div><div class="hit-pool"></div><div class="gl-pool"></div><div class="mq"></div>' +
      HANDLES.map(function (d) { return '<div class="h" data-h="' + d + '"></div>'; }).join('');
    D.documentElement.appendChild(ui);
    boxes.hov = ui.querySelector('.hov');
    boxes.sel = ui.querySelector('.sel');
    boxes.tag = ui.querySelector('.tag');
    boxes.dim = ui.querySelector('.dim');
    boxes.h = ui.querySelectorAll('.h');
    boxes.mq = ui.querySelector('.mq');
    boxes.xs = ui.querySelector('.xs-pool');
    boxes.hits = ui.querySelector('.hit-pool');
    boxes.gl = ui.querySelector('.gl-pool');

    setMode('select');

    // Ctrl+wheel zooms the canvas (not the browser); a sideways wheel or
    // Shift+wheel pans it; Space+drag is the hand tool.
    W.addEventListener('wheel', function (e) {
      if (e.ctrlKey || e.metaKey) {
        e.preventDefault();
        host('zoom', { dy: e.deltaY, x: e.clientX, y: e.clientY });
      } else if (e.shiftKey || Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
        host('pan', { dx: e.shiftKey ? e.deltaY : e.deltaX });
      }
    }, { passive: false, capture: true });
    W.addEventListener('keydown', function (e) {
      if (e.key === ' ' && !editingEl && !/^(INPUT|TEXTAREA|SELECT)$/.test((e.target && e.target.tagName) || '') && !(e.target && e.target.isContentEditable)) {
        e.preventDefault();
        if (!spaceDown) { spaceDown = true; D.documentElement.setAttribute('data-ldrn-ws-hand', ''); }
      }
    }, true);
    W.addEventListener('keyup', function (e) {
      if (e.key === ' ') { spaceDown = false; D.documentElement.removeAttribute('data-ldrn-ws-hand'); }
    }, true);
    W.addEventListener('blur', function () { spaceDown = false; D.documentElement.removeAttribute('data-ldrn-ws-hand'); });
    W.addEventListener('pointerdown', onPointerDown, true);
    W.addEventListener('pointermove', onPointerDrag, true);
    W.addEventListener('pointerup', onPointerUp, true);
    W.addEventListener('pointercancel', onPointerUp, true);
    ['pointerdown', 'mousedown', 'pointerup', 'mouseup', 'click', 'auxclick', 'submit', 'touchstart'].forEach(function (type) {
      W.addEventListener(type, guard, true);
    });
    W.addEventListener('dblclick', onDouble, true);
    D.addEventListener('pointermove', onMove, true);
    D.addEventListener('pointerleave', function () { hoverEl = null; refreshBoxes(); }, true);
    W.addEventListener('scroll', refreshBoxes, true);
    W.addEventListener('resize', refreshBoxes);
    W.addEventListener('keydown', onKey, true);

    (function loop() {
      refreshBoxes();
      W.requestAnimationFrame(loop);
    })();

    host('ready', { route: route });
  }

  function setMode(next) {
    mode = next === 'preview' ? 'preview' : 'select';
    D.documentElement.setAttribute('data-ldrn-ws-mode', mode);
    if (mode === 'preview') { hoverEl = null; stopEditing(true); }
    refreshBoxes();
  }

  /**
   * A layer that only decorates what is under it: laid absolutely over the
   * page, with no words and no picture in it - a shade, a glow, a gradient,
   * a grain. The pointer passes through it to what it covers; it can still
   * be picked from the Layers list. Anything added in the editor never is.
   */
  function isDecor(el) {
    if (!el || el.nodeType !== 1 || el.hasAttribute('data-ldrn-wsn') || isMain(el) || isTop(el)) return false;
    if (!/absolute|fixed/.test(W.getComputedStyle(el).position)) return false;
    if ((el.textContent || '').trim()) return false;
    if (/^(IMG|VIDEO|CANVAS|PICTURE|IFRAME|svg|SVG|INPUT|TEXTAREA|SELECT|BUTTON|A)$/.test(el.tagName)) return false;
    return !el.querySelector('img,video,canvas,picture,iframe,svg,input,textarea,select,button,a');
  }

  function usable(el, top) {
    return !!el && el.nodeType === 1 && !isOwnUi(el) && top && top.contains(el) && !isDecor(el);
  }

  /** The element a visitor's click at this point would reach (see targetFrom). */
  function visitorAt(x, y, top, ending) {
    var root = D.documentElement;
    root.setAttribute('data-ldrn-ws-probe', '');
    try {
      var stack = D.elementsFromPoint(x, y);
      for (var i = 0; i < stack.length; i++) if (usable(stack[i], top) || usable(stack[i], ending)) return stack[i];
      return null;
    } finally {
      root.removeAttribute('data-ldrn-ws-probe');
    }
  }

  /** Does the site let a visitor's click pass through this element? */
  function clickThrough(el) {
    var root = D.documentElement;
    root.setAttribute('data-ldrn-ws-probe', '');
    try { return W.getComputedStyle(el).pointerEvents === 'none'; } finally { root.removeAttribute('data-ldrn-ws-probe'); }
  }

  function targetFrom(event) {
    var generated=event.target.closest && event.target.closest('[data-ldrn-link-for]');
    if(generated)return find(generated.getAttribute('data-ldrn-link-for'));
    var el = event.target;
    if (!el || el.nodeType !== 1) el = el && el.parentElement;
    if (!el || isOwnUi(el)) return null;
    var top = topEl();
    var ending = endingEl();
    if (!top) return null;
    // Something laid over the page from outside it (a full-screen grain or
    // glow canvas) or a decorative layer: look under it.
    if ((!top.contains(el) && !(ending && ending.contains(el)) || isDecor(el)) && D.elementsFromPoint && event.clientX !== undefined) {
      var stack = D.elementsFromPoint(event.clientX, event.clientY);
      el = null;
      for (var i = 0; i < stack.length; i++) if (usable(stack[i], top) || usable(stack[i], ending)) { el = stack[i]; break; }
      if (!el) return null;
    }
    // A layer the site made click-through (a transparent picture laid over
    // words) gives way to the text or picture a visitor's click would reach
    // under it. Nothing there? Then the layer itself - Alt+click and the
    // Layers panel reach every layer either way.
    if (event.clientX !== undefined && D.elementsFromPoint && clickThrough(el)) {
      var reached = visitorAt(event.clientX, event.clientY, top, ending);
      var rb = reached && reached.getBoundingClientRect();
      // Only something whose own box is under the pointer - not a link reached through
      // a ::before stretched over a whole card.
      if (reached && reached !== el && !reached.contains(el) && rb && event.clientX >= rb.left && event.clientX <= rb.right && event.clientY >= rb.top && event.clientY <= rb.bottom && (isTextish(reached) || /^(IMG|VIDEO|PICTURE|CANVAS)$/.test(reached.tagName))) el = reached;
    }
    if (!top.contains(el) && !(ending && ending.contains(el))) return null;
    if (el.tagName === 'svg' || el.closest('svg')) el = el.closest('svg');
    return el;
  }

  /**
   * Alt+click: the next layer down under the pointer - below the one
   * selected now, not one of its parents.
   */
  function deeperAt(x, y) {
    var top = topEl();
    var ending = endingEl();
    var current = selectedKey ? find(selectedKey) : null;
    if (!top || !D.elementsFromPoint) return null;
    var stack = D.elementsFromPoint(x, y).filter(function (e) { return (usable(e, top) || usable(e, ending)) && !isMain(e) && !isTop(e); });
    var at = current ? stack.indexOf(current) : -1;
    for (var i = at + 1; i < stack.length; i++) {
      if (current && stack[i].contains(current)) continue;
      return stack[i];
    }
    return stack[0] || null;
  }

  /**
   * Preview: a link to another INU page would load it inside the canvas
   * without the editor's flag. Hand it to the editor instead, which opens
   * that page in edit mode. Links within the page (#services) and links
   * out of the site behave as they do for a visitor.
   */
  function followInPreview(event) {
    if (mode !== 'preview' || event.defaultPrevented || event.button) return;
    var a = event.target && event.target.closest ? event.target.closest('a[href]') : null;
    if (!a || a.target === '_blank') return;
    var url;
    try { url = new URL(a.getAttribute('href'), W.location.href); } catch (e) { return; }
    if (url.origin !== W.location.origin || /^\/wp-/.test(url.pathname) || /\.[a-z0-9]{2,5}$/i.test(url.pathname)) return;
    var next = ALIASES[url.pathname.replace(/\/+$/, '') || '/'] || url.pathname.replace(/\/+$/, '') || '/';
    if (next === route) return;
    event.preventDefault();
    event.stopPropagation();
    host('navigate', { route: next });
  }

  function guard(event) {
    if (event.type === 'click') followInPreview(event);
    if (mode !== 'select') return;
    if (editingEl && (editingEl.contains(event.target) || event.target===editingLabel)) return;
    if (isOwnUi(event.target) || (event.type === 'click' && suppressClick)) {
      suppressClick = false;
      event.preventDefault();
      event.stopPropagation();
      return;
    }
    var el = targetFrom(event);
    event.preventDefault();
    event.stopPropagation();
    if (event.type !== 'click') return;
    if (editingEl) stopEditing(false);
    if (!el) { select(null); host('select', { key: null }); return; }
    if (event.altKey) {
      var deeper = deeperAt(event.clientX, event.clientY);
      if (deeper) { lastClick = null; select(keyOf(deeper), 'canvas'); }
      return;
    }
    if (event.shiftKey) {
      // Shift+click adds a layer to the selection, or takes it back out.
      var k = keyOf(el);
      var keys = selectedKeys();
      var at = keys.indexOf(k);
      if (at === -1) keys.push(k); else keys.splice(at, 1);
      setSelection(keys, at === -1 ? k : keys[keys.length - 1], true);
      return;
    }
    // Double-click, recognised here: cancelling pointerdown (which keeps
    // the site's own handlers out of the way) also stops some browsers from
    // counting clicks, so the native dblclick cannot be relied on.
    var now = Date.now();
    if (lastClick && lastClick.el === el && now - lastClick.at < 450 &&
        Math.abs(lastClick.x - event.clientX) < 6 && Math.abs(lastClick.y - event.clientY) < 6) {
      lastClick = null;
      handleDouble(el);
      return;
    }
    lastClick = { el: el, at: now, x: event.clientX, y: event.clientY };
    select(keyOf(el), 'canvas');
  }

  var lastClick = null;
  var lastDoubleAt = 0;

  function handleDouble(el) {
    lastDoubleAt = Date.now();
    var key = keyOf(el);
    if (!isTextish(el)) {
      if(ownText(el))startEditingLabel(el,key);
      else select(key,'canvas');
      return;
    }
    startEditing(el, key);
  }

  function onMove(event) {
    if (mode !== 'select' || drag) return;
    var el = targetFrom(event);
    var overSel = !!el && !editingEl && selectedEls().some(function (s) { return s === el || s.contains(el); });
    if (overSel !== D.documentElement.hasAttribute('data-ldrn-ws-over-sel')) {
      if (overSel) D.documentElement.setAttribute('data-ldrn-ws-over-sel', '');
      else D.documentElement.removeAttribute('data-ldrn-ws-over-sel');
    }
    if (el !== hoverEl) {
      hoverEl = el;
      host('hover', { key: el ? keyOf(el) : null });
    }
  }

  function onKey(event) {
    if (editingEl) {
      if (event.key === 'Escape') { event.preventDefault(); stopEditing(true); }
      else if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); stopEditing(false); }
      return;
    }
    if (drag && event.key === 'Escape') { event.preventDefault(); cancelDrag(); return; }
    // Everything else is the editor's shortcut to handle, not the site's.
    var t = event.target;
    if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) return;
    if (mode === 'select' && selectedKey && !event.altKey && !event.metaKey && !event.ctrlKey && /^Arrow(Up|Down|Left|Right)$/.test(event.key)) {
      event.preventDefault();
      var step = event.shiftKey ? 10 : 1;
      nudge(event.key === 'ArrowLeft' ? -step : event.key === 'ArrowRight' ? step : 0,
        event.key === 'ArrowUp' ? -step : event.key === 'ArrowDown' ? step : 0);
      return;
    }
    host('key', { key: event.key, meta: event.metaKey || event.ctrlKey, shift: event.shiftKey, alt: event.altKey });
    if (mode === 'select' && /^(Escape|Delete|Backspace|z|Z|y)$/.test(event.key)) event.preventDefault();
    // Ctrl+D is Duplicate here, not "bookmark this page"; Ctrl+S is save.
    if ((event.metaKey || event.ctrlKey) && /^[dDsS]$/.test(event.key)) event.preventDefault();
  }

  /* ------------------------------------------------------- inline typing */

  var editingBefore = '';
  var editingRich = null;
  var editingKey = null;
  var editingLabel = null;
  function ownText(el) {
    return Array.prototype.filter.call(el.childNodes,function(n){return n.nodeType===3 && n.nodeValue.trim();}).map(function(n){return n.nodeValue;}).join('').trim();
  }
  function startEditingLabel(el,key) {
    stopEditing(false);select(key,'canvas');
    editingEl=el;editingKey=key;editingBefore=ownText(el);
    var r=el.getBoundingClientRect(),cs=W.getComputedStyle(el);
    editingLabel=D.createElement('textarea');
    editingLabel.setAttribute('aria-label','Edit button or label text');
    editingLabel.value=editingBefore;
    editingLabel.style.cssText='position:fixed;z-index:2147483647;pointer-events:auto;resize:none;box-sizing:border-box;border:2px solid #0099ff;border-radius:8px;padding:8px;background:white;color:#213472;';
    editingLabel.style.left=r.left+'px';editingLabel.style.top=r.top+'px';
    editingLabel.style.width=Math.max(r.width,160)+'px';editingLabel.style.height=Math.max(r.height,48)+'px';
    editingLabel.style.fontSize=cs.fontSize;editingLabel.style.fontFamily=cs.fontFamily;editingLabel.style.fontWeight=cs.fontWeight;
    ui.appendChild(editingLabel);editingLabel.focus();editingLabel.select();
    editingLabel.addEventListener('blur',onEditBlur);
    host('editing',{key:key});
  }

  function onDouble(event) {
    if (mode !== 'select') return;
    event.preventDefault();
    event.stopPropagation();
    // Already handled from the second click.
    if (Date.now() - lastDoubleAt < 500 || editingEl) return;
    var el = targetFrom(event);
    if (!el) return;
    lastClick = null;
    handleDouble(el);
  }

  function startEditing(el, key) {
    stopEditing(false);
    select(key, 'canvas');
    // Typing changes the page before the document hears about it, so the
    // words as designed are remembered now, for Undo.
    var memory = memoryOf(el, key);
    if (!memory.text) memory.text = Array.prototype.map.call(el.childNodes, function (n) { return n.cloneNode(true); });
    editingEl = el;
    editingKey = key;
    editingBefore = tidy(readText(el));
    // A heading with a styled word in it is saved as runs, so the word keeps
    // its styling; plain text stays plain text.
    editingRich = hasRich(el) ? readRich(el) : null;
    // The translator skips anything marked translate="no" - without it, every
    // clock tick would put the old words back mid-sentence.
    el.setAttribute('translate', 'no');
    el.setAttribute('data-ldrn-ws-editing', '');
    el.setAttribute('contenteditable', 'plaintext-only');
    if (el.contentEditable !== 'plaintext-only') el.setAttribute('contenteditable', 'true');
    el.focus();
    try {
      var range = D.createRange();
      range.selectNodeContents(el);
      var sel = W.getSelection();
      sel.removeAllRanges();
      sel.addRange(range);
    } catch (e) {}
    el.addEventListener('blur', onEditBlur);
    host('editing', { key: key });
  }

  function onEditBlur() { stopEditing(false); }

  function stopEditing(cancel) {
    if (!editingEl) return;
    var el = editingEl;
    var key = editingKey;
    editingEl = null;
    editingKey = null;
    if(editingLabel) {
      var label=editingLabel.value;
      editingLabel.removeEventListener('blur',onEditBlur);editingLabel.remove();editingLabel=null;
      if(!cancel && label!==editingBefore)host('label',{key:key,text:label});
      schedule();refreshBoxes();
      return;
    }
    el.removeEventListener('blur', onEditBlur);
    el.removeAttribute('contenteditable');
    el.removeAttribute('data-ldrn-ws-editing');
    el.removeAttribute('translate');
    var text = tidy(el.innerText != null ? el.innerText : readText(el));
    if (editingRich) {
      var before = editingRich;
      editingRich = null;
      if (cancel) {
        while (el.firstChild) el.removeChild(el.firstChild);
        buildRich(before, el);
      } else {
        var runs = readRich(el);
        if (JSON.stringify(runs) !== JSON.stringify(before)) host('rich', { key: key, rich: runs, text: text });
      }
    } else if (cancel) {
      writeText(el, editingBefore);
    } else {
      writeText(el, text);
      if (text !== editingBefore) host('text', { key: key, text: text });
    }
    try { W.getSelection().removeAllRanges(); } catch (e) {}
    host('editing', { key: null });
  }

  /* ------------------------------------------------------------- boxes */

  function place(box, r) {
    if (!r) { box.style.display = 'none'; return; }
    box.style.display = 'block';
    box.style.left = r.left + 'px';
    box.style.top = r.top + 'px';
    box.style.width = r.width + 'px';
    box.style.height = r.height + 'px';
  }

  function rectOf(el) {
    if (!el || !el.isConnected) return null;
    var r = el.getBoundingClientRect();
    if (!r.width && !r.height) return null;
    return r;
  }

  function syncPool(pool, cls, rects) {
    while (pool.children.length < rects.length) {
      var b = D.createElement('div');
      b.className = cls;
      pool.appendChild(b);
    }
    for (var i = 0; i < pool.children.length; i++) place(pool.children[i], rects[i] || null);
  }

  function refreshBoxes() {
    if (!ui) return;
    var on = mode === 'select';
    var els = on ? selectedKeys().map(find).filter(function (el) { return !!rectOf(el); }) : [];
    var primary = on && selectedKey ? find(selectedKey) : null;
    var many = els.length > 1;
    var hov = on && !drag && hoverEl && els.indexOf(hoverEl) === -1 ? rectOf(hoverEl) : null;
    place(boxes.hov, hov);
    syncPool(boxes.xs, 'xs', many ? els.map(rectOf) : []);
    var marquee = drag && drag.type === 'marquee' ? drag : null;
    syncPool(boxes.hits, 'hit', marquee && marquee.hits ? marquee.hits.map(rectOf) : []);
    place(boxes.mq, marquee && marquee.rect ? {
      left: marquee.rect.left - W.scrollX, top: marquee.rect.top - W.scrollY,
      width: marquee.rect.right - marquee.rect.left, height: marquee.rect.bottom - marquee.rect.top,
    } : null);
    // Snapping guides: full-length hairlines where the box lined up.
    syncPool(boxes.gl, 'gl', drag && drag.guides ? drag.guides.map(function (g) {
      return g.x !== undefined
        ? { left: g.x - W.scrollX, top: 0, width: 1, height: W.innerHeight }
        : { left: 0, top: g.y - W.scrollY, width: W.innerWidth, height: 1 };
    }) : []);
    var r = many ? unionRect(els) : rectOf(primary);
    place(boxes.sel, r);
    var i;
    if (!r) {
      boxes.tag.style.display = boxes.dim.style.display = 'none';
      for (i = 0; i < boxes.h.length; i++) boxes.h[i].style.display = 'none';
      return;
    }
    var name = many ? els.length + ' layers' : nameOf(primary);
    if (boxes.tag.textContent !== name) boxes.tag.textContent = name;
    boxes.tag.style.display = 'block';
    boxes.tag.style.left = r.left + 'px';
    boxes.tag.style.top = Math.max(0, r.top - 20) + 'px';
    var dim = drag && drag.dim ? drag.dim : Math.round(r.width) + ' × ' + Math.round(r.height);
    if (boxes.dim.textContent !== dim) boxes.dim.textContent = dim;
    boxes.dim.style.display = 'block';
    boxes.dim.style.left = (r.left + r.width / 2 - boxes.dim.offsetWidth / 2) + 'px';
    boxes.dim.style.top = Math.min(W.innerHeight - 20, r.bottom + 6) + 'px';
    // Handles only where something can actually be resized, and the edge
    // handles only once the box is big enough to tell them apart.
    var canResize = !editingEl && els.some(movable);
    var at = {
      nw: [r.left, r.top], n: [r.left + r.width / 2, r.top], ne: [r.right, r.top], e: [r.right, r.top + r.height / 2],
      se: [r.right, r.bottom], s: [r.left + r.width / 2, r.bottom], sw: [r.left, r.bottom], w: [r.left, r.top + r.height / 2],
    };
    for (i = 0; i < boxes.h.length; i++) {
      var d = boxes.h[i].getAttribute('data-h');
      var edgeOk = d.length === 2 || ((d === 'n' || d === 's') ? r.width >= 36 : r.height >= 36);
      boxes.h[i].style.display = canResize && edgeOk ? 'block' : 'none';
      boxes.h[i].style.left = (at[d][0] - 4) + 'px';
      boxes.h[i].style.top = (at[d][1] - 4) + 'px';
    }
  }

  /* ------------------------------------------------------------ geometry
   *
   * Framer / Canva direct manipulation, on a real website:
   *
   *   drag a selected layer        moves it (and everything selected with it),
   *                                snapping to its neighbours and its parent
   *                                (hold Alt to place it freely)
   *   drag one of the 8 handles    resizes it:
   *                                  text     corners and top/bottom scale the
   *                                           type; left/right re-wrap it
   *                                  picture  corners scale it, sides crop it
   *                                  frame    changes its size
   *                                Shift swaps proportional and free
   *   drag anywhere else           draws a selection box; every layer wholly
   *                                inside it is selected (Ctrl+drag always)
   *   Shift + click                adds a layer to the selection, or removes it
   *   arrow keys                   nudge 1px, Shift + arrow 10px
   *   near the top or bottom edge  the canvas scrolls with the drag
   *
   * A layer is handled as a visual unit: a picture is usually wrapped in one
   * or two elements exactly its size, and resizing only one of them would
   * leave the picture clipped inside a box that did not grow. The unit's
   * outer box takes the new size; the pieces inside follow at 100%.
   *
   * While the pointer is down the change is previewed with inline styles.
   * On release it goes to the editor as ordinary style edits for the
   * breakpoint being edited - one undo step - and the preview is cleared.
   *
   * How a move is written depends on how the layer is placed:
   *   in the flow          position: relative + left/top, so nothing around
   *                        it moves
   *   relative             its left/top offsets change
   *   absolute / fixed     its left/top/right/bottom change
   *   sticky, or in the flow with absolutely placed pieces inside that are
   *   positioned against something outside it   compensating margins
   * ------------------------------------------------------------------- */

  var extraKeys = [];       // selected besides the primary one
  var drag = null;          // the gesture in progress
  var suppressClick = false;
  var HANDLES = ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'];
  var GEO_PROPS = ['position', 'left', 'top', 'right', 'bottom', 'width', 'height', 'max-width', 'min-width',
    'max-height', 'min-height', 'flex', 'display', 'margin-left', 'margin-right', 'margin-top', 'margin-bottom',
    'font-size', 'line-height', 'letter-spacing', 'object-fit'];
  var SNAP = 6;

  function px(v) { var n = parseFloat(v); return isFinite(n) ? n : 0; }
  function r1(n) { return Math.round(n) + 'px'; }
  function rf(n) { return Math.round(n * 10) / 10 + 'px'; }

  function selectedKeys() {
    var keys = selectedKey ? [selectedKey] : [];
    extraKeys.forEach(function (k) { if (keys.indexOf(k) === -1) keys.push(k); });
    return keys;
  }

  function movable(el) {
    return !!el && el.isConnected && !isMain(el) && !isTop(el) && !!rectOf(el);
  }

  /** The selection as elements, outermost only - a child moves with its parent. */
  function selectedEls() {
    var els = selectedKeys().map(find).filter(movable);
    return els.filter(function (el) {
      return !els.some(function (other) { return other !== el && other.contains(el); });
    });
  }

  function unionOf(rects) {
    var box = null;
    rects.forEach(function (r) {
      if (!r) return;
      var right = r.right !== undefined ? r.right : r.left + r.width;
      var bottom = r.bottom !== undefined ? r.bottom : r.top + r.height;
      if (!box) box = { left: r.left, top: r.top, right: right, bottom: bottom };
      else {
        box.left = Math.min(box.left, r.left); box.top = Math.min(box.top, r.top);
        box.right = Math.max(box.right, right); box.bottom = Math.max(box.bottom, bottom);
      }
    });
    if (box) { box.width = box.right - box.left; box.height = box.bottom - box.top; }
    return box;
  }

  function unionRect(els) { return unionOf(els.map(rectOf)); }

  function sameBox(a, b) {
    var ra = a.getBoundingClientRect();
    var rb = b.getBoundingClientRect();
    return Math.abs(ra.left - rb.left) <= 6 && Math.abs(ra.top - rb.top) <= 6 &&
      Math.abs(ra.right - rb.right) <= 6 && Math.abs(ra.bottom - rb.bottom) <= 6;
  }

  /**
   * The visual unit a layer belongs to, outermost first: the wrappers around
   * it that are exactly its size and hold nothing else, then the same inward
   * down to the picture or the words.
   */
  function chainOf(el) {
    var up = [];
    var c = el;
    while (c.parentElement) {
      var p = c.parentElement;
      if (isMain(p) || isTop(p) || p === D.body || !keyOf(p)) break;
      // The only thing in its wrapper that takes up room - overlays laid
      // absolutely over it (a shade, a gradient) follow the wrapper anyway.
      var inFlow = layerKids(p).filter(function (k) { return k === c || !/absolute|fixed/.test(W.getComputedStyle(k).position); });
      if (inFlow.length !== 1 || inFlow[0] !== c || !sameBox(p, c)) break;
      up.unshift(p);
      c = p;
    }
    var down = [el];
    var d = el;
    for (var i = 0; i < 6; i++) {
      if (/^(IMG|VIDEO|CANVAS|IFRAME)$/.test(d.tagName) || d.tagName.toLowerCase() === 'svg' || isTextish(d)) break;
      var kids = d.tagName === 'PICTURE'
        ? Array.prototype.filter.call(d.children, function (k) { return k.tagName === 'IMG'; })
        : layerKids(d);
      if (kids.length !== 1 || !sameBox(kids[0], d)) break;
      d = kids[0];
      down.push(d);
    }
    return up.concat(down);
  }

  function kindOf(chain) {
    var inner = chain[chain.length - 1];
    if (/^(IMG|VIDEO|CANVAS|PICTURE)$/.test(inner.tagName) || inner.tagName.toLowerCase() === 'svg') return 'image';
    return isTextish(inner) ? 'text' : 'box';
  }

  /**
   * How many screen pixels one CSS pixel is where this element is placed -
   * the product of every scale() above it. Read from the transform matrices,
   * so a rotated or skewed element (INU's brush words lean) is not mistaken
   * for a scaled one.
   */
  function scaleOfTransform(el) {
    var t = W.getComputedStyle(el).transform;
    if (!t || t === 'none') return 1;
    var m = t.match(/matrix(3d)?\(([^)]+)\)/);
    if (!m) return 1;
    var v = m[2].split(',').map(parseFloat);
    var a = v[0], b = v[1];
    var s = Math.sqrt(a * a + b * b);
    return isFinite(s) && s > 0 ? s : 1;
  }

  function unitOf(el, withSelf) {
    var u = withSelf ? scaleOfTransform(el) : 1;
    for (var a = el.parentElement; a && a !== D.documentElement; a = a.parentElement) u *= scaleOfTransform(a);
    return u;
  }

  /** Does making this element a containing block move something inside it? */
  function hasEscapingChildren(el) {
    var list = el.querySelectorAll('*');
    for (var i = 0; i < list.length && i < 400; i++) {
      var p = W.getComputedStyle(list[i]).position;
      if (p === 'fixed') return true;
      if (p === 'absolute' && list[i].offsetParent && !el.contains(list[i].offsetParent)) return true;
    }
    return false;
  }

  /** Everything the geometry needs to know about one element, read once. */
  function measure(el) {
    var cs = W.getComputedStyle(el);
    var parent = el.parentElement ? W.getComputedStyle(el.parentElement) : null;
    var pos = cs.position;
    var how = pos === 'absolute' || pos === 'fixed' ? 'abs'
      : pos === 'relative' ? 'rel'
      : pos === 'sticky' || hasEscapingChildren(el) ? 'margin' : 'static';
    var left = cs.left === 'auto' ? (cs.right === 'auto' ? 0 : -px(cs.right)) : px(cs.left);
    var top = cs.top === 'auto' ? (cs.bottom === 'auto' ? 0 : -px(cs.bottom)) : px(cs.top);
    var r = el.getBoundingClientRect();
    var unit = unitOf(el);
    // Sizes are the layout's own (times any scale above), not the bounding
    // box, which a rotated element inflates.
    var own = unitOf(el, true);
    var w = el.offsetWidth ? el.offsetWidth * own : r.width;
    var h = el.offsetHeight ? el.offsetHeight * own : r.height;
    return {
      el: el, key: keyOf(el), how: how, unit: unit, own: own,
      rect: { left: r.left + W.scrollX + (r.width - w) / 2, top: r.top + W.scrollY + (r.height - h) / 2, width: w, height: h },
      left: left, top: top, right: px(cs.right), bottom: px(cs.bottom),
      absLeft: px(cs.left), absTop: px(cs.top),
      ml: px(cs.marginLeft), mr: px(cs.marginRight), mt: px(cs.marginTop), mb: px(cs.marginBottom),
      maxW: cs.maxWidth, minW: px(cs.minWidth), maxH: cs.maxHeight, minH: px(cs.minHeight),
      inline: cs.display === 'inline',
      flexChild: !!parent && /flex/.test(parent.display),
    };
  }

  /** The words' own sizes: the element and every piece inside with a size of its own. */
  function textParts(el) {
    var out = [];
    (function walk(e, parentSize) {
      var cs = W.getComputedStyle(e);
      var fs = px(cs.fontSize);
      if (e === el || Math.abs(fs - parentSize) > 0.4) {
        var k = keyOf(e);
        if (k) {
          out.push({
            el: e, key: k, fs: fs,
            lh: cs.lineHeight === 'normal' ? 0 : px(cs.lineHeight),
            ls: cs.letterSpacing === 'normal' ? 0 : px(cs.letterSpacing),
          });
        }
      }
      Array.prototype.forEach.call(e.children, function (c) { if (!SKIP.test(c.tagName)) walk(c, fs); });
    })(el, -1);
    return out;
  }

  function moveStyles(m, dx, dy) {
    var s = {};
    dx /= m.unit || 1;
    dy /= m.unit || 1;
    if (!dx && !dy) return s;
    if (m.how === 'abs') {
      s.left = r1(m.absLeft + dx); s.right = 'auto';
      s.top = r1(m.absTop + dy); s.bottom = 'auto';
    } else if (m.how === 'margin') {
      s['margin-left'] = r1(m.ml + dx); s['margin-right'] = r1(m.mr - dx);
      s['margin-top'] = r1(m.mt + dy); s['margin-bottom'] = r1(m.mb - dy);
    } else {
      if (m.how === 'static') s.position = 'relative';
      s.left = r1(m.left + dx); s.top = r1(m.top + dy);
      s.right = 'auto'; s.bottom = 'auto';
    }
    return s;
  }

  /** Width and height in the element's own pixels, from screen pixels. */
  function sizeStyles(m, w, h) {
    var s = {};
    var u = m.own || m.unit || 1;
    if (w !== null) {
      w /= u;
      s.width = r1(w);
      if (m.maxW !== 'none' && px(m.maxW) < w) s['max-width'] = 'none';
      if (m.minW > w) s['min-width'] = '0px';
    }
    if (h !== null) {
      h /= u;
      s.height = r1(h);
      if (m.maxH !== 'none' && px(m.maxH) < h) s['max-height'] = 'none';
      if (m.minH > h) s['min-height'] = '0px';
    }
    if (m.inline) s.display = 'inline-block';
    // A flex item otherwise grows or shrinks away from the size it was given.
    if (m.flexChild) s.flex = 'none';
    return s;
  }

  function merged(a, b) {
    var out = {};
    Object.keys(a).forEach(function (k) { out[k] = a[k]; });
    Object.keys(b).forEach(function (k) { out[k] = b[k]; });
    return out;
  }

  /* ------------------------------------------ previews and committing */

  /** Show a change on one element without committing it. */
  function preview(e, styles) {
    e.styles = styles;
    if (!e.saved) {
      e.saved = {};
      GEO_PROPS.forEach(function (p) { e.saved[p] = [e.el.style.getPropertyValue(p), e.el.style.getPropertyPriority(p)]; });
    }
    GEO_PROPS.forEach(function (p) {
      if (styles[p] !== undefined) e.el.style.setProperty(p, styles[p], 'important');
      else if (e.saved[p][0]) e.el.style.setProperty(p, e.saved[p][0], e.saved[p][1]);
      else e.el.style.removeProperty(p);
    });
  }

  function unpreview(e) {
    if (!e.saved) return;
    GEO_PROPS.forEach(function (p) {
      if (e.saved[p][0]) e.el.style.setProperty(p, e.saved[p][0], e.saved[p][1]);
      else e.el.style.removeProperty(p);
    });
    e.saved = null;
  }

  function commit(entries, group) {
    var byKey = {};
    var order = [];
    entries.forEach(function (e) {
      if (!e.key || !e.styles || !Object.keys(e.styles).length) return;
      if (!byKey[e.key]) { byKey[e.key] = {}; order.push(e.key); }
      Object.keys(e.styles).forEach(function (p) { byKey[e.key][p] = e.styles[p]; });
    });
    // The editor writes them into the document and repaints the stylesheet
    // synchronously, so clearing the preview afterwards never flickers.
    if (order.length) host('geometry', { changes: order.map(function (k) { return { key: k, s: byKey[k] }; }), group: group || null });
    entries.forEach(unpreview);
  }

  /* ------------------------------------------------------- snapping */

  /** Lines a moving box can snap to: its neighbours' and its parent's edges and centres. */
  function snapLines(units) {
    var xs = [];
    var ys = [];
    var sx = W.scrollX, sy = W.scrollY;
    var seen = [];
    units.forEach(function (u) {
      var parent = u.parentElement;
      if (!parent || seen.indexOf(parent) !== -1) return;
      seen.push(parent);
      var pr = parent.getBoundingClientRect();
      var pcs = W.getComputedStyle(parent);
      var cl = pr.left + px(pcs.paddingLeft) + px(pcs.borderLeftWidth);
      var cr = pr.right - px(pcs.paddingRight) - px(pcs.borderRightWidth);
      xs.push(pr.left + sx, cl + sx, (pr.left + pr.right) / 2 + sx, cr + sx, pr.right + sx);
      ys.push(pr.top + sy, (pr.top + pr.bottom) / 2 + sy, pr.bottom + sy);
      layerKids(parent).forEach(function (k) {
        if (units.some(function (x) { return x === k || x.contains(k) || k.contains(x); })) return;
        var r = rectOf(k);
        if (!r) return;
        xs.push(r.left + sx, (r.left + r.right) / 2 + sx, r.right + sx);
        ys.push(r.top + sy, (r.top + r.bottom) / 2 + sy, r.bottom + sy);
      });
    });
    return { xs: xs, ys: ys };
  }

  /** The nearest line to any of `values`, within reach: [shift, line] or null. */
  function nearest(values, lines) {
    var best = null;
    values.forEach(function (v) {
      lines.forEach(function (l) {
        var d = l - v;
        if (Math.abs(d) <= SNAP && (!best || Math.abs(d) < Math.abs(best[0]))) best = [d, l];
      });
    });
    return best;
  }

  /* ------------------------------------------------------ the gestures */

  function pagePoint(event) { return { x: event.clientX + W.scrollX, y: event.clientY + W.scrollY }; }

  /** Where the dragged handles and the pointer put the box. */
  function resizedBox(box, dir, dx, dy, keep) {
    var l = box.left, t = box.top, r = box.left + box.width, b = box.top + box.height;
    if (dir.indexOf('w') !== -1) l += dx;
    if (dir.indexOf('e') !== -1) r += dx;
    if (dir.indexOf('n') !== -1) t += dy;
    if (dir.indexOf('s') !== -1) b += dy;
    var MIN = 8;
    if (r - l < MIN) { if (dir.indexOf('w') !== -1) l = r - MIN; else r = l + MIN; }
    if (b - t < MIN) { if (dir.indexOf('n') !== -1) t = b - MIN; else b = t + MIN; }
    if (keep && dir.length === 2 && box.width && box.height) {
      var ratio = box.width / box.height;
      var w = r - l, h = b - t;
      if (w / h > ratio) h = w / ratio; else w = h * ratio;
      if (dir.indexOf('w') !== -1) l = r - w; else r = l + w;
      if (dir.indexOf('n') !== -1) t = b - h; else b = t + h;
    }
    return { left: l, top: t, width: r - l, height: b - t };
  }

  /** One selected layer, ready to be resized as the visual unit it is. */
  function resizeItem(el) {
    var chain = chainOf(el);
    var m = measure(chain[0]);
    m.kind = kindOf(chain);
    m.inner = chain.slice(1).map(function (e) {
      var cs = W.getComputedStyle(e);
      return { el: e, key: keyOf(e), abs: /absolute|fixed/.test(cs.position), fit: /^(IMG|VIDEO)$/.test(e.tagName) ? cs.objectFit : null };
    });
    m.texts = m.kind === 'text' ? textParts(chain[chain.length - 1]) : [];
    // One preview record per element: the unit's box, the pieces inside and
    // the words can be the same element, and two records on one element
    // would each put back the other's half.
    m.entries = [m];
    m.inner.concat(m.texts).forEach(function (x) { x.part = partOf(m, x.el, x.key); });
    return m;
  }

  function partOf(m, el, key) {
    for (var i = 0; i < m.entries.length; i++) if (m.entries[i].el === el) return m.entries[i];
    var part = { el: el, key: key };
    m.entries.push(part);
    return part;
  }

  /** Every style change one item needs for the group box going from `box` to `next`. */
  function resizeTo(m, box, next, dir) {
    var sx = box.width ? next.width / box.width : 1;
    var sy = box.height ? next.height / box.height : 1;
    var r = m.rect;
    var nl = next.left + (r.left - box.left) * sx;
    var nt = next.top + (r.top - box.top) * sy;
    var horiz = /[ew]/.test(dir), vert = /[ns]/.test(dir);
    var w = null, h = null, k = 1;
    if (m.kind === 'text') {
      // Type scales from the corners and the top/bottom; the sides re-wrap it.
      if (vert) k = horiz ? sy : sy;
      w = r.width * (horiz ? sx : k);
      if (!vert) k = 1;
    } else if (m.kind === 'image') {
      // Both sizes always: a picture's side handle crops, it never collapses.
      w = r.width * sx;
      h = r.height * sy;
    } else {
      w = horiz ? r.width * sx : null;
      h = vert ? r.height * sy : null;
    }
    var mv = moveStyles(m, nl - r.left, nt - r.top);
    // An absolutely placed layer is pinned by the edge it grows from.
    if (m.how === 'abs') {
      var u = m.unit || 1;
      if (w !== null) { mv.left = r1(m.absLeft + (nl - r.left) / u); mv.right = 'auto'; }
      if (h !== null) { mv.top = r1(m.absTop + (nt - r.top) / u); mv.bottom = 'auto'; }
    }
    m.entries.forEach(function (part) { part.next = {}; });
    m.next = merged(mv, sizeStyles(m, w, h));
    m.inner.forEach(function (e) {
      var s = {};
      if (w !== null) { s.width = '100%'; s['max-width'] = 'none'; s['min-width'] = '0px'; }
      if (h !== null) { s.height = '100%'; s['max-height'] = 'none'; s['min-height'] = '0px'; }
      if (e.abs) { s.left = '0px'; s.top = '0px'; s.right = 'auto'; s.bottom = 'auto'; }
      if (e.fit === 'fill' && Math.abs(sx - sy) > 0.01) s['object-fit'] = 'cover';
      e.part.next = merged(e.part.next, s);
    });
    m.texts.forEach(function (t) {
      var s = {};
      if (k !== 1) {
        s['font-size'] = rf(t.fs * k);
        // Kept as a ratio, so the lines keep their rhythm at the new size.
        if (t.lh && t.fs) s['line-height'] = String(Math.round(t.lh / t.fs * 1000) / 1000);
        if (t.ls) s['letter-spacing'] = rf(t.ls * k);
      }
      t.part.next = merged(t.part.next, s);
    });
    m.entries.forEach(function (part) { preview(part, part.next); });
  }

  var spaceDown = false;

  function onPointerDown(event) {
    if (spaceDown && event.button === 0) {
      // The hand tool: the canvas follows the pointer, in screen pixels.
      drag = { type: 'pan', start: { id: event.pointerId, x: event.screenX, y: event.screenY }, live: true, last: { x: event.screenX, y: event.screenY } };
      try { D.documentElement.setPointerCapture(event.pointerId); } catch (e) {}
      event.preventDefault();
      return;
    }
    if (mode !== 'select' || event.button !== 0) return;
    if (editingEl && (editingEl.contains(event.target) || event.target===editingLabel)) return;
    var handle = event.target && event.target.closest ? event.target.closest('#ldrn-studio-ui .h') : null;
    var start = pagePoint(event);
    start.id = event.pointerId;
    if (handle) {
      var els = selectedEls();
      if (!els.length) return;
      var items = els.map(resizeItem);
      drag = {
        type: 'resize', dir: handle.getAttribute('data-h'), start: start, items: items, live: true,
        box: unionOf(items.map(function (m) { return m.rect; })),
        natural: items.some(function (m) { return m.kind !== 'box'; }),
      };
      drag.lines = snapLines(items.map(function (m) { return m.el; }));
      try { D.documentElement.setPointerCapture(event.pointerId); } catch (e) {}
    } else {
      var el = targetFrom(event);
      var sel = selectedEls();
      var onSelection = !!el && sel.some(function (s) { return s === el || s.contains(el); });
      var boxMode = event.ctrlKey || event.metaKey || !onSelection;
      drag = boxMode
        ? { type: 'marquee', start: start, from: el }
        : { type: 'move', start: start, items: null, els: sel };
    }
  }

  /** The units a move carries - each selected layer's outermost same-size wrapper. */
  function moveUnits(els) {
    var units = [];
    els.forEach(function (el) {
      var u = chainOf(el)[0];
      if (units.indexOf(u) === -1) units.push(u);
    });
    return units.filter(function (u) { return !units.some(function (o) { return o !== u && o.contains(u); }); });
  }

  function onPointerDrag(event) {
    if (!drag || event.pointerId !== drag.start.id) return;
    if (drag.type === 'pan') {
      var fe = W.frameElement;
      var scale = fe ? fe.getBoundingClientRect().width / W.innerWidth : 1;
      host('pan', { dx: -(event.screenX - drag.last.x) });
      W.scrollBy(0, -(event.screenY - drag.last.y) / (scale || 1));
      drag.last = { x: event.screenX, y: event.screenY };
      return;
    }
    drag.last = { clientX: event.clientX, clientY: event.clientY, shiftKey: event.shiftKey, altKey: event.altKey };
    var p = pagePoint(event);
    var dx = p.x - drag.start.x;
    var dy = p.y - drag.start.y;
    if (!drag.live) {
      if (Math.abs(dx) < 4 && Math.abs(dy) < 4) return;
      drag.live = true;
      // Captured only once it is a drag: a plain click must still land on
      // the element under the pointer.
      try { D.documentElement.setPointerCapture(event.pointerId); } catch (e) {}
      D.documentElement.setAttribute('data-ldrn-ws-dragging', drag.type);
      if (drag.type === 'move') {
        drag.items = moveUnits(drag.els).map(measure);
        drag.box = unionOf(drag.items.map(function (m) { return m.rect; }));
        drag.lines = snapLines(drag.items.map(function (m) { return m.el; }));
      }
      if (drag.type === 'marquee') drag.pool = marqueePool();
    }
    event.preventDefault();
    hoverEl = null;
    drag.guides = [];
    if (drag.type === 'move') {
      if (!event.altKey && drag.lines) {
        var b = drag.box;
        var gx = nearest([b.left + dx, b.left + b.width / 2 + dx, b.left + b.width + dx], drag.lines.xs);
        var gy = nearest([b.top + dy, b.top + b.height / 2 + dy, b.top + b.height + dy], drag.lines.ys);
        if (gx) { dx += gx[0]; drag.guides.push({ x: gx[1] }); }
        if (gy) { dy += gy[0]; drag.guides.push({ y: gy[1] }); }
      }
      drag.items.forEach(function (m) { preview(m, moveStyles(m, dx, dy)); });
      drag.dim = (dx >= 0 ? '→ ' : '← ') + Math.abs(Math.round(dx)) + '   ' + (dy >= 0 ? '↓ ' : '↑ ') + Math.abs(Math.round(dy));
    } else if (drag.type === 'resize') {
      var keep = drag.dir.length === 2 && (drag.natural !== event.shiftKey);
      if (!keep && !event.altKey && drag.lines) {
        var bx = drag.box;
        if (/e/.test(drag.dir)) { var se = nearest([bx.left + bx.width + dx], drag.lines.xs); if (se) { dx += se[0]; drag.guides.push({ x: se[1] }); } }
        if (/w/.test(drag.dir)) { var sw = nearest([bx.left + dx], drag.lines.xs); if (sw) { dx += sw[0]; drag.guides.push({ x: sw[1] }); } }
        if (/s/.test(drag.dir)) { var ss = nearest([bx.top + bx.height + dy], drag.lines.ys); if (ss) { dy += ss[0]; drag.guides.push({ y: ss[1] }); } }
        if (/n/.test(drag.dir)) { var sn = nearest([bx.top + dy], drag.lines.ys); if (sn) { dy += sn[0]; drag.guides.push({ y: sn[1] }); } }
      }
      var next = resizedBox(drag.box, drag.dir, dx, dy, keep);
      drag.items.forEach(function (m) { resizeTo(m, drag.box, next, drag.dir); });
      drag.dim = Math.round(next.width) + ' × ' + Math.round(next.height);
    } else if (drag.type === 'marquee') {
      drag.rect = {
        left: Math.min(drag.start.x, p.x), top: Math.min(drag.start.y, p.y),
        right: Math.max(drag.start.x, p.x), bottom: Math.max(drag.start.y, p.y),
      };
      drag.hits = marqueeHits(drag.pool, drag.rect);
    }
    autoScroll(event.clientY);
    refreshBoxes();
  }

  /** Near the top or bottom of the canvas the page scrolls under the drag. */
  function autoScroll(y) {
    var edge = 36;
    var speed = y < edge ? -Math.ceil((edge - y) / 3) : y > W.innerHeight - edge ? Math.ceil((y - (W.innerHeight - edge)) / 3) : 0;
    drag.auto = drag.live ? speed : 0;
    if (drag.auto && !drag.ticking) {
      drag.ticking = true;
      (function tick() {
        if (!drag || !drag.auto) { if (drag) drag.ticking = false; return; }
        W.scrollBy(0, drag.auto);
        var ev = drag.last;
        onPointerDrag({ pointerId: drag.start.id, clientX: ev.clientX, clientY: ev.clientY, shiftKey: ev.shiftKey, altKey: ev.altKey, preventDefault: function () {} });
        W.requestAnimationFrame(tick);
      })();
    }
  }

  function entriesOf(d) {
    if (!d || !d.items) return [];
    return d.type === 'resize'
      ? d.items.reduce(function (all, m) { return all.concat(m.entries); }, [])
      : d.items;
  }

  function onPointerUp(event) {
    if (!drag || event.pointerId !== drag.start.id) return;
    if (drag.type === 'pan') {
      drag = null;
      suppressClick = true;
      setTimeout(function () { suppressClick = false; }, 0);
      try { D.documentElement.releasePointerCapture(event.pointerId); } catch (e) {}
      return;
    }
    var done = drag;
    drag = null;
    D.documentElement.removeAttribute('data-ldrn-ws-dragging');
    try { D.documentElement.releasePointerCapture(event.pointerId); } catch (e) {}
    if (done.live) {
      // The click that ends a drag is not a click on whatever is under it.
      suppressClick = true;
      setTimeout(function () { suppressClick = false; }, 0);
    }
    var entries = entriesOf(done);
    if (!done.live || (done.type !== 'marquee' && !entries.some(function (e) { return e.styles && Object.keys(e.styles).length; }))) {
      entries.forEach(unpreview);
      refreshBoxes();
      return;
    }
    if (done.type === 'marquee') {
      var keys = (done.hits || []).map(keyOf).filter(Boolean);
      if (event.shiftKey) keys = selectedKeys().concat(keys.filter(function (k) { return selectedKeys().indexOf(k) === -1; }));
      setSelection(keys, keys[keys.length - 1] || null, true);
    } else {
      commit(entries, done.type);
    }
    refreshBoxes();
  }

  /** Every layer a selection box could pick, with where it sits on the page - read once per drag. */
  function marqueePool() {
    var top = topEl();
    if (!top) return [];
    var out = [];
    var sx = W.scrollX, sy = W.scrollY;
    var roots = [top];
    if (endingEl()) roots.push(endingEl());
    roots.forEach(function (root) { Array.prototype.forEach.call(root.querySelectorAll('*'), function (el) {
      if (SKIP.test(el.tagName) || isOwnUi(el) || isMain(el)) return;
      if (el.closest('svg') && el.tagName.toLowerCase() !== 'svg') return;
      var r = el.getBoundingClientRect();
      if (r.width < 2 || r.height < 2) return;
      var cs = W.getComputedStyle(el);
      if (cs.visibility === 'hidden' || cs.display === 'none') return;
      out.push({ el: el, r: { left: r.left + sx, top: r.top + sy, right: r.right + sx, bottom: r.bottom + sy } });
    }); });
    return out;
  }

  /** Wholly inside the box, and not inside another layer that also is. */
  function marqueeHits(pool, box) {
    if (!pool || box.right - box.left < 3 || box.bottom - box.top < 3) return [];
    var inside = pool.filter(function (p) {
      return p.r.left >= box.left && p.r.right <= box.right && p.r.top >= box.top && p.r.bottom <= box.bottom;
    }).map(function (p) { return p.el; });
    return inside.filter(function (el) {
      for (var a = el.parentElement; a; a = a.parentElement) if (inside.indexOf(a) !== -1) return false;
      return !!keyOf(el);
    });
  }

  function setSelection(keys, primary, fromCanvas) {
    var clean = [];
    keys.forEach(function (k) { if (k && clean.indexOf(k) === -1 && find(k)) clean.push(k); });
    selectedKey = primary && clean.indexOf(primary) !== -1 ? primary : clean[clean.length - 1] || null;
    extraKeys = clean.filter(function (k) { return k !== selectedKey; });
    refreshBoxes();
    if (fromCanvas) host('selectMany', { keys: clean, primary: selectedKey });
  }

  function cancelDrag() {
    if (!drag) return;
    entriesOf(drag).forEach(unpreview);
    drag = null;
    D.documentElement.removeAttribute('data-ldrn-ws-dragging');
    refreshBoxes();
  }

  function nudge(dx, dy) {
    var els = selectedEls();
    if (!els.length) return;
    var items = moveUnits(els).map(measure);
    items.forEach(function (m) { m.styles = moveStyles(m, dx, dy); });
    commit(items, 'nudge');
    refreshBoxes();
  }

  /* ----------------------------------------------------------- describing */

  var INLINE = /^(BR|SPAN|EM|STRONG|I|B|SMALL|SUP|SUB|MARK|U|ABBR|TIME|Q|S)$/;

  function isTextish(el) {
    if (!el || el.nodeType !== 1) return false;
    if (el.hasAttribute('data-ldrn-ws-label')) return true;
    if (/^(A|BUTTON)$/.test(el.tagName)) return false;
    if (/^(IMG|PICTURE|VIDEO|SVG|svg|INPUT|TEXTAREA|SELECT|IFRAME|CANVAS)$/.test(el.tagName)) return false;
    var hasText = false;
    for (var n = el.firstChild; n; n = n.nextSibling) {
      if (n.nodeType === 3 && n.nodeValue.trim()) hasText = true;
      else if (n.nodeType === 1) {
        if (!INLINE.test(n.tagName)) return false;
        if (n.querySelector('img,svg,video')) return false;
        if (n.textContent.trim()) hasText = true;
      }
    }
    return hasText;
  }

  function typeOf(el) {
    var tag = el.tagName;
    if (tag === 'IMG' || tag === 'PICTURE' || tag === 'VIDEO') return 'image';
    // A wrapper that holds a picture and no words is, to anyone editing, the picture.
    if (tag !== 'SECTION' && tag !== 'MAIN' && !(el.textContent || '').trim() && imageIn(el) && !isTop(el)) return 'image';
    if (tag === 'svg' || tag === 'SVG') return 'graphic';
    if (tag === 'A') return 'link';
    if (tag === 'BUTTON') return 'button';
    if (/^(INPUT|TEXTAREA|SELECT)$/.test(tag)) return 'input';
    if (isTextish(el)) return 'text';
    var display = W.getComputedStyle(el).display;
    if (/flex/.test(display)) return 'stack';
    if (/grid/.test(display)) return 'grid';
    if (tag === 'SECTION' || tag === 'HEADER' || tag === 'FOOTER' || tag === 'NAV' || tag === 'MAIN' || tag === 'ARTICLE') return 'section';
    return 'frame';
  }

  function visibleText(el) {
    var t = el.innerText;
    return typeof t === 'string' && t.trim() ? t : el.textContent || '';
  }

  function short(text, n) {
    text = tidy(text).replace(/\n/g, ' ');
    return text.length > n ? text.slice(0, n - 1) + '…' : text;
  }

  /** The site chrome, named the way the studio would talk about it. */
  function chromeName(el) {
    var top = topEl();
    if (!top || el.parentElement !== top) {
      if (el.id === 'mobile-nav') return 'Mobile menu';
      if (el.classList.contains('desktop-nav')) return 'Menu';
      if (el.classList.contains('header-cta')) return 'Button · Let’s talk';
      return '';
    }
    if (el.tagName === 'HEADER') return 'Navigation';
    if (el.tagName === 'FOOTER') return 'Footer';
    return '';
  }

  function nameOf(el) {
    if (!el) return '';
    if (isMain(el)) return 'Page';
    var chrome = chromeName(el);
    if (chrome) return chrome;
    var own = el.hasAttribute('data-ldrn-wsn') ? treeIndex[el.getAttribute('data-ldrn-wsn')] : null;
    if (own && own.name) return own.name;
    var tag = el.tagName;
    var type = typeOf(el);
    var label = el.getAttribute('aria-label') || '';
    if (tag === 'NAV') return 'Navigation';
    if (tag === 'FOOTER') return 'Footer';
    if (type === 'image') {
      var img = imageIn(el) || el;
      return 'Image' + (img.getAttribute && img.getAttribute('alt') ? ' · ' + short(img.getAttribute('alt'), 28) : '');
    }
    if (type === 'text') return short(visibleText(el), 40) || 'Text';
    if (type === 'link' || type === 'button') {
      var t = short(visibleText(el) || label, 32);
      return (type === 'link' ? 'Link' : 'Button') + (t ? ' · ' + t : '');
    }
    if (type === 'graphic') return label ? 'Graphic · ' + short(label, 24) : 'Graphic';
    if (type === 'input') return 'Field · ' + (el.getAttribute('name') || el.getAttribute('placeholder') || tag.toLowerCase());
    if (type === 'section' || tag === 'SECTION') {
      var heading = el.querySelector('h1,h2,h3');
      var ht = heading ? short(visibleText(heading), 30) : '';
      return 'Section' + (label ? ' · ' + short(label, 30) : ht ? ' · ' + ht : '');
    }
    var word = type === 'stack' ? 'Stack' : type === 'grid' ? 'Grid' : 'Frame';
    if (el.hasAttribute('data-ldrn-ws')) return word + ' · ' + el.getAttribute('data-ldrn-ws');
    var inner = short(visibleText(el), 26);
    return inner ? word + ' · ' + inner : word;
  }

  var SKIP = /^(SCRIPT|STYLE|NOSCRIPT|TEMPLATE|BR|LINK|META)$/;

  function layerKids(el) {
    if (!el || el.tagName === 'svg' || el.tagName === 'SVG' || el.tagName === 'PICTURE') return [];
    if (typeOf(el) === 'text') return [];
    return Array.prototype.filter.call(el.children, function (c) {
      if (SKIP.test(c.tagName)) return false;
      if (c.classList.contains('skip-link') || c.classList.contains('svg-definitions') || c.classList.contains('reading-progress')) return false;
      return true;
    });
  }

  function isHidden(el) {
    var cs = W.getComputedStyle(el);
    return cs.display === 'none' || cs.visibility === 'hidden';
  }

  function describe(el) {
    var key = keyOf(el);
    if (!key) return null;
    var cs = W.getComputedStyle(el);
    var order = parseInt(cs.order, 10) || 0;
    return {
      key: key,
      name: nameOf(el),
      type: typeOf(el),
      kids: layerKids(el).length,
      hidden: isHidden(el),
      edited: !!doc.nodes[key],
      order: order,
      z: parseInt(cs.zIndex, 10) || 0,
      position: cs.position,
      parentKey: el.parentElement ? keyOf(el.parentElement) : null,
      inserted: el.hasAttribute('data-ldrn-wsn'),
    };
  }

  function sorted(list) {
    return list
      .map(function (el, i) { return { el: el, i: i, o: parseInt(W.getComputedStyle(el).order, 10) || 0 }; })
      .sort(function (a, b) { return a.o - b.o || a.i - b.i; })
      .map(function (x) { return x.el; });
  }

  /** The top of the layer tree: the page's own sections, then the chrome. */
  function roots() {
    var main = mainEl();
    var top = topEl();
    var list = [];
    if (main) list.push(main);
    if (top && !SCOPED) {
      layerKids(top).forEach(function (el) {
        if (el === main || (main && el.contains(main))) return;
        list.push(el);
      });
    }
    if (endingEl()) list.push(endingEl());
    return list;
  }

  function tree(key) {
    var kids = key ? layerKids(find(key)) : roots();
    return sorted(kids).map(describe).filter(Boolean);
  }

  function outline() {
    var count = 0;
    function walk(el, depth) {
      if (count++ > 2000 || depth > 16) return null;
      var item = describe(el);
      if (!item) return null;
      item.children = depth < 16 ? sorted(layerKids(el)).map(function (kid) { return walk(kid, depth + 1); }).filter(Boolean) : [];
      return item;
    }
    return roots().map(function (root) { return walk(root, 0); }).filter(Boolean);
  }

  // Painting order of one sibling group, front first. Keeping the scope at
  // one parent means a single layer can move without lifting its whole page.
  function stack(key) {
    var el = find(key);
    if (!el || !el.parentElement || el.parentElement === D.body) return [];
    return layerKids(el.parentElement).map(function (item, index) {
      var cs = W.getComputedStyle(item);
      return { key: keyOf(item), name: nameOf(item), type: typeOf(item),
        position: cs.position, hidden: cs.display === 'none' || cs.visibility === 'hidden',
        z: parseInt(cs.zIndex, 10) || 0, index: index };
    }).filter(function (item) { return item.key && !item.hidden; })
      .sort(function (a, b) { return b.z - a.z || b.index - a.index; });
  }

  function ancestry(key) {
    var el = find(key);
    var chain = [];
    var main = mainEl();
    var top = topEl();
    while (el && el !== top && el !== D.body) {
      var k = keyOf(el);
      if (k) chain.unshift({ key: k, name: nameOf(el) });
      if (el === main) break;
      if (isEnding(el)) break;
      el = el.parentElement;
      // The wrapper around <main> and the footer is not a layer anyone thinks
      // of - skip straight past it.
      if (el && main && el.contains(main) && el !== main) break;
    }
    return chain;
  }

  var COMPUTED = [
    'fill','stroke','stroke-width',
    'color', 'font-family', 'font-size', 'font-weight', 'line-height', 'letter-spacing',
    'text-align', 'text-transform', 'font-style', 'text-decoration-line',
    'width', 'height', 'min-height', 'max-width', 'display', 'flex-direction',
    'justify-content', 'align-items', 'flex-wrap', 'gap', 'order',
    'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
    'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
    'opacity', 'background-color', 'border-top-left-radius', 'border-top-width', 'border-top-style',
    'border-top-color', 'box-shadow', 'overflow', 'object-fit', 'object-position',
  ];

  function info(key) {
    var el = find(key);
    if (!el) return null;
    var cs = W.getComputedStyle(el);
    var computed = {};
    COMPUTED.forEach(function (p) { computed[p] = cs.getPropertyValue(p); });
    var parent = el.parentElement;
    var siblings = parent ? sorted(layerKids(parent)) : [el];
    // A section containing one photo is still a section. Its inspector
    // must not offer a replacement for a different, unselected child.
    var img = typeOf(el)==='image' ? imageIn(el) : null;
    var link = linkFor(el);
    var labelText=Array.prototype.filter.call(el.childNodes,function(child){return child.nodeType===3&&child.nodeValue.trim();}).map(function(child){return child.nodeValue;}).join('').trim();
    var r = el.getBoundingClientRect();
    return {
      key: key,
      name: nameOf(el),
      type: typeOf(el),
      tag: el.tagName.toLowerCase(),
      inserted: el.hasAttribute('data-ldrn-wsn'),
      embed: el.tagName === 'IFRAME' ? el.getAttribute('src') || '' : null,
      textual: isTextish(el),
      rich: isTextish(el) && hasRich(el),
      text: isTextish(el) ? tidy(readText(el)) : null,
      labelText: !isTextish(el) && (labelText || typeof (doc.nodes[key]||{}).label==='string') ? labelText : null,
      placeholder: /^(INPUT|TEXTAREA)$/.test(el.tagName) ? el.getAttribute('placeholder')||'' : null,
      options: el.tagName==='SELECT' ? Array.prototype.map.call(el.options,function(option){return {value:option.value,label:option.textContent};}) : null,
      image: img ? {
        src: img.currentSrc || img.getAttribute('src') || '',
        alt: img.getAttribute('alt') || '',
        // Fit and focus belong to the <img> itself, even when the layer
        // selected is the frame around it.
        key: keyOf(img),
        fit: W.getComputedStyle(img).objectFit,
        position: W.getComputedStyle(img).objectPosition,
      } : null,
      href: link ? link.getAttribute('href') || '' : null,
      linkable: canLink(el),
      linkKey: link ? (link.getAttribute('data-ldrn-link-for') || keyOf(link)) : null,
      newTab: link ? link.getAttribute('target')==='_blank' : false,
      computed: computed,
      parentDisplay: parent ? W.getComputedStyle(parent).display : '',
      parentKey: parent ? keyOf(parent) : null,
      siblings: siblings.map(keyOf),
      index: siblings.indexOf(el),
      kids: layerKids(el).length,
      width: Math.round(r.width),
      height: Math.round(r.height),
      path: ancestry(key),
    };
  }

  function select(key, source) {
    selectedKey = key || null;
    extraKeys = [];
    refreshBoxes();
    if (source === 'canvas') host('select', { key: selectedKey });
  }

  function scrollToKey(key) {
    var el = find(key);
    if (!el) return;
    var r = el.getBoundingClientRect();
    if (r.top >= 0 && r.bottom <= W.innerHeight) return;
    var y = W.scrollY + r.top - Math.max(40, (W.innerHeight - r.height) / 2);
    // Lenis never starts inside the editor (see reduced motion above), so
    // the window scrolls natively.
    W.scrollTo(0, y);
  }

  function discoverRoutes() {
    var found = {};
    Array.prototype.forEach.call(D.querySelectorAll('a[href^="/"]'), function (a) {
      var raw = a.getAttribute('href').split(/[?#]/)[0];
      if (/^\/\//.test(raw) || /^\/wp-/.test(raw) || /\.[a-z0-9]{2,5}$/i.test(raw)) return;
      var r = raw.replace(/\/$/, '') || '/';
      r = ALIASES[r] || r;
      // A project card links with its whole face; its heading is its name.
      var heading = a.querySelector('h1,h2,h3,h4');
      var label = visibleText(heading || a).split(/\n/).map(function (l) { return l.trim(); }).filter(Boolean)[0] || r;
      if (/^\/[A-Za-z0-9\/_-]*$/.test(r) && !found[r]) found[r] = short(label, 40);
    });
    return found;
  }

  /**
   * A copy of one of the site's own sections, as an inserted tree - for
   * Duplicate. It keeps the build's class names, so it looks the same, and
   * drops anything that only exists to run an animation.
   */
  function newId() {
    var id = '';
    while (id.length < 8) id += Math.random().toString(36).slice(2);
    return id.slice(0, 8);
  }

  var SNAP_AS = { nav: 'div', main: 'div', button: 'a', label: 'span', time: 'span', b: 'strong', i: 'em' };

  function snap(el, depth) {
    if (!el || el.nodeType !== 1 || depth > 13) return null;
    var tag = el.tagName.toLowerCase();
    if (tag === 'picture') { var inner = el.querySelector('img'); return inner ? snap(inner, depth) : null; }
    if (/^(video|audio|input|textarea|select|form|script|style|canvas|iframe|br|noscript|dialog)$/.test(tag)) return null;
    if(el.closest('svg') && !SVG_TAGS.test(tag))return null;
    tag = SNAP_AS[tag] || tag;
    if (!TAGS.test(tag)) tag = 'div';
    var node = { id: newId(), tag: tag };
    if(SVG_TAGS.test(tag)) {
      node.attrs={};
      Array.prototype.forEach.call(el.attributes,function(attr){if(safeSvgAttr(attr.name,attr.value))node.attrs[attr.name]=attr.value;});
    }
    // Where it was copied from, so the editor can bring its edits along.
    var from = keyOf(el); if (from) node.from = from;
    var cls = String(el.getAttribute('class') || '').split(/\s+/).filter(function (c) {
      return c && !/^(reveal|is-visible|parallax)$/.test(c);
    }).join(' ');
    if (cls) node.cls = cls;
    if (tag === 'img') {
      node.src = el.getAttribute('src') || el.currentSrc || '';
      node.alt = el.getAttribute('alt') || '';
      return node;
    }
    if (tag === 'a') {node.href=el.getAttribute('href')||'';node.newTab=el.getAttribute('target')==='_blank';}
    // A run of text with inline emphasis copies as text. A flex or grid row
    // of spans is a layout, and copies as separate pieces.
    if (isTextish(el) && !/flex|grid/.test(W.getComputedStyle(el).display)) { node.text = tidy(readText(el)); return node; }
    var kids = [];
    Array.prototype.forEach.call(el.children, function (c) {
      var k = snap(c, depth + 1);
      if (k) kids.push(k);
    });
    if (kids.length) node.kids = kids;
    return node;
  }

  W.__ldrnStudioBridge = {
    setDirectory: function(list) {
      W.ldrnWorkspace = W.ldrnWorkspace || {};
      W.ldrnWorkspace.pages = list.map(function(page){return Object.assign({},page,{group:page.menu?'primary':'other'});});
      W.ldrnWorkspace.customPages = list.filter(function(page){return page.custom;}).map(function(page){return {route:page.route,label:page.label};});
      W.dispatchEvent(new Event('ldrn-workspace-pages'));
    },
    setDoc: function (next) {
      doc = normalise(JSON.parse(JSON.stringify(next || {})));
      resolveKeys();
      yieldLegacy();
      indexTrees();
      paintStyles();
      schedule();
      refreshBoxes();
    },
    setPages: function (list) {
      customPages = Array.isArray(list) ? list : [];
      schedule();
    },
    snapshot: function (key) { var el = find(key); return el ? snap(el, 0) : null; },
    stack: stack,
    outline: outline,
    setMode: setMode,
    select: function (key) { stopEditing(false); select(key); },
    selectMany: function (keys, primary) { stopEditing(false); setSelection(keys || [], primary, false); },
    nudge: nudge,
    // What an edit on this address should remember it was made on.
    fingerprint: function (key) {
      if (!fingerprintable(key)) return '';
      var el = rawFind(key);
      return el ? fingerprintOf(el) : '';
    },
    // Edits on this page whose element can no longer be found.
    orphans: function () { resolveKeys(); return Object.keys(orphans); },
    // Edited or added layers that run past the right edge of the screen -
    // further than the site's own design already has them (a marquee's strip
    // runs off by design; that is not the client's doing).
    overflow: function () {
      var out = [];
      var limit = D.documentElement.clientWidth + 1;
      var keys = Object.keys(doc.nodes).concat(Object.keys(built).map(function (id) { return 'n:' + id; }));
      var over = [];
      keys.forEach(function (k) {
        var el = find(k);
        if (!el || el.closest('#ldrn-studio-ui')) return;
        var r = el.getBoundingClientRect();
        if (r.width && r.right > limit + 2 && W.getComputedStyle(el).display !== 'none') over.push({ key: k, el: el, right: r.right });
      });
      if (!over.length) return out;
      // Where the site itself puts them: measured with the edits switched
      // off for an instant (no transitions, so nothing moves on screen).
      var still = D.createElement('style');
      still.textContent = '*,*::before,*::after{transition:none!important}';
      D.head.appendChild(still);
      var sheet = styleEl && styleEl.parentNode ? styleEl : null;
      if (sheet) sheet.disabled = true;
      over.forEach(function (o) { o.before = o.key.indexOf('n:') === 0 ? -Infinity : o.el.getBoundingClientRect().right; });
      if (sheet) sheet.disabled = false;
      void D.documentElement.offsetWidth;
      still.remove();
      over.forEach(function (o) {
        if (o.right > Math.max(limit, o.before) + 2) out.push({ key: o.key, name: nameOf(o.el), over: Math.round(o.right - limit) });
      });
      return out;
    },
    // Space held in the editor's own chrome: the hand tool here too.
    hand: function (on) {
      spaceDown = !!on;
      if (on) D.documentElement.setAttribute('data-ldrn-ws-hand', '');
      else D.documentElement.removeAttribute('data-ldrn-ws-hand');
    },
    // The address of an element - for the editor's own tooling and QA.
    keyOf: function (el) { return keyOf(el); },
    // What a gesture in progress is doing - read-only, for QA.
    dragState: function () {
      return drag ? JSON.parse(JSON.stringify({ type: drag.type, dir: drag.dir, live: drag.live, start: drag.start, box: drag.box, dim: drag.dim,
        items: (drag.items || []).map(function (m) { return { key: m.key, kind: m.kind, rect: m.rect, unit: m.unit, styles: m.styles, inner: (m.inner || []).length, texts: (m.texts || []).map(function (t) { return [t.key, t.fs, t.styles]; }) }; }) })) : null;
    },
    hover: function (key) { hoverEl = key ? find(key) : null; refreshBoxes(); },
    scrollTo: scrollToKey,
    tree: tree,
    info: info,
    route: function () { return route; },
    routes: discoverRoutes,
    exists: function (key) { return !!find(key); },
    edit: function (key) { var el = find(key); if (el && isTextish(el)) startEditing(el, key); else if(el && ownText(el))startEditingLabel(el,key); },
    stopEditing: function () { stopEditing(false); },
  };
})();
