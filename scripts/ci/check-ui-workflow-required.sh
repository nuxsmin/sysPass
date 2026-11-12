#!/usr/bin/env bash
# check-ui-workflow-required — read-only check that neither UI Workflow
# reporting check is already merge-required.
#
# Usage:
#   bash scripts/ci/check-ui-workflow-required.sh [owner/repo]
#
# It reads branch protection and repository rulesets. It never writes repository
# settings and is not run by CI. The two checks are independent of
# check-ui-required.sh and of the #2086 Tier A policy. Exit codes:
#   0   neither UI Workflow check is required
#   1   at least one UI Workflow check appears required
#   127 gh is unavailable or unauthenticated
set -euo pipefail

REPO="${1:-nuxsmin/sysPass}"
GH_BIN="${GH_BIN:-gh}"
API_BASE="repos/$REPO"
CHECK_NAMES=(
  "${UI_WORKFLOW_WIRING_CHECK_NAME:-UI Workflow (navigation wiring, reporting-only)}"
  "${UI_WORKFLOW_WRITE_CHECK_NAME:-UI Workflow (account creation, reporting-only)}"
)

if ! command -v "$GH_BIN" >/dev/null 2>&1; then
  echo "ERROR: GitHub CLI '$GH_BIN' is not available. Install gh or set GH_BIN to its path." >&2
  exit 127
fi

if ! "$GH_BIN" auth status >/dev/null 2>&1; then
  echo "ERROR: GitHub CLI is not authenticated. Run 'gh auth login' first." >&2
  exit 127
fi

error_file="$(mktemp)"
trap 'rm -f "$error_file"' EXIT

echo "Repository: $REPO"
echo "Checks:"
for check_name in "${CHECK_NAMES[@]}"; do
  echo "  - $check_name"
done
echo "Mode:       read-only (no settings are changed)"
echo

required_by_protection=0
protection_contexts=""

if protection_contexts="$(
  "$GH_BIN" api "$API_BASE/branches/master/protection" --jq '
    ((.required_status_checks.contexts // [])
      + ((.required_status_checks.checks // []) | map(.context)))
    | unique | .[]
  ' 2>"$error_file"
)"; then
  echo "Branch protection contexts/checks:"
  if [ -n "$protection_contexts" ]; then
    while IFS= read -r context; do
      [ -n "$context" ] && echo "  - $context"
    done <<< "$protection_contexts"
  else
    echo "  (none)"
  fi

  for check_name in "${CHECK_NAMES[@]}"; do
    if printf '%s\n' "$protection_contexts" | grep -Fxq -- "$check_name"; then
      required_by_protection=1
    fi
  done
elif grep -q 'HTTP 404' "$error_file"; then
  echo "Branch protection: none configured (404)."
else
  echo "ERROR: could not read branch protection for master:" >&2
  cat "$error_file" >&2
  exit 1
fi

echo
required_by_ruleset=0
ruleset_contexts=""
if ! ruleset_ids="$("$GH_BIN" api "$API_BASE/rulesets" --jq '.[].id' 2>"$error_file")"; then
  echo "ERROR: could not read repository rulesets:" >&2
  cat "$error_file" >&2
  exit 1
fi

for ruleset_id in $ruleset_ids; do
  if ! ruleset_name="$("$GH_BIN" api "$API_BASE/rulesets/$ruleset_id" --jq '.name' 2>"$error_file")"; then
    echo "ERROR: could not read ruleset $ruleset_id:" >&2
    cat "$error_file" >&2
    exit 1
  fi

  if ! contexts="$(
    "$GH_BIN" api "$API_BASE/rulesets/$ruleset_id" --jq '
      .rules[]?
      | select(.type == "required_status_checks")
      | .parameters.required_status_checks[]?.context
    ' 2>"$error_file"
  )"; then
    echo "ERROR: could not read ruleset $ruleset_id rules:" >&2
    cat "$error_file" >&2
    exit 1
  fi

  if [ -z "$contexts" ]; then
    continue
  fi

  while IFS= read -r context; do
    [ -n "$context" ] || continue
    ruleset_contexts="${ruleset_contexts}${ruleset_name}: ${context}"$'\n'

    for check_name in "${CHECK_NAMES[@]}"; do
      if [ "$context" = "$check_name" ]; then
        required_by_ruleset=1
      fi
    done
  done <<< "$contexts"
done

echo "Ruleset required status checks:"
if [ -n "$ruleset_contexts" ]; then
  printf '%s' "$ruleset_contexts" | sed 's/^/  - /'
else
  echo "  (none)"
fi

echo
if [ "$required_by_protection" -eq 1 ] || [ "$required_by_ruleset" -eq 1 ]; then
  echo "RESULT: at least one UI Workflow reporting check is already merge-required. Do not start observation without maintainer review."
  exit 1
fi

echo "RESULT: neither UI Workflow reporting check is required by branch protection or rulesets."
