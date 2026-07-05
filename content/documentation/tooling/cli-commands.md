+++
title = "CLI Commands"
weight = 1
description = "Every built-in phel command: init, build, run, test, repl, eval, compile, lint, watch, nrepl, lsp, and more"
aliases = ["/documentation/cli-commands"]
+++

Every task you run through Phel goes through one CLI. This page lists the built-in commands with a working example for each.

```bash
# Overview of all commands
vendor/bin/phel list
```

## Initialize a new project

Scaffold a new Phel project:

```bash
vendor/bin/phel init
# Usage:
#   init [options] [--] [<project-name>]
#
# Arguments:
#   project-name          The project/namespace name (default: "app")
#
# Options:
#       --nested          Use nested layout (src/phel/, tests/phel/)
#   -m, --minimal         Use root layout (single main.phel at project root)
#       --force           Overwrite existing files
#       --dry-run         Show what would be created without writing anything
#       --no-gitignore    Skip generating .gitignore
#       --no-tests        Skip generating a test file
#   -t, --template[=NAME] Scaffold from a bundled example; omit the value to list
#       --list-templates  List available project templates and exit
```

Defaults to **Flat** layout (`src/`, `tests/`). `--nested` for `src/phel/`. `--minimal` for a single root file.

```bash
# Flat layout (default)
vendor/bin/phel init my-app

# Nested layout
vendor/bin/phel init my-app --nested

# Preview what would be created
vendor/bin/phel init my-app --dry-run
```

Scaffold from a bundled, runnable example instead of a bare skeleton. The template's namespaces, `composer.json`, and entry points are renamed to your project name. Composes with `--dry-run` and `--force`.

```bash
# List the bundled templates
vendor/bin/phel init --list-templates
# http-json-api, todo-app, cli-wordcount

# Scaffold a project from a template
vendor/bin/phel init my-api --template=http-json-api
```

## Build the project

```bash
vendor/bin/phel build
# Usage:
#   build [options]
#
# Options:
#       --cache|--no-cache            Enable cache
#       --source-map|--no-source-map  Enable source maps
#   -O, --optimization-level=LEVEL    Override configured level (0 = off, 2 = inline + tail-call rewrite)
#       --report                      Print a build report (namespaces, sizes, time)
```

Compiles Phel to PHP, writing to the configured main path (entry point `out/index.php`). Run the resulting PHP directly. Skips recompilation, improving runtime.

```bash
# Build with optimizations on (inlining + self-recursive tail-call rewriting)
vendor/bin/phel build -O 2

# Print a build summary to spot bloat and verify CI builds
vendor/bin/phel build --report
```

`-O` overrides the level set via `withOptimizationLevel(...)` in `phel-config.php`. See [Performance](/documentation/performance/) for what each level does. `--report` prints namespace count, per-namespace compiled size, total size, the fresh/cached breakdown, and build time.

[Configuration](/documentation/configuration/) in `phel-config.php`:
```php
<?php
return (new \Phel\Config\PhelConfig())
    ->withMainPhelNamespace('your-ns.index')
    ->withMainPhpPath('out/index.php');
```

## Export definitions

Exports definitions with `{:export true}` metadata as PHP classes. Generates one class per namespace, one method per exported definition. Lets you call Phel functions from PHP.

```bash
vendor/bin/phel export
```

