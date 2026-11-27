<!-- Copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org -->

# UI Smoke Fixtures Guide

This guide explains how the synthetic UI smoke fixtures under `tests/ui/fixtures/` are structured and how agents should update or extend them safely. For runner architecture, tiers and isolation rules, see [UI smoke suite](ui-smoke-suite.md).

## Ownership and boundaries

| Path | Role |
| --- | --- |
| `tests/ui/fixtures/e2e-fixture.json` | Committed source of truth for synthetic `E2E_*` credentials, logical IDs, names and sample account/file content. |
| `tests/ui/src/fixture.ts` | TypeScript contract (`UiFixture`) and `FIXTURE` / `*_CREDENTIALS` exports consumed by Playwright specs. |
| `tests/ui/fixtures/seed.php` | Namespaced (`SP\Tests\Ui\Fixtures`) CLI seeder run inside the pinned PHP container with `--reset` and `--verify`. |
| `scripts/ui-smoke.sh` | Reads `credentials.admin.*` for `sp:install` and runs `seed.php` twice (`--reset`, `--verify`, `--reset`, `--verify`) before launching the test server. |

Rules to preserve:

- Keep fixtures strictly synthetic (`E2E_*` names, `.invalid` URLs, disposable test secrets). Never copy real credentials, user data or production values.
- Keep `tests/SP/` fixtures and shared datasets completely separate; `seed.php` must only target the runner-owned isolated instance.
- Run PHP commands only via `$PHP_BIN` (`.ai-cli/bin/php`) after sourcing `.ai-cli/env.sh`. Never invoke host PHP or point `seed.php` at a developer database or `app/config`.

## How `seed.php` works

`tests/ui/fixtures/seed.php` separates fixture declaration, query building, cleanup, reset and verification:

1. **Specification (`loadFixtureRaw()`, `buildFixtureSpec()`):**
   Reads `SP_UI_FIXTURE_FILE` (defaulting to `tests/ui/fixtures/e2e-fixture.json`) and produces a typed associative array covering `groups`, `profiles`, `users`, `clients`, `categories`, `tags`, `accounts`, `accountGroupLinks` and `files`. Optional secondary items (`DEFAULT_SECOND_CLIENT_*`, `DEFAULT_TAG_*`) fall back to constants when not declared in JSON.
2. **Query helpers (`insertRow()`, `updateWhere()`, `deleteWhere()`, `bindStatementParams()`):**
   Uses `Aura\SqlQuery\QueryFactory` with explicit PDO parameter typing (`PARAM_NULL`, `PARAM_INT`, `PARAM_LOB`, `PARAM_STR`). Binary/JSON LOB columns (`UserProfile.profile`, `User.preferences`, `AccountFile.content`) must be listed in `$lobColumns`.
3. **Cleanup (`cleanFixtureRows()`):**
   Deletes synthetic rows inside the database transaction in child-to-parent foreign-key order:
   `AccountFile` → `AccountHistory` → `Account` → `Client` → `Category` → `Tag` → `Notification` & `PublicLink` → `AccountToUserGroup` → `User` → `UserGroup` → `UserProfile`.
   Cleanup matches unique keys (`id`, `login`, `name`, `itemHash($name)`) so `--reset` stays idempotent even after partial runs. Installed records (`admin`, `id = 1`) are updated in place rather than deleted.
4. **Reset (`resetFixture()`):**
   - Acquires `MasterPasswordRequestLock` in the CLI entrypoint.
   - Executes all DB deletes, inserts and updates inside `$pdo->beginTransaction()` / `commit()` (rolling back on any exception).
   - Computes password hashes, user vaults and encrypted account payloads through domain ports (`HashService`, `UserMasterPassService`, `AccountCryptService`) rather than hardcoded hashes or blobs.
   - Runs filesystem side effects (`configureApplication($configFileService)` and `$cryptPKIHandler->createKeys()`) **after** `$pdo->commit()`, preventing `config.xml` or PKI keys from drifting if the transaction rolls back.
5. **Verification (`verifyFixture()`):**
   Validates `ConfigFileService` flags and raw `config.xml` tags, `Config` master-password metadata, all user hashes/vaults/preferences/group/profile bindings, `admin` lookup via `UserService::getByLogin()`, account metadata and ciphertext decryption, `AccountFile` content, `CustomFieldType`, `Client` (including `isGlobal`), `Category`, `Tag`, `UserGroup`, `UserProfile` (comparing every permission flag on `restricted` against `restrictedProfileModel()`), and `AccountToUserGroup` links.

