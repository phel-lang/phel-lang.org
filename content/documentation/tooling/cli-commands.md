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
#       --timing                      Print per-phase compile timing (lex/parse/read/analyze/emit)
```

Compiles Phel to PHP and writes it to the configured main path (entry point `out/index.php`). Run the resulting PHP directly. Production requests then skip the compile step.

```bash
# Build with optimizations on (inlining + self-recursive tail-call rewriting)
vendor/bin/phel build -O 2

# Print a build summary to spot bloat and verify CI builds
vendor/bin/phel build --report

# Measure where compile time goes (pair with --no-cache for a full run)
vendor/bin/phel build --no-cache --timing
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
vendor/bin/phel format --exclude='src/generated/*'  # skip a glob, repeatable
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

Run a file or namespace. Omit the path and Phel looks for `main.phel` or `core.phel`.

```bash
vendor/bin/phel run
# Usage:
#   run [options] [--] [<path> [<argv>...]]
#
# Arguments:
#   path                  The file path or namespace to execute
#   argv                  Optional arguments
#
# Options:
#   -t, --with-time       With time awareness
#       --clear-opcache   Clears OPCache before running
#       --debug[=FILTER]  Line-by-line trace to ./phel-debug.log (optional file filter, e.g. --debug="core")
#       --stack-trace     Show every stack frame, including the internal ones collapsed by default
```

Arguments after the path reach your code as `*argv*`:

```bash
vendor/bin/phel run src/cli.phel --name Alice
# *argv* is ["--name" "Alice"]
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
#   -f, --filter=REGEX      Regex or substring matched against test names. Repeatable.
#       --fail-fast         Stop on first failure or error.
#       --include=TAG       Only run tests tagged TAG. Repeatable.
#       --exclude=TAG       Skip tests tagged TAG. Repeatable.
#       --ns=GLOB           Only run namespaces matching GLOB. Repeatable.
#       --list              List the selected tests without running them.
#       --reporter=NAME     Reporter: default|testdox|dot|tap|junit-xml|github. Repeatable.
#   -o, --output=PATH       Write the junit-xml report to a file.
#       --testdox           Shortcut for --reporter=testdox.
#       --repeat=N          Run each test N times (default 1).
#       --seed=INT          Seed used for randomized order.
#       --random-order      Run tests in random order (uses --seed if given).
#       --parallel=N        Run namespaces in subprocess workers: int, "auto" (capped at 8), or "max".
#       --watch             Re-run selected tests on every .phel / phel-config.php change.
#       --last-failed       Re-run only tests that failed on the previous run.
#       --changed[=REF]     Run only tests affected by changed files (uncommitted, or `git diff REF`).
#       --fail-on-focus     Exit non-zero when a ^:focus test narrowed the run (always on under CI).
#       --slowest=N         Print the N slowest tests after the summary (0 disables).
#       --stack-trace       Show every stack frame, including the collapsed internal ones.
#       --coverage[=FORMAT] Line coverage via pcov or xdebug: text (default), clover, html, per-test.
#       --coverage-output=PATH  Write the coverage report to a file (the directory for html).
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

Add `--stack-trace` to see every frame when an expression throws.

```bash
vendor/bin/phel eval '(+ 1 2 3)'
# => 6

