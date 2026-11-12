import assert from 'node:assert/strict';
import { mkdirSync, mkdtempSync, readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import {
  evaluateRunSummary,
  findRunSummaries,
  renderMarkdown,
  resolveSetupWindow,
  TIER_A_BUDGET_MS,
} from './ci-report.mjs';
import { annotateKnownFailures, parseRegistry } from './known-failures.mjs';

const UI_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const CLI = path.join(UI_DIR, 'src', 'ci-report.mjs');
const RUNNER = path.join(UI_DIR, '..', '..', 'scripts', 'ui-smoke.sh');

function completeSummary(overrides = {}) {
  return {
    schemaVersion: 1,
    runId: 'sp-ui-smoke.test',
    status: 'passed',
    completeTierA: true,
    partialSelection: false,
    commit: { sha: 'a'.repeat(40), short: 'aaaaaaaaa', dirty: false, branch: 'master' },
    durationMs: 120_000,
    counts: { selected: 4, completed: 4, failed: 0, skipped: 0, retries: 0, knownFailed: 0, newFailed: 0 },
    firstAttemptResults: { passed: 4, failed: 0, skipped: 0 },
    globalErrors: [],
    knownFailures: [],
    newFailures: [],
    staleKnownFailures: [],
    unexecutedKnownFailures: [],
    knownFailureErrors: [],
    ...overrides,
  };
}

function completeTierBSummary(overrides = {}) {
  return completeSummary({
    status: 'passed',
    completeTierA: false,
    completeTierB: true,
    selection: {
      tier: 'B',
      selectedSpecs: [
        'accessManager.spec.ts',
        'itemManager.spec.ts',
        'securityManager.spec.ts',
        'configManager.spec.ts',
        'notification.spec.ts',
        'userSettingsManager.spec.ts',
        'plugin.spec.ts',
      ],
      explicit: false,
      partialSelection: false,
      partialReasons: [],
    },
    counts: { selected: 7, completed: 7, failed: 0, skipped: 0, retries: 0, knownFailed: 0, newFailed: 0 },
    firstAttemptResults: { passed: 7, failed: 0, skipped: 0 },
    ...overrides,
  });
}

function writeSummary(root, summary) {
  writeFileSync(path.join(root, 'run-summary.json'), `${JSON.stringify(summary, null, 2)}\n`);
}

function completeWorkflowSummary(tier, spec, overrides = {}) {
  return completeSummary({
    status: 'passed',
    completeTierA: false,
    completeTierB: false,
    completeTierInstall: false,
    completeTierWiring: tier === 'WIRING',
    completeTierWrite: tier === 'WRITE',
    selection: {
      tier,
      selectedSpecs: [spec],
      explicit: false,
      partialSelection: false,
      partialReasons: [],
    },
    counts: { selected: 1, completed: 1, failed: 0, skipped: 0, retries: 0, knownFailed: 0, newFailed: 0 },
    firstAttemptResults: { passed: 1, failed: 0, skipped: 0 },
    ...overrides,
  });
}

test('a complete passing Tier A run within budget is eligible', () => {
  const evaluation = evaluateRunSummary(completeSummary());

  assert.equal(evaluation.ok, true, evaluation.reasons.join('; '));
  assert.equal(evaluation.withinBudget, true);
  assert.equal(evaluation.budgetMs, TIER_A_BUDGET_MS);
});

test('partial, failed and over-budget runs are not eligible', () => {
  assert.equal(evaluateRunSummary(completeSummary({ status: 'partial', completeTierA: false })).ok, false);
  assert.equal(evaluateRunSummary(completeSummary({ status: 'setup-failed', completeTierA: false })).ok, false);
  assert.equal(evaluateRunSummary(completeSummary({ durationMs: TIER_A_BUDGET_MS + 1 })).ok, false);
  assert.equal(evaluateRunSummary(null).ok, false);
});

test('a retry-success flaky run is rejected as an incomplete Tier A observation', () => {
  const summary = completeSummary({
    status: 'flaky',
    completeTierA: false,
    counts: { selected: 4, completed: 4, failed: 0, skipped: 0, retries: 1, flaky: 1 },
    firstAttemptResults: { passed: 3, failed: 1, skipped: 0 },
  });
  const evaluation = evaluateRunSummary(summary);

  assert.equal(evaluation.ok, false);
  assert.match(evaluation.reasons.join('\n'), /flaky/);
});

test('the rendered evidence records the observed commit and duration against the budget', () => {
  const summary = completeSummary();
  const markdown = renderMarkdown(summary, evaluateRunSummary(summary), { setupDurationMs: 45_000 });

  assert.match(markdown, /a{40}/);
  assert.match(markdown, /120\.0s \/ 300s budget \(within\)/);
  assert.match(markdown, /45\.0s/);
  assert.match(markdown, /may be counted as one Tier A observation/);
});

test('the CLI finds exactly one retained summary and fails when none exists', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-report-'));
  const summaryDir = path.join(root, 'run-1');
  const stepSummary = path.join(root, 'step-summary.md');

  mkdirSync(summaryDir, { recursive: true });
  writeSummary(summaryDir, completeSummary());
  writeFileSync(stepSummary, '');

  const setupStartedMs = 1_700_000_000_000;

  const pass = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      root,
      '--budget-seconds',
      '300',
      '--setup-started-ms',
      String(setupStartedMs),
      '--setup-finished-ms',
      String(setupStartedMs + 60_000),
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.equal(pass.status, 0, pass.stderr);
  assert.equal(findRunSummaries(root).length, 1);
  assert.match(readFileSync(stepSummary, 'utf8'), /UI smoke Tier A observation/);

  const empty = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-report-empty-'));
  const fail = spawnSync(
    process.execPath,
    [CLI, '--summaries', empty, '--budget-seconds', '300'],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: '' } },
  );

  assert.notEqual(fail.status, 0, 'a missing summary must fail');
  assert.match(fail.stdout, /No retained run summary was found/);
  assert.match(fail.stderr, /::error::/);
});

