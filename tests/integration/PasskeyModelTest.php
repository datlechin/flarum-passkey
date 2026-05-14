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

use Datlechin\Passkey\Model\Passkey;
use Flarum\Testing\integration\TestCase;
use Symfony\Component\Uid\Uuid;
use Webauthn\TrustPath\EmptyTrustPath;

class PasskeyModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-passkey');
        $this->prepareDatabase([]);
    }

    /** @test */
    public function base64_url_round_trips_arbitrary_bytes(): void
    {
        $samples = [
            random_bytes(16),
            random_bytes(32),
            random_bytes(64),
            random_bytes(257),
            "\x00\xff\x80\x7f", // boundary bytes
        ];

        foreach ($samples as $raw) {
            $encoded = Passkey::base64UrlEncode($raw);

            $this->assertStringNotContainsString('+', $encoded);
            $this->assertStringNotContainsString('/', $encoded);
            $this->assertStringNotContainsString('=', $encoded);
            $this->assertSame($raw, Passkey::base64UrlDecode($encoded));
        }
    }

    /** @test */
    public function to_credential_record_round_trip(): void
    {
        $rawCredentialId = random_bytes(32);
        $rawPublicKey = random_bytes(64);

        $passkey = new Passkey();
        $passkey->user_id = 42;
        $passkey->credential_id = Passkey::base64UrlEncode($rawCredentialId);
        $passkey->public_key_cose = Passkey::base64UrlEncode($rawPublicKey);
        $passkey->signature_count = 7;
        $passkey->transports = ['internal', 'hybrid'];
        $passkey->aaguid = '550e8400-e29b-41d4-a716-446655440000';
        $passkey->attestation_format = 'packed';
        $passkey->backup_eligible = true;
        $passkey->backup_state = true;
        $passkey->uv_initialized = true;

        $record = $passkey->toCredentialRecord();

        $this->assertSame($rawCredentialId, $record->publicKeyCredentialId);
        $this->assertSame($rawPublicKey, $record->credentialPublicKey);
        $this->assertSame(7, $record->counter);
        $this->assertSame(['internal', 'hybrid'], $record->transports);
        $this->assertSame('packed', $record->attestationType);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $record->aaguid->toRfc4122());
        $this->assertTrue($record->backupEligible);
        $this->assertTrue($record->backupStatus);
        $this->assertTrue($record->uvInitialized);
        $this->assertInstanceOf(EmptyTrustPath::class, $record->trustPath);
        $this->assertSame('42', $record->userHandle);
    }

    /** @test */
    public function sync_from_credential_record_updates_mutable_fields(): void
    {
        $passkey = new Passkey();
        $passkey->signature_count = 0;
        $passkey->backup_state = false;
        $passkey->backup_eligible = false;
        $passkey->uv_initialized = false;

        $record = new \Webauthn\CredentialRecord(
            publicKeyCredentialId: 'irrelevant',
            type: 'public-key',
            transports: [],
            attestationType: 'none',
            trustPath: new EmptyTrustPath(),
            aaguid: Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            credentialPublicKey: 'irrelevant',
            userHandle: '1',
            counter: 99,
            backupEligible: true,
            backupStatus: true,
            uvInitialized: true,
        );

        $passkey->syncFromCredentialRecord($record);

        $this->assertSame(99, $passkey->signature_count);
        $this->assertTrue($passkey->backup_eligible);
        $this->assertTrue($passkey->backup_state);
        $this->assertTrue($passkey->uv_initialized);
    }
}
