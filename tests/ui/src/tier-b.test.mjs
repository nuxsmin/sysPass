import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { PageDiagnostics } from './diagnostics.mjs';
import { summarizeTest } from './summary.mjs';
import { TIER_B_SPECS } from './tiers.mjs';

const TIER_B_INDEXES = [
  ['accessManager/index', 'accessManager.spec.ts'],
  ['itemManager/index', 'itemManager.spec.ts'],
  ['securityManager/index', 'securityManager.spec.ts'],
  ['configManager/index', 'configManager.spec.ts'],
  ['notification/index', 'notification.spec.ts'],
  ['userSettingsManager/index', 'userSettingsManager.spec.ts'],
  ['plugin/index', 'plugin.spec.ts'],
];

function makeDetector() {
  return new PageDiagnostics({
    quietWindowMs: 20,
    timeoutMs: 80,
    sleep: (ms) => new Promise((resolve) => setTimeout(resolve, ms)),
  });
}

for (const [route, spec] of TIER_B_INDEXES) {
  test(`a suppressed ${route} initializer fails with the spec and route named`, async () => {
    const detector = makeDetector();
    detector.setContract({
      name: route,
      actions: [{ route, format: 'html', content: [`data-tab-route="${route}"`] }],
      conditions: [],
    });

    let failure;

    await assert.rejects(
      () => detector.waitForActions([route], 0, Date.now() + 40),
      (error) => {
        failure = error;
        return error.message.includes(route);
      },
    );

    const summary = summarizeTest({
      title: `${route} index loads in-shell`,
      file: `tests/ui/specs/${spec}`,
      attempts: [{ status: 'failed', durationMs: 40, error: { message: failure.message } }],
    });

    assert.equal(summary.file, `tests/ui/specs/${spec}`);
    assert.equal(summary.attempts[0].firstAttempt, true);
    assert.equal(summary.attempts[0].route, route);
    assert.match(summary.attempts[0].errorMessage, new RegExp(route.replace('/', '\\/')));
  });
}

test('a removed Tier B route response is attributed to its route', () => {
  const route = 'accessManager/index';
  const detector = makeDetector();
  detector.setContract({
    name: route,
    actions: [{ route, format: 'html', content: ['id="data-table-tblUsers"'] }],
    conditions: [],
  });

  detector.observeRequestStart({
    id: '1',
    url: `http://127.0.0.1:1234/index.php?r=${route}&isAjax=1`,
    method: 'GET',
    resourceType: 'xhr',
  });
  detector.observeResponse({
    id: '1',
    url: `http://127.0.0.1:1234/index.php?r=${route}&isAjax=1`,
    status: 404,
    headers: {},
  });
  detector.observeBody({ id: '1', body: '<div id="content"></div>' });

  const problems = detector.problems();

  assert.ok(problems.length > 0);
  assert.ok(
    problems.some((problem) => problem.kind === 'http' && problem.route === route),
    'the removed route must be attributed to accessManager/index',
  );
  assert.ok(
    problems.some(
      (problem) =>
        problem.kind === 'missing-content' &&
        problem.route === route &&
        /data-table-tblUsers/.test(problem.message),
    ),
    'an empty/stale response must name the missing index content',
  );
});
const UI_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const SPECS_DIR = path.join(UI_DIR, 'specs');
const TIER_B_SOURCE = path.join(UI_DIR, 'src', 'tier-b.ts');
const ACCESS_MANAGER_SPEC = path.join(SPECS_DIR, 'accessManager.spec.ts');

test('every Tier B spec uses the shared live session instead of logging in per spec', () => {
  for (const spec of TIER_B_SPECS) {
    const source = readFileSync(path.join(SPECS_DIR, spec), 'utf8');

    assert.match(source, /sessionPage/, `${spec} must use the live cookie-carrying fixture`);
    assert.doesNotMatch(source, /isolatedPage/, `${spec} must not create an anonymous page per spec`);
    assert.doesNotMatch(source, /loginThroughUi/, `${spec} must not log in for every spec`);
    assert.match(source, /openSeededShell|runTierBIndex/, `${spec} must use the shared seeded shell helper`);
  }

  const helper = readFileSync(TIER_B_SOURCE, 'utf8');

  assert.match(helper, /authenticated/, 'the helper must track the one-time authenticated worker session');
  assert.match(helper, /openSeededShellPage/, 'the helper must reuse the shared live-cookie shell opener');
});

test('the access-manager spec contains a scratch-only browser fault canary that restores its route', () => {
  const spec = readFileSync(ACCESS_MANAGER_SPEC, 'utf8');
  const helper = readFileSync(TIER_B_SOURCE, 'utf8');

  assert.match(spec, /SP_UI_TIER_B_FAULT === 'accessManager\/index'/, 'the canary must be explicitly opt-in');
  assert.match(spec, /proveTierBFaultCanary/, 'the browser canary must run the real navigation helper');
  assert.match(helper, /proveTierBFaultCanary/, 'the canary proof must live in the shared Tier B helper');
  assert.match(helper, /\.route\(/, 'the canary must suppress a real browser route');
  assert.match(helper, /\.unroute\(/, 'the suppression must be removed before the proof completes');
});
