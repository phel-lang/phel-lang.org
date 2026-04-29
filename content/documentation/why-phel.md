+++
title = "Why Phel?"
weight = 3
+++

You already know PHP. So why learn a Lisp that compiles to the same runtime?

Honest answers below. No marketing. If Phel isn't right for you, that's fine.

## Do I need to know Lisp?

No. The whole syntax is one rule: the first element inside parentheses is the function, everything after it is an argument.

```php
// PHP
array_map($fn, $arr);
str_contains($haystack, $needle);
```

```phel
;; Phel
(map fn arr)
(php/str_contains haystack needle)
```

No operator precedence, no special cases, no ambiguity. The [Practice exercises](/practice/basic) get you comfortable in an hour.

## Why Lisp syntax specifically?

1. **Homoiconicity.** Phel code is Phel data (lists, vectors, maps). Macros manipulate code with the same functions you use on data.
2. **Macros.** Extend the language with new syntax that looks built-in. See [Macros](/documentation/language/macros).
3. **Regularity.** One pattern: `(function arg1 arg2 ...)`. Once internalized, you can read any Phel code.

The unfamiliar feeling fades faster than expected, usually within a few days of actual use.

## Why not just modern PHP?

Modern PHP is great. Some things are not on its roadmap:

- **Immutable data structures by default.** Data never changes in place. Eliminates whole classes of bugs (stale state, unexpected mutations, order-dependent side effects).
- **A real macro system.** Operates on code as data. PHP attributes and codegen are not the same.
- **REPL-driven development.** Evaluate expressions interactively, build programs incrementally. See [REPL](/documentation/tooling/repl).
- **Functional-first design.** Pure functions, pipelines, data transformation. PHP can do FP, but its stdlib and conventions push toward OOP.

PHP is not bad. Phel and PHP optimize for different things on the same runtime.

## Can I use existing PHP libraries?

Yes, fully. Phel has full PHP interop through the `php/` prefix:

```phel
(php/strlen "hello")                          ; => 5
(php/new \DateTimeImmutable "2024-01-15")     ; create object
(php/-> date (format "Y-m-d"))                ; call method
(php/:: \DateTimeImmutable ATOM)              ; static / constant
```

Composer packages work. Any class, trait, function, constant. See [PHP Interop](/documentation/php-interop).

## Isn't a compilation step a hassle?

No. `vendor/bin/phel run` or the REPL compiles transparently. No build pipeline, no watcher, no output dir to manage. Same as TypeScript → JavaScript or Sass → CSS. Deployment is plain PHP files on existing infra.

## What about performance?

Phel compiles to standard PHP. Runtime performance is comparable to handwritten PHP. Persistent data structures carry some overhead vs plain arrays since "modification" creates a new structure. Negligible for web requests, CLI tools, data processing.

The trade-off is explicit: trade micro-performance for correctness and DX. For tight inner loops over millions of mutations, plain PHP arrays win. For everything else, immutability wins.

## What about debugging?

PHP's full debugging ecosystem works:

- **Phel helpers**: `tap>`, `add-tap`, `pprint` for inline inspection.
- **PHP native**: `var_dump`, `print_r`, Symfony `dump()` all work since Phel values are PHP objects.
- **XDebug**: full step-through with breakpoints, call stacks, variable inspection. PhpStorm and VS Code.

See [Debug helpers](/documentation/tooling/phel-helpers/) and [XDebug setup](/documentation/tooling/xdebug-setup/).

## What about IDE support?

Editor support exists for the major editors:

- **VS Code**, **PhpStorm**, **Emacs**, **Vim**: syntax highlighting, REPL/nREPL integration, formatting.

See [Editor Support](/documentation/tooling/editor-support).

## Is Phel production-ready?

The core language is stable and well-tested. Great fit for:

- Side projects and personal tools
- CLI applications
- Internal tools and scripts
- Learning functional programming on a runtime you already know
- Prototyping with REPL-driven development

The community is small but active. Need battle-tested enterprise stability with LTS guarantees? Phel is not there yet. Want a productive, expressive language on PHP and OK being an early adopter? Phel delivers.

See [In the Wild](/documentation/reference/in-the-wild) for what people are building.
