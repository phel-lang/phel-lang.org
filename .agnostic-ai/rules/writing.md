---
name: writing
description: Voice and style for docs, practice and blog content.
globs: content/**
---

# Writing

Applies to `content/**`.

- Plain English for a reader whose second language is English. Short sentences, one idea each, second person.
- No em or en dashes. Use commas, colons, parentheses or a hyphen.
- No filler adverbs (just, really, basically, actually, simply), no hype, no exclamation marks, no emoji.
- Every Phel sample runs on the Phel version in `composer.lock`. Show results as `; => value` and verify each one on the runtime.
- Never use deprecated syntax: key-first map destructuring (`{:name n}`, write `{n :name}`), backslash namespaces (`app\core`), `php/new`, `php/->`, `php/::`.
- Blog posts are in the maintainer's voice. A post about a release leads with the capability and its samples; the version goes in the footer.
