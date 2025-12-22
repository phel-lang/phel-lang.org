(function() {
  'use strict';

  function initSubsectionToggles() {
    const toggleButtons = document.querySelectorAll('.subsection-toggle');
    
    toggleButtons.forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const sectionItem = this.closest('.sidebar-section');
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        
        // Toggle the collapsed state
        if (isExpanded) {
          sectionItem.classList.add('collapsed');
          this.setAttribute('aria-expanded', 'false');
        } else {
          sectionItem.classList.remove('collapsed');
          this.setAttribute('aria-expanded', 'true');
        }
      });
    });
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSubsectionToggles);
  } else {
    initSubsectionToggles();
  }
})();
