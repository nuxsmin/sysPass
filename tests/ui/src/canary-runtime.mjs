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

import { existsSync } from 'node:fs';

export const CANARY_SKIP_ENV = 'SP_UI_ALLOW_MISSING_CANARY_RUNTIME';

/**
 * Resolve whether the synthetic canaries can run.
 *
 * Missing prerequisites fail the canaries by default. The opt-out is only for
 * a deliberate local bypass and is never set by the shared runner.
 *
 * @param {object} input
 * @param {string} input.playwrightPath
 * @param {string} input.browsersPath
 * @param {string} [input.chromiumExecutable]
 * @param {Record<string, string | undefined>} [input.env]
 * @returns {{available: boolean, missing: string[], optOut: boolean, skip: boolean, message: string}}
 */
export function canaryRuntimeStatus({
  playwrightPath,
  browsersPath,
  chromiumExecutable = '',
  env = process.env,
} = {}) {
  const missing = [];

  if (!existsSync(playwrightPath)) {
    missing.push(`Playwright CLI not found: ${playwrightPath}`);
  }

  if (!existsSync(browsersPath) && !chromiumExecutable) {
    missing.push(`pinned Chromium not found: ${browsersPath}`);
  }

  const available = missing.length === 0;
  const optOut = env[CANARY_SKIP_ENV] === '1';
  const optOutHint = `Set ${CANARY_SKIP_ENV}=1 only for a deliberate local bypass.`;

  return {
    available,
    missing,
    optOut,
    skip: !available && optOut,
    message: available
      ? ''
      : `Canary prerequisites are unavailable: ${missing.join('; ')}. Install the pinned runtime or explicitly opt out. ${optOutHint}`,
  };
}
