#!/usr/bin/env bash
# Comment on an issue: body via file/stdin (never argv, avoids quoting bugs).
# Usage: gh-issue-comment.sh <number> [--body-file FILE]
#        gh-issue-comment.sh 42 < comment.md
# Uses ~/.local/bin/gh explicitly — never bare `gh`.
set -euo pipefail

GH_BIN="${GH_BIN:-$HOME/.local/bin/gh}"
NUM="${1:?Usage: gh-issue-comment.sh <number> [--body-file FILE]}"
shift || true
BODY_FILE=""

while [ "$#" -gt 0 ]; do
  case "$1" in
    --body-file) BODY_FILE="$2"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

TMP=""
cleanup() { [ -n "$TMP" ] && rm -f "$TMP"; }
trap cleanup EXIT

if [ -n "$BODY_FILE" ]; then
  BODY_SRC="$BODY_FILE"
elif [ ! -t 0 ]; then
  TMP="$(mktemp)"
  cat > "$TMP"
  BODY_SRC="$TMP"
else
  echo "Provide --body-file or pipe body on stdin" >&2
  exit 2
fi

"$GH_BIN" issue comment "$NUM" --body-file "$BODY_SRC"
