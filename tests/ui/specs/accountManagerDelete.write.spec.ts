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

import type { Page, Request as PlaywrightRequest } from '@playwright/test';

import { FIXTURE } from '../src/fixture';
import { ActionTracker, parseActionRoute } from '../src/readiness';
import { expect, test } from '../src/session';
import { runTierBIndex, TIER_B_INDEX_CASES } from '../src/tier-b';

interface SecurityContext {
  sk: string;
  csrf: string;
}

let uniqueCounter = 0;

function uniqueSuffix(): string {
  uniqueCounter += 1;
  const timePart = (Date.now() % 1_679_616).toString(36).padStart(4, '0');
  const seqPart = uniqueCounter.toString(36);
  const randPart = Math.random().toString(36).slice(2, 5);
  return `${timePart}${seqPart}${randPart}`;
}

async function getSecurityContext(page: Page): Promise<SecurityContext> {
  return page.evaluate(() => {
    const app = (0, eval)('sysPassApp');
    return { sk: app.sk.get(), csrf: app.config.CSRF };
  });
}

async function createSyntheticAccount(
  page: Page,
  security: SecurityContext,
  {
    name,
    login,
    password,
  }: {
    name: string;
    login: string;
    password: string;
  },
): Promise<number> {
  const response = await page.request.post('/index.php?r=account/saveCreate', {
    headers: { 'X-CSRF': security.csrf },
    form: {
      name,
      login,
      client_id: String(FIXTURE.ids.clients.primary),
      category_id: String(FIXTURE.ids.categories.primary),
      password,
      password_repeat: password,
      sk: security.sk,
      isAjax: '1',
    },
  });
  expect(response.status()).toBe(200);
  const body = (await response.json()) as { status?: string; data?: { itemId?: number } };
  expect(body.status).toBe('OK');
  expect(typeof body.data?.itemId).toBe('number');
  expect(body.data!.itemId!).toBeGreaterThan(0);
  return body.data!.itemId!;
}

async function editSyntheticAccountName(
  page: Page,
  security: SecurityContext,
  accountId: number,
  name: string,
): Promise<void> {
  const response = await page.request.post(`/index.php?r=account/saveEdit/${accountId}`, {
    headers: { 'X-CSRF': security.csrf },
    form: {
      name,
      client_id: String(FIXTURE.ids.clients.primary),
      category_id: String(FIXTURE.ids.categories.primary),
      sk: security.sk,
      isAjax: '1',
    },
  });
  expect(response.status()).toBe(200);
  const body = (await response.json()) as { status?: string };
  expect(body.status).toBe('OK');
}

async function extractRowIds(page: Page, html: string): Promise<string[]> {
  return page.evaluate((markup) => {
    const doc = new DOMParser().parseFromString(markup, 'text/html');
    return Array.from(doc.querySelectorAll('tr[data-item-id]'))
      .map((row) => row.getAttribute('data-item-id'))
      .filter((id): id is string => Boolean(id));
  }, html);
}

