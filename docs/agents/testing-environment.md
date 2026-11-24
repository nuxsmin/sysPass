<!-- Copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org -->

# Testing environment

Use this setup for database-backed PHPUnit troubleshooting. It creates a disposable database containing synthetic fixtures; fixture loading truncates tables. Never point it at a developer or production database. Run database suites and UI smoke sequentially: concurrent runs can interfere through shared runtime paths.

AI sessions must first source `.ai-cli/env.sh` and follow `.ai-cli/README.md` and `AGENTS.md`. Docker container creation, execution and removal require explicit human approval; the PHP wrapper's ephemeral container is the documented exception. Building the PHP image also requires approval. The commands below use the wrappers and `ai-run` so they work in the configured agent environment.

## Prerequisites

- Docker, the `php-testing-composer:latest` image, the `mariadb:10.3` image, and installed Composer dependencies in `vendor/`. See `.ai-cli/README.md` for the PHP image and `docs/agents/linting.md` for dependencies.
- Port `127.0.0.1:3306` available. The fixture loader invokes the MariaDB client without passing `DB_PORT`, so a different published port will not work for fixture reloads.
- For UI smoke, install the Node dependencies, generated assets and Playwright Chromium as described in [UI smoke suite](ui-smoke-suite.md). Its runner owns and cleans up its own containers; it does not use the database below.

## Start a disposable database

After approval, from the repository root, check that the name `syspass-test-db` is unused. If it is already in use, choose a different name and substitute it consistently below; do not reuse or stop the existing container. Record the container ID printed by the successful `run` command. If creation fails, stop here and do not run the schema or cleanup commands against that name.

```bash
source .ai-cli/env.sh
export MARIADB_ROOT_PASSWORD=synthetic-test-only
.ai-cli/bin/ai-run -- "$DOCKER_BIN" run -d --rm --name syspass-test-db \
  -e MARIADB_ROOT_PASSWORD -e MARIADB_DATABASE=syspass \
  -p 127.0.0.1:3306:3306 mariadb:10.3
```

Wait until the container reports it is ready with `.ai-cli/command-templates/docker-read.sh logs syspass-test-db`. If it exits or the port is occupied, inspect the error and resolve it before proceeding. Load the schema once into this new database:

```bash
.ai-cli/bin/ai-run -- "$DOCKER_BIN" exec -i syspass-test-db sh -c \
  'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mysql -uroot syspass' < schemas/dbstructure.sql
export PHP_DOCKER_NETWORK=host DB_SERVER=127.0.0.1 DB_PORT=3306
export DB_NAME=syspass DB_USER=root DB_PASS="$MARIADB_ROOT_PASSWORD"
```

The host network setting connects the PHP container to the published local port on Linux; other Docker platforms may require a different host address. The database container is disposable and must be used only for tests.

## Reproduce and verify

Run the smallest failing test first, then the relevant suite. For example:

```bash
.ai-cli/bin/ai-run --timeout 300 -- "$PHP_BIN" vendor/bin/phpunit -c tests/phpunit.xml \
  --no-coverage tests/SP/Modules/Api/AccountBoundaryTest.php
.ai-cli/bin/ai-run --timeout 900 -- "$PHP_BIN" vendor/bin/phpunit -c tests/phpunit.xml \
  --no-coverage --group integration --testsuite modules
```

For CI-aligned PHPStan and PHPCS checks, follow [Static analysis and formatting](linting.md). For the isolated browser suite, with the required explicit approval, run `TESTS_UI_ENABLED=true .ai-cli/bin/ai-run --timeout 1800 -- ./scripts/ui-smoke.sh`. The runner verifies its synthetic fixture twice before starting browser tests and retains sanitized summaries on failure.

If a fixture reset hangs, inspect `docker-read.sh logs syspass-test-db` and the database process list in the disposable container. A timed-out PHP wrapper can leave its container running and holding locks; inspect Docker containers and stop only the container belonging to that test run. Do not retry against an unknown database.

## Clean up

After testing, stop the container only if this session successfully created it and its ID still matches the ID recorded above. If ownership is uncertain, inspect it and leave it running. Then clear the test connection settings:

```bash
.ai-cli/bin/ai-run -- "$DOCKER_BIN" stop syspass-test-db
unset PHP_DOCKER_NETWORK DB_SERVER DB_PORT DB_NAME DB_USER DB_PASS MARIADB_ROOT_PASSWORD
```

The `--rm` container is removed on stop. Preserve unrelated containers, volumes, worktree changes and runtime files.
