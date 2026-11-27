<!-- Copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org -->

# Trigger visibility and master-password rotation

> Historical research: the chosen policy treats custom triggers as operator-owned
> and does not require trigger inspection. See [master-password change](master-password-change.md)
> for the current operational guidance. The alternatives below describe the
> earlier, broader atomicity requirement.

## Question and historical code

The research asked whether rotation could be atomic without granting `TRIGGER`
to the ordinary database account. Its original prototype inspected triggers in
the storage guard and coordination-row setup. Those observations are historical:
the selected policy delegates custom-trigger safety to operators and checks
transactional storage without requiring trigger visibility. Follow
[current rotation guidance](master-password-change.md) and inspect the current
guard and lock-row implementations before applying the alternatives below.

## Database facts

- In MySQL 8.0, `INFORMATION_SCHEMA.TRIGGERS` requires the `TRIGGER` privilege on a table to show its triggers; `SHOW TRIGGERS` is not an independent unprivileged source. [MySQL 8.0 trigger metadata](https://dev.mysql.com/doc/refman/8.0/en/information-schema-triggers-table.html).
- MariaDB documents `SHOW TRIGGERS` as requiring `TRIGGER`. Metadata visibility differs across MariaDB releases: the 10.11.16 release notes describe a change from filtering entire rows to hiding sensitive columns in some cases. Do not assume a uniform metadata query is sufficient across supported versions. [MariaDB SHOW TRIGGERS](https://mariadb.com/docs/server/reference/sql-statements/administrative-sql-statements/show/show-triggers), [MariaDB 10.11.16 release notes](https://mariadb.com/docs/release-notes/community-server/10.11/10.11.16).
- Triggers execute in the invoking statement's transaction when using transactional engines, and MySQL restricts triggers from explicitly or implicitly committing. This does not make writes to nontransactional tables rollbackable. [MariaDB trigger overview](https://mariadb.com/docs/server/server-usage/triggers-events/triggers/trigger-overview), [MySQL trigger transaction events](https://dev.mysql.com/doc/refman/8.4/en/performance-schema-transaction-tables.html), [MySQL nontransactional rollback](https://dev.mysql.com/doc/refman/8.0/en/nontransactional-tables.html).
- MySQL metadata locks on tables used in a transaction are held through transaction end and block conflicting DDL; trigger metadata is protected by the subject table's metadata lock. This supports, but does not by itself prove for all supported MariaDB versions, an inspect-after-lock design. [MySQL metadata locking](https://dev.mysql.com/doc/refman/8.4/en/metadata-locking.html), [MySQL data dictionary developer reference](https://dev.mysql.com/doc/dev/mysql-server/latest/group__Data__Dictionary.html).
- `LOCK TABLES`, `CREATE TRIGGER` and `DROP TRIGGER` can implicitly commit in MySQL; introducing them into the rotation transaction does not preserve the existing transaction boundary. [MySQL implicit commits](https://dev.mysql.com/doc/refman/8.4/en/implicit-commit.html).

## Options

1. **Separate privileged rotation identity.** Keep an unprivileged web/API runtime account and run the administrative CLI with a separately configured, short-lived database identity that has `TRIGGER` on affected tables. Retain fail-closed engine and trigger inspection and acquire metadata locks before inspection. The identity still needs `TRIGGER`: this removes it from the ordinary runtime account, not from the system. Initialization of the coordination row and automatic hash upgrades also currently inspect `Config`; provision the row administratively and either move hash upgrade into an equivalently guarded privileged operation or design and review a safe alternative. Operator credential management, shared database configuration, upgrades and lock lifetime require implementation and testing.
2. **Privileged definer helper.** An administrator installs a tightly scoped `SQL SECURITY DEFINER` routine or view owned by a dedicated account with trigger visibility; the application receives only permission to call or read that helper. MySQL documents definer-context privileges for stored routines and views. [MySQL stored-object access control](https://dev.mysql.com/doc/refman/8.0/en/stored-objects-security.html). This is a design candidate, not a proven drop-in solution: validate metadata visibility under definer context on every supported MySQL/MariaDB version and ensure the helper cannot be replaced by the runtime identity. Fail closed if it is missing, inaccessible or not the trusted definition. Ensure table metadata locks are acquired on the same connection before checking and remain held until transaction end. Installation and updates require a trusted DBA and introduce definer lifecycle and operational risks.
3. **Administrator-enforced deployment invariant.** Require a trusted operator to enforce trigger-free affected tables and transactional storage, and to prevent trigger-creating DDL for the duration of rotation. This avoids an application-side `TRIGGER` check, but atomicity is conditional on that external invariant; a one-time attestation or successful backup is not proof that triggers remain absent. If sysPass cannot verify the invariant, it must not advertise unconditional enforcement. Maintenance mode and application advisory locks do not constrain independent database administrators.
4. **Permit triggers after inspecting their dependencies.** A privileged inspector could validate all reachable trigger effects and storage engines. This is substantially more complex: triggers execute with their definer's privileges, and may use routines or affect other tables. It requires an exhaustive, version-specific dependency and privilege model and is not a simple replacement for rejecting triggers. [MySQL CREATE TRIGGER](https://dev.mysql.com/doc/refman/8.0/en/create-trigger.html).

## Recommendation and verification

Prefer a separate administrative rotation identity if the operational requirement is to remove `TRIGGER` from the long-lived application identity: it keeps the existing visibility model and gives rotation a direct fail-closed inspection path. A definer helper is worth prototyping only if the rotation account itself must lack `TRIGGER`; it still requires a privileged, trusted owner and careful deployment. Neither option can prove atomicity against an unconstrained administrator changing database configuration or trigger DDL outside the stated trust model.

For either design, test MySQL and each supported MariaDB version with a hidden trigger and an actual nontransactional side effect, permitted and denied inspection, trigger creation attempted concurrently after the preflight lock, engine changes, ordinary no-trigger success and rollback at multiple rotation steps. Cover absent or changed helper, revoked privileges, roles and MySQL partial revokes, plus setup and hash rehash paths. Confirm that failures occur before sensitive writes and that locks remain until commit or rollback. The repository requires maintainer security review before merge. These are research recommendations, not executed tests or an implementation claim.
