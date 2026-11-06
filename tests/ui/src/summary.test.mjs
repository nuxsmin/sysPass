import assert from 'node:assert/strict';
import test from 'node:test';

import {
  TIER_A_SPECS,
  TIER_B_SPECS,
  buildRunSummary,
  classifyFailure,
  renderJUnit,
  resolveSelection,
  summarizeTest,
} from './summary.mjs';

function testRun({ title, file = 'login.spec.ts', statuses, expectedStatus = 'passed', errorMessage }) {
  const attempts = statuses.map((status, index) => ({
    status,
    durationMs: 10 * (index + 1),
    retryIndex: index,
    error:
      status === 'passed'
        ? undefined
        : {
            message:
              errorMessage ?? (index === 0 ? 'console error: password=CANARY_SECRET' : 'second failure'),
          },
  }));
  const finalStatus = statuses.at(-1);

  return summarizeTest({
    title,
    file,
    project: 'chromium',
    expectedStatus,
    skipped: finalStatus === 'skipped',
    attempts,
  });
}

const COMMIT = { sha: 'f3d488dfb', short: 'f3d488dfb', dirty: false, branch: 'main' };
const ENVIRONMENT = {
  ci: false,
  node: 'v20.19.2',
  platform: 'linux',
  playwright: '1.63.0',
  tier: 'A',
};

function build(tests, overrides = {}) {
  return buildRunSummary({
    runId: 'sp-ui-smoke.test-20261104T000000Z',
    selection: resolveSelection({
      tier: 'A',
      specs: '',
      grep: false,
      availableSpecs: TIER_A_SPECS,
    }),
    commit: COMMIT,
    environment: ENVIRONMENT,
    tests,
    startedAt: '2026-11-04T00:00:00.000Z',
    finishedAt: '2026-11-04T00:01:00.000Z',
    durationMs: 60_000,
    resultStatus: 'passed',
    cancelled: false,
    setupFailure: false,
    globalErrors: [],
    ...overrides,
  });
}

test('a complete Tier A run with first-attempt passes is a complete success', () => {
  const summary = build([
    testRun({ title: 'login', statuses: ['passed'] }),
    testRun({ title: 'shell', file: 'shell.spec.ts', statuses: ['passed'] }),
  ]);

  assert.equal(summary.status, 'passed');
  assert.equal(summary.completeTierA, true);
  assert.equal(summary.partialSelection, false);
  assert.equal(summary.counts.selected, 2);
  assert.equal(summary.counts.attempted, 2);
  assert.equal(summary.counts.completed, 2);
  assert.equal(summary.counts.skipped, 0);
  assert.equal(summary.counts.firstAttemptPassed, 2);
  assert.equal(summary.counts.retries, 0);
  assert.deepEqual(summary.failureClasses, {});
});

test('explicit partial selection cannot appear as complete Tier A success', () => {
  const selection = resolveSelection({
    tier: 'A',
    specs: 'login.spec.ts',
    grep: false,
    availableSpecs: TIER_A_SPECS,
  });
  const summary = build([testRun({ title: 'login', statuses: ['passed'] })], { selection });

  assert.equal(selection.partialSelection, true);
  assert.match(selection.partialReasons.join('\n'), /subset/);
  assert.equal(summary.status, 'partial');
  assert.equal(summary.completeTierA, false);
});

test('a grep-filtered run and a non-Tier-A selection are partial', () => {
  const grepSelection = resolveSelection({
    tier: 'A',
    specs: '',
    grep: true,
    availableSpecs: TIER_A_SPECS,
  });
  assert.equal(grepSelection.partialSelection, true);

  const tierB = resolveSelection({
    tier: 'B',
    specs: '',
    grep: false,
    availableSpecs: TIER_A_SPECS,
  });
  assert.equal(tierB.partialSelection, true);
  assert.match(tierB.partialReasons.join('\n'), /tier/);
});

