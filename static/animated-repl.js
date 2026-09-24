document.addEventListener('DOMContentLoaded', () => {
  // The transcript is server-rendered by the hero_repl shortcode from REAL
  // `phel repl` output (build/generate-repl-showcase.php), so the terminal is
  // never empty and never drifts from what Phel prints. This script only
  // replays the last form as typing, then reveals its result.
  const terminal = document.querySelector('[data-animated-repl]');
  if (!terminal) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  const body = terminal.querySelector('.terminal-body');
  const inputs = body.querySelectorAll('.terminal-input .terminal-text');
  const results = body.querySelectorAll('.terminal-result');
  const idle = body.querySelector('.terminal-input:last-child');
  if (!inputs.length || !results.length || !idle) return;

  const lastInput = inputs[inputs.length - 1];
  const lastResult = results[results.length - 1];
  const text = lastInput.textContent;

  // Lock the height so typing never shifts the layout around it.
  body.style.minHeight = `${body.offsetHeight}px`;
  lastInput.textContent = '';
  lastResult.style.visibility = 'hidden';
  idle.style.visibility = 'hidden';

  const sleep = ms => new Promise(r => setTimeout(r, ms));

  (async () => {
    await sleep(700);
    for (const ch of text) {
      lastInput.textContent += ch;
      await sleep(30 + Math.random() * 14);
    }
    await sleep(180);
    lastResult.style.visibility = '';
    await sleep(120);
    idle.style.visibility = '';
  })();
});
