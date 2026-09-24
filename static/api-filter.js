(function () {
  'use strict';

  function init() {
    const root = document.querySelector('[data-api-filter]');
    const fns = document.getElementById('api-fns');
    if (!root || !fns) return;

    const input = root.querySelector('.api-filter__input');
    const clearBtn = root.querySelector('.api-filter__clear');
    const count = root.querySelector('.api-filter__count');
    const empty = document.querySelector('[data-api-filter-empty]');
    const emptyQuery = empty.querySelector('code');

    // Wrap each function heading and the nodes after it, up to the next
    // heading, so one match shows or hides the whole entry.
    const entries = [];
    let current = null;
    Array.from(fns.childNodes).forEach(node => {
      if (node.nodeType === Node.ELEMENT_NODE && node.tagName === 'H3') {
        current = document.createElement('div');
        current.className = 'api-fn';
        fns.insertBefore(current, node);
        const code = node.querySelector('code');
        const full = (code ? code.textContent : node.textContent).trim().toLowerCase();
        const slash = full.indexOf('/');
        entries.push({
          el: current,
          full,
          name: slash > 0 ? full.slice(slash + 1) : full,
        });
      }
      if (current) current.appendChild(node);
    });
    if (entries.length === 0) return;

    // The jump list mirrors the headings in order. Pair by position: a few
    // symbol-only names (`%`) get an empty or shared anchor id.
    const tocItems = document.querySelectorAll('.api-namespace-toc__list > li');
    if (tocItems.length === entries.length) {
      entries.forEach((entry, i) => {
        entry.tocItem = tocItems[i];
      });
    }

    const total = entries.length;
    const tocTitle = document.querySelector('.api-namespace-toc__title');

    function apply() {
      const raw = input.value.trim();
      const q = raw.toLowerCase();
      // "map" matches names only; "core/ma" opts into the qualified name.
      const field = q.includes('/') ? 'full' : 'name';
      let shown = 0;

      entries.forEach(entry => {
        const match = q === '' || entry[field].includes(q);
        entry.el.hidden = !match;
        if (entry.tocItem) entry.tocItem.hidden = !match;
        if (match) shown++;
      });

      clearBtn.hidden = raw === '';
      count.textContent = q === '' ? total + ' functions' : shown + ' of ' + total;
      if (tocTitle) {
        tocTitle.textContent =
          'Jump to function (' + (q === '' ? total : shown + ' of ' + total) + ')';
      }
      empty.hidden = shown > 0;
      emptyQuery.textContent = raw;
    }

    // Keep the first result in view when typing from deep in the page.
    // Instant: the site-wide smooth scrolling would lag behind each keystroke.
    // Measure against where the bar sticks, not where it is: near the page
    // end the bar can sit higher, pushed up by its container.
    function revealResults() {
      const stuckBottom = parseFloat(getComputedStyle(root).top) + root.offsetHeight;
      const top = fns.getBoundingClientRect().top;
      if (top < stuckBottom) window.scrollBy({ top: top - stuckBottom, behavior: 'instant' });
    }

    function clear() {
      input.value = '';
      apply();
    }

    input.addEventListener('input', () => {
      apply();
      revealResults();
    });

    input.addEventListener('keydown', e => {
      if (e.key !== 'Escape') return;
      e.preventDefault();
      if (input.value === '') {
        input.blur();
      } else {
        clear();
      }
    });

    clearBtn.addEventListener('click', () => {
      clear();
      input.focus();
    });

    empty.querySelector('button').addEventListener('click', () => {
      clear();
      input.focus();
    });

    // A see-also link or a shared URL can point at a filtered-out entry.
    window.addEventListener('hashchange', () => {
      const target = document.getElementById(decodeURIComponent(location.hash.slice(1)));
      if (target && target.closest('.api-fn[hidden]')) {
        clear();
        target.scrollIntoView();
      }
    });

    // Back/forward navigation can restore a typed query.
    apply();
    root.hidden = false;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
