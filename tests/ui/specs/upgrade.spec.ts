import fs from 'node:fs';
import path from 'node:path';
import type { PageContract } from '../src/contracts';
import { ActionTracker } from '../src/readiness';
import { expect, test } from '../src/session';

const UPGRADE_ROUTE = 'upgrade/index';
const SUBMIT_ROUTE = 'upgrade/upgrade';
const SCREENSHOT_DIR = process.env.SP_UI_SCREENSHOT_DIR ?? '';

interface UpgradeResponse {
  status?: string;
  description?: string;
}

function upgradePageContract(): PageContract {
  return {
    name: 'upgrade page',
    actions: [
      { route: 'bootstrap/getEnvironment', format: 'json' },
      {
        route: 'upgrade/index',
        format: 'html',
        content: ['id="frmUpgrade"', 'data-action-route="upgrade/upgrade"', 'id="chkConfirm"'],
      },
    ],
    conditions: [
      { kind: 'visible', selector: '#actions.sp-flow-page' },
      { kind: 'visible', selector: '#frmUpgrade' },
      { kind: 'attribute', selector: '#frmUpgrade', name: 'data-onsubmit', value: 'main/upgrade' },
      { kind: 'attribute', selector: '#frmUpgrade', name: 'data-action-route', value: 'upgrade/upgrade' },
      { kind: 'visible', selector: '#key' },
      { kind: 'count', selector: '#chkConfirm', min: 1 },
      { kind: 'visible', selector: '#btnChange' },
      { kind: 'hidden', selector: '#wrap-loading' },
    ],
  };
}

async function maybeCapture(page: import('@playwright/test').Page, name: string): Promise<void> {
  if (!SCREENSHOT_DIR) {
    return;
  }
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
  await page.screenshot({ path: path.join(SCREENSHOT_DIR, name), fullPage: true });
}

