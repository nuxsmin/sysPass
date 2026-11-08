import path from 'node:path';

import { defineConfig } from '@playwright/test';

const outputDir = process.env.SP_UI_OUTPUT_DIR ?? path.join(import.meta.dirname, 'test-results');

process.env.PLAYWRIGHT_NO_COPY_PROMPT = '1';

export default defineConfig({
  testDir: '.',
  testMatch: /\/retry-canary\.spec\.ts$/,
  outputDir,
  fullyParallel: false,
  workers: 1,
  retries: 1,
  failOnFlakyTests: true,
  reporter: [['list'], [path.join(import.meta.dirname, '..', '..', 'src', 'reporter.mjs'), { outputDir }]],
  timeout: 15_000,
  use: {
    baseURL: 'http://127.0.0.1:1',
    trace: 'off',
    video: 'off',
    screenshot: 'off',
    serviceWorkers: 'block',
  },
  projects: [{ name: 'chromium' }],
});
