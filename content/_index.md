+++
title="The Phel Language"
+++

Phel is a functional programming language that compiles to PHP. It is a dialect of Lisp inspired by [Clojure](https://clojure.org/) and [Janet](https://janet-lang.org/).

<p align="center">
    <img src="/images/logo_phel.svg" width="350" alt="Phel logo"/>
</p>

## Community

Feel free to ask questions and join discussions on the [Phel Gitter channel](https://gitter.im/phel-lang/community).

## Features

* Built on PHP's ecosystem
* Good error reporting
* Persistent Datastructures (Lists, Vectors, Maps and Sets)
* Macros
* Recursive functions
* Powerful but simple Syntax
* REPL

## Why Phel?

Phel is a result of my [failed attempts to do functional programming in PHP](/blog/functional-programming-in-php). Basically I wanted:

* A LISP-inspired
* functional programming language
* that runs on cheap hosting providers
* and is easy to write and debug


## Example

The following example gives a short impression on how Phel looks like:

```phel
# Define a namespace
(ns my\example)

# Define a variable with name "my-name" and value "world"
(def my-name "world")

# Define a function with name "print-name" and one argument "your-name"
(defn print-name [your-name]
  (print "hello" your-name))

# Call the function
(print-name my-name)
```

## Try Phel

The quickest way to try out Phel is to run our REPL Docker container.

```bash
docker run -it --rm phellang/repl
```

![Try Phel animation](/try-phel.gif "Try Phel Animation")

## Getting started

Phel requires [PHP >=8.2](https://www.php.net/) and [Composer](https://getcomposer.org/). 

> Read the [Getting Started Guide](/documentation/getting-started) to create your first Phel program.

## Status of Development

Phel is approaching completion, but it’s not yet considered fully stable. We’re committed to continuously improving the language, which means breaking changes may still happen as we refine and evolve the project.

We actively welcome suggestions, improvements and bug reports —your feedback plays a vital role in helping us raise the quality of Phel. Whether through opening issues or submitting pull requests, every contribution helps the project grow stronger.
