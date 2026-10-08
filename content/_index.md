+++
title = "Phel: A Functional Lisp Dialect for PHP Developers"
aliases = ["/documentation/why-phel/"]
+++

<section class="ph-hero" aria-labelledby="ph-title">
  <svg class="ph-mark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200.3 167.7" width="104" height="87" role="img" aria-label="Phel language logo"><path pathLength="1" d="M6 66l95 96h36V93l-36-74H42L6 56v106h36l24-35"/><path pathLength="1" d="M137 93l58-3-12-58-32-26h-34l-16 13-23 9-12 22 46 52z"/><path pathLength="1" d="M195 90l-12 57h-13l-2-56m2 56l-13-11h12"/></svg>
  <h1 id="ph-title" class="ph-title">Functional Lisp <span class="ph-title-accent">for PHP developers</span></h1>
  <p class="ph-lede">Phel compiles a Lisp dialect to PHP. Macros, persistent data structures, and REPL-driven development on any PHP host.</p>
  <div class="ph-actions">
    <a href="/repl/" class="ph-sx ph-sx--primary"><span class="ph-paren" aria-hidden="true">(</span>Try it in your browser<span class="ph-paren" aria-hidden="true">)</span></a>
    <a href="/documentation/" class="ph-sx"><span class="ph-paren" aria-hidden="true">(</span>Read the docs<span class="ph-paren" aria-hidden="true">)</span></a>
  </div>
  <p class="ph-note">The browser REPL downloads about 80 MB. Desktop recommended.</p>

  <div class="ph-console ph-frame" data-homepage-tabs>
    <div class="ph-console-bar">
      <h2 id="try-it-in-30-seconds" class="ph-console-title">Try it in 30 seconds</h2>
      <div class="ph-console-tabs" role="tablist" aria-label="Install Phel">
        <button class="homepage-tab-btn is-active" data-tab="docker" role="tab" aria-selected="true" tabindex="0">Docker</button>
        <button class="homepage-tab-btn" data-tab="composer" role="tab" aria-selected="false" tabindex="-1">Composer</button>
        <button class="homepage-tab-btn" data-tab="phar" role="tab" aria-selected="false" tabindex="-1">PHAR</button>
      </div>
    </div>

  <div class="homepage-tab-panel is-active" data-panel="docker" role="tabpanel">

<p class="ph-console-caption">Try without installing anything. Drops you into a REPL.</p>

```bash
docker run --rm -it php:8.5-cli sh -c \
  "curl -sL https://phel-lang.org/phar -o /tmp/phel.phar \
  && php /tmp/phel.phar repl"
```

  </div>

  <div class="homepage-tab-panel" data-panel="composer" role="tabpanel" hidden>

<p class="ph-console-caption">Add to an existing PHP project.</p>

```bash
composer require phel-lang/phel-lang
vendor/bin/phel repl
```

  </div>

  <div class="homepage-tab-panel" data-panel="phar" role="tabpanel" hidden>

<p class="ph-console-caption">Single-file binary, no Composer required.</p>

```bash
curl -L https://phel-lang.org/phar -o phel.phar
php phel.phar repl
```

  </div>
  </div>

  <p class="ph-followup">Full walkthrough in the <a href="/documentation/installation/">installation guide</a>.</p>

  <ul class="ph-facts" aria-label="Production facts">
    <li>Requires <strong>PHP 8.5+</strong></li>
    <li>Stable 1.x. Read the <a href="/documentation/reference/stability/">stability policy</a></li>
    <li>PHP-FPM, FrankenPHP or RoadRunner. <a href="/documentation/guides/deployment/">Deploy guide</a></li>
  </ul>
</section>

<div class="ph-story">

<section class="ph-beat" aria-labelledby="why-phel">
<div class="ph-beat-text">

## Why Phel? { #why-phel }

<p><strong>Against vanilla PHP: more expression, same runtime.</strong> Macros, persistent collections, and a REPL, without leaving the PHP ecosystem. Composer, FPM, shared hosting all work.</p>

<p><strong>Against Clojure: Lisp without the JVM.</strong> Same Lisp ideas as Clojure, deployed like a PHP app. No JVM warmup, no AOT pipeline, no extra hosting target.</p>

<p><strong>Against PHP FP libraries: a language, not a library.</strong> Real homoiconic syntax, real macros, real tail-call elimination. A library can't extend PHP's grammar. Phel doesn't need to.</p>

</div>
<div class="ph-beat-demo">
{{ <hero_repl /> }}
</div>
</section>

<section class="ph-beat" aria-labelledby="phel-in-php-out">
<div class="ph-beat-text">

## Phel in, PHP out { #phel-in-php-out }

<p>The compiler emits plain PHP. The <code>-&gt;&gt;</code> macro expands away. PHP functions are called directly.</p>

<p class="ph-aside">Real output of <code>phel compile</code> for the <code>defn</code>. Run it on any snippet to see the PHP.</p>

</div>
<div class="ph-beat-demo ph-compile">
<figure class="ph-pane ph-frame">
<figcaption class="ph-pane-bar"><span>You write Phel</span></figcaption>

