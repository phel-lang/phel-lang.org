+++
title = "Upgrading"
weight = 5
description = "What breaks in each Phel release from 0.54 back to 0.46, the one-line fix for each change, and the steps to run after every bump."
aliases = ["/documentation/upgrading/"]
+++

Breaking changes from 0.54 back to 0.46, newest first, with the fix for each. Read each section between your current version and the target. Each version's full notes are in [Releases](/releases/); older versions are in [the changelog](https://github.com/phel-lang/phel-lang/blob/main/CHANGELOG.md).

## How to upgrade

1. Fix deprecations on your current version first ([how they work](/documentation/reference/stability/#deprecations)):

   ```bash
   vendor/bin/phel test --warn-deprecations      # or PHEL_WARN_DEPRECATIONS=1
   ```

2. Bump the version and clear the compiled cache:

   ```bash
   composer require phel-lang/phel-lang
   vendor/bin/phel cache:clear
   ```

3. Run your tests again, and rebuild any downstream project.

Never skip the cache clear. Compiled PHP from an older install can reference renamed core types and fail to load.

Going to 1.0 from 0.49 or later? Follow [the 1.0 upgrade guide](https://github.com/phel-lang/phel-lang/blob/main/docs/migration/upgrade-0.49-to-1.0.md).

## 0.54

| Old | New |
|---|---|
| `{:a 1 :a 2}` read as `{:a 2}` | `PHEL203` error. Remove the repeated key |
| `(defn sq [n] ...)` then `(sq 3 4)` ran | `PHEL002` error. Pass one argument per parameter |
| `{:my/keys [a]}` read `:a` | Reads `:my/a`. Write `{:keys [a]}` to read `:a` |
| A `:refer` of a name the namespace does not define failed at the call, or never | `PHEL013` error at the `ns` form. Drop the name or define it |
| `transduce` with a reducer that has only a 2-arity | Fails when it completes. Wrap the reducer in `completing` |
| Implementing `CompilerFacadeInterface` | Add `withoutDeprecations()`, `rejectSupersededForms()` and `findSimilarNames()` |

A `:refer` of a private name fails the same way. `^:dynamic` and `^:redef` fns skip the argument count check. The last row affects PHP code that implements Phel's PHP API only.

## 0.53

| Old | New |
|---|---|
| Older PHP | PHP 8.5 is the minimum |
| `"\400"` (octal escape above `\377`) compiled to NUL | Compile error. Use a valid escape |
| `Phel\Filesystem\FilesystemFacadeInterface` | `Phel\Shared\Facade\FilesystemFacadeInterface` |
| `Phel\Fiber\FiberFacadeInterface` | Type-hint `Phel\Fiber\FiberFacade` |
| `MetaInterface::withMeta()` mutating | Returns a copy. Keep the returned value |
| Lint rule code constants | Moved to `Phel\Shared\LintRuleCodes`, same strings |

The last four affect PHP code that calls Phel's PHP API only.

## 0.52

| Old | New |
|---|---|
| `(php/new \Foo arg)` | `(new \Foo arg)` or `(\Foo. arg)` |
| `(php/-> obj (method arg))` | `(.method obj arg)`, fields with `(.-field obj)` |
| `(php/:: \Foo (method arg))` | `(\Foo/method arg)`, constants with `\Foo/CONST` |
| `(set-var v x)` | `(alter-var-root (var v) (constantly x))` |
| `CommandFacadeInterface::getRuntimeErrorReport()` result | Returns a string |
| `ErrorCode::INVALID_QUOTE`, `INVALID_UNQUOTE`, `INVALID_CHARACTER` | Removed |

Writing one of the first four forms in source is a `PHEL012` error. The compiler still emits them, so macros that expand to them keep working. The rest of `php/*` stays. Runtime errors now carry a code: see the [Error Reference](/documentation/reference/errors/).

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

## 0.49

No breaking changes. [0.49 release notes](/releases/0-49-arity-lane/).

## 0.48

No breaking changes. [0.48 release notes](/releases/0-48-step-into/).

## 0.47

No breaking changes. [0.47 release notes](/releases/0-47-clear-signals/).

## 0.46

| Old | New |
|---|---|
| `PhelConfig` `setX()` setters | `withX()` methods |
| `useLayout()`, `useNestedLayout()`, `useFlatLayout()` | `withLayout(ProjectLayout::...)` |
| `setX()` on `PhelBuildConfig`, `PhelExportConfig` | `withX()` methods |
| `phel build` exited `0` when compilation aborted | Exits non-zero. CI may start failing as intended |
| Broken `phel-config.php` threw a stack trace | Clear error naming the file, exit code 1 |

The incremental build cache now recompiles namespaces that depend on a changed one.
