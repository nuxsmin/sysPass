import type { Page } from '@playwright/test';
import { accountCreateContract } from '../src/contracts';
import { FIXTURE } from '../src/fixture';
import { ActionTracker, parseActionRoute } from '../src/readiness';
import { expect, test } from '../src/session';
import { openSeededShell, runTierBIndex, TIER_B_INDEX_CASES } from '../src/tier-b';

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
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const message = 'Synthetic account history delete error';
  const envelope = { status: 'ERROR', description: message, messages: [], data: null };
  try {
    const accountId = await createAccount(page, tracker, session);
    await page.goto(`/index.php?r=account/edit/${accountId}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#frmAccount')).toHaveAttribute('data-item-id', String(accountId));
    const historyName = await page.locator('#name').inputValue();
    await page.locator('#name').fill(`Edited ${historyName}`);
    const savePromise = page.waitForResponse((response) => response.request().method() === 'POST' && matchesSave(response.url(), accountId));
    await page.locator('.item-actions button[type="submit"][form="frmAccount"]').first().click();
    expect((await (await savePromise).json()).status).toBe('OK');

    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.itemManager);
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
  } finally {
    await page.unroute('**/index.php?**');
    tracker.dispose();
  }
});
