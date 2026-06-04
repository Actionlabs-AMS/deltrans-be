#!/usr/bin/env bash
# Full-repo graphify AST update; output is relocated to app/graphify-out (see .cursor/rules/graphify.mdc).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

graphify update . || true

if [[ ! -d graphify-out ]]; then
  echo "graphify-out not created — update failed before writing output." >&2
  exit 1
fi

mkdir -p app/graphify-out
rsync -a --delete graphify-out/ app/graphify-out/
rm -rf graphify-out

echo "Graph updated at app/graphify-out/ ($(du -sh app/graphify-out | cut -f1))"
