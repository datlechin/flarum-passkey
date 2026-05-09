import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import type Mithril from 'mithril';
import AddPasskeyModal from './AddPasskeyModal';

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
  // True when the user explicitly handed off to AddPasskeyModal via Set up.
  // Without this we cannot tell apart "user accepted" from "user closed",
  // and the dismissal cool-down would fire on every accept.
  private accepted = false;

  className(): string {
    return 'SuggestPasskeyModal Modal--small';
  }

  title(): Mithril.Children {
    return app.translator.trans('datlechin-passkey.forum.suggest.title');
  }

  content(): Mithril.Children {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <p className="helpText">
            {app.translator.trans('datlechin-passkey.forum.suggest.body')}
          </p>
          <div className="Form-group Form-controls">
            <Button
              className="Button Button--primary Button--block"
              onclick={this.setUp.bind(this)}
            >
              {app.translator.trans('datlechin-passkey.forum.suggest.set_up')}
            </Button>
          </div>
          <div className="Form-group">
            <Button
              className="Button Button--text Button--link"
              onclick={() => this.hide()}
            >
              {app.translator.trans('datlechin-passkey.forum.suggest.not_now')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  onbeforeremove(vnode: Mithril.VnodeDOM<ISuggestPasskeyModalAttrs, this>): Promise<void> | void {
    if (!this.accepted) {
      this.attrs.onDismiss?.();
    }
    return super.onbeforeremove(vnode);
  }

  private setUp(): void {
    this.accepted = true;
    // app.modal.show replaces the current modal stack; calling hide() first
    // races with that replacement and leaves the new modal in a half-open
    // state where its loading flag never resets after a successful submit.
    app.modal.show(AddPasskeyModal);
  }
}
