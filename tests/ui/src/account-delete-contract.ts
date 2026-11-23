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

import {
  validateDeleteAbsence as validateDeleteAbsenceImpl,
  validateDeleteDispatch as validateDeleteDispatchImpl,
  validateDeleteEnvelope as validateDeleteEnvelopeImpl,
} from './account-delete-contract.mjs';

export interface AccountDeleteRequestRecord {
  method: string;
  url: string;
  headers?: Record<string, string>;
  body?: string | null;
}

export interface AccountDeleteDispatchOptions {
  requests: AccountDeleteRequestRecord[];
  itemId: number;
}

export interface AccountDeleteAbsenceOptions {
  envelope: unknown;
  name: string;
  itemId: number;
}

export type AccountDeleteFailure = {
  ok: false;
  code: string;
  message: string;
};

export type AccountDeleteValidation = { ok: true } | AccountDeleteFailure;

export function validateDeleteDispatch(
  options: AccountDeleteDispatchOptions,
): AccountDeleteValidation {
  return validateDeleteDispatchImpl(options) as AccountDeleteValidation;
}

export function validateDeleteEnvelope(envelope: unknown): AccountDeleteValidation {
  return validateDeleteEnvelopeImpl(envelope) as AccountDeleteValidation;
}

export function validateDeleteAbsence(
  options: AccountDeleteAbsenceOptions,
): AccountDeleteValidation {
  return validateDeleteAbsenceImpl(options) as AccountDeleteValidation;
}
