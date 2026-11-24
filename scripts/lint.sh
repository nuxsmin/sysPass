#!/usr/bin/env bash
# Run the static-analysis checks used by CI. Pass the PR base commit as $1.
set -euo pipefail
cd "$(dirname "$0")/.."

base="${1:-}"
if [ -z "$base" ]; then
  echo "Usage: bash scripts/lint.sh <base-commit>" >&2
  exit 2
fi
if ! git rev-parse --verify --quiet "$base^{commit}" >/dev/null; then
  echo "Invalid base commit: $base" >&2
  exit 2
fi

php_runner="${PHP_RUNNER:-php}"
"$php_runner" vendor/bin/phpstan analyse --no-progress
PHP_RUNNER="$php_runner" bash scripts/ci/check-phpcs-changed.sh "$base"
