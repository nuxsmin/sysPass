# sysPass: Browser UI smoke suite for page-load actions

Status: proposed implementation, revised after quality review on 2026-11-04; nothing below is claimed implemented or
qualified.
Tracking: #2075 and #2076-#2086. Decision record: `docs/adr/0009-browser-smoke-suite-for-page-load-actions.md`.
Goal: detect missing or broken critical Page-load Actions and their rendering/initialization failures in an automated
browser run.
Non-goals: layout/geometry, save/delete/upload/copy/export workflows, dialogs, a locale/browser matrix, JSON-RPC
coverage, or comprehensive authorization/cryptography verification.

Vocabulary: `CONTEXT.md`. HTTP Integration Tests remain the broad rendering/security-contract safety net. Tier A gates
the transport and boot changes in #2089 Phase 4, not every template migration.

## 0. Delivery and dependencies

- GitHub Issues are the executable specs; keep their bodies and native dependencies synchronized with this plan.
- Start only when a task's prerequisites are complete. Status legend: `[ ]` pending, `[~]` active, `[x]` done, `[-]`
  dropped with reason.
- Use red-green slices and automated negative tests for the harness. Do not modify `tests/res/datasets/` or `tests/SP/`
  to accommodate this suite. A minimal production fix for the direct-account initialization defect is explicitly part of
  #2079, with focused regression evidence.
- Bring a small, complete Tier A observation job online before implementing the optional tiers or Known Failure
  machinery. Green observation is not merge gating or security approval.
- Do not create commits, change repository variables/protection, build images, or run Docker mutations merely because
  this plan describes them. Follow the CLI approval rules at implementation time.

| Status | Task | Issue | Scope                                                          | Depends on |
|--------|------|-------|----------------------------------------------------------------|------------|
| [ ]    | U1   | #2076 | Safe runner and fresh-install login skeleton                   | None       |
| [ ]    | U2   | #2077 | Valid fixture, authenticated shell and evolving session state  | U1         |
| [ ]    | U3   | #2078 | Positive contracts, readiness and failure detector self-tests  | U2         |
| [ ]    | U4   | #2079 | Six account navigation cases and direct-binding regression fix | U3         |
| [ ]    | U3-E | #2081 | Sanitized evidence and every-run result summaries              | U3         |
| [ ]    | U7   | #2084 | Early Tier A CI observation and daily execution                | U4, U3-E   |
| [ ]    | U8a  | #2085 | Complete contributor/triage/qualification runbook              | U7         |
| [ ]    | U8b  | #2086 | Evidence-based required-check promotion after 30 days          | U7, U8a    |
| [ ]    | U3-K | #2080 | Optional Known Failure registry; not a CI prerequisite         | U3         |
| [ ]    | U5   | #2082 | Seven Tier B indexes; independent observation/promotion        | U3         |
| [ ]    | U6   | #2083 | Isolated stateful installation check                           | U1, U3     |

## U1 - Harness, isolation and fresh installation (#2076)

Create `tests/ui/` with its own locked `package.json`, Playwright configuration and TypeScript configuration. Provide
`scripts/ui-smoke.sh`, its allowed-command entry, and minimal prerequisite/run instructions with this first slice.

- Use PHP 8.3, the supported floor (`composer.json`: `>=8.3 <8.6`), MariaDB 10.3, Chromium, `workers: 1`, and
  `retries: 0` locally and in CI. Use browser locale `en-US`, application locale `en_US`, and a fixed User-Agent/client
  address. CI also forbids focused tests and type-checks the TypeScript harness.
- Build the application assets from the root lockfile with `npm ci` and `bash scripts/build-assets.sh`. Then install the
  separate `tests/ui` lockfile with `npm ci`; install its pinned browser with
  `npx playwright install --with-deps chromium` in CI. No global packages or unpinned `npx` downloads. Document local
  dependency installation and approval prerequisites.
- Define exactly where PHP, MariaDB, Node and Chromium execute, their mounts, ports, network and lifetimes. The PHP
  server must persist for the run and be reachable only on the runner-owned local/test origin. Do not reuse an existing
  application server or accept an arbitrary external `E2E_BASE_URL`; verify the origin belongs to this run.
