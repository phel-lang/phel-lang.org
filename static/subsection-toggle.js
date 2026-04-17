(function() {
  'use strict';

  function initSubsectionToggles() {
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

        if (isExpanded) {
          sectionItem.classList.add('collapsed');
          toggleButton.setAttribute('aria-expanded', 'false');
        } else {
          sectionItem.classList.remove('collapsed');
          toggleButton.setAttribute('aria-expanded', 'true');
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
