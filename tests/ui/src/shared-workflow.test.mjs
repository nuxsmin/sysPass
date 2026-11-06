import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { parseJob, parsePermissions, parseTriggers } from './workflow-parser.mjs';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const MODULE = path.join(REPO_ROOT, '.github', 'workflows', 'ui-smoke-run.yml');

const SUMMARY = '${{ runner.temp }}/ui-summaries/tier-${{ inputs.tier }}';

const EVIDENCE_PATHS = [
  '*/run-summary.json',
  '*/junit.xml',
  '*/upload-manifest.json',
  '*/failures/**/screenshot.png',
  '*/failures/**/console.log',
  '*/failures/**/failure.json',
].map((suffix) => `${SUMMARY}/${suffix}`);

function read() {
  return readFileSync(MODULE, 'utf8');
}

function findStep(job, predicate) {
  return job.steps.find(predicate);
}

function stepIndex(job, predicate) {
  return job.steps.findIndex(predicate);
}

const isRunner = (step) => /scripts\/ui-smoke\.sh/.test(step.run ?? '');
const isSetupStamp = (step) => /UI_SMOKE_SETUP_STARTED_MS/.test(step.run ?? '');
const isSetupGuard = (step) => /Enforce setup budget/.test(step.name ?? '');
const isCheckout = (step) => step.uses === 'actions/checkout@v4';

test('the shared runner is callable only and declares its tier, retry and retention inputs', () => {
  const triggers = parseTriggers(read());

  assert.deepEqual(Object.keys(triggers), ['workflow_call']);
  assert.deepEqual(Object.keys(triggers.workflow_call.inputs).sort(), [
    'diagnostic_retry',
    'retention_days',
    'tier',
  ]);

  const { tier, diagnostic_retry: retry, retention_days: retention } = triggers.workflow_call.inputs;

  assert.equal(tier.type, 'string');
  assert.equal(tier.required, 'true');
  assert.equal(retry.type, 'boolean');
  assert.equal(retry.default, 'false');
  assert.equal(retention.type, 'number');
  assert.equal(retention.default, '90');
});

test('the shared runner uses least privilege and a single bounded job', () => {
  const raw = read();
  const job = parseJob(raw, 'run');

  assert.equal(parsePermissions(raw).contents, 'read');
  assert.equal(job.name, 'Run Tier ${{ inputs.tier }} UI smoke');
  assert.equal(job['runs-on'], 'ubuntu-latest');
  assert.equal(job['timeout-minutes'], '45');
  assert.equal(job.env.TESTS_UI_ENABLED, 'true');
  assert.match(job.env.SP_UI_TIER, /inputs\.tier/);
  assert.equal(job.env.PLAYWRIGHT_BROWSERS_PATH, '${{ github.workspace }}/tests/ui/.browsers');
});

test('the tier is validated after checkout and before the setup stamp', () => {
  const job = parseJob(read(), 'run');
  const checkoutIndex = stepIndex(job, isCheckout);
  const validationIndex = stepIndex(job, (step) => /UI_SMOKE_TIER_SLUG/.test(step.run ?? ''));
  const stampIndex = stepIndex(job, isSetupStamp);
  const validation = job.steps[validationIndex];

  assert.ok(checkoutIndex !== -1, 'the job must check out the repository');
  assert.ok(validation, 'the job must validate the requested tier');
  assert.ok(
    checkoutIndex < validationIndex,
    'validation must follow checkout so setup-failure evidence has a workspace',
  );
  assert.ok(validationIndex < stampIndex, 'validation must precede the setup start stamp');
  assert.equal(validation.env.REQUESTED_TIER, '${{ inputs.tier }}', 'validation must inspect the requested tier');
  assert.match(validation.run, /REQUESTED_TIER/);
  assert.match(validation.run, /exit 1/, 'an unknown tier must fail closed');
  assert.match(validation.run, /A\|B/, 'only exactly A or B may pass');
  assert.match(validation.run, /tr\b/, 'the slug must be derived with tr');
  assert.match(validation.run, /UI_SMOKE_TIER_SLUG/, 'the slug must be exported to later steps');
});

