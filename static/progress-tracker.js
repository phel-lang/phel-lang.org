document.addEventListener('DOMContentLoaded', () => {
  const STORAGE_KEY = 'phel-practice-progress';

  // Get current page slug from URL
  const pageSlug = window.location.pathname.replace(/\/$/, '').split('/').pop();
  if (!pageSlug || !document.querySelector('.question-block')) return;

  // Load progress from localStorage
  function getProgress() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
    } catch { return {}; }
  }

  function saveProgress(progress) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(progress));
  }

  function getExerciseId(pageSlug, index) {
    return `${pageSlug}-${index}`;
  }

  const progress = getProgress();
  const questions = document.querySelectorAll('.question-block');
  const total = questions.length;

  if (total === 0) return;

  // Count completed for this page
  function countCompleted() {
    const p = getProgress();
    let count = 0;
    for (let i = 0; i < total; i++) {
      if (p[getExerciseId(pageSlug, i)]) count++;
    }
    return count;
  }

  // Create progress bar
  const progressBar = document.createElement('div');
  progressBar.className = 'progress-tracker';
  progressBar.innerHTML = `
    <div class="progress-tracker-header">
      <span class="progress-tracker-label">Progress</span>
      <span class="progress-tracker-count"><span class="progress-completed">0</span>/${total}</span>
    </div>
    <div class="progress-tracker-bar">
      <div class="progress-tracker-fill" style="width: 0%"></div>
    </div>
  `;

  // Insert progress bar before first question
  const firstQuestion = questions[0];
  firstQuestion.parentNode.insertBefore(progressBar, firstQuestion);

  // Add complete buttons to each exercise
  questions.forEach((question, index) => {
    const exerciseId = getExerciseId(pageSlug, index);
    const isCompleted = progress[exerciseId];

    // Add a checkmark indicator to the question header
    const header = question.querySelector('h3');
    if (header) {
      const check = document.createElement('span');
      check.className = `exercise-check ${isCompleted ? 'completed' : ''}`;
      check.innerHTML = isCompleted ? '&#10003;' : '';
      check.title = isCompleted ? 'Completed!' : 'Not yet completed';
      header.prepend(check);
    }

    // Add "Mark as Complete" button inside the solution
    const solutionContent = question.querySelector('.solution-content');
    if (solutionContent) {
      const btn = document.createElement('button');
      btn.className = `exercise-complete-btn btn ${isCompleted ? 'btn-completed' : 'btn-secondary'}`;
      btn.innerHTML = isCompleted ? '&#10003; Completed' : 'Mark as Complete';
      btn.addEventListener('click', () => {
        const p = getProgress();
        if (p[exerciseId]) {
          delete p[exerciseId];
          btn.innerHTML = 'Mark as Complete';
          btn.classList.remove('btn-completed');
          btn.classList.add('btn-secondary');
          if (header) {
            const check = header.querySelector('.exercise-check');
            if (check) { check.innerHTML = ''; check.classList.remove('completed'); check.title = 'Not yet completed'; }
          }
        } else {
          p[exerciseId] = true;
          btn.innerHTML = '&#10003; Completed';
          btn.classList.add('btn-completed');
          btn.classList.remove('btn-secondary');
          if (header) {
            const check = header.querySelector('.exercise-check');
            if (check) { check.innerHTML = '&#10003;'; check.classList.add('completed'); check.title = 'Completed!'; }
          }
        }
        saveProgress(p);
        updateProgressBar();
      });
      solutionContent.appendChild(btn);
    }
  });

  function updateProgressBar() {
    const completed = countCompleted();
    const pct = Math.round((completed / total) * 100);
    const fill = progressBar.querySelector('.progress-tracker-fill');
    const countEl = progressBar.querySelector('.progress-completed');
    fill.style.width = `${pct}%`;
    countEl.textContent = completed;

    if (completed === total) {
      progressBar.classList.add('all-complete');
    } else {
      progressBar.classList.remove('all-complete');
    }
  }

  updateProgressBar();
});
