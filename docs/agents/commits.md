# Commit Workflow

Read this guide before creating a commit. The authorization and execution safeguards in [AGENTS.md](../../AGENTS.md) apply; follow [the command kit guide](../../.ai-cli/README.md) for CLI permissions and wrappers.

## Message Format

Use [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/), consistent with the repository's recent history:

```text
<type>[optional scope][!]: <description>
```

- Use `feat` for new functionality, `fix` for bug fixes, `docs` for documentation, `test` for tests, `refactor` for behavior-preserving restructuring, `perf` for performance improvements, `ci` for CI configuration, `build` for build tooling or dependencies, and `chore` for other maintenance.
- Use a short optional scope identifying the affected subsystem, such as `account`, `crypt` or `session`.
- Write a concise, imperative description of the change. Add a body when the motivation or compatibility implications need explanation; reference related issues when applicable.
- Mark breaking changes with `!` before the colon or a `BREAKING CHANGE:` footer explaining the incompatibility and migration requirements.
- Keep each commit focused on one logical change. For example: `docs(agents): document red-green development workflow`.

## Pre-Commit Review

Before committing, inspect the working-tree status, diff and recent commit history. Stage only intended changes, exclude secrets and unrelated work, and review the staged diff before creating the commit.