async function cleanupSyntheticAccountsAndHistory(
  page: Page,
  security: SecurityContext,
  accountIds: number[],
  historySearchPrefix: string,
): Promise<void> {
  for (const accountId of accountIds) {
    await page.request
      .get(
        `/index.php?r=accountManager/delete/${accountId}&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
      )
      .catch(() => undefined);
  }

  const historySearch = await page.request
    .get(
      `/index.php?r=accountHistoryManager/search&search=${encodeURIComponent(historySearchPrefix)}&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
    )
    .catch(() => undefined);

  if (historySearch && historySearch.status() === 200) {
    const body = (await historySearch.json().catch(() => null)) as {
      status?: string;
      data?: { html?: string };
    } | null;
    if (body?.status === 'OK' && typeof body.data?.html === 'string') {
      const ids = await extractRowIds(page, body.data.html);
      if (ids.length > 0) {
        const itemsQuery = ids.map((id) => `items[]=${encodeURIComponent(id)}`).join('&');
        await page.request
          .get(
            `/index.php?r=accountHistoryManager/delete&${itemsQuery}&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
          )
          .catch(() => undefined);
      }
    }
  }
}

for (const outcome of ['OK', 'ERROR', 'WARNING', 'transport'] as const) {
  test(`AccountManager delete reconciles the ${outcome} outcome`, async ({
    sessionPage: { page, context, session },
  }) => {
    const tracker = new ActionTracker(page, context, { sessionEstablished: false });
    const suffix = uniqueSuffix();
    const outcomeTag = outcome.slice(0, 3).toUpperCase();
    const token = `MG-${outcomeTag}-${suffix}`;
    const name = `000-${token}`;
    const message = `Synthetic account manager delete ${outcome}`;
    const refreshes: PlaywrightRequest[] = [];
    const onRequest = (request: PlaywrightRequest) => {
      if (request.method() === 'GET' && parseActionRoute(request.url()) === 'itemManager/index') {
        refreshes.push(request);
      }
    };
    let createdId: number | null = null;
    let security: SecurityContext | null = null;

    try {
      await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.itemManager);
      security = await getSecurityContext(page);

      createdId = await createSyntheticAccount(page, security, {
        name,
        login: `mgr-acc-${suffix}`,
        password: `MgrAcc-${suffix}-Ab1!`,
      });
      const id = String(createdId);

      await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.itemManager);
      const grid = page.locator('#data-table-tblAccounts');
      const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
      const panelId = await panel.getAttribute('id');
      const tabIndex = await panel.getAttribute('data-tab-index');
      expect(tabIndex).toBeTruthy();
      await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();

      const searchForm = panel.locator('#frmSearchAccount');
      await searchForm.locator('label[for^="search-"]').first().click();
      await searchForm.locator('input[name="search"]').fill(name);
      const searchPromise = page.waitForResponse(
        (response) =>
          response.request().method() === 'GET' &&
          parseActionRoute(response.url()) === 'accountManager/search',
      );
      await searchForm.locator('input[name="search"]').press('Enter');
      expect((await searchPromise).status()).toBe(200);

      const row = grid.locator(`tr[data-item-id="${id}"]`);
      await expect(row).toHaveCount(1);
      await expect(row).toContainText(name);
      const control = row.locator('[data-onclick="appMgmt/delete"]');
      await expect(control).toHaveAttribute('data-action-route', 'accountManager/delete');
      await expect(control).toHaveAttribute('data-item-id', id);

      const deleteRoute = `accountManager/delete/${id}`;

      if (outcome !== 'OK') {
        await page.route('**/index.php?**', async (route) => {
          const request = route.request();
          if (
            request.method() === 'GET' &&
            new URL(request.url()).searchParams.get('r') === deleteRoute
          ) {
            if (outcome === 'transport') {
              await route.abort('failed');
            } else {
              await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({
                  status: outcome,
                  description: message,
                  messages: [],
                  data: null,
                }),
              });
            }
          } else {
            await route.continue();
          }
        });
      }

      await page.locator('#toast-container').evaluateAll((nodes) =>
        nodes.forEach((node) => node.remove()),
      );
      await grid.evaluate((element) => element.setAttribute('data-test-stale-grid', 'true'));
      await control.click();
      await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();

      page.on('request', onRequest);
      const requestPromise = page.waitForRequest(
        (request) =>
          request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === deleteRoute,
      );
      const responsePromise =
        outcome === 'transport'
          ? null
          : page.waitForResponse(
              (response) =>
                response.request().method() === 'GET' &&
                new URL(response.url()).searchParams.get('r') === deleteRoute,
            );
      const refreshPromise =
        outcome === 'transport'
          ? null
          : page.waitForResponse(
              (response) =>
                response.request().method() === 'GET' &&
                parseActionRoute(response.url()) === 'itemManager/index',
            );

      await page.locator('#orrsDiag #positive').click();
      const request = await requestPromise;
      expect(new URL(request.url()).searchParams.get('isAjax')).toBe('1');

      if (responsePromise) {
        const response = await responsePromise;
        expect(response.status()).toBe(200);
        const envelope = (await response.json()) as {
          status: string;
          description: string;
          messages?: string[];
          data: unknown;
        };
        if (outcome === 'OK') {
          expect(envelope.status).toBe('OK');
        } else {
          expect(envelope).toEqual({
            status: outcome,
            description: message,
            messages: [],
            data: null,
          });
        }

        await expect.poll(() => refreshes.length).toBe(1);
        const [refresh] = refreshes;
        expect(refresh).toBeDefined();
        expect(new URL(refresh!.url()).searchParams.get('tabIndex')).toBe(tabIndex);
        expect((await refreshPromise!).status()).toBe(200);
        await expect(grid).not.toHaveAttribute('data-test-stale-grid', 'true');

        if (outcome === 'OK') {
          await expect(grid.locator(`tr[data-item-id="${id}"]`)).toHaveCount(0);
          await expect(
            page.locator('.toast-success').filter({ hasText: envelope.description }),
          ).toBeVisible();
          await expect(page.locator('.toast-error')).toHaveCount(0);
          await expect(page.locator('.toast-warning')).toHaveCount(0);
        } else {
          await expect(grid.locator(`tr[data-item-id="${id}"]`)).toHaveCount(1);
          await expect(
            page
              .locator(outcome === 'ERROR' ? '.toast-error' : '.toast-warning')
              .filter({ hasText: message }),
          ).toBeVisible();
          await expect(page.locator('.toast-success')).toHaveCount(0);
        }
      } else {
        await expect(page.locator('.toast-error')).toBeVisible();
        await expect(page.locator('.toast-success')).toHaveCount(0);
        await page.waitForTimeout(1000);
        expect(refreshes).toHaveLength(0);
        await expect(grid).toHaveAttribute('data-test-stale-grid', 'true');
      }

      const mgrSearchRead = await page.request.get(
        `/index.php?r=accountManager/search&search=${encodeURIComponent(name)}&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
      );
      expect(mgrSearchRead.status()).toBe(200);
      const mgrSearchBody = (await mgrSearchRead.json()) as {
        status: string;
        data: { html: string };
      };
      expect(mgrSearchBody.status).toBe('OK');

      const accSearchRead = await page.request.get(
        `/index.php?r=account/search&search=${encodeURIComponent(name)}`,
      );
      expect(accSearchRead.status()).toBe(200);
      const accSearchBody = (await accSearchRead.json()) as {
        status: string;
        data: { html: string };
      };
      expect(accSearchBody.status).toBe('OK');

      if (outcome === 'OK') {
        expect(mgrSearchBody.data.html).not.toContain(`data-item-id="${id}"`);
        expect(mgrSearchBody.data.html).not.toContain(name);
        expect(accSearchBody.data.html).not.toContain(`data-item-id="${id}"`);
        expect(accSearchBody.data.html).not.toContain(name);
        createdId = null;
      } else {
        expect(mgrSearchBody.data.html).toContain(`data-item-id="${id}"`);
        expect(mgrSearchBody.data.html).toContain(name);
        expect(accSearchBody.data.html).toContain(`data-item-id="${id}"`);
        expect(accSearchBody.data.html).toContain(name);
      }
    } finally {
      page.off('request', onRequest);
      await page.unroute('**/index.php?**');
      if (security) {
        await cleanupSyntheticAccountsAndHistory(
          page,
          security,
          createdId ? [createdId] : [],
          token,
        );
      }
      tracker.dispose();
    }
  });
}

test('bulk AccountManager delete sends items[] and reconciles a synthetic ERROR outcome', async ({
  sessionPage: { page, context, session },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const suffix = uniqueSuffix();
  const sharedToken = `MGB-${suffix}`;
  const names = [`000-${sharedToken}-A`, `000-${sharedToken}-B`] as const;
  const message = 'Synthetic bulk account manager error';
  const envelope = { status: 'ERROR', description: message, messages: [], data: null };
  const refreshes: PlaywrightRequest[] = [];
  const onRequest = (request: PlaywrightRequest) => {
    if (request.method() === 'GET' && parseActionRoute(request.url()) === 'itemManager/index') {
      refreshes.push(request);
    }
  };
  const createdIds: number[] = [];
  let security: SecurityContext | null = null;

  try {
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.itemManager);
    security = await getSecurityContext(page);

    for (const [index, name] of names.entries()) {
      const id = await createSyntheticAccount(page, security, {
        name,
        login: `mgr-bulk-${index}-${suffix}`,
        password: `MgrBulk-${index}-${suffix}-Ab1!`,
      });
      createdIds.push(id);
    }

    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.itemManager);
    const grid = page.locator('#data-table-tblAccounts');
    const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
    const panelId = await panel.getAttribute('id');
    const tabIndex = await panel.getAttribute('data-tab-index');
    expect(tabIndex).toBeTruthy();
    await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();

    const searchForm = panel.locator('#frmSearchAccount');
    await searchForm.locator('label[for^="search-"]').first().click();
    await searchForm.locator('input[name="search"]').fill(sharedToken);
    const searchPromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        parseActionRoute(response.url()) === 'accountManager/search',
    );
    await searchForm.locator('input[name="search"]').press('Enter');
    expect((await searchPromise).status()).toBe(200);

    const stringIds = createdIds.map((id) => String(id));
    for (const [index, id] of stringIds.entries()) {
      const row = grid.locator(`tr[data-item-id="${id}"]`);
      await expect(row).toContainText(names[index]!);
      await row.locator('label.mdl-checkbox').click();
      await expect(row).toHaveClass(/is-selected/);
    }

    const control = panel.locator(
      '[data-onclick="appMgmt/delete"][data-selection="#data-table-tblAccounts"]',
    );
    await expect(control).toHaveAttribute('data-action-route', 'accountManager/delete');

    await page.route('**/index.php?**', async (route) => {
      const request = route.request();
      if (
        request.method() === 'GET' &&
        parseActionRoute(request.url()) === 'accountManager/delete'
      ) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(envelope),
        });
      } else {
        await route.continue();
      }
    });

    await page.locator('#toast-container').evaluateAll((nodes) =>
      nodes.forEach((node) => node.remove()),
    );
    await panel.locator('#menu-tblAccounts-menu-lower-right').click();
    await control.click();
    await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();

    page.on('request', onRequest);
    const requestPromise = page.waitForRequest(
      (request) =>
        request.method() === 'GET' &&
        parseActionRoute(request.url()) === 'accountManager/delete',
    );
    const responsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        parseActionRoute(response.url()) === 'accountManager/delete',
    );
    const refreshPromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        parseActionRoute(response.url()) === 'itemManager/index',
    );

    await page.locator('#orrsDiag #positive').click();
    const request = await requestPromise;
    const params = new URL(request.url()).searchParams;
    expect(params.get('isAjax')).toBe('1');
    expect(params.getAll('items[]').sort()).toEqual([...stringIds].sort());

    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual(envelope);

    await expect.poll(() => refreshes.length).toBe(1);
    const [refresh] = refreshes;
    expect(refresh).toBeDefined();
    expect(new URL(refresh!.url()).searchParams.get('tabIndex')).toBe(tabIndex);
    expect((await refreshPromise).status()).toBe(200);

    await expect(page.locator('.toast-error').filter({ hasText: message })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);

    const searchRead = await page.request.get(
      `/index.php?r=accountManager/search&search=${encodeURIComponent(sharedToken)}&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
    );
    expect(searchRead.status()).toBe(200);
    const searchBody = (await searchRead.json()) as { status: string; data: { html: string } };
    expect(searchBody.status).toBe('OK');
    for (const [index, id] of stringIds.entries()) {
      expect(searchBody.data.html).toContain(`data-item-id="${id}"`);
      expect(searchBody.data.html).toContain(names[index]!);
    }
  } finally {
    page.off('request', onRequest);
    await page.unroute('**/index.php?**');
    if (security) {
      await cleanupSyntheticAccountsAndHistory(page, security, createdIds, sharedToken);
    }
    tracker.dispose();
  }
});

for (const outcome of ['OK', 'ERROR', 'WARNING', 'transport'] as const) {
  test(`AccountHistoryManager delete reconciles the ${outcome} outcome`, async ({
    sessionPage: { page, context, session },
  }) => {
    const tracker = new ActionTracker(page, context, { sessionEstablished: false });
    const suffix = uniqueSuffix();
    const outcomeTag = outcome.slice(0, 3).toUpperCase();
    const sharedToken = `H-${outcomeTag}-${suffix}`;
    const originalName = `${sharedToken}-Orig`;
    const updatedName = `${sharedToken}-Edit`;
    const message = `Synthetic account history manager delete ${outcome}`;
    const refreshes: PlaywrightRequest[] = [];
    const onRequest = (request: PlaywrightRequest) => {
      if (request.method() === 'GET' && parseActionRoute(request.url()) === 'itemManager/index') {
        refreshes.push(request);
      }
    };
    let createdAccountId: number | null = null;
    let security: SecurityContext | null = null;

    try {
      await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.itemManager);
      security = await getSecurityContext(page);

      createdAccountId = await createSyntheticAccount(page, security, {
        name: originalName,
        login: `hist-acc-${suffix}`,
        password: `HistAcc-${suffix}-Ab1!`,
      });
      await editSyntheticAccountName(page, security, createdAccountId, updatedName);

      await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.itemManager);
      const grid = page.locator('#data-table-tblAccountsHistory');
      const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
      const panelId = await panel.getAttribute('id');
      const tabIndex = await panel.getAttribute('data-tab-index');
      expect(tabIndex).toBeTruthy();
      await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();

      const searchForm = panel.locator('#frmSearchAccountHistory');
      await searchForm.locator('label[for^="search-"]').first().click();
      await searchForm.locator('input[name="search"]').fill(originalName);
      const searchPromise = page.waitForResponse(
        (response) =>
          response.request().method() === 'GET' &&
          parseActionRoute(response.url()) === 'accountHistoryManager/search',
      );
      await searchForm.locator('input[name="search"]').press('Enter');
      expect((await searchPromise).status()).toBe(200);

      const row = grid.locator('tr').filter({ hasText: originalName });
      await expect(row).toHaveCount(1);
      const historyId = await row.getAttribute('data-item-id');
      expect(historyId).toMatch(/^\d+$/);

      const control = row.locator('[data-onclick="appMgmt/delete"]');
      await expect(control).toHaveAttribute('data-action-route', 'accountHistoryManager/delete');
      await expect(control).toHaveAttribute('data-item-id', historyId!);

      const deleteRoute = `accountHistoryManager/delete/${historyId}`;

      if (outcome !== 'OK') {
        await page.route('**/index.php?**', async (route) => {
          const request = route.request();
          if (
            request.method() === 'GET' &&
            new URL(request.url()).searchParams.get('r') === deleteRoute
          ) {
            if (outcome === 'transport') {
              await route.abort('failed');
            } else {
              await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({
                  status: outcome,
                  description: message,
                  messages: [],
                  data: null,
                }),
              });
            }
          } else {
            await route.continue();
          }
        });
      }

      await page.locator('#toast-container').evaluateAll((nodes) =>
        nodes.forEach((node) => node.remove()),
      );
      await grid.evaluate((element) => element.setAttribute('data-test-stale-grid', 'true'));
      await control.click();
      await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();

      page.on('request', onRequest);
      const requestPromise = page.waitForRequest(
        (request) =>
          request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === deleteRoute,
      );
      const responsePromise =
        outcome === 'transport'
          ? null
          : page.waitForResponse(
              (response) =>
                response.request().method() === 'GET' &&
                new URL(response.url()).searchParams.get('r') === deleteRoute,
            );
      const refreshPromise =
        outcome === 'transport'
          ? null
          : page.waitForResponse(
              (response) =>
                response.request().method() === 'GET' &&
                parseActionRoute(response.url()) === 'itemManager/index',
            );

      await page.locator('#orrsDiag #positive').click();
      const request = await requestPromise;
      expect(new URL(request.url()).searchParams.get('isAjax')).toBe('1');

      if (responsePromise) {
        const response = await responsePromise;
        expect(response.status()).toBe(200);
        const envelope = (await response.json()) as {
          status: string;
          description: string;
          messages?: string[];
          data: unknown;
        };
        if (outcome === 'OK') {
          expect(envelope.status).toBe('OK');
        } else {
          expect(envelope).toEqual({
            status: outcome,
            description: message,
            messages: [],
            data: null,
          });
        }

        await expect.poll(() => refreshes.length).toBe(1);
        const [refresh] = refreshes;
        expect(refresh).toBeDefined();
        expect(new URL(refresh!.url()).searchParams.get('tabIndex')).toBe(tabIndex);
        expect((await refreshPromise!).status()).toBe(200);
        await expect(grid).not.toHaveAttribute('data-test-stale-grid', 'true');

        if (outcome === 'OK') {
          await expect(grid.locator(`tr[data-item-id="${historyId}"]`)).toHaveCount(0);
          await expect(
            page.locator('.toast-success').filter({ hasText: envelope.description }),
          ).toBeVisible();
          await expect(page.locator('.toast-error')).toHaveCount(0);
          await expect(page.locator('.toast-warning')).toHaveCount(0);
        } else {
          await expect(grid.locator(`tr[data-item-id="${historyId}"]`)).toHaveCount(1);
          await expect(
            page
              .locator(outcome === 'ERROR' ? '.toast-error' : '.toast-warning')
              .filter({ hasText: message }),
          ).toBeVisible();
          await expect(page.locator('.toast-success')).toHaveCount(0);
        }
      } else {
        await expect(page.locator('.toast-error')).toBeVisible();
        await expect(page.locator('.toast-success')).toHaveCount(0);
        await page.waitForTimeout(1000);
        expect(refreshes).toHaveLength(0);
        await expect(grid).toHaveAttribute('data-test-stale-grid', 'true');
      }

      const searchRead = await page.request.get(
        `/index.php?r=accountHistoryManager/search&search=${encodeURIComponent(originalName)}&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
      );
      expect(searchRead.status()).toBe(200);
      const searchBody = (await searchRead.json()) as { status: string; data: { html: string } };
      expect(searchBody.status).toBe('OK');

      if (outcome === 'OK') {
        expect(searchBody.data.html).not.toContain(`data-item-id="${historyId}"`);
        expect(searchBody.data.html).not.toContain(originalName);
      } else {
        expect(searchBody.data.html).toContain(`data-item-id="${historyId}"`);
        expect(searchBody.data.html).toContain(originalName);
      }
    } finally {
      page.off('request', onRequest);
      await page.unroute('**/index.php?**');
      if (security) {
        await cleanupSyntheticAccountsAndHistory(
          page,
          security,
          createdAccountId ? [createdAccountId] : [],
          sharedToken,
        );
      }
      tracker.dispose();
    }
  });
}