test('the runner stamps the pre-spec boundary before launching Playwright', () => {
  const source = readFileSync(RUNNER, 'utf8');
  const stampIndex = source.indexOf('UI_SMOKE_SPECS_STARTED_MS');
  const playwrightIndex = source.indexOf('./node_modules/.bin/playwright test');

  assert.notEqual(stampIndex, -1, 'the runner must write UI_SMOKE_SPECS_STARTED_MS');
  assert.notEqual(playwrightIndex, -1, 'the runner must launch Playwright');
  assert.ok(
    stampIndex < playwrightIndex,
    'UI_SMOKE_SPECS_STARTED_MS must be written before Playwright starts, not after the spec run',
  );
});

test('setup duration is measured from setup start to setup finish, excluding the later spec run', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-report-setup-'));
  const summaryDir = path.join(root, 'run-1');
  const stepSummary = path.join(root, 'step-summary.md');
  const setupStartedMs = 1_700_000_000_000;
  const setupFinishedMs = setupStartedMs + 42_500;

  mkdirSync(summaryDir, { recursive: true });
  writeSummary(summaryDir, completeSummary());
  writeFileSync(stepSummary, '');

  const report = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      root,
      '--budget-seconds',
      '300',
      '--setup-started-ms',
      String(setupStartedMs),
      '--setup-finished-ms',
      String(setupFinishedMs),
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.equal(report.status, 0, report.stderr);
  const markdown = readFileSync(stepSummary, 'utf8');
  assert.match(markdown, /Setup duration \(before specs\) \| 42\.5s \/ 1800s budget \(within\)/);
  assert.doesNotMatch(
    markdown,
    /Setup duration \(before specs\) \| 43\./,
    'the setup duration must not be recomputed after the spec run',
  );
});

test('a missing setup stamp cannot qualify as Tier A evidence', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-report-no-setup-'));
  const summaryDir = path.join(root, 'run-1');
  const stepSummary = path.join(root, 'step-summary.md');

  mkdirSync(summaryDir, { recursive: true });
  writeSummary(summaryDir, completeSummary());
  writeFileSync(stepSummary, '');

  const report = spawnSync(
    process.execPath,
    [CLI, '--summaries', root, '--budget-seconds', '300'],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.notEqual(report.status, 0, 'missing setup stamps must fail the evaluation');
  assert.match(readFileSync(stepSummary, 'utf8'), /setup start stamp is missing/);
  assert.match(report.stderr, /::error::/);
});

test('a non-numeric setup stamp cannot qualify as Tier A evidence', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-report-bad-setup-'));
  const summaryDir = path.join(root, 'run-1');
  const stepSummary = path.join(root, 'step-summary.md');

  mkdirSync(summaryDir, { recursive: true });
  writeSummary(summaryDir, completeSummary());
  writeFileSync(stepSummary, '');

  const report = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      root,
      '--budget-seconds',
      '300',
      '--setup-started-ms',
      'not-a-number',
      '--setup-finished-ms',
      '1700000001000',
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.notEqual(report.status, 0, 'a non-numeric setup stamp must fail the evaluation');
  assert.match(readFileSync(stepSummary, 'utf8'), /setup start stamp is not numeric/);
  assert.match(report.stderr, /::error::/);
});

test('a negative setup stamp cannot qualify as Tier A evidence', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-report-negative-setup-'));
  const summaryDir = path.join(root, 'run-1');
  const stepSummary = path.join(root, 'step-summary.md');

  mkdirSync(summaryDir, { recursive: true });
  writeSummary(summaryDir, completeSummary());
  writeFileSync(stepSummary, '');

  const report = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      root,
      '--budget-seconds',
      '300',
      '--setup-started-ms',
      '-1',
      '--setup-finished-ms',
      '1700000001000',
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.notEqual(report.status, 0, 'a negative setup stamp must fail the evaluation');
  assert.match(readFileSync(stepSummary, 'utf8'), /setup start stamp is negative/);
  assert.match(report.stderr, /::error::/);
});

test('zero and empty setup stamps produce no measured window', () => {
  const cases = [
    { setupStartedMs: 0, setupFinishedMs: 1_700_000_001_000 },
    { setupStartedMs: 1_700_000_001_000, setupFinishedMs: 0 },
    { setupStartedMs: '', setupFinishedMs: 1_700_000_001_000 },
    { setupStartedMs: 1_700_000_001_000, setupFinishedMs: '' },
  ];

  for (const options of cases) {
    const window = resolveSetupWindow(options);

    assert.equal(window.durationMs, undefined, JSON.stringify(options));
    assert.ok(window.reasons.length > 0, JSON.stringify(options));
  }

  assert.match(resolveSetupWindow(cases[0]).reasons.join(' '), /start stamp is not a valid timestamp: 0/);
  assert.match(resolveSetupWindow(cases[1]).reasons.join(' '), /finish stamp is not a valid timestamp: 0/);
  assert.match(resolveSetupWindow(cases[2]).reasons.join(' '), /start stamp is missing/);
  assert.match(resolveSetupWindow(cases[3]).reasons.join(' '), /finish stamp is missing/);
});

test('the CLI rejects zero and empty setup stamps as incomplete Tier A evidence', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-report-empty-stamps-'));
  const summaryDir = path.join(root, 'run-1');
  const stepSummary = path.join(root, 'step-summary.md');

  mkdirSync(summaryDir, { recursive: true });
  writeSummary(summaryDir, completeSummary());

  const cases = [
    ['0', '1700000001000', /start stamp is not a valid timestamp: 0/],
    ['1700000001000', '0', /finish stamp is not a valid timestamp: 0/],
    ['', '1700000001000', /start stamp is missing/],
    ['1700000001000', '', /finish stamp is missing/],
  ];

  for (const [started, finished, message] of cases) {
    writeFileSync(stepSummary, '');

    const report = spawnSync(
      process.execPath,
      [
        CLI,
        '--summaries',
        root,
        '--budget-seconds',
        '300',
        '--setup-started-ms',
        started,
        '--setup-finished-ms',
        finished,
      ],
      { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
    );

    assert.notEqual(report.status, 0, `stamps ${JSON.stringify([started, finished])} must fail`);
    assert.match(readFileSync(stepSummary, 'utf8'), message);
    assert.match(report.stderr, /::error::/);
  }
});

