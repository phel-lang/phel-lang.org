(function () {
  'use strict';

  function init() {
    const input = document.querySelector('.site-navigation-filter__input');
    const nav = document.querySelector('.site-navigation');
    if (!input || !nav) return;

    const directItems = Array.from(nav.querySelectorAll(':scope > li.site-navigation__entry'));
    const sections = Array.from(nav.querySelectorAll(':scope > li.sidebar-section'));

    function apply() {
      const q = input.value.trim().toLowerCase();
      const active = q.length > 0;

      directItems.forEach((li) => {
        if (li.classList.contains('sidebar-section')) return;
        if (!active) {
          li.hidden = false;
          return;
        }
        const text = (li.textContent || '').toLowerCase();
        li.hidden = text.indexOf(q) === -1;
      });

      sections.forEach((sec) => {
        const label = sec.querySelector('.sidebar-section-label');
        const subs = Array.from(sec.querySelectorAll('.sidebar-subsection > li'));
        const labelText = (label ? label.textContent : '').toLowerCase();
        let anyVisible = false;

        subs.forEach((li) => {
          if (!active) {
            li.hidden = false;
            anyVisible = true;
            return;
          }
          const match = (li.textContent || '').toLowerCase().indexOf(q) !== -1;
          li.hidden = !match;
          if (match) anyVisible = true;
        });

        if (active) {
          const selfMatch = labelText.indexOf(q) !== -1;
          if (selfMatch) {
            subs.forEach((li) => (li.hidden = false));
            anyVisible = true;
          }
          sec.hidden = !anyVisible && !selfMatch;
          if (anyVisible) {
            sec.classList.remove('collapsed');
            const btn = sec.querySelector('.subsection-toggle');
            if (btn) btn.setAttribute('aria-expanded', 'true');
          }
        } else {
          sec.hidden = false;
        }
      });
    }

    input.addEventListener('input', apply);
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        input.value = '';
        apply();
        input.blur();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
