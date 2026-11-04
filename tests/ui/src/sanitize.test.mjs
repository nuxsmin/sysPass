import assert from 'node:assert/strict';
import test from 'node:test';

import {
  REDACTED,
  SCREENSHOT_MASK_SELECTORS,
  maskedScreenshotOptions,
  normalizeFailingRequest,
  sanitizeConsoleEntry,
  sanitizeDom,
  sanitizePageError,
  sanitizeText,
  scrubUrl,
} from './sanitize.mjs';

const CANARY_URL = 'CANARY_URL';
const CANARY_CONSOLE = 'CANARY_CONSOLE_SECRET_77ab';
const CANARY_DOM = 'CANARY_DOM_SECRET_51de';
const CANARY_ERROR = 'CANARY_ERROR_SECRET_4b22';

test('scrubUrl removes sensitive and unknown query values but retains the action route', () => {
  const url = scrubUrl(
    `http://127.0.0.1:54321/index.php?r=account/view/1002&token=${CANARY_URL}&next=%2Fsecret`,
  );

  assert.equal(
    url,
    `http://127.0.0.1:54321/index.php?r=account/view/1002&token=${REDACTED}&next=${REDACTED}`,
  );
  assert.doesNotMatch(url, new RegExp(CANARY_URL));
});

test('scrubUrl rejects credentials, fragments, data and blob payloads', () => {
  assert.doesNotMatch(scrubUrl('http://user:pass@127.0.0.1:54321/index.php?r=index'), /user|pass@/);
  assert.doesNotMatch(scrubUrl(`http://127.0.0.1:54321/index.php#${CANARY_URL}`), new RegExp(CANARY_URL));
  assert.equal(scrubUrl(`data:text/html,<p>${CANARY_URL}</p>`), 'data:[REDACTED]');
  assert.equal(scrubUrl('blob:http://127.0.0.1:54321/abc'), 'blob:[REDACTED]');
});

test('sanitizeText scrubs key/value secrets, JSON values, cookies, bearer tokens and URLs', () => {
  const text = sanitizeText(
    [
      `password=${CANARY_URL}`,
      `{"token":"${CANARY_CONSOLE}","csrfToken":"${CANARY_URL}"}`,
      `Cookie: PHPSESSID=${CANARY_CONSOLE}; other=value`,
      `Authorization: Bearer ${CANARY_ERROR}`,
      `see http://127.0.0.1:54321/index.php?r=login/index&key=${CANARY_URL}`,
    ].join('\n'),
  );

  for (const canary of [CANARY_URL, CANARY_CONSOLE, CANARY_ERROR]) {
    assert.doesNotMatch(text, new RegExp(canary), `canary ${canary} must be scrubbed`);
  }

  assert.match(text, /r=login\/index/);
  assert.match(text, /\[REDACTED\]/);
});

test('sanitizeText preserves source file and line positions', () => {
  const text = sanitizeText(
    [
      'error at login.ts:56',
      'at specs/login.spec.ts:12:3',
      'password: CANARY_CONSOLE_SECRET_77ab',
      'login: CANARY_CONSOLE_SECRET_77ab',
    ].join('\n'),
  );

  assert.match(text, /login\.ts:56/);
  assert.match(text, /specs\/login\.spec\.ts:12:3/);
  assert.doesNotMatch(text, /login\.ts:\[REDACTED\]/);
  assert.doesNotMatch(text, new RegExp(CANARY_CONSOLE));
});

test('sanitizeText removes private key blocks and raw JWTs', () => {
  const text = sanitizeText(
    [
      '-----BEGIN RSA PRIVATE KEY-----',
      CANARY_ERROR,
      '-----END RSA PRIVATE KEY-----',
      `token eyJhbGciOiJIUzI1NiJ9.${CANARY_CONSOLE}.signature`,
    ].join('\n'),
  );

  assert.doesNotMatch(text, new RegExp(CANARY_ERROR));
  assert.doesNotMatch(text, new RegExp(CANARY_CONSOLE));
});

