# Handover: Serialized payload compatibility (#2055)

Decision record: `docs/adr/0005-preserve-serialized-payload-compatibility.md` (Accepted; resolved decisions recorded there).
Branch: `refactor-syspass-to-hexagonal-architecture`. The ADR, this handover, P0.1 (`60fc7056`), T1 (`6131893c`), T2 (`75269d7b`), and T3 (`1d19d25f`) are committed. T4 is committed in `441e88aa`. T5 is committed in `670e58e1` (rewriter grammar) and `2c7db922` (config fallback). T6 is committed in `e6bdff3a`. T7 is committed in `6859ee7f`.

## Goal

Make persisted serialized PHP payloads survive the #2053 namespace move (and future moves) without silent loss or partial decode. Fail closed for durable data; regenerate transient data. No data is ever cleared.

## Resolved decisions

| Gate | Decision |
|---|---|
| Target release | **v4** (`Version::VERSION` `[4, 0, 0]`). The issue label `v3` is stale. |
| Upgrade handler version | New handler uses **`#[UpgradeVersion('4.0.0')]`**. Normalised to `4000.0`; applies to installs stored below 4000 and never to 4.0 installs (verified with `Version::checkVersion`). |
| Upgrade handler registration | **All** handlers carrying `#[UpgradeVersion]` are registered in production, including the existing `UpgradeDatabase` (`400.24210101`). |
| Unreadable durable rows | **No data is cleared** for any payload type. Rows are left byte-for-byte untouched, the type and IDs are logged, and the upgrade fails. Re-running after the cause is fixed is safe. |

## Before you start

Run every PHP command through the Docker runtime. Never call bare `php`, `vendor/bin/*`, or composer scripts that shell out to `php`.

```sh
source .ai-cli/env.sh
"$PHP_BIN" -v
"$PHP_BIN" vendor/bin/phpunit -c ./tests/phpunit.xml --no-coverage
"$PHP_BIN" vendor/bin/phpstan analyse --level 6 lib
```

GitHub operations use the `github_*` MCP tools first. Fall back to `$GH_BIN` (`host-spawn gh`) only if they are unavailable. Mutations go through `.ai-cli/bin/ai-run -- <cmd>` as described in `.ai-cli/README.md`.

Do not add code comments unless asked. Do not commit unless asked.

## Phase 0: Prerequisite defect (upgrade registration)

**P0.1 Upgrade service has no production wiring.** Verified:
- `tests/SP/Core/Definitions/ContainerWiringTest.php:58` documents `UpgradeService` as unresolved because it "needs a runtime handler registry". `DomainDefinitions::getDefinitions()` binds Ports to Services by convention and skips that port.
- No other binding for `UpgradeService` exists in `lib/` or `app/`.
- `Upgrade::registerUpgradeHandler()` is called only from tests, so `Upgrade::upgrade()` (`lib/SP/Domain/Upgrade/Services/Upgrade.php`) iterates an empty list.
- `UpgradeController` (`app/modules/web/Controllers/Upgrade/UpgradeController.php:49`) depends on `UpgradeService`.

Fix:
1. Add a composition-root factory binding for `UpgradeService` in `lib/SP/Core/Definitions/CoreDefinitions.php` that builds `Upgrade` from the container and registers every handler class with `#[UpgradeVersion]`. Use an explicit list of handler classes (`UpgradeDatabase::class` plus the new migration handler), not classpath scanning. The binding lives in the composition root because `app/modules` must not import concrete Domain services (`modules-concrete-services` architecture rule).
2. Bind the orphaned `FileHandlerProvider` port to `LogHandler` so `Upgrade` can be autowired. The port lost its implementation when `FileHandler` was removed in `9fe1ad9a`.
3. Update the documented exclusion in `ContainerWiringTest.php:58` so the test asserts the new binding resolves.
4. Acceptance: a wiring test resolves `UpgradeService` and shows that `UpgradeDatabase` is applied for a stored version below 4000 (`configVersion` / `appVersion`). Run the existing `UpgradeTest` and `UpgradeDatabaseTest` unchanged.

Status: implemented and committed in `60fc7056`. `UpgradeService` resolves, `UpgradeDatabase` is registered, and the wiring test selects it for version `400.0`.

This is a separate defect from #2055. Consider a separate issue, but it blocks T9.

## Phase 1: Foundations (no behaviour change)

