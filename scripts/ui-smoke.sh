#!/usr/bin/env bash
# ui-smoke — isolated, single-command UI smoke run for sysPass.
#
# Usage:
#   TESTS_UI_ENABLED=true ./scripts/ui-smoke.sh [--keep]
#   TESTS_UI_ENABLED=true SP_UI_TIER=B ./scripts/ui-smoke.sh
#   TESTS_UI_ENABLED=true SP_UI_TIER=INSTALL ./scripts/ui-smoke.sh
#   TESTS_UI_ENABLED=true SP_UI_TIER=A SP_UI_INSTALL=1 ./scripts/ui-smoke.sh
#
# TESTS_UI_ENABLED must be the exact value "true"; otherwise the runner exits
# without provisioning anything and without producing Tier A evidence.
#
# SP_UI_TIER defaults to A. SP_UI_INSTALL=1 explicitly adds the installer
# phase; a combined selection runs the seeded specs first and the wizard last.
# The installer uses a separate run-owned instance and always retries: 0. A
# rerun recreates the entire wizard instance/database instead of retrying an
# already installed one.
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
  INSTALL)
    # The installer is a separate stateful selection with its own evidence root.
    DEFAULT_SUMMARY_ROOT="$UI_DIR/summaries/tier-install"
    ;;
  *)
    echo "ERROR: SP_UI_TIER must be A, B or INSTALL; received '${SP_UI_TIER:-}'" >&2
    exit 2
    ;;
esac
SP_UI_TIER="$SELECTED_TIER"
export SP_UI_TIER
SP_UI_INSTALL="${SP_UI_INSTALL:-0}"
export SP_UI_INSTALL
INSTALL_DEFAULT_SUMMARY_ROOT="$UI_DIR/summaries/tier-install"
RUN_PLAN_JSON="$(node "$UI_DIR/src/install-selection.mjs" \
  --tier "$SELECTED_TIER" \
  --install "$SP_UI_INSTALL" \
  --diagnostic-retry "${SP_UI_DIAGNOSTIC_RETRY:-0}" \
  --summary-dir "${SP_UI_SUMMARY_DIR:-}" \
  --install-summary-dir "${SP_UI_INSTALL_SUMMARY_DIR:-}" \
  --default-summary-root "$DEFAULT_SUMMARY_ROOT" \
  --default-install-summary-root "$INSTALL_DEFAULT_SUMMARY_ROOT")" || {
  echo "ERROR: invalid UI smoke selection" >&2
  exit 2
}
SEEDED_TIER="$(node -e 'const p=JSON.parse(process.argv[1]);process.stdout.write(p.seededTier??"")' "$RUN_PLAN_JSON")"
INSTALL_SELECTED="$(node -e 'const p=JSON.parse(process.argv[1]);process.stdout.write(p.installSelected?"1":"0")' "$RUN_PLAN_JSON")"
INSTALL_RETRIES="$(node -e 'const p=JSON.parse(process.argv[1]);process.stdout.write(String(p.retries.install))' "$RUN_PLAN_JSON")"
SUMMARY_ROOT="$(node -e 'const p=JSON.parse(process.argv[1]);process.stdout.write(p.summaryRoot??"")' "$RUN_PLAN_JSON")"
INSTALL_SUMMARY_ROOT="$(node -e 'const p=JSON.parse(process.argv[1]);process.stdout.write(p.installSummaryRoot??"")' "$RUN_PLAN_JSON")"
RUN_STARTED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
FALLBACK_RUN_ID="ui-smoke.setup-$(date -u +%Y%m%dT%H%M%SZ)-$$"
DOCKER_BIN="${DOCKER_BIN:-docker}"
PHP_IMAGE="${PHP_IMAGE:-php-testing-composer:latest}"
DB_IMAGE="${SP_UI_DB_IMAGE:-mariadb:10.3}"
KEEP=0
INSTALL_ROOT=""
INSTALL_NETWORK_NAME=""
INSTALL_DB_CONTAINER=""
INSTALL_WEB_CONTAINER=""
SEEDED_STATUS=0
INSTALL_STATUS=0
SEEDED_SETUP_ERROR=""
INSTALL_SETUP_ERROR=""
PROVISIONING_STARTED=0
FINALIZED=0

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

  # Post-provisioning failures must still compare the developer fingerprints.
  # The explicit flow calls finalize_run; this catches an unexpected command
  # failure so only failures before provisioning may skip the comparison.
  if [ "$PROVISIONING_STARTED" -eq 1 ] && [ "$FINALIZED" -ne 1 ]; then
    finalize_run || status=$?
  fi

  if [ -n "${RUN_ROOT:-}" ]; then
    ui_smoke_remove_error_context "$RUN_ROOT/output"
    ui_smoke_remove_error_context "$RUN_ROOT/output-install"
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
    echo "  installer: ${INSTALL_NETWORK_NAME:-<none>} ${INSTALL_WEB_CONTAINER:-<none>} ${INSTALL_DB_CONTAINER:-<none>}"
    exit "$status"
  fi

  if [ -n "${INSTALL_WEB_CONTAINER:-}" ]; then
    "$DOCKER_BIN" rm -f -v "$INSTALL_WEB_CONTAINER" >/dev/null 2>&1 || true
  fi
  if [ -n "${INSTALL_DB_CONTAINER:-}" ]; then
    "$DOCKER_BIN" rm -f -v "$INSTALL_DB_CONTAINER" >/dev/null 2>&1 || true
  fi
  if [ -n "${INSTALL_NETWORK_NAME:-}" ]; then
    "$DOCKER_BIN" network rm "$INSTALL_NETWORK_NAME" >/dev/null 2>&1 || true
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

