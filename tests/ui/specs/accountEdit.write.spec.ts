import type { Page } from '@playwright/test';
import { accountCreateContract } from '../src/contracts';
import { FIXTURE } from '../src/fixture';
import { ActionTracker, parseActionRoute } from '../src/readiness';
import { expect, test } from '../src/session';
import { navigateTierBIndex, openSeededShell, TIER_B_INDEX_CASES } from '../src/tier-b';

async function createAccount(page: Page, tracker: ActionTracker, session: Parameters<typeof openSeededShell>[2]): Promise<number> {
  await openSeededShell(page, tracker, session);
  const contract = accountCreateContract({ mode: 'in-shell', parentAccount: FIXTURE.names.parentAccount });
  tracker.setContract(contract);
  const since = Date.now();
  await page.locator('a.btn-menu[data-route="account/create"]').first().click();
  await tracker.waitForReady(contract, since);
  const suffix = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
  await page.locator('#name').fill(`Edit fixture ${suffix}`);
  await page.locator('#login').fill(`edit-fixture-${suffix}`);
  for (const [selector, value] of [['#client_id', FIXTURE.ids.clients.primary], ['#category_id', FIXTURE.ids.categories.primary]] as const) {
    const applied = await page.locator(selector).evaluate((element, selected) => {
      const select = element as HTMLSelectElement & { selectize?: { setValue(value: string, silent?: boolean): void } };
      if (!select.selectize) return false;
      select.selectize.setValue(String(selected), true);
      return true;
    }, value);
    expect(applied).toBe(true);
  }
  await page.locator('#frmAccount input[name="password"]').fill(`Ed1t-${suffix}!`);
  await page.locator('#frmAccount input[name="password_repeat"]').fill(`Ed1t-${suffix}!`);
  const responsePromise = page.waitForResponse((response) => response.request().method() === 'POST' && parseActionRoute(response.url()) === 'account/saveCreate');
  await page.locator('.item-actions button[type="submit"][form="frmAccount"]').first().click();
  const response = await responsePromise;
  expect(response.status()).toBe(200);
  const envelope = await response.json();
  expect(envelope.status).toBe('OK');
  expect(envelope.data.itemId).toBeGreaterThan(0);
  await tracker.waitForQuiet(since);
  return envelope.data.itemId;
}

function matchesSave(url: string, id: number): boolean {
  return new URL(url).searchParams.get('r') === `account/saveEdit/${id}`;
}

