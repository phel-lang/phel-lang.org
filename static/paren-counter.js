document.addEventListener('DOMContentLoaded', () => {
  // Only show on pages with substantial content
  const content = document.querySelector('.page-content, .section-content, main');
  if (!content) return;

  const text = content.textContent || '';
  const openParens = (text.match(/\(/g) || []).length;
  const closeParens = (text.match(/\)/g) || []).length;
  const total = openParens + closeParens;

  if (total < 10) return; // Skip pages with few parens

  const counter = document.createElement('div');
  counter.className = 'paren-counter';
  counter.innerHTML = `<span class="paren-counter-icon">()</span> This page contains <strong>${total.toLocaleString()}</strong> parentheses${openParens === closeParens ? ' — perfectly balanced.' : '.'}`;

  // Insert before footer or at end of main content
  const footer = document.querySelector('footer');
  if (footer) {
    footer.parentNode.insertBefore(counter, footer);
  }
});
