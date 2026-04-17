(function () {
  'use strict';

  function init() {
    const activeLink = document.querySelector(
      '.site-navigation li.active > a, .site-navigation .sidebar-subsection > li.active > a'
    );
    if (!activeLink) return;

    if (document.querySelector('.api-ns-rail')) return;

    const content =
      document.querySelector('.two-column-layout__content') ||
      document.querySelector('.documentation__content') ||
      document.querySelector('main');
    if (!content) return;

    const headings = Array.from(content.querySelectorAll('h2[id]'));
    if (headings.length < 2) return;
    if (headings.length > 25) return;

    const ul = document.createElement('ul');
    ul.className = 'sidebar-mini-toc';
    headings.forEach((h) => {
      const li = document.createElement('li');
      li.className = 'sidebar-mini-toc__item';
      const a = document.createElement('a');
      a.href = '#' + encodeURIComponent(h.id);
      a.textContent = (h.textContent || '').replace(/#+\s*$/, '').trim();
      a.dataset.target = h.id;
      li.appendChild(a);
      ul.appendChild(li);
    });

    activeLink.insertAdjacentElement('afterend', ul);

    const linkMap = new Map();
    ul.querySelectorAll('a').forEach((a) => {
      const id = a.dataset.target;
      const target = document.getElementById(id);
      if (target) linkMap.set(target, a);
    });

    function setActive(a) {
      ul.querySelectorAll('a').forEach((x) => x.classList.remove('is-active'));
      if (a) a.classList.add('is-active');
    }

    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((e) => e.isIntersecting)
          .sort((a, b) => a.target.offsetTop - b.target.offsetTop);
        if (visible.length > 0) setActive(linkMap.get(visible[0].target));
      },
      { rootMargin: '-15% 0px -70% 0px', threshold: 0 }
    );

    linkMap.forEach((_, target) => observer.observe(target));
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
