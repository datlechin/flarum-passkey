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
export default function extendLogInModal(): void;
