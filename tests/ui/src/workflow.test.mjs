import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { parseJob, parsePermissions, parseTriggers, parseWorkflow } from './workflow-parser.mjs';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const WORKFLOW = path.join(REPO_ROOT, '.github', 'workflows', 'ui-smoke.yml');
const SHARED_RUNNER = './.github/workflows/ui-smoke-run.yml';

function readWorkflow() {
  return readFileSync(WORKFLOW, 'utf8');
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

test('the workflow uses least privilege and no pull_request_target or secrets', () => {
  const raw = readWorkflow();
  const permissions = parsePermissions(raw);

  assert.equal(permissions.contents, 'read');
  assert.doesNotMatch(raw, /pull_request_target/);
  assert.doesNotMatch(raw, /secrets\./);
});

test('the Tier A workload calls the shared runner and keeps the execution switch', () => {
  const raw = readWorkflow();
  const job = parseJob(raw, 'ui-work');

  assert.equal(job.name, 'UI Smoke (Tier A) workload');
  assert.equal(job.uses, SHARED_RUNNER);
  assert.equal(job['runs-on'], undefined, 'a calling job must not declare a runner');
  assert.match(job.if, /vars\.TESTS_UI_ENABLED == 'true'/);
  assert.equal(job.with.tier, 'A');
  assert.match(
    job.with.diagnostic_retry,
    /inputs\.diagnostic_retry/,
    'CI retries must default to zero and require explicit dispatch opt-in',
  );
  assert.match(job.with.diagnostic_retry, /github\.event_name == 'workflow_dispatch'/);
  assert.equal(job.permissions.contents, 'read');
});

test('the stable Tier A check mirrors the shared runner result', () => {
  const raw = readWorkflow();
  const job = parseJob(raw, 'ui');
  const resultStep = job.steps[0];

  assert.equal(job.name, 'UI Smoke (Tier A)');
  assert.equal(job.needs, 'ui-work');
  assert.match(job.if, /always\(\)/);
  assert.match(job.if, /vars\.TESTS_UI_ENABLED == 'true'/);
  assert.equal(job['runs-on'], 'ubuntu-latest');
  assert.equal(job['timeout-minutes'], '5');
  assert.equal(job.steps.length, 1, 'the mirror job must only carry the result step');
  assert.ok(resultStep, 'the mirror job must have a result step');
  assert.equal(resultStep['continue-on-error'], undefined);
  assert.match(JSON.stringify(resultStep.env), /needs\['ui-work'\]\.result/);
  assert.match(resultStep.run, /set -euo pipefail/);
  assert.match(resultStep.run, /"\$WORK_RESULT" = "success"/);
});

test('the caller does not own the pipeline or its evidence', () => {
  const raw = readWorkflow();

  assert.doesNotMatch(raw, /scripts\/ui-smoke\.sh/);
  assert.doesNotMatch(raw, /docker build/);
  assert.doesNotMatch(raw, /ci-report\.mjs/);
  assert.doesNotMatch(raw, /upload-artifact/);
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
