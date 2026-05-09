import type { PublicKeyCredentialCreationOptionsJSON, PublicKeyCredentialRequestOptionsJSON, RegistrationResponseJSON, AuthenticationResponseJSON } from '@simplewebauthn/browser';
/**
 * Discriminated error class so callers can branch on cause without parsing
 * messages. The browser surfaces a {@link DOMException} for almost every
 * failure mode (NotAllowedError for cancellation or timeout, NotSupportedError
 * when no compatible authenticator is available, InvalidStateError when a
 * credential is already enrolled). We translate those into a single shape.
 */
export declare class PasskeyClientError extends Error {
    readonly kind: 'unsupported' | 'cancelled' | 'invalid_state' | 'security' | 'unknown';
    readonly cause?: unknown;
    constructor(kind: 'unsupported' | 'cancelled' | 'invalid_state' | 'security' | 'unknown', message: string, cause?: unknown);
}
export declare function isSupported(): boolean;
export declare function isAutofillSupported(): Promise<boolean>;
export declare function fetchRegistrationOptions(): Promise<PublicKeyCredentialCreationOptionsJSON>;
export declare function performRegistration(options: PublicKeyCredentialCreationOptionsJSON, useAutoRegister?: boolean): Promise<RegistrationResponseJSON>;
export declare function submitRegistration(credential: RegistrationResponseJSON, deviceName: string): Promise<{
    data: {
        id: string;
        attributes: Record<string, unknown>;
    };
}>;
export declare function fetchLoginOptions(): Promise<PublicKeyCredentialRequestOptionsJSON>;
export declare function performAuthentication(options: PublicKeyCredentialRequestOptionsJSON, useBrowserAutofill?: boolean): Promise<AuthenticationResponseJSON>;
export declare function submitLogin(credential: AuthenticationResponseJSON, remember?: boolean): Promise<{
    token: string;
    userId: number;
}>;
export declare function bulkRevoke(): Promise<void>;
