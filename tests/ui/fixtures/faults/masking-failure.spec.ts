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

import { CANARY, SENSITIVE_BOX, VIEWPORT } from './masking-failure.region.mjs';

test('renders a canary in a sensitive region that the failure screenshot must mask', async ({
  isolatedPage: { page },
}) => {
  await page.setViewportSize(VIEWPORT);
  await page.setContent(
    [
      '<!doctype html><html><body style="margin:0;background:#ffffff">',
      `<div id="pass" data-sensitive style="position:absolute;left:${SENSITIVE_BOX.left}px;top:${SENSITIVE_BOX.top}px;width:${SENSITIVE_BOX.width}px;height:${SENSITIVE_BOX.height}px;background:#e8f0fe;color:#000000;font:24px/34px monospace;overflow:hidden">${CANARY} ${CANARY}</div>`,
      '</body></html>',
    ].join(''),
  );

  const referenceFile = process.env.SP_UI_CANARY_UNMASKED_REFERENCE;

  if (referenceFile) {
    await page.screenshot({
      path: referenceFile,
      animations: 'disabled',
      caret: 'hide',
      fullPage: false,
      scale: 'css',
    });
  }

  expect(await page.locator('#pass').textContent()).toContain(CANARY);
  expect(false, 'intentional canary failure to exercise failure-evidence screenshots').toBe(true);
});
