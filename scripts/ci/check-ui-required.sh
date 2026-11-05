#!/usr/bin/env bash
# check-ui-required — read-only check for an existing merge requirement on the
# UI Smoke check before observation starts.
#
# Usage:
#   bash scripts/ci/check-ui-required.sh [owner/repo]
#
# It reads branch protection, rulesets and the other workflow definitions. It
# never writes repository settings and is not run by CI. Workflow content is
# fetched with the REST raw media type, so no GNU-specific base64 decoder is
# required on BSD/macOS or Linux hosts. Exit codes:
#   0   the UI check is not required; observation may start
#   1   the UI check appears required (or an aggregate reference needs review)
#   127 gh is unavailable, unauthenticated or a required tool is missing
set -euo pipefail

REPO="${1:-nuxsmin/sysPass}"
CHECK_NAME="${UI_SMOKE_CHECK_NAME:-UI Smoke (Tier A)}"
GH_BIN="${GH_BIN:-gh}"
API_BASE="repos/$REPO"

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
echo "UI check:   $CHECK_NAME"
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
  if printf '%s\n' "$protection_contexts" | grep -Fxq -- "$CHECK_NAME"; then
    required_by_protection=1
  fi

  echo "Branch protection contexts/checks:"
  if [ -n "$protection_contexts" ]; then
    while IFS= read -r context; do
      [ -n "$context" ] && echo "  - $context"
    done <<< "$protection_contexts"
  else
    echo "  (none)"
  fi
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

    if [ "$context" = "$CHECK_NAME" ]; then
      required_by_ruleset=1
    fi
  done <<< "$contexts"
done

echo "Ruleset required status checks:"
if [ -n "$ruleset_contexts" ]; then
  printf '%s' "$ruleset_contexts" | sed 's/^/  - /'
else
  echo "  (none)"
fi

echo
echo "Other workflows referencing the UI check:"
aggregate_references=()
if ! workflow_files="$(
  "$GH_BIN" api "$API_BASE/contents/.github/workflows" --jq '.[] | select(.type == "file") | .name' 2>"$error_file"
)"; then
  echo "ERROR: could not list workflow definitions:" >&2
  cat "$error_file" >&2
  exit 1
fi

while IFS= read -r workflow_file; do
  [ -n "$workflow_file" ] || continue
  [ "$workflow_file" = "ui-smoke.yml" ] && continue

  # Ask for the raw representation so no base64 flag is needed. This remains a
  # read-only GET request.
  if ! workflow_content="$(
    "$GH_BIN" api "$API_BASE/contents/.github/workflows/$workflow_file" \
      -H 'Accept: application/vnd.github.v3.raw' 2>"$error_file"
  )"; then
    echo "ERROR: could not read workflow definition $workflow_file:" >&2
    cat "$error_file" >&2
    exit 1
  fi

  if printf '%s' "$workflow_content" | grep -Eq 'ui-smoke|UI Smoke \(Tier A\)|needs:.*\bui\b'; then
    aggregate_references+=("$workflow_file")
    echo "  - $workflow_file"
  fi
done <<< "$workflow_files"

if [ "${#aggregate_references[@]}" -eq 0 ]; then
  echo "  (none)"
fi

echo
if [ "$required_by_protection" -eq 1 ] || [ "$required_by_ruleset" -eq 1 ]; then
  echo "RESULT: '$CHECK_NAME' is already merge-required. Do not start observation without maintainer review."
  exit 1
fi

if [ "${#aggregate_references[@]}" -gt 0 ]; then
  echo "RESULT: no direct branch-protection/ruleset requirement was found, but another workflow references the UI check."
  echo "Review the listed workflow(s) manually before starting observation."
  exit 1
fi

echo "RESULT: '$CHECK_NAME' is not required by branch protection, rulesets or another workflow. Observation may start."