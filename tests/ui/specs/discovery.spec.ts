import { test } from '../src/session';

import {
  DISCOVERY_CONTROL_ATTRIBUTES,
  buildInventory,
  buildRunMetadata,
  discoveryPagesFor,
  writeDiscoveryEvidence,
} from '../src/discovery.mjs';
import {
  ADMIN_CREDENTIALS,
  FIXTURE,
  RESTRICTED_CREDENTIALS,
} from '../src/fixture';
import { loginThroughUi } from '../src/login';

interface DiscoveryControl {
  tag: string;
  id: string;
  name: string;
  form: string;
  attributes: Record<string, string>;
}

interface DiscoveryPage {
  name: string;
  route: string;
  status: number | null;
  controls: DiscoveryControl[];
}

interface DiscoveryRun {
  profile: string;
  pages: DiscoveryPage[];
}

const runs: DiscoveryRun[] = [];
const INTERACTIVE_TAGS = new Set(['a', 'button', 'form', 'input', 'select', 'textarea']);

async function collectControls(page: import('@playwright/test').Page): Promise<DiscoveryControl[]> {
  return page.evaluate(
    ({ attributes, interactiveTags }) => {
      const tags = new Set<string>(interactiveTags);

      return Array.from(document.querySelectorAll('*'))
        .map((element) => {
          const stableAttributes: Record<string, string> = {};

          for (const name of attributes) {
            const value = element.getAttribute(name);

            if (value) {
              stableAttributes[name] = value;
            }
          }

          const tag = element.tagName.toLowerCase();
          const id = element.id ?? '';
          const name = element.getAttribute('name') ?? '';
          const form = element.closest('form')?.getAttribute('name') ?? '';

          return { tag, id, name, form, attributes: stableAttributes };
        })
        .filter(
          (control) =>
            Object.keys(control.attributes).length > 0 ||
            (tags.has(control.tag) && (control.id !== '' || control.name !== '')),
        );
    },
    {
      attributes: DISCOVERY_CONTROL_ATTRIBUTES,
      interactiveTags: [...INTERACTIVE_TAGS],
    },
  );
}

async function discoverProfile(
  profile: 'admin' | 'restricted',
  page: import('@playwright/test').Page,
): Promise<void> {
  const credential = profile === 'admin' ? ADMIN_CREDENTIALS : RESTRICTED_CREDENTIALS;
  const pages = discoveryPagesFor(profile, { accountId: FIXTURE.ids.accounts.child });

  await loginThroughUi(page, credential);

  const discovered: DiscoveryPage[] = [];

  for (const pageDefinition of pages) {
    let status: number | null = null;
    let controls: DiscoveryControl[] = [];

    try {
      const response = await page.goto(`/index.php?r=${pageDefinition.route}`, {
        waitUntil: 'domcontentloaded',
      });
      status = response?.status() ?? null;

      await page.waitForLoadState('networkidle', { timeout: 5_000 }).catch(() => undefined);

      if (status !== null && status >= 200 && status < 400) {
        controls = await collectControls(page);
      }
    } catch {
      status = null;
    }

    discovered.push({
      name: pageDefinition.name,
      route: pageDefinition.route,
      status,
      controls,
    });
  }

  runs.push({ profile, pages: discovered });
}

test('inventory discovery browses representative pages as the administrator', async ({
  isolatedPage: { page },
}) => {
  await discoverProfile('admin', page);
});

test('inventory discovery browses only viewable pages as the restricted user', async ({
  isolatedPage: { page },
}) => {
  await discoverProfile('restricted', page);
});

test.afterAll(() => {
  const outputDir =
    process.env.SP_UI_DISCOVERY_OUTPUT_DIR ??
    `${process.env.SP_UI_OUTPUT_DIR ?? 'test-results'}/discovery`;
  const inventory = buildInventory(runs);
  const metadata = buildRunMetadata(inventory, { runId: process.env.SP_UI_RUN_ID ?? '' });

  writeDiscoveryEvidence(outputDir, { inventory, metadata });
});
