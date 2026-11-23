# Handover: Serialized payload compatibility (#2055)

Decision record: `docs/adr/0005-preserve-serialized-payload-compatibility.md` (Accepted; resolved decisions recorded there).
Branch: `refactor-syspass-to-hexagonal-architecture`. The ADR, this handover, P0.1 (`60fc7056`), T1 (`6131893c`), T2 (`75269d7b`), and T3 (`1d19d25f`) are committed. T4 is committed in `441e88aa`. T5 is committed in `670e58e1` (rewriter grammar) and `2c7db922` (config fallback). T6 is committed in `e6bdff3a`. T7 is committed in `6859ee7f`. T8 is committed in `a3f744ba` (tests) and `a97826f9` (handover). T9 is committed in `de5c7908` (2026-10-31). T10 is committed in `28921e30` (restore) and `7622d5f2` (snapshot decode remediation). T11 is committed in `28921e30` and `7622d5f2`. T12 is committed in `7622d5f2` (fixtures regenerated). All tasks are committed; no code changes remain in the working tree.

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

Status: verified and closed as test-only (2026-10-31). No production change was required.
- **T6 already routed the only production read.** `grep` over `lib/` and `app/` (excluding tests and `vendor/`) finds exactly one caller of `AuthTokenModel::getVault()`: `lib/SP/Domain/Api/Services/Api.php:265`. It decodes through `SerializedPayloadReader::read($payload, Vault::class)` at `Api.php:277` with the default legacy map. The auth-token web controllers, forms, grids, and repository never read `vault`; `lib/SP/Domain/Auth/Services/AuthToken.php:159` and `:228` only write it.
- **Fail-closed path.** `Api::getMasterPassFromVault()` catches `CryptException|InvalidClassException` at `Api.php:291` and throws `ServiceException` before `setup()` marks the API initialized. No write occurs in that path.
- **Regression coverage.** `tests/SP/Domain/Api/Services/ApiTest.php`: the secured-action helper now returns the `AuthToken` model and asserts `update()`, `updateRaw()`, and `refreshAndUpdate()` are never called. The unreadable, malformed, and missing-vault tests assert the original model vault value is unchanged after the failure, in addition to failing with `Internal error`. The existing legacy-vault test also asserts the legacy payload is unchanged while the remapped vault is read.
- **Not changed.** The raw `AuthToken::getVault()` accessor stays. Moving decoding into the model or adding a typed accessor to `AuthTokenService` would widen a port without changing behaviour.
- Verification: `ApiTest` 47/47 OK (141 assertions); `tests/SP/Domain/Api`, `tests/SP/Domain/Common/Adapters`, `tests/SP/Architecture`: 126/126 OK. PHPStan level 6 on `ApiTest.php` reports only the 26 pre-existing errors on untouched methods and data providers.

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

Status: committed in `de5c7908` (2026-10-31).

Files:
- `lib/SP/Domain/Upgrade/Services/UpgradeDurablePayloads.php`
- `lib/SP/Domain/Upgrade/Enums/DurablePayloadType.php`
- `lib/SP/Domain/Upgrade/Dtos/DurablePayloadRow.php`
- `lib/SP/Domain/Upgrade/Ports/DurablePayloadRepository.php`
- `lib/SP/Infrastructure/Upgrade/Repositories/DurablePayload.php`
- `lib/SP/Domain/Common/Adapters/LegacyClassMap.php`
- `lib/SP/Core/Definitions/CoreDefinitions.php`
- `lib/SP/Core/Definitions/DomainDefinitions.php`
- `tests/SP/Domain/Upgrade/Services/UpgradeDurablePayloadsTest.php`
- `tests/SP/Infrastructure/Upgrade/Repositories/DurablePayloadTest.php`
- `tests/SP/Stubs/DurablePayloadFixtures.php`
- `tests/SP/Stubs/InMemoryDurablePayloadRepository.php`
- `tests/SP/Core/Definitions/ContainerWiringTest.php`