**T1 Persisted-class registry and legacy map.**
- Registry of classes whose layout is stored in payloads: Vault, Crypt, ConfigData, DataCollection (if it has private state), ProfileData, UserPreferences, PluginDataStorage, ItemPreset, and any `AuthToken` hydrate target.
- Legacy map: `legacy FQCN => current FQCN`, including per-class private property names. Seed with `SP\Core\Crypt\Vault`, `SP\Core\Crypt\Crypt`, `SP\Core\DataCollection`, `SP\Core\Exceptions\ContextException`, and any other class moved by #2053. Confirm the exact list from `git show --stat 6a6cd3dc`.
- Location: `lib/SP/Domain/Common/Adapters/` (Domain layer, no Infrastructure imports).
- Acceptance: every map value resolves via `class_exists`; no map key resolves via `class_exists`.

Status: implemented and committed in `6131893c`. Files: `lib/SP/Domain/Common/Adapters/PersistedClassRegistry.php`, `lib/SP/Domain/Common/Adapters/LegacyClassMap.php`, `tests/SP/Domain/Common/Adapters/PersistedClassRegistryTest.php`. Decisions taken at approval:
- Registry holds the `#[Hydratable]` targets actually embedded in payloads (Vault, Crypt, ConfigData, ProfileData, UserPreferences, ItemPreset's Password, AccountPrivate, AccountPermission, SessionTimeout). Owner models (ItemPreset, User, UserProfile, PluginData, AuthToken) are not embedded.
- `PluginDataStorage` is excluded: an interface with no implementation in core. Plugin-provided classes cannot be enumerated. T9 plugin-data scope needs a separate decision.
- `DataCollection` and `ContextException` are excluded from the map: `DataCollection` declares no private state and `ArrayObject` serializes only the concrete class; `ContextException` is not serialized. The legacy name for `ContextException` is `SP\Core\Context\ContextException`, not `SP\Core\Exceptions\ContextException`.
- The map is class-level. No per-property entries, because no persisted property was renamed by #2053.
- Verification: new test 5/5 OK; `tests/SP/Architecture` 16/16 OK; `tests/SP/Domain/Common/Adapters` 18/18 OK; PHPStan level 6 on the two new files: no errors.

**T2 Token-level payload rewriter.**
- Walk the serialize grammar (`N;`, `b:`, `i:`, `d:`, `s:N:"..."`, `a:N:{}`, `O:N:"name":N:{}`, `r:`, `R:`) and rewrite only class-name tokens and mangled property keys (`\0Class\0prop`, `\0*\0prop`). Recompute the length prefix of every changed token.
- Do not use regex over the whole payload. `Serde::fixSerialized()` (`lib/SP/Domain/Common/Adapters/Serde.php:87-98`) is regex-based and must not be reused.
- Unknown class names are left untouched so `allowed_classes` rejects them.
- Acceptance: strings containing a legacy class name are unchanged; nested objects are rewritten; references are preserved; malformed input throws; output unserialises to the expected object.

Status: implemented and committed in `75269d7b`. Files: `lib/SP/Domain/Common/Adapters/SerializedPayloadRewriter.php`, `tests/SP/Domain/Common/Adapters/SerializedPayloadRewriterTest.php`, `tests/SP/Stubs/SerializedPayloadFixture.php` (fixture lives in `Stubs` because PHPUnit collects every `.php` under `tests/SP/Domain`). Decisions taken at approval:
- API: `SerializedPayloadRewriter::rewrite(string $payload, array $classMap = LegacyClassMap::MAP): string`. Pure string transform; never calls `unserialize()`.
- Cursor-based walker, not regex. `s:` values are read by declared length, so binary values and text containing legacy names are copied verbatim.
- Rewritten: `O:` class names, `E:` enum class names, and mangled `\0Class\0prop` prefixes in `O:` property lists only. String keys inside `a:` arrays are never rewritten.
- Class-name lookup is case-insensitive. Unmapped names pass through unchanged.
- Accepted tokens: `N`, `b`, `i`, `d` (including `INF`, `NAN`, `-0`, exponent form), `s`, `a`, `O`, `E`, `r`, `R`. `C:` and anything else throw.
- Failures throw `SPException::error` with the byte offset. Counts and lengths must be canonical unsigned integers, and trailing bytes throw.
- Verification: `tests/SP/Domain/Common/Adapters` 48/48 OK (30 new); `tests/SP/Architecture` 16/16 OK; PHPStan level 6 on the three new files: no errors.

**T3 Typed legacy-aware reader.**
- Rewrites with T2, unserialises with `allowed_classes` set to the current target classes only, verifies `instanceof` against the expected type, and throws a domain exception on any mismatch. Never returns `__PHP_Incomplete_Class` or `stdClass`.
- Leave `Serde::deserialize()` behaviour unchanged for existing callers.
- Acceptance: legacy Vault payload returns a usable Vault; corrupt or unmapped payloads throw; no partial objects.

Status: implemented and committed in `1d19d25f`. Files: `lib/SP/Domain/Common/Adapters/SerializedPayloadReader.php`, `tests/SP/Domain/Common/Adapters/SerializedPayloadReaderTest.php`, `tests/SP/Stubs/SerializedPayloadGraphFixture.php`. Decisions taken at approval:
- API: `SerializedPayloadReader::read(string $payload, string $expectedClass, array $allowedClasses = PersistedClassRegistry::CLASSES, array $classMap = LegacyClassMap::MAP): object`. Static adapter, like `Serde` and the rewriter; no container wiring.
- The expected class is always added to the allow-list. The default allow-list is the current persisted-class registry, so a `Vault` payload can instantiate its nested `Crypt`; T9 can pass plugin classes explicitly.
- All failures throw `InvalidClassException` (a domain `SPException`), including rewriter failures, so T4 and T5 catch one type. A rewriter failure keeps its `SPException` as the previous exception.
- Decoded graphs are scanned recursively for `__PHP_Incomplete_Class`, so an unmapped nested class cannot produce a partial object under an otherwise valid target. Objects are tracked with `SplObjectStorage`, and traversal is depth-bounded at 512.
- Top-level `stdClass`, scalars, arrays, and incomplete objects are rejected by the `instanceof` check.
- `Serde::deserialize()` is unchanged; no production caller uses the reader yet.
- Verification: `tests/SP/Domain/Common/Adapters` 60/60 OK (12 new); `tests/SP/Architecture` 16/16 OK; PHPStan level 6 on the new reader: no errors.

## Phase 2: Read-path hardening

Each depends on T3. Independent of each other.

**T4 Secure session regeneration.** `lib/SP/Domain/Crypt/Services/SecureSession.php:87-106`: on `InvalidClassException` from `loadWith()`, log and regenerate via `saveKey()`. Leave the expiry branch unchanged. Test in `tests/SP/Domain/Crypt/Services/SecureSessionServiceTest.php`.
- Acceptance: a legacy session cache produces a new key and rewrites the cache file.

Status: implemented and committed in `441e88aa`. Files: `lib/SP/Domain/Crypt/Services/SecureSession.php`, `lib/SP/Infrastructure/File/FileCache.php`, `tests/SP/Domain/Crypt/Services/SecureSessionServiceTest.php`, `tests/SP/Infrastructure/File/FileCacheTest.php` (new). Decisions taken at approval:
- **Read path routed through T3.** `FileCache::loadWith()` now calls `SerializedPayloadReader::read($raw, $class, PersistedClassRegistry::CLASSES, [])`. The raw `unserialize(..., ['allowed_classes' => [$class]])` made every current-format Vault fail: its nested `Crypt` becomes `__PHP_Incomplete_Class`, which cannot be assigned to the typed `CryptInterface $crypt` property (`TypeError`, not caught by `getKey()`). Verified in the PHP 8.3 runtime. The bug predates #2053 (same `allowed_classes` shape at `6a6cd3dc^`). It affects only installs with `encryptSession` enabled.
- **No remap for caches.** The empty class map means a legacy session throws `InvalidClassException` and regenerates (ADR decision 2), rather than being remapped.
- **Shared method.** `ConfigFile` (T5) also calls `loadWith()`. Current `ConfigData` reads unchanged; a legacy `config.cache` still throws `InvalidClassException`, which is T5's input. T5 is committed (see below).
- **`FileCache::save()` truncates and rewinds.** `readToString()` leaves the handle at EOF, and `FileHandler::write()` neither truncates nor rewinds. Without the fix, a regeneration on the same handle appended to the legacy bytes, and a shorter payload over a longer file left trailing bytes that the rewriter rejects. Both regressions are covered by `FileCacheTest` and fail when the fix is removed.
- `SecureSession::getKey()` catches `InvalidClassException` next to `FileException`, logs a warning, and returns `saveKey()`. The expiry branch is unchanged.
- Verification: `SecureSessionServiceTest`, `FileCacheTest`, `ConfigFileTest`, `tests/SP/Domain/Common/Adapters`, and `tests/SP/Architecture`: 106/106 OK. The unitary group has 2 errors and 1 failure (`SessionTest::testReKey`, `DefinitionsTest::testGetDefinitions`, `UuidCookieTest::testCreate`). The same failures occur on HEAD sources, so they are pre-existing and environmental (UUID cookie creation in the shared run); each passes in isolation. PHPStan level 6 on the changed lib files: no errors.
- Pre-existing deprecation: `FileHandler::autoDetectEOL()` calls `ini_set('auto_detect_line_endings')`, which PHP 8.3 deprecates. Triggered by `readToString()`. Out of T4 scope.

**T5 Config cache fallback.** `lib/SP/Domain/Config/Services/ConfigFile.php:85-102`: catch `InvalidClassException` in `loadFromCache()`, delete the cache, return `null` so `loadFromFile()` runs. Test in `tests/SP/Domain/Config/Services/ConfigFileTest.php`.
- Acceptance: a mismatched `config.cache` no longer aborts initialisation.

Status: implemented and committed in `670e58e1` (rewriter grammar) and `2c7db922` (config fallback). Files: `lib/SP/Domain/Common/Adapters/SerializedPayloadRewriter.php`, `lib/SP/Domain/Config/Services/ConfigFile.php`, `lib/SP/Domain/Storage/Ports/FileCacheService.php`, `tests/SP/Domain/Common/Adapters/SerializedPayloadRewriterTest.php`, `tests/SP/Domain/Common/Adapters/SerializedPayloadReaderTest.php`, `tests/SP/Domain/Config/Services/ConfigFileTest.php`, `tests/SP/Infrastructure/File/FileCacheTest.php`. Findings and decisions taken at implementation:
- **Correction to the T4 handover.** T4's `FileCache::loadWith()` change did not only reject legacy config caches: it rejected **current-format** `ConfigData` caches too. `ConfigData` extends `DataCollection` (`ArrayObject`), and PHP serializes its storage with integer property keys (`O:...:{i:0;i:0;i:1;a:...}`). The T2 rewriter's `object()`/`propertyKey()` accepted only `s:` keys, so every `ConfigData` payload threw `SPException: Malformed serialized payload at offset 47`, which T4's reader surfaced as `InvalidClassException`. Reproduced in the PHP 8.3 runtime; the pre-T4 raw `unserialize` path decoded the same payload.
- **#2053 did not change the `ConfigData` FQCN.** `6a6cd3dc` only changed its `use` of `DataCollection`; `DataCollection` declares no private state, and `ArrayObject` serializes only the concrete class. A pre-#2053 config cache is therefore structurally identical. The fallback is still required for genuinely mismatched caches (for example the pre-hexagonal `SP\Config\ConfigData` name) and corrupt payloads.
- **Rewriter grammar extended.** `propertyKey()` now copies integer keys (`i:`) in `O:` property lists and still rewrites mangled `s:` keys and nested class names. Without this, the fallback would delete a valid cache on every request and disable caching.
- **Best-effort delete.** On `InvalidClassException`, the cache is deleted; a `FileException` from `delete()` is logged as a warning and does not block the `config.xml` fallback.
- **Port contract.** `FileCacheService::loadWith()` now declares `@throws InvalidClassException`.
- Verification: targeted `ConfigFileTest`, `tests/SP/Domain/Common/Adapters`, `FileCacheTest`, and `tests/SP/Architecture`: 204/204 OK. Unitary group: 2178 tests, the same 2 errors and 1 failure as T4 (`SessionTest::testReKey`, `DefinitionsTest::testGetDefinitions`, `UuidCookieTest::testCreate`), all pre-existing and environmental. PHPStan level 6 on the three changed lib files: no errors.

**T6 API master password.** `lib/SP/Domain/Api/Services/Api.php:257-289`: replace the raw `unserialize()` at `:267` with the T3 reader. Keep the `ServiceException` mapping and handle the domain exception so it is never an uncaught `Error`. Test in `tests/SP/Domain/Api/Services/ApiTest.php`.
- Acceptance: a legacy vault yields a controlled `ServiceException` or succeeds after remap.

Status: implemented and committed in `e6bdff3a`. Files: `lib/SP/Domain/Api/Services/Api.php`, `tests/SP/Domain/Api/Services/ApiTest.php`. Decisions taken at approval:
- `getMasterPassFromVault()` now reads `AuthToken::getVault()` into a local, rejects `null`/empty with the existing `Invalid data` `ServiceException`, and decodes through `SerializedPayloadReader::read($payload, Vault::class)`.
- The default class map is used, so the durable auth-token vault is remapped from the legacy `SP\Core\Crypt\*` FQCNs instead of failing closed. An empty map is correct only for the transient session/config caches (T4/T5).
- `Vault::class` is the expected class, not `VaultInterface::class`, because T3 uses `class_exists()` and validates with `instanceof`.
- `InvalidClassException` is caught alongside `CryptException` and mapped to the same `ServiceException`, so the T3 domain failure can no longer surface as a bare `Error` from `getData()` on `__PHP_Incomplete_Class`.
- The `null`/empty guard is required because `Api.php` declares `strict_types=1`; passing `null` to T3's `string` parameter would raise `TypeError`.
- The now-unused `Crypt` and `VaultInterface` imports were removed.
- Verification: `ApiTest` 47/47 OK (4 new: legacy vault succeeds after remap; unmapped, malformed, and missing vaults raise `ServiceException`). `tests/SP/Domain/Api`, `tests/SP/Domain/Common/Adapters`, and `tests/SP/Architecture`: 126/126 OK. Unitary group: 2182 tests, the same 2 errors and 1 failure as T4/T5 (`SessionTest::testReKey`, `DefinitionsTest::testGetDefinitions`, `UuidCookieTest::testCreate`), all pre-existing and environmental. PHPStan level 6 on `Api.php`: the same 7 pre-existing errors on untouched code (constructor generic port, `getHelp()` array value type, untyped `$default` parameters). The test file reports only pre-existing style errors; the new methods add none.

**T7 Public link view.** `app/modules/web/Controllers/Account/ViewLinkController.php:99-108`: replace `Serde::deserialize($publicLink->getData(), VaultInterface::class)` with the T3 reader. Keep the existing error view.
- Acceptance: a legacy link renders, or shows the existing error view. The stored row is not modified by the read.

Status: implemented and committed in `6859ee7f` (2026-10-31). Files: `app/modules/web/Controllers/Account/ViewLinkController.php`, `tests/SP/Modules/Web/Controllers/Account/AccountTest.php`. Decisions taken at approval:
- `Serde::deserialize($publicLink->getData(), VaultInterface::class)` is replaced by `SerializedPayloadReader::read($payload, Vault::class)` with the default legacy map. `Vault::class` is the expected class because T3 validates with `class_exists()` and `instanceof`.
- **Read before mutation.** The payload is decoded before `addLinkView()` and the view/decrypt counters, so an unreadable link does not consume a view or modify counters. This is a deliberate ordering change; the reader itself never writes.
- `PublicLink::getData()` is `?string`; `null`/empty payloads are guarded because passing `null` to the T3 `string` parameter raises `TypeError` even without `strict_types` (verified).
- `InvalidClassException` is caught, logged with `processException`, and rendered through the existing error partial (`ERR_EXCEPTION`, replacing the `account-link` template). The expired/over-limit branch keeps `ERR_PAGE_NO_PERMISSION`.
- The inner `Serde::deserialize(..., Simple::class)` is unchanged: it decodes decrypted account data, not a stored serialized row, and `Simple` was not moved by `6a6cd3dc`.
- Tests follow the `AccountTest` conventions: `viewLinkLegacyPayload` (legacy `SP\Core\Crypt\*` payload renders; model `data` unchanged), `viewLinkUnreadablePayload` and `viewLinkEmptyPayload` (error view; `addLinkView` and both counters never called; `data` unchanged). The new tests bind `DatabaseUtilInterface` to a stub so they do not depend on the shared harness's DB binding for `Init`.
- Verification: new tests 3/3 OK (13 assertions); `tests/SP/Domain/Common/Adapters` and `tests/SP/Architecture` 79/79 OK. PHPStan level 6 on the controller reports only the 4 pre-existing errors (`$icons` property type, `ThemeIcons::view()`, and two `EventMessage::addExtra()` types). The existing `viewLink` test still fails locally because `DatabaseUtil::checkDatabaseTables()` reaches a real connection; the same failure reproduces at `6a6cd3dc` and is environmental.

**T8 Auth token read path.** Locate all reads of `AuthToken::getVault()` (`lib/SP/Domain/Auth/Models/AuthToken.php:66`) and route them through T3. `AuthToken.php:228` writes the new format and needs no change.
- Acceptance: an unreadable token fails authentication closed. The row is not modified.

## Phase 3: Durable migration

Depends on T3 and P0.1.

**T9 Upgrade handler for durable payloads.**
- New class implementing `UpgradeHandlerService`, annotated `#[UpgradeVersion('4.0.0')]`. Model it on `lib/SP/Domain/Upgrade/Services/UpgradeDatabase.php`.
- Scope: auth token vault (`refreshVaultByUserId` column `vault`), public link data (`PublicLink.data`), user profile, user preferences, plugin data, item presets. Config backups are excluded.
- Per row: decode with T3. If the row is already in the current format, skip it. If it is legacy and readable, rewrite it in one transaction. If it is unreadable, leave it untouched, record the payload type and ID in the event log, and continue scanning so the full report is produced.
- After scanning, return `false` if any row was unreadable, which fails the upgrade. Return `true` otherwise.
- Never delete or clear any row, for any payload type.
- Run under `AppLockHandler` (`lib/SP/Core/Definitions/CoreDefinitions.php`, lock file `config/.lock`).
- Report counts per payload type (rewritten, already current, unreadable) to the event log.
- Acceptance:
  - running twice produces no changes on the second run;
  - legacy rows are rewritten;
  - an unreadable row is byte-identical before and after, is reported by type and ID, and fails the upgrade;
  - re-running after the unreadable row is fixed completes successfully.

**T10 Config backup restore.** `lib/SP/Domain/Config/Services/ConfigBackup.php:90`: use the T3 reader for `ConfigData`. Do not rewrite stored backups.
- Acceptance: a legacy backup restores; an unreadable backup reports failure and modifies nothing.

## Phase 4: Guardrail

**T11 Architecture test.** Add a test in `tests/SP/Architecture/` (alongside `LayerRulesTest.php`) that:
- asserts every registry class resolves;
- asserts no legacy map key resolves via `class_exists`;
- fails when a registered class is missing from the map's current-name set.

Do not add a serialize-call-site allowlist. The repo removed its allowlist and ratchet in `6a6cd3dc`.

## Phase 5: Fixtures and tests

**T12 Legacy fixtures.**
- Generate fixtures from the pre-move tree: `git show 6a6cd3dc^` (where `Vault` lived under `lib/SP/Core/Crypt`). Run the legacy classes once in the Docker runtime and capture the serialized strings.
- Cover: Vault with its Crypt, a public link vault, ConfigData, ProfileData, UserPreferences, PluginDataStorage, ItemPreset, and an unreadable payload for T9.
- Store as static strings in the test classes, following `tests/SP/Core/Crypt/HashTest.php` (`b96c0234`).

**Required test cases:**
- T2: string values containing legacy names unchanged; references preserved; malformed input throws.
- T3: legacy Vault readable; corrupt and unmapped payloads throw.
- T4 to T8: legacy inputs produce the behaviour in each task's acceptance criteria.
- T9: idempotent second run; unreadable rows untouched and failing the upgrade; re-run after fix succeeds.
- T11: architecture guardrail.

## Phase 6: Verification and release

- Full suite: `"$PHP_BIN" vendor/bin/phpunit -c ./tests/phpunit.xml --no-coverage`.
- Static analysis: `"$PHP_BIN" vendor/bin/phpstan analyse --level 6 lib`.
- Architecture: `LayerRulesTest` and `LayerDependencyTest` pass.
- Coverage: per-layer gates in `tests/coverage-thresholds.php` (commit `6e07b8b8`) still pass.
- Release notes for v4:
  - the upgrade rewrites durable rows;
  - an unreadable row fails the upgrade and is listed in the event log, and nothing is deleted;
  - take a database backup before upgrading;
  - rollback to a pre-4.0 version requires restoring that backup, because rewritten rows are not readable by older versions.

## Dependency order

```
P0.1 -> T9 -> T10 -> release verification
T1 -> T2 -> T3 -> { T4, T5, T6, T7, T8 } -> T9
T1 -> T11
T12 precedes all test tasks in Phases 2-4
```

## Out of scope

- Restricting `allowed_classes` on untyped `Serde::deserialize()` (object-instantiation risk). Track as a follow-up issue.
- Versioned envelope/DTO format (ADR option C). Deferred.
- Rewriting config backups stored by users.

## Risks

- A rewriter bug could corrupt a durable row. Mitigate with T2 tests, per-row transactions, and the backup requirement.
- A single unreadable durable row blocks the upgrade until the operator resolves it. This is the accepted cost of not clearing data. Operators check the event log for IDs.
- Rolled-back installs cannot read rewritten rows. Mitigate with release-note wording and the backup requirement.
- P0.1 is a release blocker independent of #2055: without it, no upgrade handler runs on this branch.