- Generate runner-owned configuration, cache, temp, session and output locations. Canonicalize paths; refuse existing
  developer configuration and aliases/symlinks into it. Verify effective PHP paths before installation, not just the
  parent shell's exports. The current PHP wrapper forwards neither `CONFIG_PATH` nor the `InstallCommand` variables and
  mounts only the repository: explicit forwarding and mounts are required.
- Isolate all runtime caches per instance, including `Path::CACHE/config.cache`, which is fixed under `app/cache` in
  `CoreDefinitions.php`. `CACHE_PATH` currently redirects DI compilation only; it does not isolate those application
  caches. Use isolated filesystem mounts/workspaces or an explicitly verified path-binding implementation, not an
  assumed environment override.
- Use a disposable MariaDB service with a unique, run-owned database name. Normal `sp:install` creates the database and
  schema: do not precreate `MYSQL_DATABASE` for that name or load `schemas/dbstructure.sql` first. A hosting-mode
  alternative must be explicitly configured and tested, never silently substituted.
- Forward installer variables from `InstallCommand::envVarsMapping` through the supported environment, without secret
  command arguments/logging. Install before checking installed-instance readiness. Provision/reset/cleanup must refuse
  non-owned database names and remove only this run's resources, including after failure or interruption.
- Start/validate the fresh instance and load the anonymous login page. The current boot action is
  `bootstrap/getEnvironment`; health checks must validate an expected response, not merely any HTTP 200 or a redirected
  installer page. Future boot changes update this probe in the same change.

Acceptance: one command succeeds on a clean checkout after documented prerequisites; two consecutive runs start clean;
negative preflight tests reject developer paths/non-owned databases/origins before mutation; effective paths and
database ownership are verified without secrets; developer configuration and runtime caches remain unchanged.

## U2 - Fixture, shell and authentication lifecycle (#2077)

The UI suite owns its fixture and reset procedure under `tests/ui/fixtures/`. Reuse the shared fixture's logical
examples where useful, not its unknown cryptographic state.

- Generate synthetic admin and demo-user records, master-password metadata, vault/account ciphertext and matching
  configuration using the current application cryptographic abstractions. Document generation and reset. Do not try to
  make old ciphertext usable by editing login hashes alone; either preserve a complete matching synthetic snapshot or
  regenerate compatible values during setup.
- Commit clearly named `E2E_*` synthetic credentials, including a known admin Master Password meeting installer rules;
  no unnecessary credential environment indirection. Never use real data or credentials. Keep active
  authentication-state files temporary, ignored and excluded from artifacts.
- Provide stable account/user/group/client identifiers, a synthetic attachment/list item and an eligible parent account
  so action-produced content can be asserted. Validate fixture unlock/decryption consistency during setup, not just
  shell authentication; this is fixture validation, not a browser reveal workflow.
- Set `filesEnabled=1`, disabled update/notice checks and `debug=0` in the isolated application configuration, not only
  SQL. Preserve the shared fixture and PHPUnit contracts.
- Keep anonymous tests in clean contexts without authenticated `storageState`. Authenticate once through the UI for the
  seeded suite. Carry forward legitimate cookie/SID changes between sequential isolated tests, or use another explicitly
  tested lifecycle-safe approach. A static authentication snapshot is insufficient after the application's 120-second
  SID regeneration. Never recover an actual session-lost failure by silently logging in again.
- Assert the shell's specific account-list content and completed `account/index` and `items/notifications` requests,
  plus the current boot contract. Prototype positive readiness here; U3 adds the complete detector and its
  fault-injection tests.

Acceptance: repeated reset yields the same logical fixture; known users and fixture cryptography are valid;
action-produced seeded content appears; authenticated reuse survives legitimate SID rotation across contexts without
re-login; the public login check cannot destroy the authenticated suite's session.

## U3 - Positive contracts, diagnostics and readiness (#2078)

Every spec declares a contract for its page and navigation mode: expected actions, page-specific ready root, meaningful
seeded/empty-state content, and dispatch/binding attributes. An action must be issued and complete successfully; a shell
root, static panel heading or placeholder option alone cannot establish readiness. Use real routes, not
`app/resources/actions.yaml` as an inventory.

- Attach listeners before navigation: context `weberror` or per-page `pageerror`, console errors, `request`,
  `requestfinished`, `requestfailed`, and `response`. Track Page-load Actions through body inspection, not just response
  headers; await bounded diagnostic work before passing.