test('account copy displays a synthetic error without navigating or creating a copy', async ({ sessionPage: { page, context, session } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const sourceId = FIXTURE.ids.accounts.parent;
  const name = `Copy error fixture ${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
  const message = 'Synthetic account copy error';
  const password = `Copy-${Date.now()}-fixture!`;
  const envelope = { status: 'ERROR', description: message, messages: [], data: { itemId: sourceId, nextAction: 'account/view' } };
  let saves = 0;
  let navigations = 0;
  try {
    await openSeededShell(page, tracker, session);
    await page.goto(`/index.php?r=account/view/${sourceId}`, { waitUntil: 'domcontentloaded' });
    await page.locator('#actions-menu-account').click();
    const control = page.locator('.item-actions [data-onclick="account/copy"]');
    await expect(control).toHaveAttribute('data-action-route', 'account/copy');
    await expect(control).toHaveAttribute('data-item-id', String(sourceId));
    const copyResponse = page.waitForResponse((response) => response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === `account/copy/${sourceId}`);
    await control.click();
    expect((await copyResponse).status()).toBe(200);
    const form = page.locator('#frmAccount');
    await expect(form).toHaveAttribute('data-action-route', 'account/saveCopy');
    await form.locator('#name').fill(name);
    await form.locator('input[name="password"]').fill(password);
    await form.locator('input[name="password_repeat"]').fill(password);
    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
    page.on('request', (request) => {
      if (request.method() === 'POST' && parseActionRoute(request.url()) === 'account/saveCopy') saves++;
      if (request.method() === 'GET' && parseActionRoute(request.url()) === 'account/view') navigations++;
    });
    await page.route('**/index.php?**', async (route) => {
      if (route.request().method() === 'POST' && parseActionRoute(route.request().url()) === 'account/saveCopy') {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(envelope) });
      } else await route.continue();
    });
    const since = Date.now();
    const responsePromise = page.waitForResponse((response) => response.request().method() === 'POST' && parseActionRoute(response.url()) === 'account/saveCopy');
    await page.locator('.item-actions button[type="submit"][form="frmAccount"]').first().click();
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual(envelope);
    expect(new URL(response.url()).searchParams.get('r')).toBe(`account/saveCopy/${sourceId}`);
    const fields = new URLSearchParams(response.request().postData() ?? '');
    expect(fields.get('name')).toBe(name);
    for (const field of ['password', 'password_repeat']) {
      expect(Boolean(fields.get(field))).toBe(true);
      expect(fields.get(field) === password).toBe(false);
    }
    await expect(page.locator('.toast-error').filter({ hasText: message })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);
    await tracker.waitForQuiet(since);
    expect(saves).toBe(1);
    expect(navigations).toBe(0);
    await expect(form).toHaveAttribute('data-action-route', 'account/saveCopy');
    await expect(form.locator('#name')).toHaveValue(name);
    const read = await page.request.get(`/index.php?r=account/search&search=${encodeURIComponent(name)}`);
    expect(read.status()).toBe(200);
    const result = await read.json();
    expect(result.status).toBe('OK');
    expect(typeof result.data.html).toBe('string');
    expect(result.data.html).not.toContain(name);
  } finally {
    await page.unroute('**/index.php?**');
    tracker.dispose();
  }
});

test('account history restore displays a synthetic error without navigating or changing the account', async ({ sessionPage: { page, context, session } }) => {
  test.setTimeout(90_000);
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const message = 'Synthetic account restore error';
  let saves = 0;
  let navigations = 0;
  let failure: unknown;
  try {
    const id = await test.step('create the disposable account', () => createAccount(page, tracker, session), { timeout: 20_000 });
    await page.goto(`/index.php?r=account/edit/${id}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#frmAccount')).toHaveAttribute('data-item-id', String(id));
    const historicalName = await page.locator('#name').inputValue();
    const currentName = `Restorable fixture ${Date.now()}`;
    await page.locator('#name').fill(currentName);
    const sinceSave = Date.now();
    const save = page.waitForResponse((response) => response.request().method() === 'POST' && matchesSave(response.url(), id));
    await page.locator('.item-actions button[type="submit"][form="frmAccount"]').first().click();
    expect((await (await test.step('save the changed account', () => save, { timeout: 15_000 })).json()).status).toBe('OK');
    await test.step('wait for save navigation', async () => {
      await tracker.waitForActions(['account/view'], sinceSave, Date.now() + 15_000);
      await tracker.waitForQuiet(sinceSave);
    }, { timeout: 20_000 });
    // Find the generated history row via the manager; this bypasses the account-view history selector.
    await test.step('reopen the authenticated shell', () => openSeededShell(page, tracker, session), { timeout: 20_000 });
    await test.step('open the account history manager', () => navigateTierBIndex(page, tracker, TIER_B_INDEX_CASES.itemManager), { timeout: 20_000 });
    const grid = page.locator('#data-table-tblAccountsHistory');
    const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
    const panelId = await panel.getAttribute('id');
    await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();
    const searchForm = page.locator('#frmSearchAccountHistory');
    await searchForm.locator('label[for^="search-"]').first().click();
    await searchForm.locator('input[name="search"]').fill(historicalName);
    const searchResponse = page.waitForResponse((response) => response.request().method() === 'GET' && parseActionRoute(response.url()) === 'accountHistoryManager/search');
    await searchForm.locator('input[name="search"]').press('Enter');
    expect((await test.step('search account history', () => searchResponse, { timeout: 15_000 })).status()).toBe(200);
    const row = grid.locator('tr').filter({ hasText: historicalName });
    await expect(row).toHaveCount(1);
    const historyId = await row.getAttribute('data-item-id');
    expect(historyId).toMatch(/^\d+$/);
    const historyResponse = await page.goto(`/index.php?r=account/viewHistory/${historyId}`, { waitUntil: 'domcontentloaded' });
    expect(historyResponse?.status()).toBe(200);
    const control = page.locator('.item-actions [data-onclick="account/saveEditRestore"]');
    await expect(control).toHaveAttribute('data-action-route', 'account/saveEditRestore');
    await expect(control).toHaveAttribute('data-history-id', historyId!);
    await expect(control).toHaveAttribute('data-item-id', String(id));
    const restoreRoute = `account/saveEditRestore/${historyId}/${id}`;
    const matchesRestore = (method: string, body: string | null) => method === 'POST' && new URLSearchParams(body ?? '').get('r') === restoreRoute;
    const envelope = { status: 'ERROR', description: message, messages: [], data: { itemId: id, nextAction: 'account/view' } };
    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
    page.on('request', (request) => {
      if (matchesRestore(request.method(), request.postData())) saves++;
      if (request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `account/view/${id}`) navigations++;
    });
    await page.route('**/index.php', async (route) => {
      const request = route.request();
      if (matchesRestore(request.method(), request.postData())) {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(envelope) });
      } else await route.continue();
    });
    const since = Date.now();
    const restoreResponse = page.waitForResponse((response) => matchesRestore(response.request().method(), response.request().postData()));
    await control.click();
    const response = await test.step('receive the intercepted restore response', () => restoreResponse, { timeout: 15_000 });
    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual(envelope);
    await expect(page.locator('.toast-error').filter({ hasText: message })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);
    await tracker.waitForQuiet(since);
    expect(saves).toBe(1);
    expect(navigations).toBe(0);
    await expect(control).toBeVisible();
    const read = await page.request.get(`/index.php?r=account/view/${id}`);
    expect(read.status()).toBe(200);
    const markup = await read.text();
    expect(markup).toContain(currentName);
    expect(markup).not.toContain(historicalName);
  } catch (error) {
    failure = error;
    throw error;
  } finally {
    try {
      await page.unroute('**/index.php');
    } catch (error) {
      if (failure === undefined) throw error;
    }
    tracker.dispose();
  }
});