echo '(map inc [1 2 3])' | vendor/bin/phel eval -
# => (2 3 4)
```

For reliable multi-line evaluation, pass a quoted heredoc to stdin:

```bash
vendor/bin/phel eval - <<'PHEL'
(ns app)
(println (+ 40 2))
PHEL
```

Prefer this pattern because:

- **No quoting issues:** Everything between `<<'PHEL'` and `PHEL` is treated as literal input.
- **Consistent pattern:** One approach works for all evaluations, from simple to complex.
- **Multi-line friendly:** Code keeps its natural, readable formatting.
- **Easy to extend:** Add more forms without changing the command syntax.

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

Static analysis. Errors: unresolved-symbol, arity-mismatch, invalid-destructuring, duplicate-key, duplicate-def. Warnings: unused-binding, unused-require, unused-import, shadowed-binding, redundant-do, discouraged-var, comment-style.

```bash
vendor/bin/phel lint
# Usage:
#   lint [options] [--] [<paths>...]
#
# Options:
#   -f, --format=FORMAT   human (default), json, github
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
vendor/bin/phel index src --output=symbols.json
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
#   --check            Compare the installed docs version with the bundled one; exit 1 if they differ
```

## Profile

Per-function timings and compile-phase costs:

```bash
vendor/bin/phel profile path/to/file.phel
# Options:
#       --top=N              Show the top N functions (default 20)
#   -f, --format=FORMAT      table (default), json, both
#   -o, --output=PATH        Write the JSON report to PATH
#   -s, --sort=KEY           Sort by self (default), total, calls, avg
#       --no-compile-phases  Skip the compile-time phase report
```


## Look up docs

Print the signature, docstring and example of any function. The argument is a search term, so `map` also matches `map?`, `mapcat` and friends.

```bash
vendor/bin/phel doc map
# Usage:
#   doc [options] [--] [<search>]
#
# Options:
#       --ns[=NS]         Namespaces to load. Repeatable.
#   -f, --format=FORMAT   table (default), json
```

With no argument it lists every documented function. `--format=json` emits the same data for tooling:

```bash
vendor/bin/phel doc --format=json > docs.json
```

A search with no match prints `No function matches "..."` and still exits 0. Read the output, not the exit code.

## Explain an error code

Every compiler error carries a stable code such as `[PHEL001]`. `explain` prints what it means and how to fix it. Omit the code to list them all.

```bash
vendor/bin/phel explain PHEL001
# Undefined symbol [PHEL001]
#
# The analyzer reached a symbol that is bound nowhere: not in the current namespace, not in a required namespace, and not in a local binding.
# ...

vendor/bin/phel explain   # list every code
```

The same explanations live on the [Error Reference](/documentation/reference/errors/) page.

## Check your environment

`doctor` checks the PHP extensions Phel needs, the source and test directories, OPcache, and the cache size. Run it first when something fails before your code even loads.

```bash
vendor/bin/phel doctor
# Checking requirements:
#  - json extension: OK
#  - mbstring extension: OK
#  - readline extension: OK
# ...
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

# Machine-readable: the effective config as JSON
vendor/bin/phel config --format=json
```

See [Configuration](/documentation/configuration/) for every setter.

## Clear caches

Clear namespace and compiled-code caches:

```bash
vendor/bin/phel cache:clear
```

Removes everything in the cache dir. Useful for stale caches or after upgrades.

Runtime state (cache, REPL history, error log) lives under `.phel/` by default. Override via `withPhelDir('...')` in `phel-config.php` or the `PHEL_DIR` env var.

## Other commands

Smaller tools. Run `vendor/bin/phel help <command>` for every flag.

| Command | What it does | Key flags |
|---------|--------------|-----------|
| `balance [paths]` | Report unbalanced `()`, `[]`, `{}` in `.phel` files. | `--fix` appends the missing closers |
| `bench [paths]` | Run the benchmarks under `tests/` (or the given paths). | `-f/--filter`, `--revs`, `--iterations`, `--warmup`, `--store`, `--ref`, `--tolerance`, `--ab=REF`, `--pairs` |
| `mutate [paths]` | Mutation testing: mutate every `defn`, report the mutants your tests miss. | `--tests`, `--only`, `--min-msi`, `--min-covered-msi`, `--reporter=text\|json`, `-o`, `--parallel`, `--changed[=REF]` |
| `ns [namespace]` | List loaded namespaces, or show one namespace and its dependencies. | `-s/--simple` |
| `completion [shell]` | Print the shell completion script (bash, zsh, fish). | `--debug` |
| `cache:warm` | Pre-resolve module classes and warm the cache for production. | `-c/--clear`, `-a/--attributes` |

```bash
vendor/bin/phel balance src/broken.phel
# Unbalanced (1):
#   src/broken.phel:2:0: unclosed '(', needs ')'
# Run again with --fix to append the missing delimiters.
```

## Next steps

- [REPL](/documentation/tooling/repl/) - the interactive loop behind `phel repl`
- [Editor support](/documentation/tooling/editor-support/) - connect your editor to `phel nrepl`
- [Configuration](/documentation/configuration/) - tune paths, cache, and export in `phel-config.php`
