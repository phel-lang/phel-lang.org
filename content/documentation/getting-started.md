+++
title = "Getting Started"
weight = 2
description = "Go from zero to a live Phel REPL in under a minute, then tour your first project."
+++

Phel is a Lisp that compiles to PHP. Persistent data structures, immutability by default, macros. Runs on your existing PHP runtime.

Zero to live REPL in under a minute.

## Requirements

- **PHP 8.4+** (`php -v`)
- **[Composer](https://getcomposer.org/)** (`composer --version`)

No extra runtime. No JVM.

> **No PHP installed?** Run a REPL in a single Docker command: see [Installation → Docker](/documentation/installation/#docker-no-php-required).

## 60-second quick start

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
[1 2 3]                    ; original vector is unchanged
>>> (map inc xs)
@[2 3 4]
>>> (php/date "Y-m-d")      ; call any PHP function
"2026-04-21"
```

Exit with `Ctrl+D` or `exit`. Run the entry script:

```bash
composer dev
```

Done. Working Phel project.

## Which background do you come from?

<details class="dev-note dev-note--clojure">
<summary>
  <span class="dev-note__label">Clojure</span>
  <span class="dev-note__title">What transfers, what differs</span>
  <span class="dev-note__chevron">›</span>
</summary>
<div class="dev-note__content">

**Most intuition transfers.** `def`, `defn`, `let`, `fn`, `if`, `when`, `cond`, `case`, `loop`/`recur`, `->`, `->>`, `as->`, destructuring, `conj`, `assoc`, `map`, `filter`, `reduce`, transducers, protocols: work as expected.

**Key differences:**

- Runtime is PHP, not JVM. `println`, files, HTTP go through PHP.
- Namespaces use dashes and dot separators in source, map to PHP classes (`my-app.core` ↔ `MyApp\Core`).
- Interop: `(php/date "Y-m-d")`, `(php/new DateTime)`, `(php/-> obj (method arg))`.
- No agents/refs. Use PHP for concurrency, or Phel's fiber-based `async` (amphp).
- Only `nil` and `false` are falsy. Strings, `0`, `[]` truthy.
- Comments: `;` inline, `;;` standalone. `#_` reader discard and `(comment ...)` work.

**Start:** [Coming from Clojure](/documentation/guides/coming-from-clojure).

</div>
</details>

<details class="dev-note dev-note--php">
<summary>
  <span class="dev-note__label">PHP</span>
  <span class="dev-note__title">What changes, what stays</span>
  <span class="dev-note__chevron">›</span>
</summary>
<div class="dev-note__content">

**PHP ecosystem stays.** Compiles to PHP, ships via Composer, runs with your PHP binary, calls any PHP function/class directly.

**Differences:**

- Immutable by default. Bind new values: `(let [x (+ x 1)] ...)` instead of `$x = $x + 1`.
- Prefix notation: `add(1, 2)` becomes `(+ 1 2)`. Function is always first.
- Persistent vectors/maps/sets, not PHP arrays (structural sharing, O(log32 n) updates).
- Everything an expression. No statements, no `return`.
- One-liner interop: `(php/date "Y-m-d")`, `(php/new DateTime "2024-01-01")`, `(php/-> obj (method arg))`.
- REPL-first. Evaluate forms, don't re-run scripts.

**Start:** [Rosetta Stone: PHP → Phel](/documentation/guides/rosetta-stone). Maps PHP patterns to Phel.

</div>
</details>

## Project layout

Skeleton gives you:

```
example-app/
├── composer.json       ; PHP deps + phel scripts
├── phel-config.php     ; project config (src/test dirs, build)
├── src/
│   ├── main.phel       ; entry namespace
│   └── modules/        ; your code by namespace
└── tests/
    └── modules/
```

All commands as `vendor/bin/phel <cmd>` (e.g. `vendor/bin/phel repl`). Skeleton wires `composer repl`, `composer dev`, `composer test`, `composer build` as shortcuts.

## Verify setup

Tooling misbehaving? Run `vendor/bin/phel doctor`: it reports missing extensions, cache permissions, and layout problems. Full breakdown in [Installation → Verify install](/documentation/installation/#verify-install).

Adding Phel to an existing project instead of the skeleton? See [Installation → Add to an existing project](/documentation/installation/#add-to-an-existing-project).

## Your first 30 minutes

In order:

1. **[Practice: Basics](/practice/basic)** (~10 min), graded REPL exercises.
2. **[Basic Types](/documentation/language/basic-types)** (~5 min), every literal.
3. **[Cheat Sheet](/documentation/reference/cheat-sheet)** (keep open), core functions, filterable.
4. **[Cookbook](/documentation/guides/cookbook)** (~15 min), copy-paste recipes.

Branch by need:

- **Editor flow:** [REPL](/documentation/tooling/repl), [Editor Support](/documentation/tooling/editor-support).
- **From PHP:** [Rosetta Stone](/documentation/guides/rosetta-stone), [PHP Interop](/documentation/php-interop).
- **Power features:** [Macros](/documentation/language/macros), [Interfaces](/documentation/language/interfaces).
- **AI agent pairing:** [Agentic Coding](/documentation/reference/agentic-coding) for Claude Code, Codex, Cursor.

Different install path? See [Installation](/documentation/installation).
