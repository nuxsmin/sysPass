export type ActionFormat = 'json' | 'html' | 'none';

export interface ActionSpec {
  route: string;
  format?: ActionFormat;
  content?: string[];
  emptyBodyFails?: boolean;
}

export type Condition =
  | { kind: 'visible' | 'hidden'; selector: string; label?: string }
  | { kind: 'count'; selector: string; min?: number; max?: number; label?: string }
  | { kind: 'text'; selector: string; contains: string; label?: string }
  | { kind: 'class'; selector: string; name: string; label?: string }
  | { kind: 'attribute'; selector: string; name: string; value?: string; label?: string };

export interface PageContract {
  name: string;
  actions: ActionSpec[];
  conditions: Condition[];
}

/**
 * Anonymous login page. It deliberately has no `#content` requirement: the
 * login and installer pages render outside the authenticated shell.
 */
export function loginContract(): PageContract {
  return {
    name: 'anonymous login',
    actions: [
      { route: 'bootstrap/getEnvironment', format: 'json' },
      { route: 'login/index', format: 'html' },
    ],
    conditions: [
      { kind: 'visible', selector: '#box-login' },
      { kind: 'attribute', selector: '#frmLogin', name: 'method', value: 'post' },
      { kind: 'attribute', selector: '#frmLogin', name: 'data-onsubmit', value: 'main/login' },
      { kind: 'attribute', selector: '#frmLogin', name: 'data-route', value: 'login/login' },
      { kind: 'visible', selector: '#user' },
      { kind: 'visible', selector: '#pass' },
      { kind: 'visible', selector: '#btnLogin' },
      { kind: 'attribute', selector: '#frmLogin input[name="isAjax"]', name: 'value', value: '1' },
      { kind: 'hidden', selector: '#wrap-loading' },
    ],
  };
}

export interface ShellContractOptions {
  parentAccount: string;
  childAccount: string;
  client: string;
  category: string;
}

export type AccountNavigationMode = 'in-shell' | 'direct';
interface AccountPageContractOptions {
  mode: AccountNavigationMode;
  accountId: number;
  accountName: string;
  parentAccount: string;
  childFile: string;
}

function contentPageCondition(mode: AccountNavigationMode): Condition[] {
  return mode === 'direct'
    ? [{ kind: 'attribute', selector: '#content', name: 'data-page', value: 'account' }]
    : [];
}

function accountCommonConditions(mode: AccountNavigationMode): Condition[] {
  return [
    { kind: 'visible', selector: '#content' },
    ...contentPageCondition(mode),
    { kind: 'hidden', selector: '#wrap-loading' },
  ];
}

function parentSelectConditions(
  parentAccount: string,
  itemId: string,
  expectOptionList = false,
): Condition[] {
  const selectedOrListedOption: Condition = {
    kind: 'count',
    selector: [
      `#parent_account_id + .selectize-control .item:has-text("${parentAccount}")`,
      `#parent_account_id + .selectize-control .option:has-text("${parentAccount}")`,
    ].join(', '),
    min: 1,
    label: 'eligible seeded parent-account option',
  };

  return [
    {
      kind: 'attribute',
      selector: '#parent_account_id',
      name: 'data-action-route',
      value: 'items/accountsUser',
    },
    {
      kind: 'attribute',
      selector: '#parent_account_id',
      name: 'data-item-id',
      value: itemId,
    },
    ...(expectOptionList
      ? [
          {
            kind: 'count' as const,
            selector: '#parent_account_id + .selectize-control .option',
            min: 2,
            label: 'linked-account options produced by items/accountsUser',
          },
        ]
      : []),
    selectedOrListedOption,
  ];
}

