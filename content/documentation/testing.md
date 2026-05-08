+++
title = "Testing"
weight = 70
+++

Built-in unit testing framework.

{% php_note() %}
Lighter than PHPUnit. No classes/methods:

```php
// PHPUnit - class-based tests
class MyTest extends TestCase {
    public function testAddition() {
        $this->assertEquals(4, 2 + 2);
    }
}

// Phel - function-based tests
(deftest my-test
  (is (= 4 (+ 2 2))))
```

No class boilerplate, simpler for functional code.
{% end %}

## Assertions

The `is` macro defines assertions:

```phel
(is (= 4 (+ 2 2)) "my test description")
(is (true? (or true false)) "another test")
```

First arg must take one of the forms below. Second arg is an optional description string.

```phel
(predicate expected actual)
;; (is (= 4 (+ 2 2)))
```

Tests `actual` against `expected` via `predicate`.

```phel
(predicate value)
;; (is (true? (or true false)))
```

Tests `value` satisfies `predicate`.

```phel
(not (predicate expected actual))
;; (is (not (= 4 (+ 2 3))))
```

Tests `actual` does **not** match `expected` via `predicate`.

```phel
(not (predicate value))
;; (is (not (true? (and true false))))
```

Tests `value` does **not** satisfy `predicate`.

```phel
(thrown? exception-type body)
;; (is (thrown? \Exception (throw (php/new \Exception "test"))))
```

Tests `body` throws `exception-type`.

```phel
(thrown-with-msg? exception-type msg body)
;; (is (thrown? \Exception "test" (throw (php/new \Exception "test"))))
```

Tests `body` throws `exception-type` with message `msg`.

```phel
(output? expected body) ; (output? "hello" (php/echo "hello"))
```

Tests `body` prints `expected` to output.

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

// Phel - inline exception assertions
(is (thrown? \Exception (throw (php/new \Exception "test"))))
(is (thrown-with-msg? \Exception "test" (throw (php/new \Exception "test"))))
```

The `output?` assertion is similar to PHPUnit's output buffering:
```php
// PHPUnit
$this->expectOutputString("hello");
echo "hello";

// Phel
(is (output? "hello" (php/echo "hello")))
```
{% end %}

## Defining tests

`deftest` defines a test. Like a no-arg function.

```phel
(ns my-namespace.tests
  (:require phel.test :refer [deftest is]))

(deftest my-test
  (is (= 4 (+ 2 2))))
```

## Running tests

Run via `./vendor/bin/phel test`. Picks up tests recursively from [setTestDirs](/documentation/configuration/#testdirs), defaults to `tests/`.

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
  (:require phel.test :refer [deftest is test-ns]))

; Run all tests in a namespace
(test-ns 'my-app.tests)
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
(test-ns 'my-app.tests)
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
(def err-mock (mock-throwing (php/new \RuntimeException "fail")))
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

`phel.test.gen` provides generators, `sample`, `quick-check`, `defspec` with seedable PRNG.

```phel
(ns my-app.tests
  (:require phel.test :refer [deftest is defspec])
  (:require phel.test.gen :as gen))

(defspec reverse-roundtrip
  [xs (gen/vector (gen/int))]
  (is (= xs (reverse (reverse xs)))))
```

Failing cases shrink via `phel.test.shrink` (rose tree). On failure, `:defspec-failed` event emits `:shrunk-args`, `:original-args`, `:shrink-steps`, `:seed`. Opt out with `^:no-shrink` or `:shrink? false`.
