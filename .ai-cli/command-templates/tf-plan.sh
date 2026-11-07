#!/usr/bin/env bash
# Read-only terraform plan. Never prompts, never refreshes state locks by default.
# Usage: tf-plan.sh [dir] [extra plan args...]
# Examples:
#   tf-plan.sh infra/
#   tf-plan.sh infra/ -var 'env=dev'
# Plan is always safe; `apply` is NOT — ask the human first.
set -euo pipefail

if [ -z "${TERRAFORM_BIN:-}" ]; then
  if [ -n "${HOST_SPAWN:-}" ]; then
    TERRAFORM_BIN="$HOST_SPAWN terraform"
  else
    TERRAFORM_BIN="terraform"
  fi
fi
# TERRAFORM_BIN may be "runner binary" (e.g. "host-spawn terraform"); split into an array.
# shellcheck disable=SC2206
TF_CMD=($TERRAFORM_BIN)
DIR="${1:-.}"
shift || true

# -input=false: never prompt; -no-color: log-friendly output.
"${TF_CMD[@]}" -chdir="$DIR" plan -input=false -no-color "$@"
