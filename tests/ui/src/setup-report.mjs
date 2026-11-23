import { execFileSync } from 'node:child_process';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import {
  buildRunSummary,
  renderJUnit,
  resolveSelection,
} from './summary.mjs';
import { buildUploadManifest, listArtifacts } from './uploads.mjs';

function safeGit(args) {
  try {
    return execFileSync('git', args, { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] }).trim();
  } catch {
    return '';
  }
}

function collectCommit() {
  const sha = safeGit(['rev-parse', 'HEAD']);

  return {
    sha,
    short: safeGit(['rev-parse', '--short=9', 'HEAD']) || sha.slice(0, 9),
    dirty: safeGit(['status', '--porcelain=v1']).length > 0,
    branch: safeGit(['rev-parse', '--abbrev-ref', 'HEAD']),
  };
}

/**
 * Write a sanitized report when the runner fails before Playwright can emit
 * its own summary. It never invents test attempts and always marks the run as
 * setup-failed so it cannot look like a complete Tier A success.
 *
 * @param {object} options
 * @param {string} options.outputDir
 * @param {string} options.runId
 * @param {string} options.message
 * @param {string} [options.startedAt]
 * @param {string} [options.finishedAt]
 * @returns {object}
 */
export function writeSetupFailureReport({
  outputDir,
  runId,
  message,
  startedAt = new Date().toISOString(),
  finishedAt = new Date().toISOString(),
}) {
  const selection = resolveSelection({
    tier: process.env.SP_UI_TIER ?? 'A',
    specs: process.env.SP_UI_SPECS ?? '',
    grep: false,
  });
  const summary = buildRunSummary({
    runId,
    selection,
    commit: collectCommit(),
    environment: {
      ci: process.env.CI === 'true',
      node: process.version,
      platform: process.platform,
      playwright: '',
      tier: selection.tier,
    },
    tests: [],
    startedAt,
    finishedAt,
    durationMs: Math.max(0, Date.parse(finishedAt) - Date.parse(startedAt)),
    resultStatus: 'failed',
    cancelled: false,
    setupFailure: true,
    globalErrors: [{ message }],
    artifacts: {},
  });

  mkdirSync(outputDir, { recursive: true });
  writeFileSync(path.join(outputDir, 'run-summary.json'), `${JSON.stringify(summary, null, 2)}\n`);
  writeFileSync(path.join(outputDir, 'junit.xml'), renderJUnit(summary));

  const files = listArtifacts(outputDir)
    .filter((artifact) => artifact !== 'upload-manifest.json')
    .map((artifact) => ({
      path: artifact,
      contents: readFileSync(path.join(outputDir, artifact)),
    }));
  const manifest = buildUploadManifest(files);

  writeFileSync(path.join(outputDir, 'upload-manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`);

  return summary;
}

function parseArguments(argv) {
  const options = {};

  for (let index = 0; index < argv.length; index += 1) {
    const argument = argv[index];

    switch (argument) {
      case '--output':
        options.outputDir = argv[(index += 1)];
        break;
      case '--run-id':
        options.runId = argv[(index += 1)];
        break;
      case '--started-at':
        options.startedAt = argv[(index += 1)];
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

    if (!options.outputDir || !options.runId) {
      throw new Error('Usage: setup-report.mjs --output <dir> --run-id <id>');
    }

    writeSetupFailureReport({
      ...options,
      message: process.env.SP_UI_SETUP_ERROR ?? 'UI smoke run failed before test evidence was produced',
    });
  } catch (error) {
    process.stderr.write(`${error instanceof Error ? error.message : String(error)}\n`);
    process.exit(1);
  }
}