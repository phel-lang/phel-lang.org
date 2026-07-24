+++
title = "Agent Setup"
weight = 49
description = "Wire an AI coding agent (Claude Code, Cursor, Copilot, Codex, Gemini, Aider) to Phel with one command: phel agent-install."
aliases = ["/documentation/agent-setup", "/documentation/ai-setup"]
+++

Set up your AI coding agent to write correct Phel. One command installs a per-tool skill file plus a shared docs tree into your project, so the agent knows Phel's syntax, idioms, and gotchas without crawling the web.

> **Two kinds of "AI" on this site.** This page is about **coding Phel *with* an agent**. If instead you want to **build AI features *in* Phel** (LLM chat, embeddings, tool use), see the [AI Module](/documentation/guides/ai/) and the [ai API](/documentation/reference/api/ai/).

## One-command setup

Install Phel as a dev dependency, then let it detect the agents already in your project:

```bash
composer require --dev phel-lang/phel-lang
vendor/bin/phel agent-install --auto
```

`--auto` looks for signals like `.claude/`, `.cursor/`, `AGENTS.md`, or `.github/copilot-instructions.md` and installs only for the tools you actually use. Prefer to be explicit? Name a platform, or install everything:

```bash
vendor/bin/phel agent-install claude     # just Claude Code
vendor/bin/phel agent-install --all      # every supported platform
```

Existing files are backed up to `*.pre-phel.bak` first, unless you pass `--force`.

## What each platform gets

The skill file lands where each tool looks for it:

| Platform | File installed |
|----------|----------------|
| Claude Code | `.claude/skills/phel-lang/SKILL.md` |
| Cursor | `.cursor/rules/phel.mdc` |
| Codex | `AGENTS.md` |
| GitHub Copilot | `.github/copilot-instructions.md` |
| Gemini | `GEMINI.md` |
| Aider | `CONVENTIONS.md` |

## The shared docs tree

Alongside the skill file, `agent-install` copies an `.agents/` directory into your project root (skip it with `--no-docs`):

- `.agents/RULES.md` : the hard rules, modern-feature list, and CLI cheatsheet.
- `.agents/quick-syntax.md` : a one-screen syntax reference.
- `.agents/index.md` : an intent-to-recipe map.
- `.agents/tasks/` : focused recipes (HTTP apps, CLI tools, tests, macros, debugging errors, pattern matching, schema validation, and more).
- `.agents/examples/` : small runnable sample projects (add with `--with-examples`).

The per-tool skill file tells the agent to load these in order, so it reads the rules and gotchas before writing a line.

## Useful flags

| Flag | Effect |
|------|--------|
| `--auto` | Install only for agents detected in the project. |
| `--all` | Install for every supported platform. |
| `--with-examples` | Include the `.agents/examples/` sample projects. |
| `--no-docs` | Install only the skill file, skip the `.agents/` tree. |
| `--dry-run` | Print what would be written, change nothing. |
| `--force` | Overwrite without creating `.pre-phel.bak` backups. |
| `--uninstall` | Remove installed skill files, restoring any backup. |

## No CLI? Fetch the reference directly

For agents or scripts that cannot run the Phel CLI, the same knowledge is on the web as plain text:

- `curl https://phel-lang.org/agentic-coding.md` : the single-page [Agentic Coding](/documentation/reference/agentic-coding/) reference, no HTML chrome.
- `curl https://phel-lang.org/llms.txt` : a link index of the whole documentation for LLM consumers.

## Where to go next

- [Agentic Coding](/documentation/reference/agentic-coding/) : the reference an agent should load, with a truncation-safe rules table.
- [Cheat Sheet](/documentation/reference/cheat-sheet/) : the filterable core surface.
- [CLI Commands](/documentation/tooling/cli-commands/) : every `phel` subcommand.
