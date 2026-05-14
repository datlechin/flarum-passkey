/**
 * Add a "Sign in with passkey" entry to the canonical alternative-sign-in
 * surface (`LogInButtons`), the same hook OAuth providers like
 * `flarum/auth-github` use. The buttons render above the username/password
 * form in both the login and sign-up modals.
 *
 * Flarum 1.x's {@link extend} patches a prototype directly (the 2.x string
 * module-path form does not exist here), so we import `LogInButtons` as a
 * value and hook `LogInButtons.prototype`.
 */
export default function extendLogInModal(): void;
