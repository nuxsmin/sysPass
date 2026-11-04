#!/usr/bin/env bash
# Shared helpers for scripts/ui-smoke.sh. Sourced by the runner and runner tests.

ui_smoke_remove_error_context() {
  local output_dir=${1:-}

  if [ -z "$output_dir" ] || [ ! -d "$output_dir" ]; then
    return 0
  fi

  find "$output_dir" -type f -name 'error-context.md' -delete 2>/dev/null || true
}

# ui_smoke_verify_retained SUMMARY_DIR
#
# Succeed only when every allowlisted retained artifact exists and is non-empty.
ui_smoke_verify_retained() {
  local summary_dir=${1:-}
  local artifact
  local missing=0

  for artifact in run-summary.json junit.xml upload-manifest.json; do
    if [ ! -s "$summary_dir/$artifact" ]; then
      echo "ERROR: retained artifact missing or empty: $summary_dir/$artifact" >&2
      missing=1
    fi
  done

  return "$missing"
}

# ui_smoke_retain_artifacts OUTPUT_DIR SUMMARY_DIR UI_DIR
#
# Run the retention step and then prove that all three allowlisted artifacts
# were retained. A zero exit from the retention command alone is not success.
ui_smoke_retain_artifacts() {
  local output_dir=$1
  local summary_dir=$2
  local ui_dir=$3

  if ! node "$ui_dir/src/retain.mjs" --output "$output_dir" --destination "$summary_dir"; then
    echo "ERROR: failed to retain sanitized summary artifacts" >&2
    return 1
  fi

  ui_smoke_verify_retained "$summary_dir"
}

# ui_smoke_finalize_report OUTPUT_DIR SUMMARY_ROOT UI_DIR RUN_ID
#
# Retention entry point for a run that reached Playwright. Prints success only
# after the complete allowlisted set has been verified.
ui_smoke_finalize_report() {
  local output_dir=$1
  local summary_root=$2
  local ui_dir=$3
  local run_id=$4
  local summary_dir="$summary_root/$run_id"

  if [ ! -d "$output_dir" ]; then
    echo "ERROR: run output directory is missing: $output_dir" >&2
    return 1
  fi

  ui_smoke_remove_error_context "$output_dir"

  if ! ui_smoke_retain_artifacts "$output_dir" "$summary_dir" "$ui_dir"; then
    return 1
  fi

  echo "Retained sanitized summary: $summary_dir"
  return 0
}

# ui_smoke_setup_report STATUS OUTPUT_DIR RUN_ROOT SUMMARY_ROOT UI_DIR RUN_ID STARTED_AT
#
# Write and retain a setup-failure report when the runner did not reach or
# finish Playwright. Returns non-zero when a required summary cannot be
# produced or retained, and prints success only after the complete retained
# artifact set exists.
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

  summary_dir="$summary_root/$run_id"

  if [ -s "$summary_dir/run-summary.json" ] \
    && [ -s "$summary_dir/junit.xml" ] \
    && [ -s "$summary_dir/upload-manifest.json" ]; then
    remove_temporary_output
    return 0
  fi

  if [ -f "$output_dir/run-summary.json" ]; then
    if ! ui_smoke_retain_artifacts "$output_dir" "$summary_dir" "$ui_dir" >/dev/null 2>&1; then
      ui_smoke_verify_retained "$summary_dir" || true
      echo "ERROR: failed to retain sanitized summary" >&2
      remove_temporary_output
      return 1
    fi

    echo "Retained sanitized summary: $summary_dir"
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

  if ! ui_smoke_retain_artifacts "$output_dir" "$summary_dir" "$ui_dir" >/dev/null 2>&1; then
    ui_smoke_verify_retained "$summary_dir" || true
    echo "ERROR: failed to retain sanitized setup-failure summary" >&2
    remove_temporary_output
    return 1
  fi

  echo "Retained sanitized setup-failure summary: $summary_dir"
  remove_temporary_output
  return 0
}
