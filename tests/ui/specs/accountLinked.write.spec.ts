// Copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
import { FIXTURE } from '../src/fixture';
import { ActionTracker } from '../src/readiness';
import { expect, test } from '../src/session';
import { openSeededShell } from '../src/tier-b';

test('linked account identifies its password source and hides password editing', async ({ sessionPage: { page, context, session } }) => {
  const tracker = new ActionTracker(page, context, { sessionEstablished: false });
  try {
    await openSeededShell(page, tracker, session);
    await page.goto(`/index.php?r=account/view/${FIXTURE.ids.accounts.child}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('.item-actions [data-onclick="account/editPass"]')).toHaveCount(0);
    await expect(page.locator('#password-source-account')).toHaveText(FIXTURE.names.parentAccount);
  } finally {
    tracker.dispose();
  }
});
