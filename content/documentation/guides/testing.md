+++
title = "Testing"
weight = 10
description = "Write tests with deftest, is, mocks, and property-based specs, then run them with phel test."
aliases = ["/documentation/testing/"]
+++

This page shows you how to write tests with `phel.test`, run them with `phel test`, replace functions with mocks, and check properties against random input. Tests are plain functions, with no classes or setup.

## Write and run a test

```phel
(ns my-app.math-test
  (:require phel.test :refer [deftest is]))

(deftest addition-works
  (is (= 4 (+ 2 2))))

(deftest string-concat
  (is (= "hello world" (str "hello" " " "world")))
  (is (not (= "" (str "a" "b")))))
```

`deftest` defines a test. A test can hold any number of `is` assertions, and it passes when all of them pass. Run every test:

```bash
vendor/bin/phel test
```

```text
Discovering tests...
Loading 36 namespace(s)...
...

Passed: 3
Failed: 0
Error: 0
Total: 3
Time: 00:00.922, Memory: 52.00 MB
```

Each dot is one passing assertion. The totals count assertions, not tests. `phel test` looks for tests in the folders set by [`withTestDirs`](/documentation/reference/configuration/), `tests/` by default.

A test for your own code requires the namespace under test:

<!-- phel-test: skip -->
```phel
(ns my-app.cart-test
  (:require phel.test :refer [deftest is])
  (:require my-app.cart :refer [add-item total]))

(deftest add-item-increases-total
  (let [cart (add-item [] {:price 10 :qty 2})]
    (is (= 20 (total cart)))
    (is (= 1 (count cart)))))

(deftest rejects-negative-price
  (is (thrown? Exception (add-item [] {:price -5 :qty 1}))))
```

## Assertions

`is` takes any expression that should be truthy, and an optional message shown on failure:

```phel
(ns my-app.is-test
  (:require phel.test :refer [deftest is]))

(deftest assertions
  (is (= 4 (+ 2 2)))
  (is (= 4 (+ 2 2)) "2 + 2 should be 4")
  (is (nil? (get {} :missing))))
```

Three special forms inside `is` check exceptions and output:

```phel
(ns my-app.special-test
  (:require phel.test :refer [deftest is]))

(deftest special-assertions
  (is (thrown? Exception
        (throw (new Exception "test"))))
  (is (thrown-with-msg? Exception "test"
        (throw (new Exception "test"))))
  (is (output? "hello" (print "hello"))))
```

| Form | Passes when |
|---|---|
| `(thrown? Class body)` | `body` throws an instance of `Class` |
| `(thrown-with-msg? Class msg body)` | `body` throws `Class` with message `msg` |
| `(output? expected body)` | `body` prints exactly `expected` to stdout |

{% php_note() %}
Each PHPUnit assertion maps to one `is` form:

| PHPUnit | Phel |
|---|---|
| `$this->assertEquals(4, 2 + 2)` | `(is (= 4 (+ 2 2)))` |
| `$this->expectException(Exception::class)` | `(is (thrown? Exception ...))` |
| `$this->expectExceptionMessage("test")` | `(is (thrown-with-msg? Exception "test" ...))` |
| `$this->expectOutputString("hello")` | `(is (output? "hello" ...))` |
{% end %}

### Reading a failure

A failed `=` names the test and its location, then shows a diff. Collections get one line per entry, with `-` for the expected value and `+` for the actual one:

<!-- phel-test: skip -->
```phel
(deftest vector-diff
  (is (= [:a 1 :b 2 :c 3] [:a 1 :b 99 :c 3])))
```

```text
FAIL vector-diff (diff_test.phel:5)
          Form: (= [:a 1 :b 2 :c 3] [:a 1 :b 99 :c 3])
  evaluated to: [:a 1 :b 99 :c 3]
    but is not: = to [:a 1 :b 2 :c 3]
  Diff:
      [0] :a
      [1] 1
      [2] :b
    - [3] 2
    + [3] 99
      [4] :c
      [5] 3
```