- Fail on uncaught errors/rejections, console errors, unexpected failed requests, action HTTP status >=400, malformed
  expected envelopes, or parsed `status: ERROR`. Do not match JSON by substring. Attribute action failures to their
  route; report a source location/page when a global script failure has no attributable route rather than inventing one.
- Detect session loss through JSON `status: 10`, unexpected login redirects/pages/logout script responses, and missing
  authenticated fragment content/empty HTTP 200 responses. Distinguish invalid fragment responses from proven session
  loss when the cause cannot be established. Never re-authenticate automatically.
- Fail on applicable client error markup/toasts and server-rendered critical markup, including
  `.errors.round .msg-critical`, without requiring `#content` on login/install pages. Annotate warnings without failing;
  do not treat every `.errors.round` container as fatal because it also contains warnings.
- Readiness requires the positive contract, hidden `#wrap-loading`, all expected actions completed and inspected,
  drained diagnostics, and 300 ms without an in-flight Page-load Action. Apply one 15 s readiness deadline; report
  missing and pending routes or the unmet DOM/overlay condition, not just a generic timeout. Do not use global
  `networkidle`.
- Ignore only demonstrably expected navigation/teardown cancellations after the relevant contract, not arbitrary
  `net::ERR_ABORTED` or application errors. Make listener ownership/teardown explicit so one test cannot contaminate
  another.
- Keep core actions real in smoke specs. Use deterministic fault injection only to self-test the detector: suppress an
  expected dispatch, HTTP failure, JSON error/malformed body, JS error/rejection/console error, critical markup, missing
  content, stuck overlay/hanging action, session-loss variants, and non-fatal warnings. Assert late response-body
  diagnostics cannot escape the result.

Acceptance: each negative case reliably fails with the responsible route/condition; a missing request fails even with
non-empty surrounding markup; warning cases pass with annotations; all seeded shell checks remain green. No Known
Failure framework is needed for this slice or early CI.

## U4 - Tier A account pages and baseline fix (#2079)

Cover view, create and edit in both in-shell navigation and direct full-page URLs, independently asserting the contract
in each of the six cases.

- View/edit require successful `accountFile/list` and rendered fixture list content; create/edit require successful
  `items/accountsUser` and the eligible fixture option. Both navigation modes must initialize the account behavior.
- Reproduce the current direct-navigation defect before fixing it: `ControllerBase::upgradeView()` stamps `contentPage`
  as `view`/`create`/`edit`, while the initializer is `views.account` in `app-triggers.js`. Add focused regression
  evidence and the smallest production fix. Do not omit the required requests, accept static panel roots, or silently
  quarantine this defect to obtain a green Tier A.
- Assert the current dispatch/binding attributes as contract, not styling. This fix does not choose the future
  attachment mechanism pending #2116 or implement the later transport rewrite.
- Submit no account forms and perform no user-initiated business mutation. Login, session activity, view counters and
  audit side effects are expected; reset isolates them. Do not assert zero database writes.

Acceptance: all six cases pass with required actions and content, Tier A spec execution is under five minutes, and
suppressing an initializer or breaking the file/parent action fails the appropriate cases with the route named. Record
red-green evidence for the baseline fix and relevant regression checks.

## U3-E - Safe evidence and every-run results (#2081)

- Persist a lightweight sanitized result summary and JUnit on every run, including passing observation runs:
  run/commit/environment identity, selected tier/specs, attempted/completed/skipped counts, first-attempt outcomes,
  retry counts, durations and failure classification. Setup failure, cancellation and partial selection cannot look like
  a complete successful Tier A run.
- Preserve sanitized diagnostics for failing attempts, even if an explicitly enabled diagnostic retry passes: masked
  screenshot, sanitized console messages and normalized failed route/URL/status. Exclude sensitive query values and
  reporter/exception text as needed. Correlate JavaScript source/page information when no action route is available.
- Use an explicit upload allowlist, not an entire output directory. Never persist/upload request/response bodies, HAR,
  CI traces/video, authentication state, cookies, configuration snapshots, keys or temporary fixture dumps. CI forces
  capture off regardless of a local trace flag. Protect sensitive DOM regions and disable automatic DOM/error-context
  attachments unless proven sanitized.
