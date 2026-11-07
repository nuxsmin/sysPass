import { fileURLToPath } from 'node:url';

import { normalizeTier } from './tiers.mjs';

export const INSTALL_TIER = 'INSTALL';

/**
 * The installer switch is explicit. Only the literal value `1` enables the
 * install phase; every other truthy-looking string is a usage error instead of
 * accidentally starting a stateful installation.
 *
 * @param {string | null | undefined} value
 * @returns {boolean}
 */
export function normalizeInstallFlag(value) {
  const normalized = String(value ?? '');

  if (normalized === '' || normalized === '0') {
    return false;
  }

  if (normalized === '1') {
    return true;
  }

  throw new Error(`SP_UI_INSTALL must be 0 or 1; received '${String(value)}'`);
}

/**
 * Enforce the installer retry rule at the boundary that actually starts
 * Playwright. A retry would run the browser wizard again against an already
 * installed instance, so the runner must recreate the whole instance instead.
 *
 * @param {string} tier
 * @param {number} retries
 * @returns {void}
 */
export function assertInstallerRetries(tier, retries) {
  if (normalizeTier(tier) === INSTALL_TIER && Number(retries) !== 0) {
    throw new Error('installer selection requires retries: 0; recreate the wizard instance instead');
  }
}

/**
 * Resolve the retained-evidence roots for one invocation.
 *
 * A seeded run writes to its tier root and the installer to its own sibling
 * root. For an installer-only selection, `SP_UI_SUMMARY_DIR` is the explicit
 * override for the install root; a combined run keeps the two overrides
 * independent.
 *
 * @param {object} [options]
 * @param {string} [options.tier]
 * @param {string} [options.summaryDir]
 * @param {string} [options.installSummaryDir]
 * @param {string} [options.defaultSummaryRoot]
 * @param {string} [options.defaultInstallSummaryRoot]
 * @returns {{summaryRoot: string, installSummaryRoot: string}}
 */
export function resolveSummaryRoots({
  tier = 'A',
  summaryDir = '',
  installSummaryDir = '',
  defaultSummaryRoot = '',
  defaultInstallSummaryRoot = '',
} = {}) {
  const normalizedTier = normalizeTier(tier);
  const seededRoot = summaryDir || defaultSummaryRoot;
  const installRoot =
    normalizedTier === INSTALL_TIER
      ? summaryDir || installSummaryDir || defaultInstallSummaryRoot
      : installSummaryDir || defaultInstallSummaryRoot;

  return {
    summaryRoot: normalizedTier === INSTALL_TIER ? installRoot : seededRoot,
    installSummaryRoot: installRoot,
  };
}

/**
 * Resolve the ordered phases for one runner invocation.
 *
 * A combined selection (`SP_UI_TIER=A|B` plus `SP_UI_INSTALL=1`) always runs
 * the seeded specs before the installer. The installer is never selected by
 * filename order and always keeps zero retries: a rerun must recreate the
 * whole wizard instance instead of retrying an installed one.
 *
 * @param {object} [options]
 * @param {string} [options.tier]
 * @param {string} [options.install]
 * @param {string} [options.diagnosticRetry]
 * @returns {{
 *   tier: string,
 *   seededTier: string | null,
 *   installSelected: boolean,
 *   phases: string[],
 *   retries: {seeded: number, install: number},
 * }}
 */
export function resolveRunPlan({ tier = 'A', install = '', diagnosticRetry = '0' } = {}) {
  const normalizedTier = normalizeTier(tier);
  const installFlagRequested = normalizeInstallFlag(install);
  const installRequested = normalizedTier === INSTALL_TIER || installFlagRequested;
  const diagnosticRetryRequested = String(diagnosticRetry ?? '0') === '1';

  if (installRequested && diagnosticRetryRequested) {
    throw new Error('installer selection requires retries: 0; diagnostic retry is not allowed');
  }

  const seededTier = normalizedTier === INSTALL_TIER ? null : normalizedTier;
  const phases = [];

  if (seededTier !== null) {
    phases.push('seeded');
  }

  if (installRequested) {
    phases.push('install');
  }

  return {
    tier: normalizedTier,
    seededTier,
    installSelected: installRequested,
    phases,
    retries: {
      seeded: diagnosticRetryRequested ? 1 : 0,
      install: 0,
    },
  };
}

function parseArguments(argv) {
  const options = {};

  for (let index = 0; index < argv.length; index += 1) {
    const argument = argv[index];

    switch (argument) {
      case '--tier':
        options.tier = argv[(index += 1)];
        break;
      case '--install':
        options.install = argv[(index += 1)];
        break;
      case '--diagnostic-retry':
        options.diagnosticRetry = argv[(index += 1)];
        break;
      case '--summary-dir':
        options.summaryDir = argv[(index += 1)];
        break;
      case '--install-summary-dir':
        options.installSummaryDir = argv[(index += 1)];
        break;
      case '--default-summary-root':
        options.defaultSummaryRoot = argv[(index += 1)];
        break;
      case '--default-install-summary-root':
        options.defaultInstallSummaryRoot = argv[(index += 1)];
        break;
      default:
        throw new Error(`Unknown argument: ${argument}`);
    }
  }

  return options;
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  try {
    const options = parseArguments(process.argv.slice(2));
    const plan = resolveRunPlan(options);

    process.stdout.write(
      `${JSON.stringify({ ...plan, ...resolveSummaryRoots(options) })}\n`,
    );
  } catch (error) {
    process.stderr.write(`${error instanceof Error ? error.message : String(error)}\n`);
    process.exit(1);
  }
}
