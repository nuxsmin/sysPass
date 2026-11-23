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
import { existsSync, readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const RUNNER = path.join(REPO_ROOT, 'scripts', 'ui-smoke.sh');
const PROBE = path.join(REPO_ROOT, 'tests', 'ui', 'server', 'check-db.php');

function readinessBlock() {
  const contents = readFileSync(RUNNER, 'utf8');
  const start = contents.indexOf('Waiting for MariaDB');
  const end = contents.indexOf('# end readiness probe', start);

  assert.notEqual(start, -1, 'runner must announce the database readiness phase');
  assert.notEqual(end, -1, 'readiness phase must end with its marker');

  return contents.slice(start, end);
}

test('database readiness is probed from the application network over TCP', () => {
  const block = readinessBlock();

  assert.match(
    block,
    /--network "\$NETWORK_NAME"/,
    'the readiness probe must run on the run-owned application network',
  );
  assert.match(
    block,
    /tests\/ui\/server\/check-db\.php/,
    'the readiness probe must use the shared TCP probe script',
  );
});

test('database readiness no longer trusts the temporary init server socket', () => {
  const block = readinessBlock();

  assert.doesNotMatch(block, /mysqladmin\s+ping/, 'socket-local ping reports the init server as ready');
  assert.doesNotMatch(block, /mysql\s+-uroot/, 'the readiness probe must not run inside the database container');
});

test('the TCP probe connects by host and port and keeps the password out of argv', () => {
  assert.equal(existsSync(PROBE), true, 'tests/ui/server/check-db.php must exist');

  const probe = readFileSync(PROBE, 'utf8');

  assert.match(probe, /mysql:host=/, 'the probe must use a TCP DSN with an explicit host');
  assert.doesNotMatch(probe, /mysql:unix_socket|host=localhost/, 'the probe must not fall back to a local socket');
  assert.match(probe, /MARIADB_ROOT_PASSWORD/, 'the probe must read the password from its environment');

  const block = readinessBlock();

  assert.match(block, /--env-file "\$RUN_ROOT\/env\/db\.env"/, 'the password must arrive through an env file');
  assert.doesNotMatch(
    block,
    /-e\s+[^\s]*PASSWORD=/i,
    'the readiness probe must not pass the password on the command line',
  );
});