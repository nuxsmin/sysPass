import assert from 'node:assert/strict';
import test from 'node:test';

import {
  buildFailureRecord,
  shouldCaptureFailure,
} from './failure-evidence.mjs';

const CANARY = 'CANARY_EVIDENCE_SECRET_88fa';

test('failure evidence captures sanitized error, console, request and source information', () => {
  const record = buildFailureRecord({
    runId: 'sp-ui-smoke.test-20261104T000000Z',
    test: {
      title: 'login',
      file: 'specs/login.spec.ts',
      line: 12,
      project: 'chromium',
    },
    attempt: 0,
    status: 'failed',
    error: {
      message: `password=${CANARY}`,
      stack: `Error: password=${CANARY}\n    at http://127.0.0.1:54321/js/app.js?token=${CANARY}:4:9`,
    },
    pageUrl: `http://127.0.0.1:54321/index.php?r=login/index&token=${CANARY}`,
    requests: [
      {
        route: 'account/index',
        method: 'GET',
        url: `http://127.0.0.1:54321/index.php?r=account/index&token=${CANARY}`,
        status: 500,
      },
      {
        route: null,
        method: 'GET',
        url: `http://127.0.0.1:54321/js/app.js?token=${CANARY}`,
        status: 200,
      },
    ],
    consoleEntries: [
      { type: 'error', text: `token=${CANARY}`, locationUrl: `http://x/app.js?token=${CANARY}` },
      { type: 'log', text: 'safe message', locationUrl: '' },
    ],
    pageErrors: [
      {
        message: `password=${CANARY}`,
        stack: `Error: password=${CANARY}`,
        source: `http://127.0.0.1:54321/js/app.js?token=${CANARY}:2:3`,
        pageUrl: `http://127.0.0.1:54321/index.php?r=index&token=${CANARY}`,
      },
    ],
    screenshot: 'screenshot.png',
  });

  const serialized = JSON.stringify(record);
  assert.doesNotMatch(serialized, new RegExp(CANARY));
  assert.equal(record.test.title.includes('login'), true);
  assert.equal(record.attempt, 1);
  assert.equal(record.firstAttempt, true);
  assert.equal(record.failureClass, 'http');
  assert.deepEqual(record.failingRequest, {
    route: 'account/index',
    method: 'GET',
    status: 500,
    url: 'http://127.0.0.1:54321/index.php?r=account/index&token=[REDACTED]',
    error: undefined,
  });
  assert.equal(record.requests.length, 1);
  assert.equal(record.console.length, 2);
  assert.equal(record.pageErrors[0].source.includes('app.js:2:3'), true);
  assert.equal('dom' in record, false);
  assert.equal('html' in record, false);
  assert.equal(record.screenshot, 'screenshot.png');
});

test('only unexpected non-skipped outcomes are captured', () => {
  assert.equal(shouldCaptureFailure({ status: 'failed', expectedStatus: 'passed' }), true);
  assert.equal(shouldCaptureFailure({ status: 'timedOut', expectedStatus: 'passed' }), true);
  assert.equal(shouldCaptureFailure({ status: 'passed', expectedStatus: 'passed' }), false);
  assert.equal(shouldCaptureFailure({ status: 'skipped', expectedStatus: 'skipped' }), false);
  assert.equal(shouldCaptureFailure({ status: 'failed', expectedStatus: 'failed' }), false);
});

test('a failure without a request or console output still produces a safe record', () => {
  const record = buildFailureRecord({
    runId: 'run',
    test: { title: 'shell', file: 'specs/shell.spec.ts', line: 1, project: 'chromium' },
    attempt: 1,
    status: 'timedOut',
    error: { message: 'Test timeout of 30000ms exceeded', stack: '' },
    pageUrl: '',
    requests: [],
    consoleEntries: [],
    pageErrors: [],
    screenshot: 'screenshot.png',
  });

  assert.equal(record.failureClass, 'timeout');
  assert.equal(record.attempt, 2);
  assert.equal(record.firstAttempt, false);
  assert.equal(record.failingRequest, null);
  assert.deepEqual(record.requests, []);
  assert.deepEqual(record.console, []);
});

test('failure evidence never persists an absolute user path', () => {
  const absolutePath = '/home/CANARY_USER/worktrees/spry-beacon/tests/ui/specs/login.spec.ts';
  const record = buildFailureRecord({
    runId: 'run',
    test: { title: 'login', file: absolutePath, line: 4, project: 'chromium' },
    attempt: 0,
    status: 'failed',
    error: { message: 'boom', stack: '' },
  });

  assert.equal(record.test.file, 'tests/ui/specs/login.spec.ts');
  assert.doesNotMatch(JSON.stringify(record), /CANARY_USER/);
  assert.doesNotMatch(record.test.file, /^\//);

  const opaque = buildFailureRecord({
    runId: 'run',
    test: { title: 'login', file: '/home/CANARY_USER/private/secrets.ts', line: 4, project: 'chromium' },
    attempt: 0,
    status: 'failed',
    error: { message: 'boom', stack: '' },
  });

  assert.equal(opaque.test.file, 'secrets.ts');
  assert.doesNotMatch(JSON.stringify(opaque), /CANARY_USER/);
});
