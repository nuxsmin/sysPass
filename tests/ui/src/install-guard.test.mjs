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
import test from 'node:test';

import { evaluateFreshness } from './install-guard.mjs';

function freshInstance(overrides = {}) {
  return {
    installed: false,
    configFileExists: false,
    databaseExists: false,
    appCacheEntries: 0,
    sessionEntries: 0,
    retries: 0,
    ...overrides,
  };
}

test('a fresh, uninstalled wizard instance is eligible for the browser installer', () => {
  assert.deepEqual(evaluateFreshness(freshInstance()), { ok: true, errors: [] });
});

test('an already installed instance is refused instead of retried', () => {
  assert.equal(evaluateFreshness(freshInstance({ installed: true })).ok, false);
  assert.match(
    evaluateFreshness(freshInstance({ installed: true })).errors.join('\n'),
    /already installed/,
  );
  assert.match(
    evaluateFreshness(freshInstance({ configFileExists: true })).errors.join('\n'),
    /run configuration already exists/,
  );
});

test('a precreated database is refused: the wizard must create its own unique database', () => {
  const result = evaluateFreshness(freshInstance({ databaseExists: true }));

  assert.equal(result.ok, false);
  assert.match(result.errors.join('\n'), /database already exists before installation/);
});

test('installer retries and populated wizard caches/sessions are refused', () => {
  assert.match(
    evaluateFreshness(freshInstance({ retries: 1 })).errors.join('\n'),
    /retries must be 0/,
  );
  assert.match(
    evaluateFreshness(freshInstance({ appCacheEntries: 1 })).errors.join('\n'),
    /application cache must be empty before installation/,
  );
  assert.match(
    evaluateFreshness(freshInstance({ sessionEntries: 1 })).errors.join('\n'),
    /session namespace must be empty before installation/,
  );
});
