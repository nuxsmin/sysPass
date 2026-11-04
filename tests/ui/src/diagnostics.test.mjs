import assert from 'node:assert/strict';
import test from 'node:test';

import { classifyMarkup, PageDiagnostics, parseActionRoute } from './diagnostics.mjs';

const CONTRACT = {
  name: 'test-shell',
  actions: [
    { route: 'bootstrap/getEnvironment', format: 'json' },
    { route: 'account/index', format: 'html', content: ['Parent Account'] },
    { route: 'items/notifications', format: 'json' },
  ],
};

function makeDetector(options = {}) {
  return new PageDiagnostics({
    quietWindowMs: 20,
    timeoutMs: 80,
    sleep: (ms) => new Promise((resolve) => setTimeout(resolve, ms)),
    ...options,
  });
}

function start(detector, id, route, extra = {}) {
  const url = extra.url ?? `http://127.0.0.1:1234/index.php?r=${route}`;
  detector.observeRequestStart({
    id,
    url,
    method: extra.method ?? 'GET',
    resourceType: extra.resourceType ?? 'xhr',
    isNavigationRequest: extra.isNavigationRequest ?? false,
  });
  return url;
}

function complete(detector, id, route, { status = 200, body = '{}', headers = {}, url } = {}) {
  start(detector, id, route, { url });
  detector.observeResponse({
    id,
    url: url ?? `http://127.0.0.1:1234/index.php?r=${route}`,
    status,
    headers,
  });
  if (body !== null) {
    detector.observeBody({ id, body });
  }
}

test('parses only the controller/action pair from the r parameter', () => {
  assert.equal(parseActionRoute('http://x/index.php?r=account/index/12'), 'account/index');
  assert.equal(parseActionRoute('http://x/index.php?r=items/notifications'), 'items/notifications');
  assert.equal(parseActionRoute('http://x/index.php?r=login'), 'login');
  assert.equal(parseActionRoute('http://x/index.php'), null);
  assert.equal(parseActionRoute('not a url'), null);
});

test('a suppressed dispatch fails when no request was issued even with surrounding markup', async () => {
  const detector = makeDetector();
  detector.setContract(CONTRACT);

  await assert.rejects(
    () => detector.waitForActions(['account/index'], 0, Date.now() + 40),
    /account\/index/,
  );

  const result = detector.result();
  assert.equal(result.problems.length, 0, 'missing dispatch is reported by readiness');
});

test('an action HTTP failure is attributed to its route', async () => {
  const detector = makeDetector();
  detector.setContract(CONTRACT);

  complete(detector, '1', 'account/index', { status: 500, body: 'boom' });

  const [problem] = detector.problems();
  assert.equal(problem.kind, 'http');
  assert.equal(problem.route, 'account/index');
  assert.match(problem.message, /HTTP 500/);
});

test('a non-action HTTP failure is not attributed to an invented route', () => {
  const detector = makeDetector();
  detector.observeRequestStart({
    id: '1',
    url: 'http://127.0.0.1:1234/js/app-main.min.js',
    method: 'GET',
    resourceType: 'script',
  });
  detector.observeResponse({
    id: '1',
    url: 'http://127.0.0.1:1234/js/app-main.min.js',
    status: 404,
    headers: {},
  });

  const [problem] = detector.problems();
  assert.equal(problem.kind, 'http');
  assert.equal(problem.route, undefined);
  assert.match(problem.message, /app-main\.min\.js/);
});

test('JSON ERROR status fails and is attributed to the route', () => {
  const detector = makeDetector();
  detector.setContract(CONTRACT);

  complete(detector, '1', 'items/notifications', {
    body: JSON.stringify({ status: 'ERROR', description: 'failed' }),
  });

  const [problem] = detector.problems();
  assert.equal(problem.kind, 'json-status');
  assert.equal(problem.route, 'items/notifications');
  assert.match(problem.message, /JSON status ERROR/);
});

