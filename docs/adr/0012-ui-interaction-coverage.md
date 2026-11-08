# 0012 - UI interaction coverage

Status: Proposed (2026-11-08). If adopted, this decision amends ADR-0004's exclusion of browser workflow tests without changing ADR-0009's Page-load UI Smoke Test scope.

## Context

HTTP Integration Tests and Page-load UI Smoke Tests cannot by themselves prove that a user control dispatches the right request and produces the intended visible and persisted outcome. Browser workflow tests add that evidence, but cost more to isolate, maintain and run than lower-layer tests. No finite suite can guarantee every combination of inputs and application states.

## Proposed decision

- Maintain an Interaction Inventory of every user-visible interaction, discovered from templates, client-side bindings and Routes and cross-checked by browsing with representative User Profiles. Group equivalent controls by interaction and context; record meaningful success, failure and denied-access scenarios according to risk.
- Derive expected outcomes from requirements and domain rules, not from current behavior alone. Record undefined outcomes and untested scenarios explicitly; do not count them as covered until resolved and tested. Link scenarios to their tests and update the inventory and relevant tests when interactions change.
- Assert each scenario at the lowest test layer that can prove its contract. Use UI Workflow Tests for browser interaction wiring and user-visible outcomes; use HTTP and DB-backed tests for broader response, authorization and persistence cases. Assert relevant HTTP response semantics and state effects as well as visible behavior. Hiding a control is not proof of server-side authorization.
- Run state-changing browser tests only on disposable test instances with controlled synthetic fixtures and isolated or reset data, never against production. Preserve the existing smoke suite's evidence and secret-handling safeguards.
- Check navigation controls for visibility, usability, active state and access on representative desktop and mobile viewports without requiring pixel-perfect styling.
- Introduce workflow coverage incrementally, starting as reporting checks and promoting it to merge-required status only after stability is demonstrated. Execution alone does not establish a required check.

## First milestone

Exercise navigation as an administrator on representative desktop and mobile viewports. In a separate Account creation workflow, assert the documented submitted Action response and visible outcome, verify persistence through an independent authorized read, and verify that a restricted user cannot create the Account and that no Account is created. Restricted-profile navigation remains a later coverage gap.

## Consequences

This supplements rather than replaces Unit Tests, HTTP Integration Tests, DB-backed Tests and UI Smoke Tests. Browser tests are reserved for contracts that need a real browser, while the inventory makes missing requirements and coverage visible. Workflow isolation, fixture reset, CI qualification criteria and specific expected outcomes must be designed and verified before implementation or promotion.
