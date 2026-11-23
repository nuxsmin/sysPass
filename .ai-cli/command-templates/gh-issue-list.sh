#!/usr/bin/env bash
# List issues with bounded, machine-readable output.
# Usage: gh-issue-list.sh [--label LABEL] [--state STATE] [--limit N]
# Uses ~/.local/bin/gh explicitly — never bare `gh`.
set -euo pipefail

GH_BIN="${GH_BIN:-$HOME/.local/bin/gh}"
LABEL=""
STATE="open"
LIMIT="30"

while [ "$#" -gt 0 ]; do
  case "$1" in
    --label) LABEL="$2"; shift 2 ;;
    --state) STATE="$2"; shift 2 ;;
    --limit) LIMIT="$2"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

ARGS=(issue list --state "$STATE" --limit "$LIMIT"
  --json number,title,labels,assignees,updatedAt
  --jq '[.[] | {number, title, labels: [.labels[].name],
                 assignees: [.assignees[].login], updatedAt}]')
if [ -n "$LABEL" ]; then
  ARGS+=(--label "$LABEL")
fi

"$GH_BIN" "${ARGS[@]}"