- A local-only trace requires an explicit flag against the synthetic isolated instance and is never uploaded. Disable
  telemetry. Test the complete reporter/upload path with synthetic secret canaries in logs, URLs, DOM and error output;
  verify redaction and exclusion, including on failure.

Acceptance: all runs retain useful non-sensitive summaries; failing attempts retain readable sanitized evidence; no
forbidden files or canaries reach CI output/artifacts; passing first-attempt runs produce no failure diagnostics.

## U7 - Early CI observation (#2084)

Add an independent `ui` check for the complete Tier A once #2079 and #2081 are done. It does not wait for #2080, #2082
or #2083, nor require the integration job's database container.

- `TESTS_UI_ENABLED == 'true'` controls execution only. Enable it for observation, before qualification; do not wait 30
  days to start collecting runs. Unset disables the job and must never be counted as a qualified result.
- Run on relevant PRs, master updates, manual dispatch and a daily master schedule. Ensure the scheduled path actually
  selects master and does not inadvertently add daily work to unrelated jobs. Use a stable, unique check name.
- Before observation, verify the UI check is not already required through an aggregate workflow/ruleset. Tier A failures
  still fail the UI check (`continue-on-error: false`) and produce annotations/evidence, but merge-required promotion is
  a separate maintainer operation in U8b.
- Use PHP 8.3, an independent disposable MariaDB 10.3 service, both locked Node installations and the application asset
  build. Let normal `sp:install` create its unique database/schema; omit that database from service `MYSQL_DATABASE` and
  do not import the schema first.
- Default to zero retries. Any explicit diagnostic retry must preserve first-attempt evidence and use `failOnFlakyTests`
  for Tier A so retry-success cannot turn a regression green. Apply setup/job deadlines in addition to the per-page cap
  and record actual spec/setup durations.
- Always publish the safe summary/JUnit; publish only allowlisted failing-attempt diagnostics when applicable. Add Tier
  B later as an independently reported annotation-only selection. An optional installer selection runs on its own
  instance and has a separately visible result; neither delays the initial Tier A observation job.

Acceptance: the execution switch works; scheduled master runs collect real results before gating; a deliberately broken
or missing action fails Tier A with safe evidence; optional tiers are not prerequisites and cannot hide a Tier A
failure. This issue does not enable required merge checks.

## U8a/U8b - Runbook, qualification and promotion (#2085, #2086)

The full runbook at `docs/agents/ui-smoke-suite.md` extends U1's starter instructions. Cover prerequisites/approvals,
process/network/path/database ownership, clean install/reset/cleanup, fixture generation, contract extension and fault
injection, SID rotation versus session loss, safe artifacts, tier selection, Known Failure admission if implemented, and
current boot/transport migration boundaries.

- Collect at least 30 consecutive days with at least one complete master Tier A run per day and at least 30 complete
  Tier A runs. Record the actual run/spec/attempt counts, first-pass and retry outcomes, environment/commit, runtime
  distribution, every failure's cause and resolution, and any explicitly approved deterministic Known Failures.
- Skips, cancellations, setup failures, missing daily results or partial spec selections do not satisfy the daily
  complete-run requirement. Material contract/harness changes restart qualification for the affected tier. No
  unexplained first-attempt failures or unresolved flakes are permitted at promotion; a passing retry is not evidence of
  stability.
- The implementation must produce the record; this plan is not a fabricated stability record. #2086 cannot close before
  the real 30-day interval has elapsed.
- After qualification, a maintainer verifies/configures branch protection or a ruleset requiring the stable Tier A check
  for applicable PR targets. Verify a failing check actually blocks an ordinary merge; document bypass scope and
  disabled/skipped behavior. Do not claim that `TESTS_UI_ENABLED` or `continue-on-error: false` alone establishes a
  merge gate, and do not silently disable a required check via the execution switch.
- Tier B needs its own complete 30-day record after it starts running. Promote only on its own evidence, or leave it
  annotation-only with the reason recorded; unfinished Tier B/installer/Known Failure implementation must not delay Tier
  A qualification.

Acceptance: a contributor can reproduce a clean run and interpret each failure; real summaries support the qualification
criteria; a maintainer-required check blocks a deliberately failing PR after promotion. Repository variable/protection
mutations require separate execution-time approval; updating these specs is not that operation.

