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

const UI_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const CLI = path.join(UI_DIR, 'src', 'ci-report.mjs');
const RUNNER = path.join(UI_DIR, '..', '..', 'scripts', 'ui-smoke.sh');

function completeSummary(overrides = {}) {
  return {
    schemaVersion: 1,
    runId: 'sp-ui-smoke.test',
    status: 'passed',
    completeTierA: true,
    commit: { sha: 'a'.repeat(40), short: 'aaaaaaaaa', dirty: false, branch: 'master' },
    durationMs: 120_000,
    counts: { selected: 4, completed: 4, failed: 0, skipped: 0, retries: 0 },
    firstAttemptResults: { passed: 4, failed: 0, skipped: 0 },
    ...overrides,
  };
}

function writeSummary(root, summary) {
  writeFileSync(path.join(root, 'run-summary.json'), `${JSON.stringify(summary, null, 2)}\n`);
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
