---
name: commit
description: Use when asked to commit changes ("commit", "/commit").
x-claude:
  model: sonnet
---

# Commit

## Rules

- Conventional commits on every commit. Use `ref:`, not `refactor:`.
- No AI mention, no `Co-Authored-By` trailer, no emojis.
- Identity: `chemaclass@outlook.es`, GPG key `E51B5BF45F85D160`. Never `--no-gpg-sign` or `--no-verify`.
- Hook fails: fix, then make a NEW commit. Never `--amend`.
- Stage files by name. Never `git add -A` / `.`.
- Refuse files matching `.env`, `*credentials*`, `*.pem`, `*.key`.
- Do not push.

## Workflow

1. Parallel: `git status`, `git diff`, `git log --oneline -5`.
2. Pick type from diff: `feat` / `fix` / `ref` / `docs` / `test` / `chore` / `style` / `perf` / `build` / `ci`.
3. Subject: `<type>(<scope>): <imperative>`. ≤72 chars, lowercase, no period.
4. Add a body only when the why is not obvious. Wrap at 72.
5. Commit via HEREDOC.
6. Report: subject + short hash + ahead count.
