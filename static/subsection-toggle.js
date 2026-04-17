(function() {
  'use strict';

  const STORAGE_KEY = 'phel.sidebarCollapsed';

  function loadState() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) || {} : {};
    } catch (_) {
      return {};
    }
  }

  function saveState(state) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch (_) {}
  }

  function sectionKey(sectionItem) {
    const label = sectionItem.querySelector('.sidebar-section-label');
    return label ? (label.textContent || '').trim() : '';
  }

  function applyState(state) {
    document.querySelectorAll('.sidebar-section').forEach((sectionItem) => {
      if (sectionItem.classList.contains('active')) return;
      const key = sectionKey(sectionItem);
      if (!key || !(key in state)) return;
      const button = sectionItem.querySelector('.subsection-toggle');
      const collapsed = state[key] === true;
      if (collapsed) {
        sectionItem.classList.add('collapsed');
        if (button) button.setAttribute('aria-expanded', 'false');
      } else {
        sectionItem.classList.remove('collapsed');
        if (button) button.setAttribute('aria-expanded', 'true');
      }
    });
  }

  function initSubsectionToggles() {
    const state = loadState();
    applyState(state);

    const sectionHeaders = document.querySelectorAll('.sidebar-section-header');

    sectionHeaders.forEach(header => {
      header.addEventListener('click', function(e) {
        if (e.target.closest('a')) return;

        e.preventDefault();
        e.stopPropagation();

        const sectionItem = this.closest('.sidebar-section');
        const toggleButton = this.querySelector('.subsection-toggle');
        if (!toggleButton) return;

        const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';
        const key = sectionKey(sectionItem);

        if (isExpanded) {
          sectionItem.classList.add('collapsed');
          toggleButton.setAttribute('aria-expanded', 'false');
          if (key) {
            state[key] = true;
            saveState(state);
          }
        } else {
          sectionItem.classList.remove('collapsed');
          toggleButton.setAttribute('aria-expanded', 'true');
          if (key) {
            state[key] = false;
            saveState(state);
          }
        }
      });

      header.style.cursor = 'pointer';
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSubsectionToggles);
  } else {
    initSubsectionToggles();
  }
})();