Decisions and findings:
- **Handler version.** The handler carries `#[UpgradeVersion('4.0.0')]` as decided. `Version::normalizeVersionForCompare('4.0.0')` yields `4000.0`, so it applies to pre-4.0 stored versions and is not selected for a stored `400.0` (equal to 4.0.0). The wiring test uses `320.0` and asserts that the durable handler runs before `UpgradeDatabase`.
- **Handler order.** `UpgradeDurablePayloads` is registered before `UpgradeDatabase`. `Upgrade` re-runs every handler selected by the stored app version and does not track per-handler completion; `UpgradeDatabase`'s SQL is not idempotent. Running the durable handler first means a durable failure leaves the schema untouched and a retry is safe.
- **Direct table reads.** `DurablePayload` queries `AuthToken.vault`, `PublicLink.data`, `UserProfile.profile`, `User.preferences`, `ItemPreset.data`/`type`, and `PluginData.data` directly. Repository enumeration was not used because `PublicLinkRepository::getAll()` joins `User`/`Account` and omits orphaned links.
- **Plugin data is skipped, not rewritten.** `PluginDataService` encrypts `data` with the master key on write (`PluginData.php:79,96`) and decrypts on read (`:122,143,161`). The upgrade endpoint is in `PARTIAL_INIT` and unauthenticated (`app/modules/web/Init.php:91-100`), so no master key is available; plugin classes are external and cannot be enumerated for T3. T9 counts non-empty plugin rows as `skipped`, leaves the ciphertext byte-identical, and does not fail on them. This is a deliberate exception to the T9 scope. A future authenticated phase can migrate plugin data if needed.
- **Legacy map was incomplete.** T1 mapped only the #2053 names. Tag `3.2.11` and `tests/res/datasets/syspass.xml` contain `SP\DataModel\ProfileData`, `SP\DataModel\UserPreferencesData`, and `SP\DataModel\ItemPreset\{Password,AccountPrivate,AccountPermission,SessionTimeout}`. T9 adds those six class-level entries; class-only remaps were verified against the current classes. `SP\DataModel\ConfigData` is left for T10.
- **Preferences and profiles are mixed-format, and both are readable.** Current writes are JSON (`lib/SP/Infrastructure/User/Repositories/User.php:483` for preferences, `Installer.php:353` for the Admin profile), while the historical format is PHP-serialized. T9 treats valid JSON as already current for both `User.preferences` and `UserProfile.profile`; legacy serialized rows are class-rewritten to the current serialized classes. `SerializedModel::hydrate()` now reads JSON through `Serde::deserializeJsonTo()` and falls back to `Serde::deserialize()` for serialized payloads, so accepting JSON as current no longer leaves the row unreadable. This also fixes the pre-existing JSON profile read path.
- **Legacy vaults missing the `crypt` dependency are repaired.** Pre-hexagonal `Vault` payloads do not always serialize the `crypt` dependency; the current `Vault` has a readonly `crypt` property and `getData()` fails if it is uninitialized. For `AuthToken.vault` and `PublicLink.data`, T9 checks `ReflectionProperty::isInitialized()` and, when missing, injects the container's stateless `CryptInterface` and re-serializes the object. A class-only rewrite would leave a payload that reads as an object but fails on use. Payloads that already contain `crypt` use the normal byte-level rewrite.
- **Per-row transactions and no deletes.** Every rewrite runs through `DurablePayloadRepository::transactional()`, which wraps `beginTransaction()`/`endTransaction()`/`rollbackTransaction()`. The port has no delete method; T9 issues only `SELECT` and single-row `UPDATE`. Unreadable rows are never updated, all applicable types are scanned, and `apply()` returns `false` if any row is unreadable or any write fails.
- **Locking.** The handler no longer locks on its own. `Upgrade::upgrade()` acquires one `AppLockHandler::tryLock(0, 'upgrade')` before any handler runs and releases it in `finally` after all handlers, so `UpgradeDurablePayloads` and `UpgradeDatabase` share the same critical section. `AppLock` uses `fopen('c+')` plus `flock(LOCK_EX|LOCK_NB)`, writes an owner token and expiry, recovers abandoned token-owned locks and expired legacy locks, and only deletes the file when `unlock()` verifies the token. The existing tokenless maintenance lock behaviour is preserved.
- **Event log.** Per-row failures include type and ID (never payload contents). Per-type summaries include rewritten, current, empty, unreadable, failed, and skipped counts.

Review follow-up (2026-10-31):
- **`DurablePayload::findByType()` did not fetch rows.** It called `DatabaseInterface::runQuery()` with a `Query` object that implements only `QueryInterface`, not `SelectInterface`. `Database::runQuery()` fetches only for `SelectInterface` and otherwise returns `QueryResult(null, ...)`, so production returned an empty result and `UpgradeDurablePayloads` reported success without migrating anything. The mocked `runQuery()` in the repository test masked it.
- Fixed in `lib/SP/Infrastructure/Upgrade/Repositories/DurablePayload.php` (commit `de5c7908`): `findByType()` reads through `doFetchWithOptions($queryData, [], PDO::FETCH_ASSOC)` instead of `runQuery()`, so the SELECT actually fetches. The per-type SELECT list and ID/subtype mapping are unchanged. The later WARNING-finding follow-up replaced the single full materialisation with keyset-paginated yields (see below).
- Regression coverage in `tests/SP/Infrastructure/Upgrade/Repositories/DurablePayloadTest.php`: `fetchesAndMapsRowsThroughTheRealDatabasePath` builds the real `Database` with the real `QueryFactoryAdapter` over an in-memory PDO connection, inserts two `AuthToken` rows, and asserts both rows are fetched and mapped. It failed with 0 rows before the fix. The existing mapping tests now stub `doFetchWithOptions()` instead of `runQuery()`; the update and transaction tests are unchanged.

