/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

export const TELEMETRY_ENVIRONMENT = Object.freeze({
  DO_NOT_TRACK: '1',
  PLAYWRIGHT_CLI_ANALYTICS: '0',
});

function isEnabled(value) {
  return String(value ?? '').toLowerCase() === 'true';
}

/**
 * Resolve the browser capture policy for one run.
 *
 * CI always forces forbidden capture off, regardless of local flags. A local
 * trace is only available as an explicit opt-in against the runner-owned
 * synthetic instance and is never uploaded.
 *
 * @param {Record<string, string | undefined>} [env]
 * @param {object} [options]
 * @param {boolean} [options.syntheticRun]
 */
export function resolveCapturePolicy(env = process.env, { syntheticRun = false } = {}) {
  const ci = isEnabled(env.CI);
  const traceRequested = env.SP_UI_LOCAL_TRACE === '1';
  const retryRequested = env.SP_UI_DIAGNOSTIC_RETRY === '1';
  const reasons = [];
  let traceError = null;

  let trace = 'off';

  if (ci) {
    if (traceRequested) {
      reasons.push('CI forces trace off even when SP_UI_LOCAL_TRACE is set');
    }
  } else if (traceRequested) {
    if (syntheticRun) {
      trace = 'on';
      reasons.push('local trace enabled against the synthetic isolated instance');
    } else {
      traceError = 'a local trace requires explicit opt-in against the synthetic isolated instance';
      reasons.push(traceError);
    }
  }

  if (ci && retryRequested) {
    reasons.push(
      'explicit diagnostic retry enabled; a retry-success run is reported flaky and is not complete Tier A evidence',
    );
  }

  return {
    ci,
    trace,
    video: 'off',
    screenshot: 'off',
    telemetry: false,
    uploadTrace: false,
    retries: retryRequested ? 1 : 0,
    failOnFlakyTests: retryRequested,
    traceError,
    reasons,
    environment: { ...TELEMETRY_ENVIRONMENT },
  };
}
