# UI Smoke Suite

Starter guide for the isolated browser smoke harness. The full contributor, triage and qualification runbook arrives with #2085.

`scripts/ui-smoke.sh` is shared repository tooling for contributors, reviewers and CI. `.ai-cli/` is ONLY for AI agents; it is not the canonical home for this runner or any other human/CI entry point.

Current scope: #2076 / plan U1. The suite provisions a fresh synthetic instance with `sp:install` and loads the anonymous login page. Fixtures, the authenticated shell, positive readiness contracts and account pages are later slices and are not implemented here.

## Prerequisites

Run these once from a clean checkout. They are deliberately not executed by the runner.

1. Build the application assets from the root lockfile:

   ```sh
   npm ci
   bash scripts/build-assets.sh
   ```

   Install the PHP dependencies inside the pinned container (never with host PHP):

   ```sh
   source .ai-cli/env.sh
   .ai-cli/bin/ai-run --timeout 600 -- "$PHP_BIN" /usr/bin/composer install \
     --prefer-dist --no-interaction --no-plugins --no-scripts
   ```

2. Install the separate UI harness and its pinned Chromium:

   ```sh
   cd tests/ui
   npm ci
   PLAYWRIGHT_BROWSERS_PATH="$PWD/.browsers" ./node_modules/.bin/playwright install chromium
   ```

   The runner uses the same `tests/ui/.browsers` path so Chromium is pinned to the harness instead of a shared host cache.

   On a host distribution that Playwright does not support for downloading Chromium, an explicit local fallback may be selected with `SP_UI_CHROMIUM_EXECUTABLE=/path/to/chromium`. It is not a substitute for the pinned-Chromium run before CI qualification.

3. Make the pinned container images available. Building and pulling images are Docker mutations and need explicit human approval:

   ```sh
   make -C .ai-cli/docker build          # php-testing-composer:latest
   docker pull mariadb:10.3
   ```

   PHP 8.3 is the supported floor (`composer.json`: `>=8.3 <8.6`).

## Run

```sh
./scripts/ui-smoke.sh
```

Use `--keep` only for debugging; it leaves the run root, network and containers in place for inspection. Do not reuse them for another run.

CI and human contributors invoke `scripts/ui-smoke.sh` directly. AI sessions may source `.ai-cli/env.sh` for the approved wrappers, but `.ai-cli/` is not part of the shared CI or contributor invocation path.

The runner requires Docker permission and creates runner-owned containers, a network, a database and a run directory. It never pulls or builds images, never reuses a developer server or database, and refuses an externally supplied `E2E_BASE_URL`.

## Execution model

| Piece | Location | Lifetime |
| --- | --- | --- |
| PHP / web server | `php-testing-composer:latest` container, `<repo>` mounted at the same absolute path | Per run, removed afterwards |
| MariaDB | `mariadb:10.3` container on a run-owned Docker network, no pre-created database | Per run, removed afterwards |
| Node / Playwright | Host Node 20+, `tests/ui/node_modules` | Installed once |
| Chromium | `tests/ui/.browsers` | Installed once |
| Configuration | `<run root>/config` via `CONFIG_PATH` / `CONFIG_FILE` | Per run, removed afterwards |
| DI cache | `<run root>/cache` via `CACHE_PATH` | Per run, removed afterwards |
| Application cache | `<run root>/app-cache` bind-mounted over `<repo>/app/cache` (`Path::CACHE/config.cache`) | Per run, removed afterwards |
| Application temp | `<run root>/app-temp` bind-mounted over `<repo>/app/temp` | Per run, removed afterwards |
| Sessions | `<run root>/sessions` via `session.save_path` | Per run, removed afterwards |
| Output | `<run root>/output` (JUnit, Playwright output) | Per run, removed afterwards |

The web server listens only on a random `127.0.0.1` port and is reachable only for the run. The origin is passed to Playwright as `SP_UI_BASE_URL` after the runner has proved it belongs to this run. Browser locale is `en-US`; application locale is `en_US`; the User-Agent is fixed; there is one worker and zero retries.

## Isolation and refusal rules

Before any container or database mutation, `tests/ui/src/preflight.mjs` rejects:

- a run root inside the repository, including `app/config`, `app/cache`, `app/temp`, `.git` and `vendor`;
- a symlinked run root or an owned path that resolves outside the run root;
- a non-empty run configuration directory;
- a database name that is not `sp_ui_<12 hex>` or is reserved;
- an origin that is not `http://127.0.0.1:<port>`;
- an externally supplied `E2E_BASE_URL`.

The runner also verifies `CONFIG_PATH`, `CONFIG_FILE`, `CACHE_PATH`, `LOG_FILE`, `session.save_path` and the effective `Path::CACHE`/`Path::TMP` bindings inside PHP before installing. MariaDB is started without `MYSQL_DATABASE`; normal `sp:install` creates the unique database and schema. Developer `app/config`, `app/cache` and `app/temp` fingerprints must be unchanged when the run finishes.

## Current checks

`tests/ui/specs/login.spec.ts` loads `index.php?r=login/index` and asserts the page-specific login form (`#box-login`, `#frmLogin`, field and button roots, dispatch attributes) plus the current `bootstrap/getEnvironment` boot contract (`status: OK`, `loggedIn: false`, `locale: en_US`, a CSRF token and a public key). It fails on uncaught page/console errors, unexpected failed requests and critical error markup.

Readiness contracts, fixture data and authenticated navigation belong to #2077 and #2078. `bootstrap/getEnvironment` is the current boot contract; #2096 will replace it and must update this probe in the same change.
