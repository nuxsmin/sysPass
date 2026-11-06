/**
 * Explicit tier selections. A run without SP_UI_SPECS executes exactly one
 * tier's spec files, never "everything under specs/". Tier A stays the default.
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

export const TIER_SPECS = {
  A: TIER_A_SPECS,
  B: TIER_B_SPECS,
};

/**
 * Normalize a caller-supplied tier token. Lower-case and surrounding whitespace
 * are accepted; anything outside the explicit A/B selection is a usage error
 * rather than an unknown "Tier Z" observation.
 *
 * @param {string | null | undefined} value
 * @returns {'A' | 'B'}
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