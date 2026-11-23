import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import {
  INSTALL_TIER,
  assertInstallerRetries,
  normalizeInstallFlag,
  resolveRunPlan,
  resolveSummaryRoots,
} from './install-selection.mjs';

const CLI = path.join(
  path.dirname(fileURLToPath(import.meta.url)),
  'install-selection.mjs',
);

test('the installer selection is explicit and separate from Tier A and Tier B', () => {
  const installer = resolveRunPlan({ tier: 'install' });

  assert.equal(installer.tier, INSTALL_TIER);
  assert.equal(installer.seededTier, null);
  assert.equal(installer.installSelected, true);
  assert.deepEqual(installer.phases, ['install']);

  for (const tier of ['A', 'a', 'B', 'b', 'DISCOVERY', 'discovery']) {
    const plan = resolveRunPlan({ tier });

    assert.equal(plan.installSelected, false);
    assert.notEqual(plan.tier, INSTALL_TIER);
    assert.deepEqual(plan.phases, ['seeded']);
  }

  assert.equal(resolveRunPlan({}).tier, 'A', 'Tier A remains the default');
});

test('inventory discovery is a seeded-only selection and rejects the installer', () => {
  const plan = resolveRunPlan({ tier: 'discovery' });

  assert.equal(plan.tier, 'DISCOVERY');
  assert.equal(plan.seededTier, 'DISCOVERY');
  assert.equal(plan.installSelected, false);
  assert.deepEqual(plan.phases, ['seeded']);
  assert.throws(
    () => resolveRunPlan({ tier: 'DISCOVERY', install: '1' }),
    /inventory discovery cannot run the installer/,
  );
});

test('a combined selection runs the seeded specs before the installer phase', () => {
  for (const tier of ['A', 'B']) {
    const plan = resolveRunPlan({ tier, install: '1' });

    assert.equal(plan.installSelected, true);
    assert.equal(plan.seededTier, tier);
    assert.deepEqual(
      plan.phases,
      ['seeded', 'install'],
      'the installer phase must be orchestrated after seeded specs, never by filename order',
    );
  }

  assert.deepEqual(resolveRunPlan({ tier: 'install', install: '1' }).phases, ['install']);
});

test('the installer phase always uses zero retries and rejects diagnostic retries', () => {
  assert.equal(resolveRunPlan({ tier: 'install' }).retries.install, 0);
  assert.equal(resolveRunPlan({ tier: 'A', install: '1' }).retries.install, 0);
  assert.throws(
    () => resolveRunPlan({ tier: 'A', install: '1', diagnosticRetry: '1' }),
    /installer selection requires retries: 0/,
  );
  assert.throws(
    () => resolveRunPlan({ tier: 'install', diagnosticRetry: '1' }),
    /installer selection requires retries: 0/,
  );
  assert.equal(resolveRunPlan({ tier: 'A', diagnosticRetry: '1' }).retries.seeded, 1);
});

test('install flags are strict instead of truthy strings', () => {
  for (const value of [undefined, null, '', '0']) {
    assert.equal(normalizeInstallFlag(value), false, String(value));
  }

  assert.equal(normalizeInstallFlag('1'), true);

  for (const value of ['true', 'yes', 'install', '2', ' 1 ']) {
    assert.throws(() => normalizeInstallFlag(value), /SP_UI_INSTALL must be 0 or 1/);
  }
});

test('the install flag is validated even when the tier is INSTALL', () => {
  for (const value of ['true', 'yes', 'install', '2', ' 1 ']) {
    assert.throws(
      () => resolveRunPlan({ tier: 'INSTALL', install: value }),
      /SP_UI_INSTALL must be 0 or 1/,
      `tier INSTALL must not bypass validation of SP_UI_INSTALL=${JSON.stringify(value)}`,
    );
  }

  for (const value of ['', '0']) {
    assert.equal(resolveRunPlan({ tier: 'INSTALL', install: value }).installSelected, true);
  }

  assert.equal(resolveRunPlan({ tier: 'INSTALL', install: '1' }).installSelected, true);
});

test('the installer retry guard is enforced from the resolved plan', () => {
  assert.doesNotThrow(() => assertInstallerRetries('INSTALL', 0));
  assert.doesNotThrow(() => assertInstallerRetries('a', 1));
  assert.throws(
    () => assertInstallerRetries('INSTALL', 1),
    /installer selection requires retries: 0/,
  );
  assert.throws(
    () => assertInstallerRetries('install', 2),
    /installer selection requires retries: 0/,
  );
});

test('SP_UI_SUMMARY_DIR overrides the install root for an installer-only selection', () => {
  assert.deepEqual(
    resolveSummaryRoots({
      tier: 'INSTALL',
      summaryDir: '/tmp/custom-install',
      installSummaryDir: '/tmp/other-install',
      defaultSummaryRoot: '/summaries/tier-install',
      defaultInstallSummaryRoot: '/summaries/tier-install',
    }),
    { summaryRoot: '/tmp/custom-install', installSummaryRoot: '/tmp/custom-install' },
  );

  assert.deepEqual(
    resolveSummaryRoots({
      tier: 'INSTALL',
      summaryDir: '',
      installSummaryDir: '/tmp/install-evidence',
      defaultSummaryRoot: '/summaries/tier-install',
      defaultInstallSummaryRoot: '/summaries/tier-install',
    }),
    { summaryRoot: '/tmp/install-evidence', installSummaryRoot: '/tmp/install-evidence' },
  );
});

test('the seeded and installer summary roots keep their own overrides in a combined run', () => {
  assert.deepEqual(
    resolveSummaryRoots({
      tier: 'A',
      summaryDir: '/tmp/seeded-evidence',
      installSummaryDir: '/tmp/install-evidence',
      defaultSummaryRoot: '/summaries/tier-A',
      defaultInstallSummaryRoot: '/summaries/tier-install',
    }),
    { summaryRoot: '/tmp/seeded-evidence', installSummaryRoot: '/tmp/install-evidence' },
  );

  assert.deepEqual(
    resolveSummaryRoots({
      tier: 'B',
      summaryDir: '',
      installSummaryDir: '',
      defaultSummaryRoot: '/summaries/tier-B',
      defaultInstallSummaryRoot: '/summaries/tier-install',
    }),
    { summaryRoot: '/summaries/tier-B', installSummaryRoot: '/summaries/tier-install' },
  );
});

test('a seeded tier never reads or writes the installer root by default', () => {
  const roots = resolveSummaryRoots({
    tier: 'A',
    defaultSummaryRoot: '/summaries/tier-A',
    defaultInstallSummaryRoot: '/summaries/tier-install',
  });

  assert.notEqual(roots.summaryRoot, roots.installSummaryRoot);
});

test('the runner CLI applies SP_UI_SUMMARY_DIR to the install root', () => {
  const result = spawnSync(
    process.execPath,
    [
      CLI,
      '--tier',
      'INSTALL',
      '--install',
      '0',
      '--summary-dir',
      '/tmp/install-override',
      '--default-summary-root',
      '/summaries/tier-install',
      '--default-install-summary-root',
      '/summaries/tier-install',
    ],
    { encoding: 'utf8' },
  );

  assert.equal(result.status, 0, result.stderr);
  assert.deepEqual(JSON.parse(result.stdout), {
    tier: 'INSTALL',
    seededTier: null,
    installSelected: true,
    phases: ['install'],
    retries: { seeded: 0, install: 0 },
    summaryRoot: '/tmp/install-override',
    installSummaryRoot: '/tmp/install-override',
  });
});