Configure the export dirs, namespace prefix, and target directory in `phel-config.php`; see [Configuration](/documentation/configuration/#full-reference).

## Format phel files

Formats files. Accepts relative or absolute paths.

```bash
vendor/bin/phel format            # formats src and tests by default
vendor/bin/phel format src/foo.phel
vendor/bin/phel format --dry-run  # report files that would change, exit non-zero if any
```

[Configuration](/documentation/configuration/) in `phel-config.php`:
```php
<?php
return (new PhelConfig())
    ->withFormatDirs(['src', 'tests']);
```

Indents definition and body forms (`defstruct`, `defprotocol`, `defmethod`, `reify`, `doseq`, `letfn`, ...) cljfmt-style, and collapses consecutive blank lines to one.

## Read-eval-print loop

Interactive prompt for quick tests and language exploration.

```bash
vendor/bin/phel repl
```

See [REPL](/documentation/tooling/repl).

## Run a script

Run a file or namespace:

```bash
vendor/bin/phel run
# Usage:
#   run [options] [--] <path> [<argv>...]
#
# Arguments:
#   path                  The file path that you want to run.
#   argv                  Optional arguments
#
# Options:
#   -t, --with-time       With time awareness
```

[Configuration](/documentation/configuration/) in `phel-config.php`:
```php
<?php
return (new PhelConfig())
    ->withSrcDirs(['src']);
```

See [Getting Started](/documentation/getting-started/).

## Test your Phel logic

Runs tests. No paths runs everything in `tests/`.

```bash
vendor/bin/phel test
# Usage:
#   test [options] [--] [<paths>...]
#
# Arguments:
#   paths                   The file paths that you want to test.
#
# Options:
#   -f, --filter[=REGEX]    Filter by test name regex. Repeatable.
#       --fail-fast         Stop on first failure or error.
#       --include=TAG       Only run tests tagged TAG. Repeatable.
#       --exclude=TAG       Skip tests tagged TAG. Repeatable.
#       --ns=GLOB           Only run namespaces matching GLOB. Repeatable.
#       --reporter=NAME     Reporter: default|testdox|dot|tap|junit-xml. Repeatable.
#       --output=PATH       Output path (for junit-xml).
#       --testdox           Shortcut for --reporter=testdox.
#       --repeat=N          Run each test N times (default 1).
#       --seed=INT          Seed used for randomized order.
#       --random-order      Run tests in random order (uses --seed if given).
#       --parallel=N        Run namespaces in subprocess workers: int, "auto" (capped at 8), or "max".
#       --watch             Re-run selected tests on every .phel / phel-config.php change.
#       --last-failed       Re-run only tests that failed on the previous run.
#       --slowest=N         Print the N slowest tests after the summary (0 disables).
#       --stack-trace       Print the full PHP stack trace for each errored test.
#       --coverage[=FORMAT] Collect line coverage (text|clover) via pcov or xdebug.
#       --coverage-output=PATH  Write the coverage report to a file (use with --coverage=clover for CI).
```

See [Testing](/documentation/testing/) for what each flag does.

[Configuration](/documentation/configuration/) in `phel-config.php`:
```php
<?php
return (new PhelConfig())
    ->withTestDirs(['tests']);
```

## Evaluate an expression

Evaluate and print. Pass a literal expression, or `-` for stdin.

```bash
vendor/bin/phel eval '(+ 1 2 3)'
# => 6

echo '(map inc [1 2 3])' | vendor/bin/phel eval -
# => @[2 3 4]
```

## Compile to PHP

Emit the PHP that Phel generates for a snippet, file, or stdin, without evaluating it. Handy for understanding the compiler or debugging interop.

```bash
vendor/bin/phel compile '(php/strlen "hello")'
# => strlen("hello");

vendor/bin/phel compile src/main.phel    # compile a file
echo '(map inc [1 2 3])' | vendor/bin/phel compile -

# Usage:
#   compile [options] [--] [<source>]
#
# Arguments:
#   source            Phel expression, path to a .phel file, or "-" for stdin
#
# Options:
#   -t, --target      Compilation target (currently only "php")
```

## Lint

Static analysis. Rules: unresolved-symbol, arity-mismatch, unused-binding, unused-require, unused-import, shadowed-binding, redundant-do, duplicate-key, invalid-destructuring, discouraged-var.

```bash
vendor/bin/phel lint
# Usage:
#   lint [options] [--] [<paths>...]
#
# Options:
#       --format=FORMAT   human (default), json, github
#       --config=PATH     Path to phel-lint.phel
#       --no-cache        Disable linter cache
```

Configure rules in `phel-lint.phel` at the project root.

## Watch

Reloads changed namespaces in dependency order. Backends: inotify, fswatch, polling.

```bash
vendor/bin/phel watch
# Usage:
#   watch [options] [--] [<paths>...]
#
# Arguments:
#   paths                 Files or directories to watch (default: configured src dirs)
#
# Options:
#   -b, --backend=BACKEND Watcher backend: auto, inotify, fswatch, polling (default: auto)
#       --poll=MS         Polling interval in ms, polling backend only (default: 500)
#       --debounce=MS     Debounce window in ms (default: 100)
```

From Phel code, use `phel.watch`:

```phel
(ns my-app
  (:require phel.watch :refer [watch!]))

(watch! ["src/"])
```


## nREPL

Bencode-over-TCP nREPL server for editor inline eval.

```bash
vendor/bin/phel nrepl --port=7888 --host=127.0.0.1
```

See [Editor support](/documentation/tooling/editor-support/#nrepl-and-editor-integration) for supported ops and connecting your editor.

## LSP

LSP v3.17 over stdio.

```bash
vendor/bin/phel lsp
```

See [Editor support](/documentation/tooling/editor-support/#language-server-lsp) for supported features and PHP-interop-aware completion.


## Analyze and index

`phel analyze <file>` emits JSON diagnostics; `phel index <dir>...` builds a symbol table for tooling.

```bash
vendor/bin/phel analyze src/main.phel
vendor/bin/phel index src --out=symbols.json
```

`phel api-daemon` serves the Api facade as JSON-RPC over stdio.

```bash
vendor/bin/phel api-daemon
```


## Agent install

Writes skill/recipe files for AI coding assistants: Claude Code, Cursor, Codex, Gemini, Copilot, Aider. Copies a per-platform skill file plus the shared `.agents/` docs tree. Re-install is idempotent; existing files are backed up to `.pre-phel.bak` unless `--force`.

```bash
vendor/bin/phel agent-install              # pick platform interactively
vendor/bin/phel agent-install claude       # single platform
vendor/bin/phel agent-install --all        # every platform
vendor/bin/phel agent-install --auto       # only platforms detected in project
vendor/bin/phel agent-install --uninstall  # remove skill files, restore .pre-phel.bak
#   --no-docs          Skip the .agents/ docs tree (copied by default)
#   --with-examples    Also copy example projects into .agents/examples/
#   --dry-run          Show what would be written, change nothing
#   --force            Overwrite without .pre-phel.bak backups
```

## Profile

Per-function timings and compile-phase costs:

```bash
vendor/bin/phel profile path/to/file.phel
# Options:
#   --format=FORMAT   text (default), json
#   --output=PATH     Write report to PATH
```


## Inspect configuration

Print the effective configuration and where each part comes from. Useful when a `phel-config.php`, a `phel-config-local.php` override, or the `PHEL_DIR` env var is not taking effect as you expect.

```bash
vendor/bin/phel config
# Sources:
#  - project root: /path/to/project
#  - phel-config.php: not found, using auto-detected defaults
#  - phel-config-local.php: not present
#  - PHEL_DIR env: (unset)
#
# Effective config:
# { "src-dirs": ["src"], "test-dirs": ["tests"], ... }

# Machine-readable: just the effective config as JSON
vendor/bin/phel config --json
```

See [Configuration](/documentation/configuration/) for every setter.

## Clear caches

Clear namespace and compiled-code caches:

```bash
vendor/bin/phel cache:clear
```

Removes everything in the cache dir. Useful for stale caches or after upgrades.

Runtime state (cache, REPL history, error log) lives under `.phel/` by default. Override via `withPhelDir('...')` in `phel-config.php` or the `PHEL_DIR` env var.

## Next steps

- [REPL](/documentation/tooling/repl/) - the interactive loop behind `phel repl`
- [Editor support](/documentation/tooling/editor-support/) - connect your editor to `phel nrepl`
- [Configuration](/documentation/configuration/) - tune paths, cache, and export in `phel-config.php`
