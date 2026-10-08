+++
title = "Stability Policy"
weight = 6
description = "What a Phel version promises: language, embedding and command-line stability for 1.x, which PHP symbols are public, how deprecations work, and PHP support."
aliases = ["/documentation/stability/"]
+++

This page tells you what a Phel version promises, what the promise covers, and how deprecations reach you.

Phel is at **{{ <phel_version /> }}**. Every `1.x` release keeps these promises.

## Three promises

1. **Language stability.** Phel source that compiles on `1.0.0` compiles on every later `1.x`. Reader syntax, special forms and the public `phel.*` core API do not break inside the major. The frozen list is [the language surface spec](https://github.com/phel-lang/phel-lang/blob/main/docs/spec/language-surface.md).
2. **Embedding stability.** The PHP symbols listed under [Public PHP API](#public-php-api) follow semver, so a project that calls Phel from PHP can take `1.x` updates without reading a diff.
3. **Command-line stability.** The parts of `phel` a program reads, listed under [Command-line interface](#command-line-interface), follow semver, so an editor plugin or a CI script can take `1.x` updates the same way.

## Public PHP API

Read this if you call Phel's PHP classes. If you only write `.phel` files, promise 1 covers you.

A PHP symbol is public only if it matches a rule below. Everything else in `src/php/` is internal, carries `@internal`, and can change in any release, patches included.

| Rule | Examples |
|---|---|
| The `\Phel` runtime class | `Phel::vector()`, `Phel::bootstrap()` |
| `Phel\<Module>\<Module>Facade` | `Phel\Compiler\CompilerFacade` |
| Everything under `Phel\Shared\` | `Phel\Shared\Facade\CompilerFacadeInterface`, `Phel\Shared\CompileOptions` |
| Everything under `Phel\Lang\` | `Phel\Lang\Symbol`, `Phel\Lang\Collections\Map\PersistentMapInterface` |
| Everything under `Phel\Config\` | `Phel\Config\PhelConfig`, `Phel\Config\ProjectLayout` |

`\Phel` is public because compiled PHP calls it, so build output from older versions depends on it. Since 0.53 every facade interface lives under `Phel\Shared\Facade\`.

These stay internal even when a public class returns them:

- `Phel\<Module>\Domain\`, `Application\` and `Infrastructure\`
- `*Factory`, `*Config`, `*Provider` and `#[ServiceMap]` accessors
- `Phel\<Module>\Transfer\` (cross-module transfers live in `Phel\Shared\Api\`)

Using an internal symbol is unsupported. If you need one, [open an issue](https://github.com/phel-lang/phel-lang/issues): a facade probably lacks a method.

### What counts as a break

| Breaking (major only) | Not breaking (minor or patch) |
|---|---|
| Removing a class, interface, method, constant or public property | Adding a class, or a method to a `final` class |
| Narrowing a parameter type, adding a required parameter, reordering parameters | Adding an optional parameter at the end |
| Widening a return type, or changing it to an unrelated type | Widening a parameter type, narrowing a return type |
| Adding a method to an interface, or making a method abstract | Any change to an `@internal` symbol |
| Making a class `final`, or removing a public constructor | |

Adding a method to an interface under `Phel\Shared\Facade\` breaks only implementers. The changelog labels it **BREAKING (PHP API, implementers only)**.

CI snapshots every public signature, every `phel.*` definition and arity, and the special-form list on each pull request. Known gap: members inherited from a vendor base class are not in the snapshot.

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

Since 0.52, writing `php/new`, `php/->`, `php/::` or `set-var` is a [`PHEL012`](/documentation/reference/errors/#phel012-superseded-form) error. See [Upgrading](/documentation/reference/upgrading/#0-52) for the replacements.

## PHP and platform support

- `1.x` requires **PHP 8.5 or newer**. Raising the minimum is breaking, so from `1.0.0` it is frozen until `2.0.0`.
- CI tests every PHP minor from the minimum to the newest stable. A new PHP minor is added within one Phel minor of its release.
- A PHP minor is never dropped inside a major, even after PHP ends its security support.

| Tier | Platforms | Meaning |
|---|---|---|
| Supported | Linux, macOS | Full compiler, core and PHAR suites on every push. A failure blocks a release. |
| Best effort | Windows | A reduced suite in CI. Bugs get fixed, but a Windows-only failure does not block a release. |

Platform-sensitive parts: path separators, `readline` in the REPL, and `phel watch` (which falls back to polling).

## Configuration and project layout

`phel-config.php` returns a `Phel\Config\PhelConfig`, so it is public API. Also frozen:

- **Config keys.** Each constant keeps its string, for example `PhelConfig::SRC_DIRS` is `'src-dirs'`.
- **Builder methods.** New `with*()` methods may appear. Existing ones keep their name, parameter type, and return a new instance.
- **The `.phel/` layout.** Tools can rely on `.phel/cache/` and `.phel/repl-history`. `PHEL_DIR` moves the whole tree.

See [Configuration](/documentation/reference/configuration/).

## Command-line interface

Read this if a program runs `phel`: an editor plugin, a CI step, a script. What a program reads is covered. What a person reads is not.

Covered, changed only in a major:

| Surface | What is frozen |
|---|---|
| Commands | every command `phel list` shows, except the ones under "Not covered" below, with their aliases |
| Options | every option a command's `--help` lists, with the values it documents |
| Exit codes | `0` nothing to fail on, `1` found something, `2` could not run as asked, with the problem on stderr |
| Positions | every line and column a command prints counts from 1 |
| Paths | a JSON field naming a file is absolute; `--format=github` prints `file=` relative to the working directory |
| Machine output | in `--format=json`, `--format=github`, `test --reporter=tap` and `test --reporter=junit-xml`, stdout carries that output and nothing else |
| Fields | the field names and meaning of every machine output; fields are only added, so ignore the ones you do not know |
| `--format` | picks the output format on every command, and `text` is the default |
| `api-daemon` | its methods, including `version`, which reports the running Phel version (so does the LSP) |
| nREPL | Phel's own ops `reload` and `run-tests`, and the `.nrepl-port` file |
| Environment variables | the documented ones; a switch reads `1/true/yes/on` and `0/false/no/off`, and any other value exits 2 naming the variable |
| `config --format=json` | the values after environment variables, with every config key |

Not covered:

- Human text: messages, tables, colours, help wording, progress lines and every `text` format. Match on an error code or a field, never on a message.
- Command prefixes such as `phel li` for `phel lint`. A new command can make one ambiguous.
- Symfony's `help`, `list` and `completion`, and the Gacela commands (`cache:warm`, `debug:*`, `list:modules`, `profile:report`, `validate:config`).
- Hidden worker commands, `profile --format=json`, the `bench` baseline file, and the OPcache switches.
- What a program run by `run`, `eval`, `test` or `repl` prints, and the exit code it picks.

The full list, the output shapes and the exit codes per command: [the CLI reference](https://github.com/phel-lang/phel-lang/blob/main/docs/cli-reference.md). Why the line falls there: [ADR 0022](https://github.com/phel-lang/phel-lang/blob/main/docs/adr/0022-the-cli-machine-surface-is-under-semver.md).

## Not covered

These are outside semver, before and after `1.0`:

- The exact PHP the compiler emits. Only its behaviour is promised.
- The wording and layout of diagnostics. Match on [error codes](/documentation/reference/errors/), not messages.
- The `.phel/cache/` file format. A version bump invalidates it.
- Anything under `tests/`, `tools/`, `build/` or `resources/` in the Phel repository.
- The nREPL and LSP wire protocols beyond the upstream specifications and the surfaces listed under [Command-line interface](#command-line-interface).

## Upgrading

- [Upgrading](/documentation/reference/upgrading/) lists the breaking changes for each release back to 0.37.
- From 0.49 or later straight to 1.0: follow [the 1.0 upgrade guide](https://github.com/phel-lang/phel-lang/blob/main/docs/migration/upgrade-0.49-to-1.0.md).
- A behaviour differs from Clojure? Check [the divergence catalogue](https://github.com/phel-lang/phel-lang/blob/main/docs/spec/clojure-divergences.md). Listed differences are deliberate. Report anything else as an issue.
- The full policy and its reasoning: [docs/stability.md](https://github.com/phel-lang/phel-lang/blob/main/docs/stability.md).
