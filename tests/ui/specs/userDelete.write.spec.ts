import type { Request as PlaywrightRequest } from '@playwright/test';

import { FIXTURE } from '../src/fixture';
import { loginThroughUi } from '../src/login';
import { ActionTracker, parseActionRoute } from '../src/readiness';
import { expect, test } from '../src/session';
import { runTierBIndex, TIER_B_INDEX_CASES } from '../src/tier-b';

test('User delete reconciles the ERROR outcome', async ({ sessionPage: { page, context, session } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const id = String(FIXTURE.ids.users.restricted);
  const name = FIXTURE.names.restrictedProfile;
  const message = 'Synthetic user error';
  const refreshes: PlaywrightRequest[] = [];
  const onRequest = (request: PlaywrightRequest) => {
    if (request.method() === 'GET' && parseActionRoute(request.url()) === 'accessManager/index') refreshes.push(request);
  };

  try {
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.accessManager);
    const security = await page.evaluate(() => (0, eval)('sysPassApp').sk.get());
    const grid = page.locator('#data-table-tblUsers');
    const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
    const panelId = await panel.getAttribute('id');
    const tabIndex = await panel.getAttribute('data-tab-index');
    await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();
    const row = grid.locator(`tr[data-item-id="${id}"]`);
    await expect(row).toContainText(name);
    const control = row.locator('[data-onclick="appMgmt/delete"]');
    await expect(control).toHaveAttribute('data-action-route', 'user/delete');
    await expect(control).toHaveAttribute('data-item-id', id);

    await page.route('**/index.php?**', async (route) => {
      const request = route.request();
      if (request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `user/delete/${id}`) {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ status: 'ERROR', description: message, messages: [], data: null }) });
      } else await route.continue();
    });
    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
    await control.click();
    await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();
    page.on('request', onRequest);
    const requestPromise = page.waitForRequest((request) => request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `user/delete/${id}`);
    const responsePromise = page.waitForResponse((response) => response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === `user/delete/${id}`);
    const refreshPromise = page.waitForResponse((response) => response.request().method() === 'GET' && parseActionRoute(response.url()) === 'accessManager/index');
    await page.locator('#orrsDiag #positive').click();
    const request = await requestPromise;
    expect(new URL(request.url()).searchParams.get('isAjax')).toBe('1');
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual({ status: 'ERROR', description: message, messages: [], data: null });
    await expect.poll(() => refreshes.length).toBe(1);
    const [refresh] = refreshes;
    expect(refresh).toBeDefined();
    expect(new URL(refresh!.url()).searchParams.get('tabIndex')).toBe(tabIndex);
    expect((await refreshPromise).status()).toBe(200);
    await expect(grid.locator(`tr[data-item-id="${id}"]`)).toContainText(name);
    await expect(page.locator('.toast-error').filter({ hasText: message })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);
    const read = await page.request.get(`/index.php?r=user/search&sk=${encodeURIComponent(security)}&isAjax=1`);
    expect(read.status()).toBe(200);
    expect(await read.text()).toContain(name);
  } finally {
    page.off('request', onRequest);
    await page.unroute('**/index.php?**');
    tracker.dispose();
  }
});

test('admin user password save displays a synthetic ERROR and keeps the form open', async ({ sessionPage: { page, context, session }, isolatedPage }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const id = String(FIXTURE.ids.users.restricted);
  const saveRoute = `user/saveEditPass/${id}`;
  const message = 'Synthetic user password error';
  const envelope = { status: 'ERROR', description: message, messages: [], data: null };
  const password = `Synthetic-${Date.now()}-aB1!`;
  try {
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.accessManager);
    const grid = page.locator('#data-table-tblUsers');
    const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
    const panelId = await panel.getAttribute('id');
    await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();
    const control = grid.locator(`tr[data-item-id="${id}"] [data-onclick="appMgmt/show"][data-action-route="user/editPass"]`);
    await expect(control).toHaveAttribute('data-item-id', id);
    const open = page.waitForResponse((response) => response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === `user/editPass/${id}`);
    await control.click();
    expect((await open).status()).toBe(200);
    const form = page.locator('#frmUpdUsrPass');
    await expect(form).toHaveAttribute('data-onsubmit', 'user/savePassword');
    await expect(form).toHaveAttribute('data-action-route', saveRoute);
    await form.locator('[name="password"]').fill(password);
    await form.locator('[name="password_repeat"]').fill(password);
    await page.route('**/index.php?**', async (route) => {
      const request = route.request();
      if (request.method() === 'POST' && new URL(request.url()).searchParams.get('r') === saveRoute) {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(envelope) });
      } else await route.continue();
    });
    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
    const responsePromise = page.waitForResponse((response) => response.request().method() === 'POST' && new URL(response.url()).searchParams.get('r') === saveRoute);
    await page.locator('button[form="frmUpdUsrPass"]').click();
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual(envelope);
    const fields = new URLSearchParams(response.request().postData() ?? '');
    // Compare booleans only: neither credentials nor encrypted values belong in reports.
    for (const field of ['password', 'password_repeat']) {
      const value = fields.get(field);
      expect(Boolean(value) && value !== password, `${field} must be encrypted`).toBe(true);
    }
    await expect(page.locator('.toast-error').filter({ hasText: message })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);
    await expect(form).toBeVisible();
    // An independent session can still authenticate with the fixture credential.
    // This only checks the intercepted request, not a real backend error.
    await loginThroughUi(isolatedPage.page, FIXTURE.credentials.restricted);
  } finally {
    await page.unroute('**/index.php?**');
    tracker.dispose();
  }
});

