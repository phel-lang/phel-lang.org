// API Namespace Accordion
document.addEventListener('DOMContentLoaded', function() {
  const toggleButtons = document.querySelectorAll('.api-namespace__toggle');
  
  toggleButtons.forEach(function(button) {
    // Check if content has overflow
    const content = button.nextElementSibling;
    const toggleText = button.querySelector('.api-namespace__toggle-text');
    
    if (content && content.classList.contains('api-namespace__content')) {
      // Check if content height exceeds the collapsed max-height (160px)
      if (content.scrollHeight > 160) {
        content.classList.add('has-overflow');
        
        // Set initial text based on aria-expanded state
        updateToggleText(button, toggleText);
      } else {
        // Hide toggle text if no overflow
        if (toggleText) {
          toggleText.style.display = 'none';
        }
      }
      
      // Click on content area when collapsed expands it
      content.addEventListener('click', function(e) {
        const isCollapsed = button.getAttribute('aria-expanded') === 'false';
        if (isCollapsed && content.classList.contains('has-overflow')) {
          e.preventDefault();
          button.setAttribute('aria-expanded', 'true');
          updateToggleText(button, toggleText);
        }
      });
    }
    
    // Toggle functionality on button click - ONLY for items with overflow
    button.addEventListener('click', function() {
      const content = this.nextElementSibling;
      if (content && content.classList.contains('has-overflow')) {
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', !isExpanded);
        updateToggleText(this, toggleText);
      }
    });
  });
  
  // Helper function to update toggle text
  function updateToggleText(button, toggleText) {
    if (!toggleText) return;
    
    const isExpanded = button.getAttribute('aria-expanded') === 'true';
    toggleText.textContent = isExpanded ? 'Show less' : 'Show all';
  }
});

