import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import UserSecurityPage from 'flarum/forum/components/UserSecurityPage';
import FieldSet from 'flarum/common/components/FieldSet';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import PasskeyList from '../components/PasskeyList';

/**
 * Inject a Passkeys section above the developer-tokens block on the user's
 * own security page. The route guard in `UserSecurityPage.oninit` already
 * prevents non-owners (and non-mods) from reaching this page, so we don't
 * need an additional ownership check here.
 *
 * Flarum 1.x's {@link extend} hooks a prototype directly, so we import
 * `UserSecurityPage` as a value rather than passing a string module path.
 */
export default function extendUserSecurityPage(): void {
  extend(UserSecurityPage.prototype, 'settingsItems', function (this: UserSecurityPage, items: ItemList<Mithril.Children>) {
    items.add(
      'datlechin-passkeys',
      <FieldSet className="UserSecurityPage-passkeys" label={app.translator.trans('datlechin-passkey.forum.settings.heading')}>
        <PasskeyList />
      </FieldSet>,
      90
    );
  });
}