test('a finish stamp before the start stamp cannot qualify as Tier A evidence', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-report-reversed-setup-'));
  const summaryDir = path.join(root, 'run-1');
  const stepSummary = path.join(root, 'step-summary.md');

  mkdirSync(summaryDir, { recursive: true });
  writeSummary(summaryDir, completeSummary());
  writeFileSync(stepSummary, '');

  const report = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      root,
      '--budget-seconds',
      '300',
      '--setup-started-ms',
      '1700000002000',
      '--setup-finished-ms',
      '1700000001000',
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.notEqual(report.status, 0, 'a finish stamp before the start stamp must fail the evaluation');
  assert.match(readFileSync(stepSummary, 'utf8'), /setup finish stamp .* is not after the setup start stamp/);
  assert.match(report.stderr, /::error::/);
});

test('setup duration includes runner provisioning and the 30-minute setup budget fails closed', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-report-provisioning-'));
  const summaryDir = path.join(root, 'run-1');
  const setupStartedMs = 1_700_000_000_000;
  const workflowPreRunnerFinishMs = setupStartedMs + 60_000;
  const runnerPreSpecStartedMs = setupStartedMs + 900_000;

  mkdirSync(summaryDir, { recursive: true });
  writeSummary(summaryDir, completeSummary());

  const withinStepSummary = path.join(root, 'step-summary-within.md');
  writeFileSync(withinStepSummary, '');

  const within = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      root,
      '--budget-seconds',
      '300',
      '--setup-budget-seconds',
      '1800',
      '--setup-started-ms',
      String(setupStartedMs),
      '--setup-finished-ms',
      String(runnerPreSpecStartedMs),
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: withinStepSummary } },
  );

  assert.equal(within.status, 0, within.stderr);
  const withinMarkdown = readFileSync(withinStepSummary, 'utf8');
  assert.match(
    withinMarkdown,
    /Setup duration \(before specs\) \| 900\.0s \/ 1800s budget \(within\)/,
    'the setup duration must include runner provisioning after the pre-runner boundary',
  );
  assert.doesNotMatch(
    withinMarkdown,
    new RegExp(`${(workflowPreRunnerFinishMs - setupStartedMs) / 1000}\\.0s`),
    'the pre-runner boundary must not be used as the setup finish',
  );

  const overStepSummary = path.join(root, 'step-summary-over.md');
  writeFileSync(overStepSummary, '');

  const over = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      root,
      '--budget-seconds',
      '300',
      '--setup-budget-seconds',
      '1800',
      '--setup-started-ms',
      String(setupStartedMs),
      '--setup-finished-ms',
      String(setupStartedMs + 1_800_001),
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: overStepSummary } },
  );

  assert.notEqual(over.status, 0, 'an over-budget setup window must fail the evaluation');
  assert.match(readFileSync(overStepSummary, 'utf8'), /exceeds the 1800s setup budget/);
  assert.match(over.stderr, /::error::/);
});

const REGISTRY_ADMISSION = {
  who: 'maintainer',
  date: '2026-11-06',
  ref: '#2080',
};

function registryEntry(overrides = {}) {
  return {
    spec: 'shell.spec.ts',
    route: 'account/index',
    failureKind: 'http',
    reason: 'account/index is deterministically broken',
    defect: '#2000',
    admission: { ...REGISTRY_ADMISSION },
    ...overrides,
  };
}

function registry(entries = []) {
  return { schemaVersion: 1, entries };
}

function failingTest({
  file = 'tests/ui/specs/shell.spec.ts',
  route = 'account/index',
  failureKind = 'http',
  message = 'http [account/index]: action account/index returned HTTP 500',
  failureProblems = [{ route, failureKind }],
} = {}) {
  return {
    title: 'loads the shell',
    file,
    project: 'chromium',
    skipped: false,
    expectedStatus: 'passed',
    firstAttemptStatus: 'failed',
    finalStatus: 'failed',
    retries: 0,
    durationMs: 10,
    attempts: [
      {
        attempt: 1,
        retryIndex: 0,
        firstAttempt: true,
        status: 'failed',
        durationMs: 10,
        failureClass: failureKind,
        route,
        failureProblems,
        errorMessage: message,
        errorStack: '',
      },
    ],
  };
}

function passingTest(overrides = {}) {
  return {
    ...failingTest(overrides),
    firstAttemptStatus: 'passed',
    finalStatus: 'passed',
    attempts: [
      {
        attempt: 1,
        retryIndex: 0,
        firstAttempt: true,
        status: 'passed',
        durationMs: 10,
        failureClass: null,
        route: null,
        failureProblems: [],
        errorMessage: '',
        errorStack: '',
      },
    ],
  };
}

function recordedSummary(tests, entries = [], overrides = {}) {
  const parsed = parseRegistry(registry(entries));
  const annotations = annotateKnownFailures(tests, parsed.ok ? parsed.entries : []);
  const failed = annotations.tests.filter(
    (test) => test.finalStatus === 'failed' || test.finalStatus === 'timedOut',
  ).length;
  const status =
    failed > 0 || !parsed.ok || annotations.staleEntries.length > 0 ? 'failed' : 'passed';

  return completeSummary({
    status,
    completeTierA: false,
    tests: annotations.tests,
    knownFailures: annotations.knownFailures,
    newFailures: annotations.newFailures,
    staleKnownFailures: annotations.staleEntries,
    unexecutedKnownFailures: annotations.unexecutedEntries,
    knownFailureErrors: parsed.ok ? [] : parsed.errors,
    counts: {
      selected: 4,
      completed: 4,
      failed,
      skipped: 0,
      retries: 0,
      knownFailed: annotations.knownFailures.length,
      newFailed: annotations.newFailures.length,
    },
    ...overrides,
  });
}

test('an admitted known failure is annotated but never eligible or exit-zero', () => {
  const evaluation = evaluateRunSummary(recordedSummary([failingTest()], [registryEntry()]));

  assert.equal(evaluation.ok, false);
  assert.equal(evaluation.completeTierA, false);
  assert.equal(evaluation.knownFailures.length, 1);
  assert.equal(evaluation.newFailures.length, 0);
  assert.equal(evaluation.staleKnownFailures.length, 0);
  assert.equal(evaluation.status, 'failed');
  assert.match(evaluation.reasons.join('\n'), /known failure/i);
});

