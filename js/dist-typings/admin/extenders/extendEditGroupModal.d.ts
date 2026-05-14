/**
 * Adds a "Require passkey" toggle to the canonical Edit Group modal.
 *
 * The flag lives on the `groups` table as `passkey_required` and rides the
 * standard model update , no parallel storage, no global setting parsing.
 * The GroupSerializer exposes the field as `passkeyRequired`, the Group
 * frontend model below picks it up, the modal saves it through `group.save()`
 * like every other field on this surface.
 *
 * Flarum 1.x's {@link extend} hooks a prototype directly, so we import
 * `EditGroupModal` as a value rather than passing a string module path.
 */
export default function extendEditGroupModal(): void;
