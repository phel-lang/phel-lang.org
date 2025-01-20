+++
title = "Testing"
weight = 17
+++

Phel comes with an integrated unit testing framework.

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


If you want to run tests from Phel code, the `run-tests` function can be used. As arguments, it takes a map of options (that can be empty) and one or more namespaces that should be tested.

```phel
(run-tests {} 'my\ns\a 'my\ns\b)
```
