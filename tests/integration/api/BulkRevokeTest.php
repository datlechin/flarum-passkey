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
use Datlechin\Passkey\Event\PasskeyBulkRevoked;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class BulkRevokeTest extends TestCase
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
                $this->passkeyRow(1, 2, 'Mac'),
                $this->passkeyRow(2, 2, 'iPhone'),
                $this->passkeyRow(3, 3, 'Windows'),
            ],
            'login_providers' => [
                ['user_id' => 2, 'provider' => 'passkey', 'identifier' => 'cred-1', 'created_at' => Carbon::now(), 'last_login_at' => Carbon::now()],
                ['user_id' => 2, 'provider' => 'passkey', 'identifier' => 'cred-2', 'created_at' => Carbon::now(), 'last_login_at' => Carbon::now()],
                ['user_id' => 3, 'provider' => 'passkey', 'identifier' => 'cred-3', 'created_at' => Carbon::now(), 'last_login_at' => Carbon::now()],
            ],
        ]);
    }

    #[Test]
    public function guest_cannot_bulk_revoke(): void
    {
        $response = $this->send($this->request('DELETE', '/api/passkey/bulk-revoke'));
        // 400 covers the CSRF token rejection that fires before auth check.
        $this->assertContains($response->getStatusCode(), [400, 401, 403]);
    }

    #[Test]
    public function user_can_revoke_all_their_passkeys_in_one_call(): void
    {
        $captured = [];
        $this->app()->getContainer()->make('events')->listen(
            PasskeyBulkRevoked::class,
            function (PasskeyBulkRevoked $event) use (&$captured) {
                $captured[] = $event;
            }
        );

        $response = $this->send($this->request('DELETE', '/api/passkey/bulk-revoke', [
            'authenticatedAs' => 2,
        ]));

        $this->assertSame(204, $response->getStatusCode());

        // Owner's two passkeys are gone; the other user's row is untouched.
        $this->assertSame(0, $this->countPasskeys(2));
        $this->assertSame(1, $this->countPasskeys(3));

        // Owner's login_providers are gone; the other user's link is untouched.
        $this->assertSame(0, $this->countProviders(2));
        $this->assertSame(1, $this->countProviders(3));

        // Single bulk event with the right count and actor.
        $this->assertCount(1, $captured);
        $this->assertSame(2, $captured[0]->count);
        $this->assertSame(2, $captured[0]->owner->id);
        $this->assertSame(2, $captured[0]->actor->id);
    }

    #[Test]
    public function user_with_no_passkeys_gets_204_and_no_event(): void
    {
        $captured = 0;
        $this->app()->getContainer()->make('events')->listen(
            PasskeyBulkRevoked::class,
            function () use (&$captured) {
                $captured++;
            }
        );

        $response = $this->send($this->request('DELETE', '/api/passkey/bulk-revoke', [
            'authenticatedAs' => 1, // admin from RetrievesAuthorizedUsers; has no passkeys
        ]));

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame(0, $captured);
    }

    /**
     * @return array<string, mixed>
     */
    private function passkeyRow(int $id, int $userId, string $name): array
    {
        return [
            'id' => $id,
            'user_id' => $userId,
            'credential_id' => "cred-{$id}",
            'public_key_cose' => 'a3JleS1ieXRlcw',
            'signature_count' => 0,
            'transports' => '["internal"]',
            'aaguid' => '00000000-0000-0000-0000-000000000000',
            'attestation_format' => 'none',
            'backup_eligible' => false,
            'backup_state' => false,
            'uv_initialized' => true,
            'device_name' => $name,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    private function countPasskeys(int $userId): int
    {
        $rows = $this->app()->getContainer()->make('db')
            ->table('passkeys')->where('user_id', $userId)->get()->all();

        return count($rows);
    }

    private function countProviders(int $userId): int
    {
        $rows = $this->app()->getContainer()->make('db')
            ->table('login_providers')
            ->where('user_id', $userId)
            ->where('provider', 'passkey')
            ->get()->all();

        return count($rows);
    }
}
