#!/usr/bin/env bash
# ui-smoke — isolated, single-command UI smoke run for sysPass.
#
# Usage:
#   TESTS_UI_ENABLED=true ./scripts/ui-smoke.sh [--keep]
#
# TESTS_UI_ENABLED must be the exact value "true"; otherwise the runner exits
# without provisioning anything and without producing Tier A evidence.
#
# Requires (installed beforehand, never downloaded by this script):
#   - Docker images php-testing-composer:latest and mariadb:10.3
#   - root assets: npm ci && bash scripts/build-assets.sh
#   - tests/ui: npm ci && ./node_modules/.bin/playwright install chromium
#
# Every Docker resource is runner-owned and removed on success, failure or
# interruption. This script does not pull, build or reuse developer images,
# servers or databases, and it never reads an external E2E_BASE_URL.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
UI_DIR="$REPO_ROOT/tests/ui"
# shellcheck source=lib/ui-smoke-report.sh
. "$SCRIPT_DIR/lib/ui-smoke-report.sh"
SELECTED_TIER="$(printf '%s' "${SP_UI_TIER:-A}" | tr '[:lower:]' '[:upper:]')"
case "$SELECTED_TIER" in
  A)
    DEFAULT_SUMMARY_ROOT="$UI_DIR/summaries/tier-A"
    ;;
  B)
    # Tier B evidence is retained in a sibling root so Tier A qualification can
    # never accidentally consume, replace or hide it.
    DEFAULT_SUMMARY_ROOT="$UI_DIR/summaries/tier-B"
    ;;
  *)
    echo "ERROR: SP_UI_TIER must be A or B; received '${SP_UI_TIER:-}'" >&2
    exit 2
    ;;
esac
SP_UI_TIER="$SELECTED_TIER"
export SP_UI_TIER
SUMMARY_ROOT="${SP_UI_SUMMARY_DIR:-$DEFAULT_SUMMARY_ROOT}"
RUN_STARTED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
FALLBACK_RUN_ID="ui-smoke.setup-$(date -u +%Y%m%dT%H%M%SZ)-$$"
DOCKER_BIN="${DOCKER_BIN:-docker}"
PHP_IMAGE="${PHP_IMAGE:-php-testing-composer:latest}"
DB_IMAGE="${SP_UI_DB_IMAGE:-mariadb:10.3}"
KEEP=0

usage() {
  sed -n '2,17p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
}

while [ "$#" -gt 0 ]; do
  case "$1" in
    --keep)
      KEEP=1
      shift
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *)
      echo "ERROR: unknown argument: $1" >&2
      usage >&2
      exit 2
      ;;
  esac
done

fail() {
  echo "ERROR: $*" >&2
  exit 1
}

# The execution switch is intentionally strict: only the exact value "true"
# enables a real run. A skipped run performs no provisioning, writes no
# qualification evidence and must never be read as a complete Tier A run.
if [ "${TESTS_UI_ENABLED:-}" != "true" ]; then
  echo "UI smoke skipped: TESTS_UI_ENABLED is not 'true'."
  echo "No provisioning was performed and no Tier A qualification evidence was produced."
  exit 0
fi

require_file() {
  [ -e "$1" ] || fail "$2"
}

