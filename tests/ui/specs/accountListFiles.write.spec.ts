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

import type { Page } from '@playwright/test';

import { FIXTURE, RESTRICTED_CREDENTIALS } from '../src/fixture';
import { loginThroughUi } from '../src/login';
import { ActionTracker, parseActionRoute } from '../src/readiness';
import { expect, test } from '../src/session';
import { openSeededShell } from '../src/tier-b';

async function createDisposableAccount(
  page: Page,
  tracker: ActionTracker,
  session: Parameters<typeof openSeededShell>[2],
): Promise<{ id: number; sk: string; csrf: string }> {
  await openSeededShell(page, tracker, session);

  const createResponsePromise = page.waitForResponse(
    (response) =>
      response.request().method() === 'GET' && parseActionRoute(response.url()) === 'account/create',
  );
  await page.locator('a.btn-menu[data-route="account/create"]').first().click();
  const createResponse = await createResponsePromise;
  expect(createResponse.status()).toBe(200);

  await expect(page.locator('#frmAccount')).toHaveAttribute('data-action-route', 'account/saveCreate');
  await expect(page.locator('#client_id + .selectize-control')).toHaveCount(1);
  await expect(page.locator('#category_id + .selectize-control')).toHaveCount(1);
  await expect(page.locator('#wrap-loading')).toBeHidden();
  const since = Date.now();

  const suffix = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
  await page.locator('#name').fill(`FilesEmpty ${suffix}`);
  await page.locator('#login').fill(`files-empty-${suffix}`);

  for (const [selector, value] of [
    ['#client_id', FIXTURE.ids.clients.primary],
    ['#category_id', FIXTURE.ids.categories.primary],
  ] as const) {
    const applied = await page.locator(selector).evaluate((element, selected) => {
      const select = element as HTMLSelectElement & {
        selectize?: { setValue(val: string, silent?: boolean): void };
      };
      if (!select.selectize) return false;
      select.selectize.setValue(String(selected), true);
      return true;
    }, value);
    expect(applied).toBe(true);
  }

  await page.locator('#frmAccount input[name="password"]').fill(`Empty-${suffix}!`);
  await page.locator('#frmAccount input[name="password_repeat"]').fill(`Empty-${suffix}!`);

  const responsePromise = page.waitForResponse(
    (response) =>
      response.request().method() === 'POST' && parseActionRoute(response.url()) === 'account/saveCreate',
  );
  await page.locator('.item-actions button[type="submit"][form="frmAccount"]').first().click();
  const response = await responsePromise;
  expect(response.status()).toBe(200);
  const envelope = (await response.json()) as { status?: string; data?: { itemId?: number } };
  expect(envelope.status).toBe('OK');
  expect(envelope.data?.itemId).toBeGreaterThan(0);

  await tracker.waitForQuiet(since);

  const security = await page.evaluate(() => {
    const app = (0, eval)('sysPassApp');
    return { sk: String(app.sk.get()), csrf: String(app.config.CSRF) };
  });

  return { id: envelope.data!.itemId!, sk: security.sk, csrf: security.csrf };
}

test('accountFile/list loads the seeded file list on account/view and account/edit', async ({
  sessionPage: { page, context, session },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const childId = FIXTURE.ids.accounts.child;
  const listRoute = `accountFile/list/${childId}`;

  try {
    await openSeededShell(page, tracker, session);

    const viewListPromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === listRoute,
    );
    const link = page
      .locator('#search-rows .account-label', { hasText: FIXTURE.names.childAccount })
      .locator('[data-onclick="account/view"]')
      .first();
    await link.click();

    const viewListResponse = await viewListPromise;
    expect(viewListResponse.status()).toBe(200);
    expect(new URL(viewListResponse.url()).searchParams.get('del')).toBe('0');

    await page.locator('a[href="#file-panel"]').click();
    await expect(page.locator('#list-account-files #files-wrap')).toBeVisible();
    await expect(page.locator('#list-account-files')).toContainText(FIXTURE.names.childFile);
    await expect(page.locator('#list-account-files [data-onclick="file/delete"]')).toHaveCount(0);
    await expect(page.locator('#list-account-files p.error')).toHaveCount(0);

    const editListPromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === listRoute,
    );
    await page.locator('.item-actions [data-onclick="account/edit"]').first().click();

    const editListResponse = await editListPromise;
    expect(editListResponse.status()).toBe(200);
    expect(new URL(editListResponse.url()).searchParams.get('del')).toBe('1');

    await page.locator('a[href="#file-panel"]').click();
    await expect(page.locator('#list-account-files #files-wrap')).toBeVisible();
    await expect(page.locator('#list-account-files')).toContainText(FIXTURE.names.childFile);
    await expect(page.locator('#list-account-files [data-onclick="file/delete"]')).toHaveCount(1);
    await expect(page.locator('#list-account-files p.error')).toHaveCount(0);
  } finally {
    tracker.dispose();
  }
});

