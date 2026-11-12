import assert from 'node:assert/strict';
import test from 'node:test';

import {
  DISCOVERY_CONTROL_ATTRIBUTES,
  buildInventory,
  buildRunMetadata,
  discoveryPagesFor,
  sanitizeControl,
  sanitizeInventory,
  sanitizePage,
} from './discovery.mjs';

test('inventory discovery selects only admin and restricted page sets', () => {
  assert.deepEqual(
    discoveryPagesFor('restricted', { accountId: 1002 }).map((page) => page.name),
    ['shell-home', 'account-index', 'account-view'],
  );

  assert.deepEqual(
    discoveryPagesFor('admin', { accountId: 1002 }).map((page) => page.name),
    [
      'shell-home',
      'account-index',
      'account-view',
      'account-edit',
      'account-create',
      'access-manager',
      'item-manager',
      'security-manager',
      'plugins',
      'config-manager',
      'user-settings',
      'notifications',
    ],
  );

  assert.equal(
    discoveryPagesFor('admin', { accountId: 1002 }).find((page) => page.name === 'account-view')
      ?.route,
    'account/view/1002',
  );
  assert.throws(() => discoveryPagesFor('demo'), /Unknown discovery profile/);
  assert.throws(() => discoveryPagesFor('admin'), /accountId/);
});

test('control sanitization keeps only stable structural metadata', () => {
  const sanitized = sanitizeControl({
    tag: 'a',
    id: 'btn-account-view',
    name: 'account-view',
    form: 'frmAccount',
    value: 'synthetic-account-value',
    text: 'Synthetic Account',
    password: 'synthetic-password',
    cookie: 'synthetic-cookie',
    href: 'https://example.invalid/secret',
    attributes: {
      'data-onclick': 'account/view',
      'data-onsubmit': 'account/save',
      'data-action-route': 'account/view',
      'data-route': 'account/index',
      'data-view': 'account',
      'data-onchange': 'account/change',
      'data-item-id': '1002',
      'data-password': 'synthetic-password',
      value: 'synthetic-value',
      href: 'https://example.invalid/secret',
    },
  });

  assert.deepEqual(sanitized, {
    tag: 'a',
    id: 'btn-account-view',
    name: 'account-view',
    form: 'frmAccount',
    attributes: {
      'data-onclick': 'account/view',
      'data-onsubmit': 'account/save',
      'data-action-route': 'account/view',
      'data-route': 'account/index',
      'data-view': 'account',
      'data-onchange': 'account/change',
    },
  });

  assert.deepEqual(Object.keys(sanitized).sort(), ['attributes', 'form', 'id', 'name', 'tag']);
  assert.deepEqual(Object.keys(sanitized.attributes), DISCOVERY_CONTROL_ATTRIBUTES);
});

test('page sanitization records route and status but drops page bodies and values', () => {
  const sanitized = sanitizePage({
    name: 'account-view',
    route: 'account/view/1002',
    url: 'https://127.0.0.1/index.php?r=account/view/1002',
    status: 200,
    body: '<html>synthetic response body</html>',
    headers: { 'set-cookie': 'synthetic-cookie' },
    account: { login: 'synthetic-login', password: 'synthetic-password' },
    controls: [
      {
        tag: 'input',
        id: 'account-pass',
        name: 'pass',
        value: 'synthetic-password',
        attributes: { 'data-onchange': 'account/checkPass' },
      },
    ],
  });

  assert.deepEqual(sanitized, {
    name: 'account-view',
    route: 'account/view/1002',
    status: 200,
    controlCount: 1,
    controls: [
      {
        tag: 'input',
        id: 'account-pass',
        name: 'pass',
        form: '',
        attributes: { 'data-onchange': 'account/checkPass' },
      },
    ],
  });

  const serialized = JSON.stringify(sanitized);
  assert.doesNotMatch(serialized, /synthetic-password|synthetic-cookie|synthetic-login|response body/);
  assert.doesNotMatch(serialized, /"body"|"headers"|"url"|"account"/);
});

test('inventory sanitization emits only the documented schema', () => {
  const inventory = buildInventory(
    [
      {
        profile: 'admin',
        pages: [
          {
            name: 'shell-home',
            route: 'index',
            status: 200,
            token: 'synthetic-token',
            controls: [
              {
                id: 'btn-5002',
                password: 'synthetic-password',
                attributes: { 'data-route': 'accessManager/index' },
              },
            ],
          },
        ],
      },
      {
        profile: 'restricted',
        pages: [
          {
            name: 'account-index',
            route: 'account/index',
            status: 403,
            cookie: 'synthetic-cookie',
            controls: [],
          },
        ],
      },
    ],
    { generatedAt: '2026-11-12T00:00:00.000Z' },
  );

  assert.deepEqual(Object.keys(inventory), [
    'schemaVersion',
    'tier',
    'generatedAt',
    'profiles',
  ]);
  assert.equal(inventory.schemaVersion, 1);
  assert.equal(inventory.tier, 'DISCOVERY');
  assert.equal(inventory.generatedAt, '2026-11-12T00:00:00.000Z');
  assert.deepEqual(Object.keys(inventory.profiles), ['admin', 'restricted']);
  assert.deepEqual(inventory.profiles.restricted, [
    {
      name: 'account-index',
      route: 'account/index',
      status: 403,
      controlCount: 0,
      controls: [],
    },
  ]);

  const serialized = JSON.stringify(inventory);
  assert.doesNotMatch(serialized, /synthetic-token|synthetic-password|synthetic-cookie/);
  assert.doesNotMatch(serialized, /credential|password|cookie|authorization/i);
});

test('run metadata contains counts and no account or secret fields', () => {
  const inventory = sanitizeInventory({
    generatedAt: '2026-11-12T00:00:00.000Z',
    profiles: {
      restricted: [
        { name: 'shell-home', route: 'index', status: 200, controls: [] },
        {
          name: 'account-view',
          route: 'account/view/1002',
          status: 200,
          controls: [{ id: 'btn-view', attributes: { 'data-route': 'account/view' } }],
        },
      ],
    },
  });

  const metadata = buildRunMetadata(inventory, { runId: 'run-1' });

  assert.deepEqual(metadata, {
    schemaVersion: 1,
    runId: 'run-1',
    tier: 'DISCOVERY',
    generatedAt: '2026-11-12T00:00:00.000Z',
    profiles: {
      restricted: {
        pages: 2,
        controls: 1,
        statuses: { '200': 2 },
      },
    },
  });

  assert.doesNotMatch(JSON.stringify(metadata), /credential|password|cookie|authorization/i);
});
