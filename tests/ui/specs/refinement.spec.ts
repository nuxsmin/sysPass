import path from 'node:path';

import { test, expect } from '../src/session';
import { FIXTURE } from '../src/fixture';
import { accountCreateContract, accountEditContract, accountViewContract } from '../src/contracts';
import { ActionTracker } from '../src/readiness';
import { openSeededShell, navigateTierBIndex, TIER_B_INDEX_CASES } from '../src/tier-b';
import { openSeededShellPage } from '../src/seeded-shell';

test('workspace and management tables remain usable at supported widths', async ({ sessionPage: { page, context, session } }) => {
  test.setTimeout(90_000);
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const captureDir = process.env.SP_UI_REFINEMENT_CAPTURE_DIR;

  async function capture(name: string, label: string): Promise<void> {
    if (captureDir) {
      await page.screenshot({
        path: path.join(captureDir, `${process.env.SP_UI_REFINEMENT_CAPTURE_PREFIX ?? ''}${name}-${label}.png`),
        fullPage: true,
        animations: 'disabled',
        mask: [page.locator('input[type="password"]')],
      });

    }
  }

  try {
    await openSeededShell(page, tracker, session);
    for (const [width, label] of [[1440, 'desktop'], [768, 'tablet'], [375, 'mobile']] as const) {
      await page.setViewportSize({ width, height: 900 });
      await capture('phase1-workspace', label);
      await expect(page.locator('#search')).toBeVisible();
      await expect(page.locator('#client + .selectize-control')).toBeVisible();
      await expect(page.locator('#category + .selectize-control')).toBeVisible();
      await expect(page.locator('footer #secure-info')).toBeVisible();
      await expect(page.locator('footer #app-info')).toBeVisible();
      expect(await page.locator('footer').evaluate((footer) => {
        const bounds = footer.getBoundingClientRect();
        return bounds.bottom <= window.innerHeight + 1 && bounds.top > window.innerHeight / 2;
      })).toBe(true);
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    }

    await page.setViewportSize({ width: 1440, height: 900 });
    const sort = page.locator('#data-search-header button.sort-up').first();
    await expect(sort).toHaveAttribute('aria-label', /.+/);
    await sort.focus();
    await sort.press('Enter');
    await expect(page.locator('#data-search-header button.sort-up').first()).toHaveAttribute('aria-pressed', 'true');
    await page.locator('#search').fill(FIXTURE.names.childAccount);
    await page.locator('#search').press('Enter');
    await expect(page.locator('#search-rows .account-label', { hasText: FIXTURE.names.childAccount })).toBeVisible();
    await expect(page.locator('#search-rows .account-label', { hasText: FIXTURE.names.parentAccount })).toHaveCount(0);

    await navigateTierBIndex(page, tracker, TIER_B_INDEX_CASES.accessManager);
    const table = page.locator('.mdl-tabs__panel.is-active .data-table').first();
    await expect(table).toBeVisible();
    await expect(table.locator('thead th').first()).not.toHaveCSS('background-color', 'rgb(57, 73, 171)');
    for (const [width, label] of [[1440, 'desktop'], [768, 'tablet'], [375, 'mobile']] as const) {
      await page.setViewportSize({ width, height: 900 });
      await capture('phase1-management', label);
      await expect(table).toBeVisible();
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
      if (width === 375) {
        const scroll = page.locator('.mdl-tabs__panel.is-active .sp-table-scroll').first();
        await expect(scroll).toHaveAttribute('tabindex', '0');
        expect(await scroll.evaluate((element) => element.scrollWidth > element.clientWidth)).toBe(true);
        await expect(table.locator('tbody td.cell-data').first()).toHaveCSS('text-overflow', 'clip');
      }
    }
    await page.setViewportSize({ width: 1440, height: 900 });
    const selectAll = table.locator('thead input[type="checkbox"]');
    await selectAll.focus();
    await selectAll.press('Space');
    await expect(table.locator('tbody input[type="checkbox"]').first()).toBeChecked();
    await selectAll.press('Space');
    const viewUser = table.getByRole('button', { name: 'View User Details' }).first();
    await viewUser.focus();
    await viewUser.press('Enter');
    await expect(page.locator('#box-popup')).toBeVisible();
  } finally {
    tracker.dispose();
  }
});

