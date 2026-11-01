# sysPass

sysPass is a self-hosted password manager. Web, JSON-RPC and CLI requests are driving adapters over a Domain core, and this glossary fixes the vocabulary of that architecture, the password vault and its tests.

## Language

### Architecture

**Domain**:
The core of the application: models, value objects, context services, use cases, ports and domain exceptions. It knows nothing about HTTP, database implementations, sessions or translation.
_Avoid_: lib, business layer, backend

**Bounded Context**:
A cohesive area of the Domain with its own model and vocabulary, such as Account, Tag, User or Config. Its use cases, ports and exceptions belong to that context and do not leak into another.
_Avoid_: module, subsystem, package

**Domain Model**:
An object that expresses a Domain concept and its rules, such as an Account, Tag or User. It carries no persistence or presentation behaviour.
_Avoid_: entity, bean, record

**Value Object**:
An immutable Domain object defined by its values rather than its identity. It validates its own values and carries no persistence or presentation behaviour.
_Avoid_: DTO, primitive, scalar

**Domain Service**:
A Domain class that holds behaviour shared by several use cases or entities and that does not fit on one model. It is reached through a Port, never by a static call from a module.
_Avoid_: manager, helper, utility

**Repository**:
A Driven Adapter that loads and stores Domain models for a persistence Port. It is not a Domain service.
_Avoid_: DAO, store, database

**Use Case**:
One user intent, expressed as a class that orchestrates domain objects and ports. It has a single `execute()` entry point and does not know which interface called it.
_Avoid_: action, command, service (when meaning a use case)

**Command**:
The input DTO of exactly one use case. It carries parsed primitives, value objects or Domain DTOs, never request objects, session values or translated text.
_Avoid_: request, payload, input array

**Result**:
The output DTO of exactly one use case. It carries Domain models, Domain DTOs, primitives and result metadata, never response objects or translated text.
_Avoid_: response, output array, return value

**DTO**:
A final readonly data carrier with public promoted properties. It has no behaviour, no base DTO, no reflection hydration and no getters.
_Avoid_: value object (a DTO carries no invariants), entity, bean

**Port**:
An interface owned by the Domain that describes what the core needs from the outside, or the contract the outside calls. Implementations live outside the Domain.
_Avoid_: interface, contract (when meaning a port)

**Adapter**:
A class that connects a port to a technology or an interface. Adapters are either driving (they call the core) or driven (the core calls them).
_Avoid_: implementation, driver, gateway

**Driving Adapter**:
An adapter that turns an external request into a use case call and maps the Result back. The Web, API and CLI modules are driving adapters.
_Avoid_: controller, entry point (a controller is one part of a driving adapter)

**Driven Adapter**:
An adapter that implements a port the core calls, such as a repository, mailer, clock or crypto implementation.
_Avoid_: repository (only one kind), provider, backend

**Infrastructure**:
The layer of driven adapters: repositories, database access, filesystem, LDAP, SMTP and crypto implementations. It implements Domain ports and depends only on the Domain and vendor libraries.
_Avoid_: persistence layer, data layer

**Core**:
The composition root and framework glue: bootstrap, dependency-injection definitions, session and context implementations, event and logging adapters.
_Avoid_: Domain, application, kernel

**Composition Root**:
The single place allowed to know both sides of a port: the dependency-injection definitions and each module bootstrap. It binds ports to adapters explicitly.
_Avoid_: container, bootstrap (the bootstrap is one composition root)

**Dependency Injection Container**:
The runtime that reads the Composition Root definitions and resolves use cases, adapters and framework services. It is not a Service Locator: classes receive their dependencies through the constructor.
_Avoid_: service locator, registry, Application

**Bootstrap**:
The module entry sequence that builds the Dependency Injection Container, registers the module, and dispatches the request or command. Each module has one Entry Point that starts it.
_Avoid_: front controller, init, startup

