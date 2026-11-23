import { mkdirSync, writeFileSync } from 'node:fs';
import path from 'node:path';

export const DISCOVERY_TIER = 'DISCOVERY';
export const DISCOVERY_SCHEMA_VERSION = 1;

export const DISCOVERY_CONTROL_ATTRIBUTES = Object.freeze([
  'data-onclick',
  'data-onsubmit',
  'data-action-route',
  'data-route',
  'data-view',
  'data-onchange',
]);

export const DISCOVERY_PAGE_CATALOG = Object.freeze({
  admin: [
    { name: 'shell-home', route: 'index' },
    { name: 'account-index', route: 'account/index' },
    { name: 'account-view', route: 'account/view/{accountId}' },
    { name: 'account-edit', route: 'account/edit/{accountId}' },
    { name: 'account-create', route: 'account/create' },
    { name: 'access-manager', route: 'accessManager/index' },
    { name: 'item-manager', route: 'itemManager/index' },
    { name: 'security-manager', route: 'securityManager/index' },
    { name: 'plugins', route: 'plugin/index' },
    { name: 'config-manager', route: 'configManager/index' },
    { name: 'user-settings', route: 'userSettingsManager/index' },
    { name: 'notifications', route: 'notification/index' },
  ],
  restricted: [
    { name: 'shell-home', route: 'index' },
    { name: 'account-index', route: 'account/index' },
    { name: 'account-view', route: 'account/view/{accountId}' },
  ],
});

function text(value) {
  return typeof value === 'string' ? value : '';
}

export function discoveryPagesFor(profile, { accountId } = {}) {
  const catalog = DISCOVERY_PAGE_CATALOG[profile];

  if (!catalog) {
    throw new Error(`Unknown discovery profile: ${String(profile)}`);
  }

  return catalog.map((page) => {
    if (page.route.includes('{accountId}')) {
      if (accountId === undefined || accountId === null || String(accountId) === '') {
        throw new Error(`Discovery route ${page.route} requires accountId`);
      }

      return { ...page, route: page.route.replace('{accountId}', String(accountId)) };
    }

    return { ...page };
  });
}

export function sanitizeControl(raw = {}) {
  const sourceAttributes = raw && typeof raw === 'object' ? raw.attributes : {};
  const attributes = {};

  for (const name of DISCOVERY_CONTROL_ATTRIBUTES) {
    const value = sourceAttributes?.[name];

    if (typeof value === 'string' && value !== '') {
      attributes[name] = value;
    }
  }

  return {
    tag: text(raw?.tag),
    id: text(raw?.id),
    name: text(raw?.name),
    form: text(raw?.form),
    attributes,
  };
}

export function sanitizePage(raw = {}) {
  const controls = Array.isArray(raw?.controls) ? raw.controls.map(sanitizeControl) : [];

  return {
    name: text(raw?.name),
    route: text(raw?.route),
    status: Number.isInteger(raw?.status) ? raw.status : null,
    controlCount: controls.length,
    controls,
  };
}

export function sanitizeInventory(raw = {}) {
  const profiles = {};

  for (const [profile, pages] of Object.entries(raw?.profiles ?? {})) {
    profiles[profile] = Array.isArray(pages) ? pages.map(sanitizePage) : [];
  }

  return {
    schemaVersion: DISCOVERY_SCHEMA_VERSION,
    tier: DISCOVERY_TIER,
    generatedAt: text(raw?.generatedAt),
    profiles,
  };
}

export function buildInventory(runs, { generatedAt = new Date().toISOString() } = {}) {
  const profiles = {};

  for (const run of Array.isArray(runs) ? runs : []) {
    profiles[text(run?.profile)] = Array.isArray(run?.pages) ? run.pages : [];
  }

  return sanitizeInventory({ generatedAt, profiles });
}

export function buildRunMetadata(inventory, { runId = '' } = {}) {
  const profiles = {};

  for (const [profile, pages] of Object.entries(inventory?.profiles ?? {})) {
    const statuses = {};
    let controls = 0;

    for (const page of pages) {
      controls += page.controlCount;
      const key = Number.isInteger(page.status) ? String(page.status) : 'unknown';
      statuses[key] = (statuses[key] ?? 0) + 1;
    }

    profiles[profile] = {
      pages: pages.length,
      controls,
      statuses,
    };
  }

  return {
    schemaVersion: DISCOVERY_SCHEMA_VERSION,
    runId: text(runId),
    tier: DISCOVERY_TIER,
    generatedAt: text(inventory?.generatedAt),
    profiles,
  };
}

export function writeDiscoveryEvidence(outputDir, { inventory, metadata }) {
  mkdirSync(outputDir, { recursive: true });

  const inventoryPath = path.join(outputDir, 'inventory.json');
  const metadataPath = path.join(outputDir, 'run-metadata.json');

  writeFileSync(inventoryPath, `${JSON.stringify(inventory, null, 2)}\n`);
  writeFileSync(metadataPath, `${JSON.stringify(metadata, null, 2)}\n`);

  return { inventoryPath, metadataPath };
}
