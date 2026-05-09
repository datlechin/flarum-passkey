import app from 'flarum/forum/app';
import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import Form from 'flarum/common/components/Form';
import Button from 'flarum/common/components/Button';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import type Passkey from '../../common/models/Passkey';

export interface IRenamePasskeyModalAttrs extends IFormModalAttrs {
  passkey: Passkey;
  onSuccess?: () => void;
}

export default class RenamePasskeyModal extends FormModal<IRenamePasskeyModalAttrs> {
  // Streams must be created in oninit, not as class field initializers,
  // because Mithril sets `this.attrs` only after construction. A field
  // initializer on `this.attrs.passkey` runs first and throws.
  name!: Stream<string>;

  oninit(vnode: Mithril.Vnode<IRenamePasskeyModalAttrs, this>): void {
    super.oninit(vnode);
    this.name = Stream(this.attrs.passkey.deviceName());
  }

  className(): string {
    return 'RenamePasskeyModal Modal--small';
  }

  title(): Mithril.Children {
    return app.translator.trans('datlechin-passkey.forum.settings.rename_modal.title');
  }

  content(): Mithril.Children {
    return (
      <div className="Modal-body">
        <Form className="Form--centered">{this.fields().toArray()}</Form>
      </div>
    );
  }

  fields(): ItemList<Mithril.Children> {
    const fields = new ItemList<Mithril.Children>();

    fields.add(
      'name',
      <div className="Form-group">
        <input className="FormControl" bidi={this.name} maxlength={64} disabled={this.loading} autofocus />
      </div>,
      30
    );

    fields.add(
      'submit',
      <div className="Form-group Form-controls">
        <Button className="Button Button--primary Button--block" type="submit" loading={this.loading}>
          {app.translator.trans('datlechin-passkey.forum.settings.rename_modal.save_button')}
        </Button>
      </div>,
      -10
    );

    return fields;
  }

  onsubmit(e: SubmitEvent): void {
    e.preventDefault();

    const trimmed = (this.name() ?? '').trim();
    if (trimmed === '') return;

    this.loading = true;

    this.attrs.passkey
      .save({ deviceName: trimmed })
      .then(() => {
        app.alerts.show({ type: 'success' }, app.translator.trans('datlechin-passkey.forum.settings.alerts.rename_succeeded'));
        this.attrs.onSuccess?.();
        this.hide();
      })
      .catch(() => {
        this.loaded();
      });
  }
}
