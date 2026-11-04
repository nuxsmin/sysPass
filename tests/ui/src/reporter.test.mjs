import assert from 'node:assert/strict';
import { mkdtempSync, readFileSync, readdirSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import test from 'node:test';

import SanitizedArtifactReporter from './reporter.mjs';

function fakeTest(id, title, file = '/repo/tests/ui/specs/login.spec.ts') {
  return {
    id,
    title,
    location: { file, line: 1, column: 1 },
    parent: { project: () => ({ name: 'chromium' }) },
    expectedStatus: 'passed',
  };
}

function fakeSuite(tests) {
  return { allTests: () => tests };
}

function result(status, { retry = 0, error, duration = 10 } = {}) {
  return {
    status,
    retry,
    duration,
    error: error ? { message: error, stack: `Error: ${error}` } : undefined,
    errors: error ? [{ message: error, stack: `Error: ${error}` }] : [],
  };
}

function readOutput(outputDir, name) {
  return readFileSync(path.join(outputDir, name), 'utf8');
}

test('the reporter writes summary, JUnit and an allowlisted manifest for a flaky retry', () => {
  const outputDir = mkdtempSync(path.join(tmpdir(), 'sp-ui-reporter-'));
  const login = fakeTest('login', 'logs in');
  const shell = fakeTest('shell', 'loads shell', '/repo/tests/ui/specs/shell.spec.ts');
  const reporter = new SanitizedArtifactReporter({ outputDir, runId: 'run-1' });

  reporter.onBegin({ rootDir: '/repo', grep: null }, fakeSuite([login, shell]));
  reporter.onTestEnd(login, result('failed', { error: 'console error: password=CANARY_REPORTER' }));
  reporter.onTestEnd(login, result('passed', { retry: 1 }));
  reporter.onTestEnd(shell, result('passed'));
  reporter.onEnd({ status: 'passed', duration: 1234 });

  const summary = JSON.parse(readOutput(outputDir, 'run-summary.json'));
  const junit = readOutput(outputDir, 'junit.xml');
  const manifest = JSON.parse(readOutput(outputDir, 'upload-manifest.json'));
  const files = readdirSync(outputDir).sort();

  assert.equal(summary.status, 'flaky');
  assert.equal(summary.completeTierA, false);
  assert.equal(summary.counts.retries, 1);
  assert.equal(summary.counts.firstAttemptFailed, 1);
  assert.equal(summary.failureClasses.console, 1);
  assert.doesNotMatch(JSON.stringify(summary), /CANARY_REPORTER/);
  assert.doesNotMatch(junit, /CANARY_REPORTER/);
  assert.match(junit, /sp-ui.status" value="flaky"/);
  assert.deepEqual(files, ['junit.xml', 'run-summary.json', 'upload-manifest.json']);
  assert.deepEqual(
    manifest.files.map((file) => file.path).sort(),
    ['junit.xml', 'run-summary.json'],
  );
});

test('the reporter never writes a green summary or JUnit for a failed run with only passing tests', () => {
  const outputDir = mkdtempSync(path.join(tmpdir(), 'sp-ui-reporter-run-failed-'));
  const login = fakeTest('login', 'logs in');
  const shell = fakeTest('shell', 'loads shell', '/repo/tests/ui/specs/shell.spec.ts');
  const reporter = new SanitizedArtifactReporter({ outputDir, runId: 'run-failed' });

  reporter.onBegin({ rootDir: '/repo', grep: null }, fakeSuite([login, shell]));
  reporter.onTestEnd(login, result('passed'));
  reporter.onTestEnd(shell, result('passed'));
  reporter.onEnd({ status: 'failed', duration: 99 });

  const summary = JSON.parse(readOutput(outputDir, 'run-summary.json'));
  const junit = readOutput(outputDir, 'junit.xml');

  assert.equal(summary.resultStatus, 'failed');
  assert.notEqual(summary.status, 'passed');
  assert.equal(summary.completeTierA, false);
  assert.match(junit, /sp-ui.status" value="failed"/);
  assert.match(junit, /<error type="failed"/);
  assert.doesNotMatch(junit, /sp-ui.status" value="passed"/);
});

test('the reporter marks a setup failure that never completes Tier A', () => {
  const outputDir = mkdtempSync(path.join(tmpdir(), 'sp-ui-reporter-setup-'));
  const reporter = new SanitizedArtifactReporter({ outputDir, runId: 'run-2' });

  reporter.onError(new Error('setup failed password=CANARY_SETUP'));
  reporter.onEnd({ status: 'failed', duration: 5 });

  const summary = JSON.parse(readOutput(outputDir, 'run-summary.json'));
  const junit = readOutput(outputDir, 'junit.xml');

  assert.equal(summary.status, 'setup-failed');
  assert.equal(summary.setupFailure, true);
  assert.equal(summary.completeTierA, false);
  assert.doesNotMatch(JSON.stringify(summary), /CANARY_SETUP/);
  assert.doesNotMatch(junit, /CANARY_SETUP/);
  assert.match(junit, /sp-ui.setupFailure" value="true"/);
});

test('the reporter records cancellation without claiming success', () => {
  const outputDir = mkdtempSync(path.join(tmpdir(), 'sp-ui-reporter-cancel-'));
  const login = fakeTest('login', 'logs in');
  const reporter = new SanitizedArtifactReporter({ outputDir, runId: 'run-3' });

  reporter.onBegin({ rootDir: '/repo', grep: null }, fakeSuite([login]));
  reporter.onTestEnd(login, result('interrupted'));
  reporter.onEnd({ status: 'interrupted', duration: 50 });

  const summary = JSON.parse(readOutput(outputDir, 'run-summary.json'));

  assert.equal(summary.status, 'cancelled');
  assert.equal(summary.cancelled, true);
  assert.equal(summary.completeTierA, false);
});
test('an empty Playwright grep does not mark a complete Tier A run as partial', () => {
  const outputDir = mkdtempSync(path.join(tmpdir(), 'sp-ui-reporter-grep-'));
  const login = fakeTest('login', 'logs in');
  const shell = fakeTest('shell', 'loads shell', '/repo/tests/ui/specs/shell.spec.ts');
  const reporter = new SanitizedArtifactReporter({ outputDir, runId: 'run-4' });

  reporter.onBegin({ rootDir: '/repo', grep: [/(?:)/] }, fakeSuite([login, shell]));
  reporter.onTestEnd(login, result('passed'));
  reporter.onTestEnd(shell, result('passed'));
  reporter.onEnd({ status: 'passed', duration: 10 });

  const summary = JSON.parse(readOutput(outputDir, 'run-summary.json'));

  assert.equal(summary.status, 'passed');
  assert.equal(summary.partialSelection, false);
  assert.equal(summary.completeTierA, true);
  assert.equal(summary.environment.playwright, '1.63.0');
});
