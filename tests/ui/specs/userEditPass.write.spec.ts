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

import { FIXTURE } from '../src/fixture';
import { loginThroughUi } from '../src/login';
import { ActionTracker, parseActionRoute } from '../src/readiness';
import { expect, test } from '../src/session';
import { runTierBIndex, TIER_B_INDEX_CASES } from '../src/tier-b';

interface LoginAttemptResponse {
  status?: string;
  description?: string;
}

async function submitLoginAttempt(
  page: import('@playwright/test').Page,
  login: string,
  password: string,
): Promise<LoginAttemptResponse> {
  const bootResponse = page.waitForResponse(
    (response) => parseActionRoute(response.url()) === 'bootstrap/getEnvironment',
    { timeout: 15_000 },
  );
  const loginResponse = page.waitForResponse(
    (response) =>
      response.request().method() === 'POST' && parseActionRoute(response.url()) === 'login/login',
    { timeout: 15_000 },
  );

  await page.goto('/index.php?r=login/index', { waitUntil: 'domcontentloaded' });
  await page.locator('#box-login').waitFor({ state: 'visible', timeout: 15_000 });

  const boot = await bootResponse;
  expect(boot.status()).toBe(200);

  await page.waitForFunction(
    "typeof sysPassApp !== 'undefined' && sysPassApp.config && sysPassApp.config.PKI && sysPassApp.config.PKI.AVAILABLE === true",
    undefined,
    { timeout: 15_000 },
  );

  await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
  await page.locator('#user').fill(login);
  await page.locator('#pass').fill(password);
  await page.locator('#pass').press('Tab');

  await page.locator('#btnLogin').click();
  const response = await loginResponse;
  expect(response.status()).toBe(200);

  return (await response.json()) as LoginAttemptResponse;
}

