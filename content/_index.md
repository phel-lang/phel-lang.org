+++
title = "Phel: A Functional Lisp Dialect for PHP Developers"
+++

<img src="/images/logo_phel.svg" width="380" alt="Phel language logo"/>

**Phel** is a functional programming language that compiles to PHP — a modern Lisp dialect inspired by [Clojure](https://clojure.org/) and [Janet](https://janet-lang.org/), bringing functional elegance and expressive code to PHP development.

<div class="homepage-cta">
  <a href="#try-phel-instantly-with-docker" class="btn btn-primary homepage-cta-button homepage-cta-primary">
    <svg class="homepage-cta-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
    Try Phel with Docker
  </a>
  <a href="/documentation/getting-started" class="btn btn-secondary homepage-cta-button homepage-cta-secondary">
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

<div class="features-grid">
  {% feature_card(title="Built on PHP Ecosystem", description="Runs on the PHP ecosystem with access to all existing libraries", icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9 3l-7 7 7 7M15 3l7 7-7 7"/></svg>') %}
  • Seamless PHP interoperability
  • Access to Composer packages
  • Familiar deployment patterns
  {% end %}

  {% feature_card(title="Immutable Data Structures", description="Built-in persistent data structures like Lists, Vectors, Maps, and Sets", icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>') %}
  • Structural sharing for performance
  • Thread-safe by default
  • Minimal, readable Lisp syntax
  {% end %}

  {% feature_card(title="Macro System", description="Advanced metaprogramming capabilities for code generation", icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>') %}
  • Powerful macro system
  • Code as data philosophy
  • Extensible language constructs
  {% end %}

  {% feature_card(title="Interactive REPL", description="Interactive REPLs for iterating and prototyping", icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>') %}
  • Live code evaluation
  • Rapid prototyping
  • Interactive development
  {% end %}

  {% feature_card(title="Lisp-inspired Syntax", description="Clean, expressive, and easy to pick up syntax", icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path><polyline points="10 2 10 10 12.5 7.5 15 10 15 2"></polyline></svg>') %}
  • Minimal, readable syntax
  • Homoiconicity benefits
  • Expressive and concise
  {% end %}

  {% feature_card(title="Modern Tooling", description="Comprehensive development tools and ecosystem", icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg>') %}
  • Plugin support
  • Package management
  • Testing frameworks
  {% end %}
</div>

<div class="homepage-code-section homepage-why-section">

## Why Choose Phel for Functional Programming in PHP?

Phel started as an [experiment in writing functional PHP](/blog/functional-programming-in-php) and quickly turned into its own thing.

<div class="why-cards">
  <div class="why-card">
    <div class="why-card-icon">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"></path>
        <path d="M9 18h6"></path>
        <path d="M10 22h4"></path>
      </svg>
    </div>
    <div class="why-card-text">A Lisp-inspired functional language</div>
  </div>
  <div class="why-card">
    <div class="why-card-icon">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"></path>
        <path d="M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"></path>
        <path d="M12 2v2"></path>
        <path d="M12 22v-2"></path>
        <path d="m17 20.66-1-1.73"></path>
        <path d="M11 10.27 7 3.34"></path>
        <path d="m20.66 17-1.73-1"></path>
        <path d="m3.34 7 1.73 1"></path>
        <path d="M14 12h8"></path>
        <path d="M2 12h2"></path>
        <path d="m20.66 7-1.73 1"></path>
        <path d="m3.34 17 1.73-1"></path>
        <path d="m17 3.34-1 1.73"></path>
        <path d="m11 13.73-4 6.93"></path>
      </svg>
    </div>
    <div class="why-card-text">That runs on affordable PHP hosting</div>
  </div>
  <div class="why-card">
    <div class="why-card-icon">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 16l4-4-4-4"></path>
        <path d="M6 8l-4 4 4 4"></path>
        <path d="M14.5 4l-5 16"></path>
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
# To update to the latest version of Phel:
# docker pull phellang/repl
```

![Try Phel animation](/try-phel.gif "Try Phel Animation")
