/**
 * Inject a Passkeys section above the developer-tokens block on the user's
 * own security page. The route guard in `UserSecurityPage.oninit` already
 * prevents non-owners (and non-mods) from reaching this page, so we don't
 * need an additional ownership check here.
 *
 * The page is lazy-loaded, so we pass its module path as a string and let
 * {@link extend} hook the prototype once the chunk arrives.
 */
export default function extendUserSecurityPage(): void;
