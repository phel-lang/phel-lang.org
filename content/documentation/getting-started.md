+++
title = "Getting Started"
weight = 1
+++

Phel is a Lisp that compiles to PHP. You get persistent data structures, immutability by default, and macros. All running on the PHP runtime you already have.

This page gets you from zero to a live REPL in under a minute.

## Requirements

- **PHP 8.4+**, check with `php -v`
- **[Composer](https://getcomposer.org/)**, check with `composer --version`

That is all you need. No extra runtime, no JVM.

> **No PHP installed?** Skip to the one-line Docker REPL:
>
> ```bash
> docker run --rm -it php:8.4-cli sh -c "curl -sL https://phel-lang.org/phar -o /tmp/phel.phar && php /tmp/phel.phar repl"
> ```
>
> See [Installation → Docker](/documentation/installation#docker-no-php-required) for a `phel` alias and Composer-based workflows.

## 60-Second Quick Start

```bash
composer create-project --stability dev phel-lang/cli-skeleton example-app
cd example-app
composer repl
```

You should see:

```
Welcome to the Phel Repl.
Type "exit" or press Ctrl-D to quit.
>>>
```

Try a few expressions:

```phel
>>> (+ 1 2 3)
6
>>> (def xs [1 2 3])
>>> (conj xs 4)
[1 2 3 4]
>>> xs
[1 2 3]                    ;; original vector is unchanged
>>> (map inc xs)
(2 3 4)
>>> (php/date "Y-m-d")      ;; call any PHP function
"2026-04-21"
```

Exit with `Ctrl+D` or `exit`. Run the entry script:

```bash
composer dev
```

Done. You have a working Phel project.

## Which Background Do You Come From?

<details class="dev-note dev-note--clojure">
<summary>
  <span class="dev-note__label">Clojure</span>
  <span class="dev-note__title">What transfers, what differs</span>
  <span class="dev-note__chevron">›</span>
</summary>
<div class="dev-note__content">

**Most of your intuition transfers unchanged.** `def`, `defn`, `let`, `fn`, `if`, `when`, `cond`, `case`, `loop`/`recur`, `->`, `->>`, `as->`, destructuring, `conj`, `assoc`, `map`, `filter`, `reduce`, transducers, protocols: all work as you expect.

**Key differences at a glance:**

- Runtime is PHP, not the JVM. `println`, files, HTTP all go through PHP.
- Namespaces use dashes in source but map to PHP classes under the hood (`my-app\core` ↔ `MyApp\Core`).
- Interop: `(php/date "Y-m-d")`, `(php/new DateTime)`, `(php/-> obj (method arg))`.
- No agents/refs. Use PHP features for concurrency, or Phel's fiber-based `async` (amphp).
- `nil` is the only falsy value other than `false`. Strings, `0`, `[]` are all truthy.
- Comments: `;` inline, `;;` standalone. `#_` reader discard and `(comment ...)` both work.

**Start here:** [Coming from Clojure](/documentation/guides/coming-from-clojure) for the full diff guide.

</div>
</details>

<details class="dev-note dev-note--php">
<summary>
  <span class="dev-note__label">PHP</span>
  <span class="dev-note__title">What changes, what stays</span>
  <span class="dev-note__chevron">›</span>
</summary>
<div class="dev-note__content">

**Your PHP ecosystem stays.** Phel compiles to PHP, ships via Composer, runs with your PHP binary, and can call any PHP function or class directly.

**What is different:**

- Immutable by default. Instead of `$x = $x + 1`, you bind a new value: `(let [x (+ x 1)] ...)`.
- Prefix notation: `add(1, 2)` becomes `(+ 1 2)`. The function is always the first element.
- Persistent vectors/maps/sets instead of PHP arrays (structural sharing, O(log32 n) updates).
- Everything is an expression. No statements, no `return` keyword.
- Interop is one-liner: `(php/date "Y-m-d")`, `(php/new DateTime "2024-01-01")`, `(php/-> obj (method arg))`.
- REPL-first workflow. You write code by evaluating forms, not by running scripts repeatedly.

**Start here:** [Phel for PHP Developers](/documentation/guides/phel-for-php-developers). Maps every common PHP pattern to Phel.

</div>
</details>

## Project Layout

The skeleton gives you this:

```
example-app/
├── composer.json       # PHP dependencies + phel scripts
├── phel-config.php     # project config (source/test dirs, build)
├── src/
│   ├── main.phel       # entry namespace
│   └── modules/        # your code, organized by namespace
└── tests/
    └── modules/
```

All commands are available as `vendor/bin/phel <cmd>` (for example `vendor/bin/phel repl`). The skeleton also wires `composer repl`, `composer dev`, `composer test`, and `composer build` as shortcuts.

## Initialize Without the Skeleton

Prefer a minimal setup? Add Phel to any Composer project:

```bash
mkdir my-app && cd my-app
composer require phel-lang/phel-lang
vendor/bin/phel init my-app
vendor/bin/phel repl
```

`phel init` creates `phel-config.php`, a `src/` namespace, and a matching test file.

## Verify Your Setup

```bash
vendor/bin/phel doctor
```

This checks required PHP extensions (`json`, `mbstring`, `readline`), cache directory permissions, and source layout. Run it any time the tooling misbehaves.

## Where to Go Next

**Build intuition**
- [Practice exercises](/practice/basic): graded challenges from "Hello, world" to real programs.
- [Basic Types](/documentation/language/basic-types): the full set of literals.
- [Cheat Sheet](/documentation/reference/cheat-sheet): one-page core functions.

**Tooling**
- [REPL guide](/documentation/tooling/repl): history, reloading, helpers.
- [Editor Support](/documentation/tooling/editor-support): nREPL, LSP, syntax highlighting.
- [CLI Commands](/documentation/tooling/cli-commands): every command explained.

**Going deeper**
- [Rosetta Stone: PHP → Phel](/documentation/guides/rosetta-stone): side-by-side patterns.
- [Cookbook](/documentation/guides/cookbook): real-world snippets.
- [PHP Interop](/documentation/php-interop): calling PHP code from Phel.
- [Macros](/documentation/language/macros): the superpower.

Need a different install path? See [Installation](/documentation/installation).
