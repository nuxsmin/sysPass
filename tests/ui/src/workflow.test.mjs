import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { parseJob, parsePermissions, parseTriggers, parseWorkflow } from './workflow-parser.mjs';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const WORKFLOW = path.join(REPO_ROOT, '.github', 'workflows', 'ui-smoke.yml');

const EVIDENCE_PATHS = [
  '${{ runner.temp }}/ui-summaries/tier-A/*/run-summary.json',
  '${{ runner.temp }}/ui-summaries/tier-A/*/junit.xml',
  '${{ runner.temp }}/ui-summaries/tier-A/*/upload-manifest.json',
  '${{ runner.temp }}/ui-summaries/tier-A/*/failures/**/screenshot.png',
  '${{ runner.temp }}/ui-summaries/tier-A/*/failures/**/console.log',
  '${{ runner.temp }}/ui-summaries/tier-A/*/failures/**/failure.json',
];

function readWorkflow() {
  return readFileSync(WORKFLOW, 'utf8');
}

function findStep(job, predicate) {
  return job.steps.find(predicate);
}

function stepIndex(job, predicate) {
  return job.steps.findIndex(predicate);
}

test('the UI smoke workflow declares pull requests, master pushes, manual dispatch and a daily schedule', () => {
  const triggers = parseTriggers(readWorkflow());

  assert.deepEqual(Object.keys(triggers).sort(), [
    'pull_request',
    'push',
    'schedule',
    'workflow_dispatch',
  ]);
  assert.deepEqual(triggers.pull_request.branches, ['master', 'feat/*']);
  assert.deepEqual(triggers.push.branches, ['master']);
  assert.equal(triggers.workflow_dispatch.inputs.diagnostic_retry.type, 'boolean');
  assert.equal(triggers.workflow_dispatch.inputs.diagnostic_retry.default, 'false');
  assert.ok(triggers.schedule.length > 0, 'a schedule trigger is required');
  assert.match(triggers.schedule[0].cron, /^\d{1,2} \d{1,2} \* \* \*$/, 'the schedule must run daily');
});

test('the workflow checks out master for scheduled runs and keeps the switch at job level', () => {
  const raw = readWorkflow();
  const job = parseJob(raw);
  const checkout = findStep(job, (step) => step.uses === 'actions/checkout@v4');
  const runner = findStep(job, (step) => /scripts\/ui-smoke\.sh/.test(step.run ?? ''));

  assert.ok(checkout, 'the UI job must check out the repository');
  assert.match(checkout.with.ref, /github\.event_name == 'schedule'/);
  assert.match(checkout.with.ref, /master/);
  assert.equal(job.name, 'UI Smoke (Tier A)');
  assert.match(job.if, /vars\.TESTS_UI_ENABLED == 'true'/);
  assert.equal(job['continue-on-error'], 'false');
  assert.equal(job.env.TESTS_UI_ENABLED, '${{ vars.TESTS_UI_ENABLED }}');

  assert.ok(runner, 'the job must invoke the shared runner');
  assert.equal(runner.env.SP_UI_SUMMARY_DIR, '${{ runner.temp }}/ui-summaries/tier-A');
  assert.match(
    runner.env.SP_UI_DIAGNOSTIC_RETRY,
    /inputs\.diagnostic_retry/,
    'CI retries must default to zero and require explicit dispatch opt-in',
  );
});

test('the workflow uses least privilege and no pull_request_target or secrets', () => {
  const raw = readWorkflow();
  const permissions = parsePermissions(raw);

  assert.equal(permissions.contents, 'read');
  assert.doesNotMatch(raw, /pull_request_target/);
  assert.doesNotMatch(raw, /secrets\./);
});

test('the workflow bounds setup separately from the spec run and the whole job', () => {
  const job = parseJob(readWorkflow());
  const setupBudgetIndex = stepIndex(job, (step) => /Enforce setup budget/.test(step.name ?? ''));
  const runnerIndex = stepIndex(job, (step) => /scripts\/ui-smoke\.sh/.test(step.run ?? ''));
  const runner = job.steps[runnerIndex];
  const setupBudget = job.steps[setupBudgetIndex];
  const jobSeconds = Number(job['timeout-minutes']) * 60;
  const runnerSeconds = Number(runner['timeout-minutes']) * 60;
  const setupSeconds = Number(setupBudget?.env?.UI_SMOKE_SETUP_BUDGET_SECONDS);

  assert.equal(job['timeout-minutes'], '45');
  assert.equal(runner['timeout-minutes'], '20');
  assert.ok(setupBudgetIndex !== -1, 'the workflow must enforce a dedicated setup budget');
  assert.ok(
    setupBudgetIndex > runnerIndex,
    'the setup budget must be checked after the runner so it includes provisioning',
  );
  assert.ok(Number.isFinite(setupSeconds) && setupSeconds > 0, 'the setup budget must be an explicit number of seconds');
  assert.notEqual(setupSeconds, runnerSeconds, 'the setup bound must differ from the spec/run timeout');
  assert.notEqual(setupSeconds, jobSeconds, 'the setup bound must differ from the job timeout');
  assert.equal(setupBudget['timeout-minutes'], '1');

  for (const [index, step] of job.steps.entries()) {
    if (index >= runnerIndex) {
      break;
    }

    assert.ok(
      Number.isFinite(Number(step['timeout-minutes'])),
      `setup step "${step.name ?? step.uses}" must declare an explicit timeout-minutes`,
    );
  }
});

