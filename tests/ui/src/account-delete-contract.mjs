export const ACCOUNT_DELETE_HANDLER = 'account/delete';
export const ACCOUNT_SAVE_DELETE_ROUTE = 'account/saveDelete';

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

function parseUrl(rawUrl) {
  try {
    return new URL(rawUrl, 'http://ui-smoke.invalid');
  } catch {
    return null;
  }
}

function expectedRoute(itemId) {
  return `${ACCOUNT_SAVE_DELETE_ROUTE}/${itemId}`;
}

function isLegacyDeleteRoute(route) {
  return route === ACCOUNT_DELETE_HANDLER || route.startsWith(`${ACCOUNT_DELETE_HANDLER}/`);
}

export function validateDeleteDispatch({ requests, itemId }) {
  if (!Array.isArray(requests) || requests.length !== 1) {
    const observed = Array.isArray(requests) ? requests.length : 0;

    return failure(
      'dispatch.count',
      `expected exactly one ${ACCOUNT_SAVE_DELETE_ROUTE} POST request, observed ${observed}`,
    );
  }

  const request = requests[0];

  if (requestMethod(request) !== 'POST') {
    return failure('dispatch.method', `${ACCOUNT_SAVE_DELETE_ROUTE} request was not a POST`);
  }

  const url = parseUrl(requestUrl(request));

  if (url === null || !url.pathname.endsWith('/index.php')) {
    return failure('dispatch.url', `${ACCOUNT_SAVE_DELETE_ROUTE} request did not target /index.php`);
  }

  const headers = requestHeaders(request);
  const contentType = headers['content-type'] ?? headers['Content-Type'] ?? '';

  if (!String(contentType).toLowerCase().includes('application/x-www-form-urlencoded')) {
    return failure(
      'dispatch.content-type',
      `${ACCOUNT_SAVE_DELETE_ROUTE} request was not form-urlencoded`,
    );
  }

  const form = new URLSearchParams(requestBody(request));
  const bodyRoute = form.get('r');
  const urlRoute = url.searchParams.get('r');

  if (bodyRoute !== null && isLegacyDeleteRoute(bodyRoute)) {
    return failure(
      'dispatch.legacy-route',
      `request used the legacy ${ACCOUNT_DELETE_HANDLER}/<id> route instead of ${ACCOUNT_SAVE_DELETE_ROUTE}/<id>`,
    );
  }

  if (urlRoute !== null && isLegacyDeleteRoute(urlRoute)) {
    return failure(
      'dispatch.legacy-route',
      `request URL used the legacy ${ACCOUNT_DELETE_HANDLER}/<id> route instead of ${ACCOUNT_SAVE_DELETE_ROUTE}/<id>`,
    );
  }

  if (bodyRoute === null && urlRoute !== null) {
    return failure(
      'dispatch.route-body',
      `${ACCOUNT_SAVE_DELETE_ROUTE} route was supplied only in the URL instead of the form body`,
    );
  }

  if (bodyRoute === null || bodyRoute.length === 0) {
    return failure('dispatch.route', `form body did not carry the ${ACCOUNT_SAVE_DELETE_ROUTE} route`);
  }

  const expected = expectedRoute(itemId);

  if (bodyRoute !== expected) {
    const parts = bodyRoute.split('/');

    if (parts.length === 3 && `${parts[0]}/${parts[1]}` === ACCOUNT_SAVE_DELETE_ROUTE) {
      return failure(
        'dispatch.item-id',
        `form-body route did not target the created account item id`,
      );
    }

    return failure('dispatch.route', `form body did not target ${ACCOUNT_SAVE_DELETE_ROUTE}/<id>`);
  }

  const sk = form.get('sk');

  if (sk === null || sk.length === 0) {
    return failure('dispatch.sk', `${ACCOUNT_SAVE_DELETE_ROUTE} request did not carry a session key`);
  }

  return { ok: true };
}

export function validateDeleteEnvelope(envelope) {
  if (envelope === null || typeof envelope !== 'object' || Array.isArray(envelope)) {
    return failure(
      'envelope.invalid',
      `${ACCOUNT_SAVE_DELETE_ROUTE} response was not a JSON object envelope`,
    );
  }

  if (envelope.status !== 'OK') {
    return failure('envelope.status', `${ACCOUNT_SAVE_DELETE_ROUTE} response status was not OK`);
  }

  if (envelope.data !== null) {
    return failure(
      'envelope.data',
      `${ACCOUNT_SAVE_DELETE_ROUTE} response data was not null`,
    );
  }

  return { ok: true };
}

export function validateDeleteAbsence({ envelope, name, itemId }) {
  if (envelope === null || typeof envelope !== 'object' || Array.isArray(envelope)) {
    return failure('absence.invalid', 'independent account search response was not a JSON object');
  }

  if (envelope.status !== 'OK') {
    return failure('absence.status', 'independent account search response status was not OK');
  }

  const html = envelope.data?.html;

  if (typeof html !== 'string' || html.length === 0) {
    return failure('absence.html', 'independent account search response did not carry search markup');
  }

  if (html.includes(name)) {
    return failure(
      'absence.name',
      'independent account search markup still contains the deleted account name',
    );
  }

  if (html.includes(`data-item-id="${itemId}"`)) {
    return failure(
      'absence.item-id',
      'independent account search markup still contains the deleted account item id',
    );
  }

  return { ok: true };
}
