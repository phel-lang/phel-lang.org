/**
 * Scroll the active navigation item into view on page load
 */
(function() {
  'use strict';

  function scrollActiveNavIntoView() {
    // Find the active navigation item in the sidebar
    const activeNavItem = document.querySelector('.site-navigation__entry.active');

    if (activeNavItem) {
      // Scroll it into view with some padding
      activeNavItem.scrollIntoView({
        behavior: 'auto',
        block: 'center',
        inline: 'nearest'
      });
    }
  }

  // Run when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scrollActiveNavIntoView);
  } else {
    // DOM is already ready
    scrollActiveNavIntoView();
  }
})();
