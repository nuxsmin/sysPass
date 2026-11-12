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
