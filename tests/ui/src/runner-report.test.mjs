import assert from 'node:assert/strict';
import { chmodSync, existsSync, mkdirSync, mkdtempSync, readFileSync, readdirSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { findRunSummaries } from './ci-report.mjs';

const UI_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const REPO_ROOT = path.resolve(UI_DIR, '..', '..');
const LIB = path.join(REPO_ROOT, 'scripts', 'lib', 'ui-smoke-report.sh');
const CI_REPORT = path.join(UI_DIR, 'src', 'ci-report.mjs');

function runBash(script, args, { env = {} } = {}) {
  return spawnSync('bash', ['-c', script, 'bash', LIB, ...args], {
    cwd: REPO_ROOT,
    encoding: 'utf8',
    env: { ...process.env, ...env },
  });
}

function failingNode(binDir) {
  const node = path.join(binDir, 'node');
  writeFileSync(node, '#!/usr/bin/env bash\nexit 7\n');
  chmodSync(node, 0o755);
}

function partialSetupNode(binDir, realNode) {
  const node = path.join(binDir, 'node');
  const quotedRealNode = realNode.replaceAll("'", "'\\''");

  writeFileSync(
    node,
    `#!/usr/bin/env bash
set -euo pipefail
script=""
output=""
previous=""
for argument in "$@"; do
  case "$argument" in
    */src/setup-report.mjs) script="setup" ;;
  esac
  if [ "$previous" = "--output" ]; then
    output="$argument"
  fi
  previous="$argument"
done
if [ "$script" = "setup" ]; then
  mkdir -p "$output"
  printf '{"schemaVersion":1}\\n' > "$output/run-summary.json"
  exit 0
fi
exec '${quotedRealNode}' "$@"
`,
  );
  chmodSync(node, 0o755);
}

function writeArtifactSet(directory, names = ['run-summary.json', 'junit.xml', 'upload-manifest.json']) {
  mkdirSync(directory, { recursive: true });

  for (const name of names) {
    if (name === 'upload-manifest.json') {
      continue;
    }

    writeFileSync(
      path.join(directory, name),
      name === 'junit.xml' ? '<testsuites/>\n' : '{"schemaVersion":1}\n',
    );
  }

  if (names.includes('upload-manifest.json')) {
    const files = names
      .filter((name) => name !== 'upload-manifest.json')
      .map((name) => ({ path: name, bytes: 1, sha256: '0'.repeat(64) }));

    writeFileSync(
      path.join(directory, 'upload-manifest.json'),
      `${JSON.stringify({ schemaVersion: 1, generatedAt: '2026-11-05T00:00:00.000Z', files }, null, 2)}\n`,
    );
  }
}

function completeRunSummary(runId) {
  return {
    schemaVersion: 1,
    runId,
    status: 'passed',
    completeTierA: true,
    partialSelection: false,
    partialReasons: [],
    setupFailure: false,
    cancelled: false,
    selection: {
      tier: 'A',
      selectedSpecs: ['login.spec.ts', 'shell.spec.ts'],
      explicit: false,
      partialSelection: false,
      partialReasons: [],
    },
    commit: { sha: 'a'.repeat(40), short: 'aaaaaaaaa', dirty: false, branch: 'master' },
    environment: { ci: true, node: 'v20.19.2', platform: 'linux', playwright: '1.63.0', tier: 'A' },
    startedAt: '2026-11-05T00:00:00.000Z',
    finishedAt: '2026-11-05T00:02:00.000Z',
    durationMs: 120_000,
    resultStatus: 'passed',
    counts: {
      selected: 0,
      attempted: 0,
      completed: 0,
      skipped: 0,
      failed: 0,
      timedOut: 0,
      interrupted: 0,
      notRun: 0,
      retries: 0,
      firstAttemptPassed: 0,
      firstAttemptFailed: 0,
      flaky: 0,
    },
    firstAttemptResults: { passed: 0, failed: 0, skipped: 0 },
    failureClasses: {},
    globalErrors: [],
    tests: [],
    artifacts: {
      junit: 'junit.xml',
      summary: 'run-summary.json',
      uploadManifest: 'upload-manifest.json',
      failureDirectories: [],
    },
  };
}

test('the runner reports success only after retaining all three allowlisted artifacts', () => {
  const output = mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-complete-'));
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-complete-summaries-'));
  writeArtifactSet(output);

  const result = runBash(
    'source "$1"; ui_smoke_finalize_report "$2" "$3" "$4" run-complete',
    [output, summaries, UI_DIR],
  );

  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /Retained sanitized summary/);
  assert.equal(existsSync(path.join(summaries, 'run-complete', 'run-summary.json')), true);
  assert.equal(existsSync(path.join(summaries, 'run-complete', 'junit.xml')), true);
  assert.equal(existsSync(path.join(summaries, 'run-complete', 'upload-manifest.json')), true);
});

