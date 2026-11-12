/* All saved sessions - the list, its search and its nearest-date sort. */
(function () {
  'use strict';
  var cfg = window.ldrnSessions;
  var root = document.getElementById('wwss');
  if (!cfg || !root) return;

  var table = root.querySelector('[data-table]');
  var search = root.querySelector('[data-search]');
  var dateBox = root.querySelector('[data-date]');
  var native = root.querySelector('[data-native]');
  var orderEl = root.querySelector('[data-order]');
  var countEl = root.querySelector('[data-count]');
  var rows = cfg.rows || [];
  var saving = false;

  function h(tag, attrs, kids) {
    var e = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      var v = attrs[k];
      if (v === null || v === undefined || v === false) return;
      if (k === 'class') e.className = v;
      else if (k === 'text') e.textContent = v;
      else if (k.indexOf('on') === 0) e.addEventListener(k.slice(2), v);
      else e.setAttribute(k, v === true ? '' : v);
    });
    (kids || []).forEach(function (c) { if (c) e.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
    return e;
  }

  function post(action, data) {
    var body = new URLSearchParams();
    body.set('action', action);
    body.set('nonce', cfg.nonce);
    body.set('rev', cfg.rev);
    Object.keys(data || {}).forEach(function (k) { body.set(k, data[k]); });
    return fetch(cfg.ajax, { method: 'POST', credentials: 'same-origin', body: body })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || !res.success) throw new Error((res && res.data && res.data.message) || 'That did not work.');
        return res.data;
      });
  }

  function parseDate(text) {
    var m = String(text || '').match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (!m) return null;
    var d = new Date(+m[3], +m[2] - 1, +m[1], 12);
    return isNaN(d) || d.getDate() !== +m[1] || d.getMonth() !== +m[2] - 1 ? null : d.getTime() / 1000;
  }

  function render() {
    var q = (search.value || '').trim().toLowerCase();
    var target = parseDate(dateBox.value);
    var list = rows.filter(function (r) {
      if (!q) return true;
      return (r.title + ' ' + r.summary + ' ' + r.pages.join(' ') + ' ' + r.kind + ' ' + r.user).toLowerCase().indexOf(q) !== -1;
    });
    if (target) {
      list = list.slice().sort(function (a, b) { return Math.abs(a.time - target) - Math.abs(b.time - target); });
      orderEl.textContent = 'Closest to ' + dateBox.value + ' first';
    } else {
      orderEl.textContent = 'Newest changes first';
    }
    countEl.textContent = list.length + (list.length === 1 ? ' result' : ' results');

    table.innerHTML = '';
    table.appendChild(h('div', { class: 'wwss-row wwss-row-head' }, [
      h('span', { text: 'Time' }), h('span', { text: 'Recent changes' }), h('span', { text: 'What changed' }),
      h('span', { class: 'wwss-c', text: 'Open' }), h('span', { class: 'wwss-c', text: 'Delete' }),
    ]));
    if (!list.length) {
      table.appendChild(h('p', { class: 'wwss-empty', text: rows.length ? 'Nothing matches that search.' : 'No saved sessions yet. The first Publish makes one.' }));
      return;
    }
    list.forEach(function (r) {
      var titleEl = h('strong', { class: 'wwss-name', text: r.title, title: 'Double-click to rename', tabindex: '0', onkeydown: function (e) { if (e.key === 'Enter' || e.key === 'F2') { e.preventDefault(); rename(r, titleEl); } }, ondblclick: function () { rename(r, titleEl); } });
      table.appendChild(h('div', { class: 'wwss-row' + (r.latest ? ' is-latest' : '') }, [
        h('div', {}, [
          h('div', { class: 'wwss-when', text: r.when }),
          h('div', { class: 'wwss-user', text: r.user }),
          r.role ? h('span', { class: 'wwss-role', text: r.role }) : null,
        ]),
        h('div', {}, [
          titleEl,
          h('div', { class: 'wwss-pages', text: r.pages.length ? r.pages.slice(0, 3).join(', ') + (r.pages.length > 3 ? ' +' + (r.pages.length - 3) : '') : 'Whole site' }),
          h('div', { class: 'wwss-tags' }, [
            r.kind ? h('span', { class: 'wwss-kind', text: r.kind }) : null,
            r.latest ? h('span', { class: 'wwss-kind is-live', text: 'Latest' }) : null,
          ]),
        ]),
        h('div', { class: 'wwss-summary', text: r.summary }),
        h('a', { class: 'wwss-open wwss-c', href: r.open, target: '_blank', rel: 'noopener', text: 'Open' }),
        h('button', { type: 'button', class: 'wwss-delete wwss-c', text: 'Delete', onclick: function () { remove(r); } }),
      ]));
    });
  }

  function remove(r) {
    if (!window.confirm('Delete the saved session “' + r.title + '”? The site itself does not change.')) return;
    post('ldrn_ws_session_delete', { id: r.id }).then(function () {
      rows = rows.filter(function (x) { return x.id !== r.id; });
      render();
    }).catch(function (e) { window.alert(e.message); });
  }

  function rename(r, el) {
    var input = h('input', { type: 'text', class: 'wwss-rename', value: r.title, 'aria-label': 'Rename saved session' });
    el.replaceWith(input);
    input.focus();
    input.select();
    var finished = false;
    function done(save) {
      if (finished) return;
      finished = true;
      var name = input.value.trim();
      if (save && name && name !== r.title) {
        input.disabled = true;
        post('ldrn_ws_session_rename', { id: r.id, name: name }).then(function () {
          r.title = name; render();
        }).catch(function (error) { render(); window.alert(error.message); });
      } else render();
    }
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') done(true);
      if (e.key === 'Escape') done(false);
    });
    input.addEventListener('blur', function () { done(true); });
  }

  /* "Save current site" - an inline name field, not a dialog. */
  var saveBtn = root.querySelector('[data-save]');
  saveBtn.addEventListener('click', function () {
    if (root.querySelector('.wwss-saveform')) return;
    var input = h('input', { type: 'text', class: 'wwss-rename', placeholder: 'Name this save, e.g. “Handover to client”' });
    var form = h('form', { class: 'wwss-saveform' }, [
      input,
      h('button', { type: 'submit', class: 'wwss-btn wwss-btn-primary', text: 'Save' }),
      h('button', { type: 'button', class: 'wwss-btn', text: 'Cancel', onclick: function () { form.remove(); } }),
    ]);
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (saving) return;
      saving = true;
      post('ldrn_ws_session_save', { name: input.value.trim() }).then(function () { window.location.reload(); })
        .catch(function (err) { saving = false; window.alert(err.message); });
    });
    root.querySelector('.wwss-filters').before(form);
    input.focus();
  });

  root.querySelector('[data-refresh]').addEventListener('click', function () { window.location.reload(); });
  search.addEventListener('input', render);
  dateBox.addEventListener('input', function () {
    // Type 01082026 and it becomes 01/08/2026.
    var digits = dateBox.value.replace(/\D/g, '').slice(0, 8);
    var out = digits.slice(0, 2) + (digits.length > 2 ? '/' + digits.slice(2, 4) : '') + (digits.length > 4 ? '/' + digits.slice(4) : '');
    if (out !== dateBox.value) dateBox.value = out;
    render();
  });
  root.querySelector('[data-cal]').addEventListener('click', function () {
    try { native.showPicker(); } catch (e) { native.focus(); native.click(); }
  });
  native.addEventListener('change', function () {
    var p = native.value.split('-');
    if (p.length === 3) { dateBox.value = p[2] + '/' + p[1] + '/' + p[0]; render(); }
  });

  render();
})();
