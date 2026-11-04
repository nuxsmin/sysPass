import assert from 'node:assert/strict';
import { mkdtempSync, readFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import test from 'node:test';

import { writeSetupFailureReport } from './setup-report.mjs';

test('a runner setup failure writes a sanitized setup-failed summary, JUnit and manifest', () => {
  const outputDir = mkdtempSync(path.join(tmpdir(), 'sp-ui-setup-report-'));

  const summary = writeSetupFailureReport({
    outputDir,
    runId: 'sp-ui-smoke.setup-run',
    message: 'MariaDB did not accept TCP connections; password=CANARY_SETUP_SECRET',
    startedAt: '2026-11-04T00:00:00.000Z',
    finishedAt: '2026-11-04T00:00:10.000Z',
  });

  const written = JSON.parse(readFileSync(path.join(outputDir, 'run-summary.json'), 'utf8'));
  const junit = readFileSync(path.join(outputDir, 'junit.xml'), 'utf8');
  const manifest = JSON.parse(readFileSync(path.join(outputDir, 'upload-manifest.json'), 'utf8'));

  assert.equal(summary.status, 'setup-failed');
  assert.equal(written.status, 'setup-failed');
  assert.equal(written.setupFailure, true);
  assert.equal(written.completeTierA, false);
  assert.match(junit, /sp-ui.setupFailure" value="true"/);
  assert.doesNotMatch(JSON.stringify(written), /CANARY_SETUP_SECRET/);
  assert.doesNotMatch(junit, /CANARY_SETUP_SECRET/);
  assert.deepEqual(
    manifest.files.map((file) => file.path).sort(),
    ['junit.xml', 'run-summary.json'],
  );
});
