# 0012 - UI interaction coverage

Status: Accepted (2026-11-12), narrowed to wiring contracts; layout/usability excluded. This decision amends ADR-0004's exclusion of browser workflow tests for JS-to-backend wiring contract tests only, without changing ADR-0009's Page-load UI Smoke Test scope.

## Context

HTTP Integration Tests and Page-load UI Smoke Tests cannot by themselves prove that a user control dispatches the right request and produces the intended visible and persisted outcome. Browser workflow tests add that evidence, but cost more to isolate, maintain and run than lower-layer tests. No finite suite can guarantee every combination of inputs and application states.

## Proposed decision

- UI Workflow Tests verify wiring between JavaScript-driven controls and backend Actions only: each JS-bound control sends the expected Action request (route/action, method, payload shape; password fields encrypted, never plaintext), and the UI renders the backend's success or error response.
- Maintain an Interaction Inventory limited to JS-bound actions, discovered from client-side bindings and Routes. Record success and error scenarios per action; record undefined outcomes and untested scenarios explicitly and do not count them as covered until tested.
- Out of scope for UI Workflow Tests: layout, composition, visibility, usability, viewport/mobile checks, active-state checks and pixel/visual checks.
- Authorization and denied-access checks are enforced and tested at the HTTP/backend layer (HTTP Integration Tests and DB-backed Tests), not in the browser suite. Hiding a control is not proof of server-side authorization.
- Assert state changes through HTTP status and persisted state read back by an independent authorized read, not only visible text. Prefer network-level request assertions and stable hooks (existing Render Contract dispatch attributes) over translated or locale-dependent text.
- Run state-changing browser tests only on disposable, reset instances with synthetic fixtures, never against production. Preserve the existing smoke suite's evidence and secret-handling safeguards.
- Start as reporting checks. Promotion to required status requires ADR-0009-style qualification and is a separate maintainer action; execution alone does not establish a required check.

## First milestone

Wiring-contract tests for two flows:

- Account creation: the JS-bound submit sends the expected request, and the response is the Action JSON envelope from `app/modules/web/Controllers/Account/SaveCreateController.php` (`ActionResponse::ok` with `data` `itemId` and `nextAction`). Assert the envelope and ids, not translated message text. Verify persistence through an independent authorized read.
- Admin navigation wiring: each JS-bound navigation control dispatches the expected Action and the UI renders the backend's response.

## Follow-up sequence

Candidate actions are added incrementally, one group at a time, after each group has a documented expected response, an independent read for persistence, and a reporting-only first run. The final order is set by the Interaction Inventory (#2139).

1. Account edit and delete (state-changing, same pattern as creation).
2. Other high-risk JS-bound state changes in the Account and admin areas identified by the inventory.
3. Read-only JS-bound actions (for example view and search).

Excluded until a separate maintainer decision and security review under AGENTS.md: password copy, reveal, export, public links, and any action that depends on configuration (LDAP, mail, encryption, backup) that the disposable instance may not provide.

## Consequences

This supplements rather than replaces Unit Tests, HTTP Integration Tests, DB-backed Tests and UI Smoke Tests. Browser tests are reserved for JS-to-backend wiring contracts that need a real browser; layout, usability and authorization remain outside the browser suite. Workflow isolation, fixture reset, CI qualification criteria and specific expected outcomes must be designed and verified before implementation or promotion.
