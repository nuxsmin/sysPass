# UI Workflow Tests observation

## Purpose and non-goals

UI Workflow Tests are two independent, reporting-only observations for the
JavaScript-to-backend wiring contracts accepted by ADR-0012:

| Selection | Check name | Contract |
| --- | --- | --- |
| WIRING | `UI Workflow (navigation wiring, reporting-only)` | Five ACL-gated administrator menu controls dispatch their expected `getContent` Action and render route-specific content. |
| WRITE | `UI Workflow (account creation, reporting-only)` | The Account create form dispatches one `account/saveCreate` POST, receives the success envelope and persists the account through an independent authorized read. |

Non-goals: these checks are not merge-required, not Tier A or Tier B evidence,
not installer evidence and not a replacement for unit, HTTP integration or
DB-backed tests. They do not assert layout, visibility-only behaviour, active
state, translated text, restricted-user denial or arbitrary workflow
combinations.

The authoritative JS-to-backend wiring inventory is [ui-interaction-inventory.md](ui-interaction-inventory.md).

## Selection commands

```sh
TESTS_UI_ENABLED=true SP_UI_TIER=WIRING ./scripts/ui-smoke.sh
TESTS_UI_ENABLED=true SP_UI_TIER=WRITE ./scripts/ui-smoke.sh
```

The selections are independent. `WIRING` runs only
`specs/adminNavigation.wiring.spec.ts`; `WRITE` runs only
`specs/accountCreate.write.spec.ts`. Lower-case tier tokens are accepted and
normalized by the shared runner. A partial, filtered or explicit-subset run is
never a complete observation.

## Dedicated switch

`.github/workflows/ui-workflow-tests.yml` is gated by its own repository
variable:

```text
TESTS_UI_WORKFLOW_ENABLED == 'true'
```

This switch does not affect Tier A (`TESTS_UI_ENABLED`), Tier B
(`TESTS_UI_TIER_B_ENABLED`) or the installer. When the switch is unset or not
exactly `true`, both workloads and both stable mirror jobs are skipped. A skipped
check must never be counted as a complete observation.

## Schedule and check identity

The workflow runs on a daily schedule at `27 5 * * *` and on
`workflow_dispatch` with an optional `diagnostic_retry` boolean that defaults to
`false`. It has no `pull_request` or `push` trigger. The schedule minute differs
from Tier A (`17 4 * * *`) and Tier B (`47 4 * * *`).

The stable check names are carried by one-step mirror jobs:

- `UI Workflow (navigation wiring, reporting-only)`
- `UI Workflow (account creation, reporting-only)`

Each mirror requires its corresponding workload result to be `success`. Mirrors
do not use `continue-on-error`, and a skipped workload skips its mirror. Neither
check is qualified for required-check promotion by this workflow.

## Isolation, reset and cleanup

Both selections use the shared runner and the same disposable, runner-owned
seeded instance used by the seeded tiers. The runner owns the MariaDB container,
network, web server and run root, and removes them on success, failure,
cancellation or interruption. It never reuses a developer instance, an external
`E2E_BASE_URL` or a production database.

Fixture reset removes only the stable fixture rows. The WRITE selection creates
exactly one synthetic Account per run and does not delete arbitrary Accounts
created by earlier runs, so the target instance must be disposable. Never point
either selection at developer or production data.

Prerequisites are the same as the shared UI harness:

- `php-testing-composer:latest` and `mariadb:10.3` images available locally;
- root assets built with `npm ci` and `bash scripts/build-assets.sh`;
- `tests/ui` dependencies and pinned Chromium installed with `npm ci` and
  `./node_modules/.bin/playwright install chromium`.

The workflow performs those setup steps itself. The runner does not pull images,
build images or download Chromium during a local run.

## Evidence

Every run retains sanitized evidence under its own selection root:

| Selection | CI summary root | Local retained root |
| --- | --- | --- |
| WIRING | `${{ runner.temp }}/ui-summaries/tier-WIRING/` | `tests/ui/summaries/tier-wiring/<run-id>/` |
| WRITE | `${{ runner.temp }}/ui-summaries/tier-WRITE/` | `tests/ui/summaries/tier-write/<run-id>/` |

Artifacts use the `ui-workflow` family, for example
`ui-workflow-tier-wiring-<sha>-<run-id>` and
`ui-workflow-tier-write-<sha>-<run-id>`. Tier A and Tier B keep the unchanged
`ui-smoke` family.

The recorded fields include:

- selection tier, selected specs and whether the selection was partial;
- run and attempt identity, commit SHA, branch, dirty state and environment;
- status, complete-selection flags, selected/attempted/completed/failed/
  skipped/not-run counts, first-attempt results, retries, flaky count and
  failure classes;
