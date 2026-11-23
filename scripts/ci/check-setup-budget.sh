#!/usr/bin/env bash
# check-setup-budget — fail-closed guard for the UI smoke pre-spec setup window.
#
# Usage:
#   UI_SMOKE_SETUP_STARTED_MS=<epoch-ms> \
#   UI_SMOKE_SPECS_STARTED_MS=<epoch-ms> \
#   UI_SMOKE_SETUP_BUDGET_SECONDS=1800 \
#   bash scripts/ci/check-setup-budget.sh
#
# UI_SMOKE_SETUP_STARTED_MS is stamped at job start and UI_SMOKE_SPECS_STARTED_MS
# is written by the runner after provisioning and immediately before Playwright.
# Both are required. The guard exits 0 only for a non-zero, ordered window within
# the budget; missing, empty, non-numeric, negative, zero, reversed or
# over-budget input fails with a GitHub error annotation.
set -euo pipefail

DEFAULT_BUDGET_SECONDS=1800

if [ "${UI_SMOKE_SETUP_BUDGET_SECONDS+x}" = "x" ]; then
  budget_seconds="$UI_SMOKE_SETUP_BUDGET_SECONDS"
else
  budget_seconds="$DEFAULT_BUDGET_SECONDS"
fi

started_ms="${UI_SMOKE_SETUP_STARTED_MS:-}"
finished_ms="${UI_SMOKE_SPECS_STARTED_MS:-}"

fail() {
  echo "::error::$*" >&2
  exit 1
}

if [ -z "$started_ms" ] || [ -z "$finished_ms" ]; then
  fail "UI smoke setup stamps are missing (start='${started_ms}', finish='${finished_ms}')"
fi

case "$budget_seconds" in
  ''|*[!0-9]*)
    fail "UI smoke setup budget is not a positive integer: '${budget_seconds}'"
    ;;
esac

if [ "$budget_seconds" -le 0 ]; then
  fail "UI smoke setup budget must be greater than zero: ${budget_seconds}"
fi

validate_stamp() {
  local label=$1
  local value=$2

  case "$value" in
    -*)
      fail "UI smoke setup ${label} stamp is negative: '${value}'"
      ;;
    *[!0-9]*)
      fail "UI smoke setup ${label} stamp is not a non-negative integer: '${value}'"
      ;;
  esac

  if [ "$value" -eq 0 ]; then
    fail "UI smoke setup stamps must be non-zero timestamps"
  fi
}

validate_stamp start "$started_ms"
validate_stamp finish "$finished_ms"

if [ "$finished_ms" -le "$started_ms" ]; then
  fail "UI smoke setup finish stamp (${finished_ms}) is not after the start stamp (${started_ms})"
fi

elapsed_ms=$((finished_ms - started_ms))
budget_ms=$((budget_seconds * 1000))

echo "UI smoke setup duration: ${elapsed_ms} ms (budget ${budget_ms} ms, includes runner provisioning)"

if [ "$elapsed_ms" -gt "$budget_ms" ]; then
  fail "UI smoke setup exceeded its ${budget_seconds}s budget"
fi
