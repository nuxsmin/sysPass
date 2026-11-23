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

import {
  normalizeTier,
  specsForTier,
  TIER_A_SPECS,
  TIER_B_SPECS,
  TIER_DISCOVERY_SPECS,
  TIER_INSTALL_SPECS,
  TIER_WIRING_SPECS,
  TIER_WRITE_SPECS,
  TIER_SPECS,
} from './tiers.mjs';

test('tier tokens are normalized to the canonical upper-case selection', () => {
  for (const value of ['A', 'a', ' A ', '\ta\n']) {
    assert.equal(normalizeTier(value), 'A');
  }

  for (const value of ['B', 'b', ' B ', '\tb\n']) {
    assert.equal(normalizeTier(value), 'B');
  }

  for (const value of ['install', 'INSTALL', ' Install ']) {
    assert.equal(normalizeTier(value), 'INSTALL');
  }

  for (const value of ['wiring', 'WIRING', ' Wiring ']) {
    assert.equal(normalizeTier(value), 'WIRING');
  }

  for (const value of ['write', 'WRITE', ' Write ']) {
    assert.equal(normalizeTier(value), 'WRITE');
  }

  for (const value of ['discovery', 'DISCOVERY', ' Discovery ']) {
    assert.equal(normalizeTier(value), 'DISCOVERY');
  }

  assert.equal(normalizeTier(undefined), 'A');
  assert.deepEqual(specsForTier('a'), TIER_A_SPECS);
  assert.deepEqual(specsForTier('b'), TIER_B_SPECS);
  assert.deepEqual(specsForTier('install'), TIER_INSTALL_SPECS);
  assert.deepEqual(specsForTier('wiring'), TIER_WIRING_SPECS);
  assert.deepEqual(specsForTier('write'), TIER_WRITE_SPECS);
  assert.deepEqual(specsForTier('discovery'), TIER_DISCOVERY_SPECS);
});

test('an unknown tier is rejected instead of producing a Tier Z selection', () => {
  for (const value of ['Z', 'z', 'C', '', 'tier-a', 'AB']) {
    assert.throws(
      () => normalizeTier(value),
      /Unknown UI smoke tier/,
      `tier token ${JSON.stringify(value)} must be rejected`,
    );
    assert.throws(() => specsForTier(value), /Unknown UI smoke tier/);
  }

  assert.deepEqual(Object.keys(TIER_SPECS).sort(), [
    'A',
    'B',
    'DISCOVERY',
    'INSTALL',
    'WIRING',
    'WRITE',
  ]);
});
