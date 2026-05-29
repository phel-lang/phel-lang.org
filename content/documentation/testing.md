+++
title = "Testing"
weight = 70
+++

Built-in unit testing with no boilerplate. Define tests as functions, run them from the CLI.

## Quick start

```phel
(ns my-app.math-test
  (:require phel.test :refer [deftest is]))

(deftest addition-works
  (is (= 4 (+ 2 2))))

(deftest string-concat
  (is (= "hello world" (str "hello" " " "world")))
  (is (not (= "" (str "a" "b")))))
```

Run:

```bash
./vendor/bin/phel test
```

Output:

```
....
2 tests, 3 assertions, 0 failures.
```

{% php_note() %}
No class boilerplate. Tests are plain functions:

```php
// PHPUnit
class MathTest extends TestCase {
    public function testAddition() {
        $this->assertEquals(4, 2 + 2);
    }
}

// Phel
(deftest addition-works
  (is (= 4 (+ 2 2))))
```
{% end %}

## Assertions

The `is` macro defines assertions. Optional second argument is a description string shown on failure.

```phel
(is (= 4 (+ 2 2)))
(is (= 4 (+ 2 2)) "2 + 2 should be 4")
```

### Equality and predicates

```phel
(is (= expected actual))           ; equality
(is (true? value))                 ; predicate
(is (not (= "x" (str "a" "b"))))  ; negation
(is (nil? (get {} :missing)))      ; any predicate works
```

For collection equality, failures render a unified diff (added in 0.37) so missing/extra entries are obvious:

```
FAIL (= a b)
--- expected
+++ actual
 [:a 1
- :b 2
+ :b 99
  :c 3]
```

### Exceptions

```phel
;; assert throws
(is (thrown? Exception
      (throw (php/new Exception "test"))))

;; assert throws with specific message
(is (thrown-with-msg? Exception "test"
      (throw (php/new Exception "test"))))
```

### Output

```phel
;; assert what gets printed to stdout
(is (output? "hello" (print "hello")))
```

{% php_note() %}
Exception testing more concise than PHPUnit:

```php
// PHPUnit
$this->expectException(Exception::class);
throw new Exception("test");

// or
$this->expectException(Exception::class);
$this->expectExceptionMessage("test");
throw new Exception("test");

// Phel (inline exception assertions)
(is (thrown? Exception (throw (php/new Exception "test"))))
(is (thrown-with-msg? Exception "test" (throw (php/new Exception "test"))))
```

The `output?` assertion is similar to PHPUnit's output buffering:
```php
// PHPUnit
$this->expectOutputString("hello");
echo "hello";

// Phel
(is (output? "hello" (print "hello")))
```
{% end %}

## Defining tests

`deftest` defines a test. Each test can contain any number of `is` assertions. A test passes when all assertions pass.

```phel
(ns my-app.cart-test
  (:require phel.test :refer [deftest is])
  (:require my-app.cart :refer [add-item total]))

(deftest empty-cart-has-zero-total
  (is (= 0 (total []))))

(deftest add-item-increases-total
  (let [cart (add-item [] {:price 10 :qty 2})]
    (is (= 20 (total cart)))
    (is (= 1 (count cart)))))

(deftest rejects-negative-price
  (is (thrown? Exception (add-item [] {:price -5 :qty 1}))))
```

## Running tests

Run via `./vendor/bin/phel test`. Picks up tests recursively from [withTestDirs](/documentation/configuration/), defaults to `tests/`.

Pass filenames to run specific files:

```bash
./vendor/bin/phel test tests/main.phel tests/utils.phel
```

Filter by name with `--filter`:

```bash
./vendor/bin/phel test tests/utils.phel --filter my-test-function
```

Stop on first failure with `--fail-fast`:

```bash
./vendor/bin/phel test --fail-fast
```

Print discovered tests without running them (`--list`), re-run only failures from the previous run (`--last-failed`), or print the N slowest tests after the summary (`--slowest=N`):

```bash
./vendor/bin/phel test --list
./vendor/bin/phel test --last-failed
./vendor/bin/phel test --slowest=10
```

`--last-failed` persists failures to `.phel/last-failed.txt`.

`--testdox` for TestDox format. `--quiet` for errors only, `--silent` to silence fully.

Full options: `./vendor/bin/phel test --help`.

### Reporters

Pick format with `--reporter=<name>`. Repeatable for multiple formats.

| Reporter    | Description                                 |
|-------------|---------------------------------------------|
| `default`   | Human-readable summary (default)            |
| `testdox`   | Sentence-style names                        |
| `dot`       | One character per test                      |
| `tap`       | Test Anything Protocol                      |
| `junit-xml` | JUnit XML (use `--output=path` for a file)  |

```bash
./vendor/bin/phel test --reporter=dot
./vendor/bin/phel test --reporter=junit-xml --output=build/tests.xml
./vendor/bin/phel test --reporter=tap --reporter=junit-xml --output=build/tests.xml
```

`phel.test/report` is a multimethod dispatching on event `:type`. Register custom reporters from Phel.

### Selectors

Filter by tag, namespace glob, or regex:

```bash
./vendor/bin/phel test --include=integration
./vendor/bin/phel test --exclude=slow
./vendor/bin/phel test --ns='my-app.http.*'
./vendor/bin/phel test --filter 'user.*login'
```

