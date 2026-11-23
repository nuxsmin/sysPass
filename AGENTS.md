# Contributor and Agent Guide

## Project Purpose

sysPass is a self-hosted PHP password manager for business and personal use, backed by MySQL/MariaDB. It manages encrypted account credentials, access through users, groups and profiles, and features such as account history, public links and external authentication. Users interact through a server-rendered web interface, a JSON-RPC API and CLI commands.

This file guides AI-assisted development and contributor navigation. Agents are development tools, not application services or autonomous components in the deployed password manager.

## Architecture

The repository is organized around a domain core with ports and adapters. Shared bootstrap code loads Composer autoloading and a PHP-DI container; module-specific bootstraps adapt incoming requests to application behavior.

| Location | Responsibility |
| --- | --- |
| `index.php`, `api.php`, `cli.php` | Entry points for the Web, API and CLI modules. |
| `lib/bootstrap.php`, `lib/Base.php` | Shared initialization and container construction. |
| `lib/SP/Domain/` | Domain models, business rules, use cases, commands/results and ports, grouped by domain context. |
| `lib/SP/Infrastructure/` | Driven adapters, including persistence and external-service implementations. |
| `lib/SP/Core/` | Composition root and shared framework glue, including DI definitions, sessions, events and logging. |
| `app/modules/web/` | Web controllers, presentation and server-rendered views. |
| `app/modules/api/` | JSON-RPC controllers and request/response adapters. |
| `app/modules/cli/` | Symfony Console commands and CLI initialization. |
| `app/config/`, `app/resources/`, `app/locales/` | Runtime configuration, application resources and translations. |
| `public/`, `schemas/` | Frontend assets and database schemas. |
| `tests/SP/` | Domain, infrastructure, module integration and architecture tests. |

For use-case-based operations, the flow is: entry point and bootstrap, driving adapter, Command DTO, use case, domain port, infrastructure adapter, Result DTO, then boundary-specific presentation. Port-to-adapter bindings are explicit in `lib/SP/Core/Definitions/DomainDefinitions.php`.

Before exploring or changing a subsystem, read `CONTEXT.md` for its terminology and the relevant decisions in `docs/adr/`. ADRs include migration targets as well as implemented rules: verify the current code before describing a target as shipped behavior. See `docs/agents/domain.md` for domain-document conventions.

### Dependency Rules

- Keep business rules in `Domain`; use constructor-injected ports for external capabilities rather than a service locator.
- Keep infrastructure implementations behind domain ports. Wire concrete adapters in the composition root rather than importing them into domain code.
- Keep request parsing, translation and response formatting at the Web/API/CLI boundary. Domain errors carry stable codes and untranslated message keys.
- Before changing use cases, read [ADR-0003](docs/adr/0003-use-case-and-command-result-convention.md). Before moving or changing persisted classes, read [ADR-0005](docs/adr/0005-preserve-serialized-payload-compatibility.md).

## Agent Responsibilities

This worktree has no repository-specific agent personas in `.kilo/agent/`. Available modes and skills depend on the developer's tooling. The responsibilities below describe a collaboration workflow, not separate application components or required agent processes.

| Responsibility | Scope and Expected Output |
| --- | --- |
| Investigation and planning | Read the issue, domain vocabulary and relevant ADRs; identify affected layers, constraints and acceptance criteria. |
| Implementation | Make scoped changes in the appropriate layer, maintain DI wiring and add tests for changed behavior. |
| Review and verification | Check correctness, security-sensitive behavior, architecture boundaries and spec coverage; report findings with file references and test evidence. |
| Coordination | When work is delegated, define non-overlapping tasks, share interface changes and blockers, and integrate and verify the combined result. |
| Human maintenance | Resolve product or architecture decisions and grant approvals required by the execution rules below. |

One contributor or agent may perform several responsibilities. Skills provide reusable workflows for these activities; they are not runtime dependencies. Delegation does not expand the user's authorization or remove human approval requirements.

### Issues and Handoffs

GitHub Issues are the source of truth for issues and specs. Read the relevant issue and acceptance criteria before implementation; include scope, affected files, decisions, blockers and verification results in handoffs.

For tracker operations, consult `docs/agents/issue-tracker.md`. For triage, use the label meanings in `docs/agents/triage-labels.md`: `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human` and `wontfix`.

Prefer enabled MCP servers over CLI equivalents for operations they cover. Use the `github` server's `github_*` tools first for GitHub work. If unavailable, denied or missing a capability, fall back to `$GH_BIN` under the CLI rules below. This tool-selection rule takes precedence over bare `gh` examples in tracker documentation.

## Development Workflows

- Before changing behavior or fixing a bug, read [Testing](docs/agents/testing.md) and follow its red-green workflow. Read it before verification of refactors as well.
- Before creating a commit, read [Commits](docs/agents/commits.md). Use Conventional Commits; create or amend commits only when explicitly requested.

Include scope, decisions, blockers and actual verification results in the completion report. Report unavailable prerequisites rather than claiming checks passed.

## Security

- Before reporting a suspected vulnerability, read [SECURITY.md](SECURITY.md) and follow its private disclosure process rather than publishing sensitive details in public issues.
- Before changing security-sensitive behavior, read [Security Guidance](docs/agents/security.md) and the relevant ADRs. Changes to authentication, authorization, cryptography, key handling, sessions, public links or credential export require explicit security review by a maintainer before merge.
- Preserve server-side authorization across Web, API and CLI entry points; test denied access as well as successful access.
- Keep passwords, keys, tokens and decrypted account data out of logs, exceptions and fixtures; expose them only to intended recipients through authorized application behavior.
- Use established project cryptographic abstractions. Algorithm, key derivation, randomness and stored-ciphertext changes require explicit review and compatibility tests.

## Safe Execution

- Before CLI work, read `.ai-cli/README.md` for command permissions, wrappers, runtime setup and examples. Source the environment first with `source .ai-cli/env.sh`.
- Invoke GitHub CLI through `"$GH_BIN"`; the wrapper selects the appropriate invocation for sandboxed and non-sandboxed environments. Never invoke bare `gh`.
- Run CLI mutations through `.ai-cli/bin/ai-run -- <cmd>`, use `.ai-cli/command-templates/` where available, and obey `.ai-cli/allowed-commands.txt`.
- Ask before commands not covered by the allowlist.
- AWS, Terraform and Docker mutations require explicit human approval. The documented exception is the ephemeral Docker invocation performed by `$PHP_BIN`.
- Run PHP only inside Docker through `"$PHP_BIN"` (`.ai-cli/bin/php`). Never invoke host PHP, execute `vendor/bin/*` directly, or use Composer scripts that invoke host PHP.
- `.ai-cli/` is AI-agent tooling only. Shared human/CI entry points belong in
  `scripts/` (for example `scripts/ui-smoke.sh`), not in `.ai-cli/bin/`.
- Ask for human approval before building or rebuilding the PHP image with `make -C scripts/docker build`.
- Keep secrets out of command arguments and logs; use the supported environment, stdin or file-based inputs.
- Preserve unrelated worktree changes and keep credentials, production data and generated runtime files out of patches.
