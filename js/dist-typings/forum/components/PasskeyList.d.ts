import Component, { ComponentAttrs } from 'flarum/common/Component';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type Passkey from '../../common/models/Passkey';
export interface IPasskeyListAttrs extends ComponentAttrs {
}
export default class PasskeyList extends Component<IPasskeyListAttrs> {
    protected loaded: boolean;
    protected loading: Record<string, boolean | undefined>;
    protected bulkLoading: boolean;
    protected passkeys: Passkey[];
    oninit(vnode: Mithril.Vnode<IPasskeyListAttrs, this>): void;
    view(): Mithril.Children;
    protected itemView(passkey: Passkey): Mithril.Children;
    protected itemContents(passkey: Passkey): ItemList<Mithril.Children>;
    protected itemInfo(passkey: Passkey): ItemList<Mithril.Children>;
    protected metaItems(passkey: Passkey): ItemList<Mithril.Children>;
    protected itemActions(passkey: Passkey): ItemList<Mithril.Children>;
    protected refresh(): void;
    protected openAdd(): void;
    protected openRename(passkey: Passkey): void;
    protected bulkRevoke(): Promise<void>;
    protected revoke(passkey: Passkey): Promise<void>;
}