test('bulk User delete displays a synthetic ERROR and reloads the active tab', async ({ sessionPage: { page, context, session } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const ids = [String(FIXTURE.ids.users.demo), String(FIXTURE.ids.users.restricted)];
  const names = [FIXTURE.names.demoProfile, FIXTURE.names.restrictedProfile];
  const message = 'Synthetic bulk user error';
  const envelope = { status: 'ERROR', description: message, messages: [], data: null };
  const refreshes: PlaywrightRequest[] = [];
  const onRequest = (request: PlaywrightRequest) => {
    if (request.method() === 'GET' && parseActionRoute(request.url()) === 'accessManager/index') refreshes.push(request);
  };

  try {
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.accessManager);
    const security = await page.evaluate(() => (0, eval)('sysPassApp').sk.get());
    const grid = page.locator('#data-table-tblUsers');
    const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
    const panelId = await panel.getAttribute('id');
    const tabIndex = await panel.getAttribute('data-tab-index');
    await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();
    for (const [index, id] of ids.entries()) {
      const row = grid.locator(`tr[data-item-id="${id}"]`);
      const expectedName = names[index];
      expect(expectedName).toBeDefined();
      await expect(row).toContainText(expectedName!);
      await row.locator('label.mdl-checkbox').click();
      await expect(row).toHaveClass(/is-selected/);
    }
    const control = panel.locator('[data-onclick="appMgmt/delete"][data-selection="#data-table-tblUsers"]');
    await expect(control).toHaveAttribute('data-action-route', 'user/delete');
    await page.route('**/index.php?**', async (route) => {
      const request = route.request();
      if (request.method() === 'GET' && parseActionRoute(request.url()) === 'user/delete') {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(envelope) });
      } else await route.continue();
    });
    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
    await panel.locator('#menu-tblUsers-menu-lower-right').click();
    await control.click();
    await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();
    page.on('request', onRequest);
    const requestPromise = page.waitForRequest((request) => request.method() === 'GET' && parseActionRoute(request.url()) === 'user/delete');
    const responsePromise = page.waitForResponse((response) => response.request().method() === 'GET' && parseActionRoute(response.url()) === 'user/delete');
    const refreshPromise = page.waitForResponse((response) => response.request().method() === 'GET' && parseActionRoute(response.url()) === 'accessManager/index');
    await page.locator('#orrsDiag #positive').click();
    const request = await requestPromise;
    const params = new URL(request.url()).searchParams;
    expect(params.get('isAjax')).toBe('1');
    expect(params.getAll('items[]').sort()).toEqual([...ids].sort());
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual(envelope);
    // This records current client behaviour, not a server rollback guarantee.
    await expect.poll(() => refreshes.length).toBe(1);
    const [refresh] = refreshes;
    expect(refresh).toBeDefined();
    expect(new URL(refresh!.url()).searchParams.get('tabIndex')).toBe(tabIndex);
    expect((await refreshPromise).status()).toBe(200);
    await expect(page.locator('.toast-error').filter({ hasText: message })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);
    const read = await page.request.get(`/index.php?r=user/search&sk=${encodeURIComponent(security)}&isAjax=1`);
    expect(read.status()).toBe(200);
    const body = await read.json();
    expect(body.status).toBe('OK');
    for (const id of ids) expect(body.data.html).toContain(`data-item-id="${id}"`);
  } finally {
    page.off('request', onRequest);
    await page.unroute('**/index.php?**');
    tracker.dispose();
  }
});
