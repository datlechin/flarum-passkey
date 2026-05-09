/**
 * Wires up Conditional UI ("autofill") for passkey sign-in.
 *
 * Browsers expose saved passkeys as autofill suggestions on any input whose
 * `autocomplete` attribute contains the `webauthn` token, but only while a
 * `navigator.credentials.get({ mediation: 'conditional' })` call is in flight.
 *
 * Patches `LogInModal.prototype.oncreate` so the wiring happens at the exact
 * lifecycle point the input lands in the DOM. Failure modes (no platform
 * support, no registered credential) are silent: the password form stays
 * functional and the user never sees an error from this code path.
 */
export default function setupConditionalUi(): void;
