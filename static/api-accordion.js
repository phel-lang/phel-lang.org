// API Namespace Accordion
document.addEventListener('DOMContentLoaded', function() {
  const toggleButtons = document.querySelectorAll('.api-namespace-toggle');
  
  toggleButtons.forEach(function(button) {
    // Check if content has overflow
    const content = button.nextElementSibling;
    if (content && content.classList.contains('api-namespace-content')) {
      // Check if content height exceeds the collapsed max-height (160px)
      if (content.scrollHeight > 160) {
        content.classList.add('has-overflow');
      }
      
      // Click on content area when collapsed expands it
      content.addEventListener('click', function(e) {
        const isCollapsed = button.getAttribute('aria-expanded') === 'false';
        if (isCollapsed && content.classList.contains('has-overflow')) {
          e.preventDefault();
          button.setAttribute('aria-expanded', 'true');
        }
      });
    }
    
    // Toggle functionality on button click
    button.addEventListener('click', function() {
      const isExpanded = this.getAttribute('aria-expanded') === 'true';
      this.setAttribute('aria-expanded', !isExpanded);
    });
  });
});

