import { expect, test } from '../src/session';
import { writeFile } from 'node:fs/promises';
import path from 'node:path';

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

test('login logo is centered and balanced at supported viewport sizes', async ({ isolatedPage: { page } }) => {
  await page.goto(`/index.php?r=${LOGIN_ROUTE}`, { waitUntil: 'domcontentloaded' });
  const logo = page.locator('.sp-auth-logo');
  const card = page.locator('#box-login');

  const geometry = await logo.evaluate(async (image: HTMLImageElement) => {
    const response = await fetch(image.src);
    if (!response.ok) throw new Error(`Logo request failed: ${response.status}`);
    const svg = new DOMParser().parseFromString(await response.text(), 'image/svg+xml').documentElement;
    svg.setAttribute('width', '280');
    svg.setAttribute('height', '205');
    svg.setAttribute('style', 'position:absolute;visibility:hidden');
    document.body.appendChild(svg);
    try {
      const children = Array.from(svg.children) as SVGGraphicsElement[];
      const symbolElement = children[0];
      if (!symbolElement || children.length < 2) throw new Error('Logo geometry is incomplete');
      const symbol = symbolElement.getBBox();
      const wordmark = children.slice(1).map((child) => {
        const box = child.getBBox();
        const matrix = child.transform.baseVal.consolidate()?.matrix;
        return { left: box.x + (matrix?.e ?? 0), right: box.x + box.width + (matrix?.e ?? 0), bottom: box.y + box.height };
      });
      return {
        symbolCenter: symbol.x + symbol.width / 2 + (symbolElement.transform.baseVal.consolidate()?.matrix.e ?? 0),
        wordmarkCenter: (Math.min(...wordmark.map((box) => box.left)) + Math.max(...wordmark.map((box) => box.right))) / 2,
        wordmarkBottom: Math.max(...wordmark.map((box) => box.bottom)),
      };
    } finally {
      svg.remove();
    }
  });

  const layouts = [];
  for (const [width, height, label] of [
    [1440, 900, 'desktop'],
    [768, 812, 'tablet'],
    [375, 812, 'mobile'],
    [375, 500, 'short'],
    [720, 450, 'zoom-200-reflow'],
  ] as const) {
    await page.setViewportSize({ width, height });
    const logoBox = await logo.boundingBox();
    const cardBox = await card.boundingBox();
    expect(logoBox && cardBox).toBeTruthy();

    if (process.env.SP_UI_LOGIN_CAPTURE_DIR) {
      await page.screenshot({
        path: path.join(process.env.SP_UI_LOGIN_CAPTURE_DIR, `${process.env.SP_UI_LOGIN_CAPTURE_PREFIX ?? ''}${label}.png`),
        animations: 'disabled',
      });
    }

    layouts.push({
      logoBox: logoBox!,
      cardBox: cardBox!,
      noOverflow: await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth),
    });
  }
  for (const { logoBox, cardBox, noOverflow } of layouts) {
    expect(logoBox.width).toBeCloseTo(140, 0);
    expect(logoBox.x + logoBox.width / 2).toBeCloseTo(cardBox.x + cardBox.width / 2, 0);
    const visibleGap = cardBox.y - (logoBox.y + logoBox.height * geometry.wordmarkBottom / 205);
    expect(visibleGap).toBeGreaterThanOrEqual(24);
    expect(visibleGap).toBeLessThanOrEqual(28);
    expect(logoBox.y).toBeGreaterThanOrEqual(0);
    expect(noOverflow).toBe(true);
  }
  expect(Math.abs(geometry.symbolCenter - geometry.wordmarkCenter)).toBeLessThan(1);

  const cdp = await page.context().newCDPSession(page);
  try {
    await cdp.send('Emulation.setDeviceMetricsOverride', {
      width: 720,
      height: 450,
      deviceScaleFactor: 2,
      mobile: false,
    });
    if (process.env.SP_UI_LOGIN_CAPTURE_DIR) {
      const { data } = await cdp.send('Page.captureScreenshot', { format: 'png' });
      await writeFile(
        path.join(process.env.SP_UI_LOGIN_CAPTURE_DIR, `${process.env.SP_UI_LOGIN_CAPTURE_PREFIX ?? ''}zoom-200-hidpi.png`),
        Buffer.from(data, 'base64'),
      );
    }
    expect(await page.evaluate(() => window.devicePixelRatio)).toBe(2);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await card.scrollIntoViewIfNeeded();
    await expect(page.locator('#btnLogin')).toBeInViewport();
  } finally {
    await cdp.send('Emulation.clearDeviceMetricsOverride');
    await cdp.detach();
  }
});

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
