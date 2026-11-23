#!/usr/bin/env bash
# Read-only terraform check: fmt -check + validate. Changes nothing.
# Usage: tf-check.sh [dir]
# Terraform runs on the host via host-spawn (see ../env.sh -> $TERRAFORM_BIN).
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

# shellcheck disable=SC2086
"${TF_CMD[@]}" -chdir="$DIR" fmt -check -recursive
"${TF_CMD[@]}" -chdir="$DIR" validate
