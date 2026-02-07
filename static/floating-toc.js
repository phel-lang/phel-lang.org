(function () {
  'use strict';

  var toc = document.getElementById('floating-toc');
  if (!toc) return;

  var toggle = document.getElementById('floating-toc-toggle');
  var panel = document.getElementById('floating-toc-panel');
  var links = panel ? panel.querySelectorAll('a[href^="#"]') : [];

  if (!toggle || !panel || links.length === 0) return;

  var WIDE_BREAKPOINT = 1400;

  function isWideScreen() {
    return window.innerWidth >= WIDE_BREAKPOINT;
  }

  // --- Toggle ---
  function openPanel() {
    toc.classList.add('open');
    toggle.setAttribute('aria-expanded', 'true');
  }

  function closePanel() {
    toc.classList.remove('open');
    toggle.setAttribute('aria-expanded', 'false');
  }

  toggle.addEventListener('click', function () {
    if (toc.classList.contains('open')) {
      closePanel();
    } else {
      openPanel();
    }
  });

  // Close on click outside (only when not wide screen)
  document.addEventListener('click', function (e) {
    if (!isWideScreen() && toc.classList.contains('open') && !toc.contains(e.target)) {
      closePanel();
    }
  });

  // Close on Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && toc.classList.contains('open') && !isWideScreen()) {
      closePanel();
      toggle.focus();
    }
  });

  // Close panel when clicking a link on non-wide screens
  for (var i = 0; i < links.length; i++) {
    links[i].addEventListener('click', function () {
      if (!isWideScreen()) {
        closePanel();
      }
    });
  }

  // --- Scroll spy ---
  var headingIds = [];
  for (var j = 0; j < links.length; j++) {
    var id = links[j].getAttribute('href');
    if (id && id.charAt(0) === '#') {
      headingIds.push(id.slice(1));
    }
  }

  function updateActiveLink() {
    var scrollTop = window.scrollY || document.documentElement.scrollTop;
    var offset = 120;
    var activeId = null;

    for (var k = headingIds.length - 1; k >= 0; k--) {
      var el = document.getElementById(headingIds[k]);
      if (el && el.offsetTop <= scrollTop + offset) {
        activeId = headingIds[k];
        break;
      }
    }

    // If near the top and no heading passed yet, highlight the first
    if (!activeId && headingIds.length > 0 && scrollTop < offset) {
      activeId = headingIds[0];
    }

    for (var m = 0; m < links.length; m++) {
      var href = links[m].getAttribute('href');
      if (href === '#' + activeId) {
        links[m].classList.add('active');
      } else {
        links[m].classList.remove('active');
      }
    }
  }

  var ticking = false;
  window.addEventListener('scroll', function () {
    if (!ticking) {
      requestAnimationFrame(function () {
        updateActiveLink();
        ticking = false;
      });
      ticking = true;
    }
  });

  // --- Panel scrollbar: show only while scrolling ---
  var scrollTimer = null;
  panel.addEventListener('scroll', function () {
    panel.classList.add('scrolling');
    clearTimeout(scrollTimer);
    scrollTimer = setTimeout(function () {
      panel.classList.remove('scrolling');
    }, 800);
  });

  // Initial highlight
  updateActiveLink();
})();