test('an unlisted or same-spec unrelated failure still fails evaluation', () => {
  const unlisted = evaluateRunSummary(
    recordedSummary([failingTest()], [registryEntry({ route: 'account/view' })]),
  );
  const unrelated = evaluateRunSummary(
    recordedSummary(
      [
        failingTest({
          route: 'accountFile/list',
          failureKind: 'missing-content',
          message: 'missing-content [accountFile/list]: response is missing expected content "file"',
        }),
      ],
      [registryEntry()],
    ),
  );

  for (const evaluation of [unlisted, unrelated]) {
    assert.equal(evaluation.ok, false);
    assert.equal(evaluation.newFailures.length, 1);
    assert.match(evaluation.reasons.join('\n'), /unadmitted|not admitted|new failure|missing or not matched/i);
  }
});

test('an obsolete entry whose spec executed fails with removal instructions', () => {
  const evaluation = evaluateRunSummary(
    recordedSummary([passingTest()], [registryEntry()]),
  );

  assert.equal(evaluation.ok, false);
  assert.equal(evaluation.staleKnownFailures.length, 1);
  assert.match(evaluation.reasons.join('\n'), /remove the stale entry/i);
});

test('a filtered or unexecuted entry is surfaced and never produces an ok observation', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-report-filtered-'));
  const summaryDir = path.join(root, 'run-1');
  const stepSummary = path.join(root, 'step-summary.md');
  const setupStartedMs = 1_700_000_000_000;
  const summary = recordedSummary([passingTest({ file: 'tests/ui/specs/login.spec.ts' })], [registryEntry()], {
    status: 'partial',
    completeTierA: false,
    partialSelection: true,
    partialReasons: ['explicit specs are a subset of Tier A; missing shell.spec.ts'],
    counts: {
      selected: 1,
      completed: 1,
      failed: 0,
      skipped: 0,
      retries: 0,
      knownFailed: 0,
      newFailed: 0,
    },
  });

  mkdirSync(summaryDir, { recursive: true });
  writeSummary(summaryDir, summary);
  writeFileSync(stepSummary, '');

  const evaluation = evaluateRunSummary(summary);

  assert.equal(evaluation.staleKnownFailures.length, 0);
  assert.equal(evaluation.unexecutedKnownFailures.length, 1);
  assert.equal(evaluation.ok, false);
  assert.match(evaluation.reasons.join('\n'), /not exercised/i);

  const report = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      root,
      '--budget-seconds',
      '300',
      '--setup-started-ms',
      String(setupStartedMs),
      '--setup-finished-ms',
      String(setupStartedMs + 60_000),
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.notEqual(report.status, 0, 'an unexercised entry must not exit zero');
  const markdown = readFileSync(stepSummary, 'utf8');
  assert.match(markdown, /not exercised by this selection/i);
  assert.doesNotMatch(markdown, /may be counted as one Tier A observation/);
});

test('an invalid known-failure registry recorded in the summary fails closed', () => {
  const evaluation = evaluateRunSummary(
    recordedSummary([], [registryEntry({ failureKind: 'flaky' })]),
  );

  assert.equal(evaluation.ok, false);
  assert.ok(evaluation.knownFailureErrors.length > 0);
  assert.match(evaluation.reasons.join('\n'), /known failure registry|failure kind/i);
});

test('the recorded registry evaluation is authoritative over a supplied registry file', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-report-authoritative-'));
  const summaryDir = path.join(root, 'run-1');
  const stepSummary = path.join(root, 'step-summary.md');
  const registryPath = path.join(root, 'known-failures.json');
  const setupStartedMs = 1_700_000_000_000;
  const recorded = recordedSummary([failingTest()], [registryEntry()], {
    knownFailureErrors: ['recorded registry evaluation failed closed'],
  });

  mkdirSync(summaryDir, { recursive: true });
  writeSummary(summaryDir, recorded);
  writeFileSync(registryPath, `${JSON.stringify(registry([registryEntry()]), null, 2)}\n`);
  writeFileSync(stepSummary, '');

  const report = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      root,
      '--registry',
      registryPath,
      '--setup-started-ms',
      String(setupStartedMs),
      '--setup-finished-ms',
      String(setupStartedMs + 60_000),
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.notEqual(report.status, 0, 'recorded known-failure errors must not be re-admitted');
  assert.match(report.stderr, /recorded registry evaluation failed closed/);
  const markdown = readFileSync(stepSummary, 'utf8');
  assert.match(markdown, /recorded registry evaluation failed closed/);
  assert.doesNotMatch(markdown, /may be counted as one Tier A observation/);
});

test('registry reason and admission text are sanitized before the step summary', () => {
  const summary = recordedSummary([failingTest()], [
    registryEntry({
      reason: 'password=CANARY_REGISTRY_REASON',
      admission: { ...REGISTRY_ADMISSION, who: 'password=CANARY_ADMISSION' },
    }),
  ]);
  const markdown = renderMarkdown(summary, evaluateRunSummary(summary), {});

  assert.match(markdown, /Admitted known failures/);
  assert.doesNotMatch(markdown, /CANARY_REGISTRY_REASON/);
  assert.doesNotMatch(markdown, /CANARY_ADMISSION/);
  assert.match(markdown, /\[REDACTED\]/);
});

test('a summary without recorded registry fields is incompatible rather than re-evaluated', () => {
  const summary = completeSummary();

  for (const field of [
    'knownFailures',
    'newFailures',
    'staleKnownFailures',
    'unexecutedKnownFailures',
    'knownFailureErrors',
  ]) {
    delete summary[field];
  }

  const evaluation = evaluateRunSummary(summary, {
    registry: registry([registryEntry()]),
  });

  assert.equal(evaluation.ok, false);
  assert.match(evaluation.reasons.join('\n'), /incompatible|does not record/i);
});

