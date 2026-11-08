import { mkdirSync, writeFileSync } from 'node:fs';
import path from 'node:path';

import type {
  BrowserContext,
  ConsoleMessage,
  Page,
  Request as PlaywrightRequest,
  Response as PlaywrightResponse,
  TestInfo,
} from '@playwright/test';

import { parseActionRoute } from './diagnostics.mjs';
import { buildFailureRecord, shouldCaptureFailure } from './failure-evidence.mjs';
import {
  maskedScreenshotOptions,
  sanitizeConsoleEntry,
  sanitizePageError,
} from './sanitize.mjs';

interface CapturedConsoleEntry {
  type: string;
  text: string;
  locationUrl: string;
}

interface CapturedPageError {
  message: string;
  stack: string;
  source: string;
  pageUrl: string;
}

interface CapturedRequest {
  route: string | null;
  method: string;
  url: string;
  status?: number;
  error?: string;
}

function sourceFromStack(stack: string): string {
  const match = /(?:\(|at\s+)((?:https?:\/\/|file:|data:|blob:)[^\s)]+:\d+:\d+)\)?/.exec(stack ?? '');

  return match?.[1] ?? '';
}

/**
 * Captures sanitized evidence for a failed attempt only. It never attaches a
 * DOM snapshot, request/response body, trace, video or authentication state.
 */
export class FailureEvidenceRecorder {
  private readonly consoleEntries: CapturedConsoleEntry[] = [];
  private readonly pageErrors: CapturedPageError[] = [];
  private readonly requests = new Map<PlaywrightRequest, CapturedRequest>();
  private readonly cleanups: Array<() => void> = [];
  private finalized = false;

  constructor(
    private readonly page: Page,
    private readonly context: BrowserContext,
    private readonly testInfo: TestInfo,
  ) {
    this.attach();
  }

  private attach(): void {
    const onConsole = (message: ConsoleMessage): void => {
      if (this.consoleEntries.length >= 250) {
        return;
      }

      this.consoleEntries.push({
        type: message.type(),
        text: message.text(),
        locationUrl: message.location().url,
      });
    };

    const onPageError = (error: Error): void => {
      if (this.pageErrors.length >= 50) {
        return;
      }

      this.pageErrors.push({
        message: error.message,
        stack: error.stack ?? '',
        source: sourceFromStack(error.stack ?? ''),
        pageUrl: this.page.url(),
      });
    };

    const onRequest = (request: PlaywrightRequest): void => {
      this.requests.set(request, {
        route: parseActionRoute(request.url()),
        method: request.method(),
        url: request.url(),
      });
    };

    const onResponse = (response: PlaywrightResponse): void => {
      const request = response.request();
      const record = this.requests.get(request);

      if (record) {
        record.status = response.status();
      }
    };

    const onRequestFailed = (request: PlaywrightRequest): void => {
      const record = this.requests.get(request);

      if (record) {
        record.error = request.failure()?.errorText ?? 'request failed';
      }
    };

    const onWebError = (webError: { error(): Error }): void => {
      if (this.pageErrors.length >= 50) {
        return;
      }

      const error = webError.error();

      this.pageErrors.push({
        message: error.message,
        stack: error.stack ?? '',
        source: sourceFromStack(error.stack ?? ''),
        pageUrl: this.page.url(),
      });
    };

    this.page.on('console', onConsole);
    this.page.on('pageerror', onPageError);
    this.page.on('request', onRequest);
    this.page.on('response', onResponse);
    this.page.on('requestfailed', onRequestFailed);
    this.context.on('weberror', onWebError);

    this.cleanups.push(
      () => this.page.off('console', onConsole),
      () => this.page.off('pageerror', onPageError),
      () => this.page.off('request', onRequest),
      () => this.page.off('response', onResponse),
      () => this.page.off('requestfailed', onRequestFailed),
      () => this.context.off('weberror', onWebError),
    );
  }

  private detach(): void {
    for (const cleanup of this.cleanups.splice(0)) {
      cleanup();
    }
  }

  async finalize(): Promise<void> {
    if (this.finalized) {
      return;
    }

    this.finalized = true;
    this.detach();

    if (
      !shouldCaptureFailure({
        status: this.testInfo.status,
        expectedStatus: this.testInfo.expectedStatus,
      })
    ) {
      return;
    }

    const outputRoot =
      process.env.SP_UI_OUTPUT_DIR ?? path.dirname(this.testInfo.outputDir);
    const fileStem = path
      .basename(this.testInfo.file)
      .replace(/\.[^.]+$/, '')
      .replace(/[^A-Za-z0-9]+/g, '-')
      .slice(0, 60);
    const outputDirectory = path.join(
      outputRoot,
      'failures',
      `${fileStem || 'test'}-${this.testInfo.project.name.replace(/[^A-Za-z0-9]+/g, '-')}-line-${this.testInfo.line}-attempt-${this.testInfo.retry + 1}`,
    );
    const screenshotFile = path.join(outputDirectory, 'screenshot.png');
    let screenshot: string | null = 'screenshot.png';

    mkdirSync(outputDirectory, { recursive: true });

    try {
      if (!this.page.isClosed()) {
        const options = maskedScreenshotOptions();

        await this.page.screenshot({
          path: screenshotFile,
          mask: options.mask.map((selector) => this.page.locator(selector)),
          animations: options.animations,
          caret: options.caret,
          fullPage: options.fullPage,
          scale: options.scale,
        });
      } else {
        screenshot = null;
      }
    } catch {
      screenshot = null;
    }

    const error = this.testInfo.error;
    const record = buildFailureRecord({
      runId: process.env.SP_UI_RUN_ID ?? '',
      test: {
        title: this.testInfo.title,
        file: this.testInfo.file,
        line: this.testInfo.line,
        project: this.testInfo.project.name,
      },
      attempt: this.testInfo.retry,
      status: this.testInfo.status ?? 'failed',
      error: {
        message: error?.message ?? '',
        stack: error?.stack ?? '',
      },
      pageUrl: this.page.isClosed() ? '' : this.page.url(),
      requests: [...this.requests.values()],
      consoleEntries: this.consoleEntries,
      pageErrors: this.pageErrors,
      screenshot,
    });

    writeFileSync(path.join(outputDirectory, 'failure.json'), `${JSON.stringify(record, null, 2)}\n`);
    writeFileSync(
      path.join(outputDirectory, 'console.log'),
      `${record.console
        .map((entry: { type: string; text: string; locationUrl: string }) => `[${entry.type}] ${entry.text}${entry.locationUrl ? ` (${entry.locationUrl})` : ''}`)
        .join('\n')}\n`,
    );
  }
}

export function sanitizeCapturedForTest(entries: CapturedConsoleEntry[]): CapturedConsoleEntry[] {
  return entries.map((entry) => sanitizeConsoleEntry(entry));
}

export function sanitizePageErrorsForTest(errors: CapturedPageError[]): CapturedPageError[] {
  return errors.map((error) => sanitizePageError(error));
}