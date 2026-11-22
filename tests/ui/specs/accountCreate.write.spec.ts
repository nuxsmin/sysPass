import type { Page, Request as PlaywrightRequest } from '@playwright/test';

import {
  validateCreateDispatch,
  validateCreateEnvelope,
  validateCreatePersistence,
} from '../src/account-create-contract';
import { accountCreateContract } from '../src/contracts';
import { FIXTURE } from '../src/fixture';
import { ActionTracker, parseActionRoute } from '../src/readiness';
import { expect, test } from '../src/session';
import { openSeededShell } from '../src/tier-b';

const SAVE_CREATE_ROUTE = 'account/saveCreate';

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

test('account create submits through the JS form and persists the account', async ({
  sessionPage: { page, context, session },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const writeRequests: PlaywrightRequest[] = [];
  const onRequest = (request: PlaywrightRequest): void => {
    if (request.method() === 'POST' && parseActionRoute(request.url()) === SAVE_CREATE_ROUTE) {
      writeRequests.push(request);
    }
  };

  try {
    page.on('request', onRequest);

    await openSeededShell(page, tracker, session);

    const createLink = page.locator('a.btn-menu[data-route="account/create"]').first();
    await expect(createLink).toHaveAttribute('data-view', 'account');

    const createContract = accountCreateContract({
      mode: 'in-shell',
      parentAccount: FIXTURE.names.parentAccount,
    });
    tracker.setContract(createContract);
    const createSince = Date.now();
    await createLink.click();
    await tracker.waitForReady(createContract, createSince);

    const suffix = uniqueSuffix();
    const name = `E2E UI WRITE ${suffix}`;
    const login = `ui-write-${suffix}`;
    const password = `Wr1te-${suffix}!`;

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
    await tracker.waitForActions([SAVE_CREATE_ROUTE], submitSince, Date.now() + 15_000);
    expect(writeRequests).toHaveLength(1);

    const dispatch = validateCreateDispatch({
      requests: writeRequests.map((recorded) => ({
        method: recorded.method(),
        url: recorded.url(),
        headers: recorded.headers(),
        body: recorded.postData() ?? '',
      })),
      expected: {
        name,
        login,
        clientId: FIXTURE.ids.clients.primary,
        categoryId: FIXTURE.ids.categories.primary,
        password,
      },
    });

    if (!dispatch.ok) {
      throw new Error(dispatch.message);
    }

    const envelope = validateCreateEnvelope(tracker.getJson(SAVE_CREATE_ROUTE, submitSince));

    if (!envelope.ok) {
      throw new Error(envelope.message);
    }

    const accountId = envelope.itemId;
    const nextRoute = envelope.nextAction;

    await tracker.waitForActions([nextRoute], submitSince, Date.now() + 15_000);
    await tracker.waitForQuiet(submitSince);

    await page.goto(`/index.php?r=account/view/${accountId}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#frmAccount')).toHaveAttribute('data-item-id', String(accountId));

    const persistence = validateCreatePersistence({
      markup: await page.content(),
      name,
      login,
    });

    if (!persistence.ok) {
      throw new Error(persistence.message);
    }

    const editControl = page.locator('.item-actions [data-onclick="account/edit"]').first();
    await expect(editControl).toHaveCount(1);
    const renderedRoute = await editControl.getAttribute('data-action-route');
    const renderedItemId = await editControl.getAttribute('data-item-id');
    expect(renderedRoute === nextRoute && renderedItemId === String(accountId)).toBe(true);
  } finally {
    page.off('request', onRequest);
    tracker.dispose();
  }
});