test('a multi-problem attempt must have every problem admitted to fail closed', () => {
  const multi = failingTest({
    route: 'accountFile/list',
    failureKind: 'missing-content',
    message: [
      'readiness failed',
      'detector problems: missing-content [accountFile/list]: response is missing expected content "file"',
      '| http [account/index]: action account/index returned HTTP 500',
    ].join('; '),
    failureProblems: [
      { route: 'accountFile/list', failureKind: 'missing-content' },
      { route: 'account/index', failureKind: 'http' },
    ],
  });
  const partial = evaluateRunSummary(
    recordedSummary([multi], [registryEntry({ route: 'accountFile/list', failureKind: 'missing-content' })]),
  );
  const all = evaluateRunSummary(
    recordedSummary(
      [multi],
      [
        registryEntry({ route: 'accountFile/list', failureKind: 'missing-content' }),
        registryEntry(),
      ],
    ),
  );

  assert.equal(partial.ok, false);
  assert.equal(partial.newFailures.length, 1);
  assert.equal(partial.newFailures[0].route, 'account/index');
  assert.equal(partial.newFailures[0].failureKind, 'http');

  assert.equal(all.knownFailures.length, 2);
  assert.equal(all.newFailures.length, 0);
  assert.equal(all.ok, false, 'admitted known failures never make the run eligible');
});

test('the ci-report CLI exits non-zero for admitted known and for stale entries', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-report-known-'));
  const summaryDir = path.join(root, 'run-1');
  const stepSummary = path.join(root, 'step-summary.md');
  const setupStartedMs = 1_700_000_000_000;

  mkdirSync(summaryDir, { recursive: true });
  writeSummary(summaryDir, recordedSummary([failingTest()], [registryEntry()]));
  writeFileSync(stepSummary, '');

  const known = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      root,
      '--setup-started-ms',
      String(setupStartedMs),
      '--setup-finished-ms',
      String(setupStartedMs + 60_000),
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.notEqual(known.status, 0);
  const markdown = readFileSync(stepSummary, 'utf8');
  assert.match(markdown, /Admitted known failures/);
  assert.match(markdown, /Tests selected\/completed\/failed\/skipped \| 4\/4\/1\/0/);
  assert.match(markdown, /cannot be counted as a Tier A observation/);
  assert.doesNotMatch(markdown, /may be counted as one Tier A observation/);
  assert.match(known.stderr, /::error::/);
  assert.match(known.stderr, /admitted known failure/i);

  writeSummary(summaryDir, recordedSummary([passingTest()], [registryEntry()]));
  writeFileSync(stepSummary, '');

  const stale = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      root,
      '--setup-started-ms',
      String(setupStartedMs),
      '--setup-finished-ms',
      String(setupStartedMs + 60_000),
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.notEqual(stale.status, 0);
  assert.match(readFileSync(stepSummary, 'utf8'), /remove the stale entry/i);
  assert.match(stale.stderr, /::error::/);
});

test('Tier B is evaluated independently and can never satisfy Tier A', () => {
  const tierB = completeTierBSummary();

  const asTierB = evaluateRunSummary(tierB, { tier: 'B' });
  assert.equal(asTierB.ok, true, asTierB.reasons.join('; '));
  assert.equal(asTierB.completeTierB, true);
  assert.equal(asTierB.completeTierA, false);

  const asTierA = evaluateRunSummary(tierB, { tier: 'A' });
  assert.equal(asTierA.ok, false);
  assert.match(asTierA.reasons.join('\n'), /not a complete Tier A selection|belongs to Tier B/i);

  const tierA = completeSummary();
  const tierAAsB = evaluateRunSummary(tierA, { tier: 'B' });
  assert.equal(tierAAsB.ok, false);
  assert.match(tierAAsB.reasons.join('\n'), /complete Tier B|belongs to Tier A/i);
});

test('the ci-report CLI renders Tier B separately, annotates failures and keeps a failing exit', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-report-tier-b-'));
  const summaryDir = path.join(root, 'run-1');
  const stepSummary = path.join(root, 'step-summary.md');
  const setupStartedMs = 1_700_000_000_000;

  mkdirSync(summaryDir, { recursive: true });
  writeSummary(summaryDir, completeTierBSummary());
  writeFileSync(stepSummary, '');

  const passing = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      root,
      '--tier',
      'B',
      '--budget-seconds',
      '300',
      '--setup-started-ms',
      String(setupStartedMs),
      '--setup-finished-ms',
      String(setupStartedMs + 60_000),
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.equal(passing.status, 0, passing.stderr);
  const passMarkdown = readFileSync(stepSummary, 'utf8');
  assert.match(passMarkdown, /UI smoke Tier B observation/);
  assert.match(passMarkdown, /Complete Tier B \| yes/);
  assert.match(passMarkdown, /Tier B spec duration/);
  assert.doesNotMatch(passMarkdown, /Tier A spec duration/);
  assert.match(passMarkdown, /may be counted as one Tier B observation/);
  assert.doesNotMatch(passMarkdown, /Tier A observation/);

  const failedTest = {
    title: 'accessManager/index loads its own tabbed content in-shell',
    file: 'tests/ui/specs/accessManager.spec.ts',
    project: 'chromium',
    skipped: false,
    expectedStatus: 'passed',
    firstAttemptStatus: 'failed',
    finalStatus: 'failed',
    retries: 0,
    durationMs: 42,
    attempts: [
      {
        attempt: 1,
        retryIndex: 0,
        firstAttempt: true,
        status: 'failed',
        durationMs: 42,
        failureClass: 'http',
        route: 'accessManager/index',
        failureProblems: [{ route: 'accessManager/index', failureKind: 'http' }],
        errorMessage: 'http [accessManager/index]: action accessManager/index returned HTTP 500',
        errorStack: '',
      },
    ],
  };

  writeSummary(
    summaryDir,
    completeTierBSummary({
      status: 'failed',
      completeTierB: false,
      tests: [failedTest],
      counts: { selected: 7, completed: 7, failed: 1, skipped: 0, retries: 0, knownFailed: 0, newFailed: 1 },
      firstAttemptResults: { passed: 6, failed: 1, skipped: 0 },
      newFailures: [{ spec: 'accessManager.spec.ts', route: 'accessManager/index', failureKind: 'http' }],
      failureClasses: { http: 1 },
    }),
  );
  writeFileSync(stepSummary, '');

  const failing = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      root,
      '--tier',
      'B',
      '--budget-seconds',
      '300',
      '--setup-started-ms',
      String(setupStartedMs),
      '--setup-finished-ms',
      String(setupStartedMs + 60_000),
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.notEqual(failing.status, 0, 'a Tier B failure must not exit zero');
  assert.match(failing.stderr, /::warning .*accessManager\/index/);
  assert.match(failing.stderr, /::error::UI smoke is not a complete Tier B observation/);
  const failMarkdown = readFileSync(stepSummary, 'utf8');
  assert.match(failMarkdown, /UI smoke Tier B observation/);
  assert.match(failMarkdown, /Complete Tier B \| no/);
  assert.match(failMarkdown, /must not be counted as a Tier B observation/);
  assert.doesNotMatch(failMarkdown, /Tier A observation/);
});

