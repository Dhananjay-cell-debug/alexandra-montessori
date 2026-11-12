/* Little Rainbows - first-party measurement.
 *
 * Runs only after the visitor allows "Analytics" in the site's cookie panel
 * (localStorage "ldrn-consent", event "ldrn:consent"). The site has no cookie
 * panel yet - its privacy page says it sets no cookies - so until one exists
 * this waits and sends nothing. No cookie is set and nothing identifying is sent: the
 * page, where the visit came from, the screen size class, how long the page
 * was in view, and the page's Core Web Vitals as this visitor experienced
 * them.
 *
 * The site moves between pages without reloading (History API), so every
 * change of address counts as a page view of its own; the Core Web Vitals
 * belong to the page the visitor landed on.
 *
 * The same consent gates the Google tag (GA4) and Tag Manager, when Settings
 * names them: nothing from Google loads until it is allowed.
 */
(function () {
  'use strict';
  var me = document.getElementById('ldrnseo-measure');
  if (!me || navigator.webdriver) return;
  var api = me.getAttribute('data-api');
  var off = me.getAttribute('data-off') === '1';
  var ga4 = me.getAttribute('data-ga4');
  var gtm = me.getAttribute('data-gtm');

  function choices() {
    try {
      var r = JSON.parse(localStorage.getItem('ldrn-consent') || 'null');
      if (!r || r.version !== 1 || !r.choices) return null;
      if (Date.now() - (r.at || 0) > 365 * 864e5) return null;
      return r.choices;
    } catch (e) { return null; }
  }

  /* ---- Core Web Vitals, observed from the start (buffered), sent only with consent */
  var v = { lcp: null, cls: 0, inp: null, fcp: null, ttfb: null };
  var clsWin = 0, clsFirst = 0, clsLast = 0, interacted = false;
  var inter = {};
  function observe(type, cb, opts) {
    try {
      var po = new PerformanceObserver(function (l) { l.getEntries().forEach(cb); });
      var o = { type: type, buffered: true };
      if (opts) for (var k in opts) o[k] = opts[k];
      po.observe(o);
    } catch (e) { /* not supported in this browser */ }
  }
  observe('largest-contentful-paint', function (e) { if (!interacted) v.lcp = Math.round(e.renderTime || e.loadTime || e.startTime); });
  observe('layout-shift', function (e) {
    if (e.hadRecentInput) return;
    if (clsWin && e.startTime - clsLast < 1000 && e.startTime - clsFirst < 5000) { clsWin += e.value; }
    else { clsWin = e.value; clsFirst = e.startTime; }
    clsLast = e.startTime;
    if (clsWin > v.cls) v.cls = clsWin;
  });
  observe('paint', function (e) { if (e.name === 'first-contentful-paint') v.fcp = Math.round(e.startTime); });
  observe('event', function (e) {
    if (!e.interactionId) return;
    inter[e.interactionId] = Math.max(inter[e.interactionId] || 0, e.duration);
  }, { durationThreshold: 40 });
  observe('first-input', function (e) { inter['f' + e.startTime] = Math.max(inter['f' + e.startTime] || 0, e.duration); });
  ['keydown', 'pointerdown'].forEach(function (t) { addEventListener(t, function () { interacted = true; }, { once: true, capture: true }); });
  try {
    var nav = performance.getEntriesByType('navigation')[0];
    if (nav) v.ttfb = Math.max(0, Math.round(nav.responseStart - (nav.activationStart || 0)));
  } catch (e) {}
  function inp() {
    var d = Object.keys(inter).map(function (k) { return inter[k]; }).sort(function (a, b) { return b - a; });
    if (!d.length) return null;
    return Math.round(d[Math.min(d.length - 1, Math.floor(d.length / 50))]);
  }

  /* ---- Time in view, per page view */
  var shown = 0, since = document.visibilityState === 'visible' ? performance.now() : 0;
  function engaged() { return Math.round(shown + (since ? performance.now() - since : 0)); }
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') { since = performance.now(); }
    else { if (since) shown += performance.now() - since; since = 0; send('end'); }
  });
  addEventListener('pagehide', function () { send('end'); });

  /* ---- Sending */
  function newId() { return Math.random().toString(36).slice(2, 10) + Date.now().toString(36).slice(-6); }
  var id = newId(), landed = true, path = location.pathname, referrer = document.referrer || '';
  var started = false;
  function post(body) {
    var data = JSON.stringify(body);
    if (navigator.sendBeacon && navigator.sendBeacon(api, new Blob([data], { type: 'application/json' }))) return;
    try { fetch(api, { method: 'POST', body: data, keepalive: true, credentials: 'omit', headers: { 'Content-Type': 'application/json' } }); } catch (e) {}
  }
  function send(kind) {
    if (off || !started) return;
    if (kind === 'pv') {
      post({ t: 'pv', id: id, p: location.pathname, r: referrer, q: location.search.slice(0, 300), w: Math.round(innerWidth) });
    } else if (landed) {
      post({ t: 'end', id: id, e: engaged(), lcp: v.lcp, cls: Math.round(v.cls * 1000), inp: inp(), fcp: v.fcp, ttfb: v.ttfb });
    } else {
      post({ t: 'end', id: id, e: engaged() });
    }
  }

  /* ---- A move to another page without a reload is a page view of its own */
  function moved() {
    if (location.pathname === path) return;
    send('end');
    referrer = location.origin + path;
    path = location.pathname;
    id = newId();
    landed = false;
    shown = 0;
    since = document.visibilityState === 'visible' ? performance.now() : 0;
    send('pv');
  }
  ['pushState', 'replaceState'].forEach(function (m) {
    var orig = history[m];
    if (typeof orig !== 'function') return;
    history[m] = function () { var r = orig.apply(this, arguments); setTimeout(moved, 0); return r; };
  });
  addEventListener('popstate', function () { setTimeout(moved, 0); });

  /* ---- What a nursery's site exists for: an enquiry, or a tap to call.
     The forms announce "ldrn:enquiry-sent" only after the server
     accepted it. */
  addEventListener('ldrn:enquiry-sent', function () { if (started) post({ t: 'lead', id: id, k: 'enquiry' }); });
  document.addEventListener('click', function (e) {
    var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
    if (!a || !started) return;
    var h = a.getAttribute('href') || '', k = '';
    if (/^tel:/i.test(h)) k = 'call';
    else if (/^mailto:/i.test(h)) k = 'email';
    else if (/wa\.me|whatsapp\.com/i.test(h)) k = 'whatsapp';
    else if (/google\.[a-z.]+\/maps|maps\.app\.goo\.gl|goo\.gl\/maps/i.test(h)) k = 'map';
    if (k) post({ t: 'lead', id: id, k: k });
  }, true);

  /* ---- Google, only when allowed */
  var gLoaded = false;
  function google(c) {
    if (gLoaded || !(ga4 || gtm)) return;
    var analytics = !!c.analytics, marketing = !!c.marketing;
    if (!analytics && !marketing) return;
    gLoaded = true;
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
    window.gtag('consent', 'update', {
      analytics_storage: analytics ? 'granted' : 'denied',
      ad_storage: marketing ? 'granted' : 'denied',
      ad_user_data: marketing ? 'granted' : 'denied',
      ad_personalization: marketing ? 'granted' : 'denied'
    });
    function load(src) { var s = document.createElement('script'); s.async = true; s.src = src; document.head.appendChild(s); }
    if (gtm) {
      window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
      load('https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(gtm));
    }
    if (ga4 && analytics) {
      window.gtag('js', new Date());
      window.gtag('config', ga4);
      load('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(ga4));
    }
  }

  function start(c) {
    if (!c) return;
    google(c);
    if (c.analytics && !started) { started = true; send('pv'); }
  }
  start(choices());
  addEventListener('ldrn:consent', function (e) {
    var c = e.detail || {};
    start(c);
    if (gLoaded && window.gtag) {
      window.gtag('consent', 'update', {
        analytics_storage: c.analytics ? 'granted' : 'denied',
        ad_storage: c.marketing ? 'granted' : 'denied',
        ad_user_data: c.marketing ? 'granted' : 'denied',
        ad_personalization: c.marketing ? 'granted' : 'denied'
      });
    }
    if (!c.analytics) started = false;
  });
})();
