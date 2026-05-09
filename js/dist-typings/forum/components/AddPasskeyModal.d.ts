import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
export interface IAddPasskeyModalAttrs extends IFormModalAttrs {
    onSuccess?: () => void;
}
export default class AddPasskeyModal extends FormModal<IAddPasskeyModalAttrs> {
    deviceName: Stream<string>;
    oninit(vnode: Mithril.Vnode<IAddPasskeyModalAttrs, this>): void;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    fields(): ItemList<Mithril.Children>;
    onsubmit(e: SubmitEvent): void;
    /** "{browser} on {os}" matching core's AccessTokenResource session devices. */
    private guessDeviceName;
}
