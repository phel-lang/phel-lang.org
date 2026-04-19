(function () {
  'use strict';

  function initTabs(container) {
    var buttons = container.querySelectorAll('.phel-terminal-tab, .homepage-tab-btn');
    var panels = container.querySelectorAll('.phel-terminal-session, .homepage-tab-panel');
    if (!buttons.length || !panels.length) return;

    var tabIds = Array.prototype.map.call(buttons, function (b) {
      return b.getAttribute('data-tab');
    });

    function activate(tabId, updateHash) {
      if (tabIds.indexOf(tabId) === -1) return;
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
      if (updateHash && window.history && window.history.replaceState) {
        window.history.replaceState(null, '', '#tab-' + tabId);
      }
    }

    function tabIdFromHash() {
      var h = window.location.hash || '';
      if (h.indexOf('#tab-') !== 0) return null;
      var id = h.slice(5);
      return tabIds.indexOf(id) !== -1 ? id : null;
    }

    buttons.forEach(function (btn, idx) {
      btn.addEventListener('click', function () {
        activate(btn.getAttribute('data-tab'), true);
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
        activate(next.getAttribute('data-tab'), true);
        next.focus();
      });
    });

    var initial = tabIdFromHash();
    if (initial) activate(initial, false);

    window.addEventListener('hashchange', function () {
      var id = tabIdFromHash();
      if (id) activate(id, false);
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
