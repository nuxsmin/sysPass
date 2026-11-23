/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

import { test as base, expect } from '@playwright/test';
import type { BrowserContext } from '@playwright/test';

import { BROWSER_LOCALE, FIXED_USER_AGENT } from './env';
import { FailureEvidenceRecorder } from './failure-evidence';

type BrowserCookies = Awaited<ReturnType<BrowserContext['cookies']>>;

export interface SessionState {
  cookies: BrowserCookies;
  authenticated: boolean;
}

interface IsolatedPage {
  page: import('@playwright/test').Page;
  context: BrowserContext;
}

export interface SessionPage extends IsolatedPage {
  session: SessionState;
}

interface SessionFixtures {
  sessionPage: SessionPage;
  isolatedPage: IsolatedPage;
}

interface SessionWorkerFixtures {
  sharedSession: SessionState;
}

/**
 * Worker-scoped authenticated session for the seeded suite.
 *
 * The first seeded test logs in through the UI and marks the worker session as
 * authenticated. Every following seeded test receives the live cookie jar
 * produced by the previous context, so SID rotation and legitimate Set-Cookie
 * updates are carried forward instead of restoring an immutable storage-state
 * snapshot or logging in again. A session that is actually lost is never
 * silently re-authenticated.
 *
 * Anonymous/login specs import the plain Playwright `test` and never receive
 * these cookies.
 */
export const test = base.extend<SessionFixtures, SessionWorkerFixtures>({
  sharedSession: [
    async ({}, use) => {
      await use({ cookies: [], authenticated: false });
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
    const sessionPage: SessionPage = { page, context, session: sharedSession };

    try {
      await use(sessionPage);
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