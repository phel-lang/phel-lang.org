(function () {
  const STORAGE_KEY = 'phel-practice-progress';

  function getProgress() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); }
    catch { return {}; }
  }

  function saveProgress(progress) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(progress));
  }

  function exerciseId(slug, index) { return `${slug}-${index}`; }

  function init() {
    const pageSlug = window.location.pathname.replace(/\/$/, '').split('/').pop();
    if (!pageSlug) return;

    const questions = document.querySelectorAll('.question-block');
    const total = questions.length;
    if (total === 0) return;

    const progressBar = document.querySelector('[data-progress-tracker]');

    function paintExercise(question, index) {
      const p = getProgress();
      const id = exerciseId(pageSlug, index);
      const done = !!p[id];
      const check = question.querySelector('[data-exercise-check]');
      if (check) {
        check.innerHTML = done ? '&#10003;' : '';
        check.classList.toggle('completed', done);
        check.title = done ? 'Completed — click to undo' : 'Mark as done';
      }
    }

    function updateBar() {
      if (!progressBar) return;
      const p = getProgress();
      let completed = 0;
      for (let i = 0; i < total; i++) if (p[exerciseId(pageSlug, i)]) completed++;
      const pct = Math.round((completed / total) * 100);
      const fill = progressBar.querySelector('.progress-tracker-fill');
      const countEl = progressBar.querySelector('.progress-completed');
      const resetBtn = progressBar.querySelector('[data-progress-reset]');
      if (fill) fill.style.width = `${pct}%`;
      if (countEl) countEl.textContent = completed;
      if (resetBtn) resetBtn.hidden = completed === 0;
      progressBar.classList.toggle('all-complete', completed === total);
    }

    function hydrate() {
      questions.forEach(paintExercise);
      updateBar();
    }

    questions.forEach((question, index) => {
      const check = question.querySelector('[data-exercise-check]');
      if (!check) return;
      check.addEventListener('click', () => {
        const p = getProgress();
        const id = exerciseId(pageSlug, index);
        if (p[id]) delete p[id]; else p[id] = true;
        saveProgress(p);
        paintExercise(question, index);
        updateBar();
      });
    });

    const resetBtn = document.querySelector('[data-progress-reset]');
    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        localStorage.removeItem(STORAGE_KEY);
        hydrate();
      });
    }

    hydrate();
    window.addEventListener('pageshow', hydrate);
    window.addEventListener('storage', (e) => { if (e.key === STORAGE_KEY) hydrate(); });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