test('the upgrade page fits supported viewports, keeps neutral initial states, and recovers cleanly after a wrong-key error', async ({
  isolatedPage: { page, context },
}) => {
  const consoleErrors: string[] = [];
  page.on('console', (msg) => {
    if (msg.type() === 'error') {
      consoleErrors.push(msg.text());
    }
  });

  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const contract = upgradePageContract();
  tracker.setContract(contract);
  const since = Date.now();

  try {
    await page.goto(`/index.php?r=${UPGRADE_ROUTE}`, { waitUntil: 'domcontentloaded' });
    await tracker.waitForReady(contract, since);

    const keyInput = page.locator('#key');
    const keyWrap = page.locator('.mdl-textfield').filter({ has: keyInput });
    const confirmInput = page.locator('#chkConfirm');
    const confirmWrap = page.locator('.sp-upgrade-confirm');
    const feedback = page.locator('#upgrade-feedback');
    const runningStatus = page.locator('#upgrade-running-status');
    const submitButton = page.locator('#btnChange');
    const warningAlert = page.locator('.sp-alert--warning');

    // 1. Accurate warning copy & neutral initial field/button states
    await expect(warningAlert).toBeVisible();
    await expect(warningAlert).toContainText(
      'An administrator must verify a full backup and enter the upgradekey security code from config.xml before updating.',
    );
    await expect(warningAlert).not.toContainText('Please contact to the administrator');
    await expect(page.locator('#key-hint')).toContainText('upgradekey');
    await expect(keyInput).toHaveAttribute('aria-describedby', 'key-hint');
    await expect(keyInput).toHaveValue('');
    await expect(keyInput).not.toHaveAttribute('aria-invalid', 'true');
    await expect(keyWrap).not.toHaveClass(/\bis-invalid\b/);
    await expect(confirmInput).not.toBeChecked();
    await expect(confirmWrap).not.toHaveClass(/\bis-invalid\b/);
    await expect(submitButton).toHaveAttribute('aria-disabled', 'true');
    await expect(submitButton).toHaveJSProperty('disabled', false);
    await expect(feedback).toBeHidden();
    await expect(runningStatus).toBeHidden();

    // 2. Centered flow surface, zero horizontal overflow, and footer clearance across viewports + 200% zoom reflow
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
      const footer = page.locator('#wrap > footer');
      await expect(flowSurface).toBeVisible();

      const box = await flowSurface.boundingBox();
      const submitBox = await submitButton.boundingBox();
      const footerBox = await footer.boundingBox();
      expect(box).toBeTruthy();
      expect(submitBox).toBeTruthy();
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
        submitBox!.y + submitBox!.height,
        `footer must not obscure #btnChange at ${width}x${height}`,
      ).toBeLessThanOrEqual(footerBox!.y + 1);
      expect(
        await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth),
        `upgrade page must not overflow horizontally at ${width}x${height}`,
      ).toBe(true);

      if (width === 1440 && height === 900) {
        await maybeCapture(page, 'upgrade-1440.png');
      } else if (width === 768 && height === 1024) {
        await maybeCapture(page, 'upgrade-768.png');
      } else if (width === 375 && height === 812) {
        await maybeCapture(page, 'upgrade-375.png');
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
        'upgrade page must not overflow horizontally at 200% zoom reflow',
      ).toBe(true);
      await submitButton.scrollIntoViewIfNeeded();
      await expect(submitButton).toBeInViewport();
      await maybeCapture(page, 'upgrade-zoom200.png');
    } finally {
      await cdp.send('Emulation.clearDeviceMetricsOverride');
      await cdp.detach();
    }

    await page.setViewportSize({ width: 1280, height: 720 });
  } finally {
    // Dispose ActionTracker before intentionally triggering an ERROR response from upgrade/upgrade
    tracker.dispose();
  }

  // 3. Missing security code & unchecked backup acknowledgement validation states
  const keyInput = page.locator('#key');
  const keyWrap = page.locator('.mdl-textfield').filter({ has: keyInput });
  const confirmInput = page.locator('#chkConfirm');
  const confirmWrap = page.locator('.sp-upgrade-confirm');
  const feedback = page.locator('#upgrade-feedback');
  const runningStatus = page.locator('#upgrade-running-status');
  const submitButton = page.locator('#btnChange');

  await submitButton.click({ force: true });
  await expect(keyInput).toHaveAttribute('aria-invalid', 'true');
  await expect(keyWrap).toHaveClass(/\bis-invalid\b/);
  await expect(page.locator('#orrsDiag_content')).toHaveCount(0);

  await keyInput.fill('wrong-security-code');
  await expect(submitButton).toHaveAttribute('aria-disabled', 'true');
  await submitButton.click({ force: true });
  await expect(confirmInput).toHaveAttribute('aria-invalid', 'true');
  await expect(confirmWrap).toHaveClass(/\bis-invalid\b/);
  await expect(page.locator('#orrsDiag_content')).toHaveCount(0);
  expect(
    consoleErrors.filter((msg) => /not focusable/i.test(msg)),
    'MDL checkbox validation must not trigger a non-focusable form control console error',
  ).toEqual([]);

  // 4. Confirmation modal -> wrong security code error -> focus & field recovery
  await confirmWrap.click();
  await expect(confirmInput).toBeChecked();
  await expect(confirmInput).not.toHaveAttribute('aria-invalid', 'true');
  await expect(confirmWrap).not.toHaveClass(/\bis-invalid\b/);
  await expect(submitButton).not.toHaveAttribute('aria-disabled', 'true');
  await expect(submitButton).not.toBeDisabled();

  await submitButton.click();

  const modal = page.locator('#orrsDiag_content');
  await expect(modal).toBeVisible();
  await expect(modal).toHaveAttribute('role', 'dialog');
  await expect(modal).toHaveAttribute('aria-modal', 'true');

  const upgradeResponsePromise = page.waitForResponse(
    (response) => response.url().includes(`r=${SUBMIT_ROUTE}`),
    { timeout: 15_000 },
  );

  await page.locator('#orrsDiag #positive').click();

  const upgradeResponse = await upgradeResponsePromise;
  expect(upgradeResponse.status()).toBe(200);
  const payload = (await upgradeResponse.json()) as UpgradeResponse;

  expect(payload.status).toBe('ERROR');
  expect(payload.description).toMatch(/Wrong security code/i);

  await expect(runningStatus).toBeHidden();
  await expect(submitButton).toHaveJSProperty('disabled', false);
  await expect(submitButton).toHaveAttribute('aria-disabled', 'true');
  await expect(submitButton).not.toHaveAttribute('aria-busy', 'true');
  await expect(feedback).toBeVisible();
  await expect(feedback).toHaveClass(/\bsp-alert--error\b/);
  await expect(feedback).toContainText('Wrong security code');

  await expect(keyInput).toHaveValue('');
  await expect(keyInput).toHaveAttribute('aria-invalid', 'true');
  await expect(keyWrap).toHaveClass(/\bis-invalid\b/);
  await expect(keyInput).toBeFocused();
  await maybeCapture(page, 'upgrade-error.png');

  // 5. Typing a new value clears the invalid marker, hides stale error, and re-enables Update
  await keyInput.fill('retry-security-code');
  await expect(keyInput).not.toHaveAttribute('aria-invalid', 'true');
  await expect(keyWrap).not.toHaveClass(/\bis-invalid\b/);
  await expect(feedback).toBeHidden();
  await expect(submitButton).not.toHaveAttribute('aria-disabled', 'true');
  await expect(submitButton).not.toBeDisabled();
});

