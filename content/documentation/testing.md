+++
title = "Testing"
weight = 17
+++

Phel comes with an integrated unit testing framework.

{% php_note() %}
Unlike PHPUnit which uses classes and methods, Phel's testing is more lightweight:

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

Phel's approach is simpler for functional code and doesn't require class boilerplate.
{% end %}

{% clojure_note() %}
Phel's testing framework is modeled after `clojure.test`—same `is` macro, same `deftest` structure.
{% end %}

## Assertions

The core of the library is the `is` macro, which can be used to defined assertions.

```phel
(is (= 4 (+ 2 2)) "my test description")
(is (true? (or true false)) "my othe test")
```

The first argument of the `is` macro must be in one of the following forms. The second argument is an optional string to describe the test.

```phel
(predicate expected actual)
# Example: (is (= 4 (+ 2 2)))
```

This tests whether, according to `predicate`, the `actual` value is in fact what we `expected`.

```phel
(predicate value)
# Example: (is (true? (or true false)))
```
This tests whether the `value` satisfies the `predicate`.

```phel
(not (predicate expected actual))
# Example: (is (not (= 4 (+ 2 3))))
```

This tests whether, according to `predicate`, the `actual` value is **not** what we `expected`.

```phel
(not (predicate value))
# Example (is (not (true? (and true false))))
```
This tests whether the `value` does **not** satisfies the `predicate`.

```phel
(thrown? exception-type body)
# Example: (is (thrown? \Exception (throw (php/new \Exception "test"))))
```
This tests whether the execution of `body` throws an exception of type `exception-type`.

```phel
(thrown-with-msg? exception-type msg body)
# Example: (is (thrown? \Exception "test"  (throw (php/new \Exception "test"))))
```
This tests whether the execution of `body` throws an exception of type `exception-type` and that the exception has the message `msg`.

```phel
(output? expected body) # For example (output? "hello" (php/echo "hello"))
```
This tests whether the execution of `body` prints the `expected` text to the output stream.

{% php_note() %}
Exception testing in Phel is more concise than PHPUnit:

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

Test can be defined by using the `deftest` macro. This macro is like a function without arguments.

```phel
(ns my-namespace\tests
  (:require phel\test :refer [deftest is]))

(deftest my-test
  (is (= 4 (+ 2 2))))
```

## Running tests

Tests can be run using the `./vendor/bin/phel test` command. Tests are looked up recursively in all directories set by [setTestDirs](/documentation/configuration/#testdirs) configuration option which defaults to `tests/`.

Pass filenames as arguments to the `phel test` command to run tests in specified files only:

```bash
./vendor/bin/phel test tests/main.phel tests/utils.phel
```

To filter tests that should run by name, `--filter` command line argument can be used:

```bash
./vendor/bin/phel test tests/utils.phel --filter my-test-function
```

Test report can be set to more verbose TestDox format showing individual test names with `--testdox` flag. Output can also be suppressed with `--quiet` flag to only include errors or silenced fully with `--silent` flag.

See more options available by running `./vendor/bin/phel test --help`.

{% php_note() %}
Phel's test command is similar to PHPUnit:

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

Both support filtering, verbose output, and running specific test files.
{% end %}


If you want to run tests from Phel code, the `run-tests` function can be used. As arguments, it takes a map of options (that can be empty) and one or more namespaces that should be tested.

```phel
(run-tests {} 'my\ns\a 'my\ns\b)
```

## Mocking

Phel provides a built-in mocking framework in the `phel\mock` module for replacing functions with test doubles.

### Creating mocks

```phel
(ns my-app\tests
  (:require phel\test :refer [deftest is])
  (:require phel\mock :refer [mock mock-fn mock-returning mock-throwing
                               calls call-count called? called-with?
                               called-once? never-called? reset-mock!
                               with-mocks]))

# Fixed return value
(def my-mock (mock :ok))
(my-mock "any" "args")  # => :ok

# Custom behavior
(def double-mock (mock-fn |(* $ 2)))
(double-mock 5)  # => 10

# Consecutive return values
(def seq-mock (mock-returning [1 2 3]))
(seq-mock)  # => 1
(seq-mock)  # => 2
(seq-mock)  # => 3

# Mock that throws
(def err-mock (mock-throwing (php/new \RuntimeException "fail")))
```

### Inspecting calls

```phel
(def m (mock :result))
(m "a" "b")
(m "c")

(calls m)          # => [["a" "b"] ["c"]]
(call-count m)     # => 2
(called? m)        # => true
(called-with? m "a" "b")  # => true
(called-once? m)   # => false
(never-called? m)  # => false
```

### Replacing functions in tests

Use `with-mocks` to temporarily replace functions with mocks using dynamic binding. Mocks are automatically reset after the block:

```phel
(defn fetch-user [id]
  # ... makes HTTP call ...
  )

(deftest test-with-mock
  (with-mocks [fetch-user (mock {:id 1 :name "Alice"})]
    (is (= {:id 1 :name "Alice"} (fetch-user 42)))
    (is (called-once? fetch-user))))
```

{% php_note() %}
Phel's mocking is simpler than Mockery or PHPUnit mocks:

```php
// PHPUnit
$mock = $this->createMock(UserService::class);
$mock->method('find')->willReturn(['id' => 1]);

// Phel
(with-mocks [find-user (mock {:id 1})]
  (find-user 42))
```

No class structure needed — mock any function directly.
{% end %}
