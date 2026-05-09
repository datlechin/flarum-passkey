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

/**
 * Maps well-known FIDO AAGUIDs to human-readable authenticator names.
 *
 * Sourced from the FIDO Alliance Metadata Service v3 (MDS3) and the
 * passkey-authenticator-aaguids community list. The list is intentionally
 * curated rather than exhaustive, covering the consumer authenticators that
 * account for the vast majority of real-world passkeys.
 *
 * Unknown or anonymous AAGUIDs (the all-zero guid is the W3C-defined
 * "anonymous" credential) resolve to null so callers can fall back to the
 * user-set device label.
 */
final class AuthenticatorMetadata
{
    /** @var array<string, string> normalized AAGUID => display name */
    private const NAMES = [
        // Apple
        'adce0002-35bc-c60a-648b-0b25f1f05503' => 'iCloud Keychain',
        'fbfc3007-154e-4ecc-8c0b-6e020557d7bd' => 'iCloud Keychain',
        'dd4ec289-e01d-41c9-bb89-70fa845d4bf2' => 'iCloud Keychain (managed)',

        // Google
        'ea9b8d66-4d01-1d21-3ce4-b6b48cb575d4' => 'Google Password Manager',
        '42129de2-1c46-4d2b-94de-6e94d6f5db9b' => 'Google Password Manager',

        // Microsoft Windows Hello
        '08987058-cadc-4b81-b6e1-30de50dcbe96' => 'Windows Hello',
        '9ddd1817-af5a-4672-a2b9-3e3dd95000a9' => 'Windows Hello',
        '6028b017-b1d4-4c02-b4b3-afcdafc96bb2' => 'Windows Hello',

        // Password managers
        'bada5566-a7aa-401f-bd96-45619a55120d' => '1Password',
        'b84e4048-15dc-4dd0-8640-f4f60813c8af' => '1Password',
        'd548826e-79b4-db40-a3d8-11116f7e8349' => 'Bitwarden',
        'de1e552d-de52-4e1c-a9bd-7f91b9bfb74a' => 'Dashlane',
        'cb04b3f0-44ac-4f86-95a4-e6d61b0e7a1e' => 'NordPass',
        '53414d53-554e-4700-0000-000000000000' => 'Samsung Pass',
        '50726f74-6f6e-2050-6173-732d4b657900' => 'Proton Pass',
        '2c0b96be-3ee3-481b-9b96-bda1e94e0a4f' => 'Enpass',
        '50a45b0c-80e7-f944-bf29-f552bfa2e048' => 'KeePassXC',

        // YubiKey
        'cb69481e-8ff7-4039-93ec-0a2729a154a8' => 'YubiKey 5 Series',
        'fa2b99dc-9e39-4257-8f92-4a30d23c4118' => 'YubiKey 5 Series with NFC',
        'c5ef55ff-ad9a-4b9f-b580-adebafe026d0' => 'YubiKey 5 NFC',
        '73bb0cd4-e502-49b8-9c6f-b59445bf720b' => 'YubiKey 5 with NFC (FIPS)',
        'a4e9fc6d-4cbe-4758-b8ba-37598bb5bbaa' => 'Security Key by Yubico',
        'f8a011f3-8c0a-4d15-8006-17111f9edc7d' => 'Security Key NFC by Yubico',
        '149a2021-8ef6-4133-96b8-81f8d5b7f1f5' => 'Security Key NFC by Yubico (Enterprise)',
        '34744913-4533-4e60-9c3c-2a8cc3e4b3c6' => 'YubiKey Bio',
        'd8522d9f-575b-4866-88a9-ba99fa02f35b' => 'YubiKey Bio (FIDO Edition)',

        // Other hardware keys
        '85203421-48f9-4355-9bc8-8a53846e5083' => 'OnlyKey',
        '54d9fee8-e621-4291-8b18-7157b99c5bec' => 'HyperFIDO',
        '8c97a730-3f7b-41a6-87d6-1e9b62bda6f0' => 'FIDO KeyPass S3',
        'aeb6569c-f8fb-4950-ac60-24ca2bbe2e52' => 'HUAWEI Mobile',
    ];

    public static function nameFor(?string $aaguid): ?string
    {
        if ($aaguid === null || $aaguid === '') {
            return null;
        }

        $normalized = strtolower($aaguid);

        if ($normalized === '00000000-0000-0000-0000-000000000000') {
            return null;
        }

        return self::NAMES[$normalized] ?? null;
    }
}
