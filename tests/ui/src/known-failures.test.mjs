import assert from 'node:assert/strict';
import test from 'node:test';

import {
  ADMISSIBLE_FAILURE_KINDS,
  annotateKnownFailures,
  extractFailureProblems,
  extractFailureRoute,
  parseRegistry,
} from './known-failures.mjs';
import { classifyFailure } from './summary.mjs';

const ADMISSION = {
  who: 'maintainer@example.test',
  date: '2026-11-06',
  ref: '#2080',
};

function entry(overrides = {}) {
  return {
    spec: 'shell.spec.ts',
    route: 'account/index',
    failureKind: 'http',
    reason: 'account/index is deterministically broken',
    defect: '#2000',
    admission: { ...ADMISSION },
    ...overrides,
  };
}

function registry(entries = []) {
  return { schemaVersion: 1, entries };
}

function testResult(overrides = {}) {
  return {
    title: 'loads the shell',
    file: 'shell.spec.ts',
    skipped: false,
    expectedStatus: 'passed',
    firstAttemptStatus: 'failed',
    finalStatus: 'failed',
    retries: 0,
    attempts: [attempt()],
    ...overrides,
  };
}

function attempt(overrides = {}) {
  return {
    attempt: 1,
    retryIndex: 0,
    firstAttempt: true,
    status: 'failed',
    durationMs: 10,
    failureClass: 'http',
    route: 'account/index',
    errorMessage: 'http [account/index]: action account/index returned HTTP 500',
    errorStack: '',
    ...overrides,
  };
}

test('an empty registry validates and produces no annotations', () => {
  const parsed = parseRegistry(registry());
  const result = annotateKnownFailures([], parsed.entries);

  assert.equal(parsed.ok, true);
  assert.deepEqual(parsed.errors, []);
  assert.deepEqual(result.knownFailures, []);
  assert.deepEqual(result.newFailures, []);
  assert.deepEqual(result.staleEntries, []);
});

test('an exact (spec, route, failure kind) admission annotates only that failure', () => {
  const parsed = parseRegistry(registry([entry()]));
  const result = annotateKnownFailures([testResult()], parsed.entries);

  assert.equal(parsed.ok, true);
  assert.equal(result.knownFailures.length, 1);
  assert.equal(result.newFailures.length, 0);
  assert.equal(result.staleEntries.length, 0);
  assert.equal(result.tests[0].attempts[0].knownFailure.reason, entry().reason);
  assert.equal(result.tests[0].knownFailure.defect, '#2000');
  assert.equal(result.tests[0].attempts[0].firstAttempt, true);
  assert.equal(result.tests[0].attempts[0].durationMs, 10);
});

test('an unlisted failure remains a new failure and marks the executed entry stale', () => {
  const parsed = parseRegistry(registry([entry({ route: 'account/view' })]));
  const result = annotateKnownFailures([testResult()], parsed.entries);

  assert.equal(result.knownFailures.length, 0);
  assert.equal(result.newFailures.length, 1);
  assert.equal(result.newFailures[0].failureKind, 'http');
  assert.equal(result.newFailures[0].route, 'account/index');
  assert.equal(result.staleEntries.length, 1);
  assert.match(result.staleEntries[0].removal, /remove/i);
  assert.match(result.staleEntries[0].removal, /known-failures\.json/);
});

test('an unrelated failure in the same spec still fails', () => {
  const parsed = parseRegistry(registry([entry()]));
  const result = annotateKnownFailures(
    [
      testResult(),
      testResult({
        title: 'other shell test',
        firstAttemptStatus: 'failed',
        finalStatus: 'failed',
        attempts: [
          attempt({
            failureClass: 'missing-content',
            route: 'accountFile/list',
            errorMessage:
              'missing-content [accountFile/list]: action accountFile/list response is missing expected content "file"',
          }),
        ],
      }),
    ],
    parsed.entries,
  );

  assert.equal(result.knownFailures.length, 1);
  assert.equal(result.newFailures.length, 1);
  assert.equal(result.newFailures[0].route, 'accountFile/list');
  assert.equal(result.newFailures[0].failureKind, 'missing-content');
  assert.equal(result.staleEntries.length, 0);
});

test('an entry whose spec executed without the failure fails with removal instructions', () => {
  const parsed = parseRegistry(registry([entry()]));
  const result = annotateKnownFailures(
    [
      testResult({
        firstAttemptStatus: 'passed',
        finalStatus: 'passed',
        attempts: [attempt({ status: 'passed', failureClass: null, route: null, errorMessage: '' })],
      }),
    ],
    parsed.entries,
  );

  assert.equal(result.knownFailures.length, 0);
  assert.equal(result.newFailures.length, 0);
  assert.equal(result.staleEntries.length, 1);
  assert.match(result.staleEntries[0].removal, /no longer occurs/i);
  assert.match(result.staleEntries[0].removal, /remove/i);
});

