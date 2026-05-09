<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ListPasskeysTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-passkey');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'other', 'email' => 'other@example.com', 'is_email_confirmed' => 1, 'password' => '$2y$10$invalid'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => Group::MEMBER_ID],
            ],
            'passkeys' => [
                [
                    'id' => 1,
                    'user_id' => 2,
                    'credential_id' => 'cred-mac-1234567890abcdef',
                    'public_key_cose' => 'a3JleS1ieXRlcy1hbGljZQ',
                    'signature_count' => 0,
                    'transports' => '["internal"]',
                    'aaguid' => '00000000-0000-0000-0000-000000000000',
                    'attestation_format' => 'none',
                    'backup_eligible' => false,
                    'backup_state' => false,
                    'uv_initialized' => true,
                    'device_name' => 'My MacBook',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'id' => 2,
                    'user_id' => 3,
                    'credential_id' => 'cred-iphone-fedcba0987654321',
                    'public_key_cose' => 'a3JleS1ieXRlcy1ib2I',
                    'signature_count' => 0,
                    'transports' => '["internal"]',
                    'aaguid' => '00000000-0000-0000-0000-000000000000',
                    'attestation_format' => 'none',
                    'backup_eligible' => false,
                    'backup_state' => false,
                    'uv_initialized' => true,
                    'device_name' => 'Their iPhone',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
            ],
        ]);
    }

    #[Test]
    public function guest_cannot_list_passkeys(): void
    {
        $response = $this->send($this->request('GET', '/api/passkeys'));

        $this->assertSame(401, $response->getStatusCode());
    }

    #[Test]
    public function user_only_sees_own_passkeys(): void
    {
        $response = $this->send($this->request('GET', '/api/passkeys', [
            'authenticatedAs' => 2,
        ]));

        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(1, $payload['data']);
        $this->assertSame('1', $payload['data'][0]['id']);
        $this->assertSame('My MacBook', $payload['data'][0]['attributes']['deviceName']);
    }

    #[Test]
    public function user_cannot_delete_someone_elses_passkey(): void
    {
        $response = $this->send($this->request('DELETE', '/api/passkeys/2', [
            'authenticatedAs' => 2,
        ]));

        $this->assertContains($response->getStatusCode(), [403, 404]);
    }

    #[Test]
    public function user_can_rename_own_passkey(): void
    {
        $response = $this->send($this->request('PATCH', '/api/passkeys/1', [
            'authenticatedAs' => 2,
            'json' => [
                'data' => [
                    'type' => 'passkeys',
                    'id' => '1',
                    'attributes' => ['deviceName' => 'Renamed'],
                ],
            ],
        ]));

        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertSame('Renamed', $payload['data']['attributes']['deviceName']);
    }
}
