import { parseActionRoute } from './diagnostics.mjs';

export const ACCOUNT_CREATE_ROUTE = 'account/saveCreate';

const REQUIRED_FIELDS = ['isAjax', 'name', 'login', 'client_id', 'category_id'];

function failure(code, message) {
  return { ok: false, code, message };
}

function requestMethod(request) {
  return typeof request.method === 'string' ? request.method : '';
}

function requestUrl(request) {
  return typeof request.url === 'string' ? request.url : '';
}

function requestHeaders(request) {
  return request.headers && typeof request.headers === 'object' ? request.headers : {};
}

function requestBody(request) {
  return typeof request.body === 'string' ? request.body : '';
}

export function validateCreateDispatch({ requests, expected, route = ACCOUNT_CREATE_ROUTE }) {
  if (!Array.isArray(requests) || requests.length !== 1) {
    const observed = Array.isArray(requests) ? requests.length : 0;

    return failure(
      'dispatch.count',
      `expected exactly one ${route} POST request, observed ${observed}`,
    );
  }

  const request = requests[0];

  if (requestMethod(request) !== 'POST') {
    return failure('dispatch.method', `${route} request was not a POST`);
  }

  if (parseActionRoute(requestUrl(request)) !== route) {
    return failure('dispatch.route', `request did not target ${route}`);
  }

  const headers = requestHeaders(request);
  const contentType = headers['content-type'] ?? headers['Content-Type'] ?? '';

  if (!String(contentType).toLowerCase().includes('application/x-www-form-urlencoded')) {
    return failure('dispatch.content-type', `${route} request was not form-urlencoded`);
  }

  const form = new URLSearchParams(requestBody(request));

  for (const field of REQUIRED_FIELDS) {
    if (!form.has(field)) {
      return failure('dispatch.field', `required field ${field} is missing from ${route}`);
    }
  }

  const expectedFields = new Map([
    ['isAjax', '1'],
    ['name', expected.name],
    ['login', expected.login],
    ['client_id', String(expected.clientId)],
    ['category_id', String(expected.categoryId)],
  ]);

  for (const [field, value] of expectedFields) {
    if (form.get(field) !== value) {
      return failure(
        'dispatch.field',
        `required field ${field} does not match the submitted value`,
      );
    }
  }

  for (const field of ['password', 'password_repeat']) {
    const value = form.get(field);

    if (value === null || value.length === 0) {
      return failure('dispatch.password', `password field ${field} is missing or empty`);
    }

    if (value === expected.password) {
      return failure(
        'dispatch.password',
        `password field ${field} matches the typed plaintext`,
      );
    }
  }

  return { ok: true };
}

export function validateCreateEnvelope(envelope) {
  if (envelope === null || typeof envelope !== 'object' || Array.isArray(envelope)) {
    return failure(
      'envelope.invalid',
      `${ACCOUNT_CREATE_ROUTE} response was not a JSON object envelope`,
    );
  }

  if (envelope.status !== 'OK') {
    return failure('envelope.status', `${ACCOUNT_CREATE_ROUTE} response status was not OK`);
  }

  const data = envelope.data;

  if (data === null || typeof data !== 'object' || Array.isArray(data)) {
    return failure('envelope.data', `${ACCOUNT_CREATE_ROUTE} response data was not an object`);
  }

  const itemId = data.itemId;

  if (typeof itemId !== 'number' || !Number.isInteger(itemId) || itemId <= 0) {
    return failure(
      'envelope.itemId',
      `${ACCOUNT_CREATE_ROUTE} response itemId was not a positive integer`,
    );
  }

  const nextAction = data.nextAction;

  if (typeof nextAction !== 'string' || nextAction.length === 0) {
    return failure(
      'envelope.nextAction',
      `${ACCOUNT_CREATE_ROUTE} response nextAction was empty`,
    );
  }

  return { ok: true, itemId, nextAction };
}

export function validateCreatePersistence({ markup, name, login }) {
  if (typeof markup !== 'string' || markup.length === 0) {
    return failure('persistence.empty', 'independent account view markup was empty');
  }

  if (!markup.includes(name)) {
    return failure(
      'persistence.name',
      'independent account view markup did not contain the created account name',
    );
  }

  if (!markup.includes(login)) {
    return failure(
      'persistence.login',
      'independent account view markup did not contain the created account login',
    );
  }

  return { ok: true };
}
