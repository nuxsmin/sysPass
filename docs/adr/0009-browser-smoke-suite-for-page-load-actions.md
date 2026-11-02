# 0009 - Browser smoke suite for page-load actions

Status: Accepted (2026-11-01)
Related: ADR-0004 (#2041), tracking #1976

## Context

ADR-0004 deliberately left browser end-to-end testing out, on the grounds that the in-process HTTP Integration Test covers routing, controller, use case and view. That holds for server-rendered HTML, but it cannot observe what the browser does after the response arrives. Every page boots `public/js/app-main.js`, which calls `index.php?r=bootstrap/getEnvironment` (`app/modules/web/Controllers/Bootstrap/GetEnvironmentController.php:95`) before binding any view behaviour. The authenticated shell then loads `account/index` (`public/js/app-triggers.js:269-270`) and `items/notifications` (`public/js/app-actions.js:1551-1559`), and account forms load `accountFile/list` and `items/accountsUser` (`public/js/app-actions.js:378-393,450-472`). When one of those requests fails or returns an error envelope, the user sees an empty content area, error markup, a stuck loading overlay or a silently swallowed JavaScript exception. None of that is visible to a test that dispatches a request in-process and asserts the body.

The suite shape in ADR-0004 also trusted that adding a second runtime was not worth the cost. The follow-up defect that motivated this ADR shows the cost of *not* having one: `status/checkRelease` is called by the shell (`public/js/app-actions.js:698`) but no matching controller exists, so the request returns 404 and the failure is reduced to a warning icon (`app-actions.js:726-730`). No PHP-level test could catch it, because the broken contract is between the rendered page and the request it makes.

Two terms need pinning down for this decision. The browser never calls `api.php`; that is a token-authenticated JSON-RPC endpoint (`app/modules/api/Bootstrap.php:88-163`) used only by external clients. The browser's "API" is a **page-load action**: a web action at `index.php?r=<controller>/<action>` that returns an HTML fragment or an `ActionResponse` envelope (`lib/SP/Domain/Common/Dtos/ActionResponse.php:98-105`). This decision is about page-load actions, not the JSON-RPC API.

## Decision

### Scope

The suite detects **loading and rendering failures caused by page-load actions**. It does not test layout: assertions are limited to presence and non-empty text of expected DOM roots, the loading overlay's state, console output and network outcomes. Geometry, computed styles and element positions are out of scope, so an unstyled but complete page passes.

The suite is **read-only**. It does not save, delete, copy passwords, export or upload; those are workflow tests for a later suite. Page loads only.

### Tooling

A Playwright (TypeScript) suite in `tests/ui/` with its own `package.json` and lockfile, Chromium only, `workers: 1`, `retries: 0` locally and `1` in CI, fixed `en_US` locale and a fixed User-Agent. The User-Agent must be stable because the CSRF key is `sha1(User-Agent + client address)` (`lib/SP/Core/Crypt/Csrf.php:83-86`). Login happens once through the UI and is reused as Playwright `storageState`.

The runner is invoked through `.ai-cli/bin/ui-smoke` and that wrapper is added to `.ai-cli/allowed-commands.txt`, so agent sessions run it the way they run `$PHP_BIN`. No global npm installs.

### Page tiers

- **Tier A (gating):** the pages that fire page-load actions — the logged-out login page, the main shell (which auto-loads `account/index`), account view, account create and account edit, each navigated both in-shell through `getContent` and by direct full-page URL. Account view/create/edit exercise different bootstrap branches: in-shell is the normal user path, direct URL is the full-page `upgradeView()` branch.
- **Tier B (non-gating until stable):** in-shell navigation to the seven server-rendered indexes — `accessManager`, `itemManager`, `securityManager`, `configManager`, `notification`, `userSettingsManager`, `plugin` — asserting each content root is present and non-empty with no error markup. No dialogs: they are user-triggered, not page loads.
- **Install wizard:** one separate spec, because it needs an uninstalled database. It runs last in the same CI job against a second empty database and does not share the seeded instance.

### Failure criteria

A page load fails when any of these occur:

1. An uncaught `pageerror` or unhandled rejection.
2. A `console.error` entry (the application funnels `log.error` there, `public/js/app-main.js:264-266`).
3. Any `index.php?r=*` response with HTTP status 400 or above.
4. HTTP 200 with an `ActionResponse` whose `status` is `ERROR`.
5. `#content .error.round` (`public/js/app-main.js:388-392`) or `.toast-error` present.
6. `#wrap-loading` still displayed after network quiet (`app/modules/web/themes/material-blue/js/app-theme.js:47-67`).
7. An expected DOM root for that page is missing or empty.

`WARNING` is annotated but does not fail. A response with `status: 10` (session expired, `app/modules/web/Mvc/Controller/ControllerTrait.php:61-70`) fails the spec as **session lost**; the suite does not silently re-authenticate, because that would hide the failure it exists to catch.

### Readiness

Readiness is defined per page as: the page's explicit `readySelector` is present, the loading overlay is hidden, and no `index.php?r=*` request has been in flight for 300 ms. A hard cap of 15 s applies; a timeout reports which request was still pending. `networkidle` is not used: the application polls notifications every 120 s (`public/js/app-triggers.js:263-267`) and its default AJAX `timeout` is `0` (`public/js/app-requests.js:37-52`), so "idle" is not a reliable state.

### Environment

The runner generates a fresh configuration directory and points `CONFIG_PATH` at it; it refuses to start if `CONFIG_PATH` resolves to `app/config`, so a local run cannot touch a real instance. It installs with `sp:install` (`app/modules/cli/Commands/InstallCommand.php:56-72`) against MariaDB 10.3 and PHP 8.3, the supported floor. CI temporarily runs PHP 8.2 until P4-4 (#2002).

The UI suite owns a dedicated seed fixture under `tests/ui/fixtures/`, derived from `tests/res/datasets/syspass.sql` but with known credentials — including a known admin master password, which the shared fixture does not provide. The shared fixture is not modified, so DB-backed integration tests keep their contract. E2E credentials are committed with an obvious test-only naming convention: env indirection buys no security for a throwaway database and makes local reproduction harder.

`debug` is off, and `CHECK_UPDATES`/`CHECK_NOTICES` are disabled so CI does not depend on the internet. The missing `status/checkRelease` controller is tracked as its own defect, not encoded into the suite.

### Known-failure ratchet

The suite starts with an empty allowlist. An entry is keyed by `(spec, route, failure kind)` with a reason and an issue link; no regex matching over messages. An allowlisted failure that stops occurring is itself a failure, so entries can only be removed. This keeps the suite green on the day a known defect is filed without letting the list rot.

### Evidence

On failure the suite uploads a screenshot, the browser console log and the failed request's URL and status. It does not persist request bodies, HAR files or Playwright traces from CI: this is a password manager, and login and account forms POST secrets. Trace capture is available behind an explicit local flag and is never uploaded.

### CI

`.github/workflows/feature.yaml` gains a third job, `ui`, gated by the repository variable `TESTS_UI_ENABLED` in the same way the integration job is gated by `TESTS_INTEGRATION_ENABLED`. It redeclares the MariaDB 10.3 service and schema load rather than depending on the integration job's container. Tier A has a budget of about five minutes. The job runs with `continue-on-error: false` and gates pull requests once the variable is enabled, after a 30-day stability window. Failures produce JUnit output and GitHub annotations; the suite does not file issues automatically — triage stays with the existing label workflow.

## Considered options

- **Reuse Symfony Panther/BrowserKit.** Rejected: it would keep one toolchain but cannot observe network responses, uncaught JavaScript errors or the loading overlay, which are the signals this suite exists to collect.
- **Extend the in-process HTTP Integration Test.** Rejected: it executes no JavaScript, so it cannot see a page that boots and then fails its first request. It remains the right level for server-rendered content.
- **Cover the full workflow in the browser.** Rejected for now: writes, dialogs and clipboard behaviour multiply runtime and flakiness, and the page-load failures are independent of them.
- **Use `networkidle`.** Rejected: the application polls on a 120 s interval and sets no AJAX timeout, so idleness is neither reached nor meaningful.
- **Persist HAR or traces from CI for richer debugging.** Rejected: both can contain decrypted vault data and POSTed passwords.
- **Auto-file GitHub issues on failure.** Rejected: it duplicates the triage label workflow and needs deduplication logic that is not worth building yet.
- **Assert layout as well, since the browser is already running.** Rejected: layout correctness is a different, high-maintenance problem; it would obscure the page-load signal this suite is built to produce.
- **Keep external update/notice checks enabled.** Rejected: they make CI depend on third-party availability and would bake a known 404 into the allowlist.

## Consequences

- A new CI job and a Node toolchain exist alongside the PHP toolchain. They are isolated in `tests/ui/` and intentionally do not participate in the Composer or PHPUnit pipeline.
- Tier A is the regression net for the browser boot path; tier B grows into it only after proving stable.
- The page-load action vocabulary and the distinction from the JSON-RPC API are recorded in `CONTEXT.md`, so future work does not conflate them.
- The `status/checkRelease` defect remains a normal code issue rather than a permanent known failure.
- ADR-0004's "no browser end-to-end suite" clause is amended: it still holds for workflow and layout testing, not for page-load observability.

## References

- ADR-0004: test strategy and CI gating.
- ADR-0002: translation and error policy (assertion levels).
- Code: `public/js/app-main.js:264-266,388-392,428-433`, `public/js/app-actions.js:378-393,450-472,698,1551-1559`, `public/js/app-triggers.js:263-270`, `public/js/app-requests.js:37-52,172-194`, `app/modules/web/themes/material-blue/js/app-theme.js:47-67`, `lib/SP/Domain/Common/Dtos/ActionResponse.php:98-105`, `lib/SP/Core/Crypt/Csrf.php:83-86`, `app/modules/web/Mvc/Controller/ControllerTrait.php:61-70`, `app/modules/web/Controllers/Bootstrap/GetEnvironmentController.php:95`.
- Environment: `build/docker-compose-demo.yml:15-16`, `.github/workflows/feature.yaml:97-105,154-162`, `tests/res/datasets/syspass.sql`, `tests/res/config/config.xml`.
- Plan: `.kilo/plans/ui-smoke-suite.md`.