## Updating existing fixture values

1. Edit `tests/ui/fixtures/e2e-fixture.json`. Keep `.invalid` domains and `E2E_` prefixes; note that `tests/ui/src/sanitize.mjs` scrubs sensitive keys (`password`, `login`, `master`, etc.) from failure evidence.
2. If you add, rename or remove JSON keys, update `UiFixture` in `tests/ui/src/fixture.ts` and check `fixture_value` lookups in `scripts/ui-smoke.sh` (`credentials.admin.*`).
3. Audit dependent specs and helpers under `tests/ui/src/` and `tests/ui/specs/` (for example `contracts.ts`, `tier-b.ts`, `discovery.spec.ts`, `accountCreate.write.spec.ts`).
4. Account for application behavior before changing flags or ACLs:
   - `Client.isGlobal`: non-global clients (`0`) are excluded from `ClientRepository::getAllForFilter()` unless the user holds `CLIENT` access via `AccountHelper` or an existing account already references them. Keep the primary vs. secondary client visibility intentional.
   - Profiles: `demo` copies installed `UserProfile` `id = 1`; `restricted` uses `restrictedProfileModel()` (`accView = true`, all other flags `false`). Changing `restrictedProfileModel()` automatically updates both `resetFixture()` and `verifyFixture()`.

## Extending the fixture with new entities

Follow this checklist when adding new seeded rows, users or tables:

1. **Declare the data cleanly:** Add stable IDs/names to `tests/ui/fixtures/e2e-fixture.json` and `tests/ui/src/fixture.ts` (or extend `buildFixtureSpec()` in `tests/ui/fixtures/seed.php`). Use non-colliding IDs (for example `>= 1000` for synthetic accounts/tags/clients) and never overwrite installed `id = 1` defaults unless intended.
2. **Add foreign-key-safe cleanup:** Update `cleanFixtureRows()` in `tests/ui/fixtures/seed.php` so any new child/join rows are deleted before their parents, and include unique columns (`name`, `login`, `itemHash($name)`) so double `--reset` remains idempotent.
3. **Use typed builders and domain services:** Insert or update via `insertRow()` / `updateWhere()` (never raw interpolated SQL). Pass LOB column names in `$lobColumns`, generate hashes/ciphertexts with `HashService`, `UserMasterPassService`, `AccountCryptService` or `itemHash()`, and keep config/filesystem updates after `$pdo->commit()`.
4. **Assert invariants in `verifyFixture()`:** Do not check row existence alone—verify key columns, foreign keys, flags (such as `isGlobal`) or decryption so `--verify` catches silent drift immediately.
5. **Keep write specs self-contained:** Specs under `tests/ui/specs/*.write.spec.ts` should create disposable items at runtime or clean up after themselves when modifying seeded entities, rather than assuming single-run state.

## Verification checklist

Run static checks after any change to `tests/ui/fixtures/seed.php`, `tests/ui/fixtures/e2e-fixture.json` or `tests/ui/src/fixture.ts`:

```sh
source .ai-cli/env.sh
.ai-cli/bin/ai-run -- "$PHP_BIN" -l tests/ui/fixtures/seed.php
.ai-cli/bin/ai-run -- "$PHP_BIN" vendor/bin/phpcs --standard=PSR2 --warning-severity=0 tests/ui/fixtures/seed.php
.ai-cli/bin/ai-run -- "$PHP_BIN" vendor/bin/phpstan analyse --level=6 --no-progress tests/ui/fixtures/seed.php
( cd tests/ui && npm run typecheck && npm run test:unit )
```

Then run a targeted UI smoke check (which exercises `--reset`, `--verify`, `--reset`, `--verify` on a disposable MariaDB container) followed by the relevant tiers:

```sh
TESTS_UI_ENABLED=true SP_UI_TIER=WRITE SP_UI_SPECS=accountCreate.write.spec.ts \
  .ai-cli/bin/ai-run --timeout 180 -- ./scripts/ui-smoke.sh
TESTS_UI_ENABLED=true SP_UI_TIER=A .ai-cli/bin/ai-run --timeout 300 -- ./scripts/ui-smoke.sh
```

Note that `scripts/ui-smoke.sh` verifies developer paths (`app/config`, `app/cache`, `app/temp`) before and after execution and exits non-zero if an external process (such as another running container) modifies `app/config/syspass.log` during the run.
