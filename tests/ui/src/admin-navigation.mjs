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