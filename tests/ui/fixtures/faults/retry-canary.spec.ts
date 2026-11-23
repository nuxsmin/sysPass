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

import { expect, test } from '../../src/session';

const CANARY = 'CANARY_RETRY_SECRET_3a71d5';

test('a diagnostic retry keeps the first-attempt failure evidence', async ({
  isolatedPage: { page },
}, testInfo) => {
  await page.evaluate((secret) => console.error(`token=${secret}`), CANARY);

  if (testInfo.retry === 0) {
    expect(false, 'intentional first-attempt canary failure').toBe(true);
  }

  await expect(page.locator('body')).toBeAttached();
});
