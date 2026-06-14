+++
title = "Error Reference"
weight = 3
description = "Every Phel compiler error code (PHEL001-PHEL310), what it means, and how to fix it."
+++

Phel compiler errors are tagged with a stable code like `[PHEL001]`. The code
survives wording changes, so it is the reliable thing to search for. An error
prints as the code, a message, the source location, a snippet of the offending
code, and often a hint:

```text
[PHEL001] Cannot resolve symbol 'maap'. Did you mean 'map'?
in src/app.phel:12
```

Codes are grouped by the compiler stage that raises them.

## Analyzer errors

Raised while analyzing forms: undefined names, wrong arity, type and binding problems. The bulk of day-to-day errors.

### PHEL001 : Undefined symbol

A symbol could not be resolved to a definition in the current scope.

**Common cause:** A typo, a missing `(:require ...)` for the namespace the symbol lives in, an alias that does not match, or using a binding before it is defined.

**Fix:** Check the spelling, require the namespace (e.g. `(:require phel\string :as str)` for `str/...`), or move the definition above its first use. The error message suggests near matches.

### PHEL002 : Arity error

A function was called with the wrong number of arguments.

**Common cause:** The call site passes more or fewer arguments than any of the function's arities accept.

**Fix:** Match the call to a declared arity. For variadic functions use `& rest` in the parameter vector.

### PHEL003 : Type error

A form received a value of the wrong type.

**Common cause:** For example attaching metadata that is not a String, Keyword or Map, or passing a non-collection where a collection is required.

**Fix:** Pass the type the form expects. The message names the value it got.

### PHEL004 : Def not allowed

`def` was used somewhere it is not allowed.

**Common cause:** `def` defines a top-level var, so it cannot appear nested inside a function body or another expression.

**Fix:** Move the `def` to the top level of the namespace. For function-local values use `let`.

### PHEL005 : Macro expansion error

A macro threw while expanding.

**Common cause:** The macro received arguments it did not expect, or its own body raised during expansion.

**Fix:** Check the arguments at the call site and inspect the expansion with `(macroexpand '(your-form ...))`.

**Learn more:** [Macros](/documentation/language/macros/).

### PHEL006 : Inline expansion error

An inline-expanded function failed to expand.

**Common cause:** A function declared with an `:inline` implementation produced an invalid expansion for the given call.

**Fix:** Call the function within the shape its `:inline` definition supports, or report it upstream if it is a core function.

### PHEL007 : Invalid special form

A special form was written in an invalid shape.

**Common cause:** A core form such as `if`, `let`, `fn`, `do` or `quote` was given the wrong structure (missing or extra parts).

**Fix:** Match the form's grammar, e.g. `(if test then else?)`, `(let [bindings*] body*)`.

### PHEL008 : Binding error

A binding vector is invalid.

**Common cause:** An odd number of binding forms in `let`/`loop`, or a binding target that cannot be destructured.

**Fix:** Provide an even number of `name value` pairs and use valid destructuring targets (symbols, vectors, maps).

**Learn more:** [Destructuring](/documentation/language/destructuring/), [Global and local bindings](/documentation/language/global-and-local-bindings/).

### PHEL009 : Interface error

An interface or protocol definition (or its implementation) is invalid.

**Common cause:** A malformed `definterface`/`defprotocol`, or trying to implement a `defprotocol` inline in `defstruct` (only `definterface` can be implemented inline).

**Fix:** Use `definterface` for inline implementation, or `defprotocol` plus `extend-type` per struct.

**Learn more:** [Interfaces](/documentation/language/interfaces/).

### PHEL010 : Recur error

`recur` was used incorrectly.

**Common cause:** `recur` appeared outside a `loop`/`fn` tail position, or with an argument count that does not match the recursion point.

**Fix:** Use `recur` only in tail position, with as many arguments as the enclosing `loop`/`fn` binds.

**Learn more:** [Functions and recursion](/documentation/language/functions-and-recursion/).

### PHEL011 : Not callable

A value that is not a function was called.

**Common cause:** A non-callable value (a number, string, keyword used wrongly) sits in the head position of a list, often an extra pair of parentheses.

**Fix:** Remove the stray parentheses, or put a function in the call position.

## Parser errors

Raised while parsing tokens into forms, almost always an unbalanced or unterminated bracket.

### PHEL100 : Unterminated list

A list was not closed.

**Common cause:** A missing `)`.

**Fix:** Balance the parentheses. Editor rainbow-brackets or `phel format` help spot it.

### PHEL101 : Unterminated vector

A vector was not closed.

**Common cause:** A missing `]`.

**Fix:** Balance the brackets.

### PHEL102 : Unterminated map

A map was not closed.

**Common cause:** A missing `}`, or an odd number of key/value forms.

**Fix:** Close the brace and ensure every key has a value.

### PHEL103 : Unterminated table

A table literal was not closed.

**Common cause:** A missing closing brace on a `@{ ... }` table literal.

**Fix:** Close the table literal.

### PHEL110 : Unexpected token

A token appeared where the parser did not expect one.

**Common cause:** A stray closing bracket, or a reader macro applied to nothing.

**Fix:** Remove or complete the offending token.

### PHEL120 : Parser error

A general parser error.

**Common cause:** The token stream could not be assembled into valid forms for a reason not covered by a more specific code.

**Fix:** Check the indicated location for malformed structure.

## Reader errors

Raised while reading quote / quasiquote forms.

### PHEL200 : Invalid quote

A `quote` form is malformed.

**Common cause:** `quote` was given the wrong number of arguments.

**Fix:** Use `(quote x)` or the `'x` shorthand with a single form.

### PHEL201 : Invalid unquote

An unquote (`~`) is invalid.

**Common cause:** `~` was used outside a quasiquote (`` ` ``) or with a wrong argument shape.

**Fix:** Only use `~` inside a quasiquoted form.

### PHEL202 : Invalid splice

A splicing unquote (`~@`) is invalid.

**Common cause:** `~@` was used outside a quasiquote, or in a position where a sequence cannot be spliced.

**Fix:** Use `~@` inside a quasiquote, splicing into a list or vector.

### PHEL210 : Reader error

A general reader error.

**Common cause:** A reader macro could not be read for a reason not covered by a more specific code.

**Fix:** Check the quote/quasiquote forms at the indicated location.

## Lexer errors

Raised while turning source text into tokens: invalid characters or unterminated strings.

### PHEL300 : Invalid character

An invalid character was found in the source.

**Common cause:** A character that is not valid Phel syntax at that position.

**Fix:** Remove or escape the character.

### PHEL301 : Unterminated string

A string was not closed.

**Common cause:** A missing closing `"`, sometimes from an unescaped quote inside the string.

**Fix:** Close the string and escape interior quotes as `\"`.

### PHEL310 : Lexer error

A general lexer error.

**Common cause:** The source could not be tokenized for a reason not covered by a more specific code.

**Fix:** Check the indicated location for stray or invalid characters.