test('accountFile/list renders the empty-state no_records_found message for an account with no files', async ({
  sessionPage: { page, context, session },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  let created: { id: number; sk: string; csrf: string } | null = null;

  try {
    created = await createDisposableAccount(page, tracker, session);
    const listRoute = `accountFile/list/${created.id}`;

    const listPromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === listRoute,
    );
    await page.goto(`/index.php?r=account/view/${created.id}`, { waitUntil: 'domcontentloaded' });
    const listResponse = await listPromise;
    expect(listResponse.status()).toBe(200);

    await page.locator('a[href="#file-panel"]').click();
    await expect(page.locator('#list-account-files #files-wrap')).toBeAttached();
    await expect(page.locator('#list-account-files .noRes')).toBeVisible();
    await expect(page.locator('#list-account-files .noRes')).toContainText(
      'There are no linked files for the account',
    );
    await expect(page.locator('#list-account-files p.error')).toHaveCount(0);
  } finally {
    if (created) {
      await page.request.post(`/index.php?r=account/saveDelete/${created.id}`, {
        headers: { 'X-CSRF': created.csrf },
        form: { sk: created.sk, isAjax: '1' },
      });
    }
    tracker.dispose();
  }
});

for (const outcome of ['ERROR', 'WARNING'] as const) {
  test(`accountFile/list rejects JSON ${outcome} envelope without injecting raw JSON or overwriting #content`, async ({
    sessionPage: { page, context, session },
  }) => {
    const tracker = new ActionTracker(page, context, { sessionEstablished: false });
    const childId = FIXTURE.ids.accounts.child;
    const listRoute = `accountFile/list/${childId}`;
    const message = `Synthetic file list ${outcome}`;
    const envelope = { status: outcome, description: message, messages: [], data: null };

    try {
      await openSeededShell(page, tracker, session);
      await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));

      await page.route('**/index.php?**', async (route) => {
        const request = route.request();
        if (request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === listRoute) {
          await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(envelope),
          });
        } else {
          await route.continue();
        }
      });

      const listPromise = page.waitForResponse(
        (response) =>
          response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === listRoute,
      );
      await page.goto(`/index.php?r=account/view/${childId}`, { waitUntil: 'domcontentloaded' });
      const listResponse = await listPromise;
      expect(listResponse.status()).toBe(200);

      const toastSelector = outcome === 'ERROR' ? '.toast-error' : '.toast-warning';
      await expect(page.locator(toastSelector).filter({ hasText: message })).toBeVisible();
      await expect(page.locator('.toast-success')).toHaveCount(0);

      await expect(page.locator('#title')).toContainText('Account Details');
      await expect(page.locator('#frmAccount')).toHaveAttribute('data-item-id', String(childId));

      await page.locator('a[href="#file-panel"]').click();
      const errorBox = page.locator('#list-account-files p.error.round');
      await expect(errorBox).toBeVisible();
      await expect(errorBox).toHaveText(message);
      await expect(page.locator('#list-account-files #files-wrap')).toHaveCount(0);

      const containerText = await page.locator('#list-account-files').innerText();
      expect(containerText).not.toContain('"status"');
      expect(containerText).not.toContain('{');
      expect(containerText).not.toContain(FIXTURE.names.childFile);
    } finally {
      await page.unroute('**/index.php?**');
      tracker.dispose();
    }
  });
}

