import { test, expect } from '../src/session';
import { ActionTracker, parseActionRoute } from '../src/readiness';
import { navigateTierBIndex, openSeededShell, TIER_B_INDEX_CASES } from '../src/tier-b';

test('configuration and preference changes survive a fresh view', async ({ sessionPage: { page, context, session } }) => {
  test.setTimeout(120_000);
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  try {
    await openSeededShell(page, tracker, session);
    await navigateTierBIndex(page, tracker, TIER_B_INDEX_CASES.configManager);
    const theme = page.locator('#sel-sitetheme');
    const themeValue = await theme.inputValue();
    const debug = page.getByRole('checkbox', { name: 'Enable debug' });
    const originalDebug = await debug.isChecked();
    await debug.focus();
    await debug.press('Space');
    const configResponse = page.waitForResponse(r => r.request().method() === 'POST' && parseActionRoute(r.url()) === 'configGeneral/save');
    await page.locator('button[form="frmConfig"]').click();
    const configSaved = await configResponse;
    const configFields = new URLSearchParams(configSaved.request().postData() ?? '');
    expect(configFields.get('site_theme')).toBe(themeValue);
    expect(configFields.get('site_lang')).toBeTruthy();
    expect((await configSaved.json()).status).toBe('OK');
    await navigateTierBIndex(page, tracker, TIER_B_INDEX_CASES.configManager);
    expect(await page.getByRole('checkbox', { name: 'Enable debug' }).isChecked()).toBe(!originalDebug);
    expect(await page.locator('#sel-sitetheme').inputValue()).toBe(themeValue);

    await page.locator('#users-menu-lower-right').click();
    await navigateTierBIndex(page, tracker, TIER_B_INDEX_CASES.userSettingsManager);
    const preference = page.locator('#account_link');
    const original = await preference.isChecked();
    await preference.focus();
    await preference.press('Space');
    const pageErrors: string[] = [];
    page.on('pageerror', error => pageErrors.push(error.message));
    const reload = page.waitForEvent('framenavigated', frame => frame === page.mainFrame());
    const prefsResponse = page.waitForResponse(r => r.request().method() === 'POST' && parseActionRoute(r.url()) === 'userSettingsGeneral/save');
    await page.locator('button[form="frmPreferences"]').click();
    const saved = await prefsResponse;
    expect(new URLSearchParams(saved.request().postData() ?? '').has('account_link')).toBe(!original);
    expect((await saved.json()).status).toBe('OK');
    const freshView = await page.request.get('/index.php?r=userSettingsManager/index');
    expect(freshView.ok()).toBe(true);
    const markup = await freshView.text();
    const savedChecked = await page.evaluate(html => {
      const doc = new DOMParser().parseFromString(html, 'text/html');
      return (doc.querySelector('#account_link') as HTMLInputElement | null)?.checked;
    }, markup);
    expect(savedChecked).toBe(!original);
    await reload;
    await expect(page.locator('#content')).toBeVisible();
    expect(pageErrors).toEqual([]);
  } finally {
    tracker.dispose();
  }
});
