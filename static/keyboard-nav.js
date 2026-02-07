document.addEventListener('DOMContentLoaded', () => {
  let overlayVisible = false;

  // Create overlay
  const overlay = document.createElement('div');
  overlay.className = 'kbd-overlay';
  overlay.innerHTML = `
    <div class="kbd-overlay-backdrop"></div>
    <div class="kbd-overlay-panel">
      <div class="kbd-overlay-header">
        <h3>Keyboard Shortcuts</h3>
        <button class="kbd-overlay-close" aria-label="Close">&times;</button>
      </div>
      <div class="kbd-overlay-body">
        <div class="kbd-section">
          <h4>Navigation</h4>
          <div class="kbd-row"><kbd>j</kbd> / <kbd>k</kbd><span>Scroll down / up</span></div>
          <div class="kbd-row"><kbd>n</kbd><span>Next page</span></div>
          <div class="kbd-row"><kbd>p</kbd><span>Previous page</span></div>
          <div class="kbd-row"><kbd>g</kbd> <kbd>h</kbd><span>Go to homepage</span></div>
          <div class="kbd-row"><kbd>g</kbd> <kbd>d</kbd><span>Go to documentation</span></div>
          <div class="kbd-row"><kbd>g</kbd> <kbd>p</kbd><span>Go to practice</span></div>
        </div>
        <div class="kbd-section">
          <h4>Actions</h4>
          <div class="kbd-row"><kbd>/</kbd><span>Open search</span></div>
          <div class="kbd-row"><kbd>t</kbd><span>Toggle dark mode</span></div>
          <div class="kbd-row"><kbd>?</kbd><span>Show this help</span></div>
          <div class="kbd-row"><kbd>Esc</kbd><span>Close overlay</span></div>
        </div>
      </div>
    </div>
  `;
  document.body.appendChild(overlay);

  const backdrop = overlay.querySelector('.kbd-overlay-backdrop');
  const closeBtn = overlay.querySelector('.kbd-overlay-close');

  function toggleOverlay() {
    overlayVisible = !overlayVisible;
    overlay.classList.toggle('visible', overlayVisible);
  }

  function hideOverlay() {
    overlayVisible = false;
    overlay.classList.remove('visible');
  }

  backdrop.addEventListener('click', hideOverlay);
  closeBtn.addEventListener('click', hideOverlay);

  // "g" prefix for go-to shortcuts
  let gPressed = false;
  let gTimer = null;

  document.addEventListener('keydown', (e) => {
    // Don't handle when typing in input/textarea
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;
    // Don't handle if a modal is open (search, etc.)
    if (document.querySelector('.search-modal.active')) return;

    const key = e.key;

    // Handle "g" prefix
    if (gPressed) {
      gPressed = false;
      clearTimeout(gTimer);
      if (key === 'h') { window.location.href = '/'; return; }
      if (key === 'd') { window.location.href = '/documentation/getting-started/'; return; }
      if (key === 'p') { window.location.href = '/practice/basic/'; return; }
      return;
    }

    switch (key) {
      case '?':
        e.preventDefault();
        toggleOverlay();
        break;
      case 'Escape':
        hideOverlay();
        break;
      case 'j':
        window.scrollBy({ top: 100, behavior: 'smooth' });
        break;
      case 'k':
        window.scrollBy({ top: -100, behavior: 'smooth' });
        break;
      case 'n': {
        const next = document.querySelector('.page-navigation a[href]:last-child');
        if (next) next.click();
        break;
      }
      case 'p': {
        const prev = document.querySelector('.page-navigation a[href]:first-child');
        if (prev) prev.click();
        break;
      }
      case 'g':
        gPressed = true;
        gTimer = setTimeout(() => { gPressed = false; }, 1000);
        break;
      case 't': {
        const toggle = document.querySelector('.dark-mode-toggle, #dark-mode-toggle');
        if (toggle) toggle.click();
        break;
      }
      case '/':
        // Let the existing search handler take care of this
        break;
    }
  });
});