test('a JSON status of OK is not treated as an error by substring matching', () => {
  const detector = makeDetector();
  detector.setContract(CONTRACT);

  complete(detector, '1', 'items/notifications', {
    body: JSON.stringify({ status: 'OK', description: 'ERROR in the description' }),
  });

  assert.deepEqual(detector.problems(), []);
});

test('malformed JSON and missing envelopes fail', () => {
  const detector = makeDetector();
  detector.setContract(CONTRACT);

  complete(detector, '1', 'items/notifications', { body: '{not json' });
  complete(detector, '2', 'bootstrap/getEnvironment', {
    body: JSON.stringify({ data: { loggedin: true } }),
  });

  assert.deepEqual(
    detector.problems().map((problem) => problem.kind),
    ['json-malformed', 'json-envelope'],
  );
});

test('JSON WARNING is annotated but does not fail', () => {
  const detector = makeDetector();
  detector.setContract(CONTRACT);

  complete(detector, '1', 'items/notifications', {
    body: JSON.stringify({ status: 'WARNING', description: 'careful' }),
  });

  assert.deepEqual(detector.problems(), []);
  assert.equal(detector.warnings().length, 1);
  assert.match(detector.warnings()[0].message, /WARNING/);
});

test('uncaught errors, rejections and console errors fail with source information', () => {
  const detector = makeDetector();

  detector.observePageError({
    message: 'Unhandled rejection: boom',
    source: 'http://127.0.0.1:1234/js/app-main.min.js:10:2',
  });
  detector.observeConsole({
    type: 'error',
    text: 'console exploded',
    locationUrl: 'http://127.0.0.1:1234/js/app-main.min.js:20:1',
  });

  const problems = detector.problems();
  assert.equal(problems.length, 2);
  assert.equal(problems[0].kind, 'page-error');
  assert.match(problems[0].message, /source:/);
  assert.equal(problems[0].route, undefined, 'global errors must not invent a route');
  assert.equal(problems[1].kind, 'console');
  assert.equal(problems[1].route, undefined);
});

test('critical server markup fails without requiring #content', () => {
  const detector = makeDetector();

  detector.observeMarkup(
    '<ul class="errors round"><li class="msg-critical"><strong>Critical failure</strong></li></ul>',
  );

  assert.equal(detector.problems()[0].kind, 'markup');
  assert.match(detector.problems()[0].message, /msg-critical/);
});

test('client error markup and error toasts fail', () => {
  const errorMarkup = makeDetector();
  errorMarkup.observeMarkup('<p class="error round">Oops</p>');

  const toast = makeDetector();
  toast.observeMarkup('<div id="toast-container"><div class="toast toast-error">failed</div></div>');

  assert.equal(errorMarkup.problems()[0].kind, 'markup');
  assert.equal(toast.problems()[0].kind, 'markup');
});

test('warning markup in a plural errors container is annotated, not fatal', () => {
  const detector = makeDetector();

  detector.observeMarkup(
    '<ul class="errors round"><li class="msg-warning"><strong>Just a warning</strong></li></ul>',
  );

  assert.deepEqual(detector.problems(), []);
  assert.equal(detector.warnings().length, 1);
});

test('a plural errors container with only informational entries does not fail', () => {
  const detector = makeDetector();

  detector.observeMarkup('<ul class="errors round"><li class="msg-ok"><strong>Done</strong></li></ul>');

  assert.deepEqual(detector.problems(), []);
});

test('empty and missing authenticated fragment content fails as an invalid response', () => {
  const detector = makeDetector({ sessionEstablished: true });
  detector.setContract(CONTRACT);

  complete(detector, '1', 'account/index', { body: '' });
  complete(detector, '2', 'account/index', { body: '<div id="search-rows"></div>' });

  const problems = detector.problems();
  assert.deepEqual(
    problems.map((problem) => problem.kind),
    ['empty-body', 'missing-content'],
  );
  assert.match(problems[0].message, /invalid response/);
  assert.doesNotMatch(problems[0].message, /session lost/);
});

