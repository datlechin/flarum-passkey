import { extend } from 'flarum/common/extend';
import type LogInButtons from 'flarum/forum/components/LogInButtons';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import PasskeyLoginButton from '../components/PasskeyLoginButton';

/**
 * Add a "Sign in with passkey" entry to the canonical alternative-sign-in
 * surface (`LogInButtons`), the same hook OAuth providers like
 * `flarum/auth-github` use. The buttons render above the username/password
 * form in both the login and sign-up modals.
 *
 * `LogInButtons` is lazy-loaded by core, so the prototype hook is registered
 * via the string-path form of {@link extend} which defers patching until the
 * module is registered.
 */
export default function extendLogInModal(): void {
  extend('flarum/forum/components/LogInButtons', 'items', function (this: LogInButtons, items: ItemList<Mithril.Children>) {
    items.add('datlechin-passkey', <PasskeyLoginButton />, 100);
  });
}
