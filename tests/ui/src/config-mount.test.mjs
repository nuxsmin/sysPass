import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');

test('all smoke application containers mask the developer configuration directory', () => {
  const runner = readFileSync(path.join(root, 'scripts/ui-smoke.sh'), 'utf8');
  const verifier = readFileSync(path.join(root, 'tests/ui/server/verify-paths.php'), 'utf8');

  for (const runRoot of ['RUN_ROOT', 'INSTALL_ROOT']) {
    const mount = `-v "$${runRoot}/config:$REPO_ROOT/app/config"`;
    assert.equal(runner.split(mount).length - 1, 2, `${runRoot}: CLI and web must both mask app/config`);
  }
  assert.equal(runner.split('-v "$REPO_ROOT:$REPO_ROOT:ro"').length - 1, 3,
    'database probes and fingerprinting must mount the repository read-only');
  assert.match(verifier, /\$checkMarker\('app\/config', \$repoRoot/);
});
