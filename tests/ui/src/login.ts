import type { Page } from '@playwright/test';

import type { FixtureCredential } from './fixture';
import { loginThroughUiFlow } from './login-flow.mjs';

/**
 * Authenticate through the real login form exactly once per seeded suite.
 *
 * Returns the timestamp taken immediately before submitting the form. Callers
 * use it as the lower bound for shell actions that are issued after the login
 * redirect, so the anonymous login boot is not mistaken for the shell boot.
 */
export async function loginThroughUi(page: Page, credential: FixtureCredential): Promise<number> {
  return loginThroughUiFlow(page, credential);
}
