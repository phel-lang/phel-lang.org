(function () {
  'use strict';

  var REPO = 'phel-lang/phel-lang';
  var CACHE_KEY = 'phel-gh-stats';
  var TTL_MS = 24 * 60 * 60 * 1000;

  function formatStars(n) {
    if (n >= 1000) return (n / 1000).toFixed(1).replace(/\.0$/, '') + 'k';
    return String(n);
  }

  function paint(stars) {
    if (typeof stars !== 'number' || stars < 0) return;
    document.querySelectorAll('[data-gh-stars-count]').forEach(function (el) {
      el.textContent = formatStars(stars);
    });
  }

  function readCache() {
    try {
      var raw = localStorage.getItem(CACHE_KEY);
      if (!raw) return null;
      var obj = JSON.parse(raw);
      if (!obj || typeof obj.stars !== 'number' || !obj.t) return null;
      if (Date.now() - obj.t > TTL_MS) return null;
      return obj;
    } catch (_) {
      return null;
    }
  }

  function writeCache(stars) {
    try {
      localStorage.setItem(CACHE_KEY, JSON.stringify({ stars: stars, t: Date.now() }));
    } catch (_) {}
  }

  var cached = readCache();
  if (cached) {
    paint(cached.stars);
    return;
  }

  fetch('https://api.github.com/repos/' + REPO, { headers: { Accept: 'application/vnd.github+json' } })
    .then(function (r) { return r.ok ? r.json() : null; })
    .then(function (d) {
      if (!d || typeof d.stargazers_count !== 'number') return;
      paint(d.stargazers_count);
      writeCache(d.stargazers_count);
    })
    .catch(function () {});
})();