Review follow-up (2026-10-31, WARNING findings):
- **Transaction results are checked.** `DurablePayload::transactional()` throws when `beginTransaction()` returns false, checks `endTransaction()` and throws when the commit fails, and attempts `rollbackTransaction()` on every failure path. Tests cover begin failure (callback not invoked, no rollback), commit failure (rollback attempted, exception raised), and callback-failure rollback.
- **JSON is handled consistently.** JSON is the accepted current format for both `UserProfile.profile` and `User.preferences`. `Serde::isJson()` centralises detection, `Serde::deserializeJsonTo()` builds the target model from the decoded object, and `SerializedModel::hydrate()` reads JSON as well as serialized payloads. The upgrade handler treats JSON profile and JSON preferences as current instead of sending them to the typed serialized reader. Fixtures and tests cover JSON profile, JSON preferences, the serialized read path, and unreadable payloads.
- **Locking is atomic and covers every upgrade handler.** `AppLockHandler::tryLock()` was added. `AppLock` acquires an exclusive `flock(LOCK_EX|LOCK_NB)` on `config/.lock`, writes a random owner token and expiry, reclaims abandoned token-owned locks (the OS released the flock) and expired legacy tokenless upgrade locks, and `unlock()` deletes the file only when the owner token matches. `Upgrade::upgrade()` acquires the lock once around all handlers and releases it in `finally`; `UpgradeDurablePayloads` no longer checks or takes the lock itself. Tests cover concurrent acquisition, non-owner unlock, expired and abandoned token-owned locks, legacy stale locks, live maintenance locks, and maintenance lock persistence.
- **Full-table materialisation was removed.** `DurablePayload::findByType()` yields keyset-paginated batches of 500 rows ordered by `id`, or by `(name, itemId)` for `PluginData`. `PluginData` selects a non-empty marker instead of the ciphertext payload. Tests assert two-page keyset traversal for both key shapes and the composite-key bind values.
- **Dependency on the critical fix.** The CRITICAL `findByType()` fetch fix was already present in `de5c7908`. Pagination was implemented on top of that fetch mechanism; the critical SELECT mechanics were not duplicated or changed.

Verification:
- New and updated T9/repository/service tests: `UpgradeDurablePayloadsTest`, `DurablePayloadTest`, `UpgradeTest`, `SerializedModelTest`, and `AppLockTest` pass (44/44 targeted run).
- Upgrade/adapter/architecture group (`tests/SP/Domain/Upgrade`, `tests/SP/Infrastructure/Upgrade`, `tests/SP/Domain/Common/Adapters`, `SerializedModelTest`, `AppLockTest`, `ContainerWiringTest`, `tests/SP/Architecture`): 129/129 OK (329 assertions, 8 pre-existing deprecations).
- Full unitary group: 2219 tests, the same 2 pre-existing errors and 1 failure recorded for T4-T8 (`SessionTest::testReKey`, `DefinitionsTest::testGetDefinitions`, `UuidCookieTest::testCreate`); no new failures.
- PHPStan level 6 on all changed lib files: no errors.

Previous follow-up verification (findByType fix):
- New T9 tests: 20/20 OK (99 assertions).
- `tests/SP/Domain/Upgrade`, `tests/SP/Infrastructure/Upgrade`, and `tests/SP/Domain/Common/Adapters`: 93/93 OK (246 assertions, 2 pre-existing deprecations).
- `ContainerWiringTest`: 3/3 OK (8 assertions, 8 pre-existing deprecations).
- PHPStan level 6 on `DurablePayload.php` and `DurablePayloadTest.php`: no errors.
- Not rerun in this follow-up: the full unitary group and `--testsuite core`. The latter still stops in the integration fixture loader because MySQL is unavailable (`ERROR 2005`); that is environmental. The broader `CoreDefinitions`, `DomainDefinitions`, and existing upgrade files still report pre-existing level-6 errors (`CoreDefinitions.php:202,523`, `DomainDefinitions.php:196`, `UpgradeService.php:39`, `UpgradeDatabase.php:120`); T9 adds none.

