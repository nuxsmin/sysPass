/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

import assert from 'node:assert/strict';
import test from 'node:test';

import {
  TELEMETRY_ENVIRONMENT,
  resolveCapturePolicy,
} from './capture-policy.mjs';

test('CI forces forbidden capture off even when a local trace flag is set', () => {
  const policy = resolveCapturePolicy(
    { CI: 'true', SP_UI_LOCAL_TRACE: '1', SP_UI_DIAGNOSTIC_RETRY: '1' },
    { syntheticRun: true },
  );

  assert.equal(policy.ci, true);
  assert.equal(policy.trace, 'off');
  assert.equal(policy.video, 'off');
  assert.equal(policy.screenshot, 'off');
  assert.equal(policy.telemetry, false);
  assert.equal(policy.uploadTrace, false);
  assert.match(policy.reasons.join('\n'), /CI forces trace off/);
});

test('a local trace requires explicit opt-in against the synthetic run', () => {
  const refused = resolveCapturePolicy(
    { CI: 'false', SP_UI_LOCAL_TRACE: '1' },
    { syntheticRun: false },
  );
  assert.equal(refused.trace, 'off');
  assert.match(refused.traceError ?? '', /synthetic/);

  const enabled = resolveCapturePolicy(
    { CI: 'false', SP_UI_LOCAL_TRACE: '1' },
    { syntheticRun: true },
  );
  assert.equal(enabled.trace, 'on');
  assert.equal(enabled.uploadTrace, false);
  assert.equal(enabled.telemetry, false);
});

test('trace is off by default and diagnostic retry is explicit', () => {
  const defaults = resolveCapturePolicy({}, { syntheticRun: true });
  assert.equal(defaults.trace, 'off');
  assert.equal(defaults.retries, 0);
  assert.equal(defaults.failOnFlakyTests, false);

  const retry = resolveCapturePolicy(
    { SP_UI_DIAGNOSTIC_RETRY: '1' },
    { syntheticRun: true },
  );
  assert.equal(retry.retries, 1);
  assert.equal(retry.failOnFlakyTests, true);
});

test('CI keeps diagnostic retries at zero by default', () => {
  const policy = resolveCapturePolicy({ CI: 'true' }, { syntheticRun: true });

  assert.equal(policy.retries, 0);
  assert.equal(policy.failOnFlakyTests, false);
});

test('CI enables one diagnostic retry only when explicitly opted in', () => {
  const policy = resolveCapturePolicy(
    { CI: 'true', SP_UI_DIAGNOSTIC_RETRY: '1' },
    { syntheticRun: true },
  );

  assert.equal(policy.retries, 1);
  assert.equal(policy.failOnFlakyTests, true);
  assert.match(policy.reasons.join('\n'), /explicit diagnostic retry/i);
});

test('telemetry environment disables tracking without affecting trace opt-in', () => {
  assert.equal(TELEMETRY_ENVIRONMENT.DO_NOT_TRACK, '1');
  assert.equal(TELEMETRY_ENVIRONMENT.PLAYWRIGHT_CLI_ANALYTICS, '0');
});
