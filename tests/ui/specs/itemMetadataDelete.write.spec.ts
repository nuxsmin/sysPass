import type { Request as PlaywrightRequest } from '@playwright/test';

import { FIXTURE } from '../src/fixture';
import { ActionTracker, parseActionRoute } from '../src/readiness';
import { expect, test } from '../src/session';
import { runTierBIndex, TIER_B_INDEX_CASES } from '../src/tier-b';

for (const outcome of ['ERROR', 'WARNING', 'transport'] as const) {
  test(`Tag delete reconciles the ${outcome} outcome`, async ({ sessionPage: { page, context, session } }) => {
    const tracker = new ActionTracker(page, context, { sessionEstablished: false });
    const name = `E2E TAG DELETE ${Date.now()}-${Math.random().toString(36).slice(2)}`;
    const message = `Synthetic tag ${outcome}`;
    const refreshes: PlaywrightRequest[] = [];
    const onRequest = (request: PlaywrightRequest) => {
      if (request.method() === 'GET' && parseActionRoute(request.url()) === 'itemManager/index') refreshes.push(request);
    };

    try {
      await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.itemManager);
      const security = await page.evaluate(() => {
        const app = (0, eval)('sysPassApp');
        return { sk: app.sk.get(), csrf: app.config.CSRF };
      });
      const created = await page.request.post('/index.php?r=tag/saveCreate', {
        headers: { 'X-CSRF': security.csrf },
        form: { name, sk: security.sk, isAjax: '1' },
      });
      expect(created.status()).toBe(200);
      expect((await created.json()).status).toBe('OK');

      // Reload the actual manager so the newly created synthetic Tag is rendered.
      await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.itemManager);
      const grid = page.locator('#data-table-tblTags');
      const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
      const panelId = await panel.getAttribute('id');
      const tabIndex = await panel.getAttribute('data-tab-index');
      await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();
      const row = grid.locator('tr').filter({ hasText: name });
      await expect(row).toHaveCount(1);
      const id = await row.getAttribute('data-item-id');
      expect(id).toMatch(/^\d+$/);
      const control = row.locator('[data-onclick="appMgmt/delete"]');
      await expect(control).toHaveAttribute('data-action-route', 'tag/delete');
      await expect(control).toHaveAttribute('data-item-id', id!);

      await page.route('**/index.php?**', async (route) => {
        const request = route.request();
        if (request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `tag/delete/${id}`) {
          if (outcome === 'transport') await route.abort('failed');
          else await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ status: outcome, description: message, messages: [], data: null }) });
        } else await route.continue();
      });
      await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
      await control.click();
      await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();
      page.on('request', onRequest);
      const requestPromise = page.waitForRequest((request) => request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `tag/delete/${id}`);
      const responsePromise = outcome === 'transport' ? null : page.waitForResponse((response) => response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === `tag/delete/${id}`);
      const refreshPromise = outcome === 'transport' ? null : page.waitForResponse((response) => response.request().method() === 'GET' && parseActionRoute(response.url()) === 'itemManager/index');
      await page.locator('#orrsDiag #positive').click();
      const request = await requestPromise;
      expect(new URL(request.url()).searchParams.get('isAjax')).toBe('1');
      if (responsePromise) {
        const response = await responsePromise;
        expect(response.status()).toBe(200);
        expect(await response.json()).toEqual({ status: outcome, description: message, messages: [], data: null });
        await expect.poll(() => refreshes.length).toBe(1);
        expect(new URL(refreshes[0].url()).searchParams.get('tabIndex')).toBe(tabIndex);
        expect((await refreshPromise!).status()).toBe(200);
        await expect(grid.locator('tr').filter({ hasText: name })).toHaveCount(1);
        await expect(page.locator(outcome === 'ERROR' ? '.toast-error' : '.toast-warning').filter({ hasText: message })).toBeVisible();
      } else {
        await expect(page.locator('.toast-error')).toBeVisible();
        await page.waitForTimeout(1000);
        expect(refreshes).toHaveLength(0);
      }
      await expect(page.locator('.toast-success')).toHaveCount(0);
      const read = await page.request.get(`/index.php?r=tag/search&sk=${encodeURIComponent(security.sk)}&isAjax=1`);
      expect(read.status()).toBe(200);
      expect(await read.text()).toContain(name);
    } finally {
      page.off('request', onRequest);
      await page.unroute('**/index.php?**');
      tracker.dispose();
    }
  });
}
test('Client delete displays a synthetic ERROR and reloads the active tab', async ({ sessionPage: { page, context, session } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const id = String(FIXTURE.ids.clients.primary);
  const name = FIXTURE.names.client;
  const envelope = { status: 'ERROR', description: 'Synthetic client delete error', messages: [], data: null };
  const refreshes: PlaywrightRequest[] = [];
  const onRequest = (request: PlaywrightRequest) => {
    if (request.method() === 'GET' && parseActionRoute(request.url()) === 'itemManager/index') refreshes.push(request);
  };

  try {
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.itemManager);
    const security = await page.evaluate(() => (0, eval)('sysPassApp').sk.get());
    const grid = page.locator('#data-table-tblClients');
    const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
    const panelId = await panel.getAttribute('id');
    const tabIndex = await panel.getAttribute('data-tab-index');
    await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();
    const row = grid.locator(`tr[data-item-id="${id}"]`);
    await expect(row).toContainText(name);
    const control = row.locator('[data-onclick="appMgmt/delete"]');
    await expect(control).toHaveAttribute('data-action-route', 'client/delete');
    await expect(control).toHaveAttribute('data-item-id', id);

    await page.route('**/index.php?**', async (route) => {
      const request = route.request();
      if (request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `client/delete/${id}`) {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(envelope) });
      } else await route.continue();
    });
    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
    await control.click();
    await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();
    page.on('request', onRequest);
    const requestPromise = page.waitForRequest((request) => request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `client/delete/${id}`);
    const responsePromise = page.waitForResponse((response) => response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === `client/delete/${id}`);
    const refreshPromise = page.waitForResponse((response) => response.request().method() === 'GET' && parseActionRoute(response.url()) === 'itemManager/index');
    await page.locator('#orrsDiag #positive').click();
    const request = await requestPromise;
    expect(new URL(request.url()).searchParams.get('isAjax')).toBe('1');
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual(envelope);
    // This records current client behaviour, not a server rollback guarantee.
    await expect.poll(() => refreshes.length).toBe(1);
    expect(new URL(refreshes[0].url()).searchParams.get('tabIndex')).toBe(tabIndex);
    expect((await refreshPromise).status()).toBe(200);
    await expect(page.locator('.toast-error').filter({ hasText: envelope.description })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);
    const read = await page.request.get(`/index.php?r=client/search&sk=${encodeURIComponent(security)}&isAjax=1`);
    expect(read.status()).toBe(200);
    const body = await read.json();
    expect(body.status).toBe('OK');
    expect(body.data.html).toContain(`data-item-id="${id}"`);
    expect(body.data.html).toContain(name);
  } finally {
    page.off('request', onRequest);
    await page.unroute('**/index.php?**');
    tracker.dispose();
  }
});

