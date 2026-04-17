(function () {
  'use strict';

  function init() {
    const rail = document.querySelector('.api-ns-rail');
    if (!rail) return;

    const items = Array.from(rail.querySelectorAll('.api-ns-rail__item'));
    if (items.length === 0) return;

    const map = new Map();
    items.forEach((li) => {
      const a = li.querySelector('a');
      if (!a) return;
      const href = a.getAttribute('href') || '';
      const id = href.startsWith('#') ? decodeURIComponent(href.slice(1)) : '';
      if (!id) return;
      const target = document.getElementById(id);
      if (target) map.set(target, li);
    });

    if (map.size === 0) return;

    function setActive(li) {
      items.forEach((x) => x.classList.remove('is-active'));
      if (li) li.classList.add('is-active');
    }

    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((e) => e.isIntersecting)
          .sort((a, b) => a.target.offsetTop - b.target.offsetTop);
        if (visible.length > 0) {
          setActive(map.get(visible[0].target));
        }
      },
      { rootMargin: '-20% 0px -70% 0px', threshold: 0 }
    );

    map.forEach((_, target) => observer.observe(target));

    rail.addEventListener('click', (e) => {
      const a = e.target.closest('a[href^="#"]');
      if (!a) return;
      const li = a.closest('.api-ns-rail__item');
      if (li) setActive(li);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
