<!-- Copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org -->

# UI smoke harness

See `docs/agents/ui-smoke-suite.md` for prerequisites and the execution model.
See `docs/agents/ui-workflow-tests.md` for the WIRING/WRITE CI observation,
qualification criteria, check names and durable run table.

```sh
cd tests/ui
npm ci
PLAYWRIGHT_BROWSERS_PATH="$PWD/.browsers" ./node_modules/.bin/playwright install chromium
npm run typecheck
npm run test:unit
npm run test:preflight
cd ../..
```

Run the shared `./scripts/ui-smoke.sh` examples below from the repository root.
AI sessions must follow `.ai-cli/README.md`; provisioning requires approval.
Historical verification paragraphs below record their stated dates, not the
current checkout's dependency availability or browser results.

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

The authoritative JS-to-backend wiring inventory is [docs/agents/ui-interaction-inventory.md](../../docs/agents/ui-interaction-inventory.md).

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

## Account write wiring (reporting only)

`SP_UI_TIER=WRITE` selects `specs/accountCreate.write.spec.ts` and
`specs/accountDelete.write.spec.ts`. It is a separate reporting-only selection,
never part of Tier A or Tier B, and it sets no `completeTierA`, `completeTierB`
or `completeTierInstall` flag.

```sh
TESTS_UI_ENABLED=true SP_UI_TIER=WRITE ./scripts/ui-smoke.sh
```

The runner provisions the same isolated, runner-owned seeded instance used by
the seeded tiers and retains evidence under `summaries/tier-write/`.

The create spec creates exactly one synthetic Account per run. It fills a unique
name/login derived from the run timestamp, selects the fixture client and
category, types a synthetic password and repeat, and submits through the real
`.form-action` pipeline rather than calling the Action URL directly. It
asserts:

- exactly one form-urlencoded POST Action for `account/saveCreate`, containing
  the entered name/login/client/category and `isAjax=1`, with both password
  fields present but not equal to the typed plaintext;
- the `ActionResponse::ok` envelope from
  `SaveCreateController::saveCreateAction()`: `status: "OK"`, an integer
  `data.itemId > 0`, and a non-empty `data.nextAction` equal to the account edit
  route rendered by the application. Translated text and `description` are not
  asserted;
- persistence through an independent authorized GET to
  `account/view/<itemId>` in the same authenticated session, checking the
  unique name and login. The toast is never treated as persistence proof.

The dispatch, envelope and persistence checks live in
`src/account-create-contract.mjs`; `src/account-create-contract.test.mjs`
injects omitted dispatch, false-success and missing-persistence faults through
`npm run test:unit`.

The run instance must be disposable. Fixture reset removes only the stable
fixture rows and does not delete arbitrary Accounts created by this or earlier
runs, so never point the runner at a developer or production database.

Coverage limits: this selection verifies the authorized Account-create
JS-to-backend wiring only. A restricted-user/denied write is not asserted in the
browser; server-side authorization and denied writes remain covered by
HTTP/DB-backed tests. Layout, visibility, active state, viewport, styling and
translated-text checks are out of scope.

### Account delete

`specs/accountDelete.write.spec.ts` creates its own uniquely named disposable
Account through the same create form, then deletes it through the search-row
control and the JavaScript confirmation dialog. It does not delete fixture rows
or Accounts created by earlier runs, so the run instance must be disposable.

The spec asserts:

- `#search-rows` exposes exactly one row action with
  `data-onclick="account/delete"`, `data-action-route="account/saveDelete"` and
  the created `data-item-id`;
- clicking the row action opens `#orrsDiag`, and `#orrsDiag #alert-text` is
  visible; the positive confirm button is `#orrsDiag #positive` and is matched
  by id, not translated text;
- the positive button sends exactly one form-urlencoded
  `POST /index.php` whose body route is `account/saveDelete/<id>` and whose `sk`
  is non-empty; no `account/delete/<id>` request is sent;
- the response is HTTP 200 with the JSON envelope `status: "OK"` and
  `data: null`; the create-response fields `itemId` and `nextAction` are not
  expected;
- the D7 `account/search` refresh runs after the delete regardless of the
  envelope; and
- an independent authenticated `GET /index.php?r=account/search&search=<name>`
  returns `status: "OK"` and its `data.html` contains neither the unique name
  nor `data-item-id="<id>"`.

The dispatch, envelope and absence checks live in
`src/account-delete-contract.mjs`; `src/account-delete-contract.test.mjs`
injects omitted dispatch, legacy and URL-only routing, false-success and
still-present rows through `npm run test:unit`. The delete route is asserted
from the POST form body; `ActionTracker` and `parseActionRoute` cannot observe
that route because it is not in the request URL.

Denied deletes are not asserted in the browser. The existing HTTP integration
coverage in
`tests/SP/Modules/Web/Controllers/Account/AccountTest.php`
(`unauthenticatedAccountMutationsAreRejected`,
`accountMutationWithoutActionIsRejected` and
`accountMutationWithoutVisibilityIsRejected`) remains the authorization
evidence.

Status: reporting-only, live browser run pending. The delete interaction is not
marked Covered in the inventory until a live run has executed.

## Inventory discovery (manual, working-tree only)

`SP_UI_TIER=DISCOVERY` selects only `specs/discovery.spec.ts`. It is a manual
inventory browsing mechanism, not a qualification run, not CI evidence, and it
sets no `completeTierA`, `completeTierB`, `completeTierInstall`,
`completeTierWiring` or `completeTierWrite` flag. It cannot run the installer.

```sh
TESTS_UI_ENABLED=true SP_UI_TIER=DISCOVERY ./scripts/ui-smoke.sh
```

The run provisions the same isolated, runner-owned seeded instance, logs in as
the synthetic administrator and the synthetic restricted user, and browses the
fixed page set in `src/discovery.mjs`. It records only page route/status and
structural control metadata: `data-onclick`, `data-onsubmit`,
`data-action-route`, `data-route`, `data-view`, `data-onchange`, element
`id`/`name` and form name. It does not record passwords, form values,
credentials, cookies, response bodies or account field values.

Output is retained under `summaries/inventory-discovery/<run-id>/` as
`inventory.json` plus `run-metadata.json`. `summaries/` is gitignored, so this is
working-tree evidence only and is never committed. Because it is not CI
evidence, the discovery selection skips the standard `run-summary.json` /
`junit.xml` / `upload-manifest.json` retention set; the runner still owns and
removes the isolated containers.