test('account password edit displays a synthetic error without navigating', async ({ sessionPage: { page, context, session } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const message = 'Synthetic account password edit error';
  const password = `Changed-${Date.now()}-fixture!`;
  let saves = 0;
  let navigations = 0;
  try {
    const id = await createAccount(page, tracker, session);
    await page.goto(`/index.php?r=account/view/${id}`, { waitUntil: 'domcontentloaded' });
    const control = page.locator('.item-actions [data-onclick="account/editPass"]').first();
    await expect(control).toHaveAttribute('data-action-route', 'account/editPass');
    const formResponse = page.waitForResponse((response) => response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === `account/editPass/${id}`);
    await control.click();
    expect((await formResponse).status()).toBe(200);
    const form = page.locator('#frmEditPass');
    await expect(form).toHaveAttribute('data-action-route', 'account/saveEditPass');
    await expect(form).toHaveAttribute('data-item-id', String(id));
    await form.locator('input[name="password"]').fill(password);
    await form.locator('input[name="password_repeat"]').fill(password);
    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
    const saveRoute = `account/saveEditPass/${id}`;
    const envelope = { status: 'ERROR', description: message, messages: [], data: null };
    page.on('request', (request) => {
      const route = new URL(request.url()).searchParams.get('r');
      if (request.method() === 'POST' && route === saveRoute) saves++;
      if (request.method() === 'GET' && route === `account/view/${id}`) navigations++;
    });
    await page.route('**/index.php?**', async (route) => {
      if (route.request().method() === 'POST' && new URL(route.request().url()).searchParams.get('r') === saveRoute) {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(envelope) });
      } else await route.continue();
    });
    const since = Date.now();
    const responsePromise = page.waitForResponse((response) => response.request().method() === 'POST' && new URL(response.url()).searchParams.get('r') === saveRoute);
    await page.locator('button[type="submit"][form="frmEditPass"]').click();
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual(envelope);
    const fields = new URLSearchParams(response.request().postData() ?? '');
    for (const field of ['password', 'password_repeat']) {
      expect(Boolean(fields.get(field))).toBe(true);
      expect(fields.get(field) === password).toBe(false);
    }
    await expect(page.locator('.toast-error').filter({ hasText: message })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);
    await tracker.waitForQuiet(since);
    expect(saves).toBe(1);
    expect(navigations).toBe(0);
    await expect(form).toHaveAttribute('data-item-id', String(id));
  } finally {
    await page.unroute('**/index.php?**');
    tracker.dispose();
  }
});

