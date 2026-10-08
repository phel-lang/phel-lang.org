# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Inferred from the site's copy, not interviewed:

- PHP developers curious about functional programming and Lisp, evaluating whether Phel fits a side project, CLI app or internal tool.
- Clojure and Lisp developers who want Lisp ideas on a PHP host.
- Existing Phel users coming back for docs, the API reference, practice exercises and release notes.

## Product Purpose

phel-lang.org is the home of the Phel language: a Lisp dialect that compiles to PHP. The site convinces newcomers to try it (browser REPL, Docker, Composer, PHAR), then teaches and supports them (documentation, API reference, practice journey, blog, releases). Success is a visitor who runs their first Phel form.

## Positioning

A real Lisp (macros, persistent data structures, REPL-driven development) that deploys like any PHP app: Composer, PHP-FPM, FrankenPHP, RoadRunner, shared hosting. The compiler emits plain PHP and calls PHP functions directly.

## Capabilities and Constraints

- Zola 0.23.6 static site, Tera 2 templates and components; CSS concatenated and built with Tailwind 4; deployed over FTP from `master`.
- Light and dark themes via a `.dark` class on `<html>`; both must work.
- Every ```phel block in `content/` runs against the real runtime; homepage snippets must stay valid.
- Requires PHP 8.5+. 1.x, with a published stability policy.
- The browser REPL downloads about 80 MB; desktop recommended.

## Brand Commitments

- Name: Phel. Logo: the outline elephant (`static/images/logo_phel.svg`), purple `#512da8`.
- Voice: plain words, concrete verbs, short sentences, no hype, no exclamation marks, no em dashes.

## Evidence on Hand

- Real REPL transcript captured from `phel repl` (`static/animated-repl-data.json`).
- Real `phel compile` output for the homepage `slugify` example.
- DOOM written in Phel: video and playable build (`static/images/phel-doom.jpg`).
- No testimonials, adoption numbers or benchmarks exist; never invent them.

## Product Principles

- Prove with real code and real output, not claims.
- One step from curiosity to a running REPL.
- Honest about maturity: the 1.x stability policy, PHP 8.5+, the trade-offs of persistent data.
