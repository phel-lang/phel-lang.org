(function () {
  'use strict';

  const KINDS = ['function', 'macro'];
  const STORAGE_KEY = 'phel.apiFilters';

  function init() {
    const content = document.querySelector('.two-column-layout__content');
    if (!content) return;
    const apiIndex = content.querySelector('.api-index');
    if (!apiIndex) return;
    if (!Array.isArray(window.searchIndexApi)) return;

    const kindByAnchor = new Map();
    window.searchIndexApi.forEach((e) => {
      if (e.type === 'api' && e.anchor != null && e.kind) {
        kindByAnchor.set(e.anchor, e.kind);
      }
    });
    if (kindByAnchor.size === 0) return;

    groupEntries(content, kindByAnchor);

    const saved = loadSaved();
    const state = {
      function: saved.function !== false,
      macro: saved.macro !== false,
    };

    const bar = renderBar(state, (next) => {
      Object.assign(state, next);
      applyFilter(state);
      save(state);
    });
    apiIndex.parentNode.insertBefore(bar, apiIndex);

    applyFilter(state);
  }

  function groupEntries(content, kindByAnchor) {
    const headings = Array.from(content.querySelectorAll('h3[id]'));
    headings.forEach((h3) => {
      const id = h3.id;
      const kind = kindByAnchor.get(id);
      if (!kind) return;
      const wrapper = document.createElement('div');
      wrapper.className = 'api-entry';
      wrapper.dataset.kind = kind;
      wrapper.dataset.anchor = id;
      h3.parentNode.insertBefore(wrapper, h3);
      let n = h3;
      while (n) {
        const next = n.nextSibling;
        wrapper.appendChild(n);
        if (next && next.nodeType === 1 && /^H[23]$/.test(next.tagName)) break;
        n = next;
      }
    });
  }

  function renderBar(state, onChange) {
    const bar = document.createElement('div');
    bar.className = 'api-filters';
    bar.innerHTML =
      '<span class="api-filters__label">Filter</span>' +
      KINDS.map(
        (k) =>
          '<label class="api-filters__chip" data-kind="' + k + '">' +
          '<input type="checkbox" ' + (state[k] ? 'checked' : '') + ' data-kind="' + k + '"/>' +
          '<span>' + (k === 'function' ? 'Functions' : 'Macros') + '</span>' +
          '<span class="api-filters__count" data-count-for="' + k + '"></span>' +
          '</label>'
      ).join('');
    bar.addEventListener('change', (e) => {
      const input = e.target.closest('input[type="checkbox"]');
      if (!input) return;
      onChange({ [input.dataset.kind]: input.checked });
    });
    return bar;
  }

  function applyFilter(state) {
    const entries = document.querySelectorAll('.api-entry');
    const counts = { function: 0, macro: 0 };
    entries.forEach((el) => {
      const k = el.dataset.kind;
      if (counts[k] != null) counts[k]++;
      el.hidden = !state[k];
    });

    const pills = document.querySelectorAll('.api-namespace__content a[href^="#"]');
    pills.forEach((a) => {
      const li = a.closest('li');
      if (!li) return;
      const id = decodeURIComponent((a.getAttribute('href') || '').slice(1));
      const entry = document.querySelector('.api-entry[data-anchor="' + cssEscape(id) + '"]');
      if (!entry) return;
      li.hidden = entry.hidden;
    });

    document.querySelectorAll('.api-index__entry').forEach((ns) => {
      const any = Array.from(ns.querySelectorAll('.api-namespace__content > li')).some(
        (li) => !li.hidden
      );
      ns.hidden = !any;
    });

    KINDS.forEach((k) => {
      const el = document.querySelector('[data-count-for="' + k + '"]');
      if (el) el.textContent = counts[k];
    });
  }

  function cssEscape(s) {
    if (window.CSS && CSS.escape) return CSS.escape(s);
    return String(s).replace(/[^a-zA-Z0-9_-]/g, (c) => '\\' + c);
  }

  function save(state) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch (_) {}
  }

  function loadSaved() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}') || {};
    } catch (_) {
      return {};
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
