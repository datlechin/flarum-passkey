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

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\TestCase;

class WellKnownTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-passkey');
    }

    /** @test */
    public function returns_empty_origins_by_default(): void
    {
        $response = $this->send($this->request('GET', '/.well-known/webauthn'));

        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertSame(['origins' => []], $payload);
    }

    /** @test */
    public function reflects_configured_related_origins(): void
    {
        $this->prepareDatabase([]);

        $this->app()
            ->getContainer()
            ->make(SettingsRepositoryInterface::class)
            ->set(
                'datlechin-passkey.related_origins',
                "https://app.example.com\nhttps://forum.example.org"
            );

        $response = $this->send($this->request('GET', '/.well-known/webauthn'));

        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertSame(
            [
                'origins' => [
                    'https://app.example.com',
                    'https://forum.example.org',
                ],
            ],
            $payload
        );
    }
}
