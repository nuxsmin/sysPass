import assert from 'node:assert/strict';
import { existsSync, mkdtempSync, readFileSync, readdirSync, statSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { buildFailureRecord } from './failure-evidence.mjs';
import { canaryRuntimeStatus } from './canary-runtime.mjs';
import { buildRunSummary, renderJUnit, resolveSelection } from './summary.mjs';
import { specsForTier } from './tiers.mjs';
import { buildUploadManifest, isAllowedArtifact } from './uploads.mjs';
import {
  MASK_COLOR,
  SENSITIVE_BOX,
  UNMASKED_REFERENCE,
  VIEWPORT,
} from '../fixtures/faults/masking-failure.region.mjs';

const UI_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const PLAYWRIGHT = path.join(UI_DIR, 'node_modules', '.bin', 'playwright');
const BROWSERS = path.join(UI_DIR, '.browsers');
const CI_REPORT = path.join(UI_DIR, 'src', 'ci-report.mjs');
const CANARY = 'CANARY_E2E_SECRET_6f2a9c';
const MIN_UNMASKED_CONTENT_FRACTION = 0.5;

function requireCanaryRuntime(t) {
  const runtime = canaryRuntimeStatus({
    playwrightPath: PLAYWRIGHT,
    browsersPath: BROWSERS,
    chromiumExecutable: process.env.SP_UI_CHROMIUM_EXECUTABLE,
  });

  if (runtime.skip) {
    t.skip(runtime.message);
    return false;
  }

  if (!runtime.available) {
    assert.fail(runtime.message);
  }

  return true;
}

async function maskedRegionStats(maskedFile, unmaskedFile) {
  process.env.PLAYWRIGHT_BROWSERS_PATH ??= BROWSERS;

  const { chromium } = await import('@playwright/test');
  const executablePath = process.env.SP_UI_CHROMIUM_EXECUTABLE;

  const browser = await chromium.launch({
    headless: true,
    ...(executablePath ? { executablePath } : {}),
  });

  try {
    const page = await browser.newPage();
    const masked = readFileSync(maskedFile).toString('base64');
    const unmasked = readFileSync(unmaskedFile).toString('base64');

    return await page.evaluate(
      async ({ maskedEncoded, unmaskedEncoded, box, maskColor }) => {
        const decode = async (encoded) => {
          const image = new Image();
          const loaded = new Promise((resolve, reject) => {
            image.onload = () => resolve();
            image.onerror = () => reject(new Error('screenshot PNG could not be decoded'));
          });

          image.src = `data:image/png;base64,${encoded}`;
          await loaded;

          const canvas = document.createElement('canvas');
          canvas.width = image.naturalWidth;
          canvas.height = image.naturalHeight;
          const context = canvas.getContext('2d');

          if (!context) {
            throw new Error('2D canvas is unavailable');
          }

          context.drawImage(image, 0, 0);

          return {
            width: image.naturalWidth,
            height: image.naturalHeight,
            pixels: context.getImageData(box.left, box.top, box.width, box.height).data,
          };
        };

        const [maskedImage, unmaskedImage] = await Promise.all([
          decode(maskedEncoded),
          decode(unmaskedEncoded),
        ]);
        const maskedPixels = maskedImage.pixels;
        const unmaskedPixels = unmaskedImage.pixels;
        let interiorPixels = 0;
        let interiorMaskMismatches = 0;
        let unmaskedInteriorNonBackground = 0;

        for (let y = 0; y < box.height; y += 1) {
          for (let x = 0; x < box.width; x += 1) {
            const index = (y * box.width + x) * 4;

            if (x > 0 && x < box.width - 1 && y > 0 && y < box.height - 1) {
              interiorPixels += 1;

              const isMaskColor =
                maskedPixels[index] === maskColor[0] &&
                maskedPixels[index + 1] === maskColor[1] &&
                maskedPixels[index + 2] === maskColor[2];

              if (!isMaskColor) {
                interiorMaskMismatches += 1;
              }

              const isUnmaskedBackground =
                unmaskedPixels[index] >= 250 &&
                unmaskedPixels[index + 1] >= 250 &&
                unmaskedPixels[index + 2] >= 250;

              if (!isUnmaskedBackground) {
                unmaskedInteriorNonBackground += 1;
              }
            }
          }
        }

        return {
          maskedWidth: maskedImage.width,
          maskedHeight: maskedImage.height,
          unmaskedWidth: unmaskedImage.width,
          unmaskedHeight: unmaskedImage.height,
          interiorPixels,
          interiorMaskMismatches,
          unmaskedInteriorNonBackground,
        };
      },
      {
        maskedEncoded: masked,
        unmaskedEncoded: unmasked,
        box: SENSITIVE_BOX,
        maskColor: MASK_COLOR,
      },
    );
  } finally {
    await browser.close();
  }
}

function listFiles(root, relative = '') {
  const files = [];

  for (const name of readdirSync(path.join(root, relative)).sort()) {
    const child = relative ? `${relative}/${name}` : name;
    const target = path.join(root, child);

    if (statSync(target).isDirectory()) {
      files.push(...listFiles(root, child));
    } else if (statSync(target).isFile()) {
      files.push(child);
    }
  }

  return files;
}

test('a failing Playwright run emits sanitized summary, JUnit, failure evidence and upload manifest', (t) => {
  if (!requireCanaryRuntime(t)) {
    return;
  }

  const outputDir = mkdtempSync(path.join(tmpdir(), 'sp-ui-canary-'));
  const run = spawnSync(
    PLAYWRIGHT,
    ['test', '--config=fixtures/faults/playwright.config.ts'],
    {
      cwd: UI_DIR,
      encoding: 'utf8',
      timeout: 120_000,
      env: {
        ...process.env,
        SP_UI_OUTPUT_DIR: outputDir,
        SP_UI_RUN_ID: 'sp-ui-canary-run',
        PLAYWRIGHT_BROWSERS_PATH: BROWSERS,
      },
    },
  );

  assert.notEqual(run.status, 0, `the canary spec must fail; output: ${run.stdout}\n${run.stderr}`);
  assert.doesNotMatch(
    `${run.stdout}\n${run.stderr}`,
    new RegExp(CANARY),
    'the spawned Playwright process must not echo the canary to stdout/stderr',
  );

  const files = listFiles(outputDir);
  const textFiles = files.filter((file) => /\.(json|xml|log|txt)$/.test(file));

  assert.ok(files.includes('run-summary.json'), `run-summary.json missing: ${files.join(', ')}`);
  assert.ok(files.includes('junit.xml'), `junit.xml missing: ${files.join(', ')}`);
  assert.ok(files.includes('upload-manifest.json'), `upload-manifest.json missing: ${files.join(', ')}`);

  const failureFiles = files.filter((file) => file.endsWith('/failure.json'));
  assert.equal(failureFiles.length, 1, `expected one failure record: ${files.join(', ')}`);
  const failureDir = path.posix.dirname(failureFiles[0]);
  assert.ok(files.includes(`${failureDir}/screenshot.png`), 'masked screenshot missing');
  assert.ok(files.includes(`${failureDir}/console.log`), 'sanitized console log missing');
  assert.ok(statSync(path.join(outputDir, `${failureDir}/screenshot.png`)).size > 0);

  const summary = JSON.parse(readFileSync(path.join(outputDir, 'run-summary.json'), 'utf8'));
  const junit = readFileSync(path.join(outputDir, 'junit.xml'), 'utf8');
  const failure = JSON.parse(readFileSync(path.join(outputDir, failureFiles[0]), 'utf8'));
  const consoleLog = readFileSync(path.join(outputDir, `${failureDir}/console.log`), 'utf8');
  const manifest = JSON.parse(readFileSync(path.join(outputDir, 'upload-manifest.json'), 'utf8'));

  for (const file of textFiles) {
    const contents = readFileSync(path.join(outputDir, file), 'utf8');
    assert.doesNotMatch(contents, new RegExp(CANARY), `${file} retained the synthetic canary`);
  }

  assert.equal(summary.status, 'failed');
  assert.equal(summary.completeTierA, false);
  assert.equal(summary.counts.attempted, 1);
  assert.equal(summary.counts.firstAttemptFailed, 1);
  assert.equal(summary.counts.retries, 0);
  assert.ok(Object.keys(summary.failureClasses).length > 0);
  assert.match(junit, /sp-ui.completeTierA" value="false"/);

  assert.equal(failure.firstAttempt, true);
  assert.equal(failure.failingRequest.route, 'account/index');
  assert.match(failure.failingRequest.url, /r=account\/index/);
  assert.match(failure.failingRequest.url, /token=\[REDACTED\]/);
  assert.ok(failure.console.some((entry) => entry.type === 'error'));
  assert.ok(failure.pageErrors.length > 0);
  assert.equal('dom' in failure, false);
  assert.doesNotMatch(consoleLog, new RegExp(CANARY));

  assert.deepEqual(
    manifest.files.map((file) => file.path).sort(),
    [
      'junit.xml',
      'run-summary.json',
      `${failureDir}/console.log`,
      `${failureDir}/failure.json`,
      `${failureDir}/screenshot.png`,
    ].sort(),
  );
  assert.ok(manifest.files.every((file) => isAllowedArtifact(file.path)));
  assert.deepEqual(
    files.filter((file) => /\.(zip|webm|mp4|har|trace|html|md)$/.test(file)),
    [],
    'forbidden trace/video/HAR/DOM/error-context artifacts must not be produced',
  );

  const retainedDir = mkdtempSync(path.join(tmpdir(), 'sp-ui-canary-retained-'));
  const retain = spawnSync(
    process.execPath,
    [path.join(UI_DIR, 'src', 'retain.mjs'), '--output', outputDir, '--destination', retainedDir],
    { cwd: UI_DIR, encoding: 'utf8', timeout: 60_000 },
  );
  assert.equal(retain.status, 0, `retention failed: ${retain.stderr}`);

  const retainedFiles = listFiles(retainedDir);
  const retainedManifest = JSON.parse(readFileSync(path.join(retainedDir, 'upload-manifest.json'), 'utf8'));

  assert.equal(
    retainedFiles.filter((file) => file.endsWith('error-context.md')).length,
    0,
    `error-context.md must not survive retention: ${retainedFiles.join(', ')}`,
  );
  assert.ok(retainedManifest.files.every((file) => isAllowedArtifact(file.path)));
  assert.deepEqual(
    retainedManifest.files.map((file) => file.path).sort(),
    manifest.files.map((file) => file.path).sort(),
  );

  for (const file of retainedFiles.filter((candidate) => /\.(json|xml|log|txt)$/.test(candidate))) {
    const contents = readFileSync(path.join(retainedDir, file), 'utf8');

    assert.doesNotMatch(contents, new RegExp(CANARY), `${file} retained the synthetic canary after retention`);
    assert.doesNotMatch(contents, /\/home\/[A-Za-z0-9_.-]+\//, `${file} retained an absolute user path`);
  }
});

test('a masked screenshot replaces a sensitive region with the mask colour', (t) => {
  if (!requireCanaryRuntime(t)) {
    return;
  }

  const outputDir = mkdtempSync(path.join(tmpdir(), 'sp-ui-masking-canary-'));
  const run = spawnSync(
    PLAYWRIGHT,
    ['test', '--config=fixtures/faults/masking.config.ts'],
    {
      cwd: UI_DIR,
      encoding: 'utf8',
      timeout: 120_000,
      env: {
        ...process.env,
        SP_UI_OUTPUT_DIR: outputDir,
        PLAYWRIGHT_BROWSERS_PATH: BROWSERS,
      },
    },
  );

  assert.equal(run.status, 0, `the masking canary must prove the masked region: ${run.stdout}\n${run.stderr}`);
});

test('the real failure-evidence screenshot masks a sensitive canary region', async (t) => {
  if (!requireCanaryRuntime(t)) {
    return;
  }

  const outputDir = mkdtempSync(path.join(tmpdir(), 'sp-ui-masking-failure-canary-'));
  const referenceFile = path.join(
    mkdtempSync(path.join(tmpdir(), 'sp-ui-masking-failure-reference-')),
    UNMASKED_REFERENCE,
  );
  const run = spawnSync(
    PLAYWRIGHT,
    ['test', '--config=fixtures/faults/masking-failure.config.ts'],
    {
      cwd: UI_DIR,
      encoding: 'utf8',
      timeout: 120_000,
      env: {
        ...process.env,
        SP_UI_OUTPUT_DIR: outputDir,
        SP_UI_CANARY_UNMASKED_REFERENCE: referenceFile,
        PLAYWRIGHT_BROWSERS_PATH: BROWSERS,
      },
    },
  );

  assert.notEqual(run.status, 0, `the masking failure fixture must fail the run: ${run.stdout}\n${run.stderr}`);

  const files = listFiles(outputDir);
  const failureFiles = files.filter((file) => file.endsWith('/failure.json'));

  assert.equal(failureFiles.length, 1, `expected one failure record: ${files.join(', ')}`);

  const failureDir = path.posix.dirname(failureFiles[0]);
  const screenshot = `${failureDir}/screenshot.png`;

  assert.ok(files.includes(screenshot), `failure screenshot missing: ${files.join(', ')}`);
  assert.ok(
    existsSync(referenceFile),
    `unmasked reference screenshot missing: ${referenceFile}`,
  );

  const stats = await maskedRegionStats(path.join(outputDir, screenshot), referenceFile);
  const observedContentFraction =
    stats.unmaskedInteriorNonBackground / stats.interiorPixels;

  t.diagnostic(
    `unmasked sensitive region: ${stats.unmaskedInteriorNonBackground}/${stats.interiorPixels} non-background pixels (${(observedContentFraction * 100).toFixed(1)}%)`,
  );

  assert.equal(
    stats.maskedWidth,
    VIEWPORT.width,
    `the retained failure screenshot is ${stats.maskedWidth}px wide, expected ${VIEWPORT.width}px`,
  );
  assert.equal(
    stats.maskedHeight,
    VIEWPORT.height,
    `the retained failure screenshot is ${stats.maskedHeight}px tall, expected ${VIEWPORT.height}px`,
  );
  assert.equal(
    stats.unmaskedWidth,
    VIEWPORT.width,
    `the unmasked reference is ${stats.unmaskedWidth}px wide, expected ${VIEWPORT.width}px`,
  );
  assert.equal(
    stats.unmaskedHeight,
    VIEWPORT.height,
    `the unmasked reference is ${stats.unmaskedHeight}px tall, expected ${VIEWPORT.height}px`,
  );
  assert.ok(stats.interiorPixels > 0, 'the sensitive region must have an interior to inspect');
  assert.ok(
    observedContentFraction >= MIN_UNMASKED_CONTENT_FRACTION,
    `the unmasked sensitive region rendered ${stats.unmaskedInteriorNonBackground}/${stats.interiorPixels} non-background pixels (${(observedContentFraction * 100).toFixed(1)}%), expected at least ${(MIN_UNMASKED_CONTENT_FRACTION * 100).toFixed(1)}%`,
  );
  assert.equal(
    stats.interiorMaskMismatches,
    0,
    `the retained failure screenshot rendered ${stats.interiorMaskMismatches} unmasked interior pixels in the sensitive region`,
  );
});

test('a diagnostic retry keeps first-attempt failure evidence and never claims Tier A success', (t) => {
  if (!requireCanaryRuntime(t)) {
    return;
  }

  const outputDir = mkdtempSync(path.join(tmpdir(), 'sp-ui-retry-canary-'));
  const run = spawnSync(
    PLAYWRIGHT,
    ['test', '--config=fixtures/faults/retry.config.ts'],
    {
      cwd: UI_DIR,
      encoding: 'utf8',
      timeout: 120_000,
      env: {
        ...process.env,
        SP_UI_OUTPUT_DIR: outputDir,
        SP_UI_RUN_ID: 'sp-ui-retry-canary-run',
        PLAYWRIGHT_BROWSERS_PATH: BROWSERS,
      },
    },
  );

  assert.notEqual(run.status, 0, `the flaky canary must fail the run; output: ${run.stdout}\n${run.stderr}`);
  assert.doesNotMatch(
    `${run.stdout}\n${run.stderr}`,
    /CANARY_RETRY_SECRET/,
    'the spawned Playwright process must not echo the retry canary to stdout/stderr',
  );

  const files = listFiles(outputDir);
  const summary = JSON.parse(readFileSync(path.join(outputDir, 'run-summary.json'), 'utf8'));
  const junit = readFileSync(path.join(outputDir, 'junit.xml'), 'utf8');
  const failureFiles = files.filter((file) => file.endsWith('/failure.json'));

  assert.equal(failureFiles.length, 1, `first-attempt evidence missing: ${files.join(', ')}`);
  assert.ok(failureFiles[0].includes('attempt-1'));
  assert.ok(files.includes(`${path.posix.dirname(failureFiles[0])}/screenshot.png`));
  assert.equal(summary.status, 'flaky');
  assert.equal(summary.completeTierA, false);
  assert.equal(summary.counts.retries, 1);
  assert.equal(summary.counts.firstAttemptFailed, 1);
  assert.equal(summary.counts.flaky, 1);
  assert.match(junit, /first attempt failed/);
  assert.doesNotMatch(JSON.stringify(summary), /CANARY_RETRY_SECRET/);

  const report = spawnSync(process.execPath, [CI_REPORT, '--summaries', outputDir], {
    encoding: 'utf8',
    env: { ...process.env, GITHUB_STEP_SUMMARY: '' },
  });

  assert.notEqual(report.status, 0, 'a retry-success flaky run must be rejected by ci-report');
  assert.match(report.stderr, /flaky/);

  for (const file of files.filter((candidate) => /\.(json|xml|log|txt)$/.test(candidate))) {
    assert.doesNotMatch(
      readFileSync(path.join(outputDir, file), 'utf8'),
      /CANARY_RETRY_SECRET/,
      `${file} retained the synthetic retry canary`,
    );
  }
});

test('a WRITE failure canary never reaches summary, JUnit, failure.json, manifest or console output', () => {
  const name = 'CANARY_WRITE_NAME_7d3e';
  const login = 'CANARY_WRITE_LOGIN_8c1f';
  const password = 'CANARY_WRITE_PASSWORD_9a2b';
  const ciphertext = 'CANARY_WRITE_CIPHERTEXT_4f6d';
  const route = 'account/saveCreate';
  const url = `http://ui-smoke.invalid/index.php?r=${route}&name=${name}&login=${login}&password=${password}&ciphertext=${ciphertext}`;
  const message = `http [${route}]: POST ${url} failed with password=${password}`;
  const selection = resolveSelection({
    tier: 'WRITE',
    specs: '',
    grep: false,
    availableSpecs: specsForTier('WRITE'),
  });
  const summary = buildRunSummary({
    runId: 'ui-workflow.write-canary',
    selection,
    commit: { sha: 'f'.repeat(40), short: 'fffffffff', dirty: false, branch: 'master' },
    environment: { ci: true, node: 'v20.19.2', platform: 'linux', playwright: '1.63.0', tier: 'WRITE' },
    tests: [
      {
        title: 'creates one account through the write wiring',
        file: 'tests/ui/specs/accountCreate.write.spec.ts',
        attempts: [
          {
            status: 'failed',
            durationMs: 42,
            error: { message, stack: `Error: ${message}\n    at ${url}` },
          },
        ],
      },
    ],
    startedAt: '2026-11-12T00:00:00.000Z',
    finishedAt: '2026-11-12T00:01:00.000Z',
    durationMs: 60_000,
    resultStatus: 'failed',
    globalErrors: [{ message }],
  });
  const junit = renderJUnit(summary);
  const failure = buildFailureRecord({
    runId: 'ui-workflow.write-canary',
    test: {
      title: 'creates one account through the write wiring',
      file: 'tests/ui/specs/accountCreate.write.spec.ts',
      project: 'chromium',
    },
    error: { message, stack: `Error: ${message}\n    at ${url}` },
    pageUrl: url,
    requests: [{ route, method: 'POST', status: 500, url }],
    consoleEntries: [{ type: 'error', text: `save request failed: ${url} password=${password}` }],
  });
  const manifest = buildUploadManifest([
    { path: 'run-summary.json', contents: JSON.stringify(summary) },
    { path: 'junit.xml', contents: junit },
    { path: 'failures/attempt-1/failure.json', contents: JSON.stringify(failure) },
  ]);
  const retained = {
    summary: JSON.stringify(summary),
    junit,
    failure: JSON.stringify(failure),
    manifest: JSON.stringify(manifest),
    console: JSON.stringify(failure.console),
  };

  assert.equal(summary.status, 'failed');
  assert.equal(summary.completeTierWrite, false);
  assert.equal(summary.completeTierA, false);

  for (const [artifact, contents] of Object.entries(retained)) {
    for (const secret of [name, login, password, ciphertext]) {
      assert.doesNotMatch(contents, new RegExp(secret), `${artifact} retained the synthetic WRITE canary`);
    }
  }
});
