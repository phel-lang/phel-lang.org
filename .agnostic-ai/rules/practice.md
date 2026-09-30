---
name: practice
description: Structure of the practice modules and their exercise shortcodes.
globs: content/practice/**,templates/shortcodes/question.html,templates/shortcodes/hint.html,templates/shortcodes/solution.html,templates/practice-*.html
---

# Practice

- Modules go from zero Lisp to real programs. A module only uses concepts from itself and earlier modules (by `weight`).
- Front matter: `title`, `weight`, `description`, `[extra] stage` (`Foundations`, `Functional core`, `Real world` or `Projects`), `goals`, `read_first`, `recap`.
- An exercise is `{% question(difficulty=..., kind=...) %}`, an optional `{% hint() %}`, then `{% solution() %}`. `kind` is one of predict, fill, write, fix, refactor, build.
- Every solution block runs standalone. Question blocks with blanks, bugs or undefined calls get `<!-- phel-test: skip -->`.
- Progress is stored per page slug and exercise index in the browser. Renaming a module slug resets its progress for readers.
