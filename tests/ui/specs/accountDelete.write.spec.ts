import type { Page, Request as PlaywrightRequest } from '@playwright/test';

import {
  validateDeleteAbsence,
  validateDeleteDispatch,
  validateDeleteEnvelope,
} from '../src/account-delete-contract';
import { accountCreateContract } from '../src/contracts';
import { FIXTURE } from '../src/fixture';
import { ActionTracker, parseActionRoute } from '../src/readiness';
import { expect, test } from '../src/session';
import { openSeededShell } from '../src/tier-b';

const SAVE_CREATE_ROUTE = 'account/saveCreate';
const SAVE_DELETE_ROUTE = 'account/saveDelete';
const DELETE_HANDLER = 'account/delete';

function uniqueSuffix(): string {
  return `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

async function selectFixtureValue(page: Page, selector: string, value: number): Promise<void> {
  await expect(page.locator(`${selector} + .selectize-control`)).toHaveCount(1);

  const applied = await page.locator(selector).evaluate((element, selected) => {
    const select = element as HTMLSelectElement & {
      selectize?: { setValue(value: string, silent?: boolean): void };
    };

    if (!select.selectize) {
      return false;
    }

    select.selectize.setValue(String(selected), true);

    return true;
  }, value);

  expect(applied, `${selector} must be selectize-bound`).toBe(true);
  await expect(page.locator(selector)).toHaveValue(String(value));
}

function createdItemId(envelope: unknown): number {
  if (envelope === null || typeof envelope !== 'object' || Array.isArray(envelope)) {
    throw new Error(`${SAVE_CREATE_ROUTE} response was not a JSON object envelope`);
  }

  const response = envelope as { status?: unknown; data?: { itemId?: unknown } };
  const itemId = response.data?.itemId;

  if (response.status !== 'OK' || typeof itemId !== 'number' || !Number.isInteger(itemId) || itemId <= 0) {
    throw new Error(`${SAVE_CREATE_ROUTE} did not return the success envelope with a positive itemId`);
  }

  return itemId;
}

async function createDisposableAccount(
  page: Page,
  tracker: ActionTracker,
): Promise<{ itemId: number; name: string }> {
  const createLink = page.locator('a.btn-menu[data-route="account/create"]').first();
  await expect(createLink).toHaveAttribute('data-view', 'account');

  const createContract = accountCreateContract({
    mode: 'in-shell',
    parentAccount: FIXTURE.names.parentAccount,
  });
  const createSince = Date.now();
  tracker.setContract(createContract);
  await createLink.click();
  await tracker.waitForReady(createContract, createSince);

  const suffix = uniqueSuffix();
  const name = `E2E UI DELETE ${suffix}`;
  const login = `ui-delete-${suffix}`;
  const password = `De1ete-${suffix}!`;

  await page.locator('#name').fill(name);
  await page.locator('#login').fill(login);
  await selectFixtureValue(page, '#client_id', FIXTURE.ids.clients.primary);
  await selectFixtureValue(page, '#category_id', FIXTURE.ids.categories.primary);
  await page.locator('#frmAccount input[name="password"]').fill(password);
  await page.locator('#frmAccount input[name="password_repeat"]').fill(password);

  const save = page.locator('.item-actions button[type="submit"][form="frmAccount"]').first();
  await expect(save).toHaveCount(1);

  const submitSince = Date.now();
  const responsePromise = page.waitForResponse(
    (response) =>
      response.request().method() === 'POST' &&
      parseActionRoute(response.url()) === SAVE_CREATE_ROUTE,
    { timeout: 15_000 },
  );

  await save.click();
  const response = await responsePromise;

  expect(response.status()).toBe(200);
  const itemId = createdItemId(await response.json());
  await tracker.waitForQuiet(submitSince);

  return { itemId, name };
}

test('account delete opens the dialog and dispatches the advertised form-body route', async ({
  sessionPage: { page, context, session },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const deleteRequests: PlaywrightRequest[] = [];
  const legacyRequests: PlaywrightRequest[] = [];
  const onRequest = (request: PlaywrightRequest): void => {
    let url: URL;

    try {
      url = new URL(request.url());
    } catch {
      return;
    }

    const body = request.postData();
    const urlRoute = url.searchParams.get('r');
    const bodyRoute = body ? new URLSearchParams(body).get('r') : null;

    for (const route of [urlRoute, bodyRoute]) {
      if (route === DELETE_HANDLER || route?.startsWith(`${DELETE_HANDLER}/`)) {
        legacyRequests.push(request);
        break;
      }
    }

    if (request.method() === 'POST' && url.pathname.endsWith('/index.php')) {
      deleteRequests.push(request);
    }
  };

  try {
    await openSeededShell(page, tracker, session);

    const { itemId, name } = await createDisposableAccount(page, tracker);

    const indexSince = Date.now();
    await page.goto('/index.php?r=account/index', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#wrap-loading')).toBeHidden();
    await tracker.waitForQuiet(indexSince, Date.now() + 15_000);
    await expect(page.locator('#frmSearch')).toHaveAttribute('data-action-route', 'account/search');
    await expect(page.locator('#search')).toBeVisible();

    await page.locator('#search').fill(name);

    const searchResponsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        parseActionRoute(response.url()) === 'account/search',
      { timeout: 15_000 },
    );

    await page.locator('#search').press('Enter');
    const searchResponse = await searchResponsePromise;
    expect(searchResponse.status()).toBe(200);

    const deleteControl = page.locator(`#search-rows [data-onclick="${DELETE_HANDLER}"]`);
    await expect(deleteControl).toHaveCount(1);
    await expect(deleteControl).toHaveAttribute('data-action-route', SAVE_DELETE_ROUTE);
    await expect(deleteControl).toHaveAttribute('data-item-id', String(itemId));

    page.on('request', onRequest);

    const deleteSince = Date.now();
    const deleteResponsePromise = page.waitForResponse(
      (response) => {
        if (response.request().method() !== 'POST') {
          return false;
        }

        try {
          if (!new URL(response.url()).pathname.endsWith('/index.php')) {
            return false;
          }
        } catch {
          return false;
        }

        const route = new URLSearchParams(response.request().postData() ?? '').get('r');
        return route?.startsWith('account/') === true;
      },
      { timeout: 15_000 },
    );

    await deleteControl.click();

    const dialog = page.locator('#orrsDiag');
    await expect(dialog).toBeVisible();
    await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();

    await page.locator('#orrsDiag #positive').click();
    const deleteResponse = await deleteResponsePromise;

    expect(deleteResponse.status()).toBe(200);

    if (legacyRequests.length > 0) {
      throw new Error('the delete flow dispatched the legacy account/delete/<id> route');
    }

    const dispatch = validateDeleteDispatch({
      requests: deleteRequests.map((recorded) => ({
        method: recorded.method(),
        url: recorded.url(),
        headers: recorded.headers(),
        body: recorded.postData() ?? '',
      })),
      itemId,
    });

    if (!dispatch.ok) {
      throw new Error(dispatch.message);
    }

    const envelope = validateDeleteEnvelope(await deleteResponse.json());

    if (!envelope.ok) {
      throw new Error(envelope.message);
    }

    await tracker.waitForActions(['account/search'], deleteSince, Date.now() + 15_000);
    await tracker.waitForQuiet(deleteSince);

    const origin = new URL(page.url()).origin;
    const absenceResponse = await page.request.get(
      `${origin}/index.php?r=account/search&search=${encodeURIComponent(name)}`,
    );
    expect(absenceResponse.status()).toBe(200);

    const absence = validateDeleteAbsence({
      envelope: await absenceResponse.json(),
      name,
      itemId,
    });

    if (!absence.ok) {
      throw new Error(absence.message);
    }
  } finally {
    page.off('request', onRequest);
    tracker.dispose();
  }
});
