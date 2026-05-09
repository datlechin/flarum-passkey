<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\Throttle;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Per-IP throttler for the passkey login endpoint.
 *
 * Throttling lives close to the auth surface (rather than relying on a reverse
 * proxy alone) because the cost of a failed assertion is dominated by the
 * cryptographic verification, which happens in PHP. Limiting at the framework
 * layer is what stops a botnet of clients each below the proxy's per-IP cap
 * from collectively exhausting CPU.
 *
 * The throttler key is salted with the route name so a request to a different
 * endpoint never increments the bucket.
 */
class PasskeyLoginThrottler
{
    private const ROUTE_NAME = 'datlechin-passkey.login';
    private const DEFAULT_PER_MINUTE = 10;

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly SettingsRepositoryInterface $settings,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ?bool
    {
        if ($request->getAttribute('routeName') !== self::ROUTE_NAME) {
            return null;
        }

        $limit = $this->limit();
        $bucket = 'datlechin-passkey.throttle.'.($request->getAttribute('ipAddress') ?? 'unknown');

        $count = (int) ($this->cache->get($bucket) ?? 0);

        if ($count >= $limit) {
            return true;
        }

        $this->cache->put($bucket, $count + 1, 60);

        return false;
    }

    private function limit(): int
    {
        $configured = (int) $this->settings->get('datlechin-passkey.throttle_per_minute');

        return $configured > 0 ? $configured : self::DEFAULT_PER_MINUTE;
    }
}
