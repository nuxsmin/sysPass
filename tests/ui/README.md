# UI smoke harness

See `docs/agents/ui-smoke-suite.md` for prerequisites and the execution model.

```sh
npm ci
PLAYWRIGHT_BROWSERS_PATH="$PWD/.browsers" ./node_modules/.bin/playwright install chromium
npm run typecheck
npm run test:preflight
```

The real suite is started only through `scripts/ui-smoke.sh`, which creates the isolated instance and supplies the run-owned origin.