test('filtered, skipped and unexecuted specs do not imply the defect was fixed', () => {
  const parsed = parseRegistry(registry([entry()]));
  const skipped = annotateKnownFailures(
    [
      testResult({
        skipped: true,
        firstAttemptStatus: 'skipped',
        finalStatus: 'skipped',
        attempts: [],
      }),
    ],
    parsed.entries,
  );
  const filtered = annotateKnownFailures([], parsed.entries);
  const notRun = annotateKnownFailures(
    [testResult({ firstAttemptStatus: 'not-run', finalStatus: 'not-run', attempts: [] })],
    parsed.entries,
  );

  for (const result of [skipped, filtered, notRun]) {
    assert.equal(result.staleEntries.length, 0);
    assert.equal(result.unexecutedEntries.length, 1);
    assert.match(result.unexecutedEntries[0].message, /not treated as fixed/i);
  }
});

test('a flaky first-attempt failure is never admitted as a known failure', () => {
  const parsed = parseRegistry(registry([entry()]));
  const result = annotateKnownFailures(
    [
      testResult({
        firstAttemptStatus: 'failed',
        finalStatus: 'passed',
        retries: 1,
        attempts: [attempt(), attempt({ attempt: 2, retryIndex: 1, firstAttempt: false, status: 'passed', failureClass: null, route: null, errorMessage: '' })],
      }),
    ],
    parsed.entries,
  );

  assert.equal(result.knownFailures.length, 0);
  assert.equal(result.newFailures.length, 1);
  assert.equal(result.staleEntries.length, 0);
});

test('missing admission metadata is rejected', () => {
  for (const overrides of [
    { reason: undefined },
    { defect: undefined },
    { admission: undefined },
    { admission: { who: '', date: '2026-11-06', ref: '#1' } },
    { admission: { who: 'someone', date: '', ref: '#1' } },
    { admission: { who: 'someone', date: '2026-02-30', ref: '#1' } },
    { admission: { who: 'someone', date: '2026-11-06', ref: '' } },
  ]) {
    const parsed = parseRegistry(registry([entry(overrides)]));

    assert.equal(parsed.ok, false, JSON.stringify(overrides));
    assert.ok(parsed.errors.length > 0, JSON.stringify(overrides));
  }
});

test('regex and glob keys are rejected', () => {
  for (const overrides of [
    { spec: '/.*\\.spec\\.ts/' },
    { spec: '*.spec.ts' },
    { route: '/account\\/.*/' },
    { route: 'account/.*' },
    { failureKind: '^http$' },
    { failureKind: 'http|console' },
  ]) {
    const parsed = parseRegistry(registry([entry(overrides)]));

    assert.equal(parsed.ok, false, JSON.stringify(overrides));
    assert.match(parsed.errors.join('\n'), /exact|regular expression|literal/i);
  }
});

test('unknown and non-deterministic failure kinds are rejected', () => {
  for (const failureKind of [
    'flaky',
    'setup',
    'setup-failure',
    'setup/isolation',
    'isolation',
    'session-loss',
    'login-navigation',
    'invalid-response',
    'not-a-kind',
  ]) {
    const parsed = parseRegistry(registry([entry({ failureKind })]));

    assert.equal(parsed.ok, false, failureKind);
    assert.match(parsed.errors.join('\n'), /failure kind/i);
  }
});

test('unknown fields and duplicate keys are rejected', () => {
  const unknown = parseRegistry(registry([entry({ extra: true })]));
  const duplicate = parseRegistry(registry([entry(), entry()]));
  const badTopLevel = parseRegistry({ schemaVersion: 1, entries: [], extra: true });

  assert.equal(unknown.ok, false);
  assert.match(unknown.errors.join('\n'), /unknown field/i);
  assert.equal(duplicate.ok, false);
  assert.match(duplicate.errors.join('\n'), /duplicate/i);
  assert.equal(badTopLevel.ok, false);
  assert.match(badTopLevel.errors.join('\n'), /unknown field/i);
});

test('failure routes are extracted only from the detector prefix', () => {
  assert.equal(
    extractFailureRoute('http [account/index]: action account/index returned HTTP 500'),
    'account/index',
  );
  assert.equal(
    extractFailureRoute(
      'missing-content [accountFile/list]: action accountFile/list response is missing expected content "x"',
    ),
    'accountFile/list',
  );
  assert.equal(extractFailureRoute('expect(received).toBe(expected)'), null);
});

const MULTI_PROBLEM_MESSAGE = [
  'readiness failed',
  'detector problems: missing-content [accountFile/list]: action accountFile/list response is missing expected content "file"',
  '| http [account/index]: action account/index returned HTTP 500',
].join('; ');

function multiProblemTest(overrides = {}) {
  return testResult({
    file: 'shell.spec.ts',
    attempts: [
      attempt({
        failureClass: 'http',
        route: 'accountFile/list',
        errorMessage: MULTI_PROBLEM_MESSAGE,
      }),
    ],
    ...overrides,
  });
}

