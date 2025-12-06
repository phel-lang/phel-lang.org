// Get the button:
let mybutton = document.getElementById("back-to-top-button");

// Track last scroll position for detecting scroll direction
let lastScrollTop = 0;
let isScrollingUp = false;

// When the user scrolls, check position and direction
window.onscroll = function() {scrollFunction()};

function scrollFunction() {
  const currentScrollTop = document.body.scrollTop || document.documentElement.scrollTop;
  
  // Determine scroll direction
  if (currentScrollTop < lastScrollTop) {
    isScrollingUp = true;
  } else {
    isScrollingUp = false;
  }
  lastScrollTop = currentScrollTop <= 0 ? 0 : currentScrollTop;
  
  // Show button only when scrolling up AND past 300px (all resolutions)
  if (currentScrollTop > 300 && isScrollingUp) {
    mybutton.classList.add("visible");
  } else {
    mybutton.classList.remove("visible");
  }
}

// When the user clicks on the button, scroll to the top of the document
function backToTop() {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  });
}
