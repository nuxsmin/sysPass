# 0009 - Browser smoke suite for page-load actions

Status: Accepted (2026-11-01); amended by ADR-0011 (2026-11-02) and the quality-review plan revision (2026-11-04). The suite and CI job remain unimplemented and unqualified.
Related: ADR-0004 (#2041), ADR-0011 (#2089), suite #2075 and #2076-#2086, transport/boot #2096-#2098, attachment decision #2116, tracking #1976.

## Context

ADR-0004 left browser end-to-end testing out because in-process HTTP Integration Tests cover routing, controller, use case and view. Those tests remain the right safety net for server-rendered content, but do not observe subsequent JavaScript initialization, request dispatch or fragment insertion.

The current browser boot calls `bootstrap/getEnvironment` before binding views. The shell automatically loads `account/index` and `items/notifications`; account pages load files and parent-account items. Missing requests, failed responses or initialization errors can leave an empty area, static panel, stuck overlay or swallowed exception despite a valid initial HTML response.

The motivating defect is the shell's `status/checkRelease` request with no matching controller. PHP-level response assertions alone do not prove the browser dispatched the right action. This particular request is disabled in the deterministic suite, so the suite does not claim to detect that defect under its configured environment.

Page-load Actions are Web actions used by the browser, currently under `index.php?r=<controller>/<action>`, returning HTML or an Action Response. They are not the token-authenticated `api.php` JSON-RPC API. The vocabulary is defined in `CONTEXT.md`.

## Decision

### Scope and tiers

Detect missing or broken critical Page-load Actions and rendering/initialization failures. Assert specific DOM roots, meaningful action-produced content and dispatch/binding attributes, never layout, geometry or visual snapshots. The suite does not replace authorization, escaping or cryptographic compatibility tests.

Page-load specs perform no user-initiated business writes: no save/delete/copy/export/upload/dialog workflows. Login, session activity, account view counters and audit writes are expected. The installer is a separate, explicitly stateful exception.

- **Tier A:** anonymous login, authenticated shell, and account view/create/edit in both in-shell and direct full-page navigation. The shell requires completed account-list and notification actions; view/edit require file-list content and create/edit require eligible parent-account options. Every page/mode must issue and complete its required actions and show the expected content. Static headings, placeholder options and a non-empty shell are insufficient.
- **Tier B:** in-shell navigation to `accessManager`, `itemManager`, `securityManager`, `configManager`, `notification`, `userSettingsManager` and `plugin`. Assert each index's own positive contract and automatic actions. Report failures independently as annotation-only until this tier qualifies; do not hide a Tier A failure or falsely mark failed cases as passes.
- **Installer:** an optional separate selection on a completely separate instance/configuration/cache/temp/session namespace and unique nonexistent database. Run last when selected, with zero retries and complete recreation before rerun. It does not block initial Tier A observation.

Tier A is the browser prerequisite for ADR-0011's transport and boot changes only. HTTP Integration Tests, not browser availability, gate template migrations. Execution and required-check promotion are separate; no tier is currently claimed to gate merges.

**Baseline defect owned by #2079:** `ControllerBase::upgradeView()` stamps direct-account `contentPage` as `view`/`create`/`edit`, while `app-triggers.js` registers `views.account`. Reproduce and minimally fix the missing initialization before declaring Tier A green. Do not remove required-action assertions or silently quarantine this defect.

### Tooling and sessions

Use Playwright/TypeScript in `tests/ui/` with its own package and lockfile, Chromium, one worker, browser locale `en-US`, application locale `en_US`, and stable User-Agent/client address. CSRF depends on User-Agent and client address. Default to zero retries locally and in CI; an explicit diagnostic retry must preserve first-attempt outcomes/evidence and use `failOnFlakyTests` for Tier A. Type-check the harness and forbid focused tests in CI.

Build application assets with root `npm ci` and `bash scripts/build-assets.sh`, then install the separate UI lockfile and pinned Chromium. No global packages or unpinned `npx` downloads. Node already exists in the asset toolchain; this adds a separate browser-test package, not the first Node dependency.

Anonymous/login tests never inherit authenticated state. Authenticate once through the UI for the seeded suite; carry live cookie/storage changes between sequential authenticated contexts rather than repeatedly restoring an immutable snapshot. Verify reuse across legitimate 120-second SID regeneration (`SessionLifecycleHandler`, `Init::checkSessionLifeTime()`), which deletes the previous ID. Never silently re-login after actual session loss. Authenticated tests must not revisit the session-destroying login route.

### Isolation and installation

The initial smoke job uses PHP 8.3 and MariaDB 10.3. Existing PHP checks cover the supported 8.3-8.5 range (`composer.json`: `>=8.3 <8.6`); a browser/PHP matrix is not required.

Provide `scripts/ui-smoke.sh` as shared repository tooling, its allowed-command entry and starter prerequisite/run instructions. `.ai-cli/` remains AI-agent tooling only. Define PHP/server/database/Node/browser execution locations, mounts, ports and lifetimes. Respect existing execution-time CLI/Docker approval rules; this decision does not grant permission to build images or create resources.

Generate runner-owned configuration/cache/temp/session/output paths and verify the effective paths inside PHP before mutation. Explicit forwarding and mounts are needed: the current PHP wrapper forwards neither `CONFIG_PATH` nor installer variables and mounts only the repository. Reject developer paths and aliases/symlinks into them, non-owned database names and arbitrary external origins. Never reuse a developer server. Cleanup after success/failure/interruption removes only run-owned resources.

Separate application caches, including `Path::CACHE/config.cache`, per instance. `CACHE_PATH` only relocates DI compilation; it does not override application cache bindings. Use verified path bindings or isolated mounts/workspaces rather than assumed environment support. The installer and seeded instances must not share these caches.

Normal `sp:install` owns creation of its unique nonexistent database and schema. Do not precreate that database through service `MYSQL_DATABASE` or preload `schemas/dbstructure.sql`. An explicitly selected hosting-mode alternative uses a pre-created empty database and has its own test. The browser wizard likewise owns database/schema creation; do not CLI-install its instance first.

Own the fixture under `tests/ui/fixtures/`, reusing logical examples rather than unknown shared encrypted state. Generate matching login/master-password metadata, vault/account ciphertext and configuration using current cryptographic abstractions; document generation/reset and verify consistency, not merely shell login. Apply data after installation without replacing the schema. Provide stable IDs and meaningful synthetic file/parent content; set `filesEnabled=1` in application configuration. Keep shared datasets and `tests/SP/` untouched.

Commit clearly named synthetic `E2E_*` credentials for reproduction. Never use real data. Live authentication-state files are temporary and excluded from artifacts. Disable debug, update/notice checks and their external dependencies; those checks remain explicitly uncovered, and `status/checkRelease` stays a separate defect, not a Known Failure.

### Failure criteria and readiness

Attach listeners before navigation: context `weberror` or per-page `pageerror`, console errors, request start/finish/failure and response events. Track actions through response-body inspection and drain pending diagnostic work before passing.

A load fails on uncaught errors/rejections, console errors, unexpected request failures, action HTTP >=400, parsed JSON `ERROR`, malformed expected envelopes, missing actions/content/binding attributes, critical markup or a stuck overlay. Include client error/toast markup and server `.errors.round .msg-critical` without requiring `#content` on login/install pages. Warnings are annotations, not failures; plural `errors` containers can contain warnings.

Detect JSON `status: 10`, unexpected login redirects/pages and logout-script responses. Empty HTTP 200 bodies and missing authenticated fragments also fail, but classify these as invalid responses unless session loss is established. No silent re-authentication. Attribute action failures to routes, or retain source/page information for global JS errors without inventing a route.

Readiness requires the positive per-page/mode contract, completed and inspected expected actions, specific content, hidden overlay, drained diagnostics and 300 ms without an in-flight Page-load Action. One 15 s deadline bounds readiness/diagnostics. Report missing/pending actions and unmet DOM/overlay conditions, including when an action was never sent. Scope listeners and teardown to avoid cross-test contamination; ignore only proven expected navigation/teardown cancellations.

Do not use global `networkidle`: momentary idleness does not establish initialization, notifications are periodic, and the default AJAX timeout is zero. Automate fault-injection tests for every failure class, including suppressed dispatch, invalid responses, session variants, warnings, hanging actions and late body-inspection failures. Real smoke specs keep core actions real.

### Known Failure admission

Start with an empty registry; #2080 is optional and does not block early CI. Entries match exactly `(spec, route, kind)` with a reason, issue link and explicit maintainer admission. Only narrowly deterministic failures qualify; never flakes, setup/isolation or session-loss failures. Other failures in the same spec still fail.

When the corresponding spec executes without the known failure, the stale entry fails with removal instructions. Unselected/skipped specs do not imply a fix. New admissions are exceptional reviewed changes, not a claim that an initially empty list can literally only shrink. The #2079 baseline must be fixed, not waived.

### Evidence

Persist sanitized summary/JUnit on every run: run/commit/environment, selection/completion/skips, first attempts, retries, durations and failure classes. Keep summaries through qualification; incomplete/setup-failed runs cannot appear as complete success. Preserve failing-attempt diagnostics even if a diagnostic retry succeeds.

Upload only an explicit allowlist of sanitized logs/normalized route-URL-status, masked screenshots and safe reports. Scrub query values, console/exception/JUnit text and sensitive DOM regions. Disable automatic DOM/error-context attachments unless proven sanitized. Synthetic-canary tests verify the complete reporter/output/upload path.

Never persist/upload CI request/response bodies, HAR, traces/video, authentication state, cookies, configuration snapshots, keys or temporary fixture dumps. CI forces forbidden capture off regardless of local flags; telemetry is off. Local traces require explicit opt-in against synthetic isolated data and are never uploaded.

### CI observation and promotion

Add an independent, stably named `ui` check in #2084 once #2079 and #2081 are complete. It owns its disposable database service and installer provisioning, not the integration job's container or schema-load recipe. Known Failure machinery, Tier B and the wizard are not blockers.

`TESTS_UI_ENABLED == 'true'` controls execution only: enable it at the start of observation. Run on relevant PRs, master updates, manual dispatch and daily master scheduling without accidentally scheduling unrelated jobs. Verify applicable rules do not already require the observation check through an aggregate workflow. Tier A failures fail the UI check with `continue-on-error: false`; merge-required promotion is separate.

In #2086, qualify at least 30 consecutive days, with at least one complete master Tier A run per day and at least 30 complete runs. Skips, cancellations, setup failures, missing daily results and partial selections do not qualify. Retain first-attempt/retry counts, durations, every failure's cause/resolution and environment/commit identity. No unexplained first-attempt failures or unresolved flakes are allowed; material contract/harness changes restart the affected tier's window. Tier A spec execution has a five-minute budget; setup/job deadlines and actual durations are recorded separately.

Only after real qualification does a maintainer configure/verify branch protection or a ruleset requiring the stable check, prove a failing ordinary PR cannot merge, and document bypass/disabled/skipped behavior. Neither the execution switch nor `continue-on-error: false` alone establishes a merge gate. Tier B needs its own complete 30-day history or stays annotation-only; unfinished optional work does not delay Tier A. The suite does not auto-file issues; triage follows existing labels.

### Boot and transport evolution

The current `bootstrap/getEnvironment` request is the baseline. #2096 removes it; #2097/#2098 split page/fragment routes. Update probes, expected actions, response predicates and both navigation-mode contracts in the same changes, retaining negative proof that missing initialization fails. Do not retain an obsolete matcher or assert both old and future contracts as a compatibility scheme.

#2096 owns additional logout/install browser compatibility checks for its boot change, outside the initial Tier A. Logout uses a disposable session; installation reuses #2083's separate instance when available or an isolated check owned by #2096. These checks do not delay initial Tier A observation.

Follow the latest #2089 decisions: clean URLs are independent, the future attachment choice is pending #2116, and HTTP Integration Tests gate template migrations. ADR-0011's stale details are corrected through #2112, not treated as shipped behavior.

## Considered options

- **Symfony Panther or BrowserKit:** BrowserKit does not execute browser JavaScript; Panther does and can inspect console/overlay state. Playwright is preferred for convenient network/event instrumentation and diagnostic APIs, not because PHP browser tooling lacks JavaScript capability.
- **In-process HTTP Integration Tests only:** retained for broad rendering/security contracts, but insufficient to prove browser dispatch and initialization.
- **Full business workflows or visual assertions:** deferred because they add different state, runtime and maintenance concerns that obscure page-load failures.
- **Global network idle:** rejected in favor of positive action contracts and bounded readiness.
- **CI HAR/traces or automatic issue creation:** rejected for secret exposure and unnecessary triage/deduplication complexity.
- **External update/notice checks:** disabled for determinism and explicitly outside this configured coverage.

## Consequences

- Adds a separate browser-test package and CI check alongside existing PHP checks and Node asset tooling; it does not join the Composer/PHPUnit pipeline.
- ADR-0004's no-browser-suite decision is amended only for critical page-load observability, not general workflows or layout.
- Isolation and trustworthy positive contracts precede rollout; safe early Tier A observation precedes optional breadth and required-check promotion.

## Revision history

- **2026-11-01:** original acceptance of the browser page-load smoke layer.
- **2026-11-02:** ADR-0011 limited its prerequisite role to transport/boot changes and identified the forthcoming boot-contract change.
- **2026-11-04:** quality review revised provisioning/isolation, positive contracts, session lifecycle, diagnostics, artifact safety, retry policy, early observation and evidence-based promotion; corrected the Panther rationale and kept implementation/qualification explicitly pending.

## References

- ADR-0004 (test strategy), ADR-0002 (error assertions), ADR-0006 (independent clean URLs), ADR-0011 (SSR).
- `ControllerBase::upgradeView()`, `public/js/app-triggers.js`, `public/js/app-actions.js`, `public/js/app-requests.js`, `ControllerTrait::sessionLogout()`, `GetEnvironmentController`, `Login/IndexController`, `SessionLifecycleHandler`, `lib/SP/Core/Crypt/Session.php`.
- `composer.json`, `scripts/build-assets.sh`, `.ai-cli/bin/php`, `lib/Base.php`, `CoreDefinitions`, `InstallCommand`, `MysqlSetup`, `.github/workflows/feature.yaml`, shared fixtures under `tests/res/`.
- Implementation plan: `.kilo/plans/ui-smoke-suite.md`; authoritative issues: #2075 and its children.
