<!-- Copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org -->

# Documentation maintenance

Read this workflow for implementations and documentation reviews.

1. Read issue acceptance criteria, `CONTEXT.md` and relevant ADRs. Trace affected
   entry points, use cases, ports, adapters and tests. Distinguish implemented
   behavior from accepted migration targets.
2. Search repository Markdown for changed symbols, paths, commands and behavior.
   Identify affected documents using the table below before implementation.
3. Update guidance in the same change. Link authoritative configuration instead
   of copying transient counts. Preserve historical ADR context; record changed
   decisions in a new ADR and link the superseding decision.
4. Verify changed local links and source references. Compare command examples
   with scripts and CI. Execute relevant checks within repository permissions.
   Inspection of a command is not evidence that it ran successfully.
5. Report updated documents, actual verification and unresolved prerequisites.
   When no update is needed, identify reviewed guidance and explain why it remains
   accurate.

| Change | Sources and documentation to compare |
| --- | --- |
| Terminology and boundaries | `CONTEXT.md`, ADRs, architecture tests, DI definitions |
| Use cases and DTOs | ADR-0003, use-case inventory, use cases and boundary callers |
| Dependencies and runtime | `README.md`, `composer.json`, lockfile, Docker definitions, CI |
| Testing and build commands | Testing guides, `package.json`, `scripts/`, PHPUnit configuration, workflows |
| Routes and rendering | ADR-0006/0007/0011/0012, UI guides and interaction inventory, controllers, templates, JavaScript |
| Errors and events | ADR-0002, exception codes, boundary adapters, translation catalogs |
| Persisted classes | ADR-0005, serialized compatibility fixtures and tests |
| Authorization and secrets | Security guidance, affected entry points, successful and denied-access tests |
| Agent tooling | `AGENTS.md`, `.ai-cli/README.md`, wrappers and allowlist |

For a broad review, inventory the scope first and check navigation, local links,
prerequisites, command examples and contradictory claims. Record unresolved items
with their source and reason. Historical research and handovers describe their
original checkout; recheck counts and status before reusing them.
