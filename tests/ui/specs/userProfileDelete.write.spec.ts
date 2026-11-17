import type { Request as PlaywrightRequest } from '@playwright/test';

import { FIXTURE } from '../src/fixture';
import { ActionTracker, parseActionRoute } from '../src/readiness';
import { expect, test } from '../src/session';
import { runTierBIndex, TIER_B_INDEX_CASES } from '../src/tier-b';

test('User Profile delete reconciles the ERROR outcome', async ({ sessionPage: { page, context, session } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const id = String(FIXTURE.ids.profiles.restricted);
  const name = FIXTURE.names.restrictedProfile;
  const message = 'Synthetic user profile error';
  const refreshes: PlaywrightRequest[] = [];
  const onRequest = (request: PlaywrightRequest) => {
    if (request.method() === 'GET' && parseActionRoute(request.url()) === 'accessManager/index') refreshes.push(request);
  };

  try {
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.accessManager);
    const security = await page.evaluate(() => (0, eval)('sysPassApp').sk.get());
    const grid = page.locator('#data-table-tblProfiles');
    const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
    const panelId = await panel.getAttribute('id');
    const tabIndex = await panel.getAttribute('data-tab-index');
    await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();
    const row = grid.locator(`tr[data-item-id="${id}"]`);
    await expect(row).toContainText(name);
    const control = row.locator('[data-onclick="appMgmt/delete"]');
    await expect(control).toHaveAttribute('data-action-route', 'userProfile/delete');
    await expect(control).toHaveAttribute('data-item-id', id);

    await page.route('**/index.php?**', async (route) => {
      const request = route.request();
      if (request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `userProfile/delete/${id}`) {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ status: 'ERROR', description: message, messages: [], data: null }) });
      } else await route.continue();
    });
    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
    await control.click();
    await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();
    page.on('request', onRequest);
    const requestPromise = page.waitForRequest((request) => request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `userProfile/delete/${id}`);
    const responsePromise = page.waitForResponse((response) => response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === `userProfile/delete/${id}`);
    const refreshPromise = page.waitForResponse((response) => response.request().method() === 'GET' && parseActionRoute(response.url()) === 'accessManager/index');
    await page.locator('#orrsDiag #positive').click();
    const request = await requestPromise;
    expect(new URL(request.url()).searchParams.get('isAjax')).toBe('1');
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual({ status: 'ERROR', description: message, messages: [], data: null });
    await expect.poll(() => refreshes.length).toBe(1);
    expect(new URL(refreshes[0].url()).searchParams.get('tabIndex')).toBe(tabIndex);
    expect((await refreshPromise).status()).toBe(200);
    await expect(grid.locator(`tr[data-item-id="${id}"]`)).toContainText(name);
    await expect(page.locator('.toast-error').filter({ hasText: message })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);
    const read = await page.request.get(`/index.php?r=userProfile/search&sk=${encodeURIComponent(security)}&isAjax=1`);
    expect(read.status()).toBe(200);
    expect(await read.text()).toContain(name);
  } finally {
    page.off('request', onRequest);
    await page.unroute('**/index.php?**');
    tracker.dispose();
  }
});