Strings get a caret under the first mismatch:

```text
FAIL string-diff (diff_test.phel:8)
          Form: (= "hello" "hallo")
  evaluated to: "hallo"
    but is not: = to "hello"
  String diff (first mismatch at index 1):
    expected: "hello"
    actual:   "hallo"
                ^
```

## Choose which tests run

Pass files to run only those, and `--filter` to match test names (a regex):

```bash
vendor/bin/phel test tests/main.phel tests/utils.phel
vendor/bin/phel test --filter 'user.*login'
```

Tag tests with metadata to include or exclude them as a group:

<!-- phel-test: skip -->
```phel
(deftest ^:integration full-signup-flow
  ...)

(deftest ^{:tags [:integration :slow]} heavy-job
  ...)
```

| Flag | Effect |
|---|---|
| `--filter <regex>` | Only tests whose name matches |
| `--include=<tag>` / `--exclude=<tag>` | Only, or all but, tests with that tag. Skipped tests emit a `:skipped` event |
| `--ns='my-app.http.*'` | Only namespaces matching the glob |
| `--last-failed` | Only the tests that failed last run (read from `.phel/last-failed.txt`) |
| `--fail-fast` | Stop at the first failure |
| `--list` | Print the discovered tests without running them |
| `--repeat=N` | Run each test N times, to stress a flaky test |
| `--random-order` | Random order with a random seed. Add `--seed=42` to reproduce a run |
| `--seed=<int>` | Fix the seed for the default order |
| `--slowest=N` | Print the N slowest tests after the summary |

