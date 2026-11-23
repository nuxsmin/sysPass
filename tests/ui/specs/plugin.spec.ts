import { test } from '../src/session';

import { ActionTracker } from '../src/readiness';
import { runTierBIndex, TIER_B_INDEX_CASES } from '../src/tier-b';

const indexCase = TIER_B_INDEX_CASES.plugin;

test('plugin/index loads its own plugin grid in-shell', async ({
  sessionPage: { page, context, session },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });

  try {
    await runTierBIndex(page, tracker, session, indexCase);
  } finally {
    tracker.dispose();
  }
});