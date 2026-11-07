import type {
  BrowserContext,
  ConsoleMessage,
  Page,
  Request as PlaywrightRequest,
  Response as PlaywrightResponse,
} from '@playwright/test';

import { waitForConditions } from './contract.mjs';
import type { PageContract } from './contracts';
import { PageDiagnostics, parseActionRoute } from './diagnostics.mjs';

export { parseActionRoute };

export interface ActionTrackerOptions {
  readonly quietWindowMs?: number;
  readonly timeoutMs?: number;
  readonly sessionEstablished?: boolean;
}

interface DomProbe {
  isVisible(selector: string): Promise<boolean>;
  count(selector: string): Promise<number>;
  text(selector: string): Promise<string>;
  attribute(selector: string, name: string): Promise<string | null>;
}

export interface ActionRecordView {
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

/**
 * Wires the pure `PageDiagnostics` detector to one Playwright page.
 *
 * Listeners are attached in the constructor and must be removed with
 * `dispose()`. Each tracker owns only its own page/context handlers, so one
 * test cannot leave diagnostics behind for another.
 */
export class ActionTracker {
  private readonly detector: PageDiagnostics;
  private readonly requestIds = new Map<PlaywrightRequest, string>();
  private readonly cleanups: Array<() => void> = [];
  private readonly timeoutMs: number;
  private nextRequestId = 1;
  private readonly reportedWarnings = new Set<string>();

  constructor(
    private readonly page: Page,
    private readonly context: BrowserContext,
    options: ActionTrackerOptions = {},
  ) {
    this.timeoutMs = options.timeoutMs ?? 15_000;
    this.detector = new PageDiagnostics({
      quietWindowMs: options.quietWindowMs ?? 300,
      timeoutMs: this.timeoutMs,
      sessionEstablished: options.sessionEstablished ?? false,
    });

    this.bindPageEvents();
    this.bindContextEvents();
  }

  private bindPageEvents(): void {
    const onRequest = (request: PlaywrightRequest): void => {
      try {
        if (request.isNavigationRequest() && request.frame() === this.page.mainFrame()) {
          this.detector.beginNavigation();
        }
      } catch {
        // Requests without a frame (service workers, browser internals) are not
        // page navigation and cannot cancel a tracked Page-load Action.
      }

      const id = String(this.nextRequestId++);
      this.requestIds.set(request, id);
      this.detector.observeRequestStart({
        id,
        url: request.url(),
        method: request.method(),
        resourceType: request.resourceType(),
        isNavigationRequest: request.isNavigationRequest(),
      });
    };

    const onRequestFinished = (request: PlaywrightRequest): void => {
      const id = this.requestIds.get(request);

      if (id) {
        this.detector.observeRequestFinished({ id });
      }
    };

    const onRequestFailed = (request: PlaywrightRequest): void => {
      const id = this.requestIds.get(request);

      if (id) {
        this.detector.observeRequestFailure({
          id,
          errorText: request.failure()?.errorText ?? 'request failed',
        });
      }
    };

    const onResponse = (response: PlaywrightResponse): void => {
      const request = response.request();
      const id = this.requestIds.get(request);

      if (!id) {
        return;
      }

      const headers = response.headers();
      const url = response.url();

      this.detector.observeResponse({ id, url, status: response.status(), headers });
      const record = this.detector.requests.get(id);

      if (record && record.inspected === false) {
        const inspection = response
          .text()
          .then((body) => this.detector.observeBody({ id, body }))
          .catch((error: unknown) =>
            this.detector.observeBodyFailure({
              id,
              message: error instanceof Error ? error.message : String(error),
            }),
          );

        this.detector.trackBody(inspection);
      }
    };

    const onPageError = (error: Error): void => {
      this.detector.observePageError({ message: error.message, pageUrl: this.page.url() });
    };

    const onConsole = (message: ConsoleMessage): void => {
      this.detector.observeConsole({
        type: message.type(),
        text: message.text(),
        locationUrl: message.location().url,
        pageUrl: this.page.url(),
      });
    };

    this.page.on('request', onRequest);
    this.page.on('requestfinished', onRequestFinished);
    this.page.on('requestfailed', onRequestFailed);
    this.page.on('response', onResponse);
    this.page.on('pageerror', onPageError);
    this.page.on('console', onConsole);

    this.cleanups.push(
      () => this.page.off('request', onRequest),
      () => this.page.off('requestfinished', onRequestFinished),
      () => this.page.off('requestfailed', onRequestFailed),
      () => this.page.off('response', onResponse),
      () => this.page.off('pageerror', onPageError),
      () => this.page.off('console', onConsole),
    );
  }

