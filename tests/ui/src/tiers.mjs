/**
 * Explicit tier selections. A run without SP_UI_SPECS executes exactly one
 * tier's spec files, never "everything under specs/". Tier A stays the default.
 *
 * The installer is a separate, explicitly stateful selection. It never runs
 * with Tier A/B specs in the same Playwright invocation; a combined run is
 * orchestrated by scripts/ui-smoke.sh as seeded specs first, then the installer.
 */
export const TIER_A_SPECS = ['login.spec.ts', 'shell.spec.ts'];

export const TIER_B_SPECS = [
  'accessManager.spec.ts',
  'itemManager.spec.ts',
  'securityManager.spec.ts',
  'configManager.spec.ts',
  'notification.spec.ts',
  'userSettingsManager.spec.ts',
  'plugin.spec.ts',
];

export const TIER_INSTALL_SPECS = ['install.spec.ts'];

export const TIER_WIRING_SPECS = ['adminNavigation.wiring.spec.ts'];

export const TIER_SPECS = {
  A: TIER_A_SPECS,
  B: TIER_B_SPECS,
  INSTALL: TIER_INSTALL_SPECS,
  WIRING: TIER_WIRING_SPECS,
};

/**
 * Normalize a caller-supplied tier token. Lower-case and surrounding whitespace
 * are accepted; anything outside the explicit A/B/INSTALL/WIRING selection is a
 * usage error rather than an unknown "Tier Z" observation.
 *
 * @param {string | null | undefined} value
 * @returns {'A' | 'B' | 'INSTALL' | 'WIRING'}
 */
export function normalizeTier(value) {
  const normalized = String(value ?? 'A').trim().toUpperCase();

  if (!Object.hasOwn(TIER_SPECS, normalized)) {
    throw new Error(`Unknown UI smoke tier: ${String(value)}`);
  }

  return normalized;
}

/**
 * @param {string} tier
 * @returns {string[]}
 */
export function specsForTier(tier) {
  return TIER_SPECS[normalizeTier(tier)];
}
