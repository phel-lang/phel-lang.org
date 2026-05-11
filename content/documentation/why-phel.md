+++
title = "Why Phel?"
weight = 3
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
(php/new \DateTimeImmutable "2024-01-15")     ; create object
(php/-> date (format "Y-m-d"))                ; call method
(php/:: \DateTimeImmutable ATOM)              ; static / constant
```

Composer packages work. Any class, trait, function, constant. See [PHP Interop](/documentation/php-interop).

## Isn't a compilation step a hassle?

No. `vendor/bin/phel run` or the REPL compiles transparently. No build pipeline, watcher, or output dir. Same idea as TypeScript or Sass. Deploys as plain PHP.

## What about performance?

Compiles to standard PHP. Runtime comparable to handwritten PHP. Persistent data structures add some overhead vs plain arrays since "modification" creates a new structure. Negligible for web, CLI, data processing.

Trade-off: micro-performance for correctness and DX. For tight inner loops over millions of mutations, plain PHP arrays win. Otherwise immutability wins.

## What about debugging?

Full PHP debugging ecosystem works:

- **Phel helpers**: `tap>`, `add-tap`, `pprint`.
- **PHP native**: `var_dump`, `print_r`, Symfony `dump()`. Phel values are PHP objects.
- **XDebug**: step-through, breakpoints, variable inspection. PhpStorm and VS Code.

See [Debug helpers](/documentation/tooling/phel-helpers/) and [XDebug setup](/documentation/tooling/xdebug-setup/).

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