test('the checkout still pins master for scheduled runs', () => {
  const job = parseJob(read(), 'run');
  const checkout = findStep(job, isCheckout);

  assert.ok(checkout, 'the job must check out the repository');
  assert.match(checkout.with.ref, /github\.event_name == 'schedule'/);
  assert.match(checkout.with.ref, /master/);
  assert.equal(checkout['timeout-minutes'], '2');
});

test('the runner uses the derived summary root, tier and retry input', () => {
  const job = parseJob(read(), 'run');
  const runner = findStep(job, isRunner);

  assert.ok(runner, 'the job must invoke the shared runner');
  assert.equal(runner.env.SP_UI_SUMMARY_DIR, SUMMARY);
  assert.match(runner.env.SP_UI_DIAGNOSTIC_RETRY, /inputs\.diagnostic_retry/);
  assert.equal(runner['timeout-minutes'], '20');
});

test('the setup stamp precedes the runner and every setup step stays bounded', () => {
  const job = parseJob(read(), 'run');
  const stampIndex = stepIndex(job, isSetupStamp);
  const runnerIndex = stepIndex(job, isRunner);
  const jobSeconds = Number(job['timeout-minutes']) * 60;
  const runnerSeconds = Number(job.steps[runnerIndex]['timeout-minutes']) * 60;
  const guard = job.steps[stepIndex(job, isSetupGuard)];
  const setupSeconds = Number(guard?.env?.UI_SMOKE_SETUP_BUDGET_SECONDS);

  assert.ok(stampIndex !== -1, 'the job must stamp the setup start');
  assert.ok(stampIndex < runnerIndex, 'setup start must precede the runner');
  assert.equal(job['timeout-minutes'], '45');
  assert.equal(job.steps[runnerIndex]['timeout-minutes'], '20');
  assert.ok(setupSeconds > 0 && Number.isFinite(setupSeconds));
  assert.notEqual(setupSeconds, runnerSeconds, 'the setup bound must differ from the run timeout');
  assert.notEqual(setupSeconds, jobSeconds, 'the setup bound must differ from the job timeout');

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

test('npm caching covers both root and UI lockfiles', () => {
  const job = parseJob(read(), 'run');
  const node = findStep(job, (step) => step.uses === 'actions/setup-node@v4');

  assert.ok(node, 'the job must set up Node.js');
  assert.equal(node.with.cache, 'npm');
  const dependencyPaths = String(node.with['cache-dependency-path'])
    .split('\n')
    .map((line) => line.trim())
    .filter(Boolean);

  assert.ok(dependencyPaths.includes('package-lock.json'), 'the root lockfile must be cached');
  assert.ok(
    dependencyPaths.includes('tests/ui/package-lock.json'),
    'the UI harness lockfile must be cached',
  );
});

test('the PHP image builds through buildx with a GHA layer cache', () => {
  const job = parseJob(read(), 'run');
  const buildxIndex = stepIndex(job, (step) => step.uses === 'docker/setup-buildx-action@v3');
  const buildIndex = stepIndex(job, (step) => step.uses === 'docker/build-push-action@v6');
  const buildx = job.steps[buildxIndex];
  const build = job.steps[buildIndex];

  assert.ok(buildx, 'the job must set up Docker Buildx');
  assert.ok(build, 'the job must build the PHP image with build-push-action');
  assert.ok(buildxIndex < buildIndex, 'Buildx setup must precede the image build');

  assert.equal(build.with.context, 'scripts/docker');
  assert.equal(build.with.file, 'scripts/docker/Dockerfile.php-testing');
  assert.match(build.with['build-args'], /COMPOSER_VERSION=2\.7/);
  assert.equal(build.with.tags, 'php-testing-composer:latest');
  assert.equal(build.with.load, 'true');
  assert.match(build.with['cache-from'], /type=gha/);
  assert.match(build.with['cache-from'], /scope=php-testing-composer/);
  assert.match(build.with['cache-to'], /type=gha/);
  assert.match(build.with['cache-to'], /mode=max/);
  assert.match(build.with['cache-to'], /scope=php-testing-composer/);
});

test('the image verification follows the build and checks PHP and Composer', () => {
  const job = parseJob(read(), 'run');
  const buildIndex = stepIndex(job, (step) => step.uses === 'docker/build-push-action@v6');
  const verifyIndex = stepIndex(job, (step) => /Verify the PHP 8\.3 test image/.test(step.name ?? ''));
  const verify = job.steps[verifyIndex];

  assert.ok(verify, 'the job must verify the built image');
  assert.ok(buildIndex < verifyIndex, 'verification must follow the build');
  assert.match(verify.run, /docker run --rm --entrypoint php php-testing-composer:latest -r/);
  assert.match(verify.run, /PHP_VERSION_ID >= 80300/);
  assert.match(verify.run, /docker run --rm --entrypoint php php-testing-composer:latest -v/);
});

test('the Composer cache is restored and mounted into the install container', () => {
  const job = parseJob(read(), 'run');
  const cacheIndex = stepIndex(job, (step) => /Restore Composer cache/.test(step.name ?? ''));
  const installIndex = stepIndex(job, (step) => /Install Composer dependencies/.test(step.name ?? ''));
  const cache = job.steps[cacheIndex];
  const install = job.steps[installIndex];

  assert.ok(cache, 'the job must restore the Composer cache');
  assert.ok(install, 'the job must install Composer dependencies');
  assert.ok(cacheIndex < installIndex, 'the Composer cache must be restored before install');
  assert.equal(cache.uses, 'actions/cache@v4');
  assert.match(cache.with.path, /runner\.temp/);
  assert.match(cache.with.path, /composer-cache/);
  assert.match(cache.with.key, /composer\.lock/);
  assert.equal(cache.with['restore-keys'], 'composer-${{ runner.os }}-');

  assert.match(install.run, /COMPOSER_CACHE_DIR=\/tmp\/composer-cache/);
  assert.match(install.run, /runner\.temp \}\}\/composer-cache:\/tmp\/composer-cache/);
});

