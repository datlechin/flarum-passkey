<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\WebAuthn;

use Cose\Algorithms;
use Datlechin\Passkey\Model\Passkey;
use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Builds the per-ceremony option DTOs that get serialized to the browser.
 */
class OptionsBuilder
{
    private const TIMEOUT_MS = 60_000;
    private const DEFAULT_USER_VERIFICATION = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;
    private const DEFAULT_ATTESTATION = PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE;
    private const CHALLENGE_BYTES = 32;

    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
        private readonly Config $config,
    ) {
    }

    /** @param Passkey[] $existingPasskeys */
    public function buildCreationOptions(User $user, array $existingPasskeys, string $host): PublicKeyCredentialCreationOptions
    {
        $rpEntity = new PublicKeyCredentialRpEntity(
            name: $this->rpName(),
            id: $this->rpId($host),
        );

        $userEntity = new PublicKeyCredentialUserEntity(
            name: $user->username,
            id: (string) $user->id,
            displayName: $user->display_name ?? $user->username,
        );

        $excludeCredentials = array_map(
            fn (Passkey $p) => PublicKeyCredentialDescriptor::create(
                'public-key',
                $p->credential_id,
                $p->transports ?? []
            ),
            $existingPasskeys
        );

        $authenticatorSelection = AuthenticatorSelectionCriteria::create(
            authenticatorAttachment: null,
            userVerification: $this->userVerification(),
            residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_PREFERRED,
        );

        return PublicKeyCredentialCreationOptions::create(
            rp: $rpEntity,
            user: $userEntity,
            challenge: random_bytes(self::CHALLENGE_BYTES),
            pubKeyCredParams: $this->credentialParameters(),
            authenticatorSelection: $authenticatorSelection,
            attestation: $this->attestationConveyance(),
            excludeCredentials: $excludeCredentials,
            timeout: self::TIMEOUT_MS,
        );
    }

    /** @param Passkey[] $allowCredentials Empty for discoverable-credential flow. */
    public function buildRequestOptions(array $allowCredentials, string $host): PublicKeyCredentialRequestOptions
    {
        $allow = array_map(
            fn (Passkey $p) => PublicKeyCredentialDescriptor::create(
                'public-key',
                $p->credential_id,
                $p->transports ?? []
            ),
            $allowCredentials
        );

        return PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(self::CHALLENGE_BYTES),
            rpId: $this->rpId($host),
            allowCredentials: $allow,
            userVerification: $this->userVerification(),
            timeout: self::TIMEOUT_MS,
            // Prefer the on-device platform authenticator before offering hybrid
            // (cross-device) flows. On modern Chromium/Edge/Safari this collapses
            // the picker to a single local prompt when only one local credential
            // matches; hybrid stays available as a fallback for users signing in
            // from a phone or roaming security key.
            hints: [
                PublicKeyCredentialOptions::HINT_CLIENT_DEVICE,
                PublicKeyCredentialOptions::HINT_HYBRID,
            ],
        );
    }

    /** @return PublicKeyCredentialParameters[] */
    private function credentialParameters(): array
    {
        return [
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256),
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_RS256),
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_EDDSA),
        ];
    }

    private function rpId(string $host): string
    {
        $configured = $this->settings->get('datlechin-passkey.rp_id');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        // Trust config.url over the request Host header so a poisoned proxy
        // cannot pin ceremony options to a spoofed RP ID.
        $configHost = parse_url((string) $this->config->url(), PHP_URL_HOST);

        return is_string($configHost) && $configHost !== '' ? $configHost : $host;
    }

    private function rpName(): string
    {
        $configured = $this->settings->get('datlechin-passkey.rp_name');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $forumTitle = $this->settings->get('forum_title');

        return is_string($forumTitle) && $forumTitle !== '' ? $forumTitle : 'Flarum';
    }

    private function userVerification(): string
    {
        $configured = $this->settings->get('datlechin-passkey.user_verification');

        return in_array($configured, ['required', 'preferred', 'discouraged'], true)
            ? $configured
            : self::DEFAULT_USER_VERIFICATION;
    }

    private function attestationConveyance(): string
    {
        $configured = $this->settings->get('datlechin-passkey.attestation');

        return in_array($configured, ['none', 'indirect', 'direct'], true)
            ? $configured
            : self::DEFAULT_ATTESTATION;
    }

}