test('a passing run with a retention failure exits non-zero and does not report success', () => {
  const output = mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-fail-'));
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-fail-summaries-'));
  const bin = mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-fail-bin-'));
  writeArtifactSet(output);
  failingNode(bin);

  const result = runBash(
    'source "$1"; ui_smoke_finalize_report "$2" "$3" "$4" run-retain-failed',
    [output, summaries, UI_DIR],
    { env: { PATH: `${bin}:${process.env.PATH}` } },
  );

  assert.notEqual(result.status, 0, 'a failed retention step must fail the run');
  assert.doesNotMatch(result.stdout, /Retained sanitized summary/);
  assert.match(result.stderr, /retain/i);
  assert.equal(existsSync(path.join(summaries, 'run-retain-failed', 'run-summary.json')), false);
});

test('a partial retained artifact set exits non-zero', () => {
  const output = mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-partial-'));
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-finalize-partial-summaries-'));
  writeArtifactSet(output, ['run-summary.json', 'upload-manifest.json']);

  const result = runBash(
    'source "$1"; ui_smoke_finalize_report "$2" "$3" "$4" run-partial',
    [output, summaries, UI_DIR],
  );

  assert.notEqual(result.status, 0, 'a missing junit.xml must fail the run');
  assert.doesNotMatch(result.stdout, /Retained sanitized summary/);
  assert.match(result.stderr, /junit\.xml/);
});

test('the setup report cannot claim success for a partial retained artifact set', () => {
  const output = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-partial-'));
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-partial-summaries-'));
  writeArtifactSet(output, ['run-summary.json']);
  writeArtifactSet(path.join(summaries, 'run-partial'), ['run-summary.json']);

  const result = runBash(
    'set -euo pipefail; source "$1"; ui_smoke_setup_report 1 "$2" "$2" "$3" "$4" run-partial 2026-11-04T00:00:00Z',
    [output, summaries, UI_DIR],
  );

  assert.notEqual(result.status, 0, 'a partial retained set must not be reported as retained');
  assert.doesNotMatch(result.stdout, /Retained sanitized/);
  assert.match(result.stderr, /ERROR: failed to retain sanitized summary/);
});

test('a partial retained set prints the intended error and removes its temporary output', () => {
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-temp-summaries-'));
  const bin = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-temp-bin-'));
  const temporaryRoot = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-temp-root-'));
  partialSetupNode(bin, process.execPath);

  const result = runBash(
    'set -euo pipefail; source "$1"; ui_smoke_setup_report 1 "$2" "" "$3" "$4" run-partial-temp 2026-11-04T00:00:00Z',
    ['', summaries, UI_DIR],
    { env: { PATH: `${bin}:${process.env.PATH}`, TMPDIR: temporaryRoot } },
  );

  assert.notEqual(result.status, 0, 'a partial retained set must fail the setup report');
  assert.doesNotMatch(result.stdout, /Retained sanitized/);
  assert.match(result.stderr, /ERROR: failed to retain sanitized setup-failure summary/);
  assert.deepEqual(readdirSync(temporaryRoot), [], 'the temporary output directory must be removed');
});

test('the runner helper removes every error-context.md before retention', () => {
  const output = mkdtempSync(path.join(tmpdir(), 'sp-ui-error-context-'));
  mkdirSync(path.join(output, 'failures', 'one'), { recursive: true });
  writeFileSync(path.join(output, 'error-context.md'), 'canary');
  writeFileSync(path.join(output, 'failures', 'one', 'error-context.md'), 'canary');
  writeFileSync(path.join(output, 'failures', 'one', 'console.log'), 'safe');

  const result = runBash('source "$1"; ui_smoke_remove_error_context "$2"', [output]);

  assert.equal(result.status, 0, result.stderr);
  assert.equal(existsSync(path.join(output, 'error-context.md')), false);
  assert.equal(existsSync(path.join(output, 'failures', 'one', 'error-context.md')), false);
  assert.equal(existsSync(path.join(output, 'failures', 'one', 'console.log')), true);
});

