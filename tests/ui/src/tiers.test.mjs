import assert from 'node:assert/strict';
import test from 'node:test';

import {
  normalizeTier,
  specsForTier,
  TIER_A_SPECS,
  TIER_B_SPECS,
  TIER_SPECS,
} from './tiers.mjs';

test('tier tokens are normalized to the canonical upper-case selection', () => {
  for (const value of ['A', 'a', ' A ', '\ta\n']) {
    assert.equal(normalizeTier(value), 'A');
  }

  for (const value of ['B', 'b', ' B ', '\tb\n']) {
    assert.equal(normalizeTier(value), 'B');
  }

  assert.equal(normalizeTier(undefined), 'A');
  assert.deepEqual(specsForTier('a'), TIER_A_SPECS);
  assert.deepEqual(specsForTier('b'), TIER_B_SPECS);
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

  assert.deepEqual(Object.keys(TIER_SPECS).sort(), ['A', 'B']);
});
