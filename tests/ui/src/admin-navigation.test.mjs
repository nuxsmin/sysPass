import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { ADMIN_NAVIGATION_CASES } from './admin-navigation.mjs';
import { specsForTier, TIER_A_SPECS, TIER_B_SPECS } from './tiers.mjs';

const UI_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const SPEC = path.join(UI_DIR, 'specs', 'adminNavigation.wiring.spec.ts');

const EXPECTED = [
  {
    name: 'accessManager',
    trigger: '#btn-5002',
    route: 'accessManager/index',
    content: [
      'data-tab-route="accessManager/index"',
      'id="data-table-tblUsers"',
      'id="data-table-tblProfiles"',
    ],
    selectors: [
      '[data-tab-route="accessManager/index"]',
      '#data-table-tblUsers',
      '#data-table-tblProfiles',
    ],
  },
  {
    name: 'itemManager',
    trigger: '#btn-5001',
    route: 'itemManager/index',
    content: [
      'data-tab-route="itemManager/index"',
      'id="data-table-tblCategories"',
      'id="data-table-tblClients"',
    ],
    selectors: [
      '[data-tab-route="itemManager/index"]',
      '#data-table-tblCategories',
      '#data-table-tblClients',
    ],
  },
  {
    name: 'securityManager',
    trigger: '#btn-5003',
    route: 'securityManager/index',
    content: [
      'data-tab-route="securityManager/index"',
      'id="data-table-tblEventLog"',
      'id="data-table-tblTracks"',
    ],
    selectors: [
      '[data-tab-route="securityManager/index"]',
      '#data-table-tblEventLog',
      '#data-table-tblTracks',
    ],
  },
  {
    name: 'plugin',
    trigger: '#btn-1101',
    route: 'plugin/index',
    content: ['class="datagrid-header-title"', 'id="data-table-tblPlugins"'],
    selectors: ['.datagrid-header-title', '#data-table-tblPlugins'],
  },
  {
    name: 'configManager',
    trigger: '#btn-config',
    route: 'configManager/index',
    content: [
      'data-tab-route="configManager/index"',
      'id="frmConfig"',
      'data-action-route="configGeneral/save"',
    ],
    selectors: [
      '[data-tab-route="configManager/index"]',
      '#frmConfig',
      '[data-action-route="configGeneral/save"]',
    ],
  },
];

test('admin navigation wiring is its own reporting-only selection', () => {
  assert.deepEqual(specsForTier('wiring'), ['adminNavigation.wiring.spec.ts']);
  assert.deepEqual(specsForTier('WIRING'), ['adminNavigation.wiring.spec.ts']);
  assert.equal(TIER_A_SPECS.includes('adminNavigation.wiring.spec.ts'), false);
  assert.equal(TIER_B_SPECS.includes('adminNavigation.wiring.spec.ts'), false);
});

test('the inventory contains only the five ACL-gated administrator menu entries', () => {
  assert.deepEqual(
    ADMIN_NAVIGATION_CASES.map(({ name, trigger, route }) => ({ name, trigger, route })),
    EXPECTED.map(({ name, trigger, route }) => ({ name, trigger, route })),
  );

  const routes = ADMIN_NAVIGATION_CASES.map((entry) => entry.route);

  assert.equal(routes.includes('account/index'), false);
  assert.equal(routes.includes('account/create'), false);
  assert.equal(routes.includes('notification/index'), false);
  assert.equal(routes.includes('userSettingsManager/index'), false);
  assert.equal(new Set(routes).size, routes.length);
});

test('every entry declares stable response content and target-container selectors', () => {
  for (const [index, entry] of ADMIN_NAVIGATION_CASES.entries()) {
    const expected = EXPECTED[index];

    assert.equal(entry.route, expected.route);
    assert.deepEqual(entry.content, expected.content);
    assert.deepEqual(entry.selectors, expected.selectors);
    assert.ok(entry.content.length > 0);

    for (const marker of entry.content) {
      assert.match(marker, /^(?:id=|class=|data-)/, `${entry.route} marker ${marker} must be a stable hook`);
    }

    for (const selector of entry.selectors) {
      assert.match(selector, /^[#.\[]/, `${entry.route} selector ${selector} must be a hook`);
    }
  }
});

test('the spec clicks the real menu control and proves one successful action per route', () => {
  const source = readFileSync(SPEC, 'utf8');

  assert.match(source, /ADMIN_NAVIGATION_ENTRIES/);
  assert.match(source, /openSeededShell/);
  assert.match(source, /locator\(entry\.trigger\)/);
  assert.match(source, /\.click\(\)/);
  assert.match(source, /actionCount\(entry\.route, since\)/);
  assert.match(source, /action\?\.status\)\.toBe\(200\)/);
  assert.match(source, /action\?\.method\)\.toBe\('GET'\)/);
  assert.doesNotMatch(source, /toBeVisible|toBeInViewport|toHaveText|viewport/);
});