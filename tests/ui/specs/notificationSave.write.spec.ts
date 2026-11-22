import type { Page, Request as PlaywrightRequest } from '@playwright/test';

import { FIXTURE } from '../src/fixture';
import { ActionTracker, parseActionRoute } from '../src/readiness';
import { expect, test } from '../src/session';
import { runTierBIndex, TIER_B_INDEX_CASES } from '../src/tier-b';

interface SecurityTokens {
  sk: string;
  csrf: string;
}

async function getSecurityTokens(page: Page): Promise<SecurityTokens> {
  return page.evaluate(() => {
    const app = (0, eval)('sysPassApp');
    return { sk: app.sk.get(), csrf: app.config.CSRF };
  });
}

async function createSyntheticNotification(
  page: Page,
  security: SecurityTokens,
  fields: { type: string; component: string; description: string; checked?: boolean },
): Promise<void> {
  const form: Record<string, string> = {
    notification_type: fields.type,
    notification_component: fields.component,
    notification_description: fields.description,
    notification_user: String(FIXTURE.ids.users.admin),
    sk: security.sk,
    isAjax: '1',
  };
  if (fields.checked) {
    form.notification_checkout = '1';
  }

  const created = await page.request.post('/index.php?r=notification/saveCreate', {
    headers: { 'X-CSRF': security.csrf },
    form,
  });
  expect(created.status()).toBe(200);
  const body = (await created.json()) as { status?: string };
  expect(body.status).toBe('OK');
}

async function deleteNotificationById(
  page: Page,
  security: SecurityTokens,
  id: string,
): Promise<void> {
  await page.request.get(
    `/index.php?r=notification/delete/${id}&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
  );
}

async function readNotificationSearch(page: Page, security: SecurityTokens): Promise<string> {
  const response = await page.request.get(
    `/index.php?r=notification/search&count=1000&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
  );
  expect(response.status()).toBe(200);
  const body = (await response.json()) as { status?: string; data?: { html?: string } };
  expect(body.status).toBe('OK');
  return body.data?.html ?? '';
}

