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

export interface InstallEnvironment {
  dbHost: string;
  dbName: string;
  dbPassword: string;
  siteLang: string;
}

function required(name: string): string {
  const value = process.env[name];

  if (!value) {
    throw new Error(`${name} is required for the isolated browser installer`);
  }

  return value;
}

/**
 * Runner-owned installer inputs. Secrets are passed through the process
 * environment, never through process arguments or committed fixtures.
 */
export function getInstallEnvironment(): InstallEnvironment {
  return {
    dbHost: required('SP_UI_INSTALL_DB_HOST'),
    dbName: required('SP_UI_INSTALL_DB_NAME'),
    dbPassword: required('SP_UI_INSTALL_DB_PASSWORD'),
    siteLang: process.env.SP_UI_INSTALL_SITE_LANG ?? 'en_US',
  };
}
