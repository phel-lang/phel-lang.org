document.addEventListener('DOMContentLoaded', function() {
  const menuToggle = document.getElementById('mobile-menu-toggle');
  const menuOverlay = document.getElementById('mobile-menu-overlay');
  const body = document.body;

  if (!menuToggle) {
    console.error('ERROR: mobile-menu-toggle button not found!');
    return;
  }

  if (!menuOverlay) {
    console.error('ERROR: mobile-menu-overlay not found!');
    return;
  }

  function openMenu() {
    menuToggle.classList.add('active');
    menuToggle.setAttribute('aria-expanded', 'true');
    menuOverlay.classList.add('active');
    body.classList.add('menu-open');
  }

  function closeMenu() {
    menuToggle.classList.remove('active');
    menuToggle.setAttribute('aria-expanded', 'false');
    menuOverlay.classList.remove('active');
    body.classList.remove('menu-open');
  }

  // Toggle menu on button click
  menuToggle.addEventListener('click', function(e) {
    e.preventDefault();

    if (menuToggle.classList.contains('active')) {
      closeMenu();
    } else {
      openMenu();
    }
  });

  // Close menu when clicking on the overlay background
  menuOverlay.addEventListener('click', function(e) {
    if (e.target === menuOverlay) {
      closeMenu();
    }
  });

  // Close menu on Escape and hand focus back to the toggle
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && menuToggle.classList.contains('active')) {
      closeMenu();
      menuToggle.focus();
    }
  });

  // Close menu when clicking any navigation link
  const menuLinks = menuOverlay.querySelectorAll('a');

  menuLinks.forEach(function(link) {
    link.addEventListener('click', closeMenu);
  });
});

// Also try to initialize if DOM is already loaded
if (document.readyState !== 'loading') {
  const event = new Event('DOMContentLoaded');
  document.dispatchEvent(event);
}

// Mobile dark mode toggle (separate initialization)
document.addEventListener('DOMContentLoaded', function() {
  const mobileDarkToggle = document.querySelector('.mobile-menu__dark-mode-toggle');
  const mainDarkToggle = document.getElementById('dark-mode-toggle');

  if (mobileDarkToggle && mainDarkToggle) {
    mobileDarkToggle.addEventListener('click', function() {
      mainDarkToggle.click();
    });
  }
});
