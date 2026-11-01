# 0004 - Test strategy and CI gating

Status: Accepted (2026-10-31); amended by ADR-0008 (2026-11-01)
Issue: #2041 (plan P11-4, decisions D4 and P10-6, tracking #1976)

## Context

The hexagonal migration moved business behaviour into use cases, made the modules driving adapters and turned `lib/` into the core. That split only holds if each layer is tested at the boundary where its behaviour is observable: use cases without Infrastructure, repositories against real SQL, and web areas through the full bootstrap and rendering path.

The pre-migration suite mixed these levels. Domain tests imported Infrastructure, repository tests mocked `DatabaseInterface` and asserted generated SQL without ever executing it, and the web controller characterisation tests existed but were excluded from CI. Plan decisions P10-1, P10-2, P10-3, P10-4, P10-5 and P10-6, plus D4, established the current strategy. This ADR records it as the rule for new work.

## Decision

### The test pyramid

| Level | What it proves | Location and base | Group | Runs against |
|---|---|---|---|---|
| **Unit** | Domain rules, use case orchestration, DTO/contract shape, static architecture rules | `tests/SP/Domain`, `tests/SP/Core`, `tests/SP/Infrastructure` (mocked SQL), `tests/SP/Mvc`, `tests/SP/Util`, `tests/SP/Architecture`; plain `TestCase` or `SP\Tests\UnitaryTestCase` | `unitary` | No database, no module bootstrap |
| **DB-backed Infrastructure** | Repository queries execute and return correct rows on the real engine | `tests/SP/Infrastructure/*/Repositories/*DbTest.php`; base `SP\Tests\DatabaseRepositoryTestCase` (`tests/SP/DatabaseRepositoryTestCase.php:40`) | `integration` | Real MariaDB with fixtures |
| **HTTP-level web integration** | A request reaches the right action and produces the expected response body through the module bootstrap | `tests/SP/Modules/Web/Controllers/<Area>/`; base `SP\Tests\IntegrationTestCase` (`tests/SP/IntegrationTestCase.php:84`) | `integration` | In-process web bootstrap with stubbed container dependencies |
| **API and CLI adapter integration** | JSON-RPC envelopes and console commands keep their observable contract | `tests/SP/Modules/Api` and `tests/SP/Modules/Cli`; base `ApiTestCase` / `CliTestCase` | `integration` | Module bootstrap with stubbed dependencies where the test does not need a database |

The pyramid is deliberately narrow at the top. There is no browser or network end-to-end suite: the web integration tests build a container, dispatch an in-process `Klein\Request` through `Bootstrap::run()` and assert the rendered body, which covers the routing, controller, use case and view path without a second process.

### Unit tests use fakes, not Infrastructure

- A use case is instantiated directly with in-memory port fakes from `tests/SP/Stubs/` (`InMemoryTagRepository`, `InMemoryEventEmitter`, and so on). It does not use the container, a database or a module bootstrap.
- A Domain test must not import `SP\Infrastructure`. The architecture metrics script reports this as M14 and the target is zero (`scripts/architecture-metrics.sh:118`).
- Mocked repository tests that only assert generated SQL stay `unitary`. They are not a substitute for the DB-backed tests below.
- `#[Test]` and `#[Group('unitary')]` are mandatory. The metrics script reports untagged files as M16; the target is zero.

### Infrastructure tests that matter run against real SQL

- Repository queries with joins, `OR` chains, `IN` lists, unions, repeated placeholders or unqualified columns are covered by `*DbTest.php` classes extending `DatabaseRepositoryTestCase`.
- The base class loads the fixtures, builds the production `Database` adapter over the test connection and gives each test a fresh fixture state. Assertions cover returned models, counts, ordering and persistence side effects, not just “no exception”.
- These tests carry `#[Group('integration')]` and run in the CI integration job against MariaDB 10.3, the version used by CI and by the migration baseline.
- A defect found by a DB-backed test is fixed in the same change as its regression test.

### Web characterisation tests come before migration

- Every migrated web area has an integration test class under `tests/SP/Modules/Web/Controllers/<Area>/`, with one test per controller action.
- The class is written before the controller is migrated and must be green on the current code. It then acts as the regression net for the migration.
- `IntegrationTestCase::buildContainer()` merges the real module definitions with stubbed dependencies (config, database, ACL, mail, theme) and `runApp()` executes the request. `#[BodyChecker]` attributes assert the rendered response.
- The test names the user-visible behaviour, not the implementation. It must not depend on the internal call sequence of a controller.

### Test conventions