test('run-summary discovery is non-recursive and never walks into a sibling or nested tier root', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-tier-roots-'));
  const tierA = path.join(root, 'tier-A');
  const tierB = path.join(root, 'tier-B');
  const runA = path.join(tierA, 'run-a');
  const runB = path.join(tierB, 'run-b');

  mkdirSync(runA, { recursive: true });
  mkdirSync(runB, { recursive: true });
  writeSummary(runA, completeSummary());
  writeSummary(runB, completeTierBSummary());

  assert.deepEqual(findRunSummaries(tierA), [path.join(runA, 'run-summary.json')]);
  assert.deepEqual(findRunSummaries(tierB), [path.join(runB, 'run-summary.json')]);

  const legacyA = path.join(root, 'legacy-A');
  const legacyRunA = path.join(legacyA, 'run-a');
  const nestedTierB = path.join(legacyA, 'tier-B', 'run-b');

  mkdirSync(legacyRunA, { recursive: true });
  mkdirSync(nestedTierB, { recursive: true });
  writeSummary(legacyRunA, completeSummary());
  writeSummary(nestedTierB, completeTierBSummary());

  assert.deepEqual(
    findRunSummaries(legacyA),
    [path.join(legacyRunA, 'run-summary.json')],
    'a nested Tier B root must not contribute a Tier A summary',
  );

  const onlyNestedB = mkdtempSync(path.join(tmpdir(), 'sp-ui-tier-only-nested-b-'));
  const hiddenB = path.join(onlyNestedB, 'tier-B', 'run-b');
  mkdirSync(hiddenB, { recursive: true });
  writeSummary(hiddenB, completeTierBSummary());

  assert.deepEqual(
    findRunSummaries(onlyNestedB),
    [],
    'a Tier A report must fail instead of consuming a nested Tier B summary',
  );
});

test('a Tier A report over its root cannot consume Tier B evidence and a Tier B report cannot consume Tier A evidence', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-tier-scoped-report-'));
  const tierA = path.join(root, 'tier-A');
  const tierB = path.join(root, 'tier-B');
  const runA = path.join(tierA, 'run-a');
  const runB = path.join(tierB, 'run-b');
  const stepSummary = path.join(root, 'step-summary.md');
  const setupStartedMs = 1_700_000_000_000;

  mkdirSync(runA, { recursive: true });
  mkdirSync(runB, { recursive: true });
  writeSummary(runA, completeSummary());
  writeSummary(runB, completeTierBSummary());
  writeFileSync(stepSummary, '');

  const reportA = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      tierA,
      '--tier',
      'A',
      '--budget-seconds',
      '300',
      '--setup-started-ms',
      String(setupStartedMs),
      '--setup-finished-ms',
      String(setupStartedMs + 60_000),
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.equal(reportA.status, 0, reportA.stderr);
  const markdownA = readFileSync(stepSummary, 'utf8');
  assert.match(markdownA, /UI smoke Tier A observation/);
  assert.doesNotMatch(markdownA, /Tier B observation/);
  assert.doesNotMatch(markdownA, /Complete Tier B/);

  writeFileSync(stepSummary, '');

  const reportB = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      tierB,
      '--tier',
      'B',
      '--budget-seconds',
      '300',
      '--setup-started-ms',
      String(setupStartedMs),
      '--setup-finished-ms',
      String(setupStartedMs + 60_000),
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.equal(reportB.status, 0, reportB.stderr);
  const markdownB = readFileSync(stepSummary, 'utf8');
  assert.match(markdownB, /UI smoke Tier B observation/);
  assert.doesNotMatch(markdownB, /Tier A observation/);

  const wrongRoot = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      tierB,
      '--tier',
      'A',
      '--budget-seconds',
      '300',
      '--setup-started-ms',
      String(setupStartedMs),
      '--setup-finished-ms',
      String(setupStartedMs + 60_000),
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: '' } },
  );

  assert.notEqual(wrongRoot.status, 0, 'a Tier B summary must not qualify a Tier A report');
  assert.match(wrongRoot.stderr, /belongs to Tier B|not a complete Tier A selection/);
});

test('ci-report accepts a per-run directory so accumulated tier history does not break the single-summary rule', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-tier-history-'));
  const tierB = path.join(root, 'tier-B');
  const runOne = path.join(tierB, 'run-1');
  const runTwo = path.join(tierB, 'run-2');
  const stepSummary = path.join(root, 'step-summary.md');
  const setupStartedMs = 1_700_000_000_000;

  mkdirSync(runOne, { recursive: true });
  mkdirSync(runTwo, { recursive: true });
  writeSummary(runOne, completeTierBSummary({ runId: 'sp-ui-smoke.run-1' }));
  writeSummary(
    runTwo,
    completeTierBSummary({ runId: 'sp-ui-smoke.run-2', status: 'failed', completeTierB: false }),
  );
  writeFileSync(stepSummary, '');

  const perRun = spawnSync(
    process.execPath,
    [
      CLI,
      '--summaries',
      runOne,
      '--tier',
      'b',
      '--budget-seconds',
      '300',
      '--setup-started-ms',
      String(setupStartedMs),
      '--setup-finished-ms',
      String(setupStartedMs + 60_000),
    ],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.equal(perRun.status, 0, perRun.stderr);
  assert.match(readFileSync(stepSummary, 'utf8'), /UI smoke Tier B observation/);

  const accumulatedRoot = spawnSync(
    process.execPath,
    [CLI, '--summaries', tierB, '--tier', 'B', '--budget-seconds', '300'],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: '' } },
  );

  assert.notEqual(accumulatedRoot.status, 0, 'the accumulating tier root is not a single-run input');
  assert.match(accumulatedRoot.stderr, /expected one run-summary\.json, found 2/);
});

