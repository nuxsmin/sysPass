import fs from 'node:fs';
import path from 'node:path';
import { test, expect } from '../src/session';

import { installPageContract, installShellContract } from '../src/contracts';
import { ADMIN_CREDENTIALS, FIXTURE } from '../src/fixture';
import { getInstallEnvironment } from '../src/install-env';
import { loginThroughUi } from '../src/login';
import { ActionTracker } from '../src/readiness';

const INSTALL_ROUTE = 'install/index';
const SUBMIT_ROUTE = 'install/install';
const SCREENSHOT_DIR = process.env.SP_UI_SCREENSHOT_DIR ?? '';

async function maybeCapture(page: import('@playwright/test').Page, name: string): Promise<void> {
  if (!SCREENSHOT_DIR) {
    return;
  }
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
  await page.screenshot({ path: path.join(SCREENSHOT_DIR, name), fullPage: true });
}

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

test('the installer layout fits supported viewports cleanly and enforces neutral states and password controls', async ({
  isolatedPage: { page, context },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const contract = installPageContract();
  tracker.setContract(contract);
  const since = Date.now();

  try {
    await page.goto(`/index.php?r=${INSTALL_ROUTE}`, { waitUntil: 'domcontentloaded' });
    await tracker.waitForReady(contract, since);

    // 1. Neutral initial states, localization default (en_US), and documented defaults
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
    await expect(page.locator('#sel-sitelang')).toHaveValue('en_US');
    await expect(page.locator('#sel-sitelang + .selectize-control .item')).toContainText('English');
    await expect(page.locator('#sitelang-hint')).toContainText('after installation');
    await expect(
      page.locator('#sel-sitelang + .selectize-control .selectize-input input'),
    ).toHaveAttribute('aria-describedby', 'sitelang-hint');

    await expect(page.locator('#adminlogin')).toHaveValue('admin');
    await expect(page.locator('#dbuser')).toHaveValue('root');
    await expect(page.locator('#dbname')).toHaveValue('syspass');
    await expect(page.locator('#dbhost')).toHaveValue('localhost');
    await expect(page.locator('#masterpassword-hint')).toContainText('at least 11 characters');
    await expect(page.locator('#dbuser-hint')).toContainText('admin rights');
    await expect(page.locator('#hostingmode-hint')).toBeVisible();

    for (const fieldName of ['adminpass', 'masterpassword', 'masterpasswordr', 'dbpass'] as const) {
      const input = page.locator(`input[name="${fieldName}"]`);
      const fieldWrap = page.locator('.mdl-textfield').filter({ has: input });
      const inputId = await input.getAttribute('id');

      expect(inputId).toBeTruthy();
      await expect(page.locator(`label[for="${inputId}"]`)).toBeVisible();
      await expect(input).toHaveValue('');
      await expect(input).not.toHaveAttribute('aria-invalid', 'true');
      await expect(fieldWrap).not.toHaveClass(/\bis-invalid\b/);
    }

    await expect(page.locator('input[name="adminpass"]')).toHaveAttribute(
      'autocomplete',
      'new-password',
    );
    await expect(page.locator('input[name="masterpassword"]')).toHaveAttribute(
      'autocomplete',
      'new-password',
    );
    await expect(page.locator('input[name="masterpasswordr"]')).toHaveAttribute(
      'autocomplete',
      'new-password',
    );
    await expect(page.locator('input[name="masterpasswordr"]')).toHaveAttribute(
      'aria-describedby',
      'masterpasswordR-error',
    );
    await expect(page.locator('#dbpass')).toHaveAttribute('autocomplete', 'off');

    await expect(page.locator('#install-feedback')).toBeHidden();
    await expect(page.locator('#install-running-status')).toBeHidden();
    await expect(page.locator('.sp-password-match-error')).toBeHidden();

    // 2. Centered flow surface, zero horizontal overflow, and footer clearance across supported viewports + 200% zoom reflow
    for (const [width, height] of [
      [1440, 900],
      [1440, 600],
      [768, 1024],
      [375, 812],
      [375, 600],
      [720, 450],
    ] as const) {
      await page.setViewportSize({ width, height });

      const flowSurface = page.locator('#actions.sp-flow-page');
      const installBtn = page.locator('#btnInstall');
      const footer = page.locator('#wrap > footer');
      await expect(flowSurface).toBeVisible();

      const box = await flowSurface.boundingBox();
      const btnBox = await installBtn.boundingBox();
      const footerBox = await footer.boundingBox();
      expect(box).toBeTruthy();
      expect(btnBox).toBeTruthy();
      expect(footerBox).toBeTruthy();

      if (width >= 1024) {
        expect(box!.width).toBeLessThanOrEqual(640);
        expect(box!.x + box!.width / 2).toBeCloseTo(width / 2, 0);
      }

      expect(box!.x).toBeGreaterThanOrEqual(0);
      expect(box!.x + box!.width).toBeLessThanOrEqual(width + 1);
      expect(
        footerBox!.y,
        `footer must sit below #actions at ${width}x${height}`,
      ).toBeGreaterThanOrEqual(box!.y + box!.height - 1);
      expect(
        btnBox!.y + btnBox!.height,
        `footer must not obscure #btnInstall at ${width}x${height}`,
      ).toBeLessThanOrEqual(footerBox!.y + 1);
      expect(
        await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth),
        `installer must not overflow horizontally at ${width}x${height}`,
      ).toBe(true);

      if (width === 1440 && height === 900) {
        await maybeCapture(page, 'install-1440.png');
      } else if (width === 768 && height === 1024) {
        await maybeCapture(page, 'install-768.png');
      } else if (width === 375 && height === 812) {
        await maybeCapture(page, 'install-375.png');
      }
    }

    const cdp = await context.newCDPSession(page);
    try {
      await cdp.send('Emulation.setDeviceMetricsOverride', {
        width: 720,
        height: 450,
        deviceScaleFactor: 2,
        mobile: false,
      });

      expect(await page.evaluate(() => window.devicePixelRatio)).toBe(2);
      expect(
        await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth),
        'installer must not overflow horizontally at 200% zoom reflow',
      ).toBe(true);
      await page.locator('#btnInstall').scrollIntoViewIfNeeded();
      await expect(page.locator('#btnInstall')).toBeInViewport();
      await maybeCapture(page, 'install-zoom200.png');
    } finally {
      await cdp.send('Emulation.clearDeviceMetricsOverride');
      await cdp.detach();
    }

    await page.setViewportSize({ width: 1280, height: 720 });

    // 3. Password visibility toggles update input type & aria-pressed without clipboard copy, and menus expose accessible roles
    for (const fieldName of ['adminpass', 'masterpassword', 'dbpass'] as const) {
      const input = page.locator(`input[name="${fieldName}"]`);
      const control = page.locator('.form-control').filter({ has: input });
      const toggle = control.locator('button.sp-password-toggle.showpass');

      await expect(input).toHaveAttribute('type', 'password');
      await expect(toggle).toHaveAttribute('aria-pressed', 'false');
      await expect(toggle).not.toHaveClass(/\bclip-pass-field\b/);

      await toggle.click();
      await expect(input).toHaveAttribute('type', 'text');
      await expect(toggle).toHaveAttribute('aria-pressed', 'true');

      await toggle.click();
      await expect(input).toHaveAttribute('type', 'password');
      await expect(toggle).toHaveAttribute('aria-pressed', 'false');

      if (fieldName !== 'dbpass') {
        const menuTrigger = control.locator('button[id^="menu-password-"]');
        const menuList = control.locator('ul.mdl-menu');
        await expect(menuTrigger).toHaveAttribute('aria-haspopup', 'menu');
        await expect(menuList).toHaveAttribute('role', 'menu');
        await expect(menuList.locator('li[role="menuitem"]').first()).toBeAttached();
      }
    }

    // 4. Master Password repeat mismatch sets customValidity, blocks Enter submit, and clears cleanly when matched
    const masterInput = page.locator('input[name="masterpassword"]');
    const repeatInput = page.locator('input[name="masterpasswordr"]');
    const repeatWrap = page.locator('.mdl-textfield').filter({ has: repeatInput });
    const matchError = page.locator('.sp-password-match-error');

    await masterInput.fill('master-secret-11');
    await repeatInput.fill('different-secret');

    await expect(matchError).toBeVisible();
    await expect(matchError).toContainText('Passwords do not match');
    await expect(repeatInput).toHaveAttribute('aria-invalid', 'true');
    await expect(repeatWrap).toHaveClass(/\bis-invalid\b/);
    expect(await repeatInput.evaluate((el: HTMLInputElement) => el.validity.customError)).toBe(true);

    await repeatInput.press('Enter');
    await expect(page.locator('#install-running-status')).toBeHidden();

    await repeatInput.fill('master-secret-11');

    await expect(matchError).toBeHidden();
    await expect(repeatInput).not.toHaveAttribute('aria-invalid', 'true');
    await expect(repeatWrap).not.toHaveClass(/\bis-invalid\b/);
    expect(await repeatInput.evaluate((el: HTMLInputElement) => el.validity.customError)).toBe(false);
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