test('the workflow stamps setup start before the runner and evaluates the runner pre-spec boundary', () => {
  const job = parseJob(readWorkflow());
  const started = stepIndex(job, (step) => /UI_SMOKE_SETUP_STARTED_MS/.test(step.run ?? ''));
  const runner = stepIndex(job, (step) => /scripts\/ui-smoke\.sh/.test(step.run ?? ''));
  const evaluation = findStep(job, (step) => /ci-report\.mjs/.test(step.run ?? ''));

  assert.ok(started !== -1, 'the workflow must stamp the setup start');
  assert.ok(runner !== -1, 'the workflow must invoke the runner');
  assert.ok(evaluation, 'the workflow must evaluate the retained summary');
  assert.ok(started < runner, 'setup start must precede the runner');
  assert.match(evaluation.run, /--setup-started-ms/);
  assert.match(evaluation.run, /--setup-finished-ms/);
  assert.match(
    evaluation.run,
    /UI_SMOKE_SPECS_STARTED_MS/,
    'the evaluator must use the runner pre-spec stamp, which includes provisioning',
  );
  assert.doesNotMatch(
    evaluation.run,
    /UI_SMOKE_SETUP_FINISHED_MS/,
    'the evaluator must not fall back to a pre-runner boundary',
  );
});

test('the setup guard runs after the runner so provisioning is included', () => {
  const job = parseJob(readWorkflow());
  const guardIndex = stepIndex(job, (step) => /Enforce setup budget/.test(step.name ?? ''));
  const runnerIndex = stepIndex(job, (step) => /scripts\/ui-smoke\.sh/.test(step.run ?? ''));

  assert.ok(guardIndex !== -1, 'the workflow must enforce a dedicated setup budget');
  assert.ok(runnerIndex !== -1, 'the workflow must invoke the shared runner');
  assert.ok(
    guardIndex > runnerIndex,
    'the setup guard must run after the runner so DB start, install, seeding and readiness are included',
  );
  assert.equal(job.steps[guardIndex].if, 'always()', 'the guard must evaluate even when the runner fails');

  const guardRun = job.steps[guardIndex].run;

  assert.match(
    guardRun,
    /scripts\/ci\/check-setup-budget\.sh/,
    'the guard must call the tested setup-budget script',
  );
  assert.equal(
    job.steps[guardIndex].env.UI_SMOKE_SETUP_BUDGET_SECONDS,
    '1800',
    'the guard must pass the explicit setup budget to the script',
  );
  assert.doesNotMatch(
    guardRun,
    /UI_SMOKE_SETUP_FINISHED_MS/,
    'the guard must not fall back to the pre-runner boundary',
  );
});

test('the setup evaluator prefers the runner pre-spec stamp and does not fall back to the pre-runner boundary', () => {
  const job = parseJob(readWorkflow());
  const evaluation = findStep(job, (step) => /ci-report\.mjs/.test(step.run ?? ''));

  assert.ok(evaluation, 'the workflow must evaluate the retained summary');
  assert.match(evaluation.run, /--setup-started-ms/);
  assert.match(evaluation.run, /--setup-finished-ms/);
  assert.match(evaluation.run, /--setup-budget-seconds/);
  assert.match(evaluation.run, /UI_SMOKE_SPECS_STARTED_MS/);
  assert.doesNotMatch(
    evaluation.run,
    /UI_SMOKE_SETUP_FINISHED_MS/,
    'a missing runner stamp must fail instead of qualifying through the pre-runner boundary',
  );
});

test('the pre-run fallback condition covers cancellation as well as failure', () => {
  const job = parseJob(readWorkflow());
  const failureReport = findStep(job, (step) => /ui_smoke_ci_setup_report/.test(step.run ?? ''));

  assert.ok(failureReport, 'a pre-run failure step must use the shared setup report helper');
  assert.match(failureReport.if, /cancelled\(\)/, 'cancellation must still retain setup-failed evidence');
  assert.match(failureReport.if, /failure\(\)/, 'pre-run failures must still retain setup-failed evidence');
});

