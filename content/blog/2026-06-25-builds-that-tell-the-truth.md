+++
title = "Builds That Tell the Truth"
aliases = [ "/blog/phel-0-46-native-path" ]
description = "phel doctor now checks your config before a build does, a failed phel build exits non-zero, and --timing shows where compile time goes."
date = 2026-06-25
+++

You push a change. CI goes green. You deploy, and the app is broken, because the build printed an error and still exited `0`.

Or you open a project after a few weeks, run a command, and get a PHP stack trace. The cause was a typo in `phel-config.php`. The trace did not say so.

Both problems have the same root. The tools knew something was wrong and did not tell you in a way you could act on.

Now they do. `phel doctor` checks your config, `phel build` fails when it fails, and `phel build --timing` shows where the time goes.

## `phel doctor` checks your config before a build does

Here is a config with three mistakes. The test directory is absolute, `lib` does not exist, and there is no optimization level 7.

```php
<?php

declare(strict_types=1);

use Phel\Config\PhelConfig;

return (new PhelConfig())
    ->withSrcDirs(['src', 'lib'])
    ->withTestDirs(['/abs/tests'])
    ->withOptimizationLevel(7)
    ->withMainPhelNamespace('shop.main');
```

Run `phel doctor`:

```bash
$ phel doctor
...
Checking configuration:
 - FAIL Test directory '/abs/tests' should be relative, not absolute
 - TIP Source directory 'lib' does not exist
 - TIP Unknown optimization level 7; supported levels are 0, 1, 2
...
Your system does not meet all requirements.
```

Errors are `FAIL` and make `doctor` exit with `1`. Softer problems are `TIP` lines. They do not fail the run, but they tell you what to fix. `phel config` runs the same checks when it prints the effective configuration.

That exit code matters. Put `phel doctor` as the first step in CI and a bad config stops the pipeline before any build or test starts.

## A broken config file names itself

A config file that does not even load used to end in an uncaught exception. Now the error names the file, the PHP error, and the shape Phel expects:

```bash
$ phel doctor
Failed to load /app/shop/phel-config.php: syntax error, unexpected end of file, expecting ";"

A phel-config.php must `return` a PhelConfig instance (recommended) or a config array, for example:

    <?php
    declare(strict_types=1);
    use Phel\Config\PhelConfig;
    return new PhelConfig()->withSrcDirs(['src']);

    // or, equivalently, a plain array:
    // return ['src-dirs' => ['src']];
```

The exit code is `1`. No stack trace. You read one line and know where to look.

## A failed build fails the pipeline

This namespace has a typo. The parameter is `items`, the body says `itemz`:

<!-- phel-test: skip -->
```phel
(ns shop.main)

(defn total [items]
  (reduce + (map :price itemz)))
```

`phel build` stops at the unknown symbol and points at it:

```bash
$ phel build
[PHEL001] Cannot resolve symbol 'itemz'. Did you mean 'intern', 'Atom', or 'atom'?
in /app/shop/src/main.phel:4

3| (defn total [items]
4|   (reduce + (map :price itemz)))
                           ^^^^^

hint: 'itemz' is not defined. Check the spelling, or add (:require ...) for the namespace it lives in.
$ echo $?
1
```

Before, the same error printed and the command still exited `0`. The output folder was partial or empty, and CI moved on to deploy it.

**The exit code is part of the output.** A build that lies about it is worse than a slow build.

If your pipeline trusted the old behaviour, it may go red after the upgrade. That is the point. Fix the typo and it goes green for the right reason:

```phel
(defn total [items]
  (reduce + (map :price items)))

(total [{:price 3} {:price 4}])
; => 7
```

## `--timing` shows where compile time goes

A slow build is easy to feel and hard to explain. `phel build --timing` splits compile time into its five phases (lex, parse, read, analyze, emit) and adds them up across every namespace it compiled:

```bash
$ phel build --timing --no-cache
...
Compiled 36 files (0 reused from cache).

Compile-phase timing
====================
  lex             0.43 ms    0.0%
  parse         324.74 ms    7.0%
  read          191.87 ms    4.1%
  analyze      1244.72 ms   26.8%
  emit         2877.15 ms   62.0%
  total        4638.92 ms
  (65 namespaces compiled)
```

The timing only covers compilation. A warm cache skips most of the work, so pair it with `--no-cache` when you want the full picture. It also works together with `--report`.

Now a question like "why is the build slow" has a number next to it. Most of the time goes to emitting PHP here, not to reading source.

## Dependents recompile when a dependency changes

The incremental cache had a quieter lie. Take two namespaces, where `shop.main` uses a value from `shop.prices`:

<!-- phel-test: skip -->
```phel
(ns shop.prices)

(def tax 0.21)

(ns shop.main
  (:require shop.prices :refer [tax]))

(println (* 100 (+ 1 tax)))
```

You change `tax` to `0.10` and rebuild. The source of `shop.main` did not change, so the old cache reused its compiled output. The build passed with stale code.

Now a change in a required namespace cascades. Every namespace that depends on it recompiles too:

```bash
$ phel build
#0 | Namespace: shop.prices
...
#1 | Namespace: shop.main
...
Compiled 2 files (35 reused from cache).
```

One file changed. Two compiled. The rest came from the cache. You no longer reach for `cache:clear` to get a correct build.

## Also in Phel 0.46

- **Breaking:** the deprecated `setX()` setters and `useLayout()`, `useNestedLayout()`, `useFlatLayout()` are gone from `PhelConfig`, and `setX()` is gone from `PhelBuildConfig` and `PhelExportConfig`. Use the `with*()` methods.
- `phel init` scaffolds `phel-config.php` with `declare(strict_types=1);`.
- PHP interop compiles to direct PHP expressions instead of closure wrappers.
- Type-tagged core calls like `inc` on an `^int` local, or `count` on a string, compile to native PHP instead of runtime dispatch.
- The REPL boots with only `phel.core` and loads the rest on demand, about 34% faster.
- An opt-in intermediate compile cache, `withEnableIntermediateCache()`, skips the front half of the pipeline on warm rebuilds.
- Hashing large persistent collections no longer throws, and keywords compare by value, so lookups against unserialized maps work.
- `phel lsp` stays alive while the editor is idle, and the outline lists definitions from unsaved edits.

Run `phel doctor` before your next build. Let the tools say what is wrong, then believe them when they say it is fine.

Shipped in [Phel 0.46](/releases/0-46-native-path/). Upgrade notes: [0.46](/documentation/reference/upgrading/#0-46).
