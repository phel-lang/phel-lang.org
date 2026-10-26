+++
title = "AI Agents"
weight = 5
description = "Wire an AI coding agent (Claude Code, Cursor, Copilot, Codex, Gemini, Aider) to Phel with one command: phel agent-install."
aliases = ["/documentation/agent-setup", "/documentation/ai-setup", "/documentation/reference/agent-setup/"]
+++

After this page your AI coding agent writes correct Phel. One command installs a skill file for each agent and a shared docs tree into your project, so the agent knows Phel's syntax, idioms, and common mistakes.

To build AI features in Phel instead (LLM chat, embeddings, tool use), see the [AI Module](/documentation/libraries/ai/).

## One-command setup

```bash
composer require phel-lang/phel-lang
vendor/bin/phel agent-install --auto
```

`--auto` looks for `.claude/`, `.cursor/`, `AGENTS.md`, `.github/copilot-instructions.md`, and similar files, and installs only for the agents you use. To choose yourself, name a platform or install all of them:

```bash
vendor/bin/phel agent-install claude   # Claude Code only
vendor/bin/phel agent-install --all    # every platform
```

An existing file is backed up to `*.pre-phel.bak` before it is replaced. Run the command again after you update Phel: it writes only new or changed docs and keeps files you edited.

## Per platform

| Platform | Argument | File installed |
|----------|----------|----------------|
| Claude Code | `claude` | `.claude/skills/phel-lang/SKILL.md` |
| Cursor | `cursor` | `.cursor/rules/phel.mdc` |
| Codex | `codex` | `AGENTS.md` |
| GitHub Copilot | `copilot` | `.github/copilot-instructions.md` |
| Gemini | `gemini` | `GEMINI.md` |
| Aider | `aider` | `CONVENTIONS.md` |

Every platform also gets the `.agents/` docs tree in the project root. The skill file tells the agent to read it in this order:

- `.agents/RULES.md`: the hard rules, modern features, and a CLI cheat sheet
- `.agents/quick-syntax.md`: a one-screen syntax reference
- `.agents/index.md`: a map from task to recipe
- `.agents/tasks/`: recipes for HTTP apps, CLI tools, tests, macros, errors, pattern matching, schema validation, and more
- `.agents/examples/`: small runnable projects, only with `--with-examples`

## Flags

| Flag | Effect |
|------|--------|
| `--auto` | install only for agents found in the project |
| `--all` | install for every platform |
| `--with-examples` | also copy `.agents/examples/` |
| `--no-docs` | install only the skill file, not `.agents/` |
| `--dry-run` | print what would be written, change nothing |
| `--force` | replace the skill file without a backup, and replace docs you edited (after backing them up) |
| `--uninstall` | remove the skill file and restore its backup |
| `--check` | compare the installed docs version with the bundled one; exit 1 when they differ |

## Without the CLI

Agents that cannot run `phel` can read the same knowledge as plain text:

- `https://phel-lang.org/agentic-coding.md`: the [Agentic Coding](/documentation/reference/agentic-coding/) reference as one page
- `https://phel-lang.org/llms.txt`: a link index of all documentation for LLMs
