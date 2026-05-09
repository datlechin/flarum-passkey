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

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class GroupPasskeyRequiredTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-passkey');
    }

    #[Test]
    public function migration_adds_the_column_with_a_safe_default(): void
    {
        $this->prepareDatabase([]);

        $columns = $this->app()->getContainer()->make('db')
            ->getSchemaBuilder()
            ->getColumnListing('groups');

        $this->assertContains('passkey_required', $columns);

        // Existing groups inherit the false default; nothing surprises an
        // admin who upgrades without ever opening the per-group toggle.
        $member = Group::find(Group::MEMBER_ID);
        $this->assertNotNull($member);
        $this->assertFalse((bool) $member->passkey_required);
    }

    #[Test]
    public function flag_persists_via_raw_update(): void
    {
        $this->prepareDatabase([]);

        $db = $this->app()->getContainer()->make('db');
        $db->table('groups')
            ->where('id', Group::MEMBER_ID)
            ->update(['passkey_required' => true]);

        $row = $db->table('groups')->where('id', Group::MEMBER_ID)->first();
        $this->assertSame(1, (int) $row->passkey_required);
    }

    #[Test]
    public function non_admin_cannot_flip_the_flag_via_api(): void
    {
        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);

        $response = $this->send($this->request('PATCH', '/api/groups/'.Group::MEMBER_ID, [
            'authenticatedAs' => 2,
            'json' => [
                'data' => [
                    'type' => 'groups',
                    'id' => (string) Group::MEMBER_ID,
                    'attributes' => ['passkeyRequired' => true],
                ],
            ],
        ]));

        $this->assertContains($response->getStatusCode(), [403, 422]);
        $this->assertFalse((bool) Group::find(Group::MEMBER_ID)->passkey_required);
    }
}
