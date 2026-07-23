document.addEventListener('DOMContentLoaded', () => {
  // Only activate on the cookbook page
  if (!window.location.pathname.includes('cookbook')) return;

  const content = document.querySelector('.page-content');
  if (!content) return;

  // Create filter input (reuses the .cheat-filter styles)
  const filterWrapper = document.createElement('div');
  filterWrapper.className = 'cheat-filter';
  filterWrapper.innerHTML = `
    <input type="search" class="cheat-filter-input" placeholder="Filter recipes... (e.g., csv, http, dates, schema)" autocomplete="off" spellcheck="false">
    <span class="cheat-filter-count"></span>
  `;

  // Insert at the top of content, after the first heading (or first element)
  const anchor = content.querySelector('h1') || content.firstElementChild;
  if (anchor && anchor.nextSibling) {
    content.insertBefore(filterWrapper, anchor.nextSibling);
  } else {
    content.prepend(filterWrapper);
  }

  const input = filterWrapper.querySelector('.cheat-filter-input');
  const countEl = filterWrapper.querySelector('.cheat-filter-count');

  // Group by h2 headings and their content until the next h2
  const sections = [];
  let currentSection = null;

  Array.from(content.children).forEach(el => {
    if (el.tagName === 'H2') {
      currentSection = { heading: el, elements: [], text: '' };
      sections.push(currentSection);
    } else if (currentSection && !el.classList.contains('cheat-filter')) {
      currentSection.elements.push(el);
      currentSection.text += ' ' + el.textContent.toLowerCase();
    }
  });

  sections.forEach(s => {
    s.text = s.heading.textContent.toLowerCase() + s.text;
  });

  function filter(query) {
    const q = query.toLowerCase().trim();
    let visible = 0;

    sections.forEach(section => {
      const match = !q || section.text.includes(q);
      section.heading.style.display = match ? '' : 'none';
      section.elements.forEach(el => el.style.display = match ? '' : 'none');
      if (match) visible++;
    });

    countEl.textContent = `(${visible}/${sections.length} recipes)`;
  }

  filter('');

  input.addEventListener('input', (e) => filter(e.target.value));
  input.addEventListener('search', (e) => filter(e.target.value));
});
