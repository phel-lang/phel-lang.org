---
name: Phel homepage
description: A quiet editorial page about a Lisp that compiles to PHP, where every claim sits beside the code that proves it.
colors:
  canvas: "#f4f2f8"
  panel: "#fbfaff"
  panel-bar: "#efecf6"
  line: "rgb(56 36 110 / 0.14)"
  line-strong: "rgb(56 36 110 / 0.3)"
  grid: "rgb(81 45 168 / 0.05)"
  text: "#1b1726"
  copy: "#3d3850"
  muted: "#5f5973"
  phel-purple: "#512da8"
  phel-purple-deep: "#3f1f8f"
  on-accent: "#ffffff"
  canvas-dark: "#14121b"
  panel-dark: "#1b1824"
  panel-bar-dark: "#221e2e"
  line-dark: "rgb(214 204 250 / 0.12)"
  line-strong-dark: "rgb(214 204 250 / 0.26)"
  grid-dark: "rgb(214 204 250 / 0.035)"
  text-dark: "#ece8f5"
  copy-dark: "#c4bfd2"
  muted-dark: "#9a94ab"
  lilac: "#bfa4ff"
  lilac-light: "#d4c2ff"
  on-accent-dark: "#16121f"
typography:
  display:
    fontFamily: "'Brygada 1918', 'Iowan Old Style', 'Palatino Linotype', Georgia, serif"
    fontSize: "clamp(2.6rem, 6vw, 4.6rem)"
    fontWeight: 450
    lineHeight: 1.02
    letterSpacing: "-0.012em"
  headline:
    fontFamily: "'Brygada 1918', 'Iowan Old Style', 'Palatino Linotype', Georgia, serif"
    fontSize: "clamp(2rem, 3.4vw, 2.75rem)"
    fontWeight: 450
    lineHeight: 1.08
    letterSpacing: "-0.012em"
  title:
    fontFamily: "'Brygada 1918', 'Iowan Old Style', 'Palatino Linotype', Georgia, serif"
    fontSize: "1.2rem"
    fontWeight: 400
    lineHeight: 1.6
  lede:
    fontFamily: "'Brygada 1918', 'Iowan Old Style', 'Palatino Linotype', Georgia, serif"
    fontSize: "clamp(1.125rem, 1.6vw, 1.3rem)"
    fontWeight: 400
    lineHeight: 1.5
  body:
    fontFamily: "'Brygada 1918', 'Iowan Old Style', 'Palatino Linotype', Georgia, serif"
    fontSize: "1.125rem"
    fontWeight: 400
    lineHeight: 1.6
  label:
    fontFamily: "'Departure Mono', ui-monospace, 'SFMono-Regular', Menlo, monospace"
    fontSize: "0.75rem"
    fontWeight: 400
    letterSpacing: "0.08em"
  code:
    fontFamily: "'Fira Code', 'JetBrains Mono', 'Cascadia Code', 'Consolas', 'Monaco', monospace"
    fontSize: "0.875rem"
    lineHeight: 1.65
rounded:
  none: "0px"
spacing:
  grid: "20px"
  tick: "9px"
  gap-sm: "0.75rem"
  gap-md: "1.25rem"
  gap-lg: "2rem"
  pane-pad: "1.1rem 1.25rem 1.25rem"
  beat-gap: "clamp(5rem, 14vh, 9rem)"
components:
  button-sx-primary:
    backgroundColor: "{colors.phel-purple}"
    textColor: "{colors.on-accent}"
    typography: "{typography.label}"
    rounded: "{rounded.none}"
    padding: "0.55rem 1.1rem"
    height: "2.75rem"
  button-sx-primary-hover:
    backgroundColor: "{colors.phel-purple-deep}"
    textColor: "{colors.on-accent}"
  button-sx:
    backgroundColor: "transparent"
    textColor: "{colors.text}"
    typography: "{typography.label}"
    rounded: "{rounded.none}"
    padding: "0.55rem 1.1rem"
    height: "2.75rem"
  frame:
    backgroundColor: "{colors.panel}"
    rounded: "{rounded.none}"
  pane-bar:
    backgroundColor: "{colors.panel-bar}"
    textColor: "{colors.muted}"
    typography: "{typography.label}"
    padding: "0.7rem 1rem"
  console-tab:
    backgroundColor: "transparent"
    textColor: "{colors.muted}"
    typography: "{typography.label}"
    padding: "0 1rem"
    height: "2.75rem"
  console-tab-active:
    backgroundColor: "{colors.panel}"
    textColor: "{colors.text}"
  faq-question:
    textColor: "{colors.text}"
    typography: "{typography.title}"
    padding: "1.15rem 0.25rem"
---

# Design System: Phel homepage

## Overview

**Creative North Star: "The Well-Set Book"**

