# 0005 - Preserve serialized PHP payload compatibility across domain namespace moves

Status: Accepted (2026-10-31); gates resolved 2026-10-31 (see Resolved decisions)
Issue: #2055 (tracking #1976, triggered by #2053)

## Context

PHP `serialize()` embeds fully qualified class names (FQCNs) and private property names mangled with the declaring class (`\0SP\Core\Crypt\Vault\0data`). Moving a class to a new namespace makes every persisted payload that contains it unreadable to the new code. #2053 moved `SP\Core\Crypt\Vault` and `SP\Core\Crypt\Crypt` to `SP\Domain\Core\Crypt\*` (commit `6a6cd3dc`), along with `DataCollection` and `ContextException`.

Verified behaviour on the current branch (PHP 8.3 in the project Docker runtime):

- A legacy payload is not rejected by `allowed_classes`. PHP instantiates `__PHP_Incomplete_Class`, and the failure surfaces later:
  - `lib/SP/Domain/Api/Services/Api.php:267` calls `->getData()` on the incomplete object. This throws a bare `Error`, which the `catch (CryptException)` at `Api.php:281` does not handle.
  - `lib/SP/Domain/Common/Adapters/Serde.php:72` rejects typed reads with "Invalid target class" before the incomplete-class fallback at `Serde.php:73` runs. `fixSerialized()` is only reached for untyped reads, where it silently returns `stdClass`.
  - `lib/SP/Domain/Crypt/Services/SecureSession.php:96-105`: `InvalidClassException` extends `SPException`, not `FileException`, so `getKey()` returns `false` and does not regenerate the session key. The session stays unusable until `CACHE_EXPIRE_TIME` (86400s) elapses.
  - `lib/SP/Domain/Config/Services/ConfigFile.php:91`: a mismatched `config.cache` is not caught by `loadFromCache()` and reaches `initialize()` as `ConfigException::critical`. Bootstrap fails and does not fall back to `config.xml`. This applies only if the serialized form of `ConfigData` changes, which can happen because it extends the moved `DataCollection`.
- `class_alias()` silently corrupts state. The aliased object receives `data = NULL` plus a dynamic `Old\Vault::$data` property, because private properties are keyed by the declaring class name. A test-run reproduction confirmed this.
- A serialized `Vault` embeds its `Crypt` object (`private readonly CryptInterface $crypt`), so each Vault payload carries two legacy class names.

Persisted payloads affected (inventory beyond the issue body):

| Payload | Storage | Classes | Lifetime | Regenerable | Risk class |
|---|---|---|---|---|---|
| Secure session vault | `secure_session/<uuid>` cache file | Vault, Crypt | 24h | Yes (new key) | Transient |
| Auth token vault | `vault` column, written at `AuthToken.php:228` | Vault, Crypt | Until refresh or revoke | No: needs the master password | Durable |
| API unpack | Read from the auth token (`Api.php:267`) | Vault, Crypt | Per request | n/a | Durable via auth token |
| Public link data | `PublicLink.data`, written at `PublicLink.php:212` | Vault (+Crypt) | `publinksMaxTime` | Re-create the link | Durable until expiry |
| Config cache | `config.cache` | ConfigData (extends DataCollection) | File-time based | Yes (`config.xml`) | Transient |
| Config backup | `ConfigBackup.php:80`, restored at `:90` | ConfigData | User-held export | No | Durable |
| User profile and preferences | `SerializedModel` via `UserProfile`/`User` `#[Hydratable]` | ProfileData, UserPreferences | Indefinite | No | Durable user data |
| Plugin data, item presets | `SerializedModel` via `PluginData`/`ItemPreset` `#[Hydratable]` | PluginDataStorage, ItemPreset | Indefinite | No | Durable user data |
| Acl and MIME caches | `FileCache::load()` | Various | File-time based | Yes | Transient |

Constraints:

- Auth tokens, public links, backups, and user data cannot be regenerated. Silent loss or partial decode of these is unacceptable.
- The repo removed its architecture allowlist and ratchet in `6a6cd3dc`. Any guardrail must be a rule or registry, not a new allowlist.
- Upgrade handlers are discovered through `UpgradeVersion` attributes, but no production code calls `Upgrade::registerUpgradeHandler()`. Only tests call it. At runtime `Upgrade::upgrade()` iterates an empty list. This must be fixed before any data migration can rely on the upgrade path.