test('skipped tests prevent a complete Tier A success and are counted', () => {
  const summary = build([
    testRun({ title: 'login', statuses: ['passed'] }),
    testRun({ title: 'shell', file: 'shell.spec.ts', statuses: ['skipped'] }),
  ]);

  assert.equal(summary.counts.skipped, 1);
  assert.equal(summary.counts.completed, 1);
  assert.equal(summary.completeTierA, false);
  assert.notEqual(summary.status, 'passed');
});

test('a first-attempt failure followed by a diagnostic retry is flaky, not a Tier A success', () => {
  const summary = build([
    testRun({ title: 'login', statuses: ['failed', 'passed'] }),
    testRun({ title: 'shell', file: 'shell.spec.ts', statuses: ['passed'] }),
  ]);

  assert.equal(summary.status, 'flaky');
  assert.equal(summary.completeTierA, false);
  assert.equal(summary.counts.retries, 1);
  assert.equal(summary.counts.firstAttemptFailed, 1);
  assert.equal(summary.counts.flaky, 1);
  assert.equal(summary.failureClasses.console, 1);
  assert.equal(summary.tests[0]?.firstAttemptStatus, 'failed');
  assert.equal(summary.tests[0]?.finalStatus, 'passed');
  assert.match(summary.tests[0]?.attempts[0]?.errorMessage ?? '', /\[REDACTED\]/);
  assert.doesNotMatch(summary.tests[0]?.attempts[0]?.errorMessage ?? '', /CANARY_SECRET/);
});

test('setup failure and cancellation are never complete success', () => {
  const setup = build([], {
    setupFailure: true,
    globalErrors: [{ message: 'config failed password=CANARY_SECRET' }],
  });
  assert.equal(setup.status, 'setup-failed');
  assert.equal(setup.completeTierA, false);
  assert.doesNotMatch(JSON.stringify(setup), /CANARY_SECRET/);

  const cancelled = build([testRun({ title: 'login', statuses: ['interrupted'] })], {
    resultStatus: 'interrupted',
    cancelled: true,
  });
  assert.equal(cancelled.status, 'cancelled');
  assert.equal(cancelled.completeTierA, false);
});

