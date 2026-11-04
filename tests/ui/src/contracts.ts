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
