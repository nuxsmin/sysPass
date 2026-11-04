import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const CONFIG = path.join(REPO_ROOT, 'tests', 'ui', 'playwright.config.ts');

function chromiumProject() {
  const contents = readFileSync(CONFIG, 'utf8');
  const start = contents.indexOf('projects:');

  assert.notEqual(start, -1, 'Playwright config must declare projects');

  return contents.slice(start);
}

test('the fixed user agent wins over the desktop device descriptor', () => {
  const project = chromiumProject();

  assert.match(project, /\.\.\.devices\['Desktop Chrome'\]/, 'desktop device properties must be retained');
  assert.match(
    project,
    /\.\.\.devices\['Desktop Chrome'\][\s\S]*userAgent:\s*FIXED_USER_AGENT/,
    'the fixed user agent must be applied after the device descriptor',
  );
});