async function readNotificationView(
  page: Page,
  security: SecurityTokens,
  id: string,
): Promise<string> {
  const response = await page.request.get(
    `/index.php?r=notification/view/${id}&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
  );
  expect(response.status()).toBe(200);
  const body = (await response.json()) as { status?: string; data?: { html?: string } };
  expect(body.status).toBe('OK');
  return body.data?.html ?? '';
}

function shortId(): string {
  return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 6)}`;
}

test('notification create dispatches notification/saveCreate, closes modal, refreshes list and badge, and persists', async ({
  sessionPage: { page, context, session },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const suffix = shortId();
  const type = 'Notice';
  const component = `NCmp-${suffix}`;
  const description = `NDsc-${suffix}`;
  const requests: string[] = [];
  const onRequest = (request: PlaywrightRequest) => {
    const action = parseActionRoute(request.url());
    if (request.method() === 'GET' && (action === 'notification/index' || action === 'items/notifications')) {
      requests.push(action);
    }
  };
  let security: SecurityTokens | null = null;
  let createdId: string | null = null;

  try {
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.notification);
    security = await getSecurityTokens(page);

    const initialBadgeRead = await page.request.get(
      `/index.php?r=items/notifications&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
    );
    expect(initialBadgeRead.status()).toBe(200);
    const initialBadgeJson = (await initialBadgeRead.json()) as {
      status?: string;
      data?: { count?: number };
    };
    expect(initialBadgeJson.status).toBe('OK');
    const initialBadgeCount = initialBadgeJson.data?.count ?? 0;

    const addButton = page.locator(
      'button[data-onclick="notification/show"][data-action-route="notification/create"]',
    );
    await expect(addButton).toBeVisible();

    const createFormResponse = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        parseActionRoute(response.url()) === 'notification/create',
    );
    await addButton.click();
    expect((await createFormResponse).status()).toBe(200);

    const form = page.locator('#box-popup #frmNotices');
    await expect(form).toBeVisible();
    await expect(form).toHaveAttribute('data-onsubmit', 'notification/save');
    await expect(form).toHaveAttribute('data-route', 'notification/saveCreate');
    await expect(form).toHaveAttribute('data-action-next', 'notification/index');

    await form.locator('#notification_type').fill(type);
    await form.locator('#notification_component').fill(component);
    await form.locator('#notification_description').fill(description);
    const userSelected = await form.locator('#notification_user').evaluate((element, userId) => {
      const select = element as HTMLSelectElement & {
        selectize?: { setValue(value: string, silent?: boolean): void };
      };
      if (!select.selectize) return false;
      select.selectize.setValue(userId, true);
      return select.value === userId;
    }, String(FIXTURE.ids.users.admin));
    expect(userSelected).toBe(true);

    const grid = page.locator('#data-table-tblNotifications');
    const badgeLocator = page.locator('.notifications-badge');
    await expect(badgeLocator.first()).toBeAttached();
    await grid.evaluate((element) => element.setAttribute('data-test-stale-list', 'true'));
    await badgeLocator.evaluateAll((elements) =>
      elements.forEach((element) => element.setAttribute('data-badge', 'stale')),
    );
    await page.locator('#toast-container').evaluateAll((nodes) =>
      nodes.forEach((node) => node.remove()),
    );

    page.on('request', onRequest);
    const saveResponsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'POST' &&
        parseActionRoute(response.url()) === 'notification/saveCreate',
    );
    const badgeResponsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        parseActionRoute(response.url()) === 'items/notifications',
    );

    await page.locator('#box-popup button[form="frmNotices"]').click();

    const saveResponse = await saveResponsePromise;
    expect(saveResponse.status()).toBe(200);
    const postParams = new URLSearchParams(saveResponse.request().postData() ?? '');
    expect(postParams.get('notification_type')).toBe(type);
    expect(postParams.get('notification_component')).toBe(component);
    expect(postParams.get('notification_description')).toBe(description);
    expect(postParams.get('notification_user')).toBe(String(FIXTURE.ids.users.admin));

    const saveJson = (await saveResponse.json()) as { status?: string; description?: string };
    expect(saveJson.status).toBe('OK');

    const badgeResponse = await badgeResponsePromise;
    expect(badgeResponse.status()).toBe(200);
    const badgeData = (await badgeResponse.json()) as {
      status?: string;
      data?: { count?: number };
    };
    expect(badgeData.status).toBe('OK');
    expect(badgeData.data?.count).toBe(initialBadgeCount + 1);

    await expect(page.locator('#box-popup #frmNotices')).toHaveCount(0);
    await expect(
      page.locator('.toast-success').filter({ hasText: saveJson.description ?? '' }),
    ).toBeVisible();
    await expect(page.locator('.toast-error')).toHaveCount(0);
    await expect(page.locator('.toast-warning')).toHaveCount(0);

    await expect(grid).not.toHaveAttribute('data-test-stale-list', 'true');
    const createdRow = grid.locator('tr').filter({ hasText: description });
    await expect(createdRow).toHaveCount(1);
    const id = await createdRow.getAttribute('data-item-id');
    expect(id).toMatch(/^\d+$/);
    createdId = id;

    await expect
      .poll(() =>
        badgeLocator.evaluateAll((elements) =>
          elements.map((element) => element.getAttribute('data-badge')),
        ),
      )
      .toEqual(Array(await badgeLocator.count()).fill(String(badgeData.data?.count)));
    expect(requests).toEqual(['notification/index', 'items/notifications']);

    const searchHtml = await readNotificationSearch(page, security);
    expect(searchHtml).toContain(description);
    expect(searchHtml).toContain(component);

    const viewHtml = await readNotificationView(page, security, createdId!);
    expect(viewHtml).toContain(component);
    expect(viewHtml).toContain(description);
  } finally {
    page.off('request', onRequest);
    if (createdId && security) {
      await deleteNotificationById(page, security, createdId);
    }
    tracker.dispose();
  }
});

test('notification edit dispatches notification/saveEdit/{id}, closes modal, refreshes list and badge, and persists', async ({
  sessionPage: { page, context, session },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const suffix = shortId();
  const origComponent = `OCmp-${suffix}`;
  const origDescription = `ODsc-${suffix}`;
  const updatedType = 'Warning';
  const updatedComponent = `ECmp-${suffix}`;
  const updatedDescription = `EDsc-${suffix}`;
  const requests: string[] = [];
  const onRequest = (request: PlaywrightRequest) => {
    const action = parseActionRoute(request.url());
    if (request.method() === 'GET' && (action === 'notification/index' || action === 'items/notifications')) {
      requests.push(action);
    }
  };
  let security: SecurityTokens | null = null;
  let createdId: string | null = null;

  try {
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.notification);
    security = await getSecurityTokens(page);

    await createSyntheticNotification(page, security, {
      type: 'Notice',
      component: origComponent,
      description: origDescription,
      checked: false,
    });

    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.notification);
    const grid = page.locator('#data-table-tblNotifications');
    const row = grid.locator('tr').filter({ hasText: origDescription });
    await expect(row).toHaveCount(1);
    const id = await row.getAttribute('data-item-id');
    expect(id).toMatch(/^\d+$/);
    createdId = id;

    const editControl = row.locator('[data-onclick="notification/show"][data-action-route="notification/edit"]');
    await expect(editControl).toHaveAttribute('data-item-id', id!);

    const editFormResponse = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        new URL(response.url()).searchParams.get('r') === `notification/edit/${id}`,
    );
    await editControl.click();
    expect((await editFormResponse).status()).toBe(200);

    const form = page.locator('#box-popup #frmNotices');
    await expect(form).toBeVisible();
    await expect(form).toHaveAttribute('data-onsubmit', 'notification/save');
    await expect(form).toHaveAttribute('data-route', `notification/saveEdit/${id}`);
    await expect(form).toHaveAttribute('data-action-next', 'notification/index');

    await form.locator('#notification_type').fill(updatedType);
    await form.locator('#notification_component').fill(updatedComponent);
    await form.locator('#notification_description').fill(updatedDescription);
    await form.locator('label[for="notification_checkout"]').click();
    await expect(form.locator('#notification_checkout')).toBeChecked();

    const badgeLocator = page.locator('.notifications-badge');
    await expect(badgeLocator.first()).toBeAttached();
    await grid.evaluate((element) => element.setAttribute('data-test-stale-list', 'true'));
    await badgeLocator.evaluateAll((elements) =>
      elements.forEach((element) => element.setAttribute('data-badge', 'stale')),
    );
    await page.locator('#toast-container').evaluateAll((nodes) =>
      nodes.forEach((node) => node.remove()),
    );

    page.on('request', onRequest);
    const saveResponsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'POST' &&
        new URL(response.url()).searchParams.get('r') === `notification/saveEdit/${id}`,
    );
    const badgeResponsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        parseActionRoute(response.url()) === 'items/notifications',
    );

    await page.locator('#box-popup button[form="frmNotices"]').click();

    const saveResponse = await saveResponsePromise;
    expect(saveResponse.status()).toBe(200);
    const postParams = new URLSearchParams(saveResponse.request().postData() ?? '');
    expect(postParams.get('notification_type')).toBe(updatedType);
    expect(postParams.get('notification_component')).toBe(updatedComponent);
    expect(postParams.get('notification_description')).toBe(updatedDescription);

    const saveJson = (await saveResponse.json()) as { status?: string; description?: string };
    expect(saveJson.status).toBe('OK');

    const badgeResponse = await badgeResponsePromise;
    expect(badgeResponse.status()).toBe(200);
    const badgeData = (await badgeResponse.json()) as {
      status?: string;
      data?: { count?: number };
    };
    expect(badgeData.status).toBe('OK');

    await expect(page.locator('#box-popup #frmNotices')).toHaveCount(0);
    await expect(
      page.locator('.toast-success').filter({ hasText: saveJson.description ?? '' }),
    ).toBeVisible();
    await expect(page.locator('.toast-error')).toHaveCount(0);
    await expect(page.locator('.toast-warning')).toHaveCount(0);

    await expect(grid).not.toHaveAttribute('data-test-stale-list', 'true');
    const updatedRow = grid.locator(`tr[data-item-id="${id}"]`);
    await expect(updatedRow).toContainText(updatedDescription);
    await expect(updatedRow).toContainText(updatedComponent);
    await expect(updatedRow).not.toContainText(origDescription);

    await expect
      .poll(() =>
        badgeLocator.evaluateAll((elements) =>
          elements.map((element) => element.getAttribute('data-badge')),
        ),
      )
      .toEqual(Array(await badgeLocator.count()).fill(String(badgeData.data?.count)));
    expect(requests).toEqual(['notification/index', 'items/notifications']);

    const searchHtml = await readNotificationSearch(page, security);
    expect(searchHtml).toContain(updatedDescription);
    expect(searchHtml).toContain(updatedComponent);
    expect(searchHtml).not.toContain(origDescription);

    const viewHtml = await readNotificationView(page, security, id!);
    expect(viewHtml).toContain(updatedComponent);
    expect(viewHtml).toContain(updatedDescription);
    expect(viewHtml).not.toContain(origDescription);
  } finally {
    page.off('request', onRequest);
    if (createdId && security) {
      await deleteNotificationById(page, security, createdId);
    }
    tracker.dispose();
  }
});

for (const outcome of ['ERROR', 'WARNING', 'transport'] as const) {
  test(`notification save keeps modal open and does not refresh or persist on a ${outcome} outcome`, async ({
    sessionPage: { page, context, session },
  }) => {
    const tracker = new ActionTracker(page, context, { sessionEstablished: false });
    const suffix = shortId();
    const tag = outcome === 'transport' ? 'TR' : outcome;
    const origComponent = `OC-${tag}-${suffix}`;
    const origDescription = `OD-${tag}-${suffix}`;
    const failedDescription = `FD-${tag}-${suffix}`;
    const message = `Synthetic notification save ${outcome}`;
    const requests: string[] = [];
    const onRequest = (request: PlaywrightRequest) => {
      const action = parseActionRoute(request.url());
      if (
        request.method() === 'GET' &&
        (action === 'notification/index' || action === 'items/notifications')
      ) {
        requests.push(action);
      }
    };
    let security: SecurityTokens | null = null;
    let createdId: string | null = null;

    try {
      await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.notification);
      security = await getSecurityTokens(page);

      await createSyntheticNotification(page, security, {
        type: 'Notice',
        component: origComponent,
        description: origDescription,
        checked: true,
      });

      await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.notification);
      const grid = page.locator('#data-table-tblNotifications');
      const row = grid.locator('tr').filter({ hasText: origDescription });
      await expect(row).toHaveCount(1);
      const id = await row.getAttribute('data-item-id');
      expect(id).toMatch(/^\d+$/);
      createdId = id;

      const editControl = row.locator('[data-onclick="notification/show"][data-action-route="notification/edit"]');
      const editFormResponse = page.waitForResponse(
        (response) =>
          response.request().method() === 'GET' &&
          new URL(response.url()).searchParams.get('r') === `notification/edit/${id}`,
      );
      await editControl.click();
      expect((await editFormResponse).status()).toBe(200);

      const form = page.locator('#box-popup #frmNotices');
      await expect(form).toBeVisible();
      await form.locator('#notification_description').fill(failedDescription);

      const badgeLocator = page.locator('.notifications-badge');
      await expect(badgeLocator.first()).toBeAttached();
      await grid.evaluate((element) => element.setAttribute('data-test-stale-list', 'true'));
      await badgeLocator.evaluateAll((elements) =>
        elements.forEach((element) => element.setAttribute('data-badge', 'stale')),
      );
      await page.locator('#toast-container').evaluateAll((nodes) =>
        nodes.forEach((node) => node.remove()),
      );

      const saveRoute = `notification/saveEdit/${id}`;
      await page.route('**/index.php?**', async (route) => {
        const request = route.request();
        if (
          request.method() === 'POST' &&
          new URL(request.url()).searchParams.get('r') === saveRoute
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

      page.on('request', onRequest);
      const requestPromise = page.waitForRequest(
        (request) =>
          request.method() === 'POST' &&
          new URL(request.url()).searchParams.get('r') === saveRoute,
      );
      const responsePromise =
        outcome === 'transport'
          ? null
          : page.waitForResponse(
              (response) =>
                response.request().method() === 'POST' &&
                new URL(response.url()).searchParams.get('r') === saveRoute,
            );

      await page.locator('#box-popup button[form="frmNotices"]').click();
      const dispatchedRequest = await requestPromise;
      expect(
        new URLSearchParams(dispatchedRequest.postData() ?? '').get('notification_description'),
      ).toBe(failedDescription);

      if (responsePromise) {
        const response = await responsePromise;
        expect(response.status()).toBe(200);
        expect(await response.json()).toEqual({
          status: outcome,
          description: message,
          messages: [],
          data: null,
        });
        const toastSelector = outcome === 'ERROR' ? '.toast-error' : '.toast-warning';
        await expect(page.locator(toastSelector).filter({ hasText: message })).toBeVisible();
      } else {
        await expect(page.locator('.toast-error')).toBeVisible();
      }

      await expect(page.locator('.toast-success')).toHaveCount(0);
      await expect(form).toBeVisible();
      await expect(form.locator('#notification_description')).toHaveValue(failedDescription);

      await page.waitForTimeout(1000);
      expect(requests).toEqual([]);
      await expect(grid).toHaveAttribute('data-test-stale-list', 'true');
      await expect
        .poll(() =>
          badgeLocator.evaluateAll((elements) =>
            elements.map((element) => element.getAttribute('data-badge')),
          ),
        )
        .toEqual(Array(await badgeLocator.count()).fill('stale'));

      const searchHtml = await readNotificationSearch(page, security);
      expect(searchHtml).toContain(origDescription);
      expect(searchHtml).not.toContain(failedDescription);

      const viewHtml = await readNotificationView(page, security, id!);
      expect(viewHtml).toContain(origDescription);
      expect(viewHtml).not.toContain(failedDescription);
    } finally {
      page.off('request', onRequest);
      await page.unroute('**/index.php?**');
      if (createdId && security) {
        await deleteNotificationById(page, security, createdId);
      }
      tracker.dispose();
    }
  });
}
