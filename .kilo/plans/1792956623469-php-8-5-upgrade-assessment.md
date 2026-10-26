# PHP 8.5 upgrade: assessment and plan

Status: draft, implementation-ready once the hexagonal-migration plan has landed (D3). Nothing has been implemented. D3a (early split) was rejected; the plans run sequentially. Phase 0 (read-only) may run at any time.

Related plan: `.kilo/plans/hexagonal-migration.md` (see "Relationship" and "Ownership split").

## Verdict

Not upgradeable as-is. Application code is mostly compatible, but:

1. The app refuses to start on PHP >= 8.4 (hard-coded runtime gate).
2. Dependency resolution is pinned to PHP 8.2, and several locked packages are EOL or declare no 8.4+ support.
3. CI runs only PHP 8.1/8.2 and installs with `--ignore-platform-reqs`, which hides incompatibilities. The `modules` test suite never runs in CI.
4. PHP 8.4/8.5 deprecations exist in the code (implicit nullable params, `SplObjectStorage::attach/contains/detach`, PDO MySQL driver constants, `__wakeup` without `__serialize`).

No PHP binary is available in this environment, so nothing below has been executed. Runtime deprecation and test results must be gathered in Phase 0.

## Decisions

| # | Decision | Status |
|---|---|---|
| D1 | Support range PHP 8.3–8.5. PHP 8.2 dropped. | Decided by user |
| D2 | Library major targets (below) | Proposed; in scope unless user objects |
| D3 | Sequencing relative to `.kilo/plans/hexagonal-migration.md`: hex plan lands first, PHP 8.5 work starts after | Decided by user |
| D3a | Refinement of D3: split the work. Early (no wait): Phases 0, 2, 3 and the CI matrix change in Phase 4. After hex plan step "P2-1" (section 7 of the hex plan): Phase 1 code deprecation fixes. | Rejected by user: sequential execution. Revisit only if an external PHP deadline appears (critical path then: runtime gate, `laminas-ldap`, `monolog`, CI on 8.5). |
| D4 | CI gate before the 8.2 job is removed: unit core + modules + DB-backed integration, all on 8.5 | Decided by user |
| D5 | `ademarre/binary-to-text-php`: pin current commit, replace only if pinning is impossible | Proposed |

D1 consequences:
- `Pdo\Mysql` exists only on PHP 8.4+, and 8.3 is supported, so the PDO constant gate in Phase 1.3 is required.
- `Environment::MIN_PHP_VERSION` becomes `80300`. `MAX_PHP_VERSION` becomes `80600` (exclusive).
- `composer.json` `require.php` becomes `>=8.3 <8.6`.
- CI matrix is `['8.3', '8.4', '8.5']`. No 8.2 job after cut-over.

## Evidence

### Runtime and config blockers

- `lib/SP/Domain/Common/Providers/Environment.php:33-34`: `MIN_PHP_VERSION = 80200`, `MAX_PHP_VERSION = 80400` (exclusive). `checkPhpVersion()` returns false on 8.4+.
- `lib/SP/Core/Bootstrap/BootstrapBase.php:180-185`: throws `InitializationException` with hard-coded `'8.2', '8.3'` text when the check fails.
- `tests/SP/Domain/Common/Providers/EnvironmentTest.php:44-47`: asserts `checkPhpVersion()` is true. It fails on 8.5 until the gate is moved.
- `composer.json`:
  - `require.php` is `~8.2 || ~8.3`. Composer's `~8.2` means `>=8.2 <9.0`, so it does not block 8.5 by itself, but it documents 8.2–8.3 only.
  - `config.platform.php` is `8.2`. Composer resolves every dependency as if running on 8.2, so the lock cannot prove 8.5 compatibility.
- `qodana.yaml:22` pins `version: "8.2"`. `.idea/php.xml` targets 8.3 and language level 8.2 (IDE only, low priority).
- `.travis.yml` targets PHP 7.3/7.4 and is stale.

### CI (`.github/workflows/feature.yaml`)

