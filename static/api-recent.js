(function () {
  'use strict';

  const STORAGE_KEY = 'phel.apiRecent';
  const MAX = 10;

  async function loadApiData() {
    if (Array.isArray(window.searchIndexApi)) return window.searchIndexApi;
    try {
      const res = await fetch('/api_search.json');
      if (!res.ok) return [];
      const data = await res.json();
      window.searchIndexApi = data;
      return data;
    } catch (_) {
      return [];
    }
  }

  async function init() {
    const content = document.querySelector('.two-column-layout__content');
    if (!content) return;
    const apiIndex = content.querySelector('.api-index');
    if (!apiIndex) return;

    const data = await loadApiData();
    if (!Array.isArray(data) || data.length === 0) return;

    const byAnchor = new Map();
    data.forEach((e) => {
      if (e.type === 'api' && e.anchor != null) byAnchor.set(e.anchor, e);
    });
    if (byAnchor.size === 0) return;

    const container = document.createElement('div');
    container.className = 'api-recent';
    container.hidden = true;
    container.innerHTML =
      '<div class="api-recent__head">' +
        '<span class="api-recent__label">Recently viewed</span>' +
        '<button type="button" class="api-recent__clear" aria-label="Clear recently viewed">Clear</button>' +
      '</div>' +
      '<ul class="api-recent__list"></ul>';

    const anchorBar = content.querySelector('.api-page-search');
    if (anchorBar) anchorBar.insertAdjacentElement('afterend', container);
    else apiIndex.parentNode.insertBefore(container, apiIndex);

    function render() {
      const list = load();
      const ul = container.querySelector('.api-recent__list');
      ul.innerHTML = '';
      if (list.length === 0) {
        container.hidden = true;
        return;
      }
      container.hidden = false;
      const frag = document.createDocumentFragment();
      list.forEach((anchor) => {
        const e = byAnchor.get(anchor);
        if (!e) return;
        const li = document.createElement('li');
        li.innerHTML =
          '<a href="#' + encodeURIComponent(anchor) + '">' +
          '<span class="api-recent__name">' + escapeHtml(e.name) + '</span>' +
          '</a>';
        frag.appendChild(li);
      });
      ul.appendChild(frag);
    }

    function escapeHtml(s) {
      return String(s).replace(/[&<>"']/g, (c) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
      }[c]));
    }

    function track() {
      const id = decodeURIComponent((window.location.hash || '').slice(1));
      if (!id || !byAnchor.has(id)) return;
      const list = load().filter((a) => a !== id);
      list.unshift(id);
      save(list.slice(0, MAX));
      render();
    }

    function load() {
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? JSON.parse(raw) || [] : [];
      } catch (_) {
        return [];
      }
    }

    function save(list) {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
      } catch (_) {}
    }

    container.querySelector('.api-recent__clear').addEventListener('click', () => {
      save([]);
      render();
    });

    window.addEventListener('hashchange', track);
    track();
    render();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
