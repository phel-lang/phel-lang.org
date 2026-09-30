#!/bin/bash
# PreToolUse hook: generated files are rebuilt by composer build, the release
# generator or agnostic-ai sync, so a hand edit is lost on the next build.
set -euo pipefail

file=$(jq -r '.tool_input.file_path // empty')
[[ -z "$file" ]] && exit 0

case "$file" in
  */content/documentation/reference/api/* | \
  */content/documentation/reference/errors.md | \
  */static/api.json | */static/api_search.json | \
  */static/tailwind.css | */static/llms-full.txt | */static/agentic-coding.md | \
  */CLAUDE.md | */AGENTS.md | */.claude/rules/* | */.claude/skills/* | */.claude/agents/* | */.claude/settings.json)
    echo "Generated file: $file. Edit its source instead (build/src/, scripts/, css/ or .agnostic-ai/), then rebuild." >&2
    exit 2
    ;;
  */content/releases/*)
    [[ "$file" == */content/releases/_index.md ]] && exit 0
    echo "Generated file: $file. Release pages come from GitHub releases via build/generate-releases.php." >&2
    exit 2
    ;;
esac
exit 0
