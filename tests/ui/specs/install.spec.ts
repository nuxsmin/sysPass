import { test, expect } from '../src/session';

import { installPageContract, installShellContract } from '../src/contracts';
import { ADMIN_CREDENTIALS, FIXTURE } from '../src/fixture';
import { getInstallEnvironment } from '../src/install-env';
import { loginThroughUi } from '../src/login';
import { ActionTracker } from '../src/readiness';

const INSTALL_ROUTE = 'install/index';
const SUBMIT_ROUTE = 'install/install';

interface InstallResponse {
  status?: string;
  description?: string;
}

interface BootPayload {
  status?: string;
  data?: {
    loggedin?: boolean;
    locale?: string;
    csrf?: unknown;
    pki_key?: unknown;
    session_timeout?: number;
    check_updates?: boolean;
    check_notices?: boolean;
  };
}

interface NotificationsPayload {
  status?: string;
  data?: {
    count?: number;
  };
}

test.describe.configure({ mode: 'serial' });

/**
 * The installer is an explicitly stateful exception to the page-load suite.
 * It runs only on the runner-owned second instance and never retries against
 * an installed one (`retries: 0` is enforced by playwright.config.ts).
 */
test('the normal browser installer page exposes the current required fields', async ({
  isolatedPage: { page, context },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const contract = installPageContract();
  tracker.setContract(contract);
  const since = Date.now();

  try {
    await page.goto(`/index.php?r=${INSTALL_ROUTE}`, { waitUntil: 'domcontentloaded' });

    // Scratch-only negative canary: prove a missing current required field is a
    // visible installer failure and cannot be mistaken for Tier A evidence.
    if (process.env.SP_UI_INSTALL_FAULT === 'remove-master-password') {
      await page.evaluate(() => {
        document.querySelector('input[name="masterpassword"]')?.remove();
      });
    }

    await tracker.waitForReady(contract, since);

    const boot = tracker.getJson('bootstrap/getEnvironment') as BootPayload | undefined;

    expect(boot?.status).toBe('OK');
    expect(boot?.data?.loggedin).toBe(false);
    expect(typeof boot?.data?.csrf).toBe('object');
    expect(boot?.data?.csrf).toBeNull();
    expect(typeof boot?.data?.pki_key).toBe('string');
    expect((boot?.data?.pki_key as string).length).toBeGreaterThan(0);

    await expect(page.locator('input[name="adminlogin"]')).toHaveAttribute('required', '');
    await expect(page.locator('input[name="adminpass"]')).toHaveAttribute('required', '');
    await expect(page.locator('input[name="masterpassword"]')).toHaveAttribute('required', '');
    await expect(page.locator('input[name="masterpasswordr"]')).toHaveAttribute('required', '');
    await expect(page.locator('input[name="dbuser"]')).toHaveAttribute('required', '');
    await expect(page.locator('input[name="dbpass"]')).toHaveAttribute('required', '');
    await expect(page.locator('input[name="dbname"]')).toHaveAttribute('required', '');
    await expect(page.locator('input[name="dbhost"]')).toHaveAttribute('required', '');
    await expect(page.locator('#hostingmode')).not.toBeChecked();

    expect(
      FIXTURE.credentials.admin.masterPassword.length,
      'the synthetic Master Password must satisfy the current server minimum length',
    ).toBeGreaterThanOrEqual(11);
  } finally {
    tracker.dispose();
  }
});

test('the server rejects an invalid Master Password and the browser requires its confirmation', async ({
  isolatedPage: { page, context },
}) => {
  test.setTimeout(120_000);

  const install = getInstallEnvironment();
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const contract = installPageContract();
  tracker.setContract(contract);
  const since = Date.now();

  try {
    await page.goto(`/index.php?r=${INSTALL_ROUTE}`, { waitUntil: 'domcontentloaded' });
    await tracker.waitForReady(contract, since);

    await page.waitForFunction(
      "typeof sysPassApp !== 'undefined' && sysPassApp.config && sysPassApp.config.PKI && sysPassApp.config.PKI.AVAILABLE === true",
      undefined,
      { timeout: 15_000 },
    );

    await page.locator('input[name="adminlogin"]').fill(ADMIN_CREDENTIALS.login);
    await page.locator('input[name="adminpass"]').fill(ADMIN_CREDENTIALS.password);
    await page.locator('input[name="adminpass"]').press('Tab');
    await page.locator('input[name="dbuser"]').fill('root');
    await page.locator('input[name="dbpass"]').fill(install.dbPassword);
    await page.locator('input[name="dbpass"]').press('Tab');
    await page.locator('input[name="dbname"]').fill(install.dbName);
    await page.locator('input[name="dbhost"]').fill(install.dbHost);

    await page.locator('input[name="masterpassword"]').fill('short');
    await page.locator('input[name="masterpassword"]').press('Tab');
    await page.locator('input[name="masterpasswordr"]').fill('short');
    await page.locator('input[name="masterpasswordr"]').press('Tab');

    const rejectedResponsePromise = page.waitForResponse(
      (response) => response.url().includes(`r=${SUBMIT_ROUTE}`),
      { timeout: 60_000 },
    );

    await page.locator('#frmInstall button[type="submit"]').click();

    const rejectedResponse = await rejectedResponsePromise;
    const rejectedPayload = (await rejectedResponse.json()) as InstallResponse;

    expect(rejectedResponse.status()).toBe(200);
    expect(rejectedPayload.status, 'a short Master Password must not install').not.toBe('OK');
    expect(rejectedPayload.description).toMatch(/Master password too short/i);

    expect(
      await page.evaluate(() => {
        const repeat = document.querySelector<HTMLInputElement>('input[name="masterpasswordr"]');
        const form = document.querySelector<HTMLFormElement>('#frmInstall');

        if (!repeat || !form) {
          return null;
        }

        repeat.value = '';

        return {
          required: repeat.required,
          formValid: form.checkValidity(),
          valid: repeat.checkValidity(),
        };
      }),
    ).toEqual({ required: true, formValid: false, valid: false });
  } finally {
    tracker.dispose();
  }
});

test('the wizard creates its own database, logs in as the new admin and loads the fresh shell', async ({
  isolatedPage: { page, context },
}) => {
  test.setTimeout(120_000);

  const install = getInstallEnvironment();
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const pageContract = installPageContract();
  tracker.setContract(pageContract);
  const since = Date.now();

  try {
    await page.goto(`/index.php?r=${INSTALL_ROUTE}`, { waitUntil: 'domcontentloaded' });
    await tracker.waitForReady(pageContract, since);

    await page.waitForFunction(
      "typeof sysPassApp !== 'undefined' && sysPassApp.config && sysPassApp.config.PKI && sysPassApp.config.PKI.AVAILABLE === true",
      undefined,
      { timeout: 15_000 },
    );

    await page.locator('input[name="adminlogin"]').fill(ADMIN_CREDENTIALS.login);
    await page.locator('input[name="adminpass"]').fill(ADMIN_CREDENTIALS.password);
    await page.locator('input[name="adminpass"]').press('Tab');

    await page.locator('input[name="masterpassword"]').fill(ADMIN_CREDENTIALS.masterPassword);
    await page.locator('input[name="masterpassword"]').press('Tab');
    await page.locator('input[name="masterpasswordr"]').fill(ADMIN_CREDENTIALS.masterPassword);
    await page.locator('input[name="masterpasswordr"]').press('Tab');

    await page.locator('input[name="dbuser"]').fill('root');
    await page.locator('input[name="dbpass"]').fill(install.dbPassword);
    await page.locator('input[name="dbpass"]').press('Tab');
    await page.locator('input[name="dbname"]').fill(install.dbName);
    await page.locator('input[name="dbhost"]').fill(install.dbHost);
    await page.evaluate((siteLang) => {
      const select = document.querySelector('select[name="sitelang"]') as
        | (HTMLSelectElement & { selectize?: { setValue: (value: string, silent?: boolean) => void } })
        | null;

      if (!select?.selectize) {
        throw new Error('language selectize control is not initialized');
      }

      select.selectize.setValue(siteLang, true);
    }, install.siteLang);

    const installResponsePromise = page.waitForResponse(
      (response) => response.url().includes(`r=${SUBMIT_ROUTE}`),
      { timeout: 60_000 },
    );

    await page.locator('#frmInstall button[type="submit"]').click();

    const installResponse = await installResponsePromise;

    expect(installResponse.status(), 'the installer must return HTTP 200').toBe(200);

    const payload = (await installResponse.json()) as InstallResponse;

    expect(payload.status, 'the installer action must report OK').toBe('OK');

    await page.waitForURL(
      (url) => url.pathname.endsWith('/index.php') && url.searchParams.get('r') === 'login/index',
      { timeout: 30_000 },
    );
    await expect(page.locator('#box-login')).toBeVisible();

    tracker.dispose();

    const shellTracker = new ActionTracker(page, context, { sessionEstablished: false });
    const shellContract = installShellContract();
    shellTracker.setContract(shellContract);

    const shellSince = await loginThroughUi(page, ADMIN_CREDENTIALS);
    shellTracker.setSessionEstablished(true);

    try {
      await shellTracker.waitForReady(shellContract, shellSince);

      const boot = shellTracker.getJson('bootstrap/getEnvironment', shellSince) as
        | BootPayload
        | undefined;

      expect(boot?.status).toBe('OK');
      expect(boot?.data?.loggedin).toBe(true);
      expect(boot?.data?.locale).toBe('en_US');
      expect(boot?.data?.check_updates).toBe(false);
      expect(boot?.data?.check_notices).toBe(false);
      expect(typeof boot?.data?.csrf).toBe('string');
      expect((boot?.data?.csrf as string).length).toBeGreaterThan(0);
      expect(boot?.data?.session_timeout).toBeGreaterThan(0);

      const notifications = shellTracker.getJson('items/notifications', shellSince) as
        | NotificationsPayload
        | undefined;

      expect(notifications?.status).toBe('OK');
      expect(notifications?.data?.count).toBe(0);

      expect(shellTracker.getAction('account/index', shellSince)?.status).toBe(200);

      await expect(page.locator('body')).toHaveClass(/main/);
      await expect(page.locator('#wrap')).toBeVisible();
      await expect(page.locator('#content')).toBeVisible();
      await expect(page.locator('#searchbox')).toBeVisible();
      await expect(page.locator('#res-content .noRes')).toContainText('No records found');
    } finally {
      shellTracker.dispose();
    }
  } finally {
    tracker.dispose();
  }
});
