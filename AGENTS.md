## CLI commands

AI sessions MUST source `.ai-cli/env.sh` first (`source .ai-cli/env.sh`).
`gh` MUST be invoked as `host-spawn gh` via `$GH_BIN` — never bare `gh`.
Run mutations via `.ai-cli/bin/ai-run -- <cmd>`, use templates in `.ai-cli/command-templates/`, obey `.ai-cli/allowed-commands.txt`. See `.ai-cli/README.md`.

## PHP runtime

PHP MUST run inside Docker, never from the host. After `source .ai-cli/env.sh`, use `$PHP_BIN` (`.ai-cli/bin/php`), which runs `php-testing-composer:latest` (PHP 8.3 + extensions, Composer 2.7) with the repo bind-mounted:

```sh
"$PHP_BIN" -v
"$PHP_BIN" vendor/bin/phpunit -c ./tests/phpunit.xml --no-coverage
"$PHP_BIN" vendor/bin/phpstan analyse --level 6 lib
```

Never call bare `php`, `vendor/bin/*` directly, or a composer script that shells out to `php`. Build/rebuild the image from `.ai-cli/docker/` with `make -C .ai-cli/docker build` (ask the human first). See `.ai-cli/README.md` → “PHP (always via Docker)”.

## MCP servers

Prefer enabled MCP servers over CLI equivalents for the operations they cover. The `github` server's tools (`github_*` — issues, PRs, search, reviews) come first for GitHub work. If the server is unavailable, a tool is denied, or it lacks a capability, fall back to the `gh` CLI with the constraints in `## CLI commands` above (`$GH_BIN`, `.ai-cli` rules).

## Agent skills

### Issue tracker

Issues and specs live in this repo's GitHub Issues — use `github_*` MCP tools first, else the `gh` CLI (`## CLI commands`). See `docs/agents/issue-tracker.md`.

### Triage labels

The five canonical defaults: `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: one `CONTEXT.md` plus `docs/adr/` at the repo root. See `docs/agents/domain.md`.

### Applications incubator

New applications live self-contained under `apps/<name>/` — see `apps/README.md`. To migrate one to its own repository, run `scripts/graduate.sh` (dry-run first; creates a repo and pushes, so confirm with the user before the real run).