test('the Playwright browser cache is restored before the UI harness install', () => {
  const job = parseJob(read(), 'run');
  const cacheIndex = stepIndex(job, (step) => /Restore Playwright browsers/.test(step.name ?? ''));
  const installIndex = stepIndex(job, (step) =>
    /Install locked UI harness and pinned Chromium/.test(step.name ?? ''),
  );
  const cache = job.steps[cacheIndex];

  assert.ok(cache, 'the job must restore Playwright browsers');
  assert.ok(cacheIndex < installIndex, 'the browser cache must be restored before the harness install');
  assert.equal(cache.uses, 'actions/cache@v4');
  assert.equal(cache.with.path, 'tests/ui/.browsers');
  assert.match(cache.with.key, /tests\/ui\/package-lock\.json/);
  assert.equal(cache.with['restore-keys'], 'playwright-${{ runner.os }}-');
});

test('MariaDB stays runner-owned and is pulled per job', () => {
  const raw = read();
  const job = parseJob(raw, 'run');
  const pull = findStep(job, (step) => /Pull disposable MariaDB 10\.3/.test(step.name ?? ''));

  assert.ok(pull, 'the job must pull MariaDB explicitly');
  assert.equal(pull.run, 'docker pull mariadb:10.3');
  assert.doesNotMatch(raw, /^\s*services:\s*$/m, 'a job service must not replace the run-owned database');
  assert.doesNotMatch(raw, /mariadb:10\.3\s*\n\s*env:/, 'MariaDB must not be declared as a job service');
});

test('the setup guard runs after the runner so provisioning is included', () => {
  const job = parseJob(read(), 'run');
  const guardIndex = stepIndex(job, isSetupGuard);
  const runnerIndex = stepIndex(job, isRunner);
  const guard = job.steps[guardIndex];

  assert.ok(guard, 'the job must enforce a dedicated setup budget');
  assert.ok(guardIndex > runnerIndex, 'the setup guard must run after the runner');
  assert.equal(guard.if, 'always()');
  assert.equal(guard['timeout-minutes'], '1');
  assert.equal(guard.env.UI_SMOKE_SETUP_BUDGET_SECONDS, '1800');
  assert.match(guard.run, /scripts\/ci\/check-setup-budget\.sh/);
});

