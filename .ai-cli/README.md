# .ai-cli — AI session command kit

Minimal, reproducible CLI setup for AI sessions in this repo.

## 1. Load env (every session)

```sh
source .ai-cli/env.sh
```

This sets:

- `GH_BIN="$AI_CLI_ROOT/bin/gh"` — **always use `$GH_BIN`, never bare `gh`** (wrapper execs host gh via host-spawn inside Flatpak)
- `AWS_BIN="$AI_CLI_ROOT/bin/aws"`, `TERRAFORM_BIN="$AI_CLI_ROOT/bin/terraform"` — host binaries via the same wrapper pattern
- `DOCKER_BIN="$AI_CLI_ROOT/bin/docker"` — host docker via the same wrapper pattern
- `PHP_BIN="$AI_CLI_ROOT/bin/php"` — **always use `$PHP_BIN`, never bare `php`** (runs PHP in Docker)
- `PHP_IMAGE` — PHP image to run (default `php-testing-composer:latest`)
- `AI_CLI_ROOT`, `AI_CLI_LOG_DIR`
- strict mode: `set -euo pipefail`

Verify:

```sh
"$GH_BIN" --version
"$GH_BIN" auth status
```

## 2. Run commands via `ai-run`

```sh
.ai-cli/bin/ai-run --timeout 60 -- "$GH_BIN" issue list --state open --limit 10
.ai-cli/bin/ai-run -- "$GH_BIN" issue view 1 --comments
```

`ai-run` adds: timeout (default 120s), timestamped log to `.ai-cli/logs/`,
exit-code echo, no interactive prompts.

## 3. Use templates, don't improvise

See `command-templates/`:

- `gh-issue-view.sh <number>` — read one issue + comments + labels
- `gh-issue-list.sh [--label X] [--state open]` — bounded JSON list
- `gh-issue-create.sh --title "..." --body-file ...` — heredoc-safe create
- `tf-check.sh [dir]` — read-only: `fmt -check` + `validate`
- `tf-plan.sh [dir] [args...]` — read-only plan, never prompts
- `aws-read.sh <service> <operation> [args...]` — read-only AWS verbs only, JSON output
- `docker-read.sh <subcommand> [args...]` — read-only docker subcommands only (ps, images, logs, inspect, compose ps/logs/config, ...)

All templates call `$HOME/.local/bin/gh` explicitly (aws/terraform/docker run on the
host via `$HOST_SPAWN`, see `env.sh` -> `$AWS_BIN`, `$TERRAFORM_BIN`, `$DOCKER_BIN`).

## 4. aws / terraform / docker

Binaries live on the host and are pinned in `env.sh`:

- `$AWS_BIN` → `host-spawn aws` (CLI v2)
- `$TERRAFORM_BIN` → `host-spawn terraform`
- `$DOCKER_BIN` → `host-spawn docker`

They run on the host, so paths must exist on the host — run from the repo,
which maps to the same path on both sides.

```sh
.ai-cli/bin/ai-run -- .ai-cli/command-templates/tf-plan.sh infra/
.ai-cli/bin/ai-run -- .ai-cli/command-templates/aws-read.sh ec2 describe-instances --max-results 20
.ai-cli/bin/ai-run -- .ai-cli/command-templates/docker-read.sh ps -a
.ai-cli/bin/ai-run -- .ai-cli/command-templates/docker-read.sh compose logs --tail 50
```

Hard rule: **read-only only** (`plan`, `validate`, `fmt -check`, `state list`,
`output`, AWS `get-*`/`describe-*`/`list-*`/`head-*`, docker inspect-style
subcommands per `docker-read.sh`). Anything that mutates
(`terraform init/apply/destroy/fmt/state *`, AWS `create-*`/`update-*`/
`delete-*`/`put-*`/..., docker `run`/`exec`/`build`/`rm`/`compose up`/...)
requires explicit human approval — ask first, never
`-auto-approve`, never secrets on argv (use env / `--body-file` / var files).

## 5. PHP (always via Docker)

PHP MUST NOT be run from the host. `$PHP_BIN` runs the pinned image
`php-testing-composer:latest` (PHP 8.3 + extensions, Composer 2.7) with the
repo bind-mounted at the same absolute path, workdir at the repo root, and
the caller's uid/gid:

```sh
source .ai-cli/env.sh

.ai-cli/bin/ai-run -- "$PHP_BIN" -v
.ai-cli/bin/ai-run -- "$PHP_BIN" -l app/modules/api/Controllers/Controller.php
.ai-cli/bin/ai-run --timeout 300 -- "$PHP_BIN" vendor/bin/phpunit -c ./tests/phpunit.xml --no-coverage
.ai-cli/bin/ai-run --timeout 300 -- "$PHP_BIN" vendor/bin/phpstan analyse --level 6 lib
.ai-cli/bin/ai-run -- "$PHP_BIN" vendor/bin/phpcs --standard=PSR2 lib
```

- `XDEBUG_MODE` defaults to `off` in the wrapper; set `XDEBUG_MODE=coverage`
  for coverage runs. `tests/phpunit.xml` asks for coverage, so pass
  `--no-coverage` (as CI does) or `XDEBUG_MODE=coverage` to get a clean exit.
- `PHP_IMAGE` overrides the image.
- Never prefix commands with `php`, `vendor/bin/...` directly, or composer
  scripts that shell out to `php`: use `"$PHP_BIN" <script>` so the container
  PHP is the interpreter.

### Building / rebuilding the image

The image definition and build target are vendored in `.ai-cli/docker/`
(`Dockerfile.composer` + `Makefile`, Composer 2.7 → PHP 8.3):

```sh
make -C .ai-cli/docker build          # tags php-testing-composer:latest and :2.7
COMPOSER_VERSION=2.7 make -C .ai-cli/docker build
```

Building is a Docker mutation: ask the human first (see `allowed-commands.txt`).
The build uses the network (`--pull`); the package set includes
`intl gettext pdo_mysql opcache gd xmlwriter simplexml ldap` and Xdebug.

## 6. Session hooks

```sh
.ai-cli/session-hooks/pre-session.sh    # context capture, safe to run anytime
.ai-cli/session-hooks/post-session.sh   # diff snapshot, runs at end
```

## Rules

1. `gh` === host `/usr/bin/gh`, invoked only as `$GH_BIN` (`$AI_CLI_ROOT/bin/gh` wrapper — execs host gh via host-spawn inside Flatpak). No bare `gh`, no PATH lookup, no alias.
2. Prefer `--json` + `--jq` for machine-readable output.
3. Bound everything: `--limit`, `| head -50`, `timeout`.
4. No secrets on argv. Use env / stdin / `--body-file`.
5. Check `allowed-commands.txt` — if a command isn't listed, ask first.
6. aws/terraform/docker: read-only verbs/subcommands only; every mutation or
   destructive command needs explicit human approval first. The one exception
   is `$PHP_BIN` (`.ai-cli/bin/php`), which runs an ephemeral
   `docker run --rm` of the pinned PHP image over the repo.
7. PHP === the pinned Docker image, invoked only as `$PHP_BIN`. No bare `php`,
   no host PHP, no `vendor/bin/*` executed directly.
