(function () {
  'use strict';

  document.addEventListener('click', (e) => {
    const anchor = e.target.closest('.zola-anchor');
    if (!anchor) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

    const href = anchor.getAttribute('href') || '';
    if (!href.startsWith('#')) return;

    e.preventDefault();
    const url = window.location.origin + window.location.pathname + href;

    history.replaceState(null, '', href);
    const target = document.getElementById(decodeURIComponent(href.slice(1)));
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
})();
