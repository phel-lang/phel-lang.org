(function () {
  'use strict';

  const MAX_RESULTS = 20;

  function init() {
    const container = document.querySelector('.api-page-search');
    if (!container) return;
    if (!Array.isArray(window.searchIndexApi)) return;

    const input = container.querySelector('.api-page-search__input');
    const results = container.querySelector('.api-page-search__results');
    const hint = container.querySelector('.api-page-search__hint');
    if (!input || !results) return;

    const entries = window.searchIndexApi.filter((e) => e.type === 'api');
    let active = -1;
    let matches = [];

    function score(entry, q) {
      const name = (entry.name || '').toLowerCase();
      const desc = (entry.desc || '').toLowerCase();
      const parts = name.split('/');
      const short = parts[parts.length - 1];
      if (name === q) return 1000;
      if (short === q) return 900;
      if (name.startsWith(q)) return 800 - (name.length - q.length);
      if (short.startsWith(q)) return 700 - (short.length - q.length);
      const idx = name.indexOf(q);
      if (idx !== -1) return 500 - idx;
      if (desc.indexOf(q) !== -1) return 200;
      let i = 0;
      for (let j = 0; j < name.length && i < q.length; j++) {
        if (name[j] === q[i]) i++;
      }
      return i === q.length ? 100 - (name.length - q.length) : -1;
    }

    function render() {
      results.innerHTML = '';
      if (matches.length === 0) {
        results.hidden = true;
        if (hint) hint.textContent = input.value ? 'No matches' : '';
        return;
      }
      results.hidden = false;
      const frag = document.createDocumentFragment();
      matches.forEach((m, i) => {
        const li = document.createElement('li');
        li.className = 'api-page-search__result' + (i === active ? ' is-active' : '');
        li.dataset.index = String(i);
        const sig = (m.signatures && m.signatures[0]) || '';
        li.innerHTML =
          '<a href="#' + encodeURIComponent(m.anchor || '') + '">' +
          '<span class="api-page-search__name">' + escapeHtml(m.name) + '</span>' +
          (sig ? '<span class="api-page-search__sig">' + escapeHtml(sig) + '</span>' : '') +
          '</a>';
        frag.appendChild(li);
      });
      results.appendChild(frag);
      if (hint) hint.textContent = matches.length + ' match' + (matches.length === 1 ? '' : 'es');
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

    function update() {
      const q = input.value.trim().toLowerCase();
      active = -1;
      if (!q) {
        matches = [];
        render();
        return;
      }
      matches = entries
        .map((e) => ({ e, s: score(e, q) }))
        .filter((x) => x.s >= 0)
        .sort((a, b) => b.s - a.s)
        .slice(0, MAX_RESULTS)
        .map((x) => x.e);
      if (matches.length > 0) active = 0;
      render();
    }

    function go(i) {
      const m = matches[i];
      if (!m) return;
      const anchor = m.anchor || '';
      window.location.hash = anchor;
      input.blur();
      results.hidden = true;
    }

    input.addEventListener('input', update);
    input.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (matches.length === 0) return;
        active = (active + 1) % matches.length;
        render();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (matches.length === 0) return;
        active = (active - 1 + matches.length) % matches.length;
        render();
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (active >= 0) go(active);
      } else if (e.key === 'Escape') {
        if (input.value) {
          input.value = '';
          update();
        } else {
          input.blur();
        }
      }
    });

    results.addEventListener('mousedown', (e) => {
      const li = e.target.closest('.api-page-search__result');
      if (!li) return;
      e.preventDefault();
      go(Number(li.dataset.index));
    });

    document.addEventListener('click', (e) => {
      if (!container.contains(e.target)) results.hidden = true;
    });

    input.addEventListener('focus', () => {
      if (matches.length > 0) results.hidden = false;
    });

    document.addEventListener(
      'keydown',
      (e) => {
        if (e.key !== '/') return;
        if (e.metaKey || e.ctrlKey || e.altKey) return;
        const ae = document.activeElement;
        if (
          ae &&
          (ae.tagName === 'INPUT' ||
            ae.tagName === 'TEXTAREA' ||
            ae.tagName === 'SELECT' ||
            ae.isContentEditable)
        ) {
          return;
        }
        e.preventDefault();
        e.stopPropagation();
        input.focus();
        input.select();
      },
      true
    );
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