test('summaries and JUnit never expose an absolute user path', () => {
  const summary = build([
    testRun({
      title: 'login',
      file: '/home/CANARY_USER/worktrees/spry-beacon/tests/ui/specs/login.spec.ts',
      statuses: ['failed'],
    }),
    testRun({ title: 'shell', file: 'shell.spec.ts', statuses: ['passed'] }),
  ]);
  const xml = renderJUnit(summary);

  assert.equal(summary.tests[0].file, 'tests/ui/specs/login.spec.ts');
  assert.doesNotMatch(JSON.stringify(summary), /CANARY_USER/);
  assert.doesNotMatch(xml, /CANARY_USER/);
  assert.doesNotMatch(xml, /\/home\//);
});

test('classifyFailure maps detector and runner failures to stable classes', () => {
  assert.equal(classifyFailure('Test timeout of 30000ms exceeded'), 'timeout');
  assert.equal(classifyFailure('console error: boom (source: app.js)'), 'console');
  assert.equal(classifyFailure('page-error: boom (source: app.js)'), 'page-error');
  assert.equal(classifyFailure('http [account/index]: action returned HTTP 500'), 'http');
  assert.equal(classifyFailure('expect(received).toBe(expected)'), 'assertion');
  assert.equal(classifyFailure('something else'), 'test-error');
});

test('a multi-problem detector message records every route and kind without losing the classifier', () => {
  const summary = summarizeTest({
    title: 'loads the shell',
    file: '/repo/tests/ui/specs/shell.spec.ts',
    attempts: [
      {
        status: 'failed',
        durationMs: 10,
        error: {
          message: [
            'readiness failed',
            'detector problems: missing-content [accountFile/list]: response is missing expected content "file"',
            '| http [account/index]: action account/index returned HTTP 500',
          ].join('; '),
        },
      },
    ],
  });

  assert.equal(summary.attempts[0].failureClass, 'http');
  assert.equal(summary.attempts[0].route, 'accountFile/list');
  assert.deepEqual(
    summary.attempts[0].failureProblems.map((problem) => [problem.route, problem.failureKind]),
    [
      ['accountFile/list', 'missing-content'],
      ['account/index', 'http'],
    ],
  );
  assert.equal(summary.attempts[0].firstAttempt, true);
});

test('a partially admitted multi-problem attempt keeps one known and one new failure', () => {
  const summary = build(
    [
      summarizeTest({
        title: 'loads the shell',
        file: 'login.spec.ts',
        attempts: [
          {
            status: 'failed',
            durationMs: 10,
            error: {
              message: [
                'readiness failed',
                'detector problems: missing-content [accountFile/list]: response is missing expected content "file"',
                '| http [account/index]: action account/index returned HTTP 500',
              ].join('; '),
            },
          },
        ],
      }),
    ],
    {
      knownFailureRegistry: knownRegistry([
        knownEntry({ route: 'accountFile/list', failureKind: 'missing-content' }),
      ]),
    },
  );

  assert.equal(summary.knownFailures.length, 1);
  assert.equal(summary.newFailures.length, 1);
  assert.equal(summary.newFailures[0].route, 'account/index');
  assert.equal(summary.newFailures[0].failureKind, 'http');
  assert.equal(summary.counts.knownFailed, 1);
  assert.equal(summary.counts.newFailed, 1);
  assert.equal(summary.status, 'failed');
  assert.equal(summary.completeTierA, false);
});

test('JUnit contains selected tier, counts, first attempts, retries, durations and failure classes', () => {
  const summary = build([
    testRun({ title: 'login & shell', statuses: ['failed', 'passed'] }),
    testRun({ title: 'skipped test', file: 'shell.spec.ts', statuses: ['skipped'] }),
  ]);
  const xml = renderJUnit(summary);

  assert.match(xml, /^<\?xml version="1.0" encoding="UTF-8"\?>/);
  assert.match(xml, /<testsuites[^>]*tests="2"[^>]*failures="1"[^>]*skipped="1"/);
  assert.match(xml, /<property name="sp-ui.tier" value="A"\/>/);
  assert.match(xml, /<property name="sp-ui.completeTierA" value="false"\/>/);
  assert.match(xml, /<property name="sp-ui.status" value="flaky"\/>/);
  assert.match(xml, /<property name="sp-ui.counts.attempted" value="2"\/>/);
  assert.match(xml, /<property name="sp-ui.counts.completed" value="1"\/>/);
  assert.match(xml, /<property name="sp-ui.failureClasses" value="console:1"\/>/);
  assert.match(xml, /<testcase[^>]*name="login &amp; shell"[^>]*retries="1"[^>]*firstAttempt="failed"/);
  assert.match(xml, /<failure type="flaky"/);
  assert.match(xml, /<skipped\/>/);
  assert.doesNotMatch(xml, /CANARY_SECRET/);
});

test('passing first-attempt runs retain a safe summary and JUnit without failure diagnostics', () => {
  const summary = build([
    testRun({ title: 'login', statuses: ['passed'] }),
    testRun({ title: 'shell', file: 'shell.spec.ts', statuses: ['passed'] }),
  ]);
  summary.artifacts = { failureDirectories: [] };

  assert.deepEqual(summary.artifacts.failureDirectories, []);
  assert.equal(summary.failureClasses && Object.keys(summary.failureClasses).length, 0);
  const xml = renderJUnit(summary);
  assert.match(xml, /<testsuites[^>]*tests="2"[^>]*failures="0"[^>]*skipped="0"[^>]*errors="0"/);
  assert.doesNotMatch(xml, /<failure\b/);
  assert.doesNotMatch(xml, /<error\b/);
});

test('JUnit makes every non-green run outcome visible to XML consumers', () => {
  const failed = build([testRun({ title: 'login', statuses: ['failed'] })]);
  const failedXml = renderJUnit(failed);
  assert.match(failedXml, /<testsuites[^>]*tests="1"[^>]*failures="1"[^>]*errors="0"/);
  assert.match(failedXml, /<failure type="console"/);
  assert.match(failedXml, /sp-ui.status" value="failed"/);

  const timedOut = build([
    testRun({ title: 'login', statuses: ['timedOut'], errorMessage: 'Test timeout of 30000ms exceeded' }),
  ]);
  const timedOutXml = renderJUnit(timedOut);
  assert.equal(timedOut.counts.timedOut, 1);
  assert.match(timedOutXml, /<testsuites[^>]*failures="1"[^>]*errors="0"/);
  assert.match(timedOutXml, /<failure type="timeout"/);
  assert.match(timedOutXml, /sp-ui.status" value="failed"/);
  assert.match(timedOutXml, /sp-ui.counts.timedOut" value="1"/);

  const interrupted = build([testRun({ title: 'login', statuses: ['interrupted'] })], {
    resultStatus: 'interrupted',
    cancelled: true,
  });
  const interruptedXml = renderJUnit(interrupted);
  assert.equal(interrupted.status, 'cancelled');
  assert.match(interruptedXml, /<testsuites[^>]*failures="0"[^>]*errors="1"/);
  assert.match(interruptedXml, /<error type="interrupted"/);
  assert.match(interruptedXml, /sp-ui.status" value="cancelled"/);
  assert.doesNotMatch(interruptedXml, /<testcase[^>]*status="interrupted"[^>]*\/>/);

  const flaky = build([
    testRun({ title: 'login', statuses: ['failed', 'passed'] }),
    testRun({ title: 'shell', file: 'shell.spec.ts', statuses: ['passed'] }),
  ]);
  const flakyXml = renderJUnit(flaky);
  assert.equal(flaky.status, 'flaky');
  assert.match(flakyXml, /<testsuites[^>]*failures="1"[^>]*errors="0"/);
  assert.match(flakyXml, /<failure type="flaky"/);
  assert.match(flakyXml, /sp-ui.status" value="flaky"/);
});

test('a non-passed run result with only passing tests is never a complete Tier A success', () => {
  for (const resultStatus of [
    'failed',
    'timedout',
    'interrupted',
    'setup-failed',
    'partial',
    'flaky',
    'unknown',
  ]) {
    const summary = build(
      [
        testRun({ title: 'login', statuses: ['passed'] }),
        testRun({ title: 'shell', file: 'shell.spec.ts', statuses: ['passed'] }),
      ],
      { resultStatus },
    );
    const xml = renderJUnit(summary);

    assert.notEqual(summary.status, 'passed', `resultStatus ${resultStatus} must not summarize as passed`);
    assert.equal(summary.resultStatus, resultStatus);
    assert.equal(summary.completeTierA, false);
    assert.match(
      xml,
      new RegExp(`sp-ui.status" value="${summary.status}"`),
      `resultStatus ${resultStatus} must be visible in JUnit`,
    );
    assert.doesNotMatch(xml, /sp-ui.completeTierA" value="true"/);
    assert.match(xml, /<error type="(?:cancelled|failed|flaky|partial|setup-failed|timedout)"/);
    assert.doesNotMatch(
      xml,
      /sp-ui.status" value="passed"/,
      `resultStatus ${resultStatus} must not emit a passing sp-ui.status property`,
    );
  }
});

test('JUnit marks setup failures and partial selections with a suite-level error', () => {
  const setup = build([], {
    setupFailure: true,
    globalErrors: [{ message: 'config failed password=CANARY_SECRET' }],
  });
  const setupXml = renderJUnit(setup);
  assert.equal(setup.status, 'setup-failed');
  assert.match(setupXml, /<testsuites[^>]*tests="1"[^>]*failures="0"[^>]*errors="1"/);
  assert.match(setupXml, /<error type="setup-failed"/);
  assert.match(setupXml, /sp-ui.setupFailure" value="true"/);
  assert.doesNotMatch(setupXml, /CANARY_SECRET/);

  const selection = resolveSelection({
    tier: 'A',
    specs: 'login.spec.ts',
    grep: false,
    availableSpecs: TIER_A_SPECS,
  });
  const partial = build([testRun({ title: 'login', statuses: ['passed'] })], { selection });
  const partialXml = renderJUnit(partial);
  assert.equal(partial.status, 'partial');
  assert.match(partialXml, /<testsuites[^>]*failures="0"[^>]*errors="1"/);
  assert.match(partialXml, /<error type="partial"/);
  assert.match(partialXml, /sp-ui.partialSelection" value="true"/);
});

function knownEntry(overrides = {}) {
  return {
    spec: 'login.spec.ts',
    route: 'account/index',
    failureKind: 'http',
    reason: 'account/index is deterministically broken',
    defect: '#2000',
    admission: { who: 'maintainer', date: '2026-11-06', ref: '#2080' },
    ...overrides,
  };
}

function knownRegistry(entries = []) {
  return { schemaVersion: 1, entries };
}

test('an empty known-failure registry leaves a complete passing run unchanged', () => {
  const summary = build(
    [
      testRun({ title: 'login', statuses: ['passed'] }),
      testRun({ title: 'shell', file: 'shell.spec.ts', statuses: ['passed'] }),
    ],
    { knownFailureRegistry: knownRegistry() },
  );

  assert.equal(summary.status, 'passed');
  assert.equal(summary.completeTierA, true);
  assert.deepEqual(summary.knownFailures, []);
  assert.deepEqual(summary.newFailures, []);
  assert.deepEqual(summary.staleKnownFailures, []);
});

test('an admitted deterministic failure is annotated without hiding counts or first-attempt evidence', () => {
  const summary = build(
    [
      testRun({
        title: 'login',
        statuses: ['failed'],
        errorMessage: 'http [account/index]: action account/index returned HTTP 500',
      }),
      testRun({ title: 'shell', file: 'shell.spec.ts', statuses: ['passed'] }),
    ],
    { knownFailureRegistry: knownRegistry([knownEntry()]) },
  );

  assert.equal(summary.status, 'failed');
  assert.equal(summary.completeTierA, false);
  assert.equal(summary.counts.failed, 1);
  assert.equal(summary.counts.firstAttemptFailed, 1);
  assert.equal(summary.counts.knownFailed, 1);
  assert.equal(summary.counts.newFailed, 0);
  assert.equal(summary.knownFailures.length, 1);
  assert.equal(summary.knownFailures[0].route, 'account/index');
  assert.equal(summary.knownFailures[0].firstAttempt, true);
  assert.equal(summary.tests[0].attempts[0].firstAttempt, true);
  assert.equal(summary.tests[0].attempts[0].knownFailure.reason, knownEntry().reason);
  assert.equal(summary.tests[0].knownFailure.defect, '#2000');
  assert.equal(summary.failureClasses.http, 1);
});

test('an unlisted or same-spec unrelated failure remains a new failure', () => {
  const summary = build(
    [
      testRun({
        title: 'login',
        statuses: ['failed'],
        errorMessage: 'http [account/index]: action account/index returned HTTP 500',
      }),
      testRun({
        title: 'shell',
        file: 'shell.spec.ts',
        statuses: ['failed'],
        errorMessage: 'missing-content [accountFile/list]: response is missing expected content "file"',
      }),
    ],
    { knownFailureRegistry: knownRegistry([knownEntry()]) },
  );

  assert.equal(summary.knownFailures.length, 1);
  assert.equal(summary.newFailures.length, 1);
  assert.equal(summary.newFailures[0].spec, 'shell.spec.ts');
  assert.equal(summary.newFailures[0].route, 'accountFile/list');
  assert.equal(summary.counts.knownFailed, 1);
  assert.equal(summary.counts.newFailed, 1);
  assert.equal(summary.status, 'failed');
});

test('an obsolete entry fails with removal instructions when its spec executes without it', () => {
  const summary = build(
    [
      testRun({ title: 'login', statuses: ['passed'] }),
      testRun({ title: 'shell', file: 'shell.spec.ts', statuses: ['passed'] }),
    ],
    { knownFailureRegistry: knownRegistry([knownEntry()]) },
  );

  assert.equal(summary.staleKnownFailures.length, 1);
  assert.match(summary.staleKnownFailures[0].removal, /remove/i);
  assert.equal(summary.status, 'failed');
  assert.equal(summary.completeTierA, false);
});

test('skipped and filtered selections do not mark an unexercised entry as fixed', () => {
  for (const [tests, entries] of [
    [
      [
        testRun({ title: 'login', statuses: ['passed'] }),
        testRun({ title: 'shell', file: 'shell.spec.ts', statuses: ['skipped'] }),
      ],
      [knownEntry({ spec: 'shell.spec.ts' })],
    ],
    [
      [testRun({ title: 'login', statuses: ['passed'] })],
      [knownEntry({ spec: 'shell.spec.ts' })],
    ],
  ]) {
    const summary = build(tests, { knownFailureRegistry: knownRegistry(entries) });

    assert.equal(summary.staleKnownFailures.length, 0);
    assert.equal(summary.unexecutedKnownFailures.length, 1);
    assert.match(summary.unexecutedKnownFailures[0].message, /not treated as fixed/i);
  }
});

test('JUnit exposes known, new and stale registry evidence without suppressing failures', () => {
  const known = build(
    [
      testRun({
        title: 'login',
        statuses: ['failed'],
        errorMessage: 'http [account/index]: action account/index returned HTTP 500',
      }),
    ],
    { knownFailureRegistry: knownRegistry([knownEntry()]) },
  );
  const xml = renderJUnit(known);

  assert.match(xml, /sp-ui.knownFailures" value="1"/);
  assert.match(xml, /sp-ui.newFailures" value="0"/);
  assert.match(xml, /sp-ui.counts.failed" value="1"/);
  assert.match(xml, /<failure type="http"/);
  assert.match(xml, /firstAttempt="failed"/);

  const stale = build(
    [
      testRun({ title: 'login', statuses: ['passed'] }),
      testRun({ title: 'shell', file: 'shell.spec.ts', statuses: ['passed'] }),
    ],
    { knownFailureRegistry: knownRegistry([knownEntry()]) },
  );
  const staleXml = renderJUnit(stale);

  assert.match(staleXml, /sp-ui.staleKnownFailures" value="1"/);
  assert.match(staleXml, /<error type="known-failure-stale"/);
  assert.match(staleXml, /remove the stale entry/i);
});

test('a complete Tier B selection is complete for Tier B and never Tier A', () => {
  const selection = resolveSelection({
    tier: 'B',
    specs: '',
    grep: false,
    availableSpecs: TIER_B_SPECS,
  });
  const summary = build(
    TIER_B_SPECS.map((spec) => testRun({ title: spec, file: spec, statuses: ['passed'] })),
    {
      selection,
      environment: { ...ENVIRONMENT, tier: 'B' },
    },
  );

  assert.equal(selection.partialSelection, false);
  assert.equal(selection.selectedSpecs.length, TIER_B_SPECS.length);
  assert.equal(summary.status, 'passed');
  assert.equal(summary.completeTierB, true);
  assert.equal(summary.completeTierA, false);
});

test('an incomplete or failing Tier B selection is never complete Tier B', () => {
  const partialSelection = resolveSelection({
    tier: 'B',
    specs: 'accessManager.spec.ts',
    grep: false,
    availableSpecs: ['accessManager.spec.ts'],
  });
  const partial = build([testRun({ title: 'access', file: 'accessManager.spec.ts', statuses: ['passed'] })], {
    selection: partialSelection,
    environment: { ...ENVIRONMENT, tier: 'B' },
  });

  assert.equal(partialSelection.partialSelection, true);
  assert.equal(partial.status, 'partial');
  assert.equal(partial.completeTierB, false);
  assert.equal(partial.completeTierA, false);

  const failureSelection = resolveSelection({
    tier: 'B',
    specs: '',
    grep: false,
    availableSpecs: TIER_B_SPECS,
  });
  const failed = build(
    TIER_B_SPECS.map((spec, index) =>
      testRun({ title: spec, file: spec, statuses: [index === 0 ? 'failed' : 'passed'] }),
    ),
    {
      selection: failureSelection,
      environment: { ...ENVIRONMENT, tier: 'B' },
    },
  );

  assert.equal(failed.status, 'failed');
  assert.equal(failed.completeTierB, false);
  assert.equal(failed.completeTierA, false);
});
