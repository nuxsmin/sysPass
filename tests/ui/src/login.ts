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

import type { FixtureCredential } from './fixture';
import { loginThroughUiFlow } from './login-flow.mjs';

/**
 * Authenticate through the real login form exactly once per seeded suite.
 *
 * Returns the timestamp taken immediately before submitting the form. Callers
 * use it as the lower bound for shell actions that are issued after the login
 * redirect, so the anonymous login boot is not mistaken for the shell boot.
 */
export async function loginThroughUi(page: Page, credential: FixtureCredential): Promise<number> {
  return loginThroughUiFlow(page, credential);
}
