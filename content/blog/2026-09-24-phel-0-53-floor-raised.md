+++
title = "Phel 0.53: Floor Raised"
aliases = [ "/blog/phel-0-53-floor-raised" ]
description = "PHP 8.5 is the new minimum, octal escapes above \\377 are a compile error, short type tags like ^map and ^vector, warnings for dropped collection results, 2-4x faster assoc and get-in, and phel bench --ab. What changed in 0.53 and how to upgrade."
date = 2026-09-24
+++

Phel 0.53, *Floor Raised*, moves the PHP minimum to 8.5. That is the change to plan for. The rest makes code faster and catches two quiet bugs: a string escape that wrapped to the wrong byte, and a collection update whose result went nowhere.

## Upgrade

```bash
composer require phel-lang/phel-lang:^0.53
./vendor/bin/phel cache:clear        # or: rm -rf .phel/cache
```

Always clear the cache after upgrading: compiled PHP from an earlier install can reference renamed internals and fail to load otherwise.

## PHP 8.5 is the minimum

Phel 0.52 ran on PHP 8.4. Phel 0.53 needs 8.5. On 8.4, Composer refuses the install.

Check your runtime before you bump:

```bash
php -v
```

Then raise the PHP version in your CI matrix, your Docker image and your deploy target. Your Phel code does not change.

Why now? Phel is close to `1.x`. Once `1.x` starts, raising the floor needs a major version. Moving it before that keeps 1.0 on a current PHP.

## Octal escapes stop wrapping

An octal escape above `\377` used to wrap around without a word. `"\400"` compiled to a NUL byte. Now it fails at compile time:

<!-- phel-test: skip -->
```phel
(prn "\400")
```

```text
Octal escape sequence out of range: \400 is above \377.
```

`\377` is the largest byte. For a character past it, use a Unicode escape:

```phel
(php/bin2hex "\377")
; => "ff"

"Ā"
; => "Ā"
```

## Short type tags

Tagging a parameter as a Phel map used to mean the full PHP interface name. Now it takes one word: `^map`, `^vector`, `^set`, `^list`, `^keyword`, `^symbol` or `^atom`. `?map` accepts `nil` too.

```phel
(defn order-total [^map order]
  (* (:qty order) (:price order)))

(order-total {:qty 3 :price 10})
; => 30
```

The tag does two jobs. `(:qty order)` compiles to a direct map lookup instead of a generic call. And passing a vector fails at the call with `[PHEL402]`, not three functions later.

## Dropped results now warn

Phel collections are immutable. `assoc` returns a new map and leaves the old one alone. So this function does nothing useful:

```phel
(defn make-admin [^map user]
  (assoc user :role :admin)
  user)

(make-admin {:name "Ada"})
; => {:name "Ada"}
```

In 0.53 it also prints a PHP warning: the return value of `PersistentArrayMap::put()` should be used, since the receiver is unchanged. The fix is to return the new map:

```phel
(defn make-admin [^map user]
  (assoc user :role :admin))

(make-admin {:name "Ada"})
; => {:name "Ada", :role :admin}
```

The warning fires when the call compiles to a direct method, as it does on a tagged local. It comes from PHP, so it names the compiled file, not your `.phel` line.

## Faster paths, and a way to prove it

Literal `assoc`, `get-in`, `assoc-in` and `update-in` calls now compile to direct code. Same results, less work: `assoc` with several pairs is 3.4x to 3.7x faster, `get-in` 2.9x to 4.5x, `assoc-in` and `update-in` 1.9x to 2.8x.

To measure your own change against a git ref, `phel bench` gained an A/B mode:

```bash
phel bench --ab=main --pairs=5
```

It runs the working tree and the ref in turns, in a temporary worktree. Each row shows the mean delta, or `noise` when the pairs disagree on the sign.

For the full list, see the [0.53 release notes](/releases/0-53-floor-raised/) and the [0.53 upgrade notes](/documentation/upgrading/#0-53). Upgrade PHP first. Then Phel. Then clear the cache.