  private bindContextEvents(): void {
    const onWebError = (webError: { error(): Error }): void => {
      this.detector.observePageError({
        message: webError.error().message,
        pageUrl: this.page.url(),
      });
    };

    this.context.on('weberror', onWebError);
    this.cleanups.push(() => this.context.off('weberror', onWebError));
  }

  private probe(): DomProbe {
    return {
      isVisible: async (selector) => {
        try {
          return await this.page.locator(selector).first().isVisible();
        } catch {
          return false;
        }
      },
      count: async (selector) => {
        try {
          return await this.page.locator(selector).count();
        } catch {
          return 0;
        }
      },
      text: async (selector) => {
        try {
          return await this.page.locator(selector).first().innerText({ timeout: 500 });
        } catch {
          return '';
        }
      },
      attribute: async (selector, name) => {
        try {
          return await this.page.locator(selector).first().getAttribute(name);
        } catch {
          return null;
        }
      },
    };
  }

  setSessionEstablished(value: boolean): void {
    this.detector.setSessionEstablished(value);
  }

  setContract(contract: PageContract): void {
    this.detector.setContract(contract);
  }

  /**
   * Wait for the complete positive contract: expected actions and their bodies,
   * DOM/content/binding conditions, a hidden overlay, drained diagnostics and
   * the action-scoped quiet window. All work shares one deadline.
   */
  async waitForReady(
    contract: PageContract,
    since: number,
    deadline = Date.now() + this.timeoutMs,
  ): Promise<void> {
    this.detector.setContract(contract);

    if (this.detector.sessionEstablished) {
      this.detector.assertNotOnLoginPage(since, this.page.url());
    }

    this.detector.assertNoProblems();

    try {
      await this.detector.waitForActions(
        contract.actions.map((action) => action.route),
        since,
        deadline,
      );

      const overlay: PageContract['conditions'][number] = {
        kind: 'hidden',
        selector: '#wrap-loading',
        label: 'loading overlay must be hidden',
      };
      const conditions = contract.conditions.some(
        (condition) => condition.kind === 'hidden' && condition.selector === '#wrap-loading',
      )
        ? contract.conditions
        : [...contract.conditions, overlay];

      await waitForConditions(conditions, this.probe(), deadline);
      await this.detector.waitForQuiet(since, deadline);
      await this.detector.drain();

      if (this.detector.sessionEstablished) {
        this.detector.assertNotOnLoginPage(since, this.page.url());
      }

      this.detector.observeMarkup(await this.page.content());

      for (const warning of this.detector.warnings() as Array<{ message: string }>) {
        if (!this.reportedWarnings.has(warning.message)) {
          this.reportedWarnings.add(warning.message);
          console.warn(`[ui-smoke warning] ${warning.message}`);
        }
      }

      await waitForConditions([overlay], this.probe(), deadline);
      this.detector.assertNoProblems();
    } catch (error) {
      throw this.detector.withProblems(error);
    }
  }

  async waitForActions(routes: string[], since: number, deadline: number): Promise<unknown[]> {
    return this.detector.waitForActions(routes, since, deadline);
  }

  async waitForQuiet(since: number, deadline?: number): Promise<void> {
    return this.detector.waitForQuiet(since, deadline);
  }

  getAction(route: string, since = 0): ActionRecordView | undefined {
    return this.detector.getAction(route, since) as ActionRecordView | undefined;
  }

  getJson(route: string, since = 0): unknown {
    return this.detector.getJson(route, since);
  }

  observedRoutes(since = 0): string[] {
    return this.detector.observedRoutes(since);
  }

  pendingRoutes(since = 0): string[] {
    return this.detector.pendingRoutes(since);
  }

  assertNoProblems(): void {
    this.detector.assertNoProblems();
  }

  assertNotOnLoginPage(since: number): void {
    this.detector.assertNotOnLoginPage(since, this.page.url());
  }

  problems(): unknown[] {
    return this.detector.problems();
  }

  warnings(): unknown[] {
    return this.detector.warnings();
  }

  dispose(): void {
    this.detector.beginTeardown();

    for (const cleanup of this.cleanups.splice(0)) {
      cleanup();
    }
  }
}