## Decision

Adopt option D, risk-tiered handling, with these parts:

1. **Read-time remap (option B).** Introduce an explicit map from legacy FQCNs and their mangled private property names to current names. A token-level rewriter applies the map to a payload before `unserialize()`. `unserialize()` then runs with `allowed_classes` restricted to current names. Unmapped or unreadable payloads fail closed: the typed reader throws a domain exception and never returns a partial or `stdClass` value.
2. **Transient data: regenerate, do not fail.** Secure sessions regenerate their key on `InvalidClassException`. The config cache deletes itself and reloads from `config.xml`. Caches are rebuilt from their source.
3. **Durable data: one-time idempotent migration.** Add an upgrade handler, tagged `#[UpgradeVersion('4.0.0')]`, that rewrites durable payloads to current names in one transaction per row, under the existing `AppLockHandler`. Payloads already in the current format are left alone. Config backups are not rewritten; they stay readable through the remap. Unreadable durable rows are never deleted, cleared, or modified: the handler records their type and IDs in the event log and returns `false`, which fails the upgrade. Re-running after the operator resolves the cause is safe because the handler is idempotent.
4. **Guardrail.** A persisted-class registry lists each class whose layout is stored in payloads. An architecture test enforces that each registered class resolves and that each legacy map key does not resolve to a current class. Any future move of a registered class requires a map entry and a fixture in the same change.

Resolved decisions (2026-10-31):

- **Target release: v4.** The migration ships in the release whose `Version::VERSION` is `[4, 0, 0]`. The issue label `v3` is stale.
- **Upgrade handler version: `4.0.0`.** The new handler uses `#[UpgradeVersion('4.0.0')]`. The comparator normalises `4.0.0` to `4000.0`, the same bucket as the existing `UpgradeDatabase` tag `400.24210101` (`4000.24210101`). Verified in the Docker runtime: `checkVersion()` returns `true` for every stored version below 4000 and `false` for stored versions at or above 4000. The handler therefore applies to installs upgraded from any pre-4.0 version and never re-runs on 4.0 installs.
- **Upgrade handler registration.** All handlers carrying `#[UpgradeVersion]` are registered with the `Upgrade` service in production wiring, including the existing `UpgradeDatabase`. Without this, `Upgrade::upgrade()` iterates an empty list.
- **No data is cleared.** The unreadable-row policy in the earlier proposal (clearing auth tokens and public links) is withdrawn. All durable payloads follow the same rule: untouched, reported, upgrade fails.
- **Durable handler ordering (T9).** The durable handler is registered before `UpgradeDatabase`. `Upgrade` re-runs handlers selected by the stored app version and does not track per-handler completion; the schema handler is not idempotent. A durable failure must leave the schema untouched so a retry is safe.
- **Plugin data is not migrated by the unauthenticated upgrade.** `PluginData.data` is encrypted with the master key on write and the upgrade endpoint runs in `PARTIAL_INIT` without a session/master key; plugin classes are external and cannot be enumerated for the typed reader. T9 counts plugin rows as skipped, leaves the ciphertext byte-identical, and does not fail on them. This is an exception to the "plugin data" row in the inventory; an authenticated follow-up is required if plugin payloads ever embed moved core classes.
- **Legacy map covers pre-hexagonal durable names.** T1's map only covered #2053 names. T9 adds `SP\DataModel\ProfileData`, `SP\DataModel\UserPreferencesData`, and `SP\DataModel\ItemPreset\{Password,AccountPrivate,AccountPermission,SessionTimeout}`, verified against tag `3.2.11` and the checked-in fixtures. `SP\DataModel\ConfigData` remains T10's responsibility.
- **Preferences keep the serialized format.** Legacy serialized preferences are class-rewritten to `SP\Domain\User\Models\UserPreferences`; valid JSON rows are skipped as current. Current writes are JSON while the only reader is PHP `unserialize`; that pre-existing mismatch is tracked separately and is not changed by T9.
- **Vault dependency repair.** Older `Vault` payloads may not include the `crypt` dependency that the current class stores. The durable handler injects the stateless `CryptInterface` into the uninitialized readonly property and re-serializes the object; a class-only rewrite would produce a vault that reads as an object but fails on `getData()`.

