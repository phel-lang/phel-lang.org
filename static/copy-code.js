document.addEventListener('DOMContentLoaded', function() {
  // Add copy functionality to all pre blocks
  const preBlocks = document.querySelectorAll('pre');

  preBlocks.forEach(pre => {
    // Wrap pre in a container if not already wrapped
    if (!pre.parentElement.classList.contains('code-block-wrapper')) {
      const wrapper = document.createElement('div');
      wrapper.className = 'code-block-wrapper';
      pre.parentNode.insertBefore(wrapper, pre);
      wrapper.appendChild(pre);
    }

    // Create copy button element
    const copyButton = document.createElement('button');
    copyButton.className = 'copy-code-button';
    copyButton.setAttribute('aria-label', 'Copy code to clipboard');
    copyButton.innerHTML = `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
      </svg>
    `;

    // Add button to wrapper
    pre.parentElement.appendChild(copyButton);

    // Handle copy
    copyButton.addEventListener('click', async () => {
      const code = pre.querySelector('code');
      const text = code ? code.textContent : pre.textContent;

      try {
        await navigator.clipboard.writeText(text);

        // Add copied state
        copyButton.classList.add('copied');

        // Change icon to checkmark
        copyButton.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
        `;

        // Reset after 2 seconds
        setTimeout(() => {
          copyButton.classList.remove('copied');
          copyButton.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
              <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
            </svg>
          `;
        }, 2000);
      } catch (err) {
        console.error('Failed to copy code:', err);
      }
    });
  });
});
