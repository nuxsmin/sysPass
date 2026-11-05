#!/usr/bin/env bash
# Shared helpers for scripts/ui-smoke.sh. Sourced by the runner and runner tests.

ui_smoke_remove_error_context() {
  local output_dir=${1:-}

  if [ -z "$output_dir" ] || [ ! -d "$output_dir" ]; then
    return 0
  fi

  find "$output_dir" -type f -name 'error-context.md' -delete 2>/dev/null || true
}

# ui_smoke_ui_dir
#
# Resolve the shared UI harness directory from this library so the verifier can
# reuse the same allowlist as the reporter without a caller-supplied path.
ui_smoke_ui_dir() {
  local lib_dir

  lib_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

  printf '%s/tests/ui\n' "$(cd "$lib_dir/../.." && pwd)"
}

# ui_smoke_verify_upload_manifest SUMMARY_DIR
#
# Require the retained upload manifest to describe exactly the allowlisted
# files that were retained. Unknown or missing entries fail closed.
ui_smoke_verify_upload_manifest() {
  local summary_dir=${1:-}
  local manifest="$summary_dir/upload-manifest.json"
  local ui_dir

  if [ ! -s "$manifest" ]; then
    echo "ERROR: retained upload manifest missing or empty: $manifest" >&2
    return 1
  fi

  ui_dir="$(ui_smoke_ui_dir)"

  if ! node "$ui_dir/src/verify-manifest.mjs" --manifest "$manifest" --directory "$summary_dir"; then
    echo "ERROR: retained upload manifest is not consistent with the upload allowlist" >&2
    return 1
  fi
}

# ui_smoke_verify_retained SUMMARY_DIR
#
# Succeed only when every allowlisted retained artifact exists and is non-empty,
# and the upload manifest matches the retained allowlist.
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

  if [ "$missing" -ne 0 ]; then
    return 1
  fi

  ui_smoke_verify_upload_manifest "$summary_dir"
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

# ui_smoke_verify_single_summary_root SUMMARY_ROOT
#
# Require exactly one retained run-summary.json below SUMMARY_ROOT and the
# complete allowlisted artifact set beside it. CI uses this before uploading so
# an empty, duplicate or partial evidence set fails instead of publishing
# nothing or an ambiguous report.
ui_smoke_verify_single_summary_root() {
  local summary_root=${1:-}
  local -a summaries=()
  local -a artifact_dirs=()
  local -a failure_run_dirs=()
  local summary_dir
  local failure_dir

  if [ -z "$summary_root" ] || [ ! -d "$summary_root" ]; then
    echo "ERROR: retained summary directory is missing: ${summary_root:-<unset>}" >&2
    return 1
  fi

  summary_root="${summary_root%/}"

  mapfile -t summaries < <(
    find "$summary_root" -type f -name 'run-summary.json' -print 2>/dev/null | sort
  )

  if [ "${#summaries[@]}" -ne 1 ]; then
    echo "ERROR: expected exactly one retained run-summary.json below $summary_root, found ${#summaries[@]}" >&2
    return 1
  fi

  summary_dir="$(dirname "${summaries[0]}")"

  mapfile -t artifact_dirs < <(
    find "$summary_root" -type f \
      \( -name 'run-summary.json' -o -name 'junit.xml' -o -name 'upload-manifest.json' \) \
      -print 2>/dev/null | while IFS= read -r artifact; do
        dirname "$artifact"
      done | sort -u
  )

  if [ "${#artifact_dirs[@]}" -ne 1 ] || [ "${artifact_dirs[0]}" != "$summary_dir" ]; then
    echo "ERROR: retained artifacts must all live beside the single run summary; found ${#artifact_dirs[@]} artifact directories" >&2
    return 1
  fi

  mapfile -t failure_run_dirs < <(
    find "$summary_root" -type f -path '*/failures/*' -print 2>/dev/null | while IFS= read -r artifact; do
      relative="${artifact#"$summary_root"/}"
      printf '%s\n' "$summary_root/${relative%%/failures/*}"
    done | sort -u
  )

  for failure_dir in "${failure_run_dirs[@]}"; do
    if [ "$failure_dir" != "$summary_dir" ]; then
      echo "ERROR: failure diagnostics outside the single run directory: $failure_dir" >&2
      return 1
    fi
  done

  ui_smoke_verify_retained "$summary_dir"
}

