import { test } from '../src/session';

import { ActionTracker } from '../src/readiness';
import {
  proveTierBFaultCanary,
  runTierBIndex,
  TIER_B_INDEX_CASES,
} from '../src/tier-b';

const indexCase = TIER_B_INDEX_CASES.accessManager;

test('accessManager/index loads its own tabbed content in-shell', async ({
  sessionPage: { page, context, session },
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });

  try {
    await runTierBIndex(page, tracker, session, indexCase);
  } finally {
    tracker.dispose();
  }
});

if (process.env.SP_UI_TIER_B_FAULT === 'accessManager/index') {
  test('Tier B fault canary: a suppressed accessManager/index initializer fails this spec naming the route', async ({
    sessionPage: { page, context, session },
  }) => {
    await proveTierBFaultCanary(page, context, session, indexCase);
  });
}