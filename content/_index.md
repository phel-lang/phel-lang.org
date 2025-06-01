+++
title = "Phel: A Functional Lisp Dialect for PHP Developers"
+++

**Phel** is a functional programming language that compiles down to PHP. It's a modern Lisp dialect inspired by [Clojure](https://clojure.org/) and [Janet](https://janet-lang.org/), tailored to bring functional elegance and expressive code to the world of PHP development.

<p align="center">
    <img src="/images/logo_phel.svg" width="350" alt="Phel language logo"/>
</p>

## Join the Phel Developer Community

Got questions? Want to chat about macros, tail recursion, or why parentheses are awesome?  
Swing by the [Phel Gitter channel](https://gitter.im/phel-lang/community)—we're friendly, nerdy, and always happy to talk code.

## Key Features of Phel

Why code in Phel? Here's what makes it click:

- ✅ Runs on the rock-solid PHP ecosystem
- 🧠 Helpful and human-readable error messages
- 📚 Built-in persistent data structures: Lists, Vectors, Maps, Sets
- 🧩 Macro system for advanced metaprogramming
- 🔁 Tail-recursive function support
- ✨ Minimal, readable Lisp syntax
- 💬 Interactive REPL for tinkering and prototyping

## Why Choose Phel for Functional Programming in PHP?

Phel started as an [experiment in writing functional PHP](/blog/functional-programming-in-php) and quickly turned into its own thing.

It exists because we wanted:

- A Lisp-inspired functional language
- That runs on affordable PHP hosting
- That's expressive, debug-friendly, and easy to pick up

If you've ever wished PHP was a bit more... functional, Phel is for you.

## See Phel in Action — Sample Code

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

If you know Lisp or Clojure, you'll feel right at home. If you don't—this is a great place to start.

## Try Phel Instantly with Docker

No setup? No problem. You can run Phel's REPL right away:

```bash
docker run -it --rm phellang/repl
```

![Try Phel animation](/try-phel.gif "Try Phel Animation")

## Get Started with Phel in Minutes

All you need is [PHP >=8.2](https://www.php.net/) and [Composer](https://getcomposer.org/). 

> Follow our [Getting Started Guide](/documentation/getting-started) to build and run your first Phel program today.

## Development Status & How to Contribute

Phel is approaching its 1.0 release, but we're still actively refining the language —and yes, breaking changes may happen.

We're building this in the open. That means:
- Found a bug? File an issue.
- Got a cool idea? Open a pull request.
- Want to shape the language's future? Let's talk.

Your feedback, ideas, and code help Phel grow into something great.