This system covers the whole site. Shared values live in `css/theme.css`: the lavender `#f4f2f8` and violet ink `#14121b` grounds, Phel purple as the accent, and square radius tokens; the fonts load from `css/base.css`. The homepage (`content/_index.md`, `templates/index.html`, `templates/components/hero_repl.html`, `css/components/homepage.css`) is the full expression, scoped under `.ph` with its own `--ph-*` tokens. Other pages take a lighter form by section. Docs, practice and the API keep a sans body so they stay quick to scan, and set page titles and content section headings in Brygada italic. Blog posts set their body in the roman serif. Blog and release cards use mono labels and paren buttons. Smaller headings and interface titles stay in the sans everywhere.

The homepage reads like a quietly typeset book about a language. An italic serif carries the headlines, a roman serif carries the prose, and a pixel mono speaks only for commands, labels and the s-expression buttons. The page has one accent, Phel purple, and otherwise stays in near-neutral violet paper and ink. Proof sits beside every claim: a real REPL transcript, real `phel compile` output, runnable snippets and DOOM. Each story beat pins its prose on the right while the taller proof scrolls past on the left.

Surfaces are hairline panels with a small tick at each corner, laid on a faint 20px grid that fades out from the centre of the hero and the closing panel. Corners are square everywhere. Motion is small and purposeful: the elephant mark draws its outline on load, the last REPL form replays as typing, and parens on the buttons step outward on hover. The page refuses the SaaS stack of centered hero, card grid and repeated centered sections.

**Key Characteristics:**
- Whole site: the full world on the homepage; shared palette, headings and square shapes everywhere else.
- One accent (Phel purple, lilac in dark mode) on a violet-tinted neutral canvas.
- Brygada 1918 italic for headings, roman for prose; Departure Mono for labels and commands; Fira Code for code.
- Square corners, hairline borders, corner ticks, a faint 20px grid.
- Claims pinned beside the code that proves them.

## Colors

A violet-tinted paper and ink palette with a single purple voice; dark mode swaps to violet-ink canvas and a lighter lilac accent under the `.dark` class.

### Primary
- **Phel Purple** (light `phel-purple`, dark `lilac`): the brand color from the logo. Used for the primary button fill, the headline accent tail, the logo stroke, the REPL prompt and cursor, list bullets, the FAQ plus sign, focus outlines, active tab underline and link underlines.
- **Deep Purple / Pale Lilac** (`phel-purple-deep`, `lilac-light`): hover state of the primary button only.
- **On Accent** (`on-accent`, `on-accent-dark`): text on the purple fill. Parens inside the primary button mix it 62% toward the accent so they read softer than the label.

### Neutral
- **Lavender Paper / Violet Ink** (`canvas`, `canvas-dark`): page background behind the homepage, also tinting the sticky header.
- **Panel** (`panel`, `panel-dark`): the inside of every framed pane, console and REPL; inline code chips.
- **Panel Bar** (`panel-bar`, `panel-bar-dark`): the title strip of panes, the console bar and the REPL header.
- **Ink** (`text`, `text-dark`): headings, strong text, link text, active labels.
- **Copy** (`copy`, `copy-dark`): running prose.
- **Muted** (`muted`, `muted-dark`): captions, asides, the facts line, inactive tabs, REPL results.
- **Hairline** (`line`, `line-strong` and dark pairs): borders and dividers; the strong variant draws corner ticks and the secondary button edge.
- **Grid** (`grid`, `grid-dark`): the 20px background grid, always masked by a radial fade.

### Named Rules
**The One Voice Rule.** Purple is the only hue on the page apart from syntax highlighting and the DOOM still. No second accent, no gradients of color.

**The Scope Rule.** Homepage-only tokens and components stay under `.ph` as `--ph-*` properties; values every page shares go in `css/theme.css`. Off the homepage and blog, body text stays in the sans.

## Typography

**Display Font:** Brygada 1918 italic (with Iowan Old Style, Palatino Linotype, Georgia)
**Body Font:** Brygada 1918 roman (same fallbacks)
**Label Font:** Departure Mono (with ui-monospace, SFMono-Regular, Menlo)
**Code Font:** the site's `--font-mono` (Fira Code first)

**Character:** A bookish serif pairing where italic headlines feel set rather than shouted, against a pixel mono that marks anything a machine reads. Fonts are self-hosted from `static/fonts/` (OFL) and preloaded from `templates/index.html`.

### Hierarchy
- **Display** (italic 450, `clamp(2.6rem, 6vw, 4.6rem)`, 1.02, max 14ch): the hero headline only; the second line is set in the accent.
- **Headline** (italic 450, `clamp(2rem, 3.4vw, 2.75rem)`, 1.08): story beat titles, the FAQ title; the close title goes to `clamp(2rem, 4vw, 3rem)`.
- **Title** (roman 400, 1.2rem): FAQ questions.
- **Lede** (roman 400, `clamp(1.125rem, 1.6vw, 1.3rem)`, 1.5, max 34rem): hero introduction.
- **Body** (roman 400, 1.125rem, 1.6; 1.0625rem under 560px): prose, beat text capped at 32rem.
- **Label** (Departure Mono 400, 0.72 to 0.9rem, uppercase with 0.08em tracking in bars and tabs, sentence case with 0.02em in buttons, notes and facts): pane bars, console tabs, the note under the buttons, the facts line, button labels.
- **Code** (Fira Code, 0.875rem, 1.65): code blocks and the REPL; inline code at 0.8em in a square hairline chip.