- One style everywhere: the `#[Test]` attribute on every test method and one `#[Group]` per class (`unitary` or `integration`).
- Test data comes from `tests/SP/Generators/` and fixtures under `tests/res/`; ad-hoc arrays are acceptable only for small value objects.
- A test asserts the contract at its level: unit tests assert exception types, stable codes and raw message keys; integration tests assert the rendered or serialised text and the exposed code (ADR-0002).
- Architecture rules are tests, not review conventions: `LayerDependencyTest` is `unitary` (`tests/SP/Architecture/LayerDependencyTest.php:18`), and container wiring is covered by `ContainerWiringTest` in the integration group (`tests/SP/Core/Definitions/ContainerWiringTest.php:28`).

### CI gating

`.github/workflows/feature.yaml` has two jobs:

1. **Unitary** runs `phpunit -c tests/phpunit.xml --group unitary --testsuite core` and then `php scripts/coverage-gate.php --gate` (`feature.yaml:66`, `feature.yaml:72`). The coverage report is uploaded as an artifact.
2. **Integration** depends on the unitary job, starts a MariaDB 10.3 service, loads `schemas/dbstructure.sql`, and runs the modules integration group followed by the full core suite (`feature.yaml:164`, `feature.yaml:167`). It is enabled by the repository variable `TESTS_INTEGRATION_ENABLED=true` (D4); when the variable is absent the job is skipped. Once enabled it is not an opt-in per pull request.

The workflow runs on pushes to `master`, pull requests targeting `master` or `feat/*`, and manual dispatch. A change is not considered verified until both jobs have run: the unitary job proves the core in isolation, and the integration job proves the SQL and adapter boundaries that unit tests intentionally fake.

### Coverage gates

- `scripts/coverage-gate.php` reads the Clover report from the unit run, aggregates statement coverage per layer and enforces the minimums in `tests/coverage-thresholds.php`.
- Only `Domain\*\UseCases` (90%) and `Domain\*\Services` (95%) are gated. Other layers are reported for visibility and are not gated yet, because their coverage depends on suites that run in the integration job.
- Thresholds ratchet the recorded baseline with headroom; raising a threshold requires the suite to meet it, lowering one requires a documented reason.
- The web modules suite has its own separate report and is not gated by the unit budget.

## Considered options

- **Keep only unit tests.** Rejected: use cases with fakes cannot catch broken SQL, and the migration's riskiest changes were repository queries and namespace moves.
- **Keep repository tests mocked.** Rejected: asserting generated SQL does not prove that MariaDB accepts it or returns the expected rows. The DB-backed tests found real defects during P10-2.
- **Add a browser end-to-end suite.** Rejected for now: it adds a second runtime, browser binaries and flakiness for behaviour the in-process web bootstrap already covers. Revisit only for flows that depend on real HTTP semantics. ADR-0008 later accepts a browser suite limited to page-load observability; the rejection still stands for workflow and layout testing.
- **Make the integration job opt-in per pull request.** Rejected in D4: the web controller tests had been excluded from CI for years and silently regressed. The repository variable enables the job by default.
- **Gate every layer's coverage immediately.** Rejected: module coverage comes from the integration job, and a global gate would either fail on untested adapters or force low-value tests. Gate the core layers first and extend deliberately.
- **Use docblock annotations instead of attributes.** Rejected: the suite standardised on PHPUnit attributes (`#[Test]`, `#[Group]`) and the metrics script checks for them.

## Consequences

- New use cases, ports and DTOs are tested with fakes; new or changed repository queries need a `*DbTest.php` case; new or migrated web areas need an integration class per action.
- The integration job is part of normal CI and needs MariaDB 10.3. When it is unavailable the repository variable is the single switch, not a scattered set of test exclusions.
- Coverage can only ratchet upward for use cases and Domain services. The other layers remain visible in the report without becoming a merge blocker.
- Architecture and wiring violations fail the same CI run as the unit suite, so the rules in ADR-0001 are executable.
- A test that needs a database is explicitly `integration`; a `unitary` test that reaches for Infrastructure is a regression reported by M14.
- The suite stays split by observable boundary rather than by framework convenience: core behaviour, SQL behaviour and adapter behaviour each have one home.

## References

- ADR-0001: target architecture and dependency rules.
- ADR-0002: translation and error policy (assertions at each boundary).
- ADR-0003: use case and Command/Result convention.
- Plan: `.kilo/plans/hexagonal-migration.md` phase 10, decision D4 and section 9.
- Decisions: #2018 (P10-2), #2019 (P10-3), #2035 (P10-1), #2039 (P10-4), #2020 (P10-5), #1958 (P0-2, D4). This ADR: #2041. Tracking: #1976.
- Code and configuration: `tests/phpunit.xml:42`, `tests/phpunit.xml:52`, `tests/SP/DatabaseRepositoryTestCase.php:40`, `tests/SP/IntegrationTestCase.php:84`, `tests/coverage-thresholds.php`, `scripts/coverage-gate.php`, `.github/workflows/feature.yaml:66`, `.github/workflows/feature.yaml:84`, `.github/workflows/feature.yaml:164`.
- Metrics: `scripts/architecture-metrics.sh` (M14, M16).
