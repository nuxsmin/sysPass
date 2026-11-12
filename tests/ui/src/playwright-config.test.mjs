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
