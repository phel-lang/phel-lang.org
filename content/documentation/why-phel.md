+++
title = "Why Phel?"
weight = 5
+++

You already know PHP. It pays the bills, it runs half the web, and PHP 8.x is genuinely good. So why would you bother learning a Lisp that compiles to the same runtime?

This page gives honest answers. No marketing fluff, no hand-waving. If Phel isn't right for you, that's fine too.

---

## "Why not just use modern PHP?"

Modern PHP is great. Union types, match expressions, fibers, enums -- the language has improved dramatically. But some things are not on PHP's roadmap:

- **Immutable data structures by default.** In Phel, data never changes in place. You transform it into new values. This eliminates entire categories of bugs (stale state, unexpected mutations, order-dependent side effects).
- **A real macro system.** Phel macros operate on the code itself as data, letting you extend the language with new syntax. PHP attributes and code generation are not the same thing.
- **Homoiconicity.** Phel code is Phel data. This means programs can inspect, generate, and transform other programs trivially.
- **REPL-driven development.** Evaluate expressions interactively, test ideas instantly, build programs incrementally. See the [REPL guide](/documentation/repl).
- **Functional-first design.** Phel is built around pure functions, pipelines, and data transformation. PHP can do functional programming, but its standard library, conventions, and tooling all push you toward OOP.

None of this makes PHP bad. It means Phel and PHP optimize for different things, even though they share a runtime.

## "Isn't adding a compilation step a hassle?"

Phel compiles to PHP transparently. You run `vendor/bin/phel run` or use the REPL. There is no separate build pipeline to configure, no watcher to set up, no output directory to manage.

This is no different from TypeScript compiling to JavaScript, or Sass compiling to CSS. Your deployment is still just PHP files running on your existing infrastructure.

See [CLI Commands](/documentation/cli-commands) for the full list of available commands.

## "Can I use existing PHP libraries?"

Yes, 100%. Phel has full PHP interop through the `php/` prefix:

```phel
# Call any PHP function
(php/strlen "hello")                  # => 5
(php/array_map |(* $ 2) (php/array 1 2 3))

# Create objects
(php/new \DateTimeImmutable "2024-01-15")

# Call methods
(php/-> date (format "Y-m-d"))

# Access static methods and constants
(php/:: \DateTimeImmutable ATOM)
```

Composer packages work. You can call any PHP function, instantiate any class, use traits, and access constants. See [PHP Interop](/documentation/php-interop) for the full reference.

## "What about performance?"

Phel compiles to standard PHP, so runtime performance is comparable to handwritten PHP. The persistent (immutable) data structures carry some overhead compared to plain PHP arrays because every "modification" creates a new structure. For most applications -- web requests, CLI tools, data processing -- this overhead is negligible.

The trade-off is explicit: you give up a bit of micro-performance in exchange for correctness guarantees and a better developer experience. If you are writing a tight inner loop that processes millions of array mutations per second, plain PHP arrays are the right tool. For everything else, immutable data structures make your code easier to reason about and harder to break.

## "Can I use Phel in an existing PHP project?"

Yes. Phel is a Composer package. Add it to any existing PHP project:

```bash
composer require phel-lang/phel-lang
```

You can call Phel functions from PHP and PHP functions from Phel. This means you can adopt Phel gradually -- write one module in Phel, keep everything else in PHP, and expand as it makes sense. See the [PHP Interop](/documentation/php-interop) section on calling Phel from PHP for details.

## "What about debugging?"

Phel compiles to PHP, so PHP's debugging ecosystem works:

- **Phel's built-in helpers** -- `dbg`, `trace`, and friends for quick inspection during development.
- **PHP native tools** -- `var_dump`, `print_r`, and Symfony's `dump()` all work since Phel values are PHP objects under the hood.
- **XDebug** -- Full step-through debugging with breakpoints, call stacks, and variable inspection. Works in PhpStorm and VS Code.

See the [Debug guide](/documentation/debug) for setup instructions and examples.

## "Is Phel production-ready?"

Honest answer: Phel is a growing project. The core language is stable and well-tested. It is a great fit for:

- Side projects and personal tools
- CLI applications
- Internal tools and scripts
- Learning functional programming on a runtime you already know
- Prototyping ideas with REPL-driven development

The community is small but active. If you need battle-tested, enterprise-grade stability with long-term support guarantees, Phel is not there yet. If you want a productive, expressive language that runs anywhere PHP does and you are comfortable being an early adopter, Phel delivers.

Check out [In the Wild](/documentation/in-the-wild) to see what people are building.

## "Do I need to know Lisp?"

No. Phel's syntax is minimal -- it is just parentheses wrapping function calls. If you can read this PHP:

```php
array_map($fn, $arr);
str_contains($haystack, $needle);
```

You can read this Phel:

```phel
(map fn arr)
(php/str_contains haystack needle)
```

The rule is simple: the first element inside the parentheses is the function, everything after it is an argument. That is the entire syntax. No operator precedence rules, no special cases, no ambiguity.

The [Practice exercises](/practice/basic) will get you comfortable in an hour. If you already know PHP, [Phel for PHP Developers](/documentation/phel-for-php-developers) maps familiar patterns to their Phel equivalents.

## "What about IDE support?"

Editor support exists for the major editors:

- **VS Code** -- Syntax highlighting, snippets, and inline evaluation.
- **PhpStorm** -- Plugin with syntax highlighting, structural editing, and REPL actions.
- **Emacs** -- Editing helpers and REPL integration.
- **Vim** -- Syntax highlighting, filetype detection, and indentation.

See [Editor Support](/documentation/editor-support) for installation links and details.

## "Why Lisp syntax specifically?"

Three reasons:

1. **Homoiconicity.** Because Phel code is Phel data (lists, vectors, maps), the macro system can manipulate code with the same functions you use to manipulate data. This is not possible with PHP's syntax.

2. **The macro system.** Macros let you extend the language itself. You can create new control structures, DSLs, and abstractions that look and feel like built-in language features. See [Macros](/documentation/macros) for examples.

3. **Regularity.** There is one syntactic pattern: `(function arg1 arg2 ...)`. No operator precedence, no special forms for different constructs, no syntax to memorize beyond parentheses. Once you internalize the pattern, you can read any Phel code.

The trade-off is that Lisp syntax looks unfamiliar at first. That feeling goes away faster than you expect -- most developers report being comfortable within a few days of actual use.
