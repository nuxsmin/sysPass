import assert from 'node:assert/strict';
import { mkdtempSync, readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import test from 'node:test';

import { buildFailureRecord } from './failure-evidence.mjs';
import { loginThroughUiFlow } from './login-flow.mjs';
import { buildLoginRedirectError, loginRedirectFailureMessage } from './login-failure.mjs';
import {
  TIER_A_SPECS,
  buildRunSummary,
  renderJUnit,
  resolveSelection,
  summarizeTest,
} from './summary.mjs';

const CANARY = 'CANARY_LOGIN_RESPONSE_BODY_9f4c2b';

function responseWithBody(
  body = CANARY,
  {
    status = 200,
    contentType = 'text/html; charset=UTF-8',
    url = 'http://127.0.0.1:1234/index.php?r=login/login',
  } = {},
) {
  let textCalls = 0;

  return {
    url: () => url,
    status: () => status,
    headers: () => ({ 'content-type': contentType }),
    text: async () => {
      textCalls += 1;
      return body;
    },
    textCalls: () => textCalls,
  };
}

function fakeLoginPage({ boot, login }) {
  const locator = {
    waitFor: async () => {},
    fill: async () => {},
    press: async () => {},
    click: async () => {},
  };

  return {
    waitForResponse: async (predicate) => {
      if (predicate(boot)) {
        return boot;
      }

      if (predicate(login)) {
        return login;
      }

      throw new Error('unexpected response wait');
    },
    goto: async () => {},
    locator: () => locator,
    waitForFunction: async () => {},
    waitForURL: async () => {
      throw new Error('redirect timeout');
    },
  };
}

function completeRunSummary(error) {
  return buildRunSummary({
    runId: 'sp-ui-smoke.canary-20261106T000000Z',
    selection: resolveSelection({
      tier: 'A',
      specs: '',
      grep: false,
      availableSpecs: TIER_A_SPECS,
    }),
    commit: { sha: 'a'.repeat(40), short: 'aaaaaaaaa', dirty: false, branch: 'master' },
    environment: {
      ci: false,
      node: 'v20.19.2',
      platform: 'linux',
      playwright: '1.63.0',
      tier: 'A',
    },
    tests: [
      summarizeTest({
        title: 'anonymous login',
        file: 'login.spec.ts',
        attempts: [
          {
            status: 'failed',
            durationMs: 1,
            error: { message: error.message, stack: error.stack },
          },
        ],
      }),
    ],
    startedAt: '2026-11-06T00:00:00.000Z',
    finishedAt: '2026-11-06T00:01:00.000Z',
    durationMs: 60_000,
    resultStatus: 'failed',
  });
}

test('a failed login redirect reports route, status and content-type without the body', () => {
  const response = responseWithBody();
  const error = buildLoginRedirectError(response);

  assert.equal(response.textCalls(), 0, 'the login failure must not read the response body');
  assert.doesNotMatch(error.message, new RegExp(CANARY));
  assert.match(error.message, /route login\/login/);
  assert.match(error.message, /HTTP 200/);
  assert.match(error.message, /content-type text\/html/);
  assert.match(error.message, /response body omitted/);
});

test('unexpected content-type text is not treated as safe context', () => {
  const error = buildLoginRedirectError(
    responseWithBody(CANARY, { contentType: `${CANARY}; charset=UTF-8` }),
  );

  assert.doesNotMatch(error.message, new RegExp(CANARY));
  assert.match(error.message, /content-type unknown/);
});

test('the login body canary never reaches failure.json, the summary or JUnit', () => {
  const error = buildLoginRedirectError(responseWithBody());
  const summary = completeRunSummary(error);
  const record = buildFailureRecord({
    runId: 'sp-ui-smoke.canary-20261106T000000Z',
    test: {
      title: 'anonymous login',
      file: 'login.spec.ts',
      line: 20,
      project: 'chromium',
    },
    attempt: 0,
    status: 'failed',
    error: { message: error.message, stack: error.stack },
    pageUrl: 'http://127.0.0.1:1234/index.php?r=login/index',
    screenshot: null,
  });
  const outputDir = mkdtempSync(path.join(tmpdir(), 'sp-ui-login-canary-'));

  writeFileSync(path.join(outputDir, 'failure.json'), `${JSON.stringify(record, null, 2)}\n`);
  writeFileSync(
    path.join(outputDir, 'run-summary.json'),
    `${JSON.stringify(summary, null, 2)}\n`,
  );
  writeFileSync(path.join(outputDir, 'junit.xml'), renderJUnit(summary));

  for (const artifact of ['failure.json', 'run-summary.json', 'junit.xml']) {
    const contents = readFileSync(path.join(outputDir, artifact), 'utf8');

    assert.doesNotMatch(
      contents,
      new RegExp(CANARY),
      `${artifact} retained the synthetic login response canary`,
    );
  }
});

test('the fixed login failure message carries no body content', () => {
  const message = loginRedirectFailureMessage({
    route: 'login/login',
    status: 500,
    contentType: 'application/json',
  });

  assert.doesNotMatch(message, new RegExp(CANARY));
  assert.match(message, /route login\/login, HTTP 500, content-type application\/json/);
});

test('loginThroughUi never reads the failing response body', async () => {
  const boot = responseWithBody('boot body', {
    url: 'http://127.0.0.1:1234/index.php?r=bootstrap/getEnvironment',
  });
  const login = responseWithBody(CANARY);
  const page = fakeLoginPage({ boot, login });
  let thrown;

  try {
    await loginThroughUiFlow(page, { login: 'admin', password: 'secret' });
  } catch (error) {
    thrown = error;
  }

  assert.ok(thrown instanceof Error, 'a failed redirect must throw');
  assert.equal(boot.textCalls(), 0, 'the boot response body must not be read');
  assert.equal(login.textCalls(), 0, 'the failing login response body must not be read');
  assert.doesNotMatch(thrown.message, new RegExp(CANARY));
  assert.doesNotMatch(thrown.stack ?? '', new RegExp(CANARY));
  assert.match(thrown.message, /Login did not redirect to the shell/);
  assert.match(thrown.message, /HTTP 200/);
  assert.match(thrown.message, /response body omitted/);

  const outputDir = mkdtempSync(path.join(tmpdir(), 'sp-ui-login-flow-canary-'));
  const summary = completeRunSummary(thrown);
  const record = buildFailureRecord({
    runId: 'sp-ui-smoke.login-flow-canary',
    test: {
      title: 'anonymous login',
      file: 'login.spec.ts',
      line: 20,
      project: 'chromium',
    },
    attempt: 0,
    status: 'failed',
    error: { message: thrown.message, stack: thrown.stack },
    pageUrl: 'http://127.0.0.1:1234/index.php?r=login/index',
    screenshot: null,
  });

  writeFileSync(path.join(outputDir, 'failure.json'), `${JSON.stringify(record, null, 2)}\n`);
  writeFileSync(
    path.join(outputDir, 'run-summary.json'),
    `${JSON.stringify(summary, null, 2)}\n`,
  );
  writeFileSync(path.join(outputDir, 'junit.xml'), renderJUnit(summary));

  for (const artifact of ['failure.json', 'run-summary.json', 'junit.xml']) {
    assert.doesNotMatch(
      readFileSync(path.join(outputDir, artifact), 'utf8'),
      new RegExp(CANARY),
      `${artifact} retained the login response canary`,
    );
  }
});
