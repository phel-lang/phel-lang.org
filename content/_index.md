+++
title = "Phel: A Functional Lisp Dialect for PHP Developers"
+++

<section class="homepage-hero">
  <div class="homepage-hero-text">
    <img class="homepage-hero-logo" src="/images/logo_phel.svg" width="96" height="96" alt="Phel language logo" fetchpriority="high" decoding="async"/>
    <h1 class="homepage-hero-title">Functional Lisp <span class="homepage-hero-accent">for PHP developers</span></h1>
    <p class="homepage-hero-lede">Phel compiles a Lisp dialect to PHP. Macros, persistent data structures, and REPL-driven development on any PHP host.</p>
    <div class="homepage-hero-actions">
      <a href="#try-it-in-30-seconds" class="homepage-cta-button homepage-cta-primary">
        <svg class="homepage-cta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
        Quick start
      </a>
      <a href="/documentation/getting-started" class="homepage-cta-button homepage-cta-secondary">
        <svg class="homepage-cta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
        Read docs
      </a>
      <a href="/practice/basic" class="homepage-hero-link">Practice exercises <span aria-hidden="true">&rarr;</span></a>
    </div>
  </div>
  <div class="homepage-hero-aside">
    <div id="animated-repl" aria-label="Phel REPL animation"></div>
    <noscript>
<pre class="phel-terminal-session is-active"><span class="t-p">&gt;&gt;&gt;</span> <span class="t-in">(map inc [1 2 3])</span>
<span class="t-out">(2 3 4)</span>
<span class="t-p">&gt;&gt;&gt;</span> <span class="t-in">(-&gt;&gt; (range 1 6) (filter odd?) (reduce +))</span>
<span class="t-out">9</span>
<span class="t-p">&gt;&gt;&gt;</span> <span class="t-in">(defn greet [name] (str "hello, " name))</span>
<span class="t-out">user/greet</span>
<span class="t-p">&gt;&gt;&gt;</span> <span class="t-in">(greet "phel")</span>
<span class="t-out">"hello, phel"</span></pre>
    </noscript>
  </div>
</section>

<ul class="phel-meta">
  <li class="phel-meta-pill">
    <a href="/releases/" class="phel-meta-link">
      <span class="phel-meta-key">Latest</span>
      <span class="phel-meta-val">{{ phel_version() }}</span>
    </a>
  </li>
  <li class="phel-meta-pill">
    <a href="/documentation/installation" class="phel-meta-link">
      <span class="phel-meta-key">Requires</span>
      <span class="phel-meta-val">PHP 8.4+</span>
    </a>
  </li>
  <li class="phel-meta-pill">
    <a href="https://github.com/phel-lang/phel-lang/blob/main/LICENSE" class="phel-meta-link" rel="noopener">
      <span class="phel-meta-key">License</span>
      <span class="phel-meta-val">MIT</span>
    </a>
  </li>
  <li class="phel-meta-pill">
    <a href="https://github.com/phel-lang/phel-lang" class="phel-meta-link" rel="noopener" data-gh-stars>
      <span class="phel-meta-key">Stars</span>
      <span class="phel-meta-val"><span data-gh-stars-count>500+</span></span>
    </a>
  </li>
  <li class="phel-meta-pill">
    <a href="https://github.com/phel-lang/phel-lang/releases/latest" class="phel-meta-link" rel="noopener" data-gh-release>
      <span class="phel-meta-key">Source</span>
      <span class="phel-meta-val">GitHub <span aria-hidden="true">&rarr;</span></span>
    </a>
  </li>
</ul>

## Phel by example

<div class="homepage-code-tabs" data-homepage-tabs>
  <div class="homepage-code-tabs-nav" role="tablist" aria-label="Phel code examples">
    <button class="homepage-tab-btn is-active" data-tab="vs" role="tab" aria-selected="true" tabindex="0">vs PHP</button>
    <button class="homepage-tab-btn" data-tab="fp" role="tab" aria-selected="false" tabindex="-1">Functional</button>
    <button class="homepage-tab-btn" data-tab="interop" role="tab" aria-selected="false" tabindex="-1">PHP Interop</button>
    <button class="homepage-tab-btn" data-tab="macros" role="tab" aria-selected="false" tabindex="-1">Macros</button>
    <button class="homepage-tab-btn" data-tab="tests" role="tab" aria-selected="false" tabindex="-1">Tests</button>
  </div>

  <div class="homepage-tab-panel is-active" data-panel="vs" role="tabpanel">

