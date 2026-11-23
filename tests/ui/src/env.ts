import { readFileSync, realpathSync } from 'node:fs';
import path from 'node:path';

export const APP_LOCALE = 'en_US';
export const BROWSER_LOCALE = 'en-US';
export const FIXED_USER_AGENT = 'sysPass-ui-smoke/1.0 (+https://github.com/nuxsmin/sysPass)';

const LOOPBACK_ORIGIN = /^http:\/\/127\.0\.0\.1:(\d{4,5})$/;

export function assertRunOwnedBaseUrl(baseUrl: string): void {
  const runRoot = process.env.SP_UI_RUN_ROOT;
  const runId = process.env.SP_UI_RUN_ID;
  const match = LOOPBACK_ORIGIN.exec(baseUrl);

  if (!runRoot || !runId) {
    throw new Error('UI smoke run context is missing; start the suite through scripts/ui-smoke.sh');
  }

  if (!match) {
    throw new Error(`UI smoke origin must be runner-owned loopback, received ${baseUrl}`);
  }

  const port = Number.parseInt(match[1] ?? '', 10);

  if (port < 1024 || port > 65535) {
    throw new Error(`UI smoke origin port is not a valid ephemeral port: ${port}`);
  }

  if (realpathSync(runRoot) !== runRoot) {
    throw new Error('UI smoke run root must not be a symlink');
  }

  const owner = readFileSync(path.join(runRoot, 'owner'), 'utf8').trim();

  if (owner !== runId) {
    throw new Error('UI smoke origin does not belong to this run');
  }

  if (process.env.SP_UI_BASE_URL !== baseUrl) {
    throw new Error('UI smoke base URL does not match the runner-owned origin');
  }
}

export function getVerifiedBaseUrl(fallback = 'http://127.0.0.1:1'): string {
  const baseUrl = process.env.SP_UI_BASE_URL ?? fallback;

  assertRunOwnedBaseUrl(baseUrl);

  return baseUrl;
}
