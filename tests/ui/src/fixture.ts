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

import { readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export interface FixtureCredential {
  login: string;
  password: string;
  masterPassword: string;
}

export interface UiFixture {
  credentials: {
    admin: FixtureCredential;
    demo: FixtureCredential;
    restricted: FixtureCredential;
  };
  ids: {
    groups: { demo: number; restricted: number };
    profiles: { demo: number; restricted: number };
    users: { admin: number; demo: number; restricted: number };
    clients: { primary: number };
    categories: { primary: number };
    accounts: { parent: number; child: number };
    files: { childDocument: number };
  };
  names: {
    demoGroup: string;
    demoProfile: string;
    restrictedGroup: string;
    restrictedProfile: string;
    client: string;
    category: string;
    parentAccount: string;
    childAccount: string;
    childFile: string;
  };
  content: {
    parentLogin: string;
    childLogin: string;
    parentPassword: string;
    childPassword: string;
    childFileText: string;
  };
}

const moduleDirectory = path.dirname(fileURLToPath(import.meta.url));

export const FIXTURE = JSON.parse(
  readFileSync(path.join(moduleDirectory, '..', 'fixtures', 'e2e-fixture.json'), 'utf8'),
) as UiFixture;

export const ADMIN_CREDENTIALS = FIXTURE.credentials.admin;
export const DEMO_CREDENTIALS = FIXTURE.credentials.demo;
export const RESTRICTED_CREDENTIALS = FIXTURE.credentials.restricted;