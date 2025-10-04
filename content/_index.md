+++
title = "Phel: A Functional Lisp Dialect for PHP Developers"
+++

**Phel** is a functional programming language that compiles down to PHP. It's a modern Lisp dialect inspired by [Clojure](https://clojure.org/) and [Janet](https://janet-lang.org/), tailored to bring functional elegance and expressive code to the world of PHP development.

<img src="/images/logo_phel.svg" width="450" alt="Phel language logo"/>

<div class="homepage-cta">
  <a href="#try-phel-instantly-with-docker" class="homepage-cta-button homepage-cta-primary">
    <svg class="homepage-cta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
    Try Phel with Docker
  </a>
  <a href="/documentation/getting-started" class="homepage-cta-button homepage-cta-secondary">
    <svg class="homepage-cta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
    Read Documentation
  </a>
</div>

<div class="homepage-code-section">

## See Phel in Action

```phel
# Define a namespace
(ns my\example)

# Create a variable
(def my-name "world")

# Define a function
(defn print-name [your-name]
  (print "hello" your-name))

# Call the function
(print-name my-name)
```

</div>

## Key Features of Phel

Built for modern PHP development with functional programming principles

{% features() %}

{% feature_card(title="Built on PHP Ecosystem", description="Runs on the PHP ecosystem with access to all existing libraries", icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9 3l-7 7 7 7M15 3l7 7-7 7"/></svg>') %}
<li>• Seamless PHP interoperability</li>
<li>• Access to Composer packages</li>
<li>• Familiar deployment patterns</li>
{% end %}

{% feature_card(title="Immutable Data Structures", description="Built-in persistent data structures like Lists, Vectors, Maps, and Sets", icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>') %}
<li>• Structural sharing for performance</li>
<li>• Thread-safe by default</li>
<li>• Minimal, readable Lisp syntax</li>
{% end %}

{% feature_card(title="Macro System", description="Advanced metaprogramming capabilities for code generation", icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>') %}
<li>• Powerful macro system</li>
<li>• Code as data philosophy</li>
<li>• Extensible language constructs</li>
{% end %}

{% feature_card(title="Interactive REPL", description="Interactive REPLs for iterating and prototyping", icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>') %}
<li>• Live code evaluation</li>
<li>• Rapid prototyping</li>
<li>• Interactive development</li>
{% end %}

{% feature_card(title="Lisp-inspired Syntax", description="Clean, expressive, and easy to pick up syntax", icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>') %}
<li>• Minimal, readable syntax</li>
<li>• Homoiconicity benefits</li>
<li>• Expressive and concise</li>
{% end %}

{% feature_card(title="Modern Tooling", description="Comprehensive development tools and ecosystem", icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg>') %}
<li>• Package management</li>
<li>• Testing frameworks</li>
<li>• Development server</li>
{% end %}

{% end %}

<div class="homepage-code-section homepage-why-section">

## Why Choose Phel for Functional Programming in PHP?

Phel started as an [experiment in writing functional PHP](/blog/functional-programming-in-php) and quickly turned into its own thing.

<div class="why-cards">
  <div class="why-card">
    <div class="why-card-icon">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
      </svg>
    </div>
    <div class="why-card-text">A Lisp-inspired functional language</div>
  </div>
  <div class="why-card">
    <div class="why-card-icon">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
      </svg>
    </div>
    <div class="why-card-text">That runs on affordable PHP hosting</div>
  </div>
  <div class="why-card">
    <div class="why-card-icon">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
        <polyline points="14 2 14 8 20 8"></polyline>
        <line x1="16" y1="13" x2="8" y2="13"></line>
        <line x1="16" y1="17" x2="8" y2="17"></line>
        <polyline points="10 9 9 9 8 9"></polyline>
      </svg>
    </div>
    <div class="why-card-text">That's expressive, debug-friendly, and easy to pick up</div>
  </div>
</div>

If you've ever wished PHP was a bit more... functional, Phel is for you.

</div>

## Try Phel Instantly with Docker

No setup? No problem. You can run Phel's REPL right away:

```bash
docker run -it --rm phellang/repl
```

![Try Phel animation](/try-phel.gif "Try Phel Animation")
