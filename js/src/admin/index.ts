import app from 'flarum/admin/app';
import Model from 'flarum/common/Model';
import Group from 'flarum/common/models/Group';
import extendEditGroupModal from './extenders/extendEditGroupModal';

// Teach the canonical Group model about our column. Done at module top level
// so the accessor is in place before any component reads `group.passkeyRequired()`.
// The matching declaration lives in `js/src/@types/augmentations.d.ts`.
Group.prototype.passkeyRequired = Model.attribute<boolean>('passkeyRequired');

app.initializers.add('datlechin-passkey', () => {
  extendEditGroupModal();

  app.registry
    .for('datlechin-passkey')
    .registerSetting({
      setting: 'datlechin-passkey.rp_id',
      label: app.translator.trans('datlechin-passkey.admin.settings.rp_id_label'),
      help: app.translator.trans('datlechin-passkey.admin.settings.rp_id_help'),
      type: 'text',
    })
    .registerSetting({
      setting: 'datlechin-passkey.rp_name',
      label: app.translator.trans('datlechin-passkey.admin.settings.rp_name_label'),
      help: app.translator.trans('datlechin-passkey.admin.settings.rp_name_help'),
      type: 'text',
    })
    .registerSetting({
      setting: 'datlechin-passkey.related_origins',
      label: app.translator.trans('datlechin-passkey.admin.settings.related_origins_label'),
      help: app.translator.trans('datlechin-passkey.admin.settings.related_origins_help'),
      type: 'textarea',
    })
    .registerSetting({
      setting: 'datlechin-passkey.user_verification',
      label: app.translator.trans('datlechin-passkey.admin.settings.user_verification_label'),
      type: 'select',
      options: {
        required: app.translator.trans('datlechin-passkey.admin.settings.user_verification_required'),
        preferred: app.translator.trans('datlechin-passkey.admin.settings.user_verification_preferred'),
        discouraged: app.translator.trans('datlechin-passkey.admin.settings.user_verification_discouraged'),
      },
      default: 'preferred',
    })
    .registerSetting({
      setting: 'datlechin-passkey.attestation',
      label: app.translator.trans('datlechin-passkey.admin.settings.attestation_label'),
      type: 'select',
      options: {
        none: app.translator.trans('datlechin-passkey.admin.settings.attestation_none'),
        indirect: app.translator.trans('datlechin-passkey.admin.settings.attestation_indirect'),
        direct: app.translator.trans('datlechin-passkey.admin.settings.attestation_direct'),
      },
      default: 'none',
    })
    .registerSetting({
      setting: 'datlechin-passkey.throttle_per_minute',
      label: app.translator.trans('datlechin-passkey.admin.settings.throttle_label'),
      type: 'number',
      min: 1,
      max: 1000,
    });
});
