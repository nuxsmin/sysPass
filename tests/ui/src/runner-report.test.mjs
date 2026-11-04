import assert from 'node:assert/strict';
import { chmodSync, existsSync, mkdirSync, mkdtempSync, readdirSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const UI_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const REPO_ROOT = path.resolve(UI_DIR, '..', '..');
const LIB = path.join(REPO_ROOT, 'scripts', 'lib', 'ui-smoke-report.sh');

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
    writeFileSync(path.join(directory, name), '{"schemaVersion":1}\n');
  }
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
