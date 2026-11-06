import type { BrowserContext, Page } from '@playwright/test';

import { expect } from '@playwright/test';

import {
  accessManagerContract,
  configManagerContract,
  itemManagerContract,
  notificationContract,
  pluginContract,
  securityManagerContract,
  userSettingsManagerContract,
  type PageContract,
} from './contracts';
import { ADMIN_CREDENTIALS } from './fixture';
import { loginThroughUi } from './login';
import { ActionTracker } from './readiness';
import { openSeededShellPage, SEEDED_SHELL_CONTRACT } from './seeded-shell';
import type { SessionState } from './session';

export interface TierBNavigation {
  trigger: string;
  route: string;
  attributes: Record<string, string>;
  contract: PageContract;
}

export interface TierBIndexCase extends TierBNavigation {
  spec: string;
}

/**
 * The seven Tier B index navigations, declared once so the real specs and the
 * scratch fault canary exercise exactly the same trigger/route/contract tuple.
 */
export const TIER_B_INDEX_CASES = {
  accessManager: {
    spec: 'accessManager.spec.ts',
    trigger: '#btn-5002',
    route: 'accessManager/index',
    attributes: { 'data-route': 'accessManager/index', 'data-view': 'datatabs' },
    contract: accessManagerContract(),
  },
  itemManager: {
    spec: 'itemManager.spec.ts',
    trigger: '#btn-5001',
    route: 'itemManager/index',
    attributes: { 'data-route': 'itemManager/index', 'data-view': 'datatabs' },
    contract: itemManagerContract(),
  },
  securityManager: {
    spec: 'securityManager.spec.ts',
    trigger: '#btn-5003',
    route: 'securityManager/index',
    attributes: { 'data-route': 'securityManager/index', 'data-view': 'datatabs' },
    contract: securityManagerContract(),
  },
  configManager: {
    spec: 'configManager.spec.ts',
    trigger: '#btn-config',
    route: 'configManager/index',
    attributes: { 'data-route': 'configManager/index', 'data-view': 'config' },
    contract: configManagerContract(),
  },
  notification: {
    spec: 'notification.spec.ts',
    trigger: '#notifications',
    route: 'notification/index',
    attributes: { 'data-route': 'notification/index', 'data-view': 'notifications' },
    contract: notificationContract(),
  },
  userSettingsManager: {
    spec: 'userSettingsManager.spec.ts',
    trigger: '#btnPrefs',
    route: 'userSettingsManager/index',
    attributes: {
      'data-action-route': 'userSettingsManager/index',
      'data-onclick': 'user/showSettings',
    },
    contract: userSettingsManagerContract(),
  },
  plugin: {
    spec: 'plugin.spec.ts',
    trigger: '#btn-1101',
    route: 'plugin/index',
    attributes: { 'data-route': 'plugin/index', 'data-view': 'plugin' },
    contract: pluginContract(),
  },
} satisfies Record<string, TierBIndexCase>;

/**
 * Authenticate once for the seeded suite and require the complete shell before
 * any Tier B index is navigated to. Later specs reuse the live worker cookies;
 * they never log in again and never restore an immutable session snapshot.
 */
export async function openSeededShell(
  page: Page,
  tracker: ActionTracker,
  session: SessionState,
): Promise<void> {
  tracker.setContract(SEEDED_SHELL_CONTRACT);

  if (!session.authenticated) {
    const since = await loginThroughUi(page, ADMIN_CREDENTIALS);

    session.authenticated = true;
    tracker.setSessionEstablished(true);
    await tracker.waitForReady(SEEDED_SHELL_CONTRACT, since);
    return;
  }

  tracker.setSessionEstablished(true);
  const since = await openSeededShellPage(page, tracker);

  expect(
    tracker.observedRoutes(since),
    'a reused Tier B session must not silently navigate back through the login route',
  ).not.toContain('login/login');
  expect(tracker.observedRoutes(since)).not.toContain('login/index');
}

/**
 * Prove the shell menu item still carries the route/view binding that the
 * initializer dispatches, then click it and require the index's own contract.
 * Errors name the route so a removed route or suppressed initializer is
 * attributable to the affected Tier B spec.
 */
export async function navigateTierBIndex(
  page: Page,
  tracker: ActionTracker,
  { trigger, route, attributes, contract }: TierBNavigation,
): Promise<void> {
  const locator = page.locator(trigger).first();

  await expect(locator, `Tier B ${route} navigation trigger ${trigger} must be visible`).toBeVisible();

  for (const [name, value] of Object.entries(attributes)) {
    await expect(
      locator,
      `Tier B ${route} navigation trigger ${trigger} must keep ${name}=${value}`,
    ).toHaveAttribute(name, value);
  }

  tracker.setContract(contract);
  const since = Date.now();

  await locator.click();
  await tracker.waitForReady(contract, since);
}

export async function runTierBIndex(
  page: Page,
  tracker: ActionTracker,
  session: SessionState,
  indexCase: TierBIndexCase,
): Promise<void> {
  await openSeededShell(page, tracker, session);
  await navigateTierBIndex(page, tracker, indexCase);
}

function escapeRegExp(value: string): string {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Scratch-only browser fault proof. Suppress one real index response, require
 * the real navigation to fail while naming the spec and route, remove the
 * interception and prove the unmodified route loads again. The deliberate
 * failure is rethrown so it stays visible in the run evidence.
 */
export async function proveTierBFaultCanary(
  page: Page,
  context: BrowserContext,
  session: SessionState,
  indexCase: TierBIndexCase,
): Promise<never> {
  const matcher = new RegExp(`[?&]r=${escapeRegExp(indexCase.route)}(?:[&#]|$)`);
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });

  try {
    await openSeededShell(page, tracker, session);
    await page.route(matcher, async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'text/html',
        body: '<div id="content"></div>',
      });
    });

    let observedFailure: unknown;

    try {
      await navigateTierBIndex(page, tracker, indexCase);
    } catch (error) {
      observedFailure = error;
    } finally {
      await page.unroute(matcher);
    }

    if (!observedFailure) {
      throw new Error(
        `Tier B fault canary for ${indexCase.spec} did not fail when ${indexCase.route} was suppressed`,
      );
    }

    const observedMessage =
      observedFailure instanceof Error ? observedFailure.message : String(observedFailure);

    if (!observedMessage.includes(indexCase.route)) {
      throw new Error(
        `Tier B fault canary failure for ${indexCase.spec} did not name ${indexCase.route}: ${observedMessage}`,
      );
    }

    const restoreTracker = new ActionTracker(page, context, { sessionEstablished: true });

    try {
      await openSeededShellPage(page, restoreTracker);
      await navigateTierBIndex(page, restoreTracker, indexCase);
    } finally {
      restoreTracker.dispose();
    }

    throw new Error(
      `Tier B fault canary (${indexCase.spec}) suppressed ${indexCase.route}, observed the expected failure, then restored the real route: ${observedMessage}`,
    );
  } finally {
    tracker.dispose();
  }
}