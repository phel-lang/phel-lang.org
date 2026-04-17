(function () {
  'use strict';

  function flash() {
    const hash = window.location.hash;
    if (!hash || hash.length < 2) return;
    let target;
    try {
      target = document.getElementById(decodeURIComponent(hash.slice(1)));
    } catch (_) {
      return;
    }
    if (!target) return;
    target.classList.remove('anchor-flash');
    void target.offsetWidth;
    target.classList.add('anchor-flash');
    setTimeout(() => target.classList.remove('anchor-flash'), 1600);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', flash);
  } else {
    flash();
  }
  window.addEventListener('hashchange', flash);
})();
