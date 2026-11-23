import { parseActionRoute } from './diagnostics.mjs';
import { buildLoginRedirectError } from './login-failure.mjs';

/**
 * Authenticate through the real login form exactly once per seeded suite.
 *
 * The page is only the small subset of the Playwright Page used here, so the
 * failure path can be exercised without a browser. Response bodies are never
 * read: a failed redirect is reported from the route, status and content-type
 * only.
 *
 * @param {any} page
 * @param {{ login: string, password: string }} credential
 * @returns {Promise<number>}
 */
export async function loginThroughUiFlow(page, credential) {
  const bootResponse = page.waitForResponse(
    (response) => parseActionRoute(response.url()) === 'bootstrap/getEnvironment',
    { timeout: 15_000 },
  );
  const loginResponse = page.waitForResponse(
    (response) => parseActionRoute(response.url()) === 'login/login',
    { timeout: 15_000 },
  );

  await page.goto('/index.php?r=login/index', { waitUntil: 'domcontentloaded' });
  await page.locator('#box-login').waitFor({ state: 'visible', timeout: 15_000 });

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
    throw buildLoginRedirectError(response);
  }

  return since;
}