test('bulk AccountHistoryManager delete sends items[] and reconciles a synthetic WARNING outcome', async ({
  sessionPage: { page, context, session },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const suffix = uniqueSuffix();
  const sharedToken = `HB-${suffix}`;
  const rev1 = `${sharedToken}-R1`;
  const rev2 = `${sharedToken}-R2`;
  const rev3 = `${sharedToken}-R3`;
  const message = 'Synthetic bulk account history warning';
  const envelope = { status: 'WARNING', description: message, messages: [], data: null };
  const refreshes: PlaywrightRequest[] = [];
  const onRequest = (request: PlaywrightRequest) => {
    if (request.method() === 'GET' && parseActionRoute(request.url()) === 'itemManager/index') {
      refreshes.push(request);
    }
  };
  let createdAccountId: number | null = null;
  let security: SecurityContext | null = null;

  try {
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.itemManager);
    security = await getSecurityContext(page);

    createdAccountId = await createSyntheticAccount(page, security, {
      name: rev1,
      login: `hist-bulk-${suffix}`,
      password: `HistBulk-${suffix}-Ab1!`,
    });
    await editSyntheticAccountName(page, security, createdAccountId, rev2);
    await editSyntheticAccountName(page, security, createdAccountId, rev3);

    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.itemManager);
    const grid = page.locator('#data-table-tblAccountsHistory');
    const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
    const panelId = await panel.getAttribute('id');
    const tabIndex = await panel.getAttribute('data-tab-index');
    expect(tabIndex).toBeTruthy();
    await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();

    const searchForm = panel.locator('#frmSearchAccountHistory');
    await searchForm.locator('label[for^="search-"]').first().click();
    await searchForm.locator('input[name="search"]').fill(sharedToken);
    const searchPromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        parseActionRoute(response.url()) === 'accountHistoryManager/search',
    );
    await searchForm.locator('input[name="search"]').press('Enter');
    expect((await searchPromise).status()).toBe(200);

    const historyIds: string[] = [];
    for (const name of [rev1, rev2]) {
      const row = grid.locator('tr').filter({ hasText: name });
      await expect(row).toHaveCount(1);
      const id = await row.getAttribute('data-item-id');
      expect(id).toMatch(/^\d+$/);
      historyIds.push(id!);
      await row.locator('label.mdl-checkbox').click();
      await expect(row).toHaveClass(/is-selected/);
    }

    const control = panel.locator(
      '[data-onclick="appMgmt/delete"][data-selection="#data-table-tblAccountsHistory"]',
    );
    await expect(control).toHaveAttribute('data-action-route', 'accountHistoryManager/delete');

    await page.route('**/index.php?**', async (route) => {
      const request = route.request();
      if (
        request.method() === 'GET' &&
        parseActionRoute(request.url()) === 'accountHistoryManager/delete'
      ) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(envelope),
        });
      } else {
        await route.continue();
      }
    });

    await page.locator('#toast-container').evaluateAll((nodes) =>
      nodes.forEach((node) => node.remove()),
    );
    await panel.locator('#menu-tblAccountsHistory-menu-lower-right').click();
    await control.click();
    await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();

    page.on('request', onRequest);
    const requestPromise = page.waitForRequest(
      (request) =>
        request.method() === 'GET' &&
        parseActionRoute(request.url()) === 'accountHistoryManager/delete',
    );
    const responsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        parseActionRoute(response.url()) === 'accountHistoryManager/delete',
    );
    const refreshPromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        parseActionRoute(response.url()) === 'itemManager/index',
    );

    await page.locator('#orrsDiag #positive').click();
    const request = await requestPromise;
    const params = new URL(request.url()).searchParams;
    expect(params.get('isAjax')).toBe('1');
    expect(params.getAll('items[]').sort()).toEqual([...historyIds].sort());

    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual(envelope);

    await expect.poll(() => refreshes.length).toBe(1);
    const [refresh] = refreshes;
    expect(refresh).toBeDefined();
    expect(new URL(refresh!.url()).searchParams.get('tabIndex')).toBe(tabIndex);
    expect((await refreshPromise).status()).toBe(200);

    await expect(page.locator('.toast-warning').filter({ hasText: message })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);

    const searchRead = await page.request.get(
      `/index.php?r=accountHistoryManager/search&search=${encodeURIComponent(sharedToken)}&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
    );
    expect(searchRead.status()).toBe(200);
    const searchBody = (await searchRead.json()) as { status: string; data: { html: string } };
    expect(searchBody.status).toBe('OK');
    for (const [index, id] of historyIds.entries()) {
      expect(searchBody.data.html).toContain(`data-item-id="${id}"`);
      expect(searchBody.data.html).toContain([rev1, rev2][index]!);
    }
  } finally {
    page.off('request', onRequest);
    await page.unroute('**/index.php?**');
    if (security) {
      await cleanupSyntheticAccountsAndHistory(
        page,
        security,
        createdAccountId ? [createdAccountId] : [],
        sharedToken,
      );
    }
    tracker.dispose();
  }
});