test('Category delete displays a synthetic ERROR and reloads the active tab', async ({ sessionPage: { page, context, session } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const id = String(FIXTURE.ids.categories.primary);
  const name = FIXTURE.names.category;
  const envelope = { status: 'ERROR', description: 'Synthetic category delete error', messages: [], data: null };
  const refreshes: PlaywrightRequest[] = [];
  const onRequest = (request: PlaywrightRequest) => {
    if (request.method() === 'GET' && parseActionRoute(request.url()) === 'itemManager/index') refreshes.push(request);
  };

  try {
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.itemManager);
    const security = await page.evaluate(() => (0, eval)('sysPassApp').sk.get());
    const grid = page.locator('#data-table-tblCategories');
    const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
    const panelId = await panel.getAttribute('id');
    const tabIndex = await panel.getAttribute('data-tab-index');
    await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();
    const row = grid.locator(`tr[data-item-id="${id}"]`);
    await expect(row).toContainText(name);
    const control = row.locator('[data-onclick="appMgmt/delete"]');
    await expect(control).toHaveAttribute('data-action-route', 'category/delete');
    await expect(control).toHaveAttribute('data-item-id', id);

    await page.route('**/index.php?**', async (route) => {
      const request = route.request();
      if (request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `category/delete/${id}`) {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(envelope) });
      } else await route.continue();
    });
    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
    await control.click();
    await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();
    page.on('request', onRequest);
    const requestPromise = page.waitForRequest((request) => request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `category/delete/${id}`);
    const responsePromise = page.waitForResponse((response) => response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === `category/delete/${id}`);
    const refreshPromise = page.waitForResponse((response) => response.request().method() === 'GET' && parseActionRoute(response.url()) === 'itemManager/index');
    await page.locator('#orrsDiag #positive').click();
    const request = await requestPromise;
    expect(new URL(request.url()).searchParams.get('isAjax')).toBe('1');
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual(envelope);
    // Record current refresh behavior; interception says nothing about server rollback.
    await expect.poll(() => refreshes.length).toBe(1);
    expect(new URL(refreshes[0].url()).searchParams.get('tabIndex')).toBe(tabIndex);
    expect((await refreshPromise).status()).toBe(200);
    await expect(page.locator('.toast-error').filter({ hasText: envelope.description })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);
    const read = await page.request.get(`/index.php?r=category/search&sk=${encodeURIComponent(security)}&isAjax=1`);
    expect(read.status()).toBe(200);
    const body = await read.json();
    expect(body.status).toBe('OK');
    expect(body.data.html).toContain(`data-item-id="${id}"`);
    expect(body.data.html).toContain(name);
  } finally {
    page.off('request', onRequest);
    await page.unroute('**/index.php?**');
    tracker.dispose();
  }
});

