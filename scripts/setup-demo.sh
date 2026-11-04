#!/usr/bin/env bash
#
# Build and provision the Docker-based sysPass demo from this checkout.
#
# The stack is defined in build/docker-compose-demo.yml and uses a dedicated
# MariaDB container and volume. A fresh database is installed with the sysPass
# CLI (sp:install), optionally followed by the demo fixtures; application
# settings and admin/master credentials are then applied.
#
# Usage:
#   scripts/setup-demo.sh [--build-image] [--reset] [--no-fixtures]
#
#   --build-image   Rebuild the PHP image with make -C .ai-cli/docker build.
#   --reset         Drop and recreate the application database (destructive).
#   --no-fixtures   Do not load demo fixture data after a fresh install.
#
# Environment overrides:
#   COMPOSE_FILE, PHP_IMAGE, APP_PORT, APP_URL, APP_CONTAINER_NAME,
#   DB_CONTAINER_NAME, DB_NAME, DB_USER, DB_PASS,
#   ADMIN_LOGIN, ADMIN_PASSWORD, MASTER_PASSWORD, APP_DEBUG,
#   APP_SESSION_TIMEOUT, APP_UID, APP_GID, REPO_ROOT

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_FILE="${COMPOSE_FILE:-$ROOT/build/docker-compose-demo.yml}"
PHP_IMAGE="${PHP_IMAGE:-php-testing-composer:latest}"
APP_CONTAINER_NAME="${APP_CONTAINER_NAME:-syspass-uw-app}"
DB_CONTAINER_NAME="${DB_CONTAINER_NAME:-syspass-uw-db}"
APP_PORT="${APP_PORT:-8080}"
APP_URL="${APP_URL:-http://localhost:$APP_PORT}"
DB_NAME="${DB_NAME:-syspass}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-syspass}"
ADMIN_LOGIN="${ADMIN_LOGIN:-admin}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-admin123}"
MASTER_PASSWORD="${MASTER_PASSWORD:-12345678900}"
APP_DEBUG="${APP_DEBUG:-0}"
APP_SESSION_TIMEOUT="${APP_SESSION_TIMEOUT:-300}"
APP_UID="${APP_UID:-$(id -u)}"
APP_GID="${APP_GID:-$(id -g)}"
REPO_ROOT="${REPO_ROOT:-$ROOT}"
DOCKER="${DOCKER_BIN:-docker}"

export COMPOSE_FILE PHP_IMAGE APP_CONTAINER_NAME DB_CONTAINER_NAME
export APP_PORT APP_URL DB_NAME DB_PASS APP_UID APP_GID REPO_ROOT

BUILD_IMAGE=0
RESET_DB=0
LOAD_FIXTURES=1

usage() {
  cat <<'EOF'
Build and provision the Docker-based sysPass demo from this checkout.

The stack is defined in build/docker-compose-demo.yml and uses a dedicated
MariaDB container and volume. A fresh database is installed with the sysPass
CLI (sp:install), optionally followed by the demo fixtures; application
settings and admin/master credentials are then applied.

Usage:
  scripts/setup-demo.sh [--build-image] [--reset] [--no-fixtures]

  --build-image   Rebuild the PHP image with make -C .ai-cli/docker build.
  --reset         Drop and recreate the application database (destructive).
  --no-fixtures   Do not load demo fixture data after a fresh install.

Environment overrides:
  COMPOSE_FILE, PHP_IMAGE, APP_PORT, APP_URL, APP_CONTAINER_NAME,
  DB_CONTAINER_NAME, DB_NAME, DB_USER, DB_PASS,
  ADMIN_LOGIN, ADMIN_PASSWORD, MASTER_PASSWORD, APP_DEBUG,
  APP_SESSION_TIMEOUT, APP_UID, APP_GID, REPO_ROOT
EOF
}

while [ "$#" -gt 0 ]; do
  case "$1" in
    --build-image)
      BUILD_IMAGE=1
      ;;
    --reset)
      RESET_DB=1
      ;;
    --no-fixtures)
      LOAD_FIXTURES=0
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      usage >&2
      exit 2
      ;;
  esac
  shift
