import { execFileSync } from 'node:child_process';
import { createRequire } from 'node:module';
import { mkdirSync, readFileSync, readdirSync, rmSync, statSync, writeFileSync } from 'node:fs';
import path from 'node:path';

import {
  loadKnownFailureRegistry,
  REGISTRY_FILENAME,
  REGISTRY_SCHEMA_VERSION,
} from './known-failures.mjs';
import { buildUploadManifest, listArtifacts } from './uploads.mjs';
import {
  buildRunSummary,
  renderJUnit,
  resolveSelection,
  summarizeTest,
} from './summary.mjs';

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

function relativeTestFile(config, file) {
  if (!file) {
    return '';
  }

  return path.relative(config.rootDir, file).replaceAll('\\', '/');
}

function hasGrep(config) {
  const values = Array.isArray(config.grep) ? config.grep : [config.grep];

  return values.filter(Boolean).some((value) => {
    if (value instanceof RegExp) {
      return value.source !== '' && value.source !== '(?:)' && value.source !== '.*';
    }

    return false;
  });
}

function playwrightVersion() {
  try {
    const require = createRequire(import.meta.url);

    return require('@playwright/test/package.json').version;
  } catch {
    return '';
  }
}

function availableSpecs(suite) {
  return [
    ...new Set(
      suite
        .allTests()
        .map((test) => path.basename(test.location?.file ?? ''))
        .filter(Boolean),
    ),
  ];
}

function removeForbiddenAttachments(root, relative = '') {
  const directory = path.join(root, relative);

  for (const name of readdirSync(directory)) {
    const child = relative ? path.join(relative, name) : name;
    const target = path.join(root, child);
    const stat = statSync(target);

    if (stat.isDirectory()) {
      removeForbiddenAttachments(root, child);
    } else if (name === 'error-context.md') {
      rmSync(target, { force: true });
    }
  }
}

export const DEFAULT_KNOWN_FAILURE_REGISTRY = path.join(
  import.meta.dirname,
  '..',
  REGISTRY_FILENAME,
);

function failureDirectories(outputDir) {
  try {
    return [
      ...new Set(
        listArtifacts(outputDir)
          .filter((artifact) => artifact.endsWith('/failure.json'))
          .map((artifact) => path.posix.dirname(artifact)),
      ),
    ].sort();
  } catch {
    return [];
  }
}

/**
 * Playwright reporter that writes the sanitized run summary, JUnit and upload
 * manifest on every run. It never copies arbitrary Playwright output.
 */
export default class SanitizedArtifactReporter {
  constructor(options = {}) {
    this.options = options;
    this.outputDir = options.outputDir ?? process.env.SP_UI_OUTPUT_DIR ?? path.join(process.cwd(), 'test-results');
    this.runId = options.runId ?? process.env.SP_UI_RUN_ID ?? `ui-smoke-${Date.now()}`;
    this.startedAt = new Date().toISOString();
    this.began = false;
    this.tests = new Map();
    this.globalErrors = [];
    this.knownFailureRegistryPath =
      options.knownFailureRegistryPath ??
      process.env.SP_UI_KNOWN_FAILURES ??
      DEFAULT_KNOWN_FAILURE_REGISTRY;
    this.knownFailureRegistry = loadKnownFailureRegistry(this.knownFailureRegistryPath);
    this.selection = resolveSelection({
      tier: process.env.SP_UI_TIER ?? 'A',
      specs: process.env.SP_UI_SPECS ?? '',
      grep: false,
    });
  }

  onBegin(config, suite) {
    this.began = true;
    this.config = config;
    this.startedAt = new Date().toISOString();
    this.commit = collectCommit();
    this.selection = resolveSelection({
      tier: process.env.SP_UI_TIER ?? 'A',
      specs: process.env.SP_UI_SPECS ?? '',
      grep: hasGrep(config),
      availableSpecs: availableSpecs(suite),
    });

    for (const test of suite.allTests()) {
      this.tests.set(test.id, {
        title: test.title,
        file: relativeTestFile(config, test.location?.file),
        line: test.location?.line ?? 0,
        project: test.parent?.project()?.name ?? 'chromium',
        expectedStatus: test.expectedStatus,
        skipped: test.expectedStatus === 'skipped',
        attempts: [],
      });
    }
  }

  onTestBegin(test) {
    if (!this.tests.has(test.id)) {
      this.tests.set(test.id, {
        title: test.title,
        file: relativeTestFile(this.config, test.location?.file),
        line: test.location?.line ?? 0,
        project: test.parent?.project()?.name ?? 'chromium',
        expectedStatus: test.expectedStatus,
        skipped: false,
        attempts: [],
      });
    }
  }

  onTestEnd(test, result) {
    const entry = this.tests.get(test.id) ?? {
      title: test.title,
      file: relativeTestFile(this.config, test.location?.file),
      line: test.location?.line ?? 0,
      project: test.parent?.project()?.name ?? 'chromium',
      expectedStatus: test.expectedStatus,
      skipped: false,
      attempts: [],
    };

    if (result.status === 'skipped') {
      entry.skipped = true;
    } else {
      entry.attempts.push({
        status: result.status,
        durationMs: result.duration,
        retryIndex: result.retry,
        error: result.error ?? result.errors?.[0],
      });
    }

    this.tests.set(test.id, entry);
  }

  onError(error) {
    this.globalErrors.push({
      message: error?.message ?? String(error),
      stack: error?.stack ?? '',
    });
  }

  onEnd(result) {
    const tests = [...this.tests.values()].map((test) => summarizeTest(test));
    const endedAttempts = tests.reduce((total, test) => total + test.attempts.length, 0);
    const setupFailure = (!this.began || (this.globalErrors.length > 0 && endedAttempts === 0));
    const finishedAt = new Date().toISOString();

    mkdirSync(this.outputDir, { recursive: true });
    removeForbiddenAttachments(this.outputDir);

    const failureDirs = failureDirectories(this.outputDir);
    const summary = buildRunSummary({
      runId: this.runId,
      selection: this.selection,
      commit: this.commit,
      environment: {
        ci: process.env.CI === 'true',
        node: process.version,
        platform: process.platform,
        playwright: this.options.playwrightVersion ?? playwrightVersion(),
        tier: this.selection.tier,
      },
      tests,
      startedAt: this.startedAt,
      finishedAt,
      durationMs: result.duration,
      resultStatus: result.status,
      cancelled: result.status === 'interrupted',
      setupFailure,
      globalErrors: this.globalErrors,
      knownFailureRegistry: {
        schemaVersion: REGISTRY_SCHEMA_VERSION,
        entries: this.knownFailureRegistry.ok ? this.knownFailureRegistry.entries : [],
      },
      knownFailureRegistryErrors: this.knownFailureRegistry.ok
        ? []
        : this.knownFailureRegistry.errors,
      artifacts: { failureDirectories: failureDirs },
    });

    writeFileSync(path.join(this.outputDir, 'run-summary.json'), `${JSON.stringify(summary, null, 2)}\n`);
    writeFileSync(path.join(this.outputDir, 'junit.xml'), renderJUnit(summary));

    this.writeUploadManifest();
  }

  writeUploadManifest() {
    const files = listArtifacts(this.outputDir)
      .filter((artifact) => artifact !== 'upload-manifest.json')
      .map((artifact) => ({
        path: artifact,
        contents: readFileSync(path.join(this.outputDir, artifact)),
      }));
    const manifest = buildUploadManifest(files);

    writeFileSync(path.join(this.outputDir, 'upload-manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`);
  }
}
