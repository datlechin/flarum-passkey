<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\Tests\integration;

use Datlechin\Passkey\WebAuthn\AuthenticatorMetadata;
use Flarum\Testing\integration\TestCase;

class AuthenticatorMetadataTest extends TestCase
{
    /** @test */
    public function known_aaguids_resolve_to_friendly_names(): void
    {
        $this->assertSame('iCloud Keychain', AuthenticatorMetadata::nameFor('adce0002-35bc-c60a-648b-0b25f1f05503'));
        $this->assertSame('Google Password Manager', AuthenticatorMetadata::nameFor('ea9b8d66-4d01-1d21-3ce4-b6b48cb575d4'));
        $this->assertSame('Windows Hello', AuthenticatorMetadata::nameFor('08987058-cadc-4b81-b6e1-30de50dcbe96'));
        $this->assertSame('1Password', AuthenticatorMetadata::nameFor('bada5566-a7aa-401f-bd96-45619a55120d'));
        $this->assertSame('YubiKey 5 Series', AuthenticatorMetadata::nameFor('cb69481e-8ff7-4039-93ec-0a2729a154a8'));
    }

    /** @test */
    public function lookup_is_case_insensitive(): void
    {
        $this->assertSame(
            'iCloud Keychain',
            AuthenticatorMetadata::nameFor('ADCE0002-35BC-C60A-648B-0B25F1F05503')
        );
    }

    /** @test */
    public function anonymous_aaguid_resolves_to_null(): void
    {
        $this->assertNull(AuthenticatorMetadata::nameFor('00000000-0000-0000-0000-000000000000'));
    }

    /** @test */
    public function unknown_aaguid_resolves_to_null(): void
    {
        $this->assertNull(AuthenticatorMetadata::nameFor('11111111-2222-3333-4444-555555555555'));
    }

    /** @test */
    public function null_or_empty_input_resolves_to_null(): void
    {
        $this->assertNull(AuthenticatorMetadata::nameFor(null));
        $this->assertNull(AuthenticatorMetadata::nameFor(''));
    }
}
