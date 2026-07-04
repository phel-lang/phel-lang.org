+++
title = "Phel 0.47: Clear Signals"
aliases = [ "/blog/phel-0-47-clear-signals" ]
description = "LSP signature help for plain Phel calls, per-session REPL value history over nREPL, examples in (doc), structural test diffs, sharper error locations, and a ~30% faster startup. What changed in 0.47 and how to upgrade."
date = 2026-07-01
+++

Phel 0.47, *Clear Signals*, is about the tools telling you what actually happened. Your editor shows the shape of the call you are writing, test failures point straight at the byte that differs, and runtime errors name a `.phel` location instead of a compiled temp path. No language changes, no breaking changes: upgrade and keep going.

## Upgrade

```bash
composer require phel-lang/phel-lang:^0.47
./vendor/bin/phel cache:clear        # or: rm -rf .phel/cache
```

Always clear the cache after upgrading: compiled PHP from an earlier install can reference renamed internals and fail to load otherwise.

## Signature help while you type

The language server now provides signature help for plain Phel calls, not only `php/...` interop. Start typing `(map ` and the editor shows the arity, the parameter names, and the docstring inline, so you no longer break flow to look up argument order.

```phel
(map inc [1 2 3])
# => [2 3 4]
```

## A REPL that remembers

nREPL eval responses now carry per-session `*1`, `*2`, and `*3` value history. Calva and Conjure surface the last three results, so you can reach back for the previous value without recomputing it:

<!-- phel-test: skip -->
```phel
(+ 1 2)     # => 3
(* *1 10)   # => 30, reusing the last result
```

## Examples in (doc)

The REPL's `(doc sym)` now renders a function's `:example` under an `Example:` heading, turning the doc lookup into a usage hint instead of a bare signature.

<!-- phel-test: skip -->
```phel
(doc map)
```

## Test output that points at the mismatch

`phel test` now prints a `+`/`-`/`~` structural diff for any same-shape collection that differs, not just collections past a few entries. When a large map or vector fails an assertion, the diff points straight at what changed instead of dumping both sides. Scalars and mismatched shapes keep the summary line.

`phel compile` also prints the folded value to stderr when a form emits no PHP output (for example `(+ 1 2)` folds to `3`), so constant folding is visible rather than silent.

## Errors that name the source

Runtime errors from `phel run`, `test`, `build`, `profile`, and `export` now show the `.phel` location and stack trace for stdlib failures like `(/ 1 0)`, matching the REPL, rather than a compiled temp path. Invoking a non-function value points at the real line with a `hint:`, and "Did you mean" suggestions rank by relevance, so `prn` suggests `print`/`printf`/`println`.

## Faster, and scaffolded for speed

Startup is about 30% faster: `phel run`/`eval`/`repl` re-exec once into a warm child backed by a persistent OPcache file cache (opt out with `PHEL_NO_OPCACHE_REEXEC=1`), and `phel test --parallel` workers share that cache. The emitter also skips column bookkeeping when source maps are off. New projects scaffold `phel-config.php` at `->withOptimizationLevel(2)`; the runtime default stays `0`, so existing configs are untouched.

## Same language, steadier tools

Nothing here changes how your code compiles:

```phel
(defn greet [name]
  (str "Hello, " name "!"))

(greet "Phel")
# => "Hello, Phel!"
```

For the full list, see the [0.47 release notes](/releases/0-47-clear-signals/). Upgrade, clear the cache, and let the tooling keep pace with your REPL.
