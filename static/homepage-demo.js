document.addEventListener('DOMContentLoaded', () => {
  // Click-to-load: YouTube is only contacted once the visitor presses play.
  document.querySelectorAll('[data-youtube-id]').forEach(link => {
    link.addEventListener('click', event => {
      if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      const iframe = document.createElement('iframe');
      iframe.src = `https://www.youtube-nocookie.com/embed/${link.dataset.youtubeId}?autoplay=1`;
      iframe.title = link.getAttribute('aria-label').replace(/^Play video: /, '');
      iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
      iframe.referrerPolicy = 'strict-origin-when-cross-origin';
      iframe.allowFullscreen = true;
      iframe.className = 'homepage-demo-video';
      link.replaceWith(iframe);
      iframe.focus();
    });
  });
});
