import { expect, test } from '../src/session';

import { loginContract } from '../src/contracts';
import {
  APP_LOCALE,
  assertRunOwnedBaseUrl,
  BROWSER_LOCALE,
  FIXED_USER_AGENT,
} from '../src/env';
import { ActionTracker } from '../src/readiness';
import { ADMIN_CREDENTIALS } from '../src/fixture';
import { loginThroughUi } from '../src/login';

const LOGIN_ROUTE = 'login/index';

test('the configured fixed user agent wins over the desktop device descriptor', ({}, testInfo) => {
  expect(testInfo.project.use.userAgent).toBe(FIXED_USER_AGENT);
  expect(testInfo.project.use.viewport).toEqual({ width: 1280, height: 720 });
  expect(testInfo.project.use.isMobile).toBe(false);
});

test('login controls remain labeled and vertically aligned at supported widths', async ({ isolatedPage: { page } }) => {
  await page.goto(`/index.php?r=${LOGIN_ROUTE}`, { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#btnLogin')).toHaveText(/sign in/i);
  await expect(page.locator('#user')).toHaveAttribute('autocomplete', 'username');
  await expect(page.locator('#pass')).toHaveAttribute('autocomplete', 'current-password');
  await page.locator('#user').fill('synthetic');
  await expect(page.locator('label[for="user"]')).toBeVisible();
  await page.locator('#user').focus();
  expect(await page.locator('#user').evaluate((element) => getComputedStyle(element).outlineStyle)).not.toBe('none');

  for (const width of [375, 768, 1440]) {
    await page.setViewportSize({ width, height: 812 });
    const user = await page.locator('#user').boundingBox();
    const pass = await page.locator('#pass').boundingBox();
    const button = await page.locator('#btnLogin').boundingBox();
    expect(user && pass && button).toBeTruthy();
    expect(pass!.y).toBeGreaterThan(user!.y);
    expect(button!.y).toBeGreaterThan(pass!.y);
    expect(button!.width).toBeGreaterThanOrEqual(user!.width - 1);
    expect(button!.height).toBeGreaterThanOrEqual(44);
  }
});

test('sign-out keeps the notification separate and retains its redirect', async ({ isolatedPage: { page } }) => {
  await loginThroughUi(page, ADMIN_CREDENTIALS);
  await page.goto('/index.php?r=login/logout', { waitUntil: 'domcontentloaded' });
  const toast = page.locator('#toast-container .toast-warning');
  await expect(toast).toBeVisible();
  await expect(toast).toHaveCSS('opacity', '1');
  await expect(page.locator('.sp-auth-signin a')).toHaveAttribute('href', 'index.php?r=login');

  for (const width of [375, 768, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    const logoBox = await page.locator('.sp-auth-logo').boundingBox();
    const toastBox = await toast.boundingBox();
    expect(logoBox && toastBox).toBeTruthy();
    expect(toastBox!.y + toastBox!.height).toBeLessThanOrEqual(logoBox!.y);
  }

  await toast.locator('.toast-close-button').click();
  await expect(page).toHaveURL(/r=login$/);
});

test('anonymous login page loads with its current boot contract', async ({ baseURL, isolatedPage: { context, page } }) => {
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