**Controller**:
The part of a driving adapter that parses a request, builds a Command, calls one use case and maps the Result to an Action Response. It holds no business rules.
_Avoid_: action (an Action is the addressable behaviour, a Controller is the code that handles it), handler

**Layer Rule**:
One enforceable dependency restriction of the target architecture, identified by a stable rule key such as `domain-only` or `modules-concrete-services`. The Architecture Test checks every rule.
_Avoid_: guideline, convention, policy

**Ratchet**:
A limit that may only move in the direction of stricter compliance, such as a shrinking Allowlist or a rising coverage threshold. It makes progress irreversible without a documented reason.
_Avoid_: target, quota

**Module**:
One of the driving adapters `Web`, `Api` or `Cli`. A Module maps requests and responses to use cases and may render presentation, but contains no business rules.
_Avoid_: context, plugin, component

**Presentation**:
The rendering concern of the Web module: templates, DataGrid, icons, select items and view helpers. It depends on Domain ports and models, never on concrete Domain services or Infrastructure.
_Avoid_: view layer, frontend, theme

**Presentation Global**:
A process-wide helper that formats or translates user-facing text, such as `__()`, `__u()` or `processException()`. Domain code must not call a Presentation Global.
_Avoid_: helper, util, translation function

**Service Locator**:
A process-wide registry that resolves dependencies on demand and hides them from the constructor. `SP\Core\Application` was the service locator and has been removed; it must not return.
_Avoid_: container, dependency injection

**Architecture Test**:
The automated check that enforces the layer dependency rules and the container wiring rules. It is a hard rule, not a review convention.
_Avoid_: lint, static analysis

**Allowlist**:
An explicit list of tolerated architecture violations, used while the migration was incomplete. It is now empty; new violations fail the Architecture Test instead of extending it.
_Avoid_: baseline, exceptions list, ignore list

**Container Wiring**:
The invariant that every Domain port resolves through the container to an adapter, or is recorded as an intentional non-instantiable contract.
_Avoid_: autowiring, binding convention

**Architecture Metric**:
A counted property of the codebase reported by the metrics script, such as the number of `__u()` calls or of Domain tests that import Infrastructure. Each metric has a target and a ratchet.
_Avoid_: KPI, score

**Coverage Gate**:
The CI check that compares per-layer statement coverage with the thresholds in the coverage configuration. Only use cases and Domain services are gated today.
_Avoid_: coverage report, quality gate

### Errors and events

**Base Domain Exception**:
The common Domain exception that every context exception extends. It carries the severity type, the code, the optional hint and the previous exception.
_Avoid_: SPException, base error

**Domain Exception**:
A context exception that extends the base Domain exception and signals a domain failure with a Stable Code, an untranslated Message Key, a Severity Type and an optional Hint. It is created through named constructors.
_Avoid_: error, generic exception, runtime exception

**Stable Code**:
An integer constant on a context exception that identifies the failure. It is unique within the context, exposed by the API and never reused or renumbered.
_Avoid_: error number, status code, HTTP code

**Message Key**:
The untranslated English gettext `msgid` carried by a Domain exception, event or DTO. The Translation Boundary translates it exactly once.
_Avoid_: message, text, label (when meaning the key)

**Gettext**:
The translation library and file format that map Message Keys to localized text. Only the Translation Boundary reads the active locale.
_Avoid_: i18n, translations

**Locale**:
The language selected for the current request and used by the Translation Boundary. Domain code never reads it.
_Avoid_: language, culture

**Logger**:
The boundary adapter that records exceptions, messages and context for diagnostics. It receives untranslated Message Keys and never renders user-facing copy.
_Avoid_: error log, debug output

**Translation Boundary**:
The driving adapter that renders a failure or event: Web, API or CLI. It applies `__()` once and maps the result to its own response format.
_Avoid_: translator, locale, i18n layer

**Hint**:
Optional technical detail attached to a Domain exception. It is diagnostic data, not user-facing copy, and it need not be translatable.
_Avoid_: help text, description, detail

