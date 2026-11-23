import { test } from '../src/session';

import { ActionTracker } from '../src/readiness';
import {
  navigateTierBIndex,
  openSeededShell,
  TIER_B_INDEX_CASES,
} from '../src/tier-b';

const indexCase = TIER_B_INDEX_CASES.userSettingsManager;

test('userSettingsManager/index loads its own preferences content in-shell', async ({
  sessionPage: { page, context, session },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });

  try {
    await openSeededShell(page, tracker, session);
    await page.locator('#users-menu-lower-right').click();
    await navigateTierBIndex(page, tracker, indexCase);
  } finally {
    tracker.dispose();
  }
});