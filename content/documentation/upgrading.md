+++
title = "Upgrading"
weight = 4
description = "Upgrade notes for each Phel release from 0.53 back to 0.37: what breaks, what changes, and the cache clear you need after every bump."
+++

Every release below lists what breaks and what is new. Newest first. Read each section between your current version and the target.

The bump is the same every time. Change the version, then clear the cache:

```bash
composer require phel-lang/phel-lang:^0.53
vendor/bin/phel cache:clear        # or: rm -rf .phel/cache
```

Never skip the cache clear. Compiled PHP from an older install can reference renamed core types and fail to load. Rebuild downstream projects too.

Going to 1.0 from 0.49 or later? Follow [the 1.0 upgrade guide](https://github.com/phel-lang/phel-lang/blob/main/docs/migration/upgrade-0.49-to-1.0.md). What a version number promises is on [Stability Policy](/documentation/stability/). Releases older than 0.37 are in [the changelog](https://github.com/phel-lang/phel-lang/blob/main/CHANGELOG.md).

## 0.53

Breaking changes:

- PHP 8.5 is the minimum.
- An octal escape above `\377` is a compile error. `"\400"` used to compile to NUL.
- PHP API only: `FilesystemFacadeInterface` moves from `Phel\Filesystem` to `Phel\Shared\Facade`, and `Phel\Fiber\FiberFacadeInterface` is gone (type-hint `Phel\Fiber\FiberFacade`). `MetaInterface::withMeta()` always returns a copy, so keep the returned value. Lint rule codes move to `Phel\Shared\LintRuleCodes`, with the same strings.

New: short type tags (`^map`, `^vector`, `^set`, `^list`, `^keyword`, `^symbol`, `^atom`), a warning when the result of a copying collection call is dropped, `phel bench --ab=<git-ref>`, and faster literal `assoc`, `get-in`, `assoc-in` and `update-in`. See the [0.53 release notes](/releases/0-53-floor-raised/).

## 0.52

Breaking changes:

- `php/new`, `php/->`, `php/::` and `set-var` are no longer valid source. Writing one is a `PHEL012` error. Use `(new \Foo arg)`, `(.method obj arg)` / `(.-field obj)`, `(\Foo/method arg)` / `\Foo/CONST`, and `(alter-var-root (var v) f)`. The compiler still emits all four, so generated PHP does not change and macros that expand to them keep working. The rest of `php/*` stays.
- PHP API only: `CommandFacadeInterface::getRuntimeErrorReport()` returns the runtime error report as a string, and `ErrorCode::INVALID_QUOTE`, `INVALID_UNQUOTE` and `INVALID_CHARACTER` are gone.

New: runtime errors carry a code (`PHEL400` to `PHEL404`), `phel explain <code>` prints what a code means with the smallest program that raises it, `--stack-trace` on `run`, `eval` and `repl`, uniform compile-error reporting, `phel lint` exits 1 on a file it cannot parse, `(exit)` / `(quit)` in the REPL, and a pruned OPcache file cache. See the [0.52 release notes](/releases/0-52-honest-output/) and the [Error Reference](/documentation/reference/errors/).

## 0.51

Breaking changes:

- A bare all-caps class name used as a value now reads as a global constant. `(def driver-class PDO)` fails with `Undefined constant "PDO"`. Write `\PDO`, `(:use PDO)`, or `PDO/class`. The compiler warns at each site before it fails. See [ADR 0016](https://github.com/phel-lang/phel-lang/blob/v0.51.0/docs/adr/0016-a-bare-all-caps-host-name-reads-by-position.md).
- A constant that holds a class name can no longer be a bare member target. Use `php/NAME` or bind it with `let` first.

Two deprecations start printing under `PHEL_WARN_DEPRECATIONS=1`: `to-php-array` (use `to-array`) and key-first map destructuring `{:key local}` (use binding-first `{local :key}`).

New: `phel mutate` for mutation testing, `phel test --changed` / `--reporter=github` / `^:skip` / `^:focus`, `phel format --exclude`, `cache-env-vars`, Clojure-style binding-first destructuring, `^:redef`, id and class shorthand in `phel.html`, plus 18-24% faster compiles and large runtime and stdlib speedups. See the [0.51 release notes](/releases/0-51-only-once/).

## 0.50

0.50 is the clean-up release before 1.0. Everything that printed a deprecation notice is now gone. Run `PHEL_WARN_DEPRECATIONS=1 vendor/bin/phel run src/main.phel` on 0.49 first. A clean run means the upgrade is a plain version bump.

Breaking changes:

- Long-deprecated core aliases are removed: `push` → `conj`, `put` → `assoc`, `unset` → `dissoc`, `put-in` → `assoc-in`, `unset-in` → `dissoc-in`, `values` → `vals`, `function?` → `fn?`, `hash-map?` → `map?`, `id` → `identical?`, `str-contains?` → `phel.string/contains?`, `set-meta!` → `with-meta`.
- Deprecated reader syntax is removed: `#| |#` and bare `#` comments (use `;` / `;;`), `|(...)` short functions (use `#(...)` with `%`), `,` / `,@` unquote (use `~` / `~@`), and `foo$` auto-gensym (use `foo#`).
- **`,` and `foo$` are the ones to watch.** The other forms stop parsing, so the compiler finds them. These two stay silent. `,` is now plain whitespace, so `` `(f ,x) `` still parses and quietly quotes `x` instead of unquoting it. `foo$` is now an ordinary symbol, so a macro that binds `tmp$` still compiles but no longer gets a unique name. Rename those to `tmp#`. Sweep anything that generates Phel, not only `.phel` files: `grep -rnE ",[A-Za-z0-9_(\[{'\`~@:*+-]" --include='*.phel' src/ tests/`.
- Lazy sequences print as `(1 2 3)` rather than `@[1 2 3]`, so `@` again means only the deref reader macro.
- Core functions declare real arities, so a wrong argument count raises an arity error instead of being ignored, and `arity` reports `0` for the multi-arity ones.
- `(max)` and `(min)` with no arguments are rejected at compile time (PHEL002) instead of throwing at runtime.
- An unresolved `(:require ...)` fails at require time rather than when the missing name is first used.
- Requires `gacela-project/gacela` `^2.0` and `symfony/console` `^7.3|^8.0`. Gacela 2 resolves module classes by filename suffix, so a `DependencyProvider` must be renamed to `Provider`.
- CLI: `phel index --out` is now `--output` (`-o`), and `phel config --json` is now `--format=json` (`-f json`).

New: `phel.bench` and a `phel bench` command for benchmarks with baseline storage and a tolerance gate; `phel balance` to find (and `--fix`) unbalanced brackets; Clojure-style PHP interop for value members, dynamic calls, enum cases and `set!`; LSP go-to-definition for lexical locals, honouring shadowing and destructuring; and completion plus hover for PHP superglobals. See the [0.50 release notes](/releases/0-50-the-last-zero/).

## 0.49

No breaking changes. Existing code compiles as before.

Behaviour changes:

- `partition` and `partition-all` accept Clojure's extra arities (`[n step coll]`, plus `[n step pad coll]` for `partition`). The `[n coll]` form is unchanged.
- Sorted maps and sorted sets treat `NaN` as equal to itself and ordered after every number, matching Clojure's `compare`. `(count (sorted-set NAN NAN))` is now `1` instead of `2`.
- `pr`/`prn` print char literals like `\A` as one-char strings (`"A"`).
- Multi-arity functions emit fixed-arity `invokeArityN` methods, so build-mode calls with a known arity skip variadic dispatch (roughly 1.5-2x faster per call).
- Optional `PhelConfig::withStripSymbolMeta()` drops symbol metadata from compiled artifacts (-28% size, -40% cold require). With it on, `phel doc` and `(meta ...)` over built defs return nil, and toggling forces a full recompile.
- `PhelConfig::withAppModulePaths()` scopes Gacela module discovery, so `phel list:modules` and `phel cache:warm` no longer fatal on classes that cannot load standalone. Defaults to the previous whole-root walk.

New: 20 new core fns, including `every-pred`, `mapv`, `filterv`, `while`, `distinct?`, `bounded-count`, `map-invert`, `random-sample`, the `pr`/`prn`/`pr-str`/`prn-str` family, the completed atom API (`compare-and-set!`, `swap-vals!`, `reset-vals!`), `clojure.set`-style relational helpers (`select`, `project`, `rename`, `index`), and `subseq`/`rsubseq` over sorted collections; a new `phel\trace` namespace (`trace`, `trace-fn`, `deftrace`, `dotrace`) in the spirit of `clojure.tools.trace`; and `(tap> x)` printing out of the box in the REPL. See the [0.49 release notes](/releases/0-49-arity-lane/).

## 0.48

No breaking changes. Existing code compiles as before.

Behaviour changes:

- The compiled-code cache key now hashes only the `.phel` source, so a compiler-only upgrade no longer serves stale PHP. The cache index format bumped and invalidates old entries once on first run.
- `phel` no longer fatals in read-only or unwritable environments. Caches degrade quietly, and CLI commands report a clear error instead of aborting when a target file can't be written.
- Squaring (`(** x 2)`) and `reduce` over a typed vector now compile to native PHP. Startup and emitted code shrink further via constant-slot sharing and leaner location metadata.

New: new core fns (`trampoline`, `reductions`, `subvec`, `with-open`, `reduce-kv`, `gcd`, `lcm`, `arity`, `variadic?`, `inspect`, and `dbg`); a stepping debugger via `(break)` that opens a sub-REPL over the captured locals (`(continue)` or EOF resumes, so non-interactive runs never hang); `phel test --coverage=html` for a self-contained line-colored coverage report; and `phel export` stubs that carry native parameter/return types from `:tag` metadata. See the [0.48 release notes](/releases/0-48-step-into/).

## 0.47

No breaking changes. Existing code compiles as before.

Behaviour changes:

- `phel test` now prints structural diffs (`+`/`-`/`~`) for any collection that differs, not only the first few entries, so assertion failures point straight at the mismatch.
- `phel compile` prints folded values to stderr when a form emits no PHP output, making constant folding visible instead of silent.
- New projects scaffold with optimization level 2 enabled in `phel-config.php`. Existing configs are untouched.

New: LSP signature help now covers plain Phel calls like `(map f xs)` (arity, parameter names, docstring); nREPL eval responses carry per-session `*1`/`*2`/`*3` value history so Calva and Conjure show the last three results; the REPL's `(doc sym)` renders function examples under an `Example:` heading; runtime errors name the `.phel` location instead of a compiled temp path; and startup is about 30% faster via OPcache re-execution. See the [0.47 release notes](/releases/0-47-clear-signals/).

## 0.46

- **Breaking**: the deprecated `PhelConfig` `setX()` setters and `useLayout()`/`useNestedLayout()`/`useFlatLayout()` were removed, along with the `setX()` shims on `PhelBuildConfig`/`PhelExportConfig`. Use the `with*()` methods in `phel-config.php` instead.
- A broken `phel-config.php` now fails with a clear error naming the file and expected structure (exit code 1) instead of an uncaught exception stack trace.
- `phel build` now exits non-zero when compilation aborts, instead of printing errors while exiting `0`. CI relying on the old exit code may start failing as intended.
- The incremental build cache now cascades recompiles to dependent namespaces when a required namespace changes, preventing stale output reuse.

New: config validation in `phel config` and `phel doctor` (relative paths, source/test dirs, optimization levels, types); `phel build --timing` for per-phase compile durations; `phel init` scaffolds configs with `declare(strict_types=1);`; an optional intermediate compile cache via `withEnableIntermediateCache()`; and a more resilient LSP that stays alive during idle periods and lists symbols from unsaved buffer edits. See the [0.46 release notes](/releases/0-46-native-path/).

## 0.45

- **Breaking**: the runtime CLI-args var is now `*argv*` (earmuffed), matching `*program*` and Clojure's `*command-line-args*`. The old `argv` name was removed. Replace `argv` with `*argv*` in scripts that read command-line arguments.
- CLI flag renames with deprecated aliases kept: `index --output`/`-o` (was `--out`), `config --format=json` (was `--json`). The old flags still work but warn on stderr.
- Overflowing constant int arithmetic (`+`/`-`/`*`) now folds to `BigInt` like the runtime instead of `float`. Float printing is consistent across `str`/`print`/REPL (integer floats keep `.0`).

New: the PHAR ships `phel.core` precompiled, cutting cold-start `run`/`test`/`eval` from ~1.2s to ~0.2s; a native-int arithmetic fast path (~1.8-8x per op); shell completion (`bash`/`zsh`/`fish`) plus CLI short aliases (`r` run, `t` test, `b` build, `e` eval); REPL/nREPL autocompletion of special forms and native symbols; and `phel doctor` OPcache reporting. See the [0.45 release notes](/releases/0-45-warm-boot/).

## 0.44

- Requires `gacela-project/gacela: ^1.15`. Editing `phel-config.php` takes effect immediately again (the stale merged-config cache is cleared on change).
- `phel test` exit codes are stricter: it no longer exits `0` when nothing ran, bad paths/selectors fail loudly, and `--list` no longer appends a false `No tests matched`. CI relying on the old lenient codes may start failing as intended.
- `await-all` (and `pmap`, built on it) now return results in input order instead of completion order. Code that tolerated shuffled concurrent results sees deterministic ordering now.
- The docs doctest harness (`composer test-docs`, `tests/doctest/`) was removed. User-facing guides now live on [phel-lang.org](https://phel-lang.org/documentation/).

New: `phel config` prints the merged config with provenance, `phel test --coverage` and `--watch`, `phel build --report`, `phel init --template=<name>`, optimization levels (`phel build -O <level>`), LSP PHP interop, and a REPL reload workflow (`(reload!)`, `(run-tests ...)`). See the [0.44 release notes](/releases/0-44-feedback-loop/).

## 0.43

- A `never` / `void` / `null` `:tag` return on a value-returning function is now a compile error instead of a load-time fatal (`mixed`, `?T`, and union/intersection tags still pass).

New: `php/callable` first-class callables, `defstruct ^:php/readonly` fields, `defenum` methods + interfaces, `^:php/override` (`#[\Override]`), and `definterface` typed class constants. See the [0.43 release notes](/releases/0-43-first-class-callable/).

## 0.42

- Structs print with a `.` separator instead of `\` (e.g. `(my.ns.point 1 2)`). Snapshot tests or code that parses struct output must match the new form.
- `str/index-of` returns `nil` for an empty search string instead of throwing a PHP `ValueError`.
- Lexer columns are counted in code points, so error locations in multibyte source point at the right column.
- `if-let`, `when-let`, `if-some`, `when-first` are now hygienic: a user binding named like the macros' internal temporary no longer collides.

New, richer typed PHP interop, all opt-in. The `php/new`, `php/->` and `php/::` forms below are `PHEL012` errors since 0.52. Write `(new \Foo ...)`, `(.method obj ...)` and `(\Foo/method ...)` instead.

- `phel.reflect`: read PHP 8 attributes (`class-attributes` / `method-attributes` / ...) and bridge native enums (`enum->keyword` / `keyword->enum` / `enum-values`).
- `defenum` native backed enums and `defexception` with an optional parent class.
- `php/ref` passes a local by reference into `php/->` / `php/::` and plain PHP calls like `preg_match` / `sort`.
- `hydrate` / `bean` bridge a Phel map and a typed PHP object both ways.
- PHP 8 named arguments in `php/new` / `php/->` / `php/::` via the `:&` marker, e.g. `(php/new \App\Mailer :& :host "smtp")`.
- `iterator-seq` builds a lazy seq over any PHP `Traversable`.
- `defstruct` `:php` blocks declare inline PHP magic methods; `phel format` and `phel.http` JSON bodies / response builders round it out.

See the [0.42 release notes](/releases/0-42-life-everything/) for the full list.

## 0.41

Breaking changes:

- Stricter argument errors: `take` with a non-int count, `remove` / `select-keys` on a non-seqable, `int` / `long` / `float` / `double` on non-numeric values, and `get` / `assoc` / `update` with non-int keys now raise clean Phel errors instead of leaking a PHP `TypeError`. Code that leaned on silent coercion must pass real values.
- Clojure-aligned laziness: `map`, `filter`, `remove`, `concat`, `distinct`, and `repeatedly` no longer realize their head eagerly, `map` over `nil` returns a lazy seq, and `LazySeq` no longer drops `nil` values. Force with `doall` or `vec` where you relied on eager evaluation.

See the [0.41 release notes](/releases/0-41-fold-and-inline/).

## 0.40

Breaking changes:

- `phel agent-install`: the `.agents/` docs tree is now copied by default. The `--with-docs` flag is gone. Use `--no-docs` to opt out.
- Map destructuring with `:keys` / `:strs` / `:syms` and a non-vector value now reports a shape error instead of silently dropping the binding.

See the [0.40 release notes](/releases/0-40-sharper-edges/).

## 0.39

Breaking changes, Clojure-aligned core type renames:

- `Variable` → `Atom`
- `Uuid` → `UUID`
- `BigInteger` → `BigInt`
- `Rational` → `Ratio`
- `PhelFuture` → `Future`
- `ExInfoException` → `ExceptionInfo`
- `LazyCons` → `Cons`
- Auto-refer: common `Phel\Lang\*` types resolve without `(:use ...)`. `Interface` suffix dropped (e.g. `(php/instanceof x LazySeq)`). User `(:use ...)` still overrides.

See the [0.39 release notes](/releases/0-39-parity-pass/).

## 0.37

- `PhelConfig` setters replaced by immutable `withX()` chain; old `setX()` shims emit deprecation notices. See [Configuration](/documentation/configuration/).
- `PhelConfig::forProject(ProjectLayout $layout = Flat, string $mainNamespace = '')`: layout argument is first, `Flat` is the default.
- `Phel\Printer` moved to `Phel\Shared\Printer`. Phel sources should `(:use Phel.Shared.Printer.Printer)`; the old path no longer resolves.
- Cross-module exceptions + `CodeSnippet` moved to `Phel\Shared\Exceptions` / `Phel\Shared\Parser\ReadModel`.
- Runtime state (cache, REPL history, error log) now lives under `.phel/`. Override via `withPhelDir('...')` or the `PHEL_DIR` env var.

See the [0.37 release notes](/releases/0-37-type-inference/).