```phel
(defn slugify [title]
  (->> title
       php/trim
       php/strtolower
       (php/str_replace " " "-")))

(slugify "  Hello Phel World ")
; => "hello-phel-world"
```

</figure>
<figure class="ph-pane ph-frame">
<figcaption class="ph-pane-bar"><span>Phel emits PHP</span><span class="ph-pane-meta">metadata trimmed</span></figcaption>

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

</figure>
</div>
</section>

<section class="ph-beat" aria-labelledby="threading-pipelines">
<div class="ph-beat-text">

## Threading pipelines { #threading-pipelines }

- Reads top to bottom, no nested calls
- Each step is one pure function
- Immutable data, predictable output
- Same `->>` macro PHP devs miss from RxJS or pipe operators

</div>
<div class="ph-beat-demo">
<figure class="ph-pane ph-frame">
<figcaption class="ph-pane-bar"><span>Functional</span></figcaption>

```phel
(->> (range 1 11)
     (filter odd?)
     (map #(* % %))
     (reduce +))
; => 165
```

</figure>
</div>
</section>

<section class="ph-beat" aria-labelledby="direct-php-interop">
<div class="ph-beat-text">

## Direct PHP interop { #direct-php-interop }

- `php/` prefix calls any built-in function
- `.method` calls PHP methods on objects
- `new` constructs PHP classes
- Composer packages work without wrappers

</div>
<div class="ph-beat-demo">
<figure class="ph-pane ph-frame">
<figcaption class="ph-pane-bar"><span>PHP interop</span></figcaption>

```phel
(php/strlen "hello, phel")
; => 11

(php/array_sum
  (to-array [1 2 3 4]))
; => 10

(.format (new \DateTime) "Y")
; => "2026"
```

</figure>
</div>
</section>

<section class="ph-beat" aria-labelledby="code-as-data">
<div class="ph-beat-text">

## Code as data { #code-as-data }

- `defmacro` extends the language at compile time
- Backtick + `~`, `~@` build syntax trees
- Zero runtime cost, expands before compilation
- Impossible in a library, only in a Lisp

</div>
<div class="ph-beat-demo">
<figure class="ph-pane ph-frame">
<figcaption class="ph-pane-bar"><span>Macros</span></figcaption>

```phel
(defmacro unless [pred & body]
  `(if (not ~pred) (do ~@body)))

(def x 5)
(unless (zero? x)
  (println "x is non-zero")
  (/ 10 x))
