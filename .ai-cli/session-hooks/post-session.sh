#!/usr/bin/env bash
# post-session: snapshot what changed. Read-only, never commits/pushes.
# Usage: .ai-cli/session-hooks/post-session.sh
set -euo pipefail

REPO_ROOT="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$REPO_ROOT"

echo "## diff stat"
git status --porcelain=v1 | head -30
git diff --stat HEAD | tail -10
echo "## logs this session"
ls -t .ai-cli/logs/ 2>/dev/null | head -10
