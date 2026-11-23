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

# ui_smoke_setup_report STATUS OUTPUT_DIR RUN_ROOT SUMMARY_ROOT UI_DIR RUN_ID STARTED_AT [FORCE]
#
# Write and retain a setup-failure report when the runner did not reach or
# finish Playwright. Returns non-zero when a required summary cannot be
# produced or retained, and prints success only after the complete retained
# artifact set exists.
#
# FORCE=1 replaces an existing complete (possibly passing) retained set with a
# setup-failed set. It never reuses the Playwright output summary, so a later
# isolation failure cannot leave a passing interviewer summary in place.
ui_smoke_setup_report() {
  local status=$1
  local output_dir=$2
  local run_root=$3
  local summary_root=$4
  local ui_dir=$5
  local run_id=$6
  local started_at=$7
  local force=${8:-0}
  local temporary_output=0
  local staging_dir=""
  local summary_dir

  if [ "$status" -eq 0 ] && [ "$force" -ne 1 ]; then
    return 0
  fi

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

  remove_staging_output() {
    if [ -n "$staging_dir" ] && [ -d "$staging_dir" ]; then
      find "$staging_dir" -mindepth 1 -delete 2>/dev/null || true
      rmdir "$staging_dir" 2>/dev/null || true
    fi
  }

  ui_smoke_remove_error_context "$output_dir"

  summary_dir="$summary_root/$run_id"

  if [ "$force" -ne 1 ] \
    && [ -s "$summary_dir/run-summary.json" ] \
    && [ -s "$summary_dir/junit.xml" ] \
    && [ -s "$summary_dir/upload-manifest.json" ]; then
    remove_temporary_output
    return 0
  fi

  if [ "$force" -ne 1 ] && [ -f "$output_dir/run-summary.json" ]; then
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

  # A forced failure must not inherit the Playwright summary from the instance
  # that just passed. Stage a fresh setup-failure report and replace the whole
  # retained run directory.
  if [ "$force" -eq 1 ]; then
    staging_dir="$(mktemp -d "${TMPDIR:-/tmp}/sp-ui-forced-report.XXXXXX")" || {
      remove_temporary_output
      return 1
    }
  else
    staging_dir="$output_dir"
  fi

  mkdir -p "$staging_dir" || {
    remove_staging_output
    remove_temporary_output
    return 1
  }

  if ! SP_UI_SETUP_ERROR="${SP_UI_SETUP_ERROR:-UI smoke run terminated before Playwright evidence was produced (exit $status)}" \
    SP_UI_RUN_ID="$run_id" \
    SP_UI_TIER="${SP_UI_TIER:-A}" \
    SP_UI_SPECS="${SP_UI_SPECS:-}" \
    node "$ui_dir/src/setup-report.mjs" \
      --output "$staging_dir" \
      --run-id "$run_id" \
      --started-at "$started_at" >/dev/null 2>&1; then
    echo "ERROR: failed to write sanitized setup-failure summary" >&2
    remove_staging_output
    remove_temporary_output
    return 1
  fi

  if [ ! -s "$staging_dir/run-summary.json" ]; then
    echo "ERROR: sanitized setup-failure summary was not written" >&2
    remove_staging_output
    remove_temporary_output
    return 1
  fi

  if [ "$force" -eq 1 ] && [ -d "$summary_dir" ]; then
    find "$summary_dir" -mindepth 1 -delete 2>/dev/null || true
  fi

  if ! ui_smoke_retain_artifacts "$staging_dir" "$summary_dir" "$ui_dir" >/dev/null 2>&1; then
    ui_smoke_verify_retained "$summary_dir" || true
    echo "ERROR: failed to retain sanitized setup-failure summary" >&2
    remove_staging_output
    remove_temporary_output
    return 1
  fi

  echo "Retained sanitized setup-failure summary: $summary_dir"
  remove_staging_output
  remove_temporary_output
  return 0
}

# ui_smoke_install_failure_report STATUS OUTPUT_DIR SUMMARY_ROOT UI_DIR RUN_ID STARTED_AT MESSAGE
#
# Replace the installer evidence with a forced setup-failed summary and return
# non-zero so the runner cannot finish successfully. The caller must not rely
# on the previously retained Playwright summary, even when the wizard itself
# passed but the seeded-instance isolation proof failed afterwards.
ui_smoke_install_failure_report() {
  local status=$1
  local output_dir=$2
  local summary_root=$3
  local ui_dir=$4
  local run_id=$5
  local started_at=$6
  local message=${7:-UI smoke installer selection failed}

  if ! SP_UI_TIER=INSTALL SP_UI_SPECS= SP_UI_SETUP_ERROR="$message" \
    ui_smoke_setup_report \
      "$status" "$output_dir" "$output_dir" "$summary_root" "$ui_dir" "$run_id" "$started_at" 1; then
    return 1
  fi

  [ "$status" -eq 0 ] && return 0

  return 1
}

