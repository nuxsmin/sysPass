#!/usr/bin/env bash
# ui-smoke — isolated, single-command UI smoke run for sysPass.
#
# Usage:
#   ./scripts/ui-smoke.sh [--keep]
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
DOCKER_BIN="${DOCKER_BIN:-docker}"
PHP_IMAGE="${PHP_IMAGE:-php-testing-composer:latest}"
DB_IMAGE="${SP_UI_DB_IMAGE:-mariadb:10.3}"
KEEP=0

usage() {
  sed -n '2,14p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
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

require_file() {
  [ -e "$1" ] || fail "$2"
}

require_executable() {
  [ -x "$1" ] || fail "$2"
}

cleanup() {
  local status=$?

  trap - EXIT INT TERM

  if [ "$KEEP" -eq 1 ]; then
    echo "Kept run resources for inspection:"
    echo "  run root:  ${RUN_ROOT:-<none>}"
    echo "  network:   ${NETWORK_NAME:-<none>}"
    echo "  containers:${WEB_CONTAINER:-<none>} ${DB_CONTAINER:-<none>}"
    exit "$status"
  fi

  if [ -n "${WEB_CONTAINER:-}" ]; then
    "$DOCKER_BIN" rm -f "$WEB_CONTAINER" >/dev/null 2>&1 || true
  fi
  if [ -n "${DB_CONTAINER:-}" ]; then
    "$DOCKER_BIN" rm -f "$DB_CONTAINER" >/dev/null 2>&1 || true
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
  || fail "PHP image not found: $PHP_IMAGE (build it with explicit approval: make -C .ai-cli/docker build)"
"$DOCKER_BIN" image inspect "$DB_IMAGE" >/dev/null 2>&1 \
  || fail "Database image not found: $DB_IMAGE (pull it with explicit approval; this runner never pulls images)"

require_file "$REPO_ROOT/vendor/autoload.php" "Composer dependencies missing: source .ai-cli/env.sh and run '\$PHP_BIN /usr/bin/composer install --prefer-dist --no-interaction --no-plugins --no-scripts'"
require_file "$REPO_ROOT/node_modules/.bin/esbuild" "Root asset tooling missing: run 'npm ci' in the repository root"
require_file "$REPO_ROOT/public/js/app-main.min.js" "Built assets missing: run 'bash scripts/build-assets.sh'"
require_executable "$UI_DIR/node_modules/.bin/playwright" "UI harness missing: run 'npm ci' in tests/ui"
CHROMIUM_EXECUTABLE="${SP_UI_CHROMIUM_EXECUTABLE:-}"
if [ -n "$CHROMIUM_EXECUTABLE" ]; then
  require_executable "$CHROMIUM_EXECUTABLE" "Explicit Chromium executable not found: $CHROMIUM_EXECUTABLE"
else
  [ -d "$UI_DIR/.browsers" ] \
    || fail "Pinned Chromium missing: run 'cd tests/ui && ./node_modules/.bin/playwright install chromium'"
fi

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
ADMIN_PASSWORD="$(node -e "process.stdout.write(require('node:crypto').randomBytes(24).toString('base64url'))")"
MASTER_PASSWORD="$(node -e "process.stdout.write(require('node:crypto').randomBytes(24).toString('base64url'))")"

{
  printf 'MARIADB_ROOT_PASSWORD=%s\n' "$DB_PASSWORD"
  printf 'SP_UI_RUN_DATABASE=%s\n' "$DATABASE_NAME"
} > "$RUN_ROOT/env/db.env"

{
  printf 'ADMIN_LOGIN=E2E_Admin\n'
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
(
  cd "$UI_DIR"
  SP_UI_BASE_URL="$ORIGIN" \
  SP_UI_RUN_ROOT="$RUN_ROOT" \
  SP_UI_RUN_ID="$RUN_ID" \
  SP_UI_OUTPUT_DIR="$RUN_ROOT/output" \
  SP_UI_CHROMIUM_EXECUTABLE="$CHROMIUM_EXECUTABLE" \
  PLAYWRIGHT_BROWSERS_PATH="$UI_DIR/.browsers" \
  ./node_modules/.bin/playwright test --config=playwright.config.ts
)

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
