import path from 'node:path';

import { test, expect } from '../src/session';
import { ActionTracker } from '../src/readiness';
import { navigateTierBIndex, openSeededShell, TIER_B_INDEX_CASES } from '../src/tier-b';

test('stabilize user details, password change dialogs and user preferences across viewports', async ({
  sessionPage: { page, context, session },
}) => {
  test.setTimeout(120_000);
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  const captureDir = process.env.SP_UI_REFINEMENT_CAPTURE_DIR;

  async function capture(name: string, label: string): Promise<void> {
    if (!captureDir) {
      return;
    }
    await page.screenshot({
      path: path.join(captureDir, `${process.env.SP_UI_REFINEMENT_CAPTURE_PREFIX ?? ''}${name}-${label}.png`),
      fullPage: true,
      animations: 'disabled',
      mask: [page.locator('input[type="password"]')],
    });
  }

  try {
    await openSeededShell(page, tracker, session);
    await navigateTierBIndex(page, tracker, TIER_B_INDEX_CASES.accessManager);

    const table = page.locator('#data-table-tblUsers');
    await expect(table).toBeVisible();

    // --- Phase A2: User Details modal ---
    const viewUser = table.getByRole('button', { name: 'View User Details' }).first();
    await viewUser.focus();
    await viewUser.click();

    const popup = page.locator('#box-popup');
    await expect(popup).toBeVisible();
    await expect(popup).toHaveAttribute('role', 'dialog');
    await expect(popup).toHaveAttribute('aria-modal', 'true');
    await expect(popup).toHaveAttribute('aria-labelledby', 'box-popup-title');

    // Verify neutral heading (not saturated indigo bar)
    const userDialogStyle = await popup.evaluate((el) => {
      const h2 = el.querySelector('#box-popup-title') as HTMLElement;
      const h2Style = getComputedStyle(h2);
      const boxStyle = getComputedStyle(el);
      const descField = el.querySelector('td.descField') as HTMLElement;
      const descStyle = getComputedStyle(descField);
      const nameInput = el.querySelector('#name') as HTMLElement;
      const nameStyle = getComputedStyle(nameInput);
      const closeButtons = el.querySelectorAll('.btn-popup-close');
      return {
        boxBg: boxStyle.backgroundColor,
        boxRadius: boxStyle.borderRadius,
        h2Bg: h2Style.backgroundColor,
        h2Color: h2Style.color,
        descBorderRightWidth: descStyle.borderRightWidth,
        nameBorderBottomColor: nameStyle.borderBottomColor,
        closeButtonCount: closeButtons.length,
        hasHorizontalOverflow: el.scrollWidth > el.clientWidth + 2,
      };
    });

    expect(userDialogStyle.boxBg).toBe('rgb(255, 255, 255)');
    expect(userDialogStyle.h2Bg).toBe('rgb(255, 255, 255)');
    expect(userDialogStyle.h2Color).toBe('rgb(38, 53, 68)');
    expect(userDialogStyle.descBorderRightWidth).toBe('0px');
    expect(userDialogStyle.nameBorderBottomColor).toBe('rgba(0, 0, 0, 0)');
    expect(userDialogStyle.closeButtonCount).toBe(1);

    // Verify tabs: User, Options, Details
    await expect(popup.locator('.mdl-tabs__tab', { hasText: 'User' })).toBeVisible();
    await expect(popup.locator('.mdl-tabs__tab', { hasText: 'Options' })).toBeVisible();
    await expect(popup.locator('.mdl-tabs__tab', { hasText: 'Details' })).toBeVisible();

    // Keyboard focus must remain inside the open modal in both directions.
    for (const key of ['Tab', 'Shift+Tab'] as const) {
      for (let i = 0; i < 12; i++) {
        await page.keyboard.press(key);
        expect(await popup.evaluate(el => el.contains(document.activeElement)), `Focus stays in user dialog after ${key}`).toBe(true);
      }
    }

    for (const [width, label] of [[1440, 'desktop'], [768, 'tablet'], [375, 'mobile']] as const) {
      await page.setViewportSize({ width, height: 900 });
      const fitsViewport = await popup.evaluate((el) => {
        const rect = el.getBoundingClientRect();
        return rect.left >= 0 && rect.right <= window.innerWidth + 1;
      });
      expect(fitsViewport, `User Details dialog fits ${label} (${width}px)`).toBe(true);
      await capture('user-details', label);
    }

    // Check short viewport (640px height)
    await page.setViewportSize({ width: 1440, height: 640 });
    await expect(popup.locator('.sp-dialog-footer .btn-popup-close')).toBeVisible();

    await page.setViewportSize({ width: 1440, height: 900 });
    await page.keyboard.press('Escape');
    await expect(popup).toHaveCount(0);

    // Focus restored to invoking button
    await expect(viewUser).toBeFocused();

    // --- Phase A3: Password Change modal ---
    const editPass = table.locator('[data-onclick="appMgmt/show"][data-action-route="user/editPass"]').first();
    await editPass.focus();
    await editPass.click();
    await expect(page.locator('#box-popup #frmUpdUsrPass')).toBeVisible();

    // Check neutral header, read-only context block, non-invalid initial password fields, and footer buttons
    const passDialogState = await popup.evaluate((el) => {
      const h2 = el.querySelector('#box-popup-title') as HTMLElement;
      const h2Style = getComputedStyle(h2);
      const contextItems = Array.from(el.querySelectorAll('.sp-dialog-context__item')).map((item) => ({
        label: item.querySelector('.sp-dialog-context__label')?.textContent?.trim(),
        value: item.querySelector('.sp-dialog-context__value')?.textContent?.trim(),
      }));
      const invalidFields = el.querySelectorAll('.mdl-textfield.is-invalid').length;
      const passInput = el.querySelector('input[name="password"]') as HTMLInputElement;
      const repeatInput = el.querySelector('input[name="password_repeat"]') as HTMLInputElement;
      const passLabel = el.querySelector(`td.descField label[for="${passInput.id}"]`);
      const repeatLabel = el.querySelector(`td.descField label[for="${repeatInput.id}"]`);
      const toggleBtn = el.querySelector('button.sp-password-toggle.showpass') as HTMLButtonElement;
      const menuBtn = el.querySelector('button[id^="menu-password-"]') as HTMLButtonElement;
      const cancelBtn = el.querySelector('.sp-dialog-footer .btn-popup-close') as HTMLButtonElement;
      const saveBtn = el.querySelector('.sp-dialog-footer button[form="frmUpdUsrPass"]') as HTMLButtonElement;
      return {
        h2Bg: h2Style.backgroundColor,
        h2Color: h2Style.color,
        contextItems,
        invalidFields,
        passAutocomplete: passInput?.getAttribute('autocomplete'),
        repeatAutocomplete: repeatInput?.getAttribute('autocomplete'),
        hasPassLabel: Boolean(passLabel),
        hasRepeatLabel: Boolean(repeatLabel),
        toggleAriaLabel: toggleBtn?.getAttribute('aria-label'),
        toggleAriaPressed: toggleBtn?.getAttribute('aria-pressed'),
        menuAriaLabel: menuBtn?.getAttribute('aria-label'),
        hasCancelBtn: Boolean(cancelBtn),
        hasSaveBtn: Boolean(saveBtn),
      };
    });

    expect(passDialogState.h2Bg).toBe('rgb(255, 255, 255)');
    expect(passDialogState.h2Color).toBe('rgb(38, 53, 68)');
    expect(passDialogState.contextItems.length).toBe(2);
    expect(passDialogState.invalidFields).toBe(0);
    expect(passDialogState.passAutocomplete).toBe('new-password');
    expect(passDialogState.repeatAutocomplete).toBe('new-password');
    expect(passDialogState.hasPassLabel).toBe(true);
    expect(passDialogState.hasRepeatLabel).toBe(true);
    expect(passDialogState.toggleAriaLabel).toBeTruthy();
    expect(passDialogState.toggleAriaPressed).toBe('false');
    expect(passDialogState.menuAriaLabel).toBeTruthy();
    expect(passDialogState.hasCancelBtn).toBe(true);
    expect(passDialogState.hasSaveBtn).toBe(true);

    for (const [width, label] of [[1440, 'desktop'], [768, 'tablet'], [375, 'mobile']] as const) {
      await page.setViewportSize({ width, height: 900 });
      const fitsViewport = await popup.evaluate((el) => {
        const rect = el.getBoundingClientRect();
        return rect.left >= 0 && rect.right <= window.innerWidth + 1;
      });
      expect(fitsViewport, `Password Change dialog fits ${label} (${width}px)`).toBe(true);
      await capture('password-change', label);
    }

    // Verify mismatch validation feedback and show/hide toggle
    await page.setViewportSize({ width: 1440, height: 900 });
    const passInput = popup.locator('input[name="password"]');
    const repeatInput = popup.locator('input[name="password_repeat"]');
    const matchError = popup.locator('.sp-password-match-error');

    await passInput.fill('ValidTestPass#1');
    await repeatInput.fill('DifferentPass#2');
    await expect(matchError).toBeVisible();
    await expect(matchError).toContainText('Passwords do not match');

    await repeatInput.fill('ValidTestPass#1');
    await expect(matchError).toBeHidden();

    // Dirty backdrop guard: clicking backdrop when form has unsaved password should NOT close dialog
    await page.evaluate(() => {
      const mfp = (window as unknown as { $?: { magnificPopup?: { instance?: { bgOverlay?: { trigger: (e: string) => void } } } } }).$?.magnificPopup?.instance;
      mfp?.bgOverlay?.trigger('click');
    });
    await expect(popup).toBeVisible();

    // Cancel button closes cleanly and restores focus
    await popup.locator('.sp-dialog-footer .btn-popup-close').click();
    await expect(popup).toHaveCount(0);
    await expect(editPass).toBeFocused();

    // --- Phase B: User Preferences ---
    await page.locator('#users-menu-lower-right').click();
    await navigateTierBIndex(page, tracker, TIER_B_INDEX_CASES.userSettingsManager);
    await expect(page.locator('#frmPreferences')).toBeVisible();

    const prefsState = await page.evaluate(() => {
      const tabsRoot = document.querySelector('#tabsHeader')?.closest('.mdl-tabs');
      const titleEl = document.querySelector('#content #title') as HTMLElement;
      const titleStyle = getComputedStyle(titleEl);
      const descField = document.querySelector('#tblSite td.descField') as HTMLElement;
      const descStyle = getComputedStyle(descField);
      const themeSelect = document.querySelector('#sel-usertheme') as HTMLSelectElement;
      const themeOption = themeSelect?.querySelector('option[value="material-blue"]');
      const selectizeItem = themeSelect?.parentElement?.querySelector('.selectize-input .item');
      const switches = Array.from(document.querySelectorAll('#tblSite .mdl-switch__input')).map((sw) => {
        const labelEl = sw.closest('label.mdl-switch') as HTMLElement;
        const rect = labelEl.getBoundingClientRect();
        return {
          id: sw.id,
          ariaLabel: sw.getAttribute('aria-label'),
          ariaDescribedBy: sw.getAttribute('aria-describedby'),
          hitHeight: rect.height,
          hitWidth: rect.width,
        };
      });
      return {
        hasSpConfigTabs: Boolean(tabsRoot?.classList.contains('sp-config-tabs')),
        titleBg: titleStyle.backgroundColor,
        titleColor: titleStyle.color,
        descBorderRightWidth: descStyle.borderRightWidth,
        themeOptionText: themeOption?.textContent?.trim(),
        selectizeText: selectizeItem?.textContent?.trim(),
        switches,
      };
    });

    expect(prefsState.hasSpConfigTabs).toBe(true);
    expect(prefsState.titleBg).toBe('rgba(0, 0, 0, 0)');
    expect(prefsState.titleColor).toBe('rgb(38, 53, 68)');
    expect(prefsState.descBorderRightWidth).toBe('0px');
    expect(prefsState.themeOptionText).toBe('Material Blue');
    expect(prefsState.selectizeText).toBe('Material Blue');
    expect(prefsState.switches.length).toBe(7);
    for (const sw of prefsState.switches) {
      expect(sw.ariaLabel, `Switch ${sw.id} has aria-label`).toBeTruthy();
      expect(sw.ariaDescribedBy, `Switch ${sw.id} has aria-describedby`).toBeTruthy();
      expect(sw.hitHeight, `Switch ${sw.id} hit target height >= 44`).toBeGreaterThanOrEqual(44);
      expect(sw.hitWidth, `Switch ${sw.id} hit target width >= 36`).toBeGreaterThanOrEqual(36);
    }

    // Keyboard toggle check on first switch
    const firstSwitch = page.locator('#account_link');
    const initialChecked = await firstSwitch.isChecked();
    await firstSwitch.focus();
    await page.keyboard.press('Space');
    expect(await firstSwitch.isChecked()).toBe(!initialChecked);
    await page.keyboard.press('Space');
    expect(await firstSwitch.isChecked()).toBe(initialChecked);

    // Check scrolling to final setting and action buttons without footer overlap
    for (const [width, height, label] of [
      [1440, 900, 'desktop'],
      [768, 900, 'tablet'],
      [375, 900, 'mobile'],
      [1440, 640, 'short'],
    ] as const) {
      await page.setViewportSize({ width, height });
      const noFooterOverlap = await page.evaluate(() => {
        const scroller = document.querySelector('main.mdl-layout__content') as HTMLElement | null;
        if (scroller) {
          scroller.scrollTop = scroller.scrollHeight;
        }
        window.scrollTo(0, document.body.scrollHeight);
        const lastSwitch = document.querySelector('#show_account_search_filters')?.closest('li');
        const footer = document.querySelector('footer');
        if (!lastSwitch || !footer) {
          return true;
        }
        const switchRect = lastSwitch.getBoundingClientRect();
        const footerRect = footer.getBoundingClientRect();
        return switchRect.bottom <= footerRect.top + 2;
      });
      expect(noFooterOverlap, `Last switch scrolls above footer on ${label}`).toBe(true);
      await page.evaluate(() => {
        const scroller = document.querySelector('main.mdl-layout__content') as HTMLElement | null;
        if (scroller) {
          scroller.scrollTop = 0;
        }
        window.scrollTo(0, 0);
      });
      if (label !== 'short') {
        await capture('user-preferences', label);
      }
    }

    // 200% zoom layout check
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.evaluate(() => { document.body.style.zoom = '2'; });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true);
    await page.evaluate(() => { document.body.style.zoom = ''; });

    // --- Notifications view and modal ---
    await navigateTierBIndex(page, tracker, TIER_B_INDEX_CASES.notification);
    await expect(page.locator('#content .datagrid-header-title')).toBeVisible();
    await expect(page.locator('#data-table-tblNotifications')).toBeVisible();

    const notifIndexState = await page.evaluate(() => {
      const titleEl = document.querySelector('#content .datagrid-header-title') as HTMLElement;
      const wrapperEl = titleEl?.closest('.sp-management-tabs');
      const titleStyle = getComputedStyle(titleEl);
      return {
        hasManagementWrapper: Boolean(wrapperEl),
        titleColor: titleStyle.color,
        titleBg: titleStyle.backgroundColor,
        titleFontWeight: titleStyle.fontWeight,
      };
    });
    expect(notifIndexState.hasManagementWrapper).toBe(true);
    expect(notifIndexState.titleColor).toBe('rgb(38, 53, 68)');
    expect(notifIndexState.titleFontWeight).toBe('600');
    await capture('notifications-index', 'desktop');

    const newNotifBtn = page.locator('button[data-onclick="notification/show"][data-action-route="notification/create"]');
    await expect(newNotifBtn).toBeVisible();
    await newNotifBtn.click();
    await expect(popup).toBeVisible();
    await expect(popup.locator('#frmNotices')).toBeVisible();

    const notifModalState = await popup.evaluate((el) => {
      const h2 = el.querySelector('h2') as HTMLElement;
      const h2Style = getComputedStyle(h2);
      const headerClose = h2.querySelector('.btn-popup-close');
      const cancelBtn = el.querySelector('.sp-dialog-footer .btn-popup-close');
      const saveBtn = el.querySelector('.sp-dialog-footer button[form="frmNotices"]');
      return {
        role: el.getAttribute('role'),
        ariaModal: el.getAttribute('aria-modal'),
        headingBg: h2Style.backgroundColor,
        headingColor: h2Style.color,
        hasHeaderClose: Boolean(headerClose),
        hasCancelBtn: Boolean(cancelBtn),
        hasSaveBtn: Boolean(saveBtn),
      };
    });
    expect(notifModalState.role).toBe('dialog');
    expect(notifModalState.ariaModal).toBe('true');
    expect(notifModalState.headingBg).toBe('rgb(255, 255, 255)');
    expect(notifModalState.headingColor).toBe('rgb(38, 53, 68)');
    expect(notifModalState.hasHeaderClose).toBe(false);
    expect(notifModalState.hasCancelBtn).toBe(true);
    expect(notifModalState.hasSaveBtn).toBe(true);
    await capture('notification-modal', 'desktop');

    await popup.locator('.sp-dialog-footer .btn-popup-close').click();
    await expect(popup).toHaveCount(0);

    // --- Help buttons overlay (#orrsDiag) ---
    await page.locator('.sp-brand-home').click();
    const helpBtn = page.locator('#help-account_search');
    await expect(helpBtn).toBeVisible();
    await helpBtn.focus();
    await page.keyboard.press('Enter');

    const helpDialog = page.locator('#orrsDiag_content');
    await expect(helpDialog).toBeVisible();

    const helpDialogState = await helpDialog.evaluate((el) => {
      const header = el.querySelector('header') as HTMLElement;
      const headerStyle = getComputedStyle(header);
      const posBtn = el.querySelector('#positive') as HTMLElement;
      return {
        role: el.getAttribute('role'),
        ariaModal: el.getAttribute('aria-modal'),
        ariaLabelledBy: el.getAttribute('aria-labelledby'),
        headerBg: headerStyle.backgroundColor,
        headerColor: headerStyle.color,
        headerFontWeight: headerStyle.fontWeight,
        hasPositiveBtn: Boolean(posBtn),
      };
    });
    expect(helpDialogState.role).toBe('dialog');
    expect(helpDialogState.ariaModal).toBe('true');
    expect(helpDialogState.ariaLabelledBy).toBe('orrsDiag_title');
    expect(helpDialogState.headerBg).toBe('rgba(0, 0, 0, 0)');
    expect(helpDialogState.headerColor).toBe('rgb(38, 53, 68)');
    expect(helpDialogState.headerFontWeight).toBe('600');
    expect(helpDialogState.hasPositiveBtn).toBe(true);
    await expect(helpDialog.locator('#positive')).toHaveText(/close/i);
    await capture('help-overlay', 'desktop');

    await helpDialog.locator('#positive').click();
    await expect(helpDialog).toHaveCount(0);
  } finally {
    tracker.dispose();
  }
});