test('a hanging action prevents the quiet window and names the pending route', async () => {
  const detector = makeDetector({ timeoutMs: 60 });
  detector.setContract(CONTRACT);

  start(detector, '1', 'account/index');

  await assert.rejects(
    () => detector.waitForQuiet(0, Date.now() + 60),
    (error) => /account\/index/.test(error.message) && /pending:/.test(error.message),
  );
});

test('actions that never complete fail readiness within one deadline', async () => {
  const detector = makeDetector();
  detector.setContract(CONTRACT);

  start(detector, '1', 'account/index');
  const deadline = Date.now() + 40;

  await assert.rejects(
    () => detector.waitForActions(['account/index'], 0, deadline),
    (error) => /account\/index/.test(error.message) && /pending: account\/index/.test(error.message),
  );
});

test('JSON status 10 establishes session loss only after authentication', () => {
  const established = makeDetector({ sessionEstablished: true });
  established.setContract(CONTRACT);
  complete(established, '1', 'items/notifications', { body: JSON.stringify({ status: 10 }) });

  assert.equal(established.result().sessionLost, true);
  assert.equal(established.problems()[0].kind, 'session-loss');
  assert.match(established.problems()[0].message, /session lost/);

  const anonymous = makeDetector();
  anonymous.setContract(CONTRACT);
  complete(anonymous, '1', 'items/notifications', { body: JSON.stringify({ status: 10 }) });

  assert.equal(anonymous.result().sessionLost, false);
  assert.equal(anonymous.problems()[0].kind, 'invalid-response');
  assert.doesNotMatch(anonymous.problems()[0].message, /session lost/);
});

test('logout script responses and login redirects are detected', () => {
  const logout = makeDetector({ sessionEstablished: true });
  logout.setContract(CONTRACT);
  complete(logout, '1', 'account/index', {
    body: '<script>sysPassApp.actions.main.logout();</script>',
  });

  assert.ok(
    logout.problems().some((problem) => /logout script/.test(problem.message)),
    'logout script response must be reported',
  );

  const redirect = makeDetector({ sessionEstablished: true });
  redirect.setContract(CONTRACT);
  complete(redirect, '1', 'account/index', {
    status: 302,
    headers: { location: '/index.php?r=login' },
    body: null,
  });

  assert.ok(
    redirect.problems().some((problem) => /redirect to the login page/.test(problem.message)),
    'login redirect must be reported',
  );
});

test('body loss caused by a proven navigation is not a late body-inspection failure', () => {
  const detector = makeDetector();

  start(detector, '1', 'login/login');
  detector.observeResponse({
    id: '1',
    url: 'http://127.0.0.1:1234/index.php?r=login/login',
    status: 200,
    headers: { 'content-type': 'application/json' },
  });
  detector.beginNavigation();
  detector.observeBodyFailure({
    id: '1',
    message: 'Response body is not available for a response that was navigated away from.',
  });

  assert.deepEqual(detector.problems(), []);
  assert.equal(detector.records[0].cancelled, true);
});

test('an unexpected login page after authentication is reported, without re-authenticating', () => {
  const detector = makeDetector({ sessionEstablished: true });

  detector.assertNotOnLoginPage(0, 'http://127.0.0.1:1234/index.php?r=login/index');

  assert.equal(detector.problems()[0].kind, 'login-page');
  assert.match(detector.problems()[0].message, /login\/index/);
});

test('late body-inspection failures are drained before readiness can pass', async () => {
  const detector = makeDetector();
  detector.setContract(CONTRACT);

  start(detector, '1', 'items/notifications');
  detector.observeResponse({
    id: '1',
    url: 'http://127.0.0.1:1234/index.php?r=items/notifications',
    status: 200,
    headers: { 'content-type': 'application/json' },
  });

  const body = new Promise((_, reject) => {
    setTimeout(() => reject(new Error('late body failure')), 10);
  });

  detector.trackBody(
    body.catch((error) => detector.observeBodyFailure({ id: '1', message: error.message })),
  );

  await detector.drain();

  assert.equal(detector.problems()[0].kind, 'body-inspection');
  assert.match(detector.problems()[0].message, /late body failure/);
});

