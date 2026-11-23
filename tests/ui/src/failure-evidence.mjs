import {
  normalizeFailingRequest,
  sanitizeConsoleEntry,
  sanitizeEvidencePath,
  sanitizePageError,
  sanitizeText,
  scrubUrl,
} from './sanitize.mjs';
import { classifyFailure } from './summary.mjs';

/**
 * @param {{status?: string | undefined, expectedStatus?: string | undefined}} result
 * @returns {boolean}
 */
export function shouldCaptureFailure({ status, expectedStatus = 'passed' } = {}) {
  if (!status || status === 'skipped' || status === 'unknown') {
    return false;
  }

  if (status === expectedStatus) {
    return false;
  }

  return status === 'failed' || status === 'timedOut' || status === 'interrupted';
}

function isFailingRequest(request) {
  return (
    (typeof request.status === 'number' && request.status >= 400) ||
    (typeof request.error === 'string' && request.error.length > 0)
  );
}

/**
 * Build sanitized failure evidence for one failed attempt. DOM snapshots and
 * request/response bodies are deliberately not part of this record.
 *
 * @param {object} input
 */
export function buildFailureRecord({
  runId,
  test,
  attempt = 0,
  status = 'failed',
  error = {},
  pageUrl = '',
  requests = [],
  consoleEntries = [],
  pageErrors = [],
  screenshot = 'screenshot.png',
}) {
  const message = sanitizeText(error?.message ?? '', { maxLength: 2000 });
  const failingRequests = requests
    .filter(isFailingRequest)
    .slice(0, 20)
    .map((request) => normalizeFailingRequest(request));
  const sanitizedConsole = consoleEntries
    .slice(0, 200)
    .map((entry) => sanitizeConsoleEntry(entry));
  const sanitizedPageErrors = pageErrors
    .slice(0, 50)
    .map((entry) => sanitizePageError(entry));
  let failureClass = classifyFailure(message);

  if (failingRequests[0]) {
    failureClass = failingRequests[0].status >= 400 ? 'http' : 'request-failed';
  } else if (sanitizedPageErrors[0]) {
    failureClass = 'page-error';
  } else if (sanitizedConsole[0]?.type === 'error') {
    failureClass = 'console';
  }

  return {
    schemaVersion: 1,
    runId: sanitizeText(runId, { maxLength: 200 }),
    capturedAt: new Date().toISOString(),
    test: {
      title: sanitizeText(test?.title ?? '', { maxLength: 500 }),
      file: sanitizeEvidencePath(test?.file ?? ''),
      line: Number(test?.line ?? 0),
      project: sanitizeEvidencePath(test?.project ?? ''),
    },
    attempt: Number(attempt) + 1,
    firstAttempt: Number(attempt) === 0,
    status: String(status),
    failureClass,
    error: {
      message,
      stack: sanitizeText(error?.stack ?? '', { maxLength: 6000 }),
    },
    pageUrl: scrubUrl(pageUrl),
    failingRequest: failingRequests[0] ?? null,
    requests: failingRequests,
    console: sanitizedConsole,
    pageErrors: sanitizedPageErrors,
    screenshot: screenshot ? String(screenshot) : null,
  };
}
