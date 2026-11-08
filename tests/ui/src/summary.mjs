import {
  annotateKnownFailures,
  extractFailureProblems,
  extractFailureRoute,
  isFailingAttempt,
  isFlakyTest,
  normalizeSpecName,
  parseRegistry,
  REGISTRY_SCHEMA_VERSION,
} from './known-failures.mjs';
import { sanitizeEvidencePath, sanitizeText } from './sanitize.mjs';
import { TIER_A_SPECS, TIER_B_SPECS, TIER_INSTALL_SPECS, TIER_SPECS } from './tiers.mjs';

export { TIER_A_SPECS, TIER_B_SPECS, TIER_INSTALL_SPECS, TIER_SPECS };

const FAILURE_CLASS_PATTERNS = [
  [/test timeout|timed out|timeout of \d+ms/i, 'timeout'],
  [/\bpage-error\b|page error:|unhandled rejection/i, 'page-error'],
  [/\bconsole error\b|console error:/i, 'console'],
  [/\bhttp\b.*HTTP \d{3}|returned HTTP \d{3}/i, 'http'],
  [/\bjson-status\b/i, 'json-status'],
  [/\brequest-failed\b/i, 'request-failed'],
  [/\bbody-inspection\b/i, 'body-inspection'],
  [/\bmarkup\b|msg-critical|toast-error/i, 'markup'],
  [/\bsession-loss\b|session lost/i, 'session-loss'],
  [/\binvalid-response\b/i, 'invalid-response'],
  [/\bmissing-content\b/i, 'missing-content'],
  [/\bempty-body\b/i, 'empty-body'],
  [/\blogin-page\b|\blogin-navigation\b/i, 'login-navigation'],
  [/^\s*expect\(|\btoBe\b|\btoHave\b|\btoEqual\b/i, 'assertion'],
];

/**
 * @param {string} message
 * @returns {string}
 */
export function classifyFailure(message) {
  const text = String(message ?? '');

  for (const [pattern, failureClass] of FAILURE_CLASS_PATTERNS) {
    if (pattern.test(text)) {
      return failureClass;
    }
  }

  return 'test-error';
}

function parseSpecList(specs) {
  return String(specs ?? '')
    .split(/[\s,]+/)
    .map(normalizeSpecName)
    .filter(Boolean);
}

/**
 * Resolve what the run was asked to execute and whether it can represent a
 * complete run of its selected tier. A Tier B selection is never eligible as
 * Tier A evidence and a partial selection of either tier is never complete.
 *
 * @param {object} options
 * @param {string} [options.tier]
 * @param {string} [options.specs]
 * @param {boolean} [options.grep]
 * @param {string[]} [options.availableSpecs]
 * @returns {{tier: string, selectedSpecs: string[], explicit: boolean, partialSelection: boolean, partialReasons: string[]}}
 */
export function resolveSelection({
  tier = 'A',
  specs = '',
  grep = false,
  availableSpecs,
} = {}) {
  const normalizedTier = String(tier ?? 'A').trim().toUpperCase() || 'A';
  const knownTier = Object.hasOwn(TIER_SPECS, normalizedTier);
  const expectedSpecs = knownTier ? TIER_SPECS[normalizedTier] : [];
  const explicitSpecs = parseSpecList(specs);
  const explicit = explicitSpecs.length > 0;
  const selectedSpecs = explicit ? explicitSpecs : [...expectedSpecs];
  const reasons = [];

  if (!knownTier) {
    reasons.push(`tier ${normalizedTier} is not a known UI smoke tier`);
  }

  if (grep) {
    reasons.push('a grep/filtered selection narrows the run');
  }

  if (explicit && knownTier) {
    const missing = expectedSpecs.filter((spec) => !selectedSpecs.includes(spec));
    const unknown = selectedSpecs.filter((spec) => !expectedSpecs.includes(spec));

    if (missing.length > 0) {
      reasons.push(`explicit specs are a subset of Tier ${normalizedTier}; missing ${missing.join(', ')}`);
    }

    if (unknown.length > 0) {
      reasons.push(`explicit specs are outside the Tier ${normalizedTier} set: ${unknown.join(', ')}`);
    }
  }

  const available = new Set(
    ((availableSpecs ?? expectedSpecs) || []).map(normalizeSpecName),
  );
  const notAvailable = expectedSpecs.filter((spec) => !available.has(spec));

  if (notAvailable.length > 0) {
    reasons.push(`tier ${normalizedTier} available specs are missing ${notAvailable.join(', ')}`);
  }

  if (explicit && !knownTier) {
    reasons.push(`explicit specs cannot complete an unknown tier ${normalizedTier}: ${selectedSpecs.join(', ')}`);
  }

  return {
    tier: normalizedTier,
    selectedSpecs,
    explicit,
    partialSelection: reasons.length > 0,
    partialReasons: reasons,
  };
}

/**
 * @param {object} test
 * @param {string} test.title
 * @param {string} test.file
 * @param {string} [test.project]
 * @param {string} [test.expectedStatus]
 * @param {boolean} [test.skipped]
 * @param {object[]} test.attempts
 * @returns {object}
 */
export function summarizeTest({
  title,
  file,
  project = 'chromium',
  expectedStatus = 'passed',
  skipped = false,
  attempts = [],
}) {
  const summarizedAttempts = attempts.map((attempt, index) => {
    const status = attempt.status ?? 'unknown';
    const message = sanitizeText(attempt.error?.message ?? attempt.errorMessage ?? '', {
      maxLength: 2000,
    });
    const stack = sanitizeText(attempt.error?.stack ?? attempt.errorStack ?? '', {
      maxLength: 6000,
    });
    const rawMessage = attempt.error?.message ?? attempt.errorMessage ?? '';
    const route = attempt.route ?? extractFailureRoute(rawMessage);
    const failing = status !== 'passed' && status !== 'skipped' && status !== 'unknown';
    const failureClass = failing ? classifyFailure(message) : null;
    const failureProblems = failing
      ? extractFailureProblems(rawMessage)
      : [];
    const normalizedProblems =
      failureProblems.length > 0
        ? failureProblems
        : failing
          ? [{ route: route ?? null, failureKind: failureClass }]
          : [];

    return {
      attempt: index + 1,
      retryIndex: attempt.retryIndex ?? index,
      firstAttempt: index === 0,
      status,
      durationMs: Number(attempt.durationMs ?? 0),
      failureClass,
      route,
      failureProblems: normalizedProblems,
      errorMessage: message,
      errorStack: stack,
    };
  });

  const firstAttemptStatus = summarizedAttempts[0]?.status ?? (skipped ? 'skipped' : 'not-run');
  const finalStatus = summarizedAttempts.at(-1)?.status ?? (skipped ? 'skipped' : 'not-run');
  const failedAttempts = summarizedAttempts.filter(isFailingAttempt);

  return {
    title: sanitizeText(title, { maxLength: 500 }),
    file: sanitizeEvidencePath(file),
    project: sanitizeEvidencePath(project),
    expectedStatus,
    skipped: skipped || finalStatus === 'skipped',
    attempts: summarizedAttempts,
    firstAttemptStatus,
    finalStatus,
    retries: Math.max(0, summarizedAttempts.length - 1),
    durationMs: summarizedAttempts.reduce((total, attempt) => total + attempt.durationMs, 0),
    failedAttempts: failedAttempts.length,
    failureClass: failedAttempts[0]?.failureClass ?? null,
  };
}

function countByFailureClass(tests) {
  const counts = {};

  for (const test of tests) {
    for (const attempt of test.attempts) {
      if (attempt.failureClass) {
        counts[attempt.failureClass] = (counts[attempt.failureClass] ?? 0) + 1;
      }
    }
  }

  return counts;
}

function createCounts(tests) {
  const skipped = tests.filter((test) => test.skipped).length;
  const attempted = tests.filter((test) => test.attempts.length > 0).length;
  const completed = tests.filter(
    (test) =>
      test.attempts.length > 0 &&
      test.finalStatus !== 'skipped' &&
      test.finalStatus !== 'not-run' &&
      test.finalStatus !== 'unknown',
  ).length;
  const failed = tests.filter(
    (test) => test.finalStatus === 'failed' || test.finalStatus === 'timedOut',
  ).length;
  const timedOut = tests.filter((test) => test.finalStatus === 'timedOut').length;
  const interrupted = tests.filter(
    (test) => test.finalStatus === 'interrupted' || test.firstAttemptStatus === 'interrupted',
  ).length;

  return {
    selected: tests.length,
    attempted,
    completed,
    skipped,
    failed,
    timedOut,
    interrupted,
    notRun: tests.filter((test) => test.attempts.length === 0 && !test.skipped).length,
    retries: tests.reduce((total, test) => total + test.retries, 0),
    firstAttemptPassed: tests.filter((test) => test.firstAttemptStatus === 'passed').length,
    firstAttemptFailed: tests.filter(
      (test) => test.firstAttemptStatus !== 'passed' && test.firstAttemptStatus !== 'skipped' && test.firstAttemptStatus !== 'not-run',
    ).length,
    flaky: tests.filter(
      (test) =>
        test.attempts.length > 1 && test.firstAttemptStatus !== 'passed' && test.finalStatus === 'passed',
    ).length,
  };
}

function sanitizeGlobalErrors(globalErrors) {
  return (globalErrors ?? []).map((error) => {
    const message = sanitizeText(error?.message ?? error ?? '', { maxLength: 2000 });

    return {
      failureClass: classifyFailure(message),
      message,
      stack: sanitizeText(error?.stack ?? '', { maxLength: 6000 }),
    };
  });
}

/**
 * @param {object} input
 * @returns {object}
 */
export function buildRunSummary({
  runId,
  selection,
  commit,
  environment,
  tests,
  startedAt,
  finishedAt,
  durationMs,
  resultStatus = 'passed',
  cancelled = false,
  setupFailure = false,
  globalErrors = [],
  artifacts = {},
  knownFailureRegistry = { schemaVersion: REGISTRY_SCHEMA_VERSION, entries: [] },
  knownFailureRegistryErrors = [],
}) {
  const summarizedTests = tests.map((test) =>
    test.attempts && test.firstAttemptStatus !== undefined ? test : summarizeTest(test),
  );
  const parsedRegistry = parseRegistry(knownFailureRegistry);
  const annotations = annotateKnownFailures(
    summarizedTests,
    parsedRegistry.ok ? parsedRegistry.entries : [],
  );
  const annotatedTests = annotations.tests;
  const knownFailureErrors = [
    ...knownFailureRegistryErrors,
    ...(parsedRegistry.ok ? [] : parsedRegistry.errors),
  ];
  const counts = {
    ...createCounts(annotatedTests),
    knownFailed: annotations.knownFailures.length,
    newFailed: annotations.newFailures.length,
  };
  const failureClasses = countByFailureClass(annotatedTests);
  const safeGlobalErrors = sanitizeGlobalErrors(globalErrors);
  const isCancelled = cancelled || resultStatus === 'interrupted';
  const hasGlobalFailure = safeGlobalErrors.length > 0;
  const partialSelection = selection.partialSelection;
  const hasStaleKnownFailure = annotations.staleEntries.length > 0;

  let status = 'passed';

  if (isCancelled) {
    status = 'cancelled';
  } else if (setupFailure || resultStatus === 'setup-failed') {
    status = 'setup-failed';
  } else if (resultStatus === 'timedout') {
    status = 'timedout';
  } else if (counts.failed > 0 || counts.interrupted > 0 || hasGlobalFailure) {
    status = 'failed';
  } else if (counts.flaky > 0) {
    status = 'flaky';
  } else if (resultStatus === 'failed' || resultStatus === 'unknown') {
    status = 'failed';
  } else if (resultStatus === 'flaky') {
    status = 'flaky';
  } else if (
    resultStatus === 'partial' ||
    partialSelection ||
    counts.skipped > 0 ||
    counts.notRun > 0 ||
    counts.completed !== counts.selected
  ) {
    status = 'partial';
  } else if (resultStatus !== 'passed') {
    status = 'failed';
  }

  if ((knownFailureErrors.length > 0 || hasStaleKnownFailure) && status === 'passed') {
    status = 'failed';
  }

  const completeTierA =
    status === 'passed' &&
    selection.tier === 'A' &&
    !partialSelection &&
    counts.selected > 0 &&
    counts.skipped === 0 &&
    counts.notRun === 0 &&
    counts.completed === counts.selected &&
    Object.keys(failureClasses).length === 0 &&
    safeGlobalErrors.length === 0 &&
    annotations.newFailures.length === 0 &&
    annotations.staleEntries.length === 0 &&
    annotations.unexecutedEntries.length === 0 &&
    knownFailureErrors.length === 0;

  const completeTierB =
    status === 'passed' &&
    selection.tier === 'B' &&
    !partialSelection &&
    counts.selected > 0 &&
    counts.skipped === 0 &&
    counts.notRun === 0 &&
    counts.completed === counts.selected &&
    Object.keys(failureClasses).length === 0 &&
    safeGlobalErrors.length === 0 &&
    annotations.newFailures.length === 0 &&
    annotations.staleEntries.length === 0 &&
    annotations.unexecutedEntries.length === 0 &&
    knownFailureErrors.length === 0;

  /**
   * The installer is a separate, explicitly stateful selection. Its own
   * complete flag is independent of `completeTierA`/`completeTierB`, so a
   * passing wizard result can never be read as Tier A evidence and a Tier A
   * failure can never be hidden by the installer.
   */
  const completeTierInstall =
    status === 'passed' &&
    selection.tier === 'INSTALL' &&
    !partialSelection &&
    counts.selected > 0 &&
    counts.skipped === 0 &&
    counts.notRun === 0 &&
    counts.completed === counts.selected &&
    Object.keys(failureClasses).length === 0 &&
    safeGlobalErrors.length === 0 &&
    annotations.newFailures.length === 0 &&
    annotations.staleEntries.length === 0 &&
    annotations.unexecutedEntries.length === 0 &&
    knownFailureErrors.length === 0;

  return {
    schemaVersion: 1,
    runId,
    status,
    completeTierA,
    completeTierB,
    completeTierInstall,
    partialSelection,
    partialReasons: partialSelection ? selection.partialReasons : [],
    setupFailure,
    cancelled: isCancelled,
    selection,
    commit: {
      sha: String(commit?.sha ?? ''),
      short: String(commit?.short ?? commit?.sha ?? ''),
      dirty: Boolean(commit?.dirty),
      branch: String(commit?.branch ?? ''),
    },
    environment: {
      ci: Boolean(environment?.ci),
      node: String(environment?.node ?? ''),
      platform: String(environment?.platform ?? ''),
      playwright: String(environment?.playwright ?? ''),
      tier: String(environment?.tier ?? selection.tier),
    },
    startedAt,
    finishedAt,
    durationMs: Number(durationMs ?? 0),
    resultStatus,
    counts,
    firstAttemptResults: {
      passed: counts.firstAttemptPassed,
      failed: counts.firstAttemptFailed,
      skipped: annotatedTests.filter((test) => test.firstAttemptStatus === 'skipped').length,
    },
    failureClasses,
    globalErrors: safeGlobalErrors,
    tests: annotatedTests,
    knownFailures: annotations.knownFailures,
    newFailures: annotations.newFailures,
    staleKnownFailures: annotations.staleEntries,
    unexecutedKnownFailures: annotations.unexecutedEntries,
    knownFailureErrors,
    artifacts: {
      junit: 'junit.xml',
      summary: 'run-summary.json',
      uploadManifest: 'upload-manifest.json',
      failureDirectories: [],
      ...artifacts,
    },
  };
}

function escapeXml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&apos;');
}