**Severity Type**:
The `CRITICAL`, `WARNING`, `ERROR` or `INFO` classification of a Domain exception. It describes how the interface should present and log the failure, not an HTTP status.
_Avoid_: level, priority, status

**Extraction Marker**:
The `__u()` helper, which returns its input unchanged so gettext can extract the string. It is allowed in the Domain only as a temporary stopgap; migrated code uses Message Keys instead.
_Avoid_: translation, gettext call

**Translation Catalog**:
The gettext catalog that maps Message Keys to localized text. Changing a key changes the catalog contract.
_Avoid_: language file, translations

**Event**:
An audit or notification fact emitted by a use case after a state change succeeded. Its name is stable, its source is the use case and its messages are Message Keys.
_Avoid_: hook, signal, log entry

**Event Emitter**:
The Domain port through which a use case emits Events. It is injected, never resolved from the container.
_Avoid_: dispatcher, bus, notifier

**Audit Event**:
An Event that records a security-relevant change in the event log.
_Avoid_: log line, history entry

**Notification Event**:
An Event that informs users about a change, by mail or in the application.
_Avoid_: alert, message

**Event Message**:
The payload attached to an Event, carrying an untranslated description, detail keys and receiver extras such as the user, profile, group or notification flag.
_Avoid_: notification, mail body

### Testing

**Unit Test**:
A test of Domain rules, use case orchestration, contract shape or architecture rules. It uses fakes instead of Infrastructure and runs without a database or module bootstrap.
_Avoid_: isolated test, mock test

**Integration Test**:
A test that exercises a real boundary, such as SQL against MariaDB or a request through the module bootstrap.
_Avoid_: functional test, end-to-end test

**DB-backed Test**:
An Integration Test of a repository against the real database engine, using fixtures. It asserts returned models, counts, ordering and persistence side effects.
_Avoid_: repository test (mocked SQL tests are not DB-backed)

**HTTP Integration Test**:
An Integration Test that dispatches an in-process request through the Web bootstrap and asserts the rendered response, covering routing, controller, use case and view.
_Avoid_: browser test, Selenium test

**Adapter Integration Test**:
An Integration Test of the API or CLI module that asserts the JSON-RPC envelope or the console output of a use case.
_Avoid_: API test, command test

**Body Checker**:
The test helper attribute that asserts part of a rendered response body in an HTTP Integration Test.
_Avoid_: assertion helper, response matcher

**Characterisation Test**:
An Integration Test written against existing behaviour before a migration, so it fails if the migration changes the observable contract. Every migrated web area has one per action.
_Avoid_: regression test, snapshot test

**Test Pyramid**:
The deliberate split into many Unit Tests, fewer DB-backed and HTTP Integration Tests, and no browser end-to-end suite.
_Avoid_: test suite, test plan

**Fake**:
An in-memory implementation of a port used by a Unit Test, such as an in-memory repository or event emitter. A fake is behaviourally useful; a mock only records calls.
_Avoid_: mock, stub (a stub returns canned values without behaviour)

**Stub**:
A test double that returns canned values or replaces a dependency the test does not exercise, such as a stubbed configuration or database.
_Avoid_: fake, mock

**Mock**:
A test double that records calls and asserts how it was used. It is not a substitute for a Fake when the test depends on behaviour.
_Avoid_: fake, stub

**Fixture**:
Shared test data loaded before a DB-backed Test so each test starts from a known database state.
_Avoid_: seed data, sample data

**Test Group**:
The mandatory `unitary` or `integration` classification of a test class. It selects which CI job runs the test.
_Avoid_: suite, category

**CI Gating**:
The rule that a change is not verified until both the unitary job and the integration job have run. The integration job is enabled by repository configuration, not per pull request.
_Avoid_: pipeline, build

### Domain

**Item**:
A Domain record with an id and a name that can be listed or selected. The Account Item is the account-list form of an Account.
_Avoid_: entity, object, record

