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
import { mkdtempSync, mkdirSync, symlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { validateRunContext } from './preflight.mjs';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');

function makeContext(overrides = {}) {
  const runRoot = mkdtempSync(path.join(tmpdir(), 'sp-ui-preflight-'));
  const repoRoot = REPO_ROOT;

  return {
    runRoot,
    repoRoot,
    databaseName: 'sp_ui_0123456789ab',
    origin: 'http://127.0.0.1:54321',
    externalBaseUrl: '',
    ownedPaths: ['config', 'cache', 'temp', 'sessions', 'output'].map((name) =>
      path.join(runRoot, name),
    ),
    ...overrides,
  };
}

test('accepts a run-owned context outside the repository', () => {
  const context = makeContext();
  for (const ownedPath of context.ownedPaths) {
    mkdirSync(ownedPath, { recursive: true });
  }

  assert.deepEqual(validateRunContext(context), { ok: true, errors: [] });
});

test('rejects a run root inside a protected developer path', () => {
  const repoRoot = mkdtempSync(path.join(tmpdir(), 'sp-ui-repo-'));
  const runRoot = path.join(repoRoot, 'app', 'cache', 'run');
  mkdirSync(runRoot, { recursive: true });

  const result = validateRunContext(
    makeContext({
      runRoot,
      repoRoot,
      ownedPaths: [path.join(runRoot, 'config')],
    }),
  );

  assert.equal(result.ok, false);
  assert.match(result.errors.join('\n'), /protected repository path/);
});

test('rejects an owned path that is a symlink outside the run root', () => {
  const context = makeContext();
  const outside = mkdtempSync(path.join(tmpdir(), 'sp-ui-outside-'));
  symlinkSync(outside, path.join(context.runRoot, 'config'));

  const result = validateRunContext(context);

  assert.equal(result.ok, false);
  assert.match(result.errors.join('\n'), /must be a real directory inside the run root/);
});

test('rejects a symlinked run root', () => {
  const target = mkdtempSync(path.join(tmpdir(), 'sp-ui-target-'));
  const link = path.join(mkdtempSync(path.join(tmpdir(), 'sp-ui-link-')), 'run');
  symlinkSync(target, link);

  const result = validateRunContext(
    makeContext({
      runRoot: link,
      ownedPaths: [path.join(target, 'config')],
    }),
  );

  assert.equal(result.ok, false);
  assert.match(result.errors.join('\n'), /run root must not be a symlink/);
});

test('rejects non-owned database names', () => {
  for (const databaseName of ['syspass', 'mysql', 'sp_ui_', 'sp_ui_abc', 'dev_db']) {
    const result = validateRunContext(makeContext({ databaseName }));
    assert.equal(result.ok, false, databaseName);
    assert.match(result.errors.join('\n'), /run-owned database name/, databaseName);
  }
});

test('accepts only the runner-owned loopback origin', () => {
  for (const origin of [
    'http://example.com:8080',
    'https://127.0.0.1:8080',
    'http://localhost:8080',
    'http://127.0.0.1',
    'http://127.0.0.1:80',
  ]) {
    const result = validateRunContext(makeContext({ origin }));
    assert.equal(result.ok, false, origin);
    assert.match(result.errors.join('\n'), /loopback origin/, origin);
  }
});

test('rejects an external E2E_BASE_URL', () => {
  const result = validateRunContext(
    makeContext({ externalBaseUrl: 'https://syspass.example.com' }),
  );

  assert.equal(result.ok, false);
  assert.match(result.errors.join('\n'), /E2E_BASE_URL is runner-owned/);
});

test('rejects a non-empty run configuration directory before mutation', () => {
  const context = makeContext();
  const configDir = path.join(context.runRoot, 'config');
  mkdirSync(configDir, { recursive: true });
  writeFileSync(path.join(configDir, 'config.xml'), '<config/>');

  const result = validateRunContext(context);

  assert.equal(result.ok, false);
  assert.match(result.errors.join('\n'), /run configuration directory must be empty/);
});

test('rejects relative owned paths', () => {
  const result = validateRunContext(makeContext({ ownedPaths: ['config'] }));

  assert.equal(result.ok, false);
  assert.match(result.errors.join('\n'), /must be absolute/);
});
