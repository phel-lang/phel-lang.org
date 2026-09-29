+++
title = "CLI Commands"
weight = 2
description = "Every built-in phel command with its flags and an example: init, run, test, build, eval, format, lint, repl, nrepl, lsp and more."
aliases = ["/documentation/cli-commands", "/documentation/tooling/cli-commands/"]
+++

This page lists every `phel` command with its main flags and a working example. Run `vendor/bin/phel list` for the list and `vendor/bin/phel help <command>` for every flag of one command.

## Overview

| Command | Alias | What it does |
|---|---|---|
| [`init`](#initialize-a-project) | | Scaffold a new project |
| [`run`](#run-a-script) | `r` | Run a file or namespace |
| [`test`](#test) | `t` | Run tests |
| [`eval`](#evaluate-an-expression) | `e` | Evaluate an expression and print the result |
| [`repl`](#repl) | | Interactive prompt |
| [`build`](#build-the-project) | `b` | Compile the project to PHP |
| [`export`](#export-definitions) | | Generate PHP classes for exported functions |
| [`format`](#format) | `fmt` | Format `.phel` files |
| [`lint`](#lint) | | Static analysis |
| [`balance`](#balance) | | Find and fix unbalanced brackets |
| [`compile`](#compile-to-php) | | Print the PHP a snippet compiles to |
| [`doc`](#look-up-docs) | | Show docs for core functions |
| [`explain`](#explain-an-error-code) | | Explain an error code |
| [`watch`](#watch) | | Reload namespaces on file change |
| [`nrepl`](#nrepl) | | nREPL server for editors |
| [`lsp`](#lsp) | | Language server for editors |
| [`profile`](#profile) | | Per-function timings |
| [`bench`](#bench) | | Run benchmarks |
| [`mutate`](#mutate) | | Mutation testing |
| [`doctor`](#check-your-environment) | | Check PHP, extensions and config |
| [`config`](#inspect-configuration) | | Show the effective config |
| [`cache:clear`](#caches) | | Clear compiled and namespace caches |
| [`cache:warm`](#caches) | | Warm module caches for production |
| [`ns`](#tooling-commands) | `loaded-ns` | List or inspect loaded namespaces |
| [`analyze`, `index`, `api-daemon`](#tooling-commands) | | JSON output for tools |
| [`agent-install`](#agent-install) | | Install AI agent skill files |
| [`completion`](#shell-completion) | | Print a shell completion script |

Flags that work on every command:

| Flag | Effect |
|---|---|
| `--warn-deprecations` | Print deprecation notices to stderr. Same as `PHEL_WARN_DEPRECATIONS=1` |
| `-q`, `--quiet` | Show errors only |
| `-v`, `-vv`, `-vvv` | More output |
| `--no-ansi` | No colors (or set `NO_COLOR`) |

`--stack-trace` on `run`, `eval`, `repl` and `test` shows every stack frame, including the internal ones that are collapsed by default.

## Initialize a project

```bash
vendor/bin/phel init my-app                          # Flat layout: src/, tests/
vendor/bin/phel init my-app --nested                 # src/phel/, tests/phel/
vendor/bin/phel init my-app --minimal                # single main.phel at the root
vendor/bin/phel init my-api --template=http-json-api # start from a bundled example
```

Writes `phel-config.php`, a main namespace, a test, and a `.gitignore` in the current directory. The project name defaults to `app`.

| Flag | Effect |
|---|---|
| `--nested` | Nested layout |
| `-m`, `--minimal` | Root layout |
| `-t`, `--template[=NAME]` | Scaffold from a bundled template. Without a value, lists them |
| `--list-templates` | List templates (`http-json-api`, `todo-app`, `cli-wordcount`) |
| `--dry-run` | Show what would be created |
| `--force` | Overwrite existing files |
| `--no-gitignore`, `--no-tests` | Skip those files |

A template's namespaces, `composer.json` and entry points are renamed to your project name.

## Run a script

```bash
vendor/bin/phel run                          # auto-detects main.phel or core.phel
vendor/bin/phel run src/cli.phel --name Alice
```

Arguments after the path reach your code as `*argv*`, here `["--name" "Alice"]`. The path can also be a namespace.

| Flag | Effect |
|---|---|
| `-t`, `--with-time` | Print time and memory used after the run |
| `--clear-opcache` | Clear OPcache before running |
| `--debug[=FILTER]` | Line-by-line trace to `./phel-debug.log`. Optional file filter, e.g. `--debug="core"` |
| `--stack-trace` | Show every stack frame |

See [Getting Started](/documentation/getting-started/).

## Test

```bash
vendor/bin/phel test                                  # everything in the test dirs
vendor/bin/phel test tests/app/math-test.phel
vendor/bin/phel test --filter=greet --parallel=auto
```

| Flag | Effect |
|---|---|
| `-f`, `--filter=REGEX` | Match test names. Repeatable |
| `--include=TAG`, `--exclude=TAG` | Run or skip tagged tests. Repeatable. `--exclude` wins |
| `--ns=GLOB` | Match namespaces. `*` is one segment, `**` any. Repeatable |
| `--list` | List the selected tests without running them |
| `--fail-fast` | Stop at the first failure |
| `--reporter=NAME` | `default`, `testdox`, `dot`, `tap`, `junit-xml`, `github`. Repeatable. `github` is added on GitHub Actions |
| `--testdox` | Same as `--reporter=testdox` |
| `-o`, `--output=PATH` | Write the `junit-xml` report to a file |
| `--parallel=N` | Run namespaces in workers: a number, `auto` (CPU count, max 8), or `max` |
| `--watch` | Re-run on every `.phel` or `phel-config.php` change |
| `--last-failed` | Re-run only the tests that failed last time |
| `--changed[=REF]` | Run only tests affected by changed files (uncommitted, or `git diff REF`) |
| `--repeat=N` | Run each test N times, to find flaky tests |
| `--random-order`, `--seed=INT` | Shuffle test order, reproducibly with a seed |
| `--fail-on-focus` | Exit non-zero when a `^:focus` test narrowed the run. Always on when `CI` is set |
| `--slowest=N` | Print the N slowest tests |
| `--coverage[=FORMAT]` | Line coverage via pcov or xdebug: `text`, `clover`, `html`, `per-test` |
| `--coverage-output=PATH` | Write the coverage report to a file (a dir for `html`) |

See [Testing](/documentation/guides/testing/) for how to write tests and use these flags.

## Evaluate an expression

```bash
vendor/bin/phel eval '(+ 1 2 3)'
# 6

echo '(map inc [1 2 3])' | vendor/bin/phel eval -
# (2 3 4)
```

For multi-line code, pass a quoted heredoc on stdin. Nothing inside it needs escaping:

```bash
vendor/bin/phel eval - <<'PHEL'
(ns app)
(println (+ 40 2))
PHEL
```

## REPL

```bash
vendor/bin/phel repl
```

Interactive prompt with history, completion, and `*1`, `*2`, `*3`, `*e`. Leave with `(exit)`, `(quit)` or Ctrl+D. See [REPL](/documentation/tooling/repl/).

## Build the project

```bash
vendor/bin/phel build                  # incremental, uses the cache
vendor/bin/phel build --no-cache -O 2  # clean, fully optimized
```

Compiles every namespace to PHP in the build dir (`out/` by default). When `withMainPhelNamespace` is set, it also writes an entry file (`out/index.php`) that you run with plain PHP, so production requests skip compiling. See [Deployment](/documentation/guides/deployment/).

| Flag | Effect |
|---|---|
| `--cache`, `--no-cache` | Use or skip the build cache |
| `--source-map`, `--no-source-map` | Emit source maps |
| `-O`, `--optimization-level=N` | Override the configured level. `2` inlines `^:pure` calls and rewrites self tail calls. See [Performance](/documentation/guides/performance/#optimization-levels) |
| `--report` | Print namespace count, compiled size per namespace, total size and build time |
| `--timing` | Print compile time per phase (lex, parse, read, analyze, emit). Pair with `--no-cache` |

Set the entry namespace and output paths in [Configuration](/documentation/reference/configuration/#build).

## Export definitions

```bash
vendor/bin/phel export
```

Writes one PHP class per namespace, with one method per function marked `{:export true}`, so PHP code can call Phel. Set the dirs and namespace prefix in [Configuration](/documentation/reference/configuration/#export). See [PHP Interop](/documentation/language/php-interop/#calling-phel-from-php).

## Format

```bash
vendor/bin/phel format                              # the configured format dirs
vendor/bin/phel format src/main.phel
vendor/bin/phel format --dry-run                    # exit non-zero if a file would change
vendor/bin/phel format --exclude='src/generated/*'  # repeatable
```

Rewrites files in place. Indents definition and body forms cljfmt-style and collapses repeated blank lines. `--exclude` is combined with the `format-exclude` config key.

## Lint

```bash
vendor/bin/phel lint                     # the configured source dirs
vendor/bin/phel lint src --format=json   # human (default), json, github
```

Reports issues without changing files. Exits 1 on errors, including a file it cannot parse. `--no-cache` skips the lint cache.

| Default severity | Rules |
|---|---|
| Error | `unresolved-symbol`, `arity-mismatch`, `invalid-destructuring`, `duplicate-key`, `duplicate-def` |
| Warning | `unused-binding`, `unused-require`, `unused-import`, `shadowed-binding`, `redundant-do`, `discouraged-var`, `comment-style` |

Change severities (`:error`, `:warning`, `:info`, `:hint`, `:off`) or exclude files in `phel-lint.phel` at the project root, or pass `--config=PATH`:

<!-- phel-test: skip -->
```phel
{:rules {:phel/unused-binding :off
         :phel/arity-mismatch :error}
 :exclude {:phel/unused-binding ["src/local.phel" "app.experimental.*"]}}
```

## Balance

```bash
vendor/bin/phel balance src/broken.phel
# Unbalanced (1):
#   src/broken.phel:2:0: unclosed '(', needs ')'
# Run again with --fix to append the missing delimiters.
```

Brackets inside strings, comments, regexes and char literals are ignored. `--fix` only appends missing closers. A surplus or mismatched closer is reported and left alone.

## Compile to PHP

```bash
vendor/bin/phel compile '(php/strlen "hello")'
# strlen("hello");

vendor/bin/phel compile src/main.phel
echo '(map inc [1 2 3])' | vendor/bin/phel compile -
```

Prints the PHP without running it. A form that folds to a constant emits no PHP; the folded value goes to stderr.

## Look up docs

```bash
vendor/bin/phel doc map                  # search: also matches map?, mapcat, ...
vendor/bin/phel doc --format=json > docs.json
```

With no search term it lists every function. `--ns=NS` loads more namespaces. A search with no match prints `No function matches "..."` and still exits 0.

## Explain an error code

```bash
vendor/bin/phel explain PHEL001
# Undefined symbol [PHEL001]
#
# The analyzer reached a symbol that is bound nowhere: ...

vendor/bin/phel explain 1   # same code, short form
vendor/bin/phel explain     # list every code
```

Prints the meaning, the smallest program that raises it, and the fix. The same text is on the [Error Reference](/documentation/reference/errors/).

## Watch

```bash
vendor/bin/phel watch                 # the configured source dirs
vendor/bin/phel watch src -b polling
```

Re-evaluates changed namespaces in dependency order. `-b`, `--backend` is `auto` (default), `inotify`, `fswatch` or `polling`. `--poll=MS` (default 500) and `--debounce=MS` (default 100) tune it. From Phel code, use `phel.watch`:

<!-- phel-test: skip -->
```phel
(ns my-app
  (:require phel.watch :refer [watch!]))

(watch! ["src/"])
```

## nREPL

```bash
vendor/bin/phel nrepl                # 127.0.0.1:7888
vendor/bin/phel nrepl --port=0       # random free port
```

Bencode-over-TCP server for Calva, CIDER, Conjure and Cursive. `-p`, `--port` and `--host` set the address. The bound port is written to `.nrepl-port` and removed when the server stops. See [Editor support](/documentation/tooling/editor-support/#nrepl-and-editor-integration).

## LSP

```bash
vendor/bin/phel lsp
```

Language server (LSP 3.17) over stdio. Your editor starts it. See [Editor support](/documentation/tooling/editor-support/#language-server-lsp).

## Profile

```bash
vendor/bin/phel profile src/main.phel
vendor/bin/phel profile src/main.phel --sort=total --format=json -o profile.json
```

Runs a script and reports per-function call counts and timings, plus compile-phase costs.

| Flag | Effect |
|---|---|
| `--top=N` | Rows in the table (default 20) |
| `-s`, `--sort=KEY` | `self` (default), `total`, `calls`, `avg` |
| `-f`, `--format=FORMAT` | `table` (default), `json`, `both` |
| `-o`, `--output=PATH` | Write the JSON report to a file |
| `--no-compile-phases` | Skip the compile-phase report |

## Bench

```bash
vendor/bin/phel bench                                     # every defbench under the test dirs
vendor/bin/phel bench --store=.phel/bench-baseline.json   # save a baseline
vendor/bin/phel bench --ref=.phel/bench-baseline.json --tolerance=10
vendor/bin/phel bench --ab=main --pairs=5 --filter=step   # compare with a git ref
```

Runs functions defined with `phel.bench/defbench`. With `--tolerance`, it exits non-zero when a benchmark is slower than the baseline by more than that percentage. `--ab` runs the ref in a temporary worktree and your working tree in alternating pairs. Other flags: `-f`, `--filter`, `--revs`, `--iterations`, `--warmup`.

## Mutate

```bash
vendor/bin/phel mutate                        # project sources against project tests
vendor/bin/phel mutate src/app/calc.phel --only=arith,compare
vendor/bin/phel mutate --min-msi=80 --parallel=auto
```

Mutates every `defn` and reports the mutants your tests do not catch. The unmutated suite must pass first.

| Flag | Effect |
|---|---|
| `--tests=PATH` | Tests to run. Repeatable. Default: test dirs |
| `--only=IDS` | Comma-separated mutator ids |
| `--min-msi=N`, `--min-covered-msi=N` | Exit 1 below this score (percent) |
| `--reporter=FORMAT`, `-o PATH` | `text` or `json`, optionally to a file |
| `--parallel=N` | Workers: a number or `auto` |
| `--changed[=REF]` | Mutate only changed files |
| `--timeout-factor=N` | A mutant slower than N times the baseline counts as killed (default 3) |

## Check your environment

```bash
vendor/bin/phel doctor
# Checking requirements:
#  - json extension: OK
#  - mbstring extension: OK
#  - readline extension: OK
# ...
```

Checks the PHP version and extensions, module health, your config, and OPcache, and prints a fix for anything missing. Run it first when Phel fails before your code loads.

## Inspect configuration

```bash
vendor/bin/phel config
# Sources:
#  - project root: /path/to/project
#  - phel-config.php: found (/path/to/project/phel-config.php)
#  - phel-config-local.php: not present
#  - PHEL_DIR env: (unset)
#
# Effective config:
# { "src-dirs": ["src"], "test-dirs": ["tests"], ... }

vendor/bin/phel config --format=json
```

Shows the merged config and where each part came from, then validation warnings. Use it when `phel-config.php`, `phel-config-local.php` or `PHEL_DIR` does not take effect. See [Configuration](/documentation/reference/configuration/).

## Caches

```bash
vendor/bin/phel cache:clear
vendor/bin/phel cache:warm --clear --attributes
```

`cache:clear` removes the compiled-code, namespace and temp caches and empties the OPcache file cache. Run it after every upgrade. `cache:warm` pre-resolves module classes for production; `-c`, `--clear` starts fresh and `-a`, `--attributes` also caches `#[ServiceMap]` attributes.

Runtime state (cache, REPL history, error log) lives under `.phel/`. Move it with `withPhelDir('...')` or `PHEL_DIR`.

## Tooling commands

For editors and scripts. You rarely run these by hand.

```bash
vendor/bin/phel analyze src/main.phel                 # JSON diagnostics for one file
vendor/bin/phel index src tests --output=index.json   # project symbol index
vendor/bin/phel api-daemon                            # JSON-RPC over stdio
vendor/bin/phel ns                                    # list loaded namespaces
vendor/bin/phel ns phel.core --simple                 # inspect one, names only
```

The Gacela framework also adds `debug:container`, `debug:dependencies`, `debug:modules`, `list:modules`, `profile:report` and `validate:config`. They inspect Phel's internal modules.

## Agent install

```bash
vendor/bin/phel agent-install --auto     # agents detected in this project
vendor/bin/phel agent-install claude     # one platform
vendor/bin/phel agent-install --all      # every platform
vendor/bin/phel agent-install --check    # exit 1 if installed docs are stale
```

Writes a skill file for Claude, Cursor, Codex, Gemini, Copilot or Aider, plus a shared `.agents/` docs tree. Re-running updates only what changed upstream and keeps files you edited. An existing skill file is backed up to `.pre-phel.bak`.

| Flag | Effect |
|---|---|
| `--no-docs` | Skip the `.agents/` docs tree |
| `--with-examples` | Also copy example projects into `.agents/examples/` |
| `--dry-run` | Show what would be written |
| `--force` | Overwrite without the skill backup, and overwrite edited docs (backing them up) |
| `--uninstall` | Remove skill files and restore `.pre-phel.bak` |

See [AI agents](/documentation/tooling/ai-agents/).

## Shell completion

```bash
vendor/bin/phel completion zsh > completion.sh && source completion.sh
eval "$(vendor/bin/phel completion zsh)"   # or in ~/.zshrc
```

Supports `bash`, `zsh` and `fish`. Without an argument it reads `$SHELL`.