done

if [[ ! "$DB_NAME" =~ ^[A-Za-z0-9_]+$ ]]; then
  echo "DB_NAME may only contain letters, digits, and underscores" >&2
  exit 1
fi

if [ -z "$DB_PASS" ]; then
  echo "DB_PASS must not be empty" >&2
  exit 1
fi

if [[ ! "$APP_PORT" =~ ^[0-9]+$ ]]; then
  echo "APP_PORT must be a number" >&2
  exit 1
fi

if "$DOCKER" compose version >/dev/null 2>&1; then
  COMPOSE=("$DOCKER" compose)
elif command -v docker-compose >/dev/null 2>&1; then
  COMPOSE=(docker-compose)
else
  echo "Docker Compose is required (docker compose or docker-compose)" >&2
  exit 1
fi

compose() {
  "${COMPOSE[@]}" -f "$COMPOSE_FILE" "$@"
}

for file in \
  "$COMPOSE_FILE" \
  "$ROOT/tests/res/config/config.xml" \
  "$ROOT/schemas/dbstructure.sql" \
  "$ROOT/tests/res/datasets/truncate.sql" \
  "$ROOT/tests/res/datasets/syspass.sql" \
  "$ROOT/scripts/configure-demo.php"; do
  if [ ! -f "$file" ]; then
    echo "Required file not found: $file" >&2
    exit 1
  fi
done

build_image() {
  local docker_dir

  docker_dir="$(cd "$(dirname "$DOCKER")" && pwd)"

  if [ "$docker_dir" != "$(pwd)" ]; then
    PATH="$docker_dir:$PATH" make -C "$ROOT/.ai-cli/docker" build
  else
    make -C "$ROOT/.ai-cli/docker" build
  fi
}

if [ "$BUILD_IMAGE" -eq 1 ] || ! "$DOCKER" image inspect "$PHP_IMAGE" >/dev/null 2>&1; then
  if [ "$BUILD_IMAGE" -eq 1 ]; then
    echo "Building PHP image: $PHP_IMAGE"
    build_image

    if ! "$DOCKER" image inspect "$PHP_IMAGE" >/dev/null 2>&1; then
      echo "Image $PHP_IMAGE was not built; adjust PHP_IMAGE or build it manually" >&2
      exit 1
    fi
  else
    echo "PHP image not found: $PHP_IMAGE" >&2
    echo "Run with --build-image, or build it with: make -C .ai-cli/docker build" >&2
    exit 1
  fi
fi

if [ ! -f "$ROOT/vendor/autoload.php" ]; then
  echo "Installing Composer dependencies"
  "$DOCKER" run --rm \
    --user "$APP_UID:$APP_GID" \
    -e HOME=/tmp \
    -v "$REPO_ROOT:/repo" \
    -w /repo \
    --entrypoint composer \
    "$PHP_IMAGE" install --no-interaction --no-progress
fi

compose config >/dev/null

if [ "$RESET_DB" -eq 1 ]; then
  echo "WARNING: dropping database '$DB_NAME' (--reset)"
  compose stop app >/dev/null 2>&1 || true
fi

compose up -d db

printf 'Waiting for database'
DB_READY=0
for _ in $(seq 1 60); do
  if "$DOCKER" exec "$DB_CONTAINER_NAME" \
    mysqladmin ping -h 127.0.0.1 -u"$DB_USER" -p"$DB_PASS" --silent >/dev/null 2>&1; then
    DB_READY=1
    break
  fi
  printf '.'
  sleep 1
done

if [ "$DB_READY" -ne 1 ]; then
  echo
  echo "Database did not become ready" >&2
  compose logs --tail=50 db
  exit 1
fi
echo " ready"

mysql_exec() {
  local database="$1"
  shift
  "$DOCKER" exec -i -e MYSQL_PWD="$DB_PASS" "$DB_CONTAINER_NAME" \
    mysql -u"$DB_USER" "$database" "$@"
}

