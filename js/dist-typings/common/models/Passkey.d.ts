import Model from 'flarum/common/Model';
export default class Passkey extends Model {
    deviceName(): string;
    aaguid(): string | null;
    authenticatorName(): string | null;
    transports(): string[];
    backupEligible(): boolean;
    backupState(): boolean;
    createdAt(): Date;
    lastUsedAt(): Date | null;
    userAgentSummary(): string | null;
}
