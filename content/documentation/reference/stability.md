+++
title = "Stability Policy"
weight = 6
description = "What a Phel version promises: language, embedding and command-line stability for 1.x, which PHP symbols are public, how deprecations work, and PHP support."
aliases = ["/documentation/stability/"]
+++

Phel is at **{{ <phel_version /> }}**. This page describes what `1.x` will guarantee. `0.x` can still break, and [the changelog](https://github.com/phel-lang/phel-lang/blob/main/CHANGELOG.md) marks each such change **BREAKING**. The full policy and its reasoning are in [docs/stability.md](https://github.com/phel-lang/phel-lang/blob/main/docs/stability.md).

## Three promises

1. **Language.** Phel source that compiles on `1.0.0` compiles on every later `1.x`. Reader syntax, special forms and the public `phel.*` core API do not break inside the major. The frozen list is [the language surface spec](https://github.com/phel-lang/phel-lang/blob/main/docs/spec/language-surface.md).
2. **Embedding.** The public PHP API follows semver, so a project that calls Phel from PHP can take `1.x` updates without reading a diff.
3. **Command line.** The parts of `phel` a program reads follow semver, so an editor plugin or a CI script can take `1.x` updates the same way.

## Public PHP API

Read this if you call Phel's PHP classes. Public means: the `\Phel` runtime class, each `Phel\<Module>\<Module>Facade`, and everything under `Phel\Shared\`, `Phel\Lang\` and `Phel\Config\`. Everything else in `src/php/` is internal, carries `@internal`, and can change in any release. If you need an internal symbol, [open an issue](https://github.com/phel-lang/phel-lang/issues): a facade probably lacks a method.

`phel-config.php` returns a `Phel\Config\PhelConfig`, so config key strings, existing `with*()` methods, and the `.phel/` layout (`.phel/cache/`, `.phel/repl-history`) are frozen too. See [Configuration](/documentation/reference/configuration/).

What counts as a break, and how CI checks it: [docs/stability.md](https://github.com/phel-lang/phel-lang/blob/main/docs/stability.md).

## Deprecations

1. **Announce before removing.** A deprecated symbol prints a notice for at least one full minor and is removed only in a major.
2. **Off by default.** Notices appear with `--warn-deprecations`, `PHEL_WARN_DEPRECATIONS=1`, or `withWarnDeprecations(true)` in `phel-config.php`. They go to stderr and never fail a build. Uses inside Phel's own standard library and under `vendor/` are not reported, so you see only your own code.
3. **Always a migration entry.** Every live deprecation is in [the deprecated surface map](https://github.com/phel-lang/phel-lang/blob/main/docs/migration/deprecated-surface.md) with its replacement and a before/after. It moves to [the removed list](https://github.com/phel-lang/phel-lang/blob/main/docs/migration/removed-deprecated-core-fns.md) once gone.
4. **PHP-side deprecations** use `#[\Deprecated]` or `@deprecated`, so `phpstan/phpstan-deprecation-rules` reports them.

```bash
vendor/bin/phel test --warn-deprecations
PHEL_WARN_DEPRECATIONS=1 vendor/bin/phel run src/main.phel
```

Two deprecations print without the flag:

- **A renamed CLI option** prints a one-line notice on every run. No rename is in progress today.
- **The `\` namespace separator.** Write `shared.utils`, not `shared\utils`. `\` stays supported through all of `1.x`, but warns once per file and symbol (never under `vendor/`). See [backslash to dot](https://github.com/phel-lang/phel-lang/blob/main/docs/migration/backslash-to-dot.md).

## PHP and platform support

- `1.x` requires **PHP 8.5 or newer**. Raising the minimum is breaking, so from `1.0.0` it is frozen until `2.0.0`.
- CI tests every PHP minor from the minimum to the newest stable. A new PHP minor is added within one Phel minor of its release.
- A PHP minor is never dropped inside a major, even after PHP ends its security support.

| Tier | Platforms | Meaning |
|---|---|---|
| Supported | Linux, macOS | Full compiler, core and PHAR suites on every push. A failure blocks a release. |
| Best effort | Windows | A reduced suite in CI. Bugs get fixed, but a Windows-only failure does not block a release. |

Platform-sensitive parts: path separators, `readline` in the REPL, and `phel watch` (which falls back to polling).

## Command-line interface

Read this if a program runs `phel`: an editor plugin, a CI step, a script. What a program reads is covered; what a person reads is not. Covered: commands and their aliases, documented options, exit codes (`0` nothing to fail on, `1` found something, `2` could not run as asked), positions counted from 1, and the fields of machine output (`--format=json`, `--format=github`, `test --reporter=tap` and `junit-xml`). Fields are only added, so ignore the ones you do not know.

Not covered: human text (messages, tables, help wording; match on an error code or a field, never on a message), the Symfony and Gacela helper commands, and what a program run by `run`, `eval`, `test` or `repl` prints. The full list and the output shapes per command: [the CLI reference](https://github.com/phel-lang/phel-lang/blob/main/docs/cli-reference.md). Why the line falls there: [ADR 0022](https://github.com/phel-lang/phel-lang/blob/main/docs/adr/0022-the-cli-machine-surface-is-under-semver.md).

## Not covered

These are outside semver, before and after `1.0`:

- The exact PHP the compiler emits. Only its behaviour is promised.
- The wording and layout of diagnostics. Match on [error codes](/documentation/reference/errors/), not messages.
- The `.phel/cache/` file format. A version bump invalidates it.
- Anything under `tests/`, `tools/`, `build/` or `resources/` in the Phel repository.
- The nREPL and LSP wire protocols beyond the upstream specifications and the command-line surfaces above.

A behaviour differs from Clojure? Check [the divergence catalogue](https://github.com/phel-lang/phel-lang/blob/main/docs/spec/clojure-divergences.md). Listed differences are deliberate. Report anything else as an issue.
