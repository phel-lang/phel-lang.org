// Sidebar toggle functionality for mobile
(function() {
  'use strict';

  function initSidebarToggle() {
    const toggleButton = document.getElementById('sidebar-toggle');
    const sidebarContent = document.getElementById('sidebar-content');

    if (!toggleButton || !sidebarContent) {
      return;
    }

    toggleButton.addEventListener('click', function(e) {
      e.preventDefault();
      const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';
      
      toggleButton.setAttribute('aria-expanded', !isExpanded);
      sidebarContent.classList.toggle('active');
    });
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebarToggle);
  } else {
    initSidebarToggle();
  }
})();