Tag tests with metadata:

```phel
(deftest ^:integration full-signup-flow
  ...)

(deftest ^{:tags [:integration :slow]} heavy-job
  ...)
```

Skipped tests emit `:skipped` event.

### Repeat and random order

Re-run each test N times, randomize discovery order, and seed for reproducible runs:

```bash
./vendor/bin/phel test --repeat=10            # stress a flaky test
./vendor/bin/phel test --random-order         # random order, random seed
./vendor/bin/phel test --random-order --seed=42  # deterministic
```

`--seed=<int>` alone fixes the seed for the default deterministic order.

{% php_note() %}
Test command similar to PHPUnit:

```bash
# PHPUnit
./vendor/bin/phpunit tests/
./vendor/bin/phpunit tests/MainTest.php
./vendor/bin/phpunit --filter testMyFunction

# Phel
./vendor/bin/phel test
./vendor/bin/phel test tests/main.phel
./vendor/bin/phel test --filter my-test-function
```

Both support filtering, verbose output, specific files.
{% end %}


Run tests from Phel code with `run-tests`. Takes options map (can be empty) and one or more namespaces.

```phel
(run-tests {} 'my.ns.a 'my.ns.b)
```

### Interactive testing with `test-ns`

Run tests for a single namespace from the REPL:

```phel
(ns my-app.tests
  (:require phel.test :refer [deftest is])
  (:require phel.repl :refer [test-ns]))

; Run all tests in a namespace (pass namespace as a string)
(test-ns "my-app.tests")
```

Useful for REPL-driven feedback without running the full suite.

### Test statistics

Manage stats programmatically:

```phel
; Reset test counters to zero
(reset-stats)

; Get current test statistics (pass/fail/error counts)
(get-stats)

; Save and restore stats around a test run
(def saved (get-stats))
(test-ns "my-app.tests")
(restore-stats saved)
```

Useful in REPL to isolate or reset state between runs.

## Mocking

`phel.mock` module replaces functions with test doubles.

### Creating mocks

```phel
(ns my-app.tests
  (:require phel.test :refer [deftest is])
  (:require phel.mock :refer [mock mock-fn mock-returning mock-throwing
                               calls call-count called? called-with?
                               called-once? never-called? reset-mock!
                               with-mocks]))

;; Fixed return value
(def my-mock (mock :ok))
(my-mock "any" "args")  ; => :ok

;; Custom behavior
(def double-mock (mock-fn #(* % 2)))
(double-mock 5)  ; => 10

;; Consecutive return values
(def seq-mock (mock-returning [1 2 3]))
(seq-mock)  ; => 1
(seq-mock)  ; => 2
(seq-mock)  ; => 3

;; Mock that throws
(def err-mock (mock-throwing (php/new RuntimeException "fail")))
```

### Inspecting calls

```phel
(def m (mock :result))
(m "a" "b")
(m "c")

(calls m)          ; => [["a" "b"] ["c"]]
(call-count m)     ; => 2
(called? m)        ; => true
(called-with? m "a" "b")  ; => true
(called-once? m)   ; => false
(never-called? m)  ; => false
```

### Replacing functions in tests

`with-mocks` temporarily replaces functions via dynamic binding. Auto-resets after the block:

```phel
(defn fetch-user [id]
  ;; ... makes HTTP call ...
  )

(deftest test-with-mock
  (with-mocks [fetch-user (mock {:id 1 :name "Alice"})]
    (is (= {:id 1 :name "Alice"} (fetch-user 42)))
    (is (called-once? fetch-user))))
```

{% php_note() %}
Simpler than Mockery or PHPUnit mocks:

```php
// PHPUnit
$mock = $this->createMock(UserService::class);
$mock->method('find')->willReturn(['id' => 1]);

// Phel
(with-mocks [find-user (mock {:id 1})]
  (find-user 42))
```

No class structure. Mock any function directly.
{% end %}

## Property-based testing

Instead of writing specific examples, describe properties that must hold for *any* input. Phel generates random inputs and shrinks failures to the smallest reproducing case.

```phel
(ns my-app.tests
  (:require phel.test :refer [deftest is])
  (:require phel.test.gen :as gen :refer [defspec]))

;; Property: reversing twice gives back the original (holds for any vector of ints)
;; Shape: (defspec name options args-gen property-fn)
(defspec reverse-roundtrip
  {}
  (gen/tuple (gen/vector-of gen/int))
  (fn [xs] (= xs (reverse (reverse xs)))))

;; Property: sorting is idempotent (sort of a sorted list is still sorted)
(defspec sort-idempotent
  {}
  (gen/tuple (gen/vector-of gen/int))
  (fn [xs]
    (let [sorted (sort xs)]
      (= sorted (sort sorted)))))
```

On failure, Phel shrinks the input to the smallest case that still fails, then reports `:shrunk-args`, `:original-args`, `:shrink-steps`, and a `:seed` to reproduce the run.

Available generators: `gen/int`, `gen/string`, `gen/boolean`, `gen/keyword`, `gen/tuple`, `gen/vector-of`, `gen/map-of`, `gen/one-of`, `gen/frequency`, `gen/such-that`, and more in [phel.test.gen](/documentation/reference/api/test-gen/).

Opt out of shrinking with `^:no-shrink` metadata or `:shrink? false`.