test('account labels and actions remain usable in view, edit and create', async ({ sessionPage: { page, context, session } }) => {
  test.setTimeout(90_000);
  const tracker = new ActionTracker(page, context, { sessionEstablished: session.authenticated });
  const options = {
    mode: 'in-shell' as const,
    accountId: FIXTURE.ids.accounts.child,
    accountName: FIXTURE.names.childAccount,
    parentAccount: FIXTURE.names.parentAccount,
    childFile: FIXTURE.names.childFile,
  };
  try {
    await openSeededShell(page, tracker, session);
    const view = accountViewContract(options);
    tracker.setContract(view);
    let since = Date.now();
    await page.locator('#search-rows .account-label', { hasText: FIXTURE.names.childAccount })
      .locator('[data-onclick="account/view"]').first().click();
    await tracker.waitForReady(view, since);

    for (const [width, label] of [[1440, 'desktop'], [768, 'tablet'], [375, 'mobile']] as const) {
      await page.setViewportSize({ width, height: 900 });
      await expect(page.locator('#account-panel tr').filter({ has: page.locator('#name') }).locator('.descField')).toBeVisible();
      await expect(page.locator('label[for="client_id-selectized"]')).toBeVisible();
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
      if (width === 375) {
        const linked = page.locator('#parent_account_id + .selectize-control .selectize-input');
        expect(await linked.evaluate((element) => element.scrollWidth <= element.clientWidth)).toBe(true);
        const selected = linked.locator('[data-value]').first();
        await expect(selected).toBeVisible();
        expect(await selected.evaluate((element) => element.scrollWidth <= element.clientWidth)).toBe(true);
        const bounds = await selected.evaluate((element) => {
          const item = element.getBoundingClientRect();
          const parent = element.parentElement!.getBoundingClientRect();
          const row = element.closest('tr')!.getBoundingClientRect();
          return { item: item.right, parent: parent.right, row: row.right, viewport: document.documentElement.clientWidth };
        });
        expect(bounds.item, JSON.stringify(bounds)).toBeLessThanOrEqual(Math.min(bounds.parent + 1, bounds.viewport));
      }
      if (process.env.SP_UI_REFINEMENT_CAPTURE_DIR) {
        await page.screenshot({
          path: path.join(process.env.SP_UI_REFINEMENT_CAPTURE_DIR, `phase2-account-${label}.png`),
          fullPage: true,
          animations: 'disabled',
          mask: [page.locator('input[type="password"]')],
        });

      }
    }

    await page.setViewportSize({ width: 1440, height: 900 });
    const edit = accountEditContract(options);
    tracker.setContract(edit);
    since = Date.now();
    await page.locator('.item-actions [data-onclick="account/edit"]').first().click();
    await tracker.waitForReady(edit, since);
    await expect(page.getByLabel('Account name')).toBeVisible();
    await expect(page.locator('.item-actions button[type="submit"]')).toBeVisible();

    await openSeededShellPage(page, tracker);
    const create = accountCreateContract({ mode: 'in-shell', parentAccount: FIXTURE.names.parentAccount });
    tracker.setContract(create);
    since = Date.now();
    await page.locator('.btn-menu[data-route="account/create"]').first().click();
    await tracker.waitForReady(create, since);
    await expect(page.getByLabel('Account name')).toBeVisible();
    await expect(page.locator('label[for="client_id-selectized"]')).toBeVisible();
  } finally {
    tracker.dispose();
  }
});

test('configuration tabs and settings remain usable at supported widths', async ({ sessionPage: { page, context, session } }) => {
  test.setTimeout(90_000);
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  try {
    await openSeededShell(page, tracker, session);
    await navigateTierBIndex(page, tracker, TIER_B_INDEX_CASES.configManager);
    for (const [width, label] of [[1440, 'desktop'], [768, 'tablet'], [375, 'mobile']] as const) {
      await page.setViewportSize({ width, height: 900 });
      if (process.env.SP_UI_REFINEMENT_CAPTURE_DIR) {
        await page.screenshot({
          path: path.join(process.env.SP_UI_REFINEMENT_CAPTURE_DIR, `${process.env.SP_UI_REFINEMENT_CAPTURE_PREFIX ?? ''}configuration-${label}.png`),
          fullPage: true,
          animations: 'disabled',
          mask: [page.locator('input[type="password"]')],
        });
      }
    }

    const config = page.locator('.sp-config-tabs');
    const tabs = config.locator('.mdl-tabs__tab-bar');
    await expect(config.locator('#frmConfig')).toBeVisible();
    await expect(config.locator('label[for="sel-sitelang-selectized"]')).toBeVisible();
    await expect(config.locator('label[for="sel-sitetheme-selectized"]')).toBeVisible();
    await expect(page.getByRole('checkbox', { name: 'Force HTTPS' })).toHaveCount(1);
    const debug = page.getByRole('checkbox', { name: 'Enable debug' });
    const initiallyChecked = await debug.isChecked();
    await debug.focus();
    await debug.press('Space');
    expect(await debug.isChecked()).toBe(!initiallyChecked);
    await debug.press('Space');
    expect(await debug.isChecked()).toBe(initiallyChecked);
    const rows = config.locator('#tblSite > tbody > tr');
    const rowBounds = await rows.evaluateAll((elements) => elements.map((element) => {
      const rect = element.getBoundingClientRect();
      return { top: rect.top, bottom: rect.bottom };
    }));
    for (const [index, row] of rowBounds.entries()) {
      if (index === 0) continue;
      const previous = rowBounds[index - 1];
      if (!previous) throw new Error('Missing previous configuration row');
      expect(row.top).toBeGreaterThanOrEqual(previous.bottom - 1);
    }
    await expect(config.locator('#frmConfig #title').first()).not.toHaveCSS('background-color', 'rgb(57, 73, 171)');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await expect(tabs).toHaveCSS('flex-wrap', 'nowrap');
    const unlabeledSwitches = await config.locator('.mdl-switch__input').evaluateAll((elements) =>
      elements.filter((element) => !element.getAttribute('aria-label') && !element.getAttribute('aria-labelledby')).length);
    expect(unlabeledSwitches).toBe(0);
    const lastTab = tabs.locator('a').last();
    await lastTab.focus();
    await lastTab.press('Enter');
    await expect(lastTab).toHaveClass(/is-active/);
    expect(await lastTab.evaluate((element) => {
      const tab = element.getBoundingClientRect();
      const bar = element.parentElement!.getBoundingClientRect();
      return tab.left >= bar.left - 1 && tab.right <= bar.right + 1;
    })).toBe(true);
    for (const width of [375, 768, 1440]) {
      await page.setViewportSize({ width, height: 900 });
      for (let index = 0; index < await tabs.locator('a').count(); index += 1) {
        const tab = tabs.locator('a').nth(index);
        await tab.click();
        await expect(tab).toHaveClass(/is-active/);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true);
      }
    }
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.evaluate(() => { document.body.style.zoom = '2'; });
    await expect(tabs).toHaveCSS('flex-wrap', 'nowrap');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true);
    await page.evaluate(() => { document.body.style.zoom = ''; });
  } finally {
    tracker.dispose();
  }
});
