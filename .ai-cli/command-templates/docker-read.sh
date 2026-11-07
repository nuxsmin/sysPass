#!/usr/bin/env bash
# Read-only docker wrapper: only safe inspect-style subcommands, no pager,
# no prompts. Bounded by the caller's args (add --limit/-n where supported).
# Usage: docker-read.sh <subcommand> [args...]
# Examples:
#   docker-read.sh ps -a --format '{{.Names}} {{.Status}}'
#   docker-read.sh images
#   docker-read.sh logs --tail 50 mycontainer
#   docker-read.sh compose ps
#   docker-read.sh compose logs --tail 50
# Everything that mutates (run/exec/build/pull/push/rm/stop/up/down/prune/...)
# is rejected — ask the human first.
set -euo pipefail

if [ -z "${DOCKER_BIN:-}" ]; then
  if [ -n "${HOST_SPAWN:-}" ]; then
    DOCKER_BIN="$HOST_SPAWN docker"
  else
    DOCKER_BIN="docker"
  fi
fi
# DOCKER_BIN may be "wrapper binary" (e.g. "host-spawn docker"); split into an array.
# shellcheck disable=SC2206
DOCKER_CMD=($DOCKER_BIN)

[ "$#" -ge 1 ] || { echo "Usage: docker-read.sh <subcommand> [args...]" >&2; exit 2; }
SUB="$1"; shift

case "$SUB" in
  version|info|ps|images|container|image|network|volume|inspect|logs|top|stats|events|history|context|system)
    case "$SUB" in
      system)
        [ "$#" -ge 1 ] || { echo "Usage: docker-read.sh system <df|info|events>" >&2; exit 2; }
        case "$1" in
          df|info|events) ;;
          *) echo "Rejected: 'system $1' is not read-only" >&2; exit 3 ;;
        esac
        ;;
      container|image|network|volume)
        [ "$#" -ge 1 ] || { echo "Usage: docker-read.sh $SUB <ls|inspect|...>" >&2; exit 2; }
        case "$1" in
          ls|list|inspect|ps|history|port|diff) ;;
          *) echo "Rejected: '$SUB $1' is not read-only" >&2; exit 3 ;;
        esac
        ;;
    esac
    ;;
  compose)
    [ "$#" -ge 1 ] || { echo "Usage: docker-read.sh compose <ps|logs|config|top>" >&2; exit 2; }
    case "$1" in
      ps|logs|config|top|images|port) ;;
      *) echo "Rejected: 'compose $1' is not read-only" >&2; exit 3 ;;
    esac
    ;;
  *) echo "Rejected: '$SUB' is not a read-only docker subcommand" >&2; exit 3 ;;
esac

exec "${DOCKER_CMD[@]}" "$SUB" "$@"
