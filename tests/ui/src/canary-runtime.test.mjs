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

import assert from 'node:assert/strict';
import { mkdtempSync, readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { CANARY_SKIP_ENV, canaryRuntimeStatus } from './canary-runtime.mjs';

const UI_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const REPO_ROOT = path.resolve(UI_DIR, '..', '..');

const MISSING = {
  playwrightPath: '/nonexistent/playwright',
  browsersPath: '/nonexistent/.browsers',
};

test('missing Playwright or pinned Chromium fails the canaries instead of skipping them', () => {
  const status = canaryRuntimeStatus({ ...MISSING, env: {} });

  assert.equal(status.available, false);
  assert.equal(status.optOut, false);
  assert.equal(status.skip, false, 'canaries must not skip by default');
  assert.equal(status.missing.length, 2);
  assert.match(status.message, /Playwright CLI not found/);
  assert.match(status.message, /pinned Chromium not found/);
  assert.match(status.message, new RegExp(CANARY_SKIP_ENV));
});

test('an explicit opt-out is the only way to skip missing canary prerequisites', () => {
  const optedOut = canaryRuntimeStatus({ ...MISSING, env: { [CANARY_SKIP_ENV]: '1' } });

  assert.equal(optedOut.optOut, true);
  assert.equal(optedOut.skip, true);

  for (const value of ['', '0', 'true', 'yes']) {
    const status = canaryRuntimeStatus({ ...MISSING, env: { [CANARY_SKIP_ENV]: value } });

    assert.equal(status.skip, false, `${CANARY_SKIP_ENV}=${value} must not opt out`);
  }
});

test('installed Playwright and pinned Chromium make the canaries runnable', () => {
  const directory = mkdtempSync(path.join(tmpdir(), 'sp-ui-canary-runtime-'));
  const playwrightPath = path.join(directory, 'playwright');
  writeFileSync(playwrightPath, '');

  const executable = canaryRuntimeStatus({
    playwrightPath,
    browsersPath: '/nonexistent/.browsers',
    chromiumExecutable: '/explicit/chromium',
    env: {},
  });

  assert.equal(executable.available, true);
  assert.equal(executable.skip, false);
});

test('the shared runner never sets the canary opt-out', () => {
  const runner = readFileSync(path.join(REPO_ROOT, 'scripts', 'ui-smoke.sh'), 'utf8');

  assert.doesNotMatch(runner, new RegExp(CANARY_SKIP_ENV));
});
