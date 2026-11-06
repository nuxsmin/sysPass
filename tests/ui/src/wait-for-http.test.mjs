import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import http from 'node:http';
import test from 'node:test';

const scriptPath = new URL('./wait-for-http.mjs', import.meta.url).pathname;

function runNode(args) {
  return new Promise((resolve, reject) => {
    const child = spawn(process.execPath, args, { stdio: ['ignore', 'pipe', 'pipe'] });
    let stdout = '';
    let stderr = '';

    child.stdout.on('data', (chunk) => {
      stdout += chunk.toString();
    });
    child.stderr.on('data', (chunk) => {
      stderr += chunk.toString();
    });
    child.on('error', reject);
    child.on('close', (code, signal) => {
      resolve({ code, signal, stdout, stderr });
    });
  });
}

test('a pending response is aborted by a bounded request timeout', async () => {
  const server = http.createServer(() => {
    // Accept the connection and never respond.
  });
  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  const { port } = server.address();
  const started = Date.now();

  try {
    const result = await runNode([
      scriptPath,
      '--url',
      `http://127.0.0.1:${port}/`,
      '--marker',
      'box-login',
      '--timeout',
      '1',
      '--request-timeout-ms',
      '200',
    ]);
    const elapsed = Date.now() - started;

    assert.equal(result.code, 1, result.stderr);
    assert.match(result.stderr, /UI server did not become ready/);
    assert.ok(elapsed < 4000, `expected a bounded request timeout, took ${elapsed}ms`);
  } finally {
    server.closeAllConnections?.();
    await new Promise((resolve) => server.close(resolve));
  }
});
