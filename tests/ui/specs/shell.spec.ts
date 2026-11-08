import type { Page } from '@playwright/test';

import { test, expect } from '../src/session';
import { ADMIN_CREDENTIALS, FIXTURE } from '../src/fixture';
import {
  accountCreateContract,
  accountEditContract,
  accountViewContract,
  loginContract,
  type AccountNavigationMode,
} from '../src/contracts';
import { loginThroughUi } from '../src/login';
import { ActionTracker } from '../src/readiness';
import {
  SEEDED_SHELL_CONTRACT as SHELL_CONTRACT,
  openSeededShellPage as openSeededShell,
} from '../src/seeded-shell';

const READINESS_TIMEOUT_MS = 15_000;

const ACCOUNT_PAGE_OPTIONS = {
  accountId: FIXTURE.ids.accounts.child,
  accountName: FIXTURE.names.childAccount,
  parentAccount: FIXTURE.names.parentAccount,
  childFile: FIXTURE.names.childFile,
};

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

async function openInShellAccountView(page: Page, tracker: ActionTracker): Promise<void> {
  const link = page
    .locator('#search-rows .account-label', { hasText: FIXTURE.names.childAccount })
    .locator('[data-onclick="account/view"]')
    .first();

  await expect(link).toHaveAttribute('data-action-route', 'account/view');
  await expect(link).toHaveAttribute('data-item-id', String(FIXTURE.ids.accounts.child));

  const contract = accountViewContract({ mode: 'in-shell', ...ACCOUNT_PAGE_OPTIONS });

  tracker.setContract(contract);
  const since = Date.now();

  await link.click();
  await tracker.waitForReady(contract, since);
}

test.describe.configure({ mode: 'serial' });

test('authenticates once and loads the complete seeded shell', async ({ sessionPage: { page, context, session } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  tracker.setContract(SHELL_CONTRACT);

  try {
    const since = await loginThroughUi(page, ADMIN_CREDENTIALS);
    session.authenticated = true;
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
  tracker.setContract(SHELL_CONTRACT);
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
  tracker.setContract(SHELL_CONTRACT);
  const since = Date.now();

  try {
    await page.goto('/index.php?r=index', { waitUntil: 'domcontentloaded' });
    await tracker.waitForReady(SHELL_CONTRACT, since);

    expect(tracker.observedRoutes(since)).not.toContain('login');
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

test('account view loads in-shell with the seeded file and linked-account content', async ({
  sessionPage: { page, context },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: true });

  try {
    await openSeededShell(page, tracker);
    await openInShellAccountView(page, tracker);
  } finally {
    tracker.dispose();
  }
});

test('account create loads in-shell with the seeded linked-account option', async ({
  sessionPage: { page, context },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: true });

  try {
    await openSeededShell(page, tracker);

    const link = page.locator('a.btn-menu[data-route="account/create"]').first();
    await expect(link).toHaveAttribute('data-view', 'account');

    const contract = accountCreateContract({
      mode: 'in-shell',
      parentAccount: FIXTURE.names.parentAccount,
    });

    tracker.setContract(contract);
    const since = Date.now();

    await link.click();
    await tracker.waitForReady(contract, since);
  } finally {
    tracker.dispose();
  }
});

test('account edit loads in-shell with the seeded file and linked-account content', async ({
  sessionPage: { page, context },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: true });

  try {
    await openSeededShell(page, tracker);
    await openInShellAccountView(page, tracker);

    const link = page.locator('.item-actions [data-onclick="account/edit"]').first();
    await expect(link).toHaveAttribute('data-action-route', 'account/edit');
    await expect(link).toHaveAttribute('data-item-id', String(FIXTURE.ids.accounts.child));

    const contract = accountEditContract({ mode: 'in-shell', ...ACCOUNT_PAGE_OPTIONS });

    tracker.setContract(contract);
    const since = Date.now();

    await link.click();
    await tracker.waitForReady(contract, since);
  } finally {
    tracker.dispose();
  }
});

test('account view loads by direct full-page URL with file and linked-account actions', async ({
  sessionPage: { page, context },
}) => {
  const contract = accountViewContract({ mode: 'direct', ...ACCOUNT_PAGE_OPTIONS });
  const tracker = new ActionTracker(page, context, { sessionEstablished: true });
  tracker.setContract(contract);
  const since = Date.now();

  try {
    await page.goto(`/index.php?r=account/view/${FIXTURE.ids.accounts.child}`, {
      waitUntil: 'domcontentloaded',
    });
    await tracker.waitForReady(contract, since);
  } finally {
    tracker.dispose();
  }
});

test('account create loads by direct full-page URL with the seeded linked-account option', async ({
  sessionPage: { page, context },
}) => {
  const contract = accountCreateContract({
    mode: 'direct',
    parentAccount: FIXTURE.names.parentAccount,
  });
  const tracker = new ActionTracker(page, context, { sessionEstablished: true });
  tracker.setContract(contract);
  const since = Date.now();

  try {
    await page.goto('/index.php?r=account/create', { waitUntil: 'domcontentloaded' });
    await tracker.waitForReady(contract, since);
  } finally {
    tracker.dispose();
  }
});

test('account edit loads by direct full-page URL with file and linked-account actions', async ({
  sessionPage: { page, context },
}) => {
  const contract = accountEditContract({ mode: 'direct', ...ACCOUNT_PAGE_OPTIONS });
  const tracker = new ActionTracker(page, context, { sessionEstablished: true });
  tracker.setContract(contract);
  const since = Date.now();

  try {
    await page.goto(`/index.php?r=account/edit/${FIXTURE.ids.accounts.child}`, {
      waitUntil: 'domcontentloaded',
    });
    await tracker.waitForReady(contract, since);
  } finally {
    tracker.dispose();
  }
});

test('account contracts require file-list and linked-account actions in both navigation modes', () => {
  for (const mode of ['in-shell', 'direct'] as AccountNavigationMode[]) {
    expect(accountViewContract({ mode, ...ACCOUNT_PAGE_OPTIONS }).actions.map((action) => action.route)).toEqual([
      'account/view',
      'accountFile/list',
      'items/accountsUser',
    ]);
    expect(accountEditContract({ mode, ...ACCOUNT_PAGE_OPTIONS }).actions.map((action) => action.route)).toEqual([
      'account/edit',
      'accountFile/list',
      'items/accountsUser',
    ]);
    expect(
      accountCreateContract({ mode, parentAccount: FIXTURE.names.parentAccount }).actions.map(
        (action) => action.route,
      ),
    ).toEqual(['account/create', 'items/accountsUser']);
  }
});
