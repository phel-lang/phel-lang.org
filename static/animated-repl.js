document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('animated-repl');
  if (!container) return;

  const lines = [
    { type: 'prompt', text: '(map inc [1 2 3])', delay: 45 },
    { type: 'result', text: '(2 3 4)', delay: 0 },
    { type: 'prompt', text: '(->> (range 1 6) (filter odd?) (reduce +))', delay: 35 },
    { type: 'result', text: '9', delay: 0 },
    { type: 'prompt', text: '(defn greet [name] (str "hello, " name))', delay: 40 },
    { type: 'result', text: "#'user/greet", delay: 0 },
    { type: 'prompt', text: '(greet "phel")', delay: 50 },
    { type: 'result', text: '"hello, phel"', delay: 0 },
    { type: 'prompt', text: '(->> (range 1 11) (map (fn [x] (* x x))) (reduce +))', delay: 35 },
    { type: 'result', text: '385', delay: 0 },
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

  let promptCounter = 0;

  function getPrefix(type) {
    if (type === 'shell') return '<span class="terminal-shell">$ </span>';
    if (type === 'prompt') {
      promptCounter += 1;
      return `<span class="terminal-prompt">user:${promptCounter}&gt; </span>`;
    }
    return '';
  }

  function getClass(type) {
    if (type === 'result') return 'terminal-result';
    if (type === 'output') return 'terminal-output';
    return 'terminal-input';
  }

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  async function sleep(ms) {
    if (reduceMotion) return;
    return new Promise(r => setTimeout(r, ms));
  }

  async function typeLine(lineEl, text, charDelay) {
    if (reduceMotion || charDelay <= 0) {
      lineEl.textContent = text;
      return;
    }
    for (let i = 0; i < text.length; i++) {
      lineEl.textContent += text[i];
      await sleep(charDelay + Math.random() * 20);
    }
  }

  async function run() {
    if (isRunning) return;
    isRunning = true;
    body.innerHTML = '';
    promptCounter = 0;

    for (const line of lines) {
      const div = document.createElement('div');
      div.className = `terminal-line ${getClass(line.type)}`;
      div.innerHTML = getPrefix(line.type);
      body.appendChild(div);

      if (line.delay > 0) {
        const textSpan = document.createElement('span');
        div.appendChild(textSpan);
        await sleep(300);
        await typeLine(textSpan, line.text, line.delay);
        await sleep(400);
      } else {
        const textSpan = document.createElement('span');
        textSpan.textContent = line.text;
        div.appendChild(textSpan);
        await sleep(100);
      }

      body.scrollTop = body.scrollHeight;
    }

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
    body.scrollTop = body.scrollHeight;
    isRunning = false;
  }

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