test('account edit displays a synthetic warning without navigating or persisting', async ({ sessionPage: { page, context, session } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const message = 'Synthetic edit warning';
  let saves = 0;
  let navigations = 0;
  try {
    const id = await createAccount(page, tracker, session);
    const envelope = { status: 'WARNING', description: message, messages: [], data: { itemId: id, nextAction: 'account/view' } };
    await page.goto(`/index.php?r=account/edit/${id}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#frmAccount')).toHaveAttribute('data-item-id', String(id));
    const oldName = await page.locator('#name').inputValue();
    const newName = `Synthetic warning edit ${Date.now()}`;
    await page.locator('#name').fill(newName);
    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
    page.on('request', (request) => {
      if (request.method() === 'POST' && matchesSave(request.url(), id)) saves++;
      if (request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `account/view/${id}`) navigations++;
    });
    await page.route('**/index.php?**', async (route) => {
      if (route.request().method() === 'POST' && matchesSave(route.request().url(), id)) {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(envelope) });
      } else await route.continue();
    });
    const since = Date.now();
    const responsePromise = page.waitForResponse((response) => response.request().method() === 'POST' && matchesSave(response.url(), id));
    await page.locator('.item-actions button[type="submit"][form="frmAccount"]').first().click();
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual(envelope);
    expect(new URLSearchParams(response.request().postData() ?? '').get('name')).toBe(newName);
    await expect(page.locator('.toast-warning').filter({ hasText: message })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);
    await tracker.waitForQuiet(since);
    expect(saves).toBe(1);
    expect(navigations).toBe(0);
    await expect(page.locator('#frmAccount')).toHaveAttribute('data-action-route', 'account/saveEdit');
    await expect(page.locator('#name')).toHaveValue(newName);
    const read = await page.request.get(`/index.php?r=account/view/${id}`);
    expect(read.status()).toBe(200);
    const markup = await read.text();
    expect(markup).toContain(oldName);
    expect(markup).not.toContain(newName);
  } finally {
    await page.unroute('**/index.php?**');
    tracker.dispose();
  }
});

test('account edit displays a synthetic error without navigating or persisting', async ({ sessionPage: { page, context, session } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  try {
    const id = await createAccount(page, tracker, session);
    await page.goto(`/index.php?r=account/edit/${id}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#frmAccount')).toHaveAttribute('data-item-id', String(id));
    const oldName = await page.locator('#name').inputValue();
    const newName = `Synthetic edit ${Date.now()}`;
    await page.locator('#name').fill(newName);
    await page.route('**/index.php?**', async (route) => {
      const request = route.request();
      if (request.method() === 'POST' && matchesSave(request.url(), id)) {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ status: 'ERROR', description: 'Synthetic edit failure', messages: [], data: null }) });
      } else await route.continue();
    });
    const responsePromise = page.waitForResponse((response) => response.request().method() === 'POST' && matchesSave(response.url(), id));
    await page.locator('.item-actions button[type="submit"][form="frmAccount"]').first().click();
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual({ status: 'ERROR', description: 'Synthetic edit failure', messages: [], data: null });
    expect(new URLSearchParams(response.request().postData() ?? '').get('name')).toBe(newName);
    await expect(page.locator('.toast-error').filter({ hasText: 'Synthetic edit failure' })).toBeVisible();
    await expect(page.locator('#frmAccount')).toHaveAttribute('data-action-route', 'account/saveEdit');
    await expect(page.locator('#name')).toHaveValue(newName);
    const read = await page.request.get(`/index.php?r=account/view/${id}`);
    expect(read.status()).toBe(200);
    expect(await read.text()).toContain(oldName);
  } finally {
    await page.unroute('**/index.php?**');
    tracker.dispose();
  }
});

