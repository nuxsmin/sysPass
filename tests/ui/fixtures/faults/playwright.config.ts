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

import path from 'node:path';

import { defineConfig } from '@playwright/test';

const outputDir = process.env.SP_UI_OUTPUT_DIR ?? path.join(import.meta.dirname, 'test-results');
const chromiumExecutable = process.env.SP_UI_CHROMIUM_EXECUTABLE;
const launchOptions = chromiumExecutable ? { executablePath: chromiumExecutable } : undefined;

process.env.PLAYWRIGHT_NO_COPY_PROMPT = '1';

export default defineConfig({
  testDir: '.',
  testMatch: /\/canary\.spec\.ts$/,
  outputDir,
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: [['list'], [path.join(import.meta.dirname, '..', '..', 'src', 'reporter.mjs'), { outputDir }]],
  timeout: 15_000,
  use: {
    baseURL: 'http://127.0.0.1:1',
    trace: 'off',
    video: 'off',
    screenshot: 'off',
    serviceWorkers: 'block',
    ...(launchOptions ? { launchOptions } : {}),
  },
  projects: [{ name: 'chromium' }],
});