import { expect, test } from '@playwright/test';

import { loginContract } from '../src/contracts';
import {
  APP_LOCALE,
  assertRunOwnedBaseUrl,
  BROWSER_LOCALE,
  FIXED_USER_AGENT,
} from '../src/env';
import { ActionTracker } from '../src/readiness';

const LOGIN_ROUTE = 'login/index';

test('the configured fixed user agent wins over the desktop device descriptor', ({}, testInfo) => {
  expect(testInfo.project.use.userAgent).toBe(FIXED_USER_AGENT);
  expect(testInfo.project.use.viewport).toEqual({ width: 1280, height: 720 });
  expect(testInfo.project.use.isMobile).toBe(false);
});

test('anonymous login page loads with its current boot contract', async ({ baseURL, context, page }) => {
  if (!baseURL) {
    throw new Error('UI smoke base URL is missing');
  }

  assertRunOwnedBaseUrl(baseURL);
  expect(await page.evaluate(() => navigator.language)).toBe(BROWSER_LOCALE);
  expect(await page.evaluate(() => navigator.userAgent)).toBe(FIXED_USER_AGENT);

  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const contract = loginContract();
  tracker.setContract(contract);
  const since = Date.now();

  try {
    await page.goto(`/index.php?r=${LOGIN_ROUTE}`, { waitUntil: 'domcontentloaded' });
    await tracker.waitForReady(contract, since);

    const boot = tracker.getJson('bootstrap/getEnvironment') as
      | {
          status?: string;
          data?: {
            csrf?: unknown;
            locale?: unknown;
            loggedin?: unknown;
            pki_key?: unknown;
          };
        }
      | undefined;

    expect(boot?.status).toBe('OK');
    expect(boot?.data?.locale).toBe(APP_LOCALE);
    expect(boot?.data?.loggedin).toBe(false);
    expect(boot?.data?.csrf).toBeNull();
    expect(typeof boot?.data?.pki_key).toBe('string');
    expect((boot?.data?.pki_key as string).length).toBeGreaterThan(0);
  } finally {
    tracker.dispose();
  }
});