- Unit job: PHP `8.2` only. Runs `phpunit --group unitary --testsuite core`. Installs with `--ignore-platform-reqs`.
- Integration job: gated by repo variable `TESTS_INTEGRATION_ENABLED`. PHP `8.1`, `8.2`. Runs `phpunit --testsuite core` against MariaDB 10.3. Installs with `--ignore-platform-reqs`.
- The `modules` suite is not run by CI. `tests/phpunit.xml` `modules` suite includes `SP/Modules/Web`, but excludes `Api` and `Cli`.

### Locked dependencies (`composer.lock`) and target versions

Versions below were checked against upstream release metadata (Packagist, vendor docs) during this pass.

| Package | Locked | Problem | Target |
|---|---|---|---|
| `laminas/laminas-ldap` | 2.18.1 | Declares `~8.1.0 \|\| ~8.2.0 \|\| ~8.3.0`. Blocks resolution once the platform pin is removed. | `>= 2.20.0`, which declares `~8.2 \|\| ~8.3 \|\| ~8.4 \|\| ~8.5`. |
| `phpunit/phpunit` | 11.2.8 | PHPUnit 11 is for PHP 8.2+. Not tied to 8.5. | `12.x`. PHPUnit 12 requires PHP 8.3+, so it fits the 8.3 floor. PHPUnit 13 requires 8.4+, so it is not usable with 8.3. |
| `symfony/console` | 5.4.42 | 5.4 is past its support window and not tested on 8.x. | `7.4` LTS. Requires PHP 8.2+. Bug fixes until Nov 2028. |
| `symfony/lock` | 5.4.40 | Not referenced directly in PHP source. Symfony's console `LockableTrait` needs it at runtime (`app/modules/cli/Commands/Crypt/UpdateMasterPasswordCommand.php:43,119`). | Keep. Upgrade to the same major as console (`7.4`). Do not remove. |
| `monolog/monolog` | 1.27.1 | EOL. Used in `lib/SP/Core/Definitions/CoreDefinitions.php:31-35` and `app/modules/cli/module.php:25-26`. | `3.x`, at least `3.11` (includes PHP 8.5 deprecation fixes). Requires PHP 8.1+. Monolog 3 changes records to `LogRecord` objects and levels to a `Level` enum. Any `$record['x']` array access and level constants must be migrated. |
| `guzzlehttp/guzzle` | 6.5.8 | EOL. Used in `lib/SP/Core/Definitions/CoreDefinitions.php:293` (DI `GuzzleHttp\Client`) and `app/modules/web/Controllers/Status/StatusBase.php:28` (`ClientInterface`). Small surface. | `7.x`. Upgrade, do not remove. |
| `ademarre/binary-to-text-php` | `dev-master` | Unpinned branch dependency. | Pin to a commit or tag (D5). |
| `klein/klein` | 2.1.2 | Used throughout web/API bootstrap. | Keep. Verify under 8.5 through tests. |
| `phpseclib/phpseclib` | 2.0.47 | Used in `lib/SP/Core/Crypt/CryptPKI.php:29` and `CoreDefinitions.php:35`. | Keep 2.x. Verify under 8.5. Migration to 3.x is out of scope. |
| `phpstan/phpstan` | 1.11.8 | Dev tool. | Upgrade only if its parser fails on 8.5 syntax. |

No known 8.5 blocker from constraints: `php-di/php-di` 7.0.7, `defuse/php-encryption` 2.4.0, `league/fractal` 0.20.1, `aura/sqlquery` 3.0.0, `vlucas/phpdotenv` 4.3.0, `phpmailer/phpmailer` 6.9.1, `nikic/php-parser` 5.1.0.

PHPUnit migration check: `grep` found no docblock annotations (`@test`, `@dataProvider`, `@covers`, `@group`, `@depends`, `@runInSeparateProcess`, `@preserveGlobalState`, `@requires`) in `tests/`. Tests already use `#[Group]` attributes. `tests/phpunit.xml` uses schema 10.1 and has an empty `<logging/>` element. Run `vendor/bin/phpunit --migrate-configuration` after upgrading.

