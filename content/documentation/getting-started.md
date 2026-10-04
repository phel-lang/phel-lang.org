+++
title = "Getting Started"
weight = 2
description = "Create a Phel project, try the REPL, run your first file, and learn the project layout."
+++

This page takes you from nothing to a working Phel project: a live REPL, a file you wrote, and a project layout you understand.

## Requirements

- **PHP 8.5+** (`php -v`)
- **[Composer](https://getcomposer.org/)** (`composer --version`)

No PHP on your machine, or you want the PHAR, Docker, or Nix? See [Installation](/documentation/installation/).

## Create a project

```bash
composer create-project --stability dev phel-lang/cli-skeleton example-app
cd example-app
```

The skeleton is a small CLI app with tests and Composer scripts already wired.

## Open the REPL

```bash
composer repl
```

You should see:

```
Welcome to the Phel Repl (v0.54.0)
Type (exit) or press Ctrl-D to exit.
user:1>
```

Try a few expressions:

```phel
user:1> (+ 1 2 3)
6
user:2> (def xs [1 2 3])
#'user/xs
user:3> (conj xs 4)
[1 2 3 4]
user:4> xs
[1 2 3]                         ; original vector is unchanged
user:5> (map inc xs)
(2 3 4)                         ; map returns a lazy sequence
user:6> (php/date "Y-m-d")      ; call any PHP function
"2026-04-21"
```

Exit with `Ctrl+D` or `(exit)`. The [REPL guide](/documentation/tooling/repl/) covers history, introspection, and debug helpers.

## Write your first file

Create `src/hello.phel`:

```phel
(ns cli-skeleton.hello)

(defn greet [name]
  (str "Hello, " name "!"))

(println (greet "Phel"))
```

Run it:

```bash
vendor/bin/phel run src/hello.phel
# Hello, Phel!
```

The `ns` form names the namespace. Every file starts with one. The skeleton's own entry point runs with `composer dev`.

## Project layout

```
example-app/
├── composer.json       ; PHP deps + phel scripts
├── phel-config.php     ; project config (main namespace, build output)
├── src/
│   ├── main.phel       ; entry namespace, run by composer dev
│   ├── commands/       ; CLI commands
│   └── core/           ; pure logic the commands call
└── tests/
    ├── commands/
    └── core/
```

Every command runs as `vendor/bin/phel <cmd>`. The skeleton adds Composer shortcuts:

| Script | Runs |
| --- | --- |
| `composer repl` | `phel repl` |
| `composer dev` | `phel run cli-skeleton.main` |
| `composer test` | `phel test` |
| `composer build` | `phel build`, compiles to plain PHP |
| `composer format` | `phel format` |

See [CLI Commands](/documentation/reference/cli-commands/) for every command and [Configuration](/documentation/reference/configuration/) for `phel-config.php`.

## Verify your setup

```bash
vendor/bin/phel doctor
```

It checks the PHP extensions Phel needs, your source and test directories, OPcache, and the cache size. Run `composer test` to confirm the skeleton's tests pass.

## Next steps

- [Practice: First Steps](/practice/first-steps/): short graded exercises you solve in the REPL.
- [Basic Types](/documentation/language/basic-types/): every literal and how it maps to PHP.
- [Build a Web App](/documentation/guides/build-a-web-app/): a complete guestbook, end to end.
