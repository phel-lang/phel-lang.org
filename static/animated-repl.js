document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('animated-repl');
  if (!container) return;

  const lines = [
    { type: 'shell', text: 'docker run -it --rm phellang/repl', delay: 40 },
    { type: 'output', text: 'Welcome to the Phel Repl', delay: 0 },
    { type: 'output', text: '------------------------------', delay: 0 },
    { type: 'prompt', text: '(+ 1 2 3)', delay: 60 },
    { type: 'result', text: '6', delay: 0 },
    { type: 'prompt', text: '(def name "World")', delay: 50 },
    { type: 'result', text: 'World', delay: 0 },
    { type: 'prompt', text: '(str "Hello, " name "!")', delay: 45 },
    { type: 'result', text: '"Hello, World!"', delay: 0 },
    { type: 'prompt', text: '(defn greet [who] (str "Hi, " who "!"))', delay: 40 },
    { type: 'result', text: '', delay: 0 },
    { type: 'prompt', text: '(greet "PHP developer")', delay: 50 },
    { type: 'result', text: '"Hi, PHP developer!"', delay: 0 },
    { type: 'prompt', text: '(->> (range 1 6) (map (fn [x] (* x x))) (reduce +))', delay: 35 },
    { type: 'result', text: '55', delay: 0 },
  ];

  const terminal = document.createElement('div');
  terminal.className = 'terminal';
  terminal.innerHTML = `
    <div class="terminal-header">
      <div class="terminal-dots">
        <span class="terminal-dot terminal-dot-red"></span>
        <span class="terminal-dot terminal-dot-yellow"></span>
        <span class="terminal-dot terminal-dot-green"></span>
      </div>
      <div class="terminal-title">phel repl</div>
      <div class="terminal-dots" style="visibility:hidden">
        <span class="terminal-dot"></span>
        <span class="terminal-dot"></span>
        <span class="terminal-dot"></span>
      </div>
    </div>
    <div class="terminal-body" id="terminal-body"></div>
  `;
  container.innerHTML = '';
  container.appendChild(terminal);

  const body = terminal.querySelector('#terminal-body');
  let isRunning = false;

  function getPrefix(type) {
    if (type === 'shell') return '<span class="terminal-shell">$ </span>';
    if (type === 'prompt') return '<span class="terminal-prompt">>>> </span>';
    return '';
  }

  function getClass(type) {
    if (type === 'result') return 'terminal-result';
    if (type === 'output') return 'terminal-output';
    return 'terminal-input';
  }

  async function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
  }

  async function typeLine(lineEl, text, charDelay) {
    for (let i = 0; i < text.length; i++) {
      lineEl.textContent += text[i];
      if (charDelay > 0) await sleep(charDelay + Math.random() * 20);
    }
  }

  async function run() {
    if (isRunning) return;
    isRunning = true;
    body.innerHTML = '';

    for (const line of lines) {
      const div = document.createElement('div');
      div.className = `terminal-line ${getClass(line.type)}`;
      div.innerHTML = getPrefix(line.type);
      body.appendChild(div);

      if (line.delay > 0) {
        // Typewriter effect for input lines
        const textSpan = document.createElement('span');
        div.appendChild(textSpan);
        await sleep(300); // pause before typing
        await typeLine(textSpan, line.text, line.delay);
        await sleep(400); // pause after typing (like pressing Enter)
      } else {
        // Instant display for output
        const textSpan = document.createElement('span');
        textSpan.textContent = line.text;
        div.appendChild(textSpan);
        await sleep(100);
      }

      // Auto-scroll
      body.scrollTop = body.scrollHeight;
    }

    // Add replay button
    await sleep(1000);
    const replay = document.createElement('div');
    replay.className = 'terminal-replay';
    replay.innerHTML = '<button class="terminal-replay-btn" title="Replay">&#8635; Replay</button>';
    replay.querySelector('button').addEventListener('click', () => {
      replay.remove();
      isRunning = false;
      run();
    });
    body.appendChild(replay);
    isRunning = false;
  }

  // Start animation when element is in viewport
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting && !isRunning) {
        observer.disconnect();
        run();
      }
    });
  }, { threshold: 0.3 });

  observer.observe(container);
});
