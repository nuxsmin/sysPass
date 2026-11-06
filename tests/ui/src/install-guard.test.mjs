import assert from 'node:assert/strict';
import test from 'node:test';

import { evaluateFreshness } from './install-guard.mjs';

function freshInstance(overrides = {}) {
  return {
    installed: false,
    configFileExists: false,
    databaseExists: false,
    appCacheEntries: 0,
    sessionEntries: 0,
    retries: 0,
    ...overrides,
  };
}

test('a fresh, uninstalled wizard instance is eligible for the browser installer', () => {
  assert.deepEqual(evaluateFreshness(freshInstance()), { ok: true, errors: [] });
});

test('an already installed instance is refused instead of retried', () => {
  assert.equal(evaluateFreshness(freshInstance({ installed: true })).ok, false);
  assert.match(
    evaluateFreshness(freshInstance({ installed: true })).errors.join('\n'),
    /already installed/,
  );
  assert.match(
    evaluateFreshness(freshInstance({ configFileExists: true })).errors.join('\n'),
    /run configuration already exists/,
  );
});

test('a precreated database is refused: the wizard must create its own unique database', () => {
  const result = evaluateFreshness(freshInstance({ databaseExists: true }));

  assert.equal(result.ok, false);
  assert.match(result.errors.join('\n'), /database already exists before installation/);
});

test('installer retries and populated wizard caches/sessions are refused', () => {
  assert.match(
    evaluateFreshness(freshInstance({ retries: 1 })).errors.join('\n'),
    /retries must be 0/,
  );
  assert.match(
    evaluateFreshness(freshInstance({ appCacheEntries: 1 })).errors.join('\n'),
    /application cache must be empty before installation/,
  );
  assert.match(
    evaluateFreshness(freshInstance({ sessionEntries: 1 })).errors.join('\n'),
    /session namespace must be empty before installation/,
  );
});