test('accountFile/list handles plain-text denied responses without raw HTML injection or overwriting #content', async ({
  sessionPage: { page, context, session },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const childId = FIXTURE.ids.accounts.child;
  const listRoute = `accountFile/list/${childId}`;
  const deniedText = "You don't have permission to access this account";

  try {
    await openSeededShell(page, tracker, session);
    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));

    await page.route('**/index.php?**', async (route) => {
      const request = route.request();
      if (request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === listRoute) {
        await route.fulfill({
          status: 200,
          contentType: 'text/plain; charset=utf-8',
          body: deniedText,
        });
      } else {
        await route.continue();
      }
    });

    const listPromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === listRoute,
    );
    await page.goto(`/index.php?r=account/edit/${childId}`, { waitUntil: 'domcontentloaded' });
    const listResponse = await listPromise;
    expect(listResponse.status()).toBe(200);

    await expect(page.locator('.toast-error').filter({ hasText: deniedText })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);
    await expect(page.locator('#title')).toContainText('Edit Account');
    await expect(page.locator('#frmAccount')).toHaveAttribute('data-action-route', 'account/saveEdit');

    await page.locator('a[href="#file-panel"]').click();
    const errorBox = page.locator('#list-account-files p.error.round');
    await expect(errorBox).toBeVisible();
    await expect(errorBox).toHaveText(deniedText);
    await expect(page.locator('#list-account-files #files-wrap')).toHaveCount(0);
  } finally {
    await page.unroute('**/index.php?**');
    tracker.dispose();
  }
});

for (const mode of ['403', '500', 'transport'] as const) {
  test(`accountFile/list handles ${mode} failure without overwriting #content`, async ({
    sessionPage: { page, context, session },
  }) => {
    const tracker = new ActionTracker(page, context, { sessionEstablished: false });
    const childId = FIXTURE.ids.accounts.child;
    const listRoute = `accountFile/list/${childId}`;

    try {
      await openSeededShell(page, tracker, session);
      await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));

      await page.route('**/index.php?**', async (route) => {
        const request = route.request();
        if (request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === listRoute) {
          if (mode === 'transport') {
            await route.abort('failed');
          } else if (mode === '403') {
            await route.fulfill({
              status: 403,
              contentType: 'text/plain; charset=utf-8',
              body: "You don't have permission to do this operation",
            });
          } else {
            await route.fulfill({
              status: 500,
              contentType: 'text/plain; charset=utf-8',
              body: 'Internal error',
            });
          }
        } else {
          await route.continue();
        }
      });

      const requestPromise = page.waitForRequest(
        (request) => request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === listRoute,
      );
      const responsePromise =
        mode === 'transport'
          ? null
          : page.waitForResponse(
              (response) =>
                response.request().method() === 'GET' &&
                new URL(response.url()).searchParams.get('r') === listRoute,
            );

      await page.goto(`/index.php?r=account/edit/${childId}`, { waitUntil: 'domcontentloaded' });
      await requestPromise;
      if (responsePromise) {
        const response = await responsePromise;
        expect(response.status()).toBe(mode === '403' ? 403 : 500);
      }

      await expect(page.locator('.toast-error')).toBeVisible();
      await expect(page.locator('.toast-success')).toHaveCount(0);
      await expect(page.locator('#title')).toContainText('Edit Account');
      await expect(page.locator('#frmAccount')).toHaveAttribute('data-action-route', 'account/saveEdit');
      await expect(page.locator('#frmAccount')).toHaveAttribute('data-item-id', String(childId));

      await page.locator('a[href="#file-panel"]').click();
      const errorBox = page.locator('#list-account-files p.error.round');
      await expect(errorBox).toBeVisible();
      await expect(errorBox).toHaveText('There was an error');
      await expect(page.locator('#list-account-files #files-wrap')).toHaveCount(0);
    } finally {
      await page.unroute('**/index.php?**');
      tracker.dispose();
    }
  });
}

test('accountFile/list denies a restricted user without files permission on the real backend', async ({
  isolatedPage: { page },
}) => {
  await loginThroughUi(page, RESTRICTED_CREDENTIALS);
  const sk = await page.evaluate(() => String((0, eval)('sysPassApp').sk.get()));

  const response = await page.request.get(
    `/index.php?r=accountFile/list/${FIXTURE.ids.accounts.child}&sk=${encodeURIComponent(sk)}&isAjax=1`,
  );
  expect(response.status()).toBe(200);
  const body = await response.text();
  expect(body).toBe("You don't have permission to do this operation");
  expect(body).not.toContain(FIXTURE.names.childFile);
});