Addendum (2026-10-31): legacy `ConfigData` snapshot.
- `SP\Config\ConfigData` is mapped to a new Domain-layer snapshot DTO,
  `SP\Domain\Config\Legacy\LegacyConfigData`, instead of directly to the current `ConfigData`. The DTO
  declares the 3.2.11 private properties and defaults and only exposes them as an array. It is never
  persisted and is deliberately absent from `PersistedClassRegistry`.
- `SP\Domain\Config\Legacy\LegacyConfigDataMapper` maps the snapshot onto the current collection through an
  explicit property-to-constant/setter table. `backup_hash`, `export_hash`, and `checknotices` are aliased;
  `filesAllowedExts` is carried through `ConfigDataInterface::FILES_ALLOWED_EXTS`; only `ldapAds` and
  `ldapProxyUser` are dropped. Dropped and unknown property names are reported. Missing declared properties
  use the 3.2.11 defaults.
- `ConfigBackup` tries the current `ConfigData` first and then the snapshot; if both fail it throws one
  `InvalidClassException`. The export path (`configToJson()`) uses the same decode because this ADR forbids
  returning `stdClass` or a partial object. `restore()` has no production caller; export is the production
  path.
- Rationale: the current `ConfigData` stays free of legacy unserialization behaviour, while the typed reader
  still guarantees a complete, fail-closed decode.

Option C (versioned envelope/DTO) is deferred. It is the right long-term format, but it changes what new writes look like and makes rollback harder. It can be revisited for new payload types.

## Considered options

**A. Compatibility aliases only (`class_alias()` shims).** Rejected as a standalone solution. The alias resolves class names but silently drops private state (verified above), which is a data-integrity failure. Aliases also need to load on every entry point, and legacy names persist indefinitely.

**B. Read-time remap.** Chosen as the mechanism. It preserves existing rows, is explicit, and is testable. Its cost is a byte-level rewriter, which must be a real token walker, not the regex used by `fixSerialized()`. A regex can rewrite class-name text inside string values and miscount lengths.

**C. Versioned envelope / DTO.** Deferred. Structurally the cleanest, because future namespace moves stop affecting stored data. But new writes change format, so older versions cannot read rows written by newer versions, and every payload type needs a serializer. Too large for this migration.

**D. Risk-tiered handling.** Chosen. It applies the cheapest safe behaviour to each tier: regenerate transient data, migrate durable data, and keep the remap as the single compatibility mechanism.

**Baseline: accept invalidation everywhere.** Rejected. It would silently break auth tokens, public links, and user profile and preference data, which are not regenerable.

**Baseline: revert the namespace move.** Rejected. The move is part of the layer-rules hard gate (`6a6cd3dc`) and the architecture direction of the branch.

**Guardrail-only (no remap).** Rejected as sufficient. A guardrail prevents new breakage but does not repair existing payloads.

## Consequences

- The upgrade rewrites durable rows to current class names. **Older versions cannot read those rows afterwards.** Rollback requires restoring a database backup taken before the upgrade. This must be stated in the release notes and the upgrade notes.
- The legacy map is maintained for as long as legacy backups may be restored. It is append-only in practice.
- Config backups keep legacy payloads. They remain readable only through the remap.
- The payload rewriter becomes shared infrastructure in `Domain/Common/Adapters`. It must be covered by unit tests with fixtures.
- The rewriter does not solve untyped `Serde::deserialize()` calling unrestricted `unserialize()`. This is an object-instantiation risk on data read from storage. It is out of scope and must be tracked as a follow-up.
- A single unreadable durable row (for example an orphaned public link) blocks the upgrade until the operator resolves it. This is the accepted cost of not clearing data. Operators must be told to take a database backup before upgrading and to check the event log for reported IDs.
- Implementation tasks are in `docs/handover/2055-serialized-compat-handover.md`.

## Verification

Fixtures must be generated from the pre-move tree (`git show 6a6cd3dc^`) by running the legacy classes once, then stored as static strings in the tests. Fixture generation follows the precedent in `b96c0234`. Tests must assert:

- Legacy payloads are read and rewritten to current names, with the expected `data` value after decryption.
- Unmapped or corrupt payloads raise a domain exception and never return a partial object or `stdClass`.
- Strings containing a legacy class name are left unchanged.
- Re-running the migration is a no-op.
- The architecture test fails when a registered class is moved without a map entry.