function accountFileConditions(accountId: number, childFile: string): Condition[] {
  return [
    {
      kind: 'attribute',
      selector: '#list-account-files',
      name: 'data-action-route',
      value: 'accountFile/list',
    },
    {
      kind: 'attribute',
      selector: '#list-account-files',
      name: 'data-item-id',
      value: String(accountId),
    },
    {
      kind: 'text',
      selector: '#list-account-files',
      contains: childFile,
      label: 'seeded file-list content produced by accountFile/list',
    },
  ];
}

/**
 * Account view page. Both navigation modes must complete the account view,
 * its file list and the linked-account option load; a static panel or the
 * select's placeholder option cannot satisfy the contract.
 */
export function accountViewContract({
  mode,
  accountId,
  accountName,
  parentAccount,
  childFile,
}: AccountPageContractOptions): PageContract {
  return {
    name: `account view (${mode})`,
    actions: [
      { route: 'account/view', format: 'html', content: [accountName, 'Account Details'] },
      { route: 'accountFile/list', format: 'html', content: [childFile] },
      { route: 'items/accountsUser', format: 'json', content: [parentAccount] },
    ],
    conditions: [
      ...accountCommonConditions(mode),
      { kind: 'text', selector: '#title', contains: 'Account Details' },
      { kind: 'attribute', selector: '#frmAccount', name: 'data-onsubmit', value: 'account/save' },
      { kind: 'attribute', selector: '#frmAccount', name: 'data-action-route', value: '' },
      {
        kind: 'attribute',
        selector: '#frmAccount',
        name: 'data-item-id',
        value: String(accountId),
      },
      ...accountFileConditions(accountId, childFile),
      ...parentSelectConditions(parentAccount, String(accountId)),
    ],
  };
}

/**
 * New-account page. It has no account id yet, so accountFile/list must not be
 * expected; the seeded linked-account options must still be action-produced.
 */
export function accountCreateContract({
  mode,
  parentAccount,
}: Pick<AccountPageContractOptions, 'mode' | 'parentAccount'>): PageContract {
  return {
    name: `account create (${mode})`,
    actions: [
      { route: 'account/create', format: 'html', content: ['New Account'] },
      { route: 'items/accountsUser', format: 'json', content: [parentAccount] },
    ],
    conditions: [
      ...accountCommonConditions(mode),
      { kind: 'text', selector: '#title', contains: 'New Account' },
      { kind: 'attribute', selector: '#frmAccount', name: 'data-onsubmit', value: 'account/save' },
      {
        kind: 'attribute',
        selector: '#frmAccount',
        name: 'data-action-route',
        value: 'account/saveCreate',
      },
      { kind: 'attribute', selector: '#frmAccount', name: 'data-item-id', value: '' },
      ...parentSelectConditions(parentAccount, '0', true),
    ],
  };
}

/**
 * Account edit page. Both the seeded file list and the linked-account options
 * are required in both navigation modes.
 */
export function accountEditContract({
  mode,
  accountId,
  accountName,
  parentAccount,
  childFile,
}: AccountPageContractOptions): PageContract {
  return {
    name: `account edit (${mode})`,
    actions: [
      { route: 'account/edit', format: 'html', content: [accountName, 'Edit Account'] },
      { route: 'accountFile/list', format: 'html', content: [childFile] },
      { route: 'items/accountsUser', format: 'json', content: [parentAccount] },
    ],
    conditions: [
      ...accountCommonConditions(mode),
      { kind: 'text', selector: '#title', contains: 'Edit Account' },
      { kind: 'attribute', selector: '#frmAccount', name: 'data-onsubmit', value: 'account/save' },
      {
        kind: 'attribute',
        selector: '#frmAccount',
        name: 'data-action-route',
        value: 'account/saveEdit',
      },
      {
        kind: 'attribute',
        selector: '#frmAccount',
        name: 'data-item-id',
        value: String(accountId),
      },
      ...accountFileConditions(accountId, childFile),
      ...parentSelectConditions(parentAccount, String(accountId)),
    ],
  };
}

/**
 * Authenticated shell after its automatic Page-load Actions.
 *
 * The account rows are action-produced content, not static shell markup, so a
 * static panel heading or placeholder options cannot satisfy the contract.
 */
