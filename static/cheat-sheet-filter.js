document.addEventListener('DOMContentLoaded', () => {
  // Only activate on the cheat-sheet page
  if (!window.location.pathname.includes('cheat-sheet')) return;

  const content = document.querySelector('.page-content');
  if (!content) return;

  // Create filter input
  const filterWrapper = document.createElement('div');
  filterWrapper.className = 'cheat-filter';
  filterWrapper.innerHTML = `
    <input type="text" class="cheat-filter-input" placeholder="Filter cheat sheet... (e.g., map, threading, loop)" autocomplete="off" spellcheck="false">
    <span class="cheat-filter-count"></span>
  `;

  // Insert at the top of content, after the first <h1>
  const h1 = content.querySelector('h1') || content.firstElementChild;
  if (h1 && h1.nextSibling) {
    content.insertBefore(filterWrapper, h1.nextSibling);
  } else {
    content.prepend(filterWrapper);
  }

  const input = filterWrapper.querySelector('.cheat-filter-input');
  const countEl = filterWrapper.querySelector('.cheat-filter-count');

  // Get all sections (h2 headings and their content until next h2)
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

  // Add heading text to searchable text
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

    if (q) {
      countEl.textContent = `${visible}/${sections.length} sections`;
    } else {
      countEl.textContent = '';
    }
  }

  input.addEventListener('input', (e) => filter(e.target.value));

  // Focus on Ctrl+F or / when not already focused on an input
  document.addEventListener('keydown', (e) => {
    if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
      // Only on cheat sheet page
      if (window.location.pathname.includes('cheat-sheet')) {
        e.preventDefault();
        input.focus();
        input.select();
      }
    }
  });
});
