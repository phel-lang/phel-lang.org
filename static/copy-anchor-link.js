(function () {
  'use strict';

  function announce(message) {
    if (typeof window.announceStatus === 'function') window.announceStatus(message);
  }

  // Clicking a heading's "#" puts the section URL in the address bar and on
  // the clipboard, then confirms through the shared live region.
  document.addEventListener('click', async (e) => {
    const anchor = e.target.closest('.zola-anchor');
    if (!anchor) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

    const href = anchor.getAttribute('href') || '';
    if (!href.startsWith('#')) return;

    e.preventDefault();
    const url = window.location.origin + window.location.pathname + href;

    history.replaceState(null, '', href);
    const target = document.getElementById(decodeURIComponent(href.slice(1)));
    if (target) {
      const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
    }

    try {
      await navigator.clipboard.writeText(url);
      anchor.classList.add('copied');
      setTimeout(() => anchor.classList.remove('copied'), 2000);
      announce('Link to section copied');
    } catch (_) {
      announce('Could not copy the link. It is in the address bar.');
    }
  });
})();
