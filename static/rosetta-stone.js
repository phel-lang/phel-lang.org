document.addEventListener('DOMContentLoaded', () => {
  const filters = document.getElementById('rosetta-filters');
  if (!filters) return;

  const buttons = filters.querySelectorAll('.rosetta-filter');
  const items = document.querySelectorAll('.rosetta-item');

  buttons.forEach(btn => {
    btn.addEventListener('click', () => {
      const filter = btn.dataset.filter;

      buttons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      items.forEach(item => {
        if (filter === 'all' || item.dataset.category === filter) {
          item.classList.remove('hidden');
        } else {
          item.classList.add('hidden');
        }
      });
    });
  });
});
