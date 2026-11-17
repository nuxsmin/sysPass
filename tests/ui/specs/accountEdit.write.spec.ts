import type { Page } from '@playwright/test';
import { accountCreateContract } from '../src/contracts';
import { FIXTURE } from '../src/fixture';
import { ActionTracker, parseActionRoute } from '../src/readiness';
import { expect, test } from '../src/session';
import { openSeededShell } from '../src/tier-b';

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
