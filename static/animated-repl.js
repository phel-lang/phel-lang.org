document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('animated-repl');
  if (!container) return;

  const initialCode = '(->> (range 1 10)\n     (filter odd?)\n     (map #(* % %))\n     (reduce +))';

  const state = {
    line: 1,
    vars: new Map(),
    fns: new Map(),
    knownApiNames: new Set(),
  };

  const repl = document.createElement('section');
  repl.className = 'phel-repl-demo';
  repl.setAttribute('aria-label', 'Interactive Phel REPL demo');
  repl.innerHTML = `
    <div class="phel-repl-chrome" aria-hidden="true">
      <div class="phel-repl-dots">
        <span class="phel-repl-dot phel-repl-dot--red"></span>
        <span class="phel-repl-dot phel-repl-dot--yellow"></span>
        <span class="phel-repl-dot phel-repl-dot--green"></span>
      </div>
      <div class="phel-repl-title">phel repl</div>
      <div class="phel-repl-spacer"></div>
    </div>
    <div class="phel-repl-main">
      <div class="phel-repl-editor">
        <div class="phel-repl-pane-title">main.phel</div>
        <textarea id="phel-repl-input" class="phel-repl-input" spellcheck="false"></textarea>
        <div class="phel-repl-actions">
          <button type="button" class="phel-repl-run" data-repl-run>Run</button>
          <button type="button" class="phel-repl-ghost" data-repl-reset>Reset</button>
        </div>
      </div>
      <div class="phel-repl-output-wrap">
        <div class="phel-repl-pane-title">output</div>
        <div class="phel-repl-output" role="log" aria-live="polite" aria-label="REPL output" tabindex="0"></div>
      </div>
    </div>
  `;

  container.innerHTML = '';
  container.appendChild(repl);

  const input = repl.querySelector('.phel-repl-input');
  const output = repl.querySelector('.phel-repl-output');
  const runButton = repl.querySelector('[data-repl-run]');
  const resetButton = repl.querySelector('[data-repl-reset]');

  input.value = initialCode;

  fetch('/api.json')
    .then((response) => response.ok ? response.json() : [])
    .then((entries) => {
      if (!Array.isArray(entries)) return;
      entries.forEach((entry) => {
        if (!entry.name) return;
        state.knownApiNames.add(entry.name);
        state.knownApiNames.add(entry.name.split('/').pop());
      });
    })
    .catch(() => {});

  function resetState() {
    state.line = 1;
    state.vars = new Map([
      ['name', 'world'],
      ['true', true],
      ['false', false],
      ['nil', null],
    ]);
    state.fns = new Map();
    output.innerHTML = '';
    appendOutput('comment', 'Welcome to the Phel demo REPL');
    appendOutput('comment', 'This browser demo simulates a small pure Phel subset. The real REPL unleashes PHP interop, IO, Composer packages, and the full language.');
  }

  function appendOutput(type, text) {
    const shouldFollow = isOutputPinnedToBottom();
    const line = document.createElement('div');
    line.className = `phel-repl-line phel-repl-line--${type}`;
    if (type === 'input') {
      line.innerHTML = highlightPromptLine(text);
    } else if (type === 'result') {
      line.innerHTML = highlightPhel(text);
    } else {
      line.textContent = text;
    }
    output.appendChild(line);

    if (shouldFollow) {
      output.scrollTop = output.scrollHeight;
    }
  }

  function isOutputPinnedToBottom() {
    return output.scrollTop + output.clientHeight >= output.scrollHeight - 8;
  }

  function escapeHtml(text) {
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;');
  }

  function highlightPromptLine(text) {
    const match = text.match(/^((?:user:\d+|\.{4}:\d+)> )(.*)$/);
    if (!match) return highlightPhel(text);

    return `<span class="phel-repl-syntax-prompt">${escapeHtml(match[1])}</span>${highlightPhel(match[2])}`;
  }

  function highlightPhel(code) {
    const tokenPattern = /(;.*$)|("(?:\\.|[^"\\])*")|(:[A-Za-z0-9*+!_?$%&=<>.-]+)|(#\(|[()[\]{}])|(-?\d+(?:\.\d+)?)|([A-Za-z_*+!/?<>=.-][A-Za-z0-9_*+!/?<>=.-]*)/gm;
    let html = '';
    let cursor = 0;

    code.replace(tokenPattern, (match, comment, string, keyword, paren, number, symbol, offset) => {
      html += escapeHtml(code.slice(cursor, offset));
      const escaped = escapeHtml(match);

      if (comment) html += `<span class="phel-repl-syntax-comment">${escaped}</span>`;
      else if (string) html += `<span class="phel-repl-syntax-string">${escaped}</span>`;
      else if (keyword) html += `<span class="phel-repl-syntax-keyword">${escaped}</span>`;
      else if (paren) html += `<span class="phel-repl-syntax-paren">${escaped}</span>`;
      else if (number) html += `<span class="phel-repl-syntax-number">${escaped}</span>`;
      else if (symbol && syntaxSpecialForms.has(symbol)) html += `<span class="phel-repl-syntax-special">${escaped}</span>`;
      else if (symbol && nativeFns[symbol]) html += `<span class="phel-repl-syntax-fn">${escaped}</span>`;
      else if (symbol) html += `<span class="phel-repl-syntax-symbol">${escaped}</span>`;
      else html += escaped;

      cursor = offset + match.length;
      return match;
    });

    return html + escapeHtml(code.slice(cursor));
  }

  function appendPrompt(code) {
    code.split('\n').forEach((line, index) => {
      const currentLine = state.line;
      const prompt = index === 0 ? `user:${currentLine}> ` : `....:${currentLine}> `;
      appendOutput('input', `${prompt}${line}`);
      state.line += 1;
    });
  }

  function fail(message) {
    throw new Error(message);
  }

  function tokenize(source) {
    const tokens = [];
    let i = 0;

    while (i < source.length) {
      const char = source[i];

      if (char === ';') {
        while (i < source.length && source[i] !== '\n') i += 1;
        continue;
      }

      if (/\s/.test(char)) {
        i += 1;
        continue;
      }

      if (char === '#' && source[i + 1] === '(') {
        tokens.push('#(');
        i += 2;
        continue;
      }

      if ('()[]{}'.includes(char)) {
        tokens.push(char);
        i += 1;
        continue;
      }

      if (char === '"') {
        let value = '';
        i += 1;
        while (i < source.length) {
          if (source[i] === '"' && source[i - 1] !== '\\') break;
          value += source[i];
          i += 1;
        }
        if (source[i] !== '"') fail('UnexpectedParserException: unterminated string');
        tokens.push({ type: 'string', value: value.replace(/\\"/g, '"').replace(/\\n/g, '\n') });
        i += 1;
        continue;
      }

      let token = '';
      while (i < source.length && !/\s/.test(source[i]) && !'()[]{}'.includes(source[i])) {
        token += source[i];
        i += 1;
      }
      tokens.push(token);
    }

    return tokens;
  }

  function parse(source) {
    const tokens = tokenize(source);
    let index = 0;
    const forms = [];

    function parseOne() {
      const token = tokens[index];
      index += 1;

      if (token === undefined) fail('UnexpectedParserException: unexpected end of input');
      if (token && typeof token === 'object') return token;

      if (token === '(') return parseDelimited(')', 'list');
      if (token === '[') return parseDelimited(']', 'vector');
      if (token === '{') return parseDelimited('}', 'map');
      if (token === '#(') return parseDelimited(')', 'anon');
      if ([')', ']', '}'].includes(token)) fail(`UnexpectedParserException: unexpected ${token}`);

      if (/^-?\d+(\.\d+)?$/.test(token)) return Number(token);
      if (token.startsWith(':')) return { type: 'keyword', name: token.slice(1) };
      return { type: 'symbol', name: token };
    }

    function parseDelimited(end, type) {
      const items = [];
      while (tokens[index] !== end) {
        if (index >= tokens.length) fail('UnfinishedParserException: expected closing delimiter');
        items.push(parseOne());
      }
      index += 1;
      return { type, items };
    }

    while (index < tokens.length) {
      forms.push(parseOne());
    }

    return forms;
  }

  function envWith(parent, entries = []) {
    return {
      values: new Map(entries),
      parent,
      get(name) {
        if (this.values.has(name)) return this.values.get(name);
        return this.parent?.get(name);
      },
      set(name, value) {
        this.values.set(name, value);
      },
      has(name) {
        return this.values.has(name) || Boolean(this.parent?.has(name));
      },
    };
  }

  function rootEnv() {
    return envWith(null, state.vars);
  }

  function isNode(value, type) {
    return value && typeof value === 'object' && value.type === type;
  }

  function isTruthy(value) {
    return value !== false && value !== null;
  }

  function asArray(value) {
    if (Array.isArray(value)) return value;
    if (value && value.kind === 'list') return value.items;
    if (value === null) return [];
    fail('RuntimeException: expected a collection');
  }

  function mapKey(key) {
    if (key && key.kind === 'keyword') return `:${key.name}`;
    return printValue(key);
  }

  function evalNode(node, env) {
    if (typeof node === 'number') return node;
    if (node && node.type === 'string') return node.value;

    if (isNode(node, 'keyword')) {
      return { kind: 'keyword', name: node.name };
    }

    if (isNode(node, 'symbol')) {
      if (env.has(node.name)) return env.get(node.name);
      return { kind: 'fn', name: node.name };
    }

    if (isNode(node, 'vector')) {
      return node.items.map((item) => evalNode(item, env));
    }

    if (isNode(node, 'map')) {
      const map = new Map();
      for (let i = 0; i < node.items.length; i += 2) {
        map.set(mapKey(evalNode(node.items[i], env)), evalNode(node.items[i + 1], env));
      }
      return { kind: 'map', entries: map };
    }

    if (isNode(node, 'anon')) {
      return {
        kind: 'closure',
        params: ['%'],
        body: [{ type: 'list', items: node.items }],
        env,
      };
    }

    if (isNode(node, 'list')) {
      return evalList(node.items, env);
    }

    return node;
  }

  function evalList(items, env) {
    if (items.length === 0) return { kind: 'list', items: [] };

    const head = items[0];
    const headName = isNode(head, 'symbol') ? head.name : null;

    if (headName === 'quote') return quoteValue(items[1]);

    if (headName === 'def') {
      const name = items[1]?.name;
      if (!name) fail('CompilerException: def expects a symbol');
      const value = evalNode(items[2], env);
      state.vars.set(name, value);
      return { kind: 'var', name };
    }

    if (headName === 'defn') {
      const name = items[1]?.name;
      const params = items[2]?.items?.map((param) => param.name) || [];
      if (!name || params.length === 0) fail('CompilerException: defn expects a name and parameters');
      state.fns.set(name, { kind: 'closure', params, body: items.slice(3), env });
      return { kind: 'var', name };
    }

    if (headName === 'fn') {
      const params = items[1]?.items?.map((param) => param.name) || [];
      return { kind: 'closure', params, body: items.slice(2), env };
    }

    if (headName === 'let') {
      const local = envWith(env);
      const bindings = items[1]?.items || [];
      for (let i = 0; i < bindings.length; i += 2) {
        local.set(bindings[i].name, evalNode(bindings[i + 1], local));
      }
      return evalBody(items.slice(2), local);
    }

    if (headName === 'if') {
      return isTruthy(evalNode(items[1], env))
        ? evalNode(items[2], env)
        : evalNode(items[3], env);
    }

    if (headName === 'do') {
      return evalBody(items.slice(1), env);
    }

    if (headName === 'and') {
      let result = true;
      for (const item of items.slice(1)) {
        result = evalNode(item, env);
        if (!isTruthy(result)) return result;
      }
      return result;
    }

    if (headName === 'or') {
      for (const item of items.slice(1)) {
        const result = evalNode(item, env);
        if (isTruthy(result)) return result;
      }
      return null;
    }

    if (headName === '->' || headName === '->>') {
      return evalThread(headName, items.slice(1), env);
    }

    const fn = evalNode(head, env);
    const args = items.slice(1).map((item) => evalNode(item, env));
    return callFn(fn, args, env);
  }

  function evalBody(body, env) {
    let result = null;
    body.forEach((item) => {
      result = evalNode(item, env);
    });
    return result;
  }

  function evalThread(kind, forms, env) {
    let value = evalNode(forms[0], env);

    forms.slice(1).forEach((form) => {
      if (isNode(form, 'list')) {
        const threaded = kind === '->'
          ? [form.items[0], literalNode(value), ...form.items.slice(1)]
          : [form.items[0], ...form.items.slice(1), literalNode(value)];
        value = evalNode({ type: 'list', items: threaded }, env);
      } else {
        value = callFn(evalNode(form, env), [value], env);
      }
    });

    return value;
  }

  function literalNode(value) {
    return { type: 'literal', value };
  }

  const originalEvalNode = evalNode;
  evalNode = function evalNodeWithLiteral(node, env) {
    if (node && node.type === 'literal') return node.value;
    return originalEvalNode(node, env);
  };

  function quoteValue(node) {
    if (typeof node === 'number') return node;
    if (node && node.type === 'string') return node.value;
    if (isNode(node, 'keyword')) return { kind: 'keyword', name: node.name };
    if (isNode(node, 'symbol')) return { kind: 'symbol', name: node.name };
    if (isNode(node, 'vector')) return node.items.map(quoteValue);
    if (isNode(node, 'list')) return { kind: 'list', items: node.items.map(quoteValue) };
    return node;
  }

  function callFn(fn, args) {
    if (fn && fn.kind === 'keyword') {
      return nativeGet([args[0], fn]);
    }

    if (fn && fn.kind === 'closure') {
      const local = envWith(fn.env);
      fn.params.forEach((param, index) => local.set(param, args[index]));
      return evalBody(fn.body, local);
    }

    const name = fn?.name;
    if (!name) fail('RuntimeException: cannot call value as function');

    if (state.fns.has(name)) {
      return callFn(state.fns.get(name), args);
    }

    if (nativeFns[name]) return nativeFns[name](args);

    if (state.knownApiNames.has(name)) {
      fail(unsupportedFunctionMessage(name));
    }

    if (isNamespacedFunction(name)) {
      fail(unsupportedFunctionMessage(name));
    }

    fail(`CompilerException: Cannot resolve symbol ${name}`);
  }

  function isNamespacedFunction(name) {
    return name.includes('/');
  }

  function unsupportedFunctionMessage(name) {
    const namespace = name.includes('/') ? name.split('/')[0] : null;
    const reason = namespace === 'php'
      ? 'that would use PHP interop'
      : 'that is outside this small pure-function subset';

    return `Demo REPL limit: ${name} is not executed here because ${reason}. Use the real Phel REPL for the full language; this browser preview supports pure examples like +, range, map, filter, reduce, get, assoc, let, if, and ->>.`;
  }

  function nativeGet(args) {
    const [coll, key, fallback = null] = args;
    if (coll && coll.kind === 'map') {
      const value = coll.entries.get(mapKey(key));
      return value === undefined ? fallback : value;
    }
    if (Array.isArray(coll)) {
      return coll[Number(key)] ?? fallback;
    }
    return fallback;
  }

  function applyComparable(args, compare) {
    return args.slice(1).every((value, index) => compare(args[index], value));
  }

  const nativeFns = {
    '+': (args) => args.reduce((sum, value) => sum + Number(value), 0),
    '-': (args) => args.length === 1 ? -Number(args[0]) : args.slice(1).reduce((res, value) => res - Number(value), Number(args[0])),
    '*': (args) => args.reduce((res, value) => res * Number(value), 1),
    '/': (args) => args.slice(1).reduce((res, value) => res / Number(value), Number(args[0])),
    inc: ([value]) => Number(value) + 1,
    dec: ([value]) => Number(value) - 1,
    mod: ([a, b]) => Number(a) % Number(b),
    quot: ([a, b]) => Math.trunc(Number(a) / Number(b)),
    min: (args) => Math.min(...args.map(Number)),
    max: (args) => Math.max(...args.map(Number)),
    abs: ([value]) => Math.abs(Number(value)),
    '=': (args) => args.slice(1).every((value) => printValue(value) === printValue(args[0])),
    'not=': (args) => !nativeFns['='](args),
    '<': (args) => applyComparable(args, (a, b) => a < b),
    '<=': (args) => applyComparable(args, (a, b) => a <= b),
    '>': (args) => applyComparable(args, (a, b) => a > b),
    '>=': (args) => applyComparable(args, (a, b) => a >= b),
    not: ([value]) => !isTruthy(value),
    'nil?': ([value]) => value === null,
    'some?': ([value]) => value !== null,
    'true?': ([value]) => value === true,
    'false?': ([value]) => value === false,
    'number?': ([value]) => typeof value === 'number',
    'string?': ([value]) => typeof value === 'string',
    'keyword?': ([value]) => value?.kind === 'keyword',
    'vector?': ([value]) => Array.isArray(value),
    'list?': ([value]) => value?.kind === 'list',
    'map?': ([value]) => value?.kind === 'map',
    'empty?': ([value]) => asArrayOrMap(value).length === 0,
    'odd?': ([value]) => Math.abs(Number(value) % 2) === 1,
    'even?': ([value]) => Number(value) % 2 === 0,
    'zero?': ([value]) => Number(value) === 0,
    'pos?': ([value]) => Number(value) > 0,
    'neg?': ([value]) => Number(value) < 0,
    str: (args) => args.map((value) => typeof value === 'string' ? value : printValue(value).replace(/^"|"$/g, '')).join(''),
    println: (args) => {
      appendOutput('stdout', args.map((value) => typeof value === 'string' ? value : printValue(value)).join(' '));
      return null;
    },
    print: (args) => {
      appendOutput('stdout', args.map((value) => typeof value === 'string' ? value : printValue(value)).join(''));
      return null;
    },
    vector: (args) => args,
    list: (args) => ({ kind: 'list', items: args }),
    vec: ([value]) => asArray(value).slice(),
    count: ([value]) => asArrayOrMap(value).length,
    first: ([value]) => asArray(value)[0] ?? null,
    second: ([value]) => asArray(value)[1] ?? null,
    last: ([value]) => {
      const values = asArray(value);
      return values[values.length - 1] ?? null;
    },
    rest: ([value]) => asArray(value).slice(1),
    next: ([value]) => {
      const rest = asArray(value).slice(1);
      return rest.length > 0 ? rest : null;
    },
    cons: ([value, coll]) => [value, ...asArray(coll)],
    conj: ([coll, ...values]) => Array.isArray(coll) ? [...coll, ...values] : { kind: 'list', items: [...values.reverse(), ...asArray(coll)] },
    range: (args) => {
      const start = args.length === 1 ? 0 : Number(args[0]);
      const end = Number(args.length === 1 ? args[0] : args[1]);
      const step = Number(args[2] ?? 1);
      const values = [];
      for (let i = start; step > 0 ? i < end : i > end; i += step) values.push(i);
      return values;
    },
    take: ([n, coll]) => asArray(coll).slice(0, Number(n)),
    drop: ([n, coll]) => asArray(coll).slice(Number(n)),
    reverse: ([coll]) => asArray(coll).slice().reverse(),
    sort: ([coll]) => asArray(coll).slice().sort((a, b) => a > b ? 1 : -1),
    map: ([fn, coll]) => asArray(coll).map((value) => callFn(fn, [value])),
    filter: ([fn, coll]) => asArray(coll).filter((value) => isTruthy(callFn(fn, [value]))),
    reduce: ([fn, initialOrColl, maybeColl]) => {
      const hasInitial = maybeColl !== undefined;
      const values = asArray(hasInitial ? maybeColl : initialOrColl);
      if (values.length === 0 && !hasInitial) return null;
      let acc = hasInitial ? initialOrColl : values[0];
      values.slice(hasInitial ? 0 : 1).forEach((value) => {
        acc = callFn(fn, [acc, value]);
      });
      return acc;
    },
    get: nativeGet,
    assoc: ([map, ...pairs]) => {
      const entries = new Map(map?.entries || []);
      for (let i = 0; i < pairs.length; i += 2) entries.set(mapKey(pairs[i]), pairs[i + 1]);
      return { kind: 'map', entries };
    },
    dissoc: ([map, ...keys]) => {
      const entries = new Map(map?.entries || []);
      keys.forEach((key) => entries.delete(mapKey(key)));
      return { kind: 'map', entries };
    },
    keys: ([map]) => [...(map?.entries?.keys() || [])].map((key) => key.startsWith(':') ? { kind: 'keyword', name: key.slice(1) } : key),
    vals: ([map]) => [...(map?.entries?.values() || [])],
    'contains?': ([map, key]) => Boolean(map?.entries?.has(mapKey(key))),
    'hash-map': (args) => nativeFns.assoc([{ kind: 'map', entries: new Map() }, ...args]),
  };

  const syntaxSpecialForms = new Set([
    'def',
    'defn',
    'fn',
    'let',
    'if',
    'do',
    'and',
    'or',
    'quote',
    '->',
    '->>',
  ]);

  function asArrayOrMap(value) {
    if (value && value.kind === 'map') return [...value.entries];
    return asArray(value);
  }

  function printValue(value) {
    if (value === null || value === undefined) return 'nil';
    if (typeof value === 'number' || typeof value === 'boolean') return String(value);
    if (typeof value === 'string') return `"${value}"`;
    if (Array.isArray(value)) return `@[${value.map(printValue).join(' ')}]`;
    if (value.kind === 'keyword') return `:${value.name}`;
    if (value.kind === 'symbol') return value.name;
    if (value.kind === 'var') return `#'user/${value.name}`;
    if (value.kind === 'list') return `(${value.items.map(printValue).join(' ')})`;
    if (value.kind === 'map') {
      return `{${[...value.entries].map(([key, val]) => `${key} ${printValue(val)}`).join(' ')}}`;
    }
    if (value.kind === 'fn' || value.kind === 'closure') return '#<function>';
    return String(value);
  }

  function runInput() {
    const code = input.value.trim();
    if (code === '') return;

    appendPrompt(code);

    try {
      const forms = parse(code);
      const env = rootEnv();
      let result = null;

      forms.forEach((form) => {
        result = evalNode(form, env);
      });

      if (result !== null) appendOutput('result', printValue(result));
    } catch (error) {
      appendOutput('error', error.message || 'RuntimeException: evaluation failed');
    }
  }

  runButton.addEventListener('click', runInput);
  resetButton.addEventListener('click', () => {
    input.value = initialCode;
    resetState();
    input.focus();
  });

  input.addEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
      event.preventDefault();
      runInput();
    }
  });

  resetState();
});
