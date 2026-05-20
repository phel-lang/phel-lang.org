---
name: commit
description: Create a git commit following project conventions. Trigger on "commit", "/commit", or requests to commit changes.
model: sonnet
---

# Commit

## Rules

- **Conventional commits - non-negotiable.** Every commit. Use `ref:` not `refactor:`.
- No AI references. No `Co-Authored-By: Claude`. No emojis unless asked.
- Identity: `chemaclass@outlook.es`, GPG key `E51B5BF45F85D160`. Never `--no-gpg-sign` or `--no-verify`.
- Hook fails → fix + NEW commit. Never `--amend`.
- Stage files by name. Never `git add -A` / `.`.
- Refuse files matching `.env`, `*credentials*`, `*.pem`, `*.key`.
- Do not push.

## Workflow

1. Parallel: `git status`, `git diff`, `git log --oneline -5`.
2. Pick type from diff: `feat` / `fix` / `ref` / `docs` / `test` / `chore` / `style` / `perf` / `build` / `ci`.
3. Subject: `<type>(<scope>): <imperative>`. ≤72 chars, lowercase, no period.
4. Body only if "why" non-obvious. Wrap 72.
5. Commit via HEREDOC.
6. Report: subject + short hash + ahead count.
