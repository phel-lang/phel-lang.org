---
name: Phel site
description: A plain, readable site for a Lisp that compiles to PHP, with code samples doing most of the talking.
colors:
  background: "#ffffff"
  background-alt: "#f8fafc"
  text: "#0f172a"
  text-secondary: "#475569"
  border: "#e2e8f0"
  phel-purple: "#512da8"
  background-dark: "#212830"
  text-dark: "#f1f5f9"
  accent-dark: "#bfa4ff"
typography:
  body: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif"
  code: "'Fira Code', 'JetBrains Mono', 'Cascadia Code', 'Consolas', 'Monaco', monospace"
---

# Design System: Phel site

The source of truth is `css/theme.css` (tokens) and `css/components/` (one file per area). This note only names the choices.

## Look

- Sans body and headings in the system font stack. Fira Code for code.
- Light mode: white pages, light grey (`#f8fafc`) for alternating sections, slate text, Phel purple for links and the primary button.
- Dark mode: slate background (`#212830`), light text, lilac accent.
- Rounded cards, buttons and code blocks, with soft shadows.

## Homepage

Sections in order, defined in `content/_index.md` and styled in `css/components/homepage.css`:

1. Hero: logo, title, lede, three actions and the facts list on the left, the animated REPL window on the right.
2. Phel in, PHP out: the compile showcase, two code panes side by side.
3. Phel by example: four tabs (Functional, PHP Interop, Macros, Tests) on a grey section.
4. Why Phel?: three positioning cards.
5. Try it in 30 seconds: install tabs (Docker, Shell, Composer, PHAR) on a grey section.
6. Common questions: an accordion.
7. Final call to action on a grey section.
