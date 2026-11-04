import { test as base, expect } from '@playwright/test';
import type { BrowserContext } from '@playwright/test';

import { BROWSER_LOCALE, FIXED_USER_AGENT } from './env';
import { FailureEvidenceRecorder } from './failure-evidence';

type BrowserCookies = Awaited<ReturnType<BrowserContext['cookies']>>;

export interface SessionState {
  cookies: BrowserCookies;
}

interface IsolatedPage {
  page: import('@playwright/test').Page;
  context: BrowserContext;
}

interface SessionFixtures {
  sessionPage: IsolatedPage;
  isolatedPage: IsolatedPage;
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
  isolatedPage: async ({ browser, baseURL }, use, testInfo) => {
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
    const page = await context.newPage();
    const evidence = new FailureEvidenceRecorder(page, context, testInfo);

    try {
      await use({ page, context });
    } finally {
      await evidence.finalize().catch((error: unknown) => {
        console.warn(`[ui-smoke evidence] ${error instanceof Error ? error.message : String(error)}`);
      });
      await context.close();
    }
  },
  sessionPage: async ({ browser, baseURL, sharedSession }, use, testInfo) => {
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
    const evidence = new FailureEvidenceRecorder(page, context, testInfo);

    try {
      await use({ page, context });
    } finally {
      await evidence.finalize().catch((error: unknown) => {
        console.warn(`[ui-smoke evidence] ${error instanceof Error ? error.message : String(error)}`);
      });
      sharedSession.cookies = await context.cookies();
      await context.close();
    }
  },
});

export { expect };