# UI Smoke Suite

Starter guide for the isolated browser smoke harness. The full contributor, triage and qualification runbook arrives with #2085.

`scripts/ui-smoke.sh` is shared repository tooling for contributors, reviewers and CI. `.ai-cli/` is ONLY for AI agents; it is not the canonical home for this runner or any other human/CI entry point.

Current scope: #2076 / #2077 / #2079 / #2081 / #2082 / plan U1, U2 and U4. The suite provisions a fresh synthetic instance with `sp:install`, loads the anonymous login page, and then exercises the seeded authenticated shell through positive readiness contracts. Tier A covers account view, create and edit in both in-shell navigation and direct full-page URL modes. Tier B covers the seven in-shell index navigations: `accessManager`, `itemManager`, `securityManager`, `configManager`, `notification`, `userSettingsManager` and `plugin`.

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
   make -C scripts/docker build          # php-testing-composer:latest
   docker pull mariadb:10.3
   ```

   `scripts/docker/` is the shared build path for contributors and CI; the
   workflow builds the same Dockerfile with the same `scripts/docker` build
   context.

   PHP 8.3 is the supported floor (`composer.json`: `>=8.3 <8.6`).

## Run

```sh
TESTS_UI_ENABLED=true ./scripts/ui-smoke.sh
```

`TESTS_UI_ENABLED` must be exactly `true`. Any other value, including unset,
makes the runner exit before checking prerequisites or creating resources. A
skipped run writes no summary and is never a complete Tier A observation.

Use `--keep` only for debugging; it leaves the run root, network and containers in place for inspection. Do not reuse them for another run.

### Tier selection

`SP_UI_TIER` selects the tier and defaults to `A` when unset. A default or
explicit Tier A run executes only `login.spec.ts` and `shell.spec.ts`; it never
picks up Tier B specs. Select Tier B explicitly:

```sh
TESTS_UI_ENABLED=true SP_UI_TIER=B ./scripts/ui-smoke.sh
```

`SP_UI_SPECS` still narrows a run to an explicit subset, which is recorded as a
partial selection for that tier. Tier A retains its evidence under
`tests/ui/summaries/tier-A/` and Tier B under `tests/ui/summaries/tier-B/`.
The roots are siblings, not nested: discovery is non-recursive, so a Tier A
report can never walk into Tier B history and vice versa. Set
`SP_UI_SUMMARY_DIR` to override either root. The tier token is case-insensitive
(`A`/`a`, `B`/`b`); any other token is rejected as a usage error instead of
being reported as a "Tier Z" observation.

Tier B is annotation-only until #2086 qualifies it: its run keeps a real,
non-zero exit status when an index fails, `ci-report --tier B` emits warning
annotations for each named route and a failure error, and a Tier B summary can
never satisfy `completeTierA`. Tier B authenticates once for the seeded suite
and reuses the live worker cookies, so the seven specs share one login while
still carrying legitimate SID rotation. The manual/CLI path is:

```sh
setup_started_ms=$(date +%s%3N)
SP_UI_TIER=B TESTS_UI_ENABLED=true ./scripts/ui-smoke.sh
# The runner prints UI_SMOKE_SPECS_STARTED_MS after provisioning.
node tests/ui/src/ci-report.mjs \
  --summaries tests/ui/summaries/tier-B/<run-id> \
  --tier B --budget-seconds 300 \
  --setup-started-ms "$setup_started_ms" \
  --setup-finished-ms <UI_SMOKE_SPECS_STARTED_MS value>