### PHP 8.4/8.5 deprecations found in code

Full-tree scan not yet done. The following were confirmed by grep:

1. Implicit nullable parameters (`Type $x = null`), deprecated since 8.4:
   - `app/modules/web/Util/ErrorUtil.php:120`
   - `app/modules/web/module.php:63` (`SessionHandlerInterface $sessionHandler = null`)
   - `app/modules/web/Controllers/Helpers/LayoutHelper.php:86`
   - `lib/SP/Infrastructure/File/FileHandler.php:182` and `lib/SP/Domain/File/Ports/FileHandlerInterface.php:70` (`callable $chunker = null`)
   - `lib/SP/Domain/Import/Services/ImportBase.php:149` (`callable $value = null`)
   - `lib/SP/Domain/Common/Providers/Password.php:55` (`int $flags = null`)
   - `lib/SP/Domain/Core/Acl/AccountPermissionException.php:42`, `UnauthorizedPageException.php:46`, `UnauthorizedActionException.php:46`, `lib/SP/Domain/User/Services/UpdatedMasterPassException.php:43` (`Exception $previous = null`)
2. `SplObjectStorage::attach/contains/detach`, deprecated in 8.5 in favour of `offsetSet/offsetExists/offsetUnset`:
   - `lib/SP/Core/Bootstrap/PathsContext.php:55,81,85`
   - `lib/SP/Core/Events/EventDispatcherBase.php:56,69,82`. These are `SplObjectStorage` calls. The class's own `attach`/`detach` methods are its API and stay (`ModuleBase.php`, `PluginLoader.php` call them).
   - `lib/SP/Domain/Html/Services/Minify.php:159`
   - `lib/SP/Html/DataGrid/Layout/DataGridHeaderSort.php:57`
   - `lib/SP/Domain/Import/Services/KeepassImport.php:166`
3. PDO MySQL driver constants, deprecated in 8.5 in favour of `Pdo\Mysql::ATTR_*`:
   - `PDO::MYSQL_ATTR_FOUND_ROWS`: `lib/SP/Infrastructure/Database/MysqlHandler.php:42`
   - `PDO::MYSQL_ATTR_USE_BUFFERED_QUERY`: `lib/SP/Infrastructure/Database/Database.php:216`
   - Also in `tests/SP/Infrastructure/Database/DatabaseTest.php:405` and `MysqlHandlerTest.php:115`.
4. `__wakeup()` without `__serialize()/__unserialize()`, soft-deprecated in 8.5 with no runtime notice:
   - `lib/SP/Domain/User/Models/ProfileData.php:587`
   - `lib/SP/Domain/User/Models/UserPreferences.php:96`
   - Serialized payloads already stored must still unserialize, so `__wakeup` stays.
5. `ArrayObject` subclasses (`lib/SP/Core/DataCollection.php`, `TemplateCollection`, `ContextCollection`): deprecated when wrapping objects. Current constructions use no-arg `new`, so risk is low. Confirm in tests.

Grep found no matches for these in the sampled output: `$GLOBALS` writes, `(boolean)/(integer)/(double)/(binary)` casts, backtick operator, `__debugInfo`, `setAccessible`, `readdir(null)`, `spl_autoload_unregister`, `$http_response_header`. Output was truncated, so confirm with a full-tree scan in Phase 0.

### Relationship to `.kilo/plans/hexagonal-migration.md` (D3)

The hexagonal plan is in progress and touches the same files:
- P0-1 and P0-2 need the same PHP 8.2 baseline and add `modules` to CI. This plan reuses them (see "Ownership split" below).
- P0-3 adds an architecture test that must keep passing after each change here. Phase 1 edits only signatures and calls, so it adds no imports.
- P1-1, P1-2, P1-4 move exception, `QueryResult`, and event classes in Domain. Phase 1 here edits `AccountPermissionException`, `UnauthorizedPageException`, `UnauthorizedActionException`, `UpdatedMasterPassException`, and `FileHandlerInterface`.
- P2-1 changes the base class of 70 Domain services.
- P1-1 and P4-1 move classes that may be serialized (`ProfileData`, `UserPreferences`). The hexagonal plan rule 8 keeps their names stable. Phase 1.4 here must run after any such move or be re-verified.