test('the runner fails and does not claim success when the setup report cannot be written', () => {
  const output = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-output-'));
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-summaries-'));
  const bin = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-bin-'));
  failingNode(bin);

  const result = runBash(
    'source "$1"; ui_smoke_setup_report 1 "$2" "" "$3" "$4" run-1 2026-11-04T00:00:00Z',
    [output, summaries, UI_DIR],
    { env: { PATH: `${bin}:${process.env.PATH}` } },
  );

  assert.notEqual(result.status, 0, 'a missing setup-failure report must fail the runner');
  assert.doesNotMatch(result.stdout, /Retained sanitized setup-failure summary/);
  assert.match(result.stderr, /setup-failure summary/);
  assert.deepEqual(readdirSync(summaries), []);
});

test('the runner fails when setup-report exits zero without writing a summary', () => {
  const output = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-empty-'));
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-empty-summaries-'));
  const bin = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-empty-bin-'));
  const node = path.join(bin, 'node');
  writeFileSync(node, '#!/usr/bin/env bash\nexit 0\n');
  chmodSync(node, 0o755);

  const result = runBash(
    'source "$1"; ui_smoke_setup_report 1 "$2" "" "$3" "$4" run-2 2026-11-04T00:00:00Z',
    [output, summaries, UI_DIR],
    { env: { PATH: `${bin}:${process.env.PATH}` } },
  );

  assert.notEqual(result.status, 0, 'an absent setup summary must fail the runner');
  assert.doesNotMatch(result.stdout, /Retained sanitized setup-failure summary/);
  assert.match(result.stderr, /setup-failure summary/);
});

test('the runner retains a real setup-failure summary and only then reports success', () => {
  const output = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-real-'));
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-real-summaries-'));

  const result = runBash(
    'source "$1"; ui_smoke_setup_report 1 "$2" "" "$3" "$4" run-3 2026-11-04T00:00:00Z',
    [output, summaries, UI_DIR],
  );

  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /Retained sanitized setup-failure summary/);
  assert.equal(existsSync(path.join(summaries, 'run-3', 'run-summary.json')), true);
  assert.equal(existsSync(path.join(summaries, 'run-3', 'junit.xml')), true);
  assert.equal(existsSync(path.join(summaries, 'run-3', 'upload-manifest.json')), true);
});

test('a successful run needs no setup report', () => {
  const output = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-none-'));
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-none-summaries-'));

  const result = runBash(
    'source "$1"; ui_smoke_setup_report 0 "$2" "" "$3" "$4" run-4 2026-11-04T00:00:00Z',
    [output, summaries, UI_DIR],
  );

  assert.equal(result.status, 0, result.stderr);
  assert.equal(result.stdout, '');
  assert.deepEqual(readdirSync(summaries), []);
});

test('a pre-run CI failure emits sanitized setup-failed evidence that ci-report rejects', () => {
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-setup-failure-'));

  const result = runBash(
    'source "$1"; ui_smoke_ci_setup_report "$2" "$3" run-pre-run 2026-11-05T00:00:00Z',
    [summaries, UI_DIR],
    { env: { SP_UI_SETUP_ERROR: 'pre-run setup failed; password=CANARY_PRE_RUN_SECRET' } },
  );

  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /Retained sanitized setup-failure summary/);

  for (const artifact of ['run-summary.json', 'junit.xml', 'upload-manifest.json']) {
    assert.equal(existsSync(path.join(summaries, 'run-pre-run', artifact)), true, artifact);
  }

  const summary = JSON.parse(readFileSync(path.join(summaries, 'run-pre-run', 'run-summary.json'), 'utf8'));
  const junit = readFileSync(path.join(summaries, 'run-pre-run', 'junit.xml'), 'utf8');
  const manifest = readFileSync(path.join(summaries, 'run-pre-run', 'upload-manifest.json'), 'utf8');

  assert.equal(summary.status, 'setup-failed');
  assert.equal(summary.completeTierA, false);
  assert.doesNotMatch(JSON.stringify(summary), /CANARY_PRE_RUN_SECRET/);
  assert.doesNotMatch(junit, /CANARY_PRE_RUN_SECRET/);
  assert.doesNotMatch(manifest, /CANARY_PRE_RUN_SECRET/);

  const report = spawnSync(process.execPath, [CI_REPORT, '--summaries', summaries], {
    encoding: 'utf8',
    env: { ...process.env, GITHUB_STEP_SUMMARY: '' },
  });

  assert.notEqual(report.status, 0, 'setup-failed evidence must not pass ci-report');
  assert.match(report.stderr, /setup-failed/);
});

