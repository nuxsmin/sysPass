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
import { readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const RUNNER = path.join(REPO_ROOT, 'scripts', 'ui-smoke.sh');

function webServerInvocation() {
  const contents = readFileSync(RUNNER, 'utf8');
  const start = contents.indexOf('Starting the run-owned web server');
  const end = contents.indexOf('wait-for-http.mjs', start);

  assert.notEqual(start, -1, 'runner must start the web server');
  assert.notEqual(end, -1, 'runner must wait for the web server');

  return contents.slice(start, end);
}

test('the web server suppresses deprecation output before session start', () => {
  const invocation = webServerInvocation();

  assert.match(invocation, /-d\s+"error_reporting=[^"]*E_ALL/, 'error reporting must remain enabled');
  assert.match(invocation, /~E_DEPRECATED/, 'engine deprecations must not be rendered');
  assert.match(invocation, /~E_USER_DEPRECATED/, 'user deprecations must not be rendered');
});

test('suppressing deprecations does not hide fatal or application errors', () => {
  const invocation = webServerInvocation();

  assert.doesNotMatch(invocation, /display_errors=0/, 'fatal errors must still be displayed');
  assert.doesNotMatch(invocation, /log_errors=0/, 'errors must still be logged');
});