import path from 'node:path';

export const REDACTED = '[REDACTED]';
export const TRUNCATED = '\n…[truncated]';

export const SCREENSHOT_MASK_SELECTORS = [
  'input',
  'textarea',
  'select',
  '[contenteditable="true"]',
  '[data-sensitive]',
  '[data-sp-ui-sensitive]',
  '#pass',
  '#user',
  '.selectize-input',
];

const SENSITIVE_KEY =
  /pass(?:word|wd)?|pwd|token|csrf|secret|api[-_]?key|private[-_]?key|auth(?:orization)?|session(?:id)?|cookie|master(?:password)?|username|login|email/i;

const SAFE_ROUTE = /^[A-Za-z0-9_./-]{1,160}$/;
const URL_PATTERN = /\b(?:https?:\/\/|wss?:\/\/)[^\s"'<>]+/gi;
const JWT_PATTERN = /\beyJ[A-Za-z0-9_-]*\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\b/g;
const PRIVATE_KEY_PATTERN =
  /-----BEGIN [A-Z0-9 ]*PRIVATE KEY-----[\s\S]*?-----END [A-Z0-9 ]*PRIVATE KEY-----/g;
const BEARER_PATTERN = /\b(Bearer|Basic)\s+[A-Za-z0-9._~+/=-]+/gi;
const COOKIE_HEADER_PATTERN = /\b(Set-Cookie|Cookie)\s*:\s*[^\n\r]+/gi;
const ABSOLUTE_FILE_PATH_PATTERN =
  /(?<![:\w/])((?:\/[A-Za-z0-9_.@%+-]+){2,})(:\d+(?::\d+)?)?/g;

function asText(value) {
  return value === undefined || value === null ? '' : String(value);
}

function bound(text, maxLength) {
  if (text.length <= maxLength) {
    return text;
  }

  return `${text.slice(0, maxLength)}${TRUNCATED}`;
}

/**
 * @param {string} key
 * @returns {boolean}
 */
export function isSensitiveKey(key) {
  return SENSITIVE_KEY.test(key);
}

function scrubSearch(search) {
  if (!search) {
    return '';
  }

  const pairs = search.replace(/^\?/, '').split('&').filter(Boolean);

  return `?${pairs
    .map((pair) => {
      const separator = pair.indexOf('=');

      if (separator === -1) {
        return pair;
      }

      const key = pair.slice(0, separator);
      const value = pair.slice(separator + 1);

      if (key === 'r' && SAFE_ROUTE.test(value)) {
        return `${key}=${value}`;
      }

      return `${key}=${REDACTED}`;
    })
    .join('&')}`;
}

/**
 * Normalize a URL for diagnostics without retaining sensitive query values.
 *
 * Only the action route (`r`) keeps its value because action attribution needs
 * it. Every other query value, credentials, fragments and opaque payload URLs
 * are dropped or redacted.
 *
 * @param {string} rawUrl
 * @returns {string}
 */
export function scrubUrl(rawUrl) {
  const raw = asText(rawUrl).trim();

  if (raw === '') {
    return '';
  }

  if (/^data:/i.test(raw)) {
    return 'data:[REDACTED]';
  }

  if (/^blob:/i.test(raw)) {
    return 'blob:[REDACTED]';
  }

  if (/^about:/i.test(raw)) {
    return 'about:blank';
  }

  try {
    const url = new URL(raw, 'http://ui-smoke.invalid');
    const path = url.pathname || '/';
    const search = scrubSearch(url.search);

    if (url.protocol === 'http:' || url.protocol === 'https:' || url.protocol === 'ws:' || url.protocol === 'wss:') {
      return `${url.protocol}//${url.host}${path}${search}`;
    }

    return `${url.protocol}[REDACTED]`;
  } catch {
    return bound(sanitizeText(raw, { maxLength: 500 }), 500);
  }
}

/**
 * Scrub free-form text that may be logged or written to an evidence report.
 *
 * @param {unknown} value
 * @param {object} [options]
 * @param {number} [options.maxLength]
 * @returns {string}
 */
export function sanitizeText(value, { maxLength = 4000 } = {}) {
  let text = asText(value);

  text = text.replace(PRIVATE_KEY_PATTERN, `-----BEGIN PRIVATE KEY-----${REDACTED}-----END PRIVATE KEY-----`);
  text = text.replace(COOKIE_HEADER_PATTERN, `Cookie: ${REDACTED}`);
  text = text.replace(BEARER_PATTERN, `$1 ${REDACTED}`);
  text = text.replace(JWT_PATTERN, REDACTED);
  text = text.replace(URL_PATTERN, (match) => scrubUrl(match));
  text = text.replace(
    ABSOLUTE_FILE_PATH_PATTERN,
    (match, filePath, position = '') => `${sanitizeEvidencePath(filePath)}${position}`,
  );

  text = text.replace(
    /("(?:[^"\\]|\\.)*"\s*:\s*)"(?:[^"\\]|\\.)*"/g,
    (match, prefix, offset, whole) => {
      const key = prefix.replace(/["\s:]/g, '');

      return isSensitiveKey(key) ? `${prefix}"${REDACTED}"` : match;
    },
  );

  text = text.replace(
    /\b([A-Za-z0-9_.-]+)(\s*=\s*)("[^"]*"|'[^']*'|[^\s,&;]+)/g,
    (match, key, separator) => (isSensitiveKey(key) ? `${key}${separator}${REDACTED}` : match),
  );

  text = text.replace(
    /\b([A-Za-z0-9_.-]+)(\s*:\s*)("[^"]*"|'[^']*'|[^\s,&;]+)/g,
    (match, key, separator, value) => {
      if (!isSensitiveKey(key)) {
        return match;
      }

      if (/\.[A-Za-z0-9]+$/.test(key) && /^\d+(?::\d+)?$/.test(value)) {
        return match;
      }

      return `${key}${separator}${REDACTED}`;
    },
  );

  return bound(text, maxLength);
}

/**
 * Keep only a repository-relative path. Absolute or opaque paths are reduced
 * to a known repository anchor or, failing that, the basename, so evidence
 * never carries a developer home directory or worktree root.
 *
 * @param {unknown} value
 * @param {object} [options]
 * @param {string[]} [options.baseDirs]
 * @returns {string}
 */
export function sanitizeEvidencePath(value, { baseDirs = [] } = {}) {
  const raw = asText(value).trim();

  if (raw === '') {
    return '';
  }

  const normalized = raw.replaceAll('\\', '/');
  const absolute = normalized.startsWith('/') || /^[A-Za-z]:\//.test(normalized) || normalized.startsWith('//');

  for (const base of baseDirs) {
    const normalizedBase = asText(base).trim().replaceAll('\\', '/').replace(/\/+$/, '');

    if (normalizedBase !== '' && normalized.startsWith(`${normalizedBase}/`)) {
      return normalized.slice(normalizedBase.length + 1);
    }
  }

  if (!absolute) {
    const relative = normalized.replace(/^\.\//, '');

    return relative.split('/').includes('..') ? path.posix.basename(normalized) : relative;
  }

  for (const anchor of ['tests/ui/', 'tests/', 'specs/', 'src/']) {
    const index = normalized.indexOf(`/${anchor}`);

    if (index !== -1) {
      return normalized.slice(index + 1);
    }
  }

  return path.posix.basename(normalized);
}

/**
 * Reduce a captured DOM to safe structural evidence. The harness does not
 * persist DOM attachments; this scrubber is used by tests and defensive
 * reporting so a DOM snapshot can never carry field values, scripts or
 * explicitly sensitive regions.
 *
 * @param {string} html
 * @param {object} [options]
 * @param {number} [options.maxLength]
 * @returns {string}
 */
export function sanitizeDom(html, { maxLength = 20_000 } = {}) {
  let result = asText(html);

  result = result.replace(/<!--[\s\S]*?-->/g, '');
  result = result.replace(/<(script|style|noscript|template)\b[^>]*>[\s\S]*?<\/\1>/gi, '');
  result = result.replace(
    /<(input|textarea|select)\b[^>]*\bvalue\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]+)/gi,
    (match) => match.replace(/\bvalue\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]+)/i, `value="${REDACTED}"`),
  );
  result = result.replace(/<textarea\b([^>]*)>[\s\S]*?<\/textarea>/gi, `<textarea$1>${REDACTED}</textarea>`);
  result = result.replace(/<select\b([^>]*)>[\s\S]*?<\/select>/gi, `<select$1>${REDACTED}</select>`);
  result = result.replace(
    /<([a-z][a-z0-9-]*)\b([^>]*\bdata-sensitive\b[^>]*)>[\s\S]*?<\/\1>/gi,
    `<${1}${2}>${REDACTED}</${1}>`,
  );
  result = result.replace(
    /<([a-z][a-z0-9-]*)\b([^>]*(?:id|name|class)\s*=\s*["'][^"']*(?:pass|pwd|token|csrf|secret|api[-_]?key|private[-_]?key|auth|session|cookie|master|username|login|email)[^"']*["'][^>]*)>[\s\S]*?<\/\1>/gi,
    `<${1}${2}>${REDACTED}</${1}>`,
  );

  return bound(sanitizeText(result, { maxLength }), maxLength);
}

/**
 * @param {object} request
 * @param {string | null} [request.route]
 * @param {string} [request.method]
 * @param {number} [request.status]
 * @param {string} [request.url]
 * @param {string} [request.error]
 * @returns {{route: string | null, method: string, status: number | undefined, url: string, error: string | undefined}}
 */
export function normalizeFailingRequest({ route = null, method = 'GET', status, url = '', error } = {}) {
  return {
    route: route ?? null,
    method: asText(method) || 'GET',
    status,
    url: scrubUrl(url),
    error: error === undefined ? undefined : sanitizeText(error, { maxLength: 500 }),
  };
}

/**
 * @param {object} entry
 * @param {string} entry.type
 * @param {string} entry.text
 * @param {string} [entry.locationUrl]
 * @returns {{type: string, text: string, locationUrl: string}}
 */
export function sanitizeConsoleEntry({ type = 'log', text = '', locationUrl = '' } = {}) {
  return {
    type: asText(type) || 'log',
    text: sanitizeText(text, { maxLength: 2000 }),
    locationUrl: sanitizeSource(locationUrl),
  };
}

/**
 * @param {object} error
 * @param {string} [error.message]
 * @param {string} [error.stack]
 * @param {string} [error.source]
 * @param {string} [error.pageUrl]
 * @returns {{message: string, stack: string, source: string, pageUrl: string}}
 */
export function sanitizePageError({ message = '', stack = '', source = '', pageUrl = '' } = {}) {
  return {
    message: sanitizeText(message, { maxLength: 2000 }),
    stack: sanitizeText(stack, { maxLength: 6000 }),
    source: sanitizeSource(source),
    pageUrl: scrubUrl(pageUrl),
  };
}

/**
 * Scrub a source location such as `http://host/js/app.js?token=x:10:2` while
 * keeping the safe file/line information required for global JS failures.
 *
 * @param {string} source
 * @returns {string}
 */
export function sanitizeSource(source) {
  const raw = asText(source).trim();

  if (raw === '') {
    return '';
  }

  const location = /^(.*?)(:\d+(?::\d+)?)$/.exec(raw);
  const base = location ? location[1] : raw;
  const position = location ? location[2] : '';
  let scrubbed = scrubUrl(base);

  try {
    const url = new URL(base, 'http://ui-smoke.invalid');

    if (url.protocol === 'http:' || url.protocol === 'https:') {
      scrubbed = `${url.protocol}//${url.host}${url.pathname}`;
    }
  } catch {
    // Keep the already-scrubbed value for opaque sources.
  }

  return `${scrubbed}${position}`;
}

/**
 * @param {string[]} [selectors]
 * @returns {{mask: string[], animations: 'disabled', caret: 'hide', fullPage: false, scale: 'css'}}
 */
export function maskedScreenshotOptions(selectors = SCREENSHOT_MASK_SELECTORS) {
  return {
    mask: selectors,
    animations: 'disabled',
    caret: 'hide',
    fullPage: false,
    scale: 'css',
  };
}
