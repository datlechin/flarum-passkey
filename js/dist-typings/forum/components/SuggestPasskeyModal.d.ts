import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';
export interface ISuggestPasskeyModalAttrs extends IInternalModalAttrs {
    onDismiss?: () => void;
}
/**
 * Surfaces the post-login passkey suggestion as a focused modal rather than
 * an inline alert. Clicking "Set up" hands off to {@link AddPasskeyModal} so
 * the registration ceremony runs from a fresh user gesture (Safari refuses
 * passkey prompts that land more than one frame after a click).
 *
 * The modal is dismissable: closing via the X, the Not now button, or the
 * backdrop all route through the same dismissal callback so the 30-day
 * cool-down cannot be sidestepped.
 */
export default class SuggestPasskeyModal extends Modal<ISuggestPasskeyModalAttrs> {
    private accepted;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    onbeforeremove(vnode: Mithril.VnodeDOM<ISuggestPasskeyModalAttrs, this>): Promise<void> | void;
    private setUp;
}