test('the pre-run CI report does not add a second summary when the runner already retained one', () => {
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-setup-existing-'));
  const runDir = path.join(summaries, 'existing-run');
  writeArtifactSet(runDir);

  const summaryBefore = readFileSync(path.join(runDir, 'run-summary.json'), 'utf8');

  const result = runBash(
    'source "$1"; ui_smoke_ci_setup_report "$2" "$3" run-pre-run 2026-11-05T00:00:00Z',
    [summaries, UI_DIR],
  );

  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /already exists/);
  assert.equal(findRunSummaries(summaries).length, 1);
  assert.equal(
    readFileSync(path.join(runDir, 'run-summary.json'), 'utf8'),
    summaryBefore,
    'a complete retained set must be left untouched',
  );
});

test('the CI setup report repairs a partial retained set that still has the authoritative summary', () => {
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-setup-repair-'));
  const runDir = path.join(summaries, 'run-partial-summary');

  mkdirSync(runDir, { recursive: true });
  writeFileSync(
    path.join(runDir, 'run-summary.json'),
    `${JSON.stringify(completeRunSummary('run-partial-summary'), null, 2)}\n`,
  );

  const result = runBash(
    'source "$1"; ui_smoke_ci_setup_report "$2" "$3" run-partial-summary 2026-11-05T00:00:00Z',
    [summaries, UI_DIR],
  );

  assert.equal(result.status, 0, result.stderr);
  for (const artifact of ['run-summary.json', 'junit.xml', 'upload-manifest.json']) {
    assert.equal(existsSync(path.join(runDir, artifact)), true, artifact);
  }

  const summary = JSON.parse(readFileSync(path.join(runDir, 'run-summary.json'), 'utf8'));
  assert.equal(summary.status, 'passed', 'repair must not rewrite the authoritative summary');
  assert.equal(summary.completeTierA, true);
  assert.equal(findRunSummaries(summaries).length, 1);
});

test('the CI setup report fails closed with a non-pass set when only JUnit exists', () => {
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-setup-junit-'));
  const runDir = path.join(summaries, 'run-partial-junit');

  mkdirSync(runDir, { recursive: true });
  writeFileSync(path.join(runDir, 'junit.xml'), '<testsuites/>\n');

  const result = runBash(
    'source "$1"; ui_smoke_ci_setup_report "$2" "$3" run-partial-junit 2026-11-05T00:00:00Z',
    [summaries, UI_DIR],
  );

  assert.equal(result.status, 0, result.stderr);
  for (const artifact of ['run-summary.json', 'junit.xml', 'upload-manifest.json']) {
    assert.equal(existsSync(path.join(runDir, artifact)), true, artifact);
  }

  const summary = JSON.parse(readFileSync(path.join(runDir, 'run-summary.json'), 'utf8'));
  assert.equal(summary.status, 'setup-failed');
  assert.equal(summary.completeTierA, false);
  assert.equal(findRunSummaries(summaries).length, 1, 'the setup report must not create an ambiguous second summary');

  const report = spawnSync(process.execPath, [CI_REPORT, '--summaries', summaries], {
    encoding: 'utf8',
    env: { ...process.env, GITHUB_STEP_SUMMARY: '' },
  });

  assert.notEqual(report.status, 0, 'setup-failed evidence must not pass ci-report');
  assert.match(report.stderr, /setup-failed/);
});

