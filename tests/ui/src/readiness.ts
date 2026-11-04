import type {
  BrowserContext,
  ConsoleMessage,
  Page,
  Request as PlaywrightRequest,
  Response as PlaywrightResponse,
} from '@playwright/test';

const IGNORED_RESOURCE = /\/favicon\.ico$/;

export interface ActionRecord {
  route: string;
  url: string;
  method: string;
  startedAt: number;
  finishedAt?: number;
  status?: number;
  body?: string;
  json?: unknown;
  error?: string;
}

export interface ActionTrackerOptions {
  readonly quietWindowMs?: number;
  readonly timeoutMs?: number;
}

export function parseActionRoute(rawUrl: string): string | null {
  let url: URL;

  try {
    url = new URL(rawUrl);
  } catch {
    return null;
  }

  const route = url.searchParams.get('r');

  if (!route) {
    return null;
  }

  const parts = route.split('/').filter(Boolean);

  return parts.length > 1 ? `${parts[0]}/${parts[1]}` : parts[0] ?? null;
}

function sleep(milliseconds: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, milliseconds));
}

/**
 * Tracks Page-load Actions, diagnostics and the action-scoped quiet window.
 *
 * Attach it before navigation. It deliberately ignores static resource
 * failures except page-level errors, so the readiness contract stays scoped to
 * requests carrying the `r` action parameter.
 */
export class ActionTracker {
  private readonly records: ActionRecord[] = [];
  private readonly pending = new Map<PlaywrightRequest, ActionRecord>();
  private readonly problems: string[] = [];
  private readonly quietWindowMs: number;
  private readonly timeoutMs: number;

  constructor(
    private readonly page: Page,
    context: BrowserContext,
    options: ActionTrackerOptions = {},
  ) {
    this.quietWindowMs = options.quietWindowMs ?? 300;
    this.timeoutMs = options.timeoutMs ?? 15_000;

    page.on('request', (request) => this.handleRequest(request));
    page.on('response', (response) => {
      void this.handleResponse(response);
    });
    page.on('requestfailed', (request) => this.handleRequestFailure(request));
    page.on('pageerror', (error) => this.problems.push(`page error: ${error.message}`));
    page.on('console', (message) => this.handleConsole(message));
    context.on('weberror', (error) => this.problems.push(`page error: ${error.error().message}`));
  }

  private handleRequest(request: PlaywrightRequest): void {
    const route = parseActionRoute(request.url());

    if (!route) {
      return;
    }

    const record: ActionRecord = {
      route,
      url: request.url(),
      method: request.method(),
      startedAt: Date.now(),
    };

    this.records.push(record);
    this.pending.set(request, record);
  }

  private async handleResponse(response: PlaywrightResponse): Promise<void> {
    const request = response.request();
    const record = this.pending.get(request);

    if (!record) {
      return;
    }

    record.status = response.status();

    if (response.status() >= 400) {
      this.problems.push(
        `action ${record.route} returned HTTP ${response.status()} for ${record.method} ${record.url}`,
      );
    }

    try {
      if (record.route !== 'login/login' && !request.url().match(IGNORED_RESOURCE)) {
        record.body = await response.text();
      }
    } catch (error) {
      this.problems.push(
        `action ${record.route} body could not be inspected: ${error instanceof Error ? error.message : String(error)}`,
      );
    }

    const contentType = response.headers()['content-type'] ?? '';
    const expectsJson = contentType.includes('application/json') || record.route === 'bootstrap/getEnvironment';

    if (expectsJson && record.body !== undefined) {
      try {
        record.json = JSON.parse(record.body);
      } catch {
        this.problems.push(`action ${record.route} returned malformed JSON`);
      }

      if (
        record.json !== null &&
        typeof record.json === 'object' &&
        'status' in record.json &&
        (record.json as { status?: unknown }).status !== 'OK'
      ) {
        this.problems.push(
          `action ${record.route} returned status ${String((record.json as { status?: unknown }).status)}`,
        );
      }
    }

    if (record.route === 'account/index' && (record.body ?? '').trim() === '') {
      this.problems.push('action account/index returned an empty body');
    }

    record.finishedAt = Date.now();
    this.pending.delete(request);
  }

