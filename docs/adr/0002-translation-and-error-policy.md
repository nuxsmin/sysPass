# 0002 - Translation and error policy

Status: Accepted (2026-10-31)
Issue: #2028 (decision #1965, tracking #1976)

## Context

sysPass is multilingual and translates user-facing text with gettext. Before the hexagonal migration, domain code produced user-facing strings directly: it wrapped them with `__u()`, a marker that returns its input unchanged, and the outer layers decided when to call `__()`. The global `processException()` helper also translated exception messages while logging them. That made the Domain depend on presentation policy and on the process-wide locale configured by an interface layer.

The target architecture (ADR-0001) forbids Domain code from calling `__()` or `processException()`, and it allows `__u()` only because it is the extraction marker. The migration therefore needs an explicit answer to two questions:

- Where does a user-facing message become translated text?
- How does a domain failure carry a stable identity across the module boundary, including the API?

Decision D1 (#1965) selected option (b): domain exceptions carry a stable code and an untranslated message key, and modules translate them. Option (a), keeping `__u()` as a stopgap marker, remains available for contexts that are too large to migrate in one step.

## Decision

### Domain exceptions

Domain code signals a failure by throwing a context exception that extends `SP\Domain\Core\Exceptions\SPException`. The exception carries:

- **A stable integer code** declared as a class constant on the context exception. Codes are unique within the context, are part of the observable contract (the API exposes them), and are never reused or renumbered. The first migrated context, `Tag`, reserves `1001` to `1003`; each new context reserves a disjoint range so codes stay unambiguous where they cross the boundary.
- **An untranslated English message key** passed as the exception message. The key is the gettext `msgid`; it must remain identical to the string the context previously passed to `__u()` so existing catalogs and message snapshot assertions keep working.
- **A severity type** from `SPException` (`CRITICAL`, `WARNING`, `ERROR`, `INFO`, mirrored by `SP\Domain\Core\Exceptions\Type`). The type describes how the interface should present and log the failure, not the HTTP status.
- **An optional hint** for technical detail. Hints are diagnostic data, not user-facing copy; the API exposes them under `data.help` and they are not required to be translatable.

Exceptions are created through named static constructors such as `TagException::notFound()` rather than by passing a code and message at every throw site. The constructor keeps the code/message pair in one place, makes the failure vocabulary discoverable, and lets unit tests assert the code without duplicating magic numbers.

Use cases may throw the same context exceptions. They validate and enforce domain rules; they do not translate, log or build responses.

### Translation at the boundary

Translation is the responsibility of the driving adapter that renders the failure:

- **Web** — `SP\Modules\Web\Bootstrap::translateResponse()` (`app/modules/web/Bootstrap.php:189`) applies `__()` to a string `ActionResponse` subject and to string entries of an array subject. Controllers therefore return raw message keys, including when they catch a domain exception and pass `$e->getMessage()` to `ActionResponse::error()`.
- **API** — `SP\Modules\Api\Adapters\ActionResponseAdapter` translates the message with `__()` in `toJsonRpcError()` (`app/modules/api/Adapters/ActionResponseAdapter.php:88`) and in the result envelope (`app/modules/api/Adapters/ActionResponseAdapter.php:113`). `toJsonRpcException()` forwards the exception code unchanged as the JSON-RPC `error.code` (`app/modules/api/Adapters/ActionResponseAdapter.php:79`), so the domain code is the application-level error identity. JSON-RPC protocol failures use the adapter's reserved `-327xx` constants, not domain codes.
- **CLI** — commands translate exception messages with `__()` when writing to the console (`app/modules/cli/Commands/InstallCommand.php:185`). The logger receives the untranslated message.
- **Logging** — `SP\processException()` (`lib/BaseFunctions.php:161`) applies `__()` when formatting the log entry and also logs the previous exception. It remains a module/Core logging helper; Domain code never calls it.

The rule is single translation: the layer that renders the message calls `__()` once. Domain code and DTOs carry keys, never translated text. A boundary must not translate a value that is already translated.

### Migration rule

Contexts migrate one at a time. A migration:

1. Adds the context exception with named constructors, stable codes and the existing message strings.
2. Replaces `__u()` calls in that context's Domain code with those exceptions and updates the ports' `@throws` contracts.
3. Updates the controllers to catch the context exception and return its key through `ActionResponse`.
4. Keeps message strings unchanged, so user-visible text and translation catalogs do not regress.
5. Adds unit assertions for the code and the raw key, plus a boundary assertion for the translated message.

`__u()` in Domain code is a temporary stopgap under this rule. It is allowed by the architecture test because it is a marker, but the M5 metric tracks it and migrated contexts must remove it. No new Domain code should add `__u()`.

## Considered options

- **Option (a) only: keep `__u()` in the Domain and translate later.** Rejected as the end state. It keeps a presentation global in the core, makes domain tests depend on the process locale, and leaves the translation point implicit.
- **Inject a translator port into the Domain and translate there.** Rejected. It adds a port for a presentation concern, couples the core to rendering policy, and prevents interfaces from choosing different presentation (for example, the JSON-RPC API may need the raw key for clients or logs).
- **Throw generic exceptions with only a message.** Rejected. It loses typed catches, stable API codes and the ability to distinguish not-found from validation or persistence failures without string matching.
- **Keep `processException()` as the universal renderer.** Rejected. It logs and translates; it does not build web, API or CLI responses, and it existed only because the Domain knew about presentation.
- **Translate domain messages eagerly and pass the translated text upward.** Rejected. Translation would happen before the boundary knows the target locale or response format, and it would freeze text at throw time.

## Consequences

- Domain unit tests assert exception type, stable code and raw message key. Interface tests assert the rendered/translated text and the exposed code. The API `TagControllerTest` assertions are the first user-visible snapshot for this policy.
- Message keys are part of the translation catalog contract. Changing a key requires updating the catalog and any snapshot test; changing a code is a breaking API change.
- New contexts must reserve a disjoint code range and document their codes on the context exception.
- `__u()` remains technically legal in the Domain while the migration is incomplete, but adding it to migrated code is a regression. M5 should keep shrinking toward zero.
- `processException()` remains at the boundary for logging only; it must not reappear in `lib/SP/Domain`.
- Errors that originate in Infrastructure or Core and cross into the Domain must be mapped to a Domain exception (or a context exception) before the module sees them; the module translates only what the Domain exposes.

## References

- ADR-0001: target architecture and dependency rules, rule 3 (`domain-presentation-calls`).
- Plan: `.kilo/plans/hexagonal-migration.md`, decision D1, P4-2 and P11-2.
- Decision: #1965. This ADR: #2028. Tracking: #1976.
- Code: `lib/SP/Domain/Core/Exceptions/SPException.php:36`, `lib/SP/Domain/Tag/Exceptions/TagException.php:38`, `app/modules/web/Bootstrap.php:189`, `app/modules/api/Adapters/ActionResponseAdapter.php:79`, `lib/BaseFunctions.php:161`, `app/modules/cli/Commands/InstallCommand.php:185`.
