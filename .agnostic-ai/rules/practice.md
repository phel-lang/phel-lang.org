---
name: practice
description: Structure of the practice modules and their exercise components.
scope: content/practice
---

# Practice

- Modules go from zero Lisp to real programs. A module only uses concepts from itself and earlier modules (by `weight`).
- Front matter: `title`, `weight`, `description`, `[extra] stage` (`Foundations`, `Functional core`, `Real world` or `Projects`), `goals`, `read_first`, `recap`.
- An exercise is `{% <question difficulty="..." kind="..."> %}...{% </question> %}`, an optional `{% <hint> %}...{% </hint> %}`, then `{% <solution> %}...{% </solution> %}`. `kind` is one of predict, fill, write, fix, refactor, build.
- `practice-page.html` numbers the exercises in page order: the label, the `#exercise-N` anchor and `data-exercise-index`. Never number them by hand.
- Every solution block runs standalone. Question blocks with blanks, bugs or undefined calls get `<!-- phel-test: skip -->`.
- Progress is stored per page slug and exercise index in the browser. Renaming a module slug resets its progress for readers.