  private handleRequestFailure(request: PlaywrightRequest): void {
    if (IGNORED_RESOURCE.test(request.url())) {
      return;
    }

    const record = this.pending.get(request);

    if (record) {
      record.finishedAt = Date.now();
      record.error = request.failure()?.errorText ?? 'request failed';
      this.problems.push(
        `action ${record.route} failed: ${request.method()} ${request.url()} ${record.error}`,
      );
      this.pending.delete(request);
    }
  }

  private handleConsole(message: ConsoleMessage): void {
    if (message.type() !== 'error' || IGNORED_RESOURCE.test(message.location().url)) {
      return;
    }

    this.problems.push(`console error: ${message.text()}`);
  }

  private completedActions(route: string, since: number): ActionRecord[] {
    return this.records.filter(
      (record) => record.route === route && record.startedAt >= since && record.finishedAt !== undefined,
    );
  }

  getAction(route: string, since = 0): ActionRecord | undefined {
    return this.completedActions(route, since).at(-1);
  }

  getJson(route: string, since = 0): unknown {
    return this.getAction(route, since)?.json;
  }

  observedRoutes(since = 0): string[] {
    return [...new Set(this.records.filter((record) => record.startedAt >= since).map((record) => record.route))];
  }

  pendingRoutes(since = 0): string[] {
    return [...this.pending.values()]
      .filter((record) => record.startedAt >= since)
      .map((record) => record.route);
  }

  private describeMissing(expected: string[], since: number): string {
    return [
      `Expected Page-load Actions were not completed within the readiness deadline: ${expected.join(', ')}`,
      `pending: ${this.pendingRoutes(since).join(', ') || 'none'}`,
      `observed: ${this.observedRoutes(since).join(', ') || 'none'}`,
    ].join('; ');
  }

  async waitForAction(route: string, since: number, deadline: number): Promise<ActionRecord> {
    while (Date.now() < deadline) {
      const completed = this.completedActions(route, since);

      if (completed.length > 0) {
        return completed.at(-1) as ActionRecord;
      }

      await sleep(25);
    }

    throw new Error(this.describeMissing([route], since));
  }

  async waitForActions(routes: string[], since: number, deadline: number): Promise<ActionRecord[]> {
    const completed: ActionRecord[] = [];

    for (const route of routes) {
      completed.push(await this.waitForAction(route, since, deadline));
    }

    return completed;
  }

  private lastActionActivity(since: number): number | null {
    const relevant = this.records.filter((record) => record.startedAt >= since);

    if (relevant.length === 0) {
      return null;
    }

    return relevant.reduce((latest, record) => {
      return Math.max(latest, record.startedAt, record.finishedAt ?? record.startedAt);
    }, 0);
  }

  async waitForQuiet(since: number, deadline = Date.now() + this.timeoutMs): Promise<void> {
    while (Date.now() < deadline) {
      const lastActivity = this.lastActionActivity(since);
      const pending = this.pendingRoutes(since);

      if (
        pending.length === 0 &&
        lastActivity !== null &&
        Date.now() - lastActivity >= this.quietWindowMs
      ) {
        return;
      }

      await sleep(25);
    }

    throw new Error(
      [
        `No ${this.quietWindowMs}ms action-scoped quiet window before the readiness deadline`,
        `pending: ${this.pendingRoutes(since).join(', ') || 'none'}`,
        `observed: ${this.observedRoutes(since).join(', ') || 'none'}`,
      ].join('; '),
    );
  }

  assertNoProblems(): void {
    if (this.problems.length > 0) {
      throw new Error([...new Set(this.problems)].join('\n'));
    }
  }

  assertNotOnLoginPage(since: number): void {
    const route = parseActionRoute(this.page.url());

    if (route === 'login/index' || route === 'login/login') {
      this.problems.push(`unexpected login page after authenticated readiness began: ${this.page.url()}`);
    }

    if (this.records.some((record) => record.route === 'login/index' && record.startedAt >= since)) {
      this.problems.push('unexpected navigation to the login page after authenticated readiness began');
    }
  }
}