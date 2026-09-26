+++
title = "Phel 0.51: Only Once"
aliases = [ "/blog/phel-0-51-only-once" ]
description = "Mutation testing with phel mutate, a test runner that runs only what changed, Clojure-style binding-first map destructuring, and compiles that emit each form once for an 18-24% speedup. What changed in 0.51 and how to upgrade."
date = 2026-09-05
+++

Phel 0.51, *Only Once*, is named after a compiler fix. The compiler used to emit every form twice: once to evaluate it, once to write the output. Now it emits once and reuses the result. Compiles are 18 to 24% faster. The same idea runs through the release: less repeated work, in the compiler and in your test loop. One edge case breaks, and the compiler warns you about it.

## Upgrade

```bash
composer require phel-lang/phel-lang:^0.51
./vendor/bin/phel cache:clear        # or: rm -rf .phel/cache
```

Always clear the cache after upgrading: compiled PHP from an earlier install can reference renamed internals and fail to load otherwise.

## The one break: bare all-caps names

A bare all-caps PHP name now reads by where it stands, not by what happens to be loaded. As a value, `PHP_EOL` is always the constant. As a call target, `(WP_CLI/log "x")` is always the class. Before, the compiler checked whether a class of that name could be loaded. The same source could compile two ways on two machines.

This breaks one case: a bare all-caps *class* name used as a value. `(def driver-class PDO)` now reads a constant named `PDO`, which doesn't exist. The compiler warns at the site, then PHP fails:

```text
warning: PDO reads as the global constant PDO here, but it is also a PHP class (at src/app/db.phel:3). Spell the class as \PDO, import it with (:use PDO), or write PDO/class; php/PDO is the constant, explicitly.
Undefined constant "PDO"
  at src/app/db.phel:3
```

Use one of those three spellings. The reasoning is in [ADR 0016](https://github.com/phel-lang/phel-lang/blob/v0.51.0/docs/adr/0016-a-bare-all-caps-host-name-reads-by-position.md).

## Tests that test your tests

A green suite says your tests pass. It doesn't say they would catch a bug. `phel mutate` checks that. It changes your code in small ways, one at a time, and runs your tests against each change. A change the tests don't notice is a *survivor*: a bug your suite would let through.

Take a shipping rule and a test that looks fine:

```phel
(ns app.price)

(defn shipping [total]
  (if (> total 100) 0 5))
```

<!-- phel-test: skip -->
```phel
(ns app.price-test
  (:require phel.test :refer [deftest is])
  (:require app.price :refer [shipping]))

(deftest big-orders-ship-free
  (is (= 0 (shipping 200)))
  (is (= 5 (shipping 20))))
```

```bash
./vendor/bin/phel mutate
```

```text
Mutants: 6  Killed: 4  Survived: 2  Not covered: 0  Errors: 0  Timeouts: 0
MSI: 66.7%  Covered MSI: 66.7%  Baseline: 0.0s
Coverage: none (every mutant ran the whole suite)

Survived:
  src/app/price.phel:4 [compare] (> total 100) -> (>= total 100)
  src/app/price.phel:4 [literal-num] (> total 100) -> (> total 101)
```

Both survivors point at the same gap. Nothing tests the line at 100. Add `(shipping 100)` and `(shipping 101)` to the suite and all six mutants die.

In CI, `--min-msi=80` fails the run below that score. `--changed` mutates only the files you touched, and `--parallel=auto` spreads the work across CPUs.

> Coverage tells you a line ran. Mutation testing tells you a test would notice.

## A test runner that skips the rest

`phel test --changed` runs only the tests affected by your changes: the tests of the changed namespaces and of everything that requires them. Pass a ref to compare against a branch: `--changed=main`.

Tests can opt in or out with metadata:

```phel
(ns app.report-test
  (:require phel.test :refer [deftest is skip!]))

(deftest ^{:skip "slow, runs nightly"} full-report
  (is (= 1 1)))

(deftest ^:focus only-this-one
  (is (= 5 (+ 2 3))))

(deftest needs-redis
  (when-not (php/extension_loaded "redis")
    (skip! "no redis"))
  (is (= 1 1)))
```

`^:focus` narrows the run to the focused tests. `--fail-on-focus` makes that run exit non-zero, and it is always on when the `CI` variable is set. A forgotten focus can't turn your pipeline green. `--reporter=github` turns failures into annotations on the pull request.

## Destructuring the Clojure way

Map destructuring now puts the binding first, as Clojure does:

```phel
(def order {:id 7 :customer {:name "Ada" :city "Berlin"}})

(let [{id :id {name :name} :customer} order]
  [id name])
; => [7 "Ada"]
```

The old key-first form still works in 0.51. With deprecation warnings on, it tells you what to write:

```text
deprecated: Key-first map destructuring pair {:id id} at src/app/order.phel:4 is deprecated; write it binding-first, as {id :id}. The key-first order will be removed in a future release.
```

`to-php-array` is deprecated too. Use `to-array`.

## Smaller things worth knowing

- `phel.html` reads CSS-style ids and classes from the tag: `[:button#save.btn "Save"]` renders `<button class="btn" id="save">Save</button>`.
- `phel format --exclude='src/*_data.phel'` leaves generated files alone. The `format-exclude` config key does the same.
- `withCacheEnvVars(['APP_MODE'])` makes an environment variable part of the compiled-code cache key. Use it when a macro reads `(php/getenv ...)` at expansion time.
- `^:redef` keeps a function replaceable with `with-redefs`, even where `-O2` would inline its calls.

## Faster everywhere

Beyond the compiler, `phel.core` loads 20% faster, because a definition's metadata is now built on first read. `mapv` and `filterv` are 3.5x and 2.8x faster. Grown maps read up to 5.5x faster, and `update-in` is 32% faster.

For the full list, see the [0.51 release notes](/releases/0-51-only-once/) and the [Upgrading notes](/documentation/upgrading/#0-51). Upgrade, then run `phel mutate` once. Read what survives.
