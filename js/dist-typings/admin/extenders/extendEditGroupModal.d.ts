/**
 * Adds a "Require passkey" toggle to the canonical Edit Group modal.
 *
 * The flag lives on the `groups` table as `passkey_required` and rides the
 * standard model update , no parallel storage, no global setting parsing.
 * The Group resource exposes the field as `passkeyRequired` (admin-writable),
 * the Group frontend model below picks it up, the modal saves it through
 * `group.save()` like every other field on this surface.
 */
export default function extendEditGroupModal(): void;
