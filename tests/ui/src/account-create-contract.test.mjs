import assert from 'node:assert/strict';
import test from 'node:test';

import {
  validateCreateDispatch,
  validateCreateEnvelope,
  validateCreatePersistence,
} from './account-create-contract.mjs';

const NAME = 'E2E UI WRITE fixture';
const LOGIN = 'ui-write-fixture';
const PASSWORD = 'Plaintext-Fixture-Passw0rd!';
const CIPHER = 'ciphertext-fixture-value';
const BODY_CANARY = 'response-body-fixture-canary';
const CLIENT_ID = 2;
const CATEGORY_ID = 3;
const SECRETS = [PASSWORD, CIPHER, BODY_CANARY, NAME, LOGIN];

const EXPECTED = {
  name: NAME,
  login: LOGIN,
  clientId: CLIENT_ID,
  categoryId: CATEGORY_ID,
  password: PASSWORD,
};

const VALID_ENVELOPE = {
  status: 'OK',
  data: {
    itemId: 42,
    nextAction: 'account/edit',
  },
};

function fieldPairs(overrides = {}) {
  return {
    isAjax: '1',
    name: NAME,
    login: LOGIN,
    client_id: String(CLIENT_ID),
    category_id: String(CATEGORY_ID),
    password: CIPHER,
    password_repeat: CIPHER,
    ...overrides,
  };
}

function request(overrides = {}) {
  const { fields = {}, ...rest } = overrides;

  return {
    method: 'POST',
    url: 'http://ui-smoke.invalid/index.php?r=account/saveCreate',
    headers: { 'content-type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams(fieldPairs(fields)).toString(),
    ...rest,
  };
}

function assertFailure(result, code) {
  assert.equal(result.ok, false);
  assert.equal(result.code, code);
  assert.equal(typeof result.message, 'string');
  assert.ok(result.message.length > 0);

  for (const secret of SECRETS) {
    assert.equal(
      result.message.includes(secret),
      false,
      'failure message must not expose fixture values',
    );
  }
}

test('dispatch accepts exactly one form-urlencoded POST for account/saveCreate', () => {
  const result = validateCreateDispatch({ requests: [request()], expected: EXPECTED });

  assert.deepEqual(result, { ok: true });
});

test('dispatch fails when the saveCreate request is suppressed', () => {
  const result = validateCreateDispatch({ requests: [], expected: EXPECTED });

  assertFailure(result, 'dispatch.count');
  assert.match(result.message, /account\/saveCreate/);
});

test('dispatch fails for a request to another route or method', () => {
  const wrongRoute = validateCreateDispatch({
    requests: [request({ url: 'http://ui-smoke.invalid/index.php?r=account/saveEdit' })],
    expected: EXPECTED,
  });
  const wrongMethod = validateCreateDispatch({
    requests: [request({ method: 'GET' })],
    expected: EXPECTED,
  });

  assertFailure(wrongRoute, 'dispatch.route');
  assertFailure(wrongMethod, 'dispatch.method');
});

test('dispatch fails when more than one POST is recorded', () => {
  const result = validateCreateDispatch({
    requests: [request(), request()],
    expected: EXPECTED,
  });

  assertFailure(result, 'dispatch.count');
});

test('dispatch fails when the request is not form-urlencoded', () => {
  const result = validateCreateDispatch({
    requests: [request({ headers: { 'content-type': 'application/json' } })],
    expected: EXPECTED,
  });

  assertFailure(result, 'dispatch.content-type');
});

test('dispatch fails when a required field is missing or does not match', () => {
  const missing = fieldPairs();
  delete missing.name;

  const missingField = validateCreateDispatch({
    requests: [request({ body: new URLSearchParams(missing).toString() })],
    expected: EXPECTED,
  });
  const mismatchedField = validateCreateDispatch({
    requests: [request({ fields: { name: 'another name' } })],
    expected: EXPECTED,
  });

  assertFailure(missingField, 'dispatch.field');
  assert.match(missingField.message, /name/);
  assertFailure(mismatchedField, 'dispatch.field');
});

test('dispatch fails when password fields are empty or equal to the plaintext', () => {
  const emptyPassword = validateCreateDispatch({
    requests: [request({ fields: { password: '' } })],
    expected: EXPECTED,
  });
  const plaintextPassword = validateCreateDispatch({
    requests: [request({ fields: { password: PASSWORD } })],
    expected: EXPECTED,
  });
  const plaintextRepeat = validateCreateDispatch({
    requests: [request({ fields: { password_repeat: PASSWORD } })],
    expected: EXPECTED,
  });

  assertFailure(emptyPassword, 'dispatch.password');
  assertFailure(plaintextPassword, 'dispatch.password');
  assertFailure(plaintextRepeat, 'dispatch.password');
});

test('envelope accepts an OK status with a positive itemId and nextAction', () => {
  const result = validateCreateEnvelope(VALID_ENVELOPE);

  assert.deepEqual(result, { ok: true, itemId: 42, nextAction: 'account/edit' });
});

test('envelope fails for an ERROR or missing status', () => {
  for (const envelope of [
    { status: 'ERROR', description: BODY_CANARY },
    { data: { itemId: 42, nextAction: 'account/edit' } },
  ]) {
    const result = validateCreateEnvelope(envelope);

    assertFailure(result, 'envelope.status');
  }
});

test('envelope fails when itemId is missing, zero or not an integer', () => {
  for (const itemId of [undefined, 0, -1, 1.5, '42']) {
    const result = validateCreateEnvelope({
      status: 'OK',
      data: { itemId, nextAction: 'account/edit' },
    });

    assertFailure(result, 'envelope.itemId');
  }
});

test('envelope fails when nextAction is empty', () => {
  for (const nextAction of ['', undefined]) {
    const result = validateCreateEnvelope({
      status: 'OK',
      data: { itemId: 42, nextAction },
    });

    assertFailure(result, 'envelope.nextAction');
  }
});

test('envelope fails for an invalid response that is not a JSON object', () => {
  for (const envelope of [null, undefined, 'not-json', []]) {
    const result = validateCreateEnvelope(envelope);

    assertFailure(result, 'envelope.invalid');
  }
});

test('a hanging saveCreate action cannot pass even though the request was dispatched', () => {
  const dispatch = validateCreateDispatch({ requests: [request()], expected: EXPECTED });
  const result = validateCreateEnvelope(undefined);

  assert.deepEqual(dispatch, { ok: true });
  assertFailure(result, 'envelope.invalid');
});

test('persistence accepts markup containing both unique values', () => {
  const result = validateCreatePersistence({
    markup: `<input id="name" value="${NAME}"><input id="login" value="${LOGIN}">`,
    name: NAME,
    login: LOGIN,
  });

  assert.deepEqual(result, { ok: true });
});

test('persistence fails when the independent markup is missing the name or login', () => {
  const markup = `<input id="name" value="${NAME}"><input id="login" value="${LOGIN}">`;
  const missingName = validateCreatePersistence({
    markup,
    name: 'absent account name',
    login: LOGIN,
  });
  const missingLogin = validateCreatePersistence({
    markup,
    name: NAME,
    login: 'absent-account-login',
  });

  assertFailure(missingName, 'persistence.name');
  assertFailure(missingLogin, 'persistence.login');
});

test('persistence fails when the independent markup is empty', () => {
  const result = validateCreatePersistence({ markup: '', name: NAME, login: LOGIN });

  assertFailure(result, 'persistence.empty');
});
