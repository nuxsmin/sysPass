import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { parseJob, parsePermissions, parseTriggers } from './workflow-parser.mjs';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const WORKFLOW = path.join(REPO_ROOT, '.github', 'workflows', 'ui-workflow-tests.yml');
const SHARED_RUNNER = './.github/workflows/ui-smoke-run.yml';
const SWITCH = /vars\.TESTS_UI_WORKFLOW_ENABLED == 'true'/;

const CHECK_NAMES = {
  'ui-workflow-wiring': 'UI Workflow (navigation wiring, reporting-only)',
  'ui-workflow-write': 'UI Workflow (account creation, reporting-only)',
};

function read() {
  return readFileSync(WORKFLOW, 'utf8');
}

test('UI Workflow Tests runs only daily and manually, never for pull requests or pushes', () => {
  const triggers = parseTriggers(read());

  assert.deepEqual(Object.keys(triggers).sort(), ['schedule', 'workflow_dispatch']);
  assert.equal(triggers.pull_request, undefined, 'workflow tests must not be a pull-request check');
  assert.equal(triggers.push, undefined, 'workflow tests must not run on pushes');
  assert.equal(triggers.schedule.length, 1);
  assert.equal(triggers.schedule[0].cron, '27 5 * * *');
  assert.notEqual(triggers.schedule[0].cron, '17 4 * * *', 'the schedule must differ from Tier A');
  assert.notEqual(triggers.schedule[0].cron, '47 4 * * *', 'the schedule must differ from Tier B');
  assert.equal(triggers.workflow_dispatch.inputs.diagnostic_retry.type, 'boolean');
  assert.equal(triggers.workflow_dispatch.inputs.diagnostic_retry.default, 'false');
});

test('the workflow keeps least privilege and per-ref non-cancelling concurrency', () => {
  const raw = read();

  assert.equal(parsePermissions(raw).contents, 'read');
  assert.match(raw, /^concurrency:\s*$/m);
  assert.match(raw, /^\s+group: .*github\.ref/m);
  assert.match(raw, /^\s+cancel-in-progress: false$/m);
});

test('WIRING and WRITE workloads call the shared runner under the dedicated switch', () => {
  const raw = read();

  for (const [jobId, tier] of [
    ['ui-workflow-wiring-work', 'WIRING'],
    ['ui-workflow-write-work', 'WRITE'],
  ]) {
    const job = parseJob(raw, jobId);

    assert.equal(job.uses, SHARED_RUNNER);
    assert.equal(job['runs-on'], undefined, 'a calling job must not declare a runner');
    assert.equal(job.with.tier, tier);
    assert.match(job.if, SWITCH);
    assert.equal(job.permissions.contents, 'read');
  }

  assert.doesNotMatch(raw, /vars\.TESTS_UI_ENABLED/, 'workflow tests must use their own switch');
  assert.doesNotMatch(raw, /vars\.TESTS_UI_TIER_B_ENABLED/, 'workflow tests must not reuse the Tier B switch');
});

test('the stable mirror checks are distinct, reporting-only and gate on workload success', () => {
  const raw = read();
  const names = [];

  for (const [jobId, checkName] of Object.entries(CHECK_NAMES)) {
    const job = parseJob(raw, jobId);

    assert.equal(job.name, checkName);
    assert.match(job.name, /reporting-only/);
    assert.equal(job.needs, jobId === 'ui-workflow-wiring' ? 'ui-workflow-wiring-work' : 'ui-workflow-write-work');
    assert.match(job.if, /always\(\)/);
    assert.match(job.if, SWITCH);
    assert.equal(job['runs-on'], 'ubuntu-latest');
    assert.equal(job['timeout-minutes'], '5');
    assert.equal(job.steps.length, 1, 'the mirror job must only carry the result step');
    assert.match(JSON.stringify(job.steps[0].env), new RegExp(`${job.needs}.*result`));
    assert.match(job.steps[0].run, /"\$WORK_RESULT" = "success"/);
    assert.equal(job['continue-on-error'], undefined);

    names.push(job.name);
  }

  assert.equal(new Set(names).size, 2, 'the reporting check names must be distinct');
  assert.doesNotMatch(raw, /continue-on-error/);
});

test('the workflow declares no secrets and no inline pipeline', () => {
  const raw = read();

  assert.doesNotMatch(raw, /secrets\./);
  assert.doesNotMatch(raw, /scripts\/ui-smoke\.sh/);
  assert.doesNotMatch(raw, /ci-report\.mjs/);
  assert.doesNotMatch(raw, /upload-artifact/);
  assert.doesNotMatch(raw, /docker /);
});
