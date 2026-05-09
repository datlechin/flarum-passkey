import app from 'flarum/admin/app';
import { extend } from 'flarum/common/extend';
import type EditGroupModal from 'flarum/admin/components/EditGroupModal';
import Switch from 'flarum/common/components/Switch';
import Stream from 'flarum/common/utils/Stream';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type Group from 'flarum/common/models/Group';

/**
 * Adds a "Require passkey" toggle to the canonical Edit Group modal.
 *
 * The flag lives on the `groups` table as `passkey_required` and rides the
 * standard model update , no parallel storage, no global setting parsing.
 * The Group resource exposes the field as `passkeyRequired` (admin-writable),
 * the Group frontend model below picks it up, the modal saves it through
 * `group.save()` like every other field on this surface.
 */
export default function extendEditGroupModal(): void {
  extend(
    'flarum/admin/components/EditGroupModal',
    'oninit',
    function (this: EditGroupModal & { passkeyRequired: Stream<boolean>; group: Group }) {
      this.passkeyRequired = Stream(this.group.passkeyRequired() ?? false);
    }
  );

  extend(
    'flarum/admin/components/EditGroupModal',
    'fields',
    function (
      this: EditGroupModal & { passkeyRequired: Stream<boolean> },
      items: ItemList<Mithril.Children>
    ) {
      items.add(
        'datlechin-passkey-required',
        <div className="Form-group">
          <Switch state={this.passkeyRequired()} onchange={this.passkeyRequired}>
            {app.translator.trans('datlechin-passkey.admin.group_modal.require_passkey')}
          </Switch>
          <div className="helpText">
            {app.translator.trans('datlechin-passkey.admin.group_modal.require_passkey_help')}
          </div>
        </div>,
        // Slot between the canonical "Hide on forum" Switch (priority 10)
        // and the submit row (-10) so the toggle stays inside the form area.
        5
      );
    }
  );

  extend(
    'flarum/admin/components/EditGroupModal',
    'submitData',
    function (
      this: EditGroupModal & { passkeyRequired: Stream<boolean> },
      data: Record<string, unknown>
    ) {
      data.passkeyRequired = this.passkeyRequired();
    }
  );
}
