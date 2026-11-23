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

export const ADMIN_NAVIGATION_CASES = [
  {
    name: 'accessManager',
    trigger: '#btn-5002',
    route: 'accessManager/index',
    content: [
      'data-tab-route="accessManager/index"',
      'id="data-table-tblUsers"',
      'id="data-table-tblProfiles"',
    ],
    selectors: [
      '[data-tab-route="accessManager/index"]',
      '#data-table-tblUsers',
      '#data-table-tblProfiles',
    ],
  },
  {
    name: 'itemManager',
    trigger: '#btn-5001',
    route: 'itemManager/index',
    content: [
      'data-tab-route="itemManager/index"',
      'id="data-table-tblCategories"',
      'id="data-table-tblClients"',
    ],
    selectors: [
      '[data-tab-route="itemManager/index"]',
      '#data-table-tblCategories',
      '#data-table-tblClients',
    ],
  },
  {
    name: 'securityManager',
    trigger: '#btn-5003',
    route: 'securityManager/index',
    content: [
      'data-tab-route="securityManager/index"',
      'id="data-table-tblEventLog"',
      'id="data-table-tblTracks"',
    ],
    selectors: [
      '[data-tab-route="securityManager/index"]',
      '#data-table-tblEventLog',
      '#data-table-tblTracks',
    ],
  },
  {
    name: 'plugin',
    trigger: '#btn-1101',
    route: 'plugin/index',
    content: ['class="datagrid-header-title"', 'id="data-table-tblPlugins"'],
    selectors: ['.datagrid-header-title', '#data-table-tblPlugins'],
  },
  {
    name: 'configManager',
    trigger: '#btn-config',
    route: 'configManager/index',
    content: [
      'data-tab-route="configManager/index"',
      'id="frmConfig"',
      'data-action-route="configGeneral/save"',
    ],
    selectors: [
      '[data-tab-route="configManager/index"]',
      '#frmConfig',
      '[data-action-route="configGeneral/save"]',
    ],
  },
];