test('a pre-run failure produces tier-scoped sanitized evidence through the shared helper', () => {
  const job = parseJob(read(), 'run');
  const failureReport = findStep(job, (step) => /ui_smoke_ci_setup_report/.test(step.run ?? ''));

  assert.ok(failureReport, 'a pre-run failure step must use the shared setup report helper');
  assert.match(failureReport.if, /cancelled\(\)/, 'cancellation must retain setup-failed evidence');
  assert.match(failureReport.if, /failure\(\)/, 'pre-run failures must retain setup-failed evidence');
  assert.match(failureReport.run, /scripts\/lib\/ui-smoke-report\.sh/);
  assert.equal(failureReport.env.SP_UI_SUMMARY_DIR, SUMMARY);
  assert.match(failureReport.env.SP_UI_SETUP_ERROR, /UI smoke workflow failed before the runner retained Playwright evidence\./);
  assert.match(
    failureReport.env.SP_UI_SETUP_ERROR,
    /UI smoke Tier B workflow failed before the runner retained Playwright evidence\./,
  );
  assert.match(failureReport.env.SP_UI_SETUP_RUN_ID, /ui-smoke\.ci-setup-/);
  assert.match(failureReport.env.SP_UI_SETUP_RUN_ID, /ui-smoke\.tier-b-ci-setup-/);
  assert.match(failureReport.env.SP_UI_SETUP_RUN_ID, /github\.run_id/);
  assert.match(failureReport.env.SP_UI_SETUP_RUN_ID, /github\.run_attempt/);
  assert.match(failureReport.run, /run_id="\$\{SP_UI_SETUP_RUN_ID/, 'the tier run-ID prefix must be used');
});

test('the evaluator passes the tier, budgets and both setup stamps', () => {
  const job = parseJob(read(), 'run');
  const evaluation = findStep(job, (step) => /ci-report\.mjs/.test(step.run ?? ''));

  assert.ok(evaluation, 'the job must evaluate the retained summary');
  assert.equal(evaluation.if, 'always()');
  assert.equal(evaluation.env.SP_UI_SUMMARY_DIR, SUMMARY);
  assert.match(evaluation.run, /--tier "\$\{\{ inputs\.tier \}\}"/);
  assert.match(evaluation.run, /--summaries "\$SP_UI_SUMMARY_DIR"/);
  assert.match(evaluation.run, /--budget-seconds 300/);
  assert.match(evaluation.run, /--setup-budget-seconds 1800/);
  assert.match(evaluation.run, /--setup-started-ms/);
  assert.match(evaluation.run, /--setup-finished-ms/);
  assert.match(evaluation.run, /UI_SMOKE_SPECS_STARTED_MS/);
  assert.doesNotMatch(
    evaluation.run,
    /UI_SMOKE_SETUP_FINISHED_MS/,
    'a missing runner stamp must fail instead of qualifying through the pre-runner boundary',
  );
});

test('the upload is gated on verification and publishes only the tier allowlist', () => {
  const job = parseJob(read(), 'run');
  const verificationIndex = stepIndex(job, (step) =>
    /ui_smoke_verify_single_summary_root/.test(step.run ?? ''),
  );
  const uploadIndex = stepIndex(job, (step) => step.uses === 'actions/upload-artifact@v4');
  const verification = job.steps[verificationIndex];
  const upload = job.steps[uploadIndex];

  assert.ok(verification, 'the job must verify the retained set');
  assert.ok(upload, 'the job must publish the retained evidence');
  assert.ok(verificationIndex < uploadIndex, 'verification must run before upload');
  assert.ok(verification.id, 'the verification step must expose an id for the upload gate');
  assert.equal(verification.if, 'always()');
  assert.equal(verification.env.SP_UI_SUMMARY_DIR, SUMMARY);
  assert.equal(upload.if, `always() && steps.${verification.id}.outcome == 'success'`);
  assert.doesNotMatch(upload.if, /success\(\)/, 'a genuine failure must still upload verified evidence');
  assert.doesNotMatch(upload.if, /failure\(\)/, 'upload must depend on verification, not earlier outcomes');
  assert.equal(
    upload.with.name,
    'ui-smoke-tier-${{ env.UI_SMOKE_TIER_SLUG }}-${{ github.sha }}-${{ github.run_id }}',
  );
  assert.equal(upload.with['retention-days'], '${{ inputs.retention_days }}');
  assert.equal(upload.with['if-no-files-found'], 'error');

  const uploadedPaths = String(upload.with.path)
    .split('\n')
    .map((line) => line.trim())
    .filter(Boolean);

  assert.deepEqual(uploadedPaths, EVIDENCE_PATHS);
  assert.ok(
    uploadedPaths.every((uploaded) => !uploaded.endsWith('/')),
    'the upload must not publish a whole directory',
  );
});
