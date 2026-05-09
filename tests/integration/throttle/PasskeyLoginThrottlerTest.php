<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\Tests\integration\throttle;

use Datlechin\Passkey\Throttle\PasskeyLoginThrottler;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\TestCase;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;

class PasskeyLoginThrottlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-passkey');
        $this->prepareDatabase([]);
    }

    #[Test]
    public function it_only_acts_on_the_login_route(): void
    {
        $throttler = $this->make($limit = 5);

        $result = $throttler($this->buildRequest(routeName: 'datlechin-passkey.registration', ip: '203.0.113.1'));

        $this->assertNull($result);
    }

    #[Test]
    public function it_allows_requests_below_the_limit(): void
    {
        $throttler = $this->make($limit = 3);
        $request = $this->buildRequest(routeName: 'datlechin-passkey.login', ip: '203.0.113.5');

        $this->assertFalse($throttler($request));
        $this->assertFalse($throttler($request));
        $this->assertFalse($throttler($request));
    }

    #[Test]
    public function it_blocks_requests_at_or_above_the_limit(): void
    {
        $throttler = $this->make($limit = 2);
        $request = $this->buildRequest(routeName: 'datlechin-passkey.login', ip: '203.0.113.7');

        $throttler($request);
        $throttler($request);

        $this->assertTrue($throttler($request));
    }

    #[Test]
    public function buckets_are_isolated_per_ip(): void
    {
        $throttler = $this->make($limit = 1);

        $a = $this->buildRequest(routeName: 'datlechin-passkey.login', ip: '203.0.113.1');
        $b = $this->buildRequest(routeName: 'datlechin-passkey.login', ip: '203.0.113.2');

        $throttler($a);
        $this->assertTrue($throttler($a));
        $this->assertFalse($throttler($b));
    }

    private function make(int $limit): PasskeyLoginThrottler
    {
        $cache = new Repository(new ArrayStore());

        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
        $settings->set('datlechin-passkey.throttle_per_minute', (string) $limit);

        return new PasskeyLoginThrottler($cache, $settings);
    }

    private function buildRequest(string $routeName, string $ip): ServerRequest
    {
        return (new ServerRequest())
            ->withAttribute('routeName', $routeName)
            ->withAttribute('ipAddress', $ip);
    }
}
