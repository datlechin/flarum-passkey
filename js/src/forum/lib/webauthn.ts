import {
  startAuthentication,
  startRegistration,
  browserSupportsWebAuthn,
  browserSupportsWebAuthnAutofill,
} from '@simplewebauthn/browser';
import type {
  PublicKeyCredentialCreationOptionsJSON,
  PublicKeyCredentialRequestOptionsJSON,
  RegistrationResponseJSON,
  AuthenticationResponseJSON,
} from '@simplewebauthn/browser';
import app from 'flarum/forum/app';

/**
 * Discriminated error class so callers can branch on cause without parsing
 * messages. The browser surfaces a {@link DOMException} for almost every
 * failure mode (NotAllowedError for cancellation or timeout, NotSupportedError
 * when no compatible authenticator is available, InvalidStateError when a
 * credential is already enrolled). We translate those into a single shape.
 */
export class PasskeyClientError extends Error {
  constructor(
    public readonly kind:
      | 'unsupported'
      | 'cancelled'
      | 'invalid_state'
      | 'security'
      | 'unknown',
    message: string,
    public readonly cause?: unknown
  ) {
    super(message);
    this.name = 'PasskeyClientError';
  }
}

export function isSupported(): boolean {
  return browserSupportsWebAuthn();
}

export async function isAutofillSupported(): Promise<boolean> {
  try {
    return await browserSupportsWebAuthnAutofill();
  } catch {
    return false;
  }
}

export async function fetchRegistrationOptions(): Promise<PublicKeyCredentialCreationOptionsJSON> {
  return app.request<PublicKeyCredentialCreationOptionsJSON>({
    method: 'GET',
    url: app.forum.attribute('apiUrl') + '/passkey/registration-options',
  });
}

export async function performRegistration(
  options: PublicKeyCredentialCreationOptionsJSON,
  useAutoRegister = false
): Promise<RegistrationResponseJSON> {
  try {
    return await startRegistration({ optionsJSON: options, useAutoRegister });
  } catch (err) {
    throw mapWebAuthnError(err);
  }
}

export async function submitRegistration(
  credential: RegistrationResponseJSON,
  deviceName: string
): Promise<{ data: { id: string; attributes: Record<string, unknown> } }> {
  return app.request({
    method: 'POST',
    url: app.forum.attribute('apiUrl') + '/passkey/registration',
    body: { credential, deviceName },
  });
}

export async function fetchLoginOptions(): Promise<PublicKeyCredentialRequestOptionsJSON> {
  return app.request<PublicKeyCredentialRequestOptionsJSON>({
    method: 'GET',
    url: app.forum.attribute('apiUrl') + '/passkey/login-options',
  });
}

export async function performAuthentication(
  options: PublicKeyCredentialRequestOptionsJSON,
  useBrowserAutofill = false
): Promise<AuthenticationResponseJSON> {
  try {
    return await startAuthentication({ optionsJSON: options, useBrowserAutofill });
  } catch (err) {
    throw mapWebAuthnError(err);
  }
}

export async function submitLogin(
  credential: AuthenticationResponseJSON,
  remember = true
): Promise<{ token: string; userId: number }> {
  return app.request({
    method: 'POST',
    url: app.forum.attribute('apiUrl') + '/passkey/login',
    body: { credential, remember },
  });
}

export async function bulkRevoke(): Promise<void> {
  await app.request({
    method: 'DELETE',
    url: app.forum.attribute('apiUrl') + '/passkey/bulk-revoke',
  });
}

function mapWebAuthnError(err: unknown): PasskeyClientError {
  if (!(err instanceof Error)) {
    return new PasskeyClientError('unknown', 'Unknown error', err);
  }

  // The conditional-mediation flow can also throw an AbortError when a fresh
  // ceremony cancels the in-flight one; treat as a normal cancellation.
  if (err.name === 'NotAllowedError' || err.name === 'AbortError') {
    return new PasskeyClientError('cancelled', err.message, err);
  }
  if (err.name === 'InvalidStateError') {
    return new PasskeyClientError('invalid_state', err.message, err);
  }
  if (err.name === 'SecurityError') {
    return new PasskeyClientError('security', err.message, err);
  }
  if (err.name === 'NotSupportedError') {
    return new PasskeyClientError('unsupported', err.message, err);
  }

  return new PasskeyClientError('unknown', err.message, err);
}