test('only explicitly expected navigation cancellations are ignored', () => {
  const detector = makeDetector();

  start(detector, '1', 'account/index');
  detector.beginNavigation();
  detector.observeRequestFailure({ id: '1', errorText: 'net::ERR_ABORTED' });

  assert.deepEqual(detector.problems(), []);
  assert.equal(detector.records[0].cancelled, true);

  const unexpected = makeDetector();
  start(unexpected, '1', 'account/index');
  unexpected.observeRequestFailure({ id: '1', errorText: 'net::ERR_ABORTED' });

  assert.equal(unexpected.problems()[0].kind, 'request-failed');
  assert.equal(unexpected.problems()[0].route, 'account/index');
});

test('classifyMarkup reports critical and warning sources separately', () => {
  const result = classifyMarkup(
    '<ul class="errors round"><li class="msg-warning">warn</li><li class="msg-critical">bad</li></ul>',
  );

  assert.ok(result.critical.some((item) => item.includes('msg-critical')));
  assert.ok(result.warnings.some((item) => item.includes('msg-warning')));
});

test('readiness surfaces a recorded request failure instead of only a missing action', async () => {
  const detector = makeDetector();
  detector.setContract(CONTRACT);

  start(detector, '1', 'account/index');
  detector.observeRequestFailure({ id: '1', errorText: 'net::ERR_CONNECTION_RESET' });

  await assert.rejects(
    () => detector.waitForActions(['account/index'], 0, Date.now() + 200),
    (error) =>
      /account\/index/.test(error.message) &&
      /request-failed/.test(error.message) &&
      /action account\/index failed: GET/.test(error.message) &&
      /ERR_CONNECTION_RESET/.test(error.message),
  );
});

test('readiness surfaces session loss before blocking on expected actions', async () => {
  const detector = makeDetector({ sessionEstablished: true });
  detector.setContract(CONTRACT);

  complete(detector, '1', 'bootstrap/getEnvironment', {
    body: JSON.stringify({ status: 10, description: 'Session not started or timed out' }),
  });

  await assert.rejects(
    () => detector.waitForActions(['account/index'], 0, Date.now() + 200),
    (error) => /session-loss/.test(error.message) && /session lost/.test(error.message),
  );
});

test('a contract installed after body inspection re-evaluates content and format', () => {
  const detector = makeDetector();

  complete(detector, '1', 'account/index', { body: '<div id="search-rows"></div>' });
  assert.deepEqual(detector.problems(), []);
  assert.equal(detector.completedActions('account/index', 0).length, 1);

  detector.setContract(CONTRACT);

  assert.ok(
    detector
      .problems()
      .some((problem) => problem.kind === 'missing-content' && problem.route === 'account/index'),
    'the contract must inspect records whose bodies were recorded before it was installed',
  );
});

test('a late contract install re-inspects a JSON body whose route was not preconfigured', () => {
  const detector = makeDetector();

  complete(detector, '1', 'custom/action', { body: JSON.stringify({ status: 'ERROR' }) });
  assert.deepEqual(detector.problems(), []);

  detector.setContract({
    name: 'late-contract',
    actions: [{ route: 'custom/action', format: 'json' }],
  });

  assert.equal(detector.problems()[0]?.kind, 'json-status');
  assert.equal(detector.problems()[0]?.route, 'custom/action');
});

test('a navigation does not hide a non-abort request failure', () => {
  const detector = makeDetector();

  start(detector, '1', 'account/index');
  detector.beginNavigation();
  detector.observeRequestFailure({ id: '1', errorText: 'net::ERR_CONNECTION_RESET' });

  const [problem] = detector.problems();
  assert.equal(problem.kind, 'request-failed');
  assert.equal(problem.route, 'account/index');
  assert.equal(detector.records[0].cancelled, false);
});

