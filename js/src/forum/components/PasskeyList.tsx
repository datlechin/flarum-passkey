import app from 'flarum/forum/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Icon from 'flarum/common/components/Icon';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import humanTime from 'flarum/common/helpers/humanTime';
import extractText from 'flarum/common/utils/extractText';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type Passkey from '../../common/models/Passkey';
import AddPasskeyModal from './AddPasskeyModal';
import RenamePasskeyModal from './RenamePasskeyModal';
import { isSupported, bulkRevoke } from '../lib/webauthn';

export interface IPasskeyListAttrs extends ComponentAttrs {}

export default class PasskeyList extends Component<IPasskeyListAttrs> {
  protected loaded = false;
  protected loading: Record<string, boolean | undefined> = {};
  protected bulkLoading = false;
  protected passkeys: Passkey[] = [];

  oninit(vnode: Mithril.Vnode<IPasskeyListAttrs, this>): void {
    super.oninit(vnode);
    this.refresh();
  }

  view(): Mithril.Children {
    if (!this.loaded) {
      return <LoadingIndicator />;
    }

    return (
      <div className="PasskeyList">
        {this.passkeys.length === 0 ? (
          <div className="PasskeyList--empty">{app.translator.trans('datlechin-passkey.forum.settings.list.empty')}</div>
        ) : (
          this.passkeys.map((p) => this.itemView(p))
        )}

        <div className="PasskeyList-actions">
          <Button className="Button" icon="fas fa-plus" disabled={!isSupported()} onclick={this.openAdd.bind(this)}>
            {app.translator.trans('datlechin-passkey.forum.settings.add_button')}
          </Button>

          {this.passkeys.length > 0 && (
            <Button className="Button Button--link" loading={this.bulkLoading} onclick={this.bulkRevoke.bind(this)}>
              {app.translator.trans('datlechin-passkey.forum.settings.bulk_revoke_button')}
            </Button>
          )}
        </div>
      </div>
    );
  }

  protected itemView(passkey: Passkey): Mithril.Children {
    return (
      <div className="PasskeyList-item" key={passkey.id()!}>
        {this.itemContents(passkey).toArray()}
      </div>
    );
  }

  protected itemContents(passkey: Passkey): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'icon',
      <div className="PasskeyList-item-icon">
        <Icon name="fas fa-key" />
      </div>,
      50
    );

    items.add('info', <div className="PasskeyList-item-info">{this.itemInfo(passkey).toArray()}</div>, 40);

    items.add('actions', <div className="PasskeyList-item-actions">{this.itemActions(passkey).toArray()}</div>, 30);

    return items;
  }

  protected itemInfo(passkey: Passkey): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    // Authenticator name when the AAGUID is recognised, sync-state hint otherwise.
    const sub =
      passkey.authenticatorName() ??
      (passkey.backupState()
        ? app.translator.trans('datlechin-passkey.forum.settings.list.synced')
        : app.translator.trans('datlechin-passkey.forum.settings.list.device_only'));

    items.add(
      'title',
      <div className="PasskeyList-item-title">
        <span className="PasskeyList-item-title-main">{passkey.deviceName()}</span>
        {', '}
        <span className="PasskeyList-item-title-sub">{sub}</span>
      </div>,
      30
    );

    items.add('meta', <div className="PasskeyList-item-meta">{this.metaItems(passkey).toArray()}</div>, 20);

    return items;
  }

  protected metaItems(passkey: Passkey): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    const createdAt = passkey.createdAt();
    if (createdAt) {
      items.add(
        'added',
        <span>
          {app.translator.trans('datlechin-passkey.forum.settings.list.added_label')} <strong>{humanTime(createdAt)}</strong>
        </span>,
        20
      );
    }

    const lastUsedAt = passkey.lastUsedAt();
    items.add(
      'lastUsed',
      <span>
        {lastUsedAt ? (
          <>
            {app.translator.trans('datlechin-passkey.forum.settings.list.last_used_label')} <strong>{humanTime(lastUsedAt)}</strong>
          </>
        ) : (
          app.translator.trans('datlechin-passkey.forum.settings.list.never_used')
        )}
      </span>,
      10
    );

    return items;
  }

  protected itemActions(passkey: Passkey): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'rename',
      <Button className="Button Button--inverted" icon="fas fa-pen" onclick={() => this.openRename(passkey)}>
        {app.translator.trans('datlechin-passkey.forum.settings.list.rename_button')}
      </Button>,
      20
    );

    items.add(
      'revoke',
      <Button className="Button Button--danger" icon="fas fa-trash" loading={!!this.loading[passkey.id()!]} onclick={() => this.revoke(passkey)}>
        {app.translator.trans('datlechin-passkey.forum.settings.list.revoke_button')}
      </Button>,
      10
    );

    return items;
  }

  protected refresh(): void {
    this.loaded = false;
    m.redraw();

    app.store
      .find<Passkey[]>('passkeys')
      .then((passkeys) => {
        this.passkeys = passkeys;
        this.loaded = true;
        m.redraw();
      })
      .catch(() => {
        this.loaded = true;
        m.redraw();
      });
  }

  protected openAdd(): void {
    app.modal.show(AddPasskeyModal, { onSuccess: () => this.refresh() });
  }

  protected openRename(passkey: Passkey): void {
    app.modal.show(RenamePasskeyModal, { passkey, onSuccess: () => m.redraw() });
  }

  protected async bulkRevoke(): Promise<void> {
    const message = extractText(
      app.translator.trans('datlechin-passkey.forum.settings.bulk_revoke_modal.body', {
        count: this.passkeys.length,
      })
    );
    if (!confirm(message)) return;

    this.bulkLoading = true;
    m.redraw();

    try {
      await bulkRevoke();
      this.passkeys = [];
      app.alerts.show({ type: 'success' }, app.translator.trans('datlechin-passkey.forum.settings.alerts.bulk_revoke_succeeded'));
    } finally {
      this.bulkLoading = false;
      m.redraw();
    }
  }

  protected async revoke(passkey: Passkey): Promise<void> {
    const message = extractText(
      app.translator.trans('datlechin-passkey.forum.settings.revoke_modal.body', {
        device: passkey.deviceName(),
      })
    );
    if (!confirm(message)) return;

    this.loading[passkey.id()!] = true;
    m.redraw();

    try {
      await passkey.delete();
      this.passkeys = this.passkeys.filter((p) => p !== passkey);
      app.alerts.show({ type: 'success' }, app.translator.trans('datlechin-passkey.forum.settings.alerts.revoke_succeeded'));
    } finally {
      delete this.loading[passkey.id()!];
      m.redraw();
    }
  }
}
