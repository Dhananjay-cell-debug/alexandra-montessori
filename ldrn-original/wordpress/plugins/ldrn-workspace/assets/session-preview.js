/* A saved session, open as the real website - a bar across the bottom that
   says so, with the one button that matters. Inlined by sessions.php into the
   page only for someone allowed to restore, and only on ?ldrn_session=… */
(function () {
  var s = window.__LDRN_SESSION__;
  if (!s) return;

  function el(tag, css, text) {
    var e = document.createElement(tag);
    if (css) e.style.cssText = css;
    if (text) e.textContent = text;
    return e;
  }

  function mount() {
    var bar = el('div',
      'position:fixed;left:50%;bottom:18px;transform:translateX(-50%);z-index:2147483646;display:flex;align-items:center;gap:14px;' +
      'max-width:calc(100% - 24px);padding:10px 12px 10px 18px;border-radius:12px;background:#1b1b1b;color:#eee;' +
      'font:500 13px/1.35 Inter,system-ui,-apple-system,"Segoe UI",sans-serif;box-shadow:0 18px 50px rgba(0,0,0,.35);flex-wrap:wrap');
    bar.setAttribute('translate', 'no');
    bar.id = 'ldrn-session-bar';

    var info = el('div', 'min-width:0');
    info.appendChild(el('div', 'font-size:10.5px;letter-spacing:.08em;text-transform:uppercase;color:#9aa;', 'Saved session · ' + s.when));
    info.appendChild(el('div', 'font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:46vw', s.title));
    bar.appendChild(info);

    var note = el('span', 'color:#aaa;font-size:12px', s.complete ? 'This is the site exactly as it was.' : 'Legacy save · design only.');
    bar.appendChild(note);

    var restore = el('button',
      'border:0;border-radius:8px;background:#0099ff;color:#fff;font:600 13px Inter,system-ui,sans-serif;padding:9px 14px;cursor:pointer',
      'Restore this version');
    restore.addEventListener('click', function () {
      if (!window.confirm('Put the whole site back to “' + s.title + '”?\n\nA backup of how it is right now is saved first, so this can be undone.')) return;
      restore.disabled = true;
      restore.textContent = 'Restoring…';
      var body = new URLSearchParams();
      body.set('action', 'ldrn_ws_session_restore');
      body.set('nonce', s.nonce);
      body.set('id', String(s.id));
      body.set('rev', s.rev);
      fetch(s.ajax, { method: 'POST', credentials: 'same-origin', body: body })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res && res.success) { window.location.href = res.data.redirect; return; }
          throw new Error((res && res.data && res.data.message) || 'Not restored.');
        })
        .catch(function (e) {
          restore.disabled = false;
          restore.textContent = 'Restore this version';
          note.textContent = e.message;
          note.style.color = '#ff8a8a';
        });
    });
    bar.appendChild(restore);

    var back = el('a', 'color:#ddd;text-decoration:none;padding:9px 10px;border-radius:8px;background:#2a2a2a', 'All saved sessions');
    back.href = s.back;
    bar.appendChild(back);

    document.body.appendChild(bar);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount);
  else mount();
})();
