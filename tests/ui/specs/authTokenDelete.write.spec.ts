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

// Non-key-dependent actions from AuthTokenAction::getTokenActions()
// (ACCOUNT_SEARCH, CATEGORY_SEARCH, TAG_SEARCH, CLIENT_SEARCH, GROUP_SEARCH, TAG_CREATE)
// so synthetic tokens can be created with an empty pass without touching encryption keys.
const SINGLE_TOKEN_ACTION_IDS = [2, 102, 202, 302] as const;
const BULK_TOKEN_ACTION_IDS = [802, 204] as const;

function escapeRegExp(value: string): string {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

async function getActionLabels(page: Page, sk: string): Promise<Record<string, string>> {
  const formResponse = await page.request.get(
    `/index.php?r=authToken/create&sk=${encodeURIComponent(sk)}&isAjax=1`,
  );
  expect(formResponse.status()).toBe(200);
  const formBody = (await formResponse.json()) as { status?: string; data?: { html?: string } };
  expect(formBody.status).toBe('OK');

  return page.evaluate((html) => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const labels: Record<string, string> = {};
    for (const option of Array.from(doc.querySelectorAll<HTMLOptionElement>('#selActions option'))) {
      if (option.value) {
        labels[option.value] = (option.textContent ?? '').trim();
      }
    }
    return labels;
  }, formBody.data?.html ?? '');
}

async function createSyntheticToken(
  page: Page,
  security: { sk: string; csrf: string },
  actionId: number,
): Promise<void> {
  const created = await page.request.post('/index.php?r=authToken/saveCreate', {
    headers: { 'X-CSRF': security.csrf },
    form: {
      users: String(FIXTURE.ids.users.admin),
      actions: String(actionId),
      pass: '',
      sk: security.sk,
      isAjax: '1',
    },
  });
  expect(created.status()).toBe(200);
  const createdJson = (await created.json()) as { status?: string };
  expect(createdJson.status).toBe('OK');
}