`--last-failed --repeat=20` is a quick way to hammer the tests that just failed. `vendor/bin/phel test --help` and [CLI commands](/documentation/reference/cli-commands/#test-your-phel-logic) list every flag.

{% php_note() %}
`vendor/bin/phpunit tests/MainTest.php --filter testMyFunction` becomes `vendor/bin/phel test tests/main.phel --filter my-test-function`.
{% end %}

## Watch, parallel, and coverage

Rerun the selected tests on every change to a `.phel` file or `phel-config.php` in the source and test folders. Press `Ctrl+C` to stop:

```bash
vendor/bin/phel test --watch --ns='my-app.users.*'
```

Run namespaces across worker processes to speed up a large suite:

```bash
vendor/bin/phel test --parallel=auto   # detect CPUs, at most 8 workers
vendor/bin/phel test --parallel=4      # fixed worker count
vendor/bin/phel test --parallel=max    # every core the kernel reports
```

Parallel mode turns itself off for `--reporter=tap`, `--list`, and when a profiler hook is installed.

Measure line coverage of your `.phel` sources (vendor and core are excluded). It needs the `pcov` or `xdebug` extension and always runs serially:

```bash
vendor/bin/phel test --coverage                  # per-file and total %, as text
vendor/bin/phel test --coverage=clover \
  --coverage-output=coverage.xml                 # Clover XML for CI (Codecov and others)
```

## Reporters

Pick the output format with `--reporter=<name>`. Repeat the flag for several formats at once.

| Reporter | Output |
|---|---|
| `default` | Human-readable summary |
| `testdox` | Sentence-style names (also `--testdox`) |
| `dot` | One character per test |
| `tap` | Test Anything Protocol |
| `junit-xml` | JUnit XML. Use `--output=path` to write a file |

```bash
vendor/bin/phel test --reporter=tap --reporter=junit-xml --output=build/tests.xml
```

`--quiet` prints errors only, and `--silent` prints nothing. To write your own reporter, add a method to `phel.test/report`, a multimethod that dispatches on the event `:type`.

## Run tests from the REPL or code

`test-ns` runs one namespace from the REPL, without the full suite:

<!-- phel-test: skip -->
```phel
(ns my-app.tests
  (:require phel.repl :refer [test-ns]))

(test-ns "my-app.tests")
```

`run-tests` runs namespaces from code. It takes an options map (can be empty) and one or more namespaces:

```phel
(ns my-app.runner
  (:require phel.test :refer [run-tests]))

(run-tests {} 'my.ns.a 'my.ns.b)
```

To keep REPL runs apart, manage the test counters: `reset-stats` sets them to zero, `get-stats` reads them, and `restore-stats` puts back a saved copy:

<!-- phel-test: skip -->
```phel
(ns my-app.stats
  (:require phel.test :refer [reset-stats get-stats restore-stats])
  (:require phel.repl :refer [test-ns]))

(reset-stats)
(get-stats)
; => {:failed [], :skipped [], :counts {:failed 0, :error 0, :pass 0, :skipped 0, :total 0}}

(def saved (get-stats))
(test-ns "my-app.tests")
(restore-stats saved)
```

## Mocking

`phel.mock` creates test doubles and records their calls. `with-mocks` replaces functions for the length of a block and restores them afterwards:

```phel
(ns my-app.with-mocks-test
  (:require phel.test :refer [deftest is])
  (:require phel.mock :refer [mock with-mocks called-once?]))

(defn fetch-user [id]
  ;; ... makes an HTTP call ...
  )

(deftest test-with-mock
  (with-mocks [fetch-user (mock {:id 1 :name "Alice"})]
    (is (= {:id 1 :name "Alice"} (fetch-user 42)))
    (is (called-once? fetch-user))))
```

Four ways to build a mock:

```phel
(ns my-app.mocks
  (:require phel.mock :refer [mock mock-fn mock-returning mock-throwing]))

(def my-mock (mock :ok))            ; fixed return value
(my-mock "any" "args")              ; => :ok

(def double-mock (mock-fn #(* % 2))) ; custom behavior
(double-mock 5)                     ; => 10

(def seq-mock (mock-returning [1 2 3])) ; one value per call
(seq-mock)                          ; => 1
(seq-mock)                          ; => 2

(def err-mock (mock-throwing (new RuntimeException "fail")))
```

Inspect how a mock was called:

```phel
(ns my-app.mock-test
  (:require phel.mock :refer [mock calls call-count called?
                              called-with? called-once? never-called?]))

(def m (mock :result))
(m "a" "b")
(m "c")

(calls m)                 ; => [["a" "b"] ["c"]]
(call-count m)            ; => 2
(called? m)               ; => true
(called-with? m "a" "b")  ; => true
(called-once? m)          ; => false
(never-called? m)         ; => false
```

`reset-mock!` clears the recorded calls.

{% php_note() %}
No mock classes: `$this->createMock(UserService::class)->method('find')->willReturn(['id' => 1])` becomes `(with-mocks [find-user (mock {:id 1})] ...)`. You mock the function itself.
{% end %}

## Property-based testing

A property is a rule that must hold for any input. `defspec` generates random inputs, and when one fails, it shrinks the input to the smallest case that still fails.

```phel
(ns my-app.props
  (:require phel.test.gen :as gen :refer [defspec]))

;; Shape: (defspec name options args-gen property-fn)
(defspec reverse-roundtrip
  {}
  (gen/tuple (gen/vector-of gen/int))
  (fn [xs] (= xs (reverse (reverse xs)))))

(defspec sort-idempotent
  {}
  (gen/tuple (gen/vector-of gen/int))
  (fn [xs]
    (let [sorted (sort xs)]
      (= sorted (sort sorted)))))
```

A failure reports `:shrunk-args`, `:original-args`, `:shrink-steps`, and a `:seed` to reproduce the run. Turn shrinking off with `^:no-shrink` metadata or `:shrink? false`.

Generators include `gen/int`, `gen/string`, `gen/boolean`, `gen/keyword`, `gen/tuple`, `gen/vector-of`, `gen/map-of`, `gen/one-of`, `gen/frequency`, and `gen/such-that`. The [phel.test.gen API](/documentation/reference/api/test-gen/) lists them all, and the [phel.test API](/documentation/reference/api/test/) lists every assertion and helper.