export function shellContract({
  parentAccount,
  childAccount,
  client,
  category,
}: ShellContractOptions): PageContract {
  return {
    name: 'authenticated shell',
    actions: [
      { route: 'bootstrap/getEnvironment', format: 'json' },
      {
        route: 'account/index',
        format: 'html',
        content: [parentAccount, childAccount, client, category],
      },
      { route: 'items/notifications', format: 'json' },
    ],
    conditions: [
      { kind: 'class', selector: 'body', name: 'main' },
      { kind: 'visible', selector: '#wrap' },
      { kind: 'visible', selector: '#content' },
      { kind: 'attribute', selector: '#content', name: 'data-page' },
      { kind: 'count', selector: '#search-rows .account-label', min: 2, label: 'seeded account rows' },
      { kind: 'text', selector: '#content', contains: parentAccount },
      { kind: 'text', selector: '#content', contains: childAccount },
      { kind: 'text', selector: '#content', contains: client },
      { kind: 'text', selector: '#content', contains: category },
      { kind: 'hidden', selector: '#wrap-loading' },
    ],
  };
}

/**
 * Shared Tier B in-shell contract helpers.
 *
 * Tier B indexes are loaded through the authenticated shell. The returned
 * fragment replaces `#content`, so every contract requires the index's own
 * root/content and proves that the previous account-search content is gone.
 */
function tierBStaleShellCondition(): Condition {
  return {
    kind: 'count',
    selector: '#content #search-rows',
    max: 0,
    label: 'stale account shell content must be replaced by the index',
  };
}

function tierBTriggerCondition(
  selector: string,
  name: string,
  value: string,
): Condition {
  return { kind: 'attribute', selector, name, value, label: `${selector} dispatch ${name}=${value}` };
}

function tierBTabConditions(route: string, gridSelectors: string[]): Condition[] {
  return [
    { kind: 'visible', selector: '#content' },
    tierBStaleShellCondition(),
    { kind: 'count', selector: `#content [data-tab-route="${route}"]`, min: 1, label: `${route} tab root` },
    { kind: 'count', selector: '#content #tabsHeader', min: 1, label: 'index tab headers' },
    { kind: 'count', selector: '#content #tabs-0', min: 1, label: 'first index tab panel' },
    ...gridSelectors.map(
      (selector): Condition => ({
        kind: 'count',
        selector: `#content ${selector}`,
        min: 1,
        label: `${route} content ${selector}`,
      }),
    ),
  ];
}

function tierBTabAction(route: string, content: string[]): ActionSpec {
  return { route, format: 'html', content: [`data-tab-route="${route}"`, ...content] };
}

/**
 * Access manager (`accessManager/index`): users, groups, profiles and API
 * tokens are rendered as tabs. Require the users and profiles grids so a bare
 * tab shell or stale account list cannot satisfy the contract.
 */
export function accessManagerContract(): PageContract {
  const route = 'accessManager/index';

  return {
    name: route,
    actions: [tierBTabAction(route, ['id="data-table-tblUsers"', 'id="data-table-tblProfiles"'])],
    conditions: [
      ...tierBTabConditions(route, ['#data-table-tblUsers', '#data-table-tblProfiles']),
      tierBTriggerCondition('#btn-5002', 'data-route', route),
      tierBTriggerCondition('#btn-5002', 'data-view', 'datatabs'),
    ],
  };
}

/**
 * Item manager (`itemManager/index`): categories and clients are required
 * action-produced grids inside the tab shell.
 */
export function itemManagerContract(): PageContract {
  const route = 'itemManager/index';

  return {
    name: route,
    actions: [tierBTabAction(route, ['id="data-table-tblCategories"', 'id="data-table-tblClients"'])],
    conditions: [
      ...tierBTabConditions(route, ['#data-table-tblCategories', '#data-table-tblClients']),
      tierBTriggerCondition('#btn-5001', 'data-route', route),
      tierBTriggerCondition('#btn-5001', 'data-view', 'datatabs'),
    ],
  };
}

