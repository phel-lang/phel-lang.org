+++
title = "Installation"
weight = 3
description = "Install Phel via Composer, PHAR, a one-line script, Docker, or Nix, then verify with phel doctor."
+++

Requires **PHP 8.4+**, except [Quick launch](#quick-launch-no-php-no-docker), which brings its own. Pick the method matching your workflow.

## Which method?

| Goal                               | Use                                               |
|------------------------------------|---------------------------------------------------|
| New project with tests + scripts   | [Composer skeleton](#new-project-from-skeleton)   |
| Add to existing Composer project   | [Composer require](#add-to-an-existing-project)   |
| Run a single file, no setup        | [PHAR](#phar-no-project-setup)                    |
| **No PHP**, no Docker              | [Quick launch](#quick-launch-no-php-no-docker)    |
| **No PHP**, Docker available       | [Docker](#docker-no-php-required)                 |
| Reproducible dev shells            | [Nix](#nix)                                       |
| Fastest path                       | [Getting Started](/documentation/getting-started) |

## Composer (recommended)

### New project from skeleton

Ships with tests, build config, ready-to-use `composer` scripts (`repl`, `dev`, `test`, `build`, `format`).

```bash
composer create-project --stability dev phel-lang/cli-skeleton example-app
cd example-app
composer repl
```

### Add to an existing project

```bash
composer require phel-lang/phel-lang
vendor/bin/phel init my-app    # scaffold phel-config.php + src/
```

All commands then via `vendor/bin/phel <cmd>` (e.g. `vendor/bin/phel repl`).

<details class="dev-note dev-note--php">
<summary>
  <span class="dev-note__label">PHP</span>
  <span class="dev-note__title">Does this replace my PHP app?</span>
  <span class="dev-note__chevron">›</span>
</summary>
<div class="dev-note__content">

No. Phel lives alongside PHP. `require 'vendor/autoload.php'` and call compiled Phel namespaces from PHP, or call PHP from Phel. Drop into any Composer project (Laravel, Symfony, WordPress plugin) and use where Lisp fits better.

</div>
</details>

## PHAR (no project setup)

Run without Composer. Good for quick experiments, CI one-shots, trying the language.

```bash
curl -L https://phel-lang.org/phar -o phel.phar
php phel.phar --version
```

Every command works the same:

```bash
php phel.phar repl
php phel.phar run src/main.phel
php phel.phar test --filter foo
```

Make it globally available:

```bash
chmod +x phel.phar
sudo mv phel.phar /usr/local/bin/phel
phel repl
```

## Quick launch (no PHP, no Docker)

One command, no PHP and no container runtime. Linux and macOS, x86_64 and arm64.

```bash
bash <(curl -sL https://phel-lang.org/get) repl
```

What it does:

- Downloads a [static PHP build](https://github.com/crazywhalecc/static-php-cli) and the Phel PHAR into `${TMPDIR:-/tmp}/phel-quick`
- Checks the PHP tarball against a SHA-256 pinned in the script
- Writes a small `phel` wrapper next to them
- Runs your command

Nothing lands outside that folder, nothing needs root. The first run takes a few seconds, later runs start instantly.

Not on bash or zsh? Download first, then run it:

```bash
curl -sL https://phel-lang.org/get -o /tmp/phel-get
bash /tmp/phel-get repl
```

> Do not use `curl ... | bash`. Bash takes over stdin and the REPL exits on the spot. Use `bash <(curl ...)` or download first.

### Keep `phel` for the shell session

```bash
source <(curl -sL https://phel-lang.org/get)
phel repl
phel run src/main.phel
```

Only `phel` goes on `PATH`. Your system `php`, if you have one, is untouched.

### Options

| Variable       | Default                      | Effect                                |
|----------------|------------------------------|---------------------------------------|
| `PHEL_VERSION` | `latest`                     | Pin a Phel release, e.g. `0.50.0`     |
| `PHP_VERSION`  | `8.4.18`                     | Pin a static PHP build                |
| `PREFIX`       | `${TMPDIR:-/tmp}/phel-quick` | Where everything is downloaded        |
| `PHEL_FORCE`   | `0`                          | `1` re-downloads instead of reusing   |

```bash
PHEL_VERSION=0.50.0 PHP_VERSION=8.5.8 bash <(curl -sL https://phel-lang.org/get) repl
```

> `PHP_VERSION` must name a build static-php-cli has actually published, and they lag upstream PHP. Check the [listing](https://dl.static-php.dev/static-php-cli/common/). Overriding it skips checksum verification, since only the default build has a pinned sum.

### Read it before you run it

The command runs a remote script on your machine. Read it first:

```bash
curl -sL https://phel-lang.org/get | less
```

The source is in the [website repo](https://github.com/phel-lang/phel-lang.org/blob/master/static/get). Everything is fetched over HTTPS, and the PHP tarball is checked against a pinned hash. The Phel PHAR has no published checksum yet, so the script verifies it by running it rather than by hash.

### Differences from a normal install

- **No readline.** The static build ships without it, so `phel doctor` reports `readline extension: FAIL` and the REPL has no history or arrow keys. Install `rlwrap` (`brew install rlwrap`, `apt install rlwrap`) and the wrapper uses it automatically for `repl`.
- **No OPcache.** `phel doctor` says so too. Repeat runs recompile every time.
- **Temporary by design.** The folder goes away on reboot. Re-run the command to get it back.
- **Fixed extension set.** No PECL, no `php.ini` to edit. `json`, `mbstring`, `curl`, `openssl`, `gd`, `gmp`, `bcmath`, `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`, `redis`, `soap`, `sockets` and `zip` are compiled in. If your code needs anything else, use a normal install.
- **No Windows.** static-php-cli publishes no Windows builds. Use WSL or [Docker](#docker-no-php-required).

Everything Phel itself does works the same: `repl`, `run`, `test`, `eval`, `build`, `fmt`.

Remove it when you are done:

```bash
rm -rf "${TMPDIR:-/tmp}/phel-quick"
```

### Community alternative

[clojure.cc](https://clojure.cc/try/#quick-dialect-usage) by [@ingydotnet](https://github.com/ingydotnet) launches Phel the same way, alongside a dozen other Lisp dialects:

```bash
source <(curl -sL clojure.cc/get) phel && phel
```

It also needs `make` and `git`, and it is maintained outside the Phel project.

## Docker (no PHP required)

No PHP installed? With Docker, run Phel in one command.

### Zero-setup REPL

Paste and you're in a live Phel REPL. No files, no install:

```bash
docker run --rm -it php:8.4-cli sh -c \
  "curl -sL https://phel-lang.org/phar -o /tmp/phel.phar && php /tmp/phel.phar repl"
```

Container downloads PHAR fresh each run. Fine for experimenting, wasteful for daily use. See [Persistent `phel` alias](#persistent-phel-alias-backed-by-docker) for a cached setup.

### Run a Phel file from your host

Mount cwd, run any Phel script:

```bash
docker run --rm -it -v "$PWD":/app -w /app php:8.4-cli sh -c \
  "curl -sL https://phel-lang.org/phar -o /tmp/phel.phar && php /tmp/phel.phar run src/main.phel"
```

### Persistent `phel` alias backed by Docker

Download PHAR once, make `phel` feel native:

```bash
curl -L https://phel-lang.org/phar -o phel.phar

# Add to ~/.zshrc, ~/.bashrc, or run in your shell:
alias phel='docker run --rm -it -v "$PWD":/app -w /app php:8.4-cli php /app/phel.phar'

phel repl
phel run src/main.phel
phel test
```

### Composer project with no local PHP

Use official `composer` image (ships PHP + Composer):

```bash
docker run --rm -it -v "$PWD":/app -w /app composer \
  create-project --stability dev phel-lang/cli-skeleton example-app

cd example-app

# Start the REPL
docker run --rm -it -v "$PWD":/app -w /app -p 2345:2345 composer composer repl
```

Alias for daily use:

```bash
alias dcomposer='docker run --rm -it -v "$PWD":/app -w /app composer'
dcomposer composer repl
dcomposer composer test
dcomposer composer dev
```

> `-p 2345:2345` exposes default nREPL port for host editor integration. Omit if not needed.

## Nix

Reproducible dev environments. Phel is in nixpkgs: see [phel on search.nixos.org](https://search.nixos.org/packages?channel=unstable&show=phel) or the [package source](https://github.com/NixOS/nixpkgs/blob/master/pkgs/by-name/ph/phel/package.nix).

No Nix yet? Install via [Determinate Systems installer](https://determinate.systems/nix-installer/) or [official installer](https://nixos.org/download).

### Ad-hoc shell

```bash
nix shell nixpkgs#phel
phel repl
```

> Nixpkgs may lag latest. Check `nix eval nixpkgs#phel.version`. For newest, use Composer or PHAR.

### Project `shell.nix`

Pin PHP + Composer for the team:

```nix
{ pkgs ? import <nixpkgs> { } }:

pkgs.mkShell {
  packages = with pkgs; [
    php84
    php84Packages.composer
  ];
}
```

Then `nix-shell` and use Composer as normal.

## Verify install

Run the doctor:

```bash
vendor/bin/phel doctor    ; Composer
php phel.phar doctor      ; PHAR
phel doctor               ; Nix / global
```

Checks PHP extensions (`json`, `mbstring`, `readline`), writable cache dir, source layout. Tells you exactly what's missing.

<details class="dev-note dev-note--clojure">
<summary>
  <span class="dev-note__label">Clojure</span>
  <span class="dev-note__title">Mental model for the toolchain</span>
  <span class="dev-note__chevron">›</span>
</summary>
<div class="dev-note__content">

Mapping from `lein`/`deps.edn`:

| Clojure                    | Phel                                         |
|----------------------------|----------------------------------------------|
| `deps.edn` / `project.clj` | `composer.json` + `phel-config.php`          |
| `lein new app foo`         | `composer create-project … cli-skeleton foo` |
| `clj` / `lein repl`        | `composer repl` or `phel repl`               |
| `lein test`                | `composer test` or `phel test`               |
| `uberjar`                  | `phel build` (compiles to PHP)               |
| nREPL                      | `phel nrepl` (bencode over TCP)              |

Editor integration: nREPL + LSP. See [Editor Support](/documentation/tooling/editor-support).

</div>
</details>

## Upgrading to 0.50

```bash
composer require phel-lang/phel-lang:^0.50
./vendor/bin/phel cache:clear        # or: rm -rf .phel/cache
```

Always clear the cache after upgrading: compiled PHP from earlier installs references renamed core types and fails to load otherwise. Rebuild downstream projects too.

0.50 is the clean-up release before 1.0: everything that had been printing a deprecation notice is now gone. Run `vendor/bin/phel run --warn-deprecations src/main.phel` on 0.49 first; a clean run means the upgrade is just a version bump.

Breaking changes in 0.50:

- Long-deprecated core aliases are removed: `push` → `conj`, `put` → `assoc`, `unset` → `dissoc`, `put-in` → `assoc-in`, `unset-in` → `dissoc-in`, `values` → `vals`, `function?` → `fn?`, `hash-map?` → `map?`, `id` → `identical?`, `str-contains?` → `phel.string/contains?`, `set-meta!` → `with-meta`.
- Deprecated reader syntax is removed: `#| |#` and bare `#` comments (use `;` / `;;`), `|(...)` short functions (use `#(...)` with `%`), `,` / `,@` unquote (use `~` / `~@`), and `foo$` auto-gensym (use `foo#`).
- **`,` is the one to watch.** The others stop parsing, so the compiler finds them; `,` is now plain whitespace, so `` `(f ,x) `` still parses and quietly quotes `x` instead of unquoting it. Sweep anything that generates Phel, not just `.phel` files: `grep -rnE ",[A-Za-z0-9_(\[{'\`~@:*+-]" --include='*.phel' src/ tests/`.
- Lazy sequences print as `(1 2 3)` rather than `@[1 2 3]`, so `@` again means only the deref reader macro. Vectors still print as `@[1 2 3]`.
- Core functions declare real arities, so a wrong argument count raises an arity error instead of being ignored, and `arity` reports `0` for the multi-arity ones.
- `(max)` and `(min)` with no arguments are rejected at compile time (PHEL002) instead of throwing at runtime.
- An unresolved `(:require ...)` fails at require time rather than when the missing name is first used.
- Requires `gacela-project/gacela` `^2.0` and `symfony/console` `^7.3|^8.0`. Gacela 2 resolves module classes by filename suffix, so a `DependencyProvider` must be renamed to `Provider`.
- CLI: `phel index --out` is now `--output` (`-o`), and `phel config --json` is now `--format=json` (`-f json`).

New in 0.50: `phel.bench` and a `phel bench` command for benchmarks with baseline storage and a tolerance gate; `phel balance` to find (and `--fix`) unbalanced brackets; Clojure-style PHP interop for value members, dynamic calls, enum cases and `set!`; LSP go-to-definition for lexical locals, honouring shadowing and destructuring; and completion plus hover for PHP superglobals. See the [0.50 release notes](/releases/0-50-the-last-zero/).

## Upgrading to 0.49

```bash
composer require phel-lang/phel-lang:^0.49
./vendor/bin/phel cache:clear        # or: rm -rf .phel/cache
```

Always clear the cache after upgrading: compiled PHP from earlier installs references renamed core types and fails to load otherwise. Rebuild downstream projects too.

Behaviour changes in 0.49:

- No breaking changes. Existing code compiles as before.
- `partition` and `partition-all` accept Clojure's extra arities (`[n step coll]`, plus `[n step pad coll]` for `partition`); the `[n coll]` form is unchanged.
- Sorted maps and sorted sets treat `NaN` as equal to itself and ordered after every number, matching Clojure's `compare`. `(count (sorted-set NAN NAN))` is now `1` instead of `2`.
- `pr`/`prn` print char literals like `\A` as one-char strings (`"A"`).
- Multi-arity functions emit fixed-arity `invokeArityN` methods, so build-mode calls with a known arity skip variadic dispatch (roughly 1.5-2x faster per call).
- Optional `PhelConfig::withStripSymbolMeta()` drops symbol metadata from compiled artifacts (-28% size, -40% cold require). With it on, `phel doc` and `(meta ...)` over built defs return nil, and toggling forces a full recompile.
- `PhelConfig::withAppModulePaths()` scopes Gacela module discovery, so `phel list:modules` and `phel cache:warm` no longer fatal on classes that cannot load standalone. Defaults to the previous whole-root walk.

New in 0.49: 20 new core fns, including `every-pred`, `mapv`, `filterv`, `while`, `distinct?`, `bounded-count`, `map-invert`, `random-sample`, the `pr`/`prn`/`pr-str`/`prn-str` family, the completed atom API (`compare-and-set!`, `swap-vals!`, `reset-vals!`), `clojure.set`-style relational helpers (`select`, `project`, `rename`, `index`), and `subseq`/`rsubseq` over sorted collections; a new `phel\trace` namespace (`trace`, `trace-fn`, `deftrace`, `dotrace`) in the spirit of `clojure.tools.trace`; and `(tap> x)` printing out of the box in the REPL. See the [0.49 release notes](/releases/0-49-arity-lane/).

## Upgrading to 0.48

```bash
composer require phel-lang/phel-lang:^0.48
./vendor/bin/phel cache:clear        # or: rm -rf .phel/cache
```

Always clear the cache after upgrading: compiled PHP from earlier installs references renamed core types and fails to load otherwise. Rebuild downstream projects too.

Behaviour changes in 0.48:

- No breaking changes. Existing code compiles as before.
- The compiled-code cache key now hashes only the `.phel` source, so a compiler-only upgrade no longer serves stale PHP; the cache index format bumped and invalidates old entries once on first run.
- `phel` no longer fatals in read-only / unwritable environments: caches degrade quietly and CLI commands report a clear error instead of aborting when a target file can't be written.
- Squaring (`(** x 2)`) and `reduce` over a typed vector now compile to native PHP; startup and emitted code shrink further via constant-slot sharing and leaner location metadata.

New in 0.48: new core fns (`trampoline`, `reductions`, `subvec`, `with-open`, `reduce-kv`, `gcd`, `lcm`, `arity`, `variadic?`, `inspect`, and `dbg`); a stepping debugger via `(break)` that opens a sub-REPL over the captured locals (`(continue)` or EOF resumes, so non-interactive runs never hang); `phel test --coverage=html` for a self-contained line-colored coverage report; and `phel export` stubs that carry native parameter/return types from `:tag` metadata. See the [0.48 release notes](/releases/0-48-step-into/).

Behaviour changes in 0.47:

- No breaking changes. Existing code compiles as before.
- `phel test` now prints structural diffs (`+`/`-`/`~`) for any collection that differs, not just the first few entries, so assertion failures point straight at the mismatch.
- `phel compile` prints folded values to stderr when a form emits no PHP output, making constant folding visible instead of silent.
- New projects scaffold with optimization level 2 enabled in `phel-config.php`. Existing configs are untouched.

New in 0.47: LSP signature help now covers plain Phel calls like `(map f xs)` (arity, parameter names, docstring); nREPL eval responses carry per-session `*1`/`*2`/`*3` value history so Calva and Conjure show the last three results; the REPL's `(doc sym)` renders function examples under an `Example:` heading; runtime errors name the `.phel` location instead of a compiled temp path; and startup is about 30% faster via OPcache re-execution. See the [0.47 release notes](/releases/0-47-clear-signals/).

Behaviour changes in 0.46:

- **Breaking**: the deprecated `PhelConfig` `setX()` setters and `useLayout()`/`useNestedLayout()`/`useFlatLayout()` were removed, along with the `setX()` shims on `PhelBuildConfig`/`PhelExportConfig`. Use the `with*()` methods in `phel-config.php` instead.
- A broken `phel-config.php` now fails with a clear error naming the file and expected structure (exit code 1) instead of an uncaught exception stack trace.
- `phel build` now exits non-zero when compilation aborts, instead of printing errors while exiting `0`. CI relying on the old exit code may start failing as intended.
- The incremental build cache now cascades recompiles to dependent namespaces when a required namespace changes, preventing stale output reuse.

New in 0.46 (native path): config validation in `phel config` and `phel doctor` (relative paths, source/test dirs, optimization levels, types); `phel build --timing` for per-phase compile durations; `phel init` scaffolds configs with `declare(strict_types=1);`; an optional intermediate compile cache via `withEnableIntermediateCache()`; and a more resilient LSP that stays alive during idle periods and lists symbols from unsaved buffer edits. See the [0.46 release notes](/releases/0-46-native-path/).

Behaviour changes in 0.45:

- **Breaking**: the runtime CLI-args var is now `*argv*` (earmuffed), matching `*program*` and Clojure's `*command-line-args*`. The old `argv` name was removed: replace `argv` with `*argv*` in scripts that read command-line arguments.
- CLI flag renames with deprecated aliases kept: `index --output`/`-o` (was `--out`), `config --format=json` (was `--json`). The old flags still work but warn on stderr.
- Overflowing constant int arithmetic (`+`/`-`/`*`) now folds to `BigInt` like the runtime instead of `float`. Float printing is consistent across `str`/`print`/REPL (integer floats keep `.0`).

New in 0.45 (warm boot): the PHAR ships `phel.core` precompiled, cutting cold-start `run`/`test`/`eval` from ~1.2s to ~0.2s; a native-int arithmetic fast path (~1.8-8x per op); shell completion (`bash`/`zsh`/`fish`) plus CLI short aliases (`r` run, `t` test, `b` build, `e` eval); REPL/nREPL autocompletion of special forms and native symbols; and `phel doctor` OPcache reporting. See the [0.45 release notes](/releases/0-45-warm-boot/).

Behaviour changes in 0.44:

- Requires `gacela-project/gacela: ^1.15`. Editing `phel-config.php` takes effect immediately again (the stale merged-config cache is cleared on change).
- `phel test` exit codes are stricter: it no longer exits `0` when nothing ran, bad paths/selectors fail loudly, and `--list` no longer appends a false `No tests matched`. CI relying on the old lenient codes may start failing as intended.
- `await-all` (and `pmap`, built on it) now return results in input order instead of completion order. Code that tolerated shuffled concurrent results sees deterministic ordering now.
- The docs doctest harness (`composer test-docs`, `tests/doctest/`) was removed; user-facing guides now live on [phel-lang.org](https://phel-lang.org/documentation/).

New in 0.44 (config tooling + sharper test runner): `phel config` prints the merged config with provenance, `phel test --coverage` and `--watch`, `phel build --report`, `phel init --template=<name>`, optimization levels (`phel build -O <level>`), LSP PHP interop, and a REPL reload workflow (`(reload!)`, `(run-tests ...)`). See the [0.44 release notes](/releases/0-44-feedback-loop/).

Behaviour changes in 0.43:

- A `never` / `void` / `null` `:tag` return on a value-returning function is now a compile error instead of a load-time fatal (`mixed`, `?T`, and union/intersection tags still pass).

New in 0.43 (typed PHP interop): `php/callable` first-class callables, `defstruct ^:php/readonly` fields, `defenum` methods + interfaces, `^:php/override` (`#[\Override]`), and `definterface` typed class constants. See the [0.43 release notes](/releases/0-43-first-class-callable/).

Behaviour changes in 0.42:

- Structs print with a `.` separator instead of `\` (e.g. `(my.ns.point 1 2)`). Snapshot tests or code that parses struct output must match the new form.
- `str/index-of` returns `nil` for an empty search string instead of throwing a PHP `ValueError`.
- Lexer columns are counted in code points, so error locations in multibyte source point at the right column.
- `if-let`, `when-let`, `if-some`, `when-first` are now hygienic: a user binding named like the macros' internal temporary no longer collides.

New in 0.42 (richer typed PHP interop, all opt-in):

- `phel.reflect`: read PHP 8 attributes (`class-attributes` / `method-attributes` / ...) and bridge native enums (`enum->keyword` / `keyword->enum` / `enum-values`).
- `defenum` native backed enums and `defexception` with an optional parent class.
- `php/ref` passes a local by reference into `php/->` / `php/::` and plain PHP calls like `preg_match` / `sort`.
- `hydrate` / `bean` bridge a Phel map and a typed PHP object both ways.
- PHP 8 named arguments in `php/new` / `php/->` / `php/::` via the `:&` marker, e.g. `(php/new \App\Mailer :& :host "smtp")`.
- `iterator-seq` builds a lazy seq over any PHP `Traversable`.
- `defstruct` `:php` blocks declare inline PHP magic methods; `phel format` and `phel.http` JSON bodies / response builders round it out.

See the [0.42 release notes](/releases/0-42-life-everything/) for the full list.

Breaking changes in 0.41:

- Stricter argument errors: `take` with a non-int count, `remove` / `select-keys` on a non-seqable, `int` / `long` / `float` / `double` on non-numeric values, and `get` / `assoc` / `update` with non-int keys now raise clean Phel errors instead of leaking a PHP `TypeError`. Code that leaned on silent coercion must pass real values.
- Clojure-aligned laziness: `map`, `filter`, `remove`, `concat`, `distinct`, and `repeatedly` no longer realize their head eagerly, `map` over `nil` returns a lazy seq, and `LazySeq` no longer drops `nil` values. Force with `doall` or `vec` where you relied on eager evaluation.

Breaking changes in 0.40:

- `phel agent-install`: the `.agents/` docs tree is now copied by default. The `--with-docs` flag is gone; use `--no-docs` to opt out.
- Map destructuring with `:keys` / `:strs` / `:syms` and a non-vector value now reports a shape error instead of silently dropping the binding.

Breaking changes in 0.39 (Clojure-aligned core type renames):

- `Variable` → `Atom`
- `Uuid` → `UUID`
- `BigInteger` → `BigInt`
- `Rational` → `Ratio`
- `PhelFuture` → `Future`
- `ExInfoException` → `ExceptionInfo`
- `LazyCons` → `Cons`
- Auto-refer: common `Phel\Lang\*` types resolve without `(:use ...)`. `Interface` suffix dropped (e.g. `(php/instanceof x LazySeq)`). User `(:use ...)` still overrides.

Earlier upgrades (0.37):

- `PhelConfig` setters replaced by immutable `withX()` chain; old `setX()` shims emit deprecation notices. See [Configuration](/documentation/configuration/).
- `PhelConfig::forProject(ProjectLayout $layout = Flat, string $mainNamespace = '')`: layout argument is first, `Flat` is the default.
- `Phel\Printer` moved to `Phel\Shared\Printer`. Phel sources should `(:use Phel.Shared.Printer.Printer)`; the old path no longer resolves.
- Cross-module exceptions + `CodeSnippet` moved to `Phel\Shared\Exceptions` / `Phel\Shared\Parser\ReadModel`.
- Runtime state (cache, REPL history, error log) now lives under `.phel/`. Override via `withPhelDir('...')` or the `PHEL_DIR` env var.

## Next steps

- [Getting Started](/documentation/getting-started): first REPL session, project tour.
- [Editor Support](/documentation/tooling/editor-support): Emacs, VS Code, IntelliJ, Vim.
- [CLI Commands](/documentation/tooling/cli-commands): every subcommand.
- [Configuration](/documentation/configuration): `phel-config.php` options.
