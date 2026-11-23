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