test('the upload gate publishes a failed-but-valid set and skips a cancelled run without evidence', () => {
  const job = parseJob(readWorkflow());
  const verificationIndex = stepIndex(job, (step) =>
    /ui_smoke_verify_single_summary_root/.test(step.run ?? ''),
  );
  const upload = findStep(job, (step) => step.uses === 'actions/upload-artifact@v4');
  const verification = job.steps[verificationIndex];

  assert.ok(verification, 'the workflow must verify the retained set');
  assert.equal(verification.if, 'always()');
  assert.equal(upload.if, `always() && steps.${verification.id}.outcome == 'success'`);
  assert.doesNotMatch(
    upload.if,
    /success\(\)/,
    'a genuine Tier A failure must still upload its complete verified evidence set',
  );
  assert.doesNotMatch(
    upload.if,
    /failure\(\)/,
    'upload must depend on verification, not on the earlier step outcome',
  );
});

test('a pre-run failure produces sanitized setup-failed evidence through the shared helpers', () => {
  const job = parseJob(readWorkflow());
  const failureReport = findStep(job, (step) => /ui_smoke_ci_setup_report/.test(step.run ?? ''));

  assert.ok(failureReport, 'a pre-run failure step must use the shared setup report helper');
  assert.match(failureReport.if, /failure\(\)/, 'pre-run failures must retain setup-failed evidence');
  assert.match(failureReport.if, /cancelled\(\)/, 'cancellation must retain setup-failed evidence');
  assert.match(failureReport.run, /scripts\/lib\/ui-smoke-report\.sh/);
});

test('the workflow uploads only after verification succeeds and only the explicit sanitized allowlist', () => {
  const job = parseJob(readWorkflow());
  const verificationIndex = stepIndex(job, (step) =>
    /ui_smoke_verify_single_summary_root/.test(step.run ?? ''),
  );
  const uploadIndex = stepIndex(job, (step) => step.uses === 'actions/upload-artifact@v4');
  const verification = job.steps[verificationIndex];
  const upload = job.steps[uploadIndex];

  assert.ok(verification, 'the workflow must fail when the retained evidence set is incomplete');
  assert.ok(upload, 'the workflow must publish the retained evidence');
  assert.ok(verificationIndex < uploadIndex, 'verification must run before upload');
  assert.ok(verification.id, 'the verification step must expose an id for the upload gate');
  assert.equal(verification.if, 'always()');
  assert.equal(
    upload.if,
    `always() && steps.${verification.id}.outcome == 'success'`,
    'upload must be gated on verification success so partial or duplicate sets are never published',
  );

  const uploadedPaths = String(upload.with.path)
    .split('\n')
    .map((line) => line.trim())
    .filter(Boolean);

  assert.deepEqual(uploadedPaths, EVIDENCE_PATHS);
  assert.ok(
    uploadedPaths.every((uploaded) => !uploaded.endsWith('/')),
    'the upload must not publish a whole directory',
  );
  assert.equal(upload.with['retention-days'], '90');
  assert.equal(upload.with['if-no-files-found'], 'error');
});

test('the parser fails loudly when a required section is missing or unparsable', () => {
  assert.throws(
    () => parseWorkflow('permissions:\n  contents: read\njobs:\n  ui:\n    runs-on: ubuntu-latest\n'),
    /declare triggers/,
    'a missing on: section must not parse as an empty trigger set',
  );

  assert.throws(
    () => parseWorkflow('on:\n  push:\n    branches: [ master ]\n'),
    /declare a ui job/,
    'a missing ui job must not parse as an empty job',
  );

  assert.throws(
    () =>
      parseWorkflow(
        [
          'on:',
          '  push:',
          '    branches: [ master ]',
          'jobs:',
          '  ui:',
          '    runs-on: ubuntu-latest',
          '    steps-typo:',
          '      - run: echo nope',
          '',
        ].join('\n'),
      ),
    /unexpected|unparsable|steps/i,
    'an unparsable job key must not be skipped',
  );

  assert.throws(
    () =>
      parseWorkflow(
        [
          'on:',
          '  push:',
          '    branches: [ master ]',
          'jobs:',
          '  ui:',
          '    runs-on: ubuntu-latest',
          '    steps:',
          '      - run: echo ok',
          '        malformed line without a key',
          '',
        ].join('\n'),
      ),
    /unexpected|unparsable/i,
    'an unparsable step line must not be skipped',
  );
});
