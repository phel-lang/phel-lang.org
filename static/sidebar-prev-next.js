(function () {
  'use strict';

  function collectLinks() {
    const nav = document.querySelector('.site-navigation');
    if (!nav) return [];
    const links = Array.from(nav.querySelectorAll('a[href]'));
    return links
      .map((a) => a.getAttribute('href') || '')
      .filter((h) => h && !h.startsWith('#'));
  }

  function currentIndex(links) {
    const path = window.location.pathname.replace(/\/+$/, '');
    for (let i = 0; i < links.length; i++) {
      const href = links[i].replace(/\/+$/, '');
      try {
        const linkPath = new URL(href, window.location.origin).pathname.replace(/\/+$/, '');
        if (linkPath === path) return i;
      } catch (_) {}
    }
    return -1;
  }

  function go(delta) {
    const links = collectLinks();
    if (links.length === 0) return;
    const idx = currentIndex(links);
    if (idx === -1) return;
    const next = idx + delta;
    if (next < 0 || next >= links.length) return;
    window.location.href = links[next];
  }

  document.addEventListener('keydown', (e) => {
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
    if (e.key === '[') {
      e.preventDefault();
      go(-1);
    } else if (e.key === ']') {
      e.preventDefault();
      go(1);
    }
  });
})();
