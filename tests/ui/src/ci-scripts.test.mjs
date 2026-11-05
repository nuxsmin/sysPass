import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');

function read(relativePath) {
  return readFileSync(path.join(REPO_ROOT, relativePath), 'utf8');
}

test('check-ui-required.sh reads workflow content without GNU-specific base64 flags', () => {
  const source = read('scripts/ci/check-ui-required.sh');

  assert.doesNotMatch(source, /base64\s+(--decode|-d|-D)/, 'base64 flags are not portable across GNU and BSD');
  assert.doesNotMatch(source, /command -v base64/, 'base64 must not be a required tool');
  assert.match(
    source,
    /application\/vnd\.github\.v3\.raw/,
    'the read-only check should request raw content directly from the GitHub API',
  );
});

test('scripts/docker is the single shared image build entry point', () => {
  const shared = read('scripts/docker/Makefile');
  const ai = read('.ai-cli/docker/Makefile');
  const setup = read('scripts/setup-demo.sh');

  assert.match(shared, /^	docker build/m, 'scripts/docker must own the build recipe');
  assert.match(shared, /Dockerfile\.php-testing/);
  assert.doesNotMatch(ai, /^\s*docker build/m, '.ai-cli/docker must delegate instead of duplicating the recipe');
  assert.match(ai, /scripts\/docker/, '.ai-cli/docker must delegate to scripts/docker');
  assert.doesNotMatch(setup, /\.ai-cli\/docker/, 'setup-demo must use the shared scripts/docker entry point');
  assert.match(setup, /scripts\/docker/);
  assert.match(read('AGENTS.md'), /make -C scripts\/docker build/);
  assert.match(read('.ai-cli/bin/php'), /make -C scripts\/docker build/);
  assert.equal(
    existsSync(path.join(REPO_ROOT, '.ai-cli', 'docker', 'Dockerfile.composer')),
    false,
    'the deleted Dockerfile must not remain',
  );
});
