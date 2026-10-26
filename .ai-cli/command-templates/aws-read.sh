#!/usr/bin/env bash
# Read-only AWS API wrapper: only get-*/describe-*/list-*/head-* verbs,
# JSON output, no pager, no prompts.
# Usage: aws-read.sh <service> <operation> [args...]
# Examples:
#   aws-read.sh ec2 describe-instances --max-results 20
#   aws-read.sh s3api list-objects-v2 --bucket my-bucket --max-items 50
# Mutations (create/update/delete/put/...) are rejected — ask the human first.
set -euo pipefail

if [ -z "${AWS_BIN:-}" ]; then
  if [ -n "${HOST_SPAWN:-}" ]; then
    AWS_BIN="$HOST_SPAWN aws"
  else
    AWS_BIN="aws"
  fi
fi
# AWS_BIN may be "runner binary" (e.g. "host-spawn aws"); split into an array.
# shellcheck disable=SC2206
AWS_CMD=($AWS_BIN)

[ "$#" -ge 2 ] || { echo "Usage: aws-read.sh <service> <operation> [args...]" >&2; exit 2; }
SERVICE="$1"; OP="$2"; shift 2

case "$OP" in
  get-*|describe-*|list-*|head-*) ;;
  ls|list) [ "$SERVICE" = "s3" ] || { echo "Rejected: '$OP' is not read-only" >&2; exit 3; } ;;
  *) echo "Rejected: '$OP' is not a read-only operation (get/describe/list/head only)" >&2; exit 3 ;;
esac

export AWS_PAGER=""
# --no-cli-pager: AWS CLI v2+; --output json: machine-readable, bounded by caller args.
exec "${AWS_CMD[@]}" "$SERVICE" "$OP" --no-cli-pager --output json "$@"