### Named Rules
**The Machine Voice Rule.** Departure Mono is for what you type or what the machine labels. Never set prose or headings in it.

**The Italic Heading Rule.** Every heading on the homepage is Brygada italic at weight 450, balanced. The one exception is the console title, which is a label.

## Layout

The homepage widens the one-column layout to 76rem. The hero is centered: mark, headline, lede, buttons, a note, the install console (max 44rem), a follow-up link and a centered mono facts line. After the hero, the story is a stack of beats with `clamp(5rem, 14vh, 9rem)` between them. Each beat is a two-column grid (`1.25fr` proof on the left, `1fr` prose on the right, gap `clamp(2.5rem, 5vw, 5rem)`); the prose column is sticky below the header so it stays in view while the taller proof scrolls. The FAQ is a single centered column (max 46rem) and the close is a framed centered panel.

At 900px and below, beats collapse to one column with prose first and stickiness off. At 560px and below, buttons stack full width, the console bar stacks its title above equal-width tabs, and the facts line stacks.

## Elevation & Depth

Mostly flat, with one soft drop under framed panels to lift them off the grid. Depth otherwise comes from the panel tone step (canvas, panel, panel bar) and hairlines.

### Shadow Vocabulary
- **Frame lift, light** (`box-shadow: 0 18px 40px -24px rgb(20 10 50 / 0.28)`): every framed pane, the console, the REPL, the close panel.
- **Frame lift, dark** (`box-shadow: 0 24px 48px -28px rgb(0 0 0 / 0.7)`): the same in dark mode.

### Named Rules
**The One Shadow Rule.** Only framed panels cast a shadow, and only the frame lift. Buttons, tabs, chips and the FAQ stay flat.

## Shapes

Square corners throughout (0 radius), including buttons, code chips, copy buttons, focus outlines and the play button. Frames are 1px hairline boxes with a 9px tick drawn at each corner in the strong line color, painted as background gradients. The FAQ toggle is a thin plus built from two 1.5px bars that rotates 45 degrees to a cross when open. List bullets are 5px accent squares.

## Components

### Buttons (s-expression)
Written as a Lisp form: the parens are the button's edges.
- **Shape:** square (0), 1px border, min height 2.75rem, padding 0.55rem 1.1rem, Departure Mono 0.9rem.
- **Primary:** purple fill, on-accent label, parens softened toward the fill. Hover darkens to Deep Purple.
- **Secondary:** transparent with a strong hairline, ink label, purple parens. Hover turns the border purple.
- **Hover (both):** parens slide 3px outward over 260ms on the house ease (`cubic-bezier(0.16, 1, 0.3, 1)`).
- **Focus:** 2px accent outline, 3px offset, square.

### Frame
The container for every proof: panel background, hairline border, corner ticks, frame lift. Used by panes, the install console, the REPL and the close panel.

### Pane
A frame with a label bar (panel bar tone, uppercase mono label in ink, optional sentence-case meta on the right such as "metadata trimmed") above an unstyled code block padded `1.1rem 1.25rem 1.25rem`. The copy button is a square 2.25rem hairline box.

### Install console
A frame whose bar holds the uppercase title and three tabs (Docker, Composer, PHAR). Tabs are mono uppercase, divided by hairlines; the active tab takes the panel tone and a 2px inset accent underline. Each panel shows a muted serif caption then the command.

### REPL transcript
Server-rendered from a real `phel repl` session; the header drops the window dots and shows the "phel repl" label. Prompts in accent, results muted, a purple block cursor; the last form replays as typing.

### FAQ
Native `details` rows separated by hairlines, question in ink at 1.2rem with the accent plus on the right; hover turns the question purple.

### Signature: elephant mark
The logo drawn as three stroked paths (104px wide, 3px purple stroke) that draw themselves over 1.6s with 120ms staggers, only when reduced motion is not requested.

## Do's and Don'ts

### Do:
- **Do** keep homepage-only styles under `.ph` with `--ph-*` tokens, and put shared values in `css/theme.css` with a `.dark` override.
- **Do** put a real artifact beside each claim: a REPL transcript, compile output or a runnable snippet.
- **Do** frame proof in a pane with a mono label bar and corner ticks.
- **Do** write calls to action as s-expressions: `(Try it in your browser)`.
- **Do** wrap motion in `prefers-reduced-motion: no-preference`.

### Don't:
- **Don't** set docs, practice or API body text in the serif, or use Departure Mono beyond commands, labels and card metadata.
- **Don't** round corners or add a second accent hue.
- **Don't** set prose or headings in Departure Mono.
- **Don't** fall back to the SaaS stack of centered hero, feature card grid and repeated centered sections.
- **Don't** add testimonials, adoption numbers or benchmarks; none exist.
