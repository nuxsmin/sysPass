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

export const TIER_WRITE_SPECS = ['accountCreate.write.spec.ts', 'accountEdit.write.spec.ts', 'accountDelete.write.spec.ts', 'accountListFiles.write.spec.ts', 'accountManagerDelete.write.spec.ts', 'authTokenDelete.write.spec.ts', 'itemMetadataDelete.write.spec.ts', 'notificationDelete.write.spec.ts', 'notificationSave.write.spec.ts', 'userGroupDelete.write.spec.ts', 'userDelete.write.spec.ts', 'userEditPass.write.spec.ts', 'userProfileDelete.write.spec.ts'];

export const TIER_DISCOVERY_SPECS = ['discovery.spec.ts'];

export const TIER_SPECS = {
  A: TIER_A_SPECS,
  B: TIER_B_SPECS,
  INSTALL: TIER_INSTALL_SPECS,
  WIRING: TIER_WIRING_SPECS,
  WRITE: TIER_WRITE_SPECS,
  DISCOVERY: TIER_DISCOVERY_SPECS,
};

/**
 * Normalize a caller-supplied tier token. Lower-case and surrounding whitespace
 * are accepted; anything outside the explicit A/B/INSTALL/WIRING/WRITE/DISCOVERY
 * selection is a usage error rather than an unknown "Tier Z" observation.
 *
 * @param {string | null | undefined} value
 * @returns {'A' | 'B' | 'INSTALL' | 'WIRING' | 'WRITE' | 'DISCOVERY'}
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
