# shellcheck shell=bash
# Source this file at the start of every AI session:
#   source .ai-cli/env.sh
#
# Pins tool paths so sessions never depend on PATH lookup.
# GH_BIN / AWS_BIN / TERRAFORM_BIN / DOCKER_BIN are single executable paths
# (thin wrappers in bin/), so they work quoted or unquoted, and when passed to
# child processes such as ai-run, session hooks, and the command templates.

case $- in
  *i*) ;;                      # interactive (your VS Code terminal): leave defaults alone
  *) set -euo pipefail ;;      # scripts / ai-run / agent sessions: keep strict
esac

# --- Kit locations ---
# Resolve relative to this script so sourcing works from any cwd,
# even outside the repo.
_AI_SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]:-$0}")" && pwd)"
export AI_CLI_ROOT="$_AI_SCRIPT_DIR"
export AI_CLI_LOG_DIR="$AI_CLI_ROOT/logs"
export AI_CLI_TEMPLATES="$AI_CLI_ROOT/command-templates"
# Put the kit's pinned wrappers on PATH (idempotent, prepended).
case ":${PATH:-}:" in
  *":$AI_CLI_ROOT/bin:"*) ;;
  *) export PATH="$AI_CLI_ROOT/bin:${PATH:-}" ;;
esac
unset _AI_SCRIPT_DIR

# --- host-spawn: runs commands on the host from inside a Flatpak sandbox ---
if [ "${FLATPAK_IDE_ENV:-}" = "1" ] && [ -x /app/bin/host-spawn ]; then
  HOST_SPAWN="/app/bin/host-spawn"
else
  HOST_SPAWN=""
fi
export HOST_SPAWN

# --- Pinned binaries (do not change to bare names) ---
# `gh` MUST be invoked as `$GH_BIN` (wrapper execs host gh via host-spawn
# inside Flatpak). aws / terraform run on the host the same way; they are
# optional (warn only).
GH_BIN="$AI_CLI_ROOT/bin/gh"
AWS_BIN="$AI_CLI_ROOT/bin/aws"
TERRAFORM_BIN="$AI_CLI_ROOT/bin/terraform"
DOCKER_BIN="$AI_CLI_ROOT/bin/docker"
export GH_BIN AWS_BIN TERRAFORM_BIN DOCKER_BIN

# Never let the AWS CLI open an interactive pager.
export AWS_PAGER=""

# --- Health checks (gh required; aws / terraform optional, warn only) ---
if ! "$GH_BIN" --version > /dev/null 2>&1; then
  echo "ERROR: gh not found using $GH_BIN" >&2
  echo "Expected: host /usr/bin/gh reachable via the bin/gh wrapper" >&2
  return 1 2>/dev/null || exit 1
fi

if ! "$AWS_BIN" --version > /dev/null 2>&1; then
  echo "WARNING: aws not found via $AWS_BIN — aws commands unavailable" >&2
fi
if ! "$TERRAFORM_BIN" --version > /dev/null 2>&1; then
  echo "WARNING: terraform not found via $TERRAFORM_BIN — tf commands unavailable" >&2
fi
if ! "$DOCKER_BIN" version > /dev/null 2>&1; then
  echo "WARNING: docker not found via $DOCKER_BIN — docker commands unavailable" >&2
fi
