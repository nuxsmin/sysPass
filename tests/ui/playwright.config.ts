import path from 'node:path';

import { defineConfig, devices } from '@playwright/test';

import { resolveCapturePolicy, TELEMETRY_ENVIRONMENT } from './src/capture-policy.mjs';
import {
  BROWSER_LOCALE,
  FIXED_USER_AGENT,
  getVerifiedBaseUrl,
} from './src/env';
import { specsForTier, normalizeTier } from './src/tiers.mjs';

const outputDir = process.env.SP_UI_OUTPUT_DIR ?? path.join(import.meta.dirname, 'test-results');
const chromiumExecutable = process.env.SP_UI_CHROMIUM_EXECUTABLE;
const launchOptions = chromiumExecutable ? { executablePath: chromiumExecutable } : undefined;
const syntheticRun = Boolean(process.env.SP_UI_RUN_ROOT && process.env.SP_UI_RUN_ID);
const capture = resolveCapturePolicy(process.env, { syntheticRun });
const tier = normalizeTier(process.env.SP_UI_TIER ?? 'A');

const explicitSpecs = (process.env.SP_UI_SPECS ?? '')
  .split(/[\s,]+/)
  .filter(Boolean);
const selectedSpecs = (explicitSpecs.length > 0 ? explicitSpecs : specsForTier(tier)).map(
  (spec) => `**/${spec}`,
);

process.env.DO_NOT_TRACK = TELEMETRY_ENVIRONMENT.DO_NOT_TRACK;
process.env.PLAYWRIGHT_CLI_ANALYTICS = TELEMETRY_ENVIRONMENT.PLAYWRIGHT_CLI_ANALYTICS;
process.env.PLAYWRIGHT_NO_COPY_PROMPT = '1';

if (capture.traceError) {
  throw new Error(capture.traceError);
}

export default defineConfig({
  testDir: './specs',
  ...(selectedSpecs.length > 0 ? { testMatch: selectedSpecs } : {}),
  outputDir,
  fullyParallel: false,
  workers: 1,
  retries: capture.retries,
  failOnFlakyTests: capture.failOnFlakyTests,
  forbidOnly: process.env.CI === 'true',
  reporter: [
    ['list'],
    [path.join(import.meta.dirname, 'src', 'reporter.mjs'), { outputDir }],
  ],
  timeout: 30_000,
  expect: {
    timeout: 10_000,
  },
  use: {
    baseURL: getVerifiedBaseUrl(),
    locale: BROWSER_LOCALE,
    userAgent: FIXED_USER_AGENT,
    trace: capture.trace as 'off' | 'on',
    video: capture.video as 'off',
    screenshot: capture.screenshot as 'off',
    serviceWorkers: 'block',
    actionTimeout: 10_000,
    navigationTimeout: 20_000,
    ...(launchOptions ? { launchOptions } : {}),
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'], userAgent: FIXED_USER_AGENT },
    },
  ],
});
