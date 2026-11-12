/* Little Rainbows SEO - the screen's behaviour.
 *
 * The page analyser lives here so it can answer as a title or keyphrase is
 * typed. It reads the page's live text (collected by the audit, which renders
 * each page in a browser frame here, since the site draws its pages with
 * JavaScript), and applies the checks Yoast and Rank Math run, with
 * thresholds fitted to the kind of page - a nursery page is expected to carry
 * depth, a contact page is not.
 */
(function () {
  'use strict';
  var cfg = window.ldrnSeo || {};
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  function post(data) {
    var body = new URLSearchParams();
    body.set('nonce', cfg.nonce);
    Object.keys(data).forEach(function (k) { body.set(k, data[k]); });
    return fetch(cfg.ajax, { method: 'POST', credentials: 'same-origin', body: body })
      .then(function (r) { return r.json().catch(function () { return { success: false, data: { message: 'The server answered ' + r.status + '.' } }; }); });
  }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

  /* ------------------------------------------------------------ Audit run */
  $$('[data-audit-run]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var box = $('[data-audit-progress]');
      var bar = box && $('.is-progress-track i', box), label = box && $('span', box);
      $$('[data-audit-run]').forEach(function (b) { b.disabled = true; });
      if (box) box.hidden = false;
      function show(d) {
        if (!box) return;
        var total = d.total || 1, done = total - (d.left || 0);
        bar.style.width = Math.round(100 * done / total) + '%';
        label.textContent = d.did ? 'Read ' + d.did + ' - ' + done + ' of ' + total : (d.left === 0 ? 'Checking the site as a whole' : 'Starting');
      }
      function fail(msg) {
        $$('[data-audit-run]').forEach(function (b) { b.disabled = false; });
        if (label) label.textContent = msg || 'The audit stopped. Try again.';
      }
      var tries = 0;
      function step(next) {
        var page = next && next.charAt(0) === '/';
        if (page && label) label.textContent = 'Rendering ' + next;
        (page ? render(next) : Promise.resolve('')).then(function (html) {
          var data = { action: 'ldrnseo_audit', step: 'next' };
          if (html) { data.route = next; data.html = html; }
          return post(data);
        }).then(function (r) {
          // A dropped request (the host recycling a PHP worker) is retried: a step is safe to repeat.
          if (!r.success && tries < 3 && !(r.data && /session/i.test(r.data.message || ''))) { tries++; return setTimeout(function () { step(next); }, 1200); }
          tries = 0;
          if (!r.success) return fail(r.data && r.data.message);
          if (r.data.done) { if (label) label.textContent = 'Done. Score ' + r.data.score + '.'; location.reload(); return; }
          show(r.data); step(r.data.next);
        }).catch(function () { if (tries++ < 3) return setTimeout(function () { step(next); }, 1200); fail(); });
      }
      post({ action: 'ldrnseo_audit', step: 'start' }).then(function (r) {
        if (!r.success) return fail(r.data && r.data.message);
        show(r.data); step(r.data.next);
      }).catch(function () { fail(); });
    });
  });

  /**
   * The site draws its pages in the browser, so the audit reads each one the
   * way Google's renderer does: opened in a frame (same site, so its document
   * can be read), given time to render, then handed to the server whole.
   * Off-screen and inert; nothing the visitor would see is measured from it.
   */
  function render(route) {
    return new Promise(function (resolve) {
      var f = document.createElement('iframe'), done = false, t0 = Date.now();
      f.setAttribute('aria-hidden', 'true');
      f.setAttribute('tabindex', '-1');
      f.title = 'Rendering ' + route;
      f.style.cssText = 'position:fixed;left:-20000px;top:0;width:1366px;height:900px;border:0;pointer-events:none';
      function finish() {
        if (done) return;
        done = true;
        var html = '';
        try { html = '<!doctype html>\n' + f.contentDocument.documentElement.outerHTML; } catch (e) {}
        f.remove();
        resolve(html);
      }
      f.addEventListener('load', function () {
        (function wait() {
          var ready = false;
          try {
            var main = f.contentDocument.querySelector('#root main');
            ready = !!main && main.textContent.replace(/\s+/g, '').length > 40;
          } catch (e) { return finish(); }
          // Rendered, and a moment more for text the page writes in after its intro.
          if (ready && Date.now() - t0 > 2200) return finish();
          if (Date.now() - t0 > 15000) return finish();
          setTimeout(wait, 250);
        })();
      });
      setTimeout(finish, 25000);
      f.src = cfg.site + route;
      document.body.appendChild(f);
    });
  }

  /* ---------------------------------------------------------- PageSpeed */
  // Google's servers run each test (20-60 s a page); jobs go one at a time,
  // and a whole-site run tests every page on mobile and desktop.
  function psiRun(jobs) {
    var box = $('[data-psi-progress]');
    var bar = box && $('.is-progress-track i', box), label = box && $('span', box);
    var done = 0, failed = [];
    $$('[data-psi], [data-psi-site]').forEach(function (b) { b.disabled = true; });
    if (box) box.hidden = false;
    function next() {
      if (!jobs.length) {
        if (label) label.textContent = failed.length ? failed.length + ' test(s) failed: ' + failed[0] : 'Done.';
        if (!failed.length || done > failed.length) location.reload();
        $$('[data-psi], [data-psi-site]').forEach(function (b) { b.disabled = false; });
        return;
      }
      var job = jobs.shift(), total = done + jobs.length + 1;
      if (label) label.textContent = 'Google is testing ' + job.route + ' on ' + job.device + ' (' + (done + 1) + ' of ' + total + ')';
      post({ action: 'ldrnseo_psi', strategy: job.device, route: job.route }).then(function (r) {
        if (!r.success) {
          failed.push((r.data && r.data.message) || 'no answer');
          // A key problem or a spent quota stops the run; anything else moves on.
          if (/key|quota/i.test(failed[failed.length - 1])) { jobs = []; }
        }
      }).catch(function () { failed.push('no answer'); }).then(function () {
        done++;
        if (bar) bar.style.width = Math.round(100 * done / total) + '%';
        next();
      });
    }
    next();
  }
  $$('[data-psi]').forEach(function (btn) {
    btn.addEventListener('click', function () { psiRun([{ route: btn.dataset.route || '/', device: btn.dataset.psi }]); });
  });
  $$('[data-psi-site]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var jobs = [];
      JSON.parse(btn.getAttribute('data-psi-site')).forEach(function (r) {
        jobs.push({ route: r, device: 'mobile' }, { route: r, device: 'desktop' });
      });
      psiRun(jobs);
    });
  });

  /* ------------------------------------------------- Selects that navigate */
  // Speed: show only the audits that affect one metric, as PageSpeed does.
  $$('.is-lh-filter button').forEach(function (b) {
    b.addEventListener('click', function () {
      var m = b.getAttribute('data-metric');
      $$('.is-lh-filter button').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
      $$('#lh-performance .is-lha[data-metrics]').forEach(function (row) {
        row.hidden = !!m && (' ' + row.getAttribute('data-metrics') + ' ').indexOf(' ' + m + ' ') < 0;
      });
    });
  });
  $$('select[data-nav]').forEach(function (sel) { sel.addEventListener('change', function () { location.href = sel.value; }); });
  $$('.is-db-row[data-href]').forEach(function (row) { row.addEventListener('click', function () { location.href = row.dataset.href; }); });

  /* ------------------------------------------------------ Audit filters */
  $$('button[data-cat]').forEach(function (b) {
    b.addEventListener('click', function () {
      $$('button[data-cat]').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
      var c = b.dataset.cat;
      $$('.is-issue').forEach(function (d) { d.hidden = !!c && d.dataset.cat !== c; });
      $$('.is-issues').forEach(function (g) {
        var any = $$('.is-issue', g).some(function (d) { return !d.hidden; });
        g.hidden = !any; if (g.previousElementSibling) g.previousElementSibling.hidden = !any;
      });
    });
  });
  if (location.hash && /^#issue-/.test(location.hash)) {
    var target = document.getElementById(location.hash.slice(1));
    if (target && target.tagName === 'DETAILS') target.open = true;
  }

  /* ------------------------------------------------------ Chart read-out */
  $$('.is-chart').forEach(function (fig) {
    var pts = JSON.parse(fig.dataset.points || '[]'), series = JSON.parse(fig.dataset.series || '[]');
    var plot = $('.is-chart-plot', fig), tip = $('.is-tip', fig), cross = $('.is-cross', fig), svg = $('svg', fig);
    if (!pts.length) return;
    function at(clientX) {
      var r = svg.getBoundingClientRect();
      var f = (clientX - r.left) / r.width, best = 0;
      pts.forEach(function (p, i) { if (Math.abs(p.x - f) < Math.abs(pts[best].x - f)) best = i; });
      var p = pts[best], vb = svg.viewBox.baseVal;
      cross.setAttribute('x1', p.x * vb.width); cross.setAttribute('x2', p.x * vb.width); cross.setAttribute('visibility', 'visible');
      tip.hidden = false;
      tip.innerHTML = '<b>' + esc(p.d) + '</b>' + series.map(function (s) { return '<span><i class="is-key is-' + s[2] + '"></i>' + esc(s[1]) + ' <b>' + p[s[0]] + '</b></span>'; }).join('');
      var x = p.x * r.width;
      tip.style.left = Math.max(0, Math.min(r.width - tip.offsetWidth, x - tip.offsetWidth / 2)) + 'px';
    }
    plot.addEventListener('pointermove', function (e) { at(e.clientX); });
    plot.addEventListener('pointerleave', function () { tip.hidden = true; cross.setAttribute('visibility', 'hidden'); });
  });

  /* -------------------------------------------------------- Media picker */
  $$('[data-pick-image]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!window.wp || !wp.media) return;
      var field = btn.closest('.is-control') || btn.closest('.is-section-aside') || btn.parentNode;
      var input = $('[data-image-id]', field) || $('[data-image-id]');
      var frame = wp.media({ title: 'Choose an image', library: { type: 'image' }, multiple: false, button: { text: 'Use this image' } });
      frame.on('select', function () {
        var a = frame.state().get('selection').first().toJSON();
        input.value = a.id;
        var url = (a.sizes && (a.sizes.large || a.sizes.medium || a.sizes.full) || a).url;
        var img = btn.closest('.is-editor-form') ? $('[data-share-img]') : $('img.is-thumb', field);
        if (btn.closest('.is-editor-form') && $('[data-share-img-hero]')) $('[data-share-img-hero]').src = url;
        if (img) { img.src = url; } else { var i = document.createElement('img'); i.className = 'is-thumb'; i.src = url; i.alt = ''; btn.before(i, ' '); }
      });
      frame.open();
    });
  });
  $$('[data-clear-image]').forEach(function (btn) {
    btn.addEventListener('click', function () { var i = $('[data-image-id]'); if (i) i.value = 0; btn.textContent = 'Saved on Save - the page\'s own card returns'; });
  });

  /* ============================================================ Analyser */
  var STOP = 'a an and are as at be by for from has have in is it its of on or that the to was were will with our your we you us i'.split(' ');
  var TRANSITIONS = ['also', 'because', 'but', 'so', 'then', 'however', 'therefore', 'instead', 'first', 'second', 'finally', 'for example', 'for instance', 'in fact', 'in short', 'as a result', 'that is why', 'meanwhile', 'besides', 'moreover', 'still', 'yet', 'after', 'before', 'while', 'once', 'until', 'since', 'whether', 'unless', 'although', 'though', 'even so', 'in other words', 'above all', 'next', 'together', 'from there', 'that way', 'which means', 'so that'];

  function norm(s) { return String(s || '').toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '').replace(/[’']/g, '').replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, ' ').trim(); }
  function words(s) { var n = norm(s); return n ? n.split(' ') : []; }
  function content(kp) { return words(kp).filter(function (w) { return STOP.indexOf(w) < 0; }); }
  // full: the phrase as written; part: every content word, any order; none.
  function match(hay, kp) {
    var h = ' ' + norm(hay) + ' ', k = norm(kp);
    if (!k) return 'none';
    if (h.indexOf(' ' + k + ' ') >= 0) return 'full';
    var cw = content(kp);
    if (cw.length && cw.every(function (w) { return h.indexOf(' ' + w) >= 0; })) return 'part';
    return 'none';
  }
  function count(hay, kp) {
    var h = ' ' + norm(hay) + ' ', k = ' ' + norm(kp) + ' ', n = 0, i = 0;
    if (k.trim() === '') return 0;
    while ((i = h.indexOf(k, i)) >= 0) { n++; i += k.length - 1; }
    return n;
  }
  var ctx = document.createElement('canvas').getContext('2d');
  function px(s, font) { ctx.font = font || '20px Arial'; return Math.round(ctx.measureText(s || '').width); }
  function syllables(w) {
    w = w.toLowerCase().replace(/[^a-z]/g, '');
    if (w.length <= 3) return 1;
    w = w.replace(/(?:[^laeiouy]es|ed|[^laeiouy]e)$/, '').replace(/^y/, '');
    var m = w.match(/[aeiouy]{1,2}/g);
    return Math.max(1, m ? m.length : 1);
  }
  function sentencesOf(paras) {
    var out = [];
    paras.forEach(function (p) {
      (p.match(/[^.!?]+[.!?]*/g) || []).forEach(function (s) { s = s.trim(); if (words(s).length >= 3) out.push(s); });
    });
    return out;
  }

  function analyse(page, v, phrases) {
    var title = v.title || page.builtTitle || '', desc = v.desc || page.builtDesc || '', kp = (v.kp || '').trim();
    var h1 = (page.headings || []).filter(function (h) { return h[0] === 1; }).map(function (h) { return h[1]; }).join(' ');
    var subs = (page.headings || []).filter(function (h) { return h[0] === 2 || h[0] === 3; });
    var prose = (page.paras || []).filter(function (p) { return words(p).length >= 8; });
    var intro = prose[0] || '';
    var total = page.words || words(page.text).length;
    var slug = page.route.replace(/[\/-]+/g, ' ');
    var seo = [], read = [];
    var G = 'Keyphrase';
    var add = function (list, s, t) { list.push({ s: s, t: t, g: G }); };
    var kind = page.kind;

    if (!page.audited) { add(seo, 'ok', 'No live text yet - the content checks run after the audit.'); }
    if (!kp) {
      // A legal page is found by name, not by a search it must win.
      if (kind !== 'legal') add(seo, 'bad', 'No focus keyphrase. Choose the one search this page should win - a phrase a parent would type.');
    } else {
      var kw = content(kp).length;
      if (kw > 6) add(seo, 'bad', 'The keyphrase is ' + kw + ' words. Long phrases are rarely searched as written - keep it to 2-4.');
      else if (kw > 4) add(seo, 'ok', 'The keyphrase has ' + kw + ' key words; 2-4 is the sweet spot.');
      var mt = match(title, kp);
      if (mt === 'full') {
        var pos = norm(title).indexOf(norm(kp));
        add(seo, pos <= norm(title).length / 2 ? 'good' : 'ok', pos <= norm(title).length / 2 ? 'The keyphrase leads the title.' : 'The keyphrase is in the title - move it nearer the start, where it is read and bolded first.');
      } else add(seo, mt === 'part' ? 'ok' : 'bad', mt === 'part' ? 'The title has the keyphrase\'s words, not the phrase itself.' : 'The keyphrase is not in the title - the strongest on-page signal there is.');
      var md = match(desc, kp);
      add(seo, md === 'full' ? 'good' : md === 'part' ? 'ok' : 'bad', md === 'full' ? 'The description uses the keyphrase (Google bolds it in the result).' : md === 'part' ? 'The description has the words but not the phrase.' : 'The description does not mention the keyphrase.');
      if (page.route !== '/') {
        var mu = match(slug, kp), cw = content(kp), inUrl = cw.filter(function (w) { return (' ' + slug + ' ').indexOf(' ' + w) >= 0; }).length;
        add(seo, mu !== 'none' ? 'good' : inUrl ? 'ok' : (kind === 'setting' ? 'bad' : 'ok'), mu !== 'none' ? 'The address carries the keyphrase.' : inUrl ? 'The address carries part of the keyphrase (' + page.route + ').' : 'The address (' + page.route + ') does not reflect the keyphrase' + (kind === 'setting' ? ' - for a nursery page, worth matching when the page is new.' : '.'));
      }
      if (page.audited) {
        var mh = match(h1, kp);
        add(seo, mh === 'full' ? 'good' : mh === 'part' ? 'ok' : 'bad', mh === 'full' ? 'The H1 uses the keyphrase.' : mh === 'part' ? 'The H1 has the words, not the phrase.' : h1 ? 'The H1 ("' + h1 + '") does not use the keyphrase.' : 'There is no H1 to carry the keyphrase.');
        var mi = match(intro, kp);
        add(seo, mi === 'full' ? 'good' : mi === 'part' ? 'ok' : 'bad', mi === 'full' ? 'The first paragraph uses the keyphrase.' : mi === 'part' ? 'The first paragraph has the words, not the phrase.' : 'The first paragraph does not mention the keyphrase - Google weighs the opening lines most.');
        var hs = subs.filter(function (h) { return match(h[1], kp) !== 'none'; }).length;
        if (subs.length) add(seo, hs ? 'good' : 'ok', hs ? hs + ' of ' + subs.length + ' subheadings use the keyphrase.' : 'No subheading uses the keyphrase.');
        var n = count(page.text, kp), dens = total ? 100 * n * Math.max(1, words(kp).length) / total : 0;
        if (!n) add(seo, 'bad', 'The keyphrase never appears in the page\'s text.');
        else if (dens > 3) add(seo, 'bad', 'The keyphrase appears ' + n + ' times (' + dens.toFixed(1) + '%) - that reads as stuffing.');
        else if (n < 2 && total > 150) add(seo, 'ok', 'The keyphrase appears once in ' + total + ' words; two or three natural uses help.');
        else add(seo, 'good', 'The keyphrase appears ' + n + ' time' + (n === 1 ? '' : 's') + ' (' + dens.toFixed(1) + '%).');
        var alts = (page.images || []).filter(function (i) { return i.alt && match(i.alt, kp) !== 'none'; }).length;
        if ((page.images || []).length) add(seo, alts ? 'good' : 'ok', alts ? 'An image\'s alt text uses the keyphrase.' : 'No image alt text uses the keyphrase.');
      }
      var others = Object.keys(phrases || {}).filter(function (r) { return r !== page.route && norm(phrases[r]) === norm(kp); });
      if (others.length) add(seo, 'bad', 'Also the keyphrase of ' + others.join(', ') + '. Two pages chasing one search compete with each other.');
      else add(seo, 'good', 'No other page targets this keyphrase.');
    }
    G = 'Title & description';
    var tpx = px(title);
    if (!title) add(seo, 'bad', 'No title.');
    else if (tpx > 600) add(seo, 'bad', 'The title is about ' + tpx + ' px wide; Google cuts at ~600 px, so the end is lost.');
    else if (title.length < 30) add(seo, 'ok', 'The title is short (' + title.length + ' characters) - room to say what and where.');
    else add(seo, 'good', 'The title fits (' + tpx + ' of ~600 px).');
    if (!desc) add(seo, 'bad', 'No description - Google will pick a sentence itself.');
    else if (desc.length > 158) add(seo, 'ok', 'The description is ' + desc.length + ' characters; past ~158 it is cut.');
    else if (desc.length < 120) add(seo, 'ok', 'The description is ' + desc.length + ' characters; 120-158 uses the space.');
    else add(seo, 'good', 'The description is ' + desc.length + ' characters.');
    G = 'Content & links';
    if (page.audited) {
      if (kind === 'setting') {
        add(seo, total >= 600 ? 'good' : total >= 300 ? 'ok' : 'bad', total + ' words. ' + (total >= 600 ? 'Enough depth to compete.' : 'Nursery pages that rank for "nursery in <town>" typically carry 600+ words: ages and sessions, a typical day, rooms and garden, funding, how to visit, questions parents ask.'));
      } else if (kind !== 'contact' && kind !== 'legal') {
        add(seo, total >= 250 ? 'good' : 'ok', total + ' words on the page.');
      }
      if (page.route !== '/') {
        var inl = (page.inlinks || []).length;
        add(seo, inl >= 2 ? 'good' : inl ? 'ok' : 'bad', inl ? 'Linked from ' + inl + ' other page' + (inl === 1 ? '' : 's') + '.' : 'No other page links here - it looks unimportant to Google.');
      }
      var noalt = (page.images || []).filter(function (i) { return i.alt === null; }).length;
      if ((page.images || []).length) add(seo, noalt ? 'ok' : 'good', noalt ? noalt + ' image' + (noalt === 1 ? ' has' : 's have') + ' no alt text.' : 'Every image has alt text.');
    }

    G = 'Readability';
    // Readability - on running prose only.
    var sent = sentencesOf(prose), sw = sent.map(function (s) { return words(s).length; });
    var proseWords = sw.reduce(function (a, b) { return a + b; }, 0);
    if (proseWords < 60) {
      add(read, 'ok', 'Only ' + proseWords + ' words of running text - too little to judge. Short, visual pages are read as captions.');
    } else {
      var syl = sent.reduce(function (a, s) { return a + words(s).reduce(function (b, w) { return b + syllables(w); }, 0); }, 0);
      var fre = 206.835 - 1.015 * (proseWords / sent.length) - 84.6 * (syl / proseWords);
      add(read, fre >= 60 ? 'good' : fre >= 50 ? 'ok' : 'bad', 'Flesch reading ease ' + Math.round(fre) + (fre >= 60 ? ' - plain, easy to read.' : fre >= 50 ? ' - fairly difficult. Shorter sentences and words help.' : ' - difficult. Break sentences up; prefer short words.'));
      var long = sw.filter(function (n) { return n > 20; }).length, lp = Math.round(100 * long / sent.length);
      add(read, lp <= 25 ? 'good' : lp <= 30 ? 'ok' : 'bad', lp + '% of sentences run over 20 words' + (lp <= 25 ? '.' : ' - aim for at most a quarter.'));
      var maxp = Math.max.apply(null, prose.map(function (p) { return words(p).length; }));
      add(read, maxp <= 120 ? 'good' : maxp <= 150 ? 'ok' : 'bad', maxp <= 120 ? 'Paragraphs are short (longest ' + maxp + ' words).' : 'The longest paragraph is ' + maxp + ' words; split anything over 120-150.');
      var passive = sent.filter(function (s) { return /\b(am|is|are|was|were|be|been|being|get|got)\s+(\w+ly\s+)?\w+(ed|en|wn|lt|nt)\b/i.test(s); }).length, pp = Math.round(100 * passive / sent.length);
      add(read, pp <= 10 ? 'good' : pp <= 15 ? 'ok' : 'bad', pp + '% passive voice' + (pp <= 10 ? '.' : ' - say who does what ("we edit the film", not "the film is edited").'));
      var tr = sent.filter(function (s) { var n = ' ' + norm(s) + ' '; return TRANSITIONS.some(function (t) { return n.indexOf(' ' + t + ' ') >= 0; }); }).length, tp = Math.round(100 * tr / sent.length);
      add(read, tp >= 30 ? 'good' : tp >= 20 ? 'ok' : 'bad', tp + '% of sentences use a transition word' + (tp >= 30 ? '.' : ' ("because", "so", "then", "for example") - they carry the reader from one idea to the next.'));
      var run = 1, worst = 1;
      for (var i = 1; i < sent.length; i++) { if (words(sent[i])[0] === words(sent[i - 1])[0]) { run++; worst = Math.max(worst, run); } else run = 1; }
      add(read, worst >= 3 ? 'ok' : 'good', worst >= 3 ? worst + ' sentences in a row start with the same word.' : 'Sentence openings vary.');
      var blocks = subs.length + 1, per = Math.round(total / blocks);
      if (total > 300) add(read, per <= 300 ? 'good' : 'ok', per <= 300 ? 'Subheadings break the text up (about ' + per + ' words per section).' : 'About ' + per + ' words per section - add subheadings every 300 words or so.');
    }
    function score(list) { if (!list.length) return null; var p = { good: 1, ok: 0.5, bad: 0 }; return Math.round(100 * list.reduce(function (a, c) { return a + p[c.s]; }, 0) / list.length); }
    return { seo: seo, read: read, seoScore: score(seo), readScore: score(read) };
  }
  function band(n) { return n === null ? '' : n >= 80 ? 'good' : n >= 50 ? 'ni' : 'poor'; }
  function ring(n, size, label) {
    var r = (size - 6) / 2, c = 2 * Math.PI * r, b = n === null ? 'none' : n >= 80 ? 'good' : n >= 50 ? 'fair' : 'weak';
    return '<span class="is-ring is-' + b + '" style="width:' + size + 'px;height:' + size + 'px" title="' + label + '"><svg viewBox="0 0 ' + size + ' ' + size + '" aria-hidden="true">'
      + '<circle cx="' + size / 2 + '" cy="' + size / 2 + '" r="' + r + '" class="is-ring-track"/>'
      + (n === null ? '' : '<circle cx="' + size / 2 + '" cy="' + size / 2 + '" r="' + r + '" class="is-ring-bar" stroke-dasharray="' + (c * n / 100).toFixed(2) + ' ' + c.toFixed(2) + '" transform="rotate(-90 ' + size / 2 + ' ' + size / 2 + ')"/>')
      + '</svg><b>' + (n === null ? '&ndash;' : n) + '</b><i>' + label + '</i></span>';
  }
  var ICO = {
    good: '<svg class="is-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    ok: '<svg class="is-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 7v6M12 17h.01"/></svg>',
    bad: '<svg class="is-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>'
  };
  var GICO = {
    'Keyphrase': 'M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z',
    'Title & description': 'M4 7V4h16v3M9 20h6M12 4v16',
    'Content & links': 'M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71',
    'Readability': 'M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2zM22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z'
  };
  function groups(list) {
    var order = { bad: 0, ok: 1, good: 2 }, by = {}, names = [];
    list.forEach(function (c) { if (!by[c.g]) { by[c.g] = []; names.push(c.g); } by[c.g].push(c); });
    return names.map(function (g) {
      var items = by[g].slice().sort(function (x, y) { return order[x.s] - order[y.s]; });
      var n = { good: 0, ok: 0, bad: 0 }; items.forEach(function (c) { n[c.s]++; });
      return '<details class="is-agroup"' + (n.bad || n.ok ? ' open' : '') + '><summary><svg class="is-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="' + (GICO[g] || '') + '"/></svg>' + esc(g)
        + '<span class="is-tally">' + (n.bad ? '<i class="b">' + n.bad + '</i>' : '') + (n.ok ? '<i class="o">' + n.ok + '</i>' : '') + (n.good ? '<i class="g">' + n.good + '</i>' : '') + '</span></summary><ul class="is-checks">'
        + items.map(function (c) { return '<li class="is-c-' + c.s + '"><span class="is-cdot">' + ICO[c.s] + '</span><span>' + esc(c.t) + '</span></li>'; }).join('') + '</ul></details>';
    }).join('');
  }
  function badge(n) {
    if (n === null) return '&ndash;';
    var b = band(n), w = { good: 'Good', ni: 'OK', poor: 'Weak' }[b];
    return '<span class="is-score is-v-' + b + '" title="' + w + '">' + n + '</span>';
  }

  /* ------------------------------------------------------- Pages: list */
  var listData = $('#is-pages-data');
  if (listData) {
    var L = JSON.parse(listData.textContent);
    L.pages.forEach(function (p) {
      var row = $('.is-frame[data-route="' + CSS.escape(p.route) + '"]');
      if (!row) return;
      var a = analyse(p, { title: p.title, desc: p.desc, kp: p.keyphrase }, L.phrases);
      $('[data-ring="seo"]', row).innerHTML = ring(a.seoScore, 40, 'SEO');
      $('[data-ring="read"]', row).innerHTML = ring(p.audited ? a.readScore : null, 40, 'Read');
    });
  }

  /* ----------------------------------------------------- Pages: editor */
  var pageData = $('#is-page-data');
  if (pageData) {
    var D = JSON.parse(pageData.textContent), P = D.page;
    var fTitle = $('[data-f="title"]'), fDesc = $('[data-f="desc"]'), fKp = $('[data-f="kp"]');
    var serp = $('.is-serp'), mode = 'desktop';
    function truncatePx(s, max, font) {
      if (px(s, font) <= max) return s;
      while (s.length && px(s + ' ...', font) > max) s = s.slice(0, -1);
      return s.replace(/\s+\S*$/, '') + ' ...';
    }
    function bold(text, kp) {
      var cw = content(kp);
      if (!cw.length) return esc(text);
      return esc(text).replace(new RegExp('\\b(' + cw.map(function (w) { return w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }).join('|') + ')\\b', 'gi'), '<b>$1</b>');
    }
    function meter(name, value, good, max) {
      var m = $('[data-meter="' + name + '"]'), c = $('[data-count="' + name + '"]');
      var pct = Math.min(100, Math.round(100 * value / max));
      m.querySelector('i').style.width = pct + '%';
      m.className = 'is-meter ' + (value === 0 ? '' : value > max ? 'is-over' : value >= good ? 'is-fit' : 'is-short');
      return c;
    }
    function render() {
      var v = { title: fTitle.value.trim(), desc: fDesc.value.trim(), kp: fKp.value.trim() };
      var title = v.title || P.builtTitle, desc = v.desc || P.builtDesc;
      var font = mode === 'desktop' ? '20px Arial' : '18px Arial', tmax = mode === 'desktop' ? 600 : 520;
      $('[data-serp-title]').textContent = truncatePx(title, tmax, font);
      var dmax = mode === 'desktop' ? 158 : 120;
      var d = desc.length > dmax ? desc.slice(0, dmax).replace(/\s+\S*$/, '') + ' ...' : desc;
      $('[data-serp-desc]').innerHTML = bold(d, v.kp);
      $('[data-share-title]').textContent = title;
      $('[data-share-desc]').textContent = desc;
      var tp = px(title);
      meter('title', tp, 380, 600).textContent = tp + ' / 600 px';
      meter('desc', desc.length, 120, 158).textContent = desc.length + ' / 158';
      var a = analyse(P, v, D.phrases);
      $('[data-an-list="seo"]').innerHTML = groups(a.seo);
      $('[data-an-list="read"]').innerHTML = groups(a.read);
      $('[data-ring="seo"]').innerHTML = ring(a.seoScore, 64, 'SEO');
      $('[data-ring="read"]').innerHTML = ring(P.audited ? a.readScore : null, 64, 'Readability');
    }
    [fTitle, fDesc, fKp].forEach(function (f) { f.addEventListener('input', render); });
    $$('[data-serp]').forEach(function (b) {
      b.addEventListener('click', function () {
        mode = b.dataset.serp;
        $$('[data-serp]').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
        serp.dataset.serpBox = mode;
        render();
      });
    });
    $$('[data-an]').forEach(function (b) {
      b.addEventListener('click', function () {
        $$('[data-an]').forEach(function (x) { x.setAttribute('aria-selected', x === b ? 'true' : 'false'); });
        $$('[data-an-list]').forEach(function (l) { l.hidden = l.dataset.anList !== b.dataset.an; });
      });
    });
    var dirty = false;
    [fTitle, fDesc, fKp].forEach(function (f) { f.addEventListener('input', function () { dirty = true; }); });
    $('.is-editor-form').addEventListener('submit', function () { dirty = false; });
    addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    if (document.fonts && document.fonts.load) { document.fonts.load('20px Arial').then(render, render); } else { render(); }
  }
})();
