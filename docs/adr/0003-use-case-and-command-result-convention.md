# 0003 - Use case and Command/Result convention

Status: Accepted (2026-10-31)
Issue: #2040 (plan P11-3, tracking #1976)

## Context

Before the hexagonal migration, controllers and concrete Domain services shared the work of one user intent. Controllers read the request, applied business rules, called static helpers, built audit events and produced response objects. The same behaviour was reached from web, API and CLI through different code paths, so validation, events and error handling could drift between interfaces.

The migration introduced use cases as the application entry point and moved request parsing and response mapping to the driving adapters. Decisions P5-1 (#2026), P5-2 (#2032) and P5-3 (#2037) fixed the location, the DTO shape, the single `execute()` contract and the event-emission convention. This ADR records that convention as the rule for new work and for the remaining migrated areas.

## Decision

### Location and naming

- A use case handles one user intent and lives in `SP\Domain\<Ctx>\UseCases\<Action>UseCase`, for example `SP\Domain\Tag\UseCases\CreateTagUseCase`.
- Its input and output live in `SP\Domain\<Ctx>\UseCases\Dtos` and are named `<Action><Ctx>Command` and `<Action><Ctx>Result`, for example `CreateTagCommand` and `CreateTagResult`. Batch intents use the plural action (`DeleteTagsCommand`).
- Every intent has its own Command. A Result is returned even when it only echoes an id, the deleted ids or an empty search, so the return shape stays explicit.

### Command and Result DTOs

- A DTO is a `final readonly class` with public promoted properties. It does not extend a base DTO, use reflection hydration or add getters.
- Commands implement `SP\Domain\Common\Dtos\Command`; Results implement `SP\Domain\Common\Dtos\Result`. The interfaces are marker contracts, not behaviour.
- Commands carry already parsed primitives, value objects or Domain DTOs. They do not contain HTTP request objects, session values, presentation types, translated text or logging concerns. Validation belongs to the use case, not to the Command constructor.
- Results carry Domain models, Domain DTOs, primitives and result metadata such as a total count. They do not carry translated text, `ActionResponse` or any other module type.
- Arrays of Domain objects are documented with a typed `@param`/`@return` docblock. Public properties remain the only accessors.

### Use case contract

- A use case is a `final readonly class` implementing `SP\Domain\Common\Ports\UseCase`, with the generic pair documented as `@implements UseCase<TCommand, TResult>`.
- The contract has exactly one public method, `execute(Command $command): Result` (`lib/SP/Domain/Common/Ports/UseCase.php:43`). Implementations narrow the return type to their Result.
- Because PHP cannot narrow the parameter through the generic declaration, `execute()` checks `instanceof` for its Command and throws `LogicException` when the wrong Command is passed. That is a programming error, not a domain failure.
- Dependencies are constructor-injected ports (interfaces). A use case must not use the container, `SP\Core\Application`, concrete Infrastructure classes, static Domain services or interface-layer helpers.
- The use case orchestrates domain objects and ports: it validates domain rules, calls repositories and Domain services through ports, and throws Domain exceptions. It does not use HTTP, session, translation, logging or presentation APIs.
- A use case does not build responses and does not know which adapter called it.

### Events

- Audit and notification events are emitted by the use case through `SP\Domain\Common\Ports\EventEmitter` (`lib/SP/Domain/Common/Ports/EventEmitter.php:49`), after the state change succeeded and before the Result is returned.
- The event name is the existing name (`create.tag`, `delete.tag.selection`, and so on). The source is the use case instance, so receivers can detect a `Throwable` without knowing the controller.
- Message descriptions and detail keys are untranslated message keys, per ADR-0002. The receiver translates them when it writes the event log, mail or notification. A use case never calls `__()` or `__u()`.
- Passing `null` as the message reproduces a message-less legacy event. Receiver extras such as `userId`, `userProfileId`, `userGroupId` and `notify` stay on the `EventMessage`.
- No event is emitted when the operation fails.

### Failures at the boundary

- A use case signals failure with a context exception that extends `SP\Domain\Core\Exceptions\SPException`, using a stable code and an untranslated message key (ADR-0002).
- The driving adapter catches the exception, translates the key once and maps it to its own response format. Domain code never renders or translates a response.
- Infrastructure failures are mapped to Domain exceptions before they cross the use case boundary.

### Driving adapters

- A controller parses the request, builds a Command, calls `execute()` once, maps the Result to `ActionResponse` and lets the module adapter translate or serialise it.
- Controllers contain no business rules, no direct repository calls and no `EventMessage` construction. This keeps web, API and CLI on the same application behaviour.

### Tests

- Use cases are tested as unit tests with in-memory port fakes under `tests/SP/Stubs/`, without Infrastructure or a database.
- `tests/SP/Domain/Tag/UseCases/Dtos/TagUseCaseDtosTest.php:85` guards the DTO shape (final, readonly, promoted public properties, right marker contract).
- `tests/SP/Domain/Common/Ports/UseCaseContractTest.php:52` guards the single-entry-point contract and the Domain boundary of the interface.
- `tests/SP/Domain/Tag/UseCases/TagUseCasesTest.php:263` guards that Tag use cases depend on ports only and asserts their events.
- `tests/SP/Domain/Common/Ports/UseCaseEventEmissionTest.php:57` guards the event convention, including untranslated messages and message-less events.

## Considered options

- **Keep business logic in controllers or concrete Domain services.** Rejected: it duplicates behaviour across web, API and CLI, keeps static coupling and makes the use case untestable without the interface layer.
- **Pass associative arrays or generic payloads instead of DTOs.** Rejected: it loses type information and documentation, and makes Command/Result changes invisible to static analysis.
- **Use a base DTO with reflection hydration and getters.** Rejected: it adds machinery without adding meaning. `final readonly` promoted properties are the smallest shape that carries the data.
- **Expose several public methods on a use case.** Rejected: one class per intent keeps the contract discoverable and lets the container and tests treat use cases uniformly. Shared logic belongs in a port, Domain service or collaborator, not in extra public entry points.
- **Return `void`, `null`, a scalar or a raw model.** Rejected: callers would guess the outcome shape. A Result keeps ids, counts and models explicit and gives the adapter one mapping target.
- **Emit events from controllers.** Rejected: it splits one operation between the use case and the adapter, and it lets one interface omit an audit event that another interface emits.
- **Translate or log inside the use case.** Rejected: it makes the core depend on presentation policy and the process locale, contrary to ADR-0001 and ADR-0002.
- **Use a separate method name per Command type.** Rejected: it breaks the single-entry-point contract and forces callers to know implementation details.

## Consequences

- Each intent adds a use case, a Command and a Result. The extra classes are the price of an explicit application boundary; they stay small because they only carry data.
- PHP generics are documentation and static-analysis hints only. The runtime `instanceof` guard is mandatory and is part of the convention.
- Adding an intent means adding its DTO pair, its use case test and its adapter mapping. The architecture test already forbids reaching around the use case from a module.
- Events, exception codes and untranslated message keys become part of the use case's observable contract and are tested there.
- A use case can be exercised with in-memory fakes, so its behaviour is verified without HTTP, database or translation setup.
- The adapter owns parsing, translation and response shape. Changing the web response does not change the use case, and adding an API caller does not duplicate business rules.

## References

- ADR-0001: target architecture and dependency rules.
- ADR-0002: translation and error policy.
- Plan: `.kilo/plans/hexagonal-migration.md`, phase 5 and the slice template in section 6.
- Decisions: #1966 (D2, use case location), #2026 (P5-1), #2032 (P5-2), #2037 (P5-3). This ADR: #2040. Tracking: #1976.
- Code: `lib/SP/Domain/Common/Ports/UseCase.php:43`, `lib/SP/Domain/Common/Dtos/Command.php:37`, `lib/SP/Domain/Common/Dtos/Result.php:35`, `lib/SP/Domain/Common/Ports/EventEmitter.php:49`, `lib/SP/Domain/Tag/UseCases/CreateTagUseCase.php:45`.
- Tests: `tests/SP/Domain/Tag/UseCases/Dtos/TagUseCaseDtosTest.php:85`, `tests/SP/Domain/Common/Ports/UseCaseContractTest.php:52`, `tests/SP/Domain/Tag/UseCases/TagUseCasesTest.php:263`, `tests/SP/Domain/Common/Ports/UseCaseEventEmissionTest.php:57`.
