+++
title = "Phel: A Functional Lisp Dialect for PHP Developers"
+++

<section class="homepage-hero">
  <div class="homepage-hero-text">
    <img class="homepage-hero-logo" src="/images/logo_phel.svg" width="96" height="96" alt="Phel language logo" fetchpriority="high" decoding="async"/>
    <h1 class="homepage-hero-title">Functional Lisp <span class="homepage-hero-accent">for PHP developers</span></h1>
    <p class="homepage-hero-lede">Phel compiles a Lisp dialect to PHP. Macros, persistent data structures, and REPL-driven development on any PHP host.</p>
    <div class="homepage-hero-actions">
      <a href="/repl/" class="homepage-cta-button homepage-cta-primary">
        <svg class="homepage-cta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 5.2 18.6 12 7 18.8Z"></path></svg>
        Try it in your browser
      </a>
      <a href="#try-it-in-30-seconds" class="homepage-cta-button homepage-cta-secondary">
        <svg class="homepage-cta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v11"></path><path d="m7 10 5 5 5-5"></path><path d="M5 20h14"></path></svg>
        Install
      </a>
      <a href="/documentation/" class="homepage-hero-link">Docs <span aria-hidden="true">&rarr;</span></a>
    </div>
    <p class="homepage-hero-note">The browser REPL downloads about 80 MB. Desktop recommended.</p>
    <ul class="homepage-facts" aria-label="Production facts">
      <li>Requires <strong>PHP 8.5+</strong></li>
      <li>Pre-1.0. Read the <a href="/documentation/stability/">stability policy</a></li>
      <li>PHP-FPM, FrankenPHP or RoadRunner. <a href="/documentation/deployment/">Deploy guide</a></li>
    </ul>
  </div>
  <div class="homepage-hero-aside">
    {{ hero_repl() }}
  </div>
</section>

<section class="homepage-section compile-showcase" aria-labelledby="phel-in-php-out">

## Phel in, PHP out {#phel-in-php-out}

<p class="compile-showcase-lede">The compiler emits plain PHP. The <code>-&gt;&gt;</code> macro expands away. PHP functions are called directly.</p>

<div class="compile-split">
<div class="compile-pane">
<div class="compile-pane-label">You write Phel</div>

```phel
(defn slugify [title]
  (->> title
       php/trim
       php/strtolower
       (php/str_replace " " "-")))

(slugify "  Hello Phel World ")
; => "hello-phel-world"
```

</div>
<div class="compile-pane">
<div class="compile-pane-label">Phel emits PHP <span class="compile-pane-meta">metadata trimmed</span></div>

```php
\Phel::addDefinition(
  "user",
  "slugify",
  new class() extends \Phel\Lang\AbstractFn {
    public const BOUND_TO = "user\\slugify";

    public function __invoke($title) {
      return str_replace(" ", "-", strtolower(trim($title)));
    }
  },
  // ...source location and doc metadata
);
```

</div>
</div>

<p class="compile-showcase-note">Real output of <code>phel compile</code> for the <code>defn</code>. Run it on any snippet to see the PHP.</p>

</section>

<section class="homepage-section homepage-section--alt">

## Phel by example

<div class="homepage-code-tabs" data-homepage-tabs>
  <div class="homepage-code-tabs-nav" role="tablist" aria-label="Phel features">
    <button class="homepage-tab-btn is-active" data-tab="fp" role="tab" aria-selected="true" tabindex="0">Functional</button>
    <button class="homepage-tab-btn" data-tab="interop" role="tab" aria-selected="false" tabindex="-1">PHP Interop</button>
    <button class="homepage-tab-btn" data-tab="macros" role="tab" aria-selected="false" tabindex="-1">Macros</button>
    <button class="homepage-tab-btn" data-tab="tests" role="tab" aria-selected="false" tabindex="-1">Tests</button>
  </div>

  <div class="homepage-tab-panel is-active" data-panel="fp" role="tabpanel">

<div class="tab-split">
<div class="tab-split-code">

```phel
(->> (range 1 11)
     (filter odd?)
     (map #(* % %))
     (reduce +))
; => 165
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
; => 11

(php/array_sum
  (to-array [1 2 3 4]))
; => 10

(.format (new \DateTime) "Y")
; => "2026"
```

</div>
<div class="tab-split-info">

<div class="tab-split-title">Direct PHP interop</div>

- `php/` prefix calls any built-in function
- `.method` calls PHP methods on objects
- `new` constructs PHP classes
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

(def x 5)
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
(ns my.sum-test
  (:require phel.test
    :refer [deftest is]))

(deftest sum-test
  (is (= 6 (+ 1 2 3)))
  (is (= [1 4 9]
         (map #(* % %)
              [1 2 3]))))
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

</section>

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

<section class="homepage-section homepage-section--alt">

## Try it in 30 seconds