<div class="vs-grid">

<div class="vs-col">

<div class="vs-label">PHP</div>

```php
$r = array_sum(
  array_map(
    fn($n) => $n * $n,
    array_filter(range(1, 10), fn($n) => $n % 2)
  )
);
```

</div>

<div class="vs-col">

<div class="vs-label">Phel</div>

```phel
(->> (range 1 11)
     (filter odd?)
     (map #(* % %))
     (reduce +))
```

<p class="vs-caption">Same task, read top to bottom. No nested calls.</p>

</div>

</div>

  </div>

  <div class="homepage-tab-panel" data-panel="fp" role="tabpanel" hidden>

<div class="tab-split">

<div class="tab-split-code">

```phel
(->> (range 1 11)
     (filter odd?)
     (map #(* % %))
     (reduce +))
# => 165
```

</div>

<div class="tab-split-info">

<div class="tab-split-title">Threading pipelines</div>

- Reads top to bottom, no nested calls
- Each step is one pure function
- Immutable data, predictable output
- Same `->>` macro PHP devs miss from RxJS or pipe operators

</div>

</div>

  </div>

  <div class="homepage-tab-panel" data-panel="interop" role="tabpanel" hidden>

<div class="tab-split">

<div class="tab-split-code">

```phel
(php/strlen "hello, phel")
# => 11

(php/array_map php/strtoupper ["foo" "bar" "baz"])
# => ["FOO" "BAR" "BAZ"]

(php/-> (php/new \DateTimeImmutable) (format "Y"))
# => "2026"
```

</div>

<div class="tab-split-info">

<div class="tab-split-title">Direct PHP interop</div>

- `php/` prefix calls any built-in function
- `php/->` is the PHP method-call operator
- `php/new` constructs PHP classes
- Composer packages work without wrappers

</div>

</div>

  </div>

  <div class="homepage-tab-panel" data-panel="macros" role="tabpanel" hidden>

<div class="tab-split">

<div class="tab-split-code">

```phel
(defmacro unless [pred & body]
  `(if (not ~pred) (do ~@body)))

(unless (zero? x)
  (println "x is non-zero")
  (/ 10 x))
```

</div>

<div class="tab-split-info">

<div class="tab-split-title">Code as data</div>

- `defmacro` extends the language at compile time
- Backtick + `~`, `~@` build syntax trees
- Zero runtime cost, expands before compilation
- Impossible in a library, only in a Lisp

</div>

</div>

  </div>

  <div class="homepage-tab-panel" data-panel="tests" role="tabpanel" hidden>

<div class="tab-split">

<div class="tab-split-code">

```phel
(ns my\sum-test
  (:require phel\test :refer [deftest is]))