test('a cancelled required action is never reported as completed or inspected', () => {
  const detector = makeDetector();
  detector.setContract(CONTRACT);

  start(detector, '1', 'account/index');
  detector.observeResponse({
    id: '1',
    url: 'http://127.0.0.1:1234/index.php?r=account/index',
    status: 200,
    headers: { 'content-type': 'text/html' },
  });
  detector.beginNavigation();
  detector.observeBodyFailure({
    id: '1',
    message: 'Response body is not available for a response that was navigated away from.',
  });

  assert.equal(detector.records[0].cancelled, true);
  assert.equal(detector.records[0].inspected, false);
  assert.deepEqual(detector.completedActions('account/index', 0), []);
});

test('the bare login route is detected as a login page', () => {
  const detector = makeDetector({ sessionEstablished: true });

  detector.assertNotOnLoginPage(0, 'http://127.0.0.1:1234/index.php?r=login');

  assert.equal(detector.problems()[0].kind, 'login-page');
  assert.match(detector.problems()[0].message, /\?r=login/);
});

test('a navigation record for the bare login route is detected', () => {
  const detector = makeDetector({ sessionEstablished: true });

  start(detector, '1', 'login');
  detector.assertNotOnLoginPage(0, 'http://127.0.0.1:1234/index.php?r=index');

  assert.ok(detector.problems().some((problem) => problem.kind === 'login-navigation'));
});

test('the login action itself is not reported as an unexpected login navigation', () => {
  const detector = makeDetector({ sessionEstablished: true });

  start(detector, '1', 'login/login', { method: 'POST' });
  detector.assertNotOnLoginPage(0, 'http://127.0.0.1:1234/index.php?r=index');

  assert.deepEqual(detector.problems(), []);
});

test('a tracked redirect does not report an unavailable redirect body', () => {
  const detector = makeDetector({ sessionEstablished: true });
  detector.setContract(CONTRACT);

  start(detector, '1', 'account/index');
  detector.observeResponse({
    id: '1',
    url: 'http://127.0.0.1:1234/index.php?r=account/index',
    status: 302,
    headers: { location: '/index.php?r=login' },
  });
  detector.observeBodyFailure({
    id: '1',
    message: 'Response body is unavailable for redirect responses',
  });

  const kinds = detector.problems().map((problem) => problem.kind);
  assert.ok(kinds.includes('session-loss'));
  assert.ok(!kinds.includes('body-inspection'), 'redirect bodies must not be read');
  assert.equal(detector.records[0].inspected, true);
  assert.deepEqual(detector.pendingRoutes(), []);
});

test('a non-login redirect does not produce a spurious body failure', () => {
  const detector = makeDetector({ sessionEstablished: true });
  detector.setContract(CONTRACT);

  start(detector, '1', 'account/index');
  detector.observeResponse({
    id: '1',
    url: 'http://127.0.0.1:1234/index.php?r=account/index',
    status: 307,
    headers: { location: '/index.php?r=items/notifications' },
  });
  detector.observeBodyFailure({
    id: '1',
    message: 'Response body is unavailable for redirect responses',
  });

  assert.deepEqual(detector.problems(), []);
});

test('a JSON content type wins over an HTML fragment expectation so status 10 is not missed', () => {
  const detector = makeDetector({ sessionEstablished: true });
  detector.setContract(CONTRACT);

  complete(detector, '1', 'account/index', {
    headers: { 'content-type': 'application/json' },
    body: JSON.stringify({ status: 10, description: 'Session not started or timed out' }),
  });

  assert.equal(detector.result().sessionLost, true);
  assert.equal(detector.problems()[0].kind, 'session-loss');
  assert.equal(detector.problems()[0].route, 'account/index');
});
