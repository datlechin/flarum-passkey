<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\Model;

use Carbon\Carbon;
use Datlechin\Passkey\WebAuthn\AuthenticatorMetadata;
use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * @property int $id
 * @property int $user_id
 * @property string $credential_id Raw binary credential id
 * @property string $public_key_cose Raw COSE-encoded public key
 * @property int $signature_count
 * @property string[] $transports
 * @property string|null $aaguid
 * @property string $attestation_format
 * @property bool $backup_eligible
 * @property bool $backup_state
 * @property bool $uv_initialized
 * @property string $device_name
 * @property string|null $user_agent_summary
 * @property Carbon|null $last_used_at
 * @property string|null $last_used_ip
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User|null $user
 */
class Passkey extends AbstractModel
{
    protected $table = 'passkeys';

    // Override Flarum\Database\AbstractModel which sets $timestamps = false.
    public $timestamps = true;

    protected $casts = [
        'user_id' => 'integer',
        'signature_count' => 'integer',
        'transports' => 'array',
        'backup_eligible' => 'boolean',
        'backup_state' => 'boolean',
        'uv_initialized' => 'boolean',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Cascade login_providers cleanup for delete paths that bypass the
        // API resource (admin tools, GDPR erasure, raw Eloquent).
        static::deleted(function (Passkey $passkey) {
            \Flarum\User\LoginProvider::where('provider', 'passkey')
                ->where('identifier', $passkey->getProviderIdentifier())
                ->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAuthenticatorNameAttribute(): ?string
    {
        return AuthenticatorMetadata::nameFor($this->aaguid);
    }

    public function toCredentialRecord(): CredentialRecord
    {
        return new CredentialRecord(
            publicKeyCredentialId: $this->credential_id,
            type: 'public-key',
            transports: $this->transports ?? [],
            attestationType: $this->attestation_format,
            trustPath: new EmptyTrustPath(),
            aaguid: $this->aaguid !== null ? Uuid::fromString($this->aaguid) : Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            credentialPublicKey: $this->public_key_cose,
            userHandle: (string) $this->user_id,
            counter: $this->signature_count,
            backupEligible: $this->backup_eligible,
            backupStatus: $this->backup_state,
            uvInitialized: $this->uv_initialized,
        );
    }

    public function syncFromCredentialRecord(CredentialRecord $record): void
    {
        $this->signature_count = $record->counter;
        $this->backup_eligible = (bool) $record->backupEligible;
        $this->backup_state = (bool) $record->backupStatus;
        $this->uv_initialized = (bool) $record->uvInitialized;
    }

    public function getProviderIdentifier(): string
    {
        return self::base64UrlEncode($this->credential_id);
    }

    public static function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $encoded): string
    {
        $padded = str_pad($encoded, strlen($encoded) + (4 - strlen($encoded) % 4) % 4, '=');

        return base64_decode(strtr($padded, '-_', '+/'));
    }
}
