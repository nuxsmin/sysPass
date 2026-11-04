import { test, expect } from '../src/session';
import { ADMIN_CREDENTIALS, FIXTURE } from '../src/fixture';
import { loginContract, shellContract } from '../src/contracts';
import { loginThroughUi } from '../src/login';
import { ActionTracker } from '../src/readiness';

const READINESS_TIMEOUT_MS = 15_000;

const SHELL_CONTRACT = shellContract({
  parentAccount: FIXTURE.names.parentAccount,
  childAccount: FIXTURE.names.childAccount,
  client: FIXTURE.names.client,
  category: FIXTURE.names.category,
});

interface BootPayload {
  status?: string;
  data?: {
    loggedin?: boolean;
    locale?: string;
    check_updates?: boolean;
    check_notices?: boolean;
    debug?: boolean;
    csrf?: unknown;
    session_timeout?: number;
  };
}

interface NotificationsPayload {
  status?: string;
  data?: {
    count?: number;
    message?: string;
    notifications?: unknown[];
  };
}

test.describe.configure({ mode: 'serial' });

test('authenticates once and loads the complete seeded shell', async ({ sessionPage: { page, context } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });

  try {
    const since = await loginThroughUi(page, ADMIN_CREDENTIALS);
    tracker.setSessionEstablished(true);
    await tracker.waitForReady(SHELL_CONTRACT, since);

    const boot = tracker.getJson('bootstrap/getEnvironment', since) as BootPayload;
    expect(boot.status).toBe('OK');
    expect(boot.data?.loggedin).toBe(true);
    expect(boot.data?.locale).toBe('en_US');
    expect(boot.data?.check_updates).toBe(false);
    expect(boot.data?.check_notices).toBe(false);
    expect(boot.data?.debug).toBe(false);
    expect(typeof boot.data?.csrf).toBe('string');
    expect((boot.data?.csrf as string).length).toBeGreaterThan(0);
    expect(boot.data?.session_timeout).toBeGreaterThan(0);

    const notifications = tracker.getJson('items/notifications', since) as NotificationsPayload;
    expect(notifications.status).toBe('OK');
    expect(notifications.data?.count).toBe(0);
    expect(typeof notifications.data?.message).toBe('string');

    const accountIndex = tracker.getAction('account/index', since);
    expect(accountIndex?.status).toBe(200);
    expect(accountIndex?.body).toContain(FIXTURE.names.parentAccount);
    expect(accountIndex?.body).toContain(FIXTURE.names.childAccount);
    expect(accountIndex?.body).toContain(FIXTURE.names.client);
    expect(accountIndex?.body).toContain(FIXTURE.names.category);

    await expect(page.locator('body')).toHaveClass(/main/);
    await expect(page.locator('#wrap')).toBeVisible();
    await expect(page.locator('#content')).toBeVisible();
    await expect(
      page.locator('.account-label', { hasText: FIXTURE.names.parentAccount }),
    ).toBeVisible();
    await expect(
      page.locator('.account-label', { hasText: FIXTURE.names.childAccount }),
    ).toBeVisible();
  } finally {
    tracker.dispose();
  }
});

test('reuses the authenticated session across 120-second SID regeneration', async ({
  sessionPage: { page, context },
}) => {
  test.setTimeout(180_000);

  const tracker = new ActionTracker(page, context, { sessionEstablished: true });
  const baselineSince = Date.now();

  try {
    await page.goto('/index.php?r=index', { waitUntil: 'domcontentloaded' });
    await tracker.waitForReady(SHELL_CONTRACT, baselineSince);

    const cookiesBefore = await context.cookies();
    const sessionCookieBefore =
      cookiesBefore.find((cookie) => cookie.name === 'PHPSESSID') ?? cookiesBefore[0];

    expect(sessionCookieBefore, 'the authenticated session cookie should exist').toBeDefined();

    await page.waitForTimeout(122_000);

    const rotationSince = Date.now();

    await page.goto('/index.php?r=index', { waitUntil: 'domcontentloaded' });
    await tracker.waitForReady(SHELL_CONTRACT, rotationSince);

    const cookiesAfter = await context.cookies();
    const sessionCookieAfter = cookiesAfter.find(
      (cookie) => cookie.name === sessionCookieBefore?.name,
    );

    expect(sessionCookieAfter, 'the rotated session cookie should exist').toBeDefined();
    expect(sessionCookieAfter?.value).not.toBe(sessionCookieBefore?.value);
  } finally {
    tracker.dispose();
  }
});

test('a fresh sequential context uses the rotated cookies without logging in again', async ({
  sessionPage: { page, context },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: true });
  const since = Date.now();

  try {
    await page.goto('/index.php?r=index', { waitUntil: 'domcontentloaded' });
    await tracker.waitForReady(SHELL_CONTRACT, since);

    expect(tracker.observedRoutes(since)).not.toContain('login/login');
    expect(tracker.observedRoutes(since)).not.toContain('login/index');

    await expect(page.locator('body')).toHaveClass(/main/);
    await expect(page.locator('#wrap')).toBeVisible();
    await expect(
      page.locator('.account-label', { hasText: FIXTURE.names.childAccount }),
    ).toBeVisible();
  } finally {
    tracker.dispose();
  }
});

test('the login contract is available for anonymous navigation', () => {
  expect(loginContract().actions.map((action) => action.route)).toContain('login/index');
  expect(READINESS_TIMEOUT_MS).toBe(15_000);
});
