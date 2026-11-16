import type { Request as PlaywrightRequest } from '@playwright/test';

import { ActionTracker, parseActionRoute } from '../src/readiness';
import { expect, test } from '../src/session';
import { runTierBIndex, TIER_B_INDEX_CASES } from '../src/tier-b';

// WARNING is a simulated client contract, not an outcome emitted by DeleteController.
for (const outcome of ['OK', 'ERROR', 'WARNING', 'transport'] as const) {
  test(`notification delete reconciles a received ${outcome} outcome`, async ({ sessionPage: { page, context, session } }) => {
    const tracker = new ActionTracker(page, context, { sessionEstablished: false });
    const name = `E2E NOTIFICATION ${Date.now()}-${Math.random().toString(36).slice(2)}`;
    const message = `Synthetic notification ${outcome}`;
    const requests: string[] = [];
    const onRequest = (request: PlaywrightRequest) => {
      const action = parseActionRoute(request.url());
      if (request.method() === 'GET' && (action === 'notification/index' || action === 'items/notifications')) requests.push(action);
    };

    try {
      await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.notification);
      const security = await page.evaluate(() => {
        const app = (0, eval)('sysPassApp');
        return { sk: app.sk.get(), csrf: app.config.CSRF };
      });
      const created = await page.request.post('/index.php?r=notification/saveCreate', {
        headers: { 'X-CSRF': security.csrf },
        form: { notification_type: 'notice', notification_component: 'test', notification_description: name, notification_user: '1', sk: security.sk, isAjax: '1' },
      });
      expect(created.status()).toBe(200);
      expect((await created.json()).status).toBe('OK');
      await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.notification);
      const grid = page.locator('#data-table-tblNotifications');
      const row = grid.locator('tr').filter({ hasText: name });
      await expect(row).toHaveCount(1);
      const id = await row.getAttribute('data-item-id');
      expect(id).toMatch(/^\d+$/);
      const control = row.locator('[data-onclick="notification/delete"]');
      await expect(control).toHaveAttribute('data-action-route', 'notification/delete');
      await expect(control).toHaveAttribute('data-action-next', 'notification/index');
      await expect(control).toHaveAttribute('data-item-id', id!);

      await page.route('**/index.php?**', async (route) => {
        const request = route.request();
        if (request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `notification/delete/${id}`) {
          if (outcome === 'transport') await route.abort('failed');
          else await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ status: outcome, description: message, messages: [], data: null }) });
        } else await route.continue();
      });
      await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
      await control.click();
      await expect(page.locator('#orrsDiag #alert-text')).toBeVisible();
      page.on('request', onRequest);
      const dispatch = page.waitForRequest((request) => request.method() === 'GET' && new URL(request.url()).searchParams.get('r') === `notification/delete/${id}`);
      const badge = outcome === 'transport' ? null : page.waitForResponse((response) => response.request().method() === 'GET' && parseActionRoute(response.url()) === 'items/notifications');
      await page.locator('#orrsDiag #positive').click();
      const request = await dispatch;
      expect(new URL(request.url()).searchParams.get('isAjax')).toBe('1');
      if (badge) {
        expect((await badge).status()).toBe(200);
        expect(requests).toEqual(['notification/index', 'items/notifications']);
        const toast = outcome === 'OK' ? '.toast-success' : outcome === 'ERROR' ? '.toast-error' : '.toast-warning';
        await expect(page.locator(toast).filter({ hasText: message })).toBeVisible();
      } else {
        await expect(page.locator('.toast-error')).toBeVisible();
        await page.waitForTimeout(1000);
        expect(requests).toEqual([]);
      }
      if (outcome !== 'OK') await expect(page.locator('.toast-success')).toHaveCount(0);
      const read = await page.request.get(`/index.php?r=notification/search&sk=${encodeURIComponent(security.sk)}&isAjax=1`);
      expect(read.status()).toBe(200);
      expect((await read.json()).data.html).toContain(name);
    } finally {
      page.off('request', onRequest);
      await page.unroute('**/index.php?**');
      tracker.dispose();
    }
  });
}