test('the workflow verifier rejects upload-manifest entries outside the explicit allowlist', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-verify-manifest-'));
  const runDir = path.join(root, 'run-1');

  writeArtifactSet(runDir);

  const manifestPath = path.join(runDir, 'upload-manifest.json');
  const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));

  for (const unknown of ['failures/login/trace.zip', 'extra.json']) {
    manifest.files.push({ path: unknown, bytes: 1, sha256: '1'.repeat(64) });
    writeFileSync(manifestPath, `${JSON.stringify(manifest, null, 2)}\n`);

    const result = runBash('source "$1"; ui_smoke_verify_single_summary_root "$2"', [root]);

    assert.notEqual(result.status, 0, `${unknown} must not be publishable`);
    assert.match(result.stderr, /upload allowlist|forbidden|unknown/i);

    manifest.files.pop();
  }
});

test('the workflow verifier requires run-summary.json and junit.xml in the manifest', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-verify-manifest-missing-'));
  const runDir = path.join(root, 'run-1');

  writeArtifactSet(runDir, ['run-summary.json', 'junit.xml']);
  writeFileSync(
    path.join(runDir, 'upload-manifest.json'),
    `${JSON.stringify({ schemaVersion: 1, files: [{ path: 'run-summary.json' }] }, null, 2)}\n`,
  );

  const result = runBash('source "$1"; ui_smoke_verify_single_summary_root "$2"', [root]);

  assert.notEqual(result.status, 0, 'a manifest that omits junit.xml must fail verification');
  assert.match(result.stderr, /junit\.xml|manifest/i);
});

test('the workflow verifier requires exactly one complete retained summary set', () => {
  const complete = mkdtempSync(path.join(tmpdir(), 'sp-ui-verify-complete-'));
  writeArtifactSet(path.join(complete, 'run-1'));

  const passed = runBash('source "$1"; ui_smoke_verify_single_summary_root "$2"', [complete]);
  assert.equal(passed.status, 0, passed.stderr);

  const duplicate = mkdtempSync(path.join(tmpdir(), 'sp-ui-verify-duplicate-'));
  writeArtifactSet(path.join(duplicate, 'run-1'));
  writeArtifactSet(path.join(duplicate, 'run-2'));

  const duplicated = runBash('source "$1"; ui_smoke_verify_single_summary_root "$2"', [duplicate]);
  assert.notEqual(duplicated.status, 0, 'two summaries must fail the upload check');
  assert.match(duplicated.stderr, /exactly one/);

  const incomplete = mkdtempSync(path.join(tmpdir(), 'sp-ui-verify-incomplete-'));
  writeArtifactSet(path.join(incomplete, 'run-1'), ['run-summary.json', 'junit.xml']);

  const missing = runBash('source "$1"; ui_smoke_verify_single_summary_root "$2"', [incomplete]);
  assert.notEqual(missing.status, 0, 'a missing manifest must fail the upload check');
  assert.match(missing.stderr, /upload-manifest\.json/);

  const summaryOnly = mkdtempSync(path.join(tmpdir(), 'sp-ui-verify-summary-only-'));
  writeArtifactSet(path.join(summaryOnly, 'run-1'), ['run-summary.json']);

  const partial = runBash('source "$1"; ui_smoke_verify_single_summary_root "$2"', [summaryOnly]);
  assert.notEqual(partial.status, 0, 'a summary-only set must not be publishable');
  assert.match(partial.stderr, /junit\.xml/);

  const orphan = mkdtempSync(path.join(tmpdir(), 'sp-ui-verify-orphan-'));
  writeArtifactSet(path.join(orphan, 'run-1'));
  mkdirSync(path.join(orphan, 'run-orphan'), { recursive: true });
  writeFileSync(path.join(orphan, 'run-orphan', 'junit.xml'), '<testsuites/>\n');

  const orphaned = runBash('source "$1"; ui_smoke_verify_single_summary_root "$2"', [orphan]);
  assert.notEqual(orphaned.status, 0, 'artifacts outside the single run directory must not be publishable');
  assert.match(orphaned.stderr, /exactly one run directory|all live beside the single run summary/);

  const orphanFailures = mkdtempSync(path.join(tmpdir(), 'sp-ui-verify-orphan-failures-'));
  writeArtifactSet(path.join(orphanFailures, 'run-1'));
  mkdirSync(path.join(orphanFailures, 'run-orphan', 'failures', 'login'), { recursive: true });
  writeFileSync(path.join(orphanFailures, 'run-orphan', 'failures', 'login', 'console.log'), 'safe\n');

  const orphanedFailures = runBash('source "$1"; ui_smoke_verify_single_summary_root "$2"', [orphanFailures]);
  assert.notEqual(orphanedFailures.status, 0, 'failure diagnostics outside the single run directory must not be publishable');
  assert.match(orphanedFailures.stderr, /failure diagnostics outside/);
});

