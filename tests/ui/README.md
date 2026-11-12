# UI smoke harness

See `docs/agents/ui-smoke-suite.md` for prerequisites and the execution model.

```sh
npm ci
PLAYWRIGHT_BROWSERS_PATH="$PWD/.browsers" ./node_modules/.bin/playwright install chromium
npm run typecheck
npm run test:unit
npm run test:preflight
```

`npm run test:unit` runs the detector, redaction, reporter, retention and canary
tests. `scripts/ui-smoke.sh` runs the same suite before Playwright, so a
harness regression fails the smoke run before it creates any container.

The real suite is started only through `scripts/ui-smoke.sh`, which creates the isolated instance and supplies the run-owned origin.

## Coverage

Tier A currently covers:

| Surface | Source |
| --- | --- |
| Anonymous login page | `specs/login.spec.ts` |
| Authenticated shell and account list | `specs/shell.spec.ts` |
| Account view, create and edit, opened in-shell and by direct URL | `specs/shell.spec.ts`, contracts in `src/contracts.ts` |
| Session SID rotation and reuse | `specs/shell.spec.ts` |

Detailed readiness contracts, isolation rules and artifact handling are in `docs/agents/ui-smoke-suite.md`.

## Administrator navigation wiring (reporting only)

`SP_UI_TIER=WIRING` selects only `specs/adminNavigation.wiring.spec.ts`. It is a
separate reporting-only selection, never part of Tier A or Tier B, and it sets
no `completeTierA`, `completeTierB` or `completeTierInstall` flag.

```sh
TESTS_UI_ENABLED=true SP_UI_TIER=WIRING ./scripts/ui-smoke.sh
```

The runner provisions the same isolated, runner-owned seeded instance used by
the seeded tiers and retains evidence under `summaries/tier-wiring/`.

Coverage is limited to the five ACL-gated administrator menu entries declared by
`LayoutHelper::getMenu` and dispatched by the `.btn-menu` handler in
`public/js/app-triggers.js` through `getContent` in `public/js/app-actions.js`:

| Menu control | Route |
| --- | --- |
| `#btn-5002` | `accessManager/index` |
| `#btn-5001` | `itemManager/index` |
| `#btn-5003` | `securityManager/index` |
| `#btn-1101` | `plugin/index` |
| `#btn-config` | `configManager/index` |

Each case clicks the actual shell menu control, requires exactly one GET Action
request for the expected route, a successful HTTP response, and route-specific
stable markup rendered inside `#content`. The selection makes no layout,
visibility-only, active-state, viewport or translated-text assertions.

Excluded: Search and New Account are not ACL-gated administrator menu entries
and remain outside this selection. Restricted-profile navigation and denied
access are not asserted here; server-side authorization remains covered by
backend/HTTP tests and is an explicit later gap for browser navigation.

Verification on 2026-11-12: the targeted harness unit tests for this selection
passed; the full `npm --prefix tests/ui run test:unit` run reported 296 passes
and 5 failures because this worktree has no `tests/ui/node_modules` or
`tests/ui/.browsers`, so the Playwright CLI, pinned Chromium and the
version/screenshot canaries are unavailable. `npm --prefix tests/ui run
typecheck` was not run because `tsc` is not installed. The live browser wiring
run was not executed: it requires an approved isolated provisioning run
(`scripts/ui-smoke.sh`), which this task must not start.