Decision D3: the hexagonal-migration plan lands first. The PHP 8.5 code changes (Phases 1–5) start only after it is merged. Phase 0 is read-only and may run earlier. Re-run the Phase 0 deprecation scan after the hexagonal plan lands, because it moves exception classes (P1-1), `QueryResult` (P1-2), and event classes (P1-4), and changes the `Service` base class (P2-1). File paths in this document will change accordingly.

Ownership split to avoid duplicated work:
- The hexagonal plan owns the baseline (P0-1), the `modules` CI job (P0-2) and PHPStan runnability (P0-7). This plan reuses them and does not redo them.
- This plan owns the PHP version range, the dependency majors, the runtime gate, and the PHP 8.4/8.5 deprecation fixes.
- The hexagonal plan's rule 7 (section 1.2) keeps new code free of 8.4/8.5 deprecations, so this plan's Phase 1 list does not grow.
- The hexagonal plan's rule 8 covers moves of serialized classes. Phase 1.4 and Phase 5.5 here verify stored payloads unserialize.

D3a (running Phases 0, 2, 3 early) was rejected. Reasons: dependency upgrades would be validated twice (on the 8.2 pin, then on 8.5), `CoreDefinitions.php` and `cli/module.php` edits would conflict with hex commits, and the hex baseline (PHP 8.2, PHPUnit 11) would lose its value as a regression signal.

## Ordered plan

### Phase 0: baseline (no code changes)

1. Provision PHP 8.2 (baseline for comparison) and PHP 8.5 CLI with `mbstring, intl, gd, ldap, gettext, pdo_mysql, fileinfo, zlib, xml, dom, json`. Provision MariaDB 10.3 (CI parity).
2. On PHP 8.2, reuse the hexagonal plan P0-1 baseline (`hexagonal-migration.md` section 9: 1978/1993/96 tests, 0 failures). Only run `composer install` without `--ignore-platform-reqs` here to confirm it still resolves on 8.2, and record the result.
3. On PHP 8.5, `php -l` over `lib/`, `app/`, `tests/`, `public/`, `index.php`, `api.php`, `cli.php`. Save output.
4. On PHP 8.5, run the same suites with `error_reporting(E_ALL)` and PHPUnit `convertDeprecationsToExceptions`. Save every `Deprecated:` message. This is the authoritative deprecation list.
5. On PHP 8.2, `vendor/bin/phpstan analyse --level 6 lib`. Reuse the hexagonal plan P0-7 baseline once it exists. Until then PHPStan is not runnable (`APP_PATH` undefined).

Exit: baseline recorded, deprecation list complete, dependency blockers confirmed with `composer update --dry-run --with-all-dependencies` under platform 8.5.

### Phase 1: code deprecations (must work on PHP 8.3, 8.4, and 8.5)

1. Replace implicit nullable params with explicit `?Type` at all sites listed above. Signature-only change.
2. Replace `SplObjectStorage::attach/contains/detach` with `offsetSet/offsetExists/offsetUnset` in the 5 files listed. Keep `EventDispatcherBase` public methods.
3. Replace PDO MySQL constants with `Pdo\Mysql::ATTR_*` (`ATTR_FOUND_ROWS`, `ATTR_USE_BUFFERED_QUERY`). Use one helper that returns `Pdo\Mysql::ATTR_*` when `class_exists(\Pdo\Mysql::class)` is true, else `PDO::MYSQL_ATTR_*`. Used by `MysqlHandler`, `Database::doFetchWithOptions`, and tests.
4. Add `__serialize()/__unserialize()` to `ProfileData` and `UserPreferences`, keeping `__wakeup()`. Add a test that unserializes an old-format payload.
5. Fix anything the Phase 0 deprecation run reports beyond the list above.

### Phase 2: dependencies

