# sysPass: Clean / Hexagonal Architecture Migration Plan

Branch: `obvious-author` (561 commits ahead of `master` @ `9d0e169d`)
Baseline measured on: `4b83c270` (clean working tree)
Goal: `lib/` holds the core logic (Domain + Application + Infrastructure adapters) with no knowledge of the interface layer; `app/modules/{web,api,cli}` is a set of driving adapters that talk to the core only through ports, use cases, DTOs and domain exceptions. Testing is layered: fast unit tests for the core, DB-backed tests for infrastructure adapters, HTTP-level tests for the interface layer.

---

## 0. How to iterate this plan

- Each task has an ID (`P0-1`, `P1-3`, ...), a scope, and acceptance criteria. Pick the first task whose dependencies are `[x]`.
- A task is done only when its acceptance criteria pass **and** the metric it targets (section 2) has moved in the right direction. Record the new number in the "Progress log" (section 9).
- Keep each task as a separate commit series. Mechanical namespace moves (P1-*, P3-*) go in their own commits so `git log` and `git blame` stay readable.
- Do not commit unless explicitly asked. Do not change database schema or user-visible behaviour in refactor tasks unless the task says so.
- Re-run the metric commands in section 2 after each task.
- Status legend: `[ ]` todo, `[~]` in progress, `[x]` done, `[-]` dropped (with reason).

### 0.1 GitHub tracking

