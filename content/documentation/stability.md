+++
title = "Stability Policy"
weight = 85
description = "What a Phel version number promises: language and embedding stability for 1.x, which PHP symbols are public, how deprecations are announced, and where the upgrade guides live."
+++

What a Phel version number promises you, which symbols it covers, and how those are allowed to change.

Phel is at **{{ phel_version() }}**. Until `1.0.0` ships, this page describes the *target*: what `1.x` will guarantee, with the enforceable parts already gated in CI. `0.x` remains free to break, and [the changelog](https://github.com/phel-lang/phel-lang/blob/main/CHANGELOG.md) marks every such change **BREAKING**.

`1.0.0` is a stability commitment, not a feature release. What arrives is a promise that what exists stops moving.

## Two promises

1. **Language stability.** Phel source that compiles on `1.0.0` compiles on every later `1.x`. Reader syntax, special forms and the public `phel.*` core API do not break inside the major. The frozen list is [the language surface spec](https://github.com/phel-lang/phel-lang/blob/main/docs/spec/language-surface.md).
2. **Embedding stability.** The PHP surface listed under [Public PHP API](#public-php-api) follows semver, so a project wiring Phel into its own tooling can take `1.x` updates without reading a diff.

Anything else is explicitly not promised. That boundary is what makes the promise affordable, not a gap to be filled later.

## Public PHP API

This section matters if you call Phel's PHP classes from your own code. If you only write `.phel` files, promise 1 is the one that covers you.

A PHP symbol is public if and only if it matches a rule below. **Everything else in `src/php/` is internal, carries `@internal`, and may change in any release including a patch.**

| # | Rule | Examples |
|---|------|----------|
| 1 | The `\Phel` runtime class | `Phel::vector()`, `Phel::bootstrap()` |
| 2 | `Phel\<Module>\<Module>Facade` | `Phel\Compiler\CompilerFacade` |
| 3 | `Phel\<Module>\<Module>FacadeInterface` | `Phel\Fiber\FiberFacadeInterface` |
| 4 | Everything under `Phel\Shared\` | `Phel\Shared\Facade\CompilerFacadeInterface`, `Phel\Shared\CompileOptions` |
| 5 | Everything under `Phel\Lang\` | `Phel\Lang\Symbol`, `Phel\Lang\Collections\Map\PersistentMapInterface` |
| 6 | Everything under `Phel\Config\` | `Phel\Config\PhelConfig`, `Phel\Config\ProjectLayout` |

Rule 1 exists because emitted PHP calls into it: every compiled `.phel` file is a consumer, so `\Phel` is load-bearing for build artifacts produced by older versions.

Rules 4 to 6 are whole namespaces rather than curated lists because they are what a consumer cannot avoid: the values that cross the facade boundary (`Lang`), the contracts those facades speak in (`Shared`), and the object your `phel-config.php` constructs (`Config`).

### Internal by construction

Internal even when a public class returns it:

- `Phel\<Module>\Domain\`, `…\Application\`, `…\Infrastructure\`
- `*Factory`, `*Config`, `*Provider` and `#[ServiceMap]` accessors
- `Phel\<Module>\Transfer\` (cross-module transfers live in `Phel\Shared\Api\`)

Depending on an internal symbol is not forbidden, it is unsupported. Reaching for one usually means a facade is missing a method, which is worth [an issue](https://github.com/phel-lang/phel-lang/issues).

### What counts as a break

Breaking for a public symbol, so major only:

- removing a class, interface, method, constant or public property
- narrowing a parameter type, adding a required parameter, reordering parameters
- widening a return type, or changing it to an unrelated type
- adding a method to an interface, or making an existing method abstract
- changing a class from non-`final` to `final`, or removing a public constructor

Not breaking, so fine in a minor or a patch:

- adding a class, or a method to a `final` class
- adding an optional parameter at the end of a signature
- widening a parameter type, narrowing a return type
- any change to an `@internal` symbol

Interfaces under `Phel\Shared\Facade\` are the one place where adding a method bites implementers rather than callers. The changelog labels those **BREAKING (PHP API, implementers only)**.

### How it is enforced today

The enforceable half of both promises already runs in CI, on every pull request:

- a snapshot test reflects over every symbol the rules above match, so any signature change fails the build until the snapshot is regenerated and the diff reviewed
- an annotation test pins the complement, so the public/internal split reaches your IDE and your static analyser instead of living only on this page
- a standard-library snapshot fails when a `phel.*` definition or one of its arities disappears
- the special-form list is compared against the analyzer, so the language surface spec cannot drift from the compiler

One known gap: a public class inheriting a *vendor* base is rendered without that base's members, so a dependency upgrade that changes an inherited signature is a real break the snapshot cannot see.

## How deprecations are announced

1. **Announce before removing.** A deprecated symbol ships with a notice for at least one full minor, and is removed only in a major.
2. **One channel, off by default.** Everything the compiler knows about reports through a single switch, enabled with `--warn-deprecations` or `PHEL_WARN_DEPRECATIONS=1`. Notices go to stderr and cannot break a build. A deprecation inside a `vendor/` path is never reported: it belongs to the dependency's author.
3. **No version promises in the message.** The release such a message names inevitably ships and the text goes stale. The tracking issue carries the schedule.
4. **A migration page, always.** Every live deprecation appears in [the deprecated surface map](https://github.com/phel-lang/phel-lang/blob/main/docs/migration/deprecated-surface.md) with its replacement and a mechanical before/after, and moves to [the removed list](https://github.com/phel-lang/phel-lang/blob/main/docs/migration/removed-deprecated-core-fns.md) once it is gone.
5. **PHP-side deprecations** use `#[\Deprecated]` or `@deprecated`, so `phpstan/phpstan-deprecation-rules` reports them in your project.

Turn the notices on for one run to find out whether you are affected:

```bash
vendor/bin/phel run --warn-deprecations src/main.phel
PHEL_WARN_DEPRECATIONS=1 vendor/bin/phel test
```

Or permanently, in `phel-config.php`:

```php
return PhelConfig::forProject()->withWarnDeprecations(true);
```

Uses inside Phel's own standard library are suppressed, so the output lists only code you own.

### Two deprecations announce without the flag

- **A renamed CLI option.** A renamed flag is one unmissable event rather than something scattered through your source, so it prints a one-line stderr notice on every run. No rename is in flight today.
- **The `\` namespace separator.** `.` is the spelling going forward (`shared.utils`), and `\` is deprecated. It is **not** removed in `1.0` and stays supported through all of `1.x`, but a notice nobody is shown does not give anyone time to act, so this one warns by default. It reports once per file and symbol, and never under `vendor/`. Details and the migration path: [backslash to dot](https://github.com/phel-lang/phel-lang/blob/main/docs/migration/backslash-to-dot.md).

### Already removed as source in 0.52

Four forms are no longer accepted in source. Writing one is a [`PHEL012`](/documentation/reference/errors/#phel012-superseded-form) error, with no flag to turn it off.

| No longer valid as source | Write instead |
|---|---|
| `php/new` | `(new \Foo arg)` or `(\Foo. arg)` |
| `php/->` | `(.method obj arg)` and `(.-field obj)` |
| `php/::` | `(\Foo/method arg)` and `\Foo/CONST` |
| `set-var` | `(alter-var-root (var v) f)`, or `(set! v x)` for the current binding frame |

They could not simply be deleted: the compiler still emits all four, `(new \C 1)` becomes `(php/new \C 1)` and `binding` expands to `set-var`. What went is the ability to write them, not the forms themselves, so generated PHP does not change and macros expanding to them keep working. The rest of `php/*` stays, because each reaches a PHP capability Phel has no other word for.

## PHP support

- `1.x` requires **PHP 8.4 or newer**. Raising the minimum is breaking, so major only. It can still move before `1.0.0`; from the major it is frozen.
- Every PHP minor from the minimum to the newest stable runs the full compiler and core suites in CI, added within one Phel minor of its release.
- Support for a PHP minor is never dropped inside a major, including after it leaves PHP's own security window. Phel keeps testing it; the security posture of the runtime is your call.

## Platform support

| Tier | Platforms | Meaning |
|---|---|---|
| Supported | Linux, macOS | Full compiler, core and PHAR suites run in CI on every push. A failure blocks a release. |
| Best effort | Windows | A reduced suite runs in CI. Bugs are fixed, but a Windows-only failure does not block a release. |

The distinction is about what the project commits to, not about what works. The platform-sensitive parts are narrow: path separators, `readline` in the REPL, and the `phel watch` backends, which fall back to polling.

## Configuration and project layout

Your `phel-config.php` returns a `Phel\Config\PhelConfig`, covered by rule 6. Two things are frozen:

- **The wire keys.** `PhelConfig::SRC_DIRS` is the string `'src-dirs'`, and every sibling constant is the literal key the config reader consumes. Renaming one would silently change the meaning of an existing config file.
- **The builder API.** `with*()` methods only gain siblings. An existing one keeps its name, its parameter type and its "returns a new instance" contract.

The `.phel/` layout is frozen too: a tool may rely on `.phel/cache/` and `.phel/repl-history` being where they are. `PHEL_DIR` relocates the whole tree. See [Configuration](/documentation/configuration/).

## Explicitly not covered

Not under semver, and not before `1.0` either:

- The exact PHP source the emitter produces. Only its *behaviour* is promised; the test suite pins the text so changes are reviewed, not forbidden.
- Compiler diagnostic wording and error-output shape. The [error codes](/documentation/reference/errors/) are the stable thing to match on, not the message.
- The `.phel/cache/` file format. It is keyed by source hash plus optimization level, Phel version and the fingerprint of the declared `cache-env-vars`, so a version bump invalidates it by design.
- Anything under `tests/`, `tools/`, `build/` or `resources/` in the Phel repository.
- The nREPL and LSP wire protocols beyond the upstream specifications.

## Upgrading

- **Version by version:** the [Installation](/documentation/installation/) page carries the upgrade notes for every release, with the breaking changes and the `cache:clear` you need after each bump.
- **Straight to 1.0 from 0.49 or later:** [the upgrade guide](https://github.com/phel-lang/phel-lang/blob/main/docs/migration/upgrade-0.49-to-1.0.md) walks the whole path step by step. Most projects need nothing; where there is work, it is removing calls to things that have been printing notices for several releases.
- **Something behaves differently from Clojure?** Check [the divergence catalogue](https://github.com/phel-lang/phel-lang/blob/main/docs/spec/clojure-divergences.md) first. If a behaviour is listed there, it is deliberate. Anything unlisted that differs is worth an issue.

The normative policy this page summarises lives in [docs/stability.md](https://github.com/phel-lang/phel-lang/blob/main/docs/stability.md) in the Phel repository, alongside the reasoning and the quality gates behind each promise.