```

Use the printed per-run directory; the tier root accumulates history and is not
a single-summary input. Passing the per-run directory keeps the "exactly one
summary" rule meaningful as history grows. Omitting the setup stamps correctly
fails closed rather than qualifying the run.

That report is separate from the Tier A workflow result. The daily Tier B
history lives in its own workflow (`.github/workflows/ui-smoke-tier-b.yml`), is
gated by its own `TESTS_UI_TIER_B_ENABLED` variable, selects only Tier B, and
cannot become a required replacement for the Tier A check. #2086 owns Tier B
promotion only after its own complete qualification history; until then the
workflow is annotation-only and a failure remains a failed job conclusion.
The removal of a Tier B entry or the promotion decision belongs to that
qualification work, not to Tier A maintenance.

CI and human contributors invoke `scripts/ui-smoke.sh` directly. AI sessions may source `.ai-cli/env.sh` for the approved wrappers, but `.ai-cli/` is not part of the shared CI or contributor invocation path.

## CI observation

`.github/workflows/ui-smoke.yml` is an isolated workflow so its daily schedule
can only start the UI job. It runs on pull requests targeting `master` or
`feat/*`, pushes to `master`, manual dispatch and a daily schedule. Scheduled
runs check out `master` explicitly and record the observed commit.

`TESTS_UI_ENABLED` is a repository variable used as an execution switch. When
it is not exactly `true`, the job is skipped; a skipped job is not Tier A
evidence. The switch is not a merge gate. Tier A failures fail the UI check,
but promoting it to a required check is a separate maintainer operation.

### Required-check verification before observation (2026-11-05)

The read-only check was executed against `nuxsmin/sysPass` on 2026-11-05 with
an authenticated `gh` wrapper. The results were mixed and are recorded exactly
as observed rather than as a "not required" verdict:

- `GET /repos/nuxsmin/sysPass/branches/master/protection` succeeded and
  returned `required_status_checks.contexts: []` and
  `required_status_checks.checks: []`.
- `GET /repos/nuxsmin/sysPass/rulesets` succeeded and returned `[]`.
- The aggregate-workflow scan could not complete: `GET
  /repos/nuxsmin/sysPass/contents/.github/workflows` returned HTTP 404 because
  remote `master` (observed commit `23d8989156f2db685139a056c390da97bc3ac95d`)
  has no `.github/workflows` directory. The script therefore exited 1 instead
  of claiming the check was not required.
- A separate read-only `GET /repos/nuxsmin/sysPass/actions/workflows` returned
  one active workflow, `Feature Build & Test` (`.github/workflows/feature.yaml`);
  its recent runs were on feature branches, not `master`. That does not
  establish a required-check reference on `master`, but the missing
  default-branch workflow directory remains an unresolved blocker for the full
  aggregate scan.

No branch protection, ruleset, variable or required-check setting was changed.
Required-check promotion remains a maintainer operation after qualification.

The same read-only verification is reproducible with:

```sh
source .ai-cli/env.sh
GH_BIN="$GH_BIN" bash scripts/ci/check-ui-required.sh nuxsmin/sysPass
```

The script reads branch protection contexts/checks, ruleset required status
checks and the other workflow definitions, prints what it found, and exits
non-zero when the UI check already appears required, an aggregate reference
needs review, or a required read cannot be completed. It only issues read-only
GET requests; it is not run by CI and changes no settings. Workflow content is
requested through the REST raw media type, so it needs no GNU-specific
`base64 --decode` and works on BSD/macOS hosts. If `gh` is unavailable or
unauthenticated it fails with exit 127; a failed API read exits 1 instead of
being reported as "not required".

The job builds the shared PHP 8.3 testing image from
`scripts/docker/Dockerfile.php-testing`, pulls MariaDB 10.3 and lets the
runner create its own disposable database container and network. It does not
reuse the integration job's database, schema load or container, and it does
not set `MYSQL_DATABASE`. The setup window is stamped at job start and again by
the runner immediately before Playwright starts, after provisioning (database
start, `sp:install`, fixture seeding and web readiness), so the recorded setup
duration includes real setup and never includes spec execution. The bounds are
distinct: page readiness uses its 15-second deadline, the Tier A spec run has a
20-minute step timeout and a five-minute spec-duration budget, setup has
explicit per-step timeouts plus a 30-minute aggregate guard, and the job has a
45-minute timeout. The tested `scripts/ci/check-setup-budget.sh` guard runs after
the runner and fails the job when either stamp is missing, empty, non-numeric,
negative, zero or not ordered before the other, and when the observed setup
window exceeds its budget; `ci-report` repeats the same validation on the
retained evidence so an invalid or over-budget window is never a complete
Tier A observation. The runner retains sanitized summaries on
every run and the workflow uploads only the explicit allowlist with 90-day
retention, and only after the retained-set verification succeeds, so partial or
duplicate sets are never published. If any pre-run step fails, or the run is
cancelled before or during setup, the workflow writes the same sanitized
setup-failed summary, JUnit and manifest through the shared helpers instead of
leaving the upload step empty.

Tier B uses its own `SP_UI_TIER=B` selection, spec set and sibling summary
root. Its daily/manual history is a separate annotation-only workflow
(`.github/workflows/ui-smoke-tier-b.yml`) gated by
`TESTS_UI_TIER_B_ENABLED`; it is not merge-required, cannot gate or hide Tier A,
and a failing Tier B run keeps a failed job and workflow conclusion instead of
passing silently. #2086 promotes Tier B only after its own qualification
history and records the responsible maintainer for each admission removal. The
installer wizard remains a later addition and similarly must not hide a Tier A
failure.

The runner requires Docker permission and creates runner-owned containers, a network, a database and a run directory. It never pulls or builds images, never reuses a developer server or database, and refuses an externally supplied `E2E_BASE_URL`.

The separate Tier B workflow runs daily and on manual dispatch, and is gated by
the repository variable `TESTS_UI_TIER_B_ENABLED == 'true'`. Inside that
workflow the shared runner's `TESTS_UI_ENABLED` switch is literal `true`; it is
not a second gate and it never controls the Tier A workflow. Enabling the Tier B
variable is a maintainer action and no GitHub setting is changed by this
documentation or the repository files. A failed Tier B evaluation exits
non-zero with `continue-on-error: false`, so the job/workflow conclusion remains
failed and the failure cannot look like a silent pass. Required-check promotion
is not implied and remains a separate maintainer operation after #2086.

Selection is `SP_UI_TIER` (default `A`) plus optional `SP_UI_SPECS`. A subset or grep-filtered run is recorded as partial. Default retries are zero locally and in CI. An explicit `SP_UI_DIAGNOSTIC_RETRY=1`, or the `workflow_dispatch` `diagnostic_retry` input, enables one diagnostic retry with `failOnFlakyTests`; first-attempt evidence is preserved and a retry-success is reported flaky and rejected by `ci-report` as incomplete Tier A. CI never enables retries implicitly. `SP_UI_LOCAL_TRACE=1` is an opt-in trace for the synthetic runner instance only, is forced off in CI, and is never uploaded.

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
| Output | `<run root>/output` (summary, JUnit, Playwright output) | Per run, removed afterwards |
| Retained evidence | `tests/ui/summaries/tier-A/<run-id>/` and `tests/ui/summaries/tier-B/<run-id>/` (allowlisted summary/JUnit/manifest) | Kept through qualification |
| Fixture credentials | `tests/ui/fixtures/e2e-fixture.json` | Committed; synthetic only |
| Authentication state | Playwright worker memory and `<run root>/sessions` | Never written to artifacts; removed afterwards |

The web server listens only on a random `127.0.0.1` port and is reachable only for the run. The origin is passed to Playwright as `SP_UI_BASE_URL` after the runner has proved it belongs to this run. Browser locale is `en-US`; application locale is `en_US`; the User-Agent is fixed; there is one worker and zero retries by default. An explicit diagnostic retry requires `SP_UI_DIAGNOSTIC_RETRY=1` or the dispatch input and is always rejected as complete Tier A evidence when it only succeeds on retry.

## Evidence and artifacts

Every run writes `run-summary.json`, `junit.xml` and `upload-manifest.json` through the harness reporter. They record the selected tier/specs, commit and safe environment identity, attempted/completed/skipped counts, first-attempt results, retry counts, durations and normalized failure classes. The summary exposes `completeTierA` and `completeTierB` independently; only the selected tier can be complete, and a Tier B run never sets `completeTierA`. A run that is partial, setup-failed, cancelled or flaky is never reported as a complete success for its tier.

A failed attempt writes only sanitized diagnostics under `failures/`: a masked screenshot, sanitized console/error output and normalized route/URL/status information. Absolute file paths are reduced to repository-relative paths. No request or response bodies, HAR, trace, video, authentication state, cookies, configuration snapshot, private key, DOM snapshot or temporary fixture dump is uploaded. Playwright's automatic page snapshot is disabled at source (`PLAYWRIGHT_NO_COPY_PROMPT`), and any `error-context.md` file is removed by the reporter, the runner cleanup and the retention step before the artifact set is kept. It is not part of the allowlist.

`scripts/ui-smoke.sh` retains the allowlisted summary/JUnit/manifest under
`tests/ui/summaries/tier-A/<run-id>/` for Tier A and
`tests/ui/summaries/tier-B/<run-id>/` for Tier B on every run, including
failure, so qualification history does not depend on a failed run's temporary
output. The sibling roots and non-recursive discovery keep a Tier B summary
from being consumed by a Tier A report, or the reverse. `SP_UI_SUMMARY_DIR`
overrides the root for either tier. Report one retained run with
`ci-report --tier B --summaries tests/ui/summaries/tier-B/<run-id>`; passing the
accumulating tier root is intentionally rejected once it holds more than one
summary. The upload manifest records the retained set, and CI publishes only an
explicit allowlist: `run-summary.json`, `junit.xml`, `upload-manifest.json` and
`failures/**/{screenshot.png,console.log,failure.json}`. Before upload, verification checks the manifest against that same allowlist and rejects unknown, duplicate, unsafe, missing or unlisted entries, so the workflow's hardcoded paths and the manifest cannot silently diverge. The workflow never uploads the summary directory as a whole, and it does not resolve upload paths from the manifest dynamically because GitHub Actions cannot consume a manifest-supplied path set safely. Verification runs before upload and upload is gated on its success: a genuine Tier A test failure still publishes its complete verified set, while an empty, duplicated or partial set fails before publishing and is never uploaded.

If an `npm ci`, asset build, image build, Composer install or `tests/ui` install step fails before `scripts/ui-smoke.sh` runs, or the run is cancelled before or during setup, the workflow still writes a sanitized setup-failed summary, JUnit and manifest through `scripts/lib/ui-smoke-report.sh` and `setup-report.mjs`. When the runner already retained a complete set, the fallback leaves it untouched. A partial set that still has the authoritative `run-summary.json` is repaired through the normal retention path, which regenerates the missing derived JUnit/manifest artifacts. A partial set without a usable summary is replaced with a setup-failed set in the same directory, so `ci-report` sees exactly one summary and rejects it as incomplete Tier A evidence.

`tests/ui/src/*.test.mjs` includes a synthetic-canary end-to-end test that deliberately fails a Playwright spec and inspects the reporter/output/upload path for canary leakage in logs, URLs, DOM text and error output. `scripts/ui-smoke.sh` runs the whole Node test suite (`cd tests/ui && npm run test:unit`) before Playwright, and a harness failure stops the run before any container is created.

## Isolation and refusal rules

Before any container or database mutation, `tests/ui/src/preflight.mjs` rejects:

- a run root inside the repository, including `app/config`, `app/cache`, `app/temp`, `.git` and `vendor`;
- a symlinked run root or an owned path that resolves outside the run root;
- a non-empty run configuration directory;
- a database name that is not `sp_ui_<12 hex>` or is reserved;
- an origin that is not `http://127.0.0.1:<port>`;
- an externally supplied `E2E_BASE_URL`.

The runner also verifies `CONFIG_PATH`, `CONFIG_FILE`, `CACHE_PATH`, `LOG_FILE`, `session.save_path` and the effective `Path::CACHE`/`Path::TMP` bindings inside PHP before installing. MariaDB is started without `MYSQL_DATABASE`; normal `sp:install` creates the unique database and schema. Developer `app/config`, `app/cache` and `app/temp` fingerprints must be unchanged when the run finishes.

## Synthetic fixtures

`tests/ui/fixtures/e2e-fixture.json` is the committed source of truth for the isolated fixture. It contains only synthetic `E2E_*` credentials, stable logical IDs and fixture text. The runner reads the admin login, password and known Master Password from it and passes them to `sp:install` through the run-owned environment file; secrets are not passed on process arguments.

After installation, `tests/ui/fixtures/seed.php` applies the fixture:

- it configures the isolated `config.xml` through `ConfigFileService` with `filesEnabled=1`, update and notice checks disabled, `debug=false`, demo mode disabled and HTTP Basic auto-login disabled so the seeded form login uses database authentication;
- it pre-generates the run-owned PKI key pair with `CryptPKIHandler` so login encryption is deterministic, and writes user preferences needed by the session;
- it creates the demo user, group and profile plus a synthetic client, category, parent account, child account and child account file;
- it derives the login hash with `HashService`, the user vault with `UserMasterPassService` and the account ciphertext with `AccountCryptService`;
- it verifies the login hash, Master Password metadata, both user vaults, both account ciphertexts, user preferences, the file row and the application configuration before the server starts.

For a `--keep` debugging run, invoke `tests/ui/fixtures/seed.php` with the same run-owned environment file, network and repository/run-root mounts as the runner, using `--reset` or `--verify`. Do not point it at a developer database or configuration.

`--reset` deletes only the stable fixture rows and recreates them with the same logical IDs. The runner resets twice, verifies after each reset, and never touches `tests/SP` fixtures or `tests/res/datasets`. Session files and cookie jars stay in the run root or worker memory and are excluded from artifacts.

## Current checks

`tests/ui/specs/login.spec.ts` loads `index.php?r=login/index` and asserts the page-specific login form (`#box-login`, `#frmLogin`, field and button roots, dispatch attributes) plus the current `bootstrap/getEnvironment` boot contract (`status: OK`, `loggedIn: false`, `locale: en_US`, a null CSRF token for the anonymous session and a public key). It fails on uncaught page/console errors, unexpected failed requests and critical error markup.

`tests/ui/specs/shell.spec.ts` uses a separate authenticated worker session. It logs in through the UI exactly once, then runs sequentially: the first test asserts the shell root, completed `bootstrap/getEnvironment`, `account/index` and `items/notifications` actions, meaningful account-list content, a hidden loading overlay, a 300 ms action-scoped quiet window within the 15 s readiness deadline, disabled update/notice flags and no pending or failed routes. Later tests wait through the real 120-second SID rotation and prove that the rotated cookie carries into a fresh sequential context without a second login. Missing or pending routes are reported together with the observed route set.

The seven Tier B specs share the same live worker cookie jar. The first spec logs in through the UI and marks the worker session authenticated; the remaining specs reuse the live cookies and fail if the login route is observed again, so there is no per-spec login storm and no immutable snapshot restore. Anonymous/login tests keep their own clean Playwright contexts and never receive the seeded cookie jar. `bootstrap/getEnvironment` is the current boot contract; #2096 will replace it and must update these probes in the same change.

The scratch-only Tier B fault canary is opt-in with
`SP_UI_TIER_B_FAULT=accessManager/index`. It intercepts the real
`accessManager/index` response in that browser context, requires the actual
spec navigation to fail with the spec file and route named, removes the route
interception, and then proves the unmodified index loads again before rethrowing
the named failure. It is never set in CI and does not alter either tier's normal
selection.

## Known failure admissions

`tests/ui/known-failures.json` is the committed registry and starts empty. It is
not a quarantine list and it never suppresses a failing exit status or changes
a spec's assertions. An entry must match exactly one `(spec, route,
failureKind)` failure:

```json
{
  "schemaVersion": 1,
  "entries": [
    {
      "spec": "shell.spec.ts",
      "route": "account/index",
      "failureKind": "http",
      "reason": "deterministic account-index failure",
      "defect": "#2000",
      "admission": {
        "who": "maintainer",
        "date": "2026-11-06",
        "ref": "#2080"
      }
    }
  ]
}
```

The schema is strict. Unknown fields, missing reason/defect/admission metadata,
invalid admission dates, duplicate keys, regular expressions or glob keys and
unknown failure kinds are rejected. `defect` and `admission.ref` must be a
linked issue (`#<number>` or a GitHub issue URL); arbitrary strings are
rejected, and #2079 is explicitly refused because it is a fixed regression.
Only route-bearing deterministic detector kinds qualify: `http`,
`json-status`, `request-failed`, `body-inspection`, `missing-content` and
`empty-body`. Kinds the pipeline cannot key by `(spec, route, failureKind)` —
including `console`, `page-error`, `markup`, `assertion`, `timeout`,
`json-malformed` and `json-envelope` — are not admissible. `flaky`, `setup`,
setup/isolation, `session-loss`, `invalid-response`, `login-navigation`,
`login-page` and the catch-all `test-error` are also not admissible. A
first-attempt failure that passes on retry is always flaky and is never
admitted.

An attempt can emit several detector problems. Every problem is recorded as its
own `(route, failureKind)` tuple and the attempt is admitted only when all of
them have an entry; any unlisted problem remains a new failure and fails the
run.

Admission is an exceptional reviewed change, not a routine way to obtain a
green baseline. A new entry requires a linked defect, a concrete reason and an
explicit maintainer admission recorded in the registry commit. The registry is
therefore not restricted to shrinking: reviewed admissions may add entries, and
the maintainer who adds one owns its removal. Never add an entry for #2079 or
another defect that is supposed to remain fixed.

The matching failure is annotated in `run-summary.json` and `junit.xml`, but
the original failed count, first-attempt result, failure class and sanitized
evidence stay visible. `status` remains `failed` and `completeTierA` remains
`false`; an admitted failure run is never a clean pass, never exits zero and
never counts as a complete Tier A observation. Unlisted failures, and other
failures in the same spec, still fail the run.

The reporter records the complete known-failure evaluation in
`run-summary.json`. `ci-report` consumes that recorded evaluation as the single
authoritative source and never re-runs the registry against the retained tests,
so a failure the reporter rejected cannot be re-admitted later. A summary that
lacks the recorded fields is treated as incompatible and fails rather than
being silently re-evaluated.

When a registry entry's spec actually executes without the admitted failure,
the run fails with removal instructions. A filtered, skipped or unexecuted spec
does not prove the defect is fixed, never causes an entry to be treated as
stale, and keeps the run ineligible. Existing obsolete entries must be removed
rather than left in place. The registry used by the reporter can be overridden
for local experiments with `SP_UI_KNOWN_FAILURES`, but CI always uses the
committed file unless the workflow is changed.