# ui_smoke_ci_setup_report SUMMARY_ROOT UI_DIR RUN_ID STARTED_AT [STATUS]
#
# Write a sanitized setup-failure report when the CI workflow fails before the
# runner can retain its own evidence. An existing complete set is left
# untouched. A partial set that still has the authoritative run summary is
# repaired through the normal retention path; one that cannot be repaired is
# replaced with a clearly non-pass setup-failed set in the same directory, so
# ci-report always sees exactly one summary.
ui_smoke_ci_setup_report() {
  local summary_root=${1:-}
  local ui_dir=${2:-}
  local run_id=${3:-}
  local started_at=${4:-}
  local status=${5:-1}
  local -a artifacts=()
  local -a run_dirs=()
  local summary_dir

  if [ -z "$summary_root" ] || [ -z "$ui_dir" ] || [ -z "$run_id" ]; then
    echo "ERROR: ui_smoke_ci_setup_report requires SUMMARY_ROOT, UI_DIR and RUN_ID" >&2
    return 1
  fi

  [ -n "$started_at" ] || started_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

  if [ -d "$summary_root" ]; then
    mapfile -t artifacts < <(
      find "$summary_root" -type f \
        \( -name 'run-summary.json' -o -name 'junit.xml' -o -name 'upload-manifest.json' \) \
        -print 2>/dev/null | sort
    )
  fi

  if [ "${#artifacts[@]}" -gt 0 ]; then
    mapfile -t run_dirs < <(
      printf '%s\n' "${artifacts[@]}" | while IFS= read -r artifact; do
        dirname "$artifact"
      done | sort -u
    )
  fi

  if [ "${#run_dirs[@]}" -eq 0 ]; then
    ui_smoke_setup_report "$status" "" "" "$summary_root" "$ui_dir" "$run_id" "$started_at"
    return $?
  fi

  if [ "${#run_dirs[@]}" -ne 1 ]; then
    echo "ERROR: retained evidence is ambiguous; found artifact directories: ${run_dirs[*]}" >&2
    return 1
  fi

  summary_dir="${run_dirs[0]}"

  if ui_smoke_verify_retained "$summary_dir"; then
    echo "A complete retained run summary already exists ($summary_dir/run-summary.json); not writing a second setup report."
    return 0
  fi

  # The run summary is authoritative and JUnit/manifest are derived. Reuse the
  # existing retention path to regenerate missing derived artifacts in place.
  if [ -s "$summary_dir/run-summary.json" ]; then
    if ui_smoke_retain_artifacts "$summary_dir" "$summary_dir" "$ui_dir" >/dev/null 2>&1 \
      && ui_smoke_verify_retained "$summary_dir"; then
      echo "Repaired partial retained artifact set: $summary_dir"
      return 0
    fi
  fi

  # The partial set cannot be repaired into trustworthy evidence. Replace it
  # with a setup-failed set in the same run directory so ci-report rejects it
  # and the upload never publishes an ambiguous partial report.
  echo "ERROR: retained artifact set is partial and could not be repaired: $summary_dir" >&2
  ui_smoke_setup_report \
    "$status" "" "" \
    "$(dirname "$summary_dir")" "$ui_dir" "$(basename "$summary_dir")" "$started_at"
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

  if ! SP_UI_SETUP_ERROR="${SP_UI_SETUP_ERROR:-UI smoke run terminated before Playwright evidence was produced (exit $status)}" \
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