1. Remove `config.platform.php` from `composer.json`.
2. Set `require.php` to `>=8.3 <8.6`.
3. Upgrade one commit each, running the suites after each:
   1. `ademarre/binary-to-text-php`: pin (D5).
   2. `laminas/laminas-ldap`: `>= 2.20.0`.
   3. `symfony/console` and `symfony/lock`: `7.4`. Adapt `app/modules/cli` if signatures change. Keep `symfony/lock`.
   4. `monolog/monolog`: `3.x` (`>= 3.11`). Adapt `CoreDefinitions.php` and `app/modules/cli/module.php` for `LogRecord`/`Level`. Check any custom handler or formatter.
   5. `guzzlehttp/guzzle`: `7.x`. Check `CoreDefinitions.php:293` and `StatusBase.php`.
   6. `phpunit/phpunit`: `12.x`. Run `--migrate-configuration`.
   7. `phpstan/phpstan` only if its parser fails on 8.5.
4. Regenerate `composer.lock` with `composer update --with-all-dependencies` on PHP 8.5, after the above.

### Phase 3: runtime gate and metadata

1. `Environment`: `MIN_PHP_VERSION = 80300`, `MAX_PHP_VERSION = 80600`.
2. `BootstrapBase.php:182`: build the message from the constants, not literals.
3. `EnvironmentTest`: assert 8.2 fails, 8.3, 8.4, 8.5 pass, 8.6 fails. Use an injectable version value rather than `PHP_VERSION_ID` if needed.
4. Update README, INSTALL, installer text that states supported versions, and `qodana.yaml` `version`. `.idea/php.xml` only if the team uses it.
5. Remove `.travis.yml`, or update it to match `feature.yaml`.

### Phase 4: CI (`.github/workflows/feature.yaml`)

1. Unit job matrix: `['8.3', '8.4', '8.5']`. Remove `--ignore-platform-reqs`.
2. Integration job matrix: `['8.3', '8.4', '8.5']`. Remove `--ignore-platform-reqs`. Set `TESTS_INTEGRATION_ENABLED` for the 8.5 gate (D4).
3. Do not add a new `modules` job here. The hexagonal plan P0-2 owns it. Extend its matrix to `['8.3', '8.4', '8.5']` and make it DB-backed on 8.5 (D4). Note `tests/phpunit.xml` excludes `Api` and `Cli` from `modules`, so only `Web` runs. Api and Cli are enabled by hexagonal plan P7-1 and P8-1.
4. Keep the current 8.2 job green until the full 8.5 gate is green, then remove it.

### Phase 5: validation (PHP 8.5, MariaDB 10.3)

1. Unitary and core suites, zero deprecations.
2. Modules suite (`Web`), zero deprecations.
3. Integration suite with DB.
4. Manual smoke through the web entrypoint:
   - Installer (`InstallCommand`) on a fresh DB.
   - Login, session create and destroy.
   - Account create, edit password, history view.
   - Backup and XML export (`BackupFile.php`, `XmlExportController`), including the scrollable cursor path (`BackupFile.php:198`).
   - Import, including `XmlFileStorage` `DOMNodeList` path.
   - Mail send via PHPMailer.
   - LDAP check (needs an LDAP server; outside unit scope).
   - CLI commands with `LockableTrait` (`UpdateMasterPasswordCommand`).
5. Stored-data compatibility: unserialize a `ProfileData` and a `UserPreferences` payload created on PHP 8.2 under PHP 8.5.
6. `composer audit` and `composer outdated --direct` on 8.5.

### Rollback

Each Phase 2 dependency is its own commit. Keep the current runtime gate and CI jobs until the new jobs are green.

## Out of scope

- `phpseclib` 2 to 3.
- Replacing Klein, Aura.SqlQuery, or Fractal.
- PHP 8.6 readiness.
- Effort estimates.

## Open questions

None blocking. D1, D3 and D4 are resolved. D2 is in scope unless the user objects. D5 (binary-to-text pinning) can be confirmed during Phase 2.1.

D3a is closed (rejected). D3 applies: Phases 1–5 start after the hexagonal plan lands.
