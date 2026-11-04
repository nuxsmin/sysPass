const FAVICON = /\/favicon\.ico$/;
const JSON_ROUTES = new Set([
  'bootstrap/getEnvironment',
  'login/login',
  'items/notifications',
]);
const LOGIN_ROUTES = new Set(['login/index', 'login/login']);
const LOGOUT_SCRIPT = /sysPassApp\.actions\.main\.logout\s*\(/;
const LOGIN_LOCATION = /(?:\?|&)r=login(?:\/|&|$)/;
const NAVIGATION_BODY_LOSS =
  /navigated away|No resource with given identifier found|Target page, context or browser has been closed/i;

/**
 * Extract the `controller/action` pair from an `r` query parameter.
 *
 * Only the first two segments are significant; additional segments are route
 * parameters and never part of the attribution key.
 *
 * @param {string} rawUrl
 * @returns {string | null}
 */
export function parseActionRoute(rawUrl) {
  let url;

  try {
    url = new URL(rawUrl, 'http://ui-smoke.invalid');
  } catch {
    return null;
  }

  const route = url.searchParams.get('r');

  if (!route) {
    return null;
  }

  const parts = route.split('/').filter(Boolean);

  if (parts.length === 0) {
    return null;
  }

  return parts.length > 1 ? `${parts[0]}/${parts[1]}` : parts[0];
}

/**
 * @param {string} html
 * @returns {{ critical: string[], warnings: string[] }}
 */
export function classifyMarkup(html) {
  const critical = [];
  const warnings = [];

  if (/\bmsg-critical\b/.test(html)) {
    critical.push('server critical markup (.msg-critical)');
  }

  const classTokens = [...html.matchAll(/class\s*=\s*(?:"([^"]*)"|'([^']*)')/gi)].map(
    (match) => (match[1] ?? match[2] ?? '').split(/\s+/).filter(Boolean),
  );

  const hasClientError = classTokens.some(
    (tokens) => tokens.includes('error') && tokens.includes('round'),
  );

  if (hasClientError) {
    critical.push('client error markup (.error.round)');
  }

  if (/\btoast-error\b/.test(html)) {
    critical.push('client error toast (.toast-error)');
  }

  if (/\bmsg-warning\b/.test(html)) {
    warnings.push('server warning markup (.msg-warning)');
  }

  if (/\btoast-warning\b/.test(html)) {
    warnings.push('client warning toast (.toast-warning)');
  }

  return { critical, warnings };
}

/**
 * @typedef {object} ActionRecord
 * @property {string} id
 * @property {string | null} route
 * @property {string} url
 * @property {string} method
 * @property {string} resourceType
 * @property {boolean} isNavigationRequest
 * @property {number} startedAt
 * @property {number} [finishedAt]
 * @property {number} [respondedAt]
 * @property {number} [status]
 * @property {Record<string, string>} [headers]
 * @property {string} [body]
 * @property {unknown} [json]
 * @property {boolean} inspected
 * @property {'json' | 'html' | 'none'} [format]
 * @property {boolean} expectedAbort
 * @property {boolean} cancelled
 * @property {string} [error]
 */

/**
 * @typedef {object} ActionSpec
 * @property {string} route
 * @property {'json' | 'html' | 'none'} [format]
 * @property {string[]} [content]
 * @property {boolean} [emptyBodyFails]
 */

/**
 * @typedef {object} Contract
 * @property {string} [name]
 * @property {ActionSpec[]} actions
 */

/**
 * @typedef {object} Problem
 * @property {string} kind
 * @property {string} message
 * @property {string} [route]
 * @property {string} [url]
 */

/**
 * Event-driven detector for one page load.
 *
 * It has no Playwright dependency so its failure classes can be self-tested
 * with synthetic events. `ActionTracker` in readiness.ts wires it to a real
 * browser page.
 */
export class PageDiagnostics {
  /**
   * @param {object} [options]
   * @param {number} [options.quietWindowMs]
   * @param {number} [options.timeoutMs]
   * @param {() => number} [options.now]
   * @param {(ms: number) => Promise<void>} [options.sleep]
   * @param {boolean} [options.sessionEstablished]
   */
  constructor({
    quietWindowMs = 300,
    timeoutMs = 15_000,
    now = () => Date.now(),
    sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms)),
    sessionEstablished = false,
  } = {}) {
    this.quietWindowMs = quietWindowMs;
    this.timeoutMs = timeoutMs;
    this.now = now;
    this.sleep = sleep;
    this.sessionEstablished = sessionEstablished;
    this.sessionLost = false;
    /** @type {number | null} */
    this.navigationStartedAt = null;
    /** @type {Contract | null} */
    this.contract = null;
    /** @type {ActionRecord[]} */
    this.records = [];
    /** @type {Map<string, ActionRecord>} */
    this.requests = new Map();
    /** @type {Map<string, ActionRecord>} */
    this.pending = new Map();
    /** @type {Set<Promise<unknown>>} */
    this.bodyTasks = new Set();
    /** @type {Problem[]} */
    this.problemList = [];
    /** @type {{kind: string, message: string, route?: string}[]} */
    this.warningList = [];
  }

  /** @param {Contract | null} contract */
  setContract(contract) {
    this.contract = contract;
  }

  /** @param {boolean} value */
  setSessionEstablished(value) {
    this.sessionEstablished = value;
  }

  /** @param {string} route */
  specFor(route) {
    return this.contract?.actions?.find((action) => action.route === route);
  }

  /**
   * @param {string} kind
   * @param {string} message
   * @param {{route?: string, url?: string}} [extra]
   */
  addProblem(kind, message, extra = {}) {
    const dedupeKey = `${kind}\0${message}\0${extra.route ?? ''}`;

    if (this.problemList.some((problem) => `${problem.kind}\0${problem.message}\0${problem.route ?? ''}` === dedupeKey)) {
      return;
    }

    this.problemList.push({ kind, message, ...extra });
  }

  /**
   * @param {string} kind
   * @param {string} message
   * @param {{route?: string}} [extra]
   */
  addWarning(kind, message, extra = {}) {
    this.warningList.push({ kind, message, ...extra });
  }

  /**
   * Mark requests that are currently in flight as expected navigation
   * cancellations. Called when the main frame starts a new navigation.
   */
  beginNavigation() {
    this.navigationStartedAt = this.now();

    for (const record of this.requests.values()) {
      if (record.finishedAt === undefined) {
        record.expectedAbort = true;
      }
    }
  }

  /**
   * Mark all outstanding requests as expected teardown cancellations.
   */
  beginTeardown() {
    for (const record of this.requests.values()) {
      if (record.finishedAt === undefined) {
        record.expectedAbort = true;
      }
    }
  }

  /**
   * @param {object} event
   * @param {string} event.id
   * @param {string} event.url
   * @param {string} event.method
   * @param {string} [event.resourceType]
   * @param {boolean} [event.isNavigationRequest]
   */
  observeRequestStart({ id, url, method, resourceType = 'other', isNavigationRequest = false }) {
    const route = parseActionRoute(url);
    /** @type {ActionRecord} */
    const record = {
      id,
      route,
      url,
      method,
      resourceType,
      isNavigationRequest,
      startedAt: this.now(),
      inspected: route === null,
      expectedAbort: false,
      cancelled: false,
    };

    this.records.push(record);
    this.requests.set(id, record);

    if (route !== null) {
      this.pending.set(id, record);
    }
  }

  /**
   * @param {object} event
   * @param {string} event.id
   */
  observeRequestFinished({ id }) {
    const record = this.requests.get(id);

    if (record && record.respondedAt === undefined) {
      record.respondedAt = this.now();
    }
  }

  /**
   * @param {object} event
   * @param {string} event.id
   * @param {string} event.url
   * @param {number} event.status
   * @param {Record<string, string>} [event.headers]
   */
  observeResponse({ id, url, status, headers = {} }) {
    const record = this.requests.get(id);

    if (!record) {
      return;
    }

    record.respondedAt = this.now();
    record.status = status;
    record.headers = headers;

    if (FAVICON.test(record.url)) {
      record.finishedAt = this.now();
      this.pending.delete(id);
      return;
    }

    if (status >= 400) {
      if (record.route) {
        this.addProblem(
          'http',
          `action ${record.route} returned HTTP ${status} for ${record.method} ${record.url}`,
          { route: record.route, url: record.url },
        );
      } else {
        this.addProblem('http', `${record.method} ${record.url} returned HTTP ${status}`, {
          url: record.url,
        });
      }
    }

    const location = headers.location ?? '';

    if (status >= 300 && status < 400 && LOGIN_LOCATION.test(location)) {
      this.recordSessionLoss(`redirect to the login page from ${record.url}`, record.route);
    }

    if (!record.route) {
      record.finishedAt = this.now();
      return;
    }

    record.format = this.resolveFormat(record);
    record.inspected = record.format === 'none';

    if (record.inspected) {
      record.finishedAt = this.now();
      this.pending.delete(id);
    }
  }

  /**
   * @param {ActionRecord} record
   * @returns {'json' | 'html' | 'none'}
   */
  resolveFormat(record) {
    const contentType = record.headers?.['content-type'] ?? '';

    // A JSON payload is always inspected as JSON, even when the route normally
    // returns a fragment: session loss and action errors arrive as JSON and
    // must not be hidden behind an HTML-content expectation.
    if (contentType.includes('application/json')) {
      return 'json';
    }

    const spec = record.route ? this.specFor(record.route) : undefined;

    if (spec?.format) {
      return spec.format;
    }

    if (record.route && JSON_ROUTES.has(record.route)) {
      return 'json';
    }

    return 'html';
  }

  /**
   * @param {object} event
   * @param {string} event.id
   * @param {string} event.body
   */
  observeBody({ id, body }) {
    const record = this.requests.get(id);

    if (!record) {
      return;
    }

    record.body = body;
    record.inspected = true;
    record.finishedAt = this.now();
    this.pending.delete(id);

    if (record.format === 'json') {
      this.inspectJson(record, body);
    } else {
      this.inspectHtml(record, body);
    }
  }

  /**
   * @param {ActionRecord} record
   * @param {string} body
   */
  inspectJson(record, body) {
    let parsed;

    try {
      parsed = JSON.parse(body);
    } catch {
      this.addProblem('json-malformed', `action ${record.route} returned malformed JSON`, {
        route: record.route ?? undefined,
        url: record.url,
      });
      return;
    }

    record.json = parsed;

    if (parsed === null || typeof parsed !== 'object' || Array.isArray(parsed)) {
      this.addProblem('json-envelope', `action ${record.route} returned a non-object JSON envelope`, {
        route: record.route ?? undefined,
        url: record.url,
      });
      return;
    }

    if (!('status' in parsed)) {
      this.addProblem('json-envelope', `action ${record.route} returned a JSON envelope without status`, {
        route: record.route ?? undefined,
        url: record.url,
      });
      return;
    }

    const status = parsed.status;

    if (status === 10) {
      this.recordSessionLoss(`action ${record.route} returned JSON status 10`, record.route ?? undefined);
      return;
    }

    if (status === 'ERROR') {
      const description =
        typeof parsed.description === 'string' && parsed.description.length > 0
          ? `: ${parsed.description}`
          : '';
      this.addProblem(
        'json-status',
        `action ${record.route} returned JSON status ERROR${description}`,
        { route: record.route ?? undefined, url: record.url },
      );
      return;
    }

    if (status === 'WARNING') {
      this.addWarning('json-warning', `action ${record.route} returned JSON status WARNING`, {
        route: record.route ?? undefined,
      });
      return;
    }

    if (status !== 'OK') {
      this.addProblem(
        'json-envelope',
        `action ${record.route} returned an unexpected JSON status ${JSON.stringify(status)}`,
        { route: record.route ?? undefined, url: record.url },
      );
    }
  }

  /**
   * @param {ActionRecord} record
   * @param {string} body
   */
  inspectHtml(record, body) {
    const spec = record.route ? this.specFor(record.route) : undefined;
    const empty = body.trim() === '';

    if (empty && spec?.emptyBodyFails !== false) {
      this.addProblem(
        'empty-body',
        `action ${record.route} returned an empty HTTP ${record.status ?? 200} body (invalid response)`,
        { route: record.route ?? undefined, url: record.url },
      );
    }

    for (const marker of spec?.content ?? []) {
      if (!body.includes(marker)) {
        this.addProblem(
          'missing-content',
          `action ${record.route} response is missing expected content ${JSON.stringify(marker)}`,
          { route: record.route ?? undefined, url: record.url },
        );
      }
    }

    if (LOGOUT_SCRIPT.test(body)) {
      this.recordSessionLoss(`logout script in the response from ${record.route}`, record.route ?? undefined);
    }
  }

  /**
   * @param {object} event
   * @param {string} event.id
   * @param {string} event.message
   */
  observeBodyFailure({ id, message }) {
    const record = this.requests.get(id);

    if (!record) {
      return;
    }

    const navigationCancelled =
      NAVIGATION_BODY_LOSS.test(message) &&
      (record.expectedAbort ||
        (this.navigationStartedAt !== null &&
          this.navigationStartedAt >= (record.respondedAt ?? record.startedAt)));

    record.inspected = true;
    record.finishedAt = this.now();
    this.pending.delete(id);

    if (navigationCancelled) {
      record.cancelled = true;
      return;
    }

    this.addProblem(
      'body-inspection',
      `action ${record.route} body could not be inspected: ${message}`,
      { route: record.route ?? undefined, url: record.url },
    );
  }

  /**
   * Register asynchronous body inspection so readiness cannot pass before it
   * settles.
   *
   * @param {Promise<unknown>} task
   */
  trackBody(task) {
    const tracked = Promise.resolve(task).then(
      () => undefined,
      () => undefined,
    );

    this.bodyTasks.add(tracked);
    tracked.finally(() => this.bodyTasks.delete(tracked));
  }

  /**
   * @param {object} event
   * @param {string} event.message
   * @param {string} [event.source]
   * @param {string} [event.pageUrl]
   */
  observePageError({ message, source, pageUrl }) {
    const origin = source || pageUrl || 'unknown source';
    this.addProblem('page-error', `page error: ${message} (source: ${origin})`, {
      url: source || pageUrl,
    });
  }

  /**
   * @param {object} event
   * @param {string} event.type
   * @param {string} event.text
   * @param {string} [event.locationUrl]
   * @param {string} [event.pageUrl]
   */
  observeConsole({ type, text, locationUrl, pageUrl }) {
    if (type !== 'error' || (locationUrl && FAVICON.test(locationUrl))) {
      return;
    }

    const origin = locationUrl || pageUrl || 'unknown source';
    this.addProblem('console', `console error: ${text} (source: ${origin})`, {
      url: locationUrl || pageUrl,
    });
  }

  /**
   * @param {object} event
   * @param {string} event.id
   * @param {string} [event.errorText]
   */
  observeRequestFailure({ id, errorText = 'request failed' }) {
    const record = this.requests.get(id);

    if (!record) {
      return;
    }

    if (FAVICON.test(record.url)) {
      record.finishedAt = this.now();
      return;
    }

    if (record.expectedAbort) {
      record.cancelled = true;
      record.finishedAt = this.now();
      this.pending.delete(id);
      return;
    }

    record.error = errorText;
    record.finishedAt = this.now();
    this.pending.delete(id);

    if (record.route) {
      this.addProblem(
        'request-failed',
        `action ${record.route} failed: ${record.method} ${record.url} ${errorText}`,
        { route: record.route, url: record.url },
      );
      return;
    }

    this.addProblem(
      'request-failed',
      `request failed: ${record.method} ${record.url} ${errorText}`,
      { url: record.url },
    );
  }

  /**
   * @param {string} detail
   * @param {string} [route]
   */
  recordSessionLoss(detail, route) {
    if (this.sessionEstablished) {
      this.sessionLost = true;
      this.addProblem('session-loss', `session lost after authentication: ${detail}`, {
        route,
      });
      return;
    }

    this.addProblem('invalid-response', `unexpected session/logout response: ${detail}`, {
      route,
    });
  }

  /**
   * @param {string} html
   */
  observeMarkup(html) {
    const result = classifyMarkup(html);

    for (const message of result.critical) {
      this.addProblem('markup', `critical ${message}`);
    }

    for (const message of result.warnings) {
      this.addWarning('markup-warning', message);
    }
  }

  /**
   * @param {string} route
   * @param {number} since
   * @returns {ActionRecord[]}
   */
  completedActions(route, since) {
    return this.records.filter(
      (record) =>
        record.route === route &&
        record.startedAt >= since &&
        record.finishedAt !== undefined &&
        record.inspected !== false,
    );
  }

  /**
   * @param {string} route
   * @param {number} [since]
   * @returns {ActionRecord | undefined}
   */
  getAction(route, since = 0) {
    return this.completedActions(route, since).at(-1);
  }

  /**
   * @param {string} route
   * @param {number} [since]
   * @returns {unknown}
   */
  getJson(route, since = 0) {
    return this.getAction(route, since)?.json;
  }

  /**
   * @param {number} [since]
   * @returns {string[]}
   */
  observedRoutes(since = 0) {
    return [...new Set(this.records.filter((record) => record.startedAt >= since && record.route).map((record) => record.route))];
  }

  /**
   * @param {number} [since]
   * @returns {string[]}
   */
  pendingRoutes(since = 0) {
    return [...this.pending.values()]
      .filter((record) => record.startedAt >= since)
      .map((record) => record.route)
      .filter((route) => route !== null);
  }

  /**
   * @param {string[]} expected
   * @param {number} since
   * @returns {string}
   */
  describeMissing(expected, since) {
    const missing = expected.filter((route) => this.completedActions(route, since).length === 0);
    const pending = this.pendingRoutes(since);
    const observed = this.observedRoutes(since);

    return [
      `Expected Page-load Actions were not completed within the readiness deadline: ${missing.join(', ') || expected.join(', ')}`,
      `pending: ${pending.join(', ') || 'none'}`,
      `observed: ${observed.join(', ') || 'none'}`,
    ].join('; ');
  }

  /**
   * @param {string} route
   * @param {number} since
   * @param {number} deadline
   * @returns {Promise<ActionRecord>}
   */
  async waitForAction(route, since, deadline) {
    while (this.now() < deadline) {
      const completed = this.completedActions(route, since);

      if (completed.length > 0) {
        return completed.at(-1);
      }

      await this.sleep(10);
    }

    throw new Error(this.describeMissing([route], since));
  }

  /**
   * @param {string[]} routes
   * @param {number} since
   * @param {number} deadline
   * @returns {Promise<ActionRecord[]>}
   */
  async waitForActions(routes, since, deadline) {
    const completed = [];

    for (const route of routes) {
      completed.push(await this.waitForAction(route, since, deadline));
    }

    return completed;
  }

  /**
   * @param {number} since
   * @returns {number | null}
   */
  lastActionActivity(since) {
    const relevant = this.records.filter((record) => record.route !== null && record.startedAt >= since);

    if (relevant.length === 0) {
      return null;
    }

    return relevant.reduce(
      (latest, record) => Math.max(latest, record.startedAt, record.finishedAt ?? record.startedAt),
      0,
    );
  }

  /**
   * @param {number} since
   * @param {number} [deadline]
   */
  async waitForQuiet(since, deadline = this.now() + this.timeoutMs) {
    while (this.now() < deadline) {
      const pending = this.records.filter(
        (record) => record.route !== null && record.startedAt >= since && record.finishedAt === undefined,
      );
      const lastActivity = this.lastActionActivity(since);

      if (
        pending.length === 0 &&
        (lastActivity === null || this.now() - lastActivity >= this.quietWindowMs)
      ) {
        return;
      }

      await this.sleep(10);
    }

    const pending = this.pendingRoutes(since);

    throw new Error(
      [
        `No ${this.quietWindowMs}ms action-scoped quiet window before the readiness deadline`,
        `pending: ${pending.join(', ') || 'none'}`,
        `observed: ${this.observedRoutes(since).join(', ') || 'none'}`,
      ].join('; '),
    );
  }

  async drain() {
    while (this.bodyTasks.size > 0) {
      const tasks = [...this.bodyTasks];
      await Promise.allSettled(tasks);
    }
  }

  /**
   * @param {number} since
   * @param {string} currentUrl
   */
  assertNotOnLoginPage(since, currentUrl) {
    const route = parseActionRoute(currentUrl);

    if (route !== null && LOGIN_ROUTES.has(route)) {
      this.addProblem('login-page', `unexpected login page after authenticated readiness began: ${currentUrl}`, {
        url: currentUrl,
      });
    }

    if (this.records.some((record) => record.route === 'login/index' && record.startedAt >= since)) {
      this.addProblem('login-navigation', 'unexpected navigation to the login page after authenticated readiness began');
    }
  }

  /** @returns {Problem[]} */
  problems() {
    return [...this.problemList];
  }

  /** @returns {{kind: string, message: string, route?: string}[]} */
  warnings() {
    return [...this.warningList];
  }

  assertNoProblems() {
    if (this.problemList.length === 0) {
      return;
    }

    throw new Error(this.problemList.map((problem) => problem.message).join('\n'));
  }

  result() {
    return {
      problems: this.problems(),
      warnings: this.warnings(),
      records: [...this.records],
      sessionLost: this.sessionLost,
    };
  }
}
