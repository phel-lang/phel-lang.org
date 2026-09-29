+++
title = "Configuration"
weight = 3
description = "Every phel-config.php option with its default, the local override file, zero-config detection, and the environment variables Phel reads."
aliases = ["/documentation/configuration/"]
+++

This page lists every option in `phel-config.php`, with its default, so you can change how Phel finds, builds, caches and exports your code.

## Minimal config

Phel reads `phel-config.php` from the project root. It returns a `PhelConfig`. Most projects need only the factory:

```php
<?php
// phel-config.php
use Phel\Config\PhelConfig;
use Phel\Config\ProjectLayout;

return PhelConfig::forProject(ProjectLayout::Flat, 'my-app.main');
```

This sets `src/` and `tests/` as the source and test dirs, and `my-app.main` as the entry namespace for `phel build`. Chain `with*()` methods to change anything else. Each call returns a new config:

```php
return PhelConfig::forProject(ProjectLayout::Flat, 'my-app.main')
    ->withBuildDestDir('dist')
    ->withOptimizationLevel(2)
    ->withFormatExclude(['src/generated/*']);
```

Run `phel config` to see the merged result and where each value came from. See [CLI commands](/documentation/reference/cli-commands/#inspect-configuration).

## Project layouts

`forProject()` and `withLayout()` take a `ProjectLayout`. It sets the source, test, format and export dirs in one call, and overwrites any you set before it.

| Layout | Source | Tests |
|---|---|---|
| `ProjectLayout::Flat` (default) | `src/` | `tests/` |
| `ProjectLayout::Nested` | `src/phel/` | `tests/phel/` |
| `ProjectLayout::Root` | `.` | `.` |

Without a `phel-config.php`, Phel detects the layout: `Nested` if `src/phel/` or `tests/phel/` exists, otherwise `Flat`.

## Local overrides

An optional `phel-config-local.php` next to `phel-config.php` overrides its values. Use it for machine-specific settings. `phel init` adds it to `.gitignore`. It returns a `PhelConfig` or an array keyed by the config keys below:

```php
<?php
// phel-config-local.php
return ['optimization-level' => 0, 'warn-deprecations' => true];
```

## Full reference

Every method on `PhelConfig`, the key it sets (what `phel config` prints and what an array config uses), and its default.

### Source and test dirs

| Method | Key | Default | Purpose |
|---|---|---|---|
| `withLayout` | | `Flat` | Set source, test, format and export dirs from a `ProjectLayout` |
| `withSrcDirs` | `src-dirs` | `['src']` | Source roots for `run`, `test`, `build` and namespace lookup |
| `withTestDirs` | `test-dirs` | `['tests']` | Dirs `phel test` walks |
| `withVendorDir` | `vendor-dir` | `'vendor'` | Composer vendor dir, relative to the project root |
| `withAppModulePaths` | `app-module-paths` | `[]` (whole root) | Dirs Gacela scans for modules. Set it only if you define Gacela modules and `list:modules` or `cache:warm` fails on a file that cannot load alone |

Source, test and vendor dirs must be relative. `phel config` and `phel doctor` report absolute ones.

### Build

| Method | Key | Default | Purpose |
|---|---|---|---|
| `withMainPhelNamespace` | `out.main-phel-namespace` | none | Entry namespace for `phel build`. Without it, no entry PHP file is written |
| `withBuildDestDir` | `out.dir` | `'out'` | Output dir for `phel build` |
| `withMainPhpPath` | `out.main-php-path` | `<dest dir>/index.php` | Entry PHP file. A bare filename goes under the dest dir; `.php` is added if missing |
| `withIgnoreWhenBuilding` | `ignore-when-building` | `[]` | Skip files whose path contains one of these strings |
| `withNoCacheWhenBuilding` | `no-cache-when-building` | `[]` | Always recompile files whose output path contains one of these strings |
| `withOptimizationLevel` | `optimization-level` | `0` | `2` turns on `^:pure` inlining and self tail-call rewriting for `build`, `run` and `test`. The REPL stays at `0`. See [Performance](/documentation/guides/performance/#optimization-levels) |
| `withStripSymbolMeta` | `strip-symbol-meta` | `false` | `phel build` drops docstrings, arglists and source locations. Smaller, faster output, but `phel doc` and `(meta ...)` return `nil` on built defs. Production only |
| `withEnableAsserts` | `asserts-enabled` | `true` | When `false`, `(assert ...)` forms are removed from compiled code |
| `withBuildConfig` | `out` | | Replace the build config with a `PhelBuildConfig`, or patch it with a closure: `fn (PhelBuildConfig $b) => $b->withDestDir('dist')` |

### Export

`phel export` writes PHP wrapper classes for functions marked `{:export true}`. See [PHP Interop](/documentation/language/php-interop/#calling-phel-from-php).

| Method | Key | Default | Purpose |
|---|---|---|---|
| `withExportFromDirectories` | `export.from-directories` | `['src']` | Source dirs scanned |
| `withExportNamespacePrefix` | `export.namespace-prefix` | `'PhelGenerated'` | PHP namespace prefix of the wrappers |
| `withExportTargetDirectory` | `export.target-directory` | `'src/PhelGenerated'` | Output dir |
| `withExportConfig` | `export` | | Replace with a `PhelExportConfig`, or patch with a closure |

### Formatting

| Method | Key | Default | Purpose |
|---|---|---|---|
| `withFormatDirs` | `format-dirs` | `['src', 'tests']` | Dirs `phel format` rewrites |
| `withFormatExclude` | `format-exclude` | `[]` | Globs `phel format` skips. `*` spans dirs. Combined with `--exclude` |

### Caches and runtime state

| Method | Key | Default | Purpose |
|---|---|---|---|
| `withPhelDir` | `phel-dir` | `.phel` | Root for runtime state (cache, REPL history, error log). Move it out of a web root, e.g. `'/var/cache/phel'` |
| `withCacheDir` | `cache-dir` | `'.phel/cache'` | Namespace and compiled-code caches |
| `withEnableNamespaceCache` | `enable-namespace-cache` | `true` | Cache the file-to-namespace map so builds skip re-reading `(ns ...)` forms |
| `withEnableCompiledCodeCache` | `enable-compiled-code-cache` | `true` | Cache compiled PHP per file, keyed by source hash |
| `withEnableIntermediateCache` | `enable-intermediate-cache` | `false` | Experimental. Cache the read result per file, so warm rebuilds start at analysis |
| `withCacheEnvVars` | `cache-env-vars` | `[]` | Env vars that join the compiled-code cache key. Use it when a macro reads `(php/getenv ...)`. Values are hashed, never stored. Changing one recompiles the whole project |
| `withTempDir` | `temp-dir` | system temp + `/phel/tmp` | Short-lived compile output, such as `(load ...)` |
| `withKeepGeneratedTempFiles` | `keep-generated-temp-files` | `false` | Keep temp files for compiler debugging |
| `withErrorLogFile` | `error-log-file` | `'.phel/error.log'` | File that runtime and compile errors are appended to |

### Diagnostics

| Method | Key | Default | Purpose |
|---|---|---|---|
| `withWarnDeprecations` | `warn-deprecations` | `false` | Print deprecation notices to stderr. See [Stability](/documentation/reference/stability/#deprecations) |

## Environment variables

| Variable | Effect |
|---|---|
| `PHEL_DIR` | Overrides `withPhelDir` |
| `PHEL_CACHE_DIR` | Overrides `withCacheDir` |
| `PHEL_WARN_DEPRECATIONS=1` | Same as `--warn-deprecations` or `withWarnDeprecations(true)` |
| `PHEL_TEST_WORKERS` | Worker count for `phel test --parallel` and `phel mutate --parallel` |
| `NO_COLOR` | Turns off colored output |

{% callout(kind="note") %}
The old `setX()` setters were removed in 0.46. Use the `with*()` methods.
{% end %}
