#!/usr/bin/env bash
set -euo pipefail

# Fetch the experimental WASM REPL bundle into static/wasm-repl/.
# Source: https://github.com/kambo-1st/kambo-1st.github.io (used with permission).
# Skipped if the bundle is already present unless FORCE=1.

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEST="$ROOT/static/wasm-repl"
BASE="https://kambo-1st.github.io/phel"

FILES=(
  "build/php-web.mjs"
  "build/php-web.wasm"
  "build/php-web.data"
)

if [[ -f "$DEST/build/php-web.data" && "${FORCE:-0}" != "1" ]]; then
  echo "[fetch-wasm-repl] bundle already present, skipping (set FORCE=1 to refetch)"
  exit 0
fi

mkdir -p "$DEST/build"

for f in "${FILES[@]}"; do
  echo "[fetch-wasm-repl] $f"
  curl -fsSL --retry 3 --retry-delay 2 -o "$DEST/$f" "$BASE/$f"
done

echo "[fetch-wasm-repl] done"
