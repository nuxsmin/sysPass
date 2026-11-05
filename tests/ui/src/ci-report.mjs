import { appendFileSync, existsSync, readFileSync, readdirSync, statSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const TIER_A_BUDGET_MS = 300_000;
export const TIER_A_SETUP_BUDGET_MS = 1_800_000;

/**
 * Find every retained run summary below a directory.
 *
 * @param {string} root
 * @returns {string[]}
 */
export function findRunSummaries(root) {
  if (!root || !existsSync(root)) {
    return [];
  }

  const summaries = [];

  const walk = (directory) => {
    for (const name of readdirSync(directory).sort()) {
      const target = path.join(directory, name);

      if (statSync(target).isDirectory()) {
        walk(target);
      } else if (name === 'run-summary.json') {
        summaries.push(target);
      }
    }
  };

  walk(root);

  return summaries.sort();
}

/**
 * Decide whether a retained summary is usable as a complete Tier A
 * qualification observation. A skipped, partial, setup-failed, failed or
 * over-budget run is never eligible.
 *
 * @param {object | null | undefined} summary
 * @param {{budgetMs?: number}} [options]
 * @returns {{ok: boolean, status: string, completeTierA: boolean, durationMs: number, budgetMs: number, withinBudget: boolean, reasons: string[]}}
 */
export function evaluateRunSummary(summary, { budgetMs = TIER_A_BUDGET_MS } = {}) {
  const reasons = [];
  const status = String(summary?.status ?? 'missing');
  const completeTierA = summary?.completeTierA === true;
  const durationMs = Number(summary?.durationMs);
  const hasDuration = Number.isFinite(durationMs) && durationMs >= 0;
  const withinBudget = hasDuration && durationMs <= budgetMs;

  if (!summary || typeof summary !== 'object') {
    reasons.push('run summary is missing or invalid');
  } else {
    if (status !== 'passed') {
      reasons.push(`run status is "${status}", not "passed"`);
    }

    if (!completeTierA) {
      reasons.push('run is not a complete Tier A selection');
    }

    if (!hasDuration) {
      reasons.push('run duration is missing');
    } else if (!withinBudget) {
      reasons.push(
        `Tier A duration ${(durationMs / 1000).toFixed(1)}s exceeds the ${(budgetMs / 1000).toFixed(0)}s budget`,
      );
    }
  }

  return {
    ok: reasons.length === 0,
    status,
    completeTierA,
    durationMs: hasDuration ? durationMs : 0,
    budgetMs,
    withinBudget,
    reasons,
  };
}

/**
 * @param {object | null} summary
 * @param {ReturnType<typeof evaluateRunSummary>} evaluation
 * @param {{setupDurationMs?: number, setupBudgetMs?: number}} [options]
 * @returns {string}
 */
export function renderMarkdown(summary, evaluation, { setupDurationMs, setupBudgetMs } = {}) {
  const lines = ['## UI smoke Tier A observation', ''];

  if (!summary) {
    lines.push('No retained run summary was found. This run is **not** a complete Tier A observation.');
    return `${lines.join('\n')}\n`;
  }

  const commit = summary.commit ?? {};
  const counts = summary.counts ?? {};
  const specSeconds = (evaluation.durationMs / 1000).toFixed(1);
  const budgetSeconds = (evaluation.budgetMs / 1000).toFixed(0);

  lines.push(
    '| Field | Value |',
    '| --- | --- |',
    `| Run | \`${summary.runId ?? ''}\` |`,
    `| Commit | \`${commit.sha ?? ''}\` (${commit.branch ?? ''}${commit.dirty ? ', dirty' : ''}) |`,
    `| Status | \`${evaluation.status}\` |`,
    `| Complete Tier A | ${evaluation.completeTierA ? 'yes' : 'no'} |`,
    `| Tests selected/completed/failed/skipped | ${counts.selected ?? 0}/${counts.completed ?? 0}/${counts.failed ?? 0}/${counts.skipped ?? 0} |`,
    `| First-attempt failed | ${summary.firstAttemptResults?.failed ?? 0} |`,
    `| Retries | ${counts.retries ?? 0} |`,
    `| Tier A spec duration | ${specSeconds}s / ${budgetSeconds}s budget (${evaluation.withinBudget ? 'within' : 'exceeded'}) |`,
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

  lines.push('', evaluation.ok ? 'This run may be counted as one Tier A observation.' : 'This run must not be counted as a Tier A observation.');

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

export function run({
  summariesDir,
  budgetMs = TIER_A_BUDGET_MS,
  setupBudgetMs = TIER_A_SETUP_BUDGET_MS,
  setupStartedMs,
  setupFinishedMs,
  stepSummary = process.env.GITHUB_STEP_SUMMARY,
}) {
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

  const evaluation = evaluateRunSummary(summary, { budgetMs });
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

  const markdown = renderMarkdown(summary, evaluation, { setupDurationMs, setupBudgetMs });

  process.stdout.write(markdown);

  if (stepSummary) {
    appendFileSync(stepSummary, markdown);
  }

  if (!evaluation.ok) {
    process.stderr.write(`::error::UI smoke is not a complete Tier A observation: ${evaluation.reasons.join('; ')}\n`);
  }

  return evaluation;
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  try {
    const options = parseArguments(process.argv.slice(2));

    if (!options.summariesDir) {
      throw new Error('Usage: ci-report.mjs --summaries <dir> [--budget-seconds 300] [--setup-budget-seconds 1800] [--setup-started-ms <ms>] [--setup-finished-ms <ms>]');
    }

    const evaluation = run(options);

    process.exit(evaluation.ok ? 0 : 1);
  } catch (error) {
    process.stderr.write(`${error instanceof Error ? error.message : String(error)}\n`);
    process.exit(1);
  }
}
