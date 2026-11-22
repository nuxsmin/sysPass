import { appendFileSync, existsSync, readFileSync, readdirSync, statSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { sanitizeText } from './sanitize.mjs';
import { normalizeTier, TIER_SPECS } from './tiers.mjs';

export const TIER_A_BUDGET_MS = 300_000;
export const TIER_A_SETUP_BUDGET_MS = 1_800_000;

class UsageError extends Error {}

const RECORDED_REGISTRY_FIELDS = [
  'knownFailures',
  'newFailures',
  'staleKnownFailures',
  'unexecutedKnownFailures',
  'knownFailureErrors',
];

/**
 * Find retained run summaries for one tier root. Discovery is deliberately
 * non-recursive: an accumulating root contains one directory per run, while a
 * per-run directory contains its own run-summary.json. Walking deeper could
 * make a Tier A report consume nested Tier B evidence.
 *
 * @param {string} root
 * @returns {string[]}
 */
export function findRunSummaries(root) {
  if (!root || !existsSync(root)) {
    return [];
  }

  const rootStat = statSync(root);

  if (!rootStat.isDirectory()) {
    return [];
  }

  const directSummary = path.join(root, 'run-summary.json');

  if (existsSync(directSummary) && statSync(directSummary).isFile()) {
    return [directSummary];
  }

  const summaries = [];

  for (const name of readdirSync(root).sort()) {
    const directory = path.join(root, name);

    if (!statSync(directory).isDirectory()) {
      continue;
    }

    const summary = path.join(directory, 'run-summary.json');

    if (existsSync(summary) && statSync(summary).isFile()) {
      summaries.push(summary);
    }
  }

  return summaries.sort();
}

/**
 * Read the known-failure evaluation recorded by the reporter. The summary is
 * the single authoritative evidence record: re-evaluating a stale registry at
 * report time could re-admit a failure the producer already rejected.
 *
 * @param {object | null | undefined} summary
 * @returns {object}
 */
function recordedRegistryEvidence(summary) {
  if (!summary || typeof summary !== 'object') {
    return {
      incompatible: false,
      errors: [],
      knownFailures: [],
      newFailures: [],
      staleKnownFailures: [],
      unexecutedKnownFailures: [],
    };
  }

  const missing = RECORDED_REGISTRY_FIELDS.filter((field) => !Array.isArray(summary[field]));

  if (missing.length > 0) {
    return {
      incompatible: true,
      errors: [],
      knownFailures: [],
      newFailures: [],
      staleKnownFailures: [],
      unexecutedKnownFailures: [],
    };
  }

  return {
    incompatible: false,
    errors: summary.knownFailureErrors,
    knownFailures: summary.knownFailures,
    newFailures: summary.newFailures,
    staleKnownFailures: summary.staleKnownFailures,
    unexecutedKnownFailures: summary.unexecutedKnownFailures,
  };
}

/**
 * Decide whether a retained summary is usable as a complete observation of the
 * requested tier. A skipped, partial, setup-failed, failed or over-budget run
 * is never eligible. An admitted deterministic known failure remains annotated
 * but never makes the run eligible or suppresses its exit status.
 *
 * Tier A, Tier B and the installer are evaluated independently: a Tier B or
 * installer summary can never be complete Tier A evidence, and a Tier A
 * summary can never qualify another selection.
 *
 * @param {object | null | undefined} summary
 * @param {{budgetMs?: number, tier?: string}} [options]
 * @returns {object}
 */
export function evaluateRunSummary(summary, { budgetMs = TIER_A_BUDGET_MS, tier = 'A' } = {}) {
  const reasons = [];
  const requestedTier = String(tier ?? 'A').trim().toUpperCase() || 'A';
  const knownTier = Object.hasOwn(TIER_SPECS, requestedTier);
  const status = String(summary?.status ?? 'missing');
  const completeTierA = summary?.completeTierA === true;
  const completeTierB = summary?.completeTierB === true;
  const completeTierInstall = summary?.completeTierInstall === true;
  const completeTierWiring = summary?.completeTierWiring === true;
  const completeTierWrite = summary?.completeTierWrite === true;
  const completeTier =
    requestedTier === 'A'
      ? completeTierA
      : requestedTier === 'B'
        ? completeTierB
        : requestedTier === 'INSTALL'
          ? completeTierInstall
          : requestedTier === 'WIRING'
            ? completeTierWiring
            : requestedTier === 'WRITE'
              ? completeTierWrite
              : false;
  const summaryTier = String(summary?.selection?.tier ?? '').toUpperCase();
  const durationMs = Number(summary?.durationMs);
  const hasDuration = Number.isFinite(durationMs) && durationMs >= 0;
  const withinBudget = hasDuration && durationMs <= budgetMs;
  const evidence = recordedRegistryEvidence(summary);
  const counts = summary?.counts ?? {};
  const failedCount = Number(counts.failed ?? 0);

  if (!knownTier) {
    reasons.push(`tier ${requestedTier} is not a known UI smoke tier`);
  }

  if (!summary || typeof summary !== 'object') {
    reasons.push('run summary is missing or invalid');
  } else {
    if (evidence.incompatible) {
      reasons.push(
        `run summary is incompatible: it does not record known-failure evaluation fields (${RECORDED_REGISTRY_FIELDS.join(', ')}); refusing to re-evaluate the registry`,
      );
    }

    if (evidence.errors.length > 0) {
      reasons.push(`known failure registry is invalid: ${evidence.errors.join('; ')}`);
    }

    if (evidence.staleKnownFailures.length > 0) {
      reasons.push(evidence.staleKnownFailures.map((entry) => entry.removal).join('; '));
    }

    if (evidence.newFailures.length > 0) {
      const failures = evidence.newFailures
        .slice(0, 5)
        .map(
          (failure) =>
            `${failure.spec ?? '<unknown spec>'}:${failure.route ?? '<no route>'}:${failure.failureKind}`,
        )
        .join(', ');
      reasons.push(
        `${evidence.newFailures.length} known-failure admission(s) missing or not matched for: ${failures}`,
      );
    }

    if (evidence.unexecutedKnownFailures.length > 0) {
      const entries = evidence.unexecutedKnownFailures
        .slice(0, 5)
        .map(
          (failure) =>
            `${failure.spec ?? '<unknown spec>'}:${failure.route ?? '<no route>'}:${failure.failureKind}`,
        )
        .join(', ');
      reasons.push(
        `${evidence.unexecutedKnownFailures.length} known-failure entr(ies) were not exercised by this selection and are not evidence of a fix: ${entries}`,
      );
    }

    if (status !== 'passed') {
      reasons.push(
        evidence.knownFailures.length > 0 && evidence.newFailures.length === 0
          ? `run failed with ${evidence.knownFailures.length} admitted known failure(s); known failures are annotated but never make the run eligible or suppress the non-zero exit`
          : `run status is "${status}", not "passed"`,
      );
    } else if (failedCount > 0) {
      reasons.push(
        `run summary records ${failedCount} failed attempt(s) while claiming status "passed"`,
      );
    }

    if (summaryTier !== '' && summaryTier !== requestedTier) {
      reasons.push(
        `run summary belongs to Tier ${summaryTier}, not the requested Tier ${requestedTier}`,
      );
    }

    if (!completeTier) {
      reasons.push(`run is not a complete Tier ${requestedTier} selection`);
    }

    if (!hasDuration) {
      reasons.push('run duration is missing');
    } else if (!withinBudget) {
      reasons.push(
        `Tier ${requestedTier} duration ${(durationMs / 1000).toFixed(1)}s exceeds the ${(budgetMs / 1000).toFixed(0)}s budget`,
      );
    }
  }

  return {
    ok: reasons.length === 0,
    tier: requestedTier,
    status,
    completeTierA,
    completeTierB,
    completeTierInstall,
    completeTierWiring,
    completeTierWrite,
    durationMs: hasDuration ? durationMs : 0,
    budgetMs,
    withinBudget,
    reasons,
    knownFailures: evidence.knownFailures,
    newFailures: evidence.newFailures,
    staleKnownFailures: evidence.staleKnownFailures,
    unexecutedKnownFailures: evidence.unexecutedKnownFailures,
    knownFailureErrors: evidence.errors,
  };
}

/**
 * @param {object | null} summary
 * @param {ReturnType<typeof evaluateRunSummary>} evaluation
 * @param {{setupDurationMs?: number, setupBudgetMs?: number, tier?: string}} [options]
 * @returns {string}
 */
export function renderMarkdown(summary, evaluation, { setupDurationMs, setupBudgetMs, tier = 'A' } = {}) {
  const tierLabel = String(tier ?? 'A').trim().toUpperCase() || 'A';
  const lines = [`## UI smoke Tier ${tierLabel} observation`, ''];

  if (!summary) {
    lines.push(
      `No retained run summary was found. This run is **not** a complete Tier ${tierLabel} observation.`,
    );
    return `${lines.join('\n')}\n`;
  }

  const commit = summary.commit ?? {};
  const counts = summary.counts ?? {};
  const specSeconds = (evaluation.durationMs / 1000).toFixed(1);
  const budgetSeconds = (evaluation.budgetMs / 1000).toFixed(0);
  const completeSelected =
    tierLabel === 'A'
      ? evaluation.completeTierA
      : tierLabel === 'B'
        ? evaluation.completeTierB
        : tierLabel === 'INSTALL'
          ? evaluation.completeTierInstall
          : tierLabel === 'WIRING'
            ? evaluation.completeTierWiring
            : tierLabel === 'WRITE'
              ? evaluation.completeTierWrite
              : false;

  lines.push(
    '| Field | Value |',
    '| --- | --- |',
    `| Run | \`${summary.runId ?? ''}\` |`,
    `| Commit | \`${commit.sha ?? ''}\` (${commit.branch ?? ''}${commit.dirty ? ', dirty' : ''}) |`,
    `| Status | \`${evaluation.status}\` |`,
    `| Complete Tier ${tierLabel} | ${completeSelected ? 'yes' : 'no'} |`,
    `| Tests selected/completed/failed/skipped | ${counts.selected ?? 0}/${counts.completed ?? 0}/${counts.failed ?? 0}/${counts.skipped ?? 0} |`,
    `| First-attempt failed | ${summary.firstAttemptResults?.failed ?? 0} |`,
    `| Retries | ${counts.retries ?? 0} |`,
    `| Tier ${tierLabel} spec duration | ${specSeconds}s / ${budgetSeconds}s budget (${evaluation.withinBudget ? 'within' : 'exceeded'}) |`,
  );

  if (typeof setupDurationMs === 'number' && Number.isFinite(setupDurationMs)) {
    const setupSeconds = (setupDurationMs / 1000).toFixed(1);

    if (typeof setupBudgetMs === 'number' && Number.isFinite(setupBudgetMs)) {
      const setupBudgetSeconds = (setupBudgetMs / 1000).toFixed(0);
      const setupWithinBudget = setupDurationMs <= setupBudgetMs;

      lines.push(
        `| Setup duration (before specs) | ${setupSeconds}s / ${setupBudgetSeconds}s budget (${setupWithinBudget ? 'within' : 'exceeded'}) |`,
      );
    } else {
      lines.push(`| Setup duration (before specs) | ${setupSeconds}s |`);
    }
  } else if (typeof setupBudgetMs === 'number' && Number.isFinite(setupBudgetMs)) {
    lines.push('| Setup duration (before specs) | unavailable (invalid setup stamps) |');
  }

  if (evaluation.reasons.length > 0) {
    lines.push('', 'Qualification blockers:', '');
    for (const reason of evaluation.reasons) {
      lines.push(`- ${reason}`);
    }
  }

  if ((evaluation.knownFailures ?? []).length > 0) {
    lines.push('', 'Admitted known failures (annotated, never suppressed):', '');

    for (const failure of evaluation.knownFailures ?? []) {
      const spec = sanitizeText(failure.spec, { maxLength: 200 });
      const route = sanitizeText(failure.route, { maxLength: 200 });
      const failureKind = sanitizeText(failure.failureKind, { maxLength: 100 });
      const reason = sanitizeText(failure.reason, { maxLength: 2000 });
      const defect = sanitizeText(failure.defect, { maxLength: 300 });
      const who = sanitizeText(failure.admission?.who, { maxLength: 200 });
      const date = sanitizeText(failure.admission?.date, { maxLength: 40 });
      const ref = sanitizeText(failure.admission?.ref, { maxLength: 300 });

      lines.push(
        `- \`${spec}\` \`${route}\` \`${failureKind}\` — ${reason} (${defect}; admitted by ${who} on ${date}, ref ${ref})`,
      );
    }
  }

  if ((evaluation.unexecutedKnownFailures ?? []).length > 0) {
    lines.push('', 'Known failure entries not exercised by this selection (not evidence of a fix):', '');

    for (const failure of evaluation.unexecutedKnownFailures ?? []) {
      const spec = sanitizeText(failure.spec, { maxLength: 200 });
      const route = sanitizeText(failure.route, { maxLength: 200 });
      const failureKind = sanitizeText(failure.failureKind, { maxLength: 100 });
      const message = sanitizeText(failure.message, { maxLength: 2000 });

      lines.push(`- \`${spec}\` \`${route}\` \`${failureKind}\` — ${message}`);
    }
  }

  if ((evaluation.knownFailures ?? []).length > 0) {
    lines.push(
      '',
      `Admitted known failures remain visible in the counts and failure evidence. This run cannot be counted as a Tier ${tierLabel} observation and its failure is not suppressed.`,
    );
  }

  lines.push(
    '',
    evaluation.ok
      ? `This run may be counted as one Tier ${tierLabel} observation.`
      : `This run must not be counted as a Tier ${tierLabel} observation.`,
  );

  return `${lines.join('\n')}\n`;
}

/**
 * @param {unknown} value
 * @param {string} label
 * @param {string[]} reasons
 * @returns {number | undefined}
 */
function parseSetupStamp(value, label, reasons) {
  if (value === undefined || value === null) {
    reasons.push(`setup ${label} stamp is missing`);
    return undefined;
  }

  if (typeof value === 'string' && value.trim() === '') {
    reasons.push(`setup ${label} stamp is missing`);
    return undefined;
  }

  const numeric = typeof value === 'number' ? value : Number(value);

  if (!Number.isFinite(numeric)) {
    reasons.push(`setup ${label} stamp is not numeric: "${String(value)}"`);
    return undefined;
  }

  if (numeric < 0) {
    reasons.push(`setup ${label} stamp is negative: ${numeric}`);
    return undefined;
  }

  if (numeric === 0) {
    reasons.push(`setup ${label} stamp is not a valid timestamp: 0`);
    return undefined;
  }

  return numeric;
}

/**
 * Resolve the measured pre-spec setup window. A missing, non-numeric,
 * negative or reversed stamp is never a valid window: qualification must fail
 * closed instead of silently measuring zero elapsed time.
 *
 * @param {{setupStartedMs?: unknown, setupFinishedMs?: unknown}} [options]
 * @returns {{durationMs: number | undefined, reasons: string[]}}
 */
export function resolveSetupWindow({ setupStartedMs, setupFinishedMs } = {}) {
  const reasons = [];
  const started = parseSetupStamp(setupStartedMs, 'start', reasons);
  const finished = parseSetupStamp(setupFinishedMs, 'finish', reasons);

  if (reasons.length > 0) {
    return { durationMs: undefined, reasons };
  }

  if (finished <= started) {
    return {
      durationMs: undefined,
      reasons: [`setup finish stamp (${finished}) is not after the setup start stamp (${started})`],
    };
  }

  return { durationMs: finished - started, reasons: [] };
}

/**
 * Resolve the measured pre-spec setup duration. Both bounds come from the
 * workflow so evaluation time (after the spec run) is never included.
 *
 * @param {{setupStartedMs?: unknown, setupFinishedMs?: unknown}} [options]
 * @returns {number | undefined}
 */
export function resolveSetupDurationMs(options = {}) {
  return resolveSetupWindow(options).durationMs;
}

function parseArguments(argv) {
  const options = {};

  for (let index = 0; index < argv.length; index += 1) {
    const argument = argv[index];

    switch (argument) {
      case '--summaries':
        options.summariesDir = argv[(index += 1)];
        break;
      case '--budget-seconds':
        options.budgetMs = Number(argv[(index += 1)]) * 1000;
        break;
      case '--registry':
        options.registryPath = argv[(index += 1)];
        break;
      case '--tier':
        try {
          options.tier = normalizeTier(argv[(index += 1)]);
        } catch (error) {
          throw new UsageError(error instanceof Error ? error.message : String(error));
        }
        break;
      case '--setup-budget-seconds':
        options.setupBudgetMs = Number(argv[(index += 1)]) * 1000;
        break;
      case '--setup-started-ms':
        options.setupStartedMs = argv[(index += 1)];
        break;
      case '--setup-finished-ms':
        options.setupFinishedMs = argv[(index += 1)];
        break;
      default:
        throw new Error(`Unknown argument: ${argument}`);
    }
  }

  return options;
}

/**
 * Escape a value for a GitHub Actions workflow command annotation.
 *
 * @param {unknown} value
 * @returns {string}
 */
function annotationValue(value) {
  return sanitizeText(value, { maxLength: 500 })
    .replaceAll('%', '%25')
    .replaceAll('\r', '%0D')
    .replaceAll('\n', '%0A')
    .replaceAll(':', '%3A')
    .replaceAll(',', '%2C');
}

/**
 * Emit one warning annotation per failed detector problem for a Tier B run.
 * It never changes the failed status, counts or exit code; Tier B is simply
 * evaluated in its own selection so it cannot hide a Tier A result.
 *
 * @param {object | null} runSummary retained run summary (with its tests)
 * @param {string} tier
 */
function annotateTierBFailures(runSummary, tier) {
  if (tier !== 'B' || !runSummary || !Array.isArray(runSummary.tests)) {
    return;
  }

  for (const test of runSummary.tests) {
    for (const attempt of test.attempts ?? []) {
      if (attempt.status === 'passed' || attempt.status === 'skipped') {
        continue;
      }

      const problems =
        Array.isArray(attempt.failureProblems) && attempt.failureProblems.length > 0
          ? attempt.failureProblems
          : [{ route: attempt.route ?? null, failureKind: attempt.failureClass ?? 'test-error' }];

      for (const problem of problems) {
        const file = annotationValue(test.file ?? '');
        const route = annotationValue(problem.route ?? '<no route>');
        const failureKind = annotationValue(problem.failureKind ?? 'test-error');
        const spec = annotationValue(test.file ?? '<unknown spec>');

        process.stderr.write(
          `::warning file=${file},title=Tier B ${route} ${failureKind}::${spec}: ${route} failed as ${failureKind}\n`,
        );
      }
    }
  }
}

export function run({
  summariesDir,
  budgetMs = TIER_A_BUDGET_MS,
  setupBudgetMs = TIER_A_SETUP_BUDGET_MS,
  setupStartedMs,
  setupFinishedMs,
  tier = 'A',
  stepSummary = process.env.GITHUB_STEP_SUMMARY,
}) {
  const tierLabel = normalizeTier(tier);
  const summaryFiles = findRunSummaries(summariesDir);
  let summary = null;
  const reasons = [];

  if (summaryFiles.length !== 1) {
    reasons.push(
      summaryFiles.length === 0
        ? `no run-summary.json found below ${summariesDir}`
        : `expected one run-summary.json, found ${summaryFiles.length}`,
    );
  } else {
    try {
      summary = JSON.parse(readFileSync(summaryFiles[0], 'utf8'));
    } catch {
      reasons.push(`retained run summary is not valid JSON: ${summaryFiles[0]}`);
    }
  }

  const evaluation = evaluateRunSummary(summary, { budgetMs, tier: tierLabel });
  evaluation.reasons.unshift(...reasons);

  const setupWindow = resolveSetupWindow({ setupStartedMs, setupFinishedMs });
  const setupDurationMs = setupWindow.durationMs;
  const setupWithinBudget =
    setupWindow.reasons.length === 0 && setupDurationMs <= setupBudgetMs;

  if (setupWindow.reasons.length > 0) {
    evaluation.reasons.push(...setupWindow.reasons);
  } else if (!setupWithinBudget) {
    evaluation.reasons.push(
      `setup duration ${(setupDurationMs / 1000).toFixed(3)}s exceeds the ${(setupBudgetMs / 1000).toFixed(0)}s setup budget`,
    );
  }

  evaluation.ok = evaluation.reasons.length === 0;
  evaluation.setupDurationMs = setupDurationMs;
  evaluation.setupBudgetMs = setupBudgetMs;
  evaluation.setupWithinBudget = setupWithinBudget;

  const markdown = renderMarkdown(summary, evaluation, {
    setupDurationMs,
    setupBudgetMs,
    tier: tierLabel,
  });

  process.stdout.write(markdown);

  if (stepSummary) {
    appendFileSync(stepSummary, markdown);
  }

  if (!evaluation.ok) {
    annotateTierBFailures(summary, tierLabel);

    process.stderr.write(
      `::error::UI smoke is not a complete Tier ${tierLabel} observation: ${evaluation.reasons.join('; ')}\n`,
    );
  }

  return evaluation;
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  try {
    const options = parseArguments(process.argv.slice(2));

    if (!options.summariesDir) {
      throw new UsageError(
        'Usage: ci-report.mjs --summaries <tier-root|run-dir> [--tier A|B|INSTALL|WIRING|WRITE] [--budget-seconds 300] [--setup-budget-seconds 1800] [--setup-started-ms <ms>] [--setup-finished-ms <ms>]',
      );
    }

    const evaluation = run(options);

    process.exit(evaluation.ok ? 0 : 1);
  } catch (error) {
    process.stderr.write(`${error instanceof Error ? error.message : String(error)}\n`);
    process.exit(error instanceof UsageError ? 2 : 1);
  }
}