# ui_smoke_compare_fingerprints BEFORE AFTER LABEL [BEFORE AFTER LABEL ...]
#
# Compare before/after fingerprint pairs. Prints the label of every changed
# directory and returns non-zero when any pair differs, so the runner can name
# the paths that leaked instead of only reporting a generic digest mismatch.
ui_smoke_compare_fingerprints() {
  local before
  local after
  local label
  local changed=0

  while [ "$#" -ge 3 ]; do
    before=$1
    after=$2
    label=$3
    shift 3

    if [ "$before" != "$after" ]; then
      printf '%s\n' "$label"
      changed=1
    fi
  done

  return "$changed"
}

# ui_smoke_finalize_run SEEDED_STATUS INSTALL_STATUS INSTALL_SELECTED SEEDED_TIER \
#   RUN_ROOT SUMMARY_ROOT INSTALL_SUMMARY_ROOT UI_DIR RUN_ID STARTED_AT \
#   CONFIG_BEFORE CONFIG_AFTER CACHE_BEFORE CACHE_AFTER TEMP_BEFORE TEMP_AFTER \
#   [SEEDED_SETUP_ERROR] [INSTALL_SETUP_ERROR]
#
# Always compare the developer app/config, app/cache and app/temp fingerprints
# before honoring a failed seeded or installer status. A failed ordinary run
# still has to prove it did not mutate the developer tree; only a run that could
# not provision reaches its failure path without calling this gate.
#
# SEEDED_SETUP_ERROR and INSTALL_SETUP_ERROR are non-empty only when a failure
# after provisioning (finalization, installer pre-check, installer finalize or
# isolation proof) must replace the retained Playwright evidence. On drift the
# changed paths override both messages.
#
# On drift it prints every changed path, forces setup-failed evidence for each
# selected summary and returns non-zero. Otherwise it forces setup-failed
# evidence for an accumulated post-provisioning failure, then returns the first
# non-zero selection status so ordinary seeded/installer failures keep their
# exit code.
ui_smoke_finalize_run() {
  local seeded_status=$1
  local install_status=$2
  local install_selected=$3
  local seeded_tier=$4
  local run_root=$5
  local summary_root=$6
  local install_summary_root=$7
  local ui_dir=$8
  local run_id=$9
  local started_at=${10}
  local config_before=${11}
  local config_after=${12}
  local cache_before=${13}
  local cache_after=${14}
  local temp_before=${15}
  local temp_after=${16}
  local seeded_setup_error=${17:-}
  local install_setup_error=${18:-}
  local changes=""
  local changed_summary=""

  if ! changes="$(ui_smoke_compare_fingerprints \
    "$config_before" "$config_after" "app/config" \
    "$cache_before" "$cache_after" "app/cache" \
    "$temp_before" "$temp_after" "app/temp")"; then
    changed_summary="${changes//$'\n'/, }"

    echo "ERROR: developer paths changed during the run: $changed_summary" >&2

    if [ -n "$seeded_tier" ]; then
      SP_UI_TIER="$seeded_tier" \
        SP_UI_SETUP_ERROR="developer paths changed during the run: $changed_summary" \
        ui_smoke_setup_report \
          1 "$run_root/output" "$run_root" "$summary_root" "$ui_dir" "$run_id" "$started_at" 1 \
        || true

      seeded_status=1
    fi

    if [ "$install_selected" -eq 1 ]; then
      ui_smoke_install_failure_report \
        1 "$run_root/output-install" "$install_summary_root" "$ui_dir" "$run_id" "$started_at" \
        "developer paths changed during the run: $changed_summary" \
        || true

      install_status=1
    fi
  else
    if [ "$seeded_status" -ne 0 ] && [ -n "$seeded_setup_error" ] && [ -n "$seeded_tier" ]; then
      SP_UI_TIER="$seeded_tier" \
        SP_UI_SETUP_ERROR="$seeded_setup_error" \
        ui_smoke_setup_report \
          1 "$run_root/output" "$run_root" "$summary_root" "$ui_dir" "$run_id" "$started_at" 1 \
        || true

      seeded_status=1
    fi

    if [ "$install_status" -ne 0 ] && [ -n "$install_setup_error" ] && [ "$install_selected" -eq 1 ]; then
      ui_smoke_install_failure_report \
        1 "$run_root/output-install" "$install_summary_root" "$ui_dir" "$run_id" "$started_at" \
        "$install_setup_error" \
        || true

      install_status=1
    fi
  fi

  if [ "$seeded_status" -ne 0 ]; then
    echo "Seeded UI smoke selection failed with status $seeded_status" >&2
    return "$seeded_status"
  fi

  if [ "$install_status" -ne 0 ]; then
    echo "Installer UI smoke selection failed with status $install_status" >&2
    return "$install_status"
  fi

  return 0
}