test('every detector problem is extracted as its own route and failure kind', () => {
  assert.deepEqual(extractFailureProblems(MULTI_PROBLEM_MESSAGE), [
    { route: 'accountFile/list', failureKind: 'missing-content' },
    { route: 'account/index', failureKind: 'http' },
  ]);
  assert.deepEqual(extractFailureProblems('console: console error: boom (source: app.js)'), [
    { route: null, failureKind: 'console' },
  ]);
  assert.deepEqual(extractFailureProblems('expect(received).toBe(expected)'), []);
});

test('a multi-problem attempt is admitted only when every problem is listed', () => {
  const allEntries = parseRegistry(
    registry([
      entry({ route: 'accountFile/list', failureKind: 'missing-content' }),
      entry({ route: 'account/index', failureKind: 'http' }),
    ]),
  ).entries;
  const partialEntries = parseRegistry(
    registry([entry({ route: 'accountFile/list', failureKind: 'missing-content' })]),
  ).entries;

  const all = annotateKnownFailures([multiProblemTest()], allEntries);
  const partial = annotateKnownFailures([multiProblemTest()], partialEntries);

  assert.equal(all.knownFailures.length, 2);
  assert.equal(all.newFailures.length, 0);
  assert.deepEqual(
    all.tests[0].attempts[0].failureProblems.map((problem) => [
      problem.route,
      problem.failureKind,
    ]),
    [
      ['accountFile/list', 'missing-content'],
      ['account/index', 'http'],
    ],
  );

  assert.equal(partial.knownFailures.length, 1);
  assert.equal(partial.newFailures.length, 1);
  assert.equal(partial.newFailures[0].route, 'account/index');
  assert.equal(partial.newFailures[0].failureKind, 'http');
  assert.equal(partial.tests[0].attempts[0].firstAttempt, true);
});

test('the classifier route must not be paired with a different problem prefix', () => {
  const parsed = parseRegistry(
    registry([entry({ route: 'accountFile/list', failureKind: 'http' })]),
  );
  const result = annotateKnownFailures([multiProblemTest()], parsed.entries);

  assert.equal(result.knownFailures.length, 0, 'missing-content must not be treated as http');
  assert.equal(result.newFailures.length, 2);
  assert.deepEqual(
    result.newFailures.map((failure) => [failure.route, failure.failureKind]),
    [
      ['accountFile/list', 'missing-content'],
      ['account/index', 'http'],
    ],
  );
});

test('only route-bearing deterministic kinds are admissible', () => {
  const samples = {
    http: 'http [account/index]: action account/index returned HTTP 500',
    'json-status': 'json-status [account/index]: action account/index returned JSON status ERROR',
    'request-failed': 'request-failed [account/index]: action account/index failed',
    'body-inspection': 'body-inspection [account/index]: action body could not be inspected',
    'missing-content': 'missing-content [accountFile/list]: response is missing expected content "file"',
    'empty-body': 'empty-body [account/index]: action returned an empty HTTP 200 body',
  };

  assert.deepEqual([...ADMISSIBLE_FAILURE_KINDS].sort(), Object.keys(samples).sort());

  for (const kind of ADMISSIBLE_FAILURE_KINDS) {
    const parsed = parseRegistry(registry([entry({ failureKind: kind })]));

    assert.equal(classifyFailure(samples[kind]), kind, `${kind} must be reachable by the classifier`);
    assert.equal(parsed.ok, true, `${kind} must be admissible: ${parsed.errors.join('; ')}`);
  }

  for (const kind of [
    'json-malformed',
    'json-envelope',
    'console',
    'page-error',
    'markup',
    'assertion',
    'timeout',
  ]) {
    const parsed = parseRegistry(registry([entry({ failureKind: kind })]));

    assert.equal(ADMISSIBLE_FAILURE_KINDS.includes(kind), false, kind);
    assert.equal(parsed.ok, false, `${kind} must not be admissible`);
  }
});

test('defect and admission ref must link an issue, not any non-empty string', () => {
  for (const overrides of [
    { defect: 'not-an-issue' },
    { defect: '2079' },
    { defect: '#2079' },
    { defect: 'https://github.com/nuxsmin/sysPass/issues/2079' },
    { admission: { who: 'maintainer', date: '2026-11-06', ref: 'not-an-issue' } },
  ]) {
    const parsed = parseRegistry(registry([entry(overrides)]));

    assert.equal(parsed.ok, false, JSON.stringify(overrides));
    assert.match(parsed.errors.join('\n'), /issue|#2079/i, JSON.stringify(overrides));
  }

  for (const defect of ['#2000', 'https://github.com/nuxsmin/sysPass/issues/2000']) {
    const parsed = parseRegistry(registry([entry({ defect })]));

    assert.equal(parsed.ok, true, `${defect} must be accepted: ${parsed.errors.join('; ')}`);
  }
});
