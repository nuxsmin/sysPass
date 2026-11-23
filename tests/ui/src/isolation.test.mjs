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
import { mkdirSync, mkdtempSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import test from 'node:test';

import {
  compareSnapshots,
  snapshotSeededState,
} from './isolation.mjs';

function writeFile(directory, relative, contents) {
  const target = path.join(directory, relative);

  mkdirSync(path.dirname(target), { recursive: true });
  writeFileSync(target, contents);
}

function seededFixture(prefix = 'sp-ui-seeded-') {
  const root = mkdtempSync(path.join(tmpdir(), prefix));

  writeFile(root, 'config/config.xml', '<config installed="true"/>');
  writeFile(root, 'cache/di.php', '<?php return [];');
  writeFile(root, 'sessions/sess_abc', 'session');
  writeFile(root, 'app-cache/config.cache', 'cached-config');
  writeFile(root, 'temp/upload.tmp', 'temporary upload');
  writeFile(root, 'app-temp/compiled.php', 'temporary application file');

  return root;
}

test('an unchanged seeded instance produces no isolation differences', () => {
  const root = seededFixture();
  const before = snapshotSeededState(root, { database: 'db-digest' });
  const after = snapshotSeededState(root, { database: 'db-digest' });

  const result = compareSnapshots(before, after);

  assert.equal(result.ok, true, result.changes.join('; '));
  assert.deepEqual(result.changes, []);
});

test('a wizard write into a seeded path is reported as a failed isolation proof', () => {
  const root = seededFixture();
  const before = snapshotSeededState(root, { database: 'db-digest' });

  writeFile(root, 'app-cache/config.cache', 'wizard-overwrote-this');
  const after = snapshotSeededState(root, { database: 'db-digest' });

  const result = compareSnapshots(before, after);

  assert.equal(result.ok, false);
  assert.match(result.changes.join('\n'), /app-cache/);
});

test('seeded database and session changes are detected', () => {
  const root = seededFixture();
  const before = snapshotSeededState(root, { database: 'db-digest' });

  writeFile(root, 'sessions/sess_abc', 'rotated');
  const after = snapshotSeededState(root, { database: 'db-digest-2' });

  const result = compareSnapshots(before, after);

  assert.equal(result.ok, false);
  assert.match(result.changes.join('\n'), /sessions/);
  assert.match(result.changes.join('\n'), /database/);
});

test('a temp-only change is detected in the seeded fingerprint', () => {
  const root = seededFixture();
  const before = snapshotSeededState(root, { database: 'db-digest' });

  writeFile(root, 'temp/late-upload.tmp', 'installer leaked into seeded temp');
  const after = snapshotSeededState(root, { database: 'db-digest' });

  const result = compareSnapshots(before, after);

  assert.equal(result.ok, false);
  assert.match(result.changes.join('\n'), /temp/);
});

test('an app-temp-only change is detected in the seeded fingerprint', () => {
  const root = seededFixture();
  const before = snapshotSeededState(root, { database: 'db-digest' });

  writeFile(root, 'app-temp/late-compiled.php', 'installer leaked into seeded app-temp');
  const after = snapshotSeededState(root, { database: 'db-digest' });

  const result = compareSnapshots(before, after);

  assert.equal(result.ok, false);
  assert.match(result.changes.join('\n'), /app-temp/);
});