- per-test attempts, durations, failure routes/problems and sanitized messages;
- started/finished timestamps, spec duration and the setup window;
- known-failure annotations and registry errors;
- `completeTierWiring` and `completeTierWrite`, which never set
  `completeTierA`, `completeTierB` or `completeTierInstall`.

The sanitized upload allowlist is exactly the existing shared list:

- `run-summary.json`
- `junit.xml`
- `upload-manifest.json`
- `failures/**/screenshot.png`
- `failures/**/console.log`
- `failures/**/failure.json`

Traces, videos, HAR files, request or response bodies, auth state, cookies,
DOM snapshots and error-context files are never uploaded. Synthetic WRITE
password, name, login and ciphertext canaries are covered by the unit canary.

Artifacts are retained for 90 days. The durable run table below is the
long-lived observation record.

## Reporting-only status

Both checks are **reporting-only**. They are not merge-required, are not part of
the #2086 Tier A required-check policy and must not be added to branch
protection or a ruleset during observation. `scripts/ci/check-ui-workflow-required.sh`
is a read-only helper that verifies neither new check name is already required;
it never changes repository settings. The existing #2086
`scripts/ci/check-ui-required.sh` and the Tier A policy remain unchanged and,
because the new workflow calls the shared `ui-smoke-run.yml`, that older scanner
may report the new workflow as an aggregate reference requiring manual review;
this is advisory, does not make either new check required, and
`scripts/ci/check-ui-workflow-required.sh` is the authoritative read-only check
for the new names.

## Troubleshooting

| Symptom | Action |
| --- | --- |
| Both workloads and mirrors are skipped | Set repository variable `TESTS_UI_WORKFLOW_ENABLED=true`. Confirm the value is exactly `true`, not `1` or `yes`. |
| A lower-case tier does not run | The shared runner normalizes the token, but the caller must pass `WIRING` or `WRITE`. Do not add another tier spelling. |
| Setup fails before Playwright | Inspect the setup-failed summary, its selection-specific run ID (`ui-workflow.wiring-ci-setup-*` or `ui-workflow.write-ci-setup-*`) and the workflow logs. Fix the prerequisite instead of retrying blindly. |
| A retry turns a failure green | The run is flaky and does not count as a complete observation. Investigate the first attempt; do not rely on the retry. |
| Artifact name looks like `ui-smoke` | Confirm the caller tier is `WIRING` or `WRITE`; those selections must use the `ui-workflow` artifact family. |
| `ci-report` reports no summary or several summaries | Pass the single per-run directory or the selection's single retained root. Do not point a report at a parent that contains other selections. |
| A WIRING or WRITE report is requested as Tier A or Tier B | Expected: selection completeness is independent. Re-run `ci-report` with the matching `--tier WIRING` or `--tier WRITE`. |
| WRITE leaves an extra account | The run instance must be disposable. Reset removes fixture rows only; do not reuse developer or production databases. |

## Qualification criteria

Observation starts only after this workflow is merged to the default branch and
the first scheduled run has completed. Until then there is no observation
window.

A selection qualifies only when all of the following hold:

- at least 30 consecutive days of observation;
- at least one complete run of that selection per day;
- at least 30 complete runs of that selection;
- counts exclude skipped, cancelled, setup-failed, partial, failed, flaky and
  timed-out runs;
- zero unexplained first-attempt failures;
- a retry-success run is flaky and never counts;
- environment and commit identity are recorded for every counted run;
- 90-day artifact retention is available together with this durable table.

The window restarts after material changes to the selection's spec, contract,
session/boot transport, fixture seed schema or the shared
provisioning/evidence path. Restart WIRING and WRITE independently unless the
change affects both selections.

Promotion is a separate maintainer action after qualification. It is not implied
by merging this workflow or by an execution switch.

## Baseline and durable run table

Baseline as of 2026-11-12: **0 complete runs** for each selection. Observation is
**not started**, both checks are **not qualified** and both are
**reporting-only**.

| Date | Selection | Run ID | Commit | Complete | First attempts/retries | Failures/skips | Duration | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 2026-11-12 | WIRING | — | — | 0 | — | — | — | Baseline only; observation not started |
| 2026-11-12 | WRITE | — | — | 0 | — | — | — | Baseline only; observation not started |

Add one row per scheduled or manually dispatched run. Record `Complete` only
when the corresponding `completeTierWiring` or `completeTierWrite` flag is true
and the run was not skipped, cancelled, setup-failed, partial, failed, flaky or
timed out.
