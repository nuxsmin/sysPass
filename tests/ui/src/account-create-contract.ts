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
  validateCreateDispatch as validateCreateDispatchImpl,
  validateCreateEnvelope as validateCreateEnvelopeImpl,
  validateCreatePersistence as validateCreatePersistenceImpl,
} from './account-create-contract.mjs';

export interface AccountCreateRequestRecord {
  method: string;
  url: string;
  headers?: Record<string, string>;
  body?: string | null;
}

export interface AccountCreateDispatchExpectation {
  name: string;
  login: string;
  clientId: number;
  categoryId: number;
  password: string;
}

export interface AccountCreateDispatchOptions {
  requests: AccountCreateRequestRecord[];
  expected: AccountCreateDispatchExpectation;
  route?: string;
}

export interface AccountCreatePersistenceOptions {
  markup: string;
  name: string;
  login: string;
}

export type AccountCreateFailure = {
  ok: false;
  code: string;
  message: string;
};

export type AccountCreateValidation = { ok: true } | AccountCreateFailure;

export type AccountCreateEnvelopeValidation =
  | { ok: true; itemId: number; nextAction: string }
  | AccountCreateFailure;

export function validateCreateDispatch(
  options: AccountCreateDispatchOptions,
): AccountCreateValidation {
  return validateCreateDispatchImpl(options) as AccountCreateValidation;
}

export function validateCreateEnvelope(
  envelope: unknown,
): AccountCreateEnvelopeValidation {
  return validateCreateEnvelopeImpl(envelope) as AccountCreateEnvelopeValidation;
}

export function validateCreatePersistence(
  options: AccountCreatePersistenceOptions,
): AccountCreateValidation {
  return validateCreatePersistenceImpl(options) as AccountCreateValidation;
}
