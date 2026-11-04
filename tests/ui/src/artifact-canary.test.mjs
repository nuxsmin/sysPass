import assert from 'node:assert/strict';
import { existsSync, mkdtempSync, readFileSync, readdirSync, statSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { isAllowedArtifact } from './uploads.mjs';

const UI_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const PLAYWRIGHT = path.join(UI_DIR, 'node_modules', '.bin', 'playwright');
const BROWSERS = path.join(UI_DIR, '.browsers');
const CANARY = 'CANARY_E2E_SECRET_6f2a9c';

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
  if (!existsSync(PLAYWRIGHT) || (!existsSync(BROWSERS) && !process.env.SP_UI_CHROMIUM_EXECUTABLE)) {
    t.skip('Playwright or pinned Chromium is not installed');
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

test('a diagnostic retry keeps first-attempt failure evidence and never claims Tier A success', (t) => {
  if (!existsSync(PLAYWRIGHT) || (!existsSync(BROWSERS) && !process.env.SP_UI_CHROMIUM_EXECUTABLE)) {
    t.skip('Playwright or pinned Chromium is not installed');
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

  for (const file of files.filter((candidate) => /\.(json|xml|log|txt)$/.test(candidate))) {
    assert.doesNotMatch(
      readFileSync(path.join(outputDir, file), 'utf8'),
      /CANARY_RETRY_SECRET/,
      `${file} retained the synthetic retry canary`,
    );
  }
});
