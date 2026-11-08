# UI smoke harness

See `docs/agents/ui-smoke-suite.md` for prerequisites and the execution model.

```sh
npm ci
PLAYWRIGHT_BROWSERS_PATH="$PWD/.browsers" ./node_modules/.bin/playwright install chromium
npm run typecheck
npm run test:unit
npm run test:preflight
```

`npm run test:unit` runs the detector, redaction, reporter, retention and canary
tests. `scripts/ui-smoke.sh` runs the same suite before Playwright, so a
harness regression fails the smoke run before it creates any container.

The real suite is started only through `scripts/ui-smoke.sh`, which creates the isolated instance and supplies the run-owned origin.

## Coverage

Tier A currently covers:

| Surface | Source |
| --- | --- |
| Anonymous login page | `specs/login.spec.ts` |
| Authenticated shell and account list | `specs/shell.spec.ts` |
| Account view, create and edit, opened in-shell and by direct URL | `specs/shell.spec.ts`, contracts in `src/contracts.ts` |
| Session SID rotation and reuse | `specs/shell.spec.ts` |

Detailed readiness contracts, isolation rules and artifact handling are in `docs/agents/ui-smoke-suite.md`.
