// Dark Mode Toggle Functionality
(function() {
  'use strict';

  // Initialize dark mode based on user preference or system setting
  function initDarkMode() {
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
      document.documentElement.classList.add('dark');
    }
  }

  // Expose the current theme on every toggle (desktop + mobile menu)
  function syncToggleState() {
    const isDark = document.documentElement.classList.contains('dark');
    document.querySelectorAll('#dark-mode-toggle, .mobile-menu__dark-mode-toggle').forEach(function(button) {
      button.setAttribute('aria-pressed', String(isDark));
    });
  }

  // Browser chrome color per theme, read once from the media-scoped metas in
  // base.html before a manual choice overwrites them
  const themeColorMetas = Array.from(document.querySelectorAll('meta[name="theme-color"]'));
  const themeColors = {};
  themeColorMetas.forEach(function(meta) {
    const media = meta.getAttribute('media') || '';
    if (media.includes('dark')) {
      themeColors.dark = meta.getAttribute('content');
    } else if (media.includes('light')) {
      themeColors.light = meta.getAttribute('content');
    }
  });

  // A manual choice can differ from the OS scheme the metas key off, so point
  // both at the chosen theme's color
  function syncThemeColor() {
    const color = themeColors[document.documentElement.classList.contains('dark') ? 'dark' : 'light'];
    if (!color) return;
    themeColorMetas.forEach(function(meta) {
      meta.setAttribute('content', color);
    });
  }

  function toggleDarkMode() {
    const isDark = document.documentElement.classList.contains('dark');

    if (isDark) {
      document.documentElement.classList.remove('dark');
      localStorage.setItem('theme', 'light');
    } else {
      document.documentElement.classList.add('dark');
      localStorage.setItem('theme', 'dark');
    }

    syncToggleState();
    syncThemeColor();
  }

  // Attach event listener to existing dark mode toggle button
  function attachToggleButton() {
    const button = document.getElementById('dark-mode-toggle');
    if (button) {
      button.addEventListener('click', toggleDarkMode);
    }
  }

  // Listen for system theme changes
  function watchSystemTheme() {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addEventListener('change', (e) => {
      if (!localStorage.getItem('theme')) {
        if (e.matches) {
          document.documentElement.classList.add('dark');
        } else {
          document.documentElement.classList.remove('dark');
        }
        syncToggleState();
      }
    });
  }

  // Paper is white: print with the light theme, then restore the choice.
  function watchPrint() {
    let wasDark = false;
    window.addEventListener('beforeprint', () => {
      wasDark = document.documentElement.classList.contains('dark');
      document.documentElement.classList.remove('dark');
    });
    window.addEventListener('afterprint', () => {
      if (wasDark) document.documentElement.classList.add('dark');
    });
  }

  function init() {
    initDarkMode();
    watchPrint();
    attachToggleButton();
    watchSystemTheme();
    syncToggleState();
    // Without a stored choice the media-scoped metas already follow the OS
    if (localStorage.getItem('theme')) {
      syncThemeColor();
    }
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
