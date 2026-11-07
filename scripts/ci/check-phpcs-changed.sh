#!/usr/bin/env bash
# check-phpcs-changed — run PHPCS (PSR2, errors only) on the PHP files that
# changed under lib/ since a base commit.
#
# Usage:
#   bash scripts/ci/check-phpcs-changed.sh <base-ref>
#
# The base ref is compared with the working tree, so a clean CI checkout checks
# the commits since the base and a developer checkout also includes uncommitted
# changes. Deleted files are ignored (--diff-filter=ACMR), paths are handled
# NUL-safely, and an empty or invalid base ref or an empty file list exits 0.
set -euo pipefail

BASE_REF="${1:-}"
PHPCS_BIN="${PHPCS_BIN:-vendor/bin/phpcs}"

if [ -z "$BASE_REF" ]; then
  echo "No changed-files base supplied; nothing to check."
  exit 0
fi

if ! git rev-parse --verify --quiet "$BASE_REF^{commit}" >/dev/null; then
  echo "Base ref '$BASE_REF' is not a commit; nothing to check."
  exit 0
fi

files=()
while IFS= read -r -d '' file; do
  case "$file" in
    *.php) files+=("$file") ;;
  esac
done < <(git diff --name-only --diff-filter=ACMR -z "$BASE_REF" -- lib)

if [ "${#files[@]}" -eq 0 ]; then
  echo "No changed PHP files under lib/; PHPCS not required."
  exit 0
fi

echo "PHPCS (PSR2, errors only) on ${#files[@]} changed file(s) under lib/:"
printf '  %s\n' "${files[@]}"

if ! "$PHPCS_BIN" --standard=PSR2 --warning-severity=0 -- "${files[@]}"; then
  exit 1
fi
