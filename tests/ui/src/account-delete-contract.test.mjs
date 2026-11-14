import assert from 'node:assert/strict';
import test from 'node:test';

import {
  validateDeleteAbsence,
  validateDeleteDispatch,
  validateDeleteEnvelope,
} from './account-delete-contract.mjs';

const ITEM_ID = 42;
const NAME = 'E2E UI DELETE fixture';
const SK = 'sk-fixture-secret-token';
const SECRETS = [NAME, SK];

const VALID_HTML = '<div id="search-rows"><div class="noRes round">No records found</div></div>';

function dispatchRequest(overrides = {}) {
  return {
    method: 'POST',
    url: 'http://ui-smoke.invalid/index.php',
    headers: { 'content-type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams({
      r: `account/saveDelete/${ITEM_ID}`,
      sk: SK,
    }).toString(),
    ...overrides,
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
      'failure message must not expose fixture secrets or account values',
    );
  }
}

test('dispatch accepts exactly one form-body POST to /index.php for account/saveDelete/<id>', () => {
  const result = validateDeleteDispatch({
    requests: [dispatchRequest()],
    itemId: ITEM_ID,
  });

  assert.deepEqual(result, { ok: true });
});

test('dispatch fails when the delete request is suppressed or duplicated', () => {
  const suppressed = validateDeleteDispatch({ requests: [], itemId: ITEM_ID });
  const duplicated = validateDeleteDispatch({
    requests: [dispatchRequest(), dispatchRequest()],
    itemId: ITEM_ID,
  });

  assertFailure(suppressed, 'dispatch.count');
  assertFailure(duplicated, 'dispatch.count');
});

test('dispatch fails for a non-POST request or a request outside /index.php', () => {
  const wrongMethod = validateDeleteDispatch({
    requests: [dispatchRequest({ method: 'GET' })],
    itemId: ITEM_ID,
  });
  const wrongUrl = validateDeleteDispatch({
    requests: [dispatchRequest({ url: 'http://ui-smoke.invalid/api.php' })],
    itemId: ITEM_ID,
  });

  assertFailure(wrongMethod, 'dispatch.method');
  assertFailure(wrongUrl, 'dispatch.url');
});

test('dispatch fails when the request is not form-urlencoded', () => {
  const result = validateDeleteDispatch({
    requests: [dispatchRequest({ headers: { 'content-type': 'application/json' } })],
    itemId: ITEM_ID,
  });

  assertFailure(result, 'dispatch.content-type');
});

test('dispatch rejects the legacy account/delete/<id> route', () => {
  const result = validateDeleteDispatch({
    requests: [
      dispatchRequest({
        body: new URLSearchParams({
          r: `account/delete/${ITEM_ID}`,
          sk: SK,
        }).toString(),
      }),
    ],
    itemId: ITEM_ID,
  });

  assertFailure(result, 'dispatch.legacy-route');
});

test('dispatch rejects a route supplied only in the URL instead of the form body', () => {
  const result = validateDeleteDispatch({
    requests: [
      dispatchRequest({
        url: `http://ui-smoke.invalid/index.php?r=account/saveDelete/${ITEM_ID}`,
        body: new URLSearchParams({ sk: SK }).toString(),
      }),
    ],
    itemId: ITEM_ID,
  });

  assertFailure(result, 'dispatch.route-body');
});

test('dispatch fails when the form-body route is missing, wrong or for another item', () => {
  const missingRoute = validateDeleteDispatch({
    requests: [dispatchRequest({ body: new URLSearchParams({ sk: SK }).toString() })],
    itemId: ITEM_ID,
  });
  const wrongRoute = validateDeleteDispatch({
    requests: [
      dispatchRequest({
        body: new URLSearchParams({ r: `account/saveEdit/${ITEM_ID}`, sk: SK }).toString(),
      }),
    ],
    itemId: ITEM_ID,
  });
  const wrongItem = validateDeleteDispatch({
    requests: [
      dispatchRequest({
        body: new URLSearchParams({ r: 'account/saveDelete/99', sk: SK }).toString(),
      }),
    ],
    itemId: ITEM_ID,
  });

  assertFailure(missingRoute, 'dispatch.route');
  assertFailure(wrongRoute, 'dispatch.route');
  assertFailure(wrongItem, 'dispatch.item-id');
});

test('dispatch fails when the session key is missing or empty', () => {
  for (const body of [
    new URLSearchParams({ r: `account/saveDelete/${ITEM_ID}` }).toString(),
    new URLSearchParams({ r: `account/saveDelete/${ITEM_ID}`, sk: '' }).toString(),
  ]) {
    const result = validateDeleteDispatch({
      requests: [dispatchRequest({ body })],
      itemId: ITEM_ID,
    });

    assertFailure(result, 'dispatch.sk');
  }
});

test('envelope accepts only an OK status with null data', () => {
  const result = validateDeleteEnvelope({ status: 'OK', data: null });

  assert.deepEqual(result, { ok: true });
});

test('envelope rejects a non-OK status', () => {
  for (const envelope of [
    { status: 'ERROR', description: NAME, data: null },
    { data: null },
  ]) {
    const result = validateDeleteEnvelope(envelope);

    assertFailure(result, 'envelope.status');
  }
});

test('envelope rejects data that differs from the create response shape', () => {
  for (const data of [{ itemId: ITEM_ID, nextAction: 'account/edit' }, undefined, []]) {
    const result = validateDeleteEnvelope({ status: 'OK', data });

    assertFailure(result, 'envelope.data');
  }
});

test('envelope rejects a response that is not a JSON object', () => {
  for (const envelope of [null, undefined, 'not-json', []]) {
    const result = validateDeleteEnvelope(envelope);

    assertFailure(result, 'envelope.invalid');
  }
});

test('absence accepts an OK search response whose markup omits the name and item id', () => {
  const result = validateDeleteAbsence({
    envelope: { status: 'OK', data: { html: VALID_HTML } },
    name: NAME,
    itemId: ITEM_ID,
  });

  assert.deepEqual(result, { ok: true });
});

test('absence fails when the search response is not OK or has no markup', () => {
  const wrongStatus = validateDeleteAbsence({
    envelope: { status: 'ERROR', data: { html: VALID_HTML } },
    name: NAME,
    itemId: ITEM_ID,
  });
  const missingHtml = validateDeleteAbsence({
    envelope: { status: 'OK', data: {} },
    name: NAME,
    itemId: ITEM_ID,
  });

  assertFailure(wrongStatus, 'absence.status');
  assertFailure(missingHtml, 'absence.html');
});

test('absence fails when the deleted account name or row item id is still present', () => {
  const namePresent = validateDeleteAbsence({
    envelope: { status: 'OK', data: { html: `<div>${NAME}</div>` } },
    name: NAME,
    itemId: ITEM_ID,
  });
  const itemPresent = validateDeleteAbsence({
    envelope: {
      status: 'OK',
      data: { html: `<i data-item-id="${ITEM_ID}" data-onclick="account/delete"></i>` },
    },
    name: NAME,
    itemId: ITEM_ID,
  });

  assertFailure(namePresent, 'absence.name');
  assertFailure(itemPresent, 'absence.item-id');
});
