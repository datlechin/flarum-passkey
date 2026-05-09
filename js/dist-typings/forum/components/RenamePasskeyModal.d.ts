import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import type Passkey from '../../common/models/Passkey';
export interface IRenamePasskeyModalAttrs extends IFormModalAttrs {
    passkey: Passkey;
    onSuccess?: () => void;
}
export default class RenamePasskeyModal extends FormModal<IRenamePasskeyModalAttrs> {
    name: Stream<string>;
    oninit(vnode: Mithril.Vnode<IRenamePasskeyModalAttrs, this>): void;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    fields(): ItemList<Mithril.Children>;
    onsubmit(e: SubmitEvent): void;
}
