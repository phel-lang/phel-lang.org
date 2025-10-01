// Dark Mode Toggle Functionality
(function() {
  'use strict';

  // Switch syntax highlighting theme
  function switchSyntaxTheme(isDark) {
    const lightTheme = document.querySelector('link[href*="syntax-theme-light.css"]');
    const darkTheme = document.querySelector('link[href*="syntax-theme-dark.css"]');
    
    if (lightTheme && darkTheme) {
      if (isDark) {
        lightTheme.media = 'not all';
        darkTheme.media = 'all';
      } else {
        lightTheme.media = 'all';
        darkTheme.media = 'not all';
      }
    }
  }

  // Initialize dark mode based on user preference or system setting
  function initDarkMode() {
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
      document.documentElement.classList.add('dark');
      switchSyntaxTheme(true);
    } else {
      switchSyntaxTheme(false);
    }
  }

  // Toggle dark mode
  function toggleDarkMode() {
    const isDark = document.documentElement.classList.contains('dark');
    
    if (isDark) {
      document.documentElement.classList.remove('dark');
      localStorage.setItem('theme', 'light');
      switchSyntaxTheme(false);
    } else {
      document.documentElement.classList.add('dark');
      localStorage.setItem('theme', 'dark');
      switchSyntaxTheme(true);
    }
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
          switchSyntaxTheme(true);
        } else {
          document.documentElement.classList.remove('dark');
          switchSyntaxTheme(false);
        }
      }
    });
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      initDarkMode();
      attachToggleButton();
      watchSystemTheme();
    });
  } else {
    initDarkMode();
    attachToggleButton();
    watchSystemTheme();
  }
})();