Live status is tracked in GitHub issues on `nuxsmin/sysPass`. The map issue is [#1976](https://github.com/nuxsmin/sysPass/issues/1976), and the board is [Project #1](https://github.com/users/nuxsmin/projects/1). Each issue lists `Blocked by:` and `Blocks:` at the top of its body, and the full dependency order is in #1976. When an issue and this file disagree, the issue wins.

| Task | Issue | State |
|---|---|---|
| P0-1 to P0-7 | #1957 to #1963 | done |
| P1-1 | #1964 | done |
| P1-5 | #2013 | done (`4495bdb0`) |
| P1-2 | #1969 | done (`a2a557fa`) |
| P1-3 | #1972 | done (`ed2649e2`) |
| P1-4 | #1970 | done (`38101a7e`) |
| P1-6 | #2012 | done |
| P1-7 | #2014 | done |
| P1-8 | #2051 | todo (last; after all callers are removed) |
| P2-1 | #1971 | done (`333ae6c8`) |
| P2-2 | #2023 | done (security review pending) |
| P2-3 | #2029 | done |
| P2-4 | #2024 | done (`d596e85a`) |
| P3-1 | #1973 | done (`90efe568`) |
| P3-2 | #2030 | done |
| P3-3 | #2036 | done |
| P4-1 | #2025 | done |
| P4-2 | #1974 | done |
| P4-3 | #2031 | todo |
| P4-4 | #2015 | done |
| P5-1 | #2026 | done |
| P5-2 | #2032 | done |
| P5-3 | #2037 | done |
| P6-W1 | #1975 | done |
| P6-W2 | #2042 | done |
| P6-W3 | #2043 | done |
| P6-W4 to P6-W7 | #2044 to #2048 | todo |
| P7-1 to P7-4 | #2016, #2027, #2047, #2049 | P7-1, P7-2 done; rest todo |
| P8-1, P8-2 | #2017, #2033 | todo |
| P9-1 to P9-5 | #2038, #2050, #2034, #2052, #2053 | P9-1, P9-2 done; rest todo |
| P10-1 to P10-5 | #2035, #2039, #2018, #2019, #2020 | all done |
| P11-1 to P11-5 | #2021, #2028, #2040, #2041, #2022 | todo |
| D1 | #1965 | decided: option (b) |
| D2 | #1966 | decided: option (a) |
| D3 | #2011 | decided: yes |
| D8 | #1967 | decided: per-controller behind a response adapter |
| D9 | #1968 | decided: explicit bindings per port |

---

## 1. Target architecture

### 1.1 Layers and allowed dependencies

| Layer (namespace / path) | Responsibility | May depend on | Must NOT depend on |
|---|---|---|---|
| **Domain** `SP\Domain\<Ctx>\` (`lib/SP/Domain`) | Models, value objects, enums, domain services, use cases, ports (interfaces), domain exceptions | Domain only, PHP stdlib | Infrastructure, Core, Mvc, Html, Util, Modules, `__()`/`logger()`/`processException()` globals |
| **Application / use cases** `SP\Domain\<Ctx>\UseCases\` (decision D2) | Orchestrates ports + domain for one user intent; takes Command DTO, returns Result DTO | Domain | Controllers, HTTP, Klein, session, DI container |
| **Ports** `SP\Domain\<Ctx>\Ports\` | Interfaces the core needs from the outside world (persistence, crypto, file, mail, session, clock) and interfaces the outside world calls (use case contracts) | Domain | Infrastructure types |
| **Infrastructure** `SP\Infrastructure\` (`lib/SP/Infrastructure`) | Driven adapters: repositories, DB, filesystem, LDAP, SMTP, crypto impl. Implements Domain ports | Domain (ports, models, exceptions), vendor libs | Modules, Mvc, Html |
| **Core** `SP\Core\` (`lib/SP/Core`) | Composition root and framework glue: bootstrap, DI definitions, `Application` (to be removed), session/context implementation | Domain, Infrastructure, vendor libs | Modules (except controller resolution by convention in bootstrap) |
| **Modules** `SP\Modules\{Web,Api,Cli}\` (`app/modules`) | Driving adapters: HTTP/JSON/CLI request mapping, response mapping, view/theme rendering, presentation helpers | Domain (ports, models, DTOs, use cases, domain exceptions), Core bootstrap contracts | Infrastructure (except composition files), Domain concrete services (except exceptions/enums) |
| **Mvc / Html** (`app/modules/web`) | Presentation: templates, DataGrid, icons, select items | Domain ports and module code | Domain concrete services, Infrastructure |

### 1.2 Hard rules (to be enforced by P0-3)

1. Domain imports only Domain. (Current: 186 files violate.)
2. Infrastructure imports only Domain + vendor. (Current: 2 Core imports.)
3. Modules import Domain ports/models/DTOs/use cases/exceptions. No Infrastructure outside bootstrap files. No concrete Domain services except exception types.
4. `lib/` never references `SP\Modules\` (exception: string-based controller resolution in `BootstrapBase`, documented).
5. No new `Application` injections anywhere (the service locator is being removed).
6. Domain code never produces user-facing translated text or calls `processException()`; it throws domain exceptions with stable codes/messages (see decision D1).

---

## 2. Baseline metrics (re-measure after each task)

Run from the worktree root. Keep the numbers in the progress log.

| ID | Metric | Command (abbreviated) | Baseline |
|---|---|---|---|
| M1 | Domain files importing any outer layer (Infrastructure, Core, Modules, Util) | `grep -rlE --include=*.php '^use SP\\(Infrastructure\|Core\|Modules\|Util)\\' lib/SP/Domain \| wc -l` | **186** |
| M2 | Domain files importing Infrastructure | `grep -rlE '^use SP\\Infrastructure\\' lib/SP/Domain \| wc -l` | **131** |
| M3 | Domain files importing `SP\Core` | `grep -rlE '^use SP\\Core\\' lib/SP/Domain \| wc -l` | **96** |
| M4 | Domain files importing `SP\Core\Application` (service locator) | `grep -rlE '^use SP\\Core\\Application;' lib/SP/Domain \| wc -l` | **74** |
| M5 | Domain files calling translation global `__u()` | `grep -rlE '\b__u\(' lib/SP/Domain \| wc -l` | **73** (346 strings) |
| M6 | Port interfaces importing Infrastructure types (mostly `QueryResult`) | `grep -rlE '^use SP\\Infrastructure\\' $(find lib/SP/Domain -path '*Ports*' -name '*.php') \| wc -l` | **69** of 143 |
| M7 | Module files importing Infrastructure | `grep -rlE '^use SP\\Infrastructure\\' app/modules \| wc -l` | **74** |
| M8 | Module `use` lines importing concrete Domain Services (non-port) | `grep -rhE '^use SP\\Domain\\[A-Za-z]+\\Services\\' app/modules \| wc -l` | **127** (vs 275 port imports) |
| M9 | Web controllers on the new `#[Action]` + `ActionResponse` contract | `grep -rl '#\[Action' app/modules/web/Controllers \| wc -l` | **75** / 261 |
| M10 | Web controllers still on legacy JSON (`returnJsonResponse` / `JsonMessage`) | `grep -rlE 'returnJsonResponse\|JsonMessage' app/modules/web/Controllers \| wc -l` | **84** (83 in untested areas) |
| M11 | API controllers on the new contract | `grep -rl '#\[Action' app/modules/api \| wc -l` | **0** / 46 |
| M12 | `lib/` → `SP\Modules` references | `grep -rl 'SP\\Modules' lib \| wc -l` | **3** |
| M13 | Core → Infrastructure references outside composition (`Core/Definitions`) | see section 3, finding 7 | **7** (+2 composition) |
| M14 | Domain test files importing Infrastructure | `grep -rlE '^use SP\\Infrastructure\\' tests/SP/Domain \| wc -l` | **55** |
| M15 | Web controller areas with no integration test directory | loop over `app/modules/web/Controllers/*/` | **27** / 46 |
| M16 | Test files with no `#[Group]` | `grep -rL '#\[Group' $(find tests/SP -name '*Test.php') \| wc -l` | **11** |

Baseline test execution is verified on PHP 8.2.12 + MariaDB 10.3. See section 9 for the environment, pass/fail counts, coverage and quarantined tests.

---

## 3. Findings (evidence summary)

1. **The Domain is not pure.** 186 Domain files import an outer layer. The top offenders are Infrastructure exceptions (`NoSuchItemException` 54 imports, `FileException` 31, `DuplicatedItemException` 23, `DatabaseException` 2) and the `QueryResult` container (70). These types cross into Ports, so the ports themselves are coupled to Infrastructure (M6).
2. **Service locator in the core.** `SP\Core\Application` (config, event dispatcher, context) is injected into 74 Domain files and into every web controller base. Domain services therefore have hidden dependencies, and the `Service` base class (60 subclasses) reads the session directly (`CryptSession::getSessionKey`, `SessionContext`).
3. **Domain speaks presentation.** 73 Domain files call `__u()`, which yields 346 user-facing strings. 30 files call `processException()`. Domain adapters extend or use Mvc types (`ItemTrait` in `Account`/`Category`/`Client` adapters, `SelectItemAdapter` in `Account`, `PasswordValidator` in `AccountPreset`). `Domain/Core/UI/ThemeIconsInterface` imports `SP\Html\Assets\IconInterface`. `Domain/Http/Services/Request` and `Domain/Import/Services/ImportStrategy` import `SP\Util\Util`.
4. **Inverted dependency.** `Domain/Api/Services/Api.php` imports `SP\Modules\Api\Controllers\Help\HelpInterface`. The Domain depends on the interface layer.
5. **Ports exist but are bypassed.** There are 143 port interfaces and 275 port imports in the modules, yet 127 imports still reach concrete Domain services (23 non-exception classes such as `Config`, `ConfigFile`, `Login`, `AuthToken`, `Installer`, `MasterPass`, `TemporaryMasterPass`, `Request`, `JsonResponse`, `JsonRpcResponse`, `Account`, `PublicLink`). Modules also call static Domain helpers directly: `ConfigBackup::configToJson()`, `ConfigUtil::mailAddressesAdapter()`.
6. **Wiring is implicit.** `Core/Definitions/DomainDefinitions.php` binds ports to implementations through wildcard name conventions (`Domain\X\Ports\*Repository` → `Infrastructure\X\Repositories\*`). Nothing verifies that every port resolves.
7. **Core ↔ Infrastructure.** Outside composition, `Core/UI/Theme`, `ThemeIcons`, `ThemeContext`, `Crypt/CryptPKI`, `MimeTypes`, `AppLock`, `Acl/Actions` import `FileSystem`/`FileHandler`/`FileException` directly. `Core/Definitions` is the composition root and may import Infrastructure.
8. **Presentation in `lib/`.** `lib/SP/Mvc` (21 files) and `lib/SP/Html` (25 files) are interface-layer code living in the core library. `Mvc/Controller/WebControllerHelper` imports `SP\Modules\Web\Controllers\Helpers\*`.
9. **Controllers own orchestration.** 226 `->notify(` audit/event calls and 74 controllers that build `EventMessage` sit inside the interface layer. 36 presentation helper classes live in `app/modules/web/Controllers/Helpers` (largest: `AccountActionsHelper` 513 lines). The largest controller is `ConfigManager/IndexController.php` (560 lines).
10. **Migration is test-gated and partly done.** Every web area that has an integration test is already on `#[Action]` (only 1 legacy controller left in tested areas). The 27 untested areas hold 83 of the 84 legacy controllers. API (46 files) has not started. CLI (8 files) is nearly clean.
11. **CI does not run the web controller tests.** `feature.yaml` runs `phpunit --group unitary --testsuite core` (unit) and `phpunit --testsuite core` (integration, gated by `TESTS_INTEGRATION_ENABLED`). The `core` suite covers `SP/Core`, `Domain`, `Infrastructure`, `Mvc`, `Util`. It does not cover `SP/Modules`, so the 19 web controller IT files added on this branch (Aug–Dec 2024) never run in CI. In `phpunit.xml`, the `modules` suite excludes `Api` and `Cli` (6 + 3 test files).
12. **Cross-context coupling.** Concrete imports across bounded contexts are heaviest in `Import` (51 lines), `Auth` (41), `Account` (33), `Install` (15), `Export` (14).
13. **Parallel branch overlap.** `feat/code_refactoring` has 2 commits not in this branch (`1128bdf4` modularises ConfigManager views, `f1d296e6` IDE/Qodana config). Reconcile before touching ConfigManager (P6-W7).
14. **No guardrails.** No architecture test, no deptrac, PHPStan only on `lib` at level 6 (`composer phpstan-app`), no CI job for architecture rules.

---

## 4. Decisions needed

Each has a recommendation. Decide before the phase that depends on it.

| ID | Question | Options | Recommendation | Needed before |
|---|---|---|---|---|
| D1 | Where do user-facing messages get translated? | (a) Domain keeps `__u()` as a marker and translation happens at the boundary. (b) Domain throws exceptions with stable error codes and message keys; modules translate. | (b) for new code; migrate contexts one at a time. Keep (a) as a stopgap if a context is too big. | P4-2 |
| D2 | Where do use cases live? | (a) `SP\Domain\<Ctx>\UseCases`. (b) new top-level `lib/SP/Application/`. | (a). It avoids a third top-level namespace and a second move of the same classes. **Decided: (a).** | P5-* |
| D3 | Move Mvc and Html out of `lib/`? | Yes into `app/modules/web/` (namespace `SP\Modules\Web\Mvc`, `...\Html`) | Yes. **Decided: yes.** | P4-1 |
| D4 | Run the `modules` suite and integration tests in CI by default? | Yes now / after P0-1 green / keep gated | After P0-1 and P0-2 succeed: make integration job default on | P0-2 |
| D5 | How to enforce layer rules? | (a) deptrac (new dev dependency). (b) PHPUnit architecture test using `nikic/php-parser` (already in `require-dev`). | (b) first, with an allowlist that can only shrink (ratchet). Consider deptrac later. | P0-3 |
| D6 | Pilot bounded context for the full pattern | `Tag` (5 core files, 9 controllers, 0 tests, 18 external references) or `ItemPreset` (10 domain files, 9 controllers) | `Tag` | P6-W1 |
| D7 | Exception policy | Keep domain exceptions (`ServiceException`, `AuthException`, `ImportException`, `UpdatedMasterPassException`, `SPException` family) as the only Domain types modules may import. Move Infrastructure exceptions to Domain. | Adopt | P1-1 |
| D8 | API migration style | Big bang vs per-controller with a response adapter | Per-controller, behind a thin JSON-RPC/REST response adapter in `app/modules/api`. **Decided: per-controller.** | P7-* |
| D9 | DI wiring style | Keep wildcard conventions + container test, or explicit bindings per port | Explicit bindings for ports (generated list checked by P0-4). **Decided: explicit bindings.** | P9-3 |

**Decision log**

- **D1 (2026-10-27): option (b).** Domain exceptions carry a stable code and an untranslated message key; the interface layer translates. Migrate one bounded context at a time, starting with `Tag` (P4-2). Option (a) remains a stopgap for contexts that are too large.
- **D2 (2026-10-27): option (a).** Use cases live in `SP\Domain\<Ctx>\UseCases`; their Commands and Results live in `SP\Domain\<Ctx>\UseCases\Dtos`. No third top-level namespace.
- **D3 (2026-10-27): yes.** Presentation code moves out of `lib/`: `SP\Mvc` -> `SP\Modules\Web\Mvc`, `SP\Html` -> `SP\Modules\Web\Html`, and the theme icon service moves with it to `SP\Modules\Web\UI`. `Core/Definitions` remains the composition root and may reference module classes.
- **D8 (2026-10-29): per-controller.** Migrate API controllers one resource at a time, behind a thin JSON-RPC/REST response adapter in `app/modules/api` (P7-2), instead of a big-bang rewrite. The adapter maps `ActionResponse` to both envelopes and replaces the direct `JsonRpcResponse` / `ApiResponse` calls.
- **D9 (2026-10-29): explicit bindings.** Replace the wildcard `DomainDefinitions` port bindings with one explicit entry per port, verified by the container wiring test (P0-4). The generated list is a ratchet: new ports must resolve, and P9-3 removes the 9 stale wildcard bindings recorded in P0-4.

---

## 5. Phased backlog

Phases are ordered so the guardrails come first, then inward dependency fixes, then interface migration by vertical slice. Phases 1–4 are mostly independent of each other once P0 is done. P5/P6 depend on P2 and P3 for the slice being migrated.

### Phase 0: Baseline and guardrails (blocking)

- [x] **P0-1 Establish a verified baseline.** Set up PHP 8.2, Composer deps, and MariaDB 10.3 (same as CI). Run `vendor/bin/phpunit -c tests/phpunit.xml --group unitary --testsuite core`, `--testsuite core`, and `--testsuite modules`. Record pass/fail counts and coverage (`tests/_output`). Triage failures: fix or quarantine each with an issue reference.
  - Acceptance: a baseline table in section 9 with the exact PHP version, pass/fail counts per suite, and the list of quarantined tests.
- [~] **P0-2 Run the web controller tests in CI.** Add a CI step `phpunit -c tests/phpunit.xml --testsuite modules --group integration` (Web). Decide on Api and Cli (see below). Remove `<exclude>` for `Api` and `Cli` in `tests/phpunit.xml` only once those suites pass.
  - Acceptance: CI log shows the web controller IT files executing. Decision D4 recorded.
- [x] **P0-3 Architecture test with a shrinking allowlist (D5).** Add `tests/SP/Architecture/LayerDependencyTest.php`. It parses `use` statements with `nikic/php-parser` and asserts the rules in section 1.2. Existing violations are listed in an allowlist file in the test directory. The test fails on any new violation, and the allowlist is only allowed to shrink.
  - Acceptance: test green on current code. Adding `use SP\Infrastructure\...` to a Domain file makes it fail.
- [x] **P0-4 Container wiring test.** Add a test that resolves every port interface in `SP\Domain\*\Ports\*` through the built container (`IntegrationTestCase::buildContainer` or a minimal `ContainerBuilder`). This documents the wildcard bindings and catches broken ones.
  - Acceptance: test lists all 143 port interfaces, or documents exceptions explicitly.
- [x] **P0-5 Metrics script.** Turn the section 2 commands into `scripts/architecture-metrics.sh` (or keep as commands in this file). Prints M1–M16.
  - Acceptance: one command prints the full table.
- [x] **P0-6 Test hygiene.** Add `#[Group]` to the 11 untagged test files. Ensure every IT test has `#[Group('integration')]` and every unit test has `#[Group('unitary')]`.
  - Acceptance: M16 = 0.

### Phase 1: Remove outer-layer types from the Domain (mechanical, no behaviour change)

- [x] **P1-1 Move Infrastructure exceptions to Domain (D7).** `NoSuchItemException`, `DuplicatedItemException` (`Infrastructure/Common/Repositories`), `FileException` (`Infrastructure/File`), `DatabaseException` (`Infrastructure/Database`). All extend `SP\Domain\Core\Exceptions\SPException` already. Move to `SP\Domain\Core\Exceptions\` (or `Domain\Common\Exceptions\`). Update ~150 imports across Domain, modules, and tests.
  - Acceptance: no `use SP\Infrastructure\...Exception` in `lib/SP/Domain` or `app/modules`. Full test run green.
  - Targets: M7 drops by 48 import lines (36 + 9 + 3). M1 drops for every Domain file whose only outer import is one of these exceptions.
- [x] **P1-2 Move `QueryResult` to Domain and remove its translation call.** Move `SP\Infrastructure\Database\QueryResult` to `SP\Domain\Database\Dtos\QueryResult` (depends only on `Domain\Common\Models\Model`). Replace `__u()` in its constructor with a domain exception message. Update the `Infrastructure\Database\QueryData` that builds it.
  - Acceptance: M6 drops to 0 (ports no longer import Infrastructure types). M14 drops for files that only import `QueryResult`.
- [x] **P1-3 Move `Hash` to the Domain crypto context.** `SP\Core\Crypt\Hash` (static `hashKey`, `checkHashKey`, `signMessage`, `checkMessage`) is used in 16 Domain files and only depends on `logger()`. Move it to `SP\Domain\Crypt\Services\Hash` (or behind a `HashService` port). Add characterization tests first if none exist. This is security code: output bytes must stay identical.
  - Acceptance: byte-for-byte identical hashes for fixed fixtures before and after; no `use SP\Core\Crypt\Hash` in Domain.
- [x] **P1-4 Move events and messages into the Domain.** Move `SP\Core\Events\Event` and `EventMessage` value objects to `SP\Domain\Core\Events\` and `SP\Domain\Core\Messages\`. Keep `EventDispatcher` implementation in Core. Replace `HtmlFormatter`/`TextFormatter` concrete references inside `EventMessage` with the existing `Domain\Core\Messages\FormatterInterface`, injected by Core.
  - Acceptance: no `use SP\Core\Events\...` in Domain or Infrastructure. This removes the Event/EventMessage imports (39 + 30 Domain files, 5 Infrastructure use lines) from M3.
- [x] **P1-5 Move `MailMessage` into Domain notification.** `SP\Core\Messages\MailMessage` → `SP\Domain\Notification\...`. Acceptance: no `SP\Core` in `Domain/Notification`.
- [x] **P1-6 Invert `HelpInterface` dependency.** Define a `SP\Domain\Api\Ports\HelpProvider` (or similar) in Domain. Implement it in `app/modules/api`. `Domain/Api/Services/Api.php` depends only on the port.
  - Acceptance: M12 drops by 1 (`Domain/Api` no longer references `SP\Modules`).
- [x] **P1-7 Strip presentation from Domain adapters.** Move `ItemTrait` usages out of `Domain/Account/Adapters/Account`, `Domain/Category/Adapters/Category`, `Domain/Client/Adapters/Client`. Move `SelectItemAdapter` out of `Domain/Account/Adapters/Account`. Move `PasswordValidator` usage out of `Domain/Account/Services/AccountPreset` (into the calling controller, or into a Domain validator with a rule port). Replace `IconInterface` in `Domain/Core/UI/ThemeIconsInterface` with a domain icon-key type. Replace `SP\Util\Util` in `Domain/Import/Services/ImportStrategy` and `Domain/Http/Services/Request` with a domain helper.
  - Acceptance: Domain has 0 imports of `SP\Mvc`, `SP\Html`, `SP\Util` and `SP\Modules` (currently 4 + 1 + 2 + 1 files), and M1 drops by the same amount, unless those files also import Core.
- [ ] **P1-8 Relocate response formats out of the Domain.** `Domain/Http/Services/JsonResponse`, `Domain/Api/Services/JsonRpcResponse`, `Domain/Api/Dtos/ApiResponse`: move to `app/modules/{web,api}` as adapters, or delete once P6/P7 remove their callers. Track callers in section 9.

### Phase 2: Remove the service locator and session access from the Domain

- [x] **P2-1 Replace `Application` in `Domain\Common\Services\Service`.** Constructor takes explicit ports: `ConfigFileService`, `EventDispatcherInterface`, `Context`. Update the 60 subclasses (and 10 with another base). PHP-DI autowires by type, so DI definitions should not change. Verify with P0-4.
  - Acceptance: no `SP\Core\Application` import in `lib/SP/Domain` (M4 = 0).
- [x] **P2-2 Master-key handling behind a port.** `Service::getMasterKeyFromContext()` and `setMasterKeyInContext()` currently call `CryptSession` and check `SessionContext`. Introduce `SP\Domain\Crypt\Ports\MasterKeyStore` (get/set/clear). Implement it in Core (session) and Stateless (non-session context). Callers use the port.
  - Acceptance: no `SP\Core\Crypt\Session` or `SP\Core\Context\Session` import in Domain. Auth and account encryption tests still pass.
- [x] **P2-3 Remove direct session superglobal use.** `$_SESSION` appears in 2 `lib/` files and `$_SERVER` in 1. Move into Core context implementation.
  - Acceptance: superglobal use only in `SP\Core\Context\*` and bootstrap files.
- [x] **P2-4 Logging port.** Replace `logger()` in 16 Domain files with an injected `Psr\Log\LoggerInterface` (or `SP\Domain\Core\Log\LoggerInterface`). Keep `logger()` in Core.
  - Acceptance: Domain files do not use `function SP\logger`.

### Phase 3: Ports for everything the interface layer uses

- [ ] **P3-1 Port every concrete Domain service imported by the modules.** The 23 non-exception classes (see finding 5). Priority: `Config`/`ConfigFile`/`ConfigBackup`, `Login`/`AuthToken`, `User`/`UserPassRecover`, `MasterPass`/`TemporaryMasterPass`, `Installer`, `Account`/`PublicLink`, `Request`/`JsonResponse`, `ApiRequest`/`JsonRpcResponse`, `TaskFactory`, `ItemPresetRequest`, `ImportStrategy`, `MinifyJs`/`MinifyCss`, `MailEvent`.
  - Acceptance: M8 = number of `use` lines that reference a Domain Services class not in the exception allowlist = 0.
- [ ] **P3-2 Move static domain helpers behind ports or value objects.** `ConfigBackup::configToJson()` (called from `DownloadConfigBackupController`), `ConfigUtil::mailAddressesAdapter()`, and similar statics called from controllers. Use a serializer/mapper service in Domain injected via port, or a value object constructor.
  - Acceptance: no static call on a Domain Services class from `app/modules` (except exception factories and enums).
- [x] **P3-3 Cross-context imports via ports.** Reduce concrete cross-context imports in `Import` (51), `Auth` (41), `Account` (33), `Install` (15), `Export` (14). Use the owning context's port, or a domain event if the coupling is a notification.
  - Acceptance: per-context count of cross-context concrete imports decreases; list in section 9.

### Phase 4: Presentation out of `lib/`

- [x] **P4-1 Move `lib/SP/Mvc` and `lib/SP/Html` into `app/modules/web`.** Namespace `SP\Modules\Web\Mvc\...` and `SP\Modules\Web\Html\...` (decision D3). Move `Core/UI/Theme*` icon usage too. Update `tests/` imports.
  - Acceptance: `lib/SP/Mvc` and `lib/SP/Html` removed; M1 and M12 reduced; `lib/` has no `SP\Mvc`/`SP\Html` references (except as listed in section 1.2).
- [x] **P4-2 Translation policy (D1).** Per bounded context, replace `__u()` in Domain code with domain exceptions carrying a stable code and an untranslated message key. Translate in the module boundary (exception-to-response mapper in `app/modules/web` and `app/modules/api`). Do one context per task, starting with `Tag` (pilot, see P6-W1).
  - Acceptance: M5 drops per context. A snapshot test of user-visible messages for each migrated context proves no text changed.
- [ ] **P4-3 Remove `processException()` from the Domain (30 files).** Domain throws; the module boundary logs and maps. Same per-context approach as P4-2.
- [x] **P4-4 Remove `Core/Acl/Actions` and `Core/Crypt` file-system dependencies.** See finding 7. Move the file-based implementations into Infrastructure and keep Core as wiring.

### Phase 5: Application layer (use cases) — the slice template

Applied per context in Phase 6 and Phase 7. The steps are in section 6.

- [x] **P5-1 Define the Command/Result DTO convention** under `SP\Domain\<Ctx>\UseCases\Dtos` (D2; or existing `Dtos`). Document in ADR-003 (section 8).
  - Location and naming: `<Action><Ctx>Command` and `<Action><Ctx>Result` in `SP\Domain\<Ctx>\UseCases\Dtos`. One Command per user intent; a Result even when it only echoes an id or the deleted ids.
  - Shape: `final readonly class` with public promoted properties. No base `Dto`, no reflection hydration, no getters boilerplate.
  - Contracts: Commands implement `SP\Domain\Common\Dtos\Command`; Results implement `SP\Domain\Common\Dtos\Result`.
  - Boundary: Commands hold already parsed primitives, value objects or domain DTOs. No HTTP, session or presentation types; no translation; no logging. Validation and domain exceptions belong to the use case.
  - Pilot inputs: `SP\Domain\Tag\UseCases\Dtos` has the Tag commands and results; `TagUseCaseDtosTest` guards the shape.
- [x] **P5-2 Define the use case base contract.** Single public method (`execute(Command): Result`), no HTTP types, no session types, no translation calls. Throws domain exceptions only.
- [x] **P5-3 Event emission moves to use cases.** Audit/notification events currently emitted by controllers (226 `->notify()` calls) move into the use case, emitted via `EventDispatcherInterface`. Controllers no longer build `EventMessage`.
  - Port: use cases depend on `SP\Domain\Common\Ports\EventEmitter` (`emit(eventName, source, ?EventMessage)`). `SP\Core\Events\EventDispatcherEmitter` adapts it to `EventDispatcherInterface`; the port only publishes, so use cases cannot attach or detach receivers.
  - Event name: keep the existing name exactly (`create.tag`, `delete.tag`, `request.account`, ...). Receivers and the mail event configuration match on it.
  - Source: the use case instance, not the controller. Receivers only inspect the source to detect a `Throwable`.
  - Message: descriptions and detail keys are untranslated keys. Receivers translate when composing the event log, mail or notification (D1 policy). Do not call `__()`/`__u()` in a use case.
  - Message-less events: pass `null` to reproduce the legacy `new Event($this)` emission.
  - Extras: keep the keys receivers read (`userId`, `userProfileId`, `userGroupId`, `notify`) on the `EventMessage`.
  - Timing: emit only after the state change succeeded and before returning the Result.
  - Migration: per area in P6/P7. The metric is `grep -rl "EventMessage::build" app/modules | wc -l`; the Tag pilot is the first area. `UseCaseEventEmissionTest` and `EventDispatcherEmitterTest` guard the convention.

### Phase 6: Web controller migration by vertical slice

Each slice follows section 6. Waves are ordered by legacy count and risk. Current state: 26 untested areas, 77 legacy controllers (after the Tag pilot).

- [x] **P6-W1 Pilot: `Tag`** (7 legacy of 9). Also the pilot for D1 (P4-2) and the use-case pattern (P5-*). Done: six use cases, thin controllers and the first web IT suite for the area.
- [x] **P6-W2 Small CRUD areas: `ItemPreset`** (7 of 9), **`CustomField`** (7 of 9), **`UserGroup`** (7 of 9), **`UserProfile`** (7 of 9). Done: 24 use cases, thin `ActionResponse` controllers, and unit tests with mocked ports.
- [x] **P6-W3 Remaining untested areas:** `Notification` (8 of 11), `PublicLink` (8 of 10), `Plugin` (6 of 8), `Track` (3 of 4), `Eventlog` (2 of 3), `Status` (2 of 3), `Items` (2 of 5), `UserPassReset` (2 of 5), `Resource` (0 of 3), `Error` (0 of 5). Done: 25 use cases, thin `ActionResponse` controllers, and one integration test class per area.
- [ ] **P6-W4 `User`** (9 of 11). Depends on P3-1 for `User` and `UserPassRecover`.
- [ ] **P6-W5 Auth and bootstrap entry points: `Login`** (1 of 3), **`Install`** (1 of 2), **`Upgrade`** (1 of 2), **`Task`** (0 of 2), **`Index`**, **`SecurityManager`**, **`ItemManager`**, **`UserSettingsGeneral`**, **`UserSettingsManager`**, **`ConfigWiki`**, **`Traits`**. High risk. `Login` needs IT tests before migration, because `Domain/Auth/Services/LoginAuthHandler.php` mixes Core, Infrastructure, translation and logging calls (32 matches).
- [ ] **P6-W6 Already-tested legacy leftovers.** Migrate the 1 remaining legacy controller in tested areas. Remove `JsonTrait` and `ConfigTrait` once unused.
- [ ] **P6-W7 Large tested areas that still carry Domain-side debt.** `Account` (27 controllers, `Domain/Account/Services/Account.php` 660 lines), `ConfigManager/IndexController.php` (560 lines; reconcile with `feat/code_refactoring` commit `1128bdf4` first), `Controllers/Helpers` (36 classes of presentation logic, to move to `app/modules/web` presenters). Apply P2–P5 to these, one area per task.

### Phase 7: API module migration

- [ ] **P7-1 Enable API tests.** Remove `Api` from the `<exclude>` in `tests/phpunit.xml` after P0-2 is green. Fix failing API tests first.
- [x] **P7-2 API response adapter.** One adapter in `app/modules/api` that maps `ActionResponse` to the JSON-RPC/REST envelope (replaces `JsonRpcResponse`, `ApiResponse::makeSuccess`, 29+20 call sites) (D8).
- [ ] **P7-3 Migrate API controllers per resource** (`Account`, `Category`, `Client`, `Tag`, `UserGroup`, `Config`), following section 6. Start with `Tag` to reuse the P6-W1 use cases.
  - Acceptance: M11 = 46/46 or the remaining files listed with reasons.
- [ ] **P7-4 Delete `Domain/Api/Services/ApiRequest` and `JsonRpcResponse` and `Dtos/ApiResponse`** once unused (or move into `app/modules/api`).

### Phase 8: CLI module

- [ ] **P8-1 Enable CLI tests** (`Cli` `<exclude>` in `tests/phpunit.xml`) after P0-2.
- [ ] **P8-2 Replace concrete Domain services in commands** (`InstallCommand`, `BackupCommand`, and the `Crypt/*` commands) with ports or use cases. The Infrastructure exception import is fixed by P1-1.
  - Acceptance: CLI import list contains only ports, use cases, models, and exceptions.

### Phase 9: Core cleanup and composition root

- [x] **P9-1 Core ↔ Infrastructure cleanup.** Finish P4-4. Keep `Core/Definitions` as the only place that names Infrastructure classes (composition root). Acceptance: M13 = 2 (composition files only). Enforced by the `core-infrastructure` architecture rule.
- [x] **P9-2 Remove `SP\Core\Application`** after all controllers and services stop injecting it (M4 = 0 and modules count = 0). Replaced with the `ModuleDependencies` and `ApiControllerHelper` parameter objects plus the existing `WebControllerHelper`/`SimpleControllerHelper` web aggregates.
- [ ] **P9-3 Explicit port bindings (D9).** Replace wildcard `DomainDefinitions` bindings with explicit entries, verified by P0-4.
- [ ] **P9-4 Remove legacy response types.** `JsonMessage`, `returnJsonResponse`, `JsonTrait`, `Domain\Http\Services\JsonResponse` (once P6 and P7 are done).
- [ ] **P9-5 Tighten the architecture test.** Empty the allowlist from P0-3 and turn the ratchet into a hard rule.

### Phase 10: Testing strategy

- [x] **P10-1 Unit tests for the core use memory fakes, not Infrastructure.** Build in-memory port fakes under `tests/SP/Stubs/` (there are a few already: `AccountRepositoryStub`, `UserGroupRepositoryStub`, `PublicLinkRepositoryStub`). Migrate the 55 Domain test files that import Infrastructure. Acceptance: M14 = 0.
- [~] **P10-2 Keep Infrastructure tests DB-backed.** Repository queries with the highest risk run against the real test database (`#[Group('integration')]`, base `SP\Tests\DatabaseRepositoryTestCase`). Done: Tag; User (login lookup/update incl. SSO, usage, group emails, admin-hiding search); UserGroup usage; AccountHistory reads/search; AuthToken search; CustomFieldData/CustomFieldDefinition search; ItemPreset search; Track search; AccountFile reads/search; AccountToUser/AccountToUserGroup permissions; PublicLink listing. The 28 mocked repository tests stay `unitary`, since they only assert generated SQL. Acceptance: DB-backed tests run in the CI integration job (P0-2); the residual P0/P1 test backlog is follow-up work.
- [ ] **P10-3 HTTP-level IT per migrated web area.** Follow the existing `IntegrationTestCase` + `BodyChecker` pattern. Each slice in P6 adds one IT class per controller action, written before the migration (characterisation tests).
- [x] **P10-4 Coverage gates per layer.** Record current coverage per layer in section 9. Set target coverage for `Domain\*\UseCases` and `Domain\*\Services` once the baseline is known (decision needed after P0-1).
- [x] **P10-5 Test naming and attribute standard.** One style for all tests: `#[Test]` attribute plus `#[Group]`. The whole suite now adds `#[Test]` to every `test*` method; M16 stays 0.
- [ ] **P10-6 Document the test pyramid** in ADR-004 (section 8).

### Phase 11: Documentation and decision records

- [ ] **P11-1 ADR-001** target architecture and dependency rules (section 1).
- [ ] **P11-2 ADR-002** translation and error policy (D1).
- [ ] **P11-3 ADR-003** use case and Command/Result convention (P5-*).
- [ ] **P11-4 ADR-004** test strategy (unit, DB-backed, HTTP IT) and CI gating (D4).
- [ ] **P11-5 Glossary** of bounded contexts and domain terms (for example "use case", "port", "item", "master key").

---

## 6. Slice template (Definition of Done for one web or API area)

1. **Characterisation test first.** Add an IT class under `tests/SP/Modules/Web/Controllers/<Area>/` (or `Api/Controllers`), one test per controller action, using `IntegrationTestCase::buildContainer` and `runApp`. Run it green on the current code.
2. **Use case.** Create `SP\Domain\<Ctx>\UseCases\<Action>UseCase` with a Command and Result DTO. Depends only on ports. Moves business logic, validation and audit events out of the controller.
3. **Ports.** Any repository method returning `QueryResult` is changed to return domain models or DTOs (after P1-2). Any concrete service the controller used is replaced with a port (P3-1).
4. **Controller.** `#[Action(ResponseType::...)]` method, returns `ActionResponse`, around 30 lines max: parse request, build Command, call use case, map Result to `ActionResponse`. Legacy `returnJsonResponse` and `JsonMessage` removed.
5. **Unit tests** for the use case with in-memory fakes (no Infrastructure, no DB).
6. **Exceptions** map to `ActionResponse::error(...)` at the module boundary (P4-2 policy).
7. **Dead code removed:** unused helpers, traits, `EventMessage` in the controller.
8. **Metrics:** M9/M10 (web) or M11 (API) moved; M1, M7, M8 do not increase; the architecture test passes.
9. **Verification:** the area's IT tests, the module's unit tests, `composer phpstan-app` (level 6), and `composer phpcs` on the changed `lib/` files.

---

## 7. Recommended order of execution (first 10 tasks)

1. P0-1 Verified baseline (blocking; needs a PHP environment).
2. P0-2 Run the modules suite in CI.
3. P0-3 Architecture test with a shrinking allowlist.
4. P0-4 Container wiring test.
5. P1-1 Move Infrastructure exceptions to Domain (mechanical, large, low risk).
6. P1-2 Move `QueryResult` to Domain.
7. P2-1 Replace `Application` in `Service` base.
8. P6-W1 Pilot slice `Tag` (characterisation IT → use case → controller → unit tests), which also validates D1 (P4-2) on a small context.
9. P1-3 Move `Hash` (after characterisation tests; security review).
10. P3-1 Port the first batch of concrete services used by `Tag`, `ItemPreset`, `CustomField`.

---

## 8. Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| No PHP runtime in the current sandbox; baseline unverified | All verification is unproven | P0-1 first; until done, every task must state "not executed" for test results |
| Namespace moves conflict with the open `feat/code_refactoring` branch | Merge pain | Do P1 moves in dedicated commits; reconcile `1128bdf4` before P6-W7 |
| Hash, session and master-key changes break encryption | Data loss or auth bypass | Characterisation tests with fixed fixtures; byte-identical output checks; security review for P1-3 and P2-2 |
| Translation policy change alters user-visible text | UX regressions and translation breakage | Snapshot tests of messages per context; keep message keys identical to existing `__u()` strings |
| Large mechanical edits (P1-1 ~150 imports, P2-1 74 files) are hard to review | Hidden errors | Scripted rewrites with a diff review; run full unit and IT suites after each step |
| Web controller IT tests were not running in CI | Silent regressions in the work done on this branch since Aug 2024 | P0-2 is blocking for the slice work |
| Integration tests need MariaDB 10.3 | CI time and flakiness | Keep DB-backed tests in the integration group; unit tests use fakes only |

---

## 9. Progress log

| Date | Task | Metric changes | Notes |
|---|---|---|---|
| 2026-10-24 | Plan created | Baseline in section 2 | No code changes yet. Working tree clean at `4b83c270`. |
| 2026-10-25 | P0-1 verified baseline | M16 11 → 0 after P0-6; other metrics unchanged | PHP 8.2.12, Composer 2.7.6, MariaDB 10.3 (Docker). Fixed test-harness and flaky-test debt; one test quarantined (Q1). |
| 2026-10-25 | P0-3 architecture test | Allowlist: domain-only 395, infrastructure-only 6, modules-infrastructure 89, modules-concrete-services 38, lib-modules 2, domain-application 74, domain-presentation-calls 126 | `LayerDependencyTest` + `LayerRules` + shrinking `allowed-violations.php`. Verified new Domain → Infrastructure, `__()`, and `__u()` calls fail. |
| 2026-10-25 | P0-4 container wiring test | 99/143 ports resolve; 44 documented exceptions | Expected-unresolved map is a ratchet: new failures fail, fixed ports must be removed from the map. 9 stale wildcard bindings recorded for P9-3. |
| 2026-10-25 | P0-5 metrics script | — | `scripts/architecture-metrics.sh` prints M1–M16 in one command. |
| 2026-10-25 | P0-2 (in progress) | — | Web controller step added to the integration job. Job stays gated until its first green CI run (D4). Api/Cli stay excluded (see P7-1/P8-1). |
| 2026-10-25 | P0-6 test hygiene | M16 = 0 | Added the 11 missing `#[Group]` attributes. |
| 2026-10-25 | Review fixes (1–3) | `domain-presentation-calls` 103 → 126 | Added frozen per-rule allowlist maxima; included `__()` and per-file call counts; made M6 tolerate zero matches. |
| 2026-10-26 | P2-1 ports in `Service` (verified: unitary, wiring, core, modules) | M4 74 → 0; M1 96 → 45; M3 88 → 32; allowlist domain-only 144 → 70, domain-application 74 → 0 | `Service` takes `ConfigFileService`, `EventDispatcherInterface`, `Context`; 73 Domain subclasses forward the ports (`ImportStrategy` and `XmlImportBase` edited by hand). Tests use `$this->config`, `$this->eventDispatcherStub`, `$this->context`. `php-testing-composer:2.6`: `--group unitary --testsuite core` 2000 tests, 0 failures; `--testsuite core` 2001 tests, 0 failures; `--testsuite modules` 96 tests, 0 failures, 0 errors, 1 skipped (197 assertions vs 199 in the P0-1 baseline, not investigated). The only error in the first two runs is `ImageTest::testConvertText` (`imagefttext()` missing; this image lacks GD FreeType; `Image.php` untouched). `LayerDependencyTest` 7/7 and `ContainerWiringTest` pass. Core and modules ran against the existing `syspass-test-db` (MariaDB 10.3) using a dedicated `syspass_p21` database. Clear `CACHE_PATH/container/` before running with DEBUG off, because compiled containers keep old constructor signatures. |
| 2026-10-27 | P1-5 mail message into Domain (verified: unitary, core, modules, architecture, wiring) | M1 35 → 30; M3 19 → 14; allowlist domain-only 54 → 48 | `MailMessage` moved to `SP\Domain\Notification\Dtos`; `MessageBase` moved to `SP\Domain\Core\Messages` and no longer implements `MessageInterface`. Command-side formatting moved behind `MailMessageFormatterInterface` (Core implementation `MailMessageFormatter`), wired in `CoreDefinitions`. `EventMessageFormatterInterface` gained `formatDescription()` so `MailEvent` no longer imports `TextFormatter`. `Mail` now takes `MailMessageFormatterInterface`. `MailMessage` is a pure data DTO; `NotificationMessage` explicitly implements `MessageInterface`. PHPStan unchanged at 1143 errors. Suites: core unitary 2007 tests, core 2008 tests, modules 96 tests, all green (1 skipped, known warnings). |
| 2026-10-27 | P1-6 invert `HelpInterface` (verified: architecture, API unit, modules) | M12 3 → 2; allowlist domain-only 48 → 47; M6 144 → 145 ports | Added `SP\Domain\Api\Ports\HelpProvider`; `HelpInterface` extends it, so the module help classes implement the Domain port. `Api` now validates against `HelpProvider` and has no `SP\Modules` import. PHPStan reports only the pre-existing errors in `Api.php`; API unit tests 50/50 and modules 96/96 green (1 skipped, known warnings). |
| 2026-10-27 | P1-7 strip presentation from Domain adapters (verified: unitary, core, modules, architecture, wiring) | M1 33 → 24; allowlist domain-only 47 → 38; M6 stays 0/146 | `CustomFieldItems` in Domain replaces `ItemTrait` in the Account/Category/Client adapters and backs the Mvc trait. `Account` maps tags/users/groups to plain `{id,name}` arrays instead of `SelectItemAdapter`. `SP\Domain\Account\Ports\PasswordValidator` is the new port; the Mvc validator implements it and is bound in `CoreDefinitions`. `Domain\Core\UI\IconInterface` is the domain icon contract, extended by `SP\Html\Assets\IconInterface`; `ThemeIconsInterface` now uses `FileCacheService`. `Environment::getMaxUpload()`/`convertShortUnit()` and `Filter::getBool()` replace `SP\Util\Util` in Domain; `Util` delegates to them. Also documented the module-specific `HelpProvider` port in `ContainerWiringTest` (P1-6 regression). PHPStan: no new errors in changed files. Suites: core 2008 tests, modules 96 tests, architecture 7/7, wiring green (known warnings/deprecations). |
| 2026-10-27 | P2-2 master key behind a port (verified: unitary, core, modules, architecture, wiring) | M1 24 → 23; M3 14 → 13; M5 73 files / 348 → 72 files / 345 sites; allowlist domain-only 38 → 35, presentation calls 126 → 125; M6 146 → 147 ports | Added `SP\Domain\Crypt\Ports\MasterKeyStore` with `get()`/`set()`/`clear()` and removed the Core session imports from `Domain\Common\Services\Service`. Core implementations: `SP\Core\Crypt\SessionMasterKeyStore` (encrypted session vault) and `SP\Core\Context\StatelessMasterKeyStore` (transient context key), selected by a factory in `CoreDefinitions` from the injected `Context`. The eight callers (`Api`, `AuthToken`, `TemporaryMasterPass`, `PublicLink`, `AccountCrypt`, `CustomFieldData`, `UserMasterPass`, `PluginData`) now take the port. Suites: core unitary 2007 tests, core 2008 tests, modules 96 tests, architecture 8/8, container wiring green (known warnings/deprecations). |
| 2026-10-27 | P2-4 logging port (verified: core, modules, architecture, wiring) | presentation calls 125 → 124; allowlist domain-only stays 35; M1 23, M3 13, M6 147 ports | Added `SP\Domain\Core\Log\LoggerInterface` plus `NullLogger`, implemented in Core by `SP\Core\Log\FunctionLogger` (delegates to `logger()` and has no dependencies, avoiding the `ConfigFile`/PSR-logger cycle) and bound in `CoreDefinitions`. All 16 Domain files that called `SP\logger` now use the port; the two static helpers (`Address`, `Hash`) lost their incidental debug lines. PHPStan unchanged. Suites: core 2008 tests, modules 96 tests, architecture 7/7, container wiring green. |
| 2026-10-27 | P2-3 remove direct superglobal use (verified: Domain Common, architecture) | metrics unchanged; M1 23, M3 13, M6 147 ports | `Http::checkHttps()` now reads the request URI through the existing `RequestService::getServer('REQUEST_URI')` port method instead of `$_SERVER`. The two remaining `$_SESSION` files are already in `SP\Core\Context`, so no superglobal access remains in `lib/` outside Core context or in `app/modules`. `HttpTest` now asserts the port call and that it is skipped when HTTPS is disabled or already in use. |
| 2026-10-27 | P3-2 static domain helpers (verified: architecture, core unit, Api/AuthToken unit, PSR2) | metrics unchanged; M8 89 (exception imports only), module static calls on Domain service classes = 0 | `ConfigBackup::configToJson()` and `ConfigUtil` normalisers were already moved behind `ConfigBackupService` and `ConfigInput` in P3-1. This task removed the remaining public statics from the `AuthToken` service (`Api` and the service now call the model; `canUseSecureTokenAction()` is private) and extended the `modules-concrete-services` architecture rule to reject static calls on Domain `Services` classes from `app/modules`, resolving imports and aliases while still allowing exception factories. Added `LayerRulesTest` coverage for direct and aliased calls. |
| 2026-10-27 | P4-4 remove Core file-system dependencies (verified: core unit, modules, architecture, wiring, PSR2) | M13 4 (+2 composition) → 0 (+2); M6 152 → 154 ports; other metrics unchanged | Added `SP\Domain\File\Ports\PhpFileLoaderInterface` (Infrastructure `PhpFileLoader` wraps `FileSystem::require`) and `SP\Domain\File\Ports\FileHandlerFactoryInterface` (Infrastructure `FileHandlerFactory`). `Theme` and `ThemeIcons` load theme data through the loader, `ThemeContext` joins paths locally, and `AppLock` creates its lock file through the factory so the handler is still constructed lazily. `CoreDefinitions` wires the two adapters. No `SP\Infrastructure\File` import remains in `lib/SP/Core` outside `Core/Definitions`. Added `PhpFileLoaderTest`. |
| 2026-10-27 | P4-2 Tag translation policy (D1) (verified: Tag unit, core unit, architecture, PSR2) | M5 72 → 71 files / 345 → 341 call sites; `domain-presentation-calls` allowlist 124 → 123 | Decided D1 option (b). Added `SP\Domain\Tag\Exceptions\TagException` with stable codes `NOT_FOUND` (1001) and `DELETE_FAILED` (1002) and untranslated keys. `Tag` no longer calls `__u()`; `JsonMessage`/`ActionResponse`/`JsonRpcResponse` translate at the boundary. The API `TagControllerTest` message assertions plus the new stable-code assertions act as the user-visible snapshot. Core unit 2014 tests green (known warnings/deprecations); architecture 12/12. The API integration test still cannot run locally because of the known `vfs://` mysql fixture issue (P7-1). |
| 2026-10-27 | P9-1 Core ↔ Infrastructure cleanup (verified: architecture 12/12) | M13 stays 0 (+2 composition); no other metric changes | P4-4 already removed the runtime references, so P9-1 adds the guard: a new `core-infrastructure` rule rejects `SP\Infrastructure\` imports from `lib/SP/Core` outside `Core/Definitions`. `LayerDependencyTest` freezes the rule at 0, and `LayerRulesTest` covers both the violating and the composition-root cases. |
| 2026-10-27 | P3-3 cross-context imports via ports (verified: core unit 2052, modules 104, architecture 18, PHPStan 1013) | M17 45 → 11; Import 9 → 0, Auth 5 → 0, Account 4 → 2, Install 1 → 0, Export 3 → 0; M6 0/156 → 0/157 ports | Injected the existing `HashService` port into every cross-context user (Api, Auth, Export, Import, Http, Install, User) and moved `Html`/`Address` to `Common\Providers`. Extracted `XmlIntegrity` to Common, removing the Import ↔ Export concrete coupling and the stale `ImportException` from `XmlVerifyService`. Moved LDAP params/results/exception/type-enum into Auth `Dtos`/`Exceptions`/`Enums` and added `LdapServiceFactory`, so Import no longer builds `LdapBase`. Moved `UserMasterPassStatus` to User `Models`. Added M17 to the metrics script. |
| 2026-10-27 | D2 + P5-1 use case DTO convention (verified: Tag DTO unit, architecture, PSR2) | M6 stays 0/154; other metrics unchanged | Decided D2 option (a): use cases live in `SP\Domain\<Ctx>\UseCases`, DTOs in `SP\Domain\<Ctx>\UseCases\Dtos`. Added the `Command` and `Result` marker contracts in `SP\Domain\Common\Dtos`, the Tag pilot commands and results (create, update, delete single/batch, get, search), and `TagUseCaseDtosTest`, which enforces final readonly classes with promoted public properties and the right contract. |

| 2026-10-27 | P4-1 move Mvc and Html into the web module (D3) (verified: core unit, core, modules, architecture, wiring, PSR2, PHPStan) | M12 stays 2 (composition root + bootstrap); lib-modules allowlist 2 -> 1; M1 23, M6 0/154, M13 0 (+2 composition); PHPStan lib 1143 -> 1015 errors (files moved out of the analysed tree) | Moved `lib/SP/Mvc` (21 files) and `lib/SP/Html` (25 files) to `app/modules/web/Mvc` and `app/modules/web/Html` under `SP\Modules\Web\Mvc` and `SP\Modules\Web\Html`. Moved `ThemeIcons` to `SP\Modules\Web\UI` so Core has no presentation import. Updated all class and template references. `TemplateResolver` no longer uses `FileSystem`; `ControllerTrait` uses the `JsonResponseFactory` port; `CustomFieldItems` moved from Domain Services to Domain Providers so modules do not call a concrete Domain service. The `lib-modules` rule now allows `Core/Definitions` as the composition root and the ratchet dropped to 1 (`BootstrapBase`). |
| 2026-10-27 | P5-2 use case base contract (verified: core, architecture, wiring, PSR2, PHPStan) | M6 0/154 -> 0/155 ports; other metrics unchanged | Added `SP\Domain\Common\Ports\UseCase` with one public method `execute(Command $command): Result` and the `TCommand`/`TResult` templates; no HTTP, session, translation or presentation concerns. `UseCaseContractTest` guards the single entry point and the Domain boundary, and runs a use case against the new `InMemoryTagRepository` fake. Documented the marker contract in `ContainerWiringTest`. |
| 2026-10-27 | P5-3 event emission convention (verified: core unit, architecture, wiring, PSR2) | M6 0/155 -> 0/156 ports; no other metric changes | Added `SP\Domain\Common\Ports\EventEmitter` (publish-only) and the `SP\Core\Events\EventDispatcherEmitter` adapter, wired in `CoreDefinitions`. Recorded the convention in section 5: keep the existing event names, use the use case as the source, pass untranslated message keys, support message-less events, preserve receiver extras, and emit only after the state change. Added `InMemoryEventEmitter`, `UseCaseEventEmissionTest` and `EventDispatcherEmitterTest`. Per-area call-site migration starts with the Tag pilot (#1975); no `app/modules` call site changed here, so the `EventMessage::build` count is still 98 files. |
| 2026-10-27 | P6-W1 Tag pilot (verified: core, modules, architecture, wiring, PSR2, PHPStan) | M9 75 -> 81/259; M10 84 -> 77; M15 27 -> 26/46; M6 stays 0/156; PHPStan lib 1015 -> 1013 errors | Added `CreateTagUseCase`, `UpdateTagUseCase`, `DeleteTagUseCase`, `DeleteTagsUseCase`, `GetTagUseCase` and `SearchTagsUseCase` (Command in, Result out, ports only) with the `create.tag`/`edit.tag`/`delete.tag`/`delete.tag.selection` events emitted through `EventEmitter` using untranslated keys. `TagException` gained the stable `NAME_REQUIRED` code. The seven Tag controllers now use `#[Action]` + `ActionResponse` and are thin; `JsonTrait`, `JsonMessage`, `TagSaveBase`, `TagForm` and the static `Acl::getActionRoute()` calls are gone from the area. Fixed the Tag repository update/delete queries (Aura SQL Query has no `limit()` on them) and the batch delete now removes the custom fields of the deleted ids. Added `TagTest` (8 HTTP IT cases) and `TagUseCasesTest` (13 unit cases with `InMemoryTagRepository` and `InMemoryEventEmitter`).


| 2026-10-27 | P10-1 Domain tests use fakes, not Infrastructure (verified: five migrated tests, core unit 2052, modules 104, architecture 14, PSR2 no new errors) | M14 5 → 0; M1 23 → 21; M2 13 → 11; M17 stays 11; M6 0/157 → 0/158 ports | Added `DatabaseUtilInterface` and the `DatabaseTables` constant holder in `Domain\Database`, and moved `DatabaseConnectionData` to `Domain\Database\Dtos` with a primitive `build()` factory so Domain no longer imports the Install adapter. `BackupFile` and `MysqlSetup` now take the port; `CoreDefinitions` binds it. `XmlFile` reads through `FileHandlerInterface::readToString()` and parses with `loadXML()`, so the new `InMemoryFileHandler` stub can back the tests without a filesystem. `FileBackupServiceTest`, `XmlFileTest`, `AccountCacheTest`, `MySQLTest` and `InstallerTest` now import only Domain ports/DTOs/stubs. Pruned the architecture allowlist by three and lowered the `domain-only` ratchet from 35 to 32. |

| 2026-10-27 | P6-W2 small CRUD areas (verified: core unit 2077, modules 104, architecture + wiring 15, PSR2, PHPStan) | M9 81 -> 105/259; M10 77 -> 49; M6 stays 0/158; PHPStan lib 1012 -> 1009 errors | Added 24 use cases (`Create`, `Update`, `Delete`, `DeleteBatch`, `Get`, `Search`) for `ItemPreset`, `CustomField`, `UserGroup` and `UserProfile`, with Command/Result DTOs and events emitted through `EventEmitter` using untranslated keys. Converted the 28 legacy controllers to `#[Action]` + `ActionResponse` and removed `JsonTrait`/`JsonMessage` from the four areas; the view bases read through the `Get` use cases. `UserGroup`/`UserProfile` delete use cases now remove the custom fields of the deleted ids (the old batch path passed `null`). Added four unit test classes (25 cases) with mocked service ports and `InMemoryEventEmitter`. |
| 2026-10-27 | P6-W3 remaining untested areas (verified: core unit 2108, modules integration 152, architecture + wiring, PSR2, PHPStan) | M9 105 -> 151/259; M10 49 -> 16; M15 26 -> 16/46; M8 89; M6 0/158; PHPStan lib 1009 -> 1009 | Added 25 use cases with Command/Result DTOs and use-case events for `Eventlog` (clear/search), `Track` (clear/unlock/search), `Plugin` (toggle/reset/delete single and batch/search), `Notification` (create/update/delete single and batch/check/get/search), `PublicLink` (create/create from account/delete single and batch/refresh/get/search) and `UserPassReset` (request/reset). The eight areas' legacy controllers now return `#[Action]` + `ActionResponse`; `Status`, `Items`, `Resource` and `Error` were converted to `ActionResponse`/`CALLBACK` without new use cases. Added unit test classes for the new use cases (31 cases) and one integration test class per area (10 areas, 44 cases). Fixed latent breakage found by the new tests: static `Acl` calls in five grids, `getIsAdminApp()`/`getIsAdmin()` mismatches, the stale `NotificationForm` model construction, missing `Controller` class suffixes (`SaveCreateController`, `ViewController`, `CheckNoticesController`), missing `statusAction()`, `module.php` minify bindings (`autowire` -> `get`), missing `error/index` template/missing `pluginInfo` guards, and case-sensitive `userPassReset` view path. |
| 2026-10-29 | P7-2 API response adapter (verified: API IT 119, web+API 274, core unit 2108 except one pre-existing architecture failure, adapter unit 5, PSR2, PHPStan no new adapter errors) | M11 stays 0/46 (per-resource controllers still use legacy action methods until P7-3); other metrics unchanged | Added `SP\Modules\Api\Adapters\ActionResponseAdapter` with JSON-RPC and REST formatting, wired through `ControllerBase` and `Bootstrap`. Migrated all 29 `ApiResponse::makeSuccess()` call sites to `ActionResponse::ok()`, preserved the legacy JSON-RPC result shape (`itemId`, `result`, `resultCode`, `resultMessage`, `count`), and added an optional `itemId` to `ActionResponse`. No `ApiResponse` or `JsonRpcResponseFormatter` reference remains in `app/modules/api`. The architecture run still reports the pre-existing `BackupFile => __u() x6` violation introduced by `a21b5d1e` (allowlist entry is x5). |
| 2026-10-30 | P9-2 remove `SP\Core\Application` (verified: architecture + wiring 15/15, core unit 2103, module unit 19, Resource/Status controller IT 4, PHPStan level 0 no new arity errors) | M4 stays 0; module references to `SP\Core\Application` 174 files / 202 references -> 0; no metric regression (M1 21, M3 13, M6 0/152, M13 0) | Deleted `lib/SP/Core/Application.php`. Added two immutable parameter objects instead of forwarding the same three deps everywhere: `SP\Core\ModuleDependencies` (config, context, event dispatcher) for `ModuleBase`, `HttpModuleBase` and the web/API/CLI `Init`, and `SP\Modules\Api\Controllers\ApiControllerHelper` for `ApiControllerBase` and its 8 bases. Both are `final readonly`, expose only typed ports and hold no container. Web `ControllerBase`/`SimpleControllerBase` read the deps from `WebControllerHelper`/`SimpleControllerHelper` (the existing web aggregate; `SessionContext` is bound to the web `Context` instance). `FormBase` and `HelperBase` keep explicit `ConfigFileService` + `Context` (+ `EventDispatcherInterface` for helpers) because each class uses them directly. `UnitaryTestCase` no longer builds an `Application`; tests read `$this->config` / `$this->eventDispatcherStub`. Verified: 36/36 API controllers resolve from the container. Full module integration suite still blocked locally by missing `DB_*` environment variables. |
| 2026-10-31 | P10-4 coverage gates per layer (verified: core unit 2098 tests / 28245 assertions with coverage, gate passes at the declared targets, gate fails when a threshold is raised) | Use cases 91.11% -> gate 90%; Domain services 96.39% -> gate 95%; Domain 89.37%, Core 62.35%, Infrastructure 92.42%, total 53.38% recorded in section 9 | Added `scripts/coverage-gate.php` (Clover parser and per-layer report/gate) and `tests/coverage-thresholds.php` (use case and domain service minimums). The CI unitary job runs `php scripts/coverage-gate.php --gate` after the core unit suite. Only `Domain\*\UseCases` and `Domain\*\Services` are gated, as agreed in the issue; the other layers are reported for visibility. |

### Baseline test results (P0-1)

Environment: PHP 8.2.12 (`php-testing-composer:2.6` + gd/FreeType, `font-freefont`, `mariadb-client`, `iproute2`), Composer 2.7.6, MariaDB 10.3. Commands were run in a Docker container with the worktree mounted at `/app` and `DB_SERVER`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_PORT` set.

| Suite | Command | Tests | Assertions | Result | Warnings / deprecations / notices / incomplete |
|---|---|---|---|---|---|
| Core unit | `phpunit -c tests/phpunit.xml --group unitary --testsuite core` | 1978 | 27799 | 0 failures, 0 errors | 24 / 14 / 1 / 7 |
| Core integration | `phpunit -c tests/phpunit.xml --testsuite core` | 1993 | 27828 | 0 failures, 0 errors | 24 / 14 / 1 / 7 |
| Web modules | `phpunit -c tests/phpunit.xml --testsuite modules` | 96 | 199 | 0 failures, 0 errors, 1 skipped | 23 / 22 / 1 / 0 |

Api and Cli remain excluded from `tests/phpunit.xml`. Baseline runs outside the suite show:
- Api: database fixtures fail because the `mysql` CLI cannot read `vfs://test/res/datasets/truncate.sql`. Fix before P7-1.
- Cli: 3 errors, `Undefined constant "SP\Tests\APP_DEFINITIONS_FILE"`. Fix before P8-1.

### Coverage per layer (P10-4)

Unit suite (`XDEBUG_MODE=coverage --group unitary --testsuite core`), statements only, refreshed after the P9 cleanup:

| Layer | Statements | Covered | Percent | Minimum |
|---|---|---|---|---|
| `lib/SP/Domain/*/UseCases` | 731 | 666 | 91.11% | 90.00% |
| `lib/SP/Domain/*/Services` | 4350 | 4193 | 96.39% | 95.00% |
| `lib/SP/Domain` | 7561 | 6757 | 89.37% | - |
| `lib/SP/Core` | 1320 | 823 | 62.35% | - |
| `lib/SP/Infrastructure` | 3482 | 3218 | 92.42% | - |
| `app/modules/web` | 7991 | 81 | 1.01% | - |
| `app/modules/api` | 0 | 0 | 0.00% | - |
| `app/modules/cli` | 0 | 0 | 0.00% | - |
| **Total** | **20394** | **10886** | **53.38%** | - |

`scripts/coverage-gate.php` reads `tests/_output/coverage-clover.xml`, aggregates statements per layer and enforces the minimums declared in `tests/coverage-thresholds.php`. The thresholds ratchet the current baseline for use cases (91.11%) and domain services (96.39%) down to 90% and 95%, leaving headroom for refactors. Report only: `php scripts/coverage-gate.php`. Gate: `php scripts/coverage-gate.php --gate`. CI runs the gate in the unitary job after the core unit suite.

The web modules suite covers `app/modules/web` separately (`~47.80%` in the P0-1 baseline); the unit suite intentionally reports it near zero and it is not gated.

### Baseline fixes and quarantines (P0-1)

Fixes:
- `tests/SP/bootstrap.php` seeds `tests/res/config/config.xml` into the vfs `app/config`, fixing `DefinitionsTest`.
- `Validator::isRegex()` passes an empty subject instead of `null` (PHP 8.2 TypeError).
- `PasswordValidatorTest` uses the readonly `Password` constructor and the current `validate(PresetInterface, string)` signature.
- `AuthTokenGenerator` defaults to a non-secure action id; secure-action tests mutate it explicitly.
- `UserDataGenerator` defaults the user language to `en_US` (translation assertions were flaky).
- `AccountSearchDataTest` uses the public `UserDto::$id` property.
- `AccountPresetTest` expected arrays remove the current user/user group, matching production.

Q1 — `ConfigManager\IndexControllerTest::index` is skipped. It still expects the pre-`1128bdf4` markup (5 forms under `#tabs-*`); the button/renderer now produces 0 matches. Re-enable and rewrite in P6-W7 after reconciling `feat/code_refactoring`. No GitHub issue was created for this baseline-only quarantine.

### Metrics after P0 (script output)

M1 186, M2 131, M3 96, M4 74, M5 73 files / 348 call sites, M6 69/143, M7 74, M8 127, M9 75/261, M10 84, M11 0/46, M12 3, M13 7 (+2 composition), M14 55, M15 27/46, M16 0.

PHPStan (`composer phpstan-app`) is not runnable on this branch: loading `lib/Base.php` fails with `Undefined constant "APP_PATH"` because `BaseFunctions.php` is required before `APP_PATH` is defined. PHPCS passes on the changed `lib/` file.

Cross-context imports per context (P3-3): **11** — `Account=2 Category=2 Client=2 Html=4 Http=1`. The migration target contexts are clean: `Import=0 Auth=0 Install=0 Export=0`. The remaining entries are the shared `CustomField` transformer in the Account/Category/Client adapters and the Html minifier's `Http` helpers. M17 in `scripts/architecture-metrics.sh` computes this list (business contexts only; ports, DTOs, models, enums and exceptions are contracts, `Common`/`Core` are shared kernels).

The pre-P3-3 baseline by the same definition was **45**: `Import=9 Auth=5 Html=4 Account=4 User=4 Export=3 Category=2 Http=2 Common=2 Client=2 Notification=1 ItemPreset=1 Api=1 Security=1 Install=1 Plugin=1`.
