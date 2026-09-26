(function () {
  'use strict';

  function init() {
    const toc = document.querySelector('.page-toc');
    if (!toc) return;

    const links = Array.from(toc.querySelectorAll('a[href^="#"]'));
    if (links.length === 0) return;

    const map = new Map();
    links.forEach((a) => {
      const id = decodeURIComponent((a.getAttribute('href') || '').slice(1));
      if (!id) return;
      const target = document.getElementById(id);
      if (target) map.set(target, a);
    });

    if (map.size === 0) {
      return;
    }

    const lastLink = links[links.length - 1];

    // A long TOC scrolls inside its sticky rail; keep the active entry in
    // view there. Only the rail scrolls, never the page.
    function keepInView(a) {
      if (toc.scrollHeight <= toc.clientHeight) return;
      const box = toc.getBoundingClientRect();
      const item = a.getBoundingClientRect();
      const pad = 24;
      if (item.top < box.top) {
        toc.scrollTop -= box.top - item.top + pad;
      } else if (item.bottom > box.bottom) {
        toc.scrollTop += item.bottom - box.bottom + pad;
      }
    }

    function setActive(a) {
      links.forEach((x) => {
        x.classList.remove('active');
        x.removeAttribute('aria-current');
      });
      if (!a) return;
      a.classList.add('active');
      a.setAttribute('aria-current', 'true');
      keepInView(a);
    }

    // The last sections are often too short to cross the observer band, so
    // reaching the end of the page marks the last entry directly.
    function atPageEnd() {
      return window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2;
    }

    let suppressUntil = 0;

    const observer = new IntersectionObserver(
      (entries) => {
        if (Date.now() < suppressUntil) return;
        if (atPageEnd()) {
          setActive(lastLink);
          return;
        }
        const visible = entries
          .filter((e) => e.isIntersecting)
          .sort((a, b) => a.target.offsetTop - b.target.offsetTop);
        if (visible.length > 0) setActive(map.get(visible[0].target));
      },
      { rootMargin: '-15% 0px -70% 0px', threshold: 0 }
    );

    map.forEach((_, target) => observer.observe(target));

    let ticking = false;
    window.addEventListener(
      'scroll',
      () => {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(() => {
          ticking = false;
          if (Date.now() >= suppressUntil && atPageEnd()) setActive(lastLink);
        });
      },
      { passive: true }
    );

    links.forEach((a) => {
      a.addEventListener('click', () => {
        setActive(a);
        suppressUntil = Date.now() + 800;
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
