# 0008 - Fail the PHP 8.5 gate on first-party deprecations only and track vendor debt

Status: Accepted (2026-11-01)

Issue: #1980 (tracking #1977, Phase 0 decision D6)

## Context

sysPass is moving to a PHP 8.3-8.5 support range. The Phase 0 baseline recorded in #1979 at `66fe8482f` found:

- 18 application/test implicit-nullable deprecations at compile time.
- 15 unique vendor deprecations at runtime: Guzzle promises (6), vfsStream (3), Faker (3), and `sebastian/recursion-context` (3, via PHPUnit 11).
- 108 vendor compile-time deprecation sites across Faker 1.23.1, Guzzle 6.5.8, promises 1.5.3, Klein 2.1.2, vfsStream 1.6.11, and Fractal 0.20.1.

Phase 5 (#2003) requires a zero-deprecation gate. A gate that fails on every deprecation would fail on libraries the project cannot fix in this release: Klein 2.1.2 is the latest tag and is already being replaced under ADR 0007, while Guzzle and PHPUnit are scheduled for upgrade in #1993 and #1994.

The current PHPUnit 11.2.8 cannot cleanly separate first-party from vendor deprecations:

- `ignoreIndirectDeprecations` uses a frame-based trigger model that hides first-party deprecations triggered from tests.
- `restrictDeprecations` filters correctly by file but is absent from the 11.2 schema.
- `failOnDeprecation` also counts PHPUnit/test-runner deprecations.

PHPUnit 12 classifies issues by callee/caller (`IssueTrigger`). `ignoreIndirectDeprecations` then keeps first-party and test-origin PHP deprecations while ignoring vendor-origin ones, and `failOnDeprecation` counts PHP/user deprecations only. #2003 is already transitively blocked by #1994 (PHPUnit 12), so the gate naturally belongs to the 12 migration.

## Decision

Adopt option (c): fail on first-party deprecations only, keep vendor libraries, and track vendor deprecations as debt.

1. Apply the gate on PHPUnit 12, after #1994 has migrated the configuration:

   ```xml
   <phpunit ... failOnDeprecation="true"
                 displayDetailsOnTestsThatTriggerDeprecations="true">
       <source ignoreIndirectDeprecations="true"
               ignoreSuppressionOfPhpDeprecations="true">
           <include>
               <directory suffix=".php">../lib/SP</directory>
               <directory suffix=".php">../app/modules/web</directory>
               <directory suffix=".php">../app/modules/api</directory>
               <directory suffix=".php">../app/modules/cli</directory>
               <file>../lib/Base.php</file>
               <file>../lib/BaseFunctions.php</file>
               <file>../index.php</file>
               <file>../api.php</file>
               <file>../cli.php</file>
           </include>
           <!-- Keep Assets and ConfigData.php; remove Html/DataGrid from excludes
                so DataGridHeaderSort.php:57 is first-party for the gate. -->
       </source>
   </phpunit>
   ```

   `ignoreSuppressionOfPhpDeprecations="true"` is required because both sysPass (`BootstrapBase::initPHPVars()`) and PHPUnit lower `error_reporting` while tests run. Do not enable `ignoreSuppressionOfDeprecations` (`E_USER_DEPRECATED`) for the 8.5 gate: Faker's deliberately suppressed property accesses would otherwise be classified as direct and fail the gate.

2. On PHP 8.5 CI, run the core and modules suites with `--fail-on-deprecation --display-deprecations`, and add an application/test lint gate over `lib`, `app`, `tests`, `public`, and the entry points that fails on any `Deprecated:` line. Publish a non-blocking vendor lint report as an artifact; vendor runtime debt is tracked in the register below.

3. Maintain the vendor debt register:

   | Package | Locked | Runtime sites | Lint sites | Owner |
   |---|---:|---:|---:|---|
   | `guzzlehttp/guzzle` | 6.5.8 | 0 | 26 | #1993 (Guzzle 7) |
   | `guzzlehttp/promises` | 1.5.3 | 6 | 24 | #1993 |
   | `sebastian/recursion-context` | 6.0.2 | 3 | 0 | #1994 (PHPUnit 12) |
   | `fakerphp/faker` | 1.23.1 | 3 | 40 | patch bump to `^1.24.1` via #1996 or a targeted update |
   | `mikey179/vfsstream` | 1.6.11 | 3 | 3 | patch bump to `^1.6.12` via #1996 or a targeted update |
   | `league/fractal` | 0.20.1 | 0 | 2 | patch bump to `^0.20.2` via #1996 or a targeted update |
   | `klein/klein` | 2.1.2 | 0 | 13 | accepted residual; ADR 0007 replaces it (API path ported separately) |
   | `phpseclib/phpseclib` | 2.0.47 | 0 | 0 | #1987 |
   | `nikic/php-parser` | 5.1.0 | 0 | 0 | #1995 |

   The existing constraints already allow the Faker, vfsStream, and Fractal patch bumps.

4. Do not enable `failOnDeprecation` on PHPUnit 11.2.8. Record the decision now; enforcement lands with #1994/#2003. #1985 uses this register to define the remaining-deprecation scope.

## Considered options

- **Option (a): upgrade or remove every vendor library that emits a deprecation.** Rejected for the 8.5 release. Faker, vfsStream, Fractal, Guzzle, and PHPUnit have upgrade paths, but Klein 2.1.2 has no upstream fix; completing (a) would require replacing or forking it, expanding the release and duplicating ADR 0007.
- **Option (b): allowlist vendor deprecations in PHPUnit configuration.** Rejected. PHPUnit has no package-aware allowlist; a file:line baseline must be regenerated on every dependency bump, is brittle, and cannot baseline test-runner deprecations.
- **Option (c): fail on application deprecations only and track vendor debt.** Accepted.

## Consequences

- Application and test deprecations fail the PHP 8.5 gate at runtime and at lint time, including deprecations currently hidden by lowered `error_reporting`.
- Vendor deprecations remain visible but do not block the release; the register must be revisited after the 8.5 release.
- Klein remains a known deprecation source until the router port in ADR 0007 covers the API entry point.
- The PHPUnit 12 migration must adopt this configuration; until then, only deprecation diagnostics are enabled.
- A vendor deprecation triggered directly from a test is classified as direct by PHPUnit 12 and will fail the gate. The known Faker case stays suppressed and is excluded by leaving the `E_USER_DEPRECATED` suppression switch off.

## References

- #1979: verified Phase 0 baseline after the hexagonal migration.
- #1977: PHP 8.5 upgrade tracking issue and plan.
- ADR 0007: own Router port replaces Klein.