test('an installer isolation failure replaces a passing summary with a failed install result', () => {
  const output = mkdtempSync(path.join(tmpdir(), 'sp-ui-install-isolation-output-'));
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-install-isolation-summaries-'));
  const runId = 'run-install-isolation';
  const runDir = path.join(summaries, runId);

  writeArtifactSet(output);
  mkdirSync(runDir, { recursive: true });

  const passingInstall = {
    ...completeRunSummary(runId),
    completeTierA: false,
    completeTierInstall: true,
    selection: {
      tier: 'INSTALL',
      selectedSpecs: ['install.spec.ts'],
      explicit: false,
      partialSelection: false,
      partialReasons: [],
    },
  };

  writeFileSync(
    path.join(runDir, 'run-summary.json'),
    `${JSON.stringify(passingInstall, null, 2)}\n`,
  );
  writeArtifactSet(runDir);
  writeFileSync(path.join(runDir, 'stale-artifact.txt'), 'must be replaced');

  const result = runBash(
    'source "$1"; SP_UI_TIER=INSTALL ui_smoke_install_failure_report 1 "$2" "$3" "$4" "$5" 2026-11-05T00:00:00Z "the installer selection changed the seeded instance"',
    [output, summaries, UI_DIR, runId],
  );

  assert.notEqual(result.status, 0, 'an isolation failure report must not exit zero');

  const summary = JSON.parse(
    readFileSync(path.join(runDir, 'run-summary.json'), 'utf8'),
  );
  const junit = readFileSync(path.join(runDir, 'junit.xml'), 'utf8');

  assert.equal(summary.status, 'setup-failed');
  assert.equal(summary.setupFailure, true);
  assert.equal(summary.completeTierA, false);
  assert.equal(summary.completeTierInstall, false);
  assert.equal(summary.selection.tier, 'INSTALL');
  assert.match(JSON.stringify(summary), /changed the seeded instance/);
  assert.doesNotMatch(junit, /sp-ui.completeTierInstall" value="true"/);
  assert.equal(
    existsSync(path.join(runDir, 'stale-artifact.txt')),
    false,
    'the forced failure report must replace the whole retained run directory',
  );
});

test('the finalizer replaces the installer summary for an accumulated post-provisioning failure', () => {
  const output = mkdtempSync(path.join(tmpdir(), 'sp-ui-install-fail-output-'));
  const summaries = mkdtempSync(path.join(tmpdir(), 'sp-ui-install-fail-summaries-'));
  const runId = 'run-install-fail';
  const sameFingerprint = JSON.stringify({ root: '/repo/app/config', digest: 'same', count: 1 });

  writeArtifactSet(output);
  writeArtifactSet(path.join(summaries, runId));

  const result = runBash(
    'source "$1"; shift; ui_smoke_finalize_run "$@"',
    [
      '0',
      '1',
      '1',
      '',
      output,
      mkdtempSync(path.join(tmpdir(), 'sp-ui-install-fail-seeded-')),
      summaries,
      UI_DIR,
      runId,
      '2026-11-05T00:00:00Z',
      sameFingerprint,
      sameFingerprint,
      sameFingerprint,
      sameFingerprint,
      sameFingerprint,
      sameFingerprint,
      '',
      'the installer selection changed the seeded instance',
    ],
  );

  assert.equal(result.status, 1, 'an accumulated installer failure must stay non-zero');
  assert.match(result.stderr, /Installer UI smoke selection failed with status 1/);

  const summary = JSON.parse(
    readFileSync(path.join(summaries, runId, 'run-summary.json'), 'utf8'),
  );

  assert.equal(summary.status, 'setup-failed');
  assert.equal(summary.completeTierInstall, false);
  assert.equal(summary.completeTierA, false);
  assert.match(JSON.stringify(summary), /changed the seeded instance/);
});
