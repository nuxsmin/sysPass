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
