import { test as base, expect } from '@playwright/test';
import type { BrowserContext } from '@playwright/test';

import { BROWSER_LOCALE, FIXED_USER_AGENT } from './env';

type BrowserCookies = Awaited<ReturnType<BrowserContext['cookies']>>;

export interface SessionState {
  cookies: BrowserCookies;
}

interface SessionFixtures {
  sessionPage: {
    page: import('@playwright/test').Page;
    context: BrowserContext;
  };
}

interface SessionWorkerFixtures {
  sharedSession: SessionState;
}

/**
 * Worker-scoped authenticated session for the seeded suite.
 *
 * The first seeded test logs in through the UI. Every following seeded test
 * receives the live cookie jar produced by the previous context, so SID
 * rotation and legitimate Set-Cookie updates are carried forward instead of
 * restoring an immutable storage-state snapshot or logging in again.
 *
 * Anonymous/login specs import the plain Playwright `test` and never receive
 * these cookies.
 */
export const test = base.extend<SessionFixtures, SessionWorkerFixtures>({
  sharedSession: [
    async ({}, use) => {
      await use({ cookies: [] });
    },
    { scope: 'worker' },
  ],
  sessionPage: async ({ browser, baseURL, sharedSession }, use) => {
    if (!baseURL) {
      throw new Error('UI smoke base URL is missing');
    }

    const context = await browser.newContext({
      baseURL,
      locale: BROWSER_LOCALE,
      userAgent: FIXED_USER_AGENT,
      viewport: { width: 1280, height: 720 },
      serviceWorkers: 'block',
    });

    if (sharedSession.cookies.length > 0) {
      await context.addCookies(sharedSession.cookies);
    }

    const page = await context.newPage();

    try {
      await use({ page, context });
    } finally {
      sharedSession.cookies = await context.cookies();
      await context.close();
    }
  },
});

export { expect };