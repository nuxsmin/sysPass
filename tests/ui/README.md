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
