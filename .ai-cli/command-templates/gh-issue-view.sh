#!/usr/bin/env bash
# Read one issue: body + comments + labels, machine-readable.
# Usage: gh-issue-view.sh <number>
# Uses ~/.local/bin/gh explicitly — never bare `gh`.
set -euo pipefail

GH_BIN="${GH_BIN:-$HOME/.local/bin/gh}"
NUM="${1:?Usage: gh-issue-view.sh <number>}"

"$GH_BIN" issue view "$NUM" \
  --json number,title,body,labels,state,assignees,comments \
  --jq '{number, title, state,
          labels: [.labels[].name],
          assignees: [.assignees[].login],
          body,
          comments: [.comments[] | {author: .author.login, body}]}'