(deftest sum-test
  (is (= 6 (+ 1 2 3)))
  (is (= [1 4 9] (map #(* % %) [1 2 3]))))
```

</div>

<div class="tab-split-info">

<div class="tab-split-title">Tests are functions</div>

- `deftest` + `is`, no extra framework
- Plain Phel, REPL-friendly
- Run with `vendor/bin/phel test`
- Same namespace and dependency model as production code

</div>

</div>

  </div>
</div>

## Why Phel?

<div class="positioning-grid">
  <div class="positioning-card">
    <div class="positioning-card-tag">vs vanilla PHP</div>
    <h3 class="positioning-card-title">More expression. Same runtime.</h3>
    <p>Macros, persistent collections, and a REPL, without leaving the PHP ecosystem. Composer, FPM, shared hosting all work.</p>
  </div>
  <div class="positioning-card">
    <div class="positioning-card-tag">vs Clojure on JVM</div>
    <h3 class="positioning-card-title">Lisp without the JVM.</h3>
    <p>Same Lisp ideas as Clojure, deployed like a PHP app. No JVM warmup, no AOT pipeline, no extra hosting target.</p>
  </div>
  <div class="positioning-card">
    <div class="positioning-card-tag">vs PHP FP libraries</div>
    <h3 class="positioning-card-title">Language, not library.</h3>
    <p>Real homoiconic syntax, real macros, real tail-call elimination. A library can't extend PHP's grammar. Phel doesn't need to.</p>
  </div>
</div>

## Try it in 30 seconds

<ol class="quickstart-grid">
  <li class="quickstart-card">
    <div class="quickstart-step">1</div>
    <h3 class="quickstart-title">Docker</h3>
    <p class="quickstart-desc">Try without installing anything.</p>

```bash
docker run -it --rm phellang/repl
```

  </li>
  <li class="quickstart-card">
    <div class="quickstart-step">2</div>
    <h3 class="quickstart-title">Composer</h3>
    <p class="quickstart-desc">Add to an existing PHP project.</p>

```bash
composer require phel-lang/phel-lang
vendor/bin/phel repl
```

  </li>
  <li class="quickstart-card">
    <div class="quickstart-step">3</div>
    <h3 class="quickstart-title">PHAR</h3>
    <p class="quickstart-desc">Single-file binary, no Composer required.</p>

```bash
curl -L https://phel-lang.org/phar -o phel.phar
php phel.phar repl
```

  </li>
</ol>

<p class="quickstart-followup">Full walkthrough in the <a href="/documentation/installation">installation guide</a>.</p>

## Common questions

<div class="faq">
  <details class="faq-item">
    <summary class="faq-q">Is Phel production-ready?</summary>
    <div class="faq-a">Not yet. Phel is pre-1.0. The core language and tooling are usable for personal projects, prototypes, and internal tools, but breaking changes can land between minor releases. Track <a href="/releases/">release notes</a> if you depend on it.</div>
  </details>
  <details class="faq-item">
    <summary class="faq-q">Can I call PHP libraries from Phel?</summary>
    <div class="faq-a">Yes. Phel compiles to PHP, so any Composer package, function, or class is directly callable via the <code>php/</code> prefix. See the <a href="/documentation/php-interop">interop guide</a>.</div>
  </details>
  <details class="faq-item">
    <summary class="faq-q">How is Phel different from Clojure?</summary>
    <div class="faq-a">Phel borrows ideas from Lisp and Clojure (immutable data, macros, threading) but targets the PHP runtime, not the JVM. No agents, no STM, no atoms. Phel maps to PHP's execution model. See the <a href="/blog/functional-programming-in-php">design rationale</a>.</div>
  </details>
  <details class="faq-item">
    <summary class="faq-q">What PHP version do I need?</summary>
    <div class="faq-a">The current release targets PHP 8.4 or later. Earlier Phel versions support older PHP releases if you need them.</div>
  </details>
  <details class="faq-item">
    <summary class="faq-q">Where do I get help?</summary>
    <div class="faq-a">Open a thread on <a href="https://github.com/phel-lang/phel-lang/discussions">GitHub Discussions</a>, file an issue on <a href="https://github.com/phel-lang/phel-lang/issues">GitHub</a>, or read the <a href="/documentation/">full documentation</a>.</div>
  </details>
</div>

<section class="homepage-final-cta">
  <h2 class="homepage-final-cta-title">Ready to try Phel?</h2>
  <p class="homepage-final-cta-text">Open the REPL, install via Composer, or jump into the docs.</p>
  <div class="homepage-cta">
    <a href="#try-it-in-30-seconds" class="homepage-cta-button homepage-cta-primary">Quick start</a>
    <a href="/documentation/getting-started" class="homepage-cta-button homepage-cta-secondary">Read docs</a>
    <a href="https://github.com/phel-lang/phel-lang" class="homepage-cta-button homepage-cta-secondary">GitHub</a>
  </div>
</section>