**T10 Config backup restore.** `lib/SP/Domain/Config/Services/ConfigBackup.php:90`: use the T3 reader for `ConfigData`. Do not rewrite stored backups.
- Acceptance: a legacy backup restores; an unreadable backup reports failure and modifies nothing.

Status: implemented and committed in `28921e30` (2026-10-31), then reviewed and remediated in
`7622d5f2` (2026-10-31). Files:
- `lib/SP/Domain/Config/Legacy/LegacyConfigData.php`
- `lib/SP/Domain/Config/Legacy/LegacyConfigDataMapper.php`
- `lib/SP/Domain/Config/Services/ConfigBackup.php`
- `lib/SP/Domain/Common/Adapters/LegacyClassMap.php`
- `lib/SP/Domain/Config/Adapters/ConfigData.php` (reverted to `28921e30^`; no legacy hook)

Decisions and findings:
- **Legacy snapshot DTO.** `LegacyConfigData` faithfully declares the 93 private properties of the 3.2.11
  `SP\Config\ConfigData` class with their 3.2.11 defaults. Its only behaviour is `toArray()`. It is never
  persisted, is not in `PersistedClassRegistry`, and the architecture test asserts that.
- **Map target.** `LegacyClassMap::MAP` now maps `SP\Config\ConfigData` to `LegacyConfigData`, not directly
  to the current `ConfigData`. The map docblock explains the snapshot mapping.
- **Explicit mapper.** `LegacyConfigDataMapper` replaces the reflection-based generic engine with an explicit
  property -> `ConfigDataInterface` constant + setter + cast table. Aliases are `backup_hash`, `export_hash`,
  and `checknotices`. `filesAllowedExts` is preserved through
  `set(ConfigDataInterface::FILES_ALLOWED_EXTS, ...)`. Only `ldapAds` and `ldapProxyUser` are dropped, and
  their names are returned for logging. Unknown/undeclared properties are returned separately and never
  silently ignored. Missing declared properties use the 3.2.11 defaults.
- **Correction to the earlier T10 note.** `filesAllowedExts` is not obsolete: the current `ConfigData` has a
  getter and the collection accepts the key, so the mapper carries it through. Only `ldapAds` and
  `ldapProxyUser` have no current target.
- **Two-step decode.** `ConfigBackup::decode()` first reads the payload as the current `ConfigData`. On
  `InvalidClassException` it reads it as `LegacyConfigData` and maps it. If both reads fail, one
  `InvalidClassException` is thrown. No `stdClass` or partial object is returned. Both `restore()` and
  `configToJson()` use this path.
- **Export remains required.** `configToJson()` uses the same decode because the ADR forbids returning a
  `stdClass` or partial object from a typed read.
- **No production restore caller.** `ConfigBackupService::restore()` has no caller in `lib/` or `app/`. The
  only production use is `DownloadConfigBackupController::index()` through `getBackup()` and
  `configToJson()`.
- **Real fixture asserted.** `ConfigBackupTest` restores the `config_backup` row from
  `tests/res/datasets/syspass.xml`: `dbHost=syspass-db-dev`, `dbPort=3306`, all 18
  `filesAllowedExts`, and `backup_hash=c9142988b9d91a375ca83387f0593c7a12eaf5a2`. That row also contains
  the undeclared `ldapUserattr` property, which is reported as unknown.

Verification:
- `ConfigBackupTest`, `LegacyConfigDataMapperTest`, and `SerializedPayloadCompatibilityTest`: 24/24 OK
  (389 assertions).
- `tests/SP/Domain/Common/Adapters`, `tests/SP/Domain/Config`, `tests/SP/Domain/Upgrade`,
  `tests/SP/Infrastructure/Upgrade`, and `tests/SP/Architecture`: 162/162 OK (746 assertions, 2 pre-existing
  deprecations).
- PHPStan level 6 on all changed lib files: 19 findings, all pre-existing in the reverted `ConfigData.php`;
  no findings in `LegacyConfigData`, `LegacyConfigDataMapper`, `ConfigBackup`, or `LegacyClassMap`.
- Full core suite stops at the integration fixture loader after 1769/2279 tests with MySQL `ERROR 2005`
  (`mysql -h  -u  ... Unknown server host '-u'`). Modules suite stops at the same loader after 189/370 tests;
  its earlier failures are also database-dependent. Both are environmental and unrelated to this change.

## Phase 4: Guardrail

**T11 Architecture test.** Add a test in `tests/SP/Architecture/` (alongside `LayerRulesTest.php`) that:
- asserts every registry class resolves;
- asserts no legacy map key resolves via `class_exists`;
- fails when a registered class is missing from the map's current-name set.

