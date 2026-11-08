import assert from 'node:assert/strict';
import { mkdtempSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import test from 'node:test';

import {
  ALLOWED_ROOT_ARTIFACTS,
  allowedArtifacts,
  buildUploadManifest,
  forbiddenReason,
  isAllowedArtifact,
} from './uploads.mjs';

const ALLOWED = [
  'run-summary.json',
  'junit.xml',
  'upload-manifest.json',
  'failures/login-page-attempt-1/screenshot.png',
  'failures/login-page-attempt-1/console.log',
  'failures/login-page-attempt-1/failure.json',
];

const FORBIDDEN = [
  'trace.zip',
  'video.webm',
  'network.har',
  'auth.json',
  'storage-state.json',
  'cookies.txt',
  'config.xml',
  'private-key.pem',
  'fixtures/dump.json',
  'request-body.txt',
  'response-body.txt',
  'dom-snapshot.html',
  'failures/login/actual-dom.txt',
  'failures/login/trace.zip',
];

test('the upload allowlist contains only explicit report and sanitized failure files', () => {
  assert.deepEqual(
    [...ALLOWED_ROOT_ARTIFACTS].sort(),
    ['junit.xml', 'run-summary.json', 'upload-manifest.json'],
  );

  for (const artifact of ALLOWED) {
    assert.equal(isAllowedArtifact(artifact), true, artifact);
  }
});

test('the upload allowlist rejects traces, video, HAR, auth state, config, keys and dumps', () => {
  for (const artifact of FORBIDDEN) {
    assert.equal(isAllowedArtifact(artifact), false, artifact);
    assert.match(forbiddenReason(artifact), /not on the upload allowlist|forbidden/, artifact);
  }
});

test('path traversal and absolute paths are never uploadable', () => {
  for (const artifact of ['../run-summary.json', '/tmp/run-summary.json', 'failures/../../junit.xml']) {
    assert.equal(isAllowedArtifact(artifact), false, artifact);
  }
});

test('buildUploadManifest includes only allowlisted files and their hashes', () => {
  const files = [
    { path: 'run-summary.json', contents: '{"ok":true}' },
    { path: 'failures/login/attempt-1/failure.json', contents: '{"safe":true}' },
    { path: 'failures/login/attempt-1/screenshot.png', contents: Buffer.from([1, 2, 3]) },
    { path: 'trace.zip', contents: 'secret trace' },
    { path: 'auth.json', contents: 'secret auth' },
  ];

  const manifest = buildUploadManifest(files, { now: new Date('2026-11-04T00:00:00Z') });

  assert.equal(manifest.generatedAt, '2026-11-04T00:00:00.000Z');
  assert.deepEqual(
    manifest.files.map((file) => file.path),
    ['failures/login/attempt-1/failure.json', 'failures/login/attempt-1/screenshot.png', 'run-summary.json'],
  );
  assert.ok(manifest.files.every((file) => /^[0-9a-f]{64}$/.test(file.sha256)));
  assert.ok(manifest.files.every((file) => file.bytes > 0));
  assert.equal(manifest.files.find((file) => file.path === 'run-summary.json')?.bytes, 11);
});

test('allowedArtifacts lists only allowlisted inputs', () => {
  assert.deepEqual(
    allowedArtifacts([...ALLOWED, ...FORBIDDEN]).sort(),
    [...ALLOWED].sort(),
  );
});

test('retain copies only allowlisted artifacts and writes its own manifest', async () => {
  const output = mkdtempSync(path.join(tmpdir(), 'sp-ui-output-'));
  const destination = mkdtempSync(path.join(tmpdir(), 'sp-ui-retained-'));
  mkdirSync(path.join(output, 'failures', 'login'), { recursive: true });

  writeFileSync(path.join(output, 'run-summary.json'), '{"status":"failed"}');
  writeFileSync(path.join(output, 'junit.xml'), '<testsuites/>');
  writeFileSync(path.join(output, 'failures', 'login', 'failure.json'), '{"safe":true}');
  writeFileSync(path.join(output, 'failures', 'login', 'console.log'), 'safe log');
  writeFileSync(path.join(output, 'trace.zip'), 'secret trace');
  writeFileSync(path.join(output, 'failures', 'login', 'dom.html'), 'secret dom');

  const { retainArtifacts } = await import('./retain.mjs');
  const manifest = retainArtifacts(output, destination);

  assert.deepEqual(
    manifest.files.map((file) => file.path).sort(),
    ['failures/login/console.log', 'failures/login/failure.json', 'junit.xml', 'run-summary.json'],
  );
  assert.equal(readFileSync(path.join(destination, 'run-summary.json'), 'utf8'), '{"status":"failed"}');
  assert.throws(() => readFileSync(path.join(destination, 'trace.zip')), /ENOENT/);
  assert.throws(() => readFileSync(path.join(destination, 'failures', 'login', 'dom.html')), /ENOENT/);

  const written = JSON.parse(readFileSync(path.join(destination, 'upload-manifest.json'), 'utf8'));
  assert.deepEqual(written.files.map((file) => file.path).sort(), manifest.files.map((file) => file.path).sort());
});
