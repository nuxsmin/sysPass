import type { Page } from '@playwright/test';

import { shellContract } from './contracts';
import { FIXTURE } from './fixture';
import { ActionTracker } from './readiness';

/**
 * Shared authenticated-shell contract and opener for the seeded suite.
 *
 * Both Tier A and Tier B use the same positive contract so a shell regression
 * is reported consistently, and the opener returns the navigation lower bound
 * so callers can prove a reuse path did not silently re-login.
 */
export const SEEDED_SHELL_CONTRACT = shellContract({
  parentAccount: FIXTURE.names.parentAccount,
  childAccount: FIXTURE.names.childAccount,
  client: FIXTURE.names.client,
  category: FIXTURE.names.category,
});

export async function openSeededShellPage(page: Page, tracker: ActionTracker): Promise<number> {
  tracker.setContract(SEEDED_SHELL_CONTRACT);

  const since = Date.now();

  await page.goto('/index.php?r=index', { waitUntil: 'domcontentloaded' });
  await tracker.waitForReady(SEEDED_SHELL_CONTRACT, since);

  return since;
}
