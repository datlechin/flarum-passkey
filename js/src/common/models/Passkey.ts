import Model from 'flarum/common/Model';

export default class Passkey extends Model {
  deviceName() {
    return Model.attribute<string>('deviceName').call(this);
  }

  aaguid() {
    return Model.attribute<string | null>('aaguid').call(this);
  }

  authenticatorName() {
    return Model.attribute<string | null>('authenticatorName').call(this);
  }

  transports() {
    return Model.attribute<string[]>('transports').call(this);
  }

  backupEligible() {
    return Model.attribute<boolean>('backupEligible').call(this);
  }

  backupState() {
    return Model.attribute<boolean>('backupState').call(this);
  }

  createdAt() {
    return Model.attribute<Date, string>('createdAt', Model.transformDate).call(this);
  }

  lastUsedAt() {
    return Model.attribute<Date | null, string | null>('lastUsedAt', Model.transformDate).call(this);
  }

  userAgentSummary() {
    return Model.attribute<string | null>('userAgentSummary').call(this);
  }
}
