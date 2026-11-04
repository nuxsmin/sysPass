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
