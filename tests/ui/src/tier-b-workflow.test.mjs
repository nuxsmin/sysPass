import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { parseJob, parsePermissions, parseTriggers } from './workflow-parser.mjs';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const TIER_A_WORKFLOW = path.join(REPO_ROOT, '.github', 'workflows', 'ui-smoke.yml');
const TIER_B_WORKFLOW = path.join(REPO_ROOT, '.github', 'workflows', 'ui-smoke-tier-b.yml');

function read(workflow) {
  return readFileSync(workflow, 'utf8');
}

function tierBJob(raw) {
  return parseJob(raw, 'ui-tier-b');
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

test('the Tier B job is gated by its own variable and selects only Tier B', () => {
  const raw = read(TIER_B_WORKFLOW);
  const job = tierBJob(raw);
  const runner = job.steps.find((step) => /scripts\/ui-smoke\.sh/.test(step.run ?? ''));

  assert.equal(job.name, 'UI Smoke (Tier B, annotation-only)');
  assert.match(job.if, /vars\.TESTS_UI_TIER_B_ENABLED == 'true'/);
  assert.doesNotMatch(job.if, /vars\.TESTS_UI_ENABLED/, 'Tier B must not depend on the Tier A switch');
  assert.equal(job.env.TESTS_UI_ENABLED, 'true');
  assert.equal(job.env.SP_UI_TIER, 'B');
  assert.ok(runner, 'the job must invoke the shared runner with the Tier B selection');
  assert.match(runner.env.SP_UI_SUMMARY_DIR, /ui-summaries\/tier-B$/);
  assert.equal(job['continue-on-error'], 'false', 'a Tier B failure must remain a failed job');
});

test('the Tier B report is annotation-only, tier-scoped and cannot evaluate Tier A evidence', () => {
  const raw = read(TIER_B_WORKFLOW);
  const job = tierBJob(raw);
  const evaluation = job.steps.find((step) => /ci-report\.mjs/.test(step.run ?? ''));

  assert.ok(evaluation, 'the job must evaluate its retained Tier B summary');
  assert.equal(evaluation.if, 'always()');
  assert.match(evaluation.run, /--tier B/);
  assert.doesNotMatch(evaluation.run, /--tier A/);
  assert.match(evaluation.run, /--summaries/);
  assert.match(evaluation.env.SP_UI_SUMMARY_DIR, /tier-B$/);
  assert.equal(evaluation.env.SP_UI_TIER, 'B');
});

test('Tier B evidence is uploaded separately from Tier A and never under the Tier A job artifact', () => {
  const tierARaw = read(TIER_A_WORKFLOW);
  const tierBRaw = read(TIER_B_WORKFLOW);
  const tierAJob = parseJob(tierARaw, 'ui');
  const tierB = tierBJob(tierBRaw);
  const upload = tierB.steps.find((step) => step.uses === 'actions/upload-artifact@v4');

  assert.ok(upload, 'Tier B evidence must be publishable');
  assert.match(upload.with.name, /ui-smoke-tier-b-/);
  assert.doesNotMatch(upload.with.name, /ui-smoke-tier-a-/);
  assert.doesNotMatch(tierARaw, /TESTS_UI_TIER_B_ENABLED|ui-smoke-tier-b/, 'Tier A must not depend on Tier B');
  assert.doesNotMatch(tierBRaw, /TESTS_UI_ENABLED ==/, 'Tier B must not manipulate the Tier A execution switch');
  assert.ok(parsePermissions(tierBRaw).contents === 'read');
  assert.equal(parsePermissions(tierARaw).contents, 'read');
  assert.equal(tierAJob['continue-on-error'], 'false', 'the separate Tier B workflow must not change Tier A');
});

test('the Tier B workflow keeps its own setup-failure evidence in the Tier B root', () => {
  const job = tierBJob(read(TIER_B_WORKFLOW));
  const failureReport = job.steps.find((step) => /ui_smoke_ci_setup_report/.test(step.run ?? ''));

  assert.ok(failureReport, 'a pre-run failure must still retain Tier B evidence');
  assert.match(failureReport.if, /failure\(\)/);
  assert.match(failureReport.if, /cancelled\(\)/);
  assert.match(failureReport.env.SP_UI_SUMMARY_DIR, /ui-summaries\/tier-B$/);
});
