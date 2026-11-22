import { expect, test } from '../src/session';

import {
  ADMIN_NAVIGATION_ENTRIES,
  adminNavigationContract,
} from '../src/admin-navigation';
import { ActionTracker } from '../src/readiness';
import { openSeededShell } from '../src/tier-b';

for (const entry of ADMIN_NAVIGATION_ENTRIES) {
  test(`the ${entry.route} menu control dispatches exactly one successful action and renders its content`, async ({
    sessionPage: { page, context, session },
  }) => {
    const tracker = new ActionTracker(page, context, { sessionEstablished: false });
    const contract = adminNavigationContract(entry);

    try {
      await openSeededShell(page, tracker, session);

      const trigger = page.locator(entry.trigger).first();
      await expect(trigger).toHaveAttribute('data-route', entry.route);

      tracker.setContract(contract);
      const since = Date.now();

      await trigger.click();
      await tracker.waitForReady(contract, since);

      expect(
        tracker.actionCount(entry.route, since),
        `${entry.route} must be requested exactly once by its menu control`,
      ).toBe(1);

      const action = tracker.getAction(entry.route, since);
      expect(action?.status).toBe(200);
      expect(action?.method).toBe('GET');
    } finally {
      tracker.dispose();
    }
  });
}