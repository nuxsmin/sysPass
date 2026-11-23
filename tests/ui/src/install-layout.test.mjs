import assert from 'node:assert/strict';
import { mkdirSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import {
  INSTANCE_DIRECTORIES,
  instanceLayout,
  namespaceErrors,
  resolveInstallLayout,
} from './install-layout.mjs';

const MODULE = path.join(
  path.dirname(fileURLToPath(import.meta.url)),
  'install-layout.mjs',
);

function seededRoot() {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-layout-seeded-'));

  for (const directory of INSTANCE_DIRECTORIES) {
    mkdirSync(path.join(root, directory), { recursive: true });
  }

  return root;
}

test('the real runner layout keeps every managed installer path disjoint from the seeded instance', () => {
  const root = seededRoot();
  const layout = resolveInstallLayout(root);

  assert.equal(
    layout.install.root,
    path.join(root, 'install'),
    'the runtime layout is the documented <run root>/install subtree',
  );

  assert.deepEqual(
    namespaceErrors(layout, {
      seededDatabase: 'sp_ui_seeded',
      installDatabase: 'sp_ui_install',
    }),
    [],
  );

  for (const directory of INSTANCE_DIRECTORIES) {
    assert.notEqual(
      layout.seeded[directory],
      layout.install[directory],
      `${directory} must not be shared between the instances`,
    );
    assert.equal(
      layout.install[directory].startsWith(`${layout.install.root}${path.sep}`),
      true,
      `${directory} must live under the installer root`,
    );
  }
});

test('an installer namespace inside a seeded managed path is rejected', () => {
  const root = seededRoot();
  const layout = resolveInstallLayout(root);
  const displaced = {
    ...layout,
    install: instanceLayout(path.join(layout.seeded.config, 'install')),
  };

  assert.match(
    namespaceErrors(displaced).join('\n'),
    /installer configuration overlaps the seeded configuration/,
  );
});

test('an identical installer and seeded root is rejected', () => {
  const root = seededRoot();
  const layout = resolveInstallLayout(root);
  const identical = {
    seeded: instanceLayout(root),
    install: instanceLayout(root),
  };

  assert.match(
    namespaceErrors(identical).join('\n'),
    /installer run root must not be the seeded run root/,
  );
});

test('the runtime layout refuses to reuse the seeded database name', () => {
  const root = seededRoot();
  const layout = resolveInstallLayout(root);

  assert.match(
    namespaceErrors(layout, {
      seededDatabase: 'sp_ui_same',
      installDatabase: 'sp_ui_same',
    }).join('\n'),
    /installer database must not be the seeded database/,
  );
});

test('the layout CLI verifies the actual directories used by the runner', () => {
  const root = seededRoot();
  const result = spawnSync(
    process.execPath,
    [
      MODULE,
      'verify',
      '--seeded-root',
      root,
      '--seeded-database',
      'sp_ui_seeded',
      '--install-database',
      'sp_ui_install',
    ],
    { encoding: 'utf8' },
  );

  assert.equal(result.status, 0, result.stderr);
  assert.equal(JSON.parse(result.stdout).installRoot, path.join(root, 'install'));
});
