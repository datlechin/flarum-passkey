import app from 'flarum/admin/app';
import { extend } from 'flarum/common/extend';
import EditGroupModal from 'flarum/admin/components/EditGroupModal';
import Switch from 'flarum/common/components/Switch';
import Stream from 'flarum/common/utils/Stream';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

// `passkeyRequired` is an instance field this extender adds to EditGroupModal
// at runtime; the intersection makes it visible to the `this` inside each hook
// and lets us cast the prototype so 1.x `extend()` infers the right type.
type EditGroupModalWithPasskey = EditGroupModal & { passkeyRequired: Stream<boolean> };

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
export default function extendEditGroupModal(): void {
  extend(EditGroupModal.prototype as EditGroupModalWithPasskey, 'oninit', function (this: EditGroupModalWithPasskey) {
    this.passkeyRequired = Stream(this.group.passkeyRequired() ?? false);
  });

  extend(
    EditGroupModal.prototype as EditGroupModalWithPasskey,
    'fields',
    function (this: EditGroupModalWithPasskey, items: ItemList<Mithril.Children>) {
      items.add(
        'datlechin-passkey-required',
        <div className="Form-group">
          <Switch state={this.passkeyRequired()} onchange={this.passkeyRequired}>
            {app.translator.trans('datlechin-passkey.admin.group_modal.require_passkey')}
          </Switch>
          <div className="helpText">{app.translator.trans('datlechin-passkey.admin.group_modal.require_passkey_help')}</div>
        </div>,
        // Slot between the canonical "Hide on forum" Switch (priority 10)
        // and the submit row (-10) so the toggle stays inside the form area.
        5
      );
    }
  );

  extend(
    EditGroupModal.prototype as EditGroupModalWithPasskey,
    'submitData',
    function (this: EditGroupModalWithPasskey, data: Record<string, unknown>) {
      data.passkeyRequired = this.passkeyRequired();
    }
  );
}