Do not add a serialize-call-site allowlist. The repo removed its allowlist and ratchet in `6a6cd3dc`.

Status: implemented and committed in `28921e30`, with the snapshot assertions added in `7622d5f2`
(2026-10-31). File:
`tests/SP/Architecture/SerializedPayloadCompatibilityTest.php`. The test asserts that every registry class
resolves, every legacy map key no longer resolves, every registry class appears among the map's current names
(or is produced by a legacy snapshot mapper), and every map target resolves. It also asserts that
`LegacyConfigData` is not in `PersistedClassRegistry` and that `SP\Config\ConfigData` maps to the snapshot.
Verification: 5/5 OK (39 assertions).

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

Status: covered and committed in `7622d5f2` (2026-10-31; fixtures regenerated).
Provenance:
- The pre-move tree `6a6cd3dc^` and tag `3.2.11` were exported with `git archive` into `/tmp/kilo`
  (not `git worktree`, which mutates `.git`).
- A PHP generator run through `$PHP_BIN` produced the static strings. The Docker runtime mounts only the
  repository, so the generator and selected legacy sources were staged in a temporary directory inside the
  worktree, then deleted together with `/tmp/kilo`.
- Vault-with-Crypt and the public-link vault come from `6a6cd3dc^` `lib/SP/Core/Crypt/{Vault,Crypt}.php`.
  The public-link fixture is now used for `PublicLinkData` in `UpgradeDurablePayloadsTest`.
- ProfileData, UserPreferencesData, and ItemPreset models come from tag `3.2.11` `lib/SP/DataModel`; the
  `SP\DataModel\*` names no longer exist at `6a6cd3dc^`.
- `LegacyConfigData` comes from `3.2.11` `lib/SP/Config/ConfigData.php` with all 93 properties populated.
  It was cross-checked against the `config_backup` row in `tests/res/datasets/syspass.xml`: the row has 89
  properties, the extra `ldapUserattr` (reported as unknown), and lacks the newer `applicationUrl`,
  `appVersion`, `filesAllowedMime`, `ldapTlsEnabled`, and `ldapType` properties.
- `PluginDataStorage` remains excluded: it is an interface with no core implementation, as recorded in the
  T1/T9 decisions. No fixture was invented.

Static payloads live in `tests/SP/Stubs/DurablePayloadFixtures.php`, and the legacy `SP\Config\ConfigData`
snapshot is exercised through that fixture in `LegacyConfigDataMapperTest` plus the real database row in
`ConfigBackupTest`.

## Phase 6: Verification and release

- Full suite: `"$PHP_BIN" vendor/bin/phpunit -c ./tests/phpunit.xml --no-coverage`.
- Static analysis: `"$PHP_BIN" vendor/bin/phpstan analyse --level 6 lib`.
- Architecture: `LayerRulesTest` and `LayerDependencyTest` pass.
- Coverage: per-layer gates in `tests/coverage-thresholds.php` (commit `6e07b8b8`) still pass.
- Release notes for v4:
  - the upgrade rewrites durable rows;
  - an unreadable row fails the upgrade and is listed in the event log, and nothing is deleted;
  - take a database backup before upgrading;
  - clear the compiled container cache (`cache/container/<normalized-version>`) when deploying if the normalized version string is unchanged, because the durable handler binding is part of the compiled container;
  - rollback to a pre-4.0 version requires restoring that backup, because rewritten rows are not readable by older versions.

Status: verified (2026-10-31).
- Full suite: stops at the integration fixture loader after 1769/2649 tests with MySQL `ERROR 2005`
  (`mysql -h  -u  ... Unknown server host '-u'`), the same environmental failure recorded for T9/T10.
- Unitary core suite with coverage: 2215 tests, 28908 assertions, OK (20 warnings, 15 deprecations,
  7 incomplete). The 2 errors and 1 failure recorded in earlier T4-T9 runs no longer reproduce.
- Coverage gate (`scripts/coverage-gate.php --gate`): `Domain\*\UseCases` 91.11% (minimum 90%),
  `Domain\*\Services` 96.46% (minimum 95%): passed.
- Architecture/adapters/config/upgrade group (`tests/SP/Architecture`, `tests/SP/Domain/Common/Adapters`,
  `tests/SP/Domain/Config`, `tests/SP/Domain/Upgrade`, `tests/SP/Infrastructure/Upgrade`): 162/162 OK
  (746 assertions, 2 pre-existing deprecations).
- PHPStan level 6 on the changed lib files: no errors. A full `lib` run still reports 1040 pre-existing
  baseline findings unrelated to #2055; the branch adds none in the changed files.
- No production code changes remain uncommitted.

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