for (const [index, outcome] of (['OK', 'ERROR', 'WARNING', 'transport'] as const).entries()) {
  test(`AuthToken delete reconciles the ${outcome} outcome`, async ({
    sessionPage: { page, context, session },
  }) => {
    const tracker = new ActionTracker(page, context, { sessionEstablished: false });
    const actionId = SINGLE_TOKEN_ACTION_IDS[index]!;
    const message = `Synthetic auth token ${outcome}`;
    const refreshes: PlaywrightRequest[] = [];
    const onRequest = (request: PlaywrightRequest) => {
      if (request.method() === 'GET' && parseActionRoute(request.url()) === 'accessManager/index') {
        refreshes.push(request);
      }
    };
    let createdId: string | null = null;
    let security: { sk: string; csrf: string } | null = null;

    try {
      await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.accessManager);
      security = await page.evaluate(() => {
        const app = (0, eval)('sysPassApp');
        return { sk: app.sk.get(), csrf: app.config.CSRF };
      });

      const actionLabels = await getActionLabels(page, security.sk);
      const actionLabel = actionLabels[String(actionId)];
      expect(actionLabel).toBeTruthy();

      await createSyntheticToken(page, security, actionId);

      // Reload accessManager/index so the newly created synthetic token row renders.
      await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.accessManager);
      const grid = page.locator('#data-table-tblTokens');
      const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
      const panelId = await panel.getAttribute('id');
      const tabIndex = await panel.getAttribute('data-tab-index');
      await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();

      const row = grid.locator('tr').filter({
        has: page.locator('td.cell-data', {
          hasText: new RegExp(`^${escapeRegExp(actionLabel!)}$`),
        }),
      });
      await expect(row).toHaveCount(1);
      const id = await row.getAttribute('data-item-id');
      expect(id).toMatch(/^\d+$/);
      createdId = id;

      const control = row.locator('[data-onclick="appMgmt/delete"]');
      await expect(control).toHaveAttribute('data-action-route', 'authToken/delete');
      await expect(control).toHaveAttribute('data-item-id', id!);

      const deleteRoute = `authToken/delete/${id}`;

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
                parseActionRoute(response.url()) === 'accessManager/index',
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

      const searchRead = await page.request.get(
        `/index.php?r=authToken/search&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
      );
      expect(searchRead.status()).toBe(200);
      const searchBody = (await searchRead.json()) as { status: string; data: { html: string } };
      expect(searchBody.status).toBe('OK');

      const viewRead = await page.request.get(
        `/index.php?r=authToken/view/${id}&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
      );
      const viewBody = (await viewRead.json()) as { status: string; data?: { html?: string } };

      if (outcome === 'OK') {
        expect(searchBody.data.html).not.toContain(`data-item-id="${id}"`);
        expect(viewRead.status()).toBe(500);
        expect(viewBody.status).toBe('ERROR');
        createdId = null;
      } else {
        expect(searchBody.data.html).toContain(`data-item-id="${id}"`);
        expect(viewRead.status()).toBe(200);
        expect(viewBody.status).toBe('OK');
        expect(viewBody.data?.html ?? '').toContain('***');
      }
    } finally {
      page.off('request', onRequest);
      await page.unroute('**/index.php?**');
      if (createdId && security) {
        await page.request.get(
          `/index.php?r=authToken/delete/${createdId}&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
        );
      }
      tracker.dispose();
    }
  });
}

test('bulk AuthToken delete sends items[] and reconciles a synthetic WARNING outcome', async ({
  sessionPage: { page, context, session },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const message = 'Synthetic bulk auth token warning';
  const envelope = { status: 'WARNING', description: message, messages: [], data: null };
  const refreshes: PlaywrightRequest[] = [];
  const onRequest = (request: PlaywrightRequest) => {
    if (request.method() === 'GET' && parseActionRoute(request.url()) === 'accessManager/index') {
      refreshes.push(request);
    }
  };
  const createdIds: string[] = [];
  let security: { sk: string; csrf: string } | null = null;

  try {
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.accessManager);
    security = await page.evaluate(() => {
      const app = (0, eval)('sysPassApp');
      return { sk: app.sk.get(), csrf: app.config.CSRF };
    });

    const actionLabels = await getActionLabels(page, security.sk);
    const targetLabels: string[] = [];
    for (const actionId of BULK_TOKEN_ACTION_IDS) {
      const label = actionLabels[String(actionId)];
      expect(label).toBeTruthy();
      targetLabels.push(label!);
      await createSyntheticToken(page, security, actionId);
    }

    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.accessManager);
    const grid = page.locator('#data-table-tblTokens');
    const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
    const panelId = await panel.getAttribute('id');
    const tabIndex = await panel.getAttribute('data-tab-index');
    await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();

    for (const label of targetLabels) {
      const row = grid.locator('tr').filter({
        has: page.locator('td.cell-data', {
          hasText: new RegExp(`^${escapeRegExp(label)}$`),
        }),
      });
      await expect(row).toHaveCount(1);
      const id = await row.getAttribute('data-item-id');
      expect(id).toMatch(/^\d+$/);
      createdIds.push(id!);
      await row.locator('label.mdl-checkbox').click();
      await expect(row).toHaveClass(/is-selected/);
    }

    const control = panel.locator(
      '[data-onclick="appMgmt/delete"][data-selection="#data-table-tblTokens"]',
    );
    await expect(control).toHaveAttribute('data-action-route', 'authToken/delete');

    await page.route('**/index.php?**', async (route) => {
      const request = route.request();
      if (request.method() === 'GET' && parseActionRoute(request.url()) === 'authToken/delete') {
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
    await panel.locator('#menu-tblTokens-menu-lower-right').click();
    await control.click();
    await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();

    page.on('request', onRequest);
    const requestPromise = page.waitForRequest(
      (request) =>
        request.method() === 'GET' && parseActionRoute(request.url()) === 'authToken/delete',
    );
    const responsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        parseActionRoute(response.url()) === 'authToken/delete',
    );
    const refreshPromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        parseActionRoute(response.url()) === 'accessManager/index',
    );

    await page.locator('#orrsDiag #positive').click();
    const request = await requestPromise;
    const params = new URL(request.url()).searchParams;
    expect(params.get('isAjax')).toBe('1');
    expect(params.getAll('items[]').sort()).toEqual([...createdIds].sort());

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
      `/index.php?r=authToken/search&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
    );
    expect(searchRead.status()).toBe(200);
    const searchBody = (await searchRead.json()) as { status: string; data: { html: string } };
    expect(searchBody.status).toBe('OK');
    for (const id of createdIds) {
      expect(searchBody.data.html).toContain(`data-item-id="${id}"`);
    }
  } finally {
    page.off('request', onRequest);
    await page.unroute('**/index.php?**');
    if (security) {
      for (const id of createdIds) {
        await page.request.get(
          `/index.php?r=authToken/delete/${id}&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
        );
      }
    }
    tracker.dispose();
  }
});
