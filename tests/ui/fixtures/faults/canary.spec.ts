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

const CANARY = 'CANARY_E2E_SECRET_6f2a9c';

test('synthetic canary failure exercises sanitized diagnostics end to end', async ({
  isolatedPage: { page },
}) => {
  await page.goto(`data:text/html,<p>canary page</p>?token=${CANARY}`);
  await page.setContent(
    [
      `<script>window.secret = "${CANARY}";</script>`,
      `<input id="pass" name="password" value="${CANARY}">`,
      `<textarea name="secret">${CANARY}</textarea>`,
      `<div id="pass">${CANARY}</div>`,
      `<div data-sensitive>${CANARY}</div>`,
    ].join(''),
  );

  await page.evaluate(async (secret) => {
    try {
      await fetch(`http://127.0.0.1:1/index.php?r=account/index&token=${secret}`);
    } catch {
      // The request failure is the synthetic failing route evidence.
    }
  }, CANARY);

  page.on('console', () => undefined);
  await page.evaluate((secret) => console.error(`token=${secret}`), CANARY);
  await page.evaluate((secret) => {
    setTimeout(() => {
      throw new Error(`password=${secret}`);
    }, 0);
  }, CANARY);
  await page.waitForTimeout(100);

  expect(false, 'intentional canary failure').toBe(true);
});