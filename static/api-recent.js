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

  function load() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      const parsed = raw ? JSON.parse(raw) : [];
      // Migrate legacy shape (array of anchor strings) to {path,name} records.
      if (Array.isArray(parsed) && parsed.length > 0 && typeof parsed[0] === 'string') {
        return [];
      }
      return Array.isArray(parsed) ? parsed : [];
    } catch (_) {
      return [];
    }
  }

  function save(list) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
    } catch (_) {}
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

  function pathnameOf(path) {
    if (!path) return '';
    const hashIdx = path.indexOf('#');
    return hashIdx === -1 ? path : path.slice(0, hashIdx);
  }

  async function trackCurrentView(data) {
    if (!Array.isArray(data) || data.length === 0) return;
    const pathname = location.pathname.replace(/\/+$/, '/');
    const hash = location.hash || '';

    // Match on full path+hash if present, otherwise just the namespace pathname.
    const fullPath = pathname + hash;
    const hit = data.find((e) => e.type === 'api' && e.path === fullPath)
      || data.find((e) => e.type === 'api' && pathnameOf(e.path) === pathname);
    if (!hit || !hit.path) return;

    const list = load().filter((entry) => entry.path !== hit.path);
    list.unshift({ path: hit.path, name: hit.name });
    save(list.slice(0, MAX));
  }

  function renderRecentBar(container) {
    const list = load();
    const ul = container.querySelector('.api-recent__list');
    ul.innerHTML = '';
    if (list.length === 0) {
      container.hidden = true;
      return;
    }
    container.hidden = false;
    const frag = document.createDocumentFragment();
    list.forEach((entry) => {
      if (!entry || !entry.path || !entry.name) return;
      const li = document.createElement('li');
      li.innerHTML =
        '<a href="' + escapeHtml(entry.path) + '">' +
        '<span class="api-recent__name">' + escapeHtml(entry.name) + '</span>' +
        '</a>';
      frag.appendChild(li);
    });
    ul.appendChild(frag);
  }

  function mountRecentBar(content) {
    const container = document.createElement('div');
    container.className = 'api-recent';
    container.hidden = true;
    container.innerHTML =
      '<div class="api-recent__head">' +
        '<span class="api-recent__label">Recently viewed</span>' +
        '<button type="button" class="api-recent__clear" aria-label="Clear recently viewed">Clear</button>' +
      '</div>' +
      '<ul class="api-recent__list"></ul>';

    // Prefer placing the bar directly above the namespace grid; fall back to
    // after the page H1; last resort, top of content.
    const grid = content.querySelector('.api-namespace-grid');
    const heading = content.querySelector('h1');
    if (grid && grid.parentNode) {
      grid.parentNode.insertBefore(container, grid);
    } else if (heading) {
      heading.insertAdjacentElement('afterend', container);
    } else {
      content.insertBefore(container, content.firstChild);
    }

    container.querySelector('.api-recent__clear').addEventListener('click', () => {
      save([]);
      renderRecentBar(container);
    });

    return container;
  }

  async function init() {
    const isApiArea = location.pathname.startsWith('/documentation/reference/api/');
    if (!isApiArea) return;

    const data = await loadApiData();
    if (!Array.isArray(data) || data.length === 0) return;

    await trackCurrentView(data);

    // Only render the "Recently viewed" bar on the API index page.
    const isIndex = location.pathname.replace(/\/+$/, '/') === '/documentation/reference/api/';
    if (!isIndex) return;

    const content = document.querySelector('.two-column-layout__content');
    if (!content) return;

    const bar = mountRecentBar(content);
    renderRecentBar(bar);

    window.addEventListener('hashchange', async () => {
      await trackCurrentView(data);
      renderRecentBar(bar);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
