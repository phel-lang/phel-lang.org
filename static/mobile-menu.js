// Wait for DOM to be fully loaded
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

  // Toggle menu on button click
  menuToggle.addEventListener('click', function(e) {
    e.preventDefault();
    
    const isActive = menuToggle.classList.contains('active');
    
    if (isActive) {
      // Close menu
      menuToggle.classList.remove('active');
      menuOverlay.classList.remove('active');
      body.classList.remove('menu-open');
    } else {
      // Open menu
      menuToggle.classList.add('active');
      menuOverlay.classList.add('active');
      body.classList.add('menu-open');
    }
  });

  // Close menu when clicking on the overlay background
  menuOverlay.addEventListener('click', function(e) {
    if (e.target === menuOverlay) {
      menuToggle.classList.remove('active');
      menuOverlay.classList.remove('active');
      body.classList.remove('menu-open');
    }
  });

  // Close menu when clicking any navigation link
  const menuLinks = menuOverlay.querySelectorAll('a');
  
  menuLinks.forEach(function(link) {
    link.addEventListener('click', function() {
      menuToggle.classList.remove('active');
      menuOverlay.classList.remove('active');
      body.classList.remove('menu-open');
    });
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