## U3-K - Optional Known Failure registry (#2080)

- Starts empty; do not build or populate it as a prerequisite for core detection, safe artifacts or CI observation. Key
  entries by `(spec, route, kind)` with a reason, issue link and explicit maintainer admission. New admissions are
  exceptional reviewed changes, not a claim that a literally empty list can only shrink.
- Suppress only that deterministic failure, never all failures in the spec. No flaky, setup/isolation or session-loss
  exceptions. An entry no longer observed fails with removal instructions only when its corresponding spec/selection
  actually ran; filtered/skipped specs do not imply a fix.
- Keep first-attempt/known-failure counts visible. Approval does not waive required page initialization or the #2079
  baseline fix.

Acceptance: empty registry passes; exact known failures are annotated; unrelated failures still fail; fixed entries are
rejected; filtered selections and admission metadata are tested.

## U5 - Tier B indexes (#2082)

Navigate in-shell to `accessManager`, `itemManager`, `securityManager`, `configManager`, `notification`,
`userSettingsManager` and `plugin`. Assert each index's own root/content/dispatch contract, completed navigation action
and any declared automatic actions using U3's detector. No dialogs or business mutations.

Run as a separate report/selection: failing specs remain visibly failed with safe diagnostics and counts, but are
annotation-only for merge policy until independently promoted. Do not implement annotation-only by ignoring the entire
UI command's exit status or marking failures as passes. Start its daily observation record when available; this work
does not block U7.

Acceptance: all seven load; breaking one index fails and identifies that case while preserving Tier A's exit policy; a
missing initializer cannot pass via stale shell content.

## U6 - Isolated installation workflow exception (#2083)

The wizard is an explicitly stateful exception, not a claim that installation is read-only. It is optional for the early
Tier A job and runs last when a full selection includes it, using explicit orchestration rather than filename order
alone.

- Give it a separate application instance, configuration/cache/temp/session namespace and unique nonexistent database
  name. Do not run `sp:install` or precreate/load the schema for the wizard's database; the browser installer owns
  creation. Use an explicitly tested hosting-mode alternative only if deliberately selected.
- Drive `#frmInstall`, follow current installer/master-password rules, then log in using the newly created synthetic
  credentials and assert the shell/automatic actions.
- Use `retries: 0`; recreate its complete instance/database before a manual rerun or any future explicitly supported
  retry. Never retry against an already installed instance.
- Compare the seeded instance immediately before/after this wizard selection, accounting for ordinary seeded-test
  counters before the comparison. Prove configuration/cache/session isolation as well as absence of seeded database
  mutation. Cleanup touches only wizard-owned resources.

Acceptance: first install and clean rerun both succeed; successful login proves installation; the seeded instance
remains unchanged and usable after the wizard; the wizard cannot delay initial Tier A observation.

## Migration and coverage boundaries

- Capture today's real routes/boot request first. #2096 removes `bootstrap/getEnvironment`; #2097/#2098 split transport.
  Each change must update readiness probes, expected-action/response predicates and both navigation-mode contracts in
  the same change, with browser evidence that genuinely missing actions still fail. Do not preserve an obsolete route
  matcher as a fallback or require a removed boot request.
- #2096 owns its additional logout/install browser compatibility checks outside the initial Tier A. Use a disposable
  authenticated session for logout, and reuse #2083's isolated installer check when available or provide an isolated
  check within #2096. These migration requirements do not delay initial Tier A observation or add logout to its scope.
- Follow the latest #2089 decisions: clean URLs are independent, and the future binding mechanism is pending #2116. Do
  not hard-code a future envelope binder choice into this suite. Correct ADR-0011's stale details through its existing
  #2112 track, not by treating them as shipped behavior.
- Playwright is preferred for convenient network/event instrumentation, not because Panther cannot run JavaScript or
  inspect console/overlay state. Node already exists in the application asset build; the new dependency is the isolated
  browser-test package/tooling.
- Disabled update/notice checks remain explicitly uncovered. The missing `status/checkRelease` controller is a separate
  defect, not a Known Failure or a regression this configured suite claims to detect.
- No screenshots-as-visual-tests, browser workflow expansion or substitution for server-side
  authorization/escaping/cryptographic compatibility tests. Authentication/security-sensitive production changes still
  require the project's maintainer review before merge.
