/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

import type { Request as PlaywrightRequest } from '@playwright/test';

import { ActionTracker, parseActionRoute } from '../src/readiness';
import { expect, test } from '../src/session';
import { runTierBIndex, TIER_B_INDEX_CASES } from '../src/tier-b';

test('User Group delete reconciles the ERROR outcome', async ({ sessionPage: { page, context, session } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const name = `E2E GROUP DELETE ${Date.now()}-${Math.random().toString(36).slice(2)}`;
  const outcome = 'ERROR';
  const message = 'Synthetic user group error';
  const refreshes: PlaywrightRequest[] = [];
  const onRequest = (request: PlaywrightRequest) => {
    if (request.method() === 'GET' && parseActionRoute(request.url()) === 'accessManager/index') refreshes.push(request);
  };

  try {
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.accessManager);
    const security = await page.evaluate(() => {
      const app = (0, eval)('sysPassApp');
      return { sk: app.sk.get(), csrf: app.config.CSRF };
    });
    const created = await page.request.post('/index.php?r=userGroup/saveCreate', {
      headers: { 'X-CSRF': security.csrf },
      form: { name, sk: security.sk, isAjax: '1' },
    });
    expect(created.status()).toBe(200);
    expect((await created.json()).status).toBe('OK');

    // Reload the actual manager so the newly created synthetic User Group is rendered.
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.accessManager);
    const grid = page.locator('#data-table-tblGroups');
    const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
    const panelId = await panel.getAttribute('id');
    const tabIndex = await panel.getAttribute('data-tab-index');
    await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();
    const row = grid.locator('tr').filter({ hasText: name });
    await expect(row).toHaveCount(1);
    const id = await row.getAttribute('data-item-id');
    expect(id).toMatch(/^\d+$/);
    const control = row.locator('[data-onclick="appMgmt/delete"]');
    await expect(control).toHaveAttribute('data-action-route', 'userGroup/delete');
    await expect(control).toHaveAttribute('data-item-id', id!);

    await page.route('**/index.php?**', async (route) => {
      const request = route.request();
      if (request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `userGroup/delete/${id}`) {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ status: outcome, description: message, messages: [], data: null }) });
      } else await route.continue();
    });
    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
    await control.click();
    await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();
    page.on('request', onRequest);
    const requestPromise = page.waitForRequest((request) => request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `userGroup/delete/${id}`);
    const responsePromise = page.waitForResponse((response) => response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === `userGroup/delete/${id}`);
    const refreshPromise = page.waitForResponse((response) => response.request().method() === 'GET' && parseActionRoute(response.url()) === 'accessManager/index');
    await page.locator('#orrsDiag #positive').click();
    const request = await requestPromise;
    expect(new URL(request.url()).searchParams.get('isAjax')).toBe('1');
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual({ status: outcome, description: message, messages: [], data: null });
    await expect.poll(() => refreshes.length).toBe(1);
    const [refresh] = refreshes;
    expect(refresh).toBeDefined();
    expect(new URL(refresh!.url()).searchParams.get('tabIndex')).toBe(tabIndex);
    expect((await refreshPromise).status()).toBe(200);
    await expect(grid.locator('tr').filter({ hasText: name })).toHaveCount(1);
    await expect(page.locator('.toast-error').filter({ hasText: message })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);
    const read = await page.request.get(`/index.php?r=userGroup/search&sk=${encodeURIComponent(security.sk)}&isAjax=1`);
    expect(read.status()).toBe(200);
    expect(await read.text()).toContain(name);
  } finally {
    page.off('request', onRequest);
    await page.unroute('**/index.php?**');
    tracker.dispose();
  }
});

test('bulk User Group delete displays a synthetic ERROR and reloads the active tab', async ({ sessionPage: { page, context, session } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const prefix = `E2E BULK GROUP ${Date.now()}-${Math.random().toString(36).slice(2)}`;
  const names = [`${prefix} A`, `${prefix} B`];
  const message = 'Synthetic bulk user group error';
  const envelope = { status: 'ERROR', description: message, messages: [], data: null };
  const refreshes: PlaywrightRequest[] = [];
  const onRequest = (request: PlaywrightRequest) => {
    if (request.method() === 'GET' && parseActionRoute(request.url()) === 'accessManager/index') refreshes.push(request);
  };

  try {
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.accessManager);
    const security = await page.evaluate(() => {
      const app = (0, eval)('sysPassApp');
      return { sk: app.sk.get(), csrf: app.config.CSRF };
    });
    for (const name of names) {
      const created = await page.request.post('/index.php?r=userGroup/saveCreate', {
        headers: { 'X-CSRF': security.csrf },
        form: { name, sk: security.sk, isAjax: '1' },
      });
      expect(created.status()).toBe(200);
      expect((await created.json()).status).toBe('OK');
    }

    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.accessManager);
    const grid = page.locator('#data-table-tblGroups');
    const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
    const panelId = await panel.getAttribute('id');
    const tabIndex = await panel.getAttribute('data-tab-index');
    await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();
    const ids: string[] = [];
    for (const name of names) {
      const row = grid.locator('tr').filter({ hasText: name });
      await expect(row).toHaveCount(1);
      const id = await row.getAttribute('data-item-id');
      expect(id).toMatch(/^\d+$/);
      ids.push(id!);
      await row.locator('label.mdl-checkbox').click();
      await expect(row).toHaveClass(/is-selected/);
    }
    const control = panel.locator('[data-onclick="appMgmt/delete"][data-selection="#data-table-tblGroups"]');
    await expect(control).toHaveAttribute('data-action-route', 'userGroup/delete');
    await page.route('**/index.php?**', async (route) => {
      const request = route.request();
      if (request.method() === 'GET' && parseActionRoute(request.url()) === 'userGroup/delete') {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(envelope) });
      } else await route.continue();
    });
    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
    await panel.locator('#menu-tblGroups-menu-lower-right').click();
    await control.click();
    await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();
    page.on('request', onRequest);
    const requestPromise = page.waitForRequest((request) => request.method() === 'GET' && parseActionRoute(request.url()) === 'userGroup/delete');
    const responsePromise = page.waitForResponse((response) => response.request().method() === 'GET' && parseActionRoute(response.url()) === 'userGroup/delete');
    const refreshPromise = page.waitForResponse((response) => response.request().method() === 'GET' && parseActionRoute(response.url()) === 'accessManager/index');
    await page.locator('#orrsDiag #positive').click();
    const request = await requestPromise;
    const params = new URL(request.url()).searchParams;
    expect(params.get('isAjax')).toBe('1');
    expect(params.getAll('items[]').sort()).toEqual(ids.sort());
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
    const read = await page.request.get(`/index.php?r=userGroup/search&sk=${encodeURIComponent(security.sk)}&isAjax=1`);
    expect(read.status()).toBe(200);
    const body = await read.text();
    for (const name of names) expect(body).toContain(name);
  } finally {
    page.off('request', onRequest);
    await page.unroute('**/index.php?**');
    tracker.dispose();
  }
});
