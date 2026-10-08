+++
title = "Upgrading"
weight = 5
description = "What breaks in each Phel release from 1.0 back to 0.37, the one-line fix for each change, and the steps to run after every bump."
aliases = ["/documentation/upgrading/"]
+++

This page lists every breaking change from 1.0 back to 0.37, with the fix for each. Newest first. Read each section between your current version and the target. The full changelog for each version is in [Releases](/releases/).

## How to upgrade

1. Fix deprecations on your current version first. They show only when you ask for them:

   ```bash
   vendor/bin/phel test --warn-deprecations      # or PHEL_WARN_DEPRECATIONS=1
   ```

2. Bump the version and clear the compiled cache:

   ```bash
   composer require phel-lang/phel-lang:^1.0
   vendor/bin/phel cache:clear
   ```

3. Run your tests again, and rebuild any downstream project.

Never skip the cache clear. Compiled PHP from an older install can reference renamed core types and fail to load.

What a version number promises is on [Stability Policy](/documentation/reference/stability/). Releases older than 0.37 are in [the changelog](https://github.com/phel-lang/phel-lang/blob/main/CHANGELOG.md).

## 1.0

`1.0` adds no features. It removes the deprecated surface and keeps what remains stable for every `1.x` release.

Coming from 0.49 or later, follow [the 1.0 upgrade guide](https://github.com/phel-lang/phel-lang/blob/main/docs/migration/upgrade-0.49-to-1.0.md) step by step. It covers the PHP and dependency floors, the removed deprecations, code the compiler now rejects, and code that compiles but behaves differently. From a release older than 0.49, first work through the sections below from your version up to 0.49.

## 0.54

| Old | New |
|---|---|
| `{:a 1 :a 2}` read as `{:a 2}` | `PHEL203` error. Remove the repeated key |
| `(defn sq [n] ...)` then `(sq 3 4)` ran | `PHEL002` error. Pass one argument per parameter |
| `{:my/keys [a]}` read `:a` | Reads `:my/a`. Write `{:keys [a]}` to read `:a` |
| A `:refer` of a name the namespace does not define failed at the call, or never | `PHEL013` error at the `ns` form. Drop the name or define it |
| `transduce` with a reducer that has only a 2-arity | Fails when it completes. Wrap the reducer in `completing` |
| Implementing `CompilerFacadeInterface` | Add `withoutDeprecations()`, `rejectSupersededForms()` and `findSimilarNames()` |

A `:refer` of a private name fails the same way. `^:dynamic` and `^:redef` fns skip the argument count check. The last row affects PHP code that implements Phel's PHP API only. [0.54 release notes](/releases/0-54-road-to-one/).

## 0.53

| Old | New |
|---|---|
| Older PHP | PHP 8.5 is the minimum |
| `"\400"` (octal escape above `\377`) compiled to NUL | Compile error. Use a valid escape |
| `Phel\Filesystem\FilesystemFacadeInterface` | `Phel\Shared\Facade\FilesystemFacadeInterface` |
| `Phel\Fiber\FiberFacadeInterface` | Type-hint `Phel\Fiber\FiberFacade` |
| `MetaInterface::withMeta()` mutating | Returns a copy. Keep the returned value |
| Lint rule code constants | Moved to `Phel\Shared\LintRuleCodes`, same strings |

The last four affect PHP code that calls Phel's PHP API only. [0.53 release notes](/releases/0-53-floor-raised/).

## 0.52

| Old | New |
|---|---|
| `(php/new \Foo arg)` | `(new \Foo arg)` or `(\Foo. arg)` |
| `(php/-> obj (method arg))` | `(.method obj arg)`, fields with `(.-field obj)` |
| `(php/:: \Foo (method arg))` | `(\Foo/method arg)`, constants with `\Foo/CONST` |
| `(set-var v x)` | `(alter-var-root (var v) (constantly x))` |
| `CommandFacadeInterface::getRuntimeErrorReport()` result | Returns a string |
| `ErrorCode::INVALID_QUOTE`, `INVALID_UNQUOTE`, `INVALID_CHARACTER` | Removed |

Writing one of the first four forms in source is a `PHEL012` error. The compiler still emits them, so macros that expand to them keep working. The rest of `php/*` stays. Runtime errors now carry a code: see the [Error Reference](/documentation/reference/errors/). [0.52 release notes](/releases/0-52-honest-output/).

## 0.51

| Old | New |
|---|---|
| Bare all-caps class as a value: `(def driver-class PDO)` | Write `\PDO`, `(:use PDO)`, or `PDO/class`. A bare name reads as a global constant |
| Constant holding a class name as a member target | Use `php/NAME`, or bind it with `let` first |

The compiler warns at each all-caps site before it fails. See [ADR 0016](https://github.com/phel-lang/phel-lang/blob/v0.51.0/docs/adr/0016-a-bare-all-caps-host-name-reads-by-position.md).

Deprecated (warns under `--warn-deprecations`):

| Old | New |
|---|---|
| `to-php-array` | `to-array` |
| Key-first map destructuring `{:key local}` | Binding-first `{local :key}` |

[0.51 release notes](/releases/0-51-only-once/).

## 0.50

Everything that printed a deprecation notice in 0.49 is gone. Run your tests with `--warn-deprecations` on 0.49 first. A clean run means the upgrade is a plain version bump.

| Old | New |
|---|---|
| `push`, `put`, `unset` | `conj`, `assoc`, `dissoc` |
| `put-in`, `unset-in` | `assoc-in`, `dissoc-in` |
| `values` | `vals` |
| `function?`, `hash-map?` | `fn?`, `map?` |
| `id` | `identical?` |
| `str-contains?` | `phel.string/contains?` |
| `set-meta!` | `with-meta` |
| `#\| \|#` and bare `#` comments | `;` or `;;` |
| `\|(...)` short functions | `#(...)` with `%` |
| `,` and `,@` unquote | `~` and `~@` |
| `foo$` auto-gensym | `foo#` |
| Lazy seqs print as `@[1 2 3]` | Print as `(1 2 3)` |
| Wrong arity on a core fn was ignored | Raises an arity error. `arity` reports `0` for multi-arity fns |
| `(max)`, `(min)` threw at runtime | Compile error `PHEL002` |
| Unresolved `(:require ...)` failed on first use | Fails at require time |
| Gacela 1, `symfony/console` 6 | `gacela-project/gacela ^2.0`, `symfony/console ^7.3\|^8.0`. Rename `DependencyProvider` classes to `Provider` |
| `phel index --out` | `--output` (`-o`) |
| `phel config --json` | `--format=json` (`-f json`) |

{% <callout kind="warning"> %}
`,` and `foo$` fail silently. `,` is now whitespace, so `` `(f ,x) `` still parses but quotes `x` instead of unquoting it. `foo$` is now an ordinary symbol, so a macro that binds `tmp$` compiles but loses its unique name. Search everything that generates Phel, not only `.phel` files:

```bash
grep -rnE ",[A-Za-z0-9_(\[{'\`~@:*+-]" --include='*.phel' src/ tests/
```
{% </callout> %}

[0.50 release notes](/releases/0-50-the-last-zero/).

## 0.49

No breaking changes. Behaviour changes:

- `partition` and `partition-all` accept Clojure's `[n step coll]` arity, and `partition` also `[n step pad coll]`.
- Sorted maps and sets treat `NaN` as equal to itself and greater than every number. `(count (sorted-set NAN NAN))` is `1`.
- `pr` and `prn` print a char literal like `\A` as `"A"`.
- Opt-in `PhelConfig::withStripSymbolMeta()` drops symbol metadata from build output. With it on, `phel doc` and `(meta ...)` over built defs return `nil`.
- `PhelConfig::withAppModulePaths()` limits Gacela module discovery. The default is still the whole root.

[0.49 release notes](/releases/0-49-arity-lane/).

## 0.48

No breaking changes. Behaviour changes:

- The compiled-code cache key hashes only the `.phel` source. Old cache entries are invalidated once on first run.
- In read-only environments, caches degrade quietly and commands report a clear error instead of a fatal.

[0.48 release notes](/releases/0-48-step-into/).

## 0.47

No breaking changes. Behaviour changes:

- `phel test` prints full structural diffs (`+`, `-`, `~`) for differing collections.
- `phel compile` prints folded values to stderr when a form emits no PHP.
- `phel init` enables optimization level 2 in new configs. Existing configs are untouched.

[0.47 release notes](/releases/0-47-clear-signals/).

## 0.46

| Old | New |
|---|---|
| `PhelConfig` `setX()` setters | `withX()` methods |
| `useLayout()`, `useNestedLayout()`, `useFlatLayout()` | `withLayout(ProjectLayout::...)` |
| `setX()` on `PhelBuildConfig`, `PhelExportConfig` | `withX()` methods |
| `phel build` exited `0` when compilation aborted | Exits non-zero. CI may start failing as intended |
| Broken `phel-config.php` threw a stack trace | Clear error naming the file, exit code 1 |

The incremental build cache now recompiles namespaces that depend on a changed one. [0.46 release notes](/releases/0-46-native-path/).

<details>
<summary>Older releases (0.45 to 0.37)</summary>

## 0.45

| Old | New |
|---|---|
| `argv` | `*argv*` |
| `phel index --out`, `phel config --json` | `--output`, `--format=json` (old flags warned, removed in 0.50) |
| Overflowing constant int arithmetic folded to `float` | Folds to `BigInt`, like the runtime |
| Integer floats printed inconsistently | `str`, `print` and the REPL all keep `.0` |

[0.45 release notes](/releases/0-45-warm-boot/).

## 0.44

| Old | New |
|---|---|
| `gacela-project/gacela` older than 1.15 | `^1.15` |
| `phel test` exited `0` when nothing ran | Non-zero. Bad paths and selectors fail loudly |
| `await-all` and `pmap` returned in completion order | Return in input order |
| `composer test-docs`, `tests/doctest/` | Removed. Guides live on this site |

Edits to `phel-config.php` take effect immediately again. [0.44 release notes](/releases/0-44-feedback-loop/).

## 0.43

| Old | New |
|---|---|
| `never`, `void` or `null` `:tag` return on a value-returning fn was a load-time fatal | Compile error. `mixed`, `?T` and union tags still pass |

[0.43 release notes](/releases/0-43-first-class-callable/).

## 0.42

| Old | New |
|---|---|
| Structs printed as `(my\ns\point 1 2)` | Print as `(my.ns.point 1 2)`. Update snapshot tests |
| `str/index-of` with `""` threw `ValueError` | Returns `nil` |
| Lexer columns counted in bytes | Counted in code points |
| `if-let`, `when-let`, `if-some`, `when-first` could capture a user binding | Hygienic |

The typed interop added in 0.42 used `php/new`, `php/->` and `php/::`. Those are `PHEL012` errors since 0.52, see [0.52](#0-52). [0.42 release notes](/releases/0-42-life-everything/).

## 0.41

| Old | New |
|---|---|
| `take` with a non-int count, `remove` or `select-keys` on a non-seqable, `int`/`long`/`float`/`double` on non-numbers, `get`/`assoc`/`update` with bad keys leaked a PHP `TypeError` | Raise a Phel error. Pass real values |
| `map`, `filter`, `remove`, `concat`, `distinct`, `repeatedly` realized their head eagerly | Fully lazy. Force with `doall` or `vec` |
| `map` over `nil` | Returns a lazy seq |
| `LazySeq` dropped `nil` values | Keeps them |

[0.41 release notes](/releases/0-41-fold-and-inline/).

## 0.40

| Old | New |
|---|---|
| `phel agent-install --with-docs` | Docs copied by default. Opt out with `--no-docs` |
| `:keys`, `:strs`, `:syms` with a non-vector value dropped the binding | Shape error |

[0.40 release notes](/releases/0-40-sharper-edges/).

## 0.39

Core types renamed to match Clojure:

| Old | New |
|---|---|
| `Variable` | `Atom` |
| `Uuid` | `UUID` |
| `BigInteger` | `BigInt` |
| `Rational` | `Ratio` |
| `PhelFuture` | `Future` |
| `ExInfoException` | `ExceptionInfo` |
| `LazyCons` | `Cons` |
| `...Interface` suffix, e.g. `LazySeqInterface` | `LazySeq` |

Common `Phel\Lang\*` types now resolve without `(:use ...)`. Your own `(:use ...)` still wins. [0.39 release notes](/releases/0-39-parity-pass/).

## 0.37

| Old | New |
|---|---|
| `PhelConfig` `setX()` | Immutable `withX()` chain (see [Configuration](/documentation/reference/configuration/)) |
| `PhelConfig::forProject()` arguments | `forProject(ProjectLayout $layout = Flat, string $mainNamespace = '')`: layout first, `Flat` by default |
| `Phel\Printer` | `Phel\Shared\Printer`. In Phel: `(:use Phel.Shared.Printer.Printer)` |
| Cross-module exceptions, `CodeSnippet` | `Phel\Shared\Exceptions`, `Phel\Shared\Parser\ReadModel` |
| Runtime state (cache, REPL history, error log) in several places | Under `.phel/`. Override with `withPhelDir('...')` or `PHEL_DIR` |

[0.37 release notes](/releases/0-37-type-inference/).

</details>
