import assert from 'node:assert/strict';
import { existsSync, mkdirSync, mkdtempSync, readdirSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { evaluateFreshness } from './install-guard.mjs';
import {
  INSTANCE_SUBDIRS,
  createInstallIdentity,
  resetInstallWorkspace,
} from './install-lifecycle.mjs';

const MODULE = path.join(
  path.dirname(fileURLToPath(import.meta.url)),
  'install-lifecycle.mjs',
);

function installedFixture() {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-install-reset-'));

  mkdirSync(path.join(root, 'config'), { recursive: true });
  mkdirSync(path.join(root, 'app-cache'), { recursive: true });
  mkdirSync(path.join(root, 'sessions'), { recursive: true });
  writeFileSync(path.join(root, 'config', 'config.xml'), '<config installed="true"/>');
  writeFileSync(path.join(root, 'app-cache', 'config.cache'), 'installed-config');
  writeFileSync(path.join(root, 'sessions', 'sess_installed'), 'session');

  return root;
}

test('a clean rerun destroys the previously installed wizard workspace', () => {
  const root = installedFixture();

  resetInstallWorkspace(root);

  assert.equal(existsSync(path.join(root, 'config', 'config.xml')), false);
  assert.equal(existsSync(path.join(root, 'app-cache', 'config.cache')), false);
  assert.equal(existsSync(path.join(root, 'sessions', 'sess_installed')), false);

  for (const directory of INSTANCE_SUBDIRS) {
    assert.equal(existsSync(path.join(root, directory)), true, `${directory} must be recreated empty`);
    assert.deepEqual(readdirSync(path.join(root, directory)), [], `${directory} must start empty`);
  }
});

test('the recreated workspace is fresh and the guard accepts it', () => {
  const root = installedFixture();

  resetInstallWorkspace(root);

  const freshness = evaluateFreshness({
    installed: false,
    configFileExists: existsSync(path.join(root, 'config', 'config.xml')),
    databaseExists: false,
    appCacheEntries: readdirSync(path.join(root, 'app-cache')).length,
    sessionEntries: readdirSync(path.join(root, 'sessions')).length,
    retries: 0,
  });

  assert.deepEqual(freshness, { ok: true, errors: [] });
});

test('a rerun creates a new identity and never reuses the previous database name', () => {
  const first = createInstallIdentity({
    random: (size) => Buffer.alloc(size, 0x11),
  });
  const second = createInstallIdentity({
    random: (size) => Buffer.alloc(size, 0x22),
  });

  assert.notEqual(first.databaseName, second.databaseName);
  assert.notEqual(first.networkName, second.networkName);
  assert.notEqual(first.dbContainer, second.dbContainer);
  assert.notEqual(first.webContainer, second.webContainer);
  assert.match(first.databaseName, /^sp_ui_[0-9a-f]{12}$/);
  assert.match(first.networkName, /^sp-ui-install-net-[0-9a-f]{12}$/);
});

test('the reset CLI clears the real workspace and reports the recreated directories', () => {
  const root = installedFixture();
  const result = spawnSync(process.execPath, [MODULE, 'reset', '--root', root], {
    encoding: 'utf8',
  });

  assert.equal(result.status, 0, result.stderr);
  assert.deepEqual(JSON.parse(result.stdout).directories, [...INSTANCE_SUBDIRS]);
  assert.equal(existsSync(path.join(root, 'config', 'config.xml')), false);
});

test('the reset helper refuses to clear a filesystem root', () => {
  assert.throws(
    () => resetInstallWorkspace(path.parse(process.cwd()).root),
    /refusing to reset a filesystem root/,
  );
});