test('ci-report rejects an invalid tier as a usage error instead of rendering a Tier Z report', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-ci-report-bad-tier-'));
  const summaryDir = path.join(root, 'run-1');
  const stepSummary = path.join(root, 'step-summary.md');

  mkdirSync(summaryDir, { recursive: true });
  writeSummary(summaryDir, completeSummary());
  writeFileSync(stepSummary, '');

  const report = spawnSync(
    process.execPath,
    [CLI, '--summaries', root, '--tier', 'Z', '--budget-seconds', '300'],
    { encoding: 'utf8', env: { ...process.env, GITHUB_STEP_SUMMARY: stepSummary } },
  );

  assert.equal(report.status, 2, 'an unknown tier is a usage error, not an observation');
  assert.match(report.stderr, /Unknown UI smoke tier: Z/);
  assert.doesNotMatch(report.stdout, /Tier Z/);
  assert.equal(readFileSync(stepSummary, 'utf8'), '', 'no observation may be rendered for an invalid tier');
});

test('the runner normalizes lower-case tier tokens and rejects unknown tiers as a usage error', () => {
  const baseEnv = { ...process.env };

  delete baseEnv.SP_UI_TIER;
  delete baseEnv.TESTS_UI_ENABLED;
  delete baseEnv.SP_UI_SPECS;

  const invalid = spawnSync('bash', [RUNNER], {
    cwd: path.resolve(UI_DIR, '..', '..'),
    encoding: 'utf8',
    env: { ...baseEnv, SP_UI_TIER: 'Z', TESTS_UI_ENABLED: 'false' },
  });

  assert.equal(invalid.status, 2, invalid.stderr);
  assert.match(
    invalid.stderr,
    /SP_UI_TIER must be A, B, INSTALL, WIRING, WRITE or DISCOVERY; received 'Z'/,
  );

  for (const token of ['a', 'b', 'wiring', 'discovery']) {
    const lowercase = spawnSync('bash', [RUNNER], {
      cwd: path.resolve(UI_DIR, '..', '..'),
      encoding: 'utf8',
      env: { ...baseEnv, SP_UI_TIER: token, TESTS_UI_ENABLED: 'false' },
    });

    assert.equal(lowercase.status, 0, lowercase.stderr);
    assert.match(lowercase.stdout, /UI smoke skipped: TESTS_UI_ENABLED is not 'true'/);
  }
});

test('the runner rejects a truthy-looking SP_UI_INSTALL even with tier INSTALL', () => {
  const baseEnv = { ...process.env };

  delete baseEnv.SP_UI_TIER;
  delete baseEnv.SP_UI_INSTALL;
  delete baseEnv.TESTS_UI_ENABLED;
  delete baseEnv.SP_UI_SPECS;

  for (const tier of ['A', 'INSTALL']) {
    const invalid = spawnSync('bash', [RUNNER], {
      cwd: path.resolve(UI_DIR, '..', '..'),
      encoding: 'utf8',
      env: {
        ...baseEnv,
        SP_UI_TIER: tier,
        SP_UI_INSTALL: 'yes',
        TESTS_UI_ENABLED: 'false',
      },
    });

    assert.equal(invalid.status, 2, invalid.stderr);
    assert.match(invalid.stderr, /SP_UI_INSTALL must be 0 or 1/);
  }
});

test('the runner keeps sibling Tier A and Tier B summary roots', () => {
  const source = readFileSync(RUNNER, 'utf8');

  assert.match(source, /summaries\/tier-A/, 'Tier A must retain a dedicated summary root');
  assert.match(source, /summaries\/tier-B/, 'Tier B must retain a dedicated summary root');
  assert.match(source, /summaries\/tier-write/, 'Tier WRITE must retain a dedicated summary root');
  assert.doesNotMatch(
    source,
    /DEFAULT_SUMMARY_ROOT="\$UI_DIR\/summaries"$/m,
    'Tier A must not use the shared parent that also contains Tier B',
  );
});

test('an over-budget Tier B observation is labelled Tier B, not Tier A', () => {
  const evaluation = evaluateRunSummary(
    completeTierBSummary({ durationMs: TIER_A_BUDGET_MS + 1 }),
    { tier: 'B' },
  );

  assert.equal(evaluation.ok, false);
  assert.match(evaluation.reasons.join('\n'), /Tier B duration .* exceeds/);
  assert.doesNotMatch(evaluation.reasons.join('\n'), /Tier A duration/);
});

test('the installer has a separate completion flag and never completes Tier A', () => {
  const installer = completeSummary({
    completeTierA: false,
    completeTierB: false,
    completeTierInstall: true,
    selection: {
      tier: 'INSTALL',
      selectedSpecs: ['install.spec.ts'],
      explicit: false,
      partialSelection: false,
      partialReasons: [],
    },
    counts: { selected: 2, completed: 2, failed: 0, skipped: 0, retries: 0, knownFailed: 0, newFailed: 0 },
    firstAttemptResults: { passed: 2, failed: 0, skipped: 0 },
  });

  const installerEvaluation = evaluateRunSummary(installer, { tier: 'INSTALL' });

  assert.equal(installerEvaluation.ok, true, installerEvaluation.reasons.join('; '));
  assert.equal(installerEvaluation.completeTierInstall, true);
  assert.equal(installerEvaluation.completeTierA, false);

  const asTierA = evaluateRunSummary(installer, { tier: 'A' });

  assert.equal(asTierA.ok, false);
  assert.match(asTierA.reasons.join('\n'), /belongs to Tier INSTALL, not the requested Tier A/);
});

