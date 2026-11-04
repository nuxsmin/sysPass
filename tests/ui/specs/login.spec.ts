import { expect, test } from '@playwright/test';

import {
  APP_LOCALE,
  assertRunOwnedBaseUrl,
  BROWSER_LOCALE,
  FIXED_USER_AGENT,
} from '../src/env';

const BOOT_ROUTE = 'bootstrap/getEnvironment';
const LOGIN_ROUTE = 'login/index';
const IGNORED_RESOURCE = /\/favicon\.ico$/;

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

  const diagnostics: string[] = [];

  context.on('weberror', (error) => {
    diagnostics.push(`page error: ${error.error().message}`);
  });
  page.on('pageerror', (error) => {
    diagnostics.push(`page error: ${error.message}`);
  });
  page.on('console', (message) => {
    if (message.type() === 'error' && !IGNORED_RESOURCE.test(message.location().url)) {
      diagnostics.push(`console error: ${message.text()}`);
    }
  });
  page.on('requestfailed', (request) => {
    if (!IGNORED_RESOURCE.test(request.url())) {
      diagnostics.push(
        `request failed: ${request.method()} ${request.url()} ${request.failure()?.errorText ?? ''}`,
      );
    }
  });

  const bootResponse = page.waitForResponse((response) => {
    const url = new URL(response.url());

    return url.searchParams.get('r') === BOOT_ROUTE;
  });

  await page.goto(`/index.php?r=${LOGIN_ROUTE}`, { waitUntil: 'domcontentloaded' });

  await expect(page.locator('#box-login')).toBeVisible();
  await expect(page.locator('#box-login form#frmLogin')).toHaveAttribute('method', 'post');
  await expect(page.locator('#frmLogin')).toHaveAttribute('data-onsubmit', 'main/login');
  await expect(page.locator('#frmLogin')).toHaveAttribute('data-route', 'login/login');
  await expect(page.locator('#user')).toBeVisible();
  await expect(page.locator('#pass')).toBeVisible();
  await expect(page.locator('#btnLogin')).toBeVisible();
  await expect(page.locator('#frmLogin input[name="isAjax"]')).toHaveValue('1');
  await expect(page.locator('.errors.round .msg-critical')).toHaveCount(0);

  const response = await bootResponse;

  expect(response.status()).toBe(200);

  const boot = (await response.json()) as {
    status?: string;
    data?: {
      csrf?: unknown;
      locale?: unknown;
      loggedin?: unknown;
      pki_key?: unknown;
    };
  };

  expect(boot.status).toBe('OK');
  expect(boot.data?.locale).toBe(APP_LOCALE);
  expect(boot.data?.loggedin).toBe(false);
  expect(boot.data?.csrf).toBeNull();
  expect(typeof boot.data?.pki_key).toBe('string');
  expect((boot.data?.pki_key as string).length).toBeGreaterThan(0);

  await expect(page.locator('#wrap-loading')).toBeHidden();
  await page.waitForTimeout(300);

  expect(diagnostics).toEqual([]);
});
