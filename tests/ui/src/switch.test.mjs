import assert from 'node:assert/strict';
import {
  chmodSync,
  existsSync,
  mkdtempSync,
  readFileSync,
  readdirSync,
  writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { findRunSummaries } from './ci-report.mjs';

const UI_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const REPO_ROOT = path.resolve(UI_DIR, '..', '..');
const RUNNER = path.join(REPO_ROOT, 'scripts', 'ui-smoke.sh');
const CI_REPORT = path.join(UI_DIR, 'src', 'ci-report.mjs');

const RUNNER_OUTPUT_ENV = [
  'SP_UI_OUTPUT_DIR',
  'SP_UI_SUMMARY_DIR',
  'SP_UI_RUN_ID',
  'SP_UI_RUN_ROOT',
  'SP_UI_SETUP_ERROR',
  'SP_UI_TIER',
  'SP_UI_SPECS',
  'SP_UI_DIAGNOSTIC_RETRY',
  'SP_UI_LOCAL_TRACE',
  'E2E_BASE_URL',
];

function dockerShim(binDir, body) {
  const docker = path.join(binDir, 'docker');

  writeFileSync(docker, `#!/usr/bin/env bash\n${body}\n`);
  chmodSync(docker, 0o755);

  return docker;
}

function runRunner({
  enabled,
  docker,
  pathPrefix,
  summaryDir = mkdtempSync(path.join(tmpdir(), 'sp-ui-switch-summary-')),
}) {
  const env = {
    ...process.env,
    DOCKER_BIN: docker,
    TMPDIR: mkdtempSync(path.join(tmpdir(), 'sp-ui-switch-tmp-')),
    CI: 'true',
  };

  if (pathPrefix) {
    env.PATH = `${pathPrefix}:${process.env.PATH}`;
  }

  for (const name of RUNNER_OUTPUT_ENV) {
    delete env[name];
  }

  env.SP_UI_SUMMARY_DIR = summaryDir;

  if (enabled === undefined) {
    delete env.TESTS_UI_ENABLED;
  } else {
    env.TESTS_UI_ENABLED = enabled;
  }

  return spawnSync('bash', [RUNNER], {
    cwd: REPO_ROOT,
    encoding: 'utf8',
    env,
  });
}

test('the execution switch skips without provisioning when unset or not exactly true', () => {
  const bin = mkdtempSync(path.join(tmpdir(), 'sp-ui-switch-bin-'));
  const marker = path.join(bin, 'docker-called');
  const docker = dockerShim(bin, `touch '${marker}'\nexit 0`);

  for (const enabled of [undefined, '', 'false', 'TRUE', '1']) {
    const result = runRunner({ enabled, docker });

    assert.equal(result.status, 0, `switch value ${String(enabled)} must skip cleanly: ${result.stderr}`);
    assert.match(result.stdout, /UI smoke skipped: TESTS_UI_ENABLED is not 'true'/);
    assert.doesNotMatch(result.stdout, /UI smoke run passed/);
    assert.doesNotMatch(result.stdout, /Running the UI harness unit tests/);
    assert.equal(existsSync(marker), false, 'a skipped run must not probe Docker');
  }

  assert.deepEqual(readdirSync(path.dirname(marker)), ['docker']);
});

test('the execution switch proceeds to prerequisites when exactly true', () => {
  const bin = mkdtempSync(path.join(tmpdir(), 'sp-ui-switch-enabled-bin-'));
  const marker = path.join(bin, 'docker-called');
  const docker = dockerShim(
    bin,
    `touch '${marker}'\necho "ERROR: PHP image not found" >&2\nexit 1`,
  );
  const result = runRunner({ enabled: 'true', docker });

  assert.notEqual(result.status, 0, 'the enabled path must reach the image prerequisite');
  assert.match(result.stderr, /PHP image not found/);
  assert.doesNotMatch(result.stdout, /UI smoke skipped/);
  assert.equal(existsSync(marker), true, 'the enabled path must probe Docker');
});

test('the enabled runner resolves the Docker command from PATH', () => {
  const bin = mkdtempSync(path.join(tmpdir(), 'sp-ui-switch-path-bin-'));
  const marker = path.join(bin, 'docker-called');
  dockerShim(
    bin,
    `touch '${marker}'\necho "ERROR: PHP image not found" >&2\nexit 1`,
  );

  const result = runRunner({ enabled: 'true', docker: 'docker', pathPrefix: bin });

  assert.notEqual(result.status, 0, 'the enabled path must reach the image prerequisite');
  assert.doesNotMatch(result.stderr, /Docker wrapper not found/);
  assert.match(result.stderr, /PHP image not found/);
  assert.equal(existsSync(marker), true, 'the Docker command from PATH must be invoked');
});

test('an inherited summary dir cannot capture runner evidence and ci-report sees exactly one summary', () => {
  const bin = mkdtempSync(path.join(tmpdir(), 'sp-ui-switch-inherited-bin-'));
  const inherited = mkdtempSync(path.join(tmpdir(), 'sp-ui-switch-inherited-'));
  const isolated = mkdtempSync(path.join(tmpdir(), 'sp-ui-switch-isolated-'));
  const dump = mkdtempSync(path.join(tmpdir(), 'sp-ui-switch-env-'));
  const docker = dockerShim(
    bin,
    `mkdir -p '${dump}'
printf '%s' "\${SP_UI_SUMMARY_DIR:-}" > '${dump}/summary-dir'
printf '%s' "\${SP_UI_RUN_ID:-}" > '${dump}/run-id'
echo "ERROR: PHP image not found" >&2
exit 1`,
  );
  const previousSummaryDir = process.env.SP_UI_SUMMARY_DIR;
  const previousRunId = process.env.SP_UI_RUN_ID;

  process.env.SP_UI_SUMMARY_DIR = inherited;
  process.env.SP_UI_RUN_ID = 'inherited-run-id-canary';

  try {
    const result = runRunner({ enabled: 'true', docker, summaryDir: isolated });

    assert.notEqual(result.status, 0, 'the enabled path must reach the image prerequisite');
    assert.match(result.stderr, /PHP image not found/);
  } finally {
    if (previousSummaryDir === undefined) {
      delete process.env.SP_UI_SUMMARY_DIR;
    } else {
      process.env.SP_UI_SUMMARY_DIR = previousSummaryDir;
    }

    if (previousRunId === undefined) {
      delete process.env.SP_UI_RUN_ID;
    } else {
      process.env.SP_UI_RUN_ID = previousRunId;
    }
  }

  assert.equal(
    readFileSync(path.join(dump, 'summary-dir'), 'utf8'),
    isolated,
    'the child runner must use the isolated summary dir instead of the inherited one',
  );
  assert.equal(
    readFileSync(path.join(dump, 'run-id'), 'utf8'),
    '',
    'the test invocation must strip the inherited SP_UI_RUN_ID from the child environment',
  );
  assert.deepEqual(
    readdirSync(inherited),
    [],
    'the runner must ignore an inherited SP_UI_SUMMARY_DIR',
  );

  const summaries = findRunSummaries(isolated);
  assert.equal(summaries.length, 1, `expected one isolated summary, found: ${summaries.join(', ')}`);

  const report = spawnSync(process.execPath, [CI_REPORT, '--summaries', isolated], {
    encoding: 'utf8',
    env: { ...process.env, GITHUB_STEP_SUMMARY: '' },
  });

  assert.notEqual(report.status, 0, 'a setup-failed summary must not be accepted as complete Tier A');
  assert.doesNotMatch(report.stderr, /found \d+/, 'ci-report must see exactly one summary');
  assert.match(report.stdout, /setup-failed/);
});
