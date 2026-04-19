document.addEventListener('DOMContentLoaded', function() {
  const toggleButtons = document.querySelectorAll('.api-namespace__toggle');

  toggleButtons.forEach(function(button) {
    const content = button.nextElementSibling;
    const toggleText = button.querySelector('.api-namespace__toggle-text');

    if (!content || !content.classList.contains('api-namespace__content')) return;

    content.addEventListener('click', function(e) {
      if (button.getAttribute('aria-expanded') === 'false') {
        e.preventDefault();
        button.setAttribute('aria-expanded', 'true');
        updateToggleText(button, toggleText);
      }
    });

    button.addEventListener('click', function() {
      const isExpanded = this.getAttribute('aria-expanded') === 'true';
      this.setAttribute('aria-expanded', !isExpanded);
      updateToggleText(this, toggleText);
    });
  });

  function updateToggleText(button, toggleText) {
    if (!toggleText) return;
    toggleText.textContent =
      button.getAttribute('aria-expanded') === 'true' ? 'Show less' : 'Show all';
  }
});