test('admin user password save changes a disposable user password and closes the popup', async ({
  sessionPage: { page, context, session },
  isolatedPage,
}) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const suffix = String(Date.now());
  const disposableLogin = `e2e_editpass_${suffix}`;
  const disposableName = `Synthetic EditPass ${suffix}`;
  const disposableEmail = `${disposableLogin}@example.invalid`;
  const oldPassword = `OldPass-${suffix}-aB1!`;
  const newPassword = `NewPass-${suffix}-cD2@`;
  let createdId: string | null = null;
  let security: { sk: string; csrf: string } | null = null;

  try {
    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.accessManager);
    await page.waitForFunction(
      "typeof sysPassApp !== 'undefined' && sysPassApp.config && sysPassApp.config.PKI && sysPassApp.config.PKI.AVAILABLE === true",
      undefined,
      { timeout: 15_000 },
    );

    security = await page.evaluate(() => {
      const app = (0, eval)('sysPassApp');
      return { sk: String(app.sk.get()), csrf: String(app.config.CSRF) };
    });

    const encryptedOldPassword = await page.evaluate(
      (secret) => String((0, eval)('sysPassApp').config.PKI.CRYPTO.encrypt(secret) || ''),
      oldPassword,
    );
    expect(Boolean(encryptedOldPassword) && encryptedOldPassword !== oldPassword).toBe(true);

    const createResponse = await page.request.post('/index.php?r=user/saveCreate', {
      headers: { 'X-CSRF': security.csrf },
      form: {
        name: disposableName,
        login: disposableLogin,
        email: disposableEmail,
        usergroup_id: String(FIXTURE.ids.groups.restricted),
        userprofile_id: String(FIXTURE.ids.profiles.restricted),
        isLdap: '0',
        password: encryptedOldPassword,
        password_repeat: encryptedOldPassword,
        sk: security.sk,
        isAjax: '1',
      },
    });
    expect(createResponse.status()).toBe(200);
    const createBody = (await createResponse.json()) as { status?: string };
    expect(createBody.status).toBe('OK');

    await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.accessManager);
    const grid = page.locator('#data-table-tblUsers');
    const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
    const panelId = await panel.getAttribute('id');
    await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();

    const row = grid.locator('tr', { hasText: disposableLogin });
    await expect(row).toHaveCount(1);
    const id = await row.getAttribute('data-item-id');
    expect(id).toMatch(/^\d+$/);
    createdId = id;

    const saveRoute = `user/saveEditPass/${createdId}`;
    const control = row.locator('[data-onclick="appMgmt/show"][data-action-route="user/editPass"]');
    await expect(control).toHaveAttribute('data-item-id', createdId!);

    const open = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        new URL(response.url()).searchParams.get('r') === `user/editPass/${createdId}`,
    );
    await control.click();
    expect((await open).status()).toBe(200);

    const form = page.locator('#frmUpdUsrPass');
    await expect(form).toBeVisible();
    await expect(form).toHaveAttribute('data-onsubmit', 'user/savePassword');
    await expect(form).toHaveAttribute('data-action-route', saveRoute);

    await form.locator('[name="password"]').fill(newPassword);
    await form.locator('[name="password"]').press('Tab');
    await form.locator('[name="password_repeat"]').fill(newPassword);
    await form.locator('[name="password_repeat"]').press('Tab');

    await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
    const responsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'POST' &&
        new URL(response.url()).searchParams.get('r') === saveRoute,
    );
    await page.locator('button[form="frmUpdUsrPass"]').click();
    const response = await responsePromise;
    expect(response.status()).toBe(200);

    const fields = new URLSearchParams(response.request().postData() ?? '');
    // Compare booleans only: neither credentials nor encrypted values belong in reports.
    for (const field of ['password', 'password_repeat']) {
      const value = fields.get(field);
      expect(Boolean(value) && value !== newPassword, `${field} must be encrypted`).toBe(true);
    }

    const envelope = (await response.json()) as { status?: string; description?: string };
    expect(envelope.status).toBe('OK');
    expect(typeof envelope.description === 'string' && envelope.description.length > 0).toBe(true);

    await expect(page.locator('.toast-success').filter({ hasText: envelope.description! })).toBeVisible();
    await expect(page.locator('.toast-error')).toHaveCount(0);
    await expect(page.locator('.toast-warning')).toHaveCount(0);
    await expect(form).toHaveCount(0);
    await expect(page.locator('#box-popup')).toHaveCount(0);

    // Independent authentication checks in an isolated context:
    // 1. Old password is rejected by DatabaseAuth ('Wrong login').
    const oldLoginResult = await submitLoginAttempt(isolatedPage.page, disposableLogin, oldPassword);
    expect(oldLoginResult.status).toBe('ERROR');
    expect(oldLoginResult.description).toBe('Wrong login');
    await expect(isolatedPage.page.locator('.toast-error').filter({ hasText: 'Wrong login' })).toBeVisible();

    // 2. New password passes DatabaseAuth and LoginUser, stopping only at LoginMasterPass::loadCurrent (NotSet)
    // because user/saveCreate does not seed mPass/mKey and LoginController flattens LoginStatus codes to ERROR.
    const newLoginResult = await submitLoginAttempt(isolatedPage.page, disposableLogin, newPassword);
    expect(newLoginResult.status).toBe('ERROR');
    expect(newLoginResult.description).toBe('The Master Password either is not saved or is wrong');
    await expect(
      isolatedPage.page
        .locator('.toast-error')
        .filter({ hasText: 'The Master Password either is not saved or is wrong' }),
    ).toBeVisible();

    const cleanupResponse = await page.request.get(
      `/index.php?r=user/delete/${createdId}&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
    );
    expect(cleanupResponse.status()).toBe(200);
    const cleanupBody = (await cleanupResponse.json()) as { status?: string };
    expect(cleanupBody.status).toBe('OK');
    createdId = null;

    const verifyDeleted = await page.request.get(
      `/index.php?r=user/search&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
    );
    expect(verifyDeleted.status()).toBe(200);
    expect(await verifyDeleted.text()).not.toContain(disposableLogin);
  } finally {
    if (createdId && security) {
      await page.request.get(
        `/index.php?r=user/delete/${createdId}&sk=${encodeURIComponent(security.sk)}&isAjax=1`,
      );
    }
    tracker.dispose();
  }
});