**Account**:
The vault entry that stores a name, login, password, URL, notes and permissions. It is the item users actually manage.
_Avoid_: password, credential, entry

**Account Item**:
The lightweight, list-oriented projection of an Account: id, name, login and edit flag. It is not the full Account.
_Avoid_: account summary, account DTO

**Item Preset**:
A stored set of defaults, restrictions and permissions applied when accounts are created or edited.
_Avoid_: template, default, policy

**Tag**:
A free-form label attached to accounts for filtering and search.
_Avoid_: label, keyword

**Category**:
A hierarchical grouping of accounts.
_Avoid_: folder, group

**Client**:
The customer or organization an account belongs to.
_Avoid_: customer, company

**Custom Field**:
A user-defined field attached to accounts. Its account data is encrypted like vault data.
_Avoid_: extra field, metadata

**User**:
A person who can log in to sysPass and owns or can access accounts according to permissions.
_Avoid_: account (an Account is a vault entry, not a login), operator

**User Group**:
A set of users that share permissions and can own accounts together.
_Avoid_: team, role

**User Profile**:
The permission profile assigned to a user, which determines what the user may do across the application.
_Avoid_: role, permission set

**API Token**:
A per-user credential that authorizes one Action over the API. A user who is not an application administrator may only create or edit a token for their own user and only for an Action they are themselves allowed to perform; an application administrator may manage tokens for any user.
_Avoid_: auth token, authorization (an API Token authenticates a user; authorization is the permission decision), API key

**Access Control List (ACL)**:
The rules that determine which users and groups may view, edit or delete an account.
_Avoid_: permissions (an ACL is the evaluated rule set), rights

**Master Key**:
The secret used to encrypt and decrypt vault data. The Master Key Store holds it for the current session only.
_Avoid_: encryption key, private key, password

**Master Key Store**:
The session-scoped port that stores, returns and clears the Master Key for the current context.
_Avoid_: key cache, session variable

**Master Password**:
The system-wide password that unlocks the Master Key. A user's copy is checked against the system hash at login.
_Avoid_: master key, admin password

**Temporary Master Password**:
A newly generated Master Password delivered out of band when an administrator resets the system secret.
_Avoid_: reset password, temporary key

**Public Link**:
A time-limited, view-limited link that exposes one account without a login.
_Avoid_: share link, public URL

### Interface

**Web Module**:
The driving adapter that serves HTML pages and form or AJAX actions through the Web bootstrap and Presentation helpers.
_Avoid_: frontend, UI, site

**API Module**:
The driving adapter that exposes use cases over JSON-RPC for external clients.
_Avoid_: REST API, backend, web service

**CLI Module**:
The driving adapter that exposes use cases as console commands for installation, upgrade and administration.
_Avoid_: shell, console scripts, daemon

**Route**:
The mapping between a URI and the Action that handles it. A Route is the pairing, not the string and not the code.
_Avoid_: URL, path, endpoint (when meaning the mapping itself)

**Action**:
A unit of application behaviour that is addressable over HTTP, declared by annotating a controller method.
_Avoid_: endpoint, ajax call, service (an Action is not a domain service)

**Route Parameter**:
A named value extracted from a Route's URI and bound to the Action's argument of the same name.
_Avoid_: positional param, path segment (the segment is syntax; the Parameter is the bound value)

**Entry Point**:
The file through which one application module (web, api, cli) receives HTTP requests and bootstraps. Each module has exactly one Entry Point.
_Avoid_: front controller, index

**Base Path**:
The URL prefix under which the installation is served, empty when installed at a domain root.
_Avoid_: web root, subfolder

**Action Response**:
The module-neutral outcome returned by a controller: a success or error subject plus optional data and metadata. Each module adapter translates or serialises it.
_Avoid_: HTTP response, JSON response, view model

**JSON-RPC**:
The request and response protocol used by the API module. Protocol failures use reserved negative codes; domain failures keep their Stable Code.
_Avoid_: REST, API (JSON-RPC is the protocol, the API is the module)
