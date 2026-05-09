import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
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
    protected loading: boolean;
    view(): Mithril.Children;
    protected signIn(e: MouseEvent): void;
}
