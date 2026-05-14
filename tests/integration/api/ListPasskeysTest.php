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
use Datlechin\Passkey\Event\PasskeyRevoked;
use Datlechin\Passkey\Model\Passkey;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class ListPasskeysTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-passkey');

        $this->prepareDatabase([
            'users' => [
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
            'login_providers' => [
                ['user_id' => 2, 'provider' => 'passkey', 'identifier' => 'cred-mac-1234567890abcdef', 'created_at' => Carbon::now(), 'last_login_at' => Carbon::now()],
                ['user_id' => 3, 'provider' => 'passkey', 'identifier' => 'cred-iphone-fedcba0987654321', 'created_at' => Carbon::now(), 'last_login_at' => Carbon::now()],
            ],
        ]);
    }

    /** @test */
    public function guest_cannot_list_passkeys(): void
    {
        $response = $this->send($this->request('GET', '/api/passkeys'));

        $this->assertSame(401, $response->getStatusCode());
    }

    /** @test */
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

    /** @test */
    public function user_cannot_delete_someone_elses_passkey(): void
    {
        $response = $this->send($this->request('DELETE', '/api/passkeys/2', [
            'authenticatedAs' => 2,
        ]));

        $this->assertContains($response->getStatusCode(), [403, 404]);
    }

    /** @test */
    public function user_can_revoke_own_passkey(): void
    {
        $captured = [];
        $this->app()->getContainer()->make('events')->listen(
            PasskeyRevoked::class,
            function (PasskeyRevoked $event) use (&$captured) {
                $captured[] = $event;
            }
        );

        $response = $this->send($this->request('DELETE', '/api/passkeys/1', [
            'authenticatedAs' => 2,
        ]));

        $this->assertSame(204, $response->getStatusCode());

        // The passkey row is gone, and its login_providers link was cascaded
        // away by the Passkey model's `deleted` boot hook.
        $this->assertNull(Passkey::find(1));
        $this->assertSame(0, $this->providerCount('cred-mac-1234567890abcdef'));

        // The other user's credential is untouched.
        $this->assertNotNull(Passkey::find(2));
        $this->assertSame(1, $this->providerCount('cred-iphone-fedcba0987654321'));

        // PasskeyRevoked fired exactly once, naming the owner and the actor.
        $this->assertCount(1, $captured);
        $this->assertSame(2, $captured[0]->owner->id);
        $this->assertSame(2, $captured[0]->actor->id);
    }

    private function providerCount(string $identifier): int
    {
        return $this->app()->getContainer()->make('db')
            ->table('login_providers')
            ->where('provider', 'passkey')
            ->where('identifier', $identifier)
            ->count();
    }

    /** @test */
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

    /**
     * @test
     *
     * @dataProvider invalidDeviceNames
     */
    public function rename_rejects_an_out_of_range_device_name(mixed $deviceName): void
    {
        $response = $this->send($this->request('PATCH', '/api/passkeys/1', [
            'authenticatedAs' => 2,
            'json' => [
                'data' => [
                    'type' => 'passkeys',
                    'id' => '1',
                    'attributes' => ['deviceName' => $deviceName],
                ],
            ],
        ]));

        // Mirrors the 2.x Schema\Str minLength(1)/maxLength(64) contract.
        $this->assertSame(422, $response->getStatusCode());

        // The rejected write must not have touched the stored name.
        $this->assertSame('My MacBook', Passkey::find(1)->device_name);
    }

    public static function invalidDeviceNames(): array
    {
        return [
            'empty' => [''],
            'whitespace only' => ['   '],
            'longer than 64 chars' => [str_repeat('a', 65)],
            'non-string' => [['not', 'a', 'string']],
        ];
    }
}
