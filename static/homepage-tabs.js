(function () {
  'use strict';

  function initTabs(container) {
    var buttons = container.querySelectorAll('.homepage-tab-btn');
    var panels = container.querySelectorAll('.homepage-tab-panel');
    if (!buttons.length || !panels.length) return;

    function activate(tabId) {
      buttons.forEach(function (btn) {
        var isActive = btn.getAttribute('data-tab') === tabId;
        btn.classList.toggle('is-active', isActive);
        btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        btn.setAttribute('tabindex', isActive ? '0' : '-1');
      });
      panels.forEach(function (panel) {
        var isActive = panel.getAttribute('data-panel') === tabId;
        panel.classList.toggle('is-active', isActive);
        if (isActive) {
          panel.removeAttribute('hidden');
        } else {
          panel.setAttribute('hidden', '');
        }
      });
    }

    buttons.forEach(function (btn, idx) {
      btn.addEventListener('click', function () {
        activate(btn.getAttribute('data-tab'));
      });
      btn.addEventListener('keydown', function (e) {
        var key = e.key;
        if (key !== 'ArrowLeft' && key !== 'ArrowRight' && key !== 'Home' && key !== 'End') return;
        e.preventDefault();
        var nextIdx = idx;
        if (key === 'ArrowLeft') nextIdx = (idx - 1 + buttons.length) % buttons.length;
        if (key === 'ArrowRight') nextIdx = (idx + 1) % buttons.length;
        if (key === 'Home') nextIdx = 0;
        if (key === 'End') nextIdx = buttons.length - 1;
        var next = buttons[nextIdx];
        activate(next.getAttribute('data-tab'));
        next.focus();
      });
    });
  }

  function init() {
    document.querySelectorAll('[data-homepage-tabs]').forEach(initTabs);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
