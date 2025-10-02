// Get the button:
let mybutton = document.getElementById("back-to-top-button");

// When the user scrolls down 300px from the top of the document, show the button
window.onscroll = function() {scrollFunction()};

function scrollFunction() {
  if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
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
