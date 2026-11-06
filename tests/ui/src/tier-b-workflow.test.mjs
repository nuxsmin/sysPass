import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { parseJob, parsePermissions, parseTriggers } from './workflow-parser.mjs';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const TIER_A_WORKFLOW = path.join(REPO_ROOT, '.github', 'workflows', 'ui-smoke.yml');
const TIER_B_WORKFLOW = path.join(REPO_ROOT, '.github', 'workflows', 'ui-smoke-tier-b.yml');
const SHARED_RUNNER = './.github/workflows/ui-smoke-run.yml';

function read(workflow) {
  return readFileSync(workflow, 'utf8');
}

test('Tier B history is a separate daily and manual workflow, never a pull-request gate', () => {
  const raw = read(TIER_B_WORKFLOW);
  const triggers = parseTriggers(raw);

  assert.deepEqual(Object.keys(triggers).sort(), ['schedule', 'workflow_dispatch']);
  assert.ok(triggers.schedule.length > 0, 'Tier B must run on a schedule');
  assert.match(triggers.schedule[0].cron, /^\d{1,2} \d{1,2} \* \* \*$/, 'the schedule must run daily');
  assert.equal(triggers.pull_request, undefined, 'Tier B must not be a pull-request check');
  assert.equal(triggers.push, undefined, 'Tier B must not run on pushes');
});

test('the Tier B workload calls the shared runner under its own switch', () => {
  const raw = read(TIER_B_WORKFLOW);
  const job = parseJob(raw, 'ui-tier-b-work');

  assert.equal(job.name, 'UI Smoke (Tier B) workload');
  assert.equal(job.uses, SHARED_RUNNER);
  assert.equal(job['runs-on'], undefined, 'a calling job must not declare a runner');
  assert.equal(job.with.tier, 'B');
  assert.equal(job.with.diagnostic_retry, undefined, 'Tier B must not request a diagnostic retry');
  assert.equal(job.permissions.contents, 'read');

  // TESTS_UI_TIER_B_ENABLED is an execution switch for this history only. It
  // is not a merge gate and never controls Tier A. An unset or non-true value
  // skips this workflow without producing Tier B evidence.
  assert.match(job.if, /vars\.TESTS_UI_TIER_B_ENABLED == 'true'/);
  assert.doesNotMatch(job.if, /vars\.TESTS_UI_ENABLED/, 'Tier B must not depend on the Tier A switch');
});

test('the stable Tier B check mirrors the shared runner result', () => {
  const job = parseJob(read(TIER_B_WORKFLOW), 'ui-tier-b');
  const resultStep = job.steps[0];

  assert.equal(job.name, 'UI Smoke (Tier B, annotation-only)');
  assert.equal(job.needs, 'ui-tier-b-work');
  assert.match(job.if, /always\(\)/);
  assert.match(job.if, /vars\.TESTS_UI_TIER_B_ENABLED == 'true'/);
  assert.equal(job['runs-on'], 'ubuntu-latest');
  assert.equal(job['timeout-minutes'], '5');
  assert.equal(job.steps.length, 1, 'the mirror job must only carry the result step');
  assert.ok(resultStep, 'the mirror job must have a result step');
  assert.match(JSON.stringify(resultStep.env), /needs\['ui-tier-b-work'\]\.result/);
  assert.match(resultStep.run, /set -euo pipefail/);
  assert.match(resultStep.run, /"\$WORK_RESULT" = "success"/);
});

test('Tier A and Tier B callers stay independent and read-only', () => {
  const tierARaw = read(TIER_A_WORKFLOW);
  const tierBRaw = read(TIER_B_WORKFLOW);

  assert.doesNotMatch(tierARaw, /TESTS_UI_TIER_B_ENABLED|ui-smoke-tier-b/, 'Tier A must not depend on Tier B');
  assert.doesNotMatch(tierBRaw, /TESTS_UI_ENABLED ==/, 'Tier B must not manipulate the Tier A execution switch');
  assert.equal(parsePermissions(tierARaw).contents, 'read');
  assert.equal(parsePermissions(tierBRaw).contents, 'read');
});

test('the Tier B caller does not own the pipeline or its evidence', () => {
  const raw = read(TIER_B_WORKFLOW);

  assert.doesNotMatch(raw, /scripts\/ui-smoke\.sh/);
  assert.doesNotMatch(raw, /scripts\/lib\/ui-smoke-report\.sh/);
  assert.doesNotMatch(raw, /docker build/);
  assert.doesNotMatch(raw, /ci-report\.mjs/);
  assert.doesNotMatch(raw, /upload-artifact/);
});
