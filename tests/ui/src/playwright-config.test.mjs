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

import { TIER_A_SPECS, TIER_B_SPECS, specsForTier } from './tiers.mjs';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const CONFIG = path.join(REPO_ROOT, 'tests', 'ui', 'playwright.config.ts');

function chromiumProject() {
  const contents = readFileSync(CONFIG, 'utf8');
  const start = contents.indexOf('projects:');

  assert.notEqual(start, -1, 'Playwright config must declare projects');

  return contents.slice(start);
}

test('the fixed user agent wins over the desktop device descriptor', () => {
  const project = chromiumProject();

  assert.match(project, /\.\.\.devices\['Desktop Chrome'\]/, 'desktop device properties must be retained');
  assert.match(
    project,
    /\.\.\.devices\['Desktop Chrome'\][\s\S]*userAgent:\s*FIXED_USER_AGENT/,
    'the fixed user agent must be applied after the device descriptor',
  );
});

test('the resolved capture policy controls retries and flaky rejection', () => {
  const contents = readFileSync(CONFIG, 'utf8');

  assert.match(contents, /retries:\s*capture\.retries/, 'retries must come from the capture policy');
  assert.match(
    contents,
    /failOnFlakyTests:\s*capture\.failOnFlakyTests/,
    'flaky rejection must come from the capture policy',
  );
});

test('the Playwright config runs exactly the selected tier and keeps Tier A the default', () => {
  const contents = readFileSync(CONFIG, 'utf8');

  assert.deepEqual(specsForTier('A'), TIER_A_SPECS);
  assert.deepEqual(specsForTier('a'), TIER_A_SPECS);
  assert.deepEqual(specsForTier('B'), TIER_B_SPECS);
  assert.notDeepEqual(TIER_A_SPECS, TIER_B_SPECS);
  assert.match(contents, /SP_UI_TIER \?\? 'A'/);
  assert.match(contents, /specsForTier\(tier\)/);
  assert.match(contents, /testMatch:\s*selectedSpecs/);
});

test('the installer spec is its own explicit selection', () => {
  assert.deepEqual(specsForTier('install'), ['install.spec.ts']);
});

test('admin navigation wiring is its own reporting-only selection', () => {
  assert.deepEqual(specsForTier('wiring'), ['adminNavigation.wiring.spec.ts']);
  assert.equal(TIER_A_SPECS.includes('adminNavigation.wiring.spec.ts'), false);
  assert.equal(TIER_B_SPECS.includes('adminNavigation.wiring.spec.ts'), false);
});

test('write wiring is its own reporting-only selection', () => {
  assert.deepEqual(specsForTier('write'), [
    'accountCreate.write.spec.ts',
    'accountEdit.write.spec.ts',
    'accountDelete.write.spec.ts',
    'accountListFiles.write.spec.ts',
    'accountManagerDelete.write.spec.ts',
    'authTokenDelete.write.spec.ts',
    'itemMetadataDelete.write.spec.ts',
    'notificationDelete.write.spec.ts',
    'notificationSave.write.spec.ts',
    'userGroupDelete.write.spec.ts',
    'userDelete.write.spec.ts',
    'userEditPass.write.spec.ts',
    'userProfileDelete.write.spec.ts',
  ]);
  assert.equal(TIER_A_SPECS.includes('accountCreate.write.spec.ts'), false);
  assert.equal(TIER_B_SPECS.includes('accountCreate.write.spec.ts'), false);
  assert.equal(TIER_A_SPECS.includes('accountDelete.write.spec.ts'), false);
  assert.equal(TIER_B_SPECS.includes('accountDelete.write.spec.ts'), false);
});
