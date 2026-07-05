+++
title = "Why Phel?"
weight = 4
description = "Honest answers for PHP developers: why a Lisp on PHP, what it costs, and where it fits."
+++

You know PHP. Why learn a Lisp on the same runtime?

Honest answers below. If Phel doesn't fit, that's fine.

## Do I need to know Lisp?

No. One rule: first element inside parentheses is the function, the rest are arguments.

```php
// PHP
array_map($fn, $arr);
str_contains($haystack, $needle);
```

<!-- phel-test: skip -->
```phel
;; Phel
(map fn arr)
(php/str_contains haystack needle)
```

No operator precedence, no special cases. [Practice exercises](/practice/basic) get you comfortable in an hour.

## Why Lisp syntax specifically?

1. **Homoiconicity.** Code is data (lists, vectors, maps). Macros manipulate code with the same functions you use on data.
2. **Macros.** Extend the language with built-in-looking syntax. See [Macros](/documentation/language/macros).
3. **Regularity.** One pattern: `(function arg1 arg2 ...)`. Read any Phel code once you internalize it.

Unfamiliar feeling fades within days of use.

## Why not just modern PHP?

Modern PHP is great. Some things aren't on its roadmap:

- **Immutable data structures by default.** Eliminates stale state, unexpected mutations, order-dependent side effects.
- **Real macro system.** Operates on code as data. PHP attributes and codegen are not the same.
- **REPL-driven development.** Evaluate expressions interactively, build incrementally. See [REPL](/documentation/tooling/repl).
- **Functional-first design.** Pure functions, pipelines, data transformation. PHP allows FP but pushes OOP.

Phel and PHP optimize for different things on the same runtime.

## Can I use existing PHP libraries?

Yes. Full interop through the `php/` prefix:

```phel
(php/strlen "hello")                          ; => 5
(def date (php/new DateTimeImmutable "2024-01-15")) ; create object
(php/-> date (format "Y-m-d"))                ; call method
(php/:: DateTimeImmutable ATOM)               ; static / constant
```

Composer packages work. Any class, trait, function, constant. See [PHP Interop](/documentation/php-interop).

## Isn't a compilation step a hassle?

No. `vendor/bin/phel run` or the REPL compiles transparently. No build pipeline, watcher, or output dir. Same idea as TypeScript or Sass. Deploys as plain PHP.

## What about performance?

Phel compiles to plain PHP, so the bulk of your code runs at PHP speed. The real overhead is the persistent (immutable) data structures: an "update" allocates a new structure with structural sharing (O(log32 n)) rather than mutating in place, so they are slower than a native PHP array write. For most web, CLI, and data-processing work that cost is negligible.

In a hot inner loop over millions of elements, reach for native PHP arrays through [PHP interop](/documentation/php-interop/) (`php/aset`, `php/aget`, `php/apush`) and stay on the fast path, then hand the result back as a Phel value. Everywhere else, immutability buys correctness and clearer code for a price you will not notice.

## What about debugging?

Full PHP debugging ecosystem works:

- **Phel helpers**: `tap>`, `add-tap`, `pprint`.
- **PHP native**: `var_dump`, `print_r`, Symfony `dump()`. Phel values are PHP objects.
- **XDebug**: step-through, breakpoints, variable inspection. PhpStorm and VS Code.

See [Debug helpers](/documentation/tooling/repl/#debug-helpers) and [XDebug setup](/documentation/tooling/xdebug-setup/).

## What about IDE support?

VS Code, PhpStorm, Emacs, Vim: syntax highlighting, REPL/nREPL, formatting. See [Editor Support](/documentation/tooling/editor-support).

## Is Phel production-ready?

Core is stable and tested. Good fit for:

- Side projects, personal tools
- CLI applications
- Internal tools and scripts
- Learning FP on a runtime you know
- REPL-driven prototyping

Community small but active. Need LTS-grade enterprise stability? Not yet. Want expressive PHP and OK being an early adopter? Phel delivers.

See [awesome-phel](https://github.com/phel-lang/awesome-phel) for libraries, tools, and projects.

## Next steps

- [Getting Started](/documentation/getting-started): zero to a live REPL.
- [PHP Interop](/documentation/php-interop): call any PHP function or class.
- [Cheat Sheet](/documentation/reference/cheat-sheet): core syntax and functions in one page.
