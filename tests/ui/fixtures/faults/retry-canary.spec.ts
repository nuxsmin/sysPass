import { expect, test } from '../../src/session';

const CANARY = 'CANARY_RETRY_SECRET_3a71d5';

test('a diagnostic retry keeps the first-attempt failure evidence', async ({
  isolatedPage: { page },
}, testInfo) => {
  await page.evaluate((secret) => console.error(`token=${secret}`), CANARY);

  if (testInfo.retry === 0) {
    expect(false, 'intentional first-attempt canary failure').toBe(true);
  }

  await expect(page.locator('body')).toBeAttached();
});