# The single finalization point for every post-provisioning outcome. It always
# takes the after-fingerprints, compares them with the before-fingerprints and
# asks the report library to force setup-failed evidence when they drift or when
# a setup/isolation failure was accumulated without drift.
finalize_run() {
  local config_after
  local cache_after
  local temp_after
  local status=0

  config_after="$(fingerprint "$REPO_ROOT/app/config")" || config_after="unavailable"
  cache_after="$(fingerprint "$REPO_ROOT/app/cache")" || cache_after="unavailable"
  temp_after="$(fingerprint "$REPO_ROOT/app/temp")" || temp_after="unavailable"

  ui_smoke_finalize_run \
    "$SEEDED_STATUS" "$INSTALL_STATUS" "$INSTALL_SELECTED" "$SEEDED_TIER" \
    "$RUN_ROOT" "$SUMMARY_ROOT" "$INSTALL_SUMMARY_ROOT" "$UI_DIR" \
    "$RUN_ID" "$RUN_STARTED_AT" \
    "$DEVELOPER_CONFIG_BEFORE" "$config_after" \
    "$DEVELOPER_CACHE_BEFORE" "$cache_after" \
    "$DEVELOPER_TEMP_BEFORE" "$temp_after" \
    "$SEEDED_SETUP_ERROR" "$INSTALL_SETUP_ERROR" || status=$?

  FINALIZED=1

  return "$status"
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

PROVISIONING_STARTED=1

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

web_server_failure_report() {
  [ -n "${WEB_CONTAINER:-}" ] || return 0

  "$DOCKER_BIN" inspect \
    --format 'web container: status={{.State.Status}} exit={{.State.ExitCode}} error={{.State.Error}}' \
    "$WEB_CONTAINER" >&2 2>&1 || true

  if [ "${SP_UI_DEBUG:-0}" = "1" ]; then
    "$DOCKER_BIN" logs --tail 80 "$WEB_CONTAINER" 2>&1 \
      | sed -e "s|${DB_PASSWORD}|<redacted>|g" -e "s|${MASTER_PASSWORD}|<redacted>|g" >&2 || true
  fi
}

echo "Starting the run-owned web server..."
set +e
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
WEB_START_STATUS=$?
set -e
if [ "$WEB_START_STATUS" -ne 0 ]; then
  echo "Web server container failed to start with status $WEB_START_STATUS" >&2
  web_server_failure_report
  exit "$WEB_START_STATUS"
fi

echo "Waiting for the run-owned web server..."
set +e
node "$UI_DIR/src/wait-for-http.mjs" \
  --url "http://127.0.0.1:${PORT}/index.php?r=login/index" \
  --marker 'id="box-login"' \
  --timeout 120
WEB_READY_STATUS=$?
set -e
if [ "$WEB_READY_STATUS" -ne 0 ]; then
  echo "Web server readiness failed with status $WEB_READY_STATUS" >&2
  web_server_failure_report
  exit "$WEB_READY_STATUS"
fi

echo "Running the UI smoke suite..."
# The setup window for CI ends here, after seeded provisioning and before any
# seeded spec runs. For an installer-only selection the stamp is emitted after
# the wizard instance is provisioned, so its setup is not silently excluded.
SPECS_STARTED_MS="$(date +%s%3N)"
if [ -n "$SEEDED_TIER" ]; then
  if [ -n "${GITHUB_ENV:-}" ]; then
    printf 'UI_SMOKE_SPECS_STARTED_MS=%s\n' "$SPECS_STARTED_MS" >> "$GITHUB_ENV"
  fi
  printf 'UI_SMOKE_SPECS_STARTED_MS=%s\n' "$SPECS_STARTED_MS"
fi

run_playwright() {
  local output_dir=$1
  local tier=$2
  local base_url=$3
  local run_root=$4

  set +e
  (
    cd "$UI_DIR"
    export SP_UI_BASE_URL="$base_url"
    export SP_UI_RUN_ROOT="$run_root"
    export SP_UI_RUN_ID="$RUN_ID"
    export SP_UI_OUTPUT_DIR="$output_dir"
    export SP_UI_REPO_ROOT="$REPO_ROOT"
    export SP_UI_CHROMIUM_EXECUTABLE="$CHROMIUM_EXECUTABLE"
    export SP_UI_TIER="$tier"
    export SP_UI_SPECS="${SP_UI_SPECS:-}"
    export SP_UI_LOCAL_TRACE="${SP_UI_LOCAL_TRACE:-0}"
    export SP_UI_DIAGNOSTIC_RETRY="${SP_UI_DIAGNOSTIC_RETRY:-0}"
    export CI="${CI:-false}"
    export PLAYWRIGHT_NO_COPY_PROMPT=1
    export PLAYWRIGHT_BROWSERS_PATH="$UI_DIR/.browsers"

    if [ "$tier" = "INSTALL" ]; then
      export SP_UI_INSTALL_DB_HOST="$INSTALL_DB_HOST"
      export SP_UI_INSTALL_DB_NAME="$INSTALL_DATABASE_NAME"
      export SP_UI_INSTALL_DB_PASSWORD="$INSTALL_DB_PASSWORD"
      export SP_UI_INSTALL_SITE_LANG="en_US"
      export SP_UI_INSTALL_FAULT="${SP_UI_INSTALL_FAULT:-}"
    fi

    ./node_modules/.bin/playwright test --config=playwright.config.ts
  )
  PLAYWRIGHT_STATUS=$?
  set -e
}

database_exists() {
  local container=$1
  local database=$2
  local password=$3

  "$DOCKER_BIN" exec -i "$container" sh -s >/dev/null 2>&1 <<EOS
mysql -uroot -p"$password" -N -e "SHOW DATABASES LIKE '$database'" 2>/dev/null | grep -q .
EOS
}

seeded_database_digest() {
  local output_file=$1

  "$DOCKER_BIN" run --rm \
    --network "$NETWORK_NAME" \
    --user "$(id -u):$(id -g)" \
    -e HOME=/tmp \
    --env-file "$RUN_ROOT/env/fingerprint.env" \
    -w "$REPO_ROOT" \
    -v "$REPO_ROOT:$REPO_ROOT" \
    "$PHP_IMAGE" \
    php "$REPO_ROOT/tests/ui/server/db-fingerprint.php" > "$output_file"

  node -e '
    const fs = require("node:fs");
    const data = JSON.parse(fs.readFileSync(process.argv[1], "utf8"));
    process.stdout.write(data.digest);
  ' "$output_file"
}

snapshot_seeded_state() {
  local database_digest=$1
  local output_file=$2

  node "$UI_DIR/src/isolation.mjs" snapshot \
    --run-root "$RUN_ROOT" \
    --database-digest "$database_digest" > "$output_file"
}

reset_install_instance() {
  if [ -n "${INSTALL_WEB_CONTAINER:-}" ]; then
    "$DOCKER_BIN" rm -f -v "$INSTALL_WEB_CONTAINER" >/dev/null 2>&1 || true
  fi
  if [ -n "${INSTALL_DB_CONTAINER:-}" ]; then
    "$DOCKER_BIN" rm -f -v "$INSTALL_DB_CONTAINER" >/dev/null 2>&1 || true
  fi
  if [ -n "${INSTALL_NETWORK_NAME:-}" ]; then
    "$DOCKER_BIN" network rm "$INSTALL_NETWORK_NAME" >/dev/null 2>&1 || true
  fi
  if [ -n "${INSTALL_ROOT:-}" ]; then
    if ! node "$UI_DIR/src/install-lifecycle.mjs" reset --root "$INSTALL_ROOT" >/dev/null; then
      install_fail "failed to destroy the previous wizard workspace"
      return 0
    fi
  fi
}

# Record an installer failure without exiting. The caller returns to the single
# finalization point, which still takes and compares the after-fingerprints.
install_fail() {
  local message="$*"

  INSTALL_STATUS=1

  if [ -n "$INSTALL_SETUP_ERROR" ]; then
    INSTALL_SETUP_ERROR="$INSTALL_SETUP_ERROR; $message"
  else
    INSTALL_SETUP_ERROR="$message"
  fi

  echo "ERROR: $message" >&2
}

prepare_install_instance() {
  # Clean-rerun path: destroy only wizard-owned resources and recreate the
  # entire instance. The browser installer is never retried over an installed
  # instance and never reuses a previous wizard run root.
  INSTALL_ROOT="$(node "$UI_DIR/src/install-layout.mjs" install-root --seeded-root "$RUN_ROOT")"
  reset_install_instance
  if [ "$INSTALL_STATUS" -ne 0 ]; then
    return 0
  fi

  INSTALL_IDENTITY_JSON="$(node "$UI_DIR/src/install-lifecycle.mjs" identity)"
  INSTALL_DATABASE_NAME="$(node -e 'const p=JSON.parse(process.argv[1]);process.stdout.write(p.databaseName)' "$INSTALL_IDENTITY_JSON")"
  INSTALL_NETWORK_NAME="$(node -e 'const p=JSON.parse(process.argv[1]);process.stdout.write(p.networkName)' "$INSTALL_IDENTITY_JSON")"
  INSTALL_DB_CONTAINER="$(node -e 'const p=JSON.parse(process.argv[1]);process.stdout.write(p.dbContainer)' "$INSTALL_IDENTITY_JSON")"
  INSTALL_WEB_CONTAINER="$(node -e 'const p=JSON.parse(process.argv[1]);process.stdout.write(p.webContainer)' "$INSTALL_IDENTITY_JSON")"
  INSTALL_DB_PASSWORD="$(node -e 'const p=JSON.parse(process.argv[1]);process.stdout.write(p.dbPassword)' "$INSTALL_IDENTITY_JSON")"
  INSTALL_PORT="$(node "$UI_DIR/src/find-free-port.mjs")"
  INSTALL_ORIGIN="http://127.0.0.1:${INSTALL_PORT}"
  INSTALL_DB_HOST="db"

  if ! node "$UI_DIR/src/install-layout.mjs" verify \
    --seeded-root "$RUN_ROOT" \
    --install-root "$INSTALL_ROOT" \
    --seeded-database "$DATABASE_NAME" \
    --install-database "$INSTALL_DATABASE_NAME" >/dev/null; then
    install_fail "installer paths or database are not isolated from the seeded instance"
    return 0
  fi

  mkdir -p \
    "$INSTALL_ROOT/config" \
    "$INSTALL_ROOT/cache" \
    "$INSTALL_ROOT/temp" \
    "$INSTALL_ROOT/sessions" \
    "$INSTALL_ROOT/output" \
    "$INSTALL_ROOT/env" \
    "$INSTALL_ROOT/app-cache" \
    "$INSTALL_ROOT/app-temp" \
    "$RUN_ROOT/output-install"

  printf '%s\n' "$RUN_ID" > "$INSTALL_ROOT/owner"

  INSTALL_PREFLIGHT_ARGS=(
    --run-root "$INSTALL_ROOT"
    --repo-root "$REPO_ROOT"
    --database "$INSTALL_DATABASE_NAME"
    --origin "$INSTALL_ORIGIN"
  )

  for owned in config cache temp sessions output env app-cache app-temp; do
    INSTALL_PREFLIGHT_ARGS+=(--owned "$INSTALL_ROOT/$owned")
  done

  node "$UI_DIR/src/preflight.mjs" "${INSTALL_PREFLIGHT_ARGS[@]}"

  for marker_dir in config cache sessions app-cache app-temp; do
    printf '%s\n' "$RUN_ID" > "$INSTALL_ROOT/$marker_dir/.sp-ui-owner"
  done

  {
    printf 'MARIADB_ROOT_PASSWORD=%s\n' "$INSTALL_DB_PASSWORD"
    printf 'SP_UI_RUN_DATABASE=%s\n' "$INSTALL_DATABASE_NAME"
  } > "$INSTALL_ROOT/env/db.env"

  {
    printf 'CONFIG_PATH=%s/config\n' "$INSTALL_ROOT"
    printf 'CONFIG_FILE=%s/config/config.xml\n' "$INSTALL_ROOT"
    printf 'CACHE_PATH=%s/cache\n' "$INSTALL_ROOT"
    printf 'LOG_FILE=%s/config/syspass.log\n' "$INSTALL_ROOT"
    printf 'DEBUG=0\n'
    printf 'SYSPASS_DIR=%s\n' "$REPO_ROOT"
    printf 'SP_UI_RUN_ROOT=%s\n' "$INSTALL_ROOT"
    printf 'SP_UI_RUN_ID=%s\n' "$RUN_ID"
  } > "$INSTALL_ROOT/env/web.env"

  printf '# Runner-owned installer environment file mounted over the repository .env.\n' \
    > "$INSTALL_ROOT/env/app.env"

  "$DOCKER_BIN" network create "$INSTALL_NETWORK_NAME" >/dev/null
  "$DOCKER_BIN" run -d \
    --name "$INSTALL_DB_CONTAINER" \
    --network "$INSTALL_NETWORK_NAME" \
    --network-alias db \
    --env-file "$INSTALL_ROOT/env/db.env" \
    "$DB_IMAGE" >/dev/null

  echo "Waiting for the installer MariaDB to accept connections..."
  INSTALL_DB_READY=0
  for _ in $(seq 1 120); do
    if "$DOCKER_BIN" run --rm \
      --network "$INSTALL_NETWORK_NAME" \
      --env-file "$INSTALL_ROOT/env/db.env" \
      -v "$REPO_ROOT:$REPO_ROOT" \
      -w "$REPO_ROOT" \
      "$PHP_IMAGE" \
      php "$REPO_ROOT/tests/ui/server/check-db.php" >/dev/null 2>&1
    then
      INSTALL_DB_READY=1
      break
    fi
    sleep 1
  done

  if [ "$INSTALL_DB_READY" -ne 1 ]; then
    install_fail "installer MariaDB did not accept TCP connections"
    return 0
  fi

  if database_exists "$INSTALL_DB_CONTAINER" "$INSTALL_DATABASE_NAME" "$INSTALL_DB_PASSWORD"; then
    install_fail "installer database already exists before installation: $INSTALL_DATABASE_NAME"
    return 0
  fi

  INSTALL_PHP_ARGS=(
    --rm
    --network "$INSTALL_NETWORK_NAME"
    --user "$(id -u):$(id -g)"
    -e HOME=/tmp
    --env-file "$INSTALL_ROOT/env/web.env"
    -w "$REPO_ROOT"
    -v "$REPO_ROOT:$REPO_ROOT"
    -v "$INSTALL_ROOT:$INSTALL_ROOT"
    -v "$INSTALL_ROOT/env/app.env:$REPO_ROOT/.env:ro"
    -v "$INSTALL_ROOT/app-cache:$REPO_ROOT/app/cache"
    -v "$INSTALL_ROOT/app-temp:$REPO_ROOT/app/temp"
    "$PHP_IMAGE"
    php
    -d "session.save_path=$INSTALL_ROOT/sessions"
    -d "upload_tmp_dir=$INSTALL_ROOT/temp"
    -d "error_reporting=E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED"
  )

  echo "Verifying effective installer PHP paths..."
  if ! "$DOCKER_BIN" run "${INSTALL_PHP_ARGS[@]}" "$REPO_ROOT/tests/ui/server/verify-paths.php"; then
    install_fail "installer paths are not isolated from the seeded instance"
    return 0
  fi

  CONFIG_EXISTS=0
  [ -e "$INSTALL_ROOT/config/config.xml" ] && CONFIG_EXISTS=1
  APP_CACHE_ENTRIES="$(find "$INSTALL_ROOT/app-cache" -mindepth 1 ! -name '.sp-ui-owner' | wc -l | tr -d ' ')"
  SESSION_ENTRIES="$(find "$INSTALL_ROOT/sessions" -mindepth 1 ! -name '.sp-ui-owner' | wc -l | tr -d ' ')"
  INSTALL_DATABASE_EXISTS=0
  if database_exists "$INSTALL_DB_CONTAINER" "$INSTALL_DATABASE_NAME" "$INSTALL_DB_PASSWORD"; then
    INSTALL_DATABASE_EXISTS=1
  fi

  echo "Verifying the wizard instance is fresh and uninstalled..."
  if ! node "$UI_DIR/src/install-guard.mjs" \
    --installed "$CONFIG_EXISTS" \
    --config-file "$CONFIG_EXISTS" \
    --database "$INSTALL_DATABASE_EXISTS" \
    --app-cache "$APP_CACHE_ENTRIES" \
    --sessions "$SESSION_ENTRIES" \
    --retries "$INSTALL_RETRIES"; then
    install_fail "the wizard instance is not fresh and must not be retried"
    return 0
  fi

  echo "Starting the run-owned installer web server..."
  "$DOCKER_BIN" run -d \
    --name "$INSTALL_WEB_CONTAINER" \
    --network "$INSTALL_NETWORK_NAME" \
    --user "$(id -u):$(id -g)" \
    -e HOME=/tmp \
    --env-file "$INSTALL_ROOT/env/web.env" \
    -w "$REPO_ROOT" \
    -p "127.0.0.1:${INSTALL_PORT}:8080" \
    -v "$REPO_ROOT:$REPO_ROOT" \
    -v "$INSTALL_ROOT:$INSTALL_ROOT" \
    -v "$INSTALL_ROOT/env/app.env:$REPO_ROOT/.env:ro" \
    -v "$INSTALL_ROOT/app-cache:$REPO_ROOT/app/cache" \
    -v "$INSTALL_ROOT/app-temp:$REPO_ROOT/app/temp" \
    "$PHP_IMAGE" \
    php \
    -d "session.save_path=$INSTALL_ROOT/sessions" \
    -d "upload_tmp_dir=$INSTALL_ROOT/temp" \
    -d "error_reporting=E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" \
    -S "0.0.0.0:8080" \
    -t "$REPO_ROOT" \
    "$REPO_ROOT/tests/ui/server/router.php" >/dev/null

  if ! node "$UI_DIR/src/wait-for-http.mjs" \
    --url "http://127.0.0.1:${INSTALL_PORT}/index.php?r=install/index" \
    --marker 'id="frmInstall"' \
    --timeout 120; then
    install_fail "installer page did not become ready"
    return 0
  fi
}

SEEDED_STATUS=0
if [ -n "$SEEDED_TIER" ]; then
  echo "Running seeded Tier ${SEEDED_TIER} specs..."
  run_playwright "$RUN_ROOT/output" "$SEEDED_TIER" "$ORIGIN" "$RUN_ROOT"
  SEEDED_STATUS=$PLAYWRIGHT_STATUS

  if ! ui_smoke_finalize_report "$RUN_ROOT/output" "$SUMMARY_ROOT" "$UI_DIR" "$RUN_ID"; then
    echo "ERROR: run artifacts were not retained and verified" >&2
    SEEDED_STATUS=1
    SEEDED_SETUP_ERROR="run artifacts were not retained and verified"
  fi
else
  echo "Installer-only selection: seeded specs are not run."
  mkdir -p "$RUN_ROOT/output"
fi

INSTALL_STATUS=0
if [ "$INSTALL_SELECTED" -eq 1 ]; then
  # The comparison baseline is taken after the seeded specs (and after their
  # ordinary view/session/audit writes) so those expected writes are not
  # misreported as installer isolation failures.
  {
    printf 'SP_UI_FP_DB_HOST=db\n'
    printf 'SP_UI_FP_DB_NAME=%s\n' "$DATABASE_NAME"
    printf 'SP_UI_FP_DB_USER=root\n'
    printf 'SP_UI_FP_DB_PASSWORD=%s\n' "$DB_PASSWORD"
  } > "$RUN_ROOT/env/fingerprint.env"

  SEEDED_DATABASE_BEFORE="$(seeded_database_digest "$RUN_ROOT/seeded-db-before.json")"
  snapshot_seeded_state "$SEEDED_DATABASE_BEFORE" "$RUN_ROOT/seeded-state-before.json"

  echo "Preparing a separate, uninstalled wizard instance (database ${INSTALL_DATABASE_NAME:-<not yet created>})..."
  prepare_install_instance

  INSTALL_PLAYWRIGHT_RAN=0
  if [ "$INSTALL_STATUS" -eq 0 ]; then
    if [ -z "$SEEDED_TIER" ]; then
      SPECS_STARTED_MS="$(date +%s%3N)"
      if [ -n "${GITHUB_ENV:-}" ]; then
        printf 'UI_SMOKE_SPECS_STARTED_MS=%s\n' "$SPECS_STARTED_MS" >> "$GITHUB_ENV"
      fi
      printf 'UI_SMOKE_SPECS_STARTED_MS=%s\n' "$SPECS_STARTED_MS"
    fi

    echo "Driving the normal browser installer on the separate instance..."
    run_playwright "$RUN_ROOT/output-install" "INSTALL" "$INSTALL_ORIGIN" "$INSTALL_ROOT"
    INSTALL_STATUS=$PLAYWRIGHT_STATUS
    INSTALL_PLAYWRIGHT_RAN=1

    if [ ! -s "$INSTALL_ROOT/config/config.xml" ]; then
      install_fail "the browser installer did not create the run-owned configuration"
    fi

    if ! database_exists "$INSTALL_DB_CONTAINER" "$INSTALL_DATABASE_NAME" "$INSTALL_DB_PASSWORD"; then
      install_fail "the browser installer did not create the run-owned database: $INSTALL_DATABASE_NAME"
    fi

    SEEDED_DATABASE_AFTER="$(seeded_database_digest "$RUN_ROOT/seeded-db-after.json")"
    snapshot_seeded_state "$SEEDED_DATABASE_AFTER" "$RUN_ROOT/seeded-state-after.json"

    echo "Proving the seeded instance is unchanged and still usable..."
    if ! node "$UI_DIR/src/isolation.mjs" compare \
      --before "$RUN_ROOT/seeded-state-before.json" \
      --after "$RUN_ROOT/seeded-state-after.json"; then
      install_fail "the installer selection changed the seeded instance"
    fi

    if ! node "$UI_DIR/src/wait-for-http.mjs" \
      --url "http://127.0.0.1:${PORT}/index.php?r=login/index" \
      --marker 'id="box-login"' \
      --timeout 60; then
      install_fail "the seeded instance is no longer usable after the installer selection"
    fi
  fi

  # Retain the Playwright evidence only when no post-provisioning setup or
  # isolation failure must replace it. Finalization forces setup-failed evidence
  # for an accumulated failure, even when the wizard itself passed.
  if [ "$INSTALL_PLAYWRIGHT_RAN" -eq 1 ] && [ -z "$INSTALL_SETUP_ERROR" ]; then
    if ! ui_smoke_finalize_report "$RUN_ROOT/output-install" "$INSTALL_SUMMARY_ROOT" "$UI_DIR" "$RUN_ID"; then
      echo "ERROR: installer artifacts were not retained and verified" >&2
      install_fail "installer artifacts were not retained and verified"
    fi
  fi

  echo "Installer database created by the wizard: ${INSTALL_DATABASE_NAME:-<not created>}"
  echo "Installer run root: ${INSTALL_ROOT:-<not provisioned>}"
  echo "Installer origin: ${INSTALL_ORIGIN:-<not provisioned>}"
fi

# The single finalization point: it always takes the after-fingerprints,
# compares them and forces setup-failed evidence on drift or on an accumulated
# post-provisioning failure.
FINAL_STATUS=0
finalize_run || FINAL_STATUS=$?

if [ "$FINAL_STATUS" -ne 0 ]; then
  exit "$FINAL_STATUS"
fi

umask "$UMASK_PREVIOUS"

echo "UI smoke run passed."
