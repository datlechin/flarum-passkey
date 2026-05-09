import app from 'flarum/forum/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import type Mithril from 'mithril';
import {
  fetchLoginOptions,
  performAuthentication,
  submitLogin,
  isSupported,
  PasskeyClientError,
} from '../lib/webauthn';

export interface IPasskeyLoginButtonAttrs extends ComponentAttrs {
  className?: string;
}

/**
 * Sign-in button surfaced in the login modal.
 *
 * On click, fetches assertion options from the server, runs the browser
 * ceremony, then posts the result back to /api/passkey/login. A success
 * triggers a full page reload (mirroring the native password login flow in
 * Flarum core) so the freshly minted session cookie is picked up by every
 * subsequent request without the SPA having to mutate its own auth state.
 */
export default class PasskeyLoginButton extends Component<IPasskeyLoginButtonAttrs> {
  protected loading = false;

  view(): Mithril.Children {
    if (!isSupported()) {
      return null;
    }

    // Reuse the `LogInButton` class from core so the passkey button picks up
    // the same width, icon, and spacing treatment as OAuth provider buttons
    // (GitHub, Google, etc.) without copying their popup onclick handler.
    return (
      <Button
        type="button"
        className={`Button LogInButton ${this.attrs.className ?? ''}`}
        icon="fas fa-key"
        loading={this.loading}
        onclick={this.signIn.bind(this)}
      >
        {app.translator.trans('datlechin-passkey.forum.log_in.sign_in_with_passkey')}
      </Button>
    );
  }

  protected signIn(e: MouseEvent): void {
    e.preventDefault();

    this.loading = true;
    m.redraw();

    (async () => {
      const options = await fetchLoginOptions();
      const credential = await performAuthentication(options, false);
      await submitLogin(credential, true);
    })()
      .then(() => {
        window.location.reload();
      })
      .catch((err) => {
        this.loading = false;
        m.redraw();

        if (err instanceof PasskeyClientError && err.kind === 'cancelled') {
          return;
        }

        // Server-side rejections (401 unknown credential, 429 throttled,
        // 500 verification failed) ride Flarum's default request handler;
        // surfacing our own toast here would double up.
        if (!(err instanceof PasskeyClientError)) {
          return;
        }

        const messageByKind: Record<string, string> = {
          unsupported: 'datlechin-passkey.forum.log_in.unsupported',
          security: 'datlechin-passkey.forum.log_in.security_error',
        };
        const messageKey = messageByKind[err.kind] ?? 'datlechin-passkey.forum.log_in.failed';

        app.alerts.show({ type: 'error' }, app.translator.trans(messageKey));
      });
  }
}
