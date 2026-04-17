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

    if (map.size === 0) return;

    function setActive(a) {
      links.forEach((x) => x.classList.remove('active'));
      if (a) a.classList.add('active');
    }

    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((e) => e.isIntersecting)
          .sort((a, b) => a.target.offsetTop - b.target.offsetTop);
        if (visible.length > 0) setActive(map.get(visible[0].target));
      },
      { rootMargin: '-15% 0px -70% 0px', threshold: 0 }
    );

    map.forEach((_, target) => observer.observe(target));
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
