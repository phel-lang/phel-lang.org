// Mobile Menu Toggle - Simple and Robust
console.log('Mobile menu script loaded');

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
  console.log('DOM loaded, initializing mobile menu...');
  
  const menuToggle = document.getElementById('mobile-menu-toggle');
  const menuOverlay = document.getElementById('mobile-menu-overlay');
  const body = document.body;

  console.log('Menu toggle button:', menuToggle);
  console.log('Menu overlay:', menuOverlay);

  if (!menuToggle) {
    console.error('ERROR: mobile-menu-toggle button not found!');
    return;
  }

  if (!menuOverlay) {
    console.error('ERROR: mobile-menu-overlay not found!');
    return;
  }

  console.log('✓ All menu elements found, attaching click handler...');

  // Toggle menu on button click
  menuToggle.addEventListener('click', function(e) {
    console.log('Hamburger clicked!');
    e.preventDefault();
    
    const isActive = menuToggle.classList.contains('active');
    
    if (isActive) {
      // Close menu
      menuToggle.classList.remove('active');
      menuOverlay.classList.remove('active');
      body.classList.remove('menu-open');
      console.log('Menu CLOSED');
    } else {
      // Open menu
      menuToggle.classList.add('active');
      menuOverlay.classList.add('active');
      body.classList.add('menu-open');
      console.log('Menu OPENED');
    }
  });

  // Close menu when clicking on the overlay background
  menuOverlay.addEventListener('click', function(e) {
    if (e.target === menuOverlay) {
      console.log('Clicked outside menu, closing...');
      menuToggle.classList.remove('active');
      menuOverlay.classList.remove('active');
      body.classList.remove('menu-open');
    }
  });

  // Close menu when clicking any navigation link
  const menuLinks = menuOverlay.querySelectorAll('a');
  console.log('Found', menuLinks.length, 'menu links');
  
  menuLinks.forEach(function(link) {
    link.addEventListener('click', function() {
      console.log('Link clicked, closing menu');
      menuToggle.classList.remove('active');
      menuOverlay.classList.remove('active');
      body.classList.remove('menu-open');
    });
  });

  console.log('✓ Mobile menu fully initialized!');
});

// Also try to initialize if DOM is already loaded
if (document.readyState !== 'loading') {
  console.log('DOM already loaded, initializing immediately...');
  const event = new Event('DOMContentLoaded');
  document.dispatchEvent(event);
}

// Mobile dark mode toggle (separate initialization)
document.addEventListener('DOMContentLoaded', function() {
  const mobileDarkToggle = document.querySelector('.mobile-dark-mode-toggle');
  const mainDarkToggle = document.getElementById('dark-mode-toggle');
  
  if (mobileDarkToggle && mainDarkToggle) {
    console.log('✓ Mobile dark mode toggle found');
    mobileDarkToggle.addEventListener('click', function() {
      console.log('Mobile dark mode clicked');
      mainDarkToggle.click();
    });
  } else {
    console.log('Dark mode toggles not found yet (may initialize later)');
  }
});

// Mobile search expansion
document.addEventListener('DOMContentLoaded', function() {
  // Only run on mobile screens
  function isMobile() {
    return window.innerWidth < 1040;
  }

  const searchInput = document.getElementById('search');
  const headerContainer = document.querySelector('.site-header__container');
  
  if (!searchInput || !headerContainer) {
    console.log('Search elements not found');
    return;
  }

  console.log('✓ Mobile search expansion initialized');

  // Expand search on focus (mobile only)
  searchInput.addEventListener('focus', function() {
    if (isMobile()) {
      console.log('Search focused - expanding');
      headerContainer.classList.add('search-expanded');
    }
  });

  // Collapse search on blur (mobile only)
  searchInput.addEventListener('blur', function() {
    if (isMobile()) {
      // Small delay to allow clicking search results
      setTimeout(function() {
        console.log('Search blurred - collapsing');
        headerContainer.classList.remove('search-expanded');
      }, 200);
    }
  });

  // Re-check on window resize
  window.addEventListener('resize', function() {
    if (!isMobile()) {
      headerContainer.classList.remove('search-expanded');
    }
  });
});
