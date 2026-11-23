import assert from 'node:assert/strict';
import test from 'node:test';

import { describeCondition, unmetConditions, waitForConditions } from './contract.mjs';

function probeFor({ visible = {}, counts = {}, text = {}, attributes = {} } = {}) {
  return {
    isVisible: async (selector) => visible[selector] ?? false,
    count: async (selector) => counts[selector] ?? 0,
    text: async (selector) => text[selector] ?? '',
    attribute: async (selector, name) => attributes[`${selector}@${name}`] ?? null,
  };
}

test('contract conditions cover visible, hidden, count, text, class and attribute states', async () => {
  const probe = probeFor({
    visible: { '#content': true, '#wrap-loading': false },
    counts: { '#search-rows .account-label': 2 },
    text: { '#content': 'Parent Account' },
    attributes: {
      'body@class': 'main',
      '#frmLogin@data-route': 'login/login',
      '#content@data-page': 'search',
    },
  });

  const problems = await unmetConditions(
    [
      { kind: 'visible', selector: '#content' },
      { kind: 'hidden', selector: '#wrap-loading' },
      { kind: 'count', selector: '#search-rows .account-label', min: 2 },
      { kind: 'text', selector: '#content', contains: 'Parent Account' },
      { kind: 'class', selector: 'body', name: 'main' },
      { kind: 'attribute', selector: '#frmLogin', name: 'data-route', value: 'login/login' },
      { kind: 'attribute', selector: '#content', name: 'data-page' },
    ],
    probe,
  );

  assert.deepEqual(problems, []);
});

test('missing content and a stuck overlay report the failing selector', async () => {
  const probe = probeFor({
    visible: { '#content': true, '#wrap-loading': true },
    counts: { '#search-rows .account-label': 0 },
    text: { '#content': 'Still loading' },
  });

  const problems = await unmetConditions(
    [
      { kind: 'count', selector: '#search-rows .account-label', min: 2, label: 'account rows' },
      { kind: 'text', selector: '#content', contains: 'Parent Account' },
      { kind: 'hidden', selector: '#wrap-loading' },
    ],
    probe,
  );

  assert.deepEqual(problems, [
    'account rows',
    '#content must contain "Parent Account"',
    '#wrap-loading must be hidden',
  ]);
});

test('waitForConditions polls until satisfied and reports unmet conditions on timeout', async () => {
  let visible = false;
  const probe = {
    ...probeFor(),
    isVisible: async () => visible,
  };

  const pending = waitForConditions([{ kind: 'visible', selector: '#content' }], probe, Date.now() + 500);
  setTimeout(() => {
    visible = true;
  }, 20);

  await pending;

  await assert.rejects(
    () =>
      waitForConditions(
        [{ kind: 'visible', selector: '#content' }],
        { ...probe, isVisible: async () => false },
        Date.now() + 40,
      ),
    /#content must be visible/,
  );
});

test('describeCondition keeps the label when one is supplied', () => {
  assert.equal(describeCondition({ kind: 'visible', selector: '#x', label: 'custom label' }), 'custom label');
});