if [ "$RESET_DB" -eq 1 ]; then
  "$DOCKER" exec -e MYSQL_PWD="$DB_PASS" "$DB_CONTAINER_NAME" \
    mysql -u"$DB_USER" \
    -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
else
  "$DOCKER" exec -e MYSQL_PWD="$DB_PASS" "$DB_CONTAINER_NAME" \
    mysql -u"$DB_USER" \
    -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
fi

TABLE_COUNT="$("$DOCKER" exec -e MYSQL_PWD="$DB_PASS" "$DB_CONTAINER_NAME" \
  mysql -N -B -u"$DB_USER" \
  -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME' AND table_name = 'Config'")"

if [ "$RESET_DB" -eq 1 ] || [ "$TABLE_COUNT" = "0" ]; then
  echo "Installing sysPass with the CLI"
  mkdir -p "$ROOT/app/config" "$ROOT/app/cache"
  cp "$ROOT/tests/res/config/config.xml" "$ROOT/app/config/config.xml"
  chmod 0640 "$ROOT/app/config/config.xml"
  rm -f "$ROOT/app/cache/config.cache"

  compose run --rm -T --no-deps \
    -e ADMIN_LOGIN="$ADMIN_LOGIN" \
    -e ADMIN_PASSWORD="$ADMIN_PASSWORD" \
    -e DATABASE_HOST=db \
    -e DATABASE_NAME="$DB_NAME" \
    -e DATABASE_USER="$DB_USER" \
    -e DATABASE_PASSWORD="$DB_PASS" \
    -e MASTER_PASSWORD="$MASTER_PASSWORD" \
    -e LANGUAGE=en_US \
    -e HOSTING_MODE=1 \
    -e FORCE_INSTALL=1 \
    -e INSTALL=1 \
    --entrypoint php \
    app -d display_errors=0 -d log_errors=1 cli.php sp:install --no-ansi --no-interaction

  if [ "$LOAD_FIXTURES" -eq 1 ]; then
    echo "Loading demo fixtures"
    mysql_exec "$DB_NAME" < "$ROOT/tests/res/datasets/truncate.sql"
    mysql_exec "$DB_NAME" < "$ROOT/tests/res/datasets/syspass.sql"
  fi
else
  echo "Database already provisioned; keeping existing data (use --reset to recreate)"
fi

echo "Writing application config and credentials"
compose run --rm -T --no-deps \
  -e APP_URL="$APP_URL" \
  -e APP_DB_HOST=db \
  -e APP_DB_PORT=3306 \
  -e APP_DB_NAME="$DB_NAME" \
  -e APP_DB_USER="$DB_USER" \
  -e APP_DB_PASS="$DB_PASS" \
  -e ADMIN_LOGIN="$ADMIN_LOGIN" \
  -e ADMIN_PASSWORD="$ADMIN_PASSWORD" \
  -e MASTER_PASSWORD="$MASTER_PASSWORD" \
  -e APP_DEBUG="$APP_DEBUG" \
  -e APP_SESSION_TIMEOUT="$APP_SESSION_TIMEOUT" \
  --entrypoint php \
  app scripts/configure-demo.php

compose up -d --force-recreate app api web

printf 'Waiting for sysPass'
APP_READY=0
for _ in $(seq 1 30); do
  HTTP_CODE="$(curl -sS -o /dev/null -w '%{http_code}' "$APP_URL/index.php?r=login" 2>/dev/null || true)"
  if [ "$HTTP_CODE" = "200" ]; then
    APP_READY=1
    break
  fi
  printf '.'
  sleep 1
done

if [ "$APP_READY" -ne 1 ]; then
  echo
  echo "sysPass did not respond at $APP_URL" >&2
  compose logs --tail=50 app
  exit 1
fi
echo " ready"

cat <<EOF

sysPass is ready.
  URL:             $APP_URL
  Admin:           $ADMIN_LOGIN / $ADMIN_PASSWORD
  Master password: $MASTER_PASSWORD
  Database:        $DB_NAME on $DB_CONTAINER_NAME

Stop the stack:
  docker compose -f "$COMPOSE_FILE" down

Recreate the database and fixtures:
  $0 --reset
EOF