/**
 * Security manager (`securityManager/index`): event log and track grids are
 * required action-produced content.
 */
export function securityManagerContract(): PageContract {
  const route = 'securityManager/index';

  return {
    name: route,
    actions: [tierBTabAction(route, ['id="data-table-tblEventLog"', 'id="data-table-tblTracks"'])],
    conditions: [
      ...tierBTabConditions(route, ['#data-table-tblEventLog', '#data-table-tblTracks']),
      tierBTriggerCondition('#btn-5003', 'data-route', route),
      tierBTriggerCondition('#btn-5003', 'data-view', 'datatabs'),
    ],
  };
}

/**
 * Configuration manager (`configManager/index`): the general configuration
 * form is required action-produced content.
 */
export function configManagerContract(): PageContract {
  const route = 'configManager/index';

  return {
    name: route,
    actions: [tierBTabAction(route, ['id="frmConfig"', 'data-action-route="configGeneral/save"'])],
    conditions: [
      ...tierBTabConditions(route, ['#frmConfig']),
      tierBTriggerCondition('#btn-config', 'data-route', route),
      tierBTriggerCondition('#btn-config', 'data-view', 'config'),
    ],
  };
}

/**
 * Notification index (`notification/index`): its own header and notification
 * grid are required.
 */
/**
 * Notification and plugin indexes share the same header/grid shape. Keep one
 * builder so their positive contracts cannot drift apart.
 */
function tierBGridContract({
  route,
  title,
  gridId,
  trigger,
  attributes,
}: {
  route: string;
  title: string;
  gridId: string;
  trigger: string;
  attributes: Record<string, string>;
}): PageContract {
  return {
    name: route,
    actions: [
      {
        route,
        format: 'html',
        content: ['class="datagrid-header-title"', title, `id="${gridId}"`],
      },
    ],
    conditions: [
      { kind: 'visible', selector: '#content' },
      tierBStaleShellCondition(),
      { kind: 'visible', selector: '#content .datagrid-header-title' },
      { kind: 'text', selector: '#content .datagrid-header-title', contains: title },
      { kind: 'visible', selector: `#content #${gridId}` },
      ...Object.entries(attributes).map(([name, value]) =>
        tierBTriggerCondition(trigger, name, value),
      ),
    ],
  };
}

export function notificationContract(): PageContract {
  return tierBGridContract({
    route: 'notification/index',
    title: 'Notifications',
    gridId: 'data-table-tblNotifications',
    trigger: '#notifications',
    attributes: {
      'data-route': 'notification/index',
      'data-view': 'notifications',
    },
  });
}

/**
 * User settings (`userSettingsManager/index`): the preferences tab and form
 * are required action-produced content.
 */
export function userSettingsManagerContract(): PageContract {
  const route = 'userSettingsManager/index';

  return {
    name: route,
    actions: [
      tierBTabAction(route, ['id="frmPreferences"', 'id="sel-userlang"']),
    ],
    conditions: [
      ...tierBTabConditions(route, ['#frmPreferences', '#sel-userlang']),
      { kind: 'text', selector: '#content #title', contains: 'Site' },
      tierBTriggerCondition('#btnPrefs', 'data-action-route', route),
      tierBTriggerCondition('#btnPrefs', 'data-onclick', 'user/showSettings'),
    ],
  };
}

/**
 * Plugin index (`plugin/index`): its own header and plugin grid are required.
 */
export function pluginContract(): PageContract {
  return tierBGridContract({
    route: 'plugin/index',
    title: 'Plugins',
    gridId: 'data-table-tblPlugins',
    trigger: '#btn-1101',
    attributes: {
      'data-route': 'plugin/index',
      'data-view': 'plugin',
    },
  });
}