test('a Tier A summary cannot be used as installer evidence', () => {
  const evaluation = evaluateRunSummary(
    completeSummary({ completeTierInstall: false }),
    { tier: 'INSTALL' },
  );

  assert.equal(evaluation.ok, false);
  assert.match(evaluation.reasons.join('\n'), /not a complete Tier INSTALL selection/);
});

test('complete WIRING and WRITE runs are observations only for their own selection', () => {
  for (const [tier, spec, flag] of [
    ['WIRING', 'adminNavigation.wiring.spec.ts', 'completeTierWiring'],
    ['WRITE', 'accountCreate.write.spec.ts', 'completeTierWrite'],
  ]) {
    const summary = completeWorkflowSummary(tier, spec);
    const evaluation = evaluateRunSummary(summary, { tier });
    const markdown = renderMarkdown(summary, evaluation, { tier });

    assert.equal(evaluation.ok, true, evaluation.reasons.join('; '));
    assert.equal(evaluation[flag], true);
    assert.equal(evaluation.completeTierA, false);
    assert.equal(evaluation.completeTierB, false);
    assert.equal(evaluation.completeTierInstall, false);
    assert.match(markdown, new RegExp(`UI smoke Tier ${tier} observation`));
    assert.match(markdown, new RegExp(`Complete Tier ${tier} \\| yes`));
    assert.match(markdown, new RegExp(`may be counted as one Tier ${tier} observation`));
    assert.doesNotMatch(markdown, /Tier A observation/);
  }
});

test('failed, partial, setup-failed, skipped and flaky workflow runs are not observations', () => {
  for (const [tier, spec, flag] of [
    ['WIRING', 'adminNavigation.wiring.spec.ts', 'completeTierWiring'],
    ['WRITE', 'accountCreate.write.spec.ts', 'completeTierWrite'],
  ]) {
    const cases = [
      completeWorkflowSummary(tier, spec, { status: 'failed', [flag]: false }),
      completeWorkflowSummary(tier, spec, {
        status: 'partial',
        [flag]: false,
        partialSelection: true,
        partialReasons: ['selection is not complete'],
      }),
      completeWorkflowSummary(tier, spec, { status: 'setup-failed', [flag]: false }),
      completeWorkflowSummary(tier, spec, {
        status: 'partial',
        [flag]: false,
        counts: { selected: 1, completed: 0, failed: 0, skipped: 1, retries: 0 },
      }),
      completeWorkflowSummary(tier, spec, {
        status: 'flaky',
        [flag]: false,
        counts: { selected: 1, completed: 1, failed: 0, skipped: 0, retries: 1, flaky: 1 },
      }),
    ];

    for (const summary of cases) {
      const evaluation = evaluateRunSummary(summary, { tier });
      const markdown = renderMarkdown(summary, evaluation, { tier });

      assert.equal(evaluation.ok, false, `${tier} ${summary.status} must not be eligible`);
      assert.equal(evaluation[flag], false);
      assert.match(markdown, new RegExp(`must not be counted as a Tier ${tier} observation`));
    }
  }
});

test('a WIRING summary cannot satisfy Tier A and a WIRING report cannot annotate as Tier B', () => {
  const wiring = completeWorkflowSummary('WIRING', 'adminNavigation.wiring.spec.ts');
  const asTierA = evaluateRunSummary(wiring, { tier: 'A' });
  const asTierB = evaluateRunSummary(wiring, { tier: 'B' });

  assert.equal(asTierA.ok, false);
  assert.match(asTierA.reasons.join('\n'), /belongs to Tier WIRING|not a complete Tier A selection/);
  assert.equal(asTierB.ok, false);
  assert.match(asTierB.reasons.join('\n'), /belongs to Tier WIRING|not a complete Tier B selection/);
});

test('failed WIRING and WRITE reports do not change Tier A or Tier B completeness', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'sp-ui-workflow-isolation-'));
  const tierA = path.join(root, 'tier-A', 'run-a');
  const tierB = path.join(root, 'tier-B', 'run-b');
  const tierWiring = path.join(root, 'tier-wiring', 'run-wiring');
  const tierWrite = path.join(root, 'tier-write', 'run-write');

  for (const directory of [tierA, tierB, tierWiring, tierWrite]) {
    mkdirSync(directory, { recursive: true });
  }

  writeSummary(tierA, completeSummary());
  writeSummary(tierB, completeTierBSummary());
  writeSummary(
    tierWiring,
    completeWorkflowSummary('WIRING', 'adminNavigation.wiring.spec.ts', {
      status: 'failed',
      completeTierWiring: false,
      counts: { selected: 1, completed: 1, failed: 1, skipped: 0, retries: 0 },
    }),
  );
  writeSummary(
    tierWrite,
    completeWorkflowSummary('WRITE', 'accountCreate.write.spec.ts', {
      status: 'failed',
      completeTierWrite: false,
      counts: { selected: 1, completed: 1, failed: 1, skipped: 0, retries: 0 },
    }),
  );

  const evaluationA = evaluateRunSummary(
    JSON.parse(readFileSync(path.join(tierA, 'run-summary.json'), 'utf8')),
    { tier: 'A' },
  );
  const evaluationB = evaluateRunSummary(
    JSON.parse(readFileSync(path.join(tierB, 'run-summary.json'), 'utf8')),
    { tier: 'B' },
  );
  const evaluationWiring = evaluateRunSummary(
    JSON.parse(readFileSync(path.join(tierWiring, 'run-summary.json'), 'utf8')),
    { tier: 'WIRING' },
  );
  const evaluationWrite = evaluateRunSummary(
    JSON.parse(readFileSync(path.join(tierWrite, 'run-summary.json'), 'utf8')),
    { tier: 'WRITE' },
  );

  assert.equal(evaluationA.ok, true, evaluationA.reasons.join('; '));
  assert.equal(evaluationB.ok, true, evaluationB.reasons.join('; '));
  assert.equal(evaluationWiring.ok, false);
  assert.equal(evaluationWrite.ok, false);
});
