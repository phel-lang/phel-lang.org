(function () {
  const STORAGE_KEY = 'phel-practice-progress';

  function hydrate() {
    const overview = document.querySelector('[data-practice-overview]');
    const cards = document.querySelectorAll('.practice-card');
    if (!overview || cards.length === 0) return;


    let progress = {};
    try { progress = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); } catch {}

    let overallCompleted = 0;
    let overallTotal = 0;

    cards.forEach((card) => {
      const slug = card.dataset.slug;
      const total = parseInt(card.dataset.total, 10) || 0;
      let completed = 0;
      for (let i = 0; i < total; i++) {
        if (progress[`${slug}-${i}`]) completed++;
      }
      const pct = total > 0 ? Math.round((completed / total) * 100) : 0;
      const pctEl = card.querySelector('[data-card-progress]');
      const fillEl = card.querySelector('[data-card-fill]');
      if (pctEl) pctEl.textContent = `${completed}/${total}`;
      if (fillEl) fillEl.style.width = `${pct}%`;
      card.classList.toggle('is-complete', total > 0 && completed === total);

      overallCompleted += completed;
      overallTotal += total;
    });

    const doneEl = overview.querySelector('[data-overall-completed]');
    const totalEl = overview.querySelector('[data-overall-total]');
    const fillEl = overview.querySelector('[data-overall-fill]');
    if (doneEl) doneEl.textContent = overallCompleted;
    if (totalEl) totalEl.textContent = overallTotal;
    const overallPct = overallTotal > 0 ? Math.round((overallCompleted / overallTotal) * 100) : 0;
    if (fillEl) fillEl.style.width = `${overallPct}%`;
    overview.classList.toggle('all-complete', overallTotal > 0 && overallCompleted === overallTotal);
    const resetBtn = overview.querySelector('[data-progress-reset]');
    if (resetBtn) resetBtn.hidden = overallCompleted === 0;
  }

  function wireReset() {
    const resetBtn = document.querySelector('[data-progress-reset]');
    if (!resetBtn) return;
    resetBtn.addEventListener('click', () => {
      localStorage.removeItem(STORAGE_KEY);
      hydrate();
    });
  }

  document.addEventListener('DOMContentLoaded', () => { hydrate(); wireReset(); });
  window.addEventListener('pageshow', hydrate);
  window.addEventListener('storage', (e) => { if (e.key === STORAGE_KEY) hydrate(); });
})();
