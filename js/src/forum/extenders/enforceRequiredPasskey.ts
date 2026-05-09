import app from 'flarum/forum/app';
import AddPasskeyModal from '../components/AddPasskeyModal';
import { isSupported } from '../lib/webauthn';

/**
 * If the admin has flagged this user's group as "passkey required" and the
 * user has not yet registered one, surface a sticky banner pointing them at
 * the registration flow. The banner reappears on every page load until the
 * user complies, but does not hard-block navigation , that would lock anyone
 * out who happened to lose their last device.
 *
 * The flag is computed server-side from the request actor + settings, so it
 * is always fresh: as soon as the user registers a passkey the next render
 * stops emitting the flag.
 */
export default function enforceRequiredPasskey(): void {
  if (!isSupported()) return;

  const required = (app.data as Record<string, any> | undefined)?.datlechinPasskey?.passkeyRequired;
  if (!required) return;

  // Wait one tick so other initializers finish wiring app.alerts.
  setTimeout(() => {
    app.alerts.show(
      {
        type: 'warning',
        dismissible: false,
        controls: [
          m(
            'a',
            {
              className: 'Button Button--link',
              onclick: (e: MouseEvent) => {
                e.preventDefault();
                app.modal.show(AddPasskeyModal, {
                  onSuccess: () => window.location.reload(),
                });
              },
              href: '#',
            },
            app.translator.trans('datlechin-passkey.forum.required.set_up')
          ),
        ],
      },
      app.translator.trans('datlechin-passkey.forum.required.body')
    );
  }, 0);
}
