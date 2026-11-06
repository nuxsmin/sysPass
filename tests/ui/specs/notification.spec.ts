import { test } from '../src/session';

import { ActionTracker } from '../src/readiness';
import { runTierBIndex, TIER_B_INDEX_CASES } from '../src/tier-b';

const indexCase = TIER_B_INDEX_CASES.notification;

test('notification/index loads its own notification grid in-shell', async ({
  sessionPage: { page, context, session },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });

  try {
    await runTierBIndex(page, tracker, session, indexCase);
  } finally {
    tracker.dispose();
  }
});