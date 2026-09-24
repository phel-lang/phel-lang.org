document.addEventListener('DOMContentLoaded', function() {
  // Add copy functionality to all pre blocks
  const preBlocks = document.querySelectorAll('pre');

  // A block that scrolls sideways must be reachable by keyboard, otherwise
  // arrow-key users can never read the clipped part. Only overflowing blocks
  // join the tab order, and the check reruns whenever a block is resized.
  function syncScrollableFocus(pre) {
    if (pre.scrollWidth > pre.clientWidth) {
      pre.setAttribute('tabindex', '0');
    } else {
      pre.removeAttribute('tabindex');
    }
  }

  const resizeObserver = 'ResizeObserver' in window
    ? new ResizeObserver(entries => entries.forEach(entry => syncScrollableFocus(entry.target)))
    : null;

  preBlocks.forEach(pre => {
    syncScrollableFocus(pre);
    if (resizeObserver) resizeObserver.observe(pre);

    // Wrap pre in a container if not already wrapped
    if (!pre.parentElement.classList.contains('code-block-wrapper')) {
      const wrapper = document.createElement('div');
      wrapper.className = 'code-block-wrapper';
      pre.parentNode.insertBefore(wrapper, pre);
      wrapper.appendChild(pre);
    }

    const copyButton = document.createElement('button');
    copyButton.className = 'copy-code-button';
    copyButton.setAttribute('aria-label', 'Copy code to clipboard');
    copyButton.innerHTML = `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
      </svg>
    `;

    pre.parentElement.appendChild(copyButton);

    // Handle copy
    copyButton.addEventListener('click', async () => {
      let text;
      if (pre.classList.contains('phel-terminal-session')) {
        const inputs = pre.querySelectorAll('.t-in');
        text = Array.from(inputs).map(el => el.textContent).join('\n');
      } else {
        const code = pre.querySelector('code');
        text = code ? code.textContent : pre.textContent;
      }

      try {
        await navigator.clipboard.writeText(text);

        copyButton.classList.add('copied');
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
