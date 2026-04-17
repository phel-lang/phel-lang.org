(function () {
  'use strict';

  function showToast(anchor, text) {
    const existing = anchor.querySelector('.zola-anchor__toast');
    if (existing) existing.remove();
    const toast = document.createElement('span');
    toast.className = 'zola-anchor__toast';
    toast.textContent = text;
    anchor.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('is-visible'));
    setTimeout(() => {
      toast.classList.remove('is-visible');
      setTimeout(() => toast.remove(), 250);
    }, 1200);
  }

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

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(
        () => showToast(anchor, 'Link copied'),
        () => showToast(anchor, 'Copy failed')
      );
    } else {
      showToast(anchor, 'Copy unsupported');
    }
  });
})();
