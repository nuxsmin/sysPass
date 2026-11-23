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