function property(name, value) {
  return `      <property name="${escapeXml(name)}" value="${escapeXml(value)}"/>`;
}

function renderFailureClasses(failureClasses) {
  const entries = Object.entries(failureClasses).sort(([left], [right]) => left.localeCompare(right));

  return entries.map(([name, count]) => `${name}:${count}`).join(',');
}

function runLevelMessage(summary) {
  if (summary.staleKnownFailures?.length > 0) {
    return summary.staleKnownFailures.map((entry) => entry.removal).join('; ');
  }

  if (summary.knownFailureErrors?.length > 0) {
    return `known failure registry is invalid: ${summary.knownFailureErrors.join('; ')}`;
  }

  switch (summary.status) {
    case 'setup-failed':
      return (
        summary.globalErrors.map((error) => error.message).filter(Boolean).join('; ') ||
        'setup failed before any test produced evidence'
      );
    case 'partial':
      return summary.partialReasons.join('; ') || `selection is not a complete Tier ${summary.selection.tier} run`;
    case 'cancelled':
      return 'run was cancelled before completion';
    case 'failed':
    case 'timedout':
      return 'run did not complete successfully outside an individual test result';
    default:
      return `run finished with status ${summary.status}`;
  }
}

/**
 * @param {object} summary
 * @returns {string}
 */
