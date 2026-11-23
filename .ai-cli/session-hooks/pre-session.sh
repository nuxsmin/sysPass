#!/usr/bin/env bash
# pre-session: capture context. Safe, read-only, runs in seconds.
# Usage: .ai-cli/session-hooks/pre-session.sh
# Run it at the start of every AI session.
set -euo pipefail

REPO_ROOT="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$REPO_ROOT"
# shellcheck disable=SC1091
. .ai-cli/env.sh

echo "## repo"
git status --porcelain=v1 | head -20
echo "## recent"
git log --oneline -5
echo "## gh"
"$GH_BIN" --version | head -1
"$GH_BIN" auth status 2>&1 | head -5
echo "## open issues (bounded)"
"$GH_BIN" issue list --state open --limit 10 \
  --json number,title,labels \
  --jq '[.[] | {number, title, labels: [.labels[].name]}]'
