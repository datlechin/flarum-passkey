/**
 * Inject a Passkeys section above the developer-tokens block on the user's
 * own security page. The route guard in `UserSecurityPage.oninit` already
 * prevents non-owners (and non-mods) from reaching this page, so we don't
 * need an additional ownership check here.
 *
 * Flarum 1.x's {@link extend} hooks a prototype directly, so we import
 * `UserSecurityPage` as a value rather than passing a string module path.
 */
export default function extendUserSecurityPage(): void;
