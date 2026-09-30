+++
title = "Test Your Tests with phel mutate"
aliases = [ "/blog/phel-0-51-only-once" ]
description = "phel mutate plants small bugs in your code and checks that your tests catch each one. The ones that survive show you exactly which case your suite never checks."
date = 2026-09-05
+++

Your suite is green. Coverage says every line ran. Then a bug ships on the one input nobody tried.

Coverage told the truth. The line ran. But no test checked what it returned at that input. A test that runs a line and a test that would notice it break are not the same test.

`phel mutate` measures the second one. It changes your code in small ways, one change at a time, and runs your tests against each change. If the tests still pass, you found a hole.

> Coverage tells you a line ran. Mutation testing tells you a test would notice.

## A mutant is a bug you plant on purpose

Each small change is a *mutant*. `phel mutate` turns `>` into `>=`, `100` into `101`, a branch into its opposite, a return value into `nil`. It runs your suite once per mutant.

- A test fails: the mutant is **killed**. Good. Your tests noticed.
- Every test passes: the mutant **survived**. That is a bug your suite would let through.

The score is the share of mutants killed. Phel calls it the MSI, the mutation score indicator.

## Survivors point at the case you never tested

Take a shipping rule. Orders over 100 ship free:

```phel
(ns app.price)

(defn shipping [total]
  (if (> total 100) 0 5))
```

And a test that looks fine. It checks a big order and a small one:

<!-- phel-test: skip -->
```phel
(ns app.price-test
  (:require phel.test :refer [deftest is])
  (:require app.price :refer [shipping]))

(deftest big-orders-ship-free
  (is (= 0 (shipping 200)))
  (is (= 5 (shipping 20))))
```

Coverage for `shipping` is 100%. Now run the mutants:

```bash
./vendor/bin/phel mutate
```

```text
Mutants: 6  Killed: 4  Survived: 2  Not covered: 0  Errors: 0  Timeouts: 0
MSI: 66.7%  Covered MSI: 66.7%  Baseline: 0.0s
Coverage: none (every mutant ran the whole suite)

Survived:
  /home/ada/shop/src/app/price.phel:4 [compare] (> total 100) -> (>= total 100)
  /home/ada/shop/src/app/price.phel:4 [literal-num] (> total 100) -> (> total 101)
```

{% <callout kind="warning"> %}
Every mutant survives and the MSI reads 0%? Your `phel-config.php` probably sets `->withOptimizationLevel(2)`, which `phel init` writes by default. At that level `phel mutate` reports every mutant as survived, whatever your tests check. Levels 0 and 1 give the real result, so lower it while you run `phel mutate`. The fix is tracked in [#3396](https://github.com/phel-lang/phel-lang/issues/3396).
{% </callout> %}

Read the two survivors. One moves the line to "100 or more". The other moves it to "over 101". Your tests pass with either, because nothing checks an order of exactly 100 or 101.

That is the gap. Is an order of 100 free or not? The code says no. No test says anything.

Pin the edge:

<!-- phel-test: skip -->
```phel
(deftest the-line-sits-at-100
  (is (= 5 (shipping 100)))
  (is (= 0 (shipping 101))))
```

Run it again:

```text
Mutants: 6  Killed: 6  Survived: 0  Not covered: 0  Errors: 0  Timeouts: 0
MSI: 100.0%  Covered MSI: 100.0%  Baseline: 0.0s
Coverage: none (every mutant ran the whole suite)
```

All six dead. The survivor list was a to-do list, one line per missing case.

## Eleven mutators, and you can pick them

`phel mutate` ships with eleven mutators: `arith`, `compare`, `equality`, `logic`, `cond-branch`, `literal-bool`, `literal-num`, `literal-str`, `seq-op`, `return-nil` and `body-drop`. Each survivor names the one that made it, in brackets.

Pass paths to mutate one file, and `--only` to run a few mutators:

```bash
./vendor/bin/phel mutate src/app/price.phel
./vendor/bin/phel mutate --only=compare,literal-num
```

Start with `compare` and `literal-num` on the code that handles money, limits and dates. Those are the places where an off-by-one costs you.

## Make it a gate in CI

`--min-msi` fails the run when the score drops below a percentage. On the first suite above:

```bash
./vendor/bin/phel mutate --min-msi=80
```

```text
MSI 66.7% (covered 66.7%) is below the required MSI 80.0%.
```

Mutation testing is slow by nature: one suite run per mutant. Two flags keep it fast enough for a pull request. `--changed` mutates only the files you touched: your uncommitted changes, or the diff against the default branch when the tree is clean. `--parallel=auto` runs one worker per CPU, up to eight.

```bash
./vendor/bin/phel mutate --changed --parallel=auto --min-msi=80
```

Set the bar where your suite is today. Raise it when the survivors are gone.

## Run only the tests your change touches

The same release made the plain test loop shorter. `phel test --changed` runs the tests of the namespaces you changed and of everything that requires them. `--changed=main` compares against a branch.

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

## Also in Phel 0.51

- **Breaking:** a bare all-caps PHP name reads by its position. As a value, `PDO` is a constant. Write `\PDO`, `(:use PDO)` or `PDO/class` for the class. The compiler warns at each site.
- Deprecated: key-first map destructuring. Write `{id :id}`, binding first, as Clojure does.
- Deprecated: `to-php-array`. Use `to-array`.
- The compiler emits each form once instead of twice: compiles are 18 to 24% faster.
- `phel.core` loads 20% faster. `mapv` is 3.5x faster, `filterv` 2.8x, and `update-in` 32%.
- `phel.html` reads ids and classes from the tag: `[:button#save.btn "Save"]`.
- `phel format --exclude` leaves generated files alone.
- `withCacheEnvVars` makes an environment variable part of the compiled-code cache key.
- `^:redef` keeps a function replaceable with `with-redefs`, even where `-O2` would inline it.

Run `phel mutate` once on the code you trust most. Read what survives.

Shipped in [Phel 0.51](/releases/0-51-only-once/). Upgrade notes: [0.51](/documentation/reference/upgrading/#0-51).
