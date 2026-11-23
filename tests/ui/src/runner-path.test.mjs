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
import { existsSync, readFileSync, statSync } from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const RUNNER = path.join(REPO_ROOT, 'scripts', 'ui-smoke.sh');
const OBSOLETE_RUNNER = path.join(REPO_ROOT, '.ai-cli', 'bin', 'ui-smoke');

const GUIDANCE_FILES = [
  'AGENTS.md',
  '.ai-cli/README.md',
  '.ai-cli/allowed-commands.txt',
  'docs/agents/ui-smoke-suite.md',
  'docs/adr/0009-browser-smoke-suite-for-page-load-actions.md',
  'tests/ui/README.md',
  'tests/ui/src/env.ts',
];

test('shared UI smoke runner lives in scripts, not in AI-only .ai-cli', () => {
  assert.equal(existsSync(OBSOLETE_RUNNER), false, '.ai-cli/bin/ui-smoke must not exist');
  assert.equal(existsSync(RUNNER), true, 'scripts/ui-smoke.sh must exist');

  const mode = statSync(RUNNER).mode;

  assert.notEqual(mode & 0o111, 0, 'scripts/ui-smoke.sh must be executable');
});

test('shared UI smoke runner reports its repository path', () => {
  const result = spawnSync(RUNNER, ['--help'], { encoding: 'utf8' });

  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /scripts\/ui-smoke\.sh/);
});

test('guidance references the shared runner and documents the AI-only scope', () => {
  for (const file of GUIDANCE_FILES) {
    const contents = readFileSync(path.join(REPO_ROOT, file), 'utf8');

    assert.doesNotMatch(contents, /\.ai-cli\/bin\/ui-smoke/, file);
  }

  assert.match(
    readFileSync(path.join(REPO_ROOT, '.ai-cli', 'README.md'), 'utf8'),
    /ONLY for AI agents/,
  );
});
