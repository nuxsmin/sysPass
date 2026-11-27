// Copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

    select.selectize.setValue(String(selected));

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

    await page.goto(`/index.php?r=account/view/${FIXTURE.ids.accounts.parent}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#client_id + .selectize-control .item')).toHaveText(FIXTURE.names.client);
    await page.goto(`/index.php?r=account/edit/${FIXTURE.ids.accounts.parent}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#client_id')).toHaveValue(String(FIXTURE.ids.clients.primary));
    await openSeededShell(page, tracker, session);

    const createLink = page.locator('.btn-menu[data-route="account/create"]').first();
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
    await selectFixtureValue(page, '#tags', FIXTURE.ids.tags.primary);
    await page.locator('a[href="#permission-panel"]').click();
    await selectFixtureValue(page, '#other_users_view', FIXTURE.ids.users.demo);
    await page.locator('label[for="private_enabled"]').click();
    await page.locator('label[for="private_group_enabled"]').click();
    await page.locator('a[href="#account-panel"]').click();
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
    const submitted = new URLSearchParams(writeRequests[0]!.postData() ?? '');
    expect(submitted.get('tags_update')).toBe('1');
    expect(submitted.getAll('tags[]')).toContain(String(FIXTURE.ids.tags.primary));

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

    await expect(page.locator('#client_id')).toHaveValue(String(FIXTURE.ids.clients.primary));
    await expect(page.locator('#tags')).toHaveValues([String(FIXTURE.ids.tags.primary)]);
    await expect(page.locator('#other_users_view')).toHaveValues([String(FIXTURE.ids.users.demo)]);
    await expect(page.locator('#private_enabled')).toBeChecked();
    await expect(page.locator('#private_group_enabled')).toBeChecked();

    await page.goto(`/index.php?r=account/view/${accountId}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#client_id')).toHaveValue(String(FIXTURE.ids.clients.primary));
    await expect(page.locator('#client_id + .selectize-control')).toContainText(FIXTURE.names.client);
    await expect(page.locator('#private_enabled')).toBeChecked();
    await expect(page.locator('#private_enabled')).toBeDisabled();
    await expect(page.locator('#private_group_enabled')).toBeChecked();
    await expect(page.locator('#private_group_enabled')).toBeDisabled();
    await page.goto(`/index.php?r=account/edit/${accountId}`, { waitUntil: 'domcontentloaded' });

    await selectFixtureValue(page, '#client_id', FIXTURE.ids.clients.secondary);
    await selectFixtureValue(page, '#other_users_view', FIXTURE.ids.users.restricted);
    await page.locator('a[href="#permission-panel"]').click();
    await page.locator('label[for="private_enabled"]').click();
    await page.locator('label[for="private_group_enabled"]').click();
    const updateSince = Date.now();
    const updateRoute = `account/saveEdit/${accountId}`;
    const updateResponse = page.waitForResponse((response) =>
      response.request().method() === 'POST' && new URL(response.url()).searchParams.get('r') === updateRoute,
    );
    await page.locator('.item-actions button[type="submit"][form="frmAccount"]').first().click();
    expect((await (await updateResponse).json()).status).toBe('OK');
    await tracker.waitForActions(['account/view'], updateSince, Date.now() + 15_000);
    await tracker.waitForQuiet(updateSince);
    await expect(page.locator('#client_id + .selectize-control .item')).toBeVisible();
    await expect(page.locator('#client_id + .selectize-control .item')).toHaveText(FIXTURE.names.secondClient);
    await expect(page.locator('#data-accesses')).toContainText(FIXTURE.names.restrictedProfile);
    await page.goto(`/index.php?r=account/edit/${accountId}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#client_id')).toHaveValue(String(FIXTURE.ids.clients.secondary));
    await expect(page.locator('#tags')).toHaveValues([String(FIXTURE.ids.tags.primary)]);
    await expect(page.locator('#other_users_view')).toHaveValues([String(FIXTURE.ids.users.restricted)]);
    await expect(page.locator('#private_enabled')).not.toBeChecked();
    await expect(page.locator('#private_group_enabled')).not.toBeChecked();

    await page.locator('a[href="#permission-panel"]').click();
    await page.locator('label[for="private_enabled"]').click();
    await page.locator('label[for="private_group_enabled"]').click();
    const privateSince = Date.now();
    const privateResponse = page.waitForResponse((response) =>
      response.request().method() === 'POST' && new URL(response.url()).searchParams.get('r') === updateRoute,
    );
    await page.locator('.item-actions button[type="submit"][form="frmAccount"]').first().click();
    expect((await (await privateResponse).json()).status).toBe('OK');
    await tracker.waitForActions(['account/view'], privateSince, Date.now() + 15_000);
    await tracker.waitForQuiet(privateSince);
    await expect(page.locator('#private_enabled')).toBeChecked();
    await expect(page.locator('#private_enabled')).toBeDisabled();
    await expect(page.locator('#private_group_enabled')).toBeChecked();
    await expect(page.locator('#private_group_enabled')).toBeDisabled();

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
