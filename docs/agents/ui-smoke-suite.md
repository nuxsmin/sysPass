# UI Smoke Suite

This is the contributor, triage and qualification runbook for the isolated
browser smoke harness. It documents the current implementation, not a target
design. Optional work that has not landed is marked **not yet available**.

`scripts/ui-smoke.sh` is shared repository tooling for contributors, reviewers
and CI. `.ai-cli/` is **only** AI-agent tooling; it is not the canonical home
for this runner or any other human/CI entry point.

## Status at a glance

| Area | Current state |
| --- | --- |
| Tier A | Implemented and under observation. Runs anonymous login, the authenticated shell and account view/create/edit in both in-shell and direct full-page modes. |
| Tier B | Implemented as an independently reported, annotation-only selection. Own daily/manual workflow, own summary root, not merge-required. |
| Installer | Implemented as a separate, explicitly stateful selection on a second isolated instance. Never part of Tier A/B and never sets `completeTierA`. |
| Known Failure registry | Implemented (`tests/ui/known-failures.json`) and initially empty. Admission and stale-entry removal are enforced. |
| 30-day qualification | **Not complete.** Observation began 2026-11-06; no qualification period has elapsed. |
| Required check | **Not enabled.** `TESTS_UI_ENABLED` controls execution only. Branch protection or a ruleset must separately require `UI Smoke (Tier A)`. |
| Daily master schedule | **Not yet available before merge.** The schedules are present in `.github/workflows/ui-smoke.yml` and `.github/workflows/ui-smoke-tier-b.yml`, but only become active once those workflow files are on the default branch. Before merge there are no scheduled master observations. |
| Hosting-mode installer | **Not implemented or tested.** Explicitly excluded. |
| Clean URLs | **Not current behaviour.** The suite uses `index.php?r=<controller>/<action>`. Clean URLs are independent future work under ADR-0006 (#2064 / #2073). |
| Attachment/binder choice | **Pending #2116.** Not assumed by this runbook. |

## Prerequisites

Run these once from a clean checkout. The runner deliberately does not install
prerequisites, build images, pull images or download Chromium.

### Runtime and assets

1. Build the application assets from the root lockfile:

   ```sh
   npm ci
   bash scripts/build-assets.sh
   ```

   Install the PHP dependencies inside the pinned container. Never use host PHP
   and never invoke `vendor/bin/*` directly:

   ```sh
   source .ai-cli/env.sh
   .ai-cli/bin/ai-run --timeout 600 -- "$PHP_BIN" /usr/bin/composer install \
     --prefer-dist --no-interaction --no-plugins --no-scripts
   ```

2. Install the separate UI harness, its lockfile and pinned Chromium:

   ```sh
   ( cd tests/ui
     npm ci
     npm run typecheck
     PLAYWRIGHT_BROWSERS_PATH="$PWD/.browsers" ./node_modules/.bin/playwright install chromium
   )
   ```

   The runner uses the same `tests/ui/.browsers` directory. Pinned Chromium is
   required for CI qualification. On a host distribution that Playwright does
   not support for downloading Chromium, an explicit local fallback may be
   selected with `SP_UI_CHROMIUM_EXECUTABLE=/path/to/chromium`; it is not a
   substitute for the pinned-Chromium run before CI qualification.

3. Make the pinned container images available. Building or pulling images are
   Docker mutations and need explicit human approval:

   ```sh
   make -C scripts/docker build          # php-testing-composer:latest
   docker pull mariadb:10.3
   ```

   `scripts/docker/` is the shared build path for contributors and CI. The
   workflow builds the same Dockerfile with the same `scripts/docker` build
   context. PHP 8.3 is the supported floor (`composer.json`: `>=8.3 <8.6`);
   the browser job uses PHP 8.3 and MariaDB 10.3.

### Approvals and safe execution

- Read `AGENTS.md` and `.ai-cli/README.md` before running commands.
- The runner creates and removes only runner-owned Docker containers, a
  network, a database and a run directory. That is still a Docker mutation:
  ask for approval before running it on a shared machine. The command is listed
  in `.ai-cli/allowed-commands.txt` as a logged mutation, but that listing does
  not replace explicit human approval.
- The runner never builds or pulls images, never reuses a developer server or
  database, and refuses an externally supplied `E2E_BASE_URL`.
- Do not rebuild the PHP image, prune Docker, add dependencies or edit
  `.idea/*` as part of a smoke run.
- Keep secrets out of command arguments and logs. The runner passes synthetic
  fixture credentials through run-owned environment files, not process
  arguments.

### Validate the harness without provisioning

These checks need no Docker containers and are safe to run before a real
observation:

```sh
bash -n scripts/ui-smoke.sh scripts/lib/ui-smoke-report.sh \
  scripts/ci/check-setup-budget.sh scripts/ci/check-ui-required.sh
( cd tests/ui && npm run typecheck && npm run test:unit )
```

The runner repeats `node --test src/*.test.mjs` before Playwright, so a harness
regression stops the run before any container is created.

## Execution switch, tiers and selection

`TESTS_UI_ENABLED` must be exactly `true`. Any other value, including unset,
makes the runner exit before checking prerequisites or creating resources. A
skipped run writes no summary and is never a complete Tier A observation
(`scripts/ui-smoke.sh:112-119`).

```sh
TESTS_UI_ENABLED=true ./scripts/ui-smoke.sh
```

### Tier selection

`SP_UI_TIER` selects the tier and defaults to `A` when unset. `A`/`a`, `B`/`b`
and `INSTALL`/`install` are accepted; any other token is a usage error (exit 2),
not an unknown observation.

| Selection | Command | Specs |
| --- | --- | --- |
| Tier A (default) | `TESTS_UI_ENABLED=true ./scripts/ui-smoke.sh` | `login.spec.ts`, `shell.spec.ts` |
| Tier B | `TESTS_UI_ENABLED=true SP_UI_TIER=B ./scripts/ui-smoke.sh` | Seven in-shell index specs |
| Installer only | `TESTS_UI_ENABLED=true SP_UI_TIER=INSTALL ./scripts/ui-smoke.sh` | `install.spec.ts` |
| Tier A + installer | `TESTS_UI_ENABLED=true SP_UI_TIER=A SP_UI_INSTALL=1 ./scripts/ui-smoke.sh` | Seeded specs first, wizard last |
| Tier B + installer | `TESTS_UI_ENABLED=true SP_UI_TIER=B SP_UI_INSTALL=1 ./scripts/ui-smoke.sh` | Seeded specs first, wizard last |

`SP_UI_INSTALL` accepts only `0` or `1`; any other value is a usage error. A
`SP_UI_TIER=INSTALL` run selects the installer even with `SP_UI_INSTALL=0`.
A combined selection always runs the seeded tier first and the installer
afterwards; this comes from the explicit run plan, not Playwright filename
order (`tests/ui/src/install-selection.mjs:80-131`).

`SP_UI_SPECS` narrows a run to an explicit subset. A subset, grep-filtered run
or unknown spec is recorded as a partial selection and is never a complete
observation.

### Evidence roots and overrides

| Selection | Default retained root |
| --- | --- |
| Tier A | `tests/ui/summaries/tier-A/<run-id>/` |
| Tier B | `tests/ui/summaries/tier-B/<run-id>/` |
| Installer | `tests/ui/summaries/tier-install/<run-id>/` |

The roots are siblings, not nested. Discovery is non-recursive, so a Tier A
report can never consume Tier B or installer history and vice versa. Use the
per-run directory, not the accumulating tier root, when running `ci-report`.

`SP_UI_SUMMARY_DIR` overrides the seeded tier root. For an installer-only
selection it overrides the install root. `SP_UI_INSTALL_SUMMARY_DIR` overrides
the installer root in a combined run. The two overrides stay independent.

### Other selection variables

| Variable | Effect |
| --- | --- |
| `SP_UI_DIAGNOSTIC_RETRY=1` | Enables one retry with `failOnFlakyTests`. A retry-success is reported flaky and rejected as complete evidence. CI exposes this only through the `workflow_dispatch` `diagnostic_retry` input. |
| `SP_UI_LOCAL_TRACE=1` | Enables a Playwright trace only for a local run against the runner-owned synthetic instance. Forced off in CI and never uploaded. |
| `SP_UI_CHROMIUM_EXECUTABLE` | Local explicit Chromium executable fallback. Not the pinned-Chromium CI path. |
| `SP_UI_KNOWN_FAILURES` | Local override for the Known Failure registry. CI uses the committed file. |
| `SP_UI_TIER_B_FAULT=accessManager/index` | Scratch-only Tier B fault canary. Never set in CI. |
| `SP_UI_INSTALL_FAULT=remove-master-password` | Scratch-only installer fault canary. Never set in CI. |

The installer phase always uses `retries: 0`. A diagnostic retry combined with
the installer is rejected because it would run the wizard again against an
already installed instance.

## Execution model

| Piece | Location | Lifetime |
| --- | --- | --- |
| PHP / web server | `php-testing-composer:latest` container, `<repo>` mounted at the same absolute path | Per run, removed afterwards |
| MariaDB | `mariadb:10.3` container on a run-owned Docker network, no pre-created database | Per run, removed afterwards |
| Node / Playwright | Host Node 20+, `tests/ui/node_modules` | Installed once |
| Chromium | `tests/ui/.browsers` | Installed once |
| Run root | `/tmp/sp-ui-smoke.XXXXXX` (or `$TMPDIR`) | Per run, removed afterwards |
| Configuration | `<run root>/config` via `CONFIG_PATH` / `CONFIG_FILE` | Per run, removed afterwards |
| DI cache | `<run root>/cache` via `CACHE_PATH` | Per run, removed afterwards |
| Application cache | `<run root>/app-cache` bind-mounted over `<repo>/app/cache` (`Path::CACHE/config.cache`) | Per run, removed afterwards |
| Application temp | `<run root>/app-temp` bind-mounted over `<repo>/app/temp` | Per run, removed afterwards |
| Upload temp | `<run root>/temp` via `upload_tmp_dir` | Per run, removed afterwards |
| Sessions | `<run root>/sessions` via `session.save_path` | Per run, removed afterwards |
| Output | `<run root>/output` (summary, JUnit, Playwright output) | Per run, removed afterwards |
| Installer instance | `<run root>/install` subtree plus its own containers, network, database, origin, config, caches, sessions and `<run root>/output-install` | Per run, removed afterwards; only when selected |
| Retained evidence | `tests/ui/summaries/tier-A|tier-B|tier-install/<run-id>/` | Kept through qualification; `tests/ui/summaries/` is gitignored |
| Fixture credentials | `tests/ui/fixtures/e2e-fixture.json` | Committed; synthetic only |
| Authentication state | Playwright worker memory and `<run root>/sessions` | Never written to artifacts; removed afterwards |

The web server listens only on a random `127.0.0.1` port and is reachable only
for the run. The origin is passed to Playwright as `SP_UI_BASE_URL` after the
runner has proved it belongs to this run. Browser locale is `en-US`;
application locale is `en_US`; the User-Agent is fixed to
`sysPass-ui-smoke/1.0 (+https://github.com/nuxsmin/sysPass)`; there is one
worker and zero retries by default. Playwright runs `fullyParallel: false`,
`workers: 1`, blocks service workers and forbids focused tests in CI.

### Network, database and origin ownership

The runner generates:

- a database name matching `sp_ui_<12 hex>`;
- a random ephemeral loopback port and origin `http://127.0.0.1:<port>`;
- a network `sp-ui-net-<hex>` and containers `sp-ui-web-<hex>` /
  `sp-ui-db-<hex>`;
- a run root under `${TMPDIR:-/tmp}` carrying `.sp-ui-owner` marker files that
  contain the run id.

MariaDB is started without `MYSQL_DATABASE`. The runner proves the database is
absent before installation and present afterwards. Normal `sp:install` owns
creation of the unique database and schema. The browser wizard likewise owns
database/schema creation on its own instance; the runner never precreates that
database, preloads `schemas/dbstructure.sql` or runs `sp:install` for it.

### Effective PHP paths

Before any installation, `tests/ui/server/verify-paths.php` checks inside PHP
that:

- `CONFIG_PATH`, `CONFIG_FILE`, `CACHE_PATH`, `LOG_FILE` and
  `session.save_path` resolve inside the run root;
- the owned directories carry the run-id marker;
- `Path::CACHE` and `Path::TMP` are the application cache/temp paths and carry
  the marker through their bind mounts;
- `Path::CONFIG`, `Path::CONFIG_FILE` and `Path::LOG_FILE` match the
  environment values.

`CACHE_PATH` only relocates DI compilation. It does **not** override the
application cache binding: the fixed `app/cache/config.cache` path is isolated
by binding `<run root>/app-cache` over `<repo>/app/cache`. The same applies to
`app/temp` through `<run root>/app-temp`.

The runner intentionally uses the pinned image through `$DOCKER_BIN` for these
commands rather than the shared `$PHP_BIN` wrapper. The wrapper forwards
neither `CONFIG_PATH` nor the installer variables and mounts only the
repository (`scripts/ui-smoke.sh:415-420`).

### Installer instance

The installer lives in `<run root>/install`. It has its own origin, MariaDB
container, network, database name, configuration, DI cache, application
cache/temp, upload temp, session namespace and output. `install-layout.mjs`
resolves the same subtree the runner creates and rejects overlap between the
two instances' managed paths or a reused database name. `install-guard.mjs`
refuses an already installed instance, an existing configuration file, a
precreated database, populated wizard caches/sessions or a nonzero retry count
(`tests/ui/src/install-guard.mjs:17-51`).

Before the wizard runs, `verify-paths.php` is rerun against the installer
instance. The spec asserts that hosting mode is present and unchecked; the
runner never selects it.

### Cleanup and interruption

`scripts/ui-smoke.sh` installs an `EXIT INT TERM` trap. On any exit it:

1. removes `error-context.md` files from both output trees;
2. writes a sanitized setup-failure report when the run did not finish
   successfully;
3. removes installer and seeded containers, networks and the run root;
4. removes the run root only when it matches `${TMPDIR:-/tmp}/sp-ui-smoke.*`;
5. preserves resources when `--keep` is used.

`--keep` is for debugging only. It leaves the run root, network and containers
in place; do not reuse them for another run. The installer has an additional
`reset_install_instance` path that destroys only wizard-owned resources and
starts a fresh uninstalled instance on every invocation. `install-lifecycle.mjs`
recreates the workspace and generates a new database/container/network identity;
the runner refuses a database that already exists.

If a process is killed with `SIGKILL`, the trap cannot run. Remove only the
run-owned containers, network and `/tmp/sp-ui-smoke.*` directory after
confirming their names; never remove developer images, volumes or unrelated
containers.

## Synthetic fixtures and crypto consistency

`tests/ui/fixtures/e2e-fixture.json` is the committed source of truth. It
contains only synthetic `E2E_*` credentials, stable logical IDs and fixture
text. The runner reads the admin login, password and Master Password from it
and passes them to `sp:install` through the run-owned environment file.

After installation, `tests/ui/fixtures/seed.php`:

- configures the isolated `config.xml` through `ConfigFileService` with
  `filesEnabled=1`, update and notice checks disabled, `debug=false`, demo mode
  disabled and HTTP Basic auto-login disabled;
- pre-generates the run-owned PKI key pair with `CryptPKIHandler` so login
  encryption is deterministic;
- writes user preferences needed by the session;
- creates the demo user, group and profile plus a synthetic client, category,
  parent account, child account and child account file;
- derives the login hash with `HashService`, the user vault with
  `UserMasterPassService` and the account ciphertext with `AccountCryptService`;
- verifies the login hash, Master Password metadata, both user vaults, both
  account ciphertexts, user preferences, the file row and the application
  configuration before the server starts.

The runner applies `--reset`, then `--verify`, then repeats the reset and
verification to prove reset stability. `--reset` deletes only the stable
fixture rows and recreates them with the same logical IDs; it never touches
`tests/SP` fixtures or shared datasets. Session files and cookie jars stay in
the run root or worker memory and are excluded from artifacts.

For a `--keep` debugging run, invoke `tests/ui/fixtures/seed.php` with the same
run-owned environment file, network and repository/run-root mounts as the
runner, using `--reset` or `--verify`. Do not point it at a developer database
or configuration. Run PHP only through the pinned container, not host PHP.

## Isolation and refusal rules

Before any container or database mutation, `tests/ui/src/preflight.mjs`
rejects:

- a run root inside the repository, including `app/config`, `app/cache`,
  `app/temp`, `.git` and `vendor`;
- a symlinked run root or an owned path that resolves outside the run root;
- a non-empty run configuration directory;
- a database name that is not `sp_ui_<12 hex>` or is reserved;
- an origin that is not `http://127.0.0.1:<port>`;
- an externally supplied `E2E_BASE_URL`.

The runner also fingerprints the developer `app/config`, `app/cache` and
`app/temp` directories before and after the run and fails if any changed. That
comparison runs even when a seeded or installer selection already failed; only a
run that fails before provisioning (selection validation, preflight or
environment checks) skips it. Every post-provisioning failure—including seeded
finalization, installer pre-checks, installer finalization and the isolation
proof—accumulates its status and reaches the same finalization point, which
always takes the after-fingerprints. A difference names the changed paths,
forces setup-failed evidence for the selected summaries and exits non-zero. The
installer instance repeats the same preflight/path verification with its own
subtree.

When both a seeded tier and the installer are selected, the runner takes the
seeded-state baseline after the seeded specs have produced their ordinary
view/session/audit writes. It compares the seeded configuration, DI cache,
upload temp, application temp, app cache, sessions and a `CHECKSUM TABLE`
database digest after the installer selection, and probes the seeded login page
to prove the instance remains usable. Any difference replaces the retained
installer summary with a forced setup-failed result and makes the runner exit
non-zero. The installer's own summary has `completeTierInstall` and
`completeTierA: false`; a passing wizard can never replace a Tier A result.

## Readiness contracts

The suite asserts positive per-page/mode contracts, not general layout. A page
load is ready only when all of the following hold within one 15-second
deadline (`tests/ui/src/readiness.ts:60`, `tests/ui/src/diagnostics.mjs`):

- every expected Page-load Action for the page/mode completed;
- the response body matches the expected format and meaningful content;
- the page-specific DOM/content/binding conditions are present;
- the loading overlay is hidden;
- asynchronous body inspection has drained;
- no detector problem is recorded;
- a 300 ms action-scoped quiet window has elapsed with no in-flight Page-load
  Action.

Static headings, placeholder options and a non-empty shell are insufficient.
Missing or pending actions are reported together with the observed route set.

### Tier A

| Page/mode | Required actions and meaningful content |
| --- | --- |
| Anonymous login | `login/index` HTML with `#box-login`, `#frmLogin`, login fields and dispatch attributes; `bootstrap/getEnvironment` JSON with `status: OK`, `loggedin: false`, `locale: en_US`, a null CSRF token and a public key. |
| Authenticated shell | `bootstrap/getEnvironment`, `account/index` and `items/notifications`; shell root, visible content, seeded parent/child/client/category content and a hidden overlay. |
| Account view, in-shell and direct URL | `account/view` with account details; `accountFile/list` with the seeded file; `items/accountsUser` with the seeded linked-account option; account form bindings. |
| Account create, in-shell and direct URL | `account/create` with “New Account”; `items/accountsUser` with the seeded parent option; account form bindings and empty item id. |
| Account edit, in-shell and direct URL | `account/edit` with account details and “Edit Account”; `accountFile/list`; `items/accountsUser`; account form bindings and item id. |

The login contract is checked in `tests/ui/specs/login.spec.ts`; the
authenticated contracts are in `tests/ui/src/contracts.ts` and
`tests/ui/specs/shell.spec.ts`.

### Tier B

Tier B covers seven in-shell index navigations and asserts each index's own
tabbed/grid/panel content and automatic actions:

`accessManager`, `itemManager`, `securityManager`, `configManager`,
`notification`, `userSettingsManager` and `plugin`.

Tier B authenticates once for the seeded suite and reuses the live worker cookie
jar. The seven specs share one login while still carrying legitimate SID
rotation. A reused Tier B session fails if the login route is observed again,
so there is no per-spec login storm and no immutable snapshot restore. Tier B
failures are reported independently as annotations; they cannot hide a Tier A
failure or falsely mark failed cases as passes.

### Installer

The installer spec first asserts the installer page contract and current
required fields (including database user/password and the Master Password
repeat), that hosting mode is unchecked, and that the Master Password repeat
field is required by the browser. It then submits a deliberately short Master
Password and requires the server to reject it, before filling synthetic
admin/database credentials and submitting `install/install`.

The installer must answer `status: OK` and redirect to the login page. The spec
then authenticates as the newly created admin and requires the fresh shell
contract: completed `bootstrap/getEnvironment`, `account/index` with the
empty-state content and `items/notifications`, a visible shell/search content
and no pending or failed routes. It never precreates the database, preloads the
schema or logs in with the seeded fixture.

## Diagnostics, warnings and fault injection

### Detector failure classes

The event-driven detector records, at minimum:

- uncaught page errors and web errors, with source/page URL;
- unexpected console errors, with source/page URL;
- unexpected request failures and HTTP responses `>= 400`;
- malformed or unexpected JSON envelopes, missing `status`, and JSON
  `status: ERROR`;
- JSON `status: 10` as session loss after authentication, or as an invalid
  response before authentication;
- empty HTTP 200 bodies and missing expected fragment content;
- critical server markup (`.msg-critical`), client error markup
  (`.error.round`) and error toasts (`.toast-error`);
- logout-script responses and unexpected login redirects/pages;
- late body-inspection failures;
- unmet DOM/content/binding conditions and a stuck loading overlay.

Warnings are annotations, not failures. Server warning markup
(`.msg-warning`), warning toasts (`.toast-warning`), JSON `status: WARNING` and
informational entries in plural `errors` containers do not fail a page load.
Critical markup and unexpected errors do fail. The detector does not require
`#content` on login or install pages.

Action failures are attributed to their route. Global JavaScript errors keep
the available source/page URL and are not assigned an invented route.
Readiness failures list the missing/pending routes and the observed route set.

### Session lifecycle

Anonymous/login tests never inherit authenticated state. The seeded suite
authenticates once through the UI and carries live cookie/storage changes
between sequential authenticated contexts. It does not restore an immutable
snapshot and never silently re-authenticates after actual session loss. A
silent re-login would hide the session-loss defect, invalidate the first-attempt
evidence and make the SID-rotation proof meaningless.

The suite verifies reuse across the legitimate 120-second SID regeneration:
after the rotation window it proves the rotated cookie is present and different,
and that a fresh sequential context uses the rotated cookies without a second
login. Visiting the session-destroying login route in an authenticated test is a
failure. Actual session loss is reported as `session-loss`; an unexpected
login/logout response before authentication is `invalid-response`. Neither is a
Known Failure kind.

### Fault injection

The detector and reporter have automated fault-injection unit tests covering
suppressed dispatch, invalid responses, session variants, warnings, hanging
actions and late body-inspection failures. These run in
`tests/ui/src/*.test.mjs`.

Two scratch-only browser canaries remain opt-in and are never set in CI:

- `SP_UI_TIER_B_FAULT=accessManager/index` intercepts the real
  `accessManager/index` response, requires the actual spec navigation to fail
  with the spec file and route named, removes the interception, proves the
  unmodified index loads again, then rethrows the named failure.
- `SP_UI_INSTALL_FAULT=remove-master-password` removes a required installer
  field in the browser and proves the missing field fails the installer
  contract.

## Evidence, redaction and capture policy

### Every run

The harness reporter writes three allowlisted artifacts on every run
(`tests/ui/src/reporter.mjs`):

- `run-summary.json`: run/commit/environment identity, selection/completion/
  skips, first attempts, retry counts, durations, failure classes and
  known-failure evaluation;
- `junit.xml`: JUnit representation of the same evidence;
- `upload-manifest.json`: SHA-256/size manifest of the retained allowlist.

The summary exposes `completeTierA`, `completeTierB` and `completeTierInstall`
independently. Only the selected tier can be complete. A partial,
setup-failed, cancelled or flaky run is never a complete success. A combined
seeded+installer run retains the seeded summary under its tier root and the
installer summary under `tier-install`; each is evaluated separately with
`ci-report --tier A|B|INSTALL`.

### Failing-attempt evidence

A failed attempt writes only sanitized diagnostics under `failures/`:

- a masked screenshot;
- sanitized console output;
- normalized route/URL/status information in `failure.json`.

Absolute file paths are reduced to repository-relative paths. The record
deliberately excludes request/response bodies, DOM snapshots, authentication
state, cookies, configuration snapshots, keys and fixture dumps. Screenshots
mask form fields, contenteditable regions and explicitly sensitive regions. A
login that does not redirect reports only its fixed route, HTTP status and a
validated content-type. The harness never reads or embeds the login response
body, so a body canary cannot reach the thrown error, `failure.json`, the summary
or JUnit.

### Upload allowlist and redaction

CI publishes only this explicit allowlist:

```text
run-summary.json
junit.xml
upload-manifest.json
failures/**/screenshot.png
failures/**/console.log
failures/**/failure.json
```

Before upload, verification checks the manifest against the same allowlist and
rejects unknown, duplicate, unsafe, missing or unlisted entries. The workflow
never uploads the summary directory as a whole and never resolves upload paths
dynamically from the manifest.

The Playwright page-snapshot copy prompt is disabled
(`PLAYWRIGHT_NO_COPY_PROMPT=1`), and any generated `error-context.md` file is
removed by the reporter, the runner cleanup and the retention step. It is never
part of the allowlist.

### Capture policy and canary

CI forces forbidden capture off regardless of local flags:

- no Playwright trace;
- no video;
- no HAR;
- no request/response bodies persisted or uploaded;
- no authentication state, cookies, configuration snapshots or keys;
- telemetry disabled (`DO_NOT_TRACK=1`,
  `PLAYWRIGHT_CLI_ANALYTICS=0`).

`SP_UI_LOCAL_TRACE=1` is a local-only opt-in against the runner-owned synthetic
instance. It is forced off in CI and is never uploaded. A trace requested
outside that synthetic context is rejected by the capture policy.

`tests/ui/src/*.test.mjs` includes a synthetic-canary end-to-end test that
deliberately fails a Playwright spec and inspects the reporter/output/upload
path for canary leakage in logs, URLs, DOM text and error output. A harness
failure stops the run before containers are created.

## Tiers, independence and Known Failures

Tier A is the browser prerequisite for ADR-0011's transport and boot changes
only. It is not a general end-to-end replacement. Tier B is independently
reported and remains annotation-only until its own qualification; it cannot
gate or hide Tier A. The installer is a separate, explicitly stateful exception
to the page-load suite's no-user-business-mutation rule.

Known Failure admission has landed. `tests/ui/known-failures.json` is the
committed registry and starts empty. It is not a quarantine list: it never
suppresses a failing exit status or changes a spec's assertions. An entry must
match exactly one `(spec, route, failureKind)` failure and include a reason,
linked defect and explicit maintainer admission.

Only route-bearing deterministic detector kinds qualify: `http`,
`json-status`, `request-failed`, `body-inspection`, `missing-content` and
`empty-body`. Flaky, setup/isolation, session-loss, invalid-response,
login-navigation, login-page, console, page-error, markup, assertion, timeout,
malformed-JSON and generic test-error kinds are not admissible. #2079 is
explicitly refused because it is a fixed regression.

An attempt can emit several detector problems. Every problem is recorded as its
own `(route, failureKind)` tuple; the attempt is admitted only when all of them
have entries. An admitted failure remains visible in counts and evidence, the
run still exits non-zero, and it never counts as a complete observation.

When an entry's spec actually executes without the admitted failure, the run
fails with removal instructions. A filtered, skipped or unexecuted spec does not
prove the defect is fixed and never marks an entry stale. New admissions are
exceptional reviewed changes; the maintainer who adds one owns its removal.
The registry can grow through reviewed admissions, but it is not a routine way
to obtain a green baseline.

## CI observation and qualification

### Workflows and scheduling

`.github/workflows/ui-smoke.yml` is an isolated workflow so its daily schedule
can only start the UI job. It runs on pull requests targeting `master` or
`feat/*`, pushes to `master`, manual dispatch and a daily schedule
(`17 4 * * *`). Scheduled runs check out `master` explicitly and record the
observed commit. Before the workflow is merged to the default branch, no
scheduled master observation exists.

`.github/workflows/ui-smoke-tier-b.yml` is a separate workflow for Tier B. It
runs on manual dispatch and a daily schedule (`47 4 * * *`), selects only
Tier B, and keeps its own summary root and artifact name. It cannot replace,
hide or gate Tier A.

Both callers keep their own triggers, execution switches and stable check
names, and delegate the pipeline to `.github/workflows/ui-smoke-run.yml`
(`workflow_call`, `tier`/`diagnostic_retry`/`retention_days` inputs). The shared
module owns checkout, setup, provisioning, the Playwright run, budgets, the
tier-scoped summary root and artifact name, and the sanitized evidence upload.
Unknown tiers fail closed before setup starts. Each caller has a workload job
(`ui-work`, `ui-tier-b-work`) that calls the module plus a one-step mirror job
(`ui`, `ui-tier-b`) that carries the stable check name and requires the workload
result to be `success`; a skipped workload therefore skips the mirror instead of
reporting success.

### Execution switch versus required check

`TESTS_UI_ENABLED == 'true'` controls execution only:

- When it is not exactly `true`, the job is skipped and produces no evidence.
- When it is `true`, Tier A failures fail the UI check with
  `continue-on-error: false`.
- Neither the switch nor `continue-on-error: false` establishes a merge gate.

A required check is established separately by branch protection or a
repository ruleset. The stable check name is `UI Smoke (Tier A)`.
`scripts/ci/check-ui-required.sh` is a read-only pre-observation check for an
existing requirement; it never changes settings. A skipped job publishes no
successful check run and therefore cannot satisfy a required check.

Tier B uses its own variable `TESTS_UI_TIER_B_ENABLED == 'true'` inside its own
workflow. The shared runner's `TESTS_UI_ENABLED` is literal `true` there; it is
not a second gate. A failed Tier B evaluation exits non-zero with
`continue-on-error: false`, so the job/workflow conclusion remains failed. It is
not merge-required and cannot become a required replacement for Tier A.

### Required-check verification before observation

The read-only check was run against `nuxsmin/sysPass` on 2026-11-05:

- `GET /repos/nuxsmin/sysPass/branches/master/protection` returned empty
  `required_status_checks.contexts` and `required_status_checks.checks`.
- `GET /repos/nuxsmin/sysPass/rulesets` returned `[]`.
- The aggregate-workflow scan could not complete because remote `master` had no
  `.github/workflows` directory at the observed commit; the script exited 1
  instead of claiming the check was not required.
- A separate read-only workflow list showed one active workflow on feature
  branches, not a required-check reference on `master`.

No branch protection, ruleset, variable or required-check setting was changed.
Required-check promotion remains a maintainer operation after qualification.

Reproduce the read-only check with:

```sh
source .ai-cli/env.sh
GH_BIN="$GH_BIN" bash scripts/ci/check-ui-required.sh nuxsmin/sysPass
```

The script prints branch-protection contexts/checks, ruleset required status
checks and other workflow references. It exits non-zero when the UI check
already appears required, an aggregate reference needs review, or a required
read cannot be completed. If `gh` is unavailable or unauthenticated it exits
127; a failed API read exits 1 rather than being reported as “not required.”

### Budgets and timeouts

| Bound | Value |
| --- | --- |
| Page readiness/diagnostics | 15 seconds per page load |
| Tier A spec-duration budget | 5 minutes (300 seconds), enforced by `ci-report` |
| Tier A UI step timeout | 20 minutes |
| Setup budget | 30 minutes (1800 seconds), enforced by `check-setup-budget.sh` and `ci-report` |
| Job timeout | 45 minutes |
| Playwright test timeout | 30 seconds default; specific session/installer tests raise it |
| Playwright action/navigation timeout | 10 / 20 seconds |

The setup window starts at job start and ends when the runner stamps
`UI_SMOKE_SPECS_STARTED_MS` after provisioning and immediately before
Playwright. The guard fails closed for missing, empty, non-numeric, negative,
zero, reversed or over-budget stamps. `ci-report` repeats the same validation on
the retained evidence.

### Every-run evidence in CI

The workflow retains sanitized summaries on every run and uploads only the
explicit allowlist after retained-set verification succeeds. If any pre-run
step fails, or the run is cancelled before or during setup, the workflow writes
a sanitized setup-failed summary, JUnit and manifest through
`scripts/lib/ui-smoke-report.sh` and `setup-report.mjs` instead of leaving the
upload step empty. A complete runner-retained set is left untouched; a partial
set with the authoritative summary is repaired; an unrepairable set is replaced
with a setup-failed set so `ci-report` sees exactly one summary.

CI artifacts use 90-day retention. Local retained summaries stay in
`tests/ui/summaries/` through qualification; the directory is gitignored.

### Qualification (as of 2026-11-06)

Observation start date: **2026-11-06**.

Completed Tier A observation runs recorded for this runbook:

- PR run **42231785260**;
- `workflow_dispatch` run **42231791666**.

No 30-day qualification period has elapsed. Those two runs are not a stability
history, and the UI check is not yet eligible for required-check promotion.

Qualification requires, in #2086:

- at least **30 consecutive days**;
- at least one **complete master Tier A run per day**;
- at least **30 complete runs**;
- no skips, cancellations, setup failures, missing daily results or partial
  selections counted as complete;
- first-attempt/retry counts, durations and every failure's cause/resolution;
- environment and commit identity for each run;
- no unexplained first-attempt failures or unresolved flakes;
- a restart of the affected tier's window after material contract or harness
  changes.

Tier B needs its own complete qualification history before promotion. Tier B,
the installer and Known Failure machinery are optional for early Tier A
observation and do not delay it.

### Promotion and rollback

Promotion is a maintainer operation after qualification:

1. confirm `check-ui-required.sh` shows the check is not already required;
2. configure branch protection or a ruleset to require `UI Smoke (Tier A)`;
3. prove a failing ordinary PR cannot merge;
4. document bypass, disabled and skipped behaviour;
5. record the date, commit and responsible maintainer.

Rollback is also a maintainer operation. Removing the execution variable alone
does not remove a required check and leaves the check unsatisfied. To roll back,
remove or disable the required check deliberately, then document the reason and
the restored observation window. Tier B promotion, if any, is separate and must
not replace Tier A.

## Boot and transport contract

The current transport is `index.php?r=<controller>/<action>`. The current boot
path is the `bootstrap/getEnvironment` JSON request issued before views bind.
The suite asserts:

- `bootstrap/getEnvironment` is completed on login, install, shell and Tier B
  pages;
- its payload contains `status: OK` and the expected `loggedin`, `locale`,
  `check_updates`, `check_notices`, `debug`, `csrf`, `session_timeout` and
  `pki_key` fields;
- the shell automatically loads `account/index` and `items/notifications`;
- account pages load `accountFile/list` and `items/accountsUser` where
  applicable.

Future transport changes must update the suite in the same change:

- **#2096** removes `bootstrap/getEnvironment` and folds it into the shell
  render. It must update boot probes, expected actions, payload assertions,
  login/install contracts and the negative proof that missing initialization
  fails. It also owns additional logout/install browser compatibility checks.
- **#2097/#2098** split page and fragment routes. They must update response
  predicates, expected action routes and both in-shell/direct navigation-mode
  contracts.

Do not keep an obsolete matcher or assert both old and future contracts as a
compatibility scheme. Clean URLs (ADR-0006; tracker #2064 / #2073) are
independent, and the future attachment/binder choice remains pending **#2116**.
This runbook does not claim either as implemented.

## Coverage boundaries and non-goals

Disabled update/notice coverage is explicit:

- the fixture disables update and notice checks (`checkUpdates=false`,
  `checkNotices=false`);
- `bootstrap/getEnvironment` returns those values and the shell/install boot
  probes assert `check_updates: false` and `check_notices: false`;
- the shell only calls `getUpdates`/`getNotices` when the corresponding flag is
  true, so it does not dispatch `status/checkRelease` or `status/checkNotices`
  under this configuration;
- `status/checkRelease` has no matching controller: the status controller serves
  `status/status`, and `status/checkNotices` is a separate controller. The
  missing-controller defect is therefore not exercised, is not detected by the
  15-second readiness deadline under the fixture, and is not a Known Failure
  admitted by this suite.

The suite does not exercise any status route. It only observes that update and
notice checks are reported as disabled.

The suite is not:

- a general end-to-end or business-workflow suite;
- a test of save/delete/copy/export/upload/dialog workflows;
- a layout, geometry or visual-snapshot suite;
- a replacement for authorization, escaping or cryptographic compatibility
  tests;
- a replacement for HTTP Integration Tests;
- a JSON-RPC API test (Page-load Actions are not the `api.php` API);
- an auto-filing issue system;
- a hosting-mode installer test.

HTTP Integration Tests remain required for template migrations. Tier A gates
transport/boot changes only; it does not gate the whole rendering rewrite.

## Troubleshooting

### Docker is unavailable or permission is denied

The runner fails before creating anything if the Docker command or an image is
missing. Check:

```sh
docker version
docker image inspect php-testing-composer:latest
docker image inspect mariadb:10.3
```

Do not work around this by using host PHP. Build or pull the images only with
explicit human approval. If the runner reports a Docker wrapper error, source
`.ai-cli/env.sh` for AI sessions or use the host `docker` command for
contributor runs.

### Disk is full (`ENOSPC`)

Docker layers, MariaDB data, Chromium and run roots all consume disk. Check
`docker system df` and free space. Remove only run-owned containers, networks
and `/tmp/sp-ui-smoke.*` directories, or rely on the runner's cleanup. Do not
run `docker system prune` or remove unrelated volumes without approval.

### Missing Chromium

The runner requires `tests/ui/.browsers` or an explicit
`SP_UI_CHROMIUM_EXECUTABLE`. Reinstall the pinned browser:

```sh
( cd tests/ui
  PLAYWRIGHT_BROWSERS_PATH="$PWD/.browsers" ./node_modules/.bin/playwright install chromium
)
```

Use the explicit executable fallback only for local diagnosis; it is not the
pinned-Chromium CI path.

### Database initialization or readiness fails

The runner waits up to 120 seconds for MariaDB to accept TCP connections from
the application network. Common causes:

- a stale run-owned container or network from a killed run;
- the database already exists because a previous run reused a name;
- Docker networking or MariaDB startup failure;
- a precreated database or `MYSQL_DATABASE` supplied by hand.

The runner refuses a preexisting database and never precreates one. Remove only
stale run-owned resources, then rerun. Do not load `schemas/dbstructure.sql`
for the seeded instance or the browser wizard.

### Session loss or unexpected login redirects

Do not add a re-login workaround. Inspect the retained failure evidence and the
route that reported `session-loss`, `invalid-response` or `login-navigation`.
Check that the test did not visit the login route, that the fixed User-Agent and
single-worker CSRF context were preserved, and that the 120-second SID rotation
test still passes. Actual session loss is a defect to fix, not a Known Failure.

### A page load times out at 15 seconds

The 15-second deadline covers expected actions, DOM conditions, the quiet window
and drained diagnostics. Inspect the reported missing/pending routes and the
observed route set. A hanging action, a missing controller for an action the
suite actually dispatches, a stuck overlay or a late body-inspection failure
will surface here. The `status/checkRelease` defect does not surface because the
fixture disables update checks and the shell never dispatches that request.
Increase timeouts only when the contract genuinely needs more time; do not mask a
missing action.

### Evidence upload is skipped or rejected

The workflow verifies the retained set before upload. Check that the per-run
directory contains exactly one `run-summary.json`, `junit.xml` and
`upload-manifest.json`, and that failure files stay under
`failures/**/{screenshot.png,console.log,failure.json}`. `error-context.md`,
HAR, traces, video, authentication state and configuration snapshots are
forbidden. Re-run verification with:

```sh
node tests/ui/src/verify-manifest.mjs \
  --manifest <run-dir>/upload-manifest.json --directory <run-dir>
```

A partial or duplicate set is never published.

### Setup budget exceeded

`check-setup-budget.sh` and `ci-report` both fail when the setup window is
missing or over 1800 seconds. Check that the workflow stamped
`UI_SMOKE_SETUP_STARTED_MS` and that the runner emitted
`UI_SMOKE_SPECS_STARTED_MS` after provisioning. Reduce image/build wait time,
MariaDB startup time or fixture setup; do not count spec execution as setup.
For a local manual evaluation, pass both stamps explicitly:

```sh
node tests/ui/src/ci-report.mjs \
  --summaries tests/ui/summaries/tier-A/<run-id> \
  --tier A --budget-seconds 300 --setup-budget-seconds 1800 \
  --setup-started-ms <start-ms> --setup-finished-ms <finish-ms>
```

### Installer rerun or isolation failure

Never retry the wizard against an installed instance and never reuse a
`--keep` run. Rerun with a clean instance:

```sh
TESTS_UI_ENABLED=true SP_UI_TIER=INSTALL ./scripts/ui-smoke.sh
```

`reset_install_instance` destroys only wizard-owned resources and creates a new
database/container/network identity. If the seeded-state isolation proof fails,
the installer summary is replaced with a forced setup-failed result and the
runner exits non-zero. Treat this as a real isolation defect.

### `--keep` inspection

`--keep` leaves the run root, network and containers for inspection. Do not
reuse them for another run. After inspection, remove the printed run-owned
resources and run root. Use `--keep` only for debugging, never for
qualification.

## Reference commands

```sh
# One-time prerequisites
npm ci
bash scripts/build-assets.sh
source .ai-cli/env.sh
.ai-cli/bin/ai-run --timeout 600 -- "$PHP_BIN" /usr/bin/composer install \
  --prefer-dist --no-interaction --no-plugins --no-scripts
( cd tests/ui
  npm ci
  npm run typecheck
  PLAYWRIGHT_BROWSERS_PATH="$PWD/.browsers" ./node_modules/.bin/playwright install chromium
)

# Harness checks without provisioning
bash -n scripts/ui-smoke.sh scripts/lib/ui-smoke-report.sh \
  scripts/ci/check-setup-budget.sh scripts/ci/check-ui-required.sh
( cd tests/ui && npm run typecheck && npm run test:unit )

# Disabled path
TESTS_UI_ENABLED=false ./scripts/ui-smoke.sh

# Real runs
TESTS_UI_ENABLED=true ./scripts/ui-smoke.sh
TESTS_UI_ENABLED=true SP_UI_TIER=B ./scripts/ui-smoke.sh
TESTS_UI_ENABLED=true SP_UI_TIER=INSTALL ./scripts/ui-smoke.sh
TESTS_UI_ENABLED=true SP_UI_TIER=A SP_UI_INSTALL=1 ./scripts/ui-smoke.sh

# Diagnose one retained run
node tests/ui/src/ci-report.mjs \
  --summaries tests/ui/summaries/tier-A/<run-id> \
  --tier A --budget-seconds 300 --setup-budget-seconds 1800 \
  --setup-started-ms <start-ms> --setup-finished-ms <finish-ms>
node tests/ui/src/ci-report.mjs \
  --summaries tests/ui/summaries/tier-B/<run-id> \
  --tier B --budget-seconds 300 --setup-budget-seconds 1800 \
  --setup-started-ms <start-ms> --setup-finished-ms <finish-ms>
node tests/ui/src/ci-report.mjs \
  --summaries tests/ui/summaries/tier-install/<run-id> \
  --tier INSTALL --budget-seconds 300 --setup-budget-seconds 1800 \
  --setup-started-ms <start-ms> --setup-finished-ms <finish-ms>
```

## File map

| Path | Purpose |
| --- | --- |
| `scripts/ui-smoke.sh` | Shared runner: prerequisites, isolation, provisioning, Playwright, retention, cleanup. |
| `scripts/lib/ui-smoke-report.sh` | Setup-failure reports, retention verification, single-summary checks. |
| `scripts/ci/check-setup-budget.sh` | Fail-closed setup-window guard. |
| `scripts/ci/check-ui-required.sh` | Read-only required-check verification. |
| `.github/workflows/ui-smoke.yml` | Tier A PR/master/dispatch/daily caller: gate, stable check mirror. |
| `.github/workflows/ui-smoke-tier-b.yml` | Independent Tier B daily/manual caller: gate, stable check mirror. |
| `.github/workflows/ui-smoke-run.yml` | Shared reusable runner: setup, provisioning, Playwright, budgets, evidence upload. |
| `tests/ui/src/tiers.mjs` | Tier-to-spec mapping. |
| `tests/ui/src/install-selection.mjs` | Combined-run ordering, retry policy and summary roots. |
| `tests/ui/src/preflight.mjs` | Run-root, database, origin and ownership refusals. |
| `tests/ui/src/install-layout.mjs` | Seeded/installer namespace overlap checks. |
| `tests/ui/src/install-lifecycle.mjs` | Wizard workspace reset and new identity. |
| `tests/ui/src/install-guard.mjs` | Refuse installed, precreated or populated wizard instances. |
| `tests/ui/src/verify-manifest.mjs` | Upload-manifest/allowlist verification. |
| `tests/ui/src/uploads.mjs` | Artifact allowlist, sanitized retention and manifest generation. |
| `tests/ui/src/sanitize.mjs` | URL/text/path redaction and screenshot masks. |
| `tests/ui/src/readiness.ts` | ActionTracker, 15-second deadline and quiet window. |
| `tests/ui/src/diagnostics.mjs` | Route/response/session/markup detector. |
| `tests/ui/src/session.ts` | Live worker cookie jar; no snapshot restore. |
| `tests/ui/src/contracts.ts` | Page/mode contracts and dispatch attributes. |
| `tests/ui/src/tier-b.ts` | Tier B index cases, shared session and fault canary. |
| `tests/ui/specs/` | Login, shell, Tier B and installer specs. |
| `tests/ui/fixtures/` | Committed synthetic fixture, seeder and fault fixtures. |
| `tests/ui/known-failures.json` | Known Failure registry (initially empty). |
| `tests/ui/summaries/` | Local retained evidence, gitignored. |