test('account edit dispatches and persists the changed name', async ({ sessionPage: { page, context, session } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  try {
    const id = await createAccount(page, tracker, session);
    const since = Date.now();
    await page.goto(`/index.php?r=account/edit/${id}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#frmAccount')).toHaveAttribute('data-item-id', String(id));
    const name = `Synthetic edit ${Date.now()}`;
    await page.locator('#name').fill(name);
    const responsePromise = page.waitForResponse((response) => response.request().method() === 'POST' && matchesSave(response.url(), id));
    await page.locator('.item-actions button[type="submit"][form="frmAccount"]').first().click();
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(new URLSearchParams(response.request().postData() ?? '').get('name')).toBe(name);
    const envelope = await response.json();
    expect(envelope.status).toBe('OK');
    expect(envelope.data.itemId).toBe(id);
    expect(envelope.data.nextAction).toBe('account/view');
    await tracker.waitForActions(['account/view'], since, Date.now() + 15_000);
    const read = await page.request.get(`/index.php?r=account/view/${id}`);
    expect(read.status()).toBe(200);
    expect(await read.text()).toContain(name);
  } finally {
    tracker.dispose();
  }
});

test('single account history delete displays a synthetic ERROR and reloads the active tab', async ({ sessionPage: { page, context, session } }) => {
  test.setTimeout(90_000);
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const message = 'Synthetic account history delete error';
  const envelope = { status: 'ERROR', description: message, messages: [], data: null };
  let failure: unknown;
  try {
    const accountId = await createAccount(page, tracker, session);
    await page.goto(`/index.php?r=account/edit/${accountId}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#frmAccount')).toHaveAttribute('data-item-id', String(accountId));
    const historyName = await page.locator('#name').inputValue();
    await page.locator('#name').fill(`Edited ${historyName}`);
    const sinceSave = Date.now();
    const savePromise = page.waitForResponse((response) => response.request().method() === 'POST' && matchesSave(response.url(), accountId));
    await page.locator('.item-actions button[type="submit"][form="frmAccount"]').first().click();
    expect((await (await savePromise).json()).status).toBe('OK');
    await test.step('wait for save navigation', async () => {
      await tracker.waitForActions(['account/view'], sinceSave, Date.now() + 15_000);
      await tracker.waitForQuiet(sinceSave);
    }, { timeout: 20_000 });

    await test.step('reopen the authenticated shell', () => openSeededShell(page, tracker, session), { timeout: 20_000 });
    await test.step('open the account history manager', () => navigateTierBIndex(page, tracker, TIER_B_INDEX_CASES.itemManager), { timeout: 20_000 });
    const grid = page.locator('#data-table-tblAccountsHistory');
    const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
    const panelId = await panel.getAttribute('id');
    const tabIndex = await panel.getAttribute('data-tab-index');
    await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();
    const form = page.locator('#frmSearchAccountHistory');
    await form.locator('label[for^="search-"]').first().click();
    await form.locator('input[name="search"]').fill(historyName);
    const searchPromise = page.waitForResponse((response) => response.request().method() === 'GET' && parseActionRoute(response.url()) === 'accountHistoryManager/search');
    await form.locator('input[name="search"]').press('Enter');
    expect((await searchPromise).status()).toBe(200);
    const row = grid.locator('tr').filter({ hasText: historyName });
    await expect(row).toHaveCount(1);
    const historyId = await row.getAttribute('data-item-id');
    expect(historyId).toMatch(/^\d+$/);
    const control = row.locator('[data-onclick="appMgmt/delete"]');
    await expect(control).toHaveAttribute('data-action-route', 'accountHistoryManager/delete');
    await expect(control).toHaveAttribute('data-item-id', historyId!);
    const deleteRoute = `accountHistoryManager/delete/${historyId}`;
    await page.route('**/index.php?**', async (route) => {
      const request = route.request();
      if (request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === deleteRoute) {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(envelope) });
      } else await route.continue();
    });
    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
    await control.click();
    await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();
    const responsePromise = page.waitForResponse((response) => response.request().method() === 'GET' && new URL(response.url()).searchParams.get('r') === deleteRoute);
    const refreshPromise = page.waitForResponse((response) => response.request().method() === 'GET' && parseActionRoute(response.url()) === 'itemManager/index');
    await page.locator('#orrsDiag #positive').click();
    const response = await responsePromise;
    expect(response.status()).toBe(200);
    expect(new URL(response.url()).searchParams.get('isAjax')).toBe('1');
    expect(await response.json()).toEqual(envelope);
    // This records current browser behaviour, not a real server rollback guarantee.
    const refresh = await refreshPromise;
    expect(refresh.status()).toBe(200);
    expect(new URL(refresh.url()).searchParams.get('tabIndex')).toBe(tabIndex);
    await expect(page.locator('.toast-error').filter({ hasText: message })).toBeVisible();
    await expect(page.locator('.toast-success')).toHaveCount(0);
    const sk = await page.evaluate(() => (0, eval)('sysPassApp').sk.get());
    const read = await page.request.get(`/index.php?r=accountHistoryManager/search&search=${encodeURIComponent(historyName)}&sk=${encodeURIComponent(sk)}&isAjax=1`);
    expect(read.status()).toBe(200);
    const result = await read.json();
    expect(result.status).toBe('OK');
    expect(result.data.html).toContain(historyName);
    expect(result.data.html).toContain(`data-item-id="${historyId}"`);
  } catch (error) {
    failure = error;
    throw error;
  } finally {
    try {
      await page.unroute('**/index.php?**');
    } catch (error) {
      if (failure === undefined) throw error;
    }
    tracker.dispose();
  }
});