test('Custom field delete displays a synthetic ERROR and reloads the active tab', async ({ sessionPage: { page, context, session } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const name = `E2E FIELD DELETE ${Date.now()}-${Math.random().toString(36).slice(2)}`;
  const envelope = { status: 'ERROR', description: 'Synthetic custom field delete error', messages: [], data: null };
  const refreshes: PlaywrightRequest[] = [];
  const onRequest = (request: PlaywrightRequest) => {
    if (request.method() === 'GET' && parseActionRoute(request.url()) === 'itemManager/index') refreshes.push(request);
  };

  try {
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.itemManager);
    const security = await page.evaluate(() => {
      const app = (0, eval)('sysPassApp');
      return { sk: app.sk.get(), csrf: app.config.CSRF };
    });
    const formResponse = await page.request.get(`/index.php?r=customField/create&sk=${encodeURIComponent(security.sk)}&isAjax=1`);
    expect(formResponse.status()).toBe(200);
    const formBody = await formResponse.json();
    expect(formBody.status).toBe('OK');
    const choices = await page.evaluate((html) => {
      const doc = new DOMParser().parseFromString(html, 'text/html');
      const value = (selector: string) => Array.from(doc.querySelectorAll<HTMLOptionElement>(`${selector} option`)).find((option) => Number(option.value) > 0)?.value;
      return { type: value('#sel-type'), module: value('#sel-module') };
    }, formBody.data.html);
    expect(choices.type).toBeTruthy();
    expect(choices.module).toBeTruthy();
    const created = await page.request.post('/index.php?r=customField/saveCreate', {
      headers: { 'X-CSRF': security.csrf },
      form: { name, type: choices.type!, module: choices.module!, sk: security.sk, isAjax: '1' },
    });
    expect(created.status()).toBe(200);
    expect((await created.json()).status).toBe('OK');

    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.itemManager);
    const grid = page.locator('#data-table-tblCustomFields');
    const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
    const panelId = await panel.getAttribute('id');
    const tabIndex = await panel.getAttribute('data-tab-index');
    await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();
    const row = grid.locator('tr').filter({ hasText: name });
    await expect(row).toHaveCount(1);
    const id = await row.getAttribute('data-item-id');
    expect(id).toMatch(/^\d+$/);
    const control = row.locator('[data-onclick="appMgmt/delete"]');
    await expect(control).toHaveAttribute('data-action-route', 'customField/delete');
    await expect(control).toHaveAttribute('data-item-id', id!);

    await page.route('**/index.php?**', async (route) => {
      const request = route.request();
      if (request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `customField/delete/${id}`) {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(envelope) });
      } else await route.continue();
    });
    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
    await control.click();
    await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();
    page.on('request', onRequest);
    const requestPromise = page.waitForRequest((request) => request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `customField/delete/${id}`);
    const responsePromise = page.waitForResponse((response) => response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === `customField/delete/${id}`);
    const refreshPromise = page.waitForResponse((response) => response.request().method() === 'GET' && parseActionRoute(response.url()) === 'itemManager/index');
    await page.locator('#orrsDiag #positive').click();
    const request = await requestPromise;
    expect(new URL(request.url()).searchParams.get('isAjax')).toBe('1');
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual(envelope);
    // Record current refresh behavior; interception does not establish server rollback.
    await expect.poll(() => refreshes.length).toBe(1);
    expect(new URL(refreshes[0].url()).searchParams.get('tabIndex')).toBe(tabIndex);
    expect((await refreshPromise).status()).toBe(200);
    await expect(page.locator('.toast-error').filter({ hasText: envelope.description })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);
    const read = await page.request.get(`/index.php?r=customField/search&sk=${encodeURIComponent(security.sk)}&isAjax=1`);
    expect(read.status()).toBe(200);
    const body = await read.json();
    expect(body.status).toBe('OK');
    expect(body.data.html).toContain(`data-item-id="${id}"`);
    expect(body.data.html).toContain(name);
  } finally {
    page.off('request', onRequest);
    await page.unroute('**/index.php?**');
    tracker.dispose();
  }
});