export function renderJUnit(summary) {
  const flaky = summary.tests.filter(isFlakyTest).length;
  const failures = summary.counts.failed + flaky;
  const perTestProblems = failures + summary.counts.interrupted + summary.counts.notRun;
  const runLevelProblem =
    summary.status === 'setup-failed' ||
    summary.status === 'partial' ||
    (summary.status !== 'passed' && perTestProblems === 0);
  const errors = summary.counts.interrupted + summary.counts.notRun + (runLevelProblem ? 1 : 0);
  const tests = summary.tests.length + (runLevelProblem ? 1 : 0);
  const time = (summary.durationMs / 1000).toFixed(3);
  const lines = [
    '<?xml version="1.0" encoding="UTF-8"?>',
    `<testsuites id="syspass-ui-smoke" name="sysPass UI smoke" tests="${tests}" failures="${failures}" skipped="${summary.counts.skipped}" errors="${errors}" time="${time}">`,
    `  <testsuite name="tier-${escapeXml(summary.selection.tier)}" timestamp="${escapeXml(summary.startedAt)}" hostname="chromium" tests="${tests}" failures="${failures}" skipped="${summary.counts.skipped}" errors="${errors}" time="${time}">`,
    '    <properties>',
    property('sp-ui.runId', summary.runId),
    property('sp-ui.status', summary.status),
    property('sp-ui.completeTierA', String(summary.completeTierA)),
    property('sp-ui.completeTierB', String(summary.completeTierB ?? false)),
    property('sp-ui.completeTierInstall', String(summary.completeTierInstall ?? false)),
    property('sp-ui.partialSelection', String(summary.partialSelection)),
    property('sp-ui.setupFailure', String(summary.setupFailure)),
    property('sp-ui.cancelled', String(summary.cancelled)),
    property('sp-ui.tier', summary.selection.tier),
    property('sp-ui.selectedSpecs', summary.selection.selectedSpecs.join(',')),
    property('sp-ui.commit', summary.commit.sha),
    property('sp-ui.counts.selected', summary.counts.selected),
    property('sp-ui.counts.attempted', summary.counts.attempted),
    property('sp-ui.counts.completed', summary.counts.completed),
    property('sp-ui.counts.skipped', summary.counts.skipped),
    property('sp-ui.counts.failed', summary.counts.failed),
    property('sp-ui.counts.timedOut', summary.counts.timedOut),
    property('sp-ui.counts.interrupted', summary.counts.interrupted),
    property('sp-ui.counts.notRun', summary.counts.notRun),
    property('sp-ui.counts.retries', summary.counts.retries),
    property('sp-ui.counts.firstAttemptPassed', summary.counts.firstAttemptPassed),
    property('sp-ui.counts.firstAttemptFailed', summary.counts.firstAttemptFailed),
    property('sp-ui.counts.flaky', summary.counts.flaky),
    property('sp-ui.counts.knownFailed', summary.counts.knownFailed ?? 0),
    property('sp-ui.counts.newFailed', summary.counts.newFailed ?? 0),
    property('sp-ui.knownFailures', summary.knownFailures?.length ?? 0),
    property('sp-ui.newFailures', summary.newFailures?.length ?? 0),
    property('sp-ui.staleKnownFailures', summary.staleKnownFailures?.length ?? 0),
    property('sp-ui.unexecutedKnownFailures', summary.unexecutedKnownFailures?.length ?? 0),
    property('sp-ui.failureClasses', renderFailureClasses(summary.failureClasses)),
    '    </properties>',
  ];

  for (const test of summary.tests) {
    const className = [test.file, test.project].filter(Boolean).join(' :: ');
    const attributes = [
      `name="${escapeXml(test.title)}"`,
      `classname="${escapeXml(className)}"`,
      `time="${(test.durationMs / 1000).toFixed(3)}"`,
      `status="${escapeXml(test.finalStatus)}"`,
      `retries="${test.retries}"`,
      `firstAttempt="${escapeXml(test.firstAttemptStatus)}"`,
      `attempts="${test.attempts.length}"`,
      `knownFailure="${test.knownFailure ? 'true' : 'false'}"`,
    ].join(' ');

    if (test.skipped) {
      lines.push(`    <testcase ${attributes}>`);
      lines.push('      <skipped/>');
      lines.push('    </testcase>');
      continue;
    }

    if (test.finalStatus === 'failed' || test.finalStatus === 'timedOut') {
      const lastFailure = [...test.attempts].reverse().find((attempt) => attempt.failureClass);
      const failureClass = lastFailure?.failureClass ?? test.failureClass ?? 'test-error';
      lines.push(`    <testcase ${attributes}>`);
      lines.push(
        `      <failure type="${escapeXml(failureClass)}" message="${escapeXml(lastFailure?.errorMessage ?? '')}">${escapeXml(lastFailure?.errorStack ?? '')}</failure>`,
      );
      lines.push('    </testcase>');
      continue;
    }

    if (test.finalStatus === 'interrupted') {
      const lastAttempt = test.attempts.at(-1);
      lines.push(`    <testcase ${attributes}>`);
      lines.push(
        `      <error type="interrupted" message="${escapeXml(lastAttempt?.errorMessage ?? 'test run was interrupted')}">${escapeXml(lastAttempt?.errorStack ?? '')}</error>`,
      );
      lines.push('    </testcase>');
      continue;
    }

    if (isFlakyTest(test)) {
      const firstFailure = test.attempts[0];
      lines.push(`    <testcase ${attributes}>`);
      lines.push(
        `      <failure type="flaky" message="first attempt failed: ${escapeXml(firstFailure?.errorMessage ?? '')}">${escapeXml(firstFailure?.errorStack ?? '')}</failure>`,
      );
      lines.push('    </testcase>');
      continue;
    }

    if (test.attempts.length === 0) {
      lines.push(`    <testcase ${attributes}>`);
      lines.push('      <error type="not-run" message="test was selected but did not run"/>');
      lines.push('    </testcase>');
      continue;
    }

    if (test.finalStatus !== 'passed') {
      lines.push(`    <testcase ${attributes}>`);
      lines.push(
        `      <error type="${escapeXml(test.finalStatus)}" message="test finished with status ${escapeXml(test.finalStatus)}"/>`,
      );
      lines.push('    </testcase>');
      continue;
    }

    lines.push(`    <testcase ${attributes} />`);
  }

  if (runLevelProblem) {
    const message = runLevelMessage(summary);
    const type =
      summary.staleKnownFailures?.length > 0
        ? 'known-failure-stale'
        : summary.knownFailureErrors?.length > 0
          ? 'known-failure-registry'
          : summary.status;
    lines.push(
      `    <testcase name="run status: ${escapeXml(summary.status)}" classname="sysPass UI smoke :: run" time="0.000" status="${escapeXml(summary.status)}">`,
    );
    lines.push(
      `      <error type="${escapeXml(type)}" message="${escapeXml(message)}">${escapeXml(message)}</error>`,
    );
    lines.push('    </testcase>');
  }

  lines.push('  </testsuite>');
  lines.push('</testsuites>');
  lines.push('');

  return lines.join('\n');
}
