import assert from 'node:assert/strict';
import { existsSync, mkdirSync, mkdtempSync, readFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const UI_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const REPO_ROOT = path.resolve(UI_DIR, '..', '..');
const LIB = path.join(REPO_ROOT, 'scripts', 'lib', 'ui-smoke-report.sh');

function runBash(script, args = []) {
  return spawnSync('bash', ['-c', script, 'bash', LIB, ...args], {
    cwd: REPO_ROOT,
    encoding: 'utf8',
    env: { ...process.env, GITHUB_STEP_SUMMARY: '' },
  });
}

function fingerprint(root, digest) {
  return JSON.stringify({ root, digest, count: 1 });
}

function runRoot() {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-run-'));

  mkdirSync(path.join(root, 'output'), { recursive: true });
  mkdirSync(path.join(root, 'output-install'), { recursive: true });

  return root;
}

test('fingerprint comparison names every changed path and returns non-zero', () => {
  const same = fingerprint('/repo/app/config', 'aaa');

  const unchanged = runBash(
    'source "$1"; ui_smoke_compare_fingerprints "$2" "$3" app/config',
    [same, same],
  );

  assert.equal(unchanged.status, 0, unchanged.stderr);
  assert.equal(unchanged.stdout, '');

  const changed = runBash(
    'source "$1"; ui_smoke_compare_fingerprints "$2" "$3" app/config "$4" "$5" app/cache "$6" "$7" app/temp',
    [
      same,
      same,
      fingerprint('/repo/app/cache', 'before-cache'),
      fingerprint('/repo/app/cache', 'after-cache'),
      fingerprint('/repo/app/temp', 'before-temp'),
      fingerprint('/repo/app/temp', 'after-temp'),
    ],
  );

  assert.equal(changed.status, 1);
  assert.match(changed.stdout, /^app\/cache$/m);
  assert.match(changed.stdout, /^app\/temp$/m);
  assert.doesNotMatch(changed.stdout, /app\/config/);
});

test('a failed seeded run still compares fingerprints and surfaces drift', () => {
  const run = runRoot();
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-summaries-'));
  const runId = 'run-drift-seeded';

  const result = runBash('source "$1"; shift; ui_smoke_finalize_run "$@"', [
    '1',
    '0',
    '0',
    'A',
    run,
    summaries,
    mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-install-')),
    UI_DIR,
    runId,
    '2026-11-06T00:00:00Z',
    fingerprint('/repo/app/config', 'config'),
    fingerprint('/repo/app/config', 'config'),
    fingerprint('/repo/app/cache', 'before-cache'),
    fingerprint('/repo/app/cache', 'after-cache'),
    fingerprint('/repo/app/temp', 'temp'),
    fingerprint('/repo/app/temp', 'temp'),
  ]);

  assert.equal(result.status, 1, 'a failed selection must stay non-zero');
  assert.match(result.stderr, /developer paths changed during the run: app\/cache/);

  const summaryPath = path.join(summaries, runId, 'run-summary.json');

  assert.equal(existsSync(summaryPath), true, 'drift must produce forced evidence');
  const summary = JSON.parse(readFileSync(summaryPath, 'utf8'));

  assert.equal(summary.status, 'setup-failed');
  assert.equal(summary.setupFailure, true);
  assert.equal(summary.completeTierA, false);
  assert.match(JSON.stringify(summary), /app\/cache/);
});

test('a failed installer-only run still compares fingerprints and surfaces drift', () => {
  const run = runRoot();
  const installSummaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-install-summaries-'));
  const runId = 'run-drift-install';

  const result = runBash('source "$1"; shift; ui_smoke_finalize_run "$@"', [
    '0',
    '1',
    '1',
    '',
    run,
    mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-seeded-')),
    installSummaries,
    UI_DIR,
    runId,
    '2026-11-06T00:00:00Z',
    fingerprint('/repo/app/config', 'before-config'),
    fingerprint('/repo/app/config', 'after-config'),
    fingerprint('/repo/app/cache', 'cache'),
    fingerprint('/repo/app/cache', 'cache'),
    fingerprint('/repo/app/temp', 'temp'),
    fingerprint('/repo/app/temp', 'temp'),
  ]);

  assert.equal(result.status, 1, 'a failed installer selection must stay non-zero');
  assert.match(result.stderr, /developer paths changed during the run: app\/config/);

  const summaryPath = path.join(installSummaries, runId, 'run-summary.json');

  assert.equal(existsSync(summaryPath), true, 'drift must produce forced installer evidence');
  const summary = JSON.parse(readFileSync(summaryPath, 'utf8'));

  assert.equal(summary.status, 'setup-failed');
  assert.equal(summary.completeTierInstall, false);
  assert.equal(summary.completeTierA, false);
});

test('an unchanged failed run keeps its failure status without inventing drift', () => {
  const run = runRoot();
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-clean-summaries-'));
  const runId = 'run-clean-failure';

  const result = runBash('source "$1"; shift; ui_smoke_finalize_run "$@"', [
    '1',
    '0',
    '0',
    'A',
    run,
    summaries,
    mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-clean-install-')),
    UI_DIR,
    runId,
    '2026-11-06T00:00:00Z',
    fingerprint('/repo/app/config', 'config'),
    fingerprint('/repo/app/config', 'config'),
    fingerprint('/repo/app/cache', 'cache'),
    fingerprint('/repo/app/cache', 'cache'),
    fingerprint('/repo/app/temp', 'temp'),
    fingerprint('/repo/app/temp', 'temp'),
  ]);

  assert.equal(result.status, 1);
  assert.match(result.stderr, /Seeded UI smoke selection failed with status 1/);
  assert.doesNotMatch(result.stderr, /developer paths changed/);
  assert.equal(existsSync(path.join(summaries, runId, 'run-summary.json')), false);
});

const EARLY_FAILURE_PATHS = [
  {
    name: 'seeded finalize',
    seededStatus: '1',
    installStatus: '0',
    installSelected: '0',
    seededTier: 'A',
    seededSetupError: 'run artifacts were not retained and verified',
    installSetupError: '',
    drift: 'app/config',
  },
  {
    name: 'installer pre-check',
    seededStatus: '0',
    installStatus: '1',
    installSelected: '1',
    seededTier: '',
    seededSetupError: '',
    installSetupError: 'installer paths or database are not isolated from the seeded instance',
    drift: 'app/cache',
  },
  {
    name: 'installer finalize',
    seededStatus: '0',
    installStatus: '1',
    installSelected: '1',
    seededTier: '',
    seededSetupError: '',
    installSetupError: 'installer artifacts were not retained and verified',
    drift: 'app/temp',
  },
  {
    name: 'installer isolation check',
    seededStatus: '0',
    installStatus: '1',
    installSelected: '1',
    seededTier: '',
    seededSetupError: '',
    installSetupError: 'the installer selection changed the seeded instance',
    drift: 'app/config',
  },
];

for (const earlyPath of EARLY_FAILURE_PATHS) {
  test(`after-fingerprints run for the ${earlyPath.name} failure and reveal drift`, () => {
    const run = runRoot();
    const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-early-summaries-'));
    const installSummaries = mkdtempSync(
      path.join(tmpdir(), 'sp-ui-finalize-early-install-summaries-'),
    );
    const runId = `run-early-${earlyPath.name.replaceAll(' ', '-')}`;
    const changed = new Set([earlyPath.drift]);

    const result = runBash('source "$1"; shift; ui_smoke_finalize_run "$@"', [
      earlyPath.seededStatus,
      earlyPath.installStatus,
      earlyPath.installSelected,
      earlyPath.seededTier,
      run,
      summaries,
      installSummaries,
      UI_DIR,
      runId,
      '2026-11-06T00:00:00Z',
      fingerprint('/repo/app/config', changed.has('app/config') ? 'before-config' : 'config'),
      fingerprint('/repo/app/config', changed.has('app/config') ? 'after-config' : 'config'),
      fingerprint('/repo/app/cache', changed.has('app/cache') ? 'before-cache' : 'cache'),
      fingerprint('/repo/app/cache', changed.has('app/cache') ? 'after-cache' : 'cache'),
      fingerprint('/repo/app/temp', changed.has('app/temp') ? 'before-temp' : 'temp'),
      fingerprint('/repo/app/temp', changed.has('app/temp') ? 'after-temp' : 'temp'),
      earlyPath.seededSetupError,
      earlyPath.installSetupError,
    ]);

    assert.equal(result.status, 1, `${earlyPath.name} must stay non-zero`);
    assert.match(
      result.stderr,
      new RegExp(`developer paths changed during the run: ${earlyPath.drift.replace('/', '\\/')}`),
    );

    if (earlyPath.seededTier) {
      const seededSummary = JSON.parse(
        readFileSync(path.join(summaries, runId, 'run-summary.json'), 'utf8'),
      );

      assert.equal(seededSummary.status, 'setup-failed');
      assert.match(JSON.stringify(seededSummary), new RegExp(earlyPath.drift.replace('/', '\\/')));
    }

    if (earlyPath.installSelected === '1') {
      const installSummary = JSON.parse(
        readFileSync(path.join(installSummaries, runId, 'run-summary.json'), 'utf8'),
      );

      assert.equal(installSummary.status, 'setup-failed');
      assert.equal(installSummary.completeTierInstall, false);
      assert.match(JSON.stringify(installSummary), new RegExp(earlyPath.drift.replace('/', '\\/')));
    }
  });
}

test('a seeded finalize failure forces setup-failed evidence without fingerprint drift', () => {
  const run = runRoot();
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-seeded-setup-'));
  const runId = 'run-seeded-finalize-setup';

  const result = runBash('source "$1"; shift; ui_smoke_finalize_run "$@"', [
    '1',
    '0',
    '0',
    'A',
    run,
    summaries,
    mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-seeded-setup-install-')),
    UI_DIR,
    runId,
    '2026-11-06T00:00:00Z',
    fingerprint('/repo/app/config', 'config'),
    fingerprint('/repo/app/config', 'config'),
    fingerprint('/repo/app/cache', 'cache'),
    fingerprint('/repo/app/cache', 'cache'),
    fingerprint('/repo/app/temp', 'temp'),
    fingerprint('/repo/app/temp', 'temp'),
    'run artifacts were not retained and verified',
    '',
  ]);

  assert.equal(result.status, 1);
  assert.doesNotMatch(result.stderr, /developer paths changed/);

  const summary = JSON.parse(readFileSync(path.join(summaries, runId, 'run-summary.json'), 'utf8'));

  assert.equal(summary.status, 'setup-failed');
  assert.match(JSON.stringify(summary), /run artifacts were not retained and verified/);
});

test('the runner routes every post-provisioning failure through one finalizer', () => {
  const runner = readFileSync(path.join(REPO_ROOT, 'scripts', 'ui-smoke.sh'), 'utf8');
  const installFail = runner.match(/install_fail\(\) \{([\s\S]*?)\n\}/);

  assert.ok(installFail, 'install_fail must exist');
  assert.doesNotMatch(installFail[1], /exit/, 'install_fail must not exit before finalization');
  assert.doesNotMatch(runner, /ui_smoke_install_fail/, 'the immediate-exit helper must be gone');
  assert.match(
    runner,
    /SEEDED_SETUP_ERROR="run artifacts were not retained and verified"/,
    'a seeded finalize failure must accumulate instead of exiting',
  );
  assert.match(
    runner,
    /install_fail "installer artifacts were not retained and verified"/,
    'an installer finalize failure must accumulate instead of exiting',
  );
  assert.match(
    runner,
    /install_fail "installer paths or database are not isolated from the seeded instance"/,
    'an installer pre-check failure must accumulate instead of exiting',
  );
  assert.match(
    runner,
    /install_fail "the installer selection changed the seeded instance"/,
    'the isolation failure must accumulate instead of exiting',
  );
  assert.match(runner, /finalize_run\(\) \{[\s\S]*fingerprint "\$REPO_ROOT\/app\/config"/);
  assert.match(runner, /finalize_run \|\| FINAL_STATUS=\$\?/);
});
