# 0001 - Target architecture and dependency rules

Status: Accepted (2026-10-31)
Issue: #2021 (tracking #1976)

## Context

sysPass started as a layered application in which `lib/` mixed domain logic, infrastructure adapters and presentation code, while `app/modules/{web,api,cli}` reached into concrete services and global helpers. The migration described in `.kilo/plans/hexagonal-migration.md` turned `lib/` into the core and the modules into driving adapters, and `tests/SP/Architecture/LayerDependencyTest.php` now enforces the result as a hard rule rather than a shrinking allowlist.

This ADR records the target architecture as it stands after the migration: the layers, what each layer may depend on, and the rules the architecture test enforces. The original plan section 1.2 listed six rules against the pre-migration baseline; this ADR keeps their intent and splits them into eight enforceable rules that map one-to-one to `LayerRules::RULES`, plus the container wiring invariant that no static rule can express.

## Decision

### Layers and allowed dependencies

| Layer (namespace / path) | Responsibility | May depend on | Must not depend on |
|---|---|---|---|
| **Domain** `SP\Domain\<Ctx>\` (`lib/SP/Domain`) | Models, value objects, enums, domain services, use cases, ports, domain exceptions | Domain, PHP stdlib | Infrastructure, Core, Modules, Util, presentation globals (`__()`, `processException()`) |
| **Use cases** `SP\Domain\<Ctx>\UseCases\` | Orchestrates ports and domain services for one user intent; takes a Command DTO and returns a Result DTO | Domain | Controllers, HTTP, session, DI container, presentation |
| **Ports** `SP\Domain\<Ctx>\Ports\` | Interfaces the core needs from outside (persistence, crypto, file, mail, session, clock) and contracts the outside calls (use cases) | Domain | Infrastructure types |
| **Infrastructure** `SP\Infrastructure\` (`lib/SP/Infrastructure`) | Driven adapters: repositories, database, filesystem, LDAP, SMTP, crypto implementations; implements Domain ports | Domain (ports, models, DTOs, exceptions), vendor libraries | Core, Modules, Util |
| **Core** `SP\Core\` (`lib/SP/Core`) | Composition root and framework glue: bootstrap, DI definitions, session and context implementations, event dispatcher adapter, logging adapter | Domain, Infrastructure (composition root only), vendor libraries | Modules, except the documented controller-resolution convention |
| **Modules** `SP\Modules\{Web,Api,Cli}\` (`app/modules`) | Driving adapters: HTTP, JSON-RPC and CLI request mapping, response mapping, view and theme rendering, presentation helpers | Domain ports, models, DTOs, use cases, domain exceptions; Core bootstrap contracts | Infrastructure outside their own bootstrap files, concrete Domain services (including static calls), presentation globals in Domain code |
| **Presentation** `SP\Modules\Web\{Mvc,Html}\` (`app/modules/web`) | Templates, DataGrid, icons, select items and view helpers | Domain ports and models; module code | Domain concrete services, Infrastructure |

### Hard rules

The first six rules are enforced by `tests/SP/Architecture/LayerDependencyTest.php` through `LayerRules`. Rule 7 is enforced by the same test for imports and by container wiring for resolution. Rule 8 is enforced by `tests/SP/Core/Definitions/ContainerWiringTest.php`.

1. **Domain imports only Domain.** No `SP\Infrastructure`, `SP\Core`, `SP\Modules` or `SP\Util` import may appear in `lib/SP/Domain`. Rule key: `domain-only`.
2. **Domain does not use the service locator.** No Domain file references `SP\Core\Application`. Rule key: `domain-application`.
3. **Domain does not produce presentation.** Domain code throws exceptions with stable codes and untranslated message keys; it never calls `__()` or `processException()`. `__u()` is allowed because it is the gettext extraction marker and returns the untranslated key. Rule key: `domain-presentation-calls`.
4. **Infrastructure imports only Domain and vendor code.** No `SP\Core`, `SP\Modules` or `SP\Util` import may appear in `lib/SP/Infrastructure`. Rule key: `infrastructure-only`.
5. **Modules are driving adapters.** Modules import Domain ports, models, DTOs, use cases and exceptions. They must not import Infrastructure outside their `module.php` bootstrap file, must not import concrete Domain `Services` classes, and must not call statics on them; exception types are the only allowed `Services` import. Rule keys: `modules-infrastructure`, `modules-concrete-services`.
6. **The core never references the interface layer.** `lib/` must not reference `SP\Modules` except in the composition root `lib/SP/Core/Definitions/`. Rule key: `lib-modules`.
7. **Core uses Infrastructure only from the composition root.** `lib/SP/Core` must not import `SP\Infrastructure` outside `lib/SP/Core/Definitions/`. Rule key: `core-infrastructure`. Every port declared in Domain must also resolve through the container, or be recorded as an intentional non-instantiable contract in `ContainerWiringTest`.
8. **Wiring is explicit.** Each port is bound to its adapter in the composition root (decision D9); there are no wildcard conventions or implicit name-based bindings, and no new `Application` injection anywhere.

## Considered options

- **Keep an allowlist of tolerated violations.** Rejected: an allowlist hides drift and requires manual pruning. The migration ended with zero violations, so the test can be absolute.
- **Enforce only namespaces, not concrete classes or statics.** Rejected: namespace-only rules still let modules call concrete Domain services and their statics directly, which reintroduces hidden coupling without an import violation.
- **Rely on code review for wiring.** Rejected: a port that silently fails to resolve only shows up at runtime. `ContainerWiringTest` makes the binding explicit and fails the build.
- **Keep `SP\Core\Application` as an escape hatch.** Rejected: the service locator was removed (#2050) and its return would undermine every other rule.

## Consequences

- The architecture test is the executable form of this ADR. A new layer violation fails CI without needing an allowlist edit.
- Composition roots (`lib/SP/Core/Definitions/` and `app/modules/*/module.php`) are the only places allowed to know both sides of a port. Changes to adapters are wired there.
- Domain code cannot format user-facing text. Contexts migrate to stable exception codes and message keys one at a time; the module boundary translates them (ADR-002, #2028).
- Adding a port requires a binding and, if it is not container-instantiable, a documented entry in `ContainerWiringTest`.
- Rules 1 to 7 are checked statically. A rule that needs runtime resolution (rule 7's second sentence, rule 8) uses the wiring test instead.

## References

- Plan: `.kilo/plans/hexagonal-migration.md` sections 1 and 5.
- Tests: `tests/SP/Architecture/LayerRules.php`, `tests/SP/Architecture/LayerDependencyTest.php`, `tests/SP/Core/Definitions/ContainerWiringTest.php`.
- Related decisions: ADR-0002 (translation and error policy), ADR-0003 (use case convention), ADR-0004 (test strategy).
