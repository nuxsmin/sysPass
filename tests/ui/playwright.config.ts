import path from 'node:path';

import { defineConfig, devices } from '@playwright/test';

import {
  BROWSER_LOCALE,
  FIXED_USER_AGENT,
  getVerifiedBaseUrl,
} from './src/env';

const outputDir = process.env.SP_UI_OUTPUT_DIR ?? path.join(import.meta.dirname, 'test-results');
const chromiumExecutable = process.env.SP_UI_CHROMIUM_EXECUTABLE;
const launchOptions = chromiumExecutable ? { executablePath: chromiumExecutable } : undefined;

export default defineConfig({
  testDir: './specs',
  outputDir,
  fullyParallel: false,
  workers: 1,
  retries: 0,
  forbidOnly: process.env.CI === 'true',
  reporter: [
    ['list'],
    ['junit', { outputFile: path.join(outputDir, 'junit.xml') }],
  ],
  timeout: 30_000,
  expect: {
    timeout: 10_000,
  },
  use: {
    baseURL: getVerifiedBaseUrl(),
    locale: BROWSER_LOCALE,
    userAgent: FIXED_USER_AGENT,
    trace: 'off',
    video: 'off',
    screenshot: 'off',
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
