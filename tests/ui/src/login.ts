import { expect, type Page } from '@playwright/test';

import type { FixtureCredential } from './fixture';
import { parseActionRoute } from './readiness';

/**
 * Authenticate through the real login form exactly once per seeded suite.
 *
 * Returns the timestamp taken immediately before submitting the form. Callers
 * use it as the lower bound for shell actions that are issued after the login
 * redirect, so the anonymous login boot is not mistaken for the shell boot.
 */
export async function loginThroughUi(page: Page, credential: FixtureCredential): Promise<number> {
  const bootResponse = page.waitForResponse(
    (response) => parseActionRoute(response.url()) === 'bootstrap/getEnvironment',
    { timeout: 15_000 },
  );
  const loginResponse = page.waitForResponse(
    (response) => parseActionRoute(response.url()) === 'login/login',
    { timeout: 15_000 },
  );

  await page.goto('/index.php?r=login/index', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#box-login')).toBeVisible();

  const boot = await bootResponse;

  if (boot.status() !== 200) {
    throw new Error(`Login boot returned HTTP ${boot.status()}`);
  }

  await page.waitForFunction(
    "typeof sysPassApp !== 'undefined' && sysPassApp.config && sysPassApp.config.PKI && sysPassApp.config.PKI.AVAILABLE === true",
    undefined,
    { timeout: 15_000 },
  );

  await page.locator('#user').fill(credential.login);
  await page.locator('#pass').fill(credential.password);
  await page.locator('#pass').press('Tab');

  const since = Date.now();

  await page.locator('#btnLogin').click();

  const response = await loginResponse;

  if (response.status() !== 200) {
    throw new Error(`Login failed with HTTP ${response.status()}`);
  }

  try {
    await page.waitForURL(
      (url) => url.pathname.endsWith('/index.php') && url.searchParams.get('r') === 'index',
      { timeout: 15_000 },
    );
  } catch {
    let detail = '';

    try {
      detail = (await response.text()).slice(0, 300).replace(/\s+/g, ' ');
    } catch {
      detail = 'login response body was unavailable after the failed redirect';
    }

    throw new Error(`Login did not redirect to the shell: ${detail}`);
  }

  return since;
}
