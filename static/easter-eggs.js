document.addEventListener('DOMContentLoaded', () => {
  // Konami Code
  const konamiCode = [38,38,40,40,37,39,37,39,66,65];
  let konamiIndex = 0;
  const jokes = [
    'A Lisper walks into a bar... (((())))',
    'There are only two hard things: naming things, cache invalidation, and off-by-one errors in (recursive (functions))',
    '(defn meaning-of-life [] 42)',
    'In Phel, we don\'t say "I love you". We say (def love (fn [you] (forever you))) and I think that\'s beautiful.',
  ];

  document.addEventListener('keydown', (e) => {
    if (e.keyCode === konamiCode[konamiIndex]) {
      konamiIndex++;
      if (konamiIndex === konamiCode.length) {
        konamiIndex = 0;
        showToast(jokes[Math.floor(Math.random() * jokes.length)]);
      }
    } else {
      konamiIndex = 0;
    }
  });

  function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'easter-egg-toast';
    toast.textContent = message;
    document.body.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('visible'));
    setTimeout(() => {
      toast.classList.remove('visible');
      setTimeout(() => toast.remove(), 500);
    }, 4000);
  }

  // Search easter egg
  const searchInput = document.getElementById('search-input');
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const val = e.target.value.trim();
      if (val === '(println "hello")' || val === '(println "hello world")') {
        const results = document.querySelector('.search-results');
        if (results) {
          const egg = document.createElement('div');
          egg.className = 'search-result-item easter-egg-result';
          egg.innerHTML = '<strong>Hello, World!</strong><br><small>You found an easter egg!</small>';
          results.prepend(egg);
          setTimeout(() => egg.remove(), 3000);
        }
      }
    });
  }

  // Console message
  console.log(
    '%c   ____  _          _ \n  |  _ \\| |__   ___| |\n  | |_) | \'_ \\ / _ \\ |\n  |  __/| | | |  __/ |\n  |_|   |_| |_|\\___|_|\n\n  Hey there, curious developer!\n  Phel is open source: https://github.com/phel-lang/phel-lang\n  Want to contribute? We\'d love your help!',
    'color: #512da8; font-family: monospace; font-size: 12px;'
  );

  // Triple-click logo spin
  const logo = document.querySelector('img[alt*="Phel"], .logo img, header a img');
  if (logo) {
    let clickCount = 0;
    let clickTimer = null;
    logo.addEventListener('click', () => {
      clickCount++;
      clearTimeout(clickTimer);
      if (clickCount >= 3) {
        clickCount = 0;
        logo.style.transition = 'transform 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
        logo.style.transform = 'rotate(360deg)';
        setTimeout(() => {
          logo.style.transition = 'none';
          logo.style.transform = '';
        }, 800);
      }
      clickTimer = setTimeout(() => { clickCount = 0; }, 400);
    });
  }
});
