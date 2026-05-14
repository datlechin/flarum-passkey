import app from 'flarum/forum/app';
// Flarum 1.x has no FormModal/Form split — the base `Modal` is already wrapped
// in a <form> and exposes the same onsubmit/loading/hide API the 2.x FormModal
// did. (Modal.d.ts even notes "@todo split into FormModal and Modal in 2.0".)
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import { fetchRegistrationOptions, performRegistration, submitRegistration, PasskeyClientError } from '../lib/webauthn';

export interface IAddPasskeyModalAttrs extends IInternalModalAttrs {
  onSuccess?: () => void;
}

export default class AddPasskeyModal extends Modal<IAddPasskeyModalAttrs> {
  deviceName!: Stream<string>;

  oninit(vnode: Mithril.Vnode<IAddPasskeyModalAttrs, this>): void {
    super.oninit(vnode);
    this.deviceName = Stream(this.guessDeviceName());
  }

  className(): string {
    return 'AddPasskeyModal Modal--small';
  }

  title(): Mithril.Children {
    return app.translator.trans('datlechin-passkey.forum.settings.add_modal.title');
  }

  content(): Mithril.Children {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">{this.fields().toArray()}</div>
      </div>
    );
  }

  fields(): ItemList<Mithril.Children> {
    const fields = new ItemList<Mithril.Children>();

    fields.add(
      'deviceName',
      <div className="Form-group">
        <label htmlFor="passkey-device-name">{app.translator.trans('datlechin-passkey.forum.settings.add_modal.device_name_label')}</label>
        <input
          id="passkey-device-name"
          className="FormControl"
          placeholder={app.translator.trans('datlechin-passkey.forum.settings.add_modal.device_name_placeholder') as string}
          bidi={this.deviceName}
          maxlength={64}
          disabled={this.loading}
        />
      </div>,
      30
    );

    fields.add(
      'submit',
      <div className="Form-group Form-controls">
        <Button className="Button Button--primary Button--block" type="submit" loading={this.loading}>
          {app.translator.trans('datlechin-passkey.forum.settings.add_modal.register_button')}
        </Button>
      </div>,
      -10
    );

    return fields;
  }

  onsubmit(e: SubmitEvent): void {
    e.preventDefault();

    const trimmed = (this.deviceName() ?? '').trim();
    if (trimmed === '') return;

    this.loading = true;

    (async () => {
      const options = await fetchRegistrationOptions();
      const credential = await performRegistration(options);
      await submitRegistration(credential, trimmed);
    })()
      .then(() => {
        app.alerts.show({ type: 'success' }, app.translator.trans('datlechin-passkey.forum.settings.alerts.registration_succeeded'));
        this.attrs.onSuccess?.();
        this.hide();
      })
      .catch((err) => {
        this.loaded();

        // Cancellation is silent: user dismissed the native prompt or it timed
        // out. They know what they did, no toast needed.
        if (err instanceof PasskeyClientError && err.kind === 'cancelled') {
          return;
        }

        // Server-side failures (401 session expired, 422 validation, 429
        // throttled, ...) are not PasskeyClientError. Flarum's default
        // request handler has already shown an alert for those, so we skip
        // surfacing a second message here.
        if (!(err instanceof PasskeyClientError)) {
          return;
        }

        const messageByKind: Record<string, string> = {
          invalid_state: 'datlechin-passkey.forum.settings.alerts.registration_already_exists',
          security: 'datlechin-passkey.forum.settings.alerts.registration_security_error',
          unsupported: 'datlechin-passkey.forum.settings.alerts.registration_unsupported',
        };
        const messageKey = messageByKind[err.kind] ?? 'datlechin-passkey.forum.settings.alerts.registration_failed';

        app.alerts.show({ type: 'error' }, app.translator.trans(messageKey));
      });
  }

  /** "{browser} on {os}" matching core's AccessTokenResource session devices. */
  private guessDeviceName(): string {
    const data = navigator.userAgentData;

    if (data) {
      const platform = data.platform ?? '';
      const browser = data.brands.find((b) => /Chrome|Edge|Opera|Brave/i.test(b.brand))?.brand ?? data.brands[0]?.brand ?? '';
      const friendlyBrowser = browser.replace(/^Google /, '').replace(/Microsoft /, '');

      if (data.mobile && platform === 'Android') return browserOn(friendlyBrowser, 'Android');
      if (platform === 'macOS') return browserOn(friendlyBrowser, 'Mac');
      if (platform === 'Windows') return browserOn(friendlyBrowser, 'Windows');
      if (platform === 'Linux') return browserOn(friendlyBrowser, 'Linux');
      if (platform === 'Chrome OS') return browserOn(friendlyBrowser, 'Chromebook');
    }

    const ua = navigator.userAgent;

    const browser = /Edg\//.test(ua)
      ? 'Edge'
      : /Firefox\//.test(ua)
      ? 'Firefox'
      : /CriOS\//.test(ua)
      ? 'Chrome'
      : /OPR\/|Opera/.test(ua)
      ? 'Opera'
      : /Chrome\//.test(ua)
      ? 'Chrome'
      : /Safari\//.test(ua)
      ? 'Safari'
      : '';

    let device = '';
    if (/iPhone/.test(ua)) {
      device = 'iPhone';
    } else if (/iPad/.test(ua)) {
      device = 'iPad';
    } else if (/Android/.test(ua)) {
      const match = ua.match(/Android[^;]*;\s*([^;)]+?)(?:\s+Build|\))/);
      device = match?.[1]?.trim() || 'Android';
    } else if (/Macintosh|Mac OS X/.test(ua)) {
      device = 'Mac';
    } else if (/Windows NT/.test(ua)) {
      device = 'Windows';
    } else if (/CrOS/.test(ua)) {
      device = 'Chromebook';
    } else if (/Linux/.test(ua)) {
      device = 'Linux';
    }

    return browserOn(browser, device) || 'Passkey';
  }
}

function browserOn(browser: string, device: string): string {
  if (browser && device) return `${browser} on ${device}`;
  return browser || device;
}
