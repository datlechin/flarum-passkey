import { extend } from 'flarum/common/extend';
import type Mithril from 'mithril';
import {
  fetchLoginOptions,
  performAuthentication,
  submitLogin,
  isAutofillSupported,
  PasskeyClientError,
} from '../lib/webauthn';

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
export default function setupConditionalUi(): void {
  extend(
    'flarum/forum/components/LogInModal',
    'oncreate',
    function (_returnValue: void, ...args: unknown[]) {
      const vnode = args[0] as Mithril.VnodeDOM;
      const input = (vnode.dom as HTMLElement).querySelector<HTMLInputElement>(
        'input[name="identification"]'
      );
      if (!input) return;

      attach(input).catch(() => {});
    }
  );
}

async function attach(input: HTMLInputElement): Promise<void> {
  if (!(await isAutofillSupported())) return;

  // Preserve any existing autocomplete tokens (Flarum sets `username` on this
  // field) and append `webauthn` so the picker has somewhere to anchor.
  const existing = (input.getAttribute('autocomplete') ?? '').split(/\s+/).filter(Boolean);
  if (!existing.includes('webauthn')) existing.push('webauthn');
  input.setAttribute('autocomplete', existing.join(' '));

  const options = await fetchLoginOptions();

  try {
    const credential = await performAuthentication(options, true);
    await submitLogin(credential, true);
    window.location.reload();
  } catch (err) {
    if (err instanceof PasskeyClientError && err.kind === 'cancelled') return;
    // Any other failure stays silent.
  }
}