test('sanitizeText bounds evidence size', () => {
  const text = sanitizeText('x'.repeat(500), { maxLength: 40 });

  assert.ok(text.length <= 40 + '\n…[truncated]'.length);
  assert.match(text, /truncated/);
});

test('sanitizeDom drops scripts, values, textareas and sensitive regions', () => {
  const dom = sanitizeDom(
    [
      `<script>const token = "${CANARY_DOM}";</script>`,
      `<style>.x{content:"${CANARY_DOM}"}</style>`,
      `<input id="pass" name="password" value="${CANARY_DOM}">`,
      `<textarea name="secret">${CANARY_DOM}</textarea>`,
      `<div id="pass">${CANARY_DOM}</div>`,
      `<div data-sensitive>${CANARY_DOM}</div>`,
      `<p>safe page text</p>`,
    ].join(''),
  );

  assert.doesNotMatch(dom, new RegExp(CANARY_DOM));
  assert.match(dom, /safe page text/);
  assert.match(dom, /\[REDACTED\]/);
});

test('maskedScreenshotOptions masks form fields and explicitly sensitive regions', () => {
  assert.ok(SCREENSHOT_MASK_SELECTORS.includes('input'));
  assert.ok(SCREENSHOT_MASK_SELECTORS.includes('textarea'));
  assert.ok(SCREENSHOT_MASK_SELECTORS.includes('[data-sensitive]'));

  assert.deepEqual(maskedScreenshotOptions(), {
    mask: SCREENSHOT_MASK_SELECTORS,
    animations: 'disabled',
    caret: 'hide',
    fullPage: false,
    scale: 'css',
  });
});

test('normalizeFailingRequest keeps route, method and status but scrubs the URL', () => {
  const request = normalizeFailingRequest({
    route: 'account/index',
    method: 'GET',
    status: 500,
    url: `http://127.0.0.1:54321/index.php?r=account/index&token=${CANARY_URL}`,
    error: `token=${CANARY_ERROR}`,
  });

  assert.deepEqual(request, {
    route: 'account/index',
    method: 'GET',
    status: 500,
    url: `http://127.0.0.1:54321/index.php?r=account/index&token=${REDACTED}`,
    error: `token=${REDACTED}`,
  });
});

test('console entries and page errors keep safe source information without secrets', () => {
  const consoleEntry = sanitizeConsoleEntry({
    type: 'error',
    text: `token=${CANARY_CONSOLE}`,
    locationUrl: `http://127.0.0.1:54321/js/app.js?token=${CANARY_URL}:10:2`,
  });

  assert.equal(consoleEntry.type, 'error');
  assert.doesNotMatch(consoleEntry.text, new RegExp(CANARY_CONSOLE));
  assert.doesNotMatch(consoleEntry.locationUrl, new RegExp(CANARY_URL));
  assert.match(consoleEntry.locationUrl, /app\.js/);

  const pageError = sanitizePageError({
    message: `password=${CANARY_ERROR}`,
    stack: `Error: password=${CANARY_ERROR}\n    at http://127.0.0.1:54321/js/app.js?token=${CANARY_URL}:2:3`,
    source: `http://127.0.0.1:54321/js/app.js?token=${CANARY_URL}:2:3`,
    pageUrl: `http://127.0.0.1:54321/index.php?r=index&token=${CANARY_URL}`,
  });

  assert.doesNotMatch(pageError.message, new RegExp(CANARY_ERROR));
  assert.doesNotMatch(pageError.stack, new RegExp(CANARY_URL));
  assert.doesNotMatch(pageError.source, new RegExp(CANARY_URL));
  assert.doesNotMatch(pageError.pageUrl, new RegExp(CANARY_URL));
  assert.match(pageError.source, /app\.js:2:3/);
  assert.match(pageError.pageUrl, /r=index/);
});
