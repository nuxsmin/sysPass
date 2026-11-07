/**
 * Build the diagnostic for a login response that did not redirect to the shell.
 *
 * The response body may contain credentials, canaries or other sensitive data,
 * so it is never read or persisted. Only the fixed route, the HTTP status and a
 * validated content-type are retained.
 */

const SAFE_ROUTE = /^[A-Za-z0-9_./-]{1,160}$/;
const SAFE_MEDIA_TYPE = /^[a-z0-9][a-z0-9!#$&^_.+-]*\/[a-z0-9][a-z0-9!#$&^_.+-]*$/i;

function safeRoute(route) {
  return typeof route === 'string' && SAFE_ROUTE.test(route) ? route : 'unknown';
}

/**
 * @param {unknown} value
 * @returns {string}
 */
export function normalizeContentType(value) {
  const mediaType = String(value ?? '')
    .split(';', 1)[0]
    .trim()
    .toLowerCase();

  return SAFE_MEDIA_TYPE.test(mediaType) ? mediaType : 'unknown';
}

/**
 * @param {object} [context]
 * @param {string} [context.route]
 * @param {unknown} [context.status]
 * @param {unknown} [context.contentType]
 * @returns {string}
 */
export function loginRedirectFailureMessage({ route = 'login/login', status, contentType = '' } = {}) {
  const safeStatus = Number.isInteger(status) ? String(status) : 'unknown';

  return (
    'Login did not redirect to the shell ' +
    `(route ${safeRoute(route)}, HTTP ${safeStatus}, ` +
    `content-type ${normalizeContentType(contentType)}); response body omitted`
  );
}

/**
 * @param {object} response
 * @param {() => number} [response.status]
 * @param {() => Record<string, string>} [response.headers]
 * @param {object} [options]
 * @param {string} [options.route]
 * @returns {Error}
 */
export function buildLoginRedirectError(response, { route = 'login/login' } = {}) {
  let status;
  let contentType = '';

  try {
    status = response?.status?.();
  } catch {
    status = undefined;
  }

  try {
    contentType = response?.headers?.()?.['content-type'] ?? '';
  } catch {
    contentType = '';
  }

  return new Error(loginRedirectFailureMessage({ route, status, contentType }));
}