<div class="homepage-code-tabs" data-homepage-tabs>
  <div class="homepage-code-tabs-nav" role="tablist" aria-label="Install Phel">
    <button class="homepage-tab-btn is-active" data-tab="docker" role="tab" aria-selected="true" tabindex="0"><svg class="homepage-tab-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.983 11.078h2.119a.186.186 0 0 0 .186-.185V9.006a.186.186 0 0 0-.186-.186h-2.119a.185.185 0 0 0-.185.185v1.888c0 .102.083.185.185.185m-2.954-5.43h2.118a.186.186 0 0 0 .186-.186V3.574a.186.186 0 0 0-.186-.185h-2.118a.185.185 0 0 0-.185.185v1.888c0 .102.082.185.185.186m0 2.716h2.118a.187.187 0 0 0 .186-.186V6.29a.186.186 0 0 0-.186-.185h-2.118a.185.185 0 0 0-.185.185v1.887c0 .102.082.185.185.186m-2.93 0h2.12a.186.186 0 0 0 .184-.186V6.29a.185.185 0 0 0-.185-.185H8.1a.185.185 0 0 0-.185.185v1.887c0 .102.083.185.185.186m-2.964 0h2.119a.186.186 0 0 0 .185-.186V6.29a.185.185 0 0 0-.185-.185H5.136a.186.186 0 0 0-.186.185v1.887c0 .102.084.185.186.186m5.893 2.715h2.118a.186.186 0 0 0 .186-.185V9.006a.186.186 0 0 0-.186-.186h-2.118a.185.185 0 0 0-.185.185v1.888c0 .102.082.185.185.185m-2.93 0h2.12a.185.185 0 0 0 .184-.185V9.006a.185.185 0 0 0-.184-.186h-2.12a.185.185 0 0 0-.184.185v1.888c0 .102.083.185.185.185m-2.964 0h2.119a.185.185 0 0 0 .185-.185V9.006a.185.185 0 0 0-.184-.186h-2.12a.186.186 0 0 0-.186.186v1.887c0 .102.084.185.186.185m-2.92 0h2.12a.185.185 0 0 0 .184-.185V9.006a.185.185 0 0 0-.184-.186h-2.12a.185.185 0 0 0-.184.185v1.888c0 .102.082.185.185.185M23.763 9.89c-.065-.051-.672-.51-1.954-.51-.338.001-.676.03-1.01.087-.248-1.7-1.653-2.53-1.716-2.566l-.344-.199-.226.327c-.284.438-.49.922-.612 1.43-.23.97-.09 1.882.403 2.661-.595.332-1.55.413-1.744.42H.751a.751.751 0 0 0-.75.748 11.376 11.376 0 0 0 .692 4.062c.545 1.428 1.355 2.48 2.41 3.124 1.18.723 3.1 1.137 5.275 1.137a16.09 16.09 0 0 0 2.913-.265 12.028 12.028 0 0 0 3.778-1.388c.965-.578 1.831-1.31 2.566-2.17 1.227-1.435 1.958-3.035 2.503-4.455h.216c1.37 0 2.213-.549 2.678-1.009.309-.293.55-.65.707-1.046l.098-.288Z"/></svg>Docker</button>
    <button class="homepage-tab-btn" data-tab="composer" role="tab" aria-selected="false" tabindex="-1"><svg class="homepage-tab-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>Composer</button>
    <button class="homepage-tab-btn" data-tab="phar" role="tab" aria-selected="false" tabindex="-1"><svg class="homepage-tab-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="5" rx="1"/><path d="M4 9v10a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/><line x1="10" y1="13" x2="14" y2="13"/></svg>PHAR</button>
  </div>

  <div class="homepage-tab-panel is-active" data-panel="docker" role="tabpanel">

<p class="homepage-tab-caption">Try without installing anything. Drops you into a REPL.</p>

```bash
docker run --rm -it php:8.5-cli sh -c \
  "curl -sL https://phel-lang.org/phar -o /tmp/phel.phar \
  && php /tmp/phel.phar repl"
```

  </div>

  <div class="homepage-tab-panel" data-panel="composer" role="tabpanel" hidden>

<p class="homepage-tab-caption">Add to an existing PHP project.</p>

```bash
composer require phel-lang/phel-lang
vendor/bin/phel repl
```

  </div>

  <div class="homepage-tab-panel" data-panel="phar" role="tabpanel" hidden>

<p class="homepage-tab-caption">Single-file binary, no Composer required.</p>

```bash
curl -L https://phel-lang.org/phar -o phel.phar
php phel.phar repl
```

  </div>
</div>

<p class="quickstart-followup">Full walkthrough in the <a href="/documentation/installation">installation guide</a>.</p>

</section>

## Common questions

<div class="faq">
  <details class="faq-item">
    <summary class="faq-q">Is Phel production-ready?</summary>
    <div class="faq-a">Phel is pre-1.0, but the core language and tooling are stable and tested: a good fit for side projects, CLI apps, internal tools, and prototypes. Breaking changes can still land between minor releases, so it isn't LTS-grade enterprise-stable yet. The <a href="/documentation/stability/">stability policy</a> spells out what <code>1.0</code> will freeze, and the <a href="/documentation/deployment/">deployment guide</a> covers FPM and worker runtimes. Full picture in <a href="/documentation/why-phel/#is-phel-production-ready">Why Phel</a>.</div>
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
    <div class="faq-a">The current release targets PHP 8.5 or later. Earlier Phel versions support older PHP releases if you need them.</div>
  </details>
  <details class="faq-item">
    <summary class="faq-q">Where do I get help?</summary>
    <div class="faq-a">Open a thread on <a href="https://github.com/phel-lang/phel-lang/discussions">GitHub Discussions</a>, file an issue on <a href="https://github.com/phel-lang/phel-lang/issues">GitHub</a>, or read the <a href="/documentation/">full documentation</a>.</div>
  </details>
</div>

<section class="homepage-section homepage-section--alt homepage-final-cta" aria-labelledby="final-cta-title">
  <h2 id="final-cta-title" class="homepage-final-cta-title">Write your first Phel function today</h2>
  <p class="homepage-final-cta-lede">Install it, open a REPL, write a function. Then drill the basics in Practice.</p>
  <div class="homepage-hero-actions homepage-final-cta-actions">
    <a href="/documentation/getting-started/" class="homepage-cta-button homepage-cta-primary">Get started</a>
    <a href="/practice/" class="homepage-cta-button homepage-cta-secondary">Practice exercises</a>
    <a href="/repl/" class="homepage-hero-link">Try it in your browser <span aria-hidden="true">&rarr;</span></a>
  </div>
</section>