require_executable() {
  local target=$1
  local message=$2

  case "$target" in
    */*)
      [ -x "$target" ] || fail "$message"
      ;;
    *)
      command -v "$target" >/dev/null 2>&1 || fail "$message"
      ;;
  esac
}

setup_report() {
  local status=$1

  ui_smoke_setup_report \
    "$status" \
    "${RUN_ROOT:-}/output" \
    "${RUN_ROOT:-}" \
    "$SUMMARY_ROOT" \
    "$UI_DIR" \
    "${RUN_ID:-$FALLBACK_RUN_ID}" \
    "$RUN_STARTED_AT"
}

cleanup() {
  local status=$?

  trap - EXIT INT TERM

  if [ -n "${RUN_ROOT:-}" ]; then
    ui_smoke_remove_error_context "$RUN_ROOT/output"
  fi

  if ! setup_report "$status"; then
    echo "ERROR: failed to write the setup-failure report" >&2
    status=1
  fi

  if [ "$KEEP" -eq 1 ]; then
    echo "Kept run resources for inspection:"
    echo "  run root:  ${RUN_ROOT:-<none>}"
    echo "  network:   ${NETWORK_NAME:-<none>}"
    echo "  containers:${WEB_CONTAINER:-<none>} ${DB_CONTAINER:-<none>}"
    exit "$status"
  fi

  if [ -n "${WEB_CONTAINER:-}" ]; then
    "$DOCKER_BIN" rm -f -v "$WEB_CONTAINER" >/dev/null 2>&1 || true
  fi
  if [ -n "${DB_CONTAINER:-}" ]; then
    "$DOCKER_BIN" rm -f -v "$DB_CONTAINER" >/dev/null 2>&1 || true
  fi
  if [ -n "${NETWORK_NAME:-}" ]; then
    "$DOCKER_BIN" network rm "$NETWORK_NAME" >/dev/null 2>&1 || true
  fi
  if [ -n "${RUN_ROOT:-}" ] && [ -d "$RUN_ROOT" ]; then
    case "$RUN_ROOT" in
      "${TMPDIR:-/tmp}"/sp-ui-smoke.*)
        find "$RUN_ROOT" -mindepth 1 -delete
        rmdir "$RUN_ROOT"
        ;;
      *)
        echo "WARNING: refusing to remove unexpected run root: $RUN_ROOT" >&2
        ;;
    esac
  fi

  exit "$status"
}

trap cleanup EXIT INT TERM

if [ -n "${E2E_BASE_URL:-}" ]; then
  fail "E2E_BASE_URL is runner-owned; unset it and start the suite through scripts/ui-smoke.sh"
fi

require_executable "$DOCKER_BIN" "Docker wrapper not found: $DOCKER_BIN"
command -v node >/dev/null 2>&1 || fail "Node.js 20+ is required on the host"
command -v git >/dev/null 2>&1 || fail "git is required on the host"

"$DOCKER_BIN" image inspect "$PHP_IMAGE" >/dev/null 2>&1 \
  || fail "PHP image not found: $PHP_IMAGE (build it with explicit approval: make -C scripts/docker build)"
"$DOCKER_BIN" image inspect "$DB_IMAGE" >/dev/null 2>&1 \
  || fail "Database image not found: $DB_IMAGE (pull it with explicit approval; this runner never pulls images)"

require_file "$REPO_ROOT/vendor/autoload.php" "Composer dependencies missing: source .ai-cli/env.sh and run '\$PHP_BIN /usr/bin/composer install --prefer-dist --no-interaction --no-plugins --no-scripts'"
require_file "$REPO_ROOT/node_modules/.bin/esbuild" "Root asset tooling missing: run 'npm ci' in the repository root"
require_file "$REPO_ROOT/public/js/app-main.min.js" "Built assets missing: run 'bash scripts/build-assets.sh'"
require_file "$UI_DIR/fixtures/e2e-fixture.json" "Synthetic E2E fixture definition missing"
require_executable "$UI_DIR/node_modules/.bin/playwright" "UI harness missing: run 'npm ci' in tests/ui"
CHROMIUM_EXECUTABLE="${SP_UI_CHROMIUM_EXECUTABLE:-}"
if [ -n "$CHROMIUM_EXECUTABLE" ]; then
  require_executable "$CHROMIUM_EXECUTABLE" "Explicit Chromium executable not found: $CHROMIUM_EXECUTABLE"
else
  [ -d "$UI_DIR/.browsers" ] \
    || fail "Pinned Chromium missing: run 'cd tests/ui && ./node_modules/.bin/playwright install chromium'"
fi

echo "Running the UI harness unit tests..."
(
  cd "$UI_DIR"
  node --test src/*.test.mjs
)

fingerprint() {
  if [ -d "$1" ]; then
    node "$UI_DIR/src/fingerprint.mjs" "$1"
  else
    printf '{"root":"%s","digest":"missing","count":0}\n' "$1"
  fi
}

DEVELOPER_CONFIG_BEFORE="$(fingerprint "$REPO_ROOT/app/config")"
DEVELOPER_CACHE_BEFORE="$(fingerprint "$REPO_ROOT/app/cache")"
DEVELOPER_TEMP_BEFORE="$(fingerprint "$REPO_ROOT/app/temp")"

UMASK_PREVIOUS="$(umask)"
umask 077

RUN_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/sp-ui-smoke.XXXXXX")"
RUN_ROOT="$(cd "$RUN_ROOT" && pwd -P)"
RUN_ID="$(basename "$RUN_ROOT")-$(date -u +%Y%m%dT%H%M%SZ)"
RUN_HEX="$(node -e "process.stdout.write(require('node:crypto').randomBytes(6).toString('hex'))")"
DATABASE_NAME="sp_ui_${RUN_HEX}"
PORT="$(node "$UI_DIR/src/find-free-port.mjs")"
ORIGIN="http://127.0.0.1:${PORT}"
NETWORK_NAME="sp-ui-net-${RUN_HEX}"
WEB_CONTAINER="sp-ui-web-${RUN_HEX}"
DB_CONTAINER="sp-ui-db-${RUN_HEX}"

mkdir -p \
  "$RUN_ROOT/config" \
  "$RUN_ROOT/cache" \
  "$RUN_ROOT/temp" \
  "$RUN_ROOT/sessions" \
  "$RUN_ROOT/output" \
  "$RUN_ROOT/env" \
  "$RUN_ROOT/app-cache" \
  "$RUN_ROOT/app-temp"

printf '%s\n' "$RUN_ID" > "$RUN_ROOT/owner"

PREFLIGHT_ARGS=(
  --run-root "$RUN_ROOT"
  --repo-root "$REPO_ROOT"
  --database "$DATABASE_NAME"
  --origin "$ORIGIN"
)

for owned in config cache temp sessions output env app-cache app-temp; do
  PREFLIGHT_ARGS+=(--owned "$RUN_ROOT/$owned")
done

node "$UI_DIR/src/preflight.mjs" "${PREFLIGHT_ARGS[@]}"

for marker_dir in config cache sessions app-cache app-temp; do
  printf '%s\n' "$RUN_ID" > "$RUN_ROOT/$marker_dir/.sp-ui-owner"
done

DB_PASSWORD="$(node -e "process.stdout.write(require('node:crypto').randomBytes(24).toString('base64url'))")"
FIXTURE_FILE="$UI_DIR/fixtures/e2e-fixture.json"

fixture_value() {
  node -e '
    const fs = require("node:fs");
    const data = JSON.parse(fs.readFileSync(process.argv[1], "utf8"));
    const value = process.argv[2].split(".").reduce((item, key) => item?.[key], data);

    if (typeof value !== "string") process.exit(1);

    process.stdout.write(value);
  ' "$FIXTURE_FILE" "$1"
}

ADMIN_LOGIN="$(fixture_value credentials.admin.login)" || fail "Synthetic admin login is missing from the fixture"
ADMIN_PASSWORD="$(fixture_value credentials.admin.password)" || fail "Synthetic admin password is missing from the fixture"
MASTER_PASSWORD="$(fixture_value credentials.admin.masterPassword)" || fail "Synthetic admin Master Password is missing from the fixture"

{
  printf 'MARIADB_ROOT_PASSWORD=%s\n' "$DB_PASSWORD"
  printf 'SP_UI_RUN_DATABASE=%s\n' "$DATABASE_NAME"
} > "$RUN_ROOT/env/db.env"

{
  printf 'ADMIN_LOGIN=%s\n' "$ADMIN_LOGIN"
  printf 'ADMIN_PASSWORD=%s\n' "$ADMIN_PASSWORD"
  printf 'DATABASE_HOST=db\n'
  printf 'DATABASE_NAME=%s\n' "$DATABASE_NAME"
  printf 'DATABASE_USER=root\n'
  printf 'DATABASE_PASSWORD=%s\n' "$DB_PASSWORD"
  printf 'MASTER_PASSWORD=%s\n' "$MASTER_PASSWORD"
  printf 'HOSTING_MODE=0\n'
  printf 'LANGUAGE=en_US\n'
  printf 'INSTALL=1\n'
  printf 'FORCE_INSTALL=1\n'
  printf 'CONFIG_PATH=%s/config\n' "$RUN_ROOT"
  printf 'CONFIG_FILE=%s/config/config.xml\n' "$RUN_ROOT"
  printf 'CACHE_PATH=%s/cache\n' "$RUN_ROOT"
  printf 'LOG_FILE=%s/config/syspass.log\n' "$RUN_ROOT"
  printf 'SP_UI_FIXTURE_FILE=%s\n' "$FIXTURE_FILE"
  printf 'SP_UI_RUN_ROOT=%s\n' "$RUN_ROOT"
  printf 'SP_UI_RUN_ID=%s\n' "$RUN_ID"
} > "$RUN_ROOT/env/install.env"

{
  printf 'CONFIG_PATH=%s/config\n' "$RUN_ROOT"
  printf 'CONFIG_FILE=%s/config/config.xml\n' "$RUN_ROOT"
  printf 'CACHE_PATH=%s/cache\n' "$RUN_ROOT"
  printf 'LOG_FILE=%s/config/syspass.log\n' "$RUN_ROOT"
  printf 'DEBUG=0\n'
  printf 'SP_UI_RUN_ROOT=%s\n' "$RUN_ROOT"
  printf 'SP_UI_RUN_ID=%s\n' "$RUN_ID"
} > "$RUN_ROOT/env/web.env"

printf '# Runner-owned environment file mounted over the repository .env.\n' > "$RUN_ROOT/env/app.env"

echo "Run id:    $RUN_ID"
echo "Origin:    $ORIGIN"
echo "Database:  $DATABASE_NAME (not precreated)"
echo "Run root:  $RUN_ROOT"

"$DOCKER_BIN" network create "$NETWORK_NAME" >/dev/null

"$DOCKER_BIN" run -d \
  --name "$DB_CONTAINER" \
  --network "$NETWORK_NAME" \
  --network-alias db \
  --env-file "$RUN_ROOT/env/db.env" \
  "$DB_IMAGE" >/dev/null

echo "Waiting for MariaDB to accept TCP connections from the application network..."
DB_READY=0
for _ in $(seq 1 120); do
  if "$DOCKER_BIN" run --rm \
    --network "$NETWORK_NAME" \
    --env-file "$RUN_ROOT/env/db.env" \
    -v "$REPO_ROOT:$REPO_ROOT" \
    -w "$REPO_ROOT" \
    "$PHP_IMAGE" \
    php "$REPO_ROOT/tests/ui/server/check-db.php" >/dev/null 2>&1
  then
    DB_READY=1
    break
  fi
  sleep 1
done

[ "$DB_READY" -eq 1 ] || fail "MariaDB did not accept TCP connections from the application network"
# end readiness probe

if "$DOCKER_BIN" exec -i "$DB_CONTAINER" sh -s >/dev/null 2>&1 <<'EOS'
mysql -uroot -p"$MARIADB_ROOT_PASSWORD" -N -e "SHOW DATABASES LIKE '${SP_UI_RUN_DATABASE}'" 2>/dev/null | grep -q .
EOS
then
  fail "Database already exists before installation: $DATABASE_NAME"
fi

PHP_ARGS=(
  --rm
  --network "$NETWORK_NAME"
  --user "$(id -u):$(id -g)"
  -e HOME=/tmp
  --env-file "$RUN_ROOT/env/install.env"
  -w "$REPO_ROOT"
  -v "$REPO_ROOT:$REPO_ROOT"
  -v "$RUN_ROOT:$RUN_ROOT"
  -v "$RUN_ROOT/env/app.env:$REPO_ROOT/.env:ro"
  -v "$RUN_ROOT/app-cache:$REPO_ROOT/app/cache"
  -v "$RUN_ROOT/app-temp:$REPO_ROOT/app/temp"
  "$PHP_IMAGE"
  php
  -d "session.save_path=$RUN_ROOT/sessions"
  -d "upload_tmp_dir=$RUN_ROOT/temp"
  -d "error_reporting=E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED"
)

# This intentionally uses the pinned image through $DOCKER_BIN rather than the
# shared $PHP_BIN wrapper: the wrapper forwards neither CONFIG_PATH nor the
# installer variables and mounts only the repository.

echo "Verifying effective PHP paths..."
"$DOCKER_BIN" run "${PHP_ARGS[@]}" "$REPO_ROOT/tests/ui/server/verify-paths.php"

echo "Installing sysPass into the fresh database..."
"$DOCKER_BIN" run "${PHP_ARGS[@]}" cli.php sp:install

[ -s "$RUN_ROOT/config/config.xml" ] || fail "Installer did not create the run-owned configuration"

if ! "$DOCKER_BIN" exec -i "$DB_CONTAINER" sh -s >/dev/null 2>&1 <<'EOS'
mysql -uroot -p"$MARIADB_ROOT_PASSWORD" -N -e "SHOW DATABASES LIKE '${SP_UI_RUN_DATABASE}'" 2>/dev/null | grep -q .
EOS
then
  fail "Installer did not create the run-owned database: $DATABASE_NAME"
fi

echo "Applying the synthetic E2E fixture..."
"$DOCKER_BIN" run "${PHP_ARGS[@]}" tests/ui/fixtures/seed.php --reset
"$DOCKER_BIN" run "${PHP_ARGS[@]}" tests/ui/fixtures/seed.php --verify
echo "Re-applying the synthetic E2E fixture to prove reset is stable..."
"$DOCKER_BIN" run "${PHP_ARGS[@]}" tests/ui/fixtures/seed.php --reset
"$DOCKER_BIN" run "${PHP_ARGS[@]}" tests/ui/fixtures/seed.php --verify

echo "Starting the run-owned web server..."
"$DOCKER_BIN" run -d \
  --name "$WEB_CONTAINER" \
  --network "$NETWORK_NAME" \
  --user "$(id -u):$(id -g)" \
  -e HOME=/tmp \
  --env-file "$RUN_ROOT/env/web.env" \
  -w "$REPO_ROOT" \
  -p "127.0.0.1:${PORT}:8080" \
  -v "$REPO_ROOT:$REPO_ROOT" \
  -v "$RUN_ROOT:$RUN_ROOT" \
  -v "$RUN_ROOT/env/app.env:$REPO_ROOT/.env:ro" \
  -v "$RUN_ROOT/app-cache:$REPO_ROOT/app/cache" \
  -v "$RUN_ROOT/app-temp:$REPO_ROOT/app/temp" \
  "$PHP_IMAGE" \
  php \
  -d "session.save_path=$RUN_ROOT/sessions" \
  -d "upload_tmp_dir=$RUN_ROOT/temp" \
  -d "error_reporting=E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" \
  -S "0.0.0.0:8080" \
  -t "$REPO_ROOT" \
  "$REPO_ROOT/tests/ui/server/router.php" >/dev/null

node "$UI_DIR/src/wait-for-http.mjs" \
  --url "http://127.0.0.1:${PORT}/index.php?r=login/index" \
  --marker 'id="box-login"' \
  --timeout 120

echo "Running the UI smoke suite..."
# The setup window for CI ends here, after provisioning and before any spec
# runs. Local runs print the same pre-spec stamp so a manual ci-report can reuse
# exact setup bounds instead of guessing.
SPECS_STARTED_MS="$(date +%s%3N)"
if [ -n "${GITHUB_ENV:-}" ]; then
  printf 'UI_SMOKE_SPECS_STARTED_MS=%s\n' "$SPECS_STARTED_MS" >> "$GITHUB_ENV"
fi
printf 'UI_SMOKE_SPECS_STARTED_MS=%s\n' "$SPECS_STARTED_MS"
PLAYWRIGHT_STATUS=0
set +e
(
  cd "$UI_DIR"
  SP_UI_BASE_URL="$ORIGIN" \
  SP_UI_RUN_ROOT="$RUN_ROOT" \
  SP_UI_RUN_ID="$RUN_ID" \
  SP_UI_OUTPUT_DIR="$RUN_ROOT/output" \
  SP_UI_REPO_ROOT="$REPO_ROOT" \
  SP_UI_CHROMIUM_EXECUTABLE="$CHROMIUM_EXECUTABLE" \
  SP_UI_TIER="$SELECTED_TIER" \
  SP_UI_SPECS="${SP_UI_SPECS:-}" \
  SP_UI_LOCAL_TRACE="${SP_UI_LOCAL_TRACE:-0}" \
  SP_UI_DIAGNOSTIC_RETRY="${SP_UI_DIAGNOSTIC_RETRY:-0}" \
  CI="${CI:-false}" \
  PLAYWRIGHT_NO_COPY_PROMPT=1 \
  PLAYWRIGHT_BROWSERS_PATH="$UI_DIR/.browsers" \
  ./node_modules/.bin/playwright test --config=playwright.config.ts
)
PLAYWRIGHT_STATUS=$?
set -e

if ! ui_smoke_finalize_report "$RUN_ROOT/output" "$SUMMARY_ROOT" "$UI_DIR" "$RUN_ID"; then
  echo "ERROR: run artifacts were not retained and verified" >&2
  exit 1
fi

if [ "$PLAYWRIGHT_STATUS" -ne 0 ]; then
  echo "UI smoke run failed with status $PLAYWRIGHT_STATUS" >&2
  exit "$PLAYWRIGHT_STATUS"
fi

DEVELOPER_CONFIG_AFTER="$(fingerprint "$REPO_ROOT/app/config")"
DEVELOPER_CACHE_AFTER="$(fingerprint "$REPO_ROOT/app/cache")"
DEVELOPER_TEMP_AFTER="$(fingerprint "$REPO_ROOT/app/temp")"

[ "$DEVELOPER_CONFIG_BEFORE" = "$DEVELOPER_CONFIG_AFTER" ] \
  || fail "Developer app/config changed during the run"
[ "$DEVELOPER_CACHE_BEFORE" = "$DEVELOPER_CACHE_AFTER" ] \
  || fail "Developer app/cache changed during the run"
[ "$DEVELOPER_TEMP_BEFORE" = "$DEVELOPER_TEMP_AFTER" ] \
  || fail "Developer app/temp changed during the run"

umask "$UMASK_PREVIOUS"

echo "UI smoke run passed."