```

</figure>
</div>
</section>

<section class="ph-beat" aria-labelledby="tests-are-functions">
<div class="ph-beat-text">

## Tests are functions { #tests-are-functions }

- `deftest` + `is`, no extra framework
- Plain Phel, REPL-friendly
- Run with `vendor/bin/phel test`
- Same namespace and dependency model as production code

</div>
<div class="ph-beat-demo">
<figure class="ph-pane ph-frame">
<figcaption class="ph-pane-bar"><span>Tests</span></figcaption>

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

</figure>
</div>
</section>

<section class="ph-beat" aria-labelledby="doom-written-in-phel">
<div class="ph-beat-text">

## DOOM, written in Phel { #doom-written-in-phel }

<p>A Lisp that compiles to PHP runs a full game. <a href="https://chemaclass.github.io/phel-doom/">Play it in your browser</a>.</p>

</div>
<div class="ph-beat-demo">
<figure class="ph-pane ph-frame ph-video">
<a class="homepage-demo-video" href="https://www.youtube.com/watch?v=Gvz95m4wAkQ" data-youtube-id="Gvz95m4wAkQ" aria-label="Play video: How I built DOOM in a Lisp that compiles to PHP">
<img src="/images/phel-doom.jpg" width="960" height="540" loading="lazy" alt="DOOM running in the browser, written in Phel">
<span class="ph-play" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
</a>
</figure>
</div>
</section>

</div>

<section class="ph-faq" aria-labelledby="common-questions">

## Common questions { #common-questions }

<div class="faq">
  <details class="faq-item" id="do-i-need-to-know-lisp">
    <summary class="faq-q">Do I need to know Lisp?</summary>
    <div class="faq-a">No. There is one rule: the first element inside parentheses is the function, the rest are arguments, as in <code>(php/str_contains haystack needle)</code>. There is no operator precedence to learn. The same regular syntax makes code into data, which is what lets <a href="/documentation/language/macros/">macros</a> add new forms to the language. <a href="/documentation/phel-in-5-minutes/">Phel in 5 Minutes</a> teaches you to read it, and the <a href="/practice/">practice exercises</a> build the habit.</div>
  </details>
  <details class="faq-item" id="why-not-modern-php">
    <summary class="faq-q">Why not modern PHP?</summary>
    <div class="faq-a">Modern PHP is a good language, and Phel runs on it. Phel adds what is not on PHP's roadmap: immutable data structures by default, a real macro system that works on code as data, REPL-driven development, and a functional-first standard library. PHP allows functional code; Phel is designed for it.</div>
  </details>
  <details class="faq-item" id="is-phel-production-ready">
    <summary class="faq-q">Is Phel production-ready?</summary>
    <div class="faq-a">Phel is at 1.x. Code that compiles on <code>1.0.0</code> compiles on every later <code>1.x</code>, and breaking changes wait for a new major release. The <a href="/documentation/reference/stability/">stability policy</a> spells out what <code>1.x</code> freezes, and the <a href="/documentation/guides/deployment/">deployment guide</a> covers FPM and worker runtimes. The community is small but active; <a href="https://github.com/phel-lang/awesome-phel">awesome-phel</a> lists libraries, tools, and projects.</div>
  </details>
  <details class="faq-item" id="can-i-call-php-libraries">
    <summary class="faq-q">Can I call PHP libraries from Phel?</summary>
    <div class="faq-a">Yes. Phel compiles to PHP, so any Composer package, function, or class is directly callable. Functions take the <code>php/</code> prefix, <code>(php/strlen "hello")</code>, and classes work with <code>new</code> and <code>.method</code>: <code>(.format (new DateTimeImmutable "2024-01-15") "Y-m-d")</code>. See <a href="/documentation/language/php-interop/">PHP Interop</a>.</div>
  </details>
  <details class="faq-item" id="is-there-a-build-step">
    <summary class="faq-q">Is there a build step?</summary>
    <div class="faq-a">Not in development: <code>phel run</code>, <code>phel test</code>, and the REPL compile on the fly. For production, <code>phel build</code> compiles everything to plain PHP ahead of time. See the <a href="/documentation/guides/deployment/">deployment guide</a>.</div>
  </details>
  <details class="faq-item" id="how-fast-is-phel">
    <summary class="faq-q">How fast is Phel?</summary>
    <div class="faq-a">Phel compiles to plain PHP, so most code runs at PHP speed. The cost is in the persistent data structures: an update builds a new structure that shares most of the old one (O(log32 n)), which is slower than writing to a PHP array. For web, CLI, and data work you will not notice it. In a hot loop over millions of elements, use native PHP arrays through <a href="/documentation/language/php-interop/">interop</a>. See <a href="/documentation/guides/performance/">Performance</a>.</div>
  </details>
  <details class="faq-item" id="how-do-i-debug-phel">
    <summary class="faq-q">How do I debug Phel, and which editors work?</summary>
    <div class="faq-a">Phel has <code>dbg</code>, <code>tap&gt;</code>, and <code>pprint</code>. Phel values are PHP objects, so <code>var_dump</code>, Symfony <code>dump()</code>, and <a href="/documentation/tooling/xdebug-setup/">Xdebug</a> breakpoints in PhpStorm or VS Code work too. VS Code, PhpStorm, Emacs, and Vim have syntax highlighting and REPL support. See <a href="/documentation/guides/debugging/">Debugging</a> and <a href="/documentation/tooling/editor-support/">Editor Support</a>.</div>
  </details>
  <details class="faq-item" id="how-is-phel-different-from-clojure">
    <summary class="faq-q">How is Phel different from Clojure?</summary>
    <div class="faq-a">Phel borrows ideas from Lisp and Clojure (immutable data, macros, threading) but targets the PHP runtime, not the JVM. State lives in atoms; there are no agents, refs, or STM. Phel maps to PHP's execution model. See <a href="/documentation/guides/coming-from-clojure/">Coming from Clojure</a> and the <a href="/blog/functional-programming-in-php">design rationale</a>.</div>
  </details>
  <details class="faq-item" id="what-php-version-do-i-need">
    <summary class="faq-q">What PHP version do I need?</summary>
    <div class="faq-a">The current release targets PHP 8.5 or later. Earlier Phel versions support older PHP releases if you need them.</div>
  </details>
  <details class="faq-item" id="where-do-i-get-help">
    <summary class="faq-q">Where do I get help?</summary>
    <div class="faq-a">Open a thread on <a href="https://github.com/phel-lang/phel-lang/discussions">GitHub Discussions</a>, file an issue on <a href="https://github.com/phel-lang/phel-lang/issues">GitHub</a>, or read the <a href="/documentation/">full documentation</a>.</div>
  </details>
</div>


</section>

<section class="ph-close ph-frame" aria-labelledby="final-cta-title">
  <h2 id="final-cta-title" class="ph-close-title">Write your first Phel function today</h2>
  <p class="ph-close-lede">Install it, open a REPL, write a function. Then drill the basics in Practice.</p>
  <div class="ph-actions">
    <a href="/documentation/getting-started/" class="ph-sx ph-sx--primary"><span class="ph-paren" aria-hidden="true">(</span>Get started<span class="ph-paren" aria-hidden="true">)</span></a>
    <a href="/practice/" class="ph-sx"><span class="ph-paren" aria-hidden="true">(</span>Practice exercises<span class="ph-paren" aria-hidden="true">)</span></a>
    <a href="/repl/" class="ph-sx"><span class="ph-paren" aria-hidden="true">(</span>Try it in your browser<span class="ph-paren" aria-hidden="true">)</span></a>
  </div>
</section>
