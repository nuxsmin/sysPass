#!/usr/bin/env bash
# Create an issue safely: title via argv, body via file/stdin (never argv).
# Usage: gh-issue-create.sh --title "..." [--label L] [--body-file FILE]
#        gh-issue-create.sh --title "..." < body.md
# Uses ~/.local/bin/gh explicitly — never bare `gh`.
set -euo pipefail

GH_BIN="${GH_BIN:-$HOME/.local/bin/gh}"
TITLE=""
LABEL=""
BODY_FILE=""

while [ "$#" -gt 0 ]; do
  case "$1" in
    --title) TITLE="$2"; shift 2 ;;
    --label) LABEL="$2"; shift 2 ;;
    --body-file) BODY_FILE="$2"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

if [ -z "$TITLE" ]; then
  echo "Missing --title" >&2
  exit 2
fi

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

ARGS=(issue create --title "$TITLE" --body-file "$BODY_SRC")
if [ -n "$LABEL" ]; then
  ARGS+=(--label "$LABEL")
fi

"$GH_BIN" "${ARGS[@]}"