for (const outcome of ['ERROR', 'WARNING', 'transport'] as const) {
  test(`admin user password save reconciles ${outcome} outcome and preserves the existing credential`, async ({
    sessionPage: { page, context, session },
    isolatedPage,
  }) => {
    const tracker = new ActionTracker(page, context, { sessionEstablished: false });
    const id = String(FIXTURE.ids.users.restricted);
    const saveRoute = `user/saveEditPass/${id}`;
    const message = `Synthetic user password ${outcome}`;
    const envelope = { status: outcome, description: message, messages: [], data: null };
    const candidatePassword = `Synthetic-${Date.now()}-aB1!`;

    try {
      await runTierBIndex(page, tracker, session, TIER_B_INDEX_CASES.accessManager);
      await page.waitForFunction(
        "typeof sysPassApp !== 'undefined' && sysPassApp.config && sysPassApp.config.PKI && sysPassApp.config.PKI.AVAILABLE === true",
        undefined,
        { timeout: 15_000 },
      );

      const grid = page.locator('#data-table-tblUsers');
      const panel = grid.locator('xpath=ancestor::*[starts-with(@id,"tabs-")][1]');
      const panelId = await panel.getAttribute('id');
      await page.locator(`#tabsHeader a[href="#${panelId}"]`).click();

      const control = grid.locator(
        `tr[data-item-id="${id}"] [data-onclick="appMgmt/show"][data-action-route="user/editPass"]`,
      );
      await expect(control).toHaveAttribute('data-item-id', id);

      const open = page.waitForResponse(
        (response) =>
          response.request().method() === 'GET' &&
          new URL(response.url()).searchParams.get('r') === `user/editPass/${id}`,
      );
      await control.click();
      expect((await open).status()).toBe(200);

      const form = page.locator('#frmUpdUsrPass');
      await expect(form).toBeVisible();
      await expect(form).toHaveAttribute('data-onsubmit', 'user/savePassword');
      await expect(form).toHaveAttribute('data-action-route', saveRoute);

      await form.locator('[name="password"]').fill(candidatePassword);
      await form.locator('[name="password"]').press('Tab');
      await form.locator('[name="password_repeat"]').fill(candidatePassword);
      await form.locator('[name="password_repeat"]').press('Tab');

      await page.route('**/index.php?**', async (route) => {
        const request = route.request();
        if (request.method() === 'POST' && new URL(request.url()).searchParams.get('r') === saveRoute) {
          if (outcome === 'transport') {
            await route.abort('failed');
          } else {
            await route.fulfill({
              status: 200,
              contentType: 'application/json',
              body: JSON.stringify(envelope),
            });
          }
        } else {
          await route.continue();
        }
      });

      await page.locator('#toast-container').evaluateAll((nodes) => nodes.forEach((node) => node.remove()));
      const requestPromise = page.waitForRequest(
        (request) =>
          request.method() === 'POST' && new URL(request.url()).searchParams.get('r') === saveRoute,
      );
      const responsePromise =
        outcome === 'transport'
          ? null
          : page.waitForResponse(
              (response) =>
                response.request().method() === 'POST' &&
                new URL(response.url()).searchParams.get('r') === saveRoute,
            );

      await page.locator('button[form="frmUpdUsrPass"]').click();
      const request = await requestPromise;
      const fields = new URLSearchParams(request.postData() ?? '');
      // Compare booleans only: neither credentials nor encrypted values belong in reports.
      for (const field of ['password', 'password_repeat']) {
        const value = fields.get(field);
        expect(Boolean(value) && value !== candidatePassword, `${field} must be encrypted`).toBe(true);
      }

      if (responsePromise) {
        const response = await responsePromise;
        expect(response.status()).toBe(200);
        expect(await response.json()).toEqual(envelope);
        await expect(
          page
            .locator(outcome === 'ERROR' ? '.toast-error' : '.toast-warning')
            .filter({ hasText: message }),
        ).toBeVisible();
      } else {
        await expect(page.locator('.toast-error')).toBeVisible();
      }

      await expect(page.locator('.toast-success')).toHaveCount(0);
      await expect(form).toBeVisible();

      // An independent session can still authenticate with the seeded restricted fixture credential.
      await loginThroughUi(isolatedPage.page, FIXTURE.credentials.restricted);
    } finally {
      await page.unroute('**/index.php?**');
      tracker.dispose();
    }
  });
}
