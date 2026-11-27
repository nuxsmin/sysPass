<!-- Copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org -->

# Documentation index

User installation documentation: <https://doc.syspass.org>.
Repository guidance describes this checkout; accepted architecture decisions
also contain migration targets that require source verification.

| Area | Guidance |
| --- | --- |
| Contribution | [Contributor guide](../CONTRIBUTING.md), [agent responsibilities](../AGENTS.md), [commits](agents/commits.md) |
| Architecture | [Vocabulary](../CONTEXT.md), [domain documentation](agents/domain.md), [ADRs](adr/) |
| Verification | [Testing](agents/testing.md), [environment](agents/testing-environment.md), [linting](agents/linting.md) |
| Browser tests | [Smoke suite](agents/ui-smoke-suite.md), [workflow observations](agents/ui-workflow-tests.md), [harness](../tests/ui/README.md) |
| Inventories | [Application intents](agents/use-case-inventory.md), [UI interactions](agents/ui-interaction-inventory.md) |
| Security | [Disclosure](../SECURITY.md), [implementation guidance](agents/security.md), [master-password rotation](master-password-change.md) |
| Tracker | [Operations](agents/issue-tracker.md), [labels](agents/triage-labels.md) |
| Presentation | [Brand identity](agents/brand-identity.md) |
| Maintenance | [Documentation workflow](agents/documentation.md) |

Historical material: [serialized compatibility handover](handover/2055-serialized-compat-handover.md)
and [trigger research](trigger-visibility-research.md). Preserve historical
decisions and evidence; follow current operational guidance for new work.

## Audit verification, 2026-11-27

The final local-link scan covered 42 Markdown files, including agent tooling and
issue templates, excluding dependencies, Git metadata, plans and generated logs.
Eight links in the UI interaction inventory point to absent, gitignored local
run directories. These remain provenance identifiers and are explicitly marked
as unavailable in clean checkouts; they cannot establish reproducible coverage.

Corrections cover runtime requirements, contributor navigation, domain layout,
tracker execution and authorization rules, wrapper-path assumptions, browser
command working directories, the expanded WRITE selection and dated evidence.
Command and selection references were compared with Composer/package manifests,
the shared runner, tier definitions, Docker definitions and CI configuration.
`git diff --check` passed. Application tests and browser provisioning were not
run for these documentation-only changes. External links, live tracker state,
branch protection and remote CI qualification were not verified.
