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
