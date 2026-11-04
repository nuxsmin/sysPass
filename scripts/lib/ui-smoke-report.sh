#!/usr/bin/env bash
# Shared helpers for scripts/ui-smoke.sh. Sourced by the runner and runner tests.

ui_smoke_remove_error_context() {
  local output_dir=${1:-}

  if [ -z "$output_dir" ] || [ ! -d "$output_dir" ]; then
    return 0
  fi

  find "$output_dir" -type f -name 'error-context.md' -delete 2>/dev/null || true
}

# ui_smoke_setup_report STATUS OUTPUT_DIR RUN_ROOT SUMMARY_ROOT UI_DIR RUN_ID STARTED_AT
#
# Write and retain a setup-failure report when the runner did not reach or
# finish Playwright. Returns non-zero when a required summary cannot be
# produced or retained, and prints success only after a summary exists.
ui_smoke_setup_report() {
  local status=$1
  local output_dir=$2
  local run_root=$3
  local summary_root=$4
  local ui_dir=$5
  local run_id=$6
  local started_at=$7
  local temporary_output=0
  local summary_dir

  [ "$status" -eq 0 ] && return 0

  if [ -z "$run_root" ]; then
    output_dir="$(mktemp -d "${TMPDIR:-/tmp}/sp-ui-setup-report.XXXXXX")" || return 1
    temporary_output=1
  fi

  remove_temporary_output() {
    if [ "$temporary_output" -eq 1 ]; then
      find "$output_dir" -mindepth 1 -delete 2>/dev/null || true
      rmdir "$output_dir" 2>/dev/null || true
    fi
  }

  ui_smoke_remove_error_context "$output_dir"

  if [ -f "$output_dir/run-summary.json" ]; then
    summary_dir="$summary_root/$run_id"

    if [ ! -f "$summary_dir/run-summary.json" ]; then
      if ! node "$ui_dir/src/retain.mjs" --output "$output_dir" --destination "$summary_dir" >/dev/null 2>&1; then
        echo "ERROR: failed to retain sanitized summary" >&2
        remove_temporary_output
        return 1
      fi

      echo "Retained sanitized summary: $summary_dir"
    fi

    remove_temporary_output
    return 0
  fi

  mkdir -p "$output_dir" || {
    remove_temporary_output
    return 1
  }

  if ! SP_UI_SETUP_ERROR="UI smoke run terminated before Playwright evidence was produced (exit $status)" \
    SP_UI_RUN_ID="$run_id" \
    SP_UI_TIER="${SP_UI_TIER:-A}" \
    SP_UI_SPECS="${SP_UI_SPECS:-}" \
    node "$ui_dir/src/setup-report.mjs" \
      --output "$output_dir" \
      --run-id "$run_id" \
      --started-at "$started_at" >/dev/null 2>&1; then
    echo "ERROR: failed to write sanitized setup-failure summary" >&2
    remove_temporary_output
    return 1
  fi

  if [ ! -s "$output_dir/run-summary.json" ]; then
    echo "ERROR: sanitized setup-failure summary was not written" >&2
    remove_temporary_output
    return 1
  fi

  summary_dir="$summary_root/$run_id"

  if ! node "$ui_dir/src/retain.mjs" --output "$output_dir" --destination "$summary_dir" >/dev/null 2>&1; then
    echo "ERROR: failed to retain sanitized setup-failure summary" >&2
    remove_temporary_output
    return 1
  fi

  echo "Retained sanitized setup-failure summary: $summary_dir"
  remove_temporary_output
  return 0
}
