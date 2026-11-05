import assert from 'node:assert/strict';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const SCRIPT = path.join(REPO_ROOT, 'scripts', 'ci', 'check-setup-budget.sh');
const STAMP_KEYS = [
  'UI_SMOKE_SETUP_STARTED_MS',
  'UI_SMOKE_SPECS_STARTED_MS',
  'UI_SMOKE_SETUP_BUDGET_SECONDS',
];

function runGuard(overrides = {}) {
  const env = { ...process.env };

  for (const key of STAMP_KEYS) {
    delete env[key];
  }

  Object.assign(env, overrides);

  return spawnSync('bash', [SCRIPT], { encoding: 'utf8', env });
}

const STARTED = 1_700_000_000_000;

test('the real setup-budget script accepts a valid window and the exact budget boundary', () => {
  const valid = runGuard({
    UI_SMOKE_SETUP_STARTED_MS: String(STARTED),
    UI_SMOKE_SPECS_STARTED_MS: String(STARTED + 60_000),
    UI_SMOKE_SETUP_BUDGET_SECONDS: '1800',
  });

  assert.equal(valid.status, 0, valid.stderr);
  assert.match(valid.stdout, /setup duration: 60000 ms \(budget 1800000 ms/);

  const boundary = runGuard({
    UI_SMOKE_SETUP_STARTED_MS: String(STARTED),
    UI_SMOKE_SPECS_STARTED_MS: String(STARTED + 1_800_000),
    UI_SMOKE_SETUP_BUDGET_SECONDS: '1800',
  });

  assert.equal(boundary.status, 0, 'a window equal to the budget must not be rejected');
});

test('the real setup-budget script defaults to the 1800-second budget', () => {
  const result = runGuard({
    UI_SMOKE_SETUP_STARTED_MS: String(STARTED),
    UI_SMOKE_SPECS_STARTED_MS: String(STARTED + 60_000),
  });

  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /budget 1800000 ms/);
});

test('the real setup-budget script fails closed for every invalid stamp case', () => {
  const cases = [
    {
      name: 'missing start',
      env: { UI_SMOKE_SPECS_STARTED_MS: String(STARTED + 1) },
      message: /stamps are missing/,
    },
    {
      name: 'missing finish',
      env: { UI_SMOKE_SETUP_STARTED_MS: String(STARTED) },
      message: /stamps are missing/,
    },
    {
      name: 'empty start',
      env: { UI_SMOKE_SETUP_STARTED_MS: '', UI_SMOKE_SPECS_STARTED_MS: String(STARTED + 1) },
      message: /stamps are missing/,
    },
    {
      name: 'empty finish',
      env: { UI_SMOKE_SETUP_STARTED_MS: String(STARTED), UI_SMOKE_SPECS_STARTED_MS: '' },
      message: /stamps are missing/,
    },
    {
      name: 'non-numeric start',
      env: { UI_SMOKE_SETUP_STARTED_MS: 'not-a-number', UI_SMOKE_SPECS_STARTED_MS: String(STARTED + 1) },
      message: /start stamp is not a non-negative integer/,
    },
    {
      name: 'non-numeric finish',
      env: { UI_SMOKE_SETUP_STARTED_MS: String(STARTED), UI_SMOKE_SPECS_STARTED_MS: '12x' },
      message: /finish stamp is not a non-negative integer/,
    },
    {
      name: 'negative start',
      env: { UI_SMOKE_SETUP_STARTED_MS: '-1', UI_SMOKE_SPECS_STARTED_MS: String(STARTED + 1) },
      message: /start stamp is negative/,
    },
    {
      name: 'negative finish',
      env: { UI_SMOKE_SETUP_STARTED_MS: String(STARTED), UI_SMOKE_SPECS_STARTED_MS: '-5' },
      message: /finish stamp is negative/,
    },
    {
      name: 'zero start',
      env: { UI_SMOKE_SETUP_STARTED_MS: '0', UI_SMOKE_SPECS_STARTED_MS: String(STARTED + 1) },
      message: /stamps must be non-zero/,
    },
    {
      name: 'zero finish',
      env: { UI_SMOKE_SETUP_STARTED_MS: String(STARTED), UI_SMOKE_SPECS_STARTED_MS: '0' },
      message: /stamps must be non-zero/,
    },
    {
      name: 'reversed',
      env: { UI_SMOKE_SETUP_STARTED_MS: String(STARTED + 2), UI_SMOKE_SPECS_STARTED_MS: String(STARTED + 1) },
      message: /is not after the start stamp/,
    },
    {
      name: 'equal',
      env: { UI_SMOKE_SETUP_STARTED_MS: String(STARTED), UI_SMOKE_SPECS_STARTED_MS: String(STARTED) },
      message: /is not after the start stamp/,
    },
    {
      name: 'over budget',
      env: {
        UI_SMOKE_SETUP_STARTED_MS: String(STARTED),
        UI_SMOKE_SPECS_STARTED_MS: String(STARTED + 1_800_001),
        UI_SMOKE_SETUP_BUDGET_SECONDS: '1800',
      },
      message: /exceeded its 1800s budget/,
    },
  ];

  for (const testCase of cases) {
    const result = runGuard(testCase.env);

    assert.notEqual(result.status, 0, `${testCase.name} must fail closed`);
    assert.match(result.stderr, /::error::/, `${testCase.name} must emit an error annotation`);
    assert.match(result.stderr, testCase.message, `${testCase.name} must explain the failure`);
  }
});

test('the real setup-budget script rejects an invalid budget', () => {
  for (const budget of ['', '0', '-1', 'abc']) {
    const result = runGuard({
      UI_SMOKE_SETUP_STARTED_MS: String(STARTED),
      UI_SMOKE_SPECS_STARTED_MS: String(STARTED + 1),
      UI_SMOKE_SETUP_BUDGET_SECONDS: budget,
    });

    assert.notEqual(result.status, 0, `budget ${JSON.stringify(budget)} must fail`);
    assert.match(result.stderr, /::error::/);
    assert.match(result.stderr, /budget/);
  }
});
